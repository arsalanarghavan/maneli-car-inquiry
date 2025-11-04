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
        
        // New unified login/registration AJAX handlers
        add_action('wp_ajax_maneli_send_otp', [$this, 'handle_send_otp']);
        add_action('wp_ajax_nopriv_maneli_send_otp', [$this, 'handle_send_otp']);
        add_action('wp_ajax_maneli_verify_otp', [$this, 'handle_verify_otp']);
        add_action('wp_ajax_nopriv_maneli_verify_otp', [$this, 'handle_verify_otp']);
        add_action('wp_ajax_maneli_create_password', [$this, 'handle_create_password']);
        add_action('wp_ajax_nopriv_maneli_create_password', [$this, 'handle_create_password']);
        add_action('wp_ajax_maneli_password_login', [$this, 'handle_password_login']);
        add_action('wp_ajax_nopriv_maneli_password_login', [$this, 'handle_password_login']);
        
        // مخفی کردن Admin Bar در داشبورد
        add_action('template_redirect', [$this, 'hide_admin_bar_in_dashboard']);
        
        // Show notice if rewrite rules need flushing
        if (get_option('maneli_dashboard_rules_flushed') !== '4') {
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
                <p><?php esc_html_e('Maneli Car Inquiry: Please save the Settings once to update rewrite rules.', 'maneli-car-inquiry'); ?></p>
            </div>
            <?php
        }
    }
    
    /**
     * Enqueue global fonts for all plugin pages
     */
    public function enqueue_global_fonts() {
        // Only enqueue fonts on dashboard pages to avoid 404 errors on frontend
        if (!get_query_var('maneli_dashboard')) {
            return;
        }
        
        $font_css_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/css/maneli-fonts.css';
        if (file_exists($font_css_path)) {
            wp_enqueue_style('maneli-fonts', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-fonts.css', [], '1.0.0');
        }
        
        $rtl_css_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/css/maneli-rtl-force.css';
        if (file_exists($rtl_css_path)) {
            wp_enqueue_style('maneli-rtl-force', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-rtl-force.css', ['maneli-fonts'], '1.0.0');
        }
        
        $dashboard_fix_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/css/maneli-dashboard-fix.css';
        if (file_exists($dashboard_fix_path)) {
            wp_enqueue_style('maneli-dashboard-fix', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-dashboard-fix.css', ['maneli-rtl-force'], '1.0.0');
        }
        
        $loader_fix_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/css/maneli-loader-fix.css';
        if (file_exists($loader_fix_path)) {
            wp_enqueue_style('maneli-loader-fix', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-loader-fix.css', ['maneli-dashboard-fix'], '1.0.0');
        }
        
        $sidebar_fixes_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/css/sidebar-fixes.css';
        if (file_exists($sidebar_fixes_path)) {
            wp_enqueue_style('maneli-sidebar-fixes', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/sidebar-fixes.css', ['maneli-loader-fix'], filemtime($sidebar_fixes_path));
        }
    }
    
    /**
     * Add rewrite rules for dashboard
     */
    public function add_rewrite_rules() {
        add_rewrite_rule('^login/?$', 'index.php?maneli_dashboard=1&maneli_dashboard_page=login', 'top');
        add_rewrite_rule('^logout/?$', 'index.php?maneli_dashboard=1&maneli_dashboard_page=logout', 'top');
        add_rewrite_rule('^verify-otp/?$', 'index.php?maneli_dashboard=1&maneli_dashboard_page=verify-otp', 'top');
        add_rewrite_rule('^create-password/?$', 'index.php?maneli_dashboard=1&maneli_dashboard_page=create-password', 'top');
        add_rewrite_rule('^dashboard/?$', 'index.php?maneli_dashboard=1', 'top');
        add_rewrite_rule('^dashboard/([^/]+)/?$', 'index.php?maneli_dashboard=1&maneli_dashboard_page=$matches[1]', 'top');
        add_rewrite_rule('^dashboard/([^/]+)/([^/]+)/?$', 'index.php?maneli_dashboard=1&maneli_dashboard_page=$matches[1]&maneli_dashboard_subpage=$matches[2]', 'top');
        
        // Flush rewrite rules only once during activation
        if (get_option('maneli_dashboard_rules_flushed') !== '4') {
            add_action('init', function() {
                flush_rewrite_rules();
                update_option('maneli_dashboard_rules_flushed', '4');
            }, 999);
        }
    }
    
    /**
     * Add query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'maneli_dashboard';
        $vars[] = 'maneli_dashboard_page';
        $vars[] = 'maneli_dashboard_subpage';
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
        if (get_query_var('maneli_dashboard')) {
            // Start session early before any output or redirects
            $this->maybe_start_session();
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
            // Enqueue CSS - check if font CSS exists
            $font_css_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/css/maneli-fonts.css';
            $font_deps = [];
            if (file_exists($font_css_path)) {
                wp_enqueue_style('maneli-fonts', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-fonts.css', [], '1.0.0');
                $font_deps[] = 'maneli-fonts';
            }
            wp_enqueue_style('maneli-bootstrap', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/bootstrap/css/bootstrap.rtl.min.css', $font_deps, '5.3.0');
            wp_enqueue_style('maneli-styles', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/styles.css', ['maneli-bootstrap'], '1.0.0');
            
            // Line Awesome Complete - فایل CSS کامل و مستقل
            $line_awesome_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/css/maneli-line-awesome-complete.css';
            if (file_exists($line_awesome_path)) {
                wp_enqueue_style('maneli-line-awesome-complete', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-line-awesome-complete.css', [], '1.0.0');
            }
            
            wp_enqueue_style('maneli-waves', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/node-waves/waves.min.css', [], '1.0.0');
            wp_enqueue_style('maneli-simplebar', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/simplebar/simplebar.min.css', [], '1.0.0');
            wp_enqueue_style('maneli-flatpickr', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/flatpickr/flatpickr.min.css', [], '1.0.0');
            wp_enqueue_style('maneli-pickr', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/@simonwep/pickr/themes/nano.min.css', [], '1.0.0');
            wp_enqueue_style('maneli-choices', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/choices.js/public/assets/styles/choices.min.css', [], '1.0.0');
            wp_enqueue_style('maneli-autocomplete', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/@tarekraafat/autocomplete.js/css/autoComplete.css', [], '1.0.0');
            wp_enqueue_style('maneli-persian-datepicker', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/persianDatepicker-default.css', [], '1.0.0');
            // Force RTL and Persian Font - check if file exists
            $rtl_css_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/css/maneli-rtl-force.css';
            $rtl_deps = ['maneli-styles', 'maneli-bootstrap'];
            if (file_exists($rtl_css_path)) {
                wp_enqueue_style('maneli-rtl-force', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-rtl-force.css', $rtl_deps, '1.0.0');
                $dashboard_fix_deps = ['maneli-rtl-force'];
            } else {
                $dashboard_fix_deps = $rtl_deps;
            }
            
            // Dashboard Additional Fixes - check if file exists
            $dashboard_fix_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/css/maneli-dashboard-fix.css';
            if (file_exists($dashboard_fix_path)) {
                wp_enqueue_style('maneli-dashboard-fix', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-dashboard-fix.css', $dashboard_fix_deps, '1.0.0');
                $loader_fix_deps = ['maneli-dashboard-fix'];
            } else {
                $loader_fix_deps = $dashboard_fix_deps;
            }
            
            // Loader Fix - Prevent infinite loading - check if file exists
            $loader_fix_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/css/maneli-loader-fix.css';
            if (file_exists($loader_fix_path)) {
                wp_enqueue_style('maneli-loader-fix', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-loader-fix.css', $loader_fix_deps, '1.0.0');
            }
            // Custom styles for status indicators and expert names
            wp_enqueue_style('maneli-custom', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-custom.css', ['maneli-loader-fix'], filemtime(MANELI_INQUIRY_PLUGIN_PATH . 'assets/css/maneli-custom.css'));
            // Inline styles replacement CSS
            wp_enqueue_style('maneli-inline-styles', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-inline-styles.css', [], filemtime(MANELI_INQUIRY_PLUGIN_PATH . 'assets/css/maneli-inline-styles.css'));
            
            // TEST: Load test script first to verify enqueue is working
            wp_enqueue_script('maneli-test-loader', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/frontend/maneli-test-loader.js', ['jquery'], '1.0.0', false);
            
            // Enqueue JS - Ensure jQuery is loaded
            // Don't deregister, use WordPress built-in jQuery
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
            wp_enqueue_script('maneli-sticky', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/sticky.js', ['jquery'], '1.0.0', true);
            wp_enqueue_script('maneli-defaultmenu', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/defaultmenu.min.js', ['jquery'], '1.0.0', true);
            wp_enqueue_script('maneli-custom', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/custom.js', ['jquery', 'maneli-bootstrap'], '1.0.0', true);
            wp_enqueue_script('maneli-custom-switcher', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/custom-switcher.min.js', ['jquery'], '1.0.0', true);
            wp_enqueue_script('maneli-persian-datepicker', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/persianDatepicker.min.js', ['jquery'], '1.0.0', true);
            // Real-time notifications
            wp_enqueue_script('maneli-notifications', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/notifications.js', ['jquery'], '1.0.0', true);
            // Localize script for notifications
            wp_localize_script('maneli-notifications', 'maneli_ajax', array(
                'url' => admin_url('admin-ajax.php'),
                'notifications_nonce' => wp_create_nonce('maneli_notifications_nonce'),
                'ajax_url' => admin_url('admin-ajax.php'), // Also add for compatibility
            ));
            // wp_enqueue_script('maneli-dashboard', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/dashboard.js', ['jquery'], '1.0.0', true);
            // Dashboard.js file does not exist - commented out
            
            // Page-specific scripts
            $page = get_query_var('maneli_dashboard_page');
            $subpage = get_query_var('maneli_dashboard_subpage');
            
            // Reports page - Chart.js for charts
            if ($page === 'reports') {
                $chartjs_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/libs/chart.js/chart.umd.js';
                if (file_exists($chartjs_path)) {
                    wp_enqueue_script('chartjs', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/chart.js/chart.umd.js', ['jquery'], '4.4.0', false);
                } else {
                    // Fallback to CDN
                    wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', ['jquery'], '4.4.0', false);
                }
            }
            
            // Visitor Statistics page - ApexCharts and scripts
            if ($page === 'visitor-statistics') {
                // ApexCharts
                $apexcharts_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/libs/apexcharts/apexcharts.min.js';
                if (file_exists($apexcharts_path)) {
                    wp_enqueue_script('apexcharts', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/apexcharts/apexcharts.min.js', ['jquery'], '3.44.0', true);
                    $apexcharts_css_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/libs/apexcharts/apexcharts.css';
                    if (file_exists($apexcharts_css_path)) {
                        wp_enqueue_style('apexcharts', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/apexcharts/apexcharts.css', [], '3.44.0');
                    }
                } else {
                    // Fallback to CDN
                    wp_enqueue_script('apexcharts', 'https://cdn.jsdelivr.net/npm/apexcharts@3.44.0/dist/apexcharts.min.js', ['jquery'], '3.44.0', true);
                    wp_enqueue_style('apexcharts', 'https://cdn.jsdelivr.net/npm/apexcharts@3.44.0/dist/apexcharts.css', [], '3.44.0');
                }
                
                // Persian Datepicker
                if (!wp_script_is('maneli-persian-datepicker', 'enqueued')) {
                    wp_enqueue_script('maneli-persian-datepicker', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/persianDatepicker.min.js', ['jquery'], '1.0.0', true);
                    wp_enqueue_style('maneli-persian-datepicker', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/persianDatepicker-default.css', [], '1.0.0');
                }
                
                // Visitor Statistics Dashboard Script
                $dashboard_js_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/js/admin/visitor-statistics-dashboard.js';
                if (file_exists($dashboard_js_path)) {
                    wp_enqueue_script('maneli-visitor-statistics-dashboard', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/admin/visitor-statistics-dashboard.js', ['jquery', 'apexcharts'], filemtime($dashboard_js_path), true);
                    
                    // Localize script
                    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));
                    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');
                    
                    // Get daily stats for chart
                    require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-visitor-statistics.php';
                    $daily_stats = Maneli_Visitor_Statistics::get_daily_visits($start_date, $end_date);
                    
                    wp_localize_script('maneli-visitor-statistics-dashboard', 'maneliVisitorStats', [
                        'ajaxUrl' => admin_url('admin-ajax.php'),
                        'nonce' => wp_create_nonce('maneli_visitor_stats_nonce'),
                        'startDate' => $start_date,
                        'endDate' => $end_date,
                        'dailyStats' => $daily_stats,
                        'translations' => [
                            'loading' => esc_html__('Loading...', 'maneli-car-inquiry'),
                            'error' => esc_html__('Error loading data', 'maneli-car-inquiry'),
                            'noData' => esc_html__('No data available', 'maneli-car-inquiry'),
                            'visits' => esc_html__('Visits', 'maneli-car-inquiry'),
                            'uniqueVisitors' => esc_html__('Unique Visitors', 'maneli-car-inquiry'),
                            'pages' => esc_html__('Pages', 'maneli-car-inquiry'),
                            'date' => esc_html__('Date', 'maneli-car-inquiry'),
                        ]
                    ]);
                }
                
                // Visitor Statistics CSS
                $dashboard_css_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/css/visitor-statistics.css';
                if (file_exists($dashboard_css_path)) {
                    wp_enqueue_style('maneli-visitor-statistics', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/visitor-statistics.css', [], filemtime($dashboard_css_path));
                }
            }
            
            // Sales dashboard scripts (charts) - only for home page
            if ($page === 'home' || empty($page)) {
                wp_enqueue_script('maneli-apexcharts', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/apexcharts/apexcharts.min.js', ['jquery'], '1.0.0', true);
                wp_enqueue_script('maneli-sales-dashboard', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/sales-dashboard.js', ['maneli-apexcharts'], '1.0.0', true);
            }
            
            // Product Editor Script (for product-editor, products, and add-product pages)
            if ($page === 'product-editor' || $page === 'products' || $page === 'add-product') {
                // Use local SweetAlert2 instead of CDN
                $sweetalert2_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/libs/sweetalert2/sweetalert2.min.js';
                if (file_exists($sweetalert2_path)) {
                    wp_enqueue_style('sweetalert2', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/sweetalert2/sweetalert2.min.css', [], '11.0.0');
                    wp_enqueue_script('sweetalert2', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/sweetalert2/sweetalert2.min.js', ['jquery'], '11.0.0', true);
                } else {
                    // Fallback to CDN if local file doesn't exist
                wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', ['jquery'], null, true);
                }
                
                // Check if Quill and FilePond are needed (only for add-product page)
                if ($page === 'add-product') {
                    // Use local Quill instead of CDN
                    $quill_js_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/libs/quill/quill.js';
                    $quill_css_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/libs/quill/quill.snow.css';
                    if (file_exists($quill_js_path) && file_exists($quill_css_path)) {
                        wp_enqueue_style('quill', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/quill/quill.snow.css', [], '1.3.6');
                        wp_enqueue_script('quill', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/quill/quill.js', [], '1.3.6', true);
                    } else {
                        // Fallback to CDN
                    wp_enqueue_script('quill', 'https://cdn.quilljs.com/1.3.6/quill.js', [], '1.3.6', true);
                    wp_enqueue_style('quill', 'https://cdn.quilljs.com/1.3.6/quill.snow.css', [], '1.3.6');
                    }
                    
                    // Use local FilePond instead of CDN
                    $filepond_js_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/libs/filepond/filepond.min.js';
                    $filepond_css_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/libs/filepond/filepond.min.css';
                    if (file_exists($filepond_js_path) && file_exists($filepond_css_path)) {
                        wp_enqueue_style('filepond', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/filepond/filepond.min.css', [], '4.0.0');
                        wp_enqueue_script('filepond', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/filepond/filepond.min.js', [], '4.0.0', true);
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
                $nonce = wp_create_nonce('maneli_product_data_nonce');
                $inline_script = "
                    window.maneliAdminProductEditor = {
                        ajaxUrl: '" . esc_js($ajax_url) . "',
                        productsUrl: '" . esc_js($products_url) . "',
                        nonce: '" . esc_js($nonce) . "',
                        text: {
                            ajax_error: '" . esc_js(__('Server error. Please try again.', 'maneli-car-inquiry')) . "',
                            saved: '" . esc_js(__('Saved', 'maneli-car-inquiry')) . "',
                            error: '" . esc_js(__('Error', 'maneli-car-inquiry')) . "'
                        }
                    };
                ";
                wp_add_inline_script('jquery', $inline_script, 'before');
            }
            
            // CRITICAL FIX: ALWAYS load inquiry scripts - remove all conditions
            // Load scripts for ALL inquiry pages unconditionally
            $load_inquiry_scripts = false;
            
            // Check if we're on any inquiry-related page
            if ($page === 'cash-inquiries' || $page === 'installment-inquiries' || ($page === 'inquiries' && ($subpage === 'cash' || $subpage === 'installment'))) {
                $load_inquiry_scripts = true;
            }
            
            // Check URL parameters
            if (isset($_GET['inquiry_id']) || isset($_GET['cash_inquiry_id'])) {
                $load_inquiry_scripts = true;
            }
            
            // CRITICAL: Remove ALL conditions - ALWAYS load for dashboard pages
            // Force load regardless of any condition
            {
                // Enqueue dependencies first - Use local SweetAlert2
                $sweetalert2_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/libs/sweetalert2/sweetalert2.min.js';
                if (file_exists($sweetalert2_path)) {
                    wp_enqueue_style('sweetalert2', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/sweetalert2/sweetalert2.min.css', [], '11.0.0');
                    wp_enqueue_script('sweetalert2', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/sweetalert2/sweetalert2.min.js', ['jquery'], '11.0.0', true);
                } else {
                    // Fallback to CDN
                wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', ['jquery'], null, true);
                }
                // Note: Select2 is not available locally, keep CDN
                wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0');
                wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0', true);
                
                // Use persianDatepicker (same as in expert reports and inquiry forms)
                $datepicker_enqueued = false;
                // Check if persianDatepicker is already enqueued by dashboard handler
                if (!wp_script_is('maneli-persian-datepicker', 'enqueued')) {
                    wp_enqueue_script('maneli-persian-datepicker', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/persianDatepicker.min.js', ['jquery'], '1.0.0', true);
                    $datepicker_enqueued = true;
                } else {
                    $datepicker_enqueued = true; // Already enqueued
                }
                
                // Debug script first (loads before everything, in header)
                wp_enqueue_script('maneli-debug-buttons', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/frontend/debug-maneli-buttons.js', ['jquery'], '1.0.0', false);
                
                // Main script - only require essential dependencies (jquery, sweetalert2, select2)
                // Make datepicker optional - only add if it was successfully enqueued
                $inquiry_lists_deps = ['jquery', 'sweetalert2', 'select2'];
                if ($datepicker_enqueued) {
                    $inquiry_lists_deps[] = 'maneli-persian-datepicker';
                }
                
                wp_enqueue_script('maneli-inquiry-lists-js', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/frontend/inquiry-lists.js', $inquiry_lists_deps, '1.0.2', true);
                
                // Enqueue report-specific scripts - DISABLED: All handlers are now in inquiry-lists.js
                // These files create duplicate handlers and conflicts. Removed to fix expert note save button.
                /*
                if (isset($_GET['inquiry_id']) && !isset($_GET['cash_inquiry_id'])) {
                    // Installment report page
                    wp_enqueue_script('maneli-installment-report-js', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/frontend/installment-report.js', ['jquery', 'sweetalert2'], '1.0.0', true);
                } elseif (isset($_GET['cash_inquiry_id'])) {
                    // Cash report page
                    wp_enqueue_script('maneli-cash-report-js', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/frontend/cash-report.js', ['jquery', 'sweetalert2'], '1.0.0', true);
                }
                */
            }
            
            // Calendar Page Scripts (for calendar page)
            if ($page === 'calendar') {
                wp_enqueue_style('maneli-fullcalendar', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/fullcalendar/full-calendar.css', [], '6.1.10');
                // Load FullCalendar in header (false) so it's available when scripts run
                wp_enqueue_script('maneli-fullcalendar', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/fullcalendar/index.global.min.js', ['jquery'], '6.1.10', false);
                
                // Use local SweetAlert2 if available
                $sweetalert2_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/libs/sweetalert2/sweetalert2.min.js';
                if (file_exists($sweetalert2_path)) {
                    wp_enqueue_style('sweetalert2', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/sweetalert2/sweetalert2.min.css', [], '11.0.0');
                    wp_enqueue_script('sweetalert2', MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/sweetalert2/sweetalert2.min.js', ['jquery'], '11.0.0', false);
                } else {
                    wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', ['jquery'], null, false);
                }
            }
            
            // Inquiry Lists localization - ALWAYS localize
            {
                // Localize script with data for cash or installment
                $options = get_option('maneli_inquiry_all_options', []);
                $experts_query = get_users(['role' => 'maneli_expert', 'orderby' => 'display_name', 'order' => 'ASC']);
                $experts_list = [];
                foreach ($experts_query as $expert) {
                    $experts_list[] = ['id' => $expert->ID, 'name' => $expert->display_name ?: $expert->user_login];
                }
                
                $localize_data = [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'experts' => $experts_list,
                    'text' => [
                        'error' => esc_html__('Error', 'maneli-car-inquiry'),
                        'success' => esc_html__('Success', 'maneli-car-inquiry'),
                        'server_error' => esc_html__('Server error. Please try again.', 'maneli-car-inquiry'),
                        'unknown_error' => esc_html__('Unknown error', 'maneli-car-inquiry'),
                        'no_experts_available' => esc_html__('No experts are currently registered.', 'maneli-car-inquiry'),
                        'select_expert_required' => esc_html__('Please select an expert.', 'maneli-car-inquiry'),
                        'select_expert_placeholder' => esc_html__('Select Expert', 'maneli-car-inquiry'),
                        'auto_assign' => esc_html__('-- Automatic Assignment (Round-robin) --', 'maneli-car-inquiry'),
                        'assign_title' => esc_html__('Assign to Expert', 'maneli-car-inquiry'),
                        'assign_label' => esc_html__('Select Expert:', 'maneli-car-inquiry'),
                        'assign_button' => esc_html__('Assign', 'maneli-car-inquiry'),
                        'cancel_button' => esc_html__('Cancel', 'maneli-car-inquiry'),
                        'confirm_button' => esc_html__('Confirm', 'maneli-car-inquiry'),
                        'delete_title' => esc_html__('Deleted', 'maneli-car-inquiry'),
                        'delete_list_title' => esc_html__('Are you sure you want to delete this inquiry?', 'maneli-car-inquiry'),
                        'delete_list_text' => esc_html__('This action cannot be undone.', 'maneli-car-inquiry'),
                        'confirm_delete_title' => esc_html__('Are you sure?', 'maneli-car-inquiry'),
                        'confirm_delete_text' => esc_html__('Do you want to delete this inquiry? This action cannot be undone.', 'maneli-car-inquiry'),
                        'edit_title' => esc_html__('Edit Inquiry', 'maneli-car-inquiry'),
                        'placeholder_name' => esc_html__('Name', 'maneli-car-inquiry'),
                        'placeholder_last_name' => esc_html__('Last Name', 'maneli-car-inquiry'),
                        'placeholder_mobile' => esc_html__('Mobile Number', 'maneli-car-inquiry'),
                        'reject_title' => esc_html__('Reject Inquiry', 'maneli-car-inquiry'),
                        'reject_label' => esc_html__('Rejection Reason:', 'maneli-car-inquiry'),
                        'reject_placeholder_custom' => esc_html__('Enter your reason...', 'maneli-car-inquiry'),
                        'reject_option_default' => esc_html__('-- Select Reason --', 'maneli-car-inquiry'),
                        'reject_option_custom' => esc_html__('-- Custom Reason --', 'maneli-car-inquiry'),
                        'reject_submit_button' => esc_html__('Reject Inquiry', 'maneli-car-inquiry'),
                        'rejection_reason_required' => esc_html__('Please select or enter a rejection reason.', 'maneli-car-inquiry'),
                        'request_docs_title' => esc_html__('Request More Documents', 'maneli-car-inquiry'),
                        'request_docs_message' => esc_html__('Request will be sent to customer to upload additional documents.', 'maneli-car-inquiry'),
                        'request_docs_confirm' => esc_html__('Send Request', 'maneli-car-inquiry'),
                        'select_documents_label' => esc_html__('Select Required Documents:', 'maneli-car-inquiry'),
                        // Cash inquiry specific text
                        'start_progress_title' => esc_html__('Start Progress', 'maneli-car-inquiry'),
                        'start_progress_confirm' => esc_html__('Are you sure you want to start follow-up for this inquiry?', 'maneli-car-inquiry'),
                        'approve_title' => esc_html__('Approve Inquiry', 'maneli-car-inquiry'),
                        'approve_confirm' => esc_html__('Are you sure you want to approve this inquiry?', 'maneli-car-inquiry'),
                        'approve_button' => esc_html__('Approve', 'maneli-car-inquiry'),
                        'schedule_meeting_title' => esc_html__('Schedule Meeting', 'maneli-car-inquiry'),
                        'meeting_date_label' => esc_html__('Meeting Date', 'maneli-car-inquiry'),
                        'meeting_time_label' => esc_html__('Meeting Time', 'maneli-car-inquiry'),
                        'select_date' => esc_html__('Select Date', 'maneli-car-inquiry'),
                        'meeting_required' => esc_html__('Please enter meeting date and time', 'maneli-car-inquiry'),
                        'schedule_button' => esc_html__('Schedule', 'maneli-car-inquiry'),
                        'schedule_followup_title' => esc_html__('Schedule Follow-up', 'maneli-car-inquiry'),
                        'followup_date_label' => esc_html__('Follow-up Date', 'maneli-car-inquiry'),
                        'note_label_optional' => esc_html__('Note (Optional)', 'maneli-car-inquiry'),
                        'enter_note' => esc_html__('Enter your note...', 'maneli-car-inquiry'),
                        'schedule_followup_button' => esc_html__('Schedule Follow-up', 'maneli-car-inquiry'),
                        'set_downpayment_title' => esc_html__('Set Down Payment Amount', 'maneli-car-inquiry'),
                        'downpayment_amount_label' => esc_html__('Down Payment Amount (Toman):', 'maneli-car-inquiry'),
                        'downpayment_amount_required' => esc_html__('Please enter down payment amount', 'maneli-car-inquiry'),
                        'request_downpayment_title' => esc_html__('Request Down Payment?', 'maneli-car-inquiry'),
                        'amount' => esc_html__('Amount', 'maneli-car-inquiry'),
                        'toman' => esc_html__('Toman', 'maneli-car-inquiry'),
                        'send_button' => esc_html__('Send', 'maneli-car-inquiry'),
                        'ok_button' => esc_html__('OK', 'maneli-car-inquiry'),
                        'status_updated' => esc_html__('Status updated successfully', 'maneli-car-inquiry'),
                        'status_update_error' => esc_html__('Error updating status', 'maneli-car-inquiry'),
                        // Expert note specific text
                        'attention' => esc_html__('Attention!', 'maneli-car-inquiry'),
                        'please_enter_note' => esc_html__('Please enter your note.', 'maneli-car-inquiry'),
                        'note_saved_success' => esc_html__('Note saved successfully.', 'maneli-car-inquiry'),
                        'error_occurred' => esc_html__('An error occurred.', 'maneli-car-inquiry'),
                        // Status action text
                        'complete_title' => esc_html__('Complete Inquiry', 'maneli-car-inquiry'),
                        'complete_confirm' => esc_html__('Are you sure you want to complete this inquiry?', 'maneli-car-inquiry'),
                        'followup_date_required' => esc_html__('Please enter follow-up date', 'maneli-car-inquiry'),
                        'cancel_inquiry_title' => esc_html__('Cancel Inquiry', 'maneli-car-inquiry'),
                        'rejection_reason_label' => esc_html__('Rejection Reason', 'maneli-car-inquiry'),
                        'cancel_reason_label' => esc_html__('Cancellation Reason', 'maneli-car-inquiry'),
                        'enter_reason' => esc_html__('Please enter reason...', 'maneli-car-inquiry'),
                        'reason_required' => esc_html__('Please enter reason with at least 10 characters', 'maneli-car-inquiry'),
                    ]
                ];
                
                // Check for cash inquiries (both old and new routes)
                if ($page === 'cash-inquiries' || ($page === 'inquiries' && $subpage === 'cash')) {
                    $localize_data['nonces'] = [
                        'cash_filter' => wp_create_nonce('maneli_cash_inquiry_filter_nonce'),
                        'cash_details' => wp_create_nonce('maneli_cash_inquiry_details_nonce'),
                        'cash_update' => wp_create_nonce('maneli_cash_inquiry_update_nonce'),
                        'cash_delete' => wp_create_nonce('maneli_cash_inquiry_delete_nonce'),
                        'cash_set_downpayment' => wp_create_nonce('maneli_cash_set_downpayment_nonce'),
                        'cash_assign_expert' => wp_create_nonce('maneli_cash_inquiry_assign_expert_nonce'),
                        'save_expert_note' => wp_create_nonce('maneli_save_expert_note'),
                        'update_cash_status' => wp_create_nonce('maneli_update_cash_status'),
                    ];
                    $cash_rejection_reasons = array_filter(array_map('trim', explode("\n", $options['cash_inquiry_rejection_reasons'] ?? '')));
                    $localize_data['cash_rejection_reasons'] = $cash_rejection_reasons;
                } else {
                    $localize_data['nonces'] = [
                        'inquiry_filter' => wp_create_nonce('maneli_inquiry_filter_nonce'),
                        'details' => wp_create_nonce('maneli_inquiry_details_nonce'),
                        'inquiry_delete' => wp_create_nonce('maneli_inquiry_delete_nonce'),
                        'assign_expert' => wp_create_nonce('maneli_inquiry_assign_expert_nonce'),
                        'tracking_status' => wp_create_nonce('maneli_tracking_status_nonce'),
                        'save_installment_note' => wp_create_nonce('maneli_installment_note'),
                        'update_installment_status' => wp_create_nonce('maneli_installment_status'),
                    ];
                    $installment_rejection_reasons = array_filter(array_map('trim', explode("\n", $options['installment_inquiry_rejection_reasons'] ?? '')));
                    $localize_data['installment_rejection_reasons'] = $installment_rejection_reasons;
                    
                    // Add required documents list
                    $required_docs_raw = $options['customer_required_documents'] ?? '';
                    $required_docs = array_filter(array_map('trim', explode("\n", $required_docs_raw)));
                    $localize_data['required_documents'] = $required_docs;
                    $localize_data['text']['request_docs_title'] = esc_html__('Request More Documents', 'maneli-car-inquiry');
                    $localize_data['text']['request_docs_message'] = esc_html__('Request will be sent to customer to upload additional documents.', 'maneli-car-inquiry');
                    $localize_data['text']['request_docs_confirm'] = esc_html__('Send Request', 'maneli-car-inquiry');
                    $localize_data['text']['select_documents_label'] = esc_html__('Select Required Documents:', 'maneli-car-inquiry');
                }
                
                // Ensure maneliInquiryLists is available immediately
                // Add inline script BEFORE the main script to ensure it's available when needed
                if ($load_inquiry_scripts || true) {
                    $cash_nonce = wp_create_nonce("maneli_cash_inquiry_filter_nonce");
                    $installment_nonce = wp_create_nonce("maneli_inquiry_filter_nonce");
                    $fallback_script = "
                    window.maneliInquiryLists = window.maneliInquiryLists || {
                        ajax_url: '" . esc_js(admin_url("admin-ajax.php")) . "',
                        nonces: {
                            cash_filter: '" . esc_js($cash_nonce) . "',
                            cash_details: '" . esc_js(wp_create_nonce("maneli_cash_inquiry_details_nonce")) . "',
                            cash_update: '" . esc_js(wp_create_nonce("maneli_cash_inquiry_update_nonce")) . "',
                            cash_delete: '" . esc_js(wp_create_nonce("maneli_cash_inquiry_delete_nonce")) . "',
                            cash_assign_expert: '" . esc_js(wp_create_nonce("maneli_cash_inquiry_assign_expert_nonce")) . "',
                            inquiry_filter: '" . esc_js($installment_nonce) . "',
                            details: '" . esc_js(wp_create_nonce("maneli_inquiry_details_nonce")) . "',
                            inquiry_delete: '" . esc_js(wp_create_nonce("maneli_inquiry_delete_nonce")) . "',
                            assign_expert: '" . esc_js(wp_create_nonce("maneli_inquiry_assign_expert_nonce")) . "',
                            tracking_status: '" . esc_js(wp_create_nonce("maneli_tracking_status_nonce")) . "',
                            save_installment_note: '" . esc_js(wp_create_nonce("maneli_installment_note")) . "',
                            update_installment_status: '" . esc_js(wp_create_nonce("maneli_installment_status")) . "',
                            save_expert_note: '" . esc_js(wp_create_nonce("maneli_save_expert_note")) . "',
                            update_cash_status: '" . esc_js(wp_create_nonce("maneli_update_cash_status")) . "'
                        },
                        experts: [],
                        text: {
                            error: '" . esc_js(__('Error', 'maneli-car-inquiry')) . "',
                            success: '" . esc_js(__('Success', 'maneli-car-inquiry')) . "',
                            server_error: '" . esc_js(__('Server error. Please try again.', 'maneli-car-inquiry')) . "',
                            unknown_error: '" . esc_js(__('Unknown error', 'maneli-car-inquiry')) . "',
                            attention: '" . esc_js(__('Attention!', 'maneli-car-inquiry')) . "',
                            please_enter_note: '" . esc_js(__('Please enter your note.', 'maneli-car-inquiry')) . "',
                            note_saved_success: '" . esc_js(__('Note saved successfully.', 'maneli-car-inquiry')) . "',
                            error_occurred: '" . esc_js(__('An error occurred.', 'maneli-car-inquiry')) . "',
                            ok_button: '" . esc_js(__('OK', 'maneli-car-inquiry')) . "',
                            complete_title: '" . esc_js(__('Complete Inquiry', 'maneli-car-inquiry')) . "',
                            complete_confirm: '" . esc_js(__('Are you sure you want to complete this inquiry?', 'maneli-car-inquiry')) . "',
                            followup_date_required: '" . esc_js(__('Please enter follow-up date', 'maneli-car-inquiry')) . "',
                            cancel_inquiry_title: '" . esc_js(__('Cancel Inquiry', 'maneli-car-inquiry')) . "',
                            rejection_reason_label: '" . esc_js(__('Rejection Reason', 'maneli-car-inquiry')) . "',
                            cancel_reason_label: '" . esc_js(__('Cancellation Reason', 'maneli-car-inquiry')) . "',
                            enter_reason: '" . esc_js(__('Please enter reason...', 'maneli-car-inquiry')) . "',
                            reason_required: '" . esc_js(__('Please enter reason with at least 10 characters', 'maneli-car-inquiry')) . "'
                        }
                    };
                    console.log('maneliInquiryLists fallback initialized for " . esc_js($page) . "', window.maneliInquiryLists);
                    ";
                    wp_add_inline_script('maneli-inquiry-lists-js', $fallback_script, 'before');
                }
                
                // Now localize - this will override/merge with fallback if already set
                wp_localize_script('maneli-inquiry-lists-js', 'maneliInquiryLists', $localize_data);
                
                // EMERGENCY FIX: Add inline script DIRECTLY in template output
                // Since wp_footer might not be called, output directly after scripts
                // Store for output in template
                $GLOBALS['maneli_emergency_script'] = true;
                
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
                                    alert('" . esc_js(__('Error: maneliInquiryLists is not defined!', 'maneli-car-inquiry')) . "');
                                    console.error('maneliInquiryLists is undefined!');
                                    return;
                                }
                                
                                if (typeof Swal === 'undefined') {
                                    alert('" . esc_js(__('Error: SweetAlert2 is not loaded!', 'maneli-car-inquiry')) . "');
                                    console.error('Swal is undefined!');
                                    return;
                                }
                                
                                // Show working alert
                                Swal.fire({
                                    title: '" . esc_js(__('Button Works!', 'maneli-car-inquiry')) . "',
                                    text: 'Inquiry ID: ' + inquiryId + ', Type: ' + inquiryType,
                                    icon: 'success'
                                });
                            });
                            
                            // Direct handler for delete button
                            $(document).off('click', '.delete-inquiry-report-btn.emergency').on('click', '.delete-inquiry-report-btn', function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                console.log('🔘 Delete button clicked!');
                                var btn = $(this);
                                var inquiryId = btn.data('inquiry-id');
                                var inquiryType = btn.data('inquiry-type');
                                console.log('Delete Inquiry ID:', inquiryId, 'Type:', inquiryType);
                                
                                if (typeof Swal !== 'undefined') {
                                    Swal.fire({
                                        title: '" . esc_js(__('Delete Button Works!', 'maneli-car-inquiry')) . "',
                                        text: 'Inquiry ID: ' + inquiryId,
                                        icon: 'success'
                                    });
                                } else {
                                    alert('" . esc_js(__('Delete button clicked! Inquiry ID:', 'maneli-car-inquiry')) . " ' + inquiryId);
                                }
                            });
                            
                            console.log('✅ Emergency handlers attached');
                            console.log('Button count - .assign-expert-btn:', $('.assign-expert-btn').length);
                            console.log('Button count - .delete-inquiry-report-btn:', $('.delete-inquiry-report-btn').length);
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
                    wp_localize_script('maneli-installment-report-js', 'maneliInstallmentReport', [
                        'ajax_url' => admin_url('admin-ajax.php'),
                        'nonces' => [
                            'update_status' => wp_create_nonce('maneli_installment_status'),
                            'save_note' => wp_create_nonce('maneli_installment_note'),
                        ]
                    ]);
                } elseif (isset($_GET['cash_inquiry_id'])) {
                    // Cash report localization
                    wp_localize_script('maneli-cash-report-js', 'maneliCashReport', [
                        'ajax_url' => admin_url('admin-ajax.php'),
                        'nonces' => [
                            'update_status' => wp_create_nonce('maneli_update_cash_status'),
                            'save_note' => wp_create_nonce('maneli_save_expert_note'),
                            'save_meeting' => wp_create_nonce('maneli_save_meeting'),
                            'expert_decision' => wp_create_nonce('maneli_expert_decision_cash'),
                            'admin_approve' => wp_create_nonce('maneli_admin_approve_cash'),
                        ],
                        'text' => [
                            'select_date' => esc_html__('Select Date', 'maneli-car-inquiry'),
                            'error' => esc_html__('Error', 'maneli-car-inquiry'),
                            'success' => esc_html__('Success', 'maneli-car-inquiry'),
                            'server_error' => esc_html__('Server error. Please try again.', 'maneli-car-inquiry'),
                        ]
                    ]);
                }
                */
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
        
        // CRITICAL: Handle login page FIRST before any redirects
        // This prevents redirect loops when accessing /login
        // ALL authentication steps (OTP, password creation) are now handled in the unified login page
        if ($page === 'login') {
            // If already logged in, check for redirect URL
            if ($this->is_user_logged_in()) {
                $this->maybe_start_session();
                $redirect_url = isset($_SESSION['maneli_redirect_after_login']) ? $_SESSION['maneli_redirect_after_login'] : home_url('/dashboard');
                unset($_SESSION['maneli_redirect_after_login']);
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
        
        // Check old format: $_SESSION['maneli_user_id'] (backward compatibility)
        if (isset($_SESSION['maneli_user_id']) && !empty($_SESSION['maneli_user_id'])) {
            $user_id = (int)$_SESSION['maneli_user_id'];
            if ($user_id > 0 && get_user_by('ID', $user_id)) {
                return true;
            }
        }
        
        // Check old format: $_SESSION['maneli_dashboard_logged_in'] (backward compatibility)
        if (isset($_SESSION['maneli_dashboard_logged_in']) && $_SESSION['maneli_dashboard_logged_in'] === true) {
            $user_id = isset($_SESSION['maneli_user_id']) ? (int)$_SESSION['maneli_user_id'] : 0;
            if ($user_id > 0 && get_user_by('ID', $user_id)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Start session if not already started
     * CRITICAL: Configure session cookie parameters for proper persistence
     */
    private function maybe_start_session() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            // Configure session cookie parameters for security and persistence
            // SameSite=Lax allows session to work across redirects
            session_set_cookie_params([
                'lifetime' => 0, // Session cookie (expires when browser closes)
                'path' => '/',
                'domain' => '',
                'secure' => is_ssl(), // Use secure cookies on HTTPS
                'httponly' => true, // Prevent JavaScript access
                'samesite' => 'Lax' // Allow cookies in redirects
            ]);
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
            $_SESSION['maneli_redirect_after_login'] = esc_url_raw($_GET['redirect_to']);
        }
        
        // Get any error/success messages from session
        $error_message = isset($_SESSION['maneli_error']) ? $_SESSION['maneli_error'] : '';
        $success_message = isset($_SESSION['maneli_success']) ? $_SESSION['maneli_success'] : '';
        
        // Clear messages after displaying (will be shown once)
        unset($_SESSION['maneli_error']);
        unset($_SESSION['maneli_success']);
        
        // Include the unified login template
        include MANELI_INQUIRY_PLUGIN_PATH . 'templates/login.php';
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
        
        include MANELI_INQUIRY_PLUGIN_PATH . 'templates/dashboard.php';
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
            // Note: maneli_admin is the actual role name, but we use 'maneli_manager' as internal identifier
            $user_role = 'customer';
            if (in_array('administrator', $wp_user->roles, true)) {
                $user_role = 'administrator';
            } elseif (in_array('maneli_admin', $wp_user->roles, true) || in_array('maneli_manager', $wp_user->roles, true)) {
                $user_role = 'maneli_manager';
            } elseif (in_array('maneli_expert', $wp_user->roles, true)) {
                $user_role = 'maneli_expert';
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
        } elseif (isset($_SESSION['maneli_user_id']) && !empty($_SESSION['maneli_user_id'])) {
            // Old session format for backward compatibility
            $user_id = (int)$_SESSION['maneli_user_id'];
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
        // Note: maneli_admin is the actual role name, but we use 'maneli_manager' as internal identifier
        $user_role = 'customer';
        if (in_array('administrator', $wp_user->roles, true)) {
            $user_role = 'administrator';
        } elseif (in_array('maneli_admin', $wp_user->roles, true) || in_array('maneli_manager', $wp_user->roles, true)) {
            $user_role = 'maneli_manager';
        } elseif (in_array('maneli_expert', $wp_user->roles, true)) {
            $user_role = 'maneli_expert';
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
        $user_name = esc_html__('User', 'maneli-car-inquiry');
        $user_phone = '';
        
        // Check if WordPress user is logged in
        if (is_user_logged_in()) {
            $wp_user = wp_get_current_user();
            $user_id = $wp_user->ID;
            
            // Determine role
            if (in_array('administrator', $wp_user->roles)) {
                $user_role = 'administrator';
            } elseif (in_array('maneli_admin', $wp_user->roles) || in_array('maneli_manager', $wp_user->roles)) {
                $user_role = 'maneli_manager';
            } elseif (in_array('maneli_expert', $wp_user->roles)) {
                $user_role = 'maneli_expert';
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
            } elseif (isset($_SESSION['maneli_user_id']) && !empty($_SESSION['maneli_user_id'])) {
                // Old session format for backward compatibility
                $user_id = (int)$_SESSION['maneli_user_id'];
            }
            
            if ($user_id) {
                // Get user from database
                $wp_user = get_user_by('ID', $user_id);
                
                if ($wp_user) {
                    // Determine role from database
                    if (in_array('administrator', $wp_user->roles)) {
                        $user_role = 'administrator';
                    } elseif (in_array('maneli_admin', $wp_user->roles) || in_array('maneli_manager', $wp_user->roles)) {
                        $user_role = 'maneli_manager';
                    } elseif (in_array('maneli_expert', $wp_user->roles)) {
                        $user_role = 'maneli_expert';
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
            $user_name = $_SESSION['maneli_user_name'] ?? esc_html__('User', 'maneli-car-inquiry');
            $user_phone = $_SESSION['maneli_user_phone'] ?? '';
            $user_role = $_SESSION['maneli_user_role'] ?? 'customer';
                }
            } else {
                // Use old session format
                $user_name = $_SESSION['maneli_user_name'] ?? esc_html__('User', 'maneli-car-inquiry');
                $user_phone = $_SESSION['maneli_user_phone'] ?? '';
                $user_role = $_SESSION['maneli_user_role'] ?? 'customer';
            }
        }
        
        // Get role display name
        $role_translations = [
            'administrator' => esc_html__('General Manager', 'maneli-car-inquiry'),
            'maneli_manager' => esc_html__('Maneli Manager', 'maneli-car-inquiry'),
            'maneli_expert' => esc_html__('Maneli Expert', 'maneli-car-inquiry'),
            'customer' => esc_html__('Customer', 'maneli-car-inquiry'),
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
            'administrator' => ['manage_maneli_inquiries', 'edit_products', 'edit_product', 'edit_posts', 'delete_users'],
            'maneli_manager' => ['manage_maneli_inquiries', 'edit_products', 'edit_product', 'edit_posts', 'delete_users'],
            'maneli_expert' => ['edit_products', 'edit_posts'],
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
        // Note: maneli_admin is the actual role name, but we use 'maneli_manager' as internal identifier
        $user_role = 'customer';
        if (in_array('administrator', $wp_user->roles, true)) {
                $user_role = 'administrator';
        } elseif (in_array('maneli_admin', $wp_user->roles, true) || in_array('maneli_manager', $wp_user->roles, true)) {
                $user_role = 'maneli_manager';
        } elseif (in_array('maneli_expert', $wp_user->roles, true)) {
                $user_role = 'maneli_expert';
            } else {
                $user_role = 'customer';
        }
        
        $menu_items = [];
        
        // ===== مشتری (Customer) =====
        if ($user_role === 'customer') {
            // گروه اصلی
            $menu_items[] = [
                'title' => esc_html__('Home', 'maneli-car-inquiry'),
                'url' => home_url('/dashboard'),
                'icon' => 'ri-home-line'
            ];
            $menu_items[] = [
                'title' => esc_html__('Notifications', 'maneli-car-inquiry'),
                'url' => home_url('/dashboard/notifications'),
                'icon' => 'ri-notification-line'
            ];
            
            // دسته: استعلامات
            $menu_items[] = ['title' => esc_html__('Inquiries', 'maneli-car-inquiry'), 'category' => true];
            $menu_items[] = [
                'title' => esc_html__('My Inquiries', 'maneli-car-inquiry'),
                'icon' => 'ri-file-list-line',
                'children' => [
                    [
                        'title' => esc_html__('My Installment Inquiries', 'maneli-car-inquiry'),
                        'url' => home_url('/dashboard/inquiries/installment'),
                        'icon' => 'ri-bank-line'
                    ],
                    [
                        'title' => esc_html__('My Cash Inquiries', 'maneli-car-inquiry'),
                        'url' => home_url('/dashboard/inquiries/cash'),
                        'icon' => 'ri-money-dollar-circle-line'
                    ]
                ]
            ];
            
            // دسته: ثبت استعلام
            $menu_items[] = ['title' => esc_html__('Create Inquiry', 'maneli-car-inquiry'), 'category' => true];
            $menu_items[] = [
                'title' => esc_html__('Create Installment Inquiry', 'maneli-car-inquiry'),
                'url' => home_url('/dashboard/new-inquiry'),
                'icon' => 'ri-add-circle-line'
            ];
            $menu_items[] = [
                'title' => esc_html__('Create Cash Inquiry', 'maneli-car-inquiry'),
                'url' => home_url('/dashboard/new-cash-inquiry'),
                'icon' => 'ri-add-box-line'
            ];
            
            // گروه تنظیمات
            $menu_items[] = [
                'title' => esc_html__('Settings', 'maneli-car-inquiry'),
                'url' => home_url('/dashboard/profile-settings'),
                'icon' => 'ri-settings-3-line'
            ];
            $menu_items[] = [
                'title' => esc_html__('Logout', 'maneli-car-inquiry'),
                'url' => home_url('/logout'),
                'icon' => 'ri-logout-box-r-line'
            ];
        }
        
        // ===== کارشناس (Maneli Expert) =====
        elseif ($user_role === 'maneli_expert') {
            // دسته: داشبورد
            $menu_items[] = ['title' => esc_html__('Dashboard', 'maneli-car-inquiry'), 'category' => true];
            $menu_items[] = [
                'title' => esc_html__('Home', 'maneli-car-inquiry'),
                'url' => home_url('/dashboard'),
                'icon' => 'ri-home-line'
            ];
            $menu_items[] = [
                'title' => esc_html__('Notifications', 'maneli-car-inquiry'),
                'url' => home_url('/dashboard/notifications'),
                'icon' => 'ri-notification-line'
            ];
            $menu_items[] = [
                'title' => esc_html__('Meeting Calendar', 'maneli-car-inquiry'),
                'url' => home_url('/dashboard/calendar'),
                'icon' => 'ri-calendar-todo-line',
                'capability' => 'edit_posts'
            ];
            
            // دسته: استعلامات
            $menu_items[] = ['title' => esc_html__('Inquiries', 'maneli-car-inquiry'), 'category' => true];
            
            // گروه استعلامات نقدی
            $menu_items[] = [
                'title' => esc_html__('Cash Inquiries', 'maneli-car-inquiry'),
                'icon' => 'ri-money-dollar-circle-line',
                'capability' => 'edit_posts',
                'children' => [
                    [
                        'title' => esc_html__('Cash Inquiries List', 'maneli-car-inquiry'),
                        'url' => home_url('/dashboard/inquiries/cash'),
                        'icon' => 'ri-file-list-3-line',
                        'capability' => 'edit_posts'
                    ],
                    [
                        'title' => esc_html__('Cash Follow-ups', 'maneli-car-inquiry'),
                        'url' => home_url('/dashboard/cash-followups'),
                        'icon' => 'ri-customer-service-2-line',
                        'capability' => 'edit_posts'
                    ]
                ]
            ];
            
            // گروه استعلامات اقساطی
            $menu_items[] = [
                'title' => esc_html__('Installment Inquiries', 'maneli-car-inquiry'),
                'icon' => 'ri-bank-line',
                'capability' => 'edit_posts',
                'children' => [
                    [
                        'title' => esc_html__('Installment Inquiries List', 'maneli-car-inquiry'),
                        'url' => home_url('/dashboard/inquiries/installment'),
                        'icon' => 'ri-file-list-2-line',
                        'capability' => 'edit_posts'
                    ],
                    [
                        'title' => esc_html__('Installment Follow-ups', 'maneli-car-inquiry'),
                        'url' => home_url('/dashboard/installment-followups'),
                        'icon' => 'ri-customer-service-2-line',
                        'capability' => 'edit_posts'
                    ]
                ]
            ];
            
            // دسته: گزارشات
            $menu_items[] = ['title' => esc_html__('Reports', 'maneli-car-inquiry'), 'category' => true];
            $menu_items[] = [
                'title' => esc_html__('Performance Reports', 'maneli-car-inquiry'),
                'url' => home_url('/dashboard/reports'),
                'icon' => 'ri-bar-chart-box-line',
                'capability' => 'edit_posts'
            ];
            
            // دسته: تنظیمات - حذف شده برای کارشناس
            // کارشناسان به تنظیمات دسترسی ندارند
            $menu_items[] = [
                'title' => esc_html__('Logout', 'maneli-car-inquiry'),
                'url' => home_url('/logout'),
                'icon' => 'ri-logout-box-r-line'
            ];
        }
        
        // ===== مدیر پلاگین (Maneli Manager) و مدیر کل (Administrator) =====
        // Both roles get the exact same menu and permissions (except wp-admin access)
        if ($user_role === 'maneli_manager' || $user_role === 'administrator') {
            // دسته: داشبورد
            $menu_items[] = ['title' => esc_html__('Dashboard', 'maneli-car-inquiry'), 'category' => true];
            $menu_items[] = [
                'title' => esc_html__('Home', 'maneli-car-inquiry'),
                'url' => home_url('/dashboard'),
                'icon' => 'ri-home-line'
            ];
            $menu_items[] = [
                'title' => esc_html__('Notifications', 'maneli-car-inquiry'),
                'url' => home_url('/dashboard/notifications'),
                'icon' => 'ri-notification-line'
            ];
            $menu_items[] = [
                'title' => esc_html__('Meeting Calendar', 'maneli-car-inquiry'),
                'url' => home_url('/dashboard/calendar'),
                'icon' => 'ri-calendar-todo-line',
                'capability' => 'edit_posts'
            ];
            
            // دسته: استعلامات
            $menu_items[] = ['title' => esc_html__('Inquiries', 'maneli-car-inquiry'), 'category' => true];
            
            // گروه استعلامات نقدی
            $menu_items[] = [
                'title' => esc_html__('Cash Inquiries', 'maneli-car-inquiry'),
                'icon' => 'ri-money-dollar-circle-line',
                'capability' => 'edit_posts',
                'children' => [
                    [
                        'title' => esc_html__('Cash Inquiries List', 'maneli-car-inquiry'),
                        'url' => home_url('/dashboard/inquiries/cash'),
                        'icon' => 'ri-file-list-3-line',
                        'capability' => 'edit_posts'
                    ],
                    [
                        'title' => esc_html__('Cash Follow-ups', 'maneli-car-inquiry'),
                        'url' => home_url('/dashboard/cash-followups'),
                        'icon' => 'ri-customer-service-2-line',
                        'capability' => 'edit_posts'
                    ]
                ]
            ];
            
            // گروه استعلامات اقساطی
            $menu_items[] = [
                'title' => esc_html__('Installment Inquiries', 'maneli-car-inquiry'),
                'icon' => 'ri-bank-line',
                'capability' => 'edit_posts',
                'children' => [
                    [
                        'title' => esc_html__('Installment Inquiries List', 'maneli-car-inquiry'),
                        'url' => home_url('/dashboard/inquiries/installment'),
                        'icon' => 'ri-file-list-2-line',
                        'capability' => 'edit_posts'
                    ],
                    [
                        'title' => esc_html__('Installment Follow-ups', 'maneli-car-inquiry'),
                        'url' => home_url('/dashboard/installment-followups'),
                        'icon' => 'ri-customer-service-2-line',
                        'capability' => 'edit_posts'
                    ]
                ]
            ];
            
            // دسته: مدیریت
            $menu_items[] = ['title' => esc_html__('Management', 'maneli-car-inquiry'), 'category' => true];
            $menu_items[] = [
                'title' => esc_html__('Edit Products', 'maneli-car-inquiry'),
                'url' => home_url('/dashboard/products'),
                'icon' => 'ri-store-line',
                'capability' => 'manage_maneli_inquiries'
            ];
            $menu_items[] = [
                'title' => esc_html__('Payment Management', 'maneli-car-inquiry'),
                'url' => home_url('/dashboard/payments'),
                'icon' => 'ri-bank-card-line',
                'capability' => 'manage_maneli_inquiries'
            ];
            
            // دسته: کاربران
            $menu_items[] = ['title' => esc_html__('Users', 'maneli-car-inquiry'), 'category' => true];
            $menu_items[] = [
                'title' => esc_html__('User Management', 'maneli-car-inquiry'),
                'url' => home_url('/dashboard/users'),
                'icon' => 'ri-team-line',
                'capability' => 'delete_users'
            ];
            $menu_items[] = [
                'title' => esc_html__('Experts', 'maneli-car-inquiry'),
                'url' => home_url('/dashboard/experts'),
                'icon' => 'ri-user-star-line',
                'capability' => 'manage_maneli_inquiries'
            ];
            
            // دسته: گزارشات
            $menu_items[] = ['title' => esc_html__('Reports', 'maneli-car-inquiry'), 'category' => true];
            $menu_items[] = [
                'title' => esc_html__('Performance Reports', 'maneli-car-inquiry'),
                'url' => home_url('/dashboard/reports'),
                'icon' => 'ri-bar-chart-box-line',
                'capability' => 'manage_maneli_inquiries'
            ];
            $menu_items[] = [
                'title' => esc_html__('Visitor Statistics', 'maneli-car-inquiry'),
                'url' => home_url('/dashboard/visitor-statistics'),
                'icon' => 'ri-line-chart-line',
                'capability' => 'manage_maneli_inquiries'
            ];
            $menu_items[] = [
                'title' => esc_html__('Notification Center', 'maneli-car-inquiry'),
                'url' => home_url('/dashboard/notifications-center'),
                'icon' => 'ri-notification-3-line',
                'capability' => 'manage_maneli_inquiries',
                'children' => [
                    [
                        'title' => esc_html__('SMS Notifications', 'maneli-car-inquiry'),
                        'url' => home_url('/dashboard/notifications/sms'),
                        'icon' => 'ri-message-2-line',
                        'capability' => 'manage_maneli_inquiries'
                    ],
                    [
                        'title' => esc_html__('Email Notifications', 'maneli-car-inquiry'),
                        'url' => home_url('/dashboard/notifications/email'),
                        'icon' => 'ri-mail-line',
                        'capability' => 'manage_maneli_inquiries'
                    ],
                    [
                        'title' => esc_html__('Telegram Notifications', 'maneli-car-inquiry'),
                        'url' => home_url('/dashboard/notifications/telegram'),
                        'icon' => 'ri-telegram-line',
                        'capability' => 'manage_maneli_inquiries'
                    ],
                    [
                        'title' => esc_html__('In-App Notifications', 'maneli-car-inquiry'),
                        'url' => home_url('/dashboard/notifications/app'),
                        'icon' => 'ri-notification-line',
                        'capability' => 'manage_maneli_inquiries'
                    ]
                ]
            ];
            
            // دسته: تنظیمات
            $menu_items[] = ['title' => esc_html__('Settings', 'maneli-car-inquiry'), 'category' => true];
            $menu_items[] = [
                'title' => esc_html__('System Settings', 'maneli-car-inquiry'),
                'url' => home_url('/dashboard/settings'),
                'icon' => 'ri-settings-3-line',
                'capability' => 'manage_maneli_inquiries'
            ];
            $menu_items[] = [
                'title' => esc_html__('Logout', 'maneli-car-inquiry'),
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
        $transient_key = 'maneli_login_attempts_' . md5($identifier);
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
        $transient_key = 'maneli_login_attempts_' . md5($identifier);
        delete_transient($transient_key);
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
        
        // Get client IP for rate limiting
        $client_ip = $this->get_client_ip();
        $rate_limit_key = $phone . '_' . $client_ip;
        
        // Check rate limiting before processing
        if (!$this->check_rate_limit($rate_limit_key)) {
            wp_send_json_error(['message' => esc_html__('Too many login attempts. Please try again in 5 minutes.', 'maneli-car-inquiry')]);
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
            $this->set_user_session($phone, esc_html__('User', 'maneli-car-inquiry'));
            // Clear rate limit on successful login
            if ($rate_limit_key) {
                $this->clear_rate_limit($rate_limit_key);
            }
            wp_send_json_success(['redirect' => home_url('/dashboard')]);
        } else {
            wp_send_json_error(['message' => esc_html__('Invalid verification code.', 'maneli-car-inquiry')]);
        }
    }
    
    /**
     * Handle password login (old method for backward compatibility)
     */
    private function handle_old_password_login($phone, $password, $rate_limit_key = null) {
        // Verify password (implement your logic here)
        $verification_result = $this->verify_password($phone, $password);
        
        if ($verification_result === true) {
            $this->set_user_session($phone, esc_html__('User', 'maneli-car-inquiry'));
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
        check_ajax_referer('maneli_dashboard_nonce', 'nonce');
        
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        
        // Validate phone number
        if (!preg_match('/^09\d{9}$/', $phone)) {
            wp_send_json_error(['message' => esc_html__('Invalid mobile number.', 'maneli-car-inquiry')]);
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
                wp_send_json_error(['message' => sprintf(esc_html__('Please wait %d seconds.', 'maneli-car-inquiry'), $wait_time)]);
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
                $message = sprintf(esc_html__('Maneli verification code: %s', 'maneli-car-inquiry'), $code);
                $result = $sms_handler->send_sms($phone, $message);
            }
        } else {
            // Fallback to regular SMS if pattern not configured
            if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Sending OTP via Regular SMS (Pattern not configured)');
            }
            $message = sprintf(esc_html__('Your verification code: %s', 'maneli-car-inquiry'), $code);
            $result = $sms_handler->send_sms($phone, $message);
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('OTP Send Result: ' . ($result ? 'SUCCESS' : 'FAILED'));
        }
        
        if ($result) {
            wp_send_json_success(['message' => esc_html__('Verification code sent.', 'maneli-car-inquiry')]);
        } else {
            // بررسی اینکه آیا تنظیمات SMS وجود دارد یا نه
            $sms_username = $options['sms_username'] ?? '';
            $sms_password = $options['sms_password'] ?? '';
            
            // Check for common issues in order of priority
            if (empty($sms_username) || empty($sms_password)) {
                wp_send_json_error(['message' => esc_html__('SMS settings are not configured. Please go to Settings and enter your SMS panel information.', 'maneli-car-inquiry')]);
            } elseif (!class_exists('SoapClient')) {
                wp_send_json_error(['message' => esc_html__('PHP SOAP extension is not enabled on the server. Please contact your hosting provider.', 'maneli-car-inquiry')]);
            } elseif (!defined('MANELI_SMS_API_WSDL')) {
                wp_send_json_error(['message' => esc_html__('SMS API configuration error. Please contact support.', 'maneli-car-inquiry')]);
            } elseif (!empty($otp_pattern) && !$result) {
                // Pattern is configured but failed - could be invalid pattern or API error
                wp_send_json_error(['message' => esc_html__('Error sending SMS. Please check your SMS panel information and pattern code.', 'maneli-car-inquiry')]);
            } else {
                // General error - could be API issue, invalid credentials, or network problem
                wp_send_json_error(['message' => esc_html__('Error sending SMS. Please check your SMS panel information, credentials, and network connection.', 'maneli-car-inquiry')]);
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
     * Verify password using WordPress password hashing
     */
    private function verify_password($phone, $password) {
        $options = get_option('maneli_inquiry_all_options', []);
        $saved_password = $options['dashboard_password'] ?? '';
        
        // Check if no password is set - require admin to set password first
        if (empty($saved_password)) {
            wp_send_json_error([
                'message' => esc_html__('Dashboard password not configured. Please contact administrator to set up dashboard access.', 'maneli-car-inquiry'),
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
                update_option('maneli_inquiry_all_options', $options);
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
        
        $_SESSION['maneli_dashboard_logged_in'] = true;
        $_SESSION['maneli_user_phone'] = $phone;
        $_SESSION['maneli_user_name'] = $name;
        $_SESSION['maneli_user_role'] = 'user';
        $_SESSION['maneli_login_time'] = time();
        $_SESSION['maneli_login_ip'] = $this->get_client_ip();
        
        // Set session timeout
        $_SESSION['maneli_session_timeout'] = 3600; // 1 hour
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
        
        if (!isset($_SESSION['maneli_dashboard_logged_in']) || !$_SESSION['maneli_dashboard_logged_in']) {
            return false;
        }
        
        // Check session timeout
        if (isset($_SESSION['maneli_login_time']) && isset($_SESSION['maneli_session_timeout'])) {
            if (time() - $_SESSION['maneli_login_time'] > $_SESSION['maneli_session_timeout']) {
                $this->destroy_session();
                return false;
            }
        }
        
        // Check IP address (optional - can be disabled if users have dynamic IPs)
        if (isset($_SESSION['maneli_login_ip']) && $_SESSION['maneli_login_ip'] !== '0.0.0.0') {
            $current_ip = $this->get_client_ip();
            if ($_SESSION['maneli_login_ip'] !== $current_ip) {
                // IP changed - log it but don't block (could be legitimate)
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    // Hash IP addresses for logging to protect privacy
                    $old_ip_hash = substr(md5($_SESSION['maneli_login_ip']), 0, 8);
                    $new_ip_hash = substr(md5($current_ip), 0, 8);
                    error_log('Maneli Dashboard: IP address changed during session (hashed: ' . $old_ip_hash . ' -> ' . $new_ip_hash . ')');
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
        check_ajax_referer('maneli-ajax-nonce', 'nonce');
        
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        
        // Validate phone number
        if (!preg_match('/^09\d{9}$/', $phone)) {
            wp_send_json_error(['message' => esc_html__('Invalid mobile number.', 'maneli-car-inquiry')]);
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
                wp_send_json_error(['message' => esc_html__('Error creating user account.', 'maneli-car-inquiry')]);
                return;
            }
            
            // Set role to customer
            $user = new WP_User($user_id);
            $user->set_role('customer');
        }
        
        // Store OTP in session and send SMS
        $this->maybe_start_session();
        
        // Check resend delay
        $options = get_option('maneli_inquiry_all_options', []);
        $resend_delay = intval($options['otp_resend_delay'] ?? 60);
        
        if (isset($_SESSION['maneli_sms_time'])) {
            $time_elapsed = time() - $_SESSION['maneli_sms_time'];
            if ($time_elapsed < $resend_delay) {
                $wait_time = $resend_delay - $time_elapsed;
                wp_send_json_error(['message' => sprintf(esc_html__('Please wait %d seconds.', 'maneli-car-inquiry'), $wait_time)]);
                return;
            }
        }
        
        // Generate 4-digit OTP code
        $code = wp_rand(1000, 9999);
        
        // Store code in session
        $_SESSION['maneli_sms_code'] = $code;
        $_SESSION['maneli_sms_phone'] = $phone;
        $_SESSION['maneli_sms_time'] = time();
        
        // Get OTP pattern code from settings and send SMS
        $otp_pattern = $options['otp_pattern_code'] ?? '';
        $sms_handler = new Maneli_SMS_Handler();
        
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
                $message = sprintf(esc_html__('Maneli verification code: %s', 'maneli-car-inquiry'), $code);
                $result = $sms_handler->send_sms($phone, $message);
            }
        } else {
            // Fallback to regular SMS if pattern not configured
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Sending OTP via Regular SMS (Pattern not configured)');
            }
            $message = sprintf(esc_html__('Your verification code: %s', 'maneli-car-inquiry'), $code);
            $result = $sms_handler->send_sms($phone, $message);
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('OTP Send Result: ' . ($result ? 'SUCCESS' : 'FAILED'));
        }
        
        if ($result) {
            wp_send_json_success(['message' => esc_html__('Verification code sent.', 'maneli-car-inquiry')]);
        } else {
            // بررسی اینکه آیا تنظیمات SMS وجود دارد یا نه
            $sms_username = $options['sms_username'] ?? '';
            $sms_password = $options['sms_password'] ?? '';
            
            // Get error code from SMS handler for detailed error message
            $error_code = $sms_handler->get_last_error_code();
            
            // Check for common issues in order of priority
            if (empty($sms_username) || empty($sms_password)) {
                wp_send_json_error(['message' => esc_html__('SMS settings are not configured. Please go to Settings and enter your SMS panel information.', 'maneli-car-inquiry')]);
            } elseif (!class_exists('SoapClient')) {
                wp_send_json_error(['message' => esc_html__('PHP SOAP extension is not enabled on the server. Please contact your hosting provider.', 'maneli-car-inquiry')]);
            } elseif (!defined('MANELI_SMS_API_WSDL')) {
                wp_send_json_error(['message' => esc_html__('SMS API configuration error. Please contact support.', 'maneli-car-inquiry')]);
            } elseif ($error_code !== null) {
                // Detailed error message based on API error code
                $error_messages = [
                    1 => esc_html__('Invalid SMS panel username or password. Please check your credentials in Settings.', 'maneli-car-inquiry'),
                    2 => esc_html__('Your SMS panel account is restricted or limited. Please contact your SMS panel support or check your account status.', 'maneli-car-inquiry'),
                    3 => esc_html__('Insufficient credit in your SMS panel. Please recharge your account.', 'maneli-car-inquiry'),
                    4 => esc_html__('Invalid pattern code. Please check the pattern code (Body ID) in Settings.', 'maneli-car-inquiry'),
                    5 => esc_html__('Invalid phone number format. Please check the recipient number.', 'maneli-car-inquiry'),
                ];
                
                $error_message = $error_messages[$error_code] ?? esc_html__('Error sending SMS. API returned error code: ', 'maneli-car-inquiry') . $error_code . '. Please check your SMS panel settings and contact support if the problem persists.';
                wp_send_json_error(['message' => $error_message]);
            } elseif (!empty($otp_pattern) && !$result) {
                // Pattern is configured but failed - could be invalid pattern or API error
                wp_send_json_error(['message' => esc_html__('Error sending SMS. Please check your SMS panel information and pattern code.', 'maneli-car-inquiry')]);
            } else {
                // General error - could be API issue, invalid credentials, or network problem
                wp_send_json_error(['message' => esc_html__('Error sending SMS. Please check your SMS panel information, credentials, and network connection.', 'maneli-car-inquiry')]);
            }
        }
    }
    
    /**
     * Handle verify OTP for unified login/registration
     */
    public function handle_verify_otp() {
        check_ajax_referer('maneli-ajax-nonce', 'nonce');
        
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $otp = sanitize_text_field($_POST['otp'] ?? '');
        
        if (!$this->verify_sms_code($phone, $otp)) {
            wp_send_json_error(['message' => esc_html__('Invalid or expired verification code.', 'maneli-car-inquiry')]);
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
            wp_send_json_error(['message' => esc_html__('User not found.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Check if user needs to set password
        $needs_password = get_user_meta($user->ID, 'maneli_needs_password', true);
        
        // If user has never set a password, they need to create one
        if (!$needs_password && $user->ID > 0) {
            $last_login = get_user_meta($user->ID, 'maneli_last_login', true);
            if (empty($last_login)) {
                update_user_meta($user->ID, 'maneli_needs_password', '1');
                $needs_password = true;
            }
        }
        
        if ($needs_password || get_user_meta($user->ID, 'maneli_needs_password', true) === '1') {
            // Store phone in session for create-password page (secure)
            $this->maybe_start_session();
            $_SESSION['maneli_create_password_phone'] = $phone;
            
            // Return success with needs_password flag
            // Password creation is now handled in the same login page (no redirect)
            wp_send_json_success([
                'message' => esc_html__('Verification successful. Please create a password.', 'maneli-car-inquiry'),
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
            $_SESSION['maneli_dashboard_logged_in'] = true;
            $_SESSION['maneli_user_id'] = $user->ID;
            $_SESSION['maneli_user_role'] = 'customer';
            $_SESSION['maneli_user_phone'] = $phone;
            $_SESSION['maneli_user_name'] = $user->display_name;
            
            // Update last login
            update_user_meta($user->ID, 'maneli_last_login', time());
            
            // Clear OTP session
            unset($_SESSION['maneli_sms_code']);
            unset($_SESSION['maneli_sms_phone']);
            
            // Get redirect URL from session if available
            $this->maybe_start_session();
            $redirect_url = isset($_SESSION['maneli_redirect_after_login']) ? $_SESSION['maneli_redirect_after_login'] : home_url('/dashboard');
            
            // Clear redirect from session after reading it
            unset($_SESSION['maneli_redirect_after_login']);
            
            wp_send_json_success([
                'message' => esc_html__('Login successful.', 'maneli-car-inquiry'),
                'needs_password' => false,
                'redirect' => $redirect_url
            ]);
        }
    }
    
    /**
     * Handle create password for new users
     */
    public function handle_create_password() {
        check_ajax_referer('maneli-ajax-nonce', 'nonce');
        
        // Get phone from session (secure)
        $this->maybe_start_session();
        $phone = isset($_SESSION['maneli_create_password_phone']) ? sanitize_text_field($_SESSION['maneli_create_password_phone']) : '';
        $password = $_POST['password'] ?? '';
        
        // Check if phone exists in session
        if (empty($phone)) {
            wp_send_json_error(['message' => esc_html__('Invalid session. Please restart the process.', 'maneli-car-inquiry')]);
            return;
        }
        
        if (strlen($password) < 6) {
            wp_send_json_error(['message' => esc_html__('Password must be at least 6 characters.', 'maneli-car-inquiry')]);
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
            wp_send_json_error(['message' => esc_html__('User not found.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Update user password
        wp_set_password($password, $user->ID);
        
        // Remove needs_password flag
        delete_user_meta($user->ID, 'maneli_needs_password');
        
        // CRITICAL FIX: Log user in using WordPress native authentication
        wp_set_current_user($user->ID, $user->user_login);
        wp_set_auth_cookie($user->ID, true); // true = remember me
        
        // Also set custom session for backward compatibility (if needed by other parts)
        $this->maybe_start_session();
        $_SESSION['maneli_dashboard_logged_in'] = true;
        $_SESSION['maneli_user_id'] = $user->ID;
        $_SESSION['maneli_user_role'] = 'customer';
        $_SESSION['maneli_user_phone'] = $phone;
        $_SESSION['maneli_user_name'] = $user->display_name;
        
        // Update last login
        update_user_meta($user->ID, 'maneli_last_login', time());
        
        // Clear temporary session data (but keep user session!)
        unset($_SESSION['maneli_create_password_phone']);
        unset($_SESSION['maneli_sms_code']);
        unset($_SESSION['maneli_sms_phone']);
        
        // Get redirect URL from session if available
        $redirect_url = isset($_SESSION['maneli_redirect_after_login']) ? $_SESSION['maneli_redirect_after_login'] : home_url('/dashboard');
        
        // Clear redirect from session after reading it
        unset($_SESSION['maneli_redirect_after_login']);
        
        wp_send_json_success([
            'message' => esc_html__('Password created successfully. Logging you in...', 'maneli-car-inquiry'),
            'redirect' => $redirect_url
        ]);
    }
    
    /**
     * Handle password login
     */
    public function handle_password_login() {
        check_ajax_referer('maneli-ajax-nonce', 'nonce');
        
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($phone) || empty($password)) {
            wp_send_json_error(['message' => esc_html__('Phone number and password are required.', 'maneli-car-inquiry')]);
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
            wp_send_json_error(['message' => esc_html__('Invalid phone number or password.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Verify password - trim whitespace to avoid issues
        $password = trim($password);
        
        if (empty($password)) {
            wp_send_json_error(['message' => esc_html__('Password is required.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Check password using WordPress password verification
        if (!wp_check_password($password, $user->user_pass, $user->ID)) {
            // Log failed attempt for debugging (only in debug mode)
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli Login: Password verification failed for user ID: ' . $user->ID . ' (Phone: ' . substr($phone, 0, 4) . '****)');
            }
            wp_send_json_error(['message' => esc_html__('Invalid phone number or password.', 'maneli-car-inquiry')]);
            return;
        }
        
        // Log user in using WordPress native authentication
        wp_set_current_user($user->ID, $user->user_login);
        wp_set_auth_cookie($user->ID, true); // true = remember me
        
        // Also set custom session for backward compatibility (if needed by other parts)
        $this->maybe_start_session();
        $_SESSION['maneli_dashboard_logged_in'] = true;
        $_SESSION['maneli_user_id'] = $user->ID;
        $_SESSION['maneli_user_role'] = 'customer';
        $_SESSION['maneli_user_phone'] = $phone;
        
        // Update last login
        update_user_meta($user->ID, 'maneli_last_login', time());
        
        // Get redirect URL from session if available
        $redirect_url = isset($_SESSION['maneli_redirect_after_login']) ? $_SESSION['maneli_redirect_after_login'] : home_url('/dashboard');
        
        // Clear redirect from session after reading it
        unset($_SESSION['maneli_redirect_after_login']);
        
        wp_send_json_success([
            'message' => esc_html__('Login successful.', 'maneli-car-inquiry'),
            'redirect' => $redirect_url
        ]);
    }
}

