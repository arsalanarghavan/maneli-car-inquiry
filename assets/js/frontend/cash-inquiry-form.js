/**
 * Handles the new cash inquiry form modal in the dashboard
 * 
 * @version 1.0.0
 */
jQuery(document).ready(function($) {
    'use strict';
    
    console.log('=== Cash Inquiry Form Script Loaded ===');
    console.log('jQuery:', typeof $);
    console.log('Bootstrap:', typeof bootstrap);
    
    // باز کردن modal
    $(document.body).on('click', '#open-new-cash-inquiry-modal', function(e) {
        e.preventDefault();
        console.log('Button clicked - Opening modal');
        
        // استفاده از Bootstrap 5 API
        const modalElement = document.getElementById('new-cash-inquiry-modal');
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
            console.log('Modal opened');
        } else {
            console.error('Modal element not found!');
        }
    });
    
    // نمایش اطلاعات محصول هنگام انتخاب
    $('#cash-product-select').on('change', function() {
        const selected = $(this).find('option:selected');
        const productId = selected.val();
        
        if (productId) {
            const price = selected.data('price');
            const image = selected.data('image');
            const name = selected.text();
            
            $('#product-name').text(name);
            $('#product-price').text(parseInt(price).toLocaleString('fa-IR'));
            $('#product-image').attr('src', image || '');
            $('#product-info').slideDown();
        } else {
            $('#product-info').slideUp();
        }
    });
    
    // ثبت استعلام جدید
    $('#submit-cash-inquiry-btn').on('click', function() {
        const productId = $('#cash-product-select').val();
        const firstName = $('#cash-first-name').val().trim();
        const lastName = $('#cash-last-name').val().trim();
        const mobile = $('#cash-mobile').val().trim();
        const carColor = $('#cash-car-color').val().trim();
        
        // Validation
        if (!productId) {
            Swal.fire({
                title: 'خطا',
                text: 'لطفاً خودرو را انتخاب کنید',
                icon: 'error',
                confirmButtonText: 'باشه'
            });
            return;
        }
        
        if (!firstName || !lastName) {
            Swal.fire({
                title: 'خطا',
                text: 'لطفاً نام و نام خانوادگی را وارد کنید',
                icon: 'error',
                confirmButtonText: 'باشه'
            });
            return;
        }
        
        if (!mobile || !/^09[0-9]{9}$/.test(mobile)) {
            Swal.fire({
                title: 'خطا',
                text: 'لطفاً شماره موبایل معتبر وارد کنید (09XXXXXXXXX)',
                icon: 'error',
                confirmButtonText: 'باشه'
            });
            return;
        }
        
        // ارسال AJAX
        const button = $(this);
        button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>در حال ثبت...');
        
        $.ajax({
            url: maneliCashInquiryForm.ajax_url,
            type: 'POST',
            data: {
                action: 'maneli_create_cash_inquiry',
                product_id: productId,
                first_name: firstName,
                last_name: lastName,
                mobile: mobile,
                car_color: carColor,
                nonce: maneliCashInquiryForm.nonce
            },
            success: function(response) {
                button.prop('disabled', false).html('<i class="la la-save me-1"></i>ثبت استعلام');
                
                if (response.success) {
                    Swal.fire({
                        title: 'موفق!',
                        text: 'استعلام با موفقیت ثبت شد',
                        icon: 'success',
                        confirmButtonText: 'باشه'
                    }).then(() => {
                        // بستن modal
                        const modalElement = document.getElementById('new-cash-inquiry-modal');
                        const modal = bootstrap.Modal.getInstance(modalElement);
                        if (modal) modal.hide();
                        
                        // reload صفحه
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'خطا',
                        text: response.data.message || 'خطا در ثبت استعلام',
                        icon: 'error',
                        confirmButtonText: 'باشه'
                    });
                }
            },
            error: function() {
                button.prop('disabled', false).html('<i class="la la-save me-1"></i>ثبت استعلام');
                Swal.fire({
                    title: 'خطا',
                    text: 'خطای سرور رخ داد',
                    icon: 'error',
                    confirmButtonText: 'باشه'
                });
            }
        });
    });
});

