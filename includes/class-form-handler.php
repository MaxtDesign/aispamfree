<?php
/**
 * Form handler for AI Spam Shield
 *
 * @package AI_Spam_Shield
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI_Spam_Shield_Form_Handler class
 * 
 * Handles integration with form plugins and processes form submissions
 */
class AI_Spam_Shield_Form_Handler {
    
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
        
        // Setup hooks based on active integrations
        $this->setup_hooks();
    }
    
    /**
     * Setup hooks for enabled form plugins
     */
    private function setup_hooks() {
        // Only setup if plugin is active
        if (!$this->main->get_setting('active', true)) {
            return;
        }
        
        // Contact Form 7
        if ($this->main->get_setting('check_contact_form_7', true) && $this->is_plugin_active('contact-form-7/wp-contact-form-7.php')) {
            add_filter('wpcf7_before_send_mail', array($this, 'check_cf7_submission'), 10, 3);
        }
        
        // WPForms
        if ($this->main->get_setting('check_wpforms', true) && 
            ($this->is_plugin_active('wpforms-lite/wpforms.php') || $this->is_plugin_active('wpforms/wpforms.php'))) {
            add_action('wpforms_process_complete', array($this, 'check_wpforms_submission'), 10, 4);
        }
        
        // Gravity Forms
        if ($this->main->get_setting('check_gravity_forms', true) && class_exists('GFForms')) {
            add_filter('gform_pre_send_email', array($this, 'check_gravity_forms_submission'), 10, 4);
        }
        
        // WordPress Comments
        if ($this->main->get_setting('check_comments', false)) {
            add_filter('preprocess_comment', array($this, 'check_comment_for_spam'), 10, 1);
        }
    }
    
    /**
     * Check if a plugin is active
     *
     * @param string $plugin Plugin path
     * @return bool Whether the plugin is active
     */
    private function is_plugin_active($plugin) {
        if (!function_exists('is_plugin_active')) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        return is_plugin_active($plugin);
    }
    
    /**
     * Contact Form 7 hook
     *
     * @param WPCF7_ContactForm $contact_form Contact form object
     * @param bool $abort Whether to abort
     * @param WPCF7_Submission $submission Submission object
     * @return WPCF7_ContactForm Contact form object
     */
    public function check_cf7_submission($contact_form, $abort, $submission) {
        // Skip if already aborted
        if ($abort) {
            return $contact_form;
        }
        
        // Skip specific forms if not configured to check all
        if (!$this->main->get_setting('cf7_check_all_forms', true)) {
            $forms_to_check = $this->main->get_setting('cf7_forms', array());
            if (!in_array($contact_form->id(), $forms_to_check)) {
                return $contact_form;
            }
        }
        
        // Get all submitted data
        $posted_data = $submission->get_posted_data();
        
        if (empty($posted_data)) {
            return $contact_form;
        }
        
        // Prepare message from form data
        $message = $this->prepare_message_from_data($posted_data);
        
        // Check if message is spam
        if ($this->main->is_spam($message, 'Contact Form 7', $posted_data)) {
            // Abort the form submission
            $submission->set_status('spam');
            $submission->set_response($this->get_rejection_message());
            
            // Log the spam submission if enabled
            $this->log_spam_submission('contact-form-7', $contact_form->id(), $message);
            
            // Send notification if enabled
            $this->maybe_send_notification('Contact Form 7', $contact_form->title(), $message);
        }
        
        return $contact_form;
    }
    
    /**
     * WPForms hook
     *
     * @param array $fields Form fields
     * @param array $entry Form entry
     * @param array $form_data Form data
     * @param int $entry_id Entry ID
     */
    public function check_wpforms_submission($fields, $entry, $form_data, $entry_id) {
        // Skip specific forms if not configured to check all
        if (!$this->main->get_setting('wpforms_check_all_forms', true)) {
            $forms_to_check = $this->main->get_setting('wpforms_forms', array());
            if (!in_array($form_data['id'], $forms_to_check)) {
                return;
            }
        }
        
        // Prepare message from form fields
        $message = '';
        foreach ($fields as $field) {
            if (!empty($field['value'])) {
                if (is_array($field['value'])) {
                    $value = implode(', ', $field['value']);
                } else {
                    $value = $field['value'];
                }
                
                $message .= $field['name'] . ": " . $value . "\n";
            }
        }
        
        // Check if message is spam
        if ($this->main->is_spam($message, 'WPForms', $fields)) {
            // Add entry to spam if entry storage is enabled
            if (!empty($entry_id)) {
                wpforms()->entry->update($entry_id, array('status' => 'spam'));
            }
            
            // Add error and stop form processing
            wpforms()->process->errors[$form_data['id']] = $this->get_rejection_message();
            
            // Log the spam submission if enabled
            $this->log_spam_submission('wpforms', $form_data['id'], $message);
            
            // Send notification if enabled
            $this->maybe_send_notification('WPForms', $form_data['settings']['form_title'], $message);
        }
    }
    
    /**
     * Gravity Forms hook
     *
     * @param array $email Email data
     * @param string $message_format Message format
     * @param array $notification Notification data
     * @param array $entry Entry data
     * @return array|bool Email data or false to cancel
     */
    public function check_gravity_forms_submission($email, $message_format, $notification, $entry) {
        // Check if we have an entry and form
        if (empty($entry) || empty($entry['form_id'])) {
            return $email;
        }
        
        // Skip specific forms if not configured to check all
        if (!$this->main->get_setting('gravity_forms_check_all_forms', true)) {
            $forms_to_check = $this->main->get_setting('gravity_forms_forms', array());
            if (!in_array($entry['form_id'], $forms_to_check)) {
                return $email;
            }
        }
        
        // Get form
        $form = GFAPI::get_form($entry['form_id']);
        if (empty($form)) {
            return $email;
        }
        
        // Prepare message from form fields
        $message = '';
        foreach ($form['fields'] as $field) {
            $field_id = $field->id;
            if (isset($entry[$field_id]) && !empty($entry[$field_id])) {
                $message .= $field->label . ": " . $entry[$field_id] . "\n";
            }
        }
        
        // Check if message is spam
        if ($this->main->is_spam($message, 'Gravity Forms', $entry)) {
            // Mark as spam
            GFAPI::update_entry_property($entry['id'], 'status', 'spam');
            
            // Log the spam submission if enabled
            $this->log_spam_submission('gravity-forms', $entry['form_id'], $message);
            
            // Send notification if enabled
            $this->maybe_send_notification('Gravity Forms', $form['title'], $message);
            
            // Don't send email
            return false;
        }
        
        return $email;
    }
    
    /**
     * WordPress comment hook
     *
     * @param array $comment_data Comment data
     * @return array Comment data
     */
    public function check_comment_for_spam($comment_data) {
        // Skip for logged-in users if configured
        if ($this->main->get_setting('skip_logged_in_users', true) && is_user_logged_in()) {
            return $comment_data;
        }
        
        // Extract comment content
        $message = "Author: {$comment_data['comment_author']}\n";
        $message .= "Email: {$comment_data['comment_author_email']}\n";
        $message .= "URL: {$comment_data['comment_author_url']}\n";
        $message .= "Content: {$comment_data['comment_content']}\n";
        
        // Skip if content is very short (likely not spam)
        $content_length = strlen($comment_data['comment_content']);
        $min_length = apply_filters('ai_spam_shield_min_comment_length', 20);
        
        if ($content_length < $min_length) {
            return $comment_data;
        }
        
        // Check if message is spam
        if ($this->main->is_spam($message, 'WordPress Comment', $comment_data)) {
            // Mark as spam
            add_filter('pre_comment_approved', function() { return 'spam'; });
            
            // Log the spam comment if enabled
            $this->log_spam_submission('wordpress-comment', 0, $message);
            
            // Send notification if enabled
            $this->maybe_send_notification('WordPress Comment', __('Comment on post', 'ai-spam-shield') . ' #' . $comment_data['comment_post_ID'], $message);
        }
        
        return $comment_data;
    }
    
    /**
     * Prepare message from form data
     *
     * @param array $data Form data
     * @return string Formatted message
     */
    private function prepare_message_from_data($data) {
        $message = '';
        
        foreach ($data as $key => $value) {
            // Skip internal fields
            if (strpos($key, '_') === 0) {
                continue;
            }
            
            // Format arrays
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            
            // Skip empty values
            if (empty($value)) {
                continue;
            }
            
            // Add field to message
            $field_name = ucfirst(str_replace(array('-', '_'), ' ', $key));
            $message .= $field_name . ": " . $value . "\n";
        }
        
        return $message;
    }
    
    /**
     * Get rejection message
     *
     * @return string Rejection message
     */
    private function get_rejection_message() {
        $default = __('Your message has been identified as potential spam and was not sent.', 'ai-spam-shield');
        return $this->main->get_setting('custom_message', $default);
    }
    
    /**
     * Log spam submission
     *
     * @param string $form_type Form type
     * @param int $form_id Form ID
     * @param string $message Form message
     */
    private function log_spam_submission($form_type, $form_id, $message) {
        if (!$this->main->get_setting('log_submissions', false)) {
            return;
        }
        
        $logs = get_option('ai_spam_shield_submission_logs', array());
        
        $log = array(
            'timestamp' => current_time('timestamp'),
            'form_type' => $form_type,
            'form_id' => $form_id,
            'message' => $message,
            'ip' => $this->get_user_ip(),
        );
        
        $logs[] = $log;
        
        // Limit the number of logs
        $max_logs = apply_filters('ai_spam_shield_max_submission_logs', 100);
        if (count($logs) > $max_logs) {
            $logs = array_slice($logs, -$max_logs);
        }
        
        update_option('ai_spam_shield_submission_logs', $logs);
    }
    
    /**
     * Get user IP address
     *
     * @return string IP address
     */
    private function get_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return sanitize_text_field($ip);
    }
    
    /**
     * Send notification if enabled
     *
     * @param string $form_type Form type
     * @param string $form_title Form title
     * @param string $message Form message
     */
    private function maybe_send_notification($form_type, $form_title, $message) {
        if (!$this->main->get_setting('send_notifications', false)) {
            return;
        }
        
        $to = $this->main->get_setting('notification_email', get_option('admin_email'));
        $subject = sprintf(
            __('[%s] Spam Detected - %s', 'ai-spam-shield'),
            get_bloginfo('name'),
            $form_type
        );
        
        $body = sprintf(
            __("AI Spam Shield has detected spam in a form submission on your website.\n\nForm Type: %s\nForm Title: %s\n\nMessage:\n%s", 'ai-spam-shield'),
            $form_type,
            $form_title,
            $message
        );
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        wp_mail($to, $subject, $body, $headers);
    }
}