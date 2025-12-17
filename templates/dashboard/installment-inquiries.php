<?php
/**
 * Template for the Admin/Expert view of the Installment Inquiry List.
 *
 * This template displays statistical widgets, filter controls, and the table for installment inquiries.
 * The table body is populated and managed via AJAX.
 *
 * @package AutoPuzzle/Templates/Shortcodes/InquiryLists
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
$is_admin = current_user_can('manage_autopuzzle_inquiries');
$is_manager = in_array('autopuzzle_manager', $current_user->roles, true) || in_array('autopuzzle_admin', $current_user->roles, true);
$is_expert = in_array('autopuzzle_expert', $current_user->roles, true);
$is_customer = !$is_admin && !$is_manager && !$is_expert;

// Check if user has a pending inquiry step - redirect to wizard (CUSTOMERS ONLY)
if ($is_customer) {
    $current_user_id = get_current_user_id();
    $inquiry_step = get_user_meta($current_user_id, 'autopuzzle_inquiry_step', true);
    
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
    // Add AJAX variables for SMS sending in report pages
    ?>
    <script>
    // Global AJAX variables for SMS sending (same as installment-inquiries.php list page)
    var autopuzzleAjaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
    var autopuzzleAjaxNonce = '<?php echo wp_create_nonce('autopuzzle-ajax-nonce'); ?>';
    
    // CRITICAL: Ensure nonces are set BEFORE loading report template
    // Ensure maneliInquiryLists has update_installment_status nonce for single report view
    (function() {
        if (typeof maneliInquiryLists === 'undefined') {
            window.maneliInquiryLists = {
                ajax_url: '<?php echo esc_js(admin_url("admin-ajax.php")); ?>',
                nonces: {
                    ajax: '<?php echo esc_js(wp_create_nonce("autopuzzle-ajax-nonce")); ?>'
                },
                text: {}
            };
        }
        if (!maneliInquiryLists.nonces) {
            maneliInquiryLists.nonces = {};
        }
        if (!maneliInquiryLists.nonces.ajax) {
            maneliInquiryLists.nonces.ajax = '<?php echo esc_js(wp_create_nonce("autopuzzle-ajax-nonce")); ?>';
        }
        if (!maneliInquiryLists.nonces.update_installment_status) {
            maneliInquiryLists.nonces.update_installment_status = '<?php echo esc_js(wp_create_nonce("autopuzzle_installment_status")); ?>';
            console.log('🟢 REPORT: Added update_installment_status nonce to maneliInquiryLists');
        }
        if (!maneliInquiryLists.nonces.save_installment_note) {
            maneliInquiryLists.nonces.save_installment_note = '<?php echo esc_js(wp_create_nonce("autopuzzle_installment_note")); ?>';
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
                        autopuzzle_get_template_part('shortcodes/inquiry-lists/report-customer-installment', ['inquiry_id' => $inquiry_id]);
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- End::main-content -->
        <?php
    } else {
        // Admin/Expert sees admin report - already has wrapper
        autopuzzle_get_template_part('shortcodes/inquiry-lists/report-admin-installment', ['inquiry_id' => $inquiry_id]);
    }
    return;
}

// Autopuzzle_Admin_Dashboard_Widgets و Autopuzzle_CPT_Handler باید قبلا در هسته بارگذاری شده باشند.
$experts = $is_admin ? get_users(['role' => 'autopuzzle_expert', 'orderby' => 'display_name', 'order' => 'ASC']) : [];
?>

<div class="main-content app-content">
    <div class="container-fluid">

        <!-- Start::page-header -->
        <div class="d-flex align-items-center justify-content-between page-header-breadcrumb flex-wrap gap-2">
            <div>
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item">
                        <a href="<?php echo home_url('/dashboard'); ?>"><?php esc_html_e('Dashboard', 'autopuzzle'); ?></a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page"><?php esc_html_e('Installment Inquiries', 'autopuzzle'); ?></li>
                </ol>
                <h1 class="page-title fw-medium fs-18 mb-0"><?php esc_html_e('Installment Inquiries', 'autopuzzle'); ?></h1>
            </div>
        </div>
        <!-- End::page-header -->

<div class="row">
    <div class="col-xl-12">
        <?php 
        // Only show statistics widgets for admin/expert, not for customers
        if (!$is_customer) {
            echo Autopuzzle_Admin_Dashboard_Widgets::render_inquiry_statistics_widgets(); 
        }
        ?>
        
        <div class="card custom-card mt-4">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="card-title">
                    <?php esc_html_e('Installment Inquiries', 'autopuzzle'); ?><span class="badge bg-primary rounded ms-2 fs-12 align-middle" id="inquiry-count-badge">0</span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-success-light btn-sm" id="inquiry-export-csv-btn">
                        <i class="la la-download me-1"></i><?php esc_html_e('Export CSV', 'autopuzzle'); ?>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <!-- Filter Section -->
                <div class="p-3 border-bottom autopuzzle-mobile-filter" data-autopuzzle-mobile-filter>
                    <button
                        type="button"
                        class="autopuzzle-mobile-filter-toggle-btn d-flex align-items-center justify-content-between w-100 d-md-none"
                        data-autopuzzle-filter-toggle
                        aria-expanded="false"
                    >
                        <span class="fw-semibold"><?php esc_html_e('Filters', 'autopuzzle'); ?></span>
                        <i class="ri-arrow-down-s-line autopuzzle-mobile-filter-arrow"></i>
                    </button>
                    <div class="autopuzzle-mobile-filter-body" data-autopuzzle-filter-body>
                    <form id="autopuzzle-inquiry-filter-form" onsubmit="return false;">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">
                                        <i class="la la-search"></i>
                                    </span>
                                    <input type="search" id="inquiry-search-input" class="form-control form-control-sm" placeholder="<?php esc_attr_e('Search by customer name, car name, national ID or mobile...', 'autopuzzle'); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Filter Controls - All in one line -->
                        <?php
                        // Get initial status from URL query parameter
                        // For installment, we use tracking_status, not inquiry_status
                        $initial_status_param = '';
                        if (isset($_GET['tracking_status'])) {
                            $initial_status_param = sanitize_text_field($_GET['tracking_status']);
                        } elseif (isset($_GET['status'])) {
                            // Fallback for backward compatibility
                            $initial_status_param = sanitize_text_field($_GET['status']);
                        }
                        ?>
                        <div class="row g-3 align-items-end mt-1 autopuzzle-desktop-filter-row">
                            <!-- Status Filter -->
                            <div class="col-6 col-lg-2">
                                <label class="form-label"><?php esc_html_e('Status:', 'autopuzzle'); ?></label>
                                <select id="status-filter" class="form-select form-select-sm">
                                    <option value=""><?php esc_html_e('All Statuses', 'autopuzzle'); ?></option>
                                    <?php foreach (Autopuzzle_CPT_Handler::get_tracking_statuses() as $key => $label): ?>
                                        <option value="<?php echo esc_attr($key); ?>" <?php selected($initial_status_param, $key); ?>><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <?php if ($is_admin && !empty($experts)): ?>
                                <!-- Expert Filter -->
                                <div class="col-6 col-lg-2">
                                    <label class="form-label"><?php esc_html_e('Assigned Expert:', 'autopuzzle'); ?></label>
                                    <select id="expert-filter" class="form-select form-select-sm">
                                        <option value=""><?php esc_html_e('All Experts', 'autopuzzle'); ?></option>
                                        <option value="0" <?php selected(isset($_GET['assigned_expert']) ? $_GET['assigned_expert'] : '', '0'); ?>><?php esc_html_e('Unassigned', 'autopuzzle'); ?></option>
                                        <?php foreach ($experts as $expert) : ?>
                                            <option value="<?php echo esc_attr($expert->ID); ?>" <?php selected(isset($_GET['assigned_expert']) ? $_GET['assigned_expert'] : '', $expert->ID); ?>><?php echo esc_html($expert->display_name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Sort Filter -->
                            <div class="col-6 col-lg-2">
                                <label class="form-label"><?php esc_html_e('Sort:', 'autopuzzle'); ?></label>
                                <select id="inquiry-sort-filter" class="form-select form-select-sm">
                                    <option value="default"><?php esc_html_e('Default (Newest First)', 'autopuzzle'); ?></option>
                                    <option value="date_desc"><?php esc_html_e('Newest', 'autopuzzle'); ?></option>
                                    <option value="date_asc"><?php esc_html_e('Oldest', 'autopuzzle'); ?></option>
                                    <option value="status"><?php esc_html_e('By Status', 'autopuzzle'); ?></option>
                                </select>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="col-12 col-lg-2">
                                <div class="row g-2">
                                    <div class="col-6 col-lg-6">
                                        <button type="button" id="inquiry-apply-filters" class="btn btn-primary btn-sm w-100">
                                            <i class="la la-filter me-1"></i>
                                            <?php esc_html_e('Apply', 'autopuzzle'); ?>
                                        </button>
                                    </div>
                                    <div class="col-6 col-lg-6">
                                        <button type="button" id="inquiry-reset-filters" class="btn btn-outline-secondary btn-sm w-100">
                                            <i class="la la-refresh me-1"></i>
                                            <?php esc_html_e('Clear', 'autopuzzle'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table text-nowrap table-hover">
                        <thead>
                            <tr>
                                <th scope="col"><?php esc_html_e('ID', 'autopuzzle'); ?></th>
                                <th scope="col"><?php esc_html_e('Customer', 'autopuzzle'); ?></th>
                                <th scope="col"><?php esc_html_e('Car', 'autopuzzle'); ?></th>
                                <th scope="col"><?php esc_html_e('Status', 'autopuzzle'); ?></th>
                                <?php if ($is_admin) echo '<th scope="col">' . esc_html__('Assigned', 'autopuzzle') . '</th>'; ?>
                                <th scope="col"><?php esc_html_e('Date', 'autopuzzle'); ?></th>
                                <th scope="col"><?php esc_html_e('Actions', 'autopuzzle'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="autopuzzle-inquiry-list-tbody">
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="la la-spinner la-spin fs-24 text-muted"></i>
                                    <p class="mt-2 text-muted"><?php esc_html_e('Loading...', 'autopuzzle'); ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div id="inquiry-pagination" class="d-flex justify-content-center mt-3">
                </div>
                
                <div id="inquiry-list-loader" class="autopuzzle-list-loader">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden"><?php esc_html_e('Loading...', 'autopuzzle'); ?></span>
                    </div>
                </div>

                <div class="autopuzzle-pagination-wrapper mt-3 text-center"></div>
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
                <h5 class="modal-title"><?php esc_html_e('Set Tracking Status', 'autopuzzle'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="tracking-status-select" class="form-label"><?php esc_html_e('Select Status:', 'autopuzzle'); ?></label>
                    <select id="tracking-status-select" class="form-select">
                        <?php foreach (Autopuzzle_CPT_Handler::get_tracking_statuses() as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="calendar-wrapper" class="autopuzzle-initially-hidden">
                    <label id="calendar-label" class="form-label"><?php esc_html_e('Select Date:', 'autopuzzle'); ?></label>
                    <input type="text" id="tracking-date-picker" class="form-control autopuzzle-datepicker" readonly>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'autopuzzle'); ?></button>
                <button type="button" id="confirm-tracking-status-btn" class="btn btn-primary">
                    <?php esc_html_e('Confirm Status', 'autopuzzle'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Direct initialization check for installment inquiries
console.log('🟢 TEMPLATE: installment-inquiries.php script loaded');

const maneliDigitsHelper = window.maneliLocale || window.maneliDigits || {};

// Helper function to format numbers based on active locale
function formatNumberForLocale(num) {
    const htmlLang = (document.documentElement.getAttribute('lang') || '').toLowerCase();
    const htmlDir = (document.documentElement.getAttribute('dir') || '').toLowerCase();
    const shouldUsePersian = (maneliDigitsHelper && typeof maneliDigitsHelper.shouldUsePersianDigits === 'function')
        ? maneliDigitsHelper.shouldUsePersianDigits()
        : (htmlLang.indexOf('fa') === 0 || htmlDir === 'rtl');

    if (maneliDigitsHelper && typeof maneliDigitsHelper.ensureDigits === 'function') {
        return maneliDigitsHelper.ensureDigits(num, shouldUsePersian ? 'fa' : 'en');
    }

    if (!shouldUsePersian) {
        return String(num);
    }

    const persianDigitsFallback = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    const englishDigitsFallback = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    return String(num).replace(/\d/g, function(digit) {
        return persianDigitsFallback[englishDigitsFallback.indexOf(digit)];
    });
}

// CRITICAL: Initialize maneliInquiryLists immediately if not already set
// Also ensure update_installment_status nonce exists even if object already exists
if (typeof maneliInquiryLists === 'undefined') {
    console.warn('🔵 TEMPLATE: maneliInquiryLists is undefined! Creating fallback...');
    window.maneliInquiryLists = {
        ajax_url: '<?php echo esc_js(admin_url("admin-ajax.php")); ?>',
        nonces: {
            inquiry_filter: '<?php echo esc_js(wp_create_nonce("autopuzzle_inquiry_filter_nonce")); ?>',
            details: '<?php echo esc_js(wp_create_nonce("autopuzzle_inquiry_details_nonce")); ?>',
            assign_expert: '<?php echo esc_js(wp_create_nonce("autopuzzle_inquiry_assign_expert_nonce")); ?>',
            tracking_status: '<?php echo esc_js(wp_create_nonce("autopuzzle_tracking_status_nonce")); ?>',
            update_installment_status: '<?php echo esc_js(wp_create_nonce("autopuzzle_installment_status")); ?>',
            save_installment_note: '<?php echo esc_js(wp_create_nonce("autopuzzle_installment_note")); ?>'
        },
        experts: <?php 
            $experts_list = [];
            if (current_user_can('manage_autopuzzle_inquiries')) {
                $experts = get_users(['role' => 'autopuzzle_expert', 'orderby' => 'display_name', 'order' => 'ASC']);
                foreach ($experts as $expert) {
                    $experts_list[] = ['id' => $expert->ID, 'name' => $expert->display_name ?: $expert->user_login];
                }
            }
            echo json_encode($experts_list); 
        ?>,
        text: {
            error: '<?php echo esc_js(__('Error', 'autopuzzle')); ?>',
            success: '<?php echo esc_js(__('Success', 'autopuzzle')); ?>',
            server_error: '<?php echo esc_js(__('Server error. Please try again.', 'autopuzzle')); ?>',
            unknown_error: '<?php echo esc_js(__('Unknown error', 'autopuzzle')); ?>',
            modal_not_found_error: '<?php echo esc_js(__('Error: Status modal not found. Please contact support.', 'autopuzzle')); ?>',
            start_progress_title: '<?php echo esc_js(__('Start Follow-up', 'autopuzzle')); ?>',
            start_progress_confirm: '<?php echo esc_js(__('Are you sure you want to start follow-up for this inquiry?', 'autopuzzle')); ?>',
            schedule_meeting_title: '<?php echo esc_js(__('Schedule Meeting', 'autopuzzle')); ?>',
            meeting_date_label: '<?php echo esc_js(__('Meeting Date', 'autopuzzle')); ?>',
            meeting_time_label: '<?php echo esc_js(__('Meeting Time', 'autopuzzle')); ?>',
            select_date: '<?php echo esc_js(__('Select Date', 'autopuzzle')); ?>',
            schedule_button: '<?php echo esc_js(__('Schedule', 'autopuzzle')); ?>',
            schedule_followup_title: '<?php echo esc_js(__('Schedule Follow-up', 'autopuzzle')); ?>',
            schedule_followup_button: '<?php echo esc_js(__('Schedule Follow-up', 'autopuzzle')); ?>',
            followup_date_label: '<?php echo esc_js(__('Follow-up Date', 'autopuzzle')); ?>',
            note_label_optional: '<?php echo esc_js(__('Note (Optional)', 'autopuzzle')); ?>',
            enter_note: '<?php echo esc_js(__('Enter your note...', 'autopuzzle')); ?>',
            followup_date_required: '<?php echo esc_js(__('Please enter follow-up date', 'autopuzzle')); ?>',
            meeting_required: '<?php echo esc_js(__('Please enter meeting date and time', 'autopuzzle')); ?>',
            complete_title: '<?php echo esc_js(__('Complete Inquiry', 'autopuzzle')); ?>',
            complete_confirm: '<?php echo esc_js(__('Are you sure you want to complete this inquiry?', 'autopuzzle')); ?>',
            reject_title: '<?php echo esc_js(__('Reject Inquiry', 'autopuzzle')); ?>',
            reject_confirm: '<?php echo esc_js(__('Are you sure you want to reject this inquiry?', 'autopuzzle')); ?>',
            rejection_reason_label: '<?php echo esc_js(__('Rejection Reason', 'autopuzzle')); ?>',
            enter_reason: '<?php echo esc_js(__('Please enter reason...', 'autopuzzle')); ?>',
            reason_required: '<?php echo esc_js(__('Please enter reason with at least 10 characters', 'autopuzzle')); ?>',
            confirm_button: '<?php echo esc_js(__('Yes', 'autopuzzle')); ?>',
            cancel_button: '<?php echo esc_js(__('Cancel', 'autopuzzle')); ?>',
            ok_button: '<?php echo esc_js(__('OK', 'autopuzzle')); ?>',
            status_updated: '<?php echo esc_js(__('Status updated successfully', 'autopuzzle')); ?>',
            status_update_error: '<?php echo esc_js(__('Error updating status', 'autopuzzle')); ?>',
            loading: '<?php echo esc_js(__('Loading...', 'autopuzzle')); ?>',
            loading_inquiries: '<?php echo esc_js(__('Loading inquiries...', 'autopuzzle')); ?>',
            loading_inquiries_error: '<?php echo esc_js(__('Error loading list', 'autopuzzle')); ?>',
            connection_error: '<?php echo esc_js(__('Connection error (0). Please check your internet connection.', 'autopuzzle')); ?>',
            server_error_500: '<?php echo esc_js(__('Server error (500). Please contact the administrator.', 'autopuzzle')); ?>',
            connection_error_code: '<?php echo esc_js(__('Connection error (Code:', 'autopuzzle')); ?>',
            error_occurred: '<?php echo esc_js(__('An error occurred.', 'autopuzzle')); ?>',
            note_saved_success: '<?php echo esc_js(__('Note saved successfully.', 'autopuzzle')); ?>',
            unauthorized_403: '<?php echo esc_js(__('Unauthorized access (403). Please refresh the page and log in again.', 'autopuzzle')); ?>',
            please_refresh: '<?php echo esc_js(__('Please refresh the page.', 'autopuzzle')); ?>',
            operation_success: '<?php echo esc_js(__('Operation completed successfully.', 'autopuzzle')); ?>',
            amount: '<?php echo esc_js(__('Amount', 'autopuzzle')); ?>',
            toman: '<?php echo esc_js(__('Toman', 'autopuzzle')); ?>',
            send_button: '<?php echo esc_js(__('Yes, Send', 'autopuzzle')); ?>',
            final_approval_title: '<?php echo esc_js(__('Final Approval?', 'autopuzzle')); ?>',
            customer_request_will_be_approved: '<?php echo esc_js(__('Customer request will be approved', 'autopuzzle')); ?>',
            approve_button: '<?php echo esc_js(__('Yes, Approve', 'autopuzzle')); ?>',
            submit_rejection: '<?php echo esc_js(__('Submit Rejection', 'autopuzzle')); ?>',
            enter_rejection_reason: '<?php echo esc_js(__('Please enter rejection reason...', 'autopuzzle')); ?>',
            status_will_change_to_in_progress: '<?php echo esc_js(__('Status will change to "In Progress"', 'autopuzzle')); ?>',
            downpayment_amount_required: '<?php echo esc_js(__('Please enter down payment amount', 'autopuzzle')); ?>',
            cancel_inquiry_title: '<?php echo esc_js(__('Cancel Inquiry', 'autopuzzle')); ?>',
            cancel_reason_label: '<?php echo esc_js(__('Cancellation Reason', 'autopuzzle')); ?>',
            send_sms: '<?php echo esc_js(__('Send SMS', 'autopuzzle')); ?>',
            recipient: '<?php echo esc_js(__('Recipient:', 'autopuzzle')); ?>',
            message: '<?php echo esc_js(__('Message:', 'autopuzzle')); ?>',
            enter_message: '<?php echo esc_js(__('Enter your message...', 'autopuzzle')); ?>',
            please_enter_message: '<?php echo esc_js(__('Please enter a message', 'autopuzzle')); ?>',
            sms_sent_successfully: '<?php echo esc_js(__('SMS sent successfully!', 'autopuzzle')); ?>',
            failed_to_send_sms: '<?php echo esc_js(__('Failed to send SMS', 'autopuzzle')); ?>',
            send: '<?php echo esc_js(__('Send', 'autopuzzle')); ?>',
            sending: '<?php echo esc_js(__('Sending...', 'autopuzzle')); ?>',
            please_wait: '<?php echo esc_js(__('Please wait', 'autopuzzle')); ?>',
            missing_required_info: '<?php echo esc_js(__('Missing required information.', 'autopuzzle')); ?>',
            nonce_missing: '<?php echo esc_js(__('Nonce is missing. Please refresh the page and try again.', 'autopuzzle')); ?>',
            security_token_missing: '<?php echo esc_js(__('Security token is missing. Please refresh the page.', 'autopuzzle')); ?>',
            network_error: '<?php echo esc_js(__('Network error. Please check your connection.', 'autopuzzle')); ?>',
            security_verification_failed: '<?php echo esc_js(__('Security verification failed. Please refresh the page and try again.', 'autopuzzle')); ?>',
            unable_to_find_inquiry: '<?php echo esc_js(__('Unable to find inquiry ID. Please refresh the page.', 'autopuzzle')); ?>',
            script_init_error: '<?php echo esc_js(__('Script initialization error. Please refresh the page.', 'autopuzzle')); ?>',
            server_error_contact_support: '<?php echo esc_js(__('Server error. Please contact support if the problem persists.', 'autopuzzle')); ?>',
            unauthorized_access: '<?php echo esc_js(__('Unauthorized access.', 'autopuzzle')); ?>'
        },
        installment_rejection_reasons: <?php 
            $options = get_option('autopuzzle_inquiry_all_options', []);
            $reasons = array_filter(array_map('trim', explode("\n", $options['installment_rejection_reasons'] ?? '')));
            echo json_encode(array_values($reasons)); 
        ?>
    };
    console.log('🔵 TEMPLATE: maneliInquiryLists fallback created:', window.maneliInquiryLists);
} else {
    console.log('🔵 TEMPLATE: maneliInquiryLists already exists:', maneliInquiryLists);
    // Ensure update_installment_status nonce exists even if object already exists
    if (!maneliInquiryLists.nonces || !maneliInquiryLists.nonces.update_installment_status) {
        console.warn('🔵 TEMPLATE: Adding missing update_installment_status nonce to existing maneliInquiryLists');
        if (!maneliInquiryLists.nonces) {
            maneliInquiryLists.nonces = {};
        }
        maneliInquiryLists.nonces.update_installment_status = '<?php echo esc_js(wp_create_nonce("autopuzzle_installment_status")); ?>';
        if (!maneliInquiryLists.nonces.save_installment_note) {
            maneliInquiryLists.nonces.save_installment_note = '<?php echo esc_js(wp_create_nonce("autopuzzle_installment_note")); ?>';
        }
        console.log('🟢 TEMPLATE: Updated maneliInquiryLists with missing nonces:', maneliInquiryLists);
    }
}

const htmlLangAttr = (document.documentElement.getAttribute('lang') || '').toLowerCase();
const htmlDirAttr = (document.documentElement.getAttribute('dir') || '').toLowerCase();
const fallbackLocale = htmlLangAttr || (htmlDirAttr === 'rtl' ? 'fa' : (htmlDirAttr === 'ltr' ? 'en' : ''));

if (typeof maneliInquiryLists.locale === 'undefined' && fallbackLocale) {
    maneliInquiryLists.locale = fallbackLocale;
}

if (typeof maneliInquiryLists.use_persian_digits === 'undefined') {
    const localeSource = maneliInquiryLists.locale || fallbackLocale || '';
    maneliInquiryLists.use_persian_digits = localeSource.toLowerCase().indexOf('fa') === 0;
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('🟢 TEMPLATE: DOM ready for installment inquiries');
    console.log('Table exists:', document.getElementById('autopuzzle-inquiry-list-tbody') !== null);
    console.log('maneliInquiryLists exists:', typeof maneliInquiryLists !== 'undefined');
    if (typeof maneliInquiryLists !== 'undefined') {
        console.log('maneliInquiryLists:', maneliInquiryLists);
        console.log('inquiry_filter nonce:', maneliInquiryLists.nonces?.inquiry_filter);
    }
    
    // Force trigger if script not loading
    setTimeout(function() {
        if (typeof jQuery !== 'undefined' && jQuery('#autopuzzle-inquiry-list-tbody').length > 0) {
            var currentHtml = jQuery('#autopuzzle-inquiry-list-tbody').html();
            if (currentHtml && (currentHtml.indexOf('<?php echo esc_js(__('Loading...', 'autopuzzle')); ?>') > -1 || currentHtml.indexOf(maneliInquiryLists?.text?.loading || 'Loading...') > -1) && typeof maneliInquiryLists !== 'undefined') {
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
                            action: 'autopuzzle_filter_inquiries_ajax',
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
                                jQuery('#autopuzzle-inquiry-list-tbody').html(response.data.html);
                                var rowCount = jQuery('#autopuzzle-inquiry-list-tbody tr.crm-contact').length;
                                jQuery('#inquiry-count-badge').text(formatNumberForLocale(rowCount));
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

<script>
// Global AJAX variables for SMS sending (fallback for timing - main localization is in class-dashboard-handler.php)
var autopuzzleAjaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
var autopuzzleAjaxNonce = '<?php echo wp_create_nonce('autopuzzle-ajax-nonce'); ?>';
</script>

<?php
// Include shared SMS History Modal template
autopuzzle_get_template_part('partials/sms-history-modal');
?>
