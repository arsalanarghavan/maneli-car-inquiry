<?php
/**
 * Template for the unavailable product overlay.
 *
 * This template is displayed over a blurred, disabled calculator when a product's
 * status is set to 'unavailable'.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/Calculator
 * @author  Gemini
 * @version 1.0.0
 *
 * @var string $message The message to display to the user.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="maneli-calculator-container unavailable">
    <div class="unavailable-overlay">
        <p><?php echo esc_html($message); ?></p>
    </div>
    
    <div class="loan-calculator-form" style="filter: blur(5px); pointer-events: none; user-select: none;">
        <h2 class="loan-title"><?php esc_html_e('Budgeting and Installment Calculation', 'maneli-car-inquiry'); ?></h2>
        <div class="loan-section">
            <div class="loan-row">
                <label class="loan-label"><?php esc_html_e('Down Payment Amount:', 'maneli-car-inquiry'); ?></label>
                <input type="text" id="downPaymentInput" disabled>
            </div>
            <input type="range" id="downPaymentSlider" disabled>
            <div class="loan-note">
                <span><?php esc_html_e('Minimum Down Payment:', 'maneli-car-inquiry'); ?></span>
                <span>- <?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></span>
            </div>
        </div>
        <div class="loan-section">
            <label class="loan-label"><?php esc_html_e('Repayment Period:', 'maneli-car-inquiry'); ?></label>
            <div class="loan-buttons">
                <button type="button" class="term-btn" disabled><?php esc_html_e('12 Months', 'maneli-car-inquiry'); ?></button>
                <button type="button" class="term-btn" disabled><?php esc_html_e('24 Months', 'maneli-car-inquiry'); ?></button>
            </div>
        </div>
        <div class="loan-section result-section">
            <strong><?php esc_html_e('Approximate Installment Amount:', 'maneli-car-inquiry'); ?></strong>
            <span id="installmentAmount">0</span>
            <span><?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></span>
        </div>
        <div class="loan-section loan-action-wrapper">
             <button type="button" class="loan-action-btn" disabled><?php esc_html_e('Bank Credit Check for Car Purchase', 'maneli-car-inquiry'); ?></button>
        </div>
    </div>
</div>