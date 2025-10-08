jQuery(document).ready(function($) {
    'use strict';

    // --- Event Delegation for Cash Inquiry Detail Page ---
    // This method is more robust and works even if elements are loaded dynamically.
    $(document.body).on('click', '#edit-cash-inquiry-btn', function() {
        const button = $(this);
        const inquiryId = button.closest('#cash-inquiry-details').data('inquiry-id');
        button.prop('disabled', true).text('...');

        $.post(maneli_inquiry_ajax.ajax_url, {
            action: 'maneli_get_cash_inquiry_details',
            nonce: maneli_inquiry_ajax.cash_details_nonce,
            inquiry_id: inquiryId
        }, function(response) {
            if (response.success) {
                const data = response.data;
                Swal.fire({
                    title: `ویرایش درخواست #${data.id}`,
                    html: `
                        <div style="text-align: right; font-family: inherit;">
                            <input type="text" id="swal-first-name" class="swal2-input" value="${data.customer.first_name}" placeholder="نام">
                            <input type="text" id="swal-last-name" class="swal2-input" value="${data.customer.last_name}" placeholder="نام خانوادگی">
                            <input type="text" id="swal-mobile" class="swal2-input" value="${data.customer.mobile}" placeholder="موبایل">
                            <input type="text" id="swal-color" class="swal2-input" value="${data.car.color}" placeholder="رنگ خودرو">
                        </div>
                    `,
                    confirmButtonText: 'ذخیره تغییرات',
                    showCancelButton: true,
                    cancelButtonText: 'انصراف',
                    preConfirm: () => {
                        return {
                            first_name: document.getElementById('swal-first-name').value,
                            last_name: document.getElementById('swal-last-name').value,
                            mobile: document.getElementById('swal-mobile').value,
                            color: document.getElementById('swal-color').value
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post(maneli_inquiry_ajax.ajax_url, {
                            action: 'maneli_update_cash_inquiry',
                            nonce: maneli_inquiry_ajax.cash_update_nonce,
                            inquiry_id: inquiryId,
                            ...result.value
                        }, function(updateResponse) {
                            if (updateResponse.success) {
                                Swal.fire('موفق', 'اطلاعات با موفقیت به‌روز شد.', 'success').then(() => location.reload());
                            } else {
                                Swal.fire('خطا', updateResponse.data.message, 'error');
                            }
                        });
                    }
                });
            } else {
                Swal.fire('خطا', response.data.message, 'error');
            }
        }).always(function() {
            button.prop('disabled', false).text('ویرایش اطلاعات');
        });
    });

    $(document.body).on('click', '#delete-cash-inquiry-btn', function() {
        const button = $(this);
        const inquiryId = button.closest('#cash-inquiry-details').data('inquiry-id');
        const backUrl = $('.report-back-button-wrapper a').attr('href');

        Swal.fire({
            title: 'آیا از حذف این درخواست اطمینان دارید؟',
            text: "این عمل غیرقابل بازگشت است!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'بله، حذف کن!',
            cancelButtonText: 'انصراف'
        }).then((result) => {
            if (result.isConfirmed) {
                button.prop('disabled', true).text('...');
                $.post(maneli_inquiry_ajax.ajax_url, {
                    action: 'maneli_delete_cash_inquiry',
                    nonce: maneli_inquiry_ajax.cash_delete_nonce,
                    inquiry_id: inquiryId
                }, function(response) {
                    if (response.success) {
                        Swal.fire('حذف شد!', 'درخواست مورد نظر با موفقیت حذف شد.', 'success').then(() => {
                            window.location.href = backUrl;
                        });
                    } else {
                        Swal.fire('خطا', response.data.message, 'error');
                        button.prop('disabled', false).text('حذف درخواست');
                    }
                });
            }
        });
    });

    $(document.body).on('click', '#set-downpayment-btn', function() {
        const button = $(this);
        const inquiryId = button.closest('#cash-inquiry-details').data('inquiry-id');
        
        Swal.fire({
            title: `تعیین پیش‌پرداخت برای #${inquiryId}`,
            html: `<input type="text" id="swal-downpayment-amount" class="swal2-input" placeholder="مبلغ پیش‌پرداخت به تومان">`,
            confirmButtonText: 'تایید و ارسال برای مشتری',
            showCancelButton: true,
            cancelButtonText: 'انصراف',
            preConfirm: () => {
                return document.getElementById('swal-downpayment-amount').value;
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                button.prop('disabled', true).text('...');
                $.post(maneli_inquiry_ajax.ajax_url, {
                    action: 'maneli_set_down_payment',
                    nonce: maneli_inquiry_ajax.cash_set_downpayment_nonce,
                    inquiry_id: inquiryId,
                    status: 'awaiting_payment',
                    amount: result.value,
                    reason: ''
                }, function(response) {
                    if (response.success) {
                        Swal.fire('موفق', 'وضعیت با موفقیت تغییر کرد و برای مشتری ارسال شد.', 'success').then(() => location.reload());
                    } else {
                        Swal.fire('خطا', response.data.message, 'error');
                        button.prop('disabled', false).text('تعیین پیش‌پرداخت');
                    }
                });
            }
        });
    });

    $(document.body).on('click', '#reject-cash-inquiry-btn', function() {
        const button = $(this);
        const inquiryId = button.closest('#cash-inquiry-details').data('inquiry-id');
        
        let reasonOptions = '<option value="">-- یک دلیل انتخاب کنید --</option>';
        if(maneli_inquiry_ajax.cash_rejection_reasons && maneli_inquiry_ajax.cash_rejection_reasons.length > 0) {
            maneli_inquiry_ajax.cash_rejection_reasons.forEach(reason => {
                reasonOptions += `<option value="${reason}">${reason}</option>`;
            });
        }
        reasonOptions += '<option value="custom">دلیل دیگر (دلخواه)</option>';

        Swal.fire({
            title: `رد درخواست #${inquiryId}`,
            html: `
                <div style="text-align: right; font-family: inherit;">
                    <label for="swal-rejection-reason-select" style="display: block; margin-bottom: 10px;">لطفا دلیل رد درخواست را انتخاب کنید:</label>
                    <select id="swal-rejection-reason-select" class="swal2-select" style="width: 100%;">${reasonOptions}</select>
                    <textarea id="swal-rejection-reason-custom" class="swal2-textarea" placeholder="دلیل دلخواه را اینجا بنویسید..." style="display: none; margin-top: 10px;"></textarea>
                </div>
            `,
            confirmButtonText: 'ثبت رد درخواست',
            showCancelButton: true,
            cancelButtonText: 'انصراف',
            didOpen: () => {
                const select = document.getElementById('swal-rejection-reason-select');
                const customText = document.getElementById('swal-rejection-reason-custom');
                select.addEventListener('change', () => {
                    customText.style.display = select.value === 'custom' ? 'block' : 'none';
                });
            },
            preConfirm: () => {
                const select = document.getElementById('swal-rejection-reason-select');
                if (select.value === 'custom') {
                    return document.getElementById('swal-rejection-reason-custom').value;
                }
                return select.value;
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                button.prop('disabled', true).text('...');
                $.post(maneli_inquiry_ajax.ajax_url, {
                    action: 'maneli_set_down_payment',
                    nonce: maneli_inquiry_ajax.cash_set_downpayment_nonce,
                    inquiry_id: inquiryId,
                    status: 'rejected',
                    amount: '',
                    reason: result.value
                }, function(response) {
                    if (response.success) {
                        Swal.fire('موفق', 'درخواست با موفقیت رد شد.', 'success').then(() => location.reload());
                    } else {
                        Swal.fire('خطا', response.data.message, 'error');
                        button.prop('disabled', false).text('رد کردن درخواست');
                    }
                });
            }
        });
    });

    // --- Assign Expert Button (Cash Inquiry) ---
    $(document.body).on('click', '.assign-expert-btn', function() {
        const button = $(this);
        const inquiryId = button.data('inquiry-id');
        const expertOptions = $('#cash-expert-filter').clone().prop('id', 'swal-expert-filter').prepend('<option value="auto">-- انتساب خودکار (گردشی) --</option>').val('auto').get(0).outerHTML;

        Swal.fire({
            title: `ارجاع درخواست نقدی #${inquiryId}`,
            html: `
                <div style="text-align: right; font-family: inherit;">
                    <label for="swal-expert-filter" style="display: block; margin-bottom: 10px;">یک کارشناس را برای این درخواست انتخاب کنید:</label>
                    ${expertOptions}
                </div>
            `,
            confirmButtonText: 'ارجاع بده',
            showCancelButton: true,
            cancelButtonText: 'انصراف',
            didOpen: () => {
                 $('#swal-expert-filter').select2({
                     placeholder: "انتخاب کارشناس",
                     allowClear: false,
                     width: '100%'
                });
            },
            preConfirm: () => {
                return $('#swal-expert-filter').val();
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                button.prop('disabled', true).text('...');
                $.post(maneli_inquiry_ajax.ajax_url, {
                    action: 'maneli_assign_expert_to_cash_inquiry',
                    nonce: maneli_inquiry_ajax.cash_assign_expert_nonce,
                    inquiry_id: inquiryId,
                    expert_id: result.value
                }, function(response) {
                    if (response.success) {
                        Swal.fire('موفق', response.data.message, 'success').then(() => {
                           if (button.closest('#cash-inquiry-details').length > 0) {
                               location.reload();
                           } else { 
                               button.closest('td').text(response.data.expert_name);
                           }
                        });
                    } else {
                        Swal.fire('خطا', response.data.message, 'error');
                        button.prop('disabled', false).text('ارجاع');
                    }
                });
            }
        });
    });

    // --- Assign Expert Button (Installment Inquiry) ---
    $(document.body).on('click', '.assign-expert-inquiry-btn', function() {
        const button = $(this);
        const inquiryId = button.data('inquiry-id');
        // Clone the expert filter from the main inquiry list filters
        const expertOptions = $('#expert-filter').clone().prop('id', 'swal-expert-filter').prepend('<option value="auto">-- انتساب خودکار (گردشی) --</option>').val('auto').get(0).outerHTML;

        Swal.fire({
            title: `ارجاع استعلام #${inquiryId}`,
            html: `
                <div style="text-align: right; font-family: inherit;">
                    <label for="swal-expert-filter" style="display: block; margin-bottom: 10px;">یک کارشناس را برای این استعلام انتخاب کنید:</label>
                    ${expertOptions}
                </div>
            `,
            confirmButtonText: 'ارجاع بده',
            showCancelButton: true,
            cancelButtonText: 'انصراف',
            didOpen: () => {
                 $('#swal-expert-filter').select2({
                     placeholder: "انتخاب کارشناس",
                     allowClear: false,
                     width: '100%'
                });
            },
            preConfirm: () => {
                return $('#swal-expert-filter').val();
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                button.prop('disabled', true).text('...');
                $.post(maneli_inquiry_ajax.ajax_url, {
                    action: 'maneli_assign_expert_to_inquiry',
                    nonce: maneli_inquiry_ajax.inquiry_assign_expert_nonce, // Make sure this nonce is available
                    inquiry_id: inquiryId,
                    expert_id: result.value
                }, function(response) {
                    if (response.success) {
                        Swal.fire('موفق', response.data.message, 'success').then(() => {
                            const row = button.closest('tr');
                            row.find('.expert-cell').text(response.data.expert_name);
                            row.find('.status-cell').text(response.data.new_status);
                        });
                    } else {
                        Swal.fire('خطا', response.data.message, 'error');
                        button.prop('disabled', false).text('ارجاع');
                    }
                });
            }
        });
    });

});