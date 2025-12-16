<?php
/**
 * Email Notifications Page
 * مدیریت ایمیل - صفحه تخصصی Email
 */

if (!defined('ABSPATH')) {
    exit;
}

// Permission check - Only Admin can access
if (!current_user_can('manage_autopuzzle_inquiries')) {
    wp_redirect(home_url('/dashboard'));
    exit;
}

require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-autopuzzle-database.php';

$notification_type = 'email';

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $export_logs = Autopuzzle_Database::get_notification_logs([
        'type' => $notification_type,
        'status' => isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '',
        'date_from' => isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '',
        'date_to' => isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '',
        'search' => isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '',
        'limit' => 10000,
        'offset' => 0,
    ]);
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=email-notifications-' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    fputcsv($output, [
        esc_html__('ID', 'autopuzzle'),
        esc_html__('Recipient', 'autopuzzle'),
        esc_html__('Subject', 'autopuzzle'),
        esc_html__('Message', 'autopuzzle'),
        esc_html__('Status', 'autopuzzle'),
        esc_html__('Created At', 'autopuzzle'),
        esc_html__('Sent At', 'autopuzzle'),
        esc_html__('Error Message', 'autopuzzle'),
    ]);
    
    foreach ($export_logs as $log) {
        // Extract subject from message if it exists
        $subject = '';
        $message_body = $log->message;
        if (strpos($log->message, 'Subject:') === 0) {
            $parts = explode("\n", $log->message, 2);
            $subject = trim(str_replace('Subject:', '', $parts[0]));
            $message_body = isset($parts[1]) ? $parts[1] : '';
        }
        fputcsv($output, [
            $log->id,
            $log->recipient,
            $subject,
            $message_body,
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

// Get Email notification logs
$logs = Autopuzzle_Database::get_notification_logs([
    'type' => $notification_type,
    'status' => $status_filter,
    'date_from' => $date_from,
    'date_to' => $date_to,
    'search' => $search,
    'limit' => $per_page,
    'offset' => ($paged - 1) * $per_page,
]);

$total_logs = Autopuzzle_Database::get_notification_logs_count([
    'type' => $notification_type,
    'status' => $status_filter,
    'date_from' => $date_from,
    'date_to' => $date_to,
    'search' => $search,
]);

$total_pages = ceil($total_logs / $per_page);

// Get Email statistics
$stats = Autopuzzle_Database::get_notification_stats([
    'type' => $notification_type,
    'date_from' => $date_from ?: date('Y-m-d', strtotime('-30 days')),
    'date_to' => $date_to ?: date('Y-m-d'),
]);

// Get today's stats
$today_stats = Autopuzzle_Database::get_notification_stats([
    'type' => $notification_type,
    'date_from' => date('Y-m-d'),
    'date_to' => date('Y-m-d'),
]);

// Get this week's stats
$week_start = date('Y-m-d', strtotime('monday this week'));
$week_stats = Autopuzzle_Database::get_notification_stats([
    'type' => $notification_type,
    'date_from' => $week_start,
    'date_to' => date('Y-m-d'),
]);

// Get this month's stats
$month_start = date('Y-m-01');
$month_stats = Autopuzzle_Database::get_notification_stats([
    'type' => $notification_type,
    'date_from' => $month_start,
    'date_to' => date('Y-m-d'),
]);

// Get templates
$templates = Autopuzzle_Database::get_notification_templates([
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
if (!wp_script_is('autopuzzle-persian-datepicker', 'enqueued')) {
    if (function_exists('autopuzzle_enqueue_persian_datepicker')) {
        autopuzzle_enqueue_persian_datepicker();
    }
}

// Enqueue Chart.js
if (!wp_script_is('chartjs', 'enqueued')) {
    $chartjs_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/libs/chart.js/chart.umd.js';
    if (file_exists($chartjs_path)) {
        wp_enqueue_script('chartjs', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/chart.js/chart.umd.js', ['jquery'], '4.4.0', false);
    } else {
        wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', ['jquery'], '4.4.0', false);
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
                            <a href="<?php echo esc_url(home_url('/dashboard')); ?>"><?php esc_html_e('Dashboard', 'autopuzzle'); ?></a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="<?php echo esc_url(home_url('/dashboard/notifications-center')); ?>"><?php esc_html_e('Notification Center', 'autopuzzle'); ?></a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page"><?php esc_html_e('Email Notifications', 'autopuzzle'); ?></li>
                    </ol>
                </nav>
                <h1 class="page-title mb-0"><?php esc_html_e('Email Notifications', 'autopuzzle'); ?></h1>
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
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Total Sent', 'autopuzzle'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center text-success"><?php echo function_exists('autopuzzle_number_format_persian') ? autopuzzle_number_format_persian($stats->sent ?? 0) : number_format($stats->sent ?? 0); ?></h4>
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
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Failed', 'autopuzzle'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center text-danger"><?php echo function_exists('autopuzzle_number_format_persian') ? autopuzzle_number_format_persian($stats->failed ?? 0) : number_format($stats->failed ?? 0); ?></h4>
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
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Pending', 'autopuzzle'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center text-warning"><?php echo function_exists('autopuzzle_number_format_persian') ? autopuzzle_number_format_persian($stats->pending ?? 0) : number_format($stats->pending ?? 0); ?></h4>
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
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Total Emails', 'autopuzzle'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center"><?php echo function_exists('autopuzzle_number_format_persian') ? autopuzzle_number_format_persian($stats->total ?? 0) : number_format($stats->total ?? 0); ?></h4>
                            <span class="badge bg-primary-transparent rounded-pill fs-11"><?php echo $stats->total > 0 && function_exists('autopuzzle_number_format_persian') ? autopuzzle_number_format_persian(round((($stats->sent ?? 0) / $stats->total) * 100), 1) : '۰'; ?>% <?php esc_html_e('Success', 'autopuzzle'); ?></span>
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
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Today', 'autopuzzle'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center text-info"><?php echo function_exists('autopuzzle_number_format_persian') ? autopuzzle_number_format_persian($today_stats->sent ?? 0) : number_format($today_stats->sent ?? 0); ?></h4>
                            <span class="badge bg-info-transparent rounded-pill fs-11"><?php esc_html_e('Emails', 'autopuzzle'); ?></span>
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
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('This Week', 'autopuzzle'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center"><?php echo function_exists('autopuzzle_number_format_persian') ? autopuzzle_number_format_persian($week_stats->sent ?? 0) : number_format($week_stats->sent ?? 0); ?></h4>
                            <span class="badge bg-secondary-transparent rounded-pill fs-11"><?php esc_html_e('Emails', 'autopuzzle'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End::row-1 -->
        
        <!-- Filters and Actions -->
        <div class="card custom-card mb-4 autopuzzle-mobile-filter-card" data-autopuzzle-mobile-filter>
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h5
                        class="mb-0 autopuzzle-mobile-filter-toggle d-flex align-items-center gap-2"
                        data-autopuzzle-filter-toggle
                        role="button"
                        tabindex="0"
                        aria-expanded="false"
                    >
                        <?php esc_html_e('Filters', 'autopuzzle'); ?>
                        <i class="ri-arrow-down-s-line ms-auto autopuzzle-mobile-filter-arrow d-md-none"></i>
                    </h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-success btn-wave" data-bs-toggle="modal" data-bs-target="#sendSingleModal">
                            <i class="ri-mail-line me-1"></i>
                            <?php esc_html_e('Send Single', 'autopuzzle'); ?>
                        </button>
                        <button type="button" class="btn btn-primary btn-wave" data-bs-toggle="modal" data-bs-target="#sendBulkModal">
                            <i class="ri-send-plane-line me-1"></i>
                            <?php esc_html_e('Send Bulk', 'autopuzzle'); ?>
                        </button>
                        <button type="button" class="btn btn-info btn-wave" data-bs-toggle="modal" data-bs-target="#scheduleModal">
                            <i class="ri-time-line me-1"></i>
                            <?php esc_html_e('Schedule', 'autopuzzle'); ?>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body autopuzzle-mobile-filter-body" data-autopuzzle-filter-body>
                <form method="get" action="<?php echo esc_url(home_url('/dashboard/notifications/email')); ?>">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label"><?php esc_html_e('Search', 'autopuzzle'); ?></label>
                            <input type="text" name="search" class="form-control" placeholder="<?php esc_attr_e('Search...', 'autopuzzle'); ?>" value="<?php echo esc_attr($search); ?>">
                        </div>
                    </div>

                    <div class="row g-3 align-items-end mt-1">
                        <div class="col-6 col-lg-3">
                            <label class="form-label"><?php esc_html_e('Status', 'autopuzzle'); ?></label>
                            <select name="status" class="form-control form-select">
                                <option value=""><?php esc_html_e('All Statuses', 'autopuzzle'); ?></option>
                                <option value="sent" <?php selected($status_filter, 'sent'); ?>><?php esc_html_e('Sent', 'autopuzzle'); ?></option>
                                <option value="failed" <?php selected($status_filter, 'failed'); ?>><?php esc_html_e('Failed', 'autopuzzle'); ?></option>
                                <option value="pending" <?php selected($status_filter, 'pending'); ?>><?php esc_html_e('Pending', 'autopuzzle'); ?></option>
                            </select>
                        </div>
                        <div class="col-6 col-lg-3">
                            <label class="form-label"><?php esc_html_e('Date From', 'autopuzzle'); ?></label>
                            <input type="text" name="date_from" id="date-from-picker" class="form-control autopuzzle-datepicker" value="<?php echo esc_attr($date_from); ?>" placeholder="<?php esc_attr_e('YYYY/MM/DD', 'autopuzzle'); ?>" readonly>
                        </div>
                        <div class="col-6 col-lg-3">
                            <label class="form-label"><?php esc_html_e('Date To', 'autopuzzle'); ?></label>
                            <input type="text" name="date_to" id="date-to-picker" class="form-control autopuzzle-datepicker" value="<?php echo esc_attr($date_to); ?>" placeholder="<?php esc_attr_e('YYYY/MM/DD', 'autopuzzle'); ?>" readonly>
                        </div>
                    </div>

                    <div class="row g-2 mt-3">
                        <div class="col-6 col-lg-auto">
                            <button type="submit" class="btn btn-primary btn-wave w-100">
                                <i class="ri-search-line me-1"></i>
                                <?php esc_html_e('Filter', 'autopuzzle'); ?>
                            </button>
                        </div>
                        <div class="col-6 col-lg-auto">
                            <a href="<?php echo esc_url(home_url('/dashboard/notifications/email')); ?>" class="btn btn-secondary btn-wave w-100">
                                <?php esc_html_e('Reset', 'autopuzzle'); ?>
                            </a>
                        </div>
                        <div class="col-6 col-lg-auto">
                            <button type="button" class="btn btn-success btn-wave w-100" id="exportBtn">
                                <i class="ri-download-line me-1"></i>
                                <?php esc_html_e('Export', 'autopuzzle'); ?>
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
                        <h5 class="mb-0"><?php esc_html_e('Email Timeline', 'autopuzzle'); ?></h5>
                    </div>
                    <div class="card-body">
                        <canvas id="timelineChart" height="300"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card custom-card">
                    <div class="card-header">
                        <h5 class="mb-0"><?php esc_html_e('Status Distribution', 'autopuzzle'); ?></h5>
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
                    <h5 class="mb-0"><?php esc_html_e('Email Logs', 'autopuzzle'); ?></h5>
                    <span class="badge bg-primary-transparent"><?php echo function_exists('autopuzzle_number_format_persian') ? autopuzzle_number_format_persian($total_logs) : number_format($total_logs); ?> <?php esc_html_e('Total', 'autopuzzle'); ?></span>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('ID', 'autopuzzle'); ?></th>
                                <th><?php esc_html_e('Recipient', 'autopuzzle'); ?></th>
                                <th><?php esc_html_e('Subject', 'autopuzzle'); ?></th>
                                <th><?php esc_html_e('Message', 'autopuzzle'); ?></th>
                                <th><?php esc_html_e('Status', 'autopuzzle'); ?></th>
                                <th><?php esc_html_e('Created', 'autopuzzle'); ?></th>
                                <th><?php esc_html_e('Sent', 'autopuzzle'); ?></th>
                                <th><?php esc_html_e('Actions', 'autopuzzle'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <p class="text-muted mb-0"><?php esc_html_e('No email notifications found.', 'autopuzzle'); ?></p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?php echo esc_html($log->id); ?></td>
                                        <td><?php echo esc_html($log->recipient); ?></td>
                                        <td>
                                            <?php
                                            // Extract subject from message if it exists (format: Subject: ...\nMessage)
                                            $subject = '';
                                            if (strpos($log->message, 'Subject:') === 0) {
                                                $parts = explode("\n", $log->message, 2);
                                                $subject = str_replace('Subject:', '', $parts[0]);
                                                $subject = trim($subject);
                                            }
                                            ?>
                                            <span class="text-truncate d-inline-block" style="max-width: 150px;" title="<?php echo esc_attr($subject ?: '-'); ?>">
                                                <?php echo esc_html($subject ? wp_trim_words($subject, 5) : '-'); ?>
                                            </span>
                                        </td>
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
                                                <?php echo esc_html(ucfirst($log->status)); ?>
                                            </span>
                                        </td>
                                        <td><?php echo esc_html(date_i18n('Y/m/d H:i', strtotime($log->created_at))); ?></td>
                                        <td>
                                            <?php if ($log->sent_at): ?>
                                                <?php echo esc_html(date_i18n('Y/m/d H:i', strtotime($log->sent_at))); ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($log->status === 'failed'): ?>
                                                <button type="button" class="btn btn-sm btn-primary btn-wave retry-notification" data-log-id="<?php echo esc_attr($log->id); ?>">
                                                    <i class="ri-refresh-line"></i>
                                                    <?php esc_html_e('Retry', 'autopuzzle'); ?>
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
                            ], home_url('/dashboard/notifications/email'));
                            
                            if ($paged > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo esc_url(add_query_arg('paged', $paged - 1, $base_url)); ?>">
                                        <?php esc_html_e('Previous', 'autopuzzle'); ?>
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
                                        <?php esc_html_e('Next', 'autopuzzle'); ?>
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
                    <h5 class="mb-0"><?php esc_html_e('Message Templates', 'autopuzzle'); ?></h5>
                    <button type="button" class="btn btn-primary btn-wave" data-bs-toggle="modal" data-bs-target="#templateModal" id="addTemplateBtn">
                        <i class="ri-add-line me-1"></i>
                        <?php esc_html_e('Add Template', 'autopuzzle'); ?>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Name', 'autopuzzle'); ?></th>
                                <th><?php esc_html_e('Message', 'autopuzzle'); ?></th>
                                <th><?php esc_html_e('Status', 'autopuzzle'); ?></th>
                                <th><?php esc_html_e('Created', 'autopuzzle'); ?></th>
                                <th><?php esc_html_e('Actions', 'autopuzzle'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($templates)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <p class="text-muted mb-0"><?php esc_html_e('No templates found.', 'autopuzzle'); ?></p>
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
                                                <?php echo $template->is_active ? esc_html__('Active', 'autopuzzle') : esc_html__('Inactive', 'autopuzzle'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo esc_html(date_i18n('Y/m/d', strtotime($template->created_at))); ?></td>
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
                <h5 class="modal-title"><?php esc_html_e('Send Single Email', 'autopuzzle'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="sendSingleForm">
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Template', 'autopuzzle'); ?></label>
                        <select name="template_id" id="singleTemplateSelect" class="form-select">
                            <option value=""><?php esc_html_e('Select Template (Optional)', 'autopuzzle'); ?></option>
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
                        <label class="form-label"><?php esc_html_e('Recipient', 'autopuzzle'); ?> <span class="text-danger">*</span></label>
                        <input type="email" name="recipient" id="singleRecipient" class="form-control" required placeholder="<?php esc_attr_e('Email address', 'autopuzzle'); ?>">
                        <small class="form-text text-muted"><?php esc_html_e('Enter email address or user ID', 'autopuzzle'); ?></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Subject', 'autopuzzle'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="subject" id="singleSubject" class="form-control" required placeholder="<?php esc_attr_e('Email subject', 'autopuzzle'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Message', 'autopuzzle'); ?> <span class="text-danger">*</span></label>
                        <textarea name="message" id="singleMessage" class="form-control" rows="5" required placeholder="<?php esc_attr_e('Enter your message...', 'autopuzzle'); ?>"></textarea>
                        <small class="form-text text-muted"><?php esc_html_e('Available variables: {customer_name}, {car_name}, {date}', 'autopuzzle'); ?></small>
                    </div>
                    <div class="mb-3">
                        <button type="button" class="btn btn-info btn-wave" id="previewSingleBtn">
                            <i class="ri-eye-line me-1"></i>
                            <?php esc_html_e('Preview', 'autopuzzle'); ?>
                        </button>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'autopuzzle'); ?></button>
                <button type="button" class="btn btn-success" id="sendSingleBtn">
                    <i class="ri-send-plane-line me-1"></i>
                    <?php esc_html_e('Send Email', 'autopuzzle'); ?>
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
                <h5 class="modal-title"><?php esc_html_e('Send Bulk Email', 'autopuzzle'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="bulkNotificationForm">
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Template', 'autopuzzle'); ?></label>
                        <select name="template_id" id="bulkTemplateSelect" class="form-select">
                            <option value=""><?php esc_html_e('Select Template (Optional)', 'autopuzzle'); ?></option>
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
                        <label class="form-label"><?php esc_html_e('Recipients', 'autopuzzle'); ?></label>
                        <select name="recipient_type" id="bulkRecipientType" class="form-select mb-2">
                            <option value="all"><?php esc_html_e('All Users', 'autopuzzle'); ?></option>
                            <option value="customers"><?php esc_html_e('Customers Only', 'autopuzzle'); ?></option>
                            <option value="experts"><?php esc_html_e('Experts Only', 'autopuzzle'); ?></option>
                            <option value="admins"><?php esc_html_e('Admins Only', 'autopuzzle'); ?></option>
                        </select>
                        <textarea name="custom_recipients" id="bulkCustomRecipients" class="form-control" rows="3" placeholder="<?php esc_attr_e('Or enter custom email addresses (one per line)', 'autopuzzle'); ?>"></textarea>
                        <small class="form-text text-muted" id="recipientCount"><?php esc_html_e('Select recipients type or enter custom email addresses', 'autopuzzle'); ?></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Subject', 'autopuzzle'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="subject" id="bulkSubject" class="form-control" required placeholder="<?php esc_attr_e('Email subject', 'autopuzzle'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Message', 'autopuzzle'); ?> <span class="text-danger">*</span></label>
                        <textarea name="message" id="bulkMessage" class="form-control" rows="5" required></textarea>
                        <small class="form-text text-muted"><?php esc_html_e('Available variables: {customer_name}, {car_name}, {date}', 'autopuzzle'); ?></small>
                    </div>
                </form>
                <div id="bulkProgress" class="progress mb-3" style="display: none;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'autopuzzle'); ?></button>
                <button type="button" class="btn btn-primary" id="sendBulkBtn">
                    <i class="ri-send-plane-line me-1"></i>
                    <?php esc_html_e('Send Bulk', 'autopuzzle'); ?>
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
                <h5 class="modal-title"><?php esc_html_e('Schedule Email', 'autopuzzle'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="scheduleNotificationForm">
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Template', 'autopuzzle'); ?></label>
                        <select name="template_id" id="scheduleTemplateSelect" class="form-select">
                            <option value=""><?php esc_html_e('Select Template (Optional)', 'autopuzzle'); ?></option>
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
                        <label class="form-label"><?php esc_html_e('Recipient', 'autopuzzle'); ?> <span class="text-danger">*</span></label>
                        <input type="email" name="recipient" id="scheduleRecipient" class="form-control" required placeholder="<?php esc_attr_e('Email address', 'autopuzzle'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Subject', 'autopuzzle'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="subject" id="scheduleSubject" class="form-control" required placeholder="<?php esc_attr_e('Email subject', 'autopuzzle'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Message', 'autopuzzle'); ?> <span class="text-danger">*</span></label>
                        <textarea name="message" id="scheduleMessage" class="form-control" rows="5" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Scheduled Date', 'autopuzzle'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="scheduled_date" id="schedule-date-picker" class="form-control autopuzzle-datepicker mb-2" placeholder="<?php esc_attr_e('YYYY/MM/DD', 'autopuzzle'); ?>" required readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Scheduled Time', 'autopuzzle'); ?> <span class="text-danger">*</span></label>
                        <input type="time" name="scheduled_time" id="scheduleTime" class="form-control" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'autopuzzle'); ?></button>
                <button type="button" class="btn btn-primary" id="scheduleBtn">
                    <i class="ri-time-line me-1"></i>
                    <?php esc_html_e('Schedule', 'autopuzzle'); ?>
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
                <h5 class="modal-title" id="templateModalTitle"><?php esc_html_e('Add Template', 'autopuzzle'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="templateForm">
                    <input type="hidden" name="template_id" id="templateId">
                    <input type="hidden" name="type" value="email">
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Name', 'autopuzzle'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="templateName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Subject', 'autopuzzle'); ?></label>
                        <input type="text" name="subject" id="templateSubject" class="form-control" placeholder="<?php esc_attr_e('Email subject (optional)', 'autopuzzle'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Message', 'autopuzzle'); ?> <span class="text-danger">*</span></label>
                        <textarea name="message" id="templateMessage" class="form-control" rows="5" required></textarea>
                        <small class="form-text text-muted"><?php esc_html_e('Available variables: {customer_name}, {car_name}, {date}, {phone}, {inquiry_id}', 'autopuzzle'); ?></small>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="templateActive" value="1" checked>
                            <label class="form-check-label" for="templateActive">
                                <?php esc_html_e('Active', 'autopuzzle'); ?>
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Preview', 'autopuzzle'); ?></label>
                        <div class="card bg-light">
                            <div class="card-body">
                                <p id="templatePreview" class="mb-0 text-muted"><?php esc_html_e('Preview will appear here...', 'autopuzzle'); ?></p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'autopuzzle'); ?></button>
                <button type="button" class="btn btn-primary" id="saveTemplateBtn">
                    <?php esc_html_e('Save Template', 'autopuzzle'); ?>
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
                <h5 class="modal-title"><?php esc_html_e('Notification Details', 'autopuzzle'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <strong><?php esc_html_e('Recipient:', 'autopuzzle'); ?></strong>
                    <p id="detailRecipient" class="mb-0"></p>
                </div>
                <div class="mb-3">
                    <strong><?php esc_html_e('Message:', 'autopuzzle'); ?></strong>
                    <p id="detailMessage" class="mb-0"></p>
                </div>
                <div class="mb-3">
                    <strong><?php esc_html_e('Status:', 'autopuzzle'); ?></strong>
                    <p id="detailStatus" class="mb-0"></p>
                </div>
                <div class="mb-3" id="detailErrorSection" style="display: none;">
                    <strong><?php esc_html_e('Error:', 'autopuzzle'); ?></strong>
                    <p id="detailError" class="mb-0 text-danger"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e('Close', 'autopuzzle'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize Persian Datepicker
    if (typeof persianDatepicker !== 'undefined') {
        $('#date-from-picker').persianDatepicker({
            format: 'YYYY/MM/DD',
            calendarType: 'persian',
            observer: true,
            altField: '#date-from-picker',
            altFormat: 'YYYY/MM/DD',
            timePicker: false
        });
        
        $('#date-to-picker').persianDatepicker({
            format: 'YYYY/MM/DD',
            calendarType: 'persian',
            observer: true,
            altField: '#date-to-picker',
            altFormat: 'YYYY/MM/DD',
            timePicker: false
        });
        
        $('#schedule-date-picker').persianDatepicker({
            format: 'YYYY/MM/DD',
            calendarType: 'persian',
            observer: true,
            altField: '#schedule-date-picker',
            altFormat: 'YYYY/MM/DD',
            timePicker: false
        });
    }
    
    // Initialize Charts
    if (typeof Chart !== 'undefined') {
        // Timeline Chart
        var timelineCtx = document.getElementById('timelineChart');
        if (timelineCtx) {
            $.ajax({
                url: autopuzzle_ajax.url,
                type: 'POST',
                data: {
                    action: 'autopuzzle_get_notification_timeline',
                    nonce: autopuzzle_ajax.nonce,
                    type: 'email',
                    date_from: '<?php echo esc_js($date_from ?: date('Y-m-d', strtotime('-30 days'))); ?>',
                    date_to: '<?php echo esc_js($date_to ?: date('Y-m-d')); ?>'
                },
                success: function(response) {
                    if (response.success && response.data) {
                        var timelineChart = new Chart(timelineCtx, {
                            type: 'line',
                            data: {
                                labels: response.data.labels || [],
                                datasets: [{
                                    label: '<?php echo esc_js(__('Sent Emails', 'autopuzzle')); ?>',
                                    data: response.data.data || [],
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
                }
            });
        }
        
        // Status Chart
        var statusCtx = document.getElementById('statusChart');
        if (statusCtx) {
            var statusChart = new Chart(statusCtx, {
                type: 'pie',
                data: {
                    labels: [
                        '<?php echo esc_js(__('Sent', 'autopuzzle')); ?>',
                        '<?php echo esc_js(__('Failed', 'autopuzzle')); ?>',
                        '<?php echo esc_js(__('Pending', 'autopuzzle')); ?>'
                    ],
                    datasets: [{
                        data: [
                            <?php echo intval($stats->sent ?? 0); ?>,
                            <?php echo intval($stats->failed ?? 0); ?>,
                            <?php echo intval($stats->pending ?? 0); ?>
                        ],
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
                        }
                    }
                }
            });
        }
    }
    
    // Export to CSV
    $('#exportBtn').on('click', function() {
        var params = new URLSearchParams(window.location.search);
        params.set('export', 'csv');
        window.location.href = '<?php echo esc_url(home_url('/dashboard/notifications/email')); ?>?' + params.toString();
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
    
    // Send Single Email
    $('#sendSingleBtn').on('click', function() {
        var recipient = $('#singleRecipient').val();
        var subject = $('#singleSubject').val();
        var message = $('#singleMessage').val();
        
        if (!recipient || !subject || !message) {
            alert('<?php echo esc_js(__('Recipient, subject and message are required.', 'autopuzzle')); ?>');
            return;
        }
        
        var btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> <?php echo esc_js(__('Sending...', 'autopuzzle')); ?>');
        
        $.ajax({
            url: autopuzzle_ajax.url,
            type: 'POST',
            data: {
                action: 'autopuzzle_send_single_notification',
                nonce: autopuzzle_ajax.nonce,
                type: 'email',
                recipient: recipient,
                subject: subject,
                message: message
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php echo esc_js(__('Email sent successfully!', 'autopuzzle')); ?>');
                    $('#sendSingleModal').modal('hide');
                    location.reload();
                } else {
                    alert('<?php echo esc_js(__('Error:', 'autopuzzle')); ?> ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Server error. Please try again.', 'autopuzzle')); ?>');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="ri-send-plane-line me-1"></i> <?php echo esc_js(__('Send Email', 'autopuzzle')); ?>');
            }
        });
    });
    
    // Send Bulk Email
    $('#sendBulkBtn').on('click', function() {
        var recipientType = $('#bulkRecipientType').val();
        var customRecipients = $('#bulkCustomRecipients').val();
        var subject = $('#bulkSubject').val();
        var message = $('#bulkMessage').val();
        
        if (!subject || !message) {
            alert('<?php echo esc_js(__('Subject and message are required.', 'autopuzzle')); ?>');
            return;
        }
        
        var btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        $('#bulkProgress').show();
        
        $.ajax({
            url: autopuzzle_ajax.url,
            type: 'POST',
            data: {
                action: 'autopuzzle_send_bulk_notification_by_type',
                nonce: autopuzzle_ajax.nonce,
                type: 'email',
                recipient_type: recipientType,
                custom_recipients: customRecipients,
                subject: subject,
                message: message
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php echo esc_js(__('Bulk Email sent successfully!', 'autopuzzle')); ?>');
                    $('#sendBulkModal').modal('hide');
                    location.reload();
                } else {
                    alert('<?php echo esc_js(__('Error:', 'autopuzzle')); ?> ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Server error. Please try again.', 'autopuzzle')); ?>');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="ri-send-plane-line me-1"></i> <?php echo esc_js(__('Send Bulk', 'autopuzzle')); ?>');
                $('#bulkProgress').hide();
            }
        });
    });
    
    // Schedule Email
    $('#scheduleBtn').on('click', function() {
        var recipient = $('#scheduleRecipient').val();
        var subject = $('#scheduleSubject').val();
        var message = $('#scheduleMessage').val();
        var scheduledDate = $('#schedule-date-picker').val();
        var scheduledTime = $('#scheduleTime').val();
        
        if (!recipient || !subject || !message || !scheduledDate || !scheduledTime) {
            alert('<?php echo esc_js(__('All fields are required.', 'autopuzzle')); ?>');
            return;
        }
        
        var scheduledAt = scheduledDate + ' ' + scheduledTime;
        var btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        
        $.ajax({
            url: autopuzzle_ajax.url,
            type: 'POST',
            data: {
                action: 'autopuzzle_schedule_notification_by_type',
                nonce: autopuzzle_ajax.nonce,
                type: 'email',
                recipient: recipient,
                subject: subject,
                message: message,
                scheduled_at: scheduledAt
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php echo esc_js(__('Email scheduled successfully!', 'autopuzzle')); ?>');
                    $('#scheduleModal').modal('hide');
                    location.reload();
                } else {
                    alert('<?php echo esc_js(__('Error:', 'autopuzzle')); ?> ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Server error. Please try again.', 'autopuzzle')); ?>');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="ri-time-line me-1"></i> <?php echo esc_js(__('Schedule', 'autopuzzle')); ?>');
            }
        });
    });
    
    // Retry failed notification
    $('.retry-notification').on('click', function() {
        var logId = $(this).data('log-id');
        var btn = $(this);
        
        if (!confirm('<?php echo esc_js(__('Retry sending this email?', 'autopuzzle')); ?>')) {
            return;
        }
        
        btn.prop('disabled', true);
        
        $.ajax({
            url: autopuzzle_ajax.url,
            type: 'POST',
            data: {
                action: 'autopuzzle_retry_notification',
                nonce: autopuzzle_ajax.nonce,
                log_id: logId
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php echo esc_js(__('Email retried successfully!', 'autopuzzle')); ?>');
                    location.reload();
                } else {
                    alert('<?php echo esc_js(__('Error:', 'autopuzzle')); ?> ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Server error. Please try again.', 'autopuzzle')); ?>');
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
        $('#templateModalTitle').text('<?php echo esc_js(__('Add Template', 'autopuzzle')); ?>');
        $('#templateForm')[0].reset();
        $('#templateId').val('');
        $('#templateActive').prop('checked', true);
        $('#templatePreview').text('<?php echo esc_js(__('Preview will appear here...', 'autopuzzle')); ?>');
    });
    
    $('.edit-template').on('click', function() {
        $('#templateModalTitle').text('<?php echo esc_js(__('Edit Template', 'autopuzzle')); ?>');
        $('#templateId').val($(this).data('template-id'));
        $('#templateName').val($(this).data('name'));
        $('#templateSubject').val($(this).data('subject') || '');
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
            $('#templatePreview').text('<?php echo esc_js(__('Preview will appear here...', 'autopuzzle')); ?>');
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
        
        var action = data.template_id ? 'autopuzzle_update_notification_template' : 'autopuzzle_create_notification_template';
        
        $.ajax({
            url: autopuzzle_ajax.url,
            type: 'POST',
            data: {
                action: action,
                nonce: autopuzzle_ajax.nonce,
                ...data
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php echo esc_js(__('Template saved successfully!', 'autopuzzle')); ?>');
                    $('#templateModal').modal('hide');
                    location.reload();
                } else {
                    alert('<?php echo esc_js(__('Error:', 'autopuzzle')); ?> ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Server error. Please try again.', 'autopuzzle')); ?>');
            },
            complete: function() {
                btn.prop('disabled', false).html('<?php echo esc_js(__('Save Template', 'autopuzzle')); ?>');
            }
        });
    });
    
    $('.duplicate-template').on('click', function() {
        if (!confirm('<?php echo esc_js(__('Duplicate this template?', 'autopuzzle')); ?>')) {
            return;
        }
        
        $.ajax({
            url: autopuzzle_ajax.url,
            type: 'POST',
            data: {
                action: 'autopuzzle_duplicate_notification_template',
                nonce: autopuzzle_ajax.nonce,
                template_id: $(this).data('template-id')
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php echo esc_js(__('Template duplicated successfully!', 'autopuzzle')); ?>');
                    location.reload();
                } else {
                    alert('<?php echo esc_js(__('Error:', 'autopuzzle')); ?> ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Server error. Please try again.', 'autopuzzle')); ?>');
            }
        });
    });
    
    $('.delete-template').on('click', function() {
        if (!confirm('<?php echo esc_js(__('Delete this template?', 'autopuzzle')); ?>')) {
            return;
        }
        
        $.ajax({
            url: autopuzzle_ajax.url,
            type: 'POST',
            data: {
                action: 'autopuzzle_delete_notification_template',
                nonce: autopuzzle_ajax.nonce,
                template_id: $(this).data('template-id')
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php echo esc_js(__('Template deleted successfully!', 'autopuzzle')); ?>');
                    location.reload();
                } else {
                    alert('<?php echo esc_js(__('Error:', 'autopuzzle')); ?> ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Server error. Please try again.', 'autopuzzle')); ?>');
            }
        });
    });
});
</script>
