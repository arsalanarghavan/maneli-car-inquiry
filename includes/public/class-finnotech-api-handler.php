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
     * Decrypts data using AES-256-CBC.
     * Wrapper method for backward compatibility - uses Maneli_Encryption_Helper.
     * 
     * @param string $encrypted_data The encrypted data (Base64 encoded).
     * @return string The decrypted data or empty string on failure.
     */
    private function decrypt_data($encrypted_data) {
        return Maneli_Encryption_Helper::decrypt($encrypted_data);
    }

    /**
     * Gets decrypted credentials from settings.
     * @return array ['client_id' => string, 'api_key' => string] or ['client_id' => '', 'api_key' => '']
     */
    private function get_decrypted_credentials() {
        $options = Maneli_Options_Helper::get_all_options();
        
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
     * Makes an API request with retry logic for network failures.
     * 
     * @param string $url The API URL
     * @param array $request_args Request arguments for wp_remote_request
     * @param int $max_retries Maximum number of retry attempts (default: 3)
     * @return WP_Error|array Response from wp_remote_request or WP_Error
     */
    private function make_request_with_retry($url, $request_args, $max_retries = 3) {
        $attempt = 0;
        $last_error = null;
        
        while ($attempt < $max_retries) {
            $attempt++;
            
            $response = wp_remote_request($url, $request_args);
            
            // If successful (not WP_Error and status 200-299), return immediately
            if (!is_wp_error($response)) {
                $response_code = wp_remote_retrieve_response_code($response);
                // Success status codes (200-299) - don't retry
                if ($response_code >= 200 && $response_code < 300) {
                    return $response;
                }
                // 4xx errors (client errors) - don't retry
                if ($response_code >= 400 && $response_code < 500) {
                    return $response;
                }
                // 5xx errors (server errors) - retry
                if ($response_code >= 500 && $response_code < 600) {
                    $last_error = new WP_Error(
                        'http_error',
                        sprintf('HTTP %d error from server (attempt %d/%d)', $response_code, $attempt, $max_retries)
                    );
                    
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Maneli Finnotech API: Retrying after ' . $response_code . ' error. Attempt ' . $attempt . '/' . $max_retries);
                    }
                } else {
                    // Other status codes - don't retry
                    return $response;
                }
            } else {
                // Network error - retry
                $last_error = $response;
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Maneli Finnotech API: Network error: ' . $response->get_error_message() . ' (attempt ' . $attempt . '/' . $max_retries . ')');
                }
            }
            
            // Wait before retry (exponential backoff: 2s, 4s, 8s)
            if ($attempt < $max_retries) {
                $wait_time = pow(2, $attempt); // 2, 4, 8 seconds
                sleep($wait_time);
            }
        }
        
        // All retries exhausted - return last error or response
        return $last_error ? $last_error : $response;
    }

    /**
     * Makes a generic API request to Finnotech with retry logic.
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

        // Use retry logic
        $response = $this->make_request_with_retry($url, $request_args, 3);

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
        if (!Maneli_Options_Helper::is_option_enabled('finnotech_credit_risk_enabled', false)) {
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
        if (!Maneli_Options_Helper::is_option_enabled('finnotech_credit_score_enabled', false)) {
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
        if (!Maneli_Options_Helper::is_option_enabled('finnotech_collaterals_enabled', false)) {
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
        if (!Maneli_Options_Helper::is_option_enabled('finnotech_cheque_color_enabled', false)) {
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
