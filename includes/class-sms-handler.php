<?php
if (!defined('ABSPATH')) {
    exit;
}

class Maneli_SMS_Handler {

    private $options;

    public function __construct() {
        $this->options = get_option('maneli_inquiry_all_options', []);
    }

    /**
     * Sends an SMS using a pre-approved pattern via SoapClient.
     * @param int    $bodyId The pattern ID (Body ID).
     * @param string $to The recipient's mobile number.
     * @param array  $params_array The array of variables for the pattern.
     * @return bool True on success, false on failure.
     */
    public function send_pattern($bodyId, $to, $params_array) {
        $username = $this->options['sms_username'] ?? '';
        $password = $this->options['sms_password'] ?? '';

        if (empty($username) || empty($password) || empty($bodyId) || empty($to)) {
            error_log('Maneli SMS Error: Missing required parameters for sending pattern SMS.');
            return false;
        }

        // Ensure the soap extension is enabled on the server
        if (!class_exists('SoapClient')) {
            error_log('Maneli SMS Error: SoapClient class not found. Please enable the PHP SOAP extension on your server.');
            return false;
        }

        try {
            ini_set("soap.wsdl_cache_enabled", "0");
            $sms_client = new SoapClient("http://api.payamak-panel.com/post/send.asmx?wsdl", ['encoding' => 'UTF-8']);

            $data = [
                "username" => $username,
                "password" => $password,
                "text"     => $params_array, // Pass parameters as a true array
                "to"       => $to,
                "bodyId"   => (int)$bodyId
            ];

            $send_Result = $sms_client->SendByBaseNumber($data)->SendByBaseNumberResult;

            // A successful send returns a long string (recId). Failures return small integers.
            if (is_string($send_Result) && strlen($send_Result) > 10) {
                return true;
            } else {
                error_log('Maneli SMS API Error: Code ' . $send_Result);
                return false;
            }

        } catch (Exception $e) {
            error_log('Maneli SMS SOAP Exception: ' . $e->getMessage());
            return false;
        }
    }
}