<?php
/**
 * مدیریت Ajax برای گزارشات
 * 
 * @package Autopuzzle_Car_Inquiry
 */

defined('ABSPATH') || exit;

class Autopuzzle_Reports_Ajax_Handler {
    
    /**
     * سازنده کلاس
     */
    public function __construct() {
        // Ajax برای یوزرهای لاگین شده
        add_action('wp_ajax_maneli_get_overall_stats', [$this, 'get_overall_stats']);
        add_action('wp_ajax_maneli_get_experts_stats', [$this, 'get_experts_stats']);
        add_action('wp_ajax_maneli_get_daily_stats', [$this, 'get_daily_stats']);
        add_action('wp_ajax_maneli_get_popular_products', [$this, 'get_popular_products']);
        add_action('wp_ajax_maneli_get_monthly_performance', [$this, 'get_monthly_performance']);
        add_action('wp_ajax_maneli_get_inquiries_details', [$this, 'get_inquiries_details']);
        add_action('wp_ajax_maneli_export_inquiries_csv', [$this, 'export_inquiries_csv']);
    }
    
    /**
     * دریافت آمار کلی
     */
    public function get_overall_stats() {
        check_ajax_referer('autopuzzle_reports_nonce', 'nonce');
        
        if (!current_user_can('manage_autopuzzle_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'autopuzzle')]);
        }
        
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : null;
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : null;
        $expert_id = isset($_POST['expert_id']) ? intval($_POST['expert_id']) : null;
        
        require_once plugin_dir_path(dirname(__FILE__)) . 'class-reports-dashboard.php';
        
        $stats = Autopuzzle_Reports_Dashboard::get_overall_statistics($start_date, $end_date, $expert_id);
        
        wp_send_json_success($stats);
    }
    
    /**
     * دریافت آمار کارشناسان
     */
    public function get_experts_stats() {
        check_ajax_referer('autopuzzle_reports_nonce', 'nonce');
        
        if (!current_user_can('manage_autopuzzle_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'autopuzzle')]);
        }
        
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : null;
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : null;
        
        require_once plugin_dir_path(dirname(__FILE__)) . 'class-reports-dashboard.php';
        
        $stats = Autopuzzle_Reports_Dashboard::get_experts_statistics($start_date, $end_date);
        
        wp_send_json_success($stats);
    }
    
    /**
     * دریافت آمار روزانه
     */
    public function get_daily_stats() {
        check_ajax_referer('autopuzzle_reports_nonce', 'nonce');
        
        if (!current_user_can('manage_autopuzzle_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'autopuzzle')]);
        }
        
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : null;
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : null;
        $expert_id = isset($_POST['expert_id']) ? intval($_POST['expert_id']) : null;
        
        require_once plugin_dir_path(dirname(__FILE__)) . 'class-reports-dashboard.php';
        
        $stats = Autopuzzle_Reports_Dashboard::get_daily_statistics($start_date, $end_date, $expert_id);
        
        wp_send_json_success($stats);
    }
    
    /**
     * دریافت محصولات پرطرفدار
     */
    public function get_popular_products() {
        check_ajax_referer('autopuzzle_reports_nonce', 'nonce');
        
        if (!current_user_can('manage_autopuzzle_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'autopuzzle')]);
        }
        
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : null;
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : null;
        $expert_id = isset($_POST['expert_id']) ? intval($_POST['expert_id']) : null;
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        
        require_once plugin_dir_path(dirname(__FILE__)) . 'class-reports-dashboard.php';
        
        $products = Autopuzzle_Reports_Dashboard::get_popular_products($start_date, $end_date, $expert_id, $limit);
        
        wp_send_json_success($products);
    }
    
    /**
     * دریافت عملکرد ماهانه
     */
    public function get_monthly_performance() {
        check_ajax_referer('autopuzzle_reports_nonce', 'nonce');
        
        if (!current_user_can('manage_autopuzzle_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'autopuzzle')]);
        }
        
        $months = isset($_POST['months']) ? intval($_POST['months']) : 6;
        $expert_id = isset($_POST['expert_id']) ? intval($_POST['expert_id']) : null;
        
        require_once plugin_dir_path(dirname(__FILE__)) . 'class-reports-dashboard.php';
        
        $performance = Autopuzzle_Reports_Dashboard::get_monthly_performance($months, $expert_id);
        
        wp_send_json_success($performance);
    }
    
    /**
     * دریافت جزئیات استعلام‌ها
     */
    public function get_inquiries_details() {
        check_ajax_referer('autopuzzle_reports_nonce', 'nonce');
        
        if (!current_user_can('manage_autopuzzle_inquiries')) {
            wp_send_json_error(['message' => esc_html__('Unauthorized access.', 'autopuzzle')]);
        }
        
        $args = [
            'start_date' => isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : null,
            'end_date' => isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : null,
            'expert_id' => isset($_POST['expert_id']) ? intval($_POST['expert_id']) : null,
            'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : null,
            'type' => isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'all',
            'limit' => isset($_POST['limit']) ? intval($_POST['limit']) : 50,
            'offset' => isset($_POST['offset']) ? intval($_POST['offset']) : 0,
            'orderby' => isset($_POST['orderby']) ? sanitize_text_field($_POST['orderby']) : 'date',
            'order' => isset($_POST['order']) ? sanitize_text_field($_POST['order']) : 'DESC',
        ];
        
        require_once plugin_dir_path(dirname(__FILE__)) . 'class-reports-dashboard.php';
        
        $data = Autopuzzle_Reports_Dashboard::get_inquiries_details($args);
        
        wp_send_json_success($data);
    }
    
    /**
     * صادرات به CSV
     */
    public function export_inquiries_csv() {
        check_ajax_referer('autopuzzle_reports_nonce', 'nonce');
        
        if (!current_user_can('manage_autopuzzle_inquiries')) {
            wp_die(esc_html__('Unauthorized access.', 'autopuzzle'));
        }
        
        $args = [
            'start_date' => isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : null,
            'end_date' => isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : null,
            'expert_id' => isset($_GET['expert_id']) ? intval($_GET['expert_id']) : null,
            'status' => isset($_GET['status']) ? sanitize_text_field($_GET['status']) : null,
            'type' => isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'all',
            'limit' => 10000, // حداکثر برای export
        ];
        
        require_once plugin_dir_path(dirname(__FILE__)) . 'class-reports-dashboard.php';
        
        Autopuzzle_Reports_Dashboard::export_to_csv($args);
    }
}

// ایجاد نمونه از کلاس
new Autopuzzle_Reports_Ajax_Handler();

