<?php
/**
 * Handles the logic and rendering for Admin Dashboard Widgets and Statistics.
 * این کلاس برای ارائه متدهای استاتیک نمایش ویجت‌های آماری به داشبورد وردپرس و شورت‌کدها استفاده می‌شود.
 *
 * @package Maneli_Car_Inquiry/Includes
 * @author  Maneli
 * @version 1.0.1
 */

if (!defined('ABSPATH')) {
    exit;
}

// **رفع خطای FATAL:** نام کلاس از Maneli_Grouped_Attributes به نام صحیح Maneli_Admin_Dashboard_Widgets تغییر یافت.
// متدهای رندر به صورت استاتیک تعریف شده‌اند تا فراخوانی‌های شورت‌کد و تمپلیت‌ها با خطا مواجه نشوند.
class Maneli_Admin_Dashboard_Widgets {

    /**
     * Constructor.
     */
    public function __construct() {
        // افزودن ویجت‌های داشبورد فقط برای نقش‌هایی که دسترسی دارند
        add_action('wp_dashboard_setup', array($this, 'register_dashboard_widgets'));
    }

    /**
     * Registers the custom dashboard widgets.
     */
    public function register_dashboard_widgets() {
        // از فرض وجود کلاس Maneli_Permission_Helpers برای بررسی دسترسی استفاده شده است.
        if (class_exists('Maneli_Permission_Helpers') && !Maneli_Permission_Helpers::current_user_can_view_inquiry_list()) {
            return;
        }
        
        // ویجت‌های آماری استعلام قسطی
        wp_add_dashboard_widget(
            'maneli_inquiry_stats',
            esc_html__('Installment Inquiry Statistics', 'maneli-car-inquiry'),
            array(__CLASS__, 'render_inquiry_statistics_widgets') 
        );

        // ویجت‌های آماری استعلام نقدی
        wp_add_dashboard_widget(
            'maneli_cash_inquiry_stats',
            esc_html__('Cash Inquiry Statistics', 'maneli-car-inquiry'),
            array(__CLASS__, 'render_cash_inquiry_statistics_widgets')
        );

        // ویجت‌های آماری کاربران و محصولات
        wp_add_dashboard_widget(
            'maneli_user_stats',
            esc_html__('User Statistics', 'maneli-car-inquiry'),
            array(__CLASS__, 'render_user_statistics_widgets')
        );
        
        wp_add_dashboard_widget(
            'maneli_product_stats',
            esc_html__('Product Statistics', 'maneli-car-inquiry'),
            array(__CLASS__, 'render_product_statistics_widgets')
        );
    }
    
    /**
     * Renders the statistics widgets for installment inquiries.
     * این متد هم برای ویجت داشبورد و هم برای فراخوانی استاتیک در شورت‌کدها استفاده می‌شود.
     */
    public static function render_inquiry_statistics_widgets() {
        // منطق آمارگیری باید اینجا پیاده‌سازی شود.
        echo '';
        echo '<div class="maneli-dashboard-stats-wrapper">';
        echo '<p style="padding: 10px; text-align: center; color: #777;">' . esc_html__('Installment Inquiry Statistics (Placeholder)', 'maneli-car-inquiry') . '</p>';
        echo '</div>';
    }

    /**
     * Renders the statistics widgets for cash inquiries.
     */
    public static function render_cash_inquiry_statistics_widgets() {
        // منطق آمارگیری باید اینجا پیاده‌سازی شود.
        echo '';
        echo '<div class="maneli-dashboard-stats-wrapper">';
        echo '<p style="padding: 10px; text-align: center; color: #777;">' . esc_html__('Cash Inquiry Statistics (Placeholder)', 'maneli-car-inquiry') . '</p>';
        echo '</div>';
    }

    /**
     * Renders user statistics widgets.
     */
    public static function render_user_statistics_widgets() {
        // منطق آمارگیری باید اینجا پیاده‌سازی شود.
        echo '';
        echo '<div class="maneli-dashboard-stats-wrapper">';
        echo '<p style="padding: 10px; text-align: center; color: #777;">' . esc_html__('User Statistics (Placeholder)', 'maneli-car-inquiry') . '</p>';
        echo '</div>';
    }

    /**
     * Renders product statistics widgets.
     */
    public static function render_product_statistics_widgets() {
        // منطق آمارگیری باید اینجا پیاده‌سازی شود.
        echo '';
        echo '<div class="maneli-dashboard-stats-wrapper">';
        echo '<p style="padding: 10px; text-align: center; color: #777;">' . esc_html__('Product Statistics (Placeholder)', 'maneli-car-inquiry') . '</p>';
        echo '</div>';
    }
}