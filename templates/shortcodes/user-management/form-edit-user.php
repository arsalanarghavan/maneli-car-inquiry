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

<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="la la-user-cog me-2"></i>
                    <?php printf(esc_html__('Editing User: %s', 'maneli-car-inquiry'), esc_html($user->display_name)); ?>
                </div>
            </div>
            <div class="card-body">
                <form id="admin-edit-user-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    
                    <input type="hidden" name="action" value="maneli_admin_update_user">
                    <input type="hidden" name="user_id" value="<?php echo esc_attr($user->ID); ?>">
                    <?php wp_nonce_field('maneli_admin_update_user', 'maneli_update_user_nonce'); ?>
                    <input type="hidden" name="_wp_http_referer" value="<?php echo esc_url($back_link); ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label"><?php esc_html_e('First Name:', 'maneli-car-inquiry'); ?></label>
                            <input type="text" id="first_name" name="first_name" class="form-control" value="<?php echo esc_attr($user->first_name); ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="last_name" class="form-label"><?php esc_html_e('Last Name:', 'maneli-car-inquiry'); ?></label>
                            <input type="text" id="last_name" name="last_name" class="form-control" value="<?php echo esc_attr($user->last_name); ?>">
                        </div>
                    </div>
                    
                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <label for="email" class="form-label"><?php esc_html_e('Email:', 'maneli-car-inquiry'); ?></label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="la la-envelope"></i>
                                </span>
                                <input type="email" id="email" name="email" class="form-control" value="<?php echo esc_attr($user->user_email); ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="mobile_number" class="form-label"><?php esc_html_e('Mobile Number:', 'maneli-car-inquiry'); ?></label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="la la-mobile"></i>
                                </span>
                                <input type="text" id="mobile_number" name="mobile_number" class="form-control" value="<?php echo esc_attr(get_user_meta($user->ID, 'mobile_number', true)); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <label for="father_name" class="form-label"><?php esc_html_e('Father\'s Name:', 'maneli-car-inquiry'); ?></label>
                            <input type="text" id="father_name" name="father_name" class="form-control" value="<?php echo esc_attr(get_user_meta($user->ID, 'father_name', true)); ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="birth_date" class="form-label"><?php esc_html_e('Date of Birth:', 'maneli-car-inquiry'); ?></label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="la la-calendar"></i>
                                </span>
                                <input type="text" name="birth_date" id="birth_date" class="form-control maneli-datepicker" value="<?php echo esc_attr(get_user_meta($user->ID, 'birth_date', true)); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <label for="national_code" class="form-label"><?php esc_html_e('National Code:', 'maneli-car-inquiry'); ?></label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="la la-id-card"></i>
                                </span>
                                <input type="text" id="national_code" name="national_code" class="form-control" value="<?php echo esc_attr(get_user_meta($user->ID, 'national_code', true)); ?>" placeholder="<?php esc_attr_e('10-digit national ID', 'maneli-car-inquiry'); ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="user_role" class="form-label"><?php esc_html_e('User Role:', 'maneli-car-inquiry'); ?></label>
                            <select name="user_role" id="user_role" class="form-select">
                                <option value="customer" <?php selected(in_array('customer', $user->roles)); ?>><?php esc_html_e('Customer', 'maneli-car-inquiry'); ?></option>
                                <option value="maneli_expert" <?php selected(in_array('maneli_expert', $user->roles)); ?>><?php esc_html_e('Maneli Expert', 'maneli-car-inquiry'); ?></option>
                                <option value="maneli_admin" <?php selected(in_array('maneli_admin', $user->roles)); ?>><?php esc_html_e('Maneli Manager', 'maneli-car-inquiry'); ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top">
                        <button type="submit" class="btn btn-success btn-wave">
                            <i class="la la-save me-1"></i>
                            <?php esc_html_e('Save Changes', 'maneli-car-inquiry'); ?>
                        </button>
                        <a href="<?php echo esc_url($back_link); ?>" class="btn btn-light btn-wave ms-2">
                            <i class="la la-arrow-left me-1"></i>
                            <?php esc_html_e('Cancel', 'maneli-car-inquiry'); ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
