<?php
/**
 * Template for displaying the grouped product attributes table.
 *
 * This template replaces the default WooCommerce "Additional Information" table
 * and is called by the Autopuzzle_Grouped_Attributes class.
 *
 * @package Autopuzzle_Car_Inquiry/Templates/Public
 * @author  Gemini
 * @version 1.0.0
 *
 * @var array $grouped_attributes An associative array of attributes, grouped by their category.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="sga-wrapper">
    <h2 class="sga-main-title"><?php esc_html_e('Specifications', 'autopuzzle'); ?></h2>

    <?php foreach ($grouped_attributes as $group_title => $attributes_in_group) : ?>
        <div class="sga-group-container">
            <h3 class="sga-group-title"><?php echo esc_html($group_title); ?></h3>
            <table class="sga-table shop_attributes">
                <tbody>
                    <?php foreach ($attributes_in_group as $attribute) : ?>
                        <tr>
                            <th class="sga-label"><?php echo wp_kses_post($attribute['label']); ?></th>
                            <td class="sga-value"><?php echo wp_kses_post($attribute['value']); // wpautop() is already applied in the class ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
</div>