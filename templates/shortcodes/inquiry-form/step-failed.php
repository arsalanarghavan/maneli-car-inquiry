<?php
/**
 * Template for the "Inquiry Failed" step.
 *
 * This template is shown to the user when the initial Finotex API call fails.
 * It provides an explanation and a button to retry the process.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/InquiryForm
 * @author  Gemini
 * @version 1.0.0
 *
 * @var int $inquiry_id The ID of the failed inquiry post.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<h3><?php esc_html_e('Inquiry Unsuccessful', 'maneli-car-inquiry'); ?></h3>

<div class="status-box status-failed">
    <p><?php esc_html_e('Unfortunately, an error occurred while attempting to retrieve your inquiry from the banking system. This issue could be due to incorrect identity information or temporary system problems.', 'maneli-car-inquiry'); ?></p>
</div>

<form class="inquiry-action-form maneli-inquiry-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
    <input type="hidden" name="action" value="maneli_retry_inquiry">
    <input type="hidden" name="inquiry_id" value="<?php echo esc_attr($inquiry_id); ?>">
    <?php wp_nonce_field('maneli_retry_inquiry_nonce'); ?>
    
    <p><?php esc_html_e('You can edit your information and try again.', 'maneli-car-inquiry'); ?></p>
    
    <button type="submit" class="loan-action-btn"><?php esc_html_e('Retry and Correct Information', 'maneli-car-inquiry'); ?></button>
</form>