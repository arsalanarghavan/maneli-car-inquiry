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
    echo '<div class="status-box status-approved" style="margin-bottom:20px;"><p>' . esc_html($discount_applied_message) . '</p></div>';
    delete_user_meta($user_id, 'maneli_discount_applied');
}
?>

<h3><?php esc_html_e('Step 3: Payment of Inquiry Fee', 'maneli-car-inquiry'); ?></h3>

<div class="payment-box">
    <?php if ($amount > 0): ?>
        <p>
            <?php
            printf(
                esc_html__('To finalize your request and send it to our experts, please pay the inquiry fee of %s Toman.', 'maneli-car-inquiry'),
                '<strong>' . number_format_i18n($amount) . '</strong>'
            );
            ?>
        </p>
    <?php else: ?>
        <p><?php echo esc_html($zero_fee_message); ?></p>
    <?php endif; ?>

    <form id="payment-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
        <input type="hidden" name="action" value="maneli_start_payment">
        <?php wp_nonce_field('maneli_payment_nonce'); ?>

        <?php if ($amount > 0 && $discount_code_exists): ?>
        <p class="discount-toggle" style="cursor: pointer;">
            <a id="show-discount-form"><?php esc_html_e('Have a discount code?', 'maneli-car-inquiry'); ?></a>
        </p>
        <div class="form-group" id="discount-form-wrapper" style="display:none;">
            <label for="discount_code_input"><?php esc_html_e('Enter discount code:', 'maneli-car-inquiry'); ?></label>
            <input type="text" name="discount_code_input" placeholder="<?php esc_attr_e('Discount Code', 'maneli-car-inquiry'); ?>">
        </div>
        <?php endif; ?>

        <button type="submit" class="loan-action-btn">
            <?php echo ($amount > 0) ? esc_html__('Pay and Finalize Request', 'maneli-car-inquiry') : esc_html__('Finalize Request', 'maneli-car-inquiry'); ?>
        </button>
    </form>
</div>