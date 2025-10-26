<?php
/**
 * Template for Step 5 of the inquiry form (Final Result) - Customer view of installment inquiry.
 * Modern redesign with Bootstrap theme styling.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/InquiryForm
 * @version 2.0.0 (Modern redesign)
 *
 * @var int $inquiry_id The ID of the inquiry post.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Fetch all necessary data for the report
$status = get_post_meta($inquiry_id, 'inquiry_status', true);
$expert_status = get_post_meta($inquiry_id, 'expert_status', true);
$post_meta = get_post_meta($inquiry_id);
$product_id = $post_meta['product_id'][0] ?? 0;
$car_name = get_the_title($product_id);
$down_payment = (int)($post_meta['maneli_inquiry_down_payment'][0] ?? 0);
$term_months = $post_meta['maneli_inquiry_term_months'][0] ?? 0;
$rejection_reason = get_post_meta($inquiry_id, 'rejection_reason', true);
$finotex_data = get_post_meta($inquiry_id, '_finotex_response_data', true);
$cheque_color_code = $finotex_data['result']['chequeColor'] ?? 0;
$expert_status_info = Maneli_Render_Helpers::get_expert_status_info($expert_status);

// Product data
$product = wc_get_product($product_id);
$product_image = $product ? wp_get_attachment_url($product->get_image_id()) : '';

// Status badge
$status_data = [
    'pending' => ['label' => 'در انتظار بررسی', 'class' => 'warning', 'icon' => 'clock'],
    'user_confirmed' => ['label' => 'تایید و ارجاع شده', 'class' => 'success', 'icon' => 'check-circle'],
    'approved' => ['label' => 'تایید نهایی', 'class' => 'success', 'icon' => 'check-double'],
    'rejected' => ['label' => 'رد شده', 'class' => 'danger', 'icon' => 'times-circle'],
    'more_docs' => ['label' => 'نیاز به مدارک بیشتر', 'class' => 'warning', 'icon' => 'file-upload'],
];
$badge = $status_data[$status] ?? ['label' => 'نامشخص', 'class' => 'secondary', 'icon' => 'question-circle'];
?>

<div class="mb-3">
    <a href="<?php echo home_url('/dashboard/inquiries/installment'); ?>" class="btn btn-light btn-wave">
        <i class="la la-arrow-right me-1"></i>
        بازگشت به لیست
    </a>
</div>

<div class="card custom-card">
    <div class="card-header bg-info-transparent">
        <div class="card-title">
            <i class="la la-credit-card me-2 fs-20"></i>
            نتیجه نهایی استعلام اقساطی
            <small class="text-muted">(#<?php echo esc_html($inquiry_id); ?>)</small>
        </div>
    </div>
    <div class="card-body">
        <!-- Status Alert -->
        <div class="alert alert-<?php echo $badge['class']; ?> border-<?php echo $badge['class']; ?>">
            <div class="d-flex align-items-center">
                <i class="la la-<?php echo $badge['icon']; ?> fs-32 me-3"></i>
                <div class="flex-fill">
                    <h5 class="alert-heading mb-2">
                        وضعیت فعلی: 
                        <span class="badge bg-<?php echo $badge['class']; ?> ms-2">
                            <?php echo esc_html($badge['label']); ?>
                        </span>
                    </h5>
                    <?php if ($status === 'user_confirmed'): ?>
                        <p class="mb-0">درخواست شما توسط کارشناسان ما تایید و به واحد فروش ارجاع شده است. یکی از همکاران ما به زودی برای هماهنگی نهایی با شما تماس خواهد گرفت.</p>
                    <?php elseif ($status === 'rejected'): ?>
                        <p class="mb-0">متأسفانه درخواست شما رد شده است.</p>
                    <?php elseif ($status === 'more_docs'): ?>
                        <p class="mb-0">لطفاً مدارک تکمیلی خواسته شده را ارسال نمایید.</p>
                    <?php else: ?>
                        <p class="mb-0">درخواست شما در دست بررسی است. لطفاً منتظر بمانید.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($expert_status_info): ?>
            <!-- Expert Status -->
            <div class="alert alert-light border">
                <div class="d-flex align-items-center">
                    <i class="la la-user-tie fs-24 me-2" style="color: <?php echo esc_attr($expert_status_info['color']); ?>;"></i>
                    <div>
                        <strong>وضعیت کارشناسی:</strong>
                        <span class="badge ms-2" style="background-color: <?php echo esc_attr($expert_status_info['color']); ?>; color: white;">
                            <?php echo esc_html($expert_status_info['label']); ?>
                        </span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($rejection_reason): ?>
            <!-- Rejection Reason -->
            <div class="alert alert-danger border-danger">
                <i class="la la-exclamation-triangle me-2"></i>
                <strong>دلیل رد درخواست:</strong>
                <p class="mb-0 mt-2"><?php echo esc_html($rejection_reason); ?></p>
            </div>
        <?php endif; ?>

        <!-- Request Summary Card -->
        <div class="card border mb-4">
            <div class="card-header bg-light">
                <h6 class="card-title mb-0">
                    <i class="la la-file-alt text-primary me-2"></i>
                    خلاصه درخواست
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php if ($product_image): ?>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <img src="<?php echo esc_url($product_image); ?>" alt="<?php echo esc_attr($car_name); ?>" class="img-fluid rounded shadow-sm">
                        </div>
                    <?php endif; ?>
                    <div class="col-md-<?php echo $product_image ? '8' : '12'; ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-light">
                                    <div class="text-muted fs-12 mb-1">
                                        <i class="la la-car me-1"></i>
                                        خودرو انتخابی
                                    </div>
                                    <strong class="fs-16"><?php echo esc_html($car_name); ?></strong>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-success-transparent">
                                    <div class="text-muted fs-12 mb-1">
                                        <i class="la la-money-bill me-1"></i>
                                        پیش‌پرداخت
                                    </div>
                                    <strong class="fs-16 text-success"><?php echo number_format_i18n($down_payment); ?> تومان</strong>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-info-transparent">
                                    <div class="text-muted fs-12 mb-1">
                                        <i class="la la-calendar me-1"></i>
                                        مدت اقساط
                                    </div>
                                    <strong class="fs-16 text-info"><?php echo esc_html($term_months); ?> ماهه</strong>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-light">
                                    <div class="text-muted fs-12 mb-1">
                                        <i class="la la-clock me-1"></i>
                                        تاریخ ثبت
                                    </div>
                                    <strong class="fs-16">
                                        <?php 
                                        $inquiry = get_post($inquiry_id);
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

        <!-- Credit Verification Card -->
        <div class="card border mb-4">
            <div class="card-header bg-light">
                <h6 class="card-title mb-0">
                    <i class="la la-shield-alt text-success me-2"></i>
                    نتیجه اعتبارسنجی
                </h6>
            </div>
            <div class="card-body">
                <?php if (empty($finotex_data) || (isset($finotex_data['status']) && $finotex_data['status'] === 'SKIPPED')): ?>
                    <div class="alert alert-secondary">
                        <i class="la la-info-circle me-2"></i>
                        <strong>وضعیت چک صیادی:</strong> نامشخص
                        <br>
                        <small class="text-muted">استعلام بانکی انجام نشده است.</small>
                    </div>
                <?php else: ?>
                    <?php echo Maneli_Render_Helpers::render_cheque_status_info($cheque_color_code); ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Success Message -->
        <?php if ($status === 'user_confirmed' || $status === 'approved'): ?>
            <div class="card border-success">
                <div class="card-body text-center">
                    <i class="la la-check-circle text-success" style="font-size: 60px;"></i>
                    <h5 class="text-success mt-3">تبریک! درخواست شما تایید شد</h5>
                    <p class="text-muted">همکاران ما به زودی برای هماهنگی نهایی با شما تماس خواهند گرفت.</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Back Button -->
        <div class="text-center mt-4 pt-3 border-top">
            <a href="<?php echo home_url('/dashboard/inquiries/installment'); ?>" class="btn btn-light btn-wave">
                <i class="la la-arrow-right me-1"></i>
                بازگشت به لیست استعلامات
            </a>
        </div>
    </div>
</div>

<style>
.bg-info-transparent {
    background: linear-gradient(135deg, rgba(23, 162, 184, 0.1) 0%, transparent 100%);
}

.bg-success-transparent {
    background: linear-gradient(135deg, rgba(40, 167, 69, 0.1) 0%, transparent 100%);
}
</style>
