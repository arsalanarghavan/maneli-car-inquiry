<?php
/**
 * Template for Step 3: Confirm Selected Car and Preview Catalog.
 *
 * Shows the selected car summary and a limited product gallery below (AJAX filters & pagination).
 * Selection is locked; user must confirm and proceed.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/InquiryForm
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

$options   = get_option('maneli_inquiry_all_options', []);
$price_msg = $options['msg_price_disclaimer'] ?? esc_html__('Due to severe market fluctuations, car prices are approximate and may change until final approval.', 'maneli-car-inquiry');
$alert_msg = esc_html__('The inquiry will be performed for the car below and cannot be changed.', 'maneli-car-inquiry');

?>

<div class="confirm-car-wrapper">
    <div class="status-box status-warning" style="margin-bottom:15px;">
        <p><?php echo esc_html($alert_msg); ?></p>
    </div>
    <div class="inquiry-summary-flex-container">
        <div class="inquiry-summary-box">
            <h4><?php esc_html_e('Your Selected Car', 'maneli-car-inquiry'); ?></h4>
            <table class="summary-table">
                <tr><td><strong><?php esc_html_e('Selected Car:', 'maneli-car-inquiry'); ?></strong></td><td><?php echo esc_html($car_name); ?></td></tr>
                <tr><td><strong><?php esc_html_e('Car Model:', 'maneli-car-inquiry'); ?></strong></td><td><?php echo esc_html($car_model); ?></td></tr>
                <tr><td><strong><?php esc_html_e('Total Price:', 'maneli-car-inquiry'); ?></strong></td><td><?php echo esc_html(number_format_i18n((int)$total_price)); ?> <span><?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></span></td></tr>
                <tr><td><strong><?php esc_html_e('Down Payment:', 'maneli-car-inquiry'); ?></strong></td><td><?php echo esc_html(number_format_i18n((int)$down_payment)); ?> <span><?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></span></td></tr>
                <tr><td><strong><?php esc_html_e('Installment Term:', 'maneli-car-inquiry'); ?></strong></td><td><?php echo esc_html($term_months); ?> <span><?php esc_html_e('Months', 'maneli-car-inquiry'); ?></span></td></tr>
                <tr><td><strong><?php esc_html_e('Approx. Installment:', 'maneli-car-inquiry'); ?></strong></td><td><?php echo esc_html(number_format_i18n((int)$installment_amount)); ?> <span><?php esc_html_e('Toman', 'maneli-car-inquiry'); ?></span></td></tr>
            </table>
            <div class="status-box status-warning" style="margin-top:10px;">
                <p><?php echo esc_html($price_msg); ?></p>
            </div>
        </div>
        <div class="inquiry-car-image">
            <?php echo wp_kses_post($car_image_html); ?>
            <p style="text-align:center; margin-top:8px; font-weight:bold;"><?php echo esc_html($car_name); ?></p>
        </div>
    </div>

    <div class="catalog-filter-bar" style="display:flex; gap:10px; align-items:center; margin:20px 0; flex-wrap:wrap;">
        <input type="text" id="confirm_car_search" placeholder="<?php esc_attr_e('Search…', 'maneli-car-inquiry'); ?>" style="flex:1; min-width:200px;">
        <select id="confirm_car_brand" style="min-width:160px;"><option value=""><?php esc_html_e('All Brands', 'maneli-car-inquiry'); ?></option></select>
        <select id="confirm_car_category" style="min-width:160px;"><option value=""><?php esc_html_e('All Categories', 'maneli-car-inquiry'); ?></option></select>
        <button id="confirm_car_filter_btn" class="loan-action-btn" type="button"><?php esc_html_e('Filter', 'maneli-car-inquiry'); ?></button>
    </div>

    <div id="confirm_car_catalog" class="product-card-grid" style="display:grid; grid-template-columns: repeat(4, 1fr); gap:15px;">
        <!-- AJAX cards inserted here: only image and title, 2 rows (8 items) -->
    </div>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:15px;">
        <a href="<?php echo esc_url(get_post_type_archive_link('product')); ?>" class="loan-action-btn" style="background:#fff;color:#333;border:1px solid #ddd;">
            <?php esc_html_e('View Full Cars List', 'maneli-car-inquiry'); ?>
        </a>
        <div id="confirm_car_pagination"></div>
    </div>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:20px;">
        <label style="display:flex; gap:10px; align-items:center; margin-bottom:10px;">
            <input type="checkbox" name="confirm_car_agree" value="1" required>
            <span><?php esc_html_e('I agree and confirm this car.', 'maneli-car-inquiry'); ?></span>
        </label>
        <input type="hidden" name="action" value="maneli_confirm_car_step">
        <?php wp_nonce_field('maneli_confirm_car_step_nonce'); ?>
        <div style="display:flex; gap:10px;">
            <a href="<?php echo esc_url(home_url('/dashboard/?endp=inf_menu_1')); ?>" class="loan-action-btn" style="background:#fff;color:#333;border:1px solid #ddd;">
                <?php esc_html_e('Previous Step', 'maneli-car-inquiry'); ?>
            </a>
            <button type="submit" class="loan-action-btn"><?php esc_html_e('Continue', 'maneli-car-inquiry'); ?></button>
        </div>
    </form>
</div>


