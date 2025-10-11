<?php
/**
 * Main handler for loading all shortcode-related classes and enqueuing global assets.
 * This class acts as a central loader and delegates the shortcode rendering to other specialized classes.
 *
 * @package Maneli_Car_Inquiry/Includes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.1 (Removed duplicate calculator assets loading)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Shortcode_Handler {

    public function __construct() {
        $this->load_shortcode_classes();
        add_action('wp_enqueue_scripts', [$this, 'enqueue_global_assets']);
    }

    /**
     * Loads and initializes all the separate shortcode handler classes from the 'shortcodes' directory.
     */
    private function load_shortcode_classes() {
        $shortcode_path = MANELI_INQUIRY_PLUGIN_PATH . 'includes/shortcodes/';
        
        require_once $shortcode_path . 'class-loan-calculator-shortcode.php';
        require_once $shortcode_path . 'class-inquiry-form-shortcode.php';
        require_once $shortcode_path . 'class-inquiry-lists-shortcode.php';
        require_once $shortcode_path . 'class-user-management-shortcodes.php';
        require_once $shortcode_path . 'class-admin-shortcodes.php';
        require_once $shortcode_path . 'class-system-report-shortcode.php';
        require_once $shortcode_path . 'class-product-editor-shortcode.php';

        new Maneli_Loan_Calculator_Shortcode();
        new Maneli_Inquiry_Form_Shortcode();
        new Maneli_Inquiry_Lists_Shortcode();
        new Maneli_User_Management_Shortcodes();
        new Maneli_Admin_Shortcodes();
        new Maneli_System_Report_Shortcode();
        new Maneli_Product_Editor_Shortcode();
    }

    /**
     * Enqueues assets used globally by the plugin's shortcodes on the frontend.
     * Assets are loaded conditionally based on the page content or context.
     */
    public function enqueue_global_assets() {
        if (is_admin()) {
            return;
        }

        // Get file modification times for cache busting
        $css_version = filemtime(MANELI_INQUIRY_PLUGIN_PATH . 'assets/css/frontend.css');

        // Global styles and libraries
        wp_enqueue_style('maneli-frontend-styles', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/frontend.css', [], $css_version);
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', [], '5.15.4');
        wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', [], null, true);

        
        // Conditionally load assets for pages containing specific shortcodes that need Select2
        global $post;
        if (is_a($post, 'WP_Post') && $this->post_has_shortcodes($post)) {
             wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0');
             wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0', true);
        }
    }

    /**
     * Checks if the given post content contains any of the plugin's shortcodes that require special assets.
     *
     * @param WP_Post $post The post object.
     * @return bool True if a relevant shortcode is found, false otherwise.
     */
    private function post_has_shortcodes($post) {
        $shortcodes_to_check = [
            'maneli_user_list',
            'maneli_inquiry_list',
            'maneli_expert_inquiry_list',
            'maneli_cash_inquiry_list',
            'maneli_product_editor',
            // Add 'car_inquiry_form' for expert panel which uses Select2
            'car_inquiry_form', 
        ];

        foreach ($shortcodes_to_check as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }
        
        return false;
    }
}