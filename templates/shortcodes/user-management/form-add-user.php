<?php
/**
 * Template for the "Add New User" form, part of the User Management shortcode.
 *
 * This template is displayed when the `?add_user=true` query parameter is present.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/UserManagement
 * @author  Gemini
 * @version 1.0.0
 *
 * @var string $back_link The URL to return to the user list.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="maneli-inquiry-wrapper">
    <h3><?php esc_html_e('Add New User', 'maneli-car-inquiry'); ?></h3>
    <p><?php esc_html_e('The new user will be created with the default "Customer" role. The username and email will be automatically generated based on the mobile number.', 'maneli-car-inquiry'); ?></p>
    
    <form id="admin-add-user-form" class="maneli-inquiry-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        
        <input type="hidden" name="action" value="maneli_admin_create_user">
        <?php wp_nonce_field('maneli_admin_create_user_nonce'); ?>
        <input type="hidden" name="_wp_http_referer" value="<?php echo esc_url($back_link); ?>">
        
        <div class="form-grid">
            <div class="form-row">
                <div class="form-group">
                    <label for="mobile_number"><?php esc_html_e('Mobile Number (Required):', 'maneli-car-inquiry'); ?></label>
                    <input type="tel" id="mobile_number" name="mobile_number" required>
                </div>
                <div class="form-group">
                    <label for="password"><?php esc_html_e('Password (Required):', 'maneli-car-inquiry'); ?></label>
                    <input type="password" id="password" name="password" required>
                </div>
            </div>
            
            <h4 class="form-section-title"><?php esc_html_e('Supplementary Information', 'maneli-car-inquiry'); ?></h4>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name"><?php esc_html_e('First Name:', 'maneli-car-inquiry'); ?></label>
                    <input type="text" id="first_name" name="first_name">
                </div>
                <div class="form-group">
                    <label for="last_name"><?php esc_html_e('Last Name:', 'maneli-car-inquiry'); ?></label>
                    <input type="text" id="last_name" name="last_name">
                </div>
            </div>
        </div>

        <div class="form-group" style="margin-top: 20px;">
            <button type="submit" class="loan-action-btn"><?php esc_html_e('Create User', 'maneli-car-inquiry'); ?></button>
            <a href="<?php echo esc_url($back_link); ?>" style="margin-right: 15px;"><?php esc_html_e('Cancel', 'maneli-car-inquiry'); ?></a>
        </div>
    </form>
</div>