<?php
/**
 * Handles the [loan_calculator] shortcode, which displays the installment calculator
 * and the cash purchase request form on the single product page.
 *
 * @package Maneli_Car_Inquiry/Includes/Shortcodes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Loan_Calculator_Shortcode {

    public function __construct() {
        add_shortcode('loan_calculator', [$this, 'render_shortcode']);
    }

    /**
     * Main render method for the [loan_calculator] shortcode.
     * It validates the context and delegates rendering to template files.
     *
     * @return string The HTML output for the shortcode.
     */
    public function render_shortcode() {
        // Ensure this shortcode only runs on a single product page.
        if (!function_exists('is_product') || !is_product() || !function_exists('WC')) {
            return '';
        }
        global $product;
        if (!$product instanceof WC_Product) {
            return '';
        }

        ob_start();

        // Display a success message if a cash inquiry was just sent.
        if (isset($_GET['cash_inquiry_sent']) && $_GET['cash_inquiry_sent'] === 'true') {
            maneli_get_template_part('public/cash-inquiry-success-message');
        }

        $car_status = get_post_meta($product->get_id(), '_maneli_car_status', true);

        // If the car is marked as 'unavailable', show a specific message and a disabled calculator.
        if ($car_status === 'unavailable') {
            $options = get_option('maneli_inquiry_all_options', []);
            $message = $options['unavailable_product_message'] ?? esc_html__('This car is currently unavailable for purchase.', 'maneli-car-inquiry');
            
            maneli_get_template_part('shortcodes/calculator/unavailable-product-overlay', ['message' => $message]);
            
            return ob_get_clean();
        }

        // Prepare data to be passed to the template files.
        $cash_price = (int)$product->get_regular_price();
        $installment_price = (int)get_post_meta($product->get_id(), 'installment_price', true);
        if (empty($installment_price)) {
            $installment_price = $cash_price;
        }

        $car_colors_str = get_post_meta($product->get_id(), '_maneli_car_colors', true);
        $car_colors = !empty($car_colors_str) ? array_map('trim', explode(',', $car_colors_str)) : [];

        $template_args = [
            'product'           => $product,
            'cash_price'        => $cash_price,
            'installment_price' => $installment_price,
            'min_down_payment'  => (int)get_post_meta($product->get_id(), 'min_downpayment', true),
            'max_down_payment'  => (int)($installment_price * 0.8), // 80% of the price
            'car_colors'        => $car_colors,
        ];
        
        // Render the main calculator container with its tabs and content.
        maneli_get_template_part('shortcodes/calculator/main-container', $template_args);

        return ob_get_clean();
    }
}