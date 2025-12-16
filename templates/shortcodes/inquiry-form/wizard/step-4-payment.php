<?php
/**
 * Wizard Step 4: Payment
 * استایل ویزارد - پرداخت
 * 
 * @package Autopuzzle_Car_Inquiry/Templates/Shortcodes/InquiryForm/Wizard
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$options = get_option('autopuzzle_inquiry_all_options', []);
$amount = (int)($options['inquiry_fee'] ?? 0);
$discount_code_exists = !empty($options['discount_code']);
$discount_applied_message = $options['discount_code_text'] ?? esc_html__('100% discount applied successfully.', 'autopuzzle');
$zero_fee_message = $options['zero_fee_message'] ?? esc_html__('The inquiry fee is free for you. Please click the button below to continue.', 'autopuzzle');

$user_id = get_current_user_id();
?>

<div class="border border-bottom-0 rounded-1 mb-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table text-nowrap">
                <thead>
                    <tr class="bg-light">
                        <th scope="col"><?php esc_html_e('Payment Details', 'autopuzzle'); ?></th>
                        <th scope="col"></th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="w-25">
                            <span class="d-block fw-semibold"><?php esc_html_e('Payment Amount', 'autopuzzle'); ?></span>
                        </td>
                        <td class="w-10">:</td>
                        <td class="text-start text-muted">
                            <?php if ($amount > 0): ?>
                                <strong class="text-primary"><?php echo function_exists('autopuzzle_number_format_persian') ? autopuzzle_number_format_persian($amount) : number_format_i18n($amount); ?> <?php esc_html_e('Toman', 'autopuzzle'); ?></strong>
                            <?php else: ?>
                                <strong class="text-success"><?php esc_html_e('Free', 'autopuzzle'); ?></strong>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($amount > 0): ?>
    <div class="alert alert-info mb-4" role="alert">
        <i class="la la-info-circle me-2"></i>
        <?php
        printf(
            esc_html__('To finalize your request and send it to our experts, please pay the inquiry fee of %s Toman.', 'autopuzzle'),
            '<strong>' . (function_exists('autopuzzle_number_format_persian') ? autopuzzle_number_format_persian($amount) : number_format_i18n($amount)) . '</strong>'
        );
        ?>
    </div>
<?php else: ?>
    <div class="alert alert-success mb-4" role="alert">
        <i class="la la-check-circle me-2"></i>
        <?php echo esc_html($zero_fee_message); ?>
    </div>
<?php endif; ?>

<form id="payment-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
    <input type="hidden" name="action" value="autopuzzle_start_payment">
    <?php wp_nonce_field('autopuzzle_payment_nonce'); ?>

    <?php if ($amount > 0 && $discount_code_exists): ?>
        <div class="mb-3">
            <a href="#" id="show-discount-form" class="text-decoration-none">
                <i class="la la-ticket-alt me-1"></i>
                <?php esc_html_e('Do you have a discount code?', 'autopuzzle'); ?>
            </a>
        </div>
        
        <div id="discount-form-wrapper" class="autopuzzle-initially-hidden mb-3">
            <label for="discount_code_input" class="form-label">
                <?php esc_html_e('Enter discount code:', 'autopuzzle'); ?>
            </label>
            <input type="text" name="discount_code_input" id="discount_code_input" class="form-control" placeholder="<?php esc_attr_e('Discount Code', 'autopuzzle'); ?>">
        </div>
    <?php endif; ?>

    <div class="d-flex gap-2">
        <button type="button" class="btn btn-light wizard-btn prev" data-step="3">
            <i class="la la-arrow-right me-1"></i>
            <?php esc_html_e('Back', 'autopuzzle'); ?>
        </button>
        <button type="submit" class="btn btn-primary flex-fill wizard-btn next">
            <i class="la la-lock me-1"></i>
            <?php echo $amount > 0 ? esc_html__('Continue to Payment', 'autopuzzle') : esc_html__('Continue', 'autopuzzle'); ?>
        </button>
    </div>
</form>

<script>
(function() {
    function waitForJQuery() {
        if (typeof jQuery !== "undefined") {
            jQuery(document).ready(function($) {
                $('#show-discount-form').on('click', function(e) {
                    e.preventDefault();
                    $('#discount-form-wrapper').slideToggle();
                });
            });
        } else {
            setTimeout(waitForJQuery, 50);
        }
    }
    waitForJQuery();
})();
</script>

