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
     * This version uses a direct WP_Query to bypass any capability or context issues.
     */
    public function handle_car_search_ajax() {
        // 1. Security Check - Essential
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'maneli_expert_car_search_nonce')) {
            wp_send_json_error(['message' => 'خطای امنیتی.']);
        }

        // 2. User Authentication Check - Essential
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'لطفاً ابتدا وارد شوید.']);
        }

        // 3. Get search term
        $search_term = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

        // 4. Use a direct WP_Query - This is the definitive fix
        $args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            's'              => $search_term,
        ];

        $query = new WP_Query($args);
        
        $results = [];
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $product = wc_get_product(get_the_ID());
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
        
        // Restore original post data
        wp_reset_postdata();
        
        // 5. Send the results back
        wp_send_json_success(['results' => $results]);
    }
}