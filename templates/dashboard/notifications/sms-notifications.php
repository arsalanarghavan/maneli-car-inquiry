<?php
/**
 * SMS Notifications Page
 * مدیریت پیامک - صفحه تخصصی SMS
 */

if (!defined('ABSPATH')) {
    exit;
}

// Permission check - Only Admin can access
if (!current_user_can('manage_maneli_inquiries')) {
    wp_redirect(home_url('/dashboard'));
    exit;
}

require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-maneli-database.php';

// Get current language
$handler = Maneli_Dashboard_Handler::instance();
$current_language = method_exists($handler, 'get_preferred_language_slug') 
    ? $handler->get_preferred_language_slug() 
    : 'fa';
$is_persian = ($current_language === 'fa');

// Enqueue Persian Datepicker if Persian
if ($is_persian) {
    if (!wp_style_is('maneli-persian-datepicker', 'enqueued')) {
        wp_enqueue_style(
            'maneli-persian-datepicker',
            MANELI_INQUIRY_PLUGIN_URL . 'assets/css/persianDatepicker-default.css',
            [],
            '1.0.0'
        );
    }
    
    if (!wp_script_is('maneli-persian-datepicker', 'enqueued')) {
        wp_enqueue_script(
            'maneli-persian-datepicker',
            MANELI_INQUIRY_PLUGIN_URL . 'assets/js/persianDatepicker.min.js',
            ['jquery'],
            '1.0.0',
            true
        );
    }
}

$notification_type = 'sms';

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $export_logs = Maneli_Database::get_notification_logs([
        'type' => $notification_type,
        'status' => isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '',
        'date_from' => isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '',
        'date_to' => isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '',
        'search' => isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '',
        'limit' => 10000,
        'offset' => 0,
    ]);
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=sms-notifications-' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    fputcsv($output, [
        esc_html__('ID', 'maneli-car-inquiry'),
        esc_html__('Recipient', 'maneli-car-inquiry'),
        esc_html__('Message', 'maneli-car-inquiry'),
        esc_html__('Status', 'maneli-car-inquiry'),
        esc_html__('Created At', 'maneli-car-inquiry'),
        esc_html__('Sent At', 'maneli-car-inquiry'),
        esc_html__('Error Message', 'maneli-car-inquiry'),
    ]);
    
    foreach ($export_logs as $log) {
        fputcsv($output, [
            $log->id,
            $log->recipient,
            $log->message,
            $log->status,
            $log->created_at,
            $log->sent_at ?: '-',
            $log->error_message ?: '-',
        ]);
    }
    
    fclose($output);
    exit;
}

// Get filters
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 50;

// Convert Jalali dates to Gregorian for database queries (if Persian)
$date_from_gregorian = $date_from;
$date_to_gregorian = $date_to;

if ($is_persian && function_exists('maneli_jalali_to_gregorian')) {
    if (!empty($date_from)) {
        $normalized_date = function_exists('maneli_normalize_jalali_date') ? maneli_normalize_jalali_date($date_from) : $date_from;
        if ($normalized_date && preg_match('/^(\d{4})\/(\d{2})\/(\d{2})$/', $normalized_date, $matches)) {
            $gregorian = maneli_jalali_to_gregorian((int)$matches[1], (int)$matches[2], (int)$matches[3]);
            if ($gregorian) {
                $date_from_gregorian = date('Y-m-d', strtotime($gregorian));
            }
        }
    }
    
    if (!empty($date_to)) {
        $normalized_date = function_exists('maneli_normalize_jalali_date') ? maneli_normalize_jalali_date($date_to) : $date_to;
        if ($normalized_date && preg_match('/^(\d{4})\/(\d{2})\/(\d{2})$/', $normalized_date, $matches)) {
            $gregorian = maneli_jalali_to_gregorian((int)$matches[1], (int)$matches[2], (int)$matches[3]);
            if ($gregorian) {
                $date_to_gregorian = date('Y-m-d', strtotime($gregorian));
            }
        }
    }
}

// Convert default dates to display format
$default_date_from_display = '';
$default_date_to_display = '';

if ($is_persian && function_exists('maneli_gregorian_to_jalali')) {
    $default_from_greg = date('Y-m-d', strtotime('-30 days'));
    $default_to_greg = date('Y-m-d');
    $default_from_parts = explode('-', $default_from_greg);
    $default_to_parts = explode('-', $default_to_greg);
    
    $default_date_from_display = maneli_gregorian_to_jalali(
        (int)$default_from_parts[0],
        (int)$default_from_parts[1],
        (int)$default_from_parts[2],
        'Y/m/d',
        true // Convert digits to Persian
    );
    $default_date_to_display = maneli_gregorian_to_jalali(
        (int)$default_to_parts[0],
        (int)$default_to_parts[1],
        (int)$default_to_parts[2],
        'Y/m/d',
        true // Convert digits to Persian
    );
} else {
    $default_date_from_display = date('Y-m-d', strtotime('-30 days'));
    $default_date_to_display = date('Y-m-d');
}

// Get SMS notification logs (use Gregorian dates for database queries)
$logs = Maneli_Database::get_notification_logs([
    'type' => $notification_type,
    'status' => $status_filter,
    'date_from' => $date_from_gregorian,
    'date_to' => $date_to_gregorian,
    'search' => $search,
    'limit' => $per_page,
    'offset' => ($paged - 1) * $per_page,
]);

$total_logs = Maneli_Database::get_notification_logs_count([
    'type' => $notification_type,
    'status' => $status_filter,
    'date_from' => $date_from_gregorian,
    'date_to' => $date_to_gregorian,
    'search' => $search,
]);

$total_pages = ceil($total_logs / $per_page);

// Get SMS statistics (use Gregorian dates for database queries)
$stats = Maneli_Database::get_notification_stats([
    'type' => $notification_type,
    'date_from' => $date_from_gregorian ?: date('Y-m-d', strtotime('-30 days')),
    'date_to' => $date_to_gregorian ?: date('Y-m-d'),
]);

// Ensure stats object has default values if null
if (!$stats) {
    $stats = (object)[
        'total' => 0,
        'sent' => 0,
        'failed' => 0,
        'pending' => 0
    ];
}

// Get today's stats
$today_stats = Maneli_Database::get_notification_stats([
    'type' => $notification_type,
    'date_from' => date('Y-m-d'),
    'date_to' => date('Y-m-d'),
]);

// Ensure today_stats has default values
if (!$today_stats) {
    $today_stats = (object)[
        'total' => 0,
        'sent' => 0,
        'failed' => 0,
        'pending' => 0
    ];
}

// Get this week's stats
$week_start = date('Y-m-d', strtotime('monday this week'));
$week_stats = Maneli_Database::get_notification_stats([
    'type' => $notification_type,
    'date_from' => $week_start,
    'date_to' => date('Y-m-d'),
]);

// Ensure week_stats has default values
if (!$week_stats) {
    $week_stats = (object)[
        'total' => 0,
        'sent' => 0,
        'failed' => 0,
        'pending' => 0
    ];
}

// Get this month's stats
$month_start = date('Y-m-01');
$month_stats = Maneli_Database::get_notification_stats([
    'type' => $notification_type,
    'date_from' => $month_start,
    'date_to' => date('Y-m-d'),
]);

// Ensure month_stats has default values
if (!$month_stats) {
    $month_stats = (object)[
        'total' => 0,
        'sent' => 0,
        'failed' => 0,
        'pending' => 0
    ];
}

// Get templates
$templates = Maneli_Database::get_notification_templates([
    'type' => $notification_type,
    'limit' => 100,
]);

// Helper function for Persian numbers
if (!function_exists('persian_numbers')) {
    function persian_numbers($str) {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        return str_replace($english, $persian, $str);
    }
}

// Enqueue Persian Datepicker
if (!wp_script_is('maneli-persian-datepicker', 'enqueued')) {
    if (function_exists('maneli_enqueue_persian_datepicker')) {
        maneli_enqueue_persian_datepicker();
    }
}

// Make sure jQuery is enqueued first
if (!wp_script_is('jquery', 'enqueued')) {
    wp_enqueue_script('jquery');
}

// Enqueue Chart.js - load in footer but before our inline script
if (!wp_script_is('chartjs', 'enqueued')) {
    $chartjs_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/libs/chart.js/chart.umd.js';
    if (file_exists($chartjs_path)) {
        wp_enqueue_script('chartjs', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/chart.js/chart.umd.js', ['jquery'], '4.4.0', true);
    } else {
        wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', ['jquery'], '4.4.0', true);
    }
}
?>

<div class="main-content app-content">
    <div class="container-fluid">
        
        <!-- Start::page-header -->
        <div class="d-flex align-items-center justify-content-between page-header-breadcrumb flex-wrap gap-2 mb-4">
            <div>
                <nav>
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item">
                            <a href="<?php echo esc_url(home_url('/dashboard')); ?>"><?php esc_html_e('Dashboard', 'maneli-car-inquiry'); ?></a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="<?php echo esc_url(home_url('/dashboard/notifications-center')); ?>"><?php esc_html_e('Notification Center', 'maneli-car-inquiry'); ?></a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page"><?php esc_html_e('SMS Notifications', 'maneli-car-inquiry'); ?></li>
                    </ol>
                </nav>
                <h1 class="page-title mb-0"><?php esc_html_e('SMS Notifications', 'maneli-car-inquiry'); ?></h1>
            </div>
        </div>
        <!-- End::page-header -->
        
        <!-- Start::row-1 - Statistics Cards -->
        <div class="row">
            <div class="col-xl col-lg-4 col-md-6 col-sm-6 col-6">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-success border-opacity-10 bg-success-transparent rounded-pill">
                                <span class="avatar avatar-md avatar-rounded bg-success svg-white">
                                    <i class="la la-check-circle fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Total Sent', 'maneli-car-inquiry'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center text-success"><?php echo function_exists('maneli_number_format_persian') ? maneli_number_format_persian($stats->sent ?? 0) : number_format($stats->sent ?? 0); ?></h4>
                            <span class="text-success badge bg-success-transparent rounded-pill d-flex align-items-center fs-11">
                                <i class="la la-check-double fs-11"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl col-lg-4 col-md-6 col-sm-6 col-6">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-danger border-opacity-10 bg-danger-transparent rounded-circle">
                                <span class="avatar avatar-md avatar-rounded bg-danger svg-white">
                                    <i class="la la-times-circle fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Failed', 'maneli-car-inquiry'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center text-danger"><?php echo function_exists('maneli_number_format_persian') ? maneli_number_format_persian($stats->failed ?? 0) : number_format($stats->failed ?? 0); ?></h4>
                            <span class="text-danger badge bg-danger-transparent rounded-pill d-flex align-items-center fs-11">
                                <i class="la la-ban fs-11"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl col-lg-4 col-md-6 col-sm-6 col-6">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-warning border-opacity-10 bg-warning-transparent rounded-circle">
                                <span class="avatar avatar-md avatar-rounded bg-warning svg-white">
                                    <i class="la la-clock fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Pending', 'maneli-car-inquiry'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center text-warning"><?php echo function_exists('maneli_number_format_persian') ? maneli_number_format_persian($stats->pending ?? 0) : number_format($stats->pending ?? 0); ?></h4>
                            <span class="text-warning badge bg-warning-transparent rounded-pill d-flex align-items-center fs-11">
                                <i class="la la-hourglass-half fs-11"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl col-lg-4 col-md-6 col-sm-6 col-6">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-primary border-opacity-10 bg-primary-transparent rounded-pill">
                                <span class="avatar avatar-md avatar-rounded bg-primary svg-white">
                                    <i class="la la-list-alt fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Total SMS', 'maneli-car-inquiry'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center"><?php echo function_exists('maneli_number_format_persian') ? maneli_number_format_persian($stats->total ?? 0) : number_format($stats->total ?? 0); ?></h4>
                            <span class="badge bg-primary-transparent rounded-pill fs-11"><?php echo $stats->total > 0 && function_exists('maneli_number_format_persian') ? maneli_number_format_persian(round((($stats->sent ?? 0) / $stats->total) * 100), 1) : '۰'; ?>% <?php esc_html_e('Success', 'maneli-car-inquiry'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl col-lg-4 col-md-6 col-sm-6 col-6">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-info border-opacity-10 bg-info-transparent rounded-circle">
                                <span class="avatar avatar-md avatar-rounded bg-info svg-white">
                                    <i class="la la-calendar-day fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Today', 'maneli-car-inquiry'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center text-info"><?php echo function_exists('maneli_number_format_persian') ? maneli_number_format_persian($today_stats->sent ?? 0) : number_format($today_stats->sent ?? 0); ?></h4>
                            <span class="badge bg-info-transparent rounded-pill fs-11"><?php esc_html_e('SMS', 'maneli-car-inquiry'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl col-lg-4 col-md-6 col-sm-6 col-6">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-secondary border-opacity-10 bg-secondary-transparent rounded-circle">
                                <span class="avatar avatar-md avatar-rounded bg-secondary svg-white">
                                    <i class="la la-calendar-week fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('This Week', 'maneli-car-inquiry'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center"><?php echo function_exists('maneli_number_format_persian') ? maneli_number_format_persian($week_stats->sent ?? 0) : number_format($week_stats->sent ?? 0); ?></h4>
                            <span class="badge bg-secondary-transparent rounded-pill fs-11"><?php esc_html_e('SMS', 'maneli-car-inquiry'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End::row-1 -->
        
        <!-- Filters and Actions -->
        <div class="card custom-card mb-4 maneli-mobile-filter-card" data-maneli-mobile-filter>
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h5
                        class="mb-0 maneli-mobile-filter-toggle d-flex align-items-center gap-2"
                        data-maneli-filter-toggle
                        role="button"
                        tabindex="0"
                        aria-expanded="false"
                    >
                        <?php esc_html_e('Filters', 'maneli-car-inquiry'); ?>
                        <i class="ri-arrow-down-s-line ms-auto maneli-mobile-filter-arrow d-md-none"></i>
                    </h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-success btn-wave" data-bs-toggle="modal" data-bs-target="#sendSingleModal">
                            <i class="ri-message-2-line me-1"></i>
                            <?php esc_html_e('Send Single', 'maneli-car-inquiry'); ?>
                        </button>
                        <button type="button" class="btn btn-primary btn-wave" data-bs-toggle="modal" data-bs-target="#sendBulkModal">
                            <i class="ri-send-plane-line me-1"></i>
                            <?php esc_html_e('Send Bulk', 'maneli-car-inquiry'); ?>
                        </button>
                        <button type="button" class="btn btn-info btn-wave" data-bs-toggle="modal" data-bs-target="#scheduleModal">
                            <i class="ri-time-line me-1"></i>
                            <?php esc_html_e('Schedule', 'maneli-car-inquiry'); ?>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body maneli-mobile-filter-body" data-maneli-filter-body>
                <form method="get" action="<?php echo esc_url(home_url('/dashboard/notifications/sms')); ?>">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label"><?php esc_html_e('Search', 'maneli-car-inquiry'); ?></label>
                            <input type="text" name="search" class="form-control" placeholder="<?php esc_attr_e('Search...', 'maneli-car-inquiry'); ?>" value="<?php echo esc_attr($search); ?>">
                        </div>
                    </div>

                    <div class="row g-3 align-items-end mt-1">
                        <div class="col-6 col-lg-3">
                            <label class="form-label"><?php esc_html_e('Status', 'maneli-car-inquiry'); ?></label>
                            <select name="status" class="form-control form-select">
                                <option value=""><?php esc_html_e('All Statuses', 'maneli-car-inquiry'); ?></option>
                                <option value="sent" <?php selected($status_filter, 'sent'); ?>><?php esc_html_e('Sent', 'maneli-car-inquiry'); ?></option>
                                <option value="failed" <?php selected($status_filter, 'failed'); ?>><?php esc_html_e('Failed', 'maneli-car-inquiry'); ?></option>
                                <option value="pending" <?php selected($status_filter, 'pending'); ?>><?php esc_html_e('Pending', 'maneli-car-inquiry'); ?></option>
                            </select>
                        </div>
                        <div class="col-6 col-lg-3">
                            <label class="form-label"><?php esc_html_e('Date From', 'maneli-car-inquiry'); ?></label>
                            <?php if ($is_persian): ?>
                                <?php 
                                $date_from_display = $date_from ?: $default_date_from_display;
                                if (function_exists('persian_numbers') && !empty($date_from_display)) {
                                    // Convert English digits to Persian if not already Persian
                                    $date_from_display = persian_numbers($date_from_display);
                                }
                                ?>
                                <input type="text" name="date_from" id="date-from-picker" class="form-control maneli-datepicker" value="<?php echo esc_attr($date_from_display); ?>" placeholder="<?php esc_attr_e('YYYY/MM/DD', 'maneli-car-inquiry'); ?>" readonly>
                            <?php else: ?>
                                <input type="date" name="date_from" id="date-from-picker" class="form-control" value="<?php echo esc_attr($date_from ?: $default_date_from_display); ?>">
                            <?php endif; ?>
                        </div>
                        <div class="col-6 col-lg-3">
                            <label class="form-label"><?php esc_html_e('Date To', 'maneli-car-inquiry'); ?></label>
                            <?php if ($is_persian): ?>
                                <?php 
                                $date_to_display = $date_to ?: $default_date_to_display;
                                if (function_exists('persian_numbers') && !empty($date_to_display)) {
                                    // Convert English digits to Persian if not already Persian
                                    $date_to_display = persian_numbers($date_to_display);
                                }
                                ?>
                                <input type="text" name="date_to" id="date-to-picker" class="form-control maneli-datepicker" value="<?php echo esc_attr($date_to_display); ?>" placeholder="<?php esc_attr_e('YYYY/MM/DD', 'maneli-car-inquiry'); ?>" readonly>
                            <?php else: ?>
                                <input type="date" name="date_to" id="date-to-picker" class="form-control" value="<?php echo esc_attr($date_to ?: $default_date_to_display); ?>">
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row g-2 mt-3">
                        <div class="col-6 col-lg-auto">
                            <button type="submit" class="btn btn-primary btn-wave w-100">
                                <i class="ri-search-line me-1"></i>
                                <?php esc_html_e('Filter', 'maneli-car-inquiry'); ?>
                            </button>
                        </div>
                        <div class="col-6 col-lg-auto">
                            <a href="<?php echo esc_url(home_url('/dashboard/notifications/sms')); ?>" class="btn btn-secondary btn-wave w-100">
                                <?php esc_html_e('Reset', 'maneli-car-inquiry'); ?>
                            </a>
                        </div>
                        <div class="col-6 col-lg-auto">
                            <button type="button" class="btn btn-success btn-wave w-100" id="exportBtn">
                                <i class="ri-download-line me-1"></i>
                                <?php esc_html_e('Export', 'maneli-car-inquiry'); ?>
                            </button>
                        </div>
                        <div class="col-6 d-lg-none"></div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Start::row-2 - Charts -->
        <div class="row mb-4">
            <div class="col-xl-6">
                <div class="card custom-card">
                    <div class="card-header">
                        <h5 class="mb-0"><?php esc_html_e('SMS Timeline', 'maneli-car-inquiry'); ?></h5>
                    </div>
                    <div class="card-body">
                        <canvas id="timelineChart" height="300"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card custom-card">
                    <div class="card-header">
                        <h5 class="mb-0"><?php esc_html_e('Status Distribution', 'maneli-car-inquiry'); ?></h5>
                    </div>
                    <div class="card-body">
                        <canvas id="statusChart" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <!-- End::row-2 -->
        
        <!-- Logs Table -->
        <div class="card custom-card mb-4">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><?php esc_html_e('SMS Logs', 'maneli-car-inquiry'); ?></h5>
                    <span class="badge bg-primary-transparent"><?php echo function_exists('maneli_number_format_persian') ? maneli_number_format_persian($total_logs) : number_format($total_logs); ?> <?php esc_html_e('Total', 'maneli-car-inquiry'); ?></span>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('ID', 'maneli-car-inquiry'); ?></th>
                                <th><?php esc_html_e('Recipient', 'maneli-car-inquiry'); ?></th>
                                <th><?php esc_html_e('Message', 'maneli-car-inquiry'); ?></th>
                                <th><?php esc_html_e('Status', 'maneli-car-inquiry'); ?></th>
                                <th><?php esc_html_e('Created', 'maneli-car-inquiry'); ?></th>
                                <th><?php esc_html_e('Sent', 'maneli-car-inquiry'); ?></th>
                                <th><?php esc_html_e('Actions', 'maneli-car-inquiry'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <p class="text-muted mb-0"><?php esc_html_e('No SMS notifications found.', 'maneli-car-inquiry'); ?></p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?php echo esc_html($log->id); ?></td>
                                        <td><?php echo esc_html($log->recipient); ?></td>
                                        <td>
                                            <span class="text-truncate d-inline-block" style="max-width: 200px;" title="<?php echo esc_attr($log->message); ?>">
                                                <?php echo esc_html(wp_trim_words($log->message, 10)); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $status_classes = [
                                                'sent' => 'success',
                                                'failed' => 'danger',
                                                'pending' => 'warning',
                                            ];
                                            $status_class = $status_classes[$log->status] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo esc_attr($status_class); ?>-transparent">
                                                <?php
                                                $status_translations = [
                                                    'sent' => __('Sent', 'maneli-car-inquiry'),
                                                    'failed' => __('Failed', 'maneli-car-inquiry'),
                                                    'pending' => __('Pending', 'maneli-car-inquiry'),
                                                ];
                                                echo esc_html($status_translations[$log->status] ?? ucfirst($log->status));
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $created_timestamp = strtotime($log->created_at);
                                            if ($is_persian && function_exists('maneli_gregorian_to_jalali')) {
                                                $created_date = date('Y-m-d H:i:s', $created_timestamp);
                                                $created_parts = explode(' ', $created_date);
                                                $date_parts = explode('-', $created_parts[0]);
                                                $time_parts = explode(':', $created_parts[1]);
                                                $jalali_date = maneli_gregorian_to_jalali(
                                                    (int)$date_parts[0],
                                                    (int)$date_parts[1],
                                                    (int)$date_parts[2],
                                                    'Y/m/d',
                                                    true // Convert digits to Persian
                                                );
                                                $display_time = $time_parts[0] . ':' . $time_parts[1];
                                                if (function_exists('persian_numbers')) {
                                                    $display_time = persian_numbers($display_time);
                                                }
                                                echo esc_html($jalali_date . ' ' . $display_time);
                                            } else {
                                                echo esc_html(date_i18n('Y/m/d H:i', $created_timestamp));
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($log->sent_at): ?>
                                                <?php
                                                $sent_timestamp = strtotime($log->sent_at);
                                                if ($is_persian && function_exists('maneli_gregorian_to_jalali')) {
                                                    $sent_date = date('Y-m-d H:i:s', $sent_timestamp);
                                                    $sent_parts = explode(' ', $sent_date);
                                                    $date_parts = explode('-', $sent_parts[0]);
                                                    $time_parts = explode(':', $sent_parts[1]);
                                                    $jalali_date = maneli_gregorian_to_jalali(
                                                        (int)$date_parts[0],
                                                        (int)$date_parts[1],
                                                        (int)$date_parts[2],
                                                        'Y/m/d',
                                                        true // Convert digits to Persian
                                                    );
                                                    $display_time = $time_parts[0] . ':' . $time_parts[1];
                                                    if (function_exists('persian_numbers')) {
                                                        $display_time = persian_numbers($display_time);
                                                    }
                                                    echo esc_html($jalali_date . ' ' . $display_time);
                                                } else {
                                                    echo esc_html(date_i18n('Y/m/d H:i', $sent_timestamp));
                                                }
                                                ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($log->status === 'failed'): ?>
                                                <button type="button" class="btn btn-sm btn-primary btn-wave retry-notification" data-log-id="<?php echo esc_attr($log->id); ?>">
                                                    <i class="ri-refresh-line"></i>
                                                    <?php esc_html_e('Retry', 'maneli-car-inquiry'); ?>
                                                </button>
                                            <?php endif; ?>
                                            <?php if (!empty($log->error_message)): ?>
                                                <button type="button" class="btn btn-sm btn-info btn-wave" data-bs-toggle="tooltip" title="<?php echo esc_attr($log->error_message); ?>">
                                                    <i class="ri-error-warning-line"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-sm btn-secondary btn-wave view-details" data-log-id="<?php echo esc_attr($log->id); ?>" data-message="<?php echo esc_attr($log->message); ?>" data-recipient="<?php echo esc_attr($log->recipient); ?>" data-status="<?php echo esc_attr($log->status); ?>" data-error="<?php echo esc_attr($log->error_message ?? ''); ?>">
                                                <i class="ri-eye-line"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mt-4">
                            <?php
                            $base_url = add_query_arg([
                                'status' => $status_filter,
                                'date_from' => $date_from,
                                'date_to' => $date_to,
                                'search' => $search,
                            ], home_url('/dashboard/notifications/sms'));
                            
                            if ($paged > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo esc_url(add_query_arg('paged', $paged - 1, $base_url)); ?>">
                                        <?php esc_html_e('Previous', 'maneli-car-inquiry'); ?>
                                    </a>
                                </li>
                            <?php endif;
                            
                            for ($i = 1; $i <= $total_pages; $i++):
                                if ($i == 1 || $i == $total_pages || ($i >= $paged - 2 && $i <= $paged + 2)): ?>
                                    <li class="page-item <?php echo ($i == $paged) ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo esc_url(add_query_arg('paged', $i, $base_url)); ?>">
                                            <?php echo persian_numbers($i); ?>
                                        </a>
                                    </li>
                                <?php elseif ($i == $paged - 3 || $i == $paged + 3): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif;
                            endfor;
                            
                            if ($paged < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo esc_url(add_query_arg('paged', $paged + 1, $base_url)); ?>">
                                        <?php esc_html_e('Next', 'maneli-car-inquiry'); ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Templates Section -->
        <div class="card custom-card">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><?php esc_html_e('Message Templates', 'maneli-car-inquiry'); ?></h5>
                    <button type="button" class="btn btn-primary btn-wave" data-bs-toggle="modal" data-bs-target="#templateModal" id="addTemplateBtn">
                        <i class="ri-add-line me-1"></i>
                        <?php esc_html_e('Add Template', 'maneli-car-inquiry'); ?>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Name', 'maneli-car-inquiry'); ?></th>
                                <th><?php esc_html_e('Message', 'maneli-car-inquiry'); ?></th>
                                <th><?php esc_html_e('Status', 'maneli-car-inquiry'); ?></th>
                                <th><?php esc_html_e('Created', 'maneli-car-inquiry'); ?></th>
                                <th><?php esc_html_e('Actions', 'maneli-car-inquiry'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($templates)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <p class="text-muted mb-0"><?php esc_html_e('No templates found.', 'maneli-car-inquiry'); ?></p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($templates as $template): ?>
                                    <tr>
                                        <td><?php echo esc_html($template->name); ?></td>
                                        <td>
                                            <span class="text-truncate d-inline-block" style="max-width: 300px;" title="<?php echo esc_attr($template->message); ?>">
                                                <?php echo esc_html(wp_trim_words($template->message, 15)); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $template->is_active ? 'success' : 'secondary'; ?>-transparent">
                                                <?php echo $template->is_active ? esc_html__('Active', 'maneli-car-inquiry') : esc_html__('Inactive', 'maneli-car-inquiry'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $template_timestamp = strtotime($template->created_at);
                                            if ($is_persian && function_exists('maneli_gregorian_to_jalali')) {
                                                $template_date = date('Y-m-d', $template_timestamp);
                                                $date_parts = explode('-', $template_date);
                                                $jalali_date = maneli_gregorian_to_jalali(
                                                    (int)$date_parts[0],
                                                    (int)$date_parts[1],
                                                    (int)$date_parts[2],
                                                    'Y/m/d',
                                                    true // Convert digits to Persian
                                                );
                                                echo esc_html($jalali_date);
                                            } else {
                                                echo esc_html(date_i18n('Y/m/d', $template_timestamp));
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary btn-wave use-template" data-template-id="<?php echo esc_attr($template->id); ?>" data-message="<?php echo esc_attr($template->message); ?>">
                                                <i class="ri-file-copy-line"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-info btn-wave edit-template" data-template-id="<?php echo esc_attr($template->id); ?>" data-name="<?php echo esc_attr($template->name); ?>" data-message="<?php echo esc_attr($template->message); ?>" data-active="<?php echo esc_attr($template->is_active); ?>">
                                                <i class="ri-edit-line"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-warning btn-wave duplicate-template" data-template-id="<?php echo esc_attr($template->id); ?>">
                                                <i class="ri-file-copy-2-line"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger btn-wave delete-template" data-template-id="<?php echo esc_attr($template->id); ?>">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
    </div>
</div>

<!-- Send Single Modal -->
<div class="modal fade" id="sendSingleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php esc_html_e('Send Single SMS', 'maneli-car-inquiry'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="sendSingleForm">
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Template', 'maneli-car-inquiry'); ?></label>
                        <select name="template_id" id="singleTemplateSelect" class="form-select">
                            <option value=""><?php esc_html_e('Select Template (Optional)', 'maneli-car-inquiry'); ?></option>
                            <?php foreach ($templates as $template): ?>
                                <?php if ($template->is_active): ?>
                                    <option value="<?php echo esc_attr($template->id); ?>" data-message="<?php echo esc_attr($template->message); ?>">
                                        <?php echo esc_html($template->name); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Recipient', 'maneli-car-inquiry'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="recipient" id="singleRecipient" class="form-control" required placeholder="<?php esc_attr_e('Phone number', 'maneli-car-inquiry'); ?>">
                        <small class="form-text text-muted"><?php esc_html_e('Enter phone number or user ID', 'maneli-car-inquiry'); ?></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Message', 'maneli-car-inquiry'); ?> <span class="text-danger">*</span></label>
                        <textarea name="message" id="singleMessage" class="form-control" rows="5" required placeholder="<?php esc_attr_e('Enter your message...', 'maneli-car-inquiry'); ?>"></textarea>
                        <small class="form-text text-muted"><?php esc_html_e('Available variables: {customer_name}, {car_name}, {date}', 'maneli-car-inquiry'); ?></small>
                    </div>
                    <div class="mb-3">
                        <button type="button" class="btn btn-info btn-wave" id="previewSingleBtn">
                            <i class="ri-eye-line me-1"></i>
                            <?php esc_html_e('Preview', 'maneli-car-inquiry'); ?>
                        </button>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'maneli-car-inquiry'); ?></button>
                <button type="button" class="btn btn-success" id="sendSingleBtn">
                    <i class="ri-send-plane-line me-1"></i>
                    <?php esc_html_e('Send SMS', 'maneli-car-inquiry'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Send Bulk Modal -->
<div class="modal fade" id="sendBulkModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php esc_html_e('Send Bulk SMS', 'maneli-car-inquiry'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="bulkNotificationForm">
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Template', 'maneli-car-inquiry'); ?></label>
                        <select name="template_id" id="bulkTemplateSelect" class="form-select">
                            <option value=""><?php esc_html_e('Select Template (Optional)', 'maneli-car-inquiry'); ?></option>
                            <?php foreach ($templates as $template): ?>
                                <?php if ($template->is_active): ?>
                                    <option value="<?php echo esc_attr($template->id); ?>" data-message="<?php echo esc_attr($template->message); ?>">
                                        <?php echo esc_html($template->name); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Recipients', 'maneli-car-inquiry'); ?></label>
                        <select name="recipient_type" id="bulkRecipientType" class="form-select mb-2">
                            <option value="all"><?php esc_html_e('All Users', 'maneli-car-inquiry'); ?></option>
                            <option value="customers"><?php esc_html_e('Customers Only', 'maneli-car-inquiry'); ?></option>
                            <option value="experts"><?php esc_html_e('Experts Only', 'maneli-car-inquiry'); ?></option>
                            <option value="admins"><?php esc_html_e('Admins Only', 'maneli-car-inquiry'); ?></option>
                        </select>
                        <textarea name="custom_recipients" id="bulkCustomRecipients" class="form-control" rows="3" placeholder="<?php esc_attr_e('Or enter custom phone numbers (one per line)', 'maneli-car-inquiry'); ?>"></textarea>
                        <small class="form-text text-muted" id="recipientCount"><?php esc_html_e('Select recipients type or enter custom phone numbers', 'maneli-car-inquiry'); ?></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Message', 'maneli-car-inquiry'); ?> <span class="text-danger">*</span></label>
                        <textarea name="message" id="bulkMessage" class="form-control" rows="5" required></textarea>
                        <small class="form-text text-muted"><?php esc_html_e('Available variables: {customer_name}, {car_name}, {date}', 'maneli-car-inquiry'); ?></small>
                    </div>
                </form>
                <div id="bulkProgress" class="progress mb-3" style="display: none;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'maneli-car-inquiry'); ?></button>
                <button type="button" class="btn btn-primary" id="sendBulkBtn">
                    <i class="ri-send-plane-line me-1"></i>
                    <?php esc_html_e('Send Bulk', 'maneli-car-inquiry'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Schedule Modal -->
<div class="modal fade" id="scheduleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php esc_html_e('Schedule SMS', 'maneli-car-inquiry'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="scheduleNotificationForm">
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Template', 'maneli-car-inquiry'); ?></label>
                        <select name="template_id" id="scheduleTemplateSelect" class="form-select">
                            <option value=""><?php esc_html_e('Select Template (Optional)', 'maneli-car-inquiry'); ?></option>
                            <?php foreach ($templates as $template): ?>
                                <?php if ($template->is_active): ?>
                                    <option value="<?php echo esc_attr($template->id); ?>" data-message="<?php echo esc_attr($template->message); ?>">
                                        <?php echo esc_html($template->name); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Recipient', 'maneli-car-inquiry'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="recipient" id="scheduleRecipient" class="form-control" required placeholder="<?php esc_attr_e('Phone number', 'maneli-car-inquiry'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Message', 'maneli-car-inquiry'); ?> <span class="text-danger">*</span></label>
                        <textarea name="message" id="scheduleMessage" class="form-control" rows="5" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Scheduled Date', 'maneli-car-inquiry'); ?> <span class="text-danger">*</span></label>
                        <?php if ($is_persian): ?>
                            <input type="text" name="scheduled_date" id="schedule-date-picker" class="form-control maneli-datepicker mb-2" placeholder="<?php esc_attr_e('YYYY/MM/DD', 'maneli-car-inquiry'); ?>" required readonly>
                        <?php else: ?>
                            <input type="date" name="scheduled_date" id="schedule-date-picker" class="form-control mb-2" required>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Scheduled Time', 'maneli-car-inquiry'); ?> <span class="text-danger">*</span></label>
                        <input type="time" name="scheduled_time" id="scheduleTime" class="form-control" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'maneli-car-inquiry'); ?></button>
                <button type="button" class="btn btn-primary" id="scheduleBtn">
                    <i class="ri-time-line me-1"></i>
                    <?php esc_html_e('Schedule', 'maneli-car-inquiry'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Template Modal -->
<div class="modal fade" id="templateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="templateModalTitle"><?php esc_html_e('Add Template', 'maneli-car-inquiry'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="templateForm">
                    <input type="hidden" name="template_id" id="templateId">
                    <input type="hidden" name="type" value="sms">
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Name', 'maneli-car-inquiry'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="templateName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Message', 'maneli-car-inquiry'); ?> <span class="text-danger">*</span></label>
                        <textarea name="message" id="templateMessage" class="form-control" rows="5" required></textarea>
                        <small class="form-text text-muted"><?php esc_html_e('Available variables: {customer_name}, {car_name}, {date}, {phone}, {inquiry_id}', 'maneli-car-inquiry'); ?></small>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="templateActive" value="1" checked>
                            <label class="form-check-label" for="templateActive">
                                <?php esc_html_e('Active', 'maneli-car-inquiry'); ?>
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Preview', 'maneli-car-inquiry'); ?></label>
                        <div class="card bg-light">
                            <div class="card-body">
                                <p id="templatePreview" class="mb-0 text-muted"><?php esc_html_e('Preview will appear here...', 'maneli-car-inquiry'); ?></p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'maneli-car-inquiry'); ?></button>
                <button type="button" class="btn btn-primary" id="saveTemplateBtn">
                    <?php esc_html_e('Save Template', 'maneli-car-inquiry'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- View Details Modal -->
<div class="modal fade" id="viewDetailsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php esc_html_e('Notification Details', 'maneli-car-inquiry'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <strong><?php esc_html_e('Recipient:', 'maneli-car-inquiry'); ?></strong>
                    <p id="detailRecipient" class="mb-0"></p>
                </div>
                <div class="mb-3">
                    <strong><?php esc_html_e('Message:', 'maneli-car-inquiry'); ?></strong>
                    <p id="detailMessage" class="mb-0"></p>
                </div>
                <div class="mb-3">
                    <strong><?php esc_html_e('Status:', 'maneli-car-inquiry'); ?></strong>
                    <p id="detailStatus" class="mb-0"></p>
                </div>
                <div class="mb-3" id="detailErrorSection" style="display: none;">
                    <strong><?php esc_html_e('Error:', 'maneli-car-inquiry'); ?></strong>
                    <p id="detailError" class="mb-0 text-danger"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e('Close', 'maneli-car-inquiry'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
// Load Chart.js if not already loaded
(function() {
    if (typeof Chart === 'undefined') {
        var script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';
        script.async = true;
        document.head.appendChild(script);
    }
})();

// Define maneli_ajax globally before any other scripts
var maneli_ajax = {
    url: '<?php echo esc_js(admin_url('admin-ajax.php')); ?>',
    nonce: '<?php echo esc_js(wp_create_nonce('maneli-ajax-nonce')); ?>'
};

(function() {
    // Wait for jQuery and Chart.js to load
    function initSMSNotifications() {
        // Check if jQuery is available
        if (typeof jQuery === 'undefined') {
            setTimeout(initSMSNotifications, 100);
            return;
        }
        
        var $ = jQuery;
        
        // Wait for document ready
        $(document).ready(function() {
            // Get current language from PHP
            var isPersian = <?php echo $is_persian ? 'true' : 'false'; ?>;
            
            // Wait for persianDatepicker to load
            var datepickerRetryCount = 0;
            var maxDatepickerRetries = 50;
            
            function initDatepickers() {
                // Initialize Datepicker based on language
                if (isPersian) {
                    // Check if persianDatepicker jQuery plugin is available
                    if (typeof $.fn.persianDatepicker !== 'undefined') {
                        // Persian Datepicker for Persian language
                        $('#date-from-picker').persianDatepicker({
                            format: 'YYYY/MM/DD',
                            calendarType: 'persian',
                            observer: true,
                            altField: '#date-from-picker',
                            altFormat: 'YYYY/MM/DD',
                            timePicker: false,
                            persianNumbers: true
                        });
                        
                        $('#date-to-picker').persianDatepicker({
                            format: 'YYYY/MM/DD',
                            calendarType: 'persian',
                            observer: true,
                            altField: '#date-to-picker',
                            altFormat: 'YYYY/MM/DD',
                            timePicker: false,
                            persianNumbers: true
                        });
                        
                        if ($('#schedule-date-picker').length) {
                            $('#schedule-date-picker').persianDatepicker({
                                format: 'YYYY/MM/DD',
                                calendarType: 'persian',
                                observer: true,
                                altField: '#schedule-date-picker',
                                altFormat: 'YYYY/MM/DD',
                                timePicker: false,
                                persianNumbers: true
                            });
                        }
                        console.log('Persian Datepickers initialized successfully');
                    } else {
                        datepickerRetryCount++;
                        if (datepickerRetryCount < maxDatepickerRetries) {
                            setTimeout(initDatepickers, 100);
                            return;
                        } else {
                            console.error('Persian Datepicker failed to load after', maxDatepickerRetries, 'retries. Make sure maneli-persian-datepicker script is enqueued.');
                        }
                    }
                } else if (!isPersian) {
                    // For English, use native HTML5 date picker or flatpickr if available
                    if (typeof flatpickr !== 'undefined') {
                        flatpickr('#date-from-picker', {
                            dateFormat: 'Y-m-d',
                            locale: 'en'
                        });
                        
                        flatpickr('#date-to-picker', {
                            dateFormat: 'Y-m-d',
                            locale: 'en'
                        });
                        
                        if ($('#schedule-date-picker').length) {
                            flatpickr('#schedule-date-picker', {
                                dateFormat: 'Y-m-d',
                                locale: 'en'
                            });
                        }
                    }
                    // If flatpickr is not available, native HTML5 date input will be used
                }
            }
            
            // Initialize datepickers
            initDatepickers();
            
            // Wait for Chart.js to load
            var chartRetryCount = 0;
            var maxChartRetries = 50; // 5 seconds max wait time
            
            function initCharts() {
                if (typeof Chart === 'undefined') {
                    chartRetryCount++;
                    if (chartRetryCount >= maxChartRetries) {
                        console.error('Chart.js failed to load after', maxChartRetries, 'retries');
                        return;
                    }
                    setTimeout(initCharts, 100);
                    return;
                }
                
                // Initialize Charts
                // maneli_ajax is already defined at the top of the script
                if (typeof maneli_ajax === 'undefined') {
                    console.error('maneli_ajax is not defined!');
                    return;
                }
                
                // Timeline Chart
                var timelineCtx = document.getElementById('timelineChart');
                if (timelineCtx) {
                    $.ajax({
                        url: maneli_ajax.url,
                        type: 'POST',
                        data: {
                            action: 'maneli_get_notification_timeline',
                            nonce: maneli_ajax.nonce,
                            type: 'sms',
                            date_from: '<?php 
                                $timeline_date_from = $date_from_gregorian ?: date('Y-m-d', strtotime('-30 days'));
                                echo esc_js($timeline_date_from); 
                            ?>',
                            date_to: '<?php 
                                $timeline_date_to = $date_to_gregorian ?: date('Y-m-d');
                                echo esc_js($timeline_date_to); 
                            ?>'
                        },
                        success: function(response) {
                            if (response.success && response.data) {
                                var labels = response.data.labels || [];
                                var data = response.data.data || [];
                                
                                // If no data, show empty chart with message
                                if (labels.length === 0 || data.length === 0) {
                                    labels = ['<?php echo esc_js(__('No data', 'maneli-car-inquiry')); ?>'];
                                    data = [0];
                                }
                                
                                var timelineChart = new Chart(timelineCtx, {
                                    type: 'line',
                                    data: {
                                        labels: labels,
                                        datasets: [{
                                            label: '<?php echo esc_js(__('Sent SMS', 'maneli-car-inquiry')); ?>',
                                            data: data,
                                            borderColor: 'rgba(54, 162, 235, 1)',
                                            backgroundColor: 'rgba(54, 162, 235, 0.1)',
                                            tension: 0.4,
                                            fill: true
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: {
                                            legend: {
                                                position: 'bottom'
                                            },
                                            tooltip: {
                                                enabled: true
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
                            } else {
                                console.error('Failed to load timeline data:', response);
                                // Show empty chart
                                var timelineChart = new Chart(timelineCtx, {
                                    type: 'line',
                                    data: {
                                        labels: ['<?php echo esc_js(__('No data', 'maneli-car-inquiry')); ?>'],
                                        datasets: [{
                                            label: '<?php echo esc_js(__('Sent SMS', 'maneli-car-inquiry')); ?>',
                                            data: [0],
                                            borderColor: 'rgba(54, 162, 235, 1)',
                                            backgroundColor: 'rgba(54, 162, 235, 0.1)',
                                            tension: 0.4,
                                            fill: true
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: {
                                            legend: {
                                                position: 'bottom'
                                            }
                                        },
                                        scales: {
                                            y: {
                                                beginAtZero: true
                                            }
                                        }
                                    }
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            var errorMsg = 'Unknown error';
                            if (xhr.responseText) {
                                try {
                                    var response = JSON.parse(xhr.responseText);
                                    if (response.data && response.data.message) {
                                        errorMsg = response.data.message;
                                    } else if (response.message) {
                                        errorMsg = response.message;
                                    }
                                } catch (e) {
                                    errorMsg = xhr.responseText.substring(0, 100);
                                }
                            }
                            console.error('AJAX error loading timeline:', xhr.status, error, errorMsg);
                            
                            // If 403, it might be a nonce issue - try refreshing
                            if (xhr.status === 403) {
                                console.warn('403 Forbidden - possible nonce or permission issue. Please refresh the page.');
                            }
                            
                            // Show empty chart on error
                            var timelineChart = new Chart(timelineCtx, {
                                type: 'line',
                                data: {
                                    labels: ['<?php echo esc_js(__('Error loading data', 'maneli-car-inquiry')); ?>'],
                                    datasets: [{
                                        label: '<?php echo esc_js(__('Sent SMS', 'maneli-car-inquiry')); ?>',
                                        data: [0],
                                        borderColor: 'rgba(220, 53, 69, 1)',
                                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                                        tension: 0.4,
                                        fill: true
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            position: 'bottom'
                                        }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true
                                        }
                                    }
                                }
                            });
                        }
                    });
                }
                
                // Status Chart
                var statusCtx = document.getElementById('statusChart');
                if (statusCtx) {
                    var sentCount = <?php echo intval($stats->sent ?? 0); ?>;
                    var failedCount = <?php echo intval($stats->failed ?? 0); ?>;
                    var pendingCount = <?php echo intval($stats->pending ?? 0); ?>;
                    
                    // If all counts are zero, show a message
                    if (sentCount === 0 && failedCount === 0 && pendingCount === 0) {
                        var statusChart = new Chart(statusCtx, {
                            type: 'pie',
                            data: {
                                labels: ['<?php echo esc_js(__('No data', 'maneli-car-inquiry')); ?>'],
                                datasets: [{
                                    data: [1],
                                    backgroundColor: ['rgba(108, 117, 125, 0.8)'],
                                    borderColor: ['rgba(108, 117, 125, 1)'],
                                    borderWidth: 2
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'bottom'
                                    },
                                    tooltip: {
                                        enabled: true
                                    }
                                }
                            }
                        });
                    } else {
                        var statusChart = new Chart(statusCtx, {
                            type: 'pie',
                            data: {
                                labels: [
                                    '<?php echo esc_js(__('Sent', 'maneli-car-inquiry')); ?>',
                                    '<?php echo esc_js(__('Failed', 'maneli-car-inquiry')); ?>',
                                    '<?php echo esc_js(__('Pending', 'maneli-car-inquiry')); ?>'
                                ],
                                datasets: [{
                                    data: [sentCount, failedCount, pendingCount],
                                    backgroundColor: [
                                        'rgba(40, 167, 69, 0.8)',
                                        'rgba(220, 53, 69, 0.8)',
                                        'rgba(255, 193, 7, 0.8)'
                                    ],
                                    borderColor: [
                                        'rgba(40, 167, 69, 1)',
                                        'rgba(220, 53, 69, 1)',
                                        'rgba(255, 193, 7, 1)'
                                    ],
                                    borderWidth: 2
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'bottom'
                                    },
                                    tooltip: {
                                        enabled: true,
                                        callbacks: {
                                            label: function(context) {
                                                var label = context.label || '';
                                                var value = context.parsed || 0;
                                                var total = context.dataset.data.reduce(function(a, b) { return a + b; }, 0);
                                                var percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                                return label + ': ' + value + ' (' + percentage + '%)';
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }
                }
            }
            
            initCharts();
        });
    }
    
    // Start initialization
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSMSNotifications);
    } else {
        initSMSNotifications();
    }
})();

// Export to CSV and other handlers - using vanilla JS or wait for jQuery
(function() {
    function setupEventHandlers() {
        if (typeof jQuery === 'undefined') {
            setTimeout(setupEventHandlers, 100);
            return;
        }
        
        var $ = jQuery;
        
        $(document).ready(function() {
            // Export to CSV
            $('#exportBtn').on('click', function() {
                var params = new URLSearchParams(window.location.search);
                params.set('export', 'csv');
                window.location.href = '<?php echo esc_url(home_url('/dashboard/notifications/sms')); ?>?' + params.toString();
            });
    
    // Template select handlers
    $('#singleTemplateSelect, #bulkTemplateSelect, #scheduleTemplateSelect').on('change', function() {
        var selected = $(this).find('option:selected');
        var message = selected.data('message');
        if (message) {
            var modalId = $(this).closest('.modal').attr('id');
            if (modalId === 'sendSingleModal') {
                $('#singleMessage').val(message);
            } else if (modalId === 'sendBulkModal') {
                $('#bulkMessage').val(message);
            } else if (modalId === 'scheduleModal') {
                $('#scheduleMessage').val(message);
            }
        }
    });
    
    // Use template button
    $('.use-template').on('click', function() {
        var message = $(this).data('message');
        $('#sendSingleModal').modal('show');
        $('#singleMessage').val(message);
    });
    
    // Send Single SMS
    $('#sendSingleBtn').on('click', function() {
        var recipient = $('#singleRecipient').val();
        var message = $('#singleMessage').val();
        
        if (!recipient || !message) {
            alert('<?php echo esc_js(__('Recipient and message are required.', 'maneli-car-inquiry')); ?>');
            return;
        }
        
        var btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> ' + '<?php echo esc_js(__('Sending...', 'maneli-car-inquiry')); ?>');
        
        $.ajax({
            url: maneli_ajax.url,
            type: 'POST',
            data: {
                action: 'maneli_send_single_notification',
                nonce: maneli_ajax.nonce,
                type: 'sms',
                recipient: recipient,
                message: message
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php echo esc_js(__('SMS sent successfully!', 'maneli-car-inquiry')); ?>');
                    $('#sendSingleModal').modal('hide');
                    location.reload();
                } else {
                    alert('<?php echo esc_js(__('Error:', 'maneli-car-inquiry')); ?> ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Server error. Please try again.', 'maneli-car-inquiry')); ?>');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="ri-send-plane-line me-1"></i> ' + '<?php echo esc_js(__('Send SMS', 'maneli-car-inquiry')); ?>');
            }
        });
    });
    
    // Send Bulk SMS
    $('#sendBulkBtn').on('click', function() {
        var recipientType = $('#bulkRecipientType').val();
        var customRecipients = $('#bulkCustomRecipients').val();
        var message = $('#bulkMessage').val();
        
        if (!message) {
            alert('<?php echo esc_js(__('Message is required.', 'maneli-car-inquiry')); ?>');
            return;
        }
        
        var btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        $('#bulkProgress').show();
        
        $.ajax({
            url: maneli_ajax.url,
            type: 'POST',
            data: {
                action: 'maneli_send_bulk_notification_by_type',
                nonce: maneli_ajax.nonce,
                type: 'sms',
                recipient_type: recipientType,
                custom_recipients: customRecipients,
                message: message
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php echo esc_js(__('Bulk SMS sent successfully!', 'maneli-car-inquiry')); ?>');
                    $('#sendBulkModal').modal('hide');
                    location.reload();
                } else {
                    alert('<?php echo esc_js(__('Error:', 'maneli-car-inquiry')); ?> ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Server error. Please try again.', 'maneli-car-inquiry')); ?>');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="ri-send-plane-line me-1"></i> ' + '<?php echo esc_js(__('Send Bulk', 'maneli-car-inquiry')); ?>');
                $('#bulkProgress').hide();
            }
        });
    });
    
    // Schedule SMS
    $('#scheduleBtn').on('click', function() {
        var recipient = $('#scheduleRecipient').val();
        var message = $('#scheduleMessage').val();
        var scheduledDate = $('#schedule-date-picker').val();
        var scheduledTime = $('#scheduleTime').val();
        
        if (!recipient || !message || !scheduledDate || !scheduledTime) {
            alert('<?php echo esc_js(__('All fields are required.', 'maneli-car-inquiry')); ?>');
            return;
        }
        
        // Convert Jalali date to Gregorian if Persian
        var isPersian = <?php echo $is_persian ? 'true' : 'false'; ?>;
        var scheduledAt = scheduledDate + ' ' + scheduledTime;
        
        if (isPersian && scheduledDate.match(/^\d{4}\/\d{2}\/\d{2}$/)) {
            // Convert Persian digits to English
            scheduledDate = scheduledDate.replace(/[۰-۹]/g, function(d) {
                return String.fromCharCode(d.charCodeAt(0) - 1728);
            });
            
            // Convert Jalali to Gregorian - send to server for conversion
            // The server will handle the conversion in the AJAX handler
            // For now, we'll send the date as-is and let the backend convert it
        } else if (!isPersian) {
            // For English, ensure date is in Y-m-d format
            if (scheduledDate.match(/^\d{4}-\d{2}-\d{2}$/)) {
                // Already in correct format
            } else {
                // Try to convert if in different format
                var dateParts = scheduledDate.split('/');
                if (dateParts.length === 3) {
                    scheduledDate = dateParts[0] + '-' + dateParts[1] + '-' + dateParts[2];
                }
            }
        }
        
        scheduledAt = scheduledDate + ' ' + scheduledTime;
        
        var btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        
        $.ajax({
            url: maneli_ajax.url,
            type: 'POST',
            data: {
                action: 'maneli_schedule_notification_by_type',
                nonce: maneli_ajax.nonce,
                type: 'sms',
                recipient: recipient,
                message: message,
                scheduled_at: scheduledAt,
                is_jalali: isPersian ? '1' : '0'
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php echo esc_js(__('SMS scheduled successfully!', 'maneli-car-inquiry')); ?>');
                    $('#scheduleModal').modal('hide');
                    location.reload();
                } else {
                    alert('<?php echo esc_js(__('Error:', 'maneli-car-inquiry')); ?> ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Server error. Please try again.', 'maneli-car-inquiry')); ?>');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="ri-time-line me-1"></i> ' + '<?php echo esc_js(__('Schedule', 'maneli-car-inquiry')); ?>');
            }
        });
    });
    
    // Retry failed notification
    $('.retry-notification').on('click', function() {
        var logId = $(this).data('log-id');
        var btn = $(this);
        
        if (!confirm('<?php echo esc_js(__('Retry sending this SMS?', 'maneli-car-inquiry')); ?>')) {
            return;
        }
        
        btn.prop('disabled', true);
        
        $.ajax({
            url: maneli_ajax.url,
            type: 'POST',
            data: {
                action: 'maneli_retry_notification',
                nonce: maneli_ajax.nonce,
                log_id: logId
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php echo esc_js(__('SMS retried successfully!', 'maneli-car-inquiry')); ?>');
                    location.reload();
                } else {
                    alert('<?php echo esc_js(__('Error:', 'maneli-car-inquiry')); ?> ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Server error. Please try again.', 'maneli-car-inquiry')); ?>');
            },
            complete: function() {
                btn.prop('disabled', false);
            }
        });
    });
    
    // View details
    $('.view-details').on('click', function() {
        $('#detailRecipient').text($(this).data('recipient'));
        $('#detailMessage').text($(this).data('message'));
        $('#detailStatus').html('<span class="badge bg-' + ($(this).data('status') === 'sent' ? 'success' : ($(this).data('status') === 'failed' ? 'danger' : 'warning')) + '-transparent">' + $(this).data('status') + '</span>');
        if ($(this).data('error')) {
            $('#detailErrorSection').show();
            $('#detailError').text($(this).data('error'));
        } else {
            $('#detailErrorSection').hide();
        }
        $('#viewDetailsModal').modal('show');
    });
    
    // Template management
    $('#addTemplateBtn').on('click', function() {
        $('#templateModalTitle').text('<?php echo esc_js(__('Add Template', 'maneli-car-inquiry')); ?>');
        $('#templateForm')[0].reset();
        $('#templateId').val('');
        $('#templateActive').prop('checked', true);
        $('#templatePreview').text('<?php echo esc_js(__('Preview will appear here...', 'maneli-car-inquiry')); ?>');
    });
    
    $('.edit-template').on('click', function() {
        $('#templateModalTitle').text('<?php echo esc_js(__('Edit Template', 'maneli-car-inquiry')); ?>');
        $('#templateId').val($(this).data('template-id'));
        $('#templateName').val($(this).data('name'));
        $('#templateMessage').val($(this).data('message'));
        $('#templateActive').prop('checked', $(this).data('active') == 1);
        $('#templatePreview').text($(this).data('message'));
        $('#templateModal').modal('show');
    });
    
    $('#templateMessage').on('input', function() {
        var message = $(this).val();
        if (message) {
            $('#templatePreview').text(message);
        } else {
            $('#templatePreview').text('<?php echo esc_js(__('Preview will appear here...', 'maneli-car-inquiry')); ?>');
        }
    });
    
    $('#saveTemplateBtn').on('click', function() {
        var formData = $('#templateForm').serializeArray();
        var data = {};
        $.each(formData, function(i, field) {
            data[field.name] = field.value;
        });
        
        var btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        
        var action = data.template_id ? 'maneli_update_notification_template' : 'maneli_create_notification_template';
        
        // Merge data with action and nonce
        var ajaxData = Object.assign({}, data, {
            action: action,
            nonce: maneli_ajax.nonce
        });
        
        $.ajax({
            url: maneli_ajax.url,
            type: 'POST',
            data: ajaxData,
            success: function(response) {
                if (response.success) {
                    alert('<?php echo esc_js(__('Template saved successfully!', 'maneli-car-inquiry')); ?>');
                    $('#templateModal').modal('hide');
                    location.reload();
                } else {
                    alert('<?php echo esc_js(__('Error:', 'maneli-car-inquiry')); ?> ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Server error. Please try again.', 'maneli-car-inquiry')); ?>');
            },
            complete: function() {
                btn.prop('disabled', false).html('<?php echo esc_js(__('Save Template', 'maneli-car-inquiry')); ?>');
            }
        });
    });
    
    $('.duplicate-template').on('click', function() {
        if (!confirm('<?php echo esc_js(__('Duplicate this template?', 'maneli-car-inquiry')); ?>')) {
            return;
        }
        
        $.ajax({
            url: maneli_ajax.url,
            type: 'POST',
            data: {
                action: 'maneli_duplicate_notification_template',
                nonce: maneli_ajax.nonce,
                template_id: $(this).data('template-id')
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php echo esc_js(__('Template duplicated successfully!', 'maneli-car-inquiry')); ?>');
                    location.reload();
                } else {
                    alert('<?php echo esc_js(__('Error:', 'maneli-car-inquiry')); ?> ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Server error. Please try again.', 'maneli-car-inquiry')); ?>');
            }
        });
    });
    
    $('.delete-template').on('click', function() {
        if (!confirm('<?php echo esc_js(__('Delete this template?', 'maneli-car-inquiry')); ?>')) {
            return;
        }
        
        $.ajax({
            url: maneli_ajax.url,
            type: 'POST',
            data: {
                action: 'maneli_delete_notification_template',
                nonce: maneli_ajax.nonce,
                template_id: $(this).data('template-id')
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php echo esc_js(__('Template deleted successfully!', 'maneli-car-inquiry')); ?>');
                    location.reload();
                } else {
                    alert('<?php echo esc_js(__('Error:', 'maneli-car-inquiry')); ?> ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Server error. Please try again.', 'maneli-car-inquiry')); ?>');
            }
        });
    });
        });
    }
    
    // Start event handlers setup
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupEventHandlers);
    } else {
        setupEventHandlers();
    }
})();
</script>
