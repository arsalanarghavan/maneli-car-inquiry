/**
 * Product Tags Carousel Support
 * Ensures payment tags are displayed in Elementor carousels and other dynamic product displays
 *
 * @package Autopuzzle_Car_Inquiry/Assets/JS
 * @version 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Add payment tags to product in carousel - Fast version using inline data
     */
    function addTagsToProduct($productElement, productId) {
        // Check if tags already exist
        if ($productElement.find('.autopuzzle-payment-tags').length > 0) {
            return;
        }

        // Get product ID from data attribute or post ID
        if (!productId) {
            productId = $productElement.data('product-id') || 
                       $productElement.find('[data-product-id]').data('product-id') ||
                       $productElement.attr('data-product-id');
            
            // Try to get from post class (most reliable for Elementor)
            if (!productId) {
                var postClass = $productElement.attr('class');
                if (postClass) {
                    var match = postClass.match(/post-(\d+)/);
                    if (match) {
                        productId = match[1];
                    }
                }
            }
        }

        if (!productId) {
            return;
        }

        // Check if product has payment data in data attributes (faster than AJAX)
        var cashAvailable = $productElement.data('cash-available');
        var installmentAvailable = $productElement.data('installment-available');

        // If data attributes exist, use them directly (fastest method)
        if (cashAvailable !== undefined || installmentAvailable !== undefined) {
            renderTagsDirectly($productElement, cashAvailable === true, installmentAvailable === true);
            return;
        }

        // Fallback: Use cached data from autopuzzleProductTags if available
        if (typeof autopuzzleProductTags !== 'undefined' && autopuzzleProductTags.products) {
            var productData = autopuzzleProductTags.products[productId];
            if (productData) {
                renderTagsDirectly($productElement, productData.cash, productData.installment);
                return;
            }
        }

        // Last resort: AJAX call (only if no cached data)
        $.ajax({
            url: autopuzzleProductTags.ajax_url,
            type: 'POST',
            data: {
                action: 'autopuzzle_get_product_payment_tags',
                product_id: productId,
                nonce: autopuzzleProductTags.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    if (response.data.html) {
                        // Find the image container
                        var $imageContainer = $productElement.find('.product-image-wrap, .image-wrap, .woocommerce-loop-product__link, .product-image');
                        
                        if ($imageContainer.length === 0) {
                            $imageContainer = $productElement;
                        } else {
                            $imageContainer = $imageContainer.first();
                        }

                        $imageContainer.append(response.data.html);
                    } else if (response.data.cash !== undefined) {
                        // Use data to render directly
                        renderTagsDirectly($productElement, response.data.cash, response.data.installment);
                    }
                }
            },
            error: function() {
                // Silently fail
            }
        });
    }

    /**
     * Render tags directly without AJAX
     */
    function renderTagsDirectly($productElement, hasCash, hasInstallment) {
        if (!hasCash && !hasInstallment) {
            return;
        }

        var tagsHtml = '<div class="autopuzzle-payment-tags autopuzzle-payment-tag-loop">';
        if (hasCash) {
            tagsHtml += '<span class="autopuzzle-payment-tag autopuzzle-payment-tag--cash">نقدی</span>';
        }
        if (hasInstallment) {
            tagsHtml += '<span class="autopuzzle-payment-tag autopuzzle-payment-tag--installment">اقساطی</span>';
        }
        tagsHtml += '</div>';

        // Find the image container
        var $imageContainer = $productElement.find('.product-image-wrap, .image-wrap, .woocommerce-loop-product__link, .product-image');
        
        if ($imageContainer.length === 0) {
            $imageContainer = $productElement;
        } else {
            $imageContainer = $imageContainer.first();
        }

        $imageContainer.append(tagsHtml);
    }

    /**
     * Process all products in carousel - optimized for speed
     */
    function processCarouselProducts() {
        // Find all product elements in carousels - be more comprehensive
        var selectors = [
            '.swiper-slide.product',
            '.product.swiper-slide',
            '.eael-product-carousel',
            '.swiper-slide[class*="product"]',
            '.woocommerce ul.products li.product',
            '.products .product',
            'li.product',
            '.product'
        ];

        var processedCount = 0;
        var maxProcessPerRun = 20; // Limit to prevent performance issues

        selectors.forEach(function(selector) {
            if (processedCount >= maxProcessPerRun) {
                return;
            }

            $(selector).each(function() {
                if (processedCount >= maxProcessPerRun) {
                    return false; // Break loop
                }

                var $product = $(this);
                
                // Skip if already processed or already has tags
                if ($product.data('autopuzzle-tags-processed') || $product.find('.autopuzzle-payment-tags').length > 0) {
                    return;
                }

                var productId = null;

                // Try from post class first (most reliable and fastest for Elementor)
                var postClass = $product.attr('class');
                if (postClass) {
                    var match = postClass.match(/post-(\d+)/);
                    if (match) {
                        productId = match[1];
                    }
                }

                // Fallback: Try to get product ID from data attributes
                if (!productId) {
                    productId = $product.data('product-id') || 
                              $product.find('[data-product-id]').first().data('product-id') ||
                              $product.attr('data-product-id');
                }

                if (productId) {
                    // Mark as processed immediately
                    $product.data('autopuzzle-tags-processed', true);
                    processedCount++;
                    
                    // Use cached data if available (fastest method)
                    if (typeof autopuzzleProductTags !== 'undefined' && autopuzzleProductTags.products && autopuzzleProductTags.products[productId]) {
                        var productData = autopuzzleProductTags.products[productId];
                        renderTagsDirectly($product, productData.cash, productData.installment);
                    } else {
                        // Fallback to AJAX
                        addTagsToProduct($product, productId);
                    }
                }
            });
        });

        // If we hit the limit, schedule another run
        if (processedCount >= maxProcessPerRun) {
            setTimeout(function() {
                processCarouselProducts();
            }, 100);
        }
    }

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        // Initialize products cache if not exists
        if (typeof autopuzzleProductTags === 'undefined') {
            window.autopuzzleProductTags = { products: {} };
        }

        // Process carousel products immediately (no delay) - run multiple times to catch all
        processCarouselProducts();
        
        // Also process immediately after a tiny delay to catch products loaded via AJAX
        setTimeout(function() {
            processCarouselProducts();
        }, 50);

        // Process after Swiper initialization (if Swiper is used)
        if (typeof Swiper !== 'undefined') {
            // Wait a bit for Swiper to initialize
            setTimeout(function() {
                processCarouselProducts();
            }, 500);
            
            // Also process after a longer delay for lazy-loaded content
            setTimeout(function() {
                processCarouselProducts();
            }, 1500);
        }

        // Process on Swiper slide change
        $(document).on('slideChange slideChangeTransitionStart', '.swiper', function() {
            setTimeout(function() {
                processCarouselProducts();
            }, 100);
        });

        // Process on Elementor carousel update
        $(document).on('elementor/popup/show elementor/frontend/init', function() {
            setTimeout(function() {
                processCarouselProducts();
            }, 300);
        });

        // Use MutationObserver to watch for DOM changes
        if (typeof MutationObserver !== 'undefined') {
            var observer = new MutationObserver(function(mutations) {
                var shouldProcess = false;
                mutations.forEach(function(mutation) {
                    if (mutation.addedNodes.length > 0) {
                        // Check if any added nodes are products
                        for (var i = 0; i < mutation.addedNodes.length; i++) {
                            var node = mutation.addedNodes[i];
                            if (node.nodeType === 1) { // Element node
                                var $node = $(node);
                                if ($node.hasClass('product') || 
                                    $node.hasClass('swiper-slide') || 
                                    $node.find('.product, .swiper-slide').length > 0) {
                                    shouldProcess = true;
                                    break;
                                }
                            }
                        }
                    }
                });
                
                if (shouldProcess) {
                    setTimeout(function() {
                        processCarouselProducts();
                    }, 200);
                }
            });

            // Observe the document body for changes
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
    });

    // Also run after multiple delays to catch dynamically loaded content
    setTimeout(function() {
        processCarouselProducts();
    }, 1000);

    setTimeout(function() {
        processCarouselProducts();
    }, 2000);

    setTimeout(function() {
        processCarouselProducts();
    }, 3000);

    // Run when new content is loaded (for infinite scroll, AJAX, etc.)
    $(document).on('woocommerce_updated DOMNodeInserted', function() {
        setTimeout(function() {
            processCarouselProducts();
        }, 100);
    });

    // Also listen for window load (for images and other resources)
    $(window).on('load', function() {
        setTimeout(function() {
            processCarouselProducts();
        }, 500);
    });

})(jQuery);

