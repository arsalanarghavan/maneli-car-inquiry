<?php
/**
 * Template for Step 4 of the inquiry form (Waiting for Review).
 *
 * This template is shown to the user after they have submitted their information and are
 * waiting for an expert to review their inquiry. It can display different messages
 * based on the inquiry's sub-status.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/InquiryForm
 * @author  Gemini
 * @version 1.0.0
 *
 * @var string $status The current status of the inquiry (e.g., 'pending', 'more_docs').
 */

if (!defined('ABSPATH')) {
    exit;
}

// Determine the message to display based on the inquiry status
$options = get_option('maneli_inquiry_all_options', []);
$default_wait = esc_html__('Your request has been submitted. The result will be announced within the next 24 hours.', 'maneli-car-inquiry');
if ($status === 'more_docs') {
    $message = esc_html__('Our experts require additional documents to complete the process. Please wait for our team to contact you.', 'maneli-car-inquiry');
} else {
    // Default 'pending' message, editable via settings
    $message = !empty($options['msg_waiting_review']) ? $options['msg_waiting_review'] : $default_wait;
}
?>

<h3><?php esc_html_e('Step 4: Pending Review', 'maneli-car-inquiry'); ?></h3>

<div class="status-box status-pending">
    <p><?php esc_html_e('Thank you for your patience.', 'maneli-car-inquiry'); ?></p>
    <p><?php echo esc_html($message); ?></p>
    <p style="margin-top:8px; font-size: 12px; color: #666;">(<?php esc_html_e('Typically within 24 hours.', 'maneli-car-inquiry'); ?>)</p>
</div>