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
}

// Initialize on plugins_loaded
add_action( 'plugins_loaded', function() {
    Autopuzzle_Settings_Menu::init();
}, 15 );
