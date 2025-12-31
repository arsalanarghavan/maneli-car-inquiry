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
        // Use priority 20 to ensure this runs after enqueue_global_assets (priority 10)
        add_action('wp_enqueue_scripts', [$this, 'enqueue_calculator_assets'], 20);
        // Also hook into wp_head to ensure styles are printed (backup) - use priority 99 to run after all enqueues
        add_action('wp_head', [$this, 'force_enqueue_calculator_assets'], 99);
        
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
        global $post;
        
        // Multiple checks to ensure we detect product pages correctly
        $is_product = false;
        $has_shortcode = false;
        
        // Check 1: WooCommerce is_product() function
        if (function_exists('is_product') && is_product()) {
            $is_product = true;
        }
        
        // Check 2: Post type check
        if (!$is_product && is_a($post, 'WP_Post') && 'product' === get_post_type($post)) {
            $is_product = true;
        }
        
        // Check 3: Query var check
        if (!$is_product && get_query_var('product')) {
            $is_product = true;
        }
        
        // Check 4: Current post ID check
        $current_post_id = get_the_ID();
        if (!$is_product && $current_post_id) {
            $current_post = get_post($current_post_id);
            if (is_a($current_post, 'WP_Post') && 'product' === get_post_type($current_post)) {
                $is_product = true;
            }
        }
        
        // Check for shortcode in post content
        if (!$is_product && is_a($post, 'WP_Post') && has_shortcode($post->post_content ?? '', 'loan_calculator')) {
            $has_shortcode = true;
        }
        
        // If neither product page nor has shortcode, don't load assets
        if (!$is_product && !$has_shortcode) {
            return;
        }

        // Force enqueue all required styles for product pages
        // This ensures styles are loaded even if enqueue_global_assets didn't enqueue them
        
        // Ensure Peyda Font is registered and enqueued first (CRITICAL - must load before other styles)
        $peyda_font_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/css/peyda-font.css';
        if (file_exists($peyda_font_path)) {
            if (!wp_style_is('autopuzzle-peyda-font', 'registered')) {
                wp_register_style('autopuzzle-peyda-font', AUTOPUZZLE_PLUGIN_URL . 'assets/css/peyda-font.css', [], filemtime($peyda_font_path));
            }
            if (!wp_style_is('autopuzzle-peyda-font', 'enqueued')) {
                wp_enqueue_style('autopuzzle-peyda-font');
            }
        }
        
        // Ensure Line Awesome is registered and enqueued first (as dependency)
        $line_awesome_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/css/autopuzzle-line-awesome-complete.css';
        if (file_exists($line_awesome_path)) {
            if (!wp_style_is('autopuzzle-line-awesome-complete', 'registered')) {
                wp_register_style('autopuzzle-line-awesome-complete', AUTOPUZZLE_PLUGIN_URL . 'assets/css/autopuzzle-line-awesome-complete.css', [], '1.0.0');
            }
            if (!wp_style_is('autopuzzle-line-awesome-complete', 'enqueued')) {
                wp_enqueue_style('autopuzzle-line-awesome-complete');
            }
        }

        // Ensure frontend styles are registered and enqueued
        $frontend_css_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/css/frontend.css';
        if (file_exists($frontend_css_path)) {
            if (!wp_style_is('autopuzzle-frontend-styles', 'registered')) {
                $css_version = filemtime($frontend_css_path);
                wp_register_style('autopuzzle-frontend-styles', AUTOPUZZLE_PLUGIN_URL . 'assets/css/frontend.css', ['autopuzzle-line-awesome-complete'], $css_version);
            }
            if (!wp_style_is('autopuzzle-frontend-styles', 'enqueued')) {
                wp_enqueue_style('autopuzzle-frontend-styles');
            }
        } else {
            // Use autopuzzle-shortcode-assets.css as fallback if frontend.css doesn't exist
            $fallback_css_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/css/autopuzzle-shortcode-assets.css';
            if (file_exists($fallback_css_path)) {
                if (!wp_style_is('autopuzzle-frontend-styles', 'registered')) {
                    $css_version = filemtime($fallback_css_path);
                    wp_register_style('autopuzzle-frontend-styles', AUTOPUZZLE_PLUGIN_URL . 'assets/css/autopuzzle-shortcode-assets.css', ['autopuzzle-line-awesome-complete'], $css_version);
                }
                if (!wp_style_is('autopuzzle-frontend-styles', 'enqueued')) {
                    wp_enqueue_style('autopuzzle-frontend-styles');
                }
            }
        }

        // Ensure Bootstrap is registered and enqueued
        if (!wp_style_is('autopuzzle-bootstrap-shortcode', 'registered')) {
            wp_register_style('autopuzzle-bootstrap-shortcode', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/bootstrap/css/bootstrap.rtl.min.css', [], '5.3.0');
        }
        if (!wp_style_is('autopuzzle-bootstrap-shortcode', 'enqueued')) {
            wp_enqueue_style('autopuzzle-bootstrap-shortcode');
        }
        
        // Ensure Cash Inquiry styles are enqueued
        $cash_inquiry_css_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/css/cash-inquiry.css';
        if (file_exists($cash_inquiry_css_path) && !wp_style_is('autopuzzle-cash-inquiry-styles', 'enqueued')) {
            $css_version = filemtime($cash_inquiry_css_path);
            wp_enqueue_style('autopuzzle-cash-inquiry-styles', AUTOPUZZLE_PLUGIN_URL . 'assets/css/cash-inquiry.css', [], $css_version);
        }
        
        // Ensure Installment Inquiry styles are enqueued
        $installment_inquiry_css_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/css/installment-inquiry.css';
        if (file_exists($installment_inquiry_css_path) && !wp_style_is('autopuzzle-installment-inquiry-styles', 'enqueued')) {
            $css_version = filemtime($installment_inquiry_css_path);
            wp_enqueue_style('autopuzzle-installment-inquiry-styles', AUTOPUZZLE_PLUGIN_URL . 'assets/css/installment-inquiry.css', [], $css_version);
        }
        
        // Ensure Xintra compat styles are enqueued
        $xintra_compat_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/css/shortcode-xintra-compat.css';
        if (file_exists($xintra_compat_path)) {
            if (!wp_style_is('autopuzzle-shortcode-xintra-compat', 'registered')) {
                wp_register_style('autopuzzle-shortcode-xintra-compat', AUTOPUZZLE_PLUGIN_URL . 'assets/css/shortcode-xintra-compat.css', ['autopuzzle-frontend-styles'], '1.0.0');
            }
            if (!wp_style_is('autopuzzle-shortcode-xintra-compat', 'enqueued')) {
                wp_enqueue_style('autopuzzle-shortcode-xintra-compat');
            }
        }

        // Ensure jQuery is enqueued (required for all scripts)
        if (!wp_script_is('jquery', 'enqueued')) {
            wp_enqueue_script('jquery');
        }

        // Ensure SweetAlert2 is enqueued (required for forms)
        $sweetalert2_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/libs/sweetalert2/sweetalert2.min.js';
        if (file_exists($sweetalert2_path)) {
            if (!wp_style_is('sweetalert2', 'enqueued')) {
                wp_enqueue_style('sweetalert2', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/sweetalert2/sweetalert2.min.css', [], '11.0.0');
            }
            if (!wp_script_is('sweetalert2', 'enqueued')) {
                wp_enqueue_script('sweetalert2', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/sweetalert2/sweetalert2.min.js', ['jquery'], '11.0.0', true);
            }
        } else {
            // Fallback to CDN if local file doesn't exist
            if (!wp_script_is('sweetalert2', 'enqueued')) {
                wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', ['jquery'], null, true);
            }
        }

        // Ensure Select2 is enqueued (required for dropdowns)
        if (!wp_style_is('select2', 'enqueued')) {
            wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0');
        }
        if (!wp_script_is('select2', 'enqueued')) {
            wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0', true);
        }

        $options = Autopuzzle_Options_Helper::get_all_options();
        
        // Fetch configurable interest rate 
        $interest_rate = floatval($options['loan_interest_rate'] ?? 0.035);
        
        // Enqueue calculator CSS - check if file exists first
        // IMPORTANT: Include peyda-font as dependency so font loads before calculator CSS
        $calculator_css_deps = ['autopuzzle-frontend-styles', 'autopuzzle-bootstrap-shortcode'];
        if (wp_style_is('autopuzzle-peyda-font', 'registered') || wp_style_is('autopuzzle-peyda-font', 'enqueued')) {
            $calculator_css_deps[] = 'autopuzzle-peyda-font';
        }
        
        $calculator_css_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/css/loan-calculator.css';
        if (file_exists($calculator_css_path)) {
            wp_enqueue_style(
                'autopuzzle-loan-calculator', 
                AUTOPUZZLE_PLUGIN_URL . 'assets/css/loan-calculator.css', 
                $calculator_css_deps,
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
     * Force enqueue calculator assets in wp_head as backup
     * This ensures styles are loaded even if wp_enqueue_scripts didn't catch the product page
     * Uses direct HTML injection if enqueue doesn't work
     */
    public function force_enqueue_calculator_assets() {
        // Only run on frontend
        if (is_admin()) {
            return;
        }
        
        // Skip if this is dashboard page
        if (get_query_var('autopuzzle_dashboard')) {
            return;
        }
        
        global $post, $wp_query;
        
        // Multiple checks to detect product page - be very aggressive in detection
        $is_product = false;
        $has_shortcode = false;
        
        // Check 1: URL pattern (most reliable in wp_head - check first!)
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        if (!empty($request_uri)) {
            // Decode URL to handle Persian characters
            $decoded_uri = urldecode($request_uri);
            // Check if URL contains /product/ pattern (case insensitive, handle encoded URLs)
            if (stripos($request_uri, '/product/') !== false || 
                stripos($decoded_uri, '/product/') !== false ||
                preg_match('#/product/[^/]+/?$#i', $request_uri) ||
                preg_match('#/product/[^/]+/?$#i', $decoded_uri)) {
                $is_product = true;
            }
        }
        
        // Check 2: Queried object (reliable in wp_head)
        if (!$is_product) {
            $queried_object = get_queried_object();
            if (is_a($queried_object, 'WP_Post') && 'product' === get_post_type($queried_object)) {
                $is_product = true;
                $post = $queried_object;
            }
        }
        
        // Check 3: WooCommerce is_product()
        if (!$is_product && function_exists('is_product') && is_product()) {
            $is_product = true;
        }
        
        // Check 4: Post type check from global $post
        if (!$is_product && is_a($post, 'WP_Post') && 'product' === get_post_type($post)) {
            $is_product = true;
        }
        
        // Check 5: Current post ID check
        if (!$is_product) {
            $current_post_id = get_the_ID();
            if ($current_post_id) {
                $current_post = get_post($current_post_id);
                if (is_a($current_post, 'WP_Post') && 'product' === get_post_type($current_post)) {
                    $is_product = true;
                    $post = $current_post;
                }
            }
        }
        
        // Check 6: Check if shortcode exists in post content
        if (!$is_product && is_a($post, 'WP_Post') && has_shortcode($post->post_content ?? '', 'loan_calculator')) {
            $has_shortcode = true;
        }
        
        // Check 7: Check global query
        if (!$is_product && !$has_shortcode && isset($wp_query) && is_a($wp_query, 'WP_Query')) {
            if ($wp_query->is_singular('product') || 
                (isset($wp_query->queried_object) && is_a($wp_query->queried_object, 'WP_Post') && 'product' === get_post_type($wp_query->queried_object))) {
                $is_product = true;
            }
        }
        
        // If not a product page and no shortcode, don't do anything
        if (!$is_product && !$has_shortcode) {
            return;
        }
        
        // CRITICAL: Always inject styles directly for product pages
        // Don't rely on wp_style_is checks - always add if not already present
        echo "\n<!-- AutoPuzzle Calculator Styles Direct Injection -->\n";
        
        // Peyda Font - CRITICAL! Must load first before other styles
        $peyda_font_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/css/peyda-font.css';
        if (file_exists($peyda_font_path) && !wp_style_is('autopuzzle-peyda-font', 'enqueued') && !wp_style_is('autopuzzle-peyda-font', 'done')) {
            $css_version = filemtime($peyda_font_path);
            echo '<link rel="stylesheet" id="autopuzzle-peyda-font-css" href="' . esc_url(AUTOPUZZLE_PLUGIN_URL . 'assets/css/peyda-font.css') . '?ver=' . esc_attr($css_version) . '" media="all">' . "\n";
        }
        
        // Icons CSS (contains line-awesome) - Always add if not already enqueued
        $icons_css_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/css/icons.css';
        if (file_exists($icons_css_path) && !wp_style_is('autopuzzle-icons', 'enqueued') && !wp_style_is('autopuzzle-icons', 'done')) {
            $css_version = file_exists($icons_css_path) ? filemtime($icons_css_path) : '1.0.0';
            echo '<link rel="stylesheet" id="autopuzzle-icons-css" href="' . esc_url(AUTOPUZZLE_PLUGIN_URL . 'assets/css/icons.css') . '?ver=' . esc_attr($css_version) . '" media="all">' . "\n";
        }
        
        // Shortcode assets CSS (fallback for frontend.css) - Always add if not already enqueued
        $shortcode_assets_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/css/maneli-shortcode-assets.css';
        if (file_exists($shortcode_assets_path) && !wp_style_is('autopuzzle-frontend-styles', 'enqueued') && !wp_style_is('autopuzzle-frontend-styles', 'done')) {
            $css_version = filemtime($shortcode_assets_path);
            echo '<link rel="stylesheet" id="autopuzzle-frontend-styles-css" href="' . esc_url(AUTOPUZZLE_PLUGIN_URL . 'assets/css/maneli-shortcode-assets.css') . '?ver=' . esc_attr($css_version) . '" media="all">' . "\n";
        }
        
        // Loan Calculator CSS - CRITICAL! Always add if file exists
        // Note: Font should already be loaded above, but we ensure it's there
        $calculator_css_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/css/loan-calculator.css';
        if (file_exists($calculator_css_path)) {
            // Always add - don't check wp_style_is as it may not work correctly
            // Font is already loaded above, so it will be available
            $css_version = filemtime($calculator_css_path);
            echo '<link rel="stylesheet" id="autopuzzle-loan-calculator-css" href="' . esc_url(AUTOPUZZLE_PLUGIN_URL . 'assets/css/loan-calculator.css') . '?ver=' . esc_attr($css_version) . '" media="all">' . "\n";
        }
        
        // Xintra compat - Add if file exists
        $xintra_compat_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/css/shortcode-xintra-compat.css';
        if (file_exists($xintra_compat_path) && !wp_style_is('autopuzzle-shortcode-xintra-compat', 'enqueued') && !wp_style_is('autopuzzle-shortcode-xintra-compat', 'done')) {
            echo '<link rel="stylesheet" id="autopuzzle-shortcode-xintra-compat-css" href="' . esc_url(AUTOPUZZLE_PLUGIN_URL . 'assets/css/shortcode-xintra-compat.css') . '?ver=1.0.0" media="all">' . "\n";
        }
        
        echo "<!-- End AutoPuzzle Calculator Styles -->\n";
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