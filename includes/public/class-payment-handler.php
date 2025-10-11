<?php
/**
 * Handles all payment-related logic, including submission to gateways and verification of callbacks.
 *
 * تابع sadad_call_api برای استفاده از wp_remote_post به جای cURL خام بازنویسی شده است.
 *
 * @package Maneli_Car_Inquiry/Includes/Public
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.6 (Sadad API fix - Added OpenSSL check)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Payment_Handler {

    public function __construct() {
        add_action('template_redirect', [$this, 'handle_payment_verification']);
        
        // Hooks for starting payment processes.
        add_action('admin_post_nopriv_maneli_start_payment', '__return_false');
        add_action('admin_post_maneli_start_payment', [$this, 'handle_inquiry_fee_submission']);
        
        add_action('admin_post_nopriv_maneli_start_cash_payment', '__return_false');
        add_action('admin_post_maneli_start_cash_payment', [$this, 'handle_cash_down_payment_submission']);
    }

    /**
     * Handles the submission for the initial inquiry fee.
     */
    public function handle_inquiry_fee_submission() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'maneli_payment_nonce')) {
            wp_die(esc_html__('Security check failed!', 'maneli-car-inquiry'));
        }
        if (!is_user_logged_in()) {
            wp_redirect(home_url());
            exit;
        }

        $user_id = get_current_user_id();
        $options = get_option('maneli_inquiry_all_options', []);
        $amount_toman = (int)($options['inquiry_fee'] ?? 0);
        $discount_code = $options['discount_code'] ?? '';
        $submitted_code = isset($_POST['discount_code_input']) ? trim(sanitize_text_field($_POST['discount_code_input'])) : '';

        // Apply discount if applicable
        if (!empty($discount_code) && !empty($submitted_code) && $submitted_code === $discount_code) {
            $amount_toman = 0;
            update_user_meta($user_id, 'maneli_discount_applied', 'yes');
        }

        // If amount is zero, skip payment and finalize the inquiry
        if ($amount_toman <= 0) {
            do_action('maneli_inquiry_payment_successful', $user_id);
            wp_redirect(home_url('/dashboard/?endp=inf_menu_1'));
            exit;
        }

        // Set payment type for standard inquiry
        update_user_meta($user_id, 'maneli_payment_type', 'inquiry_fee');
        $this->initiate_payment_gateway($user_id, $amount_toman);
    }

    /**
     * Handles the submission for the cash inquiry down payment.
     */
    public function handle_cash_down_payment_submission() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'maneli_start_cash_payment_nonce')) {
            wp_die(esc_html__('Security check failed!', 'maneli-car-inquiry'));
        }
        if (!is_user_logged_in()) {
            wp_redirect(home_url());
            exit;
        }

        $user_id = get_current_user_id();
        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;

        if (!$inquiry_id || get_post_field('post_author', $inquiry_id) != $user_id) {
            wp_die(esc_html__('Access Denied: You are not the owner of this inquiry.', 'maneli-car-inquiry'));
        }

        $amount_toman = (int)get_post_meta($inquiry_id, 'cash_down_payment', true);
        if ($amount_toman <= 0) {
            wp_die(esc_html__('Down payment amount has not been specified.', 'maneli-car-inquiry'));
        }

        // Set payment type for cash inquiry
        update_user_meta($user_id, 'maneli_payment_type', 'cash_down_payment');
        update_user_meta($user_id, 'maneli_payment_cash_inquiry_id', $inquiry_id);

        $this->initiate_payment_gateway($user_id, $amount_toman);
    }

    /**
     * Centralized function to select and start the payment process with the active gateway.
     *
     * @param int $user_id      User's ID.
     * @param int $amount_toman Amount in Toman.
     */
    private function initiate_payment_gateway($user_id, $amount_toman) {
        $options = get_option('maneli_inquiry_all_options', []);
        $active_gateway = $options['active_gateway'] ?? 'zarinpal';
        $order_id = time() . '-' . $user_id;

        update_user_meta($user_id, 'maneli_payment_order_id', $order_id);
        update_user_meta($user_id, 'maneli_payment_amount', $amount_toman);

        $gateway_map = [
            'sadad'    => 'process_sadad_payment',
            'zarinpal' => 'process_zarinpal_payment',
        ];

        $is_gateway_available = function($gateway) use ($options) {
            return isset($options[$gateway . '_enabled']) && $options[$gateway . '_enabled'] == '1';
        };

        if (isset($gateway_map[$active_gateway]) && $is_gateway_available($active_gateway)) {
            $this->{$gateway_map[$active_gateway]}($user_id, $order_id, $amount_toman, $options);
        } else {
            // Fallback to the first available gateway if the active one is disabled
            foreach ($gateway_map as $gateway => $method) {
                if ($is_gateway_available($gateway)) {
                    $this->{$method}($user_id, $order_id, $amount_toman, $options);
                    return;
                }
            }
            wp_die(esc_html__('No active payment gateway is configured in the settings.', 'maneli-car-inquiry'));
        }
    }

    /**
     * Handles the callback from payment gateways to verify transactions.
     */
    public function handle_payment_verification() {
        if (!isset($_GET['maneli_payment_verify'])) {
            return;
        }

        $gateway = sanitize_key($_GET['maneli_payment_verify']);

        switch ($gateway) {
            case 'zarinpal':
                $this->verify_zarinpal_payment();
                break;
            case 'sadad':
                $this->verify_sadad_payment();
                break;
        }
    }

    /**
     * Processes payment request via Zarinpal.
     */
    private function process_zarinpal_payment($user_id, $order_id, $amount_toman, $options) {
        $merchant_id = $options['zarinpal_merchant_code'] ?? '';
        if (empty($merchant_id)) {
            wp_die(esc_html__('Zarinpal Merchant ID is not configured.', 'maneli-car-inquiry'));
        }

        $user_info = get_userdata($user_id);
        $description = sprintf(esc_html__('Payment for order ID %s', 'maneli-car-inquiry'), $order_id);
        $callback_url = home_url('/?maneli_payment_verify=zarinpal&uid=' . $user_id);

        $data = [
            'merchant_id'  => $merchant_id,
            'amount'       => $amount_toman * 10, // Amount in Rials
            'description'  => $description,
            'callback_url' => $callback_url,
            'metadata'     => [
                'email'    => $user_info->user_email,
                'order_id' => $order_id,
            ],
        ];

        // Using MANELI_ZARINPAL_REQUEST_URL constant
        $request_url = defined('MANELI_ZARINPAL_REQUEST_URL') ? MANELI_ZARINPAL_REQUEST_URL : 'https://api.zarinpal.com/pg/v4/payment/request.json';
        $response = wp_remote_post($request_url, [
            'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
            'body'    => json_encode($data),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            wp_die(esc_html__('Could not connect to Zarinpal gateway.', 'maneli-car-inquiry'));
        }

        $result = json_decode(wp_remote_retrieve_body($response), true);

        if (!empty($result['data']) && !empty($result['data']['authority']) && $result['data']['code'] == 100) {
            update_user_meta($user_id, 'maneli_payment_authority', $result['data']['authority']);
            
            // Using MANELI_ZARINPAL_STARTPAY_URL constant
            $startpay_url = defined('MANELI_ZARINPAL_STARTPAY_URL') ? MANELI_ZARINPAL_STARTPAY_URL : 'https://www.zarinpal.com/pg/StartPay/';
            wp_redirect($startpay_url . $result['data']['authority']);
            exit;
        } else {
            $error_message = $result['errors']['message'] ?? esc_html__('An unknown error occurred.', 'maneli-car-inquiry');
            wp_die(sprintf(esc_html__('Error from Zarinpal: %s', 'maneli-car-inquiry'), $error_message));
        }
    }

    /**
     * Verifies a Zarinpal transaction.
     */
    private function verify_zarinpal_payment() {
        if (empty($_GET['Authority']) || empty($_GET['Status']) || empty($_GET['uid'])) {
            return;
        }

        $authority = sanitize_text_field($_GET['Authority']);
        $status = sanitize_text_field($_GET['Status']);
        $user_id = intval($_GET['uid']);
        $current_user_id = get_current_user_id();

        $options = get_option('maneli_inquiry_all_options', []);
        $merchant_id = $options['zarinpal_merchant_code'] ?? '';
        $amount = get_user_meta($user_id, 'maneli_payment_amount', true);
        $saved_authority = get_user_meta($user_id, 'maneli_payment_authority', true);
        $payment_type = get_user_meta($user_id, 'maneli_payment_type', true);
        
        $redirect_url = ($payment_type === 'cash_down_payment')
            ? home_url('/dashboard/?endp=inf_menu_4')
            : home_url('/dashboard/?endp=inf_menu_1');

        // SECURITY CHECK: Ensure the current logged-in user matches the transaction user, if logged in.
        if (is_user_logged_in() && $current_user_id !== $user_id) {
             $this->finalize_and_redirect($current_user_id, $redirect_url, 'failed', esc_html__('Security check failed. Transaction user mismatch.', 'maneli-car-inquiry'));
             return;
        }

        if ($authority !== $saved_authority) {
            $this->finalize_and_redirect($user_id, $redirect_url, 'failed', esc_html__('Transaction details mismatch.', 'maneli-car-inquiry'));
            return;
        }

        if ($status === 'OK') {
            $data = [
                'merchant_id' => $merchant_id,
                'authority'   => $authority,
                'amount'      => (int)$amount * 10,
            ];

            // Using MANELI_ZARINPAL_VERIFY_URL constant
            $verify_url = defined('MANELI_ZARINPAL_VERIFY_URL') ? MANELI_ZARINPAL_VERIFY_URL : 'https://api.zarinpal.com/pg/v4/payment/verify.json';
            $response = wp_remote_post($verify_url, [
                'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'body'    => json_encode($data),
                'timeout' => 30,
            ]);

            if (is_wp_error($response)) {
                $this->finalize_and_redirect($user_id, $redirect_url, 'failed', esc_html__('Error communicating with payment gateway for verification.', 'maneli-car-inquiry'));
                return;
            }
            
            $result = json_decode(wp_remote_retrieve_body($response), true);

            if (!empty($result['data']) && in_array($result['data']['code'], [100, 101])) {
                // Payment successful, trigger finalization hooks
                if ($payment_type === 'cash_down_payment') {
                    $inquiry_id = get_user_meta($user_id, 'maneli_payment_cash_inquiry_id', true);
                    if ($inquiry_id) {
                        do_action('maneli_cash_inquiry_payment_successful', $user_id, $inquiry_id);
                    }
                } else {
                    do_action('maneli_inquiry_payment_successful', $user_id);
                }
                $this->finalize_and_redirect($user_id, $redirect_url, 'success');
            } else {
                $error_message = $result['errors']['message'] ?? esc_html__('Transaction not confirmed by gateway.', 'maneli-car-inquiry');
                $this->finalize_and_redirect($user_id, $redirect_url, 'failed', $error_message);
            }
        } else {
            $this->finalize_and_redirect($user_id, $redirect_url, 'cancelled');
        }
    }

    /**
     * Processes payment request via Sadad.
     */
    private function process_sadad_payment($user_id, $order_id, $amount_toman, $options) {
        $merchant_id = $options['sadad_merchant_id'] ?? '';
        $terminal_id = $options['sadad_terminal_id'] ?? '';
        $terminal_key = $options['sadad_key'] ?? '';
        if (empty($merchant_id) || empty($terminal_id) || empty($terminal_key)) {
            wp_die(esc_html__('Sadad payment gateway information is incomplete in settings.', 'maneli-car-inquiry'));
        }

        $amount_rial = $amount_toman * 10;
        if ($amount_rial < 1000) { // Sadad minimum amount
            $amount_rial = 1000;
        }
        
        $sign_data = $this->sadad_encrypt_pkcs7("$terminal_id;$order_id;$amount_rial", $terminal_key);
        
        // FIX: Handle OpenSSL check result before proceeding
        if ($sign_data === 'OPENSSL_NOT_AVAILABLE') {
            wp_die(esc_html__('OpenSSL PHP extension is not enabled. Sadad payment gateway requires this extension.', 'maneli-car-inquiry'));
        }

        $data = [
            'TerminalId'    => $terminal_id,
            'MerchantId'    => $merchant_id,
            'Amount'        => $amount_rial,
            'SignData'      => $sign_data,
            'ReturnUrl'     => home_url('/?maneli_payment_verify=sadad'),
            'LocalDateTime' => date("m/d/Y g:i:s a"),
            'OrderId'       => $order_id
        ];

        // Using MANELI_SADAD_REQUEST_URL constant
        $request_url = defined('MANELI_SADAD_REQUEST_URL') ? MANELI_SADAD_REQUEST_URL : 'https://sadad.shaparak.ir/vpg/api/v0/Request/PaymentRequest';
        $result = $this->sadad_call_api($request_url, $data);

        if ($result && isset($result->ResCode) && $result->ResCode == 0) {
            update_user_meta($user_id, 'maneli_payment_token', $result->Token);
            
            // Using MANELI_SADAD_PURCHASE_URL constant
            $purchase_url = defined('MANELI_SADAD_PURCHASE_URL') ? MANELI_SADAD_PURCHASE_URL : 'https://sadad.shaparak.ir/VPG/Purchase?Token=';
            wp_redirect($purchase_url . $result->Token);
            exit;
        } else {
            $error_message = $result->Description ?? esc_html__('Unknown error during token generation.', 'maneli-car-inquiry');
            wp_die(sprintf(esc_html__('Sadad Gateway Error: %s', 'maneli-car-inquiry'), $error_message));
        }
    }

    private function verify_sadad_payment() {
        if (empty($_POST["OrderId"]) || !isset($_POST["ResCode"])) {
            return;
        }
    
        $order_id = sanitize_text_field($_POST["OrderId"]);
        $res_code = sanitize_text_field($_POST["ResCode"]);
        $user_id_parts = explode('-', $order_id);
        $user_id = intval(end($user_id_parts));
        $current_user_id = get_current_user_id();
        $payment_type = get_user_meta($user_id, 'maneli_payment_type', true);
    
        $redirect_url = ($payment_type === 'cash_down_payment')
            ? home_url('/dashboard/?endp=inf_menu_4')
            : home_url('/dashboard/?endp=inf_menu_1');
        
        // SECURITY CHECK: Ensure the current logged-in user matches the transaction user, if logged in.
        if (is_user_logged_in() && $current_user_id !== $user_id) {
             $this->finalize_and_redirect($current_user_id, $redirect_url, 'failed', esc_html__('Security check failed. Transaction user mismatch.', 'maneli-car-inquiry'));
             return;
        }
    
        if ($res_code == 0) {
            if (empty($_POST["token"])) {
                $this->finalize_and_redirect($user_id, $redirect_url, 'failed', esc_html__('Invalid token returned from the bank.', 'maneli-car-inquiry'));
            } else {
                $token = sanitize_text_field($_POST["token"]);
                $options = get_option('maneli_inquiry_all_options', []);
                $terminal_key = $options['sadad_key'] ?? '';
                $verify_data = [
                    'Token'    => $token,
                    'SignData' => $this->sadad_encrypt_pkcs7($token, $terminal_key)
                ];
                
                // Using MANELI_SADAD_VERIFY_URL constant
                $verify_url = defined('MANELI_SADAD_VERIFY_URL') ? MANELI_SADAD_VERIFY_URL : 'https://sadad.shaparak.ir/vpg/api/v0/Advice/Verify';
                $result = $this->sadad_call_api($verify_url, $verify_data);
    
                if ($result && isset($result->ResCode) && $result->ResCode == 0) {
                    if ($payment_type === 'cash_down_payment') {
                        $inquiry_id = get_user_meta($user_id, 'maneli_payment_cash_inquiry_id', true);
                        if ($inquiry_id) {
                            do_action('maneli_cash_inquiry_payment_successful', $user_id, $inquiry_id);
                        }
                    } else {
                        do_action('maneli_inquiry_payment_successful', $user_id);
                    }
                    $this->finalize_and_redirect($user_id, $redirect_url, 'success');
                } else {
                    $error_message = $result->Description ?? esc_html__('Transaction failed at the final verification step.', 'maneli-car-inquiry');
                    $this->finalize_and_redirect($user_id, $redirect_url, 'failed', $error_message);
                }
            }
        } else {
            $error_message = isset($_POST['Description']) ? sanitize_text_field($_POST['Description']) : esc_html__('Transaction was canceled by the bank.', 'maneli-car-inquiry');
            $this->finalize_and_redirect($user_id, $redirect_url, 'failed', $error_message);
        }
    }
    
    /**
     * Cleans up user meta, builds the redirect URL, and performs the redirect.
     */
    private function finalize_and_redirect($user_id, $redirect_url, $status, $reason = '') {
        // Clean up all temporary payment-related user meta
        delete_user_meta($user_id, 'maneli_payment_order_id');
        delete_user_meta($user_id, 'maneli_payment_amount');
        delete_user_meta($user_id, 'maneli_payment_authority'); // Zarinpal
        delete_user_meta($user_id, 'maneli_payment_token');      // Sadad
        delete_user_meta($user_id, 'maneli_payment_type');
        delete_user_meta($user_id, 'maneli_payment_cash_inquiry_id');
        
        // Build the redirect URL with status
        $redirect_url = add_query_arg('payment_status', $status, $redirect_url);
        if (!empty($reason)) {
            $redirect_url = add_query_arg('reason', urlencode($reason), $redirect_url);
        }

        // Redirect if headers are not already sent
        if (!headers_sent()) {
            wp_redirect($redirect_url);
            exit;
        }
    }
    
    /**
     * Sadad API helper: Encrypts data using DES-EDE3 for signing.
     */
    private function sadad_encrypt_pkcs7($str, $key) {
        // FIX: Check for OpenSSL extension availability
        if (!extension_loaded('openssl')) {
            error_log('Maneli Sadad Error: The OpenSSL extension is required for Sadad payment gateway but is not enabled.');
            // Return a distinct string to be handled in process_sadad_payment
            return 'OPENSSL_NOT_AVAILABLE'; 
        }

        $key = base64_decode($key);
        $ciphertext = openssl_encrypt($str, "DES-EDE3", $key, OPENSSL_RAW_DATA);
        return base64_encode($ciphertext);
    }

    /**
     * FIX: Sadad API helper: Makes a request to the API using wp_remote_post.
     * This replaces the use of cURL with the standard WordPress HTTP API and removes redundant conditional logic.
     */
    private function sadad_call_api($url, $data = false) {
        $args = [
            'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
            'timeout' => 30,
        ];
        
        // The body is encoded only if data is present.
        if ($data) {
            $args['body'] = json_encode($data);
        }
        
        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
             error_log('Maneli Sadad API Error: ' . $response->get_error_message());
             return false;
        }
        
        $result = wp_remote_retrieve_body($response);
        return !empty($result) ? json_decode($result) : false;
    }
}