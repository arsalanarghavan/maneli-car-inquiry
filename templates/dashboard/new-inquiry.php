<?php
/**
 * Dashboard New Inquiry Page
 * Create new car inquiry (cash or installment)
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="row">
    <div class="col-12">
        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div class="card-title">
                    ثبت استعلام جدید
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-info" role="alert">
                    <i class="ri-information-line me-2"></i>
                    برای ثبت استعلام خودرو، لطفاً اطلاعات زیر را با دقت تکمیل نمایید.
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
                        </div>

                        <!-- انتخاب محصول -->
                        <div class="col-md-6">
                            <label for="product_id" class="form-label">محصول (خودرو) <span class="text-danger">*</span></label>
                            <select class="form-select" id="product_id" name="product_id" required>
                                <option value="">در حال بارگذاری...</option>
                            </select>
                        </div>

                        <!-- نام -->
                        <div class="col-md-6">
                            <label for="customer_name" class="form-label">نام و نام خانوادگی <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                        </div>

                        <!-- کد ملی -->
                        <div class="col-md-6">
                            <label for="national_code" class="form-label">کد ملی <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="national_code" name="national_code" maxlength="10" required>
                        </div>

                        <!-- شماره تماس -->
                        <div class="col-md-6">
                            <label for="phone" class="form-label">شماره تماس <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="phone" name="phone" maxlength="11" required>
                        </div>

                        <!-- تاریخ تولد -->
                        <div class="col-md-6">
                            <label for="birth_date" class="form-label">تاریخ تولد</label>
                            <input type="text" class="form-control persian-datepicker" id="birth_date" name="birth_date" placeholder="1370/01/01">
                        </div>

                        <!-- آدرس -->
                        <div class="col-12">
                            <label for="address" class="form-label">آدرس</label>
                            <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                        </div>

                        <!-- توضیحات -->
                        <div class="col-12">
                            <label for="notes" class="form-label">توضیحات</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="توضیحات اضافی (اختیاری)"></textarea>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-line me-2"></i>
                            ثبت استعلام
                        </button>
                        <a href="<?php echo home_url('/dashboard/inquiries'); ?>" class="btn btn-light">
                            <i class="ri-arrow-right-line me-2"></i>
                            بازگشت به لیست
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

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

