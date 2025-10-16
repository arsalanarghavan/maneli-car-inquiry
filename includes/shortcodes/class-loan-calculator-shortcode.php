<?php
/**
 * Handles the [loan_calculator] shortcode, which displays the installment calculator
 * and the cash purchase request form on the single product page.
 *
 * @package Maneli_Car_Inquiry/Includes/Shortcodes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.1 (Added interest rate localization and conditional asset loading)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Loan_Calculator_Shortcode {

    public function __construct() {
        add_shortcode('loan_calculator', [$this, 'render_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_calculator_assets']);
    }

    /**
     * Enqueues the required JavaScript and localizes the configurable interest rate 
     * and AJAX settings if the shortcode is present on the page.
     */
    public function enqueue_calculator_assets() {
        // اطمینان از اینکه اسکریپت فقط در صفحات محصول یا صفحاتی که شورت‌کد در آن هست بارگذاری شود.
        if (!is_product() && !has_shortcode(get_post(get_the_ID())->post_content, 'loan_calculator')) {
            return;
        }

        $options = get_option('maneli_inquiry_all_options', []);
        
        // Fetch configurable interest rate 
        $interest_rate = floatval($options['loan_interest_rate'] ?? 0.035);
        
        // Standard calculator JS enqueue
        // از آنجایی که این کلاس مسئولیت بارگذاری اسکریپت را بر عهده دارد، باید مسیر و نسخه را به درستی مشخص کند.
        wp_enqueue_script('maneli-calculator-js', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/calculator.js', ['jquery'], '1.0.0', true);
        
        // Localize script with AJAX info and the configurable rate
        $localize_data = [
            'interestRate' => $interest_rate, // پاس دادن نرخ سود ماهانه
            'text' => [
                'sending' => esc_html__('Sending information...', 'maneli-car-inquiry'),
                'error_sending' => esc_html__('Error sending information: ', 'maneli-car-inquiry'),
                'unknown_error' => esc_html__('Unknown error.', 'maneli-car-inquiry'),
                'credit_check' => esc_html__('Bank Credit Check for Car Purchase', 'maneli-car-inquiry'),
                'server_error_connection' => esc_html__('An unknown error occurred while communicating with the server.', 'maneli-car-inquiry'),
            ]
        ];
        
        if (is_user_logged_in()) {
            $localize_data['ajax_url'] = admin_url('admin-ajax.php');
            $localize_data['inquiry_page_url'] = home_url('/dashboard/?endp=inf_menu_1');
            $localize_data['nonce'] = wp_create_nonce('maneli_ajax_nonce');
        }

        wp_localize_script('maneli-calculator-js', 'maneli_ajax_object', $localize_data);
    }

    /**
     * Main render method for the [loan_calculator] shortcode.
     *
     * @return string The HTML output for the shortcode.
     */
    public function render_shortcode() {
        // اطمینان از اجرا شدن فقط در صفحه محصول
        if (!function_exists('is_product') || !is_product() || !function_exists('WC')) {
            return '';
        }
        global $product;
        if (!$product instanceof WC_Product) {
            return '';
        }

        ob_start();

        // نمایش پیام موفقیت‌آمیز بودن ارسال استعلام نقدی
        if (isset($_GET['cash_inquiry_sent']) && $_GET['cash_inquiry_sent'] === 'true') {
            maneli_get_template_part('public/cash-inquiry-success-message');
        }

        $car_status = get_post_meta($product->get_id(), '_maneli_car_status', true);

        // اگر وضعیت خودرو 'unavailable' باشد، یک پیام و ماشین حساب غیرفعال نمایش داده می‌شود.
        if ($car_status === 'unavailable') {
            $options = get_option('maneli_inquiry_all_options', []);
            $message = $options['unavailable_product_message'] ?? esc_html__('This car is currently unavailable for purchase.', 'maneli-car-inquiry');
            
            maneli_get_template_part('shortcodes/calculator/unavailable-product-overlay', ['message' => $message]);
            
            return ob_get_clean();
        }

        // آماده‌سازی داده‌ها برای ارسال به تمپلیت
        $cash_price = (int)$product->get_regular_price();
        $installment_price = (int)get_post_meta($product->get_id(), 'installment_price', true);
        if (empty($installment_price)) {
            $installment_price = $cash_price;
        }

        $car_colors_str = get_post_meta($product->get_id(), '_maneli_car_colors', true);
        $car_colors = !empty($car_colors_str) ? array_map('trim', explode(',', $car_colors_str)) : [];

        $template_args = [
            'product'           => $product,
            'cash_price'        => $cash_price,
            'installment_price' => $installment_price,
            'min_down_payment'  => (int)get_post_meta($product->get_id(), 'min_downpayment', true),
            'max_down_payment'  => (int)($installment_price * 0.8), // ۸۰٪ از قیمت کل
            'car_colors'        => $car_colors,
        ];
        
        // رندر کردن کانتینر اصلی ماشین حساب با تب‌ها و محتوای آن
        maneli_get_template_part('shortcodes/calculator/main-container', $template_args);

        return ob_get_clean();
    }
}