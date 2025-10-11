<?php
if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Hooks {

    public function __construct() {
        // User profile related hooks
        add_action('user_register', [$this, 'update_display_name'], 20, 1);
        add_action('profile_update', [$this, 'update_display_name'], 20, 1);
        add_action('init', [$this, 'run_once_update_display_names']);

        // Admin and frontend behavior hooks
        add_action('admin_init', [$this, 'redirect_non_admins_from_backend']);
        add_action('pre_get_posts', [$this, 'pre_get_posts_query']);
        add_action('wp', [$this, 'maybe_hide_prices']);

        // AJAX handler for updating product data
        add_action('wp_ajax_maneli_update_product_data', [$this, 'update_product_data_callback']);
    }

    /**
     * Updates user's display name based on first and last name.
     */
    public function update_display_name($user_id) {
        $user = get_userdata($user_id);
        if (!$user) return;
        $first_name = $user->first_name;
        $last_name = $user->last_name;

        if (!empty($first_name) || !empty($last_name)) {
            $display_name = trim($first_name . ' ' . $last_name);
            if ($user->display_name !== $display_name && !empty($display_name)) {
                wp_update_user(['ID' => $user_id, 'display_name' => $display_name]);
            }
        }
    }

    /**
     * One-time function to update display names for all existing users.
     */
    public function run_once_update_display_names() {
        if (get_option('maneli_display_names_updated_v2') !== 'yes') {
            $users = get_users();
            foreach ($users as $user) {
                $this->update_display_name($user->ID);
            }
            update_option('maneli_display_names_updated_v2', 'yes');
        }
    }

    /**
     * Redirects non-administrator users away from the backend dashboard.
     */
    public function redirect_non_admins_from_backend() {
        if (wp_doing_ajax() || wp_doing_cron() || basename($_SERVER['PHP_SELF']) === 'admin-post.php') {
            return;
        }
        if (!current_user_can('manage_options')) {
             wp_redirect(home_url('/dashboard/'));
             exit;
        }
    }

    /**
     * Modify queries to hide disabled products for non-admin users on the frontend.
     */
    public function pre_get_posts_query($query) {
        if (!is_admin() && !current_user_can('manage_maneli_inquiries') && $query->get('post_type') === 'product') {
            $meta_query = $query->get('meta_query') ?: [];
            if (!is_array($meta_query)) { $meta_query = []; }
            $meta_query['relation'] = 'OR';
            $meta_query[] = ['key' => '_maneli_car_status', 'value' => 'disabled', 'compare' => '!=',];
            $meta_query[] = ['key' => '_maneli_car_status', 'compare' => 'NOT EXISTS',];
            $query->set('meta_query', $meta_query);
        }
    }

    /**
     * Logic to hide prices based on the plugin setting.
     */
    public function maybe_hide_prices() {
        $options = get_option('maneli_inquiry_all_options', []);
        $is_price_hidden = isset($options['hide_prices_for_customers']) && $options['hide_prices_for_customers'] == '1';
        if ($is_price_hidden && !current_user_can('manage_maneli_inquiries')) {
            remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10);
            remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
            add_filter('woocommerce_get_price_html', '__return_empty_string', 100, 2);
        }
    }

    /**
     * AJAX handler for updating product data.
     */
    public function update_product_data_callback() {
        check_ajax_referer('maneli_product_data_nonce', 'nonce');
        if (!current_user_can('manage_maneli_inquiries')) {
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
                case 'installment_price': update_post_meta($product_id, 'installment_price', $field_value); break;
                case 'min_downpayment': update_post_meta($product_id, 'min_downpayment', $field_value); break;
                case 'car_colors': update_post_meta($product_id, '_maneli_car_colors', $field_value); break;
                case 'car_status': update_post_meta($product_id, '_maneli_car_status', $field_value); break;
                default: wp_send_json_error('نوع فیلد نامعتبر است.'); return;
            }
            wc_delete_product_transients($product_id);
            wp_send_json_success('اطلاعات با موفقیت به‌روزرسانی شد.');
        } else {
            wp_send_json_error('اطلاعات ارسالی نامعتبر است.');
        }
        wp_die();
    }
}