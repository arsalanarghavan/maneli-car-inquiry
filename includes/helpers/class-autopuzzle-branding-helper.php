<?php
/**
 * AutoPuzzle Branding Helper
 * 
 * Manages dynamic branding/white label settings for the AutoPuzzle plugin.
 * This class handles all brand name customization and provides methods to retrieve
 * branding values throughout the plugin.
 * 
 * @package AutoPuzzle
 * @subpackage Helpers
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Autopuzzle_Branding_Helper {
    
    /**
     * Option key for storing branding settings
     */
    const OPTION_KEY = 'autopuzzle_branding_settings';
    
    /**
     * Cache for branding settings
     */
    private static $cache = [];
    
    /**
     * Get all branding settings
     * 
     * @return array
     */
    public static function get_all_settings() {
        if ( ! empty( self::$cache ) ) {
            return self::$cache;
        }
        
        $defaults = self::get_defaults();
        $settings = get_option( self::OPTION_KEY, [] );
        
        self::$cache = wp_parse_args( $settings, $defaults );
        return self::$cache;
    }
    
    /**
     * Get default branding settings
     * 
     * @return array
     */
    public static function get_defaults() {
        return [
            'brand_name'              => 'AutoPuzzle',
            'brand_name_persian'      => 'اتوپازل',
            'brand_tagline'           => 'Car Inquiry System',
            'brand_tagline_persian'   => 'سیستم استعلام خودرو',
            'company_name'            => 'AutoPuzzle Company',
            'company_name_persian'    => 'شرکت اتوپازل',
            'logo_url'                => '',
            'logo_light_url'          => '',
            'logo_dark_url'           => '',
            'favicon_url'             => '',
            'primary_color'           => '#007bff',
            'secondary_color'         => '#6c757d',
            'accent_color'            => '#28a745',
            'email'                   => get_option( 'admin_email' ),
            'phone'                   => '',
            'website'                 => home_url(),
            'copyright_text'          => '© ' . current_time( 'Y' ) . ' AutoPuzzle. All rights reserved.',
            'copyright_text_persian'  => '© ' . current_time( 'Y' ) . ' اتوپازل - تمام حقوق محفوظ است',
            'support_email'           => get_option( 'admin_email' ),
            'support_phone'           => '',
            'enable_footer_branding'  => true,
            'enable_admin_branding'   => true,
        ];
    }
    
    /**
     * Get a specific branding setting
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get( $key, $default = null ) {
        $settings = self::get_all_settings();
        
        if ( isset( $settings[ $key ] ) ) {
            return $settings[ $key ];
        }
        
        return $default;
    }
    
    /**
     * Get brand name (English)
     * 
     * @return string
     */
    public static function get_brand_name() {
        return self::get( 'brand_name', 'AutoPuzzle' );
    }
    
    /**
     * Get brand name (Persian)
     * 
     * @return string
     */
    public static function get_brand_name_persian() {
        return self::get( 'brand_name_persian', 'اتوپازل' );
    }
    
    /**
     * Get brand name based on locale
     * 
     * @return string
     */
    public static function get_brand_name_localized() {
        $locale = get_locale();
        return ( strpos( $locale, 'fa' ) === 0 || strpos( $locale, 'ar' ) === 0 ) 
            ? self::get_brand_name_persian()
            : self::get_brand_name();
    }
    
    /**
     * Get brand tagline
     * 
     * @return string
     */
    public static function get_tagline() {
        return self::get( 'brand_tagline', 'Car Inquiry System' );
    }
    
    /**
     * Get brand tagline (Persian)
     * 
     * @return string
     */
    public static function get_tagline_persian() {
        return self::get( 'brand_tagline_persian', 'سیستم استعلام خودرو' );
    }
    
    /**
     * Get company name
     * 
     * @return string
     */
    public static function get_company_name() {
        return self::get( 'company_name', 'AutoPuzzle Company' );
    }
    
    /**
     * Get company name (Persian)
     * 
     * @return string
     */
    public static function get_company_name_persian() {
        return self::get( 'company_name_persian', 'شرکت اتوپازل' );
    }
    
    /**
     * Get logo URL
     * 
     * @param string $type 'main', 'light', or 'dark'
     * @return string
     */
    public static function get_logo( $type = 'main' ) {
        $main = self::get( 'logo_url', '' );

        switch ( $type ) {
            case 'light':
                $light = self::get( 'logo_light_url', '' );
                return ! empty( $light ) ? $light : $main;
            case 'dark':
                $dark = self::get( 'logo_dark_url', '' );
                return ! empty( $dark ) ? $dark : $main;
            default:
                return $main;
        }
    }
    
    /**
     * Get favicon URL
     * 
     * @return string
     */
    public static function get_favicon() {
        return self::get( 'favicon_url', '' );
    }
    
    /**
     * Get primary color
     * 
     * @return string
     */
    public static function get_primary_color() {
        return self::get( 'primary_color', '#007bff' );
    }
    
    /**
     * Get secondary color
     * 
     * @return string
     */
    public static function get_secondary_color() {
        return self::get( 'secondary_color', '#6c757d' );
    }
    
    /**
     * Get accent color
     * 
     * @return string
     */
    public static function get_accent_color() {
        return self::get( 'accent_color', '#28a745' );
    }
    
    /**
     * Get copyright text
     * 
     * @return string
     */
    public static function get_copyright() {
        return self::get( 'copyright_text', '© ' . current_time( 'Y' ) . ' AutoPuzzle. All rights reserved.' );
    }
    
    /**
     * Get copyright text (Persian)
     * 
     * @return string
     */
    public static function get_copyright_persian() {
        return self::get( 'copyright_text_persian', '© ' . current_time( 'Y' ) . ' اتوپازل - تمام حقوق محفوظ است' );
    }
    
    /**
     * Get copyright text based on locale
     * 
     * @return string
     */
    public static function get_copyright_localized() {
        $locale = get_locale();
        return ( strpos( $locale, 'fa' ) === 0 || strpos( $locale, 'ar' ) === 0 ) 
            ? self::get_copyright_persian()
            : self::get_copyright();
    }
    
    /**
     * Get contact email
     * 
     * @return string
     */
    public static function get_email() {
        return self::get( 'email', get_option( 'admin_email' ) );
    }
    
    /**
     * Get contact phone
     * 
     * @return string
     */
    public static function get_phone() {
        return self::get( 'phone', '' );
    }
    
    /**
     * Get website URL
     * 
     * @return string
     */
    public static function get_website() {
        return self::get( 'website', home_url() );
    }
    
    /**
     * Get support email
     * 
     * @return string
     */
    public static function get_support_email() {
        return self::get( 'support_email', get_option( 'admin_email' ) );
    }
    
    /**
     * Get support phone
     * 
     * @return string
     */
    public static function get_support_phone() {
        return self::get( 'support_phone', '' );
    }
    
    /**
     * Check if footer branding is enabled
     * 
     * @return bool
     */
    public static function is_footer_branding_enabled() {
        return (bool) self::get( 'enable_footer_branding', true );
    }
    
    /**
     * Check if admin branding is enabled
     * 
     * @return bool
     */
    public static function is_admin_branding_enabled() {
        return (bool) self::get( 'enable_admin_branding', true );
    }
    
    /**
     * Update branding settings
     * 
     * @param array $settings
     * @return bool
     */
    public static function update_settings( $settings ) {
        // Merge with existing settings
        $current = self::get_all_settings();
        $updated = wp_parse_args( $settings, $current );

        // No changes: treat as success to avoid false negatives on update_option()
        if ( $updated === $current ) {
            return true;
        }

        // Save to database
        $result = update_option( self::OPTION_KEY, $updated );

        // Clear cache
        self::clear_cache();
        
        return (bool) $result;
    }
    
    /**
     * Clear branding cache
     * 
     * @return void
     */
    public static function clear_cache() {
        self::$cache = [];
    }
    
    /**
     * Reset to default settings
     * 
     * @return bool
     */
    public static function reset_to_defaults() {
        self::clear_cache();
        return delete_option( self::OPTION_KEY );
    }
    
    /**
     * Get branding configuration array for dashboard white-label
     * 
     * @return array
     */
    public static function get_branding_config() {
        return [
            'system_name'           => self::get_brand_name_localized(),
            'company_name'          => self::get_company_name(),
            'company_name_persian'  => self::get_company_name_persian(),
            'company_website'       => self::get_website(),
            'support_email'         => self::get_support_email(),
            'support_phone'         => self::get_support_phone(),
            'logo_url'              => self::get_logo( 'main' ),
            'logo_light_url'        => self::get_logo( 'light' ),
            'logo_dark_url'         => self::get_logo( 'dark' ),
            'favicon_url'           => self::get_favicon(),
            'primary_color'         => self::get_primary_color(),
            'secondary_color'       => self::get_secondary_color(),
            'accent_color'          => self::get_accent_color(),
        ];
    }
    
    /**
     * Initialize default settings if not exists
     * 
     * @return void
     */
    public static function init() {
        if ( ! get_option( self::OPTION_KEY ) ) {
            add_option( self::OPTION_KEY, self::get_defaults() );
        }
    }
}
