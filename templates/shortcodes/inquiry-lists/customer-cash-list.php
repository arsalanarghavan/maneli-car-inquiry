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

<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="la la-dollar-sign me-2"></i>
                    <?php esc_html_e('Your Cash Purchase Requests', 'maneli-car-inquiry'); ?>
                </div>
            </div>
            <div class="card-body">
                <?php
                // Display payment status message if redirected from a payment gateway
                if (isset($payment_status)) {
                    $reason = isset($_GET['reason']) ? sanitize_text_field(urldecode($_GET['reason'])) : '';
                    maneli_get_template_part('shortcodes/inquiry-form/payment-status-message', ['status' => $payment_status, 'reason' => $reason]);
                }
                ?>

                <div class="alert alert-info" role="alert">
                    <i class="la la-info-circle me-2"></i>
                    <?php esc_html_e('Please note: Quoted prices are approximate. The final price will be determined based on the market rate at the time of the down payment.', 'maneli-car-inquiry'); ?>
                </div>

                <?php if (!$inquiries_query->have_posts()) : ?>
                    <div class="alert alert-warning" role="alert">
                        <i class="la la-info-circle me-2"></i>
                        <?php esc_html_e('You have not submitted any cash purchase requests yet.', 'maneli-car-inquiry'); ?>
                    </div>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th><?php esc_html_e('ID', 'maneli-car-inquiry'); ?></th>
                                    <th><?php esc_html_e('Car', 'maneli-car-inquiry'); ?></th>
                                    <th><?php esc_html_e('Status', 'maneli-car-inquiry'); ?></th>
                                    <th><?php esc_html_e('Date', 'maneli-car-inquiry'); ?></th>
                                    <th><?php esc_html_e('Actions', 'maneli-car-inquiry'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($inquiries_query->have_posts()) : $inquiries_query->the_post(); ?>
                                    <?php
                                    $inquiry_id = get_the_ID();
                                    $product_id = get_post_meta($inquiry_id, 'product_id', true);
                                    $status = get_post_meta($inquiry_id, 'cash_inquiry_status', true);
                                    $expert_status = get_post_meta($inquiry_id, 'expert_status', true);
                                    $report_url = add_query_arg('cash_inquiry_id', $inquiry_id, $current_url);
                                    $expert_status_info = Maneli_Render_Helpers::get_expert_status_info($expert_status);
                                    ?>
                                    <tr>
                                        <td>#<?php echo esc_html($inquiry_id); ?></td>
                                        <td><?php echo esc_html(get_the_title($product_id)); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $status === 'completed' ? 'success' : ($status === 'pending' ? 'warning' : 'secondary'); ?>">
                                                <?php echo esc_html(Maneli_CPT_Handler::get_cash_inquiry_status_label($status)); ?>
                                            </span>
                                            <?php if ($expert_status_info): ?>
                                                <br><span class="badge mt-1" style="background-color: <?php echo esc_attr($expert_status_info['color']); ?>;"><?php echo esc_html($expert_status_info['label']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo esc_html(get_the_date('Y/m/d', $inquiry_id)); ?></td>
                                        <td>
                                            <a href="<?php echo esc_url($report_url); ?>" class="btn btn-sm btn-primary-light">
                                                <i class="la la-eye me-1"></i>
                                                <?php esc_html_e('View Details', 'maneli-car-inquiry'); ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 text-center">
                        <?php
                        echo paginate_links([
                            'total' => $inquiries_query->max_num_pages,
                            'current' => max(1, get_query_var('paged')),
                            'prev_text' => '&laquo; ' . esc_html__('Previous', 'maneli-car-inquiry'),
                            'next_text' => esc_html__('Next', 'maneli-car-inquiry') . ' &raquo;',
                            'type' => 'plain',
                        ]);
                        ?>
                    </div>
                <?php endif; ?>
                <?php wp_reset_postdata(); ?>
            </div>
        </div>
    </div>
</div>

<!-- Tracking Status Modal -->
<div class="modal fade" id="tracking-status-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php esc_html_e('Set Tracking Status', 'maneli-car-inquiry'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="tracking-status-select" class="form-label"><?php esc_html_e('Select Status:', 'maneli-car-inquiry'); ?></label>
                    <select id="tracking-status-select" class="form-select">
                        <?php foreach (Maneli_CPT_Handler::get_tracking_statuses() as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="calendar-wrapper" style="display:none;">
                    <label id="calendar-label" class="form-label"><?php esc_html_e('Select Date:', 'maneli-car-inquiry'); ?></label>
                    <input type="text" id="tracking-date-picker" class="form-control maneli-datepicker" readonly>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'maneli-car-inquiry'); ?></button>
                <button type="button" id="confirm-tracking-status-btn" class="btn btn-primary">
                    <?php esc_html_e('Confirm Status', 'maneli-car-inquiry'); ?>
                </button>
            </div>
        </div>
    </div>
</div>
