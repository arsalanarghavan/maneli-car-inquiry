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
    3 => esc_html__('Confirm Car', 'maneli-car-inquiry'),
    4 => esc_html__('Payment', 'maneli-car-inquiry'),
    5 => esc_html__('Pending Review', 'maneli-car-inquiry'),
    6 => esc_html__('Final Result', 'maneli-car-inquiry'),
];

// Conditionally remove the payment step if it's disabled in the settings
$options = get_option('maneli_inquiry_all_options', []);
$payment_enabled = !empty($options['payment_enabled']) && $options['payment_enabled'] == '1';
if (!$payment_enabled) {
    unset($steps[4]);
}
?>

<div class="card custom-card mb-4">
    <div class="card-body">
        <div class="progress-stacked mb-3" style="height: 3px;">
            <?php 
            $total_steps = count($steps);
            $current_index = array_search($active_step, array_keys($steps)) + 1;
            $progress_percentage = ($current_index / $total_steps) * 100;
            ?>
            <div class="progress" role="progressbar" style="width: <?php echo $progress_percentage; ?>%">
                <div class="progress-bar bg-primary"></div>
            </div>
        </div>
        
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <?php foreach ($steps as $number => $title) : ?>
                <?php
                $step_class = '';
                $icon_class = 'ri-checkbox-blank-circle-line';
                $text_class = 'text-muted';
                
                if ($number < $active_step) {
                    $step_class = 'completed';
                    $icon_class = 'la la-check-circle';
                    $text_class = 'text-success';
                } elseif ($number == $active_step) {
                    $step_class = 'active';
                    $icon_class = 'ri-play-circle-fill';
                    $text_class = 'text-primary fw-semibold';
                }
                
                // This calculates the visual step number (e.g., 1, 2, 3...) even if a step is removed
                $visual_step_number = array_search($number, array_keys($steps)) + 1;
                ?>
                <div class="text-center" style="flex: 1;">
                    <div class="mb-2">
                        <span class="avatar avatar-sm avatar-rounded <?php echo $step_class === 'completed' ? 'bg-success-transparent' : ($step_class === 'active' ? 'bg-primary-transparent' : 'bg-light'); ?>">
                            <i class="<?php echo $icon_class; ?> fs-18"></i>
                        </span>
                    </div>
                    <div class="<?php echo $text_class; ?> fs-12">
                        <div class="fw-semibold"><?php printf(esc_html__('Step %s', 'maneli-car-inquiry'), number_format_i18n($visual_step_number)); ?></div>
                        <div><?php echo esc_html($title); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
