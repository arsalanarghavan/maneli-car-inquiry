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
                                        if ($is_cash) {
                                            $status = get_post_meta($inq->ID, 'cash_inquiry_status', true);
                                        } else {
                                            $status = get_post_meta($inq->ID, 'tracking_status', true) ?: 'new';
                                        }
                                        $product_id = get_post_meta($inq->ID, 'product_id', true);
                                        
                                        // تعریف وضعیت‌های مختلف برای نقدی و اقساطی
                                        if ($is_cash) {
                                            $status_badge = [
                                                'new' => ['label' => 'جدید', 'class' => 'primary'],
                                                'pending' => ['label' => 'در انتظار', 'class' => 'warning'],
                                                'approved' => ['label' => 'تایید شده', 'class' => 'success'],
                                                'completed' => ['label' => 'تکمیل شده', 'class' => 'success'],
                                                'rejected' => ['label' => 'رد شده', 'class' => 'danger'],
                                                'cancelled' => ['label' => 'لغو شده', 'class' => 'secondary'],
                                            ];
                                        } else {
                                            $status_badge = [
                                                'new' => ['label' => 'جدید', 'class' => 'primary'],
                                                'referred' => ['label' => 'ارجاع شده', 'class' => 'info'],
                                                'in_progress' => ['label' => 'در حال پیگیری', 'class' => 'warning'],
                                                'user_confirmed' => ['label' => 'تایید شده', 'class' => 'success'],
                                                'completed' => ['label' => 'تکمیل شده', 'class' => 'success'],
                                                'rejected' => ['label' => 'رد شده', 'class' => 'danger'],
                                                'cancelled' => ['label' => 'لغو شده', 'class' => 'secondary'],
                                                'follow_up_scheduled' => ['label' => 'پیگیری برنامه‌ریزی', 'class' => 'info'],
                                            ];
                                        }
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
    
    // Get separate statistics for cash and installment inquiries
    $cash_stats = get_separate_cash_statistics($start_date, $end_date, $expert_id);
    $installment_stats = get_separate_installment_statistics($start_date, $end_date, $expert_id);
    
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
    
    // اضافه کردن Chart.js به صورت inline برای اطمینان
    wp_add_inline_script('chartjs', '
        window.addEventListener("load", function() {
            if (typeof Chart === "undefined") {
                console.log("Chart.js CDN failed, loading from local...");
                var script = document.createElement("script");
                script.src = "' . MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/chart.js/chart.min.js";
                script.onload = function() {
                    console.log("Chart.js loaded from local");
                };
                document.head.appendChild(script);
            }
        });
    ');
    ?>
    
    <div class="row">
        <div class="col-xl-8">
            <!-- Detailed Statistics for Admin -->
            <?php if ($is_admin): ?>
            
            <div class="row mb-4">
                <!-- Cash Inquiry Detailed Stats -->
                <div class="col-xl-6">
                    <div class="card custom-card">
                        <div class="card-header bg-warning-transparent">
                            <div class="card-title">
                                <i class="la la-dollar-sign me-2"></i>
                                آمار تفصیلی استعلامات نقدی
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <div class="text-center p-3 border rounded">
                                        <h4 class="fw-bold text-warning"><?php echo number_format_i18n($cash_stats['total']); ?></h4>
                                        <small class="text-muted">کل نقدی</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-3 border rounded">
                                        <h4 class="fw-bold text-success"><?php echo number_format_i18n($cash_stats['completed']); ?></h4>
                                        <small class="text-muted">تکمیل شده</small>
                                    </div>
                                </div>
                                <div class="col-6 mt-2">
                                    <div class="text-center p-3 border rounded">
                                        <h4 class="fw-bold text-secondary"><?php echo number_format_i18n($cash_stats['pending']); ?></h4>
                                        <small class="text-muted">در انتظار</small>
                                    </div>
                                </div>
                                <div class="col-6 mt-2">
                                    <div class="text-center p-3 border rounded">
                                        <h4 class="fw-bold text-danger"><?php echo number_format_i18n($cash_stats['rejected']); ?></h4>
                                        <small class="text-muted">رد شده</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Installment Inquiry Detailed Stats -->
                <div class="col-xl-6">
                    <div class="card custom-card">
                        <div class="card-header bg-info-transparent">
                            <div class="card-title">
                                <i class="la la-credit-card me-2"></i>
                                آمار تفصیلی استعلامات اقساطی
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <div class="text-center p-3 border rounded">
                                        <h4 class="fw-bold text-info"><?php echo number_format_i18n($installment_stats['total']); ?></h4>
                                        <small class="text-muted">کل اقساطی</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-3 border rounded">
                                        <h4 class="fw-bold text-success"><?php echo number_format_i18n($installment_stats['user_confirmed']); ?></h4>
                                        <small class="text-muted">تایید شده</small>
                                    </div>
                                </div>
                                <div class="col-6 mt-2">
                                    <div class="text-center p-3 border rounded">
                                        <h4 class="fw-bold text-secondary"><?php echo number_format_i18n($installment_stats['pending']); ?></h4>
                                        <small class="text-muted">در انتظار</small>
                                    </div>
                                </div>
                                <div class="col-6 mt-2">
                                    <div class="text-center p-3 border rounded">
                                        <h4 class="fw-bold text-danger"><?php echo number_format_i18n($installment_stats['rejected']); ?></h4>
                                        <small class="text-muted">رد شده</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
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
                    <!-- Cash Inquiry Stats -->
                    <div class="col-xxl-3 col-xl-6">
                        <div class="card custom-card overflow-hidden border-warning">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between mb-2">
                                    <div>
                                        <span class="text-muted d-block mb-1">استعلامات نقدی</span>
                                        <h4 class="fw-semibold mb-0 text-warning"><?php echo number_format_i18n($cash_stats['total']); ?></h4>
                                    </div>
                                    <div class="lh-1">
                                        <span class="avatar avatar-md avatar-rounded bg-warning">
                                            <i class="la la-dollar-sign fs-20"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="text-muted fs-12">
                                    تکمیل شده: <span class="text-success"><?php echo number_format_i18n($cash_stats['completed']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Installment Inquiry Stats -->
                    <div class="col-xxl-3 col-xl-6">
                        <div class="card custom-card overflow-hidden border-info">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between mb-2">
                                    <div>
                                        <span class="text-muted d-block mb-1">استعلامات اقساطی</span>
                                        <h4 class="fw-semibold mb-0 text-info"><?php echo number_format_i18n($installment_stats['total']); ?></h4>
                                    </div>
                                    <div class="lh-1">
                                        <span class="avatar avatar-md avatar-rounded bg-info">
                                            <i class="la la-credit-card fs-20"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="text-muted fs-12">
                                    تایید شده: <span class="text-success"><?php echo number_format_i18n($installment_stats['user_confirmed']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total Inquiries Stats -->
                    <div class="col-xxl-3 col-xl-6">
                        <div class="card custom-card overflow-hidden border-primary">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between mb-2">
                                    <div>
                                        <span class="text-muted d-block mb-1">مجموع استعلامات</span>
                                        <h4 class="fw-semibold mb-0 text-primary"><?php echo number_format_i18n($stats['total_inquiries']); ?></h4>
                                    </div>
                                    <div class="lh-1">
                                        <span class="avatar avatar-md avatar-rounded bg-primary">
                                            <i class="la la-list-alt fs-20"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="text-muted fs-12">
                                    امروز: <span class="text-info"><?php echo number_format_i18n($stats['new_today']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Revenue Stats -->
                    <div class="col-xxl-3 col-xl-6">
                        <div class="card custom-card overflow-hidden border-success">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between mb-2">
                                    <div>
                                        <span class="text-muted d-block mb-1">درآمد کل</span>
                                        <h4 class="fw-semibold mb-0 text-success"><?php echo number_format_i18n($stats['revenue']); ?></h4>
                                    </div>
                                    <div class="lh-1">
                                        <span class="avatar avatar-md avatar-rounded bg-success">
                                            <i class="la la-money-bill-wave fs-20"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="text-muted fs-12">
                                    تومان
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
                                        $status = get_post_meta($inq->ID, 'tracking_status', true) ?: 'new';
                                    }
                                    
                                    $product_id = get_post_meta($inq->ID, 'product_id', true);
                                    
                                    // تعریف وضعیت‌های مختلف برای نقدی و اقساطی
                                    if ($is_cash) {
                                        $status_badges = [
                                            'new' => ['label' => 'جدید', 'class' => 'primary'],
                                            'pending' => ['label' => 'در انتظار', 'class' => 'warning'],
                                            'approved' => ['label' => 'تایید شده', 'class' => 'success'],
                                            'completed' => ['label' => 'تکمیل شده', 'class' => 'success'],
                                            'rejected' => ['label' => 'رد شده', 'class' => 'danger'],
                                            'cancelled' => ['label' => 'لغو شده', 'class' => 'secondary'],
                                        ];
                                    } else {
                                        $status_badges = [
                                            'new' => ['label' => 'جدید', 'class' => 'primary'],
                                            'referred' => ['label' => 'ارجاع شده', 'class' => 'info'],
                                            'in_progress' => ['label' => 'در حال پیگیری', 'class' => 'warning'],
                                            'user_confirmed' => ['label' => 'تایید شده', 'class' => 'success'],
                                            'completed' => ['label' => 'تکمیل شده', 'class' => 'success'],
                                            'rejected' => ['label' => 'رد شده', 'class' => 'danger'],
                                            'cancelled' => ['label' => 'لغو شده', 'class' => 'secondary'],
                                            'follow_up_scheduled' => ['label' => 'پیگیری برنامه‌ریزی', 'class' => 'info'],
                                        ];
                                    }
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
    
    jQuery(document).ready(function($) {
        // تست تابع تبدیل تاریخ
        console.log('Testing date conversion:');
        console.log('2024-12-05 ->', convertToJalali('2024-12-05'));
        console.log('2024-12-04 ->', convertToJalali('2024-12-04'));
        
        // Wait for Chart.js to load
        function initChart() {
            console.log('Initializing chart...');
            console.log('Chart.js available:', typeof Chart !== 'undefined');
            
            if (typeof Chart === 'undefined') {
                console.log('Chart.js not loaded, retrying...');
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
                const dailyData = <?php echo json_encode(array_values($daily_stats)); ?>;
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
                    labels = ['امروز', 'دیروز', '2 روز قبل', '3 روز قبل', '4 روز قبل', '5 روز قبل', '6 روز قبل'];
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
                console.error('Chart creation failed:', error);
                ctx.parentElement.innerHTML = '<div class="text-center py-5"><i class="la la-chart-line text-muted" style="font-size: 60px;"></i><p class="text-muted mt-3">خطا در بارگذاری نمودار</p></div>';
            }
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
