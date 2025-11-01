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
        
        // ویجت گزارشات پیشرفته
        wp_add_dashboard_widget(
            'maneli_advanced_reports',
            '📊 ' . esc_html__('Advanced Reports', 'maneli-car-inquiry'),
            array(__CLASS__, 'render_advanced_reports_widget')
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
            'total'          => ['label' => esc_html__('کل درخواست‌های اقساطی', 'maneli-car-inquiry'), 'count' => $counts['total'], 'class' => 'total'],
            'pending'        => ['label' => $statuses['pending'] ?? esc_html__('در انتظار', 'maneli-car-inquiry'), 'count' => $counts['pending'] ?? 0, 'class' => 'pending'],
            'user_confirmed' => ['label' => $statuses['user_confirmed'] ?? esc_html__('تایید شده', 'maneli-car-inquiry'), 'count' => $counts['user_confirmed'] ?? 0, 'class' => 'confirmed'],
            'rejected'       => ['label' => $statuses['rejected'] ?? esc_html__('رد شد', 'maneli-car-inquiry'), 'count' => $counts['rejected'] ?? 0, 'class' => 'rejected'],
        ];

        // Define icon and color for each stat
        $stat_configs = [
            'total' => ['icon' => 'la-list-alt', 'color' => 'primary', 'bg' => 'bg-primary-transparent'],
            'pending' => ['icon' => 'la-clock', 'color' => 'warning', 'bg' => 'bg-warning-transparent'],
            'confirmed' => ['icon' => 'la-check-circle', 'color' => 'success', 'bg' => 'bg-success-transparent'],
            'rejected' => ['icon' => 'la-times-circle', 'color' => 'danger', 'bg' => 'bg-danger-transparent'],
        ];

        ob_start();
        ?>
        <div class="row mb-4">
            <?php foreach ($stats as $key => $stat) : 
                $config = $stat_configs[$stat['class']] ?? ['icon' => 'la-info-circle', 'color' => 'secondary', 'bg' => 'bg-secondary-transparent'];
            ?>
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                    <div class="card custom-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="flex-fill">
                                    <span class="d-block mb-1 text-muted fs-12"><?php echo esc_html($stat['label']); ?></span>
                                    <h4 class="mb-0 fw-semibold"><?php echo maneli_number_format_persian($stat['count']); ?></h4>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="avatar avatar-md avatar-rounded <?php echo esc_attr($config['bg']); ?>">
                                        <i class="la <?php echo esc_attr($config['icon']); ?> fs-20 text-<?php echo esc_attr($config['color']); ?>"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
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
            'total'            => ['label' => esc_html__('کل درخواست‌های نقدی', 'maneli-car-inquiry'), 'count' => $counts['total'], 'class' => 'total'],
            'approved'         => ['label' => $statuses['approved'] ?? esc_html__('تایید شده', 'maneli-car-inquiry'), 'count' => $counts['approved'] ?? 0, 'class' => 'approved'],
            'completed'        => ['label' => $statuses['completed'] ?? esc_html__('تکمیل شده', 'maneli-car-inquiry'), 'count' => $counts['completed'] ?? 0, 'class' => 'completed'],
            'rejected'         => ['label' => $statuses['rejected'] ?? esc_html__('رد شد', 'maneli-car-inquiry'), 'count' => $counts['rejected'] ?? 0, 'class' => 'rejected'],
        ];

        // Define icon and color for each stat
        $stat_configs = [
            'total' => ['icon' => 'la-dollar-sign', 'color' => 'primary', 'bg' => 'bg-primary-transparent'],
            'approved' => ['icon' => 'la-check-circle', 'color' => 'success', 'bg' => 'bg-success-transparent'],
            'completed' => ['icon' => 'la-check-double', 'color' => 'success', 'bg' => 'bg-success-transparent'],
            'rejected' => ['icon' => 'la-times-circle', 'color' => 'danger', 'bg' => 'bg-danger-transparent'],
        ];

        ob_start();
        ?>
        <div class="row mb-4">
            <?php foreach ($stats as $key => $stat) : 
                $config = $stat_configs[$key] ?? ['icon' => 'la-info-circle', 'color' => 'secondary', 'bg' => 'bg-secondary-transparent'];
            ?>
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                    <div class="card custom-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="flex-fill">
                                    <span class="d-block mb-1 text-muted fs-12"><?php echo esc_html($stat['label']); ?></span>
                                    <h4 class="mb-0 fw-semibold"><?php echo maneli_number_format_persian($stat['count']); ?></h4>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="avatar avatar-md avatar-rounded <?php echo esc_attr($config['bg']); ?>">
                                        <i class="la <?php echo esc_attr($config['icon']); ?> fs-20 text-<?php echo esc_attr($config['color']); ?>"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
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
    
    /**
     * رندر ویجت گزارشات پیشرفته
     */
    public static function render_advanced_reports_widget() {
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-reports-dashboard.php';
        
        // دریافت آمار 30 روز گذشته
        $stats = Maneli_Reports_Dashboard::get_overall_statistics();
        
        // دریافت کارشناس فعلی
        $current_user = wp_get_current_user();
        $is_expert = in_array('maneli_expert', $current_user->roles);
        
        if ($is_expert) {
            $stats = Maneli_Reports_Dashboard::get_overall_statistics(null, null, $current_user->ID);
        }
        
        ?>
        <div class="maneli-advanced-reports-widget">
            <style>
                .maneli-advanced-reports-widget {
                    padding: 10px 0;
                }
                .reports-stats-grid {
                    display: grid;
                    grid-template-columns: repeat(3, 1fr);
                    gap: 12px;
                    margin-bottom: 15px;
                }
                .report-stat-box {
                    background: #f8f9fa;
                    padding: 15px;
                    border-radius: 6px;
                    text-align: center;
                    border: 2px solid transparent;
                    transition: all 0.3s;
                }
                .report-stat-box:hover {
                    border-color: #2271b1;
                    transform: translateY(-2px);
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                }
                .report-stat-box .number {
                    display: block;
                    font-size: 28px;
                    font-weight: bold;
                    margin-bottom: 5px;
                }
                .report-stat-box .label {
                    display: block;
                    font-size: 12px;
                    color: #666;
                }
                .report-stat-box.total .number { color: #2271b1; }
                .report-stat-box.approved .number { color: #00a32a; }
                .report-stat-box.pending .number { color: #f0b849; }
                .report-stat-box.rejected .number { color: #d63638; }
                .report-stat-box.following .number { color: #8c6d1f; }
                .report-stat-box.revenue .number { color: #2271b1; }
                .reports-quick-links {
                    display: flex;
                    gap: 8px;
                    flex-wrap: wrap;
                    padding-top: 12px;
                    border-top: 1px solid #ddd;
                }
                .reports-quick-links a {
                    flex: 1;
                    min-width: 120px;
                    text-align: center;
                    padding: 8px 12px;
                    background: #2271b1;
                    color: #fff;
                    text-decoration: none;
                    border-radius: 4px;
                    font-size: 13px;
                    transition: all 0.3s;
                }
                .reports-quick-links a:hover {
                    background: #135e96;
                    transform: translateY(-2px);
                }
                .report-period-info {
                    text-align: center;
                    color: #666;
                    font-size: 12px;
                    margin-bottom: 12px;
                    padding: 8px;
                    background: #fff3cd;
                    border-radius: 4px;
                }
            </style>
            
            <div class="report-period-info">
                <strong>📅 بازه زمانی:</strong> 30 روز گذشته
                <?php if ($is_expert): ?>
                    <br><strong>👤 کارشناس:</strong> <?php echo esc_html($current_user->display_name); ?>
                <?php endif; ?>
            </div>
            
            <div class="reports-stats-grid">
                <div class="report-stat-box total">
                    <span class="number"><?php echo number_format($stats['total_inquiries']); ?></span>
                    <span class="label">کل استعلام‌ها</span>
                </div>
                
                <div class="report-stat-box approved">
                    <span class="number"><?php echo number_format($stats['approved']); ?></span>
                    <span class="label">تایید شده</span>
                </div>
                
                <div class="report-stat-box pending">
                    <span class="number"><?php echo number_format($stats['pending']); ?></span>
                    <span class="label">در انتظار</span>
                </div>
                
                <div class="report-stat-box rejected">
                    <span class="number"><?php echo number_format($stats['rejected']); ?></span>
                    <span class="label">رد شده</span>
                </div>
                
                <div class="report-stat-box following">
                    <span class="number"><?php echo number_format($stats['following']); ?></span>
                    <span class="label"><?php esc_html_e('Follow-up', 'maneli-car-inquiry'); ?></span>
                </div>
                
                <div class="report-stat-box revenue">
                    <span class="number"><?php echo number_format($stats['revenue']); ?></span>
                    <span class="label">درآمد (تومان)</span>
                </div>
            </div>
            
            <div class="reports-stats-grid" style="grid-template-columns: repeat(2, 1fr);">
                <div class="report-stat-box" style="background: #d4edda;">
                    <span class="number" style="color: #155724;"><?php echo number_format($stats['cash_inquiries']); ?></span>
                    <span class="label">استعلام نقدی</span>
                </div>
                
                <div class="report-stat-box" style="background: #ffeaa7;">
                    <span class="number" style="color: #6c5a11;"><?php echo number_format($stats['installment_inquiries']); ?></span>
                    <span class="label">استعلام اقساطی</span>
                </div>
            </div>
            
            <div class="reports-quick-links">
                <a href="<?php echo admin_url('edit.php?post_type=cash_inquiry&page=maneli-reports'); ?>">
                    📊 گزارشات کامل
                </a>
                <a href="<?php echo admin_url('edit.php?post_type=cash_inquiry'); ?>">
                    💰 استعلام‌های نقدی
                </a>
                <a href="<?php echo admin_url('edit.php?post_type=installment_inquiry'); ?>">
                    🧾 استعلام‌های اقساطی
                </a>
            </div>
        </div>
        <?php
    }
}