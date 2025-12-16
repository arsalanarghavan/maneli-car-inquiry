<?php
/**
 * Handles the logic and rendering for Admin Dashboard Widgets and Statistics.
 * این کلاس برای ارائه متدهای استاتیک نمایش ویجت‌های آماری به داشبورد وردپرس و شورت‌کدها استفاده می‌شود.
 *
 * @package Autopuzzle_Car_Inquiry/Includes
 * @author  AutoPuzzle
 * @version 1.0.2 (Implemented live statistics)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Autopuzzle_Admin_Dashboard_Widgets {

    /**
     * Constructor.
     */
    public function __construct() {
        // افزودن ویجت‌های داشبورد فقط برای نقش‌هایی که دسترسی دارند
        // اگرچه هیچ رولی به پیشخوان دسترسی ندارد، این ویجت‌ها برای admin/autopuzzle_admin نمایش داده می‌شوند.
        add_action('wp_dashboard_setup', array($this, 'register_dashboard_widgets'));
    }

    /**
     * Registers the custom dashboard widgets.
     */
    public function register_dashboard_widgets() {
        // از فرض وجود کلاس Autopuzzle_Permission_Helpers برای بررسی دسترسی استفاده شده است.
        if (!current_user_can('manage_autopuzzle_inquiries')) {
            return;
        }
        
        // ویجت‌های آماری استعلام قسطی
        wp_add_dashboard_widget(
            'autopuzzle_inquiry_stats',
            esc_html__('Installment Inquiry Statistics', 'autopuzzle'),
            array(__CLASS__, 'render_inquiry_statistics_widgets') 
        );

        // ویجت‌های آماری استعلام نقدی
        wp_add_dashboard_widget(
            'autopuzzle_cash_inquiry_stats',
            esc_html__('Cash Inquiry Statistics', 'autopuzzle'),
            array(__CLASS__, 'render_cash_inquiry_statistics_widgets')
        );

        // ویجت‌های آماری کاربران و محصولات
        wp_add_dashboard_widget(
            'autopuzzle_user_stats',
            esc_html__('User Statistics', 'autopuzzle'),
            array(__CLASS__, 'render_user_statistics_widgets')
        );
        
        wp_add_dashboard_widget(
            'autopuzzle_product_stats',
            esc_html__('Product Statistics', 'autopuzzle'),
            array(__CLASS__, 'render_product_statistics_widgets')
        );
        
        // ویجت گزارشات پیشرفته
        wp_add_dashboard_widget(
            'autopuzzle_advanced_reports',
            '📊 ' . esc_html__('Advanced Reports', 'autopuzzle'),
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

        // For installment inquiries, use tracking_status instead of inquiry_status
        $status_key = ($post_type === 'inquiry') ? 'tracking_status' : 'cash_inquiry_status';

        // Note: Using prepare for meta_key and post_type is generally not needed 
        // as they are known internal values, but is used here for completeness.
        // Use LEFT JOIN to include inquiries without status (they will default to 'new')
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT COALESCE(pm.meta_value, 'new') as status, COUNT(p.ID) as count
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s
            WHERE p.post_type = %s
            AND p.post_status = 'publish'
            GROUP BY status
        ", $status_key, $post_type), ARRAY_A);

        foreach ($results as $row) {
            $status = $row['status'] ?: 'new'; // Default to 'new' if empty
            $counts[$status] = (int)$row['count'];
            $counts['total'] += (int)$row['count'];
        }

        return $counts;
    }

    /**
     * Renders the statistics widgets for installment inquiries.
     * این متد هم برای ویجت داشبورد و هم برای فراخوانی استاتیک در شورت‌کدها استفاده می‌شود.
     */
    public static function render_inquiry_statistics_widgets() {
        if (!class_exists('Autopuzzle_CPT_Handler')) return '';

        $counts = self::get_inquiry_counts('inquiry');
        $statuses = Autopuzzle_CPT_Handler::get_tracking_statuses();
        
        // مهم‌ترین وضعیت‌ها برای نمایش (حداکثر 10 کارت: 1 کل + 9 وضعیت)
        $important_statuses = [
            'new',
            'referred',
            'in_progress',
            'awaiting_documents',
            'follow_up_scheduled',
            'meeting_scheduled',
            'approved',
            'completed',
            'rejected'
        ];
        
        // Build stats array with important statuses only
        $stats = [
            'total' => ['label' => esc_html__('Total Installment Requests', 'autopuzzle'), 'count' => $counts['total'], 'class' => 'total'],
        ];
        
        // Add only important tracking statuses
        foreach ($important_statuses as $status_key) {
            if (isset($statuses[$status_key])) {
                $stats[$status_key] = [
                    'label' => $statuses[$status_key],
                    'count' => $counts[$status_key] ?? 0,
                    'class' => $status_key
                ];
            }
        }

        // Define icon and color for each stat
        $stat_configs = [
            'total' => ['icon' => 'la-list-alt', 'color' => 'primary', 'bg' => 'bg-primary-transparent'],
            'new' => ['icon' => 'la-folder-open', 'color' => 'secondary', 'bg' => 'bg-secondary-transparent'],
            'referred' => ['icon' => 'la-share', 'color' => 'info', 'bg' => 'bg-info-transparent'],
            'in_progress' => ['icon' => 'la-spinner', 'color' => 'warning', 'bg' => 'bg-warning-transparent'],
            'follow_up_scheduled' => ['icon' => 'la-clock', 'color' => 'warning', 'bg' => 'bg-warning-transparent'],
            'meeting_scheduled' => ['icon' => 'la-calendar-check', 'color' => 'cyan', 'bg' => 'bg-cyan-transparent'],
            'awaiting_documents' => ['icon' => 'la-file-alt', 'color' => 'warning', 'bg' => 'bg-warning-transparent'],
            'approved' => ['icon' => 'la-check-circle', 'color' => 'success', 'bg' => 'bg-success-transparent'],
            'rejected' => ['icon' => 'la-times-circle', 'color' => 'danger', 'bg' => 'bg-danger-transparent'],
            'completed' => ['icon' => 'la-check-double', 'color' => 'success', 'bg' => 'bg-success-transparent'],
        ];

        ob_start();
        ?>
        <style>
        /* Inquiry Statistics Cards */
        .autopuzzle-inquiry-stats {
            display: flex !important;
            flex-wrap: wrap !important;
            margin-right: -0.5rem;
            margin-left: -0.5rem;
        }
        .autopuzzle-inquiry-stats__col {
            padding-right: 0.5rem;
            padding-left: 0.5rem;
            flex: 0 0 20% !important;
            max-width: 20% !important;
        }
        @media (max-width: 1399.98px) {
            .autopuzzle-inquiry-stats__col {
                flex: 0 0 25% !important;
                max-width: 25% !important;
            }
        }
        @media (max-width: 991.98px) {
            .autopuzzle-inquiry-stats__col {
                flex: 0 0 33.333333% !important;
                max-width: 33.333333% !important;
            }
        }
        @media (max-width: 767.98px) {
            .autopuzzle-inquiry-stats {
                flex-wrap: nowrap !important;
                overflow-x: auto;
                padding-bottom: 0.75rem;
                margin-right: 0;
                margin-left: 0;
                gap: 0.75rem;
                -webkit-overflow-scrolling: touch;
            }
            .autopuzzle-inquiry-stats__col {
                flex: 0 0 66% !important;
                max-width: 66% !important;
                padding-right: 0;
                padding-left: 0;
            }
            .autopuzzle-inquiry-stats::-webkit-scrollbar {
                display: none;
            }
        }
        .card.custom-card.crm-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            border: 1px solid rgba(0, 0, 0, 0.06) !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08) !important;
            position: relative !important;
            overflow: hidden !important;
            border-radius: 0.5rem !important;
            background: #fff !important;
        }
        [data-theme-mode=dark] .card.custom-card.crm-card {
            background: rgb(25, 25, 28) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3) !important;
            color: rgba(255, 255, 255, 0.9) !important;
        }
        .card.custom-card.crm-card::before {
            content: '' !important;
            position: absolute !important;
            top: 0 !important;
            right: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0) 100%) !important;
            pointer-events: none !important;
            transition: opacity 0.3s ease !important;
            opacity: 0 !important;
        }
        .card.custom-card.crm-card:hover {
            transform: translateY(-4px) !important;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12) !important;
            border-color: rgba(0, 0, 0, 0.1) !important;
        }
        .card.custom-card.crm-card:hover::before {
            opacity: 1 !important;
        }
        .card.custom-card.crm-card .card-body {
            position: relative !important;
            z-index: 1 !important;
            padding: 1.5rem !important;
        }
        .card.custom-card.crm-card:hover .p-2 {
            transform: scale(1.1) !important;
        }
        .card.custom-card.crm-card:hover .avatar {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
        }
        .card.custom-card.crm-card h4 {
            font-weight: 700 !important;
            letter-spacing: -0.5px !important;
            font-size: 1.75rem !important;
            color: #1f2937 !important;
            transition: color 0.3s ease !important;
        }
        [data-theme-mode=dark] .card.custom-card.crm-card h4 {
            color: rgba(255, 255, 255, 0.9) !important;
        }
        .card.custom-card.crm-card:hover h4 {
            color: #5e72e4 !important;
        }
        [data-theme-mode=dark] .card.custom-card.crm-card:hover h4 {
            color: var(--primary-color) !important;
        }
        [data-theme-mode=dark] .card.custom-card.crm-card .text-muted,
        [data-theme-mode=dark] .card.custom-card.crm-card p {
            color: rgba(255, 255, 255, 0.6) !important;
        }
        .card.custom-card.crm-card .border-primary, .card.custom-card.crm-card .bg-primary { background: linear-gradient(135deg, #5e72e4 0%, #7c3aed 100%) !important; }
        .card.custom-card.crm-card .border-success, .card.custom-card.crm-card .bg-success { background: linear-gradient(135deg, #2dce89 0%, #20c997 100%) !important; }
        .card.custom-card.crm-card .border-warning, .card.custom-card.crm-card .bg-warning { background: linear-gradient(135deg, #fb6340 0%, #fbb140 100%) !important; }
        .card.custom-card.crm-card .border-danger, .card.custom-card.crm-card .bg-danger { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important; }
        </style>
        <div class="row mb-4 autopuzzle-inquiry-stats">
            <?php foreach ($stats as $key => $stat) : 
                $config = $stat_configs[$key] ?? ['icon' => 'la-info-circle', 'color' => 'secondary', 'bg' => 'bg-secondary-transparent'];
            ?>
                <div class="autopuzzle-inquiry-stats__col col-xl-2 col-lg-4 col-md-4 col-sm-6 mb-3">
                    <div class="card custom-card crm-card overflow-hidden">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <div class="p-2 border border-<?php echo esc_attr($config['color']); ?> border-opacity-10 bg-<?php echo esc_attr($config['color']); ?>-transparent rounded-pill">
                                    <span class="avatar avatar-md avatar-rounded bg-<?php echo esc_attr($config['color']); ?> svg-white">
                                        <i class="la <?php echo esc_attr($config['icon']); ?> fs-20"></i>
                                    </span>
                                </div>
                            </div>
                            <p class="flex-fill text-muted fs-14 mb-1"><?php echo esc_html($stat['label']); ?></p>
                            <div class="d-flex align-items-center justify-content-between mt-1">
                                <h4 class="mb-0 d-flex align-items-center"><?php echo autopuzzle_number_format_persian($stat['count']); ?></h4>
                                <span class="badge bg-<?php echo esc_attr($config['color']); ?>-transparent rounded-pill fs-11"><?php echo esc_html($stat['label']); ?></span>
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
        if (!class_exists('Autopuzzle_CPT_Handler')) return '';

        $counts = self::get_inquiry_counts('cash_inquiry');
        $statuses = Autopuzzle_CPT_Handler::get_all_cash_inquiry_statuses();
        
        // مهم‌ترین وضعیت‌ها برای نمایش (حداکثر 10 کارت: 1 کل + 9 وضعیت)
        $important_statuses = [
            'new',
            'referred',
            'in_progress',
            'awaiting_downpayment',
            'downpayment_received',
            'awaiting_documents',
            'approved',
            'completed',
            'rejected'
        ];
        
        // Build stats array with important statuses only
        $stats = [
            'total' => ['label' => esc_html__('Total Cash Requests', 'autopuzzle'), 'count' => $counts['total'], 'class' => 'total'],
        ];
        
        // Add only important cash inquiry statuses
        foreach ($important_statuses as $status_key) {
            if (isset($statuses[$status_key])) {
                $stats[$status_key] = [
                    'label' => $statuses[$status_key],
                    'count' => $counts[$status_key] ?? 0,
                    'class' => $status_key
                ];
            }
        }

        // Define icon and color for each stat
        $stat_configs = [
            'total' => ['icon' => 'la-dollar-sign', 'color' => 'primary', 'bg' => 'bg-primary-transparent'],
            'new' => ['icon' => 'la-folder-open', 'color' => 'secondary', 'bg' => 'bg-secondary-transparent'],
            'referred' => ['icon' => 'la-share', 'color' => 'info', 'bg' => 'bg-info-transparent'],
            'in_progress' => ['icon' => 'la-spinner', 'color' => 'warning', 'bg' => 'bg-warning-transparent'],
            'follow_up_scheduled' => ['icon' => 'la-clock', 'color' => 'warning', 'bg' => 'bg-warning-transparent'],
            'meeting_scheduled' => ['icon' => 'la-calendar-check', 'color' => 'cyan', 'bg' => 'bg-cyan-transparent'],
            'awaiting_downpayment' => ['icon' => 'la-dollar-sign', 'color' => 'warning', 'bg' => 'bg-warning-transparent'],
            'downpayment_received' => ['icon' => 'la-check-double', 'color' => 'success', 'bg' => 'bg-success-transparent'],
            'awaiting_documents' => ['icon' => 'la-file-alt', 'color' => 'warning', 'bg' => 'bg-warning-transparent'],
            'approved' => ['icon' => 'la-check-circle', 'color' => 'success', 'bg' => 'bg-success-transparent'],
            'rejected' => ['icon' => 'la-times-circle', 'color' => 'danger', 'bg' => 'bg-danger-transparent'],
            'completed' => ['icon' => 'la-check-double', 'color' => 'success', 'bg' => 'bg-success-transparent'],
        ];

        ob_start();
        ?>
        <style>
        /* Cash Inquiry Statistics Cards */
        .autopuzzle-inquiry-stats {
            display: flex !important;
            flex-wrap: wrap !important;
            margin-right: -0.5rem;
            margin-left: -0.5rem;
        }
        .autopuzzle-inquiry-stats__col {
            padding-right: 0.5rem;
            padding-left: 0.5rem;
            flex: 0 0 20% !important;
            max-width: 20% !important;
        }
        @media (max-width: 1399.98px) {
            .autopuzzle-inquiry-stats__col {
                flex: 0 0 25% !important;
                max-width: 25% !important;
            }
        }
        @media (max-width: 991.98px) {
            .autopuzzle-inquiry-stats__col {
                flex: 0 0 33.333333% !important;
                max-width: 33.333333% !important;
            }
        }
        @media (max-width: 767.98px) {
            .autopuzzle-inquiry-stats {
                flex-wrap: nowrap !important;
                overflow-x: auto;
                padding-bottom: 0.75rem;
                margin-right: 0;
                margin-left: 0;
                gap: 0.75rem;
                -webkit-overflow-scrolling: touch;
            }
            .autopuzzle-inquiry-stats__col {
                flex: 0 0 66% !important;
                max-width: 66% !important;
                padding-right: 0;
                padding-left: 0;
            }
            .autopuzzle-inquiry-stats::-webkit-scrollbar {
                display: none;
            }
        }
        .card.custom-card.crm-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            border: 1px solid rgba(0, 0, 0, 0.06) !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08) !important;
            position: relative !important;
            overflow: hidden !important;
            border-radius: 0.5rem !important;
            background: #fff !important;
        }
        [data-theme-mode=dark] .card.custom-card.crm-card {
            background: rgb(25, 25, 28) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3) !important;
            color: rgba(255, 255, 255, 0.9) !important;
        }
        .card.custom-card.crm-card::before {
            content: '' !important;
            position: absolute !important;
            top: 0 !important;
            right: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0) 100%) !important;
            pointer-events: none !important;
            transition: opacity 0.3s ease !important;
            opacity: 0 !important;
        }
        .card.custom-card.crm-card:hover {
            transform: translateY(-4px) !important;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12) !important;
            border-color: rgba(0, 0, 0, 0.1) !important;
        }
        .card.custom-card.crm-card:hover::before {
            opacity: 1 !important;
        }
        .card.custom-card.crm-card .card-body {
            position: relative !important;
            z-index: 1 !important;
            padding: 1.5rem !important;
        }
        .card.custom-card.crm-card:hover .p-2 {
            transform: scale(1.1) !important;
        }
        .card.custom-card.crm-card:hover .avatar {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
        }
        .card.custom-card.crm-card h4 {
            font-weight: 700 !important;
            letter-spacing: -0.5px !important;
            font-size: 1.75rem !important;
            color: #1f2937 !important;
            transition: color 0.3s ease !important;
        }
        [data-theme-mode=dark] .card.custom-card.crm-card h4 {
            color: rgba(255, 255, 255, 0.9) !important;
        }
        .card.custom-card.crm-card:hover h4 {
            color: #5e72e4 !important;
        }
        [data-theme-mode=dark] .card.custom-card.crm-card:hover h4 {
            color: var(--primary-color) !important;
        }
        [data-theme-mode=dark] .card.custom-card.crm-card .text-muted,
        [data-theme-mode=dark] .card.custom-card.crm-card p {
            color: rgba(255, 255, 255, 0.6) !important;
        }
        .card.custom-card.crm-card .border-primary, .card.custom-card.crm-card .bg-primary { background: linear-gradient(135deg, #5e72e4 0%, #7c3aed 100%) !important; }
        .card.custom-card.crm-card .border-success, .card.custom-card.crm-card .bg-success { background: linear-gradient(135deg, #2dce89 0%, #20c997 100%) !important; }
        .card.custom-card.crm-card .border-warning, .card.custom-card.crm-card .bg-warning { background: linear-gradient(135deg, #fb6340 0%, #fbb140 100%) !important; }
        .card.custom-card.crm-card .border-danger, .card.custom-card.crm-card .bg-danger { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important; }
        </style>
        <div class="row mb-4 autopuzzle-inquiry-stats">
            <?php foreach ($stats as $key => $stat) :
                $config = $stat_configs[$key] ?? ['icon' => 'la-info-circle', 'color' => 'secondary', 'bg' => 'bg-secondary-transparent'];
            ?>
                <div class="autopuzzle-inquiry-stats__col col-xl-2 col-lg-4 col-md-4 col-sm-6 mb-3">
                    <div class="card custom-card crm-card overflow-hidden">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <div class="p-2 border border-<?php echo esc_attr($config['color']); ?> border-opacity-10 bg-<?php echo esc_attr($config['color']); ?>-transparent rounded-pill">
                                    <span class="avatar avatar-md avatar-rounded bg-<?php echo esc_attr($config['color']); ?> svg-white">
                                        <i class="la <?php echo esc_attr($config['icon']); ?> fs-20"></i>
                                    </span>
                                </div>
                            </div>
                            <p class="flex-fill text-muted fs-14 mb-1"><?php echo esc_html($stat['label']); ?></p>
                            <div class="d-flex align-items-center justify-content-between mt-1">
                                <h4 class="mb-0 d-flex align-items-center"><?php echo autopuzzle_number_format_persian($stat['count']); ?></h4>
                                <span class="badge bg-<?php echo esc_attr($config['color']); ?>-transparent rounded-pill fs-11"><?php echo esc_html($stat['label']); ?></span>
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
        $expert_count = $total_users['avail_roles']['autopuzzle_expert'] ?? 0;
        
        $stats = [
            'total_users' => ['label' => esc_html__('Total Users', 'autopuzzle'), 'count' => $total_users['total_users'], 'class' => 'total'],
            'experts'     => ['label' => esc_html__('AutoPuzzle Experts', 'autopuzzle'), 'count' => $expert_count, 'class' => 'expert'],
            'admins'      => ['label' => esc_html__('Managers', 'autopuzzle'), 'count' => $total_users['avail_roles']['autopuzzle_admin'] ?? 0, 'class' => 'admin'],
        ];
        
        ob_start();
        ?>
        <div class="autopuzzle-dashboard-stats-wrapper">
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
            'total'       => ['label' => esc_html__('Total Products', 'autopuzzle'), 'count' => $total_products, 'class' => 'total'],
            'active_sale' => ['label' => esc_html__('Active for Sale', 'autopuzzle'), 'count' => $active_products_count, 'class' => 'active'],
            'unavailable' => ['label' => esc_html__('Unavailable', 'autopuzzle'), 'count' => $unavailable_products_count, 'class' => 'unavailable'],
            'disabled'    => ['label' => esc_html__('Hidden Products', 'autopuzzle'), 'count' => $disabled_products_count, 'class' => 'disabled'],
        ];

        ob_start();
        ?>
        <div class="autopuzzle-dashboard-stats-wrapper">
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
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-reports-dashboard.php';
        
        // دریافت آمار 30 روز گذشته
        $stats = Autopuzzle_Reports_Dashboard::get_overall_statistics();
        
        // دریافت کارشناس فعلی
        $current_user = wp_get_current_user();
        $is_expert = in_array('autopuzzle_expert', $current_user->roles);
        
        if ($is_expert) {
            $stats = Autopuzzle_Reports_Dashboard::get_overall_statistics(null, null, $current_user->ID);
        }
        
        ?>
        <div class="autopuzzle-advanced-reports-widget">
            <style>
                .autopuzzle-advanced-reports-widget {
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
                    <span class="label"><?php esc_html_e('Follow-up', 'autopuzzle'); ?></span>
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
                <a href="<?php echo admin_url('edit.php?post_type=cash_inquiry&page=autopuzzle-reports'); ?>">
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