/**
 * AI Spam Shield - Admin JavaScript
 * 
 * Handles all admin UI interactions for the plugin settings page.
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Handle API provider switching
        const $providerSelect = $('#ai_spam_shield_api_provider');
        const $openaiSettings = $('.openai-settings');
        const $anthropicSettings = $('.anthropic-settings');
        const $geminiSettings = $('.gemini-settings');
        
        // Initial state
        updateProviderFields($providerSelect.val());
        
        // On change
        $providerSelect.on('change', function() {
            updateProviderFields($(this).val());
        });
        
        /**
         * Show/hide fields based on selected provider
         */
        function updateProviderFields(provider) {
            if (provider === 'openai') {
                $openaiSettings.show();
                $anthropicSettings.hide();
                $geminiSettings.hide();
            } else if (provider === 'anthropic') {
                $openaiSettings.hide();
                $anthropicSettings.show();
                $geminiSettings.hide();
            } else if (provider === 'gemini') {
                $openaiSettings.hide();
                $anthropicSettings.hide();
                $geminiSettings.show();
            }
        }
        
        // Test API connection
        $('#ai_spam_shield_test_api').on('click', function(e) {
            e.preventDefault();
            
            const provider = $providerSelect.val();
            let apiKey = '';
            
            if (provider === 'openai') {
                apiKey = $('#ai_spam_shield_openai_api_key').val();
            } else if (provider === 'anthropic') {
                apiKey = $('#ai_spam_shield_anthropic_api_key').val();
            } else if (provider === 'gemini') {
                apiKey = $('#ai_spam_shield_gemini_api_key').val();
            }
            
            if (!apiKey) {
                alert('Please enter an API key first.');
                return;
            }
            
            $(this).prop('disabled', true).text('Testing...');
            
            // AJAX call to test API connection
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ai_spam_shield_test_api',
                    provider: provider,
                    api_key: apiKey,
                    nonce: $('#ai_spam_shield_nonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        alert('API connection successful!');
                    } else {
                        alert('API connection failed: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('Error testing API connection. Please try again.');
                },
                complete: function() {
                    $('#ai_spam_shield_test_api').prop('disabled', false).text('Test Connection');
                }
            });
        });
        
        // Toggle form integrations
        $('.form-integration-toggle').on('change', function() {
            const $relatedSettings = $(this).closest('tr').next('.form-specific-settings');
            if ($(this).is(':checked')) {
                $relatedSettings.show();
            } else {
                $relatedSettings.hide();
            }
        });
        
        // Initialize form integration toggles
        $('.form-integration-toggle').each(function() {
            if ($(this).is(':checked')) {
                $(this).closest('tr').next('.form-specific-settings').show();
            } else {
                $(this).closest('tr').next('.form-specific-settings').hide();
            }
        });
        
        // Handle test message submission
        $('#ai_spam_shield_test_message_form').on('submit', function(e) {
            e.preventDefault();
            
            const testMessage = $('#ai_spam_shield_test_message').val();
            if (!testMessage) {
                alert('Please enter a test message.');
                return;
            }
            
            const $submitButton = $('#ai_spam_shield_test_message_submit');
            $submitButton.prop('disabled', true).val('Testing...');
            
            // AJAX call to test message
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ai_spam_shield_test_message',
                    message: testMessage,
                    nonce: $('#ai_spam_shield_nonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        const result = response.data;
                        $('#ai_spam_shield_test_result_container').show();
                        $('#ai_spam_shield_test_result_is_spam').text(result.is_spam ? 'Yes' : 'No');
                        $('#ai_spam_shield_test_result_confidence').text((result.confidence * 100).toFixed(2) + '%');
                        $('#ai_spam_shield_test_result_reason').text(result.reason || 'N/A');
                    } else {
                        alert('Error testing message: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('Error testing message. Please try again.');
                },
                complete: function() {
                    $submitButton.prop('disabled', false).val('Test Message');
                }
            });
        });
        
        // Toggle advanced settings
        $('#ai_spam_shield_advanced_toggle').on('click', function(e) {
            e.preventDefault();
            $('.advanced-settings-section').toggle();
            $(this).text(function(i, text) {
                return text === 'Show Advanced Settings' ? 'Hide Advanced Settings' : 'Show Advanced Settings';
            });
        });
        
        // Initialize tooltips
        $('.ai-spam-shield-tooltip').on('mouseenter', function() {
            const $tooltip = $(this);
            const tooltipText = $tooltip.data('tooltip');
            
            if (tooltipText) {
                $('<div class="ai-spam-shield-tooltip-popup">' + tooltipText + '</div>')
                    .appendTo('body')
                    .css({
                        top: $tooltip.offset().top - 10,
                        left: $tooltip.offset().left + $tooltip.width() + 10
                    })
                    .fadeIn('fast');
            }
        }).on('mouseleave', function() {
            $('.ai-spam-shield-tooltip-popup').remove();
        });
        
        // Initialize tabs if they exist
        const $tabs = $('.ai-spam-shield-tabs');
        if ($tabs.length) {
            const $tabLinks = $tabs.find('.ai-spam-shield-tab-link');
            const $tabContents = $('.ai-spam-shield-tab-content');
            
            $tabLinks.on('click', function(e) {
                e.preventDefault();
                
                const tabId = $(this).data('tab');
                
                // Update active tab
                $tabLinks.removeClass('active');
                $(this).addClass('active');
                
                // Show selected tab content
                $tabContents.hide();
                $('#' + tabId).show();
                
                // Save active tab to localStorage
                if (window.localStorage) {
                    localStorage.setItem('ai_spam_shield_active_tab', tabId);
                }
            });
            
            // Check for saved tab
            if (window.localStorage) {
                const activeTab = localStorage.getItem('ai_spam_shield_active_tab');
                if (activeTab) {
                    $tabLinks.filter('[data-tab="' + activeTab + '"]').click();
                } else {
                    // Default to first tab
                    $tabLinks.first().click();
                }
            } else {
                // Default to first tab if localStorage not available
                $tabLinks.first().click();
            }
        }
    });
})(jQuery);