<?php
/**
 * Plugin Name:       Maneli Car Inquiry
 * Plugin URI:        https://puzzlinco.com
 * Description:       A plugin for car purchase inquiries using Finotex API and managing them in WordPress.
 * Version:           0.10.26
 * Author:            ArsalanArghavan
 * Author URI:        https://puzzlinco.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       maneli-car-inquiry
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('MANELI_INQUIRY_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('MANELI_INQUIRY_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Ensures the 'maneli_expert' role exists on every page load.
 */
function maneli_ensure_expert_role_exists() {
    if (!get_role('maneli_expert')) {
        add_role(
            'maneli_expert',
            'کارشناس مانلی',
            [
                'read' => true,
            ]
        );
    }
}
add_action('init', 'maneli_ensure_expert_role_exists');


/**
 * Removes the custom user role on plugin deactivation for cleanup.
 */
register_deactivation_hook(__FILE__, 'maneli_remove_expert_role');
function maneli_remove_expert_role() {
    remove_role('maneli_expert');
}

/**
 * Redirects users with the 'maneli_expert' role away from the backend dashboard,
 * but allows them to access admin-post.php and admin-ajax.php for form processing.
 * This function is now less strict to allow experts to access frontend pages.
 */
add_action('admin_init', 'maneli_redirect_experts_from_admin');
function maneli_redirect_experts_from_admin() {
    // We remove the forceful redirect to allow experts to use frontend pages with shortcodes.
    // They are already blocked from most admin pages by capability checks.
    // If specific pages need to be blocked, it can be done here.
    // For example, to block the main dashboard:
    global $pagenow;
    if (current_user_can('maneli_expert') && !current_user_can('manage_options') && $pagenow === 'index.php' && !wp_doing_ajax()) {
         wp_redirect(home_url()); // Redirect from main dashboard to homepage
         exit;
    }
}

/**
 * Main plugin class.
 */
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
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-sms-handler.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-cpt-handler.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-settings-page.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-form-handler.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-shortcode-handler.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-expert-panel.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-credit-report-page.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-user-profile.php';
    }

    private function init_classes() {
        new Maneli_CPT_Handler();
        new Maneli_Settings_Page();
        new Maneli_Form_Handler();
        new Maneli_Shortcode_Handler();
        new Maneli_Expert_Panel();
        new Maneli_Credit_Report_Page();
        new Maneli_User_Profile();
    }

    public function woocommerce_not_active_notice() {
        ?>
        <div class="error"><p>پلاگین استعلام خودرو برای کار کردن به ووکامرس نیاز دارد. لطفاً ووکامرس را نصب و فعال کنید.</p></div>
        <?php
    }
}

new Maneli_Car_Inquiry_Plugin();