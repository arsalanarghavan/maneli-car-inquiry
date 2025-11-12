<!-- Start::row -->
<?php
/**
 * Reports Dashboard Page - Complete Business and Expert Reports
 * Shows comprehensive system statistics, expert performance, and business metrics
 * Admin: All reports
 * Expert: Only their own reports
 */

// CRITICAL: Always get fresh role from WordPress user object
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login'));
    exit;
}

$current_user = wp_get_current_user();
// Always check fresh roles from database
$is_admin = current_user_can('manage_maneli_inquiries');
$is_manager = in_array('maneli_manager', $current_user->roles, true) || in_array('maneli_admin', $current_user->roles, true);
$is_expert = in_array('maneli_expert', $current_user->roles, true);

// Only admin, manager, and expert can access reports
if (!$is_admin && !$is_manager && !$is_expert) {
    wp_redirect(home_url('/dashboard'));
    exit;
}

// Load reports class
require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-reports-dashboard.php';

// Get period from query parameter
$period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : 'monthly';
$custom_start = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : null;
$custom_end = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : null;

// Get period dates
$period_data = Maneli_Reports_Dashboard::get_period_dates($period, $custom_start, $custom_end);
$start_date = $period_data['start_date'];
$end_date = $period_data['end_date'];
$period_label = $period_data['label'];

// Additional filters
$filter_expert = isset($_GET['filter_expert']) ? intval($_GET['filter_expert']) : null;
$filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '';
$filter_type = isset($_GET['filter_type']) ? sanitize_text_field($_GET['filter_type']) : 'all'; // all, cash, installment
$filter_product = isset($_GET['filter_product']) ? intval($_GET['filter_product']) : null;

// Determine expert filter
$expert_id = null;

// Apply expert filter if selected and is admin/manager
if ($filter_expert && ($is_admin || $is_manager)) {
    $expert_id = $filter_expert;
}
// Otherwise, if user is expert (not admin/manager), use their ID
elseif ($is_expert && !$is_admin && !$is_manager) {
    $expert_id = $current_user->ID;
}

// Get statistics
$business_stats = null;
$experts_detailed = [];
$profit = 0;
$success_rate = 0;
$monthly_stats = [];
$growth_stats = null;
$top_experts = [];
$vip_customers = [];
$status_distribution = [];
$attention_required = null;

// Get statistics with all filters applied
$stats = Maneli_Reports_Dashboard::get_overall_statistics_with_filters(
    $start_date, 
    $end_date, 
    $expert_id, 
    $filter_status, 
    $filter_type, 
    $filter_product
);

if (($is_admin || $is_manager) && !$expert_id) {
    // آمار کامل کسب و کار (فقط برای ادمین و مدیر مانلی)
    // Note: For business stats, we use unfiltered stats to show overall system status
    $business_stats_unfiltered = Maneli_Reports_Dashboard::get_business_statistics($start_date, $end_date);
    $experts_detailed = $business_stats_unfiltered['experts'];
    $popular_products = Maneli_Reports_Dashboard::get_popular_products($start_date, $end_date, $expert_id, 5);
    $daily_stats = Maneli_Reports_Dashboard::get_daily_statistics($start_date, $end_date, $expert_id);
    $monthly_stats = $business_stats_unfiltered['monthly'];
    
    // آمارهای جدید (با فیلترها)
    $growth_stats = Maneli_Reports_Dashboard::get_growth_statistics($start_date, $end_date, $expert_id);
    $top_experts = Maneli_Reports_Dashboard::get_top_experts($start_date, $end_date, 5, 'completed');
    $vip_customers = Maneli_Reports_Dashboard::get_vip_customers($start_date, $end_date, 10);
    $status_distribution = Maneli_Reports_Dashboard::get_status_distribution($start_date, $end_date, $expert_id);
    $attention_required = Maneli_Reports_Dashboard::get_attention_required_inquiries();
} else {
    // آمار یک کارشناس
    $expert_detailed = Maneli_Reports_Dashboard::get_expert_detailed_statistics($expert_id, $start_date, $end_date);
    $profit = $expert_detailed['profit'];
    $success_rate = $expert_detailed['success_rate'];
    $daily_stats = Maneli_Reports_Dashboard::get_daily_statistics($start_date, $end_date, $expert_id);
    $popular_products = Maneli_Reports_Dashboard::get_popular_products($start_date, $end_date, $expert_id, 5);
    $growth_stats = Maneli_Reports_Dashboard::get_growth_statistics($start_date, $end_date, $expert_id);
    $status_distribution = Maneli_Reports_Dashboard::get_status_distribution($start_date, $end_date, $expert_id);
}

// Chart.js is enqueued in class-dashboard-handler.php for reports page
// Make sure it's loaded before our script
if (!wp_script_is('chartjs', 'enqueued')) {
    $chartjs_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/libs/chart.js/chart.umd.js';
    if (file_exists($chartjs_path)) {
        wp_enqueue_script('chartjs', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/chart.js/chart.umd.js', ['jquery'], '4.4.0', false);
    } else {
        // Fallback to CDN
        wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', ['jquery'], '4.4.0', false);
    }
}
?>
<div class="main-content app-content">
    <div class="container-fluid">

        <!-- Start::page-header -->
        <div class="d-flex align-items-center justify-content-between page-header-breadcrumb flex-wrap gap-2">
            <div>
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item">
                        <a href="<?php echo esc_url(home_url('/dashboard')); ?>"><?php esc_html_e('Dashboard', 'maneli-car-inquiry'); ?></a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page"><?php esc_html_e('Reports', 'maneli-car-inquiry'); ?></li>
                </ol>
                <h1 class="page-title fw-medium fs-18 mb-0">
                    <?php echo esc_html($is_expert && !$is_admin ? esc_html__('My Performance Report', 'maneli-car-inquiry') : esc_html__('Complete System Reports', 'maneli-car-inquiry')); ?>
                </h1>
            </div>
            <div class="btn-list">
                <button class="btn btn-sm btn-primary-light" onclick="window.print()">
                    <i class="la la-print me-1"></i><?php esc_html_e('Print', 'maneli-car-inquiry'); ?>
                </button>
                <?php if (($is_admin || $is_manager) && !$expert_id): ?>
                <button class="btn btn-sm btn-success-light" onclick="exportToPDF()">
                    <i class="la la-file-pdf me-1"></i><?php esc_html_e('Export PDF', 'maneli-car-inquiry'); ?>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <!-- End::page-header -->

        <!-- Advanced Filters -->
        <div class="row mb-4">
    <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header bg-light">
                <div class="card-title">
                            <i class="la la-filter me-2"></i><?php esc_html_e('Advanced Filters', 'maneli-car-inquiry'); ?>
                </div>
                        <button class="btn btn-sm btn-outline-secondary" onclick="resetFilters()">
                            <i class="la la-refresh me-1"></i><?php esc_html_e('Reset Filters', 'maneli-car-inquiry'); ?>
                        </button>
                    </div>
                    <div class="card-body">
                        <form id="reports-filter-form" method="get" class="row g-3">
                            <!-- Period Filter -->
                            <div class="col-md-2">
                                <label class="form-label"><?php esc_html_e('Time Period:', 'maneli-car-inquiry'); ?></label>
                                <select name="period" id="period-filter" class="form-select">
                                    <option value="today" <?php selected($period, 'today'); ?>><?php esc_html_e('Today', 'maneli-car-inquiry'); ?></option>
                                    <option value="yesterday" <?php selected($period, 'yesterday'); ?>><?php esc_html_e('Yesterday', 'maneli-car-inquiry'); ?></option>
                                    <option value="weekly" <?php selected($period, 'weekly'); ?>><?php esc_html_e('Last Week', 'maneli-car-inquiry'); ?></option>
                                    <option value="monthly" <?php selected($period, 'monthly'); ?>><?php esc_html_e('Last Month', 'maneli-car-inquiry'); ?></option>
                                    <option value="yearly" <?php selected($period, 'yearly'); ?>><?php esc_html_e('Last Year', 'maneli-car-inquiry'); ?></option>
                                    <option value="all" <?php selected($period, 'all'); ?>><?php esc_html_e('All', 'maneli-car-inquiry'); ?></option>
                                    <option value="custom" <?php selected($period, 'custom'); ?>><?php esc_html_e('Custom Range', 'maneli-car-inquiry'); ?></option>
                                </select>
                            </div>
                            
                            <!-- Custom Date Range (shown when custom is selected) -->
                            <div class="col-md-2 maneli-initially-hidden" id="custom-date-start" <?php echo $period === 'custom' ? '' : 'style="display: none;"'; ?>>
                                <label class="form-label">از تاریخ:</label>
                                <?php 
                                // Convert Gregorian to Jalali for display
                                $start_jalali = '';
                                if ($custom_start) {
                                    $start_parts = explode('-', $custom_start);
                                    if (count($start_parts) == 3) {
                                        $start_jalali = maneli_gregorian_to_jalali($start_parts[0], $start_parts[1], $start_parts[2], 'Y/m/d');
                                    }
                                } elseif ($start_date) {
                                    $start_parts = explode('-', $start_date);
                                    if (count($start_parts) == 3) {
                                        $start_jalali = maneli_gregorian_to_jalali($start_parts[0], $start_parts[1], $start_parts[2], 'Y/m/d');
                                    }
                                }
                                ?>
                                <input type="text" id="start-date-filter-jalali" class="form-control maneli-datepicker" placeholder="YYYY/MM/DD" value="<?php echo esc_attr($start_jalali); ?>" autocomplete="off">
                                <input type="hidden" name="start_date" id="start-date-filter" value="<?php echo esc_attr($custom_start ?: $start_date); ?>">
                            </div>
                            <div class="col-md-2 maneli-initially-hidden" id="custom-date-end"<?php echo $period === 'custom' ? '' : ' style="display: none;"'; ?>>
                                <label class="form-label">تا تاریخ:</label>
                                <?php 
                                // Convert Gregorian to Jalali for display
                                $end_jalali = '';
                                if ($custom_end) {
                                    $end_parts = explode('-', $custom_end);
                                    if (count($end_parts) == 3) {
                                        $end_jalali = maneli_gregorian_to_jalali($end_parts[0], $end_parts[1], $end_parts[2], 'Y/m/d');
                                    }
                                } elseif ($end_date) {
                                    $end_parts = explode('-', $end_date);
                                    if (count($end_parts) == 3) {
                                        $end_jalali = maneli_gregorian_to_jalali($end_parts[0], $end_parts[1], $end_parts[2], 'Y/m/d');
                                    }
                                }
                                ?>
                                <input type="text" id="end-date-filter-jalali" class="form-control maneli-datepicker" placeholder="YYYY/MM/DD" value="<?php echo esc_attr($end_jalali); ?>" autocomplete="off">
                                <input type="hidden" name="end_date" id="end-date-filter" value="<?php echo esc_attr($custom_end ?: $end_date); ?>">
                            </div>
                            
                            <?php if (($is_admin || $is_manager) && !$filter_expert): ?>
                            <!-- Expert Filter -->
                            <div class="col-md-2">
                                <label class="form-label"><?php esc_html_e('Expert:', 'maneli-car-inquiry'); ?></label>
                                <select name="filter_expert" id="expert-filter" class="form-select">
                                    <option value=""><?php esc_html_e('All Experts', 'maneli-car-inquiry'); ?></option>
                                    <?php
                                    $all_experts = get_users(['role__in' => ['maneli_expert', 'maneli_admin', 'administrator'], 'orderby' => 'display_name']);
                                    foreach ($all_experts as $expert):
                                    ?>
                                        <option value="<?php echo esc_attr($expert->ID); ?>" <?php selected($filter_expert, $expert->ID); ?>>
                                            <?php echo esc_html($expert->display_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                </div>
                    <?php endif; ?>
                            
                            <!-- Status Filter -->
                            <div class="col-md-2">
                                <label class="form-label">وضعیت:</label>
                                <select name="filter_status" id="status-filter" class="form-select">
                                    <option value="">همه وضعیت‌ها</option>
                                    <?php
                                    if (class_exists('Maneli_CPT_Handler')) {
                                        $cash_statuses = Maneli_CPT_Handler::get_all_cash_inquiry_statuses();
                                        foreach ($cash_statuses as $key => $label):
                                    ?>
                                        <option value="<?php echo esc_attr($key); ?>" <?php selected($filter_status, $key); ?>>
                                            <?php echo esc_html($label); ?>
                                        </option>
                                    <?php 
                                        endforeach;
                                    }
                                    ?>
                                </select>
                </div>
                            
                            <!-- Type Filter -->
                            <div class="col-md-2">
                                <label class="form-label"><?php esc_html_e('Type:', 'maneli-car-inquiry'); ?></label>
                                <select name="filter_type" id="type-filter" class="form-select">
                                    <option value="all" <?php selected($filter_type, 'all'); ?>><?php esc_html_e('All', 'maneli-car-inquiry'); ?></option>
                                    <option value="cash" <?php selected($filter_type, 'cash'); ?>><?php esc_html_e('Cash', 'maneli-car-inquiry'); ?></option>
                                    <option value="installment" <?php selected($filter_type, 'installment'); ?>><?php esc_html_e('Installment', 'maneli-car-inquiry'); ?></option>
                                </select>
            </div>
                            
                            <!-- Product Filter -->
                            <div class="col-md-2">
                                <label class="form-label">محصول:</label>
                                <select name="filter_product" id="product-filter" class="form-select">
                                    <option value="">همه محصولات</option>
                                    <?php
                                    $products = get_posts(['post_type' => 'product', 'posts_per_page' => 100, 'orderby' => 'title', 'order' => 'ASC']);
                                    foreach ($products as $product):
                                    ?>
                                        <option value="<?php echo esc_attr($product->ID); ?>" <?php selected($filter_product, $product->ID); ?>>
                                            <?php echo esc_html($product->post_title); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
        </div>

                            <!-- Apply Button -->
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="la la-filter me-1"></i>اعمال فیلترها
                                </button>
                                <span class="text-muted ms-3">
                                    بازه انتخاب شده: <strong><?php echo esc_html($period_label); ?></strong>
                                    (<?php 
                                    if (function_exists('maneli_gregorian_to_jalali')) {
                                        $start_jalali = maneli_gregorian_to_jalali(date('Y', strtotime($start_date)), date('m', strtotime($start_date)), date('d', strtotime($start_date)), 'Y/m/d');
                                        $end_jalali = maneli_gregorian_to_jalali(date('Y', strtotime($end_date)), date('m', strtotime($end_date)), date('d', strtotime($end_date)), 'Y/m/d');
                                        echo esc_html($start_jalali . ' تا ' . $end_jalali);
                                    } else {
                                        echo esc_html($start_date . ' تا ' . $end_date);
                                    }
                                    ?>)
                                </span>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php if (($is_admin || $is_manager) && !$expert_id): ?>
        <!-- ADMIN: Business Overview Widgets -->
        <style>
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
        .card.custom-card.crm-card:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
        }
        .card.custom-card.crm-card h4 {
            color: #1f2937 !important;
            transition: color 0.3s ease !important;
        }
        [data-theme-mode=dark] .card.custom-card.crm-card h4 {
            color: rgba(255, 255, 255, 0.9) !important;
        }
        .card.custom-card.crm-card:hover h4 {
            color: #5e72e4 !important;
        }
        </style>
        
        <!-- Start::row-1 - 8 Important Cards -->
        <div class="row mb-4">
            <!-- Card 1: Total Inquiries -->
            <div class="col-md-6 col-lg-4 col-xl">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-primary border-opacity-10 bg-primary-transparent rounded-pill">
                                <span class="avatar avatar-md avatar-rounded bg-primary svg-white">
                                    <i class="la la-list-alt fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1">مجموع استعلام‌ها</p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center"><?php echo maneli_number_format_persian($stats['total_inquiries']); ?></h4>
                            <?php if (isset($growth_stats['total_inquiries_growth'])): ?>
                            <span class="badge <?php echo esc_attr($growth_stats['total_inquiries_growth'] >= 0 ? 'bg-success-transparent text-success' : 'bg-danger-transparent text-danger'); ?> rounded-pill fs-11">
                                <i class="la la-arrow-<?php echo esc_attr($growth_stats['total_inquiries_growth'] >= 0 ? 'up' : 'down'); ?> fs-11"></i>
                                <?php echo esc_html($growth_stats['total_inquiries_growth'] >= 0 ? '+' : ''); ?><?php echo esc_html(maneli_number_format_persian($growth_stats['total_inquiries_growth'], 1)); ?>%
                            </span>
                            <?php else: ?>
                            <span class="badge bg-primary-transparent rounded-pill fs-11"><?php echo maneli_number_format_persian($stats['cash_inquiries']); ?> <?php esc_html_e('Cash', 'maneli-car-inquiry'); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Card 2: New Inquiries -->
            <div class="col-md-6 col-lg-4 col-xl">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-secondary border-opacity-10 bg-secondary-transparent rounded-circle">
                                <span class="avatar avatar-md avatar-rounded bg-secondary svg-white">
                                    <i class="la la-folder-open fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('New Inquiries', 'maneli-car-inquiry'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center"><?php echo maneli_number_format_persian($stats['new']); ?></h4>
                            <span class="badge bg-secondary-transparent text-secondary rounded-pill d-flex align-items-center fs-11">
                                <i class="la la-clock fs-11"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Card 3: Referred -->
            <div class="col-md-6 col-lg-4 col-xl">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-info border-opacity-10 bg-info-transparent rounded-circle">
                                <span class="avatar avatar-md avatar-rounded bg-info svg-white">
                                    <i class="la la-share fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Referred', 'maneli-car-inquiry'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center text-info"><?php echo maneli_number_format_persian($stats['referred']); ?></h4>
                            <span class="badge bg-info-transparent text-info rounded-pill d-flex align-items-center fs-11">
                                <i class="la la-user-tie fs-11"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Card 4: In Progress -->
            <div class="col-md-6 col-lg-4 col-xl">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-warning border-opacity-10 bg-warning-transparent rounded-circle">
                                <span class="avatar avatar-md avatar-rounded bg-warning svg-white">
                                    <i class="la la-spinner fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('In Progress', 'maneli-car-inquiry'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center text-warning"><?php echo maneli_number_format_persian($stats['in_progress']); ?></h4>
                            <span class="text-warning badge bg-warning-transparent rounded-pill d-flex align-items-center fs-11">
                                <i class="la la-hourglass-half fs-11"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End::row-1 -->

        <!-- Start::row-2 -->
        <div class="row mb-4">
            <!-- Card 5: Completed -->
            <div class="col-md-6 col-lg-4 col-xl">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-success border-opacity-10 bg-success-transparent rounded-circle">
                                <span class="avatar avatar-md avatar-rounded bg-success svg-white">
                                    <i class="la la-check-circle fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Completed', 'maneli-car-inquiry'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center text-success"><?php echo maneli_number_format_persian($stats['completed']); ?></h4>
                            <?php if (isset($growth_stats['completed_growth'])): ?>
                            <span class="badge <?php echo esc_attr($growth_stats['completed_growth'] >= 0 ? 'bg-success-transparent text-success' : 'bg-danger-transparent text-danger'); ?> rounded-pill fs-11">
                                <i class="la la-arrow-<?php echo esc_attr($growth_stats['completed_growth'] >= 0 ? 'up' : 'down'); ?> fs-11"></i>
                                <?php echo esc_html($growth_stats['completed_growth'] >= 0 ? '+' : ''); ?><?php echo esc_html(maneli_number_format_persian($growth_stats['completed_growth'], 1)); ?>%
                            </span>
                            <?php else: ?>
                            <span class="text-success badge bg-success-transparent rounded-pill d-flex align-items-center fs-11">
                                <i class="la la-check-double fs-11"></i>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Card 6: Rejected -->
            <div class="col-md-6 col-lg-4 col-xl">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-danger border-opacity-10 bg-danger-transparent rounded-circle">
                                <span class="avatar avatar-md avatar-rounded bg-danger svg-white">
                                    <i class="la la-times-circle fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Rejected', 'maneli-car-inquiry'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center text-danger"><?php echo maneli_number_format_persian($stats['rejected']); ?></h4>
                            <span class="text-danger badge bg-danger-transparent rounded-pill d-flex align-items-center fs-11">
                                <i class="la la-ban fs-11"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Card 7: Cash Inquiries -->
            <div class="col-md-6 col-lg-4 col-xl">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-primary1 border-opacity-10 bg-primary-transparent rounded-circle">
                                <span class="avatar avatar-md avatar-rounded bg-primary1 svg-white">
                                    <i class="la la-money-bill-wave fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Cash Inquiries', 'maneli-car-inquiry'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center"><?php echo maneli_number_format_persian($stats['cash_inquiries']); ?></h4>
                            <span class="badge bg-primary-transparent rounded-pill fs-11"><?php echo $stats['total_inquiries'] > 0 ? maneli_number_format_persian(round(($stats['cash_inquiries'] / $stats['total_inquiries']) * 100), 1) : '۰'; ?>%</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Card 8: Installment Inquiries -->
            <div class="col-md-6 col-lg-4 col-xl">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-info border-opacity-10 bg-info-transparent rounded-circle">
                                <span class="avatar avatar-md avatar-rounded bg-info svg-white">
                                    <i class="la la-credit-card fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Installment Inquiries', 'maneli-car-inquiry'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center text-info"><?php echo maneli_number_format_persian($stats['installment_inquiries']); ?></h4>
                            <span class="badge bg-info-transparent rounded-pill fs-11"><?php echo $stats['total_inquiries'] > 0 ? maneli_number_format_persian(round(($stats['installment_inquiries'] / $stats['total_inquiries']) * 100), 1) : '۰'; ?>%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End::row-2 -->
            
        <!-- Experts Performance Table -->
        <div class="row mb-4">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div class="card-title">
                            <i class="la la-users me-2"></i>
                            <?php esc_html_e('Expert Performance Report', 'maneli-car-inquiry'); ?>
                            </div>
                        <button class="btn btn-sm btn-success-light" onclick="exportExpertsCSV()">
                            <i class="la la-download me-1"></i><?php esc_html_e('Export CSV', 'maneli-car-inquiry'); ?>
                        </button>
                                </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table text-nowrap table-hover">
                                <thead>
                                    <tr>
                                        <th scope="col"><?php esc_html_e('Expert Name', 'maneli-car-inquiry'); ?></th>
                                        <th scope="col"><?php esc_html_e('Total Inquiries', 'maneli-car-inquiry'); ?></th>
                                        <th scope="col"><?php esc_html_e('Cash', 'maneli-car-inquiry'); ?></th>
                                        <th scope="col"><?php esc_html_e('Installment', 'maneli-car-inquiry'); ?></th>
                                        <th scope="col"><?php esc_html_e('Completed', 'maneli-car-inquiry'); ?></th>
                                        <th scope="col"><?php esc_html_e('Rejected', 'maneli-car-inquiry'); ?></th>
                                        <th scope="col"><?php esc_html_e('Pending', 'maneli-car-inquiry'); ?></th>
                                        <th scope="col"><?php esc_html_e('Success Rate', 'maneli-car-inquiry'); ?></th>
                                        <th scope="col"><?php esc_html_e('Customers', 'maneli-car-inquiry'); ?></th>
                                        <th scope="col"><?php esc_html_e('Actions', 'maneli-car-inquiry'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if (!empty($experts_detailed)) {
                                        usort($experts_detailed, function($a, $b) {
                                            return $b['completed'] <=> $a['completed'];
                                        });
                                    }
                                    foreach ($experts_detailed as $expert): 
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="fw-medium"><?php echo esc_html($expert['name']); ?></span>
                            </div>
                                            </td>
                                            <td><span class="badge bg-primary-transparent"><?php echo maneli_number_format_persian($expert['total_inquiries']); ?></span></td>
                                            <td><?php echo maneli_number_format_persian($expert['cash_inquiries']); ?></td>
                                            <td><?php echo maneli_number_format_persian($expert['installment_inquiries']); ?></td>
                                            <td><span class="badge bg-success-transparent"><?php echo maneli_number_format_persian($expert['completed']); ?></span></td>
                                            <td><span class="badge bg-danger-transparent"><?php echo maneli_number_format_persian($expert['rejected']); ?></span></td>
                                            <td><span class="badge bg-warning-transparent"><?php echo maneli_number_format_persian($expert['pending']); ?></span></td>
                                            <td>
                                                <div class="progress maneli-progress">
                                                    <?php $success_rate_val = floatval($expert['success_rate']); ?>
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo esc_attr($success_rate_val); ?>%">
                                                        <?php echo maneli_number_format_persian($success_rate_val, 1); ?>%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-info-transparent">
                                                    کل: <?php echo maneli_number_format_persian($expert['total_customers']); ?><br>
                                                    جدید: <?php echo maneli_number_format_persian($expert['new_customers']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?php echo esc_url(add_query_arg(['period' => $period, 'filter_expert' => $expert['id']], home_url('/dashboard/reports'))); ?>" 
                                                   class="btn btn-sm btn-primary-light btn-icon" title="<?php esc_attr_e('View Details', 'maneli-car-inquiry'); ?>">
                                                    <i class="la la-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- EXPERT: Personal Performance Widgets -->
        <style>
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
        .card.custom-card.crm-card:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
        }
        .card.custom-card.crm-card h4 {
            color: #1f2937 !important;
            transition: color 0.3s ease !important;
        }
        [data-theme-mode=dark] .card.custom-card.crm-card h4 {
            color: rgba(255, 255, 255, 0.9) !important;
        }
        .card.custom-card.crm-card:hover h4 {
            color: #5e72e4 !important;
        }
        </style>
        
        <!-- Start::row-1 - Expert Cards -->
        <div class="row mb-4">
            <!-- Card 1: Total Inquiries -->
            <div class="col-md-6 col-lg-4 col-xl">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-primary border-opacity-10 bg-primary-transparent rounded-pill">
                                <span class="avatar avatar-md avatar-rounded bg-primary svg-white">
                                    <i class="la la-list-alt fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('My Total Inquiries', 'maneli-car-inquiry'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center"><?php echo maneli_number_format_persian($stats['total_inquiries']); ?></h4>
                            <span class="badge bg-primary-transparent rounded-pill fs-11"><?php echo maneli_number_format_persian($stats['cash_inquiries']); ?> <?php esc_html_e('Cash', 'maneli-car-inquiry'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Card 2: New Inquiries -->
            <div class="col-md-6 col-lg-4 col-xl">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-secondary border-opacity-10 bg-secondary-transparent rounded-circle">
                                <span class="avatar avatar-md avatar-rounded bg-secondary svg-white">
                                    <i class="la la-folder-open fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('New Inquiries', 'maneli-car-inquiry'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center"><?php echo maneli_number_format_persian($stats['new']); ?></h4>
                            <span class="badge bg-secondary-transparent text-secondary rounded-pill d-flex align-items-center fs-11">
                                <i class="la la-clock fs-11"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Card 3: Referred -->
            <div class="col-md-6 col-lg-4 col-xl">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-info border-opacity-10 bg-info-transparent rounded-circle">
                                <span class="avatar avatar-md avatar-rounded bg-info svg-white">
                                    <i class="la la-share fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Referred', 'maneli-car-inquiry'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center text-info"><?php echo maneli_number_format_persian($stats['referred']); ?></h4>
                            <span class="badge bg-info-transparent text-info rounded-pill d-flex align-items-center fs-11">
                                <i class="la la-user-tie fs-11"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Card 4: In Progress -->
            <div class="col-md-6 col-lg-4 col-xl">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-warning border-opacity-10 bg-warning-transparent rounded-circle">
                                <span class="avatar avatar-md avatar-rounded bg-warning svg-white">
                                    <i class="la la-spinner fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('In Progress', 'maneli-car-inquiry'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center text-warning"><?php echo maneli_number_format_persian($stats['in_progress']); ?></h4>
                            <span class="text-warning badge bg-warning-transparent rounded-pill d-flex align-items-center fs-11">
                                <i class="la la-hourglass-half fs-11"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End::row-1 -->

        <!-- Start::row-2 -->
        <div class="row mb-4">
            <!-- Card 5: Completed -->
            <div class="col-md-6 col-lg-4 col-xl">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-success border-opacity-10 bg-success-transparent rounded-circle">
                                <span class="avatar avatar-md avatar-rounded bg-success svg-white">
                                    <i class="la la-check-circle fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Completed', 'maneli-car-inquiry'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center text-success"><?php echo maneli_number_format_persian($stats['completed']); ?></h4>
                            <?php if (isset($growth_stats['completed_growth'])): ?>
                            <span class="badge <?php echo esc_attr($growth_stats['completed_growth'] >= 0 ? 'bg-success-transparent text-success' : 'bg-danger-transparent text-danger'); ?> rounded-pill fs-11">
                                <i class="la la-arrow-<?php echo esc_attr($growth_stats['completed_growth'] >= 0 ? 'up' : 'down'); ?> fs-11"></i>
                                <?php echo esc_html($growth_stats['completed_growth'] >= 0 ? '+' : ''); ?><?php echo esc_html(maneli_number_format_persian($growth_stats['completed_growth'], 1)); ?>%
                            </span>
                            <?php else: ?>
                            <span class="text-success badge bg-success-transparent rounded-pill d-flex align-items-center fs-11">
                                <i class="la la-check-double fs-11"></i>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Card 6: Rejected -->
            <div class="col-md-6 col-lg-4 col-xl">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-danger border-opacity-10 bg-danger-transparent rounded-circle">
                                <span class="avatar avatar-md avatar-rounded bg-danger svg-white">
                                    <i class="la la-times-circle fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Rejected', 'maneli-car-inquiry'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center text-danger"><?php echo maneli_number_format_persian($stats['rejected']); ?></h4>
                            <span class="text-danger badge bg-danger-transparent rounded-pill d-flex align-items-center fs-11">
                                <i class="la la-ban fs-11"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Card 7: Cash Inquiries -->
            <div class="col-md-6 col-lg-4 col-xl">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-primary1 border-opacity-10 bg-primary-transparent rounded-circle">
                                <span class="avatar avatar-md avatar-rounded bg-primary1 svg-white">
                                    <i class="la la-money-bill-wave fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Cash Inquiries', 'maneli-car-inquiry'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center"><?php echo maneli_number_format_persian($stats['cash_inquiries']); ?></h4>
                            <span class="badge bg-primary-transparent rounded-pill fs-11"><?php echo $stats['total_inquiries'] > 0 ? maneli_number_format_persian(round(($stats['cash_inquiries'] / $stats['total_inquiries']) * 100), 1) : '۰'; ?>%</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Card 8: Installment Inquiries -->
            <div class="col-md-6 col-lg-4 col-xl">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-info border-opacity-10 bg-info-transparent rounded-circle">
                                <span class="avatar avatar-md avatar-rounded bg-info svg-white">
                                    <i class="la la-credit-card fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Installment Inquiries', 'maneli-car-inquiry'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center text-info"><?php echo maneli_number_format_persian($stats['installment_inquiries']); ?></h4>
                            <span class="badge bg-info-transparent rounded-pill fs-11"><?php echo $stats['total_inquiries'] > 0 ? maneli_number_format_persian(round(($stats['installment_inquiries'] / $stats['total_inquiries']) * 100), 1) : '۰'; ?>%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End::row-2 -->
        <?php endif; ?>

        <!-- Daily Trend Chart -->
        <?php if (!empty($daily_stats)): ?>
        <div class="row mb-4">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="la la-chart-line me-2"></i>
                            <?php esc_html_e('Daily Inquiry Trend', 'maneli-car-inquiry'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="dailyTrendChart" height="80"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Status Distribution Pie Chart -->
        <?php if (!empty($status_distribution)): ?>
        <div class="row mb-4">
            <div class="col-xl-6 col-lg-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="la la-pie-chart me-2"></i>
                            <?php esc_html_e('Inquiry Status Distribution', 'maneli-car-inquiry'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="statusPieChart" height="300"></canvas>
                    </div>
                </div>
            </div>
            
            <?php if (($is_admin || $is_manager) && !$expert_id && !empty($monthly_stats)): ?>
            <div class="col-xl-6 col-lg-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="la la-chart-bar me-2"></i>
                            <?php esc_html_e('Monthly Performance (Last 6 Months)', 'maneli-car-inquiry'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="monthlyPerformanceChart" height="300"></canvas>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Experts Comparison Chart -->
        <?php if (($is_admin || $is_manager) && !$expert_id && !empty($experts_detailed)): ?>
        <div class="row mb-4">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="la la-chart-bar me-2"></i>
                            <?php esc_html_e('Expert Performance Comparison', 'maneli-car-inquiry'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="expertsComparisonChart" height="80"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Top Experts & VIP Customers -->
        <?php if (($is_admin || $is_manager) && !$expert_id && isset($top_experts)): ?>
        <div class="row mb-4">
            <div class="col-xl-6 col-lg-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="la la-trophy me-2"></i>
                            <?php esc_html_e('Top Experts (Based on Activity)', 'maneli-car-inquiry'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php 
                            $rank = 1;
                            foreach ($top_experts as $expert): 
                            ?>
                                <li class="list-group-item">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="badge <?php echo esc_attr($rank == 1 ? 'bg-warning' : ($rank == 2 ? 'bg-secondary' : ($rank == 3 ? 'bg-info' : 'bg-primary-transparent'))); ?> fs-16 maneli-rank-badge">
                                                <?php echo maneli_number_format_persian($rank); ?>
                                            </span>
                                            <div>
                                                <h6 class="mb-0"><?php echo esc_html($expert['name']); ?></h6>
                                                <small class="text-muted">
                                                    <?php printf(esc_html__('%s completed', 'maneli-car-inquiry'), maneli_number_format_persian($expert['completed'])); ?> • 
                                                    <?php printf(esc_html__('%s total', 'maneli-car-inquiry'), maneli_number_format_persian($expert['total_inquiries'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <strong class="text-success"><?php echo maneli_number_format_persian($expert['completed']); ?></strong>
                                            <small class="d-block text-muted"><?php esc_html_e('Completed', 'maneli-car-inquiry'); ?></small>
                                            <?php if ($expert['total_inquiries'] > 0): ?>
                                                <small class="d-block text-muted mt-1">
                                                    <?php echo maneli_number_format_persian(round(($expert['completed'] / $expert['total_inquiries']) * 100, 1)); ?>% <?php esc_html_e('Success Rate', 'maneli-car-inquiry'); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </li>
                            <?php 
                                $rank++;
                            endforeach; 
                            ?>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-6 col-lg-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="la la-star me-2"></i>
                            <?php esc_html_e('VIP Customers', 'maneli-car-inquiry'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Rank', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('National ID', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Inquiry Count', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Total Amount', 'maneli-car-inquiry'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $rank = 1;
                                    foreach ($vip_customers as $customer): 
                                    ?>
                                        <tr>
                                            <td><span class="badge bg-primary"><?php echo maneli_number_format_persian($rank++); ?></span></td>
                                            <td><code><?php echo esc_html(substr($customer->national_id, 0, 3) . '***' . substr($customer->national_id, -2)); ?></code></td>
                                            <td><strong><?php echo maneli_number_format_persian($customer->inquiry_count); ?></strong></td>
                                            <td><span class="text-success"><?php echo maneli_number_format_persian($customer->total_amount); ?> <?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Attention Required -->
        <?php if (($is_admin || $is_manager) && !$expert_id && isset($attention_required)): ?>
        <div class="row mb-4">
            <div class="col-xl-12">
                <div class="card custom-card border-warning">
                    <div class="card-header bg-warning-transparent">
                        <div class="card-title">
                            <i class="la la-exclamation-triangle me-2"></i>
                            <?php esc_html_e('Attention Required', 'maneli-car-inquiry'); ?>
                        </div>
                    </div>
                    <div class="card-body">
        <div class="row">
                            <?php if (!empty($attention_required['overdue'])): ?>
                            <div class="col-md-6">
                                <h6 class="text-danger mb-3">
                                    <i class="la la-clock me-1"></i>
                                    <?php printf(esc_html__('Overdue Inquiries (%s)', 'maneli-car-inquiry'), maneli_number_format_persian(count($attention_required['overdue']))); ?>
                                </h6>
                                <div class="list-group">
                                    <?php foreach (array_slice($attention_required['overdue'], 0, 5) as $inq): ?>
                                        <a href="<?php echo esc_url($inq->post_type === 'cash_inquiry' ? add_query_arg('cash_inquiry_id', $inq->ID, home_url('/dashboard/inquiries/cash')) : add_query_arg('inquiry_id', $inq->ID, home_url('/dashboard/installment-inquiries'))); ?>" class="list-group-item list-group-item-action">
                                            <div class="d-flex justify-content-between">
                                                <span><?php printf(esc_html__('Inquiry #%s', 'maneli-car-inquiry'), maneli_number_format_persian($inq->ID)); ?></span>
                                                <span class="badge bg-danger"><?php esc_html_e('Overdue', 'maneli-car-inquiry'); ?></span>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($attention_required['unassigned'])): ?>
                            <div class="col-md-6">
                                <h6 class="text-warning mb-3">
                                    <i class="la la-user-times me-1"></i>
                                    <?php printf(esc_html__('Unassigned Inquiries (%s)', 'maneli-car-inquiry'), maneli_number_format_persian(count($attention_required['unassigned']))); ?>
                                </h6>
                                <div class="list-group">
                                    <?php foreach (array_slice($attention_required['unassigned'], 0, 5) as $inq): ?>
                                        <a href="<?php echo esc_url($inq->post_type === 'cash_inquiry' ? add_query_arg('cash_inquiry_id', $inq->ID, home_url('/dashboard/inquiries/cash')) : add_query_arg('inquiry_id', $inq->ID, home_url('/dashboard/installment-inquiries'))); ?>" class="list-group-item list-group-item-action">
                                            <div class="d-flex justify-content-between">
                                                <span><?php printf(esc_html__('Inquiry #%s', 'maneli-car-inquiry'), maneli_number_format_persian($inq->ID)); ?></span>
                                                <span class="badge bg-warning"><?php esc_html_e('Not Assigned', 'maneli-car-inquiry'); ?></span>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Popular Products -->
        <?php if (!empty($popular_products)): ?>
        <div class="row mb-4">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="la la-car me-2"></i>
                            <?php esc_html_e('Popular Products', 'maneli-car-inquiry'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Rank', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Product Name', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Inquiry Count', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Percentage', 'maneli-car-inquiry'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $rank = 1;
                                    foreach ($popular_products as $product): 
                                        $percentage = $stats['total_inquiries'] > 0 ? round(($product['count'] / $stats['total_inquiries']) * 100, 1) : 0;
                                    ?>
                                        <tr>
                                            <td><span class="badge bg-primary"><?php echo maneli_number_format_persian($rank++); ?></span></td>
                                            <td><?php echo esc_html($product['name']); ?></td>
                                            <td><strong><?php echo maneli_number_format_persian($product['count']); ?></strong></td>
                                            <td>
                                                <div class="progress maneli-progress">
                                                    <div class="progress-bar" role="progressbar" style="width: <?php echo esc_attr($percentage); ?>%">
                                                        <?php echo maneli_number_format_persian($percentage, 1); ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>
<!-- End::row -->

<script>
// Helper function to convert Gregorian date to Jalali
function convertToJalali(dateString) {
    try {
        if (!dateString) return '';
        const date = new Date(dateString + 'T00:00:00');
        const year = date.getFullYear();
        const month = date.getMonth() + 1;
        const day = date.getDate();
        
        // Convert to Jalali (simplified - using Persian Date library if available)
        if (typeof maneli_gregorian_to_jalali === 'function') {
            return maneli_gregorian_to_jalali(year, month, day, 'Y/m/d');
        }
        // Fallback: simple conversion
        return dateString;
    } catch (e) {
        console.error('Date conversion error:', e);
        return dateString;
    }
}

// Wait for both jQuery and Chart.js to load
function waitForDependencies() {
    if (typeof jQuery === 'undefined' || typeof Chart === 'undefined') {
        console.log('Waiting for dependencies... jQuery:', typeof jQuery !== 'undefined', 'Chart:', typeof Chart !== 'undefined');
        // Increase timeout limit and add max retries
        if (typeof waitForDependencies.retries === 'undefined') {
            waitForDependencies.retries = 0;
        }
        waitForDependencies.retries++;
        if (waitForDependencies.retries > 100) {
            console.error('Timeout waiting for Chart.js. Trying to load from CDN...');
            // Try to load Chart.js from CDN if local version failed
            if (typeof Chart === 'undefined') {
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';
                script.onload = function() {
                    console.log('Chart.js loaded from CDN');
                    initCharts();
                };
                script.onerror = function() {
                    console.error('Failed to load Chart.js from CDN');
                };
                document.head.appendChild(script);
            }
            return;
        }
        setTimeout(waitForDependencies, 100);
        return;
    }
    
    // Reset retries on success
    waitForDependencies.retries = 0;
    
    // Both are loaded, now initialize charts
    initCharts();
}

function initCharts() {
    if (typeof jQuery === 'undefined') {
        console.error('jQuery is not loaded!');
        return;
    }
    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded!');
        return;
    }
    
    jQuery(document).ready(function($) {
        console.log('Initializing reports charts...');
        console.log('Chart.js available:', typeof Chart !== 'undefined');
        
        const digitsHelper = window.maneliLocale || window.maneliDigits || {};

        const toPersianNumber = (num, options = {}) => {
            if (digitsHelper && typeof digitsHelper.formatNumber === 'function') {
                return digitsHelper.formatNumber(num, options);
            }
            if (digitsHelper && typeof digitsHelper.ensureDigits === 'function') {
                const target = options.forceLocale ? options.forceLocale : undefined;
                return digitsHelper.ensureDigits(num, target);
            }
            const persianDigitsFallback = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            return num.toString().replace(/\d/g, d => persianDigitsFallback[d]);
        };
        
        // Translation object for charts
        const chartTexts = {
            totalInquiries: <?php echo wp_json_encode(esc_html__('Total Inquiries', 'maneli-car-inquiry')); ?>,
            cash: <?php echo wp_json_encode(esc_html__('Cash', 'maneli-car-inquiry')); ?>,
            installment: <?php echo wp_json_encode(esc_html__('Installment', 'maneli-car-inquiry')); ?>,
            inquiryCount: <?php echo wp_json_encode(esc_html__('Inquiry Count', 'maneli-car-inquiry')); ?>,
            completed: <?php echo wp_json_encode(esc_html__('Completed', 'maneli-car-inquiry')); ?>
        };
        
        console.log('Chart initialization started. Chart.js loaded:', typeof Chart !== 'undefined');
        
        // Daily Trend Chart
        <?php if (!empty($daily_stats)): 
            // Convert dates to Jalali in PHP
            $daily_stats_jalali = [];
            foreach ($daily_stats as $key => $stat) {
                $jalali_date = $stat['date'];
                if (function_exists('maneli_gregorian_to_jalali') && isset($stat['date']) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $stat['date'], $matches)) {
                    $jalali_date = maneli_gregorian_to_jalali($matches[1], $matches[2], $matches[3], 'Y/m/d');
                }
                // Ensure all values are integers (convert from any format including Persian numbers)
                $daily_stats_jalali[] = [
                    'date' => $jalali_date,
                    'total' => isset($stat['total']) ? absint($stat['total']) : 0,
                    'cash' => isset($stat['cash']) ? absint($stat['cash']) : 0,
                    'installment' => isset($stat['installment']) ? absint($stat['installment']) : 0,
                ];
            }
        ?>
        const ctx = document.getElementById('dailyTrendChart');
        if (ctx && typeof Chart !== 'undefined') {
            try {
                const dailyData = <?php echo wp_json_encode($daily_stats_jalali); ?>;
                console.log('Daily stats data:', dailyData);
                if (!dailyData || dailyData.length === 0) {
                    console.warn('Daily stats is empty!');
                } else {
                    const labels = dailyData.map(item => String(item.date || ''));
                    const totalData = dailyData.map(item => {
                        const val = item.total || item['total'] || 0;
                        return typeof val === 'string' ? parseInt(val.replace(/[^0-9]/g, '')) || 0 : Number(val) || 0;
                    });
                    const cashData = dailyData.map(item => {
                        const val = item.cash || item['cash'] || 0;
                        return typeof val === 'string' ? parseInt(val.replace(/[^0-9]/g, '')) || 0 : Number(val) || 0;
                    });
                    const installmentData = dailyData.map(item => {
                        const val = item.installment || item['installment'] || 0;
                        return typeof val === 'string' ? parseInt(val.replace(/[^0-9]/g, '')) || 0 : Number(val) || 0;
                    });
                
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: chartTexts.totalInquiries,
                                    data: totalData,
                                    borderColor: 'rgb(75, 192, 192)',
                                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                                    tension: 0.4,
                                    fill: true
                                },
                                {
                                    label: chartTexts.cash,
                                    data: cashData,
                                    borderColor: 'rgb(255, 159, 64)',
                                    backgroundColor: 'rgba(255, 159, 64, 0.1)',
                                    tension: 0.4,
                                    fill: true
                                },
                                {
                                    label: chartTexts.installment,
                                    data: installmentData,
                                    borderColor: 'rgb(54, 162, 235)',
                                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                                    tension: 0.4,
                                    fill: true
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top',
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            let label = context.dataset.label || '';
                                            if (label) {
                                                label += ': ';
                                            }
                                            label += toPersianNumber(context.parsed.y);
                                            return label;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1,
                                        callback: function(value) {
                                            return toPersianNumber(value, { useGrouping: false });
                                        }
                                    }
                                }
                            }
                        }
                    });
                    console.log('Daily trend chart initialized successfully');
                }
            } catch (error) {
                console.error('Error initializing daily trend chart:', error);
            }
        } else {
            console.warn('Daily trend chart canvas not found or Chart.js not loaded');
        }
        <?php else: ?>
        console.log('Daily stats is empty, skipping daily trend chart');
        <?php endif; ?>
        
        // Status Distribution Pie Chart
        <?php if (!empty($status_distribution)): ?>
        const pieCtx = document.getElementById('statusPieChart');
        if (pieCtx && typeof Chart !== 'undefined') {
            try {
                const statusData = <?php echo wp_json_encode($status_distribution); ?>;
                console.log('Status distribution data:', statusData);
                if (!statusData || statusData.length === 0) {
                    console.warn('Status distribution is empty!');
                }
                const labels = statusData.map(item => String(item.status || ''));
                const counts = statusData.map(item => {
                    const val = item.count || item['count'] || 0;
                    return typeof val === 'string' ? parseInt(val.replace(/[^0-9]/g, '')) || 0 : Number(val) || 0;
                });
                const colors = statusData.map(item => String(item.color || '#cccccc'));
                
                new Chart(pieCtx, {
                    type: 'pie',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: counts,
                            backgroundColor: colors,
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                            },
                            tooltip: {
                                    callbacks: {
                                    label: function(context) {
                                        let label = context.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percent = total > 0 ? Math.round((context.parsed / total) * 100) : 0;
                                        // Convert to Persian digits
                                        const parsedStr = toPersianNumber(context.parsed);
                                        const percentStr = toPersianNumber(percent, { useGrouping: false });
                                        label += parsedStr + ' (' + percentStr + '%)';
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });
                console.log('Status pie chart initialized');
            } catch (error) {
                console.error('Error initializing status pie chart:', error);
            }
        }
        <?php endif; ?>
        
        // Monthly Performance Chart
        <?php if (($is_admin || $is_manager) && !$expert_id && !empty($monthly_stats)): ?>
        const monthlyCtx = document.getElementById('monthlyPerformanceChart');
        if (monthlyCtx && typeof Chart !== 'undefined') {
            try {
                const monthlyData = <?php echo wp_json_encode($monthly_stats); ?>;
                console.log('Monthly stats data:', monthlyData);
                if (!monthlyData || monthlyData.length === 0) {
                    console.warn('Monthly stats is empty!');
                }
                const monthlyLabels = monthlyData.map(item => String(item.month_persian || item.month || ''));
                const monthlyTotals = monthlyData.map(item => {
                    const val = item.total || item['total'] || 0;
                    return typeof val === 'string' ? parseInt(val.replace(/[^0-9]/g, '')) || 0 : Number(val) || 0;
                });
                
                new Chart(monthlyCtx, {
                    type: 'bar',
                    data: {
                        labels: monthlyLabels,
                        datasets: [{
                            label: chartTexts.inquiryCount,
                            data: monthlyTotals,
                            backgroundColor: 'rgba(59, 130, 246, 0.5)',
                            borderColor: 'rgb(59, 130, 246)',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1,
                                    callback: function(value) {
                                        return toPersianNumber(value, { useGrouping: false });
                                    }
                                }
                            }
                        },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        label += toPersianNumber(context.parsed.y);
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });
                console.log('Monthly performance chart initialized');
            } catch (error) {
                console.error('Error initializing monthly performance chart:', error);
            }
        }
        <?php endif; ?>
        
        // Experts Comparison Chart
        <?php if (($is_admin || $is_manager) && !$expert_id && !empty($experts_detailed)): ?>
        const expertsCtx = document.getElementById('expertsComparisonChart');
        if (expertsCtx && typeof Chart !== 'undefined') {
            try {
                const expertsData = <?php echo wp_json_encode(array_slice($experts_detailed, 0, 10)); ?>;
                console.log('Experts data:', expertsData);
                if (!expertsData || expertsData.length === 0) {
                    console.warn('Experts data is empty!');
                }
                const expertNames = expertsData.map(e => String(e.name || <?php echo wp_json_encode(esc_html__('Unknown', 'maneli-car-inquiry')); ?>));
                const expertCompleted = expertsData.map(e => {
                    const val = e.completed || e['completed'] || 0;
                    return typeof val === 'string' ? parseInt(val.replace(/[^0-9]/g, '')) || 0 : Number(val) || 0;
                });
                const expertInquiries = expertsData.map(e => {
                    const val = e.total_inquiries || e['total_inquiries'] || 0;
                    return typeof val === 'string' ? parseInt(val.replace(/[^0-9]/g, '')) || 0 : Number(val) || 0;
                });
                
                new Chart(expertsCtx, {
                    type: 'bar',
                    data: {
                        labels: expertNames,
                        datasets: [
                            {
                                label: chartTexts.completed,
                                data: expertCompleted,
                                backgroundColor: 'rgba(34, 197, 94, 0.6)',
                                borderColor: 'rgb(34, 197, 94)',
                                borderWidth: 1
                            },
                            {
                                label: chartTexts.inquiryCount,
                                data: expertInquiries,
                                backgroundColor: 'rgba(59, 130, 246, 0.6)',
                                borderColor: 'rgb(59, 130, 246)',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return toPersianNumber(value, { useGrouping: false });
                                    }
                                }
                            }
                        },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        label += toPersianNumber(context.parsed.y);
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });
                console.log('Experts comparison chart initialized');
            } catch (error) {
                console.error('Error initializing experts comparison chart:', error);
            }
        }
        <?php endif; ?>
        
        console.log('All charts initialization completed');
    });
}

// Start waiting for dependencies when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', waitForDependencies);
} else {
    waitForDependencies();
}

// Show/hide custom date fields
document.getElementById('period-filter')?.addEventListener('change', function() {
    const showCustom = this.value === 'custom';
    document.getElementById('custom-date-start').style.display = showCustom ? 'block' : 'none';
    document.getElementById('custom-date-end').style.display = showCustom ? 'block' : 'none';
});

// Initialize Persian Datepicker for custom date range fields
function initReportsDatepickers() {
    if (typeof jQuery === 'undefined' || typeof jQuery.fn.persianDatepicker === 'undefined') {
        console.log('Waiting for persianDatepicker...');
        setTimeout(initReportsDatepickers, 200);
        return;
    }
    
    jQuery(document).ready(function($) {
        // Helper function to convert YYYY/MM/DD to YYYY-MM-DD
        function formatGregorianDate(dateStr) {
            if (!dateStr) return '';
            const parts = dateStr.split('/');
            if (parts.length === 3) {
                const year = parts[0];
                const month = parts[1].length === 1 ? '0' + parts[1] : parts[1];
                const day = parts[2].length === 1 ? '0' + parts[2] : parts[2];
                return year + '-' + month + '-' + day;
            }
            return dateStr;
        }
        
        // Initialize start date picker
        const $startPicker = $('#start-date-filter-jalali');
        if ($startPicker.length) {
            if (!$startPicker.data('pdp-init')) {
                $startPicker.persianDatepicker({
                    formatDate: 'YYYY/MM/DD',
                    persianNumbers: true,
                    autoClose: true,
                    observer: false,
                    showGregorianDate: false,
                    onSelect: function() {
                        // Get Gregorian date from data attribute (set by persianDatepicker)
                        const gregorianDate = $startPicker.attr('data-gDate');
                        if (gregorianDate) {
                            const formattedDate = formatGregorianDate(gregorianDate);
                            $('#start-date-filter').val(formattedDate);
                        }
                    }
                });
                $startPicker.attr('data-pdp-init', 'true');
                console.log('Start date picker initialized');
            }
        }
        
        // Initialize end date picker
        const $endPicker = $('#end-date-filter-jalali');
        if ($endPicker.length) {
            if (!$endPicker.data('pdp-init')) {
                $endPicker.persianDatepicker({
                    formatDate: 'YYYY/MM/DD',
                    persianNumbers: true,
                    autoClose: true,
                    observer: false,
                    showGregorianDate: false,
                    onSelect: function() {
                        // Get Gregorian date from data attribute (set by persianDatepicker)
                        const gregorianDate = $endPicker.attr('data-gDate');
                        if (gregorianDate) {
                            const formattedDate = formatGregorianDate(gregorianDate);
                            $('#end-date-filter').val(formattedDate);
                        }
                    }
                });
                $endPicker.attr('data-pdp-init', 'true');
                console.log('End date picker initialized');
            }
        }
        
        // Re-initialize when custom date fields are shown
        $('#period-filter').on('change', function() {
            if (this.value === 'custom') {
                setTimeout(function() {
                    if ($startPicker.length && !$startPicker.data('pdp-init')) {
                        $startPicker.persianDatepicker({
                            formatDate: 'YYYY/MM/DD',
                            persianNumbers: true,
                            autoClose: true,
                            observer: false,
                            showGregorianDate: false,
                            onSelect: function() {
                                const gregorianDate = $startPicker.attr('data-gDate');
                                if (gregorianDate) {
                                    $('#start-date-filter').val(formatGregorianDate(gregorianDate));
                                }
                            }
                        });
                        $startPicker.attr('data-pdp-init', 'true');
                    }
                    if ($endPicker.length && !$endPicker.data('pdp-init')) {
                        $endPicker.persianDatepicker({
                            formatDate: 'YYYY/MM/DD',
                            persianNumbers: true,
                            autoClose: true,
                            observer: false,
                            showGregorianDate: false,
                            onSelect: function() {
                                const gregorianDate = $endPicker.attr('data-gDate');
                                if (gregorianDate) {
                                    $('#end-date-filter').val(formatGregorianDate(gregorianDate));
                                }
                            }
                        });
                        $endPicker.attr('data-pdp-init', 'true');
                    }
                }, 100);
            }
        });
    });
}

// Start initialization
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initReportsDatepickers);
} else {
    initReportsDatepickers();
}

function resetFilters() {
    window.location.href = '<?php echo remove_query_arg(['period', 'start_date', 'end_date', 'filter_expert', 'filter_status', 'filter_type', 'filter_product']); ?>';
}

function exportToPDF() {
    // Print page with better styling for PDF
    window.print();
    // Alternative: Use server-side PDF generation
    // window.location.href = '<?php echo admin_url('admin-ajax.php?action=maneli_export_reports_pdf'); ?>';
}

function exportExpertsCSV() {
    window.location.href = <?php echo wp_json_encode(admin_url('admin-ajax.php?action=maneli_export_reports_csv')); ?>;
}
</script>

<style>
.bg-primary1 {
    background-color: var(--primary-color-1, #8e44ad);
}

.bg-primary2 {
    background-color: var(--primary-color-2, #3498db);
}

.bg-primary3 {
    background-color: var(--primary-color-3, #2ecc71);
}

.text-primary3 {
    color: var(--primary-color-3, #2ecc71) !important;
}

.svg-white {
    color: white;
}

#dailyTrendChart,
#expertsComparisonChart,
#statusPieChart,
#monthlyRevenueChart {
    min-height: 300px;
}

.main-content-card {
    border-left: 3px solid;
}

/* Print Styles */
@media print {
    .btn-list,
    .breadcrumb,
    #reports-filter-form,
    .card-header .btn,
    .page-header-breadcrumb {
        display: none !important;
    }
    
    .card {
        page-break-inside: avoid;
        margin-bottom: 20px;
    }
    
    .row {
        page-break-inside: avoid;
    }
    
    .table {
        font-size: 10px;
    }
    
    .card-title {
        font-size: 14px;
        font-weight: bold;
    }
}
</style>
