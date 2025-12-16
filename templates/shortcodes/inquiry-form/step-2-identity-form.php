<?php
/**
 * Template for Step 2 of the inquiry form (Identity Information).
 *
 * This template displays the request summary and the main form for collecting
 * buyer and optional cheque issuer information.
 *
 * @package Autopuzzle_Car_Inquiry/Templates/Shortcodes/InquiryForm
 * @author  Gemini
 * @version 1.0.2 (Security fix for $car_image_html escaping)
 *
 * @var int      $user_id             The current user's ID.
 * @var WP_User  $user_info           The current user's object.
 * @var string   $car_name            The name of the selected car.
 * @var string   $car_model           The model of the selected car.
 * @var string   $car_image_html      The HTML for the car's thumbnail.
 * @var string   $down_payment        The submitted down payment amount.
 * @var string   $term_months         The submitted installment term.
 * @var string   $total_price         The total price of the car.
 * @var string   $installment_amount  The calculated monthly installment.
 */

if (!defined('ABSPATH')) {
    exit;
}

$loan_amount = (int)$total_price - (int)$down_payment;
?>

<div class="row">
    <div class="col-xl-8">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="la la-list-alt me-2"></i>
                    <?php esc_html_e('Your Request Summary', 'autopuzzle'); ?>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-borderless">
                        <tbody>
                            <tr>
                                <td class="fw-semibold"><?php esc_html_e('Selected Car:', 'autopuzzle'); ?></td>
                                <td><?php echo esc_html($car_name); ?></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold"><?php esc_html_e('Car Model:', 'autopuzzle'); ?></td>
                                <td><?php echo esc_html($car_model); ?></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold"><?php esc_html_e('Total Price:', 'autopuzzle'); ?></td>
                                <td><span class="badge bg-primary-transparent"><?php echo function_exists('autopuzzle_number_format_persian') ? autopuzzle_number_format_persian((int)$total_price) : esc_html(number_format_i18n((int)$total_price)); ?> <?php esc_html_e('Toman', 'autopuzzle'); ?></span></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold"><?php esc_html_e('Down Payment:', 'autopuzzle'); ?></td>
                                <td><span class="badge bg-success-transparent"><?php echo function_exists('autopuzzle_number_format_persian') ? autopuzzle_number_format_persian((int)$down_payment) : esc_html(number_format_i18n((int)$down_payment)); ?> <?php esc_html_e('Toman', 'autopuzzle'); ?></span></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold"><?php esc_html_e('Loan Amount:', 'autopuzzle'); ?></td>
                                <td><span class="badge bg-warning-transparent"><?php echo function_exists('autopuzzle_number_format_persian') ? autopuzzle_number_format_persian($loan_amount) : esc_html(number_format_i18n($loan_amount)); ?> <?php esc_html_e('Toman', 'autopuzzle'); ?></span></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold"><?php esc_html_e('Installment Term:', 'autopuzzle'); ?></td>
                                <td><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($term_months) : esc_html($term_months); ?> <?php esc_html_e('Months', 'autopuzzle'); ?></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold"><?php esc_html_e('Approx. Installment:', 'autopuzzle'); ?></td>
                                <td><span class="badge bg-info-transparent"><?php echo function_exists('autopuzzle_number_format_persian') ? autopuzzle_number_format_persian((int)$installment_amount) : esc_html(number_format_i18n((int)$installment_amount)); ?> <?php esc_html_e('Toman', 'autopuzzle'); ?></span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <?php $options = get_option('autopuzzle_inquiry_all_options', []); $price_msg = $options['msg_price_disclaimer'] ?? esc_html__('Due to severe market fluctuations, car prices are approximate and may change until final approval.', 'autopuzzle'); ?>
                <div class="alert alert-warning" role="alert">
                    <i class="la la-info-circle me-2"></i>
                    <?php echo esc_html($price_msg); ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-4">
        <div class="card custom-card">
            <div class="card-body text-center">
                <?php echo wp_kses_post($car_image_html); ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="la la-user-alt me-2"></i>
                    <?php esc_html_e('Identity Information', 'autopuzzle'); ?>
                </div>
            </div>
            <div class="card-body">
                <form id="identity-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                    <input type="hidden" name="action" value="autopuzzle_submit_identity">
                    <?php wp_nonce_field('autopuzzle_submit_identity_nonce'); ?>
                    
                    <div class="mb-4">
                        <h5 class="mb-3"><?php esc_html_e('Cheque Issuer Information', 'autopuzzle'); ?></h5>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="issuer_type" id="issuer-self" value="self" checked>
                            <label class="form-check-label" for="issuer-self">
                                <?php esc_html_e('I am both the buyer and the cheque issuer.', 'autopuzzle'); ?>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="issuer_type" id="issuer-other" value="other">
                            <label class="form-check-label" for="issuer-other">
                                <?php esc_html_e('The cheque issuer is another person.', 'autopuzzle'); ?>
                            </label>
                        </div>
                    </div>

                    <div id="buyer-form-wrapper" style="display: none;">
                        <h5 class="mb-3"><?php esc_html_e('Applicant Information', 'autopuzzle'); ?></h5>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php esc_html_e('First Name:', 'autopuzzle'); ?> <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" class="form-control" value="<?php echo esc_attr($user_info->first_name); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php esc_html_e('Last Name:', 'autopuzzle'); ?> <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" class="form-control" value="<?php echo esc_attr($user_info->last_name); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label"><?php esc_html_e('Father\'s Name:', 'autopuzzle'); ?> <span class="text-danger">*</span></label>
                                <input type="text" name="father_name" class="form-control" value="<?php echo esc_attr(get_user_meta($user_id, 'father_name', true)); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php esc_html_e('Date of Birth:', 'autopuzzle'); ?> <span class="text-danger">*</span></label>
                                <input type="text" id="buyer_birth_date" name="birth_date" class="form-control autopuzzle-datepicker" value="<?php echo esc_attr(get_user_meta($user_id, 'birth_date', true)); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label"><?php esc_html_e('National Code:', 'autopuzzle'); ?> <span class="text-danger">*</span></label>
                                <input type="text" name="national_code" class="form-control" value="<?php echo esc_attr(get_user_meta($user_id, 'national_code', true)); ?>" placeholder="<?php esc_attr_e('10-digit national ID', 'autopuzzle'); ?>" required pattern="\d{10}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php esc_html_e('Mobile Number:', 'autopuzzle'); ?> <span class="text-danger">*</span></label>
                                <input type="tel" name="mobile_number" class="form-control" value="<?php echo esc_attr(get_user_meta($user_id, 'mobile_number', true)); ?>" placeholder="<?php esc_attr_e('e.g., 09123456789', 'autopuzzle'); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label"><?php esc_html_e('Job Type', 'autopuzzle'); ?>:</label>
                                <select name="job_type" id="buyer_job_type" class="form-select">
                                    <?php $saved_job_type = get_user_meta($user_id, 'job_type', true); ?>
                                    <option value=""><?php esc_html_e('-- Select --', 'autopuzzle'); ?></option>
                                    <option value="self" <?php selected($saved_job_type, 'self'); ?>><?php esc_html_e('Self-employed', 'autopuzzle'); ?></option>
                                    <option value="employee" <?php selected($saved_job_type, 'employee'); ?>><?php esc_html_e('Employee', 'autopuzzle'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-6 buyer-job-title-wrapper" style="display:none;">
                                <label class="form-label"><?php esc_html_e('Job Title:', 'autopuzzle'); ?></label>
                                <input type="text" name="job_title" id="buyer_job_title" class="form-control" value="<?php echo esc_attr(get_user_meta($user_id, 'occupation', true)); ?>">
                            </div>
                        </div>
                        
                        <div class="row g-3 mt-2">
                            <div class="col-md-6 buyer-property-wrapper" style="display:none;">
                                <label class="form-label"><?php esc_html_e('Residency Status', 'autopuzzle'); ?>:</label>
                                <select name="residency_status" id="buyer_residency_status" class="form-select">
                                    <option value="" <?php selected(get_user_meta($user_id, 'residency_status', true), ''); ?>><?php esc_html_e('-- Select --', 'autopuzzle'); ?></option>
                                    <option value="owner" <?php selected(get_user_meta($user_id, 'residency_status', true), 'owner'); ?>><?php esc_html_e('Owner', 'autopuzzle'); ?></option>
                                    <option value="tenant" <?php selected(get_user_meta($user_id, 'residency_status', true), 'tenant'); ?>><?php esc_html_e('Tenant', 'autopuzzle'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php esc_html_e('Income Level (Toman):', 'autopuzzle'); ?></label>
                                <input type="number" name="income_level" class="form-control" value="<?php echo esc_attr(get_user_meta($user_id, 'income_level', true)); ?>">
                            </div>
                        </div>
                        
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label"><?php esc_html_e('Phone Number:', 'autopuzzle'); ?></label>
                                <input type="tel" name="phone_number" class="form-control" value="<?php echo esc_attr(get_user_meta($user_id, 'phone_number', true)); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php esc_html_e('Address:', 'autopuzzle'); ?></label>
                                <textarea name="address" class="form-control" rows="1"><?php echo esc_textarea(get_user_meta($user_id, 'address', true)); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label"><?php esc_html_e('Bank Name:', 'autopuzzle'); ?></label>
                                <input type="text" name="bank_name" class="form-control" value="<?php echo esc_attr(get_user_meta($user_id, 'bank_name', true)); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php esc_html_e('Account Number:', 'autopuzzle'); ?></label>
                                <input type="text" name="account_number" class="form-control" value="<?php echo esc_attr(get_user_meta($user_id, 'account_number', true)); ?>">
                            </div>
                        </div>
                        
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label"><?php esc_html_e('Branch Code:', 'autopuzzle'); ?></label>
                                <input type="text" name="branch_code" class="form-control" value="<?php echo esc_attr(get_user_meta($user_id, 'branch_code', true)); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php esc_html_e('Branch Name:', 'autopuzzle'); ?></label>
                                <input type="text" name="branch_name" class="form-control" value="<?php echo esc_attr(get_user_meta($user_id, 'branch_name', true)); ?>">
                            </div>
                        </div>
                    </div>

                    <div id="issuer-form-wrapper" style="display: none;">
                        <h5 class="mb-3 mt-4"><?php esc_html_e('Cheque Issuer Information Form', 'autopuzzle'); ?></h5>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php esc_html_e('Issuer First Name:', 'autopuzzle'); ?></label>
                                <input type="text" name="issuer_first_name" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php esc_html_e('Issuer Last Name:', 'autopuzzle'); ?></label>
                                <input type="text" name="issuer_last_name" class="form-control">
                            </div>
                        </div>
                        
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label"><?php esc_html_e('Issuer Father\'s Name:', 'autopuzzle'); ?></label>
                                <input type="text" name="issuer_father_name" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php esc_html_e('Issuer Date of Birth:', 'autopuzzle'); ?></label>
                                <input type="text" id="issuer_birth_date" name="issuer_birth_date" class="form-control autopuzzle-datepicker">
                            </div>
                        </div>
                        
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label"><?php esc_html_e('Issuer National Code:', 'autopuzzle'); ?></label>
                                <input type="text" name="issuer_national_code" class="form-control" placeholder="<?php esc_attr_e('10-digit national ID', 'autopuzzle'); ?>" pattern="\d{10}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php esc_html_e('Issuer Mobile Number:', 'autopuzzle'); ?></label>
                                <input type="tel" name="issuer_mobile_number" class="form-control" placeholder="<?php esc_attr_e('e.g., 09129876543', 'autopuzzle'); ?>">
                            </div>
                        </div>
                        
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label"><?php esc_html_e('Job Type', 'autopuzzle'); ?>:</label>
                                <select name="issuer_job_type" id="issuer_job_type" class="form-select">
                                    <option value=""><?php esc_html_e('-- Select --', 'autopuzzle'); ?></option>
                                    <option value="self"><?php esc_html_e('Self-employed', 'autopuzzle'); ?></option>
                                    <option value="employee"><?php esc_html_e('Employee', 'autopuzzle'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-6 issuer-job-title-wrapper" style="display:none;">
                                <label class="form-label"><?php esc_html_e('Issuer Job Title:', 'autopuzzle'); ?></label>
                                <input type="text" name="issuer_job_title" id="issuer_job_title" class="form-control">
                            </div>
                        </div>
                        
                        <div class="row g-3 mt-2">
                            <div class="col-md-6 issuer-property-wrapper" style="display:none;">
                                <label class="form-label"><?php esc_html_e('Issuer Residency Status', 'autopuzzle'); ?>:</label>
                                <select name="issuer_residency_status" id="issuer_residency_status" class="form-select">
                                    <option value=""><?php esc_html_e('-- Select --', 'autopuzzle'); ?></option>
                                    <option value="owner"><?php esc_html_e('Owner', 'autopuzzle'); ?></option>
                                    <option value="tenant"><?php esc_html_e('Tenant', 'autopuzzle'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php esc_html_e('Issuer Income Level (Toman):', 'autopuzzle'); ?></label>
                                <input type="number" name="issuer_income_level" class="form-control">
                            </div>
                        </div>
                        
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label"><?php esc_html_e('Issuer Phone Number:', 'autopuzzle'); ?></label>
                                <input type="tel" name="issuer_phone_number" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php esc_html_e('Issuer Address:', 'autopuzzle'); ?></label>
                                <textarea name="issuer_address" class="form-control" rows="1"></textarea>
                            </div>
                        </div>
                        
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label"><?php esc_html_e('Issuer Bank Name:', 'autopuzzle'); ?></label>
                                <input type="text" name="issuer_bank_name" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php esc_html_e('Issuer Account Number:', 'autopuzzle'); ?></label>
                                <input type="text" name="issuer_account_number" class="form-control">
                            </div>
                        </div>
                        
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label"><?php esc_html_e('Issuer Branch Code:', 'autopuzzle'); ?></label>
                                <input type="text" name="issuer_branch_code" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php esc_html_e('Issuer Branch Name:', 'autopuzzle'); ?></label>
                                <input type="text" name="issuer_branch_name" class="form-control">
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
            </div>
        </div>
    </div>
</div>
