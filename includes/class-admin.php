<?php
/**
 * Admin functionality for AI Spam Shield
 *
 * @package AI_Spam_Shield
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI_Spam_Shield_Admin class
 * 
 * Handles all admin-related functionality for the AI Spam Shield plugin
 */
class AI_Spam_Shield_Admin {
    
    /**
     * Main plugin instance
     */
    private $main;
    
    /**
     * Constructor
     *
     * @param AI_Spam_Shield $main Main plugin instance
     */
    public function __construct($main) {
        $this->main = $main;
        
        // Admin hooks
        add_action('admin_menu', array($this, 'create_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // AJAX handlers
        add_action('wp_ajax_ai_spam_shield_test_api', array($this, 'ajax_test_api'));
        add_action('wp_ajax_ai_spam_shield_test_message', array($this, 'ajax_test_message'));
        
        // Dashboard widget
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        
        // Plugin action links
        add_filter('plugin_action_links_' . plugin_basename(AI_SPAM_SHIELD_PLUGIN_FILE), array($this, 'add_plugin_action_links'));
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
        register_setting('ai_spam_shield_settings', 'ai_spam_shield_settings', array($this, 'sanitize_settings'));
    }
    
    /**
     * Sanitize settings
     *
     * @param array $input Settings input
     * @return array Sanitized settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // Checkbox fields
        $checkbox_fields = array(
            'active',
            'check_contact_form_7',
            'check_wpforms',
            'check_gravity_forms',
            'check_comments',
            'detect_phishing',
            'detect_sales_pitch',
            'detect_promotional',
            'detect_collaboration',
            'log_detections',
            'cache_results',
            'debug_mode',
            'skip_logged_in_users',
            'cf7_check_all_forms',
            'wpforms_check_all_forms',
            'gravity_forms_check_all_forms'
        );
        
        foreach ($checkbox_fields as $field) {
            $sanitized[$field] = isset($input[$field]) ? (bool) $input[$field] : false;
        }
        
        // Text fields
        if (isset($input['openai_api_key'])) {
            $sanitized['openai_api_key'] = sanitize_text_field($input['openai_api_key']);
        }
        
        if (isset($input['anthropic_api_key'])) {
            $sanitized['anthropic_api_key'] = sanitize_text_field($input['anthropic_api_key']);
        }
        
        if (isset($input['gemini_api_key'])) {
            $sanitized['gemini_api_key'] = sanitize_text_field($input['gemini_api_key']);
        }
        
        if (isset($input['notification_email'])) {
            $sanitized['notification_email'] = sanitize_email($input['notification_email']);
        }
        
        // Dropdown fields
        if (isset($input['api_provider'])) {
            $sanitized['api_provider'] = sanitize_key($input['api_provider']);
        }
        
        if (isset($input['ai_model'])) {
            $sanitized['ai_model'] = sanitize_text_field($input['ai_model']);
        }
        
        if (isset($input['log_retention'])) {
            $sanitized['log_retention'] = absint($input['log_retention']);
        }
        
        // Numeric fields
        if (isset($input['confidence_threshold'])) {
            $sanitized['confidence_threshold'] = max(0.1, min(0.9, floatval($input['confidence_threshold'])));
        }
        
        if (isset($input['api_timeout'])) {
            $sanitized['api_timeout'] = max(5, min(60, absint($input['api_timeout'])));
        }
        
        if (isset($input['cache_duration'])) {
            $sanitized['cache_duration'] = max(1, min(168, absint($input['cache_duration'])));
        }
        
        // Textarea fields
        if (isset($input['custom_message'])) {
            $sanitized['custom_message'] = sanitize_textarea_field($input['custom_message']);
        }
        
        if (isset($input['custom_spam_types'])) {
            $sanitized['custom_spam_types'] = sanitize_textarea_field($input['custom_spam_types']);
        }
        
        if (isset($input['custom_prompt'])) {
            $sanitized['custom_prompt'] = sanitize_textarea_field($input['custom_prompt']);
        }
        
        return $sanitized;
    }
    
    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook Current admin page
     */
    public function enqueue_admin_assets($hook) {
        if ('settings_page_ai-spam-shield' !== $hook) {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'ai-spam-shield-admin',
            plugin_dir_url(AI_SPAM_SHIELD_PLUGIN_FILE) . 'assets/css/admin.css',
            array(),
            AI_SPAM_SHIELD_VERSION
        );
        
        // Enqueue JS
        wp_enqueue_script(
            'ai-spam-shield-admin',
            plugin_dir_url(AI_SPAM_SHIELD_PLUGIN_FILE) . 'js/admin.js',
            array('jquery'),
            AI_SPAM_SHIELD_VERSION,
            true
        );
        
        // Localize script with nonce and ajax url
        wp_localize_script('ai-spam-shield-admin', 'aiSpamShield', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_spam_shield_nonce')
        ));
    }
    
    /**
     * AJAX handler for testing API connection
     */
    public function ajax_test_api() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ai_spam_shield_nonce')) {
            wp_send_json_error(array('message' => esc_html__('Security check failed.', 'ai-spam-shield')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => esc_html__('You do not have permission to perform this action.', 'ai-spam-shield')));
        }
        
        $provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '';
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        
        if (empty($provider) || empty($api_key)) {
            wp_send_json_error(array('message' => esc_html__('Missing provider or API key.', 'ai-spam-shield')));
        }
        
        // Test API connection based on provider
        $result = false;
        $error_message = '';
        
        switch ($provider) {
            case 'openai':
                $result = $this->main->get_service('openai')->test_connection($api_key);
                break;
                
            case 'anthropic':
                $result = $this->main->get_service('anthropic')->test_connection($api_key);
                break;
                
            case 'gemini':
                $result = $this->main->get_service('gemini')->test_connection($api_key);
                break;
                
            default:
                $error_message = esc_html__('Invalid provider selected.', 'ai-spam-shield');
                break;
        }
        
        if ($result === true) {
            wp_send_json_success(array('message' => esc_html__('API connection successful!', 'ai-spam-shield')));
        } else {
            wp_send_json_error(array('message' => $result));
        }
    }
    
    /**
     * AJAX handler for testing a message
     */
    public function ajax_test_message() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ai_spam_shield_nonce')) {
            wp_send_json_error(array('message' => esc_html__('Security check failed.', 'ai-spam-shield')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => esc_html__('You do not have permission to perform this action.', 'ai-spam-shield')));
        }
        
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        
        if (empty($message)) {
            wp_send_json_error(array('message' => esc_html__('No message provided.', 'ai-spam-shield')));
        }
        
        // Send to processing
        $result = $this->main->analyze_message($message, 'Test Message');
        
        if ($result === false) {
            wp_send_json_error(array('message' => esc_html__('Error processing message with AI.', 'ai-spam-shield')));
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Add dashboard widget
     */
    public function add_dashboard_widget() {
        if (current_user_can('manage_options')) {
            wp_add_dashboard_widget(
                'ai_spam_shield_dashboard_widget',
                __('AI Spam Shield Stats', 'ai-spam-shield'),
                array($this, 'render_dashboard_widget')
            );
        }
    }
    
    /**
     * Render dashboard widget
     */
    public function render_dashboard_widget() {
        $stats = $this->get_spam_stats();
        
        echo '<div class="ai-spam-shield-stats">';
        
        echo '<div class="ai-spam-shield-stat-item">';
        echo '<span class="ai-spam-shield-stat-number">' . esc_html($stats['total_blocked']) . '</span>';
        echo '<span class="ai-spam-shield-stat-label">' . esc_html__('Spam Messages Blocked', 'ai-spam-shield') . '</span>';
        echo '</div>';
        
        echo '<div class="ai-spam-shield-stat-item">';
        echo '<span class="ai-spam-shield-stat-number">' . esc_html($stats['last_7_days']) . '</span>';
        echo '<span class="ai-spam-shield-stat-label">' . esc_html__('Blocked (Last 7 Days)', 'ai-spam-shield') . '</span>';
        echo '</div>';
        
        echo '<div class="ai-spam-shield-stat-breakdown">';
        echo '<h4>' . esc_html__('Breakdown by Type:', 'ai-spam-shield') . '</h4>';
        echo '<ul>';
        foreach ($stats['by_type'] as $type => $count) {
            echo '<li><strong>' . esc_html(ucfirst($type)) . ':</strong> ' . esc_html($count) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
        
        echo '<p class="ai-spam-shield-settings-link"><a href="' . esc_url(admin_url('options-general.php?page=ai-spam-shield')) . '">' . esc_html__('View Settings', 'ai-spam-shield') . '</a></p>';
        
        echo '</div>';
    }
    
    /**
     * Get spam statistics
     * 
     * @return array Statistics data
     */
    private function get_spam_stats() {
        $logs = get_option('ai_spam_shield_logs', array());
        
        $stats = array(
            'total_blocked' => 0,
            'last_7_days' => 0,
            'by_type' => array(
                'phishing' => 0,
                'sales_pitch' => 0,
                'promotional' => 0,
                'collaboration' => 0,
                'other' => 0
            )
        );
        
        if (empty($logs)) {
            return $stats;
        }
        
        $seven_days_ago = time() - (7 * DAY_IN_SECONDS);
        
        foreach ($logs as $log) {
            $stats['total_blocked']++;
            
            if ($log['timestamp'] >= $seven_days_ago) {
                $stats['last_7_days']++;
            }
            
            if (isset($log['type']) && isset($stats['by_type'][$log['type']])) {
                $stats['by_type'][$log['type']]++;
            } else {
                $stats['by_type']['other']++;
            }
        }
        
        return $stats;
    }
    
    /**
     * Add plugin action links
     * 
     * @param array $links Plugin action links
     * @return array Modified links
     */
    public function add_plugin_action_links($links) {
        $settings_link = '<a href="' . esc_url(admin_url('options-general.php?page=ai-spam-shield')) . '">' . esc_html__('Settings', 'ai-spam-shield') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Include the admin page template
        include plugin_dir_path(AI_SPAM_SHIELD_PLUGIN_FILE) . 'templates/admin-page.php';
    }
}