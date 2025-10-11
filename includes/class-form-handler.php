<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main handler for loading all form-related classes.
 * This class acts as a central loader and delegates the form handling to other specialized classes.
 */
class Maneli_Form_Handler {

    public function __construct() {
        $this->load_handlers();
    }

    private function load_handlers() {
        // Path definitions
        $public_path = MANELI_INQUIRY_PLUGIN_PATH . 'includes/public/';
        $admin_path = MANELI_INQUIRY_PLUGIN_PATH . 'includes/admin/';

        // Require files
        require_once $public_path . 'class-installment-inquiry-handler.php';
        require_once $public_path . 'class-cash-inquiry-handler.php';
        require_once $public_path . 'class-payment-handler.php';
        require_once $admin_path . 'class-admin-actions-handler.php';
        require_once $admin_path . 'class-ajax-handler.php';

        // Initialize handlers
        new Maneli_Installment_Inquiry_Handler();
        new Maneli_Cash_Inquiry_Handler();
        new Maneli_Payment_Handler();
        new Maneli_Admin_Actions_Handler();
        new Maneli_Ajax_Handler();
    }
}