<?php
/**
 * Template for Step 3: Confirm Selected Car and Preview Catalog.
 *
 * Shows the selected car summary and a limited product gallery below (AJAX filters & pagination).
 * Selection is locked; user must confirm and proceed.
 *
 * @package Autopuzzle_Car_Inquiry/Templates/Shortcodes/InquiryForm
 * @version 1.0.0
 *
 * @var int    $user_id
 * @var int    $car_id
 * @var string $car_name
 * @var string $car_model
 * @var string $car_image_html
 * @var string $down_payment
 * @var string $term_months
 * @var string $total_price
 * @var string $installment_amount
 */

if (!defined('ABSPATH')) { exit; }

$options   = get_option('autopuzzle_inquiry_all_options', []);
$price_msg = $options['msg_price_disclaimer'] ?? esc_html__('Due to severe market fluctuations, car prices are approximate and may change until final approval.', 'autopuzzle');
$alert_msg = esc_html__('The inquiry will be performed for the car below and cannot be changed.', 'autopuzzle');

?>

<div class="row">
    <div class="col-xl-12">
        <div class="alert alert-warning" role="alert">
            <i class="la la-exclamation-triangle me-2"></i>
            <?php echo esc_html($alert_msg); ?>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-8">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="la la-check-circle me-2"></i>
                    <?php esc_html_e('Your Selected Car', 'autopuzzle'); ?>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-borderless">
                        <tbody>
                            <tr>
                                <td class="fw-semibold"><?php esc_html_e('Selected Car:', 'autopuzzle'); ?></td>
                                <td><?php echo esc_html($car_name); ?></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold"><?php esc_html_e('Car Model:', 'autopuzzle'); ?></td>
                                <td><?php echo esc_html($car_model); ?></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold"><?php esc_html_e('Total Price:', 'autopuzzle'); ?></td>
                                <td><span class="badge bg-primary-transparent"><?php echo esc_html(number_format_i18n((int)$total_price)); ?> <?php esc_html_e('Toman', 'autopuzzle'); ?></span></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold"><?php esc_html_e('Down Payment:', 'autopuzzle'); ?></td>
                                <td><span class="badge bg-success-transparent"><?php echo esc_html(number_format_i18n((int)$down_payment)); ?> <?php esc_html_e('Toman', 'autopuzzle'); ?></span></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold"><?php esc_html_e('Installment Term:', 'autopuzzle'); ?></td>
                                <td><?php echo esc_html($term_months); ?> <?php esc_html_e('Months', 'autopuzzle'); ?></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold"><?php esc_html_e('Approx. Installment:', 'autopuzzle'); ?></td>
                                <td><span class="badge bg-info-transparent"><?php echo esc_html(number_format_i18n((int)$installment_amount)); ?> <?php esc_html_e('Toman', 'autopuzzle'); ?></span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="alert alert-warning" role="alert">
                    <i class="la la-info-circle me-2"></i>
                    <?php echo esc_html($price_msg); ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-4">
        <div class="card custom-card">
            <div class="card-body text-center">
                <?php echo wp_kses_post($car_image_html); ?>
                <p class="fw-semibold mt-2"><?php echo esc_html($car_name); ?></p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="la la-car me-2"></i>
                    <?php esc_html_e('Browse Other Cars', 'autopuzzle'); ?>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <input type="text" id="confirm_car_search" class="form-control" placeholder="<?php esc_attr_e('Search…', 'autopuzzle'); ?>">
                    </div>
                    <div class="col-md-3">
                        <select id="confirm_car_brand" class="form-select">
                            <option value=""><?php esc_html_e('All Brands', 'autopuzzle'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select id="confirm_car_category" class="form-select">
                            <option value=""><?php esc_html_e('All Categories', 'autopuzzle'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button id="confirm_car_filter_btn" class="btn btn-primary w-100" type="button">
                            <i class="la la-filter me-1"></i>
                            <?php esc_html_e('Filter', 'autopuzzle'); ?>
                        </button>
                    </div>
                </div>

                <div id="confirm_car_catalog" class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-3">
                    <!-- AJAX cards inserted here -->
                </div>
                
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <a href="<?php echo esc_url(get_post_type_archive_link('product')); ?>" class="btn btn-light">
                        <?php esc_html_e('View Full Cars List', 'autopuzzle'); ?>
                    </a>
                    <div id="confirm_car_pagination"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-body">
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="confirm_car_agree" id="confirm_car_agree" value="1" required>
                        <label class="form-check-label" for="confirm_car_agree">
                            <?php esc_html_e('I agree and confirm this car.', 'autopuzzle'); ?>
                        </label>
                    </div>
                    
                    <input type="hidden" name="action" value="autopuzzle_confirm_car_step">
                    <?php wp_nonce_field('autopuzzle_confirm_car_step_nonce'); ?>
                    
                    <div class="d-flex gap-2">
                        <a href="<?php echo esc_url(home_url('/dashboard/inquiries/installment')); ?>" class="btn btn-light">
                            <i class="la la-arrow-left me-1"></i>
                            <?php esc_html_e('Back', 'autopuzzle'); ?>
                        </a>
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="la la-check me-1"></i>
                            <?php esc_html_e('Confirm and Continue', 'autopuzzle'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
