<?php
/**
 * Handles the registration of Custom Post Types (CPTs) for 'inquiry' and 'cash_inquiry'.
 *
 * All CPTs are registered with 'show_ui' set to false to prevent access via the WordPress Admin Dashboard,
 * ensuring a complete frontend-only administrative experience.
 *
 * @package Maneli_Car_Inquiry/Includes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.1.0 (Removed all admin UI/Meta Box logic)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_CPT_Handler {

    public function __construct() {
        // Only register the post types, removing all admin column/meta box hooks.
        add_action('init', [$this, 'register_post_types']);
    }

    /**
     * Registers both 'inquiry' (installment) and 'cash_inquiry' (cash purchase) post types.
     */
    public function register_post_types() {
        $this->register_inquiry_post_type();
        $this->register_cash_inquiry_post_type();
        $this->register_meeting_post_type();
    }

    /**
     * Registers the 'inquiry' post type for installment requests.
     */
    private function register_inquiry_post_type() {
        $labels = [
            'name'               => esc_html__('Installment Inquiries', 'maneli-car-inquiry'),
            'singular_name'      => esc_html__('Installment Inquiry', 'maneli-car-inquiry'),
            'menu_name'          => esc_html__('Bank Inquiries', 'maneli-car-inquiry'),
            'all_items'          => esc_html__('All Inquiries', 'maneli-car-inquiry'),
        ];
        $args = [
            'labels'             => $labels,
            'supports'           => ['title', 'editor', 'author'],
            'public'             => false,
            'show_ui'            => false, // IMPORTANT: Disable Admin UI
            'show_in_menu'       => false, // IMPORTANT: Remove from Admin Menu
            'capability_type'    => 'post',
            'capabilities'       => [
                'create_posts' => 'do_not_allow', 
            ],
            'map_meta_cap'       => true,
            'menu_icon'          => 'dashicons-clipboard',
            'rewrite'            => false,
        ];
        register_post_type('inquiry', $args);
    }
    
    /**
     * Registers the 'cash_inquiry' post type for cash purchase requests.
     */
    private function register_cash_inquiry_post_type() {
        $labels = [
            'name'               => esc_html__('Cash Requests', 'maneli-car-inquiry'),
            'singular_name'      => esc_html__('Cash Request', 'maneli-car-inquiry'),
            'menu_name'          => esc_html__('Cash Requests', 'maneli-car-inquiry'),
            'all_items'          => esc_html__('All Cash Requests', 'maneli-car-inquiry'),
        ];
        $args = [
            'labels'             => $labels,
            'supports'           => ['title', 'author'],
            'public'             => false,
            'show_ui'            => false, // IMPORTANT: Disable Admin UI
            'show_in_menu'       => false, // IMPORTANT: Remove from Admin Menu
            'capability_type'    => 'post',
            'capabilities'       => ['create_posts' => 'do_not_allow'],
            'map_meta_cap'       => true,
            'rewrite'            => false,
        ];
        register_post_type('cash_inquiry', $args);
    }

    /**
     * Registers the 'maneli_meeting' post type to store meeting slots booked for customers.
     */
    private function register_meeting_post_type() {
        $labels = [
            'name'          => esc_html__('Meetings', 'maneli-car-inquiry'),
            'singular_name' => esc_html__('Meeting', 'maneli-car-inquiry'),
        ];
        $args = [
            'labels'          => $labels,
            'supports'        => ['title', 'author'],
            'public'          => false,
            'show_ui'         => false,
            'show_in_menu'    => false,
            'capability_type' => 'post',
            'capabilities'    => ['create_posts' => 'do_not_allow'],
            'map_meta_cap'    => true,
            'rewrite'         => false,
        ];
        register_post_type('maneli_meeting', $args);
    }

    /**
     * Returns an array of all possible statuses for an installment inquiry.
     * @return array
     */
    public static function get_all_statuses() {
        return [
            'pending'        => esc_html__('Pending Review', 'maneli-car-inquiry'),
            'user_confirmed' => esc_html__('Approved and Referred', 'maneli-car-inquiry'),
            'more_docs'      => esc_html__('More Documents Required', 'maneli-car-inquiry'),
            'rejected'       => esc_html__('Rejected', 'maneli-car-inquiry'),
            'failed'         => esc_html__('Inquiry Failed', 'maneli-car-inquiry'),
        ];
    }
    
    /**
     * Gets the human-readable label for a given installment status key.
     * @param string $status_key The status key (e.g., 'user_confirmed').
     * @return string The status label.
     */
    public static function get_status_label($status_key) {
        $statuses = self::get_all_statuses();
        return $statuses[$status_key] ?? esc_html__('Unknown', 'maneli-car-inquiry');
    }

    /**
     * Returns an array of all possible statuses for a cash inquiry.
     * @return array
     */
    public static function get_all_cash_inquiry_statuses() {
        return [
            'pending'          => esc_html__('Follow-up in Progress', 'maneli-car-inquiry'),
            'approved'         => esc_html__('Referred', 'maneli-car-inquiry'),
            'rejected'         => esc_html__('Rejected', 'maneli-car-inquiry'),
            'awaiting_payment' => esc_html__('Awaiting Payment', 'maneli-car-inquiry'),
            'completed'        => esc_html__('Completed', 'maneli-car-inquiry'),
        ];
    }

    /**
     * Gets the human-readable label for a given cash inquiry status key.
     * @param string $status_key The status key.
     * @return string The status label.
     */
    public static function get_cash_inquiry_status_label($status_key) {
        $statuses = self::get_all_cash_inquiry_statuses();
        return $statuses[$status_key] ?? esc_html__('Unknown', 'maneli-car-inquiry');
    }
}