<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles the frontend shortcode for editing product prices and statuses.
 */
class Maneli_Product_Editor_Shortcode {

    public function __construct() {
        add_shortcode('maneli_product_editor', [$this, 'render_shortcode']);
        add_action('wp_ajax_maneli_filter_products_ajax', [$this, 'handle_filter_products_ajax']);
    }

    /**
     * AJAX handler for filtering/searching products.
     */
    public function handle_filter_products_ajax() {
        check_ajax_referer('maneli_product_filter_nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'شما دسترسی لازم را ندارید.']);
        }

        $search_term = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
        
        $args = [
            'limit' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ];

        if (!empty($search_term)) {
            $args['s'] = $search_term;
        }

        $products = wc_get_products($args);

        ob_start();
        if (!empty($products)) {
            foreach ($products as $product) {
                self::render_product_row($product);
            }
        } else {
            echo '<tr><td colspan="4">هیچ محصولی با این مشخصات یافت نشد.</td></tr>';
        }
        $html = ob_get_clean();
        wp_send_json_success(['html' => $html]);
    }

    /**
     * Renders the HTML for the shortcode.
     */
    public function render_shortcode() {
        if (!current_user_can('manage_woocommerce')) {
            return '<div class="maneli-inquiry-wrapper error-box"><p>شما دسترسی لازم برای مشاهده این بخش را ندارید.</p></div>';
        }

        ob_start();
        ?>
        <div class="maneli-inquiry-wrapper">
            
            <?php echo Maneli_Admin_Dashboard_Widgets::render_product_statistics_widgets(); ?>

            <div class="user-list-header" style="margin-top: 40px;">
                <h3>لیست قیمت و وضعیت خودروها</h3>
            </div>

            <div class="user-list-filters">
                <div class="filter-row search-row">
                    <input type="search" id="product-search-input" class="search-input" placeholder="جستجوی نام خودرو..." style="width: 100%;">
                </div>
            </div>

            <table class="shop_table shop_table_responsive">
                <thead>
                    <tr>
                        <th style="width:35%;"><strong>نام خودرو</strong></th>
                        <th style="width:20%;"><strong>قیمت کامل (تومان)</strong></th>
                        <th style="width:20%;"><strong>حداقل پیش‌پرداخت (تومان)</strong></th>
                        <th style="width:25%;"><strong>وضعیت فروش</strong></th>
                    </tr>
                </thead>
                <tbody id="maneli-product-list-tbody">
                    <?php
                    $products = wc_get_products(['limit' => -1, 'orderby' => 'title', 'order' => 'ASC']);
                    if (!empty($products)) {
                        foreach ($products as $product) {
                            self::render_product_row($product);
                        }
                    } else {
                        echo '<tr><td colspan="4">هیچ محصولی یافت نشد.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
            <div id="product-list-loader" style="display:none; text-align:center; padding: 40px;"><div class="spinner is-active" style="float:none;"></div></div>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            
            // Function to format numbers with thousand separators
            function formatNumber(n) {
                let numStr = String(n).replace(/[^0-9]/g, '');
                return numStr.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            }

            function initializePriceInputs(selector) {
                $(selector).each(function() {
                    let initialValue = $(this).val();
                    if (initialValue) {
                        $(this).val(formatNumber(initialValue));
                    }
                });
            }
            
            initializePriceInputs('.manli-price-input');

            // Using event delegation for dynamic elements
            $('#maneli-product-list-tbody').on('keyup', '.manli-price-input', function() {
                let formattedValue = formatNumber($(this).val());
                $(this).val(formattedValue);
            });

            $('#maneli-product-list-tbody').on('change', '.manli-data-input', function() {
                const inputField = $(this);
                const productId = inputField.data('product-id');
                const fieldType = inputField.data('field-type');
                let fieldValue = inputField.val();
                
                if (inputField.hasClass('manli-price-input')) {
                    fieldValue = fieldValue.replace(/,/g, '');
                }

                const spinner = inputField.closest('td').find('.spinner');
                if(!spinner.length) {
                     inputField.after('<span class="spinner"></span>');
                }
                
                inputField.next('.spinner').addClass('is-active');
                inputField.css('border-color', '#007cba');

                $.post('<?php echo admin_url("admin-ajax.php"); ?>', {
                    action: 'maneli_update_product_data',
                    nonce: '<?php echo wp_create_nonce("maneli_product_data_nonce"); ?>',
                    product_id: productId,
                    field_type: fieldType,
                    field_value: fieldValue
                }, function(response) {
                    inputField.next('.spinner').removeClass('is-active');
                    if (response.success) {
                        inputField.css('border-color', '#46b450');
                        setTimeout(() => inputField.css('border-color', ''), 1500);
                    } else {
                        inputField.css('border-color', '#dc3232');
                        setTimeout(() => inputField.css('border-color', ''), 2000);
                        console.error('Error:', response.data);
                    }
                });
            });

            // AJAX Search
            var searchTimeout;
            $('#product-search-input').on('keyup', function() {
                clearTimeout(searchTimeout);
                var searchTerm = $(this).val();
                
                searchTimeout = setTimeout(function() {
                    $('#product-list-loader').show();
                    $('#maneli-product-list-tbody').html('');

                    $.ajax({
                        url: '<?php echo admin_url("admin-ajax.php"); ?>',
                        type: 'POST',
                        data: {
                            action: 'maneli_filter_products_ajax',
                            _ajax_nonce: '<?php echo wp_create_nonce("maneli_product_filter_nonce"); ?>',
                            search: searchTerm
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#maneli-product-list-tbody').html(response.data.html);
                                initializePriceInputs('#maneli-product-list-tbody .manli-price-input');
                            }
                        },
                        complete: function() {
                             $('#product-list-loader').hide();
                        }
                    });
                }, 500);
            });

        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Helper function to render a single product row.
     */
    private static function render_product_row($product) {
        $product_id = $product->get_id();
        $regular_price = $product->get_regular_price();
        $min_downpayment = get_post_meta($product_id, 'min_downpayment', true);
        $current_status = get_post_meta($product_id, '_maneli_car_status', true);
        ?>
        <tr>
            <td data-title="نام خودرو">
                <strong><a href="<?php echo get_edit_post_link($product_id); ?>" target="_blank"><?php echo esc_html($product->get_name()); ?></a></strong>
            </td>
            <td data-title="قیمت کامل (تومان)">
                <input type="text"
                       class="manli-data-input manli-price-input"
                       style="width: 100%;"
                       data-product-id="<?php echo esc_attr($product_id); ?>"
                       data-field-type="regular_price"
                       value="<?php echo esc_attr($regular_price); ?>"
                       placeholder="قیمت کامل">
            </td>
            <td data-title="حداقل پیش‌پرداخت (تومان)">
                <input type="text"
                       class="manli-data-input manli-price-input"
                       style="width: 100%;"
                       data-product-id="<?php echo esc_attr($product_id); ?>"
                       data-field-type="min_downpayment"
                       value="<?php echo esc_attr($min_downpayment); ?>"
                       placeholder="مبلغ پیش‌پرداخت">
            </td>
            <td data-title="وضعیت فروش">
                <select class="manli-data-input"
                        style="width: 100%;"
                        data-product-id="<?php echo esc_attr($product_id); ?>"
                        data-field-type="car_status">
                    <option value="special_sale" <?php selected($current_status, 'special_sale'); ?>>فروش ویژه (فعال)</option>
                    <option value="unavailable" <?php selected($current_status, 'unavailable'); ?>>ناموجود (نمایش در سایت)</option>
                    <option value="disabled" <?php selected($current_status, 'disabled'); ?>>غیرفعال (مخفی از سایت)</option>
                </select>
            </td>
        </tr>
        <?php
    }
}