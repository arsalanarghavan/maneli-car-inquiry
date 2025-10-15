<?php
/**
 * Template for the "Edit User" form, part of the User Management shortcode.
 *
 * This template is displayed when the `?edit_user={user_id}` query parameter is present.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/UserManagement
 * @author  Gemini
 * @version 1.0.0
 *
 * @var WP_User $user      The user object to be edited.
 * @var string  $back_link The URL to return to the user list.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="maneli-inquiry-wrapper">
    <h3><?php printf(esc_html__('Editing User: %s', 'maneli-car-inquiry'), esc_html($user->display_name)); ?></h3>
    
    <form id="admin-edit-user-form" class="maneli-inquiry-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        
        <input type="hidden" name="action" value="maneli_admin_update_user">
        <input type="hidden" name="user_id" value="<?php echo esc_attr($user->ID); ?>">
        <?php wp_nonce_field('maneli_admin_update_user', 'maneli_update_user_nonce'); ?>
        <input type="hidden" name="_wp_http_referer" value="<?php echo esc_url($back_link); ?>">

        <div class="form-grid">
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name"><?php esc_html_e('First Name:', 'maneli-car-inquiry'); ?></label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo esc_attr($user->first_name); ?>">
                </div>
                <div class="form-group">
                    <label for="last_name"><?php esc_html_e('Last Name:', 'maneli-car-inquiry'); ?></label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo esc_attr($user->last_name); ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="email"><?php esc_html_e('Email:', 'maneli-car-inquiry'); ?></label>
                    <input type="email" id="email" name="email" value="<?php echo esc_attr($user->user_email); ?>">
                </div>
                <div class="form-group">
                    <label for="mobile_number"><?php esc_html_e('Mobile Number:', 'maneli-car-inquiry'); ?></label>
                    <input type="text" id="mobile_number" name="mobile_number" value="<?php echo esc_attr(get_user_meta($user->ID, 'mobile_number', true)); ?>">
                </div>
            </div>
             <div class="form-row">
                <div class="form-group">
                    <label for="father_name"><?php esc_html_e('Father\'s Name:', 'maneli-car-inquiry'); ?></label>
                    <input type="text" id="father_name" name="father_name" value="<?php echo esc_attr(get_user_meta($user->ID, 'father_name', true)); ?>">
                </div>
                <div class="form-group">
                    <label for="birth_date"><?php esc_html_e('Date of Birth:', 'maneli-car-inquiry'); ?></label>
                    <input type="text" name="birth_date" id="birth_date" class="maneli-datepicker" value="<?php echo esc_attr(get_user_meta($user->ID, 'birth_date', true)); ?>">
                </div>
            </div>
             <div class="form-row">
                <div class="form-group">
                    <label for="national_code"><?php esc_html_e('National Code:', 'maneli-car-inquiry'); ?></label>
                    <input type="text" id="national_code" name="national_code" value="<?php echo esc_attr(get_user_meta($user->ID, 'national_code', true)); ?>" placeholder="<?php esc_attr_e('10-digit national ID', 'maneli-car-inquiry'); ?>">
                </div>
                <div class="form-group">
                    <label for="user_role"><?php esc_html_e('User Role:', 'maneli-car-inquiry'); ?></label>
                    <select name="user_role" id="user_role" style="width: 100%;">
                        <option value="customer" <?php selected(in_array('customer', $user->roles)); ?>><?php esc_html_e('Customer', 'maneli-car-inquiry'); ?></option>
                        <option value="maneli_expert" <?php selected(in_array('maneli_expert', $user->roles)); ?>><?php esc_html_e('Maneli Expert', 'maneli-car-inquiry'); ?></option>
                        <option value="maneli_admin" <?php selected(in_array('maneli_admin', $user->roles)); ?>><?php esc_html_e('Maneli Manager', 'maneli-car-inquiry'); ?></option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-group" style="margin-top: 20px;">
            <button type="submit" class="loan-action-btn"><?php esc_html_e('Save Changes', 'maneli-car-inquiry'); ?></button>
            <a href="<?php echo esc_url($back_link); ?>" style="margin-right: 15px;"><?php esc_html_e('Cancel', 'maneli-car-inquiry'); ?></a>
        </div>
    </form>
</div>