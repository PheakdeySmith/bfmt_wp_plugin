<?php

if (!defined('ABSPATH')) {
    exit;
}

class Phone_Verification_Network_Prefix {

    public static function get_all() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'phone_network_prefixes';

        return $wpdb->get_results("SELECT * FROM $table_name ORDER BY country_name, network_name, prefix");
    }

    public static function get_by_prefix($prefix) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'phone_network_prefixes';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE prefix = %s",
            $prefix
        ));
    }

    public static function get_live_coverage_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'phone_network_prefixes';

        return $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE live_coverage = 1");
    }

    public static function get_countries() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'phone_network_prefixes';

        return $wpdb->get_results("SELECT DISTINCT country_name FROM $table_name ORDER BY country_name");
    }

    public static function get_networks() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'phone_network_prefixes';

        return $wpdb->get_results("SELECT DISTINCT network_name, country_name FROM $table_name ORDER BY country_name, network_name");
    }

    public static function search($search_term) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'phone_network_prefixes';

        $search_term = '%' . $wpdb->esc_like($search_term) . '%';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name
             WHERE prefix LIKE %s
             OR country_name LIKE %s
             OR network_name LIKE %s
             ORDER BY prefix",
            $search_term,
            $search_term,
            $search_term
        ));
    }

    public static function update_live_coverage($prefix, $live_coverage) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'phone_network_prefixes';

        return $wpdb->update(
            $table_name,
            array('live_coverage' => $live_coverage ? 1 : 0),
            array('prefix' => $prefix)
        );
    }

    public static function add_prefix($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'phone_network_prefixes';

        return $wpdb->insert($table_name, array(
            'prefix' => sanitize_text_field($data['prefix']),
            'min_length' => intval($data['min_length']),
            'max_length' => intval($data['max_length']),
            'country_name' => sanitize_text_field($data['country_name']),
            'network_name' => sanitize_text_field($data['network_name']),
            'mcc' => sanitize_text_field($data['mcc']),
            'mnc' => sanitize_text_field($data['mnc']),
            'live_coverage' => isset($data['live_coverage']) ? 1 : 0,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ));
    }

    public static function delete_prefix($prefix) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'phone_network_prefixes';

        return $wpdb->delete($table_name, array('prefix' => $prefix));
    }
}