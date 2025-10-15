<?php
/**
 * Handles the [maneli_product_editor] shortcode for frontend product editing.
 *
 * @package Maneli_Car_Inquiry/Includes/Shortcodes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Product_Editor_Shortcode {

    public function __construct() {
        add_shortcode('maneli_product_editor', [$this, 'render_product_editor']);
    }

    /**
     * Renders the product editor shortcode.
     */
    public function render_product_editor() {
        if (!current_user_can('manage_maneli_inquiries')) {
            return '<div class="maneli-inquiry-wrapper error-box"><p>' . esc_html__('You do not have sufficient permissions to access this page.', 'maneli-car-inquiry') . '</p></div>';
        }

        // Enqueue necessary scripts
        wp_enqueue_script(
            'maneli-product-editor-js',
            MANELI_INQUIRY_PLUGIN_URL . 'assets/js/admin/product-editor.js',
            ['jquery'],
            '1.0.0',
            true
        );

        wp_enqueue_style(
            'maneli-product-editor-css',
            MANELI_INQUIRY_PLUGIN_URL . 'assets/css/admin-styles.css',
            [],
            '1.0.0'
        );

        // Localize script for AJAX
        wp_localize_script('maneli-product-editor-js', 'maneliProductEditor', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('maneli_product_editor_nonce'),
            'text' => [
                'loading' => esc_html__('Loading...', 'maneli-car-inquiry'),
                'error' => esc_html__('An error occurred. Please try again.', 'maneli-car-inquiry'),
                'success' => esc_html__('Operation completed successfully.', 'maneli-car-inquiry'),
            ]
        ]);

        // Prepare data for the template
        $paged = max(1, get_query_var('paged'));
        $initial_products_query = wc_get_products([
            'limit' => 50,
            'page' => $paged,
            'status' => 'publish',
            'type' => 'simple',
            'category' => ['car-listing'],
            'return' => 'objects',
        ]);
        
        $total_products = wp_count_posts('product')->publish;
        $max_num_pages = ceil($total_products / 50);
        
        $template_args = [
            'product_stats_widgets_html' => Maneli_Admin_Dashboard_Widgets::render_product_statistics_widgets(),
            'initial_products_query' => (object)[
                'products' => $initial_products_query,
                'max_num_pages' => $max_num_pages,
            ],
            'paged' => $paged,
        ];

        return maneli_get_template_part('shortcodes/product-editor', $template_args, false);
    }
}
