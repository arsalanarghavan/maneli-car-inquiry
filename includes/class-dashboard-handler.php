<?php
/**
 * Dashboard Handler Class
 * 
 * Handles dashboard pages, routing, and template rendering
 * 
 * @package Maneli_Car_Inquiry/Includes
 * @author  Arsalan Arghavan
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Dashboard_Handler {
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('template_redirect', [$this, 'handle_dashboard_requests']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_dashboard_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_global_fonts'], 1);
        add_action('wp_ajax_maneli_dashboard_login', [$this, 'handle_dashboard_login']);
        add_action('wp_ajax_nopriv_maneli_dashboard_login', [$this, 'handle_dashboard_login']);
        add_action('wp_ajax_maneli_dashboard_logout', [$this, 'handle_dashboard_logout']);
        add_action('wp_ajax_maneli_send_sms_code', [$this, 'handle_send_sms_code']);
        add_action('wp_ajax_nopriv_maneli_send_sms_code', [$this, 'handle_send_sms_code']);
        
        // مخفی کردن Admin Bar در داشبورد
        add_action('template_redirect', [$this, 'hide_admin_bar_in_dashboard']);
    }
    
    /**
     * Enqueue global fonts for all plugin pages
     */
    public function enqueue_global_fonts() {
        wp_enqueue_style('maneli-fonts', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-fonts.css', [], '1.0.0');
        wp_enqueue_style('maneli-rtl-force', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-rtl-force.css', ['maneli-fonts'], '1.0.0');
        wp_enqueue_style('maneli-dashboard-fix', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-dashboard-fix.css', ['maneli-rtl-force'], '1.0.0');
        wp_enqueue_style('maneli-loader-fix', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-loader-fix.css', ['maneli-dashboard-fix'], '1.0.0');
    }
    
    /**
     * Add rewrite rules for dashboard
     */
    public function add_rewrite_rules() {
        add_rewrite_rule('^login/?$', 'index.php?maneli_dashboard=1&maneli_dashboard_page=login', 'top');
        add_rewrite_rule('^logout/?$', 'index.php?maneli_dashboard=1&maneli_dashboard_page=logout', 'top');
        add_rewrite_rule('^dashboard/?$', 'index.php?maneli_dashboard=1', 'top');
        add_rewrite_rule('^dashboard/([^/]+)/?$', 'index.php?maneli_dashboard=1&maneli_dashboard_page=$matches[1]', 'top');
        add_rewrite_rule('^dashboard/([^/]+)/([^/]+)/?$', 'index.php?maneli_dashboard=1&maneli_dashboard_page=$matches[1]&maneli_dashboard_subpage=$matches[2]', 'top');
        
        // Flush rewrite rules if needed
        if (get_option('maneli_dashboard_rules_flushed') !== '3') {
            flush_rewrite_rules();
            update_option('maneli_dashboard_rules_flushed', '3');
        }
    }
    
    /**
     * Add query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'maneli_dashboard';
        $vars[] = 'maneli_dashboard_page';
        $vars[] = 'maneli_dashboard_subpage';
        return $vars;
    }
    
    /**
     * Handle dashboard requests
     */
    public function handle_dashboard_requests() {
        if (get_query_var('maneli_dashboard')) {
            $this->render_dashboard();
            exit;
        }
    }
    
    /**
     * مخفی کردن Admin Bar در صفحات داشبورد
     */
    public function hide_admin_bar_in_dashboard() {
        if (get_query_var('maneli_dashboard')) {
            // غیرفعال کردن کامل Admin Bar
            show_admin_bar(false);
            add_filter('show_admin_bar', '__return_false', 999);
            
            // حذف Admin Bar از DOM
            remove_action('wp_head', '_admin_bar_bump_cb');
            
            // غیرفعال کردن اسکریپت‌های Admin Bar
            add_action('wp_enqueue_scripts', function() {
                wp_dequeue_style('admin-bar');
                wp_deregister_style('admin-bar');
                wp_dequeue_script('admin-bar');
                wp_deregister_script('admin-bar');
            }, 999);
        }
    }
    
    /**
     * Enqueue dashboard assets
     */
    public function enqueue_dashboard_assets() {
        if (get_query_var('maneli_dashboard')) {
            // Enqueue CSS
            wp_enqueue_style('maneli-fonts', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-fonts.css', [], '1.0.0');
            wp_enqueue_style('maneli-bootstrap', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/bootstrap/css/bootstrap.rtl.min.css', ['maneli-fonts'], '5.3.0');
            wp_enqueue_style('maneli-styles', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/styles.css', ['maneli-bootstrap'], '1.0.0');
            
            // Line Awesome Complete - فایل CSS کامل و مستقل
            wp_enqueue_style('maneli-line-awesome-complete', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-line-awesome-complete.css', [], '1.0.0');
            
            wp_enqueue_style('maneli-waves', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/node-waves/waves.min.css', [], '1.0.0');
            wp_enqueue_style('maneli-simplebar', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/simplebar/simplebar.min.css', [], '1.0.0');
            wp_enqueue_style('maneli-flatpickr', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/flatpickr/flatpickr.min.css', [], '1.0.0');
            wp_enqueue_style('maneli-pickr', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/@simonwep/pickr/themes/nano.min.css', [], '1.0.0');
            wp_enqueue_style('maneli-choices', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/choices.js/public/assets/styles/choices.min.css', [], '1.0.0');
            wp_enqueue_style('maneli-autocomplete', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/@tarekraafat/autocomplete.js/css/autoComplete.css', [], '1.0.0');
            wp_enqueue_style('maneli-persian-datepicker', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/persianDatepicker-default.css', [], '1.0.0');
            // Force RTL and Persian Font
            wp_enqueue_style('maneli-rtl-force', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-rtl-force.css', ['maneli-styles', 'maneli-bootstrap'], '1.0.0');
            // Dashboard Additional Fixes
            wp_enqueue_style('maneli-dashboard-fix', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-dashboard-fix.css', ['maneli-rtl-force'], '1.0.0');
            // Loader Fix - Prevent infinite loading
            wp_enqueue_style('maneli-loader-fix', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-loader-fix.css', ['maneli-dashboard-fix'], '1.0.0');
            
            // Enqueue JS
            wp_enqueue_script('jquery');
            wp_enqueue_script('maneli-choices', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/choices.js/public/assets/scripts/choices.min.js', ['jquery'], '1.0.0', false);
            wp_enqueue_script('maneli-main', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/main.js', ['jquery'], '1.0.0', false);
            wp_enqueue_script('maneli-popper', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/@popperjs/core/umd/popper.min.js', ['jquery'], '2.11.0', true);
            
            wp_enqueue_script('maneli-bootstrap', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/bootstrap/js/bootstrap.bundle.min.js', ['jquery', 'maneli-popper'], '5.3.0', true);
            wp_enqueue_script('maneli-node-waves', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/node-waves/waves.min.js', ['jquery'], '1.0.0', true);
            wp_enqueue_script('maneli-simplebar-lib', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/simplebar/simplebar.min.js', ['jquery'], '1.0.0', true);
            wp_enqueue_script('maneli-simplebar', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/simplebar.js', ['maneli-simplebar-lib'], '1.0.0', true);
            wp_enqueue_script('maneli-autocomplete', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/@tarekraafat/autocomplete.js/autoComplete.min.js', ['jquery'], '1.0.0', true);
            wp_enqueue_script('maneli-pickr', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/@simonwep/pickr/pickr.es5.min.js', ['jquery'], '1.0.0', true);
            wp_enqueue_script('maneli-flatpickr', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/flatpickr/flatpickr.min.js', ['jquery'], '1.0.0', true);
            wp_enqueue_script('maneli-apexcharts', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/apexcharts/apexcharts.min.js', ['jquery'], '1.0.0', true);
            wp_enqueue_script('maneli-sales-dashboard', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/sales-dashboard.js', ['maneli-apexcharts'], '1.0.0', true);
            wp_enqueue_script('maneli-sticky', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/sticky.js', ['jquery'], '1.0.0', true);
            wp_enqueue_script('maneli-defaultmenu', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/defaultmenu.min.js', ['jquery'], '1.0.0', true);
            wp_enqueue_script('maneli-custom', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/custom.js', ['jquery', 'maneli-bootstrap'], '1.0.0', true);
            wp_enqueue_script('maneli-custom-switcher', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/custom-switcher.min.js', ['jquery'], '1.0.0', true);
            wp_enqueue_script('maneli-persian-datepicker', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/persianDatepicker.min.js', ['jquery'], '1.0.0', true);
            wp_enqueue_script('maneli-dashboard', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/dashboard.js', ['jquery'], '1.0.0', true);
            
            // Product Editor Script (for product-editor and products pages)
            $page = get_query_var('maneli_dashboard_page');
            if ($page === 'product-editor' || $page === 'products') {
                wp_enqueue_script('maneli-product-editor', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/admin/product-editor.js', ['jquery'], '1.0.0', true);
                wp_localize_script('maneli-product-editor', 'maneliAdminProductEditor', [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('maneli_product_data_nonce'),
                    'text' => [
                        'ajax_error' => esc_html__('Server error. Please try again.', 'maneli-car-inquiry'),
                        'saved' => esc_html__('Saved', 'maneli-car-inquiry'),
                        'error' => esc_html__('Error', 'maneli-car-inquiry'),
                    ]
                ]);
            }
            
            // Localize script for AJAX
            wp_localize_script('maneli-dashboard', 'maneli_ajax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('maneli_dashboard_nonce'),
                'login_url' => home_url('/login'),
                'dashboard_url' => home_url('/dashboard')
            ]);
        }
    }
    
    /**
     * Render dashboard
     */
    private function render_dashboard() {
        $page = get_query_var('maneli_dashboard_page');
        $subpage = get_query_var('maneli_dashboard_subpage');
        
        // Check if user is logged in
        if (!$this->is_user_logged_in() && $page !== 'login') {
            wp_redirect(home_url('/login'));
            exit;
        }
        
        // Route to appropriate page
        if ($page === 'login') {
            $this->render_login_page();
        } elseif ($page === 'logout') {
            $this->handle_logout();
        } else {
            $this->render_main_dashboard($page, $subpage);
        }
    }
    
    /**
     * Check if user is logged in to dashboard
     */
    private function is_user_logged_in() {
        // If user is logged in to WordPress, allow access
        if (is_user_logged_in()) {
            return true;
        }
        
        // Otherwise check session
        $this->maybe_start_session();
        return isset($_SESSION['maneli_dashboard_logged_in']) && $_SESSION['maneli_dashboard_logged_in'] === true;
    }
    
    /**
     * Start session if not already started
     */
    private function maybe_start_session() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
    }
    
    /**
     * Render login page
     */
    private function render_login_page() {
        $this->maybe_start_session();
        
        $title = 'ورود به داشبورد';
        $error_message = isset($_SESSION['maneli_error']) ? $_SESSION['maneli_error'] : '';
        $success_message = isset($_SESSION['maneli_success']) ? $_SESSION['maneli_success'] : '';
        
        // Clear messages after displaying
        unset($_SESSION['maneli_error']);
        unset($_SESSION['maneli_success']);
        
        include MANELI_INQUIRY_PLUGIN_PATH . 'templates/dashboard/login.php';
    }
    
    /**
     * Render main dashboard
     */
    private function render_main_dashboard($page = '', $subpage = '') {
        $user = $this->get_current_user();
        $menu_items = $this->get_menu_items();
        
        include MANELI_INQUIRY_PLUGIN_PATH . 'templates/dashboard/main.php';
    }
    
    /**
     * Get current user
     */
    private function get_current_user() {
        // If WordPress user is logged in, use their info
        if (is_user_logged_in()) {
            $wp_user = wp_get_current_user();
            return [
                'name' => $wp_user->display_name,
                'phone' => get_user_meta($wp_user->ID, 'billing_phone', true) ?: '',
                'role' => 'admin'
            ];
        }
        
        // Otherwise use session data
        $this->maybe_start_session();
        return [
            'name' => $_SESSION['maneli_user_name'] ?? 'کاربر',
            'phone' => $_SESSION['maneli_user_phone'] ?? '',
            'role' => $_SESSION['maneli_user_role'] ?? 'user'
        ];
    }
    
    /**
     * Get menu items
     */
    private function get_menu_items() {
        return [
            [
                'title' => 'داشبورد',
                'url' => home_url('/dashboard'),
                'icon' => 'ri-dashboard-3-line',
                'active' => true
            ],
            [
                'title' => 'استعلامات',
                'url' => home_url('/dashboard/inquiries'),
                'icon' => 'ri-file-list-line',
                'active' => false
            ],
            [
                'title' => 'گزارشات',
                'url' => home_url('/dashboard/reports'),
                'icon' => 'ri-bar-chart-line',
                'active' => false
            ],
            [
                'title' => 'تنظیمات',
                'url' => home_url('/dashboard/settings'),
                'icon' => 'ri-settings-3-line',
                'active' => false
            ]
        ];
    }
    
    /**
     * Handle dashboard login
     */
    public function handle_dashboard_login() {
        check_ajax_referer('maneli_dashboard_nonce', 'nonce');
        
        $login_type = sanitize_text_field($_POST['login_type'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $password = sanitize_text_field($_POST['password'] ?? '');
        $sms_code = sanitize_text_field($_POST['sms_code'] ?? '');
        
        if ($login_type === 'sms') {
            $this->handle_sms_login($phone, $sms_code);
        } else {
            $this->handle_password_login($phone, $password);
        }
    }
    
    /**
     * Handle SMS login
     */
    private function handle_sms_login($phone, $sms_code) {
        $this->maybe_start_session();
        
        // Verify SMS code
        if ($this->verify_sms_code($phone, $sms_code)) {
            $this->set_user_session($phone, 'کاربر');
            wp_send_json_success(['redirect' => home_url('/dashboard')]);
        } else {
            wp_send_json_error(['message' => 'کد تایید نامعتبر است']);
        }
    }
    
    /**
     * Handle password login
     */
    private function handle_password_login($phone, $password) {
        // Verify password (implement your logic here)
        if ($this->verify_password($phone, $password)) {
            $this->set_user_session($phone, 'کاربر');
            wp_send_json_success(['redirect' => home_url('/dashboard')]);
        } else {
            wp_send_json_error(['message' => 'رمز عبور نامعتبر است']);
        }
    }
    
    /**
     * Send SMS code (OTP)
     */
    public function handle_send_sms_code() {
        check_ajax_referer('maneli_dashboard_nonce', 'nonce');
        
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        
        // Validate phone number
        if (!preg_match('/^09\d{9}$/', $phone)) {
            wp_send_json_error(['message' => 'شماره موبایل نامعتبر است']);
            return;
        }
        
        $this->maybe_start_session();
        
        // Check resend delay
        $options = get_option('maneli_inquiry_all_options', []);
        $resend_delay = intval($options['otp_resend_delay'] ?? 60);
        
        if (isset($_SESSION['maneli_sms_time'])) {
            $time_elapsed = time() - $_SESSION['maneli_sms_time'];
            if ($time_elapsed < $resend_delay) {
                $wait_time = $resend_delay - $time_elapsed;
                wp_send_json_error(['message' => "لطفاً $wait_time ثانیه صبر کنید"]);
                return;
            }
        }
        
        // Generate 4-digit OTP code
        $code = wp_rand(1000, 9999);
        
        // Store code in session
        $_SESSION['maneli_sms_code'] = $code;
        $_SESSION['maneli_sms_phone'] = $phone;
        $_SESSION['maneli_sms_time'] = time();
        
        // Get OTP pattern code from settings
        $otp_pattern = $options['otp_pattern_code'] ?? '';
        
        // Send SMS using existing SMS handler
        $sms_handler = new Maneli_SMS_Handler();
        
        // Debug logging
        error_log('OTP Send Attempt - Phone: ' . $phone . ', Code: ' . $code);
        error_log('OTP Pattern: ' . ($otp_pattern ? $otp_pattern : 'NOT SET'));
        
        if (!empty($otp_pattern)) {
            // Use pattern-based SMS (MeliPayamak)
            // متغیرها در پترن: {0} = کد OTP
            error_log('Sending OTP via Pattern - Body ID: ' . $otp_pattern);
            error_log('Pattern Parameters: ' . json_encode([$code]));
            
            $result = $sms_handler->send_pattern(
                $otp_pattern,      // bodyId (Pattern Code)
                $phone,            // recipient
                [$code]            // parameters - {0} = OTP code
            );
            
            // اگر پترن کار نکرد، fallback به SMS معمولی
            if (!$result) {
                error_log('Pattern failed, trying regular SMS as fallback...');
                $message = "کد تایید مانلی خودرو: " . $code;
                $result = $sms_handler->send_sms($phone, $message);
            }
        } else {
            // Fallback to regular SMS if pattern not configured
            error_log('Sending OTP via Regular SMS (Pattern not configured)');
            $message = "کد تایید شما: " . $code;
            $result = $sms_handler->send_sms($phone, $message);
        }
        
        error_log('OTP Send Result: ' . ($result ? 'SUCCESS' : 'FAILED'));
        
        if ($result) {
            wp_send_json_success(['message' => 'کد تایید ارسال شد']);
        } else {
            // بررسی اینکه آیا تنظیمات SMS وجود دارد یا نه
            $sms_username = $options['sms_username'] ?? '';
            $sms_password = $options['sms_password'] ?? '';
            
            if (empty($sms_username) || empty($sms_password)) {
                wp_send_json_error(['message' => 'تنظیمات SMS پیکربندی نشده است. لطفاً از بخش تنظیمات، اطلاعات پنل پیامک را وارد کنید.']);
            } elseif (empty($otp_pattern)) {
                wp_send_json_error(['message' => 'پترن OTP تنظیم نشده است. لطفاً از بخش تنظیمات → احراز هویت، کد پترن را وارد کنید.']);
            } else {
                wp_send_json_error(['message' => 'خطا در ارسال پیامک. لطفاً اطلاعات پنل پیامک و پترن را بررسی کنید.']);
            }
        }
    }
    
    /**
     * Verify SMS code (OTP)
     */
    private function verify_sms_code($phone, $code) {
        $this->maybe_start_session();
        
        if (!isset($_SESSION['maneli_sms_code']) || 
            !isset($_SESSION['maneli_sms_phone']) || 
            !isset($_SESSION['maneli_sms_time'])) {
            return false;
        }
        
        // Get OTP expiry time from settings
        $options = get_option('maneli_inquiry_all_options', []);
        $expiry_minutes = intval($options['otp_expiry_minutes'] ?? 5);
        $expiry_seconds = $expiry_minutes * 60;
        
        // Check if code is expired
        if (time() - $_SESSION['maneli_sms_time'] > $expiry_seconds) {
            return false;
        }
        
        return $_SESSION['maneli_sms_code'] == $code && 
               $_SESSION['maneli_sms_phone'] == $phone;
    }
    
    /**
     * Verify password
     */
    private function verify_password($phone, $password) {
        // Implement your password verification logic here
        // For now, using a simple check - you should replace this with proper authentication
        $options = get_option('maneli_inquiry_all_options', []);
        $saved_password = $options['dashboard_password'] ?? 'admin123';
        return $password === $saved_password;
    }
    
    /**
     * Set user session
     */
    private function set_user_session($phone, $name) {
        $this->maybe_start_session();
        
        $_SESSION['maneli_dashboard_logged_in'] = true;
        $_SESSION['maneli_user_phone'] = $phone;
        $_SESSION['maneli_user_name'] = $name;
        $_SESSION['maneli_user_role'] = 'user';
    }
    
    /**
     * Handle logout
     */
    public function handle_logout() {
        $this->maybe_start_session();
        session_destroy();
        wp_redirect(home_url('/login'));
        exit;
    }
    
    /**
     * Handle AJAX logout
     */
    public function handle_dashboard_logout() {
        $this->maybe_start_session();
        session_destroy();
        wp_send_json_success(['redirect' => home_url('/login')]);
    }

    /**
     * Get total revenue from successful inquiries
     */
    public function get_total_revenue() {
        global $wpdb;

        $total = $wpdb->get_var("
            SELECT SUM(CAST(meta_value AS UNSIGNED))
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_type = 'inquiry'
            AND p.post_status = 'publish'
            AND pm.meta_key = 'inquiry_price'
        ");

        return $total ? $total : 0;
    }

    /**
     * Get count of successful inquiries
     */
    public function get_successful_inquiries_count() {
        $count = wp_count_posts('inquiry');
        return isset($count->publish) ? $count->publish : 0;
    }

    /**
     * Get count of pending inquiries
     */
    public function get_pending_inquiries_count() {
        global $wpdb;

        $pending_count = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'inquiry'
            AND p.post_status = 'publish'
            AND pm.meta_key = 'inquiry_status'
            AND pm.meta_value = 'pending'
        ");

        return $pending_count ? $pending_count : 0;
    }
}

