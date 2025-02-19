<?php
class WP_Phone_Verify {
    private $admin;
    private $shortcode;

    public function init() {
        // Initialize admin
        $this->admin = new WP_Phone_Verify_Admin();
        $this->admin->init();

        // Initialize shortcode
        $this->shortcode = new WP_Phone_Verify_Shortcode();
        $this->shortcode->init();

        // Register scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'register_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'register_admin_scripts'));
    }

    public function register_scripts() {
        // Register and enqueue the script
        wp_enqueue_script(
            'wp-phone-verify',
            WP_PHONE_VERIFY_PLUGIN_URL . 'assets/js/wp-phone-verify.js',
            array('jquery'),
            WP_PHONE_VERIFY_VERSION,
            true
        );

        // Register and enqueue the style
        wp_enqueue_style(
            'wp-phone-verify',
            WP_PHONE_VERIFY_PLUGIN_URL . 'assets/css/wp-phone-verify.css',
            array(),
            WP_PHONE_VERIFY_VERSION
        );

        // Localize the script with new data
        wp_localize_script(
            'wp-phone-verify',
            'wpPhoneVerifyObj',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp-phone-verify-nonce')
            )
        );
    }

    public function register_admin_scripts($hook) {
        // Only load on our settings page
        if ('settings_page_wp-phone-verify' !== $hook) {
            return;
        }

        // Register and enqueue admin script
        wp_enqueue_script(
            'wp-phone-verify-admin',
            WP_PHONE_VERIFY_PLUGIN_URL . 'assets/js/wp-phone-verify-admin.js',
            array('jquery'),
            WP_PHONE_VERIFY_VERSION,
            true
        );

        // Register and enqueue admin style
        wp_enqueue_style(
            'wp-phone-verify-admin',
            WP_PHONE_VERIFY_PLUGIN_URL . 'assets/css/wp-phone-verify-admin.css',
            array(),
            WP_PHONE_VERIFY_VERSION
        );

        // Localize the admin script
        wp_localize_script(
            'wp-phone-verify-admin',
            'wpPhoneVerifyObj',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp-phone-verify-nonce')
            )
        );
    }
}