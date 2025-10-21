<?php
/**
 * Handles the submission and creation of cash inquiry posts.
 *
 * @package Maneli_Car_Inquiry/Includes/Public
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Cash_Inquiry_Handler {

    public function __construct() {
        // Hooks for handling the cash inquiry form submission.
        add_action('admin_post_nopriv_maneli_submit_cash_inquiry', '__return_false');
        add_action('admin_post_maneli_submit_cash_inquiry', [$this, 'handle_cash_inquiry_submission']);
        
        // AJAX handler for customer cash inquiry creation
        add_action('wp_ajax_maneli_create_customer_cash_inquiry', [$this, 'ajax_create_customer_cash_inquiry']);
        add_action('wp_ajax_nopriv_maneli_create_customer_cash_inquiry', '__return_false');
    }
    
    /**
     * AJAX handler for creating cash inquiry from customer dashboard
     */
    public function ajax_create_customer_cash_inquiry() {
        check_ajax_referer('maneli_customer_cash_inquiry', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'لطفاً ابتدا وارد سیستم شوید.']);
            return;
        }
        
        // Validate required fields
        $required_fields = ['product_id', 'first_name', 'last_name', 'mobile_number'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(['message' => 'لطفاً تمام فیلدهای ضروری را پر کنید.']);
                return;
            }
        }
        
        $inquiry_data = [
            'product_id'         => intval($_POST['product_id']),
            'cash_first_name'    => sanitize_text_field($_POST['first_name']),
            'cash_last_name'     => sanitize_text_field($_POST['last_name']),
            'cash_mobile_number' => sanitize_text_field($_POST['mobile_number']),
            'cash_car_color'     => sanitize_text_field($_POST['car_color'] ?? ''),
        ];
        
        // Optional description
        if (!empty($_POST['description'])) {
            $inquiry_data['description'] = sanitize_textarea_field($_POST['description']);
        }
        
        $user_id = get_current_user_id();
        $post_id = self::create_cash_inquiry_post($inquiry_data, $user_id);
        
        if ($post_id) {
            // Add description as meta if provided
            if (!empty($inquiry_data['description'])) {
                update_post_meta($post_id, 'customer_description', $inquiry_data['description']);
            }
            
            wp_send_json_success([
                'message' => 'درخواست نقدی شما با موفقیت ثبت شد. به زودی کارشناس با شما تماس خواهد گرفت.',
                'inquiry_id' => $post_id
            ]);
        } else {
            wp_send_json_error(['message' => 'خطا در ثبت درخواست. لطفاً دوباره تلاش کنید.']);
        }
    }

    /**
     * Handles the validation and processing of the cash inquiry submission form.
     */
    public function handle_cash_inquiry_submission() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'maneli_cash_inquiry_nonce')) {
            wp_die(esc_html__('Security check failed!', 'maneli-car-inquiry'));
        }
    
        if (!is_user_logged_in()) {
            wp_die(esc_html__('You must be logged in to submit a request.', 'maneli-car-inquiry'));
        }
    
        $required_fields = ['product_id', 'cash_first_name', 'cash_last_name', 'cash_mobile_number', 'cash_car_color'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_die(esc_html__('Please fill out all required fields.', 'maneli-car-inquiry'));
            }
        }
    
        $inquiry_data = [
            'product_id'       => intval($_POST['product_id']),
            'cash_first_name'  => sanitize_text_field($_POST['cash_first_name']),
            'cash_last_name'   => sanitize_text_field($_POST['cash_last_name']),
            'cash_mobile_number' => sanitize_text_field($_POST['cash_mobile_number']),
            'cash_car_color'   => sanitize_text_field($_POST['cash_car_color']),
        ];
    
        $user_id = get_current_user_id();
        $post_id = self::create_cash_inquiry_post($inquiry_data, $user_id);

        if ($post_id) {
            // Redirect to the cash inquiry list page with a success message
            $redirect_url = add_query_arg('cash_inquiry_sent', 'true', home_url('/dashboard/inquiries/cash'));
            wp_redirect($redirect_url);
            exit;
        } else {
            // Handle error, maybe redirect back with an error query arg
            wp_die(esc_html__('An error occurred while creating your request. Please try again.', 'maneli-car-inquiry'));
        }
    }

    /**
     * Creates the 'cash_inquiry' custom post type entry and its associated meta data.
     *
     * @param array $inquiry_data The sanitized data from the form.
     * @param int   $user_id      The ID of the user submitting the request.
     * @return int|false The new post ID on success, or false on failure.
     */
    public static function create_cash_inquiry_post($inquiry_data, $user_id) {
        $first_name = $inquiry_data['cash_first_name'];
        $last_name = $inquiry_data['cash_last_name'];
        $mobile = $inquiry_data['cash_mobile_number'];
        $product_id = $inquiry_data['product_id'];
        $car_name = get_the_title($product_id);
    
        // Update user's profile with their name if it's not already set
        $user_obj = get_userdata($user_id);
        if ($user_obj && empty($user_obj->first_name) && empty($user_obj->last_name)) {
            wp_update_user([
                'ID'         => $user_id,
                'first_name' => $first_name,
                'last_name'  => $last_name,
            ]);
        }
    
        $post_title = sprintf(
            '%s: %s %s for %s',
            esc_html__('Cash Request', 'maneli-car-inquiry'),
            $first_name,
            $last_name,
            $car_name
        );

        $post_data = [
            'post_title'   => $post_title,
            'post_status'  => 'publish',
            'post_author'  => $user_id,
            'post_type'    => 'cash_inquiry'
        ];
        
        $post_id = wp_insert_post($post_data, true); // Second param true to return WP_Error on failure
    
        if (is_wp_error($post_id)) {
            error_log('Maneli Cash Inquiry Error: ' . $post_id->get_error_message());
            return false;
        }
        
        // Add post meta data
        $meta_data = [
            'product_id'          => $product_id,
            'cash_first_name'     => $first_name,
            'cash_last_name'      => $last_name,
            'mobile_number'       => $mobile,
            'cash_car_color'      => $inquiry_data['cash_car_color'],
            'cash_inquiry_status' => 'new', // Initial status: جدید
        ];
        foreach ($meta_data as $key => $value) {
            update_post_meta($post_id, $key, $value);
        }
            
        // Send SMS notification to the admin
        self::send_admin_notification($first_name . ' ' . $last_name, $car_name);
        
        return $post_id;
    }

    /**
     * Sends an SMS notification to the admin about the new cash inquiry.
     *
     * @param string $customer_name The full name of the customer.
     * @param string $car_name      The name of the requested car.
     */
    private static function send_admin_notification($customer_name, $car_name) {
        $options = get_option('maneli_inquiry_all_options', []);
        $admin_mobile = $options['admin_notification_mobile'] ?? '';
        $pattern_admin = $options['sms_pattern_new_inquiry'] ?? 0;
        
        if (!empty($admin_mobile) && $pattern_admin > 0) {
            $sms_handler = new Maneli_SMS_Handler();
            $car_name_suffix = sprintf('%s (%s)', $car_name, esc_html__('Cash', 'maneli-car-inquiry'));
            $sms_handler->send_pattern($pattern_admin, $admin_mobile, [$customer_name, $car_name_suffix]);
        }
    }
}