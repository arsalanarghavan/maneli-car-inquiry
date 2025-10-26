<?php
/**
 * Handles the [maneli_user_list] shortcode, providing a comprehensive frontend interface for user management.
 *
 * This class acts as a router, displaying either the user list, an add form, or an edit form based on URL parameters.
 * All AJAX and form processing logic has been moved to dedicated handler classes.
 *
 * @package Maneli_Car_Inquiry/Includes/Shortcodes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.2 (Removed Datepicker asset enqueuing logic for centralization)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_User_Management_Shortcodes {

    public function __construct() {
        add_shortcode('maneli_user_list', [$this, 'render_shortcode']);
    }

    /**
     * Renders the user management shortcode.
     * It checks for user capabilities and then routes to the appropriate view (list, add, edit).
     *
     * @return string The HTML output for the shortcode.
     */
    public function render_shortcode() {
        // 1. Security Check: Ensure only users with the master capability can access this page.
        if (!current_user_can('manage_maneli_inquiries')) {
            return '<div class="maneli-inquiry-wrapper error-box"><p>' . esc_html__('You do not have sufficient permissions to access this page.', 'maneli-car-inquiry') . '</p></div>';
        }

        // 2. Enqueue necessary scripts for the user management interface.
        $this->enqueue_scripts();

        // 3. Routing: Decide which template to render based on URL query parameters.
        if (isset($_GET['add_user'])) {
            return $this->render_user_add_form();
        }

        if (isset($_GET['edit_user'])) {
            $user_id_to_edit = intval($_GET['edit_user']);
            return $this->render_user_edit_form($user_id_to_edit);
        }

        // Default view: Render the user list.
        return $this->render_user_list();
    }

    /**
     * Renders the main user list view by preparing data and loading the corresponding template.
     *
     * @return string HTML content for the user list.
     */
    private function render_user_list() {
        $paged = get_query_var('paged') ? get_query_var('paged') : 1;
        
        $initial_user_query = new WP_User_Query([
            'orderby' => 'display_name',
            'order'   => 'ASC',
            'number'  => 50, // Users per page
            'paged'   => $paged,
        ]);

        $template_args = [
            'user_stats_widgets_html' => Maneli_Admin_Dashboard_Widgets::render_user_statistics_widgets(),
            'initial_user_query'      => $initial_user_query,
            'current_url'             => maneli_get_current_url(['edit_user', 'add_user', 'user-updated', 'user-created', 'user-deleted', 'error', 'paged']),
            'feedback_messages'       => $this->get_feedback_messages(),
        ];
        
        return maneli_get_template_part('shortcodes/user-management/user-list', $template_args, false);
    }
    
    /**
     * Renders the form for adding a new user.
     *
     * @return string HTML content for the add user form.
     */
    private function render_user_add_form() {
        $template_args = [
            'back_link' => maneli_get_current_url(['add_user']),
        ];
        return maneli_get_template_part('shortcodes/user-management/form-add-user', $template_args, false);
    }

    /**
     * Renders the form for editing an existing user.
     *
     * @param int $user_id The ID of the user to edit.
     * @return string HTML content for the edit user form.
     */
    private function render_user_edit_form($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return '<div class="maneli-inquiry-wrapper error-box"><p>' . esc_html__('The requested user was not found.', 'maneli-car-inquiry') . '</p></div>';
        }

        // NOTE: Datepicker assets are now loaded globally via class-shortcode-handler 
        // if this shortcode is present on the page, and the specific initialization 
        // is handled in assets/js/frontend/inquiry-form.js.
        // The custom asset loading logic is intentionally removed from here.

        $template_args = [
            'user'      => $user,
            'back_link' => maneli_get_current_url(['edit_user']),
        ];
        return maneli_get_template_part('shortcodes/user-management/form-edit-user', $template_args, false);
    }

    /**
     * Gathers feedback messages from URL query parameters to display to the user.
     *
     * @return array An array of messages to display.
     */
    private function get_feedback_messages() {
        $messages = [];
        if (isset($_GET['user-updated']) && $_GET['user-updated'] === 'true') {
            $messages['success'][] = esc_html__('User information updated successfully.', 'maneli-car-inquiry');
        }
        if (isset($_GET['user-created']) && $_GET['user-created'] === 'true') {
            $messages['success'][] = esc_html__('New user created successfully.', 'maneli-car-inquiry');
        }
        if (isset($_GET['user-deleted']) && $_GET['user-deleted'] === 'true') {
            $messages['success'][] = esc_html__('User deleted successfully.', 'maneli-car-inquiry');
        }
        if (isset($_GET['error'])) {
            $messages['error'][] = esc_html(urldecode($_GET['error']));
        }
        return $messages;
    }

    /**
     * Enqueues and localizes the JavaScript for the user management interface.
     */
    private function enqueue_scripts() {
        wp_enqueue_script(
            'maneli-user-management-js',
            MANELI_INQUIRY_PLUGIN_URL . 'assets/js/frontend/user-management.js',
            ['jquery'],
            '1.0.0',
            true
        );

        // Pass PHP data and nonces to the JavaScript file
        wp_localize_script('maneli-user-management-js', 'maneliUserManagement', [
            'ajax_url'     => admin_url('admin-ajax.php'),
            'filter_nonce' => wp_create_nonce('maneli_user_filter_nonce'),
            'delete_nonce' => wp_create_nonce('maneli_delete_user_nonce'),
            'text' => [
                'confirm_delete' => esc_html__('Are you sure you want to delete this user? This action cannot be undone.', 'maneli-car-inquiry'),
                'deleting'       => esc_html__('Deleting...', 'maneli-car-inquiry'),
                'delete'         => esc_html__('Delete', 'maneli-car-inquiry'),
                'error_deleting' => esc_html__('Error deleting user:', 'maneli-car-inquiry'),
                'server_error'   => esc_html__('A server error occurred.', 'maneli-car-inquiry'),
            ]
        ]);
        
        // Ensure datepicker assets are loaded for the edit form
        wp_enqueue_style('maneli-datepicker-theme', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-datepicker-theme.css');
        wp_enqueue_script('maneli-jalali-datepicker', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/vendor/kamadatepicker.min.js', [], '2.1.0', true);
        
        // This script contains the shared datepicker initialization logic.
        wp_enqueue_script(
            'maneli-inquiry-form-js',
            MANELI_INQUIRY_PLUGIN_URL . 'assets/js/frontend/inquiry-form.js',
            ['maneli-jalali-datepicker'],
            '1.0.0',
            true
        );
    }
}