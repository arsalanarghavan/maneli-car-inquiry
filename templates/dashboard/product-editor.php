<!-- Start::row -->
<?php
/**
 * Product Editor Page
 * Only accessible by Administrators
 */

// Permission check - Only Admin can access
if (!current_user_can('manage_autopuzzle_inquiries')) {
    wp_redirect(home_url('/dashboard'));
    exit;
}

// استفاده از همان لاجیک shortcode قدیمی
if (class_exists('Autopuzzle_Product_Editor_Shortcode')) {
    $shortcode_handler = new Autopuzzle_Product_Editor_Shortcode();
    
    // دریافت محصولات با استفاده از متد موجود
    $paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
    $initial_products_query = wc_get_products([
        'limit' => 20,
        'page' => $paged,
        'orderby' => 'title',
        'order' => 'ASC',
        'return' => 'objects'
    ]);
    
    // آمار محصولات
    $total_products = wp_count_posts('product');
    $published_count = $total_products->publish ?? 0;
    
    // محاسبه آمار وضعیت محصولات
    $active_products_count = 0;
    $unavailable_products_count = 0;
    $disabled_products_count = 0;
    
    if (function_exists('wc_get_products')) {
        $active_products = wc_get_products([
            'limit' => -1,
            'status' => 'publish',
            'meta_key' => '_autopuzzle_car_status',
            'meta_value' => 'special_sale',
            'return' => 'ids'
        ]);
        $active_products_count = count($active_products);
        
        $unavailable_products = wc_get_products([
            'limit' => -1,
            'status' => 'publish',
            'meta_key' => '_autopuzzle_car_status',
            'meta_value' => 'unavailable',
            'return' => 'ids'
        ]);
        $unavailable_products_count = count($unavailable_products);
        
        $disabled_products = wc_get_products([
            'limit' => -1,
            'status' => 'publish',
            'meta_key' => '_autopuzzle_car_status',
            'meta_value' => 'disabled',
            'return' => 'ids'
        ]);
        $disabled_products_count = count($disabled_products);
    }
}
?>
<style>
/* Product Editor Statistics Cards - Inline Styles for Immediate Effect */
.card.custom-card.crm-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    border: 1px solid rgba(0, 0, 0, 0.06) !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08) !important;
    position: relative !important;
    overflow: hidden !important;
    border-radius: 0.5rem !important;
    background: #fff !important;
}

.card.custom-card.crm-card::before {
    content: '' !important;
    position: absolute !important;
    top: 0 !important;
    right: 0 !important;
    width: 100% !important;
    height: 100% !important;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0) 100%) !important;
    pointer-events: none !important;
    transition: opacity 0.3s ease !important;
    opacity: 0 !important;
}

.card.custom-card.crm-card:hover {
    transform: translateY(-4px) !important;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12) !important;
    border-color: rgba(0, 0, 0, 0.1) !important;
}

.card.custom-card.crm-card:hover::before {
    opacity: 1 !important;
}

.card.custom-card.crm-card .card-body {
    position: relative !important;
    z-index: 1 !important;
    padding: 1.5rem !important;
}

.card.custom-card.crm-card:hover .p-2 {
    transform: scale(1.1) !important;
}

.card.custom-card.crm-card:hover .avatar {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
}

.card.custom-card.crm-card h4 {
    font-weight: 700 !important;
    letter-spacing: -0.5px !important;
    font-size: 1.75rem !important;
    color: #1f2937 !important;
    transition: color 0.3s ease !important;
}

.card.custom-card.crm-card:hover h4 {
    color: #5e72e4 !important;
}

.card.custom-card.crm-card .border-primary,
.card.custom-card.crm-card .bg-primary {
    background: linear-gradient(135deg, #5e72e4 0%, #7c3aed 100%) !important;
}

.card.custom-card.crm-card .border-success,
.card.custom-card.crm-card .bg-success {
    background: linear-gradient(135deg, #2dce89 0%, #20c997 100%) !important;
}

.card.custom-card.crm-card .border-warning,
.card.custom-card.crm-card .bg-warning {
    background: linear-gradient(135deg, #fb6340 0%, #fbb140 100%) !important;
}

.card.custom-card.crm-card .border-secondary,
.card.custom-card.crm-card .bg-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%) !important;
}
</style>

<div class="main-content app-content">
    <div class="container-fluid">

<div class="row">
    <div class="col-xl-12">
        <!-- آمار محصولات -->
        <div class="row mb-4">
            <!-- کارت مجموع محصولات -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-6 mb-3">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-primary border-opacity-10 bg-primary-transparent rounded-pill">
                                <span class="avatar avatar-md avatar-rounded bg-primary svg-white">
                                    <i class="la la-car fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Total Products', 'autopuzzle'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center"><?php echo number_format_i18n($published_count); ?></h4>
                            <span class="badge bg-primary-transparent rounded-pill fs-11"><?php esc_html_e('All', 'autopuzzle'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- کارت محصولات فعال برای فروش -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-6 mb-3">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-success border-opacity-10 bg-success-transparent rounded-pill">
                                <span class="avatar avatar-md avatar-rounded bg-success svg-white">
                                    <i class="la la-check-circle fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Active for Sale', 'autopuzzle'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center"><?php echo number_format_i18n($active_products_count); ?></h4>
                            <span class="badge bg-success-transparent rounded-pill fs-11"><?php esc_html_e('Available', 'autopuzzle'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- کارت محصولات ناموجود -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-6 mb-3">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-warning border-opacity-10 bg-warning-transparent rounded-pill">
                                <span class="avatar avatar-md avatar-rounded bg-warning svg-white">
                                    <i class="la la-exclamation-triangle fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Unavailable', 'autopuzzle'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center"><?php echo number_format_i18n($unavailable_products_count); ?></h4>
                            <span class="badge bg-warning-transparent rounded-pill fs-11"><?php esc_html_e('Out of Stock', 'autopuzzle'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- کارت محصولات مخفی -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-6 mb-3">
                <div class="card custom-card crm-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="p-2 border border-secondary border-opacity-10 bg-secondary-transparent rounded-pill">
                                <span class="avatar avatar-md avatar-rounded bg-secondary svg-white">
                                    <i class="la la-eye-slash fs-20"></i>
                                </span>
                            </div>
                        </div>
                        <p class="flex-fill text-muted fs-14 mb-1"><?php esc_html_e('Hidden Products', 'autopuzzle'); ?></p>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <h4 class="mb-0 d-flex align-items-center"><?php echo number_format_i18n($disabled_products_count); ?></h4>
                            <span class="badge bg-secondary-transparent rounded-pill fs-11"><?php esc_html_e('Hidden', 'autopuzzle'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">لیست قیمت و وضعیت خودروها</div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="la la-search"></i>
                            </span>
                            <input type="search" id="product-search-input" class="form-control" placeholder="<?php esc_attr_e('Search car name...', 'autopuzzle'); ?>">
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-nowrap" id="autopuzzle-product-list-tbody-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width:25%;">نام خودرو</th>
                                <th style="width:15%;">قیمت نقدی (تومان)</th>
                                <th style="width:15%;">قیمت اقساطی (تومان)</th>
                                <th style="width:15%;">حداقل پیش‌پرداخت (تومان)</th>
                                <th style="width:15%;">رنگ‌های موجود</th>
                                <th style="width:15%;">وضعیت فروش</th>
                            </tr>
                        </thead>
                        <tbody id="autopuzzle-product-list-tbody">
                            <?php
                            if (!empty($initial_products_query)) {
                                foreach ($initial_products_query as $product) {
                                    // استفاده از همان helper قدیمی
                                    Autopuzzle_Render_Helpers::render_product_editor_row($product);
                                }
                            } else {
                                echo '<tr><td colspan="6" style="text-align:center;">' . esc_html__('محصولی یافت نشد.', 'autopuzzle') . '</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <div id="product-list-loader" style="display:none; text-align:center; padding: 40px;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">در حال بارگذاری...</span>
                    </div>
                </div>

                <div class="autopuzzle-pagination-wrapper mt-3 text-center">
                    <?php
                    // محاسبه تعداد کل صفحات
                    $total_products_count = count(wc_get_products(['limit' => -1, 'return' => 'ids']));
                    $max_num_pages = ceil($total_products_count / 20);
                    
                    echo paginate_links([
                        'base'      => add_query_arg('paged', '%#%'),
                        'format'    => '?paged=%#%',
                        'current'   => $paged,
                        'total'     => $max_num_pages,
                        'prev_text' => '&laquo; قبلی',
                        'next_text' => 'بعدی &raquo;',
                        'type'      => 'plain',
                    ]);
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End::row -->

</div>
</div>
