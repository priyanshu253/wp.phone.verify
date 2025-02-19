# WP Phone Verify

A WordPress plugin for phone number verification using Twilio OTP integration. Add phone verification to any form using simple shortcodes.

## Features

### 1. Easy Integration
- Simple shortcode system to add phone verification to any form
- Works with any custom form without modifying its structure
- Responsive design that works on all devices

### 2. Secure Verification Process
- OTP (One-Time Password) verification via SMS
- Rate limiting to prevent spam
- Secure OTP storage using WordPress transients
- Protection against timing attacks
- Input validation and sanitization

### 3. Admin Settings
- Dedicated settings page in WordPress admin
- Easy configuration of Twilio credentials
- Test interface to verify Twilio integration
- Secure storage of API credentials

### 4. User Experience
- Real-time phone number formatting
- Clear error messages
- Loading indicators for all actions
- Mobile-responsive design
- Automatic form submission prevention until verification
- Smooth transitions and animations

## Installation

1. Upload the `wp-phone-verify` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > Phone Verification to configure your Twilio credentials

## Configuration

1. Sign up for a Twilio account at https://www.twilio.com
2. Get your Account SID, Auth Token, and Twilio Phone Number
3. Enter these details in the plugin settings page
4. Test the integration using the test interface provided

## Usage

Add phone verification to any form using the shortcode:

```php
[phone_verify phone_field="phone_input_id" form_id="your_form_id"]
```

Parameters:
- `phone_field`: ID of your form's phone input field
- `form_id`: ID of the form element

Example:
```html
<form id="registration_form">
    <input type="tel" id="user_phone" name="phone" />
    [phone_verify phone_field="user_phone" form_id="registration_form"]
    <button type="submit">Register</button>
</form>
```

## Security Features

- OTP Throttling: Prevents spam by limiting OTP requests
- Secure OTP Storage: Uses WordPress transients with expiration
- Input Validation: Validates phone numbers and OTP format
- XSS Prevention: Proper escaping of output
- CSRF Protection: Nonce verification for all requests
- Timing Attack Prevention: Secure string comparison

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- Twilio account with SMS capabilities

## Support

For bug reports and feature requests, please use the GitHub issues page.

## License

This plugin is licensed under the GPL v2 or later.

## Author

Created by Priyanshu Sharan

## Changelog

### 1.0.0
- Initial release
- Basic phone verification functionality
- Admin settings panel
- Twilio integration
- Shortcode system