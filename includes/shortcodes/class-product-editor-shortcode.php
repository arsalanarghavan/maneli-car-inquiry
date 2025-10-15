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

        // Get initial products data
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        
        $args = [
            'post_type' => 'product',
            'posts_per_page' => 50,
            'paged' => $paged,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ];
        
        if (!empty($search)) {
            $args['s'] = $search;
        }
        
        $query = new WP_Query($args);
        
        // Get products from query
        $products = [];
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $products[] = wc_get_product(get_the_ID());
            }
            wp_reset_postdata();
        }
        
        // Create a custom object for compatibility
        $initial_products_query = (object)[
            'products' => $products,
            'max_num_pages' => $query->max_num_pages
        ];

        // Generate product statistics widgets
        $product_stats_widgets_html = $this->render_product_statistics_widgets();

        $template_args = [
            'product_stats_widgets_html' => $product_stats_widgets_html,
            'initial_products_query' => $initial_products_query,
            'paged' => $paged
        ];

        return maneli_get_template_part('shortcodes/product-editor', $template_args, false);
    }
    
    /**
     * Renders product statistics widgets.
     *
     * @return string HTML for the statistics widgets.
     */
    private function render_product_statistics_widgets() {
        // Count products by status
        $total_products = wp_count_posts('product');
        $total = $total_products->publish ?? 0;
        
        // Count special sale products
        $special_sale_count = count(get_posts([
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_maneli_car_status',
                    'value' => 'special_sale'
                ]
            ],
            'fields' => 'ids'
        ]));
        
        // Count unavailable products
        $unavailable_count = count(get_posts([
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_maneli_car_status',
                    'value' => 'unavailable'
                ]
            ],
            'fields' => 'ids'
        ]));
        
        // Count disabled products
        $disabled_count = count(get_posts([
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_maneli_car_status',
                    'value' => 'disabled'
                ]
            ],
            'fields' => 'ids'
        ]));

        ob_start();
        ?>
        <div class="maneli-stats-widgets" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div class="stat-widget" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                <div style="font-size: 14px; opacity: 0.9; margin-bottom: 8px;"><?php esc_html_e('Total Products', 'maneli-car-inquiry'); ?></div>
                <div style="font-size: 32px; font-weight: bold;"><?php echo esc_html($total); ?></div>
            </div>
            <div class="stat-widget" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                <div style="font-size: 14px; opacity: 0.9; margin-bottom: 8px;"><?php esc_html_e('Special Sale', 'maneli-car-inquiry'); ?></div>
                <div style="font-size: 32px; font-weight: bold;"><?php echo esc_html($special_sale_count); ?></div>
            </div>
            <div class="stat-widget" style="background: linear-gradient(135deg, #ffa751 0%, #ffe259 100%); color: #333; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                <div style="font-size: 14px; opacity: 0.8; margin-bottom: 8px;"><?php esc_html_e('Unavailable', 'maneli-car-inquiry'); ?></div>
                <div style="font-size: 32px; font-weight: bold;"><?php echo esc_html($unavailable_count); ?></div>
            </div>
            <div class="stat-widget" style="background: linear-gradient(135deg, #a1c4fd 0%, #c2e9fb 100%); color: #333; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                <div style="font-size: 14px; opacity: 0.8; margin-bottom: 8px;"><?php esc_html_e('Disabled', 'maneli-car-inquiry'); ?></div>
                <div style="font-size: 32px; font-weight: bold;"><?php echo esc_html($disabled_count); ?></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
