/**
 * Handles all frontend JavaScript logic for the inquiry list pages (both installment and cash).
 * This includes AJAX filtering, pagination, and admin/expert actions like assigning experts,
 * editing, deleting, and updating statuses via SweetAlert2 modals.
 *
 * این فایل منطق فیلترینگ AJAX که در نسخه اصلی حذف شده بود را پیاده‌سازی می‌کند.
 *
 * @version 1.0.3 (Added list and report deletion logic for both inquiry types)
 */

// Immediate console log to verify script is loaded
console.log('🔵 inquiry-lists.js FILE LOADED');

// Helper function to convert numbers to Persian
function toPersianNumber(num) {
    const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    const englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    return String(num).replace(/\d/g, function(digit) {
        return persianDigits[englishDigits.indexOf(digit)];
    });
}

// Wait for jQuery to be available
(function() {
    console.log('🔵 inquiry-lists.js IIFE STARTED');
    
    function waitForJQuery(callback) {
        console.log('🔵 waitForJQuery called, jQuery check:', typeof jQuery !== 'undefined' || typeof window.jQuery !== 'undefined');
        if (typeof jQuery !== 'undefined' && jQuery) {
            jQuery(document).ready(callback);
        } else if (typeof window.jQuery !== 'undefined' && window.jQuery) {
            window.jQuery(document).ready(callback);
        } else {
            setTimeout(function() {
                waitForJQuery(callback);
            }, 50);
        }
    }
    
    waitForJQuery(function() {
        var $ = jQuery;
        'use strict';
        
        // Debug: Log that script is loaded
        console.log('=== Inquiry Lists Script INITIALIZED ===');
        console.log('jQuery available:', typeof $ !== 'undefined');
        console.log('Document ready state:', document.readyState);
        
    //======================================================================
    //  HELPER FUNCTION: LOCALIZATION & STRING ACCESS
    //======================================================================
    
    // Helper to get localized text, falling back to English/a default if missing (for robustness)
    const getText = (key, fallback = '...') => {
        if (typeof maneliInquiryLists !== 'undefined' && maneliInquiryLists.text && maneliInquiryLists.text[key]) {
            return maneliInquiryLists.text[key];
        }
        return fallback;
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
        
        // Debug: Log the localized data
        console.log('=== Expert Assignment Debug ===');
        console.log('maneliInquiryLists exists:', typeof maneliInquiryLists !== 'undefined');
        console.log('Full maneliInquiryLists:', window.maneliInquiryLists);
        console.log('Experts array:', window.maneliInquiryLists?.experts);
        console.log('Experts count:', window.maneliInquiryLists?.experts?.length || 0);
        
        // Build from localized experts data (for report pages and list pages)
        const expertsData = (typeof maneliInquiryLists !== 'undefined' && maneliInquiryLists.experts) 
                            ? maneliInquiryLists.experts 
                            : [];
        
        console.log('Processed expertsData:', expertsData);
        console.log('Processed expertsData length:', expertsData.length);
        
        if (expertsData.length === 0) {
            console.error('ERROR: No experts found!');
            Swal.fire(getText('error'), getText('no_experts_available', 'No experts found.'), 'error');
            return;
        }
        
        console.log('SUCCESS: Found', expertsData.length, 'experts');
        
        // Always build the select from experts data to ensure consistency
        let optionsHtml = `<option value="auto">${getText('auto_assign')}</option>`;
        expertsData.forEach(expert => {
            optionsHtml += `<option value="${expert.id}">${expert.name}</option>`;
        });
        const expertOptionsHTML = `<select id="swal-expert-filter" class="swal2-input" style="width: 100%; padding: 10px; font-size: 14px; border: 1px solid #ddd; border-radius: 4px;">${optionsHtml}</select>`;

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
                 // Initialize Select2 in the modal (if Select2 is available)
                 if (typeof $.fn.select2 !== 'undefined') {
                     $('#swal-expert-filter').select2({
                         placeholder: getText('select_expert_placeholder'),
                         allowClear: false,
                         width: '100%',
                         dropdownParent: $('.swal2-popup')
                     });
                 }
            },
            preConfirm: () => {
                const selectedValue = $('#swal-expert-filter').val();
                if (!selectedValue) {
                    Swal.showValidationMessage(getText('select_expert_required', 'Please select an expert.'));
                }
                return selectedValue;
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
                               // Update Assigned Expert column: Replace button with expert name (with colored style)
                               button.parent().html('<span class="assigned-expert-name">' + response.data.expert_name + '</span>');
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

    /**
     * Handles the 'Request More Documents' button click for an installment inquiry (on the report page).
     */
    $(document.body).on('click', '.request-more-docs-btn', function() {
        const button = $(this);
        const inquiryId = button.data('inquiry-id');
        const inquiryType = button.data('inquiry-type');
        
        // Get required documents from localized data
        const requiredDocs = (typeof maneliInquiryLists !== 'undefined' && maneliInquiryLists.required_documents) 
                            ? maneliInquiryLists.required_documents 
                            : [];
        
        // Build checkbox list for documents
        let docsHtml = '';
        if (requiredDocs.length > 0) {
            docsHtml = '<div style="text-align: right; margin-top: 15px;"><label style="display: block; margin-bottom: 10px; font-weight: bold;">' + getText('select_documents_label', 'Select Required Documents:') + '</label>';
            requiredDocs.forEach(function(doc, index) {
                docsHtml += '<div class="form-check" style="margin: 10px 0;"><input class="form-check-input" type="checkbox" id="doc-' + index + '" value="' + index + '" style="margin-left: 8px;"><label class="form-check-label" for="doc-' + index + '">' + doc + '</label></div>';
            });
            docsHtml += '</div>';
        }
        
        Swal.fire({
            title: getText('request_docs_title', 'Request More Documents'),
            html: `<div style="text-align: right; font-family: inherit;">
                     ${docsHtml}
                   </div>`,
            confirmButtonText: getText('request_docs_confirm', 'Send Request'),
            showCancelButton: true,
            cancelButtonText: getText('cancel_button'),
            preConfirm: () => {
                const selectedDocs = [];
                requiredDocs.forEach(function(doc, index) {
                    if ($('#doc-' + index).is(':checked')) {
                        selectedDocs.push(doc);
                    }
                });
                return selectedDocs;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                button.prop('disabled', true).text('...');
                
                // Submit via AJAX
                $.post(maneliInquiryLists.ajax_url, {
                    action: 'maneli_request_more_documents',
                    nonce: maneliInquiryLists.nonces.tracking_status || '',
                    inquiry_id: inquiryId,
                    documents: result.value,
                    inquiry_type: inquiryType
                }, function(response) {
                    if (response.success) {
                        Swal.fire(getText('success'), response.data.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire(getText('error'), response.data.message, 'error');
                        button.prop('disabled', false).text(getText('request_docs_title', 'Request More Documents'));
                    }
                }).fail(function() {
                    Swal.fire(getText('error'), getText('server_error'), 'error');
                    button.prop('disabled', false).text(getText('request_docs_title', 'Request More Documents'));
                });
            }
        });
    });

    //======================================================================
    //  AJAX LIST FILTERING & PAGINATION (IMPLEMENTATION OF MISSING LOGIC)
    //======================================================================
    
    // Determine which list is present - declare at function scope
    const installmentFilterForm = $('#maneli-inquiry-filter-form');
    let cashFilterForm = $('#maneli-cash-inquiry-filter-form');
    
    let listType = installmentFilterForm.length ? 'installment' : (cashFilterForm.length ? 'cash' : null);

    // Only set up filter handlers if listType is determined and not cash (cash uses auto-load)
    // For cash inquiries, filters will be attached after auto-load completes
    if (listType && listType === 'installment') {
        const listBody = (listType === 'installment') ? $('#maneli-inquiry-list-tbody') : $('#maneli-cash-inquiry-list-tbody');
        const searchInput = (listType === 'installment') ? $('#inquiry-search-input') : $('#cash-inquiry-search-input');
        const statusFilter = (listType === 'installment') ? $('#status-filter') : $('#cash-inquiry-status-filter');
        const expertFilter = (listType === 'installment') ? $('#expert-filter') : $('#cash-expert-filter');
        const sortFilter = (listType === 'installment') ? $('#inquiry-sort-filter') : $('#cash-inquiry-sort-filter');
        const loader = (listType === 'installment') ? $('#inquiry-list-loader') : $('#cash-inquiry-loading, #cash-inquiry-list-loader');
        const paginationWrapper = (listType === 'installment') ? $('.maneli-pagination-wrapper') : $('#cash-inquiry-pagination');
        const resetButton = (listType === 'installment') ? $('#inquiry-reset-filters') : $('#cash-inquiry-reset-filters');
        const applyButton = (listType === 'installment') ? $('#inquiry-apply-filters') : $('#cash-inquiry-apply-filters');

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
            
            // Also show loading in tbody for cash inquiries
            if (listType === 'cash') {
                if (listBody.find('tr').length === 0 || listBody.find('tr').first().find('td[colspan]').length > 0) {
                    listBody.html('<tr><td colspan="8" class="text-center py-4"><i class="la la-spinner la-spin fs-24 text-muted"></i><p class="mt-2 text-muted">' + getText('loading', 'Loading...') + '</p></td></tr>');
                }
            }

            const ajaxAction = (listType === 'installment') ? 'maneli_filter_inquiries_ajax' : 'maneli_filter_cash_inquiries_ajax';
            const nonce = (listType === 'installment') ? maneliInquiryLists.nonces.inquiry_filter : maneliInquiryLists.nonces.cash_filter;
            
            // Build base URL preserving important query parameters (like 'endp')
            const urlObj = new URL(window.location.href);
            const paramsToRemove = ['inquiry_id', 'cash_inquiry_id', 'paged', 'page'];
            paramsToRemove.forEach(param => urlObj.searchParams.delete(param));
            const baseUrl = urlObj.toString(); 
            const colspan = listType === 'installment' ? 7 : 8; // Number of columns in the table

            // For installment inquiries, check if status value is a tracking_status
            let statusParam = statusFilter.val();
            let trackingStatusParam = '';
            
            if (listType === 'installment') {
                const trackingStatuses = ['new', 'referred', 'in_progress', 'meeting_scheduled', 'follow_up_scheduled', 'cancelled', 'completed', 'rejected'];
                if (statusParam && trackingStatuses.includes(statusParam)) {
                    trackingStatusParam = statusParam;
                    statusParam = ''; // Don't use inquiry_status for tracking statuses
                }
            }
            
            const formData = {
                action: ajaxAction,
                nonce: nonce,  // Changed from _ajax_nonce to nonce for compatibility
                _ajax_nonce: nonce,  // Also send as _ajax_nonce for installment inquiries compatibility
                search: searchInput.val(),
                status: statusParam,
                tracking_status: trackingStatusParam,  // Send tracking_status for installment inquiries
                expert: expertFilter.val(),
                sort: sortFilter.val(),
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
                        if (response.data.html && response.data.html.trim() !== '') {
                            listBody.html(response.data.html);
                            // Update count badge
                            if (listType === 'installment') {
                                const rowCount = listBody.find('tr.crm-contact').length;
                                $('#inquiry-count-badge').text(toPersianNumber(rowCount));
                            }
                        } else {
                            listBody.html(`<tr><td colspan="${colspan}" class="text-center text-muted py-4">${getText('no_inquiries_found', 'No inquiries found.')}</td></tr>`);
                            if (listType === 'installment') {
                                $('#inquiry-count-badge').text('۰');
                            }
                        }
                        if (response.data.pagination_html) {
                            paginationWrapper.html(response.data.pagination_html);
                        }
                    } else {
                        listBody.html(`<tr><td colspan="${colspan}" style="text-align:center;">${getText('error')}: ${response.data.message || getText('unknown_error')}</td></tr>`);
                        paginationWrapper.html('');
                        if (listType === 'installment') {
                            $('#inquiry-count-badge').text('۰');
                        }
                    }
                },
                error: function(jqXHR, textStatus) {
                    if (textStatus !== 'abort') {
                        listBody.html(`<tr><td colspan="${colspan}" style="text-align:center;">${getText('server_error')}</td></tr>`);
                    }
                },
                complete: function() {
                    loader.hide();
                    $('#cash-inquiry-loading, #cash-inquiry-list-loader').hide(); // Ensure both are hidden for cash
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

        // Note: Initial list is loaded by auto-load functions below, so we don't call fetchInquiries here
        // for cash inquiries. For installment, fetchInquiries will be called if auto-load doesn't find the table.

        // Handle search input with a debounce effect (500ms delay)
        searchInput.on('keyup', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                fetchInquiries(1); // Reset to page 1 on new search
            }, 500);
        });

        // Handle change events on filter dropdowns - REMOVED: Filters now work with button only
        // statusFilter.on('change', function() { fetchInquiries(1); });
        // expertFilter.on('change', function() { fetchInquiries(1); });
        // sortFilter.on('change', function() { fetchInquiries(1); });
        
        // Handle reset filters button
        resetButton.on('click', function() {
            searchInput.val('');
            statusFilter.val('');
            expertFilter.val('');
            sortFilter.val('default');
            fetchInquiries(1);
        });
        
        // Handle apply filters button (for manual trigger)
        applyButton.on('click', function() {
            fetchInquiries(1);
        });

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

    //======================================================================
    //  TRACKING STATUS MODAL & CALENDAR INTEGRATION
    //  (Outside listType check - needed on report pages too)
    //======================================================================

    /**
     * Handles the 'Set Status' button click for tracking status changes
     */
    $(document.body).on('click', '.change-tracking-status-btn', function() {
        const button = $(this);
        const inquiryId = button.data('inquiry-id');
        const currentStatus = button.data('current-status') || 'new';
        
        console.log('=== Tracking Status Button Clicked ===');
        console.log('Button:', button);
        console.log('Inquiry ID:', inquiryId);
        console.log('Current Status:', currentStatus);
        
        const modal = $('#tracking-status-modal');
        const statusSelect = $('#tracking-status-select');
        const calendarWrapper = $('#calendar-wrapper');
        const datePicker = $('#tracking-date-picker');
        const calendarLabel = $('#calendar-label');

        console.log('Modal element found:', modal.length > 0);
        console.log('Status Select found:', statusSelect.length > 0);
        console.log('Calendar Wrapper found:', calendarWrapper.length > 0);
        console.log('Date Picker found:', datePicker.length > 0);
        
        if (modal.length === 0) {
            console.error('ERROR: Modal not found in DOM!');
            alert(getText('modal_not_found_error', 'Error: Status modal not found. Please contact support.'));
            return;
        }

        // Set current status
        statusSelect.val(currentStatus);

        // Show modal
        modal.fadeIn(300);

        // Initialize datepicker if not already initialized
        if (!datePicker.data('pdp-init')) {
            if (typeof $.fn.persianDatepicker !== 'undefined') {
                datePicker.persianDatepicker({
                    formatDate: 'YYYY/MM/DD',
                    persianNumbers: true, // Display Persian digits
                    autoClose: true,
                    initialValue: false,
                    observer: false
                });
                datePicker.attr('data-pdp-init', 'true');
            }
        }

        // Handle status change to show/hide calendar
        statusSelect.off('change').on('change', function() {
            const selectedStatus = $(this).val();
            
            if (selectedStatus === 'approved') {
                calendarLabel.text(getText('select_meeting_date', 'Select Meeting Date:'));
                calendarWrapper.show();
                datePicker.val('');
            } else if (selectedStatus === 'follow_up') {
                calendarLabel.text(getText('select_followup_date', 'Select Follow-up Date:'));
                calendarWrapper.show();
                datePicker.val('');
            } else {
                calendarWrapper.hide();
                datePicker.val('');
            }
        });

        // Trigger change to show/hide calendar based on current selection
        statusSelect.trigger('change');

        // Handle confirm button
        $('#confirm-tracking-status-btn').off('click').on('click', function() {
            const selectedStatus = statusSelect.val();
            const dateValue = datePicker.val();

            // Validate: if 'approved' or 'follow_up', date is required
            if ((selectedStatus === 'approved' || selectedStatus === 'follow_up') && !dateValue) {
                Swal.fire(
                    getText('error'),
                    getText('date_required', 'Please select a date.'),
                    'error'
                );
                return;
            }

            // Send AJAX request
            const confirmBtn = $(this);
            const originalText = confirmBtn.text();
            confirmBtn.prop('disabled', true).text('...');

            const ajaxUrl = (typeof maneliInquiryLists !== 'undefined' && maneliInquiryLists.ajax_url) 
                ? maneliInquiryLists.ajax_url 
                : '/wp-admin/admin-ajax.php';
            
            const ajaxNonce = (typeof maneliInquiryLists !== 'undefined' && maneliInquiryLists.nonces && maneliInquiryLists.nonces.tracking_status) 
                ? maneliInquiryLists.nonces.tracking_status 
                : '';
            
            $.post(ajaxUrl, {
                action: 'maneli_update_tracking_status',
                nonce: ajaxNonce,
                inquiry_id: inquiryId,
                tracking_status: selectedStatus,
                date_value: dateValue
            }, function(response) {
                if (response.success) {
                    Swal.fire(getText('success'), response.data.message, 'success').then(() => {
                        location.reload(); // Reload to show updated status
                    });
                } else {
                    Swal.fire(getText('error'), response.data.message || getText('unknown_error'), 'error');
                    confirmBtn.prop('disabled', false).text(originalText);
                }
            }).fail(function() {
                Swal.fire(getText('error'), getText('server_error'), 'error');
                confirmBtn.prop('disabled', false).text(originalText);
            });
        });
    });

    /**
     * Modal close button handler for tracking status modal
     */
    $(document.body).on('click', '#tracking-status-modal .modal-close', function() {
        $('#tracking-status-modal').fadeOut(300);
    });

    // Close modal when clicking outside
    $(document.body).on('click', '#tracking-status-modal', function(e) {
        if ($(e.target).is('#tracking-status-modal')) {
            $(this).fadeOut(300);
        }
    });
    
    //======================================================================
    //  AUTO-LOAD LISTS ON PAGE LOAD
    //======================================================================
    
    /**
     * Wait for maneliInquiryLists to be available
     */
    function waitForManeliInquiryLists(callback, maxAttempts = 50, attempt = 0) {
        // Check if maneliInquiryLists is available with either cash_filter or inquiry_filter
        const hasNonces = typeof maneliInquiryLists !== 'undefined' && 
                          maneliInquiryLists.nonces && 
                          (maneliInquiryLists.nonces.cash_filter || maneliInquiryLists.nonces.inquiry_filter);
        
        console.log('waitForManeliInquiryLists attempt #' + (attempt + 1) + '/' + maxAttempts);
        console.log('maneliInquiryLists defined:', typeof maneliInquiryLists !== 'undefined');
        if (typeof maneliInquiryLists !== 'undefined') {
            console.log('maneliInquiryLists.nonces:', maneliInquiryLists.nonces);
            console.log('has cash_filter:', !!maneliInquiryLists.nonces.cash_filter);
            console.log('has inquiry_filter:', !!maneliInquiryLists.nonces.inquiry_filter);
        }
        
        if (hasNonces) {
            console.log('✓ maneliInquiryLists is ready!', maneliInquiryLists);
            callback();
        } else if (attempt < maxAttempts) {
            setTimeout(function() {
                waitForManeliInquiryLists(callback, maxAttempts, attempt + 1);
            }, 100);
        } else {
            console.error('✗ maneliInquiryLists not available after', maxAttempts, 'attempts');
            console.error('Current state:', typeof maneliInquiryLists !== 'undefined' ? maneliInquiryLists : 'undefined');
            console.error('Will try to proceed anyway with fallback...');
            
            // Try to create fallback - but this won't work for nonce validation
            if (typeof maneliInquiryLists === 'undefined') {
                console.warn('⚠ Creating fallback maneliInquiryLists (will likely fail nonce check)...');
                window.maneliInquiryLists = {
                    ajax_url: '/wp-admin/admin-ajax.php',
                    nonces: {
                        cash_filter: '',
                        inquiry_filter: ''
                    },
                    experts: [],
                    text: {
                        error: 'Error',
                        success: 'Success',
                        server_error: 'Server error. Please try again.',
                        unknown_error: 'Unknown error'
                    }
                };
            }
            // Still call callback - let the error handlers deal with it
            callback();
        }
    }
    
    /**
     * Auto-load cash inquiry list on page load
     */
    console.log('=== CHECKING FOR TABLES ===');
    console.log('Cash inquiry table exists:', $('#maneli-cash-inquiry-list-tbody').length > 0);
    console.log('Installment inquiry table exists:', $('#maneli-inquiry-list-tbody').length > 0);
    console.log('maneliInquiryLists exists:', typeof maneliInquiryLists !== 'undefined');
    if (typeof maneliInquiryLists !== 'undefined') {
        console.log('maneliInquiryLists:', maneliInquiryLists);
    }
    
    if ($('#maneli-cash-inquiry-list-tbody').length > 0) {
        console.log('✓ Cash inquiry table found. Starting wait for maneliInquiryLists...');
        
        waitForManeliInquiryLists(function() {
            console.log('=== Cash Inquiry Auto-Load Debug ===');
            console.log('AJAX URL:', typeof maneliInquiryLists !== 'undefined' && maneliInquiryLists.ajax_url ? maneliInquiryLists.ajax_url : 'NOT DEFINED');
            console.log('AJAX Nonce:', typeof maneliInquiryLists !== 'undefined' && maneliInquiryLists.nonces && maneliInquiryLists.nonces.cash_filter ? 'Present (' + maneliInquiryLists.nonces.cash_filter.substring(0, 10) + '...)' : 'Missing');
            console.log('ManeliInquiryLists object:', typeof maneliInquiryLists !== 'undefined' ? maneliInquiryLists : 'NOT DEFINED');
            console.log('jQuery version:', typeof $ !== 'undefined' ? $.fn.jquery : 'Not loaded');
            
            loadCashInquiriesList();
        });
    } else {
        console.log('✗ Cash inquiry table NOT found');
    }
    
    // Function to load cash inquiries - make it globally accessible
    function loadCashInquiriesList() {
        console.log('🔵 loadCashInquiriesList called');
        // Validate maneliInquiryLists is available
        if (typeof maneliInquiryLists === 'undefined') {
            console.error('maneliInquiryLists is undefined in loadCashInquiriesList');
            $('#maneli-cash-inquiry-list-tbody').html('<tr><td colspan="8" class="text-center text-danger py-4">' + getText('error', 'Error') + ': maneliInquiryLists is not defined. Please refresh the page.</td></tr>');
            return;
        }
        
        // Get nonce - must be available
        let ajaxNonce = '';
        if (maneliInquiryLists.nonces && maneliInquiryLists.nonces.cash_filter) {
            ajaxNonce = maneliInquiryLists.nonces.cash_filter;
        }
        
        if (!ajaxNonce || ajaxNonce === '') {
            console.error('Nonce is missing or empty in loadCashInquiriesList');
            $('#maneli-cash-inquiry-list-tbody').html('<tr><td colspan="8" class="text-center text-danger py-4">' + getText('error', 'Error') + ': nonce is not available. Please refresh the page.</td></tr>');
            return;
        }
        
        // Get AJAX URL
        let ajaxUrl = '/wp-admin/admin-ajax.php';
        if (maneliInquiryLists.ajax_url) {
            ajaxUrl = maneliInquiryLists.ajax_url;
        } else if (typeof ajaxurl !== 'undefined') {
            ajaxUrl = ajaxurl;
        } else {
            // Try to construct from window.location
            const protocol = window.location.protocol;
            const host = window.location.host;
            ajaxUrl = protocol + '//' + host + '/wp-admin/admin-ajax.php';
        }
        
        console.log('Sending AJAX request:', {
            url: ajaxUrl,
            action: 'maneli_filter_cash_inquiries_ajax',
            nonce_present: ajaxNonce ? 'Yes (' + ajaxNonce.substring(0, 10) + '...)' : 'No'
        });
        
        // Hide initial loading state in tbody
        $('#maneli-cash-inquiry-list-tbody').html('<tr><td colspan="8" class="text-center py-2 text-muted">' + getText('loading', 'Loading...') + '</td></tr>');
        
        // Load initial list - Check for status query parameter
        const urlParams = new URLSearchParams(window.location.search);
        const initialStatus = urlParams.get('status') || '';
        
        // Set status filter if provided in URL (wait a bit for DOM to be ready)
        if (initialStatus && $('#cash-inquiry-status-filter').length) {
            $('#cash-inquiry-status-filter').val(initialStatus);
            // Wait a moment for select to update
            setTimeout(function() {
                // Re-read value from DOM to ensure it's set correctly
                const statusFromDOM = $('#cash-inquiry-status-filter').val();
                const requestData = {
                    action: 'maneli_filter_cash_inquiries_ajax',
                    nonce: ajaxNonce,
                    _ajax_nonce: ajaxNonce, // Fallback for compatibility
                    page: 1,
                    search: '',
                    status: statusFromDOM || initialStatus,
                    sort: 'default'
                };
                sendCashInquiryRequest(requestData);
            }, 100);
        } else {
            // No status parameter, use default values from DOM
            const requestData = {
                action: 'maneli_filter_cash_inquiries_ajax',
                nonce: ajaxNonce,
                _ajax_nonce: ajaxNonce, // Fallback for compatibility
                page: 1,
                search: '',
                status: $('#cash-inquiry-status-filter').val() || '',
                sort: $('#cash-inquiry-sort-filter').val() || 'default'
            };
            sendCashInquiryRequest(requestData);
        }
        
        function sendCashInquiryRequest(requestData) {
        
            console.log('=== AJAX REQUEST DATA ===');
            console.log('URL:', ajaxUrl);
            console.log('Action:', requestData.action);
            console.log('Nonce present:', !!ajaxNonce);
            console.log('Nonce value (first 10 chars):', ajaxNonce ? ajaxNonce.substring(0, 10) + '...' : 'MISSING');
            console.log('Full request data:', requestData);
            console.log('Status filter value:', requestData.status);
            
            $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: requestData,
            beforeSend: function() {
                $('#cash-inquiry-loading').show();
                $('#cash-inquiry-list-loader').show();
                $('#maneli-cash-inquiry-list-tbody').html('<tr><td colspan="8" class="text-center py-2"><i class="la la-spinner la-spin fs-24 text-muted"></i><p class="mt-2 text-muted">' + getText('loading', 'Loading...') + '</p></td></tr>');
            },
            success: function(response) {
                console.log('=== Cash Inquiry AJAX SUCCESS ===');
                console.log('Response type:', typeof response);
                console.log('Response:', response);
                console.log('Response.success:', response ? response.success : 'NO RESPONSE');
                console.log('Response.data:', response ? response.data : 'NO DATA');
                
                $('#cash-inquiry-loading').hide();
                $('#cash-inquiry-list-loader').hide();
                
                if (!response) {
                    console.error('ERROR: No response received');
                    $('#maneli-cash-inquiry-list-tbody').html('<tr><td colspan="8" class="text-center text-danger py-4">' + getText('error', 'Error') + ': No response received from server.</td></tr>');
                    return;
                }
                
                if (typeof response !== 'object') {
                    console.error('ERROR: Response is not an object:', typeof response, response);
                    $('#maneli-cash-inquiry-list-tbody').html('<tr><td colspan="8" class="text-center text-danger py-4">' + getText('error', 'Error') + ': Invalid response format.</td></tr>');
                    return;
                }
                
                if (response.success && response.data) {
                    console.log('✓ Response is successful');
                    console.log('HTML length:', response.data.html ? response.data.html.length : 0);
                    console.log('HTML preview:', response.data.html ? response.data.html.substring(0, 200) + '...' : 'EMPTY');
                    
                    if (response.data.html && response.data.html.trim() !== '') {
                        $('#maneli-cash-inquiry-list-tbody').html(response.data.html);
                        // Update count badge
                        const rowCount = $('#maneli-cash-inquiry-list-tbody tr.crm-contact').length;
                        $('#cash-inquiry-count-badge').text(toPersianNumber(rowCount));
                        console.log('✓ Table rows inserted:', rowCount);
                    } else {
                        console.log('⚠ No HTML content - showing empty message');
                        $('#maneli-cash-inquiry-list-tbody').html(`<tr><td colspan="8" class="text-center text-muted py-4">${getText('no_inquiries_found', 'No inquiries found.')}</td></tr>`);
                        $('#cash-inquiry-count-badge').text('۰');
                    }
                    if (response.data.pagination_html) {
                        $('#cash-inquiry-pagination').html(response.data.pagination_html);
                        console.log('✓ Pagination HTML inserted');
                    } else {
                        $('#cash-inquiry-pagination').html('');
                        console.log('⚠ No pagination HTML');
                    }
                } else {
                    console.error('✗ Response unsuccessful');
                    console.error('Response.success:', response.success);
                    console.error('Response.data:', response.data);
                    const errorMsg = (response.data && response.data.message) ? response.data.message : getText('loading_inquiries_error', 'Error loading list');
                    $('#maneli-cash-inquiry-list-tbody').html('<tr><td colspan="8" class="text-center text-danger py-4">' + errorMsg + '</td></tr>');
                    $('#cash-inquiry-count-badge').text('0');
                }
            },
            error: function(xhr, status, error) {
                console.error('=== AJAX Request Failed ===');
                console.error('Status:', status);
                console.error('Error:', error);
                console.error('Status Code:', xhr.status);
                console.error('Ready State:', xhr.readyState);
                
                // Try to parse response if it's JSON
                let responseData = null;
                try {
                    responseData = JSON.parse(xhr.responseText);
                    console.error('Response Data:', responseData);
                } catch(e) {
                    console.error('Response Text (not JSON):', xhr.responseText ? xhr.responseText.substring(0, 200) : 'Empty');
                }
                
                $('#cash-inquiry-loading').hide();
                $('#cash-inquiry-list-loader').hide();
                
                let errorMessage = getText('server_error', 'Server error. Please try again.');
                if (xhr.status === 403) {
                    if (responseData && responseData.data && responseData.data.message) {
                        errorMessage = responseData.data.message;
                    } else {
                        errorMessage = getText('unauthorized_403', 'Unauthorized access (403). Please refresh the page and log in again.');
                    }
                } else if (xhr.status === 0) {
                    errorMessage = getText('connection_error', 'Connection error (0). Please check your internet connection.');
                } else if (xhr.status === 500) {
                    errorMessage = getText('server_error_500', 'Server error (500). Please contact the administrator.');
                } else if (xhr.status > 0) {
                    if (responseData && responseData.data && responseData.data.message) {
                        errorMessage = responseData.data.message;
                    } else {
                        errorMessage = getText('connection_error_code', 'Connection error (Code:') + ' ' + xhr.status + ')';
                    }
                }
                
                $('#maneli-cash-inquiry-list-tbody').html('<tr><td colspan="8" class="text-center text-danger py-4">' + errorMessage + '<br><small>' + getText('please_refresh', 'Please refresh the page.') + '</small></td></tr>');
            }
            });
        }
    }
    
    // Make function globally accessible for template fallback
    window.loadCashInquiriesList = loadCashInquiriesList;
    
    if ($('#maneli-cash-inquiry-list-tbody').length === 0) {
        console.log('Cash inquiry table not found. Skipping auto-load.');
    }
    
    // Setup filter handlers for cash inquiries - wait for everything to be ready
    console.log('Setting up cash inquiry filter handlers...');
    // Re-initialize cashFilterForm to ensure it's available
    cashFilterForm = $('#maneli-cash-inquiry-filter-form');
    console.log('cashFilterForm found:', cashFilterForm.length > 0);
    
    if (cashFilterForm.length > 0) {
        console.log('Cash filter form found, attaching handlers...');
        const cashSearchInput = $('#cash-inquiry-search-input');
        const cashStatusFilter = $('#cash-inquiry-status-filter');
        const cashExpertFilter = $('#cash-expert-filter');
        const cashSortFilter = $('#cash-inquiry-sort-filter');
        const cashResetButton = $('#cash-inquiry-reset-filters');
        const cashApplyButton = $('#cash-inquiry-apply-filters');
        const cashListBody = $('#maneli-cash-inquiry-list-tbody');
        const cashPaginationWrapper = $('#cash-inquiry-pagination');
        const cashLoader = $('#cash-inquiry-loading, #cash-inquiry-list-loader');
        
        // Make variables accessible in the function scope
        let cashXhr = null;
        let cashSearchTimeout = null;
        
        // Store references globally for debugging
        window.maneliCashInquiryRefs = {
            searchInput: cashSearchInput,
            statusFilter: cashStatusFilter,
            expertFilter: cashExpertFilter,
            sortFilter: cashSortFilter,
            listBody: cashListBody,
            paginationWrapper: cashPaginationWrapper,
            loader: cashLoader
        };
        
        // Function to fetch cash inquiries - make it accessible globally
        window.fetchCashInquiries = function(page = 1) {
            console.log('=== fetchCashInquiries START ===');
            console.log('Page:', page);
            console.log('fetchCashInquiries called with page:', page);
            
            // Use global reference for XHR
            if (window.maneliCashXhr && window.maneliCashXhr.readyState !== 4) {
                window.maneliCashXhr.abort();
            }
            window.maneliCashXhr = null;
            
            // Get references again to ensure they exist
            const $listBody = $('#maneli-cash-inquiry-list-tbody');
            const $paginationWrapper = $('#cash-inquiry-pagination');
            const $loader = $('#cash-inquiry-loading, #cash-inquiry-list-loader');
            
            // Check if maneliInquiryLists is available
            if (typeof maneliInquiryLists === 'undefined' || !maneliInquiryLists.nonces || !maneliInquiryLists.nonces.cash_filter) {
                console.error('maneliInquiryLists not available in fetchCashInquiries!', typeof maneliInquiryLists);
                if ($listBody.length) {
                    $listBody.html('<tr><td colspan="8" class="text-center text-danger py-4">' + getText('error', 'Error') + ': maneliInquiryLists is not available. Please refresh the page.</td></tr>');
                }
                if ($loader.length) $loader.hide();
                if ($listBody.length) $listBody.css('opacity', 1);
                if ($paginationWrapper.length) $paginationWrapper.css('opacity', 1);
                console.log('=== fetchCashInquiries END (error) ===');
                return;
            }
            
            console.log('maneliInquiryLists OK:', {
                ajax_url: maneliInquiryLists.ajax_url,
                has_nonce: !!maneliInquiryLists.nonces.cash_filter
            });
            
            if ($loader.length) $loader.show();
            if ($listBody.length) $listBody.css('opacity', 0.5);
            if ($paginationWrapper.length) $paginationWrapper.css('opacity', 0.5);
            
            const urlObj = new URL(window.location.href);
            const paramsToRemove = ['inquiry_id', 'cash_inquiry_id', 'paged', 'page'];
            paramsToRemove.forEach(param => urlObj.searchParams.delete(param));
            const baseUrl = urlObj.toString();
            
            // Get current values from DOM
            const searchVal = $('#cash-inquiry-search-input').val() || '';
            const statusVal = $('#cash-inquiry-status-filter').val() || '';
            const expertVal = $('#cash-expert-filter').length ? $('#cash-expert-filter').val() || '' : '';
            const sortVal = $('#cash-inquiry-sort-filter').val() || 'default';
            
            console.log('Sending AJAX request for cash inquiries:', {
                url: maneliInquiryLists.ajax_url,
                search: searchVal,
                status: statusVal,
                expert: expertVal,
                sort: sortVal,
                page: page
            });
            
            window.maneliCashXhr = $.ajax({
                url: maneliInquiryLists.ajax_url,
                type: 'POST',
                data: {
                    action: 'maneli_filter_cash_inquiries_ajax',
                    nonce: maneliInquiryLists.nonces.cash_filter,
                    _ajax_nonce: maneliInquiryLists.nonces.cash_filter,
                    search: searchVal,
                    status: statusVal,
                    expert: expertVal,
                    sort: sortVal,
                    page: page,
                    base_url: baseUrl
                },
                success: function(response) {
                    console.log('AJAX Success Response:', response);
                    if (response.success && response.data) {
                        if (response.data.html && response.data.html.trim() !== '') {
                            if ($listBody.length) {
                                $listBody.html(response.data.html);
                                // Update count badge
                                const rowCount = $listBody.find('tr.crm-contact').length;
                                $('#cash-inquiry-count-badge').text(toPersianNumber(rowCount));
                            }
                        } else {
                            if ($listBody.length) {
                                $listBody.html(`<tr><td colspan="8" class="text-center text-muted">${getText('no_inquiries_found', 'No inquiries found.')}</td></tr>`);
                            }
                            $('#cash-inquiry-count-badge').text('۰');
                        }
                        if ($paginationWrapper.length) {
                            $paginationWrapper.html(response.data.pagination_html || '');
                        }
                    } else {
                        if ($listBody.length) {
                            $listBody.html('<tr><td colspan="8" class="text-center text-danger">' + (response.data?.message || getText('loading_inquiries_error', 'Error loading list')) + '</td></tr>');
                        }
                        $('#cash-inquiry-count-badge').text('۰');
                    }
                    console.log('=== fetchCashInquiries END (success) ===');
                },
                error: function(xhr, status, error) {
                    console.error('=== AJAX ERROR ===');
                    console.error('Status:', status);
                    console.error('Error:', error);
                    console.error('Response:', xhr.responseText);
                    if (status !== 'abort') {
                        if ($listBody.length) {
                            $listBody.html('<tr><td colspan="8" class="text-center text-danger">' + getText('server_error', 'Server error. Please try again.') + '</td></tr>');
                        }
                    }
                    console.log('=== fetchCashInquiries END (error) ===');
                },
                complete: function() {
                    if ($loader.length) $loader.hide();
                    if ($listBody.length) $listBody.css('opacity', 1);
                    if ($paginationWrapper.length) $paginationWrapper.css('opacity', 1);
                    const $expertFilter = $('#cash-expert-filter');
                    if ($expertFilter.length && $expertFilter.is(':visible') && typeof $.fn.select2 !== 'undefined') {
                        $expertFilter.select2({ width: '100%' });
                    }
                }
            });
        }
        
        // Attach filter handlers for cash inquiries using event delegation for reliability
        console.log('Attaching event handlers for cash inquiry filters...');
        
        // Use document.on for better reliability with current values
        $(document).off('keyup', '#cash-inquiry-search-input').on('keyup', '#cash-inquiry-search-input', function() {
            if (window.maneliCashSearchTimeout) {
                clearTimeout(window.maneliCashSearchTimeout);
            }
            window.maneliCashSearchTimeout = setTimeout(function() {
                console.log('Search input changed, fetching...');
                if (typeof window.fetchCashInquiries === 'function') {
                    window.fetchCashInquiries(1);
                } else {
                    console.error('fetchCashInquiries function not available!');
                }
            }, 500);
        });
        
        // REMOVED: Change event listeners for cash filters - now work with button only
        // $(document).off('change', '#cash-inquiry-status-filter').on('change', '#cash-inquiry-status-filter', function() { 
        //     console.log('Status filter changed, fetching...');
        //     if (typeof window.fetchCashInquiries === 'function') {
        //         window.fetchCashInquiries(1);
        //     } else {
        //         console.error('fetchCashInquiries function not available!');
        //     }
        // });
        // 
        // if ($('#cash-expert-filter').length) {
        //     $(document).off('change', '#cash-expert-filter').on('change', '#cash-expert-filter', function() { 
        //         console.log('Expert filter changed, fetching...');
        //         if (typeof window.fetchCashInquiries === 'function') {
        //             window.fetchCashInquiries(1);
        //         } else {
        //             console.error('fetchCashInquiries function not available!');
        //         }
        //     });
        // }
        // 
        // $(document).off('change', '#cash-inquiry-sort-filter').on('change', '#cash-inquiry-sort-filter', function() { 
        //     console.log('Sort filter changed, fetching...');
        //     if (typeof window.fetchCashInquiries === 'function') {
        //         window.fetchCashInquiries(1);
        //     } else {
        //         console.error('fetchCashInquiries function not available!');
        //     }
        // });
        
        $(document).off('click', '#cash-inquiry-reset-filters').on('click', '#cash-inquiry-reset-filters', function(e) {
            e.preventDefault();
            console.log('Reset filters clicked');
            $('#cash-inquiry-search-input').val('');
            $('#cash-inquiry-status-filter').val('');
            if ($('#cash-expert-filter').length) $('#cash-expert-filter').val('');
            $('#cash-inquiry-sort-filter').val('default');
            if (typeof window.fetchCashInquiries === 'function') {
                window.fetchCashInquiries(1);
            } else {
                console.error('fetchCashInquiries function not available!');
            }
        });
        
        $(document).off('click', '#cash-inquiry-apply-filters').on('click', '#cash-inquiry-apply-filters', function(e) {
            e.preventDefault();
            console.log('Apply filters clicked');
            if (typeof window.fetchCashInquiries === 'function') {
                window.fetchCashInquiries(1);
            } else {
                console.error('fetchCashInquiries function not available!');
            }
        });
        
        console.log('Event handlers attached successfully for cash inquiry filters.');
        
        // Handle pagination using event delegation
        $(document).off('click', '#cash-inquiry-pagination a.page-numbers').on('click', '#cash-inquiry-pagination a.page-numbers', function(e) {
            e.preventDefault();
            console.log('Pagination clicked');
            const pageUrl = $(this).attr('href');
            let pageNum = 1;
            const matches = pageUrl.match(/paged=(\d+)/);
            if (matches) {
                pageNum = parseInt(matches[1], 10);
            }
            if (typeof window.fetchCashInquiries === 'function') {
                window.fetchCashInquiries(pageNum);
            } else {
                console.error('fetchCashInquiries function not available!');
            }
        });
        
        console.log('Cash inquiry filter setup complete!');
    } else {
        console.warn('Cash filter form not found! (#maneli-cash-inquiry-filter-form)');
    }
    
    /**
     * Auto-load installment inquiry list on page load
     */
    if ($('#maneli-inquiry-list-tbody').length > 0) {
        console.log('✓ Installment inquiry table found. Starting wait for maneliInquiryLists...');
        
        waitForManeliInquiryLists(function() {
            console.log('=== Installment Inquiry Auto-Load Debug ===');
            console.log('AJAX URL:', typeof maneliInquiryLists !== 'undefined' && maneliInquiryLists.ajax_url ? maneliInquiryLists.ajax_url : 'NOT DEFINED');
            console.log('AJAX Nonce:', typeof maneliInquiryLists !== 'undefined' && maneliInquiryLists.nonces && maneliInquiryLists.nonces.inquiry_filter ? 'Present (' + maneliInquiryLists.nonces.inquiry_filter.substring(0, 10) + '...)' : 'Missing');
            console.log('ManeliInquiryLists object:', typeof maneliInquiryLists !== 'undefined' ? maneliInquiryLists : 'NOT DEFINED');
            console.log('jQuery version:', typeof $ !== 'undefined' ? $.fn.jquery : 'Not loaded');
            
            loadInstallmentInquiriesList();
        });
    } else {
        console.log('✗ Installment inquiry table NOT found');
    }
    
    function loadInstallmentInquiriesList() {
        // Validate maneliInquiryLists is available
        if (typeof maneliInquiryLists === 'undefined') {
            console.error('maneliInquiryLists is undefined in loadInstallmentInquiriesList');
            $('#maneli-inquiry-list-tbody').html('<tr><td colspan="7" class="text-center text-danger py-4">' + getText('error', 'Error') + ': maneliInquiryLists is not defined. Please refresh the page.</td></tr>');
            return;
        }
        
        // Get nonce - must be available
        let ajaxNonce = '';
        if (maneliInquiryLists.nonces && maneliInquiryLists.nonces.inquiry_filter) {
            ajaxNonce = maneliInquiryLists.nonces.inquiry_filter;
        }
        
        if (!ajaxNonce || ajaxNonce === '') {
            console.error('Nonce is missing or empty in loadInstallmentInquiriesList');
            $('#maneli-inquiry-list-tbody').html('<tr><td colspan="7" class="text-center text-danger py-4">' + getText('error', 'Error') + ': nonce is not available. Please refresh the page.</td></tr>');
            return;
        }
        
        // Get AJAX URL
        let ajaxUrl = '/wp-admin/admin-ajax.php';
        if (maneliInquiryLists.ajax_url) {
            ajaxUrl = maneliInquiryLists.ajax_url;
        } else if (typeof ajaxurl !== 'undefined') {
            ajaxUrl = ajaxurl;
        } else {
            // Try to construct from window.location
            const protocol = window.location.protocol;
            const host = window.location.host;
            ajaxUrl = protocol + '//' + host + '/wp-admin/admin-ajax.php';
        }
        
        console.log('Sending AJAX request for installment inquiries:', {
            url: ajaxUrl,
            action: 'maneli_filter_inquiries_ajax',
            nonce_present: ajaxNonce ? 'Yes (' + ajaxNonce.substring(0, 10) + '...)' : 'No'
        });
        
        // Load initial list - Check for status query parameter
        const urlParams = new URLSearchParams(window.location.search);
        const initialStatus = urlParams.get('status') || '';
        
        // For installment inquiries, if status is 'referred', it's actually a tracking_status
        // Map tracking_status values to appropriate handling
        let statusParam = initialStatus;
        let trackingStatusParam = '';
        
        // Check if this is a tracking_status value
        const trackingStatuses = ['new', 'referred', 'in_progress', 'meeting_scheduled', 'follow_up_scheduled', 'cancelled', 'completed', 'rejected'];
        if (initialStatus && trackingStatuses.includes(initialStatus)) {
            trackingStatusParam = initialStatus;
            statusParam = ''; // Don't use inquiry_status for tracking statuses
            console.log('✓ Detected tracking_status from URL:', initialStatus);
            // Note: We can't set a filter value for tracking_status as there's no dropdown for it in the template
            // But we'll send it to the AJAX handler which will filter correctly
        } else if (initialStatus) {
            console.log('⚠ Status from URL is not a tracking_status:', initialStatus);
        }
        
        // Set status filter if provided in URL (but tracking_status takes priority)
        if (statusParam && $('#status-filter').length) {
            $('#status-filter').val(statusParam);
        }
        
        // Wait a moment for DOM to be ready, then send request
        setTimeout(function() {
            // Re-read values from DOM to ensure they're set correctly
            const statusFromDOM = $('#status-filter').val() || statusParam;
            const finalStatusParam = statusFromDOM;
            
            const installmentRequestData = {
                action: 'maneli_filter_inquiries_ajax',
                nonce: ajaxNonce,
                _ajax_nonce: ajaxNonce,  // Also send as _ajax_nonce for compatibility
                page: 1,
                search: '',
                status: finalStatusParam,
                tracking_status: trackingStatusParam,  // Send tracking_status separately
                sort: $('#inquiry-sort-filter').val() || 'default'
            };
            
            console.log('=== AJAX REQUEST DATA (Installment) ===');
            console.log('URL:', ajaxUrl);
            console.log('Action:', installmentRequestData.action);
            console.log('Nonce present:', !!ajaxNonce);
            console.log('Nonce value (first 10 chars):', ajaxNonce ? ajaxNonce.substring(0, 10) + '...' : 'MISSING');
            console.log('Full request data:', installmentRequestData);
            console.log('Initial status from URL:', initialStatus);
            console.log('Status from DOM:', statusFromDOM);
            console.log('Tracking status param:', trackingStatusParam);
            
            sendInstallmentInquiryRequest(installmentRequestData);
        }, 100);
        
        function sendInstallmentInquiryRequest(installmentRequestData) {
            $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: installmentRequestData,
            success: function(response) {
                console.log('=== Installment Inquiry AJAX SUCCESS ===');
                console.log('Response type:', typeof response);
                console.log('Response:', response);
                console.log('Response.success:', response ? response.success : 'NO RESPONSE');
                console.log('Response.data:', response ? response.data : 'NO DATA');
                
                if (!response) {
                    console.error('ERROR: No response received');
                    $('#maneli-inquiry-list-tbody').html('<tr><td colspan="7" class="text-center text-danger py-4">' + getText('error', 'Error') + ': No response received from server.</td></tr>');
                    return;
                }
                
                if (typeof response !== 'object') {
                    console.error('ERROR: Response is not an object:', typeof response, response);
                    $('#maneli-inquiry-list-tbody').html('<tr><td colspan="7" class="text-center text-danger py-4">' + getText('error', 'Error') + ': Invalid response format.</td></tr>');
                    return;
                }
                
                if (response.success && response.data) {
                    console.log('✓ Response is successful');
                    console.log('HTML length:', response.data.html ? response.data.html.length : 0);
                    console.log('HTML preview:', response.data.html ? response.data.html.substring(0, 200) + '...' : 'EMPTY');
                    
                    if (response.data.html && response.data.html.trim() !== '') {
                        $('#maneli-inquiry-list-tbody').html(response.data.html);
                        // Update count badge - count actual rows (not loading messages)
                        const rowCount = $('#maneli-inquiry-list-tbody tr.crm-contact').length;
                        const hasContent = rowCount > 0;
                        console.log('✓ HTML received, checking for rows...');
                        console.log('✓ Total tr elements:', $('#maneli-inquiry-list-tbody tr').length);
                        console.log('✓ Rows with crm-contact class:', rowCount);
                        console.log('✓ HTML preview:', response.data.html.substring(0, 500));
                        
                        if (hasContent) {
                            $('#inquiry-count-badge').text(toPersianNumber(rowCount));
                            console.log('✓ Table rows inserted:', rowCount);
                            console.log('✓ Badge updated to:', toPersianNumber(rowCount));
                        } else {
                            $('#inquiry-count-badge').text('۰');
                            console.log('⚠ No inquiry rows found in HTML');
                            console.log('⚠ Full HTML content:', response.data.html);
                        }
                    } else {
                        console.log('⚠ No HTML content - showing empty message');
                        $('#maneli-inquiry-list-tbody').html(`<tr><td colspan="7" class="text-center text-muted py-4">${getText('no_inquiries_found', 'No inquiries found.')}</td></tr>`);
                        $('#inquiry-count-badge').text('۰');
                    }
                    if (response.data.pagination_html) {
                        $('#inquiry-pagination').html(response.data.pagination_html);
                        $('.maneli-pagination-wrapper').html(response.data.pagination_html);
                        console.log('✓ Pagination HTML inserted');
                    } else {
                        $('#inquiry-pagination').html('');
                        $('.maneli-pagination-wrapper').html('');
                        console.log('⚠ No pagination HTML');
                    }
                } else {
                    console.error('✗ Response unsuccessful');
                    console.error('Response.success:', response.success);
                    console.error('Response.data:', response.data);
                    const errorMsg = (response.data && response.data.message) ? response.data.message : getText('loading_inquiries_error', 'Error loading list');
                    $('#maneli-inquiry-list-tbody').html('<tr><td colspan="7" class="text-center text-danger py-4">' + errorMsg + '</td></tr>');
                    $('#inquiry-count-badge').text('0');
                }
            },
            error: function(xhr, status, error) {
                console.error('=== AJAX Request Failed (Installment) ===');
                console.error('Status:', status);
                console.error('Error:', error);
                console.error('Status Code:', xhr.status);
                console.error('Ready State:', xhr.readyState);
                
                // Try to parse response if it's JSON
                let responseData = null;
                try {
                    responseData = JSON.parse(xhr.responseText);
                    console.error('Response Data:', responseData);
                } catch(e) {
                    console.error('Response Text (not JSON):', xhr.responseText ? xhr.responseText.substring(0, 200) : 'Empty');
                }
                
                let errorMessage = getText('server_error', 'Server error. Please try again.');
                if (xhr.status === 403) {
                    if (responseData && responseData.data && responseData.data.message) {
                        errorMessage = responseData.data.message;
                    } else {
                        errorMessage = getText('unauthorized_403', 'Unauthorized access (403). Please refresh the page and log in again.');
                    }
                } else if (xhr.status === 0) {
                    errorMessage = getText('connection_error', 'Connection error (0). Please check your internet connection.');
                } else if (xhr.status === 500) {
                    errorMessage = getText('server_error_500', 'Server error (500). Please contact the administrator.');
                } else if (xhr.status > 0) {
                    if (responseData && responseData.data && responseData.data.message) {
                        errorMessage = responseData.data.message;
                    } else {
                        errorMessage = getText('connection_error_code', 'Connection error (Code:') + ' ' + xhr.status + ')';
                    }
                }
                
                $('#maneli-inquiry-list-tbody').html('<tr><td colspan="7" class="text-center text-danger py-4">' + errorMessage + '<br><small>' + getText('please_refresh', 'Please refresh the page.') + '</small></td></tr>');
                $('#inquiry-count-badge').text('0');
            }
            });
        }
    }
    
    // Make function globally accessible for template fallback
    window.loadInstallmentInquiriesList = loadInstallmentInquiriesList;

    //======================================================================
    //  REPORT PAGE EVENT HANDLERS (for both cash and installment)
    //======================================================================

    /**
     * Save expert note for installment inquiry
     */
    $(document.body).on('submit', '#installment-expert-note-form', function(e) {
        e.preventDefault();
        const inquiryId = $('#installment-inquiry-details').data('inquiry-id');
        const note = $('#installment-expert-note-input').val().trim();
        
        console.log('🔵 SAVE INSTALLMENT NOTE:', {
            inquiryId: inquiryId,
            noteLength: note.length,
            maneliInquiryLists: typeof maneliInquiryLists !== 'undefined' ? 'DEFINED' : 'UNDEFINED',
            save_note_nonce: maneliInquiryLists?.nonces?.save_installment_note || 'MISSING'
        });
        
        if (!note) {
            Swal.fire({
                title: getText('attention', 'Attention!'),
                text: getText('please_enter_note', 'Please enter your note.'),
                icon: 'warning',
                confirmButtonText: getText('ok_button', 'OK')
            });
            return;
        }
        
        const nonce = maneliInquiryLists.nonces.save_installment_note || maneliInquiryLists.nonces.tracking_status || '';
        console.log('🔵 Using nonce:', nonce ? 'PRESENT' : 'EMPTY');
        
        $.ajax({
            url: maneliInquiryLists.ajax_url,
            type: 'POST',
            data: {
                action: 'maneli_save_installment_note',
                nonce: nonce,
                inquiry_id: inquiryId,
                note: note
            }
        }).done(function(response) {
            console.log('🔵 AJAX Response:', response);
            if (response.success) {
                Swal.fire({
                    title: getText('success', 'Success') + '!',
                    text: getText('note_saved_success', 'Note saved successfully.'),
                    icon: 'success',
                    confirmButtonText: getText('ok_button', 'OK'),
                    timer: 1500
                }).then(() => location.reload());
            } else {
                Swal.fire({
                    title: getText('error', 'Error') + '!',
                    text: response.data?.message || getText('error_occurred', 'An error occurred.'),
                    icon: 'error',
                    confirmButtonText: getText('ok_button', 'OK')
                });
            }
        }).fail(function(xhr) {
            console.error('🔵 AJAX Error:', xhr.responseJSON);
            Swal.fire({
                title: getText('error', 'Error') + '!',
                text: xhr.responseJSON?.data?.message || getText('error_occurred', 'An error occurred.'),
                icon: 'error',
                confirmButtonText: getText('ok_button', 'OK')
            });
        });
    });

    /**
     * Save expert note for cash inquiry
     */
    $(document.body).on('submit', '#cash-expert-note-form', function(e) {
        e.preventDefault();
        const inquiryId = $('.frontend-expert-report').data('inquiry-id');
        const note = $('#cash-expert-note-input').val().trim();
        
        if (!note) {
            Swal.fire({
                title: getText('attention', 'Attention!'),
                text: getText('please_enter_note', 'Please enter your note.'),
                icon: 'warning',
                confirmButtonText: getText('ok_button', 'OK')
            });
            return;
        }
        
        $.ajax({
            url: maneliInquiryLists.ajax_url,
            type: 'POST',
            data: {
                action: 'maneli_save_expert_note',
                inquiry_id: inquiryId,
                note: note,
                nonce: maneliInquiryLists.nonces.save_expert_note || maneliInquiryLists.nonces.cash_update || ''
            }
        }).done(function(response) {
            if (response.success) {
                Swal.fire({
                    title: getText('success', 'Success') + '!',
                    text: getText('note_saved_success', 'Note saved successfully.'),
                    icon: 'success',
                    confirmButtonText: getText('ok_button', 'OK'),
                    timer: 1500
                }).then(() => location.reload());
            } else {
                Swal.fire({
                    title: getText('error', 'Error') + '!',
                    text: response.data?.message || getText('error_occurred', 'An error occurred.'),
                    icon: 'error',
                    confirmButtonText: getText('ok_button', 'OK')
                });
            }
        }).fail(function(xhr) {
            Swal.fire({
                title: getText('error', 'Error') + '!',
                text: xhr.responseJSON?.data?.message || getText('error_occurred', 'An error occurred.'),
                icon: 'error',
                confirmButtonText: getText('ok_button', 'OK')
            });
        });
    });

    /**
     * Handle installment status buttons
     */
    $(document.body).on('click', '.installment-status-btn', function() {
        const button = $(this);
        const action = button.data('action');
        const inquiryId = $('#installment-inquiry-details').data('inquiry-id');
        
        if (!action || !inquiryId) return;
        
        // Handle different actions
        if (action === 'schedule_meeting') {
            // Get meeting settings first
            jQuery.ajax({
                url: maneli_ajax_object.ajaxurl,
                type: 'POST',
                data: {
                    action: 'maneli_get_meeting_settings',
                    nonce: maneli_ajax_object.nonce || ''
                },
                success: function(response) {
                    if (response.success) {
                        const settings = response.data;
                        const startHour = settings.start_hour || '10:00';
                        const endHour = settings.end_hour || '20:00';
                        
                        Swal.fire({
                            title: getText('schedule_meeting_title', 'Schedule Meeting'),
                            html: `
                                <div class="text-start">
                                    <label class="form-label">${getText('meeting_date_label', 'Meeting Date')}:</label>
                                    <input type="text" id="swal-meeting-date" class="form-control mb-3 maneli-datepicker" placeholder="${getText('select_date', 'Select Date')}">
                                    <label class="form-label">${getText('meeting_time_label', 'Meeting Time')}:</label>
                                    <input type="time" id="swal-meeting-time" class="form-control" min="${startHour}" max="${endHour}" step="1800">
                                    <small class="text-muted d-block mt-1">${getText('time_range_hint', 'Time must be between')} ${startHour} ${getText('and', 'and')} ${endHour}</small>
                                </div>
                            `,
                            showCancelButton: true,
                            confirmButtonText: getText('schedule_button', 'Schedule'),
                            cancelButtonText: getText('cancel_button', 'Cancel'),
                            confirmButtonColor: '#28a745',
                            didOpen: () => {
                                if (typeof $.fn.persianDatepicker !== 'undefined') {
                                    $('#swal-meeting-date').persianDatepicker({
                                        formatDate: 'YYYY/MM/DD',
                                        persianNumbers: true,
                                        autoClose: true
                                    });
                                }
                            },
                            preConfirm: () => {
                                const meetingDate = $('#swal-meeting-date').val();
                                const meetingTime = $('#swal-meeting-time').val();
                                if (!meetingDate || !meetingTime) {
                                    Swal.showValidationMessage(getText('meeting_required', 'Please enter meeting date and time'));
                                    return false;
                                }
                                
                                // Validate time range
                                const [timeHour, timeMin] = meetingTime.split(':').map(Number);
                                const [startHourNum, startMinNum] = startHour.split(':').map(Number);
                                const [endHourNum, endMinNum] = endHour.split(':').map(Number);
                                
                                const timeMinutes = timeHour * 60 + timeMin;
                                const startMinutes = startHourNum * 60 + startMinNum;
                                const endMinutes = endHourNum * 60 + endMinNum;
                                
                                if (timeMinutes < startMinutes || timeMinutes >= endMinutes) {
                                    Swal.showValidationMessage(getText('time_outside_range', 'Selected time is outside allowed working hours.') + ' (' + startHour + ' - ' + endHour + ')');
                                    return false;
                                }
                                
                                return { meeting_date: meetingDate, meeting_time: meetingTime };
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                updateInstallmentStatus(inquiryId, action, result.value);
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: getText('error_title', 'Error'),
                            text: response.data?.message || getText('unknown_error', 'Unknown error occurred')
                        });
                    }
                },
                error: function() {
                    // Fallback without restrictions if AJAX fails
                    Swal.fire({
                        title: getText('schedule_meeting_title', 'Schedule Meeting'),
                        html: `
                            <div class="text-start">
                                <label class="form-label">${getText('meeting_date_label', 'Meeting Date')}:</label>
                                <input type="text" id="swal-meeting-date" class="form-control mb-3 maneli-datepicker" placeholder="${getText('select_date', 'Select Date')}">
                                <label class="form-label">${getText('meeting_time_label', 'Meeting Time')}:</label>
                                <input type="time" id="swal-meeting-time" class="form-control">
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: getText('schedule_button', 'Schedule'),
                        cancelButtonText: getText('cancel_button', 'Cancel'),
                        confirmButtonColor: '#28a745',
                        didOpen: () => {
                            if (typeof $.fn.persianDatepicker !== 'undefined') {
                                $('#swal-meeting-date').persianDatepicker({
                                    formatDate: 'YYYY/MM/DD',
                                    persianNumbers: true,
                                    autoClose: true
                                });
                            }
                        },
                        preConfirm: () => {
                            const meetingDate = $('#swal-meeting-date').val();
                            const meetingTime = $('#swal-meeting-time').val();
                            if (!meetingDate || !meetingTime) {
                                Swal.showValidationMessage(getText('meeting_required', 'Please enter meeting date and time'));
                                return false;
                            }
                            return { meeting_date: meetingDate, meeting_time: meetingTime };
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            updateInstallmentStatus(inquiryId, action, result.value);
                        }
                    });
                }
            });
        } else if (action === 'schedule_followup') {
            Swal.fire({
                title: getText('schedule_followup_title', 'Schedule Follow-up'),
                html: `
                    <div class="text-start">
                        <label class="form-label">${getText('followup_date_label', 'Follow-up Date')}:</label>
                        <input type="text" id="swal-followup-date" class="form-control mb-3 maneli-datepicker" placeholder="${getText('select_date', 'Select Date')}">
                        <label class="form-label">${getText('note_label_optional', 'Note (Optional)')}:</label>
                        <textarea id="swal-followup-note" class="form-control" rows="3" placeholder="${getText('enter_note', 'Enter your note...')}"></textarea>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: getText('schedule_followup_button', 'Schedule Follow-up'),
                cancelButtonText: getText('cancel_button', 'Cancel'),
                confirmButtonColor: '#ffc107',
                width: '600px',
                didOpen: () => {
                    if (typeof $.fn.persianDatepicker !== 'undefined') {
                        $('#swal-followup-date').persianDatepicker({
                            formatDate: 'YYYY/MM/DD',
                            persianNumbers: true,
                            autoClose: true
                        });
                    }
                },
                preConfirm: () => {
                    const followupDate = $('#swal-followup-date').val();
                    const followupNote = $('#swal-followup-note').val();
                    if (!followupDate) {
                        Swal.showValidationMessage(getText('followup_date_required', 'Please enter follow-up date'));
                        return false;
                    }
                    return { followup_date: followupDate, followup_note: followupNote };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    updateInstallmentStatus(inquiryId, action, result.value);
                }
            });
        } else if (action === 'cancel' || action === 'reject') {
            const title = action === 'cancel' ? getText('cancel_inquiry_title', 'Cancel Inquiry') : getText('reject_title', 'Reject Inquiry');
            const reasonLabel = action === 'cancel' ? getText('cancel_reason_label', 'Cancellation Reason') : getText('rejection_reason_label', 'Rejection Reason');
            Swal.fire({
                title: title,
                html: `
                    <div class="text-start">
                        <label class="form-label">${reasonLabel}:</label>
                        <textarea id="swal-reason" class="form-control" rows="4" placeholder="${getText('enter_reason', 'Please enter reason...')}"></textarea>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: title,
                cancelButtonText: getText('cancel_button', 'Cancel'),
                confirmButtonColor: '#dc3545',
                width: '600px',
                preConfirm: () => {
                    const reason = $('#swal-reason').val();
                    if (!reason || reason.trim().length < 10) {
                        Swal.showValidationMessage(getText('reason_required', 'Please enter reason with at least 10 characters'));
                        return false;
                    }
                    return action === 'cancel' ? { cancel_reason: reason } : { rejection_reason: reason };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    updateInstallmentStatus(inquiryId, action, result.value);
                }
            });
        } else if (action === 'approve') {
            // Simple confirmation for approve
            Swal.fire({
                title: getText('approve_title', 'Approve Inquiry'),
                text: getText('approve_confirm', 'Are you sure you want to approve this inquiry?'),
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: getText('approve_button', 'Approve'),
                cancelButtonText: getText('cancel_button', 'Cancel'),
                confirmButtonColor: '#28a745'
            }).then((result) => {
                if (result.isConfirmed) {
                    updateInstallmentStatus(inquiryId, action);
                }
            });
        } else {
            // Simple confirmation for other actions (start_progress, complete)
            const confirmText = action === 'start_progress' ? getText('start_progress_title', 'Start Follow-up') : getText('complete_title', 'Complete Inquiry');
            const confirmMessage = action === 'start_progress' ? getText('start_progress_confirm', 'Are you sure you want to start follow-up for this inquiry?') : getText('complete_confirm', 'Are you sure you want to complete this inquiry?');
            Swal.fire({
                title: confirmText,
                text: confirmMessage,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: getText('confirm_button', 'Yes'),
                cancelButtonText: getText('cancel_button', 'Cancel'),
                confirmButtonColor: '#3085d6'
            }).then((result) => {
                if (result.isConfirmed) {
                    updateInstallmentStatus(inquiryId, action);
                }
            });
        }
    });

    /**
     * Handle cancel meeting button click for installment inquiries
     */
    $(document.body).on('click', '.installment-cancel-meeting-btn', function() {
        const button = $(this);
        const inquiryId = button.data('inquiry-id');
        const inquiryType = button.data('inquiry-type') || 'installment';
        
        if (!inquiryId) return;
        
        Swal.fire({
            title: getText('cancel_meeting_title', 'Cancel Meeting'),
            text: getText('cancel_meeting_confirm', 'Are you sure you want to cancel this meeting?'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: getText('cancel_meeting_button', 'Cancel Meeting'),
            cancelButtonText: getText('cancel_button', 'Cancel'),
            confirmButtonColor: '#ffc107'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: maneliInquiryLists.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'maneli_cancel_meeting',
                        nonce: maneliInquiryLists.nonces.update_inquiry || maneliInquiryLists.nonces.tracking_status || '',
                        inquiry_id: inquiryId,
                        inquiry_type: inquiryType
                    }
                }).done(function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: getText('success', 'Success') + '!',
                            text: response.data?.message || getText('meeting_cancelled', 'Meeting cancelled successfully'),
                            icon: 'success',
                            confirmButtonText: getText('ok_button', 'OK')
                        }).then(() => location.reload());
                    } else {
                        Swal.fire({
                            title: getText('error', 'Error') + '!',
                            text: response.data?.message || getText('cancel_meeting_error', 'Error cancelling meeting'),
                            icon: 'error',
                            confirmButtonText: getText('ok_button', 'OK')
                        });
                    }
                }).fail(function(xhr) {
                    Swal.fire({
                        title: getText('error', 'Error') + '!',
                        text: xhr.responseJSON?.data?.message || getText('server_error', 'Server error. Please try again.'),
                        icon: 'error',
                        confirmButtonText: getText('ok_button', 'OK')
                    });
                });
            }
        });
    });

    /**
     * Helper function to update installment inquiry status
     */
    function updateInstallmentStatus(inquiryId, actionType, data = {}) {
        $.ajax({
            url: maneliInquiryLists.ajax_url,
            type: 'POST',
            data: {
                action: 'maneli_update_installment_status',
                nonce: maneliInquiryLists.nonces.update_installment_status || maneliInquiryLists.nonces.tracking_status || '',
                inquiry_id: inquiryId,
                action_type: actionType,
                ...data
            }
        }).done(function(response) {
            if (response.success) {
                Swal.fire({
                    title: getText('success', 'Success') + '!',
                    text: response.data?.message || getText('operation_success', 'Operation completed successfully.'),
                    icon: 'success',
                    confirmButtonText: getText('ok_button', 'OK')
                }).then(() => location.reload());
            } else {
                Swal.fire({
                    title: getText('error', 'Error') + '!',
                    text: response.data?.message || getText('error_occurred', 'An error occurred.'),
                    icon: 'error',
                    confirmButtonText: getText('ok_button', 'OK')
                });
            }
        }).fail(function(xhr) {
            Swal.fire({
                title: getText('error', 'Error') + '!',
                text: xhr.responseJSON?.data?.message || getText('error_occurred', 'An error occurred.'),
                icon: 'error',
                confirmButtonText: getText('ok_button', 'OK')
            });
        });
    }

    /**
     * Handle cash inquiry status buttons and actions
     */
    $(document.body).on('click', '#set-in-progress-btn, #request-downpayment-btn, #submit-downpayment-btn, #schedule-meeting-btn, #submit-meeting-btn, #approve-inquiry-btn, #reject-inquiry-btn', function() {
        const button = $(this);
        const buttonId = button.attr('id');
        const inquiryId = $('.frontend-expert-report').data('inquiry-id');
        
        if (buttonId === 'request-downpayment-btn') {
            // Show SweetAlert instead of card
            Swal.fire({
                title: getText('set_downpayment_title', 'Set Down Payment Amount'),
                html: `
                    <div class="text-start">
                        <label class="form-label">${getText('downpayment_amount_label', 'Down Payment Amount (Toman):')}</label>
                        <input type="number" id="swal-downpayment-amount" class="form-control" placeholder="${getText('downpayment_placeholder', 'Example: 50000000')}">
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: getText('send_button', 'Send'),
                cancelButtonText: getText('cancel_button', 'Cancel'),
                confirmButtonColor: '#ffc107',
                width: '600px',
                preConfirm: () => {
                    const amount = $('#swal-downpayment-amount').val();
                    if (!amount || amount <= 0) {
                        Swal.showValidationMessage(getText('downpayment_amount_required', 'Please enter down payment amount'));
                        return false;
                    }
                    return { downpayment_amount: amount };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const amount = result.value.downpayment_amount;
                    Swal.fire({
                        title: getText('request_downpayment_title', 'Request Down Payment?'),
                        html: `${getText('amount', 'Amount')}: <strong>${parseInt(amount).toLocaleString('fa-IR').replace(/٬/g, ',')} ${getText('toman', 'Toman')}</strong>`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: getText('send_button', 'Yes, Send'),
                        cancelButtonText: getText('cancel_button', 'Cancel')
                    }).then((confirmResult) => {
                        if (confirmResult.isConfirmed) {
                            updateCashStatus(inquiryId, 'awaiting_downpayment', { downpayment_amount: amount });
                        }
                    });
                }
            });
            return;
        }
        
        if (buttonId === 'schedule-meeting-btn') {
            $('#meeting-card').slideDown();
            return;
        }
        
        if (buttonId === 'submit-downpayment-btn') {
            const amount = $('#downpayment-amount').val();
            if (!amount || amount <= 0) {
                Swal.fire({
                    title: getText('error', 'Error'),
                    text: getText('downpayment_amount_required', 'Please enter down payment amount'),
                    icon: 'error',
                    confirmButtonText: getText('ok_button', 'OK')
                });
                return;
            }
            Swal.fire({
                title: getText('request_downpayment_title', 'Request Down Payment?'),
                html: `${getText('amount', 'Amount')}: <strong>${parseInt(amount).toLocaleString('fa-IR')} ${getText('toman', 'Toman')}</strong>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: getText('send_button', 'Yes, Send'),
                cancelButtonText: getText('cancel_button', 'Cancel')
            }).then((result) => {
                if (result.isConfirmed) {
                    updateCashStatus(inquiryId, 'awaiting_downpayment', { downpayment_amount: amount });
                }
            });
            return;
        }
        
        if (buttonId === 'submit-meeting-btn') {
            const date = $('#meeting-date').val();
            const time = $('#meeting-time').val();
            if (!date || !time) {
                Swal.fire({
                    title: getText('error', 'Error'),
                    text: getText('meeting_required', 'Please enter meeting date and time'),
                    icon: 'error',
                    confirmButtonText: getText('ok_button', 'OK')
                });
                return;
            }
            updateCashStatus(inquiryId, 'meeting_scheduled', { meeting_date: date, meeting_time: time });
            return;
        }
        
        if (buttonId === 'approve-inquiry-btn') {
            Swal.fire({
                title: getText('final_approval_title', 'Final Approval?'),
                text: getText('customer_request_will_be_approved', 'Customer request will be approved'),
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: getText('approve_button', 'Yes, Approve'),
                cancelButtonText: getText('cancel_button', 'Cancel')
            }).then((result) => {
                if (result.isConfirmed) {
                    updateCashStatus(inquiryId, 'approved');
                }
            });
            return;
        }
        
        if (buttonId === 'reject-inquiry-btn') {
            Swal.fire({
                title: getText('reject_title', 'Reject Inquiry'),
                html: `<textarea id="reject-reason" class="swal2-textarea" placeholder="${getText('enter_rejection_reason', 'Please enter rejection reason...')}"></textarea>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: getText('submit_rejection', 'Submit Rejection'),
                cancelButtonText: getText('cancel_button', 'Cancel'),
                preConfirm: () => {
                    const reason = $('#reject-reason').val();
                    if (!reason) {
                        Swal.showValidationMessage(getText('reason_required', 'Please enter reason with at least 10 characters'));
                        return false;
                    }
                    return reason;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    updateCashStatus(inquiryId, 'rejected', { rejection_reason: result.value });
                }
            });
            return;
        }
        
        if (buttonId === 'set-in-progress-btn') {
            Swal.fire({
                title: getText('start_progress_title', 'Start Follow-up'),
                text: getText('status_will_change_to_in_progress', 'Status will change to "In Progress"'),
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: getText('confirm_button', 'Yes'),
                cancelButtonText: getText('cancel_button', 'Cancel')
            }).then((result) => {
                if (result.isConfirmed) {
                    updateCashStatus(inquiryId, 'in_progress');
                }
            });
        }
    });

    /**
     * Handle all cash status buttons with data-action (unified handler)
     */
    $(document.body).on('click', '.cash-status-btn', function() {
        const button = $(this);
        const action = button.data('action');
        const inquiryId = $('.frontend-expert-report').data('inquiry-id');
        
        if (!action || !inquiryId) return;
        
        // Handle different actions
        if (action === 'start_progress') {
            Swal.fire({
                title: getText('start_progress_title', 'Start Follow-up'),
                text: getText('start_progress_confirm', 'Are you sure you want to start follow-up for this inquiry?'),
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: getText('confirm_button', 'Yes'),
                cancelButtonText: getText('cancel_button', 'Cancel'),
                confirmButtonColor: '#3085d6'
            }).then((result) => {
                if (result.isConfirmed) {
                    updateCashStatusAction(inquiryId, action);
                }
            });
        } else if (action === 'schedule_meeting') {
            // Get meeting settings first
            jQuery.ajax({
                url: maneli_ajax_object.ajaxurl,
                type: 'POST',
                data: {
                    action: 'maneli_get_meeting_settings',
                    nonce: maneli_ajax_object.nonce || ''
                },
                success: function(response) {
                    if (response.success) {
                        const settings = response.data;
                        const startHour = settings.start_hour || '10:00';
                        const endHour = settings.end_hour || '20:00';
                        
                        Swal.fire({
                            title: getText('schedule_meeting_title', 'Schedule Meeting'),
                            html: `
                                <div class="text-start">
                                    <label class="form-label">${getText('meeting_date_label', 'Meeting Date')}:</label>
                                    <input type="text" id="swal-meeting-date" class="form-control mb-3 maneli-datepicker" placeholder="${getText('select_date', 'Select Date')}">
                                    <label class="form-label">${getText('meeting_time_label', 'Meeting Time')}:</label>
                                    <input type="time" id="swal-meeting-time" class="form-control" min="${startHour}" max="${endHour}" step="1800">
                                    <small class="text-muted d-block mt-1">${getText('time_range_hint', 'Time must be between')} ${startHour} ${getText('and', 'and')} ${endHour}</small>
                                </div>
                            `,
                            showCancelButton: true,
                            confirmButtonText: getText('schedule_button', 'Schedule'),
                            cancelButtonText: getText('cancel_button', 'Cancel'),
                            confirmButtonColor: '#28a745',
                            didOpen: () => {
                                if (typeof $.fn.persianDatepicker !== 'undefined') {
                                    $('#swal-meeting-date').persianDatepicker({
                                        formatDate: 'YYYY/MM/DD',
                                        persianNumbers: true,
                                        autoClose: true
                                    });
                                }
                            },
                            preConfirm: () => {
                                const meetingDate = $('#swal-meeting-date').val();
                                const meetingTime = $('#swal-meeting-time').val();
                                if (!meetingDate || !meetingTime) {
                                    Swal.showValidationMessage(getText('meeting_required', 'Please enter meeting date and time'));
                                    return false;
                                }
                                
                                // Validate time range
                                const [timeHour, timeMin] = meetingTime.split(':').map(Number);
                                const [startHourNum, startMinNum] = startHour.split(':').map(Number);
                                const [endHourNum, endMinNum] = endHour.split(':').map(Number);
                                
                                const timeMinutes = timeHour * 60 + timeMin;
                                const startMinutes = startHourNum * 60 + startMinNum;
                                const endMinutes = endHourNum * 60 + endMinNum;
                                
                                if (timeMinutes < startMinutes || timeMinutes >= endMinutes) {
                                    Swal.showValidationMessage(getText('time_outside_range', 'Selected time is outside allowed working hours.') + ' (' + startHour + ' - ' + endHour + ')');
                                    return false;
                                }
                                
                                return { meeting_date: meetingDate, meeting_time: meetingTime };
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                updateCashStatusAction(inquiryId, action, result.value);
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: getText('error_title', 'Error'),
                            text: response.data?.message || getText('unknown_error', 'Unknown error occurred')
                        });
                    }
                },
                error: function() {
                    // Fallback without restrictions if AJAX fails
                    Swal.fire({
                        title: getText('schedule_meeting_title', 'Schedule Meeting'),
                        html: `
                            <div class="text-start">
                                <label class="form-label">${getText('meeting_date_label', 'Meeting Date')}:</label>
                                <input type="text" id="swal-meeting-date" class="form-control mb-3 maneli-datepicker" placeholder="${getText('select_date', 'Select Date')}">
                                <label class="form-label">${getText('meeting_time_label', 'Meeting Time')}:</label>
                                <input type="time" id="swal-meeting-time" class="form-control">
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: getText('schedule_button', 'Schedule'),
                        cancelButtonText: getText('cancel_button', 'Cancel'),
                        confirmButtonColor: '#28a745',
                        didOpen: () => {
                            if (typeof $.fn.persianDatepicker !== 'undefined') {
                                $('#swal-meeting-date').persianDatepicker({
                                    formatDate: 'YYYY/MM/DD',
                                    persianNumbers: true,
                                    autoClose: true
                                });
                            }
                        },
                        preConfirm: () => {
                            const meetingDate = $('#swal-meeting-date').val();
                            const meetingTime = $('#swal-meeting-time').val();
                            if (!meetingDate || !meetingTime) {
                                Swal.showValidationMessage(getText('meeting_required', 'Please enter meeting date and time'));
                                return false;
                            }
                            return { meeting_date: meetingDate, meeting_time: meetingTime };
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            updateCashStatusAction(inquiryId, action, result.value);
                        }
                    });
                }
            });
        } else if (action === 'approve') {
            // Simple confirmation for approve
            Swal.fire({
                title: getText('approve_title', 'Approve Inquiry'),
                text: getText('approve_confirm', 'Are you sure you want to approve this inquiry?'),
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: getText('approve_button', 'Approve'),
                cancelButtonText: getText('cancel_button', 'Cancel'),
                confirmButtonColor: '#28a745'
            }).then((result) => {
                if (result.isConfirmed) {
                    updateCashStatusAction(inquiryId, action);
                }
            });
        } else if (action === 'reject' || action === 'cancel') {
            const title = action === 'cancel' ? getText('cancel_inquiry_title', 'Cancel Inquiry') : getText('reject_title', 'Reject Inquiry');
            const reasonLabel = action === 'cancel' ? getText('cancel_reason_label', 'Cancellation Reason') : getText('rejection_reason_label', 'Rejection Reason');
            Swal.fire({
                title: title,
                html: `
                    <div class="text-start">
                        <label class="form-label">${reasonLabel}:</label>
                        <textarea id="swal-cash-reason" class="form-control" rows="4" placeholder="${getText('enter_reason', 'Please enter reason...')}"></textarea>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: title,
                cancelButtonText: getText('cancel_button', 'Cancel'),
                confirmButtonColor: '#dc3545',
                width: '600px',
                preConfirm: () => {
                    const reason = $('#swal-cash-reason').val();
                    if (!reason || reason.trim().length < 10) {
                        Swal.showValidationMessage(getText('reason_required', 'Please enter reason with at least 10 characters'));
                        return false;
                    }
                    return action === 'cancel' ? { cancel_reason: reason } : { rejection_reason: reason };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    updateCashStatusAction(inquiryId, action, result.value);
                }
            });
        } else if (action === 'schedule_followup') {
            Swal.fire({
                title: getText('schedule_followup_title', 'Schedule Follow-up'),
                html: `
                    <div class="text-start">
                        <label class="form-label">${getText('followup_date_label', 'Follow-up Date')}:</label>
                        <input type="text" id="swal-cash-followup-date" class="form-control mb-3 maneli-datepicker" placeholder="${getText('select_date', 'Select Date')}">
                        <label class="form-label">${getText('note_label_optional', 'Note (Optional)')}:</label>
                        <textarea id="swal-cash-followup-note" class="form-control" rows="3" placeholder="${getText('enter_note', 'Enter your note...')}"></textarea>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: getText('schedule_followup_button', 'Schedule Follow-up'),
                cancelButtonText: getText('cancel_button', 'Cancel'),
                confirmButtonColor: '#17a2b8',
                width: '600px',
                didOpen: () => {
                    if (typeof $.fn.persianDatepicker !== 'undefined') {
                        $('#swal-cash-followup-date').persianDatepicker({
                            formatDate: 'YYYY/MM/DD',
                            persianNumbers: true,
                            autoClose: true
                        });
                    }
                },
                preConfirm: () => {
                    const followupDate = $('#swal-cash-followup-date').val();
                    const followupNote = $('#swal-cash-followup-note').val();
                    if (!followupDate) {
                        Swal.showValidationMessage(getText('followup_date_required', 'Please enter follow-up date'));
                        return false;
                    }
                    return { followup_date: followupDate, followup_note: followupNote };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    updateCashStatusAction(inquiryId, action, result.value);
                }
            });
        } else if (action === 'complete') {
            Swal.fire({
                title: getText('complete_title', 'Complete Inquiry'),
                text: getText('complete_confirm', 'Are you sure you want to complete this inquiry?'),
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: getText('complete_button', 'Complete'),
                cancelButtonText: getText('cancel_button', 'Cancel'),
                confirmButtonColor: '#28a745'
            }).then((result) => {
                if (result.isConfirmed) {
                    updateCashStatusAction(inquiryId, action);
                }
            });
        }
    });

    /**
     * Handle cancel meeting button click for cash inquiries
     */
    $(document.body).on('click', '.cash-cancel-meeting-btn', function() {
        const button = $(this);
        const inquiryId = button.data('inquiry-id');
        const inquiryType = button.data('inquiry-type') || 'cash';
        
        if (!inquiryId) return;
        
        Swal.fire({
            title: getText('cancel_meeting_title', 'Cancel Meeting'),
            text: getText('cancel_meeting_confirm', 'Are you sure you want to cancel this meeting?'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: getText('cancel_meeting_button', 'Cancel Meeting'),
            cancelButtonText: getText('cancel_button', 'Cancel'),
            confirmButtonColor: '#ffc107'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: maneliInquiryLists.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'maneli_cancel_meeting',
                        nonce: maneliInquiryLists.nonces.update_inquiry || maneliInquiryLists.nonces.update_cash_status || '',
                        inquiry_id: inquiryId,
                        inquiry_type: inquiryType
                    }
                }).done(function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: getText('success', 'Success') + '!',
                            text: response.data?.message || getText('meeting_cancelled', 'Meeting cancelled successfully'),
                            icon: 'success',
                            confirmButtonText: getText('ok_button', 'OK')
                        }).then(() => location.reload());
                    } else {
                        Swal.fire({
                            title: getText('error', 'Error') + '!',
                            text: response.data?.message || getText('cancel_meeting_error', 'Error cancelling meeting'),
                            icon: 'error',
                            confirmButtonText: getText('ok_button', 'OK')
                        });
                    }
                }).fail(function(xhr) {
                    Swal.fire({
                        title: getText('error', 'Error') + '!',
                        text: xhr.responseJSON?.data?.message || getText('server_error', 'Server error. Please try again.'),
                        icon: 'error',
                        confirmButtonText: getText('ok_button', 'OK')
                    });
                });
            }
        });
    });

    /**
     * Helper function to update cash inquiry status using action_type (new workflow)
     */
    function updateCashStatusAction(inquiryId, actionType, data = {}) {
        $.ajax({
            url: maneliInquiryLists.ajax_url,
            type: 'POST',
            data: {
                action: 'maneli_update_cash_status',
                inquiry_id: inquiryId,
                action_type: actionType,
                nonce: maneliInquiryLists.nonces.update_cash_status || maneliInquiryLists.nonces.cash_update || '',
                ...data
            }
        }).done(function(response) {
            if (response.success) {
                Swal.fire({
                    title: getText('success', 'Success') + '!',
                    text: response.data?.message || getText('status_updated', 'Status updated successfully'),
                    icon: 'success',
                    confirmButtonText: getText('ok_button', 'OK')
                }).then(() => location.reload());
            } else {
                Swal.fire({
                    title: getText('error', 'Error') + '!',
                    text: response.data?.message || getText('status_update_error', 'Error updating status'),
                    icon: 'error',
                    confirmButtonText: getText('ok_button', 'OK')
                });
            }
        }).fail(function(xhr) {
            Swal.fire({
                title: getText('error', 'Error') + '!',
                text: xhr.responseJSON?.data?.message || getText('server_error', 'Server error. Please try again.'),
                icon: 'error',
                confirmButtonText: getText('ok_button', 'OK')
            });
        });
    }

    /**
     * Helper function to update cash inquiry status (legacy)
     */
    function updateCashStatus(inquiryId, newStatus, extraData = {}) {
        $.ajax({
            url: maneliInquiryLists.ajax_url,
            type: 'POST',
            data: {
                action: 'maneli_update_cash_status',
                inquiry_id: inquiryId,
                new_status: newStatus,
                nonce: maneliInquiryLists.nonces.update_cash_status || maneliInquiryLists.nonces.cash_update || '',
                ...extraData
            }
        }).done(function(response) {
            if (response.success) {
                Swal.fire({
                    title: getText('success', 'Success') + '!',
                    text: response.data?.message || getText('status_updated', 'Status updated successfully'),
                    icon: 'success',
                    confirmButtonText: getText('ok_button', 'OK')
                }).then(() => location.reload());
            } else {
                Swal.fire({
                    title: getText('error', 'Error') + '!',
                    text: response.data?.message || getText('status_update_error', 'Error updating status'),
                    icon: 'error',
                    confirmButtonText: getText('ok_button', 'OK')
                });
            }
        }).fail(function(xhr) {
            Swal.fire({
                title: getText('error', 'Error') + '!',
                text: xhr.responseJSON?.data?.message || getText('server_error', 'Server error. Please try again.'),
                icon: 'error',
                confirmButtonText: getText('ok_button', 'OK')
            });
        });
    }
    
    }); // End of document.ready callback
})(); // End of waitForJQuery function