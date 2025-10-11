<?php
/**
 * Creates a dedicated admin page for viewing a detailed credit report for an inquiry.
 * این کلاس خنثی شده است تا از ایجاد صفحه گزارش در بک‌اند جلوگیری کند،
 * زیرا تمامی عملکردهای گزارش‌گیری اکنون توسط شورت‌کدها در فرانت‌اند مدیریت می‌شوند.
 *
 * @package Maneli_Car_Inquiry/Includes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.1.0 (Neutralized for frontend-only mode)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Credit_Report_Page {

    /**
     * The hook suffix for the custom admin page.
     * @var string
     */
    private $page_hook_suffix;

    public function __construct() {
        // تمامی هوک‌های مربوط به افزودن صفحه گزارش به منوی ادمین و بارگذاری اسکریپت‌ها
        // حذف شدند تا عملیات فقط در فرانت‌اند انجام شود.
    }
}