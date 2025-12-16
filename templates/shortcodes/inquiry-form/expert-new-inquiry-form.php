<?php
/**
 * Template for the Expert/Admin New Inquiry Form.
 *
 * This form allows privileged users to create a new inquiry on behalf of a customer.
 * It includes an AJAX car search, a loan calculator, and fields for customer/issuer details.
 *
 * @package Autopuzzle_Car_Inquiry/Templates/Shortcodes/InquiryForm
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
                <?php esc_html_e('New inquiry has been successfully created for the customer.', 'autopuzzle'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="la la-user-plus me-2"></i>
                    <?php esc_html_e('Create New Inquiry (Expert Mode)', 'autopuzzle'); ?>
                </div>
            </div>
            <div class="card-body">
                <form id="expert-inquiry-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="autopuzzle_expert_create_inquiry">
                    <?php wp_nonce_field('autopuzzle_expert_create_nonce'); ?>

                    <h5 class="mb-3">
                        <span class="badge bg-primary-transparent me-2">1</span>
                        <?php esc_html_e('Select Car and Conditions', 'autopuzzle'); ?>
                    </h5>
                    
                    <div class="mb-3">
                        <label for="product_id_expert" class="form-label fw-semibold">
                            <?php esc_html_e('Search for a Car', 'autopuzzle'); ?>
                            <span class="text-danger">*</span>
                        </label>
                        <select id="product_id_expert" name="product_id" class="form-select autopuzzle-car-search-select" required>
                            <option value=""></option>
                        </select>
                        <div class="form-text">
                            <i class="la la-info-circle me-1"></i>
                            <?php esc_html_e('Start typing the car name to search (at least 2 characters)', 'autopuzzle'); ?>
                        </div>
                    </div>
                    
                    <?php if (current_user_can('manage_autopuzzle_inquiries') && !empty($experts)): ?>
                        <div class="mb-3 pt-3 border-top">
                            <label for="assigned_expert_id" class="form-label fw-semibold">
                                <?php esc_html_e('Assign Responsible Expert', 'autopuzzle'); ?>
                            </label>
                            <select id="assigned_expert_id" name="assigned_expert_id" class="form-select">
                                <option value="auto"><?php esc_html_e('-- Automatic Assignment (Round-robin) --', 'autopuzzle'); ?></option>
                                <?php foreach ($experts as $expert) : ?>
                                    <option value="<?php echo esc_attr($expert->ID); ?>"><?php echo esc_html($expert->display_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text"><?php esc_html_e('If not selected, the system will automatically assign an expert.', 'autopuzzle'); ?></div>
                        </div>
                    <?php endif; ?>

                    <div id="loan-calculator-wrapper"></div>

                    <div id="expert-form-details" class="autopuzzle-initially-hidden">
                        <h5 class="mb-3 mt-4 pt-4 border-top">
                            <span class="badge bg-primary-transparent me-2">2</span>
                            <?php esc_html_e('Identity Information', 'autopuzzle'); ?>
                        </h5>
                        
                        <div class="mb-4">
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="issuer_type" id="issuer-self" value="self" checked>
                                    <label class="form-check-label" for="issuer-self">
                                        <?php esc_html_e('Buyer and cheque issuer are the same person.', 'autopuzzle'); ?>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="issuer_type" id="issuer-other" value="other">
                                    <label class="form-check-label" for="issuer-other">
                                        <?php esc_html_e('The cheque issuer is another person.', 'autopuzzle'); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                    
                        <div id="buyer-form-wrapper">
                            <h6 class="mb-3"><?php esc_html_e('Buyer Information Form', 'autopuzzle'); ?></h6>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('First Name:', 'autopuzzle'); ?> <span class="text-danger">*</span></label>
                                    <input type="text" name="first_name" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Last Name:', 'autopuzzle'); ?> <span class="text-danger">*</span></label>
                                    <input type="text" name="last_name" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Father\'s Name:', 'autopuzzle'); ?> <span class="text-danger">*</span></label>
                                    <input type="text" name="father_name" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Date of Birth:', 'autopuzzle'); ?> <span class="text-danger">*</span></label>
                                    <input type="text" id="expert_buyer_birth_date" name="birth_date" class="form-control autopuzzle-datepicker" required>
                                </div>
                            </div>
                            
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('National Code:', 'autopuzzle'); ?> <span class="text-danger">*</span></label>
                                    <input type="text" name="national_code" class="form-control" placeholder="<?php esc_attr_e('10-digit national ID', 'autopuzzle'); ?>" required pattern="\d{10}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Mobile Number (Username):', 'autopuzzle'); ?> <span class="text-danger">*</span></label>
                                    <input type="tel" name="mobile_number" class="form-control" placeholder="<?php esc_attr_e('e.g., 09123456789', 'autopuzzle'); ?>" required>
                                </div>
                            </div>
                            
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Job Type', 'autopuzzle'); ?>:</label>
                                    <select name="job_type" id="buyer_job_type" class="form-select">
                                        <option value=""><?php esc_html_e('-- Select --', 'autopuzzle'); ?></option>
                                        <option value="self"><?php esc_html_e('Self-Employed', 'autopuzzle'); ?></option>
                                        <option value="employee"><?php esc_html_e('Employee', 'autopuzzle'); ?></option>
                                    </select>
                                </div>
                                <div class="col-md-6 buyer-job-title-wrapper autopuzzle-initially-hidden">
                                    <label class="form-label"><?php esc_html_e('Job Title:', 'autopuzzle'); ?></label>
                                    <input type="text" name="job_title" id="buyer_job_title" class="form-control">
                                </div>
                            </div>
                            
                            <div class="row g-3 mt-2">
                                <div class="col-md-6 buyer-property-wrapper autopuzzle-initially-hidden">
                                    <label class="form-label"><?php esc_html_e('Residency Status', 'autopuzzle'); ?>:</label>
                                    <select name="residency_status" id="buyer_residency_status" class="form-select">
                                        <option value=""><?php esc_html_e('-- Select --', 'autopuzzle'); ?></option>
                                        <option value="owner"><?php esc_html_e('Owner', 'autopuzzle'); ?></option>
                                        <option value="tenant"><?php esc_html_e('Tenant', 'autopuzzle'); ?></option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Income Level (Toman):', 'autopuzzle'); ?></label>
                                    <input type="number" name="income_level" class="form-control">
                                </div>
                            </div>
                            
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Phone Number:', 'autopuzzle'); ?></label>
                                    <input type="tel" name="phone_number" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Address:', 'autopuzzle'); ?></label>
                                    <textarea name="address" class="form-control" rows="1"></textarea>
                                </div>
                            </div>
                            
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Bank Name:', 'autopuzzle'); ?></label>
                                    <input type="text" name="bank_name" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Account Number:', 'autopuzzle'); ?></label>
                                    <input type="text" name="account_number" class="form-control">
                                </div>
                            </div>
                            
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Branch Code:', 'autopuzzle'); ?></label>
                                    <input type="text" name="branch_code" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><?php esc_html_e('Branch Name:', 'autopuzzle'); ?></label>
                                    <input type="text" name="branch_name" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div id="issuer-form-wrapper" class="autopuzzle-initially-hidden">
                            <h6 class="mb-3 mt-4"><?php esc_html_e('Cheque Issuer Information Form', 'autopuzzle'); ?></h6>
                            
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
                                    <input type="text" id="expert_issuer_birth_date" name="issuer_birth_date" class="form-control autopuzzle-datepicker">
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
                                        <option value="self"><?php esc_html_e('Self-Employed', 'autopuzzle'); ?></option>
                                        <option value="employee"><?php esc_html_e('Employee', 'autopuzzle'); ?></option>
                                    </select>
                                </div>
                                <div class="col-md-6 issuer-job-title-wrapper autopuzzle-initially-hidden">
                                    <label class="form-label"><?php esc_html_e('Issuer Job Title:', 'autopuzzle'); ?></label>
                                    <input type="text" name="issuer_job_title" id="issuer_job_title" class="form-control">
                                </div>
                            </div>
                            
                            <div class="row g-3 mt-2">
                                <div class="col-md-6 issuer-property-wrapper autopuzzle-initially-hidden">
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
                            <button type="submit" class="btn btn-primary btn-wave w-100">
                                <i class="la la-plus-circle me-1"></i>
                                <?php esc_html_e('Submit Inquiry and Create User', 'autopuzzle'); ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
