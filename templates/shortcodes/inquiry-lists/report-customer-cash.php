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
                    <div class="col-md-4 mb-3">
                        <h6 class="mb-3 fw-semibold">
                            <i class="la la-car text-primary me-1"></i>
                            تصویر محصول
                        </h6>
                        <div class="product-image-container">
                            <?php if ($product_image): ?>
                                <img src="<?php echo esc_url($product_image); ?>" alt="<?php echo esc_attr(get_the_title($product_id)); ?>" class="img-fluid rounded shadow-sm product-image">
                            <?php else: ?>
                                <div class="bg-light rounded d-flex align-items-center justify-content-center text-muted product-image-placeholder">
                                    <div class="text-center">
                                        <i class="la la-image fs-40"></i>
                                        <p class="mb-0 mt-2">بدون تصویر</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <h6 class="mb-3 fw-semibold">
                            <i class="la la-info-circle text-primary me-1"></i>
                            جزئیات درخواست
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped mb-0 product-details-table">
                                <tbody>
                                    <tr>
                                        <td class="fw-semibold bg-light" width="40%">خودرو</td>
                                        <td><strong><?php echo esc_html(get_the_title($product_id)); ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold bg-light">رنگ درخواستی</td>
                                        <td><strong><?php echo esc_html($car_color ?: 'نامشخص'); ?></strong></td>
                                    </tr>
                                    <?php if ($down_payment): ?>
                                        <tr>
                                            <td class="fw-semibold bg-light">مبلغ پیش‌پرداخت</td>
                                            <td><strong class="text-success fs-16"><?php echo number_format_i18n($down_payment); ?> تومان</strong></td>
                                        </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td class="fw-semibold bg-light">تاریخ ثبت</td>
                                        <td>
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
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
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

/* Product Image and Table Layout */
.product-image-container {
    display: flex;
    flex-direction: column;
}

.product-image,
.product-image-placeholder {
    width: 100%;
    object-fit: cover;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.product-image-placeholder {
    min-height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px dashed #dee2e6;
    background: #f8f9fa;
}

/* Make image height match table height dynamically */
.product-image {
    height: auto;
    max-height: 100%;
}

/* Responsive design for mobile */
@media (max-width: 768px) {
    /* On mobile, stack image and table vertically */
    .product-image-container {
        margin-bottom: 20px;
    }
    
    .product-image,
    .product-image-placeholder {
        height: 200px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Match image height with table height
    const imageContainer = document.querySelector('.product-image-container');
    const tableContainer = document.querySelector('.product-details-table');
    
    if (imageContainer && tableContainer) {
        function matchHeights() {
            const tableHeight = tableContainer.offsetHeight;
            const image = imageContainer.querySelector('.product-image');
            if (image) {
                image.style.height = tableHeight + 'px';
            }
        }
        
        // Match heights on load and resize
        matchHeights();
        window.addEventListener('resize', matchHeights);
    }
});
</script>
