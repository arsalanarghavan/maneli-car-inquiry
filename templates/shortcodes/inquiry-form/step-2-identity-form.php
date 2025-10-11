<?php
/**
 * Template for Step 2 of the inquiry form (Identity Information).
 *
 * This template displays the request summary and the main form for collecting
 * buyer and optional cheque issuer information.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/InquiryForm
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

<div class="inquiry-summary-flex-container">
    <div class="inquiry-summary-box">
        <h4><?php esc_html_e('Your Request Summary', 'maneli-car-inquiry'); ?></h4>
        <table class="summary-table">
            <tr><td><strong><?php esc_html_e('Selected Car:', 'maneli-car-inquiry'); ?></strong></td><td><?php echo esc_html($car_name); ?></td></tr>
            <tr><td><strong><?php esc_html_e('Car Model:', 'maneli-car-inquiry'); ?></strong></td><td><?php echo esc_html($car_model); ?></td></tr>
            <tr><td><strong><?php esc_html_e('Total Price:', 'maneli-car-inquiry'); ?></strong></td><td><?php echo esc_html(number_format_i18n((int)$total_price)); ?> <span><?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></span></td></tr>
            <tr><td><strong><?php esc_html_e('Down Payment:', 'maneli-car-inquiry'); ?></strong></td><td><?php echo esc_html(number_format_i18n((int)$down_payment)); ?> <span><?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></span></td></tr>
            <tr><td><strong><?php esc_html_e('Loan Amount:', 'maneli-car-inquiry'); ?></strong></td><td><?php echo esc_html(number_format_i18n($loan_amount)); ?> <span><?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></span></td></tr>
            <tr><td><strong><?php esc_html_e('Installment Term:', 'maneli-car-inquiry'); ?></strong></td><td><?php echo esc_html($term_months); ?> <span><?php esc_html_e('Months', 'maneli-car-inquiry'); ?></span></td></tr>
            <tr><td><strong><?php esc_html_e('Approx. Installment:', 'maneli-car-inquiry'); ?></strong></td><td><?php echo esc_html(number_format_i18n((int)$installment_amount)); ?> <span><?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></span></td></tr>
        </table>
    </div>
    <div class="inquiry-car-image">
        <?php echo wp_kses_post($car_image_html); // FIX: Ensuring image HTML is safely escaped ?>
    </div>
</div>

<form id="identity-form" class="maneli-inquiry-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
    <input type="hidden" name="action" value="maneli_submit_identity">
    <?php wp_nonce_field('maneli_submit_identity_nonce'); ?>
    
    <div class="issuer-choice-wrapper">
        <h4><?php esc_html_e('Cheque Issuer Information', 'maneli-car-inquiry'); ?></h4>
        <div class="form-group-radio"><label><input type="radio" name="issuer_type" value="self" checked> <?php esc_html_e('I am both the buyer and the cheque issuer.', 'maneli-car-inquiry'); ?></label></div>
        <div class="form-group-radio"><label><input type="radio" name="issuer_type" value="other"> <?php esc_html_e('The cheque issuer is another person.', 'maneli-car-inquiry'); ?></label></div>
    </div>

    <div id="buyer-form-wrapper" style="display: none;">
        <p class="form-section-title"><?php esc_html_e('Applicant Information', 'maneli-car-inquiry'); ?></p>
        <div class="form-grid">
             <div class="form-row">
                <div class="form-group"><label><?php esc_html_e('First Name:', 'maneli-car-inquiry'); ?></label><input type="text" name="first_name" value="<?php echo esc_attr($user_info->first_name); ?>" required></div>
                <div class="form-group"><label><?php esc_html_e('Last Name:', 'maneli-car-inquiry'); ?></label><input type="text" name="last_name" value="<?php echo esc_attr($user_info->last_name); ?>" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label><?php esc_html_e('Father\'s Name:', 'maneli-car-inquiry'); ?></label><input type="text" name="father_name" value="<?php echo esc_attr(get_user_meta($user_id, 'father_name', true)); ?>" required></div>
                <div class="form-group"><label><?php esc_html_e('Date of Birth:', 'maneli-car-inquiry'); ?></label><input type="text" id="buyer_birth_date" name="birth_date" value="<?php echo esc_attr(get_user_meta($user_id, 'birth_date', true)); ?>" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label><?php esc_html_e('National Code:', 'maneli-car-inquiry'); ?></label><input type="text" name="national_code" value="<?php echo esc_attr(get_user_meta($user_id, 'national_code', true)); ?>" placeholder="<?php esc_attr_e('10-digit national ID', 'maneli-car-inquiry'); ?>" required pattern="\d{10}"></div>
                <div class="form-group"><label><?php esc_html_e('Mobile Number:', 'maneli-car-inquiry'); ?></label><input type="tel" name="mobile_number" value="<?php echo esc_attr(get_user_meta($user_id, 'mobile_number', true)); ?>" placeholder="<?php esc_attr_e('e.g., 09123456789', 'maneli-car-inquiry'); ?>" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label><?php esc_html_e('Occupation:', 'maneli-car-inquiry'); ?></label><input type="text" name="occupation" value="<?php echo esc_attr(get_user_meta($user_id, 'occupation', true)); ?>"></div>
                <div class="form-group"><label><?php esc_html_e('Income Level (Toman):', 'maneli-car-inquiry'); ?></label><input type="number" name="income_level" value="<?php echo esc_attr(get_user_meta($user_id, 'income_level', true)); ?>"></div>
            </div>
             <div class="form-row">
                <div class="form-group"><label><?php esc_html_e('Phone Number:', 'maneli-car-inquiry'); ?></label><input type="tel" name="phone_number" value="<?php echo esc_attr(get_user_meta($user_id, 'phone_number', true)); ?>"></div>
                <div class="form-group">
                     <label><?php esc_html_e('Residency Status', 'maneli-car-inquiry'); ?>:</label>
                     <select name="residency_status">
                         <option value="" <?php selected(get_user_meta($user_id, 'residency_status', true), ''); ?>><?php esc_html_e('-- Select --', 'maneli-car-inquiry'); ?></option>
                         <option value="owner" <?php selected(get_user_meta($user_id, 'residency_status', true), 'owner'); ?>><?php esc_html_e('Owner', 'maneli-car-inquiry'); ?></option>
                         <option value="tenant" <?php selected(get_user_meta($user_id, 'residency_status', true), 'tenant'); ?>><?php esc_html_e('Tenant', 'maneli-car-inquiry'); ?></option>
                     </select>
                 </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><?php esc_html_e('Workplace Status', 'maneli-car-inquiry'); ?>:</label>
                    <select name="workplace_status">
                        <option value="" <?php selected(get_user_meta($user_id, 'workplace_status', true), ''); ?>><?php esc_html_e('-- Select --', 'maneli-car-inquiry'); ?></option>
                        <option value="permanent" <?php selected(get_user_meta($user_id, 'workplace_status', true), 'permanent'); ?>><?php esc_html_e('Permanent', 'maneli-car-inquiry'); ?></option>
                        <option value="contract" <?php selected(get_user_meta($user_id, 'workplace_status', true), 'contract'); ?>><?php esc_html_e('Contract', 'maneli-car-inquiry'); ?></option>
                        <option value="freelance" <?php selected(get_user_meta($user_id, 'workplace_status', true), 'freelance'); ?>><?php esc_html_e('Freelance', 'maneli-car-inquiry'); ?></option>
                    </select>
                </div>
                <div class="form-group"><label><?php esc_html_e('Address:', 'maneli-car-inquiry'); ?></label><textarea name="address"><?php echo esc_textarea(get_user_meta($user_id, 'address', true)); ?></textarea></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label><?php esc_html_e('Bank Name:', 'maneli-car-inquiry'); ?></label><input type="text" name="bank_name" value="<?php echo esc_attr(get_user_meta($user_id, 'bank_name', true)); ?>"></div>
                <div class="form-group"><label><?php esc_html_e('Account Number:', 'maneli-car-inquiry'); ?></label><input type="text" name="account_number" value="<?php echo esc_attr(get_user_meta($user_id, 'account_number', true)); ?>"></div>
            </div>
             <div class="form-row">
                <div class="form-group"><label><?php esc_html_e('Branch Code:', 'maneli-car-inquiry'); ?></label><input type="text" name="branch_code" value="<?php echo esc_attr(get_user_meta($user_id, 'branch_code', true)); ?>"></div>
                <div class="form-group"><label><?php esc_html_e('Branch Name:', 'maneli-car-inquiry'); ?></label><input type="text" name="branch_name" value="<?php echo esc_attr(get_user_meta($user_id, 'branch_name', true)); ?>"></div>
            </div>
    </div>

    <div id="issuer-form-wrapper" style="display: none;">
        <p class="form-section-title"><?php esc_html_e('Cheque Issuer Information Form', 'maneli-car-inquiry'); ?></p>
        <div class="form-grid">
            <div class="form-row">
                <div class="form-group"><label><?php esc_html_e('Issuer Full Name:', 'maneli-car-inquiry'); ?></label><input type="text" name="issuer_full_name"></div>
                <div class="form-group"><label><?php esc_html_e('Issuer National Code:', 'maneli-car-inquiry'); ?></label><input type="text" name="issuer_national_code" placeholder="<?php esc_attr_e('10-digit national ID', 'maneli-car-inquiry'); ?>" pattern="\d{10}"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label><?php esc_html_e('Issuer Father\'s Name:', 'maneli-car-inquiry'); ?></label><input type="text" name="issuer_father_name"></div>
                <div class="form-group"><label><?php esc_html_e('Issuer Date of Birth:', 'maneli-car-inquiry'); ?></label><input type="text" id="issuer_birth_date" name="issuer_birth_date"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label><?php esc_html_e('Issuer Mobile Number:', 'maneli-car-inquiry'); ?></label><input type="tel" name="issuer_mobile_number" placeholder="<?php esc_attr_e('e.g., 09129876543', 'maneli-car-inquiry'); ?>"></div>
                <div class="form-group"><label><?php esc_html_e('Issuer Occupation:', 'maneli-car-inquiry'); ?></label><input type="text" name="issuer_occupation"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label><?php esc_html_e('Issuer Phone Number:', 'maneli-car-inquiry'); ?></label><input type="tel" name="issuer_phone_number"></div>
                 <div class="form-group">
                     <label><?php esc_html_e('Issuer Residency Status', 'maneli-car-inquiry'); ?>:</label>
                     <select name="issuer_residency_status">
                         <option value=""><?php esc_html_e('-- Select --', 'maneli-car-inquiry'); ?></option>
                         <option value="owner"><?php esc_html_e('Owner', 'maneli-car-inquiry'); ?></option>
                         <option value="tenant"><?php esc_html_e('Tenant', 'maneli-car-inquiry'); ?></option>
                     </select>
                 </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><?php esc_html_e('Issuer Workplace Status', 'maneli-car-inquiry'); ?>:</label>
                    <select name="issuer_workplace_status">
                        <option value=""><?php esc_html_e('-- Select --', 'maneli-car-inquiry'); ?></option>
                        <option value="permanent"><?php esc_html_e('Permanent', 'maneli-car-inquiry'); ?></option>
                        <option value="contract"><?php esc_html_e('Contract', 'maneli-car-inquiry'); ?></option>
                        <option value="freelance"><?php esc_html_e('Freelance', 'maneli-car-inquiry'); ?></option>
                    </select>
                </div>
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
    
    <button type="submit" id="submit-identity-btn" class="loan-action-btn" style="width: 100%; margin-top: 20px;"><?php esc_html_e('Continue to Next Step', 'maneli-car-inquiry'); ?></button>
</form>