<?php
/**
 * Template for the Admin/Expert view of a single Cash Inquiry Report.
 * Modern design with role-based permissions.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/InquiryLists
 * @version 3.0.0 (Role-based redesign)
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
    return;
}

// Data Retrieval
$inquiry = get_post($inquiry_id);
$product_id = get_post_meta($inquiry_id, 'product_id', true);
$status = get_post_meta($inquiry_id, 'cash_inquiry_status', true);
$status_label = Maneli_CPT_Handler::get_cash_inquiry_status_label($status);
$back_link = remove_query_arg('cash_inquiry_id');

// Customer Details
$first_name = get_post_meta($inquiry_id, 'cash_first_name', true);
$last_name = get_post_meta($inquiry_id, 'cash_last_name', true);
$mobile_number = get_post_meta($inquiry_id, 'mobile_number', true);
$car_color = get_post_meta($inquiry_id, 'cash_car_color', true);
$down_payment = get_post_meta($inquiry_id, 'cash_down_payment', true);
$rejection_reason = get_post_meta($inquiry_id, 'cash_rejection_reason', true);
$car_name = get_the_title($product_id);

// Expert assignment
$expert_id = get_post_meta($inquiry_id, 'assigned_expert_id', true);
$expert_name = '';
if ($expert_id) {
    $expert = get_userdata($expert_id);
    $expert_name = $expert ? $expert->display_name : '';
} else {
    // Check old meta key for backward compatibility
    $expert_name = get_post_meta($inquiry_id, 'assigned_expert_name', true);
}

// Role checks
$is_admin = current_user_can('manage_maneli_inquiries');
$is_assigned_expert = Maneli_Permission_Helpers::is_assigned_expert($inquiry_id, get_current_user_id());
$is_expert = in_array('maneli_expert', wp_get_current_user()->roles, true);

// Expert notes and status
$expert_note = get_post_meta($inquiry_id, 'expert_note', true);
$expert_decision = get_post_meta($inquiry_id, 'expert_decision', true);
$meeting_date = get_post_meta($inquiry_id, 'meeting_date', true);
$meeting_time = get_post_meta($inquiry_id, 'meeting_time', true);

// Product data
$product = wc_get_product($product_id);
$product_image = $product ? wp_get_attachment_url($product->get_image_id()) : '';
?>

<!-- Wrapper for report page detection -->
<div class="frontend-expert-report" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-inquiry-type="cash">
    <!-- Back Button -->
    <div class="mb-3 report-back-button-wrapper">
        <a href="<?php echo esc_url($back_link); ?>" class="btn btn-light btn-wave">
            <i class="la la-arrow-right me-1"></i>
            بازگشت به لیست
        </a>
    </div>

    <!-- Header Card -->
    <div class="card custom-card">
    <div class="card-header bg-warning-transparent">
        <div class="card-title">
            <i class="la la-dollar-sign me-2"></i>
            جزئیات درخواست خرید نقدی
            <small class="text-muted">(#<?php echo esc_html($inquiry_id); ?>)</small>
        </div>
    </div>
    <div class="card-body">
        <!-- Status Alert -->
        <div class="alert alert-<?php echo $status === 'completed' ? 'success' : ($status === 'approved' ? 'info' : ($status === 'rejected' ? 'danger' : ($status === 'awaiting_payment' ? 'warning' : 'secondary'))); ?> border-<?php echo $status === 'completed' ? 'success' : ($status === 'approved' ? 'info' : ($status === 'rejected' ? 'danger' : ($status === 'awaiting_payment' ? 'warning' : 'secondary'))); ?>">
            <div class="d-flex align-items-center justify-content-between flex-wrap">
                <div>
                    <strong><i class="la la-info-circle me-1"></i>وضعیت فعلی:</strong> 
                    <span class="badge bg-<?php echo $status === 'completed' ? 'success' : ($status === 'approved' ? 'info' : ($status === 'rejected' ? 'danger' : ($status === 'awaiting_payment' ? 'warning' : 'secondary'))); ?>-transparent fs-14 ms-2">
                        <?php echo esc_html($status_label); ?>
                    </span>
                    <?php if ($expert_name): ?>
                        <br><strong class="mt-2 d-inline-block">کارشناس:</strong> 
                        <span class="badge bg-info-transparent"><?php echo esc_html($expert_name); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($rejection_reason): ?>
        <!-- Rejection Reason -->
        <div class="alert alert-danger border-danger">
            <strong><i class="la la-exclamation-triangle me-1"></i>دلیل رد درخواست:</strong>
            <p class="mb-0 mt-2"><?php echo esc_html($rejection_reason); ?></p>
        </div>
        <?php endif; ?>

        <!-- Request Information -->
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
                            <img src="<?php echo esc_url($product_image); ?>" alt="<?php echo esc_attr($car_name); ?>" class="img-fluid rounded shadow-sm">
                        </div>
                    <?php endif; ?>
                    <div class="col-md-<?php echo $product_image ? '8' : '12'; ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-light">
                                    <div class="text-muted fs-12 mb-1">
                                        <i class="la la-user me-1"></i>
                                        مشتری
                                    </div>
                                    <strong class="fs-16"><?php echo esc_html($first_name . ' ' . $last_name); ?></strong>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-light">
                                    <div class="text-muted fs-12 mb-1">
                                        <i class="la la-phone me-1"></i>
                                        شماره تماس
                                    </div>
                                    <a href="tel:<?php echo esc_attr($mobile_number); ?>" class="fs-16 fw-semibold text-primary">
                                        <?php echo esc_html($mobile_number); ?>
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-light">
                                    <div class="text-muted fs-12 mb-1">
                                        <i class="la la-car me-1"></i>
                                        خودرو
                                    </div>
                                    <strong class="fs-16"><?php echo esc_html($car_name); ?></strong>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-light">
                                    <div class="text-muted fs-12 mb-1">
                                        <i class="la la-palette me-1"></i>
                                        رنگ
                                    </div>
                                    <strong class="fs-16"><?php echo esc_html($car_color ?: 'نامشخص'); ?></strong>
                                </div>
                            </div>
                            <?php if ($down_payment): ?>
                                <div class="col-md-12">
                                    <div class="border rounded p-3 bg-success-transparent">
                                        <div class="text-muted fs-12 mb-1">
                                            <i class="la la-money-bill me-1"></i>
                                            مبلغ پیش‌پرداخت
                                        </div>
                                        <strong class="fs-20 text-success"><?php echo number_format_i18n($down_payment); ?> تومان</strong>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expert Actions - Only for Expert -->
        <?php if ($is_assigned_expert && !$is_admin): ?>
            
            <!-- Expert Status Control -->
            <div class="card border mb-4">
                <div class="card-header bg-warning-transparent">
                    <h6 class="card-title mb-0">
                        <i class="la la-tasks text-warning me-2"></i>
                        مدیریت وضعیت درخواست
                    </h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info border-info mb-3">
                        <strong>وضعیت فعلی:</strong> 
                        <span class="badge bg-info"><?php echo esc_html($status_label); ?></span>
                    </div>
                    
                    <div class="d-flex gap-2 flex-wrap">
                        <?php if (in_array($status, ['referred', 'new'])): ?>
                            <button type="button" class="btn btn-primary btn-wave" id="set-in-progress-btn">
                                <i class="la la-play me-1"></i>
                                شروع پیگیری
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($status === 'in_progress'): ?>
                            <button type="button" class="btn btn-warning btn-wave" id="request-downpayment-btn">
                                <i class="la la-money-bill me-1"></i>
                                درخواست پیش‌پرداخت
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($status === 'downpayment_received'): ?>
                            <button type="button" class="btn btn-info btn-wave" id="schedule-meeting-btn">
                                <i class="la la-calendar me-1"></i>
                                ثبت جلسه حضوری
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($status === 'meeting_scheduled'): ?>
                            <button type="button" class="btn btn-success btn-wave" id="approve-inquiry-btn">
                                <i class="la la-check-circle me-1"></i>
                                تایید نهایی
                            </button>
                            <button type="button" class="btn btn-danger btn-wave" id="reject-inquiry-btn">
                                <i class="la la-times-circle me-1"></i>
                                رد درخواست
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Down Payment Input (Only when requesting) -->
            <?php if ($status === 'in_progress'): ?>
            <div class="card border mb-4" id="downpayment-card" style="display: none;">
                <div class="card-header bg-warning-transparent">
                    <h6 class="card-title mb-0">
                        <i class="la la-money-bill text-warning me-2"></i>
                        تعیین مبلغ پیش‌پرداخت
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">مبلغ پیش‌پرداخت (تومان):</label>
                            <input type="number" class="form-control" id="downpayment-amount" value="<?php echo esc_attr($down_payment); ?>" placeholder="مثال: 50000000">
                        </div>
                        <div class="col-12">
                            <button type="button" class="btn btn-warning btn-wave" id="submit-downpayment-btn">
                                <i class="la la-send me-1"></i>
                                ارسال درخواست پیش‌پرداخت به مشتری
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Meeting Schedule (Only when downpayment received) -->
            <?php if ($status === 'downpayment_received'): ?>
            <div class="card border mb-4" id="meeting-card" style="display: none;">
                <div class="card-header bg-info-transparent">
                    <h6 class="card-title mb-0">
                        <i class="la la-calendar text-info me-2"></i>
                        زمان‌بندی جلسه حضوری
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">انتخاب تاریخ:</label>
                            <input type="text" class="form-control maneli-datepicker" id="meeting-date" value="<?php echo esc_attr($meeting_date); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">انتخاب ساعت:</label>
                            <input type="time" class="form-control" id="meeting-time" value="<?php echo esc_attr($meeting_time); ?>">
                        </div>
                        <div class="col-12">
                            <button type="button" class="btn btn-info btn-wave" id="submit-meeting-btn">
                                <i class="la la-save me-1"></i>
                                ثبت زمان جلسه و تغییر وضعیت
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Expert Note (Always visible for expert) -->
            <div class="card border mb-4">
                <div class="card-header bg-secondary-transparent">
                    <h6 class="card-title mb-0">
                        <i class="la la-sticky-note text-secondary me-2"></i>
                        یادداشت کارشناس
                    </h6>
                </div>
                <div class="card-body">
                    <textarea class="form-control" id="expert-note" rows="4" placeholder="یادداشت‌های خود را اینجا بنویسید..."><?php echo esc_textarea($expert_note); ?></textarea>
                    <button type="button" class="btn btn-secondary btn-wave mt-3" id="save-note-btn">
                        <i class="la la-save me-1"></i>
                        ذخیره یادداشت
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Admin View - Expert's Work -->
        <?php if ($is_admin): ?>
            <!-- Expert's Decision (Read-only for Admin) -->
            <?php if ($meeting_date || $meeting_time || $expert_note || $expert_decision): ?>
                <div class="card border mb-4">
                    <div class="card-header bg-secondary-transparent">
                        <h6 class="card-title mb-0">
                            <i class="la la-eye text-secondary me-2"></i>
                            گزارش کارشناس (فقط مشاهده)
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <?php if ($meeting_date || $meeting_time): ?>
                                <div class="col-md-12">
                                    <div class="alert alert-info border-info">
                                        <strong><i class="la la-calendar me-1"></i>زمان جلسه:</strong>
                                        <?php echo $meeting_date ? esc_html($meeting_date) : 'تعیین نشده'; ?>
                                        <?php echo $meeting_time ? ' ساعت ' . esc_html($meeting_time) : ''; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($expert_decision): ?>
                                <div class="col-md-12">
                                    <div class="alert alert-<?php echo $expert_decision === 'approved' ? 'success' : ($expert_decision === 'rejected' ? 'danger' : 'warning'); ?> border-<?php echo $expert_decision === 'approved' ? 'success' : ($expert_decision === 'rejected' ? 'danger' : 'warning'); ?>">
                                        <strong><i class="la la-gavel me-1"></i>تصمیم کارشناس:</strong>
                                        <span class="badge bg-<?php echo $expert_decision === 'approved' ? 'success' : ($expert_decision === 'rejected' ? 'danger' : 'warning'); ?> ms-2">
                                            <?php 
                                            echo $expert_decision === 'approved' ? 'تایید شده' : ($expert_decision === 'rejected' ? 'رد شده' : 'در حال بررسی');
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($expert_note): ?>
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">یادداشت کارشناس:</label>
                                    <div class="alert alert-light border">
                                        <i class="la la-sticky-note me-2"></i>
                                        <?php echo nl2br(esc_html($expert_note)); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Admin Actions -->
            <div class="card border-primary">
                <div class="card-header bg-primary-transparent">
                    <h6 class="card-title mb-0">
                        <i class="la la-shield-alt text-primary me-2"></i>
                        عملیات مدیر
                    </h6>
                </div>
                <div class="card-body">
                    <?php if ($expert_name): ?>
                        <!-- Show assigned expert -->
                        <div class="alert alert-info border-info mb-3">
                            <div class="d-flex align-items-center">
                                <i class="la la-user-tie fs-24 me-2"></i>
                                <div>
                                    <strong>ارجاع شده به:</strong>
                                    <span class="badge bg-info ms-2"><?php echo esc_html($expert_name); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-flex gap-2 flex-wrap">
                        <?php if (!$expert_name): ?>
                            <button type="button" class="btn btn-info btn-wave assign-expert-btn" 
                                    data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" 
                                    data-inquiry-type="cash">
                                <i class="la la-user-plus me-1"></i>
                                ارجاع به کارشناس
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-warning btn-wave assign-expert-btn" 
                                    data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" 
                                    data-inquiry-type="cash">
                                <i class="la la-user-edit me-1"></i>
                                تغییر کارشناس
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($expert_decision === 'approved'): ?>
                            <button type="button" class="btn btn-success btn-wave" id="admin-approve-btn">
                                <i class="la la-check-circle me-1"></i>
                                تایید نهایی مدیر
                            </button>
                        <?php endif; ?>
                        
                        <button type="button" class="btn btn-danger btn-wave delete-inquiry-report-btn" 
                                data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" 
                                data-inquiry-type="cash">
                            <i class="la la-trash me-1"></i>
                            حذف درخواست
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
</div><!-- End .frontend-expert-report -->

<style>
.bg-warning-transparent {
    background: linear-gradient(135deg, rgba(255, 193, 7, 0.1) 0%, transparent 100%);
    border-bottom: 2px solid #ffc107;
}

.bg-info-transparent {
    background: linear-gradient(135deg, rgba(23, 162, 184, 0.1) 0%, transparent 100%);
    border-bottom: 2px solid #17a2b8;
}

.bg-primary-transparent {
    background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1) 0%, transparent 100%);
    border-bottom: 2px solid var(--primary-color);
}

.bg-secondary-transparent {
    background: linear-gradient(135deg, rgba(108, 117, 125, 0.1) 0%, transparent 100%);
    border-bottom: 2px solid #6c757d;
}

.bg-success-transparent {
    background: linear-gradient(135deg, rgba(40, 167, 69, 0.1) 0%, transparent 100%);
}
</style>
