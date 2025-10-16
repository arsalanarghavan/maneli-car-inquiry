# راهنمای دیباگ مشکل تخصیص کارشناس در گزارش

## 🔍 مشکل گزارش شده

- ✅ در صفحه **لیست استعلام‌ها**: تخصیص کارشناس کار می‌کند
- ❌ در صفحه **گزارش استعلام**: خطای "هیچ کارشناسی برای تخصیص یافت نشد"

---

## 🛠️ اصلاحات انجام شده

### 1️⃣ اضافه کردن Debug Logs به JavaScript

فایل: `assets/js/frontend/inquiry-lists.js`

Debug logs اضافه شده که نشان می‌دهد:
- آیا `maneliInquiryLists` تعریف شده است؟
- محتویات کامل `maneliInquiryLists`
- آرایه `experts` و تعداد آن
- داده‌های پردازش شده

### 2️⃣ اضافه کردن Debug Logs به PHP  

فایل: `includes/shortcodes/class-inquiry-lists-shortcode.php`

Debug logs اضافه شده که نشان می‌دهد:
- تعداد کارشناسان یافت شده از دیتابیس
- لیست کامل کارشناسان
- آرایه نهایی که به JavaScript پاس داده می‌شود

### 3️⃣ بهبود Enqueue Script

اطمینان از اینکه اسکریپت فقط یک بار register می‌شود.

---

## 🧪 نحوه دیباگ

### مرحله 1: فعال کردن Debug Mode در WordPress

در فایل `wp-config.php` خط زیر را پیدا کنید و به `true` تغییر دهید:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### مرحله 2: تست در صفحه لیست (که کار می‌کند)

1. به صفحه **لیست استعلام‌های اقساطی** بروید
2. Console مرورگر را باز کنید (F12)
3. روی دکمه **"تخصیص کارشناس"** کلیک کنید
4. در Console دنبال پیام‌های زیر بگردید:

```javascript
=== Expert Assignment Debug ===
maneliInquiryLists exists: true
Full maneliInquiryLists: {ajax_url: "...", experts: [...], ...}
Experts array: [...]
Experts count: X  // باید بیشتر از 0 باشد
Processed expertsData: [...]
Processed expertsData length: X
SUCCESS: Found X experts
```

### مرحله 3: تست در صفحه گزارش (که مشکل دارد)

1. به صفحه **گزارش یک استعلام اقساطی** بروید
2. Console مرورگر را باز کنید (F12)
3. روی دکمه **"تخصیص کارشناس"** کلیک کنید
4. در Console دنبال پیام‌ها بگردید

**اگر مشکل وجود داشته باشد، باید ببینید:**

```javascript
=== Expert Assignment Debug ===
maneliInquiryLists exists: true/false
Full maneliInquiryLists: {...}
Experts array: [] یا undefined
Experts count: 0
Processed expertsData: []
Processed expertsData length: 0
ERROR: No experts found!
```

### مرحله 4: بررسی Logs سمت سرور

لاگ‌های WordPress را چک کنید:

```bash
tail -f /path/to/wp-content/debug.log | grep "Expert Assignment"
```

یا در محیط شما:

```bash
tail -f wp-content/debug.log | grep "Expert Assignment"
```

باید چیزی شبیه این ببینید:

```
=== Expert Assignment Debug (PHP) ===
Total experts found: X
Experts query result: Array (...)
Final experts_list count: X
Final experts_list: Array (...)
```

---

## 🔬 سناریوهای احتمالی و راه‌حل

### سناریو 1: `maneliInquiryLists` اصلاً تعریف نشده

**علامت:**
```javascript
maneliInquiryLists exists: false
Full maneliInquiryLists: undefined
```

**علت:** اسکریپت `maneli-inquiry-lists-js` enqueue نشده است.

**راه‌حل:**
- بررسی کنید که متد `enqueue_admin_list_assets()` در `render_single_inquiry_report()` صدا زده می‌شود
- در View Source صفحه دنبال این بگردید:
  ```html
  <script src=".../inquiry-lists.js"></script>
  <script>
  var maneliInquiryLists = {...};
  </script>
  ```

---

### سناریو 2: `maneliInquiryLists` تعریف شده ولی `experts` خالی است

**علامت:**
```javascript
maneliInquiryLists exists: true
Experts array: []
Experts count: 0
```

**علت احتمالی 1:** PHP کارشناسان را پیدا نمی‌کند

**بررسی:**
```
=== Expert Assignment Debug (PHP) ===
Total experts found: 0
```

**راه‌حل:** بررسی کنید که کاربران دقیقاً با نقش `maneli_expert` ثبت شده‌اند:
```sql
SELECT * FROM wp_usermeta WHERE meta_key = 'wp_capabilities' AND meta_value LIKE '%maneli_expert%';
```

**علت احتمالی 2:** `wp_localize_script` چند بار صدا زده می‌شود و override می‌شود

**راه‌حل:** در View Source صفحه دنبال `maneliInquiryLists` بگردید. اگر چندین بار تعریف شده، آخرین نسخه باید کارشناسان را داشته باشد.

---

### سناریو 3: نقش `maneli_expert` به درستی ثبت نشده

**علامت در PHP logs:**
```
Total experts found: 0
```

**بررسی:** اجرای کوئری زیر در phpMyAdmin یا MySQL:

```sql
-- بررسی جدول users
SELECT ID, user_login, display_name FROM wp_users 
WHERE ID IN (
    SELECT user_id FROM wp_usermeta 
    WHERE meta_key = 'wp_capabilities' 
    AND meta_value LIKE '%maneli_expert%'
);
```

**اگر نتیجه خالی بود:**
- هیچ کاربری با نقش `maneli_expert` وجود ندارد
- باید کاربران را دوباره با این نقش ثبت کنید

**راه‌حل:**
1. به **کاربران > همه کاربران** بروید
2. یک کاربر را ویرایش کنید
3. نقش را روی **"Maneli Expert"** بگذارید
4. ذخیره کنید

---

### سناریو 4: Cache مرورگر یا Plugin

**علامت:** کد جدید اجرا نمی‌شود

**راه‌حل:**
1. Cache مرورگر را پاک کنید (Ctrl+Shift+Delete)
2. اگر از plugin cache استفاده می‌کنید، آن را پاک کنید
3. در حالت Incognito/Private تست کنید
4. Hard Refresh کنید (Ctrl+F5)

---

## 📊 خروجی مورد انتظار در حالت سالم

### در Console مرورگر:
```javascript
=== Expert Assignment Debug ===
maneliInquiryLists exists: true
Full maneliInquiryLists: {
  ajax_url: "http://yoursite.com/wp-admin/admin-ajax.php",
  experts: [
    {id: 5, name: "علی کارشناسی"},
    {id: 8, name: "محمد رضایی"},
    {id: 12, name: "فاطمه احمدی"}
  ],
  nonces: {...},
  ...
}
Experts array: (3) [{…}, {…}, {…}]
Experts count: 3
Processed expertsData: (3) [{…}, {…}, {…}]
Processed expertsData length: 3
SUCCESS: Found 3 experts
```

### در debug.log:
```
=== Expert Assignment Debug (PHP) ===
Total experts found: 3
Final experts_list count: 3
Final experts_list: Array
(
    [0] => Array (
        [id] => 5
        [name] => علی کارشناسی
    )
    [1] => Array (
        [id] => 8
        [name] => محمد رضایی
    )
    [2] => Array (
        [id] => 12
        [name] => فاطمه احمدی
    )
)
```

---

## 🚀 مراحل بعدی

بعد از جمع‌آوری اطلاعات debug:

1. ✅ Console logs را کپی کنید
2. ✅ Server logs را کپی کنید  
3. ✅ View Source صفحه را ذخیره کنید (قسمت `<script>` مربوط به `maneliInquiryLists`)
4. ✅ نتایج کوئری SQL را ذخیره کنید

با این اطلاعات می‌توان مشکل دقیق را شناسایی و رفع کرد.

---

## 🔧 حذف Debug Logs بعد از رفع مشکل

بعد از حل مشکل، debug logs را حذف کنید:

1. از فایل `assets/js/frontend/inquiry-lists.js` تمام `console.log` ها را حذف کنید
2. از فایل `includes/shortcodes/class-inquiry-lists-shortcode.php` بلوک‌های `if (defined('WP_DEBUG'))` را حذف کنید
3. در `wp-config.php` مقدار `WP_DEBUG` را به `false` برگردانید

