<?php
/**
 * Template for the multi-step progress tracker.
 *
 * This template is included at the top of the inquiry form to show the user's
 * current stage in the process.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/InquiryForm
 * @author  Gemini
 * @version 1.0.0
 *
 * @var int $active_step The number of the currently active step.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define the steps of the process
$steps = [
    1 => esc_html__('Select Car', 'maneli-car-inquiry'),
    2 => esc_html__('Complete Information', 'maneli-car-inquiry'),
    3 => esc_html__('Payment', 'maneli-car-inquiry'),
    4 => esc_html__('Pending Review', 'maneli-car-inquiry'),
    5 => esc_html__('Final Result', 'maneli-car-inquiry'),
];

// Conditionally remove the payment step if it's disabled in the settings
$options = get_option('maneli_inquiry_all_options', []);
$payment_enabled = !empty($options['payment_enabled']) && $options['payment_enabled'] == '1';
if (!$payment_enabled) {
    unset($steps[3]);
}
?>

<div class="progress-tracker">
    <?php foreach ($steps as $number => $title) : ?>
        <?php
        $step_class = '';
        if ($number < $active_step) {
            $step_class = 'completed';
        } elseif ($number == $active_step) {
            $step_class = 'active';
        }
        
        // This calculates the visual step number (e.g., 1, 2, 3...) even if a step is removed
        $visual_step_number = array_search($number, array_keys($steps)) + 1;
        ?>
        <div class="step <?php echo esc_attr($step_class); ?>">
            <div class="circle"><?php echo number_format_i18n($visual_step_number); ?></div>
            <div class="label">
                <span class="step-title"><?php printf(esc_html__('Step %s', 'maneli-car-inquiry'), number_format_i18n($visual_step_number)); ?></span>
                <span class="step-name"><?php echo esc_html($title); ?></span>
            </div>
        </div>
    <?php endforeach; ?>
</div>