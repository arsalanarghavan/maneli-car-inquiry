<?php
if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Form_Handler {

    public function __construct() {
        // Customer Workflow Hooks
        add_action('init', [$this, 'handle_payment_verification']);
        add_action('wp_ajax_maneli_select_car_ajax', [$this, 'handle_car_selection_ajax']);
        add_action('admin_post_nopriv_maneli_submit_identity', '__return_false');
        add_action('admin_post_maneli_submit_identity', [$this, 'handle_identity_submission']);
        add_action('admin_post_nopriv_maneli_start_payment', '__return_false');
        add_action('admin_post_maneli_start_payment', [$this, 'handle_payment_submission']);
        add_action('admin_post_maneli_retry_inquiry', [$this, 'handle_inquiry_retry']);
        
        // Admin Workflow Hooks
        add_action('admin_post_maneli_admin_update_status', [$this, 'handle_admin_update_status']);

        // Expert Workflow Hooks
        add_action('admin_post_nopriv_maneli_expert_create_inquiry', '__return_false');
        add_action('admin_post_maneli_expert_create_inquiry', [$this, 'handle_expert_create_inquiry']);
    }

    private function execute_finotex_inquiry($national_code) {
        $all_options = get_option('maneli_inquiry_all_options', []);
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
    
    public function handle_payment_submission() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'maneli_payment_nonce')) wp_die('خطای امنیتی!');
        if (!is_user_logged_in()) { wp_redirect(home_url()); exit; }
        
        $user_id = get_current_user_id();
        $options = get_option('maneli_inquiry_all_options', []);
        $amount = (int)($options['inquiry_fee'] ?? 0);
        $discount_code = $options['discount_code'] ?? '';
        $submitted_code = isset($_POST['discount_code_input']) ? trim($_POST['discount_code_input']) : '';

        if (!empty($discount_code) && !empty($submitted_code) && $submitted_code === $discount_code) {
            $amount = 0;
            update_user_meta($user_id, 'maneli_discount_applied', 'yes');
        }

        if ($amount <= 0) {
            $this->finalize_inquiry($user_id, true);
            wp_redirect(home_url('/dashboard/?endp=inf_menu_1'));
            exit;
        }

        $active_gateway = $options['active_gateway'] ?? 'zarinpal';
        $order_id = time() . '-' . $user_id;

        update_user_meta($user_id, 'maneli_payment_order_id', $order_id);
        update_user_meta($user_id, 'maneli_payment_amount', $amount);

        if ($active_gateway === 'sadad') {
            $this->process_sadad_payment($user_id, $order_id, $amount, $options);
        } else {
            $this->process_zarinpal_payment($user_id, $order_id, $amount, $options);
        }
    }
    
    private function process_zarinpal_payment($user_id, $order_id, $amount, $options) {
        $merchant_id = $options['zarinpal_merchant_code'] ?? '';
        if (empty($merchant_id)) { wp_die('مرچنت کد زرین‌پال در تنظیمات وارد نشده است.'); }
        
        $user_info = get_userdata($user_id);
        $description = "هزینه استعلام به شماره سفارش " . $order_id;
        $callback_url = home_url('/?maneli_payment_verify=zarinpal&uid=' . $user_id);
        
        $data = ['merchant_id' => $merchant_id, 'amount' => $amount * 10, 'description' => $description, 'callback_url' => $callback_url, 'metadata' => ['email' => $user_info->user_email, 'order_id' => $order_id]];
        $jsonData = json_encode($data);
        $response = wp_remote_post('https://api.zarinpal.com/pg/v4/payment/request.json', ['method' => 'POST', 'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'], 'body' => $jsonData]);
        
        if (is_wp_error($response)) { wp_die('خطا در اتصال به درگاه پرداخت زرین‌پال.'); }
        
        $result = json_decode(wp_remote_retrieve_body($response), true);
        if (!empty($result['data']) && !empty($result['data']['authority']) && $result['data']['code'] == 100) {
            update_user_meta($user_id, 'maneli_payment_authority', $result['data']['authority']);
            wp_redirect('https://www.zarinpal.com/pg/StartPay/' . $result['data']['authority']);
            exit;
        } else {
            wp_die('خطا از درگاه زرین‌پال: ' . ($result['errors']['message'] ?? 'خطای نامشخص.'));
        }
    }

    private function process_sadad_payment($user_id, $order_id, $amount, $options) {
        $merchant_id = $options['sadad_merchant_id'] ?? '';
        $terminal_id = $options['sadad_terminal_id'] ?? '';
        $terminal_key = $options['sadad_key'] ?? '';
        if (empty($merchant_id) || empty($terminal_id) || empty($terminal_key)) {
            wp_die('اطلاعات درگاه پرداخت سداد در تنظیمات کامل نیست.');
        }

        $sign_data = $this->sadad_encrypt_pkcs7("$terminal_id;$order_id;$amount", $terminal_key);
        
        $data = [
            'TerminalId' => $terminal_id,
            'MerchantId' => $merchant_id,
            'Amount' => $amount,
            'SignData' => $sign_data,
            'ReturnUrl' => home_url('/?maneli_payment_verify=sadad'),
            'LocalDateTime' => date("m/d/Y g:i:s a"),
            'OrderId' => $order_id
        ];

        $result = $this->sadad_call_api('https://sadad.shaparak.ir/vpg/api/v0/Request/PaymentRequest', $data);

        if ($result && isset($result->ResCode) && $result->ResCode == 0) {
            update_user_meta($user_id, 'maneli_payment_token', $result->Token);
            header("Location: https://sadad.shaparak.ir/VPG/Purchase?Token={$result->Token}");
            exit;
        } else {
            wp_die('خطا از درگاه سداد: ' . ($result->Description ?? 'خطای نامشخص هنگام ایجاد توکن.'));
        }
    }
    
    public function handle_payment_verification() {
        if (!isset($_GET['maneli_payment_verify'])) return;

        $gateway = $_GET['maneli_payment_verify'];
        
        if ($gateway === 'zarinpal') {
            $this->verify_zarinpal_payment();
        } elseif ($gateway === 'sadad') {
            $this->verify_sadad_payment();
        }
    }

    private function verify_zarinpal_payment() {
        if (empty($_GET['Authority']) || empty($_GET['Status']) || empty($_GET['uid'])) return;

        $authority = $_GET['Authority'];
        $status = $_GET['Status'];
        $user_id = intval($_GET['uid']);

        $options = get_option('maneli_inquiry_all_options', []);
        $merchant_id = $options['zarinpal_merchant_code'] ?? '';
        $amount = get_user_meta($user_id, 'maneli_payment_amount', true);
        $saved_authority = get_user_meta($user_id, 'maneli_payment_authority', true);
        $redirect_url = home_url('/dashboard/?endp=inf_menu_1');

        if ($authority !== $saved_authority) { 
            wp_redirect(add_query_arg('payment_status', 'failed', $redirect_url)); 
            exit; 
        }

        if ($status == 'OK') {
            $data = ['merchant_id' => $merchant_id, 'authority' => $authority, 'amount' => (int)$amount * 10];
            $jsonData = json_encode($data);
            $response = wp_remote_post('https://api.zarinpal.com/pg/v4/payment/verify.json', ['method' => 'POST', 'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'], 'body' => $jsonData]);
            
            if (!is_wp_error($response)) {
                $result = json_decode(wp_remote_retrieve_body($response), true);
                if (!empty($result['data']) && in_array($result['data']['code'], [100, 101])) {
                    $this->finalize_inquiry($user_id, true);
                    $redirect_url = add_query_arg('payment_status', 'success', $redirect_url);
                } else {
                    $redirect_url = add_query_arg('payment_status', 'failed', $redirect_url);
                }
            } else {
                $redirect_url = add_query_arg('payment_status', 'failed', $redirect_url);
            }
        } else {
            $redirect_url = add_query_arg('payment_status', 'cancelled', $redirect_url);
        }

        delete_user_meta($user_id, 'maneli_payment_authority');
        delete_user_meta($user_id, 'maneli_payment_amount');
        delete_user_meta($user_id, 'maneli_payment_order_id');
        wp_redirect($redirect_url);
        exit;
    }
    
    private function verify_sadad_payment() {
        if (empty($_POST["OrderId"]) || empty($_POST["token"]) || !isset($_POST["ResCode"])) return;

        $options = get_option('maneli_inquiry_all_options', []);
        $terminal_key = $options['sadad_key'] ?? '';
        $order_id = $_POST["OrderId"];
        $token = $_POST["token"];
        $res_code = $_POST["ResCode"];
        
        list($timestamp, $user_id) = explode('-', $order_id);
        $user_id = intval($user_id);
        
        $redirect_url = home_url('/dashboard/?endp=inf_menu_1');

        if ($res_code == 0) {
            $verify_data = [
                'Token' => $token,
                'SignData' => $this->sadad_encrypt_pkcs7($token, $terminal_key)
            ];

            $result = $this->sadad_call_api('https://sadad.shaparak.ir/vpg/api/v0/Advice/Verify', $verify_data);

            if ($result && isset($result->ResCode) && $result->ResCode == 0) {
                $this->finalize_inquiry($user_id, true);
                $redirect_url = add_query_arg('payment_status', 'success', $redirect_url);
            } else {
                $redirect_url = add_query_arg('payment_status', 'failed', $redirect_url);
            }
        } else {
            $redirect_url = add_query_arg('payment_status', 'cancelled', $redirect_url);
        }

        delete_user_meta($user_id, 'maneli_payment_order_id');
        delete_user_meta($user_id, 'maneli_payment_amount');
        delete_user_meta($user_id, 'maneli_payment_token');
        wp_redirect($redirect_url);
        exit;
    }
    
    private function sadad_encrypt_pkcs7($str, $key) {
        $key = base64_decode($key);
        $ciphertext = openssl_encrypt($str, "DES-EDE3", $key, OPENSSL_RAW_DATA);
        return base64_encode($ciphertext);
    }

    private function sadad_call_api($url, $data = false) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json; charset=utf-8']);
        curl_setopt($ch, CURLOPT_POST, 1);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        return !empty($result) ? json_decode($result) : false;
    }

    private function finalize_inquiry($user_id, $is_new = false) {
        $temp_data = get_user_meta($user_id, 'maneli_temp_inquiry_data', true);
        if (empty($temp_data)) return false;

        $buyer_data = $temp_data['buyer_data'];
        $issuer_data = $temp_data['issuer_data'];
        $issuer_type = $temp_data['issuer_type'];

        wp_update_user(['ID' => $user_id, 'first_name' => $buyer_data['first_name'], 'last_name' => $buyer_data['last_name']]);
        foreach ($buyer_data as $key => $value) { update_user_meta($user_id, $key, $value); }
        
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
            update_post_meta($post_id, 'inquiry_status', $initial_status);

            update_post_meta($post_id, 'issuer_type', $issuer_type);
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
    
    public function handle_admin_update_status() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'maneli_admin_update_status_nonce')) wp_die('خطای امنیتی!');
        if (!current_user_can('manage_options') || empty($_POST['inquiry_id']) || empty($_POST['new_status'])) { wp_die('درخواست نامعتبر.'); }
        
        $post_id = intval($_POST['inquiry_id']);
        $new_status_request = sanitize_text_field($_POST['new_status']);
        if (!in_array($new_status_request, ['approved', 'rejected', 'more_docs'])) { wp_die('وضعیت نامعتبر است.'); }
        
        $post = get_post($post_id);
        $user_id = $post->post_author;
        $options = get_option('maneli_inquiry_all_options', []);
        $final_status = $new_status_request;

        if ($new_status_request === 'approved') {
            $final_status = 'user_confirmed';
            if (!get_post_meta($post_id, 'assigned_expert_id', true)) {
                $expert_users = get_users(['role' => 'maneli_expert', 'orderby' => 'ID', 'order' => 'ASC']);
                if (!empty($expert_users)) {
                    $last_index = get_option('maneli_expert_last_assigned_index', -1);
                    $next_index = ($last_index + 1) % count($expert_users);
                    $assigned_expert = $expert_users[$next_index];
                    update_post_meta($post_id, 'assigned_expert_id', $assigned_expert->ID);
                    update_post_meta($post_id, 'assigned_expert_name', $assigned_expert->display_name);
                    update_option('maneli_expert_last_assigned_index', $next_index);
                    
                    $expert_phone = get_user_meta($assigned_expert->ID, 'mobile_number', true);
                    $pattern_id = $options['sms_pattern_expert_referral'] ?? 0;
                    if ($pattern_id > 0 && !empty($expert_phone)) {
                        $customer_info = get_userdata($user_id);
                        $customer_name = ($customer_info->first_name ?? '') . ' ' . ($customer_info->last_name ?? '');
                        $customer_mobile = get_post_meta($post_id, 'mobile_number', true) ?? '';
                        $car_name = get_the_title(get_post_meta($post_id, 'product_id', true)) ?? '';
                        $params = [(string)$assigned_expert->display_name, (string)$customer_name, (string)$customer_mobile, (string)$car_name];
                        $sms_handler = new Maneli_SMS_Handler();
                        $sms_handler->send_pattern($pattern_id, $expert_phone, $params);
                    }
                }
            }
        } elseif ($final_status === 'rejected' && !empty($_POST['rejection_reason'])) {
            $reason = sanitize_textarea_field($_POST['rejection_reason']);
            update_post_meta($post_id, 'rejection_reason', $reason);
        }
        
        update_post_meta($post_id, 'inquiry_status', $final_status);
        
        $user_info = get_userdata($user_id);
        $user_name = $user_info->display_name ?? '';
        $mobile_number = get_user_meta($user_id, 'mobile_number', true);
        $car_name = get_the_title(get_post_meta($post_id, 'product_id', true)) ?? '';
        $pattern_id = 0; $params = [];

        switch ($final_status) {
            case 'user_confirmed':
                $pattern_id = $options['sms_pattern_approved'] ?? 0;
                $params = [(string)$user_name, (string)$car_name];
                break;
            case 'rejected':
                $pattern_id = $options['sms_pattern_rejected'] ?? 0;
                $rejection_reason = get_post_meta($post_id, 'rejection_reason', true) ?? '';
                $params = [(string)$user_name, (string)$car_name, (string)$rejection_reason];
                break;
            case 'more_docs':
                $pattern_id = $options['sms_pattern_more_docs'] ?? 0;
                $params = [(string)$user_name, (string)$car_name];
                break;
        }

        if ($pattern_id > 0 && !empty($mobile_number)) {
            $sms_handler = new Maneli_SMS_Handler();
            $sms_handler->send_pattern($pattern_id, $mobile_number, $params);
        }
        
        wp_redirect(admin_url('edit.php?post_type=inquiry'));
        exit;
    }

    public function handle_expert_create_inquiry() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'maneli_expert_create_nonce')) wp_die('خطای امنیتی!');
        if (!is_user_logged_in() || !current_user_can('maneli_expert')) { wp_die('شما اجازه دسترسی به این قابلیت را ندارید.'); }
        
        $expert_user = wp_get_current_user();
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        if (empty($product_id)) wp_die('لطفاً یک خودرو انتخاب کنید.');
    
        $buyer_fields = ['first_name', 'last_name', 'national_code', 'father_name', 'birth_date', 'mobile_number'];
        $buyer_data = [];
        foreach ($buyer_fields as $key) { if (empty($_POST[$key])) wp_die("خطا: لطفاً تمام فیلدهای خریدار را پر کنید."); $buyer_data[$key] = sanitize_text_field($_POST[$key]); }
        
        $issuer_type = isset($_POST['issuer_type']) ? sanitize_text_field($_POST['issuer_type']) : 'self';
        $issuer_data = [];
        if ($issuer_type === 'other') {
            $issuer_fields = ['issuer_first_name', 'issuer_last_name', 'issuer_national_code', 'issuer_father_name', 'issuer_birth_date', 'issuer_mobile_number'];
            foreach ($issuer_fields as $key) { if (empty($_POST[$key])) wp_die("خطا: لطفاً تمام فیلدهای صادرکننده چک را پر کنید."); $issuer_data[$key] = sanitize_text_field($_POST[$key]); }
        }
        
        $customer_id = username_exists($buyer_data['mobile_number']);
        $dummy_email = $buyer_data['mobile_number'] . '@maneli-auto.local';
        if (!$customer_id) { $customer_id = email_exists($dummy_email); }
        if (!$customer_id) {
            $random_password = wp_generate_password(12, false);
            $customer_id = wp_create_user($buyer_data['mobile_number'], $random_password, $dummy_email);
            if (is_wp_error($customer_id)) { wp_die('خطا در ساخت کاربر جدید: ' . $customer_id->get_error_message()); }
            wp_update_user(['ID' => $customer_id, 'first_name' => $buyer_data['first_name'], 'last_name' => $buyer_data['last_name'], 'role' => 'subscriber']);
        }
        foreach ($buyer_data as $key => $value) { update_user_meta($customer_id, $key, $value); }
    
        $national_code_for_api = ($issuer_type === 'other' && !empty($issuer_data['issuer_national_code'])) ? $issuer_data['issuer_national_code'] : $buyer_data['national_code'];
        
        $finotex_result = $this->execute_finotex_inquiry($national_code_for_api);
        
        $post_title = 'استعلام برای ' . $buyer_data['first_name'] . ' ' . $buyer_data['last_name'] . ' (توسط ' . $expert_user->display_name . ')';
        $post_content = "گزارش استعلام از فینوتک:\n<pre>" . esc_textarea($finotex_result['raw_response']) . "</pre>";
        
        $post_id = wp_insert_post([
            'post_title'   => $post_title,
            'post_author'  => $customer_id,
            'post_status'  => 'publish',
            'post_type'    => 'inquiry',
            'post_content' => $post_content
        ]);
        
        if ($post_id && !is_wp_error($post_id)) {
            $initial_status = ($finotex_result['status'] === 'DONE') ? 'pending' : 'failed';
            update_post_meta($post_id, 'inquiry_status', $initial_status);
            
            update_post_meta($post_id, 'assigned_expert_id', $expert_user->ID);
            update_post_meta($post_id, 'assigned_expert_name', $expert_user->display_name);
    
            update_post_meta($post_id, 'product_id', $product_id);
            update_post_meta($post_id, '_finotex_response_data', $finotex_result['data']);
            update_post_meta($post_id, 'issuer_type', $issuer_type);
            foreach ($buyer_data as $key => $value) { update_post_meta($post_id, $key, $value); }
            if (!empty($issuer_data)) { foreach ($issuer_data as $key => $value) { update_post_meta($post_id, $key, $value); } }
            
            $down_payment = sanitize_text_field(str_replace(',', '', $_POST['down_payment']));
            $term_months = sanitize_text_field($_POST['term_months']);
            update_post_meta($post_id, 'maneli_inquiry_down_payment', $down_payment);
            update_post_meta($post_id, 'maneli_inquiry_term_months', $term_months);
        }
        
        $redirect_url = add_query_arg('inquiry_created', '1', wp_get_referer());
        wp_redirect($redirect_url);
        exit;
    }
}