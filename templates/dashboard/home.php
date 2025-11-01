<?php
/**
 * Premium Dashboard Home - Advanced Implementation
 * خفن‌ترین داشبورد ممکن برای ادمین، کارشناس و مشتری
 *
 * @package Maneli_Car_Inquiry
 */

if (!defined('ABSPATH')) {
    exit;
}

// CRITICAL: Always get fresh role from WordPress user object
$current_user = wp_get_current_user();
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login'));
    exit;
}

// Always check fresh roles from database
$is_admin = current_user_can('manage_maneli_inquiries');
$is_manager = in_array('maneli_manager', $current_user->roles, true) || in_array('maneli_admin', $current_user->roles, true);
$is_expert = in_array('maneli_expert', $current_user->roles, true);
$is_customer = !$is_admin && !$is_manager && !$is_expert;

// Load Reports Dashboard class
require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-reports-dashboard.php';

// Date range (last 30 days)
$start_date = date('Y-m-d', strtotime('-30 days'));
$end_date = date('Y-m-d');


if ($is_customer) {
    // ════════════════════════════════════════════════════════════
    // CUSTOMER DASHBOARD
    // ════════════════════════════════════════════════════════════
    
    // Get customer's inquiries
    $user_id = get_current_user_id();
    
    $cash_inquiries = get_posts([
        'post_type' => 'cash_inquiry',
        'author' => $user_id,
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ]);
    
    $installment_inquiries = get_posts([
        'post_type' => 'inquiry',
        'author' => $user_id,
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ]);
    
    // Count by status
    $pending_count = 0;
    $approved_count = 0;
    $rejected_count = 0;
    $total_count = count($cash_inquiries) + count($installment_inquiries);
    
    foreach ($cash_inquiries as $inq) {
        $status = get_post_meta($inq->ID, 'cash_inquiry_status', true);
        // Convert 'pending' to 'new' automatically
        if ($status === 'pending') {
            $status = 'new';
            update_post_meta($inq->ID, 'cash_inquiry_status', 'new');
        }
        if ($status === 'new' || $status === 'pending') $pending_count++;
        elseif ($status === 'approved' || $status === 'completed') $approved_count++;
        elseif ($status === 'rejected') $rejected_count++;
    }
    
    foreach ($installment_inquiries as $inq) {
        $tracking_status = get_post_meta($inq->ID, 'tracking_status', true) ?: 'new';
        if ($tracking_status === 'new' || $tracking_status === 'referred' || $tracking_status === 'in_progress') $pending_count++;
        elseif ($tracking_status === 'completed') $approved_count++;
        elseif ($tracking_status === 'rejected' || $tracking_status === 'cancelled') $rejected_count++;
    }
    
    // Get recent inquiries (both types, last 5)
    $all_recent = array_merge($cash_inquiries, $installment_inquiries);
    usort($all_recent, function($a, $b) {
        return strtotime($b->post_date) - strtotime($a->post_date);
    });
    $recent_inquiries = array_slice($all_recent, 0, 5);
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
                    <li class="breadcrumb-item active" aria-current="page"><?php esc_html_e('Home', 'maneli-car-inquiry'); ?></li>
                </ol>
            </nav>
            <h1 class="page-title fw-medium fs-18 mb-0"><?php printf(esc_html__('Welcome, %s', 'maneli-car-inquiry'), esc_html($current_user->display_name)); ?></h1>
            </div>
        </div>
        <!-- End::page-header -->

    <!-- Start::row-1 - Statistics Cards -->
        <div class="row">
        <div class="col-md-6 col-lg-4 col-xl">
            <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <div class="p-2 border border-primary border-opacity-10 bg-primary-transparent rounded-pill">
                            <span class="avatar avatar-md avatar-rounded bg-primary svg-white">
                                <i class="la la-list-alt fs-20"></i>
                                </span>
                            </div>
                        </div>
                    <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Total Inquiries', 'maneli-car-inquiry'); ?></p>
                    <div class="d-flex align-items-center justify-content-between mt-1">
                        <h4 class="mb-0 d-flex align-items-center"><?php echo maneli_number_format_persian($total_count); ?></h4>
                        <span class="badge bg-primary-transparent rounded-pill fs-11"><?php echo maneli_number_format_persian(count($cash_inquiries)); ?> <?php esc_html_e('Cash', 'maneli-car-inquiry'); ?></span>
                    </div>
                </div>
            </div>
                            </div>
        <div class="col-md-6 col-lg-4 col-xl">
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
                        <h4 class="mb-0 d-flex align-items-center text-warning"><?php echo maneli_number_format_persian($pending_count); ?></h4>
                        <span class="text-warning badge bg-warning-transparent rounded-pill d-flex align-items-center fs-11">
                            <i class="la la-hourglass-half fs-11"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 col-xl">
            <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <div class="p-2 border border-success border-opacity-10 bg-success-transparent rounded-circle">
                            <span class="avatar avatar-md avatar-rounded bg-success svg-white">
                                <i class="la la-check-circle fs-20"></i>
                            </span>
                            </div>
                    </div>
                    <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Approved', 'maneli-car-inquiry'); ?></p>
                    <div class="d-flex align-items-center justify-content-between mt-1">
                        <h4 class="mb-0 d-flex align-items-center text-success"><?php echo maneli_number_format_persian($approved_count); ?></h4>
                        <span class="text-success badge bg-success-transparent rounded-pill d-flex align-items-center fs-11">
                            <i class="la la-check-double fs-11"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
        <div class="col-md-6 col-lg-4 col-xl">
            <div class="card custom-card crm-card overflow-hidden">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <div class="p-2 border border-danger border-opacity-10 bg-danger-transparent rounded-circle">
                            <span class="avatar avatar-md avatar-rounded bg-danger svg-white">
                                <i class="la la-times-circle fs-20"></i>
                            </span>
            </div>
                    </div>
                    <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Rejected', 'maneli-car-inquiry'); ?></p>
                    <div class="d-flex align-items-center justify-content-between mt-1">
                        <h4 class="mb-0 d-flex align-items-center text-danger"><?php echo maneli_number_format_persian($rejected_count); ?></h4>
                        <span class="text-danger badge bg-danger-transparent rounded-pill d-flex align-items-center fs-11">
                            <i class="la la-ban fs-11"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 col-xl">
            <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <div class="p-2 border border-info border-opacity-10 bg-info-transparent rounded-circle">
                            <span class="avatar avatar-md avatar-rounded bg-info svg-white">
                                <i class="la la-credit-card fs-20"></i>
                            </span>
                        </div>
                    </div>
                    <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Installment', 'maneli-car-inquiry'); ?></p>
                    <div class="d-flex align-items-center justify-content-between mt-1">
                        <h4 class="mb-0 d-flex align-items-center text-info"><?php echo maneli_number_format_persian(count($installment_inquiries)); ?></h4>
                        <span class="badge bg-info-transparent rounded-pill fs-11"><?php echo $total_count > 0 ? maneli_number_format_persian(round((count($installment_inquiries) / $total_count) * 100), 1) : '۰'; ?>%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-1 -->
            
    <!-- Start::row-2 - Quick Actions -->
    <div class="row mb-4">
        <div class="col-xl-6 col-lg-12">
            <div class="card custom-card overflow-hidden border-success">
                <div class="card-body bg-success-transparent">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="flex-fill">
                            <div class="d-flex align-items-center mb-2">
                                <span class="avatar avatar-md avatar-rounded bg-success me-3">
                                    <i class="la la-dollar-sign fs-24 text-white"></i>
                                </span>
                            <div>
                                    <h5 class="mb-1 fw-semibold"><?php esc_html_e('Cash Car Purchase', 'maneli-car-inquiry'); ?></h5>
                                    <p class="text-muted mb-0 fs-12"><?php esc_html_e('The fastest way to buy a car with cash', 'maneli-car-inquiry'); ?></p>
                            </div>
                            </div>
                            <a href="<?php echo esc_url(home_url('/dashboard/new-cash-inquiry')); ?>" class="btn btn-success btn-wave btn-sm mt-2">
                                <i class="la la-plus-circle me-1"></i>
                                <?php esc_html_e('Create New Cash Request', 'maneli-car-inquiry'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6 col-lg-12">
            <div class="card custom-card overflow-hidden border-info">
                <div class="card-body bg-info-transparent">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="flex-fill">
                            <div class="d-flex align-items-center mb-2">
                                <span class="avatar avatar-md avatar-rounded bg-info me-3">
                                    <i class="la la-credit-card fs-24 text-white"></i>
                                </span>
                                <div>
                                    <h5 class="mb-1 fw-semibold"><?php esc_html_e('Installment Car Purchase', 'maneli-car-inquiry'); ?></h5>
                                    <p class="text-muted mb-0 fs-12"><?php esc_html_e('Buy your dream car with easy installments', 'maneli-car-inquiry'); ?></p>
                            </div>
                        </div>
                            <a href="<?php echo esc_url(home_url('/dashboard/new-inquiry')); ?>" class="btn btn-info btn-wave btn-sm mt-2">
                                <i class="la la-plus-circle me-1"></i>
                                <?php esc_html_e('Create New Installment Request', 'maneli-car-inquiry'); ?>
                            </a>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
    <!-- End::row-2 -->

    <!-- Start::row-3 - Recent Inquiries -->
    <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                    <div class="card-title">
                        <i class="la la-history me-2"></i>
                        <?php esc_html_e('Your Recent Inquiries', 'maneli-car-inquiry'); ?>
                    </div>
                    <?php if (!empty($recent_inquiries)): ?>
                    <div>
                        <a href="<?php echo esc_url(home_url('/dashboard/inquiries')); ?>" class="btn btn-sm btn-primary-light">
                            <?php esc_html_e('View All', 'maneli-car-inquiry'); ?>
                            <i class="la la-arrow-left ms-1"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recent_inquiries)): ?>
                        <div class="text-center py-5">
                            <span class="avatar avatar-xl avatar-rounded bg-secondary-transparent mb-3">
                                <i class="la la-inbox fs-40 text-muted"></i>
                            </span>
                            <h6 class="fw-medium mb-2"><?php esc_html_e('No Inquiries Yet', 'maneli-car-inquiry'); ?></h6>
                            <p class="text-muted mb-4"><?php esc_html_e('You can create your first inquiry', 'maneli-car-inquiry'); ?></p>
                            <a href="<?php echo esc_url(home_url('/dashboard/new-inquiry')); ?>" class="btn btn-primary btn-wave">
                                <i class="la la-plus me-1"></i>
                                <?php esc_html_e('Create First Inquiry', 'maneli-car-inquiry'); ?>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table text-nowrap table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col"><?php esc_html_e('ID', 'maneli-car-inquiry'); ?></th>
                                        <th scope="col"><?php esc_html_e('Type', 'maneli-car-inquiry'); ?></th>
                                        <th scope="col"><?php esc_html_e('Car', 'maneli-car-inquiry'); ?></th>
                                        <th scope="col"><?php esc_html_e('Status', 'maneli-car-inquiry'); ?></th>
                                        <th scope="col"><?php esc_html_e('Date', 'maneli-car-inquiry'); ?></th>
                                        <th scope="col"><?php esc_html_e('Actions', 'maneli-car-inquiry'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_inquiries as $inq):
                                        $post_type = get_post_type($inq);
                                        $is_cash = ($post_type === 'cash_inquiry');
                                        if ($is_cash) {
                                            $status = get_post_meta($inq->ID, 'cash_inquiry_status', true);
                                        } else {
                                            $status = get_post_meta($inq->ID, 'tracking_status', true) ?: 'new';
                                        }
                                        $product_id = get_post_meta($inq->ID, 'product_id', true);
                                        
                                        // Get status badges from Maneli_CPT_Handler for consistency
                                        if ($is_cash) {
                                            $all_cash_statuses = Maneli_CPT_Handler::get_all_cash_inquiry_statuses();
                                            $status_label = $all_cash_statuses[$status] ?? esc_html__('Unknown', 'maneli-car-inquiry');
                                            // Map status to badge class
                                            $status_class_map = [
                                                'new' => 'primary',
                                                'referred' => 'info',
                                                'in_progress' => 'warning',
                                                'follow_up_scheduled' => 'info',
                                                'awaiting_downpayment' => 'warning',
                                                'downpayment_received' => 'success',
                                                'meeting_scheduled' => 'info',
                                                'approved' => 'success',
                                                'completed' => 'success',
                                                'rejected' => 'danger',
                                            ];
                                            $badge_class = $status_class_map[$status] ?? 'secondary';
                                            $badge = ['label' => $status_label, 'class' => $badge_class];
                                        } else {
                                            $all_tracking_statuses = Maneli_CPT_Handler::get_tracking_statuses();
                                            $status_label = $all_tracking_statuses[$status] ?? esc_html__('Unknown', 'maneli-car-inquiry');
                                            // Map status to badge class
                                            $status_class_map = [
                                                'new' => 'primary',
                                                'referred' => 'info',
                                                'in_progress' => 'warning',
                                                'meeting_scheduled' => 'info',
                                                'follow_up_scheduled' => 'info',
                                                'user_confirmed' => 'success',
                                                'completed' => 'success',
                                                'rejected' => 'danger',
                                                'cancelled' => 'secondary',
                                            ];
                                            $badge_class = $status_class_map[$status] ?? 'secondary';
                                            $badge = ['label' => $status_label, 'class' => $badge_class];
                                        }
                                        
                                        $timestamp = strtotime($inq->post_date);
                                        if (function_exists('maneli_gregorian_to_jalali')) {
                                            $date = maneli_gregorian_to_jalali(
                                                date('Y', $timestamp),
                                                date('m', $timestamp),
                                                date('d', $timestamp),
                                                'Y/m/d'
                                            );
                                        } else {
                                            $date = date('Y/m/d', $timestamp);
                                        }
                                        
                                        $view_url = $is_cash 
                                            ? add_query_arg('cash_inquiry_id', $inq->ID, home_url('/dashboard/inquiries/cash'))
                                            : add_query_arg('inquiry_id', $inq->ID, home_url('/dashboard/inquiries/installment'));
                                    ?>
                                        <tr>
                                            <td><strong>#<?php echo $inq->ID; ?></strong></td>
                                            <td>
                                                <span class="badge bg-<?php echo $is_cash ? 'warning' : 'info'; ?>-transparent">
                                                    <?php echo $is_cash ? esc_html__('Cash', 'maneli-car-inquiry') : esc_html__('Installment', 'maneli-car-inquiry'); ?>
                                                </span>
                                            </td>
                                            <td><?php echo esc_html(get_the_title($product_id)); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $badge['class']; ?>-transparent">
                                                    <?php echo $badge['label']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo maneli_number_format_persian($date); ?></td>
                                            <td>
                                                <div class="btn-list">
                                                    <a href="<?php echo esc_url($view_url); ?>" class="btn btn-sm btn-primary-light btn-icon" title="<?php esc_attr_e('View', 'maneli-car-inquiry'); ?>">
                                                        <i class="la la-eye"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-3 -->
    
        </div>
    </div>
    <!-- End::main-content -->
    
<?php } else {
    // ════════════════════════════════════════════════════════════
    // PREMIUM ADMIN / EXPERT DASHBOARD
    // ════════════════════════════════════════════════════════════
    
    // Determine expert filter
    $expert_id = null;
    if ($is_expert) {
        $expert_id = get_current_user_id();
    }
    
    // Get comprehensive statistics
    if ($is_admin && !$expert_id) {
        // Full business statistics for admin
        $business_stats = Maneli_Reports_Dashboard::get_business_statistics($start_date, $end_date);
        $stats = $business_stats['overall'] ?? [];
        $monthly_stats = $business_stats['monthly'] ?? [];
    } else {
        // Expert or filtered statistics
        $stats = Maneli_Reports_Dashboard::get_overall_statistics($start_date, $end_date, $expert_id);
    }
    
    // Get daily statistics for last 7 days specifically
    $daily_start_date = date('Y-m-d', strtotime('-6 days')); // 7 days including today
    $daily_end_date = date('Y-m-d');
    $daily_stats_raw = Maneli_Reports_Dashboard::get_daily_statistics($daily_start_date, $daily_end_date, $expert_id);
    // Convert associative array to indexed array for JavaScript
    $daily_stats = array_values($daily_stats_raw);
    $popular_products = Maneli_Reports_Dashboard::get_popular_products($start_date, $end_date, $expert_id, 5);
    
    // Get separate statistics for cash and installment inquiries
    $cash_stats = get_separate_cash_statistics($start_date, $end_date, $expert_id);
    $installment_stats = get_separate_installment_statistics($start_date, $end_date, $expert_id);
    
    // For experts, get detailed stats
    $expert_detailed = null;
    if ($is_expert && $expert_id) {
        $expert_detailed = Maneli_Reports_Dashboard::get_expert_detailed_statistics($expert_id, $start_date, $end_date);
    }
    
    // Calculate growth percentages
    $prev_start = date('Y-m-d', strtotime('-60 days'));
    $prev_end = date('Y-m-d', strtotime('-31 days'));
    $prev_stats = Maneli_Reports_Dashboard::get_overall_statistics($prev_start, $prev_end, $expert_id);
    
    $total_growth = $prev_stats['total_inquiries'] > 0 
        ? round((($stats['total_inquiries'] - $prev_stats['total_inquiries']) / $prev_stats['total_inquiries']) * 100, 1)
        : 0;
    $revenue_growth = $prev_stats['revenue'] > 0
        ? round((($stats['revenue'] - $prev_stats['revenue']) / $prev_stats['revenue']) * 100, 1)
        : 0;
    
    // Get today's followups count for expert
    $today_followups = 0;
    if ($is_expert && $expert_id) {
        $today_followups = count(get_posts([
            'post_type' => 'inquiry',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => [
                'relation' => 'AND',
                ['key' => 'assigned_expert_id', 'value' => $expert_id, 'compare' => '='],
                ['key' => 'follow_up_date', 'value' => date('Y-m-d'), 'compare' => '=', 'type' => 'DATE']
            ]
        ]));
    }
    
    // Get recent inquiries (both types)
    $recent_args = [
        'post_type' => ['inquiry', 'cash_inquiry'],
        'posts_per_page' => 10,
        'orderby' => 'date',
        'order' => 'DESC',
        'post_status' => 'publish'
    ];
    
    if ($expert_id) {
        $recent_args['meta_query'] = [
            [
                'key' => 'assigned_expert_id',
                'value' => $expert_id,
                'compare' => '='
            ]
        ];
    }
    
    $recent_inquiries = get_posts($recent_args);
    
    // Get upcoming followups for expert
    $upcoming_followups = [];
    if ($is_expert) {
        $upcoming_followups = get_posts([
            'post_type' => 'inquiry',
            'posts_per_page' => 5,
            'orderby' => 'meta_value',
            'meta_key' => 'follow_up_date',
            'order' => 'ASC',
            'post_status' => 'publish',
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'assigned_expert_id',
                    'value' => $expert_id,
                    'compare' => '='
                ],
                [
                    'key' => 'tracking_status',
                    'value' => 'follow_up_scheduled',
                    'compare' => '='
                ],
                [
                    'key' => 'follow_up_date',
                    'value' => date('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE'
                ]
            ]
        ]);
    }
    
    // Calculate growth percentages (compare with previous period)
    $prev_start = date('Y-m-d', strtotime('-60 days'));
    $prev_end = date('Y-m-d', strtotime('-31 days'));
    $prev_stats = Maneli_Reports_Dashboard::get_overall_statistics($prev_start, $prev_end, $expert_id);
    
    $total_growth = $prev_stats['total_inquiries'] > 0 
        ? round((($stats['total_inquiries'] - $prev_stats['total_inquiries']) / $prev_stats['total_inquiries']) * 100, 1)
        : 0;
    
    // Enqueue Chart.js - Use local version if available
    $chartjs_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/libs/chart.js/chart.umd.js';
    if (file_exists($chartjs_path)) {
        wp_enqueue_script('chartjs', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/chart.js/chart.umd.js', [], '4.4.0', true);
    } else {
        // Fallback to CDN if local file doesn't exist
        wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', [], '4.4.0', true);
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
                    <li class="breadcrumb-item active" aria-current="page"><?php esc_html_e('Home', 'maneli-car-inquiry'); ?></li>
                </ol>
            </nav>
            <h1 class="page-title fw-medium fs-18 mb-0">
                <?php if ($is_admin): ?>
                    <?php esc_html_e('Dashboard', 'maneli-car-inquiry'); ?>
                <?php else: ?>
                    <?php esc_html_e('Expert Dashboard', 'maneli-car-inquiry'); ?>
                <?php endif; ?>
            </h1>
        </div>
        <div class="btn-list">
            <a href="<?php echo esc_url(home_url('/dashboard/reports')); ?>" class="btn btn-primary btn-wave">
                <i class="la la-chart-bar me-1"></i>
                <?php esc_html_e('View Reports', 'maneli-car-inquiry'); ?>
            </a>
        </div>
    </div>
    <!-- End::page-header -->
    
    <!-- Start::row-1 - Premium Statistics Cards -->
    <div class="row">
        <?php if ($is_admin && !$expert_id): ?>
            <!-- Admin Dashboard - Premium Business Overview -->
            <?php 
            $business_stats_data = isset($business_stats) ? $business_stats : null;
            $total_profit = $business_stats_data && isset($business_stats_data['total_profit']) ? $business_stats_data['total_profit'] : 0;
            $total_experts = $business_stats_data && isset($business_stats_data['total_experts']) ? $business_stats_data['total_experts'] : 0;
            $total_customers = $business_stats_data && isset($business_stats_data['total_customers']) ? $business_stats_data['total_customers'] : 0;
            ?>
            <div class="col-md-6 col-lg-4 col-xl">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-primary border-opacity-10 bg-primary-transparent rounded-pill">
                                <span class="avatar avatar-md avatar-rounded bg-primary svg-white">
                                    <i class="la la-list-alt fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Total Inquiries', 'maneli-car-inquiry'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center"><?php echo maneli_number_format_persian($stats['total_inquiries'] ?? 0); ?></h4>
                            <span class="text-success badge bg-success-transparent rounded-pill d-flex align-items-center fs-11">
                                <i class="la la-arrow-up fs-11"></i><?php echo maneli_number_format_persian($total_growth, 1); ?>%
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 col-xl">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-success border-opacity-10 bg-success-transparent rounded-circle">
                                <span class="avatar avatar-md avatar-rounded bg-success svg-white">
                                    <i class="la la-money-bill-wave fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Total Revenue', 'maneli-car-inquiry'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center text-success"><?php echo maneli_number_format_persian($stats['revenue'] ?? 0); ?></h4>
                            <span class="text-success badge bg-success-transparent rounded-pill d-flex align-items-center fs-11">
                                <i class="la la-arrow-up fs-11"></i><?php echo maneli_number_format_persian($revenue_growth, 1); ?>%
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 col-xl">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-primary1 border-opacity-10 bg-primary1-transparent rounded-circle">
                                <span class="avatar avatar-md avatar-rounded bg-primary1 svg-white">
                                    <i class="la la-coins fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Total Profit', 'maneli-car-inquiry'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center text-primary"><?php echo maneli_number_format_persian($total_profit); ?></h4>
                            <span class="badge bg-primary-transparent rounded-pill fs-11"><?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 col-xl">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-primary2 border-opacity-10 bg-primary2-transparent rounded-circle">
                                <span class="avatar avatar-md avatar-rounded bg-primary2 svg-white">
                                    <i class="la la-user-tie fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Total Experts', 'maneli-car-inquiry'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center"><?php echo maneli_number_format_persian($total_experts); ?></h4>
                            <span class="badge bg-info-transparent rounded-pill fs-11"><?php esc_html_e('Employee', 'maneli-car-inquiry'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 col-xl">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-secondary border-opacity-10 bg-secondary-transparent rounded-circle">
                                <span class="avatar avatar-md avatar-rounded bg-secondary svg-white">
                                    <i class="la la-users fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Total Customers', 'maneli-car-inquiry'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center"><?php echo maneli_number_format_persian($total_customers); ?></h4>
                            <span class="badge bg-secondary-transparent rounded-pill fs-11"><?php esc_html_e('Customer', 'maneli-car-inquiry'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
        <?php elseif ($is_expert): ?>
            <!-- Expert Dashboard - Personal Performance -->
            <?php 
            $expert_profit = $expert_detailed && isset($expert_detailed['profit']) ? $expert_detailed['profit'] : 0;
            $success_rate = $expert_detailed && isset($expert_detailed['success_rate']) ? $expert_detailed['success_rate'] : 0;
            $total_customers_expert = $expert_detailed && isset($expert_detailed['total_customers']) ? $expert_detailed['total_customers'] : 0;
            ?>
            <div class="col-md-6 col-lg-4 col-xl">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-primary border-opacity-10 bg-primary-transparent rounded-pill">
                                <span class="avatar avatar-md avatar-rounded bg-primary svg-white">
                                    <i class="la la-clipboard-list fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Assigned to Me', 'maneli-car-inquiry'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center"><?php echo maneli_number_format_persian($stats['total_inquiries'] ?? 0); ?></h4>
                            <span class="badge bg-primary-transparent rounded-pill fs-11"><?php esc_html_e('Inquiry', 'maneli-car-inquiry'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 col-xl">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-success border-opacity-10 bg-success-transparent rounded-circle">
                                <span class="avatar avatar-md avatar-rounded bg-success svg-white">
                                    <i class="la la-check-double fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Completed', 'maneli-car-inquiry'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center text-success"><?php echo maneli_number_format_persian($stats['completed'] ?? 0); ?></h4>
                            <span class="text-success badge bg-success-transparent rounded-pill fs-11">
                                <?php echo maneli_number_format_persian($success_rate, 1); ?>%
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 col-xl">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-primary1 border-opacity-10 bg-primary1-transparent rounded-circle">
                                <span class="avatar avatar-md avatar-rounded bg-primary1 svg-white">
                                    <i class="la la-coins fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('My Profit', 'maneli-car-inquiry'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center text-primary"><?php echo maneli_number_format_persian($expert_profit); ?></h4>
                            <span class="badge bg-primary-transparent rounded-pill fs-11"><?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 col-xl">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-primary2 border-opacity-10 bg-primary2-transparent rounded-circle">
                                <span class="avatar avatar-md avatar-rounded bg-primary2 svg-white">
                                    <i class="la la-users fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('My Customers', 'maneli-car-inquiry'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center"><?php echo maneli_number_format_persian($total_customers_expert); ?></h4>
                            <span class="badge bg-info-transparent rounded-pill fs-11"><?php esc_html_e('Customer', 'maneli-car-inquiry'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 col-xl">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-warning border-opacity-10 bg-warning-transparent rounded-circle">
                                <span class="avatar avatar-md avatar-rounded bg-warning svg-white">
                                    <i class="la la-calendar-day fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Today\'s Followups', 'maneli-car-inquiry'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center text-warning"><?php echo maneli_number_format_persian($today_followups); ?></h4>
                            <span class="badge bg-warning-transparent rounded-pill fs-11"><?php esc_html_e('items', 'maneli-car-inquiry'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
        <?php endif; ?>
    </div>
    <!-- End::row-1 -->
            
    
    <!-- Start::row-2 - Main Content -->
                        <div class="row">
        <div class="col-xl-8 col-lg-12">
            <!-- Daily Trend Chart -->
            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div class="card-title">
                        <i class="la la-chart-line me-2"></i>
                        <?php esc_html_e('Daily Inquiries Trend (Last 7 Days)', 'maneli-car-inquiry'); ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="maneli-chart-container">
                        <canvas id="dailyTrendChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Recent Inquiries Table -->
            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div class="card-title">
                        <i class="la la-list me-2"></i>
                        <?php esc_html_e('Recent Inquiries', 'maneli-car-inquiry'); ?>
                    </div>
                    <div>
                        <a href="<?php echo esc_url(home_url('/dashboard/inquiries')); ?>" class="btn btn-sm btn-primary-light">
                            <?php esc_html_e('View All', 'maneli-car-inquiry'); ?>
                            <i class="la la-arrow-left ms-1"></i>
                                </a>
                            </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recent_inquiries)): ?>
                        <div class="text-center py-5">
                            <span class="avatar avatar-xl avatar-rounded bg-secondary-transparent mb-3">
                                <i class="la la-inbox fs-40 text-muted"></i>
                            </span>
                            <h6 class="fw-medium mb-2"><?php esc_html_e('No Inquiries Found', 'maneli-car-inquiry'); ?></h6>
                            <p class="text-muted mb-0"><?php esc_html_e('No inquiries have been registered in the system yet.', 'maneli-car-inquiry'); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table text-nowrap table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col"><?php esc_html_e('ID', 'maneli-car-inquiry'); ?></th>
                                        <th scope="col"><?php esc_html_e('Type', 'maneli-car-inquiry'); ?></th>
                                        <th scope="col"><?php esc_html_e('Customer', 'maneli-car-inquiry'); ?></th>
                                        <th scope="col"><?php esc_html_e('Car', 'maneli-car-inquiry'); ?></th>
                                        <th scope="col"><?php esc_html_e('Status', 'maneli-car-inquiry'); ?></th>
                                        <th scope="col"><?php esc_html_e('Date', 'maneli-car-inquiry'); ?></th>
                                        <th scope="col"><?php esc_html_e('Actions', 'maneli-car-inquiry'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($recent_inquiries, 0, 5) as $inq):
                                        $post_type = get_post_type($inq);
                                        $is_cash = ($post_type === 'cash_inquiry');
                                        
                                        if ($is_cash) {
                                            $customer_name = get_post_meta($inq->ID, 'cash_first_name', true) . ' ' . get_post_meta($inq->ID, 'cash_last_name', true);
                                            $status = get_post_meta($inq->ID, 'cash_inquiry_status', true);
                                        } else {
                                            $author = get_userdata($inq->post_author);
                                            $customer_name = $author ? $author->display_name : esc_html__('Unknown', 'maneli-car-inquiry');
                                            $status = get_post_meta($inq->ID, 'tracking_status', true) ?: 'new';
                                        }
                                        
                                        $product_id = get_post_meta($inq->ID, 'product_id', true);
                                        
                                        // Get status badges from Maneli_CPT_Handler for consistency
                                        if ($is_cash) {
                                            $all_cash_statuses = Maneli_CPT_Handler::get_all_cash_inquiry_statuses();
                                            $status_label = $all_cash_statuses[$status] ?? esc_html__('Unknown', 'maneli-car-inquiry');
                                            // Map status to badge class
                                            $status_class_map = [
                                                'new' => 'primary',
                                                'referred' => 'info',
                                                'in_progress' => 'warning',
                                                'follow_up_scheduled' => 'info',
                                                'awaiting_downpayment' => 'warning',
                                                'downpayment_received' => 'success',
                                                'meeting_scheduled' => 'info',
                                                'approved' => 'success',
                                                'completed' => 'success',
                                                'rejected' => 'danger',
                                            ];
                                            $badge_class = $status_class_map[$status] ?? 'secondary';
                                            $badge = ['label' => $status_label, 'class' => $badge_class];
                                        } else {
                                            $all_tracking_statuses = Maneli_CPT_Handler::get_tracking_statuses();
                                            $status_label = $all_tracking_statuses[$status] ?? esc_html__('Unknown', 'maneli-car-inquiry');
                                            // Map status to badge class
                                            $status_class_map = [
                                                'new' => 'primary',
                                                'referred' => 'info',
                                                'in_progress' => 'warning',
                                                'meeting_scheduled' => 'info',
                                                'follow_up_scheduled' => 'info',
                                                'user_confirmed' => 'success',
                                                'completed' => 'success',
                                                'rejected' => 'danger',
                                                'cancelled' => 'secondary',
                                            ];
                                            $badge_class = $status_class_map[$status] ?? 'secondary';
                                            $badge = ['label' => $status_label, 'class' => $badge_class];
                                        }
                                        
                                        $timestamp = strtotime($inq->post_date);
                                        if (function_exists('maneli_gregorian_to_jalali')) {
                                            $date = maneli_gregorian_to_jalali(
                                                date('Y', $timestamp),
                                                date('m', $timestamp),
                                                date('d', $timestamp),
                                                'Y/m/d'
                                            );
                                        } else {
                                            $date = date('Y/m/d', $timestamp);
                                        }
                                        
                                        $view_url = $is_cash 
                                            ? add_query_arg('cash_inquiry_id', $inq->ID, home_url('/dashboard/inquiries/cash'))
                                            : add_query_arg('inquiry_id', $inq->ID, home_url('/dashboard/inquiries/installment'));
                                    ?>
                                        <tr class="crm-contact contacts-list">
                                            <td><strong>#<?php echo maneli_number_format_persian($inq->ID); ?></strong></td>
                                            <td>
                                                <span class="badge bg-<?php echo $is_cash ? 'warning' : 'info'; ?>-transparent">
                                                    <?php echo $is_cash ? esc_html__('Cash', 'maneli-car-inquiry') : esc_html__('Installment', 'maneli-car-inquiry'); ?>
                                                </span>
                                            </td>
                                            <td data-title="<?php esc_attr_e('Customer', 'maneli-car-inquiry'); ?>"><?php echo esc_html($customer_name); ?></td>
                                            <td data-title="<?php esc_attr_e('Car', 'maneli-car-inquiry'); ?>"><?php echo esc_html(get_the_title($product_id)); ?></td>
                                            <td data-title="<?php esc_attr_e('Status', 'maneli-car-inquiry'); ?>">
                                                <span class="badge bg-<?php echo $badge['class']; ?>">
                                                    <?php echo $badge['label']; ?>
                                                </span>
                                            </td>
                                            <td data-title="<?php esc_attr_e('Date', 'maneli-car-inquiry'); ?>"><?php echo maneli_number_format_persian($date); ?></td>
                                            <td data-title="<?php esc_attr_e('Actions', 'maneli-car-inquiry'); ?>">
                                                <div class="btn-list">
                                                    <a href="<?php echo esc_url($view_url); ?>" class="btn btn-sm btn-primary-light btn-icon" title="<?php esc_attr_e('View', 'maneli-car-inquiry'); ?>">
                                                        <i class="la la-eye"></i>
                                </a>
                            </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="col-xl-4 col-lg-12">
            <!-- Today's Stats -->
            <div class="card custom-card">
                <div class="card-body p-4">
                    <div class="d-flex align-items-start gap-3">
                        <div class="p-2 border border-primary border-opacity-10 bg-primary-transparent rounded-pill">
                            <span class="avatar avatar-md avatar-rounded bg-primary">
                                <i class="la la-calendar-day fs-20 text-white"></i>
                            </span>
                        </div>
                        <div class="flex-fill">
                            <span class="fs-12 mb-1 d-block fw-medium text-muted">استعلامات امروز</span>
                            <h4 class="mb-0 d-flex align-items-center"><?php echo maneli_number_format_persian($stats['new_today'] ?? 0); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($is_expert): ?>
                <!-- Popular Products for Expert -->
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="la la-star me-2"></i>
                            محصولات پرطرفدار
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($popular_products)): ?>
                            <div class="text-center py-4">
                                <span class="avatar avatar-xl avatar-rounded bg-secondary-transparent mb-2">
                                    <i class="la la-inbox fs-40 text-muted"></i>
                                </span>
                                <p class="text-muted mb-0">داده‌ای موجود نیست</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table text-nowrap mb-0">
                                    <tbody>
                                        <?php 
                                        $rank = 1;
                                        foreach ($popular_products as $product): 
                                            $percentage = $stats['total_inquiries'] > 0 
                                                ? round(($product['count'] / $stats['total_inquiries']) * 100, 1)
                                                : 0;
                                            
                                            $colors = ['primary', 'success', 'info', 'warning', 'secondary'];
                                            $color = $colors[($rank - 1) % count($colors)];
                                        ?>
                                            <tr>
                                                <td class="maneli-col-width-30">
                                                    <span class="badge bg-<?php echo esc_attr($color); ?>"><?php echo esc_html(maneli_number_format_persian($rank++)); ?></span>
                                                </td>
                                                <td>
                                                    <span class="fw-medium"><?php echo esc_html($product['name']); ?></span>
                                                </td>
                                                <td class="maneli-col-width-80">
                                                    <span class="badge bg-<?php echo esc_attr($color); ?>-transparent">
                                                        <?php echo maneli_number_format_persian($product['count']); ?>
                                                    </span>
                                                </td>
                                                <td class="maneli-col-width-60 text-end">
                                                    <span class="text-muted fs-11"><?php echo maneli_number_format_persian($percentage, 1); ?>%</span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Upcoming Followups for Expert -->
                <?php if (!empty($upcoming_followups)): ?>
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="la la-calendar-alt me-2"></i>
                            پیگیری‌های آتی من
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php foreach ($upcoming_followups as $followup_inq): 
                                $followup_date = get_post_meta($followup_inq->ID, 'follow_up_date', true);
                                $product_id = get_post_meta($followup_inq->ID, 'product_id', true);
                                $customer = get_userdata($followup_inq->post_author);
                                $is_today = ($followup_date === date('Y-m-d'));
                                
                                // Jalali date
                                if ($followup_date && function_exists('maneli_gregorian_to_jalali')) {
                                    $timestamp = strtotime($followup_date);
                                    $jalali_date = maneli_gregorian_to_jalali(
                                        date('Y', $timestamp),
                                        date('m', $timestamp),
                                        date('d', $timestamp),
                                        'Y/m/d'
                                    );
                                } else {
                                    $jalali_date = $followup_date;
                                }
                            ?>
                                <div class="list-group-item <?php echo $is_today ? 'bg-warning-transparent' : ''; ?>">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <span class="avatar avatar-sm bg-<?php echo $is_today ? 'danger' : 'info'; ?>-transparent me-2">
                                                <i class="la la-calendar fs-18"></i>
                                            </span>
                                            <div>
                                                <div class="fw-medium"><?php echo esc_html($customer ? $customer->display_name : esc_html__('Unknown', 'maneli-car-inquiry')); ?></div>
                                                <small class="text-muted"><?php echo esc_html(get_the_title($product_id)); ?></small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-semibold <?php echo $is_today ? 'text-danger' : 'text-info'; ?>">
                                                <?php echo esc_html(maneli_number_format_persian($jalali_date)); ?>
                                            </div>
                                            <?php if ($is_today): ?>
                                                <small class="badge bg-danger"><?php esc_html_e('Today', 'maneli-car-inquiry'); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="card-footer text-center">
                        <a href="<?php echo esc_url(home_url('/dashboard/followups')); ?>" class="btn btn-sm btn-primary-light">
                            <?php esc_html_e('View All Followups', 'maneli-car-inquiry'); ?>
                            <i class="la la-arrow-left ms-1"></i>
                                </a>
                            </div>
                </div>
                <?php endif; ?>
                
            <?php else: ?>
                <!-- Task List for Admin -->
                <?php
                // Get pending assignments
                $pending_installment = get_posts([
                    'post_type' => 'inquiry',
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'meta_query' => [
                        'relation' => 'AND',
                        [
                            'key' => 'tracking_status',
                            'value' => 'new',
                            'compare' => '='
                        ],
                        [
                            'relation' => 'OR',
                            [
                                'key' => 'assigned_expert_id',
                                'compare' => 'NOT EXISTS'
                            ],
                            [
                                'key' => 'assigned_expert_id',
                                'value' => '',
                                'compare' => '='
                            ]
                        ]
                    ]
                ]);
                
                $pending_cash = get_posts([
                    'post_type' => 'cash_inquiry',
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'meta_query' => [
                        'relation' => 'AND',
                        [
                            'key' => 'cash_inquiry_status',
                            'value' => ['new', 'pending'],
                            'compare' => 'IN'
                        ],
                        [
                            'relation' => 'OR',
                            [
                                'key' => 'assigned_expert_id',
                                'compare' => 'NOT EXISTS'
                            ],
                            [
                                'key' => 'assigned_expert_id',
                                'value' => '',
                                'compare' => '='
                            ]
                        ]
                    ]
                ]);
                
                // Today's meetings
                $today_start = date('Y-m-d 00:00:00');
                $today_end = date('Y-m-d 23:59:59');
                $today_meetings = get_posts([
                    'post_type' => 'maneli_meeting',
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'meta_query' => [
                        [
                            'key' => 'meeting_start',
                            'value' => $today_start,
                            'compare' => '>='
                        ],
                        [
                            'key' => 'meeting_start',
                            'value' => $today_end,
                            'compare' => '<='
                        ]
                    ]
                ]);
                
                // Overdue followups
                $overdue_followups = get_posts([
                    'post_type' => 'inquiry',
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'meta_query' => [
                        [
                            'key' => 'tracking_status',
                            'value' => 'follow_up_scheduled',
                            'compare' => '='
                        ],
                        [
                            'key' => 'follow_up_date',
                            'value' => date('Y-m-d'),
                            'compare' => '<',
                            'type' => 'DATE'
                        ]
                    ]
                ]);
                
                // Awaiting payment
                $awaiting_payment = get_posts([
                    'post_type' => 'cash_inquiry',
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'meta_query' => [
                        [
                            'key' => 'cash_inquiry_status',
                            'value' => 'awaiting_downpayment',
                            'compare' => '='
                        ]
                    ]
                ]);
                ?>
                <div class="card custom-card">
                    <div class="card-header bg-danger-transparent">
                        <div class="card-title">
                            <i class="la la-tasks me-2"></i>
                            لیست کارهای امروز
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <!-- Pending Installment Assignments -->
                            <a href="<?php echo esc_url(home_url('/dashboard/inquiries/installment')); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between <?php echo esc_attr(!empty($pending_installment) ? 'border-start border-danger border-3' : ''); ?>">
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm bg-danger-transparent me-2">
                                        <i class="la la-credit-card"></i>
                                    </span>
                                    <div>
                                        <div class="fw-medium"><?php esc_html_e('Pending Installment Assignments', 'maneli-car-inquiry'); ?></div>
                                        <small class="text-muted"><?php esc_html_e('Need expert assignment', 'maneli-car-inquiry'); ?></small>
                                    </div>
                                </div>
                                <span class="badge bg-danger"><?php echo maneli_number_format_persian(count($pending_installment)); ?></span>
                            </a>
                            
                            <!-- Pending Cash Assignments -->
                            <a href="<?php echo esc_url(home_url('/dashboard/inquiries/cash')); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between <?php echo esc_attr(!empty($pending_cash) ? 'border-start border-warning border-3' : ''); ?>">
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm bg-warning-transparent me-2">
                                        <i class="la la-dollar-sign"></i>
                                    </span>
                                    <div>
                                        <div class="fw-medium"><?php esc_html_e('Pending Cash Assignments', 'maneli-car-inquiry'); ?></div>
                                        <small class="text-muted"><?php esc_html_e('Need expert assignment', 'maneli-car-inquiry'); ?></small>
                            </div>
                        </div>
                                <span class="badge bg-warning"><?php echo maneli_number_format_persian(count($pending_cash)); ?></span>
                            </a>
                            
                            <!-- Today's Meetings -->
                            <a href="<?php echo esc_url(home_url('/dashboard/calendar')); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between <?php echo esc_attr(!empty($today_meetings) ? 'border-start border-info border-3' : ''); ?>">
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm bg-info-transparent me-2">
                                        <i class="la la-calendar"></i>
                                    </span>
                                    <div>
                                        <div class="fw-medium"><?php esc_html_e('Today\'s Meetings', 'maneli-car-inquiry'); ?></div>
                                        <small class="text-muted"><?php esc_html_e('In-person appointments', 'maneli-car-inquiry'); ?></small>
                    </div>
                </div>
                                <span class="badge bg-info"><?php echo maneli_number_format_persian(count($today_meetings)); ?></span>
                            </a>
                            
                            <!-- Overdue Followups -->
                            <a href="<?php echo esc_url(home_url('/dashboard/followups')); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between <?php echo esc_attr(!empty($overdue_followups) ? 'border-start border-danger border-3' : ''); ?>">
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm bg-danger-transparent me-2">
                                        <i class="la la-exclamation-triangle"></i>
                                    </span>
                                    <div>
                                        <div class="fw-medium"><?php esc_html_e('Overdue Followups', 'maneli-car-inquiry'); ?></div>
                                        <small class="text-muted"><?php esc_html_e('Requires urgent action', 'maneli-car-inquiry'); ?></small>
            </div>
        </div>
                                <span class="badge bg-danger"><?php echo maneli_number_format_persian(count($overdue_followups)); ?></span>
                            </a>
                            
                            <!-- Awaiting Payment -->
                            <a href="<?php echo esc_url(home_url('/dashboard/inquiries/cash')); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm bg-success-transparent me-2">
                                        <i class="la la-money-bill"></i>
                                    </span>
                                    <div>
                                        <div class="fw-medium"><?php esc_html_e('Awaiting Payment', 'maneli-car-inquiry'); ?></div>
                                        <small class="text-muted"><?php esc_html_e('Cash inquiries', 'maneli-car-inquiry'); ?></small>
</div>
</div>
                                <span class="badge bg-success"><?php echo maneli_number_format_persian(count($awaiting_payment)); ?></span>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Popular Products for Admin -->
            <?php if ($is_admin && !empty($popular_products)): ?>
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="la la-trophy me-2"></i>
                            محصولات پرطرفدار
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table text-nowrap mb-0">
                                <tbody>
                                    <?php 
                                    $rank = 1;
                                    foreach ($popular_products as $product): 
                                        $percentage = $stats['total_inquiries'] > 0 
                                            ? round(($product['count'] / $stats['total_inquiries']) * 100, 1)
                                            : 0;
                                        
                                        $colors = ['primary', 'success', 'info', 'warning', 'secondary'];
                                        $color = $colors[($rank - 1) % count($colors)];
                                    ?>
                                        <tr>
                                                <td class="maneli-col-width-40">
                                                <span class="badge bg-<?php echo esc_attr($color); ?> rounded-pill"><?php echo esc_html($rank++); ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="la la-car text-<?php echo esc_attr($color); ?> me-2 fs-18"></i>
                                                    <span class="fw-medium"><?php echo esc_html($product['name']); ?></span>
                                                </div>
                                            </td>
                                            <td class="maneli-col-width-100">
                                                <span class="badge bg-<?php echo esc_attr($color); ?>-transparent">
                                                    <?php echo maneli_number_format_persian($product['count']); ?> <?php esc_html_e('inquiries', 'maneli-car-inquiry'); ?>
                                                </span>
                                            </td>
                                            <td class="maneli-col-width-100">
                                                <div class="progress maneli-progress-sm">
                                                    <div class="progress-bar bg-<?php echo esc_attr($color); ?>" style="width: <?php echo esc_attr($percentage); ?>%;"></div>
                                                </div>
                                                <small class="text-muted"><?php echo maneli_number_format_persian($percentage, 1); ?>%</small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Cash vs Installment for Admin -->
            <?php if (!$is_expert): ?>
                <!-- Cash vs Installment -->
                <?php
                $cash_count = isset($stats['cash_inquiries']) ? $stats['cash_inquiries'] : 0;
                $installment_count = isset($stats['installment_inquiries']) ? $stats['installment_inquiries'] : 0;
                $total_count = isset($stats['total_inquiries']) ? $stats['total_inquiries'] : 0;
                
                $cash_percentage = $total_count > 0 ? round(($cash_count / $total_count) * 100, 1) : 0;
                $installment_percentage = $total_count > 0 ? round(($installment_count / $total_count) * 100, 1) : 0;
                
                // Calculate revenue (if available from stats)
                $cash_revenue = 0;
                $installment_revenue = 0;
                $total_revenue = isset($stats['revenue']) ? floatval($stats['revenue']) : 0;
                
                // Try to get separate revenues from business stats if available
                if ($is_admin && isset($business_stats) && is_array($business_stats)) {
                    // We could calculate from business_stats if it has detailed breakdown
                    // For now, we'll just show the counts comparison
                    $total_revenue = isset($stats['revenue']) ? floatval($stats['revenue']) : 0;
                }
                
                // If we have total revenue, estimate based on inquiry counts (rough estimate)
                if ($total_revenue > 0 && $total_count > 0) {
                    $cash_revenue = ($total_revenue / $total_count) * $cash_count;
                    $installment_revenue = ($total_revenue / $total_count) * $installment_count;
                }
                
                $cash_revenue_percentage = $total_revenue > 0 ? round(($cash_revenue / $total_revenue) * 100, 1) : 0;
                $installment_revenue_percentage = $total_revenue > 0 ? round(($installment_revenue / $total_revenue) * 100, 1) : 0;
                ?>
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="la la-chart-pie me-2"></i>
                            <?php esc_html_e('Cash vs Installment Comparison', 'maneli-car-inquiry'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Inquiries Count -->
                        <div class="mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm bg-warning-transparent me-2">
                                        <i class="la la-dollar-sign"></i>
                                    </span>
                                    <div>
                                        <div class="fw-medium"><?php esc_html_e('Cash Inquiries', 'maneli-car-inquiry'); ?></div>
                                        <small class="text-muted"><?php printf(esc_html__('%s items', 'maneli-car-inquiry'), maneli_number_format_persian($cash_count)); ?></small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-warning fs-16">
                                        <?php echo maneli_number_format_persian($cash_percentage, 1); ?>%
                                    </span>
                                </div>
                            </div>
                            <div class="progress mb-4 maneli-progress-md">
                                <div class="progress-bar bg-warning" style="width: <?php echo esc_attr($cash_percentage); ?>%;" role="progressbar"></div>
                            </div>
                            
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm bg-info-transparent me-2">
                                        <i class="la la-credit-card"></i>
                                    </span>
                                    <div>
                                        <div class="fw-medium"><?php esc_html_e('Installment Inquiries', 'maneli-car-inquiry'); ?></div>
                                        <small class="text-muted"><?php printf(esc_html__('%s items', 'maneli-car-inquiry'), maneli_number_format_persian($installment_count)); ?></small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-info fs-16">
                                        <?php echo maneli_number_format_persian($installment_percentage, 1); ?>%
                                    </span>
                                </div>
                            </div>
                            <div class="progress maneli-progress-md">
                                <div class="progress-bar bg-info" style="width: <?php echo esc_attr($installment_percentage); ?>%;" role="progressbar"></div>
                            </div>
                        </div>
                        
                        <?php if ($total_revenue > 0): ?>
                        <!-- Revenue Comparison -->
                        <hr class="my-3">
                        <div class="mb-2">
                            <small class="text-muted">مقایسه درآمد:</small>
                        </div>
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center">
                                <i class="la la-dollar-sign text-warning me-2"></i>
                                <small class="fw-medium">نقدی:</small>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="me-2"><?php echo maneli_number_format_persian($cash_revenue); ?> <?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></span>
                                <span class="badge bg-warning-transparent"><?php echo maneli_number_format_persian($cash_revenue_percentage, 1); ?>%</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <i class="la la-credit-card text-info me-2"></i>
                                <small class="fw-medium">اقساطی:</small>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="me-2"><?php echo maneli_number_format_persian($installment_revenue); ?> <?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></span>
                                <span class="badge bg-info-transparent"><?php echo maneli_number_format_persian($installment_revenue_percentage, 1); ?>%</span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <div class="btn-list">
                            <a href="<?php echo esc_url(home_url('/dashboard/inquiries/cash')); ?>" class="btn btn-sm btn-warning-light">
                                <i class="la la-eye me-1"></i>
                                <?php esc_html_e('View Cash Inquiries', 'maneli-car-inquiry'); ?>
                            </a>
                            <a href="<?php echo esc_url(home_url('/dashboard/inquiries/installment')); ?>" class="btn btn-sm btn-info-light">
                                <i class="la la-eye me-1"></i>
                                <?php esc_html_e('View Installment Inquiries', 'maneli-car-inquiry'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    // تابع تبدیل تاریخ میلادی به شمسی
    function maneli_gregorian_to_jalali(gy, gm, gd, format) {
        const g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        let jy = gy <= 1600 ? 0 : 979;
        gy -= gy <= 1600 ? 621 : 1600;
        const gy2 = (gm > 2) ? (gy + 1) : gy;
        let days = (365 * gy) + (parseInt((gy2 + 3) / 4)) - (parseInt((gy2 + 99) / 100)) + (parseInt((gy2 + 399) / 400)) - 80 + gd + g_d_m[gm - 1];
        jy += 33 * (parseInt(days / 12053));
        days %= 12053;
        let jm = 1;
        let jd = 1;
        if (days >= 365) {
            jy += parseInt((days - 1) / 365);
            days = (days - 1) % 365;
        }
        if (days < 186) {
            jm = 1 + parseInt(days / 31);
            jd = 1 + (days % 31);
        } else {
            jm = 7 + parseInt((days - 186) / 30);
            jd = 1 + ((days - 186) % 30);
        }
        
        if (format === 'Y/m/d') {
            return jy + '/' + (jm < 10 ? '0' : '') + jm + '/' + (jd < 10 ? '0' : '') + jd;
        }
        return jy + '/' + jm + '/' + jd;
    }
    
    // تابع ساده‌تر برای تبدیل تاریخ
    function convertToJalali(dateString) {
        try {
            const date = new Date(dateString);
            const year = date.getFullYear();
            const month = date.getMonth() + 1;
            const day = date.getDate();
            return maneli_gregorian_to_jalali(year, month, day, 'Y/m/d');
        } catch (e) {
            console.log('Error converting date:', e);
            return dateString;
        }
    }
    
    // Wait for both jQuery and Chart.js to load
    function waitForChart() {
        if (typeof jQuery === 'undefined' || typeof Chart === 'undefined') {
            setTimeout(waitForChart, 100);
            return;
        }
        
        jQuery(document).ready(function($) {
            initDailyTrendChart($);
        });
    }
    
    // Start waiting
    waitForChart();
    
    function initDailyTrendChart($) {
        console.log('Initializing daily trend chart...');
        console.log('Chart.js available:', typeof Chart !== 'undefined');
        
        // Wait for Chart.js to load (check multiple times)
        function initChart() {
            if (typeof Chart === 'undefined') {
                console.log('Chart.js not loaded yet, retrying in 100ms...');
                setTimeout(initChart, 100);
                return;
            }
            
            const ctx = document.getElementById('dailyTrendChart');
            if (!ctx) {
                console.warn('Canvas element not found');
                return;
            }
            
            // ایجاد نمودار ساده
            try {
                const dailyData = <?php echo json_encode($daily_stats); ?>;
                console.log('Daily stats data:', dailyData);
                
                let labels, totalData, cashData, installmentData;
                
                if (dailyData && dailyData.length > 0) {
                    // تبدیل تاریخ میلادی به شمسی
                    labels = dailyData.map(item => {
                        console.log('Converting date:', item.date);
                        const jalaliDate = convertToJalali(item.date);
                        console.log('Converted to:', jalaliDate);
                        return jalaliDate;
                    });
                    totalData = dailyData.map(item => parseInt(item.total) || 0);
                    cashData = dailyData.map(item => parseInt(item.cash) || 0);
                    installmentData = dailyData.map(item => parseInt(item.installment) || 0);
                } else {
                    // داده‌های پیش‌فرض
                    labels = [<?php echo json_encode(esc_html__('Today', 'maneli-car-inquiry')); ?>, <?php echo json_encode(esc_html__('Yesterday', 'maneli-car-inquiry')); ?>, '<?php echo esc_js(sprintf(esc_html__('%d days ago', 'maneli-car-inquiry'), 2)); ?>', '<?php echo esc_js(sprintf(esc_html__('%d days ago', 'maneli-car-inquiry'), 3)); ?>', '<?php echo esc_js(sprintf(esc_html__('%d days ago', 'maneli-car-inquiry'), 4)); ?>', '<?php echo esc_js(sprintf(esc_html__('%d days ago', 'maneli-car-inquiry'), 5)); ?>', '<?php echo esc_js(sprintf(esc_html__('%d days ago', 'maneli-car-inquiry'), 6)); ?>'];
                    totalData = [0, 0, 0, 0, 0, 0, 0];
                    cashData = [0, 0, 0, 0, 0, 0, 0];
                    installmentData = [0, 0, 0, 0, 0, 0, 0];
                }
                
                console.log('Chart data:', { labels, totalData, cashData, installmentData });
                
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: <?php echo json_encode(esc_html__('Total Inquiries', 'maneli-car-inquiry')); ?>,
                                data: totalData,
                                borderColor: 'rgb(75, 192, 192)',
                                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                                borderWidth: 3,
                                tension: 0.4,
                                fill: true,
                                pointRadius: 5,
                                pointHoverRadius: 7
                            },
                            {
                                label: <?php echo json_encode(esc_html__('Cash', 'maneli-car-inquiry')); ?>,
                                data: cashData,
                                borderColor: 'rgb(255, 159, 64)',
                                backgroundColor: 'rgba(255, 159, 64, 0.1)',
                                borderWidth: 2,
                                tension: 0.4,
                                fill: true,
                                pointRadius: 4,
                                pointHoverRadius: 6
                            },
                            {
                                label: <?php echo json_encode(esc_html__('Installment', 'maneli-car-inquiry')); ?>,
                                data: installmentData,
                                borderColor: 'rgb(54, 162, 235)',
                                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                                borderWidth: 2,
                                tension: 0.4,
                                fill: true,
                                pointRadius: 4,
                                pointHoverRadius: 6
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    usePointStyle: true,
                                    padding: 15,
                                    font: {
                                        family: 'IRANSans, Arial, sans-serif',
                                        size: 13
                                    }
                                }
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleFont: {
                                    family: 'IRANSans, Arial, sans-serif'
                                },
                                bodyFont: {
                                    family: 'IRANSans, Arial, sans-serif'
                                }
                            }
                        },
                        interaction: {
                            mode: 'nearest',
                            axis: 'x',
                            intersect: false
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1,
                                    font: {
                                        family: 'IRANSans, Arial, sans-serif'
                                    }
                                },
                                grid: {
                                    drawBorder: false,
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: {
                                        family: 'IRANSans, Arial, sans-serif'
                                    }
                                }
                            }
                        }
                    }
                });
                console.log('Chart initialized successfully');
            } catch (error) {
                console.error('Chart creation failed:', error);
                if (ctx && ctx.parentElement) {
                    ctx.parentElement.innerHTML = '<div class="text-center py-5"><i class="la la-chart-line text-muted" style="font-size: 60px;"></i><p class="text-muted mt-3">خطا در بارگذاری نمودار</p></div>';
                }
            }
        }
        
        // Start initialization
        initChart();
    }
    </script>
    
        </div>
    </div>
    <!-- End::main-content -->
    
<?php } ?>

<style>
.bg-primary-gradient {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
}

.bg-success-gradient {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
}

.bg-info-gradient {
    background: linear-gradient(135deg, #17a2b8 0%, #5bc0de 100%);
}

#dailyTrendChart {
    min-height: 300px;
}
</style>

<?php
/**
 * Helper function to get separate cash inquiry statistics
 */
function get_separate_cash_statistics($start_date, $end_date, $expert_id = null) {
    global $wpdb;
    
    $expert_join = '';
    $expert_where = '';
    $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
    
    if ($expert_id) {
        $expert_join = "INNER JOIN {$wpdb->postmeta} pm_expert ON p.ID = pm_expert.post_id AND pm_expert.meta_key = 'assigned_expert_id'";
        $expert_where = "AND pm_expert.meta_value = %d";
        $params[] = $expert_id;
    }
    
    $counts = $wpdb->get_results($wpdb->prepare("
        SELECT 
            COALESCE(pm.meta_value, 'new') as status,
            COUNT(*) as count
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'cash_inquiry_status'
        $expert_join
        WHERE p.post_type = 'cash_inquiry'
        AND p.post_status = 'publish'
        AND p.post_date >= %s AND p.post_date <= %s
        $expert_where
        GROUP BY COALESCE(pm.meta_value, 'new')
    ", $params), ARRAY_A);
    
    $stats = [
        'total' => 0,
        'new' => 0,
        'pending' => 0,
        'approved' => 0,
        'completed' => 0,
        'rejected' => 0,
        'cancelled' => 0,
    ];
    
    foreach ($counts as $count) {
        $stats[$count['status']] = (int)$count['count'];
        $stats['total'] += (int)$count['count'];
    }
    
    return $stats;
}

/**
 * Helper function to get separate installment inquiry statistics
 */
function get_separate_installment_statistics($start_date, $end_date, $expert_id = null) {
    global $wpdb;
    
    $expert_join = '';
    $expert_where = '';
    $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
    
    if ($expert_id) {
        $expert_join = "INNER JOIN {$wpdb->postmeta} pm_expert ON p.ID = pm_expert.post_id AND pm_expert.meta_key = 'assigned_expert_id'";
        $expert_where = "AND pm_expert.meta_value = %d";
        $params[] = $expert_id;
    }
    
    $counts = $wpdb->get_results($wpdb->prepare("
        SELECT 
            COALESCE(pm.meta_value, 'new') as status,
            COUNT(*) as count
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'tracking_status'
        $expert_join
        WHERE p.post_type = 'inquiry'
        AND p.post_status = 'publish'
        AND p.post_date >= %s AND p.post_date <= %s
        $expert_where
        GROUP BY COALESCE(pm.meta_value, 'new')
    ", $params), ARRAY_A);
    
    $stats = [
        'total' => 0,
        'new' => 0,
        'referred' => 0,
        'in_progress' => 0,
        'user_confirmed' => 0,
        'completed' => 0,
        'rejected' => 0,
        'cancelled' => 0,
        'follow_up_scheduled' => 0,
        'pending' => 0, // برای سازگاری با نمایش
    ];
    
    foreach ($counts as $count) {
        $stats[$count['status']] = (int)$count['count'];
        $stats['total'] += (int)$count['count'];
    }
    
    // نگاشت pending به new برای سازگاری
    $stats['pending'] = $stats['new'];
    
    return $stats;
}
?>
