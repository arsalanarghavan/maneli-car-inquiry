<?php
/**
 * Handles form submissions sent via admin-post.php for administrative and expert actions.
 * این فایل شامل منطق کامل شده برای ایجاد استعلام توسط کارشناس است.
 *
 * @package Maneli_Car_Inquiry/Includes/Admin
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.3 (Configurable loan interest rate implementation & Expert meta cleanup)
 */

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
    
    /**
     * Helper function to calculate monthly installment.
     * This logic uses the configurable monthly interest rate from plugin settings.
     * * @param float $loan_amount The loan amount (principal).
     * @param int $term_months The number of months.
     * @return int The calculated monthly installment amount (rounded).
     */
    private function calculate_installment_amount($loan_amount, $term_months) {
        if ($loan_amount <= 0 || $term_months <= 0) {
            return 0;
        }

        $options = get_option('maneli_inquiry_all_options', []);
        // NEW: Get the rate from settings, fallback to hardcoded 0.035
        $monthly_rate = floatval($options['loan_interest_rate'] ?? 0.035); 
        
        // Replicating the simple calculation logic from the JS/Expert Panel (not standard PMT)
        $monthly_interest_amount = $loan_amount * $monthly_rate;
        $total_interest = $monthly_interest_amount * ($term_months + 1);
        $total_repayment = $loan_amount + $total_interest;
        $installment_amount = (int)round($total_repayment / $term_months);

        return $installment_amount;
    }


    /**
     * Handles the final status update for an installment inquiry by an admin from the frontend report page.
     */
    public function handle_admin_update_status() {
        check_admin_referer('maneli_admin_update_status_nonce');
        
        if (!current_user_can('manage_maneli_inquiries') || empty($_POST['inquiry_id']) || empty($_POST['new_status'])) { 
            wp_die(esc_html__('Invalid request or insufficient permissions.', 'maneli-car-inquiry')); 
        }
        
        $post_id = intval($_POST['inquiry_id']);
        $new_status_request = sanitize_text_field($_POST['new_status']);
        $valid_statuses = ['approved', 'rejected', 'more_docs'];

        if (!in_array($new_status_request, $valid_statuses, true)) {
            wp_die(esc_html__('Invalid status provided.', 'maneli-car-inquiry'));
        }
        
        $final_status = $new_status_request;
        $sms_params = [];
        $options = get_option('maneli_inquiry_all_options', []);
        
        if ($new_status_request === 'approved') {
            $final_status = 'user_confirmed';
            $selected_expert_id_str = isset($_POST['assigned_expert_id']) ? sanitize_text_field($_POST['assigned_expert_id']) : 'auto';
            $expert_data = $this->assign_expert_to_post($post_id, $selected_expert_id_str, 'installment');
            if (is_wp_error($expert_data)) {
                wp_die($expert_data->get_error_message());
            }
            $sms_pattern_key = 'sms_pattern_approved';

        } elseif ($final_status === 'rejected') {
            $reason = isset($_POST['rejection_reason']) ? sanitize_textarea_field($_POST['rejection_reason']) : '';
            update_post_meta($post_id, 'rejection_reason', $reason);
            $sms_pattern_key = 'sms_pattern_rejected';
            $sms_params[] = (string)$reason; // Reason is the third parameter for rejection SMS
        } else { // 'more_docs'
             $sms_pattern_key = 'sms_pattern_more_docs';
        }
        
        update_post_meta($post_id, 'inquiry_status', $final_status);
        
        // Send notification to the customer
        $user_id = get_post_field('post_author', $post_id);
        $user_info = get_userdata($user_id);
        $user_name = $user_info->display_name ?? '';
        $mobile_number = get_user_meta($user_id, 'mobile_number', true);
        $car_name = get_the_title(get_post_meta($post_id, 'product_id', true)) ?? '';

        array_unshift($sms_params, (string)$car_name);
        array_unshift($sms_params, (string)$user_name);
        
        $sms_pattern_id = $options[$sms_pattern_key] ?? 0;
        if ($sms_pattern_id > 0 && !empty($mobile_number)) {
            $sms_handler = new Maneli_SMS_Handler();
            $sms_handler->send_pattern($sms_pattern_id, $mobile_number, $sms_params);
        }
        
        $redirect_url = home_url('/dashboard/?endp=inf_menu_4&inquiry_id=' . $post_id);
        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Handles inquiry creation by an expert or admin from the frontend form.
     * **FIXED:** Role demotion bug corrected.
     */
    public function handle_expert_create_inquiry() {
        check_admin_referer('maneli_expert_create_nonce');
        if (!is_user_logged_in() || !(current_user_can('manage_maneli_inquiries') || in_array('maneli_expert', wp_get_current_user()->roles, true))) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'maneli-car-inquiry'));
        }
        
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        if (empty($product_id)) {
            wp_die(esc_html__('Please select a car.', 'maneli-car-inquiry'));
        }
    
        $required_fields = ['first_name', 'last_name', 'national_code', 'mobile_number', 'father_name', 'birth_date'];
        foreach($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_die(sprintf(esc_html__('Error: The field "%s" for the buyer is required.', 'maneli-car-inquiry'), $field));
            }
        }
        
        $mobile_number = sanitize_text_field($_POST['mobile_number']);
        $customer_id = username_exists($mobile_number);
        $dummy_email = $mobile_number . '@manelikhodro.com';

        if (!$customer_id) $customer_id = email_exists($dummy_email);

        $is_new_user = false;
        if (!$customer_id) {
            $random_password = wp_generate_password(12, false);
            $customer_id = wp_create_user($mobile_number, $random_password, $dummy_email);
            if (is_wp_error($customer_id)) {
                wp_die(esc_html__('Error creating new user: ', 'maneli-car-inquiry') . $customer_id->get_error_message());
            }
            $is_new_user = true;
        }
        
        // --- FIX FOR ROLE DEMOTION BUG ---
        $current_user_roles = [];
        if ($customer_id > 0) {
            $current_user = get_userdata($customer_id);
            $current_user_roles = $current_user ? $current_user->roles : [];
        }

        $user_update_data = [
            'ID'         => $customer_id,
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name'  => sanitize_text_field($_POST['last_name']),
        ];

        // Only set the role to 'customer' if the user is new or currently has no role/is only a customer.
        if ($is_new_user || empty($current_user_roles) || (count($current_user_roles) === 1 && $current_user_roles[0] === 'customer')) {
            $user_update_data['role'] = 'customer';
        }

        // Update user data regardless of whether they are new or existing
        wp_update_user($user_update_data);
        // --- END FIX ---

        // Update customer meta with the provided data
        $this->update_customer_meta($customer_id, $_POST);
        
        // --- Inquiry Creation Logic START ---
        $inquiry_handler = new Maneli_Installment_Inquiry_Handler();
        
        $issuer_type = sanitize_key($_POST['issuer_type'] ?? 'self');
        $national_code_for_api = ($issuer_type === 'other' && !empty($_POST['issuer_national_code']))
            ? sanitize_text_field($_POST['issuer_national_code'])
            : sanitize_text_field($_POST['national_code']);

        // 1. Execute Finotex Inquiry
        $finotex_result = $inquiry_handler->execute_finotex_inquiry($national_code_for_api);
        
        // 2. Calculate Loan Details (Replicating JS calculator logic)
        $product = wc_get_product($product_id);
        $total_price = (int)get_post_meta($product_id, 'installment_price', true);
        if (empty($total_price)) $total_price = (int)$product->get_regular_price();

        $down_payment = (int)preg_replace('/[^0-9]/', '', $_POST['down_payment'] ?? 0);
        $term_months = (int)sanitize_text_field($_POST['term_months'] ?? 12);
        
        $loan_amount = $total_price - $down_payment;
        
        // NEW: Use the helper function with the configurable interest rate
        $installment_amount = $this->calculate_installment_amount($loan_amount, $term_months);

        // 3. Prepare All Data for Post Meta
        $all_post_meta = $this->prepare_expert_inquiry_meta($_POST, $issuer_type, $product_id, $total_price, $down_payment, $term_months, $installment_amount);
        
        $post_title = sprintf(
            '%s: %s - %s',
            esc_html__('Inquiry for', 'maneli-car-inquiry'),
            get_the_title($product_id),
            $all_post_meta['first_name'] . ' ' . $all_post_meta['last_name']
        );
        
        // 4. Create Inquiry Post
        $post_id = wp_insert_post([
            'post_title'   => $post_title,
            'post_content' => "Finotex API raw response:\n<pre>" . esc_textarea($finotex_result['raw_response']) . "</pre>",
            'post_status'  => 'publish',
            'post_author'  => $customer_id,
            'post_type'    => 'inquiry'
        ], true);

        if (is_wp_error($post_id)) {
             wp_die(esc_html__('Error creating inquiry post.', 'maneli-car-inquiry') . $post_id->get_error_message());
        }

        // 5. Save All Meta Data
        $initial_status = ($finotex_result['status'] === 'DONE' || $finotex_result['status'] === 'SKIPPED') ? 'pending' : 'failed';
        
        // Save initial status
        update_post_meta($post_id, 'inquiry_status', $initial_status);
        update_post_meta($post_id, '_finotex_response_data', $finotex_result['data']);
        
        // Save all form and calculated meta
        foreach ($all_post_meta as $key => $value) {
            update_post_meta($post_id, $key, $value);
        }

        // 6. Assign Expert if requested
        $assigned_expert_id_str = sanitize_text_field($_POST['assigned_expert_id'] ?? 'auto');
        $expert_data = $this->assign_expert_to_post($post_id, $assigned_expert_id_str, 'installment');
        if (!is_wp_error($expert_data)) {
            // Set status to approved if manually assigned
            update_post_meta($post_id, 'inquiry_status', 'user_confirmed'); 
        }
        
        // 7. Cleanup temporary user meta
        $this->cleanup_user_meta($customer_id);

        // --- Inquiry Creation Logic END ---

        $redirect_url = add_query_arg('inquiry_created', '1', wp_get_referer());
        wp_redirect($redirect_url);
        exit;
    }
    
    /**
     * Handles user profile updates from the frontend user management panel.
     */
    public function handle_admin_update_user_profile() {
        check_admin_referer('maneli_admin_update_user', 'maneli_update_user_nonce');
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'maneli-car-inquiry'));
        }

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        if (!$user_id) {
            wp_die(esc_html__('User ID not specified.', 'maneli-car-inquiry'));
        }
        
        if ($user_id === get_current_user_id() && isset($_POST['user_role'])) {
            $new_role = sanitize_key($_POST['user_role']);
            if (!in_array($new_role, ['maneli_admin', 'administrator'], true)) {
                 wp_die(esc_html__('You cannot change your own administrative role to a lower-level role.', 'maneli-car-inquiry'));
            }
        }
        
        $user_data = [ 'ID' => $user_id ];
        if (isset($_POST['first_name'])) $user_data['first_name'] = sanitize_text_field($_POST['first_name']);
        if (isset($_POST['last_name'])) $user_data['last_name'] = sanitize_text_field($_POST['last_name']);
        if (isset($_POST['email']) && is_email($_POST['email'])) $user_data['user_email'] = sanitize_email($_POST['email']);
        
        // Update display name based on first/last name
        if (isset($user_data['first_name']) || isset($user_data['last_name'])) {
            $current_user = get_userdata($user_id);
            $first_name = $user_data['first_name'] ?? $current_user->first_name;
            $last_name = $user_data['last_name'] ?? $current_user->last_name;
            $user_data['display_name'] = trim($first_name . ' ' . $last_name);
        }

        if (isset($_POST['user_role'])) {
            $new_role = sanitize_key($_POST['user_role']);
            if (in_array($new_role, ['customer', 'maneli_expert', 'maneli_admin', 'administrator'], true)) {
                $user_data['role'] = $new_role;
            }
        }
        wp_update_user($user_data);

        $meta_fields = ['national_code', 'father_name', 'birth_date', 'mobile_number'];
        foreach ($meta_fields as $field) {
            if (isset($_POST[$field])) {
                update_user_meta($user_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
        
        $redirect_url = isset($_POST['_wp_http_referer']) ? esc_url_raw(wp_unslash($_POST['_wp_http_referer'])) : home_url();
        $redirect_url = remove_query_arg('edit_user', $redirect_url);
        wp_redirect(add_query_arg('user-updated', 'true', $redirect_url));
        exit;
    }

    /**
     * Handles new user creation from the frontend user management panel.
     */
    public function handle_admin_create_user() {
        check_admin_referer('maneli_admin_create_user_nonce');
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'maneli-car-inquiry'));
        }

        $redirect_url = isset($_POST['_wp_http_referer']) ? esc_url_raw(wp_unslash($_POST['_wp_http_referer'])) : home_url();

        $mobile = sanitize_text_field($_POST['mobile_number']);
        $password = $_POST['password'];

        if (empty($mobile) || empty($password)) {
            wp_redirect(add_query_arg('error', urlencode(esc_html__('Mobile number and password are required.', 'maneli-car-inquiry')), $redirect_url));
            exit;
        }

        $user_login = $mobile;
        $email = $mobile . '@manelikhodro.com';

        if (username_exists($user_login)) {
            wp_redirect(add_query_arg('error', urlencode(esc_html__('A user with this mobile number already exists.', 'maneli-car-inquiry')), $redirect_url));
            exit;
        }
        if (email_exists($email)) {
             wp_redirect(add_query_arg('error', urlencode(esc_html__('An email associated with this mobile number already exists.', 'maneli-car-inquiry')), $redirect_url));
            exit;
        }

        $user_id = wp_create_user($user_login, $password, $email);
        if (is_wp_error($user_id)) {
            wp_redirect(add_query_arg('error', urlencode($user_id->get_error_message()), $redirect_url));
            exit;
        }

        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name = sanitize_text_field($_POST['last_name'] ?? '');
        
        wp_update_user([
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => trim($first_name . ' ' . $last_name),
            'role' => 'customer'
        ]);
        update_user_meta($user_id, 'mobile_number', $mobile);
        
        wp_redirect(add_query_arg('user-created', 'true', $redirect_url));
        exit;
    }

    /**
     * Re-runs the Finotex API call for an inquiry where it previously failed or was skipped.
     */
    public function handle_admin_retry_finotex() {
        check_admin_referer('maneli_retry_finotex_nonce');
        if (!current_user_can('manage_maneli_inquiries') || empty($_POST['inquiry_id'])) {
            wp_die(esc_html__('Invalid request or insufficient permissions.', 'maneli-car-inquiry'));
        }

        $post_id = intval($_POST['inquiry_id']);
        $post_meta = get_post_meta($post_id);
        $issuer_type = $post_meta['issuer_type'][0] ?? 'self';
        $national_code_for_api = ($issuer_type === 'other' && !empty($post_meta['issuer_national_code'][0])) ? $post_meta['issuer_national_code'][0] : ($post_meta['national_code'][0] ?? '');
        
        $inquiry_handler = new Maneli_Installment_Inquiry_Handler();
        $finotex_result = $inquiry_handler->execute_finotex_inquiry($national_code_for_api);

        wp_update_post(['ID' => $post_id, 'post_content' => "Finotex API raw response (Retried by Admin):\n<pre>" . esc_textarea($finotex_result['raw_response']) . "</pre>"]);
        update_post_meta($post_id, '_finotex_response_data', $finotex_result['data']);

        $new_status = ($finotex_result['status'] === 'DONE' || $finotex_result['status'] === 'SKIPPED') ? 'pending' : 'failed';
        update_post_meta($post_id, 'inquiry_status', $new_status);

        wp_redirect(home_url('/dashboard/?endp=inf_menu_4&inquiry_id=' . $post_id));
        exit;
    }
    
    /**
     * Public method to assign an expert to a post and send notifications.
     */
    public function assign_expert_to_post($post_id, $expert_id_str, $inquiry_type) {
        if ('auto' === $expert_id_str) {
            $option_key = ($inquiry_type === 'cash') ? 'maneli_cash_expert_last_assigned_index' : 'maneli_expert_last_assigned_index';
            $expert_users = get_users(['role' => 'maneli_expert', 'orderby' => 'ID', 'order' => 'ASC']);
            if (empty($expert_users)) {
                return new WP_Error('no_experts', esc_html__('No experts found for automatic assignment.', 'maneli-car-inquiry'));
            }
            $last_index = get_option($option_key, -1);
            $next_index = ($last_index + 1) % count($expert_users);
            $assigned_expert_id = $expert_users[$next_index]->ID;
            update_option($option_key, $next_index);
        } else {
            $expert_user = get_userdata(intval($expert_id_str));
            if (!$expert_user || !in_array('maneli_expert', $expert_user->roles, true)) {
                return new WP_Error('invalid_expert', esc_html__('The selected expert is not valid.', 'maneli-car-inquiry'));
            }
            $assigned_expert_id = $expert_user->ID;
        }
        
        $expert_info = get_userdata($assigned_expert_id);
        update_post_meta($post_id, 'assigned_expert_id', $assigned_expert_id);
        update_post_meta($post_id, 'assigned_expert_name', $expert_info->display_name);
        
        $this->notify_expert_of_assignment($post_id, $assigned_expert_id, $inquiry_type);
        
        return ['id' => $assigned_expert_id, 'name' => $expert_info->display_name];
    }
    
    /**
     * Helper method to send SMS notification to an expert.
     */
    private function notify_expert_of_assignment($post_id, $expert_id, $inquiry_type) {
        $options = get_option('maneli_inquiry_all_options', []);
        $expert_phone = get_user_meta($expert_id, 'mobile_number', true);
        
        if ($inquiry_type === 'cash') {
            $pattern_id = $options['cash_inquiry_expert_referral_pattern'] ?? 0;
            $customer_name = get_post_meta($post_id, 'cash_first_name', true) . ' ' . get_post_meta($post_id, 'cash_last_name', true);
            $customer_mobile = get_post_meta($post_id, 'mobile_number', true);
        } else {
            $pattern_id = $options['sms_pattern_expert_referral'] ?? 0;
            $customer_info = get_userdata(get_post_field('post_author', $post_id));
            $customer_name = $customer_info->display_name;
            $customer_mobile = get_user_meta($customer_info->ID, 'mobile_number', true);
        }

        if ($pattern_id > 0 && !empty($expert_phone)) {
            $expert_name = get_userdata($expert_id)->display_name;
            $car_name = get_the_title(get_post_meta($post_id, 'product_id', true));
            $params = [(string)$expert_name, (string)$customer_name, (string)$customer_mobile, (string)$car_name];
            
            (new Maneli_SMS_Handler())->send_pattern($pattern_id, $expert_phone, $params);
        }
    }

    /**
     * Helper method to update customer profile meta data from the expert form.
     */
    private function update_customer_meta($user_id, $post_data) {
        $meta_fields = [
            'national_code', 'father_name', 'birth_date', 'mobile_number',
            'occupation', 'income_level', 'phone_number', 'residency_status',
            'workplace_status', 'address', 'bank_name', 'account_number',
            'branch_code', 'branch_name'
        ];
        foreach ($meta_fields as $field) {
            if (isset($post_data[$field])) {
                update_user_meta($user_id, $field, sanitize_text_field($post_data[$field]));
            }
        }
    }
    
    /**
     * Helper method to prepare all inquiry post meta from the expert form data.
     */
    private function prepare_expert_inquiry_meta($post_data, $issuer_type, $product_id, $total_price, $down_payment, $term_months, $installment_amount) {
        // Default fields for both applicant and issuer (if self)
        $meta_map = [
            'product_id'                 => $product_id,
            'issuer_type'                => $issuer_type,
            'maneli_inquiry_total_price' => $total_price,
            'maneli_inquiry_down_payment' => $down_payment,
            'maneli_inquiry_term_months' => $term_months,
            'maneli_inquiry_installment' => $installment_amount,

            // Applicant Fields (Buyer)
            'first_name'                 => sanitize_text_field($post_data['first_name'] ?? ''),
            'last_name'                  => sanitize_text_field($post_data['last_name'] ?? ''),
            'father_name'                => sanitize_text_field($post_data['father_name'] ?? ''),
            'national_code'              => sanitize_text_field($post_data['national_code'] ?? ''),
            'birth_date'                 => sanitize_text_field($post_data['birth_date'] ?? ''),
            'mobile_number'              => sanitize_text_field($post_data['mobile_number'] ?? ''),
            // Placeholder/Optional fields that exist in customer form but not always in expert form
            'occupation'                 => sanitize_text_field($post_data['occupation'] ?? ''), 
            'income_level'               => sanitize_text_field($post_data['income_level'] ?? ''), 
            'phone_number'               => sanitize_text_field($post_data['phone_number'] ?? ''), 
            'residency_status'           => sanitize_text_field($post_data['residency_status'] ?? ''), 
            'workplace_status'           => sanitize_text_field($post_data['workplace_status'] ?? ''), 
            'address'                    => sanitize_textarea_field($post_data['address'] ?? ''), 
            'bank_name'                  => sanitize_text_field($post_data['bank_name'] ?? ''), 
            'account_number'             => sanitize_text_field($post_data['account_number'] ?? ''), 
            'branch_code'                => sanitize_text_field($post_data['branch_code'] ?? ''), 
            'branch_name'                => sanitize_text_field($post_data['branch_name'] ?? ''),
        ];

        // Issuer Fields (if issuer_type is 'other')
        if ($issuer_type === 'other') {
             $meta_map['issuer_full_name']        = sanitize_text_field($post_data['issuer_first_name'] ?? '') . ' ' . sanitize_text_field($post_data['issuer_last_name'] ?? '');
             $meta_map['issuer_national_code']    = sanitize_text_field($post_data['issuer_national_code'] ?? '');
             $meta_map['issuer_father_name']      = sanitize_text_field($post_data['issuer_father_name'] ?? '');
             $meta_map['issuer_birth_date']       = sanitize_text_field($post_data['issuer_birth_date'] ?? '');
             $meta_map['issuer_mobile_number']    = sanitize_text_field($post_data['issuer_mobile_number'] ?? '');
             // Placeholder/Optional fields for Issuer
             $meta_map['issuer_occupation']       = sanitize_text_field($post_data['issuer_occupation'] ?? '');
             $meta_map['issuer_phone_number']     = sanitize_text_field($post_data['issuer_phone_number'] ?? '');
             $meta_map['issuer_address']          = sanitize_textarea_field($post_data['issuer_address'] ?? '');
             $meta_map['issuer_residency_status'] = sanitize_text_field($post_data['issuer_residency_status'] ?? '');
             $meta_map['issuer_workplace_status'] = sanitize_text_field($post_data['issuer_workplace_status'] ?? '');
             $meta_map['issuer_bank_name']        = sanitize_text_field($post_data['issuer_bank_name'] ?? '');
             $meta_map['issuer_account_number']   = sanitize_text_field($post_data['issuer_account_number'] ?? '');
             $meta_map['issuer_branch_code']      = sanitize_text_field($post_data['issuer_branch_code'] ?? '');
             $meta_map['issuer_branch_name']      = sanitize_text_field($post_data['issuer_branch_name'] ?? '');
        }
        
        return $meta_map;
    }
    
    /**
     * Cleans up all temporary meta fields from the user's profile after inquiry creation.
     */
    private function cleanup_user_meta($user_id) {
        $keys_to_delete = [
            'maneli_inquiry_down_payment', 
            'maneli_inquiry_term_months',
            'maneli_inquiry_total_price',
            'maneli_inquiry_step', 
            'maneli_selected_car_id', 
            'maneli_temp_inquiry_data'
        ];
        foreach ($keys_to_delete as $key) {
            delete_user_meta($user_id, $key);
        }
    }
}