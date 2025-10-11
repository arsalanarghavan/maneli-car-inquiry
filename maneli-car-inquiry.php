<?php
/**
 * Plugin Name:       Maneli Car Inquiry Core
 * Plugin URI:        https://puzzlinco.com
 * Description:       A plugin for car purchase inquiries using Finotex API and managing them in WordPress.
 * Version:           0.14.58
 * Author:            ArsalanArghavan
 * Author URI:        https://arsalanarghavan.ir
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       maneli-car-inquiry
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('MANELI_INQUIRY_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('MANELI_INQUIRY_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include the main plugin class and run it.
require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-maneli-car-inquiry.php';

// Instantiate the main class to get the plugin running.
new Maneli_Car_Inquiry_Plugin();

// Register deactivation hook for cleanup.
register_deactivation_hook(__FILE__, ['Maneli_Roles_Caps', 'deactivate']);