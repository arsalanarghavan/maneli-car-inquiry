<!-- Start::row -->
<?php
/**
 * Calendar Page - Meeting Management
 * Accessible by: Admin, Expert
 * Three tabs: Daily (list), Weekly (calendar), Monthly (calendar)
 */

// Check permission
$current_user = wp_get_current_user();
$is_admin = current_user_can('manage_maneli_inquiries');
$is_expert = in_array('maneli_expert', $current_user->roles, true);

if (!$is_admin && !$is_expert) {
    wp_redirect(home_url('/dashboard'));
    exit;
}

// Get settings
$options = get_option('maneli_inquiry_all_options', []);
$start_hour = $options['meetings_start_hour'] ?? '10:00';
$end_hour = $options['meetings_end_hour'] ?? '20:00';
$slot_minutes = max(5, (int)($options['meetings_slot_minutes'] ?? 30));

// Get all meetings (admin sees all, expert sees all but limited customer info)
$meetings_args = [
    'post_type' => 'maneli_meeting',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'orderby' => 'meta_value',
    'meta_key' => 'meeting_start',
    'order' => 'ASC',
];

$meetings = get_posts($meetings_args);

// Process meetings data
$meetings_data = [];
$today = current_time('Y-m-d');
$today_meetings = 0;
$week_meetings = 0;
$total_meetings = count($meetings);

// Jalali month names
$jalali_months = [
    esc_html__('Farvardin', 'maneli-car-inquiry'),
    esc_html__('Ordibehesht', 'maneli-car-inquiry'),
    esc_html__('Khordad', 'maneli-car-inquiry'),
    esc_html__('Tir', 'maneli-car-inquiry'),
    esc_html__('Mordad', 'maneli-car-inquiry'),
    esc_html__('Shahrivar', 'maneli-car-inquiry'),
    esc_html__('Mehr', 'maneli-car-inquiry'),
    esc_html__('Aban', 'maneli-car-inquiry'),
    esc_html__('Azar', 'maneli-car-inquiry'),
    esc_html__('Dey', 'maneli-car-inquiry'),
    esc_html__('Bahman', 'maneli-car-inquiry'),
    esc_html__('Esfand', 'maneli-car-inquiry')
];
$jalali_days = [
    esc_html__('Saturday', 'maneli-car-inquiry'),
    esc_html__('Sunday', 'maneli-car-inquiry'),
    esc_html__('Monday', 'maneli-car-inquiry'),
    esc_html__('Tuesday', 'maneli-car-inquiry'),
    esc_html__('Wednesday', 'maneli-car-inquiry'),
    esc_html__('Thursday', 'maneli-car-inquiry'),
    esc_html__('Friday', 'maneli-car-inquiry')
];

foreach ($meetings as $m) {
    $start = get_post_meta($m->ID, 'meeting_start', true);
    if (empty($start)) continue;
    
    $inquiry_id = get_post_meta($m->ID, 'meeting_inquiry_id', true);
    $inquiry_type = get_post_meta($m->ID, 'meeting_inquiry_type', true);
    
    // Security: Check if expert can see customer info
    $show_customer_info = true;
    $customer_name = '';
    $customer_mobile = '';
    $product_name = '';
    
    if ($is_expert && !$is_admin && $inquiry_id) {
        $assigned_expert_id = get_post_meta($inquiry_id, 'assigned_expert_id', true);
        $show_customer_info = ($assigned_expert_id == $current_user->ID);
    }
    
    // Get customer info
    if ($inquiry_type === 'cash' && get_post_type($inquiry_id) === 'cash_inquiry') {
        if ($show_customer_info) {
            $customer_name = trim(get_post_meta($inquiry_id, 'cash_first_name', true) . ' ' . get_post_meta($inquiry_id, 'cash_last_name', true));
            $customer_mobile = get_post_meta($inquiry_id, 'mobile_number', true);
        } else {
            $customer_name = esc_html__('Reserved', 'maneli-car-inquiry');
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
            $customer_name = esc_html__('Reserved', 'maneli-car-inquiry');
            $customer_mobile = '---';
        }
        $product_id = get_post_meta($inquiry_id, 'product_id', true);
        $product_name = get_the_title($product_id);
    }
    
    // Parse date
    $start_timestamp = strtotime($start);
    $date_str = date('Y-m-d', $start_timestamp);
    $time_str = date('H:i', $start_timestamp);
    
    // Convert to Jalali
    $year = (int)date('Y', $start_timestamp);
    $month = (int)date('m', $start_timestamp);
    $day = (int)date('d', $start_timestamp);
    
    if (function_exists('maneli_gregorian_to_jalali')) {
        $jalali_date = maneli_gregorian_to_jalali($year, $month, $day, 'Y/m/d');
        $jalali_parts = explode('/', $jalali_date);
        $jalali_year = (int)$jalali_parts[0];
        $jalali_month = (int)$jalali_parts[1];
        $jalali_day = (int)$jalali_parts[2];
        $jalali_month_name = $jalali_months[$jalali_month - 1] ?? '';
        $jalali_day_name = $jalali_days[date('w', $start_timestamp)] ?? '';
    } else {
        $jalali_date = $date_str;
        $jalali_year = $year;
        $jalali_month = $month;
        $jalali_day = $day;
        $jalali_month_name = '';
        $jalali_day_name = '';
    }
    
    // Count statistics
    if ($date_str === $today) $today_meetings++;
    if (strtotime($date_str) <= strtotime('+7 days')) $week_meetings++;
    
    $meetings_data[] = [
        'id' => $m->ID,
        'start' => $start,
        'date' => $date_str,
        'time' => $time_str,
        'timestamp' => $start_timestamp,
        'jalali_date' => $jalali_date,
        'jalali_year' => $jalali_year,
        'jalali_month' => $jalali_month,
        'jalali_day' => $jalali_day,
        'jalali_month_name' => $jalali_month_name,
        'jalali_day_name' => $jalali_day_name,
        'customer_name' => $customer_name,
        'customer_mobile' => $customer_mobile,
        'product_name' => $product_name,
        'inquiry_id' => $inquiry_id,
        'inquiry_type' => $inquiry_type,
        'can_view_details' => $show_customer_info,
    ];
}

// Group by day for daily view
$daily_grouped = [];
foreach ($meetings_data as $meeting) {
    $day_key = $meeting['date'];
    if (!isset($daily_grouped[$day_key])) {
        $daily_grouped[$day_key] = [];
    }
    $daily_grouped[$day_key][] = $meeting;
}
// Sort by time within each day
foreach ($daily_grouped as &$day_meetings) {
    usort($day_meetings, function($a, $b) {
        return strcmp($a['time'], $b['time']);
    });
}

// Prepare FullCalendar events (for weekly and monthly views)
$fc_events = [];
foreach ($meetings_data as $meeting) {
    $fc_events[] = [
        'id' => $meeting['id'],
        'title' => $meeting['customer_name'] . ($meeting['product_name'] ? ' - ' . $meeting['product_name'] : ''),
        'start' => $meeting['start'],
        'allDay' => false,
        'className' => $meeting['can_view_details'] ? 'meeting-visible' : 'meeting-reserved',
        'extendedProps' => [
            'customer_name' => $meeting['customer_name'],
            'customer_mobile' => $meeting['customer_mobile'],
            'product_name' => $meeting['product_name'],
            'time' => $meeting['time'],
            'inquiry_id' => $meeting['inquiry_id'],
            'inquiry_type' => $meeting['inquiry_type'],
            'can_view_details' => $meeting['can_view_details'],
        ],
    ];
}
?>
<div class="main-content app-content">
    <div class="container-fluid">
        
        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between page-header-breadcrumb flex-wrap gap-2 mb-4">
            <div>
                <nav>
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item">
                            <a href="<?php echo home_url('/dashboard'); ?>"><?php esc_html_e('Dashboard', 'maneli-car-inquiry'); ?></a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page"><?php esc_html_e('Meeting Calendar', 'maneli-car-inquiry'); ?></li>
                    </ol>
                </nav>
                <h1 class="page-title fw-medium fs-18 mb-0"><?php esc_html_e('Meeting Calendar', 'maneli-car-inquiry'); ?></h1>
            </div>
        </div>
        <!-- End::page-header -->

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-4 col-md-6">
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
                                    <span class="text-muted fs-13"><?php esc_html_e("Today's Meetings", 'maneli-car-inquiry'); ?></span>
                                </div>
                                <h4 class="fw-semibold mb-0"><?php echo maneli_number_format_persian($today_meetings); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
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
                                    <span class="text-muted fs-13"><?php esc_html_e('This Week', 'maneli-car-inquiry'); ?></span>
                                </div>
                                <h4 class="fw-semibold mb-0"><?php echo maneli_number_format_persian($week_meetings); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
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
                                    <span class="text-muted fs-13"><?php esc_html_e('Total Meetings', 'maneli-car-inquiry'); ?></span>
                                </div>
                                <h4 class="fw-semibold mb-0"><?php echo maneli_number_format_persian($total_meetings); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calendar Tabs -->
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="la la-calendar-alt me-2"></i>
                    <?php esc_html_e('Meeting Calendar', 'maneli-car-inquiry'); ?>
                </div>
            </div>
            <div class="card-body">
                <!-- Tabs Navigation -->
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="monthly-tab" data-bs-toggle="tab" data-bs-target="#monthly" type="button" role="tab" aria-controls="monthly" aria-selected="true">
                            <i class="la la-calendar me-1"></i>
                            <?php esc_html_e('Monthly', 'maneli-car-inquiry'); ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="weekly-tab" data-bs-toggle="tab" data-bs-target="#weekly" type="button" role="tab" aria-controls="weekly" aria-selected="false">
                            <i class="la la-calendar-week me-1"></i>
                            <?php esc_html_e('Weekly', 'maneli-car-inquiry'); ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="daily-tab" data-bs-toggle="tab" data-bs-target="#daily" type="button" role="tab" aria-controls="daily" aria-selected="false">
                            <i class="la la-list me-1"></i>
                            <?php esc_html_e('Daily', 'maneli-car-inquiry'); ?>
                        </button>
                    </li>
                </ul>

                <!-- Tabs Content -->
                <div class="tab-content" id="calendarTabContent">
                    
                    <!-- Daily Tab -->
                    <div class="tab-pane fade" id="daily" role="tabpanel" aria-labelledby="daily-tab" tabindex="0">
                        <?php if (empty($daily_grouped)): ?>
                            <div class="text-center py-5">
                                <div class="mb-4">
                                    <i class="la la-calendar-times" style="font-size: 80px; color: #dee2e6;"></i>
                                </div>
                                <h5 class="text-muted"><?php esc_html_e('No meetings scheduled', 'maneli-car-inquiry'); ?></h5>
                                <p class="text-muted"><?php esc_html_e('Future meetings will be displayed here.', 'maneli-car-inquiry'); ?></p>
                            </div>
                        <?php else: ?>
                            <div class="timeline-container">
                                <?php foreach ($daily_grouped as $day => $items): 
                                    $timestamp = strtotime($day);
                                    $is_today = ($day === $today);
                                    $first_meeting = $items[0];
                                    $jalali_date_display = $first_meeting['jalali_date'];
                                    $day_name_display = $first_meeting['jalali_day_name'];
                                ?>
                                    <div class="calendar-day-section mb-4">
                                        <div class="day-header d-flex align-items-center mb-3 <?php echo $is_today ? 'today' : ''; ?>">
                                            <div class="day-badge">
                                                <div class="day-number"><?php echo maneli_number_format_persian($first_meeting['jalali_day']); ?></div>
                                                <div class="day-month"><?php echo esc_html($first_meeting['jalali_month_name']); ?></div>
                                            </div>
                                            <div class="day-info me-3">
                                                <h5 class="mb-0 fw-semibold"><?php echo esc_html($day_name_display); ?></h5>
                                                <small class="text-muted"><?php echo esc_html($jalali_date_display); ?></small>
                                            </div>
                                            <?php if ($is_today): ?>
                                                <span class="badge bg-danger-gradient"><?php esc_html_e('Today', 'maneli-car-inquiry'); ?></span>
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
                                                            <strong><?php echo esc_html($event['customer_name']); ?></strong>
                                                        </div>
                                                        <?php if ($event['can_view_details']): ?>
                                                            <div class="event-info">
                                                                <i class="la la-phone text-success me-1"></i>
                                                                <?php echo esc_html($event['customer_mobile']); ?>
                                                            </div>
                                                            <div class="event-info">
                                                                <i class="la la-car text-info me-1"></i>
                                                                <?php echo esc_html($event['product_name']); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="event-actions">
                                                        <?php if ($event['inquiry_id'] && $event['can_view_details']): ?>
                                                            <a href="<?php echo home_url('/dashboard/inquiries/' . ($event['inquiry_type'] === 'cash' ? 'cash' : 'installment') . '?' . ($event['inquiry_type'] === 'cash' ? 'cash_inquiry_id' : 'inquiry_id') . '=' . $event['inquiry_id']); ?>" 
                                                               class="btn btn-sm btn-primary-light">
                                                                <i class="la la-eye"></i>
                                                                <?php esc_html_e('View', 'maneli-car-inquiry'); ?>
                                                            </a>
                                                        <?php elseif ($event['inquiry_id'] && !$event['can_view_details']): ?>
                                                            <span class="badge bg-secondary-transparent">
                                                                <i class="la la-lock me-1"></i>
                                                                <?php esc_html_e('Reserved', 'maneli-car-inquiry'); ?>
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

                    <!-- Weekly Tab -->
                    <div class="tab-pane fade" id="weekly" role="tabpanel" aria-labelledby="weekly-tab">
                        <div id="weekly-calendar"></div>
                    </div>

                    <!-- Monthly Tab -->
                    <div class="tab-pane fade show active" id="monthly" role="tabpanel" aria-labelledby="monthly-tab">
                        <div id="monthly-calendar"></div>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>
<!-- End::main-content -->

<!-- FullCalendar CSS and JS will be enqueued via dashboard-handler -->
<script>
(function() {
    'use strict';
    
    // Localized strings
    const calendarTexts = {
        mobile: '<?php echo esc_js(__('Mobile:', 'maneli-car-inquiry')); ?>',
        car: '<?php echo esc_js(__('Car:', 'maneli-car-inquiry')); ?>',
        time: '<?php echo esc_js(__('Time:', 'maneli-car-inquiry')); ?>',
        view_details: '<?php echo esc_js(__('View Details', 'maneli-car-inquiry')); ?>',
        reserved: '<?php echo esc_js(__('Reserved', 'maneli-car-inquiry')); ?>',
        meeting_details: '<?php echo esc_js(__('Meeting Details', 'maneli-car-inquiry')); ?>',
        close: '<?php echo esc_js(__('Close', 'maneli-car-inquiry')); ?>'
    };
    
    // FullCalendar events data
    const calendarEvents = <?php echo json_encode($fc_events, JSON_UNESCAPED_UNICODE); ?>;
    
    console.log('Calendar events:', calendarEvents);
    
    // Helper: Convert English digits to Persian
    function toPersianDigits(str) {
        const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        const englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        return String(str).replace(/[0-9]/g, function(w) {
            return persianDigits[englishDigits.indexOf(w)];
        });
    }
    
    // Helper: Convert Gregorian to Jalali (simplified - for display only)
    function gregorianToJalali(date) {
        // This is a simplified version - for production use a proper library
        // Return date as-is for now, server handles conversion
        return date;
    }
    
    // Wait for FullCalendar to load (v6 uses different global)
    function waitForFullCalendar(callback) {
        // FullCalendar v6 can be accessed via window.FullCalendar or just FullCalendar
        if (typeof FullCalendar !== 'undefined' || (typeof window !== 'undefined' && typeof window.FullCalendar !== 'undefined')) {
            callback();
        } else if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(() => waitForFullCalendar(callback), 100);
            });
        } else {
            setTimeout(() => waitForFullCalendar(callback), 100);
        }
    }
    
    // Initialize Weekly Calendar
    let weeklyCalendarEl = document.getElementById('weekly-calendar');
    let weeklyCalendar = null;
    
    // Wait for DOM and FullCalendar
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            waitForFullCalendar(function() {
                initCalendars();
            });
        });
    } else {
        waitForFullCalendar(function() {
            initCalendars();
        });
    }
    
    function initCalendars() {
        const FC = typeof FullCalendar !== 'undefined' ? FullCalendar : (typeof window !== 'undefined' && window.FullCalendar ? window.FullCalendar : null);
        if (!FC) {
            console.error('FullCalendar not found!');
            return;
        }
        
        if (weeklyCalendarEl) {
            console.log('Initializing weekly calendar');
            weeklyCalendar = new FC.Calendar(weeklyCalendarEl, {
            initialView: 'timeGridWeek',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'timeGridWeek,timeGridDay'
            },
            locale: 'fa',
            direction: 'rtl',
            rtl: true,
            firstDay: 6, // Saturday
            height: 'auto',
            events: calendarEvents,
            eventClick: function(info) {
                const props = info.event.extendedProps;
                let details = `<strong>${props.customer_name}</strong><br>`;
                if (props.can_view_details) {
                    details += `${calendarTexts.mobile} ${props.customer_mobile}<br>`;
                    details += `${calendarTexts.car} ${props.product_name}<br>`;
                    details += `${calendarTexts.time} ${props.time}`;
                    if (props.inquiry_id) {
                        details += `<br><br><a href="${props.inquiry_type === 'cash' ? '/dashboard/inquiries/cash?cash_inquiry_id=' : '/dashboard/inquiries/installment?inquiry_id='}${props.inquiry_id}" class="btn btn-sm btn-primary">${calendarTexts.view_details}</a>`;
                    }
                } else {
                    details += `<span class="text-muted">${calendarTexts.reserved}</span>`;
                }
                
                // Show modal or alert
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: calendarTexts.meeting_details,
                        html: details,
                        icon: 'info',
                        confirmButtonText: calendarTexts.close
                    });
                } else {
                    alert(details);
                }
            },
            eventContent: function(arg) {
                const props = arg.event.extendedProps;
                const time = props.time || '';
                
                // Show customer name if can_view_details, otherwise just time
                if (props.can_view_details && props.customer_name) {
                    return {
                        html: `<div class="fc-event-title">${props.customer_name}</div><div class="fc-event-time">${time}</div>`
                    };
                } else {
                    return {
                        html: `<div class="fc-event-time">${time}</div>`
                    };
                }
            },
            slotMinTime: '<?php echo esc_js($start_hour); ?>',
            slotMaxTime: '<?php echo esc_js($end_hour); ?>',
            slotDuration: '00:<?php echo esc_js(str_pad($slot_minutes, 2, '0', STR_PAD_LEFT)); ?>:00',
            allDaySlot: false,
        });
            console.log('Rendering weekly calendar');
            weeklyCalendar.render();
        }
        
        // Initialize Monthly Calendar
        let monthlyCalendarEl = document.getElementById('monthly-calendar');
        if (monthlyCalendarEl) {
            console.log('Initializing monthly calendar');
            monthlyCalendar = new FC.Calendar(monthlyCalendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            locale: 'fa',
            direction: 'rtl',
            rtl: true,
            firstDay: 6, // Saturday
            height: 'auto',
            events: calendarEvents,
            eventClick: function(info) {
                const props = info.event.extendedProps;
                let details = `<strong>${props.customer_name}</strong><br>`;
                if (props.can_view_details) {
                    details += `${calendarTexts.mobile} ${props.customer_mobile}<br>`;
                    details += `${calendarTexts.car} ${props.product_name}<br>`;
                    details += `${calendarTexts.time} ${props.time}`;
                    if (props.inquiry_id) {
                        details += `<br><br><a href="${props.inquiry_type === 'cash' ? '/dashboard/inquiries/cash?cash_inquiry_id=' : '/dashboard/inquiries/installment?inquiry_id='}${props.inquiry_id}" class="btn btn-sm btn-primary">${calendarTexts.view_details}</a>`;
                    }
                } else {
                    details += `<span class="text-muted">${calendarTexts.reserved}</span>`;
                }
                
                // Show modal or alert
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: calendarTexts.meeting_details,
                        html: details,
                        icon: 'info',
                        confirmButtonText: calendarTexts.close
                    });
                } else {
                    alert(details);
                }
            },
            eventDisplay: 'block',
            dayMaxEvents: 3,
            moreLinkClick: 'popover',
            eventContent: function(arg) {
                const props = arg.event.extendedProps;
                const time = props.time || '';
                
                // Show customer name if can_view_details, otherwise just time
                if (props.can_view_details && props.customer_name) {
                    return {
                        html: `<div class="fc-event-title">${props.customer_name}</div><div class="fc-event-time">${time}</div>`
                    };
                } else {
                    return {
                        html: `<div class="fc-event-time">${time}</div>`
                    };
                }
            },
        });
            console.log('Rendering monthly calendar');
            monthlyCalendar.render();
        }
        
        // Render calendars when tab is shown and ensure calendars render even if no events
        const weeklyTab = document.getElementById('weekly-tab');
        const monthlyTab = document.getElementById('monthly-tab');
        
        // Render monthly calendar immediately since it's the default active tab
        const monthlyTabContent = document.getElementById('monthly');
        if (monthlyCalendar && monthlyTabContent) {
            // Check if tab is active or visible
            const isActive = monthlyTabContent.classList.contains('show') || monthlyTabContent.classList.contains('active');
            if (isActive) {
                setTimeout(() => {
                    console.log('Re-rendering monthly calendar (default tab)');
                    monthlyCalendar.render();
                }, 300);
            }
        }
        
        if (weeklyTab) {
            weeklyTab.addEventListener('shown.bs.tab', function() {
                if (weeklyCalendar) {
                    setTimeout(() => {
                        weeklyCalendar.render();
                    }, 100);
                }
            });
        }
        
        if (monthlyTab) {
            monthlyTab.addEventListener('shown.bs.tab', function() {
                if (monthlyCalendar) {
                    setTimeout(() => {
                        monthlyCalendar.render();
                    }, 100);
                }
            });
        }
    }
    
})();
</script>

<style>
/* Calendar Page Custom Styles */
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

/* FullCalendar Customization */
#weekly-calendar, #monthly-calendar {
    margin-top: 20px;
}

.fc-event.meeting-visible {
    background-color: var(--primary-color) !important;
    border-color: var(--primary-color) !important;
}

.fc-event.meeting-reserved {
    background-color: #6c757d !important;
    border-color: #6c757d !important;
}

.fc-event-title {
    font-weight: 600;
}

.fc-event-time {
    font-size: 11px;
    opacity: 0.9;
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
</style>