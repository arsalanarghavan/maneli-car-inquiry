/**
 * Handles all frontend JavaScript logic for the User Management interface,
 * rendered by the [maneli_user_list] shortcode.
 *
 * This includes AJAX filtering, pagination, and user deletion with confirmation.
 *
 * @version 1.0.1 (Localized all hardcoded strings)
 */
jQuery(document).ready(function($) {
    'use strict';

    // Find the main container for the user list. If it doesn't exist, do nothing.
    const userListContainer = $('#maneli-user-filter-form');
    if (!userListContainer.length) {
        return;
    }

    // Cache frequently used DOM elements
    const userListBody = $('#maneli-user-list-tbody');
    const searchInput = $('#user-search-input');
    const roleFilter = $('#role-filter');
    const orderbyFilter = $('#orderby-filter');
    const orderFilter = $('#order-filter');
    const loader = $('#user-list-loader');
    const paginationWrapper = $('.maneli-pagination-wrapper');

    let xhr;
    let searchTimeout;

    // Helper function to get localized text. Assumes 'unknown_error' is available.
    const getText = (key, fallback = '...') => maneliUserManagement.text[key] || fallback;

    /**
     * Fetches and updates the user list via AJAX based on current filter values.
     * @param {number} page The page number to fetch.
     */
    function fetch_users(page = 1) {
        // Abort any ongoing AJAX request to prevent race conditions
        if (xhr && xhr.readyState !== 4) {
            xhr.abort();
        }

        loader.show();
        userListBody.css('opacity', 0.5);
        paginationWrapper.css('opacity', 0.5);

        const formData = {
            action: 'maneli_filter_users_ajax',
            _ajax_nonce: maneliUserManagement.filter_nonce,
            search: searchInput.val(),
            role: roleFilter.val(),
            orderby: orderbyFilter.val(),
            order: orderFilter.val(),
            page: page,
            current_url: window.location.href.split('?')[0] // Base URL without query params
        };

        xhr = $.ajax({
            url: maneliUserManagement.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    userListBody.html(response.data.html);
                    paginationWrapper.html(response.data.pagination_html);
                } else {
                    // FIX: Use localized string for the error message
                    userListBody.html('<tr><td colspan="5" style="text-align:center;">' + (response.data.message || getText('unknown_error')) + '</td></tr>');
                    paginationWrapper.html('');
                }
            },
            error: function(jqXHR, textStatus) {
                if (textStatus !== 'abort') {
                    console.error('AJAX Error:', textStatus);
                    // FIX: Use localized string for server error
                    userListBody.html('<tr><td colspan="5" style="text-align:center;">' + getText('server_error') + '</td></tr>');
                }
            },
            complete: function() {
                loader.hide();
                userListBody.css('opacity', 1);
                paginationWrapper.css('opacity', 1);
            }
        });
    }

    // --- EVENT HANDLERS ---

    // Handle search input with a debounce effect (500ms delay)
    searchInput.on('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            fetch_users(1); // Reset to page 1 on new search
        }, 500);
    });

    // Handle change events on filter dropdowns
    roleFilter.add(orderbyFilter).add(orderFilter).on('change', function() {
        fetch_users(1); // Reset to page 1 on filter change
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
            const pageText = $(this).text();
            if ($.isNumeric(pageText)) {
                pageNum = parseInt(pageText, 10);
            } else {
                let currentPage = parseInt(paginationWrapper.find('.page-numbers.current').text(), 10) || 1;
                if ($(this).hasClass('prev')) {
                    pageNum = Math.max(1, currentPage - 1);
                } else if ($(this).hasClass('next')) {
                    pageNum = currentPage + 1;
                }
            }
        }
        fetch_users(pageNum);
    });

    // Handle user deletion with a confirmation modal
    userListBody.on('click', '.delete-user-btn', function(e) {
        e.preventDefault();
        const button = $(this);
        const userId = button.data('user-id');

        Swal.fire({
            title: getText('confirm_delete'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: getText('delete'),
            cancelButtonText: getText('cancel_button') // FIX: Use localized string
        }).then((result) => {
            if (result.isConfirmed) {
                button.text(getText('deleting')).prop('disabled', true);

                $.ajax({
                    url: maneliUserManagement.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'maneli_delete_user_ajax',
                        user_id: userId,
                        _ajax_nonce: maneliUserManagement.delete_nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            button.closest('tr').fadeOut(400, function() {
                                $(this).remove();
                            });
                        } else {
                            // FIX: Use localized strings for alerts
                            alert(getText('error_deleting') + ' ' + (response.data.message || getText('unknown_error')));
                            button.text(getText('delete')).prop('disabled', false);
                        }
                    },
                    error: function() {
                        // FIX: Use localized string for server error alert
                        alert(getText('server_error'));
                        button.text(getText('delete')).prop('disabled', false);
                    }
                });
            }
        });
    });
});