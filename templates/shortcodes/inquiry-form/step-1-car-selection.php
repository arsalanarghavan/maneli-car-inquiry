<?php
/**
 * Template for Step 1 of the inquiry form (Car Selection).
 *
 * This template instructs the user on how to start the inquiry process
 * if they have not yet selected a car from a product page.
 *
 * @package Autopuzzle_Car_Inquiry/Templates/Shortcodes/InquiryForm
 * @author  Gemini
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="la la-car me-2"></i>
                    <?php esc_html_e('Step 1: Select a Car', 'autopuzzle'); ?>
                </div>
            </div>
            <div class="card-body text-center py-5">
                <div class="mb-4">
                    <span class="avatar avatar-xxl bg-primary-transparent">
                        <i class="la la-car fs-1"></i>
                    </span>
                </div>
                <h4 class="mb-3"><?php esc_html_e('No Car Selected', 'autopuzzle'); ?></h4>
                <p class="text-muted mb-4">
                    <?php esc_html_e('To begin the inquiry process, please first select your desired car from one of the product pages and click the "Bank Credit Check" button.', 'autopuzzle'); ?>
                </p>
                
                <?php $options = get_option('autopuzzle_inquiry_all_options', []); $price_msg = $options['msg_price_disclaimer'] ?? esc_html__('Due to severe market fluctuations, car prices are approximate and may change until final approval.', 'autopuzzle'); ?>
                <div class="alert alert-warning" role="alert">
                    <i class="la la-info-circle me-2"></i>
                    <?php echo esc_html($price_msg); ?>
                </div>
                
                <a href="<?php echo esc_url(get_post_type_archive_link('product')); ?>" class="btn btn-primary btn-wave">
                    <i class="la la-car me-1"></i>
                    <?php esc_html_e('Go to Cars List', 'autopuzzle'); ?>
                </a>
            </div>
        </div>
    </div>
</div>
