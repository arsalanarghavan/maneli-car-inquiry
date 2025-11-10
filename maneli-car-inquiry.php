<?php
/**
 * Plugin Name:       Maneli Car Inquiry Core
 * Plugin URI:        https://puzzlinco.com
 * Description:       A plugin for car purchase inquiries using Finotex API and managing them in WordPress.
 * Version:           1.1.0
 * Author:            ArsalanArghavan
 * Author URI:        https://arsalanarghavan.ir
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       maneli-car-inquiry
 * Domain Path:       /languages
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit; 
}

/**
 * Define constants for the plugin.
 */
define('MANELI_INQUIRY_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('MANELI_INQUIRY_PLUGIN_DIR', plugin_dir_path(__FILE__)); // Alias for compatibility
define('MANELI_INQUIRY_PLUGIN_URL', plugin_dir_url(__FILE__));

// Legacy constants for backward compatibility with templates
// Note: These are aliases to avoid duplication and confusion
if (!defined('MANELI_PLUGIN_PATH')) {
    define('MANELI_PLUGIN_PATH', MANELI_INQUIRY_PLUGIN_PATH);
}
if (!defined('MANELI_PLUGIN_DIR')) {
    define('MANELI_PLUGIN_DIR', MANELI_INQUIRY_PLUGIN_DIR);
}
if (!defined('MANELI_PLUGIN_URL')) {
    define('MANELI_PLUGIN_URL', MANELI_INQUIRY_PLUGIN_URL);
}
define('MANELI_VERSION', '0.2.20');
define('MANELI_DB_VERSION', '1.1.0');

/**
 * External API Endpoints (Centralized for Security and Maintenance)
 */
define('MANELI_FINOTEX_API_URL', 'https://api.finnotech.ir/credit/v2/clients/%s/chequeColorInquiry');

// Finnotech Credit APIs
define('MANELI_FINNOTECH_CREDIT_RISK_API_URL', 'https://api.finnotech.ir/kyc/v2/clients/%s/users/%s/transanctionCreditReport');
define('MANELI_FINNOTECH_CREDIT_SCORE_API_URL', 'https://api.finnotech.ir/kyc/v2/clients/%s/users/%s/transactionCreditInquiryReport');
define('MANELI_FINNOTECH_COLLATERALS_API_URL', 'https://api.finnotech.ir/credit/v2/clients/%s/users/%s/guaranteeCollaterals');
define('MANELI_FINNOTECH_CHEQUE_COLOR_API_URL', 'https://api.finnotech.ir/credit/v2/clients/%s/chequeColorInquiry');

define('MANELI_SMS_API_WSDL', 'https://api.payamak-panel.com/post/send.asmx?wsdl');

// Zarinpal Gateway
define('MANELI_ZARINPAL_REQUEST_URL', 'https://api.zarinpal.com/pg/v4/payment/request.json');
define('MANELI_ZARINPAL_VERIFY_URL', 'https://api.zarinpal.com/pg/v4/payment/verify.json');
define('MANELI_ZARINPAL_STARTPAY_URL', 'https://www.zarinpal.com/pg/StartPay/');

// Sadad Gateway
define('MANELI_SADAD_REQUEST_URL', 'https://sadad.shaparak.ir/vpg/api/v0/Request/PaymentRequest');
define('MANELI_SADAD_VERIFY_URL', 'https://sadad.shaparak.ir/vpg/api/v0/Advice/Verify');
define('MANELI_SADAD_PURCHASE_URL', 'https://sadad.shaparak.ir/VPG/Purchase?Token=');

/**
 * The main file that bootstraps the plugin.
 */
require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-maneli-car-inquiry.php';

/**
 * Begins execution of the plugin.
 *
 * This function is called once the plugin is loaded and is responsible for
 * creating an instance of the main plugin class.
 *
 * @since 1.0.0
 */
function run_maneli_car_inquiry_plugin() {
    $plugin = Maneli_Car_Inquiry_Plugin::instance();
    $plugin->run();
}
run_maneli_car_inquiry_plugin();

/**
 * Register activation and deactivation hooks
 */
register_activation_hook(__FILE__, function() {
    require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-maneli-activator.php';
    Maneli_Activator::activate();
});

register_deactivation_hook(__FILE__, function() {
    require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-roles-caps.php';
    Maneli_Roles_Caps::deactivate();
});
