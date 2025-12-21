<?php
/**
 * Dashboard Handler Class
 * 
 * Handles dashboard pages, routing, and template rendering
 * 
 * @package Autopuzzle_Car_Inquiry/Includes
 * @author  Arsalan Arghavan
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Autopuzzle_Dashboard_Handler {
    
    /**
     * Instance of this class
     */
    private static $instance = null;

    /**
     * Whether the session cookie parameters have been configured.
     *
     * @var bool
     */
    private static $session_cookie_configured = false;
    
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
        add_action('init', [$this, 'configure_session_cookie'], 0);
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('wp', [$this, 'handle_dashboard_requests']);  // Changed from template_redirect to wp (runs after init)
        add_action('wp_enqueue_scripts', [$this, 'enqueue_dashboard_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_global_fonts'], 1);
        add_action('wp_ajax_maneli_dashboard_login', [$this, 'handle_dashboard_login']);
        add_action('wp_ajax_nopriv_maneli_dashboard_login', [$this, 'handle_dashboard_login']);
        add_action('wp_ajax_autopuzzle_dashboard_login', [$this, 'handle_dashboard_login']);
        add_action('wp_ajax_nopriv_autopuzzle_dashboard_login', [$this, 'handle_dashboard_login']);
        add_action('wp_ajax_maneli_dashboard_logout', [$this, 'handle_dashboard_logout']);
        add_action('wp_ajax_autopuzzle_dashboard_logout', [$this, 'handle_dashboard_logout']);
        add_action('wp_ajax_maneli_send_sms_code', [$this, 'handle_send_sms_code']);
        add_action('wp_ajax_nopriv_maneli_send_sms_code', [$this, 'handle_send_sms_code']);
        add_action('wp_ajax_autopuzzle_send_sms_code', [$this, 'handle_send_sms_code']);
        add_action('wp_ajax_nopriv_autopuzzle_send_sms_code', [$this, 'handle_send_sms_code']);
        
        // New unified login/registration AJAX handlers
        add_action('wp_ajax_maneli_send_otp', [$this, 'handle_send_otp']);
        add_action('wp_ajax_nopriv_maneli_send_otp', [$this, 'handle_send_otp']);
        add_action('wp_ajax_autopuzzle_send_otp', [$this, 'handle_send_otp']);
        add_action('wp_ajax_nopriv_autopuzzle_send_otp', [$this, 'handle_send_otp']);
        add_action('wp_ajax_maneli_verify_otp', [$this, 'handle_verify_otp']);
        add_action('wp_ajax_nopriv_maneli_verify_otp', [$this, 'handle_verify_otp']);
        add_action('wp_ajax_autopuzzle_verify_otp', [$this, 'handle_verify_otp']);
        add_action('wp_ajax_nopriv_autopuzzle_verify_otp', [$this, 'handle_verify_otp']);
        add_action('wp_ajax_maneli_create_password', [$this, 'handle_create_password']);
        add_action('wp_ajax_nopriv_maneli_create_password', [$this, 'handle_create_password']);
        add_action('wp_ajax_autopuzzle_create_password', [$this, 'handle_create_password']);
        add_action('wp_ajax_nopriv_autopuzzle_create_password', [$this, 'handle_create_password']);
        add_action('wp_ajax_maneli_password_login', [$this, 'handle_password_login']);
        add_action('wp_ajax_nopriv_maneli_password_login', [$this, 'handle_password_login']);
        add_action('wp_ajax_autopuzzle_password_login', [$this, 'handle_password_login']);
        add_action('wp_ajax_nopriv_autopuzzle_password_login', [$this, 'handle_password_login']);
        
        // مخفی کردن Admin Bar برای همه نقش‌های افزونه در همه صفحات
        add_action('init', [$this, 'hide_admin_bar_for_plugin_roles'], 999);
        add_filter('show_admin_bar', [$this, 'disable_admin_bar_for_plugin_roles'], 999);
        add_action('template_redirect', [$this, 'hide_admin_bar_in_dashboard']);
        
        // Locale override based on dashboard language preference
        add_filter('determine_locale', [$this, 'maybe_override_locale']);
        
        // Show notice if rewrite rules need flushing
        if (get_option('autopuzzle_dashboard_rules_flushed') !== '4') {
            add_action('admin_notices', [$this, 'show_rewrite_flush_notice']);
        }
    }
    
    /**
     * Show admin notice to flush rewrite rules
     */
    public function show_rewrite_flush_notice() {
        if (current_user_can('manage_options')) {
            ?>
            <div class="notice notice-info is-dismissible">
                <p><?php esc_html_e('AutoPuzzle Car Inquiry: Please save the Settings once to update rewrite rules.', 'autopuzzle'); ?></p>
            </div>
            <?php
        }
    }
    
    /**
     * Enqueue global fonts for all plugin pages
     */
    public function enqueue_global_fonts() {
        // Only enqueue fonts on dashboard pages to avoid 404 errors on frontend
        if (!get_query_var('autopuzzle_dashboard')) {
            return;
        }
        
        $font_css_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/css/autopuzzle-fonts.css';
        if (file_exists($font_css_path)) {
            wp_enqueue_style('autopuzzle-fonts', AUTOPUZZLE_PLUGIN_URL . 'assets/css/autopuzzle-fonts.css', [], '1.0.0');
        }
        
        $rtl_css_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/css/autopuzzle-rtl-force.css';
        if (file_exists($rtl_css_path)) {
            wp_enqueue_style('autopuzzle-rtl-force', AUTOPUZZLE_PLUGIN_URL . 'assets/css/autopuzzle-rtl-force.css', ['autopuzzle-fonts'], '1.0.0');
        }
        
        $dashboard_fix_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/css/autopuzzle-dashboard-fix.css';
        if (file_exists($dashboard_fix_path)) {
            wp_enqueue_style('autopuzzle-dashboard-fix', AUTOPUZZLE_PLUGIN_URL . 'assets/css/autopuzzle-dashboard-fix.css', ['autopuzzle-rtl-force'], '1.0.0');
        }
        
        $loader_fix_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/css/autopuzzle-loader-fix.css';
        if (file_exists($loader_fix_path)) {
            wp_enqueue_style('autopuzzle-loader-fix', AUTOPUZZLE_PLUGIN_URL . 'assets/css/autopuzzle-loader-fix.css', ['autopuzzle-dashboard-fix'], '1.0.0');
        }
        
        $sidebar_fixes_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/css/sidebar-fixes.css';
        if (file_exists($sidebar_fixes_path)) {
            wp_enqueue_style('autopuzzle-sidebar-fixes', AUTOPUZZLE_PLUGIN_URL . 'assets/css/sidebar-fixes.css', ['autopuzzle-loader-fix'], filemtime($sidebar_fixes_path));
        }
    }
    
    /**
     * Add rewrite rules for dashboard
     */
    public function add_rewrite_rules() {
        add_rewrite_rule('^login/?$', 'index.php?autopuzzle_dashboard=1&autopuzzle_dashboard_page=login', 'top');
        add_rewrite_rule('^logout/?$', 'index.php?autopuzzle_dashboard=1&autopuzzle_dashboard_page=logout', 'top');
        add_rewrite_rule('^verify-otp/?$', 'index.php?autopuzzle_dashboard=1&autopuzzle_dashboard_page=verify-otp', 'top');
        add_rewrite_rule('^create-password/?$', 'index.php?autopuzzle_dashboard=1&autopuzzle_dashboard_page=create-password', 'top');
        add_rewrite_rule('^dashboard/?$', 'index.php?autopuzzle_dashboard=1', 'top');
        add_rewrite_rule('^dashboard/([^/]+)/?$', 'index.php?autopuzzle_dashboard=1&autopuzzle_dashboard_page=$matches[1]', 'top');
        add_rewrite_rule('^dashboard/([^/]+)/([^/]+)/?$', 'index.php?autopuzzle_dashboard=1&autopuzzle_dashboard_page=$matches[1]&autopuzzle_dashboard_subpage=$matches[2]', 'top');
        
        // Flush rewrite rules only once during activation
        if (get_option('autopuzzle_dashboard_rules_flushed') !== '4') {
            add_action('init', function() {
                flush_rewrite_rules();
                update_option('autopuzzle_dashboard_rules_flushed', '4');
            }, 999);
        }
    }
    
    /**
     * Add query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'autopuzzle_dashboard';
        $vars[] = 'autopuzzle_dashboard_page';
        $vars[] = 'autopuzzle_dashboard_subpage';
        $vars[] = 'view_user';
        $vars[] = 'edit_user';
        $vars[] = 'view_expert';
        $vars[] = 'edit_expert';
        $vars[] = 'view_payment';
        return $vars;
    }
    
    /**
     * Handle dashboard requests
     */
    public function handle_dashboard_requests() {
        if (get_query_var('autopuzzle_dashboard')) {
            // Start session early before any output or redirects
            $this->maybe_start_session();
            $this->render_dashboard();
            exit;
        }
    }
    
    /**
     * مخفی کردن Admin Bar برای همه نقش‌های افزونه در همه صفحات
     */
    public function hide_admin_bar_for_plugin_roles() {
        // بررسی آیا کاربر وارد شده است
        if (!is_user_logged_in()) {
            return;
        }
        
        // دریافت نقش‌های کاربر
        $current_user = wp_get_current_user();
        $user_roles = (array) $current_user->roles;
        
        // نقش‌های افزونه
        $plugin_roles = ['autopuzzle_admin', 'autopuzzle_expert', 'customer'];
        
        // بررسی آیا کاربر یکی از نقش‌های افزونه را دارد
        $has_plugin_role = false;
        foreach ($plugin_roles as $role) {
            if (in_array($role, $user_roles, true)) {
                $has_plugin_role = true;
                break;
            }
        }
        
        // اگر کاربر یکی از نقش‌های افزونه را دارد، Admin Bar را مخفی کن
        if ($has_plugin_role) {
            // غیرفعال کردن کامل Admin Bar
            show_admin_bar(false);
            
            // حذف Admin Bar از DOM
            remove_action('wp_head', '_admin_bar_bump_cb');
            remove_action('admin_head', '_admin_bar_bump_cb');
            
            // غیرفعال کردن اسکریپت‌های Admin Bar
            add_action('wp_enqueue_scripts', function() {
                wp_dequeue_style('admin-bar');
                wp_deregister_style('admin-bar');
                wp_dequeue_script('admin-bar');
                wp_deregister_script('admin-bar');
            }, 999);
            
            add_action('admin_enqueue_scripts', function() {
                wp_dequeue_style('admin-bar');
                wp_deregister_style('admin-bar');
                wp_dequeue_script('admin-bar');
                wp_deregister_script('admin-bar');
            }, 999);
        }
    }
    
    /**
     * فیلتر برای غیرفعال کردن Admin Bar برای نقش‌های افزونه
     */
    public function disable_admin_bar_for_plugin_roles($show) {
        // بررسی آیا کاربر وارد شده است
        if (!is_user_logged_in()) {
            return $show;
        }
        
        // دریافت نقش‌های کاربر
        $current_user = wp_get_current_user();
        $user_roles = (array) $current_user->roles;
        
        // نقش‌های افزونه
        $plugin_roles = ['autopuzzle_admin', 'autopuzzle_expert', 'customer'];
        
        // بررسی آیا کاربر یکی از نقش‌های افزونه را دارد
        foreach ($plugin_roles as $role) {
            if (in_array($role, $user_roles, true)) {
                return false; // غیرفعال کردن Admin Bar
            }
        }
        
        return $show;
    }
    
    /**
     * مخفی کردن Admin Bar در صفحات داشبورد
     */
    public function hide_admin_bar_in_dashboard() {
        if (get_query_var('autopuzzle_dashboard')) {
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
        if (get_query_var('autopuzzle_dashboard')) {
            // Enqueue CSS - check if font CSS exists
            $font_css_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/css/autopuzzle-fonts.css';
            $font_deps = [];
            if (file_exists($font_css_path)) {
                wp_enqueue_style('autopuzzle-fonts', AUTOPUZZLE_PLUGIN_URL . 'assets/css/autopuzzle-fonts.css', [], '1.0.0');
                $font_deps[] = 'autopuzzle-fonts';
            }
            $preferred_language = $this->get_preferred_language_slug();

            $is_rtl = ($preferred_language === 'fa');
            $bootstrap_file = $is_rtl ? 'bootstrap.rtl.min.css' : 'bootstrap.min.css';

            wp_enqueue_style('autopuzzle-bootstrap', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/bootstrap/css/' . $bootstrap_file, $font_deps, '5.3.0');
            wp_enqueue_style('autopuzzle-styles', AUTOPUZZLE_PLUGIN_URL . 'assets/css/styles.css', ['autopuzzle-bootstrap'], '1.0.0');
            
            // Line Awesome Complete - فایل CSS کامل و مستقل
            $line_awesome_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/css/autopuzzle-line-awesome-complete.css';
            if (file_exists($line_awesome_path)) {
                wp_enqueue_style('autopuzzle-line-awesome-complete', AUTOPUZZLE_PLUGIN_URL . 'assets/css/autopuzzle-line-awesome-complete.css', [], '1.0.0');
            }
            
            wp_enqueue_style('autopuzzle-waves', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/node-waves/waves.min.css', [], '1.0.0');
            wp_enqueue_style('autopuzzle-simplebar', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/simplebar/simplebar.min.css', [], '1.0.0');
            wp_enqueue_style('autopuzzle-flatpickr', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/flatpickr/flatpickr.min.css', [], '1.0.0');
            wp_enqueue_style('autopuzzle-pickr', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/@simonwep/pickr/themes/nano.min.css', [], '1.0.0');
            wp_enqueue_style('autopuzzle-choices', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/choices.js/public/assets/styles/choices.min.css', [], '1.0.0');
            wp_enqueue_style('autopuzzle-autocomplete', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/@tarekraafat/autocomplete.js/css/autoComplete.css', [], '1.0.0');

            if (function_exists('autopuzzle_enqueue_persian_datepicker')) {
                autopuzzle_enqueue_persian_datepicker();
            }

            // Force RTL and Persian Font - check if file exists
            $base_fix_deps = ['autopuzzle-styles', 'autopuzzle-bootstrap'];
            $dashboard_fix_deps = $base_fix_deps;

            if ($is_rtl) {
            $rtl_css_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/css/autopuzzle-rtl-force.css';
            if (file_exists($rtl_css_path)) {
                    wp_enqueue_style('autopuzzle-rtl-force', AUTOPUZZLE_PLUGIN_URL . 'assets/css/autopuzzle-rtl-force.css', $base_fix_deps, '1.0.0');
                $dashboard_fix_deps = ['autopuzzle-rtl-force'];
                }
            }
            
            // Dashboard Additional Fixes - check if file exists
            $dashboard_fix_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/css/autopuzzle-dashboard-fix.css';
            if (file_exists($dashboard_fix_path)) {
                wp_enqueue_style('autopuzzle-dashboard-fix', AUTOPUZZLE_PLUGIN_URL . 'assets/css/autopuzzle-dashboard-fix.css', $dashboard_fix_deps, '1.0.0');
                $loader_fix_deps = ['autopuzzle-dashboard-fix'];
            } else {
                $loader_fix_deps = $dashboard_fix_deps;
            }
            
            // Loader Fix - Prevent infinite loading - check if file exists
            $loader_fix_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/css/autopuzzle-loader-fix.css';
            if (file_exists($loader_fix_path)) {
                wp_enqueue_style('autopuzzle-loader-fix', AUTOPUZZLE_PLUGIN_URL . 'assets/css/autopuzzle-loader-fix.css', $loader_fix_deps, '1.0.0');
            }
            // Custom styles for status indicators and expert names
            wp_enqueue_style('autopuzzle-custom', AUTOPUZZLE_PLUGIN_URL . 'assets/css/autopuzzle-custom.css', ['autopuzzle-loader-fix'], filemtime(AUTOPUZZLE_PLUGIN_PATH . 'assets/css/autopuzzle-custom.css'));
            // Inline styles replacement CSS
            wp_enqueue_style('autopuzzle-inline-styles', AUTOPUZZLE_PLUGIN_URL . 'assets/css/autopuzzle-inline-styles.css', [], filemtime(AUTOPUZZLE_PLUGIN_PATH . 'assets/css/autopuzzle-inline-styles.css'));
            
            // TEST: Load test script first to verify enqueue is working
            wp_enqueue_script('autopuzzle-test-loader', AUTOPUZZLE_PLUGIN_URL . 'assets/js/frontend/autopuzzle-test-loader.js', ['jquery'], '1.0.0', false);
            
            // Enqueue JS - Ensure jQuery is loaded
            // Don't deregister, use WordPress built-in jQuery
            wp_enqueue_script('jquery');
            wp_enqueue_script('autopuzzle-choices', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/choices.js/public/assets/scripts/choices.min.js', ['jquery'], '1.0.0', false);
            wp_enqueue_script('autopuzzle-main', AUTOPUZZLE_PLUGIN_URL . 'assets/js/main.js', ['jquery'], '1.0.0', false);
            wp_enqueue_script('autopuzzle-popper', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/@popperjs/core/umd/popper.min.js', ['jquery'], '2.11.0', true);
            
            wp_enqueue_script('autopuzzle-bootstrap', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/bootstrap/js/bootstrap.bundle.min.js', ['jquery', 'autopuzzle-popper'], '5.3.0', true);
            wp_enqueue_script('autopuzzle-node-waves', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/node-waves/waves.min.js', ['jquery'], '1.0.0', true);
            wp_enqueue_script('autopuzzle-simplebar-lib', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/simplebar/simplebar.min.js', ['jquery'], '1.0.0', true);
            wp_enqueue_script('autopuzzle-simplebar', AUTOPUZZLE_PLUGIN_URL . 'assets/js/simplebar.js', ['autopuzzle-simplebar-lib'], '1.0.0', true);
            wp_enqueue_script('autopuzzle-autocomplete', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/@tarekraafat/autocomplete.js/autoComplete.min.js', ['jquery'], '1.0.0', true);
            wp_enqueue_script('autopuzzle-pickr', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/@simonwep/pickr/pickr.es5.min.js', ['jquery'], '1.0.0', true);
            wp_enqueue_script('autopuzzle-flatpickr', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/flatpickr/flatpickr.min.js', ['jquery'], '1.0.0', true);
            wp_enqueue_script('autopuzzle-sticky', AUTOPUZZLE_PLUGIN_URL . 'assets/js/sticky.js', ['jquery'], '1.0.0', true);
            wp_enqueue_script('autopuzzle-defaultmenu', AUTOPUZZLE_PLUGIN_URL . 'assets/js/defaultmenu.min.js', ['jquery'], '1.0.0', true);
            wp_enqueue_script('autopuzzle-custom', AUTOPUZZLE_PLUGIN_URL . 'assets/js/custom.js', ['jquery', 'autopuzzle-bootstrap'], '1.0.0', true);
            wp_enqueue_script('autopuzzle-custom-switcher', AUTOPUZZLE_PLUGIN_URL . 'assets/js/custom-switcher.min.js', ['jquery'], '1.0.0', true);
            // autopuzzle_enqueue_persian_datepicker() handles Persian datepicker assets when needed.
            // Real-time notifications
            wp_enqueue_script('autopuzzle-notifications', AUTOPUZZLE_PLUGIN_URL . 'assets/js/notifications.js', ['jquery'], '1.0.0', true);
            
            // Enqueue CAPTCHA scripts if enabled
            if (class_exists('Autopuzzle_Captcha_Helper') && Autopuzzle_Captcha_Helper::is_enabled()) {
                $captcha_type = Autopuzzle_Captcha_Helper::get_captcha_type();
                $site_key = Autopuzzle_Captcha_Helper::get_site_key($captcha_type);
                
                if (!empty($captcha_type) && !empty($site_key)) {
                    Autopuzzle_Captcha_Helper::enqueue_script($captcha_type, $site_key);
                    
                    // Enqueue our CAPTCHA handler script
                    wp_enqueue_script(
                        'autopuzzle-captcha',
                        AUTOPUZZLE_PLUGIN_URL . 'assets/js/captcha.js',
                        ['jquery'],
                        filemtime(AUTOPUZZLE_PLUGIN_PATH . 'assets/js/captcha.js'),
                        true
                    );
                    
                // Localize script with CAPTCHA config and error messages
                wp_localize_script('autopuzzle-captcha', 'maneliCaptchaConfig', [
                    'enabled' => true,
                    'type' => $captcha_type,
                    'siteKey' => $site_key,
                    'strings' => [
                        'verification_failed' => esc_html__('CAPTCHA verification failed. Please complete the CAPTCHA challenge and try again.', 'autopuzzle'),
                        'error_title' => esc_html__('Verification Failed', 'autopuzzle'),
                        'try_again' => esc_html__('Try Again', 'autopuzzle'),
                        'loading' => esc_html__('Verifying...', 'autopuzzle'),
                        'network_error' => esc_html__('Network error occurred. Please check your internet connection and try again.', 'autopuzzle'),
                        'script_not_loaded' => esc_html__('CAPTCHA script could not be loaded. Please refresh the page and try again.', 'autopuzzle'),
                        'token_expired' => esc_html__('CAPTCHA token has expired. Please complete the challenge again.', 'autopuzzle')
                    ]
                ]);
                }
            }
            // Localize script for notifications
            wp_localize_script('autopuzzle-notifications', 'autopuzzle_ajax', array(
                'url' => admin_url('admin-ajax.php'),
                'notifications_nonce' => wp_create_nonce('autopuzzle_notifications_nonce'),
                'ajax_url' => admin_url('admin-ajax.php'), // Also add for compatibility
                'nonce' => wp_create_nonce('autopuzzle_ajax_nonce'), // Add nonce for logging
            ));
            
            // Logging tracker - intercept console logs and track user actions
            wp_enqueue_script('autopuzzle-logging-tracker', AUTOPUZZLE_PLUGIN_URL . 'assets/js/logging-tracker.js', ['jquery'], '1.0.0', true);
            wp_localize_script('autopuzzle-logging-tracker', 'maneliAjax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('autopuzzle_ajax_nonce'),
            ));
            wp_localize_script('autopuzzle-logging-tracker', 'maneliLoggingSettings', array(
                'enable_logging_system' => Autopuzzle_Options_Helper::is_option_enabled('enable_logging_system', false),
                'log_console_messages' => Autopuzzle_Options_Helper::is_option_enabled('log_console_messages', false),
                'enable_user_logging' => Autopuzzle_Options_Helper::is_option_enabled('enable_user_logging', false),
                'log_button_clicks' => Autopuzzle_Options_Helper::is_option_enabled('log_button_clicks', false),
                'log_form_submissions' => Autopuzzle_Options_Helper::is_option_enabled('log_form_submissions', false),
                'log_ajax_calls' => Autopuzzle_Options_Helper::is_option_enabled('log_ajax_calls', false),
                'log_page_views' => Autopuzzle_Options_Helper::is_option_enabled('log_page_views', false),
            ));
            // wp_enqueue_script('autopuzzle-dashboard', AUTOPUZZLE_PLUGIN_URL . 'assets/js/dashboard.js', ['jquery'], '1.0.0', true);
            // Dashboard.js file does not exist - commented out
            
            // Page-specific scripts
            $page = get_query_var('autopuzzle_dashboard_page');
            $subpage = get_query_var('autopuzzle_dashboard_subpage');
            
            // Reports page - Chart.js for charts
            if ($page === 'reports') {
                $chartjs_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/libs/chart.js/chart.umd.js';
                if (file_exists($chartjs_path)) {
                    wp_enqueue_script('chartjs', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/chart.js/chart.umd.js', ['jquery'], '4.4.0', false);
                } else {
                    // Fallback to CDN
                    wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', ['jquery'], '4.4.0', false);
                }
            }
            
            // Visitor Statistics page - ApexCharts and scripts
            if ($page === 'visitor-statistics') {
                // ApexCharts
                $apexcharts_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/libs/apexcharts/apexcharts.min.js';
                if (file_exists($apexcharts_path)) {
                    wp_enqueue_script('apexcharts', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/apexcharts/apexcharts.min.js', ['jquery'], '3.44.0', true);
                    $apexcharts_css_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/libs/apexcharts/apexcharts.css';
                    if (file_exists($apexcharts_css_path)) {
                        wp_enqueue_style('apexcharts', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/apexcharts/apexcharts.css', [], '3.44.0');
                    }
                } else {
                    // Fallback to CDN
                    wp_enqueue_script('apexcharts', 'https://cdn.jsdelivr.net/npm/apexcharts@3.44.0/dist/apexcharts.min.js', ['jquery'], '3.44.0', true);
                    wp_enqueue_style('apexcharts', 'https://cdn.jsdelivr.net/npm/apexcharts@3.44.0/dist/apexcharts.css', [], '3.44.0');
                }
                
                // Persian Datepicker
                if (function_exists('autopuzzle_enqueue_persian_datepicker')) {
                    autopuzzle_enqueue_persian_datepicker();
                }
                
                // Flag icons for country display
                wp_enqueue_style('autopuzzle-flag-icons', 'https://cdn.jsdelivr.net/npm/flag-icon-css@3.5.0/css/flag-icon.min.css', [], '3.5.0');
                
                // Visitor Statistics Dashboard Script
                $dashboard_js_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/js/admin/visitor-statistics-dashboard.js';
                if (file_exists($dashboard_js_path)) {
                    wp_enqueue_script('autopuzzle-visitor-statistics-dashboard', AUTOPUZZLE_PLUGIN_URL . 'assets/js/admin/visitor-statistics-dashboard.js', ['jquery', 'apexcharts'], filemtime($dashboard_js_path), true);
                    
                    // Localize script
                    // Handle date conversion from Jalali to Gregorian for queries
                    require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/functions.php';
                    
                    if (!function_exists('autopuzzle_jalali_to_gregorian')) {
                        function autopuzzle_jalali_to_gregorian($j_y, $j_m, $j_d) {
                            $j_y = (int)$j_y; $j_m = (int)$j_m; $j_d = (int)$j_d;
                            $jy = $j_y - 979; $jm = $j_m - 1; $jd = $j_d - 1;
                            $j_days_in_month = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];
                            $j_day_no = 365 * $jy + (int)($jy / 33) * 8 + (int)(($jy % 33 + 3) / 4);
                            for ($i = 0; $i < $jm; ++$i) {
                                $j_day_no += $j_days_in_month[$i];
                            }
                            $j_day_no += $jd;
                            $g_day_no = $j_day_no + 79;
                            $gy = 1600 + 400 * (int)($g_day_no / 146097);
                            $g_day_no = $g_day_no % 146097;
                            $leap = true;
                            if ($g_day_no >= 36525) {
                                $g_day_no--;
                                $gy += 100 * (int)($g_day_no / 36524);
                                $g_day_no = $g_day_no % 36524;
                                if ($g_day_no >= 365) {
                                    $g_day_no++;
                                } else {
                                    $leap = false;
                                }
                            }
                            $gy += 4 * (int)($g_day_no / 1461);
                            $g_day_no = $g_day_no % 1461;
                            if ($g_day_no >= 366) {
                                $leap = false;
                                $g_day_no--;
                                $gy += (int)($g_day_no / 365);
                                $g_day_no = $g_day_no % 365;
                            }
                            $g_days_in_month = [31, ($leap ? 29 : 28), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
                            $gm = 0;
                            while ($gm < 12 && $g_day_no >= $g_days_in_month[$gm]) {
                                $g_day_no -= $g_days_in_month[$gm];
                                $gm++;
                            }
                            return sprintf('%04d-%02d-%02d', $gy, $gm + 1, $g_day_no + 1);
                        }
                    }
                    
                    $start_date_input = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
                    $end_date_input   = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';
                    
                    $start_date = date('Y-m-d', strtotime('-30 days'));
                    $end_date   = date('Y-m-d');
                    
                    if ($start_date_input !== '') {
                        $normalized_start = function_exists('autopuzzle_normalize_jalali_date') ? autopuzzle_normalize_jalali_date($start_date_input) : null;
                        if ($normalized_start) {
                            $parts = explode('/', $normalized_start);
                            $start_date = autopuzzle_jalali_to_gregorian($parts[0], $parts[1], $parts[2]);
                        } else {
                            $english_start = function_exists('autopuzzle_convert_to_english_digits') ? autopuzzle_convert_to_english_digits($start_date_input) : $start_date_input;
                            $english_start = str_replace('/', '-', $english_start);
                            $start_timestamp = strtotime($english_start);
                            if ($start_timestamp !== false) {
                                $start_date = date('Y-m-d', $start_timestamp);
                            }
                        }
                    }
                    
                    if ($end_date_input !== '') {
                        $normalized_end = function_exists('autopuzzle_normalize_jalali_date') ? autopuzzle_normalize_jalali_date($end_date_input) : null;
                        if ($normalized_end) {
                            $parts = explode('/', $normalized_end);
                            $end_date = autopuzzle_jalali_to_gregorian($parts[0], $parts[1], $parts[2]);
                        } else {
                            $english_end = function_exists('autopuzzle_convert_to_english_digits') ? autopuzzle_convert_to_english_digits($end_date_input) : $end_date_input;
                            $english_end = str_replace('/', '-', $english_end);
                            $end_timestamp = strtotime($english_end);
                            if ($end_timestamp !== false) {
                                $end_date = date('Y-m-d', $end_timestamp);
                            }
                        }
                    }
                    
                    if (strtotime($start_date) > strtotime($end_date)) {
                        $tmp = $start_date;
                        $start_date = $end_date;
                        $end_date = $tmp;
                    }
                    
                    // Get daily stats for chart
                    require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-visitor-statistics.php';
                    $daily_stats = Autopuzzle_Visitor_Statistics::get_daily_visits($start_date, $end_date);
                    
                    // Convert dates to Jalali format
                    $preferred_locale_slug = $this->get_preferred_language_slug();

                    $wp_locale = function_exists('determine_locale') ? determine_locale() : (function_exists('get_locale') ? get_locale() : 'fa_IR');
                    if ($preferred_locale_slug === 'fa' && is_string($wp_locale) && stripos($wp_locale, 'en') === 0) {
                        $preferred_locale_slug = 'en';
                    }

                    // Get previous period daily stats
                    $days_diff = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24);
                    $prev_start_date = date('Y-m-d', strtotime($start_date . ' -' . ($days_diff + 1) . ' days'));
                    $prev_end_date = date('Y-m-d', strtotime($start_date . ' -1 day'));
                    $prev_daily_stats = Autopuzzle_Visitor_Statistics::get_daily_visits($prev_start_date, $prev_end_date);
                    $prev_stats = Autopuzzle_Visitor_Statistics::get_overall_stats($prev_start_date, $prev_end_date);
                    
                    if ($preferred_locale_slug === 'fa' && function_exists('autopuzzle_gregorian_to_jalali')) {
                        foreach ($daily_stats as &$stat) {
                            if (isset($stat->date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $stat->date)) {
                                $date_parts = explode('-', $stat->date);
                                $stat->date = autopuzzle_gregorian_to_jalali($date_parts[0], $date_parts[1], $date_parts[2], 'Y/m/d');
                            }
                        }
                        unset($stat);
                        
                        foreach ($prev_daily_stats as &$prev_stat) {
                            if (isset($prev_stat->date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $prev_stat->date)) {
                                $date_parts = explode('-', $prev_stat->date);
                                $prev_stat->date = autopuzzle_gregorian_to_jalali($date_parts[0], $date_parts[1], $date_parts[2], 'Y/m/d');
                            }
                        }
                        unset($prev_stat);
                    }
                    
                    wp_localize_script('autopuzzle-visitor-statistics-dashboard', 'maneliVisitorStats', [
                        'ajaxUrl' => admin_url('admin-ajax.php'),
                        'nonce' => wp_create_nonce('autopuzzle_visitor_stats_nonce'),
                        'startDate' => $start_date,
                        'endDate' => $end_date,
                        'dailyStats' => $daily_stats,
                        'prevDailyStats' => $prev_daily_stats,
                        'prevStats' => $prev_stats,
                        'locale' => $preferred_locale_slug,
                        'wpLocale' => $wp_locale,
                        'usePersianDigits' => ($preferred_locale_slug === 'fa'),
                        'countryNames' => Autopuzzle_Visitor_Statistics::get_country_translation_map(),
                        'countryFlagIcons' => Autopuzzle_Visitor_Statistics::get_country_flag_map(),
                        'browserNames' => Autopuzzle_Visitor_Statistics::get_browser_translation_map(),
                        'osNames' => Autopuzzle_Visitor_Statistics::get_os_translation_map(),
                        'deviceNames' => Autopuzzle_Visitor_Statistics::get_device_type_translation_map(),
                        'translations' => [
                            'loading' => esc_html__('Loading...', 'autopuzzle'),
                            'error' => esc_html__('Error loading data', 'autopuzzle'),
                            'noData' => esc_html__('No data available', 'autopuzzle'),
                            'visits' => esc_html__('Visits', 'autopuzzle'),
                            'uniqueVisitors' => esc_html__('Unique Visitors', 'autopuzzle'),
                            'pages' => esc_html__('Pages', 'autopuzzle'),
                            'date' => esc_html__('Date', 'autopuzzle'),
                            'unknown' => esc_html__('Unknown', 'autopuzzle'),
                            'deviceDesktop' => esc_html__('Desktop', 'autopuzzle'),
                            'deviceMobile' => esc_html__('Mobile', 'autopuzzle'),
                            'deviceTablet' => esc_html__('Tablet', 'autopuzzle'),
                            'ago' => esc_html__('ago', 'autopuzzle'),
                            'momentsAgo' => esc_html__('Moments ago', 'autopuzzle'),
                            'unitSecond' => esc_html__('second', 'autopuzzle'),
                            'unitMinute' => esc_html__('minute', 'autopuzzle'),
                            'unitHour' => esc_html__('hour', 'autopuzzle'),
                            'unitDay' => esc_html__('day', 'autopuzzle'),
                            'previousPeriod' => esc_html__('Previous Period', 'autopuzzle'),
                            'previous' => esc_html__('Previous', 'autopuzzle'),
                        ]
                    ]);
                }
                
                // Visitor Statistics CSS
                $dashboard_css_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/css/visitor-statistics.css';
                if (file_exists($dashboard_css_path)) {
                    wp_enqueue_style('autopuzzle-visitor-statistics', AUTOPUZZLE_PLUGIN_URL . 'assets/css/visitor-statistics.css', [], filemtime($dashboard_css_path));
                }
            }
            
            // Sales dashboard scripts (charts) - only for home page
            if ($page === 'home' || empty($page)) {
                wp_enqueue_script('autopuzzle-apexcharts', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/apexcharts/apexcharts.min.js', ['jquery'], '1.0.0', true);
                wp_enqueue_script('autopuzzle-sales-dashboard', AUTOPUZZLE_PLUGIN_URL . 'assets/js/sales-dashboard.js', ['autopuzzle-apexcharts'], '1.0.0', true);
            }
            
            // Product Editor Script (for product-editor, products, and add-product pages)
            if ($page === 'product-editor' || $page === 'products' || $page === 'add-product') {
                // Use local SweetAlert2 instead of CDN
                $sweetalert2_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/libs/sweetalert2/sweetalert2.min.js';
                if (file_exists($sweetalert2_path)) {
                    wp_enqueue_style('sweetalert2', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/sweetalert2/sweetalert2.min.css', [], '11.0.0');
                    wp_enqueue_script('sweetalert2', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/sweetalert2/sweetalert2.min.js', ['jquery'], '11.0.0', true);
                } else {
                    // Fallback to CDN if local file doesn't exist
                wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', ['jquery'], null, true);
                }
                
                // Check if Quill and FilePond are needed (only for add-product page)
                if ($page === 'add-product') {
                    // Use local Quill instead of CDN
                    $quill_js_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/libs/quill/quill.js';
                    $quill_css_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/libs/quill/quill.snow.css';
                    if (file_exists($quill_js_path) && file_exists($quill_css_path)) {
                        wp_enqueue_style('quill', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/quill/quill.snow.css', [], '1.3.6');
                        wp_enqueue_script('quill', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/quill/quill.js', [], '1.3.6', true);
                    } else {
                        // Fallback to CDN
                    wp_enqueue_script('quill', 'https://cdn.quilljs.com/1.3.6/quill.js', [], '1.3.6', true);
                    wp_enqueue_style('quill', 'https://cdn.quilljs.com/1.3.6/quill.snow.css', [], '1.3.6');
                    }
                    
                    // Use local FilePond instead of CDN
                    $filepond_js_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/libs/filepond/filepond.min.js';
                    $filepond_css_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/libs/filepond/filepond.min.css';
                    if (file_exists($filepond_js_path) && file_exists($filepond_css_path)) {
                        wp_enqueue_style('filepond', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/filepond/filepond.min.css', [], '4.0.0');
                        wp_enqueue_script('filepond', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/filepond/filepond.min.js', [], '4.0.0', true);
                    } else {
                        // Fallback to CDN
                    wp_enqueue_script('filepond', 'https://unpkg.com/filepond/dist/filepond.min.js', [], '4.0.0', true);
                    wp_enqueue_style('filepond', 'https://unpkg.com/filepond/dist/filepond.min.css', [], '4.0.0');
                    }
                }
                
                // Create inline script to define maneliAdminProductEditor globally
                // This is needed because the script is inline in products.php template
                $ajax_url = admin_url('admin-ajax.php');
                $products_url = home_url('/dashboard/products');
                $nonce = wp_create_nonce('autopuzzle_product_data_nonce');
                $inline_script = "
                    window.maneliAdminProductEditor = {
                        ajaxUrl: '" . esc_js($ajax_url) . "',
                        productsUrl: '" . esc_js($products_url) . "',
                        nonce: '" . esc_js($nonce) . "',
                        text: {
                            ajax_error: '" . esc_js(__('Server error. Please try again.', 'autopuzzle')) . "',
                            saved: '" . esc_js(__('Saved', 'autopuzzle')) . "',
                            error: '" . esc_js(__('Error', 'autopuzzle')) . "'
                        }
                    };
                ";
                wp_add_inline_script('jquery', $inline_script, 'before');
            }
            
            // CRITICAL FIX: ALWAYS load inquiry scripts - remove all conditions
            // Load scripts for ALL inquiry pages unconditionally
            $load_inquiry_scripts = false;
            
            // Check if we're on any inquiry-related page (including followups)
            if ($page === 'cash-inquiries' || $page === 'installment-inquiries' || ($page === 'inquiries' && ($subpage === 'cash' || $subpage === 'installment')) || $page === 'cash-followups' || $page === 'installment-followups') {
                $load_inquiry_scripts = true;
            }
            
            // Check URL parameters
            if (isset($_GET['inquiry_id']) || isset($_GET['cash_inquiry_id'])) {
                $load_inquiry_scripts = true;
            }
            
            // Load scripts for inquiry pages (including followups)
            if ($load_inquiry_scripts) {
                // Enqueue dependencies first - Use local SweetAlert2
                $sweetalert2_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/libs/sweetalert2/sweetalert2.min.js';
                if (file_exists($sweetalert2_path)) {
                    wp_enqueue_style('sweetalert2', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/sweetalert2/sweetalert2.min.css', [], '11.0.0');
                    wp_enqueue_script('sweetalert2', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/sweetalert2/sweetalert2.min.js', ['jquery'], '11.0.0', true);
                } else {
                    // Fallback to CDN
                    wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', ['jquery'], null, true);
                }
                // Note: Select2 is not available locally, keep CDN
                wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0');
                wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0', true);
                
                // Use persianDatepicker (same as in expert reports and inquiry forms)
                $datepicker_enqueued = false;
                if (function_exists('autopuzzle_enqueue_persian_datepicker')) {
                    $datepicker_enqueued = autopuzzle_enqueue_persian_datepicker();
                }
                
                // Debug script first (loads before everything, in header)
                wp_enqueue_script('autopuzzle-debug-buttons', AUTOPUZZLE_PLUGIN_URL . 'assets/js/frontend/debug-autopuzzle-buttons.js', ['jquery'], '1.0.0', false);
                
                // Main script - only require essential dependencies (jquery, sweetalert2, select2)
                // Make datepicker optional - only add if it was successfully enqueued
                $inquiry_lists_deps = ['jquery', 'sweetalert2', 'select2'];
                if ($datepicker_enqueued) {
                    $inquiry_lists_deps[] = 'autopuzzle-persian-datepicker';
                }
                
                wp_enqueue_script('autopuzzle-inquiry-lists-js', AUTOPUZZLE_PLUGIN_URL . 'assets/js/frontend/inquiry-lists.js', $inquiry_lists_deps, '1.0.2', true);
                
                // Enqueue report-specific scripts - DISABLED: All handlers are now in inquiry-lists.js
                // These files create duplicate handlers and conflicts. Removed to fix expert note save button.
                /*
                if (isset($_GET['inquiry_id']) && !isset($_GET['cash_inquiry_id'])) {
                    // Installment report page
                    wp_enqueue_script('autopuzzle-installment-report-js', AUTOPUZZLE_PLUGIN_URL . 'assets/js/frontend/installment-report.js', ['jquery', 'sweetalert2'], '1.0.0', true);
                } elseif (isset($_GET['cash_inquiry_id'])) {
                    // Cash report page
                    wp_enqueue_script('autopuzzle-cash-report-js', AUTOPUZZLE_PLUGIN_URL . 'assets/js/frontend/cash-report.js', ['jquery', 'sweetalert2'], '1.0.0', true);
                }
                */
            }
            
            // Calendar Page Scripts (for calendar page)
            if ($page === 'calendar') {
                wp_enqueue_style('autopuzzle-fullcalendar', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/fullcalendar/full-calendar.css', [], '6.1.10');
                // Load FullCalendar in header (false) so it's available when scripts run
                wp_enqueue_script('autopuzzle-fullcalendar', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/fullcalendar/index.global.min.js', ['jquery'], '6.1.10', false);
                
                // Use local SweetAlert2 if available
                $sweetalert2_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/libs/sweetalert2/sweetalert2.min.js';
                if (file_exists($sweetalert2_path)) {
                    wp_enqueue_style('sweetalert2', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/sweetalert2/sweetalert2.min.css', [], '11.0.0');
                    wp_enqueue_script('sweetalert2', AUTOPUZZLE_PLUGIN_URL . 'assets/libs/sweetalert2/sweetalert2.min.js', ['jquery'], '11.0.0', false);
                } else {
                    wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', ['jquery'], null, false);
                }
            }
            
            // Inquiry Lists localization - only for inquiry pages (including followups)
            if ($load_inquiry_scripts) {
                // Localize script with data for cash or installment
                $options = Autopuzzle_Options_Helper::get_all_options();
                $experts_query = get_users(['role' => 'autopuzzle_expert', 'orderby' => 'display_name', 'order' => 'ASC']);
                $experts_list = [];
                foreach ($experts_query as $expert) {
                    $experts_list[] = ['id' => $expert->ID, 'name' => $expert->display_name ?: $expert->user_login];
                }
                
                $preferred_language_slug = $this->get_preferred_language_slug();

                $localize_data = [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'experts' => $experts_list,
                    'locale' => $preferred_language_slug,
                    'use_persian_digits' => ($preferred_language_slug === 'fa'),
                    'text' => [
                        'error' => esc_html__('Error', 'autopuzzle'),
                        'success' => esc_html__('Success', 'autopuzzle'),
                        'server_error' => esc_html__('Server error. Please try again.', 'autopuzzle'),
                        'unknown_error' => esc_html__('Unknown error', 'autopuzzle'),
                        'no_experts_available' => esc_html__('No experts are currently registered.', 'autopuzzle'),
                        'select_expert_required' => esc_html__('Please select an expert.', 'autopuzzle'),
                        'select_expert_placeholder' => esc_html__('Select Expert', 'autopuzzle'),
                        'auto_assign' => esc_html__('-- Automatic Assignment (Round-robin) --', 'autopuzzle'),
                        'assign_title' => esc_html__('Assign to Expert', 'autopuzzle'),
                        'assign_label' => esc_html__('Select Expert:', 'autopuzzle'),
                        'assign_button' => esc_html__('Assign', 'autopuzzle'),
                        'cancel_button' => esc_html__('Cancel', 'autopuzzle'),
                        'confirm_button' => esc_html__('Confirm', 'autopuzzle'),
                        'edit_title' => esc_html__('Edit Inquiry', 'autopuzzle'),
                        'placeholder_name' => esc_html__('Name', 'autopuzzle'),
                        'placeholder_last_name' => esc_html__('Last Name', 'autopuzzle'),
                        'placeholder_mobile' => esc_html__('Mobile Number', 'autopuzzle'),
                        'reject_title' => esc_html__('Reject Inquiry', 'autopuzzle'),
                        'reject_label' => esc_html__('Rejection Reason:', 'autopuzzle'),
                        'reject_placeholder_custom' => esc_html__('Enter your reason...', 'autopuzzle'),
                        'reject_option_default' => esc_html__('-- Select Reason --', 'autopuzzle'),
                        'reject_option_custom' => esc_html__('-- Custom Reason --', 'autopuzzle'),
                        'reject_submit_button' => esc_html__('Reject Inquiry', 'autopuzzle'),
                        'rejection_reason_required' => esc_html__('Please select or enter a rejection reason.', 'autopuzzle'),
                        'request_docs_title' => esc_html__('Request More Documents', 'autopuzzle'),
                        'request_docs_message' => esc_html__('Request will be sent to customer to upload additional documents.', 'autopuzzle'),
                        'request_docs_confirm' => esc_html__('Send Request', 'autopuzzle'),
                        'select_documents_label' => esc_html__('Select Required Documents:', 'autopuzzle'),
                        // Cash inquiry specific text
                        'start_progress_title' => esc_html__('Start Progress', 'autopuzzle'),
                        'start_progress_confirm' => esc_html__('Are you sure you want to start follow-up for this inquiry?', 'autopuzzle'),
                        'approve_title' => esc_html__('Approve Inquiry', 'autopuzzle'),
                        'approve_confirm' => esc_html__('Are you sure you want to approve this inquiry?', 'autopuzzle'),
                        'approve_button' => esc_html__('Approve', 'autopuzzle'),
                        'schedule_meeting_title' => esc_html__('Schedule Meeting', 'autopuzzle'),
                        'meeting_date_label' => esc_html__('Meeting Date', 'autopuzzle'),
                        'meeting_time_label' => esc_html__('Meeting Time', 'autopuzzle'),
                        'select_date' => esc_html__('Select Date', 'autopuzzle'),
                        'meeting_required' => esc_html__('Please enter meeting date and time', 'autopuzzle'),
                        'schedule_button' => esc_html__('Schedule', 'autopuzzle'),
                        'schedule_followup_title' => esc_html__('Schedule Follow-up', 'autopuzzle'),
                        'followup_date_label' => esc_html__('Follow-up Date', 'autopuzzle'),
                        'note_label_optional' => esc_html__('Note (Optional)', 'autopuzzle'),
                        'enter_note' => esc_html__('Enter your note...', 'autopuzzle'),
                        'send_sms' => esc_html__('Send SMS', 'autopuzzle'),
                        'recipient' => esc_html__('Recipient:', 'autopuzzle'),
                        'message' => esc_html__('Message:', 'autopuzzle'),
                        'enter_message' => esc_html__('Enter your message...', 'autopuzzle'),
                        'please_enter_message' => esc_html__('Please enter a message', 'autopuzzle'),
                        'sms_sent_successfully' => esc_html__('SMS sent successfully!', 'autopuzzle'),
                        'failed_to_send_sms' => esc_html__('Failed to send SMS', 'autopuzzle'),
                        'send' => esc_html__('Send', 'autopuzzle'),
                        'sending' => esc_html__('Sending...', 'autopuzzle'),
                        'please_wait' => esc_html__('Please wait', 'autopuzzle'),
                        'sms_history_modal_not_found' => esc_html__('SMS history modal not found.', 'autopuzzle'),
                        'invalid_inquiry_id' => esc_html__('Invalid inquiry ID.', 'autopuzzle'),
                        'no_sms_history' => esc_html__('No SMS messages have been sent for this inquiry yet.', 'autopuzzle'),
                        'error_loading_history' => esc_html__('Error loading SMS history.', 'autopuzzle'),
                        'user' => esc_html__('User', 'autopuzzle'),
                        'date_time' => esc_html__('Date & Time', 'autopuzzle'),
                        'sent' => esc_html__('Sent', 'autopuzzle'),
                        'failed' => esc_html__('Failed', 'autopuzzle'),
                        'missing_required_info' => esc_html__('Missing required information.', 'autopuzzle'),
                        // SMS History and Resend
                        'resend_sms' => esc_html__('Resend SMS?', 'autopuzzle'),
                        'resend_confirm' => esc_html__('Are you sure you want to resend this SMS?', 'autopuzzle'),
                        'yes_resend' => esc_html__('Yes, Resend', 'autopuzzle'),
                        'resend' => esc_html__('Resend', 'autopuzzle'),
                        'sms_resent_successfully' => esc_html__('SMS resent successfully.', 'autopuzzle'),
                        'failed_to_resend_sms' => esc_html__('Failed to resend SMS.', 'autopuzzle'),
                        // SMS Status Check
                        'check_status' => esc_html__('Check Status', 'autopuzzle'),
                        'checking' => esc_html__('Checking...', 'autopuzzle'),
                        'checking_status' => esc_html__('Checking status...', 'autopuzzle'),
                        'failed_to_get_status' => esc_html__('Failed to get status.', 'autopuzzle'),
                        'error_checking_status' => esc_html__('Error checking status.', 'autopuzzle'),
                        'status_unavailable' => esc_html__('Status unavailable', 'autopuzzle'),
                        'check_failed' => esc_html__('Check failed', 'autopuzzle'),
                        // SMS Status Messages
                        'delivered' => esc_html__('Delivered', 'autopuzzle'),
                        'pending' => esc_html__('Pending', 'autopuzzle'),
                        'blocked' => esc_html__('Blocked', 'autopuzzle'),
                        'rejected' => esc_html__('Rejected', 'autopuzzle'),
                        'unknown_status' => esc_html__('Unknown status', 'autopuzzle'),
                        'rate_limit_exceeded' => esc_html__('Rate limit exceeded or service temporarily unavailable', 'autopuzzle'),
                        // SMS History Table
                        'sent_by' => esc_html__('Sent By', 'autopuzzle'),
                        'recipient' => esc_html__('Recipient', 'autopuzzle'),
                        'message' => esc_html__('Message', 'autopuzzle'),
                        'status' => esc_html__('Status', 'autopuzzle'),
                        'actions' => esc_html__('Actions', 'autopuzzle'),
                        'customer' => esc_html__('Customer', 'autopuzzle'),
                        'loading_sms_history' => esc_html__('Loading SMS history...', 'autopuzzle'),
                        'no_sms_messages_sent_yet' => esc_html__('No SMS messages have been sent yet.', 'autopuzzle'),
                        'please_refresh_page' => esc_html__('Please refresh the page to use this feature.', 'autopuzzle'),
                        'nonce_missing' => esc_html__('Nonce is missing. Please refresh the page and try again.', 'autopuzzle'),
                        'security_token_missing' => esc_html__('Security token is missing. Please refresh the page.', 'autopuzzle'),
                        'network_error' => esc_html__('Network error. Please check your connection.', 'autopuzzle'),
                        'security_verification_failed' => esc_html__('Security verification failed. Please refresh the page and try again.', 'autopuzzle'),
                        'unable_to_find_inquiry' => esc_html__('Unable to find inquiry ID. Please refresh the page.', 'autopuzzle'),
                        'script_init_error' => esc_html__('Script initialization error. Please refresh the page.', 'autopuzzle'),
                        'server_error_contact_support' => esc_html__('Server error. Please contact support if the problem persists.', 'autopuzzle'),
                        'unauthorized_access' => esc_html__('Unauthorized access.', 'autopuzzle'),
                        'schedule_followup_button' => esc_html__('Schedule Follow-up', 'autopuzzle'),
                        'set_downpayment_title' => esc_html__('Set Down Payment Amount', 'autopuzzle'),
                        'downpayment_amount_label' => esc_html__('Down Payment Amount (Toman):', 'autopuzzle'),
                        'downpayment_amount_required' => esc_html__('Please enter down payment amount', 'autopuzzle'),
                        'request_downpayment_title' => esc_html__('Request Down Payment?', 'autopuzzle'),
                        'amount' => esc_html__('Amount', 'autopuzzle'),
                        'toman' => esc_html__('Toman', 'autopuzzle'),
                        'send_button' => esc_html__('Send', 'autopuzzle'),
                        'ok_button' => esc_html__('OK', 'autopuzzle'),
                        'status_updated' => esc_html__('Status updated successfully', 'autopuzzle'),
                        'status_update_error' => esc_html__('Error updating status', 'autopuzzle'),
                        // Expert note specific text
                        'attention' => esc_html__('Attention!', 'autopuzzle'),
                        'please_enter_note' => esc_html__('Please enter your note.', 'autopuzzle'),
                        'note_saved_success' => esc_html__('Note saved successfully.', 'autopuzzle'),
                        'error_occurred' => esc_html__('An error occurred.', 'autopuzzle'),
                        // Status action text
                        'complete_title' => esc_html__('Complete Inquiry', 'autopuzzle'),
                        'complete_confirm' => esc_html__('Are you sure you want to complete this inquiry?', 'autopuzzle'),
                        'followup_date_required' => esc_html__('Please enter follow-up date', 'autopuzzle'),
                        'cancel_inquiry_title' => esc_html__('Cancel Inquiry', 'autopuzzle'),
                        'rejection_reason_label' => esc_html__('Rejection Reason', 'autopuzzle'),
                        'cancel_reason_label' => esc_html__('Cancellation Reason', 'autopuzzle'),
                        'enter_reason' => esc_html__('Please enter reason...', 'autopuzzle'),
                        'reason_required' => esc_html__('Please enter reason with at least 10 characters', 'autopuzzle'),
                    ]
                ];
                
                // Check for cash inquiries (both old and new routes) and cash followups
                if ($page === 'cash-inquiries' || ($page === 'inquiries' && $subpage === 'cash') || $page === 'cash-followups') {
                    $localize_data['nonces'] = [
                        'ajax' => wp_create_nonce('autopuzzle-ajax-nonce'),
                        'cash_filter' => wp_create_nonce('autopuzzle_cash_inquiry_filter_nonce'),
                        'cash_details' => wp_create_nonce('autopuzzle_cash_inquiry_details_nonce'),
                        'cash_update' => wp_create_nonce('autopuzzle_cash_inquiry_update_nonce'),
                        'cash_set_downpayment' => wp_create_nonce('autopuzzle_cash_set_downpayment_nonce'),
                        'cash_assign_expert' => wp_create_nonce('autopuzzle_cash_inquiry_assign_expert_nonce'),
                        'save_expert_note' => wp_create_nonce('autopuzzle_save_expert_note'),
                        'update_cash_status' => wp_create_nonce('autopuzzle_update_cash_status'),
                    ];
                    $cash_rejection_reasons = array_filter(array_map('trim', explode("\n", $options['cash_inquiry_rejection_reasons'] ?? '')));
                    $localize_data['cash_rejection_reasons'] = $cash_rejection_reasons;
                } else {
                    // Installment inquiries and installment followups
                    $localize_data['nonces'] = [
                        'ajax' => wp_create_nonce('autopuzzle-ajax-nonce'),
                        'inquiry_filter' => wp_create_nonce('autopuzzle_inquiry_filter_nonce'),
                        'details' => wp_create_nonce('autopuzzle_inquiry_details_nonce'),
                        'assign_expert' => wp_create_nonce('autopuzzle_inquiry_assign_expert_nonce'),
                        'tracking_status' => wp_create_nonce('autopuzzle_tracking_status_nonce'),
                        'save_installment_note' => wp_create_nonce('autopuzzle_installment_note'),
                        'update_installment_status' => wp_create_nonce('autopuzzle_installment_status'),
                    ];
                    $installment_rejection_reasons = array_filter(array_map('trim', explode("\n", $options['installment_inquiry_rejection_reasons'] ?? '')));
                    $localize_data['installment_rejection_reasons'] = $installment_rejection_reasons;
                    
                    // Add required documents list
                    $required_docs_raw = $options['customer_required_documents'] ?? '';
                    $required_docs = array_filter(array_map('trim', explode("\n", $required_docs_raw)));
                    $localize_data['required_documents'] = $required_docs;
                    $localize_data['text']['request_docs_title'] = esc_html__('Request More Documents', 'autopuzzle');
                    $localize_data['text']['request_docs_message'] = esc_html__('Request will be sent to customer to upload additional documents.', 'autopuzzle');
                    $localize_data['text']['request_docs_confirm'] = esc_html__('Send Request', 'autopuzzle');
                    $localize_data['text']['select_documents_label'] = esc_html__('Select Required Documents:', 'autopuzzle');
                }
                
                // CRITICAL: wp_localize_script outputs inline script AFTER the main script
                // So we need to add the text data BEFORE inquiry-lists.js loads
                // This ensures maneliInquiryLists.text is available when handler functions are defined
                if ($load_inquiry_scripts) {
                    $cash_nonce = wp_create_nonce("autopuzzle_cash_inquiry_filter_nonce");
                    $installment_nonce = wp_create_nonce("autopuzzle_inquiry_filter_nonce");
                    $ajax_nonce = wp_create_nonce("autopuzzle-ajax-nonce");
                    $ajax_url = admin_url("admin-ajax.php");
                    
                    // Prepare text data as JSON for inline script
                    $text_json = json_encode($localize_data['text'], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);
                    
                    // Build nonces object
                    $nonces_obj = [
                        'ajax' => $ajax_nonce,
                        'cash_filter' => $cash_nonce,
                        'cash_details' => wp_create_nonce("autopuzzle_cash_inquiry_details_nonce"),
                        'cash_update' => wp_create_nonce("autopuzzle_cash_inquiry_update_nonce"),
                        'cash_assign_expert' => wp_create_nonce("autopuzzle_cash_inquiry_assign_expert_nonce"),
                        'inquiry_filter' => $installment_nonce,
                        'details' => wp_create_nonce("autopuzzle_inquiry_details_nonce"),
                        'assign_expert' => wp_create_nonce("autopuzzle_inquiry_assign_expert_nonce"),
                        'tracking_status' => wp_create_nonce("autopuzzle_tracking_status_nonce"),
                        'save_installment_note' => wp_create_nonce("autopuzzle_installment_note"),
                        'update_installment_status' => wp_create_nonce("autopuzzle_installment_status"),
                        'save_expert_note' => wp_create_nonce("autopuzzle_save_expert_note"),
                        'update_cash_status' => wp_create_nonce("autopuzzle_update_cash_status")
                    ];
                    $nonces_json = json_encode($nonces_obj, JSON_UNESCAPED_UNICODE);
                    $experts_json = json_encode($localize_data['experts'], JSON_UNESCAPED_UNICODE);
                    
                    // CRITICAL: Use wp_add_inline_script with 'before' position
                    // This outputs the script BEFORE the main script tag, ensuring it runs first
                    $preload_script = "
                    // Global AJAX variables for SMS sending - ensure they're available before inquiry-lists.js loads
                    if (typeof maneliAjaxUrl === 'undefined') {
                        window.maneliAjaxUrl = " . json_encode($ajax_url, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ";
                    }
                    if (typeof maneliAjaxNonce === 'undefined') {
                        window.maneliAjaxNonce = " . json_encode($ajax_nonce, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ";
                    }
                    
                    // CRITICAL: Initialize maneliInquiryLists with FULL text data BEFORE inquiry-lists.js loads
                    // This ensures translations are available when handler functions use getText()
                    if (typeof window.maneliInquiryLists === 'undefined') {
                        window.maneliInquiryLists = {
                            ajax_url: " . json_encode($ajax_url, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ",
                            nonces: " . $nonces_json . ",
                            experts: " . $experts_json . ",
                            text: " . $text_json . "
                        };
                        console.log('✅ maneliInquiryLists preloaded via wp_add_inline_script with ' + Object.keys(window.maneliInquiryLists.text).length + ' text keys');
                    } else {
                        // Merge text if object already exists
                        if (!window.maneliInquiryLists.text) {
                            window.maneliInquiryLists.text = {};
                        }
                        var preloadText = " . $text_json . ";
                        Object.assign(window.maneliInquiryLists.text, preloadText);
                        console.log('✅ maneliInquiryLists.text merged via wp_add_inline_script with ' + Object.keys(preloadText).length + ' keys');
                    }
                    ";
                    wp_add_inline_script('autopuzzle-inquiry-lists-js', $preload_script, 'before');
                    
                    // CRITICAL FIX: Also add script in wp_print_scripts hook as backup
                    // This ensures it runs even if wp_add_inline_script fails for some reason
                    add_action('wp_print_scripts', function() use ($ajax_url, $ajax_nonce, $text_json, $nonces_json, $experts_json) {
                        if (wp_script_is('autopuzzle-inquiry-lists-js', 'enqueued')) {
                            ?>
                            <script>
                            // Global AJAX variables for SMS sending - ensure they're available before inquiry-lists.js loads
                            if (typeof maneliAjaxUrl === 'undefined') {
                                window.maneliAjaxUrl = <?php echo json_encode($ajax_url, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
                            }
                            if (typeof maneliAjaxNonce === 'undefined') {
                                window.maneliAjaxNonce = <?php echo json_encode($ajax_nonce, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
                            }
                            
                            // CRITICAL: Initialize maneliInquiryLists with FULL text data BEFORE inquiry-lists.js loads
                            // This ensures translations are available when handler functions use getText()
                            if (typeof window.maneliInquiryLists === 'undefined') {
                                window.maneliInquiryLists = {
                                    ajax_url: <?php echo json_encode($ajax_url, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
                                    nonces: <?php echo $nonces_json; ?>,
                                    experts: <?php echo $experts_json; ?>,
                                    text: <?php echo $text_json; ?>
                                };
                                console.log('✅ maneliInquiryLists preloaded via wp_print_scripts with ' + Object.keys(window.maneliInquiryLists.text).length + ' text keys');
                            } else {
                                // Merge text if object already exists
                                if (!window.maneliInquiryLists.text) {
                                    window.maneliInquiryLists.text = {};
                                }
                                var preloadText = <?php echo $text_json; ?>;
                                Object.assign(window.maneliInquiryLists.text, preloadText);
                                console.log('✅ maneliInquiryLists.text merged via wp_print_scripts with ' + Object.keys(preloadText).length + ' keys');
                            }
                            </script>
                            <?php
                        }
                    }, 5); // Priority 5 to run early
                }
                
                // Now localize - this will override/merge with preloaded data if already set
                wp_localize_script('autopuzzle-inquiry-lists-js', 'maneliInquiryLists', $localize_data);
                
                // CRITICAL: wp_localize_script outputs inline script AFTER the main script
                // So we need to ensure that inquiry-lists.js can handle this timing issue
                // The getText function will use fallback if text is not available yet
                // This inline script logs when text is populated for debugging
                $debug_text_script = "
                (function() {
                    // This runs after wp_localize_script output
                    if (typeof maneliInquiryLists !== 'undefined' && maneliInquiryLists.text) {
                        console.log('✅ maneliInquiryLists.text populated via wp_localize_script:', Object.keys(maneliInquiryLists.text).length + ' keys');
                    } else {
                        console.warn('⚠️ maneliInquiryLists.text not populated after wp_localize_script');
                    }
                })();
                ";
                wp_add_inline_script('autopuzzle-inquiry-lists-js', $debug_text_script, 'after');
            }
            
            // EMERGENCY FIX: Only add emergency script if inquiry scripts are loaded
            if ($load_inquiry_scripts) {
                // EMERGENCY FIX: Add inline script DIRECTLY in template output
                // Since wp_footer might not be called, output directly after scripts
                // Store for output in template
                $GLOBALS['autopuzzle_emergency_script'] = true;
                
                // ALSO try wp_footer as backup
                add_action('wp_footer', function() {
                    ?>
                    <script type="text/javascript">
                    console.log('🚨 EMERGENCY SCRIPT LOADING...');
                    if (typeof jQuery !== 'undefined') {
                        jQuery(document).ready(function($) {
                            console.log('🚨 EMERGENCY HANDLERS LOADED');
                            
                            // Check if objects exist
                            console.log('maneliInquiryLists:', typeof window.maneliInquiryLists !== 'undefined' ? 'DEFINED' : 'UNDEFINED');
                            console.log('Swal:', typeof Swal !== 'undefined' ? 'DEFINED' : 'UNDEFINED');
                            
                            // Direct handler for assign expert button
                            $(document).off('click', '.assign-expert-btn.emergency').on('click', '.assign-expert-btn', function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                console.log('🔘 Assign Expert button clicked!');
                                var btn = $(this);
                                var inquiryId = btn.data('inquiry-id');
                                var inquiryType = btn.data('inquiry-type');
                                console.log('Inquiry ID:', inquiryId, 'Type:', inquiryType);
                                
                                if (typeof window.maneliInquiryLists === 'undefined') {
                                    alert('" . esc_js(__('Error: maneliInquiryLists is not defined!', 'autopuzzle')) . "');
                                    console.error('maneliInquiryLists is undefined!');
                                    return;
                                }
                                
                                if (typeof Swal === 'undefined') {
                                    alert('" . esc_js(__('Error: SweetAlert2 is not loaded!', 'autopuzzle')) . "');
                                    console.error('Swal is undefined!');
                                    return;
                                }
                                
                                // Show working alert
                                Swal.fire({
                                    title: '" . esc_js(__('Button Works!', 'autopuzzle')) . "',
                                    text: 'Inquiry ID: ' + inquiryId + ', Type: ' + inquiryType,
                                    icon: 'success'
                                });
                            });
                            
                            console.log('✅ Emergency handlers attached');
                            console.log('Button count - .assign-expert-btn:', $('.assign-expert-btn').length);
                        });
                    } else {
                        console.error('❌ jQuery not available for emergency handlers!');
                    }
                    </script>
                    <?php
                }, 999);
                
                // Localize report-specific scripts - DISABLED: Scripts are no longer enqueued
                // All handlers are now in inquiry-lists.js
                /*
                if (isset($_GET['inquiry_id']) && !isset($_GET['cash_inquiry_id'])) {
                    // Installment report localization
                    wp_localize_script('autopuzzle-installment-report-js', 'maneliInstallmentReport', [
                        'ajax_url' => admin_url('admin-ajax.php'),
                        'nonces' => [
                            'update_status' => wp_create_nonce('autopuzzle_installment_status'),
                            'save_note' => wp_create_nonce('autopuzzle_installment_note'),
                        ]
                    ]);
                } elseif (isset($_GET['cash_inquiry_id'])) {
                    // Cash report localization
                    wp_localize_script('autopuzzle-cash-report-js', 'maneliCashReport', [
                        'ajax_url' => admin_url('admin-ajax.php'),
                        'nonces' => [
                            'update_status' => wp_create_nonce('autopuzzle_update_cash_status'),
                            'save_note' => wp_create_nonce('autopuzzle_save_expert_note'),
                            'save_meeting' => wp_create_nonce('autopuzzle_save_meeting'),
                            'expert_decision' => wp_create_nonce('autopuzzle_expert_decision_cash'),
                            'admin_approve' => wp_create_nonce('autopuzzle_admin_approve_cash'),
                        ],
                        'text' => [
                            'select_date' => esc_html__('Select Date', 'autopuzzle'),
                            'error' => esc_html__('Error', 'autopuzzle'),
                            'success' => esc_html__('Success', 'autopuzzle'),
                            'server_error' => esc_html__('Server error. Please try again.', 'autopuzzle'),
                        ]
                    ]);
                }
                */
            }
            
            // Localize script for AJAX
            wp_localize_script('autopuzzle-dashboard', 'autopuzzle_ajax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('autopuzzle_dashboard_nonce'),
                'login_url' => home_url('/login'),
                'dashboard_url' => home_url('/dashboard')
            ]);
        }
    }

    /**
     * Override locale based on stored language preference.
     *
     * @param string $locale Current locale.
     * @return string Modified locale.
     */
    public function maybe_override_locale($locale) {
        $preferred_locale = $this->get_preferred_locale_code();
        if (!$preferred_locale) {
            return $locale;
        }

        // Avoid overriding admin pages unless requested through AJAX.
        if (is_admin() && !wp_doing_ajax()) {
            return $locale;
        }

        return $preferred_locale;
    }

    /**
     * Resolve preferred locale code from cookie.
     *
     * @return string|null Locale code like fa_IR or en_US.
     */
    private function get_preferred_locale_code() {
        $language = $this->get_preferred_language_slug();

        if ($language === 'en') {
            return 'en_US';
        }

        if ($language === 'fa') {
            return 'fa_IR';
        }

        return null;
    }

    /**
     * Get preferred language slug (fa/en) inferred from cookie.
     *
     * @return string
     */
    public function get_preferred_language_slug() {
        $preferred_language = 'fa';

        if (!empty($_COOKIE['autopuzzle_language'])) {
            $cookie_language = sanitize_text_field(wp_unslash($_COOKIE['autopuzzle_language']));
            $cookie_language = strtolower($cookie_language);
            if ($cookie_language !== '') {
                if (strpos($cookie_language, 'en') === 0 || in_array($cookie_language, ['english'], true)) {
                    $preferred_language = 'en';
                } elseif (strpos($cookie_language, 'fa') === 0 || in_array($cookie_language, ['persian'], true)) {
                    $preferred_language = 'fa';
                }
            }
        }

        return $preferred_language;
    }
    
    /**
     * Render dashboard
     */
    private function render_dashboard() {
        $page = get_query_var('autopuzzle_dashboard_page');
        $subpage = get_query_var('autopuzzle_dashboard_subpage');
        
        // CRITICAL: Handle login page FIRST before any redirects
        // This prevents redirect loops when accessing /login
        // ALL authentication steps (OTP, password creation) are now handled in the unified login page
        if ($page === 'login') {
            // If already logged in, check for redirect URL
            if ($this->is_user_logged_in()) {
                $this->maybe_start_session();
                $redirect_url = isset($_SESSION['autopuzzle_redirect_after_login']) ? $_SESSION['autopuzzle_redirect_after_login'] : home_url('/dashboard');
                unset($_SESSION['autopuzzle_redirect_after_login']);
                wp_redirect($redirect_url);
                exit;
            }
            // Otherwise render unified login page (handles all steps)
            $this->render_login_page();
            return; // Exit early to prevent further processing
        }
        
        // CRITICAL: Remove separate verify-otp and create-password pages
        // These are now handled within the unified login page
        // Redirect old URLs to login page for backward compatibility
        if (in_array($page, ['verify-otp', 'create-password'])) {
            wp_redirect(home_url('/login'));
            exit;
        }
        
        // Handle logout
        if ($page === 'logout') {
            $this->handle_logout();
            return; // Exit early
        }
        
        // For all other pages, require login
        // CRITICAL: Check session FIRST before any redirects
        // This prevents redirect loops by ensuring session is checked properly
        $this->maybe_start_session();
        
        // Check login status
        $is_logged_in = $this->is_user_logged_in();
        
        if (!$is_logged_in) {
            // User is not logged in - redirect to login
            wp_redirect(home_url('/login'));
            exit;
        }
        
        // Check license status - if not active and not demo, only allow license activation page
        if (class_exists('Autopuzzle_License')) {
            $license = Autopuzzle_License::instance();
            if (!$license->is_license_active() && !$license->is_demo_mode()) {
                // Check if current page is license activation
                $get_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
                if ($get_page !== 'license-activation' && $page !== 'license-activation') {
                    // Redirect to license activation page
                    wp_redirect(home_url('/dashboard?page=license-activation'));
                    exit;
                }
            }
        }
        
        // User is logged in - verify user data is valid
        $temp_user_check = $this->get_current_user();
        
        // Only redirect if truly invalid
        if (empty($temp_user_check) || $temp_user_check['role'] === 'guest') {
            // User session is invalid - clear and redirect
            $_SESSION = [];
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
            }
            wp_redirect(home_url('/login'));
            exit;
        }
        
        // Redirect old inquiry pages to unified pages
        if ($page === 'my-cash-inquiries') {
            wp_redirect(home_url('/dashboard/inquiries/cash'));
            exit;
        }
        if ($page === 'my-installment-inquiries') {
            wp_redirect(home_url('/dashboard/inquiries/installment'));
            exit;
        }
        
        // Render main dashboard for all other pages
        $this->render_main_dashboard($page, $subpage);
    }
    
    /**
     * Check if user is logged in to dashboard
     */
    private function is_user_logged_in() {
        // If user is logged in to WordPress, allow access
        if (is_user_logged_in()) {
            return true;
        }
        
        // Otherwise check session - check both old and new format
        $this->maybe_start_session();
        
        // Check new format: $_SESSION['maneli']['user_id']
        if (isset($_SESSION['maneli']['user_id']) && !empty($_SESSION['maneli']['user_id'])) {
            $user_id = (int)$_SESSION['maneli']['user_id'];
            // Verify user actually exists in database
            if ($user_id > 0 && get_user_by('ID', $user_id)) {
                return true;
            }
        }
        
        // Check old format: $_SESSION['autopuzzle_user_id'] (backward compatibility)
        if (isset($_SESSION['autopuzzle_user_id']) && !empty($_SESSION['autopuzzle_user_id'])) {
            $user_id = (int)$_SESSION['autopuzzle_user_id'];
            if ($user_id > 0 && get_user_by('ID', $user_id)) {
                return true;
            }
        }
        
        // Check old format: $_SESSION['autopuzzle_dashboard_logged_in'] (backward compatibility)
        if (isset($_SESSION['autopuzzle_dashboard_logged_in']) && $_SESSION['autopuzzle_dashboard_logged_in'] === true) {
            $user_id = isset($_SESSION['autopuzzle_user_id']) ? (int)$_SESSION['autopuzzle_user_id'] : 0;
            if ($user_id > 0 && get_user_by('ID', $user_id)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Ensure session cookie parameters are set early in the request before any output.
     */
    public function configure_session_cookie() {
        if (self::$session_cookie_configured) {
            return;
        }

        if (headers_sent() || session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $params = array(
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        );

        // Configure session cookie parameters for security and persistence
        session_set_cookie_params($params);
        self::$session_cookie_configured = true;
    }

    /**
     * Start session if not already started
     */
    private function maybe_start_session() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            if (!self::$session_cookie_configured) {
                $this->configure_session_cookie();
            }
            session_start();
        }
    }
    
    /**
     * Render unified login page
     * This page handles all authentication steps:
     * - Phone input and login method selection
     * - OTP verification
     * - Password creation (if needed)
     * - Password login
     */
    private function render_login_page() {
        // CRITICAL: Start session early to ensure it's available for the template
        $this->maybe_start_session();
        
        // Store redirect URL from query parameter in session for later use
        if (isset($_GET['redirect_to']) && !empty($_GET['redirect_to'])) {
            $_SESSION['autopuzzle_redirect_after_login'] = esc_url_raw($_GET['redirect_to']);
        }
        
        // Get any error/success messages from session
        $error_message = isset($_SESSION['autopuzzle_error']) ? $_SESSION['autopuzzle_error'] : '';
        $success_message = isset($_SESSION['autopuzzle_success']) ? $_SESSION['autopuzzle_success'] : '';
        
        // Clear messages after displaying (will be shown once)
        unset($_SESSION['autopuzzle_error']);
        unset($_SESSION['autopuzzle_success']);
        
        // Include the unified login template
        include AUTOPUZZLE_PLUGIN_PATH . 'templates/login.php';
    }
    
    /**
     * Render verify OTP page
     * DEPRECATED: This is now handled in the unified login page
     * Kept for backward compatibility (will redirect to login)
     */
    private function render_verify_otp_page() {
        wp_redirect(home_url('/login'));
        exit;
    }
    
    /**
     * Render create password page
     * DEPRECATED: This is now handled in the unified login page
     * Kept for backward compatibility (will redirect to login)
     */
    private function render_create_password_page() {
        wp_redirect(home_url('/login'));
        exit;
    }
    
    /**
     * Render main dashboard
     */
    private function render_main_dashboard($page = '', $subpage = '') {
        $user = $this->get_current_user();
        $menu_items = $this->get_menu_items();
        
        include AUTOPUZZLE_PLUGIN_PATH . 'templates/dashboard.php';
    }
    
    /**
     * Get current user
     * CRITICAL: Support both WordPress login and session-based login
     * Always use WordPress user roles from database when available
     * 
     * NOTE: This method does NOT perform redirects. It should only be called
     * after login has been verified in render_dashboard().
     */
    private function get_current_user() {
        // CRITICAL FIX: Do NOT redirect here - redirect is handled in render_dashboard()
        // This prevents redirect loops when get_current_user() is called after login verification
        
        // Try to get WordPress user first (if WordPress login exists)
        if (is_user_logged_in()) {
            $wp_user = wp_get_current_user();
            
            // Determine actual role from WordPress user roles (always fresh from database)
            // Note: autopuzzle_admin is the actual role name, but we use 'autopuzzle_manager' as internal identifier
            $user_role = 'customer';
            if (in_array('administrator', $wp_user->roles, true)) {
                $user_role = 'administrator';
            } elseif (in_array('autopuzzle_admin', $wp_user->roles, true) || in_array('autopuzzle_manager', $wp_user->roles, true)) {
                $user_role = 'autopuzzle_manager';
            } elseif (in_array('autopuzzle_expert', $wp_user->roles, true)) {
                $user_role = 'autopuzzle_expert';
            } else {
                $user_role = 'customer';
            }
            
            return [
                'name' => $wp_user->display_name,
                'phone' => get_user_meta($wp_user->ID, 'billing_phone', true) ?: '',
                'role' => $user_role
            ];
        }
        
        // Fallback to session-based login (for OTP/password login)
        $this->maybe_start_session();
        
        // Get user ID from session - check both new and old format for compatibility
        $user_id = 0;
        if (isset($_SESSION['maneli']['user_id']) && !empty($_SESSION['maneli']['user_id'])) {
            $user_id = (int)$_SESSION['maneli']['user_id'];
        } elseif (isset($_SESSION['autopuzzle_user_id']) && !empty($_SESSION['autopuzzle_user_id'])) {
            // Old session format for backward compatibility
            $user_id = (int)$_SESSION['autopuzzle_user_id'];
        }
        
        // CRITICAL FIX: Do NOT redirect here - return default data instead
        // Redirect is handled in render_dashboard() to prevent loops
        if (!$user_id) {
            // Session exists but no user_id - invalid session
            // Return default guest user data instead of redirecting
        return [
                'name' => '',
                'phone' => '',
                'role' => 'guest'
            ];
        }
        
        // Get user from database
        $wp_user = get_user_by('ID', $user_id);
        
        // CRITICAL FIX: Do NOT redirect here - return default data instead
        if (!$wp_user) {
            // User doesn't exist - invalid session
            // Return default guest user data instead of redirecting
            return [
                'name' => '',
                'phone' => '',
                'role' => 'guest'
            ];
        }
        
        // Determine actual role from WordPress user roles (always fresh from database)
        // Note: autopuzzle_admin is the actual role name, but we use 'autopuzzle_manager' as internal identifier
        $user_role = 'customer';
        if (in_array('administrator', $wp_user->roles, true)) {
            $user_role = 'administrator';
        } elseif (in_array('autopuzzle_admin', $wp_user->roles, true) || in_array('autopuzzle_manager', $wp_user->roles, true)) {
            $user_role = 'autopuzzle_manager';
        } elseif (in_array('autopuzzle_expert', $wp_user->roles, true)) {
            $user_role = 'autopuzzle_expert';
        } else {
            $user_role = 'customer';
        }
        
        return [
            'name' => $wp_user->display_name,
            'phone' => isset($_SESSION['maneli']['phone']) ? $_SESSION['maneli']['phone'] : (get_user_meta($user_id, 'billing_phone', true) ?: ''),
            'role' => $user_role
        ];
    }
    
    /**
     * Get current user data for template rendering (public method)
     * Returns user info including WordPress user object, role, name, and phone
     * 
     * @return array User data array with keys: wp_user, role, name, phone, role_display
     */
    public function get_current_user_for_template() {
        $wp_user = null;
        $user_id = 0;
        $user_role = 'customer';
        $user_name = esc_html__('User', 'autopuzzle');
        $user_phone = '';
        
        // Check if WordPress user is logged in
        if (is_user_logged_in()) {
            $wp_user = wp_get_current_user();
            $user_id = $wp_user->ID;
            
            // Determine role
            if (in_array('administrator', $wp_user->roles)) {
                $user_role = 'administrator';
            } elseif (in_array('autopuzzle_admin', $wp_user->roles) || in_array('autopuzzle_manager', $wp_user->roles)) {
                $user_role = 'autopuzzle_manager';
            } elseif (in_array('autopuzzle_expert', $wp_user->roles)) {
                $user_role = 'autopuzzle_expert';
            } else {
                $user_role = 'customer';
            }
            
            // Get name
            $first_name = get_user_meta($user_id, 'first_name', true);
            $last_name = get_user_meta($user_id, 'last_name', true);
            $user_name = trim($first_name . ' ' . $last_name);
            if (empty($user_name)) {
                $user_name = $wp_user->display_name;
            }
            if (empty($user_name)) {
                $user_name = $wp_user->user_login;
            }
            
            // Get phone
            $user_phone = get_user_meta($user_id, 'billing_phone', true);
            if (empty($user_phone)) {
                $user_phone = $wp_user->user_login;
            }
        } else {
            // Fallback to session-based login
            $this->maybe_start_session();
            
            // Get user ID from session - check both new and old format
            $user_id = 0;
            if (isset($_SESSION['maneli']['user_id']) && !empty($_SESSION['maneli']['user_id'])) {
                $user_id = (int)$_SESSION['maneli']['user_id'];
            } elseif (isset($_SESSION['autopuzzle_user_id']) && !empty($_SESSION['autopuzzle_user_id'])) {
                // Old session format for backward compatibility
                $user_id = (int)$_SESSION['autopuzzle_user_id'];
            }
            
            if ($user_id) {
                // Get user from database
                $wp_user = get_user_by('ID', $user_id);
                
                if ($wp_user) {
                    // Determine role from database
                    if (in_array('administrator', $wp_user->roles)) {
                        $user_role = 'administrator';
                    } elseif (in_array('autopuzzle_admin', $wp_user->roles) || in_array('autopuzzle_manager', $wp_user->roles)) {
                        $user_role = 'autopuzzle_manager';
                    } elseif (in_array('autopuzzle_expert', $wp_user->roles)) {
                        $user_role = 'autopuzzle_expert';
                    } else {
                        $user_role = 'customer';
                    }
                    
                    // Get name
                    $first_name = get_user_meta($user_id, 'first_name', true);
                    $last_name = get_user_meta($user_id, 'last_name', true);
                    $user_name = trim($first_name . ' ' . $last_name);
                    if (empty($user_name)) {
                        $user_name = $wp_user->display_name;
                    }
                    if (empty($user_name)) {
                        $user_name = $wp_user->user_login;
                    }
                    
                    // Get phone from session or database
                    $user_phone = isset($_SESSION['maneli']['phone']) ? $_SESSION['maneli']['phone'] : '';
                    if (empty($user_phone)) {
                        $user_phone = get_user_meta($user_id, 'billing_phone', true);
                    }
                    if (empty($user_phone)) {
                        $user_phone = $wp_user->user_login;
                    }
                } else {
                    // Fallback to old session format
            $user_name = $_SESSION['autopuzzle_user_name'] ?? esc_html__('User', 'autopuzzle');
            $user_phone = $_SESSION['autopuzzle_user_phone'] ?? '';
            $user_role = $_SESSION['autopuzzle_user_role'] ?? 'customer';
                }
            } else {
                // Use old session format
                $user_name = $_SESSION['autopuzzle_user_name'] ?? esc_html__('User', 'autopuzzle');
                $user_phone = $_SESSION['autopuzzle_user_phone'] ?? '';
                $user_role = $_SESSION['autopuzzle_user_role'] ?? 'customer';
            }
        }
        
        // Get role display name
        $role_translations = [
            'administrator' => esc_html__('General Manager', 'autopuzzle'),
            'autopuzzle_manager' => esc_html__('AutoPuzzle Manager', 'autopuzzle'),
            'autopuzzle_expert' => esc_html__('AutoPuzzle Expert', 'autopuzzle'),
            'customer' => esc_html__('Customer', 'autopuzzle'),
        ];
        $role_display = $role_translations[$user_role] ?? $user_role;
        
        return [
            'wp_user' => $wp_user,
            'user_id' => $user_id,
            'role' => $user_role,
            'name' => $user_name,
            'phone' => $user_phone,
            'role_display' => $role_display
        ];
    }
    
    /**
     * Check if user can access a menu item based on capability
     * 
     * @param array $item Menu item with optional 'capability' key
     * @return bool True if user has access, false otherwise
     */
    private function can_user_access_menu_item($item) {
        // If no capability requirement, allow access
        if (!isset($item['capability'])) {
            return true;
        }
        
        // Check capability for logged-in WordPress users
        if (is_user_logged_in()) {
            $wp_user = wp_get_current_user();
            return current_user_can($item['capability']);
        }
        
        // For session-based users, check based on role
        $current_user = $this->get_current_user();
        $user_role = $current_user['role'] ?? 'customer';
        
        // Default capabilities based on role
        $role_caps = [
            'administrator' => ['manage_autopuzzle_inquiries', 'edit_products', 'edit_product', 'edit_posts', 'delete_users'],
            'autopuzzle_manager' => ['manage_autopuzzle_inquiries', 'edit_products', 'edit_product', 'edit_posts', 'delete_users'],
            'autopuzzle_expert' => ['edit_products', 'edit_posts'],
            'customer' => []
        ];
        
        if (!isset($role_caps[$user_role])) {
            return false;
        }
        
        return in_array($item['capability'], $role_caps[$user_role]);
    }
    
    /**
     * Filter menu items by user capabilities, recursively
     * Removes category headers if no items below them are accessible
     * 
     * @param array $menu_items Menu items to filter
     * @return array Filtered menu items
     */
    private function filter_menu_items_by_capability($menu_items) {
        $filtered = [];
        $pending_category = null;
        
        foreach ($menu_items as $item) {
            // Category headers - store them but don't add yet
            if (isset($item['category'])) {
                $pending_category = $item;
                continue;
            }
            
            // Check if user can access this item
            if (!$this->can_user_access_menu_item($item)) {
                continue;
            }
            
            $accessible_item = null;
            
            // If item has children, filter them recursively
            if (isset($item['children']) && is_array($item['children'])) {
                $filtered_children = $this->filter_menu_items_by_capability($item['children']);
                
                // Only include parent if it has accessible children
                if (!empty($filtered_children)) {
                    $item['children'] = $filtered_children;
                    $accessible_item = $item;
                }
            } else {
                // Regular menu item without children
                $accessible_item = $item;
            }
            
            // If we found an accessible item and have a pending category, add category first
            if ($accessible_item && $pending_category) {
                $filtered[] = $pending_category;
                $pending_category = null;
            }
            
            // Add the accessible item
            if ($accessible_item) {
                $filtered[] = $accessible_item;
            }
        }
        
        return $filtered;
    }
    
    /**
     * Get menu items - Complete sidebar based on user role
     * CRITICAL: Always use fresh WordPress user roles, never cached session data
     */
    public function get_menu_items() {
        // Check if user is logged in (either WordPress or session)
        if (!$this->is_user_logged_in()) {
            return [];
        }
        
        // Check license status - if not active and not demo, return only license activation menu
        if (class_exists('Autopuzzle_License')) {
            $license = Autopuzzle_License::instance();
            if (!$license->is_license_active() && !$license->is_demo_mode()) {
                // Only show license activation menu
                return [
                    [
                        'title' => esc_html__('License Activation', 'autopuzzle'),
                        'url' => home_url('/dashboard?page=license-activation'),
                        'icon' => 'ri-key-line'
                    ]
                ];
            }
        }
        
        // Try to get WordPress user first (if WordPress login exists)
        $wp_user = null;
        if (is_user_logged_in()) {
            $wp_user = wp_get_current_user();
        } else {
            // Fallback to session-based login
            $this->maybe_start_session();
            $user_id = isset($_SESSION['maneli']['user_id']) ? (int)$_SESSION['maneli']['user_id'] : 0;
            if ($user_id) {
                $wp_user = get_user_by('ID', $user_id);
            }
        }
        
        if (!$wp_user) {
            return [];
        }
        
        // Always get fresh role from WordPress user object
        // Note: autopuzzle_admin is the actual role name, but we use 'autopuzzle_manager' as internal identifier
        $user_role = 'customer';
        if (in_array('administrator', $wp_user->roles, true)) {
                $user_role = 'administrator';
        } elseif (in_array('autopuzzle_admin', $wp_user->roles, true) || in_array('autopuzzle_manager', $wp_user->roles, true)) {
                $user_role = 'autopuzzle_manager';
        } elseif (in_array('autopuzzle_expert', $wp_user->roles, true)) {
                $user_role = 'autopuzzle_expert';
            } else {
                $user_role = 'customer';
        }
        
        $menu_items = [];
        
        // ===== مشتری (Customer) =====
        if ($user_role === 'customer') {
            // گروه اصلی
            $menu_items[] = [
                'title' => esc_html__('Home', 'autopuzzle'),
                'url' => home_url('/dashboard'),
                'icon' => 'ri-home-line'
            ];
            $menu_items[] = [
                'title' => esc_html__('Notifications', 'autopuzzle'),
                'url' => home_url('/dashboard/notifications'),
                'icon' => 'ri-notification-line'
            ];
            
            // دسته: استعلامات
            $menu_items[] = ['title' => esc_html__('Inquiries', 'autopuzzle'), 'category' => true];
            $menu_items[] = [
                'title' => esc_html__('My Inquiries', 'autopuzzle'),
                'icon' => 'ri-file-list-line',
                'children' => [
                    [
                        'title' => esc_html__('My Installment Inquiries', 'autopuzzle'),
                        'url' => home_url('/dashboard/inquiries/installment'),
                        'icon' => 'ri-bank-line'
                    ],
                    [
                        'title' => esc_html__('My Cash Inquiries', 'autopuzzle'),
                        'url' => home_url('/dashboard/inquiries/cash'),
                        'icon' => 'ri-money-dollar-circle-line'
                    ]
                ]
            ];
            
            // دسته: ثبت استعلام
            $menu_items[] = ['title' => esc_html__('Create Inquiry', 'autopuzzle'), 'category' => true];
            $menu_items[] = [
                'title' => esc_html__('Create Installment Inquiry', 'autopuzzle'),
                'url' => home_url('/dashboard/new-inquiry'),
                'icon' => 'ri-add-circle-line'
            ];
            $menu_items[] = [
                'title' => esc_html__('Create Cash Inquiry', 'autopuzzle'),
                'url' => home_url('/dashboard/new-cash-inquiry'),
                'icon' => 'ri-add-box-line'
            ];
            
            // گروه تنظیمات
            $menu_items[] = [
                'title' => esc_html__('Settings', 'autopuzzle'),
                'url' => home_url('/dashboard/profile-settings'),
                'icon' => 'ri-settings-3-line'
            ];
            $menu_items[] = [
                'title' => esc_html__('Logout', 'autopuzzle'),
                'url' => home_url('/logout'),
                'icon' => 'ri-logout-box-r-line'
            ];
        }
        
        // ===== کارشناس (AutoPuzzle Expert) =====
        elseif ($user_role === 'autopuzzle_expert') {
            // دسته: داشبورد
            $menu_items[] = ['title' => esc_html__('Dashboard', 'autopuzzle'), 'category' => true];
            $menu_items[] = [
                'title' => esc_html__('Home', 'autopuzzle'),
                'url' => home_url('/dashboard'),
                'icon' => 'ri-home-line'
            ];
            $menu_items[] = [
                'title' => esc_html__('Notifications', 'autopuzzle'),
                'url' => home_url('/dashboard/notifications'),
                'icon' => 'ri-notification-line'
            ];
            $menu_items[] = [
                'title' => esc_html__('Meeting Calendar', 'autopuzzle'),
                'url' => home_url('/dashboard/calendar'),
                'icon' => 'ri-calendar-todo-line',
                'capability' => 'edit_posts'
            ];
            
            // دسته: استعلامات
            $menu_items[] = ['title' => esc_html__('Inquiries', 'autopuzzle'), 'category' => true];
            
            // گروه استعلامات نقدی
            $menu_items[] = [
                'title' => esc_html__('Cash Inquiries', 'autopuzzle'),
                'icon' => 'ri-money-dollar-circle-line',
                'capability' => 'edit_posts',
                'children' => [
                    [
                        'title' => esc_html__('Cash Inquiries List', 'autopuzzle'),
                        'url' => home_url('/dashboard/inquiries/cash'),
                        'icon' => 'ri-file-list-3-line',
                        'capability' => 'edit_posts'
                    ],
                    [
                        'title' => esc_html__('Cash Follow-ups', 'autopuzzle'),
                        'url' => home_url('/dashboard/cash-followups'),
                        'icon' => 'ri-customer-service-2-line',
                        'capability' => 'edit_posts'
                    ]
                ]
            ];
            
            // گروه استعلامات اقساطی
            $menu_items[] = [
                'title' => esc_html__('Installment Inquiries', 'autopuzzle'),
                'icon' => 'ri-bank-line',
                'capability' => 'edit_posts',
                'children' => [
                    [
                        'title' => esc_html__('Installment Inquiries List', 'autopuzzle'),
                        'url' => home_url('/dashboard/inquiries/installment'),
                        'icon' => 'ri-file-list-2-line',
                        'capability' => 'edit_posts'
                    ],
                    [
                        'title' => esc_html__('Installment Follow-ups', 'autopuzzle'),
                        'url' => home_url('/dashboard/installment-followups'),
                        'icon' => 'ri-customer-service-2-line',
                        'capability' => 'edit_posts'
                    ]
                ]
            ];
            
            // دسته: گزارشات
            $menu_items[] = ['title' => esc_html__('Reports', 'autopuzzle'), 'category' => true];
            $menu_items[] = [
                'title' => esc_html__('Performance Reports', 'autopuzzle'),
                'url' => home_url('/dashboard/reports'),
                'icon' => 'ri-bar-chart-box-line',
                'capability' => 'edit_posts'
            ];
            
            // دسته: تنظیمات - حذف شده برای کارشناس
            // کارشناسان به تنظیمات دسترسی ندارند
            $menu_items[] = [
                'title' => esc_html__('Logout', 'autopuzzle'),
                'url' => home_url('/logout'),
                'icon' => 'ri-logout-box-r-line'
            ];
        }
        
        // ===== مدیر پلاگین (AutoPuzzle Manager) و مدیر کل (Administrator) =====
        // Both roles get the exact same menu and permissions (except wp-admin access)
        if ($user_role === 'autopuzzle_manager' || $user_role === 'administrator') {
            // دسته: داشبورد
            $menu_items[] = ['title' => esc_html__('Dashboard', 'autopuzzle'), 'category' => true];
            $menu_items[] = [
                'title' => esc_html__('Home', 'autopuzzle'),
                'url' => home_url('/dashboard'),
                'icon' => 'ri-home-line'
            ];
            $menu_items[] = [
                'title' => esc_html__('Notifications', 'autopuzzle'),
                'url' => home_url('/dashboard/notifications'),
                'icon' => 'ri-notification-line'
            ];
            $menu_items[] = [
                'title' => esc_html__('Meeting Calendar', 'autopuzzle'),
                'url' => home_url('/dashboard/calendar'),
                'icon' => 'ri-calendar-todo-line',
                'capability' => 'edit_posts'
            ];
            
            // دسته: استعلامات
            $menu_items[] = ['title' => esc_html__('Inquiries', 'autopuzzle'), 'category' => true];
            
            // گروه استعلامات نقدی
            $menu_items[] = [
                'title' => esc_html__('Cash Inquiries', 'autopuzzle'),
                'icon' => 'ri-money-dollar-circle-line',
                'capability' => 'edit_posts',
                'children' => [
                    [
                        'title' => esc_html__('Cash Inquiries List', 'autopuzzle'),
                        'url' => home_url('/dashboard/inquiries/cash'),
                        'icon' => 'ri-file-list-3-line',
                        'capability' => 'edit_posts'
                    ],
                    [
                        'title' => esc_html__('Cash Follow-ups', 'autopuzzle'),
                        'url' => home_url('/dashboard/cash-followups'),
                        'icon' => 'ri-customer-service-2-line',
                        'capability' => 'edit_posts'
                    ]
                ]
            ];
            
            // گروه استعلامات اقساطی
            $menu_items[] = [
                'title' => esc_html__('Installment Inquiries', 'autopuzzle'),
                'icon' => 'ri-bank-line',
                'capability' => 'edit_posts',
                'children' => [
                    [
                        'title' => esc_html__('Installment Inquiries List', 'autopuzzle'),
                        'url' => home_url('/dashboard/inquiries/installment'),
                        'icon' => 'ri-file-list-2-line',
                        'capability' => 'edit_posts'
                    ],
                    [
                        'title' => esc_html__('Installment Follow-ups', 'autopuzzle'),
                        'url' => home_url('/dashboard/installment-followups'),
                        'icon' => 'ri-customer-service-2-line',
                        'capability' => 'edit_posts'
                    ]
                ]
            ];
            
            // دسته: مدیریت
            $menu_items[] = ['title' => esc_html__('Management', 'autopuzzle'), 'category' => true];
            $menu_items[] = [
                'title' => esc_html__('Edit Products', 'autopuzzle'),
                'url' => home_url('/dashboard/products'),
                'icon' => 'ri-store-line',
                'capability' => 'manage_autopuzzle_inquiries'
            ];
            $menu_items[] = [
                'title' => esc_html__('Payment Management', 'autopuzzle'),
                'url' => home_url('/dashboard/payments'),
                'icon' => 'ri-bank-card-line',
                'capability' => 'manage_autopuzzle_inquiries'
            ];
            
            // دسته: کاربران
            $menu_items[] = ['title' => esc_html__('Users', 'autopuzzle'), 'category' => true];
            $menu_items[] = [
                'title' => esc_html__('User Management', 'autopuzzle'),
                'url' => home_url('/dashboard/users'),
                'icon' => 'ri-team-line',
                'capability' => 'delete_users'
            ];
            $menu_items[] = [
                'title' => esc_html__('Experts', 'autopuzzle'),
                'url' => home_url('/dashboard/experts'),
                'icon' => 'ri-user-star-line',
                'capability' => 'manage_autopuzzle_inquiries'
            ];
            
            // دسته: گزارشات
            $menu_items[] = ['title' => esc_html__('Reports', 'autopuzzle'), 'category' => true];
            $menu_items[] = [
                'title' => esc_html__('Performance Reports', 'autopuzzle'),
                'url' => home_url('/dashboard/reports'),
                'icon' => 'ri-bar-chart-box-line',
                'capability' => 'manage_autopuzzle_inquiries'
            ];
            $menu_items[] = [
                'title' => esc_html__('Visitor Statistics', 'autopuzzle'),
                'url' => home_url('/dashboard/visitor-statistics'),
                'icon' => 'ri-line-chart-line',
                'capability' => 'manage_autopuzzle_inquiries'
            ];
            $menu_items[] = [
                'title' => esc_html__('System Logs', 'autopuzzle'),
                'icon' => 'ri-file-list-3-line',
                'capability' => 'manage_autopuzzle_inquiries',
                'children' => [
                    [
                        'title' => esc_html__('System Logs', 'autopuzzle'),
                        'url' => home_url('/dashboard/logs/system'),
                        'icon' => 'ri-bug-line',
                        'capability' => 'manage_autopuzzle_inquiries'
                    ],
                    [
                        'title' => esc_html__('User Logs', 'autopuzzle'),
                        'url' => home_url('/dashboard/logs/user'),
                        'icon' => 'ri-user-line',
                        'capability' => 'manage_autopuzzle_inquiries'
                    ]
                ]
            ];
            $menu_items[] = [
                'title' => esc_html__('Notification Center', 'autopuzzle'),
                'url' => home_url('/dashboard/notifications-center'),
                'icon' => 'ri-notification-3-line',
                'capability' => 'manage_autopuzzle_inquiries',
                'children' => [
                    [
                        'title' => esc_html__('SMS Notifications', 'autopuzzle'),
                        'url' => home_url('/dashboard/notifications/sms'),
                        'icon' => 'ri-message-2-line',
                        'capability' => 'manage_autopuzzle_inquiries'
                    ],
                    [
                        'title' => esc_html__('Email Notifications', 'autopuzzle'),
                        'url' => home_url('/dashboard/notifications/email'),
                        'icon' => 'ri-mail-line',
                        'capability' => 'manage_autopuzzle_inquiries'
                    ],
                    [
                        'title' => esc_html__('Telegram Notifications', 'autopuzzle'),
                        'url' => home_url('/dashboard/notifications/telegram'),
                        'icon' => 'ri-telegram-line',
                        'capability' => 'manage_autopuzzle_inquiries'
                    ],
                    [
                        'title' => esc_html__('In-App Notifications', 'autopuzzle'),
                        'url' => home_url('/dashboard/notifications/app'),
                        'icon' => 'ri-notification-line',
                        'capability' => 'manage_autopuzzle_inquiries'
                    ]
                ]
            ];
            
            // دسته: تنظیمات
            $menu_items[] = ['title' => esc_html__('Settings', 'autopuzzle'), 'category' => true];
            $menu_items[] = [
                'title' => esc_html__('System Settings', 'autopuzzle'),
                'url' => home_url('/dashboard/settings'),
                'icon' => 'ri-settings-3-line',
                'capability' => 'manage_autopuzzle_inquiries'
            ];
            // Only show License Activation menu if license is not active
            if (class_exists('Autopuzzle_License')) {
                $license = Autopuzzle_License::instance();
                if (!$license->is_license_active() && !$license->is_demo_mode()) {
                    $menu_items[] = [
                        'title' => esc_html__('License Activation', 'autopuzzle'),
                        'url' => home_url('/dashboard?page=license-activation'),
                        'icon' => 'ri-key-line',
                        'capability' => 'manage_autopuzzle_inquiries'
                    ];
                }
            }
            $menu_items[] = [
                'title' => esc_html__('Logout', 'autopuzzle'),
                'url' => home_url('/logout'),
                'icon' => 'ri-logout-box-r-line'
            ];
        }
        
        // Filter menu items by user capabilities
        $menu_items = $this->filter_menu_items_by_capability($menu_items);
        
        return $menu_items;
    }
    
    /**
     * Check rate limiting for login attempts
     */
    private function check_rate_limit($identifier) {
        $transient_key = 'autopuzzle_login_attempts_' . md5($identifier);
        $attempts = get_transient($transient_key);
        
        if ($attempts === false) {
            set_transient($transient_key, 1, 300); // 5 minutes
            return true;
        }
        
        if ($attempts >= 5) {
            return false; // Too many attempts
        }
        
        set_transient($transient_key, $attempts + 1, 300);
        return true;
    }
    
    /**
     * Clear rate limit after successful login
     */
    private function clear_rate_limit($identifier) {
        $transient_key = 'autopuzzle_login_attempts_' . md5($identifier);
        delete_transient($transient_key);
    }
    
    /**
     * Handle dashboard login
     */
    public function handle_dashboard_login() {
        check_ajax_referer('autopuzzle_dashboard_nonce', 'nonce');
        
        $login_type = sanitize_text_field($_POST['login_type'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $password = sanitize_text_field($_POST['password'] ?? '');
        $sms_code = sanitize_text_field($_POST['sms_code'] ?? '');
        
        // Verify CAPTCHA if enabled (only for public access, not for logged-in users)
        if (!is_user_logged_in() && class_exists('Autopuzzle_Captcha_Helper') && Autopuzzle_Captcha_Helper::is_enabled()) {
            $captcha_token = '';
            $captcha_type = Autopuzzle_Captcha_Helper::get_captcha_type();
            
            // Get token based on CAPTCHA type
            if ($captcha_type === 'hcaptcha') {
                $captcha_token = isset($_POST['h-captcha-response']) ? sanitize_text_field($_POST['h-captcha-response']) : '';
            } elseif ($captcha_type === 'recaptcha_v2') {
                $captcha_token = isset($_POST['g-recaptcha-response']) ? sanitize_text_field($_POST['g-recaptcha-response']) : '';
            } elseif ($captcha_type === 'recaptcha_v3') {
                $captcha_token = isset($_POST['captcha_token']) ? sanitize_text_field($_POST['captcha_token']) : '';
            }
            
            $captcha_result = Autopuzzle_Captcha_Helper::verify_token($captcha_token, $captcha_type);
            if (!$captcha_result['success']) {
                wp_send_json_error(['message' => $captcha_result['message']]);
                return;
            }
        }
        
        // Get client IP for rate limiting
        $client_ip = $this->get_client_ip();
        $rate_limit_key = $phone . '_' . $client_ip;
        
        // Check rate limiting before processing
        if (!$this->check_rate_limit($rate_limit_key)) {
            wp_send_json_error(['message' => esc_html__('Too many login attempts. Please try again in 5 minutes.', 'autopuzzle')]);
            return;
        }
        
        if ($login_type === 'sms') {
            $this->handle_sms_login($phone, $sms_code, $rate_limit_key);
        } else {
            $this->handle_old_password_login($phone, $password, $rate_limit_key);
        }
    }
    
    /**
     * Handle SMS login
     */
    private function handle_sms_login($phone, $sms_code, $rate_limit_key = null) {
        $this->maybe_start_session();
        
        // Verify SMS code
        if ($this->verify_sms_code($phone, $sms_code)) {
            $this->set_user_session($phone, esc_html__('User', 'autopuzzle'));
            // Clear rate limit on successful login
            if ($rate_limit_key) {
                $this->clear_rate_limit($rate_limit_key);
            }
            wp_send_json_success(['redirect' => home_url('/dashboard')]);
        } else {
            wp_send_json_error(['message' => esc_html__('Invalid verification code.', 'autopuzzle')]);
        }
    }
    
    /**
     * Handle password login (old method for backward compatibility)
     */
    private function handle_old_password_login($phone, $password, $rate_limit_key = null) {
        // Verify password (implement your logic here)
        $verification_result = $this->verify_password($phone, $password);
        
        if ($verification_result === true) {
            $this->set_user_session($phone, esc_html__('User', 'autopuzzle'));
            // Clear rate limit on successful login
            if ($rate_limit_key) {
                $this->clear_rate_limit($rate_limit_key);
            }
            wp_send_json_success(['redirect' => home_url('/dashboard')]);
        } else {
            // Error message already sent by verify_password
            return;
        }
    }
    
    /**
     * Send SMS code (OTP)
     */
    public function handle_send_sms_code() {
        check_ajax_referer('autopuzzle_dashboard_nonce', 'nonce');
        
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        
        // Validate phone number
        if (!preg_match('/^09\d{9}$/', $phone)) {
            wp_send_json_error(['message' => esc_html__('Invalid mobile number.', 'autopuzzle')]);
            return;
        }
        
        $this->maybe_start_session();
        
        // Check resend delay
        $resend_delay = (int)Autopuzzle_Options_Helper::get_option('otp_resend_delay', 60);
        
        if (isset($_SESSION['autopuzzle_sms_time'])) {
            $time_elapsed = time() - $_SESSION['autopuzzle_sms_time'];
            if ($time_elapsed < $resend_delay) {
                $wait_time = $resend_delay - $time_elapsed;
                wp_send_json_error(['message' => sprintf(esc_html__('Please wait %d seconds.', 'autopuzzle'), $wait_time)]);
                return;
            }
        }
        
        // Generate 4-digit OTP code
        $code = wp_rand(1000, 9999);
        
        // Store code in session
        $_SESSION['autopuzzle_sms_code'] = $code;
        $_SESSION['autopuzzle_sms_phone'] = $phone;
        $_SESSION['autopuzzle_sms_time'] = time();
        
        // Get OTP pattern code from settings
        $otp_pattern = $options['otp_pattern_code'] ?? '';
        
        // Send SMS using existing SMS handler
        $sms_handler = new Autopuzzle_SMS_Handler();
        
        // Debug logging (only in debug mode for security)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            // Mask sensitive data for logging
            $phone_masked = substr($phone, 0, 4) . '****';
            error_log('OTP Send Attempt - Phone: ' . $phone_masked);
            error_log('OTP Pattern: ' . ($otp_pattern ? 'SET' : 'NOT SET'));
            
            if (!empty($otp_pattern)) {
                error_log('Sending OTP via Pattern - Body ID: ' . $otp_pattern);
                // Don't log OTP code for security
            }
        }
        
        if (!empty($otp_pattern)) {
            // Use pattern-based SMS (MeliPayamak)
            // متغیرها در پترن: {0} = کد OTP
            
            $result = $sms_handler->send_pattern(
                $otp_pattern,      // bodyId (Pattern Code)
                $phone,            // recipient
                [$code]            // parameters - {0} = OTP code
            );
            
            // اگر پترن کار نکرد، fallback به SMS معمولی
            if (!$result) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Pattern failed, trying regular SMS as fallback...');
                }
                $message = sprintf(esc_html__('AutoPuzzle verification code: %s', 'autopuzzle'), $code);
                $result = $sms_handler->send_sms($phone, $message);
            }
        } else {
            // Fallback to regular SMS if pattern not configured
            if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Sending OTP via Regular SMS (Pattern not configured)');
            }
            $message = sprintf(esc_html__('Your verification code: %s', 'autopuzzle'), $code);
            $result = $sms_handler->send_sms($phone, $message);
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('OTP Send Result: ' . ($result ? 'SUCCESS' : 'FAILED'));
        }
        
        if ($result) {
            wp_send_json_success(['message' => esc_html__('Verification code sent.', 'autopuzzle')]);
        } else {
            // بررسی اینکه آیا تنظیمات SMS وجود دارد یا نه
            $sms_username = $options['sms_username'] ?? '';
            $sms_password = $options['sms_password'] ?? '';
            
            // Check for common issues in order of priority
            if (empty($sms_username) || empty($sms_password)) {
                wp_send_json_error(['message' => esc_html__('SMS settings are not configured. Please go to Settings and enter your SMS panel information.', 'autopuzzle')]);
            } elseif (!class_exists('SoapClient')) {
                wp_send_json_error(['message' => esc_html__('PHP SOAP extension is not enabled on the server. Please contact your hosting provider.', 'autopuzzle')]);
            } elseif (!defined('AUTOPUZZLE_SMS_API_WSDL')) {
                wp_send_json_error(['message' => esc_html__('SMS API configuration error. Please contact support.', 'autopuzzle')]);
            } elseif (!empty($otp_pattern) && !$result) {
                // Pattern is configured but failed - could be invalid pattern or API error
                wp_send_json_error(['message' => esc_html__('Error sending SMS. Please check your SMS panel information and pattern code.', 'autopuzzle')]);
            } else {
                // General error - could be API issue, invalid credentials, or network problem
                wp_send_json_error(['message' => esc_html__('Error sending SMS. Please check your SMS panel information, credentials, and network connection.', 'autopuzzle')]);
            }
        }
    }
    
    /**
     * Verify SMS code (OTP)
     */
    private function verify_sms_code($phone, $code) {
        $this->maybe_start_session();
        
        if (!isset($_SESSION['autopuzzle_sms_code']) || 
            !isset($_SESSION['autopuzzle_sms_phone']) || 
            !isset($_SESSION['autopuzzle_sms_time'])) {
            return false;
        }
        
        // Get OTP expiry time from settings
        $expiry_minutes = (int)Autopuzzle_Options_Helper::get_option('otp_expiry_minutes', 5);
        $expiry_seconds = $expiry_minutes * 60;
        
        // Check if code is expired
        if (time() - $_SESSION['autopuzzle_sms_time'] > $expiry_seconds) {
            return false;
        }
        
        return $_SESSION['autopuzzle_sms_code'] == $code && 
               $_SESSION['autopuzzle_sms_phone'] == $phone;
    }
    
    /**
     * Verify password using WordPress password hashing
     */
    private function verify_password($phone, $password) {
        $saved_password = Autopuzzle_Options_Helper::get_option('dashboard_password', '');
        
        // Check if no password is set - require admin to set password first
        if (empty($saved_password)) {
            wp_send_json_error([
                'message' => esc_html__('Dashboard password not configured. Please contact administrator to set up dashboard access.', 'autopuzzle'),
                'code' => 'NO_PASSWORD_SET'
            ]);
            return false;
        }
        
        // Normal password verification
        // Check if it's an old plain text password
        if (strlen($saved_password) < 60 && !password_get_info($saved_password)['algo']) {
            // Old plain text password - verify and upgrade to hash
            if ($saved_password === $password) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $options['dashboard_password'] = $hashed_password;
                update_option('autopuzzle_inquiry_all_options', $options);
                return true;
            }
            return false;
        }
        
        // Use secure password verification
        return password_verify($password, $saved_password);
    }
    
    /**
     * Set user session with additional security measures
     */
    private function set_user_session($phone, $name) {
        $this->maybe_start_session();
        
        // Regenerate session ID to prevent session fixation attacks
        session_regenerate_id(true);
        
        $_SESSION['autopuzzle_dashboard_logged_in'] = true;
        $_SESSION['autopuzzle_user_phone'] = $phone;
        $_SESSION['autopuzzle_user_name'] = $name;
        $_SESSION['autopuzzle_user_role'] = 'user';
        $_SESSION['autopuzzle_login_time'] = time();
        $_SESSION['autopuzzle_login_ip'] = $this->get_client_ip();
        
        // Set session timeout
        $_SESSION['autopuzzle_session_timeout'] = 3600; // 1 hour
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                return sanitize_text_field($_SERVER[$key]);
            }
        }
        return '0.0.0.0';
    }
    
    /**
     * Check if session is still valid
     */
    private function is_session_valid() {
        $this->maybe_start_session();
        
        if (!isset($_SESSION['autopuzzle_dashboard_logged_in']) || !$_SESSION['autopuzzle_dashboard_logged_in']) {
            return false;
        }
        
        // Check session timeout
        if (isset($_SESSION['autopuzzle_login_time']) && isset($_SESSION['autopuzzle_session_timeout'])) {
            if (time() - $_SESSION['autopuzzle_login_time'] > $_SESSION['autopuzzle_session_timeout']) {
                $this->destroy_session();
                return false;
            }
        }
        
        // Check IP address (optional - can be disabled if users have dynamic IPs)
        if (isset($_SESSION['autopuzzle_login_ip']) && $_SESSION['autopuzzle_login_ip'] !== '0.0.0.0') {
            $current_ip = $this->get_client_ip();
            if ($_SESSION['autopuzzle_login_ip'] !== $current_ip) {
                // IP changed - log it but don't block (could be legitimate)
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    // Hash IP addresses for logging to protect privacy
                    $old_ip_hash = substr(md5($_SESSION['autopuzzle_login_ip']), 0, 8);
                    $new_ip_hash = substr(md5($current_ip), 0, 8);
                    error_log('AutoPuzzle Dashboard: IP address changed during session (hashed: ' . $old_ip_hash . ' -> ' . $new_ip_hash . ')');
                }
            }
        }
        
        return true;
    }
    
    /**
     * Destroy session securely
     */
    private function destroy_session() {
        $this->maybe_start_session();
        $_SESSION = array();
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        session_destroy();
    }
    
    /**
     * Handle logout
     */
    public function handle_logout() {
        // Destroy custom session
        $this->destroy_session();
        
        // Also logout from WordPress if user is logged in
        if (is_user_logged_in()) {
            wp_logout();
        }
        
        wp_redirect(home_url('/login'));
        exit;
    }
    
    /**
     * Handle AJAX logout
     */
    public function handle_dashboard_logout() {
        // Destroy custom session
        $this->destroy_session();
        
        // Also logout from WordPress if user is logged in
        if (is_user_logged_in()) {
            wp_logout();
        }
        
        wp_send_json_success(['redirect' => home_url('/login')]);
    }

    /**
     * Get total revenue from successful inquiries
     */
    public function get_total_revenue() {
        global $wpdb;

        $total = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(CAST(meta_value AS UNSIGNED))
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_type = %s
            AND p.post_status = %s
            AND pm.meta_key = %s
        ", 'inquiry', 'publish', 'inquiry_price'));

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

        $pending_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = %s
            AND p.post_status = %s
            AND pm.meta_key = %s
            AND pm.meta_value = %s
        ", 'inquiry', 'publish', 'inquiry_status', 'pending'));

        return $pending_count ? $pending_count : 0;
    }
    
    /**
     * Handle send OTP for unified login/registration
     */
    public function handle_send_otp() {
        check_ajax_referer('autopuzzle-ajax-nonce', 'nonce');
        
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        
        // Validate phone number
        if (!preg_match('/^09\d{9}$/', $phone)) {
            wp_send_json_error(['message' => esc_html__('Invalid mobile number.', 'autopuzzle')]);
            return;
        }
        
        // Create or get user
        $user_login = $phone;
        $email = $phone . '@manelikhodro.com';
        
        $user_id = username_exists($user_login);
        if (!$user_id) {
            $user_id = email_exists($email);
        }
        
        // If user doesn't exist, create placeholder user
        if (!$user_id) {
            $random_password = wp_generate_password(32, true, true);
            $user_id = wp_create_user($user_login, $random_password, $email);
            
            if (is_wp_error($user_id)) {
                wp_send_json_error(['message' => esc_html__('Error creating user account.', 'autopuzzle')]);
                return;
            }
            
            // Set role to customer
            $user = new WP_User($user_id);
            $user->set_role('customer');
        }
        
        // Store OTP in session and send SMS
        $this->maybe_start_session();
        
        // Check resend delay
        $resend_delay = (int)Autopuzzle_Options_Helper::get_option('otp_resend_delay', 60);
        
        if (isset($_SESSION['autopuzzle_sms_time'])) {
            $time_elapsed = time() - $_SESSION['autopuzzle_sms_time'];
            if ($time_elapsed < $resend_delay) {
                $wait_time = $resend_delay - $time_elapsed;
                wp_send_json_error(['message' => sprintf(esc_html__('Please wait %d seconds.', 'autopuzzle'), $wait_time)]);
                return;
            }
        }
        
        // Generate 4-digit OTP code
        $code = wp_rand(1000, 9999);
        
        // Store code in session
        $_SESSION['autopuzzle_sms_code'] = $code;
        $_SESSION['autopuzzle_sms_phone'] = $phone;
        $_SESSION['autopuzzle_sms_time'] = time();
        
        // Get OTP pattern code from settings and send SMS
        $options = Autopuzzle_Options_Helper::get_all_options();
        $otp_pattern = $options['otp_pattern_code'] ?? '';
        $sms_handler = new Autopuzzle_SMS_Handler();
        
        // Reset error code before sending
        $sms_handler->reset_error_code();
        
        // Debug logging (only in debug mode for security)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            // Mask sensitive data for logging
            $phone_masked = substr($phone, 0, 4) . '****';
            error_log('OTP Send Attempt - Phone: ' . $phone_masked);
            error_log('OTP Pattern: ' . ($otp_pattern ? 'SET (' . $otp_pattern . ')' : 'NOT SET'));
        }
        
        $result = false;
        if (!empty($otp_pattern)) {
            // Use pattern-based SMS (MeliPayamak)
            // متغیرها در پترن: {0} = کد OTP
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Sending OTP via Pattern - Body ID: ' . $otp_pattern);
            }
            $result = $sms_handler->send_pattern($otp_pattern, $phone, [$code]);
            
            // اگر پترن کار نکرد، fallback به SMS معمولی
            if (!$result) {
                // Reset error code before fallback attempt
                $sms_handler->reset_error_code();
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Pattern failed, trying regular SMS as fallback...');
                }
                $message = sprintf(esc_html__('AutoPuzzle verification code: %s', 'autopuzzle'), $code);
                $result = $sms_handler->send_sms($phone, $message);
            }
        } else {
            // Fallback to regular SMS if pattern not configured
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Sending OTP via Regular SMS (Pattern not configured)');
            }
            $message = sprintf(esc_html__('Your verification code: %s', 'autopuzzle'), $code);
            $result = $sms_handler->send_sms($phone, $message);
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('OTP Send Result: ' . ($result ? 'SUCCESS' : 'FAILED'));
        }
        
        if ($result) {
            wp_send_json_success(['message' => esc_html__('Verification code sent.', 'autopuzzle')]);
        } else {
            // بررسی اینکه آیا تنظیمات SMS وجود دارد یا نه
            $sms_username = $options['sms_username'] ?? '';
            $sms_password = $options['sms_password'] ?? '';
            
            // Get error code from SMS handler for detailed error message
            $error_code = $sms_handler->get_last_error_code();
            
            // Check for common issues in order of priority
            if (empty($sms_username) || empty($sms_password)) {
                wp_send_json_error(['message' => esc_html__('SMS settings are not configured. Please go to Settings and enter your SMS panel information.', 'autopuzzle')]);
            } elseif (!class_exists('SoapClient')) {
                wp_send_json_error(['message' => esc_html__('PHP SOAP extension is not enabled on the server. Please contact your hosting provider.', 'autopuzzle')]);
            } elseif (!defined('AUTOPUZZLE_SMS_API_WSDL')) {
                wp_send_json_error(['message' => esc_html__('SMS API configuration error. Please contact support.', 'autopuzzle')]);
            } elseif ($error_code !== null) {
                // Detailed error message based on API error code
                $error_messages = [
                    1 => esc_html__('Invalid SMS panel username or password. Please check your credentials in Settings.', 'autopuzzle'),
                    2 => esc_html__('Your SMS panel account is restricted or limited. Please contact your SMS panel support or check your account status.', 'autopuzzle'),
                    3 => esc_html__('Insufficient credit in your SMS panel. Please recharge your account.', 'autopuzzle'),
                    4 => esc_html__('Invalid pattern code. Please check the pattern code (Body ID) in Settings.', 'autopuzzle'),
                    5 => esc_html__('Invalid phone number format. Please check the recipient number.', 'autopuzzle'),
                ];
                
                $error_message = $error_messages[$error_code] ?? esc_html__('Error sending SMS. API returned error code: ', 'autopuzzle') . $error_code . '. Please check your SMS panel settings and contact support if the problem persists.';
                wp_send_json_error(['message' => $error_message]);
            } elseif (!empty($otp_pattern) && !$result) {
                // Pattern is configured but failed - could be invalid pattern or API error
                wp_send_json_error(['message' => esc_html__('Error sending SMS. Please check your SMS panel information and pattern code.', 'autopuzzle')]);
            } else {
                // General error - could be API issue, invalid credentials, or network problem
                wp_send_json_error(['message' => esc_html__('Error sending SMS. Please check your SMS panel information, credentials, and network connection.', 'autopuzzle')]);
            }
        }
    }
    
    /**
     * Handle verify OTP for unified login/registration
     */
    public function handle_verify_otp() {
        check_ajax_referer('autopuzzle-ajax-nonce', 'nonce');
        
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $otp = sanitize_text_field($_POST['otp'] ?? '');
        
        if (!$this->verify_sms_code($phone, $otp)) {
            wp_send_json_error(['message' => esc_html__('Invalid or expired verification code.', 'autopuzzle')]);
            return;
        }
        
        // Get user
        $user_login = $phone;
        $user = get_user_by('login', $user_login);
        if (!$user) {
            $email = $phone . '@manelikhodro.com';
            $user = get_user_by('email', $email);
        }
        
        if (!$user) {
            wp_send_json_error(['message' => esc_html__('User not found.', 'autopuzzle')]);
            return;
        }
        
        // Check if user needs to set password
        $needs_password = get_user_meta($user->ID, 'autopuzzle_needs_password', true);
        
        // If user has never set a password, they need to create one
        if (!$needs_password && $user->ID > 0) {
            $last_login = get_user_meta($user->ID, 'autopuzzle_last_login', true);
            if (empty($last_login)) {
                update_user_meta($user->ID, 'autopuzzle_needs_password', '1');
                $needs_password = true;
            }
        }
        
        if ($needs_password || get_user_meta($user->ID, 'autopuzzle_needs_password', true) === '1') {
            // Store phone in session for create-password page (secure)
            $this->maybe_start_session();
            $_SESSION['autopuzzle_create_password_phone'] = $phone;
            
            // Return success with needs_password flag
            // Password creation is now handled in the same login page (no redirect)
            wp_send_json_success([
                'message' => esc_html__('Verification successful. Please create a password.', 'autopuzzle'),
                'needs_password' => true,
                'redirect' => null // No redirect - handled in same page
            ]);
        } else {
            // User already has password, log them in
            // CRITICAL: Use WordPress native authentication
            wp_set_current_user($user->ID, $user->user_login);
            wp_set_auth_cookie($user->ID, true); // true = remember me
            
            // Also set custom session for backward compatibility (if needed by other parts)
            $this->maybe_start_session();
            $_SESSION['autopuzzle_dashboard_logged_in'] = true;
            $_SESSION['autopuzzle_user_id'] = $user->ID;
            $_SESSION['autopuzzle_user_role'] = 'customer';
            $_SESSION['autopuzzle_user_phone'] = $phone;
            $_SESSION['autopuzzle_user_name'] = $user->display_name;
            
            // Update last login
            update_user_meta($user->ID, 'autopuzzle_last_login', time());
            
            // Clear OTP session
            unset($_SESSION['autopuzzle_sms_code']);
            unset($_SESSION['autopuzzle_sms_phone']);
            
            // Get redirect URL from session if available
            $this->maybe_start_session();
            $redirect_url = isset($_SESSION['autopuzzle_redirect_after_login']) ? $_SESSION['autopuzzle_redirect_after_login'] : home_url('/dashboard');
            
            // Clear redirect from session after reading it
            unset($_SESSION['autopuzzle_redirect_after_login']);
            
            wp_send_json_success([
                'message' => esc_html__('Login successful.', 'autopuzzle'),
                'needs_password' => false,
                'redirect' => $redirect_url
            ]);
        }
    }
    
    /**
     * Handle create password for new users
     */
    public function handle_create_password() {
        check_ajax_referer('autopuzzle-ajax-nonce', 'nonce');
        
        // Get phone from session (secure)
        $this->maybe_start_session();
        $phone = isset($_SESSION['autopuzzle_create_password_phone']) ? sanitize_text_field($_SESSION['autopuzzle_create_password_phone']) : '';
        $password = isset($_POST['password']) ? trim($_POST['password']) : '';
        
        // Check if phone exists in session
        if (empty($phone)) {
            wp_send_json_error(['message' => esc_html__('Invalid session. Please restart the process.', 'autopuzzle')]);
            return;
        }
        
        if (strlen($password) < 6) {
            wp_send_json_error(['message' => esc_html__('Password must be at least 6 characters.', 'autopuzzle')]);
            return;
        }
        
        // Get user
        $user_login = $phone;
        $user = get_user_by('login', $user_login);
        if (!$user) {
            $email = $phone . '@manelikhodro.com';
            $user = get_user_by('email', $email);
        }
        
        if (!$user) {
            wp_send_json_error(['message' => esc_html__('User not found.', 'autopuzzle')]);
            return;
        }
        
        // Update user password
        wp_set_password($password, $user->ID);
        
        // Remove needs_password flag
        delete_user_meta($user->ID, 'autopuzzle_needs_password');
        
        // CRITICAL FIX: Log user in using WordPress native authentication
        wp_set_current_user($user->ID, $user->user_login);
        wp_set_auth_cookie($user->ID, true); // true = remember me
        
        // Also set custom session for backward compatibility (if needed by other parts)
        $this->maybe_start_session();
        $_SESSION['autopuzzle_dashboard_logged_in'] = true;
        $_SESSION['autopuzzle_user_id'] = $user->ID;
        $_SESSION['autopuzzle_user_role'] = 'customer';
        $_SESSION['autopuzzle_user_phone'] = $phone;
        $_SESSION['autopuzzle_user_name'] = $user->display_name;
        
        // Update last login
        update_user_meta($user->ID, 'autopuzzle_last_login', time());
        
        // Clear temporary session data (but keep user session!)
        unset($_SESSION['autopuzzle_create_password_phone']);
        unset($_SESSION['autopuzzle_sms_code']);
        unset($_SESSION['autopuzzle_sms_phone']);
        
        // Get redirect URL from session if available
        $redirect_url = isset($_SESSION['autopuzzle_redirect_after_login']) ? $_SESSION['autopuzzle_redirect_after_login'] : home_url('/dashboard');
        
        // Clear redirect from session after reading it
        unset($_SESSION['autopuzzle_redirect_after_login']);
        
        wp_send_json_success([
            'message' => esc_html__('Password created successfully. Logging you in...', 'autopuzzle'),
            'redirect' => $redirect_url
        ]);
    }
    
    /**
     * Handle password login
     */
    public function handle_password_login() {
        check_ajax_referer('autopuzzle-ajax-nonce', 'nonce');
        
        $login_field = sanitize_text_field($_POST['phone'] ?? '');
        $password = isset($_POST['password']) ? trim($_POST['password']) : '';
        
        if (empty($login_field) || empty($password)) {
            wp_send_json_error(['message' => esc_html__('Username/Phone and password are required.', 'autopuzzle')]);
            return;
        }
        
        // Get user - try username first, then phone, then email
        $user = get_user_by('login', $login_field);
        
        if (!$user) {
            // Try phone number
            $user = get_user_by('login', $login_field);
            if (!$user) {
                $email = $login_field . '@manelikhodro.com';
                $user = get_user_by('email', $email);
            }
        }
        
        if (!$user) {
            wp_send_json_error(['message' => esc_html__('Invalid username/phone or password.', 'autopuzzle')]);
            return;
        }
        
        // Verify password - trim whitespace to avoid issues
        $password = trim($password);
        
        if (empty($password)) {
            wp_send_json_error(['message' => esc_html__('Password is required.', 'autopuzzle')]);
            return;
        }
        
        // Check password using WordPress password verification
        if (!wp_check_password($password, $user->user_pass, $user->ID)) {
            // Log failed attempt for debugging (only in debug mode)
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AutoPuzzle Login: Password verification failed for user ID: ' . $user->ID . ' (Login: ' . substr($login_field, 0, 4) . '****)');
            }
            wp_send_json_error(['message' => esc_html__('Invalid username/phone or password.', 'autopuzzle')]);
            return;
        }
        
        // Log user in using WordPress native authentication
        wp_set_current_user($user->ID, $user->user_login);
        wp_set_auth_cookie($user->ID, true); // true = remember me
        
        // Also set custom session for backward compatibility (if needed by other parts)
        $this->maybe_start_session();
        $_SESSION['autopuzzle_dashboard_logged_in'] = true;
        $_SESSION['autopuzzle_user_id'] = $user->ID;
        $_SESSION['autopuzzle_user_role'] = 'customer';
        $_SESSION['autopuzzle_user_phone'] = $user->user_login;
        
        // Update last login
        update_user_meta($user->ID, 'autopuzzle_last_login', time());
        
        // Get redirect URL from session if available
        $redirect_url = isset($_SESSION['autopuzzle_redirect_after_login']) ? $_SESSION['autopuzzle_redirect_after_login'] : home_url('/dashboard');
        
        // Clear redirect from session after reading it
        unset($_SESSION['autopuzzle_redirect_after_login']);
        
        wp_send_json_success([
            'message' => esc_html__('Login successful.', 'autopuzzle'),
            'redirect' => $redirect_url
        ]);
    }
}

