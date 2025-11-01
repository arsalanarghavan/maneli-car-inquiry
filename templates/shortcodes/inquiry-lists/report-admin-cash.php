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
    echo '<div class="alert alert-danger">' . esc_html__('Inquiry not found or you do not have access.', 'maneli-car-inquiry') . '</div>';
    return;
}

// Data Retrieval
$inquiry = get_post($inquiry_id);
$product_id = get_post_meta($inquiry_id, 'product_id', true);
$status = get_post_meta($inquiry_id, 'cash_inquiry_status', true);
$status_label = Maneli_CPT_Handler::get_cash_inquiry_status_label($status);
$back_link = home_url('/dashboard/cash-inquiries');

// Get customer ID
$customer_id = $inquiry->post_author;

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
<div class="main-content app-content">
    <div class="container-fluid">
<div class="frontend-expert-report" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-inquiry-type="cash">
    <!-- Back Button -->
    <div class="mb-3 report-back-button-wrapper">
        <a href="<?php echo esc_url($back_link); ?>" class="btn btn-light btn-wave">
            <i class="la la-arrow-right me-1"></i>
            <?php esc_html_e('Back to List', 'maneli-car-inquiry'); ?>
        </a>
    </div>

    <!-- Header Card -->
    <div class="card custom-card">
    <div class="card-header bg-warning-transparent">
        <div class="card-title">
            <i class="la la-dollar-sign me-2"></i>
            <?php esc_html_e('Cash Purchase Request Details', 'maneli-car-inquiry'); ?>
            <small class="text-muted">(#<?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($inquiry_id) : esc_html($inquiry_id); ?>)</small>
        </div>
    </div>
    <div class="card-body">
        <!-- Status Alert -->
        <?php
        $status_class = 'secondary';
        if ($status === 'completed' || $status === 'approved') {
            $status_class = $status === 'completed' ? 'success' : 'info';
        } elseif ($status === 'rejected') {
            $status_class = 'danger';
        } elseif ($status === 'awaiting_payment') {
            $status_class = 'warning';
        }
        ?>
        <div class="alert alert-<?php echo esc_attr($status_class); ?> border-<?php echo esc_attr($status_class); ?>">
            <div class="d-flex align-items-center justify-content-between flex-wrap">
                <div>
                    <strong><i class="la la-info-circle me-1"></i><?php esc_html_e('Current Status:', 'maneli-car-inquiry'); ?></strong> 
                    <span class="badge bg-<?php echo esc_attr($status_class); ?>-transparent fs-14 ms-2">
                        <?php echo esc_html($status_label); ?>
                    </span>
                    <?php if ($expert_name): ?>
                        <br><strong class="mt-2 d-inline-block"><?php esc_html_e('Expert:', 'maneli-car-inquiry'); ?></strong> 
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
        
        <?php if ($is_admin || $is_assigned_expert): ?>
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
                            'awaiting_downpayment' => ['label' => esc_html__('Awaiting Down Payment', 'maneli-car-inquiry'), 'icon' => 'la-dollar-sign', 'color' => 'warning'],
                            'downpayment_received' => ['label' => esc_html__('Down Payment Received', 'maneli-car-inquiry'), 'icon' => 'la-check-double', 'color' => 'success-light'],
                            'meeting_scheduled' => ['label' => esc_html__('Meeting Scheduled', 'maneli-car-inquiry'), 'icon' => 'la-calendar-check', 'color' => 'cyan'],
                            'approved' => ['label' => esc_html__('Approved', 'maneli-car-inquiry'), 'icon' => 'la-check-circle', 'color' => 'success'],
                            'completed' => ['label' => esc_html__('Completed', 'maneli-car-inquiry'), 'icon' => 'la-check-circle', 'color' => 'dark'],
                        ];
                        
                        // Special end statuses (shown separately)
                        $end_statuses = [
                            'rejected' => ['label' => esc_html__('Rejected', 'maneli-car-inquiry'), 'icon' => 'la-times-circle', 'color' => 'danger'],
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
                            <?php if ($status_key !== 'completed'): ?>
                                <div class="status-arrow maneli-status-arrow" style="opacity: <?php echo esc_attr($opacity); ?>;">
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
                                        <i class="la la-times-circle fs-16 text-white"></i>
                                    </span>
                                    <strong><?php esc_html_e('Rejected', 'maneli-car-inquiry'); ?></strong>
                                </div>
                            </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Request Information -->
        <div class="card border mb-4">
            <div class="card-header bg-light">
                <div class="card-title mb-0 d-flex justify-content-between align-items-center">
                    <span>
                        <i class="la la-file-alt text-primary me-2"></i>
                        <?php esc_html_e('Request Information', 'maneli-car-inquiry'); ?>
                    </span>
                    <?php if ($customer_id): ?>
                        <a href="<?php echo esc_url(add_query_arg(['view_user' => $customer_id], home_url('/dashboard/users'))); ?>#documents-tab" class="btn btn-sm btn-warning btn-wave">
                            <i class="la la-file-alt me-1"></i>
                            <?php esc_html_e('View Documents', 'maneli-car-inquiry'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php if ($product_image): ?>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <img src="<?php echo esc_url($product_image); ?>" alt="<?php echo esc_attr($car_name); ?>" class="img-fluid rounded shadow-sm">
                        </div>
                    <?php endif; ?>
                    <div class="col-md-<?php echo esc_attr($product_image ? '8' : '12'); ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-light">
                                    <div class="text-muted fs-12 mb-1">
                                        <i class="la la-user me-1"></i>
                                        <?php esc_html_e('Customer', 'maneli-car-inquiry'); ?>
                                    </div>
                                    <strong class="fs-16"><?php echo esc_html($first_name . ' ' . $last_name); ?></strong>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-light">
                                    <div class="text-muted fs-12 mb-1">
                                        <i class="la la-phone me-1"></i>
                                        <?php esc_html_e('Contact Number', 'maneli-car-inquiry'); ?>
                                    </div>
                                    <a href="tel:<?php echo esc_attr($mobile_number); ?>" class="fs-16 fw-semibold text-primary">
                                        <?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($mobile_number) : esc_html($mobile_number); ?>
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-light">
                                    <div class="text-muted fs-12 mb-1">
                                        <i class="la la-car me-1"></i>
                                        <?php esc_html_e('Car', 'maneli-car-inquiry'); ?>
                                    </div>
                                    <strong class="fs-16"><?php echo esc_html($car_name); ?></strong>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-light">
                                    <div class="text-muted fs-12 mb-1">
                                        <i class="la la-palette me-1"></i>
                                        <?php esc_html_e('Color', 'maneli-car-inquiry'); ?>
                                    </div>
                                    <strong class="fs-16"><?php echo esc_html($car_color ?: esc_html__('Not Specified', 'maneli-car-inquiry')); ?></strong>
                                </div>
                            </div>
                            <?php if ($down_payment): ?>
                                <div class="col-md-12">
                                    <div class="border rounded p-3 bg-success-transparent">
                                        <div class="text-muted fs-12 mb-1">
                                            <i class="la la-money-bill me-1"></i>
                                            <?php esc_html_e('Down Payment Amount', 'maneli-car-inquiry'); ?>
                                        </div>
                                        <strong class="fs-20 text-success"><?php echo function_exists('persian_numbers') ? persian_numbers(number_format_i18n($down_payment)) : number_format_i18n($down_payment); ?> <?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></strong>
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
                        <?php esc_html_e('Request Status Management', 'maneli-car-inquiry'); ?>
                    </h6>
                </div>
                <div class="card-body">
                    <?php
                    // Build activity timeline
                    $timeline = [];
                    
                    // Initial creation
                    $timeline[] = [
                        'time' => $inquiry->post_date,
                        'label' => esc_html__('Request Created', 'maneli-car-inquiry'),
                        'icon' => 'la-file-alt',
                        'color' => 'secondary'
                    ];
                    
                    // Assigned to expert
                    if ($expert_name) {
                        $timeline[] = [
                            'time' => get_post_meta($inquiry_id, 'referred_at', true) ?: $inquiry->post_date,
                            'label' => esc_html__('Assigned to Expert:', 'maneli-car-inquiry') . ' ' . $expert_name,
                            'icon' => 'la-user-tie',
                            'color' => 'info'
                        ];
                    }
                    
                    // In progress started
                    if (in_array($status, ['in_progress', 'awaiting_downpayment', 'downpayment_received', 'meeting_scheduled', 'completed', 'rejected'])) {
                        $timeline[] = [
                            'time' => get_post_meta($inquiry_id, 'in_progress_at', true) ?: '',
                            'label' => esc_html__('Follow-up Started by Expert', 'maneli-car-inquiry'),
                            'icon' => 'la-spinner',
                            'color' => 'primary'
                        ];
                    }
                    
                    // Down payment requested
                    if (in_array($status, ['awaiting_downpayment', 'downpayment_received', 'meeting_scheduled', 'completed', 'rejected'])) {
                        $down_payment_amount = get_post_meta($inquiry_id, 'cash_down_payment', true);
                        $down_payment_label = function_exists('persian_numbers') ? persian_numbers(number_format_i18n($down_payment_amount)) : number_format_i18n($down_payment_amount);
                        $timeline[] = [
                            'time' => get_post_meta($inquiry_id, 'downpayment_requested_at', true) ?: '',
                            'label' => esc_html__('Down Payment Requested:', 'maneli-car-inquiry') . ' ' . $down_payment_label . ' ' . esc_html__('Toman', 'maneli-car-inquiry'),
                            'icon' => 'la-dollar-sign',
                            'color' => 'warning'
                        ];
                    }
                    
                    // Down payment received
                    if (in_array($status, ['downpayment_received', 'meeting_scheduled', 'completed', 'rejected'])) {
                        $timeline[] = [
                            'time' => get_post_meta($inquiry_id, 'downpayment_received_at', true) ?: '',
                            'label' => esc_html__('Down Payment Received', 'maneli-car-inquiry'),
                            'icon' => 'la-check-circle',
                            'color' => 'success'
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
                    if ($status === 'completed') {
                        $timeline[] = [
                            'time' => get_post_meta($inquiry_id, 'completed_at', true) ?: '',
                            'label' => esc_html__('Final Approval and Completed', 'maneli-car-inquiry'),
                            'icon' => 'la-check-circle',
                            'color' => 'dark'
                        ];
                    }
                    
                    // Rejected
                    if ($status === 'rejected') {
                        $timeline[] = [
                            'time' => get_post_meta($inquiry_id, 'rejected_at', true) ?: '',
                            'label' => esc_html__('Rejected', 'maneli-car-inquiry'),
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
                                        <span class="avatar avatar-sm bg-<?php echo esc_attr($activity['color']); ?>-transparent me-2">
                                            <i class="la <?php echo esc_attr($activity['icon']); ?>"></i>
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
                            <button type="button" class="btn btn-primary btn-wave cash-status-btn" data-action="start_progress">
                                <i class="la la-play me-1"></i>
                                <?php esc_html_e('Start Progress', 'maneli-car-inquiry'); ?>
                            </button>
                        <?php endif; ?>
                        
                        <?php if (in_array($status, ['in_progress', 'follow_up_scheduled'])): ?>
                            <?php if ($is_assigned_expert || $is_admin): ?>
                            <button type="button" class="btn btn-success btn-wave cash-status-btn" data-action="approve">
                                <i class="la la-check-circle me-1"></i>
                                <?php esc_html_e('Approve', 'maneli-car-inquiry'); ?>
                            </button>
                            
                            <button type="button" class="btn btn-danger btn-wave cash-status-btn" data-action="reject">
                                <i class="la la-times-circle me-1"></i>
                                <?php esc_html_e('Reject', 'maneli-car-inquiry'); ?>
                            </button>
                            
                            <button type="button" class="btn btn-info btn-wave cash-status-btn" data-action="schedule_meeting">
                                <i class="la la-calendar-check me-1"></i>
                                <?php esc_html_e('Schedule Meeting', 'maneli-car-inquiry'); ?>
                            </button>
                            
                            <button type="button" class="btn btn-warning btn-wave" id="request-downpayment-btn">
                                <i class="la la-money-bill me-1"></i>
                                <?php esc_html_e('Request Down Payment', 'maneli-car-inquiry'); ?>
                            </button>
                            
                            <button type="button" class="btn btn-info btn-wave cash-status-btn" data-action="schedule_followup">
                                <i class="la la-clock me-1"></i>
                                <?php esc_html_e('Schedule Follow-up', 'maneli-car-inquiry'); ?>
                            </button>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if ($status === 'downpayment_received'): ?>
                            <?php if ($is_assigned_expert || $is_admin): ?>
                            <button type="button" class="btn btn-info btn-wave cash-status-btn" data-action="schedule_meeting">
                                <i class="la la-calendar me-1"></i>
                                <?php esc_html_e('Schedule Meeting', 'maneli-car-inquiry'); ?>
                            </button>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if ($status === 'meeting_scheduled'): ?>
                            <?php if ($is_assigned_expert || $is_admin): ?>
                            <button type="button" class="btn btn-success btn-wave cash-status-btn" data-action="complete">
                                <i class="la la-check-circle me-1"></i>
                                <?php esc_html_e('Complete', 'maneli-car-inquiry'); ?>
                            </button>
                            <button type="button" class="btn btn-danger btn-wave cash-status-btn" data-action="reject">
                                <i class="la la-times-circle me-1"></i>
                                <?php esc_html_e('Reject', 'maneli-car-inquiry'); ?>
                            </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Down Payment Input (Only when requesting) -->
            <?php if ($status === 'in_progress'): ?>
            <div class="card border mb-4 maneli-initially-hidden" id="downpayment-card">
                <div class="card-header bg-warning-transparent">
                    <h6 class="card-title mb-0">
                        <i class="la la-money-bill text-warning me-2"></i>
                        <?php esc_html_e('Set Down Payment Amount', 'maneli-car-inquiry'); ?>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold"><?php esc_html_e('Down Payment Amount (Toman):', 'maneli-car-inquiry'); ?></label>
                            <input type="number" class="form-control" id="downpayment-amount" value="<?php echo esc_attr($down_payment); ?>" placeholder="<?php esc_attr_e('Example: 50000000', 'maneli-car-inquiry'); ?>">
                        </div>
                        <div class="col-12">
                            <button type="button" class="btn btn-warning btn-wave" id="submit-downpayment-btn">
                                <i class="la la-send me-1"></i>
                                <?php esc_html_e('Send Down Payment Request to Customer', 'maneli-car-inquiry'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Meeting Schedule (Only when downpayment received) -->
            <?php if ($status === 'downpayment_received'): ?>
            <div class="card border mb-4 maneli-initially-hidden" id="meeting-card">
                <div class="card-header bg-info-transparent">
                    <h6 class="card-title mb-0">
                        <i class="la la-calendar text-info me-2"></i>
                        <?php esc_html_e('Schedule Meeting', 'maneli-car-inquiry'); ?>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?php esc_html_e('Select Date:', 'maneli-car-inquiry'); ?></label>
                            <input type="text" class="form-control maneli-datepicker" id="meeting-date" value="<?php echo esc_attr($meeting_date); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?php esc_html_e('Select Time:', 'maneli-car-inquiry'); ?></label>
                            <input type="time" class="form-control" id="meeting-time" value="<?php echo esc_attr($meeting_time); ?>">
                        </div>
                        <div class="col-12">
                            <button type="button" class="btn btn-info btn-wave" id="submit-meeting-btn">
                                <i class="la la-save me-1"></i>
                                <?php esc_html_e('Save Meeting Time and Update Status', 'maneli-car-inquiry'); ?>
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
                        <?php esc_html_e('Expert Notes', 'maneli-car-inquiry'); ?>
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
                                // تبدیل اعداد به فارسی
                                $jalali_date = function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($jalali_date) : $jalali_date;
                            ?>
                                <div class="alert alert-light border mb-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-fill">
                                            <p class="mb-1"><?php echo esc_html($note['note']); ?></p>
                                            <small class="text-muted">
                                                <i class="la la-user me-1"></i>
                                                <strong><?php echo $note_expert ? esc_html($note_expert->display_name) : esc_html__('Expert', 'maneli-car-inquiry'); ?></strong>
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
                                      placeholder="<?php esc_attr_e('Enter your note...', 'maneli-car-inquiry'); ?>"></textarea>
                            <button type="submit" class="btn btn-secondary">
                                <i class="la la-save me-1"></i>
                                <?php esc_html_e('Save Note', 'maneli-car-inquiry'); ?>
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
                        <?php esc_html_e('Expert Notes', 'maneli-car-inquiry'); ?>
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
                                // تبدیل اعداد به فارسی
                                $jalali_date = function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($jalali_date) : $jalali_date;
                            ?>
                                <div class="alert alert-light border mb-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-fill">
                                            <p class="mb-1"><?php echo esc_html($note['note']); ?></p>
                                            <small class="text-muted">
                                                <i class="la la-user me-1"></i>
                                                <strong><?php echo $note_expert ? esc_html($note_expert->display_name) : esc_html__('Expert', 'maneli-car-inquiry'); ?></strong>
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
                                <span><?php esc_html_e('No notes have been recorded yet.', 'maneli-car-inquiry'); ?></span>
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
                        <?php if (!$expert_name): ?>
                            <button type="button" class="btn btn-info btn-wave assign-expert-btn" 
                                    data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" 
                                    data-inquiry-type="cash">
                                <i class="la la-user-plus me-1"></i>
                                <?php esc_html_e('Assign to Expert', 'maneli-car-inquiry'); ?>
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-warning btn-wave assign-expert-btn" 
                                    data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" 
                                    data-inquiry-type="cash">
                                <i class="la la-user-edit me-1"></i>
                                <?php esc_html_e('Change Expert', 'maneli-car-inquiry'); ?>
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($expert_decision === 'approved'): ?>
                            <button type="button" class="btn btn-success btn-wave" id="admin-approve-btn">
                                <i class="la la-check-circle me-1"></i>
                                <?php esc_html_e('Final Admin Approval', 'maneli-car-inquiry'); ?>
                            </button>
                        <?php endif; ?>
                        
                        <button type="button" class="btn btn-danger btn-wave delete-inquiry-report-btn" 
                                data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" 
                                data-inquiry-type="cash">
                            <i class="la la-trash me-1"></i>
                            <?php esc_html_e('Delete Request', 'maneli-car-inquiry'); ?>
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

<?php 
// EMERGENCY FIX: Output script directly in template to ensure it loads
if (isset($GLOBALS['maneli_emergency_script']) && $GLOBALS['maneli_emergency_script']) {
?>
<script type="text/javascript">
console.log('🚨 EMERGENCY SCRIPT IN CASH TEMPLATE');
if (typeof jQuery !== 'undefined') {
    jQuery(document).ready(function($) {
        console.log('🚨 CASH TEMPLATE HANDLERS LOADED');
        console.log('maneliInquiryLists:', typeof window.maneliInquiryLists !== 'undefined' ? 'DEFINED' : 'UNDEFINED');
        console.log('maneliCashReport:', typeof window.maneliCashReport !== 'undefined' ? 'DEFINED' : 'UNDEFINED');
        console.log('Swal:', typeof Swal !== 'undefined' ? 'DEFINED' : 'UNDEFINED');
        
        $(document).off('click.emergency', '.assign-expert-btn').on('click.emergency', '.assign-expert-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('🔘 CASH: Assign Expert clicked!');
            if (typeof Swal !== 'undefined') {
                Swal.fire({title: <?php echo wp_json_encode(esc_html__('Button works!', 'maneli-car-inquiry')); ?>, icon: 'success'});
            }
        });
        
        $(document).off('click.emergency', '.delete-inquiry-report-btn').on('click.emergency', '.delete-inquiry-report-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('🔘 CASH: Delete clicked!');
            if (typeof Swal !== 'undefined') {
                Swal.fire({title: <?php echo wp_json_encode(esc_html__('Delete works!', 'maneli-car-inquiry')); ?>, icon: 'success'});
            }
        });
        
        console.log('✅ CASH handlers attached. Buttons:', $('.assign-expert-btn').length, $('.delete-inquiry-report-btn').length);
    });
}
</script>
<?php
}
?>
