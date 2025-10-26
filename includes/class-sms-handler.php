<?php
/**
 * Handles sending SMS notifications via the Payamak-Panel (MeliPayamak) web service.
 *
 * @package Maneli_Car_Inquiry/Includes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.2 (API URL Fixed with Constant)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_SMS_Handler {

    /**
     * Plugin options array.
     * @var array
     */
    private $options;

    public function __construct() {
        $this->options = get_option('maneli_inquiry_all_options', []);
    }

    /**
     * Sends an SMS using a pre-approved pattern (Body ID) via SoapClient.
     *
     * @param int    $bodyId       The pattern ID (Body ID) from the SMS panel.
     * @param string $recipient    The recipient's mobile number.
     * @param array  $parameters   An array of variables for the pattern.
     * @return bool True on success, false on failure.
     */
    public function send_pattern($bodyId, $recipient, $parameters) {
        $username = $this->options['sms_username'] ?? '';
        $password = $this->options['sms_password'] ?? '';

        // 1. Validate required credentials and parameters
        if (empty($username) || empty($password) || empty($bodyId) || empty($recipient)) {
            error_log('Maneli SMS Error: Missing required parameters (username, password, bodyId, or recipient) for sending pattern SMS.');
            return false;
        }

        // 2. Check if the SOAP extension is enabled on the server
        if (!class_exists('SoapClient')) {
            error_log('Maneli SMS Error: SoapClient class not found. Please enable the PHP SOAP extension on your server.');
            return false;
        }

        try {
            // Disable WSDL caching for development/reliability
            ini_set("soap.wsdl_cache_enabled", "0");
            
            // FIX: Using MANELI_SMS_API_WSDL constant for secure connection
            $sms_client = new SoapClient(MANELI_SMS_API_WSDL, ['encoding' => 'UTF-8']);

            $data = [
                "username" => $username,
                "password" => $password,
                "text"     => $parameters, // Pass parameters as a native array
                "to"       => $recipient,
                "bodyId"   => (int)$bodyId,
            ];

            // 3. Call the web service method
            $result = $sms_client->SendByBaseNumber($data);
            $send_Result_code = $result->SendByBaseNumberResult;
            
            // 4. Interpret the result code
            if (is_string($send_Result_code) && strlen($send_Result_code) > 10) {
                return true;
            } else {
                error_log('Maneli SMS API Error: Failed to send SMS. API returned code: ' . $send_Result_code);
                return false;
            }

        } catch (SoapFault $e) {
            error_log('Maneli SMS SOAP Fault: ' . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log('Maneli SMS General Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Sends a simple SMS message.
     *
     * @param string $recipient The recipient's mobile number.
     * @param string $message   The message to send.
     * @return bool True on success, false on failure.
     */
    public function send_sms($recipient, $message) {
        $username = $this->options['sms_username'] ?? '';
        $password = $this->options['sms_password'] ?? '';

        // Validate required credentials
        if (empty($username) || empty($password) || empty($recipient) || empty($message)) {
            error_log('Maneli SMS Error: Missing required parameters for sending SMS.');
            return false;
        }

        // Check if the SOAP extension is enabled
        if (!class_exists('SoapClient')) {
            error_log('Maneli SMS Error: SoapClient class not found. Please enable the PHP SOAP extension on your server.');
            return false;
        }

        try {
            // Disable WSDL caching
            ini_set("soap.wsdl_cache_enabled", "0");
            
            $sms_client = new SoapClient(MANELI_SMS_API_WSDL, ['encoding' => 'UTF-8']);

            $data = [
                "username" => $username,
                "password" => $password,
                "text"     => $message,
                "to"       => $recipient,
            ];

            // Call the web service method
            $result = $sms_client->SendSimpleSMS2($data);
            $send_Result_code = $result->SendSimpleSMS2Result;
            
            // Interpret the result code
            if (is_string($send_Result_code) && strlen($send_Result_code) > 10) {
                return true;
            } else {
                error_log('Maneli SMS API Error: Failed to send SMS. API returned code: ' . $send_Result_code);
                return false;
            }

        } catch (SoapFault $e) {
            error_log('Maneli SMS SOAP Fault: ' . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log('Maneli SMS General Exception: ' . $e->getMessage());
            return false;
        }
    }
}