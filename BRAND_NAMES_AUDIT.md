# Brand Names Audit Report
## Maneli Car Inquiry Plugin

**Date:** December 15, 2025  
**Plugin:** Maneli Car Inquiry Core  
**Version:** 1.1.0  
**Text Domain:** `maneli-car-inquiry`

---

## Executive Summary

This document provides a comprehensive inventory of all occurrences of brand names and identifiers throughout the Maneli Car Inquiry plugin codebase. The search covers:

- **"Maneli"** (English brand name)
- **"مانلی"** (Persian brand name)
- **"maneli-car-inquiry"** (Plugin slug/text domain)
- **"maneli_"** (PHP prefix for classes, functions, capabilities, and database keys)

---

## 1. PHP CLASS AND FUNCTION NAMES

These are the core API classes and functions that developers interact with:

### Main Plugin Class

| File | Line | Type | Name | Purpose |
|------|------|------|------|---------|
| `includes/class-maneli-car-inquiry.php` | 16 | Class | `Maneli_Car_Inquiry_Plugin` | Main plugin orchestrator |

### Helper Classes

| File | Line | Type | Name | Purpose |
|------|------|------|------|---------|
| `includes/helpers/class-maneli-options-helper.php` | 16 | Class | `Maneli_Options_Helper` | Settings management |
| `includes/helpers/class-maneli-encryption-helper.php` | 17 | Class | `Maneli_Encryption_Helper` | Data encryption/decryption |
| `includes/helpers/class-maneli-render-helpers.php` | 21 | Class | `Maneli_Render_Helpers` | Template rendering utilities |
| `includes/helpers/class-maneli-permission-helpers.php` | 17 | Class | `Maneli_Permission_Helpers` | Access control checks |
| `includes/helpers/class-maneli-captcha-helper.php` | 19 | Class | `Maneli_Captcha_Helper` | CAPTCHA integration |

### Core Handler Classes

| File | Line | Type | Name | Purpose |
|------|------|------|------|---------|
| `includes/class-maneli-database.php` | 12 | Class | `Maneli_Database` | Database operations |
| `includes/class-maneli-session.php` | 12 | Class | `Maneli_Session` | Session management |
| `includes/class-maneli-auth.php` | 12 | Class | `Maneli_Auth` | Authentication |
| `includes/class-maneli-loader.php` | 12 | Class | `Maneli_Loader` | Asset loading |
| `includes/class-maneli-logger.php` | 12 | Class | `Maneli_Logger` | Logging system |
| `includes/class-maneli-activator.php` | 12 | Class | `Maneli_Activator` | Plugin activation |
| `includes/class-maneli-deactivator.php` | 12 | Class | `Maneli_Deactivator` | Plugin deactivation |
| `includes/class-maneli-license.php` | 14 | Class | `Maneli_License` | License management |

### Feature Classes

| File | Line | Type | Name | Purpose |
|------|------|------|------|---------|
| `includes/class-form-handler.php` | 15 | Class | `Maneli_Form_Handler` | Form submission handling |
| `includes/class-email-handler.php` | 14 | Class | `Maneli_Email_Handler` | Email notifications |
| `includes/class-sms-handler.php` | 14 | Class | `Maneli_SMS_Handler` | SMS notifications |
| `includes/class-telegram-handler.php` | 14 | Class | `Maneli_Telegram_Handler` | Telegram notifications |
| `includes/class-shortcode-handler.php` | 15 | Class | `Maneli_Shortcode_Handler` | Shortcode processing |
| `includes/class-notification-handler.php` | 14 | Class | `Maneli_Notification_Handler` | In-app notifications |
| `includes/class-notification-center-handler.php` | 14 | Class | `Maneli_Notification_Center_Handler` | Notification center |

### Inquiry & Product Classes

| File | Line | Type | Name | Purpose |
|------|------|------|------|---------|
| `includes/public/class-installment-inquiry-handler.php` | 15 | Class | `Maneli_Installment_Inquiry_Handler` | Installment inquiry processing |
| `includes/public/class-cash-inquiry-handler.php` | 14 | Class | `Maneli_Cash_Inquiry_Handler` | Cash inquiry processing |
| `includes/class-cpt-handler.php` | 17 | Class | `Maneli_CPT_Handler` | Custom post types |
| `includes/class-product-editor-page.php` | 14 | Class | `Maneli_Product_Editor_Page` | Product management |
| `includes/class-grouped-attributes.php` | 15 | Class | `Maneli_Grouped_Attributes` | Product attributes |

### Dashboard & Reporting Classes

| File | Line | Type | Name | Purpose |
|------|------|------|------|---------|
| `includes/class-dashboard-handler.php` | 16 | Class | `Maneli_Dashboard_Handler` | Dashboard functionality |
| `includes/class-reports-dashboard.php` | 10 | Class | `Maneli_Reports_Dashboard` | Reports & statistics |
| `includes/class-visitor-statistics.php` | 13 | Class | `Maneli_Visitor_Statistics` | Analytics tracking |
| `includes/class-credit-report-page.php` | 16 | Class | `Maneli_Credit_Report_Page` | Credit report display |
| `includes/admin/class-reports-page.php` | 10 | Class | `Maneli_Reports_Page` | Report pages |
| `includes/admin/class-status-migration.php` | 13 | Class | `Maneli_Status_Migration` | Database migration |

### Admin & Settings Classes

| File | Line | Type | Name | Purpose |
|------|------|------|------|---------|
| `includes/class-settings-page.php` | 17 | Class | `Maneli_Settings_Page` | Settings management |
| `includes/class-roles-caps.php` | 14 | Class | `Maneli_Roles_Caps` | User roles & capabilities |
| `includes/class-admin-dashboard-widgets.php` | 15 | Class | `Maneli_Admin_Dashboard_Widgets` | Admin dashboard widgets |
| `includes/class-expert-panel.php` | 15 | Class | `Maneli_Expert_Panel` | Expert tools |
| `includes/class-user-profile.php` | 14 | Class | `Maneli_User_Profile` | User profile management |
| `includes/class-frontend-theme-handler.php` | 16 | Class | `Maneli_Frontend_Theme_Handler` | Frontend theme |
| `includes/class-hooks.php` | 15 | Class | `Maneli_Hooks` | WordPress hooks |

### API Classes

| File | Line | Type | Name | Purpose |
|------|------|------|------|---------|
| `includes/public/class-finnotech-api-handler.php` | 14 | Class | `Maneli_Finnotech_API_Handler` | Finnotech API integration |
| `includes/public/class-payment-handler.php` | 16 | Class | `Maneli_Payment_Handler` | Payment gateway integration |

### Demo Data Classes

| File | Line | Type | Name | Purpose |
|------|------|------|------|---------|
| `includes/class-demo-data-generator.php` | 12 | Class | `Maneli_Demo_Data_Generator` | Demo data creation |
| `includes/class-demo-data-handler.php` | 11 | Class | `Maneli_Demo_Data_Handler` | Demo data management |

### Shortcode Classes

| File | Line | Type | Name | Purpose |
|------|------|------|------|---------|
| `includes/shortcodes/class-loan-calculator-shortcode.php` | 15 | Class | `Maneli_Loan_Calculator_Shortcode` | Loan calculator widget |

### Global Helper Functions

| File | Line | Type | Name | Purpose |
|------|------|------|------|---------|
| `includes/functions.php` | 24 | Function | `maneli_gregorian_to_jalali()` | Date conversion |
| `includes/functions.php` | 93 | Function | `maneli_convert_to_english_digits()` | Number conversion |
| `includes/functions.php` | 110 | Function | `maneli_normalize_jalali_date()` | Date normalization |
| `includes/functions.php` | 149 | Function | `maneli_get_current_url()` | URL handling |
| `includes/functions.php` | 192 | Function | `maneli_get_template_part()` | Template loading |
| `includes/functions.php` | 228 | Function | `maneli_number_format_persian()` | Number formatting |
| `includes/functions.php` | 298 | Function | `maneli_should_use_persian_digits()` | Locale detection |
| `includes/functions.php` | 326 | Function | `maneli_enqueue_persian_datepicker()` | Date picker |

### Template JavaScript Functions

| File | Line | Type | Name | Purpose |
|------|------|------|------|---------|
| `templates/login.php` | 444 | Function | `maneli_show_alert()` | Alert display |
| `templates/login.php` | 466 | Function | `maneli_go_back_to_phone()` | Login flow |
| `templates/login.php` | 472 | Function | `maneli_send_otp()` | OTP sending |
| `templates/login.php` | 572 | Function | `maneli_verify_otp()` | OTP verification |
| `templates/login.php` | 683 | Function | `maneli_password_login()` | Password login |
| `templates/dashboard-scripts-inject.php` | 4 | Function | `maneli_inject_inquiry_scripts()` | Script injection |

---

## 2. PLUGIN TEXT STRINGS AND MESSAGES

All user-facing strings are translatable using the `maneli-car-inquiry` text domain.

### Page Titles

| File | Line | Type | String | Context |
|------|------|------|--------|---------|
| `templates/create-password.php` | 36 | Page Title | "Create Password - Maneli Car Inquiry" | User account setup |
| `templates/verify-otp.php` | 41 | Page Title | "Verify Code - Maneli Car Inquiry" | OTP verification |
| `languages/maneli-car-inquiry.pot` | 740 | Translation | "Create Password - Maneli Car Inquiry" | Password creation |
| `languages/maneli-car-inquiry.pot` | 1763 | Translation | "Login - Maneli Car Inquiry" | Login page |
| `languages/maneli-car-inquiry.pot` | 3366 | Translation | "Verify Code - Maneli Car Inquiry" | Code verification |

### Role Names (User-Facing)

| String | Domain | Fallback (English) | Translation (Persian) |
|--------|--------|-------------------|----------------------|
| "Maneli Expert" | `maneli-car-inquiry` | Maneli Expert | کارشناس مانلی |
| "Maneli Manager" | `maneli-car-inquiry` | Maneli Manager | مدیر مانلی |
| "Maneli Admin" | `maneli-car-inquiry` | Maneli Admin | مدیر مانلی |
| "Maneli Experts" | `maneli-car-inquiry` | Maneli Experts | کارشناسان مانلی |
| "Customer" | `maneli-car-inquiry` | Customer | (N/A) |
| "General Manager" | `maneli-car-inquiry` | General Manager | (N/A) |

### System & Configuration Messages

| String | File | Line | Context |
|--------|------|------|---------|
| "Maneli Car Inquiry plugin requires WooCommerce to be installed and activated. Please activate WooCommerce to continue." | `languages/maneli-car-inquiry-fa_IR.po` | 20-21 | WooCommerce dependency |
| "Configure all aspects of the Maneli Car Inquiry plugin from this centralized settings panel." | `languages/maneli-car-inquiry-fa_IR.po` | 2699-2700 | Settings description |
| "Maneli Car Inquiry Settings" | `languages/maneli-car-inquiry.pot` | 1799 | Settings page title |
| "Maneli Car System Settings" | `languages/maneli-car-inquiry-en_US.po` | 2374 | System settings |
| "Maneli verification code: %s" | `languages/maneli-car-inquiry.pot` | 1843 | SMS verification message |

### Inquiry & Dashboard Messages

| String | File | Context |
|--------|------|---------|
| "Security check failed" | `includes/class-demo-data-handler.php` | 25 & 77 | Permission validation |
| "You do not have permission to perform this action" | `includes/class-demo-data-handler.php` | 32 & 84 | Authorization |
| "Demo data imported successfully! Created: %d customers, %d experts, %d cars, %d cash inquiries, %d installment inquiries" | `includes/class-demo-data-handler.php` | 47 | Success message |
| "Error importing demo data: %s" | `includes/class-demo-data-handler.php` | 63 | Error message |
| "Demo data deleted successfully! Deleted: %d customers, %d experts, %d cars, %d inquiries" | `includes/class-demo-data-handler.php` | 99 | Deletion confirmation |
| "Error deleting demo data: %s" | `includes/class-demo-data-handler.php` | 114 | Error message |

### Additional translatable text strings

**Location:** `languages/maneli-car-inquiry-en_US.po` and other .po files

Key translatable strings include:
- Form labels and placeholders
- Dashboard navigation items
- Error and success messages
- Modal dialog titles and buttons
- Report titles and statistics labels
- Inquiry status labels
- Payment method names
- Calendar and date-related text
- Expert and manager interface text

---

## 3. DATABASE META KEYS AND OPTION NAMES

These are used to store data in WordPress database:

### Main Options

| Option Key | File(s) | Usage | Type |
|------------|---------|-------|------|
| `maneli_inquiry_all_options` | Multiple templates | Plugin settings storage | Option name |

### Post Meta Keys (Inquiry Metadata)

| Meta Key | File(s) | Usage | Type |
|----------|---------|-------|------|
| `maneli_inquiry_total_price` | `class-demo-data-generator.php`, inquiry templates | Total inquiry amount | Post meta |
| `maneli_inquiry_down_payment` | `class-demo-data-generator.php`, inquiry templates | Down payment amount | Post meta |
| `maneli_inquiry_term_months` | `class-demo-data-generator.php`, inquiry templates | Loan term in months | Post meta |
| `maneli_inquiry_installment` | `class-demo-data-generator.php`, inquiry templates | Monthly installment amount | Post meta |

### User Meta Keys

| Meta Key | File(s) | Usage | Type |
|----------|---------|-------|------|
| `maneli_inquiry_down_payment` | `inquiry-form-wizard.php` | Stored user preference | User meta |
| `maneli_inquiry_term_months` | `inquiry-form-wizard.php` | Stored user preference | User meta |
| `maneli_inquiry_total_price` | `inquiry-form-wizard.php` | Stored user preference | User meta |
| `maneli_inquiry_installment` | `inquiry-form-wizard.php` | Stored user preference | User meta |

---

## 4. ROLE AND CAPABILITY NAMES

These are WordPress user roles and capabilities used for access control:

### Custom Roles

| Role Slug | Translatable Name | File | Line | Capabilities |
|-----------|-------------------|------|------|--------------|
| `maneli_admin` | "Maneli Manager" | `class-roles-caps.php` | 67 | See table below |
| `maneli_expert` | "Maneli Expert" | `class-roles-caps.php` | 74 | See table below |

### Master Capability

| Capability | Role | File | Line | Usage |
|------------|------|------|------|-------|
| `manage_maneli_inquiries` | administrator, maneli_admin | `class-roles-caps.php` | 29, 46, 57 | Master permission for all plugin functions |

### Role-Specific Capabilities

**Maneli Admin (maneli_admin):**
- `read` - Can access dashboard
- `manage_maneli_inquiries` - Master capability
- `edit_posts` - Can edit inquiries
- `delete_users` - Can delete users from frontend
- `edit_product` - Can edit products
- `read_product` - Can view products
- `edit_products` - Can manage products
- `edit_others_products` - Can edit other's products

**Maneli Expert (maneli_expert):**
- `read` - Can access dashboard
- `edit_posts` - Can edit assigned inquiries
- `edit_product` - Can edit products
- `read_product` - Can view products
- `edit_products` - Can manage products
- `edit_others_products` - Can edit other's products

### Capability Usage in Code

| Capability Check | File | Line | Purpose |
|------------------|------|------|---------|
| `current_user_can('manage_maneli_inquiries')` | Multiple templates | Various | Permission gate for admin functions |
| `in_array('maneli_expert', wp_get_current_user()->roles)` | Multiple templates | Various | Expert-specific features |
| `in_array('maneli_manager', wp_get_current_user()->roles)` | `dashboard/user-logs.php` | 19 | Manager-specific features |

---

## 5. ADMIN AND DASHBOARD TEXT LABELS

These are labels displayed in the admin interface and dashboard:

### Dashboard Page Titles & Labels

| String | File | Line | Context |
|--------|------|------|---------|
| "My Account" | `templates/dashboard.php` | 795 | Profile dropdown menu |
| "Logout" | `templates/dashboard.php` | 796 | Exit dashboard |
| "Alerts" | `templates/dashboard.php` | 115, 909 | Notification center |
| "Hide Sidebar" | `templates/dashboard.php` | 111 | Navigation toggle |
| "Search..." | `templates/dashboard.php` | 112 | Search placeholder |
| "Persian" | `templates/dashboard.php` | 113 | Language option |
| "English" | `templates/dashboard.php` | 114 | Language option |

### Admin Settings UI

| String | File | Line | Context |
|--------|------|------|---------|
| "Maneli Experts" | `admin-settings.php` | 64, 69 | Section title |
| "Expert:" | `system-report.php` | 62 | Report filter |
| "All Experts" | `system-report.php` | 65 | Filter option |

### Form Labels

| String | File | Line | Context |
|--------|------|------|---------|
| "Maneli Expert" | `user-management/user-list.php` | 74 | Role dropdown |
| "Maneli Expert" | `user-management/form-edit-user.php` | 103 | Role assignment |

### Meta Tags in HTML

| Meta Name | Content | File | Line |
|-----------|---------|------|------|
| `Description` | "داشبورد مدیریتی مانلی خودرو" | `dashboard-base.html` | 10 |
| `Author` | "داشبورد مدیریتی مانلی خودرو" | `dashboard-base.html` | 11 |
| `keywords` | "داشبورد مدیریتی مانلی خودرو" | `dashboard-base.html` | 12 |

### Page Titles

| Title | File | Line | Purpose |
|-------|------|------|---------|
| "داشبورد مدیریتی مانلی خودرو" | `dashboard-base.html` | 15 | HTML page title |
| "مانلی خودرو - داشبورد سیستم استعلام خودرو" | `dashboard.php` | 4 | Dashboard header comment |
| "مانلی خودرو - تنظیمات پروفایل" | `dashboard/profile-settings.php` | 4 | Profile page comment |

---

## 6. COMMENTS AND DOCUMENTATION

References to brand name in code comments and documentation:

### PHP File Headers

| File | Line | Comment | Type |
|------|------|---------|------|
| `class-maneli-car-inquiry.php` | 1-8 | "The core plugin class that orchestrates the entire Maneli Car Inquiry plugin." | Class docblock |
| `class-settings-page.php` | 1-6 | "Creates and manages the plugin's settings" | Class docblock |
| `helpers/class-maneli-render-helpers.php` | 1-6 | "Helper class for rendering various HTML elements and data in Maneli Car Inquiry plugin." | Class docblock |
| `class-demo-data-generator.php` | N/A | Special meta keys for installment inquiry display (Maneli Wizard format) | Code comment |
| `dashboard/reports.php` | 83 | "آمار کامل کسب و کار (فقط برای ادمین و مدیر مانلی)" | Code comment |
| `dashboard/products.php` | 41 | "Note: The hooks check for is_admin() OR current_user_can('manage_maneli_inquiries')" | Code comment |
| `dashboard/user-detail.php` | 60 | "Additional security: Users can only view their own profile unless they have manage_maneli_inquiries capability" | Code comment |

### Documentation Files

| File | Content | Purpose |
|------|---------|---------|
| `README.md` | References to "Maneli Car Inquiry Core" throughout | Plugin documentation |
| `MANIFEST.md` | "# 📦 Maneli Homepage - Project Manifest" | Project manifest |
| `ELEMENTOR_GUIDE.md` | "صفحة اصلی مانلی" and "Maneli Widgets" | Elementor integration guide |
| `QUICK_START.md` | "# 🚀 Quick Start Guide - صفحه اصلی مانلی" | Quick start guide |
| `DEPLOYMENT_GUIDE.md` | "wp_ajax: maneli_" actions listed | Deployment reference |

---

## 7. AJAX ACTIONS (WordPress AJAX Handlers)

These are WordPress AJAX action names for frontend-backend communication:

### Authentication AJAX Actions

| Action Name | File | Line | Purpose |
|------------|------|------|---------|
| `maneli_dashboard_login` | `class-dashboard-handler.php` | 57 | User login |
| `maneli_dashboard_logout` | `class-dashboard-handler.php` | 59 | User logout |
| `maneli_send_sms_code` | `class-dashboard-handler.php` | 60 | Send SMS code |
| `maneli_send_otp` | `class-dashboard-handler.php` | 64 | Send OTP |
| `maneli_verify_otp` | `class-dashboard-handler.php` | 66 | Verify OTP |
| `maneli_create_password` | `class-dashboard-handler.php` | 68 | Create password |
| `maneli_password_login` | `class-dashboard-handler.php` | 70 | Password-based login |

### Inquiry AJAX Actions

| Action Name | File | Line | Purpose |
|------------|------|------|---------|
| `maneli_select_car_ajax` | `class-installment-inquiry-handler.php` | 25 | Car selection |
| `maneli_confirm_car_catalog` | `class-installment-inquiry-handler.php` | 46 | Confirm car |
| `maneli_get_meeting_slots` | `class-installment-inquiry-handler.php` | 50 | Get meeting times |
| `maneli_book_meeting` | `class-installment-inquiry-handler.php` | 51 | Book meeting |
| `maneli_create_customer_cash_inquiry` | `class-cash-inquiry-handler.php` | 28 | Create cash inquiry |

### Demo Data AJAX Actions

| Action Name | File | Line | Purpose |
|------------|------|------|---------|
| `maneli_import_demo_data` | `class-demo-data-handler.php` | 14 | Import demo data |
| `maneli_delete_demo_data` | `class-demo-data-handler.php` | 15 | Delete demo data |

### Expert Panel AJAX Actions

| Action Name | File | Line | Purpose |
|------------|------|------|---------|
| `maneli_search_cars` | `class-expert-panel.php` | 18 | Search cars |

### License AJAX Actions

| Action Name | File | Line | Purpose |
|------------|------|------|---------|
| `maneli_license_webhook` | `class-maneli-license.php` | 86-87 | License webhook |

### Other AJAX Actions (from documentation)

| Action Name | File | Purpose |
|------------|------|---------|
| `maneli_contact_form` | DEPLOYMENT_GUIDE.md | Contact form submission |
| `maneli_filter_products` | DEPLOYMENT_GUIDE.md | Product filtering |
| `maneli_subscribe_newsletter` | DEPLOYMENT_GUIDE.md | Newsletter subscription |

---

## 8. PLUGIN CONSTANTS

Global PHP constants defined for plugin functionality:

### Plugin Identification

| Constant | Value | File | Line | Purpose |
|----------|-------|------|------|---------|
| `MANELI_INQUIRY_PLUGIN_FILE` | `__FILE__` | `maneli-car-inquiry.php` | 38 | Plugin main file |
| `MANELI_INQUIRY_PLUGIN_PATH` | `plugin_dir_path(__FILE__)` | `maneli-car-inquiry.php` | 39 | Plugin directory |
| `MANELI_INQUIRY_PLUGIN_DIR` | `plugin_dir_path(__FILE__)` | `maneli-car-inquiry.php` | 40 | Plugin directory alias |
| `MANELI_INQUIRY_PLUGIN_URL` | `plugin_dir_url(__FILE__)` | `maneli-car-inquiry.php` | 41 | Plugin URL |

### Legacy/Compatibility Constants

| Constant | Maps To | File | Line | Purpose |
|----------|---------|------|------|---------|
| `MANELI_PLUGIN_PATH` | `MANELI_INQUIRY_PLUGIN_PATH` | `maneli-car-inquiry.php` | 46 | Backward compatibility |
| `MANELI_PLUGIN_DIR` | `MANELI_INQUIRY_PLUGIN_DIR` | `maneli-car-inquiry.php` | 49 | Backward compatibility |
| `MANELI_PLUGIN_URL` | `MANELI_INQUIRY_PLUGIN_URL` | `maneli-car-inquiry.php` | 52 | Backward compatibility |
| `MANELI_INQUIRY_URL` | `MANELI_INQUIRY_PLUGIN_URL` | `maneli-car-inquiry.php` | 57 | Backward compatibility |
| `MANELI_INQUIRY_DIR` | `MANELI_INQUIRY_PLUGIN_DIR` | `maneli-car-inquiry.php` | 60 | Backward compatibility |

### Version Constants

| Constant | Value | File | Line | Purpose |
|----------|-------|------|------|---------|
| `MANELI_VERSION` | `'0.2.20'` | `maneli-car-inquiry.php` | 63 | Plugin version |
| `MANELI_INQUIRY_VERSION` | `MANELI_VERSION` | `maneli-car-inquiry.php` | 65 | Version alias |
| `MANELI_DB_VERSION` | `'1.1.0'` | `maneli-car-inquiry.php` | 66 | Database schema version |

### External API Endpoints

| Constant | URL | Purpose |
|----------|-----|---------|
| `MANELI_FINOTEX_API_URL` | `https://api.finnotech.ir/credit/v2/clients/%s/chequeColorInquiry` | Cheque color inquiry |
| `MANELI_FINNOTECH_CREDIT_RISK_API_URL` | `https://api.finnotech.ir/kyc/v2/clients/%s/users/%s/transactionCreditReport` | Credit risk report |
| `MANELI_FINNOTECH_CREDIT_SCORE_API_URL` | `https://api.finnotech.ir/kyc/v2/clients/%s/users/%s/transactionCreditInquiryReport` | Credit score |
| `MANELI_FINNOTECH_COLLATERALS_API_URL` | `https://api.finnotech.ir/credit/v2/clients/%s/users/%s/guaranteeCollaterals` | Collateral info |
| `MANELI_FINNOTECH_CHEQUE_COLOR_API_URL` | `https://api.finnotech.ir/credit/v2/clients/%s/chequeColorInquiry` | Cheque status |

### SMS API Endpoints

| Constant | URL | Purpose |
|----------|-----|---------|
| `MANELI_SMS_API_WSDL` | `https://api.payamak-panel.com/post/send.asmx?wsdl` | SMS gateway WSDL |

### Payment Gateway Endpoints

| Constant | URL | Purpose |
|----------|-----|---------|
| `MANELI_ZARINPAL_REQUEST_URL` | `https://api.zarinpal.com/pg/v4/payment/request.json` | Zarinpal request |
| `MANELI_ZARINPAL_VERIFY_URL` | `https://api.zarinpal.com/pg/v4/payment/verify.json` | Zarinpal verification |
| `MANELI_ZARINPAL_STARTPAY_URL` | `https://www.zarinpal.com/pg/StartPay/` | Zarinpal payment page |
| `MANELI_SADAD_REQUEST_URL` | `https://sadad.shaparak.ir/vpg/api/v0/Request/PaymentRequest` | Sadad request |
| `MANELI_SADAD_VERIFY_URL` | `https://sadad.shaparak.ir/vpg/api/v0/Advice/Verify` | Sadad verification |
| `MANELI_SADAD_PURCHASE_URL` | `https://sadad.shaparak.ir/VPG/Purchase?Token=` | Sadad payment page |

---

## 9. NONCE NAMES

WordPress nonces for security token verification:

| Nonce Name | File | Purpose |
|------------|------|---------|
| `maneli_import_demo_data` | `class-demo-data-handler.php` | Demo data import |
| `maneli_delete_demo_data` | `class-demo-data-handler.php` | Demo data deletion |
| `maneli_expert_create_nonce` | `inquiry-form/expert-new-inquiry-form.php` | Expert inquiry creation |
| `maneli_inquiry_filter_nonce` | Dashboard injection | Inquiry filtering |
| `maneli_inquiry_assign_expert_nonce` | Dashboard injection | Expert assignment |
| `maneli_inquiry_details_nonce` | Dashboard injection | Inquiry details access |
| `maneli_inquiry_delete_nonce` | Dashboard injection | Inquiry deletion |

---

## 10. LANGUAGE FILES AND TRANSLATIONS

Translation files containing brand name references:

### Translation Files

| File | Type | Status | Language |
|------|------|--------|----------|
| `languages/maneli-car-inquiry-en_US.po` | Translation | Active | English (US) |
| `languages/maneli-car-inquiry-en_US.mo` | Compiled | Active | English (US) |
| `languages/maneli-car-inquiry-fa_IR.po` | Translation | Active | Persian (Iran) |
| `languages/maneli-car-inquiry-fa_IR.mo` | Compiled | Active | Persian (Iran) |
| `languages/maneli-car-inquiry.pot` | Template | Template | English (Template) |
| `languages/maneli-car-inquiry-new.pot` | Template | Backup | English (New) |
| `languages/maneli-car-inquiry.pot.backup` | Backup | Backup | English (Backup) |
| `languages/maneli-car-inquiry-fa_IR.po.template` | Template | Template | Persian (Template) |

### Key Translation Strings

**English (en_US):**
- "Maneli Expert" → msgid at line 48
- "Maneli Manager" → msgid at line 51
- "Maneli Experts" → msgid at line 1257
- "Maneli Car System Settings" → msgid at line 2374
- "Maneli Admin" → msgid at line 3287

**Persian (fa_IR):**
- "Maneli Expert" → "کارشناس مانلی" (line 59-60)
- "Maneli Manager" → "مدیر مانلی" (line 63-64)
- "Maneli Experts" → "کارشناسان مانلی" (line 1645-1646)
- "Maneli Car Inquiry plugin requires WooCommerce..." → Persian translation (line 20-21)
- "Maneli Car System Settings" → "تنظیمات سیستم مانلی کار" (line 3027-3028)
- "Maneli Admin" → "مدیر مانلی" (line 4095-4096)

---

## 11. URL AND PLUGIN SLUG REFERENCES

### Plugin Slug

| Reference | Value | Context |
|-----------|-------|---------|
| Text Domain | `maneli-car-inquiry` | Plugin translation domain |
| Plugin Slug | `maneli-car-inquiry` | File folder & activation path |
| Directory Name | `maneli-car-inquiry` | Plugin folder name |

### Plugin Documentation URLs

| Reference | URL | File |
|-----------|-----|------|
| Plugin URI | `https://puzzlinco.com` | `maneli-car-inquiry.php` |
| Author URI | `https://arsalanarghavan.ir` | `maneli-car-inquiry.php` |

### Asset Paths with Plugin Slug

Files frequently reference assets using the plugin slug and constants:

```
MANELI_INQUIRY_PLUGIN_URL . 'assets/css/...'
MANELI_INQUIRY_PLUGIN_URL . 'assets/js/...'
MANELI_INQUIRY_PLUGIN_URL . 'assets/images/...'
```

Examples from templates:
- `templates/create-password.php` line 36-55 (CSS/JS files)
- `templates/elementor/home-page.php` line 30-31, 77, 126, 150 (enqueued assets)
- Various dashboard templates

---

## 12. FRONT-END AND ELEMENTOR REFERENCES

### Elementor Home Page Content

**File:** `templates/elementor/home-page.php`

| Line | Content (Persian/English) | Type |
|------|---------------------------|------|
| 81 | "مانلی خودرو" | Logo text |
| 172 | "چرا باید از مانلی خودرو خرید کنیم؟" | Section title |
| 302 | "فرایند خرید خودرو در مانلی" | Section title |
| 388 | "رضایت مشتریان مانلی خودرو" | Section title |
| 496 | "درباره مانلی خودرو" | Section heading |
| 498-504 | Company description text | Body copy |
| 508 | "نمایشگاه مانلی خودرو" | Image alt text |
| 523 | "مانلی خودرو" | Footer branding |
| 577 | "© [year] مانلی خودرو - تمام حقوق محفوظ است" | Copyright notice |

### Home Page Assets

| Asset | File | Enqueue Line |
|-------|------|----------------|
| CSS Stylesheet | `elementor-home.css` | 30 |
| JavaScript | `elementor-home.js` | 31 |

---

## 13. SEARCH KEYWORDS AND MEDIA REFERENCES

### Arabic Text (مانلی - Maneli in Persian)

Located in front-end facing content:

| File | Context | Usage |
|------|---------|-------|
| `templates/elementor/home-page.php` | Branding | Logo, headings, footer |
| `templates/dashboard-base.html` | Meta tags | Keywords, description, author |
| `templates/dashboard-temp.html` | Meta tags | Keywords, description, author |
| `templates/dashboard/profile-settings.php` | File header | Comment |

---

## 14. CONFIGURATION AND SETUP REFERENCES

### Plugin Header (WordPress Standard)

```php
Plugin Name:       Maneli Car Inquiry Core
Plugin URI:        https://puzzlinco.com
Description:       A plugin for car purchase inquiries...
Version:           1.1.0
Author:            ArsalanArghavan
Text Domain:       maneli-car-inquiry
Domain Path:       /languages
```

### Activation/Deactivation Hooks

| Hook | Calls | File | Line |
|------|-------|------|------|
| `register_activation_hook` | `Maneli_Activator::activate()` | `maneli-car-inquiry.php` | 115 |
| `register_deactivation_hook` | `Maneli_Roles_Caps::deactivate()` | `maneli-car-inquiry.php` | 120 |

---

## 15. SUMMARY STATISTICS

### Quantitative Overview

| Category | Count |
|----------|-------|
| PHP Classes with "Maneli_" prefix | 52+ |
| PHP Functions with "maneli_" prefix | 15+ |
| AJAX Actions with "maneli_" prefix | 17+ |
| Database Meta Keys with "maneli_" prefix | 4 |
| Database Option Keys with "maneli_" prefix | 1 |
| User Roles/Capabilities with "maneli_" prefix | 3 |
| Language Files | 7 |
| Templates containing brand references | 40+ |
| Constants with "MANELI_" prefix | 20+ |

### Language Coverage

- **English:** Full translations in `maneli-car-inquiry-en_US.po`
- **Persian (Farsi):** Full translations in `maneli-car-inquiry-fa_IR.po`
- **Template:** `maneli-car-inquiry.pot` for translator reference

---

## 16. BRANDING ELEMENTS

### Persian Brand Name Usage

The Persian text "مانلی خودرو" (Maneli Khodro - Maneli Cars) is used in:

1. **Elementor Home Page:**
   - Logo text
   - Section headings
   - Footer branding
   - Copyright notice
   - Company description text

2. **Dashboard Interface:**
   - Meta descriptions
   - Page titles
   - Dashboard branding
   - Footer text

3. **Comments & Documentation:**
   - Internal code comments
   - File headers

### English Brand Name Usage

"Maneli" appears in:

1. **Plugin Metadata:**
   - Plugin Name: "Maneli Car Inquiry Core"
   - Page titles: "Create Password - Maneli Car Inquiry", "Login - Maneli Car Inquiry"

2. **Class Names:**
   - All 52+ core classes prefixed with `Maneli_`

3. **Documentation:**
   - README, MANIFEST, guides

4. **Translation Strings:**
   - Role names: "Maneli Expert", "Maneli Manager", "Maneli Admin"

---

## 17. POTENTIAL BRANDING CHANGE IMPACT ANALYSIS

If the brand name needs to be changed from "Maneli" to another name:

### High Priority (Direct Brand References)

1. **Plugin Header** - `maneli-car-inquiry.php` line 3
2. **Page Titles** - Multiple template files
3. **Role Names** - `class-roles-caps.php` 
4. **Translation Strings** - All `.po` files
5. **Elementor Content** - `templates/elementor/home-page.php`
6. **Footer/Copyright** - Various templates

### Medium Priority (Class/Function Names - Backward Compatibility)

- PHP class names (`Maneli_*`)
- PHP function names (`maneli_*`)
- AJAX action names (`wp_ajax_maneli_*`)
- Meta key names (`maneli_*`)
- Option names (`maneli_inquiry_all_options`)

**Note:** Changing these would require database migrations and could break backward compatibility with existing installations.

### Low Priority (Database-Stored Data)

- Capability names (`manage_maneli_inquiries`)
- Role slugs (`maneli_expert`, `maneli_admin`)

**Note:** Role slugs are stored in WordPress user metadata and cannot be easily changed without database migration.

---

## 18. RECOMMENDATIONS

1. **For Localization:** All user-facing strings use the `maneli-car-inquiry` text domain and are already translatable.

2. **For Rebranding:** If considering a brand name change:
   - Update plugin header first
   - Update translation files
   - Update class/function names carefully (plan database migration)
   - Update role display names (translatable, stored in `$wp_roles->roles`)
   - Update front-end content (Elementor pages, templates)

3. **For Extension Developers:** All internal APIs follow the `maneli_` naming convention, making it easy to:
   - Hook into plugin functionality using `maneli_*` filters/actions
   - Identify plugin-specific code
   - Avoid naming conflicts

4. **For Contributors:** When adding new features:
   - Prefix classes with `Maneli_`
   - Prefix functions with `maneli_`
   - Prefix AJAX actions with `wp_ajax_maneli_`
   - Use the `maneli-car-inquiry` text domain for translations
   - Document in this audit file

---

## Document Information

- **Generated:** December 15, 2025
- **Plugin Version:** 1.1.0
- **Database Schema:** 1.1.0
- **Total Classes:** 52+
- **Total Functions:** 15+
- **Total AJAX Actions:** 17+
- **Languages Supported:** English (US), Persian (Iran)
- **Text Domain:** `maneli-car-inquiry`

---

*This audit provides a comprehensive inventory of all brand name and identifier occurrences throughout the Maneli Car Inquiry plugin codebase.*
