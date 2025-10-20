<?php
/**
 * Template for displaying a login prompt to non-logged-in users.
 *
 * This template is shown when a guest user tries to access the inquiry form.
 * It prompts them to log in first before starting the inquiry process.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/InquiryForm
 * @author  Arsalan Arghavan
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Redirect to the dashboard login page
$login_url = home_url('/dashboard/');
?>

<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-body text-center py-5">
                <div class="mb-4">
                    <span class="avatar avatar-xxl bg-warning-transparent">
                        <i class="la la-lock fs-1"></i>
                    </span>
                </div>
                <h3 class="mb-3"><?php esc_html_e('Login Required', 'maneli-car-inquiry'); ?></h3>
                <p class="text-muted mb-4"><?php esc_html_e('Please log in to start your inquiry.', 'maneli-car-inquiry'); ?></p>
                <a href="<?php echo esc_url($login_url); ?>" class="btn btn-primary btn-wave">
                    <i class="la la-sign-in-alt me-1"></i>
                    <?php esc_html_e('Log In', 'maneli-car-inquiry'); ?>
                </a>
            </div>
        </div>
    </div>
</div>
