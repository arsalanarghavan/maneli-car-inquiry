<?php
/**
 * Handles the logic and rendering for Admin Dashboard Widgets and Statistics.
 * این کلاس برای ارائه متدهای استاتیک نمایش ویجت‌های آماری به داشبورد وردپرس و شورت‌کدها استفاده می‌شود.
 *
 * @package Maneli_Car_Inquiry/Includes
 * @author  Maneli
 * @version 1.0.2 (Implemented live statistics)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Admin_Dashboard_Widgets {

    /**
     * Constructor.
     */
    public function __construct() {
        // افزودن ویجت‌های داشبورد فقط برای نقش‌هایی که دسترسی دارند
        // اگرچه هیچ رولی به پیشخوان دسترسی ندارد، این ویجت‌ها برای admin/maneli_admin نمایش داده می‌شوند.
        add_action('wp_dashboard_setup', array($this, 'register_dashboard_widgets'));
    }

    /**
     * Registers the custom dashboard widgets.
     */
    public function register_dashboard_widgets() {
        // از فرض وجود کلاس Maneli_Permission_Helpers برای بررسی دسترسی استفاده شده است.
        if (!current_user_can('manage_maneli_inquiries')) {
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
     * Helper function to get inquiry counts based on type.
     * @param string $post_type The post type ('inquiry' or 'cash_inquiry').
     * @return array Counts.
     */
    private static function get_inquiry_counts($post_type) {
        global $wpdb;
        $counts = ['total' => 0];

        $status_key = ($post_type === 'inquiry') ? 'inquiry_status' : 'cash_inquiry_status';

        // Note: Using prepare for meta_key and post_type is generally not needed 
        // as they are known internal values, but is used here for completeness.
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT pm.meta_value as status, COUNT(p.ID) as count
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = %s
            AND p.post_status = 'publish'
            AND pm.meta_key = %s
            GROUP BY status
        ", $post_type, $status_key), ARRAY_A);

        foreach ($results as $row) {
            $counts[$row['status']] = (int)$row['count'];
            $counts['total'] += (int)$row['count'];
        }

        return $counts;
    }

    /**
     * Renders the statistics widgets for installment inquiries.
     * این متد هم برای ویجت داشبورد و هم برای فراخوانی استاتیک در شورت‌کدها استفاده می‌شود.
     */
    public static function render_inquiry_statistics_widgets() {
        if (!class_exists('Maneli_CPT_Handler')) return '';

        $counts = self::get_inquiry_counts('inquiry');
        $statuses = Maneli_CPT_Handler::get_all_statuses();
        
        $stats = [
            'total'          => ['label' => esc_html__('Total Inquiries', 'maneli-car-inquiry'), 'count' => $counts['total'], 'class' => 'total'],
            'pending'        => ['label' => $statuses['pending'], 'count' => $counts['pending'] ?? 0, 'class' => 'pending'],
            'user_confirmed' => ['label' => $statuses['user_confirmed'], 'count' => $counts['user_confirmed'] ?? 0, 'class' => 'approved'],
            'more_docs'      => ['label' => $statuses['more_docs'] ?? esc_html__('Docs Req.', 'maneli-car-inquiry'), 'count' => $counts['more_docs'] ?? 0, 'class' => 'more-docs'],
            'rejected'       => ['label' => $statuses['rejected'], 'count' => $counts['rejected'] ?? 0, 'class' => 'rejected'],
            'failed'         => ['label' => $statuses['failed'], 'count' => $counts['failed'] ?? 0, 'class' => 'failed'],
        ];

        ob_start();
        ?>
        <div class="maneli-dashboard-stats-wrapper">
            <?php foreach ($stats as $stat) : ?>
                <div class="stat-widget stat-<?php echo esc_attr($stat['class']); ?>">
                    <span class="stat-count"><?php echo number_format_i18n($stat['count']); ?></span>
                    <span class="stat-label"><?php echo esc_html($stat['label']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renders the statistics widgets for cash inquiries.
     */
    public static function render_cash_inquiry_statistics_widgets() {
        if (!class_exists('Maneli_CPT_Handler')) return '';

        $counts = self::get_inquiry_counts('cash_inquiry');
        $statuses = Maneli_CPT_Handler::get_all_cash_inquiry_statuses();
        
        $stats = [
            'total'            => ['label' => esc_html__('Total Cash Requests', 'maneli-car-inquiry'), 'count' => $counts['total'], 'class' => 'total'],
            'pending'          => ['label' => $statuses['pending'], 'count' => $counts['pending'] ?? 0, 'class' => 'pending'],
            'approved'         => ['label' => $statuses['approved'], 'count' => $counts['approved'] ?? 0, 'class' => 'referred'],
            'awaiting_payment' => ['label' => $statuses['awaiting_payment'], 'count' => $counts['awaiting_payment'] ?? 0, 'class' => 'awaiting'],
            'completed'        => ['label' => $statuses['completed'], 'count' => $counts['completed'] ?? 0, 'class' => 'approved'],
            'rejected'         => ['label' => $statuses['rejected'], 'count' => $counts['rejected'] ?? 0, 'class' => 'rejected'],
        ];

        ob_start();
        ?>
        <div class="maneli-dashboard-stats-wrapper">
            <?php foreach ($stats as $stat) : ?>
                <div class="stat-widget stat-<?php echo esc_attr($stat['class']); ?>">
                    <span class="stat-count"><?php echo number_format_i18n($stat['count']); ?></span>
                    <span class="stat-label"><?php echo esc_html($stat['label']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renders user statistics widgets.
     */
    public static function render_user_statistics_widgets() {
        $total_users = count_users();
        $expert_count = $total_users['avail_roles']['maneli_expert'] ?? 0;
        
        $stats = [
            'total_users' => ['label' => esc_html__('Total Users', 'maneli-car-inquiry'), 'count' => $total_users['total_users'], 'class' => 'total'],
            'experts'     => ['label' => esc_html__('Maneli Experts', 'maneli-car-inquiry'), 'count' => $expert_count, 'class' => 'expert'],
            'admins'      => ['label' => esc_html__('Managers', 'maneli-car-inquiry'), 'count' => $total_users['avail_roles']['maneli_admin'] ?? 0, 'class' => 'admin'],
        ];
        
        ob_start();
        ?>
        <div class="maneli-dashboard-stats-wrapper">
            <?php foreach ($stats as $stat) : ?>
                <div class="stat-widget stat-<?php echo esc_attr($stat['class']); ?>">
                    <span class="stat-count"><?php echo number_format_i18n($stat['count']); ?></span>
                    <span class="stat-label"><?php echo esc_html($stat['label']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renders product statistics widgets.
     */
    public static function render_product_statistics_widgets() {
        if (!function_exists('wc_get_products')) return '';
        
        $total_products = wp_count_posts('product')->publish;
        
        $active_products_count = count(wc_get_products(['limit' => -1, 'status' => 'publish', 'meta_key' => '_maneli_car_status', 'meta_value' => 'special_sale', 'return' => 'ids']));
        $unavailable_products_count = count(wc_get_products(['limit' => -1, 'status' => 'publish', 'meta_key' => '_maneli_car_status', 'meta_value' => 'unavailable', 'return' => 'ids']));
        $disabled_products_count = count(wc_get_products(['limit' => -1, 'status' => 'publish', 'meta_key' => '_maneli_car_status', 'meta_value' => 'disabled', 'return' => 'ids']));
        
        $stats = [
            'total'       => ['label' => esc_html__('Total Products', 'maneli-car-inquiry'), 'count' => $total_products, 'class' => 'total'],
            'active_sale' => ['label' => esc_html__('Active for Sale', 'maneli-car-inquiry'), 'count' => $active_products_count, 'class' => 'active'],
            'unavailable' => ['label' => esc_html__('Unavailable', 'maneli-car-inquiry'), 'count' => $unavailable_products_count, 'class' => 'unavailable'],
            'disabled'    => ['label' => esc_html__('Hidden Products', 'maneli-car-inquiry'), 'count' => $disabled_products_count, 'class' => 'disabled'],
        ];

        ob_start();
        ?>
        <div class="maneli-dashboard-stats-wrapper">
            <?php foreach ($stats as $stat) : ?>
                <div class="stat-widget stat-<?php echo esc_attr($stat['class']); ?>">
                    <span class="stat-count"><?php echo number_format_i18n($stat['count']); ?></span>
                    <span class="stat-label"><?php echo esc_html($stat['label']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}