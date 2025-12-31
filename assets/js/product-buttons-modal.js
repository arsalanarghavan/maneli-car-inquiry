/**
 * Product Buttons Modal Handler
 * Handles opening calculator modals for Cash and Installment purchase buttons
 *
 * @package Autopuzzle_Car_Inquiry/Assets/JS
 * @version 1.0.0
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Function to ensure modal is in body
        function ensureModalInBody() {
            const modalElement = document.getElementById('autopuzzleCalculatorModal');
            if (modalElement) {
                // Check if modal is in body
                if (modalElement.parentElement && modalElement.parentElement.tagName !== 'BODY') {
                    // Clone modal to preserve event listeners
                    const clonedModal = modalElement.cloneNode(true);
                    // Remove old modal
                    modalElement.remove();
                    // Add to body
                    document.body.appendChild(clonedModal);
                    // Update reference
                    return document.getElementById('autopuzzleCalculatorModal');
                }
            }
            return modalElement;
        }

        // Ensure modal is in body on page load
        let modalElement = ensureModalInBody();

        // Initialize Bootstrap modal if available
        let calculatorModal = null;
        if (typeof bootstrap !== 'undefined' && modalElement) {
            try {
                calculatorModal = new bootstrap.Modal(modalElement, {
                    backdrop: true,
                    keyboard: true,
                    focus: true
                });
            } catch (e) {
                console.error('AutoPuzzle: Error initializing Bootstrap modal', e);
            }
        } else if ($.fn.modal && $('#autopuzzleCalculatorModal').length > 0) {
            // Fallback to jQuery Bootstrap modal
            calculatorModal = $('#autopuzzleCalculatorModal');
        }

        /**
         * Handle Cash Purchase button click
         */
        $(document).on('click', '.autopuzzle-btn-cash', function(e) {
            e.preventDefault();
            const productId = $(this).data('product-id');
            openCalculatorModal(productId, 'cash');
        });

        /**
         * Handle Installment Purchase button click
         */
        $(document).on('click', '.autopuzzle-btn-installment', function(e) {
            e.preventDefault();
            const productId = $(this).data('product-id');
            openCalculatorModal(productId, 'installment');
        });

        /**
         * Open calculator modal with specified tab
         *
         * @param {number} productId Product ID
         * @param {string} tabType 'cash' or 'installment'
         */
        function openCalculatorModal(productId, tabType) {
            if (!productId || !tabType) {
                console.error('AutoPuzzle: Invalid product ID or tab type');
                return;
            }

            // Show loading state
            const modalBody = $('#autopuzzleCalculatorModalBody');
            const modalTitle = $('#autopuzzleCalculatorModalLabel');
            
            modalBody.html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">' + autopuzzleButtonsModal.loadingText + '</span></div></div>');
            
            // Set modal title based on tab type
            if (tabType === 'cash') {
                modalTitle.text(autopuzzleButtonsModal.cashPurchaseTitle);
            } else {
                modalTitle.text(autopuzzleButtonsModal.installmentPurchaseTitle);
            }

            // Ensure modal is in body (not in footer) - BEFORE loading content
            let modalElement = ensureModalInBody();
            if (!modalElement) {
                console.error('AutoPuzzle: Modal element not found');
                return;
            }

            // Ensure modal has proper classes
            if (!modalElement.classList.contains('modal')) {
                modalElement.classList.add('modal');
            }
            if (!modalElement.classList.contains('fade')) {
                modalElement.classList.add('fade');
            }
            
            // Ensure modal is visible and positioned correctly
            $(modalElement).css({
                'position': 'fixed',
                'top': '0',
                'left': '0',
                'z-index': '1055'
            });

            // Open modal FIRST, then load content
            // Open modal using Bootstrap 5
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                try {
                    // Dispose any existing instance first
                    const existingInstance = bootstrap.Modal.getInstance(modalElement);
                    if (existingInstance) {
                        existingInstance.dispose();
                    }
                    
                    // Create new modal instance
                    const modalInstance = new bootstrap.Modal(modalElement, {
                        backdrop: true,
                        keyboard: true,
                        focus: true
                    });
                    
                    // Show modal immediately - this will move it to body automatically
                    modalInstance.show();
                    calculatorModal = modalInstance;
                    
                    // Force modal to be in body and properly positioned
                    const moveToBody = function() {
                        if (modalElement.parentElement && modalElement.parentElement.tagName !== 'BODY') {
                            document.body.appendChild(modalElement);
                        }
                        // Ensure proper positioning
                        $(modalElement).css({
                            'position': 'fixed',
                            'top': '0',
                            'left': '0',
                            'width': '100%',
                            'height': '100%',
                            'z-index': '1055'
                        });
                    };
                    moveToBody();
                    setTimeout(moveToBody, 10);
                    setTimeout(moveToBody, 50);
                } catch (e) {
                    console.error('AutoPuzzle: Error showing Bootstrap 5 modal', e);
                    // Fallback to jQuery
                    if ($.fn.modal) {
                        $('#autopuzzleCalculatorModal').modal('show');
                    }
                }
            } else if ($.fn.modal && $('#autopuzzleCalculatorModal').length > 0) {
                // Fallback to jQuery Bootstrap modal
                $('#autopuzzleCalculatorModal').modal({
                    backdrop: true,
                    keyboard: true,
                    show: true
                });
            }

            // Load tab content via AJAX AFTER modal is shown
            $.ajax({
                url: autopuzzleButtonsModal.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'autopuzzle_get_calculator_tab',
                    nonce: autopuzzleButtonsModal.nonce,
                    product_id: productId,
                    tab_type: tabType
                },
                success: function(response) {
                    if (response.success && response.data && response.data.html) {
                        modalBody.html(response.data.html);
                        
                        // Initialize calculator if installment tab
                        if (tabType === 'installment') {
                            // Wait a bit for DOM to be ready
                            setTimeout(function() {
                                initializeInstallmentCalculator(productId);
                            }, 100);
                        }
                        
                        // Ensure calculator assets are loaded
                        // Load calculator.js if not already loaded
                        if (typeof autopuzzle_ajax_object === 'undefined') {
                            // Try to load calculator assets dynamically
                            if (typeof jQuery !== 'undefined') {
                                // Load calculator CSS
                                if (!$('link[href*="loan-calculator.css"]').length) {
                                    $('head').append('<link rel="stylesheet" href="' + autopuzzleButtonsModal.pluginUrl + 'assets/css/loan-calculator.css" type="text/css">');
                                }
                                // Load calculator JS
                                if (!$('script[src*="calculator.js"]').length) {
                                    $.getScript(autopuzzleButtonsModal.pluginUrl + 'assets/js/calculator.js', function() {
                                        if (tabType === 'installment') {
                                            setTimeout(function() {
                                                initializeInstallmentCalculator(productId);
                                            }, 100);
                                        }
                                    });
                                }
                            }
                        } else {
                            // Calculator assets already loaded
                            if (tabType === 'installment') {
                                setTimeout(function() {
                                    initializeInstallmentCalculator(productId);
                                }, 100);
                            }
                        }
                    } else {
                        modalBody.html('<div class="alert alert-danger">' + (response.data && response.data.message ? response.data.message : autopuzzleButtonsModal.errorText) + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AutoPuzzle: Error loading calculator tab', error);
                    modalBody.html('<div class="alert alert-danger">' + autopuzzleButtonsModal.errorText + '</div>');
                }
            });
        }

        /**
         * Initialize installment calculator in modal
         *
         * @param {number} productId Product ID
         */
        function initializeInstallmentCalculator(productId) {
            // Wait a bit for DOM to be ready
            setTimeout(function() {
                const calculatorEl = document.getElementById('loan-calculator-modal');
                if (!calculatorEl) {
                    return;
                }

                const price = parseFloat(calculatorEl.getAttribute('data-price')) || 0;
                const minDown = parseFloat(calculatorEl.getAttribute('data-min-down')) || 0;
                const maxDown = parseFloat(calculatorEl.getAttribute('data-max-down')) || 0;
                const canSeePrices = calculatorEl.getAttribute('data-can-see-prices') === 'true';
                const isUnavailable = calculatorEl.getAttribute('data-is-unavailable') === 'true';

                if (isUnavailable || price <= 0) {
                    return;
                }

                // Initialize calculator if the function exists
                if (typeof window.autopuzzleInitializeCalculator === 'function') {
                    window.autopuzzleInitializeCalculator('loan-calculator-modal', {
                        price: price,
                        minDown: minDown,
                        maxDown: maxDown,
                        canSeePrices: canSeePrices
                    });
                } else if (typeof initializeCalculator === 'function') {
                    // Fallback to global function
                    initializeCalculator();
                } else {
                    // Manual initialization
                    initCalculatorManually(calculatorEl, price, minDown, maxDown);
                }
            }, 100);
        }

        /**
         * Manually initialize calculator if auto-init doesn't work
         */
        function initCalculatorManually(calculatorEl, price, minDown, maxDown) {
            const downPaymentInput = calculatorEl.querySelector('#downPaymentInputModal');
            const downPaymentSlider = calculatorEl.querySelector('#downPaymentSliderModal');
            const termButtons = calculatorEl.querySelectorAll('.term-btn');
            const installmentAmount = calculatorEl.querySelector('#installmentAmountModal');

            if (!downPaymentInput || !downPaymentSlider || !installmentAmount) {
                return;
            }

            // Set slider range
            downPaymentSlider.min = minDown;
            downPaymentSlider.max = maxDown;
            downPaymentSlider.value = minDown;

            // Format and set initial value
            const formatNumber = function(num) {
                return new Intl.NumberFormat('fa-IR').format(num);
            };

            const updateInstallment = function() {
                const downPayment = parseFloat(downPaymentInput.value.replace(/,/g, '')) || minDown;
                const selectedTerm = calculatorEl.querySelector('.term-btn.active');
                const months = selectedTerm ? parseInt(selectedTerm.getAttribute('data-months')) : 12;
                
                const loanAmount = price - downPayment;
                const interestRate = (typeof autopuzzle_ajax_object !== 'undefined' && autopuzzle_ajax_object.interestRate) 
                    ? autopuzzle_ajax_object.interestRate 
                    : 0.035; // Default 3.5% monthly
                
                const monthlyPayment = loanAmount * (interestRate * Math.pow(1 + interestRate, months)) / (Math.pow(1 + interestRate, months) - 1);
                
                installmentAmount.textContent = formatNumber(Math.round(monthlyPayment));
            };

            // Sync slider and input
            downPaymentSlider.addEventListener('input', function() {
                downPaymentInput.value = formatNumber(parseInt(this.value));
                updateInstallment();
            });

            downPaymentInput.addEventListener('input', function() {
                let value = parseInt(this.value.replace(/,/g, '')) || minDown;
                value = Math.max(minDown, Math.min(maxDown, value));
                this.value = formatNumber(value);
                downPaymentSlider.value = value;
                updateInstallment();
            });

            // Term button handlers
            termButtons.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    termButtons.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    updateInstallment();
                });
            });

            // Initial calculation
            downPaymentInput.value = formatNumber(minDown);
            updateInstallment();
        }

        // Clean up on modal close
        $(document).on('hidden.bs.modal', '#autopuzzleCalculatorModal', function() {
            $('#autopuzzleCalculatorModalBody').html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">' + autopuzzleButtonsModal.loadingText + '</span></div></div>');
            // Remove backdrop if exists
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open');
            $('body').css('padding-right', '');
        });

        // Also handle when modal is shown
        $(document).on('shown.bs.modal', '#autopuzzleCalculatorModal', function() {
            // Ensure backdrop is visible
            if ($('.modal-backdrop').length === 0) {
                $('body').append('<div class="modal-backdrop fade show"></div>');
            }
        });

        /**
         * Replace Elementor carousel buttons with custom purchase buttons
         */
        function replaceElementorCarouselButtons() {
            // Find all Elementor carousel products
            $('.eael-woo-product-carousel .product, .swiper-slide.product').each(function() {
                const $product = $(this);
                const productId = getProductIdFromElement($product);
                
                if (!productId) {
                    return; // Skip if product ID not found
                }

                // Check if already processed
                if ($product.data('autopuzzle-buttons-replaced')) {
                    return;
                }

                // Find buttons container
                const $buttonsWrap = $product.find('.buttons-wrap .icons-wrap');
                if ($buttonsWrap.length === 0) {
                    return;
                }

                // Check if product has payment tags to determine prices (FAST - no AJAX)
                // Method 1: Check for rendered tags in DOM
                const $cashTag = $product.find('.autopuzzle-payment-tag--cash');
                const $installmentTag = $product.find('.autopuzzle-payment-tag--installment');
                let hasCash = $cashTag.length > 0;
                let hasInstallment = $installmentTag.length > 0;
                
                // Method 2: Check autopuzzleProductTags data (from PHP)
                if (typeof autopuzzleProductTags !== 'undefined' && 
                    autopuzzleProductTags.products && 
                    autopuzzleProductTags.products[productId]) {
                    const productData = autopuzzleProductTags.products[productId];
                    hasCash = hasCash || (productData.cash === true);
                    hasInstallment = hasInstallment || (productData.installment === true);
                }
                
                // Method 3: If still not found, assume both exist (will be hidden if wrong)
                // This ensures buttons are shown immediately
                if (!hasCash && !hasInstallment) {
                    // Check if product has any price-related data
                    const $priceElement = $product.find('.price, .woocommerce-Price-amount');
                    if ($priceElement.length > 0) {
                        // Assume both exist if price element exists
                        hasCash = true;
                        hasInstallment = true;
                    }
                }

                // Replace quick-view button with cash purchase button
                // Always show button - will be hidden later if no price
                const $quickView = $buttonsWrap.find('li.eael-product-quick-view');
                if ($quickView.length > 0) {
                    const $cashBtn = $('<li class="autopuzzle-cash-purchase"></li>');
                    $cashBtn.html(
                        '<a href="#" class="autopuzzle-btn-cash" data-product-id="' + productId + '" data-tab-type="cash" title="' + autopuzzleButtonsModal.cashPurchaseTitle + '">' +
                        '<i class="fas fa-money-bill"></i>' +
                        '</a>'
                    );
                    $quickView.replaceWith($cashBtn);
                    
                    // Hide if no cash price
                    if (!hasCash) {
                        $cashBtn.hide();
                    }
                }

                // Replace view-details button with installment purchase button
                // Always show button - will be hidden later if no price
                const $viewDetails = $buttonsWrap.find('li.view-details');
                if ($viewDetails.length > 0) {
                    const $installmentBtn = $('<li class="autopuzzle-installment-purchase"></li>');
                    $installmentBtn.html(
                        '<a href="#" class="autopuzzle-btn-installment" data-product-id="' + productId + '" data-tab-type="installment" title="' + autopuzzleButtonsModal.installmentPurchaseTitle + '">' +
                        '<i class="fas fa-credit-card"></i>' +
                        '</a>'
                    );
                    $viewDetails.replaceWith($installmentBtn);
                    
                    // Hide if no installment price
                    if (!hasInstallment) {
                        $installmentBtn.hide();
                    }
                }

                // Replace "View Product" button text with icon
                const $addToCart = $buttonsWrap.find('li.add-to-cart');
                if ($addToCart.length > 0) {
                    const $viewLink = $addToCart.find('a.autopuzzle-view-product-btn, a.button');
                    if ($viewLink.length > 0) {
                        const productUrl = $viewLink.attr('href');
                        if (productUrl) {
                            const $viewBtn = $('<li class="autopuzzle-view-product"></li>');
                            $viewBtn.html(
                                '<a href="' + productUrl + '" class="autopuzzle-btn-view" title="' + (autopuzzleButtonsModal.viewProductTitle || 'View Product') + '">' +
                                '<i class="fas fa-eye"></i>' +
                                '</a>'
                            );
                            $addToCart.replaceWith($viewBtn);
                        }
                    }
                }

                // Mark as processed
                $product.data('autopuzzle-buttons-replaced', true);
            });
        }

        /**
         * Get product ID from product element
         *
         * @param {jQuery} $product Product element
         * @return {number|null} Product ID
         */
        function getProductIdFromElement($product) {
            // Try multiple methods to get product ID
            let productId = null;

            // Method 1: From data attribute
            productId = $product.data('product-id') || $product.attr('data-product-id');
            if (productId) {
                return parseInt(productId);
            }

            // Method 2: From post ID class
            const classes = $product.attr('class') || '';
            const postIdMatch = classes.match(/post-(\d+)/);
            if (postIdMatch) {
                return parseInt(postIdMatch[1]);
            }

            // Method 3: From product link
            const $link = $product.find('a[href*="/product/"]').first();
            if ($link.length > 0) {
                const href = $link.attr('href');
                const urlMatch = href.match(/\/product\/[^\/]+\/(\d+)/);
                if (urlMatch) {
                    return parseInt(urlMatch[1]);
                }
            }

            // Method 4: From quick view data attribute
            const $quickView = $product.find('.eael-product-quick-view a');
            if ($quickView.length > 0) {
                const quickViewData = $quickView.data('quickview-setting');
                if (quickViewData && quickViewData.product_id) {
                    return parseInt(quickViewData.product_id);
                }
            }

            return null;
        }


        // Initial replacement on page load - immediate execution
        replaceElementorCarouselButtons();
        
        // Also run after a very short delay to catch any late-loading elements
        setTimeout(function() {
            replaceElementorCarouselButtons();
        }, 50);

        // Use MutationObserver to watch for new products in carousel
        if (typeof MutationObserver !== 'undefined') {
            const observer = new MutationObserver(function(mutations) {
                let shouldReplace = false;
                mutations.forEach(function(mutation) {
                    if (mutation.addedNodes.length > 0) {
                        mutation.addedNodes.forEach(function(node) {
                            if (node.nodeType === 1) { // Element node
                                const $node = $(node);
                                if ($node.hasClass('product') || 
                                    $node.find('.product').length > 0 ||
                                    $node.hasClass('swiper-slide') ||
                                    $node.find('.swiper-slide').length > 0) {
                                    shouldReplace = true;
                                }
                            }
                        });
                    }
                });
                
                if (shouldReplace) {
                    // Immediate replacement for better UX
                    replaceElementorCarouselButtons();
                }
            });

            // Observe carousel containers
            $('.eael-woo-product-carousel, .swiper-container-wrap').each(function() {
                observer.observe(this, {
                    childList: true,
                    subtree: true
                });
            });

            // Also observe Swiper wrapper
            $('.swiper-wrapper').each(function() {
                observer.observe(this, {
                    childList: true,
                    subtree: true
                });
            });
        }

        // Also listen to Swiper events if available
        $(document).on('swiper:slideChange', '.eael-woo-product-carousel', function() {
            replaceElementorCarouselButtons();
        });

        // Listen to Elementor events
        $(window).on('elementor/popup/show', function() {
            setTimeout(function() {
                replaceElementorCarouselButtons();
            }, 50);
        });

        // Replace buttons when carousel is initialized
        $(document).on('swiper:init', '.eael-woo-product-carousel', function() {
            replaceElementorCarouselButtons();
        });
        
        // Also run on DOMContentLoaded and window load
        $(window).on('load', function() {
            replaceElementorCarouselButtons();
        });
    });

})(jQuery);

