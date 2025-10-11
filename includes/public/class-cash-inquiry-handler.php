<?php
if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Cash_Inquiry_Handler {

    public function __construct() {
        add_action('admin_post_maneli_submit_cash_inquiry', [$this, 'handle_cash_inquiry_submission']);
    }

    public function handle_cash_inquiry_submission() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'maneli_cash_inquiry_nonce')) {
            wp_die('خطای امنیتی!');
        }
    
        if (!is_user_logged_in()) {
            wp_die('برای ثبت درخواست، لطفاً ابتدا وارد شوید.');
        }
    
        $fields = ['product_id', 'cash_first_name', 'cash_last_name', 'cash_mobile_number', 'cash_car_color'];
        foreach ($fields as $field) {
            if (empty($_POST[$field])) {
                wp_die('لطفاً تمام فیلدها را پر کنید.');
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
        self::create_cash_inquiry_post($inquiry_data, $user_id);
        wp_redirect(add_query_arg('cash_inquiry_sent', 'true', home_url('/dashboard/?endp=inf_menu_4')));
        exit;
    }

    public static function create_cash_inquiry_post($inquiry_data, $user_id) {
        $first_name = $inquiry_data['cash_first_name'];
        $last_name = $inquiry_data['cash_last_name'];
        $mobile = $inquiry_data['cash_mobile_number'];
        $product_id = $inquiry_data['product_id'];
        $car_name = get_the_title($product_id);
    
        $user_obj = get_userdata($user_id);
        if(empty($user_obj->first_name) && empty($user_obj->last_name)) {
            wp_update_user([
                'ID'         => $user_id,
                'first_name' => $first_name,
                'last_name'  => $last_name,
            ]);
        }
    
        $post_title = 'درخواست نقدی: ' . $first_name . ' ' . $last_name . ' برای ' . $car_name;
        $post_id = wp_insert_post([
            'post_title'   => $post_title,
            'post_status'  => 'publish',
            'post_author'  => $user_id,
            'post_type'    => 'cash_inquiry'
        ]);
    
        if ($post_id && !is_wp_error($post_id)) {
            update_post_meta($post_id, 'product_id', $product_id);
            update_post_meta($post_id, 'cash_first_name', $first_name);
            update_post_meta($post_id, 'cash_last_name', $last_name);
            update_post_meta($post_id, 'mobile_number', $mobile);
            update_post_meta($post_id, 'cash_car_color', $inquiry_data['cash_car_color']);
            update_post_meta($post_id, 'cash_inquiry_status', 'pending');
            
            $all_options = get_option('maneli_inquiry_all_options', []);
            $admin_mobile = $all_options['admin_notification_mobile'] ?? '';
            $pattern_admin = $all_options['sms_pattern_new_inquiry'] ?? 0;
            if (!empty($admin_mobile) && $pattern_admin > 0) {
                $sms_handler = new Maneli_SMS_Handler();
                $sms_handler->send_pattern($pattern_admin, $admin_mobile, [$first_name . ' ' . $last_name, $car_name . ' (نقدی)']);
            }
        }
        
        return $post_id;
    }
}