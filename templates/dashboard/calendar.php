<!-- Start::row -->
<?php
/**
 * Calendar Page - Meeting Management
 * Accessible by: Admin, Expert
 */

// Check permission
if (!current_user_can('manage_maneli_inquiries') && !in_array('maneli_expert', wp_get_current_user()->roles, true)) {
    ?>
    <div class="row">
        <div class="col-xl-12">
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="la la-exclamation-triangle me-2"></i>
                <strong>دسترسی محدود!</strong> شما به این صفحه دسترسی ندارید.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    </div>
    <?php
    return;
}

// Get meetings data
$today = current_time('Y-m-d');
$current_user_id = get_current_user_id();
$is_admin = current_user_can('manage_maneli_inquiries');
$is_expert = in_array('maneli_expert', wp_get_current_user()->roles, true);

$meetings_args = [
    'post_type' => 'maneli_meeting',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'orderby' => 'meta_value',
    'meta_key' => 'meeting_start',
    'order' => 'ASC',
    'date_query' => [
        [
            'after' => $today . ' 00:00:00',
            'inclusive' => true
        ]
    ],
];

// همه کارشناسا همه meeting ها رو میبینن تا از تداخل جلوگیری بشه
$meetings = get_posts($meetings_args);

// Group by day
$by_day = [];
$total_meetings = 0;
$today_meetings = 0;
$week_meetings = 0;

foreach ($meetings as $m) {
    $start = get_post_meta($m->ID, 'meeting_start', true);
    $inquiry_id = get_post_meta($m->ID, 'meeting_inquiry_id', true);
    $inquiry_type = get_post_meta($m->ID, 'meeting_inquiry_type', true);
    
    $customer_name = '';
    $product_name = '';
    $customer_mobile = '';
    $show_customer_info = true;
    
    // SECURITY: Check if expert can see customer info
    if ($is_expert && !$is_admin && $inquiry_id) {
        $assigned_expert_id = get_post_meta($inquiry_id, 'assigned_expert_id', true);
        // Expert can only see customer info for their own inquiries
        $show_customer_info = ($assigned_expert_id == $current_user_id);
    }
    
    if ($inquiry_type === 'cash' && get_post_type($inquiry_id) === 'cash_inquiry') {
        if ($show_customer_info) {
            $customer_name = get_post_meta($inquiry_id, 'cash_first_name', true) . ' ' . get_post_meta($inquiry_id, 'cash_last_name', true);
            $customer_mobile = get_post_meta($inquiry_id, 'mobile_number', true);
        } else {
            $customer_name = 'رزرو شده';
            $customer_mobile = '---';
        }
        $product_id = get_post_meta($inquiry_id, 'product_id', true);
        $product_name = get_the_title($product_id);
    } elseif (get_post_type($inquiry_id) === 'inquiry') {
        if ($show_customer_info) {
            $customer = get_userdata(get_post_field('post_author', $inquiry_id));
            $customer_name = $customer ? $customer->display_name : '';
            $customer_mobile = get_post_meta($inquiry_id, 'mobile_number', true);
        } else {
            $customer_name = 'رزرو شده';
            $customer_mobile = '---';
        }
        $product_id = get_post_meta($inquiry_id, 'product_id', true);
        $product_name = get_the_title($product_id);
    }
    
    $day = date('Y-m-d', strtotime($start));
    $by_day[$day][] = [
        'time' => date('H:i', strtotime($start)),
        'customer' => $customer_name,
        'mobile' => $customer_mobile,
        'product' => $product_name,
        'inquiry_id' => $inquiry_id,
        'inquiry_type' => $inquiry_type,
        'meeting_id' => $m->ID,
        'can_view_details' => $show_customer_info  // برای استفاده در نمایش
    ];
    
    $total_meetings++;
    if ($day === $today) $today_meetings++;
    if (strtotime($day) <= strtotime('+7 days')) $week_meetings++;
}
?>

<div class="row">
    <div class="col-xl-12">
        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-xl-4">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-primary-transparent">
                                    <i class="la la-calendar fs-24"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="mb-1">
                                    <span class="text-muted fs-13">جلسات امروز</span>
                                </div>
                                <h4 class="fw-semibold mb-0"><?php echo number_format_i18n($today_meetings); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-info-transparent">
                                    <i class="la la-calendar-week fs-24"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="mb-1">
                                    <span class="text-muted fs-13">این هفته</span>
                                </div>
                                <h4 class="fw-semibold mb-0"><?php echo number_format_i18n($week_meetings); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-success-transparent">
                                    <i class="la la-calendar-check fs-24"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="mb-1">
                                    <span class="text-muted fs-13">مجموع جلسات</span>
                                </div>
                                <h4 class="fw-semibold mb-0"><?php echo number_format_i18n($total_meetings); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calendar Card -->
        <div class="card custom-card">
            <div class="card-header bg-primary-transparent">
                <div class="card-title">
                    <i class="la la-calendar-alt me-2"></i>
                    تقویم جلسات حضوری
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($by_day)): ?>
                    <!-- Empty State -->
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="la la-calendar-times" style="font-size: 80px; color: #dee2e6;"></i>
                        </div>
                        <h5 class="text-muted">هیچ جلسه‌ای برنامه‌ریزی نشده است</h5>
                        <p class="text-muted">جلسات آینده در اینجا نمایش داده خواهند شد.</p>
                    </div>
                <?php else: ?>
                    <!-- Calendar Timeline -->
                    <div class="timeline-container">
                        <?php foreach ($by_day as $day => $items): 
                            // Convert to Jalali
                            $timestamp = strtotime($day);
                            if (function_exists('maneli_gregorian_to_jalali')) {
                                $jalali_date = maneli_gregorian_to_jalali(
                                    date('Y', $timestamp),
                                    date('m', $timestamp),
                                    date('d', $timestamp),
                                    'Y/m/d'
                                );
                            } else {
                                $jalali_date = $day;
                            }
                            
                            $day_name = date_i18n('l', $timestamp);
                            $is_today = ($day === $today);
                        ?>
                            <div class="calendar-day-section mb-4">
                                <div class="day-header d-flex align-items-center mb-3 <?php echo $is_today ? 'today' : ''; ?>">
                                    <div class="day-badge">
                                        <div class="day-number"><?php echo date('d', $timestamp); ?></div>
                                        <div class="day-month"><?php echo date_i18n('M', $timestamp); ?></div>
                                    </div>
                                    <div class="day-info me-3">
                                        <h5 class="mb-0 fw-semibold"><?php echo $day_name; ?></h5>
                                        <small class="text-muted"><?php echo $jalali_date; ?></small>
                                    </div>
                                    <?php if ($is_today): ?>
                                        <span class="badge bg-danger-gradient">امروز</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="calendar-events">
                                    <?php foreach ($items as $event): ?>
                                        <div class="calendar-event-card">
                                            <div class="event-time">
                                                <i class="la la-clock"></i>
                                                <?php echo esc_html($event['time']); ?>
                                            </div>
                                            <div class="event-details">
                                                <div class="event-customer">
                                                    <i class="la la-user text-primary me-1"></i>
                                                    <strong><?php echo esc_html($event['customer']); ?></strong>
                                                </div>
                                                <div class="event-info">
                                                    <i class="la la-phone text-success me-1"></i>
                                                    <?php echo esc_html($event['mobile']); ?>
                                                </div>
                                                <div class="event-info">
                                                    <i class="la la-car text-info me-1"></i>
                                                    <?php echo esc_html($event['product']); ?>
                                                </div>
                                            </div>
                                            <div class="event-actions">
                                                <?php if ($event['inquiry_id'] && $event['can_view_details']): ?>
                                                    <a href="<?php echo home_url('/dashboard/inquiries/' . ($event['inquiry_type'] === 'cash' ? 'cash' : 'installment') . '?' . ($event['inquiry_type'] === 'cash' ? 'cash_inquiry_id' : 'inquiry_id') . '=' . $event['inquiry_id']); ?>" 
                                                       class="btn btn-sm btn-primary-light">
                                                        <i class="la la-eye"></i>
                                                        مشاهده
                                                    </a>
                                                <?php elseif ($event['inquiry_id'] && !$event['can_view_details']): ?>
                                                    <span class="badge bg-secondary-transparent">
                                                        <i class="la la-lock me-1"></i>
                                                        رزرو شده
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<!-- End::row -->

<style>
/* ═══════════════════════════════════════════════════════════
   Calendar Page Custom Styles
   ═══════════════════════════════════════════════════════════ */

.timeline-container {
    position: relative;
    padding-right: 30px;
}

.timeline-container::before {
    content: '';
    position: absolute;
    right: 50px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, var(--primary-color) 0%, #dee2e6 100%);
}

.calendar-day-section {
    position: relative;
}

.day-header {
    position: relative;
    padding: 15px 20px;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 12px;
    border: 2px solid #e9ecef;
    margin-bottom: 20px;
}

.day-header.today {
    background: linear-gradient(135deg, rgba(var(--danger-rgb), 0.05) 0%, #fff 100%);
    border-color: var(--danger-color);
}

.day-badge {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
    border-radius: 12px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: white;
    margin-left: 15px;
    box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.3);
}

.day-number {
    font-size: 28px;
    font-weight: 700;
    line-height: 1;
}

.day-month {
    font-size: 11px;
    text-transform: uppercase;
    opacity: 0.9;
}

.day-info h5 {
    color: var(--primary-color);
}

.calendar-events {
    padding-right: 80px;
    position: relative;
}

.calendar-event-card {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 20px;
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    margin-bottom: 15px;
    transition: all 0.3s ease;
    position: relative;
}

.calendar-event-card::before {
    content: '';
    position: absolute;
    right: -82px;
    top: 50%;
    transform: translateY(-50%);
    width: 12px;
    height: 12px;
    background: var(--primary-color);
    border: 3px solid white;
    border-radius: 50%;
    box-shadow: 0 0 0 2px var(--primary-color);
    z-index: 1;
}

.calendar-event-card:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    transform: translateX(-5px);
    border-color: var(--primary-color);
}

.event-time {
    font-size: 18px;
    font-weight: 700;
    color: var(--primary-color);
    min-width: 70px;
    text-align: center;
    padding: 10px;
    background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1) 0%, transparent 100%);
    border-radius: 8px;
}

.event-time i {
    display: block;
    font-size: 20px;
    margin-bottom: 5px;
}

.event-details {
    flex: 1;
}

.event-customer {
    font-size: 16px;
    margin-bottom: 8px;
}

.event-info {
    font-size: 13px;
    color: #6c757d;
    margin-bottom: 4px;
}

.event-info i {
    font-size: 14px;
}

.event-actions {
    display: flex;
    gap: 8px;
}

/* Responsive */
@media (max-width: 768px) {
    .timeline-container {
        padding-right: 15px;
    }
    
    .timeline-container::before {
        right: 35px;
    }
    
    .calendar-events {
        padding-right: 50px;
    }
    
    .calendar-event-card {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .event-time {
        min-width: 100%;
    }
    
    .day-badge {
        width: 60px;
        height: 60px;
    }
    
    .day-number {
        font-size: 24px;
    }
}

/* Empty State */
.la-calendar-times {
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

/* Badge Gradient */
.badge.bg-danger-gradient {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
    animation: pulse-badge 2s infinite;
}

@keyframes pulse-badge {
    0%, 100% {
        box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
    }
    50% {
        box-shadow: 0 4px 16px rgba(220, 53, 69, 0.5);
    }
}
</style>
