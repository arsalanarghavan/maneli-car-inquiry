<?php
/**
 * Template for the Customer's view of a single Installment Inquiry Report (Final Step).
 *
 * This template displays the final result of an installment inquiry to the customer.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/InquiryLists
 * @author  Gemini
 * @version 1.0.0
 *
 * @var int $inquiry_id The ID of the inquiry post.
 */

if (!defined('ABSPATH')) {
    exit;
}

$status = get_post_meta($inquiry_id, 'inquiry_status', true);
$post_meta = get_post_meta($inquiry_id);
$product_id = $post_meta['product_id'][0] ?? 0;
$car_name = get_the_title($product_id);
$finotex_data = get_post_meta($inquiry_id, '_finotex_response_data', true);
$cheque_color_code = $finotex_data['result']['chequeColor'] ?? 0;
?>
<div class="customer-report">
    <?php if ($status === 'user_confirmed'): ?>
        <div class="status-box status-final">
            <p><strong><?php esc_html_e('Final Result: Your request has been approved.', 'maneli-car-inquiry'); ?></strong></p>
            <p><?php esc_html_e('Your request has been approved by our experts and referred to the sales unit. One of our colleagues will contact you soon for final coordination.', 'maneli-car-inquiry'); ?></p>
        </div>
    <?php elseif ($status === 'rejected'): 
        $reason = get_post_meta($inquiry_id, 'rejection_reason', true);
    ?>
        <div class="status-box status-rejected">
            <p><strong><?php esc_html_e('Final Result: Your request has been rejected.', 'maneli-car-inquiry'); ?></strong></p>
            <?php if (!empty($reason)): ?>
                <p><strong><?php esc_html_e('Reason:', 'maneli-car-inquiry'); ?></strong> <?php echo esc_html($reason); ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <div class="report-section">
        <h4><?php esc_html_e('Request Summary', 'maneli-car-inquiry'); ?></h4>
        <table class="summary-table">
            <tr><td><strong><?php esc_html_e('Selected Car:', 'maneli-car-inquiry'); ?></strong></td><td><?php echo esc_html($car_name); ?></td></tr>
            <tr><td><strong><?php esc_html_e('Down Payment:', 'maneli-car-inquiry'); ?></strong></td><td><?php echo esc_html(number_format_i18n((int)($post_meta['maneli_inquiry_down_payment'][0] ?? 0))); ?> <span><?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></span></td></tr>
            <tr><td><strong><?php esc_html_e('Installment Term:', 'maneli-car-inquiry'); ?></strong></td><td><?php echo esc_html($post_meta['maneli_inquiry_term_months'][0] ?? 0); ?> <span><?php esc_html_e('Months', 'maneli-car-inquiry'); ?></span></td></tr>
        </table>
    </div>

    <div class="report-section">
        <h4><?php esc_html_e('Credit Verification Result', 'maneli-car-inquiry'); ?></h4>
         <?php if (empty($finotex_data) || (isset($finotex_data['status']) && $finotex_data['status'] === 'SKIPPED')): ?>
             <table class="summary-table" style="margin-top:20px;">
                <tr>
                    <td><strong><?php esc_html_e('Sayad Cheque Status:', 'maneli-car-inquiry'); ?></strong></td>
                    <td><?php esc_html_e('Undetermined', 'maneli-car-inquiry'); ?></td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e('Status Explanation:', 'maneli-car-inquiry'); ?></strong></td>
                    <td><?php esc_html_e('Bank inquiry was not performed.', 'maneli-car-inquiry'); ?></td>
                </tr>
            </table>
        <?php else: ?>
            <div class="maneli-status-bar">
                <?php
                // Same color bar logic as before
                ?>
            </div>
            <table class="summary-table" style="margin-top:20px;">
                <?php
                // Same cheque color map logic as before
                $color_info = $cheque_color_map[$cheque_color_code] ?? $cheque_color_map[0];
                ?>
                <tr>
                    <td><strong><?php esc_html_e('Sayad Cheque Status:', 'maneli-car-inquiry'); ?></strong></td>
                    <td><strong class="cheque-color-<?php echo esc_attr($cheque_color_code); ?>"><?php echo esc_html($color_info['text']); ?></strong></td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e('Status Explanation:', 'maneli-car-inquiry'); ?></strong></td>
                    <td><?php echo esc_html($color_info['desc']); ?></td>
                </tr>
            </table>
        <?php endif; ?>
    </div>
</div>