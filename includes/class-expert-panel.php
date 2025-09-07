<?php
if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Expert_Panel {

    public function __construct() {
        add_action('wp_ajax_maneli_search_cars', [$this, 'handle_car_search_ajax']);
    }

    public function handle_car_search_ajax() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'maneli_expert_car_search_nonce')) {
            wp_send_json_error(['message' => 'خطای امنیتی.']);
        }

        if (!current_user_can('read_product')) {
            wp_send_json_error(['message' => 'شما دسترسی لازم برای مشاهده محصولات را ندارید.']);
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
        
        wp_send_json_success(['results' => $results]);
    }
}