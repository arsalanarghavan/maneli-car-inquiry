<?php
/**
 * Handles the [loan_calculator] shortcode, which displays the installment calculator
 * and the cash purchase request form on the single product page.
 *
 * @package Autopuzzle_Car_Inquiry/Includes/Shortcodes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.1 (Added interest rate localization and conditional asset loading)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Autopuzzle_Loan_Calculator_Shortcode {

    public function __construct() {
        // Check license before registering shortcode (using optimized helper)
        if (!Autopuzzle_Permission_Helpers::is_license_active() && !Autopuzzle_Permission_Helpers::is_demo_mode()) {
            // License not active - register shortcode that shows message
            add_shortcode('loan_calculator', [$this, 'render_license_required_message']);
            return;
        }
        
        add_shortcode('loan_calculator', [$this, 'render_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_calculator_assets']);
        
        // Automatically add calculator to single product pages
        add_action('woocommerce_after_single_product_summary', [$this, 'render_shortcode'], 15);
    }

    /**
     * Render license required message
     */
    public function render_license_required_message() {
        return '<div class="alert alert-warning"><i class="ri-alert-line me-2"></i>این قابلیت نیازمند لایسنس فعال است.</div>';
    }

    /**
     * Enqueues the required JavaScript and localizes the configurable interest rate 
     * and AJAX settings if the shortcode is present on the page.
     */
    public function enqueue_calculator_assets() {
        // اطمینان از اینکه اسکریپت فقط در صفحات محصول یا صفحاتی که شورت‌کد در آن هست بارگذاری شود.
        $current_post = get_post(get_the_ID());
        if (!is_product() && (!$current_post || !has_shortcode($current_post->post_content ?? '', 'loan_calculator'))) {
            return;
        }

        // First, ensure frontend styles are registered/enqueued as dependencies
        // This is handled by Autopuzzle_Shortcode_Handler, but we need to ensure they're available
        $frontend_css_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/css/frontend.css';
        if (!wp_style_is('autopuzzle-frontend-styles', 'registered')) {
            if (file_exists($frontend_css_path)) {
                $css_version = filemtime($frontend_css_path);
                wp_register_style('autopuzzle-frontend-styles', AUTOPUZZLE_PLUGIN_URL . 'assets/css/frontend.css', [], $css_version);
            } else {
                // Use autopuzzle-shortcode-assets.css as fallback if frontend.css doesn't exist
                $fallback_css_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/css/autopuzzle-shortcode-assets.css';
                if (file_exists($fallback_css_path)) {
                    $css_version = filemtime($fallback_css_path);
                    wp_register_style('autopuzzle-frontend-styles', AUTOPUZZLE_PLUGIN_URL . 'assets/css/autopuzzle-shortcode-assets.css', [], $css_version);
                }
            }
        }

        // Ensure Bootstrap is registered/enqueued as dependency
        if (!wp_style_is('autopuzzle-bootstrap-shortcode', 'registered')) {
            wp_register_style('autopuzzle-bootstrap-shortcode', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/bootstrap/css/bootstrap.rtl.min.css', [], '5.3.0');
        }

        $options = Autopuzzle_Options_Helper::get_all_options();
        
        // Fetch configurable interest rate 
        $interest_rate = floatval($options['loan_interest_rate'] ?? 0.035);
        
        // Enqueue calculator CSS - check if file exists first
        $calculator_css_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/css/loan-calculator.css';
        if (file_exists($calculator_css_path)) {
            wp_enqueue_style(
                'autopuzzle-loan-calculator', 
                AUTOPUZZLE_PLUGIN_URL . 'assets/css/loan-calculator.css', 
                ['autopuzzle-frontend-styles', 'autopuzzle-bootstrap-shortcode'],
                filemtime($calculator_css_path)
            );
        }
        
        // Register and enqueue calculator JS - this runs only on product pages
        // Note: No jQuery dependency - we use vanilla JavaScript
        wp_register_script('autopuzzle-calculator-js', AUTOPUZZLE_PLUGIN_URL . 'assets/js/calculator.js', [], filemtime(AUTOPUZZLE_PLUGIN_PATH . 'assets/js/calculator.js'), true);
        wp_enqueue_script('autopuzzle-calculator-js');
        
        // Localize script with AJAX info and the configurable rate
        // IMPORTANT: This MUST be called AFTER wp_enqueue_script
        $localize_data = [
            'interestRate' => $interest_rate, // پاس دادن نرخ سود ماهانه
            'text' => [
                'sending' => esc_html__('Sending information...', 'autopuzzle'),
                'error_sending' => esc_html__('Error sending information: ', 'autopuzzle'),
                'unknown_error' => esc_html__('Unknown error.', 'autopuzzle'),
                'credit_check' => esc_html__('Bank Credit Check for Car Purchase', 'autopuzzle'),
                'server_error_connection' => esc_html__('An unknown error occurred while communicating with the server.', 'autopuzzle'),
            ],
            'ajax_url' => '', // Will be set if user is logged in
            'inquiry_page_url' => '',
            'nonce' => ''
        ];
        
        // Additional texts for calculator.js
        $localize_data['text']['unavailable_cash_message'] = Autopuzzle_Options_Helper::get_option('unavailable_cash_message', esc_html__('This product is currently unavailable for cash purchase.', 'autopuzzle'));
        $localize_data['text']['unknown_server_error'] = esc_html__('An unknown error occurred while communicating with the server.', 'autopuzzle');
        $localize_data['text']['please_fill_all_fields'] = esc_html__('Please fill all required fields.', 'autopuzzle');
        $localize_data['text']['invalid_mobile_number'] = esc_html__('Please enter a valid mobile number.', 'autopuzzle');
        $localize_data['text']['select_car_color'] = esc_html__('Please select a car color.', 'autopuzzle');
        $localize_data['text']['enter_car_color'] = esc_html__('Please enter a car color.', 'autopuzzle');
        $localize_data['text']['minimum_downpayment'] = esc_html__('Minimum down payment is %s Toman.', 'autopuzzle');
        $localize_data['text']['maximum_downpayment'] = esc_html__('Maximum down payment is %s Toman.', 'autopuzzle');
        $localize_data['text']['inquiry_sent_success'] = esc_html__('Your inquiry has been sent successfully!', 'autopuzzle');
        $localize_data['text']['error_sending_inquiry'] = esc_html__('Error sending inquiry. Please try again.', 'autopuzzle');

        if (is_user_logged_in()) {
            $localize_data['ajax_url'] = admin_url('admin-ajax.php');
            $localize_data['inquiry_page_url'] = home_url('/dashboard/installment-inquiries');
            $localize_data['nonce'] = wp_create_nonce('autopuzzle_ajax_nonce');
            $localize_data['cash_inquiry_nonce'] = wp_create_nonce('autopuzzle_customer_cash_inquiry');
        }

        wp_localize_script('autopuzzle-calculator-js', 'autopuzzle_ajax_object', $localize_data);
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
            autopuzzle_get_template_part('public/cash-inquiry-success-message');
        }

        $product_id = $product->get_id();
        $car_status = get_post_meta($product_id, '_maneli_car_status', true);
        
        // Normalize car_status - handle empty or invalid values
        if (empty($car_status) || !in_array($car_status, ['special_sale', 'unavailable', 'disabled'], true)) {
            $car_status = 'special_sale'; // Default status
        }
        
        // Debug: Log car status for troubleshooting
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("AutoPuzzle Calculator: Product ID {$product_id}, Car Status: " . $car_status);
        }

        // اگر وضعیت خودرو 'disabled' باشد، محصول نباید نمایش داده شود (فقط برای غیر-ادمین)
        if (!current_user_can('manage_autopuzzle_inquiries') && $car_status === 'disabled') {
            return ''; // Return empty for disabled products for non-admin users
        }

        // آماده‌سازی داده‌ها برای ارسال به تمپلیت
        // Check if prices should be hidden
        // NOTE: In calculator form, prices are ALWAYS shown regardless of hide_prices_for_customers setting
        // The hide_prices_for_customers setting only affects catalog and product pages
        $hide_prices = Autopuzzle_Options_Helper::is_option_enabled('hide_prices_for_customers', false);
        
        // In calculator form: ALWAYS show prices to everyone
        // In other places (catalog, etc): respect the hide_prices_for_customers setting
        $can_see_prices = true; // Always show prices in calculator
        
        // Get cash_price - handle empty/null values properly
        // get_regular_price() can return empty string, null, false, 0, float, int, or formatted string
        $cash_price_raw = $product->get_regular_price();
        
        // Log for debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("AutoPuzzle Calculator Cash Price DEBUG:");
            error_log("  - get_regular_price() returned: " . var_export($cash_price_raw, true));
            error_log("  - cash_price_raw type: " . gettype($cash_price_raw));
        }
        
        // Always treat as string first to handle formatting, then convert
        if ($cash_price_raw === '' || $cash_price_raw === null || $cash_price_raw === false || $cash_price_raw === 0 || $cash_price_raw === '0') {
            $cash_price = 0;
        } else {
            // Convert to string and process formatting
            $cash_price_str = (string)$cash_price_raw;
            // Convert Persian digits to English
            $persian_digits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            $english_digits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            $cash_price_str = str_replace($persian_digits, $english_digits, $cash_price_str);
            // Remove all non-numeric characters (commas, spaces, decimal points, etc.)
            $cash_price_str = preg_replace('/[^\d]/', '', $cash_price_str);
            $cash_price = (!empty($cash_price_str) && $cash_price_str !== '0') ? (int)$cash_price_str : 0;
        }
        
        // Get installment_price - handle both string and integer values from database
        // Convert to integer - remove any formatting (commas, spaces, Persian digits, etc)
        // IMPORTANT: Don't fallback to cash_price - this allows independent control of cash and installment tabs
        $installment_price_raw = get_post_meta($product_id, 'installment_price', true);
        if (is_string($installment_price_raw) && !empty($installment_price_raw)) {
            // Convert Persian digits to English
            $persian_digits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            $english_digits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            $installment_price_raw = str_replace($persian_digits, $english_digits, $installment_price_raw);
            // Remove all non-numeric characters
            $installment_price_raw = preg_replace('/[^\d]/', '', $installment_price_raw);
        }
        // Get installment_price - if not set or empty, keep it as 0 (don't fallback to cash_price)
        // This allows independent control of cash and installment tabs
        $installment_price = (!empty($installment_price_raw) && $installment_price_raw !== '0') ? (int)$installment_price_raw : 0;

        $car_colors_str = get_post_meta($product_id, '_maneli_car_colors', true);
        $car_colors = !empty($car_colors_str) ? array_map('trim', explode(',', $car_colors_str)) : [];

        // Get min_downpayment - handle both string and integer values from dashboard
        $min_down_payment_raw = get_post_meta($product_id, 'min_downpayment', true);
        
        // Convert to integer - remove any formatting (commas, spaces, Persian digits, etc)
        if (is_string($min_down_payment_raw) && !empty($min_down_payment_raw)) {
            // Convert Persian digits to English
            $persian_digits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            $english_digits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            $min_down_payment_raw = str_replace($persian_digits, $english_digits, $min_down_payment_raw);
            // Remove all non-numeric characters
            $min_down_payment_raw = preg_replace('/[^\d]/', '', $min_down_payment_raw);
        }
        
        $min_down_payment = !empty($min_down_payment_raw) ? (int)$min_down_payment_raw : 0;
        
        // If min_downpayment is not set or is 0, calculate 20% of installment_price as default
        if ($min_down_payment <= 0 && $installment_price > 0) {
            $min_down_payment = (int)($installment_price * 0.2); // ۲۰٪ پیش‌فرض
        }

        // Determine if product is unavailable based on status
        $is_unavailable = ($car_status === 'unavailable');
        
        // Check if prices are missing - mark respective tabs as unavailable
        // Each tab is unavailable if: status is unavailable OR price is 0 or empty
        // This allows independent control: cash can be available while installment is unavailable and vice versa
        $cash_unavailable = ($is_unavailable || $cash_price <= 0);
        $installment_unavailable = ($is_unavailable || $installment_price <= 0);
        
        // Debug: Log availability status for troubleshooting
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $cash_price_raw_debug = $product->get_regular_price();
            error_log("AutoPuzzle Calculator: Product ID {$product_id}");
            error_log("  - cash_price_raw: " . var_export($cash_price_raw_debug, true));
            error_log("  - cash_price (after processing): {$cash_price}");
            error_log("  - installment_price_raw: " . var_export(get_post_meta($product_id, 'installment_price', true), true));
            error_log("  - installment_price (after processing): {$installment_price}");
            error_log("  - car_status: {$car_status}");
            error_log("  - is_unavailable: " . ($is_unavailable ? 'true' : 'false'));
            error_log("  - cash_unavailable: " . ($cash_unavailable ? 'true' : 'false'));
            error_log("  - installment_unavailable: " . ($installment_unavailable ? 'true' : 'false'));
        }

        $template_args = [
            'product'                => $product,
            'cash_price'             => $cash_price,
            'installment_price'      => $installment_price,
            'min_down_payment'       => $min_down_payment,
            'max_down_payment'       => (int)($installment_price * 0.8), // ۸۰٪ از قیمت کل
            'car_colors'             => $car_colors,
            'car_status'             => $car_status,
            'can_see_prices'         => $can_see_prices,
            'is_unavailable'         => $is_unavailable,
            'cash_unavailable'       => $cash_unavailable,
            'installment_unavailable' => $installment_unavailable,
        ];
        
        // رندر کردن کانتینر اصلی ماشین حساب با تب‌ها و محتوای آن
        autopuzzle_get_template_part('shortcodes/calculator/main-container', $template_args);

        return ob_get_clean();
    }
}