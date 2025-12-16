<?php
/**
 * Wizard Step 4/5: Wait Message
 * استایل ویزارد - انتظار برای بررسی
 * 
 * @package Autopuzzle_Car_Inquiry/Templates/Shortcodes/InquiryForm/Wizard
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Determine the message to display based on the inquiry status
$options = get_option('autopuzzle_inquiry_all_options', []);
$default_wait = esc_html__('Your request has been registered. The result will be announced within the next 24 hours.', 'autopuzzle');
$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'pending';

// If the inquiry is approved (user_confirmed), show a configurable post-approval message instead
if (!empty($_GET['status']) && $_GET['status'] === 'success' && !empty($options['msg_after_approval'])) {
    $message = $options['msg_after_approval'];
} else if ($status === 'more_docs') {
    $message = esc_html__('Our experts need more documents to complete the process. Please wait for our team to contact you.', 'autopuzzle');
} else {
    $message = !empty($options['msg_waiting_review']) ? $options['msg_waiting_review'] : $default_wait;
}
?>

<div class="text-center p-4">
    <span class="avatar avatar-xl avatar-rounded bg-warning-transparent">
        <i class="la la-clock fs-1"></i>
    </span>
    <h3 class="mt-3"><?php esc_html_e('Thank You for Your Patience', 'autopuzzle'); ?></h3>
    <p class="text-muted mb-4"><?php echo esc_html($message); ?></p>
    <p class="text-muted fs-12">
        <i class="la la-info-circle me-1"></i>
        <?php esc_html_e('Usually within 24 hours.', 'autopuzzle'); ?>
    </p>
    <div class="mt-4">
        <a href="<?php echo esc_url(home_url('/dashboard/inquiries/installment')); ?>" class="btn btn-primary">
            <i class="la la-list me-1"></i>
            <?php esc_html_e('View Inquiries List', 'autopuzzle'); ?>
        </a>
    </div>
</div>

