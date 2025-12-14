<?php
/**
 * Elementor Theme Builder Integration
 * Registers header and footer templates in theme builder
 * 
 * @package Maneli_Car_Inquiry/Includes/Elementor
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Elementor_Theme_Builder {

    /**
     * Initialize theme builder integration
     */
    public static function init() {
        if (!class_exists('\Elementor\Plugin')) {
            return;
        }

        add_action('elementor/theme/register_locations', [__CLASS__, 'register_theme_locations']);
        add_action('init', [__CLASS__, 'setup_theme_builder']);
    }

    /**
     * Register header and footer locations
     */
    public static function register_theme_locations($manager) {
        $manager->register_location('header');
        $manager->register_location('footer');
    }

    /**
     * Setup theme builder templates
     */
    public static function setup_theme_builder() {
        if (!class_exists('\Elementor\Core\Settings\Manager')) {
            return;
        }

        // Get header template
        $header = self::get_header_template();
        $footer = self::get_footer_template();

        if ($header) {
            update_option('elementor_location_header', $header->ID);
        }

        if ($footer) {
            update_option('elementor_location_footer', $footer->ID);
        }
    }

    /**
     * Get header template
     */
    private static function get_header_template() {
        $args = [
            'post_type' => 'elementor_library',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => '_elementor_template_type',
                    'value' => 'header',
                ]
            ]
        ];
        $templates = get_posts($args);
        return !empty($templates) ? $templates[0] : null;
    }

    /**
     * Get footer template
     */
    private static function get_footer_template() {
        $args = [
            'post_type' => 'elementor_library',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => '_elementor_template_type',
                    'value' => 'footer',
                ]
            ]
        ];
        $templates = get_posts($args);
        return !empty($templates) ? $templates[0] : null;
    }

    /**
     * Get all elementor locations
     */
    public static function get_locations() {
        return [
            'header' => __('Header', 'maneli-car-inquiry'),
            'footer' => __('Footer', 'maneli-car-inquiry'),
        ];
    }
}

// Initialize
Maneli_Elementor_Theme_Builder::init();
