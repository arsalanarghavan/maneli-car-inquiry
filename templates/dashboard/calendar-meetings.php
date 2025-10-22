<?php
/**
 * Calendar Meetings Page - Full Calendar with Persian Date Support
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

$meetings = get_posts($meetings_args);

// Prepare meetings data for calendar and table
$calendar_events = [];
$meetings_table_data = [];
$total_meetings = 0;
$today_meetings = 0;
$week_meetings = 0;

foreach ($meetings as $m) {
    $start = get_post_meta($m->ID, 'meeting_start', true);
    $end = get_post_meta($m->ID, 'meeting_end', true);
    $inquiry_id = get_post_meta($m->ID, 'meeting_inquiry_id', true);
    $inquiry_type = get_post_meta($m->ID, 'meeting_inquiry_type', true);
    
    $customer_name = '';
    $product_name = '';
    $customer_mobile = '';
    $show_customer_info = true;
    
    // SECURITY: Check if expert can see customer info
    if ($is_expert && !$is_admin && $inquiry_id) {
        $assigned_expert_id = get_post_meta($inquiry_id, 'assigned_expert_id', true);
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
    $start_time = date('H:i', strtotime($start));
    $end_time = $end ? date('H:i', strtotime($end)) : date('H:i', strtotime($start . ' +1 hour'));
    
    // Calendar events data
    $calendar_events[] = [
        'id' => $m->ID,
        'title' => $customer_name,
        'start' => $start,
        'end' => $end ?: date('Y-m-d H:i:s', strtotime($start . ' +1 hour')),
        'extendedProps' => [
            'customer' => $customer_name,
            'mobile' => $customer_mobile,
            'product' => $product_name,
            'inquiry_id' => $inquiry_id,
            'inquiry_type' => $inquiry_type,
            'can_view_details' => $show_customer_info,
            'time' => $start_time . ' - ' . $end_time
        ]
    ];
    
    // Table data
    $meetings_table_data[] = [
        'id' => $m->ID,
        'customer' => $customer_name,
        'mobile' => $customer_mobile,
        'product' => $product_name,
        'date' => $day,
        'time' => $start_time . ' - ' . $end_time,
        'inquiry_id' => $inquiry_id,
        'inquiry_type' => $inquiry_type,
        'can_view_details' => $show_customer_info,
        'timestamp' => strtotime($start)
    ];
    
    $total_meetings++;
    if ($day === $today) $today_meetings++;
    if (strtotime($day) <= strtotime('+7 days')) $week_meetings++;
}

// Sort table data by timestamp
usort($meetings_table_data, function($a, $b) {
    return $a['timestamp'] - $b['timestamp'];
});
?>

<div class="row">
    <div class="col-xl-12">
        <!-- Statistics Cards -->
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
        <div class="card custom-card mb-4">
            <div class="card-header bg-primary-transparent">
                <div class="card-title">
                    <i class="la la-calendar-alt me-2"></i>
                    تقویم جلسات حضوری
                </div>
                <div class="card-options">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-primary" id="calendar-today">امروز</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="calendar-day">روزانه</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="calendar-week">هفتگی</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="calendar-month">ماهانه</button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div id="meetings-calendar"></div>
            </div>
        </div>

        <!-- Meetings Table -->
        <div class="card custom-card">
            <div class="card-header bg-info-transparent">
                <div class="card-title">
                    <i class="la la-list me-2"></i>
                    لیست جلسات (مرتب شده بر اساس نزدیک‌ترین زمان)
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($meetings_table_data)): ?>
                    <!-- Empty State -->
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="la la-calendar-times" style="font-size: 80px; color: #dee2e6;"></i>
                        </div>
                        <h5 class="text-muted">هیچ جلسه‌ای برنامه‌ریزی نشده است</h5>
                        <p class="text-muted">جلسات آینده در اینجا نمایش داده خواهند شد.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="meetings-table">
                            <thead>
                                <tr>
                                    <th>نام و نام خانوادگی</th>
                                    <th>تاریخ</th>
                                    <th>ساعت جلسه</th>
                                    <th>محصول</th>
                                    <th>شماره تماس</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($meetings_table_data as $meeting): 
                                    // Convert to Jalali
                                    $timestamp = strtotime($meeting['date']);
                                    if (function_exists('maneli_gregorian_to_jalali')) {
                                        $jalali_date = maneli_gregorian_to_jalali(
                                            date('Y', $timestamp),
                                            date('m', $timestamp),
                                            date('d', $timestamp),
                                            'Y/m/d'
                                        );
                                        $day_name = maneli_gregorian_to_jalali(
                                            date('Y', $timestamp),
                                            date('m', $timestamp),
                                            date('d', $timestamp),
                                            'l'
                                        );
                                    } else {
                                        $jalali_date = $meeting['date'];
                                        $day_name = date_i18n('l', $timestamp);
                                    }
                                    
                                    $is_today = ($meeting['date'] === $today);
                                ?>
                                    <tr class="<?php echo $is_today ? 'table-warning' : ''; ?>">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="me-2">
                                                    <span class="avatar avatar-sm bg-primary-transparent">
                                                        <i class="la la-user"></i>
                                                    </span>
                                                </div>
                                                <div>
                                                    <strong><?php echo esc_html($meeting['customer']); ?></strong>
                                                    <?php if ($is_today): ?>
                                                        <span class="badge bg-danger-gradient ms-2">امروز</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <div class="fw-semibold"><?php echo $jalali_date; ?></div>
                                                <small class="text-muted"><?php echo $day_name; ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info-transparent">
                                                <i class="la la-clock me-1"></i>
                                                <?php echo esc_html($meeting['time']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="la la-car text-info me-2"></i>
                                                <span><?php echo esc_html($meeting['product']); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="la la-phone text-success me-2"></i>
                                                <span><?php echo esc_html($meeting['mobile']); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($meeting['inquiry_id'] && $meeting['can_view_details']): ?>
                                                <a href="<?php echo home_url('/dashboard/inquiries/' . ($meeting['inquiry_type'] === 'cash' ? 'cash' : 'installment') . '?' . ($meeting['inquiry_type'] === 'cash' ? 'cash_inquiry_id' : 'inquiry_id') . '=' . $meeting['inquiry_id']); ?>" 
                                                   class="btn btn-sm btn-primary-light">
                                                    <i class="la la-eye"></i>
                                                    مشاهده
                                                </a>
                                            <?php elseif ($meeting['inquiry_id'] && !$meeting['can_view_details']): ?>
                                                <span class="badge bg-secondary-transparent">
                                                    <i class="la la-lock me-1"></i>
                                                    رزرو شده
                                                </span>
                                            <?php endif; ?>
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

<!-- Calendar Data for JavaScript -->
<script>
window.meetingsCalendarData = <?php echo json_encode($calendar_events); ?>;

// Debug information
console.log('Calendar Data:', window.meetingsCalendarData);
console.log('jQuery loaded:', typeof jQuery !== 'undefined');
console.log('FullCalendar loaded:', typeof FullCalendar !== 'undefined');
</script>

<style>
/* ═══════════════════════════════════════════════════════════
   Calendar Meetings Page Custom Styles
   ═══════════════════════════════════════════════════════════ */

/* FullCalendar Persian RTL Support */
.fc {
    direction: rtl;
}

.fc-toolbar {
    direction: rtl;
}

.fc-toolbar-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--primary-color);
}

.fc-button-group {
    direction: ltr;
}

.fc-button {
    background: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
    font-weight: 500;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    transition: all 0.3s ease;
}

.fc-button:hover {
    background: var(--primary-hover);
    border-color: var(--primary-hover);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(var(--primary-rgb), 0.3);
}

.fc-button:focus {
    box-shadow: 0 0 0 0.2rem rgba(var(--primary-rgb), 0.25);
}

.fc-button-active {
    background: var(--primary-hover);
    border-color: var(--primary-hover);
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
}

.fc-daygrid-day-number {
    font-weight: 600;
    color: var(--primary-color);
}

.fc-day-today {
    background-color: rgba(var(--primary-rgb), 0.05) !important;
}

.fc-day-today .fc-daygrid-day-number {
    background: var(--primary-color);
    color: white;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 2px;
}

/* Event Styling */
.fc-event {
    border: none;
    border-radius: 6px;
    padding: 2px 6px;
    font-size: 0.85rem;
    font-weight: 500;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.fc-event:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.fc-event-title {
    font-weight: 600;
}

/* Custom Event Colors */
.fc-event.meeting-cash {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}

.fc-event.meeting-installment {
    background: linear-gradient(135deg, #007bff 0%, #6610f2 100%);
    color: white;
}

.fc-event.meeting-reserved {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    color: white;
}

/* Calendar Header Buttons */
#calendar-today,
#calendar-day,
#calendar-week,
#calendar-month {
    margin-left: 5px;
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.3s ease;
}

#calendar-today {
    background: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
}

#calendar-today:hover {
    background: var(--primary-hover);
    border-color: var(--primary-hover);
    transform: translateY(-1px);
}

/* Table Styling */
#meetings-table {
    margin-bottom: 0;
}

#meetings-table th {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 2px solid var(--primary-color);
    font-weight: 600;
    color: var(--primary-color);
    padding: 1rem;
}

#meetings-table td {
    padding: 1rem;
    vertical-align: middle;
    border-bottom: 1px solid #e9ecef;
}

#meetings-table tbody tr:hover {
    background-color: rgba(var(--primary-rgb), 0.05);
    transform: translateX(5px);
    transition: all 0.3s ease;
}

.table-warning {
    background-color: rgba(255, 193, 7, 0.1) !important;
    border-left: 4px solid #ffc107;
}

/* Badge Styling */
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

/* Avatar Styling */
.avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-weight: 600;
}

.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 14px;
}

.avatar-md {
    width: 48px;
    height: 48px;
    font-size: 20px;
}

/* Card Styling */
.custom-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
}

.custom-card:hover {
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
}

.card-header.bg-primary-transparent {
    background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1) 0%, rgba(var(--primary-rgb), 0.05) 100%);
    border-bottom: 1px solid rgba(var(--primary-rgb), 0.2);
}

.card-header.bg-info-transparent {
    background: linear-gradient(135deg, rgba(13, 202, 240, 0.1) 0%, rgba(13, 202, 240, 0.05) 100%);
    border-bottom: 1px solid rgba(13, 202, 240, 0.2);
}

/* Statistics Cards */
.custom-card .card-body {
    padding: 1.5rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .fc-toolbar {
        flex-direction: column;
        gap: 1rem;
    }
    
    .fc-toolbar-chunk {
        display: flex;
        justify-content: center;
    }
    
    .btn-group {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    #meetings-table {
        font-size: 0.9rem;
    }
    
    #meetings-table th,
    #meetings-table td {
        padding: 0.75rem 0.5rem;
    }
}

/* Empty State Animation */
.la-calendar-times {
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

/* Loading Animation */
.fc-loading {
    opacity: 0.6;
    pointer-events: none;
}

/* Custom Scrollbar for Table */
.table-responsive::-webkit-scrollbar {
    height: 8px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: var(--primary-color);
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: var(--primary-hover);
}
</style>
