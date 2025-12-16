<?php
/**
 * Handles the display of product attributes in custom grouped tables,
 * replacing the default WooCommerce "Additional Information" tab.
 *
 * @package Autopuzzle_Car_Inquiry/Includes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Autopuzzle_Grouped_Attributes {

    public function __construct() {
        // Replace the default "Additional Information" tab callback with our custom function.
        // Priority 99 ensures it runs after most other plugins.
        add_filter('woocommerce_product_tabs', [$this, 'override_additional_info_tab'], 99);
    }

    /**
     * Overrides the default "Additional Information" tab if the product has attributes.
     *
     * @param array $tabs The array of product tabs.
     * @return array Modified tabs array.
     */
    public function override_additional_info_tab($tabs) {
        global $product;

        if ($product && $product->has_attributes() && isset($tabs['additional_information'])) {
            $tabs['additional_information']['callback'] = [$this, 'render_grouped_attributes_table'];
        }

        return $tabs;
    }

    /**
     * Renders the custom grouped attributes table.
     * This function is the callback for the "Additional Information" tab.
     */
    public function render_grouped_attributes_table() {
        global $product;

        // Filter out invisible attributes
        $attributes = array_filter($product->get_attributes(), 'wc_attributes_array_filter_visible');

        if (empty($attributes)) {
            return;
        }

        $grouped_attributes = $this->group_attributes($attributes, $product);

        if (empty($grouped_attributes)) {
            return;
        }
        
        // Pass the grouped attributes to a template file for rendering.
        autopuzzle_get_template_part('public/grouped-attributes-table', ['grouped_attributes' => $grouped_attributes]);
    }

    /**
     * Processes product attributes and organizes them into groups based on their labels.
     *
     * @param WC_Product_Attribute[] $attributes The array of product attributes.
     * @param WC_Product             $product    The product object.
     * @return array An associative array of grouped attributes.
     */
    private function group_attributes($attributes, $product) {
        $grouped_data = [];
        $unknown_group_name = esc_html__('Other Specifications', 'autopuzzle');
        $delimiter = ' - '; // Delimiter used in attribute names, e.g., "Technical - Engine"

        foreach ($attributes as $attribute) {
            $value = $product->get_attribute($attribute->get_name());
            
            // Skip attributes with no value
            if (empty($value)) {
                continue;
            }

            $original_label = wc_attribute_label($attribute->get_name(), $product);
            $group_name = $unknown_group_name;
            $attribute_label = $original_label;

            // Check if the label contains the delimiter to split it into group and label
            if (strpos($original_label, $delimiter) !== false) {
                list($potential_group, $potential_label) = explode($delimiter, $original_label, 2);
                $potential_group = trim($potential_group);
                $potential_label = trim($potential_label);

                if (!empty($potential_group) && !empty($potential_label)) {
                    $group_name = $potential_group;
                    $attribute_label = $potential_label;
                }
            }

            $grouped_data[$group_name][] = [
                'label' => $attribute_label,
                'value' => wpautop(wp_kses_post($value)), // Process value for display
            ];
        }
        
        return $grouped_data;
    }
}