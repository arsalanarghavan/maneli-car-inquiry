<!-- Start::row -->
<?php
/**
 * Followups Dashboard Page - Direct Implementation
 * Shows inquiries with tracking_status = 'follow_up'
 */

// Check permission
if (!current_user_can('manage_maneli_inquiries') && !in_array('maneli_expert', wp_get_current_user()->roles, true)) {
    echo '<div class="alert alert-danger">شما دسترسی به این صفحه را ندارید.</div>';
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
    'meta_key' => 'follow_up_date',
    'order' => 'ASC',
    'post_status' => 'publish',
    'meta_query' => [
        [
            'key' => 'tracking_status',
            'value' => 'follow_up',
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
    $follow_up_date = get_post_meta($inquiry->ID, 'follow_up_date', true);
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

if (!wp_script_is('maneli-jalali-datepicker', 'enqueued')) {
    wp_enqueue_script('maneli-jalali-datepicker', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/vendor/kamadatepicker.min.js', [], '2.1.0', true);
    wp_enqueue_style('maneli-datepicker-theme', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-datepicker-theme.css', [], '1.0.0');
}
?>

<div class="row">
    <div class="col-xl-12">
        <!-- Statistics Cards -->
        <div class="row mb-4">
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
                                    <span class="text-muted fs-13">پیگیری‌های امروز</span>
                                </div>
                                <h4 class="fw-semibold mb-0"><?php echo number_format_i18n($today_count); ?></h4>
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
                                    <i class="la la-exclamation-triangle fs-24"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="mb-1">
                                    <span class="text-muted fs-13">عقب‌افتاده</span>
                                </div>
                                <h4 class="fw-semibold mb-0 text-danger"><?php echo number_format_i18n($overdue_count); ?></h4>
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
                                <span class="avatar avatar-md bg-info-transparent">
                                    <i class="la la-calendar-alt fs-24"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="mb-1">
                                    <span class="text-muted fs-13">این هفته</span>
                                </div>
                                <h4 class="fw-semibold mb-0"><?php echo number_format_i18n($week_count); ?></h4>
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
                                    <i class="la la-list-alt fs-24"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="mb-1">
                                    <span class="text-muted fs-13">مجموع</span>
                                </div>
                                <h4 class="fw-semibold mb-0"><?php echo number_format_i18n($total_count); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Card -->
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="la la-tasks me-2"></i>
                    لیست پیگیری‌های استعلامات اقساطی
                </div>
            </div>
            <div class="card-body">
                <!-- Info Alert -->
                <div class="alert alert-info border-info d-flex align-items-center" role="alert">
                    <i class="la la-info-circle fs-20 me-2"></i>
                    <div>
                        <strong>راهنما:</strong>
                        استعلاماتی که نیاز به پیگیری در تاریخ‌های مشخص دارند، در اینجا نمایش داده می‌شوند.
                    </div>
                </div>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-nowrap">
                        <thead class="table-primary">
                            <tr>
                                <th><i class="la la-hashtag me-1"></i>شناسه</th>
                                <th><i class="la la-user me-1"></i>مشتری</th>
                                <th><i class="la la-car me-1"></i>خودرو</th>
                                <th><i class="la la-calendar me-1"></i>تاریخ پیگیری</th>
                                <th><i class="la la-info-circle me-1"></i>وضعیت</th>
                                <?php if ($is_admin): ?>
                                    <th><i class="la la-user-tie me-1"></i>کارشناس</th>
                                <?php endif; ?>
                                <th><i class="la la-clock me-1"></i>تاریخ ثبت</th>
                                <th><i class="la la-wrench me-1"></i>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($followups)): ?>
                                <tr>
                                    <td colspan="<?php echo $is_admin ? '8' : '7'; ?>" class="text-center">
                                        <div class="py-5">
                                            <i class="la la-inbox" style="font-size: 60px; color: #dee2e6;"></i>
                                            <p class="text-muted mt-3">هیچ پیگیری‌ای یافت نشد.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($followups as $inquiry): 
                                    $inquiry_id = $inquiry->ID;
                                    $customer = get_userdata($inquiry->post_author);
                                    $product_id = get_post_meta($inquiry_id, 'product_id', true);
                                    $follow_up_date = get_post_meta($inquiry_id, 'follow_up_date', true);
                                    $inquiry_status = get_post_meta($inquiry_id, 'inquiry_status', true);
                                    $expert_id = get_post_meta($inquiry_id, 'assigned_expert_id', true);
                                    $expert = $expert_id ? get_userdata($expert_id) : null;
                                    
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
                                    
                                    // Status badge
                                    $status_labels = [
                                        'pending' => ['label' => 'در انتظار', 'class' => 'warning'],
                                        'user_confirmed' => ['label' => 'تایید کاربر', 'class' => 'info'],
                                        'approved' => ['label' => 'تایید شده', 'class' => 'success'],
                                        'rejected' => ['label' => 'رد شده', 'class' => 'danger'],
                                    ];
                                    $status_data = $status_labels[$inquiry_status] ?? ['label' => 'نامشخص', 'class' => 'secondary'];
                                    
                                    // Overdue check
                                    $is_overdue = $follow_up_date && $follow_up_date < $today;
                                    $row_class = $is_overdue ? 'table-danger' : '';
                                ?>
                                    <tr class="<?php echo $row_class; ?>">
                                        <td>#<?php echo $inquiry_id; ?></td>
                                        <td><?php echo esc_html($customer ? $customer->display_name : 'نامشخص'); ?></td>
                                        <td><?php echo esc_html(get_the_title($product_id)); ?></td>
                                        <td>
                                            <?php if ($follow_up_date): ?>
                                                <strong class="<?php echo $is_overdue ? 'text-danger' : 'text-success'; ?>">
                                                    <?php echo esc_html($follow_up_date); ?>
                                                </strong>
                                                <?php if ($is_overdue): ?>
                                                    <br><small class="badge bg-danger">عقب‌افتاده</small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $status_data['class']; ?>">
                                                <?php echo $status_data['label']; ?>
                                            </span>
                                        </td>
                                        <?php if ($is_admin): ?>
                                            <td>
                                                <?php echo $expert ? esc_html($expert->display_name) : '<span class="text-muted">—</span>'; ?>
                                            </td>
                                        <?php endif; ?>
                                        <td><?php echo esc_html($created_date); ?></td>
                                        <td>
                                            <div class="btn-list">
                                                <a href="<?php echo add_query_arg('inquiry_id', $inquiry_id, home_url('/dashboard/inquiries/installment')); ?>" 
                                                   class="btn btn-sm btn-primary-light">
                                                    <i class="la la-eye"></i> مشاهده
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

<style>
/* Follow-up List Custom Styles */
.table-primary th {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
    color: white;
    font-weight: 600;
    border: none;
}

.table-primary th i {
    opacity: 0.9;
}

.table-hover tbody tr:hover:not(.table-danger) {
    background-color: rgba(var(--primary-rgb), 0.03);
    transform: scale(1.01);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

.table-danger {
    background-color: rgba(220, 53, 69, 0.05) !important;
}

.table-danger:hover {
    background-color: rgba(220, 53, 69, 0.1) !important;
}
</style>
