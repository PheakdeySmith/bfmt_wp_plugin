<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get verification data
$verifications = Phone_Verification_Admin::get_verifications();
$stats = Phone_Verification_Admin::get_verification_stats();
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php echo esc_html__('Phone Number Verification', 'phone-verification'); ?>
    </h1>

    <!-- Stats Cards -->
    <div class="phone-verification-stats" style="display: flex; gap: 20px; margin: 20px 0;">
        <div class="stat-card" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; min-width: 150px;">
            <h3 style="margin: 0 0 10px 0; font-size: 14px; color: #555;"><?php esc_html_e('Total Verifications', 'phone-verification'); ?></h3>
            <div style="font-size: 24px; font-weight: bold; color: #333;"><?php echo esc_html($stats['total']); ?></div>
        </div>

        <div class="stat-card" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; min-width: 150px;">
            <h3 style="margin: 0 0 10px 0; font-size: 14px; color: #555;"><?php esc_html_e('Successful', 'phone-verification'); ?></h3>
            <div style="font-size: 24px; font-weight: bold; color: #46b450;"><?php echo esc_html($stats['successful']); ?></div>
            <div style="font-size: 12px; color: #777;"><?php echo esc_html($stats['success_rate']); ?>% success rate</div>
        </div>

        <div class="stat-card" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; min-width: 150px;">
            <h3 style="margin: 0 0 10px 0; font-size: 14px; color: #555;"><?php esc_html_e('Today', 'phone-verification'); ?></h3>
            <div style="font-size: 24px; font-weight: bold; color: #0073aa;"><?php echo esc_html($stats['today']); ?></div>
        </div>

        <div class="stat-card" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; min-width: 150px;">
            <h3 style="margin: 0 0 10px 0; font-size: 14px; color: #555;"><?php esc_html_e('This Month', 'phone-verification'); ?></h3>
            <div style="font-size: 24px; font-weight: bold; color: #333;"><?php echo esc_html($stats['this_month']); ?></div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="phone-verification-actions" style="margin: 20px 0;">
        <button type="button" class="button button-primary" id="verify-single-btn">
            <span class="dashicons dashicons-plus" style="vertical-align: middle;"></span>
            <?php esc_html_e('Verify Single Number', 'phone-verification'); ?>
        </button>

        <button type="button" class="button button-primary" id="batch-verify-btn">
            <span class="dashicons dashicons-upload" style="vertical-align: middle;"></span>
            <?php esc_html_e('Batch Upload', 'phone-verification'); ?>
        </button>

        <a href="<?php echo esc_url(admin_url('admin-post.php?action=export_phone_verifications&nonce=' . wp_create_nonce('phone_verification_nonce'))); ?>" class="button">
            <span class="dashicons dashicons-download" style="vertical-align: middle;"></span>
            <?php esc_html_e('Export Results', 'phone-verification'); ?>
        </a>

        <button type="button" class="button" id="filter-btn">
            <span class="dashicons dashicons-filter" style="vertical-align: middle;"></span>
            <?php esc_html_e('Filter', 'phone-verification'); ?>
            <span id="filter-count" class="count-bubble" style="display: none;"></span>
        </button>
    </div>

    <!-- Alert Container -->
    <div id="phone-verification-alert" style="display: none; margin: 20px 0;"></div>

    <!-- Batch Results Container -->
    <div id="batch-results-container"></div>

    <!-- Verification Results Table -->
    <div class="phone-verification-table-container" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; margin: 20px 0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0;">
                <span class="dashicons dashicons-list-view" style="vertical-align: middle;"></span>
                <?php esc_html_e('Network Prefix Verification Results', 'phone-verification'); ?>
            </h2>
        </div>

        <table id="phone-verification-table" class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Number', 'phone-verification'); ?></th>
                    <th><?php esc_html_e('Country', 'phone-verification'); ?></th>
                    <th><?php esc_html_e('Min/Max Length', 'phone-verification'); ?></th>
                    <th><?php esc_html_e('Network', 'phone-verification'); ?></th>
                    <th><?php esc_html_e('MCC/MNC', 'phone-verification'); ?></th>
                    <th><?php esc_html_e('Live Coverage', 'phone-verification'); ?></th>
                    <th><?php esc_html_e('Type', 'phone-verification'); ?></th>
                    <th><?php esc_html_e('Status', 'phone-verification'); ?></th>
                    <th><?php esc_html_e('Ported', 'phone-verification'); ?></th>
                    <th><?php esc_html_e('Present', 'phone-verification'); ?></th>
                    <th><?php esc_html_e('Transaction ID', 'phone-verification'); ?></th>
                    <th><?php esc_html_e('Verified', 'phone-verification'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($verifications)): ?>
                    <?php foreach ($verifications as $verification): ?>
                        <tr data-phone="<?php echo esc_attr($verification->number); ?>">
                            <td><?php echo esc_html($verification->number); ?></td>
                            <td><?php echo esc_html($verification->country_name ?: 'Unknown'); ?></td>
                            <td><?php echo esc_html(($verification->min_length ?: 'N/A') . '/' . ($verification->max_length ?: 'N/A')); ?></td>
                            <td><?php echo esc_html($verification->prefix_network_name ?: $verification->network ?: 'Unknown'); ?></td>
                            <td><?php echo esc_html(($verification->mcc ?: '') . '/' . ($verification->mnc ?: '')); ?></td>
                            <td>
                                <?php
                                $live_coverage = $verification->live_coverage;
                                $badge_class = $live_coverage ? 'success' : 'danger';
                                $badge_text = $live_coverage ? 'Yes' : 'No';
                                ?>
                                <span class="badge badge-<?php echo esc_attr($badge_class); ?>"><?php echo esc_html($badge_text); ?></span>
                            </td>
                            <td><?php echo esc_html(ucfirst($verification->type ?: 'unknown')); ?></td>
                            <td>
                                <?php
                                $status_class = ($verification->status == 0) ? 'success' : 'danger';
                                $status_text = '';
                                switch ($verification->status) {
                                    case 0: $status_text = 'Success'; break;
                                    case 999: $status_text = 'No Live Coverage'; break;
                                    default: $status_text = $verification->status_message ?: 'Failed';
                                }
                                ?>
                                <span class="badge badge-<?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_text); ?></span>
                            </td>
                            <td>
                                <?php
                                $ported_class = $verification->ported ? 'success' : 'secondary';
                                $ported_text = $verification->ported ? 'Yes' : 'No';
                                ?>
                                <span class="badge badge-<?php echo esc_attr($ported_class); ?>"><?php echo esc_html($ported_text); ?></span>
                            </td>
                            <td>
                                <?php
                                $present = strtolower($verification->present ?: 'na');
                                if (!in_array($present, ['yes', 'no', 'na'])) {
                                    $present = 'na';
                                }
                                $present_text = ucfirst($present);
                                $present_class = 'secondary';
                                if ($present === 'yes') {
                                    $present_class = 'success';
                                }
                                ?>
                                <span class="badge badge-<?php echo esc_attr($present_class); ?>"><?php echo esc_html($present_text); ?></span>
                            </td>
                            <td><?php echo esc_html($verification->trxid ?: 'N/A'); ?></td>
                            <td><?php echo esc_html(date('Y-m-d H:i', strtotime($verification->created_at))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="12" style="text-align: center; padding: 40px;">
                            <?php esc_html_e('No verification results found. Start by verifying some phone numbers!', 'phone-verification'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Single Phone Verification Modal -->
<div id="single-verify-modal" class="phone-verification-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2><?php esc_html_e('Smart Phone Number Verification', 'phone-verification'); ?></h2>
            <span class="modal-close">&times;</span>
        </div>

        <div class="modal-body">
            <form id="single-verify-form">
                <div class="form-field">
                    <label for="phone_number"><?php esc_html_e('Enter Phone Number', 'phone-verification'); ?></label>
                    <input type="tel" id="phone_number" name="phone_number" placeholder="e.g., 85592313242" required>
                    <div class="field-description"><?php esc_html_e('Enter the full phone number including country code', 'phone-verification'); ?></div>
                    <div id="phone-validation-info" style="display: none;">
                        <div class="validation-alert">
                            <div id="network-info">
                                <strong><?php esc_html_e('Network Detected:', 'phone-verification'); ?></strong> <span id="detected-info"></span>
                            </div>
                            <div id="coverage-info">
                                <strong><?php esc_html_e('Live Coverage:', 'phone-verification'); ?></strong> <span id="coverage-status"></span>
                            </div>
                        </div>
                    </div>
                    <div id="phone-error" class="field-error"></div>
                </div>

                <div class="form-field">
                    <label for="data_freshness"><?php esc_html_e('Data Freshness', 'phone-verification'); ?></label>
                    <select id="data_freshness" name="data_freshness">
                        <option value=""><?php esc_html_e('Use cached data if available (recommended)', 'phone-verification'); ?></option>
                        <option value="30"><?php esc_html_e('Force refresh if data is older than 30 days', 'phone-verification'); ?></option>
                        <option value="60"><?php esc_html_e('Force refresh if data is older than 60 days', 'phone-verification'); ?></option>
                        <option value="90"><?php esc_html_e('Force refresh if data is older than 90 days', 'phone-verification'); ?></option>
                        <option value="all"><?php esc_html_e('Always get fresh data from API', 'phone-verification'); ?></option>
                    </select>
                    <div class="field-description"><?php esc_html_e('Select when to fetch fresh data from the API vs using cached results', 'phone-verification'); ?></div>
                </div>
            </form>
        </div>

        <div class="modal-footer">
            <button type="button" class="button" id="single-verify-cancel"><?php esc_html_e('Cancel', 'phone-verification'); ?></button>
            <button type="button" class="button button-primary" id="single-verify-submit" disabled>
                <span class="spinner" style="display: none;"></span>
                <?php esc_html_e('Verify', 'phone-verification'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Batch Upload Modal -->
<div id="batch-verify-modal" class="phone-verification-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2><?php esc_html_e('Batch Network Verification', 'phone-verification'); ?></h2>
            <span class="modal-close">&times;</span>
        </div>

        <div class="modal-body">
            <div class="form-field">
                <label for="file-upload"><?php esc_html_e('Upload Excel File', 'phone-verification'); ?></label>
                <input type="file" id="file-upload" accept=".xlsx,.xls,.csv" required>
                <div class="field-description"><?php esc_html_e('Upload an Excel file (.xlsx, .xls) or CSV file with phone numbers in the first column', 'phone-verification'); ?></div>
                <div id="batch-error" class="field-error"></div>
            </div>

            <div id="file-preview" style="display: none;">
                <h3><?php esc_html_e('File Preview:', 'phone-verification'); ?></h3>
                <div id="preview-info" class="notice"></div>
                <div class="file-preview-table">
                    <table class="wp-list-table widefat">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Phone Numbers Found', 'phone-verification'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="preview-body"></tbody>
                    </table>
                </div>
            </div>

            <div class="form-field">
                <label for="batch_data_freshness"><?php esc_html_e('Data Freshness', 'phone-verification'); ?></label>
                <select id="batch_data_freshness" name="batch_data_freshness">
                    <option value=""><?php esc_html_e('Use cached data if available (recommended)', 'phone-verification'); ?></option>
                    <option value="30"><?php esc_html_e('Force refresh if data is older than 30 days', 'phone-verification'); ?></option>
                    <option value="60"><?php esc_html_e('Force refresh if data is older than 60 days', 'phone-verification'); ?></option>
                    <option value="90"><?php esc_html_e('Force refresh if data is older than 90 days', 'phone-verification'); ?></option>
                    <option value="all"><?php esc_html_e('Verify All Fresh (bypass cache & database)', 'phone-verification'); ?></option>
                </select>
                <div class="field-description">
                    <strong><?php esc_html_e('Fresh Verification:', 'phone-verification'); ?></strong>
                    <?php esc_html_e('Numbers with live coverage will be verified with fresh API calls. Numbers without live coverage will be skipped to save costs and only checked against local database.', 'phone-verification'); ?>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="button" id="batch-verify-cancel"><?php esc_html_e('Cancel', 'phone-verification'); ?></button>
            <button type="button" class="button button-primary" id="batch-verify-submit">
                <span class="spinner" style="display: none;"></span>
                <?php esc_html_e('Verify All', 'phone-verification'); ?>
            </button>
        </div>
    </div>
</div>

<style>
/* Badge styles */
.badge {
    display: inline-block;
    padding: 4px 8px;
    font-size: 11px;
    font-weight: bold;
    border-radius: 3px;
    text-transform: uppercase;
}

.badge-success {
    background-color: #dff0d8;
    color: #3c763d;
    border: 1px solid #d6e9c6;
}

.badge-danger {
    background-color: #f2dede;
    color: #a94442;
    border: 1px solid #ebccd1;
}

.badge-secondary {
    background-color: #f5f5f5;
    color: #666;
    border: 1px solid #ddd;
}

/* Modal styles */
.phone-verification-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    z-index: 999999;
}

.modal-content {
    position: relative;
    background-color: #fff;
    margin: 50px auto;
    padding: 0;
    width: 600px;
    max-width: 90%;
    border-radius: 4px;
    box-shadow: 0 4px 25px rgba(0,0,0,0.3);
}

.modal-header {
    padding: 20px 25px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
    font-size: 18px;
}

.modal-close {
    font-size: 24px;
    cursor: pointer;
    color: #999;
}

.modal-close:hover {
    color: #333;
}

.modal-body {
    padding: 25px;
}

.modal-footer {
    padding: 20px 25px;
    border-top: 1px solid #ddd;
    text-align: right;
}

.modal-footer .button {
    margin-left: 10px;
}

/* Form styles */
.form-field {
    margin-bottom: 20px;
}

.form-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.form-field input,
.form-field select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.field-description {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}

.field-error {
    color: #dc3232;
    font-size: 12px;
    margin-top: 5px;
}

.validation-alert {
    padding: 10px;
    margin-top: 10px;
    border-radius: 4px;
    border: 1px solid #ddd;
    background-color: #f9f9f9;
}

/* File preview table */
.file-preview-table {
    max-height: 200px;
    overflow-y: auto;
    margin-top: 10px;
}

/* Count bubble */
.count-bubble {
    background: #dc3232;
    color: white;
    border-radius: 50%;
    padding: 2px 6px;
    font-size: 10px;
    font-weight: bold;
    margin-left: 5px;
}

/* Spinner */
.spinner {
    background: url('<?php echo admin_url('images/spinner.gif'); ?>') no-repeat;
    background-size: 16px 16px;
    display: inline-block;
    width: 16px;
    height: 16px;
    margin-right: 5px;
    vertical-align: middle;
}

/* Alert styles */
.phone-verification-alert {
    padding: 12px 15px;
    border-radius: 4px;
    border-left: 4px solid;
    margin: 15px 0;
}

.phone-verification-alert.success {
    background-color: #dff0d8;
    border-color: #d6e9c6;
    color: #3c763d;
}

.phone-verification-alert.error {
    background-color: #f2dede;
    border-color: #ebccd1;
    color: #a94442;
}

.phone-verification-alert.info {
    background-color: #d9edf7;
    border-color: #bce8f1;
    color: #31708f;
}

.phone-verification-alert.warning {
    background-color: #fcf8e3;
    border-color: #faebcc;
    color: #8a6d3b;
}
</style>

<script>
// Add this script to the template for now, will be moved to separate file later
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTables if available
    if (typeof jQuery !== 'undefined' && jQuery.fn.DataTable) {
        jQuery('#phone-verification-table').DataTable({
            "pageLength": 25,
            "order": [[ 11, "desc" ]], // Sort by verification date
            "columnDefs": [
                { "orderable": false, "targets": [5, 7, 8, 9] } // Disable sorting for badge columns
            ]
        });
    }
});
</script>