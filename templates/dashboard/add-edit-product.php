<?php
/**
 * Template for adding/editing products
 * 
 * @package Maneli_Car_Inquiry
 */

if (!defined('ABSPATH')) {
    exit;
}

// Helper function for Persian numbers
if (!function_exists('persian_numbers')) {
    function persian_numbers($str) {
        $english = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
        $persian = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
        return str_replace($english, $persian, $str);
    }
}

// Get product ID from query var
$product_id = isset($_GET['edit_product']) ? intval($_GET['edit_product']) : (get_query_var('edit_product') ? intval(get_query_var('edit_product')) : 0);
$is_edit_mode = $product_id > 0;
$page_title = $is_edit_mode ? esc_html__('Edit Product', 'maneli-car-inquiry') : esc_html__('Add New Product', 'maneli-car-inquiry');

// Initialize product data
$product_name = '';
$product_description = '';
$year = '';
$brand = '';
$car_status = 'special_sale';
$features = [];
$colors_array = [];
$regular_price = 0;
$installment_price = 0;
$min_downpayment = 0;
$selected_categories = [];
$featured_image_id = 0;
$gallery_image_ids = [];

// Get product categories
$product_categories = get_terms([
    'taxonomy' => 'product_cat',
    'hide_empty' => false,
]);

// If editing, load product data
if ($is_edit_mode) {
    $product = wc_get_product($product_id);
    if ($product) {
        $product_name = $product->get_name();
        $product_description = $product->get_description();
        $year = get_post_meta($product_id, '_maneli_car_year', true);
        $brand = get_post_meta($product_id, '_maneli_car_brand', true);
        $car_status = get_post_meta($product_id, '_maneli_car_status', true) ?: 'special_sale';
        $regular_price = floatval($product->get_regular_price());
        $installment_price = floatval(get_post_meta($product_id, 'installment_price', true));
        $min_downpayment = floatval(get_post_meta($product_id, 'min_downpayment', true));
        
        $features_str = get_post_meta($product_id, '_maneli_car_features', true);
        $features = !empty($features_str) ? explode(',', $features_str) : [];
        
        $colors_str = get_post_meta($product_id, '_maneli_car_colors', true);
        $colors_array = !empty($colors_str) ? explode(',', $colors_str) : [];
        
        $terms = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);
        $selected_categories = $terms ? $terms : [];
        
        $featured_image_id = $product->get_image_id();
        $gallery_image_ids = $product->get_gallery_image_ids();
    }
}

// Format prices for display with Persian numbers
$regular_price_formatted = $regular_price > 0 ? persian_numbers(number_format($regular_price, 0, '.', ',')) : '';
$installment_price_formatted = $installment_price > 0 ? persian_numbers(number_format($installment_price, 0, '.', ',')) : '';
$min_downpayment_formatted = $min_downpayment > 0 ? persian_numbers(number_format($min_downpayment, 0, '.', ',')) : '';
?>

<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between page-header-breadcrumb flex-wrap gap-2 mb-4">
            <div>
                <nav>
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="<?php echo esc_url(home_url('/dashboard')); ?>"><?php esc_html_e('Pages', 'maneli-car-inquiry'); ?></a></li>
                        <li class="breadcrumb-item"><a href="<?php echo esc_url(home_url('/dashboard/products')); ?>"><?php esc_html_e('Products', 'maneli-car-inquiry'); ?></a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo esc_html($page_title); ?></li>
                    </ol>
                </nav>
                <h1 class="page-title fw-medium fs-18 mb-0"><?php echo esc_html($page_title); ?></h1>
            </div>
            <div>
                <a href="<?php echo esc_url(home_url('/dashboard/products')); ?>" class="btn btn-primary btn-wave">
                    <i class="la la-arrow-right me-1"></i>
                    <?php esc_html_e('Back to List', 'maneli-car-inquiry'); ?>
                </a>
            </div>
        </div>
        <!-- Page Header Close -->

        <!-- Start::row -->
        <div class="row gap-3 justify-content-center">
            <div class="col-xl-9">
                <div class="card custom-card">
                    <div class="card-body">
                        <form id="add-edit-product-form" method="post">
                            <?php wp_nonce_field('maneli_save_product', 'maneli_product_nonce'); ?>
                            <input type="hidden" name="product_id" value="<?php echo esc_attr($product_id); ?>">
                            <input type="hidden" name="action" value="maneli_save_product">
                            
                            <div class="row gy-3">
                                <!-- Product Name -->
                                <div class="col-12">
                                    <label for="product-name" class="form-label"><?php esc_html_e('Car Name', 'maneli-car-inquiry'); ?> <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="product-name" name="product_name" 
                                           value="<?php echo esc_attr($product_name); ?>" placeholder="<?php esc_attr_e('Car Name', 'maneli-car-inquiry'); ?>" required>
                                    <div class="form-text text-muted mt-1">
                                        <i class="la la-info-circle me-1"></i>
                                        <?php esc_html_e('Product name should not exceed 30 characters.', 'maneli-car-inquiry'); ?>
                                    </div>
                                </div>
                                
                                <!-- Year -->
                                <div class="col-xl-6 col-lg-6">
                                    <label for="product-year" class="form-label"><?php esc_html_e('Manufacturing Year', 'maneli-car-inquiry'); ?></label>
                                    <input type="text" class="form-control" id="product-year" name="product_year" 
                                           value="<?php echo esc_attr($year); ?>" placeholder="<?php esc_attr_e('Manufacturing Year', 'maneli-car-inquiry'); ?>">
                                </div>
                                
                                <!-- Brand -->
                                <div class="col-xl-6 col-lg-6">
                                    <label for="product-brand" class="form-label"><?php esc_html_e('Brand', 'maneli-car-inquiry'); ?></label>
                                    <select class="form-control" data-trigger="" name="product_brand" id="product-brand">
                                        <option value=""><?php esc_html_e('Select', 'maneli-car-inquiry'); ?></option>
                                        <?php
                                        // Get brand terms (assuming pa_brand is a product attribute)
                                        $brands = get_terms([
                                            'taxonomy' => 'pa_brand',
                                            'hide_empty' => false,
                                        ]);
                                        foreach ($brands as $brand_term) {
                                            $selected = ($brand === $brand_term->slug) ? 'selected' : '';
                                            echo '<option value="' . esc_attr($brand_term->slug) . '" ' . $selected . '>' . esc_html($brand_term->name) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <!-- Category -->
                                <div class="col-xl-6 col-lg-6">
                                    <label for="product-category" class="form-label"><?php esc_html_e('Category', 'maneli-car-inquiry'); ?></label>
                                    <select class="form-control" data-trigger="" name="product_category[]" id="product-category" multiple>
                                        <?php
                                        foreach ($product_categories as $category) {
                                            $selected = in_array($category->term_id, $selected_categories) ? 'selected' : '';
                                            echo '<option value="' . esc_attr($category->term_id) . '" ' . $selected . '>' . esc_html($category->name) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <!-- Sales Status -->
                                <div class="col-xl-6 col-lg-6">
                                    <label for="product-status" class="form-label"><?php esc_html_e('Sales Status', 'maneli-car-inquiry'); ?></label>
                                    <select class="form-control" data-trigger="" name="product_status" id="product-status">
                                        <option value="special_sale" <?php selected($car_status, 'special_sale'); ?>><?php esc_html_e('Special Sale', 'maneli-car-inquiry'); ?></option>
                                        <option value="unavailable" <?php selected($car_status, 'unavailable'); ?>><?php esc_html_e('Unavailable', 'maneli-car-inquiry'); ?></option>
                                        <option value="disabled" <?php selected($car_status, 'disabled'); ?>><?php esc_html_e('Disabled', 'maneli-car-inquiry'); ?></option>
                                    </select>
                                </div>
                                
                                <!-- Colors (Dynamic Fields) -->
                                <div class="col-xl-6 col-lg-6">
                                    <label class="form-label"><?php esc_html_e('Colors', 'maneli-car-inquiry'); ?></label>
                                    <div id="product-colors-container">
                                        <?php
                                        if (!empty($colors_array)) {
                                            foreach ($colors_array as $index => $color) {
                                                ?>
                                                <div class="d-flex mb-2 color-field-row">
                                                    <input type="text" class="form-control me-2" name="product_colors[]" 
                                                           value="<?php echo esc_attr($color); ?>" placeholder="<?php esc_attr_e('Color', 'maneli-car-inquiry'); ?>">
                                                    <button type="button" class="btn btn-danger btn-sm remove-color-field">
                                                        <i class="bi bi-dash-lg"></i>
                                                    </button>
                                                </div>
                                                <?php
                                            }
                                        } else {
                                            ?>
                                            <div class="d-flex mb-2 color-field-row">
                                                <input type="text" class="form-control me-2" name="product_colors[]" placeholder="<?php esc_attr_e('Color', 'maneli-car-inquiry'); ?>">
                                                <button type="button" class="btn btn-danger btn-sm remove-color-field">
                                                    <i class="bi bi-dash-lg"></i>
                                                </button>
                                            </div>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                    <button type="button" class="btn btn-primary btn-sm mt-2" id="add-color-field">
                                        <i class="bi bi-plus-lg me-1"></i><?php esc_html_e('Add Color', 'maneli-car-inquiry'); ?>
                                    </button>
                                </div>
                                
                                <!-- Prices -->
                                <div class="col-xl-4 col-lg-4 col-md-6">
                                    <label for="product-regular-price" class="form-label"><?php esc_html_e('Cash Price', 'maneli-car-inquiry'); ?></label>
                                    <input type="text" class="form-control manli-price-input" id="product-regular-price" 
                                           name="product_regular_price" value="<?php echo esc_attr($regular_price_formatted); ?>" 
                                           placeholder="<?php esc_attr_e('Cash Price', 'maneli-car-inquiry'); ?>" data-raw-value="<?php echo ($regular_price > 0) ? esc_attr($regular_price) : ''; ?>">
                                </div>
                                
                                <div class="col-xl-4 col-lg-4 col-md-6">
                                    <label for="product-installment-price" class="form-label"><?php esc_html_e('Installment Price', 'maneli-car-inquiry'); ?></label>
                                    <input type="text" class="form-control manli-price-input" id="product-installment-price" 
                                           name="product_installment_price" value="<?php echo esc_attr($installment_price_formatted); ?>" 
                                           placeholder="<?php esc_attr_e('Installment Price', 'maneli-car-inquiry'); ?>" data-raw-value="<?php echo ($installment_price > 0) ? esc_attr($installment_price) : ''; ?>">
                                </div>
                                
                                <div class="col-xl-4 col-lg-4 col-md-6">
                                    <label for="product-min-downpayment" class="form-label"><?php esc_html_e('Minimum Down Payment', 'maneli-car-inquiry'); ?></label>
                                    <input type="text" class="form-control manli-price-input" id="product-min-downpayment" 
                                           name="product_min_downpayment" value="<?php echo esc_attr($min_downpayment_formatted); ?>" 
                                           placeholder="<?php esc_attr_e('Minimum Down Payment', 'maneli-car-inquiry'); ?>" data-raw-value="<?php echo ($min_downpayment > 0) ? esc_attr($min_downpayment) : ''; ?>">
                                </div>
                                
                                <!-- Description -->
                                <div class="col-12">
                                    <label for="product-description" class="form-label"><?php esc_html_e('Product Description', 'maneli-car-inquiry'); ?></label>
                                    <textarea class="form-control" id="product-description" name="product_description" rows="4"><?php echo esc_textarea($product_description); ?></textarea>
                                    <div class="form-text text-muted mt-1">
                                        <i class="la la-info-circle me-1"></i>
                                        <?php esc_html_e('Description should not exceed 500 characters.', 'maneli-car-inquiry'); ?>
                                    </div>
                                </div>
                                
                                <!-- Product Features (Dynamic Fields) -->
                                <div class="col-12">
                                    <label class="form-label"><?php esc_html_e('Car Features', 'maneli-car-inquiry'); ?></label>
                                    <div id="product-features-container">
                                        <?php
                                        if (!empty($features)) {
                                            foreach ($features as $index => $feature) {
                                                ?>
                                                <div class="d-flex mb-2 feature-field-row">
                                                    <input type="text" class="form-control me-2" name="product_features[]" 
                                                           value="<?php echo esc_attr($feature); ?>" placeholder="<?php esc_attr_e('Feature', 'maneli-car-inquiry'); ?>">
                                                    <button type="button" class="btn btn-danger btn-sm remove-feature-field">
                                                        <i class="bi bi-dash-lg"></i>
                                                    </button>
                                                </div>
                                                <?php
                                            }
                                        } else {
                                            ?>
                                            <div class="d-flex mb-2 feature-field-row">
                                                <input type="text" class="form-control me-2" name="product_features[]" placeholder="<?php esc_attr_e('Feature', 'maneli-car-inquiry'); ?>">
                                                <button type="button" class="btn btn-danger btn-sm remove-feature-field">
                                                    <i class="bi bi-dash-lg"></i>
                                                </button>
                                            </div>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                    <button type="button" class="btn btn-primary btn-sm mt-2" id="add-feature-field">
                                        <i class="bi bi-plus-lg me-1"></i><?php esc_html_e('Add Feature', 'maneli-car-inquiry'); ?>
                                    </button>
                                </div>
                                
                                <!-- Product Images -->
                                <div class="col-12">
                                    <label class="form-label mb-3"><?php esc_html_e('Product Images', 'maneli-car-inquiry'); ?></label>
                                    
                                    <!-- Cover Image -->
                                    <div class="mb-4">
                                        <label class="form-label d-block mb-2"><?php esc_html_e('Cover Image', 'maneli-car-inquiry'); ?></label>
                                        <div id="cover-image-upload" class="image-upload-container">
                                            <?php
                                            if ($featured_image_id) {
                                                $cover_url = wp_get_attachment_image_url($featured_image_id, 'medium');
                                                echo '<div class="uploaded-image-wrapper mb-2 d-inline-block">';
                                                echo '<img src="' . esc_url($cover_url) . '" class="uploaded-image me-2 maneli-img-responsive">';
                                                echo '<button type="button" class="btn btn-sm btn-danger remove-image" data-image-id="' . esc_attr($featured_image_id) . '">' . esc_html__('Delete', 'maneli-car-inquiry') . '</button>';
                                                echo '</div>';
                                                echo '<input type="hidden" name="featured_image_id" id="featured_image_id" value="' . esc_attr($featured_image_id) . '">';
                                            } else {
                                                echo '<input type="hidden" name="featured_image_id" id="featured_image_id" value="0">';
                                            }
                                            ?>
                                            <button type="button" class="btn btn-outline-primary btn-sm" id="upload-cover-image">
                                                <i class="bi bi-cloud-upload me-1"></i><?php esc_html_e('Select Cover Image', 'maneli-car-inquiry'); ?>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Gallery Images -->
                                    <div>
                                        <label class="form-label d-block mb-2"><?php esc_html_e('Image Gallery', 'maneli-car-inquiry'); ?></label>
                                        <div id="gallery-images-upload" class="image-upload-container">
                                            <?php
                                            if (!empty($gallery_image_ids)) {
                                                echo '<div class="gallery-images-wrapper mb-2 d-flex flex-wrap gap-2">';
                                                foreach ($gallery_image_ids as $gallery_id) {
                                                    $gallery_url = wp_get_attachment_image_url($gallery_id, 'thumbnail');
                                                    echo '<div class="gallery-image-item position-relative">';
                                                    echo '<img src="' . esc_url($gallery_url) . '" class="uploaded-image maneli-img-responsive-sm">';
                                                    echo '<button type="button" class="btn btn-sm btn-danger remove-gallery-image position-absolute top-0 end-0 maneli-remove-image-btn" data-image-id="' . esc_attr($gallery_id) . '">×</button>';
                                                    echo '</div>';
                                                }
                                                echo '</div>';
                                            }
                                            ?>
                                            <input type="hidden" name="gallery_image_ids" id="gallery-image-ids" value="<?php echo esc_attr(implode(',', $gallery_image_ids)); ?>">
                                            <button type="button" class="btn btn-outline-primary btn-sm" id="upload-gallery-images">
                                                <i class="bi bi-images me-1"></i><?php esc_html_e('Add Image to Gallery', 'maneli-car-inquiry'); ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Card Footer -->
                            <div class="card-footer border-top border-block-start-dashed d-sm-flex justify-content-end mt-4">
                                <a href="<?php echo home_url('/dashboard/products'); ?>" class="btn btn-light me-2 mb-2 mb-sm-0">
                                    <i class="bi bi-x-lg me-1"></i><?php esc_html_e('Cancel', 'maneli-car-inquiry'); ?>
                                </a>
                                <button type="submit" class="btn btn-primary mb-2 mb-sm-0">
                                    <i class="bi bi-download me-2"></i><?php echo $is_edit_mode ? esc_html__('Save Changes', 'maneli-car-inquiry') : esc_html__('Save Product', 'maneli-car-inquiry'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- End::row -->
    </div>
</div>

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
            if (!str) return '';
            const persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            const english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            let result = String(str);
            for (let i = 0; i < 10; i++) {
                result = result.split(persian[i]).join(english[i]);
            }
            return result;
        },
        
        englishToPersian: function(str) {
            if (!str) return '';
            const english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            const persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            let result = String(str);
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
            if (!value || value.trim() === '') return '';
            // Convert Persian to English
            let numStr = window.maneliPersianHelpers.persianToEnglish(String(value));
            // Remove all non-digit characters (commas, spaces, etc.)
            numStr = numStr.replace(/[^\d]/g, '');
            return numStr || '';
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
        'use strict';
        
        // Helper functions shortcuts
        function persianToEnglish(str) {
            return window.maneliPersianHelpers ? window.maneliPersianHelpers.persianToEnglish(str) : str;
        }
        
        function englishToPersian(str) {
            return window.maneliPersianHelpers ? window.maneliPersianHelpers.englishToPersian(str) : str;
        }
        
        function formatNumberWithSeparators(value) {
            return window.maneliPersianHelpers ? window.maneliPersianHelpers.formatNumberWithSeparators(value) : value;
        }
        
        function getRawNumber(value) {
            return window.maneliPersianHelpers ? window.maneliPersianHelpers.getRawNumber(value) : value;
        }
        
        console.log('Add/Edit product script initialized');
        
        // Ensure maneliPersianHelpers is available
        if (typeof window.maneliPersianHelpers === 'undefined') {
            console.error('maneliPersianHelpers is not defined!');
            return;
        }
            // Check if required libraries are loaded
            if (typeof Choices === 'undefined') {
                console.error('Choices.js is not loaded');
            }
            if (typeof Swal === 'undefined') {
                console.error('SweetAlert2 is not loaded');
            }
            if (typeof maneliAdminProductEditor === 'undefined') {
                console.warn('maneliAdminProductEditor is not defined, using fallback');
                // Fallback
                window.maneliAdminProductEditor = {
                    ajaxUrl: '<?php echo esc_js(admin_url("admin-ajax.php")); ?>',
                    productsUrl: '<?php echo esc_js(home_url("/dashboard/products")); ?>',
                    nonce: '<?php echo esc_js(wp_create_nonce("maneli_product_data_nonce")); ?>'
                };
                // Translation object
                window.maneliProductTranslations = {
                    error: <?php echo wp_json_encode(esc_html__('Error!', 'maneli-car-inquiry')); ?>,
                    serverConfigError: <?php echo wp_json_encode(esc_html__('Server configuration not available. Please refresh the page.', 'maneli-car-inquiry')); ?>,
                    fileSizeError: <?php echo wp_json_encode(esc_html__('File size should not exceed 5MB.', 'maneli-car-inquiry')); ?>,
                    uploading: <?php echo wp_json_encode(esc_html__('Uploading...', 'maneli-car-inquiry')); ?>,
                    uploadSuccess: <?php echo wp_json_encode(esc_html__('Image uploaded successfully.', 'maneli-car-inquiry')); ?>,
                    uploadError: <?php echo wp_json_encode(esc_html__('Error uploading image. Please try again.', 'maneli-car-inquiry')); ?>,
                    warning: <?php echo wp_json_encode(esc_html__('Warning', 'maneli-car-inquiry')); ?>,
                    fileTooLarge: <?php echo wp_json_encode(esc_html__('File %s is larger than 5MB and will be ignored.', 'maneli-car-inquiry')); ?>,
                    noValidFiles: <?php echo wp_json_encode(esc_html__('No valid files selected.', 'maneli-car-inquiry')); ?>,
                    uploadingImages: <?php echo wp_json_encode(esc_html__('Uploading %d images...', 'maneli-car-inquiry')); ?>,
                    uploadImagesSuccess: <?php echo wp_json_encode(esc_html__('%d images uploaded successfully%s.', 'maneli-car-inquiry')); ?>,
                    uploadImagesFailed: <?php echo wp_json_encode(esc_html__('Failed to upload images.', 'maneli-car-inquiry')); ?>,
                    enterCarName: <?php echo wp_json_encode(esc_html__('Please enter car name.', 'maneli-car-inquiry')); ?>,
                    saving: <?php echo wp_json_encode(esc_html__('Saving...', 'maneli-car-inquiry')); ?>,
                    success: <?php echo wp_json_encode(esc_html__('Success!', 'maneli-car-inquiry')); ?>,
                    productSaved: <?php echo wp_json_encode(esc_html__('Product saved successfully.', 'maneli-car-inquiry')); ?>,
                    saveError: <?php echo wp_json_encode(esc_html__('Error saving product. Please try again.', 'maneli-car-inquiry')); ?>,
                    requestError: <?php echo wp_json_encode(esc_html__('Error sending request. Please try again.', 'maneli-car-inquiry')); ?>,
                    ok: <?php echo wp_json_encode(esc_html__('OK', 'maneli-car-inquiry')); ?>,
                    delete: <?php echo wp_json_encode(esc_html__('Delete', 'maneli-car-inquiry')); ?>,
                    selectCategory: <?php echo wp_json_encode(esc_html__('Select category', 'maneli-car-inquiry')); ?>
                };
            } else {
                console.log('maneliAdminProductEditor loaded:', maneliAdminProductEditor);
            }
            
            // Initialize Choices.js for category select
            if (typeof Choices !== 'undefined' && $('#product-category').length) {
                new Choices('#product-category', {
                    removeItemButton: true,
                    searchEnabled: true,
                    placeholder: true,
                    placeholderValue: maneliProductTranslations.selectCategory
                });
            }
        
        // Initialize price inputs with formatted values on page load
        $('.manli-price-input').each(function() {
            const $input = $(this);
            const rawValue = $input.data('raw-value') || $input.attr('data-raw-value');
            if (rawValue && rawValue !== '0' && rawValue !== 0 && window.maneliPersianHelpers) {
                $input.val(formatNumberWithSeparators(rawValue));
            } else {
                // If price is 0 or empty, leave the input empty
                $input.val('');
                $input.attr('data-raw-value', '');
            }
        });
        
        // Handle input events for price fields - format as user types
        $('.manli-price-input').on('input', function(e) {
            const $input = $(this);
            let value = $input.val();
            
            // Store cursor position before formatting
            const cursorPos = this.selectionStart || 0;
            const originalLength = value.length;
            
            // Convert to raw number, format, and convert to Persian
            const rawNum = getRawNumber(value);
            if (!rawNum || rawNum === '0') {
                $input.val('');
                return;
            }
            
            const formatted = formatNumberWithSeparators(rawNum);
            
            // Update value
            $input.val(formatted);
            $input.attr('data-raw-value', rawNum);
            
            // Calculate new cursor position
            const textBeforeCursor = value.substring(0, cursorPos);
            // Convert to English first to correctly count both Persian and English digits
            const textBeforeCursorEnglish = persianToEnglish(textBeforeCursor);
            const commasBeforeCursor = (textBeforeCursor.match(/[،,]/g) || []).length;
            const digitsBeforeCursor = textBeforeCursorEnglish.replace(/[^\d]/g, '').length;
            
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
        
        // Handle blur event - ensure proper formatting
        $('.manli-price-input').on('blur', function() {
            const $input = $(this);
            let value = $input.val();
            if (!value || value.trim() === '') {
                $input.val('');
                $input.attr('data-raw-value', '');
                return;
            }
            
            const rawValue = getRawNumber(value);
            if (!rawValue || rawValue === '' || rawValue === '0') {
                $input.val('');
                $input.attr('data-raw-value', '');
                return;
            }
            
            const formatted = formatNumberWithSeparators(rawValue);
            $input.val(formatted);
            $input.attr('data-raw-value', rawValue);
        });
        
        // Add/Remove Color Fields
        $('#add-color-field').on('click', function() {
            const newField = `
                <div class="d-flex mb-2 color-field-row">
                    <input type="text" class="form-control me-2" name="product_colors[]" placeholder="<?php esc_attr_e('Color', 'maneli-car-inquiry'); ?>">
                    <button type="button" class="btn btn-danger btn-sm remove-color-field">
                        <i class="bi bi-dash-lg"></i>
                    </button>
                </div>
            `;
            $('#product-colors-container').append(newField);
        });
        
        $(document).on('click', '.remove-color-field', function() {
            if ($('#product-colors-container .color-field-row').length > 1) {
                $(this).closest('.color-field-row').remove();
            }
        });
        
        // Add/Remove Feature Fields
        $('#add-feature-field').on('click', function() {
            const newField = `
                <div class="d-flex mb-2 feature-field-row">
                    <input type="text" class="form-control me-2" name="product_features[]" placeholder="<?php esc_attr_e('Feature', 'maneli-car-inquiry'); ?>">
                    <button type="button" class="btn btn-danger btn-sm remove-feature-field">
                        <i class="bi bi-dash-lg"></i>
                    </button>
                </div>
            `;
            $('#product-features-container').append(newField);
        });
        
        $(document).on('click', '.remove-feature-field', function() {
            if ($('#product-features-container .feature-field-row').length > 1) {
                $(this).closest('.feature-field-row').remove();
            }
        });
        
        // Initialize Quill Editor for description
        if (typeof Quill !== 'undefined' && $('#product-description').length) {
            const quill = new Quill('#product-description', {
                theme: 'snow',
                modules: {
                    toolbar: [
                        ['bold', 'italic', 'underline'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        ['link']
                    ]
                }
            });
            
            quill.on('text-change', function() {
                $('#product-description').val(quill.root.innerHTML);
            });
        }
        
        // Simple image upload using file input - Cover Image
        $('#upload-cover-image').on('click', function(e) {
            e.preventDefault();
            
            if (typeof maneliAdminProductEditor === 'undefined' || !maneliAdminProductEditor.ajaxUrl) {
                Swal.fire({
                    icon: 'error',
                    title: maneliProductTranslations.error,
                    text: maneliProductTranslations.serverConfigError,
                    confirmButtonText: maneliProductTranslations.ok
                });
                return;
            }
            
            const input = $('<input type="file" accept="image/*" style="display:none">');
            input.on('change', function() {
                const file = this.files[0];
                if (!file) return;
                
                // Check file size (max 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    Swal.fire({
                        icon: 'error',
                        title: maneliProductTranslations.error,
                        text: maneliProductTranslations.fileSizeError,
                        confirmButtonText: maneliProductTranslations.ok
                    });
                    return;
                }
                
                // Show loading
                Swal.fire({
                    title: maneliProductTranslations.uploading,
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                const formData = new FormData();
                formData.append('action', 'maneli_upload_image');
                formData.append('image', file);
                formData.append('nonce', maneliAdminProductEditor.nonce || '');
                
                $.ajax({
                    url: maneliAdminProductEditor.ajaxUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        Swal.close();
                        console.log('Image upload response:', response);
                        if (response.success && response.data && response.data.attachment_id) {
                            $('#featured_image_id').val(response.data.attachment_id);
                            if (!$('.uploaded-image-wrapper').length) {
                                $('#cover-image-upload').prepend(
                                    '<div class="uploaded-image-wrapper mb-2 d-inline-block">' +
                                    '<img src="' + response.data.url + '" class="uploaded-image me-2 maneli-img-responsive">' +
                                    '<button type="button" class="btn btn-sm btn-danger remove-image" data-image-id="' + response.data.attachment_id + '">' + maneliProductTranslations.delete + '</button>' +
                                    '</div>'
                                );
                            } else {
                                $('.uploaded-image-wrapper img').attr('src', response.data.url);
                            }
                            Swal.fire({
                                icon: 'success',
                                title: maneliProductTranslations.success,
                                text: maneliProductTranslations.uploadSuccess,
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: maneliProductTranslations.error,
                                text: response.data?.message || maneliProductTranslations.uploadError,
                                confirmButtonText: maneliProductTranslations.ok
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.close();
                        console.error('Image upload error:', status, error, xhr.responseText);
                        Swal.fire({
                            icon: 'error',
                            title: maneliProductTranslations.error,
                            text: maneliProductTranslations.uploadError,
                            confirmButtonText: maneliProductTranslations.ok
                        });
                    }
                });
            });
            input.click();
        });
        
        // Gallery Images Upload
        $('#upload-gallery-images').on('click', function(e) {
            e.preventDefault();
            
            if (typeof maneliAdminProductEditor === 'undefined' || !maneliAdminProductEditor.ajaxUrl) {
                Swal.fire({
                    icon: 'error',
                    title: maneliProductTranslations.error,
                    text: maneliProductTranslations.serverConfigError,
                    confirmButtonText: maneliProductTranslations.ok
                });
                return;
            }
            
            const input = $('<input type="file" accept="image/*" multiple style="display:none">');
            input.on('change', function() {
                const files = this.files;
                if (!files.length) return;
                
                // Validate all files
                let validFiles = [];
                for (let i = 0; i < files.length; i++) {
                    if (files[i].size > 5 * 1024 * 1024) {
                        Swal.fire({
                            icon: 'warning',
                            title: maneliProductTranslations.warning,
                            text: maneliProductTranslations.fileTooLarge.replace('%s', files[i].name),
                            confirmButtonText: maneliProductTranslations.ok
                        });
                    } else {
                        validFiles.push(files[i]);
                    }
                }
                
                if (validFiles.length === 0) {
                    Swal.fire({
                        icon: 'error',
                        title: maneliProductTranslations.error,
                        text: maneliProductTranslations.noValidFiles,
                        confirmButtonText: maneliProductTranslations.ok
                    });
                    return;
                }
                
                if (!$('.gallery-images-wrapper').length) {
                    $('#gallery-images-upload').prepend('<div class="gallery-images-wrapper mb-2 d-flex flex-wrap gap-2"></div>');
                }
                
                let uploadCount = 0;
                let successCount = 0;
                let errorCount = 0;
                
                // Show loading
                Swal.fire({
                    title: maneliProductTranslations.uploadingImages.replace('%d', validFiles.length),
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                function checkComplete() {
                    if (uploadCount === validFiles.length) {
                        Swal.close();
                        if (successCount > 0) {
                            Swal.fire({
                                icon: 'success',
                                title: maneliProductTranslations.success,
                                text: maneliProductTranslations.uploadImagesSuccess.replace('%d', successCount).replace('%s', errorCount > 0 ? ' (' + errorCount + ' ' + <?php echo wp_json_encode(esc_html__('failed', 'maneli-car-inquiry')); ?> + ')' : ''),
                                timer: 3000,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: maneliProductTranslations.error,
                                text: maneliProductTranslations.uploadImagesFailed,
                                confirmButtonText: maneliProductTranslations.ok
                            });
                        }
                    }
                }
                
                for (let i = 0; i < validFiles.length; i++) {
                    const formData = new FormData();
                    formData.append('action', 'maneli_upload_image');
                    formData.append('image', validFiles[i]);
                    formData.append('nonce', maneliAdminProductEditor.nonce || '');
                    
                    $.ajax({
                        url: maneliAdminProductEditor.ajaxUrl,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            uploadCount++;
                            console.log('Gallery image upload response:', response);
                            if (response.success && response.data && response.data.attachment_id) {
                                successCount++;
                                const currentIds = $('#gallery-image-ids').val();
                                const ids = currentIds ? currentIds.split(',').filter(id => id) : [];
                                ids.push(response.data.attachment_id);
                                $('#gallery-image-ids').val(ids.join(','));
                                
                                $('.gallery-images-wrapper').append(
                                    '<div class="gallery-image-item position-relative">' +
                                    '<img src="' + response.data.url + '" class="uploaded-image maneli-img-responsive-sm">' +
                                    '<button type="button" class="btn btn-sm btn-danger remove-gallery-image position-absolute top-0 end-0 maneli-remove-image-btn" data-image-id="' + response.data.attachment_id + '">×</button>' +
                                    '</div>'
                                );
                            } else {
                                errorCount++;
                            }
                            checkComplete();
                        },
                        error: function(xhr, status, error) {
                            uploadCount++;
                            errorCount++;
                            console.error('Gallery image upload error:', status, error, xhr.responseText);
                            checkComplete();
                        }
                    });
                }
            });
            input.click();
        });
        
        // Remove image handlers
        $(document).on('click', '.remove-image', function() {
            $('#featured_image_id').val('');
            $(this).closest('.uploaded-image-wrapper').remove();
        });
        
        $(document).on('click', '.remove-gallery-image', function() {
            const imageId = $(this).data('image-id');
            const currentIds = $('#gallery-image-ids').val().split(',').filter(id => id && id != imageId);
            $('#gallery-image-ids').val(currentIds.join(','));
            $(this).closest('.gallery-image-item').remove();
        });
        
        // Form submission
        $('#add-edit-product-form').on('submit', function(e) {
            e.preventDefault();
            
            // Collect all form data
            const formData = {
                action: 'maneli_save_product_full',
                maneli_product_nonce: $('#maneli_product_nonce').val(),
                product_id: $('input[name="product_id"]').val(),
                product_name: $('#product-name').val(),
                product_description: $('#product-description').val() || (typeof quill !== 'undefined' ? quill.root.innerHTML : ''),
                product_year: $('#product-year').val(),
                product_brand: $('#product-brand').val(),
                product_status: $('#product-status').val(),
                product_category: $('#product-category').val() || [],
                featured_image_id: $('#featured_image_id').val() || 0,
                gallery_image_ids: $('#gallery-image-ids').val() || '',
                product_regular_price: getRawNumber($('#product-regular-price').val()),
                product_installment_price: getRawNumber($('#product-installment-price').val()),
                product_min_downpayment: getRawNumber($('#product-min-downpayment').val()),
                product_colors: [],
                product_features: []
            };
            
            $('input[name="product_colors[]"]').each(function() {
                if ($(this).val().trim()) {
                    formData.product_colors.push($(this).val().trim());
                }
            });
            
            $('input[name="product_features[]"]').each(function() {
                if ($(this).val().trim()) {
                    formData.product_features.push($(this).val().trim());
                }
            });
            
            // Validate required fields
            if (!formData.product_name || formData.product_name.trim() === '') {
                Swal.fire({
                    icon: 'warning',
                    title: maneliProductTranslations.warning,
                    text: maneliProductTranslations.enterCarName,
                    confirmButtonText: maneliProductTranslations.ok
                });
                return false;
            }
            
            // Allow empty prices - send empty string if price is empty or 0
            // Backend will handle empty strings as 0
            if (!formData.product_regular_price || formData.product_regular_price === '0' || formData.product_regular_price === '') {
                formData.product_regular_price = '';
            }
            if (!formData.product_installment_price || formData.product_installment_price === '0' || formData.product_installment_price === '') {
                formData.product_installment_price = '';
            }
            if (!formData.product_min_downpayment || formData.product_min_downpayment === '0' || formData.product_min_downpayment === '') {
                formData.product_min_downpayment = '';
            }
            
            // Show loading
            Swal.fire({
                title: maneliProductTranslations.saving,
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Submit via AJAX
            $.ajax({
                url: maneliAdminProductEditor.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    Swal.close();
                    console.log('Form submission response:', response);
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: maneliProductTranslations.success,
                            text: response.data.message || maneliProductTranslations.productSaved,
                            confirmButtonText: maneliProductTranslations.ok,
                            didClose: () => {
                                window.location.href = maneliAdminProductEditor.productsUrl;
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: maneliProductTranslations.error,
                            text: response.data.message || maneliProductTranslations.saveError,
                            confirmButtonText: maneliProductTranslations.ok
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.close();
                    console.error('AJAX error:', status, error, xhr.responseText);
                    Swal.fire({
                        icon: 'error',
                        title: maneliProductTranslations.error,
                        text: maneliProductTranslations.requestError,
                        confirmButtonText: maneliProductTranslations.ok
                    });
                }
            });
            
            return false;
        });
    }); // End of document.ready callback
})(); // End of waitForJQuery function
</script>
