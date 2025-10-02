<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get current settings
$api_key = get_option('phone_verification_api_key', '');
$api_secret = get_option('phone_verification_api_secret', '');
$api_url = get_option('phone_verification_api_url', 'https://api.tmtvelocity.com/live');
$cache_duration = get_option('phone_verification_cache_duration', 3600);
$enable_cache = get_option('phone_verification_enable_cache', 1);

// Show update message if settings were saved
if (isset($_GET['updated']) && $_GET['updated'] == '1') {
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved successfully!', 'phone-verification') . '</p></div>';
}

// Get cached statistics
$cached_stats = Phone_Verification_Admin::get_cached_stats();
?>

<div class="wrap">
    <h1><?php echo esc_html__('Phone Verification Settings', 'phone-verification'); ?></h1>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-top: 20px;">
        <!-- Settings Form -->
        <div class="settings-form-container">
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('phone_verification_settings_nonce'); ?>
                <input type="hidden" name="action" value="save_phone_verification_settings">

                <!-- API Configuration -->
                <div class="settings-section">
                    <h2><?php esc_html_e('TMT API Configuration', 'phone-verification'); ?></h2>
                    <p class="description"><?php esc_html_e('Configure your TMT Velocity API credentials for phone number verification.', 'phone-verification'); ?></p>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="api_key"><?php esc_html_e('API Key', 'phone-verification'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="api_key" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" required>
                                <p class="description"><?php esc_html_e('Your TMT API key from the developer portal.', 'phone-verification'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="api_secret"><?php esc_html_e('API Secret', 'phone-verification'); ?></label>
                            </th>
                            <td>
                                <input type="password" id="api_secret" name="api_secret" value="<?php echo esc_attr($api_secret); ?>" class="regular-text" required>
                                <p class="description"><?php esc_html_e('Your TMT API secret key. Keep this secure!', 'phone-verification'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="api_url"><?php esc_html_e('API URL', 'phone-verification'); ?></label>
                            </th>
                            <td>
                                <input type="url" id="api_url" name="api_url" value="<?php echo esc_attr($api_url); ?>" class="regular-text" required>
                                <p class="description"><?php esc_html_e('TMT API endpoint URL. Default is recommended unless specified otherwise.', 'phone-verification'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Cache Configuration -->
                <div class="settings-section">
                    <h2><?php esc_html_e('Cache Configuration', 'phone-verification'); ?></h2>
                    <p class="description"><?php esc_html_e('Configure caching settings to improve performance and reduce API costs.', 'phone-verification'); ?></p>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <?php esc_html_e('Enable Caching', 'phone-verification'); ?>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="enable_cache" value="1" <?php checked($enable_cache, 1); ?>>
                                    <?php esc_html_e('Enable result caching', 'phone-verification'); ?>
                                </label>
                                <p class="description"><?php esc_html_e('Cache verification results to improve performance and reduce API calls.', 'phone-verification'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="cache_duration"><?php esc_html_e('Cache Duration', 'phone-verification'); ?></label>
                            </th>
                            <td>
                                <select id="cache_duration" name="cache_duration">
                                    <option value="1800" <?php selected($cache_duration, 1800); ?>><?php esc_html_e('30 minutes', 'phone-verification'); ?></option>
                                    <option value="3600" <?php selected($cache_duration, 3600); ?>><?php esc_html_e('1 hour', 'phone-verification'); ?></option>
                                    <option value="7200" <?php selected($cache_duration, 7200); ?>><?php esc_html_e('2 hours', 'phone-verification'); ?></option>
                                    <option value="21600" <?php selected($cache_duration, 21600); ?>><?php esc_html_e('6 hours', 'phone-verification'); ?></option>
                                    <option value="43200" <?php selected($cache_duration, 43200); ?>><?php esc_html_e('12 hours', 'phone-verification'); ?></option>
                                    <option value="86400" <?php selected($cache_duration, 86400); ?>><?php esc_html_e('24 hours', 'phone-verification'); ?></option>
                                </select>
                                <p class="description"><?php esc_html_e('How long to cache verification results before requiring fresh API calls.', 'phone-verification'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Submit Button -->
                <div class="settings-submit">
                    <?php submit_button(__('Save Settings', 'phone-verification'), 'primary'); ?>
                </div>
            </form>

            <!-- API Test Section -->
            <div class="settings-section">
                <h2><?php esc_html_e('API Connection Test', 'phone-verification'); ?></h2>
                <p class="description"><?php esc_html_e('Test your API configuration with a sample phone number.', 'phone-verification'); ?></p>

                <div id="api-test-section">
                    <div class="api-test-form">
                        <input type="tel" id="test-phone-number" placeholder="<?php esc_attr_e('Enter test phone number (e.g., 85592313242)', 'phone-verification'); ?>" class="regular-text">
                        <button type="button" id="test-api-btn" class="button button-secondary">
                            <span class="dashicons dashicons-admin-tools" style="vertical-align: middle;"></span>
                            <?php esc_html_e('Test API', 'phone-verification'); ?>
                        </button>
                    </div>
                    <div id="api-test-result" style="margin-top: 15px; display: none;"></div>
                </div>
            </div>
        </div>

        <!-- Statistics Sidebar -->
        <div class="settings-sidebar">
            <!-- Usage Statistics -->
            <div class="settings-widget">
                <h3><?php esc_html_e('Usage Statistics', 'phone-verification'); ?></h3>

                <div class="stat-item">
                    <div class="stat-label"><?php esc_html_e('Total Verifications', 'phone-verification'); ?></div>
                    <div class="stat-value"><?php echo esc_html(number_format($cached_stats['total_verifications'])); ?></div>
                </div>

                <div class="stat-item">
                    <div class="stat-label"><?php esc_html_e('Successful', 'phone-verification'); ?></div>
                    <div class="stat-value success"><?php echo esc_html(number_format($cached_stats['successful'])); ?></div>
                </div>

                <div class="stat-item">
                    <div class="stat-label"><?php esc_html_e('Failed', 'phone-verification'); ?></div>
                    <div class="stat-value error"><?php echo esc_html(number_format($cached_stats['failed'])); ?></div>
                </div>

                <div class="stat-item">
                    <div class="stat-label"><?php esc_html_e('Skipped (No Coverage)', 'phone-verification'); ?></div>
                    <div class="stat-value warning"><?php echo esc_html(number_format($cached_stats['skipped_no_coverage'])); ?></div>
                </div>

                <div class="stat-item">
                    <div class="stat-label"><?php esc_html_e('Today', 'phone-verification'); ?></div>
                    <div class="stat-value"><?php echo esc_html(number_format($cached_stats['today'])); ?></div>
                </div>
            </div>

            <!-- Performance Statistics -->
            <div class="settings-widget">
                <h3><?php esc_html_e('Performance', 'phone-verification'); ?></h3>

                <div class="stat-item">
                    <div class="stat-label"><?php esc_html_e('Batch Operations', 'phone-verification'); ?></div>
                    <div class="stat-value"><?php echo esc_html(number_format($cached_stats['batch_total'])); ?></div>
                </div>

                <div class="stat-item">
                    <div class="stat-label"><?php esc_html_e('Numbers Processed', 'phone-verification'); ?></div>
                    <div class="stat-value"><?php echo esc_html(number_format($cached_stats['batch_numbers'])); ?></div>
                </div>

                <div class="stat-item">
                    <div class="stat-label"><?php esc_html_e('Cache Hits', 'phone-verification'); ?></div>
                    <div class="stat-value success"><?php echo esc_html(number_format($cached_stats['cached_hits'])); ?></div>
                </div>
            </div>

            <!-- Plugin Information -->
            <div class="settings-widget">
                <h3><?php esc_html_e('Plugin Information', 'phone-verification'); ?></h3>

                <div class="info-item">
                    <div class="info-label"><?php esc_html_e('Version', 'phone-verification'); ?></div>
                    <div class="info-value"><?php echo esc_html(PHONE_VERIFICATION_VERSION); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label"><?php esc_html_e('Database Tables', 'phone-verification'); ?></div>
                    <div class="info-value">
                        <?php
                        global $wpdb;
                        $prefix_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}phone_network_prefixes");
                        $verification_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}phone_verifications");
                        echo esc_html(sprintf(
                            __('%d prefixes, %d verifications', 'phone-verification'),
                            $prefix_count,
                            $verification_count
                        ));
                        ?>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label"><?php esc_html_e('Shortcode', 'phone-verification'); ?></div>
                    <div class="info-value">
                        <code>[phone_verification]</code>
                        <button type="button" class="button button-small" onclick="navigator.clipboard.writeText('[phone_verification]')" title="<?php esc_attr_e('Copy to clipboard', 'phone-verification'); ?>">
                            <?php esc_html_e('Copy', 'phone-verification'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Help & Support -->
            <div class="settings-widget">
                <h3><?php esc_html_e('Help & Support', 'phone-verification'); ?></h3>

                <div class="help-links">
                    <p>
                        <a href="https://docs.tmtvelocity.com/" target="_blank" class="button button-secondary">
                            <span class="dashicons dashicons-external" style="vertical-align: middle;"></span>
                            <?php esc_html_e('TMT API Documentation', 'phone-verification'); ?>
                        </a>
                    </p>

                    <p>
                        <button type="button" class="button button-secondary" onclick="alert('<?php esc_js_e('For support, please contact your plugin developer.', 'phone-verification'); ?>')">
                            <span class="dashicons dashicons-sos" style="vertical-align: middle;"></span>
                            <?php esc_html_e('Get Support', 'phone-verification'); ?>
                        </button>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Settings Page Specific Styles */
.settings-form-container {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 25px;
}

.settings-section {
    margin-bottom: 30px;
    padding-bottom: 25px;
    border-bottom: 1px solid #f0f0f1;
}

.settings-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.settings-section h2 {
    margin: 0 0 10px 0;
    font-size: 18px;
    font-weight: 600;
    color: #1d2327;
}

.settings-section .description {
    margin: 0 0 20px 0;
    color: #646970;
    font-size: 14px;
    line-height: 1.5;
}

.settings-submit {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #f0f0f1;
}

/* API Test Section */
.api-test-form {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.api-test-form input {
    flex: 1;
    min-width: 250px;
}

#api-test-result {
    padding: 10px;
    border-radius: 4px;
    border-left: 4px solid;
}

#api-test-result.success {
    background: #dff0d8;
    border-color: #d6e9c6;
    color: #3c763d;
}

#api-test-result.error {
    background: #f2dede;
    border-color: #ebccd1;
    color: #a94442;
}

#api-test-result.loading {
    background: #d9edf7;
    border-color: #bce8f1;
    color: #31708f;
}

/* Sidebar Styles */
.settings-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.settings-widget {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
}

.settings-widget h3 {
    margin: 0 0 15px 0;
    font-size: 16px;
    font-weight: 600;
    color: #1d2327;
    border-bottom: 1px solid #f0f0f1;
    padding-bottom: 10px;
}

/* Statistics */
.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f8f9fa;
}

.stat-item:last-child {
    border-bottom: none;
}

.stat-label {
    font-size: 13px;
    color: #646970;
}

.stat-value {
    font-weight: 600;
    color: #1d2327;
}

.stat-value.success {
    color: #46b450;
}

.stat-value.error {
    color: #dc3232;
}

.stat-value.warning {
    color: #ffb900;
}

/* Info Items */
.info-item {
    margin-bottom: 12px;
}

.info-item:last-child {
    margin-bottom: 0;
}

.info-label {
    font-size: 13px;
    color: #646970;
    margin-bottom: 3px;
}

.info-value {
    font-size: 14px;
    color: #1d2327;
    display: flex;
    align-items: center;
    gap: 8px;
}

.info-value code {
    background: #f6f7f7;
    border: 1px solid #dcdcde;
    border-radius: 3px;
    padding: 2px 6px;
    font-size: 12px;
}

/* Help Links */
.help-links p {
    margin-bottom: 10px;
}

.help-links .button {
    width: 100%;
    justify-content: center;
    display: flex;
    align-items: center;
    gap: 5px;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .wrap > div {
        grid-template-columns: 1fr;
    }

    .settings-sidebar {
        flex-direction: row;
        flex-wrap: wrap;
    }

    .settings-widget {
        flex: 1;
        min-width: 250px;
    }
}

@media (max-width: 768px) {
    .api-test-form {
        flex-direction: column;
        align-items: stretch;
    }

    .api-test-form input {
        width: 100%;
        min-width: auto;
    }

    .settings-sidebar {
        flex-direction: column;
    }

    .help-links .button {
        font-size: 12px;
        padding: 6px 12px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // API Test functionality
    $('#test-api-btn').on('click', function() {
        const phoneNumber = $('#test-phone-number').val().trim();
        const $result = $('#api-test-result');
        const $btn = $(this);

        if (!phoneNumber) {
            $result.removeClass('success error').addClass('error')
                .text('<?php esc_js_e('Please enter a phone number to test.', 'phone-verification'); ?>')
                .show();
            return;
        }

        $btn.prop('disabled', true).text('<?php esc_js_e('Testing...', 'phone-verification'); ?>');
        $result.removeClass('success error').addClass('loading')
            .text('<?php esc_js_e('Testing API connection...', 'phone-verification'); ?>')
            .show();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'verify_phone',
                phone_number: phoneNumber,
                nonce: '<?php echo wp_create_nonce('phone_verification_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $result.removeClass('loading error').addClass('success')
                        .html('<?php esc_js_e('✓ API connection successful! Phone number verification completed.', 'phone-verification'); ?>');
                } else {
                    $result.removeClass('loading success').addClass('error')
                        .text('<?php esc_js_e('✗ API test failed: ', 'phone-verification'); ?>' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                $result.removeClass('loading success').addClass('error')
                    .text('<?php esc_js_e('✗ Network error occurred during API test.', 'phone-verification'); ?>');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-admin-tools" style="vertical-align: middle;"></span> <?php esc_js_e('Test API', 'phone-verification'); ?>');
            }
        });
    });

    // Allow Enter key to trigger API test
    $('#test-phone-number').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#test-api-btn').click();
        }
    });
});
</script>