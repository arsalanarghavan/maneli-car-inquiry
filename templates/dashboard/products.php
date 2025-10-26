<!-- Start::row -->
<?php
/**
 * Products Management Page
 * Only accessible by Administrators
 */

// Permission check - Only Admin can access
if (!current_user_can('manage_maneli_inquiries')) {
    ?>
    <div class="row">
        <div class="col-xl-12">
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="la la-exclamation-triangle me-2"></i>
                <strong>دسترسی محدود!</strong> شما به این صفحه دسترسی ندارید.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    </div>
    <?php
    return;
}

// Load product data
$paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

// Get products
$args = [
    'limit' => 50,
    'page' => $paged,
    'orderby' => 'title',
    'order' => 'ASC',
    'paginate' => true
];

if (!empty($search)) {
    $args['s'] = $search;
}

$products_query = wc_get_products($args);
$products = $products_query->products;
$total_products = $products_query->total;
$max_num_pages = $products_query->max_num_pages;

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
                                <h4 class="fw-semibold mb-0"><?php echo number_format_i18n($published_count); ?></h4>
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
                                <h4 class="fw-semibold mb-0 text-success"><?php echo number_format_i18n($active_count); ?></h4>
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
                                    <span class="text-muted fs-13">ناموجود</span>
                                </div>
                                <h4 class="fw-semibold mb-0 text-danger"><?php echo number_format_i18n($unavailable_count); ?></h4>
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
                                    <span class="text-muted fs-13">غیرفعال</span>
                                </div>
                                <h4 class="fw-semibold mb-0"><?php echo number_format_i18n($disabled_count); ?></h4>
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
                <a href="<?php echo admin_url('post-new.php?post_type=product'); ?>" class="btn btn-primary btn-wave" target="_blank">
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
                                <input type="search" name="search" id="product-search-input" class="form-control" placeholder="جستجوی نام خودرو..." value="<?php echo esc_attr($search); ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="la la-search me-1"></i>
                                    جستجو
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-nowrap">
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
                            if (!empty($products)) {
                                foreach ($products as $product) {
                                    if ($product instanceof WC_Product) {
                                        Maneli_Render_Helpers::render_product_editor_row($product);
                                    }
                                }
                            } else {
                                echo '<tr><td colspan="6" class="text-center">
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

                <div id="product-list-loader" style="display:none; text-align:center; padding: 40px;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">در حال بارگذاری...</span>
                    </div>
                </div>

                <div class="maneli-pagination-wrapper mt-3 text-center">
                    <?php
                    if ($max_num_pages > 1) {
                        echo paginate_links([
                            'base'      => add_query_arg('paged', '%#%'),
                            'format'    => '?paged=%#%',
                            'current'   => $paged,
                            'total'     => $max_num_pages,
                            'prev_text' => '&laquo; قبلی',
                            'next_text' => 'بعدی &raquo;',
                            'type'      => 'plain',
                        ]);
                    }
                    ?>
                </div>
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

/* Responsive Table */
@media (max-width: 768px) {
    .table-responsive table {
        font-size: 12px;
    }
    
    .manli-data-input {
        font-size: 12px;
        padding: 4px 8px;
    }
}
</style>
