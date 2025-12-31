<?php
/**
 * Main handler for loading all shortcode-related classes and enqueuing global assets.
 * This class acts as a central loader and delegates the shortcode rendering to other specialized classes.
 *
 * @package Autopuzzle_Car_Inquiry/Includes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.1 (Removed duplicate calculator assets loading)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Autopuzzle_Shortcode_Handler {

    public function __construct() {
        // Check license before loading shortcodes (using optimized helper)
        if (!Autopuzzle_Permission_Helpers::is_license_active() && !Autopuzzle_Permission_Helpers::is_demo_mode()) {
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
        $shortcode_path = AUTOPUZZLE_PLUGIN_PATH . 'includes/shortcodes/';
        
        // Active Shortcodes
        require_once $shortcode_path . 'class-loan-calculator-shortcode.php';
        new Autopuzzle_Loan_Calculator_Shortcode();
    }

    /**
     * Enqueues assets used globally by the plugin's shortcodes on the frontend.
     * Assets are loaded conditionally based on the page content or context.
     */
    public function enqueue_global_assets() {
        if (is_admin()) {
            return;
        }

        // Always register styles (they will be enqueued by enqueue_calculator_assets if needed)
        // This ensures styles are available when needed on product pages
        
        // Register Line Awesome Complete (always register, enqueue will be handled by calculator assets)
        $line_awesome_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/css/autopuzzle-line-awesome-complete.css';
        if (file_exists($line_awesome_path) && !wp_style_is('autopuzzle-line-awesome-complete', 'registered')) {
            wp_register_style('autopuzzle-line-awesome-complete', AUTOPUZZLE_PLUGIN_URL . 'assets/css/autopuzzle-line-awesome-complete.css', [], '1.0.0');
        }
        
        // Register frontend styles - Check if frontend.css exists before registering
        $frontend_css_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/css/frontend.css';
        if (!wp_style_is('autopuzzle-frontend-styles', 'registered')) {
            if (file_exists($frontend_css_path)) {
                $css_version = filemtime($frontend_css_path);
                wp_register_style('autopuzzle-frontend-styles', AUTOPUZZLE_PLUGIN_URL . 'assets/css/frontend.css', ['autopuzzle-line-awesome-complete'], $css_version);
            } else {
                // Use autopuzzle-shortcode-assets.css as fallback if frontend.css doesn't exist
                $fallback_css_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/css/autopuzzle-shortcode-assets.css';
                if (file_exists($fallback_css_path)) {
                    $css_version = filemtime($fallback_css_path);
                    wp_register_style('autopuzzle-frontend-styles', AUTOPUZZLE_PLUGIN_URL . 'assets/css/autopuzzle-shortcode-assets.css', ['autopuzzle-line-awesome-complete'], $css_version);
                }
            }
        }
        
        // Register Bootstrap RTL (always register, enqueue will be handled by calculator assets)
        if (!wp_style_is('autopuzzle-bootstrap-shortcode', 'registered')) {
            wp_register_style('autopuzzle-bootstrap-shortcode', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/bootstrap/css/bootstrap.rtl.min.css', [], '5.3.0');
        }
        
        // Register Shortcode Xintra compat (always register, enqueue will be handled by calculator assets)
        $xintra_compat_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/css/shortcode-xintra-compat.css';
        if (file_exists($xintra_compat_path) && !wp_style_is('autopuzzle-shortcode-xintra-compat', 'registered')) {
            wp_register_style('autopuzzle-shortcode-xintra-compat', AUTOPUZZLE_PLUGIN_URL . 'assets/css/shortcode-xintra-compat.css', ['autopuzzle-frontend-styles'], '1.0.0');
        }
        
        // Cash and Installment Inquiry styles will be registered/enqueued by calculator assets when needed
        
        // Enqueue jQuery (required for all scripts)
        wp_enqueue_script('jquery');
        
        // Enqueue SweetAlert2 with jQuery dependency - Use local version
        $sweetalert2_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/libs/sweetalert2/sweetalert2.min.js';
        if (file_exists($sweetalert2_path)) {
            wp_enqueue_style('sweetalert2', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/sweetalert2/sweetalert2.min.css', [], '11.0.0');
            wp_enqueue_script('sweetalert2', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/sweetalert2/sweetalert2.min.js', ['jquery'], '11.0.0', true);
        } else {
            // Fallback to CDN if local file doesn't exist
            wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', ['jquery'], null, true);
        }

        // NOTE: calculator.js and Select2 are enqueued conditionally by Autopuzzle_Loan_Calculator_Shortcode
        // Only on product pages to ensure proper localization and avoid duplicate loading
        // Select2 will be enqueued by enqueue_calculator_assets() when needed
        
        // Logging tracker - Load on all frontend pages to track user actions (using optimized helper)
        $enable_user_logging = Autopuzzle_Options_Helper::is_option_enabled('enable_user_logging', false);
        
        if ($enable_user_logging) {
            $logging_tracker_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/js/logging-tracker.js';
            if (file_exists($logging_tracker_path)) {
                wp_enqueue_script(
                    'autopuzzle-logging-tracker',
                    AUTOPUZZLE_PLUGIN_URL . 'assets/js/logging-tracker.js',
                    ['jquery'],
                    filemtime($logging_tracker_path),
                    true
                );
                
                wp_localize_script('autopuzzle-logging-tracker', 'maneliAjax', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('autopuzzle_ajax_nonce'),
                ));
                
                wp_localize_script('autopuzzle-logging-tracker', 'maneliLoggingSettings', array(
                    'enable_logging_system' => Autopuzzle_Options_Helper::is_option_enabled('enable_logging_system', false),
                    'log_console_messages' => Autopuzzle_Options_Helper::is_option_enabled('log_console_messages', false),
                    'enable_user_logging' => $enable_user_logging,
                    'log_button_clicks' => Autopuzzle_Options_Helper::is_option_enabled('log_button_clicks', false),
                    'log_form_submissions' => Autopuzzle_Options_Helper::is_option_enabled('log_form_submissions', false),
                    'log_ajax_calls' => Autopuzzle_Options_Helper::is_option_enabled('log_ajax_calls', false),
                    'log_page_views' => Autopuzzle_Options_Helper::is_option_enabled('log_page_views', false),
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
            'autopuzzle_user_list',
            'autopuzzle_inquiry_list',
            'autopuzzle_expert_inquiry_list',
            'autopuzzle_cash_inquiry_list',
            'autopuzzle_followup_list',
            'autopuzzle_product_editor',
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