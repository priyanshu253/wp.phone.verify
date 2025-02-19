<?php
class WP_Phone_Verify_Admin {
    private $option_name = 'wp_phone_verify_settings';

    public function init() {
        add_action('admin_menu', array($this, 'add_menu_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_test_twilio_connection', array($this, 'test_twilio_connection'));
        add_action('wp_ajax_verify_test_otp', array($this, 'verify_test_otp'));
    }

    public function add_menu_page() {
        add_options_page(
            __('Phone Verification Settings', 'wp-phone-verify'),
            __('Phone Verification', 'wp-phone-verify'),
            'manage_options',
            'wp-phone-verify',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings() {
        register_setting($this->option_name, $this->option_name);

        add_settings_section(
            'twilio_settings',
            __('Twilio Settings', 'wp-phone-verify'),
            array($this, 'render_section_info'),
            'wp-phone-verify'
        );

        add_settings_field(
            'twilio_sid',
            __('Twilio Account SID', 'wp-phone-verify'),
            array($this, 'render_text_field'),
            'wp-phone-verify',
            'twilio_settings',
            array('field' => 'twilio_sid')
        );

        add_settings_field(
            'twilio_auth_token',
            __('Twilio Auth Token', 'wp-phone-verify'),
            array($this, 'render_text_field'),
            'wp-phone-verify',
            'twilio_settings',
            array('field' => 'twilio_auth_token')
        );

        add_settings_field(
            'twilio_phone_number',
            __('Twilio Phone Number', 'wp-phone-verify'),
            array($this, 'render_text_field'),
            'wp-phone-verify',
            'twilio_settings',
            array('field' => 'twilio_phone_number')
        );
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        wp_enqueue_script('wp-phone-verify-admin');
        wp_enqueue_style('wp-phone-verify-admin');

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields($this->option_name);
                do_settings_sections('wp-phone-verify');
                submit_button();
                ?>
            </form>

            <div class="twilio-test-section">
                <h2><?php _e('Test Twilio Integration', 'wp-phone-verify'); ?></h2>
                <div class="test-form">
                    <input type="tel" id="test-phone" placeholder="Enter phone number">
                    <button type="button" id="send-test-otp" class="button button-primary">
                        <?php _e('Send Test OTP', 'wp-phone-verify'); ?>
                    </button>
                    
                    <div id="verify-otp-section" style="display: none;">
                        <input type="text" id="test-otp" placeholder="Enter OTP">
                        <button type="button" id="verify-test-otp" class="button button-primary">
                            <?php _e('Verify Test OTP', 'wp-phone-verify'); ?>
                        </button>
                    </div>
                    
                    <div id="test-result"></div>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_section_info() {
        echo '<p>' . __('Configure your Twilio API credentials below.', 'wp-phone-verify') . '</p>';
    }

    public function render_text_field($args) {
        $options = get_option($this->option_name);
        $field = $args['field'];
        $value = isset($options[$field]) ? $options[$field] : '';
        $type = $field === 'twilio_auth_token' ? 'password' : 'text';
        
        printf(
            '<input type="%s" id="%s" name="%s[%s]" value="%s" class="regular-text">',
            esc_attr($type),
            esc_attr($field),
            esc_attr($this->option_name),
            esc_attr($field),
            esc_attr($value)
        );
    }

    public function test_twilio_connection() {
        check_ajax_referer('wp-phone-verify-nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $phone = sanitize_text_field($_POST['phone']);
        
        // Generate random 6-digit OTP
        $otp = sprintf("%06d", mt_rand(100000, 999999));
        
        // Store OTP in transient for 5 minutes
        set_transient('wp_phone_verify_test_otp_' . $phone, $otp, 5 * MINUTE_IN_SECONDS);

        // Send OTP via Twilio
        $result = $this->send_twilio_sms($phone, "Your verification code is: $otp");

        if ($result['success']) {
            wp_send_json_success('OTP sent successfully');
        } else {
            wp_send_json_error($result['message']);
        }
    }

    public function verify_test_otp() {
        check_ajax_referer('wp-phone-verify-nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $phone = sanitize_text_field($_POST['phone']);
        $otp = sanitize_text_field($_POST['otp']);
        
        $stored_otp = get_transient('wp_phone_verify_test_otp_' . $phone);
        
        if (!$stored_otp) {
            wp_send_json_error('OTP expired');
        }

        if ($otp === $stored_otp) {
            delete_transient('wp_phone_verify_test_otp_' . $phone);
            wp_send_json_success('OTP verified successfully');
        } else {
            wp_send_json_error('Invalid OTP');
        }
    }

    private function send_twilio_sms($to, $message) {
        $options = get_option($this->option_name);
        
        if (empty($options['twilio_sid']) || empty($options['twilio_auth_token']) || empty($options['twilio_phone_number'])) {
            return array(
                'success' => false,
                'message' => 'Twilio credentials not configured'
            );
        }

        $account_sid = $options['twilio_sid'];
        $auth_token = $options['twilio_auth_token'];
        $from = $options['twilio_phone_number'];

        $url = "https://api.twilio.com/2010-04-01/Accounts/$account_sid/Messages.json";

        $args = array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($account_sid . ':' . $auth_token)
            ),
            'body' => array(
                'To' => $to,
                'From' => $from,
                'Body' => $message
            )
        );

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['sid'])) {
            return array(
                'success' => true,
                'message' => 'SMS sent successfully'
            );
        }

        return array(
            'success' => false,
            'message' => isset($body['message']) ? $body['message'] : 'Unknown error'
        );
    }
}