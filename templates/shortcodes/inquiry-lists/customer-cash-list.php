<?php
/**
 * Template for the Customer's view of their Cash Inquiry List.
 *
 * This template displays a table of the current user's cash inquiries with their statuses and links to view details.
 * It also handles payment status feedback messages.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/InquiryLists
 * @author  Gemini
 * @version 1.0.0
 *
 * @var WP_Query $inquiries_query The WP_Query object for the user's cash inquiries.
 * @var string   $current_url     The base URL for generating action links.
 * @var string|null $payment_status Status from a payment gateway redirect ('success', 'failed', 'cancelled').
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="maneli-inquiry-wrapper">
    <h3><?php esc_html_e('Your Cash Purchase Requests', 'maneli-car-inquiry'); ?></h3>

    <?php
    // Display payment status message if redirected from a payment gateway
    if (isset($payment_status)) {
        $reason = isset($_GET['reason']) ? sanitize_text_field(urldecode($_GET['reason'])) : '';
        maneli_get_template_part('shortcodes/inquiry-form/payment-status-message', ['status' => $payment_status, 'reason' => $reason]);
    }
    ?>

    <div class="status-box status-pending" style="margin-bottom:20px;">
        <p><?php esc_html_e('Please note: Quoted prices are approximate. The final price will be determined based on the market rate at the time of the down payment.', 'maneli-car-inquiry'); ?></p>
    </div>

    <?php if (!$inquiries_query->have_posts()) : ?>
        <div class="status-box status-pending">
            <p><?php esc_html_e('You have not submitted any cash purchase requests yet.', 'maneli-car-inquiry'); ?></p>
        </div>
    <?php else : ?>
        <div class="maneli-table-wrapper">
            <table class="shop_table shop_table_responsive my_account_orders">
                <thead>
                    <tr>
                        <th><?php esc_html_e('ID', 'maneli-car-inquiry'); ?></th>
                        <th><?php esc_html_e('Car', 'maneli-car-inquiry'); ?></th>
                        <th><?php esc_html_e('Status', 'maneli-car-inquiry'); ?></th>
                        <th><?php esc_html_e('Date', 'maneli-car-inquiry'); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($inquiries_query->have_posts()) : $inquiries_query->the_post(); ?>
                        <?php
                        $inquiry_id = get_the_ID();
                        $product_id = get_post_meta($inquiry_id, 'product_id', true);
                        $status = get_post_meta($inquiry_id, 'cash_inquiry_status', true);
                        $expert_status = get_post_meta($inquiry_id, 'expert_status', true);
                        // Fix: Remove existing cash_inquiry_id parameter from current_url before adding new one
                        $base_url = remove_query_arg(['cash_inquiry_id', 'paged', 'payment_status', 'reason'], $current_url);
                        $report_url = add_query_arg('cash_inquiry_id', $inquiry_id, $base_url);
                        $expert_status_info = Maneli_Render_Helpers::get_expert_status_info($expert_status);
                        ?>
                        <tr>
                            <td data-title="<?php esc_attr_e('ID', 'maneli-car-inquiry'); ?>">#<?php echo esc_html($inquiry_id); ?></td>
                            <td data-title="<?php esc_attr_e('Car', 'maneli-car-inquiry'); ?>"><?php echo esc_html(get_the_title($product_id)); ?></td>
                            <td data-title="<?php esc_attr_e('Status', 'maneli-car-inquiry'); ?>">
                                <span class="status-indicator status-<?php echo esc_attr($status); ?>"><?php echo esc_html(Maneli_CPT_Handler::get_cash_inquiry_status_label($status)); ?></span>
                                <?php if ($expert_status_info): ?>
                                    <br><span class="expert-status-badge" style="background-color: <?php echo esc_attr($expert_status_info['color']); ?>; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; margin-top: 4px; display: inline-block;"><?php echo esc_html($expert_status_info['label']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td data-title="<?php esc_attr_e('Date', 'maneli-car-inquiry'); ?>"><?php echo esc_html(get_the_date('Y/m/d', $inquiry_id)); ?></td>
                            <td class="woocommerce-orders-table__cell-order-actions">
                                <a href="<?php echo esc_url($report_url); ?>" class="button view"><?php esc_html_e('View Details', 'maneli-car-inquiry'); ?></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="maneli-pagination-wrapper" style="margin-top: 20px; text-align: center;">
            <?php
            echo paginate_links([
                'base'      => $current_url . '%_%',
                'format'    => '?paged=%#%',
                'current'   => max(1, get_query_var('paged')),
                'total'     => $inquiries_query->max_num_pages,
                'prev_text' => esc_html__('&laquo; Previous', 'maneli-car-inquiry'),
                'next_text' => esc_html__('Next &raquo;', 'maneli-car-inquiry'),
                'type'      => 'plain',
            ]);
            ?>
        </div>
        <?php wp_reset_postdata(); ?>
    <?php endif; ?>
</div>