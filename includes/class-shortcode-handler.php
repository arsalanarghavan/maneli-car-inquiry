<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main handler for loading all shortcode-related classes and enqueuing global assets.
 * This class acts as a central loader and delegates the shortcode rendering to other specialized classes.
 */
class Maneli_Shortcode_Handler {

    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_global_assets']);
        $this->load_shortcode_classes();
    }

    /**
     * Enqueues assets used globally by the plugin's shortcodes on the frontend.
     */
    public function enqueue_global_assets() {
        if (!is_admin()) {
            $css_version = filemtime(MANELI_INQUIRY_PLUGIN_PATH . 'assets/css/frontend.css');
            $js_version = filemtime(MANELI_INQUIRY_PLUGIN_PATH . 'assets/js/calculator.js');

            wp_enqueue_style('maneli-frontend-styles', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/frontend.css', [], $css_version);
            wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', [], '5.15.4');
            wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', [], null, true);

            if (is_product()) {
                wp_enqueue_script('maneli-calculator-js', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/calculator.js', ['jquery'], $js_version, true);
                if (is_user_logged_in()) {
                    wp_localize_script('maneli-calculator-js', 'maneli_ajax_object', [
                        'ajax_url'         => admin_url('admin-ajax.php'),
                        'inquiry_page_url' => home_url('/dashboard/?endp=inf_menu_1'),
                        'nonce'            => wp_create_nonce('maneli_ajax_nonce')
                    ]);
                }
            }
            
            global $post;
            if (is_a($post, 'WP_Post')) {
                $has_user_list = has_shortcode($post->post_content, 'maneli_user_list');
                $has_inquiry_list = has_shortcode($post->post_content, 'maneli_inquiry_list') || has_shortcode($post->post_content, 'maneli_expert_inquiry_list');
                $has_cash_inquiry_list = has_shortcode($post->post_content, 'maneli_cash_inquiry_list');
                $has_product_editor = has_shortcode($post->post_content, 'maneli_product_editor');

                if ($has_user_list || $has_inquiry_list || $has_product_editor || $has_cash_inquiry_list) {
                     wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0');
                     wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0', true);
                }

                if ($has_user_list) {
                    wp_localize_script('jquery', 'maneli_user_ajax', [
                        'ajax_url' => admin_url('admin-ajax.php'),
                        'delete_nonce' => wp_create_nonce('maneli_delete_user_nonce'),
                        'filter_nonce' => wp_create_nonce('maneli_user_filter_nonce')
                    ]);
                }
				
				if ($has_inquiry_list) {
                    wp_enqueue_script('maneli-inquiry-actions', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/inquiry-actions.js', ['jquery', 'sweetalert2'], filemtime(MANELI_INQUIRY_PLUGIN_PATH . 'assets/js/inquiry-actions.js'), true);
                    wp_localize_script('maneli-inquiry-actions', 'maneli_inquiry_ajax', [
                        'ajax_url' => admin_url('admin-ajax.php'),
                        'details_nonce' => wp_create_nonce('maneli_inquiry_details_nonce')
                    ]);
                }
            }
        }
    }
    
    /**
     * Loads and initializes all the separate shortcode handler classes from the 'shortcodes' directory.
     */
    private function load_shortcode_classes() {
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/shortcodes/class-inquiry-shortcodes.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/shortcodes/class-user-management-shortcodes.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/shortcodes/class-admin-shortcodes.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/shortcodes/class-system-report-shortcode.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/shortcodes/class-product-editor-shortcode.php';

        new Maneli_Inquiry_Shortcodes();
        new Maneli_User_Management_Shortcodes();
        new Maneli_Admin_Shortcodes();
        new Maneli_System_Report_Shortcode();
        new Maneli_Product_Editor_Shortcode();
    }
}