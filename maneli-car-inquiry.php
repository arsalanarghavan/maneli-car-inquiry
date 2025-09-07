<?php
/**
 * Plugin Name:       Maneli Car Inquiry
 * Plugin URI:        https://puzzlinco.com
 * Description:       A plugin for car purchase inquiries using Finotex API and managing them in WordPress.
 * Version:           0.11.07
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
 * Ensures the custom user roles and capabilities for the plugin exist.
 */
function maneli_setup_roles_and_caps() {
    $admin_role = get_role('administrator');
    if ($admin_role && !$admin_role->has_cap('manage_maneli_inquiries')) {
        $admin_role->add_cap('manage_maneli_inquiries');
    }

    $maneli_admin_role = get_role('maneli_admin');
    if (!$maneli_admin_role) {
        add_role(
            'maneli_admin',
            'مدیریت مانلی',
            [
                'read' => true,
                'manage_maneli_inquiries' => true,
            ]
        );
    }

    $expert_role = get_role('maneli_expert');
    if (!$expert_role) {
        add_role(
            'maneli_expert',
            'کارشناس مانلی',
            [
                'read' => true,
                'edit_posts' => true,
            ]
        );
    } elseif (!$expert_role->has_cap('edit_posts')) {
        $expert_role->add_cap('edit_posts');
    }
}
add_action('init', 'maneli_setup_roles_and_caps');

/**
 * Removes the custom user roles on plugin deactivation for cleanup.
 */
register_deactivation_hook(__FILE__, 'maneli_remove_custom_roles');
function maneli_remove_custom_roles() {
    $admin_role = get_role('administrator');
    if ($admin_role && $admin_role->has_cap('manage_maneli_inquiries')) {
        $admin_role->remove_cap('manage_maneli_inquiries');
    }
    remove_role('maneli_expert');
    remove_role('maneli_admin');
}

/**
 * Translates user role names to Persian.
 */
function maneli_translate_roles() {
    global $wp_roles;
    if ( ! isset( $wp_roles ) ) {
        $wp_roles = new WP_Roles();
    }
    
    if(isset($wp_roles->roles['maneli_admin'])) { $wp_roles->roles['maneli_admin']['name'] = 'مدیریت مانلی'; }
    if(isset($wp_roles->roles['maneli_expert'])) { $wp_roles->roles['maneli_expert']['name'] = 'کارشناس مانلی'; }
    if(isset($wp_roles->roles['administrator'])) { $wp_roles->roles['administrator']['name'] = 'مدیر کل'; }
    if(isset($wp_roles->roles['editor'])) { $wp_roles->roles['editor']['name'] = 'ویرایشگر'; }
    if(isset($wp_roles->roles['author'])) { $wp_roles->roles['author']['name'] = 'نویسنده'; }
    if(isset($wp_roles->roles['contributor'])) { $wp_roles->roles['contributor']['name'] = 'مشارکت‌کننده'; }
    if(isset($wp_roles->roles['subscriber'])) { $wp_roles->roles['subscriber']['name'] = 'مشترک'; }
}
add_action('init', 'maneli_translate_roles');

/**
 * Standardizes user email on registration.
 */
function maneli_change_user_email_on_registration($user_id) {
    $user = get_user_by('id', $user_id);
    if ($user) {
        $new_email = $user->user_login . '@manelikhodro.com';
        if ($user->user_email !== $new_email) {
            wp_update_user([
                'ID'         => $user_id,
                'user_email' => $new_email
            ]);
        }
    }
}
add_action('user_register', 'maneli_change_user_email_on_registration');


/**
 * Redirects users with 'maneli_expert' or 'maneli_admin' roles away from the backend dashboard.
 */
add_action('admin_init', 'maneli_redirect_non_admins_from_backend');
function maneli_redirect_non_admins_from_backend() {
    if (wp_doing_ajax() || wp_doing_cron()) {
        return;
    }
    
    if (current_user_can('maneli_admin') || (current_user_can('maneli_expert') && !current_user_can('manage_options'))) {
        if (basename($_SERVER['PHP_SELF']) === 'admin-post.php') {
            return;
        }
        wp_redirect(home_url());
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