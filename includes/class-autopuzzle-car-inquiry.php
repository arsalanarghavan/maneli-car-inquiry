<?php
/**
 * The core plugin class that orchestrates the entire AutoPuzzle Car Inquiry plugin.
 * It is responsible for loading dependencies, setting up internationalization,
 * and initializing all components.
 *
 * @package Autopuzzle_Car_Inquiry/Includes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Autopuzzle_Car_Inquiry_Plugin {

    /**
     * The single instance of the class.
     * @var Autopuzzle_Car_Inquiry_Plugin
     */
    private static $instance = null;

    /**
     * Main plugin instance. Ensures only one instance of the plugin class is loaded or can be loaded.
     * @return Autopuzzle_Car_Inquiry_Plugin - Main instance.
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
        // Load Autopuzzle_Session class early before any hooks that might use it
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-autopuzzle-session.php';
        $this->define_hooks();
    }

    /**
     * Register all hooks related to the core functionality of the plugin.
     */
    private function define_hooks() {
        // Load textdomain early on init hook (WordPress 6.7+ requirement)
        // This ensures translations are available before any class initialization
        add_action('init', [$this, 'load_plugin_textdomain'], 0);
        // Initialize plugin classes on plugins_loaded hook
        add_action('plugins_loaded', [$this, 'initialize'], 5);
        add_action('plugins_loaded', ['Autopuzzle_Session', 'cleanup_old_sessions'], 10);  // OPTIMIZED: Cleanup sessions after classes loaded
        // Add security headers for plugin pages
        add_action('send_headers', [$this, 'add_security_headers'], 1);
    }

    /**
     * Load the plugin text domain for translation.
     */
    public function load_plugin_textdomain() {
        $locale = apply_filters('plugin_locale', determine_locale(), 'autopuzzle');
        $mofile = 'autopuzzle-' . $locale . '.mo';
        
        // Only try to load from WP_LANG_DIR if it's within allowed paths
        // This prevents realpath() errors with open_basedir restrictions
        $wp_lang_file = WP_LANG_DIR . '/plugins/' . $mofile;
        if (@file_exists($wp_lang_file)) {
            load_textdomain('autopuzzle', $wp_lang_file);
        }
        
        // Load from plugin's languages directory
        $plugin_rel_path = dirname(plugin_basename(AUTOPUZZLE_PLUGIN_PATH)) . '/languages/';
        load_plugin_textdomain('autopuzzle', false, $plugin_rel_path);
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
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/functions.php';
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/helpers/class-autopuzzle-options-helper.php';
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/helpers/class-autopuzzle-encryption-helper.php';
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/helpers/class-autopuzzle-render-helpers.php';
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/helpers/class-autopuzzle-permission-helpers.php';
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/helpers/class-autopuzzle-captcha-helper.php';
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-autopuzzle-activator.php';
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-autopuzzle-database.php';
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-roles-caps.php';
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-hooks.php';
        
        // Demo Data Generator
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-demo-data-generator.php';
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-demo-data-handler.php';
        
        // Handlers
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-sms-handler.php';
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-telegram-handler.php';
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-email-handler.php';
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-notification-center-handler.php';
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-cpt-handler.php';
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-form-handler.php';
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-shortcode-handler.php';
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/public/class-finnotech-api-handler.php';

        // Admin & Settings
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-settings-page.php';
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/helpers/class-autopuzzle-branding-helper.php';
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/admin/class-autopuzzle-white-label-settings.php';
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/admin/class-autopuzzle-settings-menu.php';
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/admin/class-wordpress-admin-branding.php';
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-expert-panel.php';
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-user-profile.php';
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-admin-dashboard-widgets.php';
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-product-editor-page.php';
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-credit-report-page.php';
        
        // Reports & Analytics
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-reports-dashboard.php';
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/admin/class-reports-page.php';
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/admin/class-reports-ajax-handler.php';
        
        // Visitor Statistics
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-visitor-statistics.php';
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/admin/class-visitor-statistics-handler.php';
        
        // Logger (Autopuzzle_Session already loaded in __construct)
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-autopuzzle-logger.php';
        
        // License Management
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-autopuzzle-license.php';
        
        // Frontend Features
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-grouped-attributes.php';
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-favicon-handler.php';
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-frontend-theme-handler.php';
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-dashboard-handler.php';
        
        // Product Tags Manager
        require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-product-tags-manager.php';
        
        // Elementor Integration
        // Elementor Integration - Temporarily disabled for custom theme design
        // Will be re-enabled in next phase with custom theme builder
    }

    /**
     * Initializes all the necessary classes by creating instances of them.
     */
    private function init_classes() {
        // Initialize License first to check status
        $license = Autopuzzle_License::instance();
        
        // Check license before initializing other classes
        if (!$license->is_license_active() && !$license->is_demo_mode()) {
            // License is not active and not in demo mode
            // Still initialize classes but they will check license in their methods
        }
        
        new Autopuzzle_Roles_Caps();
        new Autopuzzle_Hooks();
        new Autopuzzle_CPT_Handler();
        new Autopuzzle_Settings_Page();
        new Autopuzzle_Form_Handler();
        new Autopuzzle_Shortcode_Handler();
        new Autopuzzle_Expert_Panel();
        new Autopuzzle_User_Profile();
        new Autopuzzle_Product_Editor_Page();
        new Autopuzzle_Credit_Report_Page();
        
        // Initialize Product Tags Manager
        new Autopuzzle_Product_Tags_Manager();

        // Check if grouped attributes is enabled (using optimized helper)
        if (Autopuzzle_Options_Helper::is_option_enabled('enable_grouped_attributes', false)) {
            new Autopuzzle_Grouped_Attributes();
        }
        
        // Initialize Frontend Theme Handler FIRST (needed by dashboard templates)
        Autopuzzle_Frontend_Theme_Handler::instance();
        
        // Initialize Logger
        Autopuzzle_Logger::instance();
        
        // Initialize Dashboard Handler
        Autopuzzle_Dashboard_Handler::instance();
    }

    /**
     * Ensure database schema is up to date before loading other components
     */
    private function maybe_run_database_updates() {
        if (!class_exists('Autopuzzle_Activator')) {
            require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/class-autopuzzle-activator.php';
        }

        if (method_exists('Autopuzzle_Activator', 'maybe_run_updates')) {
            Autopuzzle_Activator::maybe_run_updates();
        } else {
            Autopuzzle_Activator::ensure_tables();
            $current_version = defined('AUTOPUZZLE_DB_VERSION') ? AUTOPUZZLE_DB_VERSION : AUTOPUZZLE_VERSION;
            update_option('autopuzzle_db_version', $current_version);
        }
    }

    /**
     * Displays a notice in the admin area if WooCommerce is not active.
     */
    public function woocommerce_not_active_notice() {
        // Use non-translated string since translations may not be loaded yet
        $message = 'AutoPuzzle Car Inquiry plugin requires WooCommerce to be installed and activated. Please activate WooCommerce to continue.';
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
        $dashboard_page = get_query_var('autopuzzle_dashboard_page');
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
        $csp = apply_filters('autopuzzle_security_csp_policy', $csp);
        
        header("Content-Security-Policy: {$csp}", true);
        
        // Permissions-Policy: Control browser features
        $permissions_policy = "geolocation=(), microphone=(), camera=()";
        $permissions_policy = apply_filters('autopuzzle_security_permissions_policy', $permissions_policy);
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