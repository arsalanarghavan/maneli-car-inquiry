<?php
/**
 * Plugin Name:       Maneli Car Inquiry
 * Plugin URI:        https://puzzlinco.com
 * Description:       A plugin for car purchase inquiries using Finotex API and managing them in WordPress.
 * Version:           0.11.03
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


function maneli_ensure_expert_role_exists() {
    $role = get_role('maneli_expert');
    if (!$role) {
        add_role(
            'maneli_expert',
            'کارشناس مانلی',
            [
                'read' => true,
                'edit_posts' => true,
            ]
        );
    } elseif (!$role->has_cap('edit_posts')) {
        $role->add_cap('edit_posts');
    }
}
add_action('init', 'maneli_ensure_expert_role_exists');


register_deactivation_hook(__FILE__, 'maneli_remove_expert_role');
function maneli_remove_expert_role() {
    remove_role('maneli_expert');
}

function maneli_translate_roles() {
    global $wp_roles;
    if ( ! isset( $wp_roles ) ) {
        $wp_roles = new WP_Roles();
    }
    
    if(isset($wp_roles->roles['maneli_expert'])) {
        $wp_roles->roles['maneli_expert']['name'] = 'کارشناس مانلی';
    }
    
    if(isset($wp_roles->roles['administrator'])) {
        $wp_roles->roles['administrator']['name'] = 'مدیر کل';
    }
    if(isset($wp_roles->roles['editor'])) {
        $wp_roles->roles['editor']['name'] = 'ویرایشگر';
    }
    if(isset($wp_roles->roles['author'])) {
        $wp_roles->roles['author']['name'] = 'نویسنده';
    }
    if(isset($wp_roles->roles['contributor'])) {
        $wp_roles->roles['contributor']['name'] = 'مشارکت‌کننده';
    }
    if(isset($wp_roles->roles['subscriber'])) {
        $wp_roles->roles['subscriber']['name'] = 'مشترک';
    }
}
add_action('init', 'maneli_translate_roles');


add_action('admin_init', 'maneli_redirect_experts_from_admin');
function maneli_redirect_experts_from_admin() {
    global $pagenow;
    if (current_user_can('maneli_expert') && !current_user_can('manage_options') && $pagenow === 'index.php' && !wp_doing_ajax()) {
         wp_redirect(home_url());
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