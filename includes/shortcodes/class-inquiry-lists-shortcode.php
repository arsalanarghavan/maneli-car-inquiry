<?php
/**
 * Handles shortcodes for displaying lists of inquiries ([maneli_inquiry_list], [maneli_cash_inquiry_list])
 * and the detailed report view for a single inquiry.
 *
 * @package Maneli_Car_Inquiry/Includes/Shortcodes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.2 (Added delete nonce and localization strings for list deletion)
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
            // Note: Assuming maneli_get_template_part is defined elsewhere to load templates
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
        // این تمپلیت صرفاً ساختار جدول و فیلترها را فراهم می‌کند و محتوای اصلی توسط AJAX در inquiry-lists.js بارگذاری می‌شود.
        return maneli_get_template_part('shortcodes/inquiry-lists/admin-installment-list', [], false);
    }

    private function render_admin_cash_inquiry_list() {
        $this->enqueue_admin_list_assets();
        // این تمپلیت صرفاً ساختار جدول و فیلترها را فراهم می‌کند و محتوای اصلی توسط AJAX در inquiry-lists.js بارگذاری می‌شود.
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
            'post_status'    => ['publish', 'private'],
        ]);
        
        $args = ['inquiries_query' => $query, 'current_url' => maneli_get_current_url(['inquiry_id', 'paged'])];
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
            'post_status'    => ['publish', 'private'],
        ]);

        $args = ['inquiries_query' => $query, 'current_url' => maneli_get_current_url(['cash_inquiry_id', 'payment_status', 'paged'])];
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
        
        // Note: Maneli_Permission_Helpers::is_assigned_expert function should be implemented in that class.
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
     * FIX: Added installment_rejection_reasons and localized all hardcoded JS strings.
     * FIX: Ensure localization always runs even if script was already enqueued.
     */
    private function enqueue_admin_list_assets() {
        // توجه: MANELI_INQUIRY_PLUGIN_URL باید قبلاً در فایل اصلی پلاگین تعریف شده باشد.

        // Enqueue Select2 for expert assignment modal
        wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0');
        wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0', true);

        // Enqueue datepicker for tracking status modal
        wp_enqueue_script('maneli-jalali-datepicker', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/vendor/kamadatepicker.min.js', [], '2.1.0', true);
        wp_enqueue_style('maneli-datepicker-theme', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-datepicker-theme.css', [], '1.0.0');

        // Enqueue or register the main script
        if (!wp_script_is('maneli-inquiry-lists-js', 'registered')) {
            wp_register_script(
                'maneli-inquiry-lists-js',
                MANELI_INQUIRY_PLUGIN_URL . 'assets/js/frontend/inquiry-lists.js',
                ['jquery', 'sweetalert2', 'select2', 'maneli-jalali-datepicker'],
                '1.0.0',
                true
            );
        }
        wp_enqueue_script('maneli-inquiry-lists-js');

        $options = get_option('maneli_inquiry_all_options', []);
        
        // Cash Inquiry Rejection Reasons
        $cash_rejection_reasons_raw = $options['cash_inquiry_rejection_reasons'] ?? '';
        $cash_rejection_reasons = array_filter(array_map('trim', explode("\n", $cash_rejection_reasons_raw)));

        // Installment Inquiry Rejection Reasons 
        $installment_rejection_reasons_raw = $options['installment_rejection_reasons'] ?? '';
        $installment_rejection_reasons = array_filter(array_map('trim', explode("\n", $installment_rejection_reasons_raw)));

        // Get all experts for assignment (needed in report pages where filter doesn't exist)
        $experts_list = [];
        $experts_query = get_users(['role' => 'maneli_expert', 'orderby' => 'display_name', 'order' => 'ASC']);
        
        // DEBUG: Log the number of experts found
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('=== Expert Assignment Debug (PHP) ===');
            error_log('Total experts found: ' . count($experts_query));
            error_log('Experts query result: ' . print_r($experts_query, true));
        }
        
        foreach ($experts_query as $expert) {
            $experts_list[] = [
                'id' => $expert->ID,
                'name' => $expert->display_name ?: $expert->user_login
            ];
        }
        
        // DEBUG: Log the final experts list
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Final experts_list count: ' . count($experts_list));
            error_log('Final experts_list: ' . print_r($experts_list, true));
        }

        // IMPORTANT: Force localization data to be set even if called multiple times
        // WordPress caches wp_localize_script, so we use wp_add_inline_script to ensure data is always fresh
        $localized_data = [
            'ajax_url' => admin_url('admin-ajax.php'),
            'experts' => $experts_list,
            'nonces' => [
                'inquiry_filter' => wp_create_nonce('maneli_inquiry_filter_nonce'),
                'cash_filter' => wp_create_nonce('maneli_cash_inquiry_filter_nonce'),
                'details' => wp_create_nonce('maneli_inquiry_details_nonce'),
                'cash_details' => wp_create_nonce('maneli_cash_inquiry_details_nonce'),
                'cash_update' => wp_create_nonce('maneli_cash_inquiry_update_nonce'),
                'cash_delete' => wp_create_nonce('maneli_cash_inquiry_delete_nonce'),
                'inquiry_delete' => wp_create_nonce('maneli_inquiry_delete_nonce'),
                'cash_set_downpayment' => wp_create_nonce('maneli_cash_set_downpayment_nonce'),
                'cash_assign_expert' => wp_create_nonce('maneli_cash_inquiry_assign_expert_nonce'),
                'assign_expert' => wp_create_nonce('maneli_inquiry_assign_expert_nonce'),
                'tracking_status' => wp_create_nonce('maneli_tracking_status_nonce'), // ADDED for tracking status
            ],
            'cash_rejection_reasons' => $cash_rejection_reasons,
            'installment_rejection_reasons' => $installment_rejection_reasons, 
            'text' => [
                'error' => esc_html__('Error', 'maneli-car-inquiry'),
                'success' => esc_html__('Success', 'maneli-car-inquiry'),
                'confirm_delete_title' => esc_html__('Are you sure you want to delete this request?', 'maneli-car-inquiry'),
                'confirm_delete_text' => esc_html__('This action cannot be undone!', 'maneli-car-inquiry'),
                // ADDED strings for list deletion (used in inquiry-lists.js for both types)
                'delete_list_title' => esc_html__('Delete Request', 'maneli-car-inquiry'),
                'delete_list_text' => esc_html__('Are you sure you want to permanently delete this request?', 'maneli-car-inquiry'),
                'confirm_button' => esc_html__('Yes, delete it!', 'maneli-car-inquiry'),
                'cancel_button' => esc_html__('Cancel', 'maneli-car-inquiry'),
                
                // Localized strings for modal and actions in JS
                'assign_title' => esc_html__('Referral Request', 'maneli-car-inquiry'),
                'assign_label' => esc_html__('Select an expert for this request:', 'maneli-car-inquiry'),
                'auto_assign' => esc_html__('-- Automatic Assignment (Round-robin) --', 'maneli-car-inquiry'),
                'assign_button' => esc_html__('Refer', 'maneli-car-inquiry'),
                'select_expert_placeholder' => esc_html__('Select Expert', 'maneli-car-inquiry'),
                'edit_title' => esc_html__('Edit Request', 'maneli-car-inquiry'),
                'placeholder_name' => esc_html__('First Name', 'maneli-car-inquiry'),
                'placeholder_last_name' => esc_html__('Last Name', 'maneli-car-inquiry'),
                'placeholder_mobile' => esc_html__('Mobile', 'maneli-car-inquiry'),
                'placeholder_color' => esc_html__('Car Color', 'maneli-car-inquiry'),
                'save_button' => esc_html__('Save Changes', 'maneli-car-inquiry'),
                'delete_title' => esc_html__('Deleted!', 'maneli-car-inquiry'),
                'delete_button_text' => esc_html__('Delete Request', 'maneli-car-inquiry'),
                'downpayment_title' => esc_html__('Set Down Payment', 'maneli-car-inquiry'),
                'downpayment_placeholder' => esc_html__('Down Payment Amount in Toman', 'maneli-car-inquiry'),
                'downpayment_button' => esc_html__('Confirm and Send to Customer', 'maneli-car-inquiry'),
                'reject_title' => esc_html__('Reject Request', 'maneli-car-inquiry'),
                'reject_label' => esc_html__('Please select a reason for rejection:', 'maneli-car-inquiry'),
                'reject_option_default' => esc_html__('-- Select a reason --', 'maneli-car-inquiry'),
                'reject_option_custom' => esc_html__('Other reason (Custom)', 'maneli-car-inquiry'),
                'reject_placeholder_custom' => esc_html__('Write your custom reason here...', 'maneli-car-inquiry'),
                'reject_submit_button' => esc_html__('Submit Rejection', 'maneli-car-inquiry'),
                'unknown_error' => esc_html__('Unknown error.', 'maneli-car-inquiry'),
                'server_error' => esc_html__('A server error occurred.', 'maneli-car-inquiry'),
                'rejection_reason_required' => esc_html__('Please select or enter a reason for rejection.', 'maneli-car-inquiry'),
                'select_meeting_date' => esc_html__('Select Meeting Date:', 'maneli-car-inquiry'),
                'select_followup_date' => esc_html__('Select Follow-up Date:', 'maneli-car-inquiry'),
                'date_required' => esc_html__('Please select a date.', 'maneli-car-inquiry'),
                'no_experts_available' => esc_html__('No experts found for assignment.', 'maneli-car-inquiry'),
                'select_expert_required' => esc_html__('Please select an expert.', 'maneli-car-inquiry'),
            ]
        ];
        
        // Use both methods to ensure data is always available:
        // 1. Standard wp_localize_script (works if script hasn't been localized yet)
        wp_localize_script('maneli-inquiry-lists-js', 'maneliInquiryLists', $localized_data);
        
        // 2. Force update using inline script (ensures data is always fresh even on report pages)
        $inline_script = 'window.maneliInquiryLists = window.maneliInquiryLists || {};';
        $inline_script .= 'window.maneliInquiryLists.experts = ' . wp_json_encode($experts_list) . ';';
        $inline_script .= 'window.maneliInquiryLists.ajax_url = ' . wp_json_encode(admin_url('admin-ajax.php')) . ';';
        wp_add_inline_script('maneli-inquiry-lists-js', $inline_script, 'before');
    }
}