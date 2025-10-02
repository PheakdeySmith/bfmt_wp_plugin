<?php

if (!defined('ABSPATH')) {
    exit;
}

class Phone_Verification_Ajax_Handlers {

    private $verification_service;

    public function __construct() {
        $this->verification_service = new Phone_Verification_Service();

        // Add AJAX handlers for logged-in users (admin)
        add_action('wp_ajax_check_network_prefix', array($this, 'check_network_prefix'));
        add_action('wp_ajax_verify_phone', array($this, 'verify_phone'));
        add_action('wp_ajax_batch_verify', array($this, 'batch_verify'));
        add_action('wp_ajax_export_verifications', array($this, 'export_verifications'));
        add_action('wp_ajax_delete_verification', array($this, 'delete_verification'));
        add_action('wp_ajax_clear_verification_cache', array($this, 'clear_verification_cache'));

        // Add AJAX handlers for non-logged-in users (if you want public access)
        add_action('wp_ajax_nopriv_check_network_prefix', array($this, 'check_network_prefix'));
        add_action('wp_ajax_nopriv_verify_phone', array($this, 'verify_phone'));
        add_action('wp_ajax_nopriv_batch_verify', array($this, 'batch_verify'));
    }

    public function check_network_prefix() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'phone_verification_nonce')) {
            wp_die('Security check failed');
        }

        $phone_number = sanitize_text_field($_POST['phone_number'] ?? '');

        if (empty($phone_number)) {
            wp_send_json_error(array('message' => 'Phone number is required'));
        }

        $result = $this->verification_service->check_network_prefix($phone_number);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    public function verify_phone() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'phone_verification_nonce')) {
            wp_die('Security check failed');
        }

        $phone_number = sanitize_text_field($_POST['phone_number'] ?? '');
        $data_freshness = sanitize_text_field($_POST['data_freshness'] ?? '');

        if (empty($phone_number)) {
            wp_send_json_error(array('message' => 'Phone number is required'));
        }

        error_log('AJAX verify_phone called for: ' . $phone_number);

        $result = $this->verification_service->verify_number($phone_number, $data_freshness);

        error_log('Verification result: ' . json_encode($result));

        // If result is cached, return it directly
        if (isset($result['cached']) && $result['cached']) {
            wp_send_json_success(array(
                'success' => $result['success'],
                'data' => $result,
                'cached' => true
            ));
        }

        if ($result['success'] || $result['skip_reason'] === 'no_live_coverage') {
            // Track verification stats
            $this->increment_stat('network_verification_stats:total');

            if ($result['success']) {
                $this->increment_stat('network_verification_stats:successful');
            } else {
                $this->increment_stat('network_verification_stats:skipped_no_coverage');
            }

            $this->increment_stat('network_verification_stats:today:' . date('Y-m-d'));

            wp_send_json_success(array(
                'success' => true,
                'data' => $result
            ));
        }

        // Track failed verification
        $this->increment_stat('network_verification_stats:total');
        $this->increment_stat('network_verification_stats:failed');

        wp_send_json_error(array(
            'message' => $result['error'] ?? 'Verification failed'
        ));
    }

    public function batch_verify() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'phone_verification_nonce')) {
            wp_die('Security check failed');
        }

        // For frontend requests, don't require admin capabilities
        $is_frontend = !is_admin() || (defined('DOING_AJAX') && DOING_AJAX && !current_user_can('manage_options'));

        if (!$is_frontend && !current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $phone_numbers = $_POST['phone_numbers'] ?? array();
        $data_freshness = sanitize_text_field($_POST['data_freshness'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');

        if (empty($phone_numbers) || !is_array($phone_numbers)) {
            wp_send_json_error(array('message' => 'Phone numbers array is required'));
        }

        // For frontend requests, require email
        if ($is_frontend && empty($email)) {
            wp_send_json_error(array('message' => 'Email address is required for batch verification'));
        }

        // Sanitize phone numbers
        $phone_numbers = array_map('sanitize_text_field', $phone_numbers);

        $batch_result = $this->verification_service->verify_batch($phone_numbers, $data_freshness);
        $results = $batch_result['results'];
        $statistics = $batch_result['statistics'];

        // Separate results by type
        $saved_results = array();
        $live_coverage_results = array();
        $no_coverage_results = array();
        $error_results = array();

        foreach ($results as $result) {
            if ($result['success']) {
                $saved_results[] = $result;
                $live_coverage_results[] = $result;
            } elseif (isset($result['skip_reason']) && $result['skip_reason'] === 'no_live_coverage') {
                $saved_results[] = $result;
                $no_coverage_results[] = $result;
            } else {
                $error_results[] = $result;
            }
        }

        // Update batch verification stats
        $this->increment_stat('network_verification_stats:batch_total');
        $this->add_to_stat('network_verification_stats:batch_numbers', $statistics['total_numbers']);
        $this->add_to_stat('network_verification_stats:cached_hits', $statistics['cache_hits']);
        $this->add_to_stat('network_verification_stats:database_hits', $statistics['database_hits']);
        $this->add_to_stat('network_verification_stats:api_calls', $statistics['api_calls']);
        $this->add_to_stat('network_verification_stats:skipped_no_coverage', $statistics['skipped_no_coverage']);

        // Send email if provided (for frontend requests)
        if (!empty($email) && !empty($saved_results)) {
            $this->send_verification_results_email($email, $saved_results, $statistics);
        }

        wp_send_json_success(array(
            'success' => true,
            'processed' => $statistics['total_numbers'],
            'saved' => count($saved_results),
            'live_coverage_count' => count($live_coverage_results),
            'no_coverage_count' => count($no_coverage_results),
            'error_count' => count($error_results),
            'skipped_no_coverage' => $statistics['skipped_no_coverage'],
            'statistics' => array(
                'cache_hits' => $statistics['cache_hits'],
                'database_hits' => $statistics['database_hits'],
                'api_calls' => $statistics['api_calls'],
                'skipped_no_coverage' => $statistics['skipped_no_coverage'],
                'total_cached' => $statistics['cache_hits'] + $statistics['database_hits'],
                'live_coverage_results' => count($live_coverage_results),
                'no_coverage_results' => count($no_coverage_results),
                'error_results' => count($error_results)
            ),
            'cache_message' => $this->build_cache_message($statistics),
            'data' => $saved_results,
            'live_coverage_data' => $live_coverage_results,
            'no_coverage_data' => $no_coverage_results,
            'error_data' => $error_results
        ));
    }

    public function export_verifications() {
        // Verify nonce
        if (!wp_verify_nonce($_GET['nonce'], 'phone_verification_nonce')) {
            wp_die('Security check failed');
        }

        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'phone_verifications';

        $verifications = $wpdb->get_results("
            SELECT v.*, p.country_name, p.network_name as prefix_network_name, p.live_coverage
            FROM $table_name v
            LEFT JOIN {$wpdb->prefix}phone_network_prefixes p ON v.prefix = p.prefix
            ORDER BY v.created_at DESC
        ");

        $filename = 'phone-verification-results-' . date('Y-m-d-H-i-s') . '.csv';

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // CSV Headers
        fputcsv($output, array(
            'Number',
            'Country',
            'Min/Max Length',
            'Network',
            'MCC/MNC',
            'Live Coverage',
            'Type',
            'Status',
            'Ported',
            'Present',
            'Transaction ID',
            'Verified'
        ));

        foreach ($verifications as $verification) {
            $status_text = $this->get_status_text($verification->status);
            $live_coverage = $verification->live_coverage ? 'Yes' : 'No';

            fputcsv($output, array(
                $verification->number,
                $verification->country_name ?: 'Unknown',
                ($verification->min_length ?: 'N/A') . '/' . ($verification->max_length ?: 'N/A'),
                $verification->prefix_network_name ?: $verification->network ?: 'Unknown',
                ($verification->mcc ?: '') . '/' . ($verification->mnc ?: ''),
                $live_coverage,
                ucfirst($verification->type ?: 'unknown'),
                $status_text,
                $verification->ported ? 'Yes' : 'No',
                ucfirst($verification->present ?: 'na'),
                $verification->trxid ?: 'N/A',
                $verification->created_at
            ));
        }

        fclose($output);
        exit;
    }

    private function get_status_text($status) {
        switch ($status) {
            case 0:
                return 'Success';
            case 1:
                return 'Failed';
            case 999:
                return 'No Live Coverage';
            default:
                return 'Unknown';
        }
    }

    private function increment_stat($key) {
        $current = get_transient($key) ?: 0;
        set_transient($key, $current + 1, 30 * DAY_IN_SECONDS);
    }

    private function add_to_stat($key, $value) {
        $current = get_transient($key) ?: 0;
        set_transient($key, $current + $value, 30 * DAY_IN_SECONDS);
    }

    private function build_cache_message($statistics) {
        $total = $statistics['total_numbers'];
        $cache_hits = $statistics['cache_hits'];
        $db_hits = $statistics['database_hits'];
        $api_calls = $statistics['api_calls'];
        $skipped_no_coverage = $statistics['skipped_no_coverage'];
        $total_cached = $cache_hits + $db_hits;

        $message = "Performance: ";
        $message .= "{$total_cached} numbers found in cache ({$cache_hits} from transients, {$db_hits} from database), ";
        $message .= "{$api_calls} new API calls made, ";
        $message .= "{$skipped_no_coverage} skipped (no live coverage)";

        if ($total > 0) {
            $cache_percentage = round(($total_cached / $total) * 100, 1);
            $message .= " - {$cache_percentage}% cache hit rate";
        }

        return $message;
    }

    private function send_verification_results_email($email, $results, $statistics) {
        // Create CSV content
        $csv_content = "Phone Number,Country,Network,Status,Live Coverage,Verified Date\n";

        foreach ($results as $result) {
            $phone_number = $result['phone_number'] ?? $result['number'];
            $country = str_replace(',', ';', $result['country_name'] ?? 'Cambodia');
            $network = str_replace(',', ';', $result['network'] ?? $result['network_name'] ?? 'Unknown');

            $status = 'Failed';
            if ($result['status'] === 0) $status = 'Verified';
            else if ($result['status'] === 999) $status = 'No Coverage';

            $live_coverage = $result['live_coverage'] ?? ($result['prefix_info']['live_coverage'] ?? false);
            $live_coverage_text = $live_coverage ? 'Yes' : 'No';

            $date = date('M j, Y');

            $csv_content .= "{$phone_number},{$country},{$network},{$status},{$live_coverage_text},{$date}\n";
        }

        // Prepare email
        $subject = 'Phone Verification Results - ' . date('M j, Y');
        $message = "Hello,\n\n";
        $message .= "Your batch phone verification has completed successfully.\n\n";
        $message .= "Summary:\n";
        $message .= "- Total numbers processed: {$statistics['total_numbers']}\n";
        $message .= "- Successfully verified: " . count(array_filter($results, function($r) { return $r['status'] === 0; })) . "\n";
        $message .= "- No coverage: " . count(array_filter($results, function($r) { return $r['status'] === 999; })) . "\n";
        $message .= "- Cache hits: {$statistics['cache_hits']}\n";
        $message .= "- New API calls: {$statistics['api_calls']}\n\n";
        $message .= "Please find the detailed results in the attached CSV file.\n\n";
        $message .= "Best regards,\n";
        $message .= get_bloginfo('name');

        // Create temporary file for attachment
        $upload_dir = wp_upload_dir();
        $filename = 'phone_verification_results_' . date('Y-m-d_H-i-s') . '.csv';
        $filepath = $upload_dir['path'] . '/' . $filename;

        file_put_contents($filepath, $csv_content);

        // Send email with attachment
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $sent = wp_mail($email, $subject, nl2br($message), $headers, array($filepath));

        // Clean up temporary file
        if (file_exists($filepath)) {
            unlink($filepath);
        }

        return $sent;
    }

    public function delete_verification() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'phone_verification_nonce')) {
            wp_die('Security check failed');
        }

        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $phone_number = sanitize_text_field($_POST['phone_number'] ?? '');

        if (empty($phone_number)) {
            wp_send_json_error(array('message' => 'Phone number is required'));
        }

        $deleted = Phone_Verification_Verification::delete_by_number($phone_number);

        if ($deleted) {
            wp_send_json_success(array(
                'success' => true,
                'message' => 'Verification record and cache deleted successfully'
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'Failed to delete verification record'
            ));
        }
    }

    public function clear_verification_cache() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'phone_verification_nonce')) {
            wp_die('Security check failed');
        }

        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $action_type = sanitize_text_field($_POST['action_type'] ?? 'single');

        if ($action_type === 'all') {
            $cleared = Phone_Verification_Verification::clear_all_caches();
            wp_send_json_success(array(
                'success' => true,
                'message' => "Cleared cache for {$cleared} verification records"
            ));
        } elseif ($action_type === 'orphaned') {
            $cleared = Phone_Verification_Verification::clear_cache_for_deleted_records();
            wp_send_json_success(array(
                'success' => true,
                'message' => "Cleared {$cleared} orphaned cache entries"
            ));
        } else {
            $phone_number = sanitize_text_field($_POST['phone_number'] ?? '');

            if (empty($phone_number)) {
                wp_send_json_error(array('message' => 'Phone number is required'));
            }

            $cache_key = 'phone_verification_' . md5($phone_number);
            delete_transient($cache_key);

            wp_send_json_success(array(
                'success' => true,
                'message' => 'Cache cleared for phone number'
            ));
        }
    }
}