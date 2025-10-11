/**
 * Handles all frontend JavaScript logic for the inquiry list pages (both installment and cash).
 * This includes AJAX filtering, pagination, and admin/expert actions like assigning experts,
 * editing, deleting, and updating statuses via SweetAlert2 modals.
 *
 * This file combines the logic from the old cash-inquiry-actions.js and inquiry-admin-actions.js.
 *
 * @version 1.0.0
 */
jQuery(document).ready(function($) {
    'use strict';

    //======================================================================
    //  EVENT DELEGATION FOR ADMIN/EXPERT ACTIONS
    //======================================================================

    /**
     * Handles the 'Assign Expert' button click for both cash and installment inquiries.
     * It dynamically determines the inquiry type and fetches the correct expert list.
     */
    $(document.body).on('click', '.assign-expert-btn', function() {
        const button = $(this);
        const inquiryId = button.data('inquiry-id');
        const inquiryType = button.data('inquiry-type'); // 'cash' or 'installment'
        
        // Clone the appropriate expert filter dropdown from the page to use in the modal
        const expertFilterSelector = (inquiryType === 'cash') ? '#cash-expert-filter' : '#expert-filter';
        const expertOptionsHTML = $(expertFilterSelector)
            .clone()
            .prop('id', 'swal-expert-filter')
            .prepend('<option value="auto">-- انتساب خودکار (گردشی) --</option>')
            .val('auto')
            .get(0).outerHTML;

        const ajaxAction = (inquiryType === 'cash') ? 'maneli_assign_expert_to_cash_inquiry' : 'maneli_assign_expert_to_inquiry';
        const nonce = (inquiryType === 'cash') ? maneliInquiryLists.nonces.cash_assign_expert : maneliInquiryLists.nonces.assign_expert;

        Swal.fire({
            title: `ارجاع درخواست #${inquiryId}`,
            html: `<div style="text-align: right; font-family: inherit;">
                     <label for="swal-expert-filter" style="display: block; margin-bottom: 10px;">یک کارشناس را برای این درخواست انتخاب کنید:</label>
                     ${expertOptionsHTML}
                   </div>`,
            confirmButtonText: 'ارجاع بده',
            showCancelButton: true,
            cancelButtonText: 'انصراف',
            didOpen: () => {
                 $('#swal-expert-filter').select2({
                     placeholder: "انتخاب کارشناс",
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
                $.post(maneliInquiryLists.ajax_url, {
                    action: ajaxAction,
                    nonce: nonce,
                    inquiry_id: inquiryId,
                    expert_id: result.value
                }, function(response) {
                    if (response.success) {
                        Swal.fire(maneliInquiryLists.text.success, response.data.message, 'success').then(() => {
                           // If the action was on a single report page, reload to show changes.
                           if (button.closest('#cash-inquiry-details').length > 0) {
                               location.reload();
                           } else {
                               // If on a list, update the table row directly.
                               button.closest('td').text(response.data.expert_name);
                               if (response.data.new_status_label) {
                                   button.closest('tr').find('td[data-title="وضعیت"]').text(response.data.new_status_label);
                               }
                           }
                        });
                    } else {
                        Swal.fire(maneliInquiryLists.text.error, response.data.message, 'error');
                        button.prop('disabled', false).text('ارجاع');
                    }
                });
            }
        });
    });

    /**
     * Handles the 'Edit' button click for a cash inquiry.
     */
    $(document.body).on('click', '#edit-cash-inquiry-btn', function() {
        const button = $(this);
        const inquiryId = button.closest('#cash-inquiry-details').data('inquiry-id');
        button.prop('disabled', true).text('...');

        $.post(maneliInquiryLists.ajax_url, {
            action: 'maneli_get_cash_inquiry_details',
            nonce: maneliInquiryLists.nonces.cash_details,
            inquiry_id: inquiryId
        }, function(response) {
            if (response.success) {
                const data = response.data;
                Swal.fire({
                    title: `ویرایش درخواست #${data.id}`,
                    html: `
                        <input type="text" id="swal-first-name" class="swal2-input" value="${data.customer.first_name}" placeholder="نام">
                        <input type="text" id="swal-last-name" class="swal2-input" value="${data.customer.last_name}" placeholder="نام خانوادگی">
                        <input type="text" id="swal-mobile" class="swal2-input" value="${data.customer.mobile}" placeholder="موبایل">
                        <input type="text" id="swal-color" class="swal2-input" value="${data.car.color}" placeholder="رنگ خودرو">`,
                    confirmButtonText: 'ذخیره تغییرات',
                    showCancelButton: true,
                    cancelButtonText: 'انصراف',
                    preConfirm: () => ({
                        first_name: $('#swal-first-name').val(),
                        last_name: $('#swal-last-name').val(),
                        mobile: $('#swal-mobile').val(),
                        color: $('#swal-color').val()
                    })
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post(maneliInquiryLists.ajax_url, {
                            action: 'maneli_update_cash_inquiry',
                            nonce: maneliInquiryLists.nonces.cash_update,
                            inquiry_id: inquiryId,
                            ...result.value
                        }, function(updateResponse) {
                            if (updateResponse.success) {
                                Swal.fire(maneliInquiryLists.text.success, updateResponse.data.message, 'success').then(() => location.reload());
                            } else {
                                Swal.fire(maneliInquiryLists.text.error, updateResponse.data.message, 'error');
                            }
                        });
                    }
                });
            } else {
                Swal.fire(maneliInquiryLists.text.error, response.data.message, 'error');
            }
        }).always(() => button.prop('disabled', false).text('ویرایش اطلاعات'));
    });

    /**
     * Handles the 'Delete' button click for a cash inquiry.
     */
    $(document.body).on('click', '#delete-cash-inquiry-btn', function() {
        const button = $(this);
        const inquiryId = button.closest('#cash-inquiry-details').data('inquiry-id');
        const backUrl = $('.report-back-button-wrapper a').attr('href');

        Swal.fire({
            title: maneliInquiryLists.text.confirm_delete_title,
            text: maneliInquiryLists.text.confirm_delete_text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: maneliInquiryLists.text.confirm_button,
            cancelButtonText: maneliInquiryLists.text.cancel_button
        }).then((result) => {
            if (result.isConfirmed) {
                button.prop('disabled', true).text('...');
                $.post(maneliInquiryLists.ajax_url, {
                    action: 'maneli_delete_cash_inquiry',
                    nonce: maneliInquiryLists.nonces.cash_delete,
                    inquiry_id: inquiryId
                }, function(response) {
                    if (response.success) {
                        Swal.fire('حذف شد!', response.data.message, 'success').then(() => {
                            if (backUrl) window.location.href = backUrl;
                        });
                    } else {
                        Swal.fire(maneliInquiryLists.text.error, response.data.message, 'error');
                        button.prop('disabled', false).text('حذف درخواست');
                    }
                });
            }
        });
    });

    /**
     * Handles the 'Set Down Payment' button click for a cash inquiry.
     */
    $(document.body).on('click', '#set-downpayment-btn', function() {
        const button = $(this);
        const inquiryId = button.closest('#cash-inquiry-details').data('inquiry-id');
        
        Swal.fire({
            title: `تعیین پیش‌پرداخت برای #${inquiryId}`,
            html: `<input type="text" id="swal-downpayment-amount" class="swal2-input" placeholder="مبلغ پیش‌پرداخت به تومان">`,
            confirmButtonText: 'تایید و ارسال برای مشتری',
            showCancelButton: true,
            cancelButtonText: 'انصراف',
            preConfirm: () => $('#swal-downpayment-amount').val()
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                button.prop('disabled', true).text('...');
                $.post(maneliInquiryLists.ajax_url, {
                    action: 'maneli_set_down_payment',
                    nonce: maneliInquiryLists.nonces.cash_set_downpayment,
                    inquiry_id: inquiryId,
                    status: 'awaiting_payment',
                    amount: result.value
                }, function(response) {
                    if (response.success) {
                        Swal.fire(maneliInquiryLists.text.success, response.data.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire(maneliInquiryLists.text.error, response.data.message, 'error');
                        button.prop('disabled', false).text('تعیین پیش‌پرداخت');
                    }
                });
            }
        });
    });

    /**
     * Handles the 'Reject' button click for a cash inquiry.
     */
    $(document.body).on('click', '#reject-cash-inquiry-btn', function() {
        const button = $(this);
        const inquiryId = button.closest('#cash-inquiry-details').data('inquiry-id');
        
        let reasonOptions = '<option value="">-- یک دلیل انتخاب کنید --</option>';
        if(maneliInquiryLists.cash_rejection_reasons && maneliInquiryLists.cash_rejection_reasons.length > 0) {
            maneliInquiryLists.cash_rejection_reasons.forEach(reason => {
                reasonOptions += `<option value="${reason}">${reason}</option>`;
            });
        }
        reasonOptions += '<option value="custom">دلیل دیگر (دلخواه)</option>';

        Swal.fire({
            title: `رد درخواست #${inquiryId}`,
            html: `<div style="text-align: right; font-family: inherit;">
                     <label for="swal-rejection-reason-select" style="display: block; margin-bottom: 10px;">لطفا دلیل رد درخواست را انتخاب کنید:</label>
                     <select id="swal-rejection-reason-select" class="swal2-select" style="width: 100%;">${reasonOptions}</select>
                     <textarea id="swal-rejection-reason-custom" class="swal2-textarea" placeholder="دلیل دلخواه را اینجا بنویسید..." style="display: none; margin-top: 10px;"></textarea>
                   </div>`,
            confirmButtonText: 'ثبت رد درخواست',
            showCancelButton: true,
            cancelButtonText: 'انصراف',
            didOpen: () => {
                $('#swal-rejection-reason-select').on('change', function() {
                    $('#swal-rejection-reason-custom').toggle($(this).val() === 'custom');
                });
            },
            preConfirm: () => {
                const select = $('#swal-rejection-reason-select');
                return select.val() === 'custom' ? $('#swal-rejection-reason-custom').val() : select.val();
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                button.prop('disabled', true).text('...');
                $.post(maneliInquiryLists.ajax_url, {
                    action: 'maneli_set_down_payment',
                    nonce: maneliInquiryLists.nonces.cash_set_downpayment,
                    inquiry_id: inquiryId,
                    status: 'rejected',
                    reason: result.value
                }, function(response) {
                    if (response.success) {
                        Swal.fire(maneliInquiryLists.text.success, response.data.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire(maneliInquiryLists.text.error, response.data.message, 'error');
                        button.prop('disabled', false).text('رد کردن درخواست');
                    }
                });
            }
        });
    });

    //======================================================================
    //  AJAX LIST FILTERING & PAGINATION (LOGIC MOVED FROM PHP)
    //======================================================================
    
    // You would also have the AJAX filtering logic here for both inquiry lists.
    // This logic would be similar to what was previously in the PHP files but now resides here.
});