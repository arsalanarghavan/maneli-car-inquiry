<?php
/**
 * Handles the [maneli_settings] shortcode, which renders the plugin's settings panel on the frontend for administrators.
 *
 * @package Maneli_Car_Inquiry/Includes/Shortcodes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Admin_Shortcodes {

    public function __construct() {
        add_shortcode('maneli_settings', [$this, 'render_settings_shortcode']);
    }

    /**
     * Renders the settings panel shortcode.
     * It checks for user capabilities and then delegates the rendering to a template file.
     *
     * @return string The HTML output for the shortcode.
     */
    public function render_settings_shortcode() {
        // 1. Security Check: Ensure only users with the master capability can view the settings.
        if (!current_user_can('manage_maneli_inquiries')) {
            return '<div class="maneli-inquiry-wrapper error-box"><p>' . esc_html__('You do not have sufficient permissions to access this page.', 'maneli-car-inquiry') . '</p></div>';
        }
        
        // 2. Enqueue necessary scripts for the settings panel (e.g., for tab functionality).
        wp_enqueue_script(
            'maneli-admin-settings-js',
            MANELI_INQUIRY_PLUGIN_URL . 'assets/js/frontend/admin-settings.js',
            ['jquery'],
            '1.0.0',
            true
        );

        // 3. Prepare data to be passed to the template.
        $settings_page_handler = new Maneli_Settings_Page();
        $all_settings_tabs = $settings_page_handler->get_all_settings_public();
        
        $template_args = [
            'settings_updated' => isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true',
            'all_settings_tabs' => $all_settings_tabs,
            'settings_page_handler' => $settings_page_handler, // Pass the object to call its method in the template
        ];
        
        // 4. Render the template file.
        // The `false` argument tells the function to return the output as a string instead of echoing it.
        return maneli_get_template_part('shortcodes/admin-settings', $template_args, false);
    }
}