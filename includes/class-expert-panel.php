<?php
/**
 * Handles AJAX functionality for the Expert Panel, specifically for searching products
 * and localizing assets with the configurable loan interest rate.
 *
 * @package Maneli_Car_Inquiry/Includes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.1 (Added interest rate localization)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Expert_Panel {

    public function __construct() {
        add_action('wp_ajax_maneli_search_cars', [$this, 'handle_car_search_ajax']);
        // Note: Assuming a hook exists to enqueue assets for the expert panel page/shortcode.
        // If an expert panel shortcode exists, you would hook asset enqueueing to 'wp_enqueue_scripts'.
        // For simplicity, a generic hook is assumed if the asset localization is critical.
        // add_action('wp_enqueue_scripts', [$this, 'enqueue_expert_panel_assets']); 
        
        // Since expert-panel.js is needed for the inquiry creation form:
        add_action('wp_enqueue_scripts', [$this, 'enqueue_expert_panel_assets']);
    }

    /**
     * Enqueues assets specifically for the Expert Panel shortcode/form page.
     * This localizes the configurable loan interest rate.
     */
    public function enqueue_expert_panel_assets() {
        // Only enqueue if user can create inquiries and if the shortcode is present.
        // A dedicated check (like has_shortcode) should be used in a production environment.
        if (!current_user_can('edit_posts')) {
             return;
        }
        
        // Fetch configurable interest rate
        $options = get_option('maneli_inquiry_all_options', []);
        $interest_rate = floatval($options['loan_interest_rate'] ?? 0.035); 
        
        // Note: expert-panel.js is loaded along with the form shortcode, 
        // this ensures the localization object is available.
        wp_localize_script('maneli-expert-panel-js', 'maneli_expert_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('maneli_expert_car_search_nonce'),
            'interestRate' => $interest_rate, // NEW: Pass the monthly rate
        ]);
    }

    /**
     * AJAX handler for searching WooCommerce products by title.
     * Used by the Select2 input in the expert's new inquiry form.
     */
    public function handle_car_search_ajax() {
        // 1. Security Check: Verify the AJAX nonce.
        if (!check_ajax_referer('maneli_expert_car_search_nonce', 'nonce', false)) {
            wp_send_json_error(
                ['message' => esc_html__('Security check failed. Please refresh the page and try again.', 'maneli-car-inquiry')],
                403
            );
            return;
        }

        // 2. Permission Check: Ensure the user is logged in and has sufficient permissions.
        if (!is_user_logged_in() || !current_user_can('edit_posts')) {
            wp_send_json_error(
                ['message' => esc_html__('You do not have sufficient permissions to perform this action.', 'maneli-car-inquiry')],
                403
            );
            return;
        }

        // 3. Input Validation: Ensure a search term is provided.
        $search_term = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
        if (empty(trim($search_term))) {
            wp_send_json_success(['results' => []]); // Return empty results for an empty search
            return;
        }

        global $wpdb;
        $like_term = '%' . $wpdb->esc_like($search_term) . '%';

        // 4. Database Query: Directly query the database for published products matching the title.
        // This is a reliable way to get product IDs without interference from other plugins or complex meta queries.
        $product_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} 
             WHERE post_type = 'product' 
             AND post_status = 'publish' 
             AND post_title LIKE %s 
             ORDER BY post_title ASC
             LIMIT 20", // Limit results for performance
            $like_term
        ));

        $results = [];
        if (!empty($product_ids)) {
            foreach ($product_ids as $product_id) {
                // Use wc_get_product to safely retrieve product data.
                $product = wc_get_product($product_id);
                if ($product) {
                    $results[] = [
                        'id'              => $product->get_id(),
                        'text'            => $product->get_name(), // 'text' is the standard for Select2
                        'price'           => $product->get_price(),
                        'min_downpayment' => get_post_meta($product->get_id(), 'min_downpayment', true) ?: 0,
                    ];
                }
            }
        }
        
        // 5. Send Response: Always send a success response with the results array.
        wp_send_json_success(['results' => $results]);
    }
}