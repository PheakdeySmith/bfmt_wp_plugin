/**
 * Phone Verification Plugin - Frontend JavaScript
 */

(function($) {
    'use strict';

    // Global variables
    let phoneValidationCache = {};
    let extractedPhoneNumbers = [];
    let batchVerificationResults = [];

    // Initialize when document is ready
    $(document).ready(function() {
        initializeFrontend();
    });

    function initializeFrontend() {
        // Initialize form handlers
        initializeFormHandlers();

        // Initialize button event handlers
        initializeButtonHandlers();

        // Initialize table if exists
        initializeTable();
    }


    function initializeFormHandlers() {
        // Phone number input validation
        $('#frontend_phone_number').on('input', function() {
            const phoneNumber = $(this).val().trim();
            const $input = $(this);

            // Clear previous error states
            $('#frontend-phone-error').text('');
            $input.removeClass('error');
            $('#frontend-verify-submit').prop('disabled', true);

            // Clear validation timeout
            clearTimeout($input.data('validationTimeout'));

            // Set new validation timeout
            const timeout = setTimeout(() => {
                if (phoneNumber) {
                    validateNetworkPrefix(phoneNumber);
                } else {
                    $('#frontend-phone-validation').hide();
                    $('#frontend-verify-submit').prop('disabled', true);
                }
            }, 500);

            $input.data('validationTimeout', timeout);
        });

        // Single verification form submit
        $('#frontend-verify-submit').on('click', function() {
            submitSingleVerification();
        });

        // Allow Enter key to submit
        $('#frontend_phone_number').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#frontend-verify-submit').click();
            }
        });

        // File upload handler for batch verification
        $('#frontend-file-upload').on('change', function() {
            handleFileUpload(this);
        });

        // Batch verification submit
        $('#frontend-batch-submit').on('click', function() {
            submitBatchVerification();
        });
    }

    function initializeButtonHandlers() {
        // Batch upload button handler - trigger file dialog directly
        $('#frontend-batch-btn').on('click', function() {
            $('#frontend-file-upload').click();
        });

        // Batch cancel handler
        $('#frontend-batch-cancel').on('click', function() {
            resetBatchForm();
        });

        // CSV download handler
        $('#download-csv-btn').on('click', function() {
            downloadCSV();
        });

        // Email validation for batch verification
        $('#frontend_batch_email').on('input', function() {
            validateEmail($(this).val().trim());
        });
    }

    function initializeTable() {
        
    }


    function resetForms() {
        // Reset single verification form
        $('#frontend-verify-form')[0].reset();
        $('#frontend-phone-validation').hide();
        $('#frontend-phone-error').text('');
        $('#frontend-verify-submit').prop('disabled', true);
        $('#single-verification-result').hide();
    }

    function resetBatchForm() {
        // Reset file input
        $('#frontend-file-upload').val('');
        $('#frontend-file-preview').hide();
        $('#frontend-batch-error').text('');
        $('#frontend-email-error').text('');
        $('#frontend_batch_email').val('');
        extractedPhoneNumbers = [];
    }

    async function validateNetworkPrefix(phoneNumber) {
        const cleanNumber = phoneNumber.replace(/[^0-9]/g, '');

        if (cleanNumber.length === 0) {
            $('#frontend-phone-validation').hide();
            return null;
        }

        // Check cache first
        if (phoneValidationCache[cleanNumber]) {
            displayNetworkValidationResult(phoneValidationCache[cleanNumber]);
            return phoneValidationCache[cleanNumber];
        }

        try {
            const response = await $.ajax({
                url: phoneVerificationFrontend.ajaxurl,
                type: 'POST',
                data: {
                    action: 'check_network_prefix',
                    phone_number: cleanNumber,
                    nonce: phoneVerificationFrontend.nonce
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
            $('#frontend-phone-validation').hide();
            return null;
        }
    }

    function displayNetworkValidationResult(result) {
        const $validationInfo = $('#frontend-phone-validation');
        const $validationAlert = $validationInfo.find('.validation-alert');
        const $detectedInfo = $('#frontend-detected-info');
        const $coverageStatus = $('#frontend-coverage-status');
        const $phoneInput = $('#frontend_phone_number');
        const $phoneError = $('#frontend-phone-error');
        const $verifyBtn = $('#frontend-verify-submit');

        if (result.success) {
            const countryName = result.country_name || 'Unknown';
            const networkName = result.network_name || 'Unknown';

            $detectedInfo.text(`${countryName} - ${networkName}`);

            if (result.partial_match) {
                $validationAlert.removeClass().addClass('validation-alert info');
                $coverageStatus.text(result.live_coverage ? 'Available' : 'Not Available');
                $phoneError.text('');
                $verifyBtn.prop('disabled', true);
            } else if (result.live_coverage) {
                $validationAlert.removeClass().addClass('validation-alert success');
                $coverageStatus.text('Available');
                $phoneError.text('');
                $verifyBtn.prop('disabled', false);
            } else {
                $validationAlert.removeClass().addClass('validation-alert warning');
                $coverageStatus.text('Not Available');
                $phoneError.text('');
                $verifyBtn.prop('disabled', false);
            }

            $validationInfo.show();
        } else {
            $validationAlert.removeClass().addClass('validation-alert');
            $detectedInfo.text('Network not found');
            $coverageStatus.text('Unknown');
            $validationInfo.show();
            $phoneError.text(result.error || 'Network prefix not found');
            $('#frontend_phone_number').addClass('error');
            $verifyBtn.prop('disabled', true);
        }
    }

    async function submitSingleVerification() {
        const phoneNumber = $('#frontend_phone_number').val().trim();
        const $submitBtn = $('#frontend-verify-submit');
        const $spinner = $submitBtn.find('.btn-spinner');

        if (!phoneNumber) {
            $('#frontend-phone-error').text('Please enter a phone number');
            return;
        }

        // Validate network prefix first
        const validationResult = await validateNetworkPrefix(phoneNumber);

        if (!validationResult || !validationResult.success) {
            $('#frontend-phone-error').text(validationResult?.error || 'Network prefix not found');
            $('#frontend_phone_number').addClass('error');
            showAlert('error', phoneVerificationFrontend.strings.error, validationResult?.error || 'Network prefix not found in database.');
            return;
        }

        if (validationResult.partial_match) {
            $('#frontend-phone-error').text(`Phone number is incomplete. Please enter ${validationResult.min_length}-${validationResult.max_length} digits.`);
            $('#frontend_phone_number').addClass('error');
            showAlert('error', 'Incomplete Number', `Please enter the complete phone number before verification.`);
            return;
        }

        // Start verification
        $submitBtn.prop('disabled', true);
        $spinner.show();

        try {
            const response = await $.ajax({
                url: phoneVerificationFrontend.ajaxurl,
                type: 'POST',
                data: {
                    action: 'verify_phone',
                    phone_number: phoneNumber,
                    data_freshness: '',
                    nonce: phoneVerificationFrontend.nonce
                }
            });

            if (response.success) {
                // Show single verification result in label format
                showSingleVerificationResult(response.data.data);

                if (response.data.cached) {
                    showAlert('info', phoneVerificationFrontend.strings.cached_result, 'Phone number verification retrieved from cache.');
                } else {
                    let successMessage = 'Phone number verified successfully!';
                    if (response.data.data.skip_reason === 'no_live_coverage') {
                        successMessage = 'Phone number processed - no live coverage available.';
                    }
                    showAlert('success', phoneVerificationFrontend.strings.success, successMessage);
                }
            } else {
                $('#frontend-phone-error').text(response.data.message || 'Verification failed');
                showAlert('error', phoneVerificationFrontend.strings.error, response.data.message || 'Phone number verification failed.');
            }
        } catch (error) {
            console.error('Verification error:', error);
            $('#frontend-phone-error').text('Network error. Please try again.');
            showAlert('error', phoneVerificationFrontend.strings.error, 'Network error occurred. Please try again.');
        } finally {
            $submitBtn.prop('disabled', false);
            $spinner.hide();
        }
    }

    function handleFileUpload(input) {
        const file = input.files[0];
        if (!file) {
            resetBatchForm();
            return;
        }

        $('#frontend-batch-error').text('');

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
                $('#frontend-batch-error').text('Error reading file: ' + error.message);
                $('#frontend-file-preview').hide();
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
            throw new Error('Excel processing library not available');
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
        const $batchError = $('#frontend-batch-error');
        const $filePreview = $('#frontend-file-preview');
        const $previewInfo = $('#frontend-preview-info');
        const $previewBody = $('#frontend-preview-body');

        if (phoneNumbers.length === 0) {
            $batchError.text('No valid phone numbers found in file');
            $filePreview.hide();
            return;
        }

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
            $('#frontend-batch-error').text('Please upload a file with phone numbers');
            return;
        }

        const email = $('#frontend_batch_email').val().trim();
        if (!email) {
            $('#frontend-email-error').text('Email address is required');
            $('#frontend_batch_email').focus();
            return;
        }

        if (!validateEmail(email)) {
            $('#frontend-email-error').text('Please enter a valid email address');
            $('#frontend_batch_email').focus();
            return;
        }

        const $submitBtn = $('#frontend-batch-submit');
        const $spinner = $submitBtn.find('.btn-spinner');

        $submitBtn.prop('disabled', true);
        $spinner.show();

        try {
            const response = await $.ajax({
                url: phoneVerificationFrontend.ajaxurl,
                type: 'POST',
                data: {
                    action: 'batch_verify',
                    phone_numbers: extractedPhoneNumbers,
                    email: email,
                    data_freshness: '',
                    nonce: phoneVerificationFrontend.nonce
                }
            });

            if (response.success) {
                resetBatchForm();
                showAlert('success', 'Batch Complete', `Successfully processed ${response.data.processed} phone numbers. Results have been sent to ${email}.`);

                // Store results for CSV download
                batchVerificationResults = response.data.data || [];

                // Show batch results table
                $('#batch-results-container').show();

                if (response.data.data && response.data.data.length > 0) {
                    clearBatchTable();
                    response.data.data.forEach(verification => {
                        updateBatchTableRow(verification);
                    });
                }
            } else {
                $('#frontend-batch-error').text(response.data.message || 'Batch verification failed');
                showAlert('error', phoneVerificationFrontend.strings.error, response.data.message || 'Batch verification failed.');
            }
        } catch (error) {
            console.error('Batch verification error:', error);
            $('#frontend-batch-error').text('Network error. Please try again.');
            showAlert('error', phoneVerificationFrontend.strings.error, 'Network error occurred during batch verification.');
        } finally {
            $submitBtn.prop('disabled', false);
            $spinner.hide();
        }
    }

    function showSingleVerificationResult(verificationData) {
        const phoneNumber = verificationData.phone_number || verificationData.number;
        const $resultContainer = $('#single-verification-result');
        const $resultPhone = $resultContainer.find('.result-phone');
        const $resultStatus = $resultContainer.find('.result-status');

        const statusClass = (verificationData.status === 0) ? 'success' : 'error';
        let statusText = 'Failed';
        if (verificationData.status === 0) statusText = 'Verified';
        else if (verificationData.status === 999) statusText = 'No Coverage';

        $resultPhone.text(phoneNumber);
        $resultStatus.removeClass().addClass(`status-badge status-${statusClass}`).text(statusText);
        $resultContainer.show();
    }

    function clearBatchTable() {
        const $tbody = $('#frontend-verification-table tbody');
        $tbody.empty();
    }

    function updateBatchTableRow(verificationData) {
        const phoneNumber = verificationData.phone_number || verificationData.number;
        const $table = $('#frontend-verification-table tbody');

        const statusClass = (verificationData.status === 0) ? 'success' : 'error';
        let statusText = 'Failed';
        if (verificationData.status === 0) statusText = 'Verified';
        else if (verificationData.status === 999) statusText = 'No Coverage';

        const liveCoverage = verificationData.live_coverage !== undefined ?
            verificationData.live_coverage :
            verificationData.prefix_info?.live_coverage;
        const liveCoverageClass = liveCoverage ? 'success' : 'warning';
        const liveCoverageText = liveCoverage ? 'Yes' : 'No';

        const formattedDate = new Date().toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });

        const newRow = `
            <tr data-phone="${phoneNumber}">
                <td class="phone-number">${phoneNumber}</td>
                <td>${verificationData.country_name || 'Cambodia'}</td>
                <td>${verificationData.network || verificationData.network_name || 'Unknown'}</td>
                <td><span class="status-badge status-${statusClass}">${statusText}</span></td>
                <td><span class="status-badge status-${liveCoverageClass}">${liveCoverageText}</span></td>
                <td class="date-verified">${formattedDate}</td>
            </tr>
        `;

        $table.append(newRow);
    }

    function validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const isValid = emailRegex.test(email);

        if (email && !isValid) {
            $('#frontend-email-error').text('Please enter a valid email address');
        } else {
            $('#frontend-email-error').text('');
        }

        return isValid;
    }

    function downloadCSV() {
        if (batchVerificationResults.length === 0) {
            showAlert('error', 'No Data', 'No verification results available to download.');
            return;
        }

        // Create CSV content
        const headers = ['Phone Number', 'Country', 'Network', 'Status', 'Live Coverage', 'Verified Date'];
        let csvContent = headers.join(',') + '\n';

        batchVerificationResults.forEach(verification => {
            const phoneNumber = verification.phone_number || verification.number;
            const country = (verification.country_name || 'Cambodia').replace(/,/g, ';');
            const network = (verification.network || verification.network_name || 'Unknown').replace(/,/g, ';');

            let status = 'Failed';
            if (verification.status === 0) status = 'Verified';
            else if (verification.status === 999) status = 'No Coverage';

            const liveCoverage = verification.live_coverage !== undefined ?
                verification.live_coverage :
                verification.prefix_info?.live_coverage;
            const liveCoverageText = liveCoverage ? 'Yes' : 'No';

            const formattedDate = new Date().toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });

            const row = [phoneNumber, country, network, status, liveCoverageText, formattedDate];
            csvContent += row.join(',') + '\n';
        });

        // Create and download file
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', `phone_verification_results_${new Date().toISOString().split('T')[0]}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        showAlert('success', 'Download Complete', 'CSV file has been downloaded successfully.');
    }


    function showAlert(type, title, message) {
        const $alertContainer = $('#frontend-alert');

        $alertContainer.removeClass('alert-success alert-error alert-info alert-warning').addClass(`alert-${type}`);
        $alertContainer.html(`
            <div>
                <strong>${title}!</strong> ${message}
            </div>
        `);

        $alertContainer.show();

        if (type === 'success') {
            setTimeout(() => {
                $alertContainer.hide();
            }, 7000);
        }
    }

})(jQuery);