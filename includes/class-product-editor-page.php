<?php
if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Product_Editor_Page {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu_page']);
    }

    /**
     * Adds the custom menu page under the 'Products' menu.
     */
    public function add_admin_menu_page() {
        add_submenu_page(
            'edit.php?post_type=product',
            'قیمت و وضعیت خودروها', // Page Title
            'قیمت و وضعیت خودروها', // Menu Title
            'manage_woocommerce',          // Capability
            'maneli-product-editor',       // Menu Slug
            [$this, 'render_page_html']      // Callback function
        );
    }

    /**
     * Renders the HTML for the product editor page and enqueues the necessary JavaScript.
     */
    public function render_page_html() {
        if (!class_exists('WooCommerce')) {
            echo '<div class="notice notice-error"><p>برای استفاده از این پلاگین، لطفاً ابتدا ووکامرس را نصب و فعال کنید.</p></div>';
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <p>در این صفحه می‌توانید قیمت و وضعیت فروش محصولات را به سرعت تغییر دهید. تغییرات به صورت خودکار ذخیره می‌شوند.</p>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width:35%;"><strong>نام خودرو</strong></th>
                        <th style="width:20%;"><strong>قیمت کامل (تومان)</strong></th>
                        <th style="width:20%;"><strong>حداقل پیش‌پرداخت (تومان)</strong></th>
                        <th style="width:25%;"><strong>وضعیت فروش</strong></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $products = wc_get_products(['limit' => -1, 'orderby' => 'title', 'order' => 'ASC']);
                    if (!empty($products)) {
                        foreach ($products as $product) {
                            $product_id = $product->get_id();
                            $regular_price = $product->get_regular_price();
                            $min_downpayment = get_post_meta($product_id, 'min_downpayment', true);
                            $current_status = get_post_meta($product_id, '_maneli_car_status', true);
                            ?>
                            <tr>
                                <td><strong><a href="<?php echo get_edit_post_link($product_id); ?>" target="_blank"><?php echo esc_html($product->get_name()); ?></a></strong></td>
                                <td>
                                    <input type="text"
                                           class="manli-data-input manli-price-input"
                                           data-product-id="<?php echo esc_attr($product_id); ?>"
                                           data-field-type="regular_price"
                                           value="<?php echo esc_attr($regular_price); ?>"
                                           placeholder="قیمت کامل را وارد کنید">
                                </td>
                                <td>
                                    <input type="text"
                                           class="manli-data-input manli-price-input"
                                           data-product-id="<?php echo esc_attr($product_id); ?>"
                                           data-field-type="min_downpayment"
                                           value="<?php echo esc_attr($min_downpayment); ?>"
                                           placeholder="مبلغ پیش‌پرداخت">
                                </td>
                                <td>
                                    <select class="manli-data-input"
                                            data-product-id="<?php echo esc_attr($product_id); ?>"
                                            data-field-type="car_status">
                                        <option value="special_sale" <?php selected($current_status, 'special_sale'); ?>>فروش ویژه (فعال)</option>
                                        <option value="unavailable" <?php selected($current_status, 'unavailable'); ?>>ناموجود (نمایش در سایت)</option>
                                        <option value="disabled" <?php selected($current_status, 'disabled'); ?>>غیرفعال (مخفی از سایت)</option>
                                    </select>
                                    <span class="spinner"></span>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo '<tr><td colspan="4">هیچ محصولی یافت نشد.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {

            // Function to format numbers with thousand separators
            function formatNumber(n) {
                let numStr = String(n).replace(/[^0-9]/g, '');
                return numStr.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            }

            // Format initial values on page load
            $('.manli-price-input').each(function() {
                let initialValue = $(this).val();
                if (initialValue) {
                    $(this).val(formatNumber(initialValue));
                }
            });

            // Format the number as the user types
            $('.manli-price-input').on('keyup', function() {
                let formattedValue = formatNumber($(this).val());
                $(this).val(formattedValue);
            });

            // AJAX call when user leaves the field (change event)
            $('.manli-data-input').on('change', function() {
                const inputField = $(this);
                const productId = inputField.data('product-id');
                const fieldType = inputField.data('field-type');
                let fieldValue = inputField.val();
                
                // Remove commas for price fields before sending
                if (inputField.hasClass('manli-price-input')) {
                    fieldValue = fieldValue.replace(/,/g, '');
                }

                const spinner = inputField.closest('td').find('.spinner');

                spinner.addClass('is-active');
                inputField.css('border-color', '#007cba'); // Blue for processing

                const data = {
                    action: 'maneli_update_product_data',
                    nonce: '<?php echo wp_create_nonce("maneli_product_data_nonce"); ?>',
                    product_id: productId,
                    field_type: fieldType,
                    field_value: fieldValue
                };

                $.post('<?php echo admin_url("admin-ajax.php"); ?>', data, function(response) {
                    spinner.removeClass('is-active');
                    if (response.success) {
                        inputField.css('border-color', '#46b450'); // Green for success
                        setTimeout(() => inputField.css('border-color', ''), 1500);
                    } else {
                        inputField.css('border-color', '#dc3232'); // Red for error
                        setTimeout(() => inputField.css('border-color', ''), 2000);
                        console.error('Error:', response.data);
                    }
                });
            });
        });
        </script>
        <?php
    }
}