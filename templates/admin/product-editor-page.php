<?php
/**
 * Template for the admin-side Product Editor page.
 * (/wp-admin/edit.php?post_type=product&page=maneli-product-editor)
 *
 * This template renders the main table for quick-editing product data.
 *
 * @package Maneli_Car_Inquiry/Templates/Admin
 * @author  Gemini
 * @version 1.0.0
 *
 * @var WC_Product[] $products Array of all product objects.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <p><?php esc_html_e('On this page, you can quickly change the price and sales status of products. Changes are saved automatically.', 'maneli-car-inquiry'); ?></p>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width:25%;"><strong><?php esc_html_e('Car Name', 'maneli-car-inquiry'); ?></strong></th>
                <th style="width:15%;"><strong><?php esc_html_e('Cash Price (Toman)', 'maneli-car-inquiry'); ?></strong></th>
                <th style="width:15%;"><strong><?php esc_html_e('Installment Price (Toman)', 'maneli-car-inquiry'); ?></strong></th>
                <th style="width:15%;"><strong><?php esc_html_e('Min. Down Payment (Toman)', 'maneli-car-inquiry'); ?></strong></th>
                <th style="width:15%;"><strong><?php esc_html_e('Available Colors (comma-separated)', 'maneli-car-inquiry'); ?></strong></th>
                <th style="width:15%;"><strong><?php esc_html_e('Sales Status', 'maneli-car-inquiry'); ?></strong></th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (!empty($products)) {
                foreach ($products as $product) {
                    $product_id = $product->get_id();
                    ?>
                    <tr>
                        <td>
                            <strong><a href="<?php echo get_edit_post_link($product_id); ?>" target="_blank"><?php echo esc_html($product->get_name()); ?></a></strong>
                        </td>
                        <td>
                            <input type="text"
                                   class="manli-data-input manli-price-input"
                                   data-product-id="<?php echo esc_attr($product_id); ?>"
                                   data-field-type="regular_price"
                                   value="<?php echo esc_attr($product->get_regular_price()); ?>"
                                   placeholder="<?php esc_attr_e('Cash Price', 'maneli-car-inquiry'); ?>">
                        </td>
                        <td>
                            <input type="text"
                                   class="manli-data-input manli-price-input"
                                   data-product-id="<?php echo esc_attr($product_id); ?>"
                                   data-field-type="installment_price"
                                   value="<?php echo esc_attr(get_post_meta($product_id, 'installment_price', true)); ?>"
                                   placeholder="<?php esc_attr_e('Installment Price', 'maneli-car-inquiry'); ?>">
                        </td>
                        <td>
                            <input type="text"
                                   class="manli-data-input manli-price-input"
                                   data-product-id="<?php echo esc_attr($product_id); ?>"
                                   data-field-type="min_downpayment"
                                   value="<?php echo esc_attr(get_post_meta($product_id, 'min_downpayment', true)); ?>"
                                   placeholder="<?php esc_attr_e('Down Payment Amount', 'maneli-car-inquiry'); ?>">
                        </td>
                        <td>
                             <input type="text"
                                   class="manli-data-input"
                                   style="width:100%;"
                                   data-product-id="<?php echo esc_attr($product_id); ?>"
                                   data-field-type="car_colors"
                                   value="<?php echo esc_attr(get_post_meta($product_id, '_maneli_car_colors', true)); ?>"
                                   placeholder="<?php esc_attr_e('e.g., White, Black, Silver', 'maneli-car-inquiry'); ?>">
                        </td>
                        <td>
                            <select class="manli-data-input"
                                    data-product-id="<?php echo esc_attr($product_id); ?>"
                                    data-field-type="car_status">
                                <option value="special_sale" <?php selected(get_post_meta($product_id, '_maneli_car_status', true), 'special_sale'); ?>><?php esc_html_e('Special Sale (Active)', 'maneli-car-inquiry'); ?></option>
                                <option value="unavailable" <?php selected(get_post_meta($product_id, '_maneli_car_status', true), 'unavailable'); ?>><?php esc_html_e('Unavailable (Show in site)', 'maneli-car-inquiry'); ?></option>
                                <option value="disabled" <?php selected(get_post_meta($product_id, '_maneli_car_status', true), 'disabled'); ?>><?php esc_html_e('Disabled (Hide from site)', 'maneli-car-inquiry'); ?></option>
                            </select>
                            <span class="spinner"></span>
                        </td>
                    </tr>
                    <?php
                }
            } else {
                echo '<tr><td colspan="6">' . esc_html__('No products found.', 'maneli-car-inquiry') . '</td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>