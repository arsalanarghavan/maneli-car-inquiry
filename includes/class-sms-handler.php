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
        $this->options = Maneli_Options_Helper::get_all_options();
    }

    /**
     * Sends an SMS using a pre-approved pattern (Body ID) via SoapClient.
     *
     * @param int    $bodyId       The pattern ID (Body ID) from the SMS panel.
     * @param string $recipient    The recipient's mobile number.
     * @param array  $parameters   An array of variables for the pattern.
     * @return array|bool Returns array with 'success' and 'message_id' on success, false on failure.
     */
    public function send_pattern($bodyId, $recipient, $parameters) {
        $username = $this->options['sms_username'] ?? '';
        $password = $this->options['sms_password'] ?? '';

        // 1. Validate required credentials and parameters
        if (empty($username) || empty($password) || empty($bodyId) || empty($recipient)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli SMS Error: Missing required parameters (username, password, bodyId, or recipient) for sending pattern SMS.');
            }
            return false;
        }

        // 2. Check if the SOAP extension is enabled on the server
        if (!class_exists('SoapClient')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli SMS Error: SoapClient class not found. Please enable the PHP SOAP extension on your server.');
            }
            return false;
        }

        // 3. Check if WSDL constant is defined
        if (!defined('MANELI_SMS_API_WSDL')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli SMS Error: MANELI_SMS_API_WSDL constant is not defined.');
            }
            return false;
        }

        try {
            // Disable WSDL caching for development/reliability
            ini_set("soap.wsdl_cache_enabled", "0");
            
            // FIX: Using MANELI_SMS_API_WSDL constant for secure connection
            $sms_client = new SoapClient(MANELI_SMS_API_WSDL, ['encoding' => 'UTF-8']);

            // Prepare parameters for pattern SMS
            // According to MeliPayamak SOAP API (api.payamak-panel.com):
            // SendByBaseNumber expects:
            // - username: string
            // - password: string  
            // - text: array of strings (pattern parameters for {0}, {1}, etc.)
            // - to: string (recipient phone number)
            // - bodyId: int (pattern ID)
            // - isflash: bool (false for regular SMS)
            
            // Ensure parameters is always an array
            $text_param = is_array($parameters) ? $parameters : [$parameters];
            
            // Normalize phone number format (ensure it starts with 0)
            $recipient = ltrim($recipient, '+');
            if (substr($recipient, 0, 2) === '98' && strlen($recipient) === 12) {
                // Convert 989123456789 to 09123456789
                $recipient = '0' . substr($recipient, 2);
            }
            
            // Build SOAP request parameters
            // Note: Field names must match exactly what SOAP API expects
            $data = [
                "username" => (string)$username,
                "password" => (string)$password,
                "text"     => $text_param,          // Array of pattern parameters
                "to"       => (string)$recipient,   // Phone number
                "bodyId"   => (int)$bodyId,        // Pattern ID
                "isflash"  => (bool)false,         // Regular SMS
            ];

            // Call SendByBaseNumber SOAP method for pattern-based SMS
            $result = $sms_client->SendByBaseNumber($data);
            
            // Extract result (SendByBaseNumberResult property)
            $send_Result_code = $result->SendByBaseNumberResult;
            
            // 4. Interpret the result code
            // MeliPayamak API response format:
            // - Success: Returns MessageID (can be string or long number, typically > 10 chars/digits)
            // - Error: Returns error code (typically 0-100, sometimes negative)
            // Known error codes:
            //   0 = No error / Empty (should be treated as success if MessageID exists)
            //   1 = Invalid username/password
            //   2 = User is restricted/limited
            //   3 = Credit insufficient
            //   4 = Invalid pattern/bodyId
            //   5 = Invalid recipient number
            //   10+ = Other errors
            
            // Convert to string for easier checking
            $result_str = (string)$send_Result_code;
            $result_len = strlen($result_str);
            
            // Check if it's clearly an error code (small numbers: 0-100, negative, or empty)
            if (is_numeric($send_Result_code)) {
                $code_num = intval($send_Result_code);
                
                // Known error codes (0-100 with length <= 3)
                if ($code_num > 0 && $code_num <= 100 && $result_len <= 3) {
                    // Store error code for detailed error message
                    $this->last_error_code = $code_num;
                    
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        $error_messages = [
                            1 => 'Invalid username/password',
                            2 => 'User is restricted/limited',
                            3 => 'Credit insufficient',
                            4 => 'Invalid pattern/bodyId',
                            5 => 'Invalid recipient number',
                            11 => 'Rate limit exceeded or service temporarily unavailable',
                        ];
                        $error_msg = $error_messages[$code_num] ?? 'Unknown error';
                        error_log('Maneli SMS API Error: Failed to send SMS via Pattern. API returned error code: ' . $code_num . ' (' . $error_msg . ')');
                    }
                    return false;
                } elseif ($code_num < 0) {
                    // Negative error code
                    $this->last_error_code = $code_num;
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Maneli SMS API Error: Failed to send SMS via Pattern. API returned negative error code: ' . $code_num);
                    }
                    return false;
                } elseif ($result_len > 10) {
                    // Long numeric MessageID - likely success
                    return [
                        'success' => true,
                        'message_id' => (string)$send_Result_code
                    ];
                } elseif ($code_num == 0 && $result_len <= 3) {
                    // Code 0 with short length - might be error, but could also be empty MessageID
                    // This is ambiguous - log it but treat as error for safety
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Maneli SMS API Warning: Received code 0 with short length. This might indicate an error or empty MessageID.');
                    }
                    return false;
                }
            }
            
            // Check if it's a string MessageID (success)
            if (is_string($send_Result_code) && $result_len > 10) {
                // Success - MessageID returned as string
                return [
                    'success' => true,
                    'message_id' => (string)$send_Result_code
                ];
            }
            
            // Unknown format - log for debugging but treat as error
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli SMS API Warning: Unexpected response format. Code: ' . var_export($send_Result_code, true) . ' (Type: ' . gettype($send_Result_code) . ', Length: ' . $result_len . ')');
            }
            
            // Conservative approach: if length > 3, might be MessageID (success)
            // If length <= 3, likely error code
            if ($result_len > 3) {
                return [
                    'success' => true,
                    'message_id' => (string)$send_Result_code
                ];
            }
            
            return false;

        } catch (SoapFault $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli SMS SOAP Fault: ' . $e->getMessage());
            }
            return false;
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli SMS General Exception: ' . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Last error code returned by API (for detailed error messages)
     * @var int|null
     */
    private $last_error_code = null;
    
    /**
     * Get the last error code returned by API
     * @return int|null
     */
    public function get_last_error_code() {
        return $this->last_error_code;
    }
    
    /**
     * Reset the last error code (call before each new SMS attempt)
     */
    public function reset_error_code() {
        $this->last_error_code = null;
    }
    
    /**
     * Sends a simple SMS message.
     *
     * @param string $recipient The recipient's mobile number.
     * @param string $message   The message to send.
     * @return array|bool Returns array with 'success' and 'message_id' on success, false on failure.
     */
    public function send_sms($recipient, $message) {
        $username = $this->options['sms_username'] ?? '';
        $password = $this->options['sms_password'] ?? '';

        // Validate required credentials
        if (empty($username) || empty($password) || empty($recipient) || empty($message)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli SMS Error: Missing required parameters for sending SMS.');
            }
            return false;
        }

        // Check if the SOAP extension is enabled
        if (!class_exists('SoapClient')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli SMS Error: SoapClient class not found. Please enable the PHP SOAP extension on your server.');
            }
            return false;
        }

        // Check if WSDL constant is defined
        if (!defined('MANELI_SMS_API_WSDL')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli SMS Error: MANELI_SMS_API_WSDL constant is not defined.');
            }
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
                "isflash"  => false, // Regular SMS, not flash
            ];

            // Call the web service method
            $result = $sms_client->SendSimpleSMS2($data);
            $send_Result_code = $result->SendSimpleSMS2Result;
            
            // Interpret the result code
            // MeliPayamak API response format:
            // - Success: Returns MessageID (can be string or long number, typically > 10 chars/digits)
            // - Error: Returns error code (typically 0-100, sometimes negative)
            // Known error codes (same as pattern SMS):
            //   1 = Invalid username/password
            //   2 = User is restricted/limited
            //   3 = Credit insufficient
            //   5 = Invalid recipient number
            //   10+ = Other errors
            
            // Convert to string for easier checking
            $result_str = (string)$send_Result_code;
            $result_len = strlen($result_str);
            
            // Check if it's clearly an error code (small numbers: 0-100, negative, or empty)
            if (is_numeric($send_Result_code)) {
                $code_num = intval($send_Result_code);
                
                // Known error codes (0-100 with length <= 3)
                if ($code_num > 0 && $code_num <= 100 && $result_len <= 3) {
                    // Store error code for detailed error message
                    $this->last_error_code = $code_num;
                    
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        $error_messages = [
                            1 => 'Invalid username/password',
                            2 => 'User is restricted/limited',
                            3 => 'Credit insufficient',
                            4 => 'Invalid pattern/bodyId',
                            5 => 'Invalid recipient number',
                            11 => 'Rate limit exceeded or service temporarily unavailable',
                        ];
                        $error_msg = $error_messages[$code_num] ?? 'Unknown error';
                        error_log('Maneli SMS API Error: Failed to send SMS. API returned error code: ' . $code_num . ' (' . $error_msg . ')');
                    }
                    return false;
                } elseif ($code_num < 0) {
                    // Negative error code
                    $this->last_error_code = $code_num;
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Maneli SMS API Error: Failed to send SMS. API returned negative error code: ' . $code_num);
                    }
                    return false;
                } elseif ($result_len > 10) {
                    // Long numeric MessageID - likely success
                    return [
                        'success' => true,
                        'message_id' => (string)$send_Result_code
                    ];
                } elseif ($code_num == 0 && $result_len <= 3) {
                    // Code 0 with short length - might be error
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Maneli SMS API Warning: Received code 0 with short length. This might indicate an error.');
                    }
                    return false;
                }
            }
            
            // Check if it's a string MessageID (success)
            if (is_string($send_Result_code) && $result_len > 10) {
                // Success - MessageID returned as string
                return [
                    'success' => true,
                    'message_id' => (string)$send_Result_code
                ];
            }
            
            // Unknown format - log for debugging but treat as error
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli SMS API Warning: Unexpected response format. Code: ' . var_export($send_Result_code, true) . ' (Type: ' . gettype($send_Result_code) . ', Length: ' . $result_len . ')');
            }
            
            // Conservative approach: if length > 3, might be MessageID (success)
            // If length <= 3, likely error code
            if ($result_len > 3) {
                return [
                    'success' => true,
                    'message_id' => (string)$send_Result_code
                ];
            }
            
            return false;

        } catch (SoapFault $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli SMS SOAP Fault: ' . $e->getMessage());
            }
            return false;
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli SMS General Exception: ' . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Get SMS delivery status from MeliPayamak API
     *
     * @param string $message_id Message ID returned from send_sms or send_pattern
     * @param string $from Phone number that sent the SMS (optional)
     * @return array|false Returns array with status info on success, false on failure
     */
    public function get_message_status($message_id, $from = '') {
        $username = $this->options['sms_username'] ?? '';
        $password = $this->options['sms_password'] ?? '';

        if (empty($username) || empty($password) || empty($message_id)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli SMS Error: Missing required parameters for getting message status.');
            }
            return false;
        }

        if (!class_exists('SoapClient')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli SMS Error: SoapClient class not found.');
            }
            return false;
        }

        if (!defined('MANELI_SMS_API_WSDL')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli SMS Error: MANELI_SMS_API_WSDL constant is not defined.');
            }
            return false;
        }

        try {
            ini_set("soap.wsdl_cache_enabled", "0");
            $sms_client = new SoapClient(MANELI_SMS_API_WSDL, ['encoding' => 'UTF-8']);

            // GetMessagesReceptions method from MeliPayamak API
            // Parameters: username, password, msgId, fromRows
            $data = [
                "username" => (string)$username,
                "password" => (string)$password,
                "msgId" => (string)$message_id,
                "fromRows" => "0", // Start from row 0
            ];

            $result = $sms_client->GetMessagesReceptions($data);
            $receptions = $result->GetMessagesReceptionsResult ?? null;

            if (empty($receptions)) {
                return [
                    'status' => 'unknown',
                    'message' => 'No delivery information available'
                ];
            }

            // Parse reception data (format depends on API response)
            // Typically returns array of objects with fields like: Mobile, Date, Status
            if (is_array($receptions) && !empty($receptions)) {
                $reception = $receptions[0];
                $status = $reception->Status ?? 'unknown';
                
                return [
                    'status' => (string)$status,
                    'mobile' => $reception->Mobile ?? '',
                    'date' => $reception->Date ?? '',
                    'message' => $this->parse_delivery_status($status)
                ];
            } elseif (is_object($receptions)) {
                // Single reception object
                $status = $receptions->Status ?? 'unknown';
                
                return [
                    'status' => (string)$status,
                    'mobile' => $receptions->Mobile ?? '',
                    'date' => $receptions->Date ?? '',
                    'message' => $this->parse_delivery_status($status)
                ];
            }

            return [
                'status' => 'unknown',
                'message' => 'Unable to parse delivery status'
            ];

        } catch (SoapFault $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli SMS SOAP Fault (get_message_status): ' . $e->getMessage());
            }
            return false;
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli SMS General Exception (get_message_status): ' . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Parse delivery status code to human-readable message
     *
     * @param string|int $status Status code from API
     * @return string Human-readable status message
     */
    private function parse_delivery_status($status) {
        $status = (string)$status;
        $status_messages = [
            '1' => esc_html__('Delivered', 'maneli-car-inquiry'),
            '2' => esc_html__('Failed', 'maneli-car-inquiry'),
            '3' => esc_html__('Pending', 'maneli-car-inquiry'),
            '4' => esc_html__('Blocked', 'maneli-car-inquiry'),
            '5' => esc_html__('Rejected', 'maneli-car-inquiry'),
        ];
        
        return $status_messages[$status] ?? esc_html__('Unknown status', 'maneli-car-inquiry');
    }

    /**
     * Gets the SMS credit balance from MeliPayamak panel using REST API.
     *
     * @return float|false Credit balance on success, false on failure.
     */
    public function get_credit() {
        $username = $this->options['sms_username'] ?? '';
        $password = $this->options['sms_password'] ?? '';

        // Validate required credentials
        if (empty($username) || empty($password)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli SMS Error: Missing required credentials (username or password) for getting credit.');
            }
            return false;
        }

        try {
            // Use REST API for getCredit (SOAP doesn't support it)
        // Try different REST API endpoints
        $rest_urls = [
            'https://rest.payamak-panel.com/api/SendSMS/GetCredit',
            'https://api.payamak-panel.com/rest/SendSMS/GetCredit',
            'https://rest.payamak-panel.com/api/GetCredit',
        ];
        
        $credit_result = null;
        $last_error = null;
        
        foreach ($rest_urls as $rest_url) {
            try {
                // Prepare POST data
                $post_data = [
                    'username' => $username,
                    'password' => $password,
                ];
                
                // Make REST API request
                $response = wp_remote_post($rest_url, [
                    'body' => $post_data,
                    'timeout' => 30,
                    'sslverify' => true,
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ],
                ]);
                
                // Check for errors
                if (is_wp_error($response)) {
                    $last_error = $response->get_error_message();
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Maneli SMS REST API Error (URL: ' . $rest_url . '): ' . $last_error);
                    }
                    continue; // Try next URL
                }
                
                $response_code = wp_remote_retrieve_response_code($response);
                $response_body = wp_remote_retrieve_body($response);
                
                if ($response_code !== 200) {
                    $last_error = 'HTTP ' . $response_code . ' - ' . $response_body;
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Maneli SMS REST API Error (URL: ' . $rest_url . '): ' . $last_error);
                    }
                    continue; // Try next URL
                }
                
                // Parse response
                // The API might return JSON or plain text/number
                
                // Try to decode as JSON first
                $json_data = json_decode($response_body, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($json_data)) {
                    // Check common JSON response formats
                    $credit_result = $json_data['Value'] ?? $json_data['value'] ?? $json_data['Credit'] ?? $json_data['credit'] ?? $json_data['result'] ?? $json_data['StrRetStatus'] ?? null;
                } else {
                    // If not JSON, treat as plain number
                    $credit_result = trim($response_body);
                }
                
                // If we got a valid result, break the loop
                if ($credit_result !== null && $credit_result !== '' && is_numeric($credit_result)) {
                    break;
                }
                
            } catch (Exception $e) {
                $last_error = $e->getMessage();
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Maneli SMS REST API Exception (URL: ' . $rest_url . '): ' . $last_error);
                }
                continue; // Try next URL
            }
        }
        
        // Check if we got a valid result
        if ($credit_result === null || $credit_result === '') {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli SMS API Error: getCredit returned null/empty result after trying all URLs. Last error: ' . $last_error);
            }
            return false;
        }
        
        // Convert to float/numeric
        $credit = is_numeric($credit_result) ? floatval($credit_result) : false;
        
        // Check if it's a valid positive number
        if ($credit === false || $credit < 0) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli SMS API Error: Invalid credit value returned: ' . var_export($credit_result, true) . ' (Type: ' . gettype($credit_result) . ')');
            }
            return false;
        }

        return $credit;

        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli SMS General Exception (getCredit): ' . $e->getMessage());
            }
            return false;
        }
    }
}