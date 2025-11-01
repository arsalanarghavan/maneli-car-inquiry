<?php
/**
 * Wizard Step 1: Car Selection
 * استایل ویزارد - انتخاب خودرو
 * 
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/InquiryForm/Wizard
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<h6 class="mb-3"><?php esc_html_e('Car Selection', 'maneli-car-inquiry'); ?>:</h6>
<div class="row gy-3">
    <div class="col-xl-12 text-center">
        <div class="mb-4">
            <span class="avatar avatar-xxl bg-primary-transparent">
                <i class="la la-car fs-1"></i>
            </span>
        </div>
        <h4 class="mb-3"><?php esc_html_e('No Car Selected', 'maneli-car-inquiry'); ?></h4>
        <p class="text-muted mb-4">
            <?php esc_html_e('To start the inquiry process, please first select your desired car from the products page and click on the "Bank Credit Inquiry" button.', 'maneli-car-inquiry'); ?>
        </p>
        
        <?php 
        $options = get_option('maneli_inquiry_all_options', []); 
        $price_msg = $options['msg_price_disclaimer'] ?? esc_html__('Due to severe market fluctuations, car prices are approximate and may change until final confirmation.', 'maneli-car-inquiry'); 
        ?>
        <div class="alert alert-warning d-inline-block" role="alert">
            <i class="la la-info-circle me-2"></i>
            <?php echo esc_html($price_msg); ?>
        </div>
        <div class="mt-4">
            <a href="<?php echo esc_url(get_post_type_archive_link('product')); ?>" class="btn btn-primary btn-wave">
                <i class="la la-car me-1"></i>
                <?php esc_html_e('Go to Cars List', 'maneli-car-inquiry'); ?>
            </a>
        </div>
    </div>
</div>

