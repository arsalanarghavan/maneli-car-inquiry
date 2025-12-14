# 🎨 Elementor Integration Guide - صفحه اصلی مانلی

## ✨ Elementor-Only طراحی صفحه

تمام صفحه اصلی **کاملاً با Elementor** ساخته شده است!

---

## 🚀 شروع کار

### مرحله 1: فعال‌سازی Plugin
```
WordPress Admin → Plugins → Maneli Car Inquiry Core → Activate
```

### مرحله 2: نصب Elementor (اگر نصب نشده)
```
Plugins → Add New → Search "Elementor" → Install & Activate
```

### مرحله 3: صفحه اصلی جدید بسازید
```
Pages → Add New
├─ Title: "صفحه اصلی" (یا Home)
├─ Click: "Edit with Elementor"
└─ Save as Draft
```

### مرحله 4: صفحه را به عنوان Homepage تنظیم کنید
```
Settings → Reading
├─ Your homepage displays → "A static page"
├─ Homepage → Select your page
└─ Save Changes
```

---

## 🎯 Custom Widgets - Maneli Widgets

### **4 Widget سفارشی موجود:**

#### 1️⃣ **Maneli Header**
- ✅ Logo و branding
- ✅ Navigation menu
- ✅ Phone number و social links
- ✅ Search bar
- ✅ User account button

**استفاده:**
```
Elementor Editor → Add Element
└─ Search: "Maneli Header"
└─ Drag to section
└─ Customize settings
```

**تنظیمات قابل‌تغییر:**
- Logo image
- Logo text (فارسی و انگلیسی)
- Phone number
- Menu items (اضافه/حذف)
- Colors و styling

---

#### 2️⃣ **Maneli Hero Slider**
- ✅ Full-width slider
- ✅ Multiple slides
- ✅ Auto-play
- ✅ Pagination و navigation

**استفاده:**
```
Elementor Editor → Add Element
└─ Search: "Maneli Hero Slider"
└─ Add slides
```

**تنظیمات:**
- Slide image
- Slide title & subtitle
- Button text و URL
- Auto-play delay
- Height

---

#### 3️⃣ **Maneli Products**
- ✅ WooCommerce integration
- ✅ Product grid
- ✅ Category filters
- ✅ Responsive layout

**استفاده:**
```
Elementor Editor → Add Element
└─ Search: "Maneli Products"
└─ Configure grid
```

**تنظیمات:**
- Products per page
- Grid columns
- Show/hide filters
- Sort by (Date, Price, Rating)

---

#### 4️⃣ **Maneli Footer**
- ✅ 4-column footer
- ✅ About section
- ✅ Links
- ✅ Contact info
- ✅ Social media

**استفاده:**
```
Elementor Editor → Add Element
└─ Search: "Maneli Footer"
└─ Customize columns
```

**تنظیمات:**
- Column titles و content
- Links (Add/Edit/Delete)
- Contact info
- Social media links
- Copyright text

---

## 📝 مثال - صفحه سازی کامل

### Step by Step:

**1. Section 1 - Header**
```
Add Section → Add Container
  ├─ Add Widget: Maneli Header
  └─ Customize Logo, Menu, Phone
```

**2. Section 2 - Hero**
```
Add Section → Add Container
  ├─ Add Widget: Maneli Hero Slider
  ├─ Add 3 Slides
  ├─ Set images
  └─ Configure autoplay
```

**3. Section 3 - Features** (Elementor Default)
```
Add Section → Add Container
  ├─ Add Widget: Heading
  │  └─ Text: "چرا باید انتخاب کنیم؟"
  └─ Add Widget: Icon Box (x6)
     ├─ Title
     ├─ Icon
     └─ Description
```

**4. Section 4 - Products**
```
Add Section → Add Container
  ├─ Add Widget: Maneli Products
  ├─ Set Products Per Page: 12
  ├─ Columns: 4
  └─ Enable Filters
```

**5. Section 5 - Footer**
```
Add Section → Add Container
  ├─ Add Widget: Maneli Footer
  ├─ Fill in columns
  ├─ Add links
  └─ Add social media
```

---

## 🎨 Customization در Elementor

### رنگ‌ها تغییر دهید:

1. Elementor Editor میں عنصر انتخاب کنید
2. Style tab کلیک کنید
3. رنگ تغییر دهید
4. Update کنید

### متن تغییر دهید:

1. Widget double-click کنید
2. Content تغییر دهید
3. Save کنید

### Layout تغییر دهید:

1. Column width تغییر دهید
2. Spacing تغییر دهید
3. Responsive تنظیمات کنید

---

## 📱 Responsive Design

تمام widgets **خودکار** responsive هستند:

```
Desktop (≥1024px)   ✅ تمام المان‌ها
Tablet (768-1024px) ✅ Grid 2-3 column
Mobile (<768px)     ✅ Grid 1 column
```

### Responsive تنظیمات:

```
Elementor Editor → Advanced
├─ Responsive: Desktop / Tablet / Mobile
├─ Hide on: Select device
└─ Column: Change columns per device
```

---

## 🔧 Elementor Pro Features (Optional)

اگر **Elementor Pro** داشته باشید:

- ✅ Dynamic content
- ✅ Post loops
- ✅ Custom forms
- ✅ Advanced animations
- ✅ Theme builder

---

## 📚 Elementor Resources

- **Docs**: https://elementor.com/help/
- **Community**: https://elementor.com/community/
- **Blog**: https://elementor.com/blog/

---

## ⚠️ نکات مهم

### ✅ کار می‌کند:
- ✅ تمام widgets Elementor
- ✅ Elementor default widgets
- ✅ Third-party widgets
- ✅ Custom CSS/HTML

### ❌ توصیه نمی‌شود:
- ❌ Direct PHP editing
- ❌ Direct database changes
- ❌ Deactivating Elementor

---

## 🎯 Quick Tips

1. **Draft نگاه دارید**
   ```
   اگر مشکل پیش آید، revert کنید
   ```

2. **Template ذخیره کنید**
   ```
   Templates → Save as template
   Reuse دوباره استفاده کنید
   ```

3. **Mobile Preview**
   ```
   Elementor → Mobile icon
   Desktop/Tablet/Mobile preview
   ```

4. **Undo/Redo**
   ```
   Ctrl+Z (Undo)
   Ctrl+Y (Redo)
   ```

---

## 🎉 شروع کردید!

حالا می‌تونید:

1. **Pages → Elementor Editor** باز کنید
2. **Maneli Widgets** رو اضافه کنید
3. **Customize** کنید
4. **Publish** کنید

**لذت ببرید! 🚀**

---

**Version**: 1.0.0
**Last Updated**: December 14, 2025
**Status**: Elementor Ready ✅
