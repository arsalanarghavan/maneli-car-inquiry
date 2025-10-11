<?php
/**
 * Plugin Name:       Maneli Car Inquiry Core
 * Plugin URI:        https://puzzlinco.com
 * Description:       A plugin for car purchase inquiries using Finotex API and managing them in WordPress.
 * Version:           0.1.0
 * Author:            ArsalanArghavan
 * Author URI:        https://arsalanarghavan.ir
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       maneli-car-inquiry
 * Domain Path:       /languages
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit; 
}

/**
 * Define constants for the plugin.
 */
define('MANELI_INQUIRY_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('MANELI_INQUIRY_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * The main file that bootstraps the plugin.
 */
require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-maneli-car-inquiry.php';

/**
 * Begins execution of the plugin.
 *
 * This function is called once the plugin is loaded and is responsible for
 * creating an instance of the main plugin class.
 *
 * @since 1.0.0
 */
function run_maneli_car_inquiry_plugin() {
    $plugin = Maneli_Car_Inquiry_Plugin::instance();
    $plugin->run();
}
run_maneli_car_inquiry_plugin();

/**
 * Register the deactivation hook to clean up plugin data.
 * This calls a static method in the Maneli_Roles_Caps class to remove custom roles.
 */
register_deactivation_hook(__FILE__, function() {
    require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-roles-caps.php';
    Maneli_Roles_Caps::deactivate();
});