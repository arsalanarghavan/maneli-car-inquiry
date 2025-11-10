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
    }

    /**
     * Load the plugin text domain for translation.
     */
    public function load_plugin_textdomain() {
        $locale = apply_filters('plugin_locale', determine_locale(), 'maneli-car-inquiry');
        $mofile = 'maneli-car-inquiry-' . $locale . '.mo';
        
        // Try plugin's languages directory first
        $plugin_rel_path = dirname(plugin_basename(MANELI_INQUIRY_PLUGIN_PATH)) . '/languages/';
        load_textdomain('maneli-car-inquiry', WP_LANG_DIR . '/plugins/' . $mofile);
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
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/helpers/class-maneli-render-helpers.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/helpers/class-maneli-permission-helpers.php';
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
        
        // Dashboard session handler must load before logger to support session-based authentication in logging
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-maneli-session.php';
        
        // Logger
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-maneli-logger.php';
        
        // Frontend Features
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-grouped-attributes.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-frontend-theme-handler.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-dashboard-handler.php';
    }

    /**
     * Initializes all the necessary classes by creating instances of them.
     */
    private function init_classes() {
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

        $options = get_option('maneli_inquiry_all_options', []);
        if (!empty($options['enable_grouped_attributes']) && $options['enable_grouped_attributes'] == '1') {
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
        ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <?php esc_html_e('Maneli Car Inquiry plugin requires WooCommerce to be installed and activated. Please activate WooCommerce to continue.', 'maneli-car-inquiry'); ?>
            </p>
        </div>
        <?php
    }

    /**
     * The main method that runs the plugin.
     * This is called from the root plugin file.
     */
    public function run() {
        // The constructor handles all the necessary hooks. This method is the entry point.
    }
}