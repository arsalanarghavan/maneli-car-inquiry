<?php
/**
 * Class Maneli_Grouped_Attributes
 * Handles the display of product attributes in grouped tables.
 */
if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Grouped_Attributes {

    public function __construct() {
        // Replace the default attribute table with our own
        add_filter('woocommerce_product_tabs', [$this, 'override_additional_info_tab'], 99);
        // Add CSS for styling
        add_action('wp_head', [$this, 'add_inline_styles']);
    }

    /**
     * Adds inline CSS to the page head for styling the attribute tables.
     */
    public function add_inline_styles() {
        // Only output on single product pages
        if (!is_product()) {
            return;
        }
        
        $css = '
            .sga-wrapper { 
                margin: 2em 0; 
            }
            .sga-wrapper .sga-main-title { 
                font-size: 1.6em; 
                font-weight: 700;
                margin-bottom: 1.5em;
                color: #2c3e50;
                text-align: center;
            }
            .sga-group-container {
                margin-bottom: 2.5em;
            }
            .sga-group-container .sga-group-title {
                font-size: 1.2em;
                font-weight: 600;
                color: #34495e;
                margin-bottom: 1em;
                padding-bottom: 0.5em;
                border-bottom: 2px solid #3498db;
                display: inline-block;
            }
            .sga-table { 
                width: 100%; 
                border-collapse: collapse; 
                table-layout: fixed; 
                border: 1px solid #ecf0f1;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            }
            .sga-table th, .sga-table td {
                padding: 1rem;
                border-bottom: 1px solid #ecf0f1;
                vertical-align: middle;
                text-align: right !important;
            }
            .sga-table tbody tr:last-of-type th, 
            .sga-table tbody tr:last-of-type td { 
                border-bottom: none; 
            }
            .sga-table .sga-label { 
                font-weight: 500; 
                color: #34495e;
                width: 40%;
                background-color: #f9fafb;
                border-left: 1px solid #ecf0f1;
            }
            .sga-table .sga-value {
                color: #7f8c8d;
            }
        ';
        echo '<style type="text/css">' . $css . '</style>';
    }

    /**
     * Overrides the default "Additional Information" tab callback with our custom function.
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
     */
    public function render_grouped_attributes_table() {
        global $product;
        $attributes = array_filter($product->get_attributes(), 'wc_attributes_array_filter_visible');
        if (empty($attributes)) {
            return;
        }

        $rows_data = [];
        $unknown_group = __('سایر', 'maneli-car-inquiry');
        $delimiter = ' - ';

        foreach ($attributes as $attribute) {
            $value = $product->get_attribute($attribute->get_name());
            if (empty($value)) continue;

            $label_from_wc = wc_attribute_label($attribute->get_name(), $product);
            $group = $unknown_group;
            $label = $label_from_wc;

            if (strpos($label_from_wc, $delimiter) !== false) {
                list($potential_group, $potential_label) = explode($delimiter, $label_from_wc, 2);
                $potential_group = trim($potential_group);
                $potential_label = trim($potential_label);

                if (!empty($potential_group) && !empty($potential_label)) {
                    $group = $potential_group;
                    $label = $potential_label;
                }
            }

            $rows_data[] = [
                'group' => $group,
                'label' => $label,
                'value' => $value,
            ];
        }

        if (empty($rows_data)) return;

        $grouped_rows = [];
        foreach ($rows_data as $row) {
            $grouped_rows[$row['group']][] = $row;
        }

        echo '<div class="sga-wrapper">';
        echo '<h2 class="sga-main-title">' . esc_html__('ویژگی‌ها', 'maneli-car-inquiry') . '</h2>';

        foreach ($grouped_rows as $group_title => $attributes_in_group) {
            echo '<div class="sga-group-container">';
            echo '<h3 class="sga-group-title">' . esc_html($group_title) . '</h3>';
            echo '<table class="sga-table shop_attributes">';
            echo '<tbody>';

            foreach ($attributes_in_group as $attribute) {
                echo '<tr>';
                echo '<th class="sga-label">' . wp_kses_post($attribute['label']) . '</th>';
                echo '<td class="sga-value">' . wp_kses_post(wpautop($attribute['value'])) . '</td>';
                echo '</tr>';
            }

            echo '</tbody>';
            echo '</table>';
            echo '</div>'; // End .sga-group-container
        }

        echo '</div>'; // End .sga-wrapper
    }
}