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

<div class="maneli-inquiry-wrapper">
    <?php if (isset($_GET['inquiry_created']) && $_GET['inquiry_created'] == '1') : ?>
        <div class="status-box status-approved">
            <p><?php esc_html_e('New inquiry has been successfully created for the customer.', 'maneli-car-inquiry'); ?></p>
        </div>
    <?php endif; ?>

    <form id="expert-inquiry-form" class="maneli-inquiry-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="maneli_expert_create_inquiry">
        <?php wp_nonce_field('maneli_expert_create_nonce'); ?>

        <h3><?php esc_html_e('1. Select Car and Conditions', 'maneli-car-inquiry'); ?></h3>
        <div class="form-group">
            <label for="product_id_expert"><strong><?php esc_html_e('Search for a Car', 'maneli-car-inquiry'); ?></strong></label>
            <select id="product_id_expert" name="product_id" class="maneli-car-search-select" required>
                <option value=""></option>
            </select>
            <p class="description" style="margin-top: 8px; color: #666; font-size: 13px;">
                <?php esc_html_e('Start typing the car name to search (at least 2 characters)', 'maneli-car-inquiry'); ?>
            </p>
        </div>
        
        <?php if (current_user_can('manage_maneli_inquiries') && !empty($experts)): ?>
            <div class="form-group" style="border-top: 1px solid #eee; padding-top: 20px; margin-top: 20px;">
                <label for="assigned_expert_id"><strong><?php esc_html_e('Assign Responsible Expert', 'maneli-car-inquiry'); ?></strong></label>
                <select id="assigned_expert_id" name="assigned_expert_id" style="width: 100%;">
                    <option value="auto"><?php esc_html_e('-- Automatic Assignment (Round-robin) --', 'maneli-car-inquiry'); ?></option>
                    <?php foreach ($experts as $expert) : ?>
                        <option value="<?php echo esc_attr($expert->ID); ?>"><?php echo esc_html($expert->display_name); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php esc_html_e('If not selected, the system will automatically assign an expert.', 'maneli-car-inquiry'); ?></p>
            </div>
        <?php endif; ?>

        <div id="loan-calculator-wrapper"></div>

        <div id="expert-form-details" style="display: none; margin-top: 20px;">
            <h3 style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;"><?php esc_html_e('2. Identity Information', 'maneli-car-inquiry'); ?></h3>
             <div class="issuer-choice-wrapper" style="background: transparent; border: none; padding: 0; margin: 10px 0;">
                <div class="form-group-radio" style="display: flex; gap: 20px; margin-bottom: 20px;">
                    <label><input type="radio" name="issuer_type" value="self" checked> <?php esc_html_e('Buyer and cheque issuer are the same person.', 'maneli-car-inquiry'); ?></label>
                    <label><input type="radio" name="issuer_type" value="other"> <?php esc_html_e('The cheque issuer is another person.', 'maneli-car-inquiry'); ?></label>
                </div>
            </div>
        
            <div id="buyer-form-wrapper">
                <p class="form-section-title"><?php esc_html_e('Buyer Information Form', 'maneli-car-inquiry'); ?></p>
                <div class="form-grid">
                    <div class="form-row">
                        <div class="form-group"><label><?php esc_html_e('First Name:', 'maneli-car-inquiry'); ?></label><input type="text" name="first_name" required></div>
                        <div class="form-group"><label><?php esc_html_e('Last Name:', 'maneli-car-inquiry'); ?></label><input type="text" name="last_name" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label><?php esc_html_e('Father\'s Name:', 'maneli-car-inquiry'); ?></label><input type="text" name="father_name" required></div>
                        <div class="form-group"><label><?php esc_html_e('Date of Birth:', 'maneli-car-inquiry'); ?></label><input type="text" id="expert_buyer_birth_date" name="birth_date" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label><?php esc_html_e('National Code:', 'maneli-car-inquiry'); ?></label><input type="text" name="national_code" placeholder="<?php esc_attr_e('10-digit national ID', 'maneli-car-inquiry'); ?>" required pattern="\d{10}"></div>
                        <div class="form-group"><label><?php esc_html_e('Mobile Number (Username):', 'maneli-car-inquiry'); ?></label><input type="tel" name="mobile_number" placeholder="<?php esc_attr_e('e.g., 09123456789', 'maneli-car-inquiry'); ?>" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><?php esc_html_e('Job Type', 'maneli-car-inquiry'); ?>:</label>
                            <select name="job_type" id="buyer_job_type">
                                <option value=""><?php esc_html_e('-- Select --', 'maneli-car-inquiry'); ?></option>
                                <option value="self"><?php esc_html_e('آزاد', 'maneli-car-inquiry'); ?></option>
                                <option value="employee"><?php esc_html_e('کارمند', 'maneli-car-inquiry'); ?></option>
                            </select>
                        </div>
                        <div class="form-group buyer-job-title-wrapper" style="display:none;">
                            <label><?php esc_html_e('Job Title:', 'maneli-car-inquiry'); ?></label>
                            <input type="text" name="job_title" id="buyer_job_title">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group buyer-property-wrapper" style="display:none;">
                            <label><?php esc_html_e('Residency Status', 'maneli-car-inquiry'); ?>:</label>
                            <select name="residency_status" id="buyer_residency_status">
                                <option value=""><?php esc_html_e('-- Select --', 'maneli-car-inquiry'); ?></option>
                                <option value="owner"><?php esc_html_e('Owner', 'maneli-car-inquiry'); ?></option>
                                <option value="tenant"><?php esc_html_e('Tenant', 'maneli-car-inquiry'); ?></option>
                            </select>
                        </div>
                        <div class="form-group"><label><?php esc_html_e('Income Level (Toman):', 'maneli-car-inquiry'); ?></label><input type="number" name="income_level"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label><?php esc_html_e('Phone Number:', 'maneli-car-inquiry'); ?></label><input type="tel" name="phone_number"></div>
                        <div class="form-group"><label><?php esc_html_e('Address:', 'maneli-car-inquiry'); ?></label><textarea name="address"></textarea></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label><?php esc_html_e('Bank Name:', 'maneli-car-inquiry'); ?></label><input type="text" name="bank_name"></div>
                        <div class="form-group"><label><?php esc_html_e('Account Number:', 'maneli-car-inquiry'); ?></label><input type="text" name="account_number"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label><?php esc_html_e('Branch Code:', 'maneli-car-inquiry'); ?></label><input type="text" name="branch_code"></div>
                        <div class="form-group"><label><?php esc_html_e('Branch Name:', 'maneli-car-inquiry'); ?></label><input type="text" name="branch_name"></div>
                    </div>
                </div>
            </div>
            
            <div id="issuer-form-wrapper" style="display: none;">
                <p class="form-section-title"><?php esc_html_e('Cheque Issuer Information Form', 'maneli-car-inquiry'); ?></p>
                <div class="form-grid">
                    <div class="form-row">
                        <div class="form-group"><label><?php esc_html_e('Issuer First Name:', 'maneli-car-inquiry'); ?></label><input type="text" name="issuer_first_name"></div>
                        <div class="form-group"><label><?php esc_html_e('Issuer Last Name:', 'maneli-car-inquiry'); ?></label><input type="text" name="issuer_last_name"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label><?php esc_html_e('Issuer Father\'s Name:', 'maneli-car-inquiry'); ?></label><input type="text" name="issuer_father_name"></div>
                        <div class="form-group"><label><?php esc_html_e('Issuer Date of Birth:', 'maneli-car-inquiry'); ?></label><input type="text" id="expert_issuer_birth_date" name="issuer_birth_date"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label><?php esc_html_e('Issuer National Code:', 'maneli-car-inquiry'); ?></label><input type="text" name="issuer_national_code" placeholder="<?php esc_attr_e('10-digit national ID', 'maneli-car-inquiry'); ?>" pattern="\d{10}"></div>
                        <div class="form-group"><label><?php esc_html_e('Issuer Mobile Number:', 'maneli-car-inquiry'); ?></label><input type="tel" name="issuer_mobile_number" placeholder="<?php esc_attr_e('e.g., 09129876543', 'maneli-car-inquiry'); ?>"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><?php esc_html_e('Job Type', 'maneli-car-inquiry'); ?>:</label>
                            <select name="issuer_job_type" id="issuer_job_type">
                                <option value=""><?php esc_html_e('-- Select --', 'maneli-car-inquiry'); ?></option>
                                <option value="self"><?php esc_html_e('آزاد', 'maneli-car-inquiry'); ?></option>
                                <option value="employee"><?php esc_html_e('کارمند', 'maneli-car-inquiry'); ?></option>
                            </select>
                        </div>
                        <div class="form-group issuer-job-title-wrapper" style="display:none;">
                            <label><?php esc_html_e('Issuer Job Title:', 'maneli-car-inquiry'); ?></label>
                            <input type="text" name="issuer_job_title" id="issuer_job_title">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group issuer-property-wrapper" style="display:none;">
                            <label><?php esc_html_e('Issuer Residency Status', 'maneli-car-inquiry'); ?>:</label>
                            <select name="issuer_residency_status" id="issuer_residency_status">
                                <option value=""><?php esc_html_e('-- Select --', 'maneli-car-inquiry'); ?></option>
                                <option value="owner"><?php esc_html_e('Owner', 'maneli-car-inquiry'); ?></option>
                                <option value="tenant"><?php esc_html_e('Tenant', 'maneli-car-inquiry'); ?></option>
                            </select>
                        </div>
                        <div class="form-group"><label><?php esc_html_e('Issuer Income Level (Toman):', 'maneli-car-inquiry'); ?></label><input type="number" name="issuer_income_level"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label><?php esc_html_e('Issuer Phone Number:', 'maneli-car-inquiry'); ?></label><input type="tel" name="issuer_phone_number"></div>
                        <div class="form-group"><label><?php esc_html_e('Issuer Address:', 'maneli-car-inquiry'); ?></label><textarea name="issuer_address"></textarea></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label><?php esc_html_e('Issuer Bank Name:', 'maneli-car-inquiry'); ?></label><input type="text" name="issuer_bank_name"></div>
                        <div class="form-group"><label><?php esc_html_e('Issuer Account Number:', 'maneli-car-inquiry'); ?></label><input type="text" name="issuer_account_number"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label><?php esc_html_e('Issuer Branch Code:', 'maneli-car-inquiry'); ?></label><input type="text" name="issuer_branch_code"></div>
                        <div class="form-group"><label><?php esc_html_e('Issuer Branch Name:', 'maneli-car-inquiry'); ?></label><input type="text" name="issuer_branch_name"></div>
                    </div>
                </div>
            </div>
            
            <div class="form-group" style="margin-top: 30px;">
                <button type="submit" class="loan-action-btn"><?php esc_html_e('Submit Inquiry and Create User', 'maneli-car-inquiry'); ?></button>
            </div>
        </div>
    </form>
</div>