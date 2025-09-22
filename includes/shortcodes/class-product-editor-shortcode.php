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
     * AJAX handler for filtering/searching products with pagination.
     */
    public function handle_filter_products_ajax() {
        check_ajax_referer('maneli_product_filter_nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'شما دسترسی لازم را ندارید.']);
        }

        $search_term = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
        $paged = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $products_per_page = 20;
        
        $args = [
            'limit' => $products_per_page,
            'page' => $paged,
            'orderby' => 'title',
            'order' => 'ASC',
            'paginate' => true, // Important for getting total counts
        ];

        if (!empty($search_term)) {
            $args['s'] = $search_term;
        }

        $query_result = wc_get_products($args);

        ob_start();
        if (!empty($query_result->products)) {
            foreach ($query_result->products as $product) {
                self::render_product_row($product);
            }
        } else {
            echo '<tr><td colspan="4" style="text-align:center;">هیچ محصولی با این مشخصات یافت نشد.</td></tr>';
        }
        $html = ob_get_clean();

        $pagination_html = paginate_links([
            'base' => '#',
            'format' => '?paged=%#%',
            'current' => $paged,
            'total' => $query_result->max_num_pages,
            'prev_text' => '« قبلی',
            'next_text' => 'بعدی »',
            'type' => 'plain'
        ]);

        wp_send_json_success(['html' => $html, 'pagination_html' => $pagination_html]);
    }

    /**
     * Renders the HTML for the shortcode.
     */
    public function render_shortcode() {
        if (!current_user_can('manage_woocommerce')) {
            return '<div class="maneli-inquiry-wrapper error-box"><p>شما دسترسی لازم برای مشاهده این بخش را ندارید.</p></div>';
        }

        ob_start();
        
        $products_per_page = 20;
        $paged = get_query_var('paged') ? get_query_var('paged') : 1;
        
        $initial_products_query = wc_get_products([
            'limit' => $products_per_page,
            'page' => $paged,
            'orderby' => 'title',
            'order' => 'ASC',
            'paginate' => true,
        ]);
        
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
                    if (!empty($initial_products_query->products)) {
                        foreach ($initial_products_query->products as $product) {
                            self::render_product_row($product);
                        }
                    } else {
                        echo '<tr><td colspan="4" style="text-align:center;">هیچ محصولی یافت نشد.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
            <div id="product-list-loader" style="display:none; text-align:center; padding: 40px;"><div class="spinner is-active" style="float:none;"></div></div>
            
            <div class="maneli-pagination-wrapper" style="margin-top: 20px; text-align: center;">
                 <?php
                    echo paginate_links([
                        'base' => '#',
                        'format' => '?paged=%#%',
                        'current' => $paged,
                        'total' => $initial_products_query->max_num_pages,
                        'prev_text' => '« قبلی',
                        'next_text' => 'بعدی »',
                    ]);
                 ?>
            </div>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            
            function formatNumber(n) {
                let numStr = String(n).replace(/[^0-9]/g, '');
                return numStr.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            }

            function initializePriceInputs(container) {
                $(container).find('.manli-price-input').each(function() {
                    let initialValue = $(this).val();
                    if (initialValue) {
                        $(this).val(formatNumber(initialValue));
                    }
                });
            }
            
            initializePriceInputs(document);

            // --- Event Delegation for Dynamic Elements ---
            $('#maneli-product-list-tbody').on('keyup', '.manli-price-input', function() {
                $(this).val(formatNumber($(this).val()));
            });

            $('#maneli-product-list-tbody').on('change', '.manli-data-input', function() {
                const inputField = $(this);
                const productId = inputField.data('product-id');
                const fieldType = inputField.data('field-type');
                let fieldValue = inputField.val();
                
                if (inputField.hasClass('manli-price-input')) {
                    fieldValue = fieldValue.replace(/,/g, '');
                }

                if (!inputField.next('.spinner').length) {
                     inputField.after('<span class="spinner" style="vertical-align: middle; margin-right: 5px;"></span>');
                }
                
                const spinner = inputField.next('.spinner');
                spinner.addClass('is-active');
                inputField.css('border-color', '#007cba');

                $.post('<?php echo admin_url("admin-ajax.php"); ?>', {
                    action: 'maneli_update_product_data',
                    nonce: '<?php echo wp_create_nonce("maneli_product_data_nonce"); ?>',
                    product_id: productId,
                    field_type: fieldType,
                    field_value: fieldValue
                }, function(response) {
                    spinner.removeClass('is-active');
                    if (response.success) {
                        inputField.css('border-color', '#46b450');
                    } else {
                        inputField.css('border-color', '#dc3232');
                        console.error('Error:', response.data);
                    }
                     setTimeout(() => inputField.css('border-color', ''), 2000);
                });
            });

            // --- AJAX Search and Pagination ---
            var xhr;
            var searchTimeout;
            
            function fetch_products(page = 1, search = '') {
                if (xhr && xhr.readyState !== 4) {
                    xhr.abort();
                }

                $('#product-list-loader').show();
                $('#maneli-product-list-tbody').css('opacity', 0.5);
                $('.maneli-pagination-wrapper').css('opacity', 0.5);

                xhr = $.ajax({
                    url: '<?php echo admin_url("admin-ajax.php"); ?>',
                    type: 'POST',
                    data: {
                        action: 'maneli_filter_products_ajax',
                        _ajax_nonce: '<?php echo wp_create_nonce("maneli_product_filter_nonce"); ?>',
                        search: search,
                        page: page
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#maneli-product-list-tbody').html(response.data.html);
                            $('.maneli-pagination-wrapper').html(response.data.pagination_html);
                            initializePriceInputs('#maneli-product-list-tbody');
                        }
                    },
                    complete: function() {
                        $('#product-list-loader').hide();
                        $('#maneli-product-list-tbody').css('opacity', 1);
                        $('.maneli-pagination-wrapper').css('opacity', 1);
                    }
                });
            }

            $('#product-search-input').on('keyup', function() {
                clearTimeout(searchTimeout);
                var searchTerm = $(this).val();
                searchTimeout = setTimeout(function() {
                    fetch_products(1, searchTerm);
                }, 500);
            });

            // Pagination click handler
            $('.maneli-pagination-wrapper').on('click', 'a.page-numbers', function(e) {
                e.preventDefault();
                var pageUrl = $(this).attr('href');
                var pageNum = 1;
                
                // Extract page number from href (e.g., ?paged=2)
                var matches = pageUrl.match(/paged=(\d+)/);
                if (matches) {
                    pageNum = matches[1];
                } else if (!$(this).hasClass('prev') && !$(this).hasClass('next')) {
                     // Handle case where link is just the number
                    pageNum = $(this).text();
                } else {
                    // For prev/next, we need to determine the current page and adjust
                    let currentPage = parseInt($('.maneli-pagination-wrapper .page-numbers.current').text());
                    if ($(this).hasClass('prev')) {
                        pageNum = Math.max(1, currentPage - 1);
                    } else {
                        pageNum = currentPage + 1;
                    }
                }

                var searchTerm = $('#product-search-input').val();
                fetch_products(pageNum, searchTerm);
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
                <strong><a href="<?php echo get_edit_post_link($product_id); ?>" target="_blank" rel="noopener"><?php echo esc_html($product->get_name()); ?></a></strong>
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