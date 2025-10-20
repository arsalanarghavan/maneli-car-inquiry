<?php
/**
 * Template for the Admin/Expert view of a single Cash Inquiry Report.
 * Modern design matching the dashboard theme and installment report.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/InquiryLists
 * @author  Arsalan Arghavan (Redesigned)
 * @version 2.0.1 (Fixed HTML structure)
 *
 * @var int $inquiry_id The ID of the cash inquiry post.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Permission Check
$can_view = Maneli_Permission_Helpers::can_user_view_inquiry($inquiry_id, get_current_user_id());

if (!$can_view) {
    echo '<div class="alert alert-danger">استعلام یافت نشد یا دسترسی ندارید.</div>';
} else {
    // Data Retrieval
    $inquiry = get_post($inquiry_id);
    $product_id = get_post_meta($inquiry_id, 'product_id', true);
    $status = get_post_meta($inquiry_id, 'cash_inquiry_status', true);
    $status_label = Maneli_CPT_Handler::get_cash_inquiry_status_label($status);
    $expert_name = get_post_meta($inquiry_id, 'assigned_expert_name', true);
    $back_link = remove_query_arg('cash_inquiry_id');

    // Customer Details
    $first_name = get_post_meta($inquiry_id, 'cash_first_name', true);
    $last_name = get_post_meta($inquiry_id, 'cash_last_name', true);
    $mobile_number = get_post_meta($inquiry_id, 'mobile_number', true);
    $car_color = get_post_meta($inquiry_id, 'cash_car_color', true);
    $down_payment = get_post_meta($inquiry_id, 'cash_down_payment', true);
    $rejection_reason = get_post_meta($inquiry_id, 'cash_rejection_reason', true);
    $car_name = get_the_title($product_id);

    $is_admin_or_expert = current_user_can('manage_maneli_inquiries') || Maneli_Permission_Helpers::is_assigned_expert($inquiry_id, get_current_user_id());

    // Expert Status
    $expert_status = get_post_meta($inquiry_id, 'expert_status', true);
    $expert_status_note = get_post_meta($inquiry_id, 'expert_status_note', true);
    $options = get_option('maneli_inquiry_all_options', []);
?>

<!-- Back Button -->
<div class="mb-3">
    <a href="<?php echo esc_url($back_link); ?>" class="btn btn-light btn-wave">
        <i class="la la-arrow-right me-1"></i>
        <?php esc_html_e('Back to List', 'maneli-car-inquiry'); ?>
    </a>
</div>

<!-- Header Card -->
<div class="card custom-card">
    <div class="card-header">
        <div class="card-title">
            <i class="la la-dollar-sign me-2"></i>
            <?php esc_html_e('Cash Purchase Request Details', 'maneli-car-inquiry'); ?>
            <small class="text-muted">(#<?php echo esc_html($inquiry_id); ?>)</small>
        </div>
    </div>
    <div class="card-body">
        <!-- Status Alert -->
        <div class="alert alert-<?php echo $status === 'completed' ? 'success' : ($status === 'approved' ? 'info' : ($status === 'rejected' ? 'danger' : ($status === 'awaiting_payment' ? 'warning' : 'secondary'))); ?> border-<?php echo $status === 'completed' ? 'success' : ($status === 'approved' ? 'info' : ($status === 'rejected' ? 'danger' : ($status === 'awaiting_payment' ? 'warning' : 'secondary'))); ?>">
            <div class="d-flex align-items-center justify-content-between flex-wrap">
                <div>
                    <strong><i class="la la-info-circle me-1"></i><?php esc_html_e('Current Status:', 'maneli-car-inquiry'); ?></strong> 
                    <span class="badge bg-<?php echo $status === 'completed' ? 'success' : ($status === 'approved' ? 'info' : ($status === 'rejected' ? 'danger' : ($status === 'awaiting_payment' ? 'warning' : 'secondary'))); ?>-transparent fs-14 ms-2">
                        <?php echo esc_html($status_label); ?>
                    </span>
                    <?php if ($expert_name): ?>
                        <br><strong class="mt-2 d-inline-block"><?php esc_html_e('Assigned Expert:', 'maneli-car-inquiry'); ?></strong> 
                        <span class="badge bg-info-transparent"><?php echo esc_html($expert_name); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($rejection_reason): ?>
        <!-- Rejection Reason -->
        <div class="alert alert-danger border-danger">
            <strong><i class="la la-exclamation-triangle me-1"></i><?php esc_html_e('Reason for Rejection:', 'maneli-car-inquiry'); ?></strong>
            <p class="mb-0 mt-2"><?php echo esc_html($rejection_reason); ?></p>
        </div>
        <?php endif; ?>

        <!-- Request Information -->
        <div class="row mt-4">
            <div class="col-md-4 mb-3">
                <?php 
                $car_image = get_the_post_thumbnail($product_id, 'medium', ['class' => 'img-fluid rounded shadow-sm']);
                if ($car_image) {
                    echo $car_image;
                } else {
                    echo '<div class="bg-light rounded d-flex align-items-center justify-content-center text-muted" style="height: 200px; border: 2px dashed #dee2e6;">
                        <div class="text-center">
                            <i class="la la-image fs-40"></i>
                            <p class="mb-0 mt-2">بدون تصویر</p>
                        </div>
                    </div>';
                }
                ?>
            </div>
            <div class="col-md-8">
                <h5 class="mb-3 fw-semibold">
                    <i class="la la-info-circle text-primary me-1"></i>
                    <?php esc_html_e('Request Information', 'maneli-car-inquiry'); ?>
                </h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0">
                        <tbody>
                            <tr>
                                <td class="fw-semibold bg-light" width="40%"><?php esc_html_e('Customer', 'maneli-car-inquiry'); ?></td>
                                <td><strong><?php echo esc_html($first_name . ' ' . $last_name); ?></strong></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold bg-light"><?php esc_html_e('Mobile Number', 'maneli-car-inquiry'); ?></td>
                                <td>
                                    <a href="tel:<?php echo esc_attr($mobile_number); ?>" class="text-primary">
                                        <i class="la la-phone me-1"></i><?php echo esc_html($mobile_number); ?>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold bg-light"><?php esc_html_e('Car', 'maneli-car-inquiry'); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(get_permalink($product_id)); ?>" target="_blank" class="text-primary">
                                        <?php echo esc_html($car_name); ?> <i class="la la-external-link-alt"></i>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold bg-light"><?php esc_html_e('Requested Color', 'maneli-car-inquiry'); ?></td>
                                <td><span class="badge bg-secondary-transparent"><?php echo esc_html($car_color); ?></span></td>
                            </tr>
                            <?php if (!empty($down_payment)): ?>
                                <tr>
                                    <td class="fw-semibold bg-light"><?php esc_html_e('Down Payment Amount', 'maneli-car-inquiry'); ?></td>
                                    <td>
                                        <strong class="text-success fs-16"><?php echo number_format_i18n($down_payment); ?></strong> 
                                        <?php esc_html_e('Toman', 'maneli-car-inquiry'); ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <td class="fw-semibold bg-light"><?php esc_html_e('Date Submitted', 'maneli-car-inquiry'); ?></td>
                                <td><?php echo Maneli_Render_Helpers::maneli_gregorian_to_jalali($inquiry->post_date, 'Y/m/d H:i'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="mt-4 pt-4 border-top">
            <h5 class="mb-3 fw-semibold">
                <i class="la la-tasks text-primary me-1"></i>
                <?php esc_html_e('Actions', 'maneli-car-inquiry'); ?>
            </h5>
            <div class="d-flex gap-2 flex-wrap justify-content-center">
                <?php if (!$expert_name): ?>
                    <button type="button" class="btn btn-info btn-wave assign-expert-btn" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-inquiry-type="cash">
                        <i class="la la-user-plus me-1"></i>
                        <?php esc_html_e('Assign to Expert', 'maneli-car-inquiry'); ?>
                    </button>
                <?php endif; ?>
                
                <button type="button" class="btn btn-warning btn-wave" id="edit-cash-inquiry-btn">
                    <i class="la la-edit me-1"></i>
                    <?php esc_html_e('Edit Information', 'maneli-car-inquiry'); ?>
                </button>
                
                <button type="button" class="btn btn-danger btn-wave delete-inquiry-report-btn" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-inquiry-type="cash">
                    <i class="la la-trash me-1"></i>
                    <?php esc_html_e('Delete Request', 'maneli-car-inquiry'); ?>
                </button>

                <?php if ($status === 'pending' || $status === 'approved'): ?>
                    <button type="button" class="btn btn-success btn-wave" id="set-downpayment-btn">
                        <i class="la la-dollar-sign me-1"></i>
                        <?php esc_html_e('Set Down Payment', 'maneli-car-inquiry'); ?>
                    </button>
                    <button type="button" class="btn btn-danger btn-wave" id="reject-cash-inquiry-btn">
                        <i class="la la-times-circle me-1"></i>
                        <?php esc_html_e('Reject Request', 'maneli-car-inquiry'); ?>
                    </button>
                <?php endif; ?>
                
                <?php if ($status === 'awaiting_payment'): ?>
                    <a href="<?php echo esc_url(add_query_arg(['cash_inquiry_id' => $inquiry_id, 'payment_link' => 'true'], home_url('/dashboard/inquiries/cash'))); ?>" target="_blank" class="btn btn-info btn-wave">
                        <i class="la la-link me-1"></i>
                        <?php esc_html_e('View Customer Payment Link', 'maneli-car-inquiry'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($is_admin_or_expert): ?>
<!-- Meeting Schedule Card -->
<div class="card custom-card mt-3">
    <div class="card-header bg-info-transparent">
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
                    <div id="meeting_slots" class="border rounded p-2 bg-light" style="min-height: 38px;"></div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-success btn-wave w-100">
                        <i class="la la-calendar-check me-1"></i>
                        <?php esc_html_e('Book', 'maneli-car-inquiry'); ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Expert Decision Card -->
<div class="card custom-card mt-3">
    <div class="card-header bg-secondary-transparent">
        <div class="card-title">
            <i class="la la-user-cog me-2"></i>
            <?php esc_html_e('Expert Decision', 'maneli-car-inquiry'); ?>
        </div>
    </div>
    <div class="card-body">
        <?php 
        $raw = $options['expert_statuses'] ?? ''; 
        $lines = array_filter(array_map('trim', explode("\n", (string)$raw))); 
        $default_key = $options['expert_default_status'] ?? 'unknown'; 
        ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="maneli_expert_update_decision">
            <input type="hidden" name="inquiry_id" value="<?php echo esc_attr($inquiry_id); ?>">
            <input type="hidden" name="inquiry_type" value="cash">
            <?php wp_nonce_field('maneli_expert_update_decision'); ?>
            
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label"><?php esc_html_e('Status', 'maneli-car-inquiry'); ?>:</label>
                    <select name="expert_status" class="form-select">
                        <?php if (empty($lines)): ?>
                            <option value="unknown"><?php esc_html_e('Unknown', 'maneli-car-inquiry'); ?></option>
                            <option value="approved"><?php esc_html_e('Approved', 'maneli-car-inquiry'); ?></option>
                            <option value="rejected"><?php esc_html_e('Rejected', 'maneli-car-inquiry'); ?></option>
                            <option value="reviewing"><?php esc_html_e('Under Review', 'maneli-car-inquiry'); ?></option>
                        <?php else: ?>
                            <?php foreach ($lines as $line): 
                                list($key, $label) = array_pad(explode('|', $line), 3, ''); 
                            ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($expert_status ?: $default_key, $key); ?>>
                                    <?php echo esc_html($label ?: $key); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label"><?php esc_html_e('Note (optional)', 'maneli-car-inquiry'); ?>:</label>
                    <textarea name="expert_note" rows="2" class="form-control" placeholder="<?php esc_attr_e('Add your notes here...', 'maneli-car-inquiry'); ?>"><?php echo esc_textarea($expert_status_note); ?></textarea>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-success btn-wave w-100">
                        <i class="la la-save me-1"></i>
                        <?php esc_html_e('Save', 'maneli-car-inquiry'); ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; // End admin/expert check ?>

<?php } // End permission check ?>
