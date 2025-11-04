<?php
/**
 * Visitor Statistics AJAX Handler
 * مدیریت AJAX برای آمار بازدیدکنندگان
 * 
 * @package Maneli_Car_Inquiry
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Visitor_Statistics_Handler {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Track visit via AJAX (client-side)
        add_action('wp_ajax_maneli_track_visit', [$this, 'ajax_track_visit']);
        add_action('wp_ajax_nopriv_maneli_track_visit', [$this, 'ajax_track_visit']);
        
        // Get statistics via AJAX
        add_action('wp_ajax_maneli_get_statistics', [$this, 'ajax_get_statistics']);
        add_action('wp_ajax_nopriv_maneli_get_statistics', [$this, 'ajax_get_statistics']);
        
        // Get specific stats
        add_action('wp_ajax_maneli_get_daily_stats', [$this, 'ajax_get_daily_stats']);
        add_action('wp_ajax_maneli_get_top_pages', [$this, 'ajax_get_top_pages']);
        add_action('wp_ajax_maneli_get_browser_stats', [$this, 'ajax_get_browser_stats']);
        add_action('wp_ajax_maneli_get_os_stats', [$this, 'ajax_get_os_stats']);
        add_action('wp_ajax_maneli_get_device_stats', [$this, 'ajax_get_device_stats']);
        add_action('wp_ajax_maneli_get_country_stats', [$this, 'ajax_get_country_stats']);
        add_action('wp_ajax_maneli_get_search_engine_stats', [$this, 'ajax_get_search_engine_stats']);
        add_action('wp_ajax_maneli_get_referrer_stats', [$this, 'ajax_get_referrer_stats']);
        add_action('wp_ajax_maneli_get_top_products', [$this, 'ajax_get_top_products']);
        add_action('wp_ajax_maneli_get_recent_visitors', [$this, 'ajax_get_recent_visitors']);
        add_action('wp_ajax_maneli_get_online_visitors', [$this, 'ajax_get_online_visitors']);
        add_action('wp_ajax_maneli_get_most_active_visitors', [$this, 'ajax_get_most_active_visitors']);
        add_action('wp_ajax_maneli_get_device_model_stats', [$this, 'ajax_get_device_model_stats']);
    }
    
    /**
     * AJAX: Track visit
     */
    public function ajax_track_visit() {
        // Check nonce for security
        check_ajax_referer('maneli_visitor_stats_nonce', 'nonce');
        
        // Rate limiting
        $ip = $this->get_client_ip();
        $transient_key = 'maneli_track_visit_' . md5($ip);
        if (get_transient($transient_key)) {
            wp_send_json_error(['message' => 'Too many requests']);
            return;
        }
        set_transient($transient_key, true, 5); // 5 seconds
        
        $page_url = isset($_POST['page_url']) ? esc_url_raw($_POST['page_url']) : null;
        $page_title = isset($_POST['page_title']) ? sanitize_text_field($_POST['page_title']) : null;
        $referrer = isset($_POST['referrer']) ? esc_url_raw($_POST['referrer']) : null;
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : null;
        
        $result = Maneli_Visitor_Statistics::track_visit($page_url, $page_title, $referrer, $product_id);
        
        if ($result) {
            wp_send_json_success(['message' => 'Visit tracked']);
        } else {
            wp_send_json_error(['message' => 'Failed to track visit']);
        }
    }
    
    /**
     * AJAX: Get overall statistics
     */
    public function ajax_get_statistics() {
        // Check permissions
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => 'Unauthorized']);
            return;
        }
        
        check_ajax_referer('maneli_visitor_stats_nonce', 'nonce');
        
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : null;
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : null;
        
        $stats = Maneli_Visitor_Statistics::get_overall_stats($start_date, $end_date);
        
        wp_send_json_success($stats);
    }
    
    /**
     * AJAX: Get daily statistics
     */
    public function ajax_get_daily_stats() {
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => 'Unauthorized']);
            return;
        }
        
        check_ajax_referer('maneli_visitor_stats_nonce', 'nonce');
        
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : null;
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : null;
        
        $stats = Maneli_Visitor_Statistics::get_daily_visits($start_date, $end_date);
        
        wp_send_json_success($stats);
    }
    
    /**
     * AJAX: Get top pages
     */
    public function ajax_get_top_pages() {
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => 'Unauthorized']);
            return;
        }
        
        check_ajax_referer('maneli_visitor_stats_nonce', 'nonce');
        
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : null;
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : null;
        
        $stats = Maneli_Visitor_Statistics::get_top_pages($limit, $start_date, $end_date);
        
        wp_send_json_success($stats);
    }
    
    /**
     * AJAX: Get browser statistics
     */
    public function ajax_get_browser_stats() {
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => 'Unauthorized']);
            return;
        }
        
        check_ajax_referer('maneli_visitor_stats_nonce', 'nonce');
        
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : null;
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : null;
        
        $stats = Maneli_Visitor_Statistics::get_browser_stats($start_date, $end_date);
        
        wp_send_json_success($stats);
    }
    
    /**
     * AJAX: Get OS statistics
     */
    public function ajax_get_os_stats() {
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => 'Unauthorized']);
            return;
        }
        
        check_ajax_referer('maneli_visitor_stats_nonce', 'nonce');
        
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : null;
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : null;
        
        $stats = Maneli_Visitor_Statistics::get_os_stats($start_date, $end_date);
        
        wp_send_json_success($stats);
    }
    
    /**
     * AJAX: Get device statistics
     */
    public function ajax_get_device_stats() {
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => 'Unauthorized']);
            return;
        }
        
        check_ajax_referer('maneli_visitor_stats_nonce', 'nonce');
        
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : null;
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : null;
        
        $stats = Maneli_Visitor_Statistics::get_device_stats($start_date, $end_date);
        
        wp_send_json_success($stats);
    }
    
    /**
     * AJAX: Get country statistics
     */
    public function ajax_get_country_stats() {
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => 'Unauthorized']);
            return;
        }
        
        check_ajax_referer('maneli_visitor_stats_nonce', 'nonce');
        
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : null;
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : null;
        
        $stats = Maneli_Visitor_Statistics::get_country_stats($start_date, $end_date);
        
        wp_send_json_success($stats);
    }
    
    /**
     * AJAX: Get search engine statistics
     */
    public function ajax_get_search_engine_stats() {
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => 'Unauthorized']);
            return;
        }
        
        check_ajax_referer('maneli_visitor_stats_nonce', 'nonce');
        
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : null;
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : null;
        
        $stats = Maneli_Visitor_Statistics::get_search_engine_stats($start_date, $end_date);
        
        wp_send_json_success($stats);
    }
    
    /**
     * AJAX: Get referrer statistics
     */
    public function ajax_get_referrer_stats() {
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => 'Unauthorized']);
            return;
        }
        
        check_ajax_referer('maneli_visitor_stats_nonce', 'nonce');
        
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : null;
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : null;
        
        $stats = Maneli_Visitor_Statistics::get_referrer_stats($limit, $start_date, $end_date);
        
        wp_send_json_success($stats);
    }
    
    /**
     * AJAX: Get top products
     */
    public function ajax_get_top_products() {
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => 'Unauthorized']);
            return;
        }
        
        check_ajax_referer('maneli_visitor_stats_nonce', 'nonce');
        
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : null;
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : null;
        
        $stats = Maneli_Visitor_Statistics::get_top_products($limit, $start_date, $end_date);
        
        wp_send_json_success($stats);
    }
    
    /**
     * AJAX: Get recent visitors
     */
    public function ajax_get_recent_visitors() {
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => 'Unauthorized']);
            return;
        }
        
        check_ajax_referer('maneli_visitor_stats_nonce', 'nonce');
        
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 50;
        
        $stats = Maneli_Visitor_Statistics::get_recent_visitors($limit);
        
        wp_send_json_success($stats);
    }
    
    /**
     * AJAX: Get online visitors
     */
    public function ajax_get_online_visitors() {
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => 'Unauthorized']);
            return;
        }
        
        check_ajax_referer('maneli_visitor_stats_nonce', 'nonce');
        
        $stats = Maneli_Visitor_Statistics::get_online_visitors();
        
        wp_send_json_success($stats);
    }
    
    /**
     * AJAX: Get most active visitors
     */
    public function ajax_get_most_active_visitors() {
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => 'Unauthorized']);
            return;
        }
        
        check_ajax_referer('maneli_visitor_stats_nonce', 'nonce');
        
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : null;
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : null;
        
        $stats = Maneli_Visitor_Statistics::get_most_active_visitors($limit, $start_date, $end_date);
        
        wp_send_json_success($stats);
    }
    
    /**
     * AJAX: Get device model statistics
     */
    public function ajax_get_device_model_stats() {
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => 'Unauthorized']);
            return;
        }
        
        check_ajax_referer('maneli_visitor_stats_nonce', 'nonce');
        
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : null;
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : null;
        
        $stats = Maneli_Visitor_Statistics::get_device_model_stats($limit, $start_date, $end_date);
        
        wp_send_json_success($stats);
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
}

// Initialize handler
new Maneli_Visitor_Statistics_Handler();


