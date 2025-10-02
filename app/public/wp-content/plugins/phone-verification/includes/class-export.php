<?php

if (!defined('ABSPATH')) {
    exit;
}

class Phone_Verification_Export {

    public static function export_csv() {
        $verifications = Phone_Verification_Verification::get_all(1000); // Get all records, limit to 1000 for performance

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
            $status_text = self::get_status_text($verification->status);
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

    public static function export_json() {
        $verifications = Phone_Verification_Verification::get_all(1000);

        $filename = 'phone-verification-results-' . date('Y-m-d-H-i-s') . '.json';

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $data = array();

        foreach ($verifications as $verification) {
            $data[] = array(
                'number' => $verification->number,
                'country' => $verification->country_name ?: 'Unknown',
                'network' => $verification->prefix_network_name ?: $verification->network ?: 'Unknown',
                'mcc' => $verification->mcc,
                'mnc' => $verification->mnc,
                'live_coverage' => (bool) $verification->live_coverage,
                'type' => $verification->type,
                'status' => $verification->status,
                'status_text' => self::get_status_text($verification->status),
                'ported' => (bool) $verification->ported,
                'present' => $verification->present,
                'trxid' => $verification->trxid,
                'verified_at' => $verification->created_at
            );
        }

        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }

    private static function get_status_text($status) {
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
}