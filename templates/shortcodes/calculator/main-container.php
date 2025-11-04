<?php
/**
 * Template for the main loan calculator container, rendered by the [loan_calculator] shortcode.
 *
 * This template includes the tab navigation for "Installment" and "Cash" purchase options,
 * and the content panels for each.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/Calculator
 * @author  Gemini
 * @version 1.0.0
 *
 * @var WC_Product $product           The current WooCommerce product object.
 * @var int        $cash_price        The regular price of the product.
 * @var int        $installment_price The installment price of the product.
 * @var int        $min_down_payment  The minimum required down payment.
 * @var int        $max_down_payment  The maximum allowed down payment.
 * @var array      $car_colors        An array of available car colors.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="maneli-calculator-container">
    <div class="calculator-tabs">
        <a href="#" class="tab-link active" data-tab="installment-tab"><?php esc_html_e('Installment Sale', 'maneli-car-inquiry'); ?></a>
        <a href="#" class="tab-link" data-tab="cash-tab"><?php esc_html_e('Cash Sale', 'maneli-car-inquiry'); ?></a>
    </div>
    
    <div class="tabs-content-wrapper">

        <div id="cash-tab" class="tab-content<?php echo (isset($cash_unavailable) && $cash_unavailable) ? ' unavailable-tab' : ''; ?>">
            <?php if (isset($cash_unavailable) && $cash_unavailable): ?>
                <?php 
                $options = get_option('maneli_inquiry_all_options', []);
                // Check if both are unavailable (status unavailable) or just cash
                if (isset($is_unavailable) && $is_unavailable) {
                    $unavailable_message = isset($options['unavailable_product_message']) && !empty($options['unavailable_product_message']) 
                        ? $options['unavailable_product_message'] 
                        : esc_html__('This product is currently unavailable for purchase.', 'maneli-car-inquiry');
                } else {
                    $unavailable_message = isset($options['unavailable_cash_message']) && !empty($options['unavailable_cash_message']) 
                        ? $options['unavailable_cash_message'] 
                        : esc_html__('This product is currently unavailable for cash purchase.', 'maneli-car-inquiry');
                }
                ?>
                <div class="unavailable-overlay-message">
                    <div class="unavailable-message-content">
                        <i class="la la-exclamation-circle"></i>
                        <p><?php echo esc_html($unavailable_message); ?></p>
                    </div>
                </div>
            <?php endif; ?>
             <form class="loan-calculator-form cash-request-form<?php echo (isset($cash_unavailable) && $cash_unavailable) ? ' unavailable-form' : ''; ?>" 
                   method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
                   data-is-unavailable="<?php echo (isset($cash_unavailable) && $cash_unavailable) ? 'true' : 'false'; ?>">
                <input type="hidden" name="action" value="maneli_submit_cash_inquiry">
                <input type="hidden" name="product_id" value="<?php echo esc_attr($product->get_id()); ?>">
                <?php wp_nonce_field('maneli_cash_inquiry_nonce'); ?>

                <h2 class="loan-title"><?php esc_html_e('Cash Purchase Request', 'maneli-car-inquiry'); ?></h2>
                
                <?php if (is_user_logged_in()): 
                    $current_user = wp_get_current_user();
                ?>
                <div class="loan-section">
                    <div class="form-grid">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="cash_first_name"><?php esc_html_e('First Name:', 'maneli-car-inquiry'); ?></label>
                                <input type="text" id="cash_first_name" name="cash_first_name" value="<?php echo esc_attr($current_user->first_name); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="cash_last_name"><?php esc_html_e('Last Name:', 'maneli-car-inquiry'); ?></label>
                                <input type="text" id="cash_last_name" name="cash_last_name" value="<?php echo esc_attr($current_user->last_name); ?>" required>
                            </div>
                        </div>
                        <div class="form-row">
                             <div class="form-group">
                                <label for="cash_mobile_number"><?php esc_html_e('Mobile Number:', 'maneli-car-inquiry'); ?></label>
                                <input type="tel" id="cash_mobile_number" name="cash_mobile_number" value="<?php echo esc_attr(get_user_meta($current_user->ID, 'mobile_number', true)); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="cash_car_color"><?php esc_html_e('Car Color:', 'maneli-car-inquiry'); ?></label>
                                <?php if (!empty($car_colors)): ?>
                                    <select id="cash_car_color" name="cash_car_color" required>
                                         <option value=""><?php esc_html_e('-- Select a color --', 'maneli-car-inquiry'); ?></option>
                                        <?php foreach ($car_colors as $color): ?>
                                            <option value="<?php echo esc_attr($color); ?>"><?php echo esc_html($color); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <input type="text" id="cash_car_color" name="cash_car_color" placeholder="<?php esc_attr_e('Enter your desired color', 'maneli-car-inquiry'); ?>" required>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div style="text-align: center; font-size: 14px; color: #777; padding: 10px 0; border-top: 1px solid #eee; border-bottom: 1px solid #eee; margin-bottom: 20px;">
                    <?php esc_html_e('The listed price is approximate. Due to market fluctuations, the final price will be determined upon down payment.', 'maneli-car-inquiry'); ?>
                </div>
                <div class="loan-section result-section">
                    <strong><?php esc_html_e('Approximate Price:', 'maneli-car-inquiry'); ?></strong>
                    <?php if (isset($cash_unavailable) && $cash_unavailable): ?>
                        <span class="unavailable-text"><?php esc_html_e('ناموجود', 'maneli-car-inquiry'); ?></span>
                    <?php elseif (isset($can_see_prices) && !$can_see_prices): ?>
                        <span class="price-hidden"><?php esc_html_e('-', 'maneli-car-inquiry'); ?></span>
                    <?php else: ?>
                        <span id="cashPriceAmount"><?php echo esc_html(number_format_i18n($cash_price)); ?></span>
                        <span><?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></span>
                    <?php endif; ?>
                </div>
                <div class="loan-section loan-action-wrapper">
                    <button type="submit" class="loan-action-btn"><?php esc_html_e('Submit Price Inquiry Request', 'maneli-car-inquiry'); ?></button>
                </div>
                <?php else: // User is not logged in ?>
                    <div class="loan-section" style="text-align: center;">
                        <p><?php esc_html_e('To submit a cash purchase request, please log in to your account first.', 'maneli-car-inquiry'); ?></p>
                        <a href="<?php echo esc_url(home_url('/dashboard/')); ?>" class="loan-action-btn"><?php esc_html_e('Login and Submit Request', 'maneli-car-inquiry'); ?></a>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <div id="installment-tab" class="tab-content active<?php echo (isset($installment_unavailable) && $installment_unavailable) ? ' unavailable-tab' : ''; ?>">
            <?php if (isset($installment_unavailable) && $installment_unavailable): ?>
                <?php 
                $options = get_option('maneli_inquiry_all_options', []);
                // Check if both are unavailable (status unavailable) or just installment
                if (isset($is_unavailable) && $is_unavailable) {
                    $unavailable_message = isset($options['unavailable_product_message']) && !empty($options['unavailable_product_message']) 
                        ? $options['unavailable_product_message'] 
                        : esc_html__('This product is currently unavailable for purchase.', 'maneli-car-inquiry');
                } else {
                    $unavailable_message = isset($options['unavailable_installment_message']) && !empty($options['unavailable_installment_message']) 
                        ? $options['unavailable_installment_message'] 
                        : esc_html__('This product is currently unavailable for installment purchase.', 'maneli-car-inquiry');
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
                <?php wp_nonce_field('maneli_ajax_nonce'); ?>
                <div id="loan-calculator" 
                     data-price="<?php echo esc_attr($installment_price); ?>" 
                     data-min-down="<?php echo esc_attr($min_down_payment ? $min_down_payment : 0); ?>" 
                     data-max-down="<?php echo esc_attr($max_down_payment ? $max_down_payment : ($installment_price * 0.8)); ?>"
                     data-can-see-prices="<?php echo (isset($can_see_prices) && $can_see_prices) ? 'true' : 'false'; ?>"
                     data-is-unavailable="<?php echo (isset($installment_unavailable) && $installment_unavailable) ? 'true' : 'false'; ?>">
                    <h2 class="loan-title"><?php esc_html_e('Budgeting and Installment Calculation', 'maneli-car-inquiry'); ?></h2>
                    <div class="loan-section">
                        <div class="loan-row">
                            <label class="loan-label" for="downPaymentInput"><?php esc_html_e('Down Payment Amount:', 'maneli-car-inquiry'); ?></label>
                            <input type="text" id="downPaymentInput" step="1000000">
                        </div>
                        <input type="range" id="downPaymentSlider" step="1000000">
                        <div class="loan-note">
                            <span><?php esc_html_e('Minimum Down Payment:', 'maneli-car-inquiry'); ?></span>
                            <span>
                                <?php if (isset($installment_unavailable) && $installment_unavailable): ?>
                                    <span class="unavailable-text"><?php esc_html_e('ناموجود', 'maneli-car-inquiry'); ?></span>
                                <?php elseif (isset($can_see_prices) && !$can_see_prices): ?>
                                    <span class="price-hidden">-</span>
                                    <span id="minDownDisplay" style="display:none;"><?php echo esc_html(number_format_i18n($min_down_payment ? $min_down_payment : 0)); ?></span>
                                <?php else: ?>
                                    <span id="minDownDisplay"><?php echo esc_html(number_format_i18n($min_down_payment ? $min_down_payment : 0)); ?></span> <?php esc_html_e('Toman', 'maneli-car-inquiry'); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                    <div class="loan-section">
                        <h4 class="loan-subtitle"><?php esc_html_e('Required Conditions', 'maneli-car-inquiry'); ?></h4>
                        <ul class="loan-requirements">
                            <li><?php esc_html_e('1. ID Card - National ID Card', 'maneli-car-inquiry'); ?></li>
                            <li><?php esc_html_e('2. Chequebook', 'maneli-car-inquiry'); ?></li>
                            <li><?php esc_html_e('3. Last 3-month bank statement (cheque owner)', 'maneli-car-inquiry'); ?></li>
                            <li><?php esc_html_e('4. Payslip or business license (applicant and cheque owner)', 'maneli-car-inquiry'); ?></li>
                        </ul>
                    </div>
                    <div class="loan-section">
                        <label class="loan-label"><?php esc_html_e('Repayment Period:', 'maneli-car-inquiry'); ?></label>
                        <div class="loan-buttons">
                            <button type="button" class="term-btn active" data-months="12"><?php esc_html_e('12 Months', 'maneli-car-inquiry'); ?></button>
                            <button type="button" class="term-btn" data-months="18"><?php esc_html_e('18 Months', 'maneli-car-inquiry'); ?></button>
                            <button type="button" class="term-btn" data-months="24"><?php esc_html_e('24 Months', 'maneli-car-inquiry'); ?></button>
                            <button type="button" class="term-btn" data-months="36"><?php esc_html_e('36 Months', 'maneli-car-inquiry'); ?></button>
                        </div>
                    </div>
                    <div class="loan-section result-section">
                        <strong><?php esc_html_e('Approximate Installment Amount:', 'maneli-car-inquiry'); ?></strong>
                        <?php if (isset($installment_unavailable) && $installment_unavailable): ?>
                            <span class="unavailable-text"><?php esc_html_e('ناموجود', 'maneli-car-inquiry'); ?></span>
                            <span id="installmentAmount" style="display:none;">0</span>
                        <?php elseif (isset($can_see_prices) && !$can_see_prices): ?>
                            <span class="price-hidden">-</span>
                            <span id="installmentAmount" style="display:none;">0</span>
                        <?php else: ?>
                            <span id="installmentAmount">0</span>
                            <span><?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="loan-section loan-action-wrapper">
                        <?php if (is_user_logged_in()): ?>
                            <button type="button" class="loan-action-btn"><?php esc_html_e('Bank Credit Check for Car Purchase', 'maneli-car-inquiry'); ?></button>
                        <?php else: ?>
                            <a href="<?php echo esc_url(home_url('/dashboard/')); ?>" class="loan-action-btn"><?php esc_html_e('Log in to Start Inquiry', 'maneli-car-inquiry'); ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.unavailable-text {
    color: #ef4444;
    font-weight: 600;
}
.price-hidden {
    color: #9ca3af;
}
</style>