jQuery(document).ready(function($) {
    $('.wp-phone-verify-container').each(function() {
        const container = $(this);
        const phoneField = $(`#${container.data('phone-field')}`);
        const form = $(`#${container.data('form-id')}`);
        const sendButton = container.find('.send-otp-button');
        const verifyButton = container.find('.verify-otp-button');
        const otpSection = container.find('.wp-phone-verify-otp-section');
        const otpInput = container.find('.otp-input');
        const messageDiv = container.find('.wp-phone-verify-message');

        // Check if required elements exist
        if (!phoneField.length) {
            showMessage(`Phone field with ID "${container.data('phone-field')}" not found.`, 'error');
            return;
        }
        if (!form.length) {
            showMessage(`Form with ID "${container.data('form-id')}" not found.`, 'error');
            return;
        }

        // Format phone number as user types
        phoneField.on('input', function() {
            let phone = $(this).val().replace(/\D/g, '');
            if (phone.length > 0 && phone[0] !== '+') {
                phone = '+' + phone;
            }
            $(this).val(phone);
        });

        // Only allow digits in OTP input
        otpInput.on('input', function() {
            $(this).val($(this).val().replace(/\D/g, ''));
        });

        // Disable form submission until verified
        form.on('submit', function(e) {
            if (!container.hasClass('verified')) {
                e.preventDefault();
                showMessage('Please verify your phone number first.', 'error');
                sendButton.focus();
                return false;
            }
        });

        // Enable verify button when 6 digits are entered
        otpInput.on('input', function() {
            verifyButton.prop('disabled', $(this).val().length !== 6);
        });

        sendButton.on('click', function() {
            const phone = phoneField.val();
            if (!phone) {
                showMessage('Please enter a phone number.', 'error');
                phoneField.focus();
                return;
            }

            // Basic phone number validation
            if (!/^\+?[1-9]\d{1,14}$/.test(phone)) {
                showMessage('Invalid phone number format. Please include country code.', 'error');
                phoneField.focus();
                return;
            }

            sendButton.prop('disabled', true).text('Sending...');
            messageDiv.hide();

            $.ajax({
                url: wpPhoneVerifyObj.ajaxurl,
                type: 'POST',
                data: {
                    action: 'send_otp',
                    nonce: wpPhoneVerifyObj.nonce,
                    phone: phone
                },
                success: function(response) {
                    if (response.success) {
                        otpSection.show();
                        otpInput.val('').focus();
                        verifyButton.prop('disabled', true);
                        showMessage('OTP sent successfully. Please check your phone.', 'success');
                    } else {
                        showMessage(response.data, 'error');
                    }
                },
                error: function(xhr) {
                    showMessage('Network error. Please try again.', 'error');
                },
                complete: function() {
                    sendButton.prop('disabled', false).text('Send OTP');
                }
            });
        });

        verifyButton.on('click', function() {
            const phone = phoneField.val();
            const otp = otpInput.val();

            if (!otp) {
                showMessage('Please enter the OTP.', 'error');
                otpInput.focus();
                return;
            }

            if (!/^\d{6}$/.test(otp)) {
                showMessage('Please enter a valid 6-digit OTP.', 'error');
                otpInput.focus();
                return;
            }

            verifyButton.prop('disabled', true).text('Verifying...');
            messageDiv.hide();

            $.ajax({
                url: wpPhoneVerifyObj.ajaxurl,
                type: 'POST',
                data: {
                    action: 'verify_otp',
                    nonce: wpPhoneVerifyObj.nonce,
                    phone: phone,
                    otp: otp
                },
                success: function(response) {
                    if (response.success) {
                        container.addClass('verified');
                        showMessage('Phone number verified successfully.', 'success');
                        otpSection.hide();
                        sendButton.hide();
                        phoneField.prop('readonly', true);
                    } else {
                        showMessage(response.data, 'error');
                        otpInput.val('').focus();
                    }
                },
                error: function(xhr) {
                    showMessage('Network error. Please try again.', 'error');
                },
                complete: function() {
                    verifyButton.prop('disabled', false).text('Verify OTP');
                }
            });
        });

        function showMessage(message, type) {
            messageDiv
                .removeClass('success error')
                .addClass(type)
                .text(message)
                .show();
        }
    });
});