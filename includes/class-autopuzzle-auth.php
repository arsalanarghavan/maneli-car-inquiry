<?php
/**
 * Authentication handling
 *
 * @package Autopuzzle_Car_Inquiry
 */

if (!defined('ABSPATH')) {
    exit;
}

class Autopuzzle_Auth {

    /**
     * Session instance
     */
    private $session;

    /**
     * Constructor
     */
    public function __construct() {
        $this->session = new Autopuzzle_Session();
    }

    /**
     * Check if current page requires authentication
     */
    public function requires_auth($page = 'dashboard') {
        $public_pages = array('login');
        
        if (!in_array($page, $public_pages) && !$this->session->is_logged_in()) {
            return true;
        }
        
        return false;
    }

    /**
     * Redirect to login if not authenticated
     */
    public function check_auth() {
        if ($this->requires_auth()) {
            wp_redirect(home_url('/login'));
            exit;
        }
        
        // Check session timeout
        if (!$this->session->check_timeout()) {
            wp_redirect(home_url('/login'));
            exit;
        }
    }

    /**
     * Check user capability
     */
    public function has_capability($required_role) {
        $user_role = $this->session->get_user_role();
        
        $roles = array('administrator' => 4, 'autopuzzle_manager' => 3, 'autopuzzle_expert' => 2, 'autopuzzle_customer' => 1);
        
        $user_level = isset($roles[$user_role]) ? $roles[$user_role] : 0;
        $required_level = isset($roles[$required_role]) ? $roles[$required_role] : 99;
        
        return $user_level >= $required_level;
    }

    /**
     * Get current user data
     */
    public function get_current_user() {
        if (!$this->session->is_logged_in()) {
            return null;
        }
        
        return array(
            'user_id' => $this->session->get_user_id(),
            'role' => $this->session->get_user_role(),
        );
    }
}
