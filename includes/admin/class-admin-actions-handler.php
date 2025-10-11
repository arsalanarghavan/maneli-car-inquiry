<?php
if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Admin_Actions_Handler {

    public function __construct() {
        // Admin Workflow Hooks
        add_action('admin_post_maneli_admin_update_status', [$this, 'handle_admin_update_status']);
        add_action('admin_post_maneli_admin_update_user', [$this, 'handle_admin_update_user_profile']);
        add_action('admin_post_maneli_admin_create_user', [$this, 'handle_admin_create_user']);
        add_action('admin_post_maneli_admin_retry_finotex', [$this, 'handle_admin_retry_finotex']);
        
        // Expert Workflow Hooks
        add_action('admin_post_nopriv_maneli_expert_create_inquiry', '__return_false');
        add_action('admin_post_maneli_expert_create_inquiry', [$this, 'handle_expert_create_inquiry']);
    }

    public function handle_admin_update_status() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'maneli_admin_update_status_nonce')) wp_die('خطای امنیتی!');
        
        if (!current_user_can('manage_maneli_inquiries') || empty($_POST['inquiry_id']) || empty($_POST['new_status'])) { 
            wp_die('درخواست نامعتبر یا دسترسی غیرمجاز.'); 
        }
        
        $post_id = intval($_POST['inquiry_id']);
        $new_status_request = sanitize_text_field($_POST['new_status']);
        if (!in_array($new_status_request, ['approved', 'rejected', 'more_docs'])) { wp_die('وضعیت نامعتبر است.'); }
        
        $post = get_post($post_id);
        $user_id = $post->post_author;
        $options = get_option('maneli_inquiry_all_options', []);
        $final_status = $new_status_request;

        if ($new_status_request === 'approved') {
            $final_status = 'user_confirmed';
            $selected_expert_id = isset($_POST['assigned_expert_id']) ? sanitize_text_field($_POST['assigned_expert_id']) : 'auto';

            if ($selected_expert_id !== 'auto' && !empty($selected_expert_id)) {
                $expert_user = get_userdata(intval($selected_expert_id));
                if ($expert_user && in_array('maneli_expert', $expert_user->roles)) {
                    update_post_meta($post_id, 'assigned_expert_id', $expert_user->ID);
                    update_post_meta($post_id, 'assigned_expert_name', $expert_user->display_name);
                }
            } else {
                 if (!get_post_meta($post_id, 'assigned_expert_id', true)) {
                    $this->assign_expert_round_robin($post_id);
                }
            }
        } elseif ($final_status === 'rejected' && !empty($_POST['rejection_reason'])) {
            $reason = sanitize_textarea_field($_POST['rejection_reason']);
            update_post_meta($post_id, 'rejection_reason', $reason);
        }
        
        $options = get_option('maneli_inquiry_all_options', []);
        $pattern_id = $options['sms_pattern_expert_referral'] ?? 0;
        $assigned_expert_id_for_sms = get_post_meta($post_id, 'assigned_expert_id', true);

        if ($pattern_id > 0 && $assigned_expert_id_for_sms) {
            $expert_phone = get_user_meta($assigned_expert_id_for_sms, 'mobile_number', true);
            if (!empty($expert_phone)) {
                $expert_info = get_userdata($assigned_expert_id_for_sms);
                $customer_info = get_userdata($user_id);
                $customer_name = ($customer_info->first_name ?? '') . ' ' . ($customer_info->last_name ?? '');
                $customer_mobile = get_post_meta($post_id, 'mobile_number', true) ?? '';
                $car_name = get_the_title(get_post_meta($post_id, 'product_id', true)) ?? '';
                $params = [(string)$expert_info->display_name, (string)$customer_name, (string)$customer_mobile, (string)$car_name];
                $sms_handler = new Maneli_SMS_Handler();
                $sms_handler->send_pattern($pattern_id, $expert_phone, $params);
            }
        }
        
        update_post_meta($post_id, 'inquiry_status', $final_status);
        
        $user_info = get_userdata($user_id);
        $user_name = $user_info->display_name ?? '';
        $mobile_number = get_user_meta($user_id, 'mobile_number', true);
        $car_name = get_the_title(get_post_meta($post_id, 'product_id', true)) ?? '';
        $pattern_id = 0; $params = [];

        switch ($final_status) {
            case 'user_confirmed':
                $pattern_id = $options['sms_pattern_approved'] ?? 0;
                $params = [(string)$user_name, (string)$car_name];
                break;
            case 'rejected':
                $pattern_id = $options['sms_pattern_rejected'] ?? 0;
                $rejection_reason = get_post_meta($post_id, 'rejection_reason', true) ?? '';
                $params = [(string)$user_name, (string)$car_name, (string)$rejection_reason];
                break;
            case 'more_docs':
                $pattern_id = $options['sms_pattern_more_docs'] ?? 0;
                $params = [(string)$user_name, (string)$car_name];
                break;
        }

        if ($pattern_id > 0 && !empty($mobile_number)) {
            $sms_handler = new Maneli_SMS_Handler();
            $sms_handler->send_pattern($pattern_id, $mobile_number, $params);
        }
        
        $redirect_url = home_url('/dashboard/?endp=inf_menu_4&inquiry_id=' . $post_id);
        wp_redirect($redirect_url);
        exit;
    }

    public function handle_expert_create_inquiry() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'maneli_expert_create_nonce')) wp_die('خطای امنیتی!');
        if (!is_user_logged_in() || !(current_user_can('maneli_expert') || current_user_can('manage_maneli_inquiries'))) { wp_die('شما اجازه دسترسی به این قابلیت را ندارید.'); }
        
        $submitter = wp_get_current_user();
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        if (empty($product_id)) wp_die('لطفاً یک خودرو انتخاب کنید.');
    
        $buyer_fields = ['first_name', 'last_name', 'national_code', 'father_name', 'birth_date', 'mobile_number'];
        $buyer_data = [];
        foreach ($buyer_fields as $key) { if (empty($_POST[$key])) wp_die("خطا: لطفاً تمام فیلدهای خریدار را پر کنید."); $buyer_data[$key] = sanitize_text_field($_POST[$key]); }
        
        $issuer_type = isset($_POST['issuer_type']) ? sanitize_text_field($_POST['issuer_type']) : 'self';
        $issuer_data = [];
        if ($issuer_type === 'other') {
            $issuer_fields = ['issuer_first_name', 'issuer_last_name', 'issuer_national_code', 'issuer_father_name', 'issuer_birth_date', 'issuer_mobile_number'];
            foreach ($issuer_fields as $key) { if (empty($_POST[$key])) wp_die("خطا: لطفاً تمام فیلدهای صادرکننده چک را پر کنید."); $issuer_data[$key] = sanitize_text_field($_POST[$key]); }
        }
        
        $customer_id = username_exists($buyer_data['mobile_number']);
        $dummy_email = $buyer_data['mobile_number'] . '@manelikhodro.com';

        if (!$customer_id) { $customer_id = email_exists($dummy_email); }
        if (!$customer_id) {
            $random_password = wp_generate_password(12, false);
            $customer_id = wp_create_user($buyer_data['mobile_number'], $random_password, $dummy_email);
            if (is_wp_error($customer_id)) { wp_die('خطا در ساخت کاربر جدید: ' . $customer_id->get_error_message()); }
            wp_update_user(['ID' => $customer_id, 'first_name' => $buyer_data['first_name'], 'last_name' => $buyer_data['last_name'], 'role' => 'customer', 'user_email' => $dummy_email]);
        } else {
            wp_update_user(['ID' => $customer_id, 'first_name' => $buyer_data['first_name'], 'last_name' => $buyer_data['last_name']]);
        }
        foreach ($buyer_data as $key => $value) { update_user_meta($customer_id, $key, $value); }
    
        $national_code_for_api = ($issuer_type === 'other' && !empty($issuer_data['issuer_national_code'])) ? $issuer_data['issuer_national_code'] : $buyer_data['national_code'];
        $inquiry_handler = new Maneli_Installment_Inquiry_Handler(); // To access execute_finotex_inquiry
        $finotex_result = $inquiry_handler->execute_finotex_inquiry($national_code_for_api);
        $post_title = 'استعلام برای ' . $buyer_data['first_name'] . ' ' . $buyer_data['last_name'] . ' (توسط ' . $submitter->display_name . ')';
        $post_content = "گزارش استعلام از فینوتک:\n<pre>" . esc_textarea($finotex_result['raw_response']) . "</pre>";
        $post_id = wp_insert_post(['post_title' => $post_title, 'post_author' => $customer_id, 'post_status' => 'publish', 'post_type' => 'inquiry', 'post_content' => $post_content]);
        
        if ($post_id && !is_wp_error($post_id)) {
            $initial_status = ($finotex_result['status'] === 'DONE') ? 'pending' : 'failed';
            if ($finotex_result['status'] === 'SKIPPED') { $initial_status = 'pending'; }
            update_post_meta($post_id, 'inquiry_status', $initial_status);
            
            if (current_user_can('manage_maneli_inquiries')) {
                $selected_expert_id = isset($_POST['assigned_expert_id']) ? sanitize_text_field($_POST['assigned_expert_id']) : 'auto';
                if ($selected_expert_id !== 'auto' && !empty($selected_expert_id)) {
                    $expert_user = get_userdata(intval($selected_expert_id));
                    if ($expert_user && in_array('maneli_expert', $expert_user->roles)) {
                        update_post_meta($post_id, 'assigned_expert_id', $expert_user->ID);
                        update_post_meta($post_id, 'assigned_expert_name', $expert_user->display_name);
                    }
                } else { $this->assign_expert_round_robin($post_id); }
            } else {
                update_post_meta($post_id, 'created_by_expert_id', $submitter->ID);
                update_post_meta($post_id, 'assigned_expert_id', $submitter->ID);
                update_post_meta($post_id, 'assigned_expert_name', $submitter->display_name);
            }
    
            update_post_meta($post_id, 'product_id', $product_id);
            update_post_meta($post_id, '_finotex_response_data', $finotex_result['data']);
            update_post_meta($post_id, 'issuer_type', $issuer_type);
            foreach ($buyer_data as $key => $value) { update_post_meta($post_id, $key, $value); }
            if (!empty($issuer_data)) { foreach ($issuer_data as $key => $value) { update_post_meta($post_id, $key, $value); } }
            
            $down_payment = sanitize_text_field(str_replace(',', '', $_POST['down_payment']));
            $term_months = sanitize_text_field($_POST['term_months']);
            update_post_meta($post_id, 'maneli_inquiry_down_payment', $down_payment);
            update_post_meta($post_id, 'maneli_inquiry_term_months', $term_months);
        }
        
        $redirect_url = add_query_arg('inquiry_created', '1', wp_get_referer());
        wp_redirect($redirect_url);
        exit;
    }

    public function handle_admin_update_user_profile() {
        if (!isset($_POST['maneli_update_user_nonce']) || !wp_verify_nonce($_POST['maneli_update_user_nonce'], 'maneli_admin_update_user')) {
            wp_die('خطای امنیتی!');
        }
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_die('شما دسترسی لازم برای این کار را ندارید.');
        }

        $user_id_to_update = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        if (!$user_id_to_update) {
            wp_die('شناسه کاربر مشخص نشده است.');
        }
        
        if ($user_id_to_update === get_current_user_id() && isset($_POST['user_role'])) {
            $user_obj = get_userdata($user_id_to_update);
            if (!in_array($_POST['user_role'], $user_obj->roles)) {
                 if ($_POST['user_role'] !== 'maneli_admin' && $_POST['user_role'] !== 'administrator') {
                    wp_die('شما نمی‌توانید نقش کاربری مدیریتی خود را به یک نقش پایین‌تر تغییر دهید.');
                 }
            }
        }
        
        $user_data = [];
        if (isset($_POST['first_name'])) $user_data['first_name'] = sanitize_text_field($_POST['first_name']);
        if (isset($_POST['last_name'])) $user_data['last_name'] = sanitize_text_field($_POST['last_name']);
        if (isset($_POST['email'])) {
             $email = sanitize_email($_POST['email']);
             if (is_email($email)) {
                 $user_data['user_email'] = $email;
             }
        }

        if (isset($_POST['user_role'])) {
            $new_role = sanitize_key($_POST['user_role']);
            if (in_array($new_role, ['customer', 'maneli_expert', 'maneli_admin'])) {
                $user_data['role'] = $new_role;
            }
        }

        if (!empty($user_data)) {
            $user_data['ID'] = $user_id_to_update;
            wp_update_user($user_data);
        }

        $meta_fields = ['national_code', 'father_name', 'birth_date', 'mobile_number'];
        foreach ($meta_fields as $field) {
            if (isset($_POST[$field])) {
                update_user_meta($user_id_to_update, $field, sanitize_text_field($_POST[$field]));
            }
        }
        
        $redirect_url = isset($_POST['_wp_http_referer']) ? esc_url_raw(wp_unslash($_POST['_wp_http_referer'])) : home_url();
        $redirect_url = remove_query_arg('edit_user', $redirect_url);
        wp_redirect(add_query_arg('user-updated', 'true', $redirect_url));
        exit;
    }

    public function handle_admin_create_user() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'maneli_admin_create_user_nonce')) {
            wp_die('خطای امنیتی!');
        }
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_die('شما دسترسی لازم برای این کار را ندارید.');
        }

        $redirect_url = isset($_POST['_wp_http_referer']) ? esc_url_raw(wp_unslash($_POST['_wp_http_referer'])) : home_url();

        $mobile = sanitize_text_field($_POST['mobile_number']);
        $password = $_POST['password'];

        if (empty($mobile) || empty($password)) {
            wp_redirect(add_query_arg('error', urlencode('شماره موبایل و رمز عبور الزامی هستند.'), $redirect_url));
            exit;
        }

        $user_login = $mobile;
        $email = $mobile . '@manelikhodro.com';

        if (username_exists($user_login)) {
            wp_redirect(add_query_arg('error', urlencode('کاربری با این شماره موبایل قبلاً ثبت شده است.'), $redirect_url));
            exit;
        }
        if (email_exists($email)) {
             wp_redirect(add_query_arg('error', urlencode('ایمیلی مرتبط با این شماره موبایل قبلاً ثبت شده است.'), $redirect_url));
            exit;
        }

        $user_id = wp_create_user($user_login, $password, $email);

        if (is_wp_error($user_id)) {
            wp_redirect(add_query_arg('error', urlencode($user_id->get_error_message()), $redirect_url));
            exit;
        }

        $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
        $display_name = trim($first_name . ' ' . $last_name);

        $user_data = [
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => !empty($display_name) ? $display_name : $user_login,
            'role' => 'customer'
        ];
        
        wp_update_user($user_data);
        update_user_meta($user_id, 'mobile_number', $mobile);
        
        wp_redirect(add_query_arg('user-created', 'true', $redirect_url));
        exit;
    }

    public function handle_admin_retry_finotex() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'maneli_retry_finotex_nonce')) wp_die('خطای امنیتی!');
        if (!current_user_can('manage_maneli_inquiries') || empty($_POST['inquiry_id'])) { 
            wp_die('درخواست نامعتبر یا دسترسی غیرمجاز.'); 
        }

        $post_id = intval($_POST['inquiry_id']);
        $post_meta = get_post_meta($post_id);
        $issuer_type = $post_meta['issuer_type'][0] ?? 'self';
        $national_code_for_api = ($issuer_type === 'other' && !empty($post_meta['issuer_national_code'][0])) ? $post_meta['issuer_national_code'][0] : $post_meta['national_code'][0];
        
        $inquiry_handler = new Maneli_Installment_Inquiry_Handler();
        $finotex_result = $inquiry_handler->execute_finotex_inquiry($national_code_for_api);

        $post_content = "گزارش استعلام از فینوتک (تلاش مجدد توسط مدیر):\n<pre>" . esc_textarea($finotex_result['raw_response']) . "</pre>";
        wp_update_post([
            'ID' => $post_id,
            'post_content' => $post_content,
        ]);

        update_post_meta($post_id, '_finotex_response_data', $finotex_result['data']);

        if ($finotex_result['status'] !== 'DONE' && $finotex_result['status'] !== 'SKIPPED') {
            update_post_meta($post_id, 'inquiry_status', 'failed');
        } else {
             update_post_meta($post_id, 'inquiry_status', 'pending');
        }

        $redirect_url = home_url('/dashboard/?endp=inf_menu_4&inquiry_id=' . $post_id);
        wp_redirect($redirect_url);
        exit;
    }

    private function assign_expert_round_robin($post_id) {
        $expert_users = get_users(['role' => 'maneli_expert', 'orderby' => 'ID', 'order' => 'ASC']);
        if (empty($expert_users)) {
            return false;
        }
    
        $last_index = get_option('maneli_expert_last_assigned_index', -1);
        $next_index = ($last_index + 1) % count($expert_users);
        $assigned_expert = $expert_users[$next_index];
        
        update_post_meta($post_id, 'assigned_expert_id', $assigned_expert->ID);
        update_post_meta($post_id, 'assigned_expert_name', $assigned_expert->display_name);
        update_option('maneli_expert_last_assigned_index', $next_index);
    
        $options = get_option('maneli_inquiry_all_options', []);
        $expert_phone = get_user_meta($assigned_expert->ID, 'mobile_number', true);
        $pattern_id = $options['sms_pattern_expert_referral'] ?? 0;
    
        if ($pattern_id > 0 && !empty($expert_phone)) {
            $user_id = get_post_field('post_author', $post_id);
            $customer_info = get_userdata($user_id);
            $customer_name = ($customer_info->first_name ?? '') . ' ' . ($customer_info->last_name ?? '');
            $customer_mobile = get_post_meta($post_id, 'mobile_number', true) ?? '';
            $car_name = get_the_title(get_post_meta($post_id, 'product_id', true)) ?? '';
            $params = [(string)$assigned_expert->display_name, (string)$customer_name, (string)$customer_mobile, (string)$car_name];
            $sms_handler = new Maneli_SMS_Handler();
            $sms_handler->send_pattern($pattern_id, $expert_phone, $params);
        }
        
        return $assigned_expert->ID;
    }
}