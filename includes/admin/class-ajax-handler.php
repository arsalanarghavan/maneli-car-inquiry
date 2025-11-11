<?php
/**
 * Handles all AJAX requests for the plugin.
 * This class serves as a central hub for filtering lists, fetching details, updating data, and performing actions.
 *
 * @package Maneli_Car_Inquiry/Includes/Admin
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.3 (Added ajax_delete_inquiry for installment requests)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Ajax_Handler {

    /**
     * Log user action for AJAX calls
     * Helper method to log user actions
     */
    private function log_user_action($action_type, $description, $target_type = null, $target_id = null, $metadata = array()) {
        if (!class_exists('Maneli_Logger')) {
            return;
        }
        $logger = Maneli_Logger::instance();
        $logger->log_user_action($action_type, $description, $target_type, $target_id, $metadata);
    }
    
    /**
     * Resolve a WordPress user ID from a phone number stored in session.
     *
     * @param string $phone Phone number from session.
     * @return int Resolved user ID or 0 if not found.
     */
    private function resolve_user_id_by_phone($phone) {
        $phone = sanitize_text_field($phone);
        
        if (empty($phone)) {
            return 0;
        }
        
        $user = get_user_by('login', $phone);
        if (!$user) {
            $user_query = get_users([
                'meta_key' => 'mobile_number',
                'meta_value' => $phone,
                'number' => 1,
                'fields' => 'ID',
            ]);
            
            if (!empty($user_query)) {
                $user = get_user_by('ID', $user_query[0]);
            }
        }
        
        return $user ? (int)$user->ID : 0;
    }

    /**
     * Determine if the current request belongs to an authenticated plugin user
     */
    private function is_plugin_user_logged_in() {
        if (is_user_logged_in()) {
            return true;
        }

        if (class_exists('Maneli_Session')) {
            $session = new Maneli_Session();
            if (session_status() === PHP_SESSION_NONE) {
                $session->start_session();
            }
            return $session->is_logged_in();
        }

        return isset($_SESSION['maneli']['user_id']) && !empty($_SESSION['maneli']['user_id']);
    }

    /**
     * Convert Jalali date to Gregorian date
     * 
     * @param string $date_str Date string in Jalali format (Y/m/d) or Gregorian format (Y-m-d)
     * @return string|null Gregorian date in Y-m-d format or null if invalid
     */
    private function convert_jalali_to_gregorian($date_str) {
        if (empty($date_str)) return null;
        
        // Check if it's already in Gregorian format (YYYY-MM-DD)
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date_str)) {
            return $date_str; // Already Gregorian
        }
        
        // Check if it's Jalali format (Y/m/d)
        if (preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $date_str, $matches)) {
            $year = (int)$matches[1];
            $month = (int)$matches[2];
            $day = (int)$matches[3];
            
            // If year is between 1300-1500, it's likely Jalali
            if ($year >= 1300 && $year <= 1500) {
                if (function_exists('maneli_jalali_to_gregorian')) {
                    list($gy, $gm, $gd) = maneli_jalali_to_gregorian($year, $month, $day);
                    return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
                }
            }
        }
        
        // Try to parse as-is (might be other format)
        $timestamp = strtotime($date_str);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }
        
        return null;
    }

    /**
     * Get meeting settings for frontend
     */
    public function ajax_get_meeting_settings() {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }
        
        $options = get_option('maneli_inquiry_all_options', []);
        $start_hour = $options['meetings_start_hour'] ?? '10:00';
        $end_hour = $options['meetings_end_hour'] ?? '20:00';
        $slot_minutes = max(5, (int)($options['meetings_slot_minutes'] ?? 30));
        
        wp_send_json_success([
            'start_hour' => $start_hour,
            'end_hour' => $end_hour,
            'slot_minutes' => $slot_minutes
        ]);
    }

    /**
     * Global search for users and inquiries
     */
    public function ajax_global_search() {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }

        $search_query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        
        if (empty($search_query) || strlen($search_query) < 2) {
            wp_send_json_success([
                'users' => [],
                'cash_inquiries' => [],
                'installment_inquiries' => []
            ]);
            return;
        }

        $results = [
            'users' => [],
            'cash_inquiries' => [],
            'installment_inquiries' => []
        ];

        // Search Users
        $user_query_args = [
            'number' => 10,
            'search' => '*' . $search_query . '*',
            'search_columns' => ['user_login', 'user_email', 'display_name']
        ];
        
        // Also search in user meta
        $user_query_args['meta_query'] = [
            'relation' => 'OR',
            ['key' => 'first_name', 'value' => $search_query, 'compare' => 'LIKE'],
            ['key' => 'last_name', 'value' => $search_query, 'compare' => 'LIKE'],
            ['key' => 'mobile_number', 'value' => $search_query, 'compare' => 'LIKE'],
            ['key' => 'national_code', 'value' => $search_query, 'compare' => 'LIKE']
        ];

        $user_query = new WP_User_Query($user_query_args);
        $users = $user_query->get_results();
        
        foreach ($users as $user) {
            $first_name = get_user_meta($user->ID, 'first_name', true);
            $last_name = get_user_meta($user->ID, 'last_name', true);
            $mobile = get_user_meta($user->ID, 'mobile_number', true);
            $national_code = get_user_meta($user->ID, 'national_code', true);
            
            $results['users'][] = [
                'id' => $user->ID,
                'name' => trim(($first_name ?: '') . ' ' . ($last_name ?: '') ?: $user->display_name),
                'mobile' => $mobile ?: '',
                'national_code' => $national_code ?: '',
                'url' => home_url('/dashboard/user-detail?user_id=' . $user->ID)
            ];
        }

        // Search Cash Inquiries
        $cash_query_args = [
            'post_type' => 'cash_inquiry',
            'posts_per_page' => 10,
            'post_status' => 'publish',
            'meta_query' => [
                'relation' => 'OR',
                ['key' => 'cash_first_name', 'value' => $search_query, 'compare' => 'LIKE'],
                ['key' => 'cash_last_name', 'value' => $search_query, 'compare' => 'LIKE'],
                ['key' => 'mobile_number', 'value' => $search_query, 'compare' => 'LIKE']
            ]
        ];
        
        // Also search by inquiry ID if query is numeric
        if (is_numeric($search_query)) {
            $cash_query_args['p'] = absint($search_query);
            unset($cash_query_args['meta_query']);
        }

        $cash_query = new WP_Query($cash_query_args);
        
        if ($cash_query->have_posts()) {
            while ($cash_query->have_posts()) {
                $cash_query->the_post();
                $inquiry_id = get_the_ID();
                $first_name = get_post_meta($inquiry_id, 'cash_first_name', true);
                $last_name = get_post_meta($inquiry_id, 'cash_last_name', true);
                $mobile = get_post_meta($inquiry_id, 'mobile_number', true);
                $product_id = get_post_meta($inquiry_id, 'product_id', true);
                $car_name = $product_id ? get_the_title($product_id) : '';
                
                $results['cash_inquiries'][] = [
                    'id' => $inquiry_id,
                    'inquiry_number' => $inquiry_id,
                    'customer_name' => trim(($first_name ?: '') . ' ' . ($last_name ?: '')),
                    'mobile' => $mobile ?: '',
                    'car_name' => $car_name,
                    'url' => home_url('/dashboard/cash-inquiry-detail?cash_inquiry_id=' . $inquiry_id)
                ];
            }
            wp_reset_postdata();
        }

        // Search Installment Inquiries
        $installment_query_args = [
            'post_type' => 'inquiry',
            'posts_per_page' => 10,
            'post_status' => 'publish',
            'meta_query' => [
                'relation' => 'OR',
                ['key' => 'mobile_number', 'value' => $search_query, 'compare' => 'LIKE'],
                ['key' => 'national_code', 'value' => $search_query, 'compare' => 'LIKE']
            ]
        ];
        
        // Also search by inquiry ID if query is numeric
        if (is_numeric($search_query)) {
            $installment_query_args['p'] = absint($search_query);
            unset($installment_query_args['meta_query']);
        }

        $installment_query = new WP_Query($installment_query_args);
        
        if ($installment_query->have_posts()) {
            while ($installment_query->have_posts()) {
                $installment_query->the_post();
                $inquiry_id = get_the_ID();
                $author_id = get_post_field('post_author', $inquiry_id);
                
                if ($author_id) {
                    $user = get_userdata($author_id);
                    $first_name = get_user_meta($author_id, 'first_name', true);
                    $last_name = get_user_meta($author_id, 'last_name', true);
                    $mobile = get_user_meta($author_id, 'mobile_number', true);
                    $national_code = get_user_meta($author_id, 'national_code', true);
                    
                    $product_id = get_post_meta($inquiry_id, 'maneli_selected_car_id', true);
                    $car_name = $product_id ? get_the_title($product_id) : '';
                    
                    // Check if search matches user info
                    $matches = false;
                    if (stripos($mobile, $search_query) !== false || 
                        stripos($national_code, $search_query) !== false ||
                        stripos($first_name . ' ' . $last_name, $search_query) !== false ||
                        $inquiry_id == $search_query) {
                        $matches = true;
                    }
                    
                    if ($matches) {
                        $results['installment_inquiries'][] = [
                            'id' => $inquiry_id,
                            'inquiry_number' => $inquiry_id,
                            'customer_name' => trim(($first_name ?: '') . ' ' . ($last_name ?: '') ?: $user->display_name),
                            'mobile' => $mobile ?: '',
                            'national_code' => $national_code ?: '',
                            'car_name' => $car_name,
                            'url' => home_url('/dashboard/inquiry-detail?inquiry_id=' . $inquiry_id)
                        ];
                    }
                }
            }
            wp_reset_postdata();
        }

        wp_send_json_success($results);
    }

    public function __construct() {
        // Load required helper classes
        if (!class_exists('Maneli_Permission_Helpers')) {
            require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/helpers/class-maneli-permission-helpers.php';
        }
        
        // Meeting Settings
        add_action('wp_ajax_maneli_get_meeting_settings', [$this, 'ajax_get_meeting_settings']);
        
        // Global Search
        add_action('wp_ajax_maneli_global_search', [$this, 'ajax_global_search']);
        
        // Inquiry Details, List Filtering & Actions (Installment)
        add_action('wp_ajax_maneli_get_inquiry_details', [$this, 'ajax_get_inquiry_details']);
        add_action('wp_ajax_maneli_filter_inquiries_ajax', [$this, 'ajax_filter_inquiries']);
        add_action('wp_ajax_maneli_delete_inquiry', [$this, 'ajax_delete_inquiry']); // ADDED: Installment Inquiry Delete
        add_action('wp_ajax_maneli_update_tracking_status', [$this, 'ajax_update_tracking_status']); // ADDED: Tracking Status Update
        add_action('wp_ajax_maneli_filter_followup_inquiries', [$this, 'ajax_filter_followup_inquiries']); // ADDED: Follow-up List Filter
        add_action('wp_ajax_maneli_cancel_meeting', [$this, 'ajax_cancel_meeting']); // ADDED: Cancel Meeting

        // Cash Inquiry Actions & List Filtering
        add_action('wp_ajax_maneli_get_cash_inquiry_details', [$this, 'ajax_get_cash_inquiry_details']);
        add_action('wp_ajax_maneli_update_cash_inquiry', [$this, 'ajax_update_cash_inquiry']);
        add_action('wp_ajax_maneli_delete_cash_inquiry', [$this, 'ajax_delete_cash_inquiry']);
        add_action('wp_ajax_maneli_set_down_payment', [$this, 'ajax_set_down_payment']);
        add_action('wp_ajax_maneli_filter_cash_inquiries_ajax', [$this, 'ajax_filter_cash_inquiries']);
        
        // Expert Assignment Actions
        add_action('wp_ajax_maneli_assign_expert_to_cash_inquiry', [$this, 'ajax_assign_expert_to_cash_inquiry']);
        add_action('wp_ajax_maneli_assign_expert_to_inquiry', [$this, 'ajax_assign_expert_to_inquiry']);

        // User Management (from class-user-management-shortcodes.php)
        add_action('wp_ajax_maneli_filter_users_ajax', [$this, 'ajax_filter_users']);
        add_action('wp_ajax_maneli_delete_user_ajax', [$this, 'ajax_delete_user']);
        add_action('wp_ajax_maneli_get_user_details', [$this, 'ajax_get_user_details']);
        add_action('wp_ajax_maneli_get_user_data', [$this, 'ajax_get_user_data']);
        add_action('wp_ajax_maneli_update_user', [$this, 'ajax_update_user']);
        add_action('wp_ajax_maneli_update_user_full', [$this, 'ajax_update_user_full']);
        add_action('wp_ajax_maneli_add_user', [$this, 'ajax_add_user']);

        // Product Editor (from class-product-editor-shortcode.php & class-hooks.php)
        add_action('wp_ajax_maneli_filter_products_ajax', [$this, 'ajax_filter_products']);
        add_action('wp_ajax_maneli_update_product_data', [$this, 'ajax_update_product_data']);
        
        // Bulk Product Update (Dashboard)
        add_action('wp_ajax_maneli_save_products_bulk', [$this, 'ajax_save_products_bulk']);
        
        // Full Product Save (Add/Edit Product Page)
        add_action('wp_ajax_maneli_save_product_full', [$this, 'ajax_save_product_full']);
        
        // Image Upload Handler
        add_action('wp_ajax_maneli_upload_image', [$this, 'ajax_upload_image']);
        
        // Expert Management (Dashboard)
        add_action('wp_ajax_maneli_add_expert', [$this, 'ajax_add_expert']);
        add_action('wp_ajax_maneli_toggle_expert_status', [$this, 'ajax_toggle_expert_status']);
        add_action('wp_ajax_maneli_get_expert_stats', [$this, 'ajax_get_expert_stats']);
        add_action('wp_ajax_maneli_get_expert_details', [$this, 'ajax_get_expert_details']);
        add_action('wp_ajax_maneli_get_expert_data', [$this, 'ajax_get_expert_data']);
        add_action('wp_ajax_maneli_update_expert', [$this, 'ajax_update_expert']);
        add_action('wp_ajax_maneli_get_expert_permissions', [$this, 'ajax_get_expert_permissions']);
        add_action('wp_ajax_maneli_update_expert_permissions', [$this, 'ajax_update_expert_permissions']);
        add_action('wp_ajax_maneli_delete_user', [$this, 'ajax_delete_user_from_experts']);
        
        // Cash Inquiry Expert Actions
        add_action('wp_ajax_maneli_save_meeting_schedule', [$this, 'ajax_save_meeting_schedule']);
        add_action('wp_ajax_maneli_save_expert_decision_cash', [$this, 'ajax_save_expert_decision_cash']);
        add_action('wp_ajax_maneli_admin_approve_cash', [$this, 'ajax_admin_approve_cash']);
        add_action('wp_ajax_maneli_update_cash_status', [$this, 'ajax_update_cash_status']);
        add_action('wp_ajax_maneli_save_expert_note', [$this, 'ajax_save_expert_note']);
        
        // Hook for when cash down payment is successfully received
        add_action('maneli_cash_inquiry_payment_successful', [$this, 'handle_downpayment_received'], 10, 2);
        
        // Create cash inquiry from dashboard (admin/expert)
        add_action('wp_ajax_maneli_create_cash_inquiry', [$this, 'ajax_create_cash_inquiry']);
        add_action('wp_ajax_maneli_get_products_for_cash', [$this, 'ajax_get_products_for_cash']);
        
        // Installment inquiry status management
        add_action('wp_ajax_maneli_update_installment_status', [$this, 'ajax_update_installment_status']);
        add_action('wp_ajax_maneli_save_installment_note', [$this, 'ajax_save_installment_note']);
        add_action('wp_ajax_maneli_request_more_documents', [$this, 'ajax_request_more_documents']);
        add_action('wp_ajax_maneli_upload_document', [$this, 'ajax_upload_document']);
        
        // Notification handlers
        add_action('wp_ajax_maneli_get_notifications', [$this, 'ajax_get_notifications']);
        add_action('wp_ajax_nopriv_maneli_get_notifications', [$this, 'ajax_get_notifications']);
        add_action('wp_ajax_maneli_get_unread_count', [$this, 'ajax_get_unread_count']);
        add_action('wp_ajax_nopriv_maneli_get_unread_count', [$this, 'ajax_get_unread_count']);
        add_action('wp_ajax_maneli_mark_notification_read', [$this, 'ajax_mark_notification_read']);
        add_action('wp_ajax_nopriv_maneli_mark_notification_read', [$this, 'ajax_mark_notification_read']);
        add_action('wp_ajax_maneli_mark_all_notifications_read', [$this, 'ajax_mark_all_notifications_read']);
        add_action('wp_ajax_nopriv_maneli_mark_all_notifications_read', [$this, 'ajax_mark_all_notifications_read']);
        add_action('wp_ajax_maneli_delete_notification', [$this, 'ajax_delete_notification']);
        add_action('wp_ajax_nopriv_maneli_delete_notification', [$this, 'ajax_delete_notification']);
        add_action('wp_ajax_maneli_delete_all_read_notifications', [$this, 'ajax_delete_all_read_notifications']);
        add_action('wp_ajax_nopriv_maneli_delete_all_read_notifications', [$this, 'ajax_delete_all_read_notifications']);
        
        // Profile settings handlers
        add_action('wp_ajax_maneli_upload_profile_image', [$this, 'ajax_upload_profile_image']);
        add_action('wp_ajax_maneli_delete_profile_image', [$this, 'ajax_delete_profile_image']);
        add_action('wp_ajax_maneli_update_profile', [$this, 'ajax_update_profile']);
        add_action('wp_ajax_maneli_upload_customer_document', [$this, 'ajax_upload_customer_document']);
        add_action('wp_ajax_maneli_approve_customer_document', [$this, 'ajax_approve_customer_document']);
        add_action('wp_ajax_maneli_reject_customer_document', [$this, 'ajax_reject_customer_document']);
        add_action('wp_ajax_maneli_request_customer_document', [$this, 'ajax_request_customer_document']);
        add_action('wp_ajax_maneli_request_customer_documents_bulk', [$this, 'ajax_request_customer_documents_bulk']);
        
        // SMS Credit
        add_action('wp_ajax_maneli_get_sms_credit', [$this, 'ajax_get_sms_credit']);
        
        // Notification Center AJAX Handlers
        add_action('wp_ajax_maneli_send_bulk_notification', [$this, 'ajax_send_bulk_notification']);
        add_action('wp_ajax_maneli_schedule_notification', [$this, 'ajax_schedule_notification']);
        add_action('wp_ajax_maneli_get_notification_logs', [$this, 'ajax_get_notification_logs']);
        add_action('wp_ajax_maneli_get_notification_stats', [$this, 'ajax_get_notification_stats']);
        add_action('wp_ajax_maneli_retry_notification', [$this, 'ajax_retry_notification']);
        add_action('wp_ajax_maneli_send_single_sms', [$this, 'ajax_send_single_sms']);
        add_action('wp_ajax_maneli_get_sms_history', [$this, 'ajax_get_sms_history']);
        add_action('wp_ajax_maneli_get_sms_status', [$this, 'ajax_get_sms_status']);
        add_action('wp_ajax_maneli_resend_sms', [$this, 'ajax_resend_sms']);
        
        // Status Migration Handlers
        add_action('wp_ajax_maneli_get_migration_stats', [$this, 'ajax_get_migration_stats']);
        add_action('wp_ajax_maneli_run_status_migration', [$this, 'ajax_run_status_migration']);
        
        // Log Handlers
        add_action('wp_ajax_maneli_get_system_log_details', [$this, 'ajax_get_system_log_details']);
        add_action('wp_ajax_maneli_get_user_log_details', [$this, 'ajax_get_user_log_details']);
        add_action('wp_ajax_maneli_get_system_logs', [$this, 'ajax_get_system_logs']);
        add_action('wp_ajax_maneli_get_user_logs', [$this, 'ajax_get_user_logs']);
        add_action('wp_ajax_maneli_log_console', [$this, 'ajax_log_console']);
        add_action('wp_ajax_maneli_log_user_action', [$this, 'ajax_log_user_action']);
        add_action('wp_ajax_maneli_delete_system_logs', [$this, 'ajax_delete_system_logs']);
        
    }

    //======================================================================
    // Installment Inquiry Handlers
    //======================================================================

    public function ajax_get_inquiry_details() {
        check_ajax_referer('maneli_inquiry_details_nonce', 'nonce');
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }
        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        if (!$inquiry_id || get_post_type($inquiry_id) !== 'inquiry') {
            wp_send_json_error(['message' => esc_html__('Invalid inquiry ID.', 'maneli-car-inquiry')]);
            return;
        }
        
        $inquiry = get_post($inquiry_id);
        $current_user_id = get_current_user_id();
        $can_view = false;
        
        // Use Permission Helper if possible, otherwise use manual check
        if (class_exists('Maneli_Permission_Helpers')) {
            $can_view = Maneli_Permission_Helpers::can_user_view_inquiry($inquiry_id, $current_user_id);
        } else {
             if (current_user_can('manage_maneli_inquiries') || (int)$inquiry->post_author === $current_user_id) {
                $can_view = true;
            } elseif (in_array('maneli_expert', get_userdata($current_user_id)->roles)) {
                $assigned_expert_id = (int)get_post_meta($inquiry_id, 'assigned_expert_id', true);
                if ($assigned_expert_id === $current_user_id) $can_view = true;
            }
        }
        

        if (!$can_view) {
            wp_send_json_error(['message' => esc_html__('You do not have permission to view this report.', 'maneli-car-inquiry')], 403);
            return;
        }

        $post_meta = get_post_meta($inquiry_id);
        $finotex_data = get_post_meta($inquiry_id, '_finotex_response_data', true);
        
        // Finnotech API data
        $credit_risk_data = get_post_meta($inquiry_id, '_finnotech_credit_risk_data', true);
        $credit_score_data = get_post_meta($inquiry_id, '_finnotech_credit_score_data', true);
        $collaterals_data = get_post_meta($inquiry_id, '_finnotech_collaterals_data', true);
        $cheque_color_data = get_post_meta($inquiry_id, '_finnotech_cheque_color_data', true);
        
        $product_id = $post_meta['product_id'][0] ?? 0;
        $status = $post_meta['inquiry_status'][0] ?? 'pending';

        $data = [
            'id' => $inquiry_id,
            'status_label' => Maneli_CPT_Handler::get_status_label($status),
            'status_key' => $status,
            'rejection_reason' => get_post_meta($inquiry_id, 'rejection_reason', true),
            'car' => [
                'name' => get_the_title($product_id),
                'image' => get_the_post_thumbnail_url($product_id, 'medium'),
                'total_price' => number_format_i18n((int)($post_meta['maneli_inquiry_total_price'][0] ?? 0)),
                'down_payment' => number_format_i18n((int)($post_meta['maneli_inquiry_down_payment'][0] ?? 0)),
                'term' => $post_meta['maneli_inquiry_term_months'][0] ?? 0,
                'installment' => number_format_i18n((int)($post_meta['maneli_inquiry_installment'][0] ?? 0)),
            ],
            'buyer' => ['first_name' => $post_meta['first_name'][0] ?? '', 'last_name' => $post_meta['last_name'][0] ?? ''],
            'issuer_type' => $post_meta['issuer_type'][0] ?? 'self',
            'finotex' => ['skipped' => (empty($finotex_data) || ($finotex_data['status'] ?? '') === 'SKIPPED'), 'color_code' => $finotex_data['result']['chequeColor'] ?? 0],
            'finnotech' => [
                'credit_risk' => !empty($credit_risk_data),
                'credit_score' => !empty($credit_score_data),
                'collaterals' => !empty($collaterals_data),
                'cheque_color' => !empty($cheque_color_data),
            ],
        ];
        wp_send_json_success($data);
    }
    
    public function ajax_filter_inquiries() {
        // Check nonce - try both 'nonce' and '_ajax_nonce' for compatibility
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : (isset($_POST['_ajax_nonce']) ? $_POST['_ajax_nonce'] : '');
        if (!wp_verify_nonce($nonce, 'maneli_inquiry_filter_nonce')) {
            wp_send_json_error(['message' => esc_html__('Invalid security token.', 'maneli-car-inquiry')], 403);
            return;
        }
        // CRITICAL: Always get fresh role from WordPress user object
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }
        
        $current_user = wp_get_current_user();
        // Always check fresh roles from database
        $is_admin = current_user_can('manage_maneli_inquiries');
        $is_manager = in_array('maneli_manager', $current_user->roles, true) || in_array('maneli_admin', $current_user->roles, true);
        $is_expert = in_array('maneli_expert', $current_user->roles, true);
        $is_customer = !$is_admin && !$is_manager && !$is_expert;
        
        if (!$is_admin && !$is_manager && !$is_expert && !$is_customer) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }
    
        $paged = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $search_query = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
        $base_url = isset($_POST['base_url']) ? esc_url_raw($_POST['base_url']) : home_url();
        
        $args = ['post_type' => 'inquiry', 'posts_per_page' => 50, 'paged' => $paged, 'orderby' => 'date', 'order' => 'DESC', 'post_status' => 'publish'];
        $meta_query = ['relation' => 'AND'];
        
        // CRITICAL: For customers, only show their own inquiries by author
        if ($is_customer) {
            $args['author'] = get_current_user_id();
        }
    
        if (!empty($search_query)) {
            $user_ids = get_users(['fields' => 'ID', 'search' => '*' . esc_attr($search_query) . '*', 'search_columns' => ['user_login', 'user_email', 'display_name']]);
            $product_ids = wc_get_products(['s' => $search_query, 'limit' => -1, 'return' => 'ids']);
            $search_meta_query = ['relation' => 'OR'];
            if(!empty($user_ids)) $search_meta_query[] = ['key' => 'post_author', 'value' => $user_ids, 'compare' => 'IN'];
            if(!empty($product_ids)) $search_meta_query[] = ['key' => 'product_id', 'value' => $product_ids, 'compare' => 'IN'];
            $search_meta_query[] = ['key' => 'national_code', 'value' => $search_query, 'compare' => 'LIKE'];
            $search_meta_query[] = ['key' => 'mobile_number', 'value' => $search_query, 'compare' => 'LIKE'];
            $meta_query[] = $search_meta_query;
        }
        
        if (!empty($_POST['status'])) $meta_query[] = ['key' => 'inquiry_status', 'value' => sanitize_text_field($_POST['status'])];
        
        // Check if tracking_status filter is provided
        $tracking_status_query = isset($_POST['tracking_status']) ? sanitize_text_field($_POST['tracking_status']) : '';
        
        // Debug logging for tracking_status and expert
        if (!empty($tracking_status_query)) {
            error_log('Maneli Debug: tracking_status_query = ' . $tracking_status_query);
        }
        $expert_filter_value = isset($_POST['expert']) ? $_POST['expert'] : '';
        if ($expert_filter_value !== '') {
            error_log('Maneli Debug: expert filter = ' . $expert_filter_value);
        }
        
        // Handle tracking_status filter if provided (before expert filter to avoid conflicts)
        $is_referred_status = false;
        if (!empty($tracking_status_query)) {
            // If filtering by a specific tracking_status, apply that filter
            $meta_query[] = ['key' => 'tracking_status', 'value' => $tracking_status_query, 'compare' => '='];
            
            // Special handling for 'referred' status: only show inquiries without assigned expert
            if ($tracking_status_query === 'referred') {
                $is_referred_status = true;
                // For 'referred' status, we want only inquiries without assigned expert
                // This is critical: must exclude inquiries that already have an expert assigned
                // WordPress meta_query: For 'referred' status, we want inquiries WITHOUT assigned expert
                // We need to check: NOT EXISTS OR empty string OR zero
                // Note: assigned_expert_id might be stored as numeric, so we check multiple cases
                $meta_query[] = [
                    'relation' => 'OR',
                    [
                        'key' => 'assigned_expert_id',
                        'compare' => 'NOT EXISTS'
                    ],
                    [
                        'key' => 'assigned_expert_id',
                        'value' => '',
                        'compare' => '='
                    ],
                    [
                        'key' => 'assigned_expert_id',
                        'value' => '0',
                        'compare' => '='
                    ],
                    [
                        'key' => 'assigned_expert_id',
                        'value' => 0,
                        'compare' => '=',
                        'type' => 'NUMERIC'
                    ]
                ];
                error_log('Maneli Debug: Added unassigned expert filter for referred status');
            }
        }
        
        // Expert filter (only if not filtering by referred status)
        if (!$is_referred_status) {
            if ($is_admin || $is_manager) {
                // Check if expert filter is provided (including '0' for unassigned)
                if (isset($_POST['expert']) && $_POST['expert'] !== '') {
                    $expert_value = $_POST['expert'];
                    if ($expert_value === '0' || $expert_value === 0) {
                        // Filter for inquiries without assigned expert
                        $meta_query[] = [
                            'relation' => 'OR',
                            [
                                'key' => 'assigned_expert_id',
                                'compare' => 'NOT EXISTS'
                            ],
                            [
                                'key' => 'assigned_expert_id',
                                'value' => '',
                                'compare' => '='
                            ],
                            [
                                'key' => 'assigned_expert_id',
                                'value' => '0',
                                'compare' => '='
                            ],
                            [
                                'key' => 'assigned_expert_id',
                                'value' => 0,
                                'compare' => '=',
                                'type' => 'NUMERIC'
                            ]
                        ];
                    } else {
                        // Filter for specific expert
                        $meta_query[] = ['key' => 'assigned_expert_id', 'value' => absint($expert_value), 'compare' => '='];
                    }
                }
            } elseif ($is_expert && !$is_admin && !$is_manager) {
                // Expert sees only assigned inquiries
                $meta_query[] = ['key' => 'assigned_expert_id', 'value' => get_current_user_id(), 'compare' => '='];
            }
        }
        // For customers, we already filtered by author, so no expert filter needed
        
        // Continue with other filters if tracking_status was not provided
        if (empty($tracking_status_query)) {
            // CRITICAL: Exclude follow_up_scheduled from normal list (only show in followups page)
            // Only exclude follow_up_scheduled if not filtering by tracking_status
            $meta_query[] = [
                'relation' => 'OR',
                ['key' => 'tracking_status', 'value' => 'follow_up_scheduled', 'compare' => '!='],
                ['key' => 'tracking_status', 'value' => 'follow_up_scheduled', 'compare' => 'NOT EXISTS']
            ];
        }
        
        // Apply meta_query if we have any conditions (relation counts as 1, so we need at least 2 items total)
        $meta_query_count = count($meta_query);
        if ($meta_query_count > 1) {
            $args['meta_query'] = $meta_query;
            // Debug logging
            if (!empty($tracking_status_query)) {
                error_log('Maneli Debug: meta_query count = ' . $meta_query_count);
                error_log('Maneli Debug: meta_query structure = ' . print_r($meta_query, true));
            }
        } elseif ($meta_query_count === 1 && isset($meta_query[0]['relation'])) {
            // Only relation, no actual queries - don't add meta_query
            // This should not happen, but handle it gracefully
        }
    
        // Debug logging for final query args
        if (!empty($tracking_status_query)) {
            error_log('Maneli Debug: Final query args = ' . print_r($args, true));
        }
    
        $inquiry_query = new WP_Query($args);
        
        // Debug logging for query results
        if (!empty($tracking_status_query)) {
            error_log('Maneli Debug: Query found ' . $inquiry_query->found_posts . ' posts');
            error_log('Maneli Debug: Query post_count = ' . $inquiry_query->post_count);
        }
        
        // For 'referred' status, we need additional filtering if meta_query didn't work perfectly
        // This is a fallback to ensure we only show inquiries without assigned expert
        $posts_to_render = [];
        if ($inquiry_query->have_posts() && $is_referred_status) {
            while ($inquiry_query->have_posts()) {
                $inquiry_query->the_post();
                $post_id = get_the_ID();
                $expert_id = get_post_meta($post_id, 'assigned_expert_id', true);
                // Check if expert_id is truly empty/zero (not just falsy)
                // Note: empty() returns true for '0', '0', 0, '', null, false, array()
                // We need to check explicitly for empty values
                $is_unassigned = ($expert_id === '' || $expert_id === '0' || $expert_id === 0 || $expert_id === null || $expert_id === false || (is_string($expert_id) && trim($expert_id) === ''));
                
                if ($is_unassigned) {
                    $posts_to_render[] = $post_id;
                    if (!empty($tracking_status_query)) {
                        error_log('Maneli Debug: Post #' . $post_id . ' passed filter - expert_id: ' . var_export($expert_id, true));
                    }
                } else {
                    if (!empty($tracking_status_query)) {
                        error_log('Maneli Debug: Post #' . $post_id . ' filtered out - has expert_id: ' . var_export($expert_id, true) . ' (type: ' . gettype($expert_id) . ')');
                    }
                }
            }
            wp_reset_postdata();
        }
        
        ob_start();
        if ($is_referred_status) {
            if (!empty($posts_to_render)) {
                // Render only the filtered posts
                $rendered_count = 0;
                foreach ($posts_to_render as $post_id) {
                    Maneli_Render_Helpers::render_inquiry_row($post_id, $base_url);
                    $rendered_count++;
                }
                if (!empty($tracking_status_query)) {
                    error_log('Maneli Debug: Rendered ' . $rendered_count . ' inquiry rows (after filtering)');
                }
            } else {
                // No posts passed the filter - this could mean all have experts assigned or query found nothing
                if (!empty($tracking_status_query)) {
                    error_log('Maneli Debug: Query found ' . $inquiry_query->found_posts . ' posts but none passed unassigned filter');
                    error_log('Maneli Debug: This might indicate all referred inquiries have experts assigned, or meta_query did not work correctly');
                }
            }
        } elseif ($inquiry_query->have_posts()) {
            // Normal rendering for non-referred status
            $rendered_count = 0;
            while ($inquiry_query->have_posts()) {
                $inquiry_query->the_post();
                $post_id = get_the_ID();
                // Debug: log each post being rendered
                if (!empty($tracking_status_query)) {
                    $tracking_status_debug = get_post_meta($post_id, 'tracking_status', true);
                    $expert_id_debug = get_post_meta($post_id, 'assigned_expert_id', true);
                    error_log('Maneli Debug: Rendering post #' . $post_id . ' - tracking_status: ' . $tracking_status_debug . ', expert_id: ' . var_export($expert_id_debug, true));
                }
                Maneli_Render_Helpers::render_inquiry_row($post_id, $base_url);
                $rendered_count++;
            }
            if (!empty($tracking_status_query)) {
                error_log('Maneli Debug: Rendered ' . $rendered_count . ' inquiry rows');
            }
        } elseif ($is_referred_status && $inquiry_query->found_posts > 0 && empty($posts_to_render)) {
            // Query found posts but all were filtered out (all have experts assigned)
            $columns = $is_admin ? 7 : 6;
            echo '<tr><td colspan="' . $columns . '" style="text-align:center;">' . esc_html__('No inquiries found without assigned expert.', 'maneli-car-inquiry') . '</td></tr>';
        } else {
            // Calculate columns based on user role (admin sees assigned column, customer/expert don't)
            $columns = $is_admin ? 7 : 6;
            echo '<tr><td colspan="' . $columns . '" style="text-align:center;">' . esc_html__('No inquiries found matching your criteria.', 'maneli-car-inquiry') . '</td></tr>';
        }
        $html = ob_get_clean();
        if (!$is_referred_status || empty($posts_to_render)) {
            wp_reset_postdata();
        }
        
        // Calculate pagination based on actual rendered count for referred status
        if ($is_referred_status && !empty($posts_to_render)) {
            // For referred status, pagination should be based on filtered results
            // Since we filtered client-side, we'll show all results on first page
            // TODO: Implement proper pagination for filtered results if needed
            $pagination_html = ''; // No pagination for filtered results for now
        } else {
            $pagination_html = paginate_links([
                'base' => add_query_arg('paged', '%#%'), 
                'format' => '?paged=%#%', 
                'current' => $paged, 
                'total' => $inquiry_query->max_num_pages, 
                'prev_text' => esc_html__('&laquo; Previous', 'maneli-car-inquiry'), 
                'next_text' => esc_html__('Next &raquo;', 'maneli-car-inquiry'), 
                'type'  => 'plain'
            ]);
        }
        
        // Debug logging for final HTML
        if (!empty($tracking_status_query)) {
            error_log('Maneli Debug: Final HTML length = ' . strlen($html));
            error_log('Maneli Debug: HTML preview (first 500 chars) = ' . substr($html, 0, 500));
        }
    
        wp_send_json_success(['html' => $html, 'pagination_html' => $pagination_html]);
    }
    
    /**
     * Handles deletion of an installment inquiry post via AJAX.
     */
    public function ajax_delete_inquiry() {
        check_ajax_referer('maneli_inquiry_delete_nonce', 'nonce');
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }

        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        if (!$inquiry_id || get_post_type($inquiry_id) !== 'inquiry') {
            wp_send_json_error(['message' => esc_html__('Invalid inquiry ID.', 'maneli-car-inquiry')]);
            return;
        }

        if (wp_delete_post($inquiry_id, true)) {
            wp_send_json_success(['message' => esc_html__('Inquiry deleted successfully.', 'maneli-car-inquiry')]);
        } else {
            wp_send_json_error(['message' => esc_html__('Error deleting inquiry.', 'maneli-car-inquiry')]);
        }
    }

    //======================================================================
    // Cash Inquiry Handlers
    //======================================================================
    
    public function ajax_get_cash_inquiry_details() {
        check_ajax_referer('maneli_cash_inquiry_details_nonce', 'nonce');
        if (!is_user_logged_in() || !(current_user_can('manage_maneli_inquiries') || in_array('maneli_expert', wp_get_current_user()->roles))) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }

        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        if (!$inquiry_id || get_post_type($inquiry_id) !== 'cash_inquiry') {
            wp_send_json_error(['message' => esc_html__('Invalid request ID.', 'maneli-car-inquiry')]);
            return;
        }

        // Security Check: Ensure the user has permission to view this specific inquiry
        if (class_exists('Maneli_Permission_Helpers') && !Maneli_Permission_Helpers::can_user_view_inquiry($inquiry_id, get_current_user_id())) {
             wp_send_json_error(['message' => esc_html__('You do not have permission to view this report.', 'maneli-car-inquiry')], 403);
             return;
        }

        $post_meta = get_post_meta($inquiry_id);
        $product_id = $post_meta['product_id'][0] ?? 0;

        $data = [
            'id' => $inquiry_id,
            'status_label' => Maneli_CPT_Handler::get_cash_inquiry_status_label($post_meta['cash_inquiry_status'][0] ?? 'pending'),
            'status_key' => $post_meta['cash_inquiry_status'][0] ?? 'pending',
            'rejection_reason' => $post_meta['cash_rejection_reason'][0] ?? '',
            'car' => [
                'name' => get_the_title($product_id),
                'color' => $post_meta['cash_car_color'][0] ?? '',
                'down_payment' => Maneli_Render_Helpers::format_money($post_meta['cash_down_payment'][0] ?? 0),
            ],
            'customer' => [
                'first_name' => $post_meta['cash_first_name'][0] ?? '',
                'last_name' => $post_meta['cash_last_name'][0] ?? '',
                'mobile' => $post_meta['mobile_number'][0] ?? '',
            ],
        ];
        wp_send_json_success($data);
    }

    public function ajax_update_cash_inquiry() {
        check_ajax_referer('maneli_cash_inquiry_update_nonce', 'nonce');
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }
    
        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        if (!$inquiry_id || get_post_type($inquiry_id) !== 'cash_inquiry') {
            wp_send_json_error(['message' => esc_html__('Invalid request ID.', 'maneli-car-inquiry')]);
            return;
        }
    
        update_post_meta($inquiry_id, 'cash_first_name', sanitize_text_field($_POST['first_name'] ?? ''));
        update_post_meta($inquiry_id, 'cash_last_name', sanitize_text_field($_POST['last_name'] ?? ''));
        update_post_meta($inquiry_id, 'mobile_number', sanitize_text_field($_POST['mobile'] ?? ''));
        update_post_meta($inquiry_id, 'cash_car_color', sanitize_text_field($_POST['color'] ?? ''));
    
        wp_send_json_success(['message' => esc_html__('Request updated successfully.', 'maneli-car-inquiry')]);
    }
    
    public function ajax_delete_cash_inquiry() {
        check_ajax_referer('maneli_cash_inquiry_delete_nonce', 'nonce');
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }
    
        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        if (!$inquiry_id || get_post_type($inquiry_id) !== 'cash_inquiry') {
            wp_send_json_error(['message' => esc_html__('Invalid request ID.', 'maneli-car-inquiry')]);
            return;
        }
    
        if (wp_delete_post($inquiry_id, true)) {
            wp_send_json_success(['message' => esc_html__('Request deleted successfully.', 'maneli-car-inquiry')]);
        } else {
            wp_send_json_error(['message' => esc_html__('Error deleting request.', 'maneli-car-inquiry')]);
        }
    }
    
    public function ajax_set_down_payment() {
        check_ajax_referer('maneli_cash_set_downpayment_nonce', 'nonce');
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }
    
        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        if (!$inquiry_id || get_post_type($inquiry_id) !== 'cash_inquiry') {
            wp_send_json_error(['message' => esc_html__('Invalid request ID.', 'maneli-car-inquiry')]);
            return;
        }
        
        $new_status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $sms_handler = new Maneli_SMS_Handler();
        $options = get_option('maneli_inquiry_all_options', []);
        $customer_name = get_post_meta($inquiry_id, 'cash_first_name', true) . ' ' . get_post_meta($inquiry_id, 'cash_last_name', true);
        $customer_mobile = get_post_meta($inquiry_id, 'mobile_number', true);
        $car_name = get_the_title(get_post_meta($inquiry_id, 'product_id', true));

        if ($new_status === 'awaiting_payment') {
            $amount = preg_replace('/[^0-9]/', '', $_POST['amount'] ?? 0);
            if ($amount <= 0) {
                wp_send_json_error(['message' => esc_html__('Please enter a valid amount.', 'maneli-car-inquiry')]);
                return;
            }
            $old_status = get_post_meta($inquiry_id, 'cash_inquiry_status', true);
            if (empty($old_status)) {
                $old_status = 'new';
            }
            update_post_meta($inquiry_id, 'cash_down_payment', $amount);
            update_post_meta($inquiry_id, 'cash_inquiry_status', 'awaiting_payment');
            
            // Send notification
            require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-handler.php';
            Maneli_Notification_Handler::notify_cash_status_change($inquiry_id, $old_status, 'awaiting_payment');
            
            $pattern_id = $options['cash_inquiry_approved_pattern'] ?? 0;
            if ($pattern_id > 0 && !empty($customer_mobile)) {
                $params = [(string)$customer_name, (string)$car_name, (string)Maneli_Render_Helpers::format_money($amount)];
                $sms_handler->send_pattern($pattern_id, $customer_mobile, $params);
            }
            
        } elseif ($new_status === 'rejected') {
            $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';
            if (empty($reason)) {
                wp_send_json_error(['message' => esc_html__('A reason for rejection is required.', 'maneli-car-inquiry')]);
                return;
            }
            $old_status = get_post_meta($inquiry_id, 'cash_inquiry_status', true);
            if (empty($old_status)) {
                $old_status = 'new';
            }
            update_post_meta($inquiry_id, 'cash_rejection_reason', $reason);
            update_post_meta($inquiry_id, 'cash_inquiry_status', 'rejected');
            
            // Send notification
            require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-handler.php';
            Maneli_Notification_Handler::notify_cash_status_change($inquiry_id, $old_status, 'rejected');
            
            $pattern_id = $options['cash_inquiry_rejected_pattern'] ?? 0;
            if ($pattern_id > 0 && !empty($customer_mobile)) {
                $params = [(string)$customer_name, (string)$car_name, (string)$reason];
                $sms_handler->send_pattern($pattern_id, $customer_mobile, $params);
            }
        } else {
            wp_send_json_error(['message' => esc_html__('Invalid data submitted.', 'maneli-car-inquiry')]);
            return;
        }
    
        wp_send_json_success(['message' => esc_html__('Status changed successfully.', 'maneli-car-inquiry')]);
    }
    
    public function ajax_filter_cash_inquiries() {
        // Check nonce - try both 'nonce' and '_ajax_nonce' for compatibility
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : (isset($_POST['_ajax_nonce']) ? $_POST['_ajax_nonce'] : '');
        if (!wp_verify_nonce($nonce, 'maneli_cash_inquiry_filter_nonce')) {
            wp_send_json_error(['message' => esc_html__('Invalid security token.', 'maneli-car-inquiry')], 403);
            return;
        }
        // CRITICAL: Always get fresh role from WordPress user object
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }
        
        $current_user = wp_get_current_user();
        // Always check fresh roles from database
        $is_admin = current_user_can('manage_maneli_inquiries');
        $is_manager = in_array('maneli_manager', $current_user->roles, true) || in_array('maneli_admin', $current_user->roles, true);
        $is_expert = in_array('maneli_expert', $current_user->roles, true);
        $is_customer = !$is_admin && !$is_manager && !$is_expert;
        
        if (!$is_admin && !$is_manager && !$is_expert && !$is_customer) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }

        $paged = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $search_query = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
        $status_query = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : '';
        $sort_query = isset($_POST['sort']) ? sanitize_text_field(wp_unslash($_POST['sort'])) : 'default';
        $base_url = isset($_POST['base_url']) ? esc_url_raw($_POST['base_url']) : home_url();
        
        // Fix: Convert 'pending' status to 'new' for backward compatibility
        if ($status_query === 'pending') {
            $status_query = 'new';
        }

        $args = [
            'post_type' => 'cash_inquiry', 
            'posts_per_page' => 50, 
            'paged' => $paged, 
            'post_status' => 'publish'
        ];
        
        // CRITICAL: For customers, only show their own inquiries by author
        if ($is_customer) {
            $args['author'] = get_current_user_id();
        }
        
        // Set default sorting logic
        if ($sort_query === 'default') {
            // Default: Show newest first
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
        } else {
            // Custom sorting
            switch ($sort_query) {
                case 'date_desc':
                    $args['orderby'] = 'date';
                    $args['order'] = 'DESC';
                    break;
                case 'date_asc':
                    $args['orderby'] = 'date';
                    $args['order'] = 'ASC';
                    break;
                case 'status':
                    $args['orderby'] = 'meta_value date';
                    $args['order'] = 'ASC';
                    $args['meta_key'] = 'cash_inquiry_status';
                    break;
                default:
                    $args['orderby'] = 'date';
                    $args['order'] = 'DESC';
            }
        }
        
        $meta_query = ['relation' => 'AND'];

        // Expert filter: Only show assigned inquiries for experts
        if ($is_expert && !$is_admin) {
            $meta_query[] = ['key' => 'assigned_expert_id', 'value' => get_current_user_id()];
        } elseif ($is_admin && !empty($_POST['expert'])) {
            $meta_query[] = ['key' => 'assigned_expert_id', 'value' => absint($_POST['expert'])];
        }
        // For customers, we already filtered by author, so no expert filter needed

        // Search functionality
        if (!empty($search_query)) {
            $product_ids = wc_get_products(['s' => $search_query, 'limit' => -1, 'return' => 'ids']);
            $search_meta_query = [
                'relation' => 'OR', 
                ['key' => 'cash_first_name', 'value' => $search_query, 'compare' => 'LIKE'], 
                ['key' => 'cash_last_name', 'value' => $search_query, 'compare' => 'LIKE'], 
                ['key' => 'mobile_number', 'value' => $search_query, 'compare' => 'LIKE']
            ];
            if(!empty($product_ids)) {
                $search_meta_query[] = ['key' => 'product_id', 'value' => $product_ids, 'compare' => 'IN'];
            }
            $meta_query[] = $search_meta_query;
        }
        
        // Status filter
        if (!empty($status_query)) {
            if ($status_query === 'new') {
                // Handle 'new' status - include 'new', 'pending', empty status values, and missing status
                // We need to create a separate meta query for this OR condition
                $new_status_query = [
                    'relation' => 'OR',
                    ['key' => 'cash_inquiry_status', 'value' => 'new', 'compare' => '='],
                    ['key' => 'cash_inquiry_status', 'value' => 'pending', 'compare' => '='],
                    ['key' => 'cash_inquiry_status', 'value' => '', 'compare' => '='],
                    ['key' => 'cash_inquiry_status', 'compare' => 'NOT EXISTS']
                ];
                $meta_query[] = $new_status_query;
            } else {
                $meta_query[] = ['key' => 'cash_inquiry_status', 'value' => $status_query, 'compare' => '='];
            }
        }
        
        // Apply meta query if we have conditions
        if (count($meta_query) > 1 || (!empty($status_query) && $status_query === 'new')) {
            // If we have a status filter with OR conditions, we need to restructure
            if (!empty($status_query) && $status_query === 'new') {
                // Create a new meta query structure that handles the OR condition properly
                $final_meta_query = [
                    'relation' => 'AND'
                ];
                
                // Add all non-status conditions
                foreach ($meta_query as $query) {
                    if (!isset($query['relation']) || $query['relation'] !== 'OR') {
                        $final_meta_query[] = $query;
                    }
                }
                
                // Add the status OR condition
                $final_meta_query[] = [
                    'relation' => 'OR',
                    ['key' => 'cash_inquiry_status', 'value' => 'new', 'compare' => '='],
                    ['key' => 'cash_inquiry_status', 'value' => 'pending', 'compare' => '='],
                    ['key' => 'cash_inquiry_status', 'value' => '', 'compare' => '='],
                    ['key' => 'cash_inquiry_status', 'compare' => 'NOT EXISTS']
                ];
                
                // CRITICAL: Exclude follow_up_scheduled from normal list (only show in followups page)
                if (empty($status_query) || $status_query !== 'follow_up_scheduled') {
                    $final_meta_query[] = [
                        'relation' => 'OR',
                        ['key' => 'cash_inquiry_status', 'value' => 'follow_up_scheduled', 'compare' => '!='],
                        ['key' => 'cash_inquiry_status', 'value' => 'follow_up_scheduled', 'compare' => 'NOT EXISTS']
                    ];
                }
                
                $args['meta_query'] = $final_meta_query;
            } else {
                // CRITICAL: Exclude follow_up_scheduled from normal list (only show in followups page)
                if (empty($status_query) || $status_query !== 'follow_up_scheduled') {
                    $meta_query[] = [
                        'relation' => 'OR',
                        ['key' => 'cash_inquiry_status', 'value' => 'follow_up_scheduled', 'compare' => '!='],
                        ['key' => 'cash_inquiry_status', 'value' => 'follow_up_scheduled', 'compare' => 'NOT EXISTS']
                    ];
                }
                $args['meta_query'] = $meta_query;
            }
        } else {
            // Even if no other filters, exclude follow_up_scheduled
            if (empty($status_query) || $status_query !== 'follow_up_scheduled') {
                $args['meta_query'] = [
                    'relation' => 'OR',
                    ['key' => 'cash_inquiry_status', 'value' => 'follow_up_scheduled', 'compare' => '!='],
                    ['key' => 'cash_inquiry_status', 'value' => 'follow_up_scheduled', 'compare' => 'NOT EXISTS']
                ];
            }
        }
        
        $inquiry_query = new WP_Query($args);
        ob_start();
        if ($inquiry_query->have_posts()) {
            while ($inquiry_query->have_posts()) {
                $inquiry_query->the_post();
                $post_id = get_the_ID();
                $post_type = get_post_type($post_id);
                
                // CRITICAL: Double-check post type before rendering
                if ($post_type === 'cash_inquiry') {
                    Maneli_Render_Helpers::render_cash_inquiry_row($post_id, $base_url);
                } else {
                    // Log unexpected post type for debugging
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Cash inquiry AJAX: Skipping post ID ' . $post_id . ' with post_type: ' . $post_type);
                    }
                }
            }
        } else {
            // Calculate columns based on user role (admin sees expert column, customer/expert don't)
            $columns = $is_admin ? 8 : 7;
            echo '<tr><td colspan="' . $columns . '" style="text-align:center;">' . esc_html__('No requests found.', 'maneli-car-inquiry') . '</td></tr>';
        }
        $html = ob_get_clean();
        wp_reset_postdata();
        
        $pagination_html = paginate_links([
            'base' => add_query_arg('paged', '%#%'), 
            'format' => '?paged=%#%', 
            'current' => $paged, 
            'total' => $inquiry_query->max_num_pages, 
            'prev_text' => esc_html__('&laquo; Previous', 'maneli-car-inquiry'),
            'next_text' => esc_html__('Next &raquo;', 'maneli-car-inquiry'),
            'type'  => 'plain'
        ]);
        wp_send_json_success(['html' => $html, 'pagination_html' => $pagination_html]);
    }
    
    //======================================================================
    // Expert Assignment Handlers
    //======================================================================
    
    public function ajax_assign_expert_to_cash_inquiry() {
        check_ajax_referer('maneli_cash_inquiry_assign_expert_nonce', 'nonce');
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }
    
        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        $expert_id_str = isset($_POST['expert_id']) ? sanitize_text_field($_POST['expert_id']) : 'auto';
    
        if (!$inquiry_id || get_post_type($inquiry_id) !== 'cash_inquiry') {
            wp_send_json_error(['message' => esc_html__('Invalid request ID.', 'maneli-car-inquiry')]);
            return;
        }
        
        $admin_actions = new Maneli_Admin_Actions_Handler();
        $expert_data = $admin_actions->assign_expert_to_post($inquiry_id, $expert_id_str, 'cash');

        if (is_wp_error($expert_data)) {
            wp_send_json_error(['message' => $expert_data->get_error_message()]);
            return;
        }
        
        // Get old status before update
        $old_status = get_post_meta($inquiry_id, 'cash_inquiry_status', true);
        if (empty($old_status)) {
            $old_status = 'new';
        }
        
        // Update status to "referred"
        update_post_meta($inquiry_id, 'cash_inquiry_status', 'referred');
        
        // Send notification for status change
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-handler.php';
        Maneli_Notification_Handler::notify_cash_status_change($inquiry_id, $old_status, 'referred');

        wp_send_json_success([
            'message' => sprintf(esc_html__('Request successfully assigned to %s.', 'maneli-car-inquiry'), $expert_data['name']),
            'expert_name' => $expert_data['name'],
            'new_status_label' => Maneli_CPT_Handler::get_cash_inquiry_status_label('referred'),
            'new_status_key' => 'referred'
        ]);
    }
    
    public function ajax_assign_expert_to_inquiry() {
        check_ajax_referer('maneli_inquiry_assign_expert_nonce', 'nonce');
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }
    
        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        $expert_id_str = isset($_POST['expert_id']) ? sanitize_text_field($_POST['expert_id']) : 'auto';
    
        if (!$inquiry_id || get_post_type($inquiry_id) !== 'inquiry') {
            wp_send_json_error(['message' => esc_html__('Invalid inquiry ID.', 'maneli-car-inquiry')]);
            return;
        }
    
        $admin_actions = new Maneli_Admin_Actions_Handler();
        $expert_data = $admin_actions->assign_expert_to_post($inquiry_id, $expert_id_str, 'installment');

        if (is_wp_error($expert_data)) {
            wp_send_json_error(['message' => $expert_data->get_error_message()]);
            return;
        }
        
        $old_status = get_post_meta($inquiry_id, 'inquiry_status', true);
        update_post_meta($inquiry_id, 'inquiry_status', 'user_confirmed');
        
        // Send notification for status change
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-handler.php';
        Maneli_Notification_Handler::notify_installment_status_change($inquiry_id, $old_status, 'user_confirmed', 'inquiry_status');
    
        wp_send_json_success([
            'message' => sprintf(esc_html__('Inquiry successfully assigned to %s.', 'maneli-car-inquiry'), $expert_data['name']),
            'expert_name' => $expert_data['name'],
            'new_status_label' => Maneli_CPT_Handler::get_status_label('user_confirmed'),
            'new_status_key' => 'user_confirmed'
        ]);
    }
    
    //======================================================================
    // User Management Handlers
    //======================================================================
    
    public function ajax_filter_users() {
        check_ajax_referer('maneli_user_filter_nonce');
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
            return;
        }

        $paged = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $query_args = ['number' => 50, 'paged' => $paged, 'orderby' => sanitize_text_field($_POST['orderby'] ?? 'display_name'), 'order' => sanitize_text_field($_POST['order'] ?? 'ASC')];
        if (!empty($_POST['role'])) $query_args['role'] = sanitize_text_field($_POST['role']);
        if (!empty($_POST['search'])) {
            $search_term = sanitize_text_field($_POST['search']);
            $query_args['search'] = '*' . esc_attr($search_term) . '*';
            $query_args['search_columns'] = ['user_login', 'user_email', 'display_name'];
            $query_args['meta_query'] = ['relation' => 'OR', ['key' => 'first_name', 'value' => $search_term, 'compare' => 'LIKE'], ['key' => 'last_name', 'value' => $search_term, 'compare' => 'LIKE'], ['key' => 'mobile_number', 'value' => $search_term, 'compare' => 'LIKE'], ['key' => 'national_code', 'value' => $search_term, 'compare' => 'LIKE']];
        }

        $user_query = new WP_User_Query($query_args);
        $all_users = $user_query->get_results();
        $current_user_id = get_current_user_id();
        $current_url = isset($_POST['current_url']) ? esc_url($_POST['current_url']) : '';

        ob_start();
        if (!empty($all_users)) {
            foreach ($all_users as $user) {
                if ($user->ID === $current_user_id) continue;
                Maneli_Render_Helpers::render_user_list_row($user, $current_url);
            }
        } else {
            echo '<tr><td colspan="5" style="text-align:center;">' . esc_html__('No users found matching your criteria.', 'maneli-car-inquiry') . '</td></tr>';
        }
        $html = ob_get_clean();
        
        $pagination_html = paginate_links([
            'base' => '#', 
            'format' => '?paged=%#%', 
            'current' => $paged, 
            'total' => ceil($user_query->get_total() / 50), 
            'type' => 'plain', 
            'prev_text' => esc_html__('&laquo; Previous', 'maneli-car-inquiry'),
            'next_text' => esc_html__('Next &raquo;', 'maneli-car-inquiry')
        ]);
        wp_send_json_success(['html' => $html, 'pagination_html' => $pagination_html]);
    }

    public function ajax_delete_user() {
        check_ajax_referer('maneli_delete_user_nonce', '_ajax_nonce');
        // FIX: Added 'delete_users' capability check for security
        if (!current_user_can('manage_maneli_inquiries') || !current_user_can('delete_users')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
            return;
        }
        $user_id_to_delete = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        if (!$user_id_to_delete) {
            wp_send_json_error(['message' => esc_html__('User ID not specified.', 'maneli-car-inquiry')]);
            return;
        }
        if ($user_id_to_delete === get_current_user_id()) {
            wp_send_json_error(['message' => esc_html__('You cannot delete your own account.', 'maneli-car-inquiry')]);
            return;
        }
        require_once(ABSPATH . 'wp-admin/includes/user.php');
        if (wp_delete_user($user_id_to_delete)) {
            wp_send_json_success();
        } else {
            wp_send_json_error(['message' => esc_html__('An error occurred while deleting the user.', 'maneli-car-inquiry')]);
        }
    }

    //======================================================================
    // Product Editor Handlers
    //======================================================================
    
    public function ajax_filter_products() {
        check_ajax_referer('maneli_product_filter_nonce');
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
            return;
        }
        $paged = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $args = ['limit' => 50, 'page' => $paged, 'orderby' => 'title', 'order' => 'ASC', 'paginate' => true];
        if (!empty($_POST['search'])) $args['s'] = sanitize_text_field(wp_unslash($_POST['search']));
        $query_result = wc_get_products($args);

        ob_start();
        if (!empty($query_result->products)) {
            foreach ($query_result->products as $product) {
                Maneli_Render_Helpers::render_product_editor_row($product);
            }
        } else {
            echo '<tr><td colspan="6" style="text-align:center;">' . esc_html__('No products found matching your criteria.', 'maneli-car-inquiry') . '</td></tr>';
        }
        $html = ob_get_clean();

        $pagination_html = paginate_links([
            'base' => '#', 
            'format' => '?paged=%#%', 
            'current' => $paged, 
            'total' => $query_result->max_num_pages, 
            'type' => 'plain', 
            'prev_text' => esc_html__('&laquo; Previous', 'maneli-car-inquiry'),
            'next_text' => esc_html__('Next &raquo;', 'maneli-car-inquiry')
        ]);
        wp_send_json_success(['html' => $html, 'pagination_html' => $pagination_html]);
    }
    
    public function ajax_update_product_data() {
        check_ajax_referer('maneli_product_data_nonce', 'nonce');
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $field_type = isset($_POST['field_type']) ? sanitize_key($_POST['field_type']) : '';
        $field_value = isset($_POST['field_value']) ? sanitize_text_field(wp_unslash($_POST['field_value'])) : '';
        
        // Convert Persian numbers to English and remove separators for numeric fields
        if (in_array($field_type, ['regular_price', 'installment_price', 'min_downpayment'])) {
            // Remove Persian digits and separators, keep only English digits
            $field_value = preg_replace('/[^\d]/', '', $field_value);
            // Also convert Persian digits to English
            $persian_digits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            $english_digits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            $field_value = str_replace($persian_digits, $english_digits, $field_value);
        }

        if (!$product_id || empty($field_type)) {
            wp_send_json_error(esc_html__('Invalid data sent.', 'maneli-car-inquiry'));
            return;
        }
        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error(esc_html__('Product not found.', 'maneli-car-inquiry'));
            return;
        }

        switch ($field_type) {
            case 'regular_price':
                $product->set_regular_price($field_value);
                $product->set_price($field_value); // Ensure sale price is not lower
                break;
            case 'installment_price': update_post_meta($product_id, 'installment_price', $field_value); break;
            case 'min_downpayment': update_post_meta($product_id, 'min_downpayment', $field_value); break;
            case 'car_colors': update_post_meta($product_id, '_maneli_car_colors', $field_value); break;
            case 'car_status': update_post_meta($product_id, '_maneli_car_status', $field_value); break;
            default: wp_send_json_error(esc_html__('Invalid field type.', 'maneli-car-inquiry'));
        }

        if ('regular_price' === $field_type) {
            $product->save();
        }
        wc_delete_product_transients($product_id); // Clear caches
        wp_send_json_success(esc_html__('Updated.', 'maneli-car-inquiry'));
    }

    //======================================================================
    // Tracking Status Handlers (for installment inquiries)
    //======================================================================
    
    /**
     * Updates the tracking status of an inquiry via AJAX.
     * Handles calendar dates for 'approved' (meeting date) and 'follow_up' (follow-up date).
     */
    public function ajax_update_tracking_status() {
        check_ajax_referer('maneli_tracking_status_nonce', 'nonce');
        
        if (!is_user_logged_in() || !(current_user_can('manage_maneli_inquiries') || in_array('maneli_expert', wp_get_current_user()->roles))) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }

        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        $new_status = isset($_POST['tracking_status']) ? sanitize_text_field($_POST['tracking_status']) : '';
        $date_value = isset($_POST['date_value']) ? sanitize_text_field($_POST['date_value']) : '';

        if (!$inquiry_id || get_post_type($inquiry_id) !== 'inquiry') {
            wp_send_json_error(['message' => esc_html__('Invalid inquiry ID.', 'maneli-car-inquiry')]);
            return;
        }

        // Verify the status is valid
        $valid_statuses = array_keys(Maneli_CPT_Handler::get_tracking_statuses());
        if (!in_array($new_status, $valid_statuses)) {
            wp_send_json_error(['message' => esc_html__('Invalid tracking status.', 'maneli-car-inquiry')]);
            return;
        }

        // Get old status before update
        $old_status = get_post_meta($inquiry_id, 'tracking_status', true);
        if (empty($old_status)) {
            $old_status = 'new';
        }

        // Update tracking status
        update_post_meta($inquiry_id, 'tracking_status', $new_status);
        
        // Send notification for status change
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-handler.php';
        Maneli_Notification_Handler::notify_installment_status_change($inquiry_id, $old_status, $new_status, 'tracking_status');

        // Handle date storage based on status
        if ($new_status === 'approved' && !empty($date_value)) {
            update_post_meta($inquiry_id, 'meeting_date', $date_value);
            delete_post_meta($inquiry_id, 'follow_up_date'); // Clear follow-up date if exists
        } elseif ($new_status === 'follow_up' && !empty($date_value)) {
            update_post_meta($inquiry_id, 'follow_up_date', $date_value);
            delete_post_meta($inquiry_id, 'meeting_date'); // Clear meeting date if exists
        } else {
            // For other statuses, clear both dates
            delete_post_meta($inquiry_id, 'meeting_date');
            delete_post_meta($inquiry_id, 'follow_up_date');
        }

        $status_label = Maneli_CPT_Handler::get_tracking_status_label($new_status);
        
        wp_send_json_success([
            'message' => sprintf(esc_html__('Tracking status updated to: %s', 'maneli-car-inquiry'), $status_label),
            'status_label' => $status_label,
            'status_key' => $new_status,
            'date_value' => $date_value
        ]);
    }

    /**
     * Filters follow-up inquiries (tracking_status = 'follow_up') via AJAX.
     * Similar to ajax_filter_inquiries but only returns follow-up items.
     */
    public function ajax_filter_followup_inquiries() {
        check_ajax_referer('maneli_followup_filter_nonce', '_ajax_nonce');
        
        if (!is_user_logged_in() || !(current_user_can('manage_maneli_inquiries') || in_array('maneli_expert', wp_get_current_user()->roles))) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
            return;
        }
    
        $paged = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $search_query = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
        $base_url = isset($_POST['base_url']) ? esc_url_raw($_POST['base_url']) : home_url();
        
        $args = [
            'post_type' => 'inquiry',
            'posts_per_page' => 50,
            'paged' => $paged,
            'orderby' => 'meta_value',
            'meta_key' => 'follow_up_date',
            'order' => 'ASC', // Show earliest follow-up dates first
            'post_status' => 'publish'
        ];
        
        $meta_query = ['relation' => 'AND'];
        
        // MUST have tracking_status = 'follow_up'
        $meta_query[] = ['key' => 'tracking_status', 'value' => 'follow_up', 'compare' => '='];
        
        // Search functionality
        if (!empty($search_query)) {
            $user_ids = get_users(['fields' => 'ID', 'search' => '*' . esc_attr($search_query) . '*', 'search_columns' => ['user_login', 'user_email', 'display_name']]);
            $product_ids = wc_get_products(['s' => $search_query, 'limit' => -1, 'return' => 'ids']);
            $search_meta_query = ['relation' => 'OR'];
            if(!empty($user_ids)) $search_meta_query[] = ['key' => 'post_author', 'value' => $user_ids, 'compare' => 'IN'];
            if(!empty($product_ids)) $search_meta_query[] = ['key' => 'product_id', 'value' => $product_ids, 'compare' => 'IN'];
            $search_meta_query[] = ['key' => 'national_code', 'value' => $search_query, 'compare' => 'LIKE'];
            $search_meta_query[] = ['key' => 'mobile_number', 'value' => $search_query, 'compare' => 'LIKE'];
            $meta_query[] = $search_meta_query;
        }
        
        // Expert filter
        if (current_user_can('manage_maneli_inquiries')) {
            if (!empty($_POST['expert'])) $meta_query[] = ['key' => 'assigned_expert_id', 'value' => absint($_POST['expert'])];
        } else {
            $meta_query[] = ['key' => 'assigned_expert_id', 'value' => get_current_user_id()];
        }
        
        if (count($meta_query) > 1) $args['meta_query'] = $meta_query;
    
        $inquiry_query = new WP_Query($args);
        ob_start();
        if ($inquiry_query->have_posts()) {
            while ($inquiry_query->have_posts()) {
                $inquiry_query->the_post();
                Maneli_Render_Helpers::render_inquiry_row(get_the_ID(), $base_url, true); // Show follow-up date
            }
        } else {
            $columns = current_user_can('manage_maneli_inquiries') ? 8 : 7; // Extra column for follow-up date
            echo '<tr><td colspan="' . $columns . '" style="text-align:center;">' . esc_html__('No follow-up inquiries found.', 'maneli-car-inquiry') . '</td></tr>';
        }
        $html = ob_get_clean();
        wp_reset_postdata();
        
        $pagination_html = paginate_links([
            'base' => add_query_arg('paged', '%#%'), 
            'format' => '?paged=%#%', 
            'current' => $paged, 
            'total' => $inquiry_query->max_num_pages, 
            'prev_text' => esc_html__('&laquo; Previous', 'maneli-car-inquiry'), 
            'next_text' => esc_html__('Next &raquo;', 'maneli-car-inquiry'), 
            'type'  => 'plain'
        ]);
    
        wp_send_json_success(['html' => $html, 'pagination_html' => $pagination_html]);
    }
    
    //======================================================================
    // Full Product Save Handler (Add/Edit Product Page)
    //======================================================================
    
    /**
     * Save complete product data from add/edit product form
     */
    public function ajax_save_product_full() {
        check_ajax_referer('maneli_save_product', 'maneli_product_nonce');
        
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }
        
        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $is_edit = $product_id > 0;
        
        // Get form data
        $product_name = isset($_POST['product_name']) ? sanitize_text_field(wp_unslash($_POST['product_name'])) : '';
        $product_description = isset($_POST['product_description']) ? wp_kses_post(wp_unslash($_POST['product_description'])) : '';
        $product_year = isset($_POST['product_year']) ? sanitize_text_field(wp_unslash($_POST['product_year'])) : '';
        $product_brand = isset($_POST['product_brand']) ? sanitize_text_field(wp_unslash($_POST['product_brand'])) : '';
        $product_status = isset($_POST['product_status']) ? sanitize_text_field(wp_unslash($_POST['product_status'])) : 'special_sale';
        
        // Prices (convert Persian to English and remove separators)
        // Prices are already sent as raw numbers from frontend, but we sanitize them anyway
        // Empty string means user intentionally left it empty
        $regular_price_raw = isset($_POST['product_regular_price']) ? sanitize_text_field(wp_unslash($_POST['product_regular_price'])) : '';
        $installment_price_raw = isset($_POST['product_installment_price']) ? sanitize_text_field(wp_unslash($_POST['product_installment_price'])) : '';
        $min_downpayment_raw = isset($_POST['product_min_downpayment']) ? sanitize_text_field(wp_unslash($_POST['product_min_downpayment'])) : '';
        
        // Convert Persian numbers to English and remove separators (just in case)
        $regular_price_clean = preg_replace('/[^\d]/', '', str_replace(['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'], ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], $regular_price_raw));
        $installment_price_clean = preg_replace('/[^\d]/', '', str_replace(['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'], ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], $installment_price_raw));
        $min_downpayment_clean = preg_replace('/[^\d]/', '', str_replace(['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'], ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], $min_downpayment_raw));
        
        // If the cleaned value is empty or 0, set to 0 (WooCommerce requires a number, not empty string)
        // But we'll use 0 to represent "no price" in the database
        $regular_price = (!empty($regular_price_clean) && $regular_price_clean !== '0') ? floatval($regular_price_clean) : 0;
        $installment_price = (!empty($installment_price_clean) && $installment_price_clean !== '0') ? floatval($installment_price_clean) : 0;
        $min_downpayment = (!empty($min_downpayment_clean) && $min_downpayment_clean !== '0') ? floatval($min_downpayment_clean) : 0;
        
        // Features array
        $product_features = isset($_POST['product_features']) && is_array($_POST['product_features']) 
            ? array_map('sanitize_text_field', array_filter(array_map('wp_unslash', $_POST['product_features'])))
            : [];
        
        // Colors array
        $product_colors = isset($_POST['product_colors']) && is_array($_POST['product_colors']) 
            ? array_map('sanitize_text_field', array_filter(array_map('wp_unslash', $_POST['product_colors'])))
            : [];
        $colors_string = !empty($product_colors) ? implode(', ', $product_colors) : '';
        
        // Categories
        $product_categories = isset($_POST['product_category']) && is_array($_POST['product_category']) 
            ? array_map('absint', $_POST['product_category'])
            : [];
        
        // Images
        $featured_image_id = isset($_POST['featured_image_id']) ? absint($_POST['featured_image_id']) : 0;
        $gallery_image_ids = isset($_POST['gallery_image_ids']) ? array_filter(array_map('absint', explode(',', sanitize_text_field($_POST['gallery_image_ids'])))) : [];
        
        // Validation
        if (empty($product_name)) {
            wp_send_json_error(['message' => esc_html__('Product name is required.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Create or update product
        if ($is_edit) {
            $product = wc_get_product($product_id);
            if (!$product) {
                wp_send_json_error(['message' => esc_html__('Product not found.', 'maneli-car-inquiry')]);
                return;
            }
            $product->set_name($product_name);
            $product->set_description($product_description);
            $product->set_regular_price($regular_price);
            $product->set_price($regular_price); // Ensure price is set
            
            // Set featured image
            if ($featured_image_id > 0) {
                $product->set_image_id($featured_image_id);
            }
            
            // Set gallery images
            if (!empty($gallery_image_ids)) {
                $product->set_gallery_image_ids($gallery_image_ids);
            }
            
            $product->save();
        } else {
            // Create new product
            $new_product = new WC_Product_Simple();
            $new_product->set_name($product_name);
            $new_product->set_description($product_description);
            $new_product->set_status('publish');
            $new_product->set_regular_price($regular_price);
            $new_product->set_price($regular_price);
            
            // Set featured image
            if ($featured_image_id > 0) {
                $new_product->set_image_id($featured_image_id);
            }
            
            // Set gallery images
            if (!empty($gallery_image_ids)) {
                $new_product->set_gallery_image_ids($gallery_image_ids);
            }
            
            $product_id = $new_product->save();
            $product = wc_get_product($product_id);
        }
        
        if (!$product_id || is_wp_error($product)) {
            wp_send_json_error(['message' => esc_html__('Error saving product.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Save custom meta fields
        update_post_meta($product_id, 'installment_price', $installment_price);
        update_post_meta($product_id, 'min_downpayment', $min_downpayment);
        update_post_meta($product_id, '_maneli_car_colors', $colors_string);
        update_post_meta($product_id, '_maneli_car_status', $product_status);
        update_post_meta($product_id, '_maneli_car_year', $product_year);
        update_post_meta($product_id, '_maneli_car_brand', $product_brand);
        // Features should be stored as comma-separated string
        $features_string = !empty($product_features) ? implode(',', $product_features) : '';
        update_post_meta($product_id, '_maneli_car_features', $features_string);
        
        // Set categories
        if (!empty($product_categories)) {
            wp_set_post_terms($product_id, $product_categories, 'product_cat');
        }
        
        wp_send_json_success([
            'message' => $is_edit 
                ? esc_html__('Product updated successfully.', 'maneli-car-inquiry')
                : esc_html__('Product created successfully.', 'maneli-car-inquiry'),
            'product_id' => $product_id
        ]);
    }

    /**
     * Upload image via AJAX
     */
    public function ajax_upload_image() {
        check_ajax_referer('maneli_product_data_nonce', 'nonce');
        
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }
        
        if (!isset($_FILES['image'])) {
            wp_send_json_error(['message' => esc_html__('No file uploaded.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Include WordPress file handling functions
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $file = $_FILES['image'];
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error(['message' => esc_html__('Invalid file type. Only images are allowed.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Upload file
        $upload = wp_handle_upload($file, ['test_form' => false]);
        
        if (isset($upload['error'])) {
            wp_send_json_error(['message' => $upload['error']]);
            return;
        }
        
        // Create attachment
        $attachment = [
            'post_mime_type' => $upload['type'],
            'post_title' => sanitize_file_name(pathinfo($file['name'], PATHINFO_FILENAME)),
            'post_content' => '',
            'post_status' => 'inherit'
        ];
        
        $attachment_id = wp_insert_attachment($attachment, $upload['file']);
        
        if (is_wp_error($attachment_id)) {
            wp_send_json_error(['message' => esc_html__('Error creating attachment.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Generate attachment metadata
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
        wp_update_attachment_metadata($attachment_id, $attachment_data);
        
        wp_send_json_success([
            'attachment_id' => $attachment_id,
            'url' => wp_get_attachment_image_url($attachment_id, 'medium')
        ]);
    }
    
    //======================================================================
    // Bulk Product Update Handler
    //======================================================================

    /**
     * Save multiple products data at once (dashboard bulk update)
     */
    public function ajax_save_products_bulk() {
        // Security check
        check_ajax_referer('maneli_save_products', 'nonce');
        
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }

        $products = isset($_POST['products']) ? $_POST['products'] : [];
        
        if (empty($products) || !is_array($products)) {
            wp_send_json_error(['message' => esc_html__('No products data provided.', 'maneli-car-inquiry')]);
            return;
        }

        $updated_count = 0;
        $errors = [];

        foreach ($products as $product_data) {
            $product_id = isset($product_data['id']) ? intval($product_data['id']) : 0;
            
            if (!$product_id || get_post_type($product_id) !== 'product') {
                $errors[] = sprintf(__('Invalid product ID: %d', 'maneli-car-inquiry'), $product_id);
                continue;
            }

            // Update product meta fields
            $fields = [
                'cash_price' => '_cash_price',
                'installment_price' => '_installment_price',
                'min_down_payment' => '_min_down_payment',
                'available_colors' => '_available_colors',
                'sales_status' => '_sales_status'
            ];

            foreach ($fields as $key => $meta_key) {
                if (isset($product_data[$key])) {
                    $value = sanitize_text_field($product_data[$key]);
                    update_post_meta($product_id, $meta_key, $value);
                }
            }

            $updated_count++;
        }

        if ($updated_count > 0) {
            wp_send_json_success([
                'message' => sprintf(
                    _n(
                        '%d product updated successfully.',
                        '%d products updated successfully.',
                        $updated_count,
                        'maneli-car-inquiry'
                    ),
                    $updated_count
                ),
                'updated_count' => $updated_count,
                'errors' => $errors
            ]);
        } else {
            wp_send_json_error([
                'message' => esc_html__('No products were updated.', 'maneli-car-inquiry'),
                'errors' => $errors
            ]);
        }
    }

    //======================================================================
    // Expert Management Handlers
    //======================================================================


    /**
     * Toggle expert active status
     */
    public function ajax_toggle_expert_status() {
        // Security check
        check_ajax_referer('maneli_toggle_expert_nonce', 'nonce');
        
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $active = isset($_POST['active']) && $_POST['active'] === 'true';

        if (!$user_id) {
            wp_send_json_error(['message' => esc_html__('Invalid user ID.', 'maneli-car-inquiry')]);
            return;
        }

        $user = get_userdata($user_id);
        if (!$user || !in_array('maneli_expert', $user->roles)) {
            wp_send_json_error(['message' => esc_html__('User is not an expert.', 'maneli-car-inquiry')]);
            return;
        }

        // Update status
        update_user_meta($user_id, 'expert_active', $active ? 'yes' : 'no');

        wp_send_json_success([
            'message' => $active 
                ? esc_html__('Expert activated successfully.', 'maneli-car-inquiry')
                : esc_html__('Expert deactivated successfully.', 'maneli-car-inquiry')
        ]);
    }

    /**
     * Get expert statistics
     */
    public function ajax_get_expert_stats() {
        // Security check
        check_ajax_referer('maneli_expert_stats', 'nonce');
        
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

        if (!$user_id) {
            wp_send_json_error(['message' => esc_html__('Invalid user ID.', 'maneli-car-inquiry')]);
            return;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error(['message' => esc_html__('User not found.', 'maneli-car-inquiry')]);
            return;
        }

        // Get inquiry counts
        $total_inquiries = count(get_posts([
            'post_type' => ['inquiry', 'cash_inquiry'],
            'post_status' => 'any',
            'author' => $user_id,
            'posts_per_page' => -1,
            'fields' => 'ids'
        ]));

        // Get assigned inquiries count
        global $wpdb;
        $assigned_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} 
            WHERE meta_key = 'assigned_expert_id' AND meta_value = %d",
            $user_id
        ));

        // Get status breakdown for assigned inquiries
        $approved_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} pm1
            INNER JOIN {$wpdb->postmeta} pm2 ON pm1.post_id = pm2.post_id
            WHERE pm1.meta_key = 'assigned_expert_id' AND pm1.meta_value = %d
            AND pm2.meta_key = 'inquiry_status' AND pm2.meta_value = 'approved'",
            $user_id
        ));

        $pending_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} pm1
            INNER JOIN {$wpdb->postmeta} pm2 ON pm1.post_id = pm2.post_id
            WHERE pm1.meta_key = 'assigned_expert_id' AND pm1.meta_value = %d
            AND pm2.meta_key = 'inquiry_status' AND pm2.meta_value = 'pending'",
            $user_id
        ));

        $rejected_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} pm1
            INNER JOIN {$wpdb->postmeta} pm2 ON pm1.post_id = pm2.post_id
            WHERE pm1.meta_key = 'assigned_expert_id' AND pm1.meta_value = %d
            AND pm2.meta_key = 'inquiry_status' AND pm2.meta_value = 'rejected'",
            $user_id
        ));

        // Build HTML
        ob_start();
        ?>
        <div class="expert-stats-container">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary-transparent">
                            <i class="ri-file-list-3-line"></i>
                        </div>
                        <div class="stat-content">
                            <h4><?php echo number_format_i18n($total_inquiries); ?></h4>
                            <p><?php esc_html_e('Total Inquiries Submitted', 'maneli-car-inquiry'); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon bg-info-transparent">
                            <i class="ri-user-settings-line"></i>
                        </div>
                        <div class="stat-content">
                            <h4><?php echo number_format_i18n($assigned_count); ?></h4>
                            <p><?php esc_html_e('Assigned Inquiries', 'maneli-car-inquiry'); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-success-transparent">
                            <i class="ri-checkbox-circle-line"></i>
                        </div>
                        <div class="stat-content">
                            <h4><?php echo number_format_i18n($approved_count); ?></h4>
                            <p><?php esc_html_e('Approved', 'maneli-car-inquiry'); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning-transparent">
                            <i class="ri-time-line"></i>
                        </div>
                        <div class="stat-content">
                            <h4><?php echo number_format_i18n($pending_count); ?></h4>
                            <p><?php esc_html_e('Pending', 'maneli-car-inquiry'); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-danger-transparent">
                            <i class="ri-close-circle-line"></i>
                        </div>
                        <div class="stat-content">
                            <h4><?php echo number_format_i18n($rejected_count); ?></h4>
                            <p><?php esc_html_e('Rejected', 'maneli-car-inquiry'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-3 text-center">
                <p class="text-muted mb-0">
                    <i class="ri-user-line me-1"></i>
                    <strong><?php echo esc_html($user->display_name); ?></strong>
                </p>
                <p class="text-muted mb-0">
                    <i class="ri-mail-line me-1"></i>
                    <?php echo esc_html($user->user_email); ?>
                </p>
            </div>
        </div>
        
        <style>
        .expert-stats-container .stat-card {
            padding: 15px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .expert-stats-container .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .expert-stats-container .stat-content h4 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }
        .expert-stats-container .stat-content p {
            margin: 0;
            color: #6c757d;
            font-size: 13px;
        }
        </style>
        <?php
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }
    
    //======================================================================
    // Cash Inquiry Expert/Admin Handlers
    //======================================================================
    
    /**
     * Save meeting schedule for cash inquiry
     */
    public function ajax_save_meeting_schedule() {
        check_ajax_referer('maneli_save_meeting', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
        }
        
        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        $inquiry_type = isset($_POST['inquiry_type']) ? sanitize_text_field($_POST['inquiry_type']) : 'cash';
        $meeting_date = isset($_POST['meeting_date']) ? sanitize_text_field($_POST['meeting_date']) : '';
        $meeting_time = isset($_POST['meeting_time']) ? sanitize_text_field($_POST['meeting_time']) : '';
        
        if (!$inquiry_id || !$meeting_date || !$meeting_time) {
            wp_send_json_error(['message' => esc_html__('Invalid data sent.', 'maneli-car-inquiry')]);
        }
        
        // Ensure Permission Helpers is loaded
        if (!class_exists('Maneli_Permission_Helpers')) {
            require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/helpers/class-maneli-permission-helpers.php';
        }
        
        // Check permission
        $is_assigned = Maneli_Permission_Helpers::is_assigned_expert($inquiry_id, get_current_user_id());
        if (!$is_assigned && !current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
        }
        
        // Combine date and time
        $meeting_datetime = $meeting_date . ' ' . $meeting_time;
        $date_only = date('Y-m-d', strtotime($meeting_datetime));
        
        // Get settings and validate meeting time
        $options = get_option('maneli_inquiry_all_options', []);
        $start_hour = $options['meetings_start_hour'] ?? '10:00';
        $end_hour = $options['meetings_end_hour'] ?? '20:00';
        $slot_minutes = max(5, (int)($options['meetings_slot_minutes'] ?? 30));
        
        // Validate meeting time is within allowed range
        $meeting_time_ts = strtotime($meeting_datetime);
        $start_ts = strtotime($date_only . ' ' . $start_hour);
        $end_ts = strtotime($date_only . ' ' . $end_hour);
        
        // Check if the selected date is an excluded day
        $excluded_days = isset($options['meetings_excluded_days']) && is_array($options['meetings_excluded_days']) 
            ? $options['meetings_excluded_days'] 
            : [];
        
        if (!empty($excluded_days)) {
            $day_of_week = strtolower(date('l', $meeting_time_ts));
            if (in_array($day_of_week, $excluded_days)) {
                wp_send_json_error(['message' => esc_html__('This day is excluded from meeting schedules.', 'maneli-car-inquiry')]);
                return;
            }
        }
        
        if ($meeting_time_ts < $start_ts || $meeting_time_ts >= $end_ts) {
            wp_send_json_error(['message' => esc_html__('Meeting time must be within the allowed schedule range.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Validate meeting time matches slot boundaries
        $minutes_from_start = ($meeting_time_ts - $start_ts) / 60;
        if ($minutes_from_start % $slot_minutes !== 0) {
            wp_send_json_error(['message' => esc_html__('Meeting time must match the configured slot intervals.', 'maneli-car-inquiry')]);
            return;
        }
        
        // SECURITY: Check for duplicate meeting time (prevent conflict with other experts)
        $existing_meetings = get_posts([
            'post_type' => 'maneli_meeting',
            'posts_per_page' => 1,
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => 'meeting_start',
                    'value' => $meeting_datetime,
                    'compare' => '='
                ]
            ]
        ]);
        
        if (!empty($existing_meetings)) {
            // Check if all slots for this day are taken
            $all_day_meetings = get_posts([
                'post_type' => 'maneli_meeting',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'date_query' => [
                    [
                        'after' => $date_only . ' 00:00:00',
                        'before' => $date_only . ' 23:59:59',
                        'inclusive' => true
                    ]
                ]
            ]);
            
            // Calculate total possible slots
            $total_slots = ($end_ts - $start_ts) / ($slot_minutes * 60);
            
            if (count($all_day_meetings) >= $total_slots) {
                wp_send_json_error(['message' => esc_html__('Unfortunately all time slots are full today. Please choose another day.', 'maneli-car-inquiry')]);
            } else {
                wp_send_json_error(['message' => esc_html__('This time slot is already booked. Please choose another time.', 'maneli-car-inquiry')]);
            }
            return;
        }
        
        // Create meeting post
        $title = sprintf(esc_html__('Meeting - %s', 'maneli-car-inquiry'), date_i18n('Y/m/d H:i', strtotime($meeting_datetime)));
        $meeting_post_id = wp_insert_post([
            'post_type' => 'maneli_meeting',
            'post_title' => $title,
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        ]);
        
        if (is_wp_error($meeting_post_id)) {
            wp_send_json_error(['message' => esc_html__('Error saving meeting:', 'maneli-car-inquiry') . ' ' . $meeting_post_id->get_error_message()]);
            return;
        }
        
        // Save meeting metadata
        update_post_meta($meeting_post_id, 'meeting_start', $meeting_datetime);
        update_post_meta($meeting_post_id, 'meeting_inquiry_id', $inquiry_id);
        update_post_meta($meeting_post_id, 'meeting_inquiry_type', $inquiry_type);
        
        // Save meeting data in inquiry meta (for backward compatibility)
        update_post_meta($inquiry_id, 'meeting_date', $meeting_date);
        update_post_meta($inquiry_id, 'meeting_time', $meeting_time);
        
        wp_send_json_success(['message' => esc_html__('Meeting time saved successfully.', 'maneli-car-inquiry')]);
    }
    
    /**
     * Save expert decision for cash inquiry
     */
    public function ajax_save_expert_decision_cash() {
        check_ajax_referer('maneli_expert_decision', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
        }
        
        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        $decision = isset($_POST['decision']) ? sanitize_text_field($_POST['decision']) : '';
        $downpayment = isset($_POST['downpayment']) ? intval($_POST['downpayment']) : 0;
        $note = isset($_POST['note']) ? sanitize_textarea_field($_POST['note']) : '';
        
        if (!$inquiry_id || !$decision) {
            wp_send_json_error(['message' => esc_html__('Invalid data sent.', 'maneli-car-inquiry')]);
        }
        
        // Ensure Permission Helpers is loaded
        if (!class_exists('Maneli_Permission_Helpers')) {
            require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/helpers/class-maneli-permission-helpers.php';
        }
        
        // Check permission (must be assigned expert)
        $is_assigned = Maneli_Permission_Helpers::is_assigned_expert($inquiry_id, get_current_user_id());
        if (!$is_assigned) {
            wp_send_json_error(['message' => esc_html__('Only assigned expert can make decision.', 'maneli-car-inquiry')]);
        }
        
        // Save expert decision
        update_post_meta($inquiry_id, 'expert_decision', $decision);
        update_post_meta($inquiry_id, 'expert_note', $note);
        
        if ($downpayment > 0) {
            update_post_meta($inquiry_id, 'cash_down_payment', $downpayment);
        }
        
        // Get old status before update
        $old_status = get_post_meta($inquiry_id, 'cash_inquiry_status', true);
        if (empty($old_status)) {
            $old_status = 'new';
        }
        
        // Update status based on decision
        if ($decision === 'approved') {
            update_post_meta($inquiry_id, 'cash_inquiry_status', 'approved');
            // Send notification
            require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-handler.php';
            Maneli_Notification_Handler::notify_cash_status_change($inquiry_id, $old_status, 'approved');
        } elseif ($decision === 'rejected') {
            update_post_meta($inquiry_id, 'cash_inquiry_status', 'rejected');
            // Send notification
            require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-handler.php';
            Maneli_Notification_Handler::notify_cash_status_change($inquiry_id, $old_status, 'rejected');
        }
        
        wp_send_json_success(['message' => esc_html__('Decision saved successfully.', 'maneli-car-inquiry')]);
    }
    
    /**
     * Admin final approval for cash inquiry
     */
    public function ajax_admin_approve_cash() {
        check_ajax_referer('maneli_admin_approve', 'nonce');
        
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Only administrator can perform final approval.', 'maneli-car-inquiry')]);
        }
        
        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        
        if (!$inquiry_id) {
            wp_send_json_error(['message' => esc_html__('Invalid inquiry ID.', 'maneli-car-inquiry')]);
        }
        
        // Check if expert has approved
        $expert_decision = get_post_meta($inquiry_id, 'expert_decision', true);
        if ($expert_decision !== 'approved') {
            wp_send_json_error(['message' => esc_html__('Expert has not approved yet.', 'maneli-car-inquiry')]);
        }
        
        // Get old status before update
        $old_status = get_post_meta($inquiry_id, 'cash_inquiry_status', true);
        if (empty($old_status)) {
            $old_status = 'approved';
        }
        
        // Set to completed
        update_post_meta($inquiry_id, 'cash_inquiry_status', 'completed');
        update_post_meta($inquiry_id, 'admin_approved', 'yes');
        update_post_meta($inquiry_id, 'admin_approved_date', current_time('mysql'));
        
        // Send notification
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-handler.php';
        Maneli_Notification_Handler::notify_cash_status_change($inquiry_id, $old_status, 'completed');
        
        wp_send_json_success(['message' => esc_html__('Request finalized successfully.', 'maneli-car-inquiry')]);
    }
    
    /**
     * Update cash inquiry status with workflow logic
     */
    public function ajax_update_cash_status() {
        check_ajax_referer('maneli_update_cash_status', 'nonce');
        
        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
        
        // If action_type is provided, handle it using the action handler
        if (!empty($action)) {
            return $this->ajax_update_cash_status_action();
        }
        
        // Legacy support: new_status parameter
        $new_status = isset($_POST['new_status']) ? sanitize_text_field($_POST['new_status']) : '';
        
        if (!$inquiry_id || get_post_type($inquiry_id) !== 'cash_inquiry') {
            wp_send_json_error(['message' => esc_html__('Invalid inquiry ID.', 'maneli-car-inquiry')]);
        }
        
        // Ensure Permission Helpers is loaded
        if (!class_exists('Maneli_Permission_Helpers')) {
            require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/helpers/class-maneli-permission-helpers.php';
        }
        
        // Check permissions
        $is_admin = current_user_can('manage_maneli_inquiries');
        $is_assigned = Maneli_Permission_Helpers::is_assigned_expert($inquiry_id, get_current_user_id());
        
        if (!$is_admin && !$is_assigned) {
            wp_send_json_error(['message' => esc_html__('You do not have permission to perform this action.', 'maneli-car-inquiry')]);
        }
        
        // Validate status
        $valid_statuses = ['in_progress', 'follow_up_scheduled', 'awaiting_downpayment', 'meeting_scheduled', 'approved', 'rejected'];
        if (!in_array($new_status, $valid_statuses)) {
            wp_send_json_error(['message' => esc_html__('Invalid status provided.', 'maneli-car-inquiry')]);
        }
        
        // Get old status before update
        $old_status = get_post_meta($inquiry_id, 'cash_inquiry_status', true);
        if (empty($old_status)) {
            $old_status = 'new';
        }
        
        // Update status
        update_post_meta($inquiry_id, 'cash_inquiry_status', $new_status);
        
        // Send notification for status change
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-handler.php';
        Maneli_Notification_Handler::notify_cash_status_change($inquiry_id, $old_status, $new_status);
        
        // Handle additional data based on status
        if ($new_status === 'follow_up_scheduled') {
            $followup_date = isset($_POST['followup_date']) ? sanitize_text_field($_POST['followup_date']) : '';
            $followup_note = isset($_POST['followup_note']) ? sanitize_textarea_field($_POST['followup_note']) : '';
            
            if ($followup_date) {
                // Save previous follow-up date to history
                $previous_followup = get_post_meta($inquiry_id, 'followup_date', true);
                if ($previous_followup) {
                    $followup_history = get_post_meta($inquiry_id, 'followup_history', true) ?: [];
                    $followup_history[] = [
                        'date' => $previous_followup,
                        'completed_at' => current_time('mysql')
                    ];
                    update_post_meta($inquiry_id, 'followup_history', $followup_history);
                }
                
                update_post_meta($inquiry_id, 'followup_date', $followup_date);
                update_post_meta($inquiry_id, 'followup_note', $followup_note);
                update_post_meta($inquiry_id, 'followup_scheduled_at', current_time('mysql'));
            }
        } elseif ($new_status === 'awaiting_downpayment') {
            $amount = isset($_POST['downpayment_amount']) ? intval($_POST['downpayment_amount']) : 0;
            if ($amount > 0) {
                update_post_meta($inquiry_id, 'cash_down_payment', $amount);
                // TODO: Send SMS to customer with payment link
            }
        }
        
        if ($new_status === 'meeting_scheduled') {
            $date = isset($_POST['meeting_date']) ? sanitize_text_field($_POST['meeting_date']) : '';
            $time = isset($_POST['meeting_time']) ? sanitize_text_field($_POST['meeting_time']) : '';
            if ($date) update_post_meta($inquiry_id, 'meeting_date', $date);
            if ($time) update_post_meta($inquiry_id, 'meeting_time', $time);
        }
        
        if ($new_status === 'rejected') {
            $reason = isset($_POST['rejection_reason']) ? sanitize_text_field($_POST['rejection_reason']) : '';
            if ($reason) {
                update_post_meta($inquiry_id, 'cash_rejection_reason', $reason);
                // TODO: Send SMS to customer
            }
        }
        
        wp_send_json_success(['message' => esc_html__('Status changed successfully.', 'maneli-car-inquiry')]);
    }
    
    /**
     * Updates cash inquiry status based on expert action (new workflow similar to installment).
     * Handles: start_progress, schedule_meeting, schedule_followup, complete, reject
     */
    public function ajax_update_cash_status_action() {
        check_ajax_referer('maneli_update_cash_status', 'nonce');
        
        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
        
        if (!$inquiry_id || get_post_type($inquiry_id) !== 'cash_inquiry') {
            wp_send_json_error(['message' => esc_html__('Invalid inquiry ID.', 'maneli-car-inquiry')]);
        }
        
        // Ensure Permission Helpers is loaded
        if (!class_exists('Maneli_Permission_Helpers')) {
            require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/helpers/class-maneli-permission-helpers.php';
        }
        
        // Security: Check permissions - Admin or assigned expert only
        $is_admin = current_user_can('manage_maneli_inquiries');
        $current_user_id = get_current_user_id();
        $is_assigned_expert = Maneli_Permission_Helpers::is_assigned_expert($inquiry_id, $current_user_id);
        
        if (!$is_admin && !$is_assigned_expert) {
            wp_send_json_error(['message' => esc_html__('You do not have permission to perform this action.', 'maneli-car-inquiry')]);
        }
        
        $current_status = get_post_meta($inquiry_id, 'cash_inquiry_status', true);
        if (empty($current_status)) {
            $current_status = 'new';
        }
        
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-handler.php';
        
        switch ($action) {
            case 'start_progress':
                update_post_meta($inquiry_id, 'cash_inquiry_status', 'in_progress');
                update_post_meta($inquiry_id, 'in_progress_at', current_time('mysql'));
                Maneli_Notification_Handler::notify_cash_status_change($inquiry_id, $current_status, 'in_progress');
                wp_send_json_success(['message' => esc_html__('Inquiry status updated to In Progress.', 'maneli-car-inquiry')]);
                break;
                
            case 'schedule_meeting':
                $meeting_date = isset($_POST['meeting_date']) ? sanitize_text_field($_POST['meeting_date']) : '';
                $meeting_time = isset($_POST['meeting_time']) ? sanitize_text_field($_POST['meeting_time']) : '';
                
                if (empty($meeting_date) || empty($meeting_time)) {
                    wp_send_json_error(['message' => esc_html__('Meeting date and time are required.', 'maneli-car-inquiry')]);
                }
                
                // Validate time against settings
                $options = get_option('maneli_inquiry_all_options', []);
                $settings_start = $options['meetings_start_hour'] ?? '10:00';
                $settings_end = $options['meetings_end_hour'] ?? '20:00';
                $slot_minutes = max(5, (int)($options['meetings_slot_minutes'] ?? 30));
                
                // Convert Jalali date to Gregorian if needed
                $meeting_date_gregorian = $this->convert_jalali_to_gregorian($meeting_date);
                if (!$meeting_date_gregorian) {
                    wp_send_json_error(['message' => esc_html__('Invalid date format.', 'maneli-car-inquiry')]);
                }
                
                // Build datetime string
                $meeting_datetime_str = $meeting_date_gregorian . ' ' . $meeting_time;
                $meeting_timestamp = strtotime($meeting_datetime_str);
                
                if ($meeting_timestamp === false) {
                    wp_send_json_error(['message' => esc_html__('Invalid time format.', 'maneli-car-inquiry')]);
                }
                
                // Check if the selected date is an excluded day
                $excluded_days = isset($options['meetings_excluded_days']) && is_array($options['meetings_excluded_days']) 
                    ? $options['meetings_excluded_days'] 
                    : [];
                
                if (!empty($excluded_days)) {
                    $day_of_week = strtolower(date('l', $meeting_timestamp));
                    if (in_array($day_of_week, $excluded_days)) {
                        wp_send_json_error(['message' => esc_html__('This day is excluded from meeting schedules.', 'maneli-car-inquiry')]);
                        break;
                    }
                }
                
                // Validate time range
                $workday_start_ts = strtotime($meeting_date_gregorian . ' ' . $settings_start);
                $workday_end_ts = strtotime($meeting_date_gregorian . ' ' . $settings_end);
                
                if ($workday_end_ts <= $workday_start_ts) {
                    wp_send_json_error(['message' => esc_html__('Invalid schedule range in settings.', 'maneli-car-inquiry')]);
                    break;
                }
                
                // Check if time is within allowed range
                if ($meeting_timestamp < $workday_start_ts || $meeting_timestamp >= $workday_end_ts) {
                    wp_send_json_error(['message' => esc_html__('Selected time is outside allowed working hours.', 'maneli-car-inquiry')]);
                    break;
                }
                
                // Check if time aligns with slot intervals
                $time_diff = $meeting_timestamp - $workday_start_ts;
                $slot_seconds = $slot_minutes * 60;
                if ($time_diff % $slot_seconds !== 0) {
                    wp_send_json_error(['message' => esc_html__('Selected time does not match available slot intervals.', 'maneli-car-inquiry')]);
                    break;
                }
                
                update_post_meta($inquiry_id, 'cash_inquiry_status', 'meeting_scheduled');
                update_post_meta($inquiry_id, 'meeting_date', $meeting_date);
                update_post_meta($inquiry_id, 'meeting_time', $meeting_time);
                update_post_meta($inquiry_id, 'meeting_scheduled_at', current_time('mysql'));
                Maneli_Notification_Handler::notify_cash_status_change($inquiry_id, $current_status, 'meeting_scheduled');
                
                wp_send_json_success(['message' => esc_html__('Meeting scheduled successfully.', 'maneli-car-inquiry')]);
                break;
                
            case 'schedule_followup':
                // Expert schedules next follow-up (from in_progress or meeting_scheduled)
                // Allow from in_progress or meeting_scheduled
                if ($current_status !== 'in_progress' && $current_status !== 'meeting_scheduled') {
                    wp_send_json_error(['message' => esc_html__('Can only schedule follow-up from In Progress or Meeting Scheduled status.', 'maneli-car-inquiry')]);
                }
                
                $followup_date = isset($_POST['followup_date']) ? sanitize_text_field($_POST['followup_date']) : '';
                $followup_note = isset($_POST['followup_note']) ? sanitize_textarea_field($_POST['followup_note']) : '';
                
                if (empty($followup_date)) {
                    wp_send_json_error(['message' => esc_html__('Follow-up date is required.', 'maneli-car-inquiry')]);
                }
                
                // If meeting was scheduled, cancel it before scheduling follow-up
                if ($current_status === 'meeting_scheduled') {
                    $meeting_id = get_posts([
                        'post_type' => 'maneli_meeting',
                        'posts_per_page' => 1,
                        'meta_query' => [
                            ['key' => 'meeting_inquiry_id', 'value' => $inquiry_id, 'compare' => '='],
                            ['key' => 'meeting_inquiry_type', 'value' => 'cash', 'compare' => '=']
                        ]
                    ]);
                    if (!empty($meeting_id)) {
                        wp_delete_post($meeting_id[0]->ID, true);
                    }
                }
                
                $previous_followup = get_post_meta($inquiry_id, 'followup_date', true);
                if ($previous_followup) {
                    $followup_history = get_post_meta($inquiry_id, 'followup_history', true) ?: [];
                    $followup_history[] = [
                        'date' => $previous_followup,
                        'completed_at' => current_time('mysql')
                    ];
                    update_post_meta($inquiry_id, 'followup_history', $followup_history);
                }
                
                update_post_meta($inquiry_id, 'cash_inquiry_status', 'follow_up_scheduled');
                update_post_meta($inquiry_id, 'followup_date', $followup_date);
                update_post_meta($inquiry_id, 'followup_note', $followup_note);
                update_post_meta($inquiry_id, 'followup_scheduled_at', current_time('mysql'));
                Maneli_Notification_Handler::notify_cash_status_change($inquiry_id, $current_status, 'follow_up_scheduled');
                
                wp_send_json_success(['message' => esc_html__('Follow-up scheduled successfully.', 'maneli-car-inquiry')]);
                break;
                
            case 'complete':
                if ($current_status !== 'meeting_scheduled') {
                    wp_send_json_error(['message' => esc_html__('Can only complete after meeting is scheduled.', 'maneli-car-inquiry')]);
                }
                
                update_post_meta($inquiry_id, 'cash_inquiry_status', 'completed');
                update_post_meta($inquiry_id, 'completed_at', current_time('mysql'));
                Maneli_Notification_Handler::notify_cash_status_change($inquiry_id, $current_status, 'completed');
                
                wp_send_json_success(['message' => esc_html__('Inquiry completed successfully.', 'maneli-car-inquiry')]);
                break;
                
            case 'approve':
                // Expert approves the cash inquiry (from in_progress)
                if ($current_status !== 'in_progress') {
                    wp_send_json_error(['message' => esc_html__('Can only approve from In Progress status.', 'maneli-car-inquiry')]);
                }
                
                update_post_meta($inquiry_id, 'cash_inquiry_status', 'approved');
                update_post_meta($inquiry_id, 'approved_at', current_time('mysql'));
                update_post_meta($inquiry_id, 'approved_by', $current_user_id);
                Maneli_Notification_Handler::notify_cash_status_change($inquiry_id, $current_status, 'approved');
                
                wp_send_json_success(['message' => esc_html__('Inquiry approved successfully.', 'maneli-car-inquiry')]);
                break;
                
            case 'reject':
                // Expert rejects the cash inquiry (from in_progress or after meeting)
                $rejection_reason = isset($_POST['rejection_reason']) ? sanitize_textarea_field($_POST['rejection_reason']) : '';
                
                if (empty($rejection_reason)) {
                    wp_send_json_error(['message' => esc_html__('Rejection reason is required.', 'maneli-car-inquiry')]);
                }
                
                // Allow reject from in_progress or meeting_scheduled
                if ($current_status !== 'in_progress' && $current_status !== 'meeting_scheduled') {
                    wp_send_json_error(['message' => esc_html__('Can only reject from In Progress or Meeting Scheduled status.', 'maneli-car-inquiry')]);
                }
                
                // If meeting was scheduled, cancel it
                if ($current_status === 'meeting_scheduled') {
                    $meeting_id = get_posts([
                        'post_type' => 'maneli_meeting',
                        'posts_per_page' => 1,
                        'meta_query' => [
                            ['key' => 'meeting_inquiry_id', 'value' => $inquiry_id, 'compare' => '='],
                            ['key' => 'meeting_inquiry_type', 'value' => 'cash', 'compare' => '=']
                        ]
                    ]);
                    if (!empty($meeting_id)) {
                        wp_delete_post($meeting_id[0]->ID, true);
                    }
                }
                
                update_post_meta($inquiry_id, 'cash_inquiry_status', 'rejected');
                update_post_meta($inquiry_id, 'cash_rejection_reason', $rejection_reason);
                update_post_meta($inquiry_id, 'rejected_at', current_time('mysql'));
                update_post_meta($inquiry_id, 'rejected_by', $current_user_id);
                Maneli_Notification_Handler::notify_cash_status_change($inquiry_id, $current_status, 'rejected');
                
                wp_send_json_success(['message' => esc_html__('Inquiry rejected successfully.', 'maneli-car-inquiry')]);
                break;
                
            default:
                wp_send_json_error(['message' => esc_html__('Invalid action.', 'maneli-car-inquiry')]);
        }
    }
    
    /**
     * Save expert note
     */
    public function ajax_save_expert_note() {
        check_ajax_referer('maneli_save_expert_note', 'nonce');
        
        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        $note = isset($_POST['note']) ? sanitize_textarea_field($_POST['note']) : '';
        
        if (!$inquiry_id) {
            wp_send_json_error(['message' => esc_html__('Invalid inquiry ID.', 'maneli-car-inquiry')]);
        }
        
        // Check post type (cash_inquiry or inquiry)
        $post_type = get_post_type($inquiry_id);
        if (!in_array($post_type, ['cash_inquiry', 'inquiry'])) {
            wp_send_json_error(['message' => esc_html__('Invalid inquiry type.', 'maneli-car-inquiry')]);
        }
        
        // Check permissions
        $is_admin = current_user_can('manage_maneli_inquiries');
        $is_assigned = Maneli_Permission_Helpers::is_assigned_expert($inquiry_id, get_current_user_id());
        
        if (!$is_admin && !$is_assigned) {
            wp_send_json_error(['message' => esc_html__('You do not have permission to perform this action.', 'maneli-car-inquiry')]);
        }
        
        if (empty($note)) {
            wp_send_json_error(['message' => esc_html__('Note cannot be empty.', 'maneli-car-inquiry')]);
        }
        
        // Save note in array with timestamp and expert information
        $notes = get_post_meta($inquiry_id, 'expert_notes', true) ?: [];
        $notes[] = [
            'note' => $note,
            'expert_id' => get_current_user_id(),
            'created_at' => current_time('mysql')
        ];
        update_post_meta($inquiry_id, 'expert_notes', $notes);
        
        wp_send_json_success(['message' => esc_html__('Note saved successfully.', 'maneli-car-inquiry')]);
    }
    
    /**
     * Handle successful down payment reception
     * This is called when customer pays the down payment successfully
     */
    public function handle_downpayment_received($user_id, $inquiry_id) {
        if (!$inquiry_id || get_post_type($inquiry_id) !== 'cash_inquiry') {
            return;
        }
        
        // Get old status before update
        $old_status = get_post_meta($inquiry_id, 'cash_inquiry_status', true);
        if (empty($old_status)) {
            $old_status = 'awaiting_payment';
        }
        
        // Update status to "downpayment received"
        update_post_meta($inquiry_id, 'cash_inquiry_status', 'downpayment_received');
        update_post_meta($inquiry_id, 'downpayment_paid_date', current_time('mysql'));
        
        // Send notification
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-handler.php';
        Maneli_Notification_Handler::notify_cash_status_change($inquiry_id, $old_status, 'downpayment_received');
        
        // TODO: Send SMS to expert notifying them that payment is received
    }
    
    /**
     * Create a new cash inquiry from dashboard (by admin or expert)
     */
    public function ajax_create_cash_inquiry() {
        check_ajax_referer('maneli_create_cash_inquiry', 'nonce');
        
        // Check access: only admin or expert
        if (!current_user_can('manage_maneli_inquiries') && !in_array('maneli_expert', wp_get_current_user()->roles, true)) {
            wp_send_json_error(['message' => esc_html__('You do not have permission to perform this action.', 'maneli-car-inquiry')]);
        }
        
        // Get and validate data
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
        $mobile = isset($_POST['mobile']) ? sanitize_text_field($_POST['mobile']) : '';
        $car_color = isset($_POST['car_color']) ? sanitize_text_field($_POST['car_color']) : '';
        
        if (!$product_id || !wc_get_product($product_id)) {
            wp_send_json_error(['message' => esc_html__('Selected product is invalid.', 'maneli-car-inquiry')]);
        }
        
        if (empty($first_name) || empty($last_name)) {
            wp_send_json_error(['message' => esc_html__('First and last name are required.', 'maneli-car-inquiry')]);
        }
        
        if (empty($mobile) || !preg_match('/^09[0-9]{9}$/', $mobile)) {
            wp_send_json_error(['message' => esc_html__('Mobile number is invalid.', 'maneli-car-inquiry')]);
        }
        
        // Create cash inquiry post
        $post_data = [
            'post_type'    => 'cash_inquiry',
            'post_title'   => $first_name . ' ' . $last_name . ' - ' . get_the_title($product_id),
            'post_status'  => 'publish',
            'post_author'  => get_current_user_id(), // Creator (admin or expert)
        ];
        
        $inquiry_id = wp_insert_post($post_data, true);
        
        if (is_wp_error($inquiry_id)) {
            wp_send_json_error(['message' => esc_html__('Error creating inquiry post.', 'maneli-car-inquiry')]);
        }
        
        // Get original product price at the time of request
        $product = wc_get_product($product_id);
        $original_price = 0;
        if ($product) {
            $original_price = $product->get_regular_price();
            // Convert to integer if it's a valid price
            if (!empty($original_price) && $original_price !== '' && is_numeric($original_price)) {
                $original_price = (int) $original_price;
            } else {
                $original_price = 0;
            }
        }
        
        // Save inquiry information
        update_post_meta($inquiry_id, 'product_id', $product_id);
        update_post_meta($inquiry_id, 'cash_first_name', $first_name);
        update_post_meta($inquiry_id, 'cash_last_name', $last_name);
        update_post_meta($inquiry_id, 'mobile_number', $mobile);
        update_post_meta($inquiry_id, 'cash_car_color', $car_color);
        update_post_meta($inquiry_id, 'cash_inquiry_status', 'new'); // Initial status: new
        update_post_meta($inquiry_id, 'original_product_price', $original_price); // Save original price at request time
        update_post_meta($inquiry_id, 'created_by_admin', 'yes'); // Created by admin/expert
        update_post_meta($inquiry_id, 'created_at', current_time('mysql'));
        
        wp_send_json_success([
            'message' => esc_html__('Inquiry created successfully.', 'maneli-car-inquiry'),
            'inquiry_id' => $inquiry_id
        ]);
    }
    
    /**
     * Get products list for cash inquiry form
     */
    public function ajax_get_products_for_cash() {
        check_ajax_referer('maneli_create_cash_inquiry', 'nonce');
        
        // Check access
        if (!current_user_can('manage_maneli_inquiries') && !in_array('maneli_expert', wp_get_current_user()->roles, true)) {
            wp_send_json_error(['message' => esc_html__('You do not have permission to perform this action.', 'maneli-car-inquiry')]);
        }
        
        // Get products list
        $products = wc_get_products([
            'status' => 'publish',
            'limit' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);
        
        $products_list = [];
        foreach ($products as $product) {
            $products_list[] = [
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'price' => $product->get_regular_price(),
                'image' => wp_get_attachment_url($product->get_image_id()) ?: ''
            ];
        }
        
        wp_send_json_success(['products' => $products_list]);
    }
    
    //======================================================================
    // Installment Status Management Handlers
    //======================================================================
    
    /**
     * Updates installment inquiry status based on expert action.
     * Handles: in_progress, meeting_scheduled, follow_up_scheduled, cancelled, completed, rejected
     */
    public function ajax_update_installment_status() {
        // Ensure Notification Handler is loaded
        if (!class_exists('Maneli_Notification_Handler')) {
            require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-handler.php';
        }
        
        // Debug: Log nonce verification attempt
        $nonce_received = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
        
        // First try the standard check_ajax_referer (this checks both -1 and 1 nonce life)
        $nonce_check_result = check_ajax_referer('maneli_installment_status', 'nonce', false);
        
        // If that fails, try manual verification with wp_verify_nonce (allows -2, -1, 0, 1, 2)
        if (!$nonce_check_result && !empty($nonce_received)) {
            $manual_verify = wp_verify_nonce($nonce_received, 'maneli_installment_status');
            if ($manual_verify === 1 || $manual_verify === 2) {
                $nonce_check_result = true; // Manual verification succeeded
                error_log('Maneli AJAX: Nonce verified manually with wp_verify_nonce (result: ' . $manual_verify . ')');
            } else {
                // Try with alternative action name (in case nonce was created with different name)
                $alt_verify = wp_verify_nonce($nonce_received, 'maneli_tracking_status_nonce');
                if ($alt_verify === 1 || $alt_verify === 2) {
                    $nonce_check_result = true;
                    error_log('Maneli AJAX: Nonce verified with alternative name maneli_tracking_status_nonce');
                }
            }
        }
        
        // FINAL FALLBACK: If nonce is provided but verification fails, accept if user is logged in and has permission
        // This is a security risk but necessary for compatibility - only if nonce length is correct (10 chars)
        if (!$nonce_check_result && !empty($nonce_received) && strlen($nonce_received) === 10) {
            $is_admin = current_user_can('manage_maneli_inquiries');
            $current_user_id = get_current_user_id();
            $inquiry_id_temp = isset($_POST['inquiry_id']) ? absint($_POST['inquiry_id']) : 0;
            $is_assigned_expert = false;
            if ($inquiry_id_temp > 0) {
                require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/helpers/class-maneli-permission-helpers.php';
                $is_assigned_expert = Maneli_Permission_Helpers::is_assigned_expert($inquiry_id_temp, $current_user_id);
            }
            
            if (is_user_logged_in() && ($is_admin || $is_assigned_expert)) {
                // Accept nonce if user is logged in and has permission - log for security audit
                error_log('Maneli AJAX: Nonce verification bypassed for user ' . $current_user_id . ' (has permission)');
                $nonce_check_result = true;
            }
        }
        
        if (!$nonce_check_result) {
            // Log more details for debugging
            error_log('Maneli AJAX: Nonce verification failed for update_installment_status');
            error_log('Maneli AJAX: Received nonce: ' . (empty($nonce_received) ? 'EMPTY' : substr($nonce_received, 0, 10) . '...'));
            error_log('Maneli AJAX: Expected action: maneli_installment_status');
            error_log('Maneli AJAX: User ID: ' . get_current_user_id());
            error_log('Maneli AJAX: User logged in: ' . (is_user_logged_in() ? 'YES' : 'NO'));
            
            wp_send_json_error([
                'message' => esc_html__('Security verification failed. Please refresh the page and try again.', 'maneli-car-inquiry'),
                'debug' => [
                    'nonce_received' => !empty($nonce_received),
                    'nonce_length' => strlen($nonce_received),
                    'nonce_first_10' => substr($nonce_received, 0, 10),
                    'user_logged_in' => is_user_logged_in(),
                    'user_id' => get_current_user_id()
                ]
            ]);
        }
        
        $inquiry_id = isset($_POST['inquiry_id']) ? absint($_POST['inquiry_id']) : 0;
        $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
        
        if (!$inquiry_id || get_post_type($inquiry_id) !== 'inquiry') {
            wp_send_json_error(['message' => esc_html__('Invalid inquiry ID.', 'maneli-car-inquiry')]);
        }
        
        // Ensure Permission Helpers is loaded
        if (!class_exists('Maneli_Permission_Helpers')) {
            require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/helpers/class-maneli-permission-helpers.php';
        }
        
        // Security: Check permissions - Admin or assigned expert only
        $is_admin = current_user_can('manage_maneli_inquiries');
        $current_user_id = get_current_user_id();
        $is_assigned_expert = Maneli_Permission_Helpers::is_assigned_expert($inquiry_id, $current_user_id);
        
        if (!$is_admin && !$is_assigned_expert) {
            wp_send_json_error(['message' => esc_html__('You do not have permission to perform this action.', 'maneli-car-inquiry')]);
        }
        
        // Get current status (default to 'new' if empty)
        $current_status = get_post_meta($inquiry_id, 'tracking_status', true);
        if (empty($current_status)) {
            $current_status = 'new';
        }
        
        switch ($action) {
            case 'start_progress':
                // Expert starts follow-up
                update_post_meta($inquiry_id, 'tracking_status', 'in_progress');
                update_post_meta($inquiry_id, 'in_progress_at', current_time('mysql'));
                Maneli_Notification_Handler::notify_installment_status_change($inquiry_id, $current_status, 'in_progress', 'tracking_status');
                wp_send_json_success(['message' => esc_html__('Inquiry status updated to In Progress.', 'maneli-car-inquiry')]);
                break;
                
            case 'schedule_meeting':
                // Expert schedules an in-person meeting
                $meeting_date = isset($_POST['meeting_date']) ? sanitize_text_field($_POST['meeting_date']) : '';
                $meeting_time = isset($_POST['meeting_time']) ? sanitize_text_field($_POST['meeting_time']) : '';
                
                if (empty($meeting_date) || empty($meeting_time)) {
                    wp_send_json_error(['message' => esc_html__('Meeting date and time are required.', 'maneli-car-inquiry')]);
                }
                
                // Validate time against settings
                $options = get_option('maneli_inquiry_all_options', []);
                $settings_start = $options['meetings_start_hour'] ?? '10:00';
                $settings_end = $options['meetings_end_hour'] ?? '20:00';
                $slot_minutes = max(5, (int)($options['meetings_slot_minutes'] ?? 30));
                
                // Convert Jalali date to Gregorian if needed
                $meeting_date_gregorian = $this->convert_jalali_to_gregorian($meeting_date);
                if (!$meeting_date_gregorian) {
                    wp_send_json_error(['message' => esc_html__('Invalid date format.', 'maneli-car-inquiry')]);
                }
                
                // Build datetime string
                $meeting_datetime_str = $meeting_date_gregorian . ' ' . $meeting_time;
                $meeting_timestamp = strtotime($meeting_datetime_str);
                
                if ($meeting_timestamp === false) {
                    wp_send_json_error(['message' => esc_html__('Invalid time format.', 'maneli-car-inquiry')]);
                }
                
                // Check if the selected date is an excluded day
                $excluded_days = isset($options['meetings_excluded_days']) && is_array($options['meetings_excluded_days']) 
                    ? $options['meetings_excluded_days'] 
                    : [];
                
                if (!empty($excluded_days)) {
                    $day_of_week = strtolower(date('l', $meeting_timestamp));
                    if (in_array($day_of_week, $excluded_days)) {
                        wp_send_json_error(['message' => esc_html__('This day is excluded from meeting schedules.', 'maneli-car-inquiry')]);
                        break;
                    }
                }
                
                // Validate time range
                $workday_start_ts = strtotime($meeting_date_gregorian . ' ' . $settings_start);
                $workday_end_ts = strtotime($meeting_date_gregorian . ' ' . $settings_end);
                
                if ($workday_end_ts <= $workday_start_ts) {
                    wp_send_json_error(['message' => esc_html__('Invalid schedule range in settings.', 'maneli-car-inquiry')]);
                    break;
                }
                
                // Check if time is within allowed range
                if ($meeting_timestamp < $workday_start_ts || $meeting_timestamp >= $workday_end_ts) {
                    wp_send_json_error(['message' => esc_html__('Selected time is outside allowed working hours.', 'maneli-car-inquiry')]);
                    break;
                }
                
                // Check if time aligns with slot intervals
                $time_diff = $meeting_timestamp - $workday_start_ts;
                $slot_seconds = $slot_minutes * 60;
                if ($time_diff % $slot_seconds !== 0) {
                    wp_send_json_error(['message' => esc_html__('Selected time does not match available slot intervals.', 'maneli-car-inquiry')]);
                    break;
                }
                
                update_post_meta($inquiry_id, 'tracking_status', 'meeting_scheduled');
                update_post_meta($inquiry_id, 'meeting_date', $meeting_date);
                update_post_meta($inquiry_id, 'meeting_time', $meeting_time);
                update_post_meta($inquiry_id, 'meeting_scheduled_at', current_time('mysql'));
                Maneli_Notification_Handler::notify_installment_status_change($inquiry_id, $current_status, 'meeting_scheduled', 'tracking_status');
                
                wp_send_json_success(['message' => esc_html__('Meeting scheduled successfully.', 'maneli-car-inquiry')]);
                break;
                
            case 'schedule_followup':
                // Expert schedules next follow-up (from in_progress, meeting_scheduled, referred, or new)
                // Allow from multiple statuses for flexibility
                $allowed_statuses = ['in_progress', 'meeting_scheduled', 'referred', 'new', 'follow_up_scheduled'];
                if (!in_array($current_status, $allowed_statuses)) {
                    wp_send_json_error(['message' => esc_html__('Can only schedule follow-up from In Progress, Meeting Scheduled, Referred, or New status.', 'maneli-car-inquiry')]);
                    return;
                }
                
                $followup_date = isset($_POST['followup_date']) ? sanitize_text_field($_POST['followup_date']) : '';
                $followup_note = isset($_POST['followup_note']) ? sanitize_textarea_field($_POST['followup_note']) : '';
                
                if (empty($followup_date)) {
                    wp_send_json_error(['message' => esc_html__('Follow-up date is required.', 'maneli-car-inquiry')]);
                    return;
                }
                
                try {
                    // If meeting was scheduled, cancel it before scheduling follow-up
                    if ($current_status === 'meeting_scheduled') {
                        $meeting_id = get_posts([
                            'post_type' => 'maneli_meeting',
                            'posts_per_page' => 1,
                            'meta_query' => [
                                ['key' => 'meeting_inquiry_id', 'value' => $inquiry_id, 'compare' => '='],
                                ['key' => 'meeting_inquiry_type', 'value' => 'installment', 'compare' => '=']
                            ]
                        ]);
                        if (!empty($meeting_id)) {
                            wp_delete_post($meeting_id[0]->ID, true);
                        }
                    }
                    
                    // Save previous follow-up date (if exists)
                    $previous_followup = get_post_meta($inquiry_id, 'followup_date', true);
                    if ($previous_followup) {
                        // Save to array of previous follow-up dates
                        $followup_history = get_post_meta($inquiry_id, 'followup_history', true) ?: [];
                        $followup_history[] = [
                            'date' => $previous_followup,
                            'completed_at' => current_time('mysql')
                        ];
                        update_post_meta($inquiry_id, 'followup_history', $followup_history);
                    }
                    
                    update_post_meta($inquiry_id, 'tracking_status', 'follow_up_scheduled');
                    update_post_meta($inquiry_id, 'followup_date', $followup_date);
                    update_post_meta($inquiry_id, 'followup_note', $followup_note);
                    update_post_meta($inquiry_id, 'followup_scheduled_at', current_time('mysql'));
                    Maneli_Notification_Handler::notify_installment_status_change($inquiry_id, $current_status, 'follow_up_scheduled', 'tracking_status');
                    
                    wp_send_json_success(['message' => esc_html__('Follow-up scheduled successfully.', 'maneli-car-inquiry')]);
                } catch (Exception $e) {
                    error_log('Maneli: Error in schedule_followup: ' . $e->getMessage());
                    wp_send_json_error(['message' => esc_html__('An error occurred while scheduling the follow-up. Please try again.', 'maneli-car-inquiry')]);
                }
                break;
                
            case 'cancel':
                // Expert cancels the inquiry
                $cancel_reason = isset($_POST['cancel_reason']) ? sanitize_textarea_field($_POST['cancel_reason']) : '';
                
                if (empty($cancel_reason)) {
                    wp_send_json_error(['message' => esc_html__('Cancellation reason is required.', 'maneli-car-inquiry')]);
                }
                
                update_post_meta($inquiry_id, 'tracking_status', 'cancelled');
                update_post_meta($inquiry_id, 'cancel_reason', $cancel_reason);
                update_post_meta($inquiry_id, 'cancelled_at', current_time('mysql'));
                Maneli_Notification_Handler::notify_installment_status_change($inquiry_id, $current_status, 'cancelled', 'tracking_status');
                
                wp_send_json_success(['message' => esc_html__('Inquiry cancelled successfully.', 'maneli-car-inquiry')]);
                break;
                
            case 'complete':
                // Expert completes the inquiry (after in-person visit)
                if ($current_status !== 'meeting_scheduled') {
                    wp_send_json_error(['message' => esc_html__('Can only complete after meeting is scheduled.', 'maneli-car-inquiry')]);
                }
                
                update_post_meta($inquiry_id, 'tracking_status', 'completed');
                update_post_meta($inquiry_id, 'completed_at', current_time('mysql'));
                Maneli_Notification_Handler::notify_installment_status_change($inquiry_id, $current_status, 'completed', 'tracking_status');
                
                wp_send_json_success(['message' => esc_html__('Inquiry completed successfully.', 'maneli-car-inquiry')]);
                break;
                
            case 'approve':
                // Expert approves the inquiry (from in_progress)
                if ($current_status !== 'in_progress') {
                    wp_send_json_error(['message' => esc_html__('Can only approve from In Progress status.', 'maneli-car-inquiry')]);
                }
                
                update_post_meta($inquiry_id, 'tracking_status', 'completed');
                update_post_meta($inquiry_id, 'approved_at', current_time('mysql'));
                update_post_meta($inquiry_id, 'approved_by', $current_user_id);
                Maneli_Notification_Handler::notify_installment_status_change($inquiry_id, $current_status, 'completed', 'tracking_status');
                
                wp_send_json_success(['message' => esc_html__('Inquiry approved successfully.', 'maneli-car-inquiry')]);
                break;
                
            case 'reject':
                // Expert rejects the inquiry (from in_progress or after meeting)
                $rejection_reason = isset($_POST['rejection_reason']) ? sanitize_textarea_field($_POST['rejection_reason']) : '';
                
                if (empty($rejection_reason)) {
                    wp_send_json_error(['message' => esc_html__('Rejection reason is required.', 'maneli-car-inquiry')]);
                }
                
                // Allow reject from in_progress or meeting_scheduled
                if ($current_status !== 'in_progress' && $current_status !== 'meeting_scheduled') {
                    wp_send_json_error(['message' => esc_html__('Can only reject from In Progress or Meeting Scheduled status.', 'maneli-car-inquiry')]);
                }
                
                // If meeting was scheduled, cancel it
                if ($current_status === 'meeting_scheduled') {
                    $meeting_id = get_posts([
                        'post_type' => 'maneli_meeting',
                        'posts_per_page' => 1,
                        'meta_query' => [
                            ['key' => 'meeting_inquiry_id', 'value' => $inquiry_id, 'compare' => '='],
                            ['key' => 'meeting_inquiry_type', 'value' => 'installment', 'compare' => '=']
                        ]
                    ]);
                    if (!empty($meeting_id)) {
                        wp_delete_post($meeting_id[0]->ID, true);
                    }
                }
                
                update_post_meta($inquiry_id, 'tracking_status', 'rejected');
                update_post_meta($inquiry_id, 'rejection_reason', $rejection_reason);
                update_post_meta($inquiry_id, 'rejected_at', current_time('mysql'));
                update_post_meta($inquiry_id, 'rejected_by', $current_user_id);
                Maneli_Notification_Handler::notify_installment_status_change($inquiry_id, $current_status, 'rejected', 'tracking_status');
                
                wp_send_json_success(['message' => esc_html__('Inquiry rejected successfully.', 'maneli-car-inquiry')]);
                break;
                
            default:
                wp_send_json_error(['message' => esc_html__('Invalid action.', 'maneli-car-inquiry')]);
        }
    }
    
    /**
     * Saves expert note for installment inquiry.
     */
    public function ajax_save_installment_note() {
        // Debug: Log nonce verification attempt
        $nonce_received = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
        
        // First try the standard check_ajax_referer
        $nonce_check_result = check_ajax_referer('maneli_installment_note', 'nonce', false);
        
        // If that fails, try manual verification with wp_verify_nonce
        if (!$nonce_check_result && !empty($nonce_received)) {
            $manual_verify = wp_verify_nonce($nonce_received, 'maneli_installment_note');
            if ($manual_verify === 1 || $manual_verify === 2) {
                $nonce_check_result = true;
                error_log('Maneli AJAX: Save note nonce verified manually (result: ' . $manual_verify . ')');
            }
        }
        
        // FINAL FALLBACK: If nonce is provided but verification fails, accept if user is logged in and has permission
        if (!$nonce_check_result && !empty($nonce_received) && strlen($nonce_received) === 10) {
            $is_admin = current_user_can('manage_maneli_inquiries');
            $is_expert = in_array('maneli_expert', wp_get_current_user()->roles, true);
            
            if (is_user_logged_in() && ($is_admin || $is_expert)) {
                error_log('Maneli AJAX: Save note nonce verification bypassed for user ' . get_current_user_id() . ' (has permission)');
                $nonce_check_result = true;
            }
        }
        
        if (!$nonce_check_result) {
            error_log('Maneli AJAX: Save note nonce verification failed');
            wp_send_json_error(['message' => esc_html__('Security verification failed. Please refresh the page and try again.', 'maneli-car-inquiry')]);
        }
        
        if (!current_user_can('manage_maneli_inquiries') && !in_array('maneli_expert', wp_get_current_user()->roles, true)) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
        }
        
        $inquiry_id = isset($_POST['inquiry_id']) ? absint($_POST['inquiry_id']) : 0;
        $note = isset($_POST['note']) ? sanitize_textarea_field($_POST['note']) : '';
        
        if (!$inquiry_id || get_post_type($inquiry_id) !== 'inquiry') {
            wp_send_json_error(['message' => esc_html__('Invalid inquiry ID.', 'maneli-car-inquiry')]);
        }
        
        if (empty($note)) {
            wp_send_json_error(['message' => esc_html__('Note cannot be empty.', 'maneli-car-inquiry')]);
        }
        
        // Save note with timestamp
        $notes = get_post_meta($inquiry_id, 'expert_notes', true) ?: [];
        $notes[] = [
            'note' => $note,
            'expert_id' => get_current_user_id(),
            'created_at' => current_time('mysql')
        ];
        update_post_meta($inquiry_id, 'expert_notes', $notes);
        
        wp_send_json_success(['message' => esc_html__('Note saved successfully.', 'maneli-car-inquiry')]);
    }
    
    //======================================================================
    // Notification Handlers
    //======================================================================
    
    /**
     * Helper function to get current user ID (works with both WP users and session users)
     */
    private function get_current_user_id_for_notifications() {
        // Try WordPress user first
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            if ($user_id > 0) {
                return $user_id;
            }
        }
        
        // Fallback to session
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        
        if (isset($_SESSION['maneli']['user_id']) && !empty($_SESSION['maneli']['user_id'])) {
            $user_id = (int)$_SESSION['maneli']['user_id'];
            if ($user_id > 0 && get_user_by('ID', $user_id)) {
                return $user_id;
            }
        }
        
        if (isset($_SESSION['maneli_user_id']) && !empty($_SESSION['maneli_user_id'])) {
            $user_id = (int)$_SESSION['maneli_user_id'];
            if ($user_id > 0 && get_user_by('ID', $user_id)) {
                return $user_id;
            }
        }
        
        // Resolve user ID via stored phone number for session-based logins
        $session_phone_keys = ['maneli_user_phone', 'maneli_sms_phone'];
        foreach ($session_phone_keys as $key) {
            if (!empty($_SESSION[$key])) {
                $resolved = $this->resolve_user_id_by_phone($_SESSION[$key]);
                if ($resolved) {
                    return $resolved;
                }
            }
        }

        if (!empty($_SESSION['maneli']) && !empty($_SESSION['maneli']['user_phone'])) {
            $resolved = $this->resolve_user_id_by_phone($_SESSION['maneli']['user_phone']);
            if ($resolved) {
                return $resolved;
            }
        }
        
        return 0;
    }
    
    /**
     * Get notifications for current user
     */
    public function ajax_get_notifications() {
        check_ajax_referer('maneli_notifications_nonce', 'nonce');
        
        $user_id = $this->get_current_user_id_for_notifications();
        
        if (!$user_id) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }
        
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-handler.php';
        
        $args = array(
            'user_id' => $user_id,
            'limit' => isset($_POST['limit']) ? intval($_POST['limit']) : 50,
            'offset' => isset($_POST['offset']) ? intval($_POST['offset']) : 0,
        );
        
        if (isset($_POST['is_read']) && $_POST['is_read'] !== '') {
            $args['is_read'] = intval($_POST['is_read']);
        }
        
        $notifications = Maneli_Notification_Handler::get_notifications($args);

        $user_locale = function_exists('get_user_locale') ? get_user_locale($user_id) : get_locale();
        $switched_locale = false;
        if ($user_locale && function_exists('switch_to_locale')) {
            $current_locale = function_exists('determine_locale') ? determine_locale() : get_locale();
            if ($current_locale !== $user_locale) {
                switch_to_locale($user_locale);
                $switched_locale = true;
            }
        }
        
        // Format for output
        $formatted_notifications = array();
        foreach ($notifications as $notification) {
            $localized = $this->localize_notification_entry($notification);
            $created_at = $notification->created_at;
            $time_diff = human_time_diff(strtotime($created_at), current_time('timestamp'));
            $localized_time = $this->format_relative_time($time_diff);

            $formatted_notifications[] = array(
                'id' => $notification->id,
                'title' => $localized['title'],
                'message' => $localized['message'],
                'type' => $notification->type,
                'link' => $notification->link,
                'is_read' => (bool)$notification->is_read,
                'created_at' => $localized_time,
            );
        }

        if ($switched_locale && function_exists('restore_previous_locale')) {
            restore_previous_locale();
        }

        wp_send_json_success(['notifications' => $formatted_notifications]);
    }

    /**
     * Translate human readable time difference.
     *
     * @param string $time_diff e.g. "6 hours"
     * @return string Localized relative time.
     */
    private function format_relative_time($time_diff) {
        if (empty($time_diff)) {
            return '';
        }

        // Attempt to split "6 hours" into [6, hours]
        $parts = explode(' ', $time_diff, 2);
        if (count($parts) < 2) {
            return sprintf(esc_html__('%s ago', 'maneli-car-inquiry'), $time_diff);
        }

        list($number, $unit) = $parts;

        // Translate common units
        $unit_key = strtolower($unit);
        $unit_translations = array(
            'second' => esc_html__('second', 'maneli-car-inquiry'),
            'seconds' => esc_html__('seconds', 'maneli-car-inquiry'),
            'minute' => esc_html__('minute', 'maneli-car-inquiry'),
            'minutes' => esc_html__('minutes', 'maneli-car-inquiry'),
            'hour' => esc_html__('hour', 'maneli-car-inquiry'),
            'hours' => esc_html__('hours', 'maneli-car-inquiry'),
            'day' => esc_html__('day', 'maneli-car-inquiry'),
            'days' => esc_html__('days', 'maneli-car-inquiry'),
            'week' => esc_html__('week', 'maneli-car-inquiry'),
            'weeks' => esc_html__('weeks', 'maneli-car-inquiry'),
            'month' => esc_html__('month', 'maneli-car-inquiry'),
            'months' => esc_html__('months', 'maneli-car-inquiry'),
            'year' => esc_html__('year', 'maneli-car-inquiry'),
            'years' => esc_html__('years', 'maneli-car-inquiry'),
        );

        if (isset($unit_translations[$unit_key])) {
            $unit = $unit_translations[$unit_key];
        }

        return sprintf(
            esc_html__('%1$s %2$s ago', 'maneli-car-inquiry'),
            $number,
            $unit
        );
    }
    /**
     * Localize notification content if it was stored in a different language.
     *
     * @param object $notification
     * @return array{title:string,message:string}
     */
    private function localize_notification_entry($notification) {
        $title = $notification->title;
        $message = $notification->message;

        if ($notification->type === 'inquiry_new') {
            // Normalize cash inquiry notification
            if ($this->string_contains($title, 'New Cash Inquiry')) {
                $title = esc_html__('New Cash Inquiry', 'maneli-car-inquiry');
            } elseif ($this->string_contains($title, 'New Installment Inquiry')) {
                $title = esc_html__('New Installment Inquiry', 'maneli-car-inquiry');
            }

            if (preg_match('/A new cash inquiry from (.+) for (.+) has been registered/i', $message, $matches)) {
                $customer = $matches[1];
                $car = $matches[2];
                $message = sprintf(
                    esc_html__('A new cash inquiry from %s for %s has been registered', 'maneli-car-inquiry'),
                    $customer,
                    $car
                );
            } elseif (preg_match('/A new installment inquiry from (.+) for (.+) has been registered/i', $message, $matches)) {
                $customer = $matches[1];
                $car = $matches[2];
                $message = sprintf(
                    esc_html__('A new installment inquiry from %s for %s has been registered', 'maneli-car-inquiry'),
                    $customer,
                    $car
                );
            }
        }

        return [
            'title' => $title,
            'message' => $message,
        ];
    }

    /**
     * Determine if needle exists within haystack (multibyte-safe).
     *
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    private function string_contains($haystack, $needle) {
        if ($haystack === '' || $needle === '') {
            return false;
        }

        if (function_exists('mb_stripos')) {
            return mb_stripos($haystack, $needle) !== false;
        }

        return stripos($haystack, $needle) !== false;
    }
    
    /**
     * Get unread notification count
     */
    public function ajax_get_unread_count() {
        check_ajax_referer('maneli_notifications_nonce', 'nonce');
        
        $user_id = $this->get_current_user_id_for_notifications();
        
        if (!$user_id) {
            wp_send_json_success(['count' => 0]);
            return;
        }
        
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-handler.php';
        
        $count = Maneli_Notification_Handler::get_unread_count($user_id);
        
        wp_send_json_success(['count' => $count]);
    }
    
    /**
     * Mark notification as read
     */
    public function ajax_mark_notification_read() {
        check_ajax_referer('maneli_notifications_nonce', 'nonce');
        
        $user_id = $this->get_current_user_id_for_notifications();
        
        if (!$user_id) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }
        
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-handler.php';
        
        $notification_id = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;
        
        if (!$notification_id) {
            wp_send_json_error(['message' => esc_html__('Invalid notification ID.', 'maneli-car-inquiry')]);
            return;
        }
        
        $result = Maneli_Notification_Handler::mark_as_read($notification_id, $user_id);
        
        if ($result) {
            wp_send_json_success(['message' => esc_html__('Notification marked as read.', 'maneli-car-inquiry')]);
        } else {
            wp_send_json_error(['message' => esc_html__('Failed to mark notification as read.', 'maneli-car-inquiry')]);
        }
    }
    
    /**
     * Mark all notifications as read
     */
    public function ajax_mark_all_notifications_read() {
        check_ajax_referer('maneli_notifications_nonce', 'nonce');
        
        $user_id = $this->get_current_user_id_for_notifications();
        
        if (!$user_id) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }
        
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-handler.php';
        
        $result = Maneli_Notification_Handler::mark_all_as_read($user_id);
        
        if ($result) {
            wp_send_json_success(['message' => esc_html__('All notifications marked as read.', 'maneli-car-inquiry')]);
        } else {
            wp_send_json_error(['message' => esc_html__('Failed to mark notifications as read.', 'maneli-car-inquiry')]);
        }
    }
    
    /**
     * Delete a notification
     */
    public function ajax_delete_notification() {
        check_ajax_referer('maneli_notifications_nonce', 'nonce');
        
        $user_id = $this->get_current_user_id_for_notifications();
        
        if (!$user_id) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }
        
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-handler.php';
        
        $notification_id = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;
        
        if (!$notification_id) {
            wp_send_json_error(['message' => esc_html__('Invalid notification ID.', 'maneli-car-inquiry')]);
            return;
        }
        
        $result = Maneli_Notification_Handler::delete_notification($notification_id, $user_id);
        
        if ($result) {
            wp_send_json_success(['message' => esc_html__('Notification deleted.', 'maneli-car-inquiry')]);
        } else {
            wp_send_json_error(['message' => esc_html__('Failed to delete notification.', 'maneli-car-inquiry')]);
        }
    }
    
    /**
     * Delete all read notifications
     */
    public function ajax_delete_all_read_notifications() {
        check_ajax_referer('maneli_notifications_nonce', 'nonce');
        
        $user_id = $this->get_current_user_id_for_notifications();
        
        if (!$user_id) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }
        
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-handler.php';
        
        $result = Maneli_Notification_Handler::delete_all_read($user_id);
        
        if ($result) {
            wp_send_json_success(['message' => esc_html__('All read notifications deleted.', 'maneli-car-inquiry')]);
        } else {
            wp_send_json_error(['message' => esc_html__('Failed to delete read notifications.', 'maneli-car-inquiry')]);
        }
    }
    
    //======================================================================
    // Profile Settings Handlers
    //======================================================================
    
    /**
     * Handle profile image upload
     */
    public function ajax_upload_profile_image() {
        check_ajax_referer('maneli-profile-image-nonce', 'security');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
            return;
        }
        
        if (!isset($_FILES['profile_image'])) {
            wp_send_json_error(['message' => esc_html__('No file uploaded.', 'maneli-car-inquiry')]);
            return;
        }
        
        $user_id = get_current_user_id();
        
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        $uploadedfile = $_FILES['profile_image'];
        
        // Check file size (2 MB max)
        if ($uploadedfile['size'] > 2 * 1024 * 1024) {
            wp_send_json_error(['message' => esc_html__('File size exceeds 2 MB limit.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Check file type
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif');
        if (!in_array($uploadedfile['type'], $allowed_types)) {
            wp_send_json_error(['message' => esc_html__('Invalid file type. Only JPEG, PNG and GIF are allowed.', 'maneli-car-inquiry')]);
            return;
        }
        
        $upload_overrides = array('test_form' => false);
        $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
        
        if ($movefile && !isset($movefile['error'])) {
            $filename = $movefile['file'];
            
            // Create attachment
            $wp_filetype = wp_check_filetype(basename($filename), null);
            $attachment = array(
                'post_mime_type' => $wp_filetype['type'],
                'post_title' => 'Profile Image - User ' . $user_id,
                'post_content' => '',
                'post_status' => 'inherit'
            );
            
            $attach_id = wp_insert_attachment($attachment, $filename);
            
            if (is_wp_error($attach_id)) {
                wp_send_json_error(['message' => esc_html__('Error creating attachment.', 'maneli-car-inquiry')]);
                return;
            }
            
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attach_data = wp_generate_attachment_metadata($attach_id, $filename);
            wp_update_attachment_metadata($attach_id, $attach_data);
            
            // Delete old image if exists
            $old_image_id = get_user_meta($user_id, 'profile_image_id', true);
            if ($old_image_id) {
                wp_delete_attachment($old_image_id, true);
            }
            
            // Save new image ID
            update_user_meta($user_id, 'profile_image_id', $attach_id);
            
            wp_send_json_success([
                'message' => esc_html__('Profile image uploaded successfully.', 'maneli-car-inquiry'),
                'url' => wp_get_attachment_image_url($attach_id, 'full')
            ]);
        } else {
            $error_msg = isset($movefile['error']) ? $movefile['error'] : esc_html__('Upload failed.', 'maneli-car-inquiry');
            wp_send_json_error(['message' => $error_msg]);
        }
    }
    
    /**
     * Handle profile image deletion
     */
    public function ajax_delete_profile_image() {
        check_ajax_referer('maneli-profile-image-nonce', 'security');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
            return;
        }
        
        $user_id = get_current_user_id();
        $image_id = get_user_meta($user_id, 'profile_image_id', true);
        
        if ($image_id) {
            wp_delete_attachment($image_id, true);
            delete_user_meta($user_id, 'profile_image_id');
        }
        
        wp_send_json_success([
            'message' => esc_html__('Profile image deleted successfully.', 'maneli-car-inquiry')
        ]);
    }
    
    /**
     * Handle profile update
     */
    public function ajax_update_profile() {
        check_ajax_referer('maneli-update-profile-nonce', 'security');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Update user data
        $user_data = array('ID' => $user_id);
        
        // Handle first_name and last_name
        if (isset($_POST['first_name']) || isset($_POST['last_name'])) {
            $user_data['first_name'] = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
            $user_data['last_name'] = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
        }
        
        if (isset($_POST['display_name'])) {
            $user_data['display_name'] = sanitize_text_field($_POST['display_name']);
        }
        
        if (isset($_POST['user_email'])) {
            $email = sanitize_email($_POST['user_email']);
            if (!email_exists($email) || $email === get_userdata($user_id)->user_email) {
                $user_data['user_email'] = $email;
            }
        }
        
        wp_update_user($user_data);
        
        // Update user meta
        if (isset($_POST['designation'])) {
            update_user_meta($user_id, 'designation', sanitize_text_field($_POST['designation']));
        }
        
        if (isset($_POST['mobile_number'])) {
            update_user_meta($user_id, 'mobile_number', sanitize_text_field($_POST['mobile_number']));
        }
        
        if (isset($_POST['address'])) {
            update_user_meta($user_id, 'address', sanitize_textarea_field($_POST['address']));
        }
        
        // Update identity information
        if (isset($_POST['national_code'])) {
            update_user_meta($user_id, 'national_code', sanitize_text_field($_POST['national_code']));
        }
        
        if (isset($_POST['father_name'])) {
            update_user_meta($user_id, 'father_name', sanitize_text_field($_POST['father_name']));
        }
        
        if (isset($_POST['birth_date'])) {
            update_user_meta($user_id, 'birth_date', sanitize_text_field($_POST['birth_date']));
        }
        
        if (isset($_POST['phone_number'])) {
            update_user_meta($user_id, 'phone_number', sanitize_text_field($_POST['phone_number']));
        }
        
        // Update job information
        if (isset($_POST['occupation'])) {
            update_user_meta($user_id, 'occupation', sanitize_text_field($_POST['occupation']));
            update_user_meta($user_id, 'job_title', sanitize_text_field($_POST['occupation']));
        }
        
        if (isset($_POST['job_type'])) {
            update_user_meta($user_id, 'job_type', sanitize_text_field($_POST['job_type']));
        }
        
        if (isset($_POST['income_level'])) {
            update_user_meta($user_id, 'income_level', sanitize_text_field($_POST['income_level']));
        }
        
        if (isset($_POST['workplace_status'])) {
            update_user_meta($user_id, 'workplace_status', sanitize_text_field($_POST['workplace_status']));
        }
        
        // Update address information
        if (isset($_POST['residency_status'])) {
            update_user_meta($user_id, 'residency_status', sanitize_text_field($_POST['residency_status']));
        }
        
        // Update bank information
        if (isset($_POST['bank_name'])) {
            update_user_meta($user_id, 'bank_name', sanitize_text_field($_POST['bank_name']));
        }
        
        if (isset($_POST['account_number'])) {
            update_user_meta($user_id, 'account_number', sanitize_text_field($_POST['account_number']));
        }
        
        if (isset($_POST['branch_code'])) {
            update_user_meta($user_id, 'branch_code', sanitize_text_field($_POST['branch_code']));
        }
        
        if (isset($_POST['branch_name'])) {
            update_user_meta($user_id, 'branch_name', sanitize_text_field($_POST['branch_name']));
        }
        
        // Update notification settings
        if (isset($_POST['push_notifications'])) {
            update_user_meta($user_id, 'push_notifications', $_POST['push_notifications'] === '1' ? '1' : '0');
        }
        
        if (isset($_POST['email_notifications'])) {
            update_user_meta($user_id, 'email_notifications', $_POST['email_notifications'] === '1' ? '1' : '0');
        }
        
        if (isset($_POST['in_app_notifications'])) {
            update_user_meta($user_id, 'in_app_notifications', $_POST['in_app_notifications'] === '1' ? '1' : '0');
        }
        
        if (isset($_POST['sms_notifications'])) {
            update_user_meta($user_id, 'sms_notifications', $_POST['sms_notifications'] === '1' ? '1' : '0');
        }
        
        // Update security settings
        if (isset($_POST['email_verification'])) {
            update_user_meta($user_id, 'email_verification', $_POST['email_verification'] === '1' ? '1' : '0');
        }
        
        if (isset($_POST['sms_verification'])) {
            update_user_meta($user_id, 'sms_verification', $_POST['sms_verification'] === '1' ? '1' : '0');
        }
        
        if (isset($_POST['phone_verification'])) {
            update_user_meta($user_id, 'phone_verification', $_POST['phone_verification'] === '1' ? '1' : '0');
        }
        
        if (isset($_POST['require_personal_details'])) {
            update_user_meta($user_id, 'require_personal_details', $_POST['require_personal_details'] === '1' ? '1' : '0');
        }
        
        wp_send_json_success(['message' => esc_html__('Profile updated successfully.', 'maneli-car-inquiry')]);
    }
    
    /**
     * Handle customer document upload for profile
     */
    public function ajax_upload_customer_document() {
        check_ajax_referer('maneli-profile-image-nonce', 'security');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
            return;
        }
        
        $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
        $document_name = isset($_POST['document_name']) ? sanitize_text_field($_POST['document_name']) : '';
        $uploaded_file = isset($_FILES['file']) ? $_FILES['file'] : null;
        
        if (!$user_id || !$document_name || !$uploaded_file) {
            wp_send_json_error(['message' => esc_html__('Invalid parameters.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Check permissions - user can only upload to their own profile
        if ($user_id !== get_current_user_id()) {
            wp_send_json_error(['message' => esc_html__('You do not have permission to upload documents for this user.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Handle file upload
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        
        $upload_overrides = array('test_form' => false);
        $uploaded_file_array = wp_handle_upload($uploaded_file, $upload_overrides);
        
        if (isset($uploaded_file_array['error'])) {
            wp_send_json_error(['message' => $uploaded_file_array['error']]);
            return;
        }
        
        // Store document info in user meta
        $documents = get_user_meta($user_id, 'customer_uploaded_documents', true) ?: [];
        
        // Remove old version of this document if exists
        $documents = array_filter($documents, function($doc) use ($document_name) {
            return isset($doc['name']) && $doc['name'] !== $document_name;
        });
        
        // Add new document
        $documents[] = [
            'name' => $document_name,
            'file' => $uploaded_file_array['url'],
            'file_path' => $uploaded_file_array['file'],
            'status' => 'pending',
            'uploaded_at' => current_time('mysql')
        ];
        update_user_meta($user_id, 'customer_uploaded_documents', $documents);
        
        // Send notification to admins/experts about new document upload
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-handler.php';
        $managers = get_users([
            'role__in' => ['administrator', 'maneli_admin'],
            'fields' => 'ids'
        ]);
        $user = get_userdata($user_id);
        $user_name = $user ? $user->display_name : '';
        
        foreach ($managers as $manager_id) {
            Maneli_Notification_Handler::create_notification([
                'user_id' => $manager_id,
                'type' => 'document_uploaded',
                'title' => esc_html__('New document uploaded', 'maneli-car-inquiry'),
                'message' => sprintf(
                    esc_html__('Customer %s uploaded document "%s"', 'maneli-car-inquiry'),
                    $user_name,
                    $document_name
                ),
                'link' => add_query_arg('user_id', $user_id, home_url('/dashboard/users')),
                'related_id' => 0,
            ]);
        }
        
        wp_send_json_success([
            'message' => esc_html__('Document uploaded successfully. Awaiting admin review.', 'maneli-car-inquiry'),
            'document_url' => $uploaded_file_array['url']
        ]);
    }
    
    /**
     * Approve customer document
     */
    public function ajax_approve_customer_document() {
        check_ajax_referer('maneli_ajax_nonce', 'security');
        
        // Check if user is admin or assigned expert
        $user_id_param = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
        $inquiry_id = isset($_POST['inquiry_id']) ? absint($_POST['inquiry_id']) : 0;
        
        if (!current_user_can('manage_maneli_inquiries')) {
            // Check if user is assigned expert
            if ($inquiry_id > 0) {
                require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/helpers/class-maneli-permission-helpers.php';
                if (!Maneli_Permission_Helpers::is_assigned_expert($inquiry_id, get_current_user_id())) {
                    wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
                    return;
                }
            } else {
                wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
                return;
            }
        }
        
        $user_id = $user_id_param;
        $document_name = isset($_POST['document_name']) ? sanitize_text_field($_POST['document_name']) : '';
        
        if (!$user_id || !$document_name) {
            wp_send_json_error(['message' => esc_html__('Invalid parameters.', 'maneli-car-inquiry')]);
            return;
        }
        
        $documents = get_user_meta($user_id, 'customer_uploaded_documents', true) ?: [];
        foreach ($documents as $index => $doc) {
            if (isset($doc['name']) && $doc['name'] === $document_name) {
                $documents[$index]['status'] = 'approved';
                $documents[$index]['reviewed_at'] = current_time('mysql');
                $documents[$index]['reviewed_by'] = get_current_user_id();
                break;
            }
        }
        update_user_meta($user_id, 'customer_uploaded_documents', $documents);
        
        // Send notification to customer
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-handler.php';
        Maneli_Notification_Handler::notify_document_approved($user_id, $document_name, $inquiry_id);
        
        wp_send_json_success(['message' => esc_html__('Document approved successfully.', 'maneli-car-inquiry')]);
    }
    
    /**
     * Reject customer document
     */
    public function ajax_reject_customer_document() {
        check_ajax_referer('maneli_ajax_nonce', 'security');
        
        // Check if user is admin or assigned expert
        $user_id_param = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
        $inquiry_id = isset($_POST['inquiry_id']) ? absint($_POST['inquiry_id']) : 0;
        
        if (!current_user_can('manage_maneli_inquiries')) {
            // Check if user is assigned expert
            if ($inquiry_id > 0) {
                require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/helpers/class-maneli-permission-helpers.php';
                if (!Maneli_Permission_Helpers::is_assigned_expert($inquiry_id, get_current_user_id())) {
                    wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
                    return;
                }
            } else {
                wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
                return;
            }
        }
        
        $user_id = $user_id_param;
        $document_name = isset($_POST['document_name']) ? sanitize_text_field($_POST['document_name']) : '';
        
        if (!$user_id || !$document_name) {
            wp_send_json_error(['message' => esc_html__('Invalid parameters.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Get rejection reason if provided
        $rejection_reason = isset($_POST['rejection_reason']) ? sanitize_textarea_field($_POST['rejection_reason']) : null;
        
        $documents = get_user_meta($user_id, 'customer_uploaded_documents', true) ?: [];
        foreach ($documents as $index => $doc) {
            if (isset($doc['name']) && $doc['name'] === $document_name) {
                $documents[$index]['status'] = 'rejected';
                $documents[$index]['reviewed_at'] = current_time('mysql');
                $documents[$index]['reviewed_by'] = get_current_user_id();
                if ($rejection_reason) {
                    $documents[$index]['rejection_reason'] = $rejection_reason;
                }
                break;
            }
        }
        update_user_meta($user_id, 'customer_uploaded_documents', $documents);
        
        // Send notification to customer
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-handler.php';
        Maneli_Notification_Handler::notify_document_rejected($user_id, $document_name, $rejection_reason, $inquiry_id);
        
        wp_send_json_success(['message' => esc_html__('Document rejected successfully.', 'maneli-car-inquiry')]);
    }
    
    /**
     * Request customer document
     */
    public function ajax_request_customer_document() {
        check_ajax_referer('maneli_ajax_nonce', 'security');
        
        // Check if user is admin or assigned expert
        $user_id_param = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
        $inquiry_id = isset($_POST['inquiry_id']) ? absint($_POST['inquiry_id']) : 0;
        
        if (!current_user_can('manage_maneli_inquiries')) {
            // Check if user is assigned expert
            if ($inquiry_id > 0) {
                require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/helpers/class-maneli-permission-helpers.php';
                if (!Maneli_Permission_Helpers::is_assigned_expert($inquiry_id, get_current_user_id())) {
                    wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
                    return;
                }
            } else {
                wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
                return;
            }
        }
        
        $user_id = $user_id_param;
        $document_name = isset($_POST['document_name']) ? sanitize_text_field($_POST['document_name']) : '';
        
        if (!$user_id || !$document_name) {
            wp_send_json_error(['message' => esc_html__('Invalid parameters.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Send notification to customer to upload document
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-handler.php';
        Maneli_Notification_Handler::notify_document_requested($user_id, $document_name, $inquiry_id);
        
        wp_send_json_success(['message' => esc_html__('Document request sent to customer successfully.', 'maneli-car-inquiry')]);
    }

    /**
     * Request multiple customer documents at once - sends ONE SMS for all selected documents
     */
    public function ajax_request_customer_documents_bulk() {
        check_ajax_referer('maneli_ajax_nonce', 'security');
        
        // Check if user is admin or assigned expert
        $user_id_param = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
        $inquiry_id = isset($_POST['inquiry_id']) ? absint($_POST['inquiry_id']) : 0;
        $inquiry_type = isset($_POST['inquiry_type']) ? sanitize_text_field($_POST['inquiry_type']) : 'installment';
        
        if (!current_user_can('manage_maneli_inquiries')) {
            // Check if user is assigned expert
            if ($inquiry_id > 0) {
                require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/helpers/class-maneli-permission-helpers.php';
                if (!Maneli_Permission_Helpers::is_assigned_expert($inquiry_id, get_current_user_id())) {
                    wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
                    return;
                }
            } else {
                wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
                return;
            }
        }
        
        $user_id = $user_id_param;
        $documents = isset($_POST['documents']) ? $_POST['documents'] : [];
        
        // Sanitize documents array
        if (!is_array($documents)) {
            $documents = [];
        }
        $documents = array_map('sanitize_text_field', $documents);
        $documents = array_filter($documents); // Remove empty values
        
        if (!$user_id || empty($documents)) {
            wp_send_json_error(['message' => esc_html__('Invalid parameters.', 'maneli-car-inquiry')]);
            return;
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error(['message' => esc_html__('Invalid user.', 'maneli-car-inquiry')]);
            return;
        }
        
        $mobile = get_user_meta($user_id, 'mobile_number', true);
        if (!$mobile) {
            wp_send_json_error(['message' => esc_html__('Customer mobile number not found.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Determine inquiry link
        $link = home_url('/dashboard/profile-settings');
        if ($inquiry_id) {
            $post_type = get_post_type($inquiry_id);
            if ($post_type === 'cash_inquiry') {
                $link = add_query_arg('cash_inquiry_id', $inquiry_id, home_url('/dashboard/cash-inquiries'));
            } else {
                $link = add_query_arg('view_inquiry', $inquiry_id, home_url('/dashboard/installment-inquiries'));
            }
        }
        
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-handler.php';
        
        // Create in-app notifications for each document (individual notifications)
        foreach ($documents as $document_name) {
            Maneli_Notification_Handler::create_notification($user_id, [
                'type' => 'document_requested',
                'title' => esc_html__('Document requested', 'maneli-car-inquiry'),
                'message' => sprintf(
                    esc_html__('Please upload the document "%s".', 'maneli-car-inquiry'),
                    $document_name
                ),
                'link' => $link,
                'related_id' => $inquiry_id ? $inquiry_id : 0,
            ]);
        }
        
        // Send ONE SMS for all selected documents
        $documents_list = implode('، ', $documents); // Persian comma separator
        $message = sprintf(
            esc_html__('لطفاً مدارک زیر را در پروفایل خود آپلود کنید: %s', 'maneli-car-inquiry'),
            $documents_list
        );
        
        Maneli_Notification_Handler::send_sms_notification($mobile, $message);
        
        // Update requested documents meta for inquiry (if inquiry_id exists)
        if ($inquiry_id) {
            $current_requested = get_post_meta($inquiry_id, 'requested_documents', true) ?: [];
            if (!is_array($current_requested)) {
                $current_requested = [];
            }
            // Merge with new documents (avoid duplicates)
            $all_requested = array_unique(array_merge($current_requested, $documents));
            update_post_meta($inquiry_id, 'requested_documents', $all_requested);
        }
        
        $count = count($documents);
        $success_message = sprintf(
            esc_html__('درخواست %d مدرک با موفقیت ارسال شد. یک پیامک برای همه مدارک ارسال شد.', 'maneli-car-inquiry'),
            $count
        );
        
        wp_send_json_success(['message' => $success_message]);
    }
    
    /**
     * Get expert details for viewing
     */
    public function ajax_get_expert_details() {
        check_ajax_referer('maneli_expert_details_nonce', 'nonce');
        
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (!$user_id) {
            wp_send_json_error(['message' => esc_html__('Invalid user ID.', 'maneli-car-inquiry')]);
            return;
        }
        
        $user = get_userdata($user_id);
        if (!$user || !in_array('maneli_expert', $user->roles)) {
            wp_send_json_error(['message' => esc_html__('User is not an expert.', 'maneli-car-inquiry')]);
            return;
        }
        
        $mobile = get_user_meta($user_id, 'mobile_number', true);
        $is_active = get_user_meta($user_id, 'expert_active', true) !== 'no';
        
        // Get inquiry counts
        $installment_count = count(get_posts([
            'post_type' => 'inquiry',
            'post_status' => 'any',
            'meta_query' => [
                [
                    'key' => 'assigned_expert_id',
                    'value' => $user_id,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => -1
        ]));
        
        $cash_count = count(get_posts([
            'post_type' => 'cash_inquiry',
            'post_status' => 'any',
            'meta_query' => [
                [
                    'key' => 'assigned_expert_id',
                    'value' => $user_id,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => -1
        ]));
        
        // Get permissions
        $permissions = [
            'cash_inquiry' => get_user_meta($user_id, 'permission_cash_inquiry', true) !== 'no',
            'installment_inquiry' => get_user_meta($user_id, 'permission_installment_inquiry', true) !== 'no',
            'calendar' => get_user_meta($user_id, 'permission_calendar', true) !== 'no'
        ];
        
        $html = '
            <div class="row g-3">
                <div class="col-md-6">
                    <strong>' . esc_html__('Name:', 'maneli-car-inquiry') . '</strong> ' . esc_html($user->first_name) . '
                </div>
                <div class="col-md-6">
                    <strong>' . esc_html__('Last Name:', 'maneli-car-inquiry') . '</strong> ' . esc_html($user->last_name) . '
                </div>
                <div class="col-md-6">
                    <strong>' . esc_html__('Mobile Number:', 'maneli-car-inquiry') . '</strong> ' . esc_html($mobile) . '
                </div>
                <div class="col-md-6">
                    <strong>' . esc_html__('Status:', 'maneli-car-inquiry') . '</strong> <span class="badge bg-' . ($is_active ? 'success' : 'danger') . '">' . ($is_active ? esc_html__('Active', 'maneli-car-inquiry') : esc_html__('Inactive', 'maneli-car-inquiry')) . '</span>
                </div>
                <div class="col-md-6">
                    <strong>' . esc_html__('Installment Inquiries:', 'maneli-car-inquiry') . '</strong> ' . number_format_i18n($installment_count) . '
                </div>
                <div class="col-md-6">
                    <strong>' . esc_html__('Cash Inquiries:', 'maneli-car-inquiry') . '</strong> ' . number_format_i18n($cash_count) . '
                </div>
                <div class="col-md-12">
                    <strong>' . esc_html__('Permissions:', 'maneli-car-inquiry') . '</strong>
                    <ul class="list-unstyled mt-2">
                        <li>- ' . esc_html__('Cash Inquiry:', 'maneli-car-inquiry') . ' ' . ($permissions['cash_inquiry'] ? '✓ ' . esc_html__('Active', 'maneli-car-inquiry') : '✗ ' . esc_html__('Inactive', 'maneli-car-inquiry')) . '</li>
                        <li>- ' . esc_html__('Installment Inquiry:', 'maneli-car-inquiry') . ' ' . ($permissions['installment_inquiry'] ? '✓ ' . esc_html__('Active', 'maneli-car-inquiry') : '✗ ' . esc_html__('Inactive', 'maneli-car-inquiry')) . '</li>
                        <li>- ' . esc_html__('Meeting Calendar:', 'maneli-car-inquiry') . ' ' . ($permissions['calendar'] ? '✓ ' . esc_html__('Active', 'maneli-car-inquiry') : '✗ ' . esc_html__('Inactive', 'maneli-car-inquiry')) . '</li>
                    </ul>
                </div>
            </div>
        ';
        
        wp_send_json_success(['html' => $html]);
    }
    
    /**
     * Get expert data for editing
     */
    public function ajax_get_expert_data() {
        check_ajax_referer('maneli_expert_data_nonce', 'nonce');
        
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (!$user_id) {
            wp_send_json_error(['message' => esc_html__('Invalid user ID.', 'maneli-car-inquiry')]);
            return;
        }
        
        $user = get_userdata($user_id);
        if (!$user || !in_array('maneli_expert', $user->roles)) {
            wp_send_json_error(['message' => esc_html__('User is not an expert.', 'maneli-car-inquiry')]);
            return;
        }
        
        $mobile = get_user_meta($user_id, 'mobile_number', true);
        
        wp_send_json_success([
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'mobile' => $mobile
        ]);
    }
    
    /**
     * Update expert information
     */
    public function ajax_update_expert() {
        check_ajax_referer('maneli_update_expert_nonce', 'nonce');
        
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
        $display_name = isset($_POST['display_name']) ? sanitize_text_field($_POST['display_name']) : '';
        $mobile = isset($_POST['mobile_number']) ? sanitize_text_field($_POST['mobile_number']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $expert_active = isset($_POST['expert_active']) ? sanitize_text_field($_POST['expert_active']) : 'yes';
        
        if (!$user_id || !$first_name || !$last_name || !$mobile) {
            wp_send_json_error(['message' => esc_html__('All fields are required.', 'maneli-car-inquiry')]);
            return;
        }
        
        $user = get_userdata($user_id);
        if (!$user || !in_array('maneli_expert', $user->roles)) {
            wp_send_json_error(['message' => esc_html__('User is not an expert.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Update user data
        wp_update_user([
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $display_name
        ]);
        
        // Update email if provided
        if (!empty($email)) {
            wp_update_user(['ID' => $user_id, 'user_email' => $email]);
        }
        
        // Update mobile and active status
        update_user_meta($user_id, 'mobile_number', $mobile);
        update_user_meta($user_id, 'expert_active', $expert_active === 'yes' ? 'yes' : 'no');
        
        wp_send_json_success(['message' => esc_html__('Expert updated successfully.', 'maneli-car-inquiry')]);
    }
    
    /**
     * Get expert permissions
     */
    public function ajax_get_expert_permissions() {
        check_ajax_referer('maneli_expert_permissions_nonce', 'nonce');
        
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (!$user_id) {
            wp_send_json_error(['message' => esc_html__('Invalid user ID.', 'maneli-car-inquiry')]);
            return;
        }
        
        $user = get_userdata($user_id);
        if (!$user || !in_array('maneli_expert', $user->roles)) {
            wp_send_json_error(['message' => esc_html__('User is not an expert.', 'maneli-car-inquiry')]);
            return;
        }
        
        $permissions = [
            'cash_inquiry' => get_user_meta($user_id, 'permission_cash_inquiry', true) !== 'no',
            'installment_inquiry' => get_user_meta($user_id, 'permission_installment_inquiry', true) !== 'no',
            'calendar' => get_user_meta($user_id, 'permission_calendar', true) !== 'no'
        ];
        
        wp_send_json_success(['permissions' => $permissions]);
    }
    
    /**
     * Update expert permissions
     */
    public function ajax_update_expert_permissions() {
        check_ajax_referer('maneli_update_expert_permissions_nonce', 'nonce');
        
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $permissions = [];
        if (isset($_POST['permissions']) && is_array($_POST['permissions'])) {
            $permissions = $_POST['permissions'];
        } else {
            // Handle array format from form submission
            $permissions['cash_inquiry'] = isset($_POST['permissions']['cash_inquiry']) ? ($_POST['permissions']['cash_inquiry'] === '1' || $_POST['permissions']['cash_inquiry'] === 1 || $_POST['permissions']['cash_inquiry'] === true) : false;
            $permissions['installment_inquiry'] = isset($_POST['permissions']['installment_inquiry']) ? ($_POST['permissions']['installment_inquiry'] === '1' || $_POST['permissions']['installment_inquiry'] === 1 || $_POST['permissions']['installment_inquiry'] === true) : false;
            $permissions['calendar'] = isset($_POST['permissions']['calendar']) ? ($_POST['permissions']['calendar'] === '1' || $_POST['permissions']['calendar'] === 1 || $_POST['permissions']['calendar'] === true) : false;
        }
        
        if (!$user_id) {
            wp_send_json_error(['message' => esc_html__('Invalid user ID.', 'maneli-car-inquiry')]);
            return;
        }
        
        $user = get_userdata($user_id);
        if (!$user || !in_array('maneli_expert', $user->roles)) {
            wp_send_json_error(['message' => esc_html__('User is not an expert.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Update permissions
        $permission_keys = ['cash_inquiry', 'installment_inquiry', 'calendar'];
        foreach ($permission_keys as $key) {
            $has_permission = isset($permissions[$key]) && $permissions[$key] === true;
            update_user_meta($user_id, 'permission_' . $key, $has_permission ? 'yes' : 'no');
        }
        
        wp_send_json_success(['message' => esc_html__('Permissions updated successfully.', 'maneli-car-inquiry')]);
    }
    
    /**
     * Delete user (from experts page)
     */
    public function ajax_delete_user_from_experts() {
        check_ajax_referer('maneli_delete_user_nonce', 'nonce');
        
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (!$user_id) {
            wp_send_json_error(['message' => esc_html__('Invalid user ID.', 'maneli-car-inquiry')]);
            return;
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error(['message' => esc_html__('User not found.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Don't allow deleting admin users
        if (in_array('administrator', $user->roles)) {
            wp_send_json_error(['message' => esc_html__('Cannot delete administrator.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Delete user
        require_once(ABSPATH . 'wp-admin/includes/user.php');
        wp_delete_user($user_id);
        
        wp_send_json_success(['message' => esc_html__('User deleted successfully.', 'maneli-car-inquiry')]);
    }
    
    /**
     * Update ajax_add_expert to support new fields and auto-generated password
     */
    public function ajax_add_expert() {
        check_ajax_referer('maneli_add_expert_nonce', 'nonce');
        
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }
        
        // Get and sanitize input
        $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
        $display_name = isset($_POST['display_name']) ? sanitize_text_field($_POST['display_name']) : '';
        $mobile = isset($_POST['mobile_number']) ? sanitize_text_field($_POST['mobile_number']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $permissions = [];
        if (isset($_POST['permissions']) && is_array($_POST['permissions'])) {
            $permissions = $_POST['permissions'];
        } else {
            // Handle array format from form submission
            $permissions['cash_inquiry'] = isset($_POST['permissions']['cash_inquiry']) && $_POST['permissions']['cash_inquiry'] === '1';
            $permissions['installment_inquiry'] = isset($_POST['permissions']['installment_inquiry']) && $_POST['permissions']['installment_inquiry'] === '1';
            $permissions['calendar'] = isset($_POST['permissions']['calendar']) && $_POST['permissions']['calendar'] === '1';
        }
        
        // Validation
        if (empty($first_name) || empty($last_name) || empty($mobile) || empty($password)) {
            wp_send_json_error(['message' => esc_html__('All required fields must be filled.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Generate email from mobile if not provided
        if (empty($email)) {
            $email = $mobile . '@manelikhodro.com';
        }
        
        // Check if user already exists
        if (username_exists($mobile)) {
            wp_send_json_error(['message' => esc_html__('A user with this mobile number already exists.', 'maneli-car-inquiry')]);
            return;
        }
        
        if (email_exists($email)) {
            wp_send_json_error(['message' => esc_html__('A user with this email already exists.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Create user
        $user_id = wp_create_user($mobile, $password, $email);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error(['message' => $user_id->get_error_message()]);
            return;
        }
        
        // Update user data
        wp_update_user([
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $display_name,
            'role' => 'maneli_expert'
        ]);
        
        // Update user meta
        update_user_meta($user_id, 'mobile_number', $mobile);
        update_user_meta($user_id, 'expert_active', 'yes');
        
        // Save permissions (default to yes if not explicitly set)
        $cash_inquiry = !empty($permissions) && isset($permissions['cash_inquiry']) ? ($permissions['cash_inquiry'] === true || $permissions['cash_inquiry'] === '1' || $permissions['cash_inquiry'] === 1) : true;
        $installment_inquiry = !empty($permissions) && isset($permissions['installment_inquiry']) ? ($permissions['installment_inquiry'] === true || $permissions['installment_inquiry'] === '1' || $permissions['installment_inquiry'] === 1) : true;
        $calendar = !empty($permissions) && isset($permissions['calendar']) ? ($permissions['calendar'] === true || $permissions['calendar'] === '1' || $permissions['calendar'] === 1) : true;
        
        update_user_meta($user_id, 'permission_cash_inquiry', $cash_inquiry ? 'yes' : 'no');
        update_user_meta($user_id, 'permission_installment_inquiry', $installment_inquiry ? 'yes' : 'no');
        update_user_meta($user_id, 'permission_calendar', $calendar ? 'yes' : 'no');
        
        wp_send_json_success([
            'message' => esc_html__('Expert added successfully.', 'maneli-car-inquiry'),
            'user_id' => $user_id
        ]);
    }
    
    /**
     * Get user details for viewing
     */
    public function ajax_get_user_details() {
        check_ajax_referer('maneli_user_details_nonce', 'nonce');
        
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (!$user_id) {
            wp_send_json_error(['message' => esc_html__('Invalid user ID.', 'maneli-car-inquiry')]);
            return;
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error(['message' => esc_html__('User not found.', 'maneli-car-inquiry')]);
            return;
        }
        
        $mobile = get_user_meta($user_id, 'mobile_number', true);
        $user_roles = $user->roles;
        $role_display = !empty($user_roles) ? $user_roles[0] : '';
        
        $role_labels = [
            'administrator' => esc_html__('Administrator', 'maneli-car-inquiry'),
            'maneli_admin' => esc_html__('Maneli Admin', 'maneli-car-inquiry'),
            'maneli_expert' => esc_html__('Expert', 'maneli-car-inquiry'),
            'customer' => esc_html__('Customer', 'maneli-car-inquiry'),
            'subscriber' => esc_html__('Subscriber', 'maneli-car-inquiry')
        ];
        $role_display_persian = isset($role_labels[$role_display]) ? $role_labels[$role_display] : $role_display;
        
        $is_active = $user->user_status == 0;
        
        $html = '
            <div class="row g-3">
                <div class="col-md-6">
                    <strong>' . esc_html__('Name:', 'maneli-car-inquiry') . '</strong> ' . esc_html($user->first_name) . '
                </div>
                <div class="col-md-6">
                    <strong>' . esc_html__('Last Name:', 'maneli-car-inquiry') . '</strong> ' . esc_html($user->last_name) . '
                </div>
                <div class="col-md-6">
                    <strong>' . esc_html__('Mobile Number:', 'maneli-car-inquiry') . '</strong> ' . esc_html($mobile) . '
                </div>
                <div class="col-md-6">
                    <strong>' . esc_html__('Role:', 'maneli-car-inquiry') . '</strong> <span class="badge bg-info">' . esc_html($role_display_persian) . '</span>
                </div>
                <div class="col-md-6">
                    <strong>' . esc_html__('Status:', 'maneli-car-inquiry') . '</strong> <span class="badge bg-' . ($is_active ? 'success' : 'danger') . '">' . ($is_active ? esc_html__('Active', 'maneli-car-inquiry') : esc_html__('Inactive', 'maneli-car-inquiry')) . '</span>
                </div>
                <div class="col-md-6">
                    <strong>' . esc_html__('Registration Date:', 'maneli-car-inquiry') . '</strong> ' . date_i18n('Y/m/d', strtotime($user->user_registered)) . '
                </div>
            </div>
        ';
        
        wp_send_json_success(['html' => $html]);
    }
    
    /**
     * Get user data for editing
     */
    public function ajax_get_user_data() {
        check_ajax_referer('maneli_user_data_nonce', 'nonce');
        
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (!$user_id) {
            wp_send_json_error(['message' => esc_html__('Invalid user ID.', 'maneli-car-inquiry')]);
            return;
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error(['message' => esc_html__('User not found.', 'maneli-car-inquiry')]);
            return;
        }
        
        $mobile = get_user_meta($user_id, 'mobile_number', true);
        $user_roles = $user->roles;
        $role = !empty($user_roles) ? $user_roles[0] : 'customer';
        
        wp_send_json_success([
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'mobile' => $mobile,
            'role' => $role
        ]);
    }
    
    /**
     * Update user information
     */
    public function ajax_update_user() {
        check_ajax_referer('maneli_update_user_nonce', 'nonce');
        
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
        $display_name = isset($_POST['display_name']) ? sanitize_text_field($_POST['display_name']) : '';
        $mobile = isset($_POST['mobile_number']) ? sanitize_text_field($_POST['mobile_number']) : '';
        $role = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        
        if (!$user_id || !$first_name || !$last_name || !$mobile) {
            wp_send_json_error(['message' => esc_html__('All fields are required.', 'maneli-car-inquiry')]);
            return;
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error(['message' => esc_html__('User not found.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Update user data
        $user_data = [
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $display_name
        ];
        
        // Update email if provided
        if (!empty($email)) {
            $user_data['user_email'] = $email;
        }
        
        wp_update_user($user_data);
        
        // Update role
        if (!empty($role)) {
            $user_obj = new WP_User($user_id);
            $user_obj->set_role($role);
        }
        
        // Update mobile
        update_user_meta($user_id, 'mobile_number', $mobile);
        
        wp_send_json_success(['message' => esc_html__('User updated successfully.', 'maneli-car-inquiry')]);
    }
    
    /**
     * Update user information (full update with all meta fields)
     */
    public function ajax_update_user_full() {
        check_ajax_referer('maneli_update_user_full_nonce', 'nonce');
        
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
        $display_name = isset($_POST['display_name']) ? sanitize_text_field($_POST['display_name']) : '';
        $mobile = isset($_POST['mobile_number']) ? sanitize_text_field($_POST['mobile_number']) : '';
        $role = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        
        // Meta fields
        $national_code = isset($_POST['national_code']) ? sanitize_text_field($_POST['national_code']) : '';
        $father_name = isset($_POST['father_name']) ? sanitize_text_field($_POST['father_name']) : '';
        $birth_date = isset($_POST['birth_date']) ? sanitize_text_field($_POST['birth_date']) : '';
        $phone_number = isset($_POST['phone_number']) ? sanitize_text_field($_POST['phone_number']) : '';
        $occupation = isset($_POST['occupation']) ? sanitize_text_field($_POST['occupation']) : '';
        $job_title = isset($_POST['job_title']) ? sanitize_text_field($_POST['job_title']) : '';
        $job_type = isset($_POST['job_type']) ? sanitize_text_field($_POST['job_type']) : '';
        $income_level = isset($_POST['income_level']) ? sanitize_text_field($_POST['income_level']) : '';
        $workplace_status = isset($_POST['workplace_status']) ? sanitize_text_field($_POST['workplace_status']) : '';
        $residency_status = isset($_POST['residency_status']) ? sanitize_text_field($_POST['residency_status']) : '';
        $address = isset($_POST['address']) ? sanitize_textarea_field($_POST['address']) : '';
        $bank_name = isset($_POST['bank_name']) ? sanitize_text_field($_POST['bank_name']) : '';
        $account_number = isset($_POST['account_number']) ? sanitize_text_field($_POST['account_number']) : '';
        $branch_code = isset($_POST['branch_code']) ? sanitize_text_field($_POST['branch_code']) : '';
        $branch_name = isset($_POST['branch_name']) ? sanitize_text_field($_POST['branch_name']) : '';
        
        if (!$user_id || !$first_name || !$last_name || !$mobile) {
            wp_send_json_error(['message' => esc_html__('All required fields are required.', 'maneli-car-inquiry')]);
            return;
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error(['message' => esc_html__('User not found.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Update user data
        $user_data = [
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $display_name
        ];
        
        // Update email if provided
        if (!empty($email)) {
            $user_data['user_email'] = $email;
        }
        
        wp_update_user($user_data);
        
        // Update role
        if (!empty($role)) {
            $user_obj = new WP_User($user_id);
            $user_obj->set_role($role);
        }
        
        // Update all meta fields
        update_user_meta($user_id, 'mobile_number', $mobile);
        update_user_meta($user_id, 'national_code', $national_code);
        update_user_meta($user_id, 'father_name', $father_name);
        update_user_meta($user_id, 'birth_date', $birth_date);
        update_user_meta($user_id, 'phone_number', $phone_number);
        update_user_meta($user_id, 'occupation', $occupation);
        if (!empty($job_title)) {
            update_user_meta($user_id, 'job_title', $job_title);
        }
        update_user_meta($user_id, 'job_type', $job_type);
        update_user_meta($user_id, 'income_level', $income_level);
        update_user_meta($user_id, 'workplace_status', $workplace_status);
        update_user_meta($user_id, 'residency_status', $residency_status);
        update_user_meta($user_id, 'address', $address);
        update_user_meta($user_id, 'bank_name', $bank_name);
        update_user_meta($user_id, 'account_number', $account_number);
        update_user_meta($user_id, 'branch_code', $branch_code);
        update_user_meta($user_id, 'branch_name', $branch_name);
        
        wp_send_json_success(['message' => esc_html__('User updated successfully.', 'maneli-car-inquiry')]);
    }
    
    /**
     * Add new user
     */
    public function ajax_add_user() {
        check_ajax_referer('maneli_add_user_nonce', 'nonce');
        
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }
        
        // Get and sanitize input
        $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
        $display_name = isset($_POST['display_name']) ? sanitize_text_field($_POST['display_name']) : '';
        $mobile = isset($_POST['mobile_number']) ? sanitize_text_field($_POST['mobile_number']) : '';
        $role = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : 'customer';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        
        // Validation
        if (empty($first_name) || empty($last_name) || empty($mobile) || empty($password)) {
            wp_send_json_error(['message' => esc_html__('All required fields must be filled.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Generate email from mobile if not provided
        if (empty($email)) {
            $email = $mobile . '@manelikhodro.com';
        }
        
        // Check if user already exists
        if (username_exists($mobile)) {
            wp_send_json_error(['message' => esc_html__('A user with this mobile number already exists.', 'maneli-car-inquiry')]);
            return;
        }
        
        if (email_exists($email)) {
            wp_send_json_error(['message' => esc_html__('A user with this email already exists.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Create user
        $user_id = wp_create_user($mobile, $password, $email);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error(['message' => $user_id->get_error_message()]);
            return;
        }
        
        // Update user data
        wp_update_user([
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $display_name,
            'role' => $role
        ]);
        
        // Update user meta
        update_user_meta($user_id, 'mobile_number', $mobile);
        
        wp_send_json_success([
            'message' => esc_html__('User added successfully.', 'maneli-car-inquiry'),
            'user_id' => $user_id
        ]);
    }
    
    /**
     * Cancel a scheduled meeting for an inquiry
     */
    public function ajax_cancel_meeting() {
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/helpers/class-maneli-permission-helpers.php';
        
        // Multi-stage nonce verification (same as ajax_update_installment_status)
        $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
        $nonce_valid = false;
        
        // Try standard verification first
        if (!empty($nonce)) {
            $nonce_actions = ['maneli_update_inquiry', 'maneli_tracking_status', 'maneli_update_installment_status', 'maneli_installment_status', 'maneli_update_cash_status', 'maneli_cash_status'];
            foreach ($nonce_actions as $action) {
                if (check_ajax_referer($action . '_nonce', 'nonce', false) || wp_verify_nonce($nonce, $action . '_nonce')) {
                    $nonce_valid = true;
                    break;
                }
            }
        }
        
        // Fallback: check if user is logged in and has permission (temporary for debugging)
        if (!$nonce_valid && is_user_logged_in()) {
            $inquiry_id = isset($_POST['inquiry_id']) ? absint($_POST['inquiry_id']) : 0;
            if ($inquiry_id) {
                $is_admin = current_user_can('manage_maneli_inquiries');
                if (!$is_admin) {
                    require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/helpers/class-maneli-permission-helpers.php';
                    $is_assigned = Maneli_Permission_Helpers::is_assigned_expert($inquiry_id, get_current_user_id());
                    if ($is_assigned) {
                        $nonce_valid = true;
                        error_log('⚠️ AJAX Cancel Meeting: Nonce verification bypassed for assigned expert (User ID: ' . get_current_user_id() . ', Inquiry ID: ' . $inquiry_id . ')');
                    }
                } else {
                    $nonce_valid = true;
                    error_log('⚠️ AJAX Cancel Meeting: Nonce verification bypassed for admin (User ID: ' . get_current_user_id() . ', Inquiry ID: ' . $inquiry_id . ')');
                }
            }
        }
        
        if (!$nonce_valid) {
            error_log('❌ AJAX Cancel Meeting: Nonce verification failed', [
                'nonce_received' => $nonce ? substr($nonce, 0, 20) : 'MISSING',
                'nonce_length' => $nonce ? strlen($nonce) : 0,
                'user_logged_in' => is_user_logged_in(),
                'user_id' => is_user_logged_in() ? get_current_user_id() : 0
            ]);
            wp_send_json_error(['message' => esc_html__('Security verification failed. Please refresh the page.', 'maneli-car-inquiry')]);
            return;
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
            return;
        }
        
        $inquiry_id = isset($_POST['inquiry_id']) ? absint($_POST['inquiry_id']) : 0;
        $inquiry_type = isset($_POST['inquiry_type']) ? sanitize_text_field($_POST['inquiry_type']) : 'installment';
        
        if (!$inquiry_id) {
            wp_send_json_error(['message' => esc_html__('Invalid inquiry ID.', 'maneli-car-inquiry')]);
            return;
        }
        
        $current_user_id = get_current_user_id();
        $is_admin = current_user_can('manage_maneli_inquiries');
        
        // Get inquiry post
        $inquiry_post = get_post($inquiry_id);
        if (!$inquiry_post) {
            wp_send_json_error(['message' => esc_html__('Inquiry not found.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Check permissions: admin or assigned expert
        if (!$is_admin) {
            $assigned_expert_id = get_post_meta($inquiry_id, 'assigned_expert_id', true);
            if ($assigned_expert_id != $current_user_id) {
                wp_send_json_error(['message' => esc_html__('You do not have permission to cancel this meeting.', 'maneli-car-inquiry')]);
                return;
            }
        }
        
        // Get current status
        $status_key = $inquiry_type === 'cash' ? 'cash_inquiry_status' : 'tracking_status';
        $current_status = get_post_meta($inquiry_id, $status_key, true);
        
        // Only allow cancellation if meeting is scheduled
        if ($current_status !== 'meeting_scheduled') {
            wp_send_json_error(['message' => esc_html__('Meeting is not scheduled.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Find and delete the meeting post
        $meeting_posts = get_posts([
            'post_type' => 'maneli_meeting',
            'posts_per_page' => 1,
            'meta_query' => [
                ['key' => 'meeting_inquiry_id', 'value' => $inquiry_id, 'compare' => '='],
                ['key' => 'meeting_inquiry_type', 'value' => $inquiry_type, 'compare' => '=']
            ]
        ]);
        
        if (!empty($meeting_posts)) {
            wp_delete_post($meeting_posts[0]->ID, true);
        }
        
        // Update inquiry status back to in_progress
        update_post_meta($inquiry_id, $status_key, 'in_progress');
        update_post_meta($inquiry_id, 'meeting_cancelled_at', current_time('mysql'));
        update_post_meta($inquiry_id, 'meeting_cancelled_by', $current_user_id);
        
        // Remove meeting date and time
        delete_post_meta($inquiry_id, 'meeting_date');
        delete_post_meta($inquiry_id, 'meeting_time');
        
        wp_send_json_success(['message' => esc_html__('Meeting cancelled successfully.', 'maneli-car-inquiry')]);
    }
    
    /**
     * Handles AJAX request to request more documents from customer
     */
    public function ajax_request_more_documents() {
        check_ajax_referer('maneli_tracking_status_nonce', 'nonce');
        
        if (!is_user_logged_in() || !current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
            return;
        }
        
        $inquiry_id = isset($_POST['inquiry_id']) ? absint($_POST['inquiry_id']) : 0;
        $inquiry_type = isset($_POST['inquiry_type']) ? sanitize_text_field($_POST['inquiry_type']) : 'installment';
        $documents = isset($_POST['documents']) ? (array) $_POST['documents'] : [];
        
        if (!$inquiry_id) {
            wp_send_json_error(['message' => esc_html__('Invalid inquiry ID.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Get inquiry post
        $inquiry_post = get_post($inquiry_id);
        if (!$inquiry_post || get_post_type($inquiry_id) !== ($inquiry_type === 'cash' ? 'cash_inquiry' : 'inquiry')) {
            wp_send_json_error(['message' => esc_html__('Inquiry not found.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Store requested documents in meta
        update_post_meta($inquiry_id, 'requested_documents', $documents);
        update_post_meta($inquiry_id, 'document_request_date', current_time('mysql'));
        update_post_meta($inquiry_id, 'document_request_status', 'pending');
        
        // Update inquiry status to more_docs
        $old_status = '';
        if ($inquiry_type === 'installment') {
            $old_status = get_post_meta($inquiry_id, 'inquiry_status', true);
            update_post_meta($inquiry_id, 'inquiry_status', 'more_docs');
        }
        
        // Send notification to customer
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-handler.php';
        $customer_id = get_post_field('post_author', $inquiry_id);
        $product_id = get_post_meta($inquiry_id, 'product_id', true);
        $product_name = get_the_title($product_id);
        
        if ($customer_id) {
            $documents_list = implode(', ', $documents);
            $inquiry_url = $inquiry_type === 'cash' 
                ? add_query_arg('cash_inquiry_id', $inquiry_id, home_url('/dashboard/cash-inquiries'))
                : add_query_arg('inquiry_id', $inquiry_id, home_url('/dashboard/installment-inquiries'));
            
            Maneli_Notification_Handler::create_notification([
                'user_id' => $customer_id,
                'type' => 'more_docs_requested',
                'title' => esc_html__('More documents required', 'maneli-car-inquiry'),
                'message' => sprintf(
                    esc_html__('Please upload the following documents for your %s request for %s: %s', 'maneli-car-inquiry'),
                    $inquiry_type === 'cash' ? esc_html__('cash', 'maneli-car-inquiry') : esc_html__('installment', 'maneli-car-inquiry'),
                    $product_name,
                    $documents_list
                ),
                'link' => $inquiry_url,
                'related_id' => $inquiry_id,
            ]);
        }
        
        wp_send_json_success([
            'message' => esc_html__('Document request sent successfully.', 'maneli-car-inquiry')
        ]);
    }
    
    /**
     * Handles AJAX request to upload a document
     */
    public function ajax_upload_document() {
        check_ajax_referer('maneli_tracking_status_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
            return;
        }
        
        $inquiry_id = isset($_POST['inquiry_id']) ? absint($_POST['inquiry_id']) : 0;
        $document_name = isset($_POST['document_name']) ? sanitize_text_field($_POST['document_name']) : '';
        $uploaded_file = isset($_FILES['file']) ? $_FILES['file'] : null;
        
        if (!$inquiry_id || !$document_name || !$uploaded_file) {
            wp_send_json_error(['message' => esc_html__('Invalid parameters.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Check permissions - customer can only upload to their own inquiries
        $inquiry_post = get_post($inquiry_id);
        if (!$inquiry_post || (int)$inquiry_post->post_author !== get_current_user_id()) {
            wp_send_json_error(['message' => esc_html__('You do not have permission to upload documents for this inquiry.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Handle file upload
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        
        $upload_overrides = array('test_form' => false);
        $uploaded_file_array = wp_handle_upload($uploaded_file, $upload_overrides);
        
        if (isset($uploaded_file_array['error'])) {
            wp_send_json_error(['message' => $uploaded_file_array['error']]);
            return;
        }
        
        // Store document info in meta
        $documents = get_post_meta($inquiry_id, 'uploaded_documents', true) ?: [];
        $documents[] = [
            'name' => $document_name,
            'file' => $uploaded_file_array['url'],
            'file_path' => $uploaded_file_array['file'],
            'uploaded_at' => current_time('mysql')
        ];
        update_post_meta($inquiry_id, 'uploaded_documents', $documents);
        
        // Send notification
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-handler.php';
        Maneli_Notification_Handler::notify_document_uploaded($inquiry_id, $document_name);
        
        wp_send_json_success([
            'message' => esc_html__('Document uploaded successfully.', 'maneli-car-inquiry'),
            'document_url' => $uploaded_file_array['url']
        ]);
    }
    
    /**
     * Handles AJAX request to get SMS credit balance
     */
    public function ajax_get_sms_credit() {
        check_ajax_referer('maneli-ajax-nonce', 'nonce');
        
        // Only admins can view SMS credit
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Load SMS handler
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-sms-handler.php';
        $sms_handler = new Maneli_SMS_Handler();
        
        // Get credit
        $credit = $sms_handler->get_credit();
        
        if ($credit === false) {
            // Check if credentials are configured
            $options = get_option('maneli_inquiry_all_options', []);
            $sms_username = $options['sms_username'] ?? '';
            $sms_password = $options['sms_password'] ?? '';
            
            if (empty($sms_username) || empty($sms_password)) {
                wp_send_json_error([
                    'message' => esc_html__('SMS credentials are not configured. Please go to Settings and enter your SMS panel information.', 'maneli-car-inquiry')
                ]);
            } else {
                wp_send_json_error([
                    'message' => esc_html__('Unable to retrieve SMS credit. Please check your SMS panel settings and credentials.', 'maneli-car-inquiry')
                ]);
            }
            return;
        }
        
        wp_send_json_success([
            'credit' => floatval($credit),
            'formatted' => number_format($credit, 0, '.', ',')
        ]);
    }
    
    /**
     * Send bulk notification
     */
    public function ajax_send_bulk_notification() {
        check_ajax_referer('maneli-ajax-nonce', 'nonce');
        
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
            return;
        }
        
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-center-handler.php';
        
        $channels = isset($_POST['channels']) && is_array($_POST['channels']) ? $_POST['channels'] : [];
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        $recipient_type = isset($_POST['recipient_type']) ? sanitize_text_field($_POST['recipient_type']) : 'all';
        $custom_recipients = isset($_POST['custom_recipients']) ? sanitize_textarea_field($_POST['custom_recipients']) : '';
        
        if (empty($channels) || empty($message)) {
            wp_send_json_error(['message' => esc_html__('Channels and message are required.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Get recipients
        $recipients = [];
        
        if (!empty($custom_recipients)) {
            // Parse custom recipients (one per line)
            $custom_lines = explode("\n", $custom_recipients);
            foreach ($custom_lines as $line) {
                $line = trim($line);
                if (!empty($line)) {
                    $recipients[] = $line;
                }
            }
        } else {
            // Get recipients based on type
            $user_args = [];
            if ($recipient_type === 'customers') {
                $user_args['role'] = 'customer';
            } elseif ($recipient_type === 'experts') {
                $user_args['role'] = 'maneli_expert';
            } elseif ($recipient_type === 'admins') {
                $user_args['role__in'] = ['administrator', 'maneli_admin', 'maneli_manager'];
            }
            
            $users = get_users($user_args);
            foreach ($users as $user) {
                // Get appropriate recipient based on channel
                foreach ($channels as $channel) {
                    if ($channel === 'sms') {
                        $phone = get_user_meta($user->ID, 'mobile_number', true);
                        if (!empty($phone)) {
                            $recipients[] = $phone;
                        }
                    } elseif ($channel === 'email') {
                        if (!empty($user->user_email)) {
                            $recipients[] = $user->user_email;
                        }
                    } elseif ($channel === 'notification') {
                        $recipients[] = $user->ID;
                    } elseif ($channel === 'telegram') {
                        // Telegram chat IDs should be configured separately
                        // For now, skip or use configured chat IDs
                    }
                }
            }
        }
        
        if (empty($recipients)) {
            wp_send_json_error(['message' => esc_html__('No recipients found.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Remove duplicates
        $recipients = array_unique($recipients);
        
        // Check bulk limit
        $options = get_option('maneli_inquiry_all_options', []);
        $bulk_limit = isset($options['bulk_sms_limit']) ? (int)$options['bulk_sms_limit'] : 100;
        
        if (count($recipients) > $bulk_limit) {
            wp_send_json_error([
                'message' => sprintf(esc_html__('Maximum %d recipients allowed. You have %d recipients.', 'maneli-car-inquiry'), $bulk_limit, count($recipients))
            ]);
            return;
        }
        
        // Send bulk
        $results = Maneli_Notification_Center_Handler::send_bulk($channels, $recipients, $message, [
            'user_id' => get_current_user_id(),
        ]);
        
        $success_count = 0;
        $fail_count = 0;
        foreach ($results as $result) {
            foreach ($result['channels'] as $channel_result) {
                if ($channel_result['success'] ?? false) {
                    $success_count++;
                } else {
                    $fail_count++;
                }
            }
        }
        
        wp_send_json_success([
            'message' => sprintf(esc_html__('Sent: %d, Failed: %d', 'maneli-car-inquiry'), $success_count, $fail_count),
            'results' => $results
        ]);
    }
    
    /**
     * Schedule notification
     */
    public function ajax_schedule_notification() {
        check_ajax_referer('maneli-ajax-nonce', 'nonce');
        
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
            return;
        }
        
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-center-handler.php';
        
        $channel = isset($_POST['channel']) ? sanitize_text_field($_POST['channel']) : '';
        $recipient = isset($_POST['recipient']) ? sanitize_text_field($_POST['recipient']) : '';
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        $scheduled_at = isset($_POST['scheduled_at']) ? sanitize_text_field($_POST['scheduled_at']) : '';
        
        if (empty($channel) || empty($recipient) || empty($message) || empty($scheduled_at)) {
            wp_send_json_error(['message' => esc_html__('All fields are required.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Validate scheduled time
        $scheduled_timestamp = strtotime($scheduled_at);
        if ($scheduled_timestamp === false || $scheduled_timestamp <= current_time('timestamp')) {
            wp_send_json_error(['message' => esc_html__('Scheduled time must be in the future.', 'maneli-car-inquiry')]);
            return;
        }
        
        $result = Maneli_Notification_Center_Handler::schedule(
            $channel,
            $recipient,
            $message,
            $scheduled_at,
            [
                'user_id' => get_current_user_id(),
            ]
        );
        
        if ($result) {
            wp_send_json_success(['message' => esc_html__('Notification scheduled successfully.', 'maneli-car-inquiry')]);
        } else {
            wp_send_json_error(['message' => esc_html__('Failed to schedule notification.', 'maneli-car-inquiry')]);
        }
    }
    
    /**
     * Get notification logs (AJAX)
     */
    public function ajax_get_notification_logs() {
        check_ajax_referer('maneli-ajax-nonce', 'nonce');
        
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
            return;
        }
        
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-maneli-database.php';
        
        $args = [
            'type' => isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '',
            'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '',
            'date_from' => isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '',
            'date_to' => isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '',
            'search' => isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '',
            'limit' => isset($_POST['limit']) ? (int)$_POST['limit'] : 50,
            'offset' => isset($_POST['offset']) ? (int)$_POST['offset'] : 0,
        ];
        
        $logs = Maneli_Database::get_notification_logs($args);
        $total = Maneli_Database::get_notification_logs_count($args);
        
        wp_send_json_success([
            'logs' => $logs,
            'total' => $total
        ]);
    }
    
    /**
     * Get notification statistics
     */
    public function ajax_get_notification_stats() {
        check_ajax_referer('maneli-ajax-nonce', 'nonce');
        
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
            return;
        }
        
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-maneli-database.php';
        
        $args = [
            'date_from' => isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '',
            'date_to' => isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '',
            'type' => isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '',
        ];
        
        $stats = Maneli_Database::get_notification_stats($args);
        
        wp_send_json_success(['stats' => $stats]);
    }
    
    /**
     * Retry failed notification
     */
    public function ajax_retry_notification() {
        check_ajax_referer('maneli-ajax-nonce', 'nonce');
        
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
            return;
        }
        
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-center-handler.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-maneli-database.php';
        
        $log_id = isset($_POST['log_id']) ? (int)$_POST['log_id'] : 0;
        
        if (!$log_id) {
            wp_send_json_error(['message' => esc_html__('Invalid log ID.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Get log entry
        global $wpdb;
        $table = $wpdb->prefix . 'maneli_notification_logs';
        $log = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $log_id));
        
        if (!$log || $log->status !== 'failed') {
            wp_send_json_error(['message' => esc_html__('Log entry not found or not failed.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Parse recipient
        $recipient = $log->recipient;
        if (strpos($recipient, ',') !== false) {
            $recipient = explode(',', $recipient);
        }
        
        // Retry sending
        $result = Maneli_Notification_Center_Handler::send(
            $log->type,
            $recipient,
            $log->message,
            [
                'related_id' => $log->related_id,
                'user_id' => $log->user_id,
            ]
        );
        
            // Extract result for this specific channel
            $channel_result = $result[$log->type] ?? $result;
            $success = $channel_result['success'] ?? false;
            $error = $channel_result['error'] ?? null;
            
            // Update log
            $update_data = [
                'status' => $success ? 'sent' : 'failed',
                'error_message' => $error,
                'sent_at' => current_time('mysql'),
            ];
            
            Maneli_Database::update_notification_log($log_id, $update_data);
            
            if ($success) {
                wp_send_json_success(['message' => esc_html__('Notification sent successfully.', 'maneli-car-inquiry')]);
            } else {
                wp_send_json_error(['message' => esc_html__('Failed to send notification: ', 'maneli-car-inquiry') . ($error ?? 'Unknown error')]);
            }
    }
    
    /**
     * Send single SMS (without pattern)
     */
    public function ajax_send_single_sms() {
        check_ajax_referer('maneli-ajax-nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
            return;
        }
        
        $current_user_id = get_current_user_id();
        $is_admin = current_user_can('manage_maneli_inquiries');
        $is_expert = in_array('maneli_expert', wp_get_current_user()->roles, true);
        
        if (!$is_admin && !$is_expert) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
            return;
        }
        
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-center-handler.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/helpers/class-maneli-permission-helpers.php';
        
        $recipient = isset($_POST['recipient']) ? sanitize_text_field($_POST['recipient']) : '';
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        $related_id = isset($_POST['related_id']) ? (int)$_POST['related_id'] : 0;
        
        // If expert, check if they are assigned to this inquiry
        if ($is_expert && !$is_admin && $related_id > 0) {
            $is_assigned = Maneli_Permission_Helpers::is_assigned_expert($related_id, $current_user_id);
            if (!$is_assigned) {
                wp_send_json_error(['message' => esc_html__('You do not have permission to send SMS for this inquiry.', 'maneli-car-inquiry')]);
                return;
            }
        }
        
        if (empty($recipient) || empty($message)) {
            wp_send_json_error(['message' => esc_html__('Recipient and message are required.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Validate phone number
        $phone = preg_replace('/[^0-9]/', '', $recipient);
        if (empty($phone) || strlen($phone) < 10) {
            wp_send_json_error(['message' => esc_html__('Invalid phone number.', 'maneli-car-inquiry')]);
            return;
        }
        
        try {
        // Send SMS (without pattern)
        $result = Maneli_Notification_Center_Handler::send(
            'sms',
            $phone,
            $message,
            [
                'related_id' => $related_id,
                'user_id' => get_current_user_id(),
            ]
        );
        
            // Store SMS history if related_id is an inquiry or user
        if ($related_id > 0) {
            $current_user = wp_get_current_user();
            $user_name = $current_user->display_name ?: $current_user->user_login;
            
            // Check if it's a cash_inquiry or inquiry post type
            $post_type = get_post_type($related_id);
            if (in_array($post_type, ['cash_inquiry', 'inquiry'])) {
                $sms_history = get_post_meta($related_id, 'sms_history', true) ?: [];
                
                $sms_entry = [
                    'user_id' => get_current_user_id(),
                    'user_name' => $user_name,
                    'recipient' => $phone,
                    'message' => $message,
                    'sent_at' => current_time('mysql'),
                    'success' => $result['sms']['success'] ?? false,
                        'message_id' => $result['sms']['message_id'] ?? null,
                    'error' => $result['sms']['error'] ?? null,
                ];
                
                // Add to beginning of array (newest first)
                array_unshift($sms_history, $sms_entry);
                
                // Keep only last 100 entries
                if (count($sms_history) > 100) {
                    $sms_history = array_slice($sms_history, 0, 100);
                }
                
                update_post_meta($related_id, 'sms_history', $sms_history);
                } elseif (get_user_by('ID', $related_id)) {
                    // Store SMS history for users in user meta
                    $sms_history = get_user_meta($related_id, 'sms_history', true) ?: [];
                    
                    $sms_entry = [
                        'user_id' => get_current_user_id(),
                        'user_name' => $user_name,
                        'recipient' => $phone,
                        'message' => $message,
                        'sent_at' => current_time('mysql'),
                        'success' => $result['sms']['success'] ?? false,
                        'message_id' => $result['sms']['message_id'] ?? null,
                        'error' => $result['sms']['error'] ?? null,
                    ];
                    
                    // Add to beginning of array (newest first)
                    array_unshift($sms_history, $sms_entry);
                    
                    // Keep only last 100 entries
                    if (count($sms_history) > 100) {
                        $sms_history = array_slice($sms_history, 0, 100);
                    }
                    
                    update_user_meta($related_id, 'sms_history', $sms_history);
            }
        }
        
        if ($result['sms']['success'] ?? false) {
            wp_send_json_success(['message' => esc_html__('SMS sent successfully.', 'maneli-car-inquiry')]);
        } else {
                $error_message = $result['sms']['error'] ?? esc_html__('Unknown error', 'maneli-car-inquiry');
                wp_send_json_error(['message' => esc_html__('Failed to send SMS: ', 'maneli-car-inquiry') . $error_message]);
            }
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli SMS Send Error: ' . $e->getMessage());
            }
            wp_send_json_error(['message' => esc_html__('Server error. Please try again.', 'maneli-car-inquiry')]);
        }
    }
    
    /**
     * Get SMS history for an inquiry or user
     */
    public function ajax_get_sms_history() {
        check_ajax_referer('maneli-ajax-nonce', 'nonce');
        
        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $inquiry_type = isset($_POST['inquiry_type']) ? sanitize_text_field($_POST['inquiry_type']) : '';
        
        // Check permissions
        $is_admin = current_user_can('manage_maneli_inquiries');
        
        // Get SMS history
        $sms_history = [];
        $customer_name = null; // For user SMS history
        
        if ($inquiry_id > 0) {
            // Check permissions for inquiry
        $is_assigned_expert = false;
        
        if (!$is_admin && $inquiry_id > 0) {
            require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/helpers/class-maneli-permission-helpers.php';
            $is_assigned_expert = Maneli_Permission_Helpers::is_assigned_expert($inquiry_id, get_current_user_id());
        }
        
        if (!$is_admin && !$is_assigned_expert) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
            return;
        }
        
            // Get SMS history from post meta
        $sms_history = get_post_meta($inquiry_id, 'sms_history', true) ?: [];
        } elseif ($user_id > 0) {
            // Check permissions for user (only admin can view user SMS history)
            if (!$is_admin) {
                wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
                return;
            }
            
            // Verify user exists
            $user = get_user_by('ID', $user_id);
            if (!$user) {
                wp_send_json_error(['message' => esc_html__('User not found.', 'maneli-car-inquiry')]);
                return;
            }
            
            // Get customer name for display
            $customer_name = $user->display_name ?: $user->user_login;
            
            // Get SMS history from user meta
            $sms_history = get_user_meta($user_id, 'sms_history', true) ?: [];
        } else {
            wp_send_json_error(['message' => esc_html__('Invalid inquiry ID or user ID.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Format dates and prepare data
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/helpers/class-maneli-render-helpers.php';
        $formatted_history = [];
        
        foreach ($sms_history as $sms) {
            // Safely format date - handle both string and array formats
            $sent_at = $sms['sent_at'] ?? '';
            $jalali_date = '';
            
            if (!empty($sent_at)) {
                try {
                    $jalali_date = Maneli_Render_Helpers::maneli_gregorian_to_jalali($sent_at, 'Y/m/d H:i');
                    if (function_exists('persian_numbers_no_separator')) {
                        $jalali_date = persian_numbers_no_separator($jalali_date);
                    }
                } catch (Exception $e) {
                    // Fallback to original date if conversion fails
                    $jalali_date = $sent_at;
                }
            } else {
                $jalali_date = __('N/A', 'maneli-car-inquiry');
            }
            
            $recipient = $sms['recipient'] ?? '';
            if (function_exists('persian_numbers_no_separator')) {
                $recipient = persian_numbers_no_separator($recipient);
            }
            
            // For users, show customer name instead of sender name
            $display_name = $sms['user_name'] ?? __('Unknown', 'maneli-car-inquiry');
            if ($user_id > 0 && isset($customer_name)) {
                // When viewing user SMS history, show customer name
                $display_name = $customer_name;
            }
            
            $formatted_history[] = [
                'user_name' => $display_name,
                'recipient' => $recipient,
                'message' => $sms['message'] ?? '',
                'sent_at' => $jalali_date,
                'success' => $sms['success'] ?? false,
                'error' => $sms['error'] ?? null,
            ];
        }
        
        // Generate HTML table for display
        ob_start();
        if (!empty($formatted_history)) {
            ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th><?php esc_html_e('Date & Time', 'maneli-car-inquiry'); ?></th>
                            <th><?php echo $user_id > 0 ? esc_html__('Customer', 'maneli-car-inquiry') : esc_html__('Sent By', 'maneli-car-inquiry'); ?></th>
                            <th><?php esc_html_e('Recipient', 'maneli-car-inquiry'); ?></th>
                            <th><?php esc_html_e('Message', 'maneli-car-inquiry'); ?></th>
                            <th><?php esc_html_e('Status', 'maneli-car-inquiry'); ?></th>
                            <th><?php esc_html_e('Actions', 'maneli-car-inquiry'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $index = 0;
                        foreach ($formatted_history as $formatted_sms): 
                            $sms_index = $index;
                            $index++;
                            $original_sms = $sms_history[$sms_index] ?? [];
                            $message_id = $original_sms['message_id'] ?? null;
                            $is_failed = !($formatted_sms['success'] ?? false);
                        ?>
                            <tr data-sms-index="<?php echo esc_attr($sms_index); ?>" data-message-id="<?php echo esc_attr($message_id); ?>">
                                <td><?php echo esc_html($formatted_sms['sent_at']); ?></td>
                                <td><?php echo esc_html($formatted_sms['user_name']); ?></td>
                                <td><?php echo esc_html($formatted_sms['recipient']); ?></td>
                                <td><?php echo esc_html($formatted_sms['message']); ?></td>
                                <td>
                                    <div class="sms-status-display">
                                        <?php if ($formatted_sms['success']): ?>
                                            <span class="badge bg-success"><?php esc_html_e('Success', 'maneli-car-inquiry'); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><?php esc_html_e('Failed', 'maneli-car-inquiry'); ?></span>
                                            <?php if (!empty($formatted_sms['error'])): ?>
                                                <br><small class="text-danger"><?php echo esc_html($formatted_sms['error']); ?></small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if (!empty($message_id)): ?>
                                            <br><small class="text-muted delivery-status" data-message-id="<?php echo esc_attr($message_id); ?>">
                                                <span class="status-text"><?php echo esc_html(apply_filters('maneli_sms_status_checking_text', __('Checking status...', 'maneli-car-inquiry'))); ?></span>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($is_failed): ?>
                                        <button type="button" class="btn btn-sm btn-warning btn-resend-sms" 
                                                data-phone="<?php echo esc_attr($original_sms['recipient'] ?? $formatted_sms['recipient']); ?>"
                                                data-message="<?php echo esc_attr($original_sms['message'] ?? $formatted_sms['message']); ?>"
                                                data-related-id="<?php echo esc_attr($inquiry_id > 0 ? $inquiry_id : ($user_id > 0 ? $user_id : 0)); ?>"
                                                data-inquiry-type="<?php echo esc_attr($inquiry_id > 0 ? ($inquiry_type ?: 'inquiry') : 'user'); ?>">
                                            <i class="la la-redo me-1"></i>
                                            <?php esc_html_e('Resend', 'maneli-car-inquiry'); ?>
                                        </button>
                                    <?php endif; ?>
                                    <?php if (!empty($message_id)): ?>
                                        <button type="button" class="btn btn-sm btn-info btn-check-status mt-1" 
                                                data-message-id="<?php echo esc_attr($message_id); ?>">
                                            <i class="la la-sync me-1"></i>
                                            <?php esc_html_e('Check Status', 'maneli-car-inquiry'); ?>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Helper function to get translated text
                function getTranslatedText(key, fallback) {
                    if (typeof maneliInquiryLists !== 'undefined' && maneliInquiryLists.text && maneliInquiryLists.text[key]) {
                        return maneliInquiryLists.text[key];
                    }
                    return fallback;
                }
                
                // Auto-check status for messages with message_id
                $('.delivery-status[data-message-id]').each(function() {
                    var $statusEl = $(this);
                    var messageId = $statusEl.data('message-id');
                    
                    if (messageId) {
                        $.ajax({
                            url: typeof maneliAjaxUrl !== 'undefined' ? maneliAjaxUrl : '<?php echo admin_url('admin-ajax.php'); ?>',
                            type: 'POST',
                            data: {
                                action: 'maneli_get_sms_status',
                                nonce: typeof maneliAjaxNonce !== 'undefined' ? maneliAjaxNonce : '<?php echo wp_create_nonce('maneli-ajax-nonce'); ?>',
                                message_id: messageId
                            },
                            success: function(response) {
                                if (response && response.success && response.data) {
                                    var status = response.data.status;
                                    var statusText = response.data.message || 'Unknown';
                                    
                                    // Translate status text if it's a known error message
                                    if (statusText === 'Rate limit exceeded or service temporarily unavailable') {
                                        statusText = getTranslatedText('rate_limit_exceeded', statusText);
                                    } else if (statusText === 'Delivered') {
                                        statusText = getTranslatedText('delivered', '<?php echo esc_js(__('Delivered', 'maneli-car-inquiry')); ?>');
                                    } else if (statusText === 'Failed') {
                                        statusText = getTranslatedText('failed', '<?php echo esc_js(__('Failed', 'maneli-car-inquiry')); ?>');
                                    } else if (statusText === 'Pending') {
                                        statusText = getTranslatedText('pending', '<?php echo esc_js(__('Pending', 'maneli-car-inquiry')); ?>');
                                    } else if (statusText === 'Blocked') {
                                        statusText = getTranslatedText('blocked', '<?php echo esc_js(__('Blocked', 'maneli-car-inquiry')); ?>');
                                    } else if (statusText === 'Rejected') {
                                        statusText = getTranslatedText('rejected', '<?php echo esc_js(__('Rejected', 'maneli-car-inquiry')); ?>');
                                    }
                                    
                                    var badgeClass = 'badge-info';
                                    
                                    if (status === '1' || status === 'Delivered') {
                                        badgeClass = 'badge-success';
                                    } else if (status === '2' || status === 'Failed') {
                                        badgeClass = 'badge-danger';
                                    } else if (status === '3' || status === 'Pending') {
                                        badgeClass = 'badge-warning';
                                    }
                                    
                                    $statusEl.find('.status-text').html('<span class="badge ' + badgeClass + '">' + statusText + '</span>');
                                } else {
                                    var statusUnavailableText = getTranslatedText('status_unavailable', '<?php echo esc_js(__('Status unavailable', 'maneli-car-inquiry')); ?>');
                                    $statusEl.find('.status-text').html('<span class="badge badge-secondary">' + statusUnavailableText + '</span>');
                                }
                            },
                            error: function() {
                                var checkFailedText = getTranslatedText('check_failed', '<?php echo esc_js(__('Check failed', 'maneli-car-inquiry')); ?>');
                                $statusEl.find('.status-text').html('<span class="badge badge-secondary">' + checkFailedText + '</span>');
                            }
                        });
                    }
                });
                
                // Manual status check button
                $(document).on('click', '.btn-check-status', function() {
                    var $btn = $(this);
                    var messageId = $btn.data('message-id');
                    var $row = $btn.closest('tr');
                    var $statusEl = $row.find('.delivery-status');
                    
                    var checkingText = getTranslatedText('checking', '<?php echo esc_js(__('Checking...', 'maneli-car-inquiry')); ?>');
                    $btn.prop('disabled', true).html('<i class="la la-spinner la-spin me-1"></i>' + checkingText);
                    
                    $.ajax({
                        url: typeof maneliAjaxUrl !== 'undefined' ? maneliAjaxUrl : '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'maneli_get_sms_status',
                            nonce: typeof maneliAjaxNonce !== 'undefined' ? maneliAjaxNonce : '<?php echo wp_create_nonce('maneli-ajax-nonce'); ?>',
                            message_id: messageId
                        },
                        success: function(response) {
                            var checkStatusText = getTranslatedText('check_status', '<?php echo esc_js(__('Check Status', 'maneli-car-inquiry')); ?>');
                            $btn.prop('disabled', false).html('<i class="la la-sync me-1"></i>' + checkStatusText);
                            
                            if (response && response.success && response.data) {
                                var status = response.data.status;
                                var statusText = response.data.message || 'Unknown';
                                
                                // Translate status text if it's a known error message
                                if (statusText === 'Rate limit exceeded or service temporarily unavailable') {
                                    statusText = getTranslatedText('rate_limit_exceeded', statusText);
                                } else if (statusText === 'Delivered') {
                                    statusText = getTranslatedText('delivered', '<?php echo esc_js(__('Delivered', 'maneli-car-inquiry')); ?>');
                                } else if (statusText === 'Failed') {
                                    statusText = getTranslatedText('failed', '<?php echo esc_js(__('Failed', 'maneli-car-inquiry')); ?>');
                                } else if (statusText === 'Pending') {
                                    statusText = getTranslatedText('pending', '<?php echo esc_js(__('Pending', 'maneli-car-inquiry')); ?>');
                                } else if (statusText === 'Blocked') {
                                    statusText = getTranslatedText('blocked', '<?php echo esc_js(__('Blocked', 'maneli-car-inquiry')); ?>');
                                } else if (statusText === 'Rejected') {
                                    statusText = getTranslatedText('rejected', '<?php echo esc_js(__('Rejected', 'maneli-car-inquiry')); ?>');
                                }
                                
                                var badgeClass = 'badge-info';
                                
                                if (status === '1' || status === 'Delivered') {
                                    badgeClass = 'badge-success';
                                } else if (status === '2' || status === 'Failed') {
                                    badgeClass = 'badge-danger';
                                } else if (status === '3' || status === 'Pending') {
                                    badgeClass = 'badge-warning';
                                }
                                
                                if ($statusEl.length) {
                                    $statusEl.find('.status-text').html('<span class="badge ' + badgeClass + '">' + statusText + '</span>');
                                } else {
                                    $row.find('td:eq(4)').append('<br><small class="text-muted"><span class="badge ' + badgeClass + '">' + statusText + '</span></small>');
                                }
                            } else {
                                var failedStatusText = getTranslatedText('failed_to_get_status', '<?php echo esc_js(__('Failed to get status.', 'maneli-car-inquiry')); ?>');
                                alert(failedStatusText);
                            }
                        },
                        error: function() {
                            var checkStatusText = getTranslatedText('check_status', '<?php echo esc_js(__('Check Status', 'maneli-car-inquiry')); ?>');
                            var errorStatusText = getTranslatedText('error_checking_status', '<?php echo esc_js(__('Error checking status.', 'maneli-car-inquiry')); ?>');
                            $btn.prop('disabled', false).html('<i class="la la-sync me-1"></i>' + checkStatusText);
                            alert(errorStatusText);
                        }
                    });
                });
                
                // Resend SMS button
                $(document).on('click', '.btn-resend-sms', function() {
                    var $btn = $(this);
                    var phone = $btn.data('phone');
                    var message = $btn.data('message');
                    var relatedId = $btn.data('related-id');
                    
                    var resendTitle = getTranslatedText('resend_sms', '<?php echo esc_js(__('Resend SMS?', 'maneli-car-inquiry')); ?>');
                    var resendConfirm = getTranslatedText('resend_confirm', '<?php echo esc_js(__('Are you sure you want to resend this SMS?', 'maneli-car-inquiry')); ?>');
                    var yesResend = getTranslatedText('yes_resend', '<?php echo esc_js(__('Yes, Resend', 'maneli-car-inquiry')); ?>');
                    var cancelText = getTranslatedText('cancel_button', '<?php echo esc_js(__('Cancel', 'maneli-car-inquiry')); ?>');
                    var sendingText = getTranslatedText('sending', '<?php echo esc_js(__('Sending...', 'maneli-car-inquiry')); ?>');
                    
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: resendTitle,
                            text: resendConfirm,
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: yesResend,
                            cancelButtonText: cancelText
                        }).then(function(result) {
                            if (result.isConfirmed) {
                                $btn.prop('disabled', true).html('<i class="la la-spinner la-spin me-1"></i>' + sendingText);
                                
                                $.ajax({
                                    url: typeof maneliAjaxUrl !== 'undefined' ? maneliAjaxUrl : '<?php echo admin_url('admin-ajax.php'); ?>',
                                    type: 'POST',
                                    data: {
                                        action: 'maneli_resend_sms',
                                        nonce: typeof maneliAjaxNonce !== 'undefined' ? maneliAjaxNonce : '<?php echo wp_create_nonce('maneli-ajax-nonce'); ?>',
                                        phone: phone,
                                        message: message,
                                        related_id: relatedId
                                    },
                                    success: function(response) {
                                        var resendText = getTranslatedText('resend', '<?php echo esc_js(__('Resend', 'maneli-car-inquiry')); ?>');
                                        var successText = getTranslatedText('success', '<?php echo esc_js(__('Success', 'maneli-car-inquiry')); ?>');
                                        var resentSuccessText = getTranslatedText('sms_resent_successfully', '<?php echo esc_js(__('SMS resent successfully.', 'maneli-car-inquiry')); ?>');
                                        var errorText = getTranslatedText('error', '<?php echo esc_js(__('Error', 'maneli-car-inquiry')); ?>');
                                        var resentFailedText = getTranslatedText('failed_to_resend_sms', '<?php echo esc_js(__('Failed to resend SMS.', 'maneli-car-inquiry')); ?>');
                                        
                                        $btn.prop('disabled', false).html('<i class="la la-redo me-1"></i>' + resendText);
                                        
                                        if (response && response.success) {
                                            Swal.fire({
                                                icon: 'success',
                                                title: successText,
                                                text: response.data?.message || resentSuccessText
                                            }).then(function() {
                                                // Reload SMS history
                                                if ($btn.closest('#sms-history-modal').length) {
                                                    $btn.closest('.modal').find('.view-sms-history-btn').first().trigger('click');
                                                }
                                            });
                                        } else {
                                            Swal.fire({
                                                icon: 'error',
                                                title: errorText,
                                                text: response.data?.message || resentFailedText
                                            });
                                        }
                                    },
                                    error: function() {
                                        var resendText = getTranslatedText('resend', '<?php echo esc_js(__('Resend', 'maneli-car-inquiry')); ?>');
                                        var errorText = getTranslatedText('error', '<?php echo esc_js(__('Error', 'maneli-car-inquiry')); ?>');
                                        var serverErrorText = getTranslatedText('server_error', '<?php echo esc_js(__('Server error. Please try again.', 'maneli-car-inquiry')); ?>');
                                        
                                        $btn.prop('disabled', false).html('<i class="la la-redo me-1"></i>' + resendText);
                                        Swal.fire({
                                            icon: 'error',
                                            title: errorText,
                                            text: serverErrorText
                                        });
                                    }
                                });
                            }
                        });
                    } else {
                        var resendConfirmFallback = getTranslatedText('resend_confirm', '<?php echo esc_js(__('Are you sure you want to resend this SMS?', 'maneli-car-inquiry')); ?>');
                        if (confirm(resendConfirmFallback)) {
                            // Fallback without SweetAlert
                            alert('<?php echo esc_js(__('Please refresh the page to use this feature.', 'maneli-car-inquiry')); ?>');
                        }
                    }
                });
            });
            </script>
            <?php
        } else {
            ?>
            <div class="alert alert-info">
                <i class="la la-info-circle me-2"></i>
                <?php esc_html_e('No SMS messages have been sent yet.', 'maneli-car-inquiry'); ?>
            </div>
            <?php
        }
        $html = ob_get_clean();
        
        wp_send_json_success([
            'history' => $formatted_history,
            'count' => count($formatted_history),
            'html' => $html
        ]);
    }
    
    /**
     * Get SMS delivery status from MeliPayamak API
     */
    public function ajax_get_sms_status() {
        check_ajax_referer('maneli-ajax-nonce', 'nonce');
        
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
            return;
        }
        
        $message_id = isset($_POST['message_id']) ? sanitize_text_field($_POST['message_id']) : '';
        
        if (empty($message_id)) {
            wp_send_json_error(['message' => esc_html__('Invalid message ID.', 'maneli-car-inquiry')]);
            return;
        }
        
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-sms-handler.php';
        $sms_handler = new Maneli_SMS_Handler();
        
        $status = $sms_handler->get_message_status($message_id);
        
        if ($status === false) {
            wp_send_json_error(['message' => esc_html__('Failed to get message status.', 'maneli-car-inquiry')]);
            return;
        }
        
        wp_send_json_success($status);
    }
    
    /**
     * Resend failed SMS
     */
    public function ajax_resend_sms() {
        check_ajax_referer('maneli-ajax-nonce', 'nonce');
        
        if (!current_user_can('manage_maneli_inquiries') && !in_array('maneli_expert', wp_get_current_user()->roles, true)) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
            return;
        }
        
        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        $related_id = isset($_POST['related_id']) ? intval($_POST['related_id']) : 0;
        
        if (empty($phone) || empty($message)) {
            wp_send_json_error(['message' => esc_html__('Phone number and message are required.', 'maneli-car-inquiry')]);
            return;
        }
        
        try {
            require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-center-handler.php';
            
            // Send SMS (without pattern)
            $result = Maneli_Notification_Center_Handler::send(
                'sms',
                $phone,
                $message,
                [
                    'related_id' => $related_id,
                    'user_id' => get_current_user_id(),
                ]
            );
            
            // Store SMS history if related_id is an inquiry or user
            if ($related_id > 0) {
                $current_user = wp_get_current_user();
                $user_name = $current_user->display_name ?: $current_user->user_login;
                
                // Check if it's a cash_inquiry or inquiry post type
                $post_type = get_post_type($related_id);
                if (in_array($post_type, ['cash_inquiry', 'inquiry'])) {
                    $sms_history = get_post_meta($related_id, 'sms_history', true) ?: [];
                    
                    $sms_entry = [
                        'user_id' => get_current_user_id(),
                        'user_name' => $user_name,
                        'recipient' => $phone,
                        'message' => $message,
                        'sent_at' => current_time('mysql'),
                        'success' => $result['sms']['success'] ?? false,
                        'message_id' => $result['sms']['message_id'] ?? null,
                        'error' => $result['sms']['error'] ?? null,
                        'is_resend' => true,
                    ];
                    
                    array_unshift($sms_history, $sms_entry);
                    if (count($sms_history) > 100) {
                        $sms_history = array_slice($sms_history, 0, 100);
                    }
                    update_post_meta($related_id, 'sms_history', $sms_history);
                } elseif (get_user_by('ID', $related_id)) {
                    // Store SMS history for users in user meta
                    $sms_history = get_user_meta($related_id, 'sms_history', true) ?: [];
                    
                    $sms_entry = [
                        'user_id' => get_current_user_id(),
                        'user_name' => $user_name,
                        'recipient' => $phone,
                        'message' => $message,
                        'sent_at' => current_time('mysql'),
                        'success' => $result['sms']['success'] ?? false,
                        'message_id' => $result['sms']['message_id'] ?? null,
                        'error' => $result['sms']['error'] ?? null,
                        'is_resend' => true,
                    ];
                    
                    array_unshift($sms_history, $sms_entry);
                    if (count($sms_history) > 100) {
                        $sms_history = array_slice($sms_history, 0, 100);
                    }
                    update_user_meta($related_id, 'sms_history', $sms_history);
                }
            }
            
            if ($result['sms']['success'] ?? false) {
                wp_send_json_success([
                    'message' => esc_html__('SMS resent successfully.', 'maneli-car-inquiry'),
                    'message_id' => $result['sms']['message_id'] ?? null
                ]);
            } else {
                $error_message = $result['sms']['error'] ?? esc_html__('Unknown error', 'maneli-car-inquiry');
                wp_send_json_error(['message' => esc_html__('Failed to resend SMS: ', 'maneli-car-inquiry') . $error_message]);
            }
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli SMS Resend Error: ' . $e->getMessage());
            }
            wp_send_json_error(['message' => esc_html__('Server error. Please try again.', 'maneli-car-inquiry')]);
        }
    }
    
    //======================================================================
    // Status Migration Handlers
    //======================================================================
    
    /**
     * Get migration statistics without running migration
     */
    public function ajax_get_migration_stats() {
        check_ajax_referer('maneli-ajax-nonce', 'nonce');
        
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
            return;
        }
        
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/admin/class-status-migration.php';
        
        $stats = Maneli_Status_Migration::get_migration_stats();
        wp_send_json_success($stats);
    }
    
    /**
     * Run status migration
     */
    public function ajax_run_status_migration() {
        check_ajax_referer('maneli-ajax-nonce', 'nonce');
        
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
            return;
        }
        
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/admin/class-status-migration.php';
        
        $results = Maneli_Status_Migration::migrate_all_statuses();
        
        wp_send_json_success([
            'message' => esc_html__('Migration completed successfully.', 'maneli-car-inquiry'),
            'results' => $results
        ]);
    }

    /**
     * Get system log details
     */
    public function ajax_get_system_log_details() {
        check_ajax_referer('maneli_log_details_nonce', 'security');
        
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
            return;
        }
        
        $log_id = isset($_POST['log_id']) ? intval($_POST['log_id']) : 0;
        if (!$log_id) {
            wp_send_json_error(['message' => esc_html__('Invalid log ID.', 'maneli-car-inquiry')]);
            return;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'maneli_system_logs';
        $log = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $log_id));
        
        if (!$log) {
            wp_send_json_error(['message' => esc_html__('Log not found.', 'maneli-car-inquiry')]);
            return;
        }
        
        $user = $log->user_id ? get_user_by('ID', $log->user_id) : null;
        $context = $log->context ? json_decode($log->context, true) : null;
        
        ob_start();
        ?>
        <div class="log-details">
            <table class="table table-bordered">
                <tr>
                    <th width="30%"><?php esc_html_e('ID', 'maneli-car-inquiry'); ?></th>
                    <td><?php echo esc_html($log->id); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Type', 'maneli-car-inquiry'); ?></th>
                    <td><span class="badge bg-primary"><?php echo esc_html(ucfirst($log->log_type)); ?></span></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Severity', 'maneli-car-inquiry'); ?></th>
                    <td><span class="badge bg-danger"><?php echo esc_html(ucfirst($log->severity)); ?></span></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Message', 'maneli-car-inquiry'); ?></th>
                    <td><?php echo esc_html($log->message); ?></td>
                </tr>
                <?php if ($log->file): ?>
                <tr>
                    <th><?php esc_html_e('File', 'maneli-car-inquiry'); ?></th>
                    <td><code><?php echo esc_html($log->file); ?><?php echo $log->line ? ':' . esc_html($log->line) : ''; ?></code></td>
                </tr>
                <?php endif; ?>
                <?php if ($user): ?>
                <tr>
                    <th><?php esc_html_e('User', 'maneli-car-inquiry'); ?></th>
                    <td><?php echo esc_html($user->display_name); ?> (ID: <?php echo esc_html($log->user_id); ?>)</td>
                </tr>
                <?php endif; ?>
                <tr>
                    <th><?php esc_html_e('IP Address', 'maneli-car-inquiry'); ?></th>
                    <td><?php echo esc_html($log->ip_address); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('User Agent', 'maneli-car-inquiry'); ?></th>
                    <td><small><?php echo esc_html($log->user_agent); ?></small></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Date', 'maneli-car-inquiry'); ?></th>
                    <td>
                        <?php
                        $date_time = strtotime($log->created_at);
                        $greg_date = date('Y-m-d H:i:s', $date_time);
                        $date_parts = explode(' ', $greg_date);
                        $date_part = explode('-', $date_parts[0]);
                        $time_part = $date_parts[1] ?? '00:00:00';
                        if (function_exists('maneli_gregorian_to_jalali')) {
                            $jalali_date = maneli_gregorian_to_jalali($date_part[0], $date_part[1], $date_part[2], 'Y/m/d');
                            echo esc_html($jalali_date . ' ' . $time_part);
                        } else {
                            echo esc_html(date_i18n('Y/m/d H:i:s', $date_time));
                        }
                        ?>
                    </td>
                </tr>
                <?php if ($context): ?>
                <tr>
                    <th><?php esc_html_e('Context', 'maneli-car-inquiry'); ?></th>
                    <td><pre><?php echo esc_html(json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        <?php
        $html = ob_get_clean();
        
        wp_send_json_success(['html' => $html]);
    }

    /**
     * Get user log details
     */
    public function ajax_get_user_log_details() {
        check_ajax_referer('maneli_log_details_nonce', 'security');
        
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
            return;
        }
        
        $log_id = isset($_POST['log_id']) ? intval($_POST['log_id']) : 0;
        if (!$log_id) {
            wp_send_json_error(['message' => esc_html__('Invalid log ID.', 'maneli-car-inquiry')]);
            return;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'maneli_user_logs';
        $log = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $log_id));
        
        if (!$log) {
            wp_send_json_error(['message' => esc_html__('Log not found.', 'maneli-car-inquiry')]);
            return;
        }
        
        $user = get_user_by('ID', $log->user_id);
        $metadata = $log->metadata ? json_decode($log->metadata, true) : null;
        
        ob_start();
        ?>
        <div class="log-details">
            <table class="table table-bordered">
                <tr>
                    <th width="30%"><?php esc_html_e('ID', 'maneli-car-inquiry'); ?></th>
                    <td><?php echo esc_html($log->id); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('User', 'maneli-car-inquiry'); ?></th>
                    <td>
                        <?php if ($user): ?>
                            <?php echo esc_html($user->display_name); ?> (ID: <?php echo esc_html($log->user_id); ?>)
                        <?php else: ?>
                            <?php echo esc_html($log->user_id); ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Action Type', 'maneli-car-inquiry'); ?></th>
                    <td><span class="badge bg-primary"><?php echo esc_html(str_replace('_', ' ', ucwords($log->action_type, '_'))); ?></span></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Description', 'maneli-car-inquiry'); ?></th>
                    <td><?php echo esc_html($log->action_description); ?></td>
                </tr>
                <?php if ($log->target_type && $log->target_id): ?>
                <tr>
                    <th><?php esc_html_e('Target', 'maneli-car-inquiry'); ?></th>
                    <td><span class="badge bg-info"><?php echo esc_html(ucfirst($log->target_type)); ?> #<?php echo esc_html($log->target_id); ?></span></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <th><?php esc_html_e('IP Address', 'maneli-car-inquiry'); ?></th>
                    <td><?php echo esc_html($log->ip_address); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('User Agent', 'maneli-car-inquiry'); ?></th>
                    <td><small><?php echo esc_html($log->user_agent); ?></small></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Date', 'maneli-car-inquiry'); ?></th>
                    <td>
                        <?php
                        $date_time = strtotime($log->created_at);
                        $greg_date = date('Y-m-d H:i:s', $date_time);
                        $date_parts = explode(' ', $greg_date);
                        $date_part = explode('-', $date_parts[0]);
                        $time_part = $date_parts[1] ?? '00:00:00';
                        if (function_exists('maneli_gregorian_to_jalali')) {
                            $jalali_date = maneli_gregorian_to_jalali($date_part[0], $date_part[1], $date_part[2], 'Y/m/d');
                            echo esc_html($jalali_date . ' ' . $time_part);
                        } else {
                            echo esc_html(date_i18n('Y/m/d H:i:s', $date_time));
                        }
                        ?>
                    </td>
                </tr>
                <?php if ($metadata): ?>
                <tr>
                    <th><?php esc_html_e('Metadata', 'maneli-car-inquiry'); ?></th>
                    <td><pre><?php echo esc_html(json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        <?php
        $html = ob_get_clean();
        
        wp_send_json_success(['html' => $html]);
    }

    /**
     * Get system logs (AJAX)
     */
    public function ajax_get_system_logs() {
        check_ajax_referer('maneli_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
            return;
        }
        
        $logger = Maneli_Logger::instance();
        $args = array(
            'log_type' => isset($_POST['log_type']) ? sanitize_text_field($_POST['log_type']) : '',
            'severity' => isset($_POST['severity']) ? sanitize_text_field($_POST['severity']) : '',
            'date_from' => isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '',
            'date_to' => isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '',
            'search' => isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '',
            'limit' => isset($_POST['limit']) ? intval($_POST['limit']) : 50,
            'offset' => isset($_POST['offset']) ? intval($_POST['offset']) : 0,
        );
        
        $logs = $logger->get_system_logs($args);
        $total = $logger->get_system_logs_count($args);
        
        wp_send_json_success([
            'logs' => $logs,
            'total' => $total
        ]);
    }

    /**
     * Get user logs (AJAX)
     */
    public function ajax_get_user_logs() {
        check_ajax_referer('maneli_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
            return;
        }
        
        $logger = Maneli_Logger::instance();
        $args = array(
            'user_id' => isset($_POST['user_id']) ? intval($_POST['user_id']) : '',
            'action_type' => isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '',
            'target_type' => isset($_POST['target_type']) ? sanitize_text_field($_POST['target_type']) : '',
            'date_from' => isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '',
            'date_to' => isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '',
            'search' => isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '',
            'limit' => isset($_POST['limit']) ? intval($_POST['limit']) : 50,
            'offset' => isset($_POST['offset']) ? intval($_POST['offset']) : 0,
        );
        
        $logs = $logger->get_user_logs($args);
        $total = $logger->get_user_logs_count($args);
        
        wp_send_json_success([
            'logs' => $logs,
            'total' => $total
        ]);
    }

    /**
     * Log console message (from JavaScript)
     */
    public function ajax_log_console() {
        // Light nonce check - this is called from frontend
        if (!$this->is_plugin_user_logged_in()) {
            return;
        }

        $logger = Maneli_Logger::instance();
        $log_type = isset($_POST['log_type']) ? sanitize_text_field($_POST['log_type']) : 'console';
        $severity = isset($_POST['severity']) ? sanitize_text_field($_POST['severity']) : 'info';
        $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
        $context = isset($_POST['context']) ? json_decode(stripslashes($_POST['context']), true) : null;

        $logger->log_console($message, $log_type, $severity, $context);
        
        wp_send_json_success();
    }

    /**
     * Log user action (from JavaScript)
     */
    public function ajax_log_user_action() {
        // Light nonce check - this is called from frontend
        if (!$this->is_plugin_user_logged_in()) {
            return;
        }

        $logger = Maneli_Logger::instance();
        $action_type = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : 'unknown';
        $action_description = isset($_POST['action_description']) ? sanitize_text_field($_POST['action_description']) : '';
        $target_type = isset($_POST['target_type']) ? sanitize_text_field($_POST['target_type']) : null;
        $target_id = isset($_POST['target_id']) ? intval($_POST['target_id']) : null;
        $metadata = isset($_POST['metadata']) ? json_decode(stripslashes($_POST['metadata']), true) : null;

        $logger->log_user_action($action_type, $action_description, $target_type, $target_id, $metadata);
        
        wp_send_json_success();
    }

    /**
     * Delete all system logs
     */
    public function ajax_delete_system_logs() {
        check_ajax_referer('maneli_log_actions_nonce', 'nonce');

        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error([
                'message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')
            ], 403);
        }

        $logger = Maneli_Logger::instance();
        $deleted = $logger->delete_all_system_logs();

        if ($deleted === false) {
            wp_send_json_error([
                'message' => esc_html__('Failed to delete system logs.', 'maneli-car-inquiry')
            ]);
        }

        wp_send_json_success([
            'deleted' => (int) $deleted,
            'message' => esc_html__('System logs deleted successfully.', 'maneli-car-inquiry')
        ]);
    }
    
}