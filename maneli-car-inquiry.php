<?php
/**
 * Plugin Name:       Maneli Car Inquiry
 * Plugin URI:        https://puzzlinco.com
 * Description:       A plugin for car purchase inquiries using Finotex API and managing them in WordPress.
 * Version:           0.10.3
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

// Register custom user role on plugin activation
register_activation_hook(__FILE__, 'maneli_add_expert_role');
function maneli_add_expert_role() {
    add_role(
        'maneli_expert',
        'کارشناس مانلی',
        [
            'read'         => true,
            'edit_posts'   => false,
            'delete_posts' => false,
        ]
    );
}

// Remove custom user role on plugin deactivation
register_deactivation_hook(__FILE__, 'maneli_remove_expert_role');
function maneli_remove_expert_role() {
    remove_role('maneli_expert');
}

// Redirect experts from /wp-admin/ to the frontend dashboard
add_action('admin_init', 'maneli_redirect_experts_from_admin');
function maneli_redirect_experts_from_admin() {
    if (current_user_can('maneli_expert') && !current_user_can('manage_options') && !wp_doing_ajax()) {
        wp_redirect(home_url('/dashboard/'));
        exit;
    }
}

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
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-expert-panel.php';
    }

    private function init_classes() {
        new Maneli_CPT_Handler();
        new Maneli_Settings_Page();
        new Maneli_Form_Handler();
        new Maneli_Shortcode_Handler();
        new Maneli_User_Profile();
        new Maneli_Credit_Report_Page();
        new Maneli_Expert_Panel();
    }

    public function woocommerce_not_active_notice() {
        ?>
        <div class="error"><p>پلاگین استعلام خودرو برای کار کردن به ووکامرس نیاز دارد. لطفاً ووکامرس را نصب و فعال کنید.</p></div>
        <?php
    }
}

new Maneli_Car_Inquiry_Plugin();