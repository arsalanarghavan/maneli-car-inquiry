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
$is_admin = current_user_can('manage_maneli_inquiries');
$is_manager = in_array('maneli_manager', $current_user->roles, true) || in_array('maneli_admin', $current_user->roles, true);

if (!$is_admin && !$is_manager) {
    wp_redirect(home_url('/dashboard'));
    exit;
}

// Load Logger
$logger = Maneli_Logger::instance();

// Enqueue Persian Datepicker
if (!wp_script_is('maneli-persian-datepicker', 'enqueued')) {
    wp_enqueue_script('maneli-persian-datepicker', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/persianDatepicker.min.js', ['jquery'], '1.0.0', true);
    wp_enqueue_style('maneli-persian-datepicker', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/persianDatepicker-default.css', [], '1.0.0');
}

// Helper function to convert Jalali to Gregorian
if (!function_exists('maneli_jalali_to_gregorian')) {
    function maneli_jalali_to_gregorian($j_y, $j_m, $j_d) {
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
            if (function_exists('maneli_jalali_to_gregorian')) {
                list($gy, $gm, $gd) = maneli_jalali_to_gregorian($year, $month, $day);
                return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
            }
        }
    }
    
    return $date_str;
}

if (!function_exists('maneli_format_relative_path')) {
    function maneli_format_relative_path($path) {
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

if (!function_exists('maneli_to_persian_digits')) {
    function maneli_to_persian_digits($value) {
        $digits = array('0','1','2','3','4','5','6','7','8','9');
        $persian = array('۰','۱','۲','۳','۴','۵','۶','۷','۸','۹');
        return str_replace($digits, $persian, (string) $value);
    }
}

if (!function_exists('maneli_translate_log_label')) {
    function maneli_translate_log_label($label, $type = 'severity') {
        $maps = array(
            'severity' => array(
                'info' => __('اطلاعات', 'maneli-car-inquiry'),
                'warning' => __('هشدار', 'maneli-car-inquiry'),
                'error' => __('خطا', 'maneli-car-inquiry'),
                'critical' => __('بحرانی', 'maneli-car-inquiry'),
            ),
            'type' => array(
                'error' => __('خطا', 'maneli-car-inquiry'),
                'debug' => __('اشکال‌زدایی', 'maneli-car-inquiry'),
                'console' => __('کنسول', 'maneli-car-inquiry'),
                'button_error' => __('خطای دکمه', 'maneli-car-inquiry'),
            ),
        );
        $label = strtolower((string) $label);
        if (isset($maps[$type][$label])) {
            return $maps[$type][$label];
        }
        return $label;
    }
}

if (!function_exists('maneli_format_persian_datetime')) {
    function maneli_format_persian_datetime($datetime) {
        $timestamp = strtotime($datetime);
        if (!$timestamp) {
            return '';
        }
        $greg_date = date('Y-m-d H:i:s', $timestamp);
        $date_parts = explode(' ', $greg_date);
        $date = isset($date_parts[0]) ? $date_parts[0] : '';
        $time = isset($date_parts[1]) ? $date_parts[1] : '';

        if (!empty($date)) {
            $ymd = explode('-', $date);
            if (count($ymd) === 3) {
                $jalali_date = maneli_gregorian_to_jalali($ymd[0], $ymd[1], $ymd[2], 'Y/m/d');
            } else {
                $jalali_date = $date;
            }
        } else {
            $jalali_date = '';
        }

        $result = trim($jalali_date . ' ' . $time);
        return maneli_to_persian_digits($result);
    }
}

// Get filters
$log_type = isset($_GET['log_type']) ? sanitize_text_field($_GET['log_type']) : '';
$severity = isset($_GET['severity']) ? sanitize_text_field($_GET['severity']) : '';
$date_from_raw = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
$date_to_raw = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$page_num = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$options = get_option('maneli_inquiry_all_options', []);
$per_page = isset($options['max_logs_per_page']) ? max(10, intval($options['max_logs_per_page'])) : 50;
$offset = ($page_num - 1) * $per_page;

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
$total_logs_label = maneli_to_persian_digits(number_format($total_logs));
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
                    <li class="breadcrumb-item">
                        <a href="<?php echo esc_url(home_url('/dashboard/logs/system')); ?>"><?php esc_html_e('System Logs', 'maneli-car-inquiry'); ?></a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page"><?php esc_html_e('System Logs', 'maneli-car-inquiry'); ?></li>
                </ol>
                <h1 class="page-title fw-medium fs-18 mb-0"><?php esc_html_e('System Logs', 'maneli-car-inquiry'); ?></h1>
            </div>
        </div>
        <!-- End::page-header -->

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><?php esc_html_e('Filters', 'maneli-car-inquiry'); ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="get" action="<?php echo esc_url(home_url('/dashboard/logs/system')); ?>" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label"><?php esc_html_e('Log Type', 'maneli-car-inquiry'); ?></label>
                                <select name="log_type" class="form-select">
                                    <option value=""><?php esc_html_e('All Types', 'maneli-car-inquiry'); ?></option>
                                    <option value="error" <?php selected($log_type, 'error'); ?>><?php esc_html_e('Error', 'maneli-car-inquiry'); ?></option>
                                    <option value="debug" <?php selected($log_type, 'debug'); ?>><?php esc_html_e('Debug', 'maneli-car-inquiry'); ?></option>
                                    <option value="console" <?php selected($log_type, 'console'); ?>><?php esc_html_e('Console', 'maneli-car-inquiry'); ?></option>
                                    <option value="button_error" <?php selected($log_type, 'button_error'); ?>><?php esc_html_e('Button Error', 'maneli-car-inquiry'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label"><?php esc_html_e('Severity', 'maneli-car-inquiry'); ?></label>
                                <select name="severity" class="form-select">
                                    <option value=""><?php esc_html_e('All Severities', 'maneli-car-inquiry'); ?></option>
                                    <option value="info" <?php selected($severity, 'info'); ?>><?php esc_html_e('Info', 'maneli-car-inquiry'); ?></option>
                                    <option value="warning" <?php selected($severity, 'warning'); ?>><?php esc_html_e('Warning', 'maneli-car-inquiry'); ?></option>
                                    <option value="error" <?php selected($severity, 'error'); ?>><?php esc_html_e('Error', 'maneli-car-inquiry'); ?></option>
                                    <option value="critical" <?php selected($severity, 'critical'); ?>><?php esc_html_e('Critical', 'maneli-car-inquiry'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label"><?php esc_html_e('From Date', 'maneli-car-inquiry'); ?></label>
                                <input type="text" name="date_from" id="date-from-picker" class="form-control maneli-datepicker" value="<?php echo esc_attr($date_from_raw); ?>" placeholder="1403/01/01">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label"><?php esc_html_e('To Date', 'maneli-car-inquiry'); ?></label>
                                <input type="text" name="date_to" id="date-to-picker" class="form-control maneli-datepicker" value="<?php echo esc_attr($date_to_raw); ?>" placeholder="1403/01/01">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label"><?php esc_html_e('Search', 'maneli-car-inquiry'); ?></label>
                                <input type="text" name="search" class="form-control" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search...', 'maneli-car-inquiry'); ?>">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary"><?php esc_html_e('Filter', 'maneli-car-inquiry'); ?></button>
                                <a href="<?php echo esc_url(home_url('/dashboard/logs/system')); ?>" class="btn btn-secondary"><?php esc_html_e('Reset', 'maneli-car-inquiry'); ?></a>
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
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><?php esc_html_e('System Logs', 'maneli-car-inquiry'); ?></h5>
                        <span class="badge bg-primary"><?php echo esc_html($total_logs_label); ?> <?php esc_html_e('Logs', 'maneli-car-inquiry'); ?></span>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($logs)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover text-nowrap">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e('ID', 'maneli-car-inquiry'); ?></th>
                                            <th><?php esc_html_e('Type', 'maneli-car-inquiry'); ?></th>
                                            <th><?php esc_html_e('Severity', 'maneli-car-inquiry'); ?></th>
                                            <th><?php esc_html_e('Message', 'maneli-car-inquiry'); ?></th>
                                            <th><?php esc_html_e('File/Line', 'maneli-car-inquiry'); ?></th>
                                            <th><?php esc_html_e('User', 'maneli-car-inquiry'); ?></th>
                                            <th><?php esc_html_e('Date', 'maneli-car-inquiry'); ?></th>
                                            <th><?php esc_html_e('Actions', 'maneli-car-inquiry'); ?></th>
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
                                            $relative_path = maneli_format_relative_path($log->file);
                                            $user_display = '';
                                            $user_title = '';

                                            if ($user) {
                                                $user_display = $user->display_name;
                                                $user_title = sprintf(__('User ID: %d', 'maneli-car-inquiry'), $user->ID);
                                            } elseif (!empty($log->ip_address)) {
                                                $user_display = $log->ip_address;
                                                $user_title = $log->user_agent ?: '';
                                            } elseif (!empty($log->user_agent)) {
                                                $user_display = mb_strimwidth($log->user_agent, 0, 35, '…', 'UTF-8');
                                                $user_title = $log->user_agent;
                                            }
                                            ?>
                                            <tr>
                                                <td><?php echo esc_html(maneli_to_persian_digits($log->id)); ?></td>
                                                <td><span class="badge <?php echo esc_attr($log_type_class); ?>"><?php echo esc_html(maneli_translate_log_label($log->log_type, 'type')); ?></span></td>
                                                <td><span class="badge <?php echo esc_attr($severity_class); ?>"><?php echo esc_html(maneli_translate_log_label($log->severity, 'severity')); ?></span></td>
                                                <td>
                                                    <div class="log-message" title="<?php echo esc_attr($log->message); ?>">
                                                        <?php echo esc_html($log->message); ?>
                                                    </div>
                                                    <?php if ($context): ?>
                                                        <span class="badge bg-light text-dark mt-1" title="<?php echo esc_attr(json_encode($context, JSON_UNESCAPED_UNICODE)); ?>">
                                                            <?php esc_html_e('Context', 'maneli-car-inquiry'); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($relative_path): ?>
                                                        <div class="file-path" title="<?php echo esc_attr($log->file); ?>">
                                                        <?php echo esc_html($relative_path); ?>
                                                        </div>
                                                        <?php if ($log->line): ?>
                                                        <small class="text-muted"><?php echo esc_html(sprintf(__('Line %s', 'maneli-car-inquiry'), maneli_to_persian_digits($log->line))); ?></small>
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
                                                    <?php echo esc_html(maneli_format_persian_datetime($log->created_at)); ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" onclick="showLogDetails(<?php echo esc_js($log->id); ?>)">
                                                        <?php esc_html_e('Details', 'maneli-car-inquiry'); ?>
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
                                                <a class="page-link" href="<?php echo esc_url(add_query_arg('paged', $page_num - 1, $current_url)); ?>"><?php esc_html_e('Previous', 'maneli-car-inquiry'); ?></a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <?php if ($i == 1 || $i == $total_pages || ($i >= $page_num - 2 && $i <= $page_num + 2)): ?>
                                                <li class="page-item <?php echo $i == $page_num ? 'active' : ''; ?>">
                                                    <a class="page-link" href="<?php echo esc_url(add_query_arg('paged', $i, $current_url)); ?>"><?php echo esc_html(maneli_to_persian_digits($i)); ?></a>
                                                </li>
                                            <?php elseif ($i == $page_num - 3 || $i == $page_num + 3): ?>
                                                <li class="page-item disabled">
                                                    <span class="page-link">...</span>
                                                </li>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page_num < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="<?php echo esc_url(add_query_arg('paged', $page_num + 1, $current_url)); ?>"><?php esc_html_e('Next', 'maneli-car-inquiry'); ?></a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <?php esc_html_e('No logs found.', 'maneli-car-inquiry'); ?>
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
                <h5 class="modal-title"><?php esc_html_e('Log Details', 'maneli-car-inquiry'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="logDetailsContent">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('.file-path').each(function() {
        var $el = $(this);
        if (!$el.attr('title')) {
            $el.attr('title', $el.text());
        }
    });
});

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
    }
});

function showLogDetails(logId) {
    jQuery.ajax({
        url: '<?php echo admin_url('admin-ajax.php'); ?>',
        type: 'POST',
        data: {
            action: 'maneli_get_system_log_details',
            log_id: logId,
            security: '<?php echo wp_create_nonce('maneli_log_details_nonce'); ?>'
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
</style>

