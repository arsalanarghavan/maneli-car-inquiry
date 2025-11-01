<?php
/**
 * Wizard Step 1: Selected Car Summary
 * استایل ویزارد - خلاصه خودرو انتخاب شده
 * 
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/InquiryForm/Wizard
 * @version 2.0.0
 * 
 * @var array $car_data Array containing car information
 */

if (!defined('ABSPATH')) {
    exit;
}

// Extract data from array
$car_id = $car_data['car_id'] ?? 0;
$car_name = $car_data['car_name'] ?? '';
$car_model = $car_data['car_model'] ?? '';
$car_image_html = $car_data['car_image_html'] ?? '';
$down_payment = $car_data['down_payment'] ?? '';
$term_months = $car_data['term_months'] ?? '';
$total_price = $car_data['total_price'] ?? '';
$installment_amount = $car_data['installment_amount'] ?? '';

$options = get_option('maneli_inquiry_all_options', []);
$price_msg = $options['msg_price_disclaimer'] ?? esc_html__('Due to severe market fluctuations, car prices are approximate and may change until final confirmation.', 'maneli-car-inquiry');
?>

<h6 class="mb-3"><?php esc_html_e('Selected Car Summary:', 'maneli-car-inquiry'); ?></h6>

<!-- Selected Car Card -->
<div class="card border mb-4">
    <div class="card-header bg-light">
        <h6 class="card-title mb-0">
            <i class="la la-check-circle text-success me-2"></i>
            <?php esc_html_e('Your Selected Car', 'maneli-car-inquiry'); ?>
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 text-center mb-3 mb-md-0">
                <?php echo wp_kses_post($car_image_html); ?>
            </div>
            <div class="col-md-8">
                <h5 class="mb-3"><?php echo esc_html($car_name); ?></h5>
                <?php if (!empty($car_model)): ?>
                    <p class="text-muted mb-3">
                        <i class="la la-car me-1"></i>
                        <?php echo esc_html($car_model); ?>
                    </p>
                <?php endif; ?>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="border rounded p-3 bg-success-transparent">
                            <div class="text-muted fs-12 mb-1">
                                <i class="la la-money-bill me-1"></i>
                                <?php esc_html_e('Down Payment', 'maneli-car-inquiry'); ?>
                            </div>
                            <strong class="fs-16 text-success"><?php echo number_format_i18n((int)$down_payment); ?> <?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3 bg-info-transparent">
                            <div class="text-muted fs-12 mb-1">
                                <i class="la la-calendar me-1"></i>
                                <?php esc_html_e('Installment Period', 'maneli-car-inquiry'); ?>
                            </div>
                            <strong class="fs-16 text-info"><?php echo esc_html($term_months); ?> <?php esc_html_e('Months', 'maneli-car-inquiry'); ?></strong>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="border rounded p-3 bg-warning-transparent">
                            <div class="text-muted fs-12 mb-1">
                                <i class="la la-calculator me-1"></i>
                                <?php esc_html_e('Approximate Monthly Installment', 'maneli-car-inquiry'); ?>
                            </div>
                            <strong class="fs-18 text-warning"><?php echo number_format_i18n((int)$installment_amount); ?> <?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-warning mb-4" role="alert">
    <i class="la la-info-circle me-2"></i>
    <?php echo esc_html($price_msg); ?>
</div>

<div class="alert alert-info">
    <i class="la la-arrow-right me-2"></i>
    <?php esc_html_e('Please proceed to the next step to complete your information.', 'maneli-car-inquiry'); ?>
</div>

