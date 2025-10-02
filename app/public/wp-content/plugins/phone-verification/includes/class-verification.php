<?php

if (!defined('ABSPATH')) {
    exit;
}

class Phone_Verification_Verification {

    public static function get_all($limit = 100, $offset = 0) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'phone_verifications';
        $prefix_table = $wpdb->prefix . 'phone_network_prefixes';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT v.*, p.country_name, p.network_name as prefix_network_name, p.live_coverage, p.min_length, p.max_length
             FROM $table_name v
             LEFT JOIN $prefix_table p ON v.prefix = p.prefix
             ORDER BY v.created_at DESC
             LIMIT %d OFFSET %d",
            $limit,
            $offset
        ));
    }

    public static function get_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'phone_verifications';

        return $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    }

    public static function get_by_number($number) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'phone_verifications';
        $prefix_table = $wpdb->prefix . 'phone_network_prefixes';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT v.*, p.country_name, p.network_name as prefix_network_name, p.live_coverage
             FROM $table_name v
             LEFT JOIN $prefix_table p ON v.prefix = p.prefix
             WHERE v.number = %s
             ORDER BY v.created_at DESC
             LIMIT 1",
            $number
        ));
    }

    public static function get_recent($days = 7) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'phone_verifications';
        $prefix_table = $wpdb->prefix . 'phone_network_prefixes';

        $start_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        return $wpdb->get_results($wpdb->prepare(
            "SELECT v.*, p.country_name, p.network_name as prefix_network_name, p.live_coverage
             FROM $table_name v
             LEFT JOIN $prefix_table p ON v.prefix = p.prefix
             WHERE v.created_at >= %s
             ORDER BY v.created_at DESC",
            $start_date
        ));
    }

    public static function get_successful() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'phone_verifications';
        $prefix_table = $wpdb->prefix . 'phone_network_prefixes';

        return $wpdb->get_results(
            "SELECT v.*, p.country_name, p.network_name as prefix_network_name, p.live_coverage
             FROM $table_name v
             LEFT JOIN $prefix_table p ON v.prefix = p.prefix
             WHERE v.status = 0
             ORDER BY v.created_at DESC"
        );
    }

    public static function get_live_coverage_only() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'phone_verifications';
        $prefix_table = $wpdb->prefix . 'phone_network_prefixes';

        return $wpdb->get_results(
            "SELECT v.*, p.country_name, p.network_name as prefix_network_name, p.live_coverage, p.min_length, p.max_length
             FROM $table_name v
             INNER JOIN $prefix_table p ON v.prefix = p.prefix
             WHERE p.live_coverage = 1
             ORDER BY v.created_at DESC"
        );
    }

    public static function search($search_term) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'phone_verifications';
        $prefix_table = $wpdb->prefix . 'phone_network_prefixes';

        $search_term = '%' . $wpdb->esc_like($search_term) . '%';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT v.*, p.country_name, p.network_name as prefix_network_name, p.live_coverage
             FROM $table_name v
             LEFT JOIN $prefix_table p ON v.prefix = p.prefix
             WHERE v.number LIKE %s
             OR v.network LIKE %s
             OR p.country_name LIKE %s
             OR p.network_name LIKE %s
             ORDER BY v.created_at DESC",
            $search_term,
            $search_term,
            $search_term,
            $search_term
        ));
    }

    public static function delete_by_number($number) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'phone_verifications';

        // Also delete from cache
        $cache_key = 'phone_verification_' . md5($number);
        delete_transient($cache_key);

        return $wpdb->delete($table_name, array('number' => $number));
    }

    public static function delete_multiple($numbers) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'phone_verifications';
        $deleted = 0;

        foreach ($numbers as $number) {
            // Delete from cache
            $cache_key = 'phone_verification_' . md5($number);
            delete_transient($cache_key);

            // Delete from database
            $result = $wpdb->delete($table_name, array('number' => $number));
            if ($result) {
                $deleted++;
            }
        }

        return $deleted;
    }

    public static function clear_all_caches() {
        global $wpdb;

        // Clear all transients that start with 'phone_verification_'
        // This is more comprehensive as it clears caches even for deleted records
        $cleared = 0;

        // First, try to get numbers from existing database records
        $table_name = $wpdb->prefix . 'phone_verifications';
        $existing_numbers = $wpdb->get_col("SELECT number FROM $table_name");

        foreach ($existing_numbers as $number) {
            $cache_key = 'phone_verification_' . md5($number);
            if (delete_transient($cache_key)) {
                $cleared++;
            }
        }

        // Additionally, clear all WordPress transients that match our pattern
        // This catches orphaned caches from deleted database records
        $transient_keys = $wpdb->get_col(
            "SELECT option_name FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_phone_verification_%'"
        );

        foreach ($transient_keys as $transient_key) {
            // Remove '_transient_' prefix to get the actual transient name
            $transient_name = str_replace('_transient_', '', $transient_key);
            if (delete_transient($transient_name)) {
                $cleared++;
            }
        }

        return $cleared;
    }

    public static function clear_cache_for_deleted_records() {
        global $wpdb;

        $cleared = 0;
        $table_name = $wpdb->prefix . 'phone_verifications';

        // Get all cached phone verification transients
        $transient_keys = $wpdb->get_col(
            "SELECT option_name FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_phone_verification_%'"
        );

        foreach ($transient_keys as $transient_key) {
            // Remove '_transient_' prefix to get the actual transient name
            $transient_name = str_replace('_transient_', '', $transient_key);

            // Get the cached data to extract the phone number
            $cached_data = get_transient($transient_name);
            if ($cached_data && isset($cached_data->number)) {
                // Check if this number still exists in database
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name WHERE number = %s",
                    $cached_data->number
                ));

                if (!$exists) {
                    // Record doesn't exist in DB but cache does - clear it
                    if (delete_transient($transient_name)) {
                        $cleared++;
                    }
                }
            } else {
                // Invalid cached data - clear it
                if (delete_transient($transient_name)) {
                    $cleared++;
                }
            }
        }

        return $cleared;
    }

    public static function get_statistics() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'phone_verifications';

        $stats = array();

        // Total verifications
        $stats['total'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

        // Successful verifications
        $stats['successful'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 0");

        // Failed verifications
        $stats['failed'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status != 0 AND status != 999");

        // No coverage verifications
        $stats['no_coverage'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 999");

        // Ported numbers
        $stats['ported'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE ported = 1");

        // Today's verifications
        $today = date('Y-m-d');
        $stats['today'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE DATE(created_at) = %s",
            $today
        ));

        // This week's verifications
        $week_start = date('Y-m-d', strtotime('monday this week'));
        $stats['this_week'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE created_at >= %s",
            $week_start
        ));

        // This month's verifications
        $month_start = date('Y-m-01');
        $stats['this_month'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE created_at >= %s",
            $month_start
        ));

        // Success rate
        $stats['success_rate'] = $stats['total'] > 0 ? round(($stats['successful'] / $stats['total']) * 100, 2) : 0;

        return $stats;
    }

    public static function get_network_distribution() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'phone_verifications';
        $prefix_table = $wpdb->prefix . 'phone_network_prefixes';

        return $wpdb->get_results(
            "SELECT COALESCE(p.network_name, v.network, 'Unknown') as network_name,
                    COALESCE(p.country_name, 'Unknown') as country_name,
                    COUNT(*) as count
             FROM $table_name v
             LEFT JOIN $prefix_table p ON v.prefix = p.prefix
             GROUP BY network_name, country_name
             ORDER BY count DESC"
        );
    }

    public static function get_country_distribution() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'phone_verifications';
        $prefix_table = $wpdb->prefix . 'phone_network_prefixes';

        return $wpdb->get_results(
            "SELECT COALESCE(p.country_name, 'Unknown') as country_name,
                    COUNT(*) as count
             FROM $table_name v
             LEFT JOIN $prefix_table p ON v.prefix = p.prefix
             GROUP BY country_name
             ORDER BY count DESC"
        );
    }
}