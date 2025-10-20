<?php
/**
 * Template for the Customer's view of a single Cash Inquiry Report.
 *
 * This template displays the details of a cash inquiry and provides action buttons
 * for the customer, such as proceeding to payment for the down payment.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/InquiryLists
 * @author  Gemini
 * @version 1.0.0
 *
 * @var int $inquiry_id The ID of the cash inquiry post.
 */

if (!defined('ABSPATH')) {
    exit;
}

$inquiry = get_post($inquiry_id);
$product_id = get_post_meta($inquiry_id, 'product_id', true);
$status_key = get_post_meta($inquiry_id, 'cash_inquiry_status', true);
$status_label = Maneli_CPT_Handler::get_cash_inquiry_status_label($status_key);
$back_link = remove_query_arg('cash_inquiry_id');
?>

<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="la la-file-alt me-2"></i>
                    <?php esc_html_e('Cash Purchase Request Details', 'maneli-car-inquiry'); ?>
                    <small class="text-muted">(#<?php echo esc_html($inquiry_id); ?>)</small>
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-4" role="alert">
                    <i class="la la-info-circle me-2"></i>
                    <?php esc_html_e('Please note: Quoted prices are approximate. The final price will be determined based on the market rate at the time of the down payment.', 'maneli-car-inquiry'); ?>
                </div>

                <div class="alert alert-<?php echo $status_key === 'completed' ? 'success' : ($status_key === 'approved' ? 'success' : ($status_key === 'rejected' ? 'danger' : ($status_key === 'awaiting_payment' ? 'warning' : 'secondary'))); ?>">
                    <strong><?php esc_html_e('Current Status:', 'maneli-car-inquiry'); ?></strong> <?php echo esc_html($status_label); ?>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <?php if ($product_id && has_post_thumbnail($product_id)): ?>
                            <?php echo get_the_post_thumbnail($product_id, 'medium', ['class' => 'img-fluid rounded']); ?>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-8">
                        <h5 class="mb-3"><?php esc_html_e('Request Information', 'maneli-car-inquiry'); ?></h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <td class="fw-semibold bg-light"><?php esc_html_e('Car', 'maneli-car-inquiry'); ?></td>
                                        <td><?php echo esc_html(get_the_title($product_id)); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold bg-light"><?php esc_html_e('Requested Color', 'maneli-car-inquiry'); ?></td>
                                        <td><?php echo esc_html(get_post_meta($inquiry_id, 'cash_car_color', true)); ?></td>
                                    </tr>
                                    <?php 
                                    $down_payment = get_post_meta($inquiry_id, 'cash_down_payment', true);
                                    if (!empty($down_payment)): ?>
                                        <tr>
                                            <td class="fw-semibold bg-light"><?php esc_html_e('Down Payment Amount', 'maneli-car-inquiry'); ?></td>
                                            <td><span class="badge bg-success-transparent"><?php echo number_format_i18n($down_payment); ?> <?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></span></td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php 
                                    $rejection_reason = get_post_meta($inquiry_id, 'cash_rejection_reason', true);
                                    if (!empty($rejection_reason)): ?>
                                        <tr>
                                            <td class="fw-semibold bg-light"><?php esc_html_e('Notes', 'maneli-car-inquiry'); ?></td>
                                            <td><?php echo esc_html($rejection_reason); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <?php if ($status_key === 'awaiting_payment'): ?>
                    <div class="mt-4 pt-4 border-top">
                        <h5 class="mb-3"><?php esc_html_e('Action Required', 'maneli-car-inquiry'); ?></h5>
                        <div class="alert alert-warning" role="alert">
                            <p class="mb-2"><strong><?php esc_html_e('Please note: Prices are approximate and the final price of the car will be determined by the daily rate at the time the down payment is made.', 'maneli-car-inquiry'); ?></strong></p>
                            <p class="mb-0"><?php esc_html_e('Your down payment has been set. To finalize your purchase, please proceed to the payment gateway via the button below.', 'maneli-car-inquiry'); ?></p>
                        </div>
                        
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="text-center">
                            <input type="hidden" name="action" value="maneli_start_cash_payment">
                            <input type="hidden" name="inquiry_id" value="<?php echo esc_attr($inquiry_id); ?>">
                            <?php wp_nonce_field('maneli_start_cash_payment_nonce'); ?>
                            <button type="submit" class="btn btn-primary btn-wave">
                                <i class="la la-lock me-1"></i>
                                <?php esc_html_e('Proceed to Payment Gateway', 'maneli-car-inquiry'); ?>
                            </button>
                        </form>
                    </div>
                <?php endif; ?>

                <div class="mt-4 text-center">
                    <a href="<?php echo esc_url($back_link); ?>" class="btn btn-light btn-wave">
                        <i class="la la-arrow-left me-1"></i>
                        <?php esc_html_e('Back to Requests List', 'maneli-car-inquiry'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
