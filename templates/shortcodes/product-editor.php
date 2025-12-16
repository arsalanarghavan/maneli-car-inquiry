<?php
/**
 * Template for the frontend Product Editor, rendered by the [autopuzzle_product_editor] shortcode.
 *
 * This template displays statistical widgets, a search/filter form, and the main table
 * for editing product data via AJAX.
 *
 * @package Autopuzzle_Car_Inquiry/Templates/Shortcodes
 * @author  Gemini
 * @version 1.0.0
 *
 * @var string     $product_stats_widgets_html HTML for the product statistics widgets.
 * @var object     $initial_products_query     The initial WP_Query object for products.
 * @var int        $paged                      The current page number.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="autopuzzle-inquiry-wrapper">

    <?php echo $product_stats_widgets_html; // Already escaped in the generating function ?>

    <div class="user-list-header" style="margin-top: 40px;">
        <h3><?php esc_html_e('Car Price & Status List', 'autopuzzle'); ?></h3>
    </div>

    <div class="user-list-filters">
        <div class="filter-row search-row">
            <input type="search" id="product-search-input" class="search-input" placeholder="<?php esc_attr_e('Search car name...', 'autopuzzle'); ?>" style="width: 100%;">
        </div>
    </div>

    <div class="autopuzzle-table-wrapper">
        <table class="shop_table shop_table_responsive">
            <thead>
                <tr>
                    <th style="width:25%;"><strong><?php esc_html_e('Car Name', 'autopuzzle'); ?></strong></th>
                    <th style="width:15%;"><strong><?php esc_html_e('Cash Price (Toman)', 'autopuzzle'); ?></strong></th>
                    <th style="width:15%;"><strong><?php esc_html_e('Installment Price (Toman)', 'autopuzzle'); ?></strong></th>
                    <th style="width:15%;"><strong><?php esc_html_e('Min. Down Payment (Toman)', 'autopuzzle'); ?></strong></th>
                    <th style="width:15%;"><strong><?php esc_html_e('Available Colors', 'autopuzzle'); ?></strong></th>
                    <th style="width:15%;"><strong><?php esc_html_e('Sales Status', 'autopuzzle'); ?></strong></th>
                </tr>
            </thead>
            <tbody id="autopuzzle-product-list-tbody">
                <?php
                if (!empty($initial_products_query->products)) {
                    foreach ($initial_products_query->products as $product) {
                        // Use the helper class to render each row consistently
                        Autopuzzle_Render_Helpers::render_product_editor_row($product);
                    }
                } else {
                    echo '<tr><td colspan="6" style="text-align:center;">' . esc_html__('No products found.', 'autopuzzle') . '</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>

    <div id="product-list-loader" style="display:none; text-align:center; padding: 40px;">
        <div class="spinner is-active" style="float:none;"></div>
    </div>

    <div class="autopuzzle-pagination-wrapper" style="margin-top: 20px; text-align: center;">
         <?php
            echo paginate_links([
                'base'      => '#', // Handled by JS
                'format'    => '?paged=%#%',
                'current'   => $paged,
                'total'     => $initial_products_query->max_num_pages,
                'prev_text' => esc_html__('&laquo; Previous', 'autopuzzle'),
                'next_text' => esc_html__('Next &raquo;', 'autopuzzle'),
                'type'      => 'plain', // Use 'list' for ul/li structure if preferred
            ]);
         ?>
    </div>
</div>