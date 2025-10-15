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
        add_shortcode('maneli_meetings_calendar', [$this, 'render_meetings_calendar']);
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

    /**
     * Renders a simple full-width meetings calendar view (list grouped by day with times and customer names).
     */
    public function render_meetings_calendar() {
        if (!is_user_logged_in() || !(current_user_can('manage_maneli_inquiries') || in_array('maneli_expert', wp_get_current_user()->roles, true))) {
            return '<div class="status-box status-error"><p>' . esc_html__('Unauthorized access.', 'maneli-car-inquiry') . '</p></div>';
        }
        $today = current_time('Y-m-d');
        $meetings = get_posts([
            'post_type' => 'maneli_meeting',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'ASC',
            'date_query' => [ ['after' => $today . ' 00:00:00'] ],
        ]);
        $by_day = [];
        foreach ($meetings as $m) {
            $start = get_post_meta($m->ID, 'meeting_start', true);
            $inquiry_id = get_post_meta($m->ID, 'meeting_inquiry_id', true);
            $customer_name = '';
            if (get_post_type($inquiry_id) === 'inquiry') {
                $customer = get_userdata(get_post_field('post_author', $inquiry_id));
                $customer_name = $customer ? $customer->display_name : '';
            } elseif (get_post_type($inquiry_id) === 'cash_inquiry') {
                $customer_name = (get_post_meta($inquiry_id, 'cash_first_name', true) . ' ' . get_post_meta($inquiry_id, 'cash_last_name', true));
            }
            $day = date('Y-m-d', strtotime($start));
            $by_day[$day][] = [ 'time' => date('H:i', strtotime($start)), 'customer' => $customer_name ];
        }
        ob_start();
        echo '<div class="maneli-meetings-calendar">';
        if (empty($by_day)) {
            echo '<div class="status-box status-pending"><p>' . esc_html__('No meetings scheduled.', 'maneli-car-inquiry') . '</p></div>';
        } else {
            foreach ($by_day as $day => $items) {
                echo '<div class="calendar-day"><h3>' . esc_html(date_i18n('l, F j, Y', strtotime($day))) . '</h3>';
                echo '<div class="calendar-events">';
                foreach ($items as $ev) {
                    echo '<div class="calendar-event">';
                    echo '<span class="time">' . esc_html($ev['time']) . '</span>';
                    echo '<span class="name">' . esc_html($ev['customer']) . '</span>';
                    echo '</div>';
                }
                echo '</div></div>';
            }
        }
        echo '</div>';
        return ob_get_clean();
    }
}