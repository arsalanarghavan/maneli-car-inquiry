<?php
/**
 * Handles the [maneli_followup_list] shortcode for displaying follow-up inquiries.
 * Shows only inquiries with tracking_status = 'follow_up'.
 *
 * @package Maneli_Car_Inquiry/Includes/Shortcodes
 * @author  Arsalan Arghavan
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Followup_List_Shortcode {

    public function __construct() {
        add_shortcode('maneli_followup_list', [$this, 'render_followup_list']);
    }

    /**
     * Renders the follow-up list shortcode.
     * 
     * @return string HTML output.
     */
    public function render_followup_list() {
        if (!is_user_logged_in()) {
            return maneli_get_template_part('shortcodes/login-prompt', [], false);
        }

        // Only admin and experts can view follow-up list
        if (!current_user_can('manage_maneli_inquiries') && !in_array('maneli_expert', wp_get_current_user()->roles, true)) {
            return '<div class="maneli-inquiry-wrapper error-box"><p>' . esc_html__('You do not have permission to view this page.', 'maneli-car-inquiry') . '</p></div>';
        }

        // If an inquiry_id is present in the URL, show the detailed report (reuse existing report template)
        if (!empty($_GET['inquiry_id'])) {
            $inquiry_id = intval($_GET['inquiry_id']);
            $inquiry = get_post($inquiry_id);
            
            if (!$inquiry || $inquiry->post_type !== 'inquiry' || !Maneli_Permission_Helpers::can_user_view_inquiry($inquiry_id, get_current_user_id())) {
                return '<div class="maneli-inquiry-wrapper error-box"><p>' . esc_html__('Inquiry not found or you do not have permission to view it.', 'maneli-car-inquiry') . '</p></div>';
            }
            
            // Reuse the existing admin report template
            $is_admin_or_expert = current_user_can('manage_maneli_inquiries') || Maneli_Permission_Helpers::is_assigned_expert($inquiry_id, get_current_user_id());
            
            if ($is_admin_or_expert) {
                $this->enqueue_followup_list_assets();
                return maneli_get_template_part('shortcodes/inquiry-lists/report-admin-installment', ['inquiry_id' => $inquiry_id], false);
            } else {
                return '<div class="maneli-inquiry-wrapper error-box"><p>' . esc_html__('Access denied.', 'maneli-car-inquiry') . '</p></div>';
            }
        }

        // Otherwise, show the follow-up list
        $this->enqueue_followup_list_assets();
        return maneli_get_template_part('shortcodes/inquiry-lists/followup-list', [], false);
    }

    /**
     * Enqueues scripts and styles for the follow-up list.
     */
    private function enqueue_followup_list_assets() {
        wp_enqueue_script(
            'maneli-followup-list-js',
            MANELI_INQUIRY_PLUGIN_URL . 'assets/js/frontend/followup-list.js',
            ['jquery', 'sweetalert2'],
            '1.0.0',
            true
        );

        $options = get_option('maneli_inquiry_all_options', []);
        $experts = current_user_can('manage_maneli_inquiries') ? get_users(['role' => 'maneli_expert', 'orderby' => 'display_name', 'order' => 'ASC']) : [];
        
        // Installment Inquiry Rejection Reasons 
        $installment_rejection_reasons_raw = $options['installment_rejection_reasons'] ?? '';
        $installment_rejection_reasons = array_filter(array_map('trim', explode("\n", $installment_rejection_reasons_raw)));

        wp_localize_script('maneli-followup-list-js', 'maneliFollowupList', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonces' => [
                'followup_filter' => wp_create_nonce('maneli_followup_filter_nonce'),
                'details' => wp_create_nonce('maneli_inquiry_details_nonce'),
                'inquiry_delete' => wp_create_nonce('maneli_inquiry_delete_nonce'),
                'assign_expert' => wp_create_nonce('maneli_inquiry_assign_expert_nonce'),
                'tracking_status' => wp_create_nonce('maneli_tracking_status_nonce'),
            ],
            'installment_rejection_reasons' => $installment_rejection_reasons,
            'experts' => array_map(function($expert) {
                return ['id' => $expert->ID, 'name' => $expert->display_name];
            }, $experts),
            'text' => [
                'error' => esc_html__('Error', 'maneli-car-inquiry'),
                'success' => esc_html__('Success', 'maneli-car-inquiry'),
                'confirm_delete_title' => esc_html__('Are you sure you want to delete this request?', 'maneli-car-inquiry'),
                'confirm_delete_text' => esc_html__('This action cannot be undone!', 'maneli-car-inquiry'),
                'delete_list_title' => esc_html__('Delete Request', 'maneli-car-inquiry'),
                'delete_list_text' => esc_html__('Are you sure you want to permanently delete this request?', 'maneli-car-inquiry'),
                'confirm_button' => esc_html__('Yes, delete it!', 'maneli-car-inquiry'),
                'cancel_button' => esc_html__('Cancel', 'maneli-car-inquiry'),
                'assign_title' => esc_html__('Referral Request', 'maneli-car-inquiry'),
                'assign_label' => esc_html__('Select an expert for this request:', 'maneli-car-inquiry'),
                'auto_assign' => esc_html__('-- Automatic Assignment (Round-robin) --', 'maneli-car-inquiry'),
                'assign_button' => esc_html__('Refer', 'maneli-car-inquiry'),
                'select_expert_placeholder' => esc_html__('Select Expert', 'maneli-car-inquiry'),
                'delete_title' => esc_html__('Deleted!', 'maneli-car-inquiry'),
                'delete_button_text' => esc_html__('Delete Request', 'maneli-car-inquiry'),
                'reject_title' => esc_html__('Reject Request', 'maneli-car-inquiry'),
                'reject_label' => esc_html__('Please select a reason for rejection:', 'maneli-car-inquiry'),
                'reject_option_default' => esc_html__('-- Select a reason --', 'maneli-car-inquiry'),
                'reject_option_custom' => esc_html__('Other reason (Custom)', 'maneli-car-inquiry'),
                'reject_placeholder_custom' => esc_html__('Write your custom reason here...', 'maneli-car-inquiry'),
                'reject_submit_button' => esc_html__('Submit Rejection', 'maneli-car-inquiry'),
                'unknown_error' => esc_html__('Unknown error.', 'maneli-car-inquiry'),
                'server_error' => esc_html__('A server error occurred.', 'maneli-car-inquiry'),
                'rejection_reason_required' => esc_html__('Please select or enter a reason for rejection.', 'maneli-car-inquiry'),
            ]
        ]);
    }
}

