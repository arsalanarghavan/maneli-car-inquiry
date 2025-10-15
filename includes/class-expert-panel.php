<?php
/**
 * Handles AJAX functionality for the Expert Panel, specifically for searching products
 * and localizing assets with the configurable loan interest rate.
 *
 * @package Maneli_Car_Inquiry/Includes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.2 (Added Localization for Expert Panel JS strings)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Expert_Panel {

    public function __construct() {
        add_action('wp_ajax_maneli_search_cars', [$this, 'handle_car_search_ajax']);
        // Since expert-panel.js is needed for the inquiry creation form:
        add_action('wp_enqueue_scripts', [$this, 'enqueue_expert_panel_assets']);
    }

    /**
     * Enqueues assets specifically for the Expert Panel shortcode/form page.
     * This localizes the configurable loan interest rate and all necessary text strings.
     */
    public function enqueue_expert_panel_assets() {
        // Only enqueue if user can create inquiries and if the shortcode is present.
        if (!current_user_can('edit_posts')) {
             return;
        }
        
        // Enqueue expert panel JS
        wp_enqueue_script(
            'maneli-expert-panel-js',
            MANELI_INQUIRY_PLUGIN_URL . 'assets/js/expert-panel.js',
            ['jquery', 'select2'],
            '1.0.2',
            true
        );
        
        // Fetch configurable interest rate
        $options = get_option('maneli_inquiry_all_options', []);
        $interest_rate = floatval($options['loan_interest_rate'] ?? 0.035); 
        
        // Localize script with data
        wp_localize_script('maneli-expert-panel-js', 'maneli_expert_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('maneli_expert_car_search_nonce'),
            'interestRate' => $interest_rate, // Pass the monthly rate
            'text'     => [
                'car_search_placeholder' => esc_html__('Search for a car name...', 'maneli-car-inquiry'),
                'down_payment_label'     => esc_html__('Down Payment Amount (Toman)', 'maneli-car-inquiry'),
                'min_down_payment_desc'  => esc_html__('Minimum recommended down payment:', 'maneli-car-inquiry'),
                'term_months_label'      => esc_html__('Repayment Period (Months)', 'maneli-car-inquiry'),
                'loan_amount_label'      => esc_html__('Total Loan Amount', 'maneli-car-inquiry'),
                'total_repayment_label'  => esc_html__('Total Repayment Amount', 'maneli-car-inquiry'),
                'installment_amount_label' => esc_html__('Approximate Installment Amount', 'maneli-car-inquiry'),
                'toman'                  => esc_html__('Toman', 'maneli-car-inquiry'),
                'term_12'                => esc_html__('12 Months', 'maneli-car-inquiry'),
                'term_18'                => esc_html__('18 Months', 'maneli-car-inquiry'),
                'term_24'                => esc_html__('24 Months', 'maneli-car-inquiry'),
                'term_36'                => esc_html__('36 Months', 'maneli-car-inquiry'),
                'server_error'           => esc_html__('Server error:', 'maneli-car-inquiry'),
                'unknown_error'          => esc_html__('Unknown error', 'maneli-car-inquiry'),
                'datepicker_placeholder' => esc_html__('e.g., 1365/04/15', 'maneli-car-inquiry'),
            ]
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