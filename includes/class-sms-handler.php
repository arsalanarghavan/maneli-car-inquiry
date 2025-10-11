<?php
/**
 * Handles sending SMS notifications via the Payamak-Panel (MeliPayamak) web service.
 *
 * @package Maneli_Car_Inquiry/Includes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.0
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
            
            $sms_client = new SoapClient("http://api.payamak-panel.com/post/send.asmx?wsdl", ['encoding' => 'UTF-8']);

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
            // A successful send returns a long string (recId). Failures return small integers.
            // A length check is a reliable way to determine success.
            if (is_string($send_Result_code) && strlen($send_Result_code) > 10) {
                return true;
            } else {
                // Log the specific error code from the API for debugging
                error_log('Maneli SMS API Error: Failed to send SMS. API returned code: ' . $send_Result_code);
                return false;
            }

        } catch (SoapFault $e) {
            // Catch specific SOAP errors for more detailed logging
            error_log('Maneli SMS SOAP Fault: ' . $e->getMessage());
            return false;
        } catch (Exception $e) {
            // Catch any other general exceptions
            error_log('Maneli SMS General Exception: ' . $e->getMessage());
            return false;
        }
    }
}