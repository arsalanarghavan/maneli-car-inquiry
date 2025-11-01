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

if ($is_admin && !$expert_id) {
    // آمار کامل کسب و کار (فقط برای ادمین)
    $business_stats = Maneli_Reports_Dashboard::get_business_statistics($start_date, $end_date);
    $stats = $business_stats['overall'];
    $experts_detailed = $business_stats['experts'];
    $popular_products = $business_stats['popular_products'];
    $daily_stats = $business_stats['daily'];
    $monthly_stats = $business_stats['monthly'];
    
    // آمارهای جدید
    $growth_stats = Maneli_Reports_Dashboard::get_growth_statistics($start_date, $end_date);
    $top_experts = Maneli_Reports_Dashboard::get_top_experts($start_date, $end_date, 5, 'profit');
    $vip_customers = Maneli_Reports_Dashboard::get_vip_customers($start_date, $end_date, 10);
    $status_distribution = Maneli_Reports_Dashboard::get_status_distribution($start_date, $end_date);
    $attention_required = Maneli_Reports_Dashboard::get_attention_required_inquiries();
} else {
    // آمار یک کارشناس
    $expert_detailed = Maneli_Reports_Dashboard::get_expert_detailed_statistics($expert_id, $start_date, $end_date);
    $stats = $expert_detailed['basic'];
    $profit = $expert_detailed['profit'];
    $success_rate = $expert_detailed['success_rate'];
    $daily_stats = $expert_detailed['daily'];
$popular_products = Maneli_Reports_Dashboard::get_popular_products($start_date, $end_date, $expert_id, 5);
    $growth_stats = Maneli_Reports_Dashboard::get_growth_statistics($start_date, $end_date, $expert_id);
    $status_distribution = Maneli_Reports_Dashboard::get_status_distribution($start_date, $end_date, $expert_id);
}

// Enqueue Chart.js
// Use local Chart.js if available
$chartjs_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/libs/chart.js/chart.umd.js';
if (file_exists($chartjs_path)) {
    wp_enqueue_script('chartjs', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/chart.js/chart.umd.js', [], '4.4.0', true);
} else {
    // Fallback to CDN
    wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', [], '4.4.0', true);
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
                                <input type="date" name="start_date" id="start-date-filter" class="form-control" value="<?php echo esc_attr($custom_start ?: $start_date); ?>">
                            </div>
                            <div class="col-md-2 maneli-initially-hidden" id="custom-date-end"<?php echo $period === 'custom' ? '' : ' style="display: none;"'; ?>>
                                <label class="form-label">تا تاریخ:</label>
                                <input type="date" name="end_date" id="end-date-filter" class="form-control" value="<?php echo esc_attr($custom_end ?: $end_date); ?>">
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

        <?php if (($is_admin || $is_manager) && !$expert_id && $business_stats): ?>
        <!-- ADMIN: Business Overview Widgets -->
        <!-- Start:: row-1 -->
        <div class="row mb-4">
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                <div class="card custom-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex gap-3 align-items-center">
                            <div class="avatar avatar-lg bg-primary svg-white">
                                <i class="la la-shopping-cart fs-24"></i>
                            </div>
                            <div>
                                <div class="flex-fill fs-13 text-muted">مجموع فروش</div>
                                <div class="fs-21 fw-medium"><?php echo maneli_number_format_persian($stats['total_inquiries']); ?></div>
                                <?php if (isset($growth_stats['total_inquiries_growth'])): ?>
                                    <span class="badge <?php echo esc_attr($growth_stats['total_inquiries_growth'] >= 0 ? 'bg-success-transparent text-success' : 'bg-danger-transparent text-danger'); ?> fs-10 mt-1">
                                        <i class="la la-arrow-<?php echo esc_attr($growth_stats['total_inquiries_growth'] >= 0 ? 'up' : 'down'); ?> fs-11"></i>
                                        <?php echo esc_html($growth_stats['total_inquiries_growth'] >= 0 ? '+' : ''); ?><?php echo esc_html(maneli_number_format_persian($growth_stats['total_inquiries_growth'], 1)); ?>%
                                </span>
                                <?php endif; ?>
                            </div>
                            <div class="ms-auto">
                                <span class="badge bg-success-transparent text-success fs-10">
                                    <i class="la la-calendar fs-11"></i><?php echo esc_html($period_label); ?>
                                </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                <div class="card custom-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex gap-3 align-items-center">
                            <div class="avatar avatar-lg bg-primary1 svg-white">
                                    <i class="la la-dollar-sign fs-24"></i>
                            </div>
                            <div>
                                <div class="flex-fill fs-13 text-muted">سود کل</div>
                                <div class="fs-21 fw-medium"><?php echo maneli_number_format_persian($business_stats && isset($business_stats['total_profit']) ? $business_stats['total_profit'] : 0); ?></div>
                                <?php if (isset($growth_stats['revenue_growth'])): ?>
                                    <span class="badge <?php echo esc_attr($growth_stats['revenue_growth'] >= 0 ? 'bg-success-transparent text-success' : 'bg-danger-transparent text-danger'); ?> fs-10 mt-1">
                                        <i class="la la-arrow-<?php echo esc_attr($growth_stats['revenue_growth'] >= 0 ? 'up' : 'down'); ?> fs-11"></i>
                                        <?php echo esc_html($growth_stats['revenue_growth'] >= 0 ? '+' : ''); ?><?php echo esc_html(maneli_number_format_persian($growth_stats['revenue_growth'], 1)); ?>%
                                </span>
                                <?php endif; ?>
                            </div>
                            <div class="ms-auto">
                                <span class="badge bg-success-transparent text-success fs-10">
                                    <i class="la la-dollar-sign fs-11"></i>سود
                                </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                <div class="card custom-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex gap-3 align-items-center">
                            <div class="avatar avatar-lg bg-primary2 svg-white">
                                <i class="la la-wallet fs-24"></i>
                            </div>
                            <div>
                                <div class="flex-fill fs-13 text-muted">مجموع درآمد</div>
                                <div class="fs-21 fw-medium"><?php echo maneli_number_format_persian($stats['revenue']); ?></div>
                                </div>
                            <div class="ms-auto">
                                <span class="badge bg-success-transparent text-success fs-10">
                                    <i class="la la-arrow-up fs-11"></i>درآمد
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                <div class="card custom-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex gap-3 align-items-center">
                            <div class="avatar avatar-lg bg-primary3 svg-white">
                                <i class="la la-users fs-24"></i>
                            </div>
                            <div>
                                <div class="flex-fill fs-13 text-muted"><?php esc_html_e('Total Customers', 'maneli-car-inquiry'); ?></div>
                                <div class="fs-21 fw-medium"><?php echo maneli_number_format_persian($business_stats && isset($business_stats['total_customers']) ? $business_stats['total_customers'] : 0); ?></div>
                                </div>
                            <div class="ms-auto">
                                <span class="badge bg-primary-transparent text-primary fs-10">
                                    <i class="la la-user fs-11"></i><?php esc_html_e('Customer', 'maneli-car-inquiry'); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End:: row-1 -->

        <!-- Start::row-2 -->
        <div class="row mb-4">
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                <div class="card custom-card border-primary border border-opacity-50 overflow-hidden main-content-card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="flex-grow-1">
                                <span class="text-secondary fw-semibold me-1 d-inline-block badge bg-secondary-transparent">
                                    <i class="la la-arrow-up"></i>+<?php echo maneli_number_format_persian($stats['total_inquiries'] > 0 ? ($stats['new_today'] / $stats['total_inquiries'] * 100) : 0, 1); ?>%
                                </span>
                                <h4 class="mt-2 mb-2 fw-medium"><?php echo maneli_number_format_persian($business_stats && isset($business_stats['total_experts']) ? $business_stats['total_experts'] : 0); ?></h4>
                                <p class="mb-0 fs-12 fw-medium"><?php esc_html_e('Total Experts', 'maneli-car-inquiry'); ?></p>
                            </div>
                            <div>
                                <span class="avatar avatar-md bg-primary svg-white text-fixed-white">
                                    <i class="la la-user-tie fs-20"></i>
                                </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                <div class="card custom-card border-primary1 border border-opacity-50 overflow-hidden main-content-card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="flex-grow-1">
                                <span class="text-secondary fw-semibold me-1 d-inline-block badge bg-secondary-transparent">
                                    <i class="la la-arrow-up"></i>+<?php echo maneli_number_format_persian($stats['total_inquiries'] > 0 ? ($stats['completed'] / $stats['total_inquiries'] * 100) : 0, 1); ?>%
                                </span>
                                <h4 class="mt-2 mb-2 fw-medium"><?php echo maneli_number_format_persian($stats['completed']); ?></h4>
                                <p class="mb-0 fs-12 fw-medium"><?php esc_html_e('Completed', 'maneli-car-inquiry'); ?></p>
                            </div>
                            <div>
                                <span class="avatar avatar-md bg-primary1 svg-white text-fixed-white">
                                    <i class="la la-check-circle fs-20"></i>
                                </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                <div class="card custom-card border-primary2 border border-opacity-50 overflow-hidden main-content-card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="flex-grow-1">
                                <span class="text-secondary fw-semibold me-1 d-inline-block badge bg-secondary-transparent">
                                    <i class="la la-arrow-up"></i>+<?php echo maneli_number_format_persian($stats['total_inquiries'] > 0 ? ($stats['revenue'] / $stats['total_inquiries']) : 0, 0); ?>
                                </span>
                                <h4 class="mt-2 mb-2 fw-medium"><?php echo maneli_number_format_persian($stats['revenue']); ?></h4>
                                <p class="mb-0 fs-12 fw-medium"><?php esc_html_e('Total Revenue', 'maneli-car-inquiry'); ?></p>
            </div>
                            <div>
                                <span class="avatar avatar-md bg-primary2 svg-white text-fixed-white">
                                    <i class="la la-money-bill-wave fs-20"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                <div class="card custom-card border-primary3 border border-opacity-50 overflow-hidden main-content-card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="flex-grow-1">
                                <span class="text-secondary fw-semibold me-1 d-inline-block badge bg-secondary-transparent">
                                    <i class="la la-arrow-up"></i>+<?php echo maneli_number_format_persian($stats['new_today']); ?>
                                </span>
                                <h4 class="mt-2 mb-2 fw-medium"><?php echo maneli_number_format_persian($stats['new_today']); ?></h4>
                                <p class="mb-0 fs-12 fw-medium"><?php esc_html_e('Today\'s Inquiries', 'maneli-car-inquiry'); ?></p>
                            </div>
                            <div>
                                <span class="avatar avatar-md bg-primary3 svg-white text-fixed-white">
                                    <i class="la la-calendar-day fs-20"></i>
                                </span>
                                </div>
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
                                        <th scope="col"><?php esc_html_e('Revenue (Toman)', 'maneli-car-inquiry'); ?></th>
                                        <th scope="col"><?php esc_html_e('Profit (Toman)', 'maneli-car-inquiry'); ?></th>
                                        <th scope="col"><?php esc_html_e('Success Rate', 'maneli-car-inquiry'); ?></th>
                                        <th scope="col"><?php esc_html_e('Customers', 'maneli-car-inquiry'); ?></th>
                                        <th scope="col"><?php esc_html_e('Actions', 'maneli-car-inquiry'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if (!empty($experts_detailed)) {
                                        usort($experts_detailed, function($a, $b) {
                                            return $b['profit'] <=> $a['profit'];
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
                                            <td><strong class="text-success"><?php echo maneli_number_format_persian($expert['revenue']); ?></strong></td>
                                            <td><strong class="text-primary"><?php echo maneli_number_format_persian($expert['profit']); ?></strong></td>
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
        <div class="row mb-4">
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                <div class="card custom-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex gap-3 align-items-center">
                            <div class="avatar avatar-lg bg-primary svg-white">
                                <i class="la la-list fs-24"></i>
                            </div>
                            <div>
                                <div class="flex-fill fs-13 text-muted"><?php esc_html_e('My Total Inquiries', 'maneli-car-inquiry'); ?></div>
                                <div class="fs-21 fw-medium"><?php echo maneli_number_format_persian($stats['total_inquiries']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                <div class="card custom-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex gap-3 align-items-center">
                            <div class="avatar avatar-lg bg-primary1 svg-white">
                                <i class="la la-dollar-sign fs-24"></i>
                            </div>
                            <div>
                                <div class="flex-fill fs-13 text-muted"><?php esc_html_e('My Profit', 'maneli-car-inquiry'); ?></div>
                                <div class="fs-21 fw-medium"><?php echo maneli_number_format_persian($profit); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                <div class="card custom-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex gap-3 align-items-center">
                            <div class="avatar avatar-lg bg-primary2 svg-white">
                                <i class="la la-percent fs-24"></i>
                            </div>
                            <div>
                                <div class="flex-fill fs-13 text-muted"><?php esc_html_e('Success Rate', 'maneli-car-inquiry'); ?></div>
                                <div class="fs-21 fw-medium"><?php echo maneli_number_format_persian($success_rate, 1); ?>%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                <div class="card custom-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex gap-3 align-items-center">
                            <div class="avatar avatar-lg bg-primary3 svg-white">
                                <i class="la la-users fs-24"></i>
                            </div>
                            <div>
                                <div class="flex-fill fs-13 text-muted"><?php esc_html_e('My Customers', 'maneli-car-inquiry'); ?></div>
                                <div class="fs-21 fw-medium"><?php echo isset($expert_detailed['total_customers']) ? maneli_number_format_persian($expert_detailed['total_customers']) : 0; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
                            <?php esc_html_e('Monthly Revenue (Last 6 Months)', 'maneli-car-inquiry'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="monthlyRevenueChart" height="300"></canvas>
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
                            <?php esc_html_e('Top Experts (Based on Profit)', 'maneli-car-inquiry'); ?>
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
                                                <?php echo esc_html($rank); ?>
                                            </span>
                                            <div>
                                                <h6 class="mb-0"><?php echo esc_html($expert['name']); ?></h6>
                                                <small class="text-muted"><?php printf(esc_html__('%s inquiries', 'maneli-car-inquiry'), maneli_number_format_persian($expert['total_inquiries'])); ?></small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <strong class="text-success"><?php echo maneli_number_format_persian($expert['profit']); ?></strong>
                                            <small class="d-block text-muted"><?php esc_html_e('Toman Profit', 'maneli-car-inquiry'); ?></small>
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
                                            <td><span class="badge bg-primary"><?php echo $rank++; ?></span></td>
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
                                    <?php printf(esc_html__('Overdue Inquiries (%d)', 'maneli-car-inquiry'), count($attention_required['overdue'])); ?>
                                </h6>
                                <div class="list-group">
                                    <?php foreach (array_slice($attention_required['overdue'], 0, 5) as $inq): ?>
                                        <a href="<?php echo esc_url($inq->post_type === 'cash_inquiry' ? add_query_arg('cash_inquiry_id', $inq->ID, home_url('/dashboard/inquiries/cash')) : add_query_arg('inquiry_id', $inq->ID, home_url('/dashboard/installment-inquiries'))); ?>" class="list-group-item list-group-item-action">
                                            <div class="d-flex justify-content-between">
                                                <span><?php printf(esc_html__('Inquiry #%d', 'maneli-car-inquiry'), $inq->ID); ?></span>
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
                                    <?php printf(esc_html__('Unassigned Inquiries (%d)', 'maneli-car-inquiry'), count($attention_required['unassigned'])); ?>
                                </h6>
                                <div class="list-group">
                                    <?php foreach (array_slice($attention_required['unassigned'], 0, 5) as $inq): ?>
                                        <a href="<?php echo esc_url($inq->post_type === 'cash_inquiry' ? add_query_arg('cash_inquiry_id', $inq->ID, home_url('/dashboard/inquiries/cash')) : add_query_arg('inquiry_id', $inq->ID, home_url('/dashboard/installment-inquiries'))); ?>" class="list-group-item list-group-item-action">
                                            <div class="d-flex justify-content-between">
                                                <span><?php printf(esc_html__('Inquiry #%d', 'maneli-car-inquiry'), $inq->ID); ?></span>
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
                                            <td><span class="badge bg-primary"><?php echo $rank++; ?></span></td>
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
// Wait for Chart.js to load
(function() {
    function initCharts() {
        if (typeof Chart === 'undefined') {
            console.log('Chart.js not loaded yet, retrying...');
            setTimeout(initCharts, 100);
            return;
        }
        
        jQuery(document).ready(function($) {
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
    
    // Translation object for charts
    const chartTexts = {
        totalInquiries: <?php echo json_encode(esc_html__('Total Inquiries', 'maneli-car-inquiry')); ?>,
        cash: <?php echo json_encode(esc_html__('Cash', 'maneli-car-inquiry')); ?>,
        installment: <?php echo json_encode(esc_html__('Installment', 'maneli-car-inquiry')); ?>,
        inquiryCount: <?php echo json_encode(esc_html__('Inquiry Count', 'maneli-car-inquiry')); ?>,
        profit: <?php echo json_encode(esc_html__('Profit (Toman)', 'maneli-car-inquiry')); ?>,
        revenue: <?php echo json_encode(esc_html__('Revenue (Toman)', 'maneli-car-inquiry')); ?>
    };
    
    // Daily Trend Chart
    <?php if (!empty($daily_stats)): 
        // Convert dates to Jalali in PHP
        $daily_stats_jalali = [];
        foreach ($daily_stats as $key => $stat) {
            $jalali_date = $stat['date'];
            if (function_exists('maneli_gregorian_to_jalali') && isset($stat['date']) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $stat['date'], $matches)) {
                $jalali_date = maneli_gregorian_to_jalali($matches[1], $matches[2], $matches[3], 'Y/m/d');
            }
            $daily_stats_jalali[] = [
                'date' => $jalali_date,
                'total' => isset($stat['total']) ? (int)$stat['total'] : 0,
                'cash' => isset($stat['cash']) ? (int)$stat['cash'] : 0,
                'installment' => isset($stat['installment']) ? (int)$stat['installment'] : 0,
            ];
        }
    ?>
    const ctx = document.getElementById('dailyTrendChart');
    if (ctx && typeof Chart !== 'undefined') {
        const dailyData = <?php echo json_encode($daily_stats_jalali); ?>;
        const labels = dailyData.map(item => item.date);
        const totalData = dailyData.map(item => parseInt(item.total) || 0);
        const cashData = dailyData.map(item => parseInt(item.cash) || 0);
        const installmentData = dailyData.map(item => parseInt(item.installment) || 0);
        
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
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>
    
    // Status Distribution Pie Chart
    <?php if (!empty($status_distribution)): ?>
    const pieCtx = document.getElementById('statusPieChart');
    if (pieCtx && typeof Chart !== 'undefined') {
        const statusData = <?php echo json_encode($status_distribution); ?>;
        const labels = statusData.map(item => item.status);
        const counts = statusData.map(item => parseInt(item.count) || 0);
        const colors = statusData.map(item => item.color);
        
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
                                const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
                                const percentStr = percent.toString().replace(/\d/g, d => persianDigits[d]);
                                label += context.parsed + ' (' + percentStr + '%)';
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>
    
    // Monthly Revenue Chart
    <?php if ($is_admin && !$expert_id && !empty($monthly_stats)): ?>
    const monthlyCtx = document.getElementById('monthlyRevenueChart');
    if (monthlyCtx && typeof Chart !== 'undefined') {
        const monthlyData = <?php echo json_encode($monthly_stats); ?>;
        const monthlyLabels = monthlyData.map(item => {
            // Convert month label to Persian/Jalali if needed
            return item.month_persian || item.month || '';
        });
        const monthlyTotals = monthlyData.map(item => parseInt(item.total) || 0);
        
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
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>
    
    // Experts Comparison Chart
    <?php if ($is_admin && !$expert_id && !empty($experts_detailed)): ?>
    const expertsCtx = document.getElementById('expertsComparisonChart');
    if (expertsCtx && typeof Chart !== 'undefined') {
        const expertsData = <?php echo json_encode(array_slice($experts_detailed, 0, 10)); ?>;
        const expertNames = expertsData.map(e => e.name || <?php echo json_encode(esc_html__('Unknown', 'maneli-car-inquiry')); ?>);
        const expertProfits = expertsData.map(e => parseFloat(e.profit) || 0);
        const expertRevenues = expertsData.map(e => parseFloat(e.revenue) || 0);
        const expertInquiries = expertsData.map(e => parseInt(e.total_inquiries) || 0);
        
        new Chart(expertsCtx, {
            type: 'bar',
            data: {
                labels: expertNames,
                datasets: [
                    {
                        label: chartTexts.profit,
                        data: expertProfits,
                        backgroundColor: 'rgba(34, 197, 94, 0.6)',
                        borderColor: 'rgb(34, 197, 94)',
                        borderWidth: 1
                    },
                    {
                        label: chartTexts.revenue,
                        data: expertRevenues,
                        backgroundColor: 'rgba(59, 130, 246, 0.6)',
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 1
                    },
                    {
                        label: chartTexts.inquiryCount,
                        data: expertInquiries,
                        backgroundColor: 'rgba(251, 191, 36, 0.6)',
                        borderColor: 'rgb(251, 191, 36)',
                        borderWidth: 1,
                        yAxisID: 'y1'
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
                        position: 'left',
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });
    }
    <?php endif; ?>
    
    // Start initialization
    initCharts();
});
})();

// Show/hide custom date fields
document.getElementById('period-filter')?.addEventListener('change', function() {
    const showCustom = this.value === 'custom';
    document.getElementById('custom-date-start').style.display = showCustom ? 'block' : 'none';
    document.getElementById('custom-date-end').style.display = showCustom ? 'block' : 'none';
});

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
