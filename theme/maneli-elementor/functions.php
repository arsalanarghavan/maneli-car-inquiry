<?php
/**
 * Maneli Elementor Theme functions
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load theme settings
require_once get_template_directory() . '/includes/class-theme-settings-enhanced.php';

// Theme setup
add_action('after_setup_theme', function () {
    // Core supports
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('automatic-feed-links');

    // WooCommerce ready
    add_theme_support('woocommerce');

    // Custom logo
    add_theme_support('custom-logo', [
        'height'      => 80,
        'width'       => 240,
        'flex-height' => true,
        'flex-width'  => true,
    ]);

    // Menus
    register_nav_menus([
        'primary' => __('Primary Menu', 'maneli-elementor'),
        'footer'  => __('Footer Menu', 'maneli-elementor'),
    ]);
});

// Enqueue minimal styles
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('maneli-elementor-style', get_stylesheet_uri(), [], '1.0.0');
});

// Elementor compatibility
add_action('after_setup_theme', function () {
    add_theme_support('elementor');
    add_theme_support('elementor-pro-theme');
    add_theme_support('elementor-woocommerce');
});

// Auto-setup on theme switch
add_action('after_switch_theme', function () {
    if (class_exists('Maneli_Elementor_Auto_Setup')) {
        Maneli_Elementor_Auto_Setup::setup_elementor();
    }
});

// Apply theme CSS variables
add_action('wp_head', function () {
    $options = Maneli_Theme_Settings_Enhanced::get_all_options();

    $css = ':root {';
    $css .= '--primary-color: ' . sanitize_hex_color($options['primary_color']) . ';';
    $css .= '--secondary-color: ' . sanitize_hex_color($options['secondary_color']) . ';';
    $css .= '--accent-color: ' . sanitize_hex_color($options['accent_color']) . ';';
    $css .= '--header-bg: ' . sanitize_hex_color($options['header_bg_color']) . ';';
    $css .= '--header-text: ' . sanitize_hex_color($options['header_text_color']) . ';';
    $css .= '--footer-bg: ' . sanitize_hex_color($options['footer_bg_color']) . ';';
    $css .= '--footer-text: ' . sanitize_hex_color($options['footer_text_color']) . ';';
    $css .= '--body-font: ' . sanitize_text_field($options['body_font']) . ';';
    $css .= '--heading-font: ' . sanitize_text_field($options['heading_font']) . ';';
    $css .= '}';

    if (!empty($options['custom_css'])) {
        $css .= wp_kses_post($options['custom_css']);
    }

    echo '<style>' . wp_kses_post($css) . '</style>';
});

// Fallback content renderer
function maneli_elementor_render_content() {
    if (class_exists('\\Elementor\\Plugin') && is_singular()) {
        $content = \Elementor\Plugin::instance()->frontend->get_builder_content_for_display(get_the_ID());
        if ($content) {
            echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            return;
        }
    }

    if (have_posts()) {
        while (have_posts()) {
            the_post();
            the_content();
        }
    }
}
