<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main handler for loading all inquiry-related shortcode classes.
 * This class acts as a central loader and delegates the shortcode rendering to other specialized classes.
 */
class Maneli_Inquiry_Shortcodes {

    public function __construct() {
        $this->load_shortcode_classes();
    }

    private function load_shortcode_classes() {
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/shortcodes/class-loan-calculator-shortcode.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/shortcodes/class-inquiry-form-shortcode.php';
        require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/shortcodes/class-inquiry-lists-shortcode.php';

        // Initialize each shortcode class
        new Maneli_Loan_Calculator_Shortcode();
        new Maneli_Inquiry_Form_Shortcode();
        new Maneli_Inquiry_Lists_Shortcode();
    }
}