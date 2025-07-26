<?php
if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Form_Handler {

    public function __construct() {
        add_action('init', [$this, 'handle_zarinpal_verification']);
        add_action('wp_ajax_maneli_select_car_ajax', [$this, 'handle_car_selection_ajax']);
        add_action('admin_post_nopriv_maneli_start_payment', '__return_false');
        add_action('admin_post_maneli_start_payment', [$this, 'handle_payment_submission']);
        add_action('admin_post_nopriv_maneli_submit_identity', '__return_false');
        add_action('admin_post_maneli_submit_identity', [$this, 'handle_identity_submission']);
        add_action('admin_post_maneli_retry_inquiry', [$this, 'handle_inquiry_retry']);
        add_action('admin_post_maneli_admin_update_status', [$this, 'handle_admin_update_status']);
    }

    public function handle_car_selection_ajax() {
        check_ajax_referer('maneli_ajax_nonce', 'nonce');
        if (!is_user_logged_in() || empty($_POST['product_id'])) { wp_send_json_error(['message' => 'درخواست نامعتبر است.']); }
        $user_id = get_current_user_id();
        update_user_meta($user_id, 'maneli_selected_car_id', intval($_POST['product_id']));
        update_user_meta($user_id, 'maneli_inquiry_step', 'form_pending');
        if (isset($_POST['down_payment'])) { update_user_meta($user_id, 'maneli_inquiry_down_payment', sanitize_text_field($_POST['down_payment'])); }
        if (isset($_POST['term_months'])) { update_user_meta($user_id, 'maneli_inquiry_term_months', sanitize_text_field($_POST['term_months'])); }
        if (isset($_POST['total_price'])) { update_user_meta($user_id, 'maneli_inquiry_total_price', sanitize_text_field($_POST['total_price'])); }
        if (isset($_POST['installment_amount'])) { update_user_meta($user_id, 'maneli_inquiry_installment', sanitize_text_field($_POST['installment_amount'])); }
        wp_send_json_success(['message' => 'خودرو با موفقیت انتخاب شد.']);
    }
    
    public function handle_identity_submission() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'maneli_submit_identity_nonce')) wp_die('خطای امنیتی!');
        if (!is_user_logged_in()) { wp_redirect(wp_get_referer()); exit; }
        $user_id = get_current_user_id();
        $issuer_type = isset($_POST['issuer_type']) ? sanitize_text_field($_POST['issuer_type']) : 'self';
        $buyer_fields = ['first_name', 'last_name', 'national_code', 'father_name', 'birth_date', 'mobile_number'];
        $buyer_data = [];
        foreach ($buyer_fields as $key) { if (empty($_POST[$key])) wp_die("لطفا تمام فیلدهای خریدار را پر کنید."); $buyer_data[$key] = sanitize_text_field($_POST[$key]); }
        $issuer_data = [];
        if ($issuer_type === 'other') {
            $issuer_fields = ['issuer_first_name', 'issuer_last_name', 'issuer_national_code', 'issuer_father_name', 'issuer_birth_date', 'issuer_mobile_number'];
            foreach ($issuer_fields as $key) { if (empty($_POST[$key])) wp_die("لطفا تمام فیلدهای صادرکننده چک را پر کنید."); $issuer_data[$key] = sanitize_text_field($_POST[$key]); }
        }
        $temp_data = ['buyer_data' => $buyer_data, 'issuer_data' => $issuer_data, 'issuer_type' => $issuer_type];
        update_user_meta($user_id, 'maneli_temp_inquiry_data', $temp_data);
        $options = get_option('maneli_inquiry_all_options', []);
        $payment_enabled = $options['payment_enable'] ?? '0';
        $inquiry_fee = (int)($options['inquiry_fee'] ?? 0);
        if ($payment_enabled === '1' && $inquiry_fee > 0) {
            update_user_meta($user_id, 'maneli_inquiry_step', 'payment_pending');
        } else {
            $this->finalize_inquiry($user_id, true);
        }
        wp_redirect(home_url('/dashboard/?endp=inf_menu_1'));
        exit;
    }
    
    public function handle_payment_submission() { /* ... function content from previous version ... */ }
    public function handle_zarinpal_verification() { /* ... function content from previous version ... */ }
    private function finalize_inquiry($user_id, $is_new = false) { /* ... function content from previous version ... */ }
    public function handle_inquiry_retry() { /* ... function content from previous version ... */ }

    public function handle_admin_update_status() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'maneli_admin_update_status_nonce')) wp_die('خطای امنیتی!');
        if (!current_user_can('manage_options') || empty($_POST['inquiry_id']) || empty($_POST['new_status'])) { wp_die('درخواست نامعتبر.'); }
        
        $post_id = intval($_POST['inquiry_id']);
        $new_status_request = sanitize_text_field($_POST['new_status']);
        
        if (!in_array($new_status_request, ['approved', 'rejected', 'more_docs'])) { wp_die('وضعیت نامعتبر است.'); }
        
        $old_status = get_post_meta($post_id, 'inquiry_status', true);
        if ($new_status_request === 'approved' && $old_status === 'user_confirmed') {
             wp_redirect(admin_url('edit.php?post_type=inquiry')); 
             exit;
        }

        $post = get_post($post_id);
        $user_id = $post->post_author;
        $options = get_option('maneli_inquiry_all_options', []);
        $final_status = $new_status_request;

        if ($new_status_request === 'approved') {
            $final_status = 'user_confirmed';

            // --- NEW EXPERT ASSIGNMENT LOGIC ---
            $expert_users = get_users(['role' => 'maneli_expert', 'orderby' => 'ID', 'order' => 'ASC']);
            
            if (!empty($expert_users)) {
                $last_index = get_option('maneli_expert_last_assigned_index', -1);
                $next_index = ($last_index + 1) % count($expert_users);
                $assigned_expert = $expert_users[$next_index];
                
                $expert_name = $assigned_expert->display_name;
                // Get mobile from the custom user profile field we created
                $expert_phone = get_user_meta($assigned_expert->ID, 'mobile_number', true);

                update_post_meta($post_id, 'assigned_expert_id', $assigned_expert->ID);
                update_post_meta($post_id, 'assigned_expert_name', $expert_name);
                update_post_meta($post_id, 'assigned_expert_phone', $expert_phone);
                update_option('maneli_expert_last_assigned_index', $next_index);
                
                $pattern_id = $options['sms_pattern_expert_referral'] ?? 0;
                if ($pattern_id > 0 && !empty($expert_phone)) {
                    $customer_info = get_userdata($user_id);
                    $customer_name = ($customer_info->first_name ?? '') . ' ' . ($customer_info->last_name ?? '');
                    $customer_mobile = get_post_meta($post_id, 'mobile_number', true) ?? '';
                    $car_name = get_the_title(get_post_meta($post_id, 'product_id', true)) ?? '';
                    $params = [(string)$expert_name, (string)$customer_name, (string)$customer_mobile, (string)$car_name];
                    $sms_handler = new Maneli_SMS_Handler();
                    $sms_handler->send_pattern($pattern_id, $expert_phone, $params);
                }
            }
            // --- END OF NEW LOGIC ---

        } elseif ($final_status === 'rejected' && !empty($_POST['rejection_reason'])) {
            $reason = sanitize_textarea_field($_POST['rejection_reason']);
            update_post_meta($post_id, 'rejection_reason', $reason);
        }
        
        if ($old_status !== $final_status) {
            update_post_meta($post_id, 'inquiry_status', $final_status);
            
            $user_info = get_userdata($user_id);
            $user_name = $user_info->display_name ?? '';
            $mobile_number = get_user_meta($user_id, 'mobile_number', true);
            $car_name = get_the_title(get_post_meta($post_id, 'product_id', true)) ?? '';
            $pattern_id = 0; $params = [];
            
            if ($final_status === 'user_confirmed') {
                $pattern_id = $options['sms_pattern_approved'] ?? 0;
                $params = [(string)$user_name, (string)$car_name];
            } elseif ($final_status === 'rejected') {
                $pattern_id = $options['sms_pattern_rejected'] ?? 0;
                $rejection_reason = get_post_meta($post_id, 'rejection_reason', true) ?? '';
                $params = [(string)$user_name, (string)$car_name, (string)$rejection_reason];
            } elseif ($final_status === 'more_docs') {
                $pattern_id = $options['sms_pattern_more_docs'] ?? 0;
                $params = [(string)$user_name, (string)$car_name];
            }

            if ($pattern_id > 0 && !empty($mobile_number)) {
                $sms_handler = new Maneli_SMS_Handler();
                $sms_handler->send_pattern($pattern_id, $mobile_number, $params);
            }
        }
        
        wp_redirect(admin_url('edit.php?post_type=inquiry'));
        exit;
    }
}