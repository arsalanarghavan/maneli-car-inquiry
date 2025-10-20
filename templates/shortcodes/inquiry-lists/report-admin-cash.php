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
?>

<div class="row" id="cash-inquiry-details" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>">
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
                <div class="alert alert-<?php echo $status === 'completed' ? 'success' : ($status === 'approved' ? 'success' : ($status === 'rejected' ? 'danger' : ($status === 'awaiting_payment' ? 'warning' : 'secondary'))); ?>">
                    <strong><?php esc_html_e('Current Status:', 'maneli-car-inquiry'); ?></strong> <?php echo esc_html($status_label); ?>
                    <?php if ($expert_name): ?>
                        <br><strong><?php esc_html_e('Assigned Expert:', 'maneli-car-inquiry'); ?></strong> <?php echo esc_html($expert_name); ?>
                    <?php endif; ?>
                </div>

                <div class="row mt-4">
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
                                        <td class="fw-semibold bg-light"><?php esc_html_e('Customer', 'maneli-car-inquiry'); ?></td>
                                        <td><?php echo esc_html(get_post_meta($inquiry_id, 'cash_first_name', true) . ' ' . get_post_meta($inquiry_id, 'cash_last_name', true)); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold bg-light"><?php esc_html_e('Mobile Number', 'maneli-car-inquiry'); ?></td>
                                        <td><a href="tel:<?php echo esc_attr(get_post_meta($inquiry_id, 'mobile_number', true)); ?>"><?php echo esc_html(get_post_meta($inquiry_id, 'mobile_number', true)); ?></a></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold bg-light"><?php esc_html_e('Car', 'maneli-car-inquiry'); ?></td>
                                        <td><a href="<?php echo esc_url(get_permalink($product_id)); ?>" target="_blank"><?php echo esc_html(get_the_title($product_id)); ?></a></td>
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
                                            <td class="fw-semibold bg-light"><?php esc_html_e('Rejection Reason', 'maneli-car-inquiry'); ?></td>
                                            <td><?php echo esc_html($rejection_reason); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-top">
                    <h5 class="mb-3"><?php esc_html_e('Actions', 'maneli-car-inquiry'); ?></h5>
                    <div class="d-flex gap-2 flex-wrap justify-content-center">
                        <?php if (!$expert_name): ?>
                            <button type="button" class="btn btn-info assign-expert-btn" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-inquiry-type="cash">
                                <i class="la la-user-plus me-1"></i>
                                <?php esc_html_e('Assign to Expert', 'maneli-car-inquiry'); ?>
                            </button>
                        <?php endif; ?>
                        
                        <button type="button" class="btn btn-warning" id="edit-cash-inquiry-btn">
                            <i class="la la-edit me-1"></i>
                            <?php esc_html_e('Edit Information', 'maneli-car-inquiry'); ?>
                        </button>
                        
                        <button type="button" class="btn btn-danger delete-inquiry-report-btn" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-inquiry-type="cash">
                            <i class="la la-trash me-1"></i>
                            <?php esc_html_e('Delete Request', 'maneli-car-inquiry'); ?>
                        </button>

                        <?php if ($status === 'pending' || $status === 'approved'): ?>
                            <button type="button" class="btn btn-success" id="set-downpayment-btn">
                                <i class="la la-dollar-sign me-1"></i>
                                <?php esc_html_e('Set Down Payment', 'maneli-car-inquiry'); ?>
                            </button>
                            <button type="button" class="btn btn-danger" id="reject-cash-inquiry-btn">
                                <i class="la la-times-circle me-1"></i>
                                <?php esc_html_e('Reject Request', 'maneli-car-inquiry'); ?>
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($status === 'awaiting_payment'): ?>
                            <a href="<?php echo esc_url(add_query_arg(['cash_inquiry_id' => $inquiry_id, 'payment_link' => 'true'], home_url('/dashboard/?endp=inf_menu_4'))); ?>" target="_blank" class="btn btn-info">
                                <i class="la la-link me-1"></i>
                                <?php esc_html_e('View Customer Payment Link', 'maneli-car-inquiry'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mt-4 text-center">
                    <a href="<?php echo esc_url($back_link); ?>" class="btn btn-light btn-wave">
                        <i class="la la-arrow-left me-1"></i>
                        <?php esc_html_e('Back to List', 'maneli-car-inquiry'); ?>
                    </a>
                </div>
            </div>
        </div>

        <?php if (current_user_can('manage_maneli_inquiries') || in_array('maneli_expert', wp_get_current_user()->roles, true)) : ?>
        <div class="card custom-card mt-3">
            <div class="card-header">
                <div class="card-title">
                    <i class="la la-calendar-alt me-2"></i>
                    <?php esc_html_e('Schedule In-Person Meeting', 'maneli-car-inquiry'); ?>
                </div>
            </div>
            <div class="card-body">
                <form id="meeting_form" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-inquiry-type="cash">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label"><?php esc_html_e('Select Date', 'maneli-car-inquiry'); ?>:</label>
                            <input type="date" id="meeting_date" class="form-control" required>
                            <input type="hidden" id="meeting_start" value="">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?php esc_html_e('Available Slots', 'maneli-car-inquiry'); ?>:</label>
                            <div id="meeting_slots"></div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="la la-calendar-check me-1"></i>
                                <?php esc_html_e('Book Meeting', 'maneli-car-inquiry'); ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card custom-card mt-3">
            <div class="card-header">
                <div class="card-title">
                    <i class="la la-user-cog me-2"></i>
                    <?php esc_html_e('Expert Decision', 'maneli-car-inquiry'); ?>
                </div>
            </div>
            <div class="card-body">
                <?php $options = get_option('maneli_inquiry_all_options', []); $raw = $options['expert_statuses'] ?? ''; $lines = array_filter(array_map('trim', explode("\n", (string)$raw))); $default_key = $options['expert_default_status'] ?? 'unknown'; ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="maneli_expert_update_decision">
                    <input type="hidden" name="inquiry_id" value="<?php echo esc_attr($inquiry_id); ?>">
                    <input type="hidden" name="inquiry_type" value="cash">
                    <?php wp_nonce_field('maneli_expert_update_decision'); ?>
                    
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label"><?php esc_html_e('Status', 'maneli-car-inquiry'); ?>:</label>
                            <select name="expert_status" class="form-select">
                                <?php foreach ($lines as $line): list($key,$label) = array_pad(explode('|', $line), 3, ''); ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php selected(get_post_meta($inquiry_id, 'expert_status', true) ?: $default_key, $key); ?>><?php echo esc_html($label ?: $key); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?php esc_html_e('Note (optional)', 'maneli-car-inquiry'); ?>:</label>
                            <textarea name="expert_note" rows="2" class="form-control"><?php echo esc_textarea(get_post_meta($inquiry_id, 'expert_status_note', true)); ?></textarea>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="la la-save me-1"></i>
                                <?php esc_html_e('Save Decision', 'maneli-car-inquiry'); ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
