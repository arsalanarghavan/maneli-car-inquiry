<?php
/**
 * Handles all Finnotech API calls for credit inquiries.
 * 
 * @package Maneli_Car_Inquiry/Includes/Public
 * @author  Arsalan Arghavan
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Finnotech_API_Handler {

    /**
     * Retrieves a unique, site-specific key for encryption, ensuring it's 32 bytes long.
     * @return string The encryption key.
     */
    private function get_encryption_key() {
        $key = defined('AUTH_KEY') ? AUTH_KEY : NONCE_KEY;
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
        
        $parts = explode('::', base64_decode($encrypted_data), 2);
        
        if (count($parts) !== 2) {
            return '';
        }
        $encrypted = $parts[0];
        $iv = $parts[1];
        
        if (strlen($iv) !== openssl_cipher_iv_length($cipher)) {
            return '';
        }

        $decrypted = openssl_decrypt($encrypted, $cipher, $key, 0, $iv);
        
        return $decrypted === false ? '' : $decrypted;
    }

    /**
     * Gets decrypted credentials from settings.
     * @return array ['client_id' => string, 'api_key' => string] or ['client_id' => '', 'api_key' => '']
     */
    private function get_decrypted_credentials() {
        $options = get_option('maneli_inquiry_all_options', []);
        
        $client_id_raw = $options['finotex_username'] ?? '';
        $api_key_raw = $options['finotex_password'] ?? '';
        
        $client_id = defined('MANELI_FINOTEX_CLIENT_ID') ? MANELI_FINOTEX_CLIENT_ID : $this->decrypt_data($client_id_raw);
        $api_key = defined('MANELI_FINOTEX_API_KEY') ? MANELI_FINOTEX_API_KEY : $this->decrypt_data($api_key_raw);
        
        return [
            'client_id' => $client_id,
            'api_key' => $api_key
        ];
    }

    /**
     * Makes a generic API request to Finnotech.
     * @param string $url The API URL (with %s placeholders for client_id and user_id if needed)
     * @param array $params Query parameters
     * @param string $api_key The API key
     * @param string $method HTTP method (GET or POST)
     * @param array $body Request body for POST requests
     * @return array ['status' => 'DONE'|'FAILED'|'SKIPPED', 'data' => array, 'raw_response' => string]
     */
    private function make_api_request($url, $params = [], $api_key = '', $method = 'GET', $body = null) {
        $result = ['status' => 'FAILED', 'data' => null, 'raw_response' => ''];
        
        if (empty($api_key)) {
            $result['raw_response'] = 'API Key is missing.';
            return $result;
        }

        // Build URL with query parameters
        if (!empty($params)) {
            $url = add_query_arg($params, $url);
        }

        $request_args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ],
            'timeout' => 45,
        ];

        if ($method === 'POST' && $body !== null) {
            $request_args['body'] = json_encode($body);
            $request_args['method'] = 'POST';
        }

        $response = wp_remote_request($url, $request_args);

        if (is_wp_error($response)) {
            $result['raw_response'] = 'WordPress Connection Error: ' . $response->get_error_message();
            return $result;
        }

        $response_body = wp_remote_retrieve_body($response);
        $response_code = wp_remote_retrieve_response_code($response);
        $response_data = json_decode($response_body, true);

        $result['raw_response'] = "Request URL: {$url}\nResponse Code: {$response_code}\nResponse Body:\n" . print_r($response_body, true);

        if ($response_code === 200 && isset($response_data['status']) && $response_data['status'] === 'DONE') {
            $result['status'] = 'DONE';
            $result['data'] = $response_data;
        }

        return $result;
    }

    /**
     * Executes the Credit Risk Inquiry API call.
     * @param string $national_code The national ID code to check.
     * @return array An array containing the status, data, and raw response.
     */
    public function execute_credit_risk_inquiry($national_code) {
        $options = get_option('maneli_inquiry_all_options', []);
        
        if (empty($options['finnotech_credit_risk_enabled']) || $options['finnotech_credit_risk_enabled'] !== '1') {
            return ['status' => 'SKIPPED', 'data' => null, 'raw_response' => 'Credit Risk API is disabled in settings.'];
        }

        if (!defined('MANELI_FINNOTECH_CREDIT_RISK_API_URL')) {
            return ['status' => 'SKIPPED', 'data' => null, 'raw_response' => 'Credit Risk API URL constant is missing.'];
        }

        $credentials = $this->get_decrypted_credentials();
        if (empty($credentials['client_id']) || empty($credentials['api_key'])) {
            return ['status' => 'FAILED', 'data' => null, 'raw_response' => 'Finnotech Client ID or API Key not set.'];
        }

        $api_url = sprintf(MANELI_FINNOTECH_CREDIT_RISK_API_URL, $credentials['client_id'], $national_code);
        $track_id = 'maneli_' . uniqid();
        
        return $this->make_api_request($api_url, ['trackId' => $track_id], $credentials['api_key']);
    }

    /**
     * Executes the Credit Score Inquiry API call.
     * @param string $national_code The national ID code to check.
     * @return array An array containing the status, data, and raw response.
     */
    public function execute_credit_score_inquiry($national_code) {
        $options = get_option('maneli_inquiry_all_options', []);
        
        if (empty($options['finnotech_credit_score_enabled']) || $options['finnotech_credit_score_enabled'] !== '1') {
            return ['status' => 'SKIPPED', 'data' => null, 'raw_response' => 'Credit Score API is disabled in settings.'];
        }

        if (!defined('MANELI_FINNOTECH_CREDIT_SCORE_API_URL')) {
            return ['status' => 'SKIPPED', 'data' => null, 'raw_response' => 'Credit Score API URL constant is missing.'];
        }

        $credentials = $this->get_decrypted_credentials();
        if (empty($credentials['client_id']) || empty($credentials['api_key'])) {
            return ['status' => 'FAILED', 'data' => null, 'raw_response' => 'Finnotech Client ID or API Key not set.'];
        }

        $api_url = sprintf(MANELI_FINNOTECH_CREDIT_SCORE_API_URL, $credentials['client_id'], $national_code);
        $track_id = 'maneli_' . uniqid();
        
        return $this->make_api_request($api_url, ['trackId' => $track_id], $credentials['api_key']);
    }

    /**
     * Executes the Collaterals Inquiry API call.
     * @param string $national_code The national ID code to check.
     * @return array An array containing the status, data, and raw response.
     */
    public function execute_collaterals_inquiry($national_code) {
        $options = get_option('maneli_inquiry_all_options', []);
        
        if (empty($options['finnotech_collaterals_enabled']) || $options['finnotech_collaterals_enabled'] !== '1') {
            return ['status' => 'SKIPPED', 'data' => null, 'raw_response' => 'Collaterals API is disabled in settings.'];
        }

        if (!defined('MANELI_FINNOTECH_COLLATERALS_API_URL')) {
            return ['status' => 'SKIPPED', 'data' => null, 'raw_response' => 'Collaterals API URL constant is missing.'];
        }

        $credentials = $this->get_decrypted_credentials();
        if (empty($credentials['client_id']) || empty($credentials['api_key'])) {
            return ['status' => 'FAILED', 'data' => null, 'raw_response' => 'Finnotech Client ID or API Key not set.'];
        }

        $api_url = sprintf(MANELI_FINNOTECH_COLLATERALS_API_URL, $credentials['client_id'], $national_code);
        $track_id = 'maneli_' . uniqid();
        
        return $this->make_api_request($api_url, ['trackId' => $track_id], $credentials['api_key']);
    }

    /**
     * Executes the Cheque Color Inquiry API call.
     * @param string $national_code The national ID code to check.
     * @return array An array containing the status, data, and raw response.
     */
    public function execute_cheque_color_inquiry($national_code) {
        $options = get_option('maneli_inquiry_all_options', []);
        
        if (empty($options['finnotech_cheque_color_enabled']) || $options['finnotech_cheque_color_enabled'] !== '1') {
            return ['status' => 'SKIPPED', 'data' => null, 'raw_response' => 'Cheque Color API is disabled in settings.'];
        }

        if (!defined('MANELI_FINNOTECH_CHEQUE_COLOR_API_URL')) {
            return ['status' => 'SKIPPED', 'data' => null, 'raw_response' => 'Cheque Color API URL constant is missing.'];
        }

        $credentials = $this->get_decrypted_credentials();
        if (empty($credentials['client_id']) || empty($credentials['api_key'])) {
            return ['status' => 'FAILED', 'data' => null, 'raw_response' => 'Finnotech Client ID or API Key not set.'];
        }

        $api_url = sprintf(MANELI_FINNOTECH_CHEQUE_COLOR_API_URL, $credentials['client_id']);
        $track_id = 'maneli_' . uniqid();
        
        return $this->make_api_request($api_url, ['idCode' => $national_code, 'trackId' => $track_id], $credentials['api_key']);
    }
}
