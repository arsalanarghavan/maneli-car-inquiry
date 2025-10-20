<!-- Start::row -->
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">ثبت استعلام جدید</div>
                <div class="btn-list">
                    <a href="<?php echo home_url('/dashboard/inquiries'); ?>" class="btn btn-light">
                        <i class="la la-arrow-right me-1"></i>
                        بازگشت به لیست
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-info border-0" role="alert">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="la la-info-circle fs-20 text-info"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <strong>راهنمایی:</strong> برای ثبت استعلام خودرو، لطفاً اطلاعات زیر را با دقت تکمیل نمایید.
                        </div>
                    </div>
                </div>

                <form id="new-inquiry-form" method="post" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <!-- نوع استعلام -->
                        <div class="col-md-6">
                            <label for="inquiry_type" class="form-label">نوع استعلام <span class="text-danger">*</span></label>
                            <select class="form-select" id="inquiry_type" name="inquiry_type" required>
                                <option value="">انتخاب کنید...</option>
                                <option value="cash">نقدی</option>
                                <option value="installment">اقساطی</option>
                            </select>
                            <div class="invalid-feedback">
                                لطفاً نوع استعلام را انتخاب کنید.
                            </div>
                        </div>

                        <!-- انتخاب محصول -->
                        <div class="col-md-6">
                            <label for="product_id" class="form-label">محصول (خودرو) <span class="text-danger">*</span></label>
                            <select class="form-select" id="product_id" name="product_id" required>
                                <option value="">در حال بارگذاری...</option>
                            </select>
                            <div class="invalid-feedback">
                                لطفاً محصول را انتخاب کنید.
                            </div>
                        </div>

                        <!-- نام -->
                        <div class="col-md-6">
                            <label for="customer_name" class="form-label">نام و نام خانوادگی <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                            <div class="invalid-feedback">
                                لطفاً نام و نام خانوادگی را وارد کنید.
                            </div>
                        </div>

                        <!-- کد ملی -->
                        <div class="col-md-6">
                            <label for="national_code" class="form-label">کد ملی <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="national_code" name="national_code" maxlength="10" pattern="[0-9]{10}" required>
                            <div class="invalid-feedback">
                                لطفاً کد ملی معتبر وارد کنید.
                            </div>
                        </div>

                        <!-- شماره تماس -->
                        <div class="col-md-6">
                            <label for="phone" class="form-label">شماره تماس <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="phone" name="phone" maxlength="11" pattern="09[0-9]{9}" required>
                            <div class="invalid-feedback">
                                لطفاً شماره تماس معتبر وارد کنید.
                            </div>
                        </div>

                        <!-- تاریخ تولد -->
                        <div class="col-md-6">
                            <label for="birth_date" class="form-label">تاریخ تولد</label>
                            <input type="text" class="form-control persian-datepicker" id="birth_date" name="birth_date" placeholder="1370/01/01">
                        </div>

                        <!-- آدرس -->
                        <div class="col-12">
                            <label for="address" class="form-label">آدرس</label>
                            <textarea class="form-control" id="address" name="address" rows="3" placeholder="آدرس کامل مشتری را وارد کنید"></textarea>
                        </div>

                        <!-- توضیحات -->
                        <div class="col-12">
                            <label for="notes" class="form-label">توضیحات</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="توضیحات اضافی (اختیاری)"></textarea>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top">
                        <button type="submit" class="btn btn-primary btn-wave">
                            <i class="la la-save me-2"></i>
                            ثبت استعلام
                        </button>
                        <a href="<?php echo home_url('/dashboard/inquiries'); ?>" class="btn btn-light btn-wave ms-2">
                            <i class="la la-times me-2"></i>
                            انصراف
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- End::row -->

<script>
jQuery(document).ready(function($) {
    // Load products
    $.ajax({
        url: '<?php echo admin_url('admin-ajax.php'); ?>',
        type: 'POST',
        data: {
            action: 'maneli_get_products'
        },
        success: function(response) {
            if (response.success && response.data.products) {
                var html = '<option value="">انتخاب محصول...</option>';
                response.data.products.forEach(function(product) {
                    html += '<option value="' + product.id + '">' + product.name + '</option>';
                });
                $('#product_id').html(html);
            }
        }
    });

    // Initialize Persian Datepicker
    if ($('.persian-datepicker').length) {
        $('.persian-datepicker').persianDatepicker({
            format: 'YYYY/MM/DD',
            calendar: {
                persian: {
                    locale: 'fa'
                }
            }
        });
    }

    // Form submission
    $('#new-inquiry-form').on('submit', function(e) {
        e.preventDefault();
        
        if (!this.checkValidity()) {
            e.stopPropagation();
            $(this).addClass('was-validated');
            return;
        }

        var formData = $(this).serialize();
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: formData + '&action=maneli_create_inquiry&nonce=<?php echo wp_create_nonce('maneli_create_inquiry'); ?>',
            beforeSend: function() {
                Swal.fire({
                    title: 'در حال ارسال...',
                    text: 'لطفاً صبر کنید',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    willOpen: () => {
                        Swal.showLoading();
                    }
                });
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'موفق!',
                        text: response.data.message || 'استعلام با موفقیت ثبت شد.',
                        confirmButtonText: 'مشاهده لیست'
                    }).then(() => {
                        window.location.href = '<?php echo home_url('/dashboard/inquiries'); ?>';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطا!',
                        text: response.data.message || 'خطا در ثبت استعلام'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'خطا!',
                    text: 'خطا در ارتباط با سرور'
                });
            }
        });
    });
});
</script>

