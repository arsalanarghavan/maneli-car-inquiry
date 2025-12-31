<?php
/**
 * Template for the installment tab content (used in modal)
 *
 * @package Autopuzzle_Car_Inquiry/Templates/Shortcodes/Calculator
 * @var WC_Product $product                The current WooCommerce product object.
 * @var int        $installment_price      The installment price of the product.
 * @var int        $min_down_payment      The minimum required down payment.
 * @var int        $max_down_payment      The maximum allowed down payment.
 * @var string     $car_status             The car status.
 * @var bool       $installment_unavailable Whether installment purchase is unavailable.
 * @var bool       $can_see_prices         Whether prices can be displayed.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="autopuzzle-calculator-container">
    <div class="tabs-content-wrapper">
        <div id="installment-tab" class="tab-content active<?php echo (isset($installment_unavailable) && $installment_unavailable) ? ' unavailable-tab' : ''; ?>">
    <?php if (isset($installment_unavailable) && $installment_unavailable): ?>
        <?php 
        $options = get_option('autopuzzle_inquiry_all_options', []);
        // Check if both are unavailable (status unavailable) or just installment
        if (isset($is_unavailable) && $is_unavailable) {
            $unavailable_message = isset($options['unavailable_product_message']) && !empty($options['unavailable_product_message']) 
                ? $options['unavailable_product_message'] 
                : esc_html__('This product is currently unavailable for purchase.', 'autopuzzle');
        } else {
            $unavailable_message = isset($options['unavailable_installment_message']) && !empty($options['unavailable_installment_message']) 
                ? $options['unavailable_installment_message'] 
                : esc_html__('This product is currently unavailable for installment purchase.', 'autopuzzle');
        }
        ?>
        <div class="unavailable-overlay-message">
            <div class="unavailable-message-content">
                <i class="la la-exclamation-circle"></i>
                <p><?php echo esc_html($unavailable_message); ?></p>
            </div>
        </div>
    <?php endif; ?>
     <form class="loan-calculator-form<?php echo (isset($installment_unavailable) && $installment_unavailable) ? ' unavailable-form' : ''; ?>" method="post">
        <input type="hidden" name="product_id" value="<?php echo esc_attr($product->get_id()); ?>">
        <?php wp_nonce_field('autopuzzle_ajax_nonce'); ?>
        <div id="loan-calculator" 
             data-price="<?php echo esc_attr($installment_price); ?>" 
             data-min-down="<?php echo esc_attr($min_down_payment ? $min_down_payment : 0); ?>" 
             data-max-down="<?php echo esc_attr($max_down_payment ? $max_down_payment : ($installment_price * 0.8)); ?>"
             data-can-see-prices="<?php echo (isset($can_see_prices) && $can_see_prices) ? 'true' : 'false'; ?>"
             data-is-unavailable="<?php echo (isset($installment_unavailable) && $installment_unavailable) ? 'true' : 'false'; ?>">
            <h2 class="loan-title"><?php esc_html_e('Budgeting and Installment Calculation', 'autopuzzle'); ?></h2>
            <div class="loan-section">
                <div class="loan-row">
                    <label class="loan-label" for="downPaymentInput"><?php esc_html_e('Down Payment Amount:', 'autopuzzle'); ?></label>
                    <input type="text" id="downPaymentInput" step="1000000">
                </div>
                <input type="range" id="downPaymentSlider" step="1000000">
                <div class="loan-note">
                    <span><?php esc_html_e('Minimum Down Payment:', 'autopuzzle'); ?></span>
                    <span>
                        <?php if (isset($installment_unavailable) && $installment_unavailable): ?>
                            <span class="unavailable-text"><?php esc_html_e('ناموجود', 'autopuzzle'); ?></span>
                        <?php elseif (isset($can_see_prices) && !$can_see_prices): ?>
                            <span class="price-hidden">-</span>
                            <span id="minDownDisplay" style="display:none;"><?php echo esc_html(number_format_i18n($min_down_payment ? $min_down_payment : 0)); ?></span>
                        <?php else: ?>
                            <span id="minDownDisplay"><?php echo esc_html(number_format_i18n($min_down_payment ? $min_down_payment : 0)); ?></span> <?php esc_html_e('Toman', 'autopuzzle'); ?>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
            <div class="loan-section">
                <h4 class="loan-subtitle"><?php esc_html_e('Required Conditions', 'autopuzzle'); ?></h4>
                <ul class="loan-requirements">
                    <li><?php esc_html_e('1. ID Card - National ID Card', 'autopuzzle'); ?></li>
                    <li><?php esc_html_e('2. Chequebook', 'autopuzzle'); ?></li>
                    <li><?php esc_html_e('3. Last 3-month bank statement (cheque owner)', 'autopuzzle'); ?></li>
                    <li><?php esc_html_e('4. Payslip or business license (applicant and cheque owner)', 'autopuzzle'); ?></li>
                </ul>
            </div>
            <div class="loan-section">
                <label class="loan-label"><?php esc_html_e('Repayment Period:', 'autopuzzle'); ?></label>
                <div class="loan-buttons">
                    <button type="button" class="term-btn active" data-months="12"><?php esc_html_e('12 Months', 'autopuzzle'); ?></button>
                    <button type="button" class="term-btn" data-months="18"><?php esc_html_e('18 Months', 'autopuzzle'); ?></button>
                    <button type="button" class="term-btn" data-months="24"><?php esc_html_e('24 Months', 'autopuzzle'); ?></button>
                    <button type="button" class="term-btn" data-months="36"><?php esc_html_e('36 Months', 'autopuzzle'); ?></button>
                </div>
            </div>
            <div class="loan-section result-section">
                <strong><?php esc_html_e('Approximate Installment Amount:', 'autopuzzle'); ?></strong>
                <?php if (isset($installment_unavailable) && $installment_unavailable): ?>
                    <span class="unavailable-text"><?php esc_html_e('ناموجود', 'autopuzzle'); ?></span>
                    <span id="installmentAmount" style="display:none;">0</span>
                <?php elseif (isset($can_see_prices) && !$can_see_prices): ?>
                    <span class="price-hidden">-</span>
                    <span id="installmentAmount" style="display:none;">0</span>
                <?php else: ?>
                    <span id="installmentAmount">0</span>
                    <span><?php esc_html_e('Toman', 'autopuzzle'); ?></span>
                <?php endif; ?>
            </div>
            <div class="loan-section loan-action-wrapper">
                <?php if (is_user_logged_in()): ?>
                    <button type="button" class="loan-action-btn"><?php esc_html_e('Bank Credit Check for Car Purchase', 'autopuzzle'); ?></button>
                <?php else: ?>
                    <?php
                    // Get current product page URL for redirect after login
                    $current_url = get_permalink($product->get_id());
                    if (empty($current_url)) {
                        $current_url = home_url($_SERVER['REQUEST_URI']);
                    }
                    $login_url = add_query_arg('redirect_to', urlencode($current_url), home_url('/dashboard/login'));
                    ?>
                    <a href="<?php echo esc_url($login_url); ?>" class="loan-action-btn"><?php esc_html_e('Log in to Start Inquiry', 'autopuzzle'); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </form>
        </div>
    </div>
</div>

