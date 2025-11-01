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
$is_admin = current_user_can('manage_maneli_inquiries');
$is_assigned_expert = Maneli_Permission_Helpers::is_assigned_expert($inquiry_id, get_current_user_id());
$is_admin_or_expert = $is_admin || $is_assigned_expert;

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

<!-- Wrapper for report page detection -->
<div class="frontend-expert-report">
<div class="row" id="installment-inquiry-details" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-inquiry-type="installment">
    <div class="col-xl-12">
        <!-- Back Button -->
        <div class="mb-3 report-back-button-wrapper">
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
                <!-- Status Roadmap -->
                <div class="card custom-card mt-3">
                    <div class="card-header bg-light">
                        <div class="card-title">
                            <i class="la la-route me-2"></i>
                            <?php esc_html_e('Request Journey', 'maneli-car-inquiry'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php
                        // Define all possible statuses in order
                        $all_statuses = [
                            'new' => ['label' => 'جدید', 'icon' => 'la-file-alt', 'color' => 'secondary'],
                            'referred' => ['label' => 'ارجاع شده', 'icon' => 'la-share', 'color' => 'info'],
                            'in_progress' => ['label' => 'در حال پیگیری', 'icon' => 'la-spinner', 'color' => 'primary'],
                            'meeting_scheduled' => ['label' => 'جلسه حضوری', 'icon' => 'la-calendar-check', 'color' => 'cyan'],
                            'follow_up_scheduled' => ['label' => 'پیگیری بعدی', 'icon' => 'la-clock', 'color' => 'warning'],
                            'completed' => ['label' => 'تکمیل شده', 'icon' => 'la-check-circle', 'color' => 'success'],
                        ];
                        
                        // Special end statuses (shown separately)
                        $end_statuses = [
                            'cancelled' => ['label' => 'لغو شده', 'icon' => 'la-ban', 'color' => 'danger'],
                            'rejected' => ['label' => 'رد شده', 'icon' => 'la-times-circle', 'color' => 'danger'],
                        ];
                        
                        $current_status = $tracking_status;
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
                        <?php if (in_array($current_status, ['cancelled', 'rejected'])): ?>
                            <div class="alert alert-danger-transparent border-danger">
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm bg-danger me-2">
                                        <i class="la <?php echo $end_statuses[$current_status]['icon']; ?>"></i>
                                    </span>
                                    <strong><?php echo $end_statuses[$current_status]['label']; ?></strong>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Expert Status Control -->
                <div class="card custom-card mt-3">
                    <div class="card-header bg-gradient-primary text-white">
                        <div class="card-title text-white d-flex align-items-center justify-content-between">
                            <span>
                                <i class="la la-user-cog me-2"></i>
                                <?php esc_html_e('Expert Status Control', 'maneli-car-inquiry'); ?>
                            </span>
                            <span class="badge bg-white text-primary"><?php echo esc_html($tracking_status_label); ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php
                        // نمایش اطلاعات مربوط به وضعیت فعلی
                        $meeting_date = get_post_meta($inquiry_id, 'meeting_date', true);
                        $meeting_time = get_post_meta($inquiry_id, 'meeting_time', true);
                        $followup_date = get_post_meta($inquiry_id, 'followup_date', true);
                        $cancel_reason = get_post_meta($inquiry_id, 'cancel_reason', true);
                        $rejection_reason_exp = get_post_meta($inquiry_id, 'rejection_reason', true);
                        $followup_history = get_post_meta($inquiry_id, 'followup_history', true) ?: [];
                        
                        // Build activity timeline
                        $timeline = [];
                        
                        // Initial creation
                        $timeline[] = [
                            'time' => $post->post_date,
                            'label' => 'ایجاد درخواست',
                            'icon' => 'la-file-alt',
                            'color' => 'secondary'
                        ];
                        
                        // Assigned to expert
                        if ($expert_name) {
                            $timeline[] = [
                                'time' => get_post_meta($inquiry_id, 'referred_at', true) ?: $post->post_date,
                                'label' => 'ارجاع به کارشناس: ' . $expert_name,
                                'icon' => 'la-user-tie',
                                'color' => 'info'
                            ];
                        }
                        
                        // In progress started
                        if (in_array($tracking_status, ['in_progress', 'meeting_scheduled', 'follow_up_scheduled', 'completed', 'rejected'])) {
                            $timeline[] = [
                                'time' => get_post_meta($inquiry_id, 'in_progress_at', true) ?: '',
                                'label' => 'شروع پیگیری توسط کارشناس',
                                'icon' => 'la-spinner',
                                'color' => 'primary'
                            ];
                        }
                        
                        // Followup history
                        if (!empty($followup_history)) {
                            foreach ($followup_history as $idx => $fh) {
                                $timeline[] = [
                                    'time' => $fh['completed_at'] ?? '',
                                    'label' => 'پیگیری انجام شد (تاریخ: ' . ($fh['date'] ?? '') . ')',
                                    'icon' => 'la-check',
                                    'color' => 'success'
                                ];
                            }
                        }
                        
                        // Current followup scheduled
                        if ($followup_date && $tracking_status === 'follow_up_scheduled') {
                            $timeline[] = [
                                'time' => get_post_meta($inquiry_id, 'followup_scheduled_at', true) ?: '',
                                'label' => 'پیگیری بعدی برنامه‌ریزی شد برای: ' . $followup_date,
                                'icon' => 'la-calendar',
                                'color' => 'warning'
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
                        if ($tracking_status === 'completed') {
                            $timeline[] = [
                                'time' => get_post_meta($inquiry_id, 'completed_at', true) ?: '',
                                'label' => 'تکمیل شده',
                                'icon' => 'la-check-circle',
                                'color' => 'success'
                            ];
                        }
                        
                        // Rejected or Cancelled
                        if ($tracking_status === 'rejected') {
                            $timeline[] = [
                                'time' => get_post_meta($inquiry_id, 'rejected_at', true) ?: '',
                                'label' => 'رد شده',
                                'icon' => 'la-times-circle',
                                'color' => 'danger'
                            ];
                        }
                        
                        if ($tracking_status === 'cancelled') {
                            $timeline[] = [
                                'time' => get_post_meta($inquiry_id, 'cancelled_at', true) ?: '',
                                'label' => 'لغو شده',
                                'icon' => 'la-ban',
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
                        
                        <?php if ($meeting_date && $meeting_time): ?>
                        <div class="alert alert-info">
                            <strong><i class="la la-calendar-check me-1"></i><?php esc_html_e('Meeting Scheduled:', 'maneli-car-inquiry'); ?></strong>
                            <p class="mb-0"><?php echo esc_html($meeting_date); ?> - <?php echo esc_html($meeting_time); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($followup_date): ?>
                        <div class="alert alert-warning">
                            <strong><i class="la la-clock me-1"></i><?php esc_html_e('Follow-up Date:', 'maneli-car-inquiry'); ?></strong>
                            <p class="mb-0"><?php echo esc_html($followup_date); ?></p>
                            <?php 
                            $followup_note = get_post_meta($inquiry_id, 'followup_note', true);
                            if ($followup_note):
                            ?>
                                <small class="d-block mt-2"><strong><?php esc_html_e('Note:', 'maneli-car-inquiry'); ?></strong> <?php echo esc_html($followup_note); ?></small>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($cancel_reason): ?>
                        <div class="alert alert-danger">
                            <strong><i class="la la-ban me-1"></i><?php esc_html_e('Cancellation Reason:', 'maneli-car-inquiry'); ?></strong>
                            <p class="mb-0"><?php echo esc_html($cancel_reason); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($rejection_reason_exp): ?>
                        <div class="alert alert-danger">
                            <strong><i class="la la-times-circle me-1"></i><?php esc_html_e('Rejection Reason:', 'maneli-car-inquiry'); ?></strong>
                            <p class="mb-0"><?php echo esc_html($rejection_reason_exp); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <!-- دکمه‌های اقدام بر اساس وضعیت -->
                        <div class="d-flex gap-2 flex-wrap justify-content-center mt-3">
                            <?php if (($tracking_status === 'new' || $tracking_status === 'referred') && $is_assigned_expert && !$is_admin): ?>
                                <button type="button" class="btn btn-primary btn-wave installment-status-btn" 
                                        data-action="start_progress">
                                    <i class="la la-play-circle me-1"></i>
                                    <?php esc_html_e('Start Progress', 'maneli-car-inquiry'); ?>
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($tracking_status === 'in_progress' || $tracking_status === 'follow_up_scheduled'): ?>
                                <button type="button" class="btn btn-success btn-wave installment-status-btn" 
                                        data-action="schedule_meeting">
                                    <i class="la la-calendar-check me-1"></i>
                                    <?php esc_html_e('Schedule Meeting', 'maneli-car-inquiry'); ?>
                                </button>
                                
                                <button type="button" class="btn btn-warning btn-wave installment-status-btn" 
                                        data-action="schedule_followup">
                                    <i class="la la-clock me-1"></i>
                                    <?php esc_html_e('Schedule Follow-up', 'maneli-car-inquiry'); ?>
                                </button>
                                
                                <button type="button" class="btn btn-danger btn-wave installment-status-btn" 
                                        data-action="cancel">
                                    <i class="la la-ban me-1"></i>
                                    <?php esc_html_e('Cancel', 'maneli-car-inquiry'); ?>
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($tracking_status === 'meeting_scheduled'): ?>
                                <button type="button" class="btn btn-success btn-wave installment-status-btn" 
                                        data-action="complete">
                                    <i class="la la-check-circle me-1"></i>
                                    <?php esc_html_e('Complete', 'maneli-car-inquiry'); ?>
                                </button>
                                
                                <button type="button" class="btn btn-danger btn-wave installment-status-btn" 
                                        data-action="reject">
                                    <i class="la la-times-circle me-1"></i>
                                    <?php esc_html_e('Reject', 'maneli-car-inquiry'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Expert Notes Card -->
                <div class="card custom-card mt-3">
                    <div class="card-header bg-secondary-transparent">
                        <div class="card-title">
                            <i class="la la-sticky-note me-2"></i>
                            <?php esc_html_e('Expert Notes', 'maneli-car-inquiry'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php 
                        $expert_notes = get_post_meta($inquiry_id, 'expert_notes', true) ?: [];
                        $is_manager = current_user_can('manage_maneli_inquiries');
                        $is_expert_role = in_array('maneli_expert', wp_get_current_user()->roles, true);
                        
                        if (!empty($expert_notes)): ?>
                            <div class="<?php echo $is_manager ? '' : 'mb-3'; ?>">
                                <?php foreach (array_reverse($expert_notes) as $note): 
                                    $note_expert = isset($note['expert_id']) ? get_userdata($note['expert_id']) : null;
                                ?>
                                    <div class="alert alert-light border mb-2">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-fill">
                                                <p class="mb-1"><?php echo esc_html($note['note']); ?></p>
                                                <small class="text-muted">
                                                    <i class="la la-user me-1"></i>
                                                    <strong><?php echo $note_expert ? esc_html($note_expert->display_name) : 'کارشناس'; ?></strong>
                                                    <i class="la la-clock me-1 ms-2"></i>
                                                    <?php echo esc_html($note['created_at']); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info-transparent border-info <?php echo $is_manager ? '' : 'mb-3'; ?>">
                                <div class="d-flex align-items-center">
                                    <i class="la la-info-circle me-2 fs-18"></i>
                                    <span><?php esc_html_e('No notes have been recorded yet.', 'maneli-car-inquiry'); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($is_expert_role && !$is_manager): ?>
                            <!-- فقط کارشناس می‌تواند یادداشت ثبت کند -->
                            <form id="installment-expert-note-form">
                                <div class="input-group">
                                    <textarea id="installment-expert-note-input" class="form-control" rows="2" 
                                              placeholder="<?php esc_attr_e('Add a note...', 'maneli-car-inquiry'); ?>"></textarea>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="la la-save me-1"></i>
                                        <?php esc_html_e('Save Note', 'maneli-car-inquiry'); ?>
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
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
                        <?php elseif (current_user_can('manage_maneli_inquiries') && $expert_name): ?>
                            <button type="button" class="btn btn-warning btn-wave assign-expert-btn" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-inquiry-type="installment">
                                <i class="la la-exchange-alt me-1"></i>
                                <?php esc_html_e('Change Expert', 'maneli-car-inquiry'); ?>
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
                            <p class="mb-0 fw-semibold"><?php echo esc_html(Maneli_Render_Helpers::translate_field_value('job_type', $post_meta['job_type'][0] ?? '—')); ?></p>
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
                            <p class="mb-0 fw-semibold"><?php echo esc_html(Maneli_Render_Helpers::translate_field_value('residency_status', $post_meta['residency_status'][0] ?? '—')); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Workplace Status', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo esc_html(Maneli_Render_Helpers::translate_field_value('workplace_status', $post_meta['workplace_status'][0] ?? '—')); ?></p>
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
                            <p class="mb-0 fw-semibold"><?php echo esc_html(Maneli_Render_Helpers::translate_field_value('job_type', $post_meta['issuer_job_type'][0] ?? '—')); ?></p>
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
                            <p class="mb-0 fw-semibold"><?php echo esc_html(Maneli_Render_Helpers::translate_field_value('residency_status', $post_meta['issuer_residency_status'][0] ?? '—')); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Workplace Status', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo esc_html(Maneli_Render_Helpers::translate_field_value('workplace_status', $post_meta['issuer_workplace_status'][0] ?? '—')); ?></p>
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

/* Responsive roadmap for mobile */
@media (max-width: 768px) {
    .status-roadmap .d-flex {
        flex-direction: column !important;
    }
    
    .status-arrow {
        transform: rotate(90deg);
        margin: 10px 0;
    }
    
    .status-arrow i {
        transform: rotate(-90deg);
    }
}
</style>
</div><!-- End .frontend-expert-report -->
<?php } // End of permission check ?>
