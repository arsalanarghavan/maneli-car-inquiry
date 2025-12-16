<?php
/**
 * Notification Center Page
 * مرکز اطلاع‌رسانی - مدیریت تمام اطلاع‌رسانی‌ها
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

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Get all logs for export (no pagination)
    $export_logs = Autopuzzle_Database::get_notification_logs([
        'type' => isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '',
        'status' => isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '',
        'date_from' => isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '',
        'date_to' => isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '',
        'search' => isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '',
        'limit' => 10000,
        'offset' => 0,
    ]);
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=notifications-' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
    
    // Headers
    fputcsv($output, [
        esc_html__('ID', 'autopuzzle'),
        esc_html__('Type', 'autopuzzle'),
        esc_html__('Recipient', 'autopuzzle'),
        esc_html__('Message', 'autopuzzle'),
        esc_html__('Status', 'autopuzzle'),
        esc_html__('Created At', 'autopuzzle'),
        esc_html__('Sent At', 'autopuzzle'),
        esc_html__('Error Message', 'autopuzzle'),
    ]);
    
    // Data
    foreach ($export_logs as $log) {
        fputcsv($output, [
            $log->id,
            $log->type,
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
$type_filter = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 50;

// Get notification logs
$logs = Autopuzzle_Database::get_notification_logs([
    'type' => $type_filter,
    'status' => $status_filter,
    'date_from' => $date_from,
    'date_to' => $date_to,
    'search' => $search,
    'limit' => $per_page,
    'offset' => ($paged - 1) * $per_page,
]);

$total_logs = Autopuzzle_Database::get_notification_logs_count([
    'type' => $type_filter,
    'status' => $status_filter,
    'date_from' => $date_from,
    'date_to' => $date_to,
    'search' => $search,
]);

$total_pages = ceil($total_logs / $per_page);

// Get statistics
$stats = Autopuzzle_Database::get_notification_stats([
    'date_from' => $date_from ?: date('Y-m-d', strtotime('-30 days')),
    'date_to' => $date_to ?: date('Y-m-d'),
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

// Enqueue Chart.js for charts
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
                        <li class="breadcrumb-item active" aria-current="page"><?php esc_html_e('Notification Center', 'autopuzzle'); ?></li>
                    </ol>
                </nav>
                <h1 class="page-title mb-0"><?php esc_html_e('Notification Center', 'autopuzzle'); ?></h1>
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
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Total Notifications', 'autopuzzle'); ?></p>
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
                                    <i class="la la-sms fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('SMS Sent', 'autopuzzle'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center text-info"><?php echo function_exists('autopuzzle_number_format_persian') ? autopuzzle_number_format_persian($stats->sms_sent ?? 0) : number_format($stats->sms_sent ?? 0); ?></h4>
                            <span class="badge bg-info-transparent rounded-pill fs-11"><?php echo $stats->total > 0 && function_exists('autopuzzle_number_format_persian') ? autopuzzle_number_format_persian(round((($stats->sms_sent ?? 0) / $stats->total) * 100), 1) : '۰'; ?>%</span>
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
                <form method="get" action="<?php echo esc_url(home_url('/dashboard/notifications-center')); ?>">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label"><?php esc_html_e('Search', 'autopuzzle'); ?></label>
                            <input type="text" name="search" class="form-control" placeholder="<?php esc_attr_e('Search...', 'autopuzzle'); ?>" value="<?php echo esc_attr($search); ?>">
                        </div>
                    </div>

                    <div class="row g-3 align-items-end mt-1">
                        <div class="col-6 col-lg-3">
                            <label class="form-label"><?php esc_html_e('Type', 'autopuzzle'); ?></label>
                            <select name="type" class="form-control form-select">
                                <option value=""><?php esc_html_e('All Types', 'autopuzzle'); ?></option>
                                <option value="sms" <?php selected($type_filter, 'sms'); ?>><?php esc_html_e('SMS', 'autopuzzle'); ?></option>
                                <option value="telegram" <?php selected($type_filter, 'telegram'); ?>><?php esc_html_e('Telegram', 'autopuzzle'); ?></option>
                                <option value="email" <?php selected($type_filter, 'email'); ?>><?php esc_html_e('Email', 'autopuzzle'); ?></option>
                                <option value="notification" <?php selected($type_filter, 'notification'); ?>><?php esc_html_e('In-App Notification', 'autopuzzle'); ?></option>
                            </select>
                        </div>
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
                            <a href="<?php echo esc_url(home_url('/dashboard/notifications-center')); ?>" class="btn btn-secondary btn-wave w-100">
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
                        <h5 class="mb-0"><?php esc_html_e('Notification Types Distribution', 'autopuzzle'); ?></h5>
                    </div>
                    <div class="card-body">
                        <canvas id="typesChart" height="300"></canvas>
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
        <div class="card custom-card">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><?php esc_html_e('Notification Logs', 'autopuzzle'); ?></h5>
                    <span class="badge bg-primary-transparent"><?php echo function_exists('autopuzzle_number_format_persian') ? autopuzzle_number_format_persian($total_logs) : number_format($total_logs); ?> <?php esc_html_e('Total', 'autopuzzle'); ?></span>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('ID', 'autopuzzle'); ?></th>
                                <th><?php esc_html_e('Type', 'autopuzzle'); ?></th>
                                <th><?php esc_html_e('Recipient', 'autopuzzle'); ?></th>
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
                                        <p class="text-muted mb-0"><?php esc_html_e('No notifications found.', 'autopuzzle'); ?></p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?php echo esc_html($log->id); ?></td>
                                        <td>
                                            <?php
                                            $type_labels = [
                                                'sms' => esc_html__('SMS', 'autopuzzle'),
                                                'telegram' => esc_html__('Telegram', 'autopuzzle'),
                                                'email' => esc_html__('Email', 'autopuzzle'),
                                                'notification' => esc_html__('Notification', 'autopuzzle'),
                                            ];
                                            $type_icons = [
                                                'sms' => 'ri-message-2-line',
                                                'telegram' => 'ri-telegram-line',
                                                'email' => 'ri-mail-line',
                                                'notification' => 'ri-notification-line',
                                            ];
                                            echo '<i class="' . esc_attr($type_icons[$log->type] ?? 'ri-question-line') . ' me-1"></i>';
                                            echo esc_html($type_labels[$log->type] ?? $log->type);
                                            ?>
                                        </td>
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
                                'type' => $type_filter,
                                'status' => $status_filter,
                                'date_from' => $date_from,
                                'date_to' => $date_to,
                                'search' => $search,
                            ], home_url('/dashboard/notifications-center'));
                            
                            // Previous
                            if ($paged > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo esc_url(add_query_arg('paged', $paged - 1, $base_url)); ?>">
                                        <?php esc_html_e('Previous', 'autopuzzle'); ?>
                                    </a>
                                </li>
                            <?php endif;
                            
                            // Page numbers
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
                            
                            // Next
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
        
    </div>
</div>

<!-- Send Bulk Modal -->
<div class="modal fade" id="sendBulkModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php esc_html_e('Send Bulk Notification', 'autopuzzle'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="bulkNotificationForm">
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Channels', 'autopuzzle'); ?></label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="channels[]" value="sms" id="bulkSMS">
                            <label class="form-check-label" for="bulkSMS"><?php esc_html_e('SMS', 'autopuzzle'); ?></label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="channels[]" value="telegram" id="bulkTelegram">
                            <label class="form-check-label" for="bulkTelegram"><?php esc_html_e('Telegram', 'autopuzzle'); ?></label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="channels[]" value="email" id="bulkEmail">
                            <label class="form-check-label" for="bulkEmail"><?php esc_html_e('Email', 'autopuzzle'); ?></label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="channels[]" value="notification" id="bulkNotification">
                            <label class="form-check-label" for="bulkNotification"><?php esc_html_e('In-App Notification', 'autopuzzle'); ?></label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Recipients', 'autopuzzle'); ?></label>
                        <select name="recipient_type" class="form-select mb-2">
                            <option value="all"><?php esc_html_e('All Users', 'autopuzzle'); ?></option>
                            <option value="customers"><?php esc_html_e('Customers Only', 'autopuzzle'); ?></option>
                            <option value="experts"><?php esc_html_e('Experts Only', 'autopuzzle'); ?></option>
                            <option value="admins"><?php esc_html_e('Admins Only', 'autopuzzle'); ?></option>
                        </select>
                        <textarea name="custom_recipients" class="form-control" rows="3" placeholder="<?php esc_attr_e('Or enter custom recipients (phone numbers, emails, or user IDs - one per line)', 'autopuzzle'); ?>"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Message', 'autopuzzle'); ?></label>
                        <textarea name="message" class="form-control" rows="5" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'autopuzzle'); ?></button>
                <button type="button" class="btn btn-primary" id="sendBulkBtn"><?php esc_html_e('Send', 'autopuzzle'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Schedule Modal -->
<div class="modal fade" id="scheduleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php esc_html_e('Schedule Notification Dialog', 'autopuzzle'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="scheduleNotificationForm">
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Channel', 'autopuzzle'); ?></label>
                        <select name="channel" class="form-select" required>
                            <option value="sms"><?php esc_html_e('SMS', 'autopuzzle'); ?></option>
                            <option value="telegram"><?php esc_html_e('Telegram', 'autopuzzle'); ?></option>
                            <option value="email"><?php esc_html_e('Email', 'autopuzzle'); ?></option>
                            <option value="notification"><?php esc_html_e('In-App Notification', 'autopuzzle'); ?></option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Recipient', 'autopuzzle'); ?></label>
                        <input type="text" name="recipient" class="form-control" required placeholder="<?php esc_attr_e('Phone, email, chat ID, or user ID', 'autopuzzle'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Message', 'autopuzzle'); ?></label>
                        <textarea name="message" class="form-control" rows="5" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Scheduled Date', 'autopuzzle'); ?></label>
                        <input type="text" name="scheduled_date" id="schedule-date-picker" class="form-control autopuzzle-datepicker mb-2" placeholder="<?php esc_attr_e('YYYY/MM/DD', 'autopuzzle'); ?>" required readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php esc_html_e('Scheduled Time', 'autopuzzle'); ?></label>
                        <input type="time" name="scheduled_time" class="form-control" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'autopuzzle'); ?></button>
                <button type="button" class="btn btn-primary" id="scheduleBtn"><?php esc_html_e('Schedule', 'autopuzzle'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize Persian Datepicker
    if (typeof persianDatepicker !== 'undefined') {
        // Date From
        $('#date-from-picker').persianDatepicker({
            format: 'YYYY/MM/DD',
            calendarType: 'persian',
            observer: true,
            altField: '#date-from-picker',
            altFormat: 'YYYY/MM/DD',
            timePicker: false
        });
        
        // Date To
        $('#date-to-picker').persianDatepicker({
            format: 'YYYY/MM/DD',
            calendarType: 'persian',
            observer: true,
            altField: '#date-to-picker',
            altFormat: 'YYYY/MM/DD',
            timePicker: false
        });
        
        // Schedule Date
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
        // Types Chart
        var typesCtx = document.getElementById('typesChart');
        if (typesCtx) {
            var typesChart = new Chart(typesCtx, {
                type: 'doughnut',
                data: {
                    labels: [
                        '<?php echo esc_js(__('SMS', 'autopuzzle')); ?>',
                        '<?php echo esc_js(__('Telegram', 'autopuzzle')); ?>',
                        '<?php echo esc_js(__('Email', 'autopuzzle')); ?>',
                        '<?php echo esc_js(__('Notification', 'autopuzzle')); ?>'
                    ],
                    datasets: [{
                        data: [
                            <?php echo intval($stats->sms_sent ?? 0); ?>,
                            <?php echo intval($stats->telegram_sent ?? 0); ?>,
                            <?php echo intval($stats->email_sent ?? 0); ?>,
                            <?php echo intval($stats->notification_sent ?? 0); ?>
                        ],
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.8)',
                            'rgba(0, 123, 255, 0.8)',
                            'rgba(255, 99, 132, 0.8)',
                            'rgba(255, 206, 86, 0.8)'
                        ],
                        borderColor: [
                            'rgba(54, 162, 235, 1)',
                            'rgba(0, 123, 255, 1)',
                            'rgba(255, 99, 132, 1)',
                            'rgba(255, 206, 86, 1)'
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
        window.location.href = '<?php echo esc_url(home_url('/dashboard/notifications-center')); ?>?' + params.toString();
    });
    
    // Send Bulk
    $('#sendBulkBtn').on('click', function() {
        var formData = $('#bulkNotificationForm').serialize();
        var btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        
        $.ajax({
            url: autopuzzle_ajax.url,
            type: 'POST',
            data: {
                action: 'autopuzzle_send_bulk_notification',
                nonce: autopuzzle_ajax.nonce,
                ...Object.fromEntries(new URLSearchParams(formData))
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php echo esc_js(__('Notifications sent successfully!', 'autopuzzle')); ?>');
                    location.reload();
                } else {
                    alert('<?php echo esc_js(__('Error:', 'autopuzzle')); ?> ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Server error. Please try again.', 'autopuzzle')); ?>');
            },
            complete: function() {
                btn.prop('disabled', false).html('<?php echo esc_js(__('Send', 'autopuzzle')); ?>');
            }
        });
    });
    
    // Schedule
    $('#scheduleBtn').on('click', function() {
        var formData = $('#scheduleNotificationForm').serializeArray();
        var data = {};
        $.each(formData, function(i, field) {
            data[field.name] = field.value;
        });
        
        // Combine date and time
        if (data.scheduled_date && data.scheduled_time) {
            // Convert Persian date to Gregorian for backend
            data.scheduled_at = data.scheduled_date + ' ' + data.scheduled_time;
            delete data.scheduled_date;
            delete data.scheduled_time;
        }
        
        var btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        
        $.ajax({
            url: autopuzzle_ajax.url,
            type: 'POST',
            data: {
                action: 'autopuzzle_schedule_notification',
                nonce: autopuzzle_ajax.nonce,
                ...data
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php echo esc_js(__('Notification Scheduled Successfully', 'autopuzzle')); ?>');
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
                btn.prop('disabled', false).html('<?php echo esc_js(__('Schedule', 'autopuzzle')); ?>');
            }
        });
    });
    
    // Retry failed notification
    $('.retry-notification').on('click', function() {
        var logId = $(this).data('log-id');
        var btn = $(this);
        
        if (!confirm('<?php echo esc_js(__('Retry sending this notification?', 'autopuzzle')); ?>')) {
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
                    alert('<?php echo esc_js(__('Notification retried successfully!', 'autopuzzle')); ?>');
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
});
</script>

