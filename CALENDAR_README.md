# تقویم جلسات - راهنمای استفاده

## معرفی

این سیستم تقویم جلسات یک رابط کاربری کامل و زیبا برای مدیریت و نمایش جلسات حضوری ارائه می‌دهد. این سیستم با پشتیبانی کامل از تاریخ شمسی و شروع هفته از شنبه طراحی شده است.

## ویژگی‌ها

### ✨ ویژگی‌های اصلی
- **تقویم کامل**: نمایش روزانه، هفتگی و ماهانه
- **تاریخ شمسی**: تمام تاریخ‌ها به صورت شمسی نمایش داده می‌شوند
- **شروع از شنبه**: هفته از روز شنبه شروع می‌شود
- **جدول جلسات**: لیست مرتب شده بر اساس نزدیک‌ترین زمان
- **طراحی زیبا**: رابط کاربری مدرن و واکنش‌گرا
- **رنگ‌بندی هوشمند**: جلسات مختلف با رنگ‌های مختلف نمایش داده می‌شوند

### 🎨 رنگ‌بندی جلسات
- **سبز**: جلسات نقدی
- **آبی**: جلسات اقساطی  
- **خاکستری**: جلسات رزرو شده

## فایل‌های سیستم

### 1. قالب اصلی
```
templates/dashboard/calendar-meetings.php
```
صفحه اصلی تقویم جلسات با تمام قابلیت‌ها

### 2. فایل‌های CSS
```
assets/css/calendar-meetings.css
```
استایل‌های مخصوص تقویم جلسات

### 3. فایل‌های JavaScript
```
assets/js/calendar-meetings.js
```
اسکریپت‌های تقویم و تعاملات کاربری

### 4. صفحه راهنما
```
templates/dashboard/calendar-demo.php
```
صفحه راهنمای استفاده از تقویم

## نحوه استفاده

### 1. بارگذاری فایل‌ها

در فایل اصلی پلاگین یا قالب، فایل‌های CSS و JS را بارگذاری کنید:

```php
// بارگذاری CSS
wp_enqueue_style('calendar-meetings-css', plugin_dir_url(__FILE__) . 'assets/css/calendar-meetings.css');

// بارگذاری JavaScript
wp_enqueue_script('calendar-meetings-js', plugin_dir_url(__FILE__) . 'assets/js/calendar-meetings.js', ['jquery'], '1.0.0', true);

// بارگذاری FullCalendar
wp_enqueue_script('fullcalendar', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js', [], '6.1.8', true);
wp_enqueue_style('fullcalendar-css', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css', [], '6.1.8');
```

### 2. نمایش تقویم

برای نمایش تقویم، از کد زیر استفاده کنید:

```php
// در قالب یا صفحه
include get_template_directory() . '/templates/dashboard/calendar-meetings.php';
```

### 3. تنظیمات دسترسی

سیستم تقویم برای کاربران زیر قابل دسترسی است:
- مدیران سیستم (`manage_maneli_inquiries`)
- کارشناسان (`maneli_expert`)

## ساختار داده‌ها

### داده‌های تقویم
```javascript
window.meetingsCalendarData = [
    {
        id: 'meeting_id',
        title: 'نام مشتری',
        start: '2024-01-15T10:00:00',
        end: '2024-01-15T11:00:00',
        extendedProps: {
            customer: 'نام کامل مشتری',
            mobile: 'شماره تماس',
            product: 'نام محصول',
            inquiry_id: 'inquiry_id',
            inquiry_type: 'cash|installment',
            can_view_details: true,
            time: '10:00 - 11:00'
        }
    }
];
```

## سفارشی‌سازی

### 1. تغییر رنگ‌ها
در فایل `calendar-meetings.css` متغیرهای CSS را تغییر دهید:

```css
:root {
    --primary-color: #007bff;
    --primary-hover: #0056b3;
    --primary-rgb: 0, 123, 255;
}
```

### 2. تغییر استایل رویدادها
```css
.fc-event.meeting-cash {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}
```

### 3. تغییر متن‌ها
در فایل `calendar-meetings.js` متن‌های فارسی را تغییر دهید:

```javascript
const persianLocale = {
    buttonText: {
        prev: 'قبلی',
        next: 'بعدی',
        today: 'امروز',
        // ...
    }
};
```

## API و توابع

### توابع JavaScript

#### `initCalendar()`
مقداردهی اولیه تقویم

#### `showMeetingDetails(event)`
نمایش جزئیات جلسه در مودال

#### `updateCalendarTitle(info)`
به‌روزرسانی عنوان تقویم با تاریخ شمسی

#### `sortTable(table, columnIndex)`
مرتب‌سازی جدول بر اساس ستون

### توابع PHP

#### `maneli_gregorian_to_jalali($gregorian_date, $format)`
تبدیل تاریخ میلادی به شمسی

## پشتیبانی از مرورگرها

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+

## مشکلات رایج

### 1. تقویم نمایش داده نمی‌شود
- بررسی کنید که فایل‌های CSS و JS بارگذاری شده‌اند
- مطمئن شوید که FullCalendar بارگذاری شده است

### 2. تاریخ‌ها شمسی نیستند
- بررسی کنید که تابع `maneli_gregorian_to_jalali` موجود است
- مطمئن شوید که کتابخانه تاریخ شمسی بارگذاری شده است

### 3. تقویم RTL نیست
- بررسی کنید که `direction: rtl` در CSS تنظیم شده است
- مطمئن شوید که `locale: persianLocale` در JavaScript تنظیم شده است

## به‌روزرسانی

برای به‌روزرسانی سیستم تقویم:

1. فایل‌های جدید را جایگزین کنید
2. کش مرورگر را پاک کنید
3. بررسی کنید که تمام فایل‌ها بارگذاری شده‌اند

## پشتیبانی

برای پشتیبانی و گزارش مشکلات، با تیم توسعه تماس بگیرید.

---

**نسخه**: 1.0.0  
**تاریخ**: 1403/01/15  
**توسعه‌دهنده**: تیم توسعه منلی
