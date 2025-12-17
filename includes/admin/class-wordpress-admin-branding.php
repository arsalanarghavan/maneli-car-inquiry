<?php
/**
 * WordPress Admin Panel Branding Handler
 * 
 * Applies AutoPuzzle white-label branding to the WordPress admin panel
 * (dashboard, sidebar, login page, etc.)
 * 
 * @package AutoPuzzle
 * @subpackage Admin
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Autopuzzle_WordPress_Admin_Branding {
    
    /**
     * Initialize the WordPress admin branding
     */
    public static function init() {
        // Apply Peyda font to WordPress admin panel
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_branding_styles' ] );
        add_action( 'login_enqueue_scripts', [ __CLASS__, 'enqueue_login_branding_styles' ] );
        
        // Add logo to sidebar
        add_action( 'admin_head', [ __CLASS__, 'add_sidebar_logo' ] );
        
        // Change admin panel text/title
        add_filter( 'admin_title', [ __CLASS__, 'customize_admin_title' ], 10, 2 );
        
        // Change WordPress logo in admin bar
        add_filter( 'admin_bar_menu', [ __CLASS__, 'customize_admin_bar' ], 999 );
        
        // Customize footer text
        add_filter( 'admin_footer_text', [ __CLASS__, 'customize_footer_text' ] );
    }
    
    /**
     * Enqueue branding styles for WordPress admin panel
     * 
     * @return void
     */
    public static function enqueue_admin_branding_styles() {
        // Enqueue Peyda font CSS for admin panel
        $font_css_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/css/autopuzzle-fonts.css';
        if ( file_exists( $font_css_path ) ) {
            wp_enqueue_style(
                'autopuzzle-admin-peyda-font',
                AUTOPUZZLE_PLUGIN_URL . 'assets/css/autopuzzle-fonts.css',
                [],
                '1.0.0'
            );
        }
        
        // Inline CSS to apply Peyda font and dark theme to WordPress admin
        $admin_branding_css = "
            /* Apply Peyda font to WordPress Admin Panel */
            body, button, input, select, textarea, #wp-admin-bar-root-default, .wp-admin {
                font-family: Peyda, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif !important;
            }
            
            /* Dark theme for sidebar and header */
            #adminmenuback, #adminmenuwrap, #adminmenu, #wpadminbar {
                background-color: #111829 !important;
                background-image: none !important;
            }
            
            /* Header bar styling */
            #wpadminbar {
                background-color: #111829 !important;
                color: #fff !important;
                border-bottom: 1px solid #111829 !important;
            }
            
            #wpadminbar a, #wpadminbar a.ab-item {
                color: rgba(255, 255, 255, 0.8) !important;
            }
            
            #wpadminbar a:hover, #wpadminbar a.ab-item:hover {
                color: #fff !important;
                background-color: rgba(255, 255, 255, 0.1) !important;
            }
            
            /* Sidebar menu items */
            #adminmenu {
                background-color: #111829 !important;
                border-right: 1px solid #111829 !important;
            }
            
            #adminmenu .wp-menu-name, #adminmenu > li > a {
                color: rgba(255, 255, 255, 0.8) !important;
            }
            
            #adminmenu > li > a:hover, #adminmenu > li.hover > a {
                background-color: rgba(255, 255, 255, 0.1) !important;
                color: #fff !important;
            }
            
            #adminmenu .wp-submenu {
                background-color: #0f172a !important;
                border-left: 3px solid #111829 !important;
            }
            
            #adminmenu .wp-submenu a {
                color: rgba(255, 255, 255, 0.7) !important;
            }
            
            #adminmenu .wp-submenu a:hover {
                color: #fff !important;
                background-color: rgba(255, 255, 255, 0.05) !important;
            }
            
            /* Active/Selected menu items */
            #adminmenu li.current a, #adminmenu li.current > a {
                background-color: #141B29 !important;
                color: #fff !important;
                border-left: 4px solid #141B29 !important;
            }
            
            #adminmenu li.wp-has-current-submenu a.wp-has-current-submenu {
                background-color: #141B29 !important;
                color: #fff !important;
            }
            
            #adminmenu .wp-submenu li.current a {
                background-color: #141B29 !important;
                color: #fff !important;
            }
            
            /* Admin menu divider/separator */
            #adminmenu li.wp-menu-separator {
                background-color: #111829 !important;
                border-top: 1px solid #111829 !important;
                height: 1px !important;
            }
            
            /* Admin menu background */
            #adminmenu, #adminmenuback {
                color: rgba(255, 255, 255, 0.8) !important;
                border-right: 1px solid #111829 !important;
            }
            
            /* Admin menu icons */
            #adminmenu .dashicons {
                color: inherit !important;
            }
            
            /* Admin menu and submenu backgrounds */
            #adminmenu .wp-submenu, #adminmenu .wp-submenu-wrap {
                background-color: #0f172a !important;
                border: none !important;
                border-left: 3px solid #111829 !important;
            }
            
            /* Dashboard widgets and page titles */
            .wp-heading-inline, h1, h2, h3, #dashboard-widgets h2, .postbox h2 {
                font-family: Peyda, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif !important;
            }
            
            /* Form elements */
            .form-table th, .form-table td, label {
                font-family: Peyda, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif !important;
            }
            
            /* Buttons and links */
            .button, .button-primary, .button-secondary, a {
                font-family: Peyda, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif !important;
            }
        ";
        
        wp_add_inline_style( 'wp-admin', $admin_branding_css );
    }
    
    /**
     * Add logo to sidebar header
     * 
     * @return void
     */
    public static function add_sidebar_logo() {
        ?>
        <style>
            /* Sidebar logo styling */
            .sidebar-logo-wrapper {
                padding: 15px 10px;
                text-align: center;
                border-bottom: 1px solid #111829;
                background-color: #111829;
            }
            
            .sidebar-logo-wrapper a {
                display: inline-block;
                text-decoration: none;
            }
            
            .sidebar-logo-wrapper img {
                max-width: 120px;
                height: auto;
                display: block;
            }
        </style>
        <script>
            jQuery(document).ready(function($) {
                // Add logo to the top of the admin menu
                var logoHTML = '<div class="sidebar-logo-wrapper">' +
                    '<a href="https://puzzlingco.com" target="_blank" rel="noopener noreferrer">' +
                    '<img src="<?php echo esc_url( AUTOPUZZLE_PLUGIN_URL . 'assets/images/brand-logos/desktop-white.png' ); ?>" alt="AutoPuzzle" />' +
                    '</a>' +
                    '</div>';
                
                // Insert logo before the first menu item
                $('#adminmenu').before(logoHTML);
            });
        </script>
        <?php
    }
    
    /**
     * Enqueue branding styles for WordPress login page
     * 
     * @return void
     */
    public static function enqueue_login_branding_styles() {
        // Enqueue Peyda font CSS for login page
        $font_css_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/css/autopuzzle-fonts.css';
        if ( file_exists( $font_css_path ) ) {
            wp_enqueue_style(
                'autopuzzle-login-peyda-font',
                AUTOPUZZLE_PLUGIN_URL . 'assets/css/autopuzzle-fonts.css',
                [],
                '1.0.0'
            );
        }
        
        // Inline CSS to apply Peyda font to login page
        $login_branding_css = "
            /* Apply Peyda font to WordPress Login Page */
            body, #loginform, .login form, input, button, label {
                font-family: Peyda, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif !important;
            }
            
            /* Login heading and error messages */
            h1, .error, .message {
                font-family: Peyda, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif !important;
            }
        ";
        
        wp_add_inline_style( 'wp-admin', $login_branding_css );
    }
    
    /**
     * Customize WordPress admin title
     * 
     * @param string $admin_title The original admin title.
     * @param string $blog_name   The blog name.
     * @return string
     */
    public static function customize_admin_title( $admin_title, $blog_name ) {
        // Get AutoPuzzle brand name
        if ( class_exists( 'Autopuzzle_Branding_Helper' ) ) {
            $brand_name = Autopuzzle_Branding_Helper::get_brand_name();
            if ( ! empty( $brand_name ) ) {
                // Replace WordPress with brand name
                $admin_title = str_replace( 'WordPress', $brand_name, $admin_title );
            }
        }
        
        return $admin_title;
    }
    
    /**
     * Customize WordPress admin bar
     * 
     * @param WP_Admin_Bar $wp_admin_bar The admin bar instance.
     * @return void
     */
    public static function customize_admin_bar( $wp_admin_bar ) {
        // Get AutoPuzzle branding info
        if ( class_exists( 'Autopuzzle_Branding_Helper' ) ) {
            $brand_name = Autopuzzle_Branding_Helper::get_brand_name();
            $logo_url = Autopuzzle_Branding_Helper::get_logo( 'main' );
            
            // Update WordPress logo node in admin bar
            if ( $wp_admin_bar->get_node( 'wp-logo' ) ) {
                if ( ! empty( $logo_url ) ) {
                    // Add custom logo styling to admin bar
                    $custom_logo_css = "
                        #wpadminbar #wp-admin-bar-wp-logo > .ab-item::before {
                            background-image: url('" . esc_url( $logo_url ) . "') !important;
                            background-size: contain !important;
                            background-repeat: no-repeat !important;
                        }
                    ";
                    wp_add_inline_style( 'wp-admin', $custom_logo_css );
                }
                
                // Update the WordPress logo title with brand name
                if ( ! empty( $brand_name ) ) {
                    $wp_admin_bar->add_node( [
                        'id'    => 'wp-logo',
                        'title' => esc_html( $brand_name ),
                    ] );
                }
            }
        }
        
        return $wp_admin_bar;
    }
    
    /**
     * Customize admin footer text
     * 
     * @return string
     */
    public static function customize_footer_text() {
        if ( class_exists( 'Autopuzzle_Branding_Helper' ) ) {
            $brand_name = Autopuzzle_Branding_Helper::get_brand_name();
            $support_email = Autopuzzle_Branding_Helper::get_support_email();
            
            if ( ! empty( $brand_name ) ) {
                $footer_text = sprintf(
                    /* translators: %s: Brand name, %s: Support email */
                    esc_html__( '%1$s Plugin v1.0 - For support, contact %2$s', 'autopuzzle' ),
                    esc_html( $brand_name ),
                    ! empty( $support_email ) ? '<a href="' . esc_url( 'mailto:' . $support_email ) . '">' . esc_html( $support_email ) . '</a>' : esc_html__( 'Support Team', 'autopuzzle' )
                );
                
                return $footer_text;
            }
        }
        
        return '';
    }
}

// Initialize on plugins_loaded hook
add_action( 'plugins_loaded', function() {
    Autopuzzle_WordPress_Admin_Branding::init();
}, 15 );
