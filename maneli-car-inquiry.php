<?php
/**
 * Plugin Name:       Maneli Car Inquiry
 * Plugin URI:        https://puzzlinco.com
 * Description:       A plugin for car purchase inquiries using Finotex API and managing them in WordPress.
 * Version:           0.10.0
 * Author:            ArsalanArghavan
 * Author URI:        https://puzzlinco.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       maneli-car-inquiry
 * Domain Path:       /languages
 */


// --- Final Debugging: Custom Fatal Error Handler ---
register_shutdown_function('maneli_fatal_error_handler');
function maneli_fatal_error_handler() {
    $error = error_get_last();
    // We are only interested in fatal errors
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        $log_file_path = WP_CONTENT_DIR . '/maneli-plugin-errors.log';
        $error_message = "[" . date("Y-m-d H:i:s") . "] FATAL ERROR: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line'] . "\n";
        // Write the error to our custom log file
        @file_put_contents($log_file_path, $error_message, FILE_APPEND);
    }
}
// --- End of Debugging Code ---

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('MANELI_INQUIRY_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('MANELI_INQUIRY_PLUGIN_URL', plugin_dir_url(__FILE__));

final class Maneli_Car_Inquiry_Plugin {

    public function __construct() {
        add_action('plugins_loaded', [$this, 'initialize']);
    }

    public function initialize() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', [$this, 'woocommerce_not_active_notice']);
            return;
        }
        $this->includes();
        $this->init_classes();
    }

    public function includes() {
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-cpt-handler.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-settings-page.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-form-handler.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-shortcode-handler.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-user-profile.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-sms-handler.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-credit-report-page.php';
    }

    private function init_classes() {
        new Maneli_CPT_Handler();
        new Maneli_Settings_Page();
        new Maneli_Form_Handler();
        new Maneli_Shortcode_Handler();
        new Maneli_User_Profile();
        new Maneli_Credit_Report_Page();
    }

    public function woocommerce_not_active_notice() {
        ?>
        <div class="error"><p>پلاگین استعلام خودرو برای کار کردن به ووکامرس نیاز دارد. لطفاً ووکامرس را نصب و فعال کنید.</p></div>
        <?php
    }
}
new Maneli_Car_Inquiry_Plugin();