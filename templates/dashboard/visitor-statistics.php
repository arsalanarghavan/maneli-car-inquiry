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

$use_persian_digits = function_exists('maneli_should_use_persian_digits') ? maneli_should_use_persian_digits() : true;

$visitor_stats_format_number = static function($value, $decimals = 0) use ($use_persian_digits) {
    if ($use_persian_digits) {
        return maneli_number_format_persian($value, $decimals);
    }
    $formatted = number_format_i18n((float) $value, $decimals);
    if (function_exists('maneli_convert_to_english_digits')) {
        return maneli_convert_to_english_digits($formatted);
    }
    return $formatted;
};

$visitor_stats_convert_digits = static function($value) use ($use_persian_digits) {
    if ($use_persian_digits && function_exists('persian_numbers_no_separator')) {
        return persian_numbers_no_separator($value);
    }
    if (!$use_persian_digits && function_exists('maneli_convert_to_english_digits')) {
        return maneli_convert_to_english_digits($value);
    }
    return $value;
};

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

// Determine defaults for start/end date displays based on locale
$today_gregorian = current_time('Y-m-d');
$thirty_days_ago_gregorian = date('Y-m-d', strtotime('-30 days', strtotime($today_gregorian)));

if ($use_persian_digits) {
    $default_start_display = maneli_gregorian_to_jalali(date('Y', strtotime($thirty_days_ago_gregorian)), date('m', strtotime($thirty_days_ago_gregorian)), date('d', strtotime($thirty_days_ago_gregorian)), 'Y/m/d', false);
    $default_end_display   = maneli_gregorian_to_jalali(date('Y', strtotime($today_gregorian)), date('m', strtotime($today_gregorian)), date('d', strtotime($today_gregorian)), 'Y/m/d', false);
} else {
    $default_start_display = date_i18n('Y-m-d', strtotime($thirty_days_ago_gregorian));
    $default_end_display   = date_i18n('Y-m-d', strtotime($today_gregorian));
}

$start_date_input_raw = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : $default_start_display;
$end_date_input_raw   = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : $default_end_display;

if ($use_persian_digits) {
    $start_date_normalized = function_exists('maneli_normalize_jalali_date') ? maneli_normalize_jalali_date($start_date_input_raw) : null;
    if ($start_date_normalized) {
        $start_parts = explode('/', $start_date_normalized);
        $start_date = maneli_jalali_to_gregorian($start_parts[0], $start_parts[1], $start_parts[2]);
        $start_date_display = $visitor_stats_convert_digits($start_date_normalized);
    } else {
        $start_date = $thirty_days_ago_gregorian;
        $start_date_display = $visitor_stats_convert_digits($default_start_display);
    }

    $end_date_normalized = function_exists('maneli_normalize_jalali_date') ? maneli_normalize_jalali_date($end_date_input_raw) : null;
    if ($end_date_normalized) {
        $end_parts = explode('/', $end_date_normalized);
        $end_date = maneli_jalali_to_gregorian($end_parts[0], $end_parts[1], $end_parts[2]);
        $end_date_display = $visitor_stats_convert_digits($end_date_normalized);
    } else {
        $end_date = $today_gregorian;
        $end_date_display = $visitor_stats_convert_digits($default_end_display);
    }
} else {
    $start_timestamp = strtotime($start_date_input_raw);
    if ($start_timestamp === false) {
        $start_timestamp = strtotime($thirty_days_ago_gregorian);
    }
    $start_date = date('Y-m-d', $start_timestamp);
    $start_date_display = date_i18n('Y-m-d', $start_timestamp);
    if (function_exists('maneli_convert_to_english_digits')) {
        $start_date_display = maneli_convert_to_english_digits($start_date_display);
    }

    $end_timestamp = strtotime($end_date_input_raw);
    if ($end_timestamp === false) {
        $end_timestamp = strtotime($today_gregorian);
    }
    $end_date = date('Y-m-d', $end_timestamp);
    $end_date_display = date_i18n('Y-m-d', $end_timestamp);
    if (function_exists('maneli_convert_to_english_digits')) {
        $end_date_display = maneli_convert_to_english_digits($end_date_display);
    }
}

// Ensure start date is not after end date
if (strtotime($start_date) > strtotime($end_date)) {
    $tmp_date = $start_date;
    $start_date = $end_date;
    $end_date = $tmp_date;

    $tmp_display = $start_date_display;
    $start_date_display = $end_date_display;
    $end_date_display = $tmp_display;
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
                                <label class="form-label">
                                    <?php echo esc_html($use_persian_digits ? __('From Date (Solar):', 'maneli-car-inquiry') : __('From Date:', 'maneli-car-inquiry')); ?>
                                </label>
                                <input
                                    <?php echo $use_persian_digits ? 'type="text"' : 'type="date"'; ?>
                                    name="start_date"
                                    id="start-date-picker"
                                    class="form-control"
                                    value="<?php echo esc_attr($start_date_display); ?>"
                                    placeholder="<?php echo esc_attr($use_persian_digits ? 'YYYY/MM/DD' : 'YYYY-MM-DD'); ?>"
                                    <?php echo $use_persian_digits ? '' : 'pattern="\\d{4}-\\d{2}-\\d{2}"'; ?>
                                >
                            </div>
                            <div class="col-md-3 maneli-initially-hidden" id="custom-end-date">
                                <label class="form-label">
                                    <?php echo esc_html($use_persian_digits ? __('To Date (Solar):', 'maneli-car-inquiry') : __('To Date:', 'maneli-car-inquiry')); ?>
                                </label>
                                <input
                                    <?php echo $use_persian_digits ? 'type="text"' : 'type="date"'; ?>
                                    name="end_date"
                                    id="end-date-picker"
                                    class="form-control"
                                    value="<?php echo esc_attr($end_date_display); ?>"
                                    placeholder="<?php echo esc_attr($use_persian_digits ? 'YYYY/MM/DD' : 'YYYY-MM-DD'); ?>"
                                    <?php echo $use_persian_digits ? '' : 'pattern="\\d{4}-\\d{2}-\\d{2}"'; ?>
                                >
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary btn-wave w-100">
                                    <i class="ri-filter-line me-1"></i><?php esc_html_e('Apply Filters', 'maneli-car-inquiry'); ?>
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
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-6">
                <div class="card custom-card crm-card overflow-hidden maneli-stat-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="p-2 border border-primary border-opacity-10 bg-primary-transparent rounded-circle">
                                    <span class="avatar avatar-md avatar-rounded bg-primary svg-white">
                                        <i class="ri-eye-line fs-20"></i>
                                    </span>
                                </div>
                                <div>
                                    <p class="text-muted fs-14 mb-1"><?php esc_html_e('Total Visits', 'maneli-car-inquiry'); ?></p>
                                    <h4 class="fw-semibold mb-0 maneli-stat-value" id="total-visits"><?php echo esc_html($visitor_stats_format_number($overall_stats['total_visits'])); ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-6">
                <div class="card custom-card crm-card overflow-hidden maneli-stat-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="p-2 border border-success border-opacity-10 bg-success-transparent rounded-circle">
                                    <span class="avatar avatar-md avatar-rounded bg-success svg-white">
                                        <i class="ri-user-line fs-20"></i>
                                    </span>
                                </div>
                                <div>
                                    <p class="text-muted fs-14 mb-1"><?php esc_html_e('Unique Visitors', 'maneli-car-inquiry'); ?></p>
                                    <h4 class="fw-semibold mb-0 text-success maneli-stat-value" id="unique-visitors"><?php echo esc_html($visitor_stats_format_number($overall_stats['unique_visitors'])); ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-6">
                <div class="card custom-card crm-card overflow-hidden maneli-stat-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="p-2 border border-info border-opacity-10 bg-info-transparent rounded-circle">
                                    <span class="avatar avatar-md avatar-rounded bg-info svg-white">
                                        <i class="ri-file-line fs-20"></i>
                                    </span>
                                </div>
                                <div>
                                    <p class="text-muted fs-14 mb-1"><?php esc_html_e('Total Pages', 'maneli-car-inquiry'); ?></p>
                                    <h4 class="fw-semibold mb-0 text-info maneli-stat-value" id="total-pages"><?php echo esc_html($visitor_stats_format_number($overall_stats['total_pages'])); ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-6">
                <div class="card custom-card crm-card overflow-hidden maneli-stat-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="p-2 border border-warning border-opacity-10 bg-warning-transparent rounded-circle">
                                    <span class="avatar avatar-md avatar-rounded bg-warning svg-white">
                                        <i class="ri-user-star-line fs-20"></i>
                                    </span>
                                </div>
                                <div>
                                    <p class="text-muted fs-14 mb-1"><?php esc_html_e('Online Now', 'maneli-car-inquiry'); ?></p>
                                    <h4 class="fw-semibold mb-0 text-warning maneli-stat-value" id="online-visitors"><?php echo esc_html($visitor_stats_format_number(count($online_visitors))); ?></h4>
                                </div>
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
                                        $display_date = $stat->date;
                                        if ($use_persian_digits) {
                                            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $stat->date)) {
                                                $date_parts = explode('-', $stat->date);
                                                $display_date = maneli_gregorian_to_jalali($date_parts[0], $date_parts[1], $date_parts[2], 'Y/m/d');
                                            }
                                            $display_date = $visitor_stats_convert_digits($display_date);
                                        } else {
                                            $normalized_jalali = function_exists('maneli_normalize_jalali_date') ? maneli_normalize_jalali_date($stat->date) : null;
                                if ($normalized_jalali) {
                                                $parts = explode('/', $normalized_jalali);
                                    if (count($parts) === 3) {
                                        $year = (int) $parts[0];
                                        // Jalali years are currently around 1300-1500. Skip conversion if the value looks Gregorian.
                                        if ($year > 0 && $year <= 1600) {
                                                    $display_date = maneli_jalali_to_gregorian($parts[0], $parts[1], $parts[2]);
                                        } else {
                                            $display_date = str_replace('/', '-', $normalized_jalali);
                                        }
                                                }
                                            }
                                            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $display_date)) {
                                                $display_date = date_i18n('Y-m-d', strtotime($display_date));
                                            }
                                            $display_date = $visitor_stats_convert_digits($display_date);
                                        }
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html($display_date); ?></td>
                                        <td><?php echo esc_html($visitor_stats_format_number($stat->visits)); ?></td>
                                        <td><?php echo esc_html($visitor_stats_format_number($stat->unique_visitors)); ?></td>
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
                                        <td><?php echo esc_html($visitor_stats_format_number($page->visit_count)); ?></td>
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
                                        <td>
                                            <?php
                                            $product_label = $product->product_name;
                                            if (empty($product_label)) {
                                                $product_label = sprintf(esc_html__('Product #%s', 'maneli-car-inquiry'), $visitor_stats_format_number($product->product_id));
                                            }
                                            echo esc_html($product_label);
                                            ?>
                                        </td>
                                        <td><?php echo esc_html($visitor_stats_format_number($product->visit_count)); ?></td>
                                        <td><?php echo esc_html($visitor_stats_format_number($product->unique_visitors)); ?></td>
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
                                        <td><?php echo esc_html($visitor_stats_format_number($engine->visit_count)); ?></td>
                                        <td><?php echo esc_html($visitor_stats_format_number($engine->unique_visitors)); ?></td>
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
                                        <td><?php echo esc_html($visitor_stats_format_number($referrer->visit_count)); ?></td>
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
                                        <?php $model_label = Maneli_Visitor_Statistics::translate_device_model($model->device_model); ?>
                                        <td><?php echo esc_html($model_label); ?></td>
                                        <td><?php echo esc_html($visitor_stats_format_number($model->visit_count)); ?></td>
                                        <td><?php echo esc_html($visitor_stats_format_number($model->unique_visitors)); ?></td>
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
                                    <?php foreach (array_slice($country_stats, 0, 10) as $country): 
                                        $country_name = Maneli_Visitor_Statistics::translate_country_name($country->country_code, $country->country);
                                        $flag_icon = Maneli_Visitor_Statistics::get_country_flag_icon($country->country_code, $country->country);
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="maneli-emoji-flag me-2"><?php echo esc_html($flag_icon); ?></span>
                                            <?php echo esc_html($country_name); ?>
                                        </td>
                                        <td><?php echo esc_html($visitor_stats_format_number($country->visit_count)); ?></td>
                                        <td><?php echo esc_html($visitor_stats_format_number($country->unique_visitors)); ?></td>
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
                                    <?php foreach ($most_active_visitors as $visitor):
                                        $active_country = Maneli_Visitor_Statistics::translate_country_name($visitor->country_code ?? '', $visitor->country ?? '');
                                        $active_flag = Maneli_Visitor_Statistics::get_country_flag_icon($visitor->country_code ?? '', $visitor->country ?? '');
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html($visitor->ip_address); ?></td>
                                        <td>
                                            <span class="maneli-emoji-flag me-2"><?php echo esc_html($active_flag); ?></span>
                                            <?php echo esc_html($active_country); ?>
                                        </td>
                                        <td><?php echo esc_html($visitor_stats_format_number($visitor->visit_count)); ?></td>
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
                                        if ($visit_timestamp === false && isset($visit->visit_date)) {
                                            $parts = preg_split('/\s+/', $visit->visit_date);
                                            $date_part = $parts[0] ?? '';
                                            $time_part = $parts[1] ?? '00:00';
                                            $normalized_jalali = function_exists('maneli_normalize_jalali_date') ? maneli_normalize_jalali_date($date_part) : null;
                                            if ($normalized_jalali) {
                                                $jalali_segments = explode('/', $normalized_jalali);
                                                if (count($jalali_segments) === 3) {
                                                    $gregorian_date = maneli_jalali_to_gregorian($jalali_segments[0], $jalali_segments[1], $jalali_segments[2]);
                                                    $visit_timestamp = strtotime(trim($gregorian_date . ' ' . $time_part));
                                                }
                                            }
                                        }
                                        if ($visit_timestamp === false) {
                                            $visit_timestamp = current_time('timestamp');
                                        }
                                        if ($use_persian_digits) {
                                            $visit_date_display = maneli_gregorian_to_jalali(date('Y', $visit_timestamp), date('m', $visit_timestamp), date('d', $visit_timestamp), 'Y/m/d');
                                            $visit_time_display = date('H:i', $visit_timestamp);
                                            $visit_datetime_display = $visitor_stats_convert_digits(trim($visit_date_display . ' ' . $visit_time_display));
                                        } else {
                                            $visit_datetime_display = date_i18n('Y-m-d H:i', $visit_timestamp);
                                        }
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html($visit_datetime_display); ?></td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 200px;" title="<?php echo esc_attr($visit->page_url); ?>">
                                                <?php echo esc_html($visit->page_title ?: $visit->page_url); ?>
                                            </div>
                                        </td>
                                    <?php
                                        $recent_country = Maneli_Visitor_Statistics::translate_country_name($visit->country_code ?? '', $visit->country ?? '');
                                        $recent_flag = Maneli_Visitor_Statistics::get_country_flag_icon($visit->country_code ?? '', $visit->country ?? '');
                                    ?>
                                    <td>
                                        <span class="maneli-emoji-flag me-2"><?php echo esc_html($recent_flag); ?></span>
                                        <?php echo esc_html($recent_country); ?>
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
                                    <?php foreach ($online_visitors as $visitor): 
                                        $country_display = $visitor->country ?: esc_html__('Unknown', 'maneli-car-inquiry');
                                        $country_flag_icon = $visitor->country_flag_icon ?? $visitor->country_flag ?? Maneli_Visitor_Statistics::get_country_flag_icon($visitor->country_code ?? '', $visitor->country ?? '');
                                        $browser_display = $visitor->browser ?: esc_html__('Unknown', 'maneli-car-inquiry');
                                        $os_display = $visitor->os ?: esc_html__('Unknown', 'maneli-car-inquiry');
                                        $device_display = $visitor->device_type_label ?? Maneli_Visitor_Statistics::translate_device_type($visitor->device_type ?? '');
                                        if ($use_persian_digits && function_exists('persian_numbers_no_separator')) {
                                            $device_display = persian_numbers_no_separator($device_display);
                                        } elseif (!$use_persian_digits && function_exists('maneli_convert_to_english_digits')) {
                                            $device_display = maneli_convert_to_english_digits($device_display);
                                        }
                                        $time_ago_display = $visitor->time_ago ?? '';
                                        if (!$use_persian_digits && function_exists('maneli_convert_to_english_digits')) {
                                            $time_ago_display = maneli_convert_to_english_digits($time_ago_display);
                                        }
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html($visitor->ip_address); ?></td>
                                        <td>
                                            <span class="maneli-emoji-flag me-2"><?php echo esc_html($country_flag_icon); ?></span>
                                            <?php echo esc_html($country_display); ?>
                                        </td>
                                        <td><?php echo esc_html($browser_display); ?></td>
                                        <td><?php echo esc_html($os_display); ?></td>
                                        <td><?php echo esc_html($device_display); ?></td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 200px;" title="<?php echo esc_attr($visitor->page_url); ?>">
                                                <?php echo esc_html($visitor->page_title ?: $visitor->page_url); ?>
                                            </div>
                                        </td>
                                        <td><?php echo esc_html($time_ago_display); ?></td>
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
window.maneliVisitorStatsConfig = <?php echo wp_json_encode([
    'usePersianDatepicker' => $use_persian_digits,
    'usePersianDigits' => $use_persian_digits,
]); ?>;
</script>
<script type="text/javascript">
(function() {
    var config = window.maneliVisitorStatsConfig || {};
    // Wait for jQuery to be available
    function initDatePickers() {
        if (typeof jQuery === 'undefined') {
            setTimeout(initDatePickers, 100);
            return;
        }
        
        jQuery(document).ready(function($) {
            $('#period-filter').on('change', function() {
                var period = $(this).val();
                if (period === 'custom') {
                    $('#custom-start-date, #custom-end-date').removeClass('maneli-initially-hidden').show();
                } else {
                    $('#custom-start-date, #custom-end-date').addClass('maneli-initially-hidden').hide();
                }
            });

            if ($('#period-filter').val() === 'custom') {
                $('#custom-start-date, #custom-end-date').removeClass('maneli-initially-hidden').show();
            }

            if (!config.usePersianDatepicker || typeof $.fn.persianDatepicker === 'undefined') {
                return;
            }

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

            function maneliConvertDigitsToEnglish(str) {
                var persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
                var arabic  = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
                for (var i = 0; i < 10; i++) {
                    str = str.replace(new RegExp(persian[i], 'g'), i.toString());
                    str = str.replace(new RegExp(arabic[i], 'g'), i.toString());
                }
                return str;
            }

            function maneliConvertDigitsToPersian(str) {
                var persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
                return str.replace(/\d/g, function(digit) {
                    return persian[parseInt(digit, 10)] || digit;
                });
            }

            function maneliNormalizeDateValue(value) {
                if (!value) {
                    return value;
                }
                var normalized = maneliConvertDigitsToEnglish(value).replace(/[-.]/g, '/');
                var parts = normalized.split('/');
                if (parts.length !== 3) {
                    return value;
                }
                parts[1] = parts[1].padStart(2, '0');
                parts[2] = parts[2].padStart(2, '0');
                return maneliConvertDigitsToPersian(parts.join('/'));
            }

            $('#start-date-picker, #end-date-picker').on('change', function() {
                var currentValue = $(this).val();
                var normalizedValue = maneliNormalizeDateValue(currentValue);
                $(this).val(normalizedValue);
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

