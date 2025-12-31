<?php
/**
 * Plugin Name:       AutoPuzzle
 * Plugin URI:        https://puzzlinco.com
 * Description:       A plugin for car purchase inquiries using Finotex API and managing them in WordPress.
 * Version:           1.2.01
 * Author:            ArsalanArghavan
 * Author URI:        https://arsalanarghavan.ir
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       autopuzzle
 * Domain Path:       /languages
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit; 
}

/**
 * Suppress open_basedir warnings from WordPress core translation system
 * This prevents warnings when WordPress tries to resolve translation paths
 * outside the allowed open_basedir restriction
 */
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if ($errno === E_WARNING && 
        strpos($errstr, 'open_basedir restriction') !== false && 
        (strpos($errfile, 'wp-translation-controller.php') !== false || 
         strpos($errfile, 'l10n') !== false)) {
        return true; // Suppress this specific warning
    }
    return false; // Let other errors through
}, E_WARNING);

/**
 * Define constants for the plugin.
 */
define('AUTOPUZZLE_PLUGIN_FILE', __FILE__);
define('AUTOPUZZLE_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('AUTOPUZZLE_PLUGIN_DIR', plugin_dir_path(__FILE__)); // Alias for compatibility
define('AUTOPUZZLE_PLUGIN_URL', plugin_dir_url(__FILE__));

// Legacy constants for backward compatibility with templates
// Note: These are aliases to avoid duplication and confusion
if (!defined('AUTOPUZZLE_PATH')) {
    define('AUTOPUZZLE_PATH', AUTOPUZZLE_PLUGIN_PATH);
}
if (!defined('AUTOPUZZLE_DIR')) {
    define('AUTOPUZZLE_DIR', AUTOPUZZLE_PLUGIN_DIR);
}
if (!defined('AUTOPUZZLE_URL')) {
    define('AUTOPUZZLE_URL', AUTOPUZZLE_PLUGIN_URL);
}

// Additional aliases for compatibility
if (!defined('AUTOPUZZLE_INQUIRY_URL')) {
    define('AUTOPUZZLE_INQUIRY_URL', AUTOPUZZLE_PLUGIN_URL);
}
if (!defined('AUTOPUZZLE_INQUIRY_DIR')) {
    define('AUTOPUZZLE_INQUIRY_DIR', AUTOPUZZLE_PLUGIN_DIR);
}

// Keep old constants for backward compatibility with third-party integrations
if (!defined('AUTOPUZZLE_INQUIRY_PLUGIN_FILE')) {
    define('AUTOPUZZLE_INQUIRY_PLUGIN_FILE', __FILE__);
}
if (!defined('AUTOPUZZLE_PLUGIN_PATH')) {
    define('AUTOPUZZLE_PLUGIN_PATH', AUTOPUZZLE_PLUGIN_PATH);
}
if (!defined('AUTOPUZZLE_INQUIRY_PLUGIN_DIR')) {
    define('AUTOPUZZLE_INQUIRY_PLUGIN_DIR', AUTOPUZZLE_PLUGIN_DIR);
}
if (!defined('AUTOPUZZLE_PLUGIN_URL')) {
    define('AUTOPUZZLE_PLUGIN_URL', AUTOPUZZLE_PLUGIN_URL);
}

define('AUTOPUZZLE_VERSION', '1.2.0');
if (!defined('AUTOPUZZLE_INQUIRY_VERSION')) {
    define('AUTOPUZZLE_INQUIRY_VERSION', AUTOPUZZLE_VERSION);
}
define('AUTOPUZZLE_DB_VERSION', '1.1.0');

// Backward compatibility for old version constants
if (!defined('AUTOPUZZLE_VERSION')) {
    define('AUTOPUZZLE_VERSION', AUTOPUZZLE_VERSION);
}
if (!defined('AUTOPUZZLE_INQUIRY_VERSION')) {
    define('AUTOPUZZLE_INQUIRY_VERSION', AUTOPUZZLE_VERSION);
}
if (!defined('AUTOPUZZLE_DB_VERSION')) {
    define('AUTOPUZZLE_DB_VERSION', AUTOPUZZLE_DB_VERSION);
}

/**
 * External API Endpoints (Centralized for Security and Maintenance)
 */
define('AUTOPUZZLE_FINOTEX_API_URL', 'https://api.finnotech.ir/credit/v2/clients/%s/chequeColorInquiry');

// Finnotech Credit APIs
define('AUTOPUZZLE_FINNOTECH_CREDIT_RISK_API_URL', 'https://api.finnotech.ir/kyc/v2/clients/%s/users/%s/transanctionCreditReport');
define('AUTOPUZZLE_FINNOTECH_CREDIT_SCORE_API_URL', 'https://api.finnotech.ir/kyc/v2/clients/%s/users/%s/transactionCreditInquiryReport');
define('AUTOPUZZLE_FINNOTECH_COLLATERALS_API_URL', 'https://api.finnotech.ir/credit/v2/clients/%s/users/%s/guaranteeCollaterals');
define('AUTOPUZZLE_FINNOTECH_CHEQUE_COLOR_API_URL', 'https://api.finnotech.ir/credit/v2/clients/%s/chequeColorInquiry');

define('AUTOPUZZLE_SMS_API_WSDL', 'https://api.payamak-panel.com/post/send.asmx?wsdl');

// Zarinpal Gateway
define('AUTOPUZZLE_ZARINPAL_REQUEST_URL', 'https://api.zarinpal.com/pg/v4/payment/request.json');
define('AUTOPUZZLE_ZARINPAL_VERIFY_URL', 'https://api.zarinpal.com/pg/v4/payment/verify.json');
define('AUTOPUZZLE_ZARINPAL_STARTPAY_URL', 'https://www.zarinpal.com/pg/StartPay/');

// Sadad Gateway
define('AUTOPUZZLE_SADAD_REQUEST_URL', 'https://sadad.shaparak.ir/vpg/api/v0/Request/PaymentRequest');
define('AUTOPUZZLE_SADAD_VERIFY_URL', 'https://sadad.shaparak.ir/vpg/api/v0/Advice/Verify');
define('AUTOPUZZLE_SADAD_PURCHASE_URL', 'https://sadad.shaparak.ir/VPG/Purchase?Token=');

// Keep old API constants for backward compatibility
if (!defined('AUTOPUZZLE_FINOTEX_API_URL')) {
    define('AUTOPUZZLE_FINOTEX_API_URL', AUTOPUZZLE_FINOTEX_API_URL);
}
if (!defined('AUTOPUZZLE_FINNOTECH_CREDIT_RISK_API_URL')) {
    define('AUTOPUZZLE_FINNOTECH_CREDIT_RISK_API_URL', AUTOPUZZLE_FINNOTECH_CREDIT_RISK_API_URL);
}
if (!defined('AUTOPUZZLE_FINNOTECH_CREDIT_SCORE_API_URL')) {
    define('AUTOPUZZLE_FINNOTECH_CREDIT_SCORE_API_URL', AUTOPUZZLE_FINNOTECH_CREDIT_SCORE_API_URL);
}
if (!defined('AUTOPUZZLE_FINNOTECH_COLLATERALS_API_URL')) {
    define('AUTOPUZZLE_FINNOTECH_COLLATERALS_API_URL', AUTOPUZZLE_FINNOTECH_COLLATERALS_API_URL);
}
if (!defined('AUTOPUZZLE_FINNOTECH_CHEQUE_COLOR_API_URL')) {
    define('AUTOPUZZLE_FINNOTECH_CHEQUE_COLOR_API_URL', AUTOPUZZLE_FINNOTECH_CHEQUE_COLOR_API_URL);
}
if (!defined('AUTOPUZZLE_SMS_API_WSDL')) {
    define('AUTOPUZZLE_SMS_API_WSDL', AUTOPUZZLE_SMS_API_WSDL);
}
if (!defined('AUTOPUZZLE_ZARINPAL_REQUEST_URL')) {
    define('AUTOPUZZLE_ZARINPAL_REQUEST_URL', AUTOPUZZLE_ZARINPAL_REQUEST_URL);
}
if (!defined('AUTOPUZZLE_ZARINPAL_VERIFY_URL')) {
    define('AUTOPUZZLE_ZARINPAL_VERIFY_URL', AUTOPUZZLE_ZARINPAL_VERIFY_URL);
}
if (!defined('AUTOPUZZLE_ZARINPAL_STARTPAY_URL')) {
    define('AUTOPUZZLE_ZARINPAL_STARTPAY_URL', AUTOPUZZLE_ZARINPAL_STARTPAY_URL);
}
if (!defined('AUTOPUZZLE_SADAD_REQUEST_URL')) {
    define('AUTOPUZZLE_SADAD_REQUEST_URL', AUTOPUZZLE_SADAD_REQUEST_URL);
}
if (!defined('AUTOPUZZLE_SADAD_VERIFY_URL')) {
    define('AUTOPUZZLE_SADAD_VERIFY_URL', AUTOPUZZLE_SADAD_VERIFY_URL);
}
if (!defined('AUTOPUZZLE_SADAD_PURCHASE_URL')) {
    define('AUTOPUZZLE_SADAD_PURCHASE_URL', AUTOPUZZLE_SADAD_PURCHASE_URL);
}

/**
 * The main file that bootstraps the plugin.
 */
require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-autopuzzle-car-inquiry.php';
require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-autopuzzle-migration.php';

/**
 * Begins execution of the plugin.
 *
 * This function is called once the plugin is loaded and is responsible for
 * creating an instance of the main plugin class.
 *
 * @since 1.0.0
 */
function run_autopuzzle_plugin() {
    // Run lightweight migration/aliasing to ensure backward compatibility
    if (class_exists('Autopuzzle_Migration')) {
        Autopuzzle_Migration::run();
    }
    $plugin = Autopuzzle_Car_Inquiry_Plugin::instance();
    $plugin->run();
}
run_autopuzzle_plugin();

/**
 * Register activation and deactivation hooks
 */
register_activation_hook(__FILE__, function() {
    require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-autopuzzle-activator.php';
    Autopuzzle_Activator::activate();
});

register_deactivation_hook(__FILE__, function() {
    require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-roles-caps.php';
    Autopuzzle_Roles_Caps::deactivate();
});
