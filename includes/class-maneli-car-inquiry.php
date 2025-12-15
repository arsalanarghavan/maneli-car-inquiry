<?php
/**
 * The core plugin class that orchestrates the entire Maneli Car Inquiry plugin.
 * It is responsible for loading dependencies, setting up internationalization,
 * and initializing all components.
 *
 * @package Maneli_Car_Inquiry/Includes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Maneli_Car_Inquiry_Plugin {

    /**
     * The single instance of the class.
     * @var Maneli_Car_Inquiry_Plugin
     */
    private static $instance = null;

    /**
     * Main plugin instance. Ensures only one instance of the plugin class is loaded or can be loaded.
     * @return Maneli_Car_Inquiry_Plugin - Main instance.
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor. Private to prevent direct object creation.
     */
    private function __construct() {
        // Load Maneli_Session class early before any hooks that might use it
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-maneli-session.php';
        $this->define_hooks();
    }

    /**
     * Register all hooks related to the core functionality of the plugin.
     */
    private function define_hooks() {
        // Load textdomain on init hook (WordPress 6.7+ requirement)
        // Translations should be loaded at init action or later
        add_action('init', [$this, 'load_plugin_textdomain'], 1);
        add_action('plugins_loaded', [$this, 'initialize'], 5);
        add_action('plugins_loaded', ['Maneli_Session', 'cleanup_old_sessions'], 10);  // OPTIMIZED: Cleanup sessions after classes loaded
        // Add security headers for plugin pages
        add_action('send_headers', [$this, 'add_security_headers'], 1);
    }

    /**
     * Load the plugin text domain for translation.
     */
    public function load_plugin_textdomain() {
        $locale = apply_filters('plugin_locale', determine_locale(), 'maneli-car-inquiry');
        $mofile = 'maneli-car-inquiry-' . $locale . '.mo';
        
        // Only try to load from WP_LANG_DIR if it's within allowed paths
        // This prevents realpath() errors with open_basedir restrictions
        $wp_lang_file = WP_LANG_DIR . '/plugins/' . $mofile;
        if (@file_exists($wp_lang_file)) {
            load_textdomain('maneli-car-inquiry', $wp_lang_file);
        }
        
        // Load from plugin's languages directory
        $plugin_rel_path = dirname(plugin_basename(MANELI_INQUIRY_PLUGIN_PATH)) . '/languages/';
        load_plugin_textdomain('maneli-car-inquiry', false, $plugin_rel_path);
    }

    /**
     * Initializes the plugin after all other plugins are loaded.
     * Checks for WooCommerce dependency before proceeding.
     */
    public function initialize() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', [$this, 'woocommerce_not_active_notice']);
            return;
        }
        $this->maybe_run_database_updates();
        $this->includes();
        $this->init_classes();
    }

    /**
     * Includes all necessary PHP files for the plugin to function.
     */
    private function includes() {
        // Core Functionality & Helpers
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/functions.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/helpers/class-maneli-options-helper.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/helpers/class-maneli-encryption-helper.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/helpers/class-maneli-render-helpers.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/helpers/class-maneli-permission-helpers.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/helpers/class-maneli-captcha-helper.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-maneli-activator.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-maneli-database.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-roles-caps.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-hooks.php';
        
        // Handlers
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-sms-handler.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-telegram-handler.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-email-handler.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-notification-center-handler.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-cpt-handler.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-form-handler.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-shortcode-handler.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/public/class-finnotech-api-handler.php';

        // Admin & Settings
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-settings-page.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-expert-panel.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-user-profile.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-admin-dashboard-widgets.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-product-editor-page.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-credit-report-page.php';
        
        // Reports & Analytics
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-reports-dashboard.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/admin/class-reports-page.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/admin/class-reports-ajax-handler.php';
        
        // Visitor Statistics
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-visitor-statistics.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/admin/class-visitor-statistics-handler.php';
        
        // Logger (Maneli_Session already loaded in __construct)
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-maneli-logger.php';
        
        // License Management
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-maneli-license.php';
        
        // Frontend Features
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-grouped-attributes.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-frontend-theme-handler.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-dashboard-handler.php';
        
        // Elementor Integration
        // Elementor Integration - Temporarily disabled for custom theme design
        // Will be re-enabled in next phase with custom theme builder
    }

    /**
     * Initializes all the necessary classes by creating instances of them.
     */
    private function init_classes() {
        // Initialize License first to check status
        $license = Maneli_License::instance();
        
        // Check license before initializing other classes
        if (!$license->is_license_active() && !$license->is_demo_mode()) {
            // License is not active and not in demo mode
            // Still initialize classes but they will check license in their methods
        }
        
        new Maneli_Roles_Caps();
        new Maneli_Hooks();
        new Maneli_CPT_Handler();
        new Maneli_Settings_Page();
        new Maneli_Form_Handler();
        new Maneli_Shortcode_Handler();
        new Maneli_Expert_Panel();
        new Maneli_User_Profile();
        new Maneli_Product_Editor_Page();
        new Maneli_Credit_Report_Page();

        // Check if grouped attributes is enabled (using optimized helper)
        if (Maneli_Options_Helper::is_option_enabled('enable_grouped_attributes', false)) {
            new Maneli_Grouped_Attributes();
        }
        
        // Initialize Frontend Theme Handler FIRST (needed by dashboard templates)
        Maneli_Frontend_Theme_Handler::instance();
        
        // Initialize Logger
        Maneli_Logger::instance();
        
        // Initialize Dashboard Handler
        Maneli_Dashboard_Handler::instance();
    }

    /**
     * Ensure database schema is up to date before loading other components
     */
    private function maybe_run_database_updates() {
        if (!class_exists('Maneli_Activator')) {
            require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-maneli-activator.php';
        }

        if (method_exists('Maneli_Activator', 'maybe_run_updates')) {
            Maneli_Activator::maybe_run_updates();
        } else {
            Maneli_Activator::ensure_tables();
            $current_version = defined('MANELI_DB_VERSION') ? MANELI_DB_VERSION : MANELI_VERSION;
            update_option('maneli_db_version', $current_version);
        }
    }

    /**
     * Displays a notice in the admin area if WooCommerce is not active.
     */
    public function woocommerce_not_active_notice() {
        // Use non-translated string since translations may not be loaded yet
        $message = 'Maneli Car Inquiry plugin requires WooCommerce to be installed and activated. Please activate WooCommerce to continue.';
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
        <?php
    }

    /**
     * Add security headers to HTTP responses for plugin pages.
     * These headers help protect against XSS, clickjacking, and other attacks.
     */
    public function add_security_headers() {
        // Only add headers for plugin-related pages
        $is_plugin_page = false;
        
        // Check if it's a dashboard page
        $dashboard_page = get_query_var('maneli_dashboard_page');
        if (!empty($dashboard_page)) {
            $is_plugin_page = true;
        }
        
        // Check if it's a shortcode page (you can extend this check based on your needs)
        global $post;
        if ($post && has_shortcode($post->post_content, 'maneli_')) {
            $is_plugin_page = true;
        }
        
        // Check if it's a login/create-password page
        $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        if (strpos($request_uri, '/login') !== false || strpos($request_uri, '/create-password') !== false || strpos($request_uri, '/verify-otp') !== false) {
            $is_plugin_page = true;
        }
        
        if (!$is_plugin_page) {
            return;
        }
        
        // Prevent headers from being sent twice
        if (headers_sent()) {
            return;
        }
        
        // X-Frame-Options: Prevent clickjacking attacks
        header('X-Frame-Options: SAMEORIGIN', true);
        
        // X-Content-Type-Options: Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff', true);
        
        // X-XSS-Protection: Enable browser XSS filter (legacy support)
        header('X-XSS-Protection: 1; mode=block', true);
        
        // Referrer-Policy: Control referrer information
        header('Referrer-Policy: strict-origin-when-cross-origin', true);
        
        // Content-Security-Policy: Restrict resources (adjust based on your needs)
        // This is a basic CSP - you may need to customize it based on your assets
        $csp = "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://code.jquery.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; img-src 'self' data: https:; connect-src 'self'; frame-ancestors 'self';";
        
        // Allow filtering of CSP header
        $csp = apply_filters('maneli_security_csp_policy', $csp);
        
        header("Content-Security-Policy: {$csp}", true);
        
        // Permissions-Policy: Control browser features
        $permissions_policy = "geolocation=(), microphone=(), camera=()";
        $permissions_policy = apply_filters('maneli_security_permissions_policy', $permissions_policy);
        header("Permissions-Policy: {$permissions_policy}", true);
    }

    /**
     * The main method that runs the plugin.
     * This is called from the root plugin file.
     */
    public function run() {
        // The constructor handles all the necessary hooks. This method is the entry point.
    }
}