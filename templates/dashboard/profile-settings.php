<?php
/**
 * Profile Settings Template
 * مانلی خودرو - تنظیمات پروفایل
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current user
$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Check user role
$is_admin = current_user_can('manage_maneli_inquiries');
$is_manager = in_array('maneli_manager', $current_user->roles, true) || in_array('maneli_admin', $current_user->roles, true);
$is_expert = in_array('maneli_expert', $current_user->roles, true);
$is_customer = !$is_admin && !$is_manager && !$is_expert;

// Get name fields - use WordPress user meta first
$first_name = get_user_meta($user_id, 'first_name', true);
$last_name = get_user_meta($user_id, 'last_name', true);

// Fallback to display name if first_name/last_name are empty
if (empty($first_name) && empty($last_name)) {
    $full_name = $current_user->display_name;
    $name_parts = explode(' ', $full_name, 2);
    $first_name = $name_parts[0] ?? '';
    $last_name = $name_parts[1] ?? '';
}

// Get user meta data - Basic info
$designation = get_user_meta($user_id, 'designation', true);
$mobile_number = get_user_meta($user_id, 'mobile_number', true);
$address = get_user_meta($user_id, 'address', true);
$profile_image_id = get_user_meta($user_id, 'profile_image_id', true);
$profile_image = '';
if (!empty($profile_image_id)) {
    $profile_image = wp_get_attachment_image_url($profile_image_id, 'full');
}

// Get user meta data - Identity information (like admin)
$national_code = get_user_meta($user_id, 'national_code', true);
$father_name = get_user_meta($user_id, 'father_name', true);
$birth_date = get_user_meta($user_id, 'birth_date', true);
$phone_number = get_user_meta($user_id, 'phone_number', true);

// Job information
$occupation = get_user_meta($user_id, 'occupation', true);
$job_title = get_user_meta($user_id, 'job_title', true);
$job_type = get_user_meta($user_id, 'job_type', true);
$income_level = get_user_meta($user_id, 'income_level', true);
$workplace_status = get_user_meta($user_id, 'workplace_status', true);

// Address information
$residency_status = get_user_meta($user_id, 'residency_status', true);

// Bank information
$bank_name = get_user_meta($user_id, 'bank_name', true);
$account_number = get_user_meta($user_id, 'account_number', true);
$branch_code = get_user_meta($user_id, 'branch_code', true);
$branch_name = get_user_meta($user_id, 'branch_name', true);

// Notification settings
$push_notifications = get_user_meta($user_id, 'push_notifications', true) !== '0';
$email_notifications = get_user_meta($user_id, 'email_notifications', true) !== '0';
$in_app_notifications = get_user_meta($user_id, 'in_app_notifications', true) !== '0';
$sms_notifications = get_user_meta($user_id, 'sms_notifications', true) !== '0';

// Security settings
$email_verification = get_user_meta($user_id, 'email_verification', true) !== '0';
$sms_verification = get_user_meta($user_id, 'sms_verification', true);
$phone_verification = get_user_meta($user_id, 'phone_verification', true);
?>

<div class="main-content app-content">
    <div class="container-fluid">

        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between page-header-breadcrumb flex-wrap gap-2">
            <div>
                <nav>
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="<?php echo esc_url(home_url('/dashboard')); ?>"><?php esc_html_e('Pages', 'maneli-car-inquiry'); ?></a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php esc_html_e('Profile Settings', 'maneli-car-inquiry'); ?></li>
                    </ol>
                </nav>
                <h1 class="page-title fw-medium fs-18 mb-0"><?php esc_html_e('Profile Settings', 'maneli-car-inquiry'); ?></h1>
            </div>
        </div>
        <!-- Page Header Close -->

        <!-- Start::row-1 -->
        <div class="row gap-3 justify-content-center">
    <div class="col-xl-9">
        <div class="card custom-card">
            <ul class="nav nav-tabs tab-style-8 scaleX rounded m-3 profile-settings-tab gap-2" id="myTab4" role="tablist">
                <li class="nav-item me-1" role="presentation">
                    <button class="nav-link px-4 bg-primary-transparent active" id="account" data-bs-toggle="tab" data-bs-target="#account-pane" type="button" role="tab" aria-controls="account-pane" aria-selected="true"><?php esc_html_e('My Account', 'maneli-car-inquiry'); ?></button>
                </li>
                <li class="nav-item me-1" role="presentation">
                    <button class="nav-link px-4 bg-primary-transparent" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents-tab-pane" type="button" role="tab" aria-controls="documents-tab-pane" aria-selected="false"><?php esc_html_e('Documents', 'maneli-car-inquiry'); ?></button>
                </li>
                <li class="nav-item me-1" role="presentation">
                    <button class="nav-link px-4 bg-primary-transparent" id="notification-tab" data-bs-toggle="tab" data-bs-target="#notification-tab-pane" type="button" role="tab" aria-controls="notification-tab-pane" aria-selected="false"><?php esc_html_e('Notifications', 'maneli-car-inquiry'); ?></button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link px-4 bg-primary-transparent" id="security-tab" data-bs-toggle="tab" data-bs-target="#security-tab-pane" type="button" role="tab" aria-controls="security-tab-pane" aria-selected="false" tabindex="-1"><?php esc_html_e('Security', 'maneli-car-inquiry'); ?></button>
                </li>
            </ul>
            <form id="profile-settings-form">
                <div class="p-3 border-bottom border-top border-block-end-dashed tab-content">
                    <!-- Account Tab -->
                    <div class="tab-pane show active overflow-hidden p-0 border-0" id="account-pane" role="tabpanel" aria-labelledby="account" tabindex="0">
                        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-1">
                            <div class="fw-semibold d-block fs-15"><?php esc_html_e('Account Settings:', 'maneli-car-inquiry'); ?></div>
                            <div class="btn btn-primary btn-sm" onclick="resetAccountForm()"><i class="ri-loop-left-line lh-1 me-2"></i><?php esc_html_e('Reset Changes', 'maneli-car-inquiry'); ?></div>
                        </div>
                        <div class="row gy-3">
                            <div class="col-xl-12">
                                <div class="d-flex align-items-start flex-wrap gap-3">
                                    <div>
                                        <span class="avatar avatar-xxl">
                                            <img src="<?php echo esc_url($profile_image ?: MANELI_PLUGIN_URL . 'assets/images/faces/15.jpg'); ?>" alt="Profile Image" id="profile-avatar-preview">
                                        </span>
                                    </div>
                                    <div>
                                        <span class="fw-medium d-block mb-2"><?php esc_html_e('Profile Picture', 'maneli-car-inquiry'); ?></span>
                                        <div class="btn-list mb-1">
                                            <label for="profile-image-upload" class="btn btn-sm btn-primary btn-wave" style="cursor: pointer;">
                                                <i class="ri-upload-2-line me-1"></i><?php esc_html_e('Change Image', 'maneli-car-inquiry'); ?>
                                            </label>
                                            <input type="file" id="profile-image-upload" accept="image/jpeg,image/png,image/gif" style="display: none;">
                                            <button type="button" class="btn btn-sm btn-primary1-light btn-wave" onclick="deleteProfileImage()"><i class="ri-delete-bin-line me-1"></i><?php esc_html_e('Delete', 'maneli-car-inquiry'); ?></button>
                                        </div>
                                                       <span class="d-block fs-12 text-muted"><?php esc_html_e('Use JPEG, PNG or GIF formats. Best size: 200x200 pixels. File size less than 2 MB', 'maneli-car-inquiry'); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <label for="profile-first-name" class="form-label"><?php esc_html_e('First Name:', 'maneli-car-inquiry'); ?></label>
                                <input type="text" class="form-control" id="profile-first-name" name="first_name" value="<?php echo esc_attr($first_name); ?>" placeholder="<?php esc_attr_e('Enter first name', 'maneli-car-inquiry'); ?>">
                            </div>
                            <div class="col-xl-6">
                                <label for="profile-last-name" class="form-label"><?php esc_html_e('Last Name:', 'maneli-car-inquiry'); ?></label>
                                <input type="text" class="form-control" id="profile-last-name" name="last_name" value="<?php echo esc_attr($last_name); ?>" placeholder="<?php esc_attr_e('Enter last name', 'maneli-car-inquiry'); ?>">
                            </div>
                            <div class="col-xl-6">
                                <label for="profile-phn-no" class="form-label"><?php esc_html_e('Mobile Number:', 'maneli-car-inquiry'); ?></label>
                                <input type="text" class="form-control" id="profile-phn-no" name="mobile_number" value="<?php echo esc_attr($mobile_number); ?>" placeholder="<?php esc_attr_e('Enter mobile number', 'maneli-car-inquiry'); ?>">
                            </div>
                            
                            <!-- Identity Information Section -->
                            <div class="col-xl-12">
                                <hr class="my-3">
                                <h5 class="fw-semibold mb-3"><?php esc_html_e('Identity Information', 'maneli-car-inquiry'); ?></h5>
                            </div>
                            
                            <div class="col-xl-6">
                                <label for="profile-national-code" class="form-label"><?php esc_html_e('National Code:', 'maneli-car-inquiry'); ?></label>
                                <input type="text" class="form-control" id="profile-national-code" name="national_code" value="<?php echo esc_attr($national_code); ?>" placeholder="<?php esc_attr_e('10-digit national ID', 'maneli-car-inquiry'); ?>" pattern="\d{10}" maxlength="10">
                            </div>
                            <div class="col-xl-6">
                                <label for="profile-father-name" class="form-label"><?php esc_html_e('Father\'s Name:', 'maneli-car-inquiry'); ?></label>
                                <input type="text" class="form-control" id="profile-father-name" name="father_name" value="<?php echo esc_attr($father_name); ?>" placeholder="<?php esc_attr_e('Enter father\'s name', 'maneli-car-inquiry'); ?>">
                            </div>
                            <div class="col-xl-6">
                                <label for="profile-birth-date" class="form-label"><?php esc_html_e('Birth Date:', 'maneli-car-inquiry'); ?></label>
                                <input type="text" class="form-control maneli-datepicker" id="profile-birth-date" name="birth_date" value="<?php echo esc_attr($birth_date); ?>" placeholder="<?php esc_attr_e('1370/01/01', 'maneli-car-inquiry'); ?>">
                            </div>
                            <div class="col-xl-6">
                                <label for="profile-phone" class="form-label"><?php esc_html_e('Phone Number:', 'maneli-car-inquiry'); ?></label>
                                <input type="tel" class="form-control" id="profile-phone" name="phone_number" value="<?php echo esc_attr($phone_number); ?>" placeholder="<?php esc_attr_e('02112345678', 'maneli-car-inquiry'); ?>">
                            </div>
                            
                            <!-- Job Information Section -->
                            <div class="col-xl-12">
                                <hr class="my-3">
                                <h5 class="fw-semibold mb-3"><?php esc_html_e('Job Information', 'maneli-car-inquiry'); ?></h5>
                            </div>
                            
                            <div class="col-xl-6">
                                <label for="profile-job-type" class="form-label"><?php esc_html_e('Job Type:', 'maneli-car-inquiry'); ?></label>
                                <select class="form-select" id="profile-job-type" name="job_type">
                                    <option value="">-- <?php esc_html_e('Select', 'maneli-car-inquiry'); ?> --</option>
                                    <option value="self" <?php selected($job_type, 'self'); ?>><?php esc_html_e('Self-Employed', 'maneli-car-inquiry'); ?></option>
                                    <option value="employee" <?php selected($job_type, 'employee'); ?>><?php esc_html_e('Employee', 'maneli-car-inquiry'); ?></option>
                                </select>
                            </div>
                            <div class="col-xl-6 buyer-job-title-wrapper maneli-initially-hidden" id="profile-job-title-wrapper">
                                <label for="profile-job-title" class="form-label"><?php esc_html_e('Job Title:', 'maneli-car-inquiry'); ?></label>
                                <input type="text" class="form-control" id="profile-job-title" name="job_title" value="<?php echo esc_attr($job_title ?: $occupation); ?>" placeholder="<?php esc_attr_e('Example: Engineer', 'maneli-car-inquiry'); ?>">
                            </div>
                            <div class="col-xl-6">
                                <label for="profile-income" class="form-label"><?php esc_html_e('Income Level (Toman):', 'maneli-car-inquiry'); ?></label>
                                <input type="number" class="form-control" id="profile-income" name="income_level" value="<?php echo esc_attr($income_level); ?>" placeholder="<?php esc_attr_e('Example: 50000000', 'maneli-car-inquiry'); ?>">
                            </div>
                            <div class="col-xl-6 buyer-residency-wrapper maneli-initially-hidden" id="profile-residency-wrapper">
                                <label for="profile-residency-status" class="form-label"><?php esc_html_e('Residency Status:', 'maneli-car-inquiry'); ?></label>
                                <select class="form-select" id="profile-residency-status-job" name="residency_status">
                                    <option value="">-- <?php esc_html_e('Select', 'maneli-car-inquiry'); ?> --</option>
                                    <option value="owner" <?php selected($residency_status, 'owner'); ?>><?php esc_html_e('Owner', 'maneli-car-inquiry'); ?></option>
                                    <option value="tenant" <?php selected($residency_status, 'tenant'); ?>><?php esc_html_e('Tenant', 'maneli-car-inquiry'); ?></option>
                                </select>
                            </div>
                            
                            <!-- Address Information Section -->
                            <div class="col-xl-12">
                                <hr class="my-3">
                                <h5 class="fw-semibold mb-3"><?php esc_html_e('Contact Information', 'maneli-car-inquiry'); ?></h5>
                            </div>
                            
                            <div class="col-xl-12">
                                <label for="profile-address" class="form-label"><?php esc_html_e('Address:', 'maneli-car-inquiry'); ?></label>
                                <textarea class="form-control" id="profile-address" name="address" rows="3" placeholder="<?php esc_attr_e('Enter address', 'maneli-car-inquiry'); ?>"><?php echo esc_textarea($address); ?></textarea>
                            </div>
                            
                            <!-- Bank Information Section -->
                            <div class="col-xl-12">
                                <hr class="my-3">
                                <h5 class="fw-semibold mb-3"><?php esc_html_e('Bank Information', 'maneli-car-inquiry'); ?></h5>
                            </div>
                            
                            <div class="col-xl-6">
                                <label for="profile-bank-name" class="form-label"><?php esc_html_e('Bank Name:', 'maneli-car-inquiry'); ?></label>
                                <input type="text" class="form-control" id="profile-bank-name" name="bank_name" value="<?php echo esc_attr($bank_name); ?>" placeholder="<?php esc_attr_e('Enter bank name', 'maneli-car-inquiry'); ?>">
                            </div>
                            <div class="col-xl-6">
                                <label for="profile-account-number" class="form-label"><?php esc_html_e('Account Number:', 'maneli-car-inquiry'); ?></label>
                                <input type="text" class="form-control" id="profile-account-number" name="account_number" value="<?php echo esc_attr($account_number); ?>" placeholder="<?php esc_attr_e('Enter account number', 'maneli-car-inquiry'); ?>">
                            </div>
                            <div class="col-xl-6">
                                <label for="profile-branch-code" class="form-label"><?php esc_html_e('Branch Code:', 'maneli-car-inquiry'); ?></label>
                                <input type="text" class="form-control" id="profile-branch-code" name="branch_code" value="<?php echo esc_attr($branch_code); ?>" placeholder="<?php esc_attr_e('Enter branch code', 'maneli-car-inquiry'); ?>">
                            </div>
                            <div class="col-xl-6">
                                <label for="profile-branch-name" class="form-label"><?php esc_html_e('Branch Name:', 'maneli-car-inquiry'); ?></label>
                                <input type="text" class="form-control" id="profile-branch-name" name="branch_name" value="<?php echo esc_attr($branch_name); ?>" placeholder="<?php esc_attr_e('Enter branch name', 'maneli-car-inquiry'); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Documents Tab -->
                    <div class="tab-pane overflow-hidden p-0 border-0" id="documents-tab-pane" role="tabpanel" aria-labelledby="documents-tab" tabindex="0">
                        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-1">
                            <div class="fw-semibold d-block fs-15"><?php esc_html_e('Documents:', 'maneli-car-inquiry'); ?></div>
                        </div>
                        <div class="row gx-5 gy-3">
                            <div class="col-xl-12">
                                <p class="fs-14 mb-1 fw-medium"><?php esc_html_e('Upload Required Documents', 'maneli-car-inquiry'); ?></p>
                                <p class="fs-12 mb-0 text-muted"><?php esc_html_e('Please upload the required documents for your application.', 'maneli-car-inquiry'); ?></p>
                            </div>
                            <?php 
                            // Get required documents from settings
                            $required_docs_raw = get_option('maneli_inquiry_all_options', [])['customer_required_documents'] ?? '';
                            $required_docs = array_filter(array_map('trim', explode("\n", $required_docs_raw)));
                            
                            // Get uploaded documents for this user
                            $uploaded_docs = get_user_meta($user_id, 'customer_uploaded_documents', true) ?: [];
                            ?>
                            <?php if (!empty($required_docs)): ?>
                                <?php foreach ($required_docs as $doc_name): ?>
                                    <?php 
                                    // Check if this document has been uploaded
                                    $is_uploaded = false;
                                    $uploaded_file_url = '';
                                    $doc_status = 'pending';
                                    foreach ($uploaded_docs as $uploaded) {
                                        if (isset($uploaded['name']) && $uploaded['name'] === $doc_name) {
                                            $is_uploaded = true;
                                            $uploaded_file_url = $uploaded['file'] ?? '';
                                            $doc_status = $uploaded['status'] ?? 'pending';
                                            break;
                                        }
                                    }
                                    ?>
                                    <div class="col-xl-12">
                                        <div class="border rounded p-3 document-item-customer" data-doc-name="<?php echo esc_attr($doc_name); ?>">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-fill">
                                                    <label class="fw-semibold mb-2 d-block">
                                                        <?php echo esc_html($doc_name); ?>
                                                    </label>
                                                    <?php if ($is_uploaded && $doc_status === 'approved'): ?>
                                                        <div class="alert alert-success border-success py-2 px-3 mb-2">
                                                            <i class="la la-check-circle me-2"></i>
                                                            <?php esc_html_e('Approved', 'maneli-car-inquiry'); ?>
                                                        </div>
                                                        <?php if ($uploaded_file_url): ?>
                                                            <a href="<?php echo esc_url($uploaded_file_url); ?>" target="_blank" class="btn btn-sm btn-primary">
                                                                <i class="la la-download"></i> <?php esc_html_e('Download', 'maneli-car-inquiry'); ?>
                                                            </a>
                                                        <?php endif; ?>
                                                    <?php elseif ($is_uploaded && $doc_status === 'rejected'): ?>
                                                        <div class="alert alert-danger border-danger py-2 px-3 mb-2">
                                                            <i class="la la-times-circle me-2"></i>
                                                            <?php esc_html_e('Rejected', 'maneli-car-inquiry'); ?>
                                                        </div>
                                                        <?php if ($uploaded_file_url): ?>
                                                            <a href="<?php echo esc_url($uploaded_file_url); ?>" target="_blank" class="btn btn-sm btn-primary ms-2">
                                                                <i class="la la-download"></i> <?php esc_html_e('Download', 'maneli-car-inquiry'); ?>
                                                            </a>
                                                        <?php endif; ?>
                                                    <?php elseif ($is_uploaded): ?>
                                                        <div class="alert alert-info border-info py-2 px-3 mb-2">
                                                            <i class="la la-clock me-2"></i>
                                                            <?php esc_html_e('Awaiting Review', 'maneli-car-inquiry'); ?>
                                                        </div>
                                                        <?php if ($uploaded_file_url): ?>
                                                            <a href="<?php echo esc_url($uploaded_file_url); ?>" target="_blank" class="btn btn-sm btn-primary ms-2">
                                                                <i class="la la-download"></i> <?php esc_html_e('Download', 'maneli-car-inquiry'); ?>
                                                            </a>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <input type="file" 
                                                               accept=".pdf,.jpg,.jpeg,.png" 
                                                               class="form-control customer-doc-file-input" 
                                                               data-user-id="<?php echo esc_attr($user_id); ?>"
                                                               data-doc-name="<?php echo esc_attr($doc_name); ?>">
                                                        <small class="text-muted d-block mt-1">
                                                            <?php esc_html_e('Accepted formats: PDF, JPG, PNG', 'maneli-car-inquiry'); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-xl-12">
                                    <p class="text-muted"><?php esc_html_e('No documents have been configured yet by the admin.', 'maneli-car-inquiry'); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Notifications Tab -->
                    <div class="tab-pane overflow-hidden p-0 border-0" id="notification-tab-pane" role="tabpanel" aria-labelledby="notification-tab" tabindex="0">
                        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-1">
                            <div class="fw-semibold d-block fs-15"><?php esc_html_e('Notification Settings:', 'maneli-car-inquiry'); ?></div>
                            <div class="btn btn-primary btn-sm" onclick="resetNotificationForm()"><i class="ri-loop-left-line lh-1 me-2"></i><?php esc_html_e('Reset Changes', 'maneli-car-inquiry'); ?></div>
                        </div>
                        <div class="row gx-5 gy-3">
                            <div class="col-xl-12">
                                <p class="fs-14 mb-1 fw-medium"><?php esc_html_e('Configure Notifications', 'maneli-car-inquiry'); ?></p>
                                <p class="fs-12 mb-0 text-muted"><?php esc_html_e('Users can configure their experience to receive notifications about important events.', 'maneli-car-inquiry'); ?></p>
                            </div>
                            <div class="col-xl-12">
                                <div class="d-flex align-items-top justify-content-between mt-3">
                                    <div class="mail-notification-settings">
                                        <p class="fs-14 mb-1 fw-medium"><?php esc_html_e('Push Notifications', 'maneli-car-inquiry'); ?></p>
                                        <p class="fs-12 mb-0 text-muted"><?php esc_html_e('Notifications sent to user mobile or desktop devices.', 'maneli-car-inquiry'); ?></p>
                                    </div>
                                    <div class="toggle <?php echo $push_notifications ? 'on' : ''; ?> toggle-success mb-0 float-sm-end" id="push-notifications">
                                        <span></span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-top justify-content-between mt-3">
                                    <div class="mail-notification-settings">
                                        <p class="fs-14 mb-1 fw-medium"><?php esc_html_e('Email Notifications', 'maneli-car-inquiry'); ?></p>
                                        <p class="fs-12 mb-0 text-muted"><?php esc_html_e('Messages sent to user email address.', 'maneli-car-inquiry'); ?></p>
                                    </div>
                                    <div class="toggle <?php echo $email_notifications ? 'on' : ''; ?> toggle-success mb-0 float-sm-end" id="email-notifications">
                                        <span></span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-top justify-content-between mt-3">
                                    <div class="mail-notification-settings">
                                        <p class="fs-14 mb-1 fw-medium"><?php esc_html_e('In-App Notifications', 'maneli-car-inquiry'); ?></p>
                                        <p class="fs-12 mb-0 text-muted"><?php esc_html_e('Notifications that appear within the application interface.', 'maneli-car-inquiry'); ?></p>
                                    </div>
                                    <div class="toggle <?php echo $in_app_notifications ? 'on' : ''; ?> toggle-success mb-0 float-sm-end" id="in-app-notifications">
                                        <span></span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-top justify-content-between mt-3">
                                    <div class="mail-notification-settings">
                                        <p class="fs-14 mb-1 fw-medium"><?php esc_html_e('SMS Notifications', 'maneli-car-inquiry'); ?></p>
                                        <p class="fs-12 mb-0 text-muted"><?php esc_html_e('SMS messages sent to user mobile phone.', 'maneli-car-inquiry'); ?></p>
                                    </div>
                                    <div class="toggle <?php echo $sms_notifications ? 'on' : ''; ?> toggle-success mb-0 float-sm-end" id="sms-notifications">
                                        <span></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Security Tab -->
                    <div class="tab-pane overflow-hidden p-0 border-0" id="security-tab-pane" role="tabpanel" aria-labelledby="security-tab" tabindex="0">
                        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-1">
                            <div class="fw-semibold d-block fs-15"><?php esc_html_e('Security Settings:', 'maneli-car-inquiry'); ?></div>
                            <div class="btn btn-primary btn-sm" onclick="resetSecurityForm()"><i class="ri-loop-left-line lh-1 me-2"></i><?php esc_html_e('Reset Changes', 'maneli-car-inquiry'); ?></div>
                        </div>
                        <div class="d-sm-flex d-block align-items-top justify-content-between">
                            <div class="w-50">
                                <p class="fs-14 mb-1 fw-medium"><?php esc_html_e('Authentication', 'maneli-car-inquiry'); ?></p>
                                <p class="fs-12 mb-0 text-muted"><?php esc_html_e('Control how profile information is verified for security purposes.', 'maneli-car-inquiry'); ?></p>
                            </div>
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="email-verification" <?php checked($email_verification, true); ?> name="email_verification">
                                    <label class="form-check-label" for="email-verification">
                                        <?php esc_html_e('Email', 'maneli-car-inquiry'); ?>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="sms-verification" <?php checked($sms_verification, true); ?> name="sms_verification">
                                    <label class="form-check-label" for="sms-verification">
                                        <?php esc_html_e('SMS', 'maneli-car-inquiry'); ?>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="phone-verification" <?php checked($phone_verification, true); ?> name="phone_verification">
                                    <label class="form-check-label" for="phone-verification">
                                        <?php esc_html_e('Phone', 'maneli-car-inquiry'); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="d-sm-flex d-block align-items-top justify-content-between mt-3">
                            <div class="w-50">
                                <p class="fs-14 mb-1 fw-medium"><?php esc_html_e('Login Verification', 'maneli-car-inquiry'); ?></p>
                                <p class="fs-12 mb-0 text-muted"><?php esc_html_e('This helps protect account from unauthorized access, even if password is compromised.', 'maneli-car-inquiry'); ?></p>
                            </div>
                            <a href="javascript:void(0);" class="link-primary text-decoration-underline"><?php esc_html_e('Setup Verification', 'maneli-car-inquiry'); ?></a>
                        </div>
                        <div class="d-sm-flex d-block align-items-top justify-content-between mt-3">
                            <div class="w-50">
                                <p class="fs-14 mb-1 fw-medium"><?php esc_html_e('Password Verification', 'maneli-car-inquiry'); ?></p>
                                <p class="fs-12 mb-0 text-muted"><?php esc_html_e('This extra step helps ensure the person trying to change account details is the real owner.', 'maneli-car-inquiry'); ?></p>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="personal-details" name="require_personal_details">
                                <label class="form-check-label" for="personal-details">
                                    <?php esc_html_e('Require Personal Details', 'maneli-car-inquiry'); ?>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer border-top-0">
                    <div class="btn-list float-end">
                        <button type="submit" class="btn btn-primary btn-wave"><?php esc_html_e('Save Changes', 'maneli-car-inquiry'); ?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!--End::row-1 -->

    </div>
</div>
<!-- End::main-content -->

<script>
(function() {
    function initProfileSettings() {
        if (typeof jQuery === 'undefined') {
            setTimeout(initProfileSettings, 50);
            return;
        }
        
        jQuery(document).ready(function($) {
    var $form = $('#profile-settings-form');
    var originalData = {};

    // Save original form data
    $form.find('input, textarea, select').each(function() {
        originalData[this.name || this.id] = $(this).val();
    });

    // Profile image upload
    $('#profile-image-upload').on('change', function(e) {
        var file = e.target.files[0];
        
        if (!file) {
            return;
        }
        
        // Check file size (2 MB)
        if (file.size > 2 * 1024 * 1024) {
            Swal.fire({
                icon: 'error',
                title: '<?php esc_html_e('Error', 'maneli-car-inquiry'); ?>',
                text: '<?php esc_html_e('File size exceeds 2 MB limit.', 'maneli-car-inquiry'); ?>'
            });
            $(this).val('');
            return;
        }
        
        // Check file type
        var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (allowedTypes.indexOf(file.type) === -1) {
            Swal.fire({
                icon: 'error',
                title: '<?php esc_html_e('Error', 'maneli-car-inquiry'); ?>',
                text: '<?php esc_html_e('Invalid file type. Only JPEG, PNG and GIF are allowed.', 'maneli-car-inquiry'); ?>'
            });
            $(this).val('');
            return;
        }
        
        var formData = new FormData();
        formData.append('action', 'maneli_upload_profile_image');
        formData.append('security', '<?php echo wp_create_nonce('maneli-profile-image-nonce'); ?>');
        formData.append('profile_image', file);

        // Show loading
        var $btn = $(this).prev('label');
        var originalText = $btn.html();
        $btn.html('<i class="ri-loader-4-line me-1"></i><?php esc_html_e('Uploading...', 'maneli-car-inquiry'); ?>');
        $btn.prop('disabled', true);

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Upload response:', response);
                
                if (response.success) {
                    $('#profile-avatar-preview').attr('src', response.data.url);
                    Swal.fire({
                        icon: 'success',
                        title: '<?php esc_html_e('Success', 'maneli-car-inquiry'); ?>',
                        text: response.data.message
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: '<?php esc_html_e('Error', 'maneli-car-inquiry'); ?>',
                        text: response.data.message
                    });
                }
                $btn.html(originalText);
                $btn.prop('disabled', false);
                $('#profile-image-upload').val('');
            },
            error: function(xhr, status, error) {
                console.error('Upload error:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
                
                var errorMsg = '<?php esc_html_e('Upload failed. Please try again.', 'maneli-car-inquiry'); ?>';
                
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.data && response.data.message) {
                        errorMsg = response.data.message;
                    }
                } catch(e) {
                    // Ignore parsing errors
                }
                
                Swal.fire({
                    icon: 'error',
                    title: '<?php esc_html_e('Error', 'maneli-car-inquiry'); ?>',
                    text: errorMsg
                });
                $btn.html(originalText);
                $btn.prop('disabled', false);
                $('#profile-image-upload').val('');
            }
        });
    });

    // Delete profile image
    window.deleteProfileImage = function() {
        Swal.fire({
            title: '<?php esc_html_e('Are you sure?', 'maneli-car-inquiry'); ?>',
            text: '<?php esc_html_e('You want to delete your profile image?', 'maneli-car-inquiry'); ?>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '<?php esc_html_e('Yes, delete it!', 'maneli-car-inquiry'); ?>',
            cancelButtonText: '<?php esc_html_e('Cancel', 'maneli-car-inquiry'); ?>'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'maneli_delete_profile_image',
                        security: '<?php echo wp_create_nonce('maneli-profile-image-nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#profile-avatar-preview').attr('src', '<?php echo MANELI_PLUGIN_URL; ?>assets/images/faces/15.jpg');
                            Swal.fire('<?php esc_html_e('Deleted', 'maneli-car-inquiry'); ?>', response.data.message, 'success');
                        } else {
                            Swal.fire('<?php esc_html_e('Error', 'maneli-car-inquiry'); ?>', response.data.message, 'error');
                        }
                    }
                });
            }
        });
    };

    // Form submission
    $form.on('submit', function(e) {
        e.preventDefault();
        
        var formData = $form.serialize();
        formData += '&action=maneli_update_profile';
        formData += '&security=<?php echo wp_create_nonce('maneli-update-profile-nonce'); ?>';

        // Add notification toggles
        formData += '&push_notifications=' + ($('#push-notifications').hasClass('on') ? '1' : '0');
        formData += '&email_notifications=' + ($('#email-notifications').hasClass('on') ? '1' : '0');
        formData += '&in_app_notifications=' + ($('#in-app-notifications').hasClass('on') ? '1' : '0');
        formData += '&sms_notifications=' + ($('#sms-notifications').hasClass('on') ? '1' : '0');

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    Swal.fire('<?php esc_html_e('Success', 'maneli-car-inquiry'); ?>', response.data.message, 'success');
                } else {
                    Swal.fire('<?php esc_html_e('Error', 'maneli-car-inquiry'); ?>', response.data.message, 'error');
                }
            }
        });
    });

    // Initialize toggles
    $('.toggle').each(function() {
        $(this).on('click', function() {
            $(this).toggleClass('on');
        });
    });

    // Handle job type changes to show/hide fields
    $('#profile-job-type').on('change', function() {
        var jobValue = $(this).val();
        var $jobTitleWrapper = $('#profile-job-title-wrapper');
        var $residencyWrapper = $('#profile-residency-wrapper');
        
        if (jobValue === 'self') {
            $jobTitleWrapper.slideDown(200);
            $residencyWrapper.slideDown(200);
        } else {
            $jobTitleWrapper.slideUp(200);
            $residencyWrapper.slideUp(200);
        }
    });
    
    // Trigger on page load to show/hide fields based on current value
    $('#profile-job-type').trigger('change');

    // Handle customer document upload
    $(document).on('change', '.customer-doc-file-input', function() {
        var $input = $(this);
        var file = this.files[0];
        var userId = $input.data('user-id');
        var docName = $input.data('doc-name');
        
        if (!file) return;
        
        // Validate file type
        var allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        if (allowedTypes.indexOf(file.type) === -1) {
            Swal.fire({
                icon: 'error',
                title: '<?php esc_html_e('Invalid File Type', 'maneli-car-inquiry'); ?>',
                text: '<?php esc_html_e('Please upload only PDF, JPG, or PNG files.', 'maneli-car-inquiry'); ?>'
            });
            $input.val('');
            return;
        }
        
        // Show loading
        $input.prop('disabled', true);
        $input.closest('.document-item-customer').append('<div class="upload-progress text-center mt-2"><i class="la la-spinner la-spin me-2"></i><?php esc_html_e('Uploading...', 'maneli-car-inquiry'); ?></div>');
        
        // Create FormData
        var formData = new FormData();
        formData.append('action', 'maneli_upload_customer_document');
        formData.append('security', '<?php echo wp_create_nonce('maneli-profile-image-nonce'); ?>');
        formData.append('user_id', userId);
        formData.append('document_name', docName);
        formData.append('file', file);
        
        // Upload via AJAX
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $input.closest('.document-item-customer').find('.upload-progress').remove();
                $input.prop('disabled', false);
                
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '<?php esc_html_e('Success', 'maneli-car-inquiry'); ?>',
                        text: response.data.message
                    }).then(function() {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: '<?php esc_html_e('Error', 'maneli-car-inquiry'); ?>',
                        text: response.data.message
                    });
                    $input.val('');
                }
            },
            error: function() {
                $input.closest('.document-item-customer').find('.upload-progress').remove();
                $input.prop('disabled', false);
                Swal.fire({
                    icon: 'error',
                    title: '<?php esc_html_e('Error', 'maneli-car-inquiry'); ?>',
                    text: '<?php esc_html_e('Server error. Please try again.', 'maneli-car-inquiry'); ?>'
                });
                $input.val('');
            }
        });
    });

    // Reset functions
    window.resetAccountForm = function() {
        $('#profile-first-name').val('<?php echo esc_js($first_name); ?>');
        $('#profile-last-name').val('<?php echo esc_js($last_name); ?>');
        $('#profile-phn-no').val('<?php echo esc_js($mobile_number); ?>');
        $('#profile-national-code').val('<?php echo esc_js($national_code); ?>');
        $('#profile-father-name').val('<?php echo esc_js($father_name); ?>');
        $('#profile-birth-date').val('<?php echo esc_js($birth_date); ?>');
        $('#profile-phone').val('<?php echo esc_js($phone_number); ?>');
        $('#profile-job-title').val('<?php echo esc_js($job_title ?: $occupation); ?>');
        $('#profile-job-type').val('<?php echo esc_js($job_type); ?>');
        $('#profile-income').val('<?php echo esc_js($income_level); ?>');
        $('#profile-residency-status-job').val('<?php echo esc_js($residency_status); ?>');
        $('#profile-address').val('<?php echo esc_js($address); ?>');
        $('#profile-bank-name').val('<?php echo esc_js($bank_name); ?>');
        $('#profile-account-number').val('<?php echo esc_js($account_number); ?>');
        $('#profile-branch-code').val('<?php echo esc_js($branch_code); ?>');
        $('#profile-branch-name').val('<?php echo esc_js($branch_name); ?>');
    };

    window.resetNotificationForm = function() {
        $('#push-notifications').toggleClass('on', <?php echo $push_notifications ? 'true' : 'false'; ?>);
        $('#email-notifications').toggleClass('on', <?php echo $email_notifications ? 'true' : 'false'; ?>);
        $('#in-app-notifications').toggleClass('on', <?php echo $in_app_notifications ? 'true' : 'false'; ?>);
        $('#sms-notifications').toggleClass('on', <?php echo $sms_notifications ? 'true' : 'false'; ?>);
    };

    window.resetSecurityForm = function() {
        $('#email-verification').prop('checked', <?php echo $email_verification ? 'true' : 'false'; ?>);
        $('#sms-verification').prop('checked', <?php echo $sms_verification ? 'true' : 'false'; ?>);
        $('#phone-verification').prop('checked', <?php echo $phone_verification ? 'true' : 'false'; ?>);
    };
        });
    }
    
    // Wait for DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initProfileSettings);
    } else {
        initProfileSettings();
    }
})();
</script>
