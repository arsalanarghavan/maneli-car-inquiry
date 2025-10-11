<?php
/**
 * Handles the [maneli_product_editor] shortcode, which provides a frontend interface
 * for administrators to quickly edit product prices, colors, and sales status.
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
        add_shortcode('maneli_product_editor', [$this, 'render_shortcode']);
    }

    /**
     * Renders the product editor shortcode.
     * It checks for user capabilities, enqueues necessary assets, and then delegates rendering to a template file.
     *
     * @return string The HTML output for the shortcode.
     */
    public function render_shortcode() {
        // 1. Security Check: Ensure only users with the master capability can view this editor.
        if (!current_user_can('manage_maneli_inquiries')) {
            return '<div class="maneli-inquiry-wrapper error-box"><p>' . esc_html__('You do not have sufficient permissions to access this page.', 'maneli-car-inquiry') . '</p></div>';
        }

        // 2. Enqueue the dedicated JavaScript for this component.
        $this->enqueue_scripts();

        // 3. Prepare initial data for the template.
        $products_per_page = 50;
        $paged = get_query_var('paged') ? get_query_var('paged') : 1;

        $initial_products_query = wc_get_products([
            'limit'    => $products_per_page,
            'page'     => $paged,
            'orderby'  => 'title',
            'order'    => 'ASC',
            'paginate' => true, // Needed to get total pages for pagination
        ]);
        
        $template_args = [
            'product_stats_widgets_html' => Maneli_Admin_Dashboard_Widgets::render_product_statistics_widgets(),
            'initial_products_query'     => $initial_products_query,
            'paged'                      => $paged,
        ];
        
        // 4. Render the template file.
        // The `false` argument tells the function to return the output as a string.
        return maneli_get_template_part('shortcodes/product-editor', $template_args, false);
    }
    
    /**
     * Enqueues and localizes the necessary JavaScript for the product editor interface.
     */
    private function enqueue_scripts() {
        wp_enqueue_script(
            'maneli-product-editor-js',
            MANELI_INQUIRY_PLUGIN_URL . 'assets/js/frontend/product-editor.js',
            ['jquery'],
            '1.0.0', // Use filemtime for cache busting in production
            true
        );

        // Pass PHP data to the JavaScript file
        wp_localize_script('maneli-product-editor-js', 'maneliProductEditor', [
            'ajax_url'          => admin_url('admin-ajax.php'),
            'filter_nonce'      => wp_create_nonce('maneli_product_filter_nonce'),
            'update_data_nonce' => wp_create_nonce('maneli_product_data_nonce'),
            'text' => [
                'error' => esc_html__('Error:', 'maneli-car-inquiry'),
            ]
        ]);
    }
}