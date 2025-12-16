<?php
/**
 * Wizard Step 2: Identity Information Form
 * استایل ویزارد - اطلاعات هویتی
 * 
 * @package Autopuzzle_Car_Inquiry/Templates/Shortcodes/InquiryForm/Wizard
 * @version 2.0.0
 * 
 * @var string $car_name
 * @var string $car_model
 * @var string $car_image_html
 * @var string $down_payment
 * @var string $term_months
 * @var string $total_price
 * @var string $installment_amount
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$user_info = wp_get_current_user();
$loan_amount = (int)$total_price - (int)$down_payment;
?>

<h6 class="mb-3"><?php esc_html_e('Identity Information:', 'autopuzzle'); ?></h6>

<!-- Request Summary Card -->
<div class="card border mb-4">
    <div class="card-header bg-light">
        <h6 class="card-title mb-0">
            <i class="la la-file-alt text-primary me-2"></i>
            <?php esc_html_e('Your Request Summary', 'autopuzzle'); ?>
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="border rounded p-3 bg-light">
                            <div class="text-muted fs-12 mb-1">
                                <i class="la la-car me-1"></i>
                                <?php esc_html_e('Selected Car', 'autopuzzle'); ?>
                            </div>
                            <strong class="fs-16"><?php echo esc_html($car_name); ?></strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3 bg-success-transparent">
                            <div class="text-muted fs-12 mb-1">
                                <i class="la la-money-bill me-1"></i>
                                <?php esc_html_e('Down Payment', 'autopuzzle'); ?>
                            </div>
                            <strong class="fs-16 text-success"><?php echo function_exists('autopuzzle_number_format_persian') ? autopuzzle_number_format_persian((int)$down_payment) : number_format_i18n((int)$down_payment); ?> <?php esc_html_e('Toman', 'autopuzzle'); ?></strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3 bg-info-transparent">
                            <div class="text-muted fs-12 mb-1">
                                <i class="la la-calendar me-1"></i>
                                <?php esc_html_e('Installment Period', 'autopuzzle'); ?>
                            </div>
                            <strong class="fs-16 text-info"><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($term_months) : esc_html($term_months); ?> <?php esc_html_e('Months', 'autopuzzle'); ?></strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3 bg-warning-transparent">
                            <div class="text-muted fs-12 mb-1">
                                <i class="la la-calculator me-1"></i>
                                <?php esc_html_e('Monthly Installment', 'autopuzzle'); ?>
                            </div>
                            <strong class="fs-16 text-warning"><?php echo function_exists('autopuzzle_number_format_persian') ? autopuzzle_number_format_persian((int)$installment_amount) : number_format_i18n((int)$installment_amount); ?> <?php esc_html_e('Toman', 'autopuzzle'); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <?php echo wp_kses_post($car_image_html); ?>
            </div>
        </div>
    </div>
</div>

<?php 
$options = get_option('autopuzzle_inquiry_all_options', []); 
$price_msg = $options['msg_price_disclaimer'] ?? esc_html__('Due to severe market fluctuations, car prices are approximate and may change until final confirmation.', 'autopuzzle'); 
?>
<div class="alert alert-warning mb-4" role="alert">
    <i class="la la-info-circle me-2"></i>
    <?php echo esc_html($price_msg); ?>
</div>

<form id="identity-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
    <input type="hidden" name="action" value="autopuzzle_submit_identity">
    <?php wp_nonce_field('autopuzzle_submit_identity_nonce'); ?>
    
    <div class="mb-4">
        <h5 class="mb-3"><?php esc_html_e('Check Issuer Information', 'autopuzzle'); ?></h5>
        <div class="alert alert-light border">
            <div class="form-check form-check-lg mb-2">
                <input class="form-check-input" type="radio" name="issuer_type" id="issuer-self" value="self" checked>
                <label class="form-check-label fw-medium" for="issuer-self">
                    <i class="la la-user-check text-success me-1"></i>
                    <?php esc_html_e('The buyer and check issuer are the same person.', 'autopuzzle'); ?>
                </label>
            </div>
            <div class="form-check form-check-lg">
                <input class="form-check-input" type="radio" name="issuer_type" id="issuer-other" value="other">
                <label class="form-check-label fw-medium" for="issuer-other">
                    <i class="la la-user-friends text-warning me-1"></i>
                    <?php esc_html_e('The check issuer is another person.', 'autopuzzle'); ?>
                </label>
            </div>
        </div>
    </div>

    <div id="buyer-form-wrapper">
        <h5 class="mb-3"><?php esc_html_e('Applicant Information', 'autopuzzle'); ?></h5>
        
        <div class="row gy-3">
            <div class="col-xl-6">
                <label for="first_name" class="form-label"><?php esc_html_e('First Name', 'autopuzzle'); ?> <span class="text-danger">*</span></label>
                <input type="text" id="first_name" name="first_name" class="form-control" value="<?php echo esc_attr($user_info->first_name); ?>" placeholder="<?php esc_attr_e('Enter your first name', 'autopuzzle'); ?>" required>
            </div>
            <div class="col-xl-6">
                <label for="last_name" class="form-label"><?php esc_html_e('Last Name', 'autopuzzle'); ?> <span class="text-danger">*</span></label>
                <input type="text" id="last_name" name="last_name" class="form-control" value="<?php echo esc_attr($user_info->last_name); ?>" placeholder="<?php esc_attr_e('Enter your last name', 'autopuzzle'); ?>" required>
            </div>
            <div class="col-xl-6">
                <label for="father_name" class="form-label"><?php esc_html_e('Father\'s Name', 'autopuzzle'); ?> <span class="text-danger">*</span></label>
                <input type="text" id="father_name" name="father_name" class="form-control" value="<?php echo esc_attr(get_user_meta($user_id, 'father_name', true)); ?>" placeholder="<?php esc_attr_e('Enter your father\'s name', 'autopuzzle'); ?>" required>
            </div>
            <div class="col-xl-6">
                <label for="buyer_birth_date" class="form-label"><?php esc_html_e('Birth Date', 'autopuzzle'); ?> <span class="text-danger">*</span></label>
                <div class="input-group">
                    <div class="input-group-text text-muted">
                        <i class="ri-calendar-line"></i>
                    </div>
                    <input type="text" id="buyer_birth_date" name="birth_date" class="form-control autopuzzle-datepicker" value="<?php echo esc_attr(get_user_meta($user_id, 'birth_date', true)); ?>" placeholder="<?php esc_attr_e('Select birth date', 'autopuzzle'); ?>" required>
                </div>
            </div>
            <div class="col-xl-6">
                <label for="national_code" class="form-label"><?php esc_html_e('National Code', 'autopuzzle'); ?> <span class="text-danger">*</span></label>
                <input type="text" id="national_code" name="national_code" class="form-control" value="<?php echo esc_attr(get_user_meta($user_id, 'national_code', true)); ?>" placeholder="<?php esc_attr_e('10 digits', 'autopuzzle'); ?>" required pattern="\d{10}" maxlength="10">
            </div>
            <div class="col-xl-6">
                <label for="mobile_number" class="form-label"><?php esc_html_e('Mobile Number', 'autopuzzle'); ?> <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text" id="mobile-addon">+98</span>
                    <input type="tel" id="mobile_number" name="mobile_number" class="form-control" value="<?php echo esc_attr(get_user_meta($user_id, 'mobile_number', true)); ?>" placeholder="<?php esc_attr_e('Enter your mobile number', 'autopuzzle'); ?>" required>
                </div>
            </div>
            <div class="col-xl-6">
                <label for="buyer_job_type" class="form-label"><?php esc_html_e('Job Type', 'autopuzzle'); ?>:</label>
                <select id="buyer_job_type" name="job_type" class="form-control" data-trigger="">
                    <?php $saved_job_type = get_user_meta($user_id, 'job_type', true); ?>
                    <option value=""><?php esc_html_e('-- Please Select --', 'autopuzzle'); ?></option>
                    <option value="self" <?php selected($saved_job_type, 'self'); ?>><?php esc_html_e('Self-Employed', 'autopuzzle'); ?></option>
                    <option value="employee" <?php selected($saved_job_type, 'employee'); ?>><?php esc_html_e('Employee', 'autopuzzle'); ?></option>
                </select>
            </div>
            <div class="col-xl-6 buyer-job-title-wrapper autopuzzle-initially-hidden">
                <label for="buyer_job_title" class="form-label"><?php esc_html_e('Job Title', 'autopuzzle'); ?>:</label>
                <input type="text" id="buyer_job_title" name="job_title" class="form-control" value="<?php echo esc_attr(get_user_meta($user_id, 'occupation', true)); ?>" placeholder="<?php esc_attr_e('Example: Engineer', 'autopuzzle'); ?>">
            </div>
            <div class="col-xl-6 buyer-property-wrapper autopuzzle-initially-hidden">
                <label for="buyer_residency_status" class="form-label"><?php esc_html_e('Residency Status', 'autopuzzle'); ?>:</label>
                <select id="buyer_residency_status" name="residency_status" class="form-control" data-trigger="">
                    <option value="" <?php selected(get_user_meta($user_id, 'residency_status', true), ''); ?>><?php esc_html_e('-- Please Select --', 'autopuzzle'); ?></option>
                    <option value="owner" <?php selected(get_user_meta($user_id, 'residency_status', true), 'owner'); ?>><?php esc_html_e('Owner', 'autopuzzle'); ?></option>
                    <option value="tenant" <?php selected(get_user_meta($user_id, 'residency_status', true), 'tenant'); ?>><?php esc_html_e('Tenant', 'autopuzzle'); ?></option>
                </select>
            </div>
            <div class="col-xl-6">
                <label for="income_level" class="form-label"><?php esc_html_e('Income Level (Toman)', 'autopuzzle'); ?></label>
                <input type="number" id="income_level" name="income_level" class="form-control" value="<?php echo esc_attr(get_user_meta($user_id, 'income_level', true)); ?>" placeholder="0">
            </div>
            <div class="col-xl-6">
                <label for="phone_number" class="form-label"><?php esc_html_e('Landline Phone', 'autopuzzle'); ?></label>
                <input type="tel" id="phone_number" name="phone_number" class="form-control" value="<?php echo esc_attr(get_user_meta($user_id, 'phone_number', true)); ?>" placeholder="<?php esc_attr_e('02112345678', 'autopuzzle'); ?>">
            </div>
            <div class="col-xl-6">
                <label for="address" class="form-label"><?php esc_html_e('Address', 'autopuzzle'); ?></label>
                <textarea id="address" name="address" class="form-control" rows="2" placeholder="<?php esc_attr_e('Full address', 'autopuzzle'); ?>"><?php echo esc_textarea(get_user_meta($user_id, 'address', true)); ?></textarea>
            </div>
            <div class="col-xl-6">
                <label for="bank_name" class="form-label"><?php esc_html_e('Bank Name', 'autopuzzle'); ?></label>
                <input type="text" id="bank_name" name="bank_name" class="form-control" value="<?php echo esc_attr(get_user_meta($user_id, 'bank_name', true)); ?>" placeholder="<?php esc_attr_e('Example: Melli', 'autopuzzle'); ?>">
            </div>
            <div class="col-xl-6">
                <label for="account_number" class="form-label"><?php esc_html_e('Account Number', 'autopuzzle'); ?></label>
                <input type="text" id="account_number" name="account_number" class="form-control" value="<?php echo esc_attr(get_user_meta($user_id, 'account_number', true)); ?>" placeholder="1234567890">
            </div>
            <div class="col-xl-6">
                <label for="branch_code" class="form-label"><?php esc_html_e('Branch Code', 'autopuzzle'); ?></label>
                <input type="text" id="branch_code" name="branch_code" class="form-control" value="<?php echo esc_attr(get_user_meta($user_id, 'branch_code', true)); ?>" placeholder="1234">
            </div>
            <div class="col-xl-6">
                <label for="branch_name" class="form-label"><?php esc_html_e('Branch Name', 'autopuzzle'); ?></label>
                <input type="text" id="branch_name" name="branch_name" class="form-control" value="<?php echo esc_attr(get_user_meta($user_id, 'branch_name', true)); ?>" placeholder="<?php esc_attr_e('Example: Central', 'autopuzzle'); ?>">
            </div>
        </div>
    </div>

    <div id="issuer-form-wrapper" class="autopuzzle-initially-hidden">
        <h5 class="mb-3 mt-4"><?php esc_html_e('Check Issuer Information', 'autopuzzle'); ?></h5>
        
        <div class="row gy-3">
            <div class="col-xl-6">
                <label for="issuer_first_name" class="form-label"><?php esc_html_e('Issuer First Name', 'autopuzzle'); ?></label>
                <input type="text" id="issuer_first_name" name="issuer_first_name" class="form-control" placeholder="<?php esc_attr_e('First Name', 'autopuzzle'); ?>">
            </div>
            <div class="col-xl-6">
                <label for="issuer_last_name" class="form-label"><?php esc_html_e('Issuer Last Name', 'autopuzzle'); ?></label>
                <input type="text" id="issuer_last_name" name="issuer_last_name" class="form-control" placeholder="<?php esc_attr_e('Last Name', 'autopuzzle'); ?>">
            </div>
            <div class="col-xl-6">
                <label for="issuer_father_name" class="form-label"><?php esc_html_e('Issuer Father\'s Name', 'autopuzzle'); ?></label>
                <input type="text" id="issuer_father_name" name="issuer_father_name" class="form-control" placeholder="<?php esc_attr_e('Father\'s Name', 'autopuzzle'); ?>">
            </div>
            <div class="col-xl-6">
                <label for="issuer_birth_date" class="form-label"><?php esc_html_e('Issuer Birth Date', 'autopuzzle'); ?></label>
                <div class="input-group">
                    <div class="input-group-text text-muted">
                        <i class="ri-calendar-line"></i>
                    </div>
                    <input type="text" id="issuer_birth_date" name="issuer_birth_date" class="form-control autopuzzle-datepicker" placeholder="<?php esc_attr_e('Select birth date', 'autopuzzle'); ?>">
                </div>
            </div>
            <div class="col-xl-6">
                <label for="issuer_national_code" class="form-label"><?php esc_html_e('Issuer National Code', 'autopuzzle'); ?></label>
                <input type="text" id="issuer_national_code" name="issuer_national_code" class="form-control" placeholder="<?php esc_attr_e('10 digits', 'autopuzzle'); ?>" pattern="\d{10}" maxlength="10">
            </div>
            <div class="col-xl-6">
                <label for="issuer_mobile_number" class="form-label"><?php esc_html_e('Issuer Mobile Number', 'autopuzzle'); ?></label>
                <div class="input-group">
                    <span class="input-group-text">+98</span>
                    <input type="tel" id="issuer_mobile_number" name="issuer_mobile_number" class="form-control" placeholder="<?php esc_attr_e('09123456789', 'autopuzzle'); ?>">
                </div>
            </div>
            <div class="col-xl-6">
                <label for="issuer_job_type" class="form-label"><?php esc_html_e('Issuer Job Type', 'autopuzzle'); ?></label>
                <select id="issuer_job_type" name="issuer_job_type" class="form-control" data-trigger="">
                    <option value=""><?php esc_html_e('-- Please Select --', 'autopuzzle'); ?></option>
                    <option value="self"><?php esc_html_e('Self-Employed', 'autopuzzle'); ?></option>
                    <option value="employee"><?php esc_html_e('Employee', 'autopuzzle'); ?></option>
                </select>
            </div>
            <div class="col-xl-6 issuer-job-title-wrapper autopuzzle-initially-hidden">
                <label for="issuer_job_title" class="form-label"><?php esc_html_e('Issuer Job Title', 'autopuzzle'); ?></label>
                <input type="text" id="issuer_job_title" name="issuer_job_title" class="form-control" placeholder="<?php esc_attr_e('Example: Engineer', 'autopuzzle'); ?>">
            </div>
            <div class="col-xl-6 issuer-property-wrapper autopuzzle-initially-hidden">
                <label for="issuer_residency_status" class="form-label"><?php esc_html_e('Issuer Residency Status', 'autopuzzle'); ?></label>
                <select id="issuer_residency_status" name="issuer_residency_status" class="form-control" data-trigger="">
                    <option value=""><?php esc_html_e('-- Please Select --', 'autopuzzle'); ?></option>
                    <option value="owner"><?php esc_html_e('Owner', 'autopuzzle'); ?></option>
                    <option value="tenant"><?php esc_html_e('Tenant', 'autopuzzle'); ?></option>
                </select>
            </div>
            <div class="col-xl-6">
                <label for="issuer_income_level" class="form-label"><?php esc_html_e('Issuer Income Level (Toman)', 'autopuzzle'); ?></label>
                <input type="number" id="issuer_income_level" name="issuer_income_level" class="form-control" placeholder="0">
            </div>
            <div class="col-xl-6">
                <label for="issuer_phone_number" class="form-label"><?php esc_html_e('Issuer Landline Phone', 'autopuzzle'); ?></label>
                <input type="tel" id="issuer_phone_number" name="issuer_phone_number" class="form-control" placeholder="<?php esc_attr_e('02112345678', 'autopuzzle'); ?>">
            </div>
            <div class="col-xl-6">
                <label for="issuer_address" class="form-label"><?php esc_html_e('Issuer Address', 'autopuzzle'); ?></label>
                <textarea id="issuer_address" name="issuer_address" class="form-control" rows="2" placeholder="<?php esc_attr_e('Full address', 'autopuzzle'); ?>"></textarea>
            </div>
            <div class="col-xl-6">
                <label for="issuer_bank_name" class="form-label"><?php esc_html_e('Issuer Bank Name', 'autopuzzle'); ?></label>
                <input type="text" id="issuer_bank_name" name="issuer_bank_name" class="form-control" placeholder="<?php esc_attr_e('Example: Melli', 'autopuzzle'); ?>">
            </div>
            <div class="col-xl-6">
                <label for="issuer_account_number" class="form-label"><?php esc_html_e('Issuer Account Number', 'autopuzzle'); ?></label>
                <input type="text" id="issuer_account_number" name="issuer_account_number" class="form-control" placeholder="1234567890">
            </div>
            <div class="col-xl-6">
                <label for="issuer_branch_code" class="form-label"><?php esc_html_e('Issuer Branch Code', 'autopuzzle'); ?></label>
                <input type="text" id="issuer_branch_code" name="issuer_branch_code" class="form-control" placeholder="1234">
            </div>
            <div class="col-xl-6">
                <label for="issuer_branch_name" class="form-label"><?php esc_html_e('Issuer Branch Name', 'autopuzzle'); ?></label>
                <input type="text" id="issuer_branch_name" name="issuer_branch_name" class="form-control" placeholder="<?php esc_attr_e('Example: Central', 'autopuzzle'); ?>">
            </div>
        </div>
    </div>
    
    <div class="mt-4 pt-3 border-top">
        <button type="submit" id="submit-identity-btn" class="btn btn-primary btn-wave w-100">
            <i class="la la-arrow-right me-1"></i>
            <?php esc_html_e('Continue to Next Step', 'autopuzzle'); ?>
        </button>
    </div>
</form>

<script>
(function() {
    function waitForJQuery() {
        if (typeof jQuery !== "undefined") {
            jQuery(document).ready(function($) {
                // Handle issuer type radio buttons
                $('input[name="issuer_type"]').on('change', function() {
                    const issuerFormWrapper = $('#issuer-form-wrapper');
                    const buyerFormWrapper = $('#buyer-form-wrapper');
                    const selectedValue = $(this).val();
                    
                    if (selectedValue === 'other') {
                        issuerFormWrapper.slideDown(300);
                        buyerFormWrapper.slideDown(300);
                    } else {
                        issuerFormWrapper.slideUp(300);
                        buyerFormWrapper.slideDown(300);
                    }
                });

                // Handle buyer job type changes
                $('#buyer_job_type').on('change', function() {
                    const jobValue = $(this).val();
                    const $jobTitleWrapper = $('.buyer-job-title-wrapper');
                    const $propertyWrapper = $('.buyer-property-wrapper');
                    
                    if (jobValue === 'self') {
                        $jobTitleWrapper.slideDown(200);
                        $propertyWrapper.slideDown(200);
                    } else if (jobValue === 'employee') {
                        $jobTitleWrapper.slideUp(200);
                        $propertyWrapper.slideUp(200);
                    } else {
                        $jobTitleWrapper.slideUp(200);
                        $propertyWrapper.slideUp(200);
                    }
                });

                // Handle issuer job type changes
                $('#issuer_job_type').on('change', function() {
                    const jobValue = $(this).val();
                    const $jobTitleWrapper = $('.issuer-job-title-wrapper');
                    const $propertyWrapper = $('.issuer-property-wrapper');
                    
                    if (jobValue === 'self') {
                        $jobTitleWrapper.slideDown(200);
                        $propertyWrapper.slideDown(200);
                    } else if (jobValue === 'employee') {
                        $jobTitleWrapper.slideUp(200);
                        $propertyWrapper.slideUp(200);
                    } else {
                        $jobTitleWrapper.slideUp(200);
                        $propertyWrapper.slideUp(200);
                    }
                });
            });
        } else {
            setTimeout(waitForJQuery, 50);
        }
    }
    waitForJQuery();
})();
</script>

