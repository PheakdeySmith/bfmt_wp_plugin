<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get verification data for frontend display
$verifications = Phone_Verification_Verification::get_recent(30); // Show last 30 days

// Frontend styles
wp_enqueue_style('phone-verification-frontend', PHONE_VERIFICATION_PLUGIN_URL . 'assets/css/frontend.css', array(), PHONE_VERIFICATION_VERSION);
wp_enqueue_script('phone-verification-frontend', PHONE_VERIFICATION_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), PHONE_VERIFICATION_VERSION, true);

// Localize script for AJAX
wp_localize_script('phone-verification-frontend', 'phoneVerificationFrontend', array(
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('phone_verification_nonce'),
    'strings' => array(
        'verifying' => __('Verifying...', 'phone-verification'),
        'error' => __('Error', 'phone-verification'),
        'success' => __('Success', 'phone-verification'),
        'cached_result' => __('Cached Result', 'phone-verification'),
    )
));
?>

<div class="phone-verification-frontend">
    <!-- Verification Form Section -->
    <div class="verification-form-container">
        <div class="form-header">
            <h2><?php esc_html_e('Phone Number Verification', 'phone-verification'); ?></h2>
        </div>

        <!-- Single Phone Verification Form -->
        <div class="verification-form">
            <form id="frontend-verify-form">
                <div class="form-group">
                    <div class="input-wrapper">
                        <input type="tel" id="frontend_phone_number" name="phone_number"
                               placeholder="<?php esc_attr_e('Enter phone number', 'phone-verification'); ?>" required>
                        <button type="button" class="btn btn-primary" id="frontend-verify-submit" disabled>
                            <span class="btn-spinner" style="display: none;"></span>
                            <?php esc_html_e('Verify', 'phone-verification'); ?>
                        </button>
                        <?php if ($atts['show_buttons'] === 'true'): ?>
                        <input type="file" id="frontend-file-upload" accept=".csv,.xlsx,.xls" style="display: none;">
                        <button type="button" class="btn btn-secondary" id="frontend-batch-btn">
                            <?php esc_html_e('Batch Upload', 'phone-verification'); ?>
                        </button>
                        <?php endif; ?>
                    </div>

                    <div id="frontend-phone-validation" class="validation-info" style="display: none;">
                        <div class="validation-alert">
                            <div class="network-detected">
                                <strong><?php esc_html_e('Network:', 'phone-verification'); ?></strong> <span id="frontend-detected-info"></span>
                            </div>
                            <div class="coverage-status">
                                <strong><?php esc_html_e('Live Coverage:', 'phone-verification'); ?></strong> <span id="frontend-coverage-status"></span>
                            </div>
                        </div>
                    </div>

                    <div id="frontend-phone-error" class="field-error"></div>
                </div>
            </form>

            <!-- Single Verification Result -->
            <div id="single-verification-result" class="single-result" style="display: none;">
                <div class="result-label">
                    <span class="result-phone"></span> -
                    <span class="result-status"></span>
                </div>
            </div>

            <!-- File Preview for Batch Upload -->
            <?php if ($atts['show_buttons'] === 'true'): ?>
            <div id="frontend-file-preview" class="file-preview" style="display: none;">
                <h4><?php esc_html_e('Preview:', 'phone-verification'); ?></h4>
                <div id="frontend-preview-info" class="preview-info"></div>
                <div class="preview-table-wrapper">
                    <table class="preview-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Phone Numbers', 'phone-verification'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="frontend-preview-body"></tbody>
                    </table>
                </div>

                <!-- Email Input for Batch Verification -->
                <div class="form-group">
                    <label for="frontend_batch_email"><?php esc_html_e('Email Address (Required)', 'phone-verification'); ?></label>
                    <input type="email" id="frontend_batch_email" name="batch_email"
                           placeholder="<?php esc_attr_e('your@email.com', 'phone-verification'); ?>" required>
                           <div id="frontend-email-error" class="field-error"></div>
                    <div class="field-help"><?php esc_html_e('Results will be sent to this email in case of connection issues', 'phone-verification'); ?></div>
                    
                </div>

                <div class="preview-actions">
                    <button type="button" class="btn btn-secondary" id="frontend-batch-cancel"><?php esc_html_e('Cancel', 'phone-verification'); ?></button>
                    <button type="button" class="btn btn-primary" id="frontend-batch-submit">
                        <span class="btn-spinner" style="display: none;"></span>
                        <?php esc_html_e('Verify All', 'phone-verification'); ?>
                    </button>
                </div>
                <div id="frontend-batch-error" class="field-error"></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Alert Container -->
        <div id="frontend-alert" class="alert-container" style="display: none;"></div>
    </div>

    <?php if ($atts['show_table'] === 'true'): ?>
    <!-- Batch Verification Results -->
    <div class="verification-results-container" id="batch-results-container" style="display: none;">
        <div class="results-header">
            <h3><?php esc_html_e('Batch Verification Results', 'phone-verification'); ?></h3>
            <div class="results-actions">
                <button type="button" class="btn btn-primary" id="download-csv-btn">
                    <span class="download-icon">⬇</span>
                    <?php esc_html_e('Download CSV', 'phone-verification'); ?>
                </button>
            </div>
        </div>

        <div class="results-table-wrapper">
            <table class="wp-list-table widefat fixed striped" id="frontend-verification-table">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column column-phone"><?php esc_html_e('Phone Number', 'phone-verification'); ?></th>
                        <th scope="col" class="manage-column column-country"><?php esc_html_e('Country', 'phone-verification'); ?></th>
                        <th scope="col" class="manage-column column-network"><?php esc_html_e('Network', 'phone-verification'); ?></th>
                        <th scope="col" class="manage-column column-status"><?php esc_html_e('Status', 'phone-verification'); ?></th>
                        <th scope="col" class="manage-column column-coverage"><?php esc_html_e('Live Coverage', 'phone-verification'); ?></th>
                        <th scope="col" class="manage-column column-date"><?php esc_html_e('Verified', 'phone-verification'); ?></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
