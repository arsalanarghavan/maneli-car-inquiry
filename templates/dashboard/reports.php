<!-- Start::row -->
<?php
/**
 * Reports Dashboard Page - Direct Implementation
 * Shows system statistics and charts
 */

// Check permission
if (!current_user_can('manage_maneli_inquiries')) {
    echo '<div class="alert alert-danger">شما دسترسی به این صفحه را ندارید.</div>';
    return;
}

// Load reports class
require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-reports-dashboard.php';

// Calculate date range
$days = 30;
$start_date = date('Y-m-d', strtotime("-{$days} days"));
$end_date = date('Y-m-d');

// Determine expert
$expert_id = null;
$current_user = wp_get_current_user();

if (in_array('maneli_expert', $current_user->roles)) {
    $expert_id = $current_user->ID;
}

// Get statistics
$stats = Maneli_Reports_Dashboard::get_overall_statistics($start_date, $end_date, $expert_id);
$daily_stats = Maneli_Reports_Dashboard::get_daily_statistics($start_date, $end_date, $expert_id);
$popular_products = Maneli_Reports_Dashboard::get_popular_products($start_date, $end_date, $expert_id, 5);

// Get experts stats (admin only)
$experts_stats = [];
if (current_user_can('manage_options') && !$expert_id) {
    $experts_stats = Maneli_Reports_Dashboard::get_experts_statistics($start_date, $end_date);
}

$is_expert = in_array('maneli_expert', $current_user->roles);

// Enqueue Chart.js
wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', [], '4.4.0', true);
?>

<div class="row">
    <div class="col-xl-12">
        <!-- Header Card -->
        <div class="card custom-card mb-4">
            <div class="card-header bg-primary-transparent">
                <div class="card-title">
                    <i class="la la-chart-bar me-2 fs-20"></i>
                    <?php echo $is_expert ? 'گزارش عملکرد من' : 'گزارشات سیستم'; ?>
                </div>
                <div class="card-subtitle text-muted mt-1">
                    <i class="la la-calendar me-1"></i>
                    بازه زمانی: <?php echo $days; ?> روز گذشته
                    <?php if ($is_expert): ?>
                        | کارشناس: <strong><?php echo esc_html($current_user->display_name); ?></strong>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-primary-transparent">
                                    <i class="la la-list fs-24"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="mb-1">
                                    <span class="text-muted fs-12">کل استعلام‌ها</span>
                                </div>
                                <h4 class="fw-semibold mb-0"><?php echo number_format_i18n($stats['total_inquiries']); ?></h4>
                                <small class="text-muted">
                                    <i class="la la-calendar-day me-1"></i>
                                    امروز: <strong><?php echo number_format_i18n($stats['new_today']); ?></strong>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-warning-transparent">
                                    <i class="la la-dollar-sign fs-24"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="mb-1">
                                    <span class="text-muted fs-12">استعلام نقدی</span>
                                </div>
                                <h4 class="fw-semibold mb-0 text-warning"><?php echo number_format_i18n($stats['cash_inquiries']); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-info-transparent">
                                    <i class="la la-calculator fs-24"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="mb-1">
                                    <span class="text-muted fs-12">استعلام اقساطی</span>
                                </div>
                                <h4 class="fw-semibold mb-0 text-info"><?php echo number_format_i18n($stats['installment_inquiries']); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-lg-6 col-md-6">
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
                                    <span class="text-muted fs-12">تایید شده</span>
                                </div>
                                <h4 class="fw-semibold mb-0 text-success"><?php echo number_format_i18n($stats['approved']); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Second Row Statistics -->
        <div class="row mb-4">
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-secondary-transparent">
                                    <i class="la la-clock fs-24"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="mb-1">
                                    <span class="text-muted fs-12">در انتظار</span>
                                </div>
                                <h4 class="fw-semibold mb-0"><?php echo number_format_i18n($stats['pending']); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-lg-6 col-md-6">
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
                                    <span class="text-muted fs-12">رد شده</span>
                                </div>
                                <h4 class="fw-semibold mb-0 text-danger"><?php echo number_format_i18n($stats['rejected']); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-purple-transparent">
                                    <i class="la la-eye fs-24"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="mb-1">
                                    <span class="text-muted fs-12">در حال پیگیری</span>
                                </div>
                                <h4 class="fw-semibold mb-0"><?php echo number_format_i18n($stats['following']); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-teal-transparent">
                                    <i class="la la-calendar-check fs-24"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="mb-1">
                                    <span class="text-muted fs-12">پیگیری آینده</span>
                                </div>
                                <h4 class="fw-semibold mb-0"><?php echo number_format_i18n($stats['next_followup']); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue Card -->
        <?php if (!empty($stats['revenue'])): ?>
        <div class="row mb-4">
            <div class="col-xl-12">
                <div class="card custom-card bg-gradient-primary text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-white-50 mb-2">درآمد کل (تومان)</h6>
                                <h3 class="fw-bold mb-0"><?php echo number_format_i18n($stats['revenue']); ?></h3>
                            </div>
                            <div>
                                <span class="avatar avatar-lg bg-white-transparent">
                                    <i class="la la-shopping-cart fs-32"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Daily Trend Chart -->
        <?php if (!empty($daily_stats)): ?>
        <div class="row mb-4">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="la la-chart-line me-2"></i>
                            روند استعلام‌های روزانه
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="dailyTrendChart" height="80"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Popular Products -->
        <?php if (!empty($popular_products)): ?>
        <div class="row mb-4">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="la la-car me-2"></i>
                            محصولات پرطرفدار
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>رتبه</th>
                                        <th>نام محصول</th>
                                        <th>تعداد استعلام</th>
                                        <th>درصد</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $rank = 1;
                                    foreach ($popular_products as $product): 
                                        $percentage = $stats['total_inquiries'] > 0 ? round(($product['count'] / $stats['total_inquiries']) * 100, 1) : 0;
                                    ?>
                                        <tr>
                                            <td><span class="badge bg-primary"><?php echo $rank++; ?></span></td>
                                            <td><?php echo esc_html($product['name']); ?></td>
                                            <td><strong><?php echo number_format_i18n($product['count']); ?></strong></td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar" role="progressbar" style="width: <?php echo $percentage; ?>%">
                                                        <?php echo $percentage; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Experts Statistics -->
        <?php if (!empty($experts_stats)): ?>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="la la-users-cog me-2"></i>
                            عملکرد کارشناسان
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-primary">
                                    <tr>
                                        <th>نام کارشناس</th>
                                        <th>کل استعلامات</th>
                                        <th>تایید شده</th>
                                        <th>رد شده</th>
                                        <th>در انتظار</th>
                                        <th>نرخ موفقیت</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($experts_stats as $expert_stat): 
                                        $success_rate = $expert_stat['total'] > 0 ? round(($expert_stat['approved'] / $expert_stat['total']) * 100, 1) : 0;
                                    ?>
                                        <tr>
                                            <td><strong><?php echo esc_html($expert_stat['name']); ?></strong></td>
                                            <td><?php echo number_format_i18n($expert_stat['total']); ?></td>
                                            <td><span class="badge bg-success"><?php echo number_format_i18n($expert_stat['approved']); ?></span></td>
                                            <td><span class="badge bg-danger"><?php echo number_format_i18n($expert_stat['rejected']); ?></span></td>
                                            <td><span class="badge bg-warning"><?php echo number_format_i18n($expert_stat['pending']); ?></span></td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $success_rate; ?>%">
                                                        <?php echo $success_rate; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<!-- End::row -->

<?php if (!empty($daily_stats)): ?>
<script>
jQuery(document).ready(function($) {
    // Daily Trend Chart
    const ctx = document.getElementById('dailyTrendChart');
    if (ctx) {
        const dailyData = <?php echo json_encode(array_values($daily_stats)); ?>;
        const labels = dailyData.map(item => item.date);
        const totalData = dailyData.map(item => item.total);
        const cashData = dailyData.map(item => item.cash);
        const installmentData = dailyData.map(item => item.installment);
        
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
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'نقدی',
                        data: cashData,
                        borderColor: 'rgb(255, 159, 64)',
                        backgroundColor: 'rgba(255, 159, 64, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'اقساطی',
                        data: installmentData,
                        borderColor: 'rgb(54, 162, 235)',
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
});
</script>
<?php endif; ?>

<style>
.bg-gradient-primary {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
}

.bg-white-transparent {
    background: rgba(255, 255, 255, 0.2);
}

.bg-purple-transparent {
    background: rgba(156, 39, 176, 0.1);
    color: #9c27b0;
}

.bg-teal-transparent {
    background: rgba(0, 150, 136, 0.1);
    color: #009688;
}

.table-primary th {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
    color: white;
    font-weight: 600;
}

#dailyTrendChart {
    min-height: 300px;
}
</style>
