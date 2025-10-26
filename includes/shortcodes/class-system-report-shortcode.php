<?php
/**
 * Handles the [maneli_system_report] shortcode, which displays a general system overview for administrators.
 *
 * @package Maneli_Car_Inquiry/Includes/Shortcodes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_System_Report_Shortcode {

    public function __construct() {
        add_shortcode('maneli_system_report', [$this, 'render_shortcode']);
        
        // Ajax handlers برای بارگذاری داده‌ها
        add_action('wp_ajax_maneli_shortcode_get_stats', [$this, 'ajax_get_stats']);
        add_action('wp_ajax_nopriv_maneli_shortcode_get_stats', [$this, 'ajax_get_stats']);
    }

    /**
     * Renders the system report shortcode.
     * It checks for user capabilities and then delegates the rendering to a template file.
     *
     * @param array $atts Shortcode attributes
     * @return string The HTML output for the shortcode.
     */
    public function render_shortcode($atts) {
        // پارامترهای پیش‌فرض
        $atts = shortcode_atts([
            'days' => '30',           // تعداد روز
            'show_charts' => 'yes',   // نمایش نمودارها
            'show_experts' => 'yes',  // نمایش آمار کارشناسان
            'expert_id' => '',        // آی‌دی کارشناس خاص
        ], $atts);
        
        // 1. Security Check: Ensure only users with the master capability can view this report.
        if (!current_user_can('manage_maneli_inquiries')) {
            return '<div class="maneli-inquiry-wrapper error-box"><p>' . esc_html__('You do not have sufficient permissions to access this page.', 'maneli-car-inquiry') . '</p></div>';
        }
        
        // بارگذاری کلاس گزارشات
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-reports-dashboard.php';
        
        // محاسبه بازه زمانی
        $days = intval($atts['days']);
        $start_date = date('Y-m-d', strtotime("-{$days} days"));
        $end_date = date('Y-m-d');
        
        // تعیین کارشناس
        $expert_id = null;
        $current_user = wp_get_current_user();
        
        if (!empty($atts['expert_id'])) {
            $expert_id = intval($atts['expert_id']);
        } elseif (in_array('maneli_expert', $current_user->roles)) {
            // اگر کارشناس است، فقط آمار خودش را نشان بده
            $expert_id = $current_user->ID;
        }
        
        // دریافت آمار
        $stats = Maneli_Reports_Dashboard::get_overall_statistics($start_date, $end_date, $expert_id);
        $daily_stats = Maneli_Reports_Dashboard::get_daily_statistics($start_date, $end_date, $expert_id);
        $popular_products = Maneli_Reports_Dashboard::get_popular_products($start_date, $end_date, $expert_id, 5);
        
        // آمار کارشناسان (فقط برای مدیر)
        $experts_stats = [];
        if ($atts['show_experts'] === 'yes' && current_user_can('manage_options') && !$expert_id) {
            $experts_stats = Maneli_Reports_Dashboard::get_experts_statistics($start_date, $end_date);
        }
        
        // بارگذاری استایل و اسکریپت
        $this->enqueue_assets();
        
        // 2. Prepare data to be passed to the template.
        $template_args = [
            'stats' => $stats,
            'daily_stats' => $daily_stats,
            'popular_products' => $popular_products,
            'experts_stats' => $experts_stats,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'days' => $days,
            'show_charts' => $atts['show_charts'] === 'yes',
            'show_experts' => $atts['show_experts'] === 'yes',
            'current_user' => $current_user,
            'is_expert' => in_array('maneli_expert', $current_user->roles),
        ];
        
        // 3. Render the template file.
        // The `false` argument tells the function to return the output as a string.
        return maneli_get_template_part('shortcodes/system-report', $template_args, false);
    }
    
    /**
     * بارگذاری استایل و اسکریپت
     */
    private function enqueue_assets() {
        // Dashicons
        wp_enqueue_style('dashicons');
        
        // Chart.js
        if (!wp_script_is('chartjs', 'registered')) {
            wp_enqueue_script(
                'chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
                [],
                '4.4.0',
                true
            );
        }
    }
    
    /**
     * Ajax handler برای دریافت آمار
     */
    public function ajax_get_stats() {
        // بررسی nonce
        check_ajax_referer('maneli_reports_shortcode_nonce', 'nonce');
        
        // بررسی دسترسی
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
        }
        
        // بارگذاری کلاس گزارشات
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-reports-dashboard.php';
        
        // دریافت پارامترها
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : null;
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : null;
        $expert_id = isset($_POST['expert_id']) ? intval($_POST['expert_id']) : null;
        
        // اگر کارشناس است، فقط آمار خودش
        $current_user = wp_get_current_user();
        if (in_array('maneli_expert', $current_user->roles) && !current_user_can('manage_options')) {
            $expert_id = $current_user->ID;
        }
        
        // دریافت آمار
        $stats = Maneli_Reports_Dashboard::get_overall_statistics($start_date, $end_date, $expert_id);
        $daily_stats = Maneli_Reports_Dashboard::get_daily_statistics($start_date, $end_date, $expert_id);
        $popular_products = Maneli_Reports_Dashboard::get_popular_products($start_date, $end_date, $expert_id, 5);
        
        // آمار کارشناسان (فقط برای مدیر)
        $experts_stats = [];
        if (current_user_can('manage_options') && !$expert_id) {
            $experts_stats = Maneli_Reports_Dashboard::get_experts_statistics($start_date, $end_date);
        }
        
        wp_send_json_success([
            'stats' => $stats,
            'daily_stats' => $daily_stats,
            'popular_products' => $popular_products,
            'experts_stats' => $experts_stats,
        ]);
    }
}