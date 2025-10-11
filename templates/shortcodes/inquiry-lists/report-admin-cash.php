<?php
/**
 * Template for the Admin/Expert view of a single Cash Inquiry Report.
 *
 * This template displays the full details of a cash inquiry and provides action buttons
 * for managing the request (assign, edit, delete, set down payment, etc.).
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/InquiryLists
 * @author  Gemini
 * @version 1.0.1 (Finalized actions and delete button logic)
 *
 * @var int $inquiry_id The ID of the cash inquiry post.
 */

if (!defined('ABSPATH')) {
    exit;
}

$inquiry = get_post($inquiry_id);
$product_id = get_post_meta($inquiry_id, 'product_id', true);
$status = get_post_meta($inquiry_id, 'cash_inquiry_status', true);
$status_label = Maneli_CPT_Handler::get_cash_inquiry_status_label($status);
$expert_name = get_post_meta($inquiry_id, 'assigned_expert_name', true);
$back_link = remove_query_arg('cash_inquiry_id');

$status_classes = [
    'pending'          => 'status-bg-pending',
    'approved'         => 'status-bg-approved',
    'rejected'         => 'status-bg-rejected',
    'awaiting_payment' => 'status-bg-awaiting_payment',
    'completed'        => 'status-bg-approved',
];
$status_class = $status_classes[$status] ?? 'status-bg-pending';
?>

<div class="maneli-inquiry-wrapper frontend-expert-report" id="cash-inquiry-details" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>">
    
    <h2 class="report-main-title">
        <?php esc_html_e('Cash Purchase Request Details', 'maneli-car-inquiry'); ?>
        <small>(#<?php echo esc_html($inquiry_id); ?>)</small>
    </h2>
    
    <div class="report-status-box <?php echo esc_attr($status_class); ?>">
        <strong><?php esc_html_e('Current Status:', 'maneli-car-inquiry'); ?></strong> <?php echo esc_html($status_label); ?>
        <?php if ($expert_name): ?>
            <br><strong><?php esc_html_e('Assigned Expert:', 'maneli-car-inquiry'); ?></strong> <?php echo esc_html($expert_name); ?>
        <?php endif; ?>
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
                        <tr><th><?php esc_html_e('Customer', 'maneli-car-inquiry'); ?></th><td><?php echo esc_html(get_post_meta($inquiry_id, 'cash_first_name', true) . ' ' . get_post_meta($inquiry_id, 'cash_last_name', true)); ?></td></tr>
                        <tr><th><?php esc_html_e('Mobile Number', 'maneli-car-inquiry'); ?>:</th><td><a href="tel:<?php echo esc_attr(get_post_meta($inquiry_id, 'mobile_number', true)); ?>"><?php echo esc_html(get_post_meta($inquiry_id, 'mobile_number', true)); ?></a></td></tr>
                        <tr><th><?php esc_html_e('Car', 'maneli-car-inquiry'); ?></th><td><a href="<?php echo esc_url(get_permalink($product_id)); ?>" target="_blank"><?php echo esc_html(get_the_title($product_id)); ?></a></td></tr>
                        <tr><th><?php esc_html_e('Requested Color', 'maneli-car-inquiry'); ?></th><td><?php echo esc_html(get_post_meta($inquiry_id, 'cash_car_color', true)); ?></td></tr>
                        <?php 
                        $down_payment = get_post_meta($inquiry_id, 'cash_down_payment', true);
                        if (!empty($down_payment)): ?>
                            <tr><th><?php esc_html_e('Down Payment Amount', 'maneli-car-inquiry'); ?></th><td><?php echo number_format_i18n($down_payment) . ' ' . esc_html__('Toman', 'maneli-car-inquiry'); ?></td></tr>
                        <?php endif; ?>
                         <?php 
                        $rejection_reason = get_post_meta($inquiry_id, 'cash_rejection_reason', true);
                        if (!empty($rejection_reason)): ?>
                            <tr><th><?php esc_html_e('Rejection Reason', 'maneli-car-inquiry'); ?></th><td><?php echo esc_html($rejection_reason); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="admin-actions-box">
        <h3 class="report-box-title"><?php esc_html_e('Actions', 'maneli-car-inquiry'); ?></h3>
        <div class="action-button-group" style="justify-content: center;">
            
            <?php if (!$expert_name): ?>
                <button type="button" class="action-btn assign-expert-btn" style="background-color: #17a2b8;" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-inquiry-type="cash"><?php esc_html_e('Assign to Expert', 'maneli-car-inquiry'); ?></button>
            <?php endif; ?>
            
            <button type="button" class="action-btn" id="edit-cash-inquiry-btn" style="background-color: var(--theme-yellow); color: var(--text-dark);"><?php esc_html_e('Edit Information', 'maneli-car-inquiry'); ?></button>
            
            <?php // Note: The Delete button now uses the general JS handler for report pages ?>
            <button type="button" class="action-btn delete-inquiry-report-btn" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-inquiry-type="cash" style="background-color: var(--theme-red);"><?php esc_html_e('Delete Request', 'maneli-car-inquiry'); ?></button>

            <?php if ($status === 'pending' || $status === 'approved'): ?>
                <button type="button" class="action-btn approve" id="set-downpayment-btn"><?php esc_html_e('Set Down Payment', 'maneli-car-inquiry'); ?></button>
                <button type="button" class="action-btn reject" id="reject-cash-inquiry-btn"><?php esc_html_e('Reject Request', 'maneli-car-inquiry'); ?></button>
            <?php endif; ?>
            
            <?php if ($status === 'awaiting_payment'): ?>
                <a href="<?php echo esc_url(add_query_arg(['cash_inquiry_id' => $inquiry_id, 'payment_link' => 'true'], home_url('/dashboard/?endp=inf_menu_4'))); ?>" target="_blank" class="action-btn" style="background-color: var(--theme-cyan);"><?php esc_html_e('View Customer Payment Link', 'maneli-car-inquiry'); ?></a>
            <?php endif; ?>
        </div>
    </div>

    <div class="report-back-button-wrapper">
        <a href="<?php echo esc_url($back_link); ?>" class="loan-action-btn"><?php esc_html_e('Back to List', 'maneli-car-inquiry'); ?></a>
    </div>
</div>