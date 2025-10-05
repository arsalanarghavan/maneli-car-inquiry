<?php
if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Form_Handler {

    public function __construct() {
        // Customer Workflow Hooks
        add_action('template_redirect', [$this, 'handle_payment_verification']);
        
        add_action('wp_ajax_maneli_select_car_ajax', [$this, 'handle_car_selection_ajax']);
        add_action('admin_post_nopriv_maneli_submit_identity', '__return_false');
        add_action('admin_post_maneli_submit_identity', [$this, 'handle_identity_submission']);
        add_action('admin_post_nopriv_maneli_start_payment', '__return_false');
        add_action('admin_post_maneli_start_payment', [$this, 'handle_payment_submission']);
        add_action('admin_post_maneli_retry_inquiry', [$this, 'handle_inquiry_retry']);
		add_action('admin_post_nopriv_maneli_submit_cash_inquiry', [$this, 'handle_cash_inquiry_submission']);
        add_action('admin_post_maneli_submit_cash_inquiry', [$this, 'handle_cash_inquiry_submission']);
        add_action('admin_post_maneli_start_cash_payment', [$this, 'handle_start_cash_payment']);

        
        // Admin Workflow Hooks
        add_action('admin_post_maneli_admin_update_status', [$this, 'handle_admin_update_status']);
        add_action('admin_post_maneli_admin_update_user', [$this, 'handle_admin_update_user_profile']);
        add_action('admin_post_maneli_admin_create_user', [$this, 'handle_admin_create_user']);
        add_action('admin_post_maneli_admin_retry_finotex', [$this, 'handle_admin_retry_finotex']);
		
		// AJAX hooks for cash inquiry management
        add_action('wp_ajax_maneli_get_cash_inquiry_details', [$this, 'ajax_get_cash_inquiry_details']);
        add_action('wp_ajax_maneli_update_cash_inquiry', [$this, 'ajax_update_cash_inquiry']);
        add_action('wp_ajax_maneli_delete_cash_inquiry', [$this, 'ajax_delete_cash_inquiry']);
        add_action('wp_ajax_maneli_set_down_payment', [$this, 'ajax_set_down_payment']);
		add_action('wp_ajax_maneli_get_inquiry_details', [$this, 'ajax_get_inquiry_details']);



        // Expert Workflow Hooks
        add_action('admin_post_nopriv_maneli_expert_create_inquiry', '__return_false');
        add_action('admin_post_maneli_expert_create_inquiry', [$this, 'handle_expert_create_inquiry']);
    }
	
	public function ajax_get_inquiry_details() {
        check_ajax_referer('maneli_inquiry_details_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز'], 403);
        }

        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        if (!$inquiry_id) {
            wp_send_json_error(['message' => 'شناسه استعلام نامعتبر است.']);
        }

        $inquiry = get_post($inquiry_id);
        if (!$inquiry || $inquiry->post_type !== 'inquiry') {
            wp_send_json_error(['message' => 'استعلام یافت نشد.']);
        }

        $current_user = wp_get_current_user();
        $can_view = false;
        if (current_user_can('manage_maneli_inquiries') || (int)$inquiry->post_author === $current_user->ID) {
            $can_view = true;
        } elseif (in_array('maneli_expert', $current_user->roles)) {
            $assigned_expert_id = (int)get_post_meta($inquiry_id, 'assigned_expert_id', true);
            if ($assigned_expert_id === $current_user->ID) {
                $can_view = true;
            }
        }

        if (!$can_view) {
            wp_send_json_error(['message' => 'شما اجازه مشاهده این گزارش را ندارید.'], 403);
        }

        $post_meta = get_post_meta($inquiry_id);
        $finotex_data = get_post_meta($inquiry_id, '_finotex_response_data', true);
        $product_id = $post_meta['product_id'][0] ?? 0;
        $status = $post_meta['inquiry_status'][0] ?? 'pending';

        $status_map = Maneli_CPT_Handler::get_all_statuses();

        $data = [
            'id' => $inquiry_id,
            'status_label' => $status_map[$status] ?? 'نامشخص',
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
            'buyer' => [
                'first_name' => $post_meta['first_name'][0] ?? '',
                'last_name' => $post_meta['last_name'][0] ?? '',
                'national_code' => $post_meta['national_code'][0] ?? '',
                'father_name' => $post_meta['father_name'][0] ?? '',
                'birth_date' => $post_meta['birth_date'][0] ?? '',
                'mobile' => $post_meta['mobile_number'][0] ?? '',
            ],
            'issuer_type' => $post_meta['issuer_type'][0] ?? 'self',
            'issuer' => null,
            'finotex' => [
                'skipped' => (empty($finotex_data) || (isset($finotex_data['status']) && $finotex_data['status'] === 'SKIPPED')),
                'color_code' => $finotex_data['result']['chequeColor'] ?? 0,
            ],
        ];

        if ($data['issuer_type'] === 'other') {
            $data['issuer'] = [
                'first_name' => $post_meta['issuer_first_name'][0] ?? '',
                'last_name' => $post_meta['issuer_last_name'][0] ?? '',
                'national_code' => $post_meta['issuer_national_code'][0] ?? '',
                'father_name' => $post_meta['issuer_father_name'][0] ?? '',
                'birth_date' => $post_meta['issuer_birth_date'][0] ?? '',
                'mobile' => $post_meta['issuer_mobile_number'][0] ?? '',
            ];
        }

        wp_send_json_success($data);
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
    
    private function assign_expert_round_robin($post_id) {
        $expert_users = get_users(['role' => 'maneli_expert', 'orderby' => 'ID', 'order' => 'ASC']);
        if (empty($expert_users)) {
            return false;
        }
    
        $last_index = get_option('maneli_expert_last_assigned_index', -1);
        $next_index = ($last_index + 1) % count($expert_users);
        $assigned_expert = $expert_users[$next_index];
        
        update_post_meta($post_id, 'assigned_expert_id', $assigned_expert->ID);
        update_post_meta($post_id, 'assigned_expert_name', $assigned_expert->display_name);
        update_option('maneli_expert_last_assigned_index', $next_index);
    
        $options = get_option('maneli_inquiry_all_options', []);
        $expert_phone = get_user_meta($assigned_expert->ID, 'mobile_number', true);
        $pattern_id = $options['sms_pattern_expert_referral'] ?? 0;
    
        if ($pattern_id > 0 && !empty($expert_phone)) {
            $user_id = get_post_field('post_author', $post_id);
            $customer_info = get_userdata($user_id);
            $customer_name = ($customer_info->first_name ?? '') . ' ' . ($customer_info->last_name ?? '');
            $customer_mobile = get_post_meta($post_id, 'mobile_number', true) ?? '';
            $car_name = get_the_title(get_post_meta($post_id, 'product_id', true)) ?? '';
            $params = [(string)$assigned_expert->display_name, (string)$customer_name, (string)$customer_mobile, (string)$car_name];
            $sms_handler = new Maneli_SMS_Handler();
            $sms_handler->send_pattern($pattern_id, $expert_phone, $params);
        }
        
        return $assigned_expert->ID;
    }
	
	public function handle_cash_inquiry_submission() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'maneli_cash_inquiry_nonce')) {
            wp_die('خطای امنیتی!');
        }
    
        $fields = ['product_id', 'cash_first_name', 'cash_last_name', 'cash_mobile_number', 'cash_car_color'];
        foreach ($fields as $field) {
            if (empty($_POST[$field])) {
                wp_die('لطفاً تمام فیلدها را پر کنید.');
            }
        }
    
        // *** FIX: Correctly read POST data from form fields ***
        $inquiry_data = [
            'product_id'       => intval($_POST['product_id']),
            'cash_first_name'  => sanitize_text_field($_POST['cash_first_name']),
            'cash_last_name'   => sanitize_text_field($_POST['cash_last_name']),
            'cash_mobile_number' => sanitize_text_field($_POST['cash_mobile_number']),
            'cash_car_color'   => sanitize_text_field($_POST['cash_car_color']),
        ];
    
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            self::create_cash_inquiry_post($inquiry_data, $user_id);
            wp_redirect(add_query_arg('cash_inquiry_sent', 'true', home_url('/dashboard/?endp=inf_menu_5')));
            exit;
        } else {
            if (!session_id()) {
                session_start();
            }
            $_SESSION['maneli_pending_cash_inquiry'] = $inquiry_data;
            
            // *** FIX: Point to the custom login/dashboard page and set correct redirect after login ***
            $redirect_after_login = home_url('/dashboard/?endp=inf_menu_4');
            $login_url = add_query_arg('redirect_to', urlencode($redirect_after_login), home_url('/dashboard/'));
            
            wp_redirect($login_url);
            exit;
        }
    }

    public static function create_cash_inquiry_post($inquiry_data, $user_id) {
        $first_name = $inquiry_data['cash_first_name'];
        $last_name = $inquiry_data['cash_last_name'];
        $mobile = $inquiry_data['cash_mobile_number'];
        $product_id = $inquiry_data['product_id'];
        $car_name = get_the_title($product_id);
    
        // *** FIX: Do NOT update user meta for mobile. Only update name if it's empty. ***
        $user_obj = get_userdata($user_id);
        if(empty($user_obj->first_name) && empty($user_obj->last_name)) {
            wp_update_user([
                'ID'         => $user_id,
                'first_name' => $first_name,
                'last_name'  => $last_name,
            ]);
        }
    
        $post_title = 'درخواست نقدی: ' . $first_name . ' ' . $last_name . ' برای ' . $car_name;
        $post_id = wp_insert_post([
            'post_title'   => $post_title,
            'post_status'  => 'publish',
            'post_author'  => $user_id,
            'post_type'    => 'cash_inquiry'
        ]);
    
        if ($post_id && !is_wp_error($post_id)) {
            update_post_meta($post_id, 'product_id', $product_id);
            update_post_meta($post_id, 'cash_first_name', $first_name);
            update_post_meta($post_id, 'cash_last_name', $last_name);
            update_post_meta($post_id, 'mobile_number', $mobile);
            update_post_meta($post_id, 'cash_car_color', $inquiry_data['cash_car_color']);
            update_post_meta($post_id, 'cash_inquiry_status', 'pending');
            
            $all_options = get_option('maneli_inquiry_all_options', []);
            $admin_mobile = $all_options['admin_notification_mobile'] ?? '';
            $pattern_admin = $all_options['sms_pattern_new_inquiry'] ?? 0;
            if (!empty($admin_mobile) && $pattern_admin > 0) {
                $sms_handler = new Maneli_SMS_Handler();
                $sms_handler->send_pattern($pattern_admin, $admin_mobile, [$first_name . ' ' . $last_name, $car_name . ' (نقدی)']);
            }
    
            if (!session_id()) {
                session_start();
            }
            $_SESSION['maneli_pending_cash_inquiry_processed'] = true;
        }
        
        return $post_id;
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
        foreach ($buyer_fields as $key) {
            if (empty($_POST[$key])) {
                wp_die("خطا: لطفاً تمام فیلدهای اطلاعات خریدار را پر کنید.");
            }
            $buyer_data[$key] = sanitize_text_field($_POST[$key]);
        }
        
        $issuer_data = [];
        if ($issuer_type === 'other') {
            $issuer_fields = ['issuer_first_name', 'issuer_last_name', 'issuer_national_code', 'issuer_father_name', 'issuer_birth_date', 'issuer_mobile_number'];
            foreach ($issuer_fields as $key) {
                if (empty($_POST[$key])) {
                    wp_die("خطا: لطفاً تمام فیلدهای اطلاعات صادرکننده چک را پر کنید.");
                }
                $issuer_data[$key] = sanitize_text_field($_POST[$key]);
            }
        }

        $temp_data = ['buyer_data' => $buyer_data, 'issuer_data' => $issuer_data, 'issuer_type' => $issuer_type];
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
    
    public function handle_payment_submission() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'maneli_payment_nonce')) wp_die('خطای امنیتی!');
        if (!is_user_logged_in()) { wp_redirect(home_url()); exit; }
        
        $user_id = get_current_user_id();
        $options = get_option('maneli_inquiry_all_options', []);
        $amount_toman = (int)($options['inquiry_fee'] ?? 0);
        $discount_code = $options['discount_code'] ?? '';
        $submitted_code = isset($_POST['discount_code_input']) ? trim($_POST['discount_code_input']) : '';

        if (!empty($discount_code) && !empty($submitted_code) && $submitted_code === $discount_code) {
            $amount_toman = 0;
            update_user_meta($user_id, 'maneli_discount_applied', 'yes');
        }

        if ($amount_toman <= 0) {
            $this->finalize_inquiry($user_id, true);
            wp_redirect(home_url('/dashboard/?endp=inf_menu_1'));
            exit;
        }

        $zarinpal_enabled = isset($options['zarinpal_enabled']) && $options['zarinpal_enabled'] == '1';
        $sadad_enabled = isset($options['sadad_enabled']) && $options['sadad_enabled'] == '1';
        $active_gateway = $options['active_gateway'] ?? 'zarinpal';
        $order_id = time() . $user_id;

        update_user_meta($user_id, 'maneli_payment_order_id', $order_id);
        update_user_meta($user_id, 'maneli_payment_amount', $amount_toman);

        if ($active_gateway === 'sadad' && $sadad_enabled) {
            $this->process_sadad_payment($user_id, $order_id, $amount_toman, $options);
        } elseif ($active_gateway === 'zarinpal' && $zarinpal_enabled) {
            $this->process_zarinpal_payment($user_id, $order_id, $amount_toman, $options);
        } else {
            if ($zarinpal_enabled) {
                 $this->process_zarinpal_payment($user_id, $order_id, $amount_toman, $options);
            } elseif ($sadad_enabled) {
                 $this->process_sadad_payment($user_id, $order_id, $amount_toman, $options);
            } else {
                wp_die('در حال حاضر هیچ درگاه پرداخت فعالی در تنظیمات وجود ندارد.');
            }
        }
    }
    
    private function process_zarinpal_payment($user_id, $order_id, $amount_toman, $options) {
        $merchant_id = $options['zarinpal_merchant_code'] ?? '';
        if (empty($merchant_id)) { wp_die('مرچنت کد زرین‌پال در تنظیمات وارد نشده است.'); }
        
        $user_info = get_userdata($user_id);
        $description = "هزینه استعلام به شماره سفارش " . $order_id;
        $callback_url = home_url('/?maneli_payment_verify=zarinpal&uid=' . $user_id);
        
        $data = ['merchant_id' => $merchant_id, 'amount' => $amount_toman * 10, 'description' => $description, 'callback_url' => $callback_url, 'metadata' => ['email' => $user_info->user_email, 'order_id' => $order_id]];
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

    private function process_sadad_payment($user_id, $order_id, $amount_toman, $options) {
        $merchant_id = $options['sadad_merchant_id'] ?? '';
        $terminal_id = $options['sadad_terminal_id'] ?? '';
        $terminal_key = $options['sadad_key'] ?? '';
        if (empty($merchant_id) || empty($terminal_id) || empty($terminal_key)) {
            wp_die('اطلاعات درگاه پرداخت سداد در تنظیمات کامل نیست.');
        }

        $amount_rial = $amount_toman * 10;
        if ($amount_rial < 1000) {
            $amount_rial = 1000;
        }
        
        $sign_data = $this->sadad_encrypt_pkcs7("$terminal_id;$order_id;$amount_rial", $terminal_key);
        
        $data = [
            'TerminalId' => $terminal_id,
            'MerchantId' => $merchant_id,
            'Amount' => $amount_rial,
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
        if (!isset($_GET['maneli_payment_verify'])) {
            return;
        }

        $gateway = $_GET['maneli_payment_verify'];
        
        if ($gateway === 'zarinpal') {
            $this->verify_zarinpal_payment();
        } elseif ($gateway === 'sadad') {
            $this->verify_sadad_payment();
        }
    }

    private function verify_zarinpal_payment() {
        if (empty($_GET['Authority']) || empty($_GET['Status']) || empty($_GET['uid'])) {
            return;
        }
    
        $authority = sanitize_text_field($_GET['Authority']);
        $status = sanitize_text_field($_GET['Status']);
        $user_id = intval($_GET['uid']);
    
        $options = get_option('maneli_inquiry_all_options', []);
        $merchant_id = $options['zarinpal_merchant_code'] ?? '';
        $amount = get_user_meta($user_id, 'maneli_payment_amount', true);
        $saved_authority = get_user_meta($user_id, 'maneli_payment_authority', true);
        $payment_type = get_user_meta($user_id, 'maneli_payment_type', true);
    
        if ($payment_type === 'cash_down_payment') {
            $redirect_url = home_url('/dashboard/?endp=inf_menu_5');
        } else {
            $redirect_url = home_url('/dashboard/?endp=inf_menu_1');
        }
    
        if ($authority !== $saved_authority) { 
            $redirect_url = add_query_arg(['payment_status' => 'failed', 'reason' => urlencode('اطلاعات تراکنش مغایرت دارد.')], $redirect_url);
        } elseif ($status == 'OK') {
            $data = ['merchant_id' => $merchant_id, 'authority' => $authority, 'amount' => (int)$amount * 10];
            $jsonData = json_encode($data);
            $response = wp_remote_post('https://api.zarinpal.com/pg/v4/payment/verify.json', ['method' => 'POST', 'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'], 'body' => $jsonData]);
            
            if (!is_wp_error($response)) {
                $result = json_decode(wp_remote_retrieve_body($response), true);
                if (!empty($result['data']) && in_array($result['data']['code'], [100, 101])) {
                    if ($payment_type === 'cash_down_payment') {
                        $inquiry_id = get_user_meta($user_id, 'maneli_payment_cash_inquiry_id', true);
                        if($inquiry_id) update_post_meta($inquiry_id, 'cash_inquiry_status', 'completed');
                    } else {
                        $this->finalize_inquiry($user_id, true);
                    }
                    $redirect_url = add_query_arg('payment_status', 'success', $redirect_url);
                } else {
                    $error_message = $result['errors']['message'] ?? 'تراکنش توسط درگاه تایید نشد.';
                    $redirect_url = add_query_arg(['payment_status' => 'failed', 'reason' => urlencode($error_message)], $redirect_url);
                }
            } else {
                $redirect_url = add_query_arg(['payment_status' => 'failed', 'reason' => urlencode('خطا در برقراری ارتباط با درگاه پرداخت.')], $redirect_url);
            }
        } else {
            $redirect_url = add_query_arg('payment_status', 'cancelled', $redirect_url);
        }
    
        delete_user_meta($user_id, 'maneli_payment_authority');
        delete_user_meta($user_id, 'maneli_payment_amount');
        delete_user_meta($user_id, 'maneli_payment_order_id');
        delete_user_meta($user_id, 'maneli_payment_type');
        delete_user_meta($user_id, 'maneli_payment_cash_inquiry_id');
        
        if (!headers_sent()) {
            wp_redirect($redirect_url);
            exit;
        }
    }
    
    private function verify_sadad_payment() {
        if (empty($_POST["OrderId"]) || !isset($_POST["ResCode"])) {
            return;
        }
    
        $order_id = sanitize_text_field($_POST["OrderId"]);
        $res_code = sanitize_text_field($_POST["ResCode"]);
        $user_id = intval(substr($order_id, 10));
        $payment_type = get_user_meta($user_id, 'maneli_payment_type', true);
    
        if ($payment_type === 'cash_down_payment') {
            $redirect_url = home_url('/dashboard/?endp=inf_menu_5');
        } else {
            $redirect_url = home_url('/dashboard/?endp=inf_menu_1');
        }
    
        if ($res_code == 0) {
            if (empty($_POST["token"])) {
                $redirect_url = add_query_arg(['payment_status' => 'failed', 'reason' => urlencode('توکن بازگشتی از بانک نامعتبر است.')], $redirect_url);
            } else {
                $token = sanitize_text_field($_POST["token"]);
                $options = get_option('maneli_inquiry_all_options', []);
                $terminal_key = $options['sadad_key'] ?? '';
                $verify_data = [
                    'Token' => $token,
                    'SignData' => $this->sadad_encrypt_pkcs7($token, $terminal_key)
                ];
                $result = $this->sadad_call_api('https://sadad.shaparak.ir/vpg/api/v0/Advice/Verify', $verify_data);
    
                if ($result && isset($result->ResCode) && $result->ResCode == 0) {
                    if ($payment_type === 'cash_down_payment') {
                        $inquiry_id = get_user_meta($user_id, 'maneli_payment_cash_inquiry_id', true);
                        if($inquiry_id) update_post_meta($inquiry_id, 'cash_inquiry_status', 'completed');
                    } else {
                        $this->finalize_inquiry($user_id, true);
                    }
                    $redirect_url = add_query_arg('payment_status', 'success', $redirect_url);
                } else {
                    $error_message = $result->Description ?? 'تراکنش در مرحله تایید نهایی ناموفق بود.';
                    $redirect_url = add_query_arg(['payment_status' => 'failed', 'reason' => urlencode($error_message)], $redirect_url);
                }
            }
        } else {
            $error_message = isset($_POST['Description']) ? sanitize_text_field($_POST['Description']) : 'تراکنش توسط بانک لغو شد.';
            $redirect_url = add_query_arg(['payment_status' => 'failed', 'reason' => urlencode($error_message)], $redirect_url);
        }
    
        delete_user_meta($user_id, 'maneli_payment_order_id');
        delete_user_meta($user_id, 'maneli_payment_amount');
        delete_user_meta($user_id, 'maneli_payment_token');
        delete_user_meta($user_id, 'maneli_payment_type');
        delete_user_meta($user_id, 'maneli_payment_cash_inquiry_id');

        if (!headers_sent()) {
            wp_redirect($redirect_url);
            exit;
        }
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
            if ($finotex_result['status'] === 'SKIPPED') {
                $initial_status = 'pending';
            }
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
        
        if (!current_user_can('manage_maneli_inquiries') || empty($_POST['inquiry_id']) || empty($_POST['new_status'])) { 
            wp_die('درخواست نامعتبر یا دسترسی غیرمجاز.'); 
        }
        
        $post_id = intval($_POST['inquiry_id']);
        $new_status_request = sanitize_text_field($_POST['new_status']);
        if (!in_array($new_status_request, ['approved', 'rejected', 'more_docs'])) { wp_die('وضعیت نامعتبر است.'); }
        
        $post = get_post($post_id);
        $user_id = $post->post_author;
        $options = get_option('maneli_inquiry_all_options', []);
        $final_status = $new_status_request;

        if ($new_status_request === 'approved') {
            $final_status = 'user_confirmed';
            $selected_expert_id = isset($_POST['assigned_expert_id']) ? sanitize_text_field($_POST['assigned_expert_id']) : 'auto';

            if ($selected_expert_id !== 'auto' && !empty($selected_expert_id)) {
                $expert_user = get_userdata(intval($selected_expert_id));
                if ($expert_user && in_array('maneli_expert', $expert_user->roles)) {
                    update_post_meta($post_id, 'assigned_expert_id', $expert_user->ID);
                    update_post_meta($post_id, 'assigned_expert_name', $expert_user->display_name);
                }
            } else {
                 if (!get_post_meta($post_id, 'assigned_expert_id', true)) {
                    $this->assign_expert_round_robin($post_id);
                }
            }
        } elseif ($final_status === 'rejected' && !empty($_POST['rejection_reason'])) {
            $reason = sanitize_textarea_field($_POST['rejection_reason']);
            update_post_meta($post_id, 'rejection_reason', $reason);
        }
        
        $options = get_option('maneli_inquiry_all_options', []);
        $pattern_id = $options['sms_pattern_expert_referral'] ?? 0;
        $assigned_expert_id_for_sms = get_post_meta($post_id, 'assigned_expert_id', true);

        if ($pattern_id > 0 && $assigned_expert_id_for_sms) {
            $expert_phone = get_user_meta($assigned_expert_id_for_sms, 'mobile_number', true);
            if (!empty($expert_phone)) {
                $expert_info = get_userdata($assigned_expert_id_for_sms);
                $customer_info = get_userdata($user_id);
                $customer_name = ($customer_info->first_name ?? '') . ' ' . ($customer_info->last_name ?? '');
                $customer_mobile = get_post_meta($post_id, 'mobile_number', true) ?? '';
                $car_name = get_the_title(get_post_meta($post_id, 'product_id', true)) ?? '';
                $params = [(string)$expert_info->display_name, (string)$customer_name, (string)$customer_mobile, (string)$car_name];
                $sms_handler = new Maneli_SMS_Handler();
                $sms_handler->send_pattern($pattern_id, $expert_phone, $params);
            }
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
        
        $redirect_url = home_url('/dashboard/?endp=inf_menu_4&inquiry_id=' . $post_id);
        wp_redirect($redirect_url);
        exit;
    }

    public function handle_expert_create_inquiry() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'maneli_expert_create_nonce')) wp_die('خطای امنیتی!');
        if (!is_user_logged_in() || !(current_user_can('maneli_expert') || current_user_can('manage_maneli_inquiries'))) { wp_die('شما اجازه دسترسی به این قابلیت را ندارید.'); }
        
        $submitter = wp_get_current_user();
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
        $dummy_email = $buyer_data['mobile_number'] . '@manelikhodro.com';

        if (!$customer_id) { $customer_id = email_exists($dummy_email); }
        if (!$customer_id) {
            $random_password = wp_generate_password(12, false);
            $customer_id = wp_create_user($buyer_data['mobile_number'], $random_password, $dummy_email);
            if (is_wp_error($customer_id)) { wp_die('خطا در ساخت کاربر جدید: ' . $customer_id->get_error_message()); }
            wp_update_user(['ID' => $customer_id, 'first_name' => $buyer_data['first_name'], 'last_name' => $buyer_data['last_name'], 'role' => 'customer', 'user_email' => $dummy_email]);
        } else {
            wp_update_user(['ID' => $customer_id, 'first_name' => $buyer_data['first_name'], 'last_name' => $buyer_data['last_name']]);
        }
        foreach ($buyer_data as $key => $value) { update_user_meta($customer_id, $key, $value); }
    
        $national_code_for_api = ($issuer_type === 'other' && !empty($issuer_data['issuer_national_code'])) ? $issuer_data['issuer_national_code'] : $buyer_data['national_code'];
        $finotex_result = $this->execute_finotex_inquiry($national_code_for_api);
        $post_title = 'استعلام برای ' . $buyer_data['first_name'] . ' ' . $buyer_data['last_name'] . ' (توسط ' . $submitter->display_name . ')';
        $post_content = "گزارش استعلام از فینوتک:\n<pre>" . esc_textarea($finotex_result['raw_response']) . "</pre>";
        $post_id = wp_insert_post(['post_title' => $post_title, 'post_author' => $customer_id, 'post_status' => 'publish', 'post_type' => 'inquiry', 'post_content' => $post_content]);
        
        if ($post_id && !is_wp_error($post_id)) {
            $initial_status = ($finotex_result['status'] === 'DONE') ? 'pending' : 'failed';
            if ($finotex_result['status'] === 'SKIPPED') { $initial_status = 'pending'; }
            update_post_meta($post_id, 'inquiry_status', $initial_status);
            
            if (current_user_can('manage_maneli_inquiries')) {
                $selected_expert_id = isset($_POST['assigned_expert_id']) ? sanitize_text_field($_POST['assigned_expert_id']) : 'auto';
                if ($selected_expert_id !== 'auto' && !empty($selected_expert_id)) {
                    $expert_user = get_userdata(intval($selected_expert_id));
                    if ($expert_user && in_array('maneli_expert', $expert_user->roles)) {
                        update_post_meta($post_id, 'assigned_expert_id', $expert_user->ID);
                        update_post_meta($post_id, 'assigned_expert_name', $expert_user->display_name);
                    }
                } else { $this->assign_expert_round_robin($post_id); }
            } else {
                update_post_meta($post_id, 'created_by_expert_id', $submitter->ID);
                update_post_meta($post_id, 'assigned_expert_id', $submitter->ID);
                update_post_meta($post_id, 'assigned_expert_name', $submitter->display_name);
            }
    
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

    public function handle_admin_update_user_profile() {
        if (!isset($_POST['maneli_update_user_nonce']) || !wp_verify_nonce($_POST['maneli_update_user_nonce'], 'maneli_admin_update_user')) {
            wp_die('خطای امنیتی!');
        }
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_die('شما دسترسی لازم برای این کار را ندارید.');
        }

        $user_id_to_update = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        if (!$user_id_to_update) {
            wp_die('شناسه کاربر مشخص نشده است.');
        }
        
        if ($user_id_to_update === get_current_user_id() && isset($_POST['user_role'])) {
            $user_obj = get_userdata($user_id_to_update);
            if (!in_array($_POST['user_role'], $user_obj->roles)) {
                 if ($_POST['user_role'] !== 'maneli_admin' && $_POST['user_role'] !== 'administrator') {
                    wp_die('شما نمی‌توانید نقش کاربری مدیریتی خود را به یک نقش پایین‌تر تغییر دهید.');
                 }
            }
        }
        
        $user_data = [];
        if (isset($_POST['first_name'])) $user_data['first_name'] = sanitize_text_field($_POST['first_name']);
        if (isset($_POST['last_name'])) $user_data['last_name'] = sanitize_text_field($_POST['last_name']);
        if (isset($_POST['email'])) {
             $email = sanitize_email($_POST['email']);
             if (is_email($email)) {
                 $user_data['user_email'] = $email;
             }
        }

        if (isset($_POST['user_role'])) {
            $new_role = sanitize_key($_POST['user_role']);
            if (in_array($new_role, ['customer', 'maneli_expert', 'maneli_admin'])) {
                $user_data['role'] = $new_role;
            }
        }

        if (!empty($user_data)) {
            $user_data['ID'] = $user_id_to_update;
            wp_update_user($user_data);
        }

        $meta_fields = ['national_code', 'father_name', 'birth_date', 'mobile_number'];
        foreach ($meta_fields as $field) {
            if (isset($_POST[$field])) {
                update_user_meta($user_id_to_update, $field, sanitize_text_field($_POST[$field]));
            }
        }
        
        $redirect_url = isset($_POST['_wp_http_referer']) ? esc_url_raw(wp_unslash($_POST['_wp_http_referer'])) : home_url();
        $redirect_url = remove_query_arg('edit_user', $redirect_url);
        wp_redirect(add_query_arg('user-updated', 'true', $redirect_url));
        exit;
    }

    public function handle_admin_create_user() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'maneli_admin_create_user_nonce')) {
            wp_die('خطای امنیتی!');
        }
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_die('شما دسترسی لازم برای این کار را ندارید.');
        }

        $redirect_url = isset($_POST['_wp_http_referer']) ? esc_url_raw(wp_unslash($_POST['_wp_http_referer'])) : home_url();

        $mobile = sanitize_text_field($_POST['mobile_number']);
        $password = $_POST['password'];

        if (empty($mobile) || empty($password)) {
            wp_redirect(add_query_arg('error', urlencode('شماره موبایل و رمز عبور الزامی هستند.'), $redirect_url));
            exit;
        }

        $user_login = $mobile;
        $email = $mobile . '@manelikhodro.com';

        if (username_exists($user_login)) {
            wp_redirect(add_query_arg('error', urlencode('کاربری با این شماره موبایل قبلاً ثبت شده است.'), $redirect_url));
            exit;
        }
        if (email_exists($email)) {
             wp_redirect(add_query_arg('error', urlencode('ایمیلی مرتبط با این شماره موبایل قبلاً ثبت شده است.'), $redirect_url));
            exit;
        }

        $user_id = wp_create_user($user_login, $password, $email);

        if (is_wp_error($user_id)) {
            wp_redirect(add_query_arg('error', urlencode($user_id->get_error_message()), $redirect_url));
            exit;
        }

        $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
        $display_name = trim($first_name . ' ' . $last_name);

        $user_data = [
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => !empty($display_name) ? $display_name : $user_login,
            'role' => 'customer'
        ];
        
        wp_update_user($user_data);
        update_user_meta($user_id, 'mobile_number', $mobile);
        
        wp_redirect(add_query_arg('user-created', 'true', $redirect_url));
        exit;
    }

    public function handle_admin_retry_finotex() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'maneli_retry_finotex_nonce')) wp_die('خطای امنیتی!');
        if (!current_user_can('manage_maneli_inquiries') || empty($_POST['inquiry_id'])) { 
            wp_die('درخواست نامعتبر یا دسترسی غیرمجاز.'); 
        }

        $post_id = intval($_POST['inquiry_id']);
        $post_meta = get_post_meta($post_id);
        $issuer_type = $post_meta['issuer_type'][0] ?? 'self';
        $national_code_for_api = ($issuer_type === 'other' && !empty($post_meta['issuer_national_code'][0])) ? $post_meta['issuer_national_code'][0] : $post_meta['national_code'][0];
        
        $finotex_result = $this->execute_finotex_inquiry($national_code_for_api);

        $post_content = "گزارش استعلام از فینوتک (تلاش مجدد توسط مدیر):\n<pre>" . esc_textarea($finotex_result['raw_response']) . "</pre>";
        wp_update_post([
            'ID' => $post_id,
            'post_content' => $post_content,
        ]);

        update_post_meta($post_id, '_finotex_response_data', $finotex_result['data']);

        if ($finotex_result['status'] !== 'DONE' && $finotex_result['status'] !== 'SKIPPED') {
            update_post_meta($post_id, 'inquiry_status', 'failed');
        } else {
             update_post_meta($post_id, 'inquiry_status', 'pending');
        }

        $redirect_url = home_url('/dashboard/?endp=inf_menu_4&inquiry_id=' . $post_id);
        wp_redirect($redirect_url);
        exit;
    }
    
    public function ajax_get_cash_inquiry_details() {
        check_ajax_referer('maneli_cash_inquiry_details_nonce', 'nonce');

        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.'], 403);
        }

        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        if (!$inquiry_id || get_post_type($inquiry_id) !== 'cash_inquiry') {
            wp_send_json_error(['message' => 'شناسه درخواست نامعتبر است.']);
        }

        $product_id = get_post_meta($inquiry_id, 'product_id', true);
        $status = get_post_meta($inquiry_id, 'cash_inquiry_status', true);

        $data = [
            'id' => $inquiry_id,
            'status_label' => Maneli_Admin_Dashboard_Widgets::get_cash_inquiry_status_label($status),
            'status_key' => $status,
            'car' => [
                'name' => get_the_title($product_id),
                'color' => get_post_meta($inquiry_id, 'cash_car_color', true),
            ],
            'customer' => [
                'first_name' => get_post_meta($inquiry_id, 'cash_first_name', true),
                'last_name' => get_post_meta($inquiry_id, 'cash_last_name', true),
                'mobile' => get_post_meta($inquiry_id, 'mobile_number', true),
            ],
            'payment' => [
                'down_payment' => get_post_meta($inquiry_id, 'cash_down_payment', true),
                'rejection_reason' => get_post_meta($inquiry_id, 'cash_rejection_reason', true),
            ]
        ];

        wp_send_json_success($data);
    }
    
    public function ajax_update_cash_inquiry() {
        check_ajax_referer('maneli_cash_inquiry_update_nonce', 'nonce');
    
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.'], 403);
        }
    
        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
        $mobile = isset($_POST['mobile']) ? sanitize_text_field($_POST['mobile']) : '';
        $color = isset($_POST['color']) ? sanitize_text_field($_POST['color']) : '';
    
        if (!$inquiry_id || get_post_type($inquiry_id) !== 'cash_inquiry') {
            wp_send_json_error(['message' => 'شناسه درخواست نامعتبر است.']);
        }
    
        update_post_meta($inquiry_id, 'cash_first_name', $first_name);
        update_post_meta($inquiry_id, 'cash_last_name', $last_name);
        update_post_meta($inquiry_id, 'mobile_number', $mobile);
        update_post_meta($inquiry_id, 'cash_car_color', $color);
    
        wp_send_json_success(['message' => 'درخواست با موفقیت به‌روزرسانی شد.']);
    }
    
    public function ajax_delete_cash_inquiry() {
        check_ajax_referer('maneli_cash_inquiry_delete_nonce', 'nonce');
    
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.'], 403);
        }
    
        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
    
        if (!$inquiry_id || get_post_type($inquiry_id) !== 'cash_inquiry') {
            wp_send_json_error(['message' => 'شناسه درخواست نامعتبر است.']);
        }
    
        if (wp_delete_post($inquiry_id, true)) {
            wp_send_json_success(['message' => 'درخواست با موفقیت حذف شد.']);
        } else {
            wp_send_json_error(['message' => 'خطا در حذف درخواست.']);
        }
    }
    
    public function ajax_set_down_payment() {
        check_ajax_referer('maneli_cash_set_downpayment_nonce', 'nonce');
    
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.'], 403);
        }
    
        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        $amount_raw = isset($_POST['amount']) ? $_POST['amount'] : 0;
        $amount = preg_replace('/[^0-9]/', '', $amount_raw);
        $new_status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';
    
        if (!$inquiry_id || get_post_type($inquiry_id) !== 'cash_inquiry') {
            wp_send_json_error(['message' => 'شناسه درخواست نامعتبر است.']);
        }
        
        $options = get_option('maneli_inquiry_all_options', []);
        $sms_handler = new Maneli_SMS_Handler();
        
        $customer_name = get_post_meta($inquiry_id, 'cash_first_name', true) . ' ' . get_post_meta($inquiry_id, 'cash_last_name', true);
        $customer_mobile = get_post_meta($inquiry_id, 'mobile_number', true);
        $product_id = get_post_meta($inquiry_id, 'product_id', true);
        $car_name = get_the_title($product_id);

        if ($new_status === 'awaiting_payment' && $amount > 0) {
            update_post_meta($inquiry_id, 'cash_down_payment', $amount);
            update_post_meta($inquiry_id, 'cash_inquiry_status', 'awaiting_payment');
            
            $pattern_id = $options['cash_inquiry_approved_pattern'] ?? 0;
            if ($pattern_id > 0 && !empty($customer_mobile)) {
                $params = [(string)$customer_name, (string)$car_name, (string)number_format_i18n($amount)];
                $sms_handler->send_pattern($pattern_id, $customer_mobile, $params);
            }
            
        } elseif ($new_status === 'rejected') {
            update_post_meta($inquiry_id, 'cash_rejection_reason', $reason);
            update_post_meta($inquiry_id, 'cash_inquiry_status', 'rejected');
            
            $pattern_id = $options['cash_inquiry_rejected_pattern'] ?? 0;
            if ($pattern_id > 0 && !empty($customer_mobile)) {
                $params = [(string)$customer_name, (string)$car_name, (string)$reason];
                $sms_handler->send_pattern($pattern_id, $customer_mobile, $params);
            }

        } else {
            wp_send_json_error(['message' => 'اطلاعات ارسالی نامعتبر است.']);
        }
    
        wp_send_json_success(['message' => 'وضعیت با موفقیت تغییر کرد.']);
    }
    
    public function handle_start_cash_payment() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'maneli_start_cash_payment_nonce')) wp_die('خطای امنیتی!');
        if (!is_user_logged_in()) wp_redirect(home_url());

        $user_id = get_current_user_id();
        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        
        if (get_post_field('post_author', $inquiry_id) != $user_id) wp_die('خطای دسترسی!');

        $amount_toman = (int)get_post_meta($inquiry_id, 'cash_down_payment', true);
        if ($amount_toman <= 0) wp_die('مبلغ پیش‌پرداخت مشخص نشده است.');

        update_user_meta($user_id, 'maneli_payment_type', 'cash_down_payment');
        update_user_meta($user_id, 'maneli_payment_cash_inquiry_id', $inquiry_id);

        $options = get_option('maneli_inquiry_all_options', []);
        $zarinpal_enabled = isset($options['zarinpal_enabled']) && $options['zarinpal_enabled'] == '1';
        $sadad_enabled = isset($options['sadad_enabled']) && $options['sadad_enabled'] == '1';
        $active_gateway = $options['active_gateway'] ?? 'zarinpal';
        $order_id = time() . $user_id;

        update_user_meta($user_id, 'maneli_payment_order_id', $order_id);
        update_user_meta($user_id, 'maneli_payment_amount', $amount_toman);

        if ($active_gateway === 'sadad' && $sadad_enabled) {
            $this->process_sadad_payment($user_id, $order_id, $amount_toman, $options);
        } elseif ($active_gateway === 'zarinpal' && $zarinpal_enabled) {
            $this->process_zarinpal_payment($user_id, $order_id, $amount_toman, $options);
        } else {
            if ($zarinpal_enabled) {
                 $this->process_zarinpal_payment($user_id, $order_id, $amount_toman, $options);
            } elseif ($sadad_enabled) {
                 $this->process_sadad_payment($user_id, $order_id, $amount_toman, $options);
            } else {
                wp_die('در حال حاضر هیچ درگاه پرداخت فعالی در تنظیمات وجود ندارد.');
            }
        }
    }
}