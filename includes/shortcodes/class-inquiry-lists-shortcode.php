<?php
/**
 * Handles shortcodes for displaying lists of inquiries ([maneli_inquiry_list], [maneli_cash_inquiry_list])
 * and the detailed report view for a single inquiry.
 *
 * @package Maneli_Car_Inquiry/Includes/Shortcodes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Inquiry_Lists_Shortcode {

    public function __construct() {
        add_shortcode('maneli_inquiry_list', [$this, 'render_inquiry_list_router']);
        add_shortcode('maneli_cash_inquiry_list', [$this, 'render_cash_inquiry_list_router']);

        // Backward compatibility for the old shortcode
        add_shortcode('maneli_expert_inquiry_list', [$this, 'render_inquiry_list_router']);
    }

    /**
     * Router for the [maneli_inquiry_list] shortcode.
     * Decides whether to show the list of inquiries or a single detailed report.
     *
     * @return string HTML output.
     */
    public function render_inquiry_list_router() {
        if (!is_user_logged_in()) {
            return maneli_get_template_part('shortcodes/login-prompt', [], false);
        }
        
        // If an inquiry_id is present in the URL, show the detailed report.
        if (!empty($_GET['inquiry_id'])) {
            return $this->render_single_inquiry_report(intval($_GET['inquiry_id']));
        }

        // Otherwise, show the appropriate list based on user role.
        if (current_user_can('manage_maneli_inquiries') || in_array('maneli_expert', wp_get_current_user()->roles, true)) {
            return $this->render_admin_inquiry_list();
        } else {
            return $this->render_customer_inquiry_list(get_current_user_id());
        }
    }

    /**
     * Router for the [maneli_cash_inquiry_list] shortcode.
     * Decides whether to show the list of cash inquiries or a single detailed report.
     *
     * @return string HTML output.
     */
    public function render_cash_inquiry_list_router() {
        if (!is_user_logged_in()) {
            return maneli_get_template_part('shortcodes/login-prompt', [], false);
        }

        // If a cash_inquiry_id is present, show the detailed report for it.
        if (!empty($_GET['cash_inquiry_id'])) {
            return $this->render_single_cash_inquiry_report(intval($_GET['cash_inquiry_id']));
        }

        // Otherwise, show the list view based on user role.
        if (current_user_can('manage_maneli_inquiries') || in_array('maneli_expert', wp_get_current_user()->roles, true)) {
            return $this->render_admin_cash_inquiry_list();
        } else {
            return $this->render_customer_cash_inquiry_list(get_current_user_id());
        }
    }

    //======================================================================
    // ADMIN/EXPERT LIST VIEWS
    //======================================================================

    private function render_admin_inquiry_list() {
        $this->enqueue_admin_list_assets();
        return maneli_get_template_part('shortcodes/inquiry-lists/admin-installment-list', [], false);
    }

    private function render_admin_cash_inquiry_list() {
        $this->enqueue_admin_list_assets();
        return maneli_get_template_part('shortcodes/inquiry-lists/admin-cash-list', [], false);
    }

    //======================================================================
    // CUSTOMER LIST VIEWS
    //======================================================================

    private function render_customer_inquiry_list($user_id) {
        $paged = get_query_var('paged') ? get_query_var('paged') : 1;
        $query = new WP_Query([
            'post_type'      => 'inquiry',
            'posts_per_page' => 20,
            'paged'          => $paged,
            'author'         => $user_id,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);
        
        $args = ['inquiries_query' => $query, 'current_url' => remove_query_arg('inquiry_id')];
        return maneli_get_template_part('shortcodes/inquiry-lists/customer-installment-list', $args, false);
    }

    private function render_customer_cash_inquiry_list($user_id) {
        $paged = get_query_var('paged') ? get_query_var('paged') : 1;
        $query = new WP_Query([
            'post_type'      => 'cash_inquiry',
            'posts_per_page' => 20,
            'paged'          => $paged,
            'author'         => $user_id,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        $args = ['inquiries_query' => $query, 'current_url' => remove_query_arg('cash_inquiry_id')];
        if (isset($_GET['payment_status'])) {
            $args['payment_status'] = sanitize_text_field($_GET['payment_status']);
        }
        
        return maneli_get_template_part('shortcodes/inquiry-lists/customer-cash-list', $args, false);
    }

    //======================================================================
    // SINGLE REPORT VIEWS
    //======================================================================

    private function render_single_inquiry_report($inquiry_id) {
        $inquiry = get_post($inquiry_id);
        if (!$inquiry || $inquiry->post_type !== 'inquiry' || !Maneli_Permission_Helpers::can_user_view_inquiry($inquiry_id, get_current_user_id())) {
            return '<div class="maneli-inquiry-wrapper error-box"><p>' . esc_html__('Inquiry not found or you do not have permission to view it.', 'maneli-car-inquiry') . '</p></div>';
        }
        
        $is_admin_or_expert = current_user_can('manage_maneli_inquiries') || Maneli_Permission_Helpers::is_assigned_expert($inquiry_id, get_current_user_id());

        if ($is_admin_or_expert) {
            $this->enqueue_admin_list_assets(); // For modals and actions
            return maneli_get_template_part('shortcodes/inquiry-lists/report-admin-installment', ['inquiry_id' => $inquiry_id], false);
        } else {
            // Customers see the final step of the form process as their report.
            return maneli_get_template_part('shortcodes/inquiry-form/step-5-final-report', ['inquiry_id' => $inquiry_id], false);
        }
    }

    private function render_single_cash_inquiry_report($inquiry_id) {
        $inquiry = get_post($inquiry_id);
        if (!$inquiry || $inquiry->post_type !== 'cash_inquiry' || !Maneli_Permission_Helpers::can_user_view_inquiry($inquiry_id, get_current_user_id())) {
            return '<div class="maneli-inquiry-wrapper error-box"><p>' . esc_html__('Request not found or you do not have permission to view it.', 'maneli-car-inquiry') . '</p></div>';
        }

        $is_admin_or_expert = current_user_can('manage_maneli_inquiries') || Maneli_Permission_Helpers::is_assigned_expert($inquiry_id, get_current_user_id());
        
        if ($is_admin_or_expert) {
            $this->enqueue_admin_list_assets();
            return maneli_get_template_part('shortcodes/inquiry-lists/report-admin-cash', ['inquiry_id' => $inquiry_id], false);
        } else {
            return maneli_get_template_part('shortcodes/inquiry-lists/report-customer-cash', ['inquiry_id' => $inquiry_id], false);
        }
    }

    //======================================================================
    // ASSET ENQUEUEING
    //======================================================================

    /**
     * Helper function to enqueue scripts and localize data for admin/expert list views.
     */
    private function enqueue_admin_list_assets() {
        wp_enqueue_script(
            'maneli-inquiry-lists-js',
            MANELI_INQUIRY_PLUGIN_URL . 'assets/js/frontend/inquiry-lists.js',
            ['jquery', 'sweetalert2'],
            '1.0.0',
            true
        );

        $options = get_option('maneli_inquiry_all_options', []);
        $rejection_reasons_raw = $options['cash_inquiry_rejection_reasons'] ?? '';
        $rejection_reasons = array_filter(array_map('trim', explode("\n", $rejection_reasons_raw)));

        wp_localize_script('maneli-inquiry-lists-js', 'maneliInquiryLists', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonces' => [
                'inquiry_filter' => wp_create_nonce('maneli_inquiry_filter_nonce'),
                'cash_filter' => wp_create_nonce('maneli_cash_inquiry_filter_nonce'),
                'details' => wp_create_nonce('maneli_inquiry_details_nonce'),
                'cash_details' => wp_create_nonce('maneli_cash_inquiry_details_nonce'),
                'cash_update' => wp_create_nonce('maneli_cash_inquiry_update_nonce'),
                'cash_delete' => wp_create_nonce('maneli_cash_inquiry_delete_nonce'),
                'cash_set_downpayment' => wp_create_nonce('maneli_cash_set_downpayment_nonce'),
                'cash_assign_expert' => wp_create_nonce('maneli_cash_inquiry_assign_expert_nonce'),
                'assign_expert' => wp_create_nonce('maneli_inquiry_assign_expert_nonce'),
            ],
            'cash_rejection_reasons' => $rejection_reasons,
            'text' => [
                'error' => esc_html__('Error', 'maneli-car-inquiry'),
                'success' => esc_html__('Success', 'maneli-car-inquiry'),
                'confirm_delete_title' => esc_html__('Are you sure you want to delete this request?', 'maneli-car-inquiry'),
                'confirm_delete_text' => esc_html__('This action cannot be undone!', 'maneli-car-inquiry'),
                'confirm_button' => esc_html__('Yes, delete it!', 'maneli-car-inquiry'),
                'cancel_button' => esc_html__('Cancel', 'maneli-car-inquiry'),
            ]
        ]);
    }
}