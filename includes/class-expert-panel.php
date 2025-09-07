<?php
if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Expert_Panel {

    public function __construct() {
        add_action('wp_ajax_maneli_search_cars', [$this, 'handle_car_search_ajax']);
    }

    /**
     * AJAX handler for the Select2 car search in the expert panel.
     */
    public function handle_car_search_ajax() {
        // More specific nonce check with a clear error message
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'maneli_expert_nonce')) {
            wp_send_json_error(['message' => 'خطای امنیتی (Nonce نامعتبر). لطفاً صفحه را رفرش کرده و دوباره تلاش کنید.']);
        }

        // Allow experts, maneli admins, and full admins to search for cars.
        if (!current_user_can('edit_posts') && !current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => 'دسترسی غیر مجاز.']);
        }

        $search_term = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        
        $products = wc_get_products([
            'limit' => 20,
            'status' => 'publish',
            's' => $search_term
        ]);
        
        $results = [];
        if (!empty($products)) {
            foreach ($products as $product) {
                $results[] = [
                    'id' => $product->get_id(),
                    'text' => $product->get_name(),
                    'price' => $product->get_price(),
                    'min_downpayment' => get_post_meta($product->get_id(), 'min_downpayment', true) ?: 0
                ];
            }
        }
        
        wp_send_json(['results' => $results]);
    }
}