<?php
/**
 * Uninstall script for AI Spam Shield
 *
 * @package AI_Spam_Shield
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Define constants
define('AI_SPAM_SHIELD_DELETE_ALL_DATA', true); // Set to false to keep settings on uninstall

/**
 * Handle plugin uninstallation
 */
function ai_spam_shield_uninstall() {
    // Check if we should delete all data
    if (!AI_SPAM_SHIELD_DELETE_ALL_DATA) {
        return;
    }
    
    // Delete options
    delete_option('ai_spam_shield_settings');
    delete_option('ai_spam_shield_logs');
    delete_option('ai_spam_shield_api_logs');
    delete_option('ai_spam_shield_submission_logs');
    
    // Clean up any transients
    delete_transient('ai_spam_shield_api_status');
    
    // For multisite installations
    if (is_multisite()) {
        global $wpdb;
        
        // Get all blogs
        $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
        
        // Loop through all blogs
        foreach ($blog_ids as $blog_id) {
            switch_to_blog($blog_id);
            
            // Delete options for this blog
            delete_option('ai_spam_shield_settings');
            delete_option('ai_spam_shield_logs');
            delete_option('ai_spam_shield_api_logs');
            delete_option('ai_spam_shield_submission_logs');
            
            // Clean up any transients
            delete_transient('ai_spam_shield_api_status');
            
            restore_current_blog();
        }
    }
    
    // Delete any files/directories created by the plugin
    ai_spam_shield_delete_plugin_files();
}

/**
 * Delete plugin files
 */
function ai_spam_shield_delete_plugin_files() {
    // Get plugin directory
    $plugin_dir = trailingslashit(WP_PLUGIN_DIR) . 'ai-spam-shield';
    
    // Check if logs directory exists
    $logs_dir = $plugin_dir . '/logs';
    if (is_dir($logs_dir)) {
        // Get all files in logs directory
        $files = glob($logs_dir . '/*');
        
        // Loop through files and delete them
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        
        // Delete logs directory
        @rmdir($logs_dir);
    }
}

// Run uninstallation
ai_spam_shield_uninstall();