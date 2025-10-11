<?php
if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Ajax_Handler {

    public function __construct() {
        // AJAX hooks for cash inquiry management
        add_action('wp_ajax_maneli_get_cash_inquiry_details', [$this, 'ajax_get_cash_inquiry_details']);
        add_action('wp_ajax_maneli_update_cash_inquiry', [$this, 'ajax_update_cash_inquiry']);
        add_action('wp_ajax_maneli_delete_cash_inquiry', [$this, 'ajax_delete_cash_inquiry']);
        add_action('wp_ajax_maneli_set_down_payment', [$this, 'ajax_set_down_payment']);
        add_action('wp_ajax_maneli_assign_expert_to_cash_inquiry', [$this, 'ajax_assign_expert_to_cash_inquiry']);
		add_action('wp_ajax_maneli_get_inquiry_details', [$this, 'ajax_get_inquiry_details']);
        
        // AJAX hook for assigning expert to installment inquiries
        add_action('wp_ajax_maneli_assign_expert_to_inquiry', [$this, 'ajax_assign_expert_to_inquiry']);

        // AJAX handlers for filtering lists (Moved from shortcodes)
        add_action('wp_ajax_maneli_filter_inquiries_ajax', [$this, 'handle_filter_inquiries_ajax']);
        add_action('wp_ajax_maneli_filter_cash_inquiries_ajax', [$this, 'handle_filter_cash_inquiries_ajax']);
    }

    public function ajax_get_inquiry_details() {
        check_ajax_referer('maneli_inquiry_details_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز'], 403);
        }

        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        if (!$inquiry_id) {
            wp_send_json_error(['message' => 'شناسه استعلام نامعتبر است.']);
        }

        $inquiry = get_post($inquiry_id);
        if (!$inquiry || $inquiry->post_type !== 'inquiry') {
            wp_send_json_error(['message' => 'استعلام یافت نشد.']);
        }

        $current_user = wp_get_current_user();
        $can_view = false;
        if (current_user_can('manage_maneli_inquiries') || (int)$inquiry->post_author === $current_user->ID) {
            $can_view = true;
        } elseif (in_array('maneli_expert', $current_user->roles)) {
            $assigned_expert_id = (int)get_post_meta($inquiry_id, 'assigned_expert_id', true);
            if ($assigned_expert_id === $current_user->ID) {
                $can_view = true;
            }
        }

        if (!$can_view) {
            wp_send_json_error(['message' => 'شما اجازه مشاهده این گزارش را ندارید.'], 403);
        }

        $post_meta = get_post_meta($inquiry_id);
        $finotex_data = get_post_meta($inquiry_id, '_finotex_response_data', true);
        $product_id = $post_meta['product_id'][0] ?? 0;
        $status = $post_meta['inquiry_status'][0] ?? 'pending';

        $status_map = Maneli_CPT_Handler::get_all_statuses();

        $data = [
            'id' => $inquiry_id,
            'status_label' => $status_map[$status] ?? 'نامشخص',
            'status_key' => $status,
            'rejection_reason' => get_post_meta($inquiry_id, 'rejection_reason', true),
            'car' => [
                'name' => get_the_title($product_id),
                'image' => get_the_post_thumbnail_url($product_id, 'medium'),
                'total_price' => number_format_i18n((int)($post_meta['maneli_inquiry_total_price'][0] ?? 0)),
                'down_payment' => number_format_i18n((int)($post_meta['maneli_inquiry_down_payment'][0] ?? 0)),
                'term' => $post_meta['maneli_inquiry_term_months'][0] ?? 0,
                'installment' => number_format_i18n((int)($post_meta['maneli_inquiry_installment'][0] ?? 0)),
            ],
            'buyer' => [
                'first_name' => $post_meta['first_name'][0] ?? '',
                'last_name' => $post_meta['last_name'][0] ?? '',
                'national_code' => $post_meta['national_code'][0] ?? '',
                'father_name' => $post_meta['father_name'][0] ?? '',
                'birth_date' => $post_meta['birth_date'][0] ?? '',
                'mobile' => $post_meta['mobile_number'][0] ?? '',
            ],
            'issuer_type' => $post_meta['issuer_type'][0] ?? 'self',
            'issuer' => null,
            'finotex' => [
                'skipped' => (empty($finotex_data) || (isset($finotex_data['status']) && $finotex_data['status'] === 'SKIPPED')),
                'color_code' => $finotex_data['result']['chequeColor'] ?? 0,
            ],
        ];

        if ($data['issuer_type'] === 'other') {
            $data['issuer'] = [
                'first_name' => $post_meta['issuer_first_name'][0] ?? '',
                'last_name' => $post_meta['issuer_last_name'][0] ?? '',
                'national_code' => $post_meta['issuer_national_code'][0] ?? '',
                'father_name' => $post_meta['issuer_father_name'][0] ?? '',
                'birth_date' => $post_meta['issuer_birth_date'][0] ?? '',
                'mobile' => $post_meta['issuer_mobile_number'][0] ?? '',
            ];
        }

        wp_send_json_success($data);
    }
    
    public function ajax_get_cash_inquiry_details() {
        check_ajax_referer('maneli_cash_inquiry_details_nonce', 'nonce');

        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.'], 403);
        }

        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        if (!$inquiry_id || get_post_type($inquiry_id) !== 'cash_inquiry') {
            wp_send_json_error(['message' => 'شناسه درخواست نامعتبر است.']);
        }

        $product_id = get_post_meta($inquiry_id, 'product_id', true);
        $status = get_post_meta($inquiry_id, 'cash_inquiry_status', true);

        $data = [
            'id' => $inquiry_id,
            'status_label' => Maneli_Admin_Dashboard_Widgets::get_cash_inquiry_status_label($status),
            'status_key' => $status,
            'car' => [
                'name' => get_the_title($product_id),
                'color' => get_post_meta($inquiry_id, 'cash_car_color', true),
            ],
            'customer' => [
                'first_name' => get_post_meta($inquiry_id, 'cash_first_name', true),
                'last_name' => get_post_meta($inquiry_id, 'cash_last_name', true),
                'mobile' => get_post_meta($inquiry_id, 'mobile_number', true),
            ],
            'payment' => [
                'down_payment' => get_post_meta($inquiry_id, 'cash_down_payment', true),
                'rejection_reason' => get_post_meta($inquiry_id, 'cash_rejection_reason', true),
            ]
        ];

        wp_send_json_success($data);
    }
    
    public function ajax_update_cash_inquiry() {
        check_ajax_referer('maneli_cash_inquiry_update_nonce', 'nonce');
    
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.'], 403);
        }
    
        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
        $mobile = isset($_POST['mobile']) ? sanitize_text_field($_POST['mobile']) : '';
        $color = isset($_POST['color']) ? sanitize_text_field($_POST['color']) : '';
    
        if (!$inquiry_id || get_post_type($inquiry_id) !== 'cash_inquiry') {
            wp_send_json_error(['message' => 'شناسه درخواست نامعتبر است.']);
        }
    
        update_post_meta($inquiry_id, 'cash_first_name', $first_name);
        update_post_meta($inquiry_id, 'cash_last_name', $last_name);
        update_post_meta($inquiry_id, 'mobile_number', $mobile);
        update_post_meta($inquiry_id, 'cash_car_color', $color);
    
        wp_send_json_success(['message' => 'درخواست با موفقیت به‌روزرسانی شد.']);
    }
    
    public function ajax_delete_cash_inquiry() {
        check_ajax_referer('maneli_cash_inquiry_delete_nonce', 'nonce');
    
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.'], 403);
        }
    
        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
    
        if (!$inquiry_id || get_post_type($inquiry_id) !== 'cash_inquiry') {
            wp_send_json_error(['message' => 'شناسه درخواست نامعتبر است.']);
        }
    
        if (wp_delete_post($inquiry_id, true)) {
            wp_send_json_success(['message' => 'درخواست با موفقیت حذف شد.']);
        } else {
            wp_send_json_error(['message' => 'خطا در حذف درخواست.']);
        }
    }
    
    public function ajax_set_down_payment() {
        check_ajax_referer('maneli_cash_set_downpayment_nonce', 'nonce');
    
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.'], 403);
        }
    
        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        $amount_raw = isset($_POST['amount']) ? $_POST['amount'] : 0;
        $amount = preg_replace('/[^0-9]/', '', $amount_raw);
        $new_status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';
    
        if (!$inquiry_id || get_post_type($inquiry_id) !== 'cash_inquiry') {
            wp_send_json_error(['message' => 'شناسه درخواست نامعتبر است.']);
        }
        
        $options = get_option('maneli_inquiry_all_options', []);
        $sms_handler = new Maneli_SMS_Handler();
        
        $customer_name = get_post_meta($inquiry_id, 'cash_first_name', true) . ' ' . get_post_meta($inquiry_id, 'cash_last_name', true);
        $customer_mobile = get_post_meta($inquiry_id, 'mobile_number', true);
        $product_id = get_post_meta($inquiry_id, 'product_id', true);
        $car_name = get_the_title($product_id);

        if ($new_status === 'awaiting_payment' && $amount > 0) {
            update_post_meta($inquiry_id, 'cash_down_payment', $amount);
            update_post_meta($inquiry_id, 'cash_inquiry_status', 'awaiting_payment');
            
            $pattern_id = $options['cash_inquiry_approved_pattern'] ?? 0;
            if ($pattern_id > 0 && !empty($customer_mobile)) {
                $params = [(string)$customer_name, (string)$car_name, (string)number_format_i18n($amount)];
                $sms_handler->send_pattern($pattern_id, $customer_mobile, $params);
            }
            
        } elseif ($new_status === 'rejected') {
            update_post_meta($inquiry_id, 'cash_rejection_reason', $reason);
            update_post_meta($inquiry_id, 'cash_inquiry_status', 'rejected');
            
            $pattern_id = $options['cash_inquiry_rejected_pattern'] ?? 0;
            if ($pattern_id > 0 && !empty($customer_mobile)) {
                $params = [(string)$customer_name, (string)$car_name, (string)$reason];
                $sms_handler->send_pattern($pattern_id, $customer_mobile, $params);
            }

        } else {
            wp_send_json_error(['message' => 'اطلاعات ارسالی نامعتبر است.']);
        }
    
        wp_send_json_success(['message' => 'وضعیت با موفقیت تغییر کرد.']);
    }

    public function ajax_assign_expert_to_cash_inquiry() {
        check_ajax_referer('maneli_cash_inquiry_assign_expert_nonce', 'nonce');
    
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.'], 403);
        }
    
        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        $expert_id = isset($_POST['expert_id']) ? sanitize_text_field($_POST['expert_id']) : 'auto';
    
        if (!$inquiry_id || get_post_type($inquiry_id) !== 'cash_inquiry') {
            wp_send_json_error(['message' => 'شناسه درخواست نامعتبر است.']);
        }
    
        $assigned_expert_id = 0;
        $assigned_expert_name = '';
    
        if ($expert_id === 'auto') {
            $expert_users = get_users(['role' => 'maneli_expert', 'orderby' => 'ID', 'order' => 'ASC']);
            if (empty($expert_users)) {
                wp_send_json_error(['message' => 'هیچ کارشناسی برای ارجاع خودکار یافت نشد.']);
            }
            $last_index = get_option('maneli_cash_expert_last_assigned_index', -1);
            $next_index = ($last_index + 1) % count($expert_users);
            $assigned_expert = $expert_users[$next_index];
            $assigned_expert_id = $assigned_expert->ID;
            $assigned_expert_name = $assigned_expert->display_name;
            update_option('maneli_cash_expert_last_assigned_index', $next_index);
        } else {
            $expert_user = get_userdata(intval($expert_id));
            if ($expert_user && in_array('maneli_expert', $expert_user->roles)) {
                $assigned_expert_id = $expert_user->ID;
                $assigned_expert_name = $expert_user->display_name;
            } else {
                wp_send_json_error(['message' => 'کارشناس انتخاب شده معتبر نیست.']);
            }
        }
    
        update_post_meta($inquiry_id, 'assigned_expert_id', $assigned_expert_id);
        update_post_meta($inquiry_id, 'assigned_expert_name', $assigned_expert_name);
        update_post_meta($inquiry_id, 'cash_inquiry_status', 'approved');

        // Send SMS to the assigned expert
        $options = get_option('maneli_inquiry_all_options', []);
        $pattern_id = $options['cash_inquiry_expert_referral_pattern'] ?? 0;
        $expert_phone = get_user_meta($assigned_expert_id, 'mobile_number', true);

        if ($pattern_id > 0 && !empty($expert_phone)) {
            $customer_name = get_post_meta($inquiry_id, 'cash_first_name', true) . ' ' . get_post_meta($inquiry_id, 'cash_last_name', true);
            $customer_mobile = get_post_meta($inquiry_id, 'mobile_number', true);
            $product_id = get_post_meta($inquiry_id, 'product_id', true);
            $car_name = get_the_title($product_id);
            
            $params = [(string)$assigned_expert_name, (string)$customer_name, (string)$customer_mobile, (string)$car_name];
            $sms_handler = new Maneli_SMS_Handler();
            $sms_handler->send_pattern($pattern_id, $expert_phone, $params);
        }
    
        wp_send_json_success(['message' => 'درخواست با موفقیت به ' . $assigned_expert_name . ' ارجاع داده شد.', 'expert_name' => $assigned_expert_name]);
    }
    
    public function ajax_assign_expert_to_inquiry() {
        check_ajax_referer('maneli_inquiry_assign_expert_nonce', 'nonce');
    
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.'], 403);
        }
    
        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        $expert_id = isset($_POST['expert_id']) ? sanitize_text_field($_POST['expert_id']) : 'auto';
    
        if (!$inquiry_id || get_post_type($inquiry_id) !== 'inquiry') {
            wp_send_json_error(['message' => 'شناسه استعلام نامعتبر است.']);
        }
    
        $assigned_expert_id = 0;
        $assigned_expert_name = '';
    
        if ($expert_id === 'auto') {
            // Use the round-robin logic for installment inquiries
            // This requires an instance of the class that has this method
            $admin_actions_handler = new Maneli_Admin_Actions_Handler();
            // This is not ideal. A better way would be a static helper or moving the method.
            // For now, let's make the method temporarily public for this to work, or use reflection.
            // Let's use reflection to avoid changing method visibility.
            $reflectionMethod = new ReflectionMethod('Maneli_Admin_Actions_Handler', 'assign_expert_round_robin');
            $reflectionMethod->setAccessible(true);
            $assigned_expert_id = $reflectionMethod->invoke($admin_actions_handler, $inquiry_id);

            if ($assigned_expert_id) {
                $expert_user = get_userdata($assigned_expert_id);
                $assigned_expert_name = $expert_user->display_name;
            } else {
                 wp_send_json_error(['message' => 'هیچ کارشناسی برای ارجاع خودکار یافت نشد.']);
            }
        } else {
            $expert_user = get_userdata(intval($expert_id));
            if ($expert_user && in_array('maneli_expert', $expert_user->roles)) {
                $assigned_expert_id = $expert_user->ID;
                $assigned_expert_name = $expert_user->display_name;

                update_post_meta($inquiry_id, 'assigned_expert_id', $assigned_expert_id);
                update_post_meta($inquiry_id, 'assigned_expert_name', $assigned_expert_name);
                
                // Manually trigger SMS for non-round-robin assignment
                $options = get_option('maneli_inquiry_all_options', []);
                $pattern_id = $options['sms_pattern_expert_referral'] ?? 0;
                $expert_phone = get_user_meta($assigned_expert_id, 'mobile_number', true);

                if ($pattern_id > 0 && !empty($expert_phone)) {
                    $user_id = get_post_field('post_author', $inquiry_id);
                    $customer_info = get_userdata($user_id);
                    $customer_name = ($customer_info->first_name ?? '') . ' ' . ($customer_info->last_name ?? '');
                    $customer_mobile = get_post_meta($inquiry_id, 'mobile_number', true) ?? '';
                    $car_name = get_the_title(get_post_meta($inquiry_id, 'product_id', true)) ?? '';
                    $params = [(string)$assigned_expert_name, (string)$customer_name, (string)$customer_mobile, (string)$car_name];
                    $sms_handler = new Maneli_SMS_Handler();
                    $sms_handler->send_pattern($pattern_id, $expert_phone, $params);
                }

            } else {
                wp_send_json_error(['message' => 'کارشناس انتخاب شده معتبر نیست.']);
            }
        }
    
        // Update status to 'user_confirmed' which means approved and referred
        update_post_meta($inquiry_id, 'inquiry_status', 'user_confirmed');
    
        wp_send_json_success([
            'message' => 'استعلام با موفقیت به ' . $assigned_expert_name . ' ارجاع داده شد.',
            'expert_name' => $assigned_expert_name,
            'new_status' => Maneli_CPT_Handler::get_status_label('user_confirmed')
        ]);
    }

    public function handle_filter_inquiries_ajax() {
        if ( !isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'maneli_inquiry_filter_nonce') ) {
            wp_send_json_error(['message' => 'خطای امنیتی. لطفاً صفحه را رفرش کنید.']);
        }
    
        if (!is_user_logged_in() || !(current_user_can('maneli_expert') || current_user_can('manage_maneli_inquiries'))) {
            wp_send_json_error(['message' => 'شما دسترسی لازم را ندارید.']);
        }
    
        $paged = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $search_query = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
        $base_url = isset($_POST['base_url']) ? esc_url_raw($_POST['base_url']) : home_url();
        
        $args = [
            'post_type'      => 'inquiry',
            'posts_per_page' => 50,
            'paged'          => $paged,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post_status'    => 'publish',
        ];
    
        $meta_query = ['relation' => 'AND'];
    
        if (!empty($search_query)) {
            $user_ids_from_meta = get_users([
                'fields' => 'ID',
                'meta_query' => [
                    'relation' => 'OR',
                    ['key' => 'first_name', 'value' => $search_query, 'compare' => 'LIKE'],
                    ['key' => 'last_name', 'value' => $search_query, 'compare' => 'LIKE'],
                    ['key' => 'national_code', 'value' => $search_query, 'compare' => 'LIKE'],
                    ['key' => 'mobile_number', 'value' => $search_query, 'compare' => 'LIKE'],
                ]
            ]);
            $users_by_display_name = get_users(['search' => '*' . esc_attr($search_query) . '*', 'fields' => 'ID']);
            $all_user_ids = array_unique(array_merge($user_ids_from_meta, $users_by_display_name));
            
            $product_ids = wc_get_products(['s' => $search_query, 'limit' => -1, 'return' => 'ids']);
    
            $search_related_inquiry_ids = [];
            if (!empty($all_user_ids)) {
                $user_inquiries = get_posts(['post_type' => 'inquiry', 'posts_per_page' => -1, 'author__in' => $all_user_ids, 'fields' => 'ids']);
                $search_related_inquiry_ids = array_merge($search_related_inquiry_ids, $user_inquiries);
            }
             if (!empty($product_ids)) {
                $product_inquiries = get_posts(['post_type' => 'inquiry', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => 'product_id', 'meta_value' => $product_ids, 'meta_compare' => 'IN']);
                $search_related_inquiry_ids = array_merge($search_related_inquiry_ids, $product_inquiries);
            }
    
            if(empty($search_related_inquiry_ids)){
                 $args['post__in'] = [0];
            } else {
                 $args['post__in'] = array_unique($search_related_inquiry_ids);
            }
        }
        
        if (!empty($_POST['status'])) {
            $meta_query[] = ['key' => 'inquiry_status', 'value' => sanitize_text_field($_POST['status'])];
        }
    
        if (current_user_can('manage_maneli_inquiries')) {
            if (!empty($_POST['expert'])) {
                $meta_query[] = ['key' => 'assigned_expert_id', 'value' => absint($_POST['expert'])];
            }
        } else {
            $user_id = get_current_user_id();
            $meta_query[] = [
                'relation' => 'OR',
                ['key' => 'assigned_expert_id', 'value' => $user_id],
                ['key' => 'created_by_expert_id', 'value' => $user_id]
            ];
        }
        
        if (count($meta_query) > 1) {
            $args['meta_query'] = $meta_query;
        }
    
        $inquiry_query = new WP_Query($args);
    
        ob_start();
        if ($inquiry_query->have_posts()) {
            while ($inquiry_query->have_posts()) {
                $inquiry_query->the_post();
                // We need the render_inquiry_row method, but it's private in another class.
                // To avoid duplication, let's create a temporary instance and call it via reflection.
                $list_shortcode = new Maneli_Inquiry_Lists_Shortcode();
                $reflectionMethod = new ReflectionMethod('Maneli_Inquiry_Lists_Shortcode', 'render_inquiry_row');
                $reflectionMethod->setAccessible(true);
                $reflectionMethod->invoke($list_shortcode, get_the_ID(), $base_url);
            }
        } else {
            $columns = current_user_can('manage_maneli_inquiries') ? 7 : 6;
            echo '<tr><td colspan="' . $columns . '" style="text-align:center;">هیچ استعلامی با این مشخصات یافت نشد.</td></tr>';
        }
        $html = ob_get_clean();
        wp_reset_postdata();
        
        $pagination_html = paginate_links([
            'base' => add_query_arg('paged', '%#%'),
            'format' => '?paged=%#%',
            'current' => $paged,
            'total' => $inquiry_query->max_num_pages,
            'prev_text' => '« قبلی',
            'next_text' => 'بعدی »',
            'type'  => 'plain'
        ]);
    
        wp_send_json_success(['html' => $html, 'pagination_html' => $pagination_html]);
    }
	
	public function handle_filter_cash_inquiries_ajax() {
        if ( !isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'maneli_cash_inquiry_filter_nonce') ) {
            wp_send_json_error(['message' => 'خطای امنیتی.']);
        }

        if (!is_user_logged_in() || !(current_user_can('maneli_expert') || current_user_can('manage_maneli_inquiries'))) {
            wp_send_json_error(['message' => 'شما دسترسی لازم را ندارید.']);
        }

        $paged = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $search_query = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
        $status_query = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : '';
        $base_url = isset($_POST['base_url']) ? esc_url_raw($_POST['base_url']) : home_url();

        $args = [
            'post_type'      => 'cash_inquiry',
            'posts_per_page' => 50,
            'paged'          => $paged,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post_status'    => 'publish',
        ];
		
		$meta_query = ['relation' => 'AND'];

        if (!current_user_can('manage_maneli_inquiries')) {
            $meta_query[] = [
                'key' => 'assigned_expert_id',
                'value' => get_current_user_id()
            ];
        } elseif (!empty($_POST['expert'])) {
            $meta_query[] = [
                'key' => 'assigned_expert_id',
                'value' => absint($_POST['expert'])
            ];
        }


        if (!empty($search_query)) {
            $product_ids = wc_get_products(['s' => $search_query, 'limit' => -1, 'return' => 'ids']);
            
            $search_meta_query = [
                'relation' => 'OR',
                ['key' => 'cash_first_name', 'value' => $search_query, 'compare' => 'LIKE'],
                ['key' => 'cash_last_name', 'value' => $search_query, 'compare' => 'LIKE'],
                ['key' => 'mobile_number', 'value' => $search_query, 'compare' => 'LIKE'],
            ];

            if(!empty($product_ids)){
                $search_meta_query[] = ['key' => 'product_id', 'value' => $product_ids, 'compare' => 'IN'];
            }
            $meta_query[] = $search_meta_query;
        }
		
		if (!empty($status_query)) {
            $meta_query[] = [
                'key' => 'cash_inquiry_status',
                'value' => $status_query,
                'compare' => '=',
            ];
        }

        if (count($meta_query) > 1) {
            $args['meta_query'] = $meta_query;
        }


        $inquiry_query = new WP_Query($args);

        ob_start();
        if ($inquiry_query->have_posts()) {
            $list_shortcode = new Maneli_Inquiry_Lists_Shortcode();
            while ($inquiry_query->have_posts()) {
                $inquiry_query->the_post();
                $reflectionMethod = new ReflectionMethod('Maneli_Inquiry_Lists_Shortcode', 'render_cash_inquiry_row');
                $reflectionMethod->setAccessible(true);
                $reflectionMethod->invoke($list_shortcode, get_the_ID(), $base_url);
            }
        } else {
            echo '<tr><td colspan="8" style="text-align:center;">هیچ درخواستی یافت نشد.</td></tr>';
        }
        $html = ob_get_clean();
        wp_reset_postdata();
        
        $pagination_html = paginate_links([
            'base' => add_query_arg('paged', '%#%'),
            'format' => '?paged=%#%',
            'current' => $paged,
            'total' => $inquiry_query->max_num_pages,
            'prev_text' => '« قبلی',
            'next_text' => 'بعدی »',
            'type'  => 'plain'
        ]);

        wp_send_json_success(['html' => $html, 'pagination_html' => $pagination_html]);
    }
}