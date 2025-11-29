<?php
/**
 * Main handler for loading all form-related classes.
 * This class acts as a central loader and delegates form handling to other specialized classes.
 *
 * @package Maneli_Car_Inquiry/Includes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Form_Handler {

    public function __construct() {
        // Check license before loading form handlers (using optimized helper)
        if (!Maneli_Permission_Helpers::is_license_active() && !Maneli_Permission_Helpers::is_demo_mode()) {
            // License not active - don't load form handlers
            return;
        }
        
        $this->load_handlers();
    }

    /**
     * Includes and initializes all the necessary form handler classes.
     */
    private function load_handlers() {
        // Define paths for better readability
        $public_path = MANELI_INQUIRY_PLUGIN_PATH . 'includes/public/';
        $admin_path  = MANELI_INQUIRY_PLUGIN_PATH . 'includes/admin/';

        // Require necessary handler files
        require_once $public_path . 'class-installment-inquiry-handler.php';
        require_once $public_path . 'class-cash-inquiry-handler.php';
        require_once $public_path . 'class-payment-handler.php';
        require_once $admin_path . 'class-admin-actions-handler.php';
        require_once $admin_path . 'class-ajax-handler.php';

        // Initialize each handler class to register its hooks
        new Maneli_Installment_Inquiry_Handler();
        new Maneli_Cash_Inquiry_Handler();
        new Maneli_Payment_Handler();
        new Maneli_Admin_Actions_Handler();
        new Maneli_Ajax_Handler();
    }
}