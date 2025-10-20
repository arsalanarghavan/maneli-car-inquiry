<!-- Start::row -->
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">مدیریت محصولات</div>
                <button class="btn btn-primary">
                    <i class="la la-plus me-1"></i>
                    محصول جدید
                </button>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-3">
                        <select class="form-select" id="product-category">
                            <option value="">همه دسته‌بندی‌ها</option>
                            <option value="car">خودرو</option>
                            <option value="motorcycle">موتورسیکلت</option>
                            <option value="truck">کامیون</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="product-status">
                            <option value="">همه وضعیت‌ها</option>
                            <option value="active">فعال</option>
                            <option value="inactive">غیرفعال</option>
                            <option value="out-of-stock">ناموجود</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control" id="product-search" placeholder="جستجوی محصول...">
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100" onclick="filterProducts()">
                            <i class="la la-search me-1"></i> فیلتر
                        </button>
                    </div>
                </div>

                <div class="row" id="products-container">
                    <?php
                    // Get WooCommerce products
                    if (class_exists('WooCommerce')) {
                        $products = wc_get_products([
                            'limit' => 12,
                            'status' => 'publish'
                        ]);

                        foreach ($products as $product) {
                            $image_id = $product->get_image_id();
                            $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : wc_placeholder_img_src();
                            ?>
                            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 product-item" data-category="<?php echo esc_attr($product->get_category_ids() ? implode(',', $product->get_category_ids()) : ''); ?>">
                                <div class="card border border-primary">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($product->get_name()); ?>" class="img-fluid rounded">
                                        </div>
                                        <h6 class="card-title mb-2"><?php echo esc_html($product->get_name()); ?></h6>
                                        <p class="text-muted fs-13 mb-3"><?php echo esc_html(wp_trim_words($product->get_short_description(), 10)); ?></p>
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span class="fw-medium text-primary"><?php echo $product->get_price_html(); ?></span>
                                            <span class="badge <?php echo $product->is_in_stock() ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo $product->is_in_stock() ? 'موجود' : 'ناموجود'; ?>
                                            </span>
                                        </div>
                                        <div class="btn-list">
                                            <a href="<?php echo get_edit_post_link($product->get_id()); ?>" class="btn btn-sm btn-primary-light" target="_blank">
                                                <i class="la la-edit"></i> ویرایش
                                            </a>
                                            <a href="<?php echo get_permalink($product->get_id()); ?>" class="btn btn-sm btn-success-light" target="_blank">
                                                <i class="la la-eye"></i> مشاهده
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        ?>
                        <div class="col-12">
                            <div class="alert alert-warning text-center">
                                <i class="la la-info-circle me-2"></i>
                                ووکامرس نصب نشده است. برای مدیریت محصولات، لطفاً ووکامرس را نصب و فعال کنید.
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End::row -->

<script>
function filterProducts() {
    const category = document.getElementById('product-category').value;
    const status = document.getElementById('product-status').value;
    const search = document.getElementById('product-search').value.toLowerCase();

    const products = document.querySelectorAll('.product-item');

    products.forEach(product => {
        const productName = product.querySelector('.card-title').textContent.toLowerCase();
        const shouldShow = (!search || productName.includes(search));

        if (shouldShow) {
            product.style.display = 'block';
        } else {
            product.style.display = 'none';
        }
    });
}
</script>

