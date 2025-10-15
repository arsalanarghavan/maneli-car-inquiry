<?php
/**
 * Handles all AJAX requests for the plugin.
 * This class serves as a central hub for filtering lists, fetching details, updating data, and performing actions.
 *
 * @package Maneli_Car_Inquiry/Includes/Admin
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.3 (Added ajax_delete_inquiry for installment requests)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Ajax_Handler {

    public function __construct() {
        // Inquiry Details, List Filtering & Actions (Installment)
        add_action('wp_ajax_maneli_get_inquiry_details', [$this, 'ajax_get_inquiry_details']);
        add_action('wp_ajax_maneli_filter_inquiries_ajax', [$this, 'ajax_filter_inquiries']);
        add_action('wp_ajax_maneli_delete_inquiry', [$this, 'ajax_delete_inquiry']); // ADDED: Installment Inquiry Delete

        // Cash Inquiry Actions & List Filtering
        add_action('wp_ajax_maneli_get_cash_inquiry_details', [$this, 'ajax_get_cash_inquiry_details']);
        add_action('wp_ajax_maneli_update_cash_inquiry', [$this, 'ajax_update_cash_inquiry']);
        add_action('wp_ajax_maneli_delete_cash_inquiry', [$this, 'ajax_delete_cash_inquiry']);
        add_action('wp_ajax_maneli_set_down_payment', [$this, 'ajax_set_down_payment']);
        add_action('wp_ajax_maneli_filter_cash_inquiries_ajax', [$this, 'ajax_filter_cash_inquiries']);
        
        // Expert Assignment Actions
        add_action('wp_ajax_maneli_assign_expert_to_cash_inquiry', [$this, 'ajax_assign_expert_to_cash_inquiry']);
        add_action('wp_ajax_maneli_assign_expert_to_inquiry', [$this, 'ajax_assign_expert_to_inquiry']);

        // User Management (from class-user-management-shortcodes.php)
        add_action('wp_ajax_maneli_filter_users_ajax', [$this, 'ajax_filter_users']);
        add_action('wp_ajax_maneli_delete_user_ajax', [$this, 'ajax_delete_user']);

        // Product Editor (from class-product-editor-shortcode.php & class-hooks.php)
        add_action('wp_ajax_maneli_filter_products_ajax', [$this, 'ajax_filter_products']);
        add_action('wp_ajax_maneli_update_product_data', [$this, 'ajax_update_product_data']);
    }

    //======================================================================
    // Installment Inquiry Handlers
    //======================================================================

    public function ajax_get_inquiry_details() {
        check_ajax_referer('maneli_inquiry_details_nonce', 'nonce');
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }
        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        if (!$inquiry_id || get_post_type($inquiry_id) !== 'inquiry') {
            wp_send_json_error(['message' => esc_html__('Invalid inquiry ID.', 'maneli-car-inquiry')]);
            return;
        }
        
        $inquiry = get_post($inquiry_id);
        $current_user_id = get_current_user_id();
        $can_view = false;
        
        // Use Permission Helper if possible, otherwise use manual check
        if (class_exists('Maneli_Permission_Helpers')) {
            $can_view = Maneli_Permission_Helpers::can_user_view_inquiry($inquiry_id, $current_user_id);
        } else {
             if (current_user_can('manage_maneli_inquiries') || (int)$inquiry->post_author === $current_user_id) {
                $can_view = true;
            } elseif (in_array('maneli_expert', get_userdata($current_user_id)->roles)) {
                $assigned_expert_id = (int)get_post_meta($inquiry_id, 'assigned_expert_id', true);
                if ($assigned_expert_id === $current_user_id) $can_view = true;
            }
        }
        

        if (!$can_view) {
            wp_send_json_error(['message' => esc_html__('You do not have permission to view this report.', 'maneli-car-inquiry')], 403);
            return;
        }

        $post_meta = get_post_meta($inquiry_id);
        $finotex_data = get_post_meta($inquiry_id, '_finotex_response_data', true);
        $product_id = $post_meta['product_id'][0] ?? 0;
        $status = $post_meta['inquiry_status'][0] ?? 'pending';

        $data = [
            'id' => $inquiry_id,
            'status_label' => Maneli_CPT_Handler::get_status_label($status),
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
            'buyer' => ['first_name' => $post_meta['first_name'][0] ?? '', 'last_name' => $post_meta['last_name'][0] ?? ''],
            'issuer_type' => $post_meta['issuer_type'][0] ?? 'self',
            'finotex' => ['skipped' => (empty($finotex_data) || ($finotex_data['status'] ?? '') === 'SKIPPED'), 'color_code' => $finotex_data['result']['chequeColor'] ?? 0],
        ];
        wp_send_json_success($data);
    }
    
    public function ajax_filter_inquiries() {
        check_ajax_referer('maneli_inquiry_filter_nonce', '_ajax_nonce');
        if (!is_user_logged_in() || !(current_user_can('manage_maneli_inquiries') || in_array('maneli_expert', wp_get_current_user()->roles))) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
            return;
        }
    
        $paged = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $search_query = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
        $base_url = isset($_POST['base_url']) ? esc_url_raw($_POST['base_url']) : home_url();
        
        $args = ['post_type' => 'inquiry', 'posts_per_page' => 50, 'paged' => $paged, 'orderby' => 'date', 'order' => 'DESC', 'post_status' => 'publish'];
        $meta_query = ['relation' => 'AND'];
    
        if (!empty($search_query)) {
            $user_ids = get_users(['fields' => 'ID', 'search' => '*' . esc_attr($search_query) . '*', 'search_columns' => ['user_login', 'user_email', 'display_name']]);
            $product_ids = wc_get_products(['s' => $search_query, 'limit' => -1, 'return' => 'ids']);
            $search_meta_query = ['relation' => 'OR'];
            if(!empty($user_ids)) $search_meta_query[] = ['key' => 'post_author', 'value' => $user_ids, 'compare' => 'IN'];
            if(!empty($product_ids)) $search_meta_query[] = ['key' => 'product_id', 'value' => $product_ids, 'compare' => 'IN'];
            $search_meta_query[] = ['key' => 'national_code', 'value' => $search_query, 'compare' => 'LIKE'];
            $search_meta_query[] = ['key' => 'mobile_number', 'value' => $search_query, 'compare' => 'LIKE'];
            $meta_query[] = $search_meta_query;
        }
        
        if (!empty($_POST['status'])) $meta_query[] = ['key' => 'inquiry_status', 'value' => sanitize_text_field($_POST['status'])];
        if (current_user_can('manage_maneli_inquiries')) {
            if (!empty($_POST['expert'])) $meta_query[] = ['key' => 'assigned_expert_id', 'value' => absint($_POST['expert'])];
        } else {
            $meta_query[] = ['key' => 'assigned_expert_id', 'value' => get_current_user_id()];
        }
        
        if (count($meta_query) > 1) $args['meta_query'] = $meta_query;
    
        $inquiry_query = new WP_Query($args);
        ob_start();
        if ($inquiry_query->have_posts()) {
            while ($inquiry_query->have_posts()) {
                $inquiry_query->the_post();
                Maneli_Render_Helpers::render_inquiry_row(get_the_ID(), $base_url);
            }
        } else {
            $columns = current_user_can('manage_maneli_inquiries') ? 7 : 6;
            echo '<tr><td colspan="' . $columns . '" style="text-align:center;">' . esc_html__('No inquiries found matching your criteria.', 'maneli-car-inquiry') . '</td></tr>';
        }
        $html = ob_get_clean();
        wp_reset_postdata();
        
        $pagination_html = paginate_links([
            'base' => add_query_arg('paged', '%#%'), 
            'format' => '?paged=%#%', 
            'current' => $paged, 
            'total' => $inquiry_query->max_num_pages, 
            'prev_text' => esc_html__('&laquo; Previous', 'maneli-car-inquiry'), 
            'next_text' => esc_html__('Next &raquo;', 'maneli-car-inquiry'), 
            'type'  => 'plain'
        ]);
    
        wp_send_json_success(['html' => $html, 'pagination_html' => $pagination_html]);
    }
    
    /**
     * Handles deletion of an installment inquiry post via AJAX.
     */
    public function ajax_delete_inquiry() {
        check_ajax_referer('maneli_inquiry_delete_nonce', 'nonce');
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }

        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        if (!$inquiry_id || get_post_type($inquiry_id) !== 'inquiry') {
            wp_send_json_error(['message' => esc_html__('Invalid inquiry ID.', 'maneli-car-inquiry')]);
            return;
        }

        if (wp_delete_post($inquiry_id, true)) {
            wp_send_json_success(['message' => esc_html__('Inquiry deleted successfully.', 'maneli-car-inquiry')]);
        } else {
            wp_send_json_error(['message' => esc_html__('Error deleting inquiry.', 'maneli-car-inquiry')]);
        }
    }

    //======================================================================
    // Cash Inquiry Handlers
    //======================================================================
    
    public function ajax_get_cash_inquiry_details() {
        check_ajax_referer('maneli_cash_inquiry_details_nonce', 'nonce');
        if (!is_user_logged_in() || !(current_user_can('manage_maneli_inquiries') || in_array('maneli_expert', wp_get_current_user()->roles))) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }

        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        if (!$inquiry_id || get_post_type($inquiry_id) !== 'cash_inquiry') {
            wp_send_json_error(['message' => esc_html__('Invalid request ID.', 'maneli-car-inquiry')]);
            return;
        }

        // Security Check: Ensure the user has permission to view this specific inquiry
        if (class_exists('Maneli_Permission_Helpers') && !Maneli_Permission_Helpers::can_user_view_inquiry($inquiry_id, get_current_user_id())) {
             wp_send_json_error(['message' => esc_html__('You do not have permission to view this report.', 'maneli-car-inquiry')], 403);
             return;
        }

        $post_meta = get_post_meta($inquiry_id);
        $product_id = $post_meta['product_id'][0] ?? 0;

        $data = [
            'id' => $inquiry_id,
            'status_label' => Maneli_CPT_Handler::get_cash_inquiry_status_label($post_meta['cash_inquiry_status'][0] ?? 'pending'),
            'status_key' => $post_meta['cash_inquiry_status'][0] ?? 'pending',
            'rejection_reason' => $post_meta['cash_rejection_reason'][0] ?? '',
            'car' => [
                'name' => get_the_title($product_id),
                'color' => $post_meta['cash_car_color'][0] ?? '',
                'down_payment' => Maneli_Render_Helpers::format_money($post_meta['cash_down_payment'][0] ?? 0),
            ],
            'customer' => [
                'first_name' => $post_meta['cash_first_name'][0] ?? '',
                'last_name' => $post_meta['cash_last_name'][0] ?? '',
                'mobile' => $post_meta['mobile_number'][0] ?? '',
            ],
        ];
        wp_send_json_success($data);
    }

    public function ajax_update_cash_inquiry() {
        check_ajax_referer('maneli_cash_inquiry_update_nonce', 'nonce');
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }
    
        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        if (!$inquiry_id || get_post_type($inquiry_id) !== 'cash_inquiry') {
            wp_send_json_error(['message' => esc_html__('Invalid request ID.', 'maneli-car-inquiry')]);
            return;
        }
    
        update_post_meta($inquiry_id, 'cash_first_name', sanitize_text_field($_POST['first_name'] ?? ''));
        update_post_meta($inquiry_id, 'cash_last_name', sanitize_text_field($_POST['last_name'] ?? ''));
        update_post_meta($inquiry_id, 'mobile_number', sanitize_text_field($_POST['mobile'] ?? ''));
        update_post_meta($inquiry_id, 'cash_car_color', sanitize_text_field($_POST['color'] ?? ''));
    
        wp_send_json_success(['message' => esc_html__('Request updated successfully.', 'maneli-car-inquiry')]);
    }
    
    public function ajax_delete_cash_inquiry() {
        check_ajax_referer('maneli_cash_inquiry_delete_nonce', 'nonce');
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }
    
        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        if (!$inquiry_id || get_post_type($inquiry_id) !== 'cash_inquiry') {
            wp_send_json_error(['message' => esc_html__('Invalid request ID.', 'maneli-car-inquiry')]);
            return;
        }
    
        if (wp_delete_post($inquiry_id, true)) {
            wp_send_json_success(['message' => esc_html__('Request deleted successfully.', 'maneli-car-inquiry')]);
        } else {
            wp_send_json_error(['message' => esc_html__('Error deleting request.', 'maneli-car-inquiry')]);
        }
    }
    
    public function ajax_set_down_payment() {
        check_ajax_referer('maneli_cash_set_downpayment_nonce', 'nonce');
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }
    
        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        if (!$inquiry_id || get_post_type($inquiry_id) !== 'cash_inquiry') {
            wp_send_json_error(['message' => esc_html__('Invalid request ID.', 'maneli-car-inquiry')]);
            return;
        }
        
        $new_status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $sms_handler = new Maneli_SMS_Handler();
        $options = get_option('maneli_inquiry_all_options', []);
        $customer_name = get_post_meta($inquiry_id, 'cash_first_name', true) . ' ' . get_post_meta($inquiry_id, 'cash_last_name', true);
        $customer_mobile = get_post_meta($inquiry_id, 'mobile_number', true);
        $car_name = get_the_title(get_post_meta($inquiry_id, 'product_id', true));

        if ($new_status === 'awaiting_payment') {
            $amount = preg_replace('/[^0-9]/', '', $_POST['amount'] ?? 0);
            if ($amount <= 0) {
                wp_send_json_error(['message' => esc_html__('Please enter a valid amount.', 'maneli-car-inquiry')]);
                return;
            }
            update_post_meta($inquiry_id, 'cash_down_payment', $amount);
            update_post_meta($inquiry_id, 'cash_inquiry_status', 'awaiting_payment');
            
            $pattern_id = $options['cash_inquiry_approved_pattern'] ?? 0;
            if ($pattern_id > 0 && !empty($customer_mobile)) {
                $params = [(string)$customer_name, (string)$car_name, (string)Maneli_Render_Helpers::format_money($amount)];
                $sms_handler->send_pattern($pattern_id, $customer_mobile, $params);
            }
            
        } elseif ($new_status === 'rejected') {
            $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';
            if (empty($reason)) {
                wp_send_json_error(['message' => esc_html__('A reason for rejection is required.', 'maneli-car-inquiry')]);
                return;
            }
            update_post_meta($inquiry_id, 'cash_rejection_reason', $reason);
            update_post_meta($inquiry_id, 'cash_inquiry_status', 'rejected');
            
            $pattern_id = $options['cash_inquiry_rejected_pattern'] ?? 0;
            if ($pattern_id > 0 && !empty($customer_mobile)) {
                $params = [(string)$customer_name, (string)$car_name, (string)$reason];
                $sms_handler->send_pattern($pattern_id, $customer_mobile, $params);
            }
        } else {
            wp_send_json_error(['message' => esc_html__('Invalid data submitted.', 'maneli-car-inquiry')]);
            return;
        }
    
        wp_send_json_success(['message' => esc_html__('Status changed successfully.', 'maneli-car-inquiry')]);
    }
    
    public function ajax_filter_cash_inquiries() {
        check_ajax_referer('maneli_cash_inquiry_filter_nonce', '_ajax_nonce');
        if (!is_user_logged_in() || !(current_user_can('manage_maneli_inquiries') || in_array('maneli_expert', wp_get_current_user()->roles))) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
            return;
        }

        $paged = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $search_query = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
        $status_query = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : '';
        $base_url = isset($_POST['base_url']) ? esc_url_raw($_POST['base_url']) : home_url();

        $args = ['post_type' => 'cash_inquiry', 'posts_per_page' => 50, 'paged' => $paged, 'orderby' => 'date', 'order' => 'DESC', 'post_status' => 'publish'];
		$meta_query = ['relation' => 'AND'];

        if (!current_user_can('manage_maneli_inquiries')) {
            $meta_query[] = ['key' => 'assigned_expert_id', 'value' => get_current_user_id()];
        } elseif (!empty($_POST['expert'])) {
            $meta_query[] = ['key' => 'assigned_expert_id', 'value' => absint($_POST['expert'])];
        }

        if (!empty($search_query)) {
            $product_ids = wc_get_products(['s' => $search_query, 'limit' => -1, 'return' => 'ids']);
            $search_meta_query = ['relation' => 'OR', ['key' => 'cash_first_name', 'value' => $search_query, 'compare' => 'LIKE'], ['key' => 'cash_last_name', 'value' => $search_query, 'compare' => 'LIKE'], ['key' => 'mobile_number', 'value' => $search_query, 'compare' => 'LIKE']];
            if(!empty($product_ids)) $search_meta_query[] = ['key' => 'product_id', 'value' => $product_ids, 'compare' => 'IN'];
            $meta_query[] = $search_meta_query;
        }
		
		if (!empty($status_query)) $meta_query[] = ['key' => 'cash_inquiry_status', 'value' => $status_query, 'compare' => '='];
        if (count($meta_query) > 1) $args['meta_query'] = $meta_query;
        
        $inquiry_query = new WP_Query($args);
        ob_start();
        if ($inquiry_query->have_posts()) {
            while ($inquiry_query->have_posts()) {
                $inquiry_query->the_post();
                Maneli_Render_Helpers::render_cash_inquiry_row(get_the_ID(), $base_url);
            }
        } else {
            echo '<tr><td colspan="8" style="text-align:center;">' . esc_html__('No requests found.', 'maneli-car-inquiry') . '</td></tr>';
        }
        $html = ob_get_clean();
        wp_reset_postdata();
        
        $pagination_html = paginate_links([
            'base' => add_query_arg('paged', '%#%'), 
            'format' => '?paged=%#%', 
            'current' => $paged, 
            'total' => $inquiry_query->max_num_pages, 
            'prev_text' => esc_html__('&laquo; Previous', 'maneli-car-inquiry'),
            'next_text' => esc_html__('Next &raquo;', 'maneli-car-inquiry'),
            'type'  => 'plain'
        ]);
        wp_send_json_success(['html' => $html, 'pagination_html' => $pagination_html]);
    }
    
    //======================================================================
    // Expert Assignment Handlers
    //======================================================================
    
    public function ajax_assign_expert_to_cash_inquiry() {
        check_ajax_referer('maneli_cash_inquiry_assign_expert_nonce', 'nonce');
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }
    
        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        $expert_id_str = isset($_POST['expert_id']) ? sanitize_text_field($_POST['expert_id']) : 'auto';
    
        if (!$inquiry_id || get_post_type($inquiry_id) !== 'cash_inquiry') {
            wp_send_json_error(['message' => esc_html__('Invalid request ID.', 'maneli-car-inquiry')]);
            return;
        }
        
        $admin_actions = new Maneli_Admin_Actions_Handler();
        $expert_data = $admin_actions->assign_expert_to_post($inquiry_id, $expert_id_str, 'cash');

        if (is_wp_error($expert_data)) {
            wp_send_json_error(['message' => $expert_data->get_error_message()]);
            return;
        }
        
        update_post_meta($inquiry_id, 'cash_inquiry_status', 'approved');

        wp_send_json_success([
            'message' => sprintf(esc_html__('Request successfully assigned to %s.', 'maneli-car-inquiry'), $expert_data['name']),
            'expert_name' => $expert_data['name'],
            'new_status_label' => Maneli_CPT_Handler::get_cash_inquiry_status_label('approved')
        ]);
    }
    
    public function ajax_assign_expert_to_inquiry() {
        check_ajax_referer('maneli_inquiry_assign_expert_nonce', 'nonce');
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }
    
        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        $expert_id_str = isset($_POST['expert_id']) ? sanitize_text_field($_POST['expert_id']) : 'auto';
    
        if (!$inquiry_id || get_post_type($inquiry_id) !== 'inquiry') {
            wp_send_json_error(['message' => esc_html__('Invalid inquiry ID.', 'maneli-car-inquiry')]);
            return;
        }
    
        $admin_actions = new Maneli_Admin_Actions_Handler();
        $expert_data = $admin_actions->assign_expert_to_post($inquiry_id, $expert_id_str, 'installment');

        if (is_wp_error($expert_data)) {
            wp_send_json_error(['message' => $expert_data->get_error_message()]);
            return;
        }
        
        update_post_meta($inquiry_id, 'inquiry_status', 'user_confirmed');
    
        wp_send_json_success([
            'message' => sprintf(esc_html__('Inquiry successfully assigned to %s.', 'maneli-car-inquiry'), $expert_data['name']),
            'expert_name' => $expert_data['name'],
            'new_status_label' => Maneli_CPT_Handler::get_status_label('user_confirmed')
        ]);
    }
    
    //======================================================================
    // User Management Handlers
    //======================================================================
    
    public function ajax_filter_users() {
        check_ajax_referer('maneli_user_filter_nonce');
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
            return;
        }

        $paged = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $query_args = ['number' => 50, 'paged' => $paged, 'orderby' => sanitize_text_field($_POST['orderby'] ?? 'display_name'), 'order' => sanitize_text_field($_POST['order'] ?? 'ASC')];
        if (!empty($_POST['role'])) $query_args['role'] = sanitize_text_field($_POST['role']);
        if (!empty($_POST['search'])) {
            $search_term = sanitize_text_field($_POST['search']);
            $query_args['search'] = '*' . esc_attr($search_term) . '*';
            $query_args['search_columns'] = ['user_login', 'user_email', 'display_name'];
            $query_args['meta_query'] = ['relation' => 'OR', ['key' => 'first_name', 'value' => $search_term, 'compare' => 'LIKE'], ['key' => 'last_name', 'value' => $search_term, 'compare' => 'LIKE'], ['key' => 'mobile_number', 'value' => $search_term, 'compare' => 'LIKE'], ['key' => 'national_code', 'value' => $search_term, 'compare' => 'LIKE']];
        }

        $user_query = new WP_User_Query($query_args);
        $all_users = $user_query->get_results();
        $current_user_id = get_current_user_id();
        $current_url = isset($_POST['current_url']) ? esc_url($_POST['current_url']) : '';

        ob_start();
        if (!empty($all_users)) {
            foreach ($all_users as $user) {
                if ($user->ID === $current_user_id) continue;
                Maneli_Render_Helpers::render_user_list_row($user, $current_url);
            }
        } else {
            echo '<tr><td colspan="5" style="text-align:center;">' . esc_html__('No users found matching your criteria.', 'maneli-car-inquiry') . '</td></tr>';
        }
        $html = ob_get_clean();
        
        $pagination_html = paginate_links([
            'base' => '#', 
            'format' => '?paged=%#%', 
            'current' => $paged, 
            'total' => ceil($user_query->get_total() / 50), 
            'type' => 'plain', 
            'prev_text' => esc_html__('&laquo; Previous', 'maneli-car-inquiry'),
            'next_text' => esc_html__('Next &raquo;', 'maneli-car-inquiry')
        ]);
        wp_send_json_success(['html' => $html, 'pagination_html' => $pagination_html]);
    }

    public function ajax_delete_user() {
        check_ajax_referer('maneli_delete_user_nonce', '_ajax_nonce');
        // FIX: Added 'delete_users' capability check for security
        if (!current_user_can('manage_maneli_inquiries') || !current_user_can('delete_users')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
            return;
        }
        $user_id_to_delete = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        if (!$user_id_to_delete) {
            wp_send_json_error(['message' => esc_html__('User ID not specified.', 'maneli-car-inquiry')]);
            return;
        }
        if ($user_id_to_delete === get_current_user_id()) {
            wp_send_json_error(['message' => esc_html__('You cannot delete your own account.', 'maneli-car-inquiry')]);
            return;
        }
        require_once(ABSPATH . 'wp-admin/includes/user.php');
        if (wp_delete_user($user_id_to_delete)) {
            wp_send_json_success();
        } else {
            wp_send_json_error(['message' => esc_html__('An error occurred while deleting the user.', 'maneli-car-inquiry')]);
        }
    }

    //======================================================================
    // Product Editor Handlers
    //======================================================================
    
    public function ajax_filter_products() {
        check_ajax_referer('maneli_product_filter_nonce');
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')]);
            return;
        }
        $paged = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $args = ['limit' => 50, 'page' => $paged, 'orderby' => 'title', 'order' => 'ASC', 'paginate' => true];
        if (!empty($_POST['search'])) $args['s'] = sanitize_text_field(wp_unslash($_POST['search']));
        $query_result = wc_get_products($args);

        ob_start();
        if (!empty($query_result->products)) {
            foreach ($query_result->products as $product) {
                Maneli_Render_Helpers::render_product_editor_row($product);
            }
        } else {
            echo '<tr><td colspan="6" style="text-align:center;">' . esc_html__('No products found matching your criteria.', 'maneli-car-inquiry') . '</td></tr>';
        }
        $html = ob_get_clean();

        $pagination_html = paginate_links([
            'base' => '#', 
            'format' => '?paged=%#%', 
            'current' => $paged, 
            'total' => $query_result->max_num_pages, 
            'type' => 'plain', 
            'prev_text' => esc_html__('&laquo; Previous', 'maneli-car-inquiry'),
            'next_text' => esc_html__('Next &raquo;', 'maneli-car-inquiry')
        ]);
        wp_send_json_success(['html' => $html, 'pagination_html' => $pagination_html]);
    }
    
    public function ajax_update_product_data() {
        check_ajax_referer('maneli_product_data_nonce', 'nonce');
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'maneli-car-inquiry')], 403);
            return;
        }
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $field_type = isset($_POST['field_type']) ? sanitize_key($_POST['field_type']) : '';
        $field_value = isset($_POST['field_value']) ? sanitize_text_field(wp_unslash($_POST['field_value'])) : '';

        if (!$product_id || empty($field_type)) {
            wp_send_json_error(esc_html__('Invalid data sent.', 'maneli-car-inquiry'));
            return;
        }
        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error(esc_html__('Product not found.', 'maneli-car-inquiry'));
            return;
        }

        switch ($field_type) {
            case 'regular_price':
                $product->set_regular_price($field_value);
                $product->set_price($field_value); // Ensure sale price is not lower
                break;
            case 'installment_price': update_post_meta($product_id, 'installment_price', $field_value); break;
            case 'min_downpayment': update_post_meta($product_id, 'min_downpayment', $field_value); break;
            case 'car_colors': update_post_meta($product_id, '_maneli_car_colors', $field_value); break;
            case 'car_status': update_post_meta($product_id, '_maneli_car_status', $field_value); break;
            default: wp_send_json_error(esc_html__('Invalid field type.', 'maneli-car-inquiry'));
        }

        if ('regular_price' === $field_type) {
            $product->save();
        }
        wc_delete_product_transients($product_id); // Clear caches
        wp_send_json_success(esc_html__('Updated.', 'maneli-car-inquiry'));
    }
}