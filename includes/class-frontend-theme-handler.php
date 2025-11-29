<?php
/**
 * Frontend Theme Handler Class
 * 
 * Manages theme customization options (logos, colors, etc.)
 * 
 * @package Maneli_Car_Inquiry/Includes
 * @author  Arsalan Arghavan
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Frontend_Theme_Handler {
    
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
        // No hooks needed - templates call methods directly
    }
    
    /**
     * Convert hex color to RGB
     */
    private function hex_to_rgb($hex) {
        $hex = str_replace('#', '', $hex);
        
        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        
        return "$r, $g, $b";
    }
    
    /**
     * Get logo URL
     */
    public function get_logo($type = 'desktop') {
        $options = Maneli_Options_Helper::get_all_options();
        $logo_key = 'theme_logo_' . str_replace('-', '_', $type);
        
        if (isset($options[$logo_key]) && !empty($options[$logo_key])) {
            return esc_url($options[$logo_key]);
        }
        
        // Try to get WordPress custom logo first
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
            if ($logo_url) {
                return esc_url($logo_url);
            }
        }
        
        // Fallback to site icon (favicon) as logo for smaller versions
        $site_icon_url = get_site_icon_url();
        if ($site_icon_url && in_array($type, ['toggle', 'toggle-dark', 'toggle-white'])) {
            return esc_url($site_icon_url);
        }
        
        // Default logos as last resort
        $defaults = [
            'desktop' => MANELI_INQUIRY_PLUGIN_URL . 'assets/images/brand-logos/desktop-logo.png',
            'desktop_dark' => MANELI_INQUIRY_PLUGIN_URL . 'assets/images/brand-logos/desktop-dark.png',
            'desktop_white' => MANELI_INQUIRY_PLUGIN_URL . 'assets/images/brand-logos/desktop-white.png',
            'toggle' => MANELI_INQUIRY_PLUGIN_URL . 'assets/images/brand-logos/toggle-logo.png',
            'toggle_dark' => MANELI_INQUIRY_PLUGIN_URL . 'assets/images/brand-logos/toggle-dark.png',
            'toggle_white' => MANELI_INQUIRY_PLUGIN_URL . 'assets/images/brand-logos/toggle-white.png',
        ];
        
        $key = str_replace('-', '_', $type);
        return isset($defaults[$key]) ? $defaults[$key] : $defaults['desktop'];
    }
    
    /**
     * Get favicon URL from WordPress
     */
    public function get_favicon() {
        // Try to get WordPress site icon (favicon) first
        $site_icon_url = get_site_icon_url();
        if ($site_icon_url) {
            return esc_url($site_icon_url);
        }
        
        // Fallback to default favicon
        return MANELI_INQUIRY_PLUGIN_URL . 'assets/images/brand-logos/favicon.ico';
    }
    
    /**
     * Get site title
     */
    public function get_site_title() {
        $options = Maneli_Options_Helper::get_all_options();
        
        if (isset($options['theme_site_title']) && !empty($options['theme_site_title'])) {
            return esc_html($options['theme_site_title']);
        }
        
        return get_bloginfo('name');
    }
    
    /**
     * Get footer text
     */
    public function get_footer_text() {
        $options = Maneli_Options_Helper::get_all_options();
        
        if (isset($options['theme_footer_text']) && !empty($options['theme_footer_text'])) {
            return wp_kses_post($options['theme_footer_text']);
        }
        
        // Get current Jalali year
        $current_year = '۱۴۰۴'; // یا می‌تونی از تابع تبدیل تاریخ استفاده کنی
        if (function_exists('maneli_gregorian_to_jalali')) {
            $current_year = maneli_gregorian_to_jalali(date('Y'), date('m'), date('d'), 'Y');
        }
        
        return sprintf(
            '%s طراحی با <i class="la la-heart text-danger"></i> توسط <a href="https://pazling.ir" target="_blank" class="text-primary fw-medium">موسسه پازلینگ</a>',
            $current_year
        );
    }
    
    /**
     * Output custom CSS variables
     */
    public function output_custom_css() {
        $options = Maneli_Options_Helper::get_all_options();
        
        $primary_color = isset($options['theme_primary_color']) ? $options['theme_primary_color'] : '';
        $secondary_color = isset($options['theme_secondary_color']) ? $options['theme_secondary_color'] : '';
        $header_bg = isset($options['theme_header_bg']) ? $options['theme_header_bg'] : '';
        $menu_bg = isset($options['theme_menu_bg']) ? $options['theme_menu_bg'] : '';
        
        if (empty($primary_color) && empty($secondary_color) && empty($header_bg) && empty($menu_bg)) {
            return;
        }
        
        echo '<style id="maneli-custom-theme-colors">';
        echo ':root {';
        
        if (!empty($primary_color)) {
            $rgb = $this->hex_to_rgb($primary_color);
            echo '--primary-rgb: ' . $rgb . ';';
            echo '--primary-color: rgb(var(--primary-rgb));';
        }
        
        if (!empty($secondary_color)) {
            $rgb = $this->hex_to_rgb($secondary_color);
            echo '--secondary-rgb: ' . $rgb . ';';
        }
        
        if (!empty($header_bg)) {
            echo '--header-bg: ' . esc_attr($header_bg) . ';';
        }
        
        if (!empty($menu_bg)) {
            echo '--menu-bg: ' . esc_attr($menu_bg) . ';';
        }
        
        echo '}';
        echo '</style>';
    }
}
