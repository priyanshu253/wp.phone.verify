<?php
/**
 * Plugin Name: WP Phone Verify
 * Description: Phone number verification using Twilio for WordPress forms
 * Version: 1.0.0
 * Author: Priyanshu Sharan
 * Author URI: https://github.com/priyanshu253
 * Text Domain: wp-phone-verify
 * License: GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WP_PHONE_VERIFY_VERSION', '1.0.0');
define('WP_PHONE_VERIFY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_PHONE_VERIFY_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once WP_PHONE_VERIFY_PLUGIN_DIR . 'includes/class-wp-phone-verify.php';
require_once WP_PHONE_VERIFY_PLUGIN_DIR . 'includes/class-wp-phone-verify-admin.php';
require_once WP_PHONE_VERIFY_PLUGIN_DIR . 'includes/class-wp-phone-verify-shortcode.php';

// Initialize the plugin
function wp_phone_verify_init() {
    $plugin = new WP_Phone_Verify();
    $plugin->init();
}
add_action('plugins_loaded', 'wp_phone_verify_init');