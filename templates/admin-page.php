<?php
/**
 * Admin settings page template for AI Spam Shield
 *
 * @package AI_Spam_Shield
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current settings
$settings = get_option('ai_spam_shield_settings', array());

// Default values
$active = isset($settings['active']) ? $settings['active'] : true;
$api_provider = isset($settings['api_provider']) ? $settings['api_provider'] : 'openai';
$openai_api_key = isset($settings['openai_api_key']) ? $settings['openai_api_key'] : '';
$anthropic_api_key = isset($settings['anthropic_api_key']) ? $settings['anthropic_api_key'] : '';
$gemini_api_key = isset($settings['gemini_api_key']) ? $settings['gemini_api_key'] : '';
$ai_model = isset($settings['ai_model']) ? $settings['ai_model'] : 'gpt-4o';
$check_cf7 = isset($settings['check_contact_form_7']) ? $settings['check_contact_form_7'] : true;
$check_wpforms = isset($settings['check_wpforms']) ? $settings['check_wpforms'] : true;
$check_gravity_forms = isset($settings['check_gravity_forms']) ? $settings['check_gravity_forms'] : true;
$check_comments = isset($settings['check_comments']) ? $settings['check_comments'] : false;
$detect_phishing = isset($settings['detect_phishing']) ? $settings['detect_phishing'] : true;
$detect_sales_pitch = isset($settings['detect_sales_pitch']) ? $settings['detect_sales_pitch'] : true;
$detect_promotional = isset($settings['detect_promotional']) ? $settings['detect_promotional'] : true;
$detect_collaboration = isset($settings['detect_collaboration']) ? $settings['detect_collaboration'] : true;
$confidence_threshold = isset($settings['confidence_threshold']) ? $settings['confidence_threshold'] : 0.7;
$log_detections = isset($settings['log_detections']) ? $settings['log_detections'] : true;
$notification_email = isset($settings['notification_email']) ? $settings['notification_email'] : get_option('admin_email');
$custom_message = isset($settings['custom_message']) ? $settings['custom_message'] : 'Your message has been identified as potential spam and was not sent.';
?>

<div class="wrap ai-spam-shield-admin">
    <h1><?php echo esc_html__('AI Spam Shield Settings', 'ai-spam-shield'); ?></h1>
    
    <?php if (isset($_GET['settings-updated']) && $_GET['settings-updated']) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Settings saved successfully.', 'ai-spam-shield'); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="ai-spam-shield-tabs-wrapper">
        <ul class="ai-spam-shield-tabs">
            <li><a href="#" class="ai-spam-shield-tab-link" data-tab="tab-general"><?php esc_html_e('General', 'ai-spam-shield'); ?></a></li>
            <li><a href="#" class="ai-spam-shield-tab-link" data-tab="tab-providers"><?php esc_html_e('AI Providers', 'ai-spam-shield'); ?></a></li>
            <li><a href="#" class="ai-spam-shield-tab-link" data-tab="tab-integrations"><?php esc_html_e('Form Integrations', 'ai-spam-shield'); ?></a></li>
            <li><a href="#" class="ai-spam-shield-tab-link" data-tab="tab-detection"><?php esc_html_e('Spam Detection', 'ai-spam-shield'); ?></a></li>
            <li><a href="#" class="ai-spam-shield-tab-link" data-tab="tab-advanced"><?php esc_html_e('Advanced', 'ai-spam-shield'); ?></a></li>
            <li><a href="#" class="ai-spam-shield-tab-link" data-tab="tab-test"><?php esc_html_e('Test Tool', 'ai-spam-shield'); ?></a></li>
        </ul>
    </div>
    
    <form method="post" action="options.php">
        <?php settings_fields('ai_spam_shield_settings'); ?>
        <?php wp_nonce_field('ai_spam_shield_nonce', 'ai_spam_shield_nonce'); ?>
        
        <div id="tab-general" class="ai-spam-shield-tab-content">
            <h2><?php esc_html_e('General Settings', 'ai-spam-shield'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Enable Spam Detection', 'ai-spam-shield'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="ai_spam_shield_settings[active]" value="1" <?php checked($active); ?> />
                            <?php esc_html_e('Enable AI spam detection', 'ai-spam-shield'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('When enabled, form submissions will be checked for spam using AI.', 'ai-spam-shield'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e('Notification Email', 'ai-spam-shield'); ?></th>
                    <td>
                        <input type="email" class="regular-text" name="ai_spam_shield_settings[notification_email]" value="<?php echo esc_attr($notification_email); ?>" />
                        <p class="description"><?php esc_html_e('Email address to notify when spam is detected (optional).', 'ai-spam-shield'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e('Rejection Message', 'ai-spam-shield'); ?></th>
                    <td>
                        <textarea class="large-text" name="ai_spam_shield_settings[custom_message]" rows="3"><?php echo esc_textarea($custom_message); ?></textarea>
                        <p class="description"><?php esc_html_e('Message shown to users when their submission is rejected as spam.', 'ai-spam-shield'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div id="tab-providers" class="ai-spam-shield-tab-content">
            <h2><?php esc_html_e('AI Provider Settings', 'ai-spam-shield'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('AI Provider', 'ai-spam-shield'); ?></th>
                    <td>
                        <select name="ai_spam_shield_settings[api_provider]" id="ai_spam_shield_api_provider">
                            <option value="openai" <?php selected($api_provider, 'openai'); ?>><?php esc_html_e('OpenAI', 'ai-spam-shield'); ?></option>
                            <option value="anthropic" <?php selected($api_provider, 'anthropic'); ?>><?php esc_html_e('Anthropic (Claude)', 'ai-spam-shield'); ?></option>
                            <option value="gemini" <?php selected($api_provider, 'gemini'); ?>><?php esc_html_e('Google Gemini', 'ai-spam-shield'); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e('Select which AI provider to use for spam detection.', 'ai-spam-shield'); ?></p>
                    </td>
                </tr>
                
                <!-- OpenAI Settings -->
                <tr class="openai-settings" <?php echo $api_provider !== 'openai' ? 'style="display:none;"' : ''; ?>>
                    <th scope="row"><?php esc_html_e('OpenAI API Key', 'ai-spam-shield'); ?></th>
                    <td>
                        <input type="password" class="regular-text" id="ai_spam_shield_openai_api_key" name="ai_spam_shield_settings[openai_api_key]" value="<?php echo esc_attr($openai_api_key); ?>" />
                        <button type="button" id="ai_spam_shield_test_api" class="button button-secondary"><?php esc_html_e('Test Connection', 'ai-spam-shield'); ?></button>
                        <p class="description"><?php esc_html_e('Enter your OpenAI API key', 'ai-spam-shield'); ?></p>
                        <p class="description"><a href="https://platform.openai.com/account/api-keys" target="_blank"><?php esc_html_e('Get an OpenAI API key', 'ai-spam-shield'); ?></a></p>
                    </td>
                </tr>
                
                <tr class="openai-settings" <?php echo $api_provider !== 'openai' ? 'style="display:none;"' : ''; ?>>
                    <th scope="row"><?php esc_html_e('OpenAI Model', 'ai-spam-shield'); ?></th>
                    <td>
                        <select name="ai_spam_shield_settings[ai_model]" id="ai_spam_shield_openai_model">
                            <option value="gpt-4o" <?php selected($ai_model, 'gpt-4o'); ?>><?php esc_html_e('GPT-4o', 'ai-spam-shield'); ?></option>
                            <option value="gpt-4" <?php selected($ai_model, 'gpt-4'); ?>><?php esc_html_e('GPT-4', 'ai-spam-shield'); ?></option>
                            <option value="gpt-3.5-turbo" <?php selected($ai_model, 'gpt-3.5-turbo'); ?>><?php esc_html_e('GPT-3.5 Turbo', 'ai-spam-shield'); ?></option>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Select which OpenAI model to use. GPT-4o provides the best accuracy.', 'ai-spam-shield'); ?>
                            <span class="ai-spam-shield-tooltip dashicons dashicons-info" data-tooltip="<?php esc_attr_e('GPT-4o is recommended for best accuracy. GPT-3.5 Turbo is faster but may be less accurate.', 'ai-spam-shield'); ?>"></span>
                        </p>
                    </td>
                </tr>
                
                <!-- Anthropic Settings -->
                <tr class="anthropic-settings" <?php echo $api_provider !== 'anthropic' ? 'style="display:none;"' : ''; ?>>
                    <th scope="row"><?php esc_html_e('Anthropic API Key', 'ai-spam-shield'); ?></th>
                    <td>
                        <input type="password" class="regular-text" id="ai_spam_shield_anthropic_api_key" name="ai_spam_shield_settings[anthropic_api_key]" value="<?php echo esc_attr($anthropic_api_key); ?>" />
                        <button type="button" id="ai_spam_shield_test_api" class="button button-secondary"><?php esc_html_e('Test Connection', 'ai-spam-shield'); ?></button>
                        <p class="description"><?php esc_html_e('Enter your Anthropic API key', 'ai-spam-shield'); ?></p>
                        <p class="description"><a href="https://console.anthropic.com/" target="_blank"><?php esc_html_e('Get an Anthropic API key', 'ai-spam-shield'); ?></a></p>
                    </td>
                </tr>
                
                <tr class="anthropic-settings" <?php echo $api_provider !== 'anthropic' ? 'style="display:none;"' : ''; ?>>
                    <th scope="row"><?php esc_html_e('Anthropic Model', 'ai-spam-shield'); ?></th>
                    <td>
                        <select name="ai_spam_shield_settings[ai_model]" id="ai_spam_shield_anthropic_model">
                            <option value="claude-3-opus-20240229" <?php selected($ai_model, 'claude-3-opus-20240229'); ?>><?php esc_html_e('Claude 3 Opus', 'ai-spam-shield'); ?></option>
                            <option value="claude-3-sonnet-20240229" <?php selected($ai_model, 'claude-3-sonnet-20240229'); ?>><?php esc_html_e('Claude 3 Sonnet', 'ai-spam-shield'); ?></option>
                            <option value="claude-3-haiku-20240307" <?php selected($ai_model, 'claude-3-haiku-20240307'); ?>><?php esc_html_e('Claude 3 Haiku', 'ai-spam-shield'); ?></option>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Select which Anthropic model to use. Opus provides the best accuracy.', 'ai-spam-shield'); ?>
                            <span class="ai-spam-shield-tooltip dashicons dashicons-info" data-tooltip="<?php esc_attr_e('Claude 3 Opus is recommended for best accuracy. Haiku is faster but may be less accurate.', 'ai-spam-shield'); ?>"></span>
                        </p>
                    </td>
                </tr>
                
                <!-- Gemini Settings -->
                <tr class="gemini-settings" <?php echo $api_provider !== 'gemini' ? 'style="display:none;"' : ''; ?>>
                    <th scope="row"><?php esc_html_e('Google Gemini API Key', 'ai-spam-shield'); ?></th>
                    <td>
                        <input type="password" class="regular-text" id="ai_spam_shield_gemini_api_key" name="ai_spam_shield_settings[gemini_api_key]" value="<?php echo esc_attr($gemini_api_key); ?>" />
                        <button type="button" id="ai_spam_shield_test_api" class="button button-secondary"><?php esc_html_e('Test Connection', 'ai-spam-shield'); ?></button>
                        <p class="description"><?php esc_html_e('Enter your Google Gemini API key', 'ai-spam-shield'); ?></p>
                        <p class="description"><a href="https://ai.google.dev/" target="_blank"><?php esc_html_e('Get a Gemini API key', 'ai-spam-shield'); ?></a></p>
                    </td>
                </tr>
                
                <tr class="gemini-settings" <?php echo $api_provider !== 'gemini' ? 'style="display:none;"' : ''; ?>>
                    <th scope="row"><?php esc_html_e('Gemini Model', 'ai-spam-shield'); ?></th>
                    <td>
                        <select name="ai_spam_shield_settings[ai_model]" id="ai_spam_shield_gemini_model">
                            <option value="gemini-1.5-pro" <?php selected($ai_model, 'gemini-1.5-pro'); ?>><?php esc_html_e('Gemini 1.5 Pro', 'ai-spam-shield'); ?></option>
                            <option value="gemini-1.5-flash" <?php selected($ai_model, 'gemini-1.5-flash'); ?>><?php esc_html_e('Gemini 1.5 Flash', 'ai-spam-shield'); ?></option>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Select which Gemini model to use. Pro provides the best accuracy.', 'ai-spam-shield'); ?>
                            <span class="ai-spam-shield-tooltip dashicons dashicons-info" data-tooltip="<?php esc_attr_e('Gemini 1.5 Pro is recommended for best accuracy. Flash is faster but may be less accurate.', 'ai-spam-shield'); ?>"></span>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div id="tab-integrations" class="ai-spam-shield-tab-content">
            <h2><?php esc_html_e('Form Integrations', 'ai-spam-shield'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Contact Form 7', 'ai-spam-shield'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" class="form-integration-toggle" name="ai_spam_shield_settings[check_contact_form_7]" value="1" <?php checked($check_cf7); ?> />
                            <?php esc_html_e('Check Contact Form 7 submissions', 'ai-spam-shield'); ?>
                        </label>
                        <p class="description">
                            <?php 
                            if (is_plugin_active('contact-form-7/wp-contact-form-7.php')) {
                                esc_html_e('Contact Form 7 is active.', 'ai-spam-shield');
                            } else {
                                esc_html_e('Contact Form 7 is not active or installed.', 'ai-spam-shield');
                            }
                            ?>
                        </p>
                    </td>
                </tr>
                
                <tr class="form-specific-settings cf7-settings" <?php echo !$check_cf7 ? 'style="display:none;"' : ''; ?>>
                    <th scope="row"><?php esc_html_e('CF7 Settings', 'ai-spam-shield'); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><?php esc_html_e('Contact Form 7 Settings', 'ai-spam-shield'); ?></legend>
                            <label>
                                <input type="checkbox" name="ai_spam_shield_settings[cf7_check_all_forms]" value="1" <?php checked(isset($settings['cf7_check_all_forms']) ? $settings['cf7_check_all_forms'] : true); ?> />
                                <?php esc_html_e('Check all Contact Form 7 forms', 'ai-spam-shield'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e('WPForms', 'ai-spam-shield'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" class="form-integration-toggle" name="ai_spam_shield_settings[check_wpforms]" value="1" <?php checked($check_wpforms); ?> />
                            <?php esc_html_e('Check WPForms submissions', 'ai-spam-shield'); ?>
                        </label>
                        <p class="description">
                            <?php 
                            if (is_plugin_active('wpforms-lite/wpforms.php') || is_plugin_active('wpforms/wpforms.php')) {
                                esc_html_e('WPForms is active.', 'ai-spam-shield');
                            } else {
                                esc_html_e('WPForms is not active or installed.', 'ai-spam-shield');
                            }
                            ?>
                        </p>
                    </td>
                </tr>
                
                <tr class="form-specific-settings wpforms-settings" <?php echo !$check_wpforms ? 'style="display:none;"' : ''; ?>>
                    <th scope="row"><?php esc_html_e('WPForms Settings', 'ai-spam-shield'); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><?php esc_html_e('WPForms Settings', 'ai-spam-shield'); ?></legend>
                            <label>
                                <input type="checkbox" name="ai_spam_shield_settings[wpforms_check_all_forms]" value="1" <?php checked(isset($settings['wpforms_check_all_forms']) ? $settings['wpforms_check_all_forms'] : true); ?> />
                                <?php esc_html_e('Check all WPForms', 'ai-spam-shield'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e('Gravity Forms', 'ai-spam-shield'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" class="form-integration-toggle" name="ai_spam_shield_settings[check_gravity_forms]" value="1" <?php checked($check_gravity_forms); ?> />
                            <?php esc_html_e('Check Gravity Forms submissions', 'ai-spam-shield'); ?>
                        </label>
                        <p class="description">
                            <?php 
                            if (class_exists('GFForms')) {
                                esc_html_e('Gravity Forms is active.', 'ai-spam-shield');
                            } else {
                                esc_html_e('Gravity Forms is not active or installed.', 'ai-spam-shield');
                            }
                            ?>
                        </p>
                    </td>
                </tr>
                
                <tr class="form-specific-settings gravity-forms-settings" <?php echo !$check_gravity_forms ? 'style="display:none;"' : ''; ?>>
                    <th scope="row"><?php esc_html_e('Gravity Forms Settings', 'ai-spam-shield'); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><?php esc_html_e('Gravity Forms Settings', 'ai-spam-shield'); ?></legend>
                            <label>
                                <input type="checkbox" name="ai_spam_shield_settings[gravity_forms_check_all_forms]" value="1" <?php checked(isset($settings['gravity_forms_check_all_forms']) ? $settings['gravity_forms_check_all_forms'] : true); ?> />
                                <?php esc_html_e('Check all Gravity Forms', 'ai-spam-shield'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e('WordPress Comments', 'ai-spam-shield'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" class="form-integration-toggle" name="ai_spam_shield_settings[check_comments]" value="1" <?php checked($check_comments); ?> />
                            <?php esc_html_e('Check WordPress comments', 'ai-spam-shield'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('When enabled, comments will be checked for spam using AI.', 'ai-spam-shield'); ?></p>
                    </td>
                </tr>
                
                <tr class="form-specific-settings comments-settings" <?php echo !$check_comments ? 'style="display:none;"' : ''; ?>>
                    <th scope="row"><?php esc_html_e('Comments Settings', 'ai-spam-shield'); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><?php esc_html_e('Comments Settings', 'ai-spam-shield'); ?></legend>
                            <label>
                                <input type="checkbox" name="ai_spam_shield_settings[skip_logged_in_users]" value="1" <?php checked(isset($settings['skip_logged_in_users']) ? $settings['skip_logged_in_users'] : true); ?> />
                                <?php esc_html_e('Skip spam check for logged-in users', 'ai-spam-shield'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
            </table>
        </div>
        
        <div id="tab-detection" class="ai-spam-shield-tab-content">
            <h2><?php esc_html_e('Spam Detection Settings', 'ai-spam-shield'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Spam Types to Detect', 'ai-spam-shield'); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><?php esc_html_e('Spam Types to Detect', 'ai-spam-shield'); ?></legend>
                            
                            <label>
                                <input type="checkbox" name="ai_spam_shield_settings[detect_phishing]" value="1" <?php checked($detect_phishing); ?> />
                                <?php esc_html_e('Detect phishing attempts', 'ai-spam-shield'); ?>
                                <span class="ai-spam-shield-tooltip dashicons dashicons-info" data-tooltip="<?php esc_attr_e('Attempts to trick users into revealing personal or financial information.', 'ai-spam-shield'); ?>"></span>
                            </label><br>
                            
                            <label>
                                <input type="checkbox" name="ai_spam_shield_settings[detect_sales_pitch]" value="1" <?php checked($detect_sales_pitch); ?> />
                                <?php esc_html_e('Detect sales pitches', 'ai-spam-shield'); ?>
                                <span class="ai-spam-shield-tooltip dashicons dashicons-info" data-tooltip="<?php esc_attr_e('Unsolicited offers for products or services.', 'ai-spam-shield'); ?>"></span>
                            </label><br>
                            
                            <label>
                                <input type="checkbox" name="ai_spam_shield_settings[detect_promotional]" value="1" <?php checked($detect_promotional); ?> />
                                <?php esc_html_e('Detect promotional content', 'ai-spam-shield'); ?>
                                <span class="ai-spam-shield-tooltip dashicons dashicons-info" data-tooltip="<?php esc_attr_e('Generic marketing or promotional messages.', 'ai-spam-shield'); ?>"></span>
                            </label><br>
                            
                            <label>
                                <input type="checkbox" name="ai_spam_shield_settings[detect_collaboration]" value="1" <?php checked($detect_collaboration); ?> />
                                <?php esc_html_e('Detect unsolicited collaboration requests', 'ai-spam-shield'); ?>
                                <span class="ai-spam-shield-tooltip dashicons dashicons-info" data-tooltip="<?php esc_attr_e('Generic proposals for partnerships, guest posts, or link exchanges.', 'ai-spam-shield'); ?>"></span>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e('Confidence Threshold', 'ai-spam-shield'); ?></th>
                    <td>
                        <input type="range" name="ai_spam_shield_settings[confidence_threshold]" min="0.1" max="0.9" step="0.1" value="<?php echo esc_attr($confidence_threshold); ?>" class="ai-spam-shield-slider" id="confidence_threshold_slider" />
                        <span id="confidence_threshold_value"><?php echo esc_html(number_format($confidence_threshold * 100, 0)); ?>%</span>
                        
                        <p class="description">
                            <?php esc_html_e('Minimum confidence level to mark a message as spam. Higher values reduce false positives but may let some spam through.', 'ai-spam-shield'); ?>
                            <span class="ai-spam-shield-tooltip dashicons dashicons-info" data-tooltip="<?php esc_attr_e('70% is recommended for most sites. For stricter filtering, use 60%. For fewer false positives, use 80%.', 'ai-spam-shield'); ?>"></span>
                        </p>
                        
                        <script>
                            jQuery(document).ready(function($) {
                                $('#confidence_threshold_slider').on('input', function() {
                                    $('#confidence_threshold_value').text(Math.round($(this).val() * 100) + '%');
                                });
                            });
                        </script>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e('Custom Spam Types', 'ai-spam-shield'); ?></th>
                    <td>
                        <textarea class="large-text code" name="ai_spam_shield_settings[custom_spam_types]" rows="4"><?php echo esc_textarea(isset($settings['custom_spam_types']) ? $settings['custom_spam_types'] : ''); ?></textarea>
                        <p class="description"><?php esc_html_e('Add custom spam types to detect, one per line. Example: "cryptocurrency promotion", "fake loan offers".', 'ai-spam-shield'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div id="tab-advanced" class="ai-spam-shield-tab-content">
            <h2><?php esc_html_e('Advanced Settings', 'ai-spam-shield'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Logging', 'ai-spam-shield'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="ai_spam_shield_settings[log_detections]" value="1" <?php checked($log_detections); ?> />
                            <?php esc_html_e('Log spam detections', 'ai-spam-shield'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('When enabled, spam detections will be logged for review.', 'ai-spam-shield'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e('Log Retention', 'ai-spam-shield'); ?></th>
                    <td>
                        <select name="ai_spam_shield_settings[log_retention]">
                            <option value="7" <?php selected(isset($settings['log_retention']) ? $settings['log_retention'] : 30, 7); ?>><?php esc_html_e('7 days', 'ai-spam-shield'); ?></option>
                            <option value="14" <?php selected(isset($settings['log_retention']) ? $settings['log_retention'] : 30, 14); ?>><?php esc_html_e('14 days', 'ai-spam-shield'); ?></option>
                            <option value="30" <?php selected(isset($settings['log_retention']) ? $settings['log_retention'] : 30, 30); ?>><?php esc_html_e('30 days', 'ai-spam-shield'); ?></option>
                            <option value="60" <?php selected(isset($settings['log_retention']) ? $settings['log_retention'] : 30, 60); ?>><?php esc_html_e('60 days', 'ai-spam-shield'); ?></option>
                            <option value="90" <?php selected(isset($settings['log_retention']) ? $settings['log_retention'] : 30, 90); ?>><?php esc_html_e('90 days', 'ai-spam-shield'); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e('How long to keep spam detection logs.', 'ai-spam-shield'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e('API Timeout', 'ai-spam-shield'); ?></th>
                    <td>
                        <input type="number" class="small-text" name="ai_spam_shield_settings[api_timeout]" value="<?php echo esc_attr(isset($settings['api_timeout']) ? $settings['api_timeout'] : 15); ?>" min="5" max="60" step="1" /> <?php esc_html_e('seconds', 'ai-spam-shield'); ?>
                        <p class="description"><?php esc_html_e('Maximum time to wait for AI service response before falling back.', 'ai-spam-shield'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e('Cache Results', 'ai-spam-shield'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="ai_spam_shield_settings[cache_results]" value="1" <?php checked(isset($settings['cache_results']) ? $settings['cache_results'] : false); ?> />
                            <?php esc_html_e('Cache spam detection results', 'ai-spam-shield'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('When enabled, identical messages will use cached results to reduce API calls.', 'ai-spam-shield'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e('Cache Duration', 'ai-spam-shield'); ?></th>
                    <td>
                        <input type="number" class="small-text" name="ai_spam_shield_settings[cache_duration]" value="<?php echo esc_attr(isset($settings['cache_duration']) ? $settings['cache_duration'] : 24); ?>" min="1" max="168" step="1" /> <?php esc_html_e('hours', 'ai-spam-shield'); ?>
                        <p class="description"><?php esc_html_e('How long to keep cached results.', 'ai-spam-shield'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e('Custom Prompt', 'ai-spam-shield'); ?></th>
                    <td>
                        <textarea class="large-text code" name="ai_spam_shield_settings[custom_prompt]" rows="6"><?php echo esc_textarea(isset($settings['custom_prompt']) ? $settings['custom_prompt'] : ''); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Advanced: Customize the prompt sent to the AI model. Leave blank to use the default.', 'ai-spam-shield'); ?>
                            <span class="ai-spam-shield-tooltip dashicons dashicons-info" data-tooltip="<?php esc_attr_e('Use {message} as a placeholder for the form submission content and {spam_types} for the spam types to detect.', 'ai-spam-shield'); ?>"></span>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e('Debug Mode', 'ai-spam-shield'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="ai_spam_shield_settings[debug_mode]" value="1" <?php checked(isset($settings['debug_mode']) ? $settings['debug_mode'] : false); ?> />
                            <?php esc_html_e('Enable debug mode', 'ai-spam-shield'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('When enabled, detailed debug information will be logged for troubleshooting.', 'ai-spam-shield'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div id="tab-test" class="ai-spam-shield-tab-content">
            <h2><?php esc_html_e('Test Spam Detection', 'ai-spam-shield'); ?></h2>
            
            <p><?php esc_html_e('Test how the AI will classify a message. This helps you fine-tune your settings.', 'ai-spam-shield'); ?></p>
            
            <div class="ai-spam-shield-test-container">
                <form id="ai_spam_shield_test_message_form" action="javascript:void(0);">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e('Test Message', 'ai-spam-shield'); ?></th>
                            <td>
                                <textarea id="ai_spam_shield_test_message" class="large-text" rows="8" placeholder="<?php esc_attr_e('Enter a message to test for spam...', 'ai-spam-shield'); ?>"></textarea>
                            </td>
                        </tr>
                    </table>
                    
                    <p>
                        <input type="submit" id="ai_spam_shield_test_message_submit" class="button button-primary" value="<?php esc_attr_e('Test Message', 'ai-spam-shield'); ?>" />
                    </p>
                </form>
                
                <div id="ai_spam_shield_test_result_container" style="display: none;" class="ai-spam-shield-result-box">
                    <h3><?php esc_html_e('Test Results', 'ai-spam-shield'); ?></h3>
                    
                    <table class="widefat">
                        <tr>
                            <th><?php esc_html_e('Is Spam?', 'ai-spam-shield'); ?></th>
                            <td id="ai_spam_shield_test_result_is_spam">-</td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Confidence', 'ai-spam-shield'); ?></th>
                            <td id="ai_spam_shield_test_result_confidence">-</td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Reason', 'ai-spam-shield'); ?></th>
                            <td id="ai_spam_shield_test_result_reason">-</td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="ai-spam-shield-test-examples">
                <h3><?php esc_html_e('Sample Test Messages', 'ai-spam-shield'); ?></h3>
                
                <p><?php esc_html_e('Click a sample to load it into the test box:', 'ai-spam-shield'); ?></p>
                
                <button class="button ai-spam-shield-sample-message" data-message="Hi, I found your website and I'm interested in your services. I'd like to get a quote for a project I'm working on. Can you tell me more about your pricing and turnaround times? Thanks!"><?php esc_html_e('Legitimate Inquiry', 'ai-spam-shield'); ?></button>
                
                <button class="button ai-spam-shield-sample-message" data-message="Dear Sir, I came across your website and I must say it is very impressive. I want to offer you a special opportunity to increase your website's ranking with our premium SEO services. We guarantee first page results or your money back. Contact us today for a free analysis."><?php esc_html_e('SEO Spam', 'ai-spam-shield'); ?></button>
                
                <button class="button ai-spam-shield-sample-message" data-message="Hello, I am a professional writer and I would love to contribute a guest post on your website. In return, I would include a link to my client's website which is in a related industry. Let me know if you're interested in this mutually beneficial arrangement."><?php esc_html_e('Link Exchange', 'ai-spam-shield'); ?></button>
                
                <button class="button ai-spam-shield-sample-message" data-message="URGENT: Your account has been compromised. Please verify your information by clicking on this link and entering your password: http://suspicious-link.example.com"><?php esc_html_e('Phishing Attempt', 'ai-spam-shield'); ?></button>
                
                <script>
                    jQuery(document).ready(function($) {
                        $('.ai-spam-shield-sample-message').on('click', function() {
                            $('#ai_spam_shield_test_message').val($(this).data('message'));
                        });
                    });
                </script>
            </div>
        </div>
        
        <?php submit_button(); ?>
    </form>
    
    <div class="ai-spam-shield-footer">
        <p><?php printf(esc_html__('AI Spam Shield v%s - Using AI to protect your forms from spam.', 'ai-spam-shield'), AI_SPAM_SHIELD_VERSION); ?></p>
    </div>
</div>

<style>
/* Admin Styles */
.ai-spam-shield-admin {
    max-width: 1200px;
}

.ai-spam-shield-tabs-wrapper {
    margin-bottom: 20px;
    border-bottom: 1px solid #ccc;
}

.ai-spam-shield-tabs {
    display: flex;
    margin: 0;
    padding: 0;
    list-style: none;
}

.ai-spam-shield-tabs li {
    margin: 0;
}

.ai-spam-shield-tab-link {
    display: block;
    padding: 10px 15px;
    background: #f7f7f7;
    border: 1px solid #ccc;
    border-bottom: none;
    text-decoration: none;
    color: #555;
    font-weight: 600;
    margin-right: 5px;
    border-radius: 3px 3px 0 0;
}

.ai-spam-shield-tab-link.active {
    background: #fff;
    border-bottom-color: #fff;
    color: #0073aa;
}

.ai-spam-shield-tab-content {
    display: none;
    padding: 20px 0;
}

.ai-spam-shield-tab-content h2 {
    margin-top: 0;
}

.ai-spam-shield-footer {
    margin-top: 30px;
    padding-top: 15px;
    border-top: 1px solid #ddd;
    color: #666;
}

.ai-spam-shield-test-container {
    background: #fff;
    border: 1px solid #e5e5e5;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 3px;
}

.ai-spam-shield-result-box {
    background: #f8f8f8;
    border: 1px solid #e5e5e5;
    margin-top: 20px;
    padding: 15px;
    border-radius: 3px;
}

.ai-spam-shield-sample-message {
    margin: 0 10px 10px 0 !important;
}

.ai-spam-shield-tooltip {
    cursor: help;
    color: #0073aa;
    vertical-align: middle;
}

.ai-spam-shield-tooltip-popup {
    position: absolute;
    background: #333;
    color: #fff;
    padding: 10px;
    border-radius: 3px;
    font-size: 12px;
    max-width: 300px;
    z-index: 100;
    box-shadow: 0 2px 5px rgba(0,0,0,.2);
}

.ai-spam-shield-tooltip-popup:after {
    content: '';
    position: absolute;
    left: -5px;
    top: 10px;
    width: 0;
    height: 0;
    border-style: solid;
    border-width: 5px 5px 5px 0;
    border-color: transparent #333 transparent transparent;
}

.ai-spam-shield-slider {
    width: 200px;
    vertical-align: middle;
}

#confidence_threshold_value {
    display: inline-block;
    width: 40px;
    text-align: center;
    font-weight: bold;
    margin-left: 10px;
}

.ai-spam-shield-test-examples {
    margin-top: 20px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Additional JS that's not in admin.js can go here
    // This ensures that inline script works correctly with the HTML on the page
    
    // Example: Initialize any page-specific elements
    $('.ai-spam-shield-tabs').show();
});
</script>