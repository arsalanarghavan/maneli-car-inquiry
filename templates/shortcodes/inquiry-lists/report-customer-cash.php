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

$status_classes = [
    'pending'          => 'status-bg-pending',
    'approved'         => 'status-bg-approved',
    'rejected'         => 'status-bg-rejected',
    'awaiting_payment' => 'status-bg-awaiting_payment',
    'completed'        => 'status-bg-approved', // Using 'approved' style for completed
];
$status_class = $status_classes[$status_key] ?? 'status-bg-pending';
?>

<div class="maneli-inquiry-wrapper frontend-customer-report">
    <h2 class="report-main-title">
        <?php esc_html_e('Cash Purchase Request Details', 'maneli-car-inquiry'); ?>
        <small>(#<?php echo esc_html($inquiry_id); ?>)</small>
    </h2>
    
    <div class="status-box status-pending" style="margin-bottom:20px;">
        <p><?php esc_html_e('Please note: Quoted prices are approximate. The final price will be determined based on the market rate at the time of the down payment.', 'maneli-car-inquiry'); ?></p>
    </div>

    <div class="report-status-box <?php echo esc_attr($status_class); ?>">
        <strong><?php esc_html_e('Current Status:', 'maneli-car-inquiry'); ?></strong> <?php echo esc_html($status_label); ?>
    </div>

    <div class="report-box">
        <h3 class="report-box-title"><?php esc_html_e('Request Information', 'maneli-car-inquiry'); ?></h3>
        <div class="report-box-flex">
             <div class="report-car-image">
                <?php if ($product_id && has_post_thumbnail($product_id)): ?>
                    <?php echo get_the_post_thumbnail($product_id, 'medium'); ?>
                <?php endif; ?>
            </div>
            <div class="report-details-table">
                <table class="summary-table">
                    <tbody>
                        <tr><th><?php esc_html_e('Car', 'maneli-car-inquiry'); ?></th><td><?php echo esc_html(get_the_title($product_id)); ?></td></tr>
                        <tr><th><?php esc_html_e('Requested Color', 'maneli-car-inquiry'); ?></th><td><?php echo esc_html(get_post_meta($inquiry_id, 'cash_car_color', true)); ?></td></tr>
                        <?php 
                        $down_payment = get_post_meta($inquiry_id, 'cash_down_payment', true);
                        if (!empty($down_payment)): ?>
                            <tr><th><?php esc_html_e('Down Payment Amount', 'maneli-car-inquiry'); ?></th><td><?php echo number_format_i18n($down_payment) . ' ' . esc_html__('Toman', 'maneli-car-inquiry'); ?></td></tr>
                        <?php endif; ?>
                         <?php 
                        $rejection_reason = get_post_meta($inquiry_id, 'cash_rejection_reason', true);
                        if (!empty($rejection_reason)): ?>
                            <tr><th><?php esc_html_e('Notes', 'maneli-car-inquiry'); ?></th><td><?php echo esc_html($rejection_reason); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if ($status_key === 'awaiting_payment'): ?>
    <div class="admin-actions-box">
        <h3 class="report-box-title"><?php esc_html_e('Action Required', 'maneli-car-inquiry'); ?></h3>
         <p style="font-weight: bold;"><?php esc_html_e('Please note: Prices are approximate and the final price of the car will be determined by the daily rate at the time the down payment is made.', 'maneli-car-inquiry'); ?></p>
         <p><?php esc_html_e('Your down payment has been set. To finalize your purchase, please proceed to the payment gateway via the button below.', 'maneli-car-inquiry'); ?></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="text-align:center;">
            <input type="hidden" name="action" value="maneli_start_cash_payment">
            <input type="hidden" name="inquiry_id" value="<?php echo esc_attr($inquiry_id); ?>">
            <?php wp_nonce_field('maneli_start_cash_payment_nonce'); ?>
            <button type="submit" class="loan-action-btn"><?php esc_html_e('Proceed to Payment Gateway', 'maneli-car-inquiry'); ?></button>
        </form>
    </div>
    <?php endif; ?>

    <div class="report-back-button-wrapper">
        <a href="<?php echo esc_url($back_link); ?>" class="loan-action-btn"><?php esc_html_e('Back to Requests List', 'maneli-car-inquiry'); ?></a>
    </div>
</div>