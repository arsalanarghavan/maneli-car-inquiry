<?php
/**
 * Template for Step 5 of the inquiry form (Final Result).
 *
 * This template is shown to the customer after their inquiry has been processed,
 * displaying the final status (approved or rejected) and a summary of their request.
 * This is also used as the "details" view for customers in the inquiry list.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/InquiryForm
 * @author  Gemini
 * @version 1.0.0
 *
 * @var int $inquiry_id The ID of the inquiry post.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Fetch all necessary data for the report
$status = get_post_meta($inquiry_id, 'inquiry_status', true);
$post_meta = get_post_meta($inquiry_id);
$product_id = $post_meta['product_id'][0] ?? 0;
$car_name = get_the_title($product_id);
$finotex_data = get_post_meta($inquiry_id, '_finotex_response_data', true);
$cheque_color_code = $finotex_data['result']['chequeColor'] ?? 0;
?>

<h3><?php esc_html_e('Step 5: Final Result', 'maneli-car-inquiry'); ?></h3>

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
        <?php else:
            $cheque_color_map = [
                '1' => ['text' => __('White', 'maneli-car-inquiry'), 'desc' => __('No history of bounced cheques.', 'maneli-car-inquiry')],
                '2' => ['text' => __('Yellow', 'maneli-car-inquiry'), 'desc' => __('One bounced cheque or a maximum of 50 million Rials in returned commitments.', 'maneli-car-inquiry')],
                '3' => ['text' => __('Orange', 'maneli-car-inquiry'), 'desc' => __('Two to four bounced cheques or a maximum of 200 million Rials in returned commitments.', 'maneli-car-inquiry')],
                '4' => ['text' => __('Brown', 'maneli-car-inquiry'), 'desc' => __('Five to ten bounced cheques or a maximum of 500 million Rials in returned commitments.', 'maneli-car-inquiry')],
                '5' => ['text' => __('Red', 'maneli-car-inquiry'), 'desc' => __('More than ten bounced cheques or more than 500 million Rials in returned commitments.', 'maneli-car-inquiry')],
                 0  => ['text' => __('Undetermined', 'maneli-car-inquiry'), 'desc' => __('Information was not received from Finotex.', 'maneli-car-inquiry')]
            ];
            $color_info = $cheque_color_map[$cheque_color_code] ?? $cheque_color_map[0];
        ?>
            <div class="maneli-status-bar">
                <?php
                $colors = [ 1 => 'white', 2 => 'yellow', 3 => 'orange', 4 => 'brown', 5 => 'red' ];
                foreach ($colors as $code => $class) {
                    $active_class = ((string)$code === (string)$cheque_color_code) ? 'active' : '';
                    echo "<div class='bar-segment segment-{$class} {$active_class}'><span>" . esc_html($cheque_color_map[$code]['text']) . "</span></div>";
                }
                ?>
            </div>
            <table class="summary-table" style="margin-top:20px;">
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