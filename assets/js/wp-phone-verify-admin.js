jQuery(document).ready(function ($) {
  const testPhone = $('#test-phone');
  const sendTestButton = $('#send-test-otp');
  const verifySection = $('#verify-otp-section');
  const testOtp = $('#test-otp');
  const verifyTestButton = $('#verify-test-otp');
  const resultDiv = $('#test-result');

  sendTestButton.on('click', function () {
    const phone = testPhone.val();
    if (!phone) {
      showResult('Please enter a phone number.', 'error');
      return;
    }

    sendTestButton.prop('disabled', true).text('Sending...');

    $.ajax({
      url: ajaxurl,
      type: 'POST',
      data: {
        action: 'test_twilio_connection',
        nonce: wpPhoneVerifyObj.nonce,
        phone: phone,
      },
      success: function (response) {
        if (response.success) {
          verifySection.show();
          showResult('OTP sent successfully.', 'success');
        } else {
          showResult(response.data, 'error');
        }
      },
      error: function () {
        showResult('Error sending OTP.', 'error');
      },
      complete: function () {
        sendTestButton.prop('disabled', false).text('Send Test OTP');
      },
    });
  });

  verifyTestButton.on('click', function () {
    const phone = testPhone.val();
    const otp = testOtp.val();

    if (!otp) {
      showResult('Please enter the OTP.', 'error');
      return;
    }

    verifyTestButton.prop('disabled', true).text('Verifying...');

    $.ajax({
      url: ajaxurl,
      type: 'POST',
      data: {
        action: 'verify_test_otp',
        nonce: wpPhoneVerifyObj.nonce,
        phone: phone,
        otp: otp,
      },
      success: function (response) {
        if (response.success) {
          showResult('OTP verified successfully.', 'success');
          verifySection.hide();
          testOtp.val('');
        } else {
          showResult(response.data, 'error');
        }
      },
      error: function () {
        showResult('Error verifying OTP.', 'error');
      },
      complete: function () {
        verifyTestButton.prop('disabled', false).text('Verify Test OTP');
      },
    });
  });

  function showResult(message, type) {
    resultDiv.removeClass('success error').addClass(type).text(message).show();
  }
});
