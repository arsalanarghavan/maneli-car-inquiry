/**
 * Handles all frontend JavaScript logic for the inquiry list pages (both installment and cash).
 * This includes AJAX filtering, pagination, and admin/expert actions like assigning experts,
 * editing, deleting, and updating statuses via SweetAlert2 modals.
 *
 * این فایل منطق فیلترینگ AJAX که در نسخه اصلی حذف شده بود را پیاده‌سازی می‌کند.
 *
 * @version 1.0.1 (Fixed AJAX Filtering & Added Localization for JS strings)
 */
jQuery(document).ready(function($) {
    'use strict';

    //======================================================================
    //  HELPER FUNCTION: LOCALIZATION & STRING ACCESS
    //======================================================================
    
    // Helper to get localized text, falling back to English/a default if missing (for robustness)
    const getText = (key, fallback = '...') => maneliInquiryLists.text[key] || fallback;
    
    // Helper to get hardcoded Persian texts for dynamic content within SweetAlerts
    const getHardcodedText = (key) => {
        const texts = {
            'assign_title': 'ارجاع درخواست',
            'assign_label': 'یک کارشناس را برای این درخواست انتخاب کنید:',
            'auto_assign': '-- انتساب خودکار (گردشی) --',
            'assign_button': 'ارجاع بده',
            'cancel_button': 'انصراف',
            'edit_title': 'ویرایش درخواست',
            'placeholder_name': 'نام',
            'placeholder_last_name': 'نام خانوادگی',
            'placeholder_mobile': 'موبایل',
            'placeholder_color': 'رنگ خودرو',
            'save_button': 'ذخیره تغییرات',
            'delete_title': 'حذف شد!',
            'delete_button_text': 'حذف درخواست',
            'downpayment_title': 'تعیین پیش‌پرداخت',
            'downpayment_placeholder': 'مبلغ پیش‌پرداخت به تومان',
            'downpayment_button': 'تایید و ارسال برای مشتری',
            'reject_title': 'رد درخواست',
            'reject_label': 'لطفا دلیل رد درخواست را انتخاب کنید:',
            'reject_option_default': '-- یک دلیل انتخاب کنید --',
            'reject_option_custom': 'دلیل دیگر (دلخواه)',
            'reject_placeholder_custom': 'دلیل دلخواه را اینجا بنویسید...',
            'reject_submit_button': 'ثبت رد درخواست',
            'unknown_error': 'خطای ناشناخته.',
            'server_error': 'یک خطای سرور رخ داد.',
        };
        return texts[key] || key;
    };


    //======================================================================
    //  EVENT DELEGATION FOR ADMIN/EXPERT ACTIONS
    //======================================================================

    /**
     * Handles the 'Assign Expert' button click for both cash and installment inquiries.
     */
    $(document.body).on('click', '.assign-expert-btn', function() {
        const button = $(this);
        const inquiryId = button.data('inquiry-id');
        const inquiryType = button.data('inquiry-type');
        
        // Clone the appropriate expert filter dropdown from the page to use in the modal
        const expertFilterSelector = (inquiryType === 'cash') ? '#cash-expert-filter' : '#expert-filter';
        const expertOptionsHTML = $(expertFilterSelector)
            .clone()
            .prop('id', 'swal-expert-filter')
            .prepend(`<option value="auto">${getHardcodedText('auto_assign')}</option>`)
            .val('auto')
            .get(0).outerHTML;

        const ajaxAction = (inquiryType === 'cash') ? 'maneli_assign_expert_to_cash_inquiry' : 'maneli_assign_expert_to_inquiry';
        const nonce = (inquiryType === 'cash') ? maneliInquiryLists.nonces.cash_assign_expert : maneliInquiryLists.nonces.assign_expert;

        Swal.fire({
            title: `${getHardcodedText('assign_title')} #${inquiryId}`,
            html: `<div style="text-align: right; font-family: inherit;">
                     <label for="swal-expert-filter" style="display: block; margin-bottom: 10px;">${getHardcodedText('assign_label')}</label>
                     ${expertOptionsHTML}
                   </div>`,
            confirmButtonText: getHardcodedText('assign_button'),
            showCancelButton: true,
            cancelButtonText: getHardcodedText('cancel_button'),
            didOpen: () => {
                 // Initialize Select2 in the modal
                 $('#swal-expert-filter').select2({
                     placeholder: getText('select_expert_placeholder', 'انتخاب کارشناس'),
                     allowClear: false,
                     width: '100%'
                });
            },
            preConfirm: () => {
                return $('#swal-expert-filter').val();
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                const originalText = button.text();
                button.prop('disabled', true).text('...');
                $.post(maneliInquiryLists.ajax_url, {
                    action: ajaxAction,
                    nonce: nonce,
                    inquiry_id: inquiryId,
                    expert_id: result.value
                }, function(response) {
                    if (response.success) {
                        Swal.fire(getText('success', 'Success'), response.data.message, 'success').then(() => {
                           // If the action was on a single report page, reload to show changes.
                           if (button.closest('.frontend-expert-report').length > 0) {
                               location.reload();
                           } else {
                               // If on a list, update the table row directly.
                               button.closest('td').html(response.data.expert_name);
                               if (response.data.new_status_label) {
                                   button.closest('tr').find('td[data-title="وضعیت"]').text(response.data.new_status_label);
                               }
                           }
                        });
                    } else {
                        Swal.fire(getText('error', 'Error'), response.data.message, 'error');
                        button.prop('disabled', false).text(originalText);
                    }
                }).fail(function() {
                    Swal.fire(getText('error', 'Error'), getHardcodedText('server_error'), 'error');
                    button.prop('disabled', false).text(originalText);
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
        const originalText = button.text();
        button.prop('disabled', true).text('...');

        $.post(maneliInquiryLists.ajax_url, {
            action: 'maneli_get_cash_inquiry_details',
            nonce: maneliInquiryLists.nonces.cash_details,
            inquiry_id: inquiryId
        }, function(response) {
            if (response.success) {
                const data = response.data;
                Swal.fire({
                    title: `${getHardcodedText('edit_title')} #${data.id}`,
                    html: `
                        <input type="text" id="swal-first-name" class="swal2-input" value="${data.customer.first_name}" placeholder="${getHardcodedText('placeholder_name')}">
                        <input type="text" id="swal-last-name" class="swal2-input" value="${data.customer.last_name}" placeholder="${getHardcodedText('placeholder_last_name')}">
                        <input type="text" id="swal-mobile" class="swal2-input" value="${data.customer.mobile}" placeholder="${getHardcodedText('placeholder_mobile')}">
                        <input type="text" id="swal-color" class="swal2-input" value="${data.car.color}" placeholder="${getHardcodedText('placeholder_color')}">`,
                    confirmButtonText: getHardcodedText('save_button'),
                    showCancelButton: true,
                    cancelButtonText: getHardcodedText('cancel_button'),
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
                                Swal.fire(getText('success', 'Success'), updateResponse.data.message, 'success').then(() => location.reload());
                            } else {
                                Swal.fire(getText('error', 'Error'), updateResponse.data.message, 'error');
                            }
                        }).fail(function() {
                            Swal.fire(getText('error', 'Error'), getHardcodedText('server_error'), 'error');
                        });
                    }
                });
            } else {
                Swal.fire(getText('error', 'Error'), response.data.message, 'error');
            }
        }).always(() => button.prop('disabled', false).text(originalText));
    });

    /**
     * Handles the 'Delete' button click for a cash inquiry.
     */
    $(document.body).on('click', '#delete-cash-inquiry-btn', function() {
        const button = $(this);
        const inquiryId = button.closest('#cash-inquiry-details').data('inquiry-id');
        const backUrl = $('.report-back-button-wrapper a').attr('href');
        const originalText = button.text();

        Swal.fire({
            title: getText('confirm_delete_title', 'Are you sure?'),
            text: getText('confirm_delete_text', 'This action cannot be undone!'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: getText('confirm_button', 'Yes, delete it!'),
            cancelButtonText: getText('cancel_button', 'Cancel')
        }).then((result) => {
            if (result.isConfirmed) {
                button.prop('disabled', true).text('...');
                $.post(maneliInquiryLists.ajax_url, {
                    action: 'maneli_delete_cash_inquiry',
                    nonce: maneliInquiryLists.nonces.cash_delete,
                    inquiry_id: inquiryId
                }, function(response) {
                    if (response.success) {
                        Swal.fire(getHardcodedText('delete_title'), response.data.message, 'success').then(() => {
                            if (backUrl) window.location.href = backUrl;
                        });
                    } else {
                        Swal.fire(getText('error', 'Error'), response.data.message, 'error');
                        button.prop('disabled', false).text(originalText);
                    }
                }).fail(function() {
                    Swal.fire(getText('error', 'Error'), getHardcodedText('server_error'), 'error');
                    button.prop('disabled', false).text(originalText);
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
        const originalText = button.text();
        
        Swal.fire({
            title: `${getHardcodedText('downpayment_title')} #${inquiryId}`,
            html: `<input type="text" id="swal-downpayment-amount" class="swal2-input" placeholder="${getHardcodedText('downpayment_placeholder')}">`,
            confirmButtonText: getHardcodedText('downpayment_button'),
            showCancelButton: true,
            cancelButtonText: getHardcodedText('cancel_button'),
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
                        Swal.fire(getText('success', 'Success'), response.data.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire(getText('error', 'Error'), response.data.message, 'error');
                        button.prop('disabled', false).text(originalText);
                    }
                }).fail(function() {
                    Swal.fire(getText('error', 'Error'), getHardcodedText('server_error'), 'error');
                    button.prop('disabled', false).text(originalText);
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
        const originalText = button.text();
        
        let reasonOptions = `<option value="">${getHardcodedText('reject_option_default')}</option>`;
        if(maneliInquiryLists.cash_rejection_reasons && maneliInquiryLists.cash_rejection_reasons.length > 0) {
            maneliInquiryLists.cash_rejection_reasons.forEach(reason => {
                reasonOptions += `<option value="${reason}">${reason}</option>`;
            });
        }
        reasonOptions += `<option value="custom">${getHardcodedText('reject_option_custom')}</option>`;

        Swal.fire({
            title: `${getHardcodedText('reject_title')} #${inquiryId}`,
            html: `<div style="text-align: right; font-family: inherit;">
                     <label for="swal-rejection-reason-select" style="display: block; margin-bottom: 10px;">${getHardcodedText('reject_label')}</label>
                     <select id="swal-rejection-reason-select" class="swal2-select" style="width: 100%;">${reasonOptions}</select>
                     <textarea id="swal-rejection-reason-custom" class="swal2-textarea" placeholder="${getHardcodedText('reject_placeholder_custom')}" style="display: none; margin-top: 10px;"></textarea>
                   </div>`,
            confirmButtonText: getHardcodedText('reject_submit_button'),
            showCancelButton: true,
            cancelButtonText: getHardcodedText('cancel_button'),
            didOpen: () => {
                $('#swal-rejection-reason-select').on('change', function() {
                    $('#swal-rejection-reason-custom').toggle($(this).val() === 'custom');
                });
            },
            preConfirm: () => {
                const select = $('#swal-rejection-reason-select');
                let reason = select.val() === 'custom' ? $('#swal-rejection-reason-custom').val() : select.val();
                if (!reason) {
                    Swal.showValidationMessage(getText('rejection_reason_required', 'لطفاً یک دلیل برای رد درخواست انتخاب یا وارد کنید.'));
                }
                return reason;
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
                        Swal.fire(getText('success', 'Success'), response.data.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire(getText('error', 'Error'), response.data.message, 'error');
                        button.prop('disabled', false).text(originalText);
                    }
                }).fail(function() {
                    Swal.fire(getText('error', 'Error'), getHardcodedText('server_error'), 'error');
                    button.prop('disabled', false).text(originalText);
                });
            }
        });
    });

    //======================================================================
    //  AJAX LIST FILTERING & PAGINATION (IMPLEMENTATION OF MISSING LOGIC)
    //======================================================================
    
    // Determine which list is present
    const installmentFilterForm = $('#maneli-inquiry-filter-form');
    const cashFilterForm = $('#maneli-cash-inquiry-filter-form');
    
    let filterForm = installmentFilterForm.length ? installmentFilterForm : cashFilterForm;
    let listType = installmentFilterForm.length ? 'installment' : (cashFilterForm.length ? 'cash' : null);

    if (listType) {
        const listBody = (listType === 'installment') ? $('#maneli-inquiry-list-tbody') : $('#maneli-cash-inquiry-list-tbody');
        const searchInput = (listType === 'installment') ? $('#inquiry-search-input') : $('#cash-inquiry-search-input');
        const statusFilter = (listType === 'installment') ? $('#status-filter') : $('#cash-inquiry-status-filter');
        const expertFilter = (listType === 'installment') ? $('#expert-filter') : $('#cash-expert-filter');
        const loader = (listType === 'installment') ? $('#inquiry-list-loader') : $('#cash-inquiry-list-loader');
        const paginationWrapper = (listType === 'installment') ? $('.maneli-pagination-wrapper') : $('.maneli-cash-pagination-wrapper');

        let xhr;
        let searchTimeout;

        /**
         * Fetches and updates the inquiry list via AJAX based on current filter values.
         * @param {number} page The page number to fetch.
         */
        function fetchInquiries(page = 1) {
            // Abort any ongoing AJAX request to prevent race conditions
            if (xhr && xhr.readyState !== 4) {
                xhr.abort();
            }

            loader.show();
            listBody.css('opacity', 0.5);
            paginationWrapper.css('opacity', 0.5);

            const ajaxAction = (listType === 'installment') ? 'maneli_filter_inquiries_ajax' : 'maneli_filter_cash_inquiries_ajax';
            const nonce = (listType === 'installment') ? maneliInquiryLists.nonces.inquiry_filter : maneliInquiryLists.nonces.cash_filter;
            // Use the base URL without query string for base_url parameter
            const baseUrl = window.location.href.split('?')[0]; 
            const colspan = listType === 'installment' ? 7 : 8; // Number of columns in the table

            const formData = {
                action: ajaxAction,
                _ajax_nonce: nonce,
                search: searchInput.val(),
                status: statusFilter.val(),
                expert: expertFilter.val(),
                page: page,
                base_url: baseUrl
            };
            
            // Abort previous Select2 instance before fetching new content
            if (expertFilter.hasClass('select2-hidden-accessible')) {
                expertFilter.select2('destroy');
            }
            
            xhr = $.ajax({
                url: maneliInquiryLists.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        listBody.html(response.data.html);
                        paginationWrapper.html(response.data.pagination_html);
                    } else {
                        listBody.html(`<tr><td colspan="${colspan}" style="text-align:center;">${getText('error', 'Error')}: ${response.data.message || getHardcodedText('unknown_error')}</td></tr>`);
                        paginationWrapper.html('');
                    }
                },
                error: function(jqXHR, textStatus) {
                    if (textStatus !== 'abort') {
                        listBody.html(`<tr><td colspan="${colspan}" style="text-align:center;">${getHardcodedText('server_error')}</td></tr>`);
                    }
                },
                complete: function() {
                    loader.hide();
                    listBody.css('opacity', 1);
                    paginationWrapper.css('opacity', 1);
                    // Re-initialize Select2 for expert filter if present and visible
                    if (expertFilter.length && expertFilter.is(':visible')) {
                       expertFilter.select2({ width: '100%' });
                    }
                }
            });
        }
        
        // --- Attach Listeners to Filters ---

        // Load initial list on page load
        fetchInquiries(1);

        // Handle search input with a debounce effect (500ms delay)
        searchInput.on('keyup', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                fetchInquiries(1); // Reset to page 1 on new search
            }, 500);
        });

        // Handle change events on filter dropdowns
        statusFilter.on('change', function() { fetchInquiries(1); });
        
        // Handle expert filter change (using change event on the original select element)
        expertFilter.on('change', function() { fetchInquiries(1); });

        // Re-bind the change event for Select2 wrapper for proper functionality
        if (expertFilter.hasClass('maneli-select2')) {
            // Because Select2 generates a new structure, we listen to the change event on the body 
            // and filter for the specific element inside the wrapper.
             $(document.body).on('change', '.select2-container', function(e) {
                // Only act if the change event bubbled up from the target select
                if ($(e.target).is(expertFilter)) {
                    fetchInquiries(1);
                }
            });
        }


        // Handle pagination clicks using event delegation
        paginationWrapper.on('click', 'a.page-numbers', function(e) {
            e.preventDefault();
            
            const pageUrl = $(this).attr('href');
            let pageNum = 1;

            // Extract page number from href (e.g., ?paged=2)
            const matches = pageUrl.match(/paged=(\d+)/);
            if (matches) {
                pageNum = parseInt(matches[1], 10);
            } else {
                // Crude fallback logic for previous/next buttons if page number is not explicit in link format
                const currentPageText = paginationWrapper.find('.page-numbers.current').text();
                const currentPage = parseInt(currentPageText, 10) || 1;
                
                if ($(this).hasClass('prev') || $(this).attr('rel') === 'prev') {
                    pageNum = Math.max(1, currentPage - 1);
                } else if ($(this).hasClass('next') || $(this).attr('rel') === 'next') {
                    pageNum = currentPage + 1;
                }
            }
            fetchInquiries(pageNum);
        });
    } // End of listType check
});