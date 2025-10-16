# 🔌 مستندات API سیستم گزارشات

## نمای کلی

این مستندات راهنمای کامل استفاده از API سیستم گزارشات برای توسعه‌دهندگان است.

## کلاس اصلی: `Maneli_Reports_Dashboard`

### 1. دریافت آمار کلی

```php
Maneli_Reports_Dashboard::get_overall_statistics($start_date, $end_date, $expert_id)
```

**پارامترها:**
- `$start_date` (string|null): تاریخ شروع به فرمت `Y-m-d` - پیش‌فرض: 30 روز گذشته
- `$end_date` (string|null): تاریخ پایان به فرمت `Y-m-d` - پیش‌فرض: امروز
- `$expert_id` (int|null): شناسه کارشناس - پیش‌فرض: همه کارشناسان

**خروجی:**
```php
[
    'total_inquiries' => 150,        // کل استعلام‌ها
    'cash_inquiries' => 80,          // استعلام‌های نقدی
    'installment_inquiries' => 70,   // استعلام‌های اقساطی
    'pending' => 25,                 // در انتظار
    'approved' => 90,                // تایید شده
    'rejected' => 20,                // رد شده
    'following' => 15,               // در حال پیگیری
    'next_followup' => 10,           // پیگیری آینده
    'new_today' => 5,                // استعلام‌های امروز
    'revenue' => 50000000,           // درآمد (تومان)
]
```

**مثال استفاده:**

```php
// آمار 30 روز گذشته
$stats = Maneli_Reports_Dashboard::get_overall_statistics();

// آمار بازه خاص
$stats = Maneli_Reports_Dashboard::get_overall_statistics('2025-01-01', '2025-01-31');

// آمار یک کارشناس خاص
$stats = Maneli_Reports_Dashboard::get_overall_statistics(null, null, 123);

// نمایش
echo "کل استعلام‌ها: " . $stats['total_inquiries'];
```

---

### 2. دریافت آمار کارشناسان

```php
Maneli_Reports_Dashboard::get_experts_statistics($start_date, $end_date)
```

**پارامترها:**
- `$start_date` (string|null): تاریخ شروع
- `$end_date` (string|null): تاریخ پایان

**خروجی:**
```php
[
    [
        'expert_id' => 123,
        'expert_name' => 'علی احمدی',
        'expert_email' => 'ali@example.com',
        'total_inquiries' => 45,
        'cash_inquiries' => 25,
        'installment_inquiries' => 20,
        'approved' => 30,
        'rejected' => 5,
        'pending' => 10,
        'following' => 5,
        'total_customers' => 38,
        'new_customers' => 12,
        'revenue' => 15000000,
    ],
    // ... سایر کارشناسان
]
```

**مثال استفاده:**

```php
$experts_stats = Maneli_Reports_Dashboard::get_experts_statistics('2025-01-01', '2025-01-31');

foreach ($experts_stats as $expert) {
    echo $expert['expert_name'] . ': ' . $expert['total_inquiries'] . ' استعلام<br>';
}
```

---

### 3. دریافت آمار روزانه

```php
Maneli_Reports_Dashboard::get_daily_statistics($start_date, $end_date, $expert_id)
```

**پارامترها:**
- `$start_date` (string|null): تاریخ شروع
- `$end_date` (string|null): تاریخ پایان
- `$expert_id` (int|null): شناسه کارشناس

**خروجی:**
```php
[
    [
        'date' => '2025-01-15',
        'cash' => 5,
        'installment' => 3,
        'total' => 8,
    ],
    [
        'date' => '2025-01-16',
        'cash' => 7,
        'installment' => 4,
        'total' => 11,
    ],
    // ...
]
```

**مثال استفاده:**

```php
$daily_stats = Maneli_Reports_Dashboard::get_daily_statistics('2025-01-01', '2025-01-31');

foreach ($daily_stats as $day) {
    echo $day['date'] . ': ' . $day['total'] . ' استعلام<br>';
}
```

---

### 4. دریافت محصولات پرطرفدار

```php
Maneli_Reports_Dashboard::get_popular_products($start_date, $end_date, $expert_id, $limit)
```

**پارامترها:**
- `$start_date` (string|null): تاریخ شروع
- `$end_date` (string|null): تاریخ پایان
- `$expert_id` (int|null): شناسه کارشناس
- `$limit` (int): تعداد محصولات - پیش‌فرض: 10

**خروجی:**
```php
[
    [
        'product_id' => 456,
        'product_name' => 'پژو 206',
        'inquiry_count' => 35,
    ],
    [
        'product_id' => 789,
        'product_name' => 'سمند LX',
        'inquiry_count' => 28,
    ],
    // ...
]
```

**مثال استفاده:**

```php
$popular = Maneli_Reports_Dashboard::get_popular_products(null, null, null, 5);

echo "<h3>5 محصول برتر</h3>";
foreach ($popular as $index => $product) {
    echo ($index + 1) . '. ' . $product['product_name'] . ' (' . $product['inquiry_count'] . ' استعلام)<br>';
}
```

---

### 5. دریافت عملکرد ماهانه

```php
Maneli_Reports_Dashboard::get_monthly_performance($months, $expert_id)
```

**پارامترها:**
- `$months` (int): تعداد ماه گذشته - پیش‌فرض: 6
- `$expert_id` (int|null): شناسه کارشناس

**خروجی:**
```php
[
    [
        'month' => '2025-01',
        'month_persian' => 'دی 1403',
        'cash' => 45,
        'installment' => 38,
        'total' => 83,
    ],
    // ...
]
```

**مثال استفاده:**

```php
$monthly = Maneli_Reports_Dashboard::get_monthly_performance(12);

foreach ($monthly as $month) {
    echo $month['month_persian'] . ': ' . $month['total'] . ' استعلام<br>';
}
```

---

### 6. دریافت جزئیات استعلام‌ها

```php
Maneli_Reports_Dashboard::get_inquiries_details($args)
```

**پارامترها:**

```php
$args = [
    'start_date' => '2025-01-01',    // تاریخ شروع
    'end_date' => '2025-01-31',      // تاریخ پایان
    'expert_id' => null,             // شناسه کارشناس
    'status' => null,                // وضعیت: pending, approved, rejected, following
    'type' => 'all',                 // نوع: all, cash, installment
    'limit' => 50,                   // تعداد نتایج در هر صفحه
    'offset' => 0,                   // شروع از
    'orderby' => 'date',             // مرتب‌سازی: date, customer, status
    'order' => 'DESC',               // ترتیب: ASC, DESC
];
```

**خروجی:**
```php
[
    'inquiries' => [
        [
            'ID' => 123,
            'post_date' => '2025-01-15 14:30:00',
            'post_type' => 'cash_inquiry',
            'post_author' => 456,
            'status' => 'approved',
            'customer_name' => 'محمد رضایی',
            'customer_phone' => '09123456789',
            'customer_national_id' => '0123456789',
            'product_id' => 789,
            'product_name' => 'پژو 206',
            'amount' => 500000,
        ],
        // ...
    ],
    'total' => 150,   // کل تعداد
    'pages' => 3,     // تعداد صفحات
]
```

**مثال استفاده:**

```php
$result = Maneli_Reports_Dashboard::get_inquiries_details([
    'start_date' => '2025-01-01',
    'end_date' => '2025-01-31',
    'status' => 'approved',
    'type' => 'cash',
    'limit' => 10,
]);

echo "کل: " . $result['total'] . " استعلام<br>";

foreach ($result['inquiries'] as $inquiry) {
    echo $inquiry['customer_name'] . ' - ' . $inquiry['product_name'] . '<br>';
}
```

---

### 7. صادرات به CSV

```php
Maneli_Reports_Dashboard::export_to_csv($args)
```

**پارامترها:**
مشابه `get_inquiries_details()`

**خروجی:**
فایل CSV برای دانلود (این متد خروجی header تنظیم می‌کند و اجرای اسکریپت را متوقف می‌کند)

**مثال استفاده:**

```php
// در یک صفحه جداگانه یا Ajax handler
Maneli_Reports_Dashboard::export_to_csv([
    'start_date' => '2025-01-01',
    'end_date' => '2025-01-31',
    'type' => 'all',
]);
// بعد از این خط کد اجرا نمی‌شود
```

---

### 8. متدهای کمکی

#### تعداد مشتریان یک کارشناس

```php
Maneli_Reports_Dashboard::get_expert_customers_count($expert_id)
```

**خروجی:** integer

```php
$count = Maneli_Reports_Dashboard::get_expert_customers_count(123);
echo "تعداد مشتریان: " . $count;
```

#### تعداد مشتریان جدید

```php
Maneli_Reports_Dashboard::get_expert_new_customers_count($expert_id, $start_date, $end_date)
```

**خروجی:** integer

```php
$new_count = Maneli_Reports_Dashboard::get_expert_new_customers_count(123, '2025-01-01', '2025-01-31');
echo "مشتریان جدید: " . $new_count;
```

---

## استفاده در تم یا پلاگین دیگر

### نمایش آمار در تم

```php
// در فایل template
<?php
if (class_exists('Maneli_Reports_Dashboard')) {
    $stats = Maneli_Reports_Dashboard::get_overall_statistics();
    ?>
    <div class="my-custom-stats">
        <h2>آمار سیستم</h2>
        <p>کل استعلام‌ها: <?php echo $stats['total_inquiries']; ?></p>
        <p>تایید شده: <?php echo $stats['approved']; ?></p>
        <p>درآمد: <?php echo number_format($stats['revenue']); ?> تومان</p>
    </div>
    <?php
}
?>
```

### ساخت شورت‌کد سفارشی

```php
function my_custom_reports_shortcode($atts) {
    $atts = shortcode_atts([
        'expert_id' => null,
        'days' => 30,
    ], $atts);
    
    if (!class_exists('Maneli_Reports_Dashboard')) {
        return 'کلاس گزارشات یافت نشد';
    }
    
    $start_date = date('Y-m-d', strtotime("-{$atts['days']} days"));
    $end_date = date('Y-m-d');
    
    $stats = Maneli_Reports_Dashboard::get_overall_statistics(
        $start_date,
        $end_date,
        $atts['expert_id']
    );
    
    ob_start();
    ?>
    <div class="custom-reports-widget">
        <h3>آمار <?php echo $atts['days']; ?> روز گذشته</h3>
        <ul>
            <li>کل استعلام‌ها: <?php echo $stats['total_inquiries']; ?></li>
            <li>تایید شده: <?php echo $stats['approved']; ?></li>
            <li>رد شده: <?php echo $stats['rejected']; ?></li>
        </ul>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('maneli_custom_stats', 'my_custom_reports_shortcode');

// استفاده:
// [maneli_custom_stats days="60" expert_id="123"]
```

### استفاده در Rest API

```php
add_action('rest_api_init', function() {
    register_rest_route('maneli/v1', '/reports/overall', [
        'methods' => 'GET',
        'callback' => function($request) {
            if (!current_user_can('manage_maneli_inquiries')) {
                return new WP_Error('unauthorized', 'دسترسی غیرمجاز', ['status' => 403]);
            }
            
            $start_date = $request->get_param('start_date');
            $end_date = $request->get_param('end_date');
            $expert_id = $request->get_param('expert_id');
            
            $stats = Maneli_Reports_Dashboard::get_overall_statistics(
                $start_date,
                $end_date,
                $expert_id
            );
            
            return rest_ensure_response($stats);
        },
        'permission_callback' => function() {
            return current_user_can('manage_maneli_inquiries');
        }
    ]);
});

// استفاده:
// GET /wp-json/maneli/v1/reports/overall?start_date=2025-01-01&end_date=2025-01-31
```

---

## Ajax Endpoints

### دریافت آمار کلی

**Action:** `maneli_get_overall_stats`

**Method:** POST

**پارامترها:**
```javascript
{
    action: 'maneli_get_overall_stats',
    nonce: maneliReports.nonce,
    start_date: '2025-01-01',
    end_date: '2025-01-31',
    expert_id: 123
}
```

**مثال jQuery:**

```javascript
$.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
        action: 'maneli_get_overall_stats',
        nonce: maneliReports.nonce,
        start_date: '2025-01-01',
        end_date: '2025-01-31'
    },
    success: function(response) {
        if (response.success) {
            console.log(response.data);
        }
    }
});
```

### سایر Ajax Actions

- `maneli_get_experts_stats` - آمار کارشناسان
- `maneli_get_daily_stats` - آمار روزانه
- `maneli_get_popular_products` - محصولات پرطرفدار
- `maneli_get_monthly_performance` - عملکرد ماهانه
- `maneli_get_inquiries_details` - جزئیات استعلام‌ها

همه با ساختار مشابه کار می‌کنند.

---

## Hooks و Filters

### فیلتر برای تغییر تعداد پیش‌فرض محصولات

```php
add_filter('maneli_popular_products_limit', function($limit) {
    return 20; // به جای 10
});
```

### اکشن بعد از دریافت آمار

```php
add_action('maneli_after_get_statistics', function($stats) {
    // لاگ کردن یا ارسال به سرویس دیگر
    error_log('آمار دریافت شد: ' . json_encode($stats));
}, 10, 1);
```

---

## نکات امنیتی

1. **همیشه دسترسی را چک کنید:**
```php
if (!current_user_can('manage_maneli_inquiries')) {
    wp_die('دسترسی غیرمجاز');
}
```

2. **استفاده از nonce در Ajax:**
```php
check_ajax_referer('maneli_reports_nonce', 'nonce');
```

3. **Sanitize ورودی‌ها:**
```php
$start_date = sanitize_text_field($_POST['start_date']);
$expert_id = intval($_POST['expert_id']);
```

4. **Escape خروجی‌ها:**
```php
echo esc_html($stats['total_inquiries']);
```

---

## مثال‌های پیشرفته

### ویجت سفارشی در داشبورد

```php
function add_custom_dashboard_widget() {
    wp_add_dashboard_widget(
        'my_custom_stats_widget',
        'آمار سفارشی من',
        'render_custom_stats_widget'
    );
}
add_action('wp_dashboard_setup', 'add_custom_dashboard_widget');

function render_custom_stats_widget() {
    $stats = Maneli_Reports_Dashboard::get_overall_statistics();
    $experts = Maneli_Reports_Dashboard::get_experts_statistics();
    
    ?>
    <h3>آمار کلی</h3>
    <p>کل استعلام‌ها: <?php echo $stats['total_inquiries']; ?></p>
    
    <h4>برترین کارشناس</h4>
    <?php
    usort($experts, function($a, $b) {
        return $b['total_inquiries'] - $a['total_inquiries'];
    });
    
    if (!empty($experts)) {
        $top = $experts[0];
        echo "<p><strong>{$top['expert_name']}</strong>: {$top['total_inquiries']} استعلام</p>";
    }
    ?>
    <?php
}
```

### گزارش هفتگی خودکار

```php
function send_weekly_report() {
    $start_date = date('Y-m-d', strtotime('last monday'));
    $end_date = date('Y-m-d', strtotime('last sunday'));
    
    $stats = Maneli_Reports_Dashboard::get_overall_statistics($start_date, $end_date);
    $experts = Maneli_Reports_Dashboard::get_experts_statistics($start_date, $end_date);
    
    $message = "گزارش هفتگی: {$start_date} تا {$end_date}\n\n";
    $message .= "کل استعلام‌ها: {$stats['total_inquiries']}\n";
    $message .= "تایید شده: {$stats['approved']}\n";
    $message .= "درآمد: " . number_format($stats['revenue']) . " تومان\n\n";
    
    $message .= "عملکرد کارشناسان:\n";
    foreach ($experts as $expert) {
        $message .= "- {$expert['expert_name']}: {$expert['total_inquiries']} استعلام\n";
    }
    
    wp_mail('manager@example.com', 'گزارش هفتگی سیستم', $message);
}

// اجرا هر دوشنبه ساعت 9 صبح
if (!wp_next_scheduled('maneli_weekly_report')) {
    wp_schedule_event(strtotime('next monday 9:00'), 'weekly', 'maneli_weekly_report');
}
add_action('maneli_weekly_report', 'send_weekly_report');
```

---

## تست و Debug

### فعال‌سازی حالت Debug

```php
// در wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### لاگ کردن داده‌ها

```php
$stats = Maneli_Reports_Dashboard::get_overall_statistics();
error_log('Stats: ' . print_r($stats, true));
```

### بررسی کوئری‌ها

```php
global $wpdb;
$wpdb->show_errors();
$stats = Maneli_Reports_Dashboard::get_overall_statistics();
$wpdb->print_error();
```

---

**نسخه API**: 1.0.0  
**سازگاری**: WordPress 5.0+, PHP 7.4+  
**آخرین به‌روزرسانی**: 2025

