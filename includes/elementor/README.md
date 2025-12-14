# صفحه اصلی مانلی - راهنمای نصب و استفاده

## 📋 فایل‌های ایجاد شده

### 1. **templates/elementor/home-page.php**
صفحه اصلی کامل با تمام بخش‌های لازم:

#### بخش‌های موجود:
- **Header**: هدر چسبان (Sticky) با منو، لوگو، جستجو، و دسترسی کاربر
- **Hero Slider**: اسلایدر تصاویر محصولات با easing effect و pagination
- **Features Grid**: 6 خصوصیت اصلی در قالب کارت‌های واکنش‌پذیر
- **Products Section**: محصولات WooCommerce با فیلتر دسته‌بندی
- **Buying Process**: 3 مرحله فرآیند خریداری با آیکون و انیمیشن
- **Statistics**: آمار و اعداد با انیمیشن هنگام scroll
- **Testimonials**: نقل قول‌های مشتریان از نظرات محصولات
- **Blog Section**: آخرین نوشته‌های وبلاگ
- **CTA Section**: بخش فراخوان برای تماس/عضویت
- **Footer**: فوتر 4 ستونه با لینک‌ها و اطلاعات تماس

### 2. **assets/css/elementor-home.css**
استایل‌شیت مدرن و واکنش‌پذیر:

#### ویژگی‌ها:
- متغیرهای CSS برای رنگ‌ها و سایز‌ها
- طراحی responsive برای تمام اندازه‌های صفحه
- انیمیشن‌های نرم برای hover و scroll
- پشتیبانی RTL کامل
- Grid و Flexbox برای layout
- Gradient backgrounds و shadow effects

#### Breakpoints:
- Desktop: ≥1024px
- Tablet: 768px - 1024px
- Mobile: < 768px
- Small Mobile: < 480px

### 3. **assets/js/elementor-home.js**
فانکشن‌های تعاملی:

#### Functions:
- `initializeSwiper()`: اسلایدر Hero با auto-play
- `initializeProductFilters()`: فیلتر محصولات با انیمیشن
- `initializeCounterAnimation()`: انیمیشن اعداد بر اساس scroll
- `initializeScrollAnimations()`: fade-in هنگام دیدن عنصر
- `initializeContactForm()`: ارسال فرم با AJAX
- `initializeMobileMenu()`: منوی موبایل
- `showNotification()`: نوتیفیکیشن toast

### 4. **includes/elementor/class-elementor-home.php**
کلاس اصلی برای راه‌اندازی:

#### وظایف:
- ثبت template سفارشی
- Loading assets (CSS/JS)
- Admin settings برای صفحه اصلی
- ایجاد صفحه اصلی خودکار

### 5. **includes/elementor/class-elementor-ajax-handler.php**
AJAX handlers برای عملیات‌های پویا:

#### AJAX Actions:
- `maneli_contact_form`: ارسال فرم تماس
- `maneli_filter_products`: فیلتر محصولات
- `maneli_subscribe_newsletter`: عضویت خبرنامه

---

## 🚀 نحوه نصب و فعال‌سازی

### مرحله 1: تایید نصب
```php
// فایل‌ها به‌طور خودکار load می‌شوند
// فقط دوباره plugin رو فعال کنید
```

### مرحله 2: تنظیمات صفحه اصلی
```bash
# در WordPress admin:
1. Settings > Reading
2. Homepage: Select your Home page
3. Posts page: Select a page for posts
```

### مرحله 3: تنظیمات Elementor (اختیاری)
```php
// سازگاری کامل با Elementor
// می‌توانید صفحه را در Elementor editor باز کنید و تغییر دهید
```

---

## 🎨 سفارشی‌سازی

### تغییر رنگ‌ها
```css
/* assets/css/elementor-home.css */
:root {
    --primary-color: #ef4444;      /* قرمز اصلی */
    --secondary-color: #1e293b;    /* آبی تیره */
    --accent-color: #f97316;       /* نارنجی */
    --light-bg: #f8fafc;           /* پس‌زمینه روشن */
    --text-dark: #1e293b;          /* متن تیره */
    --text-light: #64748b;         /* متن روشن */
}
```

### تغییر تصاویر و محتوا
```php
// templates/elementor/home-page.php
// تصاویر اسلایدر - اضافه کنید در:
$slides = [
    ['image' => 'URL', 'title' => 'عنوان'],
    // ...
];
```

### تغییر آمار
```php
// statistics section میں:
$stats = [
    ['number' => 20, 'label' => 'تیم متخصص'],
    ['number' => 5000, 'label' => 'سفارش موفق'],
    // ...
];
```

---

## 📱 واکنش‌پذیری

### Device Test Sizes:
```
- Desktop: 1920px, 1400px, 1024px
- Tablet: 768px, 810px
- Mobile: 480px, 375px
```

### Test Checklist:
- [ ] صفحه در تمام اندازه‌ها خوب نمایش می‌دهد
- [ ] متن‌ها قابل خوانندگی هستند
- [ ] تصاویر مناسب scale می‌شوند
- [ ] فرم‌ها کار می‌کنند
- [ ] منو موبایل کار می‌کند

---

## 🔧 فانکشن‌های اضافی

### Contact Form Handler
```javascript
// فرم تماس خودکار نام، ایمیل، شماره و پیام را ثبت می‌کند
// درخواست‌ها به CPT 'inquiry' ذخیره می‌شوند
// ایمیل تایید به مشتری ارسال می‌شود
```

### Product Filter
```javascript
// فیلتر محصولات بر اساس دسته‌بندی WooCommerce
// با انیمیشن fade-in/out
// Pagination خودکار
```

### Newsletter Subscription
```javascript
// عضویت در خبرنامه
// جلوگیری از تکراری‌سازی
// ذخیره سازی در database
```

---

## ⚡ بهینه‌سازی عملکرد

### پیشنهادهای بهینه‌سازی:
1. **تصاویر**: از WebP استفاده کنید
2. **Lazy Loading**: تصاویر را lazy load کنید
3. **CDN**: assets را از CDN بارگذاری کنید
4. **Caching**: صفحه را cache کنید

### اسکریپت‌ها:
```html
<!-- Swiper (11 KB minified) -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<!-- Font Awesome (32 KB) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
```

---

## 🌐 SEO Considerations

### Meta Tags (اضافه کنید به header):
```php
<meta name="description" content="صفحه اصلی فروش خودرو">
<meta name="keywords" content="خودرو, فروش, خرید">
<meta property="og:title" content="مانلی - فروش خودرو">
<meta property="og:description" content="بهترین خودروهای بازار">
```

### Schema Markup:
```json
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "مانلی",
  "url": "https://example.com",
  "logo": "https://example.com/logo.png"
}
```

---

## 🛠️ Troubleshooting

### مشکل: اسلایدر کار نمی‌کند
```javascript
// راه‌حل: Swiper رو load کنید
wp_enqueue_script('swiper', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js');
```

### مشکل: فرم تماس جواب نمی‌دهد
```php
// بررسی کنید:
1. Nonce صحیح باشد
2. jQuery load شده باشد
3. AJAX URL درست باشد
```

### مشکل: پایین‌ترین نسخه PHP
```php
// نیازمند: PHP 8.0+
// برای نسخه‌های قدیمی‌تر، کد رو اپدیت کنید
```

---

## 📞 پشتیبانی

برای کمک یا پیشنهادات:
- Issues: باز کنید در GitHub
- Email: support@example.com
- Documentation: docs.example.com

---

## 📄 License

تمام فایل‌ها تحت GPL v2 است.

---

## 🔄 نسخه‌های بعدی

### آینده:
- [ ] پشتیبانی Elementor Pro widgets
- [ ] Dark mode
- [ ] Multi-language support
- [ ] Advanced analytics
- [ ] AI-powered recommendations

---

**آخرین بروزرسانی**: جانوری 2025
**نسخه**: 1.0.0
