# 🚀 دستورالعمل آپلود و نصب ترجمه

## ⚠️ مشکل فعلی
همه متن‌های پلاگین انگلیسی هستند چون **فایل‌های ترجمه در سرور نیستند**.

---

## ✅ راه حل (خیلی ساده!)

### مرحله 1️⃣: دانلود فایل ZIP
فایل `maneli-translation-fix.zip` را دانلود کنید.

### مرحله 2️⃣: استخراج فایل
فایل ZIP را در کامپیوتر خود استخراج کنید. داخل آن این فایل‌ها هست:

```
📁 languages/
├── maneli-car-inquiry-fa_IR.mo  ← فایل اصلی ترجمه
├── maneli-car-inquiry-fa_IR.po
├── maneli-car-inquiry-fa.mo
└── maneli-car-inquiry-fa.po

📁 includes/
└── class-maneli-car-inquiry.php  ← فایل اصلاح شده

📄 test-translation.php  ← فایل تست
📄 README-TRANSLATION.md  ← راهنما
```

### مرحله 3️⃣: آپلود به سرور

**گزینه A - از طریق FTP/SFTP (پیشنهادی):**

1. به سرور متصل شوید
2. به مسیر پلاگین بروید:
   ```
   /wp-content/plugins/maneli-car-inquiry/
   ```
3. فایل‌ها را طبق ساختار زیر آپلود کنید:

```
📁 /wp-content/plugins/maneli-car-inquiry/
│
├── 📁 languages/
│   ├── maneli-car-inquiry-fa_IR.mo  ← آپلود کنید
│   ├── maneli-car-inquiry-fa_IR.po  ← آپلود کنید
│   ├── maneli-car-inquiry-fa.mo     ← آپلود کنید
│   └── maneli-car-inquiry-fa.po     ← آپلود کنید
│
├── 📁 includes/
│   └── class-maneli-car-inquiry.php ← جایگزین کنید (بکاپ بگیرید)
│
└── test-translation.php ← آپلود کنید (برای تست)
```

**گزینه B - از طریق cPanel File Manager:**

1. به cPanel بروید
2. File Manager را باز کنید
3. به مسیر بروید: `public_html/wp-content/plugins/maneli-car-inquiry/`
4. فایل‌ها را آپلود کنید

---

### مرحله 4️⃣: تست کنید

#### آ) تست خودکار:
به این آدرس بروید:
```
https://manelikhodro.com/wp-content/plugins/maneli-car-inquiry/test-translation.php
```

باید این موارد را ببینید:
- ✅ WordPress Locale: **fa_IR**
- ✅ Translation files exist: **YES**
- ✅ Translations working: **مشتری، خودرو، وضعیت** (نه Customer, Car, Status)

#### ب) تست دستی:
1. به پنل مدیریت وردپرس بروید
2. صفحات پلاگین را باز کنید
3. باید همه متن‌ها فارسی باشند!

---

### مرحله 5️⃣: پاک کردن کش

اگر هنوز انگلیسی است:

1. **کش پلاگین‌ها:**
   - اگر از WP Super Cache یا W3 Total Cache استفاده می‌کنید، کش را پاک کنید

2. **کش مرورگر:**
   - `Ctrl + Shift + Delete` (Windows)
   - `Cmd + Shift + Delete` (Mac)
   - یا از حالت Incognito تست کنید

3. **غیرفعال و فعال کردن پلاگین:**
   - بروید: افزونه‌ها > افزونه‌های نصب شده
   - پلاگین Maneli Car Inquiry را غیرفعال کنید
   - دوباره فعالش کنید

---

## 🎯 نکات مهم

### ✅ چک‌لیست قبل از آپلود:
- [ ] بکاپ از فایل `includes/class-maneli-car-inquiry.php` گرفتم
- [ ] مطمئنم که مسیر درست است: `/wp-content/plugins/maneli-car-inquiry/`
- [ ] فایل‌ها را در پوشه‌های درست آپلود کردم

### ⚠️ فایل مهم:
**مهم‌ترین فایل:** `languages/maneli-car-inquiry-fa_IR.mo`

اگر فقط یک فایل آپلود می‌کنید، این باشد!

### 🔍 بررسی مجوزها:
فایل‌های `.mo` و `.po` باید مجوز **644** داشته باشند:
```bash
chmod 644 languages/*.mo
chmod 644 languages/*.po
```

---

## ❌ مشکلات رایج

### مشکل: همچنان انگلیسی است
**راه‌حل:**
1. مطمئن شوید فایل `maneli-car-inquiry-fa_IR.mo` در پوشه `languages/` است
2. اندازه فایل را چک کنید (باید ~49 KB باشد)
3. کش را پاک کنید
4. فایل `test-translation.php` را چک کنید

### مشکل: test-translation.php ارور 404 می‌دهد
**راه‌حل:**
- فایل را در ریشه پلاگین آپلود کنید (کنار فایل اصلی `maneli-car-inquiry.php`)

### مشکل: خطای Permission Denied
**راه‌حل:**
```bash
chmod 755 wp-content/plugins/maneli-car-inquiry/languages/
chmod 644 wp-content/plugins/maneli-car-inquiry/languages/*.mo
```

---

## 📸 اسکرین‌شات مورد نیاز

اگر بعد از آپلود، مشکل حل نشد، این اطلاعات را ارسال کنید:

1. **اسکرین‌شات از:** `test-translation.php`
2. **لیست فایل‌ها در:** `languages/` directory
3. **تنظیمات زبان:** Settings > General > Site Language

---

## ✨ بعد از موفقیت

وقتی همه چیز فارسی شد:
- ✅ فایل `test-translation.php` را حذف کنید (امنیت)
- ✅ فایل‌های بکاپ قدیمی را پاک کنید
- ✅ لذت ببرید! 🎉

---

## 🆘 پشتیبانی

اگر مشکلی پیش آمد:
1. فایل `test-translation.php` را اجرا کنید
2. اسکرین‌شات بگیرید
3. با من تماس بگیرید

---

**موفق باشید! 🚀**

