<?php
/**
 * Handles the creation and management of custom user roles and capabilities for the plugin.
 *
 * @package Maneli_Car_Inquiry/Includes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Roles_Caps {

    public function __construct() {
        // Use priority 5 to ensure translations are loaded first (load_plugin_textdomain runs at priority 1)
        add_action('init', [$this, 'setup_roles_and_caps'], 5);
        add_action('init', [$this, 'translate_role_names'], 5);
    }

    /**
     * Sets up the custom user roles ('maneli_admin', 'maneli_expert') and assigns capabilities.
     * Also ensures the administrator role has the master capability.
     */
    public function setup_roles_and_caps() {
        // Grant master capability to the administrator role
        $admin_role = get_role('administrator');
        if ($admin_role) {
            // Always ensure administrator has the master capability
            $admin_role->add_cap('manage_maneli_inquiries', true);
        }

        // Define a base set of capabilities for editing products, required by both roles.
        $product_caps = [
            'edit_product'          => true,
            'read_product'          => true,
            'delete_product'        => false,
            'edit_products'         => true,
            'edit_others_products'  => true,
            'publish_products'      => false, // Neither role should publish new products
            'read_private_products' => false,
            'delete_products'       => false,
        ];

        // Define capabilities for 'Maneli Admin'
        $maneli_admin_caps = array_merge($product_caps, [
            'read'                    => true,
            'manage_maneli_inquiries' => true, // Master capability for the plugin
            'edit_posts'              => true, // Allows editing inquiries
            'delete_users'            => true, // ADDED: Allows Maneli Admin to delete users from frontend panel
        ]);

        // Define capabilities for 'Maneli Expert'
        $maneli_expert_caps = array_merge($product_caps, [
            'read'       => true,
            'edit_posts' => true, // Allows editing inquiries they are assigned to
        ]);
        
        // Remove existing roles to ensure a clean slate before adding them again.
        // This is useful during development or if capabilities change.
        remove_role('maneli_admin');
        remove_role('maneli_expert');

        // Add the custom roles with their defined capabilities and translatable names.
        add_role(
            'maneli_admin',
            esc_html__('Maneli Manager', 'maneli-car-inquiry'),
            $maneli_admin_caps
        );
        add_role(
            'maneli_expert',
            esc_html__('Maneli Expert', 'maneli-car-inquiry'),
            $maneli_expert_caps
        );
    }

    /**
     * Removes the custom user roles and capabilities upon plugin deactivation.
     * This is a static method called from the main plugin file's deactivation hook.
     */
    public static function deactivate() {
        // Remove the master capability from the administrator role
        $admin_role = get_role('administrator');
        if ($admin_role && $admin_role->has_cap('manage_maneli_inquiries')) {
            $admin_role->remove_cap('manage_maneli_inquiries');
        }

        // Remove the custom roles
        remove_role('maneli_expert');
        remove_role('maneli_admin');
    }

    /**
     * Translates core WordPress and WooCommerce role names into Persian.
     * This ensures a consistent language experience in the user management area.
     */
    public function translate_role_names() {
        global $wp_roles;
        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }

        $translations = [
            'maneli_admin'  => esc_html__('Maneli Manager', 'maneli-car-inquiry'),
            'maneli_expert' => esc_html__('Maneli Expert', 'maneli-car-inquiry'),
            'customer'      => esc_html__('Customer', 'maneli-car-inquiry'),
            'administrator' => esc_html__('General Manager', 'maneli-car-inquiry'),
        ];
        
        foreach ($translations as $role => $name) {
            if (isset($wp_roles->roles[$role])) {
                $wp_roles->roles[$role]['name'] = $name;
            }
        }
    }
}