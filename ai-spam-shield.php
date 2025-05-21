<?php
/**
 * Plugin Name: AI Spam Shield
 * Description: Uses AI LLM models to detect spam in contact form submissions
 * Version: 1.0.0
 * Author: MaxtDesign <Cody Hardman>
 * Text Domain: ai-spam-shield
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin version
define('AI_SPAM_SHIELD_VERSION', '1.0.0');

class AI_Spam_Shield {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Plugin settings
     */
    private $settings;
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Load settings
        $this->load_settings();
        
        // Admin settings page
        add_action('admin_menu', array($this, 'create_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_ai_spam_shield_test_api', array($this, 'ajax_test_api'));
        add_action('wp_ajax_ai_spam_shield_test_message', array($this, 'ajax_test_message'));
        
        // Form plugin integrations
        $this->setup_form_integrations();
        
        // Comment integration if enabled
        if ($this->get_setting('check_comments', false)) {
            add_filter('preprocess_comment', array($this, 'check_comment_for_spam'), 10, 1);
        }
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ('settings_page_ai-spam-shield' !== $hook) {
            return;
        }
        
        wp_enqueue_script(
            'ai-spam-shield-admin',
            plugin_dir_url(__FILE__) . 'js/admin.js',
            array('jquery'),
            '1.0.0',
            true
        );
    }
    
    /**
     * AJAX handler for testing a message
     */
    public function ajax_test_message() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ai_spam_shield_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
        }
        
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        
        if (empty($message)) {
            wp_send_json_error(array('message' => 'No message provided.'));
        }
        
        // Validate API settings
        if (!$this->validate_api_settings()) {
            wp_send_json_error(array('message' => 'API not properly configured.'));
        }
        
        // Prepare message for AI
        $prompt = $this->prepare_ai_prompt($message, 'Test Message', array());
        
        // Send to AI service
        $provider = $this->get_setting('api_provider', 'openai');
        $model = $this->get_setting('ai_model', 'gpt-4o');
        $result = false;
        
        switch ($provider) {
            case 'openai':
                $result = $this->get_full_openai_result($prompt, $model);
                break;
            case 'anthropic':
                $result = $this->get_full_anthropic_result($prompt, $model);
                break;
            case 'gemini':
                $result = $this->get_full_gemini_result($prompt, $model);
                break;
        }
        
        if ($result === false) {
            wp_send_json_error(array('message' => 'Error processing message with AI.'));
        }
        
        wp_send_json_success($result);
    }

    /**
     * AJAX handler for testing API connection
     */
    public function ajax_test_api() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ai_spam_shield_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
        }
        
        $provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '';
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        
        if (empty($provider) || empty($api_key)) {
            wp_send_json_error(array('message' => 'Missing provider or API key.'));
        }
        
        // Test API connection based on provider
        $result = false;
        $error_message = '';
        
        switch ($provider) {
            case 'openai':
                $result = $this->test_openai_connection($api_key);
                break;
                
            case 'anthropic':
                $result = $this->test_anthropic_connection($api_key);
                break;
                
            case 'gemini':
                $result = $this->test_gemini_connection($api_key);
                break;
                
            default:
                $error_message = 'Invalid provider selected.';
                break;
        }
        
        if ($result === true) {
            wp_send_json_success();
        } else {
            wp_send_json_error(array('message' => $error_message));
        }
    }
    
    /**
     * Test OpenAI API connection
     */
    private function test_openai_connection($api_key) {
        $response = wp_remote_get('https://api.openai.com/v1/models', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'timeout' => 15,
        ));
        
        if (is_wp_error($response)) {
            return $response->get_error_message();
        }
        
        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            return isset($data['error']['message']) ? $data['error']['message'] : 'API returned error code: ' . $code;
        }
        
        return true;
    }
    
    /**
     * Test Gemini API connection
     */
    private function test_gemini_connection($api_key) {
        $test_model = 'gemini-1.5-pro';
        $url = 'https://generativelanguage.googleapis.com/v1/models/' . $test_model . ':generateContent?key=' . $api_key;
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'timeout' => 15,
            'body' => json_encode(array(
                'contents' => array(
                    array(
                        'role' => 'user',
                        'parts' => array(
                            array('text' => 'Hello')
                        )
                    )
                ),
                'generationConfig' => array(
                    'maxOutputTokens' => 10
                )
            )),
        ));
        
        if (is_wp_error($response)) {
            return $response->get_error_message();
        }
        
        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            return isset($data['error']['message']) ? $data['error']['message'] : 'API returned error code: ' . $code;
        }
        
        return true;
    }
    
    /**
     * Test Anthropic API connection
     */
    private function test_anthropic_connection($api_key) {
        $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
            'headers' => array(
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ),
            'timeout' => 15,
            'body' => json_encode(array(
                'model' => 'claude-3-haiku-20240307',
                'max_tokens' => 10,
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => 'Say hello'
                    )
                )
            )),
        ));
        
        if (is_wp_error($response)) {
            return $response->get_error_message();
        }
        
        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            return isset($data['error']['message']) ? $data['error']['message'] : 'API returned error code: ' . $code;
        }
        
        return true;
    }

    /**
     * Load plugin settings
     */
    private function load_settings() {
        $this->settings = get_option('ai_spam_shield_settings', array(
            'active' => true,
            'api_provider' => 'openai',
            'openai_api_key' => '',
            'anthropic_api_key' => '',
            'gemini_api_key' => '',
            'ai_model' => 'gpt-4o',
            'check_contact_form_7' => true,
            'check_wpforms' => true,
            'check_gravity_forms' => true,
            'check_comments' => false,
            'detect_phishing' => true,
            'detect_sales_pitch' => true,
            'detect_promotional' => true,
            'detect_collaboration' => true,
        ));
    }
    
    /**
     * Setup form plugin integrations
     */
    private function setup_form_integrations() {
        // Contact Form 7
        if ($this->get_setting('check_contact_form_7', true)) {
            add_filter('wpcf7_before_send_mail', array($this, 'check_cf7_submission'), 10, 3);
        }
        
        // WPForms
        if ($this->get_setting('check_wpforms', true)) {
            add_action('wpforms_process_complete', array($this, 'check_wpforms_submission'), 10, 4);
        }
        
        // Gravity Forms
        if ($this->get_setting('check_gravity_forms', true)) {
            add_filter('gform_pre_send_email', array($this, 'check_gravity_forms_submission'), 10, 4);
        }
    }
    
    /**
     * Get a setting value
     */
    private function get_setting($key, $default = '') {
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }
    
    /**
     * Check if submission is spam using AI
     */
    private function is_spam($message, $form_type, $form_data = array()) {
        // Validate API keys and settings
        if (!$this->validate_api_settings()) {
            return false; // Let message through if API not properly configured
        }
        
        // Prepare message for AI
        $ai_prompt = $this->prepare_ai_prompt($message, $form_type, $form_data);
        
        // Send to AI service and get response
        $is_spam = $this->send_to_ai_service($ai_prompt);
        
        return $is_spam;
    }
    
    /**
     * Prepare AI prompt
     */
    private function prepare_ai_prompt($message, $form_type, $form_data) {
        // Collect spam types to detect
        $spam_types = array();
        if ($this->get_setting('detect_phishing', true)) $spam_types[] = 'phishing';
        if ($this->get_setting('detect_sales_pitch', true)) $spam_types[] = 'sales pitch';
        if ($this->get_setting('detect_promotional', true)) $spam_types[] = 'promotional content';
        if ($this->get_setting('detect_collaboration', true)) $spam_types[] = 'unsolicited collaboration requests';
        
        $spam_types_str = implode(', ', $spam_types);
        
        // Build prompt for LLM
        $prompt = "You are an AI spam detection system for website contact forms. ";
        $prompt .= "Analyze the following message submitted through a {$form_type} and determine if it is spam. ";
        $prompt .= "Specifically, check if it contains any of the following: {$spam_types_str}. ";
        $prompt .= "Respond with a JSON object containing 'is_spam' (boolean), 'confidence' (float 0-1), ";
        $prompt .= "and 'reason' (string explaining detection if spam). ";
        $prompt .= "Here's the message: \n\n{$message}";
        
        return $prompt;
    }
    
    /**
     * Send to AI service
     */
    private function send_to_ai_service($prompt) {
        $provider = $this->get_setting('api_provider', 'openai');
        $model = $this->get_setting('ai_model', 'gpt-4o');
        
        switch ($provider) {
            case 'openai':
                return $this->send_to_openai($prompt, $model);
            case 'anthropic':
                return $this->send_to_anthropic($prompt, $model);
            case 'gemini':
                return $this->send_to_gemini($prompt, $model);
            default:
                return false;
        }
    }
    
    /**
     * Send to OpenAI
     */
    private function send_to_openai($prompt, $model) {
        $api_key = $this->get_setting('openai_api_key', '');
        if (empty($api_key)) {
            return false;
        }
        
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'timeout' => 30,
            'body' => json_encode(array(
                'model' => $model,
                'messages' => array(
                    array(
                        'role' => 'system',
                        'content' => 'You are a spam detection system. Respond only with JSON.'
                    ),
                    array(
                        'role' => 'user',
                        'content' => $prompt
                    )
                ),
                'temperature' => 0.1,
                'response_format' => array('type' => 'json_object')
            )),
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['choices'][0]['message']['content'])) {
            $result = json_decode($data['choices'][0]['message']['content'], true);
            return isset($result['is_spam']) && $result['is_spam'] === true;
        }
        
        return false;
    }
    
    /**
     * Send to Anthropic
     */
    private function send_to_anthropic($prompt, $model) {
        $api_key = $this->get_setting('anthropic_api_key', '');
        if (empty($api_key)) {
            return false;
        }
        
        $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
            'headers' => array(
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ),
            'timeout' => 30,
            'body' => json_encode(array(
                'model' => $model,
                'system' => 'You are a spam detection system. Respond only with JSON.',
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => $prompt
                    )
                ),
                'temperature' => 0.1
            )),
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['content'][0]['text'])) {
            $result = json_decode($data['content'][0]['text'], true);
            return isset($result['is_spam']) && $result['is_spam'] === true;
        }
        
        return false;
    }
    
    /**
     * Send to Gemini
     */
    private function send_to_gemini($prompt, $model) {
        $api_key = $this->get_setting('gemini_api_key', '');
        if (empty($api_key)) {
            return false;
        }
        
        $url = 'https://generativelanguage.googleapis.com/v1/models/' . $model . ':generateContent?key=' . $api_key;
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'timeout' => 30,
            'body' => json_encode(array(
                'contents' => array(
                    array(
                        'role' => 'user',
                        'parts' => array(
                            array(
                                'text' => $prompt
                            )
                        )
                    )
                ),
                'generationConfig' => array(
                    'temperature' => 0.1,
                )
            )),
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $text = $data['candidates'][0]['content']['parts'][0]['text'];
            
            // Try to extract JSON from text response
            if (preg_match('/\{.*\}/s', $text, $matches)) {
                $json_str = $matches[0];
                $result = json_decode($json_str, true);
                
                if (isset($result['is_spam'])) {
                    return $result['is_spam'] === true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get full results from OpenAI
     */
    private function get_full_openai_result($prompt, $model) {
        $api_key = $this->get_setting('openai_api_key', '');
        if (empty($api_key)) {
            return false;
        }
        
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'timeout' => 30,
            'body' => json_encode(array(
                'model' => $model,
                'messages' => array(
                    array(
                        'role' => 'system',
                        'content' => 'You are a spam detection system. Respond only with JSON.'
                    ),
                    array(
                        'role' => 'user',
                        'content' => $prompt
                    )
                ),
                'temperature' => 0.1,
                'response_format' => array('type' => 'json_object')
            )),
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['choices'][0]['message']['content'])) {
            $result = json_decode($data['choices'][0]['message']['content'], true);
            if (isset($result['is_spam'])) {
                return $result;
            }
        }
        
        return false;
    }
    
    /**
     * Get full results from Anthropic
     */
    private function get_full_anthropic_result($prompt, $model) {
        $api_key = $this->get_setting('anthropic_api_key', '');
        if (empty($api_key)) {
            return false;
        }
        
        $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
            'headers' => array(
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ),
            'timeout' => 30,
            'body' => json_encode(array(
                'model' => $model,
                'system' => 'You are a spam detection system. Respond only with JSON.',
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => $prompt
                    )
                ),
                'temperature' => 0.1
            )),
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['content'][0]['text'])) {
            $text = $data['content'][0]['text'];
            
            // Try to extract JSON from text response
            if (preg_match('/\{.*\}/s', $text, $matches)) {
                $json_str = $matches[0];
                $result = json_decode($json_str, true);
                
                if (isset($result['is_spam'])) {
                    return $result;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get full results from Gemini
     */
    private function get_full_gemini_result($prompt, $model) {
        $api_key = $this->get_setting('gemini_api_key', '');
        if (empty($api_key)) {
            return false;
        }
        
        $url = 'https://generativelanguage.googleapis.com/v1/models/' . $model . ':generateContent?key=' . $api_key;
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'timeout' => 30,
            'body' => json_encode(array(
                'contents' => array(
                    array(
                        'role' => 'user',
                        'parts' => array(
                            array(
                                'text' => $prompt
                            )
                        )
                    )
                ),
                'generationConfig' => array(
                    'temperature' => 0.1,
                )
            )),
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $text = $data['candidates'][0]['content']['parts'][0]['text'];
            
            // Try to extract JSON from text response
            if (preg_match('/\{.*\}/s', $text, $matches)) {
                $json_str = $matches[0];
                $result = json_decode($json_str, true);
                
                if (isset($result['is_spam'])) {
                    return $result;
                }
            }
        }
        
        return false;
    }

    /**
     * Validate API settings
     */
    private function validate_api_settings() {
        $provider = $this->get_setting('api_provider', '');
        
        if ($provider === 'openai') {
            return !empty($this->get_setting('openai_api_key', ''));
        } else if ($provider === 'anthropic') {
            return !empty($this->get_setting('anthropic_api_key', ''));
        } else if ($provider === 'gemini') {
            return !empty($this->get_setting('gemini_api_key', ''));
        }
        
        return false;
    }

    /**
     * Contact Form 7 hook
     */
    public function check_cf7_submission($contact_form, $abort, $submission) {
        // Get submitted data
        $message = $submission->get_posted_data('your-message');
        $name = $submission->get_posted_data('your-name');
        $email = $submission->get_posted_data('your-email');
        
        // Combine data for spam check
        $full_message = "Name: {$name}\nEmail: {$email}\nMessage: {$message}";
        
        // Check if spam
        if ($this->is_spam($full_message, 'Contact Form 7', $submission->get_posted_data())) {
            // Abort the form submission
            $submission->set_status('spam');
            $submission->set_response(__('Your message has been identified as potential spam and was not sent.', 'ai-spam-shield'));
            return $contact_form;
        }
        
        return $contact_form;
    }
    
    /**
     * WPForms hook
     */
    public function check_wpforms_submission($fields, $entry, $form_data, $entry_id) {
        // Extract message from fields
        $message = '';
        foreach ($fields as $field) {
            if ($field['type'] === 'textarea') {
                $message .= "Message: " . $field['value'] . "\n";
            } else {
                $message .= $field['name'] . ": " . $field['value'] . "\n";
            }
        }
        
        // Check if spam
        if ($this->is_spam($message, 'WPForms', $fields)) {
            // Add entry to spam
            wpforms()->entry->update($entry_id, array('status' => 'spam'));
            
            // Add error and stop form processing
            wpforms()->process->errors[$form_data['id']] = __('Your message has been identified as potential spam and was not sent.', 'ai-spam-shield');
        }
    }
    
    /**
     * Gravity Forms hook
     */
    public function check_gravity_forms_submission($email, $message_format, $notification, $entry) {
        // Extract form fields
        $form = GFAPI::get_form($entry['form_id']);
        $message = '';
        
        foreach ($form['fields'] as $field) {
            $field_id = $field->id;
            if (isset($entry[$field_id])) {
                $message .= $field->label . ": " . $entry[$field_id] . "\n";
            }
        }
        
        // Check if spam
        if ($this->is_spam($message, 'Gravity Forms', $entry)) {
            // Mark as spam
            GFAPI::update_entry_property($entry['id'], 'status', 'spam');
            
            // Don't send email
            return false;
        }
        
        return $email;
    }
    
    /**
     * Comment hook
     */
    public function check_comment_for_spam($comment_data) {
        // Extract comment content
        $message = "Author: {$comment_data['comment_author']}\n";
        $message .= "Email: {$comment_data['comment_author_email']}\n";
        $message .= "URL: {$comment_data['comment_author_url']}\n";
        $message .= "Content: {$comment_data['comment_content']}\n";
        
        // Check if spam
        if ($this->is_spam($message, 'WordPress Comment', $comment_data)) {
            // Mark as spam
            add_filter('pre_comment_approved', function() { return 'spam'; });
        }
        
        return $comment_data;
    }
    
    /**
     * Create admin menu
     */
    public function create_admin_menu() {
        add_options_page(
            __('AI Spam Shield', 'ai-spam-shield'),
            __('AI Spam Shield', 'ai-spam-shield'),
            'manage_options',
            'ai-spam-shield',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('ai_spam_shield_settings', 'ai_spam_shield_settings');
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        // Admin UI code here
        include plugin_dir_path(__FILE__) . 'templates/admin-page.php';
    }
}

// Initialize the plugin
function ai_spam_shield_init() {
    AI_Spam_Shield::get_instance();
}
add_action('plugins_loaded', 'ai_spam_shield_init');