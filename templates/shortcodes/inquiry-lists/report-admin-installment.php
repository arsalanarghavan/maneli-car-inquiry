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

// Ensure required classes are loaded
if (!class_exists('Maneli_Permission_Helpers')) {
    require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/helpers/class-maneli-permission-helpers.php';
}
if (!class_exists('Maneli_CPT_Handler')) {
    require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/class-cpt-handler.php';
}
if (!class_exists('Maneli_Render_Helpers')) {
    require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/helpers/class-maneli-render-helpers.php';
}

// Permission Check
$can_view = Maneli_Permission_Helpers::can_user_view_inquiry($inquiry_id, get_current_user_id());

if (!$can_view) {
    echo '<div class="alert alert-danger">' . esc_html__('Inquiry not found or you do not have access.', 'maneli-car-inquiry') . '</div>';
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

$back_link = home_url('/dashboard/installment-inquiries');
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
<div class="main-content app-content">
    <div class="container-fluid">
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
                    <small class="text-muted">(#<?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($inquiry_id) : esc_html($inquiry_id); ?>)</small>
                </div>
            </div>
            <div class="card-body">
                <!-- Status Alert -->
                <div class="alert alert-<?php echo esc_attr($tracking_status === 'completed' ? 'success' : ($tracking_status === 'rejected' || $tracking_status === 'cancelled' ? 'danger' : ($tracking_status === 'meeting_scheduled' ? 'info' : ($tracking_status === 'in_progress' || $tracking_status === 'follow_up_scheduled' ? 'primary' : ($tracking_status === 'referred' ? 'info' : 'warning'))))); ?>">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <strong><?php esc_html_e('Current Status:', 'maneli-car-inquiry'); ?></strong> 
                            <span class="badge bg-<?php echo esc_attr($tracking_status === 'completed' ? 'success' : ($tracking_status === 'rejected' || $tracking_status === 'cancelled' ? 'danger' : ($tracking_status === 'meeting_scheduled' ? 'info' : ($tracking_status === 'in_progress' || $tracking_status === 'follow_up_scheduled' ? 'primary' : ($tracking_status === 'referred' ? 'info' : 'warning'))))); ?>-transparent fs-14 ms-2">
                                <?php echo esc_html($tracking_status_label); ?>
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
                            'new' => ['label' => esc_html__('New', 'maneli-car-inquiry'), 'icon' => 'la-folder-open', 'color' => 'secondary'],
                            'referred' => ['label' => esc_html__('Referred', 'maneli-car-inquiry'), 'icon' => 'la-share', 'color' => 'info'],
                            'in_progress' => ['label' => esc_html__('In Progress', 'maneli-car-inquiry'), 'icon' => 'la-spinner', 'color' => 'primary'],
                            'follow_up_scheduled' => ['label' => esc_html__('Follow-up Scheduled', 'maneli-car-inquiry'), 'icon' => 'la-clock', 'color' => 'warning'],
                            'meeting_scheduled' => ['label' => esc_html__('Meeting Scheduled', 'maneli-car-inquiry'), 'icon' => 'la-calendar-check', 'color' => 'cyan'],
                        ];
                        
                        // End statuses (completed or rejected)
                        $end_statuses = [
                            'completed' => ['label' => esc_html__('Completed', 'maneli-car-inquiry'), 'icon' => 'la-check-circle', 'color' => 'success'],
                            'rejected' => ['label' => esc_html__('Rejected', 'maneli-car-inquiry'), 'icon' => 'la-times-circle', 'color' => 'danger'],
                            'cancelled' => ['label' => esc_html__('Cancelled', 'maneli-car-inquiry'), 'icon' => 'la-ban', 'color' => 'danger'],
                        ];
                        
                        $current_status = $tracking_status;
                        $status_reached = false;
                        
                        // Check if current status is an end status
                        $is_end_status = in_array($current_status, ['completed', 'rejected', 'cancelled']);
                        if ($is_end_status) {
                            $status_reached = true; // Mark all previous statuses as passed
                        }
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
                                    <div class="status-step text-center maneli-status-step" style="opacity: <?php echo esc_attr($opacity); ?>; flex: 1; position: relative;">
                                        <?php if ($is_current): ?>
                                            <div class="pulse-ring"></div>
                                        <?php endif; ?>
                                        <div class="mb-2">
                                            <span class="avatar avatar-md <?php echo esc_attr($badge_class); ?> rounded-circle status-icon-wrapper">
                                                <span class="status-icon-loading">
                                                    <span class="spinner-border spinner-border-sm text-white" role="status"></span>
                                                </span>
                                                <i class="la <?php echo esc_attr($status_info['icon']); ?> fs-20 status-icon"></i>
                                            </span>
                                        </div>
                                        <small class="d-block fw-semibold <?php echo esc_attr($is_current ? 'text-' . $status_info['color'] : ''); ?>">
                                            <?php echo esc_html($status_info['label']); ?>
                                        </small>
                                        <?php if ($is_current): ?>
                                            <div class="mt-1">
                                                <span class="badge bg-<?php echo esc_attr($status_info['color']); ?>-transparent fs-11">
                                                    <i class="la la-map-marker me-1"></i><?php esc_html_e('Current Status', 'maneli-car-inquiry'); ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="status-arrow maneli-status-arrow" style="opacity: <?php echo esc_attr($opacity); ?>;">
                                        <i class="la la-arrow-left fs-18 text-muted"></i>
                                    </div>
                                <?php endforeach; ?>
                                
                                <!-- End Status: Completed or Rejected -->
                                <?php 
                                $end_status_info = isset($end_statuses[$current_status]) ? $end_statuses[$current_status] : $end_statuses['completed'];
                                
                                $end_opacity = $is_end_status ? '1' : '0.3';
                                $end_badge_class = $is_end_status ? 'bg-' . $end_status_info['color'] : 'bg-light text-muted';
                                ?>
                                <div class="status-step text-center maneli-status-step" style="opacity: <?php echo esc_attr($end_opacity); ?>; flex: 1; position: relative;">
                                    <?php if ($is_end_status): ?>
                                        <div class="pulse-ring"></div>
                                    <?php endif; ?>
                                    <div class="mb-2">
                                        <span class="avatar avatar-md <?php echo esc_attr($end_badge_class); ?> rounded-circle status-icon-wrapper">
                                            <span class="status-icon-loading">
                                                <span class="spinner-border spinner-border-sm text-white" role="status"></span>
                                            </span>
                                            <i class="la <?php echo esc_attr($end_status_info['icon']); ?> fs-20 status-icon"></i>
                                        </span>
                                    </div>
                                    <small class="d-block fw-semibold <?php echo esc_attr($is_end_status ? 'text-' . $end_status_info['color'] : ''); ?>">
                                        <?php echo $is_end_status ? esc_html($end_status_info['label']) : esc_html__('Completed / Rejected', 'maneli-car-inquiry'); ?>
                                    </small>
                                    <?php if ($is_end_status): ?>
                                        <div class="mt-1">
                                            <span class="badge bg-<?php echo esc_attr($end_status_info['color']); ?>-transparent fs-11">
                                                <i class="la la-map-marker me-1"></i><?php esc_html_e('Current Status', 'maneli-car-inquiry'); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Expert Status Control -->
                <div class="card custom-card mt-3">
                    <div class="card-header bg-primary-gradient">
                        <div class="card-title text-fixed-white d-flex align-items-center justify-content-between">
                            <span>
                                <i class="la la-user-cog me-2"></i>
                                <?php esc_html_e('Expert Status Control', 'maneli-car-inquiry'); ?>
                            </span>
                            <span class="badge bg-white text-primary"><?php echo esc_html($tracking_status_label); ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php
                        // Display information about current status
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
                            'label' => esc_html__('Request Created', 'maneli-car-inquiry'),
                            'icon' => 'la-file-alt',
                            'color' => 'secondary'
                        ];
                        
                        // Assigned to expert
                        if ($expert_name) {
                            $timeline[] = [
                                'time' => get_post_meta($inquiry_id, 'referred_at', true) ?: $post->post_date,
                                'label' => esc_html__('Assigned to Expert:', 'maneli-car-inquiry') . ' ' . $expert_name,
                                'icon' => 'la-user-tie',
                                'color' => 'info'
                            ];
                        }
                        
                        // In progress started
                        if (in_array($tracking_status, ['in_progress', 'meeting_scheduled', 'follow_up_scheduled', 'completed', 'rejected'])) {
                            $timeline[] = [
                                'time' => get_post_meta($inquiry_id, 'in_progress_at', true) ?: '',
                                'label' => esc_html__('Follow-up Started by Expert', 'maneli-car-inquiry'),
                                'icon' => 'la-spinner',
                                'color' => 'primary'
                            ];
                        }
                        
                        // Followup history
                        if (!empty($followup_history)) {
                            foreach ($followup_history as $idx => $fh) {
                                $fh_date = $fh['date'] ?? '';
                                if ($fh_date) {
                                    $fh_date = function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($fh_date) : $fh_date;
                                }
                                $timeline[] = [
                                    'time' => $fh['completed_at'] ?? '',
                                    'label' => esc_html__('Follow-up Completed (Date:', 'maneli-car-inquiry') . ' ' . $fh_date . ')',
                                    'icon' => 'la-check',
                                    'color' => 'success'
                                ];
                            }
                        }
                        
                        // Current followup scheduled
                        if ($followup_date && $tracking_status === 'follow_up_scheduled') {
                            $followup_date_label = function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($followup_date) : $followup_date;
                            $timeline[] = [
                                'time' => get_post_meta($inquiry_id, 'followup_scheduled_at', true) ?: '',
                                'label' => esc_html__('Next Follow-up Scheduled for:', 'maneli-car-inquiry') . ' ' . $followup_date_label,
                                'icon' => 'la-calendar',
                                'color' => 'warning'
                            ];
                        }
                        
                        // Meeting scheduled
                        if ($meeting_date && $meeting_time) {
                            $meeting_date_label = function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($meeting_date) : $meeting_date;
                            $meeting_time_label = function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($meeting_time) : $meeting_time;
                            $timeline[] = [
                                'time' => get_post_meta($inquiry_id, 'meeting_scheduled_at', true) ?: '',
                                'label' => esc_html__('Meeting Scheduled:', 'maneli-car-inquiry') . ' ' . $meeting_date_label . ' - ' . $meeting_time_label,
                                'icon' => 'la-handshake',
                                'color' => 'cyan'
                            ];
                        }
                        
                        // Completed
                        if ($tracking_status === 'completed') {
                            $timeline[] = [
                                'time' => get_post_meta($inquiry_id, 'completed_at', true) ?: '',
                                'label' => esc_html__('Completed', 'maneli-car-inquiry'),
                                'icon' => 'la-check-circle',
                                'color' => 'success'
                            ];
                        }
                        
                        // Rejected or Cancelled
                        if ($tracking_status === 'rejected') {
                            $timeline[] = [
                                'time' => get_post_meta($inquiry_id, 'rejected_at', true) ?: '',
                                'label' => esc_html__('Rejected', 'maneli-car-inquiry'),
                                'icon' => 'la-times-circle',
                                'color' => 'danger'
                            ];
                        }
                        
                        if ($tracking_status === 'cancelled') {
                            $timeline[] = [
                                'time' => get_post_meta($inquiry_id, 'cancelled_at', true) ?: '',
                                'label' => esc_html__('Cancelled', 'maneli-car-inquiry'),
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
                                <?php esc_html_e('Activity Timeline', 'maneli-car-inquiry'); ?>
                            </h6>
                            <div class="activity-timeline">
                                <?php foreach ($timeline as $activity): ?>
                                    <?php if (!empty($activity['time'])): 
                                        // تبدیل تاریخ به شمسی
                                        $jalali_date = Maneli_Render_Helpers::maneli_gregorian_to_jalali($activity['time'], 'Y/m/d H:i');
                                        // تبدیل اعداد به فارسی
                                        $jalali_date = function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($jalali_date) : $jalali_date;
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
                            <p class="mb-0"><?php 
                                $meeting_date_fa = function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($meeting_date) : $meeting_date;
                                $meeting_time_fa = function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($meeting_time) : $meeting_time;
                                echo esc_html($meeting_date_fa . ' - ' . $meeting_time_fa);
                            ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($followup_date): ?>
                        <div class="alert alert-warning">
                            <strong><i class="la la-clock me-1"></i><?php esc_html_e('Follow-up Date:', 'maneli-car-inquiry'); ?></strong>
                            <p class="mb-0"><?php 
                                $followup_date_fa = function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($followup_date) : $followup_date;
                                echo esc_html($followup_date_fa);
                            ?></p>
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
                        
                        <!-- Action buttons based on status -->
                        <div class="d-flex gap-2 flex-wrap justify-content-center mt-3">
                            <?php if (($tracking_status === 'new' || $tracking_status === 'referred') && $is_assigned_expert && !$is_admin): ?>
                                <button type="button" class="btn btn-primary btn-wave installment-status-btn" 
                                        data-action="start_progress"
                                        data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>">
                                    <i class="la la-play-circle me-1"></i>
                                    <?php esc_html_e('Start Progress', 'maneli-car-inquiry'); ?>
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($tracking_status === 'in_progress' || $tracking_status === 'follow_up_scheduled'): ?>
                                <button type="button" class="btn btn-success btn-wave installment-status-btn" 
                                        data-action="approve"
                                        data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>">
                                    <i class="la la-check-circle me-1"></i>
                                    <?php esc_html_e('Approve', 'maneli-car-inquiry'); ?>
                                </button>
                                
                                <button type="button" class="btn btn-danger btn-wave installment-status-btn" 
                                        data-action="reject"
                                        data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>">
                                    <i class="la la-times-circle me-1"></i>
                                    <?php esc_html_e('Reject', 'maneli-car-inquiry'); ?>
                                </button>
                                
                                <button type="button" class="btn btn-info btn-wave installment-status-btn" 
                                        data-action="schedule_meeting"
                                        data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>">
                                    <i class="la la-calendar-check me-1"></i>
                                    <?php esc_html_e('Schedule Meeting', 'maneli-car-inquiry'); ?>
                                </button>
                                
                                <button type="button" class="btn btn-warning btn-wave installment-status-btn" 
                                        data-action="schedule_followup"
                                        data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>">
                                    <i class="la la-clock me-1"></i>
                                    <?php esc_html_e('Schedule Follow-up', 'maneli-car-inquiry'); ?>
                                </button>
                                
                                <button type="button" class="btn btn-secondary btn-wave installment-status-btn" 
                                        data-action="cancel"
                                        data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>">
                                    <i class="la la-ban me-1"></i>
                                    <?php esc_html_e('Cancel', 'maneli-car-inquiry'); ?>
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($tracking_status === 'meeting_scheduled'): ?>
                                <button type="button" class="btn btn-success btn-wave installment-status-btn" 
                                        data-action="complete"
                                        data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>">
                                    <i class="la la-check-circle me-1"></i>
                                    <?php esc_html_e('Complete', 'maneli-car-inquiry'); ?>
                                </button>
                                
                                <button type="button" class="btn btn-danger btn-wave installment-status-btn" 
                                        data-action="reject"
                                        data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>">
                                    <i class="la la-times-circle me-1"></i>
                                    <?php esc_html_e('Reject', 'maneli-car-inquiry'); ?>
                                </button>
                                
                                <button type="button" class="btn btn-warning btn-wave installment-cancel-meeting-btn" 
                                        data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>"
                                        data-inquiry-type="installment">
                                    <i class="la la-calendar-times me-1"></i>
                                    <?php esc_html_e('Cancel Meeting', 'maneli-car-inquiry'); ?>
                                </button>
                                
                                <button type="button" class="btn btn-info btn-wave installment-status-btn" 
                                        data-action="schedule_followup"
                                        data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>">
                                    <i class="la la-clock me-1"></i>
                                    <?php esc_html_e('Schedule Follow-up', 'maneli-car-inquiry'); ?>
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
                                                    <strong><?php echo $note_expert ? esc_html($note_expert->display_name) : esc_html__('Expert', 'maneli-car-inquiry'); ?></strong>
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
                            <!-- Only expert can add notes -->
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
                            echo wp_kses_post($car_image);
                        } else {
                            echo '<div class="bg-light rounded d-flex align-items-center justify-content-center text-muted maneli-placeholder-image">
                                <div class="text-center">
                                    <i class="la la-image fs-40"></i>
                                    <p class="mb-0 mt-2">' . esc_html__('No Image', 'maneli-car-inquiry') . '</p>
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
                                        <td><strong class="text-success"><?php echo function_exists('persian_numbers') ? persian_numbers(Maneli_Render_Helpers::format_money($total_price)) : Maneli_Render_Helpers::format_money($total_price); ?></strong> <?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold bg-light"><?php esc_html_e('Down Payment', 'maneli-car-inquiry'); ?></td>
                                        <td><strong class="text-info"><?php echo function_exists('persian_numbers') ? persian_numbers(Maneli_Render_Helpers::format_money($down_payment)) : Maneli_Render_Helpers::format_money($down_payment); ?></strong> <?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold bg-light"><?php esc_html_e('Loan Amount', 'maneli-car-inquiry'); ?></td>
                                        <td><strong class="text-warning"><?php echo function_exists('persian_numbers') ? persian_numbers(Maneli_Render_Helpers::format_money($loan_amount)) : Maneli_Render_Helpers::format_money($loan_amount); ?></strong> <?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold bg-light"><?php esc_html_e('Installment Term', 'maneli-car-inquiry'); ?></td>
                                        <td><span class="badge bg-secondary-transparent"><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($term_months) : absint($term_months); ?> <?php esc_html_e('Months', 'maneli-car-inquiry'); ?></span></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold bg-light"><?php esc_html_e('Monthly Installment', 'maneli-car-inquiry'); ?></td>
                                        <td><strong class="text-primary fs-16"><?php echo function_exists('persian_numbers') ? persian_numbers(Maneli_Render_Helpers::format_money($installment_amount)) : Maneli_Render_Helpers::format_money($installment_amount); ?></strong> <?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold bg-light"><?php esc_html_e('Date Submitted', 'maneli-car-inquiry'); ?></td>
                                        <td><?php 
                                            $formatted_date = Maneli_Render_Helpers::maneli_gregorian_to_jalali($post->post_date, 'Y/m/d H:i');
                                            echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($formatted_date) : $formatted_date;
                                        ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <?php if (current_user_can('manage_maneli_inquiries')): ?>
            <div class="card border-primary mt-3">
                <div class="card-header bg-primary-transparent">
                    <h6 class="card-title mb-0">
                        <i class="la la-shield-alt text-primary me-2"></i>
                        <?php esc_html_e('Admin Actions', 'maneli-car-inquiry'); ?>
                    </h6>
                </div>
                <div class="card-body">
                    <?php if ($expert_name): ?>
                        <!-- Show assigned expert -->
                        <div class="alert alert-info border-info mb-3">
                            <div class="d-flex align-items-center">
                                <i class="la la-user-tie fs-24 me-2"></i>
                                <div>
                                    <strong><?php esc_html_e('Assigned to:', 'maneli-car-inquiry'); ?></strong>
                                    <span class="badge bg-info ms-2"><?php echo esc_html($expert_name); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-flex gap-2 flex-wrap">
                        <?php if ($inquiry_status === 'pending' || $inquiry_status === 'more_docs' || $inquiry_status === 'failed'): ?>
                            <button type="button" class="btn btn-danger btn-wave reject-inquiry-btn" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-inquiry-type="installment">
                                <i class="la la-times-circle me-1"></i>
                                <?php esc_html_e('Final Rejection of Request', 'maneli-car-inquiry'); ?>
                            </button>
                        <?php endif; ?>

                        <?php if (!$expert_name): ?>
                            <button type="button" class="btn btn-info btn-wave assign-expert-btn" 
                                    data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" 
                                    data-inquiry-type="installment">
                                <i class="la la-user-plus me-1"></i>
                                <?php esc_html_e('Assign to Expert', 'maneli-car-inquiry'); ?>
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-warning btn-wave assign-expert-btn" 
                                    data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" 
                                    data-inquiry-type="installment">
                                <i class="la la-user-edit me-1"></i>
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
                                <button type="button" class="btn btn-secondary btn-wave request-more-docs-btn" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-inquiry-type="installment">
                                    <i class="la la-file-alt me-1"></i>
                                    <?php esc_html_e('Request More Documents', 'maneli-car-inquiry'); ?>
                                </button>
                            <?php endif; ?>

                            <?php 
                            $customer_mobile = $post_meta['mobile_number'][0] ?? '';
                            if (!empty($customer_mobile)): ?>
                            <button type="button" class="btn btn-success btn-wave send-sms-report-btn" 
                                    data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" 
                                    data-phone="<?php echo esc_attr($customer_mobile); ?>"
                                    data-customer-name="<?php echo esc_attr($customer_name); ?>">
                                <i class="la la-sms me-1"></i>
                                <?php esc_html_e('Send SMS', 'maneli-car-inquiry'); ?>
                            </button>
                            <?php endif; ?>

                            <button type="button" class="btn btn-danger btn-wave delete-inquiry-report-btn" 
                                    data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" 
                                    data-inquiry-type="installment">
                                <i class="la la-trash me-1"></i>
                                <?php esc_html_e('Delete Inquiry', 'maneli-car-inquiry'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <?php
                    // Check if documents have been requested or uploaded
                    $requested_docs = get_post_meta($inquiry_id, 'requested_documents', true) ?: [];
                    $uploaded_docs = get_post_meta($inquiry_id, 'uploaded_documents', true) ?: [];
                    ?>
                    
                    <?php if (!empty($requested_docs) || !empty($uploaded_docs)): ?>
                        <div class="mt-3 pt-3 border-top">
                            <h6 class="fw-semibold mb-3">
                                <i class="la la-file-alt me-2"></i>
                                <?php esc_html_e('Submitted Documents', 'maneli-car-inquiry'); ?>
                            </h6>
                            
                            <?php if (empty($uploaded_docs) && !empty($requested_docs)): ?>
                                <div class="alert alert-info border-info">
                                    <i class="la la-info-circle me-2"></i>
                                    <?php esc_html_e('Request sent to user', 'maneli-car-inquiry'); ?>
                                </div>
                            <?php elseif (!empty($uploaded_docs)): ?>
                                <div class="document-list">
                                    <?php foreach ($uploaded_docs as $doc): ?>
                                        <div class="border rounded p-3 mb-2 d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="la la-file me-2 text-primary"></i>
                                                <strong><?php echo esc_html($doc['name'] ?? 'Document'); ?></strong>
                                                <?php if (isset($doc['uploaded_at'])): ?>
                                                    <br><small class="text-muted"><?php echo esc_html($doc['uploaded_at']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (isset($doc['file'])): ?>
                                                <a href="<?php echo esc_url($doc['file']); ?>" target="_blank" class="btn btn-sm btn-primary">
                                                    <i class="la la-download"></i> <?php esc_html_e('Download', 'maneli-car-inquiry'); ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
            </div>
        </div>

        <!-- Buyer Information Card -->
        <div class="card custom-card mt-3">
            <div class="card-header">
                <div class="card-title d-flex justify-content-between align-items-center">
                    <span>
                        <i class="la la-user me-2"></i>
                        <?php esc_html_e('Buyer Information', 'maneli-car-inquiry'); ?>
                    </span>
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
                            <p class="mb-0 fw-semibold"><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($post_meta['national_code'][0] ?? '—') : esc_html($post_meta['national_code'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Date of Birth', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php 
                                $birth_date = $post_meta['birth_date'][0] ?? '—';
                                if ($birth_date && $birth_date !== '—') {
                                    // Try to convert if it's in Gregorian format
                                    if (strpos($birth_date, '/') !== false || strpos($birth_date, '-') !== false) {
                                        $birth_date = Maneli_Render_Helpers::maneli_gregorian_to_jalali($birth_date, 'Y/m/d');
                                    }
                                    $birth_date = function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($birth_date) : $birth_date;
                                }
                                echo esc_html($birth_date);
                            ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Mobile Number', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold">
                                <a href="tel:<?php echo esc_attr($post_meta['mobile_number'][0] ?? ''); ?>" class="text-primary">
                                    <i class="la la-phone me-1"></i><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($post_meta['mobile_number'][0] ?? '—') : esc_html($post_meta['mobile_number'][0] ?? '—'); ?>
                                </a>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Phone Number', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($post_meta['phone_number'][0] ?? '—') : esc_html($post_meta['phone_number'][0] ?? '—'); ?></p>
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
                            <p class="mb-0 fw-semibold"><?php 
                                $income_level = $post_meta['income_level'][0] ?? '—';
                                if ($income_level && $income_level !== '—') {
                                    // Convert Persian digits to English and remove commas
                                    $income_clean = str_replace(['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', ',', ' '], ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '', ''], $income_level);
                                    // Convert to number
                                    $income_numeric = is_numeric($income_clean) ? floatval($income_clean) : 0;
                                    if ($income_numeric > 0) {
                                        // اگر عدد است، با جداکننده فارسی کن
                                        $income_level = function_exists('persian_numbers') ? persian_numbers(number_format($income_numeric, 0, '.', ',')) : number_format($income_numeric, 0, '.', ',');
                                    }
                                }
                                echo esc_html($income_level);
                            ?></p>
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

        <!-- Documents Section -->
        <?php if ($is_admin_or_expert): ?>
        <?php
        // Get required documents from settings
        $options = get_option('maneli_inquiry_all_options', []);
        $customer_docs_raw = $options['customer_required_documents'] ?? '';
        $required_docs = array_filter(array_map('trim', explode("\n", $customer_docs_raw)));
        
        // Get uploaded documents for this customer
        $uploaded_docs = get_user_meta($customer_id, 'customer_uploaded_documents', true) ?: [];
        
        // Get rejection reasons
        $rejection_reasons_raw = $options['document_rejection_reasons'] ?? '';
        $rejection_reasons = array_filter(array_map('trim', explode("\n", $rejection_reasons_raw)));
        ?>
        <div class="card custom-card mt-3">
            <div class="card-header bg-info-transparent">
                <div class="card-title mb-0">
                    <i class="la la-file-alt text-info me-2"></i>
                    <?php esc_html_e('Documents', 'maneli-car-inquiry'); ?>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($required_docs)): ?>
                    <div class="row g-3">
                        <?php foreach ($required_docs as $doc_name): ?>
                            <?php 
                            // Check if this document has been uploaded
                            $is_uploaded = false;
                            $uploaded_file_url = '';
                            $doc_status = 'pending';
                            $doc_reviewed_at = '';
                            $doc_reviewed_by = '';
                            $doc_rejection_reason = '';
                            
                            foreach ($uploaded_docs as $uploaded) {
                                if (isset($uploaded['name']) && $uploaded['name'] === $doc_name) {
                                    $is_uploaded = true;
                                    $uploaded_file_url = $uploaded['file'] ?? '';
                                    $doc_status = $uploaded['status'] ?? 'pending';
                                    $doc_reviewed_at = $uploaded['reviewed_at'] ?? '';
                                    $doc_reviewed_by = $uploaded['reviewed_by'] ?? '';
                                    $doc_rejection_reason = $uploaded['rejection_reason'] ?? '';
                                    break;
                                }
                            }
                            ?>
                            <div class="col-md-6 col-lg-4 col-xl-3">
                                <div class="border rounded p-3 h-100">
                                    <div class="d-flex flex-column">
                                        <label class="fw-semibold mb-2">
                                            <i class="la la-file me-2"></i>
                                            <?php echo esc_html($doc_name); ?>
                                        </label>
                                            
                                            <?php if ($is_uploaded && $doc_status === 'approved'): ?>
                                                <div class="alert alert-success border-success py-2 px-3 mb-2">
                                                    <i class="la la-check-circle me-2"></i>
                                                    <?php esc_html_e('Approved', 'maneli-car-inquiry'); ?>
                                                    <?php if ($doc_reviewed_at): ?>
                                                        <small class="d-block mt-1 text-muted">
                                                            <?php 
                                                            $reviewed_date = Maneli_Render_Helpers::maneli_gregorian_to_jalali($doc_reviewed_at, 'Y/m/d H:i');
                                                            $reviewed_date = function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($reviewed_date) : $reviewed_date;
                                                            echo esc_html($reviewed_date);
                                                            ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($uploaded_file_url): ?>
                                                    <a href="<?php echo esc_url($uploaded_file_url); ?>" target="_blank" class="btn btn-sm btn-primary w-100 mb-2">
                                                        <i class="la la-download"></i> <?php esc_html_e('Download', 'maneli-car-inquiry'); ?>
                                                    </a>
                                                <?php endif; ?>
                                            <?php elseif ($is_uploaded && $doc_status === 'rejected'): ?>
                                                <div class="alert alert-danger border-danger py-2 px-3 mb-2">
                                                    <i class="la la-times-circle me-2"></i>
                                                    <?php esc_html_e('Rejected', 'maneli-car-inquiry'); ?>
                                                    <?php if ($doc_rejection_reason): ?>
                                                        <div class="mt-2">
                                                            <strong><?php esc_html_e('Reason:', 'maneli-car-inquiry'); ?></strong>
                                                            <p class="mb-0 mt-1"><?php echo esc_html($doc_rejection_reason); ?></p>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($doc_reviewed_at): ?>
                                                        <small class="d-block mt-1 text-muted">
                                                            <?php 
                                                            $reviewed_date = Maneli_Render_Helpers::maneli_gregorian_to_jalali($doc_reviewed_at, 'Y/m/d H:i');
                                                            $reviewed_date = function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($reviewed_date) : $reviewed_date;
                                                            echo esc_html($reviewed_date);
                                                            ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($uploaded_file_url): ?>
                                                    <a href="<?php echo esc_url($uploaded_file_url); ?>" target="_blank" class="btn btn-sm btn-primary w-100 mb-2">
                                                        <i class="la la-download"></i> <?php esc_html_e('Download', 'maneli-car-inquiry'); ?>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($is_admin_or_expert): ?>
                                                    <button class="btn btn-sm btn-success approve-doc-btn w-100" 
                                                            data-user-id="<?php echo esc_attr($customer_id); ?>" 
                                                            data-doc-name="<?php echo esc_attr($doc_name); ?>"
                                                            data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>"
                                                            data-inquiry-type="installment">
                                                        <i class="la la-check"></i> <?php esc_html_e('Approve', 'maneli-car-inquiry'); ?>
                                                    </button>
                                                <?php endif; ?>
                                            <?php elseif ($is_uploaded): ?>
                                                <div class="alert alert-info border-info py-2 px-3 mb-2">
                                                    <i class="la la-clock me-2"></i>
                                                    <?php esc_html_e('Awaiting Review', 'maneli-car-inquiry'); ?>
                                                </div>
                                                <?php if ($uploaded_file_url): ?>
                                                    <a href="<?php echo esc_url($uploaded_file_url); ?>" target="_blank" class="btn btn-sm btn-primary w-100 mb-2">
                                                        <i class="la la-download"></i> <?php esc_html_e('Download', 'maneli-car-inquiry'); ?>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($is_admin_or_expert): ?>
                                                    <div class="d-flex gap-2 flex-wrap">
                                                        <button class="btn btn-sm btn-success approve-doc-btn flex-fill" 
                                                                data-user-id="<?php echo esc_attr($customer_id); ?>" 
                                                                data-doc-name="<?php echo esc_attr($doc_name); ?>"
                                                                data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>"
                                                                data-inquiry-type="installment">
                                                            <i class="la la-check"></i> <?php esc_html_e('Approve', 'maneli-car-inquiry'); ?>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger reject-doc-btn flex-fill" 
                                                                data-user-id="<?php echo esc_attr($customer_id); ?>" 
                                                                data-doc-name="<?php echo esc_attr($doc_name); ?>"
                                                                data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>"
                                                                data-inquiry-type="installment"
                                                                data-rejection-reasons='<?php echo wp_json_encode($rejection_reasons); ?>'>
                                                            <i class="la la-times"></i> <?php esc_html_e('Reject', 'maneli-car-inquiry'); ?>
                                                        </button>
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <div class="alert alert-warning border-warning py-2 px-3 mb-0">
                                                    <i class="la la-exclamation-triangle me-2"></i>
                                                    <?php esc_html_e('Not Uploaded', 'maneli-car-inquiry'); ?>
                                                </div>
                                                <?php if ($is_admin_or_expert): ?>
                                                    <button class="btn btn-sm btn-warning mt-2 w-100 request-doc-btn" 
                                                            data-user-id="<?php echo esc_attr($customer_id); ?>" 
                                                            data-doc-name="<?php echo esc_attr($doc_name); ?>"
                                                            data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>"
                                                            data-inquiry-type="installment">
                                                        <i class="la la-paper-plane"></i> <?php esc_html_e('Request Document', 'maneli-car-inquiry'); ?>
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="la la-info-circle me-2"></i>
                        <?php esc_html_e('No documents have been configured in settings.', 'maneli-car-inquiry'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($issuer_type === 'self' && $is_admin): ?>
        <!-- Bank Information Card (Self) - Admin Only -->
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
                            <p class="mb-0 fw-semibold"><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($post_meta['account_number'][0] ?? '—') : esc_html($post_meta['account_number'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Branch Code', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($post_meta['branch_code'][0] ?? '—') : esc_html($post_meta['branch_code'][0] ?? '—'); ?></p>
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
                            <p class="mb-0 fw-semibold"><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($post_meta['issuer_national_code'][0] ?? '—') : esc_html($post_meta['issuer_national_code'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Date of Birth', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php 
                                $issuer_birth_date = $post_meta['issuer_birth_date'][0] ?? '—';
                                if ($issuer_birth_date && $issuer_birth_date !== '—') {
                                    // Try to convert if it's in Gregorian format
                                    if (strpos($issuer_birth_date, '/') !== false || strpos($issuer_birth_date, '-') !== false) {
                                        $issuer_birth_date = Maneli_Render_Helpers::maneli_gregorian_to_jalali($issuer_birth_date, 'Y/m/d');
                                    }
                                    $issuer_birth_date = function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($issuer_birth_date) : $issuer_birth_date;
                                }
                                echo esc_html($issuer_birth_date);
                            ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Mobile Number', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold">
                                <a href="tel:<?php echo esc_attr($post_meta['issuer_mobile_number'][0] ?? ''); ?>" class="text-primary">
                                    <i class="la la-phone me-1"></i><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($post_meta['issuer_mobile_number'][0] ?? '—') : esc_html($post_meta['issuer_mobile_number'][0] ?? '—'); ?>
                                </a>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Phone Number', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($post_meta['issuer_phone_number'][0] ?? '—') : esc_html($post_meta['issuer_phone_number'][0] ?? '—'); ?></p>
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

                <?php if ($is_admin): ?>
                <!-- Issuer Bank Information - Admin Only -->
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
                            <p class="mb-0 fw-semibold"><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($post_meta['issuer_account_number'][0] ?? '—') : esc_html($post_meta['issuer_account_number'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Branch Code', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($post_meta['issuer_branch_code'][0] ?? '—') : esc_html($post_meta['issuer_branch_code'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border p-3 rounded bg-light">
                            <small class="text-muted"><?php esc_html_e('Branch Name', 'maneli-car-inquiry'); ?></small>
                            <p class="mb-0 fw-semibold"><?php echo esc_html($post_meta['issuer_branch_name'][0] ?? '—'); ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($is_admin): ?>
        <!-- Credit Verification Card (Finotex) - Admin Only -->
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
        <?php endif; ?>
    </div>
</div>

<!-- Hidden Forms -->
<form id="admin-action-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="maneli-initially-hidden">
    <input type="hidden" name="action" value="maneli_admin_update_status">
    <input type="hidden" name="inquiry_id" value="<?php echo esc_attr($inquiry_id); ?>">
    <input type="hidden" id="final-status-input" name="new_status" value="">
    <input type="hidden" id="rejection-reason-input" name="rejection_reason" value="">
    <input type="hidden" id="assigned-expert-input" name="assigned_expert_id" value="">
    <?php wp_nonce_field('maneli_admin_update_status_nonce'); ?>
</form>

<!-- Tracking Status Modal -->
<div id="tracking-status-modal" class="modal fade maneli-initially-hidden" tabindex="-1">
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
                
                <div id="calendar-wrapper" class="maneli-initially-hidden mb-3">
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

/* Status Icon Loading Animation */
.status-icon-wrapper {
    position: relative;
}

.status-icon-loading {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    display: block;
}

.status-icon {
    display: block;
    position: relative;
    z-index: 1;
}

.status-icon-wrapper .status-icon-loading {
    display: none;
}

.status-icon-wrapper:not(.loaded) .status-icon {
    display: none;
}

.status-icon-wrapper:not(.loaded) .status-icon-loading {
    display: block;
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

<script>
// Mark all status icons as loaded when the page loads
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        document.querySelectorAll('.status-icon-wrapper').forEach(function(wrapper) {
            wrapper.classList.add('loaded');
        });
    }, 500);
});
</script>
</div><!-- End .frontend-expert-report -->
    </div>
</div>

<?php 
// EMERGENCY FIX: Output script directly in template to ensure it loads
if (isset($GLOBALS['maneli_emergency_script']) && $GLOBALS['maneli_emergency_script']) {
?>
<script type="text/javascript">
console.log('🚨 EMERGENCY SCRIPT IN TEMPLATE LOADING...');
if (typeof jQuery !== 'undefined') {
    jQuery(document).ready(function($) {
        console.log('🚨 EMERGENCY HANDLERS LOADED IN TEMPLATE');
        
        // Check if objects exist
        console.log('maneliInquiryLists:', typeof window.maneliInquiryLists !== 'undefined' ? 'DEFINED' : 'UNDEFINED');
        console.log('Swal:', typeof Swal !== 'undefined' ? 'DEFINED' : 'UNDEFINED');
        
        // Direct handler for assign expert button
        $(document).off('click.emergency', '.assign-expert-btn').on('click.emergency', '.assign-expert-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('🔘 TEMPLATE: Assign Expert button clicked!');
            var btn = $(this);
            var inquiryId = btn.data('inquiry-id');
            var inquiryType = btn.data('inquiry-type');
            console.log('Inquiry ID:', inquiryId, 'Type:', inquiryType);
            
            if (typeof window.maneliInquiryLists === 'undefined') {
                alert(maneliInquiryLists?.text?.error || 'Error: maneliInquiryLists is not defined!');
                console.error('maneliInquiryLists is undefined!');
                return;
            }
            
            if (typeof Swal === 'undefined') {
                alert(maneliInquiryLists?.text?.error || 'Error: SweetAlert2 is not loaded!');
                console.error('Swal is undefined!');
                return;
            }
            
            // Show working alert
                Swal.fire({
                    title: maneliInquiryLists?.text?.test_title || 'Button Works!',
                text: 'Inquiry ID: ' + inquiryId + ', Type: ' + inquiryType,
                icon: 'success'
            });
        });
        
        // Direct handler for delete button
        $(document).off('click.emergency', '.delete-inquiry-report-btn').on('click.emergency', '.delete-inquiry-report-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('🔘 TEMPLATE: Delete button clicked!');
            var btn = $(this);
            var inquiryId = btn.data('inquiry-id');
            var inquiryType = btn.data('inquiry-type');
            console.log('Delete Inquiry ID:', inquiryId, 'Type:', inquiryType);
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '<?php echo esc_js(__('Delete Button Works!', 'maneli-car-inquiry')); ?>',
                    text: 'Inquiry ID: ' + inquiryId,
                    icon: 'success'
                });
            } else {
                alert('<?php echo esc_js(__('Delete button clicked! Inquiry ID:', 'maneli-car-inquiry')); ?> ' + inquiryId);
            }
        });
        
        console.log('✅ Template emergency handlers attached');
        console.log('Button count - .assign-expert-btn:', $('.assign-expert-btn').length);
        console.log('Button count - .delete-inquiry-report-btn:', $('.delete-inquiry-report-btn').length);
        
        // Send SMS handler
        $('.send-sms-report-btn').on('click', function() {
            var phone = $(this).data('phone');
            var customerName = $(this).data('customer-name');
            var inquiryId = $(this).data('inquiry-id');
            
            if (typeof Swal === 'undefined') {
                alert('<?php echo esc_js(__('SweetAlert is not loaded', 'maneli-car-inquiry')); ?>');
                return;
            }
            
            Swal.fire({
                title: '<?php echo esc_js(__('Send SMS', 'maneli-car-inquiry')); ?>',
                html: `
                    <div class="text-start">
                        <p><strong><?php echo esc_js(__('Recipient:', 'maneli-car-inquiry')); ?></strong> ${customerName} (${phone})</p>
                        <div class="mb-3">
                            <label class="form-label"><?php echo esc_js(__('Message:', 'maneli-car-inquiry')); ?></label>
                            <textarea id="sms-message" class="form-control" rows="5" placeholder="<?php echo esc_js(__('Enter your message...', 'maneli-car-inquiry')); ?>"></textarea>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: '<?php echo esc_js(__('Send', 'maneli-car-inquiry')); ?>',
                cancelButtonText: '<?php echo esc_js(__('Cancel', 'maneli-car-inquiry')); ?>',
                preConfirm: function() {
                    var message = $('#sms-message').val();
                    if (!message.trim()) {
                        Swal.showValidationMessage('<?php echo esc_js(__('Please enter a message', 'maneli-car-inquiry')); ?>');
                        return false;
                    }
                    return { message: message };
                }
            }).then(function(result) {
                if (result.isConfirmed && result.value) {
                    Swal.fire({
                        title: '<?php echo esc_js(__('Sending...', 'maneli-car-inquiry')); ?>',
                        text: '<?php echo esc_js(__('Please wait', 'maneli-car-inquiry')); ?>',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: function() {
                            Swal.showLoading();
                        }
                    });
                    
                    $.ajax({
                        url: maneli_ajax.url,
                        type: 'POST',
                        data: {
                            action: 'maneli_send_single_sms',
                            nonce: maneli_ajax.nonce,
                            recipient: phone,
                            message: result.value.message,
                            related_id: inquiryId
                        },
                        success: function(response) {
                            Swal.close();
                            if (response.success) {
                                Swal.fire('<?php echo esc_js(__('Success', 'maneli-car-inquiry')); ?>', '<?php echo esc_js(__('SMS sent successfully!', 'maneli-car-inquiry')); ?>', 'success');
                            } else {
                                Swal.fire('<?php echo esc_js(__('Error', 'maneli-car-inquiry')); ?>', response.data?.message || '<?php echo esc_js(__('Failed to send SMS', 'maneli-car-inquiry')); ?>', 'error');
                            }
                        },
                        error: function() {
                            Swal.close();
                            Swal.fire('<?php echo esc_js(__('Error', 'maneli-car-inquiry')); ?>', '<?php echo esc_js(__('Server error. Please try again.', 'maneli-car-inquiry')); ?>', 'error');
                        }
                    });
                }
            });
        });
    });
} else {
    console.error('❌ jQuery not available for template emergency handlers!');
}
</script>
<?php
}
?>

<?php } // End of permission check ?>
