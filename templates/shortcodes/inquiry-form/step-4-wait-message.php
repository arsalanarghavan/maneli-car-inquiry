<?php
/**
 * Template for Step 4 of the inquiry form (Waiting for Review).
 *
 * This template is shown to the user after they have submitted their information and are
 * waiting for an expert to review their inquiry. It can display different messages
 * based on the inquiry's sub-status.
 *
 * @package Autopuzzle_Car_Inquiry/Templates/Shortcodes/InquiryForm
 * @author  Gemini
 * @version 1.0.0
 *
 * @var string $status The current status of the inquiry (e.g., 'pending', 'more_docs').
 */

if (!defined('ABSPATH')) {
    exit;
}

// Determine the message to display based on the inquiry status
$options = get_option('autopuzzle_inquiry_all_options', []);
$default_wait = esc_html__('Your request has been submitted. The result will be announced within the next 24 hours.', 'autopuzzle');
// If the inquiry is approved (user_confirmed), show a configurable post-approval message instead
if (!empty($_GET['status']) && $_GET['status'] === 'success' && !empty($options['msg_after_approval'])) {
    $message = $options['msg_after_approval'];
} else if ($status === 'more_docs') {
    $message = esc_html__('Our experts require additional documents to complete the process. Please wait for our team to contact you.', 'autopuzzle');
} else {
    $message = !empty($options['msg_waiting_review']) ? $options['msg_waiting_review'] : $default_wait;
}
?>

<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="la la-clock me-2"></i>
                    <?php esc_html_e('Step 4: Pending Review', 'autopuzzle'); ?>
                </div>
            </div>
            <div class="card-body text-center py-5">
                <div class="mb-4">
                    <span class="avatar avatar-xxl bg-warning-transparent">
                        <i class="la la-clock fs-1"></i>
                    </span>
                </div>
                <h4 class="mb-3"><?php esc_html_e('Thank you for your patience.', 'autopuzzle'); ?></h4>
                <div class="alert alert-warning d-inline-block" role="alert">
                    <?php echo esc_html($message); ?>
                </div>
                <p class="text-muted fs-12 mt-3">
                    <i class="la la-info-circle me-1"></i>
                    <?php esc_html_e('Typically within 24 hours.', 'autopuzzle'); ?>
                </p>
            </div>
        </div>
    </div>
</div>
