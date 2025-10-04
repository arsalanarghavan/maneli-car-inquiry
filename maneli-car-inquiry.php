<?php
/**
 * Plugin Name:       Maneli Car Inquiry Core
 * Plugin URI:        https://puzzlinco.com
 * Description:       A plugin for car purchase inquiries using Finotex API and managing them in WordPress.
 * Version:           0.14.8
 * Author:            ArsalanArghavan
 * Author URI:        https://arsalanarghavan.ir
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
 * Helper function to convert Gregorian date to Jalali (Shamsi).
 */
function maneli_gregorian_to_jalali($gy, $gm, $gd, $format = 'Y/m/d') {
    $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    $jy = ($gy <= 1600) ? 0 : 979;
    $gy -= ($gy <= 1600) ? 621 : 1600;
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = (365 * $gy) + (int)(($gy2 + 3) / 4) - (int)(($gy2 + 99) / 100) + (int)(($gy2 + 399) / 400) - 80 + $gd + $g_d_m[$gm - 1];
    $jy += 33 * (int)($days / 12053);
    $days %= 12053;
    $jy += 4 * (int)($days / 1461);
    $days %= 1461;
    $jy += (int)(($days - 1) / 365);
    if ($days > 365) $days = ($days - 1) % 365;
    $jm = ($days < 186) ? 1 + (int)($days / 31) : 7 + (int)(($days - 186) / 30);
    $jd = 1 + (($days < 186) ? ($days % 31) : (($days - 186) % 30));
    $formatted_date = str_replace(['Y', 'm', 'd'], [$jy, sprintf('%02d', $jm), sprintf('%02d', $jd)], $format);
    return $formatted_date;
}

/**
 * Ensures the custom user roles and capabilities for the plugin exist.
 */
function maneli_setup_roles_and_caps() {
    // Grant management capability to Administrator
    $admin_role = get_role('administrator');
    if ($admin_role && !$admin_role->has_cap('manage_maneli_inquiries')) {
        $admin_role->add_cap('manage_maneli_inquiries');
    }

    $product_caps = [
        'edit_product'          => true,
        'read_product'          => true,
        'delete_product'        => false,
        'edit_products'         => true,
        'edit_others_products'  => true,
        'publish_products'      => false,
        'read_private_products' => false,
        'delete_products'       => false,
    ];

    $maneli_admin_caps = array_merge($product_caps, [
        'read' => true,
        'manage_maneli_inquiries' => true,
        'edit_posts' => true, 
    ]);

    remove_role('maneli_admin'); 
    add_role('maneli_admin', 'مدیریت مانلی', $maneli_admin_caps);

    $maneli_expert_caps = array_merge($product_caps, [
        'read' => true,
        'edit_posts' => true,
    ]);
    
    remove_role('maneli_expert');
    add_role('maneli_expert', 'کارشناس مانلی', $maneli_expert_caps);
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
    if(isset($wp_roles->roles['customer'])) { $wp_roles->roles['customer']['name'] = 'مشتری'; }
    if(isset($wp_roles->roles['administrator'])) { $wp_roles->roles['administrator']['name'] = 'مدیر کل'; }
    if(isset($wp_roles->roles['editor'])) { $wp_roles->roles['editor']['name'] = 'ویرایشگر'; }
    if(isset($wp_roles->roles['author'])) { $wp_roles->roles['author']['name'] = 'نویسنده'; }
    if(isset($wp_roles->roles['contributor'])) { $wp_roles->roles['contributor']['name'] = 'مشارکت‌کننده'; }
    if(isset($wp_roles->roles['subscriber'])) { $wp_roles->roles['subscriber']['name'] = 'مشترک'; }
}
add_action('init', 'maneli_translate_roles');

/**
 * Updates user's display name based on first and last name upon creation or update.
 */
function maneli_update_display_name($user_id) {
    $user = get_userdata($user_id);
    if (!$user) return;
    $first_name = $user->first_name;
    $last_name = $user->last_name;

    if (!empty($first_name) || !empty($last_name)) {
        $display_name = trim($first_name . ' ' . $last_name);
        if ($user->display_name !== $display_name && !empty($display_name)) {
            wp_update_user([
                'ID' => $user_id,
                'display_name' => $display_name
            ]);
        }
    }
}
add_action('user_register', 'maneli_update_display_name', 20, 1);
add_action('profile_update', 'maneli_update_display_name', 20, 1);

/**
 * One-time function to update display names for all existing users.
 */
function maneli_run_once_update_display_names() {
    if (get_option('maneli_display_names_updated_v2') !== 'yes') {
        $users = get_users();
        foreach ($users as $user) {
            maneli_update_display_name($user->ID);
        }
        update_option('maneli_display_names_updated_v2', 'yes');
    }
}
add_action('init', 'maneli_run_once_update_display_names');


/**
 * Redirects users with 'maneli_expert' or 'maneli_admin' roles away from the backend dashboard.
 */
add_action('admin_init', 'maneli_redirect_non_admins_from_backend');
function maneli_redirect_non_admins_from_backend() {
    if (wp_doing_ajax() || wp_doing_cron() || basename($_SERVER['PHP_SELF']) === 'admin-post.php') {
        return;
    }
    
    if ((current_user_can('manage_maneli_inquiries') || current_user_can('maneli_expert')) && !current_user_can('manage_options')) {
         wp_redirect(home_url('/dashboard/'));
         exit;
    }
}

/**
 * Modify queries to hide disabled products for non-admin users on the frontend.
 */
function maneli_pre_get_posts_query($query) {
    // This should only run on frontend queries for non-admin users
    if (!is_admin() && !current_user_can('manage_woocommerce') && $query->get('post_type') === 'product') {
        $meta_query = $query->get('meta_query') ?: [];
        
        if (!is_array($meta_query)) {
            $meta_query = [];
        }

        $meta_query[] = [
            'key' => '_maneli_car_status',
            'value' => 'disabled',
            'compare' => '!=',
        ];
        
        $meta_query['relation'] = 'OR';
        $meta_query[] = [
            'key' => '_maneli_car_status',
            'compare' => 'NOT EXISTS',
        ];
        
        $query->set('meta_query', $meta_query);
    }
}
add_action('pre_get_posts', 'maneli_pre_get_posts_query');


/**
 * AJAX handler for updating product data from the custom editor page.
 */
function maneli_update_product_data_callback() {
    check_ajax_referer('maneli_product_data_nonce', 'nonce');

    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error('شما دسترسی لازم را ندارید.');
        return;
    }

    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $field_type = isset($_POST['field_type']) ? sanitize_key($_POST['field_type']) : '';
    $field_value = isset($_POST['field_value']) ? sanitize_text_field(wp_unslash($_POST['field_value'])) : '';

    if ($product_id > 0 && !empty($field_type)) {
        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error('محصول یافت نشد.');
            return;
        }

        switch ($field_type) {
            case 'regular_price':
                $product->set_regular_price($field_value);
                $product->set_price($field_value);
                $product->save();
                break;
            case 'installment_price':
                update_post_meta($product_id, 'installment_price', $field_value);
                break;
            case 'min_downpayment':
                update_post_meta($product_id, 'min_downpayment', $field_value);
                break;
            case 'car_colors':
                update_post_meta($product_id, '_maneli_car_colors', $field_value);
                break;
            case 'car_status':
                update_post_meta($product_id, '_maneli_car_status', $field_value);
                break;
            default:
                wp_send_json_error('نوع فیلد نامعتبر است.');
                return;
        }
        
        wc_delete_product_transients($product_id);
        wp_send_json_success('اطلاعات با موفقیت به‌روزرسانی شد.');
    } else {
        wp_send_json_error('اطلاعات ارسالی نامعتبر است.');
    }
    
    wp_die();
}
add_action('wp_ajax_maneli_update_product_data', 'maneli_update_product_data_callback');


/**
 * Logic to hide prices based on the plugin setting.
 */
function maneli_maybe_hide_prices() {
    $options = get_option('maneli_inquiry_all_options', []);
    $is_price_hidden = isset($options['hide_prices_for_customers']) && $options['hide_prices_for_customers'] == '1';

    if ($is_price_hidden && !current_user_can('manage_woocommerce')) {
        remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
        add_filter('woocommerce_get_price_html', '__return_empty_string', 100, 2);
    }
}
add_action('wp', 'maneli_maybe_hide_prices');


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
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-product-editor-page.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-grouped-attributes.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-admin-dashboard-widgets.php';
    }

    private function init_classes() {
        new Maneli_CPT_Handler();
        new Maneli_Settings_Page();
        new Maneli_Form_Handler();
        new Maneli_Shortcode_Handler();
        new Maneli_Expert_Panel();
        new Maneli_Credit_Report_Page();
        new Maneli_User_Profile();
        new Maneli_Product_Editor_Page();
        
        $options = get_option('maneli_inquiry_all_options', []);
        if (isset($options['enable_grouped_attributes']) && $options['enable_grouped_attributes'] == '1') {
            new Maneli_Grouped_Attributes();
        }
    }

    public function woocommerce_not_active_notice() {
        ?>
        <div class="error"><p>پلاگین استعلام خودرو برای کار کردن به ووکامرس نیاز دارد. لطفاً ووکامرس را نصب و فعال کنید.</p></div>
        <?php
    }
}

new Maneli_Car_Inquiry_Plugin();