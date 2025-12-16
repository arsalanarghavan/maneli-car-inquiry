<?php
/**
 * Modal template for installment calculator in dashboard (new-inquiry step 3)
 * This modal opens when user clicks on a car to replace in the confirm car catalog
 * 
 * @package AutoPuzzle/Templates/Dashboard
 * @version 1.0.0
 * 
 * @var int        $product_id        The product ID (passed from JS)
 * @var WC_Product $product           The WooCommerce product object
 * @var int        $installment_price The installment price
 * @var int        $min_down_payment  Minimum down payment
 * @var int        $max_down_payment  Maximum down payment
 * @var bool       $can_see_prices    Whether user can see prices
 * @var bool       $is_unavailable    Whether product is unavailable
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<!-- Installment Calculator Modal -->
<div class="modal fade" id="installmentCalculatorModal" tabindex="-1" aria-labelledby="installmentCalculatorModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 0.75rem; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background: linear-gradient(135deg, #2D89BE 0%, #4a54e5 100%); color: #fff; border: none; padding: 1.25rem 1.5rem;">
                <h5 class="modal-title" id="installmentCalculatorModalLabel" style="margin: 0; font-weight: 600; font-size: 1.125rem;">
                    <i class="la la-calculator me-2"></i>
                    <?php esc_html_e('Calculate Installment', 'autopuzzle'); ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="<?php esc_attr_e('Close', 'autopuzzle'); ?>" style="filter: brightness(0) invert(1);"></button>
            </div>
            <div class="modal-body" style="padding: 1.5rem;">
                <div id="modal-car-info" class="mb-4 p-3" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); border-radius: 0.5rem; border: 1px solid #e2e6f1; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                    <h6 class="mb-2 fw-semibold" id="modal-car-name" style="color: #212b37; font-size: 1rem;"></h6>
                    <div id="modal-car-image" class="text-center mt-2"></div>
                </div>
                
                <div class="autopuzzle-calculator-container" id="modal-calculator-container" style="border: none; box-shadow: none; margin: 0; padding: 0;">
                    <div id="modal-installment-tab" class="tab-content active">
                        <?php 
                        $options = get_option('autopuzzle_inquiry_all_options', []);
                        ?>
                        <form class="loan-calculator-form" method="post" id="modal-calculator-form" style="padding: 0;">
                            <input type="hidden" name="product_id" id="modal-product-id" value="">
                            <div id="modal-loan-calculator" 
                                 data-price="0" 
                                 data-min-down="0" 
                                 data-max-down="0"
                                 data-can-see-prices="true"
                                 data-is-unavailable="false">
                                <h2 class="loan-title" style="font-size: 1rem; margin-bottom: 1.5rem; padding-bottom: 0.75rem;"><?php esc_html_e('Budgeting and Installment Calculation', 'autopuzzle'); ?></h2>
                                <div class="loan-section" style="margin-bottom: 1.5rem;">
                                    <div class="loan-row">
                                        <label class="loan-label" for="modalDownPaymentInput" style="font-size: 0.875rem; margin-bottom: 0.75rem; color: #4d5875;"><?php esc_html_e('Down Payment Amount:', 'autopuzzle'); ?></label>
                                        <input type="text" id="modalDownPaymentInput" step="1000000" style="font-size: 1rem; padding: 0.625rem 0.875rem; border: 2px solid #e2e6f1; border-radius: 0.5rem;">
                                    </div>
                                    <input type="range" id="modalDownPaymentSlider" step="1000000" style="margin: 1.25rem 0;">
                                    <div class="loan-note" style="font-size: 0.8125rem; color: #6e829f; margin-top: 0.5rem;">
                                        <span><?php esc_html_e('Minimum Down Payment:', 'autopuzzle'); ?></span>
                                        <span style="font-weight: 600; color: #2D89BE;">
                                            <span id="modalMinDownDisplay">0</span> <?php esc_html_e('Toman', 'autopuzzle'); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="loan-section" style="margin-bottom: 1.5rem;">
                                    <label class="loan-label" style="font-size: 0.875rem; margin-bottom: 0.75rem; color: #4d5875;"><?php esc_html_e('Repayment Period:', 'autopuzzle'); ?></label>
                                    <div class="loan-buttons" style="gap: 0.625rem;">
                                        <button type="button" class="term-btn active" data-months="12" style="padding: 0.625rem 1rem; border-radius: 0.5rem; font-weight: 500;"><?php esc_html_e('12 Months', 'autopuzzle'); ?></button>
                                        <button type="button" class="term-btn" data-months="18" style="padding: 0.625rem 1rem; border-radius: 0.5rem; font-weight: 500;"><?php esc_html_e('18 Months', 'autopuzzle'); ?></button>
                                        <button type="button" class="term-btn" data-months="24" style="padding: 0.625rem 1rem; border-radius: 0.5rem; font-weight: 500;"><?php esc_html_e('24 Months', 'autopuzzle'); ?></button>
                                        <button type="button" class="term-btn" data-months="36" style="padding: 0.625rem 1rem; border-radius: 0.5rem; font-weight: 500;"><?php esc_html_e('36 Months', 'autopuzzle'); ?></button>
                                    </div>
                                </div>
                                <div class="loan-section result-section" style="background: linear-gradient(135deg, rgba(45, 137, 190, 0.1) 0%, rgba(74, 84, 229, 0.1) 100%); border: 2px solid #2D89BE; border-radius: 0.75rem; padding: 1.5rem; margin-top: 1.5rem;">
                                    <strong style="font-size: 0.9375rem; color: #212b37; display: block; margin-bottom: 0.75rem;"><?php esc_html_e('Approximate Installment Amount:', 'autopuzzle'); ?></strong>
                                    <div style="font-size: 1.75rem; font-weight: 700; color: #2D89BE; line-height: 1.2;">
                                        <span id="modalInstallmentAmount">0</span>
                                        <span style="font-size: 0.875rem; font-weight: 500; margin-right: 0.25rem;"><?php esc_html_e('Toman', 'autopuzzle'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #e2e6f1; padding: 1rem 1.5rem; background: #f8f9fa;">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius: 0.5rem; padding: 0.625rem 1.25rem; font-weight: 500;">
                    <?php esc_html_e('Cancel', 'autopuzzle'); ?>
                </button>
                <button type="button" class="btn btn-primary" id="modalConfirmCarBtn" style="background: linear-gradient(135deg, #2D89BE 0%, #4a54e5 100%); border: none; border-radius: 0.5rem; padding: 0.625rem 1.5rem; font-weight: 500; box-shadow: 0 4px 12px rgba(45, 137, 190, 0.3); transition: all 0.3s ease;">
                    <i class="la la-check me-1"></i>
                    <?php esc_html_e('Confirm and Replace Car', 'autopuzzle'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

