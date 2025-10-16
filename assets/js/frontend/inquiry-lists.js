/**
 * Handles all frontend JavaScript logic for the inquiry list pages (both installment and cash).
 * This includes AJAX filtering, pagination, and admin/expert actions like assigning experts,
 * editing, deleting, and updating statuses via SweetAlert2 modals.
 *
 * این فایل منطق فیلترینگ AJAX که در نسخه اصلی حذف شده بود را پیاده‌سازی می‌کند.
 *
 * @version 1.0.3 (Added list and report deletion logic for both inquiry types)
 */
jQuery(document).ready(function($) {
    'use strict';

    //======================================================================
    //  HELPER FUNCTION: LOCALIZATION & STRING ACCESS
    //======================================================================
    
    // Helper to get localized text, falling back to English/a default if missing (for robustness)
    const getText = (key, fallback = '...') => maneliInquiryLists.text[key] || fallback;
    
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
        
        // Ensure expert filter exists (might be missing if user is expert and only sees their own list)
        if (!$(expertFilterSelector).length) {
            Swal.fire(getText('error'), getText('no_experts_available'), 'error');
            return;
        }

        const expertOptionsHTML = $(expertFilterSelector)
            .clone()
            .prop('id', 'swal-expert-filter')
            .prepend(`<option value="auto">${getText('auto_assign')}</option>`)
            .val('auto')
            .get(0).outerHTML;

        const ajaxAction = (inquiryType === 'cash') ? 'maneli_assign_expert_to_cash_inquiry' : 'maneli_assign_expert_to_inquiry';
        const nonce = (inquiryType === 'cash') ? maneliInquiryLists.nonces.cash_assign_expert : maneliInquiryLists.nonces.assign_expert;

        Swal.fire({
            title: `${getText('assign_title')} #${inquiryId}`,
            html: `<div style="text-align: right; font-family: inherit;">
                     <label for="swal-expert-filter" style="display: block; margin-bottom: 10px;">${getText('assign_label')}</label>
                     ${expertOptionsHTML}
                   </div>`,
            confirmButtonText: getText('assign_button'),
            showCancelButton: true,
            cancelButtonText: getText('cancel_button'),
            didOpen: () => {
                 // Initialize Select2 in the modal
                 $('#swal-expert-filter').select2({
                     placeholder: getText('select_expert_placeholder'),
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
                        Swal.fire(getText('success'), response.data.message, 'success').then(() => {
                           // If the action was on a single report page, reload to show changes.
                           if (button.closest('.frontend-expert-report').length > 0) {
                               location.reload();
                           } else {
                               // If on a list, update the table row directly.
                               const row = button.closest('tr');
                               // Update Assigned Expert column: Replace button with expert name
                               button.parent().html(response.data.expert_name);
                               if (response.data.new_status_label) {
                                   // Update Status column: Find TD with status-cell class and update the status indicator
                                   const statusCell = row.find('.inquiry-status-cell-installment, .inquiry-status-cell-cash');
                                   const statusIndicator = statusCell.find('.status-indicator');
                                   if (statusIndicator.length) {
                                       statusIndicator.text(response.data.new_status_label);
                                       // Update the status class if available
                                       if (response.data.new_status_key) {
                                           statusIndicator.attr('class', 'status-indicator status-' + response.data.new_status_key);
                                       }
                                   }
                               }
                           }
                        });
                    } else {
                        Swal.fire(getText('error'), response.data.message, 'error');
                        button.prop('disabled', false).text(originalText);
                    }
                }).fail(function() {
                    Swal.fire(getText('error'), getText('server_error'), 'error');
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
                    title: `${getText('edit_title')} #${data.id}`,
                    html: `
                        <input type="text" id="swal-first-name" class="swal2-input" value="${data.customer.first_name}" placeholder="${getText('placeholder_name')}">
                        <input type="text" id="swal-last-name" class="swal2-input" value="${data.customer.last_name}" placeholder="${getText('placeholder_last_name')}">
                        <input type="text" id="swal-mobile" class="swal2-input" value="${data.customer.mobile}" placeholder="${getText('placeholder_mobile')}">
                        <input type="text" id="swal-color" class="swal2-input" value="${data.car.color}" placeholder="${getText('placeholder_color')}">`,
                    confirmButtonText: getText('save_button'),
                    showCancelButton: true,
                    cancelButtonText: getText('cancel_button'),
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
                                Swal.fire(getText('success'), updateResponse.data.message, 'success').then(() => location.reload());
                            } else {
                                Swal.fire(getText('error'), updateResponse.data.message, 'error');
                            }
                        }).fail(function() {
                            Swal.fire(getText('error'), getText('server_error'), 'error');
                        });
                    }
                });
            } else {
                Swal.fire(getText('error'), response.data.message, 'error');
            }
        }).always(() => button.prop('disabled', false).text(originalText));
    });

    /**
     * Handles the 'Set Down Payment' button click for a cash inquiry.
     */
    $(document.body).on('click', '#set-downpayment-btn', function() {
        const button = $(this);
        const inquiryId = button.closest('#cash-inquiry-details').data('inquiry-id');
        const originalText = button.text();
        
        Swal.fire({
            title: `${getText('downpayment_title')} #${inquiryId}`,
            html: `<input type="text" id="swal-downpayment-amount" class="swal2-input" placeholder="${getText('downpayment_placeholder')}">`,
            confirmButtonText: getText('downpayment_button'),
            showCancelButton: true,
            cancelButtonText: getText('cancel_button'),
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
                        Swal.fire(getText('success'), response.data.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire(getText('error'), response.data.message, 'error');
                        button.prop('disabled', false).text(originalText);
                    }
                }).fail(function() {
                    Swal.fire(getText('error'), getText('server_error'), 'error');
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
        
        // Use cash_rejection_reasons for cash inquiry rejection
        let reasonOptions = `<option value="">${getText('reject_option_default')}</option>`;
        if(maneliInquiryLists.cash_rejection_reasons && maneliInquiryLists.cash_rejection_reasons.length > 0) {
            maneliInquiryLists.cash_rejection_reasons.forEach(reason => {
                reasonOptions += `<option value="${reason}">${reason}</option>`;
            });
        }
        reasonOptions += `<option value="custom">${getText('reject_option_custom')}</option>`;

        Swal.fire({
            title: `${getText('reject_title')} #${inquiryId}`,
            html: `<div style="text-align: right; font-family: inherit;">
                     <label for="swal-rejection-reason-select" style="display: block; margin-bottom: 10px;">${getText('reject_label')}</label>
                     <select id="swal-rejection-reason-select" class="swal2-select" style="width: 100%;">${reasonOptions}</select>
                     <textarea id="swal-rejection-reason-custom" class="swal2-textarea" placeholder="${getText('reject_placeholder_custom')}" style="display: none; margin-top: 10px;"></textarea>
                   </div>`,
            confirmButtonText: getText('reject_submit_button'),
            showCancelButton: true,
            cancelButtonText: getText('cancel_button'),
            didOpen: () => {
                $('#swal-rejection-reason-select').on('change', function() {
                    $('#swal-rejection-reason-custom').toggle($(this).val() === 'custom');
                });
            },
            preConfirm: () => {
                const select = $('#swal-rejection-reason-select');
                let reason = select.val() === 'custom' ? $('#swal-rejection-reason-custom').val() : select.val();
                if (!reason) {
                    Swal.showValidationMessage(getText('rejection_reason_required'));
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
                        Swal.fire(getText('success'), response.data.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire(getText('error'), response.data.message, 'error');
                        button.prop('disabled', false).text(originalText);
                    }
                }).fail(function() {
                    Swal.fire(getText('error'), getText('server_error'), 'error');
                    button.prop('disabled', false).text(originalText);
                });
            }
        });
    });
    
    // ----------------------------------------------------------------------
    // UNIVERSAL DELETION LOGIC (LISTS AND REPORTS)
    // ----------------------------------------------------------------------

    /**
     * Handles the 'Delete' button click for both cash and installment inquiries from the LIST PAGE.
     * Selectors: .delete-cash-list-btn, .delete-installment-list-btn
     */
    $(document.body).on('click', '.delete-cash-list-btn, .delete-installment-list-btn', function() {
        const button = $(this);
        const inquiryId = button.data('inquiry-id');
        // Determine type based on which class was clicked
        const inquiryType = button.hasClass('delete-installment-list-btn') ? 'installment' : 'cash'; 
        
        const ajaxAction = (inquiryType === 'cash') ? 'maneli_delete_cash_inquiry' : 'maneli_delete_inquiry';
        const nonce = (inquiryType === 'cash') ? maneliInquiryLists.nonces.cash_delete : maneliInquiryLists.nonces.inquiry_delete;
        const originalText = button.text();

        Swal.fire({
            title: getText('delete_list_title'), 
            text: getText('delete_list_text'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: getText('confirm_button'),
            cancelButtonText: getText('cancel_button')
        }).then((result) => {
            if (result.isConfirmed) {
                button.prop('disabled', true).text('...');
                $.post(maneliInquiryLists.ajax_url, {
                    action: ajaxAction,
                    nonce: nonce,
                    inquiry_id: inquiryId
                }, function(response) {
                    if (response.success) {
                        // Remove the row from the table
                        button.closest('tr').fadeOut(400, function() {
                            $(this).remove();
                        });
                        Swal.fire(getText('delete_title'), response.data.message || 'Request deleted.', 'success');
                    } else {
                        Swal.fire(getText('error'), response.data.message, 'error');
                        button.prop('disabled', false).text(originalText);
                    }
                }).fail(function() {
                    Swal.fire(getText('error'), getText('server_error'), 'error');
                    button.prop('disabled', false).text(originalText);
                });
            }
        });
    });

    /**
     * Handles the 'Delete Request' button click for both cash and installment inquiries from the REPORT PAGE.
     * Selector: .delete-inquiry-report-btn
     * This handler is more generic and ensures redirection to the list page after deletion.
     */
    $(document.body).on('click', '.delete-inquiry-report-btn', function() {
        const button = $(this);
        const inquiryId = button.data('inquiry-id');
        const inquiryType = button.data('inquiry-type');
        const backUrl = $('.report-back-button-wrapper a').attr('href'); 
        
        const ajaxAction = (inquiryType === 'cash') ? 'maneli_delete_cash_inquiry' : 'maneli_delete_inquiry';
        const nonce = (inquiryType === 'cash') ? maneliInquiryLists.nonces.cash_delete : maneliInquiryLists.nonces.inquiry_delete;
        const originalText = button.text();

        Swal.fire({
            title: getText('confirm_delete_title'), // Using the general report confirmation title
            text: getText('confirm_delete_text'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: getText('confirm_button'),
            cancelButtonText: getText('cancel_button')
        }).then((result) => {
            if (result.isConfirmed) {
                button.prop('disabled', true).text('...');
                $.post(maneliInquiryLists.ajax_url, {
                    action: ajaxAction,
                    nonce: nonce,
                    inquiry_id: inquiryId
                }, function(response) {
                    if (response.success) {
                        Swal.fire(getText('delete_title'), response.data.message || 'Request deleted.', 'success').then(() => {
                            if (backUrl) window.location.href = backUrl; // Redirect to list on successful deletion
                        });
                    } else {
                        Swal.fire(getText('error'), response.data.message, 'error');
                        button.prop('disabled', false).text(originalText);
                    }
                }).fail(function() {
                    Swal.fire(getText('error'), getText('server_error'), 'error');
                    button.prop('disabled', false).text(originalText);
                });
            }
        });
    });


    /**
     * Handles the 'Approve' button click for an installment inquiry (on the report page).
     * This action is decoupled and submits the hidden admin form.
     */
    $(document.body).on('click', '.confirm-inquiry-btn', function() {
        const button = $(this);
        const inquiryId = button.data('inquiry-id');
        
        // Clone the expert filter (only present for admins, not experts)
        const expertFilterSelector = '#expert-filter';
        const expertOptionsHTML = $(expertFilterSelector).length ? 
            $(expertFilterSelector)
                .clone()
                .prop('id', 'swal-expert-filter-approve')
                .prepend(`<option value="auto">${getText('auto_assign')}</option>`)
                .val('auto')
                .get(0).outerHTML
            : `<input type="hidden" id="swal-expert-filter-approve" value="auto">`; // Default to auto-assign if admin is not available/only expert is viewing

        Swal.fire({
            title: `${getText('assign_title')} #${inquiryId}`,
            html: `<div style="text-align: right; font-family: inherit;">
                     <p style="margin-bottom: 15px;">${getText('confirm_delete_title')}</p>
                     <label for="swal-expert-filter-approve" style="display: block; margin-bottom: 10px;">${getText('assign_label')}</label>
                     ${expertOptionsHTML}
                   </div>`,
            confirmButtonText: getText('assign_button'),
            showCancelButton: true,
            cancelButtonText: getText('cancel_button'),
             didOpen: () => {
                 // Initialize Select2 in the modal only if the select element is present
                 if($('#swal-expert-filter-approve').is('select')) {
                     $('#swal-expert-filter-approve').select2({
                         placeholder: getText('select_expert_placeholder'),
                         allowClear: false,
                         width: '100%'
                    });
                 }
            },
            preConfirm: () => {
                return $('#swal-expert-filter-approve').val();
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Submit the main hidden form with the chosen expert
                const form = $('#admin-action-form');
                $('#final-status-input').val('approved');
                $('#assigned-expert-input').val(result.value);
                form.submit();
            }
        });
    });

    /**
     * Handles the 'Reject' button click for an installment inquiry (on the report page).
     */
    $(document.body).on('click', '.reject-inquiry-btn', function() {
        const button = $(this);
        const inquiryId = button.data('inquiry-id');
        
        // Use installment_rejection_reasons for installment inquiry rejection
        let reasonOptions = `<option value="">${getText('reject_option_default')}</option>`;
        if(maneliInquiryLists.installment_rejection_reasons && maneliInquiryLists.installment_rejection_reasons.length > 0) {
            maneliInquiryLists.installment_rejection_reasons.forEach(reason => {
                reasonOptions += `<option value="${reason}">${reason}</option>`;
            });
        }
        reasonOptions += `<option value="custom">${getText('reject_option_custom')}</option>`;

        Swal.fire({
            title: `${getText('reject_title')} #${inquiryId}`,
            html: `<div style="text-align: right; font-family: inherit;">
                     <label for="swal-rejection-reason-select" style="display: block; margin-bottom: 10px;">${getText('reject_label')}</label>
                     <select id="swal-rejection-reason-select" class="swal2-select" style="width: 100%;">${reasonOptions}</select>
                     <textarea id="swal-rejection-reason-custom" class="swal2-textarea" placeholder="${getText('reject_placeholder_custom')}" style="display: none; margin-top: 10px;"></textarea>
                   </div>`,
            confirmButtonText: getText('reject_submit_button'),
            showCancelButton: true,
            cancelButtonText: getText('cancel_button'),
            didOpen: () => {
                $('#swal-rejection-reason-select').on('change', function() {
                    $('#swal-rejection-reason-custom').toggle($(this).val() === 'custom');
                });
            },
            preConfirm: () => {
                const select = $('#swal-rejection-reason-select');
                let reason = select.val() === 'custom' ? $('#swal-rejection-reason-custom').val() : select.val();
                if (!reason) {
                    Swal.showValidationMessage(getText('rejection_reason_required'));
                }
                return reason;
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                 const form = $('#admin-action-form');
                 // Set hidden fields and submit the form via admin-post.php
                 $('#final-status-input').val('rejected');
                 $('#rejection-reason-input').val(result.value);
                 form.submit();
            }
        });
    });

    //======================================================================
    //  AJAX LIST FILTERING & PAGINATION (IMPLEMENTATION OF MISSING LOGIC)
    //======================================================================
    
    // Determine which list is present
    const installmentFilterForm = $('#maneli-inquiry-filter-form');
    const cashFilterForm = $('#maneli-cash-inquiry-filter-form');
    
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
            
            // Build base URL preserving important query parameters (like 'endp')
            const urlObj = new URL(window.location.href);
            const paramsToRemove = ['inquiry_id', 'cash_inquiry_id', 'paged', 'page'];
            paramsToRemove.forEach(param => urlObj.searchParams.delete(param));
            const baseUrl = urlObj.toString(); 
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
                        listBody.html(`<tr><td colspan="${colspan}" style="text-align:center;">${getText('error')}: ${response.data.message || getText('unknown_error')}</td></tr>`);
                        paginationWrapper.html('');
                    }
                },
                error: function(jqXHR, textStatus) {
                    if (textStatus !== 'abort') {
                        listBody.html(`<tr><td colspan="${colspan}" style="text-align:center;">${getText('server_error')}</td></tr>`);
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