<!-- Start::row -->
<?php
/**
 * Products Management Page
 * Only accessible by Administrators
 */

// Helper function to convert numbers to Persian
if (!function_exists('persian_numbers')) {
    function persian_numbers($str) {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        return str_replace($english, $persian, $str);
    }
}

// Permission check - Only Admin can access
if (!current_user_can('manage_maneli_inquiries')) {
    wp_redirect(home_url('/dashboard'));
    exit;
}

// Load product data
$paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

// Get products - Include all statuses including disabled products for admin
// Note: The hooks check for is_admin() OR current_user_can('manage_maneli_inquiries')
// Since we're in dashboard (frontend), we check user capability, so filters should not run
// But to be safe, we'll use WP_Query directly to get ALL products

// Query products - Get ALL products including disabled ones for admin
// Use WP_Query directly with suppress_filters to bypass all filters
$offset = ($paged - 1) * 50;

$query_args = [
    'post_type' => 'product',
    'post_status' => ['publish', 'draft', 'private', 'pending', 'future'],
    'posts_per_page' => 50,
    'offset' => $offset,
    'orderby' => 'title',
    'order' => 'ASC',
    'no_found_rows' => false,
    'suppress_filters' => true // Bypass all filters to show ALL products
];

if (!empty($search)) {
    $query_args['s'] = $search;
}

$wp_query_products = new WP_Query($query_args);

// Get total count for pagination
$total_products = $wp_query_products->found_posts;
$max_num_pages = $wp_query_products->max_num_pages;

// Convert WP_Post objects to WC_Product objects
$products = [];
if ($wp_query_products->have_posts()) {
    while ($wp_query_products->have_posts()) {
        $wp_query_products->the_post();
        $product = wc_get_product(get_the_ID());
        if ($product && $product instanceof WC_Product) {
            $products[] = $product;
        }
    }
    wp_reset_postdata();
}

// Statistics
$total_products_count = wp_count_posts('product');
$published_count = $total_products_count->publish ?? 0;
$draft_count = $total_products_count->draft ?? 0;
$pending_count = $total_products_count->pending ?? 0;
$private_count = $total_products_count->private ?? 0;

// Get all products (publish + draft)
$all_products = wc_get_products([
    'status' => ['publish', 'draft'],
    'limit' => -1,
]);

$active_count = 0;      // car_status = special_sale (فروش ویژه/فعال)
$on_sale_count = 0;     // car_status = special_sale (فروش ویژه)
$unavailable_count = 0; // car_status = unavailable (ناموجود)
$disabled_count = 0;    // car_status = disabled (غیرفعال)

foreach ($all_products as $product) {
    $product_id = $product->get_id();
    $car_status = get_post_meta($product_id, '_maneli_car_status', true);
    
    // Count by car_status
    switch ($car_status) {
        case 'special_sale':
            $active_count++;      // موجود و فعال
            $on_sale_count++;     // فروش ویژه
            break;
        case 'unavailable':
            $unavailable_count++; // ناموجود
            break;
        case 'disabled':
            $disabled_count++;    // غیرفعال
            break;
        default:
            // اگر وضعیت تنظیم نشده، به عنوان فعال حساب میشه
            if (empty($car_status)) {
                $active_count++;
            }
            break;
    }
}

// Get most viewed products this month
$popular_products_query = new WP_Query([
    'post_type' => 'product',
    'post_status' => 'publish',
    'posts_per_page' => 5,
    'meta_key' => 'total_sales',
    'orderby' => 'meta_value_num',
    'order' => 'DESC'
]);
?>
<div class="main-content app-content">
    <div class="container-fluid">

<div class="row">
    <div class="col-xl-12">
        <!-- آمار محصولات -->
        <div class="row mb-4">
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-primary-transparent">
                                    <i class="la la-car fs-24"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="mb-1">
                                    <span class="text-muted fs-13">مجموع محصولات</span>
                                </div>
                                <h4 class="fw-semibold mb-0"><?php echo persian_numbers(number_format_i18n($published_count)); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-success-transparent">
                                    <i class="la la-check-circle fs-24"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="mb-1">
                                    <span class="text-muted fs-13">فروش ویژه (فعال)</span>
                                </div>
                                <h4 class="fw-semibold mb-0 text-success"><?php echo persian_numbers(number_format_i18n($active_count)); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-danger-transparent">
                                    <i class="la la-times-circle fs-24"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="mb-1">
                                    <span class="text-muted fs-13"><?php esc_html_e('Unavailable', 'maneli-car-inquiry'); ?></span>
                                </div>
                                <h4 class="fw-semibold mb-0 text-danger"><?php echo persian_numbers(number_format_i18n($unavailable_count)); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-secondary-transparent">
                                    <i class="la la-eye-slash fs-24"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="mb-1">
                                    <span class="text-muted fs-13"><?php esc_html_e('Inactive', 'maneli-car-inquiry'); ?></span>
                                </div>
                                <h4 class="fw-semibold mb-0"><?php echo persian_numbers(number_format_i18n($disabled_count)); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">
                    <i class="la la-edit me-2"></i>
                    ویرایش قیمت و وضعیت محصولات
                </div>
                <a href="<?php echo home_url('/dashboard/add-product'); ?>" class="btn btn-primary btn-wave">
                    <i class="la la-plus me-1"></i>
                    محصول جدید
                </a>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <form method="get" action="">
                            <input type="hidden" name="page" value="products">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="la la-search"></i>
                                </span>
                                <input type="search" name="search" id="product-search-input" class="form-control" placeholder="<?php esc_attr_e('Search car name...', 'maneli-car-inquiry'); ?>" value="<?php echo esc_attr($search); ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="la la-search me-1"></i>
                                    <?php esc_html_e('Search', 'maneli-car-inquiry'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table text-nowrap table-bordered">
                        <thead>
                            <tr>
                                <th scope="col"><?php esc_html_e('Product', 'maneli-car-inquiry'); ?></th>
                                <th scope="col"><?php esc_html_e('Category', 'maneli-car-inquiry'); ?></th>
                                <th scope="col"><?php esc_html_e('Cash Price', 'maneli-car-inquiry'); ?></th>
                                <th scope="col"><?php esc_html_e('Installment Price', 'maneli-car-inquiry'); ?></th>
                                <th scope="col"><?php esc_html_e('Minimum Down Payment', 'maneli-car-inquiry'); ?></th>
                                <th scope="col"><?php esc_html_e('Colors', 'maneli-car-inquiry'); ?></th>
                                <th scope="col"><?php esc_html_e('Sale Status', 'maneli-car-inquiry'); ?></th>
                                <th scope="col"><?php esc_html_e('Actions', 'maneli-car-inquiry'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="maneli-product-list-tbody">
                            <?php
                            if (!empty($products)) {
                                foreach ($products as $product) {
                                    if ($product instanceof WC_Product) {
                                        Maneli_Render_Helpers::render_product_editor_row($product);
                                    }
                                }
                            } else {
                                echo '<tr><td colspan="8" class="text-center">
                                    <div class="alert alert-info mb-0">
                                        <i class="la la-info-circle me-2"></i>
                                        محصولی یافت نشد.
                                    </div>
                                </td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <div id="product-list-loader" class="maneli-list-loader">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">در حال بارگذاری...</span>
                    </div>
                </div>

                <?php
                if ($max_num_pages > 1) {
                    $products_per_page = 50;
                    $products_shown = count($products);
                    ?>
                    <div class="card-footer border-top-0">
                        <div class="d-flex align-items-center">
                            <div>
                                نمایش <?php echo persian_numbers(number_format_i18n($products_shown)); ?> مورد <i class="bi bi-arrow-left ms-2 fw-medium"></i>
                            </div>
                            <div class="ms-auto">
                                <nav aria-label="Page navigation" class="pagination-style-4">
                                    <ul class="pagination mb-0">
                                        <?php
                                        // Previous button
                                        $prev_link = $paged > 1 ? add_query_arg('paged', $paged - 1) : 'javascript:void(0);';
                                        $prev_disabled = $paged <= 1 ? ' disabled' : '';
                                        echo '<li class="page-item' . $prev_disabled . '">';
                                        echo '<a class="page-link" href="' . esc_url($prev_link) . '">' . esc_html__('Previous', 'maneli-car-inquiry') . '</a>';
                                        echo '</li>';
                                        
                                        // Page numbers
                                        $range = 2;
                                        $show_all = false;
                                        
                                        for ($i = 1; $i <= $max_num_pages; $i++) {
                                            $current_class = ($i == $paged) ? ' active' : '';
                                            $page_link = add_query_arg('paged', $i);
                                            echo '<li class="page-item' . $current_class . '">';
                                            echo '<a class="page-link" href="' . esc_url($page_link) . '">' . persian_numbers($i) . '</a>';
                                            echo '</li>';
                                        }
                                        
                                        // Next button
                                        $next_link = $paged < $max_num_pages ? add_query_arg('paged', $paged + 1) : 'javascript:void(0);';
                                        $next_disabled = $paged >= $max_num_pages ? ' disabled' : '';
                                        echo '<li class="page-item' . $next_disabled . '">';
                                        echo '<a class="page-link text-primary" href="' . esc_url($next_link) . '">' . esc_html__('Next', 'maneli-car-inquiry') . '</a>';
                                        echo '</li>';
                                        ?>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>
</div>
<!-- End::row -->

<style>
/* استایل‌های مخصوص Product Editor */
.manli-data-input {
    width: 100%;
    padding: 6px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 13px;
    transition: all 0.3s ease;
}

.manli-data-input:focus {
    outline: none;
    border-color: var(--primary-color, #007cba);
    box-shadow: 0 0 0 2px rgba(0, 124, 186, 0.1);
}

.manli-data-input.saving {
    border-color: #f0ad4e;
    background-color: #fff9f0;
}

.manli-data-input.saved {
    border-color: #28a745;
    background-color: #f0fff4;
}

.manli-data-input.error {
    border-color: #dc3545;
    background-color: #fff5f5;
}

.spinner {
    display: inline-block;
    width: 16px;
    height: 16px;
    margin-right: 5px;
    vertical-align: middle;
    opacity: 0;
    transition: opacity 0.3s;
}

.spinner.is-active {
    opacity: 1;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #007cba;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.save-status-icon {
    font-size: 16px;
    line-height: 1;
    transition: opacity 0.3s ease;
}

.manli-data-input.saving ~ .save-status-icon {
    display: none !important;
}

/* Responsive Table */
@media (max-width: 768px) {
    .table-responsive table {
        font-size: 12px;
    }
    
    .manli-data-input {
        font-size: 12px;
        padding: 4px 8px;
    }
    
    .save-status-icon {
        font-size: 14px;
    }
}
</style>

<script>
// Helper functions for Persian number conversion (defined outside jQuery dependency)
// Execute immediately to ensure availability
(function() {
    'use strict';
    
    // Helper functions (available globally)
    if (typeof window.maneliPersianHelpers === 'undefined') {
        window.maneliPersianHelpers = {};
    }
    
    window.maneliPersianHelpers = {
        persianToEnglish: function(str) {
        const persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        const english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        let result = str;
        for (let i = 0; i < 10; i++) {
            result = result.split(persian[i]).join(english[i]);
        }
            return result;
        },
        
        englishToPersian: function(str) {
            const english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            const persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            let result = str;
            for (let i = 0; i < 10; i++) {
                result = result.split(english[i]).join(persian[i]);
            }
            return result;
        },
        
        formatNumberWithSeparators: function(value) {
            if (!value) return '';
            // Convert Persian to English first
            let numStr = window.maneliPersianHelpers.persianToEnglish(String(value));
            // Remove all non-digit characters
            numStr = numStr.replace(/[^\d]/g, '');
            if (!numStr) return '';
            // Add thousand separators
            const formatted = parseInt(numStr, 10).toLocaleString('en-US');
            // Convert to Persian
            return window.maneliPersianHelpers.englishToPersian(formatted);
        },
        
        getRawNumber: function(value) {
            if (!value) return '';
            // Convert Persian to English
            let numStr = window.maneliPersianHelpers.persianToEnglish(String(value));
            // Remove all non-digit characters (commas, spaces, etc.)
            numStr = numStr.replace(/[^\d]/g, '');
            return numStr;
        }
    };
    
    // Verify it was created
    console.log('maneliPersianHelpers initialized:', typeof window.maneliPersianHelpers);
})();

// Wait for jQuery and DOM to be ready
(function() {
    function waitForJQuery(callback) {
        if (typeof jQuery !== 'undefined' && typeof jQuery !== 'null') {
            jQuery(document).ready(callback);
        } else {
            setTimeout(function() {
                waitForJQuery(callback);
            }, 50);
        }
    }
    
    waitForJQuery(function() {
        var $ = jQuery;
        
        console.log('Product editor script initialized');
        
        // Ensure maneliPersianHelpers is available
        if (typeof window.maneliPersianHelpers === 'undefined') {
            console.error('maneliPersianHelpers is not defined!');
            return;
        }
        
        // Helper functions shortcut - use bind to ensure correct context
        var persianToEnglish = function(str) {
            return window.maneliPersianHelpers.persianToEnglish(str);
        };
        var englishToPersian = function(str) {
            return window.maneliPersianHelpers.englishToPersian(str);
        };
        var formatNumberWithSeparators = function(value) {
            return window.maneliPersianHelpers.formatNumberWithSeparators(value);
        };
        var getRawNumber = function(value) {
            return window.maneliPersianHelpers.getRawNumber(value);
        };
        
        // Ensure maneliAdminProductEditor is available
        if (typeof maneliAdminProductEditor === 'undefined') {
            console.warn('maneliAdminProductEditor is undefined, using fallback');
            window.maneliAdminProductEditor = {
                ajaxUrl: '<?php echo esc_js(admin_url("admin-ajax.php")); ?>',
                nonce: '<?php echo esc_js(wp_create_nonce("maneli_product_data_nonce")); ?>'
            };
        } else {
            console.log('maneliAdminProductEditor loaded:', maneliAdminProductEditor);
        }
        
        // Count inputs for debugging
        const priceInputs = $('.manli-price-input').length;
        const otherInputs = $('.manli-data-input:not(.manli-price-input):not(select)').length;
        const statusSelects = $('.manli-data-input[data-field-type="car_status"]').length;
        console.log('Found inputs:', {priceInputs, otherInputs, statusSelects});
        
        // Initialize price inputs with formatted values on page load
        $('.manli-price-input').each(function() {
            const $input = $(this);
            const rawValue = $input.data('raw-value') || $input.attr('data-raw-value');
            if (rawValue && window.maneliPersianHelpers) {
                $input.val(formatNumberWithSeparators(rawValue));
            }
        });
        
        // Handle input events for price fields
        $(document).on('input', '.manli-price-input', function(e) {
            const $input = $(this);
            let value = $input.val();
            
            // Store cursor position before formatting
            const cursorPos = this.selectionStart || 0;
            const originalLength = value.length;
            
            // Convert to raw number, format, and convert to Persian
            const rawNum = getRawNumber(value);
            if (!rawNum) {
                $input.val('');
                return;
            }
            
            const formatted = formatNumberWithSeparators(rawNum);
            
            // Update value
            $input.val(formatted);
            
            // Calculate new cursor position
            // Count commas before cursor in original value
            const textBeforeCursor = value.substring(0, cursorPos);
            const commasBeforeCursor = (textBeforeCursor.match(/,/g) || []).length;
            const digitsBeforeCursor = textBeforeCursor.replace(/[^\d]/g, '').length;
            
            // Find new position in formatted string
            let newCursorPos = 0;
            let digitCount = 0;
            for (let i = 0; i < formatted.length; i++) {
                if (formatted[i] !== '،' && formatted[i] !== ',') {
                    digitCount++;
                    if (digitCount > digitsBeforeCursor) {
                        newCursorPos = i;
                        break;
                    }
                }
                newCursorPos = i + 1;
            }
            
            // Ensure cursor position is within bounds
            if (newCursorPos < 0) newCursorPos = 0;
            if (newCursorPos > formatted.length) newCursorPos = formatted.length;
            
            // Restore cursor position
            this.setSelectionRange(newCursorPos, newCursorPos);
        });
        
        // Helper function to show save status icon
        function showSaveStatus($field, success) {
            const $statusIcon = $field.siblings('.save-status-icon');
            if (success) {
                $statusIcon.text('✅').css('display', 'inline-block').fadeIn();
                setTimeout(function() {
                    $statusIcon.fadeOut(500, function() {
                        $(this).css('display', 'none');
                    });
                }, 2000);
            } else {
                $statusIcon.text('❌').css('display', 'inline-block').fadeIn();
                setTimeout(function() {
                    $statusIcon.fadeOut(500, function() {
                        $(this).css('display', 'none');
                    });
                }, 3000);
            }
        }
        
        // Handle blur event: save to database (raw number) - auto-save
        $(document).on('blur', '.manli-price-input', function() {
            console.log('Price input blur event triggered');
            const $input = $(this);
            const productId = $input.data('product-id');
            const fieldType = $input.data('field-type');
            
            if (!productId || !fieldType) {
                console.warn('Missing productId or fieldType on blur:', {productId, fieldType, input: $input});
                return;
            }
            
            // Get raw number (English, no separators)
            const rawValue = getRawNumber($input.val());
            
            // Don't save if empty
            if (!rawValue) {
                // Restore original value if empty
                const originalRaw = $input.data('raw-value');
                if (originalRaw) {
                    $input.val(formatNumberWithSeparators(originalRaw));
                }
                return;
            }
            
            // Show saving state
            $input.addClass('saving');
            const $spinner = $input.siblings('.spinner');
            const $statusIcon = $input.siblings('.save-status-icon');
            $spinner.addClass('is-active');
            $statusIcon.hide();
            
            // Send AJAX request with raw value
            const ajaxUrl = (typeof maneliAdminProductEditor !== 'undefined' && maneliAdminProductEditor.ajaxUrl) 
                ? maneliAdminProductEditor.ajaxUrl 
                : '<?php echo esc_js(admin_url("admin-ajax.php")); ?>';
            const ajaxNonce = (typeof maneliAdminProductEditor !== 'undefined' && maneliAdminProductEditor.nonce) 
                ? maneliAdminProductEditor.nonce 
                : '<?php echo esc_js(wp_create_nonce("maneli_product_data_nonce")); ?>';
            
            console.log('Saving product:', {productId, fieldType, rawValue, ajaxUrl, hasNonce: !!ajaxNonce});
            
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'maneli_update_product_data',
                    product_id: productId,
                    field_type: fieldType,
                    field_value: rawValue,
                    nonce: ajaxNonce
                },
                success: function(response) {
                    console.log('AJAX Success:', response);
                    if (response.success) {
                        $input.removeClass('saving').addClass('saved');
                        $input.data('raw-value', rawValue);
                        
                        // Update formatted display
                        $input.val(formatNumberWithSeparators(rawValue));
                        
                        showSaveStatus($input, true);
                        
                        setTimeout(function() {
                            $input.removeClass('saved');
                        }, 2000);
                    } else {
                        console.error('AJAX Error Response:', response);
                        $input.removeClass('saving').addClass('error');
                        showSaveStatus($input, false);
                        setTimeout(function() {
                            $input.removeClass('error');
                        }, 3000);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Request Error:', status, error, xhr.responseText);
                    $input.removeClass('saving').addClass('error');
                    showSaveStatus($input, false);
                    setTimeout(function() {
                        $input.removeClass('error');
                    }, 3000);
                },
                complete: function() {
                    $spinner.removeClass('is-active');
                }
            });
        });
        
        // Handle other input fields (non-price) - auto-save on blur
        $(document).on('blur', '.manli-data-input:not(.manli-price-input):not(select)', function() {
            console.log('Non-price input blur event triggered');
            const $input = $(this);
            const productId = $input.data('product-id');
            const fieldType = $input.data('field-type');
            
            if (!productId || !fieldType) {
                console.warn('Missing productId or fieldType:', {productId, fieldType});
                return;
            }
            
            const fieldValue = $input.val();
            
            // Show saving state
            $input.addClass('saving');
            const $spinner = $input.siblings('.spinner');
            const $statusIcon = $input.siblings('.save-status-icon');
            $spinner.addClass('is-active');
            $statusIcon.hide();
            
            // Send AJAX request
            const ajaxUrl = (typeof maneliAdminProductEditor !== 'undefined' && maneliAdminProductEditor.ajaxUrl) 
                ? maneliAdminProductEditor.ajaxUrl 
                : '<?php echo esc_js(admin_url("admin-ajax.php")); ?>';
            const ajaxNonce = (typeof maneliAdminProductEditor !== 'undefined' && maneliAdminProductEditor.nonce) 
                ? maneliAdminProductEditor.nonce 
                : '<?php echo esc_js(wp_create_nonce("maneli_product_data_nonce")); ?>';
            
            console.log('Saving product (non-price):', {productId, fieldType, fieldValue, ajaxUrl, hasNonce: !!ajaxNonce});
            
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'maneli_update_product_data',
                    product_id: productId,
                    field_type: fieldType,
                    field_value: fieldValue,
                    nonce: ajaxNonce
                },
                success: function(response) {
                    console.log('AJAX Success (non-price):', response);
                    if (response.success) {
                        $input.removeClass('saving').addClass('saved');
                        showSaveStatus($input, true);
                        setTimeout(function() {
                            $input.removeClass('saved');
                        }, 2000);
                    } else {
                        console.error('AJAX Error Response (non-price):', response);
                        $input.removeClass('saving').addClass('error');
                        showSaveStatus($input, false);
                        setTimeout(function() {
                            $input.removeClass('error');
                        }, 3000);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Request Error (non-price):', status, error, xhr.responseText);
                    $input.removeClass('saving').addClass('error');
                    showSaveStatus($input, false);
                    setTimeout(function() {
                        $input.removeClass('error');
                    }, 3000);
                },
                complete: function() {
                    $spinner.removeClass('is-active');
                }
            });
        });
        
        // Handle select dropdowns (status) - auto-save on change
        $(document).on('change', '.manli-data-input[data-field-type="car_status"]', function() {
            console.log('Status select change event triggered');
            const $select = $(this);
            const productId = $select.data('product-id');
            const fieldType = $select.data('field-type');
            
            if (!productId || !fieldType) {
                console.warn('Missing productId or fieldType on change:', {productId, fieldType});
                return;
            }
            
            const fieldValue = $select.val();
            
            // Store original value for rollback
            const originalValue = $select.data('original-value') || $select.find('option:first').val();
            $select.data('original-value', fieldValue);
            
            // Show saving state
            $select.addClass('saving');
            const $spinner = $select.siblings('.spinner');
            const $statusIcon = $select.siblings('.save-status-icon');
            $spinner.addClass('is-active');
            $statusIcon.hide();
            
            // Send AJAX request
            const ajaxUrl = (typeof maneliAdminProductEditor !== 'undefined' && maneliAdminProductEditor.ajaxUrl) 
                ? maneliAdminProductEditor.ajaxUrl 
                : '<?php echo esc_js(admin_url("admin-ajax.php")); ?>';
            const ajaxNonce = (typeof maneliAdminProductEditor !== 'undefined' && maneliAdminProductEditor.nonce) 
                ? maneliAdminProductEditor.nonce 
                : '<?php echo esc_js(wp_create_nonce("maneli_product_data_nonce")); ?>';
            
            console.log('Saving product (status):', {productId, fieldType, fieldValue, ajaxUrl, hasNonce: !!ajaxNonce});
            
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'maneli_update_product_data',
                    product_id: productId,
                    field_type: fieldType,
                    field_value: fieldValue,
                    nonce: ajaxNonce
                },
                success: function(response) {
                    console.log('AJAX Success (status):', response);
                    if (response.success) {
                        $select.removeClass('saving').addClass('saved');
                        showSaveStatus($select, true);
                        setTimeout(function() {
                            $select.removeClass('saved');
                        }, 2000);
                    } else {
                        console.error('AJAX Error Response (status):', response);
                        $select.removeClass('saving').addClass('error');
                        showSaveStatus($select, false);
                        // Rollback to original value on error
                        $select.val(originalValue);
                        setTimeout(function() {
                            $select.removeClass('error');
                        }, 3000);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Request Error (status):', status, error, xhr.responseText);
                    $select.removeClass('saving').addClass('error');
                    showSaveStatus($select, false);
                    // Rollback to original value on error
                    $select.val(originalValue);
                    setTimeout(function() {
                        $select.removeClass('error');
                    }, 3000);
                },
                complete: function() {
                    $spinner.removeClass('is-active');
                }
            });
        }); // End of status dropdown change handler
        
    }); // End of document.ready callback
})(); // End of waitForJQuery function
</script>
</div>
</div>
