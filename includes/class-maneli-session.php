<?php
/**
 * Session management for the plugin
 *
 * @package Maneli_Car_Inquiry
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Session {

    /**
     * Start session if not started
     */
    public function start_session() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
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
            $timeout = get_option('maneli_session_timeout', 3600);
            
            if (time() - $login_time > $timeout) {
                $this->destroy();
                return false;
            }
        }
        return true;
    }
}
