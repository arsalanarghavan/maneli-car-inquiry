<?php
/**
 * Template for Step 3 of the inquiry form (Payment).
 *
 * This template displays the payment box, including the amount, a discount code field (if applicable),
 * and the payment submission button.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/InquiryForm
 * @author  Gemini
 * @version 1.0.0
 *
 * @var int $user_id The current user's ID.
 */

if (!defined('ABSPATH')) {
    exit;
}

$options = get_option('maneli_inquiry_all_options', []);
$amount = (int)($options['inquiry_fee'] ?? 0);
$discount_code_exists = !empty($options['discount_code']);
$discount_applied_message = $options['discount_code_text'] ?? esc_html__('100% discount applied successfully.', 'maneli-car-inquiry');
$zero_fee_message = $options['zero_fee_message'] ?? esc_html__('The inquiry fee is waived for you. Please click the button below to continue.', 'maneli-car-inquiry');

// Display a message if a discount was just applied
if (get_user_meta($user_id, 'maneli_discount_applied', true)) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="la la-check-circle me-2"></i>' . esc_html($discount_applied_message) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    delete_user_meta($user_id, 'maneli_discount_applied');
}
?>

<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="la la-dollar-sign me-2"></i>
                    <?php esc_html_e('Step 3: Payment of Inquiry Fee', 'maneli-car-inquiry'); ?>
                </div>
            </div>
            <div class="card-body">
                <?php if ($amount > 0): ?>
                    <div class="alert alert-info" role="alert">
                        <i class="la la-info-circle me-2"></i>
                        <?php
                        printf(
                            esc_html__('To finalize your request and send it to our experts, please pay the inquiry fee of %s Toman.', 'maneli-car-inquiry'),
                            '<strong>' . number_format_i18n($amount) . '</strong>'
                        );
                        ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success" role="alert">
                        <i class="la la-check-circle me-2"></i>
                        <?php echo esc_html($zero_fee_message); ?>
                    </div>
                <?php endif; ?>

                <form id="payment-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                    <input type="hidden" name="action" value="maneli_start_payment">
                    <?php wp_nonce_field('maneli_payment_nonce'); ?>

                    <?php if ($amount > 0 && $discount_code_exists): ?>
                        <div class="mb-3">
                            <a href="#" id="show-discount-form" class="text-decoration-none">
                                <i class="la la-ticket-alt me-1"></i>
                                <?php esc_html_e('Have a discount code?', 'maneli-car-inquiry'); ?>
                            </a>
                        </div>
                        
                        <div id="discount-form-wrapper" style="display:none;" class="mb-3">
                            <label for="discount_code_input" class="form-label">
                                <?php esc_html_e('Enter discount code:', 'maneli-car-inquiry'); ?>
                            </label>
                            <input type="text" name="discount_code_input" id="discount_code_input" class="form-control" placeholder="<?php esc_attr_e('Discount Code', 'maneli-car-inquiry'); ?>">
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary btn-wave w-100">
                        <i class="la la-lock me-1"></i>
                        <?php echo $amount > 0 ? esc_html__('Proceed to Payment', 'maneli-car-inquiry') : esc_html__('Continue', 'maneli-car-inquiry'); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
