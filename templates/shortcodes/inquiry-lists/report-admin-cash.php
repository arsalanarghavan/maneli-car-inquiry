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
        
        <?php if ($is_admin || $is_assigned_expert): ?>
        <!-- Status Roadmap -->
        <div class="card custom-card mt-3">
            <div class="card-header bg-light">
                <div class="card-title">
                    <i class="la la-route me-2"></i>
                    مسیر درخواست
                </div>
            </div>
            <div class="card-body">
                <?php
                        // Define all possible statuses in order
                        $all_statuses = [
                            'new' => ['label' => 'جدید', 'icon' => 'la-file-alt', 'color' => 'secondary'],
                            'referred' => ['label' => 'ارجاع شده', 'icon' => 'la-share', 'color' => 'info'],
                            'in_progress' => ['label' => 'در حال پیگیری', 'icon' => 'la-spinner', 'color' => 'primary'],
                            'follow_up_scheduled' => ['label' => 'پیگیری بعدی', 'icon' => 'la-clock', 'color' => 'warning'],
                            'awaiting_downpayment' => ['label' => 'انتظار پیش‌پرداخت', 'icon' => 'la-dollar-sign', 'color' => 'warning'],
                            'downpayment_received' => ['label' => 'پیش‌پرداخت دریافت شد', 'icon' => 'la-check-double', 'color' => 'success-light'],
                            'meeting_scheduled' => ['label' => 'جلسه حضوری', 'icon' => 'la-calendar-check', 'color' => 'cyan'],
                            'completed' => ['label' => 'تکمیل شده', 'icon' => 'la-check-circle', 'color' => 'dark'],
                        ];
                        
                        // Special end statuses (shown separately)
                        $end_statuses = [
                            'rejected' => ['label' => 'رد شده', 'icon' => 'la-times-circle', 'color' => 'danger'],
                        ];
                
                $current_status = $status;
                $status_reached = false;
                ?>
                
                <!-- Main Flow -->
                <div class="status-roadmap mb-3">
                    <div class="d-flex align-items-center justify-content-between flex-wrap">
                        <?php foreach ($all_statuses as $status_key => $status_info): 
                            $is_current = ($status_key === $current_status);
                            $is_passed = !$is_current && !$status_reached;
                            
                            if ($is_current) {
                                $status_reached = true;
                            }
                            
                            $opacity = $is_passed ? '1' : ($is_current ? '1' : '0.3');
                            $badge_class = $is_current ? 'bg-' . $status_info['color'] : ($is_passed ? 'bg-success-light' : 'bg-light text-muted');
                        ?>
                            <div class="status-step text-center" style="opacity: <?php echo $opacity; ?>; flex: 1; position: relative;">
                                <?php if ($is_current): ?>
                                    <div class="pulse-ring"></div>
                                <?php endif; ?>
                                <div class="mb-2">
                                    <span class="avatar avatar-md <?php echo $badge_class; ?> rounded-circle">
                                        <i class="la <?php echo $status_info['icon']; ?> fs-20"></i>
                                    </span>
                                </div>
                                <small class="d-block fw-semibold <?php echo $is_current ? 'text-' . $status_info['color'] : ''; ?>">
                                    <?php echo $status_info['label']; ?>
                                </small>
                                <?php if ($is_current): ?>
                                    <div class="mt-1">
                                        <span class="badge bg-<?php echo $status_info['color']; ?>-transparent fs-11">
                                            <i class="la la-map-marker me-1"></i>وضعیت فعلی
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if ($status_key !== 'completed'): ?>
                                <div class="status-arrow" style="opacity: <?php echo $opacity; ?>;">
                                    <i class="la la-arrow-left fs-18 text-muted"></i>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- End Statuses -->
                <?php if ($current_status === 'rejected'): ?>
                            <div class="alert alert-danger-transparent border-danger">
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm bg-danger me-2">
                                        <i class="la la-times-circle"></i>
                                    </span>
                                    <strong>رد شده</strong>
                                </div>
                            </div>
                <?php endif; ?>
            </div>
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
                    <?php
                    // Build activity timeline
                    $timeline = [];
                    
                    // Initial creation
                    $timeline[] = [
                        'time' => $inquiry->post_date,
                        'label' => 'ایجاد درخواست',
                        'icon' => 'la-file-alt',
                        'color' => 'secondary'
                    ];
                    
                    // Assigned to expert
                    if ($expert_name) {
                        $timeline[] = [
                            'time' => get_post_meta($inquiry_id, 'referred_at', true) ?: $inquiry->post_date,
                            'label' => 'ارجاع به کارشناس: ' . $expert_name,
                            'icon' => 'la-user-tie',
                            'color' => 'info'
                        ];
                    }
                    
                    // In progress started
                    if (in_array($status, ['in_progress', 'awaiting_downpayment', 'downpayment_received', 'meeting_scheduled', 'completed', 'rejected'])) {
                        $timeline[] = [
                            'time' => get_post_meta($inquiry_id, 'in_progress_at', true) ?: '',
                            'label' => 'شروع پیگیری توسط کارشناس',
                            'icon' => 'la-spinner',
                            'color' => 'primary'
                        ];
                    }
                    
                    // Down payment requested
                    if (in_array($status, ['awaiting_downpayment', 'downpayment_received', 'meeting_scheduled', 'completed', 'rejected'])) {
                        $down_payment_amount = get_post_meta($inquiry_id, 'cash_down_payment', true);
                        $timeline[] = [
                            'time' => get_post_meta($inquiry_id, 'downpayment_requested_at', true) ?: '',
                            'label' => 'درخواست پیش‌پرداخت: ' . number_format_i18n($down_payment_amount) . ' تومان',
                            'icon' => 'la-dollar-sign',
                            'color' => 'warning'
                        ];
                    }
                    
                    // Down payment received
                    if (in_array($status, ['downpayment_received', 'meeting_scheduled', 'completed', 'rejected'])) {
                        $timeline[] = [
                            'time' => get_post_meta($inquiry_id, 'downpayment_received_at', true) ?: '',
                            'label' => 'پیش‌پرداخت دریافت شد',
                            'icon' => 'la-check-circle',
                            'color' => 'success'
                        ];
                    }
                    
                    // Meeting scheduled
                    if ($meeting_date && $meeting_time) {
                        $timeline[] = [
                            'time' => get_post_meta($inquiry_id, 'meeting_scheduled_at', true) ?: '',
                            'label' => 'جلسه حضوری: ' . $meeting_date . ' - ' . $meeting_time,
                            'icon' => 'la-handshake',
                            'color' => 'cyan'
                        ];
                    }
                    
                    // Completed
                    if ($status === 'completed') {
                        $timeline[] = [
                            'time' => get_post_meta($inquiry_id, 'completed_at', true) ?: '',
                            'label' => 'تایید نهایی و تکمیل',
                            'icon' => 'la-check-circle',
                            'color' => 'dark'
                        ];
                    }
                    
                    // Rejected
                    if ($status === 'rejected') {
                        $timeline[] = [
                            'time' => get_post_meta($inquiry_id, 'rejected_at', true) ?: '',
                            'label' => 'رد شده',
                            'icon' => 'la-times-circle',
                            'color' => 'danger'
                        ];
                    }
                    
                    // Sort timeline by time (newest first)
                    usort($timeline, function($a, $b) {
                        return strtotime($b['time']) - strtotime($a['time']);
                    });
                    ?>
                    
                    <!-- Activity Timeline -->
                    <div class="alert alert-light border mb-3">
                        <h6 class="fw-semibold mb-3">
                            <i class="la la-history me-2"></i>
                            تایم‌لاین فعالیت‌ها
                        </h6>
                        <div class="activity-timeline">
                            <?php foreach ($timeline as $activity): ?>
                                <?php if (!empty($activity['time'])): 
                                    // تبدیل تاریخ به شمسی
                                    $jalali_date = Maneli_Render_Helpers::maneli_gregorian_to_jalali($activity['time'], 'Y/m/d H:i');
                                ?>
                                    <div class="timeline-item d-flex align-items-start mb-2">
                                        <span class="avatar avatar-sm bg-<?php echo $activity['color']; ?>-transparent me-2">
                                            <i class="la <?php echo $activity['icon']; ?>"></i>
                                        </span>
                                        <div class="flex-fill">
                                            <strong class="d-block"><?php echo esc_html($activity['label']); ?></strong>
                                            <small class="text-muted">
                                                <?php echo esc_html($jalali_date); ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2 flex-wrap">
                        <?php if (in_array($status, ['referred', 'new']) && $is_assigned_expert && !$is_admin): ?>
                            <button type="button" class="btn btn-primary btn-wave" id="set-in-progress-btn">
                                <i class="la la-play me-1"></i>
                                شروع پیگیری
                            </button>
                        <?php endif; ?>
                        
                        <?php if (in_array($status, ['in_progress', 'follow_up_scheduled'])): ?>
                            <button type="button" class="btn btn-warning btn-wave" id="request-downpayment-btn">
                                <i class="la la-money-bill me-1"></i>
                                درخواست پیش‌پرداخت
                            </button>
                            
                            <button type="button" class="btn btn-info btn-wave cash-status-btn" data-action="schedule_followup">
                                <i class="la la-clock me-1"></i>
                                ثبت پیگیری بعدی
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

            <!-- Expert Notes -->
            <div class="card border mb-4">
                <div class="card-header bg-secondary-transparent">
                    <h6 class="card-title mb-0">
                        <i class="la la-sticky-note text-secondary me-2"></i>
                        یادداشت‌های کارشناس
                    </h6>
                </div>
                <div class="card-body">
                    <?php 
                    $expert_notes = get_post_meta($inquiry_id, 'expert_notes', true) ?: [];
                    if (!empty($expert_notes)): ?>
                        <div class="mb-3">
                            <?php foreach (array_reverse($expert_notes) as $note): 
                                $note_expert = isset($note['expert_id']) ? get_userdata($note['expert_id']) : null;
                                $jalali_date = Maneli_Render_Helpers::maneli_gregorian_to_jalali($note['created_at'], 'Y/m/d H:i');
                            ?>
                                <div class="alert alert-light border mb-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-fill">
                                            <p class="mb-1"><?php echo esc_html($note['note']); ?></p>
                                            <small class="text-muted">
                                                <i class="la la-user me-1"></i>
                                                <strong><?php echo $note_expert ? esc_html($note_expert->display_name) : 'کارشناس'; ?></strong>
                                                <i class="la la-clock me-1 ms-2"></i>
                                                <?php echo esc_html($jalali_date); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form id="cash-expert-note-form">
                        <div class="input-group">
                            <textarea id="cash-expert-note-input" class="form-control" rows="2" 
                                      placeholder="یادداشت خود را وارد کنید..."></textarea>
                            <button type="submit" class="btn btn-secondary">
                                <i class="la la-save me-1"></i>
                                ذخیره یادداشت
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Admin View - Expert's Notes -->
        <?php if ($is_admin): ?>
            <!-- Expert's Notes (Read-only for Admin) -->
            <div class="card border mb-4">
                <div class="card-header bg-secondary-transparent">
                    <h6 class="card-title mb-0">
                        <i class="la la-sticky-note text-secondary me-2"></i>
                        یادداشت‌های کارشناس
                    </h6>
                </div>
                <div class="card-body">
                    <?php 
                    $expert_notes = get_post_meta($inquiry_id, 'expert_notes', true) ?: [];
                    if (!empty($expert_notes)): ?>
                        <div>
                            <?php foreach (array_reverse($expert_notes) as $note): 
                                $note_expert = isset($note['expert_id']) ? get_userdata($note['expert_id']) : null;
                                $jalali_date = Maneli_Render_Helpers::maneli_gregorian_to_jalali($note['created_at'], 'Y/m/d H:i');
                            ?>
                                <div class="alert alert-light border mb-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-fill">
                                            <p class="mb-1"><?php echo esc_html($note['note']); ?></p>
                                            <small class="text-muted">
                                                <i class="la la-user me-1"></i>
                                                <strong><?php echo $note_expert ? esc_html($note_expert->display_name) : 'کارشناس'; ?></strong>
                                                <i class="la la-clock me-1 ms-2"></i>
                                                <?php echo esc_html($jalali_date); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info-transparent border-info">
                            <div class="d-flex align-items-center">
                                <i class="la la-info-circle me-2 fs-18"></i>
                                <span>هیچ یادداشتی ثبت نشده است.</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

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

<style>
/* Status Roadmap Styles */
.status-roadmap {
    padding: 20px 10px;
}

.status-step {
    min-width: 100px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
}

.status-arrow {
    padding: 0 15px;
    display: flex;
    align-items: center;
}

/* Make inactive statuses more visible */
.status-step[style*="opacity: 0.3"] {
    opacity: 0.5 !important;
}

.status-step[style*="opacity: 0.3"] .avatar {
    background-color: #e9ecef !important;
}

/* Pulse Animation for Current Status */
.pulse-ring {
    position: absolute;
    top: -5px;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 60px;
    border: 3px solid var(--primary-color);
    border-radius: 50%;
    animation: pulse-ring 1.5s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
    z-index: -1;
}

@keyframes pulse-ring {
    0% {
        transform: translateX(-50%) scale(0.9);
        opacity: 1;
    }
    50% {
        transform: translateX(-50%) scale(1.1);
        opacity: 0.7;
    }
    100% {
        transform: translateX(-50%) scale(0.9);
        opacity: 1;
    }
}

/* Responsive roadmap for mobile */
@media (max-width: 768px) {
    .status-roadmap .d-flex {
        flex-direction: column !important;
    }
    
    .status-arrow {
        transform: rotate(90deg);
        margin: 10px 0;
    }
}
</style>
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
