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
$original_product_price = get_post_meta($inquiry_id, 'original_product_price', true);

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

                $dashboard_handler = Maneli_Dashboard_Handler::instance();
                $preferred_language_slug = method_exists($dashboard_handler, 'get_preferred_language_slug')
                    ? $dashboard_handler->get_preferred_language_slug()
                    : (is_rtl() ? 'fa' : 'en');
                $is_rtl_view = ($preferred_language_slug === 'fa');
                $arrow_icon_class = $is_rtl_view ? 'la-arrow-left' : 'la-arrow-right';
                $roadmap_direction_class = $is_rtl_view ? 'status-roadmap-rtl' : 'status-roadmap-ltr';

                $status_steps_payload = [];
                $status_steps_index = 0;
                $status_steps_current_index = 0;
                ?>
                
                <!-- Main Flow -->
                <div class="status-roadmap mb-3 <?php echo esc_attr($roadmap_direction_class); ?>">
                    <div class="d-flex align-items-center justify-content-between flex-wrap maneli-status-desktop">
                        <?php foreach ($all_statuses as $status_key => $status_info): 
                            $is_current = ($status_key === $current_status);
                            $is_passed = !$is_current && !$status_reached;
                            
                            if ($is_current) {
                                $status_reached = true;
                            }
                            
                            $opacity = $is_passed ? '1' : ($is_current ? '1' : '0.3');
                            $badge_class = $is_current ? 'bg-' . $status_info['color'] : ($is_passed ? 'bg-success-light' : 'bg-light text-muted');

                            $status_steps_payload[] = [
                                'key' => $status_key,
                                'label' => wp_strip_all_tags($status_info['label']),
                                'icon' => $status_info['icon'],
                                'color' => $status_info['color'],
                                'state' => $is_current ? 'current' : ($is_passed ? 'passed' : 'upcoming'),
                                'state_label' => $is_current
                                    ? esc_html__('Current Status', 'maneli-car-inquiry')
                                    : ($is_passed ? esc_html__('Completed Stage', 'maneli-car-inquiry') : esc_html__('Upcoming Stage', 'maneli-car-inquiry'))
                            ];

                            if ($is_current) {
                                $status_steps_current_index = $status_steps_index;
                            }
                            $status_steps_index++;
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
                                    <i class="la <?php echo esc_attr($arrow_icon_class); ?> fs-18 text-muted"></i>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <div
                        class="maneli-status-mobile d-none"
                        data-maneli-status-mobile
                        data-status-direction="<?php echo esc_attr($is_rtl_view ? 'rtl' : 'ltr'); ?>"
                        data-current-index="<?php echo esc_attr($status_steps_current_index); ?>"
                        data-statuses="<?php echo esc_attr(wp_json_encode($status_steps_payload)); ?>"
                        data-label-prev="<?php echo esc_attr(esc_html__('Previous status', 'maneli-car-inquiry')); ?>"
                        data-label-next="<?php echo esc_attr(esc_html__('Next status', 'maneli-car-inquiry')); ?>"
                    ></div>
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
                            <div class="col-md-12">
                                <div class="border rounded p-3 bg-primary-transparent">
                                    <div class="text-muted fs-12 mb-1">
                                        <i class="la la-tag me-1"></i>
                                        <?php esc_html_e('Requested Product Price (at time of request)', 'maneli-car-inquiry'); ?>
                                    </div>
                                    <strong class="fs-20 text-primary"><?php 
                                        if (!empty($original_product_price) && $original_product_price > 0) {
                                            echo function_exists('persian_numbers') ? persian_numbers(number_format_i18n($original_product_price)) : number_format_i18n($original_product_price);
                                            echo ' ' . esc_html__('Toman', 'maneli-car-inquiry');
                                        } else {
                                            esc_html_e('Not Available', 'maneli-car-inquiry');
                                        }
                                    ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Documents Section -->
        <?php if ($is_admin || $is_assigned_expert): ?>
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
        <div class="card border mb-4">
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
                                                <?php if ($is_admin || $is_assigned_expert): ?>
                                                    <button class="btn btn-sm btn-success approve-doc-btn w-100" 
                                                            data-user-id="<?php echo esc_attr($customer_id); ?>" 
                                                            data-doc-name="<?php echo esc_attr($doc_name); ?>"
                                                            data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>"
                                                            data-inquiry-type="cash">
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
                                                <?php if ($is_admin || $is_assigned_expert): ?>
                                                    <div class="d-flex gap-2 flex-wrap">
                                                        <button class="btn btn-sm btn-success approve-doc-btn flex-fill" 
                                                                data-user-id="<?php echo esc_attr($customer_id); ?>" 
                                                                data-doc-name="<?php echo esc_attr($doc_name); ?>"
                                                                data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>"
                                                                data-inquiry-type="cash">
                                                            <i class="la la-check"></i> <?php esc_html_e('Approve', 'maneli-car-inquiry'); ?>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger reject-doc-btn flex-fill" 
                                                                data-user-id="<?php echo esc_attr($customer_id); ?>" 
                                                                data-doc-name="<?php echo esc_attr($doc_name); ?>"
                                                                data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>"
                                                                data-inquiry-type="cash"
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
                                            <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($is_admin || $is_assigned_expert): ?>
                        <div class="mt-3 pt-3 border-top">
                            <button class="btn btn-primary w-100 request-documents-bulk-btn" 
                                    data-user-id="<?php echo esc_attr($customer_id); ?>" 
                                    data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>"
                                    data-inquiry-type="cash"
                                    data-required-docs='<?php echo wp_json_encode(array_values($required_docs)); ?>'>
                                <i class="la la-paper-plane me-2"></i>
                                <?php esc_html_e('Request Documents', 'maneli-car-inquiry'); ?>
                            </button>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="la la-info-circle me-2"></i>
                        <?php esc_html_e('No documents have been configured in settings.', 'maneli-car-inquiry'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

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
                        
                        <?php 
                        $customer_full_name = trim($first_name . ' ' . $last_name);
                        if (!empty($mobile_number)): ?>
                        <button type="button" class="btn btn-success btn-wave send-sms-report-btn" 
                                data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" 
                                data-phone="<?php echo esc_attr($mobile_number); ?>"
                                data-customer-name="<?php echo esc_attr($customer_full_name); ?>"
                                data-inquiry-type="cash">
                            <i class="la la-sms me-1"></i>
                            <?php esc_html_e('Send SMS', 'maneli-car-inquiry'); ?>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- SMS History - Visible for Admin and Expert -->
        <?php if ($is_admin || $is_assigned_expert): ?>
            <?php
            $sms_history = get_post_meta($inquiry_id, 'sms_history', true) ?: [];
            require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/helpers/class-maneli-render-helpers.php';
            ?>
            <div class="card border-info mt-4">
                <div class="card-header bg-info-transparent">
                    <h6 class="card-title mb-0">
                        <i class="la la-sms text-info me-2"></i>
                        <?php esc_html_e('SMS History', 'maneli-car-inquiry'); ?>
                        <?php if (!empty($sms_history)): ?>
                            <span class="badge bg-info ms-2"><?php echo count($sms_history); ?></span>
                        <?php endif; ?>
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($sms_history)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th><?php esc_html_e('Sent By', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Recipient', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Message', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Date & Time', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Status', 'maneli-car-inquiry'); ?></th>
                                        <th><?php esc_html_e('Actions', 'maneli-car-inquiry'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $index = 0;
                                    foreach ($sms_history as $sms): 
                                        $jalali_date = Maneli_Render_Helpers::maneli_gregorian_to_jalali($sms['sent_at'], 'Y/m/d H:i');
                                        $jalali_date = function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($jalali_date) : $jalali_date;
                                        $recipient_formatted = function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($sms['recipient']) : $sms['recipient'];
                                        $message_id = $sms['message_id'] ?? null;
                                        $is_failed = !($sms['success'] ?? false);
                                    ?>
                                        <tr data-sms-index="<?php echo esc_attr($index); ?>" data-message-id="<?php echo esc_attr($message_id); ?>">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="avatar avatar-sm bg-primary-transparent me-2">
                                                        <i class="la la-user"></i>
                                                    </span>
                                                    <span class="fw-semibold"><?php echo esc_html($sms['user_name'] ?? __('Unknown', 'maneli-car-inquiry')); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="text-muted"><?php echo esc_html($recipient_formatted); ?></span>
                                            </td>
                                            <td>
                                                <div class="text-wrap" style="max-width: 300px;">
                                                    <small><?php echo esc_html($sms['message'] ?? ''); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?php echo esc_html($jalali_date); ?></small>
                                            </td>
                                            <td>
                                                <div class="sms-status-display">
                                                    <?php if ($sms['success']): ?>
                                                        <span class="badge bg-success"><?php esc_html_e('Success', 'maneli-car-inquiry'); ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger"><?php esc_html_e('Failed', 'maneli-car-inquiry'); ?></span>
                                                        <?php if (!empty($sms['error'])): ?>
                                                            <br><small class="text-danger"><?php echo esc_html($sms['error']); ?></small>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                    <?php if (!empty($message_id)): ?>
                                                        <br><small class="text-muted delivery-status" data-message-id="<?php echo esc_attr($message_id); ?>">
                                                            <span class="status-text"><?php esc_html_e('Checking status...', 'maneli-car-inquiry'); ?></span>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($is_failed): ?>
                                                    <button type="button" class="btn btn-sm btn-warning btn-resend-sms" 
                                                            data-phone="<?php echo esc_attr($sms['recipient'] ?? ''); ?>"
                                                            data-message="<?php echo esc_attr($sms['message'] ?? ''); ?>"
                                                            data-related-id="<?php echo esc_attr($inquiry_id); ?>"
                                                            data-inquiry-type="cash">
                                                        <i class="la la-redo me-1"></i>
                                                        <?php esc_html_e('Resend', 'maneli-car-inquiry'); ?>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if (!empty($message_id)): ?>
                                                    <button type="button" class="btn btn-sm btn-info btn-check-status <?php echo $is_failed ? 'mt-1' : ''; ?>" 
                                                            data-message-id="<?php echo esc_attr($message_id); ?>">
                                                        <i class="la la-sync me-1"></i>
                                                        <?php esc_html_e('Check Status', 'maneli-car-inquiry'); ?>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php 
                                    $index++;
                                    endforeach; 
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info-transparent border-info">
                            <div class="d-flex align-items-center">
                                <i class="la la-info-circle me-2 fs-18"></i>
                                <span><?php esc_html_e('No SMS messages have been sent for this inquiry yet.', 'maneli-car-inquiry'); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
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

[data-theme-mode=dark] .card-header.bg-light {
    background: linear-gradient(135deg, rgba(30, 41, 59, 0.85), rgba(15, 23, 42, 0.85)) !important;
    color: #e2e8f0;
    border-bottom: 1px solid rgba(148, 163, 184, 0.2);
}

[data-theme-mode=dark] .status-roadmap {
    background: rgba(15, 23, 42, 0.6);
    border-radius: 18px;
    box-shadow: inset 0 1px 0 rgba(148, 163, 184, 0.08);
}

[data-theme-mode=dark] .status-step[style*="opacity: 0.3"] {
    opacity: 0.55 !important;
}

[data-theme-mode=dark] .status-step[style*="opacity: 0.3"] .avatar {
    background-color: rgba(30, 41, 59, 0.85) !important;
    color: rgba(148, 163, 184, 0.65);
    border: 1px solid rgba(148, 163, 184, 0.2);
}

[data-theme-mode=dark] .status-step .avatar {
    box-shadow: 0 10px 20px rgba(2, 6, 23, 0.45);
    border: 1px solid rgba(148, 163, 184, 0.18);
}

[data-theme-mode=dark] .status-step small {
    color: #cbd5f5;
}

[data-theme-mode=dark] .status-step .badge {
    background: rgba(15, 23, 42, 0.7);
    color: #e2e8f0;
    border: 1px solid rgba(148, 163, 184, 0.25);
}

[data-theme-mode=dark] .status-step .badge.bg-success-light,
[data-theme-mode=dark] .status-step .badge.bg-light {
    background: rgba(30, 41, 59, 0.9);
    color: #cbd5f5;
}

[data-theme-mode=dark] .status-arrow i {
    color: rgba(148, 163, 184, 0.65) !important;
}

[data-theme-mode=dark] .pulse-ring {
    border-color: rgba(94, 234, 212, 0.75);
    box-shadow: 0 0 0 6px rgba(45, 212, 191, 0.15);
}

[data-theme-mode=dark] .status-icon-wrapper .spinner-border {
    color: #f8fafc !important;
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

<script type="text/javascript">
// Global AJAX variables for SMS sending (fallback for timing - main localization is in class-dashboard-handler.php)
var maneliAjaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
var maneliAjaxNonce = '<?php echo wp_create_nonce('maneli-ajax-nonce'); ?>';

// Note: Send SMS handler is now handled by inquiry-lists.js (same as other pages)
// This ensures consistent behavior and translations across all pages
if (typeof jQuery !== 'undefined') {
    jQuery(document).ready(function($) {
        
        // SMS History handler
        $(document.body).on('click', '.view-sms-history-btn', function() {
            const button = $(this);
            const inquiryId = button.data('inquiry-id');
            const inquiryType = button.data('inquiry-type') || 'cash';
            
            if (!inquiryId) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: '<?php echo esc_js(__('Error', 'maneli-car-inquiry')); ?>',
                        text: '<?php echo esc_js(__('Invalid inquiry ID.', 'maneli-car-inquiry')); ?>',
                        icon: 'error'
                    });
                }
                return;
            }
            
            const modalElement = document.getElementById('sms-history-modal');
            if (!modalElement) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: '<?php echo esc_js(__('Error', 'maneli-car-inquiry')); ?>',
                        text: '<?php echo esc_js(__('SMS history modal not found.', 'maneli-car-inquiry')); ?>',
                        icon: 'error'
                    });
                }
                return;
            }
            
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            } else if (typeof jQuery !== 'undefined' && jQuery(modalElement).modal) {
                jQuery(modalElement).modal('show');
            } else {
                jQuery(modalElement).addClass('show').css('display', 'block');
                jQuery('.modal-backdrop').remove();
                jQuery('body').append('<div class="modal-backdrop fade show"></div>');
            }
            
            $('#sms-history-loading').removeClass('maneli-initially-hidden').show();
            $('#sms-history-content').addClass('maneli-initially-hidden').hide();
            $('#sms-history-table-container').empty();
            
            var ajaxUrl = typeof maneliAjaxUrl !== 'undefined' ? maneliAjaxUrl : 
                         (typeof maneliInquiryLists !== 'undefined' ? maneliInquiryLists.ajax_url : '');
            var ajaxNonce = typeof maneliAjaxNonce !== 'undefined' ? maneliAjaxNonce :
                           (typeof maneliInquiryLists !== 'undefined' ? (maneliInquiryLists.nonces?.ajax || maneliInquiryLists.nonce || '') : '');
            
            if (!ajaxUrl) {
                ajaxUrl = typeof adminAjax !== 'undefined' ? adminAjax.url : '';
                if (!ajaxUrl && typeof ajaxurl !== 'undefined') {
                    ajaxUrl = ajaxurl;
                }
            }
            
            if (!ajaxNonce) {
                $('#sms-history-loading').addClass('maneli-initially-hidden').hide();
                $('#sms-history-content').removeClass('maneli-initially-hidden').show();
                $('#sms-history-table-container').html(
                    '<div class="alert alert-danger">' +
                    '<i class="la la-exclamation-triangle me-2"></i>' +
                    '<?php echo esc_js(__('Nonce is missing. Please refresh the page and try again.', 'maneli-car-inquiry')); ?>' +
                    '</div>'
                );
                return;
            }
            
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'maneli_get_sms_history',
                    nonce: ajaxNonce,
                    inquiry_id: inquiryId,
                    inquiry_type: inquiryType
                },
                success: function(response) {
                    $('#sms-history-loading').addClass('maneli-initially-hidden').hide();
                    $('#sms-history-content').removeClass('maneli-initially-hidden').show();
                    
                    if (response && response.success && response.data && response.data.html) {
                        $('#sms-history-table-container').html(response.data.html);
                    } else {
                        $('#sms-history-table-container').html(
                            '<div class="alert alert-info">' +
                            '<i class="la la-info-circle me-2"></i>' +
                            '<?php echo esc_js(__('No SMS messages have been sent for this inquiry yet.', 'maneli-car-inquiry')); ?>' +
                            '</div>'
                        );
                    }
                },
                error: function(xhr, status, error) {
                    $('#sms-history-loading').addClass('maneli-initially-hidden').hide();
                    $('#sms-history-content').removeClass('maneli-initially-hidden').show();
                    $('#sms-history-table-container').html(
                        '<div class="alert alert-danger">' +
                        '<i class="la la-exclamation-triangle me-2"></i>' +
                        '<?php echo esc_js(__('Error loading SMS history.', 'maneli-car-inquiry')); ?>' +
                        '</div>'
                    );
                }
            });
        });
        
        // Note: Resend SMS, Check Status, and Auto-check handlers are now handled by inquiry-lists.js
        // This ensures consistent behavior and translations across all pages
    });
}
</script>

<?php
// Include SMS History Modal (shared template)
maneli_get_template_part('partials/sms-history-modal');
?>
