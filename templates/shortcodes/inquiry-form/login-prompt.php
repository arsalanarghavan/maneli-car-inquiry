<?php
/**
 * Template for displaying a login prompt to non-logged-in users.
 *
 * This template is shown when a guest user tries to access the inquiry form.
 * It prompts them to log in first before starting the inquiry process.
 *
 * @package Autopuzzle_Car_Inquiry/Templates/Shortcodes/InquiryForm
 * @author  Arsalan Arghavan
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Redirect to the dashboard login page with return URL
$return_url = home_url('/dashboard/new-inquiry');
if (!empty($_SERVER['REQUEST_URI'])) {
    $return_url = home_url($_SERVER['REQUEST_URI']);
}
$login_url = add_query_arg('redirect_to', urlencode($return_url), home_url('/dashboard/login'));
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
                <h3 class="mb-3"><?php esc_html_e('Login Required', 'autopuzzle'); ?></h3>
                <p class="text-muted mb-4"><?php esc_html_e('Please log in to start your inquiry.', 'autopuzzle'); ?></p>
                <a href="<?php echo esc_url($login_url); ?>" class="btn btn-primary btn-wave">
                    <i class="la la-sign-in-alt me-1"></i>
                    <?php esc_html_e('Log In', 'autopuzzle'); ?>
                </a>
            </div>
        </div>
    </div>
</div>
