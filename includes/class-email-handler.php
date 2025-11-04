<?php
/**
 * Email Handler
 * Handles sending emails via SMTP or wp_mail
 *
 * @package Maneli_Car_Inquiry/Includes
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Email_Handler {

    /**
     * Plugin options array.
     * @var array
     */
    private $options;

    public function __construct() {
        $this->options = get_option('maneli_inquiry_all_options', []);
    }

    /**
     * Send email
     *
     * @param string|array $to Email address(es) - can be single email or array
     * @param string $subject Email subject
     * @param string $message Email message (HTML supported)
     * @param array $headers Additional headers
     * @return bool|array True on success, false on failure, or array with results for bulk
     */
    public function send_email($to, $subject, $message, $headers = []) {
        if (empty($to) || empty($subject) || empty($message)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli Email Error: Missing required parameters (to, subject, or message).');
            }
            return false;
        }

        // Check if SMTP is configured
        $use_smtp = $this->options['email_use_smtp'] ?? false;
        
        if ($use_smtp) {
            return $this->send_via_smtp($to, $subject, $message, $headers);
        } else {
            return $this->send_via_wp_mail($to, $subject, $message, $headers);
        }
    }

    /**
     * Send email via wp_mail
     *
     * @param string|array $to
     * @param string $subject
     * @param string $message
     * @param array $headers
     * @return bool|array
     */
    private function send_via_wp_mail($to, $subject, $message, $headers = []) {
        // If single email, convert to array for processing
        $is_single = !is_array($to);
        if ($is_single) {
            $to = [$to];
        }

        $from_email = $this->options['email_from_email'] ?? get_option('admin_email');
        $from_name = $this->options['email_from_name'] ?? get_bloginfo('name');

        // Default headers
        $default_headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>',
        ];

        $all_headers = array_merge($default_headers, $headers);
        $results = [];

        foreach ($to as $email) {
            $email = sanitize_email($email);
            if (!is_email($email)) {
                $results[$email] = [
                    'success' => false,
                    'error' => 'Invalid email address'
                ];
                continue;
            }

            try {
                $result = wp_mail($email, $subject, $message, $all_headers);
                
                if ($result) {
                    $results[$email] = ['success' => true];
                } else {
                    $results[$email] = [
                        'success' => false,
                        'error' => 'wp_mail returned false'
                    ];
                }
            } catch (Exception $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Maneli Email Exception: ' . $e->getMessage());
                }
                $results[$email] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        // If single email, return single result
        if ($is_single) {
            $first_result = reset($results);
            return $first_result['success'] ?? false;
        }

        return $results;
    }

    /**
     * Send email via SMTP
     *
     * @param string|array $to
     * @param string $subject
     * @param string $message
     * @param array $headers
     * @return bool|array
     */
    private function send_via_smtp($to, $subject, $message, $headers = []) {
        $smtp_host = $this->options['email_smtp_host'] ?? '';
        $smtp_port = $this->options['email_smtp_port'] ?? 587;
        $smtp_username = $this->options['email_smtp_username'] ?? '';
        $smtp_password = $this->options['email_smtp_password'] ?? '';
        $smtp_encryption = $this->options['email_smtp_encryption'] ?? 'tls';
        $from_email = $this->options['email_from_email'] ?? get_option('admin_email');
        $from_name = $this->options['email_from_name'] ?? get_bloginfo('name');

        if (empty($smtp_host) || empty($smtp_username) || empty($smtp_password)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli Email Error: SMTP settings are not configured.');
            }
            return false;
        }

        // If single email, convert to array
        $is_single = !is_array($to);
        if ($is_single) {
            $to = [$to];
        }

        $results = [];

        foreach ($to as $email) {
            $email = sanitize_email($email);
            if (!is_email($email)) {
                $results[$email] = [
                    'success' => false,
                    'error' => 'Invalid email address'
                ];
                continue;
            }

            try {
                // Use PHPMailer for SMTP
                require_once(ABSPATH . WPINC . '/PHPMailer/PHPMailer.php');
                require_once(ABSPATH . WPINC . '/PHPMailer/SMTP.php');
                require_once(ABSPATH . WPINC . '/PHPMailer/Exception.php');

                $mail = new PHPMailer\PHPMailer\PHPMailer(true);

                // SMTP configuration
                $mail->isSMTP();
                $mail->Host = $smtp_host;
                $mail->SMTPAuth = true;
                $mail->Username = $smtp_username;
                $mail->Password = $smtp_password;
                $mail->SMTPSecure = $smtp_encryption; // 'tls' or 'ssl'
                $mail->Port = (int)$smtp_port;
                $mail->CharSet = 'UTF-8';

                // Email content
                $mail->setFrom($from_email, $from_name);
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $message;

                // Add custom headers
                foreach ($headers as $header) {
                    if (strpos($header, ':') !== false) {
                        list($key, $value) = explode(':', $header, 2);
                        $mail->addCustomHeader(trim($key), trim($value));
                    }
                }

                $mail->send();
                $results[$email] = ['success' => true];

            } catch (Exception $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Maneli Email SMTP Exception: ' . $e->getMessage());
                }
                $results[$email] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        // If single email, return single result
        if ($is_single) {
            $first_result = reset($results);
            return $first_result['success'] ?? false;
        }

        return $results;
    }
}

