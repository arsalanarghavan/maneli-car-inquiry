/**
 * Shop Payment Type Filter AJAX Handler
 * Handles AJAX filtering of shop products by payment type (cash/installment)
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        console.log('Autopuzzle Shop Filter JS loaded');
        
        // Check if autopuzzleShopFilter is available
        if (typeof autopuzzleShopFilter === 'undefined') {
            console.error('autopuzzleShopFilter is not defined');
            return;
        }
        
        const $filterContainer = $('.autopuzzle-payment-filter');
        
        if (!$filterContainer.length) {
            console.log('No filter container found');
            return; // No filter on this page
        }
        
        console.log('Filter container found');

        const $filterButtons = $filterContainer.find('.autopuzzle-filter-btn');
        console.log('Filter buttons found:', $filterButtons.length);
        
        const $productsLoop = $('.woocommerce ul.products, .woocommerce .products');
        const $pagination = $('.woocommerce nav.woocommerce-pagination, .woocommerce .woocommerce-pagination');
        
        console.log('Products loop found:', $productsLoop.length);
        console.log('Pagination found:', $pagination.length);
        
        if (!$productsLoop.length) {
            console.log('No products loop found');
            return; // No products loop found
        }

        let xhr = null;
        let isLoading = false;

        /**
         * Update button active states
         */
        function updateButtonStates(activeType) {
            $filterButtons.each(function() {
                const $btn = $(this);
                const paymentType = $btn.data('payment-type') || '';
                const isActive = paymentType === activeType;
                
                $btn.toggleClass('active', isActive);
                
                // Update styles based on active state
                if (paymentType === 'cash') {
                    $btn.css({
                        'background-color': isActive ? 'rgb(33, 206, 158)' : '#f0f0f0',
                        'color': isActive ? '#fff' : '#333'
                    });
                } else if (paymentType === 'installment') {
                    $btn.css({
                        'background-color': isActive ? 'rgb(14, 165, 232)' : '#f0f0f0',
                        'color': isActive ? '#fff' : '#333'
                    });
                } else {
                    $btn.css({
                        'background-color': isActive ? '#333' : '#f0f0f0',
                        'color': isActive ? '#fff' : '#333'
                    });
                }
            });
        }

        /**
         * Show loading state
         */
        function showLoading() {
            isLoading = true;
            $productsLoop.css('opacity', '0.5');
            $pagination.css('opacity', '0.5');
            
            // Add loading overlay if not exists
            if (!$('.autopuzzle-shop-filter-loading').length) {
                $productsLoop.after('<div class="autopuzzle-shop-filter-loading" style="text-align: center; padding: 20px;"><span>' + (autopuzzleShopFilter.loadingText || 'Loading...') + '</span></div>');
            }
        }

        /**
         * Hide loading state
         */
        function hideLoading() {
            isLoading = false;
            $productsLoop.css('opacity', '1');
            $pagination.css('opacity', '1');
            $('.autopuzzle-shop-filter-loading').remove();
        }

        /**
         * Filter products via AJAX
         */
        function filterProducts(paymentType, page) {
            if (isLoading) {
                return; // Already loading
            }

            // Abort any ongoing request
            if (xhr && xhr.readyState !== 4) {
                xhr.abort();
            }

            showLoading();
            updateButtonStates(paymentType);

            // Get filter container data
            const baseUrl = $filterContainer.data('base-url') || window.location.href.split('?')[0];
            let category = $filterContainer.data('category') || '';
            const taxonomy = $filterContainer.data('taxonomy') || '';
            const term = $filterContainer.data('term') || '';
            
            // If category is empty or '-', try to extract from URL
            if (!category || category === '-') {
                const urlMatch = window.location.pathname.match(/\/product-category\/([^\/]+)/);
                if (urlMatch) {
                    category = decodeURIComponent(urlMatch[1]);
                }
            }
            
            // Only include category if it's not empty and not '-'
            if (!category || category === '-') {
                category = '';
            }

            // Build AJAX request data
            const ajaxData = {
                action: 'autopuzzle_filter_shop_products',
                nonce: autopuzzleShopFilter.nonce,
                payment_type: paymentType || '',
                paged: page || 1,
                base_url: baseUrl
            };

            if (category && category !== '-') {
                ajaxData.category = category;
            }
            if (taxonomy) {
                ajaxData.taxonomy = taxonomy;
            }
            if (term) {
                ajaxData.term = term;
            }

            console.log('Sending AJAX request:', ajaxData);
            
            // Make AJAX request
            xhr = $.ajax({
                url: autopuzzleShopFilter.ajaxUrl,
                type: 'POST',
                data: ajaxData,
                success: function(response) {
                    hideLoading();
                    console.log('AJAX response:', response);
                    console.log('Response HTML length:', response.data?.html?.length || 0);
                    console.log('Response HTML preview:', response.data?.html?.substring(0, 200) || 'No HTML');
                    
                    if (response.success && response.data) {
                        // Update products loop
                        if (response.data.html) {
                            console.log('Replacing products loop HTML');
                            console.log('Current productsLoop:', $productsLoop[0]);
                            console.log('HTML to insert:', response.data.html.substring(0, 500));
                            
                            // Check if HTML contains <ul> tag
                            if (response.data.html.trim().startsWith('<ul')) {
                                // Replace the entire element
                                $productsLoop.replaceWith(response.data.html);
                                // Re-select the new element
                                $productsLoop = $('.woocommerce ul.products, .woocommerce .products').first();
                            } else {
                                // Replace only inner HTML
                                $productsLoop.html(response.data.html);
                            }
                            console.log('Products loop updated');
                        } else {
                            console.error('No HTML in response');
                        }
                        
                        // Update pagination
                        if (response.data.pagination) {
                            if ($pagination.length) {
                                $pagination.replaceWith(response.data.pagination);
                            } else {
                                $productsLoop.after(response.data.pagination);
                            }
                            // Re-bind pagination click handlers
                            bindPaginationHandlers();
                        }
                        
                        // Update URL without reload
                        if (response.data.url && window.history && window.history.pushState) {
                            window.history.pushState({payment_type: paymentType}, '', response.data.url);
                        }
                        
                        // Trigger WooCommerce events in case any scripts depend on them
                        $(document.body).trigger('autopuzzle_shop_filtered', [paymentType, response.data]);
                    } else {
                        hideLoading();
                        alert(autopuzzleShopFilter.errorText || 'Error loading products. Please try again.');
                    }
                },
                error: function(xhr, status, error) {
                    hideLoading();
                    console.error('AJAX error:', status, error, xhr);
                    if (status !== 'abort') {
                        alert(autopuzzleShopFilter.errorText || 'Error loading products. Please try again.');
                    }
                }
            });
        }

        /**
         * Bind pagination click handlers
         */
        function bindPaginationHandlers() {
            $('.woocommerce-pagination a.page-numbers, .woocommerce nav.woocommerce-pagination a').off('click.autopuzzle').on('click.autopuzzle', function(e) {
                e.preventDefault();
                const href = $(this).attr('href');
                if (!href) return;
                
                // Extract page number from URL
                const urlParams = new URLSearchParams(href.split('?')[1] || '');
                const page = urlParams.get('paged') || urlParams.get('page') || 1;
                
                // Get current payment type from active button
                const activeButton = $filterContainer.find('.autopuzzle-filter-btn.active');
                const paymentType = activeButton.data('payment-type') || '';
                
                filterProducts(paymentType, parseInt(page));
            });
        }

        // Bind filter button clicks
        $filterButtons.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const $btn = $(this);
            const paymentType = $btn.data('payment-type') || '';
            
            console.log('Filter button clicked:', paymentType);
            filterProducts(paymentType, 1);
        });

        // Bind pagination handlers on initial load
        bindPaginationHandlers();

        // Handle browser back/forward buttons
        $(window).on('popstate', function(e) {
            const urlParams = new URLSearchParams(window.location.search);
            const paymentType = urlParams.get('payment_type') || '';
            
            // Update button states
            updateButtonStates(paymentType);
            
            // Reload products if needed
            filterProducts(paymentType, 1);
        });
    });

})(jQuery);

