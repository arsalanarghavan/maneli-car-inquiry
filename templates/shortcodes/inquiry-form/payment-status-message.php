<?php
/**
 * Template for displaying the payment status message after a gateway redirect.
 *
 * This template is included in various shortcodes to provide feedback to the user
 * about the result of their payment attempt.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/InquiryForm
 * @author  Gemini
 * @version 1.0.0
 *
 * @var string $status The status from the URL ('success', 'failed', 'cancelled').
 * @var string $reason An optional reason for failure.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Do nothing if status is not set
if (empty($status)) {
    return;
}

switch ($status) {
    case 'success':
        $alert_class = 'alert-success';
        $icon = 'la la-check-circle';
        $message = esc_html__('Your payment was successful. Your request has been submitted to our experts.', 'maneli-car-inquiry');
        break;
    case 'failed':
        $alert_class = 'alert-danger';
        $icon = 'la la-times-circle';
        $message = esc_html__('Unfortunately, your transaction was unsuccessful. If any amount was deducted, it will be returned to your account within 72 hours.', 'maneli-car-inquiry');
        if (!empty($reason)) {
            $message .= '<br><strong>' . esc_html__('Reason:', 'maneli-car-inquiry') . '</strong> ' . esc_html($reason);
        }
        break;
    case 'cancelled':
        $alert_class = 'alert-warning';
        $icon = 'ri-information-fill';
        $message = esc_html__('You have cancelled the payment. Your request has not been finalized yet.', 'maneli-car-inquiry');
        break;
    default:
        return; // Do not render anything for unknown statuses
}
?>

<div class="alert <?php echo esc_attr($alert_class); ?> alert-dismissible fade show d-flex align-items-center" role="alert" style="margin-bottom: 20px;">
    <i class="<?php echo esc_attr($icon); ?> fs-4 me-3"></i>
    <div class="flex-fill">
        <?php echo wp_kses_post($message); ?>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
