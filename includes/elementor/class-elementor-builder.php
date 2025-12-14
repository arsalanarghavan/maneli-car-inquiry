<?php
/**
 * Elementor Page Builder Helper
 * Provides pre-built Elementor sections for homepage
 * 
 * @package Maneli_Car_Inquiry/Includes/Elementor
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Elementor_Builder {

    /**
     * Initialize Elementor integration
     */
    public static function init() {
        if (!did_action('elementor_loaded')) {
            return;
        }

        add_action('elementor/init', [__CLASS__, 'register_elementor_category']);
        add_action('elementor/widgets/widgets_registered', [__CLASS__, 'register_widgets']);
    }

    /**
     * Register custom Elementor category
     */
    public static function register_elementor_category() {
        \Elementor\Plugin::instance()->elements_manager->add_category(
            'maneli',
            [
                'title' => __('Maneli Widgets', 'maneli-car-inquiry'),
                'icon' => 'fa fa-plug',
            ]
        );
    }

    /**
     * Register custom widgets
     */
    public static function register_widgets() {
        // Include custom widgets
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/elementor/widgets/class-maneli-header-widget.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/elementor/widgets/class-maneli-hero-widget.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/elementor/widgets/class-maneli-products-widget.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/elementor/widgets/class-maneli-footer-widget.php';

        // Register widgets
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(
            new Maneli_Header_Widget()
        );
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(
            new Maneli_Hero_Widget()
        );
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(
            new Maneli_Products_Widget()
        );
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(
            new Maneli_Footer_Widget()
        );
    }

    /**
     * Import pre-built page template
     */
    public static function import_home_template() {
        if (!class_exists('\Elementor\Importer')) {
            return false;
        }

        // Check if homepage already has Elementor content
        $page_id = get_option('page_on_front');
        if (!$page_id) {
            return false;
        }

        // Check if already has Elementor data
        $elementor_data = get_post_meta($page_id, '_elementor_data', true);
        if ($elementor_data) {
            return true; // Already has content
        }

        // Import template JSON
        $template_file = MANELI_INQUIRY_PLUGIN_PATH . 'includes/elementor/templates/home-template.json';
        if (!file_exists($template_file)) {
            return false;
        }

        $template_data = json_decode(file_get_contents($template_file), true);
        if (!$template_data) {
            return false;
        }

        // Save to page
        update_post_meta($page_id, '_elementor_data', wp_slash(wp_json_encode($template_data)));
        update_post_meta($page_id, '_elementor_version', ELEMENTOR_VERSION);
        update_post_meta($page_id, '_elementor_edit_mode', 'builder');

        return true;
    }

    /**
     * Get pre-built sections
     */
    public static function get_home_sections() {
        return [
            'header' => [
                'name' => 'header',
                'title' => __('Header', 'maneli-car-inquiry'),
                'description' => __('Professional header with navigation', 'maneli-car-inquiry'),
            ],
            'hero' => [
                'name' => 'hero',
                'title' => __('Hero Slider', 'maneli-car-inquiry'),
                'description' => __('Full-width hero section with slider', 'maneli-car-inquiry'),
            ],
            'features' => [
                'name' => 'features',
                'title' => __('Features', 'maneli-car-inquiry'),
                'description' => __('6 feature cards section', 'maneli-car-inquiry'),
            ],
            'products' => [
                'name' => 'products',
                'title' => __('Products', 'maneli-car-inquiry'),
                'description' => __('WooCommerce products grid', 'maneli-car-inquiry'),
            ],
            'process' => [
                'name' => 'process',
                'title' => __('Buying Process', 'maneli-car-inquiry'),
                'description' => __('3-step process section', 'maneli-car-inquiry'),
            ],
            'statistics' => [
                'name' => 'statistics',
                'title' => __('Statistics', 'maneli-car-inquiry'),
                'description' => __('Counter section with animations', 'maneli-car-inquiry'),
            ],
            'testimonials' => [
                'name' => 'testimonials',
                'title' => __('Testimonials', 'maneli-car-inquiry'),
                'description' => __('Customer reviews section', 'maneli-car-inquiry'),
            ],
            'blog' => [
                'name' => 'blog',
                'title' => __('Blog', 'maneli-car-inquiry'),
                'description' => __('Latest posts section', 'maneli-car-inquiry'),
            ],
            'cta' => [
                'name' => 'cta',
                'title' => __('Call to Action', 'maneli-car-inquiry'),
                'description' => __('Contact form and CTA section', 'maneli-car-inquiry'),
            ],
            'footer' => [
                'name' => 'footer',
                'title' => __('Footer', 'maneli-car-inquiry'),
                'description' => __('Professional footer with links', 'maneli-car-inquiry'),
            ],
        ];
    }
}

// Initialize when Elementor is loaded
if (did_action('elementor_loaded')) {
    Maneli_Elementor_Builder::init();
} else {
    add_action('elementor_loaded', [__CLASS__, 'init']);
}
