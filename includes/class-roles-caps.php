<?php
if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Roles_Caps {

    public function __construct() {
        add_action('init', [$this, 'setup_roles_and_caps']);
        add_action('init', [$this, 'translate_roles']);
    }

    /**
     * Ensures the custom user roles and capabilities for the plugin exist.
     */
    public function setup_roles_and_caps() {
        $admin_role = get_role('administrator');
        if ($admin_role && !$admin_role->has_cap('manage_maneli_inquiries')) {
            $admin_role->add_cap('manage_maneli_inquiries');
        }

        $product_caps = [
            'edit_product'          => true, 'read_product'          => true, 'delete_product'        => false,
            'edit_products'         => true, 'edit_others_products'  => true, 'publish_products'      => false,
            'read_private_products' => false, 'delete_products'       => false,
        ];

        $maneli_admin_caps = array_merge($product_caps, ['read' => true, 'manage_maneli_inquiries' => true, 'edit_posts' => true]);
        remove_role('maneli_admin');
        add_role('maneli_admin', 'مدیریت مانلی', $maneli_admin_caps);

        $maneli_expert_caps = array_merge($product_caps, ['read' => true, 'edit_posts' => true]);
        remove_role('maneli_expert');
        add_role('maneli_expert', 'کارشناس مانلی', $maneli_expert_caps);
    }

    /**
     * Removes the custom user roles on plugin deactivation.
     * This is a static method to be called from the main plugin file.
     */
    public static function deactivate() {
        $admin_role = get_role('administrator');
        if ($admin_role && $admin_role->has_cap('manage_maneli_inquiries')) {
            $admin_role->remove_cap('manage_maneli_inquiries');
        }
        remove_role('maneli_expert');
        remove_role('maneli_admin');
    }

    /**
     * Translates user role names to Persian.
     */
    public function translate_roles() {
        global $wp_roles;
        if ( ! isset( $wp_roles ) ) { $wp_roles = new WP_Roles(); }
        if(isset($wp_roles->roles['maneli_admin'])) { $wp_roles->roles['maneli_admin']['name'] = 'مدیریت مانلی'; }
        if(isset($wp_roles->roles['maneli_expert'])) { $wp_roles->roles['maneli_expert']['name'] = 'کارشناس مانلی'; }
        if(isset($wp_roles->roles['customer'])) { $wp_roles->roles['customer']['name'] = 'مشتری'; }
        if(isset($wp_roles->roles['administrator'])) { $wp_roles->roles['administrator']['name'] = 'مدیر کل'; }
    }
}