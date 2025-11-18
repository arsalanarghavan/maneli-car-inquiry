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
    
    // باز کردن modal با SweetAlert2 (به جای Bootstrap Modal)
    $(document.body).on('click', '#open-new-cash-inquiry-modal', function(e) {
        e.preventDefault();
        console.log('Button clicked - Opening SweetAlert form');
        
        openCashInquiryForm();
    });
    
    // تابع باز کردن فرم با SweetAlert2
    function openCashInquiryForm() {
        // بارگذاری لیست محصولات
        $.ajax({
            url: maneliCashInquiryForm.ajax_url,
            type: 'POST',
            data: {
                action: 'maneli_get_products_for_cash',
                nonce: maneliCashInquiryForm.nonce
            },
            success: function(response) {
                if (response.success && response.data.products) {
                    showCashInquiryForm(response.data.products);
                } else {
                    Swal.fire({
                        title: 'خطا',
                        text: 'خطا در بارگذاری لیست محصولات',
                        icon: 'error',
                        confirmButtonText: 'باشه'
                    });
                }
            }
        });
    }
    
    // نمایش فرم در SweetAlert2
    function showCashInquiryForm(products) {
        let productOptions = '<option value="">انتخاب کنید...</option>';
        products.forEach(product => {
            productOptions += `<option value="${product.id}" data-price="${product.price}" data-image="${product.image}">${product.name}</option>`;
        });
        
        Swal.fire({
            title: '<i class="la la-dollar-sign me-2"></i> ثبت استعلام نقدی جدید',
            html: `
                <div style="text-align: right;">
                    <!-- انتخاب خودرو -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold d-block mb-2">
                            <i class="la la-car me-1"></i>
                            انتخاب خودرو <span class="text-danger">*</span>
                        </label>
                        <select class="swal2-input" id="swal-product-select" style="width: 100%; padding: 10px;">
                            ${productOptions}
                        </select>
                        
                        <!-- نمایش اطلاعات محصول -->
                        <div id="swal-product-info" style="display: none; margin-top: 15px; padding: 15px; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9;">
                            <div style="display: flex; gap: 15px; align-items: center;">
                                <img id="swal-product-image" src="" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px;">
                                <div style="flex: 1;">
                                    <h6 id="swal-product-name" style="margin: 0 0 8px 0;"></h6>
                                    <p style="margin: 0; color: #0066cc; font-weight: bold;">
                                        قیمت: <span id="swal-product-price"></span> تومان
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- فیلدهای اطلاعات -->
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="text" id="swal-first-name" class="swal2-input" placeholder="نام *" style="width: 100%; margin: 5px 0;">
                        </div>
                        <div class="col-6">
                            <input type="text" id="swal-last-name" class="swal2-input" placeholder="نام خانوادگی *" style="width: 100%; margin: 5px 0;">
                        </div>
                        <div class="col-6">
                            <input type="text" id="swal-mobile" class="swal2-input" placeholder="09XXXXXXXXX *" maxlength="11" style="width: 100%; margin: 5px 0;">
                        </div>
                        <div class="col-6">
                            <input type="text" id="swal-car-color" class="swal2-input" placeholder="رنگ (اختیاری)" style="width: 100%; margin: 5px 0;">
                        </div>
                    </div>
                </div>
            `,
            width: '700px',
            showCancelButton: true,
            confirmButtonText: '<i class="la la-save me-1"></i> ثبت استعلام',
            cancelButtonText: '<i class="la la-times me-1"></i> لغو',
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            didOpen: () => {
                // Event برای نمایش اطلاعات محصول
                $('#swal-product-select').on('change', function() {
                    const selected = $(this).find('option:selected');
                    const productId = selected.val();
                    
                    if (productId) {
                        const price = selected.data('price');
                        const image = selected.data('image');
                        const name = selected.text();
                        
                        $('#swal-product-name').text(name);
                        $('#swal-product-price').text(parseInt(price).toLocaleString('fa-IR').replace(/٬/g, ','));
                        $('#swal-product-image').attr('src', image || '');
                        $('#swal-product-info').slideDown(300);
                    } else {
                        $('#swal-product-info').slideUp(300);
                    }
                });
            },
            preConfirm: () => {
                const productId = $('#swal-product-select').val();
                const firstName = $('#swal-first-name').val().trim();
                const lastName = $('#swal-last-name').val().trim();
                const mobile = $('#swal-mobile').val().trim();
                const carColor = $('#swal-car-color').val().trim();
                
                // Validation
                if (!productId) {
                    Swal.showValidationMessage('لطفاً خودرو را انتخاب کنید');
                    return false;
                }
                
                if (!firstName || !lastName) {
                    Swal.showValidationMessage('لطفاً نام و نام خانوادگی را وارد کنید');
                    return false;
                }
                
                if (!mobile || !/^09[0-9]{9}$/.test(mobile)) {
                    Swal.showValidationMessage('شماره موبایل باید 11 رقمی و با 09 شروع شود');
                    return false;
                }
                
                return {
                    productId,
                    firstName,
                    lastName,
                    mobile,
                    carColor
                };
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                submitCashInquiry(result.value);
            }
        });
    }
    
    // ارسال استعلام
    function submitCashInquiry(data) {
        Swal.fire({
            title: 'در حال ثبت...',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => Swal.showLoading()
        });
        
        // Get CAPTCHA token if enabled
        const ajaxData = {
            action: 'maneli_create_cash_inquiry',
            product_id: data.productId,
            first_name: data.firstName,
            last_name: data.lastName,
            mobile: data.mobile,
            car_color: data.carColor,
            nonce: maneliCashInquiryForm.nonce
        };
        
        // Add CAPTCHA token if available
        if (typeof maneliCaptcha !== 'undefined' && maneliCaptchaConfig && maneliCaptchaConfig.enabled) {
            maneliCaptcha.getToken().then(function(token) {
                if (token) {
                    if (maneliCaptchaConfig.type === 'hcaptcha') {
                        ajaxData['h-captcha-response'] = token;
                    } else if (maneliCaptchaConfig.type === 'recaptcha_v2') {
                        ajaxData['g-recaptcha-response'] = token;
                    } else if (maneliCaptchaConfig.type === 'recaptcha_v3') {
                        ajaxData['captcha_token'] = token;
                    }
                }
                performAjaxRequest(ajaxData);
            }).catch(function() {
                performAjaxRequest(ajaxData);
            });
        } else {
            performAjaxRequest(ajaxData);
        }
        
        function performAjaxRequest(requestData) {
            $.ajax({
                url: maneliCashInquiryForm.ajax_url,
                type: 'POST',
                data: requestData,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'موفق!',
                        text: 'استعلام با موفقیت ثبت شد',
                        icon: 'success',
                        confirmButtonText: 'باشه'
                    }).then(() => {
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
                Swal.fire({
                    title: 'خطا',
                    text: 'خطای سرور رخ داد',
                    icon: 'error',
                    confirmButtonText: 'باشه'
                });
            }
        });
    }
    
    // حذف شد - استفاده از SweetAlert2 به جای Bootstrap Modal
});

