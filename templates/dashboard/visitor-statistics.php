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

// Handle period parameter
$period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : 'month';
$calculated_start = null;
$calculated_end = null;

switch ($period) {
    case 'today':
        $calculated_start = $today_gregorian;
        $calculated_end = $today_gregorian;
        break;
    case 'yesterday':
        $yesterday = date('Y-m-d', strtotime('-1 day', strtotime($today_gregorian)));
        $calculated_start = $yesterday;
        $calculated_end = $yesterday;
        break;
    case 'week':
        $calculated_start = date('Y-m-d', strtotime('-7 days', strtotime($today_gregorian)));
        $calculated_end = $today_gregorian;
        break;
    case 'month':
        $calculated_start = $thirty_days_ago_gregorian;
        $calculated_end = $today_gregorian;
        break;
    case '3months':
        $calculated_start = date('Y-m-d', strtotime('-3 months', strtotime($today_gregorian)));
        $calculated_end = $today_gregorian;
        break;
    case '6months':
        $calculated_start = date('Y-m-d', strtotime('-6 months', strtotime($today_gregorian)));
        $calculated_end = $today_gregorian;
        break;
    case 'year':
        $calculated_start = date('Y-m-d', strtotime('-1 year', strtotime($today_gregorian)));
        $calculated_end = $today_gregorian;
        break;
    case 'custom':
    default:
        // Use provided dates or defaults
        break;
}

if ($calculated_start && $calculated_end) {
    if ($use_persian_digits) {
        $start_parts = explode('-', $calculated_start);
        $end_parts = explode('-', $calculated_end);
        $default_start_display = maneli_gregorian_to_jalali($start_parts[0], $start_parts[1], $start_parts[2], 'Y/m/d', false);
        $default_end_display = maneli_gregorian_to_jalali($end_parts[0], $end_parts[1], $end_parts[2], 'Y/m/d', false);
    } else {
        $default_start_display = date_i18n('Y-m-d', strtotime($calculated_start));
        $default_end_display = date_i18n('Y-m-d', strtotime($calculated_end));
    }
} else {
    if ($use_persian_digits) {
        $default_start_display = maneli_gregorian_to_jalali(date('Y', strtotime($thirty_days_ago_gregorian)), date('m', strtotime($thirty_days_ago_gregorian)), date('d', strtotime($thirty_days_ago_gregorian)), 'Y/m/d', false);
        $default_end_display   = maneli_gregorian_to_jalali(date('Y', strtotime($today_gregorian)), date('m', strtotime($today_gregorian)), date('d', strtotime($today_gregorian)), 'Y/m/d', false);
    } else {
        $default_start_display = date_i18n('Y-m-d', strtotime($thirty_days_ago_gregorian));
        $default_end_display   = date_i18n('Y-m-d', strtotime($today_gregorian));
    }
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

// Calculate previous period for comparison
$days_diff = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24);
$prev_start_date = date('Y-m-d', strtotime($start_date . ' -' . ($days_diff + 1) . ' days'));
$prev_end_date = date('Y-m-d', strtotime($start_date . ' -1 day'));
$prev_stats = Maneli_Visitor_Statistics::get_overall_stats($prev_start_date, $prev_end_date);
$prev_daily_stats = Maneli_Visitor_Statistics::get_daily_visits($prev_start_date, $prev_end_date);
$top_pages = Maneli_Visitor_Statistics::get_top_pages(10, $start_date, $end_date);
$top_products = Maneli_Visitor_Statistics::get_top_products(10, $start_date, $end_date);
$browser_stats = Maneli_Visitor_Statistics::get_browser_stats($start_date, $end_date);
$os_stats = Maneli_Visitor_Statistics::get_os_stats($start_date, $end_date);
$device_stats = Maneli_Visitor_Statistics::get_device_stats($start_date, $end_date);
$country_stats = Maneli_Visitor_Statistics::get_country_stats($start_date, $end_date);
$search_engine_stats = Maneli_Visitor_Statistics::get_search_engine_stats($start_date, $end_date);
$referrer_stats = Maneli_Visitor_Statistics::get_referrer_stats(10, $start_date, $end_date);
$recent_visitors = Maneli_Visitor_Statistics::get_recent_visitors(50, $start_date, $end_date);
$online_visitors = Maneli_Visitor_Statistics::get_online_visitors();
$most_active_visitors = Maneli_Visitor_Statistics::get_most_active_visitors(10, $start_date, $end_date);
$period_statistics = Maneli_Visitor_Statistics::get_period_statistics();

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
                <div class="card custom-card maneli-mobile-filter-card" data-maneli-mobile-filter>
                    <div class="card-header bg-light">
                        <div
                            class="card-title d-flex align-items-center gap-2 maneli-mobile-filter-toggle"
                            data-maneli-filter-toggle
                            role="button"
                            tabindex="0"
                            aria-expanded="false"
                        >
                            <i class="ri-calendar-line"></i>
                            <?php esc_html_e('Date Range Filter', 'maneli-car-inquiry'); ?>
                            <i class="ri-arrow-down-s-line ms-auto maneli-mobile-filter-arrow d-md-none"></i>
                        </div>
                    </div>
                    <div class="card-body maneli-mobile-filter-body" data-maneli-filter-body>
                        <form id="visitor-stats-filter-form" method="get">
                            <div class="row g-3 align-items-end">
                                <div class="col-12 col-lg-3">
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
                                <div class="col-6 col-lg-3 maneli-initially-hidden" id="custom-start-date">
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
                                        <?php echo $use_persian_digits ? '' : 'pattern="\d{4}-\d{2}-\d{2}"'; ?>
                                    >
                                </div>
                                <div class="col-6 col-lg-3 maneli-initially-hidden" id="custom-end-date">
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
                                        <?php echo $use_persian_digits ? '' : 'pattern="\d{4}-\d{2}-\d{2}"'; ?>
                                    >
                                </div>
                                <div class="col-12 col-lg-3">
                                    <div class="row g-2">
                                        <div class="col-6 col-lg-12">
                                            <button type="submit" class="btn btn-primary btn-wave w-100">
                                                <i class="ri-filter-line me-1"></i><?php esc_html_e('Apply Filters', 'maneli-car-inquiry'); ?>
                                            </button>
                                        </div>
                                        <div class="col-6 d-lg-none"></div>
                                    </div>
                                </div>
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

        <!-- Period Statistics Box -->
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ri-file-list-line me-2"></i><?php esc_html_e('Summary', 'maneli-car-inquiry'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="ri-user-heart-line me-2"></i>
                                <span class="fw-bold"><?php esc_html_e('Online Visitors', 'maneli-car-inquiry'); ?></span>
                                <span class="fw-bold text-primary"><?php echo esc_html($visitor_stats_format_number(count($online_visitors))); ?></span>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover text-nowrap">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Time', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Visitors', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Visits', 'maneli-car-inquiry'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><?php esc_html_e('Today', 'maneli-car-inquiry'); ?></td>
                                        <td><?php echo esc_html($visitor_stats_format_number($period_statistics['today']['visitors'])); ?></td>
                                        <td><?php echo esc_html($visitor_stats_format_number($period_statistics['today']['visits'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('Yesterday', 'maneli-car-inquiry'); ?></td>
                                        <td><?php echo esc_html($visitor_stats_format_number($period_statistics['yesterday']['visitors'])); ?></td>
                                        <td><?php echo esc_html($visitor_stats_format_number($period_statistics['yesterday']['visits'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('This Week', 'maneli-car-inquiry'); ?></td>
                                        <td><?php echo esc_html($visitor_stats_format_number($period_statistics['this_week']['visitors'])); ?></td>
                                        <td><?php echo esc_html($visitor_stats_format_number($period_statistics['this_week']['visits'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('Last Week', 'maneli-car-inquiry'); ?></td>
                                        <td><?php echo esc_html($visitor_stats_format_number($period_statistics['last_week']['visitors'])); ?></td>
                                        <td><?php echo esc_html($visitor_stats_format_number($period_statistics['last_week']['visits'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('This Month', 'maneli-car-inquiry'); ?></td>
                                        <td><?php echo esc_html($visitor_stats_format_number($period_statistics['this_month']['visitors'])); ?></td>
                                        <td><?php echo esc_html($visitor_stats_format_number($period_statistics['this_month']['visits'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('Last Month', 'maneli-car-inquiry'); ?></td>
                                        <td><?php echo esc_html($visitor_stats_format_number($period_statistics['last_month']['visitors'])); ?></td>
                                        <td><?php echo esc_html($visitor_stats_format_number($period_statistics['last_month']['visits'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('Last 7 Days', 'maneli-car-inquiry'); ?></td>
                                        <td><?php echo esc_html($visitor_stats_format_number($period_statistics['last_7_days']['visitors'])); ?></td>
                                        <td><?php echo esc_html($visitor_stats_format_number($period_statistics['last_7_days']['visits'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <?php 
                                            $days_diff = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24);
                                            if ($days_diff == 0) {
                                                esc_html_e('Today', 'maneli-car-inquiry');
                                            } elseif ($days_diff == 1) {
                                                esc_html_e('Yesterday', 'maneli-car-inquiry');
                                            } elseif ($days_diff <= 7) {
                                                printf(esc_html__('Last %d Days', 'maneli-car-inquiry'), (int)$days_diff + 1);
                                            } elseif ($days_diff <= 30) {
                                                printf(esc_html__('Last %d Days', 'maneli-car-inquiry'), (int)$days_diff + 1);
                                            } elseif ($days_diff <= 90) {
                                                printf(esc_html__('Last %d Days', 'maneli-car-inquiry'), (int)$days_diff + 1);
                                            } else {
                                                printf(esc_html__('Selected Period (%d Days)', 'maneli-car-inquiry'), (int)$days_diff + 1);
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo esc_html($visitor_stats_format_number($overall_stats['unique_visitors'])); ?></td>
                                        <td><?php echo esc_html($visitor_stats_format_number($overall_stats['total_visits'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('Last 90 Days', 'maneli-car-inquiry'); ?></td>
                                        <td><?php echo esc_html($visitor_stats_format_number($period_statistics['last_90_days']['visitors'])); ?></td>
                                        <td><?php echo esc_html($visitor_stats_format_number($period_statistics['last_90_days']['visits'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('Last 6 Months', 'maneli-car-inquiry'); ?></td>
                                        <td><?php echo esc_html($visitor_stats_format_number($period_statistics['last_6_months']['visitors'])); ?></td>
                                        <td><?php echo esc_html($visitor_stats_format_number($period_statistics['last_6_months']['visits'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('This Year (January to Today)', 'maneli-car-inquiry'); ?></td>
                                        <td><?php echo esc_html($visitor_stats_format_number($period_statistics['this_year']['visitors'])); ?></td>
                                        <td><?php echo esc_html($visitor_stats_format_number($period_statistics['this_year']['visits'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('All Time', 'maneli-car-inquiry'); ?></td>
                                        <td><?php echo esc_html($visitor_stats_format_number($period_statistics['all_time']['visitors'])); ?></td>
                                        <td><?php echo esc_html($visitor_stats_format_number($period_statistics['all_time']['visits'])); ?></td>
                                    </tr>
                                </tbody>
                            </table>
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
                            <i class="ri-line-chart-line me-2"></i><?php esc_html_e('Traffic Trend Report', 'maneli-car-inquiry'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Chart -->
                        <div id="traffic-trend-chart" style="min-height: 350px;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Pages and Most Viewed Cars -->
        <div class="row">
            <!-- Top Pages -->
            <div class="col-xl-6">
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
                                        <th class="text-end"><?php esc_html_e('Visits', 'maneli-car-inquiry'); ?></th>
                                        <th class="text-center"><?php esc_html_e('View Content', 'maneli-car-inquiry'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_pages as $page): 
                                        // Get page title - use page_title if available, otherwise extract from URL
                                        $page_display_title = trim($page->page_title);
                                        
                                        if (empty($page_display_title)) {
                                            // Try to extract title from URL
                                            $url_parts = parse_url($page->page_url);
                                            $path = isset($url_parts['path']) ? trim($url_parts['path'], '/') : '';
                                            
                                            if (empty($path) || $path === '/') {
                                                $page_display_title = esc_html__('Home Page', 'maneli-car-inquiry');
                                            } else {
                                                // Convert URL path to readable title
                                                $path_parts = explode('/', $path);
                                                $last_part = end($path_parts);
                                                // Decode URL encoding
                                                $last_part = urldecode($last_part);
                                                // Convert to readable format
                                                $page_display_title = ucwords(str_replace(['-', '_'], ' ', $last_part));
                                            }
                                        }
                                        
                                        $page_url = esc_url($page->page_url);
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="text-truncate" style="max-width: 300px;" title="<?php echo esc_attr($page->page_url); ?>">
                                                <?php echo esc_html($page_display_title); ?>
                                            </div>
                                        </td>
                                        <td class="text-end"><?php echo esc_html($visitor_stats_format_number($page->visit_count)); ?></td>
                                        <td class="text-center">
                                            <a href="<?php echo $page_url; ?>" target="_blank" class="btn btn-sm btn-primary-light" title="<?php esc_attr_e('View Content', 'maneli-car-inquiry'); ?>">
                                                <i class="ri-eye-line me-1"></i><?php esc_html_e('View Content', 'maneli-car-inquiry'); ?>
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
            
            <!-- Most Viewed Cars -->
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
                                    <?php foreach ($top_products as $product): 
                                        $product_id = (int)$product->product_id;
                                        
                                        // Skip invalid product IDs
                                        if ($product_id <= 0) {
                                            continue;
                                        }
                                        
                                        $product_obj = wc_get_product($product_id);
                                        
                                        // Get product name
                                        $product_label = $product->product_name;
                                        if (empty($product_label) && $product_obj) {
                                            $product_label = $product_obj->get_name();
                                        }
                                        if (empty($product_label)) {
                                            $product_label = sprintf(esc_html__('Product #%s', 'maneli-car-inquiry'), $visitor_stats_format_number($product_id));
                                        }
                                        
                                        // Get product URL
                                        $product_url = '';
                                        if ($product_obj) {
                                            $product_url = get_permalink($product_id);
                                        }
                                        
                                        // Get product image
                                        $product_image = '';
                                        if ($product_obj) {
                                            $image_id = $product_obj->get_image_id();
                                            if ($image_id) {
                                                $image_url = wp_get_attachment_image_url($image_id, 'thumbnail');
                                                if ($image_url) {
                                                    $product_image = '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($product_label) . '" class="rounded" style="width: 50px; height: 50px; object-fit: cover;">';
                                                }
                                            }
                                        }
                                        
                                        // If no image, show placeholder
                                        if (empty($product_image)) {
                                            $product_image = '<div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;"><i class="ri-car-line fs-20 text-muted"></i></div>';
                                        }
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="flex-shrink-0">
                                                    <?php echo $product_image; ?>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <?php if ($product_url): ?>
                                                        <a href="<?php echo esc_url($product_url); ?>" target="_blank" class="text-decoration-none fw-medium">
                                                            <?php echo esc_html($product_label); ?>
                                                            <i class="ri-external-link-line ms-1 fs-12"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="fw-medium"><?php echo esc_html($product_label); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
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

        <!-- Browsers -->
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ri-global-line me-2"></i><?php esc_html_e('Browsers', 'maneli-car-inquiry'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <?php 
                                // Calculate total visits for percentage calculation
                                $total_browser_visits = array_sum(array_column($browser_stats, 'visit_count'));
                                $top_browsers = array_slice($browser_stats, 0, 5); // Top 5 browsers
                                $other_browsers = array_slice($browser_stats, 5); // Rest
                                $other_visits = array_sum(array_column($other_browsers, 'visit_count'));
                                ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th><?php esc_html_e('Browser', 'maneli-car-inquiry'); ?></th>
                                                <th class="text-end"><?php esc_html_e('Visits', 'maneli-car-inquiry'); ?></th>
                                                <th class="text-end"><?php esc_html_e('Percentage', 'maneli-car-inquiry'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($top_browsers as $browser): 
                                                $percentage = $total_browser_visits > 0 ? round(($browser['visit_count'] / $total_browser_visits) * 100, 1) : 0;
                                                $browser_icon = Maneli_Visitor_Statistics::get_browser_icon($browser['browser']);
                                                $browser_label = $browser['browser_label'];
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span><?php echo $browser_icon; ?></span>
                                                        <span><?php echo esc_html($browser_label); ?></span>
                                                    </div>
                                                </td>
                                                <td class="text-end"><?php echo esc_html($visitor_stats_format_number($browser['visit_count'])); ?></td>
                                                <td class="text-end"><?php echo esc_html($visitor_stats_format_number($percentage, 1)); ?>%</td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php if ($other_visits > 0): 
                                                $other_percentage = $total_browser_visits > 0 ? round(($other_visits / $total_browser_visits) * 100, 1) : 0;
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span><i class="ri-more-line fs-18 text-secondary"></i></span>
                                                        <span><?php esc_html_e('Others', 'maneli-car-inquiry'); ?></span>
                                                    </div>
                                                </td>
                                                <td class="text-end"><?php echo esc_html($visitor_stats_format_number($other_visits)); ?></td>
                                                <td class="text-end"><?php echo esc_html($visitor_stats_format_number($other_percentage, 1)); ?>%</td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div id="browsers-chart" style="min-height: 300px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Operating Systems -->
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ri-computer-line me-2"></i><?php esc_html_e('Operating Systems', 'maneli-car-inquiry'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <?php 
                                // Calculate total visits for percentage calculation
                                $total_os_visits = array_sum(array_column($os_stats, 'visit_count'));
                                $top_os = array_slice($os_stats, 0, 5); // Top 5 OS
                                $other_os = array_slice($os_stats, 5); // Rest
                                $other_os_visits = array_sum(array_column($other_os, 'visit_count'));
                                ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th><?php esc_html_e('Operating System', 'maneli-car-inquiry'); ?></th>
                                                <th class="text-end"><?php esc_html_e('Visits', 'maneli-car-inquiry'); ?></th>
                                                <th class="text-end"><?php esc_html_e('Percentage', 'maneli-car-inquiry'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($top_os as $os): 
                                                $percentage = $total_os_visits > 0 ? round(($os['visit_count'] / $total_os_visits) * 100, 1) : 0;
                                                $os_icon = Maneli_Visitor_Statistics::get_os_icon($os['os']);
                                                $os_label = $os['os_label'];
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span><?php echo $os_icon; ?></span>
                                                        <span><?php echo esc_html($os_label); ?></span>
                                                    </div>
                                                </td>
                                                <td class="text-end"><?php echo esc_html($visitor_stats_format_number($os['visit_count'])); ?></td>
                                                <td class="text-end"><?php echo esc_html($visitor_stats_format_number($percentage, 1)); ?>%</td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php if ($other_os_visits > 0): 
                                                $other_os_percentage = $total_os_visits > 0 ? round(($other_os_visits / $total_os_visits) * 100, 1) : 0;
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span><i class="ri-more-line fs-18 text-secondary"></i></span>
                                                        <span><?php esc_html_e('Others', 'maneli-car-inquiry'); ?></span>
                                                    </div>
                                                </td>
                                                <td class="text-end"><?php echo esc_html($visitor_stats_format_number($other_os_visits)); ?></td>
                                                <td class="text-end"><?php echo esc_html($visitor_stats_format_number($other_os_percentage, 1)); ?>%</td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div id="os-chart" style="min-height: 300px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Device Types -->
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ri-smartphone-line me-2"></i><?php esc_html_e('Device Types', 'maneli-car-inquiry'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <?php 
                                // Calculate total visits for percentage calculation
                                $total_device_visits = array_sum(array_column($device_stats, 'visit_count'));
                                $top_devices = array_slice($device_stats, 0, 5); // Top 5 device types
                                $other_devices = array_slice($device_stats, 5); // Rest
                                $other_device_visits = array_sum(array_column($other_devices, 'visit_count'));
                                
                                // Group devices and calculate totals
                                $device_groups = [];
                                foreach ($device_stats as $device) {
                                    $device_type_lower = strtolower($device['device_type']);
                                    // Group mobile as smartphone
                                    if ($device_type_lower === 'mobile') {
                                        $key = 'smartphone';
                                    } else {
                                        $key = $device_type_lower;
                                    }
                                    
                                    if (!isset($device_groups[$key])) {
                                        $device_groups[$key] = [
                                            'device_type' => $key,
                                            'visit_count' => 0,
                                            'unique_visitors' => 0,
                                            'device_label' => $device['device_label']
                                        ];
                                    }
                                    $device_groups[$key]['visit_count'] += $device['visit_count'];
                                    $device_groups[$key]['unique_visitors'] += $device['unique_visitors'];
                                }
                                
                                // Sort by visit count
                                usort($device_groups, function($a, $b) {
                                    return $b['visit_count'] - $a['visit_count'];
                                });
                                
                                $top_device_groups = array_slice($device_groups, 0, 5);
                                $other_device_groups = array_slice($device_groups, 5);
                                $other_device_group_visits = array_sum(array_column($other_device_groups, 'visit_count'));
                                $total_device_group_visits = array_sum(array_column($device_groups, 'visit_count'));
                                ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th><?php esc_html_e('Device Type', 'maneli-car-inquiry'); ?></th>
                                                <th class="text-end"><?php esc_html_e('Visits', 'maneli-car-inquiry'); ?></th>
                                                <th class="text-end"><?php esc_html_e('Percentage', 'maneli-car-inquiry'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($top_device_groups as $device): 
                                                $percentage = $total_device_group_visits > 0 ? round(($device['visit_count'] / $total_device_group_visits) * 100, 1) : 0;
                                                $device_icon = Maneli_Visitor_Statistics::get_device_type_icon($device['device_type']);
                                                $device_label = Maneli_Visitor_Statistics::translate_device_type($device['device_type']);
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span><?php echo $device_icon; ?></span>
                                                        <span><?php echo esc_html($device_label); ?></span>
                                                    </div>
                                                </td>
                                                <td class="text-end"><?php echo esc_html($visitor_stats_format_number($device['visit_count'])); ?></td>
                                                <td class="text-end"><?php echo esc_html($visitor_stats_format_number($percentage, 1)); ?>%</td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php if ($other_device_group_visits > 0): 
                                                $other_device_group_percentage = $total_device_group_visits > 0 ? round(($other_device_group_visits / $total_device_group_visits) * 100, 1) : 0;
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span><i class="ri-more-line fs-18 text-secondary"></i></span>
                                                        <span><?php esc_html_e('Others', 'maneli-car-inquiry'); ?></span>
                                                    </div>
                                                </td>
                                                <td class="text-end"><?php echo esc_html($visitor_stats_format_number($other_device_group_visits)); ?></td>
                                                <td class="text-end"><?php echo esc_html($visitor_stats_format_number($other_device_group_percentage, 1)); ?>%</td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div id="device-types-chart" style="min-height: 300px;"></div>
                            </div>
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
                                    <?php foreach ($search_engine_stats as $engine): 
                                        $engine_icon = Maneli_Visitor_Statistics::get_search_engine_icon($engine->search_engine);
                                        $engine_name = ucfirst($engine->search_engine);
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span><?php echo $engine_icon; ?></span>
                                                <span><?php echo esc_html($engine_name); ?></span>
                                            </div>
                                        </td>
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
                                        <th><?php esc_html_e('Unique Visitors', 'maneli-car-inquiry'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($referrer_stats as $referrer): 
                                        $referrer_icon = Maneli_Visitor_Statistics::get_referrer_icon($referrer->referrer_domain);
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span><?php echo $referrer_icon; ?></span>
                                                <div class="text-truncate" style="max-width: 200px;" title="<?php echo esc_attr($referrer->referrer_domain); ?>">
                                                    <?php echo esc_html($referrer->referrer_domain); ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo esc_html($visitor_stats_format_number($referrer->visit_count)); ?></td>
                                        <td><?php echo esc_html($visitor_stats_format_number($referrer->unique_visitors ?? 0)); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- World Map Distribution -->
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ri-map-line me-2"></i><?php esc_html_e('Global Visitor Distribution', 'maneli-car-inquiry'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="world-visitor-map" style="height: 500px;"></div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Most Active Visitors -->
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ri-user-star-line me-2"></i><?php esc_html_e('Most Active Visitors', 'maneli-car-inquiry'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th class="text-end"><?php esc_html_e('Visits', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Visitor Information', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Referrer', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Entry Page', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Exit Page', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Last Visit', 'maneli-car-inquiry'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($most_active_visitors as $visitor):
                                        // Skip if entry or exit page is invalid
                                        $entry_url_lower = strtolower($visitor->entry_page_url ?? '');
                                        $exit_url_lower = strtolower($visitor->exit_page_url ?? '');
                                        $entry_title_lower = strtolower($visitor->entry_page_title ?? '');
                                        $exit_title_lower = strtolower($visitor->exit_page_title ?? '');
                                        
                                        $skip = false;
                                        $static_extensions = ['.js', '.css', '.map', '.jpg', '.jpeg', '.png', '.gif', '.svg', '.woff', '.ttf'];
                                        foreach ($static_extensions as $ext) {
                                            if (strpos($entry_url_lower, $ext) !== false || strpos($exit_url_lower, $ext) !== false) {
                                                $skip = true;
                                                break;
                                            }
                                        }
                                        
                                        if (!$skip && (
                                            strpos($entry_url_lower, '/dashboard/') !== false ||
                                            strpos($entry_url_lower, '/wp-admin/') !== false ||
                                            strpos($entry_url_lower, '/wp-content/') !== false ||
                                            strpos($exit_url_lower, '/dashboard/') !== false ||
                                            strpos($exit_url_lower, '/wp-admin/') !== false ||
                                            strpos($exit_url_lower, '/wp-content/') !== false ||
                                            strpos($entry_title_lower, '404') !== false ||
                                            strpos($entry_title_lower, 'برگه پیدا نشد') !== false ||
                                            strpos($entry_title_lower, 'داشبورد') !== false ||
                                            strpos($exit_title_lower, '404') !== false ||
                                            strpos($exit_title_lower, 'برگه پیدا نشد') !== false ||
                                            strpos($exit_title_lower, 'داشبورد') !== false
                                        )) {
                                            $skip = true;
                                        }
                                        
                                        if ($skip) {
                                            continue;
                                        }
                                        
                                        // Visitor info
                                        $active_country = Maneli_Visitor_Statistics::translate_country_name($visitor->country_code ?? '', $visitor->country ?? '');
                                        $active_flag = Maneli_Visitor_Statistics::get_country_flag_icon($visitor->country_code ?? '', $visitor->country ?? '');
                                        $os_label = Maneli_Visitor_Statistics::translate_os_name($visitor->os ?? '');
                                        $os_icon = Maneli_Visitor_Statistics::get_os_icon($visitor->os ?? '');
                                        $device_type_label = Maneli_Visitor_Statistics::translate_device_type($visitor->device_type ?? '');
                                        $device_type_icon = Maneli_Visitor_Statistics::get_device_type_icon($visitor->device_type ?? '');
                                        $browser_label = Maneli_Visitor_Statistics::translate_browser_name($visitor->browser ?? '');
                                        $browser_icon = Maneli_Visitor_Statistics::get_browser_icon($visitor->browser ?? '');
                                        
                                        // Referrer - filter self-referrals
                                        $referrer_domain = $visitor->referrer_domain ?? '';
                                        $site_domain = parse_url(home_url(), PHP_URL_HOST);
                                        $site_domain_clean = preg_replace('/^www\./', '', $site_domain);
                                        if (!empty($referrer_domain) && strtolower($referrer_domain) === strtolower($site_domain_clean)) {
                                            $referrer_domain = '';
                                            $visitor->referrer_url = '';
                                        }
                                        
                                        $referrer_type = Maneli_Visitor_Statistics::get_referrer_type_label($visitor->referrer_url ?? '', $referrer_domain);
                                        $referrer_display = $referrer_domain;
                                        
                                        // Entry page
                                        $entry_page_title = trim($visitor->entry_page_title ?? '');
                                        if (empty($entry_page_title)) {
                                            $entry_url_parts = parse_url($visitor->entry_page_url ?? '');
                                            $entry_path = isset($entry_url_parts['path']) ? trim($entry_url_parts['path'], '/') : '';
                                            if (empty($entry_path) || $entry_path === '/') {
                                                $entry_page_title = esc_html__('Home Page', 'maneli-car-inquiry');
                                            } else {
                                                $entry_path_parts = explode('/', $entry_path);
                                                $entry_last_part = urldecode(end($entry_path_parts));
                                                $entry_page_title = ucwords(str_replace(['-', '_'], ' ', $entry_last_part));
                                            }
                                        }
                                        
                                        // Exit page
                                        $exit_page_title = trim($visitor->exit_page_title ?? '');
                                        if (empty($exit_page_title)) {
                                            $exit_url_parts = parse_url($visitor->exit_page_url ?? '');
                                            $exit_path = isset($exit_url_parts['path']) ? trim($exit_url_parts['path'], '/') : '';
                                            if (empty($exit_path) || $exit_path === '/') {
                                                $exit_page_title = esc_html__('Home Page', 'maneli-car-inquiry');
                                            } else {
                                                $exit_path_parts = explode('/', $exit_path);
                                                $exit_last_part = urldecode(end($exit_path_parts));
                                                $exit_page_title = ucwords(str_replace(['-', '_'], ' ', $exit_last_part));
                                            }
                                        }
                                        
                                        // Last visit date
                                        $last_visit_date = '';
                                        if (!empty($visitor->last_visit_date)) {
                                            $last_visit_timestamp = strtotime($visitor->last_visit_date);
                                            if ($last_visit_timestamp !== false) {
                                                if ($use_persian_digits && function_exists('maneli_gregorian_to_jalali')) {
                                                    $jalali = maneli_gregorian_to_jalali(
                                                        date('Y', $last_visit_timestamp),
                                                        date('m', $last_visit_timestamp),
                                                        date('d', $last_visit_timestamp),
                                                        'Y/m/d'
                                                    );
                                                    $time = date('g:i a', $last_visit_timestamp);
                                                    $last_visit_date = $visitor_stats_convert_digits($jalali . ', ' . $time);
                                                } else {
                                                    $last_visit_date = date_i18n('F j, g:i a', $last_visit_timestamp);
                                                }
                                            }
                                        }
                                    ?>
                                    <tr>
                                        <td class="text-end">
                                            <strong><?php echo esc_html($visitor_stats_format_number($visitor->visit_count)); ?></strong>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="maneli-emoji-flag" title="<?php echo esc_attr($active_country); ?>" style="cursor: help;"><?php echo esc_html($active_flag); ?></span>
                                                <span title="<?php echo esc_attr($os_label); ?>" style="cursor: help;"><?php echo $os_icon; ?></span>
                                                <span title="<?php echo esc_attr($device_type_label); ?>" style="cursor: help;"><?php echo $device_type_icon; ?></span>
                                                <span title="<?php echo esc_attr($browser_label); ?>" style="cursor: help;"><?php echo $browser_icon; ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column gap-1">
                                                <?php if (!empty($referrer_display)): ?>
                                                    <span class="small text-muted"><?php echo esc_html($referrer_display); ?></span>
                                                <?php endif; ?>
                                                <span class="small"><?php echo esc_html($referrer_type); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php 
                                            $entry_page_url = !empty($visitor->entry_page_url) ? esc_url($visitor->entry_page_url) : '';
                                            if ($entry_page_url): ?>
                                                <a href="<?php echo $entry_page_url; ?>" target="_blank" class="text-decoration-none" title="<?php echo esc_attr($visitor->entry_page_url ?? ''); ?>">
                                                    <div class="text-truncate" style="max-width: 200px;">
                                                        <?php echo esc_html($entry_page_title); ?>
                                                        <i class="ri-external-link-line ms-1 fs-12"></i>
                                                    </div>
                                                </a>
                                            <?php else: ?>
                                                <div class="text-truncate" style="max-width: 200px;" title="<?php echo esc_attr($visitor->entry_page_url ?? ''); ?>">
                                                    <?php echo esc_html($entry_page_title); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $exit_page_url = !empty($visitor->exit_page_url) ? esc_url($visitor->exit_page_url) : '';
                                            if ($exit_page_url): ?>
                                                <a href="<?php echo $exit_page_url; ?>" target="_blank" class="text-decoration-none" title="<?php echo esc_attr($visitor->exit_page_url ?? ''); ?>">
                                                    <div class="text-truncate" style="max-width: 200px;">
                                                        <?php echo esc_html($exit_page_title); ?>
                                                        <i class="ri-external-link-line ms-1 fs-12"></i>
                                                    </div>
                                                </a>
                                            <?php else: ?>
                                                <div class="text-truncate" style="max-width: 200px;" title="<?php echo esc_attr($visitor->exit_page_url ?? ''); ?>">
                                                    <?php echo esc_html($exit_page_title); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="small"><?php echo esc_html($last_visit_date); ?></span>
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

        <!-- Recent Visitors -->
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ri-time-line me-2"></i><?php esc_html_e('Recent Visitors', 'maneli-car-inquiry'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Last Visit', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Visitor Information', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Referrer', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Last Page', 'maneli-car-inquiry'); ?></th>
                                        <th class="text-end"><?php esc_html_e('Visits', 'maneli-car-inquiry'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($recent_visitors, 0, 10) as $visit): 
                                        // Skip if page is invalid
                                        $page_url_lower = strtolower($visit->page_url ?? '');
                                        $page_title_lower = strtolower($visit->page_title ?? '');
                                        
                                        $skip = false;
                                        $static_extensions = ['.js', '.css', '.map', '.jpg', '.jpeg', '.png', '.gif', '.svg', '.woff', '.ttf'];
                                        foreach ($static_extensions as $ext) {
                                            if (strpos($page_url_lower, $ext) !== false) {
                                                $skip = true;
                                                break;
                                            }
                                        }
                                        
                                        if (!$skip && (
                                            strpos($page_url_lower, '/dashboard/') !== false ||
                                            strpos($page_url_lower, '/wp-admin/') !== false ||
                                            strpos($page_url_lower, '/wp-content/') !== false ||
                                            strpos($page_title_lower, '404') !== false ||
                                            strpos($page_title_lower, 'برگه پیدا نشد') !== false ||
                                            strpos($page_title_lower, 'داشبورد') !== false
                                        )) {
                                            $skip = true;
                                        }
                                        
                                        if ($skip) {
                                            continue;
                                        }
                                        
                                        // Last visit date
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
                                        
                                        if ($use_persian_digits && function_exists('maneli_gregorian_to_jalali')) {
                                            $jalali = maneli_gregorian_to_jalali(
                                                date('Y', $visit_timestamp),
                                                date('m', $visit_timestamp),
                                                date('d', $visit_timestamp),
                                                'Y/m/d'
                                            );
                                            $time = date('g:i a', $visit_timestamp);
                                            $visit_datetime_display = $visitor_stats_convert_digits($jalali . ', ' . $time);
                                        } else {
                                            $visit_datetime_display = date_i18n('F j, g:i a', $visit_timestamp);
                                        }
                                        
                                        // Visitor info
                                        $recent_country = Maneli_Visitor_Statistics::translate_country_name($visit->country_code ?? '', $visit->country ?? '');
                                        $recent_flag = Maneli_Visitor_Statistics::get_country_flag_icon($visit->country_code ?? '', $visit->country ?? '');
                                        $recent_os_label = Maneli_Visitor_Statistics::translate_os_name($visit->os ?? '');
                                        $recent_os_icon = Maneli_Visitor_Statistics::get_os_icon($visit->os ?? '');
                                        $recent_device_type_label = Maneli_Visitor_Statistics::translate_device_type($visit->device_type ?? '');
                                        $recent_device_type_icon = Maneli_Visitor_Statistics::get_device_type_icon($visit->device_type ?? '');
                                        $recent_browser_label = Maneli_Visitor_Statistics::translate_browser_name($visit->browser ?? '');
                                        $recent_browser_icon = Maneli_Visitor_Statistics::get_browser_icon($visit->browser ?? '');
                                        
                                        // Referrer - filter self-referrals
                                        $recent_referrer_domain = $visit->referrer_domain ?? '';
                                        $site_domain = parse_url(home_url(), PHP_URL_HOST);
                                        $site_domain_clean = preg_replace('/^www\./', '', $site_domain);
                                        if (!empty($recent_referrer_domain) && strtolower($recent_referrer_domain) === strtolower($site_domain_clean)) {
                                            $recent_referrer_domain = '';
                                            $visit->referrer_url = '';
                                        }
                                        
                                        $recent_referrer_type = Maneli_Visitor_Statistics::get_referrer_type_label($visit->referrer_url ?? '', $recent_referrer_domain);
                                        $recent_referrer_display = $recent_referrer_domain;
                                        
                                        // Last page
                                        $last_page_title = trim($visit->page_title ?? '');
                                        if (empty($last_page_title)) {
                                            $last_url_parts = parse_url($visit->page_url ?? '');
                                            $last_path = isset($last_url_parts['path']) ? trim($last_url_parts['path'], '/') : '';
                                            if (empty($last_path) || $last_path === '/') {
                                                $last_page_title = esc_html__('Home Page', 'maneli-car-inquiry');
                                            } else {
                                                $last_path_parts = explode('/', $last_path);
                                                $last_last_part = urldecode(end($last_path_parts));
                                                $last_page_title = ucwords(str_replace(['-', '_'], ' ', $last_last_part));
                                            }
                                        }
                                        
                                        // Total visits
                                        $total_visits = isset($visit->total_visits) ? (int)$visit->total_visits : 1;
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="small"><?php echo esc_html($visit_datetime_display); ?></span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="maneli-emoji-flag" title="<?php echo esc_attr($recent_country); ?>" style="cursor: help;"><?php echo esc_html($recent_flag); ?></span>
                                                <span title="<?php echo esc_attr($recent_os_label); ?>" style="cursor: help;"><?php echo $recent_os_icon; ?></span>
                                                <span title="<?php echo esc_attr($recent_device_type_label); ?>" style="cursor: help;"><?php echo $recent_device_type_icon; ?></span>
                                                <span title="<?php echo esc_attr($recent_browser_label); ?>" style="cursor: help;"><?php echo $recent_browser_icon; ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column gap-1">
                                                <?php if (!empty($recent_referrer_display)): ?>
                                                    <span class="small text-muted"><?php echo esc_html($recent_referrer_display); ?></span>
                                                <?php endif; ?>
                                                <span class="small"><?php echo esc_html($recent_referrer_type); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php 
                                            $last_page_url = !empty($visit->page_url) ? esc_url($visit->page_url) : '';
                                            if ($last_page_url): ?>
                                                <a href="<?php echo $last_page_url; ?>" target="_blank" class="text-decoration-none" title="<?php echo esc_attr($visit->page_url ?? ''); ?>">
                                                    <div class="text-truncate" style="max-width: 200px;">
                                                        <?php echo esc_html($last_page_title); ?>
                                                        <i class="ri-external-link-line ms-1 fs-12"></i>
                                                    </div>
                                                </a>
                                            <?php else: ?>
                                                <div class="text-truncate" style="max-width: 200px;" title="<?php echo esc_attr($visit->page_url ?? ''); ?>">
                                                    <?php echo esc_html($last_page_title); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <strong><?php echo esc_html($visitor_stats_format_number($total_visits)); ?></strong>
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
                            <i class="ri-user-heart-line me-2"></i><?php esc_html_e('Online Visitors', 'maneli-car-inquiry'); ?>
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
                                        // Skip if page is invalid
                                        $page_url_lower = strtolower($visitor->page_url ?? '');
                                        $page_title_lower = strtolower($visitor->page_title ?? '');
                                        
                                        $skip = false;
                                        $static_extensions = ['.js', '.css', '.map', '.jpg', '.jpeg', '.png', '.gif', '.svg', '.woff', '.ttf'];
                                        foreach ($static_extensions as $ext) {
                                            if (strpos($page_url_lower, $ext) !== false) {
                                                $skip = true;
                                                break;
                                            }
                                        }
                                        
                                        if (!$skip && (
                                            strpos($page_url_lower, '/dashboard/') !== false ||
                                            strpos($page_url_lower, '/wp-admin/') !== false ||
                                            strpos($page_url_lower, '/wp-content/') !== false ||
                                            strpos($page_title_lower, '404') !== false ||
                                            strpos($page_title_lower, 'برگه پیدا نشد') !== false ||
                                            strpos($page_title_lower, 'داشبورد') !== false
                                        )) {
                                            $skip = true;
                                        }
                                        
                                        if ($skip) {
                                            continue;
                                        }
                                        
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
                                            <?php 
                                            $current_page_url = !empty($visitor->page_url) ? esc_url($visitor->page_url) : '';
                                            $current_page_title = !empty($visitor->page_title) ? $visitor->page_title : $visitor->page_url;
                                            if ($current_page_url): ?>
                                                <a href="<?php echo $current_page_url; ?>" target="_blank" class="text-decoration-none" title="<?php echo esc_attr($visitor->page_url); ?>">
                                                    <div class="text-truncate" style="max-width: 200px;">
                                                        <?php echo esc_html($current_page_title); ?>
                                                        <i class="ri-external-link-line ms-1 fs-12"></i>
                                                    </div>
                                                </a>
                                            <?php else: ?>
                                                <div class="text-truncate" style="max-width: 200px;" title="<?php echo esc_attr($visitor->page_url); ?>">
                                                    <?php echo esc_html($current_page_title); ?>
                                                </div>
                                            <?php endif; ?>
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
            // Calculate dates based on period
            function calculatePeriodDates(period) {
                var today = new Date();
                today.setHours(0, 0, 0, 0);
                var startDate, endDate;
                
                switch(period) {
                    case 'today':
                        startDate = new Date(today);
                        endDate = new Date(today);
                        break;
                    case 'yesterday':
                        startDate = new Date(today);
                        startDate.setDate(startDate.getDate() - 1);
                        endDate = new Date(startDate);
                        break;
                    case 'week':
                        startDate = new Date(today);
                        startDate.setDate(startDate.getDate() - 7);
                        endDate = new Date(today);
                        break;
                    case 'month':
                        startDate = new Date(today);
                        startDate.setDate(startDate.getDate() - 30);
                        endDate = new Date(today);
                        break;
                    case '3months':
                        startDate = new Date(today);
                        startDate.setMonth(startDate.getMonth() - 3);
                        endDate = new Date(today);
                        break;
                    case '6months':
                        startDate = new Date(today);
                        startDate.setMonth(startDate.getMonth() - 6);
                        endDate = new Date(today);
                        break;
                    case 'year':
                        startDate = new Date(today);
                        startDate.setFullYear(startDate.getFullYear() - 1);
                        endDate = new Date(today);
                        break;
                    default:
                        return null;
                }
                
                // Format dates
                function formatDate(date, usePersian) {
                    var year = date.getFullYear();
                    var month = String(date.getMonth() + 1).padStart(2, '0');
                    var day = String(date.getDate()).padStart(2, '0');
                    
                    if (usePersian) {
                        // For Persian dates, we'll let the server handle conversion
                        // Just return Gregorian format and let PHP convert
                        return year + '/' + month + '/' + day;
                    } else {
                        return year + '-' + month + '-' + day;
                    }
                }
                
                var usePersian = config.usePersianDatepicker || false;
                return {
                    start: formatDate(startDate, usePersian),
                    end: formatDate(endDate, usePersian)
                };
            }
            
            // Auto-submit form when period changes (except for custom)
            $('#period-filter').on('change', function() {
                var period = $(this).val();
                if (period === 'custom') {
                    $('#custom-start-date, #custom-end-date').removeClass('maneli-initially-hidden').show();
                } else {
                    $('#custom-start-date, #custom-end-date').addClass('maneli-initially-hidden').hide();
                    
                    // Calculate and set dates
                    var dates = calculatePeriodDates(period);
                    if (dates) {
                        $('#start-date-picker').val(dates.start);
                        $('#end-date-picker').val(dates.end);
                    }
                    
                    // Auto-submit form when period is selected (not custom)
                    $('#visitor-stats-filter-form').submit();
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

<!-- JSVectorMap CSS -->
<link rel="stylesheet" href="<?php echo esc_url(MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/jsvectormap/jsvectormap.min.css'); ?>">

<!-- JSVectorMap JS -->
<script src="<?php echo esc_url(MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/jsvectormap/jsvectormap.min.js'); ?>"></script>
<script src="<?php echo esc_url(MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/jsvectormap/maps/world-merc.js'); ?>"></script>

<script type="text/javascript">
(function() {
    'use strict';
    
    // Prepare country data for map
    var countryData = {};
    var maxVisitors = 0;
    var countryStats = <?php 
        $map_data = [];
        $max_visitors = 0;
        foreach ($country_stats as $country) {
            $code = strtoupper(trim($country->country_code ?? '')); // Use uppercase for jsVectorMap
            if (!empty($code) && strlen($code) === 2) { // Only valid 2-letter ISO codes
                $visitors = (int)($country->unique_visitors ?? 0);
                $map_data[$code] = $visitors;
                if ($visitors > $max_visitors) {
                    $max_visitors = $visitors;
                }
            }
        }
        echo json_encode($map_data);
    ?>;
    
    var maxVisitors = <?php echo $max_visitors; ?>;
    
    console.log('Country stats:', countryStats);
    console.log('Max visitors:', maxVisitors);
    
    // Country names and flags for tooltip
    var countryInfo = <?php
        $info_data = [];
        foreach ($country_stats as $country) {
            $code = strtoupper(trim($country->country_code ?? ''));
            $code_lower = strtolower($code);
            if (!empty($code) && strlen($code) === 2) {
                $country_name = Maneli_Visitor_Statistics::translate_country_name($country->country_code, $country->country);
                $flag_icon = Maneli_Visitor_Statistics::get_country_flag_icon($country->country_code, $country->country);
                // Store with multiple key formats for maximum compatibility
                $info_entry = [
                    'name' => $country_name,
                    'flag' => $flag_icon,
                    'visitors' => (int)($country->unique_visitors ?? 0),
                    'visits' => (int)($country->visit_count ?? 0)
                ];
                // Store with uppercase, lowercase, and original code
                $info_data[$code] = $info_entry;
                $info_data[$code_lower] = $info_entry;
                $info_data[$country->country_code ?? ''] = $info_entry; // Original code as stored
            }
        }
        echo json_encode($info_data, JSON_UNESCAPED_UNICODE);
    ?>;
    
    // Initialize world map
    function initWorldMap() {
        if (typeof jsVectorMap === 'undefined') {
            console.error('jsVectorMap is not loaded');
            return;
        }
        
        if (!document.getElementById('world-visitor-map')) {
            return;
        }
        
        console.log('Initializing world map with', Object.keys(countryStats).length, 'countries');
        console.log('Country stats sample:', Object.keys(countryStats).slice(0, 5).map(c => c + ': ' + countryStats[c]));
        console.log('Country info sample:', Object.keys(countryInfo).slice(0, 5));
        
        // Prepare series data for map - this is the correct way to color regions
        var seriesData = {};
        for (var code in countryStats) {
            if (countryStats.hasOwnProperty(code)) {
                seriesData[code] = countryStats[code];
            }
        }
        
        // Store tooltip update function reference for cleanup
        var tooltipUpdateHandler = null;
        
        var map = new jsVectorMap({
            selector: '#world-visitor-map',
            map: 'world_merc',
            zoomOnScroll: true,
            zoomButtons: true,
            series: {
                regions: [{
                    values: seriesData,
                    attribute: 'fill',
                    scale: ['#e0e0e0', '#0066cc'],
                    normalizeFunction: 'polynomial',
                    min: 0,
                    max: maxVisitors || 1
                }]
            },
            regionStyle: {
                initial: {
                    fill: '#e0e0e0',
                    fillOpacity: 1,
                    stroke: '#fff',
                    strokeWidth: 0.5,
                    strokeOpacity: 1
                },
                hover: {
                    fillOpacity: 0.8,
                    cursor: 'pointer'
                },
                selected: {
                    fill: '#ff525d'
                },
                selectedHover: {
                    fillOpacity: 0.8
                }
            },
            labels: {
                regions: {
                    render: function(code) {
                        return false; // Hide region labels
                    }
                }
            },
            onRegionClick: function(event, code) {
                // Optional: handle click events
            }
        });
        
        console.log('Map initialized with', Object.keys(seriesData).length, 'countries');
        
        // Create custom tooltip element
        var tooltip = document.createElement('div');
        tooltip.id = 'world-map-tooltip';
        tooltip.style.cssText = 'position: fixed; background: #ffffff; border: 1px solid #e0e0e0; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); padding: 14px; min-width: 220px; z-index: 10000; pointer-events: none; display: none;';
        document.body.appendChild(tooltip);
        
        // Store tooltip update function reference
        var tooltipUpdateHandler = null;
        
        // Function to show tooltip
        function showTooltip(code, event) {
            var codeUpper = (code || '').toUpperCase();
            var codeLower = (code || '').toLowerCase();
            var codeOriginal = code || '';
            
            // Try multiple ways to get country info
            var info = countryInfo[codeUpper] || countryInfo[codeLower] || countryInfo[codeOriginal] || null;
            
            // Get visitor count from stats
            var visitorCount = countryStats[codeUpper] || countryStats[codeLower] || countryStats[codeOriginal] || 0;
            var visitCount = 0;
            
            // Get country name and flag
            var countryName = codeUpper;
            var flagIcon = '🌍';
            
            if (info) {
                visitorCount = info.visitors || visitorCount;
                visitCount = info.visits || 0;
                countryName = info.name || codeUpper;
                flagIcon = info.flag || '🌍';
            }
            
            // Set tooltip content
            tooltip.innerHTML = '<div style="text-align: center;">' +
                '<div style="font-size: 40px; margin-bottom: 12px; line-height: 1;">' + flagIcon + '</div>' +
                '<div style="font-weight: bold; margin-bottom: 12px; font-size: 17px; color: #1a1a1a;">' + countryName + '</div>' +
                '<div style="font-size: 13px; color: #555; line-height: 2; border-top: 1px solid #e8e8e8; padding-top: 10px; margin-top: 10px;">' +
                '<div style="margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center;">' +
                '<span style="color: #666; font-weight: 500;"><?php esc_html_e('Visitors', 'maneli-car-inquiry'); ?>:</span> ' +
                '<span style="color: #0066cc; font-weight: bold; font-size: 15px;">' + visitorCount.toLocaleString() + '</span>' +
                '</div>' +
                '<div style="display: flex; justify-content: space-between; align-items: center;">' +
                '<span style="color: #666; font-weight: 500;"><?php esc_html_e('Visits', 'maneli-car-inquiry'); ?>:</span> ' +
                '<span style="color: #0066cc; font-weight: bold; font-size: 15px;">' + visitCount.toLocaleString() + '</span>' +
                '</div>' +
                '</div>' +
                '</div>';
            
            // Show tooltip
            tooltip.style.display = 'block';
            
            // Position tooltip
            if (event) {
                tooltip.style.left = (event.clientX + 15) + 'px';
                tooltip.style.top = (event.clientY + 15) + 'px';
            }
            
            // Update position on mouse move
            tooltipUpdateHandler = function(e) {
                if (tooltip && tooltip.style.display !== 'none') {
                    tooltip.style.left = (e.clientX + 15) + 'px';
                    tooltip.style.top = (e.clientY + 15) + 'px';
                }
            };
            
            document.addEventListener('mousemove', tooltipUpdateHandler);
        }
        
        // Function to hide tooltip
        function hideTooltip() {
            tooltip.style.display = 'none';
            if (tooltipUpdateHandler) {
                document.removeEventListener('mousemove', tooltipUpdateHandler);
                tooltipUpdateHandler = null;
            }
        }
        
        // Add event listeners directly to SVG paths after map loads
        setTimeout(function() {
            var mapContainer = document.getElementById('world-visitor-map');
            if (!mapContainer) {
                console.warn('Map container not found');
                return;
            }
            
            var svg = mapContainer.querySelector('svg');
            if (!svg) {
                console.warn('SVG not found in map container');
                return;
            }
            
            // Find all path elements (countries)
            var paths = svg.querySelectorAll('path');
            var addedCount = 0;
            
            // Create a map of all country codes we have data for
            var allCodes = Object.keys(countryStats).concat(Object.keys(countryInfo));
            var uniqueCodes = [...new Set(allCodes.map(c => c.toUpperCase()))];
            
            paths.forEach(function(path) {
                // Try multiple ways to get country code
                var code = null;
                
                // Method 1: Check id attribute
                var pathId = path.getAttribute('id') || '';
                if (pathId) {
                    // Remove common prefixes
                    code = pathId.replace(/^(jvectormap-|region-|country-|jsvm-)/i, '').toUpperCase();
                    if (code.length === 2 && uniqueCodes.indexOf(code) !== -1) {
                        // Valid code found
                    } else {
                        code = null;
                    }
                }
                
                // Method 2: Check data-code attribute
                if (!code) {
                    code = (path.getAttribute('data-code') || '').toUpperCase();
                    if (code.length !== 2 || uniqueCodes.indexOf(code) === -1) {
                        code = null;
                    }
                }
                
                // Method 3: Try to match with known codes by checking path's class or other attributes
                if (!code) {
                    var pathClass = path.getAttribute('class') || '';
                    for (var i = 0; i < uniqueCodes.length; i++) {
                        if (pathClass.indexOf(uniqueCodes[i]) !== -1 || pathId.indexOf(uniqueCodes[i]) !== -1) {
                            code = uniqueCodes[i];
                            break;
                        }
                    }
                }
                
                // If we found a valid code, add event listeners
                if (code && code.length === 2) {
                    // Store code on path element for easy access
                    path.setAttribute('data-country-code', code);
                    
                    // Add mouseenter event
                    path.addEventListener('mouseenter', function(e) {
                        var countryCode = this.getAttribute('data-country-code') || code;
                        showTooltip(countryCode, e);
                    });
                    
                    // Add mouseleave event
                    path.addEventListener('mouseleave', function(e) {
                        hideTooltip();
                    });
                    
                    // Update tooltip position on mousemove
                    path.addEventListener('mousemove', function(e) {
                        if (tooltip && tooltip.style.display !== 'none') {
                            tooltip.style.left = (e.clientX + 15) + 'px';
                            tooltip.style.top = (e.clientY + 15) + 'px';
                        }
                    });
                    
                    addedCount++;
                }
            });
            
            console.log('Added tooltip listeners to', addedCount, 'countries out of', paths.length, 'paths');
            console.log('Available country codes:', uniqueCodes.slice(0, 10), '...');
            
            // Debug: Log first few paths to see their structure
            if (addedCount === 0 && paths.length > 0) {
                console.log('Sample path attributes:', {
                    id: paths[0].getAttribute('id'),
                    class: paths[0].getAttribute('class'),
                    'data-code': paths[0].getAttribute('data-code'),
                    'data-name': paths[0].getAttribute('data-name')
                });
            }
        }, 1500);
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initWorldMap);
    } else {
        initWorldMap();
    }
})();
</script>



