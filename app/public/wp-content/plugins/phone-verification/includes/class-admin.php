<?php

if (!defined('ABSPATH')) {
    exit;
}

class Phone_Verification_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_pages'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_post_save_phone_verification_settings', array($this, 'save_settings'));
    }

    public function add_admin_pages() {
        // This is handled in the main plugin class now
    }

    public function register_settings() {
        // Register settings for the settings page
        register_setting('phone_verification_settings', 'phone_verification_api_key');
        register_setting('phone_verification_settings', 'phone_verification_api_secret');
        register_setting('phone_verification_settings', 'phone_verification_api_url');
        register_setting('phone_verification_settings', 'phone_verification_cache_duration');
        register_setting('phone_verification_settings', 'phone_verification_enable_cache');
    }

    public function save_settings() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['_wpnonce'], 'phone_verification_settings_nonce')) {
            wp_die('Security check failed');
        }

        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        // Save settings
        update_option('phone_verification_api_key', sanitize_text_field($_POST['api_key']));
        update_option('phone_verification_api_secret', sanitize_text_field($_POST['api_secret']));
        update_option('phone_verification_api_url', esc_url_raw($_POST['api_url']));
        update_option('phone_verification_cache_duration', intval($_POST['cache_duration']));
        update_option('phone_verification_enable_cache', isset($_POST['enable_cache']) ? 1 : 0);

        // Redirect back to settings page with success message
        wp_redirect(admin_url('admin.php?page=phone-verification-settings&updated=1'));
        exit;
    }

    public static function get_verification_stats() {
        return Phone_Verification_Verification::get_statistics();
    }

    public static function get_cached_stats() {
        $stats = array();

        // Get transient stats
        $stats['total_verifications'] = get_transient('network_verification_stats:total') ?: 0;
        $stats['successful'] = get_transient('network_verification_stats:successful') ?: 0;
        $stats['failed'] = get_transient('network_verification_stats:failed') ?: 0;
        $stats['skipped_no_coverage'] = get_transient('network_verification_stats:skipped_no_coverage') ?: 0;
        $stats['batch_total'] = get_transient('network_verification_stats:batch_total') ?: 0;
        $stats['batch_numbers'] = get_transient('network_verification_stats:batch_numbers') ?: 0;
        $stats['cached_hits'] = get_transient('network_verification_stats:cached_hits') ?: 0;
        $stats['today'] = get_transient('network_verification_stats:today:' . date('Y-m-d')) ?: 0;

        return $stats;
    }

    public static function get_network_prefixes($search = '') {
        if (empty($search)) {
            return Phone_Verification_Network_Prefix::get_all();
        } else {
            return Phone_Verification_Network_Prefix::search($search);
        }
    }

    public static function get_verifications($search = '') {
        if (empty($search)) {
            return Phone_Verification_Verification::get_live_coverage_only();
        } else {
            return Phone_Verification_Verification::search($search);
        }
    }
}