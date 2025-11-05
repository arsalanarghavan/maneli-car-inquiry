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
                        <span class="badge bg-primary"><?php echo number_format($total_logs); ?> <?php esc_html_e('Logs', 'maneli-car-inquiry'); ?></span>
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
                                            $severity_class = 'badge-info';
                                            if ($log->severity === 'error') $severity_class = 'badge-danger';
                                            elseif ($log->severity === 'warning') $severity_class = 'badge-warning';
                                            elseif ($log->severity === 'critical') $severity_class = 'badge-danger';
                                            
                                            $log_type_class = 'badge-secondary';
                                            if ($log->log_type === 'error') $log_type_class = 'badge-danger';
                                            elseif ($log->log_type === 'debug') $log_type_class = 'badge-info';
                                            elseif ($log->log_type === 'console') $log_type_class = 'badge-primary';
                                            elseif ($log->log_type === 'button_error') $log_type_class = 'badge-warning';
                                            
                                            $context = $log->context ? json_decode($log->context, true) : null;
                                            ?>
                                            <tr>
                                                <td><?php echo esc_html($log->id); ?></td>
                                                <td><span class="badge <?php echo esc_attr($log_type_class); ?>"><?php echo esc_html(ucfirst($log->log_type)); ?></span></td>
                                                <td><span class="badge <?php echo esc_attr($severity_class); ?>"><?php echo esc_html(ucfirst($log->severity)); ?></span></td>
                                                <td>
                                                    <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo esc_attr($log->message); ?>">
                                                        <?php echo esc_html($log->message); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if ($log->file): ?>
                                                        <small><?php echo esc_html(basename($log->file)); ?><?php echo $log->line ? ':' . esc_html($log->line) : ''; ?></small>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($user): ?>
                                                        <?php echo esc_html($user->display_name); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $date_time = strtotime($log->created_at);
                                                    $greg_date = date('Y-m-d H:i:s', $date_time);
                                                    $date_parts = explode(' ', $greg_date);
                                                    $date_part = explode('-', $date_parts[0]);
                                                    $time_part = $date_parts[1] ?? '00:00:00';
                                                    $jalali_date = maneli_gregorian_to_jalali($date_part[0], $date_part[1], $date_part[2], 'Y/m/d');
                                                    echo esc_html($jalali_date . ' ' . $time_part);
                                                    ?>
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
                                                    <a class="page-link" href="<?php echo esc_url(add_query_arg('paged', $i, $current_url)); ?>"><?php echo $i; ?></a>
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

