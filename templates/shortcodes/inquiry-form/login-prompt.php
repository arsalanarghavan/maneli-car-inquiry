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

<div class="maneli-inquiry-wrapper">
    <div class="status-box status-warning">
        <h3><?php esc_html_e('Login Required', 'maneli-car-inquiry'); ?></h3>
        <p><?php esc_html_e('Please log in to start your inquiry.', 'maneli-car-inquiry'); ?></p>
        <p style="margin-top: 15px;">
            <a href="<?php echo esc_url($login_url); ?>" class="loan-action-btn">
                <?php esc_html_e('Log In', 'maneli-car-inquiry'); ?>
            </a>
        </p>
    </div>
</div>

