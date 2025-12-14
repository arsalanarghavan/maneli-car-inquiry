# 🚀 Quick Start Guide - صفحه اصلی مانلی

## ⏱️ 5 دقیقه برای شروع

### مرحله 1: فعال‌سازی Plugin (1 دقیقه)
```
1. WordPress Admin → Plugins → Installed Plugins
2. "Maneli Car Inquiry Core" → Click Activate
3. صبر کنید تا plugin load شود
```

### مرحله 2: تنظیم صفحه اصلی (2 دقیقه)
```
1. Settings → Reading
2. "Your homepage displays" → Select "A static page"
3. "Homepage" → Select "صفحه اصلی" (یا "Home")
4. Save Changes
```

### مرحله 3: بازدید صفحه اصلی (1 دقیقه)
```
1. مرورگر: Visit your.domain.com
2. صفحه اصلی جدید باید نمایش داده شود
3. بررسی تمام بخش‌ها
```

### مرحله 4: تست فرم (1 دقیقه)
```
1. پایین صفحه → "تماس با ما" section
2. فرم را پر کنید
3. Submit کنید
4. باید "موفق" پیام دریافت کنید
```

---

## ✅ Quick Checklist

- [ ] Plugin فعال است
- [ ] صفحه اصلی تنظیم شده
- [ ] صفحه درست نمایش داده می‌شود
- [ ] فرم کار می‌کند
- [ ] Mobile نمایش درست است

---

## 🎨 سفارشی‌سازی سریع

### عکس Slider:
```
1. Media Library میں تصاویر اپ‌لود کنید
2. templates/elementor/home-page.php باز کنید
3. Slider section میں URL‌ها تغییر دهید
4. Save کنید
```

### رنگ‌های سایت:
```
1. assets/css/elementor-home.css باز کنید
2. خط 1-10 (CSS Variables)
3. رنگ‌ها تغییر دهید (مثال: #ef4444 → #your-color)
4. Save کنید - فوری تغییر خواهد یافت
```

### متن و عناوین:
```
1. templates/elementor/home-page.php باز کنید
2. جایی را یافتید که می‌خواهید تغییر دهید
3. متن را update کنید
4. Save کنید
```

---

## 🔍 Troubleshooting سریع

### مشکل: Slider کار نمی‌کند
```
✓ F12 (Developer Tools) بازکنید
✓ Console tab
✓ خطا وجود دارد؟ بگویید
✓ اگر "Swiper is not defined":
  - Swiper library load نشده
  - class-elementor-home.php میں 
    wp_enqueue_script('swiper', ...) وجود دارد
```

### مشکل: فرم submit نمی‌شود
```
✓ Network tab (F12)
✓ AJAX request موفق؟
✓ Response error دارد؟
✓ اگر "Undefined nonce":
  - maneliHome object وجود ندارد
  - wp_localize_script issue
```

### مشکل: صفحه خالی است
```
✓ Debug.log بررسی کنید: /wp-content/debug.log
✓ Plugin error دارد؟
✓ WordPress version compatible؟
✓ WooCommerce فعال است؟
```

---

## 📱 Test کردن on Mobile

### Browser Mobile Testing:
```
Chrome: Ctrl+Shift+M (Windows/Linux) یا Cmd+Shift+M (Mac)
Firefox: Ctrl+Shift+M یا Cmd+Shift+M
Safari: Develop > Enter Responsive Design Mode
```

### Real Device Testing:
```
1. Phone رو شبکه‌ای device وصل کنید
2. IP address سرور بنویسید
3. Mobile browser میں: http://your-ip:port
4. Test کنید
```

---

## 🎯 Next Steps

### بعد از تایید:
1. **Add Real Content**:
   - تصاویر بهتر اپ‌لود کنید
   - توضیحات product کامل کنید
   - Blog post‌های اضافی بنویسید
   - Testimonial‌های واقعی اضافه کنید

2. **Optimize Performance**:
   - Image optimization
   - Caching plugin نصب کنید
   - CDN تنظیم کنید

3. **Improve SEO**:
   - Yoast SEO install کنید
   - Meta descriptions اضافه کنید
   - Schema markup add کنید

4. **Monitor Analytics**:
   - Google Analytics setup کنید
   - Track user behavior
   - Monitor conversion rates

---

## 🆘 Help & Support

### اگر help لازم دارید:

**1. چک کنید خودکار:**
- `/wp-content/debug.log` برای PHP errors
- Browser Console (F12) برای JS errors
- Network tab برای AJAX requests

**2. Resources:**
- Plugin README: `/includes/elementor/README.md`
- Deployment Guide: `/DEPLOYMENT_GUIDE.md`
- Completion Summary: `/COMPLETION_SUMMARY.md`

**3. Contact:**
- Check WordPress.org forums
- Check Elementor community
- Email to: [support email]

---

## 🎓 Learning More

### Wanna understand the code?

**PHP/WordPress:**
- Course: WP Plugin Development
- Read: WordPress.org/plugins/developers

**JavaScript:**
- Learn: MDN JavaScript Guide
- Practice: Swiper documentation

**CSS:**
- Course: CSS Complete Guide
- Reference: CSS-Tricks

---

## 💡 Pro Tips

### 1. Use Developer Mode:
```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
// Check /wp-content/debug.log
```

### 2. Test AJAX Manually:
```javascript
// In browser console:
fetch(maneliHome.ajaxUrl, {
    method: 'POST',
    body: new URLSearchParams({
        action: 'maneli_contact_form',
        nonce: maneliHome.nonce,
        name: 'Test User',
        email: 'test@example.com',
        phone: '09123456789',
        message: 'Test message'
    })
})
.then(r => r.json())
.then(d => console.log(d))
```

### 3. Check Performance:
```
Right-click > Inspect > Network tab
- Check CSS/JS files load time
- Check image load time
- Monitor XHR/AJAX requests
```

### 4. Test on Different Networks:
- Desktop WiFi
- Mobile 4G
- Slow 3G (Chrome DevTools > Throttle)

---

## ⚠️ Important Notes

### Critical:
- ✅ PHP 8.0+ required
- ✅ WordPress 6.0+ recommended
- ✅ WooCommerce must be active
- ✅ Elementor compatible (no Pro required)

### Backup:
- Always backup before major changes
- Use UpdraftPlus or similar
- Keep version control (Git)

### Testing:
- Test on staging before production
- Test all browsers
- Test all devices
- Monitor logs after deploy

---

## 📝 Useful WordPress Commands

### Via SSH/Terminal:
```bash
# List installed plugins
wp plugin list

# Activate plugin
wp plugin activate maneli-car-inquiry

# Check WordPress version
wp core version

# List posts
wp post list --post_type=product

# Search in database
wp db query "SELECT * FROM wp_posts WHERE post_type='page';"
```

---

## 🔐 Security Reminders

- ✅ Never share database credentials
- ✅ Never execute untrusted code
- ✅ Always validate inputs
- ✅ Always escape outputs
- ✅ Keep plugins updated
- ✅ Use HTTPS only
- ✅ Strong admin password

---

## 📊 Performance Targets

### Aim for:
- Page Load: < 3 seconds
- Largest Paint: < 2.5s
- Speed Index: < 4s
- Memory: < 60 MB

### Monitor with:
- Google PageSpeed Insights
- GTmetrix
- Pingdom
- WebPageTest

---

## 🎉 You're Done!

Congratulations! صفحه اصلی شما آماده است.

```
╔════════════════════════════════════╗
║  صفحه اصلی مانلی - شروع شد!        ║
║                                    ║
║  ✅ Installation: Complete         ║
║  ✅ Configuration: Ready           ║
║  ✅ Testing: Passed                ║
║  ✅ Deployment: Ready              ║
║                                    ║
║  Enjoy your new homepage!          ║
╚════════════════════════════════════╝
```

---

**Version**: 1.0.0
**Created**: January 2025
**Status**: ✅ Ready to Use
**Last Updated**: Today

---

### حالا به صفحه اصلی جدید بروید و لذت ببرید! 🎉
