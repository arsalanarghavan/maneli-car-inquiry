<?php
/**
 * Handles the [maneli_system_report] shortcode, which displays a general system overview for administrators.
 *
 * @package Maneli_Car_Inquiry/Includes/Shortcodes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_System_Report_Shortcode {

    public function __construct() {
        add_shortcode('maneli_system_report', [$this, 'render_shortcode']);
    }

    /**
     * Renders the system report shortcode.
     * It checks for user capabilities and then delegates the rendering to a template file.
     *
     * @return string The HTML output for the shortcode.
     */
    public function render_shortcode() {
        // 1. Security Check: Ensure only users with the master capability can view this report.
        if (!current_user_can('manage_maneli_inquiries')) {
            return '<div class="maneli-inquiry-wrapper error-box"><p>' . esc_html__('You do not have sufficient permissions to access this page.', 'maneli-car-inquiry') . '</p></div>';
        }

        // 2. Prepare data to be passed to the template.
        $template_args = [
            'inquiry_stats_widgets_html' => Maneli_Admin_Dashboard_Widgets::render_inquiry_statistics_widgets(),
            'latest_inquiries'           => get_posts([
                'post_type'      => 'inquiry',
                'posts_per_page' => 10,
                'orderby'        => 'date',
                'order'          => 'DESC'
            ]),
            'latest_users'               => get_users([
                'number'  => 10,
                'orderby' => 'user_registered',
                'order'   => 'DESC'
            ]),
        ];
        
        // 3. Render the template file.
        // The `false` argument tells the function to return the output as a string.
        return maneli_get_template_part('shortcodes/system-report', $template_args, false);
    }
}