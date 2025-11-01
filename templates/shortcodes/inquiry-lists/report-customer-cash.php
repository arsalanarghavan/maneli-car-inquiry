<?php
/**
 * Template for the Customer's view of a single Cash Inquiry Report.
 * Modern redesign with Bootstrap theme styling.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/InquiryLists
 * @version 2.0.0 (Modern redesign)
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
$car_color = get_post_meta($inquiry_id, 'cash_car_color', true);
$down_payment = get_post_meta($inquiry_id, 'cash_down_payment', true);
$rejection_reason = get_post_meta($inquiry_id, 'cash_rejection_reason', true);
$back_link = remove_query_arg('cash_inquiry_id');

// Product data
$product = wc_get_product($product_id);
$product_image = $product ? wp_get_attachment_url($product->get_image_id()) : '';
?>

<div class="main-content app-content">
    <div class="container-fluid">

<div class="row">
    <div class="col-xl-12">
        <div class="mb-3">
            <a href="<?php echo esc_url($back_link); ?>" class="btn btn-light btn-wave">
                <i class="la la-arrow-right me-1"></i>
                <?php esc_html_e('Back to List', 'maneli-car-inquiry'); ?>
            </a>
        </div>

        <div class="card custom-card">
            <div class="card-header bg-warning-transparent">
                <div class="card-title">
                    <i class="la la-dollar-sign me-2 fs-20"></i>
                    <?php esc_html_e('Cash Purchase Request Details', 'maneli-car-inquiry'); ?>
                    <small class="text-muted">(#<?php echo esc_html($inquiry_id); ?>)</small>
                </div>
            </div>
            <div class="card-body">
                <!-- Status Alert -->
                <div class="alert alert-<?php echo $status_key === 'completed' || $status_key === 'approved' ? 'success' : ($status_key === 'rejected' ? 'danger' : ($status_key === 'awaiting_payment' ? 'warning' : 'info')); ?> border-<?php echo $status_key === 'completed' || $status_key === 'approved' ? 'success' : ($status_key === 'rejected' ? 'danger' : ($status_key === 'awaiting_payment' ? 'warning' : 'info')); ?>">
                    <div class="d-flex align-items-center">
                        <i class="la la-<?php echo $status_key === 'completed' || $status_key === 'approved' ? 'check-circle' : ($status_key === 'rejected' ? 'times-circle' : 'info-circle'); ?> fs-24 me-3"></i>
                        <div class="flex-fill">
                            <strong><?php esc_html_e('Current Status:', 'maneli-car-inquiry'); ?></strong>
                            <span class="badge bg-<?php echo $status_key === 'completed' || $status_key === 'approved' ? 'success' : ($status_key === 'rejected' ? 'danger' : ($status_key === 'awaiting_payment' ? 'warning' : 'info')); ?> ms-2">
                                <?php echo esc_html($status_label); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Info Note -->
                <div class="alert alert-light border">
                    <i class="la la-exclamation-triangle text-warning me-2"></i>
                    <strong><?php esc_html_e('Note:', 'maneli-car-inquiry'); ?></strong>
                    <?php esc_html_e('The announced prices are approximate. The final price of the car will be determined based on the daily market rate at the time of down payment.', 'maneli-car-inquiry'); ?>
                </div>

                <?php if ($rejection_reason): ?>
                    <!-- Rejection Reason -->
                    <div class="alert alert-danger border-danger">
                        <i class="la la-info-circle me-2"></i>
                        <strong><?php esc_html_e('Rejection Reason:', 'maneli-car-inquiry'); ?></strong>
                        <p class="mb-0 mt-2"><?php echo esc_html($rejection_reason); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Request Information Card -->
                <div class="card border mb-4">
                    <div class="card-header bg-light">
                        <h6 class="card-title mb-0">
                            <i class="la la-file-alt text-primary me-2"></i>
                            <?php esc_html_e('Request Information', 'maneli-car-inquiry'); ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php if ($product_image): ?>
                                <div class="col-md-4 mb-3 mb-md-0">
                                    <img src="<?php echo esc_url($product_image); ?>" alt="<?php echo esc_attr(get_the_title($product_id)); ?>" class="img-fluid rounded shadow-sm">
                                </div>
                            <?php endif; ?>
                            <div class="col-md-<?php echo $product_image ? '8' : '12'; ?>">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="border rounded p-3 bg-light">
                                            <div class="text-muted fs-12 mb-1">
                                                <i class="la la-car me-1"></i>
                                                <?php esc_html_e('Car', 'maneli-car-inquiry'); ?>
                                            </div>
                                            <strong class="fs-16"><?php echo esc_html(get_the_title($product_id)); ?></strong>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="border rounded p-3 bg-light">
                                            <div class="text-muted fs-12 mb-1">
                                                <i class="la la-palette me-1"></i>
                                                <?php esc_html_e('Requested Color', 'maneli-car-inquiry'); ?>
                                            </div>
                                            <strong class="fs-16"><?php echo esc_html($car_color ?: esc_html__('Not Specified', 'maneli-car-inquiry')); ?></strong>
                                        </div>
                                    </div>
                                    <?php if ($down_payment): ?>
                                        <div class="col-md-6">
                                            <div class="border rounded p-3 bg-success-transparent">
                                                <div class="text-muted fs-12 mb-1">
                                                    <i class="la la-money-bill me-1"></i>
                                                    <?php esc_html_e('Down Payment Amount', 'maneli-car-inquiry'); ?>
                                                </div>
                                                <strong class="fs-16 text-success"><?php echo number_format_i18n($down_payment); ?> <?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></strong>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <div class="col-md-6">
                                        <div class="border rounded p-3 bg-light">
                                            <div class="text-muted fs-12 mb-1">
                                                <i class="la la-calendar me-1"></i>
                                                <?php esc_html_e('Registration Date', 'maneli-car-inquiry'); ?>
                                            </div>
                                            <strong class="fs-16">
                                                <?php 
                                                $timestamp = strtotime($inquiry->post_date);
                                                if (function_exists('maneli_gregorian_to_jalali')) {
                                                    echo maneli_gregorian_to_jalali(
                                                        date('Y', $timestamp),
                                                        date('m', $timestamp),
                                                        date('d', $timestamp),
                                                        'Y/m/d'
                                                    );
                                                } else {
                                                    echo date('Y/m/d', $timestamp);
                                                }
                                                ?>
                                            </strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Action -->
                <?php if ($status_key === 'awaiting_payment' && $down_payment): ?>
                    <div class="card border-warning mb-4">
                        <div class="card-header bg-warning-transparent">
                            <h6 class="card-title mb-0 text-warning">
                                <i class="la la-exclamation-triangle me-2"></i>
                                <?php esc_html_e('Action Required', 'maneli-car-inquiry'); ?>
                            </h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-3">
                                <strong><?php esc_html_e('Your down payment amount has been determined.', 'maneli-car-inquiry'); ?></strong>
                                <?php esc_html_e('To finalize your purchase, please go to the payment gateway using the button below.', 'maneli-car-inquiry'); ?>
                            </p>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="text-center">
                                <input type="hidden" name="action" value="maneli_start_cash_payment">
                                <input type="hidden" name="inquiry_id" value="<?php echo esc_attr($inquiry_id); ?>">
                                <?php wp_nonce_field('maneli_start_cash_payment_nonce'); ?>
                                <button type="submit" class="btn btn-warning btn-wave btn-lg">
                                    <i class="la la-lock me-2"></i>
                                    <?php esc_html_e('Go to Payment Gateway', 'maneli-car-inquiry'); ?>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Success Message -->
                <?php if ($status_key === 'completed'): ?>
                    <div class="card border-success mb-4">
                        <div class="card-body text-center">
                            <i class="la la-check-circle text-success" style="font-size: 60px;"></i>
                            <h5 class="text-success mt-3"><?php esc_html_e('Your request has been completed successfully', 'maneli-car-inquiry'); ?></h5>
                            <p class="text-muted"><?php esc_html_e('Our colleagues will contact you soon for final coordination.', 'maneli-car-inquiry'); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Back Button -->
                <div class="text-center mt-4 pt-3 border-top">
                    <a href="<?php echo esc_url($back_link); ?>" class="btn btn-light btn-wave">
                        <i class="la la-arrow-right me-1"></i>
                        <?php esc_html_e('Back to Inquiries List', 'maneli-car-inquiry'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

    </div>
</div>
<!-- End::main-content -->

<style>
.bg-warning-transparent {
    background: linear-gradient(135deg, rgba(255, 193, 7, 0.1) 0%, transparent 100%);
}

.bg-success-transparent {
    background: linear-gradient(135deg, rgba(40, 167, 69, 0.1) 0%, transparent 100%);
}
</style>
