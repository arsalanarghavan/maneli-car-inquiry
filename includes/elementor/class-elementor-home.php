<?php
/**
 * Elementor Home Page Setup
 * Register custom post type, page templates, and theme support
 * 
 * @package Maneli_Car_Inquiry/Includes/Elementor
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Elementor_Home {

    /**
     * Initialize Elementor home page setup
     */
    public static function init() {
        add_action('init', [__CLASS__, 'register_templates']);
        add_filter('page_template', [__CLASS__, 'get_page_template']);
        add_action('admin_init', [__CLASS__, 'add_settings']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        add_action('elementor/widgets/widgets_registered', [__CLASS__, 'register_elementor_widgets']);
        add_action('elementor/init', [__CLASS__, 'register_elementor_category']);
    }

    /**
     * Register custom page templates
     */
    public static function register_templates() {
        $templates = [
            'elementor/home-page.php' => 'صفحه اصلی مانلی (Elementor)',
            'elementor/shop-page.php' => 'صفحه فروشگاه (Elementor)',
        ];

        // Register templates
        foreach ($templates as $template => $name) {
            wp_register_script_module(
                'maneli-elementor-template-' . sanitize_title($name),
                $template,
                ['wp-dom-ready'],
                '1.0.0'
            );
        }
    }

    /**
     * Get custom page template
     */
    public static function get_page_template($template) {
        global $post;

        if (!is_a($post, 'WP_Post')) {
            return $template;
        }

        // Get template option from page meta
        $page_template = get_post_meta($post->ID, '_maneli_page_template', true);

        if ($page_template) {
            $custom_template = MANELI_INQUIRY_PLUGIN_PATH . $page_template;
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }

        return $template;
    }

    /**
     * Register Elementor category
     */
    public static function register_elementor_category() {
        if (class_exists('\Elementor\Plugin')) {
            \Elementor\Plugin::instance()->elements_manager->add_category(
                'maneli',
                [
                    'title' => __('Maneli Widgets', 'maneli-car-inquiry'),
                    'icon' => 'fa fa-plug',
                ]
            );
        }
    }

    /**
     * Register Elementor widgets
     */
    public static function register_elementor_widgets() {
        if (!class_exists('\Elementor\Plugin')) {
            return;
        }

        // Load widget files
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/elementor/widgets/class-maneli-header-widget.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/elementor/widgets/class-maneli-hero-widget.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/elementor/widgets/class-maneli-products-widget.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/elementor/widgets/class-maneli-footer-widget.php';

        // Register widgets
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \Maneli_Header_Widget());
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \Maneli_Hero_Widget());
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \Maneli_Products_Widget());
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \Maneli_Footer_Widget());
    }

    /**
     * Add admin settings for home page
     */
    public static function add_settings() {
        // Register option for home page layout
        register_setting('general', 'maneli_home_layout', [
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'elementor'
        ]);

        register_setting('general', 'maneli_show_custom_header', [
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => true
        ]);

        register_setting('general', 'maneli_show_custom_footer', [
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => true
        ]);
    }

    /**
     * Enqueue necessary scripts and styles
     */
    public static function enqueue_assets() {
        global $post;

        // Check if this is the home page
        $is_home = is_home() || is_front_page() || (is_a($post, 'WP_Post') && get_post_meta($post->ID, '_maneli_page_template', true) === 'elementor/home-page.php');

        if (!$is_home) {
            return;
        }

        // Enqueue Swiper for slider
        wp_enqueue_script('swiper', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', [], '11.0.0', true);
        wp_enqueue_style('swiper', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', [], '11.0.0');

        // Enqueue Font Awesome
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', [], '6.4.0');

        // Enqueue custom styles and scripts
        wp_enqueue_style('maneli-elementor-home', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/elementor-home.css', [], '1.0.0');
        wp_enqueue_script('maneli-elementor-home', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/elementor-home.js', ['jquery'], '1.0.0', true);

        // Localize script
        wp_localize_script('maneli-elementor-home', 'maneliHome', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'blogName' => get_bloginfo('name'),
            'nonce' => wp_create_nonce('maneli_home_nonce')
        ]);
    }

    /**
     * Get home page settings
     */
    public static function get_settings() {
        return [
            'layout' => get_option('maneli_home_layout', 'elementor'),
            'show_header' => get_option('maneli_show_custom_header', true),
            'show_footer' => get_option('maneli_show_custom_footer', true),
        ];
    }

    /**
     * Create sample home page if it doesn't exist
     */
    public static function create_home_page() {
        // Check if home page exists
        $home_page = get_page_by_path('home');

        if (!$home_page) {
            // Create new home page
            $home_page_id = wp_insert_post([
                'post_title' => 'صفحه اصلی',
                'post_name' => 'home',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_content' => '<!-- Elementor Home Page -->',
            ]);

            if ($home_page_id) {
                // Set as front page
                update_option('page_on_front', $home_page_id);
                update_option('show_on_front', 'page');

                // Set custom template
                update_post_meta($home_page_id, '_maneli_page_template', 'elementor/home-page.php');
            }
        }
    }
}

// Initialize
Maneli_Elementor_Home::init();

// Create home page on plugin activation
register_activation_hook(MANELI_INQUIRY_PLUGIN_FILE, [
    'Maneli_Elementor_Home',
    'create_home_page'
]);
