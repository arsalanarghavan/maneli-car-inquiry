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

$use_persian_digits_for_dashboard = function_exists('maneli_should_use_persian_digits') ? maneli_should_use_persian_digits() : true;

if (!function_exists('maneli_installment_followup_convert_jalali_to_gregorian')) {
    /**
     * Convert a Jalali date (with possible Persian digits) to Gregorian (Y-m-d).
     *
     * @param string $jalali_date_string
     * @return string|null
     */
    function maneli_installment_followup_convert_jalali_to_gregorian($jalali_date_string) {
        if (!is_string($jalali_date_string) || $jalali_date_string === '') {
            return null;
        }

        $value = $jalali_date_string;
        if (function_exists('maneli_convert_to_english_digits')) {
            $value = maneli_convert_to_english_digits($value);
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $value = str_replace(['-', '.'], '/', $value);

        if (!preg_match('/^(\d{3,4})\/(\d{1,2})\/(\d{1,2})$/', $value, $matches)) {
            return null;
        }

        $jy = (int) $matches[1];
        $jm = (int) $matches[2];
        $jd = (int) $matches[3];

        if ($jy < 1300 || $jy > 1500) {
            return null;
        }

        $jy -= 979;
        $jm -= 1;
        $jd -= 1;

        $j_days_in_month = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];
        $g_days_in_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

        $j_day_no = 365 * $jy + intdiv($jy, 33) * 8 + intdiv(($jy % 33 + 3), 4);

        for ($i = 0; $i < $jm; ++$i) {
            $j_day_no += $j_days_in_month[$i];
        }

        $j_day_no += $jd;

        $g_day_no = $j_day_no + 79;
        $gy = 1600 + 400 * intdiv($g_day_no, 146097);
        $g_day_no %= 146097;

        $leap = true;
        if ($g_day_no >= 36525) {
            $g_day_no--;
            $gy += 100 * intdiv($g_day_no, 36524);
            $g_day_no %= 36524;
            if ($g_day_no >= 365) {
                $g_day_no++;
            } else {
                $leap = false;
            }
        }

        $gy += 4 * intdiv($g_day_no, 1461);
        $g_day_no %= 1461;

        if ($g_day_no >= 366) {
            $leap = false;
            $g_day_no--;
            $gy += intdiv($g_day_no, 365);
            $g_day_no %= 365;
        }

        $gm = 0;
        while (
            $gm < 12 &&
            $g_day_no >= ($g_days_in_month[$gm] + ($gm === 1 && $leap ? 1 : 0))
        ) {
            $g_day_no -= $g_days_in_month[$gm] + ($gm === 1 && $leap ? 1 : 0);
            $gm++;
        }

        $gd = $g_day_no + 1;
        $gm += 1;

        return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
    }
}

$convert_followup_to_gregorian = static function ($date_string) {
    if (empty($date_string)) {
        return null;
    }

    $value = $date_string;
    if (function_exists('maneli_convert_to_english_digits')) {
        $value = maneli_convert_to_english_digits($value);
    }

    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }

    $normalized_slash = str_replace(['-', '.'], '/', $value);
    if (preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $normalized_slash, $matches)) {
        $year = (int) $matches[1];
        $month = (int) $matches[2];
        $day = (int) $matches[3];

        if ($year >= 1300 && $year <= 1500) {
            return maneli_installment_followup_convert_jalali_to_gregorian(sprintf('%04d/%02d/%02d', $year, $month, $day));
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    $timestamp = strtotime(str_replace('/', '-', $value));
    if ($timestamp !== false) {
        return date('Y-m-d', $timestamp);
    }

    return null;
};

$format_created_date_for_display = static function ($date_string) use ($use_persian_digits_for_dashboard) {
    if (empty($date_string)) {
        return '';
    }

    $timestamp = strtotime($date_string);
    if ($timestamp === false) {
        $value = (string) $date_string;
        return $use_persian_digits_for_dashboard && function_exists('persian_numbers_no_separator')
            ? persian_numbers_no_separator($value)
            : (function_exists('maneli_convert_to_english_digits') ? maneli_convert_to_english_digits($value) : $value);
    }

    if ($use_persian_digits_for_dashboard) {
        $jalali_date = Maneli_Render_Helpers::maneli_gregorian_to_jalali(date('Y-m-d', $timestamp), 'Y/m/d');
        return function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($jalali_date) : $jalali_date;
    }

    return function_exists('wp_date') ? wp_date('Y/m/d', $timestamp) : date('Y/m/d', $timestamp);
};

$format_followup_date_for_display = static function ($raw_value) use ($use_persian_digits_for_dashboard, $convert_followup_to_gregorian) {
    if (!$raw_value) {
        return '';
    }

    $greg_date = $convert_followup_to_gregorian($raw_value);

    if ($use_persian_digits_for_dashboard) {
        if ($greg_date) {
            $jalali_date = Maneli_Render_Helpers::maneli_gregorian_to_jalali($greg_date, 'Y/m/d');
            return function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($jalali_date) : $jalali_date;
        }
        return function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($raw_value) : $raw_value;
    }

    if ($greg_date) {
        $timestamp = strtotime($greg_date);
        if ($timestamp !== false) {
            return function_exists('wp_date') ? wp_date('Y/m/d', $timestamp) : date('Y/m/d', $timestamp);
        }
        return $greg_date;
    }

    $english_digits = function_exists('maneli_convert_to_english_digits') ? maneli_convert_to_english_digits($raw_value) : $raw_value;
    $timestamp = strtotime(str_replace('/', '-', $english_digits));
    if ($timestamp !== false) {
        return function_exists('wp_date') ? wp_date('Y/m/d', $timestamp) : date('Y/m/d', $timestamp);
    }

    return $english_digits;
};

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
        $normalized_followup_for_compare = $convert_followup_to_gregorian($follow_up_date);

        if ($normalized_followup_for_compare) {
            if ($normalized_followup_for_compare === $today) {
                $today_count++;
            }
            if ($normalized_followup_for_compare < $today) {
                $overdue_count++;
            }
            if ($normalized_followup_for_compare <= $week_end) {
                $week_count++;
            }
        }
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
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-6 mb-3">
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
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-6 mb-3">
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
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-6 mb-3">
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
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-6 mb-3">
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
                <div class="p-3 border-bottom maneli-mobile-filter" data-maneli-mobile-filter>
                    <button
                        type="button"
                        class="maneli-mobile-filter-toggle-btn d-flex align-items-center justify-content-between w-100 d-md-none"
                        data-maneli-filter-toggle
                        aria-expanded="false"
                    >
                        <span class="fw-semibold"><?php esc_html_e('Filters', 'maneli-car-inquiry'); ?></span>
                        <i class="ri-arrow-down-s-line maneli-mobile-filter-arrow"></i>
                    </button>
                    <div class="maneli-mobile-filter-body" data-maneli-filter-body>
                    <form id="maneli-installment-followup-filter-form" onsubmit="return false;">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="la la-search"></i>
                                    </span>
                                    <input type="search" id="installment-followup-search-input" class="form-control" placeholder="<?php esc_attr_e('Search by customer name, car name...', 'maneli-car-inquiry'); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Filter Controls - All in one line -->
                        <div class="row g-3 align-items-end mt-1">
                            <!-- Status Filter -->
                            <div class="col-6 col-lg-2">
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
                                <div class="col-6 col-lg-2">
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
                            <div class="col-6 col-lg-2">
                                <label class="form-label"><?php esc_html_e('Sort:', 'maneli-car-inquiry'); ?></label>
                                <select id="installment-followup-sort-filter" class="form-select form-select-sm">
                                    <option value="date_asc"><?php esc_html_e('Follow-up Date (Earliest)', 'maneli-car-inquiry'); ?></option>
                                    <option value="date_desc"><?php esc_html_e('Follow-up Date (Latest)', 'maneli-car-inquiry'); ?></option>
                                    <option value="created_desc"><?php esc_html_e('Newest First', 'maneli-car-inquiry'); ?></option>
                                    <option value="created_asc"><?php esc_html_e('Oldest First', 'maneli-car-inquiry'); ?></option>
                                </select>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="col-12 col-lg-2">
                                <div class="row g-2">
                                    <div class="col-6 col-lg-6">
                                        <button type="button" id="installment-followup-apply-filters" class="btn btn-primary btn-sm w-100">
                                            <i class="la la-filter me-1"></i>
                                            <?php esc_html_e('Apply', 'maneli-car-inquiry'); ?>
                                        </button>
                                    </div>
                                    <div class="col-6 col-lg-6">
                                        <button type="button" id="installment-followup-reset-filters" class="btn btn-outline-secondary btn-sm w-100">
                                            <i class="la la-refresh me-1"></i>
                                            <?php esc_html_e('Clear', 'maneli-car-inquiry'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                    </div>
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
                                    $follow_up_date_for_compare = $convert_followup_to_gregorian($follow_up_date);
                                    $follow_up_date_display = $format_followup_date_for_display($follow_up_date);
                                    $tracking_status = get_post_meta($inquiry_id, 'tracking_status', true);
                                    $tracking_status_label = Maneli_CPT_Handler::get_tracking_status_label($tracking_status);
                                    $expert_id = get_post_meta($inquiry_id, 'assigned_expert_id', true);
                                    $expert = $expert_id ? get_userdata($expert_id) : null;
                                    
                                    // Get followup history
                                    $followup_history = get_post_meta($inquiry_id, 'followup_history', true) ?: [];
                                    
                                    // Convert dates to Jalali
                                    $created_date_display = $format_created_date_for_display($inquiry->post_date);
                                    
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
                                    $is_overdue = $follow_up_date_for_compare && $follow_up_date_for_compare < $today;
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
                                                        <i class="la la-user me-1 fs-13 align-middle"></i><?php echo esc_html($created_date_display); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td data-title="<?php esc_attr_e('Car', 'maneli-car-inquiry'); ?>"><?php echo esc_html(get_the_title($product_id)); ?></td>
                                        <td data-title="<?php esc_attr_e('Follow-up Date', 'maneli-car-inquiry'); ?>">
                                            <?php if ($follow_up_date_display !== ''): ?>
                                                <strong class="<?php echo $is_overdue ? 'text-danger' : 'text-success'; ?>">
                                                    <?php echo esc_html($follow_up_date_display); ?>
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
                                        <td data-title="<?php esc_attr_e('Registration Date', 'maneli-car-inquiry'); ?>"><?php echo esc_html($created_date_display); ?></td>
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

<?php
// Include SMS History Modal (shared template)
maneli_get_template_part('partials/sms-history-modal');
?>

<script>
// Global AJAX variables for SMS sending (fallback for timing - main localization is in class-dashboard-handler.php)
var maneliAjaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
var maneliAjaxNonce = '<?php echo wp_create_nonce('maneli-ajax-nonce'); ?>';
</script>
