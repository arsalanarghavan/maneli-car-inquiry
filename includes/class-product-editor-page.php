<?php
/**
 * Creates and manages the custom admin page for quick product editing.
 * This page provides an interface in the backend (/wp-admin) to edit car prices, colors, and statuses.
 *
 * @package Maneli_Car_Inquiry/Includes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Product_Editor_Page {

    /**
     * The hook suffix for the custom admin page.
     * @var string
     */
    private $page_hook_suffix;

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Adds the custom product editor page as a submenu under the "Products" menu in the admin dashboard.
     */
    public function add_admin_menu_page() {
        $this->page_hook_suffix = add_submenu_page(
            'edit.php?post_type=product',
            esc_html__('Car Price & Status', 'maneli-car-inquiry'),
            esc_html__('Car Price & Status', 'maneli-car-inquiry'),
            'manage_woocommerce', // Capability check
            'maneli-product-editor',
            [$this, 'render_page_html']
        );
    }

    /**
     * Enqueues the necessary JavaScript for the product editor admin page.
     * The script is only loaded on this specific admin page.
     *
     * @param string $hook The hook suffix for the current admin page.
     */
    public function enqueue_admin_assets($hook) {
        if ($hook !== $this->page_hook_suffix) {
            return;
        }

        wp_enqueue_script(
            'maneli-admin-product-editor-js',
            MANELI_INQUIRY_PLUGIN_URL . 'assets/js/admin/product-editor.js',
            ['jquery'],
            '1.0.0', // Use filemtime for cache busting in production
            true
        );

        // Pass PHP data, such as nonces and URLs, to the JavaScript file
        wp_localize_script('maneli-admin-product-editor-js', 'maneliAdminProductEditor', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('maneli_product_data_nonce'),
            'text'     => [
                'error' => esc_html__('Error:', 'maneli-car-inquiry'),
            ]
        ]);
    }

    /**
     * Renders the HTML for the product editor page.
     * This method fetches the initial products and delegates the rendering to a template file.
     */
    public function render_page_html() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            echo '<div class="notice notice-error"><p>' . esc_html__('To use this plugin, please install and activate WooCommerce first.', 'maneli-car-inquiry') . '</p></div>';
            return;
        }

        // Prepare data for the template
        $template_args = [
            'products' => wc_get_products([
                'limit'   => -1, // Get all products
                'orderby' => 'title',
                'order'   => 'ASC',
            ]),
        ];
        
        // Load the template file to render the page content
        maneli_get_template_part('admin/product-editor-page', $template_args);
    }
}