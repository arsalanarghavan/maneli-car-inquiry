<!-- Start::row -->
<?php
/**
 * Calendar Page - Meeting Management
 * Custom-built calendar without external dependencies
 * Accessible by: Admin, Expert
 * Three tabs: Daily (list), Weekly (calendar), Monthly (calendar)
 */

// Check permission
$current_user = wp_get_current_user();
$is_admin = current_user_can('manage_autopuzzle_inquiries');
$is_expert = in_array('autopuzzle_expert', $current_user->roles, true);

$use_persian_digits = function_exists('autopuzzle_should_use_persian_digits') ? autopuzzle_should_use_persian_digits() : true;
$use_jalali_calendar = $use_persian_digits;

if (!$is_admin && !$is_expert) {
    wp_redirect(home_url('/dashboard'));
    exit;
}

// Get settings
$options = get_option('autopuzzle_inquiry_all_options', []);
$start_hour = $options['meetings_start_hour'] ?? '10:00';
$end_hour = $options['meetings_end_hour'] ?? '20:00';
$slot_minutes = max(5, (int)($options['meetings_slot_minutes'] ?? 30));

// Get all meetings (admin sees all, expert sees all but limited customer info)
$meetings_args = [
    'post_type' => 'autopuzzle_meeting',
    'posts_per_page' => 50, // OPTIMIZED: Limit for memory
    'post_status' => 'publish',
    'orderby' => 'meta_value',
    'meta_key' => 'meeting_start',
    'order' => 'ASC',
];

$meetings = get_posts($meetings_args);

// Get scheduled sessions from cash inquiries
$cash_inquiries = get_posts([
    'post_type' => 'cash_inquiry',
    'posts_per_page' => 50, // OPTIMIZED: Limit for memory
    'post_status' => 'publish',
    'meta_query' => [
        'relation' => 'AND',
        [
            'key' => 'cash_inquiry_status',
            'value' => 'meeting_scheduled',
            'compare' => '='
        ],
        [
            'key' => 'meeting_date',
            'compare' => 'EXISTS'
        ],
        [
            'key' => 'meeting_time',
            'compare' => 'EXISTS'
        ]
    ]
]);

// Get scheduled sessions from installment inquiries
$installment_inquiries = get_posts([
    'post_type' => 'inquiry',
    'posts_per_page' => 50, // OPTIMIZED: Limit for memory
    'post_status' => 'publish',
    'meta_query' => [
        'relation' => 'AND',
        [
            'key' => 'tracking_status',
            'value' => 'meeting_scheduled',
            'compare' => '='
        ],
        [
            'key' => 'meeting_date',
            'compare' => 'EXISTS'
        ],
        [
            'key' => 'meeting_time',
            'compare' => 'EXISTS'
        ]
    ]
]);

// Process meetings data
$meetings_data = [];
$today = current_time('Y-m-d');
$today_meetings = 0;
$week_meetings = 0;

// Jalali month names
if ($use_jalali_calendar) {
$jalali_months = [
    esc_html__('Farvardin', 'autopuzzle'),
    esc_html__('Ordibehesht', 'autopuzzle'),
    esc_html__('Khordad', 'autopuzzle'),
    esc_html__('Tir', 'autopuzzle'),
    esc_html__('Mordad', 'autopuzzle'),
    esc_html__('Shahrivar', 'autopuzzle'),
    esc_html__('Mehr', 'autopuzzle'),
    esc_html__('Aban', 'autopuzzle'),
    esc_html__('Azar', 'autopuzzle'),
    esc_html__('Dey', 'autopuzzle'),
    esc_html__('Bahman', 'autopuzzle'),
    esc_html__('Esfand', 'autopuzzle')
];
$jalali_days = [
    esc_html__('Saturday', 'autopuzzle'),
    esc_html__('Sunday', 'autopuzzle'),
    esc_html__('Monday', 'autopuzzle'),
    esc_html__('Tuesday', 'autopuzzle'),
    esc_html__('Wednesday', 'autopuzzle'),
    esc_html__('Thursday', 'autopuzzle'),
    esc_html__('Friday', 'autopuzzle')
];
} else {
    $jalali_months = [
        esc_html__('January', 'autopuzzle'),
        esc_html__('February', 'autopuzzle'),
        esc_html__('March', 'autopuzzle'),
        esc_html__('April', 'autopuzzle'),
        esc_html__('May', 'autopuzzle'),
        esc_html__('June', 'autopuzzle'),
        esc_html__('July', 'autopuzzle'),
        esc_html__('August', 'autopuzzle'),
        esc_html__('September', 'autopuzzle'),
        esc_html__('October', 'autopuzzle'),
        esc_html__('November', 'autopuzzle'),
        esc_html__('December', 'autopuzzle')
    ];
    $jalali_days = [
        esc_html__('Sunday', 'autopuzzle'),
        esc_html__('Monday', 'autopuzzle'),
        esc_html__('Tuesday', 'autopuzzle'),
        esc_html__('Wednesday', 'autopuzzle'),
        esc_html__('Thursday', 'autopuzzle'),
        esc_html__('Friday', 'autopuzzle'),
        esc_html__('Saturday', 'autopuzzle')
    ];
}

$persian_digit_chars = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
$english_digit_chars = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
$normalize_digits = static function ($value) use ($persian_digit_chars, $english_digit_chars) {
    if (!is_string($value)) {
        $value = (string) $value;
    }
    return str_replace($persian_digit_chars, $english_digit_chars, $value);
};

// Helper function to convert Jalali to Gregorian (based on FullCalendar algorithm)
if (!function_exists('autopuzzle_jalali_to_gregorian')) {
    function autopuzzle_jalali_to_gregorian($j_y, $j_m, $j_d) {
        $j_y = (int)$j_y;
        $j_m = (int)$j_m;
        $j_d = (int)$j_d;
        
        $jy = $j_y - 979;
        $jm = $j_m - 1;
        $jd = $j_d - 1;
        
        // Jalali days in month array
        $j_days_in_month = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];
        
        $j_day_no = 365 * $jy + (int)($jy / 33) * 8 + (int)(($jy % 33 + 3) / 4);
        for ($i = 0; $i < $jm; ++$i) {
            $j_day_no += $j_days_in_month[$i];
        }
        
        $j_day_no += $jd;
        
        $g_day_no = $j_day_no + 79;
        
        // Gregorian days in month array
        $g_days_in_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        
        $gy = 1600 + 400 * (int)($g_day_no / 146097);
        $g_day_no = $g_day_no % 146097;
        
        $leap = true;
        if ($g_day_no >= 36525) {
            $g_day_no--;
            $gy += 100 * (int)($g_day_no / 36524);
            $g_day_no = $g_day_no % 36524;
            
            if ($g_day_no >= 365) {
                $g_day_no++;
            } else {
                $leap = false;
            }
        }
        
        $gy += 4 * (int)($g_day_no / 1461);
        $g_day_no = $g_day_no % 1461;
        
        if ($g_day_no >= 366) {
            $leap = false;
            $g_day_no--;
            $gy += (int)($g_day_no / 365);
            $g_day_no = $g_day_no % 365;
        }
        
        for ($i = 0; $g_day_no >= ($g_days_in_month[$i] + ($i == 1 && $leap ? 1 : 0)); $i++) {
            $g_day_no -= $g_days_in_month[$i] + ($i == 1 && $leap ? 1 : 0);
        }
        $gm = $i + 1;
        $gd = $g_day_no + 1;
        
        return [$gy, $gm, $gd];
    }
}

// Helper function to detect and convert Jalali date string to Gregorian
function convert_date_to_gregorian($date_str) {
    if (empty($date_str)) return null;
    
    // Check if it's already in Gregorian format (YYYY-MM-DD)
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date_str, $matches)) {
        return $date_str; // Already Gregorian
    }
    
    // Check if it's Jalali format (Y/m/d or Y/m/d)
    if (preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $date_str, $matches)) {
        $year = (int)$matches[1];
        $month = (int)$matches[2];
        $day = (int)$matches[3];
        
        // If year is between 1300-1500, it's likely Jalali
        if ($year >= 1300 && $year <= 1500) {
            list($gy, $gm, $gd) = autopuzzle_jalali_to_gregorian($year, $month, $day);
            return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
        }
    }
    
    // Try to parse as-is (might be other format)
    $timestamp = strtotime($date_str);
    if ($timestamp !== false) {
        return date('Y-m-d', $timestamp);
    }
    
    return null;
}

// Helper function to process meeting data
function process_meeting_data($start, $inquiry_id, $inquiry_type, $is_scheduled_session, $jalali_months, $jalali_days, $is_expert, $is_admin, $current_user_id, $today, $use_jalali_calendar, $use_persian_digits, $normalize_digits) {
    if (empty($start)) return null;
    
    // Ensure inquiry_id is an integer
    $inquiry_id = $inquiry_id ? absint($inquiry_id) : 0;
    
    // Check if $start contains a Jalali date (for scheduled sessions)
    // Format might be "1403/05/20 09:00" or "2024-08-11 09:00"
    $parts = explode(' ', $start, 2);
    $date_part = $parts[0];
    $time_part = isset($parts[1]) ? $parts[1] : '';
    
    // Convert date to Gregorian if needed
    $greg_date = convert_date_to_gregorian($date_part);
    if (!$greg_date) {
        return null; // Invalid date
    }
    
    // Combine with time
    $start_gregorian = $greg_date . ($time_part ? ' ' . $time_part : '');
    
    // Security: Check if expert can see customer info
    $show_customer_info = true;
    $customer_name = '';
    $customer_mobile = '';
    $product_name = '';
    
    if ($is_expert && !$is_admin && $inquiry_id) {
        $assigned_expert_id = get_post_meta($inquiry_id, 'assigned_expert_id', true);
        $show_customer_info = ($assigned_expert_id == $current_user_id);
    }
    
    // Get customer info
    if ($inquiry_type === 'cash' && get_post_type($inquiry_id) === 'cash_inquiry') {
        if ($show_customer_info) {
            $customer_name = trim(get_post_meta($inquiry_id, 'cash_first_name', true) . ' ' . get_post_meta($inquiry_id, 'cash_last_name', true));
            $customer_mobile = get_post_meta($inquiry_id, 'mobile_number', true);
        } else {
            $customer_name = esc_html__('Reserved', 'autopuzzle');
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
            $customer_name = esc_html__('Reserved', 'autopuzzle');
            $customer_mobile = '---';
        }
        $product_id = get_post_meta($inquiry_id, 'product_id', true);
        $product_name = get_the_title($product_id);
    }
    
    // Parse date (now guaranteed to be Gregorian)
    $start_timestamp = strtotime($start_gregorian);
    if ($start_timestamp === false) return null;
    
    $date_str = date('Y-m-d', $start_timestamp);
    $time_str = date('H:i', $start_timestamp);
    
    // Convert to Jalali
    $year = (int)date('Y', $start_timestamp);
    $month = (int)date('m', $start_timestamp);
    $day = (int)date('d', $start_timestamp);
    
    if ($use_jalali_calendar && function_exists('autopuzzle_gregorian_to_jalali')) {
        $jalali_date = autopuzzle_gregorian_to_jalali($year, $month, $day, 'Y/m/d', $use_persian_digits);
        $jalali_parts = explode('/', $jalali_date);
        $jalali_parts = array_map($normalize_digits, $jalali_parts);
        $jalali_year = (int)$jalali_parts[0];
        $jalali_month = (int)$jalali_parts[1];
        $jalali_day = (int)$jalali_parts[2];
        $jalali_month_name = $jalali_months[$jalali_month - 1] ?? '';
        $day_of_week = date('w', $start_timestamp);
        $jalali_day_index = ($day_of_week + 1) % 7;
        $jalali_day_name = $jalali_days[$jalali_day_index] ?? '';
    } else {
        $jalali_date = date_i18n('Y-m-d', $start_timestamp);
        $jalali_year = (int)date('Y', $start_timestamp);
        $jalali_month = (int)date('m', $start_timestamp);
        $jalali_day = (int)date('d', $start_timestamp);
        $jalali_month_name = $jalali_months[$jalali_month - 1] ?? date_i18n('F', $start_timestamp);
        $jalali_day_name = $jalali_days[date('w', $start_timestamp)] ?? date_i18n('l', $start_timestamp);
    }
    
    return [
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
        'inquiry_id' => (int) $inquiry_id, // Ensure it's an integer, not a string with Persian digits
        'inquiry_type' => $inquiry_type,
        'can_view_details' => $show_customer_info,
        'is_scheduled_session' => $is_scheduled_session,
    ];
}

// Process regular meetings
foreach ($meetings as $m) {
    $start = get_post_meta($m->ID, 'meeting_start', true);
    $inquiry_id = absint(get_post_meta($m->ID, 'meeting_inquiry_id', true));
    $inquiry_type = get_post_meta($m->ID, 'meeting_inquiry_type', true);
    
    $meeting_data = process_meeting_data($start, $inquiry_id, $inquiry_type, false, $jalali_months, $jalali_days, $is_expert, $is_admin, $current_user->ID, $today, $use_jalali_calendar, $use_persian_digits, $normalize_digits);
    if ($meeting_data) {
        $meeting_data['id'] = $m->ID;
        $meetings_data[] = $meeting_data;
        
        // Count statistics
        if ($meeting_data['date'] === $today) $today_meetings++;
        if (strtotime($meeting_data['date']) >= strtotime($today) && strtotime($meeting_data['date']) <= strtotime('+7 days')) $week_meetings++;
    }
}

// Process scheduled sessions from cash inquiries
foreach ($cash_inquiries as $inquiry) {
    $inquiry_id = absint($inquiry->ID);
    $meeting_date_raw = get_post_meta($inquiry_id, 'meeting_date', true);
    $meeting_time = get_post_meta($inquiry_id, 'meeting_time', true);
    
    if (empty($meeting_date_raw) || empty($meeting_time)) continue;
    
    // Convert Jalali date to Gregorian if needed
    $meeting_date_gregorian = convert_date_to_gregorian($meeting_date_raw);
    if (!$meeting_date_gregorian) continue;
    
    $start = $meeting_date_gregorian . ' ' . $meeting_time;
    $meeting_data = process_meeting_data($start, $inquiry_id, 'cash', true, $jalali_months, $jalali_days, $is_expert, $is_admin, $current_user->ID, $today, $use_jalali_calendar, $use_persian_digits, $normalize_digits);
    if ($meeting_data) {
        $meeting_data['id'] = 'cash_' . $inquiry_id;
        $meetings_data[] = $meeting_data;
        
        // Count statistics
        if ($meeting_data['date'] === $today) $today_meetings++;
        if (strtotime($meeting_data['date']) >= strtotime($today) && strtotime($meeting_data['date']) <= strtotime('+7 days')) $week_meetings++;
    }
}

// Process scheduled sessions from installment inquiries
foreach ($installment_inquiries as $inquiry) {
    $inquiry_id = absint($inquiry->ID);
    $meeting_date_raw = get_post_meta($inquiry_id, 'meeting_date', true);
    $meeting_time = get_post_meta($inquiry_id, 'meeting_time', true);
    
    if (empty($meeting_date_raw) || empty($meeting_time)) continue;
    
    // Convert Jalali date to Gregorian if needed
    $meeting_date_gregorian = convert_date_to_gregorian($meeting_date_raw);
    if (!$meeting_date_gregorian) continue;
    
    $start = $meeting_date_gregorian . ' ' . $meeting_time;
    $meeting_data = process_meeting_data($start, $inquiry_id, 'installment', true, $jalali_months, $jalali_days, $is_expert, $is_admin, $current_user->ID, $today, $use_jalali_calendar, $use_persian_digits, $normalize_digits);
    if ($meeting_data) {
        $meeting_data['id'] = 'installment_' . $inquiry_id;
        $meetings_data[] = $meeting_data;
        
        // Count statistics
        if ($meeting_data['date'] === $today) $today_meetings++;
        if (strtotime($meeting_data['date']) >= strtotime($today) && strtotime($meeting_data['date']) <= strtotime('+7 days')) $week_meetings++;
    }
}

// Sort all meetings by date and time
usort($meetings_data, function($a, $b) {
    if ($a['date'] !== $b['date']) {
        return strcmp($a['date'], $b['date']);
    }
    return strcmp($a['time'], $b['time']);
});

// Group by day for daily view
$daily_grouped = [];
foreach ($meetings_data as $meeting) {
    $day_key = $meeting['date'];
    if (!isset($daily_grouped[$day_key])) {
        $daily_grouped[$day_key] = [];
    }
    $daily_grouped[$day_key][] = $meeting;
}
foreach ($daily_grouped as &$day_meetings) {
    usort($day_meetings, function($a, $b) {
        return strcmp($a['time'], $b['time']);
    });
}

// Group by date for weekly/monthly views
$meetings_by_date = [];
foreach ($meetings_data as $meeting) {
    // Ensure date is in Y-m-d format
    $date_key = $meeting['date'];
    if (!isset($meetings_by_date[$date_key])) {
        $meetings_by_date[$date_key] = [];
    }
    $meetings_by_date[$date_key][] = $meeting;
}

// Create date mapping for calendar (Gregorian to Jalali)
$date_mapping = [];
$current_date = strtotime('-1 year');
$end_date = strtotime('+2 years');
while ($current_date <= $end_date) {
    $greg_date = date('Y-m-d', $current_date);
    $year = (int)date('Y', $current_date);
    $month = (int)date('m', $current_date);
    $day = (int)date('d', $current_date);
    
    if ($use_jalali_calendar && function_exists('autopuzzle_gregorian_to_jalali')) {
        $jalali = autopuzzle_gregorian_to_jalali($year, $month, $day, 'Y/m/d', $use_persian_digits);
        $jalali_parts = explode('/', $jalali);
        $jalali_parts = array_map($normalize_digits, $jalali_parts);
        $jalali_year = (int)$jalali_parts[0];
        $jalali_month = (int)$jalali_parts[1];
        $jalali_day = (int)$jalali_parts[2];
        $day_of_week = date('w', $current_date);
        $jalali_day_index = ($day_of_week + 1) % 7;
        $date_mapping[$greg_date] = [
            'jalali_date' => $jalali,
            'jalali_year' => $jalali_year,
            'jalali_month' => $jalali_month,
            'jalali_day' => $jalali_day,
            'jalali_month_name' => $jalali_months[$jalali_month - 1] ?? '',
            'jalali_day_name' => $jalali_days[$jalali_day_index] ?? '',
        ];
    } else {
        $date_mapping[$greg_date] = [
            'jalali_date' => $greg_date,
            'jalali_year' => $year,
            'jalali_month' => $month,
            'jalali_day' => $day,
            'jalali_month_name' => date_i18n('F', $current_date),
            'jalali_day_name' => $jalali_days[date('w', $current_date)] ?? date_i18n('l', $current_date),
        ];
    }
    $current_date = strtotime('+1 day', $current_date);
}

$total_meetings = count($meetings_data);
?>
<div class="main-content app-content">
    <div class="container-fluid">
        
        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between page-header-breadcrumb flex-wrap gap-2 mb-4">
            <div>
                <nav>
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item">
                            <a href="<?php echo home_url('/dashboard'); ?>"><?php esc_html_e('Dashboard', 'autopuzzle'); ?></a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page"><?php esc_html_e('Meeting Calendar', 'autopuzzle'); ?></li>
                    </ol>
                </nav>
                <h1 class="page-title fw-medium fs-18 mb-0"><?php esc_html_e('Meeting Calendar', 'autopuzzle'); ?></h1>
            </div>
        </div>
        <!-- End::page-header -->

<style>
/* Calendar Statistics Cards - Inline Styles for Immediate Effect */
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

[data-theme-mode=dark] .card.custom-card.crm-card h4 {
    color: rgba(255, 255, 255, 0.9) !important;
}

.card.custom-card.crm-card:hover h4 {
    color: #5e72e4 !important;
}

[data-theme-mode=dark] .card.custom-card.crm-card:hover h4 {
    color: var(--primary-color) !important;
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

        <!-- Statistics Cards -->
        <div class="row mb-4 autopuzzle-mobile-card-scroll">
            <div class="col-12 col-md-4 col-lg-4 mb-3">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-primary border-opacity-10 bg-primary-transparent rounded-pill">
                                <span class="avatar avatar-md avatar-rounded bg-primary svg-white">
                                    <i class="la la-calendar fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e("Today's Meetings", 'autopuzzle'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center"><?php echo function_exists('persian_numbers') ? persian_numbers(number_format_i18n($today_meetings)) : number_format_i18n($today_meetings); ?></h4>
                            <span class="badge bg-primary-transparent rounded-pill fs-11"><?php esc_html_e('Today', 'autopuzzle'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4 col-lg-4 mb-3">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-info border-opacity-10 bg-info-transparent rounded-pill">
                                <span class="avatar avatar-md avatar-rounded bg-info svg-white">
                                    <i class="la la-calendar-week fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('This Week', 'autopuzzle'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center"><?php echo function_exists('persian_numbers') ? persian_numbers(number_format_i18n($week_meetings)) : number_format_i18n($week_meetings); ?></h4>
                            <span class="badge bg-info-transparent rounded-pill fs-11"><?php esc_html_e('Week', 'autopuzzle'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4 col-lg-4 mb-3">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-success border-opacity-10 bg-success-transparent rounded-pill">
                                <span class="avatar avatar-md avatar-rounded bg-success svg-white">
                                    <i class="la la-calendar-check fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Total Meetings', 'autopuzzle'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center"><?php echo function_exists('persian_numbers') ? persian_numbers(number_format_i18n($total_meetings)) : number_format_i18n($total_meetings); ?></h4>
                            <span class="badge bg-success-transparent rounded-pill fs-11"><?php esc_html_e('All', 'autopuzzle'); ?></span>
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
                    <?php esc_html_e('Meeting Calendar', 'autopuzzle'); ?>
                </div>
            </div>
            <div class="card-body">
                <!-- Tabs Navigation -->
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="monthly-tab" data-bs-toggle="tab" data-bs-target="#monthly" type="button" role="tab" aria-controls="monthly" aria-selected="true">
                            <i class="la la-calendar me-1"></i>
                            <?php esc_html_e('Monthly', 'autopuzzle'); ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="weekly-tab" data-bs-toggle="tab" data-bs-target="#weekly" type="button" role="tab" aria-controls="weekly" aria-selected="false">
                            <i class="la la-calendar-week me-1"></i>
                            <?php esc_html_e('Weekly', 'autopuzzle'); ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="daily-tab" data-bs-toggle="tab" data-bs-target="#daily" type="button" role="tab" aria-controls="daily" aria-selected="false">
                            <i class="la la-list me-1"></i>
                            <?php esc_html_e('Daily', 'autopuzzle'); ?>
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
                                <h5 class="text-muted"><?php esc_html_e('No meetings scheduled', 'autopuzzle'); ?></h5>
                                <p class="text-muted"><?php esc_html_e('Future meetings will be displayed here.', 'autopuzzle'); ?></p>
                            </div>
                        <?php else: ?>
                            <div class="timeline-container">
                                <?php foreach ($daily_grouped as $day => $items): 
                                    $timestamp = strtotime($day);
                                    $is_today = ($day === $today);
                                    $first_meeting = $items[0];
                                    // Convert Jalali date to Persian numbers (format: Y/m/d)
                                    $jalali_date_parts = explode('/', $first_meeting['jalali_date']);
                                    $jalali_date_display = '';
                                    if (count($jalali_date_parts) === 3) {
                                        $conv = function_exists('persian_numbers_no_separator') ? 'persian_numbers_no_separator' : 'autopuzzle_number_format_persian';
                                        $jalali_date_display = $conv($jalali_date_parts[0]) . '/' . 
                                                              $conv($jalali_date_parts[1]) . '/' . 
                                                              $conv($jalali_date_parts[2]);
                                    } else {
                                    $jalali_date_display = $first_meeting['jalali_date'];
                                    }
                                    $day_name_display = $first_meeting['jalali_day_name'];
                                ?>
                                    <div class="calendar-day-section mb-4">
                                        <div class="day-header d-flex align-items-center mb-3 <?php echo $is_today ? 'today' : ''; ?>">
                                            <div class="day-badge">
                                                <div class="day-number"><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($first_meeting['jalali_day']) : autopuzzle_number_format_persian($first_meeting['jalali_day']); ?></div>
                                                <div class="day-month"><?php echo esc_html($first_meeting['jalali_month_name']); ?></div>
                                            </div>
                                            <div class="day-info me-3">
                                                <h5 class="mb-0 fw-semibold"><?php echo esc_html($day_name_display); ?></h5>
                                                <small class="day-date-display"><?php echo esc_html($jalali_date_display); ?></small>
                                            </div>
                                            <?php if ($is_today): ?>
                                                <span class="badge bg-danger-gradient"><?php esc_html_e('Today', 'autopuzzle'); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="calendar-events">
                                            <?php foreach ($items as $event): ?>
                                                <div class="calendar-event-card">
                                                    <div class="event-time">
                                                        <i class="la la-clock"></i>
                                                        <?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($event['time']) : autopuzzle_number_format_persian($event['time']); ?>
                                                    </div>
                                                    <div class="event-details">
                                                        <?php if ($event['is_scheduled_session'] ?? false): ?>
                                                            <div class="event-scheduled-badge mb-2">
                                                                <i class="la la-calendar-check text-info me-1"></i>
                                                                <strong><?php esc_html_e('Scheduled Session:', 'autopuzzle'); ?></strong>
                                                                <span><?php 
                                                                    $jalali_parts = explode('/', $event['jalali_date']);
                                                                    if (count($jalali_parts) === 3) {
                                                                        $conv = function_exists('persian_numbers_no_separator') ? 'persian_numbers_no_separator' : 'autopuzzle_number_format_persian';
                                                                        echo $conv($jalali_parts[0]) . '/' . 
                                                                             $conv($jalali_parts[1]) . '/' . 
                                                                             $conv($jalali_parts[2]);
                                                                    } else {
                                                                        echo esc_html($event['jalali_date']);
                                                                    }
                                                                ?> - <?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($event['time']) : autopuzzle_number_format_persian($event['time']); ?></span>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div class="event-customer">
                                                            <i class="la la-user text-primary me-1"></i>
                                                            <strong><?php echo esc_html($event['customer_name']); ?></strong>
                                                        </div>
                                                        <?php if ($event['can_view_details']): ?>
                                                            <div class="event-info">
                                                                <i class="la la-phone text-success me-1"></i>
                                                                <?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($event['customer_mobile']) : autopuzzle_number_format_persian($event['customer_mobile']); ?>
                                                            </div>
                                                            <div class="event-info">
                                                                <i class="la la-car text-info me-1"></i>
                                                                <?php echo esc_html($event['product_name']); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="event-actions">
                                                        <?php if ($event['inquiry_id'] && $event['can_view_details']): ?>
                                                            <a href="<?php echo home_url('/dashboard/inquiries/' . ($event['inquiry_type'] === 'cash' ? 'cash' : 'installment') . '?' . ($event['inquiry_type'] === 'cash' ? 'cash_inquiry_id' : 'inquiry_id') . '=' . (int) $event['inquiry_id']); ?>" 
                                                               class="btn btn-sm btn-primary-light">
                                                                <i class="la la-eye"></i>
                                                                <?php esc_html_e('View', 'autopuzzle'); ?>
                                                            </a>
                                                        <?php elseif ($event['inquiry_id'] && !$event['can_view_details']): ?>
                                                            <span class="badge bg-secondary-transparent">
                                                                <i class="la la-lock me-1"></i>
                                                                <?php esc_html_e('Reserved', 'autopuzzle'); ?>
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
                        <div class="custom-calendar-wrapper">
                            <div class="calendar-header mb-3 d-flex justify-content-between align-items-center">
                                <div class="calendar-nav">
                                    <button class="btn btn-sm btn-primary-light me-2" id="prev-week">
                                        <i class="la la-arrow-right"></i>
                                    </button>
                                    <button class="btn btn-sm btn-primary-light" id="next-week">
                                        <i class="la la-arrow-left"></i>
                                    </button>
                                </div>
                                <h5 class="mb-0 fw-semibold" id="week-title"></h5>
                                <button class="btn btn-sm btn-primary-light" id="today-week">
                                    <?php esc_html_e('Today', 'autopuzzle'); ?>
                                </button>
                            </div>
                            <div id="weekly-calendar-container" class="weekly-calendar-container"></div>
                        </div>
                    </div>

                    <!-- Monthly Tab -->
                    <div class="tab-pane fade show active" id="monthly" role="tabpanel" aria-labelledby="monthly-tab">
                        <div class="custom-calendar-wrapper">
                            <div class="calendar-header mb-3 d-flex justify-content-between align-items-center">
                                <div class="calendar-nav">
                                    <button class="btn btn-sm btn-primary-light me-2" id="prev-month">
                                        <i class="la la-arrow-right"></i>
                                    </button>
                                    <button class="btn btn-sm btn-primary-light" id="next-month">
                                        <i class="la la-arrow-left"></i>
                                    </button>
                                </div>
                                <h5 class="mb-0 fw-semibold" id="month-title"></h5>
                                <button class="btn btn-sm btn-primary-light" id="today-month">
                                    <?php esc_html_e('Today', 'autopuzzle'); ?>
                                </button>
                            </div>
                            <div id="monthly-calendar-container" class="monthly-calendar-container"></div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>
<!-- End::main-content -->

<script>
(function() {
    'use strict';
    
    // Calendar data from PHP
    // Ensure inquiry_id values are numbers (not strings with Persian digits)
    const meetingsData = <?php echo json_encode($meetings_data, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK); ?>;
    const meetingsByDate = <?php echo json_encode($meetings_by_date, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK); ?>;
    const dateMapping = <?php echo json_encode($date_mapping, JSON_UNESCAPED_UNICODE); ?>;
    const jalaliMonths = <?php echo json_encode($jalali_months, JSON_UNESCAPED_UNICODE); ?>;
    const jalaliDays = <?php echo json_encode($jalali_days, JSON_UNESCAPED_UNICODE); ?>;
    const startHour = '<?php echo esc_js($start_hour); ?>';
    const endHour = '<?php echo esc_js($end_hour); ?>';
    const todayGreg = '<?php echo esc_js($today); ?>';
    const shouldUsePersianDigits = <?php echo $use_persian_digits ? 'true' : 'false'; ?>;
    const useJalaliCalendar = <?php echo $use_jalali_calendar ? 'true' : 'false'; ?>;
    
    // Calendar texts
    const texts = {
        scheduledSession: '<?php echo esc_js(__('Scheduled Session:', 'autopuzzle')); ?>',
        mobile: '<?php echo esc_js(__('Mobile:', 'autopuzzle')); ?>',
        car: '<?php echo esc_js(__('Car:', 'autopuzzle')); ?>',
        time: '<?php echo esc_js(__('Time:', 'autopuzzle')); ?>',
        viewDetails: '<?php echo esc_js(__('View Details', 'autopuzzle')); ?>',
        reserved: '<?php echo esc_js(__('Reserved', 'autopuzzle')); ?>',
        meetingDetails: '<?php echo esc_js(__('Meeting Details', 'autopuzzle')); ?>',
        close: '<?php echo esc_js(__('Close', 'autopuzzle')); ?>',
        noMeetings: '<?php echo esc_js(__('No meetings', 'autopuzzle')); ?>'
    };
    
    // Helper: Convert to Persian digits
    function toPersian(str) {
        if (!shouldUsePersianDigits) {
            return String(str);
        }
        const persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        return String(str).replace(/[0-9]/g, (w) => persian[parseInt(w, 10)]);
    }
    
    // Helper: Convert Persian digits to English (for URLs)
    function toEnglish(str) {
        const persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        const english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        let result = String(str);
        for (let i = 0; i < 10; i++) {
            result = result.split(persian[i]).join(english[i]);
        }
        return result;
    }
    
    // Helper: Format time
    function formatTime(timeStr) {
        return timeStr.substring(0, 5);
    }
    
    // Helper: Get meetings for a date
    function getMeetingsForDate(dateStr) {
        return meetingsByDate[dateStr] || [];
    }
    
    // Debug: Log meetings data (after functions are defined)
    console.log('Total meetings:', meetingsData.length);
    console.log('Meetings by date:', Object.keys(meetingsByDate).length, 'dates');
    console.log('Today Gregorian:', todayGreg);
    console.log('Meetings today:', getMeetingsForDate(todayGreg));
    console.log('Sample meetings:', meetingsData.slice(0, 3));
    
    // Helper: Show meeting modal
    function showMeetingModal(meeting) {
        let html = '';
        
        if (meeting.is_scheduled_session) {
            html += `<div class="alert alert-info mb-3">
                <i class="la la-calendar-check me-2"></i>
                <strong>${texts.scheduledSession}</strong> ${toPersian(meeting.jalali_date)} - ${toPersian(meeting.time)}
            </div>`;
        }
        
        html += `<div class="meeting-modal-content">
            <div class="mb-3">
                <strong class="d-block mb-2">${meeting.customer_name}</strong>
                ${meeting.can_view_details ? `
                    <div class="text-muted small mb-1"><i class="la la-phone me-1"></i>${texts.mobile} ${toPersian(meeting.customer_mobile)}</div>
                    <div class="text-muted small mb-1"><i class="la la-car me-1"></i>${texts.car} ${meeting.product_name}</div>
                    <div class="text-muted small"><i class="la la-clock me-1"></i>${texts.time} ${toPersian(meeting.time)}</div>
                ` : `<div class="text-muted">${texts.reserved}</div>`}
            </div>
            ${meeting.inquiry_id && meeting.can_view_details ? `
                <a href="/dashboard/inquiries/${meeting.inquiry_type === 'cash' ? 'cash?cash_inquiry_id=' : 'installment?inquiry_id='}${Number(meeting.inquiry_id)}" 
                   class="btn btn-sm btn-primary w-100">
                    ${texts.viewDetails}
                </a>
            ` : ''}
        </div>`;
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: texts.meetingDetails,
                html: html,
                icon: 'info',
                confirmButtonText: texts.close,
                width: '400px'
            });
        } else {
            alert(html.replace(/<[^>]*>/g, ''));
        }
    }
    
    // Helper: Get Jalali date info from Gregorian date string
    function getJalaliInfo(dateStr) {
        return dateMapping[dateStr] || null;
    }
    
    // Helper: Format date string YYYY-MM-DD
    function formatDateStr(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
    // Weekly Calendar
    let currentWeekStart = new Date();
    const dayOfWeek = currentWeekStart.getDay();
    if (useJalaliCalendar) {
    if (dayOfWeek === 6) {
        currentWeekStart.setDate(currentWeekStart.getDate());
    } else if (dayOfWeek === 0) {
        currentWeekStart.setDate(currentWeekStart.getDate() - 1);
                } else {
        currentWeekStart.setDate(currentWeekStart.getDate() - (dayOfWeek + 1));
    }
    } else {
        currentWeekStart.setDate(currentWeekStart.getDate() - dayOfWeek);
    }
    currentWeekStart.setHours(0, 0, 0, 0);
    
    function renderWeeklyCalendar() {
        const container = document.getElementById('weekly-calendar-container');
        if (!container) return;
        
        const weekDays = [];
        for (let i = 0; i < 7; i++) {
            const date = new Date(currentWeekStart);
            date.setDate(date.getDate() + i);
            weekDays.push(date);
        }
        
        // Update title with Jalali dates
        const firstDateStr = formatDateStr(weekDays[0]);
        const lastDateStr = formatDateStr(weekDays[6]);
        const firstJalali = getJalaliInfo(firstDateStr);
        const lastJalali = getJalaliInfo(lastDateStr);
            const titleEl = document.getElementById('week-title');
        if (titleEl && firstJalali && lastJalali) {
            titleEl.innerHTML = `${toPersian(firstJalali.jalali_date)} - ${toPersian(lastJalali.jalali_date)}`;
        }
        
        // Generate time slots
        const timeSlots = [];
        const [startH, startM] = startHour.split(':').map(Number);
        const [endH, endM] = endHour.split(':').map(Number);
        
        let currentHour = startH;
        let currentMin = startM;
        while (currentHour < endH || (currentHour === endH && currentMin < endM)) {
            timeSlots.push({
                hour: currentHour,
                minute: currentMin,
                display: String(currentHour).padStart(2, '0') + ':' + String(currentMin).padStart(2, '0')
            });
            currentMin += 30;
            if (currentMin >= 60) {
                currentMin = 0;
                currentHour++;
            }
        }
        
        let html = '<div class="weekly-calendar-grid">';
        html += '<div class="weekly-time-column">';
        html += '<div class="weekly-time-header"></div>';
        timeSlots.forEach(slot => {
            html += `<div class="weekly-time-slot">${toPersian(slot.display)}</div>`;
        });
        html += '</div>';
        
        weekDays.forEach((day) => {
            const dateStr = formatDateStr(day);
            const jalaliInfo = getJalaliInfo(dateStr);
            const meetings = getMeetingsForDate(dateStr);
            const isToday = dateStr === todayGreg;
            
            if (!jalaliInfo) return;
            
            // Debug log for today
            if (isToday) {
                console.log('Weekly - Today:', dateStr, 'Meetings:', meetings.length, meetings);
                meetings.forEach(m => {
                    console.log('  - Meeting:', m.time, m.customer_name);
                });
            }
            
            html += `<div class="weekly-day-column ${isToday ? 'today-column' : ''}">`;
            html += `<div class="weekly-day-header">
                <div class="day-name">${jalaliInfo.jalali_day_name}</div>
                <div class="day-number ${isToday ? 'today' : ''}">${toPersian(jalaliInfo.jalali_day)}</div>
                <div class="day-month-small">${jalaliInfo.jalali_month_name}</div>
            </div>`;
            
            timeSlots.forEach(slot => {
                const slotTime = slot.display;
                // Match meetings that fall within this time slot
                const slotMeetings = meetings.filter(m => {
                    if (!m.time) return false;
                    const timeParts = m.time.split(':');
                    if (timeParts.length < 2) return false;
                    const meetingHour = parseInt(timeParts[0], 10);
                    const meetingMin = parseInt(timeParts[1], 10);
                    // Exact match with time slot
                    return meetingHour === slot.hour && meetingMin === slot.minute;
                });
                
                html += `<div class="weekly-time-cell" data-date="${dateStr}" data-time="${slotTime}">`;
                
                slotMeetings.forEach(meeting => {
                    const bgClass = meeting.is_scheduled_session 
                        ? (meeting.can_view_details ? 'meeting-scheduled' : 'meeting-scheduled-reserved')
                        : (meeting.can_view_details ? 'meeting-normal' : 'meeting-reserved');
                    
                            const meetingTime = toPersian(meeting.time || '');
                    html += `<div class="weekly-meeting-item ${bgClass}" 
                        data-meeting='${JSON.stringify(meeting)}'
                        title="${meeting.customer_name} - ${meeting.time}">
                        <div class="meeting-time-small">${meetingTime}</div>
                        <div class="meeting-name-small">${meeting.customer_name}</div>
                    </div>`;
                });
                
                html += '</div>';
            });
            
            html += '</div>';
        });
        
        html += '</div>';
        container.innerHTML = html;
        
        // Add click handlers
        container.querySelectorAll('.weekly-meeting-item').forEach(item => {
            item.addEventListener('click', function() {
                const meetingData = JSON.parse(this.dataset.meeting);
                showMeetingModal(meetingData);
            });
        });
    }
    
    // Monthly Calendar
    let currentMonth = new Date();
    
    function renderMonthlyCalendar() {
        const container = document.getElementById('monthly-calendar-container');
        if (!container) return;
        
        const year = currentMonth.getFullYear();
        const month = currentMonth.getMonth();
        
        // Get a reference date to determine Jalali month
        const referenceDate = new Date(year, month, 15); // Use middle of month for accuracy
        const referenceDateStr = formatDateStr(referenceDate);
        const referenceJalali = getJalaliInfo(referenceDateStr);
        
        if (!referenceJalali) return;
        
        // Update title with Jalali month
        const titleEl = document.getElementById('month-title');
        if (titleEl) {
            titleEl.innerHTML = `${referenceJalali.jalali_month_name} ${toPersian(referenceJalali.jalali_year)}`;
        }
        
        // Find all dates that belong to this Jalali month
        const jalaliYear = referenceJalali.jalali_year;
        const jalaliMonth = referenceJalali.jalali_month;
        const monthDays = [];
        
        // Search through dateMapping to find all days in this Jalali month
        for (const [gregDate, jalaliInfo] of Object.entries(dateMapping)) {
            if (jalaliInfo.jalali_year === jalaliYear && jalaliInfo.jalali_month === jalaliMonth) {
                monthDays.push({
                    gregDate: gregDate,
                    jalaliDay: jalaliInfo.jalali_day,
                    jalaliInfo: jalaliInfo,
                    date: new Date(gregDate + 'T00:00:00')
                });
            }
        }
        
        // Sort by Jalali day
        monthDays.sort((a, b) => a.jalaliDay - b.jalaliDay);
        
        if (monthDays.length === 0) return;
        
        // Get first day of Jalali month
        const firstDay = monthDays[0].date;
        
        // Determine starting day based on active calendar
        let startDay = useJalaliCalendar ? (firstDay.getDay() + 1) % 7 : firstDay.getDay();
        
        let html = '<div class="monthly-calendar-grid">';
        
        // Week day headers (Saturday to Friday)
        for (let i = 0; i < 7; i++) {
            html += '<div class="monthly-weekday-header">' + jalaliDays[i] + '</div>';
        }
        
        // Empty cells for days before month starts
        for (let i = 0; i < startDay; i++) {
            html += '<div class="monthly-day-cell empty"></div>';
        }
        
        // Days of the Jalali month
        monthDays.forEach(dayData => {
            const dateStr = dayData.gregDate;
            const jalaliInfo = dayData.jalaliInfo;
            const meetings = getMeetingsForDate(dateStr);
            const isToday = dateStr === todayGreg;
            
            html += `<div class="monthly-day-cell ${isToday ? 'today' : ''}" data-date="${dateStr}">`;
            html += `<div class="monthly-day-number">${toPersian(jalaliInfo.jalali_day)}</div>`;
            
            if (meetings.length > 0) {
                html += '<div class="monthly-meetings">';
                meetings.slice(0, 3).forEach(meeting => {
                    const bgClass = meeting.is_scheduled_session 
                        ? (meeting.can_view_details ? 'meeting-dot-scheduled' : 'meeting-dot-scheduled-reserved')
                        : (meeting.can_view_details ? 'meeting-dot' : 'meeting-dot-reserved');
                    
                    html += `<div class="meeting-dot-item ${bgClass}" 
                        data-meeting='${JSON.stringify(meeting)}'
                        title="${meeting.customer_name} - ${meeting.time}"></div>`;
                });
                if (meetings.length > 3) {
                    html += `<div class="meeting-more">+${toPersian(meetings.length - 3)}</div>`;
                }
                html += '</div>';
            }
            
            html += '</div>';
        });
        
        html += '</div>';
        container.innerHTML = html;
        
        // Add click handlers
        container.querySelectorAll('.monthly-day-cell:not(.empty)').forEach(cell => {
            cell.addEventListener('click', function() {
                const dateStr = this.dataset.date;
                const jalaliInfo = getJalaliInfo(dateStr);
                const meetings = getMeetingsForDate(dateStr);
                
                if (meetings.length > 0) {
                    if (meetings.length === 1) {
                        showMeetingModal(meetings[0]);
                    } else {
                        // Show list of meetings
                        let listHtml = '<div class="meeting-list-modal">';
                        meetings.forEach(meeting => {
                            listHtml += `<div class="meeting-list-item" data-meeting='${JSON.stringify(meeting)}'>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>${meeting.customer_name}</strong>
                                        <div class="text-muted small">${toPersian(meeting.jalali_date)} - ${toPersian(meeting.time)}</div>
                                    </div>
                                    ${meeting.inquiry_id && meeting.can_view_details ? `
                                        <a href="/dashboard/inquiries/${meeting.inquiry_type === 'cash' ? 'cash?cash_inquiry_id=' : 'installment?inquiry_id='}${Number(meeting.inquiry_id)}" 
                                           class="btn btn-sm btn-primary-light">${texts.viewDetails}</a>
                                    ` : `<button class="btn btn-sm btn-primary-light" disabled>${texts.viewDetails}</button>`}
                                </div>
                            </div>`;
                        });
                        listHtml += '</div>';
                        
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: jalaliInfo ? toPersian(jalaliInfo.jalali_date) : dateStr,
                                html: listHtml,
                                icon: 'info',
                                confirmButtonText: texts.close,
                                width: '500px'
                            });
                            
                            // Add click handlers to list items
                    setTimeout(() => {
                                document.querySelectorAll('.meeting-list-item').forEach(item => {
                                    item.addEventListener('click', function() {
                                        const meeting = JSON.parse(this.dataset.meeting);
                                        showMeetingModal(meeting);
                                    });
                                });
                    }, 100);
                        }
                    }
                }
            });
        });
        
        container.querySelectorAll('.meeting-dot-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.stopPropagation();
                const meeting = JSON.parse(this.dataset.meeting);
                showMeetingModal(meeting);
            });
        });
    }
    
    // Navigation handlers
    document.getElementById('prev-week')?.addEventListener('click', function() {
        currentWeekStart.setDate(currentWeekStart.getDate() - 7);
        renderWeeklyCalendar();
    });
    
    document.getElementById('next-week')?.addEventListener('click', function() {
        currentWeekStart.setDate(currentWeekStart.getDate() + 7);
        renderWeeklyCalendar();
    });
    
    document.getElementById('today-week')?.addEventListener('click', function() {
        currentWeekStart = new Date();
        // Find Saturday of current week
        const dayOfWeek = currentWeekStart.getDay();
        if (dayOfWeek === 6) {
            currentWeekStart.setDate(currentWeekStart.getDate());
        } else if (dayOfWeek === 0) {
            currentWeekStart.setDate(currentWeekStart.getDate() - 1);
        } else {
            currentWeekStart.setDate(currentWeekStart.getDate() - (dayOfWeek + 1));
        }
        currentWeekStart.setHours(0, 0, 0, 0);
        renderWeeklyCalendar();
    });
    
    document.getElementById('prev-month')?.addEventListener('click', function() {
        currentMonth.setMonth(currentMonth.getMonth() - 1);
        renderMonthlyCalendar();
    });
    
    document.getElementById('next-month')?.addEventListener('click', function() {
        currentMonth.setMonth(currentMonth.getMonth() + 1);
        renderMonthlyCalendar();
    });
    
    document.getElementById('today-month')?.addEventListener('click', function() {
        currentMonth = new Date();
        renderMonthlyCalendar();
    });
    
    // Initialize on tab switch
    document.getElementById('weekly-tab')?.addEventListener('shown.bs.tab', function() {
        setTimeout(renderWeeklyCalendar, 100);
    });
    
    document.getElementById('monthly-tab')?.addEventListener('shown.bs.tab', function() {
        setTimeout(renderMonthlyCalendar, 100);
    });
    
    // Initial render
    if (document.getElementById('monthly').classList.contains('active') || 
        document.getElementById('monthly').classList.contains('show')) {
        setTimeout(renderMonthlyCalendar, 300);
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
    color: #495057 !important;
}

.day-header.today {
    background: linear-gradient(135deg, rgba(var(--danger-rgb), 0.05) 0%, #fff 100%);
    border-color: var(--danger-color);
}

.day-badge {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, #5e72e4 0%, #4c63d2 100%) !important;
    border-radius: 12px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: white !important;
    margin-left: 15px;
    box-shadow: 0 4px 12px rgba(94, 114, 228, 0.3);
}

/* Force white color for all badge content */
.day-badge .day-number {
    font-size: 28px;
    font-weight: 700;
    line-height: 1;
    color: white !important;
    text-shadow: none !important;
}

.day-badge .day-month {
    font-size: 11px;
    text-transform: uppercase;
    opacity: 1 !important;
    color: white !important;
    text-shadow: none !important;
}

.day-badge * {
    color: white !important;
}

/* General day-number and day-month (outside badge) */
.day-number {
    font-size: 28px;
    font-weight: 700;
    line-height: 1;
}

.day-month {
    font-size: 11px;
    text-transform: uppercase;
    opacity: 0.95;
}

.day-info {
    color: #495057 !important;
}

.day-info * {
    color: inherit;
}

.day-info h5 {
    color: var(--primary-color) !important;
    margin-bottom: 0;
}

.day-info .day-date-display {
    color: #495057 !important;
    font-size: 13px;
    display: block;
    margin-top: 4px;
    font-weight: 500;
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

.event-scheduled-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 12px;
    background: linear-gradient(135deg, rgba(13, 110, 253, 0.1) 0%, rgba(13, 110, 253, 0.05) 100%);
    border: 1px solid rgba(13, 110, 253, 0.2);
    border-radius: 6px;
    color: #0d6efd;
    font-size: 13px;
}

.event-scheduled-badge i {
    font-size: 16px;
}

.event-actions {
    display: flex;
    gap: 8px;
}

/* Weekly Calendar Styles */
.weekly-calendar-container {
    overflow-x: auto;
}

.weekly-calendar-grid {
    display: grid;
    grid-template-columns: 80px repeat(7, 1fr);
    min-width: 1000px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    overflow: hidden;
}

.weekly-time-column {
    background: #f8f9fa;
    border-right: 2px solid #dee2e6;
}

.weekly-time-header {
    height: 60px;
    border-bottom: 2px solid #dee2e6;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    color: var(--primary-color);
}

.weekly-time-slot {
    height: 60px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    color: #6c757d;
}

.weekly-day-column {
    border-right: 1px solid #e9ecef;
    position: relative;
}

.weekly-day-column:last-child {
    border-right: none;
}

.weekly-day-column.today-column {
    background: rgba(var(--primary-rgb), 0.02);
}

.weekly-day-header {
    height: 60px;
    border-bottom: 2px solid #dee2e6;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    font-weight: 600;
}

.weekly-day-header .day-name {
    font-size: 12px;
    color: #6c757d;
    margin-bottom: 4px;
}

.weekly-day-header .day-number {
    font-size: 18px;
    color: var(--primary-color);
}

.weekly-day-header .day-number.today {
    width: 32px;
    height: 32px;
    background: var(--primary-color);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.weekly-day-header .day-month-small {
    font-size: 10px;
    color: #6c757d;
    margin-top: 2px;
}

.weekly-time-cell {
    height: 60px;
    border-bottom: 1px solid #e9ecef;
    position: relative;
    padding: 2px;
    transition: background 0.2s;
}

.weekly-time-cell:hover {
    background: rgba(var(--primary-rgb), 0.05);
}

.weekly-meeting-item {
    padding: 4px 6px;
    border-radius: 4px;
    margin-bottom: 2px;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 11px;
    line-height: 1.3;
    color: white;
}

.weekly-meeting-item:hover {
    transform: scale(1.02);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.meeting-normal {
    background: var(--primary-color);
}

.meeting-reserved {
    background: #6c757d;
}

.meeting-scheduled {
    background: #0d6efd;
}

.meeting-scheduled-reserved {
    background: #5a6268;
}

.meeting-time-small {
    font-weight: 600;
    font-size: 10px;
}

.meeting-name-small {
    font-size: 11px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Monthly Calendar Styles */
.monthly-calendar-container {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.monthly-calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    border: 1px solid #e9ecef;
    border-radius: 8px;
    overflow: hidden;
    min-height: 500px;
    min-width: 700px;
}

.monthly-weekday-header {
    padding: 15px;
    background: var(--primary-color);
    color: white;
    text-align: center;
    font-weight: 600;
    font-size: 14px;
    border-right: 1px solid rgba(255, 255, 255, 0.2);
}

.monthly-weekday-header:last-child {
    border-right: none;
}

.monthly-day-cell {
    min-height: 100px;
    padding: 10px;
    border-right: 1px solid #e9ecef;
    border-bottom: 1px solid #e9ecef;
    background: white;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
}

.monthly-day-cell.empty {
    background: #f8f9fa;
    cursor: default;
}

.monthly-day-cell:hover:not(.empty) {
    background: rgba(var(--primary-rgb), 0.05);
}

.monthly-day-cell.today {
    background: rgba(var(--primary-rgb), 0.08);
}

.monthly-day-cell.today .monthly-day-number {
    background: var(--primary-color);
    color: white;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}

.monthly-day-number {
    font-size: 16px;
    font-weight: 600;
    color: #495057;
    margin-bottom: 8px;
}

.monthly-meetings {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    align-items: center;
}

.meeting-dot-item {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    cursor: pointer;
    transition: transform 0.2s;
}

.meeting-dot-item:hover {
    transform: scale(1.5);
}

.meeting-dot {
    background: var(--primary-color);
}

.meeting-dot-reserved {
    background: #6c757d;
}

.meeting-dot-scheduled {
    background: #0d6efd;
}

.meeting-dot-scheduled-reserved {
    background: #5a6268;
}

.meeting-more {
    font-size: 10px;
    color: #6c757d;
    font-weight: 600;
}

.meeting-list-item {
    padding: 12px;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    margin-bottom: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.meeting-list-item:hover {
    background: rgba(var(--primary-rgb), 0.05);
    border-color: var(--primary-color);
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

/* Calendar Header */
.calendar-header {
    padding: 10px 0;
}

.calendar-nav .btn {
    border-radius: 6px;
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
    
    .weekly-calendar-grid {
        min-width: 800px;
    }
    
    .weekly-time-column {
        width: 60px;
    }
    
    .weekly-time-slot {
        height: 50px;
        font-size: 11px;
    }
    
    .weekly-time-cell {
        height: 50px;
    }
    
    .monthly-day-cell {
        min-height: 80px;
        padding: 8px;
    }
}

/* ============================================
   DARK MODE STYLES FOR CALENDAR
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

[data-theme-mode=dark] .timeline-container::before {
    background: linear-gradient(to bottom, var(--primary-color) 0%, rgba(255, 255, 255, 0.1) 100%);
}

[data-theme-mode=dark] .day-header {
    background: linear-gradient(135deg, rgb(var(--body-bg-rgb)) 0%, rgb(var(--body-bg-rgb2)) 100%) !important;
    border: 2px solid rgba(255, 255, 255, 0.1) !important;
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme-mode=dark] .day-header.today {
    background: linear-gradient(135deg, rgba(var(--danger-rgb), 0.15) 0%, rgb(var(--body-bg-rgb)) 100%) !important;
    border-color: rgb(var(--danger-rgb)) !important;
}

[data-theme-mode=dark] .day-info {
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme-mode=dark] .day-info h5 {
    color: var(--primary-color) !important;
}

[data-theme-mode=dark] .day-info .day-date-display {
    color: rgba(255, 255, 255, 0.6) !important;
}

[data-theme-mode=dark] .calendar-event-card {
    background: rgb(var(--body-bg-rgb)) !important;
    border: 2px solid rgba(255, 255, 255, 0.1) !important;
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme-mode=dark] .calendar-event-card:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4) !important;
    border-color: var(--primary-color) !important;
}

[data-theme-mode=dark] .calendar-event-card::before {
    background: var(--primary-color) !important;
    border: 3px solid rgb(var(--body-bg-rgb)) !important;
    box-shadow: 0 0 0 2px var(--primary-color) !important;
}

[data-theme-mode=dark] .event-time {
    color: var(--primary-color) !important;
    background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.2) 0%, transparent 100%) !important;
}

[data-theme-mode=dark] .event-time i {
    color: var(--primary-color) !important;
}

[data-theme-mode=dark] .event-customer {
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme-mode=dark] .event-customer i {
    color: var(--primary-color) !important;
}

[data-theme-mode=dark] .event-info {
    color: rgba(255, 255, 255, 0.6) !important;
}

[data-theme-mode=dark] .event-info i {
    color: rgb(var(--success-rgb)) !important;
}

[data-theme-mode=dark] .event-info .la-la-car {
    color: rgb(var(--info-rgb)) !important;
}

[data-theme-mode=dark] .event-scheduled-badge {
    background: linear-gradient(135deg, rgba(13, 110, 253, 0.2) 0%, rgba(13, 110, 253, 0.1) 100%) !important;
    border: 1px solid rgba(13, 110, 253, 0.3) !important;
    color: rgb(173, 216, 230) !important;
}

[data-theme-mode=dark] .event-scheduled-badge i {
    color: rgb(var(--info-rgb)) !important;
}

[data-theme-mode=dark] .text-center.py-5 {
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme-mode=dark] .text-center.py-5 h5 {
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme-mode=dark] .text-center.py-5 p {
    color: rgba(255, 255, 255, 0.6) !important;
}

[data-theme-mode=dark] .text-center.py-5 .la-la-calendar-times {
    color: rgba(255, 255, 255, 0.3) !important;
}

/* Weekly Calendar Dark Mode */
[data-theme-mode=dark] .weekly-calendar-grid {
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
}

[data-theme-mode=dark] .weekly-time-column {
    background: rgb(var(--body-bg-rgb)) !important;
    border-right: 2px solid rgba(255, 255, 255, 0.1) !important;
}

[data-theme-mode=dark] .weekly-time-header {
    border-bottom: 2px solid rgba(255, 255, 255, 0.1) !important;
    color: var(--primary-color) !important;
}

[data-theme-mode=dark] .weekly-time-slot {
    border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
    color: rgba(255, 255, 255, 0.6) !important;
}

[data-theme-mode=dark] .weekly-day-column {
    border-right: 1px solid rgba(255, 255, 255, 0.1) !important;
}

[data-theme-mode=dark] .weekly-day-column.today-column {
    background: rgba(var(--primary-rgb), 0.1) !important;
}

[data-theme-mode=dark] .weekly-day-header {
    border-bottom: 2px solid rgba(255, 255, 255, 0.1) !important;
    background: rgb(var(--body-bg-rgb)) !important;
}

[data-theme-mode=dark] .weekly-day-header .day-name {
    color: rgba(255, 255, 255, 0.6) !important;
}

[data-theme-mode=dark] .weekly-day-header .day-number {
    color: var(--primary-color) !important;
}

[data-theme-mode=dark] .weekly-day-header .day-number.today {
    background: var(--primary-color) !important;
    color: white !important;
}

[data-theme-mode=dark] .weekly-day-header .day-month-small {
    color: rgba(255, 255, 255, 0.6) !important;
}

[data-theme-mode=dark] .weekly-time-cell {
    border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
}

[data-theme-mode=dark] .weekly-time-cell:hover {
    background: rgba(var(--primary-rgb), 0.1) !important;
}

[data-theme-mode=dark] .weekly-meeting-item {
    color: white !important;
}

/* Monthly Calendar Dark Mode */
[data-theme-mode=dark] .monthly-calendar-grid {
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
}

[data-theme-mode=dark] .monthly-weekday-header {
    background: var(--primary-color) !important;
    color: white !important;
    border-right: 1px solid rgba(255, 255, 255, 0.2) !important;
}

[data-theme-mode=dark] .monthly-day-cell {
    border-right: 1px solid rgba(255, 255, 255, 0.1) !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
    background: rgb(var(--body-bg-rgb)) !important;
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme-mode=dark] .monthly-day-cell.empty {
    background: rgb(var(--body-bg-rgb2)) !important;
}

[data-theme-mode=dark] .monthly-day-cell:hover:not(.empty) {
    background: rgba(var(--primary-rgb), 0.1) !important;
}

[data-theme-mode=dark] .monthly-day-cell.today {
    background: rgba(var(--primary-rgb), 0.15) !important;
}

[data-theme-mode=dark] .monthly-day-cell.today .monthly-day-number {
    background: var(--primary-color) !important;
    color: white !important;
}

[data-theme-mode=dark] .monthly-day-number {
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme-mode=dark] .meeting-more {
    color: rgba(255, 255, 255, 0.6) !important;
}

[data-theme-mode=dark] .meeting-list-item {
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    background: rgb(var(--body-bg-rgb)) !important;
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme-mode=dark] .meeting-list-item:hover {
    background: rgba(var(--primary-rgb), 0.1) !important;
    border-color: var(--primary-color) !important;
}

[data-theme-mode=dark] .meeting-list-item strong {
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme-mode=dark] .meeting-list-item .text-muted {
    color: rgba(255, 255, 255, 0.6) !important;
}

[data-theme-mode=dark] .calendar-header h5 {
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme-mode=dark] .card.custom-card .card-header {
    border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
}

[data-theme-mode=dark] .card.custom-card .card-header .card-title {
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme-mode=dark] .card.custom-card .card-header .card-title i {
    color: var(--primary-color) !important;
}

[data-theme-mode=dark] .nav-tabs .nav-link {
    color: rgba(255, 255, 255, 0.7) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}

[data-theme-mode=dark] .nav-tabs .nav-link:hover {
    color: rgba(255, 255, 255, 0.9) !important;
    border-color: rgba(255, 255, 255, 0.2) !important;
}

[data-theme-mode=dark] .nav-tabs .nav-link.active {
    color: var(--primary-color) !important;
    border-color: var(--primary-color) var(--primary-color) transparent !important;
    background-color: rgb(var(--body-bg-rgb)) !important;
}

[data-theme-mode=dark] .nav-tabs .nav-link i {
    color: inherit !important;
}

[data-theme-mode=dark] .badge.bg-danger-gradient {
    background: linear-gradient(135deg, rgb(var(--danger-rgb)) 0%, rgba(var(--danger-rgb), 0.8) 100%) !important;
    box-shadow: 0 2px 8px rgba(var(--danger-rgb), 0.4) !important;
}

[data-theme-mode=dark] .badge.bg-danger-gradient {
    animation: pulse-badge-dark 2s infinite;
}

@keyframes pulse-badge-dark {
    0%, 100% {
        box-shadow: 0 2px 8px rgba(var(--danger-rgb), 0.4);
    }
    50% {
        box-shadow: 0 4px 16px rgba(var(--danger-rgb), 0.6);
    }
}

@media (max-width: 991.98px) {
    .weekly-calendar-wrapper {
        grid-template-columns: 120px 1fr;
    }
}

@media (max-width: 767.98px) {
    .monthly-calendar-grid {
        min-width: 600px;
        min-height: 420px;
    }

    .monthly-weekday-header {
        padding: 10px;
        font-size: 12px;
    }

    .monthly-day-cell {
        min-height: 80px;
        padding: 8px;
    }

    .monthly-day-cell .day-number {
        font-size: 14px;
    }

    .monthly-day-cell .meeting-count {
        font-size: 11px;
    }

    .monthly-day-cell .meeting-item {
        font-size: 10px;
    }
}
</style>