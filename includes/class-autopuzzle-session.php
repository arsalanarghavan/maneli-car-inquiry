<?php
/**
 * Session management for the plugin
 *
 * @package Autopuzzle_Car_Inquiry
 */

if (!defined('ABSPATH')) {
    exit;
}

class Autopuzzle_Session {

    /**
     * Start session if not started
     */
    public function start_session() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            // Configure secure session cookie parameters
            $is_https = is_ssl() || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
            $secure = $is_https; // Only send cookies over HTTPS if available
            $httponly = true; // Prevent JavaScript access to cookies
            $samesite = 'Lax'; // CSRF protection
            
            session_set_cookie_params([
                'lifetime' => 0, // Session cookie (expires when browser closes)
                'path' => '/',
                'domain' => '',
                'secure' => $secure,
                'httponly' => $httponly,
                'samesite' => $samesite
            ]);
            
            session_start();
        }
    }

    /**
     * Set session value
     */
    public function set($key, $value) {
        $_SESSION['maneli'][$key] = $value;
    }

    /**
     * Get session value
     */
    public function get($key, $default = null) {
        return isset($_SESSION['maneli'][$key]) ? $_SESSION['maneli'][$key] : $default;
    }

    /**
     * Check if user is logged in
     */
    public function is_logged_in() {
        return isset($_SESSION['maneli']['user_id']);
    }

    /**
     * Get user ID
     */
    public function get_user_id() {
        return $this->get('user_id');
    }

    /**
     * Get user role
     */
    public function get_user_role() {
        return $this->get('role');
    }

    /**
     * Set user data
     */
    public function set_user_data($user_id, $role, $data = array()) {
        $this->set('user_id', $user_id);
        $this->set('role', $role);
        $this->set('login_time', time());
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }
        
        // Regenerate session ID to prevent session fixation attacks
        if (function_exists('session_regenerate_id')) {
            session_regenerate_id(true);
        }
    }

    /**
     * Destroy session
     */
    public function destroy() {
        unset($_SESSION['maneli']);
        session_destroy();
    }

    /**
     * Check session timeout
     */
    public function check_timeout() {
        if ($this->is_logged_in()) {
            $login_time = $this->get('login_time');
            $timeout = get_option('autopuzzle_session_timeout', 3600);
            
            if (time() - $login_time > $timeout) {
                $this->destroy();
                return false;
            }
        }
        return true;
    }

    /**
     * OPTIMIZED: Cleanup old session files to prevent memory bloat
     * Called on init hook with low frequency to avoid performance impact
     */
    public static function cleanup_old_sessions() {
        // Run cleanup every 24 hours only
        $last_cleanup = get_transient('autopuzzle_session_cleanup_last_run');
        if ($last_cleanup !== false) {
            return; // Already cleaned up recently
        }
        
        // Mark cleanup as done for next 24 hours
        set_transient('autopuzzle_session_cleanup_last_run', true, 24 * HOUR_IN_SECONDS);
        
        // Get session save path
        $session_path = ini_get('session.save_path');
        if (empty($session_path)) {
            $session_path = sys_get_temp_dir();
        }
        
        // Remove session files older than 48 hours
        $max_age = 48 * HOUR_IN_SECONDS;
        $now = time();
        
        if (is_dir($session_path)) {
            $files = glob($session_path . '/sess_*');
            if (is_array($files)) {
                foreach ($files as $file) {
                    if (is_file($file) && ($now - filemtime($file)) > $max_age) {
                        @unlink($file);  // Suppress errors if file can't be deleted
                    }
                }
            }
        }
    }
}
