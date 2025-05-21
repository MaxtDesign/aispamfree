<?php
/**
 * OpenAI service implementation for AI Spam Shield
 *
 * @package AI_Spam_Shield
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI_Spam_Shield_OpenAI class
 * 
 * Implements the OpenAI service for spam detection
 */
class AI_Spam_Shield_OpenAI extends AI_Spam_Shield_Service {
    
    /**
     * Service ID
     */
    protected $service_id = 'openai';
    
    /**
     * API Base URL
     */
    private $api_base_url = 'https://api.openai.com/v1';
    
    /**
     * Constructor
     *
     * @param AI_Spam_Shield $main Main plugin instance
     */
    public function __construct($main) {
        parent::__construct($main);
    }
    
    /**
     * Test API connection
     *
     * @param string $api_key API key to test
     * @return bool|string True on success, error message on failure
     */
    public function test_connection($api_key) {
        $response = wp_remote_get($this->api_base_url . '/models', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'timeout' => 15,
        ));
        
        $this->log_request('models', array('method' => 'GET'), $response);
        
        if (is_wp_error($response)) {
            return $response->get_error_message();
        }
        
        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            return $this->format_error($response);
        }
        
        return true;
    }
    
    /**
     * Detect spam in a message
     *
     * @param string $prompt The prepared prompt
     * @param string $model The model to use
     * @return bool Whether the message is spam
     */
    public function detect_spam($prompt, $model) {
        $api_key = $this->main->get_setting('openai_api_key', '');
        if (empty($api_key)) {
            return false;
        }
        $use_cache = $this->main->get_setting('cache_results', false);
        $cache_duration = $this->main->get_setting('cache_duration', 24) * HOUR_IN_SECONDS;
        $cache_key = 'ai_spam_shield_openai_' . md5($model . '|' . $prompt);
        if ($use_cache) {
            $cached = get_transient($cache_key);
            if ($cached !== false) {
                return $cached;
            }
        }
        $timeout = $this->main->get_setting('api_timeout', 15);
        $response = wp_remote_post($this->api_base_url . '/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'timeout' => $timeout,
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
        $this->log_request('chat/completions', array(
            'model' => $model,
            'prompt_length' => strlen($prompt)
        ), $response);
        if (is_wp_error($response)) {
            return false;
        }
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if (isset($data['choices'][0]['message']['content'])) {
            $result = json_decode($data['choices'][0]['message']['content'], true);
            $is_spam = (isset($result['is_spam']) && $result['is_spam'] === true);
            if ($is_spam && $this->main->get_setting('log_detections', true)) {
                $this->log_spam_detection($result, 'openai', $model);
            }
            if ($use_cache) {
                set_transient($cache_key, $is_spam, $cache_duration);
            }
            return $is_spam;
        }
        return false;
    }
    
    /**
     * Get full analysis results
     *
     * @param string $prompt The prepared prompt
     * @param string $model The model to use
     * @return array|bool False on failure, result array on success
     */
    public function get_full_analysis($prompt, $model) {
        $api_key = $this->main->get_setting('openai_api_key', '');
        if (empty($api_key)) {
            return false;
        }
        $use_cache = $this->main->get_setting('cache_results', false);
        $cache_duration = $this->main->get_setting('cache_duration', 24) * HOUR_IN_SECONDS;
        $cache_key = 'ai_spam_shield_openai_full_' . md5($model . '|' . $prompt);
        if ($use_cache) {
            $cached = get_transient($cache_key);
            if ($cached !== false) {
                return $cached;
            }
        }
        $timeout = $this->main->get_setting('api_timeout', 15);
        $response = wp_remote_post($this->api_base_url . '/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'timeout' => $timeout,
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
        $this->log_request('chat/completions', array(
            'model' => $model,
            'prompt_length' => strlen($prompt)
        ), $response);
        if (is_wp_error($response)) {
            return false;
        }
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if (isset($data['choices'][0]['message']['content'])) {
            $result = json_decode($data['choices'][0]['message']['content'], true);
            if (isset($result['is_spam'])) {
                if ($use_cache) {
                    set_transient($cache_key, $result, $cache_duration);
                }
                return $result;
            }
        }
        return false;
    }
    
    /**
     * Get available models
     *
     * @return array List of available models
     */
    public function get_models() {
        return array(
            'gpt-4o' => __('GPT-4o (Recommended)', 'ai-spam-shield'),
            'gpt-4' => __('GPT-4', 'ai-spam-shield'),
            'gpt-3.5-turbo' => __('GPT-3.5 Turbo (Faster)', 'ai-spam-shield'),
        );
    }
    
    /**
     * Log spam detection
     *
     * @param array $result Analysis result
     * @param string $service Service used
     * @param string $model Model used
     */
    private function log_spam_detection($result, $service, $model) {
        $logs = get_option('ai_spam_shield_logs', array());
        
        $log = array(
            'timestamp' => current_time('timestamp'),
            'service' => $service,
            'model' => $model,
            'is_spam' => true,
            'confidence' => isset($result['confidence']) ? $result['confidence'] : 1.0,
            'reason' => isset($result['reason']) ? $result['reason'] : __('Unknown', 'ai-spam-shield'),
        );
        
        // Try to determine spam type
        if (isset($result['reason'])) {
            $reason = strtolower($result['reason']);
            
            if (strpos($reason, 'phish') !== false) {
                $log['type'] = 'phishing';
            } elseif (strpos($reason, 'sales pitch') !== false || strpos($reason, 'product') !== false || strpos($reason, 'service') !== false) {
                $log['type'] = 'sales_pitch';
            } elseif (strpos($reason, 'promot') !== false || strpos($reason, 'advertis') !== false) {
                $log['type'] = 'promotional';
            } elseif (strpos($reason, 'collaborat') !== false || strpos($reason, 'partner') !== false) {
                $log['type'] = 'collaboration';
            } else {
                $log['type'] = 'other';
            }
        } else {
            $log['type'] = 'other';
        }
        
        $logs[] = $log;
        
        // Limit the number of logs based on retention setting
        $retention_days = $this->main->get_setting('log_retention', 30);
        $cutoff = current_time('timestamp') - ($retention_days * DAY_IN_SECONDS);
        
        $filtered_logs = array_filter($logs, function($log) use ($cutoff) {
            return $log['timestamp'] >= $cutoff;
        });
        
        update_option('ai_spam_shield_logs', $filtered_logs);
    }
}