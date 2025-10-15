<?php
/**
 * Handles the multi-step process for creating an installment inquiry for a customer.
 * This class now listens for hooks to finalize inquiries, decoupling it from the payment handler.
 *
 * @package Maneli_Car_Inquiry/Includes/Public
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.5 (Refactored: Removed redundant loan calculation, using centralized helper)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Installment_Inquiry_Handler {

    public function __construct() {
        // Step 1: Handle car selection from the product page calculator.
        add_action('wp_ajax_maneli_select_car_ajax', [$this, 'handle_car_selection_ajax']);

        // Step 2: Handle identity form submission.
        add_action('admin_post_nopriv_maneli_submit_identity', '__return_false');
        add_action('admin_post_maneli_submit_identity', [$this, 'handle_identity_submission']);

        // Step 3 (Implicit): Listens for a successful payment hook to finalize the installment inquiry.
        add_action('maneli_inquiry_payment_successful', [$this, 'finalize_inquiry_from_hook']);
        
        // Bonus: Also listens for cash payment success to update its status. This keeps concerns separate.
        add_action('maneli_cash_inquiry_payment_successful', [$this, 'finalize_cash_inquiry_from_hook'], 10, 2);

        // Handle re-try logic for failed inquiries.
        add_action('admin_post_maneli_retry_inquiry', [$this, 'handle_inquiry_retry']);
    }
    
    // =======================================================
    //  DECRYPTION HELPERS
    // =======================================================
    
    /**
     * Retrieves a unique, site-specific key for encryption, ensuring it's 32 bytes long.
     * @return string The encryption key.
     */
    private function get_encryption_key() {
        // Use a unique, secure key from wp-config.php
        $key = defined('AUTH_KEY') ? AUTH_KEY : NONCE_KEY;
        // Generate a 32-byte key from the security constant using SHA-256 for openssl_encrypt
        return hash('sha256', $key, true); 
    }

    /**
     * Decrypts data using AES-256-CBC.
     * @param string $encrypted_data The encrypted data (Base64 encoded).
     * @return string The decrypted data or empty string on failure.
     */
    private function decrypt_data($encrypted_data) {
        if (empty($encrypted_data)) {
            return '';
        }
        $key = $this->get_encryption_key();
        $cipher = 'aes-256-cbc';
        
        // Decode and separate IV and encrypted data
        $parts = explode('::', base64_decode($encrypted_data), 2);
        
        if (count($parts) !== 2) {
            return ''; // Invalid format or decryption failed
        }
        $encrypted = $parts[0];
        $iv = $parts[1];
        
        // Basic check for IV length
        if (strlen($iv) !== openssl_cipher_iv_length($cipher)) {
            return '';
        }

        // Decrypt
        $decrypted = openssl_decrypt($encrypted, $cipher, $key, 0, $iv);
        
        return $decrypted === false ? '' : $decrypted;
    }

    /**
     * AJAX handler for Step 1: Saving the selected car and calculator data to user meta.
     * It recalculates the installment on the server to prevent client-side tampering.
     */
    public function handle_car_selection_ajax() {
        check_ajax_referer('maneli_ajax_nonce', 'nonce');

        if (!is_user_logged_in() || empty($_POST['product_id'])) {
            wp_send_json_error(['message' => esc_html__('Invalid request. Please log in and try again.', 'maneli-car-inquiry')]);
        }

        $user_id = get_current_user_id();
        
        // Validate and re-calculate installment here using the *server-side* configured rate
        $total_price = sanitize_text_field($_POST['total_price'] ?? 0);
        $down_payment = sanitize_text_field($_POST['down_payment'] ?? 0);
        $term_months = (int)sanitize_text_field($_POST['term_months'] ?? 12);
        $loan_amount = (int)$total_price - (int)$down_payment;
        
        // USE CENTRALIZED HELPER
        $recalculated_installment = Maneli_Render_Helpers::calculate_installment_amount($loan_amount, $term_months);
        
        $meta_to_save = [
            'maneli_selected_car_id'      => intval($_POST['product_id']),
            'maneli_inquiry_step'         => 'form_pending',
            'maneli_inquiry_down_payment' => $down_payment,
            'maneli_inquiry_term_months'  => $term_months,
            'maneli_inquiry_total_price'  => $total_price,
            'maneli_inquiry_installment'  => $recalculated_installment, // Use server-calculated value
        ];

        foreach ($meta_to_save as $key => $value) {
            update_user_meta($user_id, $key, $value);
        }

        wp_send_json_success(['message' => esc_html__('Car selected. Redirecting...', 'maneli-car-inquiry')]);
    }

    /**
     * Handler for Step 2: Processing the identity form submission.
     */
    public function handle_identity_submission() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'maneli_submit_identity_nonce')) {
            wp_die(esc_html__('Security check failed!', 'maneli-car-inquiry'));
        }
        if (!is_user_logged_in()) {
            wp_redirect(home_url());
            exit;
        }

        $user_id = get_current_user_id();
        
        $required_fields = ['first_name', 'last_name', 'national_code', 'mobile_number'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_die(sprintf(esc_html__('Error: The field "%s" is required.', 'maneli-car-inquiry'), $field));
            }
        }
        
        $buyer_fields = [
            'first_name', 'last_name', 'father_name', 'national_code', 'occupation', 
            'income_level', 'mobile_number', 'phone_number', 'residency_status', 
            'workplace_status', 'address', 'birth_date', 'bank_name', 'account_number',
            'branch_code', 'branch_name'
        ];
        $buyer_data = [];
        foreach ($buyer_fields as $key) {
            $buyer_data[$key] = isset($_POST[$key]) ? sanitize_text_field($_POST[$key]) : '';
        }

        $issuer_type = isset($_POST['issuer_type']) ? sanitize_key($_POST['issuer_type']) : 'self';
        $issuer_data = [];
        if ($issuer_type === 'other') {
            $issuer_fields = [
                'issuer_full_name', 'issuer_national_code', 'issuer_bank_name', 'issuer_account_number',
                'issuer_branch_code', 'issuer_branch_name', 'issuer_residency_status', 
                'issuer_workplace_status', 'issuer_address', 'issuer_phone_number', 'issuer_father_name',
                'issuer_occupation'
            ];
            foreach ($issuer_fields as $key) {
                // Address can be multi-line
                $issuer_data[$key] = isset($_POST[$key]) ? sanitize_textarea_field($_POST[$key]) : '';
            }
        }

        // Store sanitized data temporarily, waiting for payment (if any)
        update_user_meta($user_id, 'maneli_temp_inquiry_data', [
            'buyer_data'  => $buyer_data,
            'issuer_data' => $issuer_data,
            'issuer_type' => $issuer_type,
        ]);

        $options = get_option('maneli_inquiry_all_options', []);
        $payment_enabled = !empty($options['payment_enabled']) && $options['payment_enabled'] == '1';
        $inquiry_fee = !empty($options['inquiry_fee']) ? (int)$options['inquiry_fee'] : 0;

        if ($payment_enabled && $inquiry_fee > 0) {
            update_user_meta($user_id, 'maneli_inquiry_step', 'payment_pending');
        } else {
            // No payment needed, finalize the inquiry right away by triggering the success hook
            do_action('maneli_inquiry_payment_successful', $user_id);
        }

        wp_redirect(home_url('/dashboard/?endp=inf_menu_1'));
        exit;
    }
    
    /**
     * Callback for the hook `maneli_inquiry_payment_successful`.
     * This is the decoupled entry point for finalizing an installment inquiry.
     * @param int $user_id The user ID passed from the hook.
     */
    public function finalize_inquiry_from_hook($user_id) {
        $this->finalize_inquiry($user_id, true);
    }
    
    /**
     * Callback for the hook `maneli_cash_inquiry_payment_successful`.
     * This updates the status of a cash inquiry post after successful payment.
     * @param int $user_id The user ID.
     * @param int $inquiry_id The cash inquiry post ID.
     */
    public function finalize_cash_inquiry_from_hook($user_id, $inquiry_id) {
        if ($inquiry_id && get_post_type($inquiry_id) === 'cash_inquiry') {
            update_post_meta($inquiry_id, 'cash_inquiry_status', 'completed');
        }
    }

    /**
     * Finalizes the inquiry process by creating the post, calling APIs, and sending notifications.
     *
     * @param int  $user_id The ID of the user.
     * @param bool $is_new  Whether this is a brand new inquiry (for notification purposes).
     * @return bool True on success, false on failure.
     */
    public function finalize_inquiry($user_id, $is_new = false) {
        $temp_data = get_user_meta($user_id, 'maneli_temp_inquiry_data', true);
        if (empty($temp_data)) {
            return false;
        }

        $buyer_data = $temp_data['buyer_data'];
        $issuer_data = $temp_data['issuer_data'];
        $issuer_type = $temp_data['issuer_type'];

        // Update user profile with submitted data
        wp_update_user(['ID' => $user_id, 'first_name' => $buyer_data['first_name'], 'last_name' => $buyer_data['last_name']]);
        foreach ($buyer_data as $key => $value) {
            update_user_meta($user_id, $key, $value);
        }

        $national_code_for_api = ($issuer_type === 'other' && !empty($issuer_data['issuer_national_code']))
            ? $issuer_data['issuer_national_code']
            : $buyer_data['national_code'];

        $finotex_result = $this->execute_finotex_inquiry($national_code_for_api);

        $car_id = get_user_meta($user_id, 'maneli_selected_car_id', true);
        $post_title = sprintf(
            '%s: %s - %s',
            esc_html__('Inquiry for', 'maneli-car-inquiry'),
            get_the_title($car_id),
            $buyer_data['first_name'] . ' ' . $buyer_data['last_name']
        );
        
        $post_id = wp_insert_post([
            'post_title'   => $post_title,
            'post_content' => "Finotex API raw response:\n<pre>" . esc_textarea($finotex_result['raw_response']) . "</pre>",
            'post_status'  => 'publish',
            'post_author'  => $user_id,
            'post_type'    => 'inquiry'
        ]);

        if (is_wp_error($post_id)) {
            error_log('Maneli Inquiry Error: Failed to insert post. ' . $post_id->get_error_message());
            return false;
        }

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
        
        // Ensure the installment amount is calculated with the current server-side rate and is saved to the post meta.
        $down_payment = get_user_meta($user_id, 'maneli_inquiry_down_payment', true);
        $total_price = get_user_meta($user_id, 'maneli_inquiry_total_price', true);
        $term_months = get_user_meta($user_id, 'maneli_inquiry_term_months', true);
        
        $loan_amount = (int)$total_price - (int)$down_payment;
        // USE CENTRALIZED HELPER
        $recalculated_installment = Maneli_Render_Helpers::calculate_installment_amount($loan_amount, (int)$term_months);
        
        $calculator_meta_keys = [
            'maneli_inquiry_down_payment', 
            'maneli_inquiry_term_months', 
            'maneli_inquiry_total_price'
        ];
        
        foreach($calculator_meta_keys as $key) {
            $value = get_user_meta($user_id, $key, true);
            if ($value) { update_post_meta($post_id, $key, $value); }
        }
        
        // IMPORTANT: Overwrite/Save the final, server-calculated installment amount
        update_post_meta($post_id, 'maneli_inquiry_installment', $recalculated_installment); 
        
        if ($is_new && $initial_status === 'pending') {
            $this->send_new_inquiry_notifications($buyer_data, $car_id);
        }
        
        $this->cleanup_user_meta($user_id, $calculator_meta_keys);
        
        return true;
    }

    /**
     * Executes the Finotex API call to get cheque status.
     * @param string $national_code The national ID code to check.
     * @return array An array containing the status, data, and raw response.
     */
    public function execute_finotex_inquiry($national_code) {
        $options = get_option('maneli_inquiry_all_options', []);
        
        if (empty($options['finotex_enabled']) || $options['finotex_enabled'] !== '1' || !defined('MANELI_FINOTEX_API_URL')) {
            return ['status' => 'SKIPPED', 'data' => null, 'raw_response' => 'Finotex inquiry is disabled in settings or API constant is missing.'];
        }

        // === START: SECURITY IMPROVEMENT - Check for constants first, then decrypt ===
        $client_id_raw = $options['finotex_username'] ?? '';
        $api_key_raw = $options['finotex_password'] ?? '';
        
        $client_id = defined('MANELI_FINOTEX_CLIENT_ID') ? MANELI_FINOTEX_CLIENT_ID : $this->decrypt_data($client_id_raw);
        $api_key = defined('MANELI_FINOTEX_API_KEY') ? MANELI_FINOTEX_API_KEY : $this->decrypt_data($api_key_raw);
        // === END: SECURITY IMPROVEMENT ===
        
        $result = ['status' => 'FAILED', 'data' => null, 'raw_response' => ''];

        if (empty($client_id) || empty($api_key)) {
            $result['raw_response'] = 'Plugin Error: Finotex Client ID or API Key not set (missing required credentials/encrypted data).';
            return $result;
        }

        // FIX: Using MANELI_FINOTEX_API_URL constant
        $api_url = sprintf(MANELI_FINOTEX_API_URL, $client_id);
        
        $track_id = 'maneli_' . uniqid();
        $api_url_with_params = add_query_arg(['idCode' => $national_code, 'trackId' => $track_id], $api_url);
        
        $response = wp_remote_get($api_url_with_params, [
            'headers' => ['Authorization' => 'Bearer ' . $api_key, 'Accept' => 'application/json'],
            'timeout' => 45,
        ]);

        if (is_wp_error($response)) {
            $result['raw_response'] = 'WordPress Connection Error: ' . $response->get_error_message();
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

    /**
     * Handles the user's request to retry a failed inquiry.
     */
    public function handle_inquiry_retry() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'maneli_retry_inquiry_nonce')) {
            wp_die(esc_html__('Security check failed!', 'maneli-car-inquiry'));
        }
        if (!is_user_logged_in() || empty($_POST['inquiry_id'])) {
            wp_die(esc_html__('Invalid request.', 'maneli-car-inquiry'));
        }
        
        $user_id = get_current_user_id();
        $post_id = intval($_POST['inquiry_id']);

        if (get_post_field('post_author', $post_id) != $user_id) {
            wp_die(esc_html__('Access denied.', 'maneli-car-inquiry'));
        }

        wp_delete_post($post_id, true);
        update_user_meta($user_id, 'maneli_inquiry_step', 'form_pending');
        
        wp_redirect(home_url('/dashboard/?endp=inf_menu_1'));
        exit;
    }

    /**
     * Sends notifications to admin and customer for a new inquiry.
     */
    private function send_new_inquiry_notifications($buyer_data, $car_id) {
        $sms_handler = new Maneli_SMS_Handler();
        $customer_name = ($buyer_data['first_name'] ?? '') . ' ' . ($buyer_data['last_name'] ?? '');
        $car_name = get_the_title($car_id) ?? '';
        $options = get_option('maneli_inquiry_all_options', []);

        // Notify Admin
        $admin_mobile = $options['admin_notification_mobile'] ?? '';
        $pattern_admin = $options['sms_pattern_new_inquiry'] ?? 0;
        if (!empty($admin_mobile) && $pattern_admin > 0) {
            $sms_handler->send_pattern($pattern_admin, $admin_mobile, [$customer_name, $car_name]);
        }

        // Notify Customer
        $customer_mobile = $buyer_data['mobile_number'] ?? '';
        $pattern_customer = $options['sms_pattern_pending'] ?? 0;
        if (!empty($customer_mobile) && $pattern_customer > 0) {
            $sms_handler->send_pattern($pattern_customer, $customer_mobile, [$customer_name, $car_name]);
        }
    }
    
    /**
     * Cleans up all temporary meta fields from the user's profile after inquiry creation.
     */
    private function cleanup_user_meta($user_id, $calculator_meta_keys) {
        $keys_to_delete = array_merge(
            $calculator_meta_keys,
            ['maneli_inquiry_step', 'maneli_selected_car_id', 'maneli_temp_inquiry_data']
        );
        foreach ($keys_to_delete as $key) {
            delete_user_meta($user_id, $key);
        }
    }
}