<?php
if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Expert_Panel {

    public function __construct() {
        // This class handles the backend logic for the expert panel, like AJAX calls.
        add_action('wp_ajax_maneli_search_cars', [$this, 'handle_car_search_ajax']);
    }

    /**
     * AJAX handler for the Select2 car search in the expert panel.
     * Responds to search queries and returns product data in JSON format.
     */
    public function handle_car_search_ajax() {
        // Security check
        check_ajax_referer('maneli_expert_nonce', 'nonce');

        // Permission check
        if (!current_user_can('read')) { // 'read' is a base capability for experts and admins
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