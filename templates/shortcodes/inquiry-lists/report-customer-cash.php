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

<div class="mb-3">
    <a href="<?php echo esc_url($back_link); ?>" class="btn btn-light btn-wave">
        <i class="la la-arrow-right me-1"></i>
        بازگشت به لیست
    </a>
</div>

<div class="card custom-card">
    <div class="card-header bg-warning-transparent">
        <div class="card-title">
            <i class="la la-dollar-sign me-2 fs-20"></i>
            جزئیات درخواست خرید نقدی
            <small class="text-muted">(#<?php echo esc_html($inquiry_id); ?>)</small>
        </div>
    </div>
    <div class="card-body">
        <!-- Status Alert -->
        <div class="alert alert-<?php echo $status_key === 'completed' || $status_key === 'approved' ? 'success' : ($status_key === 'rejected' ? 'danger' : ($status_key === 'awaiting_payment' ? 'warning' : 'info')); ?> border-<?php echo $status_key === 'completed' || $status_key === 'approved' ? 'success' : ($status_key === 'rejected' ? 'danger' : ($status_key === 'awaiting_payment' ? 'warning' : 'info')); ?>">
            <div class="d-flex align-items-center">
                <i class="la la-<?php echo $status_key === 'completed' || $status_key === 'approved' ? 'check-circle' : ($status_key === 'rejected' ? 'times-circle' : 'info-circle'); ?> fs-24 me-3"></i>
                <div class="flex-fill">
                    <strong>وضعیت فعلی:</strong>
                    <span class="badge bg-<?php echo $status_key === 'completed' || $status_key === 'approved' ? 'success' : ($status_key === 'rejected' ? 'danger' : ($status_key === 'awaiting_payment' ? 'warning' : 'info')); ?> ms-2">
                        <?php echo esc_html($status_label); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Info Note -->
        <div class="alert alert-light border">
            <i class="la la-exclamation-triangle text-warning me-2"></i>
            <strong>توجه:</strong>
            قیمت‌های اعلام شده تقریبی هستند. قیمت نهایی خودرو بر اساس نرخ روز بازار در زمان پرداخت پیش‌پرداخت تعیین خواهد شد.
        </div>

        <?php if ($rejection_reason): ?>
            <!-- Rejection Reason -->
            <div class="alert alert-danger border-danger">
                <i class="la la-info-circle me-2"></i>
                <strong>دلیل رد درخواست:</strong>
                <p class="mb-0 mt-2"><?php echo esc_html($rejection_reason); ?></p>
            </div>
        <?php endif; ?>

        <!-- Request Information Card -->
        <div class="card border mb-4">
            <div class="card-header bg-light">
                <h6 class="card-title mb-0">
                    <i class="la la-file-alt text-primary me-2"></i>
                    اطلاعات درخواست
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
                                        خودرو
                                    </div>
                                    <strong class="fs-16"><?php echo esc_html(get_the_title($product_id)); ?></strong>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-light">
                                    <div class="text-muted fs-12 mb-1">
                                        <i class="la la-palette me-1"></i>
                                        رنگ درخواستی
                                    </div>
                                    <strong class="fs-16"><?php echo esc_html($car_color ?: 'نامشخص'); ?></strong>
                                </div>
                            </div>
                            <?php if ($down_payment): ?>
                                <div class="col-md-6">
                                    <div class="border rounded p-3 bg-success-transparent">
                                        <div class="text-muted fs-12 mb-1">
                                            <i class="la la-money-bill me-1"></i>
                                            مبلغ پیش‌پرداخت
                                        </div>
                                        <strong class="fs-16 text-success"><?php echo number_format_i18n($down_payment); ?> تومان</strong>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-light">
                                    <div class="text-muted fs-12 mb-1">
                                        <i class="la la-calendar me-1"></i>
                                        تاریخ ثبت
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
                        اقدام مورد نیاز
                    </h6>
                </div>
                <div class="card-body">
                    <p class="mb-3">
                        <strong>مبلغ پیش‌پرداخت شما تعیین شد.</strong>
                        برای نهایی کردن خرید خود، لطفاً از طریق دکمه زیر به درگاه پرداخت بروید.
                    </p>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="text-center">
                        <input type="hidden" name="action" value="maneli_start_cash_payment">
                        <input type="hidden" name="inquiry_id" value="<?php echo esc_attr($inquiry_id); ?>">
                        <?php wp_nonce_field('maneli_start_cash_payment_nonce'); ?>
                        <button type="submit" class="btn btn-warning btn-wave btn-lg">
                            <i class="la la-lock me-2"></i>
                            انتقال به درگاه پرداخت
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
                    <h5 class="text-success mt-3">درخواست شما با موفقیت تکمیل شد</h5>
                    <p class="text-muted">همکاران ما به زودی برای هماهنگی نهایی با شما تماس خواهند گرفت.</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Back Button -->
        <div class="text-center mt-4 pt-3 border-top">
            <a href="<?php echo esc_url($back_link); ?>" class="btn btn-light btn-wave">
                <i class="la la-arrow-right me-1"></i>
                بازگشت به لیست استعلامات
            </a>
        </div>
    </div>
</div>

<style>
.bg-warning-transparent {
    background: linear-gradient(135deg, rgba(255, 193, 7, 0.1) 0%, transparent 100%);
}

.bg-success-transparent {
    background: linear-gradient(135deg, rgba(40, 167, 69, 0.1) 0%, transparent 100%);
}
</style>
