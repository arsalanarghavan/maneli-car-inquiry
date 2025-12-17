<?php
/**
 * Favicon Handler
 * 
 * Handles favicon loading from white-label settings across all site pages
 * 
 * @package AutoPuzzle
 * @subpackage Includes
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Autopuzzle_Favicon_Handler {
    
    /**
     * Initialize favicon handler
     */
    public static function init() {
        // Add favicon to all frontend pages
        add_action( 'wp_head', [ __CLASS__, 'output_favicon' ], 1 );
        
        // Add favicon to admin pages
        add_action( 'admin_head', [ __CLASS__, 'output_favicon' ], 1 );
        
        // Add favicon to login page
        add_action( 'login_head', [ __CLASS__, 'output_favicon' ], 1 );
    }
    
    /**
     * Output favicon link tag
     * 
     * @return void
     */
    public static function output_favicon() {
        $favicon_url = self::get_favicon_url();
        
        if ( empty( $favicon_url ) ) {
            return;
        }
        
        // Determine the correct type based on file extension
        $type = self::get_favicon_type( $favicon_url );
        
        printf(
            '<link rel="icon" href="%s" type="%s">%s',
            esc_url( $favicon_url ),
            esc_attr( $type ),
            "\n"
        );
    }
    
    /**
     * Get favicon URL from white-label settings
     * 
     * @return string
     */
    public static function get_favicon_url() {
        // Try to get favicon from white-label branding first
        if ( class_exists( 'Autopuzzle_Branding_Helper' ) ) {
            $favicon_url = Autopuzzle_Branding_Helper::get_favicon();
            if ( ! empty( $favicon_url ) ) {
                return $favicon_url;
            }
        }
        
        // Try to get WordPress site icon (favicon)
        $site_icon_url = get_site_icon_url();
        if ( ! empty( $site_icon_url ) ) {
            return $site_icon_url;
        }
        
        // Fallback to default favicon
        return AUTOPUZZLE_PLUGIN_URL . 'assets/images/brand-logos/favicon.ico';
    }
    
    /**
     * Determine favicon MIME type based on file extension
     * 
     * @param string $url Favicon URL
     * @return string MIME type
     */
    public static function get_favicon_type( $url ) {
        // Parse URL to get file extension
        $parsed_url = wp_parse_url( $url );
        if ( ! isset( $parsed_url['path'] ) ) {
            return 'image/x-icon';
        }
        
        $path = strtolower( $parsed_url['path'] );
        
        if ( strpos( $path, '.png' ) !== false ) {
            return 'image/png';
        } elseif ( strpos( $path, '.gif' ) !== false ) {
            return 'image/gif';
        } elseif ( strpos( $path, '.jpg' ) !== false || strpos( $path, '.jpeg' ) !== false ) {
            return 'image/jpeg';
        } elseif ( strpos( $path, '.svg' ) !== false ) {
            return 'image/svg+xml';
        } elseif ( strpos( $path, '.webp' ) !== false ) {
            return 'image/webp';
        }
        
        // Default to ico for .ico files and unknown formats
        return 'image/x-icon';
    }
}

// Initialize on plugins_loaded
add_action( 'plugins_loaded', function() {
    Autopuzzle_Favicon_Handler::init();
}, 15 );
