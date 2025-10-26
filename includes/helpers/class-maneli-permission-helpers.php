<?php
/**
 * A helper class with static methods for handling complex permission and capability checks.
 *
 * This centralizes the logic for determining if a user can view or interact with specific
 * plugin-related posts (like inquiries), making the code cleaner and more secure.
 *
 * @package Maneli_Car_Inquiry/Includes/Helpers
 * @author  Gemini
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Permission_Helpers {

    /**
     * Checks if a given user has permission to view a specific inquiry (either installment or cash).
     *
     * A user can view an inquiry if they are:
     * 1. The author (customer) of the inquiry.
     * 2. An administrator or Maneli Manager (with 'manage_maneli_inquiries' capability).
     * 3. The expert specifically assigned to that inquiry.
     *
     * @param int $post_id   The ID of the inquiry or cash_inquiry post.
     * @param int $user_id   The ID of the user to check.
     * @return bool True if the user has permission, false otherwise.
     */
    public static function can_user_view_inquiry($post_id, $user_id) {
        // Rule 2: Admins and Maneli Managers can view everything.
        if (user_can($user_id, 'manage_maneli_inquiries')) {
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
        if (!$user || !in_array('maneli_expert', $user->roles, true)) {
            return false; // User is not an expert, so they can't be the assigned one.
        }

        $assigned_expert_id = (int)get_post_meta($post_id, 'assigned_expert_id', true);

        return $assigned_expert_id === $user_id;
    }
}