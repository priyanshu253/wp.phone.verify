<?php
class WP_Phone_Verify_Shortcode {
    public function init() {
        add_shortcode('phone_verify', array($this, 'render_shortcode'));
        add_action('wp_ajax_send_otp', array($this, 'send_otp'));
        add_action('wp_ajax_nopriv_send_otp', array($this, 'send_otp'));
        add_action('wp_ajax_verify_otp', array($this, 'verify_otp'));
        add_action('wp_ajax_nopriv_verify_otp', array($this, 'verify_otp'));
    }

    public function render_shortcode($atts) {
        // Enqueue required scripts and styles
        wp_enqueue_script('wp-phone-verify');
        wp_enqueue_style('wp-phone-verify');

        $atts = shortcode_atts(array(
            'phone_field' => '',
            'form_id' => ''
        ), $atts);

        // Validate required attributes
        if (empty($atts['phone_field']) || empty($atts['form_id'])) {
            return '<div class="wp-phone-verify-error">Error: phone_field and form_id attributes are required.</div>';
        }

        ob_start();
        ?>
        <div class="wp-phone-verify-container" 
             data-phone-field="<?php echo esc_attr($atts['phone_field']); ?>"
             data-form-id="<?php echo esc_attr($atts['form_id']); ?>">
            
            <div class="wp-phone-verify-buttons">
                <button type="button" class="send-otp-button button">
                    <?php _e('Send OTP', 'wp-phone-verify'); ?>
                </button>
            </div>

            <div class="wp-phone-verify-otp-section" style="display: none;">
                <input type="text" class="otp-input" placeholder="<?php _e('Enter OTP', 'wp-phone-verify'); ?>" maxlength="6" pattern="\d{6}" title="<?php _e('Please enter 6 digits', 'wp-phone-verify'); ?>">
                <button type="button" class="verify-otp-button button">
                    <?php _e('Verify OTP', 'wp-phone-verify'); ?>
                </button>
            </div>

            <div class="wp-phone-verify-message"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function send_otp() {
        check_ajax_referer('wp-phone-verify-nonce', 'nonce');

        $phone = sanitize_text_field($_POST['phone']);
        
        // Validate phone number format
        if (!preg_match('/^\+?[1-9]\d{1,14}$/', $phone)) {
            wp_send_json_error('Invalid phone number format. Please include country code.');
            return;
        }
        
        // Check if an OTP was recently sent
        $throttle_key = 'wp_phone_verify_throttle_' . $phone;
        if (get_transient($throttle_key)) {
            wp_send_json_error('Please wait before requesting another OTP.');
            return;
        }
        
        // Generate random 6-digit OTP
        $otp = sprintf("%06d", mt_rand(100000, 999999));
        
        // Store OTP in transient for 5 minutes
        set_transient('wp_phone_verify_otp_' . $phone, $otp, 5 * MINUTE_IN_SECONDS);
        
        // Set throttle for 1 minute
        set_transient($throttle_key, true, MINUTE_IN_SECONDS);

        // Get site name
        $site_name = get_bloginfo('name');
        
        // Create message with site name
        $message = sprintf(
            __('Your OTP for phone verification on %s is: %s', 'wp-phone-verify'),
            $site_name,
            $otp
        );

        // Send OTP via Twilio
        $admin = new WP_Phone_Verify_Admin();
        $result = $admin->send_twilio_sms($phone, $message);

        if ($result['success']) {
            wp_send_json_success('OTP sent successfully');
        } else {
            delete_transient('wp_phone_verify_otp_' . $phone);
            delete_transient($throttle_key);
            wp_send_json_error($result['message']);
        }
    }

    public function verify_otp() {
        check_ajax_referer('wp-phone-verify-nonce', 'nonce');

        $phone = sanitize_text_field($_POST['phone']);
        $otp = sanitize_text_field($_POST['otp']);
        
        // Validate OTP format
        if (!preg_match('/^\d{6}$/', $otp)) {
            wp_send_json_error('Invalid OTP format');
            return;
        }
        
        $stored_otp = get_transient('wp_phone_verify_otp_' . $phone);
        
        if (!$stored_otp) {
            wp_send_json_error('OTP expired');
            return;
        }

        // Use hash_equals to prevent timing attacks
        if (hash_equals($stored_otp, $otp)) {
            delete_transient('wp_phone_verify_otp_' . $phone);
            wp_send_json_success('OTP verified successfully');
        } else {
            wp_send_json_error('Invalid OTP');
        }
    }
}