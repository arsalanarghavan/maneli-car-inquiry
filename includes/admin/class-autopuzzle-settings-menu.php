<?php
/**
 * AutoPuzzle Settings Menu Handler
 * 
 * Registers and manages admin settings menu pages for AutoPuzzle plugin
 * 
 * @package AutoPuzzle
 * @subpackage Admin
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Autopuzzle_Settings_Menu {
    
    /**
     * Initialize the settings menu
     */
    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'register_settings_menu' ] );

        // Ensure white-label hooks (AJAX, uploads) are registered
        if ( class_exists( 'Autopuzzle_White_Label_Settings' ) ) {
            new Autopuzzle_White_Label_Settings();
        }

        // Handle reset-to-defaults action
        add_action( 'admin_action_autopuzzle_reset_branding', [ __CLASS__, 'handle_reset_branding' ] );
    }
    
    /**
     * Register the settings menu page
     * 
     * @return void
     */
    public static function register_settings_menu() {
        // Only show for users with manage_options capability
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        
        // Main Settings Menu
        add_menu_page(
            esc_html__( 'AutoPuzzle', 'autopuzzle' ),
            esc_html__( 'AutoPuzzle', 'autopuzzle' ),
            'manage_options',
            'autopuzzle-settings',
            [ __CLASS__, 'render_main_settings' ],
            'dashicons-settings',
            26
        );
        
        // White Label Settings Sub-Menu
        add_submenu_page(
            'autopuzzle-settings',
            esc_html__( 'White Label Settings', 'autopuzzle' ),
            esc_html__( 'White Label', 'autopuzzle' ),
            'manage_options',
            'autopuzzle-white-label',
            [ __CLASS__, 'render_white_label_page' ]
        );
    }
    
    /**
     * Render main settings page
     * 
     * @return void
     */
    public static function render_main_settings() {
        // Redirect to white label page
        wp_safe_remote_get( admin_url( 'admin.php?page=autopuzzle-white-label' ) );
        wp_redirect( admin_url( 'admin.php?page=autopuzzle-white-label' ) );
        exit;
    }
    
    /**
     * Render white label settings page
     * 
     * @return void
     */
    public static function render_white_label_page() {
        ?>
        <div class="wrap">
            <?php Autopuzzle_White_Label_Settings::render(); ?>
        </div>
        <?php
    }

    /**
     * Handle reset branding action with nonce and capability checks
     */
    public static function handle_reset_branding() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to perform this action.', 'autopuzzle' ) );
        }

        $nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

        if ( ! wp_verify_nonce( $nonce, 'autopuzzle_reset_branding_nonce' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'autopuzzle' ) );
        }

        if ( class_exists( 'Autopuzzle_Branding_Helper' ) ) {
            Autopuzzle_Branding_Helper::reset_to_defaults();
            Autopuzzle_Branding_Helper::init();
        }

        wp_safe_redirect( admin_url( 'admin.php?page=autopuzzle-white-label&reset=1' ) );
        exit;
    }
}

// Initialize on plugins_loaded
add_action( 'plugins_loaded', function() {
    Autopuzzle_Settings_Menu::init();
}, 15 );
