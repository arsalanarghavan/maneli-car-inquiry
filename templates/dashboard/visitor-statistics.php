<?php
/**
 * Visitor Statistics Dashboard Page
 * صفحه کامل آمار بازدیدکنندگان - مشابه WP Statistics
 * 
 * @package Maneli_Car_Inquiry
 */

// CRITICAL: Always get fresh role from WordPress user object
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login'));
    exit;
}

$current_user = wp_get_current_user();
$is_admin = current_user_can('manage_maneli_inquiries');
$is_manager = in_array('maneli_manager', $current_user->roles, true) || in_array('maneli_admin', $current_user->roles, true);

// Only admin and manager can access visitor statistics
if (!$is_admin && !$is_manager) {
    wp_redirect(home_url('/dashboard'));
    exit;
}

// Load visitor statistics class
require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-visitor-statistics.php';

// Check if visitor statistics tracking is enabled
$options = get_option('maneli_inquiry_all_options', []);
$tracking_enabled = isset($options['enable_visitor_statistics']) && $options['enable_visitor_statistics'] == '1';

// Helper function to convert Jalali to Gregorian
if (!function_exists('maneli_jalali_to_gregorian')) {
    function maneli_jalali_to_gregorian($j_y, $j_m, $j_d) {
        $j_y = (int)$j_y;
        $j_m = (int)$j_m;
        $j_d = (int)$j_d;
        
        $jy = $j_y - 979;
        $jm = $j_m - 1;
        $jd = $j_d - 1;
        
        $j_days_in_month = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];
        
        $j_day_no = 365 * $jy + (int)($jy / 33) * 8 + (int)(($jy % 33 + 3) / 4);
        for ($i = 0; $i < $jm; ++$i) {
            $j_day_no += $j_days_in_month[$i];
        }
        $j_day_no += $jd;
        
        $g_day_no = $j_day_no + 79;
        
        $gy = 1600 + 400 * (int)($g_day_no / 146097);
        $g_day_no = $g_day_no % 146097;
        
        $leap = true;
        if ($g_day_no >= 36525) {
            $g_day_no--;
            $gy += 100 * (int)($g_day_no / 36524);
            $g_day_no = $g_day_no % 36524;
            
            if ($g_day_no >= 365) {
                $g_day_no++;
            } else {
                $leap = false;
            }
        }
        
        $gy += 4 * (int)($g_day_no / 1461);
        $g_day_no = $g_day_no % 1461;
        
        if ($g_day_no >= 366) {
            $leap = false;
            $g_day_no--;
            $gy += (int)($g_day_no / 365);
            $g_day_no = $g_day_no % 365;
        }
        
        $g_days_in_month = [31, ($leap ? 29 : 28), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        $gm = 0;
        while ($gm < 12 && $g_day_no >= $g_days_in_month[$gm]) {
            $g_day_no -= $g_days_in_month[$gm];
            $gm++;
        }
        
        return sprintf('%04d-%02d-%02d', $gy, $gm + 1, $g_day_no + 1);
    }
}

// Get current Jalali date for default values
$today_jalali = maneli_gregorian_to_jalali(date('Y'), date('m'), date('d'), 'Y/m/d');
$thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
$thirty_days_ago_jalali = maneli_gregorian_to_jalali(date('Y', strtotime('-30 days')), date('m', strtotime('-30 days')), date('d', strtotime('-30 days')), 'Y/m/d');

// Get date range from query parameters (default: last 30 days)
// If dates are in Jalali format (YYYY/MM/DD), convert to Gregorian for database
$start_date_input = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : $thirty_days_ago_jalali;
$end_date_input = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : $today_jalali;

// Convert Jalali to Gregorian for database queries
if (preg_match('/^(\d{4})\/(\d{2})\/(\d{2})$/', $start_date_input, $matches)) {
    $start_date = maneli_jalali_to_gregorian($matches[1], $matches[2], $matches[3]);
    $start_date_display = $start_date_input;
} else {
    // Assume it's already Gregorian
    $start_date = $start_date_input;
    $start_date_display = maneli_gregorian_to_jalali(date('Y', strtotime($start_date)), date('m', strtotime($start_date)), date('d', strtotime($start_date)), 'Y/m/d');
}

if (preg_match('/^(\d{4})\/(\d{2})\/(\d{2})$/', $end_date_input, $matches)) {
    $end_date = maneli_jalali_to_gregorian($matches[1], $matches[2], $matches[3]);
    $end_date_display = $end_date_input;
} else {
    // Assume it's already Gregorian
    $end_date = $end_date_input;
    $end_date_display = maneli_gregorian_to_jalali(date('Y', strtotime($end_date)), date('m', strtotime($end_date)), date('d', strtotime($end_date)), 'Y/m/d');
}

// Get overall statistics
$overall_stats = Maneli_Visitor_Statistics::get_overall_stats($start_date, $end_date);
$daily_stats = Maneli_Visitor_Statistics::get_daily_visits($start_date, $end_date);
$top_pages = Maneli_Visitor_Statistics::get_top_pages(10, $start_date, $end_date);
$top_products = Maneli_Visitor_Statistics::get_top_products(10, $start_date, $end_date);
$browser_stats = Maneli_Visitor_Statistics::get_browser_stats($start_date, $end_date);
$os_stats = Maneli_Visitor_Statistics::get_os_stats($start_date, $end_date);
$device_stats = Maneli_Visitor_Statistics::get_device_stats($start_date, $end_date);
$device_model_stats = Maneli_Visitor_Statistics::get_device_model_stats(10, $start_date, $end_date);
$country_stats = Maneli_Visitor_Statistics::get_country_stats($start_date, $end_date);
$search_engine_stats = Maneli_Visitor_Statistics::get_search_engine_stats($start_date, $end_date);
$referrer_stats = Maneli_Visitor_Statistics::get_referrer_stats(10, $start_date, $end_date);
$recent_visitors = Maneli_Visitor_Statistics::get_recent_visitors(50);
$online_visitors = Maneli_Visitor_Statistics::get_online_visitors();
$most_active_visitors = Maneli_Visitor_Statistics::get_most_active_visitors(10, $start_date, $end_date);

// Scripts and styles are enqueued in class-dashboard-handler.php
// Localize script (will be done in dashboard handler if needed)
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
                    <li class="breadcrumb-item active" aria-current="page"><?php esc_html_e('Visitor Statistics', 'maneli-car-inquiry'); ?></li>
                </ol>
                <h1 class="page-title fw-medium fs-18 mb-0">
                    <?php esc_html_e('Visitor Statistics', 'maneli-car-inquiry'); ?>
                </h1>
            </div>
            <div class="btn-list">
                <button class="btn btn-sm btn-primary-light" onclick="window.print()">
                    <i class="ri-printer-line me-1"></i><?php esc_html_e('Print', 'maneli-car-inquiry'); ?>
                </button>
                <button class="btn btn-sm btn-success-light" onclick="exportStatistics()">
                    <i class="ri-file-excel-line me-1"></i><?php esc_html_e('Export Excel', 'maneli-car-inquiry'); ?>
                </button>
            </div>
        </div>
        <!-- End::page-header -->

        <!-- Date Range Filter -->
        <div class="row mb-4">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header bg-light">
                        <div class="card-title">
                            <i class="ri-calendar-line me-2"></i><?php esc_html_e('Date Range Filter', 'maneli-car-inquiry'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <form id="visitor-stats-filter-form" method="get" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label"><?php esc_html_e('Period:', 'maneli-car-inquiry'); ?></label>
                                <select name="period" id="period-filter" class="form-select">
                                    <option value="today"><?php esc_html_e('Today', 'maneli-car-inquiry'); ?></option>
                                    <option value="yesterday"><?php esc_html_e('Yesterday', 'maneli-car-inquiry'); ?></option>
                                    <option value="week" <?php selected($start_date, date('Y-m-d', strtotime('-7 days'))); ?>><?php esc_html_e('Last Week', 'maneli-car-inquiry'); ?></option>
                                    <option value="month" <?php selected($start_date, date('Y-m-d', strtotime('-30 days'))); ?>><?php esc_html_e('Last Month', 'maneli-car-inquiry'); ?></option>
                                    <option value="3months"><?php esc_html_e('Last 3 Months', 'maneli-car-inquiry'); ?></option>
                                    <option value="6months"><?php esc_html_e('Last 6 Months', 'maneli-car-inquiry'); ?></option>
                                    <option value="year"><?php esc_html_e('Last Year', 'maneli-car-inquiry'); ?></option>
                                    <option value="custom"><?php esc_html_e('Custom Range', 'maneli-car-inquiry'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-3 maneli-initially-hidden" id="custom-start-date">
                                <label class="form-label"><?php esc_html_e('From Date (Solar):', 'maneli-car-inquiry'); ?></label>
                                <input type="text" name="start_date" id="start-date-picker" class="form-control" value="<?php echo esc_attr($start_date_display); ?>" placeholder="YYYY/MM/DD">
                            </div>
                            <div class="col-md-3 maneli-initially-hidden" id="custom-end-date">
                                <label class="form-label"><?php esc_html_e('To Date (Solar):', 'maneli-car-inquiry'); ?></label>
                                <input type="text" name="end_date" id="end-date-picker" class="form-control" value="<?php echo esc_attr($end_date_display); ?>" placeholder="YYYY/MM/DD">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary btn-wave w-100">
                                    <i class="ri-filter-line me-1"></i><?php esc_html_e('Apply Filter', 'maneli-car-inquiry'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!$tracking_enabled): ?>
        <!-- Warning: Tracking Not Enabled -->
        <div class="row mb-4">
            <div class="col-xl-12">
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="ri-alert-line me-2"></i>
                    <strong><?php esc_html_e('Visitor Statistics Tracking is Disabled', 'maneli-car-inquiry'); ?></strong>
                    <p class="mb-0 mt-2">
                        <?php esc_html_e('Visitor statistics tracking is currently disabled. Please enable it in', 'maneli-car-inquiry'); ?>
                        <a href="<?php echo esc_url(home_url('/dashboard/settings?tab=visitor_statistics')); ?>" class="alert-link">
                            <?php esc_html_e('Settings > Visitor Statistics', 'maneli-car-inquiry'); ?>
                        </a>
                        <?php esc_html_e('to start collecting visitor data.', 'maneli-car-inquiry'); ?>
                    </p>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>
        <?php elseif ($overall_stats['total_visits'] == 0): ?>
        <!-- Info: No Data Yet -->
        <div class="row mb-4">
            <div class="col-xl-12">
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="ri-information-line me-2"></i>
                    <strong><?php esc_html_e('No Visitor Data Yet', 'maneli-car-inquiry'); ?></strong>
                    <p class="mb-0 mt-2">
                        <?php esc_html_e('No visitor data has been collected yet for the selected date range. Statistics will appear here once visitors start browsing your site.', 'maneli-car-inquiry'); ?>
                    </p>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Overall Statistics Cards -->
        <div class="row">
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card custom-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex gap-3 align-items-center">
                            <div class="avatar avatar-lg bg-primary svg-white">
                                <i class="ri-eye-line fs-32"></i>
                            </div>
                            <div>
                                <div class="flex-fill fs-13 text-muted"><?php esc_html_e('Total Visits', 'maneli-car-inquiry'); ?></div>
                                <div class="fs-21 fw-medium" id="total-visits"><?php echo maneli_number_format_persian($overall_stats['total_visits']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card custom-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex gap-3 align-items-center">
                            <div class="avatar avatar-lg bg-primary1 svg-white">
                                <i class="ri-user-line fs-32"></i>
                            </div>
                            <div>
                                <div class="flex-fill fs-13 text-muted"><?php esc_html_e('Unique Visitors', 'maneli-car-inquiry'); ?></div>
                                <div class="fs-21 fw-medium" id="unique-visitors"><?php echo maneli_number_format_persian($overall_stats['unique_visitors']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card custom-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex gap-3 align-items-center">
                            <div class="avatar avatar-lg bg-primary2 svg-white">
                                <i class="ri-file-line fs-32"></i>
                            </div>
                            <div>
                                <div class="flex-fill fs-13 text-muted"><?php esc_html_e('Total Pages', 'maneli-car-inquiry'); ?></div>
                                <div class="fs-21 fw-medium" id="total-pages"><?php echo maneli_number_format_persian($overall_stats['total_pages']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card custom-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex gap-3 align-items-center">
                            <div class="avatar avatar-lg bg-primary3 svg-white">
                                <i class="ri-user-star-line fs-32"></i>
                            </div>
                            <div>
                                <div class="flex-fill fs-13 text-muted"><?php esc_html_e('Online Now', 'maneli-car-inquiry'); ?></div>
                                <div class="fs-21 fw-medium" id="online-visitors"><?php echo maneli_number_format_persian(count($online_visitors)); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Traffic Trend Chart -->
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card overflow-hidden">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ri-line-chart-line me-2"></i><?php esc_html_e('Traffic Trend', 'maneli-car-inquiry'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="traffic-trend-chart" style="min-height: 350px;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daily Visits and Top Pages -->
        <div class="row">
            <div class="col-xl-8">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ri-calendar-check-line me-2"></i><?php esc_html_e('Daily Visits', 'maneli-car-inquiry'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover text-nowrap">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Date', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Visits', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Unique Visitors', 'maneli-car-inquiry'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="daily-visits-table">
                                    <?php foreach (array_slice($daily_stats, -10) as $stat): 
                                        // Convert date to Jalali if it's in Gregorian format
                                        $display_date = $stat->date;
                                        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $stat->date)) {
                                            $date_parts = explode('-', $stat->date);
                                            $display_date = maneli_gregorian_to_jalali($date_parts[0], $date_parts[1], $date_parts[2], 'Y/m/d');
                                        }
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html($display_date); ?></td>
                                        <td><?php echo maneli_number_format_persian($stat->visits); ?></td>
                                        <td><?php echo maneli_number_format_persian($stat->unique_visitors); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ri-file-list-line me-2"></i><?php esc_html_e('Top Pages', 'maneli-car-inquiry'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover text-nowrap">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Page', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Visits', 'maneli-car-inquiry'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_pages as $page): ?>
                                    <tr>
                                        <td>
                                            <div class="text-truncate" style="max-width: 200px;" title="<?php echo esc_attr($page->page_url); ?>">
                                                <?php echo esc_html($page->page_title ?: $page->page_url); ?>
                                            </div>
                                        </td>
                                        <td><?php echo maneli_number_format_persian($page->visit_count); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Browsers and Operating Systems -->
        <div class="row">
            <div class="col-xl-6">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ri-global-line me-2"></i><?php esc_html_e('Browsers', 'maneli-car-inquiry'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="browsers-chart" style="min-height: 300px;"></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ri-computer-line me-2"></i><?php esc_html_e('Operating Systems', 'maneli-car-inquiry'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="os-chart" style="min-height: 300px;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Device Types and Top Products -->
        <div class="row">
            <div class="col-xl-6">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ri-smartphone-line me-2"></i><?php esc_html_e('Device Types', 'maneli-car-inquiry'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="device-types-chart" style="min-height: 300px;"></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ri-car-line me-2"></i><?php esc_html_e('Most Viewed Cars', 'maneli-car-inquiry'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover text-nowrap">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Product', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Visits', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Unique', 'maneli-car-inquiry'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_products as $product): ?>
                                    <tr>
                                        <td><?php echo esc_html($product->product_name ?: 'Product #' . $product->product_id); ?></td>
                                        <td><?php echo maneli_number_format_persian($product->visit_count); ?></td>
                                        <td><?php echo maneli_number_format_persian($product->unique_visitors); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Engines and Referrers -->
        <div class="row">
            <div class="col-xl-6">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ri-search-line me-2"></i><?php esc_html_e('Search Engines', 'maneli-car-inquiry'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover text-nowrap">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Search Engine', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Visits', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Unique', 'maneli-car-inquiry'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($search_engine_stats as $engine): ?>
                                    <tr>
                                        <td><?php echo esc_html(ucfirst($engine->search_engine)); ?></td>
                                        <td><?php echo maneli_number_format_persian($engine->visit_count); ?></td>
                                        <td><?php echo maneli_number_format_persian($engine->unique_visitors); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ri-links-line me-2"></i><?php esc_html_e('Top Referrers', 'maneli-car-inquiry'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover text-nowrap">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Referrer', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Visits', 'maneli-car-inquiry'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($referrer_stats as $referrer): ?>
                                    <tr>
                                        <td>
                                            <div class="text-truncate" style="max-width: 200px;" title="<?php echo esc_attr($referrer->referrer_domain); ?>">
                                                <?php echo esc_html($referrer->referrer_domain); ?>
                                            </div>
                                        </td>
                                        <td><?php echo maneli_number_format_persian($referrer->visit_count); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Device Models and Countries -->
        <div class="row">
            <div class="col-xl-6">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ri-device-line me-2"></i><?php esc_html_e('Device Models', 'maneli-car-inquiry'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover text-nowrap">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Device Model', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Visits', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Unique', 'maneli-car-inquiry'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($device_model_stats as $model): ?>
                                    <tr>
                                        <td><?php echo esc_html($model->device_model); ?></td>
                                        <td><?php echo maneli_number_format_persian($model->visit_count); ?></td>
                                        <td><?php echo maneli_number_format_persian($model->unique_visitors); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ri-global-line me-2"></i><?php esc_html_e('Countries', 'maneli-car-inquiry'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover text-nowrap">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Country', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Visits', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Unique', 'maneli-car-inquiry'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($country_stats, 0, 10) as $country): ?>
                                    <tr>
                                        <td><?php echo esc_html($country->country ?: 'Unknown'); ?></td>
                                        <td><?php echo maneli_number_format_persian($country->visit_count); ?></td>
                                        <td><?php echo maneli_number_format_persian($country->unique_visitors); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Most Active Visitors and Recent Visitors -->
        <div class="row">
            <div class="col-xl-6">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ri-user-star-line me-2"></i><?php esc_html_e('Most Active Visitors', 'maneli-car-inquiry'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover text-nowrap">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('IP Address', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Country', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Visits', 'maneli-car-inquiry'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($most_active_visitors as $visitor): ?>
                                    <tr>
                                        <td><?php echo esc_html($visitor->ip_address); ?></td>
                                        <td><?php echo esc_html($visitor->country ?: 'Unknown'); ?></td>
                                        <td><?php echo maneli_number_format_persian($visitor->visit_count); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ri-time-line me-2"></i><?php esc_html_e('Recent Visitors', 'maneli-car-inquiry'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover text-nowrap">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Time', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Page', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Country', 'maneli-car-inquiry'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($recent_visitors, 0, 10) as $visit): 
                                        $visit_timestamp = strtotime($visit->visit_date);
                                        $visit_date_jalali = maneli_gregorian_to_jalali(date('Y', $visit_timestamp), date('m', $visit_timestamp), date('d', $visit_timestamp), 'Y/m/d');
                                        $visit_time = date('H:i', $visit_timestamp);
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html($visit_date_jalali . ' ' . $visit_time); ?></td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 200px;" title="<?php echo esc_attr($visit->page_url); ?>">
                                                <?php echo esc_html($visit->page_title ?: $visit->page_url); ?>
                                            </div>
                                        </td>
                                        <td><?php echo esc_html($visit->country ?: 'Unknown'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Online Visitors -->
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ri-user-heart-line me-2"></i><?php esc_html_e('Online Visitors (Last 15 Minutes)', 'maneli-car-inquiry'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover text-nowrap">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('IP Address', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Country', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Browser', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('OS', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Device', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Current Page', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Last Activity', 'maneli-car-inquiry'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="online-visitors-table">
                                    <?php foreach ($online_visitors as $visitor): ?>
                                    <tr>
                                        <td><?php echo esc_html($visitor->ip_address); ?></td>
                                        <td><?php echo esc_html($visitor->country ?: 'Unknown'); ?></td>
                                        <td><?php echo esc_html($visitor->browser ?: 'Unknown'); ?></td>
                                        <td><?php echo esc_html($visitor->os ?: 'Unknown'); ?></td>
                                        <td><?php echo esc_html($visitor->device_type ?: 'Unknown'); ?></td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 200px;" title="<?php echo esc_attr($visitor->page_url); ?>">
                                                <?php echo esc_html($visitor->page_title ?: $visitor->page_url); ?>
                                            </div>
                                        </td>
                                        <td><?php echo esc_html(human_time_diff(strtotime($visitor->visit_date), current_time('timestamp'))); ?> <?php esc_html_e('ago', 'maneli-car-inquiry'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script type="text/javascript">
(function() {
    // Wait for jQuery to be available
    function initDatePickers() {
        if (typeof jQuery === 'undefined') {
            setTimeout(initDatePickers, 100);
            return;
        }
        
        jQuery(document).ready(function($) {
            // Initialize Persian Datepicker
            if (typeof $.fn.persianDatepicker !== 'undefined') {
                $('#start-date-picker').persianDatepicker({
                    formatDate: 'YYYY/MM/DD',
                    persianNumbers: true,
                    autoClose: true
                });
                $('#end-date-picker').persianDatepicker({
                    formatDate: 'YYYY/MM/DD',
                    persianNumbers: true,
                    autoClose: true
                });
            }
            
            // Handle period filter change
            $('#period-filter').on('change', function() {
                var period = $(this).val();
                if (period === 'custom') {
                    $('#custom-start-date, #custom-end-date').removeClass('maneli-initially-hidden').show();
                } else {
                    $('#custom-start-date, #custom-end-date').addClass('maneli-initially-hidden').hide();
                }
            });
        });
    }
    
    // Start initialization
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDatePickers);
    } else {
        initDatePickers();
    }
})();
</script>

