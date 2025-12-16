/**
 * Global Search Functionality
 * Handles AJAX search for users, cash inquiries, and installment inquiries
 */

(function($) {
    'use strict';

    let searchTimeout;
    const searchDelay = 300; // debounce delay in milliseconds
    const minQueryLength = 2;

    // Initialize search functionality
    function initGlobalSearch() {
        const $searchInput = $('#global-search-input');
        const $resultsContainer = $('#global-search-results');
        const $resultsContent = $('#global-search-results-content');

        if (!$searchInput.length || !$resultsContainer.length) {
            return; // Elements not found
        }

        // Handle input changes with debounce
        $searchInput.on('input', function() {
            const query = $(this).val().trim();

            clearTimeout(searchTimeout);

            if (query.length < minQueryLength) {
                hideResults();
                return;
            }

            searchTimeout = setTimeout(function() {
                performSearch(query);
            }, searchDelay);
        });

        // Handle focus
        $searchInput.on('focus', function() {
            const query = $(this).val().trim();
            if (query.length >= minQueryLength && $resultsContent.html().trim() !== '') {
                showResults();
            }
        });

        // Hide results when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.header-search').length) {
                hideResults();
            }
        });

        // Handle escape key
        $searchInput.on('keydown', function(e) {
            if (e.key === 'Escape') {
                hideResults();
                $(this).blur();
            }
        });
    }

    // Perform AJAX search
    function performSearch(query) {
        if (!window.autopuzzle_ajax || !window.autopuzzle_ajax.url) {
            console.error('AutoPuzzle AJAX configuration not found');
            return;
        }

        const $resultsContainer = $('#global-search-results');
        const $resultsContent = $('#global-search-results-content');

        // Show loading state
        $resultsContent.html('<div class="text-center p-3"><div class="spinner-border spinner-border-sm" role="status"></div><span class="ms-2">در حال جستجو...</span></div>');
        showResults();

        $.ajax({
            url: window.autopuzzle_ajax.url,
            type: 'POST',
            data: {
                action: 'autopuzzle_global_search',
                query: query,
                nonce: window.autopuzzle_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    displayResults(response.data);
                } else {
                    displayNoResults();
                }
            },
            error: function() {
                $resultsContent.html('<div class="text-center p-3 text-danger">خطا در اتصال به سرور</div>');
            }
        });
    }

    // Display search results
    function displayResults(data) {
        const $resultsContent = $('#global-search-results-content');
        let html = '';

        const users = data.users || [];
        const cashInquiries = data.cash_inquiries || [];
        const installmentInquiries = data.installment_inquiries || [];

        const hasResults = users.length > 0 || cashInquiries.length > 0 || installmentInquiries.length > 0;

        if (!hasResults) {
            displayNoResults();
            return;
        }

        // Users section
        if (users.length > 0) {
            html += '<div class="search-section">';
            html += '<div class="search-section-title">' + (window.maneliGlobalSearch?.texts?.users || 'کاربران') + '</div>';
            html += '<div class="search-section-items">';
            users.forEach(function(user) {
                html += '<a href="' + user.url + '" class="search-result-item">';
                html += '<div class="search-result-content">';
                html += '<div class="search-result-title">' + escapeHtml(user.name) + '</div>';
                if (user.mobile) {
                    html += '<div class="search-result-meta">موبایل: ' + escapeHtml(user.mobile) + '</div>';
                }
                if (user.national_code) {
                    html += '<div class="search-result-meta">کد ملی: ' + escapeHtml(user.national_code) + '</div>';
                }
                html += '</div>';
                html += '</a>';
            });
            html += '</div>';
            html += '</div>';
        }

        // Cash Inquiries section
        if (cashInquiries.length > 0) {
            html += '<div class="search-section">';
            html += '<div class="search-section-title">' + (window.maneliGlobalSearch?.texts?.cash_inquiries || 'استعلام‌های نقدی') + '</div>';
            html += '<div class="search-section-items">';
            cashInquiries.forEach(function(inquiry) {
                html += '<a href="' + inquiry.url + '" class="search-result-item">';
                html += '<div class="search-result-content">';
                html += '<div class="search-result-title">' + escapeHtml(inquiry.customer_name || 'بدون نام') + '</div>';
                if (inquiry.car_name) {
                    html += '<div class="search-result-meta">خودرو: ' + escapeHtml(inquiry.car_name) + '</div>';
                }
                if (inquiry.mobile) {
                    html += '<div class="search-result-meta">موبایل: ' + escapeHtml(inquiry.mobile) + '</div>';
                }
                html += '<div class="search-result-meta">شماره استعلام: ' + inquiry.inquiry_number + '</div>';
                html += '</div>';
                html += '</a>';
            });
            html += '</div>';
            html += '</div>';
        }

        // Installment Inquiries section
        if (installmentInquiries.length > 0) {
            html += '<div class="search-section">';
            html += '<div class="search-section-title">' + (window.maneliGlobalSearch?.texts?.installment_inquiries || 'استعلام‌های اقساطی') + '</div>';
            html += '<div class="search-section-items">';
            installmentInquiries.forEach(function(inquiry) {
                html += '<a href="' + inquiry.url + '" class="search-result-item">';
                html += '<div class="search-result-content">';
                html += '<div class="search-result-title">' + escapeHtml(inquiry.customer_name || 'بدون نام') + '</div>';
                if (inquiry.car_name) {
                    html += '<div class="search-result-meta">خودرو: ' + escapeHtml(inquiry.car_name) + '</div>';
                }
                if (inquiry.mobile) {
                    html += '<div class="search-result-meta">موبایل: ' + escapeHtml(inquiry.mobile) + '</div>';
                }
                if (inquiry.national_code) {
                    html += '<div class="search-result-meta">کد ملی: ' + escapeHtml(inquiry.national_code) + '</div>';
                }
                html += '<div class="search-result-meta">شماره استعلام: ' + inquiry.inquiry_number + '</div>';
                html += '</div>';
                html += '</a>';
            });
            html += '</div>';
            html += '</div>';
        }

        $resultsContent.html(html);
        showResults();
    }

    // Display no results message
    function displayNoResults() {
        const $resultsContent = $('#global-search-results-content');
        const noResultsText = window.maneliGlobalSearch?.texts?.no_results || 'نتیجه‌ای یافت نشد';
        $resultsContent.html('<div class="text-center p-3 text-muted">' + noResultsText + '</div>');
        showResults();
    }

    // Show results dropdown
    function showResults() {
        $('#global-search-results').fadeIn(200);
    }

    // Hide results dropdown
    function hideResults() {
        $('#global-search-results').fadeOut(200);
    }

    // Escape HTML to prevent XSS
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return (text || '').replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Initialize when DOM is ready
    $(document).ready(function() {
        // Wait for jQuery and autopuzzle_ajax to be available
        if (typeof jQuery !== 'undefined' && window.autopuzzle_ajax) {
            initGlobalSearch();
        } else {
            // Retry if not ready
            setTimeout(function() {
                if (typeof jQuery !== 'undefined') {
                    initGlobalSearch();
                }
            }, 500);
        }
    });

})(jQuery);

