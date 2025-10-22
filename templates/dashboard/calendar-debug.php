<?php
/**
 * Calendar Debug Page - Simple debug to check what's happening
 */

// Debug information
$current_user = wp_get_current_user();
$is_admin = current_user_can('manage_maneli_inquiries');
$is_expert = in_array('maneli_expert', $current_user->roles, true);
$page = get_query_var('maneli_dashboard_page');

echo '<div class="container mt-4">';
echo '<h2>اطلاعات دیباگ تقویم</h2>';

echo '<div class="alert alert-info">';
echo '<h4>اطلاعات کاربر:</h4>';
echo '<p><strong>نام کاربر:</strong> ' . $current_user->display_name . '</p>';
echo '<p><strong>ID کاربر:</strong> ' . $current_user->ID . '</p>';
echo '<p><strong>نقش‌ها:</strong> ' . implode(', ', $current_user->roles) . '</p>';
echo '<p><strong>مدیر است:</strong> ' . ($is_admin ? 'بله' : 'خیر') . '</p>';
echo '<p><strong>کارشناس است:</strong> ' . ($is_expert ? 'بله' : 'خیر') . '</p>';
echo '</div>';

echo '<div class="alert alert-warning">';
echo '<h4>اطلاعات صفحه:</h4>';
echo '<p><strong>صفحه فعلی:</strong> ' . ($page ?: 'خالی') . '</p>';
echo '<p><strong>Query Vars:</strong> ' . print_r(get_query_vars(), true) . '</p>';
echo '</div>';

echo '<div class="alert alert-success">';
echo '<h4>تست دسترسی:</h4>';
if ($is_admin || $is_expert) {
    echo '<p class="text-success">✓ دسترسی مجاز است</p>';
} else {
    echo '<p class="text-danger">✗ دسترسی مجاز نیست</p>';
}
echo '</div>';

echo '<div class="alert alert-primary">';
echo '<h4>تست فایل‌ها:</h4>';
$calendar_file = MANELI_INQUIRY_PLUGIN_PATH . 'templates/dashboard/calendar-meetings.php';
echo '<p><strong>مسیر فایل تقویم:</strong> ' . $calendar_file . '</p>';
echo '<p><strong>فایل موجود است:</strong> ' . (file_exists($calendar_file) ? 'بله' : 'خیر') . '</p>';

$css_file = MANELI_INQUIRY_PLUGIN_PATH . 'assets/css/calendar-meetings.css';
echo '<p><strong>مسیر فایل CSS:</strong> ' . $css_file . '</p>';
echo '<p><strong>فایل CSS موجود است:</strong> ' . (file_exists($css_file) ? 'بله' : 'خیر') . '</p>';

$js_file = MANELI_INQUIRY_PLUGIN_PATH . 'assets/js/calendar-meetings.js';
echo '<p><strong>مسیر فایل JS:</strong> ' . $js_file . '</p>';
echo '<p><strong>فایل JS موجود است:</strong> ' . (file_exists($js_file) ? 'بله' : 'خیر') . '</p>';
echo '</div>';

echo '<div class="alert alert-secondary">';
echo '<h4>تست ثابت‌ها:</h4>';
echo '<p><strong>MANELI_INQUIRY_PLUGIN_PATH:</strong> ' . (defined('MANELI_INQUIRY_PLUGIN_PATH') ? MANELI_INQUIRY_PLUGIN_PATH : 'تعریف نشده') . '</p>';
echo '<p><strong>MANELI_INQUIRY_PLUGIN_URL:</strong> ' . (defined('MANELI_INQUIRY_PLUGIN_URL') ? MANELI_INQUIRY_PLUGIN_URL : 'تعریف نشده') . '</p>';
echo '</div>';

echo '</div>';
?>
