<?php
/**
 * A helper class with static methods for handling complex permission and capability checks.
 *
 * This centralizes the logic for determining if a user can view or interact with specific
 * plugin-related posts (like inquiries), making the code cleaner and more secure.
 *
 * @package Autopuzzle_Car_Inquiry/Includes/Helpers
 * @author  Gemini
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Autopuzzle_Permission_Helpers {

    /**
     * Cache for license status checks in same request
     *
     * @var array
     */
    private static $license_cache = [
        'is_active' => null,
        'is_demo' => null,
        'checked' => false
    ];

    /**
     * Get license instance with caching
     *
     * @return Autopuzzle_License|null
     */
    private static function get_license() {
        if (!class_exists('Autopuzzle_License')) {
            return null;
        }
        return Autopuzzle_License::instance();
    }

    /**
     * Checks if a given user has permission to view a specific inquiry (either installment or cash).
     *
     * A user can view an inquiry if they are:
     * 1. The author (customer) of the inquiry.
     * 2. An administrator or AutoPuzzle Manager (with 'manage_autopuzzle_inquiries' capability).
     * 3. The expert specifically assigned to that inquiry.
     *
     * @param int $post_id   The ID of the inquiry or cash_inquiry post.
     * @param int $user_id   The ID of the user to check.
     * @return bool True if the user has permission, false otherwise.
     */
    public static function can_user_view_inquiry($post_id, $user_id) {
        // Rule 2: Admins and AutoPuzzle Managers can view everything.
        if (user_can($user_id, 'manage_autopuzzle_inquiries')) {
            return true;
        }

        $post = get_post($post_id);
        if (!$post) {
            return false;
        }

        // Rule 1: The author (customer) of the post can view it.
        if ((int)$post->post_author === $user_id) {
            return true;
        }

        // Rule 3: The assigned expert can view it.
        if (self::is_assigned_expert($post_id, $user_id)) {
            return true;
        }

        return false;
    }

    /**
     * Checks if a specific user is the assigned expert for a given inquiry post.
     *
     * @param int $post_id   The ID of the inquiry or cash_inquiry post.
     * @param int $user_id   The ID of the user to check.
     * @return bool True if the user is the assigned expert, false otherwise.
     */
    public static function is_assigned_expert($post_id, $user_id) {
        $user = get_userdata($user_id);
        if (!$user || !in_array('autopuzzle_expert', $user->roles, true)) {
            return false; // User is not an expert, so they can't be the assigned one.
        }

        $assigned_expert_id = (int)get_post_meta($post_id, 'assigned_expert_id', true);

        return $assigned_expert_id === $user_id;
    }

    /**
     * Checks if user can delete items (checks license and demo mode)
     *
     * @param int $user_id
     * @param string $item_type
     * @return bool
     */
    public static function can_user_delete($user_id, $item_type = 'inquiry') {
        // Check demo mode
        if (self::is_demo_mode()) {
            return false; // No deletion in demo mode
        }
        
        // Check license
        if (!self::is_license_active()) {
            return false;
        }
        
        // Check user capability
        if (user_can($user_id, 'manage_autopuzzle_inquiries')) {
            return true;
        }
        
        return false;
    }

    /**
     * Checks if user can edit items (checks license and demo mode)
     *
     * @param int $user_id
     * @param string $item_type
     * @return bool
     */
    public static function can_user_edit($user_id, $item_type = 'inquiry') {
        // Check demo mode
        if (self::is_demo_mode()) {
            return false; // No editing in demo mode
        }
        
        // Check license
        if (!self::is_license_active()) {
            return false;
        }
        
        // Check user capability
        if (user_can($user_id, 'manage_autopuzzle_inquiries') || user_can($user_id, 'edit_posts')) {
            return true;
        }
        
        return false;
    }

    /**
     * Checks if demo mode is active (with caching)
     *
     * @return bool
     */
    public static function is_demo_mode() {
        // Return cached value if already checked
        if (self::$license_cache['checked'] && self::$license_cache['is_demo'] !== null) {
            return self::$license_cache['is_demo'];
        }

        $license = self::get_license();
        if (!$license) {
            self::$license_cache['is_demo'] = false;
            self::$license_cache['checked'] = true;
            return false;
        }

        $is_demo = $license->is_demo_mode();
        self::$license_cache['is_demo'] = $is_demo;
        self::$license_cache['checked'] = true;
        return $is_demo;
    }

    /**
     * Checks if license is active (with caching)
     *
     * @return bool
     */
    public static function is_license_active() {
        // Return cached value if already checked
        if (self::$license_cache['checked'] && self::$license_cache['is_active'] !== null) {
            return self::$license_cache['is_active'];
        }

        $license = self::get_license();
        if (!$license) {
            self::$license_cache['is_active'] = false;
            self::$license_cache['checked'] = true;
            return false;
        }

        $is_active = $license->is_license_active();
        self::$license_cache['is_active'] = $is_active;
        self::$license_cache['checked'] = true;
        return $is_active;
    }

    /**
     * Clear license cache (useful for testing or after license changes)
     */
    public static function clear_license_cache() {
        self::$license_cache = [
            'is_active' => null,
            'is_demo' => null,
            'checked' => false
        ];
    }
}