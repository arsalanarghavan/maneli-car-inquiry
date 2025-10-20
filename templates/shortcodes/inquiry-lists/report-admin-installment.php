<?php
/**
 * Template for the Admin/Expert view of a single Installment Inquiry Report.
 * Modern redesign with Bootstrap theme styling - matching the cash inquiry report design.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/InquiryLists
 * @author  Arsalan Arghavan (Redesigned by AI)
 * @version 2.0.0 (Complete modern redesign)
 *
 * @var int $inquiry_id The ID of the inquiry post.
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
$post = get_post($inquiry_id);
$post_meta = get_post_meta($inquiry_id);
$options = get_option('maneli_inquiry_all_options', []);
$finotex_data = get_post_meta($inquiry_id, '_finotex_response_data', true);

$inquiry_status = $post_meta['inquiry_status'][0] ?? 'pending';
$product_id = $post_meta['product_id'][0] ?? 0;
$car_name = get_the_title($product_id);
$cheque_color_code = $finotex_data['result']['chequeColor'] ?? 0;

$back_link = remove_query_arg('inquiry_id');
$status_label = Maneli_CPT_Handler::get_status_label($inquiry_status);
$is_admin_or_expert = current_user_can('manage_maneli_inquiries') || Maneli_Permission_Helpers::is_assigned_expert($inquiry_id, get_current_user_id());

// Loan Details
$down_payment = (int)($post_meta['maneli_inquiry_down_payment'][0] ?? 0);
$total_price = (int)($post_meta['maneli_inquiry_total_price'][0] ?? 0);
$term_months = (int)($post_meta['maneli_inquiry_term_months'][0] ?? 0);
$installment_amount = (int)($post_meta['maneli_inquiry_installment'][0] ?? 0);
$loan_amount = $total_price - $down_payment;

// Customer & Expert Details
$customer_id = $post->post_author;
$customer_user = get_userdata($customer_id);
$customer_name = $customer_user ? $customer_user->display_name : '';
$expert_name = get_post_meta($inquiry_id, 'assigned_expert_name', true);
$rejection_reason = get_post_meta($inquiry_id, 'rejection_reason', true);

// Tracking Status
$tracking_status = get_post_meta($inquiry_id, 'tracking_status', true) ?: 'new';
$tracking_status_label = Maneli_CPT_Handler::get_tracking_status_label($tracking_status);
$follow_up_date = get_post_meta($inquiry_id, 'follow_up_date', true);
$meeting_date = get_post_meta($inquiry_id, 'meeting_date', true);

// Rejection Reasons
$rejection_reasons_raw = $options['installment_rejection_reasons'] ?? '';
$rejection_reasons = array_filter(array_map('trim', explode("\n", $rejection_reasons_raw)));

// Issuer Type
$issuer_type = $post_meta['issuer_type'][0] ?? 'self';
?>

<div class="row" id="installment-inquiry-details" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-inquiry-type="installment">
    <div class="col-xl-12">
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
                    <i class="la la-file-invoice me-2"></i>
                    <?php esc_html_e('Installment Request Details', 'maneli-car-inquiry'); ?>
                    <small class="text-muted">(#<?php echo esc_html($inquiry_id); ?>)</small>
                </div>
            </div>
            <div class="card-body">
                <!-- Status Alert -->
                <div class="alert alert-<?php echo $inquiry_status === 'approved' || $inquiry_status === 'user_confirmed' ? 'success' : ($inquiry_status === 'rejected' ? 'danger' : 'warning'); ?>">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <strong><?php esc_html_e('Current Status:', 'maneli-car-inquiry'); ?></strong> 
                            <span class="badge bg-<?php echo $inquiry_status === 'approved' || $inquiry_status === 'user_confirmed' ? 'success' : ($inquiry_status === 'rejected' ? 'danger' : 'warning'); ?>-transparent fs-14 ms-2">
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

                <?php if ($is_admin_or_expert): ?>
                <!-- Tracking Status Box -->
                <div class="alert alert-info border-info">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div>
                            <strong class="fs-15"><?php esc_html_e('Tracking Status:', 'maneli-car-inquiry'); ?></strong>
                            <span class="badge bg-primary-gradient ms-2 fs-13"><?php echo esc_html($tracking_status_label); ?></span>
                            <?php if ($tracking_status === 'follow_up' && $follow_up_date): ?>
                                <br><small class="text-danger mt-1 d-inline-block">
                                    <i class="la la-calendar me-1"></i>
                                    <strong><?php esc_html_e('Follow-up Date:', 'maneli-car-inquiry'); ?></strong> <?php echo esc_html($follow_up_date); ?>
                                </small>
                            <?php endif; ?>
                            <?php if ($tracking_status === 'approved' && $meeting_date): ?>
                                <br><small class="text-success mt-1 d-inline-block">
                                    <i class="la la-calendar-check me-1"></i>
                                    <strong><?php esc_html_e('Meeting Date:', 'maneli-car-inquiry'); ?></strong> <?php echo esc_html($meeting_date); ?>
                                </small>
                            <?php endif; ?>
                        </div>
                        <button class="btn btn-sm btn-primary-light change-tracking-status-btn" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-current-status="<?php echo esc_attr($tracking_status); ?>">
                            <i class="la la-edit me-1"></i>
                            <?php esc_html_e('Set Status', 'maneli-car-inquiry'); ?>
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Loan & Car Information -->
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
                            <?php esc_html_e('Loan and Car Details', 'maneli-car-inquiry'); ?>
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped mb-0">
                                <tbody>
                                    <tr>
                                        <td class="fw-semibold bg-light" width="40%"><?php esc_html_e('Selected Car', 'maneli-car-inquiry'); ?></td>
                                        <td><a href="<?php echo esc_url(get_permalink($product_id)); ?>" target="_blank" class="text-primary"><?php echo esc_html($car_name); ?> <i class="la la-external-link-alt"></i></a></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold bg-light"><?php esc_html_e('Total Price', 'maneli-car-inquiry'); ?></td>
                                        <td><strong class="text-success"><?php echo Maneli_Render_Helpers::format_money($total_price); ?></strong> <?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold bg-light"><?php esc_html_e('Down Payment', 'maneli-car-inquiry'); ?></td>
                                        <td><strong class="text-info"><?php echo Maneli_Render_Helpers::format_money($down_payment); ?></strong> <?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold bg-light"><?php esc_html_e('Loan Amount', 'maneli-car-inquiry'); ?></td>
                                        <td><strong class="text-warning"><?php echo Maneli_Render_Helpers::format_money($loan_amount); ?></strong> <?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold bg-light"><?php esc_html_e('Installment Term', 'maneli-car-inquiry'); ?></td>
                                        <td><span class="badge bg-secondary-transparent"><?php echo absint($term_months); ?> <?php esc_html_e('Months', 'maneli-car-inquiry'); ?></span></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold bg-light"><?php esc_html_e('Monthly Installment', 'maneli-car-inquiry'); ?></td>
                                        <td><strong class="text-primary fs-16"><?php echo Maneli_Render_Helpers::format_money($installment_amount); ?></strong> <?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold bg-light"><?php esc_html_e('Date Submitted', 'maneli-car-inquiry'); ?></td>
                                        <td><?php echo Maneli_Render_Helpers::maneli_gregorian_to_jalali($post->post_date, 'Y/m/d H:i'); ?></td>
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
                        <?php if ($inquiry_status === 'pending' || $inquiry_status === 'more_docs' || $inquiry_status === 'failed'): ?>
                            <button type="button" class="btn btn-success btn-wave confirm-inquiry-btn" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-inquiry-type="installment" data-next-status="user_confirmed">
                                <i class="la la-check-circle me-1"></i>
                                <?php esc_html_e('Final Approval & Refer to Sales', 'maneli-car-inquiry'); ?>
                            </button>
                            <button type="button" class="btn btn-danger btn-wave reject-inquiry-btn" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-inquiry-type="installment">
                                <i class="la la-times-circle me-1"></i>
                                <?php esc_html_e('Final Rejection of Request', 'maneli-car-inquiry'); ?>
                            </button>
                        <?php endif; ?>

                        <?php if ($is_admin_or_expert && empty($expert_name)): ?>
                            <button type="button" class="btn btn-info btn-wave assign-expert-btn" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-inquiry-type="installment">
                                <i class="la la-user-plus me-1"></i>
                                <?php esc_html_e('Assign Expert', 'maneli-car-inquiry'); ?>
                            </button>
                        <?php endif; ?>

                        <?php if ($inquiry_status === 'failed' && $is_admin_or_expert): ?>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="d-inline-block">
                                <input type="hidden" name="action" value="maneli_admin_retry_finotex">
                                <input type="hidden" name="inquiry_id" value="<?php echo esc_attr($inquiry_id); ?>">
                                <?php wp_nonce_field('maneli_retry_finotex_nonce'); ?>
                                <button type="submit" class="btn btn-warning btn-wave">
                                    <i class="la la-sync me-1"></i>
                                    <?php esc_html_e('Retry Finotex Inquiry', 'maneli-car-inquiry'); ?>
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if (current_user_can('manage_maneli_inquiries')): ?>
                            <?php if ($inquiry_status !== 'rejected' && $inquiry_status !== 'user_confirmed'): ?>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="d-inline-block">
                                    <input type="hidden" name="action" value="maneli_admin_update_status">
                                    <input type="hidden" name="inquiry_id" value="<?php echo esc_attr($inquiry_id); ?>">
                                    <input type="hidden" name="new_status" value="more_docs">
                                    <?php wp_nonce_field('maneli_admin_update_status_nonce'); ?>
                                    <button type="submit" class="btn btn-secondary btn-wave">
                                        <i class="la la-file-alt me-1"></i>
                                        <?php esc_html_e('Request More Documents', 'maneli-car-inquiry'); ?>
                                    </button>
                                </form>
                            <?php endif; ?>

                            <button type="button" class="btn btn-danger btn-wave delete-inquiry-report-btn" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-inquiry-type="installment">
                                <i class="la la-trash me-1"></i>
                                <?php esc_html_e('Delete Request', 'maneli-car-inquiry'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Buyer Information Card -->
        <div class="card custom-card mt-3">
            <div class="card-header">
                <div class="card-title">
                    <i class="la la-user me-2"></i>
                    <?php esc_html_e('Buyer Information', 'maneli-car-inquiry'); ?>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('First Name', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['first_name'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Last Name', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['last_name'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Father\'s Name', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['father_name'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('National Code', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['national_code'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Date of Birth', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['birth_date'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Mobile Number', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold">
                                <a href="tel:<?php echo esc_attr($post_meta['mobile_number'][0] ?? ''); ?>" class="text-primary">
                                    <i class="la la-phone me-1"></i><?php echo esc_html($post_meta['mobile_number'][0] ?? '—'); ?>
                                </a>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Phone Number', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['phone_number'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Email', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['email'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Job Type', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['job_type'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Occupation', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['occupation'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Income Level', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['income_level'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Residency Status', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['residency_status'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Workplace Status', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['workplace_status'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Address', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['address'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($issuer_type === 'self'): ?>
        <!-- Bank Information Card (Self) -->
        <div class="card custom-card mt-3">
            <div class="card-header bg-success-transparent">
                <div class="card-title">
                    <i class="la la-university me-2"></i>
                    <?php esc_html_e('Bank Information (Cheque Holder)', 'maneli-car-inquiry'); ?>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Bank Name', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['bank_name'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Account Number', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['account_number'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Branch Code', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['branch_code'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Branch Name', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['branch_name'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($issuer_type === 'other'): ?>
        <!-- Issuer Information Card (Other Person) -->
        <div class="card custom-card mt-3">
            <div class="card-header bg-warning-transparent">
                <div class="card-title">
                    <i class="la la-user-friends me-2"></i>
                    <?php esc_html_e('Cheque Issuer Information', 'maneli-car-inquiry'); ?>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('First Name', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['issuer_full_name'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Last Name', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['issuer_last_name'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Father\'s Name', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['issuer_father_name'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('National Code', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['issuer_national_code'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Date of Birth', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['issuer_birth_date'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Mobile Number', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold">
                                <a href="tel:<?php echo esc_attr($post_meta['issuer_mobile_number'][0] ?? ''); ?>" class="text-primary">
                                    <i class="la la-phone me-1"></i><?php echo esc_html($post_meta['issuer_mobile_number'][0] ?? '—'); ?>
                                </a>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Phone Number', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['issuer_phone_number'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Job Type', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['issuer_job_type'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Occupation', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['issuer_occupation'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Residency Status', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['issuer_residency_status'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Workplace Status', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['issuer_workplace_status'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Address', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['issuer_address'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Issuer Bank Information -->
                <h5 class="mt-4 mb-3 pt-3 border-top fw-semibold">
                    <i class="la la-university text-success me-1"></i>
                    <?php esc_html_e('Bank Information (Cheque Holder)', 'maneli-car-inquiry'); ?>
                </h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Bank Name', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['issuer_bank_name'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Account Number', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['issuer_account_number'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Branch Code', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['issuer_branch_code'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Branch Name', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['issuer_branch_name'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Credit Verification Card (Finotex) -->
        <div class="card custom-card mt-3">
            <div class="card-header bg-primary-transparent">
                <div class="card-title">
                    <i class="la la-shield-alt me-2"></i>
                    <?php esc_html_e('Credit Verification Result (Finotex)', 'maneli-car-inquiry'); ?>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($finotex_data) || ($finotex_data['status'] ?? '') === 'SKIPPED'): ?>
                    <div class="alert alert-warning border-warning">
                        <i class="la la-exclamation-triangle me-2"></i>
                        <?php esc_html_e('Bank inquiry was not performed or failed to return data.', 'maneli-car-inquiry'); ?>
                    </div>
                <?php else: ?>
                    <?php echo Maneli_Render_Helpers::render_cheque_status_info($cheque_color_code); ?>
                <?php endif; ?>
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
                <form id="meeting_form" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-inquiry-type="installment">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label"><?php esc_html_e('Select Date', 'maneli-car-inquiry'); ?>:</label>
                            <input type="date" id="meeting_date" class="form-control" required>
                            <input type="hidden" id="meeting_start" value="">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?php esc_html_e('Available Slots', 'maneli-car-inquiry'); ?>:</label>
                            <div id="meeting_slots" class="border rounded p-2 bg-light"></div>
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
                    <input type="hidden" name="inquiry_type" value="installment">
                    <?php wp_nonce_field('maneli_expert_update_decision'); ?>
                    
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label"><?php esc_html_e('Status', 'maneli-car-inquiry'); ?>:</label>
                            <select name="expert_status" class="form-select">
                                <?php foreach ($lines as $line): 
                                    list($key, $label) = array_pad(explode('|', $line), 3, ''); 
                                ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php selected(get_post_meta($inquiry_id, 'expert_status', true) ?: $default_key, $key); ?>>
                                        <?php echo esc_html($label ?: $key); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?php esc_html_e('Note (optional)', 'maneli-car-inquiry'); ?>:</label>
                            <textarea name="expert_note" rows="2" class="form-control"><?php echo esc_textarea(get_post_meta($inquiry_id, 'expert_status_note', true)); ?></textarea>
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
        <?php endif; ?>
    </div>
</div>

<!-- Hidden Forms -->
<form id="admin-action-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display: none;">
    <input type="hidden" name="action" value="maneli_admin_update_status">
    <input type="hidden" name="inquiry_id" value="<?php echo esc_attr($inquiry_id); ?>">
    <input type="hidden" id="final-status-input" name="new_status" value="">
    <input type="hidden" id="rejection-reason-input" name="rejection_reason" value="">
    <input type="hidden" id="assigned-expert-input" name="assigned_expert_id" value="">
    <?php wp_nonce_field('maneli_admin_update_status_nonce'); ?>
</form>

<!-- Tracking Status Modal -->
<div id="tracking-status-modal" class="modal fade" tabindex="-1" style="display:none;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php esc_html_e('Set Tracking Status', 'maneli-car-inquiry'); ?></h5>
                <button type="button" class="btn-close modal-close"></button>
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
                
                <div id="calendar-wrapper" style="display:none;" class="mb-3">
                    <label id="calendar-label" class="form-label"><?php esc_html_e('Select Date:', 'maneli-car-inquiry'); ?></label>
                    <input type="text" id="tracking-date-picker" class="form-control maneli-datepicker" readonly>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light modal-close"><?php esc_html_e('Cancel', 'maneli-car-inquiry'); ?></button>
                <button type="button" id="confirm-tracking-status-btn" class="btn btn-primary">
                    <i class="la la-check me-1"></i>
                    <?php esc_html_e('Confirm Status', 'maneli-car-inquiry'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Modal backdrop for tracking status */
#tracking-status-modal {
    background: rgba(0, 0, 0, 0.5);
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 9999;
    overflow-y: auto;
    padding: 20px;
}

#tracking-status-modal .modal-dialog {
    max-width: 500px;
    margin: 30px auto;
}

#tracking-status-modal .modal-content {
    border-radius: 8px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}
</style>
<?php } // End of permission check ?>
