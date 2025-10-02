<?php

if (!defined('ABSPATH')) {
    exit;
}

class Phone_Verification_Service {

    private $api_key;
    private $api_secret;
    private $api_url;
    private $cache_duration;

    public function __construct() {
        $this->api_key = get_option('phone_verification_api_key', '66dc5490a189237be3990dd2d038b6e4f6cee8f9a214fdd373');
        $this->api_secret = get_option('phone_verification_api_secret', '8ce8cd8f5f6bd5');
        $this->api_url = get_option('phone_verification_api_url', 'https://api.tmtvelocity.com/live');
        $this->cache_duration = get_option('phone_verification_cache_duration', 3600);
    }

    /**
     * Check phone number against network_prefixes table
     */
    public function check_network_prefix($phone_number) {
        global $wpdb;

        try {
            // Clean phone number
            $clean_number = preg_replace('/[^0-9]/', '', $phone_number);

            if (empty($clean_number)) {
                return array(
                    'success' => false,
                    'error' => 'Invalid phone number format'
                );
            }

            $table_name = $wpdb->prefix . 'phone_network_prefixes';
            $max_prefix_length = 6;
            $network_info = null;

            // Try to find exact prefix match with length validation
            for ($i = $max_prefix_length; $i >= 2; $i--) {
                $prefix = substr($clean_number, 0, $i);

                $network_info = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $table_name WHERE prefix = %s AND min_length <= %d AND max_length >= %d",
                    $prefix,
                    strlen($clean_number),
                    strlen($clean_number)
                ));

                if ($network_info) {
                    break;
                }
            }

            // If no exact match, try partial match for incomplete numbers
            if (!$network_info) {
                for ($i = $max_prefix_length; $i >= 2; $i--) {
                    $prefix = substr($clean_number, 0, $i);

                    $network_info = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM $table_name WHERE prefix = %s",
                        $prefix
                    ));

                    if ($network_info) {
                        // Check if this is a partial number
                        if (strlen($clean_number) > strlen($prefix) && strlen($clean_number) < $network_info->min_length) {
                            return array(
                                'success' => true,
                                'phone_number' => $clean_number,
                                'prefix' => $network_info->prefix,
                                'country_name' => $network_info->country_name,
                                'network_name' => $network_info->network_name,
                                'mcc' => $network_info->mcc,
                                'mnc' => $network_info->mnc,
                                'live_coverage' => (bool) $network_info->live_coverage,
                                'min_length' => $network_info->min_length,
                                'max_length' => $network_info->max_length,
                                'partial_match' => true
                            );
                        }
                        break;
                    }
                }
            }

            // Handle progressive prefix matching for very short inputs
            if (!$network_info && strlen($clean_number) < 8) {
                $results = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM $table_name WHERE prefix LIKE %s ORDER BY prefix ASC",
                    $clean_number . '%'
                ));

                if (!empty($results)) {
                    $exact_match = null;
                    foreach ($results as $result) {
                        if ($result->prefix === $clean_number) {
                            $exact_match = $result;
                            break;
                        }
                    }

                    if ($exact_match) {
                        $network_info = $exact_match;
                        return array(
                            'success' => true,
                            'phone_number' => $clean_number,
                            'prefix' => $network_info->prefix,
                            'country_name' => $network_info->country_name,
                            'network_name' => $network_info->network_name,
                            'mcc' => $network_info->mcc,
                            'mnc' => $network_info->mnc,
                            'live_coverage' => (bool) $network_info->live_coverage,
                            'min_length' => $network_info->min_length,
                            'max_length' => $network_info->max_length,
                            'partial_match' => true
                        );
                    } else {
                        $first_match = $results[0];

                        if (strlen($clean_number) <= 4) {
                            return array(
                                'success' => true,
                                'phone_number' => $clean_number,
                                'prefix' => 'Multiple',
                                'country_name' => $first_match->country_name,
                                'network_name' => 'Multiple Networks Available',
                                'mcc' => $first_match->mcc,
                                'mnc' => 'XX',
                                'live_coverage' => true,
                                'min_length' => $first_match->min_length,
                                'max_length' => $first_match->max_length,
                                'partial_match' => true
                            );
                        } else {
                            return array(
                                'success' => true,
                                'phone_number' => $clean_number,
                                'prefix' => $first_match->prefix,
                                'country_name' => $first_match->country_name,
                                'network_name' => $first_match->network_name,
                                'mcc' => $first_match->mcc,
                                'mnc' => $first_match->mnc,
                                'live_coverage' => (bool) $first_match->live_coverage,
                                'min_length' => $first_match->min_length,
                                'max_length' => $first_match->max_length,
                                'partial_match' => true
                            );
                        }
                    }
                } else {
                    return array(
                        'success' => false,
                        'error' => 'No network prefix found starting with "' . $clean_number . '"',
                        'phone_number' => $clean_number
                    );
                }
            }

            if (!$network_info) {
                // Check if we can find the prefix but with wrong length
                for ($i = $max_prefix_length; $i >= 2; $i--) {
                    $prefix = substr($clean_number, 0, $i);
                    $prefix_only = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM $table_name WHERE prefix = %s",
                        $prefix
                    ));

                    if ($prefix_only) {
                        return array(
                            'success' => false,
                            'error' => "Phone number length invalid. Expected {$prefix_only->min_length}-{$prefix_only->max_length} digits, got " . strlen($clean_number),
                            'phone_number' => $clean_number,
                            'found_prefix' => $prefix,
                            'expected_length' => "{$prefix_only->min_length}-{$prefix_only->max_length}",
                            'actual_length' => strlen($clean_number)
                        );
                    }
                }

                return array(
                    'success' => false,
                    'error' => 'Phone number prefix not found in database',
                    'phone_number' => $clean_number
                );
            }

            return array(
                'success' => true,
                'phone_number' => $clean_number,
                'prefix' => $network_info->prefix,
                'country_name' => $network_info->country_name,
                'network_name' => $network_info->network_name,
                'mcc' => $network_info->mcc,
                'mnc' => $network_info->mnc,
                'live_coverage' => (bool) $network_info->live_coverage,
                'min_length' => $network_info->min_length,
                'max_length' => $network_info->max_length,
                'has_carrier_data' => true
            );

        } catch (Exception $e) {
            error_log('Network prefix check failed: ' . $e->getMessage());

            return array(
                'success' => false,
                'error' => 'Database error occurred while checking network prefix'
            );
        }
    }

    /**
     * Verify phone number with network prefix pre-check
     */
    public function verify_number($phone_number, $data_freshness = null) {
        // First check network prefix
        $prefix_check = $this->check_network_prefix($phone_number);

        if (!$prefix_check['success']) {
            return $prefix_check;
        }

        // If no live coverage, return without making API call
        if (!$prefix_check['live_coverage']) {
            error_log('Skipping API call for phone number with no live coverage: ' . $phone_number);

            return array(
                'success' => false,
                'error' => 'Phone number has no live coverage - API verification skipped to save costs',
                'phone_number' => $phone_number,
                'network' => $prefix_check['network_name'],
                'country_name' => $prefix_check['country_name'],
                'mcc' => $prefix_check['mcc'],
                'mnc' => $prefix_check['mnc'],
                'type' => 'mobile',
                'status' => 999,
                'status_message' => 'No Live Coverage',
                'ported' => false,
                'present' => 'no',
                'trxid' => null,
                'skip_reason' => 'no_live_coverage',
                'prefix_info' => $prefix_check
            );
        }

        // Check if we should force fresh data
        $should_force_fresh = $this->force_fresh($data_freshness);

        if (!$should_force_fresh) {
            // Check WordPress transient cache first
            $cache_key = 'phone_verification_' . md5($phone_number);
            $cached_result = get_transient($cache_key);
            if ($cached_result && $this->is_cache_fresh($cached_result, $data_freshness)) {
                error_log('Phone verification found in transient cache: ' . $phone_number);
                return $this->format_cached($cached_result);
            }

            // Check database
            $db_result = $this->get_verification_from_db($phone_number);
            if ($db_result && $this->is_db_fresh($db_result, $data_freshness)) {
                error_log('Phone verification found in database: ' . $phone_number);
                // Cache the database result
                set_transient($cache_key, $db_result, $this->cache_duration);
                return $this->format_cached($db_result);
            }
        }

        // Make API call
        return $this->make_api_call($phone_number, $prefix_check);
    }

    /**
     * Batch verification
     */
    public function verify_batch($phone_numbers, $data_freshness = null) {
        $results = array();
        $cache_hits = 0;
        $db_hits = 0;
        $api_calls = 0;
        $skipped_no_coverage = 0;

        $should_force_fresh = $this->force_fresh($data_freshness);

        foreach ($phone_numbers as $phone_number) {
            $prefix_check = $this->check_network_prefix($phone_number);

            if (!$prefix_check['success']) {
                $results[] = $prefix_check;
                continue;
            }

            // If no live coverage, skip API call
            if (!$prefix_check['live_coverage']) {
                $skipped_no_coverage++;
                $result = array(
                    'success' => false,
                    'error' => 'Phone number has no live coverage - API verification skipped to save costs',
                    'phone_number' => $phone_number,
                    'network' => $prefix_check['network_name'],
                    'country_name' => $prefix_check['country_name'],
                    'mcc' => $prefix_check['mcc'],
                    'mnc' => $prefix_check['mnc'],
                    'type' => 'mobile',
                    'status' => 999,
                    'status_message' => 'No Live Coverage',
                    'ported' => false,
                    'present' => 'no',
                    'trxid' => null,
                    'skip_reason' => 'no_live_coverage',
                    'source' => 'prefix_check'
                );
                $results[] = $result;
                usleep(100000);
                continue;
            }

            if (!$should_force_fresh) {
                // Check cache
                $cache_key = 'phone_verification_' . md5($phone_number);
                $cached_result = get_transient($cache_key);

                if ($cached_result && $this->is_cache_fresh($cached_result, $data_freshness)) {
                    $cache_hits++;
                    $result = $this->format_cached($cached_result);
                    $result['source'] = 'cache';
                    $results[] = $result;
                    usleep(100000);
                    continue;
                }

                // Check database
                $db_result = $this->get_verification_from_db($phone_number);
                if ($db_result && $this->is_db_fresh($db_result, $data_freshness)) {
                    $db_hits++;
                    set_transient($cache_key, $db_result, $this->cache_duration);
                    $result = $this->format_cached($db_result);
                    $result['source'] = 'database';
                    $results[] = $result;
                    usleep(100000);
                    continue;
                }
            }

            // Make API call
            $api_calls++;
            $result = $this->make_api_call($phone_number, $prefix_check);
            $result['source'] = 'api';
            $results[] = $result;
            usleep(100000);
        }

        return array(
            'results' => $results,
            'statistics' => array(
                'total_numbers' => count($phone_numbers),
                'cache_hits' => $cache_hits,
                'database_hits' => $db_hits,
                'api_calls' => $api_calls,
                'skipped_no_coverage' => $skipped_no_coverage
            )
        );
    }

    /**
     * Make API call to TMT service
     */
    private function make_api_call($phone_number, $prefix_info = null) {
        if (empty($this->api_key) || empty($this->api_secret)) {
            return $this->handle_api_error('TMT API credentials not configured', $phone_number, 'Configuration Error');
        }

        try {
            $url = $this->api_url . '/format/' . $this->api_key . '/' . $this->api_secret . '/' . $phone_number;

            error_log('TMT API Request - verified live coverage for: ' . $phone_number);

            $response = wp_remote_get($url, array(
                'timeout' => 30,
                'headers' => array(
                    'User-Agent' => 'WordPress Phone Verification Plugin'
                )
            ));

            if (is_wp_error($response)) {
                return $this->handle_api_error(
                    'API request failed: ' . $response->get_error_message(),
                    $phone_number,
                    'API Error'
                );
            }

            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code !== 200) {
                return $this->handle_api_error(
                    'API request failed with status: ' . $response_code,
                    $phone_number,
                    'API Error'
                );
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (empty($data)) {
                return $this->handle_api_error(
                    'API returned an empty response. Please check API credentials.',
                    $phone_number,
                    'Empty API Response'
                );
            }

            $result = $this->format_response($data, $prefix_info);

            if ($result['success']) {
                $this->save_result($result);
            }

            return $result;

        } catch (Exception $e) {
            return $this->handle_api_error($e->getMessage(), $phone_number, 'Exception Error');
        }
    }

    private function format_response($data, $prefix_info = null) {
        $phone_number = is_array($data) ? array_keys($data)[0] : null;
        $response_data = $phone_number ? $data[$phone_number] : array();

        $result = array(
            'success' => ($response_data['status'] ?? 1) === 0,
            'phone_number' => $response_data['number'] ?? $phone_number,
            'cic' => $response_data['cic'] ?? null,
            'error' => $response_data['error'] ?? 0,
            'imsi' => $response_data['imsi'] ?? null,
            'mcc' => $response_data['mcc'] ?? ($prefix_info['mcc'] ?? null),
            'mnc' => $response_data['mnc'] ?? ($prefix_info['mnc'] ?? null),
            'network' => $response_data['network'] ?? ($prefix_info['network_name'] ?? null),
            'number' => $response_data['number'] ?? null,
            'ported' => $response_data['ported'] ?? false,
            'present' => $response_data['present'] ?? null,
            'status' => $response_data['status'] ?? 0,
            'status_message' => $response_data['status_message'] ?? 'Unknown',
            'type' => $response_data['type'] ?? null,
            'trxid' => $response_data['trxid'] ?? null,
        );

        if ($prefix_info) {
            $result['prefix'] = $prefix_info['prefix'];
            $result['country_name'] = $prefix_info['country_name'];
            $result['min_length'] = $prefix_info['min_length'];
            $result['max_length'] = $prefix_info['max_length'];
            $result['live_coverage'] = $prefix_info['live_coverage'];
            $result['network_name'] = $prefix_info['network_name'];
        }

        return $result;
    }

    private function save_result($result_data) {
        global $wpdb;

        try {
            $table_name = $wpdb->prefix . 'phone_verifications';

            $verification_data = array(
                'cic' => $result_data['cic'],
                'error' => $result_data['error'] ?? 0,
                'imsi' => $result_data['imsi'],
                'mcc' => $result_data['mcc'],
                'mnc' => $result_data['mnc'],
                'network' => $result_data['network'],
                'ported' => $result_data['ported'] ? 1 : 0,
                'present' => $result_data['present'],
                'status' => $result_data['status'] ?? 0,
                'status_message' => $result_data['status_message'],
                'type' => $result_data['type'],
                'trxid' => $result_data['trxid'],
                'prefix' => $result_data['prefix'] ?? null,
                'updated_at' => current_time('mysql')
            );

            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM $table_name WHERE number = %s",
                $result_data['number']
            ));

            if ($existing) {
                $wpdb->update(
                    $table_name,
                    $verification_data,
                    array('number' => $result_data['number'])
                );
            } else {
                $verification_data['number'] = $result_data['number'];
                $verification_data['created_at'] = current_time('mysql');
                $wpdb->insert($table_name, $verification_data);
            }

            // Cache the result
            $cache_key = 'phone_verification_' . md5($result_data['number']);
            set_transient($cache_key, $verification_data, $this->cache_duration);

            error_log('Verification result saved and cached for: ' . $result_data['number']);

        } catch (Exception $e) {
            error_log('Failed to save verification result: ' . $e->getMessage());
        }
    }

    private function get_verification_from_db($phone_number) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'phone_verifications';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE number = %s ORDER BY created_at DESC LIMIT 1",
            $phone_number
        ));
    }

    private function format_cached($result) {
        if (is_object($result)) {
            return array(
                'success' => true,
                'phone_number' => $result->number,
                'number' => $result->number,
                'cic' => $result->cic,
                'error' => $result->error,
                'imsi' => $result->imsi,
                'mcc' => $result->mcc,
                'mnc' => $result->mnc,
                'network' => $result->network,
                'network_name' => $result->network,
                'ported' => (bool) $result->ported,
                'present' => $result->present,
                'status' => $result->status,
                'status_message' => $result->status_message,
                'type' => $result->type,
                'trxid' => $result->trxid,
                'prefix' => $result->prefix ?? null,
                'country_name' => $result->country_name ?? 'Unknown',
                'min_length' => $result->min_length ?? null,
                'max_length' => $result->max_length ?? null,
                'live_coverage' => $result->live_coverage ?? true,
                'cached' => true
            );
        }

        // For array results, ensure all required fields are present
        $formatted = array_merge($result, array('cached' => true));

        // Ensure required fields exist
        if (!isset($formatted['network_name']) && isset($formatted['network'])) {
            $formatted['network_name'] = $formatted['network'];
        }
        if (!isset($formatted['number']) && isset($formatted['phone_number'])) {
            $formatted['number'] = $formatted['phone_number'];
        }
        if (!isset($formatted['phone_number']) && isset($formatted['number'])) {
            $formatted['phone_number'] = $formatted['number'];
        }
        if (!isset($formatted['live_coverage'])) {
            $formatted['live_coverage'] = true; // Default to true for cached results
        }
        if (!isset($formatted['country_name'])) {
            $formatted['country_name'] = 'Unknown';
        }

        return $formatted;
    }

    private function handle_api_error($error_message, $phone_number, $status_message, $status_code = 1) {
        error_log($error_message . ' for phone: ' . $phone_number);

        return array(
            'success' => false,
            'error' => $error_message,
            'phone_number' => $phone_number,
            'status' => $status_code,
            'status_message' => $status_message,
        );
    }

    private function force_fresh($data_freshness) {
        return $data_freshness === 'all';
    }

    private function is_cache_fresh($cached_result, $data_freshness) {
        if (!$data_freshness || $data_freshness === '') {
            return true;
        }

        if ($data_freshness === 'all') {
            return false;
        }

        $cached_timestamp = null;
        if (is_object($cached_result) && isset($cached_result->created_at)) {
            $cached_timestamp = $cached_result->created_at;
        } elseif (is_array($cached_result) && isset($cached_result['created_at'])) {
            $cached_timestamp = $cached_result['created_at'];
        }

        if (!$cached_timestamp) {
            return false;
        }

        return $this->is_within_limit($cached_timestamp, $data_freshness);
    }

    private function is_db_fresh($db_result, $data_freshness) {
        if (!$data_freshness || $data_freshness === '') {
            return true;
        }

        if ($data_freshness === 'all') {
            return false;
        }

        return $this->is_within_limit($db_result->created_at, $data_freshness);
    }

    private function is_within_limit($timestamp, $data_freshness) {
        $days = (int) $data_freshness;
        if ($days <= 0) {
            return true;
        }

        $cutoff_timestamp = time() - ($days * 24 * 60 * 60);
        $data_timestamp = is_string($timestamp) ? strtotime($timestamp) : $timestamp;

        return $data_timestamp > $cutoff_timestamp;
    }
}