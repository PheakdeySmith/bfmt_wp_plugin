<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get network prefixes data
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$prefixes = Phone_Verification_Admin::get_network_prefixes($search);
$countries = Phone_Verification_Network_Prefix::get_countries();
$total_prefixes = count($prefixes);
$live_coverage_count = Phone_Verification_Network_Prefix::get_live_coverage_count();
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php echo esc_html__('Network Prefixes', 'phone-verification'); ?>
    </h1>

    <!-- Summary Cards -->
    <div class="phone-verification-stats" style="margin: 20px 0;">
        <div class="stat-card">
            <h3><?php esc_html_e('Total Prefixes', 'phone-verification'); ?></h3>
            <div class="stat-value"><?php echo esc_html($total_prefixes); ?></div>
        </div>

        <div class="stat-card">
            <h3><?php esc_html_e('Live Coverage', 'phone-verification'); ?></h3>
            <div class="stat-value" style="color: #46b450;"><?php echo esc_html($live_coverage_count); ?></div>
            <div class="stat-meta"><?php echo esc_html(sprintf(__('%d%% coverage', 'phone-verification'), $total_prefixes > 0 ? round(($live_coverage_count / $total_prefixes) * 100) : 0)); ?></div>
        </div>

        <div class="stat-card">
            <h3><?php esc_html_e('Countries', 'phone-verification'); ?></h3>
            <div class="stat-value"><?php echo esc_html(count($countries)); ?></div>
        </div>
    </div>

    <!-- Search and Actions -->
    <div class="prefixes-actions" style="margin: 20px 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
        <div class="search-form">
            <form method="get" style="display: flex; gap: 10px; align-items: center;">
                <input type="hidden" name="page" value="phone-verification-prefixes">
                <input type="search" name="search" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search prefixes, countries, or networks...', 'phone-verification'); ?>" style="min-width: 300px;">
                <button type="submit" class="button"><?php esc_html_e('Search', 'phone-verification'); ?></button>
                <?php if ($search): ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=phone-verification-prefixes')); ?>" class="button"><?php esc_html_e('Clear', 'phone-verification'); ?></a>
                <?php endif; ?>
            </form>
        </div>

        <div class="action-buttons">
            <button type="button" class="button button-primary" id="add-prefix-btn">
                <span class="dashicons dashicons-plus" style="vertical-align: middle;"></span>
                <?php esc_html_e('Add Prefix', 'phone-verification'); ?>
            </button>
        </div>
    </div>

    <?php if ($search): ?>
        <div class="search-results-info">
            <p><?php echo esc_html(sprintf(__('Showing %d results for "%s"', 'phone-verification'), count($prefixes), $search)); ?></p>
        </div>
    <?php endif; ?>

    <!-- Network Prefixes Table -->
    <div class="phone-verification-table-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0;">
                <span class="dashicons dashicons-networking" style="vertical-align: middle;"></span>
                <?php esc_html_e('Network Prefixes Database', 'phone-verification'); ?>
            </h2>
        </div>

        <table id="network-prefixes-table" class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Prefix', 'phone-verification'); ?></th>
                    <th><?php esc_html_e('Country', 'phone-verification'); ?></th>
                    <th><?php esc_html_e('Network', 'phone-verification'); ?></th>
                    <th><?php esc_html_e('Length Range', 'phone-verification'); ?></th>
                    <th><?php esc_html_e('MCC/MNC', 'phone-verification'); ?></th>
                    <th><?php esc_html_e('Live Coverage', 'phone-verification'); ?></th>
                    <th><?php esc_html_e('Actions', 'phone-verification'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($prefixes)): ?>
                    <?php foreach ($prefixes as $prefix): ?>
                        <tr data-prefix="<?php echo esc_attr($prefix->prefix); ?>">
                            <td>
                                <strong><?php echo esc_html($prefix->prefix); ?></strong>
                            </td>
                            <td><?php echo esc_html($prefix->country_name); ?></td>
                            <td><?php echo esc_html($prefix->network_name); ?></td>
                            <td><?php echo esc_html($prefix->min_length . '-' . $prefix->max_length . ' digits'); ?></td>
                            <td>
                                <?php if ($prefix->mcc && $prefix->mnc): ?>
                                    <code><?php echo esc_html($prefix->mcc . '/' . $prefix->mnc); ?></code>
                                <?php else: ?>
                                    <span class="description"><?php esc_html_e('Not set', 'phone-verification'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <label class="coverage-toggle">
                                    <input type="checkbox"
                                           class="coverage-checkbox"
                                           data-prefix="<?php echo esc_attr($prefix->prefix); ?>"
                                           <?php checked($prefix->live_coverage, 1); ?>>
                                    <span class="coverage-slider"></span>
                                    <span class="coverage-text">
                                        <?php echo $prefix->live_coverage ? esc_html__('Yes', 'phone-verification') : esc_html__('No', 'phone-verification'); ?>
                                    </span>
                                </label>
                            </td>
                            <td>
                                <button type="button" class="button button-small edit-prefix-btn"
                                        data-prefix="<?php echo esc_attr($prefix->prefix); ?>"
                                        data-country="<?php echo esc_attr($prefix->country_name); ?>"
                                        data-network="<?php echo esc_attr($prefix->network_name); ?>"
                                        data-min-length="<?php echo esc_attr($prefix->min_length); ?>"
                                        data-max-length="<?php echo esc_attr($prefix->max_length); ?>"
                                        data-mcc="<?php echo esc_attr($prefix->mcc); ?>"
                                        data-mnc="<?php echo esc_attr($prefix->mnc); ?>"
                                        data-live-coverage="<?php echo esc_attr($prefix->live_coverage); ?>">
                                    <?php esc_html_e('Edit', 'phone-verification'); ?>
                                </button>
                                <button type="button" class="button button-small button-link-delete delete-prefix-btn"
                                        data-prefix="<?php echo esc_attr($prefix->prefix); ?>">
                                    <?php esc_html_e('Delete', 'phone-verification'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px;">
                            <?php if ($search): ?>
                                <?php esc_html_e('No prefixes found matching your search criteria.', 'phone-verification'); ?>
                            <?php else: ?>
                                <?php esc_html_e('No network prefixes found. This should not happen - please check your installation.', 'phone-verification'); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Prefix Modal -->
<div id="prefix-modal" class="phone-verification-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="prefix-modal-title"><?php esc_html_e('Add Network Prefix', 'phone-verification'); ?></h2>
            <span class="modal-close">&times;</span>
        </div>

        <div class="modal-body">
            <form id="prefix-form">
                <input type="hidden" id="prefix-action" value="add">
                <input type="hidden" id="original-prefix" value="">

                <div class="form-field">
                    <label for="prefix-input"><?php esc_html_e('Prefix', 'phone-verification'); ?> <span class="required">*</span></label>
                    <input type="text" id="prefix-input" name="prefix" required maxlength="20" placeholder="<?php esc_attr_e('e.g., 85592', 'phone-verification'); ?>">
                    <div class="field-description"><?php esc_html_e('Phone number prefix without spaces or special characters', 'phone-verification'); ?></div>
                    <div id="prefix-error" class="field-error"></div>
                </div>

                <div class="form-field">
                    <label for="country-input"><?php esc_html_e('Country', 'phone-verification'); ?> <span class="required">*</span></label>
                    <input type="text" id="country-input" name="country_name" required maxlength="100" placeholder="<?php esc_attr_e('e.g., Cambodia', 'phone-verification'); ?>">
                    <div class="field-description"><?php esc_html_e('Full country name', 'phone-verification'); ?></div>
                </div>

                <div class="form-field">
                    <label for="network-input"><?php esc_html_e('Network Name', 'phone-verification'); ?> <span class="required">*</span></label>
                    <input type="text" id="network-input" name="network_name" required maxlength="100" placeholder="<?php esc_attr_e('e.g., KH Cellcard Mobile', 'phone-verification'); ?>">
                    <div class="field-description"><?php esc_html_e('Mobile network operator name', 'phone-verification'); ?></div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-field">
                        <label for="min-length-input"><?php esc_html_e('Min Length', 'phone-verification'); ?> <span class="required">*</span></label>
                        <input type="number" id="min-length-input" name="min_length" required min="8" max="15" placeholder="11">
                        <div class="field-description"><?php esc_html_e('Minimum digits', 'phone-verification'); ?></div>
                    </div>

                    <div class="form-field">
                        <label for="max-length-input"><?php esc_html_e('Max Length', 'phone-verification'); ?> <span class="required">*</span></label>
                        <input type="number" id="max-length-input" name="max_length" required min="8" max="15" placeholder="11">
                        <div class="field-description"><?php esc_html_e('Maximum digits', 'phone-verification'); ?></div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-field">
                        <label for="mcc-input"><?php esc_html_e('MCC', 'phone-verification'); ?></label>
                        <input type="text" id="mcc-input" name="mcc" maxlength="10" placeholder="456">
                        <div class="field-description"><?php esc_html_e('Mobile Country Code', 'phone-verification'); ?></div>
                    </div>

                    <div class="form-field">
                        <label for="mnc-input"><?php esc_html_e('MNC', 'phone-verification'); ?></label>
                        <input type="text" id="mnc-input" name="mnc" maxlength="10" placeholder="01">
                        <div class="field-description"><?php esc_html_e('Mobile Network Code', 'phone-verification'); ?></div>
                    </div>
                </div>

                <div class="form-field">
                    <label>
                        <input type="checkbox" id="live-coverage-input" name="live_coverage" value="1">
                        <?php esc_html_e('Has Live Coverage', 'phone-verification'); ?>
                    </label>
                    <div class="field-description"><?php esc_html_e('Whether this prefix supports live API verification', 'phone-verification'); ?></div>
                </div>
            </form>
        </div>

        <div class="modal-footer">
            <button type="button" class="button" id="prefix-cancel"><?php esc_html_e('Cancel', 'phone-verification'); ?></button>
            <button type="button" class="button button-primary" id="prefix-save">
                <span class="spinner" style="display: none;"></span>
                <?php esc_html_e('Save Prefix', 'phone-verification'); ?>
            </button>
        </div>
    </div>
</div>

<style>
/* Network Prefixes Specific Styles */
.search-results-info {
    background: #f0f6fc;
    border: 1px solid #c3d9e3;
    border-radius: 4px;
    padding: 12px 16px;
    margin: 15px 0;
    color: #0a4b78;
}

.prefixes-actions {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 15px 20px;
}

.search-form form {
    flex-wrap: wrap;
}

.search-form input[type="search"] {
    padding: 6px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

/* Coverage Toggle Switch */
.coverage-toggle {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    user-select: none;
}

.coverage-checkbox {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.coverage-slider {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
    background-color: #ccc;
    border-radius: 24px;
    transition: background-color 0.3s ease;
}

.coverage-slider:before {
    content: "";
    position: absolute;
    top: 2px;
    left: 2px;
    width: 20px;
    height: 20px;
    background-color: white;
    border-radius: 50%;
    transition: transform 0.3s ease;
}

.coverage-checkbox:checked + .coverage-slider {
    background-color: #46b450;
}

.coverage-checkbox:checked + .coverage-slider:before {
    transform: translateX(20px);
}

.coverage-text {
    font-size: 13px;
    font-weight: 500;
    min-width: 30px;
}

/* Form Grid Layouts */
.form-field[style*="grid"] {
    margin-bottom: 0;
}

.form-field[style*="grid"] .form-field {
    margin-bottom: 20px;
}

.required {
    color: #dc3232;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

/* Button Improvements */
.button-small {
    padding: 4px 8px;
    font-size: 12px;
    line-height: 1.4;
    min-height: auto;
}

.button-link-delete {
    color: #a00;
    text-decoration: none;
    border: none;
    background: none;
    box-shadow: none;
}

.button-link-delete:hover {
    color: #dc3232;
    background: none;
    box-shadow: none;
}

/* Table Improvements */
#network-prefixes-table {
    margin-top: 0;
}

#network-prefixes-table th,
#network-prefixes-table td {
    padding: 12px 8px;
}

#network-prefixes-table code {
    background: #f1f1f1;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 12px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .prefixes-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .search-form,
    .action-buttons {
        width: 100%;
    }

    .search-form form {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
    }

    .search-form input[type="search"] {
        min-width: auto;
        width: 100%;
    }

    .action-buttons {
        justify-content: center;
    }

    .phone-verification-table-container {
        overflow-x: auto;
    }

    #network-prefixes-table {
        min-width: 800px;
    }

    .modal-content {
        width: 95%;
        margin: 20px auto;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Initialize DataTable if available
    if ($.fn.DataTable && $('#network-prefixes-table tbody tr').length > 0) {
        $('#network-prefixes-table').DataTable({
            pageLength: 25,
            order: [[ 0, "asc" ]], // Sort by prefix
            columnDefs: [
                { orderable: false, targets: [5, 6] } // Disable sorting for coverage and actions
            ],
            language: {
                emptyTable: "No network prefixes found",
                zeroRecords: "No matching prefixes found",
                search: "Search prefixes:"
            }
        });
    }

    // Add prefix button
    $('#add-prefix-btn').on('click', function() {
        openPrefixModal('add');
    });

    // Edit prefix buttons
    $(document).on('click', '.edit-prefix-btn', function() {
        const data = $(this).data();
        openPrefixModal('edit', data);
    });

    // Delete prefix buttons
    $(document).on('click', '.delete-prefix-btn', function() {
        const prefix = $(this).data('prefix');
        if (confirm('<?php esc_js_e('Are you sure you want to delete this network prefix?', 'phone-verification'); ?>')) {
            deletePrefix(prefix);
        }
    });

    // Coverage toggle
    $(document).on('change', '.coverage-checkbox', function() {
        const prefix = $(this).data('prefix');
        const isChecked = $(this).prop('checked');
        updateCoverage(prefix, isChecked);
    });

    // Modal close handlers
    $('.modal-close, #prefix-cancel').on('click', function() {
        $('#prefix-modal').hide();
        resetPrefixForm();
    });

    // Save prefix
    $('#prefix-save').on('click', function() {
        savePrefix();
    });

    // Form validation
    $('#prefix-form input[required]').on('blur', function() {
        validateField($(this));
    });

    function openPrefixModal(action, data = {}) {
        const $modal = $('#prefix-modal');
        const $title = $('#prefix-modal-title');

        if (action === 'add') {
            $title.text('<?php esc_js_e('Add Network Prefix', 'phone-verification'); ?>');
            $('#prefix-action').val('add');
            resetPrefixForm();
        } else {
            $title.text('<?php esc_js_e('Edit Network Prefix', 'phone-verification'); ?>');
            $('#prefix-action').val('edit');
            $('#original-prefix').val(data.prefix);

            // Populate form
            $('#prefix-input').val(data.prefix);
            $('#country-input').val(data.country);
            $('#network-input').val(data.network);
            $('#min-length-input').val(data.minLength);
            $('#max-length-input').val(data.maxLength);
            $('#mcc-input').val(data.mcc);
            $('#mnc-input').val(data.mnc);
            $('#live-coverage-input').prop('checked', data.liveCoverage == '1');
        }

        $modal.show();
        $('#prefix-input').focus();
    }

    function resetPrefixForm() {
        $('#prefix-form')[0].reset();
        $('#prefix-form .field-error').text('');
        $('#prefix-form input').removeClass('is-invalid');
    }

    function validateField($field) {
        const value = $field.val().trim();
        const name = $field.attr('name');
        let isValid = true;
        let errorMsg = '';

        if ($field.prop('required') && !value) {
            isValid = false;
            errorMsg = '<?php esc_js_e('This field is required', 'phone-verification'); ?>';
        } else if (name === 'prefix' && value && !/^\d+$/.test(value)) {
            isValid = false;
            errorMsg = '<?php esc_js_e('Prefix must contain only numbers', 'phone-verification'); ?>';
        } else if ((name === 'min_length' || name === 'max_length') && value && (value < 8 || value > 15)) {
            isValid = false;
            errorMsg = '<?php esc_js_e('Length must be between 8 and 15', 'phone-verification'); ?>';
        }

        const $errorElement = $field.siblings('.field-error');
        if (isValid) {
            $field.removeClass('is-invalid');
            $errorElement.text('');
        } else {
            $field.addClass('is-invalid');
            $errorElement.text(errorMsg);
        }

        return isValid;
    }

    function savePrefix() {
        const $form = $('#prefix-form');
        const $saveBtn = $('#prefix-save');
        const $spinner = $saveBtn.find('.spinner');
        const action = $('#prefix-action').val();

        // Validate all required fields
        let isValid = true;
        $form.find('input[required]').each(function() {
            if (!validateField($(this))) {
                isValid = false;
            }
        });

        // Additional validation
        const minLength = parseInt($('#min-length-input').val());
        const maxLength = parseInt($('#max-length-input').val());

        if (minLength && maxLength && minLength > maxLength) {
            $('#max-length-input').addClass('is-invalid').siblings('.field-error').text('<?php esc_js_e('Max length must be greater than or equal to min length', 'phone-verification'); ?>');
            isValid = false;
        }

        if (!isValid) {
            return;
        }

        $saveBtn.prop('disabled', true);
        $spinner.show();

        const formData = {
            action: action === 'add' ? 'add_network_prefix' : 'update_network_prefix',
            prefix: $('#prefix-input').val().trim(),
            country_name: $('#country-input').val().trim(),
            network_name: $('#network-input').val().trim(),
            min_length: minLength,
            max_length: maxLength,
            mcc: $('#mcc-input').val().trim(),
            mnc: $('#mnc-input').val().trim(),
            live_coverage: $('#live-coverage-input').prop('checked') ? 1 : 0,
            nonce: '<?php echo wp_create_nonce('phone_verification_nonce'); ?>'
        };

        if (action === 'edit') {
            formData.original_prefix = $('#original-prefix').val();
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#prefix-modal').hide();
                    location.reload(); // Reload to update the table
                } else {
                    alert('<?php esc_js_e('Error:', 'phone-verification'); ?> ' + (response.data.message || '<?php esc_js_e('Unknown error occurred', 'phone-verification'); ?>'));
                }
            },
            error: function() {
                alert('<?php esc_js_e('Network error occurred. Please try again.', 'phone-verification'); ?>');
            },
            complete: function() {
                $saveBtn.prop('disabled', false);
                $spinner.hide();
            }
        });
    }

    function deletePrefix(prefix) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_network_prefix',
                prefix: prefix,
                nonce: '<?php echo wp_create_nonce('phone_verification_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $('tr[data-prefix="' + prefix + '"]').fadeOut(function() {
                        $(this).remove();
                    });
                } else {
                    alert('<?php esc_js_e('Error:', 'phone-verification'); ?> ' + (response.data.message || '<?php esc_js_e('Could not delete prefix', 'phone-verification'); ?>'));
                }
            },
            error: function() {
                alert('<?php esc_js_e('Network error occurred. Please try again.', 'phone-verification'); ?>');
            }
        });
    }

    function updateCoverage(prefix, liveCoverage) {
        const $toggle = $('.coverage-checkbox[data-prefix="' + prefix + '"]');
        const $text = $toggle.siblings('.coverage-text');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'update_prefix_coverage',
                prefix: prefix,
                live_coverage: liveCoverage ? 1 : 0,
                nonce: '<?php echo wp_create_nonce('phone_verification_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $text.text(liveCoverage ? '<?php esc_js_e('Yes', 'phone-verification'); ?>' : '<?php esc_js_e('No', 'phone-verification'); ?>');
                } else {
                    // Revert the toggle
                    $toggle.prop('checked', !liveCoverage);
                    alert('<?php esc_js_e('Error updating coverage status', 'phone-verification'); ?>');
                }
            },
            error: function() {
                // Revert the toggle
                $toggle.prop('checked', !liveCoverage);
                alert('<?php esc_js_e('Network error occurred. Please try again.', 'phone-verification'); ?>');
            }
        });
    }
});
</script>