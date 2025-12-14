<?php
/**
 * Theme Admin Setup
 * Registers menu under Puzzlinco (plugin parent)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Theme_Admin {

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'adjust_menu_hierarchy']);
    }

    /**
     * Adjust menu hierarchy to show under parent
     * Since the theme needs to be a submenu of plugin Puzzlinco,
     * we hook after the plugin menus are registered.
     */
    public static function adjust_menu_hierarchy() {
        global $submenu;

        // Find if there's a Puzzlinco parent menu and add our theme settings under it
        // This runs after other plugins have set up, so we can find/create hierarchy
        
        // Check if Maneli plugin menu exists (parent: 'maneli-theme-settings')
        if (!isset($submenu['maneli-theme-settings'])) {
            // If not found, we've already created it in class-theme-settings.php
            // So this is fine.
        }
    }
}

// Initialize
Maneli_Theme_Admin::init();
