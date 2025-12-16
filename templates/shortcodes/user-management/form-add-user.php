<?php
/**
 * Template for the "Add New User" form, part of the User Management shortcode.
 *
 * This template is displayed when the `?add_user=true` query parameter is present.
 *
 * @package Autopuzzle_Car_Inquiry/Templates/Shortcodes/UserManagement
 * @author  Gemini
 * @version 1.0.0
 *
 * @var string $back_link The URL to return to the user list.
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
                    <i class="la la-user-plus me-2"></i>
                    <?php esc_html_e('Add New User', 'autopuzzle'); ?>
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-info" role="alert">
                    <i class="la la-info-circle me-2"></i>
                    <?php esc_html_e('The new user will be created with the default "Customer" role. The username and email will be automatically generated based on the mobile number.', 'autopuzzle'); ?>
                </div>
                
                <form id="admin-add-user-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    
                    <input type="hidden" name="action" value="autopuzzle_admin_create_user">
                    <?php wp_nonce_field('autopuzzle_admin_create_user_nonce'); ?>
                    <input type="hidden" name="_wp_http_referer" value="<?php echo esc_url($back_link); ?>">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="mobile_number" class="form-label">
                                <?php esc_html_e('Mobile Number (Required):', 'autopuzzle'); ?>
                                <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="la la-mobile"></i>
                                </span>
                                <input type="tel" id="mobile_number" name="mobile_number" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="password" class="form-label">
                                <?php esc_html_e('Password (Required):', 'autopuzzle'); ?>
                                <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="la la-lock"></i>
                                </span>
                                <input type="password" id="password" name="password" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <h5 class="mt-4 mb-3">
                        <i class="la la-user me-2"></i>
                        <?php esc_html_e('Supplementary Information', 'autopuzzle'); ?>
                    </h5>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label"><?php esc_html_e('First Name:', 'autopuzzle'); ?></label>
                            <input type="text" id="first_name" name="first_name" class="form-control">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="last_name" class="form-label"><?php esc_html_e('Last Name:', 'autopuzzle'); ?></label>
                            <input type="text" id="last_name" name="last_name" class="form-control">
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top">
                        <button type="submit" class="btn btn-primary btn-wave">
                            <i class="la la-save me-1"></i>
                            <?php esc_html_e('Create User', 'autopuzzle'); ?>
                        </button>
                        <a href="<?php echo esc_url($back_link); ?>" class="btn btn-light btn-wave ms-2">
                            <i class="la la-arrow-left me-1"></i>
                            <?php esc_html_e('Cancel', 'autopuzzle'); ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
