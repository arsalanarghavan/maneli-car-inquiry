<?php
if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Shortcode_Handler {

    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_global_assets']);
        $this->load_shortcode_classes();
    }

    /**
     * Enqueues assets used globally by the shortcodes.
     */
    public function enqueue_global_assets() {
        if (!is_admin()) {
            wp_enqueue_style('maneli-frontend-styles', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/frontend.css', [], '7.5.1');
            wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', [], '5.15.4');

            if (is_product()) {
                wp_enqueue_script('maneli-calculator-js', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/calculator.js', ['jquery'], '7.2.1', true);
                if (is_user_logged_in()) {
                    wp_localize_script('maneli-calculator-js', 'maneli_ajax_object', [
                        'ajax_url'         => admin_url('admin-ajax.php'),
                        'inquiry_page_url' => home_url('/dashboard/?endp=inf_menu_1'),
                        'nonce'            => wp_create_nonce('maneli_ajax_nonce')
                    ]);
                }
            }
            
            global $post;
            if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'maneli_user_list')) {
                wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0');
                wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0', true);
                wp_localize_script('jquery', 'maneli_user_ajax', [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'delete_nonce' => wp_create_nonce('maneli_delete_user_nonce')
                ]);
            }
        }
    }
    
    /**
     * Loads and initializes all the separate shortcode handler classes.
     */
    private function load_shortcode_classes() {
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/shortcodes/class-inquiry-shortcodes.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/shortcodes/class-user-management-shortcodes.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/shortcodes/class-admin-shortcodes.php';

        new Maneli_Inquiry_Shortcodes();
        new Maneli_User_Management_Shortcodes();
        new Maneli_Admin_Shortcodes();
    }
}