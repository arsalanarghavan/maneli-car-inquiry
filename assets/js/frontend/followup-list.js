/**
 * Handles JavaScript logic for the follow-up inquiry list page.
 * Similar to inquiry-lists.js but specifically for follow-up items.
 *
 * @version 1.0.0
 */
jQuery(document).ready(function($) {
    'use strict';

    //======================================================================
    //  HELPER FUNCTION: LOCALIZATION & STRING ACCESS
    //======================================================================
    
    const getText = (key, fallback = '...') => maneliFollowupList.text[key] || fallback;
    
    //======================================================================
    //  AJAX LIST FILTERING & PAGINATION
    //======================================================================
    
    const listBody = $('#autopuzzle-followup-list-tbody');
    const searchInput = $('#followup-search-input');
    const expertFilter = $('#followup-expert-filter');
    const loader = $('#followup-list-loader');
    const paginationWrapper = $('.autopuzzle-pagination-wrapper');

    let xhr;
    let searchTimeout;

    /**
     * Fetches and updates the follow-up inquiry list via AJAX.
     * @param {number} page The page number to fetch.
     */
    function fetchFollowupInquiries(page = 1) {
        // Abort any ongoing AJAX request
        if (xhr && xhr.readyState !== 4) {
            xhr.abort();
        }

        loader.show();
        listBody.css('opacity', 0.5);
        paginationWrapper.css('opacity', 0.5);

        // Build base URL
        const urlObj = new URL(window.location.href);
        const paramsToRemove = ['inquiry_id', 'paged', 'page'];
        paramsToRemove.forEach(param => urlObj.searchParams.delete(param));
        const baseUrl = urlObj.toString();
        const colspan = expertFilter.length ? 8 : 7; // Number of columns

        const formData = {
            action: 'autopuzzle_filter_followup_inquiries',
            _ajax_nonce: maneliFollowupList.nonces.followup_filter,
            search: searchInput.val(),
            expert: expertFilter.val(),
            page: page,
            base_url: baseUrl
        };
        
        // Destroy Select2 before fetching new content
        if (expertFilter.hasClass('select2-hidden-accessible')) {
            expertFilter.select2('destroy');
        }
        
        xhr = $.ajax({
            url: maneliFollowupList.ajax_url,
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
                // Re-initialize Select2 for expert filter if present
                if (expertFilter.length && expertFilter.is(':visible')) {
                   expertFilter.select2({ width: '100%' });
                }
            }
        });
    }
    
    // Load initial list
    fetchFollowupInquiries(1);

    // Handle search input with debounce
    searchInput.on('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            fetchFollowupInquiries(1);
        }, 500);
    });

    // Handle expert filter change
    expertFilter.on('change', function() { 
        fetchFollowupInquiries(1); 
    });

    // Handle pagination clicks
    paginationWrapper.on('click', 'a.page-numbers', function(e) {
        e.preventDefault();
        
        const pageUrl = $(this).attr('href');
        let pageNum = 1;

        const matches = pageUrl.match(/paged=(\d+)/);
        if (matches) {
            pageNum = parseInt(matches[1], 10);
        } else {
            const currentPageText = paginationWrapper.find('.page-numbers.current').text();
            const currentPage = parseInt(currentPageText, 10) || 1;
            
            if ($(this).hasClass('prev') || $(this).attr('rel') === 'prev') {
                pageNum = Math.max(1, currentPage - 1);
            } else if ($(this).hasClass('next') || $(this).attr('rel') === 'next') {
                pageNum = currentPage + 1;
            }
        }
        fetchFollowupInquiries(pageNum);
    });

    //======================================================================
    //  ADMIN/EXPERT ACTIONS (Reuse from inquiry-lists.js logic)
    //======================================================================

    /**
     * Handles 'Assign Expert' button click
     */
    $(document.body).on('click', '.assign-expert-btn', function() {
        const button = $(this);
        const inquiryId = button.data('inquiry-id');
        
        if (!maneliFollowupList.experts || maneliFollowupList.experts.length === 0) {
            Swal.fire(getText('error'), 'No experts available', 'error');
            return;
        }

        let expertOptionsHTML = '<option value="auto">' + getText('auto_assign') + '</option>';
        maneliFollowupList.experts.forEach(expert => {
            expertOptionsHTML += `<option value="${expert.id}">${expert.name}</option>`;
        });

        Swal.fire({
            title: `${getText('assign_title')} #${inquiryId}`,
            html: `<div style="text-align: right; font-family: inherit;">
                     <label for="swal-expert-filter" style="display: block; margin-bottom: 10px;">${getText('assign_label')}</label>
                     <select id="swal-expert-filter" class="swal2-select" style="width: 100%;">${expertOptionsHTML}</select>
                   </div>`,
            confirmButtonText: getText('assign_button'),
            showCancelButton: true,
            cancelButtonText: getText('cancel_button'),
            didOpen: () => {
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
                $.post(maneliFollowupList.ajax_url, {
                    action: 'autopuzzle_assign_expert_to_inquiry',
                    nonce: maneliFollowupList.nonces.assign_expert,
                    inquiry_id: inquiryId,
                    expert_id: result.value
                }, function(response) {
                    if (response.success) {
                        Swal.fire(getText('success'), response.data.message, 'success').then(() => {
                            fetchFollowupInquiries(1); // Reload list
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

});

