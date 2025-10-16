# رفع مشکل تخصیص کارشناس در گزارش درخواست اقساطی

## تاریخ: 2025-10-16
## نوع: Bug Fix

---

## شرح مشکل

هنگام کلیک روی دکمه "تخصیص کارشناس" در صفحه گزارش درخواست اقساطی، یک خطای JavaScript رخ می‌داد و modal برای انتخاب کارشناس به درستی نمایش داده نمی‌شد.

### علت مشکل

1. **کد JavaScript**: کد سعی می‌کرد dropdown فیلتر کارشناس را از صفحه لیست کلون کند، اما در صفحه گزارش این dropdown وجود نداشت
2. **عدم enqueue کردن Select2**: کتابخانه Select2 که برای نمایش dropdown کارشناسان استفاده می‌شود، در صفحه inquiry lists enqueue نشده بود
3. **عدم وجود stringهای localization**: برخی پیام‌های خطا در فایل ترجمه وجود نداشت

---

## راه‌حل اعمال شده

### 1. اصلاح JavaScript (`assets/js/frontend/inquiry-lists.js`)

**قبل از اصلاح**: کد سعی می‌کرد dropdown را از صفحه کلون کند:

```javascript
// Try to clone the expert filter dropdown from the page, or build from experts data
const expertFilterSelector = (inquiryType === 'cash') ? '#cash-expert-filter' : '#expert-filter';
let expertOptionsHTML = '';

if ($(expertFilterSelector).length) {
    // Clone from existing dropdown
    expertOptionsHTML = $(expertFilterSelector)
        .clone()
        .prop('id', 'swal-expert-filter')
        .prepend(`<option value="auto">${getText('auto_assign')}</option>`)
        .val('auto')
        .get(0).outerHTML;
} else {
    // Build from localized experts data (for report pages)
    // ...
}
```

**بعد از اصلاح**: کد همیشه از داده‌های localized استفاده می‌کند:

```javascript
// Build from localized experts data (for report pages and list pages)
const expertsData = (typeof maneliInquiryLists !== 'undefined' && maneliInquiryLists.experts) 
                    ? maneliInquiryLists.experts 
                    : [];

if (expertsData.length === 0) {
    Swal.fire(getText('error'), getText('no_experts_available', 'هیچ کارشناسی یافت نشد.'), 'error');
    return;
}

// Always build the select from experts data to ensure consistency
let optionsHtml = `<option value="auto">${getText('auto_assign')}</option>`;
expertsData.forEach(expert => {
    optionsHtml += `<option value="${expert.id}">${expert.name}</option>`;
});
const expertOptionsHTML = `<select id="swal-expert-filter" class="swal2-input" ...>${optionsHtml}</select>`;
```

**مزایا**:
- کد ساده‌تر و قابل پیش‌بینی‌تر
- عدم وابستگی به وجود elementهای خاص در صفحه
- کار یکسان در لیست و گزارش

---

### 2. enqueue کردن Select2 (`includes/shortcodes/class-inquiry-lists-shortcode.php`)

**اضافه شده به متد `enqueue_admin_list_assets()`**:

```php
// Enqueue Select2 for expert assignment modal
wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0');
wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0', true);
```

**و اضافه کردن به dependencies**:

```php
wp_enqueue_script(
    'maneli-inquiry-lists-js',
    MANELI_INQUIRY_PLUGIN_URL . 'assets/js/frontend/inquiry-lists.js',
    ['jquery', 'sweetalert2', 'select2', 'maneli-jalali-datepicker'], // select2 اضافه شد
    '1.0.0',
    true
);
```

---

### 3. اضافه کردن stringهای localization

**در فایل PHP** (`includes/shortcodes/class-inquiry-lists-shortcode.php`):

```php
'text' => [
    // ... existing strings ...
    'no_experts_available' => esc_html__('No experts found for assignment.', 'maneli-car-inquiry'),
    'select_expert_required' => esc_html__('Please select an expert.', 'maneli-car-inquiry'),
]
```

**در فایل ترجمه** (`languages/maneli-car-inquiry-fa_IR.po`):

```
msgid "No experts found for assignment."
msgstr "هیچ کارشناسی برای تخصیص یافت نشد."

msgid "Please select an expert."
msgstr "لطفاً یک کارشناس انتخاب کنید."
```

**و کامپایل فایل `.mo`**:
```bash
msgfmt -o maneli-car-inquiry-fa_IR.mo maneli-car-inquiry-fa_IR.po
```

---

## فایل‌های تغییر یافته

1. ✅ `assets/js/frontend/inquiry-lists.js` - اصلاح handler دکمه تخصیص کارشناس
2. ✅ `includes/shortcodes/class-inquiry-lists-shortcode.php` - enqueue Select2 و اضافه stringها
3. ✅ `languages/maneli-car-inquiry-fa_IR.po` - اضافه ترجمه‌های جدید
4. ✅ `languages/maneli-car-inquiry-fa_IR.mo` - کامپایل مجدد

---

## تست

برای تست این اصلاحات:

1. به عنوان Admin یا Expert وارد سیستم شوید
2. به صفحه لیست درخواست‌های اقساطی بروید
3. روی یک درخواست کلیک کنید تا گزارش آن را ببینید
4. اگر کارشناس تخصیص داده نشده، دکمه "تخصیص کارشناس" را ببینید
5. روی دکمه کلیک کنید
6. modal انتخاب کارشناس باید به درستی نمایش داده شود
7. یک کارشناس انتخاب کنید یا گزینه "تخصیص خودکار" را انتخاب کنید
8. تایید کنید و بررسی کنید که تخصیص با موفقیت انجام شود

---

## نتیجه

✅ مشکل تخصیص کارشناس در گزارش درخواست اقساطی به طور کامل برطرف شد

✅ کد بهبود یافته و قابل نگهداری‌تر شد

✅ Select2 به درستی enqueue می‌شود

✅ تمام stringها localized هستند

