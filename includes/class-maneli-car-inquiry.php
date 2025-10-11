<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main plugin class.
 */
final class Maneli_Car_Inquiry_Plugin {

    public function __construct() {
        add_action('plugins_loaded', [$this, 'initialize']);
    }

    /**
     * Initializes the plugin after all other plugins are loaded.
     */
    public function initialize() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', [$this, 'woocommerce_not_active_notice']);
            return;
        }
        $this->includes();
        $this->init_classes();
    }

    /**
     * Includes all necessary files.
     */
    public function includes() {
        // Core Functionality
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/functions.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-roles-caps.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-hooks.php';
        
        // Handlers
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-sms-handler.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-cpt-handler.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-form-handler.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-shortcode-handler.php';

        // Admin & Settings
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-settings-page.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-expert-panel.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-user-profile.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-admin-dashboard-widgets.php';
        
        // Frontend Features
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-grouped-attributes.php';
    }

    /**
     * Initializes all the classes.
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

        $options = get_option('maneli_inquiry_all_options', []);
        if (isset($options['enable_grouped_attributes']) && $options['enable_grouped_attributes'] == '1') {
            new Maneli_Grouped_Attributes();
        }
    }

    /**
     * Shows a notice if WooCommerce is not active.
     */
    public function woocommerce_not_active_notice() {
        ?>
        <div class="error"><p>پلاگین استعلام خودرو برای کار کردن به ووکامرس نیاز دارد. لطفاً ووکامرس را نصب و فعال کنید.</p></div>
        <?php
    }
}