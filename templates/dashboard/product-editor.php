<!-- Start::row -->
<?php
// استفاده از همان لاجیک shortcode قدیمی
if (class_exists('Maneli_Product_Editor_Shortcode')) {
    $shortcode_handler = new Maneli_Product_Editor_Shortcode();
    
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
}
?>

<div class="row">
    <div class="col-xl-12">
        <!-- آمار محصولات -->
        <div class="row mb-4">
            <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-primary-transparent">
                                    <i class="la la-car fs-20"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="mb-0 text-muted">مجموع محصولات</span>
                                </div>
                                <h5 class="fw-semibold mb-0"><?php echo number_format_i18n($published_count); ?></h5>
                            </div>
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
                            <input type="search" id="product-search-input" class="form-control" placeholder="جستجوی نام خودرو...">
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-nowrap" id="maneli-product-list-tbody-table">
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
                        <tbody id="maneli-product-list-tbody">
                            <?php
                            if (!empty($initial_products_query)) {
                                foreach ($initial_products_query as $product) {
                                    // استفاده از همان helper قدیمی
                                    Maneli_Render_Helpers::render_product_editor_row($product);
                                }
                            } else {
                                echo '<tr><td colspan="6" style="text-align:center;">' . esc_html__('محصولی یافت نشد.', 'maneli-car-inquiry') . '</td></tr>';
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

                <div class="maneli-pagination-wrapper mt-3 text-center">
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

