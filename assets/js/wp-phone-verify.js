jQuery(document).ready(function ($) {
  $('.wp-phone-verify-container').each(function () {
    const container = $(this);
    const phoneField = $(`#${container.data('phone-field')}`);
    const form = $(`#${container.data('form-id')}`);
    const sendButton = container.find('.send-otp-button');
    const verifyButton = container.find('.verify-otp-button');
    const otpSection = container.find('.wp-phone-verify-otp-section');
    const otpInput = container.find('.otp-input');
    const messageDiv = container.find('.wp-phone-verify-message');
    const submitButton = form.find('button[type="submit"], input[type="submit"]');

    // Capture submit button classes and apply to OTP buttons
    if (submitButton.length) {
      const submitClasses = submitButton.attr('class');
      if (submitClasses) {
        // Remove any position-specific classes
        const filteredClasses = submitClasses.split(' ').filter(cls => 
          !/(^|\s)(left|right|float|position|absolute|relative|fixed)(\s|$)/i.test(cls)
        ).join(' ');
        
        sendButton.attr('class', 'send-otp-button ' + filteredClasses);
        verifyButton.attr('class', 'verify-otp-button ' + filteredClasses);
      }
      
      // Initially hide submit button and disable it
      submitButton.hide().prop('disabled', true);
    }

    // Check if required elements exist
    if (!phoneField.length) {
      showMessage(
        `Phone field with ID "${container.data('phone-field')}" not found.`,
        'error'
      );
      return;
    }
    if (!form.length) {
      showMessage(
        `Form with ID "${container.data('form-id')}" not found.`,
        'error'
      );
      return;
    }

    // Format phone number as user types
    phoneField.on('input', function () {
      let phone = $(this).val().replace(/\D/g, '');
      if (phone.length > 0 && phone[0] !== '+') {
        phone = '+' + phone;
      }
      $(this).val(phone);

      // If phone number is changed after verification
      if (container.hasClass('verified')) {
        container.removeClass('verified');
        submitButton.hide().prop('disabled', true);
        sendButton.show();
        otpSection.hide();
        otpInput.val('');
        messageDiv.hide();
      }
    });

    // Only allow digits in OTP input
    otpInput.on('input', function () {
      $(this).val($(this).val().replace(/\D/g, ''));
      verifyButton.prop('disabled', $(this).val().length !== 6);
    });

    // Disable form submission until verified
    form.on('submit', function (e) {
      if (!container.hasClass('verified')) {
        e.preventDefault();
        showMessage('Please verify your phone number first.', 'error');
        sendButton.focus();
        return false;
      }
    });

    sendButton.on('click', function () {
      const phone = phoneField.val();
      if (!phone) {
        showMessage('Please enter a phone number.', 'error');
        phoneField.focus();
        return;
      }

      // Basic phone number validation
      if (!/^\+?[1-9]\d{1,14}$/.test(phone)) {
        showMessage(
          'Invalid phone number format. Please include country code.',
          'error'
        );
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
          phone: phone,
        },
        success: function (response) {
          if (response.success) {
            otpSection.show();
            otpInput.val('').focus();
            verifyButton.prop('disabled', true);
            showMessage(
              'OTP sent successfully. Please check your phone.',
              'success'
            );
          } else {
            showMessage(response.data || 'Error sending OTP.', 'error');
          }
        },
        error: function () {
          showMessage('Network error. Please try again.', 'error');
        },
        complete: function () {
          sendButton.prop('disabled', false).text('Send OTP');
        },
      });
    });

    verifyButton.on('click', function () {
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
          otp: otp,
        },
        success: function (response) {
          if (response.success) {
            container.addClass('verified');
            showMessage('Phone number verified successfully.', 'success');
            otpSection.hide();
            sendButton.hide();
            phoneField.prop('readonly', true);
            if (submitButton.length) {
              submitButton.show().prop('disabled', false);
            }
          } else {
            showMessage(response.data || 'Error verifying OTP.', 'error');
            otpInput.val('').focus();
          }
        },
        error: function () {
          showMessage('Network error. Please try again.', 'error');
        },
        complete: function () {
          verifyButton.prop('disabled', false).text('Verify OTP');
        },
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