<?php
/**
 * Base AI service class for AI Spam Shield
 *
 * @package AI_Spam_Shield
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI_Spam_Shield_Service abstract class
 * 
 * Base class for AI service implementations
 */
abstract class AI_Spam_Shield_Service {
    
    /**
     * Main plugin instance
     */
    protected $main;
    
    /**
     * Service ID
     */
    protected $service_id;
    
    /**
     * Constructor
     *
     * @param AI_Spam_Shield $main Main plugin instance
     */
    public function __construct($main) {
        $this->main = $main;
    }
    
    /**
     * Get service ID
     *
     * @return string Service ID
     */
    public function get_id() {
        return $this->service_id;
    }
    
    /**
     * Test API connection
     *
     * @param string $api_key API key to test
     * @return bool|string True on success, error message on failure
     */
    abstract public function test_connection($api_key);
    
    /**
     * Detect spam in a message
     *
     * @param string $prompt The prepared prompt
     * @param string $model The model to use
     * @return bool Whether the message is spam
     */
    abstract public function detect_spam($prompt, $model);
    
    /**
     * Get full analysis results
     *
     * @param string $prompt The prepared prompt
     * @param string $model The model to use
     * @return array|bool False on failure, result array on success
     */
    abstract public function get_full_analysis($prompt, $model);
    
    /**
     * Get available models
     *
     * @return array List of available models
     */
    abstract public function get_models();
    
    /**
     * Log API request
     *
     * @param string $endpoint API endpoint
     * @param array $request_data Request data
     * @param array|WP_Error $response Response or error
     */
    protected function log_request($endpoint, $request_data, $response) {
        if (!$this->main->get_setting('debug_mode', false)) {
            return;
        }
        
        $log = array(
            'timestamp' => current_time('timestamp'),
            'service' => $this->service_id,
            'endpoint' => $endpoint,
            'request' => $request_data,
            'response' => is_wp_error($response) ? $response->get_error_message() : wp_remote_retrieve_response_code($response),
        );
        
        $logs = get_option('ai_spam_shield_api_logs', array());
        $logs[] = $log;
        
        // Keep only the last 100 logs
        if (count($logs) > 100) {
            $logs = array_slice($logs, -100);
        }
        
        update_option('ai_spam_shield_api_logs', $logs);
    }
    
    /**
     * Format error message
     *
     * @param WP_Error|array $response Response or WP_Error
     * @return string Formatted error message
     */
    protected function format_error($response) {
        if (is_wp_error($response)) {
            return $response->get_error_message();
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($data && isset($data['error']) && isset($data['error']['message'])) {
            return $data['error']['message'];
        }
        
        return sprintf(__('API returned error code: %s', 'ai-spam-shield'), $code);
    }
}