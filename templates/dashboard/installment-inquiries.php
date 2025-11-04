<?php
/**
 * Template for the Admin/Expert view of the Installment Inquiry List.
 *
 * This template displays statistical widgets, filter controls, and the table for installment inquiries.
 * The table body is populated and managed via AJAX.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/InquiryLists
 * @author  Gemini
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// CRITICAL: Always get fresh role from WordPress user object
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login'));
    exit;
}

$current_user = wp_get_current_user();
// Always check fresh roles from database
$is_admin = current_user_can('manage_maneli_inquiries');
$is_manager = in_array('maneli_manager', $current_user->roles, true) || in_array('maneli_admin', $current_user->roles, true);
$is_expert = in_array('maneli_expert', $current_user->roles, true);
$is_customer = !$is_admin && !$is_manager && !$is_expert;

// Check if user has a pending inquiry step - redirect to wizard (CUSTOMERS ONLY)
if ($is_customer) {
    $current_user_id = get_current_user_id();
    $inquiry_step = get_user_meta($current_user_id, 'maneli_inquiry_step', true);
    
    // If user has a pending step, redirect to wizard
    if (!empty($inquiry_step) && $inquiry_step !== 'completed' && $inquiry_step !== '') {
        // Determine which step to show
        $wizard_url = home_url('/dashboard/new-inquiry');
        
        if ($inquiry_step === 'form_pending') {
            // Step 2 (identity form)
            $wizard_url = add_query_arg('step', '2', $wizard_url);
        } elseif ($inquiry_step === 'confirm_car_pending') {
            // Step 3 (confirm car)
            $wizard_url = add_query_arg('step', '3', $wizard_url);
        } elseif ($inquiry_step === 'payment_pending') {
            // Step 4 (payment)
            $wizard_url = add_query_arg('step', '4', $wizard_url);
        }
        
        wp_redirect($wizard_url);
        exit;
    }
}

// Check if viewing a single inquiry report
$inquiry_id = isset($_GET['inquiry_id']) ? intval($_GET['inquiry_id']) : 0;
if ($inquiry_id > 0) {
    // CRITICAL: Ensure nonces are set BEFORE loading report template
    // Add inline script to ensure maneliInquiryLists has update_installment_status nonce
    ?>
    <script>
    // Ensure maneliInquiryLists has update_installment_status nonce for single report view
    (function() {
        if (typeof maneliInquiryLists === 'undefined') {
            window.maneliInquiryLists = {
                ajax_url: '<?php echo esc_js(admin_url("admin-ajax.php")); ?>',
                nonces: {},
                text: {}
            };
        }
        if (!maneliInquiryLists.nonces) {
            maneliInquiryLists.nonces = {};
        }
        if (!maneliInquiryLists.nonces.update_installment_status) {
            maneliInquiryLists.nonces.update_installment_status = '<?php echo esc_js(wp_create_nonce("maneli_installment_status")); ?>';
            console.log('🟢 REPORT: Added update_installment_status nonce to maneliInquiryLists');
        }
        if (!maneliInquiryLists.nonces.save_installment_note) {
            maneliInquiryLists.nonces.save_installment_note = '<?php echo esc_js(wp_create_nonce("maneli_installment_note")); ?>';
            console.log('🟢 REPORT: Added save_installment_note nonce to maneliInquiryLists');
        }
        console.log('🟢 REPORT: maneliInquiryLists nonces:', maneliInquiryLists.nonces);
    })();
    </script>
    <?php
    
    if ($is_customer) {
        // Customer sees customer report - needs wrapper
        ?>
        <div class="main-content app-content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-xl-12">
                        <?php
                        maneli_get_template_part('shortcodes/inquiry-lists/report-customer-installment', ['inquiry_id' => $inquiry_id]);
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- End::main-content -->
        <?php
    } else {
        // Admin/Expert sees admin report - already has wrapper
        maneli_get_template_part('shortcodes/inquiry-lists/report-admin-installment', ['inquiry_id' => $inquiry_id]);
    }
    return;
}

// Maneli_Admin_Dashboard_Widgets و Maneli_CPT_Handler باید قبلا در هسته بارگذاری شده باشند.
$experts = $is_admin ? get_users(['role' => 'maneli_expert', 'orderby' => 'display_name', 'order' => 'ASC']) : [];
?>

<div class="main-content app-content">
    <div class="container-fluid">

        <!-- Start::page-header -->
        <div class="d-flex align-items-center justify-content-between page-header-breadcrumb flex-wrap gap-2">
            <div>
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item">
                        <a href="<?php echo home_url('/dashboard'); ?>"><?php esc_html_e('Dashboard', 'maneli-car-inquiry'); ?></a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page"><?php esc_html_e('Installment Inquiries', 'maneli-car-inquiry'); ?></li>
                </ol>
                <h1 class="page-title fw-medium fs-18 mb-0"><?php esc_html_e('Installment Inquiries', 'maneli-car-inquiry'); ?></h1>
            </div>
        </div>
        <!-- End::page-header -->

<div class="row">
    <div class="col-xl-12">
        <?php 
        // Only show statistics widgets for admin/expert, not for customers
        if (!$is_customer) {
            echo Maneli_Admin_Dashboard_Widgets::render_inquiry_statistics_widgets(); 
        }
        ?>
        
        <div class="card custom-card mt-4">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="card-title">
                    <?php esc_html_e('Installment Inquiries', 'maneli-car-inquiry'); ?><span class="badge bg-primary rounded ms-2 fs-12 align-middle" id="inquiry-count-badge">0</span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-success-light btn-sm" id="inquiry-export-csv-btn">
                        <i class="la la-download me-1"></i><?php esc_html_e('Export CSV', 'maneli-car-inquiry'); ?>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <!-- Filter Section -->
                <div class="p-3 border-bottom">
                    <form id="maneli-inquiry-filter-form" onsubmit="return false;">
                        <div class="row g-3 mb-3">
                            <div class="col-md-12">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="la la-search"></i>
                                    </span>
                                    <input type="search" id="inquiry-search-input" class="form-control" placeholder="<?php esc_attr_e('Search by customer name, car name, national ID or mobile...', 'maneli-car-inquiry'); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Filter Controls - All in one line -->
                        <?php
                        // Get initial status from URL query parameter
                        // For installment, 'referred' is a tracking_status, not inquiry_status
                        $initial_status_param = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
                        // Map tracking status values
                        $tracking_statuses = ['new', 'referred', 'in_progress', 'meeting_scheduled', 'follow_up_scheduled', 'cancelled', 'completed', 'rejected'];
                        $is_tracking_status = in_array($initial_status_param, $tracking_statuses, true);
                        ?>
                        <div class="row g-2 align-items-end mb-3">
                            <!-- Status Filter -->
                            <div class="col">
                                <label class="form-label"><?php esc_html_e('Status:', 'maneli-car-inquiry'); ?></label>
                                <select id="status-filter" class="form-select form-select-sm">
                                    <option value=""><?php esc_html_e('All Statuses', 'maneli-car-inquiry'); ?></option>
                                    <?php foreach (Maneli_CPT_Handler::get_all_statuses() as $key => $label): ?>
                                        <option value="<?php echo esc_attr($key); ?>" <?php selected($initial_status_param, $key); ?>><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <?php if ($is_admin && !empty($experts)): ?>
                                <!-- Expert Filter -->
                                <div class="col">
                                    <label class="form-label"><?php esc_html_e('Assigned Expert:', 'maneli-car-inquiry'); ?></label>
                                    <select id="expert-filter" class="form-select form-select-sm maneli-select2">
                                        <option value=""><?php esc_html_e('All Experts', 'maneli-car-inquiry'); ?></option>
                                        <option value="0" <?php selected(isset($_GET['assigned_expert']) ? $_GET['assigned_expert'] : '', '0'); ?>><?php esc_html_e('Unassigned', 'maneli-car-inquiry'); ?></option>
                                        <?php foreach ($experts as $expert) : ?>
                                            <option value="<?php echo esc_attr($expert->ID); ?>" <?php selected(isset($_GET['assigned_expert']) ? $_GET['assigned_expert'] : '', $expert->ID); ?>><?php echo esc_html($expert->display_name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Sort Filter -->
                            <div class="col">
                                <label class="form-label"><?php esc_html_e('Sort:', 'maneli-car-inquiry'); ?></label>
                                <select id="inquiry-sort-filter" class="form-select form-select-sm">
                                    <option value="default"><?php esc_html_e('Default (Newest First)', 'maneli-car-inquiry'); ?></option>
                                    <option value="date_desc"><?php esc_html_e('Newest', 'maneli-car-inquiry'); ?></option>
                                    <option value="date_asc"><?php esc_html_e('Oldest', 'maneli-car-inquiry'); ?></option>
                                    <option value="status"><?php esc_html_e('By Status', 'maneli-car-inquiry'); ?></option>
                                </select>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="col-auto">
                                <label class="form-label d-block" style="visibility: hidden;">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="button" id="inquiry-reset-filters" class="btn btn-outline-secondary btn-sm">
                                        <i class="la la-refresh me-1"></i>
                                        <?php esc_html_e('Clear', 'maneli-car-inquiry'); ?>
                                    </button>
                                    <button type="button" id="inquiry-apply-filters" class="btn btn-primary btn-sm">
                                        <i class="la la-filter me-1"></i>
                                        <?php esc_html_e('Apply', 'maneli-car-inquiry'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table text-nowrap table-hover">
                        <thead>
                            <tr>
                                <th scope="col"><?php esc_html_e('ID', 'maneli-car-inquiry'); ?></th>
                                <th scope="col"><?php esc_html_e('Customer', 'maneli-car-inquiry'); ?></th>
                                <th scope="col"><?php esc_html_e('Car', 'maneli-car-inquiry'); ?></th>
                                <th scope="col"><?php esc_html_e('Status', 'maneli-car-inquiry'); ?></th>
                                <?php if ($is_admin) echo '<th scope="col">' . esc_html__('Assigned', 'maneli-car-inquiry') . '</th>'; ?>
                                <th scope="col"><?php esc_html_e('Date', 'maneli-car-inquiry'); ?></th>
                                <th scope="col"><?php esc_html_e('Actions', 'maneli-car-inquiry'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="maneli-inquiry-list-tbody">
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="la la-spinner la-spin fs-24 text-muted"></i>
                                    <p class="mt-2 text-muted"><?php esc_html_e('Loading...', 'maneli-car-inquiry'); ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div id="inquiry-pagination" class="d-flex justify-content-center mt-3">
                </div>
                
                <div id="inquiry-list-loader" class="maneli-list-loader">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden"><?php esc_html_e('Loading...', 'maneli-car-inquiry'); ?></span>
                    </div>
                </div>

                <div class="maneli-pagination-wrapper mt-3 text-center"></div>
            </div>
        </div>
    </div>
</div>

    </div>
</div>
<!-- End::main-content -->

<!-- Tracking Status Modal -->
<div class="modal fade" id="tracking-status-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php esc_html_e('Set Tracking Status', 'maneli-car-inquiry'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                
                <div id="calendar-wrapper" class="maneli-initially-hidden">
                    <label id="calendar-label" class="form-label"><?php esc_html_e('Select Date:', 'maneli-car-inquiry'); ?></label>
                    <input type="text" id="tracking-date-picker" class="form-control maneli-datepicker" readonly>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'maneli-car-inquiry'); ?></button>
                <button type="button" id="confirm-tracking-status-btn" class="btn btn-primary">
                    <?php esc_html_e('Confirm Status', 'maneli-car-inquiry'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Direct initialization check for installment inquiries
console.log('🟢 TEMPLATE: installment-inquiries.php script loaded');

// Helper function to convert numbers to Persian
function toPersianNumber(num) {
    const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    const englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    return String(num).replace(/\d/g, function(digit) {
        return persianDigits[englishDigits.indexOf(digit)];
    });
}

// CRITICAL: Initialize maneliInquiryLists immediately if not already set
// Also ensure update_installment_status nonce exists even if object already exists
if (typeof maneliInquiryLists === 'undefined') {
    console.warn('🟢 TEMPLATE: maneliInquiryLists is undefined! Creating fallback...');
    window.maneliInquiryLists = {
        ajax_url: '<?php echo esc_js(admin_url("admin-ajax.php")); ?>',
        nonces: {
            inquiry_filter: '<?php echo esc_js(wp_create_nonce("maneli_inquiry_filter_nonce")); ?>',
            details: '<?php echo esc_js(wp_create_nonce("maneli_inquiry_details_nonce")); ?>',
            inquiry_delete: '<?php echo esc_js(wp_create_nonce("maneli_inquiry_delete_nonce")); ?>',
            assign_expert: '<?php echo esc_js(wp_create_nonce("maneli_inquiry_assign_expert_nonce")); ?>',
            tracking_status: '<?php echo esc_js(wp_create_nonce("maneli_tracking_status_nonce")); ?>',
            update_installment_status: '<?php echo esc_js(wp_create_nonce("maneli_installment_status")); ?>',
            save_installment_note: '<?php echo esc_js(wp_create_nonce("maneli_installment_note")); ?>'
        },
        experts: <?php 
            $experts_list = [];
            if (current_user_can('manage_maneli_inquiries')) {
                $experts = get_users(['role' => 'maneli_expert', 'orderby' => 'display_name', 'order' => 'ASC']);
                foreach ($experts as $expert) {
                    $experts_list[] = ['id' => $expert->ID, 'name' => $expert->display_name ?: $expert->user_login];
                }
            }
            echo json_encode($experts_list); 
        ?>,
        text: {
            error: '<?php echo esc_js(__('Error', 'maneli-car-inquiry')); ?>',
            success: '<?php echo esc_js(__('Success', 'maneli-car-inquiry')); ?>',
            server_error: '<?php echo esc_js(__('Server error. Please try again.', 'maneli-car-inquiry')); ?>',
            unknown_error: '<?php echo esc_js(__('Unknown error', 'maneli-car-inquiry')); ?>',
            modal_not_found_error: '<?php echo esc_js(__('Error: Status modal not found. Please contact support.', 'maneli-car-inquiry')); ?>',
            start_progress_title: '<?php echo esc_js(__('Start Follow-up', 'maneli-car-inquiry')); ?>',
            start_progress_confirm: '<?php echo esc_js(__('Are you sure you want to start follow-up for this inquiry?', 'maneli-car-inquiry')); ?>',
            schedule_meeting_title: '<?php echo esc_js(__('Schedule Meeting', 'maneli-car-inquiry')); ?>',
            meeting_date_label: '<?php echo esc_js(__('Meeting Date', 'maneli-car-inquiry')); ?>',
            meeting_time_label: '<?php echo esc_js(__('Meeting Time', 'maneli-car-inquiry')); ?>',
            select_date: '<?php echo esc_js(__('Select Date', 'maneli-car-inquiry')); ?>',
            schedule_button: '<?php echo esc_js(__('Schedule', 'maneli-car-inquiry')); ?>',
            schedule_followup_title: '<?php echo esc_js(__('Schedule Follow-up', 'maneli-car-inquiry')); ?>',
            schedule_followup_button: '<?php echo esc_js(__('Schedule Follow-up', 'maneli-car-inquiry')); ?>',
            followup_date_label: '<?php echo esc_js(__('Follow-up Date', 'maneli-car-inquiry')); ?>',
            note_label_optional: '<?php echo esc_js(__('Note (Optional)', 'maneli-car-inquiry')); ?>',
            enter_note: '<?php echo esc_js(__('Enter your note...', 'maneli-car-inquiry')); ?>',
            followup_date_required: '<?php echo esc_js(__('Please enter follow-up date', 'maneli-car-inquiry')); ?>',
            meeting_required: '<?php echo esc_js(__('Please enter meeting date and time', 'maneli-car-inquiry')); ?>',
            complete_title: '<?php echo esc_js(__('Complete Inquiry', 'maneli-car-inquiry')); ?>',
            complete_confirm: '<?php echo esc_js(__('Are you sure you want to complete this inquiry?', 'maneli-car-inquiry')); ?>',
            reject_title: '<?php echo esc_js(__('Reject Inquiry', 'maneli-car-inquiry')); ?>',
            reject_confirm: '<?php echo esc_js(__('Are you sure you want to reject this inquiry?', 'maneli-car-inquiry')); ?>',
            rejection_reason_label: '<?php echo esc_js(__('Rejection Reason', 'maneli-car-inquiry')); ?>',
            enter_reason: '<?php echo esc_js(__('Please enter reason...', 'maneli-car-inquiry')); ?>',
            reason_required: '<?php echo esc_js(__('Please enter reason with at least 10 characters', 'maneli-car-inquiry')); ?>',
            confirm_button: '<?php echo esc_js(__('Yes', 'maneli-car-inquiry')); ?>',
            cancel_button: '<?php echo esc_js(__('Cancel', 'maneli-car-inquiry')); ?>',
            ok_button: '<?php echo esc_js(__('OK', 'maneli-car-inquiry')); ?>',
            status_updated: '<?php echo esc_js(__('Status updated successfully', 'maneli-car-inquiry')); ?>',
            status_update_error: '<?php echo esc_js(__('Error updating status', 'maneli-car-inquiry')); ?>',
            loading: '<?php echo esc_js(__('Loading...', 'maneli-car-inquiry')); ?>',
            loading_inquiries: '<?php echo esc_js(__('Loading inquiries...', 'maneli-car-inquiry')); ?>',
            loading_inquiries_error: '<?php echo esc_js(__('Error loading list', 'maneli-car-inquiry')); ?>',
            connection_error: '<?php echo esc_js(__('Connection error (0). Please check your internet connection.', 'maneli-car-inquiry')); ?>',
            server_error_500: '<?php echo esc_js(__('Server error (500). Please contact the administrator.', 'maneli-car-inquiry')); ?>',
            connection_error_code: '<?php echo esc_js(__('Connection error (Code:', 'maneli-car-inquiry')); ?>',
            error_occurred: '<?php echo esc_js(__('An error occurred.', 'maneli-car-inquiry')); ?>',
            note_saved_success: '<?php echo esc_js(__('Note saved successfully.', 'maneli-car-inquiry')); ?>',
            unauthorized_403: '<?php echo esc_js(__('Unauthorized access (403). Please refresh the page and log in again.', 'maneli-car-inquiry')); ?>',
            please_refresh: '<?php echo esc_js(__('Please refresh the page.', 'maneli-car-inquiry')); ?>',
            operation_success: '<?php echo esc_js(__('Operation completed successfully.', 'maneli-car-inquiry')); ?>',
            amount: '<?php echo esc_js(__('Amount', 'maneli-car-inquiry')); ?>',
            toman: '<?php echo esc_js(__('Toman', 'maneli-car-inquiry')); ?>',
            send_button: '<?php echo esc_js(__('Yes, Send', 'maneli-car-inquiry')); ?>',
            final_approval_title: '<?php echo esc_js(__('Final Approval?', 'maneli-car-inquiry')); ?>',
            customer_request_will_be_approved: '<?php echo esc_js(__('Customer request will be approved', 'maneli-car-inquiry')); ?>',
            approve_button: '<?php echo esc_js(__('Yes, Approve', 'maneli-car-inquiry')); ?>',
            submit_rejection: '<?php echo esc_js(__('Submit Rejection', 'maneli-car-inquiry')); ?>',
            enter_rejection_reason: '<?php echo esc_js(__('Please enter rejection reason...', 'maneli-car-inquiry')); ?>',
            status_will_change_to_in_progress: '<?php echo esc_js(__('Status will change to "In Progress"', 'maneli-car-inquiry')); ?>',
            downpayment_amount_required: '<?php echo esc_js(__('Please enter down payment amount', 'maneli-car-inquiry')); ?>',
            cancel_inquiry_title: '<?php echo esc_js(__('Cancel Inquiry', 'maneli-car-inquiry')); ?>',
            cancel_reason_label: '<?php echo esc_js(__('Cancellation Reason', 'maneli-car-inquiry')); ?>'
        },
        installment_rejection_reasons: <?php 
            $options = get_option('maneli_inquiry_all_options', []);
            $reasons = array_filter(array_map('trim', explode("\n", $options['installment_rejection_reasons'] ?? '')));
            echo json_encode(array_values($reasons)); 
        ?>
    };
    console.log('🟢 TEMPLATE: maneliInquiryLists fallback created:', window.maneliInquiryLists);
} else {
    console.log('🟢 TEMPLATE: maneliInquiryLists already exists:', maneliInquiryLists);
    // Ensure update_installment_status nonce exists even if object already exists
    if (!maneliInquiryLists.nonces || !maneliInquiryLists.nonces.update_installment_status) {
        console.warn('🟢 TEMPLATE: Adding missing update_installment_status nonce to existing maneliInquiryLists');
        if (!maneliInquiryLists.nonces) {
            maneliInquiryLists.nonces = {};
        }
        maneliInquiryLists.nonces.update_installment_status = '<?php echo esc_js(wp_create_nonce("maneli_installment_status")); ?>';
        if (!maneliInquiryLists.nonces.save_installment_note) {
            maneliInquiryLists.nonces.save_installment_note = '<?php echo esc_js(wp_create_nonce("maneli_installment_note")); ?>';
        }
        console.log('🟢 TEMPLATE: Updated maneliInquiryLists with missing nonces:', maneliInquiryLists);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('🟢 TEMPLATE: DOM ready for installment inquiries');
    console.log('Table exists:', document.getElementById('maneli-inquiry-list-tbody') !== null);
    console.log('maneliInquiryLists exists:', typeof maneliInquiryLists !== 'undefined');
    if (typeof maneliInquiryLists !== 'undefined') {
        console.log('maneliInquiryLists:', maneliInquiryLists);
        console.log('inquiry_filter nonce:', maneliInquiryLists.nonces?.inquiry_filter);
    }
    
    // Force trigger if script not loading
    setTimeout(function() {
        if (typeof jQuery !== 'undefined' && jQuery('#maneli-inquiry-list-tbody').length > 0) {
            var currentHtml = jQuery('#maneli-inquiry-list-tbody').html();
            if (currentHtml && (currentHtml.indexOf('<?php echo esc_js(__('Loading...', 'maneli-car-inquiry')); ?>') > -1 || currentHtml.indexOf(maneliInquiryLists?.text?.loading || 'Loading...') > -1) && typeof maneliInquiryLists !== 'undefined') {
                console.log('🟢 TEMPLATE: Force triggering manual load after 2 seconds');
                if (typeof window.loadInstallmentInquiriesList === 'function') {
                    window.loadInstallmentInquiriesList();
                } else if (typeof maneliInquiryLists !== 'undefined' && maneliInquiryLists.nonces && maneliInquiryLists.nonces.inquiry_filter) {
                    // Direct AJAX call as last resort
                    console.log('🟢 TEMPLATE: Making direct AJAX call');
                    jQuery.ajax({
                        url: maneliInquiryLists.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'maneli_filter_inquiries_ajax',
                            nonce: maneliInquiryLists.nonces.inquiry_filter,
                            _ajax_nonce: maneliInquiryLists.nonces.inquiry_filter,
                            page: 1,
                            search: '',
                            status: '',
                            sort: 'default'
                        },
                        success: function(response) {
                            console.log('🟢 TEMPLATE: Direct AJAX success:', response);
                            if (response && response.success && response.data && response.data.html) {
                                jQuery('#maneli-inquiry-list-tbody').html(response.data.html);
                                var rowCount = jQuery('#maneli-inquiry-list-tbody tr.crm-contact').length;
                                jQuery('#inquiry-count-badge').text(toPersianNumber(rowCount));
                                if (response.data.pagination_html) {
                                    jQuery('#inquiry-pagination').html(response.data.pagination_html);
                                }
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('🟢 TEMPLATE: Direct AJAX error:', status, error, xhr.responseText);
                        }
                    });
                }
            }
        }
    }, 2000);
});
</script>
