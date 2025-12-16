<?php
/**
 * System Logs Page
 * Displays system logs including errors, debug messages, console logs, and button errors
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check access
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login'));
    exit;
}

$current_user = wp_get_current_user();
$is_admin = current_user_can('manage_autopuzzle_inquiries');
$is_manager = in_array('autopuzzle_manager', $current_user->roles, true) || in_array('autopuzzle_admin', $current_user->roles, true);

if (!$is_admin && !$is_manager) {
    wp_redirect(home_url('/dashboard'));
    exit;
}

// Load Logger
$logger = Autopuzzle_Logger::instance();

// Enqueue Persian Datepicker (only for Persian locale)
if (function_exists('autopuzzle_enqueue_persian_datepicker')) {
    autopuzzle_enqueue_persian_datepicker();
}

// Helper function to convert Jalali to Gregorian
if (!function_exists('autopuzzle_jalali_to_gregorian')) {
    function autopuzzle_jalali_to_gregorian($j_y, $j_m, $j_d) {
        $g_days_in_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        $j_days_in_month = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];
        
        $jy = (int)$j_y - 979;
        $jm = (int)$j_m - 1;
        $jd = (int)$j_d - 1;
        
        $j_day_no = 365 * $jy + (int)(($jy) / 33) * 8 + (int)((($jy % 33) + 3) / 4);
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
        $g_day_no %= 1461;
        
        if ($g_day_no >= 366) {
            $leap = false;
            $g_day_no--;
            $gy += (int)($g_day_no / 365);
            $g_day_no = $g_day_no % 365;
        }
        
        for ($i = 0; $g_day_no >= $g_days_in_month[$i] + ($i == 1 && $leap); $i++) {
            $g_day_no -= $g_days_in_month[$i] + ($i == 1 && $leap);
        }
        
        $gm = $i + 1;
        $gd = $g_day_no + 1;
        
        return [$gy, $gm, $gd];
    }
}

// Helper function to convert Jalali date string to Gregorian
function convert_jalali_to_gregorian_date($date_str) {
    if (empty($date_str)) return '';
    
    if (function_exists('autopuzzle_convert_to_english_digits')) {
        $date_str = autopuzzle_convert_to_english_digits($date_str);
    }

    // Check if it's already in Gregorian format (YYYY-MM-DD)
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date_str)) {
        return $date_str;
    }
    
    // Check if it's Jalali format (Y/m/d or YYYY/MM/DD)
    if (preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $date_str, $matches)) {
        $year = (int)$matches[1];
        $month = (int)$matches[2];
        $day = (int)$matches[3];
        
        // If year is between 1300-1500, it's likely Jalali
        if ($year >= 1300 && $year <= 1500) {
            if (function_exists('autopuzzle_jalali_to_gregorian')) {
                list($gy, $gm, $gd) = autopuzzle_jalali_to_gregorian($year, $month, $day);
                return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
            }
        }
    }
    
    return $date_str;
}

if (!function_exists('autopuzzle_format_relative_path')) {
    function autopuzzle_format_relative_path($path) {
        if (empty($path)) {
            return '';
        }
        $normalized = str_replace('\\', '/', $path);
        $root = str_replace('\\', '/', ABSPATH);
        if (strpos($normalized, $root) === 0) {
            $normalized = ltrim(substr($normalized, strlen($root)), '/');
        }
        return $normalized;
    }
}

if (!function_exists('autopuzzle_format_localized_digits')) {
    function autopuzzle_format_localized_digits($value) {
        static $cache_should_use_persian = null;

        $value = (string) $value;

        if ($cache_should_use_persian === null) {
            $cache_should_use_persian = function_exists('autopuzzle_should_use_persian_digits')
                ? autopuzzle_should_use_persian_digits()
                : true;
        }

        $persian_digits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic_digits  = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $english_digits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        if ($cache_should_use_persian) {
            $value = str_replace($english_digits, $persian_digits, $value);
            $value = str_replace(',', '،', $value);
            return $value;
        }

        if (function_exists('autopuzzle_convert_to_english_digits')) {
            $value = autopuzzle_convert_to_english_digits($value);
        } else {
            $value = str_replace($persian_digits, $english_digits, $value);
            $value = str_replace($arabic_digits, $english_digits, $value);
        }

        return str_replace('،', ',', $value);
    }
}

if (!function_exists('autopuzzle_translate_log_label')) {
    function autopuzzle_translate_log_label($label, $type = 'severity') {
        $maps = array(
            'severity' => array(
                'info' => esc_html__('Info', 'autopuzzle'),
                'warning' => esc_html__('Warning', 'autopuzzle'),
                'error' => esc_html__('Error', 'autopuzzle'),
                'critical' => esc_html__('Critical', 'autopuzzle'),
            ),
            'type' => array(
                'error' => esc_html__('Error', 'autopuzzle'),
                'debug' => esc_html__('Debug', 'autopuzzle'),
                'console' => esc_html__('Console', 'autopuzzle'),
                'button_error' => esc_html__('Button Error', 'autopuzzle'),
            ),
        );
        $label = strtolower((string) $label);
        if (isset($maps[$type][$label])) {
            return $maps[$type][$label];
        }
        return $label;
    }
}

if (!function_exists('autopuzzle_format_log_datetime')) {
    function autopuzzle_format_log_datetime($datetime) {
        $timestamp = strtotime($datetime);
        if (!$timestamp) {
            return '';
        }

        $year = date('Y', $timestamp);
        $month = date('m', $timestamp);
        $day = date('d', $timestamp);

        if (function_exists('autopuzzle_gregorian_to_jalali')) {
            $date = autopuzzle_gregorian_to_jalali($year, $month, $day, 'Y/m/d', false);
        } else {
            $date = function_exists('wp_date') ? wp_date('Y-m-d', $timestamp) : date('Y-m-d', $timestamp);
        }

        if (empty($date)) {
            $date = function_exists('wp_date') ? wp_date('Y-m-d', $timestamp) : date('Y-m-d', $timestamp);
        }

        $time = function_exists('wp_date') ? wp_date('H:i:s', $timestamp) : date('H:i:s', $timestamp);

        return autopuzzle_format_localized_digits(trim($date . ' ' . $time));
    }
}

// Get filters
$log_type = isset($_GET['log_type']) ? sanitize_text_field($_GET['log_type']) : '';
$severity = isset($_GET['severity']) ? sanitize_text_field($_GET['severity']) : '';
$date_from_raw = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
$date_to_raw = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$page_num = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$options = get_option('autopuzzle_inquiry_all_options', []);
$per_page = isset($options['max_logs_per_page']) ? max(10, intval($options['max_logs_per_page'])) : 50;
$offset = ($page_num - 1) * $per_page;
$use_persian_digits = function_exists('autopuzzle_should_use_persian_digits') ? autopuzzle_should_use_persian_digits() : true;

// Convert Jalali dates to Gregorian for database query
$date_from = convert_jalali_to_gregorian_date($date_from_raw);
$date_to = convert_jalali_to_gregorian_date($date_to_raw);

// Get logs
$args = array(
    'log_type' => $log_type,
    'severity' => $severity,
    'date_from' => $date_from,
    'date_to' => $date_to,
    'search' => $search,
    'limit' => $per_page,
    'offset' => $offset,
);

$logs = $logger->get_system_logs($args);
$total_logs = $logger->get_system_logs_count($args);
$total_pages = ceil($total_logs / $per_page);
$total_logs_label = autopuzzle_format_localized_digits(number_format($total_logs));
$date_placeholder = $use_persian_digits
    ? autopuzzle_format_localized_digits(__('YYYY/MM/DD', 'autopuzzle'))
    : esc_html__('YYYY-MM-DD', 'autopuzzle');
$date_from_display = autopuzzle_format_localized_digits($date_from_raw);
$date_to_display = autopuzzle_format_localized_digits($date_to_raw);
$date_input_type = $use_persian_digits ? 'text' : 'date';
$date_input_class = $use_persian_digits ? 'autopuzzle-datepicker' : '';
?>
<div class="main-content app-content">
    <div class="container-fluid">

        <!-- Start::page-header -->
        <div class="d-flex align-items-center justify-content-between page-header-breadcrumb flex-wrap gap-2">
            <div>
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item">
                        <a href="<?php echo esc_url(home_url('/dashboard')); ?>"><?php esc_html_e('Dashboard', 'autopuzzle'); ?></a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="<?php echo esc_url(home_url('/dashboard/logs/system')); ?>"><?php esc_html_e('System Logs', 'autopuzzle'); ?></a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page"><?php esc_html_e('System Logs', 'autopuzzle'); ?></li>
                </ol>
                <h1 class="page-title fw-medium fs-18 mb-0"><?php esc_html_e('System Logs', 'autopuzzle'); ?></h1>
            </div>
        </div>
        <!-- End::page-header -->

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card custom-card autopuzzle-mobile-filter-card" data-autopuzzle-mobile-filter>
                    <div class="card-header">
                        <h5
                            class="card-title mb-0 autopuzzle-mobile-filter-toggle d-flex align-items-center gap-2"
                            data-autopuzzle-filter-toggle
                            role="button"
                            tabindex="0"
                            aria-expanded="false"
                        >
                            <?php esc_html_e('Filters', 'autopuzzle'); ?>
                            <i class="ri-arrow-down-s-line ms-auto autopuzzle-mobile-filter-arrow d-md-none"></i>
                        </h5>
                    </div>
                    <div class="card-body autopuzzle-mobile-filter-body" data-autopuzzle-filter-body>
                        <form method="get" action="<?php echo esc_url(home_url('/dashboard/logs/system')); ?>" novalidate>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label"><?php esc_html_e('Search', 'autopuzzle'); ?></label>
                                    <input type="text" name="search" class="form-control" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search...', 'autopuzzle'); ?>">
                                </div>
                            </div>

                            <div class="row g-3 align-items-end mt-1">
                                <div class="col-6 col-lg-3">
                                    <label class="form-label"><?php esc_html_e('Log Type', 'autopuzzle'); ?></label>
                                    <select name="log_type" class="form-select">
                                        <option value=""><?php esc_html_e('All Types', 'autopuzzle'); ?></option>
                                        <option value="error" <?php selected($log_type, 'error'); ?>><?php esc_html_e('Error', 'autopuzzle'); ?></option>
                                        <option value="debug" <?php selected($log_type, 'debug'); ?>><?php esc_html_e('Debug', 'autopuzzle'); ?></option>
                                        <option value="console" <?php selected($log_type, 'console'); ?>><?php esc_html_e('Console', 'autopuzzle'); ?></option>
                                        <option value="button_error" <?php selected($log_type, 'button_error'); ?>><?php esc_html_e('Button Error', 'autopuzzle'); ?></option>
                                    </select>
                                </div>
                                <div class="col-6 col-lg-3">
                                    <label class="form-label"><?php esc_html_e('Severity', 'autopuzzle'); ?></label>
                                    <select name="severity" class="form-select">
                                        <option value=""><?php esc_html_e('All Severities', 'autopuzzle'); ?></option>
                                        <option value="info" <?php selected($severity, 'info'); ?>><?php esc_html_e('Info', 'autopuzzle'); ?></option>
                                        <option value="warning" <?php selected($severity, 'warning'); ?>><?php esc_html_e('Warning', 'autopuzzle'); ?></option>
                                        <option value="error" <?php selected($severity, 'error'); ?>><?php esc_html_e('Error', 'autopuzzle'); ?></option>
                                        <option value="critical" <?php selected($severity, 'critical'); ?>><?php esc_html_e('Critical', 'autopuzzle'); ?></option>
                                    </select>
                                </div>
                                <div class="col-6 col-lg-3">
                                    <label class="form-label"><?php esc_html_e('From Date', 'autopuzzle'); ?></label>
                                    <input type="<?php echo esc_attr($date_input_type); ?>" name="date_from" id="date-from-picker" class="form-control <?php echo esc_attr($date_input_class); ?>" value="<?php echo esc_attr($date_from_display); ?>" placeholder="<?php echo esc_attr($date_placeholder); ?>">
                                </div>
                                <div class="col-6 col-lg-3">
                                    <label class="form-label"><?php esc_html_e('To Date', 'autopuzzle'); ?></label>
                                    <input type="<?php echo esc_attr($date_input_type); ?>" name="date_to" id="date-to-picker" class="form-control <?php echo esc_attr($date_input_class); ?>" value="<?php echo esc_attr($date_to_display); ?>" placeholder="<?php echo esc_attr($date_placeholder); ?>">
                                </div>
                            </div>

                            <div class="row g-2 mt-3">
                                <div class="col-6 col-lg-auto">
                                    <button type="submit" class="btn btn-primary w-100"><?php esc_html_e('Filter', 'autopuzzle'); ?></button>
                                </div>
                                <div class="col-6 col-lg-auto">
                                    <a href="<?php echo esc_url(home_url('/dashboard/logs/system')); ?>" class="btn btn-secondary w-100"><?php esc_html_e('Reset', 'autopuzzle'); ?></a>
                                </div>
                                <div class="col-6 d-lg-none"></div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logs Table -->
        <div class="row">
            <div class="col-12">
                <div class="card custom-card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h5 class="card-title mb-0"><?php esc_html_e('System Logs', 'autopuzzle'); ?></h5>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary"><?php echo esc_html($total_logs_label); ?> <?php esc_html_e('Logs', 'autopuzzle'); ?></span>
                            <button id="delete-system-logs-btn" type="button" class="btn btn-outline-danger btn-sm">
                                <i class="ri-delete-bin-6-line me-1"></i>
                                <?php esc_html_e('Delete Logs', 'autopuzzle'); ?>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($logs)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover text-nowrap">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e('ID', 'autopuzzle'); ?></th>
                                            <th><?php esc_html_e('Type', 'autopuzzle'); ?></th>
                                            <th><?php esc_html_e('Severity', 'autopuzzle'); ?></th>
                                            <th><?php esc_html_e('Message', 'autopuzzle'); ?></th>
                                            <th><?php esc_html_e('File/Line', 'autopuzzle'); ?></th>
                                            <th><?php esc_html_e('User', 'autopuzzle'); ?></th>
                                            <th><?php esc_html_e('Date', 'autopuzzle'); ?></th>
                                            <th><?php esc_html_e('Actions', 'autopuzzle'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($logs as $log): ?>
                                            <?php
                                            $user = $log->user_id ? get_user_by('ID', $log->user_id) : null;
                                            $severity_class = 'bg-info';
                                            if ($log->severity === 'error') $severity_class = 'bg-danger';
                                            elseif ($log->severity === 'warning') $severity_class = 'bg-warning text-dark';
                                            elseif ($log->severity === 'critical') $severity_class = 'bg-danger';
                                            
                                            $log_type_class = 'bg-secondary';
                                            if ($log->log_type === 'error') $log_type_class = 'bg-danger';
                                            elseif ($log->log_type === 'debug') $log_type_class = 'bg-info';
                                            elseif ($log->log_type === 'console') $log_type_class = 'bg-primary';
                                            elseif ($log->log_type === 'button_error') $log_type_class = 'bg-warning text-dark';
                                            
                                            $context = $log->context ? json_decode($log->context, true) : null;
                                            $relative_path = autopuzzle_format_relative_path($log->file);
                                            $user_display = '';
                                            $user_title = '';

                                            if ($user) {
                                                $user_display = $user->display_name;
                                                $user_title = sprintf(__('User ID: %d', 'autopuzzle'), $user->ID);
                                            } elseif (!empty($log->ip_address)) {
                                                $user_display = $log->ip_address;
                                                $user_title = $log->user_agent ?: '';
                                            } elseif (!empty($log->user_agent)) {
                                                $user_display = mb_strimwidth($log->user_agent, 0, 35, '…', 'UTF-8');
                                                $user_title = $log->user_agent;
                                            }
                                            if (!empty($user_display)) {
                                                $user_display = autopuzzle_format_localized_digits($user_display);
                                            }
                                            if (!empty($user_title)) {
                                                $user_title = autopuzzle_format_localized_digits($user_title);
                                            }
                                            ?>
                                            <tr>
                                                <td><?php echo esc_html(autopuzzle_format_localized_digits($log->id)); ?></td>
                                                <td><span class="badge <?php echo esc_attr($log_type_class); ?>"><?php echo esc_html(autopuzzle_translate_log_label($log->log_type, 'type')); ?></span></td>
                                                <td><span class="badge <?php echo esc_attr($severity_class); ?>"><?php echo esc_html(autopuzzle_translate_log_label($log->severity, 'severity')); ?></span></td>
                                                <td>
                                                    <div class="log-message" title="<?php echo esc_attr($log->message); ?>">
                                                        <?php echo esc_html($log->message); ?>
                                                    </div>
                                                    <?php if ($context): ?>
                                                        <span class="badge bg-light text-dark mt-1" title="<?php echo esc_attr(json_encode($context, JSON_UNESCAPED_UNICODE)); ?>">
                                                            <?php esc_html_e('Context', 'autopuzzle'); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($relative_path): ?>
                                                        <div class="file-path" title="<?php echo esc_attr($log->file); ?>">
                                                        <?php echo esc_html($relative_path); ?>
                                                        </div>
                                                        <?php if ($log->line): ?>
                                                        <small class="text-muted"><?php echo esc_html(sprintf(__('Line %s', 'autopuzzle'), autopuzzle_format_localized_digits($log->line))); ?></small>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($user_display)): ?>
                                                        <span title="<?php echo esc_attr($user_title); ?>">
                                                            <?php echo esc_html($user_display); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo esc_html(autopuzzle_format_log_datetime($log->created_at)); ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" onclick="showLogDetails(<?php echo esc_js($log->id); ?>)">
                                                        <?php esc_html_e('Details', 'autopuzzle'); ?>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination justify-content-center">
                                        <?php
                                        $current_url = add_query_arg(array(
                                            'log_type' => $log_type,
                                            'severity' => $severity,
                                            'date_from' => $date_from_raw,
                                            'date_to' => $date_to_raw,
                                            'search' => $search,
                                        ), home_url('/dashboard/logs/system'));
                                        
                                        if ($page_num > 1):
                                            ?>
                                            <li class="page-item">
                                                <a class="page-link" href="<?php echo esc_url(add_query_arg('paged', $page_num - 1, $current_url)); ?>"><?php esc_html_e('Previous', 'autopuzzle'); ?></a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <?php if ($i == 1 || $i == $total_pages || ($i >= $page_num - 2 && $i <= $page_num + 2)): ?>
                                                <li class="page-item <?php echo $i == $page_num ? 'active' : ''; ?>">
                                                    <a class="page-link" href="<?php echo esc_url(add_query_arg('paged', $i, $current_url)); ?>"><?php echo esc_html(autopuzzle_format_localized_digits($i)); ?></a>
                                                </li>
                                            <?php elseif ($i == $page_num - 3 || $i == $page_num + 3): ?>
                                                <li class="page-item disabled">
                                                    <span class="page-link">...</span>
                                                </li>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page_num < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="<?php echo esc_url(add_query_arg('paged', $page_num + 1, $current_url)); ?>"><?php esc_html_e('Next', 'autopuzzle'); ?></a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <?php esc_html_e('No logs found.', 'autopuzzle'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Log Details Modal -->
<div class="modal fade" id="logDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php esc_html_e('Log Details', 'autopuzzle'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="logDetailsContent">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    function initSystemLogsDatepickers() {
        if (typeof jQuery === 'undefined') {
            setTimeout(initSystemLogsDatepickers, 100);
            return;
        }

        jQuery(function($) {
            var isPersianLocale = <?php echo $use_persian_digits ? 'true' : 'false'; ?>;

            $('.file-path').each(function() {
                var $el = $(this);
                if (!$el.attr('title')) {
                    $el.attr('title', $el.text());
                }
            });

            var deleteLogsNonce = '<?php echo esc_js(wp_create_nonce('autopuzzle_log_actions_nonce')); ?>';
            var deleteLogsConfirm = '<?php echo esc_js(__('Are you sure you want to delete all system logs? This action cannot be undone.', 'autopuzzle')); ?>';
            var deleteLogsSuccess = '<?php echo esc_js(__('System logs deleted successfully.', 'autopuzzle')); ?>';
            var deleteLogsFailure = '<?php echo esc_js(__('Failed to delete system logs.', 'autopuzzle')); ?>';

            $('#delete-system-logs-btn').on('click', function() {
                var $btn = $(this);
                if ($btn.prop('disabled')) {
                    return;
                }

                if (!confirm(deleteLogsConfirm)) {
                    return;
                }

                $btn.prop('disabled', true).addClass('disabled');

                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'autopuzzle_delete_system_logs',
                        nonce: deleteLogsNonce
                    }
                }).done(function(response) {
                    if (response && response.success) {
                        alert(deleteLogsSuccess);
                        window.location.reload();
                    } else {
                        var message = (response && response.data && response.data.message) ? response.data.message : deleteLogsFailure;
                        alert(message);
                    }
                }).fail(function() {
                    alert(deleteLogsFailure);
                }).always(function() {
                    $btn.prop('disabled', false).removeClass('disabled');
                });
            });

            if (isPersianLocale) {
                var selectors = ['#date-from-picker', '#date-to-picker'];
                var retryCount = 0;
                var maxRetries = 20;

                function ensureDatepickerPlugin(callback) {
                    if (typeof $.fn.persianDatepicker === 'undefined') {
                        if (retryCount < maxRetries) {
                            retryCount++;
                            setTimeout(function() {
                                ensureDatepickerPlugin(callback);
                            }, 150);
                        } else {
                            console.warn('<?php echo esc_js(__('The persianDatepicker plugin was not loaded in time on the system logs page.', 'autopuzzle')); ?>');
                        }
                        return;
                    }
                    callback();
                }

                function initializePicker($field) {
                    if (!$field.length || $field.data('pdp-init') === 'true') {
                        return;
                    }

                    var hasInitialValue = $field.val() && $field.val().trim() !== '';

                    $field.persianDatepicker({
                        formatDate: 'YYYY/MM/DD',
                        persianNumbers: true,
                        autoClose: true,
                        observer: false,
                        initialValue: hasInitialValue,
                        timePicker: false
                    });

                    $field.attr('data-pdp-init', 'true');
                }

                ensureDatepickerPlugin(function() {
                    selectors.forEach(function(selector) {
                        initializePicker($(selector));
                    });
                });

                $(document).on('focus', selectors.join(', '), function() {
                    var $field = $(this);
                    if (!$field.data('pdp-init') || $field.data('pdp-init') !== 'true') {
                        ensureDatepickerPlugin(function() {
                            initializePicker($field);
                            if (typeof $field.data('persianDatepicker') === 'object' && typeof $field.data('persianDatepicker').show === 'function') {
                                $field.data('persianDatepicker').show();
                            }
                        });
                    }
                });
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSystemLogsDatepickers);
    } else {
        initSystemLogsDatepickers();
    }
})();

function showLogDetails(logId) {
    jQuery.ajax({
        url: '<?php echo admin_url('admin-ajax.php'); ?>',
        type: 'POST',
        data: {
            action: 'autopuzzle_get_system_log_details',
            log_id: logId,
            security: '<?php echo wp_create_nonce('autopuzzle_log_details_nonce'); ?>'
        },
        success: function(response) {
            if (response.success) {
                jQuery('#logDetailsContent').html(response.data.html);
                jQuery('#logDetailsModal').modal('show');
            }
        }
    });
}
</script>

<style>
.log-message {
    white-space: normal;
    word-break: break-word;
    max-width: 360px;
}
.file-path {
    max-width: 260px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.filters-toolbar {
    display: flex;
    align-items: flex-end;
    gap: 0.75rem;
    flex-wrap: nowrap;
    overflow-x: auto;
    padding-bottom: 0.5rem;
    width: 100%;
}
.filters-toolbar .filters-field {
    flex: 1 1 0;
    min-width: 180px;
}
.filters-toolbar .filters-actions {
    display: flex;
    flex: 0 0 auto;
    gap: 0.5rem;
    padding-bottom: 0.45rem;
}
[dir="rtl"] .filters-toolbar {
    flex-direction: row-reverse;
}
</style>

