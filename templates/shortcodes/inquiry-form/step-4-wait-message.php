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
if ($status === 'more_docs') {
    $message = esc_html__('Our experts require additional documents to complete the process. Please wait for our team to contact you.', 'maneli-car-inquiry');
} else {
    // Default 'pending' message
    $message = esc_html__('Your inquiry request has been successfully submitted. You will be notified of the result after it has been reviewed by our experts (usually within 24 business hours).', 'maneli-car-inquiry');
}
?>

<h3><?php esc_html_e('Step 4: Pending Review', 'maneli-car-inquiry'); ?></h3>

<div class="status-box status-pending">
    <p><?php esc_html_e('Thank you for your patience.', 'maneli-car-inquiry'); ?></p>
    <p><?php echo esc_html($message); ?></p>
</div>