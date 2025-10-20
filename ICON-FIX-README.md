# راهنمای فیکس آیکون‌های Line Awesome

## مشکل اصلی
فایل‌های CSS فارسی‌ساز با استفاده از selector `*` روی **همه المنت‌ها** فونت IRANSans را اعمال می‌کردند:

```css
*,
*::before,
*::after {
    font-family: IRANSans, Tahoma, Arial, sans-serif !important;
}
```

این باعث می‌شد آیکون‌های Line Awesome هم فونت IRANSans بگیرند و به جای آیکون، مربع نشان داده شود.

## راه حل

### 1. استثنا در CSS فارسی‌ساز
در فایل‌های زیر استثنا برای آیکون‌های Line Awesome اضافه شد:
- `assets/css/maneli-rtl-force.css`
- `assets/css/maneli-complete-dashboard-fix.css`

### 2. فایل CSS فیکس جدید
فایل `assets/css/line-awesome-fix.css` ساخته شد که:
- @font-face را با مسیرهای صحیح تعریف می‌کند
- کلاس‌های پایه را با !important override می‌کند
- تمام آیکون‌های مورد استفاده را با content codes تعریف می‌کند

### 3. تغییر در Enqueue
در فایل‌های زیر ترتیب لود CSS ها تغییر کرد:
- `includes/class-dashboard-handler.php`
- `includes/class-shortcode-handler.php`

**ترتیب صحیح:**
1. styles.css
2. line-awesome.css (اصلی)
3. سایر CSS ها
4. maneli-rtl-force.css
5. maneli-dashboard-fix.css
6. line-awesome-fix.css (آخرین!)

## تست
فایل‌های تست:
- `test-final.html` - تست کامل با همان ترتیب CSS ها
- `test-la-icons.html` - نمایش زیبای آیکون‌ها
- `test-debug.php` - دیباگ با PHP

## نکات مهم
- ⚠️ همیشه `line-awesome-fix.css` باید **آخرین CSS** لود شده باشد
- ⚠️ اگر CSS جدیدی اضافه می‌کنید، مطمئن شوید که Line Awesome را Override نمی‌کند
- ⚠️ Cache مرورگر را پاک کنید (Ctrl+F5)

## فایل‌های تغییر یافته
✅ assets/css/maneli-rtl-force.css
✅ assets/css/maneli-complete-dashboard-fix.css  
✅ assets/css/maneli-dashboard-fix.css
✅ assets/css/line-awesome-fix.css (جدید)
✅ includes/class-dashboard-handler.php
✅ includes/class-shortcode-handler.php
✅ templates/dashboard/header.php
✅ templates/dashboard/login.php

## تعداد آیکون‌های تبدیل شده
📊 213+ آیکون در 36 فایل template

