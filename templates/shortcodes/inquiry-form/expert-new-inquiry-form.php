<?php
/**
 * Template for the Expert/Admin New Inquiry Form.
 *
 * This form allows privileged users to create a new inquiry on behalf of a customer.
 * It includes an AJAX car search, a loan calculator, and fields for customer/issuer details.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/InquiryForm
 * @author  Gemini
 * @version 1.0.0
 *
 * @var WP_User[]|null $experts Array of expert user objects (only available for admins).
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="row">
    <div class="col-xl-12">
        <?php if (isset($_GET['inquiry_created']) && $_GET['inquiry_created'] == '1') : ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="la la-check-circle me-2"></i>
                <?php esc_html_e('New inquiry has been successfully created for the customer.', 'maneli-car-inquiry'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="la la-user-plus me-2"></i>
                    <?php esc_html_e('Create New Inquiry (Expert Mode)', 'maneli-car-inquiry'); ?>
                </div>
            </div>
            <div class="card-body">
                <form id="expert-inquiry-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="maneli_expert_create_inquiry">
                    <?php wp_nonce_field('maneli_expert_create_nonce'); ?>

                    <h5 class="mb-3">
                        <span class="badge bg-primary-transparent me-2">1</span>
                        <?php esc_html_e('Select Car and Conditions', 'maneli-car-inquiry'); ?>
                    </h5>
                    
                    <div class="mb-3">
                        <label for="product_id_expert" class="form-label fw-semibold">
                            <?php esc_html_e('Search for a Car', 'maneli-car-inquiry'); ?>
                            <span class="text-danger">*</span>
                        </label>
                        <select id="product_id_expert" name="product_id" class="form-select maneli-car-search-select" required>
                            <option value=""></option>
                        </select>
                        <div class="form-text">
                            <i class="la la-info-circle me-1"></i>
                            <?php esc_html_e('Start typing the car name to search (at least 2 characters)', 'maneli-car-inquiry'); ?>
                        </div>
                    </div>
                    
                    <?php if (current_user_can('manage_maneli_inquiries') && !empty($experts)): ?>
                        <div class="mb-3 pt-3 border-top">
                            <label for="assigned_expert_id" class="form-label fw-semibold">
                                <?php esc_html_e('Assign Responsible Expert', 'maneli-car-inquiry'); ?>
                            </label>
                            <select id="assigned_expert_id" name="assigned_expert_id" class="form-select">
                                <option value="auto"><?php esc_html_e('-- Automatic Assignment (Round-robin) --', 'maneli-car-inquiry'); ?></option>
                                <?php foreach ($experts as $expert) : ?>
                                    <option value="<?php echo esc_attr($expert->ID); ?>"><?php echo esc_html($expert->display_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text"><?php esc_html_e('If not selected, the system will automatically assign an expert.', 'maneli-car-inquiry'); ?></div>
                        </div>
                    <?php endif; ?>

                    <div id="loan-calculator-wrapper"></div>

                    <div id="expert-form-details" class="maneli-initially-hidden">
                        <h5 class="mb-3 mt-4 pt-4 border-top">
                            <span class="badge bg-primary-transparent me-2">2</span>
                            <?php esc_html_e('Identity Information', 'maneli-car-inquiry'); ?>
                        </h5>
                        
                        <div class="mb-4">
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="issuer_type" id="issuer-self" value="self" checked>
                                    <label class="form-check-label" for="issuer-self">
                                        <?php esc_html_e('Buyer and cheque issuer are the same person.', 'maneli-car-inquiry'); ?>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="issuer_type" id="issuer-other" value="other">
                                    <label class="form-check-label" for="issuer-other">
                                        <?php esc_html_e('The cheque issuer is another person.', 'maneli-car-inquiry'); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                    
                        <div id="buyer-form-wrapper">
                            <h6 class="mb-3"><?php esc_html_e('Buyer Information Form', 'maneli-car-inquiry'); ?></h6>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('First Name:', 'maneli-car-inquiry'); ?> <span class="text-danger">*</span></label>
                                    <input type="text" name="first_name" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Last Name:', 'maneli-car-inquiry'); ?> <span class="text-danger">*</span></label>
                                    <input type="text" name="last_name" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Father\'s Name:', 'maneli-car-inquiry'); ?> <span class="text-danger">*</span></label>
                                    <input type="text" name="father_name" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Date of Birth:', 'maneli-car-inquiry'); ?> <span class="text-danger">*</span></label>
                                    <input type="text" id="expert_buyer_birth_date" name="birth_date" class="form-control maneli-datepicker" required>
                                </div>
                            </div>
                            
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('National Code:', 'maneli-car-inquiry'); ?> <span class="text-danger">*</span></label>
                                    <input type="text" name="national_code" class="form-control" placeholder="<?php esc_attr_e('10-digit national ID', 'maneli-car-inquiry'); ?>" required pattern="\d{10}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Mobile Number (Username):', 'maneli-car-inquiry'); ?> <span class="text-danger">*</span></label>
                                    <input type="tel" name="mobile_number" class="form-control" placeholder="<?php esc_attr_e('e.g., 09123456789', 'maneli-car-inquiry'); ?>" required>
                                </div>
                            </div>
                            
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Job Type', 'maneli-car-inquiry'); ?>:</label>
                                    <select name="job_type" id="buyer_job_type" class="form-select">
                                        <option value=""><?php esc_html_e('-- Select --', 'maneli-car-inquiry'); ?></option>
                                        <option value="self"><?php esc_html_e('Self-Employed', 'maneli-car-inquiry'); ?></option>
                                        <option value="employee"><?php esc_html_e('Employee', 'maneli-car-inquiry'); ?></option>
                                    </select>
                                </div>
                                <div class="col-md-6 buyer-job-title-wrapper maneli-initially-hidden">
                                    <label class="form-label"><?php esc_html_e('Job Title:', 'maneli-car-inquiry'); ?></label>
                                    <input type="text" name="job_title" id="buyer_job_title" class="form-control">
                                </div>
                            </div>
                            
                            <div class="row g-3 mt-2">
                                <div class="col-md-6 buyer-property-wrapper maneli-initially-hidden">
                                    <label class="form-label"><?php esc_html_e('Residency Status', 'maneli-car-inquiry'); ?>:</label>
                                    <select name="residency_status" id="buyer_residency_status" class="form-select">
                                        <option value=""><?php esc_html_e('-- Select --', 'maneli-car-inquiry'); ?></option>
                                        <option value="owner"><?php esc_html_e('Owner', 'maneli-car-inquiry'); ?></option>
                                        <option value="tenant"><?php esc_html_e('Tenant', 'maneli-car-inquiry'); ?></option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Income Level (Toman):', 'maneli-car-inquiry'); ?></label>
                                    <input type="number" name="income_level" class="form-control">
                                </div>
                            </div>
                            
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Phone Number:', 'maneli-car-inquiry'); ?></label>
                                    <input type="tel" name="phone_number" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Address:', 'maneli-car-inquiry'); ?></label>
                                    <textarea name="address" class="form-control" rows="1"></textarea>
                                </div>
                            </div>
                            
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Bank Name:', 'maneli-car-inquiry'); ?></label>
                                    <input type="text" name="bank_name" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Account Number:', 'maneli-car-inquiry'); ?></label>
                                    <input type="text" name="account_number" class="form-control">
                                </div>
                            </div>
                            
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Branch Code:', 'maneli-car-inquiry'); ?></label>
                                    <input type="text" name="branch_code" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Branch Name:', 'maneli-car-inquiry'); ?></label>
                                    <input type="text" name="branch_name" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div id="issuer-form-wrapper" class="maneli-initially-hidden">
                            <h6 class="mb-3 mt-4"><?php esc_html_e('Cheque Issuer Information Form', 'maneli-car-inquiry'); ?></h6>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Issuer First Name:', 'maneli-car-inquiry'); ?></label>
                                    <input type="text" name="issuer_first_name" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Issuer Last Name:', 'maneli-car-inquiry'); ?></label>
                                    <input type="text" name="issuer_last_name" class="form-control">
                                </div>
                            </div>
                            
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Issuer Father\'s Name:', 'maneli-car-inquiry'); ?></label>
                                    <input type="text" name="issuer_father_name" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Issuer Date of Birth:', 'maneli-car-inquiry'); ?></label>
                                    <input type="text" id="expert_issuer_birth_date" name="issuer_birth_date" class="form-control maneli-datepicker">
                                </div>
                            </div>
                            
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Issuer National Code:', 'maneli-car-inquiry'); ?></label>
                                    <input type="text" name="issuer_national_code" class="form-control" placeholder="<?php esc_attr_e('10-digit national ID', 'maneli-car-inquiry'); ?>" pattern="\d{10}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Issuer Mobile Number:', 'maneli-car-inquiry'); ?></label>
                                    <input type="tel" name="issuer_mobile_number" class="form-control" placeholder="<?php esc_attr_e('e.g., 09129876543', 'maneli-car-inquiry'); ?>">
                                </div>
                            </div>
                            
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Job Type', 'maneli-car-inquiry'); ?>:</label>
                                    <select name="issuer_job_type" id="issuer_job_type" class="form-select">
                                        <option value=""><?php esc_html_e('-- Select --', 'maneli-car-inquiry'); ?></option>
                                        <option value="self"><?php esc_html_e('Self-Employed', 'maneli-car-inquiry'); ?></option>
                                        <option value="employee"><?php esc_html_e('Employee', 'maneli-car-inquiry'); ?></option>
                                    </select>
                                </div>
                                <div class="col-md-6 issuer-job-title-wrapper maneli-initially-hidden">
                                    <label class="form-label"><?php esc_html_e('Issuer Job Title:', 'maneli-car-inquiry'); ?></label>
                                    <input type="text" name="issuer_job_title" id="issuer_job_title" class="form-control">
                                </div>
                            </div>
                            
                            <div class="row g-3 mt-2">
                                <div class="col-md-6 issuer-property-wrapper maneli-initially-hidden">
                                    <label class="form-label"><?php esc_html_e('Issuer Residency Status', 'maneli-car-inquiry'); ?>:</label>
                                    <select name="issuer_residency_status" id="issuer_residency_status" class="form-select">
                                        <option value=""><?php esc_html_e('-- Select --', 'maneli-car-inquiry'); ?></option>
                                        <option value="owner"><?php esc_html_e('Owner', 'maneli-car-inquiry'); ?></option>
                                        <option value="tenant"><?php esc_html_e('Tenant', 'maneli-car-inquiry'); ?></option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Issuer Income Level (Toman):', 'maneli-car-inquiry'); ?></label>
                                    <input type="number" name="issuer_income_level" class="form-control">
                                </div>
                            </div>
                            
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Issuer Phone Number:', 'maneli-car-inquiry'); ?></label>
                                    <input type="tel" name="issuer_phone_number" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Issuer Address:', 'maneli-car-inquiry'); ?></label>
                                    <textarea name="issuer_address" class="form-control" rows="1"></textarea>
                                </div>
                            </div>
                            
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Issuer Bank Name:', 'maneli-car-inquiry'); ?></label>
                                    <input type="text" name="issuer_bank_name" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Issuer Account Number:', 'maneli-car-inquiry'); ?></label>
                                    <input type="text" name="issuer_account_number" class="form-control">
                                </div>
                            </div>
                            
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Issuer Branch Code:', 'maneli-car-inquiry'); ?></label>
                                    <input type="text" name="issuer_branch_code" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Issuer Branch Name:', 'maneli-car-inquiry'); ?></label>
                                    <input type="text" name="issuer_branch_name" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary btn-wave w-100">
                                <i class="la la-plus-circle me-1"></i>
                                <?php esc_html_e('Submit Inquiry and Create User', 'maneli-car-inquiry'); ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
