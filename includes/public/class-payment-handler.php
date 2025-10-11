<?php
if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Payment_Handler {

    public function __construct() {
        add_action('template_redirect', [$this, 'handle_payment_verification']);
        add_action('admin_post_nopriv_maneli_start_payment', '__return_false');
        add_action('admin_post_maneli_start_payment', [$this, 'handle_payment_submission']);
        add_action('admin_post_maneli_start_cash_payment', [$this, 'handle_start_cash_payment']);
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
            // Since this is payment handler, we need access to finalize_inquiry.
            // A better approach would be using hooks, but for now, we instantiate the class.
            $inquiry_handler = new Maneli_Installment_Inquiry_Handler();
            $inquiry_handler->finalize_inquiry($user_id, true);
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
            $redirect_url = home_url('/dashboard/?endp=inf_menu_4');
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
                        $inquiry_handler = new Maneli_Installment_Inquiry_Handler();
                        $inquiry_handler->finalize_inquiry($user_id, true);
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
            $redirect_url = home_url('/dashboard/?endp=inf_menu_4');
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
                        $inquiry_handler = new Maneli_Installment_Inquiry_Handler();
                        $inquiry_handler->finalize_inquiry($user_id, true);
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
}