<?php
/**
 * User Logs Page
 * Displays user activity logs including button clicks, form submissions, and AJAX calls
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
$user_id_filter = isset($_GET['user_id']) ? intval($_GET['user_id']) : '';
$action_type = isset($_GET['action_type']) ? sanitize_text_field($_GET['action_type']) : '';
$target_type = isset($_GET['target_type']) ? sanitize_text_field($_GET['target_type']) : '';
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
    'user_id' => $user_id_filter,
    'action_type' => $action_type,
    'target_type' => $target_type,
    'date_from' => $date_from,
    'date_to' => $date_to,
    'search' => $search,
    'limit' => $per_page,
    'offset' => $offset,
);

$logs = $logger->get_user_logs($args);
$total_logs = $logger->get_user_logs_count($args);
$total_pages = ceil($total_logs / $per_page);

// Get all users for filter
$users = get_users(array('fields' => array('ID', 'display_name')));
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
                        <a href="<?php echo esc_url(home_url('/dashboard/logs/user')); ?>"><?php esc_html_e('User Logs', 'maneli-car-inquiry'); ?></a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page"><?php esc_html_e('User Logs', 'maneli-car-inquiry'); ?></li>
                </ol>
                <h1 class="page-title fw-medium fs-18 mb-0"><?php esc_html_e('User Logs', 'maneli-car-inquiry'); ?></h1>
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
                        <form method="get" action="<?php echo esc_url(home_url('/dashboard/logs/user')); ?>" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label"><?php esc_html_e('User', 'maneli-car-inquiry'); ?></label>
                                <select name="user_id" class="form-select">
                                    <option value=""><?php esc_html_e('All Users', 'maneli-car-inquiry'); ?></option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($user_id_filter, $user->ID); ?>>
                                            <?php echo esc_html($user->display_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label"><?php esc_html_e('Action Type', 'maneli-car-inquiry'); ?></label>
                                <select name="action_type" class="form-select">
                                    <option value=""><?php esc_html_e('All Actions', 'maneli-car-inquiry'); ?></option>
                                    <option value="button_click" <?php selected($action_type, 'button_click'); ?>><?php esc_html_e('Button Click', 'maneli-car-inquiry'); ?></option>
                                    <option value="form_submit" <?php selected($action_type, 'form_submit'); ?>><?php esc_html_e('Form Submit', 'maneli-car-inquiry'); ?></option>
                                    <option value="ajax_call" <?php selected($action_type, 'ajax_call'); ?>><?php esc_html_e('AJAX Call', 'maneli-car-inquiry'); ?></option>
                                    <option value="page_view" <?php selected($action_type, 'page_view'); ?>><?php esc_html_e('Page View', 'maneli-car-inquiry'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label"><?php esc_html_e('Target Type', 'maneli-car-inquiry'); ?></label>
                                <select name="target_type" class="form-select">
                                    <option value=""><?php esc_html_e('All Types', 'maneli-car-inquiry'); ?></option>
                                    <option value="inquiry" <?php selected($target_type, 'inquiry'); ?>><?php esc_html_e('Inquiry', 'maneli-car-inquiry'); ?></option>
                                    <option value="user" <?php selected($target_type, 'user'); ?>><?php esc_html_e('User', 'maneli-car-inquiry'); ?></option>
                                    <option value="product" <?php selected($target_type, 'product'); ?>><?php esc_html_e('Product', 'maneli-car-inquiry'); ?></option>
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
                            <div class="col-md-3">
                                <label class="form-label"><?php esc_html_e('Search', 'maneli-car-inquiry'); ?></label>
                                <input type="text" name="search" class="form-control" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search...', 'maneli-car-inquiry'); ?>">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary"><?php esc_html_e('Filter', 'maneli-car-inquiry'); ?></button>
                                <a href="<?php echo esc_url(home_url('/dashboard/logs/user')); ?>" class="btn btn-secondary"><?php esc_html_e('Reset', 'maneli-car-inquiry'); ?></a>
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
                        <h5 class="card-title mb-0"><?php esc_html_e('User Activity Logs', 'maneli-car-inquiry'); ?></h5>
                        <span class="badge bg-primary"><?php echo number_format($total_logs); ?> <?php esc_html_e('Logs', 'maneli-car-inquiry'); ?></span>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($logs)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover text-nowrap">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e('ID', 'maneli-car-inquiry'); ?></th>
                                            <th><?php esc_html_e('User', 'maneli-car-inquiry'); ?></th>
                                            <th><?php esc_html_e('Action Type', 'maneli-car-inquiry'); ?></th>
                                            <th><?php esc_html_e('Description', 'maneli-car-inquiry'); ?></th>
                                            <th><?php esc_html_e('Target', 'maneli-car-inquiry'); ?></th>
                                            <th><?php esc_html_e('IP Address', 'maneli-car-inquiry'); ?></th>
                                            <th><?php esc_html_e('Date', 'maneli-car-inquiry'); ?></th>
                                            <th><?php esc_html_e('Actions', 'maneli-car-inquiry'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($logs as $log): ?>
                                            <?php
                                            $user = get_user_by('ID', $log->user_id);
                                            $metadata = $log->metadata ? json_decode($log->metadata, true) : null;
                                            
                                            $action_type_class = 'badge-secondary';
                                            if ($log->action_type === 'button_click') $action_type_class = 'badge-primary';
                                            elseif ($log->action_type === 'form_submit') $action_type_class = 'badge-success';
                                            elseif ($log->action_type === 'ajax_call') $action_type_class = 'badge-info';
                                            ?>
                                            <tr>
                                                <td><?php echo esc_html($log->id); ?></td>
                                                <td>
                                                    <?php if ($user): ?>
                                                        <?php echo esc_html($user->display_name); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted"><?php echo esc_html($log->user_id); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><span class="badge <?php echo esc_attr($action_type_class); ?>"><?php echo esc_html(str_replace('_', ' ', ucwords($log->action_type, '_'))); ?></span></td>
                                                <td>
                                                    <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo esc_attr($log->action_description); ?>">
                                                        <?php echo esc_html($log->action_description); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if ($log->target_type && $log->target_id): ?>
                                                        <span class="badge bg-info-transparent"><?php echo esc_html(ucfirst($log->target_type)); ?> #<?php echo esc_html($log->target_id); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><small><?php echo esc_html($log->ip_address); ?></small></td>
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
                                            'user_id' => $user_id_filter,
                                            'action_type' => $action_type,
                                            'target_type' => $target_type,
                                            'date_from' => $date_from_raw,
                                            'date_to' => $date_to_raw,
                                            'search' => $search,
                                        ), home_url('/dashboard/logs/user'));
                                        
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
(function() {
    function initUserLogsDatepickers() {
        if (typeof jQuery === 'undefined') {
            setTimeout(initUserLogsDatepickers, 100);
            return;
        }

        jQuery(function($) {
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
                        console.warn('persianDatepicker plugin was not loaded in time on user-logs page.');
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
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initUserLogsDatepickers);
    } else {
        initUserLogsDatepickers();
    }
})();

function showLogDetails(logId) {
    jQuery.ajax({
        url: '<?php echo admin_url('admin-ajax.php'); ?>',
        type: 'POST',
        data: {
            action: 'maneli_get_user_log_details',
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

