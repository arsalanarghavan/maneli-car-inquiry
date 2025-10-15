<?php
/**
 * Template for Step 1 of the inquiry form (Car Selection).
 *
 * This template instructs the user on how to start the inquiry process
 * if they have not yet selected a car from a product page.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/InquiryForm
 * @author  Gemini
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class='maneli-inquiry-form'>
    <h3><?php esc_html_e('Step 1: Select a Car', 'maneli-car-inquiry'); ?></h3>
    <p>
        <?php esc_html_e('To begin the inquiry process, please first select your desired car from one of the product pages and click the "Bank Credit Check" button.', 'maneli-car-inquiry'); ?>
    </p>
    <p>
        <a href="<?php echo esc_url(get_post_type_archive_link('product')); ?>" class="loan-action-btn">
            <?php esc_html_e('Go to Cars List', 'maneli-car-inquiry'); ?>
        </a>
    </p>
</div>