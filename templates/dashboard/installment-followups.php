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

// Scripts and localization are handled by class-dashboard-handler.php
// Similar to cash-inquiries.php and installment-inquiries.php - no need to enqueue here
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

/* ============================================
   DARK MODE STYLES FOR INSTALLMENT FOLLOWUPS
   ============================================ */

[data-theme-mode=dark] .card.custom-card.crm-card {
    background: rgb(var(--body-bg-rgb)) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3) !important;
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme-mode=dark] .card.custom-card.crm-card h4 {
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme-mode=dark] .card.custom-card.crm-card:hover h4 {
    color: var(--primary-color) !important;
}

[data-theme-mode=dark] .card.custom-card.crm-card .text-muted,
[data-theme-mode=dark] .card.custom-card.crm-card p {
    color: rgba(255, 255, 255, 0.6) !important;
}

[data-theme-mode=dark] .card.custom-card {
    background: rgb(var(--body-bg-rgb)) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
}

[data-theme-mode=dark] .card.custom-card .card-header {
    border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
    background: transparent !important;
}

[data-theme-mode=dark] .card.custom-card .card-header .card-title {
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme-mode=dark] .card.custom-card .card-body {
    background: transparent !important;
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme-mode=dark] .card.custom-card .card-body.p-0 {
    background: transparent !important;
}

[data-theme-mode=dark] .p-3.border-bottom {
    border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
    background: transparent !important;
}

[data-theme-mode=dark] .form-label {
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme-mode=dark] .form-control,
[data-theme-mode=dark] .form-select {
    background: rgb(var(--body-bg-rgb)) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme-mode=dark] .form-control:focus,
[data-theme-mode=dark] .form-select:focus {
    background: rgb(var(--body-bg-rgb)) !important;
    border-color: var(--primary-color) !important;
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme-mode=dark] .form-control::placeholder {
    color: rgba(255, 255, 255, 0.5) !important;
}

[data-theme-mode=dark] .input-group-text {
    background: rgb(var(--body-bg-rgb)) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme-mode=dark] .table {
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme-mode=dark] .table thead {
    background: rgb(var(--body-bg-rgb)) !important;
}

[data-theme-mode=dark] .table thead th {
    color: rgba(255, 255, 255, 0.9) !important;
    border-bottom: 2px solid rgba(255, 255, 255, 0.1) !important;
    font-weight: 600 !important;
}

[data-theme-mode=dark] .table tbody tr {
    border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
}

[data-theme-mode=dark] .table tbody tr:hover {
    background: rgba(var(--primary-rgb), 0.1) !important;
}

[data-theme-mode=dark] .table tbody tr.table-danger {
    background: rgba(var(--danger-rgb), 0.15) !important;
}

[data-theme-mode=dark] .table tbody tr.table-danger:hover {
    background: rgba(var(--danger-rgb), 0.2) !important;
}

[data-theme-mode=dark] .table tbody td {
    color: rgba(255, 255, 255, 0.9) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}

[data-theme-mode=dark] .table tbody td .fw-medium {
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme-mode=dark] .table tbody td .text-muted {
    color: rgba(255, 255, 255, 0.6) !important;
}

[data-theme-mode=dark] .table tbody td .text-danger {
    color: rgb(var(--danger-rgb)) !important;
}

[data-theme-mode=dark] .table tbody td .text-success {
    color: rgb(var(--success-rgb)) !important;
}

[data-theme-mode=dark] .table tbody td strong {
    color: inherit !important;
}

[data-theme-mode=dark] .table tbody td small {
    color: rgba(255, 255, 255, 0.6) !important;
}

[data-theme-mode=dark] .table.text-center i {
    color: rgba(255, 255, 255, 0.5) !important;
}

[data-theme-mode=dark] .table.text-center p {
    color: rgba(255, 255, 255, 0.6) !important;
}

[data-theme-mode=dark] .btn-primary {
    background: var(--primary-color) !important;
    border-color: var(--primary-color) !important;
    color: white !important;
}

[data-theme-mode=dark] .btn-primary:hover {
    background: rgba(var(--primary-rgb), 0.8) !important;
    border-color: rgba(var(--primary-rgb), 0.8) !important;
}

[data-theme-mode=dark] .btn-primary-light {
    background: rgba(var(--primary-rgb), 0.1) !important;
    border-color: rgba(var(--primary-rgb), 0.2) !important;
    color: var(--primary-color) !important;
}

[data-theme-mode=dark] .btn-primary-light:hover {
    background: rgba(var(--primary-rgb), 0.2) !important;
    border-color: rgba(var(--primary-rgb), 0.3) !important;
    color: var(--primary-color) !important;
}

[data-theme-mode=dark] .btn-outline-secondary {
    border-color: rgba(255, 255, 255, 0.2) !important;
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme-mode=dark] .btn-outline-secondary:hover {
    background: rgba(255, 255, 255, 0.1) !important;
    border-color: rgba(255, 255, 255, 0.3) !important;
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme-mode=dark] .badge {
    color: white !important;
}

[data-theme-mode=dark] .badge.bg-primary {
    background: var(--primary-color) !important;
}

[data-theme-mode=dark] .badge.bg-warning-transparent {
    background: rgba(var(--warning-rgb), 0.15) !important;
    color: rgb(var(--warning-rgb)) !important;
    border: 1px solid rgba(var(--warning-rgb), 0.3) !important;
}

[data-theme-mode=dark] .badge.bg-danger-transparent {
    background: rgba(var(--danger-rgb), 0.15) !important;
    color: rgb(var(--danger-rgb)) !important;
    border: 1px solid rgba(var(--danger-rgb), 0.3) !important;
}

[data-theme-mode=dark] .badge.bg-info-transparent {
    background: rgba(var(--info-rgb), 0.15) !important;
    color: rgb(var(--info-rgb)) !important;
    border: 1px solid rgba(var(--info-rgb), 0.3) !important;
}

[data-theme-mode=dark] .badge.bg-primary-transparent {
    background: rgba(var(--primary-rgb), 0.15) !important;
    color: var(--primary-color) !important;
    border: 1px solid rgba(var(--primary-rgb), 0.3) !important;
}

[data-theme-mode=dark] .btn-icon {
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme-mode=dark] .btn-icon:hover {
    color: var(--primary-color) !important;
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
                                                <?php if ($is_admin || ($expert_id == $current_user_id)): 
                                                    if ($customer):
                                                        $customer_mobile = get_user_meta($customer->ID, 'billing_phone', true);
                                                        if (empty($customer_mobile)) {
                                                            $customer_mobile = get_user_meta($customer->ID, 'mobile_number', true);
                                                        }
                                                        if (empty($customer_mobile)) {
                                                            $customer_mobile = $customer->user_login;
                                                        }
                                                        if (!empty($customer_mobile)): ?>
                                                            <button class="btn btn-sm btn-success-light btn-icon send-sms-report-btn" 
                                                                    data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" 
                                                                    data-phone="<?php echo esc_attr($customer_mobile); ?>"
                                                                    data-customer-name="<?php echo esc_attr($customer->display_name ?? ''); ?>"
                                                                    data-inquiry-type="installment"
                                                                    title="<?php esc_attr_e('Send SMS', 'maneli-car-inquiry'); ?>">
                                                                <i class="la la-sms"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                    <button class="btn btn-sm btn-info-light btn-icon view-sms-history-btn" 
                                                            data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" 
                                                            data-inquiry-type="installment" 
                                                            title="<?php esc_attr_e('SMS History', 'maneli-car-inquiry'); ?>">
                                                        <i class="la la-history"></i>
                                                    </button>
                                                <?php endif; ?>
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

<!-- SMS History Modal -->
<div class="modal fade" id="sms-history-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="la la-sms me-2"></i>
                    <?php esc_html_e('SMS History', 'maneli-car-inquiry'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="sms-history-loading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden"><?php esc_html_e('Loading...', 'maneli-car-inquiry'); ?></span>
                    </div>
                    <p class="mt-2 text-muted"><?php esc_html_e('Loading SMS history...', 'maneli-car-inquiry'); ?></p>
                </div>
                <div id="sms-history-content" class="maneli-initially-hidden">
                    <div id="sms-history-table-container"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?php esc_html_e('Close', 'maneli-car-inquiry'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
// Global AJAX variables for SMS sending (same as users.php and installment-inquiries.php)
var maneliAjaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
var maneliAjaxNonce = '<?php echo wp_create_nonce('maneli-ajax-nonce'); ?>';

// Initialize maneliInquiryLists fallback if not already defined (before inquiry-lists.js loads)
if (typeof maneliInquiryLists === 'undefined') {
    window.maneliInquiryLists = {
        ajax_url: '<?php echo esc_js(admin_url('admin-ajax.php')); ?>',
        nonces: {
            ajax: '<?php echo esc_js(wp_create_nonce('maneli-ajax-nonce')); ?>'
        },
        text: {
            'error': '<?php echo esc_js(__('Error', 'maneli-car-inquiry')); ?>',
            'success': '<?php echo esc_js(__('Success', 'maneli-car-inquiry')); ?>',
            'send_sms': '<?php echo esc_js(__('Send SMS', 'maneli-car-inquiry')); ?>',
            'recipient': '<?php echo esc_js(__('Recipient:', 'maneli-car-inquiry')); ?>',
            'message': '<?php echo esc_js(__('Message:', 'maneli-car-inquiry')); ?>',
            'enter_message': '<?php echo esc_js(__('Enter your message...', 'maneli-car-inquiry')); ?>',
            'please_enter_message': '<?php echo esc_js(__('Please enter a message', 'maneli-car-inquiry')); ?>',
            'send': '<?php echo esc_js(__('Send', 'maneli-car-inquiry')); ?>',
            'cancel_button': '<?php echo esc_js(__('Cancel', 'maneli-car-inquiry')); ?>',
            'sending': '<?php echo esc_js(__('Sending...', 'maneli-car-inquiry')); ?>',
            'please_wait': '<?php echo esc_js(__('Please wait', 'maneli-car-inquiry')); ?>',
            'sms_sent_successfully': '<?php echo esc_js(__('SMS sent successfully!', 'maneli-car-inquiry')); ?>',
            'failed_to_send_sms': '<?php echo esc_js(__('Failed to send SMS', 'maneli-car-inquiry')); ?>',
            'server_error': '<?php echo esc_js(__('Server error. Please try again.', 'maneli-car-inquiry')); ?>',
            'invalid_inquiry_id': '<?php echo esc_js(__('Invalid inquiry ID.', 'maneli-car-inquiry')); ?>',
            'sms_history_modal_not_found': '<?php echo esc_js(__('SMS history modal not found.', 'maneli-car-inquiry')); ?>',
            'nonce_missing': '<?php echo esc_js(__('Nonce is missing. Please refresh the page and try again.', 'maneli-car-inquiry')); ?>',
            'no_sms_history': '<?php echo esc_js(__('No SMS messages have been sent for this inquiry yet.', 'maneli-car-inquiry')); ?>',
            'error_loading_history': '<?php echo esc_js(__('Error loading SMS history.', 'maneli-car-inquiry')); ?>',
            'missing_required_info': '<?php echo esc_js(__('Missing required information.', 'maneli-car-inquiry')); ?>',
            'ok_button': '<?php echo esc_js(__('OK', 'maneli-car-inquiry')); ?>',
            'resend_sms': '<?php echo esc_js(__('Resend SMS?', 'maneli-car-inquiry')); ?>',
            'resend_confirm': '<?php echo esc_js(__('Are you sure you want to resend this SMS?', 'maneli-car-inquiry')); ?>',
            'yes_resend': '<?php echo esc_js(__('Yes, Resend', 'maneli-car-inquiry')); ?>',
            'resend': '<?php echo esc_js(__('Resend', 'maneli-car-inquiry')); ?>',
            'sms_resent_successfully': '<?php echo esc_js(__('SMS resent successfully.', 'maneli-car-inquiry')); ?>',
            'failed_to_resend_sms': '<?php echo esc_js(__('Failed to resend SMS.', 'maneli-car-inquiry')); ?>',
            'check_status': '<?php echo esc_js(__('Check Status', 'maneli-car-inquiry')); ?>',
            'checking': '<?php echo esc_js(__('Checking...', 'maneli-car-inquiry')); ?>',
            'failed_to_get_status': '<?php echo esc_js(__('Failed to get status.', 'maneli-car-inquiry')); ?>',
            'error_checking_status': '<?php echo esc_js(__('Error checking status.', 'maneli-car-inquiry')); ?>',
            'status_unavailable': '<?php echo esc_js(__('Status unavailable', 'maneli-car-inquiry')); ?>',
            'check_failed': '<?php echo esc_js(__('Check failed', 'maneli-car-inquiry')); ?>'
        }
    };
}

// Debug: Verify script loading (wait for jQuery)
(function() {
    function waitForjQuery() {
        if (typeof jQuery !== 'undefined' || typeof window.jQuery !== 'undefined') {
            var $ = jQuery || window.jQuery;
            $(document).ready(function() {
                console.log('🟢 Installment Followups: Script loaded');
                console.log('🟢 maneliAjaxUrl:', typeof maneliAjaxUrl !== 'undefined' ? maneliAjaxUrl : 'MISSING');
                console.log('🟢 maneliAjaxNonce:', typeof maneliAjaxNonce !== 'undefined' ? (maneliAjaxNonce.substring(0, 10) + '...') : 'MISSING');
                console.log('🟢 maneliInquiryLists:', typeof maneliInquiryLists !== 'undefined' ? 'DEFINED' : 'UNDEFINED');
                console.log('🟢 Send SMS buttons:', $('.send-sms-report-btn').length);
                console.log('🟢 SMS History buttons:', $('.view-sms-history-btn').length);
                
                // Check if inquiry-lists.js is loaded
                setTimeout(function() {
                    console.log('🟢 Checking inquiry-lists.js...');
                    console.log('🟢 Swal available:', typeof Swal !== 'undefined');
                    
                    // Helper function for getting localized text
                    function getText(key, fallback) {
                        if (typeof maneliInquiryLists !== 'undefined' && maneliInquiryLists.text && maneliInquiryLists.text[key]) {
                            return maneliInquiryLists.text[key];
                        }
                        return fallback || key;
                    }
                    
                    // Main SMS send handler (from inquiry-lists.js)
                    $(document.body).off('click', '.send-sms-report-btn.followup').on('click', '.send-sms-report-btn.followup', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        var $btn = $(this);
                        var phone = $btn.data('phone');
                        var customerName = $btn.data('customer-name');
                        var inquiryId = $btn.data('inquiry-id');
                        
                        console.log('🟢 Send SMS button clicked:', {phone: phone, customerName: customerName, inquiryId: inquiryId});
                        
                        if (!phone || !inquiryId) {
                            console.error('Missing required data for SMS:', {phone: phone, inquiryId: inquiryId});
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    title: getText('error', 'Error'),
                                    text: getText('missing_required_info', 'Missing required information.'),
                                    icon: 'error'
                                });
                            }
                            return;
                        }
                        
                        if (typeof Swal === 'undefined') {
                            alert('SweetAlert is not loaded');
                            return;
                        }
                        
                        // Get AJAX URL and nonce
                        var ajaxUrl = typeof maneliAjaxUrl !== 'undefined' ? maneliAjaxUrl : 
                                     (typeof maneliInquiryLists !== 'undefined' ? maneliInquiryLists.ajax_url : '');
                        var ajaxNonce = typeof maneliAjaxNonce !== 'undefined' ? maneliAjaxNonce :
                                       (typeof maneliInquiryLists !== 'undefined' ? (maneliInquiryLists.nonces?.ajax || maneliInquiryLists.nonce || '') : '');
                        
                        if (!ajaxUrl) {
                            ajaxUrl = typeof adminAjax !== 'undefined' ? adminAjax.url : '';
                            if (!ajaxUrl && typeof ajaxurl !== 'undefined') {
                                ajaxUrl = ajaxurl;
                            }
                        }
                        
                        Swal.fire({
                            title: getText('send_sms', 'Send SMS'),
                            html: '<div class="text-start"><p><strong>' + getText('recipient', 'Recipient:') + '</strong> ' + (customerName || '') + ' (' + phone + ')</p><div class="mb-3"><label class="form-label">' + getText('message', 'Message:') + '</label><textarea id="sms-message" class="form-control" rows="5" placeholder="' + getText('enter_message', 'Enter your message...') + '"></textarea></div></div>',
                            showCancelButton: true,
                            confirmButtonText: getText('send', 'Send'),
                            cancelButtonText: getText('cancel_button', 'Cancel'),
                            preConfirm: function() {
                                var message = $('#sms-message').val();
                                if (!message || !message.trim()) {
                                    Swal.showValidationMessage(getText('please_enter_message', 'Please enter a message'));
                                    return false;
                                }
                                return { message: message };
                            }
                        }).then(function(result) {
                            if (result.isConfirmed && result.value) {
                                Swal.fire({
                                    title: getText('sending', 'Sending...'),
                                    text: getText('please_wait', 'Please wait'),
                                    allowOutsideClick: false,
                                    allowEscapeKey: false,
                                    showConfirmButton: false,
                                    didOpen: function() {
                                        Swal.showLoading();
                                    }
                                });
                                
                                if (!ajaxNonce) {
                                    console.error('❌ Nonce is missing!');
                                    Swal.close();
                                    Swal.fire({
                                        title: getText('error', 'Error'),
                                        text: getText('nonce_missing', 'Nonce is missing. Please refresh the page and try again.'),
                                        icon: 'error'
                                    });
                                    return;
                                }
                                
                                $.ajax({
                                    url: ajaxUrl,
                                    type: 'POST',
                                    data: {
                                        action: 'maneli_send_single_sms',
                                        nonce: ajaxNonce,
                                        recipient: phone,
                                        message: result.value.message,
                                        related_id: inquiryId
                                    },
                                    success: function(response) {
                                        Swal.close();
                                        if (response && response.success) {
                                            Swal.fire({
                                                title: getText('success', 'Success'),
                                                text: getText('sms_sent_successfully', 'SMS sent successfully!'),
                                                icon: 'success',
                                                confirmButtonText: getText('ok_button', 'OK')
                                            }).then(function() {
                                                location.reload();
                                            });
                                        } else {
                                            var errorMsg = (response && response.data && response.data.message) ? response.data.message : getText('failed_to_send_sms', 'Failed to send SMS');
                                            Swal.fire({
                                                title: getText('error', 'Error'),
                                                text: errorMsg,
                                                icon: 'error'
                                            });
                                        }
                                    },
                                    error: function(xhr, status, error) {
                                        Swal.close();
                                        console.error('SMS send error:', {xhr: xhr, status: status, error: error});
                                        Swal.fire({
                                            title: getText('error', 'Error'),
                                            text: getText('server_error', 'Server error. Please try again.'),
                                            icon: 'error'
                                        });
                                    }
                                });
                            }
                        });
                    });
                    
                    // SMS History handler (from inquiry-lists.js)
                    $(document.body).off('click', '.view-sms-history-btn.followup').on('click', '.view-sms-history-btn.followup', function() {
                        const button = $(this);
                        const inquiryId = button.data('inquiry-id');
                        const inquiryType = button.data('inquiry-type') || 'installment';
                        
                        if (!inquiryId) {
                            Swal.fire({
                                title: getText('error', 'Error'),
                                text: getText('invalid_inquiry_id', 'Invalid inquiry ID.'),
                                icon: 'error'
                            });
                            return;
                        }
                        
                        const modalElement = document.getElementById('sms-history-modal');
                        if (!modalElement) {
                            Swal.fire({
                                title: getText('error', 'Error'),
                                text: getText('sms_history_modal_not_found', 'SMS history modal not found.'),
                                icon: 'error'
                            });
                            return;
                        }
                        
                        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                            const modal = new bootstrap.Modal(modalElement);
                            modal.show();
                        } else if (typeof jQuery !== 'undefined' && jQuery(modalElement).modal) {
                            jQuery(modalElement).modal('show');
                        } else {
                            jQuery(modalElement).addClass('show').css('display', 'block');
                            jQuery('.modal-backdrop').remove();
                            jQuery('body').append('<div class="modal-backdrop fade show"></div>');
                        }
                        
                        $('#sms-history-loading').removeClass('maneli-initially-hidden').show();
                        $('#sms-history-content').addClass('maneli-initially-hidden').hide();
                        $('#sms-history-table-container').empty();
                        
                        var ajaxUrl = typeof maneliAjaxUrl !== 'undefined' ? maneliAjaxUrl : 
                                     (typeof maneliInquiryLists !== 'undefined' ? maneliInquiryLists.ajax_url : '');
                        var ajaxNonce = typeof maneliAjaxNonce !== 'undefined' ? maneliAjaxNonce :
                                       (typeof maneliInquiryLists !== 'undefined' ? (maneliInquiryLists.nonces?.ajax || maneliInquiryLists.nonce || '') : '');
                        
                        if (!ajaxUrl) {
                            ajaxUrl = typeof adminAjax !== 'undefined' ? adminAjax.url : '';
                            if (!ajaxUrl && typeof ajaxurl !== 'undefined') {
                                ajaxUrl = ajaxurl;
                            }
                        }
                        
                        if (!ajaxNonce) {
                            $('#sms-history-loading').addClass('maneli-initially-hidden').hide();
                            $('#sms-history-content').removeClass('maneli-initially-hidden').show();
                            $('#sms-history-table-container').html(
                                '<div class="alert alert-danger">' +
                                '<i class="la la-exclamation-triangle me-2"></i>' +
                                getText('nonce_missing', 'Nonce is missing. Please refresh the page and try again.') +
                                '</div>'
                            );
                            return;
                        }
                        
                        $.ajax({
                            url: ajaxUrl,
                            type: 'POST',
                            data: {
                                action: 'maneli_get_sms_history',
                                nonce: ajaxNonce,
                                inquiry_id: inquiryId,
                                inquiry_type: inquiryType
                            },
                            success: function(response) {
                                $('#sms-history-loading').addClass('maneli-initially-hidden').hide();
                                $('#sms-history-content').removeClass('maneli-initially-hidden').show();
                                
                                if (response && response.success && response.data && response.data.html) {
                                    $('#sms-history-table-container').html(response.data.html);
                                } else {
                                    $('#sms-history-table-container').html(
                                        '<div class="alert alert-info">' +
                                        '<i class="la la-info-circle me-2"></i>' +
                                        getText('no_sms_history', 'No SMS messages have been sent for this inquiry yet.') +
                                        '</div>'
                                    );
                                }
                            },
                            error: function(xhr, status, error) {
                                $('#sms-history-loading').addClass('maneli-initially-hidden').hide();
                                $('#sms-history-content').removeClass('maneli-initially-hidden').show();
                                $('#sms-history-table-container').html(
                                    '<div class="alert alert-danger">' +
                                    '<i class="la la-exclamation-triangle me-2"></i>' +
                                    getText('error_loading_history', 'Error loading SMS history.') +
                                    '</div>'
                                );
                        }
                        });
                    });
                    
                    // Add class to buttons so our handlers work
                    $('.send-sms-report-btn').addClass('followup');
                    $('.view-sms-history-btn').addClass('followup');
                    
                    console.log('🟢 SMS handlers attached to', $('.send-sms-report-btn.followup').length, 'send button(s) and', $('.view-sms-history-btn.followup').length, 'history button(s)');
                }, 1000);
            });
        } else {
            setTimeout(waitForjQuery, 50);
        }
    }
    waitForjQuery();
})();
</script>
