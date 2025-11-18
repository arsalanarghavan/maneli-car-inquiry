<?php
/**
 * CAPTCHA Helper Class
 * 
 * Provides centralized CAPTCHA functionality supporting:
 * - Google reCAPTCHA v2
 * - Google reCAPTCHA v3
 * - hCaptcha
 * 
 * @package Maneli_Car_Inquiry/Includes/Helpers
 * @author  Maneli Car Inquiry Team
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Captcha_Helper {

    /**
     * Check if CAPTCHA is enabled
     * 
     * @return bool True if CAPTCHA is enabled, false otherwise
     */
    public static function is_enabled() {
        $options = get_option('maneli_inquiry_all_options', []);
        return !empty($options['captcha_enabled']) && $options['captcha_enabled'] === '1';
    }

    /**
     * Get selected CAPTCHA type
     * 
     * @return string CAPTCHA type: 'recaptcha_v2', 'recaptcha_v3', 'hcaptcha', or empty string
     */
    public static function get_captcha_type() {
        if (!self::is_enabled()) {
            return '';
        }
        
        $options = get_option('maneli_inquiry_all_options', []);
        $type = isset($options['captcha_type']) ? sanitize_text_field($options['captcha_type']) : '';
        
        // Validate type
        $valid_types = ['recaptcha_v2', 'recaptcha_v3', 'hcaptcha'];
        if (in_array($type, $valid_types, true)) {
            return $type;
        }
        
        return '';
    }

    /**
     * Get site key for a specific CAPTCHA type
     * 
     * @param string $type CAPTCHA type
     * @return string Site key or empty string
     */
    public static function get_site_key($type = null) {
        if ($type === null) {
            $type = self::get_captcha_type();
        }
        
        if (empty($type)) {
            return '';
        }
        
        $options = get_option('maneli_inquiry_all_options', []);
        
        switch ($type) {
            case 'recaptcha_v2':
                return isset($options['recaptcha_v2_site_key']) ? sanitize_text_field($options['recaptcha_v2_site_key']) : '';
            case 'recaptcha_v3':
                return isset($options['recaptcha_v3_site_key']) ? sanitize_text_field($options['recaptcha_v3_site_key']) : '';
            case 'hcaptcha':
                return isset($options['hcaptcha_site_key']) ? sanitize_text_field($options['hcaptcha_site_key']) : '';
            default:
                return '';
        }
    }

    /**
     * Get decrypted secret key for a specific CAPTCHA type
     * 
     * @param string $type CAPTCHA type
     * @return string Secret key or empty string
     */
    public static function get_secret_key($type = null) {
        if ($type === null) {
            $type = self::get_captcha_type();
        }
        
        if (empty($type)) {
            return '';
        }
        
        $options = get_option('maneli_inquiry_all_options', []);
        $encrypted_key = '';
        
        switch ($type) {
            case 'recaptcha_v2':
                $encrypted_key = isset($options['recaptcha_v2_secret_key']) ? $options['recaptcha_v2_secret_key'] : '';
                break;
            case 'recaptcha_v3':
                $encrypted_key = isset($options['recaptcha_v3_secret_key']) ? $options['recaptcha_v3_secret_key'] : '';
                break;
            case 'hcaptcha':
                $encrypted_key = isset($options['hcaptcha_secret_key']) ? $options['hcaptcha_secret_key'] : '';
                break;
            default:
                return '';
        }
        
        if (empty($encrypted_key)) {
            return '';
        }
        
        // Decrypt the secret key
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/helpers/class-maneli-encryption-helper.php';
        return Maneli_Encryption_Helper::decrypt($encrypted_key);
    }

    /**
     * Get score threshold for reCAPTCHA v3
     * 
     * @return float Score threshold (default: 0.5)
     */
    public static function get_score_threshold() {
        $options = get_option('maneli_inquiry_all_options', []);
        $threshold = isset($options['recaptcha_v3_score_threshold']) ? floatval($options['recaptcha_v3_score_threshold']) : 0.5;
        
        // Ensure threshold is between 0 and 1
        return max(0.0, min(1.0, $threshold));
    }

    /**
     * Verify CAPTCHA token with appropriate API
     * 
     * @param string $token The CAPTCHA token to verify
     * @param string|null $type CAPTCHA type (if null, uses current setting)
     * @return array Result with 'success' and 'message' keys
     */
    public static function verify_token($token, $type = null) {
        if (empty($token)) {
            return [
                'success' => false,
                'message' => esc_html__('CAPTCHA token is missing.', 'maneli-car-inquiry')
            ];
        }
        
        if ($type === null) {
            $type = self::get_captcha_type();
        }
        
        if (empty($type)) {
            return [
                'success' => false,
                'message' => esc_html__('CAPTCHA type is not configured.', 'maneli-car-inquiry')
            ];
        }
        
        $secret_key = self::get_secret_key($type);
        if (empty($secret_key)) {
            return [
                'success' => false,
                'message' => esc_html__('CAPTCHA secret key is not configured.', 'maneli-car-inquiry')
            ];
        }
        
        // Get user IP address
        $user_ip = self::get_client_ip();
        
        switch ($type) {
            case 'recaptcha_v2':
            case 'recaptcha_v3':
                return self::verify_recaptcha($token, $secret_key, $user_ip, $type);
                
            case 'hcaptcha':
                return self::verify_hcaptcha($token, $secret_key, $user_ip);
                
            default:
                return [
                    'success' => false,
                    'message' => esc_html__('Invalid CAPTCHA type.', 'maneli-car-inquiry')
                ];
        }
    }

    /**
     * Verify reCAPTCHA token (v2 or v3)
     * 
     * @param string $token The reCAPTCHA token
     * @param string $secret_key The secret key
     * @param string $user_ip User IP address
     * @param string $type 'recaptcha_v2' or 'recaptcha_v3'
     * @return array Result with 'success' and 'message' keys
     */
    private static function verify_recaptcha($token, $secret_key, $user_ip, $type) {
        $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
        
        $args = [
            'method' => 'POST',
            'timeout' => 10,
            'body' => [
                'secret' => $secret_key,
                'response' => $token,
                'remoteip' => $user_ip
            ]
        ];
        
        $response = wp_remote_post($verify_url, $args);
        
        if (is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli reCAPTCHA Error: ' . $response->get_error_message());
            }
            return [
                'success' => false,
                'message' => esc_html__('Failed to verify reCAPTCHA. Please try again.', 'maneli-car-inquiry')
            ];
        }
        
        $response_body = wp_remote_retrieve_body($response);
        $result = json_decode($response_body, true);
        
        if (!$result || !isset($result['success'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli reCAPTCHA Error: Invalid response from Google API.');
            }
            return [
                'success' => false,
                'message' => esc_html__('Invalid response from reCAPTCHA service.', 'maneli-car-inquiry')
            ];
        }
        
        if (!$result['success']) {
            $error_codes = isset($result['error-codes']) ? $result['error-codes'] : [];
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli reCAPTCHA Error: ' . implode(', ', $error_codes));
            }
            
            $error_message = esc_html__('reCAPTCHA verification failed.', 'maneli-car-inquiry');
            if (in_array('timeout-or-duplicate', $error_codes)) {
                $error_message = esc_html__('reCAPTCHA token expired or already used. Please refresh and try again.', 'maneli-car-inquiry');
            } elseif (in_array('invalid-input-secret', $error_codes)) {
                $error_message = esc_html__('reCAPTCHA configuration error. Please contact the administrator.', 'maneli-car-inquiry');
            }
            
            return [
                'success' => false,
                'message' => $error_message
            ];
        }
        
        // For reCAPTCHA v3, check score threshold
        if ($type === 'recaptcha_v3') {
            $score = isset($result['score']) ? floatval($result['score']) : 0;
            $threshold = self::get_score_threshold();
            
            if ($score < $threshold) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Maneli reCAPTCHA v3 Error: Score ' . $score . ' is below threshold ' . $threshold);
                }
                return [
                    'success' => false,
                    'message' => esc_html__('reCAPTCHA verification failed. Your request appears to be suspicious.', 'maneli-car-inquiry'),
                    'score' => $score
                ];
            }
        }
        
        return [
            'success' => true,
            'message' => esc_html__('CAPTCHA verified successfully.', 'maneli-car-inquiry')
        ];
    }

    /**
     * Verify hCaptcha token
     * 
     * @param string $token The hCaptcha token
     * @param string $secret_key The secret key
     * @param string $user_ip User IP address
     * @return array Result with 'success' and 'message' keys
     */
    private static function verify_hcaptcha($token, $secret_key, $user_ip) {
        $verify_url = 'https://hcaptcha.com/siteverify';
        
        $args = [
            'method' => 'POST',
            'timeout' => 10,
            'body' => [
                'secret' => $secret_key,
                'response' => $token,
                'remoteip' => $user_ip
            ]
        ];
        
        $response = wp_remote_post($verify_url, $args);
        
        if (is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli hCaptcha Error: ' . $response->get_error_message());
            }
            return [
                'success' => false,
                'message' => esc_html__('Failed to verify hCaptcha. Please try again.', 'maneli-car-inquiry')
            ];
        }
        
        $response_body = wp_remote_retrieve_body($response);
        $result = json_decode($response_body, true);
        
        if (!$result || !isset($result['success'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli hCaptcha Error: Invalid response from hCaptcha API.');
            }
            return [
                'success' => false,
                'message' => esc_html__('Invalid response from hCaptcha service.', 'maneli-car-inquiry')
            ];
        }
        
        if (!$result['success']) {
            $error_codes = isset($result['error-codes']) ? $result['error-codes'] : [];
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli hCaptcha Error: ' . implode(', ', $error_codes));
            }
            
            $error_message = esc_html__('hCaptcha verification failed.', 'maneli-car-inquiry');
            if (in_array('invalid-input-secret', $error_codes)) {
                $error_message = esc_html__('hCaptcha configuration error. Please contact the administrator.', 'maneli-car-inquiry');
            }
            
            return [
                'success' => false,
                'message' => $error_message
            ];
        }
        
        return [
            'success' => true,
            'message' => esc_html__('CAPTCHA verified successfully.', 'maneli-car-inquiry')
        ];
    }

    /**
     * Enqueue CAPTCHA script (Google reCAPTCHA or hCaptcha)
     * Optimized with async/defer for better performance
     * 
     * @param string|null $type CAPTCHA type (if null, uses current setting)
     * @param string|null $site_key Site key (if null, gets from settings)
     * @param bool $lazy_load Whether to lazy load the script (default: true)
     * @return void
     */
    public static function enqueue_script($type = null, $site_key = null, $lazy_load = true) {
        if ($type === null) {
            $type = self::get_captcha_type();
        }
        
        if (empty($type)) {
            return;
        }
        
        if ($site_key === null) {
            $site_key = self::get_site_key($type);
        }
        
        if (empty($site_key)) {
            return;
        }
        
        // Add script tag with async/defer attributes for better performance
        $handle = '';
        $src = '';
        
        switch ($type) {
            case 'recaptcha_v2':
                $handle = 'google-recaptcha-v2';
                $src = 'https://www.google.com/recaptcha/api.js?onload=maneliRecaptchaCallback&render=explicit';
                break;
                
            case 'recaptcha_v3':
                $handle = 'google-recaptcha-v3';
                $src = 'https://www.google.com/recaptcha/api.js?render=' . esc_attr($site_key);
                break;
                
            case 'hcaptcha':
                $handle = 'hcaptcha';
                $src = 'https://js.hcaptcha.com/1/api.js?onload=maneliHcaptchaCallback&render=explicit';
                break;
        }
        
        if (empty($handle) || empty($src)) {
            return;
        }
        
        // Enqueue script normally first
        wp_enqueue_script(
            $handle,
            $src,
            [],
            null,
            true
        );
        
        // Add async and defer attributes for better performance
        add_filter('script_loader_tag', function($tag, $hook) use ($handle) {
            if ($hook !== $handle) {
                return $tag;
            }
            
            // Add async and defer attributes
            if (strpos($tag, 'async') === false) {
                $tag = str_replace(' src', ' async defer src', $tag);
            }
            
            // Add crossorigin for better security
            if (strpos($tag, 'crossorigin') === false) {
                $tag = str_replace('<script ', '<script crossorigin="anonymous" ', $tag);
            }
            
            return $tag;
        }, 10, 2);
    }

    /**
     * Render CAPTCHA widget/badge HTML
     * 
     * @param string|null $type CAPTCHA type (if null, uses current setting)
     * @param string|null $site_key Site key (if null, gets from settings)
     * @param string $container_id Container ID for the widget
     * @return string HTML for widget/badge
     */
    public static function render_widget($type = null, $site_key = null, $container_id = 'maneli-captcha-widget') {
        if ($type === null) {
            $type = self::get_captcha_type();
        }
        
        if (empty($type)) {
            return '';
        }
        
        if ($site_key === null) {
            $site_key = self::get_site_key($type);
        }
        
        if (empty($site_key)) {
            return '';
        }
        
        $html = '';
        
        switch ($type) {
            case 'recaptcha_v2':
                $html = '<div id="' . esc_attr($container_id) . '" class="g-recaptcha" data-sitekey="' . esc_attr($site_key) . '"></div>';
                break;
                
            case 'recaptcha_v3':
                // v3 doesn't need a widget, but we can show the badge
                $html = '<div class="maneli-recaptcha-v3-badge"></div>';
                break;
                
            case 'hcaptcha':
                $html = '<div id="' . esc_attr($container_id) . '" class="h-captcha" data-sitekey="' . esc_attr($site_key) . '"></div>';
                break;
        }
        
        return $html;
    }

    /**
     * Get client IP address
     * 
     * @return string IP address
     */
    private static function get_client_ip() {
        $ip_keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';
    }
}

