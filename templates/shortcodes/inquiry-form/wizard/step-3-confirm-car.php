<?php
/**
 * Wizard Step 3: Confirm Car
 * استایل ویزارد - تایید خودرو با استایل products.html (بدون قیمت)
 * 
 * @package Autopuzzle_Car_Inquiry/Templates/Shortcodes/InquiryForm/Wizard
 * @version 2.0.0
 * 
 * @var int    $car_id
 * @var string $car_name
 * @var string $car_model
 * @var string $car_image_html
 * @var string $down_payment
 * @var string $term_months
 * @var string $total_price
 * @var string $installment_amount
 */

if (!defined('ABSPATH')) {
    exit;
}

$options = get_option('autopuzzle_inquiry_all_options', []);
$price_msg = $options['msg_price_disclaimer'] ?? esc_html__('Due to severe market fluctuations, car prices are approximate and may change until final confirmation.', 'autopuzzle');
?>

<div class="alert alert-info mb-4" role="alert">
    <i class="la la-info-circle me-2"></i>
    <?php echo esc_html__('You can click on any car below to replace the current selected car. The inquiry will be made for the selected car.', 'autopuzzle'); ?>
</div>

<!-- Selected Car Card - Product Style -->
<div class="row justify-content-center mb-4">
    <div class="col-xl-8">
        <div class="card custom-card card-style-2">
            <div class="card-body p-0">
                <div class="top-left-badge">
                    <div class="badge bg-primary d-inline-flex gap-1 lh-1 align-items-center text-fixed-white mb-1">
                        <div class="badge-icon"><i class="ti ti-check"></i></div>
                        <div class="badge-text"><?php esc_html_e('Selected Car', 'autopuzzle'); ?></div>
                    </div>
                </div>
                <div class="card-img-top border-bottom border-block-end-dashed">
                    <div class="img-box-2 p-4">
                        <?php echo wp_kses_post($car_image_html); ?>
                    </div>
                </div>
                <div class="p-4">
                    <h6 class="mb-3 fw-semibold fs-18"><?php echo esc_html($car_name); ?></h6>
                    <?php if (!empty($car_model)): ?>
                        <p class="text-muted mb-3">
                            <i class="la la-car me-1"></i>
                            <?php echo esc_html($car_model); ?>
                        </p>
                    <?php endif; ?>
                    
                    <div class="row g-3 mb-3" data-down-payment="<?php echo esc_attr($down_payment); ?>" data-term-months="<?php echo esc_attr($term_months); ?>" data-total-price="<?php echo esc_attr($total_price); ?>">
                        <div class="col-md-6">
                            <div class="border rounded p-3 bg-success-transparent">
                                <div class="text-muted fs-12 mb-1">
                                    <i class="la la-money-bill me-1"></i>
                                    <?php esc_html_e('Down Payment', 'autopuzzle'); ?>
                                </div>
                                <strong class="fs-16 text-success"><?php echo function_exists('autopuzzle_number_format_persian') ? autopuzzle_number_format_persian((int)$down_payment) : number_format_i18n((int)$down_payment); ?> <?php esc_html_e('Toman', 'autopuzzle'); ?></strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 bg-info-transparent">
                                <div class="text-muted fs-12 mb-1">
                                    <i class="la la-calendar me-1"></i>
                                    <?php esc_html_e('Installment Period', 'autopuzzle'); ?>
                                </div>
                                <strong class="fs-16 text-info"><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($term_months) : esc_html($term_months); ?> <?php esc_html_e('Months', 'autopuzzle'); ?></strong>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="border rounded p-3 bg-primary-transparent">
                                <div class="text-muted fs-12 mb-1">
                                    <i class="la la-calculator me-1"></i>
                                    <?php esc_html_e('Approximate Monthly Installment', 'autopuzzle'); ?>
                                </div>
                                <strong class="fs-18 text-primary"><?php echo function_exists('autopuzzle_number_format_persian') ? autopuzzle_number_format_persian((int)$installment_amount) : number_format_i18n((int)$installment_amount); ?> <?php esc_html_e('Toman', 'autopuzzle'); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info mb-4">
    <i class="la la-info-circle me-2"></i>
    <?php echo esc_html($price_msg); ?>
</div>

<!-- Confirmation Form - Now above Browse Cars -->
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="confirm-car-form">
    <input type="hidden" name="action" value="autopuzzle_confirm_car_step">
    <?php wp_nonce_field('autopuzzle_confirm_car_step_nonce'); ?>
    
    <div class="card border mb-4">
        <div class="card-body">
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="confirm_car_agree" id="confirm_car_agree" value="1" required>
                <label class="form-check-label" for="confirm_car_agree">
                    <?php esc_html_e('I agree and confirm this car.', 'autopuzzle'); ?>
                </label>
            </div>
            
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-light wizard-btn prev" data-step="2">
                    <i class="la la-arrow-right me-1"></i>
                    <?php esc_html_e('Back', 'autopuzzle'); ?>
                </button>
                <button type="submit" class="btn btn-primary flex-fill wizard-btn next">
                    <i class="la la-check me-1"></i>
                    <?php esc_html_e('Confirm and Continue', 'autopuzzle'); ?>
                </button>
            </div>
        </div>
    </div>
</form>

