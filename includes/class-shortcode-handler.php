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
        // Check license before loading shortcodes (using optimized helper)
        if (!Maneli_Permission_Helpers::is_license_active() && !Maneli_Permission_Helpers::is_demo_mode()) {
            // License not active - don't load shortcodes
            return;
        }
        
        $this->load_shortcode_classes();
        add_action('wp_enqueue_scripts', [$this, 'enqueue_global_assets']);
    }

    /**
     * Loads and initializes all the separate shortcode handler classes from the 'shortcodes' directory.
     * 
     * NOTE: Only loan_calculator shortcode is active. All other functionality
     * has been moved to dashboard pages for better UX.
     */
    private function load_shortcode_classes() {
        $shortcode_path = MANELI_INQUIRY_PLUGIN_PATH . 'includes/shortcodes/';
        
        // Active Shortcodes
        require_once $shortcode_path . 'class-loan-calculator-shortcode.php';
        new Maneli_Loan_Calculator_Shortcode();
    }

    /**
     * Enqueues assets used globally by the plugin's shortcodes on the frontend.
     * Assets are loaded conditionally based on the page content or context.
     */
    public function enqueue_global_assets() {
        if (is_admin()) {
            return;
        }

        // Line Awesome Complete - فایل CSS کامل و مستقل - check if file exists
        $line_awesome_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/css/maneli-line-awesome-complete.css';
        if (file_exists($line_awesome_path)) {
            wp_enqueue_style('maneli-line-awesome-complete', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-line-awesome-complete.css', [], '1.0.0');
        }
        
        // Global styles and libraries - Check if frontend.css exists before enqueuing
        $frontend_css_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/css/frontend.css';
        if (file_exists($frontend_css_path)) {
            $css_version = filemtime($frontend_css_path);
            wp_enqueue_style('maneli-frontend-styles', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/frontend.css', ['maneli-line-awesome-complete'], $css_version);
        } else {
            // Use maneli-shortcode-assets.css as fallback if frontend.css doesn't exist
            $fallback_css_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/css/maneli-shortcode-assets.css';
            if (file_exists($fallback_css_path)) {
                $css_version = filemtime($fallback_css_path);
                wp_enqueue_style('maneli-frontend-styles', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-shortcode-assets.css', ['maneli-line-awesome-complete'], $css_version);
            }
        }
        
        // Bootstrap RTL - Load early to avoid conflicts
        wp_enqueue_style('maneli-bootstrap-shortcode', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/bootstrap/css/bootstrap.rtl.min.css', [], '5.3.0');
        
        // Shortcode Xintra compat - check if file exists
        $xintra_compat_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/css/shortcode-xintra-compat.css';
        if (file_exists($xintra_compat_path)) {
            wp_enqueue_style('maneli-shortcode-xintra-compat', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/shortcode-xintra-compat.css', ['maneli-frontend-styles'], '1.0.0');
        }
        
        // Separate CSS files for shortcodes - Load AFTER Bootstrap to override
        wp_enqueue_style('maneli-loan-calculator', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/loan-calculator.css', ['maneli-frontend-styles', 'maneli-bootstrap-shortcode'], filemtime(MANELI_INQUIRY_PLUGIN_PATH . 'assets/css/loan-calculator.css'));
        wp_enqueue_style('maneli-installment-inquiry', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/installment-inquiry.css', ['maneli-frontend-styles', 'maneli-bootstrap-shortcode'], filemtime(MANELI_INQUIRY_PLUGIN_PATH . 'assets/css/installment-inquiry.css'));
        wp_enqueue_style('maneli-cash-inquiry', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/cash-inquiry.css', ['maneli-frontend-styles', 'maneli-bootstrap-shortcode'], '1.0.0');
        
        // Enqueue jQuery (required for all scripts)
        wp_enqueue_script('jquery');
        
        // Enqueue SweetAlert2 with jQuery dependency - Use local version
        $sweetalert2_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/libs/sweetalert2/sweetalert2.min.js';
        if (file_exists($sweetalert2_path)) {
            wp_enqueue_style('sweetalert2', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/sweetalert2/sweetalert2.min.css', [], '11.0.0');
            wp_enqueue_script('sweetalert2', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/sweetalert2/sweetalert2.min.js', ['jquery'], '11.0.0', true);
        } else {
            // Fallback to CDN if local file doesn't exist
            wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', ['jquery'], null, true);
        }
        
        // NOTE: calculator.js is enqueued conditionally by Maneli_Loan_Calculator_Shortcode
        // Only on product pages to ensure proper localization and avoid duplicate loading

        
        // Conditionally load assets for pages containing specific shortcodes that need Select2
        global $post;
        if (is_a($post, 'WP_Post') && $this->post_has_shortcodes($post)) {
             // Note: Select2 is not available locally, keep CDN
             wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0');
             wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0', true);
        }
        
        // Logging tracker - Load on all frontend pages to track user actions (using optimized helper)
        $enable_user_logging = Maneli_Options_Helper::is_option_enabled('enable_user_logging', false);
        
        if ($enable_user_logging) {
            $logging_tracker_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/js/logging-tracker.js';
            if (file_exists($logging_tracker_path)) {
                wp_enqueue_script(
                    'maneli-logging-tracker',
                    MANELI_INQUIRY_PLUGIN_URL . 'assets/js/logging-tracker.js',
                    ['jquery'],
                    filemtime($logging_tracker_path),
                    true
                );
                
                wp_localize_script('maneli-logging-tracker', 'maneliAjax', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('maneli_ajax_nonce'),
                ));
                
                wp_localize_script('maneli-logging-tracker', 'maneliLoggingSettings', array(
                    'enable_logging_system' => Maneli_Options_Helper::is_option_enabled('enable_logging_system', false),
                    'log_console_messages' => Maneli_Options_Helper::is_option_enabled('log_console_messages', false),
                    'enable_user_logging' => $enable_user_logging,
                    'log_button_clicks' => Maneli_Options_Helper::is_option_enabled('log_button_clicks', false),
                    'log_form_submissions' => Maneli_Options_Helper::is_option_enabled('log_form_submissions', false),
                    'log_ajax_calls' => Maneli_Options_Helper::is_option_enabled('log_ajax_calls', false),
                    'log_page_views' => Maneli_Options_Helper::is_option_enabled('log_page_views', false),
                ));
            }
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
            'maneli_followup_list',
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