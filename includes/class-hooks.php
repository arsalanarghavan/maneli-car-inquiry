<?php
/**
 * Manages general WordPress hooks to modify core behaviors.
 * This includes user redirects, query modifications, and price visibility.
 *
 * @package Maneli_Car_Inquiry/Includes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Hooks {

    public function __construct() {
        // User profile related hooks
        add_action('user_register', [$this, 'update_display_name_on_event'], 20, 1);
        add_action('profile_update', [$this, 'update_display_name_on_event'], 20, 1);
        add_action('init', [$this, 'run_once_update_all_display_names']);

        // Admin and frontend behavior hooks
        add_action('admin_init', [$this, 'redirect_non_admins_from_backend']);
        add_action('pre_get_posts', [$this, 'modify_product_query_for_customers']);
        add_action('wp', [$this, 'conditionally_hide_prices']);
    }

    /**
     * Updates a user's display name based on their first and last name.
     * Hooked to 'user_register' and 'profile_update'.
     *
     * @param int $user_id The ID of the user being updated or registered.
     */
    public function update_display_name_on_event($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        $first_name = $user->first_name;
        $last_name = $user->last_name;
        $display_name = trim($first_name . ' ' . $last_name);

        // Update only if the new display name is not empty and is different from the current one.
        if (!empty($display_name) && $user->display_name !== $display_name) {
            wp_update_user([
                'ID'           => $user_id,
                'display_name' => $display_name,
            ]);
        }
    }

    /**
     * One-time function to update display names for all existing users.
     * This helps normalize data for users created before the plugin was activated.
     */
    public function run_once_update_all_display_names() {
        if (get_option('maneli_display_names_updated_v2') === 'yes') {
            return;
        }

        $users = get_users(['fields' => 'ID']);
        foreach ($users as $user_id) {
            $this->update_display_name_on_event($user_id);
        }
        
        update_option('maneli_display_names_updated_v2', 'yes');
    }

    /**
     * Redirects non-administrator users from the WordPress backend (/wp-admin) to the frontend dashboard.
     * Ensures that all plugin custom roles (maneli_admin, maneli_expert) are also blocked from wp-admin.
     */
    public function redirect_non_admins_from_backend() {
        // Do not redirect for AJAX, Cron, or admin-post.php requests
        if (wp_doing_ajax() || wp_doing_cron() || (isset($_SERVER['PHP_SELF']) && basename($_SERVER['PHP_SELF']) === 'admin-post.php')) {
            return;
        }

        // Get current user
        $current_user = wp_get_current_user();
        
        // Check if user is logged in and in wp-admin
        if (!is_admin() || empty($current_user->ID)) {
            return;
        }

        // Get user roles
        $user_roles = (array) $current_user->roles;
        
        // Define plugin roles that should NOT have access to wp-admin
        $blocked_roles = ['maneli_admin', 'maneli_expert', 'customer'];
        
        // Check if user has any of the blocked roles
        $has_blocked_role = !empty(array_intersect($user_roles, $blocked_roles));
        
        // Redirect if user doesn't have manage_options capability OR has a blocked role
        if (!current_user_can('manage_options') || $has_blocked_role) {
            wp_redirect(home_url('/dashboard/'));
            exit;
        }
    }

    /**
     * Modifies the main product query for non-admin users on the frontend
     * to exclude products with the status 'disabled'.
     *
     * @param WP_Query $query The main WordPress query object.
     */
    public function modify_product_query_for_customers($query) {
        // Only run on the frontend, for the main product query, and for non-privileged users.
        if (is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'product' || current_user_can('manage_maneli_inquiries')) {
            return;
        }

        $meta_query = $query->get('meta_query') ?: [];
        if (!is_array($meta_query)) {
            $meta_query = [];
        }

        // Add a condition to either not have the '_maneli_car_status' key, or for it not to be 'disabled'.
        // This ensures both old products without the meta key and new, active products are shown.
        $meta_query['relation'] = 'OR';
        $meta_query[] = [
            'key'     => '_maneli_car_status',
            'value'   => 'disabled',
            'compare' => '!=',
        ];
        $meta_query[] = [
            'key'     => '_maneli_car_status',
            'compare' => 'NOT EXISTS',
        ];

        $query->set('meta_query', $meta_query);
    }

    /**
     * Conditionally removes price-related hooks based on plugin settings.
     * This effectively hides prices from non-admin users if the setting is enabled.
     */
    public function conditionally_hide_prices() {
        $options = get_option('maneli_inquiry_all_options', []);
        $is_price_hidden = !empty($options['hide_prices_for_customers']) && $options['hide_prices_for_customers'] == '1';

        if ($is_price_hidden && !current_user_can('manage_maneli_inquiries')) {
            // Remove prices from shop loop and single product pages
            remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10);
            remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
            
            // Filter the price HTML to return an empty string everywhere else
            add_filter('woocommerce_get_price_html', '__return_empty_string', 100, 2);
        }
    }
}