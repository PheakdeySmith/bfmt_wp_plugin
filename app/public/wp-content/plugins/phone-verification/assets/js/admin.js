/**
 * Phone Verification Plugin - Admin JavaScript
 */

(function($) {
    'use strict';

    // Global variables
    let phoneValidationCache = {};
    let extractedPhoneNumbers = [];
    let verificationTable;

    // Initialize when document is ready
    $(document).ready(function() {
        initializeAdmin();
    });

    function initializeAdmin() {
        // Initialize DataTable
        initializeDataTable();

        // Initialize modals
        initializeModals();

        // Initialize form handlers
        initializeFormHandlers();

        // Initialize button event handlers
        initializeButtonHandlers();
    }

    function initializeDataTable() {
        if ($.fn.DataTable && $('#phone-verification-table').length) {
            verificationTable = $('#phone-verification-table').DataTable({
                pageLength: 25,
                order: [[ 11, "desc" ]], // Sort by verification date
                columnDefs: [
                    { orderable: false, targets: [5, 7, 8, 9] } // Disable sorting for badge columns
                ],
                responsive: true,
                language: {
                    emptyTable: "No verification results found. Start by verifying some phone numbers!",
                    zeroRecords: "No matching records found",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)"
                }
            });
        }
    }

    function initializeModals() {
        // Single verification modal
        $('#verify-single-btn').on('click', function() {
            $('#single-verify-modal').show();
            $('#phone_number').focus();
        });

        // Batch verification modal
        $('#batch-verify-btn').on('click', function() {
            $('#batch-verify-modal').show();
        });

        // Modal close handlers
        $('.modal-close, #single-verify-cancel, #batch-verify-cancel').on('click', function() {
            closeAllModals();
        });

        // Close modal when clicking outside
        $('.phone-verification-modal').on('click', function(e) {
            if (e.target === this) {
                closeAllModals();
            }
        });

        // Escape key to close modals
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAllModals();
            }
        });
    }

    function initializeFormHandlers() {
        // Phone number input validation
        $('#phone_number').on('input', function() {
            const phoneNumber = $(this).val().trim();
            $('#phone-error').text('');
            $('#single-verify-submit').prop('disabled', true).attr('title', 'Validating phone number...');

            // Clear validation timeout
            clearTimeout($(this).data('validationTimeout'));

            // Set new validation timeout
            const timeout = setTimeout(() => {
                if (phoneNumber) {
                    validateNetworkPrefix(phoneNumber);
                } else {
                    $('#phone-validation-info').hide();
                    $('#single-verify-submit').prop('disabled', true).attr('title', 'Enter a phone number to enable verification');
                }
            }, 500);

            $(this).data('validationTimeout', timeout);
        });

        // Single verification form submit
        $('#single-verify-submit').on('click', function() {
            submitSingleVerification();
        });

        // Allow Enter key to submit
        $('#phone_number').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#single-verify-submit').click();
            }
        });

        // File upload handler
        $('#file-upload').on('change', function() {
            handleFileUpload(this);
        });

        // Batch verification submit
        $('#batch-verify-submit').on('click', function() {
            submitBatchVerification();
        });
    }

    function initializeButtonHandlers() {
        // Filter button (placeholder for future implementation)
        $('#filter-btn').on('click', function() {
            showAlert('info', 'Filter', 'Filter functionality will be available in the next update.');
        });
    }

    function closeAllModals() {
        $('.phone-verification-modal').hide();
        resetForms();
    }

    function resetForms() {
        // Reset single verification form
        $('#single-verify-form')[0].reset();
        $('#phone-validation-info').hide();
        $('#phone-error').text('');
        $('#single-verify-submit').prop('disabled', true).attr('title', 'Enter a phone number to enable verification');

        // Reset batch verification form
        $('#batch-verify-modal form')[0]?.reset();
        $('#file-preview').hide();
        $('#batch-error').text('');
        extractedPhoneNumbers = [];
    }

    async function validateNetworkPrefix(phoneNumber) {
        const cleanNumber = phoneNumber.replace(/[^0-9]/g, '');

        if (cleanNumber.length === 0) {
            $('#phone-validation-info').hide();
            $('#phone_number').removeClass('is-valid is-invalid');
            return null;
        }

        // Check cache first
        if (phoneValidationCache[cleanNumber]) {
            displayNetworkValidationResult(phoneValidationCache[cleanNumber]);
            return phoneValidationCache[cleanNumber];
        }

        try {
            const response = await $.ajax({
                url: phoneVerificationAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'check_network_prefix',
                    phone_number: cleanNumber,
                    nonce: phoneVerificationAjax.nonce
                }
            });

            if (response.success) {
                phoneValidationCache[cleanNumber] = response.data;
                displayNetworkValidationResult(response.data);
                return response.data;
            } else {
                displayNetworkValidationResult({ success: false, error: response.data.message || 'Validation failed' });
                return null;
            }
        } catch (error) {
            console.error('Network prefix validation error:', error);
            $('#phone-validation-info').hide();
            return null;
        }
    }

    function displayNetworkValidationResult(result) {
        const $validationInfo = $('#phone-validation-info');
        const $validationAlert = $validationInfo.find('.validation-alert');
        const $detectedInfo = $('#detected-info');
        const $coverageStatus = $('#coverage-status');
        const $phoneInput = $('#phone_number');
        const $phoneError = $('#phone-error');
        const $verifyBtn = $('#single-verify-submit');

        if (result.success) {
            const countryName = result.country_name || 'Unknown';
            const networkName = result.network_name || 'Unknown';

            $detectedInfo.text(`${countryName} - ${networkName} (${result.prefix})`);

            if (result.partial_match) {
                $validationAlert.removeClass().addClass('validation-alert alert-info');
                $coverageStatus.text(result.live_coverage ? 'Available' : 'Not Available');
                $phoneInput.removeClass('is-invalid is-valid');
                $phoneError.text('');
                $verifyBtn.prop('disabled', true).attr('title', 'Complete the phone number to enable verification');
            } else if (result.live_coverage) {
                $validationAlert.removeClass().addClass('validation-alert alert-success');
                $coverageStatus.text('Available - API call will be made');
                $phoneInput.removeClass('is-invalid').addClass('is-valid');
                $phoneError.text('');
                $verifyBtn.prop('disabled', false).attr('title', '');
            } else {
                $validationAlert.removeClass().addClass('validation-alert alert-warning');
                $coverageStatus.text('Not Available - API call will be skipped');
                $phoneInput.removeClass('is-invalid').addClass('is-valid');
                $phoneError.text('');
                $verifyBtn.prop('disabled', false).attr('title', '');
            }

            $validationInfo.show();
        } else {
            $validationAlert.removeClass().addClass('validation-alert alert-secondary');
            $detectedInfo.text('Network prefix not found in database');
            $coverageStatus.text('Unknown');
            $validationInfo.show();
            $phoneInput.removeClass('is-valid').addClass('is-invalid');
            $phoneError.text(result.error || 'Network prefix not found');
            $verifyBtn.prop('disabled', true).attr('title', 'Enter a valid phone number to enable verification');
        }
    }

    async function submitSingleVerification() {
        const phoneNumber = $('#phone_number').val().trim();
        const dataFreshness = $('#data_freshness').val();
        const $submitBtn = $('#single-verify-submit');
        const $spinner = $submitBtn.find('.spinner');

        if (!phoneNumber) {
            $('#phone_number').addClass('is-invalid');
            $('#phone-error').text('Please enter a phone number');
            return;
        }

        // Validate network prefix first
        const validationResult = await validateNetworkPrefix(phoneNumber);

        if (!validationResult || !validationResult.success) {
            $('#phone_number').addClass('is-invalid');
            $('#phone-error').text(validationResult?.error || 'Network prefix not found');
            showAlert('error', 'Validation Failed', validationResult?.error || 'Network prefix not found in database.');
            return;
        }

        if (validationResult.partial_match) {
            $('#phone_number').addClass('is-invalid');
            $('#phone-error').text(`Phone number is incomplete. Please enter ${validationResult.min_length}-${validationResult.max_length} digits.`);
            showAlert('warning', 'Incomplete Number', `Please enter the complete phone number (${validationResult.min_length}-${validationResult.max_length} digits) before verification.`);
            return;
        }

        // Start verification
        $submitBtn.prop('disabled', true);
        $spinner.show();
        $submitBtn.find('.button-text').text(phoneVerificationAjax.strings.verifying);

        try {
            const response = await $.ajax({
                url: phoneVerificationAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'verify_phone',
                    phone_number: phoneNumber,
                    data_freshness: dataFreshness,
                    nonce: phoneVerificationAjax.nonce
                }
            });

            if (response.success) {
                closeAllModals();

                if (response.data.cached) {
                    showAlert('info', phoneVerificationAjax.strings.cached_result, 'Phone number verification retrieved from cache.');
                    updateTableRow(response.data.data);
                    highlightRow(response.data.data.phone_number || response.data.data.number);
                } else {
                    let successMessage = 'Phone number verified successfully!';
                    if (response.data.data.skip_reason === 'no_live_coverage') {
                        successMessage = 'Phone number processed - no live coverage, API call skipped to save costs.';
                    }
                    showAlert('success', phoneVerificationAjax.strings.success, successMessage);
                    updateTableRow(response.data.data);
                }
            } else {
                $('#phone_number').addClass('is-invalid');
                $('#phone-error').text(response.data.message || 'Verification failed');
                showAlert('error', phoneVerificationAjax.strings.error, response.data.message || 'Phone number verification failed.');
            }
        } catch (error) {
            console.error('Verification error:', error);
            $('#phone_number').addClass('is-invalid');
            $('#phone-error').text('Network error. Please try again.');
            showAlert('error', phoneVerificationAjax.strings.error, 'Network error occurred. Please try again.');
        } finally {
            $submitBtn.prop('disabled', false);
            $spinner.hide();
            $submitBtn.find('.button-text').text('Verify');
        }
    }

    function handleFileUpload(input) {
        const file = input.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function(event) {
            try {
                if (file.name.endsWith('.csv')) {
                    extractedPhoneNumbers = parseCSV(event.target.result);
                } else {
                    extractedPhoneNumbers = parseExcel(event.target.result);
                }
                showFilePreview(extractedPhoneNumbers);
            } catch (error) {
                $(input).addClass('is-invalid');
                $('#batch-error').text('Error reading file: ' + error.message);
            }
        };

        if (file.name.endsWith('.csv')) {
            reader.readAsText(file);
        } else {
            reader.readAsArrayBuffer(file);
        }
    }

    function parseCSV(csvText) {
        const lines = csvText.split('\n');
        const phoneNumbers = [];

        for (let line of lines) {
            const columns = line.split(',');
            if (columns[0] && columns[0].trim()) {
                const phone = columns[0].trim().replace(/[^\d+]/g, '');
                if (phone && phone.length >= 8) {
                    phoneNumbers.push(phone);
                }
            }
        }
        return phoneNumbers;
    }

    function parseExcel(arrayBuffer) {
        if (typeof XLSX === 'undefined') {
            throw new Error('XLSX library not loaded');
        }

        const workbook = XLSX.read(arrayBuffer, {type: 'array'});
        const firstSheetName = workbook.SheetNames[0];
        const worksheet = workbook.Sheets[firstSheetName];
        const jsonData = XLSX.utils.sheet_to_json(worksheet, {header: 1});
        const phoneNumbers = [];

        for (let row of jsonData) {
            if (row[0]) {
                const phone = String(row[0]).trim().replace(/[^\d+]/g, '');
                if (phone && phone.length >= 8) {
                    phoneNumbers.push(phone);
                }
            }
        }

        return phoneNumbers;
    }

    function showFilePreview(phoneNumbers) {
        const $fileUpload = $('#file-upload');
        const $batchError = $('#batch-error');
        const $filePreview = $('#file-preview');
        const $previewInfo = $('#preview-info');
        const $previewBody = $('#preview-body');

        if (phoneNumbers.length === 0) {
            $fileUpload.addClass('is-invalid');
            $batchError.text('No valid phone numbers found in file');
            $filePreview.hide();
            return;
        }

        $fileUpload.removeClass('is-invalid');
        $batchError.text('');
        $previewInfo.text(`Found ${phoneNumbers.length} phone numbers`);
        $previewBody.empty();

        phoneNumbers.slice(0, 10).forEach(phone => {
            $previewBody.append(`<tr><td>${phone}</td></tr>`);
        });

        if (phoneNumbers.length > 10) {
            $previewBody.append(`<tr><td><em>... and ${phoneNumbers.length - 10} more</em></td></tr>`);
        }

        $filePreview.show();
    }

    async function submitBatchVerification() {
        if (extractedPhoneNumbers.length === 0) {
            $('#file-upload').addClass('is-invalid');
            $('#batch-error').text('Please upload a file with phone numbers');
            return;
        }

        const $submitBtn = $('#batch-verify-submit');
        const $spinner = $submitBtn.find('.spinner');
        const dataFreshness = $('#batch_data_freshness').val();

        $submitBtn.prop('disabled', true);
        $spinner.show();
        $submitBtn.find('.button-text').text(phoneVerificationAjax.strings.verifying);

        try {
            const response = await $.ajax({
                url: phoneVerificationAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'batch_verify',
                    phone_numbers: extractedPhoneNumbers,
                    data_freshness: dataFreshness,
                    nonce: phoneVerificationAjax.nonce
                }
            });

            if (response.success) {
                closeAllModals();

                // Show batch results
                showBatchResults(response.data);

                showAlert('success', phoneVerificationAjax.strings.batch_complete, `Successfully processed ${response.data.processed} phone numbers.`);

                // Update table with new results
                if (response.data.data && response.data.data.length > 0) {
                    response.data.data.forEach(verification => {
                        if (verification.source !== 'cache' && verification.skip_reason !== 'no_live_coverage') {
                            updateTableRow(verification);
                        }
                    });
                }
            } else {
                $('#file-upload').addClass('is-invalid');
                $('#batch-error').text(response.data.message || 'Batch verification failed');
                showAlert('error', phoneVerificationAjax.strings.error, response.data.message || 'Batch verification failed.');
            }
        } catch (error) {
            console.error('Batch verification error:', error);
            $('#file-upload').addClass('is-invalid');
            $('#batch-error').text('Network error. Please try again.');
            showAlert('error', phoneVerificationAjax.strings.error, 'Network error occurred during batch verification.');
        } finally {
            $submitBtn.prop('disabled', false);
            $spinner.hide();
            $submitBtn.find('.button-text').text('Verify All');
        }
    }

    function updateTableRow(verificationData) {
        if (!verificationTable) return;

        const phoneNumber = verificationData.phone_number || verificationData.number;

        // Skip adding rows for numbers without live coverage
        if (verificationData.skip_reason === 'no_live_coverage') {
            return;
        }

        const now = new Date();
        const formattedDate = now.getFullYear() + '-' +
            String(now.getMonth() + 1).padStart(2, '0') + '-' +
            String(now.getDate()).padStart(2, '0') + ' ' +
            String(now.getHours()).padStart(2, '0') + ':' +
            String(now.getMinutes()).padStart(2, '0');

        const statusClass = (verificationData.status === 0) ? 'success' : 'danger';
        const statusText = verificationData.status_message || 'Unknown';
        const portedClass = verificationData.ported ? 'success' : 'secondary';
        const portedText = verificationData.ported ? 'Yes' : 'No';

        let liveCoverageClass = 'secondary';
        let liveCoverageText = 'Unknown';

        // Check multiple possible sources for live coverage data
        if (verificationData.live_coverage !== undefined) {
            liveCoverageClass = verificationData.live_coverage ? 'success' : 'danger';
            liveCoverageText = verificationData.live_coverage ? 'Yes' : 'No';
        } else if (verificationData.prefix_info && verificationData.prefix_info.live_coverage !== undefined) {
            liveCoverageClass = verificationData.prefix_info.live_coverage ? 'success' : 'danger';
            liveCoverageText = verificationData.prefix_info.live_coverage ? 'Yes' : 'No';
        } else if (verificationData.skip_reason === 'no_live_coverage') {
            liveCoverageClass = 'danger';
            liveCoverageText = 'No';
        }

        const presentValue = verificationData.present ? verificationData.present.toLowerCase() : 'na';
        const presentText = presentValue.charAt(0).toUpperCase() + presentValue.slice(1);
        const presentClass = presentValue === 'yes' ? 'success' : 'secondary';

        const rowData = [
            phoneNumber,
            verificationData.country_name || 'Unknown',
            (verificationData.min_length || 'N/A') + '/' + (verificationData.max_length || 'N/A'),
            verificationData.network_name || verificationData.network || 'Unknown',
            (verificationData.mcc || '') + '/' + (verificationData.mnc || ''),
            `<span class="badge badge-${liveCoverageClass}">${liveCoverageText}</span>`,
            verificationData.type ? verificationData.type.charAt(0).toUpperCase() + verificationData.type.slice(1) : 'Unknown',
            `<span class="badge badge-${statusClass}">${statusText}</span>`,
            `<span class="badge badge-${portedClass}">${portedText}</span>`,
            `<span class="badge badge-${presentClass}">${presentText}</span>`,
            verificationData.trxid || 'N/A',
            formattedDate
        ];

        // Check if row already exists
        const existingRowIndex = verificationTable.data().toArray().findIndex(row => row[0] === phoneNumber);

        if (existingRowIndex !== -1) {
            // Update existing row
            verificationTable.row(existingRowIndex).data(rowData).draw(false);

            // Highlight updated row
            const $updatedRow = $(verificationTable.row(existingRowIndex).node());
            $updatedRow.css('background-color', '#fff3cd');
            setTimeout(() => {
                $updatedRow.css('background-color', '');
            }, 3000);
        } else {
            // Add new row
            const newRow = verificationTable.row.add(rowData).draw(false);
            const $newNode = $(newRow.node());
            $newNode.attr('data-phone', phoneNumber);

            // Highlight new row
            $newNode.css('background-color', '#e8f5e8');
            setTimeout(() => {
                $newNode.css('background-color', '');
            }, 3000);
        }
    }

    function highlightRow(phoneNumber) {
        if (!verificationTable) return;

        verificationTable.rows().every(function(rowIdx, tableLoop, rowLoop) {
            const data = this.data();
            if (data && data[0] === phoneNumber) {
                const $rowNode = $(this.node());
                $rowNode.css('background-color', '#cce5ff');
                setTimeout(() => {
                    $rowNode.css('background-color', '');
                }, 2000);
                return false;
            }
        });
    }

    function showBatchResults(data) {
        // Remove existing results card
        $('#batch-results-container').empty();

        const stats = data.statistics || {};
        const liveCoverageCount = data.live_coverage_count || 0;
        const noCoverageCount = data.no_coverage_count || 0;
        const errorCount = data.error_count || 0;
        const cacheHits = stats.cache_hits || 0;
        const dbHits = stats.database_hits || 0;
        const apiCalls = stats.api_calls || 0;
        const totalCached = cacheHits + dbHits;

        const liveCoveragePercent = data.processed > 0 ? Math.round((liveCoverageCount / data.processed) * 100) : 0;
        const cachePercent = data.processed > 0 ? Math.round((totalCached / data.processed) * 100) : 0;

        const resultsHtml = `
            <div class="batch-results-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h5>Batch Verification Results</h5>
                    <button type="button" class="button" onclick="this.parentElement.parentElement.parentElement.remove()">×</button>
                </div>

                <div class="batch-stats-row">
                    <div class="batch-stat-item">
                        <div class="batch-stat-value">${data.processed}</div>
                        <div class="batch-stat-label">Total Processed</div>
                    </div>
                    <div class="batch-stat-item">
                        <div class="batch-stat-value" style="color: #46b450;">${liveCoverageCount}</div>
                        <div class="batch-stat-label">Live Coverage</div>
                    </div>
                    <div class="batch-stat-item">
                        <div class="batch-stat-value" style="color: #ffb900;">${noCoverageCount}</div>
                        <div class="batch-stat-label">No Coverage</div>
                    </div>
                    <div class="batch-stat-item">
                        <div class="batch-stat-value" style="color: ${errorCount > 0 ? '#dc3232' : '#82878c'};">${errorCount}</div>
                        <div class="batch-stat-label">Errors</div>
                    </div>
                </div>

                <div class="performance-info">
                    <div class="performance-grid">
                        <div class="performance-item">
                            <h6>Cache Performance</h6>
                            <div class="performance-details">
                                <div>${totalCached} from cache/DB</div>
                                <div>${cachePercent}% cache hit rate</div>
                            </div>
                        </div>
                        <div class="performance-item">
                            <h6>API Efficiency</h6>
                            <div class="performance-details">
                                <div>${apiCalls} new API calls</div>
                                <div>${liveCoveragePercent}% coverage rate</div>
                            </div>
                        </div>
                    </div>
                    ${data.cache_message ? `<div style="margin-top: 15px; font-size: 13px; color: #666; font-style: italic;">${data.cache_message}</div>` : ''}
                </div>
            </div>
        `;

        $('#batch-results-container').html(resultsHtml);
    }

    function showAlert(type, title, message) {
        const $alertContainer = $('#phone-verification-alert');

        $alertContainer.removeClass('success error info warning').addClass(type);
        $alertContainer.html(`
            <div>
                <strong>${title}!</strong> ${message}
            </div>
            <button type="button" class="alert-close" onclick="$(this).parent().hide()">&times;</button>
        `);

        $alertContainer.show();

        if (type === 'success') {
            setTimeout(() => {
                $alertContainer.hide();
            }, 8000);
        }
    }

})(jQuery);