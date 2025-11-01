<?php
/**
 * Wizard Step 4: Payment
 * استایل ویزارد - پرداخت
 * 
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/InquiryForm/Wizard
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$options = get_option('maneli_inquiry_all_options', []);
$amount = (int)($options['inquiry_fee'] ?? 0);
$discount_code_exists = !empty($options['discount_code']);
$discount_applied_message = $options['discount_code_text'] ?? esc_html__('100% discount applied successfully.', 'maneli-car-inquiry');
$zero_fee_message = $options['zero_fee_message'] ?? esc_html__('The inquiry fee is free for you. Please click the button below to continue.', 'maneli-car-inquiry');

$user_id = get_current_user_id();
?>

<div class="border border-bottom-0 rounded-1 mb-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table text-nowrap">
                <thead>
                    <tr class="bg-light">
                        <th scope="col"><?php esc_html_e('Payment Details', 'maneli-car-inquiry'); ?></th>
                        <th scope="col"></th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="w-25">
                            <span class="d-block fw-semibold"><?php esc_html_e('Payment Amount', 'maneli-car-inquiry'); ?></span>
                        </td>
                        <td class="w-10">:</td>
                        <td class="text-start text-muted">
                            <?php if ($amount > 0): ?>
                                <strong class="text-primary"><?php echo function_exists('maneli_number_format_persian') ? maneli_number_format_persian($amount) : number_format_i18n($amount); ?> <?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></strong>
                            <?php else: ?>
                                <strong class="text-success"><?php esc_html_e('Free', 'maneli-car-inquiry'); ?></strong>
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
            esc_html__('To finalize your request and send it to our experts, please pay the inquiry fee of %s Toman.', 'maneli-car-inquiry'),
            '<strong>' . (function_exists('maneli_number_format_persian') ? maneli_number_format_persian($amount) : number_format_i18n($amount)) . '</strong>'
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
    <input type="hidden" name="action" value="maneli_start_payment">
    <?php wp_nonce_field('maneli_payment_nonce'); ?>

    <?php if ($amount > 0 && $discount_code_exists): ?>
        <div class="mb-3">
            <a href="#" id="show-discount-form" class="text-decoration-none">
                <i class="la la-ticket-alt me-1"></i>
                <?php esc_html_e('Do you have a discount code?', 'maneli-car-inquiry'); ?>
            </a>
        </div>
        
        <div id="discount-form-wrapper" class="maneli-initially-hidden mb-3">
            <label for="discount_code_input" class="form-label">
                <?php esc_html_e('Enter discount code:', 'maneli-car-inquiry'); ?>
            </label>
            <input type="text" name="discount_code_input" id="discount_code_input" class="form-control" placeholder="<?php esc_attr_e('Discount Code', 'maneli-car-inquiry'); ?>">
        </div>
    <?php endif; ?>

    <div class="d-flex gap-2">
        <button type="button" class="btn btn-light wizard-btn prev" data-step="3">
            <i class="la la-arrow-right me-1"></i>
            <?php esc_html_e('Back', 'maneli-car-inquiry'); ?>
        </button>
        <button type="submit" class="btn btn-primary flex-fill wizard-btn next">
            <i class="la la-lock me-1"></i>
            <?php echo $amount > 0 ? esc_html__('Continue to Payment', 'maneli-car-inquiry') : esc_html__('Continue', 'maneli-car-inquiry'); ?>
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

