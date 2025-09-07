<?php
if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Expert_Panel {

    public function __construct() {
        add_action('wp_ajax_maneli_search_cars', [$this, 'handle_car_search_ajax']);
    }

    /**
     * Final AJAX handler for car search.
     * This version uses a direct DB query for reliability and includes robust checks.
     */
    public function handle_car_search_ajax() {
        // Check 1: Verify the AJAX request nonce for security.
        if (!check_ajax_referer('maneli_expert_car_search_nonce', 'nonce', false)) {
            wp_send_json_error(
                ['message' => 'خطای امنیتی (Nonce نامعتبر). لطفاً صفحه را رفرش کنید.'],
                403
            );
            return;
        }

        // Check 2: Verify user is logged in and has a basic capability.
        if (!is_user_logged_in() || !current_user_can('edit_posts')) {
            wp_send_json_error(
                ['message' => 'شما دسترسی لازم برای این کار را ندارید.'],
                403
            );
            return;
        }

        // Check 3: Ensure a search term is provided.
        if (!isset($_POST['search']) || empty(trim($_POST['search']))) {
            wp_send_json_success(['results' => []]); // Return empty if search is empty
            return;
        }

        global $wpdb;
        $search_term = sanitize_text_field($_POST['search']);
        $like_term = '%' . $wpdb->esc_like($search_term) . '%';

        // Direct database query to bypass any and all permission layers.
        $product_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} 
             WHERE post_type = 'product' 
             AND post_status = 'publish' 
             AND post_title LIKE %s 
             ORDER BY post_title ASC
             LIMIT 20",
            $like_term
        ));

        $results = [];
        if (!empty($product_ids)) {
            foreach ($product_ids as $product_id) {
                // Using wc_get_product is safe as we already have the ID.
                $product = wc_get_product($product_id);
                if ($product) {
                    $results[] = [
                        'id'   => $product->get_id(),
                        'text' => $product->get_name(),
                        'price' => $product->get_price(),
                        'min_downpayment' => get_post_meta($product->get_id(), 'min_downpayment', true) ?: 0
                    ];
                }
            }
        }
        
        // Always send a success response, even if the results are empty.
        wp_send_json_success(['results' => $results]);
    }
}