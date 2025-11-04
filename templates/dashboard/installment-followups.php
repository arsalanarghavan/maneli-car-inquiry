<?php
/**
 * installment-followups.php Dashboard Page
 * Shows installment inquiries with tracking_status = 'follow_up_scheduled'
 *
 * @package Maneli_Car_Inquiry
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="main-content app-content">
    <div class="container-fluid">

        <!-- Start::page-header -->
        <div class="d-flex align-items-center justify-content-between page-header-breadcrumb flex-wrap gap-2">
            <div>
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item">
                        <a href="<?php echo home_url('/dashboard'); ?>"><?php esc_html_e('Dashboard', 'maneli-car-inquiry'); ?></a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page"><?php esc_html_e('Installment Follow-ups', 'maneli-car-inquiry'); ?></li>
                </ol>
                <h1 class="page-title fw-medium fs-18 mb-0"><?php esc_html_e('Installment Follow-ups', 'maneli-car-inquiry'); ?></h1>
            </div>
        </div>
        <!-- End::page-header -->

<?php
/**
 * installment-followups Dashboard Page - Direct Implementation
 * Shows inquiries with tracking_status = 'follow_up_scheduled'
 * Accessible by: Admin, Expert (only their own)
 */

// Check permission
if (!current_user_can('manage_maneli_inquiries') && !in_array('maneli_expert', wp_get_current_user()->roles, true)) {
    ?>
    <div class="row">
        <div class="col-xl-12">
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="ri-exclamation-triangle me-2"></i>
                <strong><?php esc_html_e('Access Restricted!', 'maneli-car-inquiry'); ?></strong> <?php esc_html_e('You do not have access to this page.', 'maneli-car-inquiry'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    </div>
    <?php
    return;
}

// Get current user
$current_user_id = get_current_user_id();
$is_admin = current_user_can('manage_maneli_inquiries');

// Get experts for filter (admin only)
$experts = $is_admin ? get_users(['role' => 'maneli_expert', 'orderby' => 'display_name', 'order' => 'ASC']) : [];

// Query followup inquiries
$args = [
    'post_type' => 'inquiry',
    'posts_per_page' => 20,
    'orderby' => 'meta_value',
    'meta_key' => 'followup_date',
    'order' => 'ASC',
    'post_status' => 'publish',
    'meta_query' => [
        [
            'key' => 'tracking_status',
            'value' => 'follow_up_scheduled',
            'compare' => '='
        ]
    ]
];

// Filter by expert if not admin
if (!$is_admin) {
    $args['meta_query'][] = [
        'key' => 'assigned_expert_id',
        'value' => $current_user_id,
        'compare' => '='
    ];
}

$followups_query = new WP_Query($args);
$followups = $followups_query->posts;

// Calculate statistics
$today = current_time('Y-m-d');
$week_end = date('Y-m-d', strtotime('+7 days'));
$total_count = 0;
$today_count = 0;
$overdue_count = 0;
$week_count = 0;

foreach ($followups as $inquiry) {
    $follow_up_date = get_post_meta($inquiry->ID, 'followup_date', true);
    $total_count++;
    
    if ($follow_up_date) {
        if ($follow_up_date === $today) $today_count++;
        if ($follow_up_date < $today) $overdue_count++;
        if ($follow_up_date <= $week_end) $week_count++;
    }
}

// Enqueue assets for modals
if (!wp_script_is('select2', 'enqueued')) {
    wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0', true);
    wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0');
}

if (!wp_script_is('maneli-persian-datepicker', 'enqueued')) {
    wp_enqueue_script('maneli-persian-datepicker', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/persianDatepicker.min.js', ['jquery'], '1.0.0', true);
    wp_enqueue_style('maneli-persian-datepicker', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/persianDatepicker-default.css', [], '1.0.0');
}
?>

<style>
/* Installment Followups Statistics Cards - Inline Styles for Immediate Effect */
.card.custom-card.crm-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    border: 1px solid rgba(0, 0, 0, 0.06) !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08) !important;
    position: relative !important;
    overflow: hidden !important;
    border-radius: 0.5rem !important;
    background: #fff !important;
}

.card.custom-card.crm-card::before {
    content: '' !important;
    position: absolute !important;
    top: 0 !important;
    right: 0 !important;
    width: 100% !important;
    height: 100% !important;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0) 100%) !important;
    pointer-events: none !important;
    transition: opacity 0.3s ease !important;
    opacity: 0 !important;
}

.card.custom-card.crm-card:hover {
    transform: translateY(-4px) !important;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12) !important;
    border-color: rgba(0, 0, 0, 0.1) !important;
}

.card.custom-card.crm-card:hover::before {
    opacity: 1 !important;
}

.card.custom-card.crm-card .card-body {
    position: relative !important;
    z-index: 1 !important;
    padding: 1.5rem !important;
}

.card.custom-card.crm-card:hover .p-2 {
    transform: scale(1.1) !important;
}

.card.custom-card.crm-card:hover .avatar {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
}

.card.custom-card.crm-card h4 {
    font-weight: 700 !important;
    letter-spacing: -0.5px !important;
    font-size: 1.75rem !important;
    color: #1f2937 !important;
    transition: color 0.3s ease !important;
}

.card.custom-card.crm-card:hover h4 {
    color: #5e72e4 !important;
}

.card.custom-card.crm-card .border-primary,
.card.custom-card.crm-card .bg-primary {
    background: linear-gradient(135deg, #5e72e4 0%, #7c3aed 100%) !important;
}

.card.custom-card.crm-card .border-success,
.card.custom-card.crm-card .bg-success {
    background: linear-gradient(135deg, #2dce89 0%, #20c997 100%) !important;
}

.card.custom-card.crm-card .border-warning,
.card.custom-card.crm-card .bg-warning {
    background: linear-gradient(135deg, #fb6340 0%, #fbb140 100%) !important;
}

.card.custom-card.crm-card .border-secondary,
.card.custom-card.crm-card .bg-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%) !important;
}

.card.custom-card.crm-card .border-danger,
.card.custom-card.crm-card .bg-danger {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
}

.card.custom-card.crm-card .border-info,
.card.custom-card.crm-card .bg-info {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%) !important;
}
</style>

        <div class="row">
    <div class="col-xl-12">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-warning border-opacity-10 bg-warning-transparent rounded-pill">
                                <span class="avatar avatar-md avatar-rounded bg-warning svg-white">
                                    <i class="la la-clock fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e("Today's Followups", 'maneli-car-inquiry'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center"><?php echo function_exists('persian_numbers') ? persian_numbers(number_format_i18n($today_count)) : number_format_i18n($today_count); ?></h4>
                            <span class="badge bg-warning-transparent rounded-pill fs-11"><?php esc_html_e('Today', 'maneli-car-inquiry'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-danger border-opacity-10 bg-danger-transparent rounded-pill">
                                <span class="avatar avatar-md avatar-rounded bg-danger svg-white">
                                    <i class="la la-exclamation-triangle fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Overdue', 'maneli-car-inquiry'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center text-danger"><?php echo function_exists('persian_numbers') ? persian_numbers(number_format_i18n($overdue_count)) : number_format_i18n($overdue_count); ?></h4>
                            <span class="badge bg-danger-transparent rounded-pill fs-11"><?php esc_html_e('Overdue', 'maneli-car-inquiry'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-info border-opacity-10 bg-info-transparent rounded-pill">
                                <span class="avatar avatar-md avatar-rounded bg-info svg-white">
                                    <i class="la la-calendar-alt fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('This Week', 'maneli-car-inquiry'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center"><?php echo function_exists('persian_numbers') ? persian_numbers(number_format_i18n($week_count)) : number_format_i18n($week_count); ?></h4>
                            <span class="badge bg-info-transparent rounded-pill fs-11"><?php esc_html_e('Week', 'maneli-car-inquiry'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-primary border-opacity-10 bg-primary-transparent rounded-pill">
                                <span class="avatar avatar-md avatar-rounded bg-primary svg-white">
                                    <i class="la la-list-alt fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Total', 'maneli-car-inquiry'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center"><?php echo function_exists('persian_numbers') ? persian_numbers(number_format_i18n($total_count)) : number_format_i18n($total_count); ?></h4>
                            <span class="badge bg-primary-transparent rounded-pill fs-11"><?php esc_html_e('All', 'maneli-car-inquiry'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Card -->
        <div class="card custom-card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="card-title">
                    <?php esc_html_e('Installment Inquiry Follow-ups List', 'maneli-car-inquiry'); ?><span class="badge bg-primary rounded ms-2 fs-12 align-middle"><?php echo persian_numbers_no_separator($total_count); ?></span>
                </div>
            </div>
            <div class="card-body p-0">
                <!-- Filter Section -->
                <div class="p-3 border-bottom">
                    <form id="maneli-installment-followup-filter-form" onsubmit="return false;">
                        <div class="row g-3 mb-3">
                            <div class="col-md-12">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="la la-search"></i>
                                    </span>
                                    <input type="search" id="installment-followup-search-input" class="form-control" placeholder="<?php esc_attr_e('Search by customer name, car name...', 'maneli-car-inquiry'); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Filter Controls - All in one line -->
                        <div class="row g-2 align-items-end mb-3">
                            <!-- Status Filter -->
                            <div class="col">
                                <label class="form-label"><?php esc_html_e('Status:', 'maneli-car-inquiry'); ?></label>
                                <select id="installment-followup-status-filter" class="form-select form-select-sm">
                                    <option value=""><?php esc_html_e('All Statuses', 'maneli-car-inquiry'); ?></option>
                                    <?php foreach (Maneli_CPT_Handler::get_tracking_statuses() as $key => $label): ?>
                                        <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <?php if ($is_admin && !empty($experts)): ?>
                                <!-- Expert Filter -->
                                <div class="col">
                                    <label class="form-label"><?php esc_html_e('Expert:', 'maneli-car-inquiry'); ?></label>
                                    <select id="installment-followup-expert-filter" class="form-select form-select-sm maneli-select2">
                                        <option value=""><?php esc_html_e('All Experts', 'maneli-car-inquiry'); ?></option>
                                        <?php foreach ($experts as $expert) : ?>
                                            <option value="<?php echo esc_attr($expert->ID); ?>"><?php echo esc_html($expert->display_name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Sort Filter -->
                            <div class="col">
                                <label class="form-label"><?php esc_html_e('Sort:', 'maneli-car-inquiry'); ?></label>
                                <select id="installment-followup-sort-filter" class="form-select form-select-sm">
                                    <option value="date_asc"><?php esc_html_e('Follow-up Date (Earliest)', 'maneli-car-inquiry'); ?></option>
                                    <option value="date_desc"><?php esc_html_e('Follow-up Date (Latest)', 'maneli-car-inquiry'); ?></option>
                                    <option value="created_desc"><?php esc_html_e('Newest First', 'maneli-car-inquiry'); ?></option>
                                    <option value="created_asc"><?php esc_html_e('Oldest First', 'maneli-car-inquiry'); ?></option>
                                </select>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="col-auto">
                                <label class="form-label d-block" style="visibility: hidden;">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="button" id="installment-followup-reset-filters" class="btn btn-outline-secondary btn-sm">
                                        <i class="la la-refresh me-1"></i>
                                        <?php esc_html_e('Clear', 'maneli-car-inquiry'); ?>
                                    </button>
                                    <button type="button" id="installment-followup-apply-filters" class="btn btn-primary btn-sm">
                                        <i class="la la-filter me-1"></i>
                                        <?php esc_html_e('Apply', 'maneli-car-inquiry'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table text-nowrap table-hover">
                        <thead>
                            <tr>
                                <th scope="col"><?php esc_html_e('ID', 'maneli-car-inquiry'); ?></th>
                                <th scope="col"><?php esc_html_e('Customer', 'maneli-car-inquiry'); ?></th>
                                <th scope="col"><?php esc_html_e('Car', 'maneli-car-inquiry'); ?></th>
                                <th scope="col"><?php esc_html_e('Follow-up Date', 'maneli-car-inquiry'); ?></th>
                                <th scope="col"><?php esc_html_e('Status', 'maneli-car-inquiry'); ?></th>
                                <?php if ($is_admin): ?>
                                    <th scope="col"><?php esc_html_e('Expert', 'maneli-car-inquiry'); ?></th>
                                <?php endif; ?>
                                <th scope="col"><?php esc_html_e('Registration Date', 'maneli-car-inquiry'); ?></th>
                                <th scope="col"><?php esc_html_e('Actions', 'maneli-car-inquiry'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($followups)): ?>
                                <tr>
                                    <td colspan="<?php echo $is_admin ? '8' : '7'; ?>" class="text-center py-4">
                                        <i class="la la-inbox fs-24 text-muted"></i>
                                        <p class="text-muted mt-3"><?php esc_html_e('No follow-ups found.', 'maneli-car-inquiry'); ?></p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($followups as $inquiry): 
                                    $inquiry_id = $inquiry->ID;
                                    $customer = get_userdata($inquiry->post_author);
                                    $product_id = get_post_meta($inquiry_id, 'product_id', true);
                                    $follow_up_date = get_post_meta($inquiry_id, 'followup_date', true);
                                    $tracking_status = get_post_meta($inquiry_id, 'tracking_status', true);
                                    $tracking_status_label = Maneli_CPT_Handler::get_tracking_status_label($tracking_status);
                                    $expert_id = get_post_meta($inquiry_id, 'assigned_expert_id', true);
                                    $expert = $expert_id ? get_userdata($expert_id) : null;
                                    
                                    // Get followup history
                                    $followup_history = get_post_meta($inquiry_id, 'followup_history', true) ?: [];
                                    
                                    // Convert dates to Jalali
                                    $created_timestamp = strtotime($inquiry->post_date);
                                    if (function_exists('maneli_gregorian_to_jalali')) {
                                        $created_date = maneli_gregorian_to_jalali(
                                            date('Y', $created_timestamp),
                                            date('m', $created_timestamp),
                                            date('d', $created_timestamp),
                                            'Y/m/d'
                                        );
                                    } else {
                                        $created_date = date('Y/m/d', $created_timestamp);
                                    }
                                    $created_date = function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($created_date) : $created_date;
                                    
                                    // Status badge color
                                    $status_class_map = [
                                        'new' => 'secondary',
                                        'referred' => 'info',
                                        'in_progress' => 'primary',
                                        'meeting_scheduled' => 'cyan',
                                        'follow_up_scheduled' => 'warning',
                                        'cancelled' => 'danger',
                                        'completed' => 'dark',
                                        'rejected' => 'danger',
                                    ];
                                    $status_class = $status_class_map[$tracking_status] ?? 'secondary';
                                    
                                    // Overdue check
                                    $is_overdue = $follow_up_date && $follow_up_date < $today;
                                    $row_class = $is_overdue ? 'table-danger' : '';
                                ?>
                                    <tr class="crm-contact contacts-list <?php echo $row_class; ?>">
                                        <td data-title="<?php esc_attr_e('ID', 'maneli-car-inquiry'); ?>">
                                            <span class="fw-medium">#<?php echo persian_numbers_no_separator($inquiry_id); ?></span>
                                        </td>
                                        <td data-title="<?php esc_attr_e('Customer', 'maneli-car-inquiry'); ?>">
                                            <div class="d-flex align-items-center gap-2">
                                                <div>
                                                    <span class="d-block fw-medium"><?php echo esc_html($customer ? $customer->display_name : esc_html__('Unknown', 'maneli-car-inquiry')); ?></span>
                                                    <span class="d-block text-muted fs-11">
                                                        <i class="la la-user me-1 fs-13 align-middle"></i><?php echo esc_html($created_date); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td data-title="<?php esc_attr_e('Car', 'maneli-car-inquiry'); ?>"><?php echo esc_html(get_the_title($product_id)); ?></td>
                                        <td data-title="<?php esc_attr_e('Follow-up Date', 'maneli-car-inquiry'); ?>">
                                            <?php if ($follow_up_date): 
                                                $follow_up_date_persian = function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($follow_up_date) : $follow_up_date;
                                            ?>
                                                <strong class="<?php echo $is_overdue ? 'text-danger' : 'text-success'; ?>">
                                                    <?php echo esc_html($follow_up_date_persian); ?>
                                                </strong>
                                                <?php if ($is_overdue): ?>
                                                    <br><span class="badge bg-danger-transparent"><?php esc_html_e('Overdue', 'maneli-car-inquiry'); ?></span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                        <td data-title="<?php esc_attr_e('Status', 'maneli-car-inquiry'); ?>">
                                            <span class="badge bg-warning-transparent">
                                                <?php echo esc_html($tracking_status_label); ?>
                                            </span>
                                            <?php if (!empty($followup_history)): ?>
                                                <br><small class="text-muted fs-11">
                                                    (<?php printf(esc_html__('%s previous follow-ups', 'maneli-car-inquiry'), persian_numbers_no_separator(count($followup_history))); ?>)
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <?php if ($is_admin): ?>
                                            <td data-title="<?php esc_attr_e('Expert', 'maneli-car-inquiry'); ?>">
                                                <?php echo $expert ? esc_html($expert->display_name) : '<span class="text-muted">—</span>'; ?>
                                            </td>
                                        <?php endif; ?>
                                        <td data-title="<?php esc_attr_e('Registration Date', 'maneli-car-inquiry'); ?>"><?php echo esc_html($created_date); ?></td>
                                        <td data-title="<?php esc_attr_e('Actions', 'maneli-car-inquiry'); ?>">
                                            <div class="btn-list">
                                                <a href="<?php echo add_query_arg('inquiry_id', $inquiry_id, home_url('/dashboard/installment-inquiries')); ?>" 
                                                   class="btn btn-sm btn-primary-light btn-icon" title="<?php esc_attr_e('View', 'maneli-car-inquiry'); ?>">
                                                    <i class="la la-eye"></i>
                                                </a>
                                            </div>
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
<!-- End::row -->
</div>
</div>
