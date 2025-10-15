<?php
/**
 * Creates and manages the custom admin page for quick product editing.
 * This class has been neutralized to prevent the creation of the backend admin page,
 * ensuring all product editing functionality is handled by the shortcode on the frontend.
 *
 * @package Maneli_Car_Inquiry/Includes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.1.0 (Neutralized for frontend-only mode)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Product_Editor_Page {

    /**
     * The hook suffix for the custom admin page.
     * @var string
     */
    private $page_hook_suffix;

    public function __construct() {
        // تمامی هوک‌های مربوط به افزودن صفحه به منوی ادمین و بارگذاری اسکریپت‌ها
        // از محیط ادمین حذف شدند تا عملیات فقط در فرانت‌اند انجام شود.
    }

    /**
     * این متد دیگر از طریق هیچ هوکی فراخوانی نمی‌شود و تنها برای
     * حفظ ساختار فایل اصلی باقی مانده است. نمایش واقعی توسط شورت‌کد انجام می‌شود.
     */
    public function render_page_html() {
        if (!class_exists('WooCommerce')) {
            echo '<div class="notice notice-error"><p>' . esc_html__('To use this plugin, please install and activate WooCommerce first.', 'maneli-car-inquiry') . '</p></div>';
            return;
        }
        
        // محتوای واقعی این صفحه به کلاس Maneli_Product_Editor_Shortcode منتقل شده است.
    }
}