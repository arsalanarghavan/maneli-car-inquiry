<?php
if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Installment_Inquiry_Handler {

    public function __construct() {
        // Customer Workflow Hooks for Installment Inquiries
        add_action('wp_ajax_maneli_select_car_ajax', [$this, 'handle_car_selection_ajax']);
        add_action('admin_post_nopriv_maneli_submit_identity', '__return_false');
        add_action('admin_post_maneli_submit_identity', [$this, 'handle_identity_submission']);
        add_action('admin_post_maneli_retry_inquiry', [$this, 'handle_inquiry_retry']);
    }

    public function handle_car_selection_ajax() {
        check_ajax_referer('maneli_ajax_nonce', 'nonce');
        if (!is_user_logged_in() || empty($_POST['product_id'])) {
            wp_send_json_error(['message' => 'درخواست نامعتبر است.']);
        }
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
        
        // Sanitize all buyer fields
        $buyer_fields = [
            'first_name', 'last_name', 'father_name', 'national_code', 'occupation', 
            'income_level', 'mobile_number', 'phone_number', 'residency_status', 
            'workplace_status', 'address', 'birth_date', 'bank_name', 'account_number',
            'branch_code', 'branch_name'
        ];
        $buyer_data = [];
        foreach ($buyer_fields as $key) {
             if (empty($_POST[$key]) && in_array($key, ['first_name', 'last_name', 'national_code', 'mobile_number'])) {
                wp_die("خطا: لطفاً فیلدهای الزامی خریدار را پر کنید: نام، نام خانوادگی، کد ملی و شماره همراه.");
            }
            $buyer_data[$key] = isset($_POST[$key]) ? sanitize_text_field($_POST[$key]) : '';
        }
        
        // Sanitize all issuer fields if applicable
        $issuer_type = isset($_POST['issuer_type']) ? sanitize_text_field($_POST['issuer_type']) : 'self';
        $issuer_data = [];
        if ($issuer_type === 'other') {
            $issuer_fields = [
                'issuer_full_name', 'issuer_national_code', 'issuer_bank_name', 'issuer_account_number',
                'issuer_branch_code', 'issuer_branch_name', 'issuer_residency_status', 
                'issuer_workplace_status', 'issuer_address', 'issuer_phone_number', 'issuer_father_name',
                'issuer_occupation'
            ];
            foreach ($issuer_fields as $key) {
                // Use appropriate sanitization based on field type
                if ($key === 'issuer_address') {
                    $issuer_data[$key] = sanitize_textarea_field($_POST[$key] ?? '');
                } else {
                    $issuer_data[$key] = sanitize_text_field($_POST[$key] ?? '');
                }
            }
        }

        $temp_data = [
            'buyer_data' => $buyer_data, 
            'issuer_data' => $issuer_data, 
            'issuer_type' => $issuer_type
        ];
        update_user_meta($user_id, 'maneli_temp_inquiry_data', $temp_data);
        
        $options = get_option('maneli_inquiry_all_options', []);
        $inquiry_fee = (int)($options['inquiry_fee'] ?? 0);
        $payment_enabled = isset($options['payment_enabled']) && $options['payment_enabled'] == '1';

        if ($payment_enabled && $inquiry_fee > 0) {
            update_user_meta($user_id, 'maneli_inquiry_step', 'payment_pending');
        } else {
            $this->finalize_inquiry($user_id, true);
        }
        wp_redirect(home_url('/dashboard/?endp=inf_menu_1'));
        exit;
    }

    public function finalize_inquiry($user_id, $is_new = false) {
        $temp_data = get_user_meta($user_id, 'maneli_temp_inquiry_data', true);
        if (empty($temp_data)) return false;

        $buyer_data = $temp_data['buyer_data'];
        $issuer_data = $temp_data['issuer_data'];
        $issuer_type = $temp_data['issuer_type'];

        // Update user profile with new data
        wp_update_user(['ID' => $user_id, 'first_name' => $buyer_data['first_name'], 'last_name' => $buyer_data['last_name']]);
        foreach ($buyer_data as $key => $value) { 
            update_user_meta($user_id, $key, $value); 
        }
        
        $national_code_for_api = ($issuer_type === 'other' && !empty($issuer_data['issuer_national_code'])) ? $issuer_data['issuer_national_code'] : $buyer_data['national_code'];
        
        $finotex_result = $this->execute_finotex_inquiry($national_code_for_api);
        
        $car_id = get_user_meta($user_id, 'maneli_selected_car_id', true);
        $post_content = "گزارش استعلام از فینوتک:\n<pre>" . esc_textarea($finotex_result['raw_response']) . "</pre>";
        $post_title = 'استعلام برای: ' . get_the_title($car_id) . ' - ' . $buyer_data['first_name'] . ' ' . $buyer_data['last_name'];
        
        $post_id = wp_insert_post([
            'post_title'   => $post_title,
            'post_content' => $post_content,
            'post_status'  => 'publish',
            'post_author'  => $user_id,
            'post_type'    => 'inquiry'
        ]);

        if ($post_id && !is_wp_error($post_id)) {
            $initial_status = ($finotex_result['status'] === 'DONE') ? 'pending' : 'failed';
            if ($finotex_result['status'] === 'SKIPPED') {
                $initial_status = 'pending';
            }
            update_post_meta($post_id, 'inquiry_status', $initial_status);

            update_post_meta($post_id, 'issuer_type', $issuer_type);
            
            // Save all buyer and issuer data as post meta
            foreach ($buyer_data as $key => $value) { update_post_meta($post_id, $key, $value); }
            if (!empty($issuer_data)) { foreach ($issuer_data as $key => $value) { update_post_meta($post_id, $key, $value); } }
            
            update_post_meta($post_id, 'product_id', $car_id);
            update_post_meta($post_id, '_finotex_response_data', $finotex_result['data']);
            
            $calculator_meta_keys = ['maneli_inquiry_down_payment', 'maneli_inquiry_term_months', 'maneli_inquiry_total_price', 'maneli_inquiry_installment'];
            foreach($calculator_meta_keys as $key) { $value = get_user_meta($user_id, $key, true); if ($value) { update_post_meta($post_id, $key, $value); } }
            
            if ($is_new && $initial_status === 'pending') {
                $sms_handler = new Maneli_SMS_Handler();
                $customer_name = ($buyer_data['first_name'] ?? '') . ' ' . ($buyer_data['last_name'] ?? '');
                $car_name = get_the_title($car_id) ?? '';
                $all_options = get_option('maneli_inquiry_all_options', []);
                $admin_mobile = $all_options['admin_notification_mobile'] ?? '';
                $pattern_admin = $all_options['sms_pattern_new_inquiry'] ?? 0;
                if (!empty($admin_mobile) && $pattern_admin > 0) { $sms_handler->send_pattern($pattern_admin, $admin_mobile, [$customer_name, $car_name]); }
                $customer_mobile = $buyer_data['mobile_number'] ?? '';
                $pattern_customer = $all_options['sms_pattern_pending'] ?? 0;
                if (!empty($customer_mobile) && $pattern_customer > 0) { $sms_handler->send_pattern($pattern_customer, $customer_mobile, [$customer_name, $car_name]); }
            }
            
            delete_user_meta($user_id, 'maneli_inquiry_step');
            delete_user_meta($user_id, 'maneli_selected_car_id');
            delete_user_meta($user_id, 'maneli_temp_inquiry_data');
            foreach($calculator_meta_keys as $key) { delete_user_meta($user_id, $key); }
        }
        return true;
    }

    private function execute_finotex_inquiry($national_code) {
        $all_options = get_option('maneli_inquiry_all_options', []);
        $finotex_enabled = isset($all_options['finotex_enabled']) && $all_options['finotex_enabled'] == '1';
        
        if (!$finotex_enabled) {
            return ['status' => 'SKIPPED', 'data' => null, 'raw_response' => 'استعلام فینوتک در تنظیمات غیرفعال است.'];
        }

        $client_id = $all_options['finotex_client_id'] ?? '';
        $api_key = $all_options['finotex_api_key'] ?? '';
        $result = ['status' => 'FAILED', 'data' => null, 'raw_response' => ''];

        if (empty($client_id) || empty($api_key)) {
            $result['raw_response'] = 'خطای پلاگین: شناسه کلاینت یا توکن فینوتک در تنظیمات وارد نشده است.';
            return $result;
        }

        $api_url = "https://api.finnotech.ir/credit/v2/clients/{$client_id}/chequeColorInquiry";
        $track_id = 'maneli_' . uniqid();
        $api_url_with_params = add_query_arg(['idCode' => $national_code, 'trackId' => $track_id], $api_url);
        
        $response = wp_remote_get($api_url_with_params, [
            'headers' => ['Authorization' => 'Bearer ' . $api_key, 'Accept' => 'application/json'],
            'timeout' => 45
        ]);

        if (is_wp_error($response)) {
            $result['raw_response'] = 'خطای اتصال وردپرس: ' . $response->get_error_message();
            return $result;
        }

        $response_body = wp_remote_retrieve_body($response);
        $response_code = wp_remote_retrieve_response_code($response);
        $response_data = json_decode($response_body, true);

        $result['raw_response'] = "Request URL: {$api_url_with_params}\nResponse Code: {$response_code}\nResponse Body:\n" . print_r($response_body, true);

        if ($response_code === 200 && isset($response_data['status']) && $response_data['status'] === 'DONE') {
            $result['status'] = 'DONE';
            $result['data'] = $response_data;
        }

        return $result;
    }

    public function handle_inquiry_retry() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'maneli_retry_inquiry_nonce')) wp_die('خطای امنیتی!');
        if (!is_user_logged_in() || empty($_POST['inquiry_id'])) wp_die('درخواست نامعتبر!');
        $user_id = get_current_user_id();
        $post_id = intval($_POST['inquiry_id']);
        if (get_post_field('post_author', $post_id) != $user_id) wp_die('خطای دسترسی!');
        wp_delete_post($post_id, true);
        update_user_meta($user_id, 'maneli_inquiry_step', 'form_pending');
        wp_redirect(home_url('/dashboard/?endp=inf_menu_1'));
        exit;
    }
}