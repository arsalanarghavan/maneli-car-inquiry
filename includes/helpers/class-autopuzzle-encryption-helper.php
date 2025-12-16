<?php
/**
 * Encryption Helper Class
 * 
 * Provides centralized encryption and decryption functionality using AES-256-CBC.
 * This class eliminates code duplication across the plugin.
 * 
 * @package Autopuzzle_Car_Inquiry/Includes/Helpers
 * @author  AutoPuzzle Car Inquiry Team
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Autopuzzle_Encryption_Helper {

    /**
     * Retrieves a unique, site-specific key for encryption, ensuring it's 32 bytes long.
     * 
     * @return string The encryption key (32 bytes).
     */
    public static function get_encryption_key() {
        // Use a unique, secure key from wp-config.php
        $key = defined('AUTH_KEY') ? AUTH_KEY : NONCE_KEY;
        // Generate a 32-byte key from the security constant using SHA-256 for openssl_encrypt
        return hash('sha256', $key, true); 
    }

    /**
     * Encrypts data using AES-256-CBC.
     * 
     * @param string $data The data to encrypt.
     * @return string The Base64 encoded encrypted data with IV, or empty string on failure.
     */
    public static function encrypt($data) {
        if (empty($data)) {
            return '';
        }
        
        $key = self::get_encryption_key();
        $cipher = 'aes-256-cbc';
        $iv_length = openssl_cipher_iv_length($cipher);
        
        if ($iv_length === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AutoPuzzle Encryption Error: Invalid cipher algorithm.');
            }
            return '';
        }
        
        // Use a cryptographically secure IV
        $iv = openssl_random_pseudo_bytes($iv_length);
        if ($iv === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AutoPuzzle Encryption Error: Failed to generate IV.');
            }
            return '';
        }
        
        $encrypted = openssl_encrypt($data, $cipher, $key, 0, $iv);
        
        if ($encrypted === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AutoPuzzle Encryption Error: Encryption failed. Error: ' . openssl_error_string());
            }
            return '';
        }

        // Return IV and encrypted data combined with a separator, then Base64 encode
        return base64_encode($encrypted . '::' . $iv);
    }

    /**
     * Decrypts data using AES-256-CBC.
     * 
     * @param string $encrypted_data The encrypted data (Base64 encoded).
     * @return string The decrypted data, or empty string on failure.
     */
    public static function decrypt($encrypted_data) {
        if (empty($encrypted_data)) {
            return '';
        }
        
        $key = self::get_encryption_key();
        $cipher = 'aes-256-cbc';
        
        // Decode and separate IV and encrypted data
        $decoded = base64_decode($encrypted_data, true);
        if ($decoded === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AutoPuzzle Decryption Error: Failed to decode base64 data.');
            }
            return '';
        }
        
        $parts = explode('::', $decoded, 2);
        
        if (count($parts) !== 2) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AutoPuzzle Decryption Error: Invalid encrypted data format.');
            }
            return ''; // Invalid format
        }
        
        $encrypted = $parts[0];
        $iv = $parts[1];
        
        // Verify IV length
        $iv_length = openssl_cipher_iv_length($cipher);
        if ($iv_length === false || strlen($iv) !== $iv_length) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AutoPuzzle Decryption Error: Invalid IV length.');
            }
            return '';
        }

        // Decrypt
        $decrypted = openssl_decrypt($encrypted, $cipher, $key, 0, $iv);
        
        if ($decrypted === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $error_string = openssl_error_string();
                error_log('AutoPuzzle Decryption Error: Decryption failed. Error: ' . ($error_string ?: 'Unknown error'));
            }
            return '';
        }
        
        return $decrypted;
    }
}

