<?php
/**
 * Main handler for loading all inquiry-related shortcode classes.
 * This class acts as a central loader, delegating the shortcode rendering to specialized classes.
 *
 * @package Maneli_Car_Inquiry/Includes/Shortcodes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Inquiry_Shortcodes {

    public function __construct() {
        $this->load_shortcode_classes();
    }

    /**
     * Includes and initializes all the separate shortcode handler classes
     * related to the inquiry process.
     */
    private function load_shortcode_classes() {
        $shortcode_path = MANELI_INQUIRY_PLUGIN_PATH . 'includes/shortcodes/';

        // Require the files for each specific shortcode handler
        require_once $shortcode_path . 'class-loan-calculator-shortcode.php';
        require_once $shortcode_path . 'class-inquiry-form-shortcode.php';
        require_once $shortcode_path . 'class-inquiry-lists-shortcode.php';

        // Initialize each shortcode class to register its shortcodes
        new Maneli_Loan_Calculator_Shortcode();
        new Maneli_Inquiry_Form_Shortcode();
        new Maneli_Inquiry_Lists_Shortcode();
    }
}