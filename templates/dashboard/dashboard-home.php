<!-- Start::row-1 -->
<?php
/**
 * Dashboard Home - Real Data Implementation
 * Different views for Admin, Expert, and Customer
 */

$current_user = wp_get_current_user();
$is_admin = current_user_can('manage_maneli_inquiries');
$is_expert = in_array('maneli_expert', $current_user->roles, true);
$is_customer = !$is_admin && !$is_expert;

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
    
    <div class="row">
        <div class="col-xl-12">
            <!-- Welcome Card -->
            <div class="card custom-card bg-primary-gradient text-white mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="text-white mb-2">سلام، <?php echo esc_html($current_user->display_name); ?> عزیز!</h4>
                            <p class="text-white-50 mb-0">به پنل کاربری مانلی خودرو خوش آمدید</p>
                        </div>
                        <div>
                            <i class="la la-user-circle" style="font-size: 80px; opacity: 0.3;"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Statistics -->
            <div class="row mb-4">
                <div class="col-xl-3 col-lg-6">
                    <div class="card custom-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <span class="avatar avatar-md bg-primary-transparent">
                                        <i class="la la-list-alt fs-24"></i>
                                    </span>
                                </div>
                                <div class="flex-fill">
                                    <div class="mb-1">
                                        <span class="text-muted fs-13">کل استعلامات</span>
                                    </div>
                                    <h4 class="fw-semibold mb-0"><?php echo number_format_i18n($total_count); ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6">
                    <div class="card custom-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <span class="avatar avatar-md bg-warning-transparent">
                                        <i class="la la-clock fs-24"></i>
                                    </span>
                                </div>
                                <div class="flex-fill">
                                    <div class="mb-1">
                                        <span class="text-muted fs-13">در انتظار</span>
                                    </div>
                                    <h4 class="fw-semibold mb-0 text-warning"><?php echo number_format_i18n($pending_count); ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6">
                    <div class="card custom-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <span class="avatar avatar-md bg-success-transparent">
                                        <i class="la la-check-circle fs-24"></i>
                                    </span>
                                </div>
                                <div class="flex-fill">
                                    <div class="mb-1">
                                        <span class="text-muted fs-13">تایید شده</span>
                                    </div>
                                    <h4 class="fw-semibold mb-0 text-success"><?php echo number_format_i18n($approved_count); ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6">
                    <div class="card custom-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <span class="avatar avatar-md bg-danger-transparent">
                                        <i class="la la-times-circle fs-24"></i>
                                    </span>
                                </div>
                                <div class="flex-fill">
                                    <div class="mb-1">
                                        <span class="text-muted fs-13">رد شده</span>
                                    </div>
                                    <h4 class="fw-semibold mb-0 text-danger"><?php echo number_format_i18n($rejected_count); ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="row mb-4">
                <div class="col-xl-6">
                    <div class="card custom-card bg-success-gradient text-white">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h5 class="text-white mb-2">
                                        <i class="la la-dollar-sign me-2"></i>
                                        خرید نقدی خودرو
                                    </h5>
                                    <p class="text-white-50 mb-3">سریع‌ترین راه برای خرید خودرو نقدی</p>
                                    <a href="<?php echo home_url('/dashboard/new-cash-inquiry'); ?>" class="btn btn-light btn-sm btn-wave">
                                        <i class="la la-plus-circle me-1"></i>
                                        ثبت درخواست نقدی جدید
                                    </a>
                                </div>
                                <div>
                                    <i class="la la-hand-holding-usd" style="font-size: 60px; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="card custom-card bg-info-gradient text-white">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h5 class="text-white mb-2">
                                        <i class="la la-credit-card me-2"></i>
                                        خرید اقساطی خودرو
                                    </h5>
                                    <p class="text-white-50 mb-3">خودرو رویایی خود را با اقساط راحت بخرید</p>
                                    <a href="<?php echo home_url('/dashboard/new-inquiry'); ?>" class="btn btn-light btn-sm btn-wave">
                                        <i class="la la-plus-circle me-1"></i>
                                        ثبت درخواست اقساطی جدید
                                    </a>
                                </div>
                                <div>
                                    <i class="la la-calendar-check" style="font-size: 60px; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Inquiries -->
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="la la-history me-2"></i>
                        آخرین استعلامات شما
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_inquiries)): ?>
                        <div class="text-center py-5">
                            <i class="la la-inbox" style="font-size: 60px; color: #dee2e6;"></i>
                            <p class="text-muted mt-3">هنوز استعلامی ثبت نکرده‌اید</p>
                            <a href="<?php echo home_url('/dashboard/new-inquiry'); ?>" class="btn btn-primary">
                                <i class="la la-plus me-1"></i>
                                ثبت اولین استعلام
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>شناسه</th>
                                        <th>نوع</th>
                                        <th>خودرو</th>
                                        <th>وضعیت</th>
                                        <th>تاریخ</th>
                                        <th>عملیات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_inquiries as $inq):
                                        $post_type = get_post_type($inq);
                                        $is_cash = ($post_type === 'cash_inquiry');
                                        $status = get_post_meta($inq->ID, $is_cash ? 'cash_inquiry_status' : 'inquiry_status', true);
                                        $product_id = get_post_meta($inq->ID, 'product_id', true);
                                        
                                        $status_badge = [
                                            'pending' => ['label' => 'در انتظار', 'class' => 'warning'],
                                            'approved' => ['label' => 'تایید شده', 'class' => 'success'],
                                            'user_confirmed' => ['label' => 'تایید شده', 'class' => 'success'],
                                            'rejected' => ['label' => 'رد شده', 'class' => 'danger'],
                                            'completed' => ['label' => 'تکمیل شده', 'class' => 'success'],
                                        ];
                                        $badge = $status_badge[$status] ?? ['label' => 'نامشخص', 'class' => 'secondary'];
                                        
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
                                            <td>#<?php echo $inq->ID; ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $is_cash ? 'warning' : 'info'; ?>-transparent">
                                                    <?php echo $is_cash ? 'نقدی' : 'اقساطی'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo esc_html(get_the_title($product_id)); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $badge['class']; ?>">
                                                    <?php echo $badge['label']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $date; ?></td>
                                            <td>
                                                <a href="<?php echo esc_url($view_url); ?>" class="btn btn-sm btn-primary-light">
                                                    <i class="la la-eye"></i> مشاهده
                                                </a>
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
    
<?php } else {
    // ════════════════════════════════════════════════════════════
    // ADMIN / EXPERT DASHBOARD
    // ════════════════════════════════════════════════════════════
    
    // Determine expert filter
    $expert_id = null;
    if ($is_expert) {
        $expert_id = get_current_user_id();
    }
    
    // Get statistics
    $stats = Maneli_Reports_Dashboard::get_overall_statistics($start_date, $end_date, $expert_id);
    $daily_stats = Maneli_Reports_Dashboard::get_daily_statistics($start_date, $end_date, $expert_id, 7); // Last 7 days
    $popular_products = Maneli_Reports_Dashboard::get_popular_products($start_date, $end_date, $expert_id, 5);
    
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
    
    // Enqueue Chart.js
    wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', [], '4.4.0', true);
    ?>
    
    <div class="row">
        <div class="col-xl-8">
            <!-- Statistics Cards -->
            <div class="row">
                <?php if ($is_expert): ?>
                    <!-- Expert-specific stats -->
                    <div class="col-xxl-3 col-xl-6">
                        <div class="card custom-card overflow-hidden">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between mb-2">
                                    <div>
                                        <span class="text-muted d-block mb-1">ارجاع شده به من</span>
                                        <h4 class="fw-semibold mb-0"><?php echo number_format_i18n($stats['total_inquiries']); ?></h4>
                                    </div>
                                    <div class="lh-1">
                                        <span class="avatar avatar-md avatar-rounded bg-primary">
                                            <i class="la la-user-check fs-20"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="text-muted fs-12">
                                    استعلامات محول شده
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xxl-3 col-xl-6">
                        <div class="card custom-card overflow-hidden">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between mb-2">
                                    <div>
                                        <span class="text-muted d-block mb-1">در حال پیگیری</span>
                                        <h4 class="fw-semibold mb-0 text-primary"><?php echo number_format_i18n($stats['in_progress']); ?></h4>
                                    </div>
                                    <div class="lh-1">
                                        <span class="avatar avatar-md avatar-rounded bg-primary">
                                            <i class="la la-spinner fs-20"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="text-muted fs-12">
                                    در حال بررسی توسط شما
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xxl-3 col-xl-6">
                        <div class="card custom-card overflow-hidden">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between mb-2">
                                    <div>
                                        <span class="text-muted d-block mb-1">تکمیل شده</span>
                                        <h4 class="fw-semibold mb-0 text-success"><?php echo number_format_i18n($stats['completed']); ?></h4>
                                    </div>
                                    <div class="lh-1">
                                        <span class="avatar avatar-md avatar-rounded bg-success">
                                            <i class="la la-check-circle fs-20"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="text-muted fs-12">
                                    نرخ موفقیت: <span class="text-success"><?php echo $stats['total_inquiries'] > 0 ? round(($stats['completed'] / $stats['total_inquiries']) * 100, 1) : 0; ?>%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xxl-3 col-xl-6">
                        <div class="card custom-card overflow-hidden">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between mb-2">
                                    <div>
                                        <span class="text-muted d-block mb-1">پیگیری امروز</span>
                                        <h4 class="fw-semibold mb-0 text-warning"><?php echo number_format_i18n($today_followups); ?></h4>
                                    </div>
                                    <div class="lh-1">
                                        <span class="avatar avatar-md avatar-rounded bg-warning">
                                            <i class="la la-calendar-day fs-20"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="text-muted fs-12">
                                    نیاز به پیگیری امروز
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Admin stats -->
                    <div class="col-xxl-3 col-xl-6">
                        <div class="card custom-card overflow-hidden">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between mb-2">
                                    <div>
                                        <span class="text-muted d-block mb-1">مجموع استعلامات</span>
                                        <h4 class="fw-semibold mb-0"><?php echo number_format_i18n($stats['total_inquiries']); ?></h4>
                                    </div>
                                    <div class="lh-1">
                                        <span class="avatar avatar-md avatar-rounded bg-primary">
                                            <i class="la la-file-alt fs-20"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="text-muted fs-12">
                                    <?php if ($total_growth >= 0): ?>
                                        افزایش <span class="text-success"><?php echo $total_growth; ?>%<i class="la la-arrow-up"></i></span>
                                    <?php else: ?>
                                        کاهش <span class="text-danger"><?php echo abs($total_growth); ?>%<i class="la la-arrow-down"></i></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xxl-3 col-xl-6">
                        <div class="card custom-card overflow-hidden">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between mb-2">
                                    <div>
                                        <span class="text-muted d-block mb-1">جدید</span>
                                        <h4 class="fw-semibold mb-0 text-secondary"><?php echo number_format_i18n($stats['new']); ?></h4>
                                    </div>
                                    <div class="lh-1">
                                        <span class="avatar avatar-md avatar-rounded bg-secondary">
                                            <i class="la la-file-alt fs-20"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="text-muted fs-12">
                                    منتظر ارجاع به کارشناس
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xxl-3 col-xl-6">
                        <div class="card custom-card overflow-hidden">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between mb-2">
                                    <div>
                                        <span class="text-muted d-block mb-1">ارجاع شده</span>
                                        <h4 class="fw-semibold mb-0 text-info"><?php echo number_format_i18n($stats['referred']); ?></h4>
                                    </div>
                                    <div class="lh-1">
                                        <span class="avatar avatar-md avatar-rounded bg-info">
                                            <i class="la la-share fs-20"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="text-muted fs-12">
                                    ارجاع داده شده به کارشناس
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xxl-3 col-xl-6">
                        <div class="card custom-card overflow-hidden">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between mb-2">
                                    <div>
                                        <span class="text-muted d-block mb-1">در حال پیگیری</span>
                                        <h4 class="fw-semibold mb-0 text-primary"><?php echo number_format_i18n($stats['in_progress']); ?></h4>
                                    </div>
                                    <div class="lh-1">
                                        <span class="avatar avatar-md avatar-rounded bg-primary">
                                            <i class="la la-spinner fs-20"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="text-muted fs-12">
                                    در حال پیگیری توسط کارشناس
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xxl-3 col-xl-6">
                        <div class="card custom-card overflow-hidden">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between mb-2">
                                    <div>
                                        <span class="text-muted d-block mb-1">تکمیل شده</span>
                                        <h4 class="fw-semibold mb-0 text-success"><?php echo number_format_i18n($stats['completed']); ?></h4>
                                    </div>
                                    <div class="lh-1">
                                        <span class="avatar avatar-md avatar-rounded bg-success">
                                            <i class="la la-check-circle fs-20"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="text-muted fs-12">
                                    نرخ موفقیت: <span class="text-success"><?php echo $stats['total_inquiries'] > 0 ? round(($stats['completed'] / $stats['total_inquiries']) * 100, 1) : 0; ?>%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Chart -->
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="la la-chart-line me-2"></i>
                        روند استعلامات (7 روز اخیر)
                    </div>
                </div>
                <div class="card-body">
                    <div style="height: 300px; position: relative;">
                        <canvas id="dailyTrendChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Recent Inquiries -->
            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div class="card-title">
                        <i class="la la-list me-2"></i>
                        آخرین استعلامات
                    </div>
                    <div>
                        <a href="<?php echo home_url('/dashboard/inquiries'); ?>" class="btn btn-sm btn-primary-light">
                            مشاهده همه
                            <i class="la la-arrow-left ms-1"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table text-nowrap table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>شناسه</th>
                                    <th>نوع</th>
                                    <th>مشتری</th>
                                    <th>خودرو</th>
                                    <th>وضعیت</th>
                                    <th>تاریخ</th>
                                    <th>عملیات</th>
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
                                        $customer_name = $author ? $author->display_name : 'نامشخص';
                                        $status = get_post_meta($inq->ID, 'inquiry_status', true);
                                    }
                                    
                                    $product_id = get_post_meta($inq->ID, 'product_id', true);
                                    
                                    $status_badges = [
                                        'pending' => ['label' => 'در انتظار', 'class' => 'warning'],
                                        'approved' => ['label' => 'تایید', 'class' => 'success'],
                                        'user_confirmed' => ['label' => 'تایید', 'class' => 'info'],
                                        'rejected' => ['label' => 'رد شده', 'class' => 'danger'],
                                    ];
                                    $badge = $status_badges[$status] ?? ['label' => 'نامشخص', 'class' => 'secondary'];
                                    
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
                                        <td>#<?php echo $inq->ID; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $is_cash ? 'warning' : 'info'; ?>-transparent">
                                                <?php echo $is_cash ? 'نقدی' : 'اقساطی'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo esc_html($customer_name); ?></td>
                                        <td><?php echo esc_html(get_the_title($product_id)); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $badge['class']; ?>-transparent">
                                                <?php echo $badge['label']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $date; ?></td>
                                        <td>
                                            <a href="<?php echo esc_url($view_url); ?>" class="btn btn-sm btn-icon btn-primary-light">
                                                <i class="la la-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-4">
            <!-- Quick Stats -->
            <div class="card custom-card">
                <div class="card-body p-4">
                    <div class="d-flex align-items-start gap-3">
                        <div class="avatar avatar-md bg-primary-transparent">
                            <i class="la la-chart-bar fs-20"></i>
                        </div>
                        <div class="flex-fill">
                            <span class="fs-12 mb-1 d-block fw-medium">استعلامات امروز</span>
                            <h4 class="mb-0 d-flex align-items-center">
                                <?php echo number_format_i18n($stats['new_today']); ?>
                            </h4>
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
                                <i class="la la-inbox text-muted fs-40"></i>
                                <p class="text-muted mt-2 mb-0">داده‌ای موجود نیست</p>
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
                                                <td style="width: 30px;">
                                                    <span class="badge bg-<?php echo $color; ?>"><?php echo $rank++; ?></span>
                                                </td>
                                                <td>
                                                    <span class="fw-medium"><?php echo esc_html($product['name']); ?></span>
                                                </td>
                                                <td style="width: 80px;">
                                                    <span class="badge bg-<?php echo $color; ?>-transparent">
                                                        <?php echo number_format_i18n($product['count']); ?>
                                                    </span>
                                                </td>
                                                <td style="width: 60px;" class="text-end">
                                                    <span class="text-muted fs-11"><?php echo $percentage; ?>%</span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
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
                            'key' => 'inquiry_status',
                            'value' => 'pending',
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
                            'value' => 'pending',
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
                
                // Today's meetings
                $today_meetings = get_posts([
                    'post_type' => 'maneli_meeting',
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'meta_query' => [
                        [
                            'key' => 'meeting_start',
                            'value' => [date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')],
                            'compare' => 'BETWEEN',
                            'type' => 'DATETIME'
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
                            'value' => 'awaiting_payment',
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
                            <a href="<?php echo home_url('/dashboard/inquiries/installment'); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between <?php echo !empty($pending_installment) ? 'border-start border-danger border-3' : ''; ?>">
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm bg-danger-transparent me-2">
                                        <i class="la la-credit-card"></i>
                                    </span>
                                    <div>
                                        <div class="fw-medium">استعلامات اقساطی منتظر ارجاع</div>
                                        <small class="text-muted">نیاز به تخصیص کارشناس</small>
                                    </div>
                                </div>
                                <span class="badge bg-danger"><?php echo count($pending_installment); ?></span>
                            </a>
                            
                            <!-- Pending Cash Assignments -->
                            <a href="<?php echo home_url('/dashboard/inquiries/cash'); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between <?php echo !empty($pending_cash) ? 'border-start border-warning border-3' : ''; ?>">
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm bg-warning-transparent me-2">
                                        <i class="la la-dollar-sign"></i>
                                    </span>
                                    <div>
                                        <div class="fw-medium">استعلامات نقدی منتظر ارجاع</div>
                                        <small class="text-muted">نیاز به تخصیص کارشناس</small>
                                    </div>
                                </div>
                                <span class="badge bg-warning"><?php echo count($pending_cash); ?></span>
                            </a>
                            
                            <!-- Today's Meetings -->
                            <a href="<?php echo home_url('/dashboard/calendar'); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between <?php echo !empty($today_meetings) ? 'border-start border-info border-3' : ''; ?>">
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm bg-info-transparent me-2">
                                        <i class="la la-calendar"></i>
                                    </span>
                                    <div>
                                        <div class="fw-medium">جلسات امروز</div>
                                        <small class="text-muted">برنامه‌های حضوری</small>
                                    </div>
                                </div>
                                <span class="badge bg-info"><?php echo count($today_meetings); ?></span>
                            </a>
                            
                            <!-- Overdue Followups -->
                            <a href="<?php echo home_url('/dashboard/followups'); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between <?php echo !empty($overdue_followups) ? 'border-start border-danger border-3' : ''; ?>">
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm bg-danger-transparent me-2">
                                        <i class="la la-exclamation-triangle"></i>
                                    </span>
                                    <div>
                                        <div class="fw-medium">پیگیری‌های عقب‌افتاده</div>
                                        <small class="text-muted">نیاز به اقدام فوری</small>
                                    </div>
                                </div>
                                <span class="badge bg-danger"><?php echo count($overdue_followups); ?></span>
                            </a>
                            
                            <!-- Awaiting Payment -->
                            <a href="<?php echo home_url('/dashboard/inquiries/cash'); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm bg-success-transparent me-2">
                                        <i class="la la-money-bill"></i>
                                    </span>
                                    <div>
                                        <div class="fw-medium">منتظر پرداخت</div>
                                        <small class="text-muted">استعلامات نقدی</small>
                                    </div>
                                </div>
                                <span class="badge bg-success"><?php echo count($awaiting_payment); ?></span>
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
                                            <td style="width: 40px;">
                                                <span class="badge bg-<?php echo $color; ?> rounded-pill"><?php echo $rank++; ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="la la-car text-<?php echo $color; ?> me-2 fs-18"></i>
                                                    <span class="fw-medium"><?php echo esc_html($product['name']); ?></span>
                                                </div>
                                            </td>
                                            <td style="width: 100px;">
                                                <span class="badge bg-<?php echo $color; ?>-transparent">
                                                    <?php echo number_format_i18n($product['count']); ?> استعلام
                                                </span>
                                            </td>
                                            <td style="width: 100px;">
                                                <div class="progress" style="height: 6px;">
                                                    <div class="progress-bar bg-<?php echo $color; ?>" style="width: <?php echo $percentage; ?>%;"></div>
                                                </div>
                                                <small class="text-muted"><?php echo $percentage; ?>%</small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Cash vs Installment OR Upcoming Followups -->
            <?php if ($is_expert && !empty($upcoming_followups)): ?>
                <!-- Upcoming Followups for Expert -->
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
                                                <div class="fw-medium"><?php echo esc_html($customer ? $customer->display_name : 'نامشخص'); ?></div>
                                                <small class="text-muted"><?php echo esc_html(get_the_title($product_id)); ?></small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-semibold <?php echo $is_today ? 'text-danger' : 'text-info'; ?>">
                                                <?php echo esc_html($jalali_date); ?>
                                            </div>
                                            <?php if ($is_today): ?>
                                                <small class="badge bg-danger">امروز</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="card-footer text-center">
                        <a href="<?php echo home_url('/dashboard/followups'); ?>" class="btn btn-sm btn-primary-light">
                            مشاهده همه پیگیری‌ها
                            <i class="la la-arrow-left ms-1"></i>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Cash vs Installment -->
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="la la-chart-pie me-2"></i>
                            نقدی vs اقساطی
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center">
                                <span class="avatar avatar-sm bg-warning-transparent me-2">
                                    <i class="la la-dollar-sign"></i>
                                </span>
                                <span class="fw-medium">نقدی</span>
                            </div>
                            <span class="badge bg-warning-transparent fs-14">
                                <?php echo number_format_i18n($stats['cash_inquiries']); ?>
                            </span>
                        </div>
                        <div class="progress mb-3" style="height: 8px;">
                            <div class="progress-bar bg-warning" style="width: <?php echo $stats['total_inquiries'] > 0 ? round(($stats['cash_inquiries'] / $stats['total_inquiries']) * 100) : 0; ?>%;"></div>
                        </div>
                        
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center">
                                <span class="avatar avatar-sm bg-info-transparent me-2">
                                    <i class="la la-credit-card"></i>
                                </span>
                                <span class="fw-medium">اقساطی</span>
                            </div>
                            <span class="badge bg-info-transparent fs-14">
                                <?php echo number_format_i18n($stats['installment_inquiries']); ?>
                            </span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-info" style="width: <?php echo $stats['total_inquiries'] > 0 ? round(($stats['installment_inquiries'] / $stats['total_inquiries']) * 100) : 0; ?>%;"></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Wait for Chart.js to load
        function initChart() {
            if (typeof Chart === 'undefined') {
                setTimeout(initChart, 100);
                return;
            }
            
            const ctx = document.getElementById('dailyTrendChart');
            if (!ctx) {
                console.warn('Canvas element not found');
                return;
            }
            
            <?php if (!empty($daily_stats)): ?>
                const dailyData = <?php echo json_encode(array_values($daily_stats)); ?>;
                
                if (!dailyData || dailyData.length === 0) {
                    console.warn('No daily stats data');
                    return;
                }
                
                const labels = dailyData.map(item => item.date);
                const totalData = dailyData.map(item => parseInt(item.total) || 0);
                const cashData = dailyData.map(item => parseInt(item.cash) || 0);
                const installmentData = dailyData.map(item => parseInt(item.installment) || 0);
                
                console.log('Chart data:', { labels, totalData, cashData, installmentData });
                
                try {
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: 'کل استعلامات',
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
                                    label: 'نقدی',
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
                                    label: 'اقساطی',
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
                    console.error('Chart initialization error:', error);
                }
            <?php else: ?>
                console.warn('daily_stats is empty');
                ctx.parentElement.innerHTML = '<div class="text-center py-5"><i class="la la-chart-line text-muted" style="font-size: 60px;"></i><p class="text-muted mt-3">داده‌ای برای نمایش نمودار موجود نیست</p></div>';
            <?php endif; ?>
        }
        
        // Start initialization
        initChart();
    });
    </script>
    
<?php } ?>
<!-- End:: row-1 -->

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
