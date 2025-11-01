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
                                <label for="profile-user-name" class="form-label"><?php esc_html_e('Username:', 'maneli-car-inquiry'); ?></label>
                                <input type="text" class="form-control" id="profile-user-name" name="display_name" value="<?php echo esc_attr($current_user->display_name); ?>" placeholder="<?php esc_attr_e('Enter name', 'maneli-car-inquiry'); ?>">
                            </div>
                            <div class="col-xl-6">
                                <label for="profile-email" class="form-label"><?php esc_html_e('Email:', 'maneli-car-inquiry'); ?></label>
                                <input type="email" class="form-control" id="profile-email" name="user_email" value="<?php echo esc_attr($current_user->user_email); ?>" placeholder="<?php esc_attr_e('Enter email', 'maneli-car-inquiry'); ?>">
                            </div>
                            <div class="col-xl-6">
                                <label for="profile-designation" class="form-label"><?php esc_html_e('Designation:', 'maneli-car-inquiry'); ?></label>
                                <input type="text" class="form-control" id="profile-designation" name="designation" value="<?php echo esc_attr($designation); ?>" placeholder="<?php esc_attr_e('Enter designation', 'maneli-car-inquiry'); ?>">
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
                                <label for="profile-occupation" class="form-label"><?php esc_html_e('Occupation/Job Title:', 'maneli-car-inquiry'); ?></label>
                                <input type="text" class="form-control" id="profile-occupation" name="occupation" value="<?php echo esc_attr($job_title ?: $occupation); ?>" placeholder="<?php esc_attr_e('Enter occupation or job title', 'maneli-car-inquiry'); ?>">
                            </div>
                            <div class="col-xl-6">
                                <label for="profile-job-type" class="form-label"><?php esc_html_e('Job Type:', 'maneli-car-inquiry'); ?></label>
                                <select class="form-select" id="profile-job-type" name="job_type">
                                    <option value="">-- <?php esc_html_e('Select', 'maneli-car-inquiry'); ?> --</option>
                                    <option value="government" <?php selected($job_type, 'government'); ?>><?php esc_html_e('Government', 'maneli-car-inquiry'); ?></option>
                                    <option value="private" <?php selected($job_type, 'private'); ?>><?php esc_html_e('Private', 'maneli-car-inquiry'); ?></option>
                                    <option value="self_employed" <?php selected($job_type, 'self_employed'); ?>><?php esc_html_e('Self Employed', 'maneli-car-inquiry'); ?></option>
                                </select>
                            </div>
                            <div class="col-xl-6">
                                <label for="profile-income" class="form-label"><?php esc_html_e('Income Level (Toman):', 'maneli-car-inquiry'); ?></label>
                                <input type="number" class="form-control" id="profile-income" name="income_level" value="<?php echo esc_attr($income_level); ?>" placeholder="<?php esc_attr_e('Example: 50000000', 'maneli-car-inquiry'); ?>">
                            </div>
                            <div class="col-xl-6">
                                <label for="profile-workplace-status" class="form-label"><?php esc_html_e('Workplace Status:', 'maneli-car-inquiry'); ?></label>
                                <select class="form-select" id="profile-workplace-status" name="workplace_status">
                                    <option value="">-- <?php esc_html_e('Select', 'maneli-car-inquiry'); ?> --</option>
                                    <option value="owner" <?php selected($workplace_status, 'owner'); ?>><?php esc_html_e('Owner', 'maneli-car-inquiry'); ?></option>
                                    <option value="employee" <?php selected($workplace_status, 'employee'); ?>><?php esc_html_e('Employee', 'maneli-car-inquiry'); ?></option>
                                    <option value="tenant" <?php selected($workplace_status, 'tenant'); ?>><?php esc_html_e('Tenant', 'maneli-car-inquiry'); ?></option>
                                </select>
                            </div>
                            
                            <!-- Address Information Section -->
                            <div class="col-xl-12">
                                <hr class="my-3">
                                <h5 class="fw-semibold mb-3"><?php esc_html_e('Contact Information', 'maneli-car-inquiry'); ?></h5>
                            </div>
                            
                            <div class="col-xl-6">
                                <label for="profile-residency-status" class="form-label"><?php esc_html_e('Residency Status:', 'maneli-car-inquiry'); ?></label>
                                <select class="form-select" id="profile-residency-status" name="residency_status">
                                    <option value="">-- <?php esc_html_e('Select', 'maneli-car-inquiry'); ?> --</option>
                                    <option value="owner" <?php selected($residency_status, 'owner'); ?>><?php esc_html_e('Owner', 'maneli-car-inquiry'); ?></option>
                                    <option value="tenant" <?php selected($residency_status, 'tenant'); ?>><?php esc_html_e('Tenant', 'maneli-car-inquiry'); ?></option>
                                </select>
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

    // Reset functions
    window.resetAccountForm = function() {
        $('#profile-user-name').val('<?php echo esc_js($current_user->display_name); ?>');
        $('#profile-email').val('<?php echo esc_js($current_user->user_email); ?>');
        $('#profile-designation').val('<?php echo esc_js($designation); ?>');
        $('#profile-phn-no').val('<?php echo esc_js($mobile_number); ?>');
        $('#profile-national-code').val('<?php echo esc_js($national_code); ?>');
        $('#profile-father-name').val('<?php echo esc_js($father_name); ?>');
        $('#profile-birth-date').val('<?php echo esc_js($birth_date); ?>');
        $('#profile-phone').val('<?php echo esc_js($phone_number); ?>');
        $('#profile-occupation').val('<?php echo esc_js($job_title ?: $occupation); ?>');
        $('#profile-job-type').val('<?php echo esc_js($job_type); ?>');
        $('#profile-income').val('<?php echo esc_js($income_level); ?>');
        $('#profile-workplace-status').val('<?php echo esc_js($workplace_status); ?>');
        $('#profile-residency-status').val('<?php echo esc_js($residency_status); ?>');
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
