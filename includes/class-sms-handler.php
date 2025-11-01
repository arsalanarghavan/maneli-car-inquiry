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
                    return true;
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
                return true;
            }
            
            // Unknown format - log for debugging but treat as error
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli SMS API Warning: Unexpected response format. Code: ' . var_export($send_Result_code, true) . ' (Type: ' . gettype($send_Result_code) . ', Length: ' . $result_len . ')');
            }
            
            // Conservative approach: if length > 3, might be MessageID (success)
            // If length <= 3, likely error code
            return $result_len > 3;

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
     * @return bool True on success, false on failure.
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
                            5 => 'Invalid recipient number',
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
                    return true;
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
                return true;
            }
            
            // Unknown format - log for debugging but treat as error
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli SMS API Warning: Unexpected response format. Code: ' . var_export($send_Result_code, true) . ' (Type: ' . gettype($send_Result_code) . ', Length: ' . $result_len . ')');
            }
            
            // Conservative approach: if length > 3, might be MessageID (success)
            // If length <= 3, likely error code
            return $result_len > 3;

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
}