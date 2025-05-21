<?php
/**
 * Google Gemini service implementation for AI Spam Shield
 *
 * @package AI_Spam_Shield
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI_Spam_Shield_Gemini class
 * 
 * Implements the Google Gemini service for spam detection
 */
class AI_Spam_Shield_Gemini extends AI_Spam_Shield_Service {
    
    /**
     * Service ID
     */
    protected $service_id = 'gemini';
    
    /**
     * API Base URL
     */
    private $api_base_url = 'https://generativelanguage.googleapis.com/v1/models';
    
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
        $test_model = 'gemini-1.5-pro';
        $url = $this->api_base_url . '/' . $test_model . ':generateContent?key=' . $api_key;
        
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
        
        $this->log_request('generateContent', array('model' => $test_model), $response);
        
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
        $api_key = $this->main->get_setting('gemini_api_key', '');
        if (empty($api_key)) {
            return false;
        }
        $use_cache = $this->main->get_setting('cache_results', false);
        $cache_duration = $this->main->get_setting('cache_duration', 24) * HOUR_IN_SECONDS;
        $cache_key = 'ai_spam_shield_gemini_' . md5($model . '|' . $prompt);
        if ($use_cache) {
            $cached = get_transient($cache_key);
            if ($cached !== false) {
                return $cached;
            }
        }
        $url = $this->api_base_url . '/' . $model . ':generateContent?key=' . $api_key;
        $timeout = $this->main->get_setting('api_timeout', 15);
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'timeout' => $timeout,
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
        $this->log_request('generateContent', array(
            'model' => $model,
            'prompt_length' => strlen($prompt)
        ), $response);
        if (is_wp_error($response)) {
            return false;
        }
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $text = $data['candidates'][0]['content']['parts'][0]['text'];
            if (preg_match('/\{.*\}/s', $text, $matches)) {
                $json_str = $matches[0];
                $result = json_decode($json_str, true);
                $is_spam = (isset($result['is_spam']) && $result['is_spam'] === true);
                if ($is_spam && $this->main->get_setting('log_detections', true)) {
                    $this->log_spam_detection($result, 'gemini', $model);
                }
                if ($use_cache) {
                    set_transient($cache_key, $is_spam, $cache_duration);
                }
                return $is_spam;
            }
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
        $api_key = $this->main->get_setting('gemini_api_key', '');
        if (empty($api_key)) {
            return false;
        }
        $use_cache = $this->main->get_setting('cache_results', false);
        $cache_duration = $this->main->get_setting('cache_duration', 24) * HOUR_IN_SECONDS;
        $cache_key = 'ai_spam_shield_gemini_full_' . md5($model . '|' . $prompt);
        if ($use_cache) {
            $cached = get_transient($cache_key);
            if ($cached !== false) {
                return $cached;
            }
        }
        $url = $this->api_base_url . '/' . $model . ':generateContent?key=' . $api_key;
        $timeout = $this->main->get_setting('api_timeout', 15);
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'timeout' => $timeout,
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
        $this->log_request('generateContent', array(
            'model' => $model,
            'prompt_length' => strlen($prompt)
        ), $response);
        if (is_wp_error($response)) {
            return false;
        }
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $text = $data['candidates'][0]['content']['parts'][0]['text'];
            if (preg_match('/\{.*\}/s', $text, $matches)) {
                $json_str = $matches[0];
                $result = json_decode($json_str, true);
                if (isset($result['is_spam'])) {
                    if ($use_cache) {
                        set_transient($cache_key, $result, $cache_duration);
                    }
                    return $result;
                }
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
            'gemini-1.5-pro' => __('Gemini 1.5 Pro (Recommended)', 'ai-spam-shield'),
            'gemini-1.5-flash' => __('Gemini 1.5 Flash (Faster)', 'ai-spam-shield'),
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