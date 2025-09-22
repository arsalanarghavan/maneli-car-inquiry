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
            // Enqueue main frontend stylesheet and Font Awesome icons
            wp_enqueue_style('maneli-frontend-styles', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/frontend.css', [], '7.5.4');
            wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', [], '5.15.4');

            // Enqueue calculator script only on single product pages
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
            // Enqueue assets for the user list page only when the shortcode is present
            if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'maneli_user_list')) {
                wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0');
                wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0', true);
                wp_localize_script('jquery', 'maneli_user_ajax', [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'delete_nonce' => wp_create_nonce('maneli_delete_user_nonce'),
                    'filter_nonce' => wp_create_nonce('maneli_user_filter_nonce')
                ]);
            }
        }
    }
    
    /**
     * Loads and initializes all the separate shortcode handler classes from the 'shortcodes' directory.
     */
    private function load_shortcode_classes() {
        // Require all the refactored shortcode class files
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/shortcodes/class-inquiry-shortcodes.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/shortcodes/class-user-management-shortcodes.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/shortcodes/class-admin-shortcodes.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/shortcodes/class-system-report-shortcode.php';

        // Instantiate each class to register its shortcodes
        new Maneli_Inquiry_Shortcodes();
        new Maneli_User_Management_Shortcodes();
        new Maneli_Admin_Shortcodes();
        new Maneli_System_Report_Shortcode();
    }
}