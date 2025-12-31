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
         * Organize buttons to be displayed inline next to View Product button
         */
        function organizeProductButtons() {
            $('.woocommerce ul.products li.product').each(function() {
                const $product = $(this);
                const $viewProductWrapper = $product.find('.autopuzzle-view-product-wrapper');
                const $productButtons = $product.find('.autopuzzle-product-buttons');
                
                // If both exist, move buttons next to View Product button
                if ($viewProductWrapper.length && $productButtons.length) {
                    // Create a container for all buttons
                    let $buttonsContainer = $product.find('.autopuzzle-all-buttons-container');
                    if (!$buttonsContainer.length) {
                        $buttonsContainer = $('<div class="autopuzzle-all-buttons-container"></div>');
                        // Insert after price or before any existing buttons
                        const $price = $product.find('.price, .woocommerce-Price-amount');
                        if ($price.length) {
                            $price.parent().after($buttonsContainer);
                        } else {
                            $viewProductWrapper.parent().append($buttonsContainer);
                        }
                    }
                    
                    // Move buttons into container if not already there
                    if ($viewProductWrapper.parent('.autopuzzle-all-buttons-container').length === 0) {
                        $buttonsContainer.append($viewProductWrapper);
                    }
                    if ($productButtons.parent('.autopuzzle-all-buttons-container').length === 0) {
                        $buttonsContainer.append($productButtons);
                    }
                }
            });
        }

        // Organize buttons on page load
        organizeProductButtons();
        
        // Also organize after AJAX content loads (for infinite scroll, etc.)
        $(document).on('woocommerce_loaded', organizeProductButtons);
        $(document).on('updated_wc_div', organizeProductButtons);

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
                        // Ensure jQuery is available
                        if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
                            modalBody.html('<div class="alert alert-danger">jQuery is not loaded. Please refresh the page.</div>');
                            return;
                        }
                        
                        // Load ALL required assets first, then set up modal content
                        if (response.data.assets) {
                            const assets = response.data.assets;
                            let assetsToLoad = 0;
                            let assetsLoaded = 0;
                            
                            // Count assets that need to be loaded
                            if (assets.peyda_font && !$('link[href*="peyda-font.css"]').length) assetsToLoad++;
                            if (assets.line_awesome && !$('link[href*="line-awesome"]').length) assetsToLoad++;
                            if (assets.frontend_css && !$('link[href*="frontend.css"]').length) assetsToLoad++;
                            if (assets.bootstrap_rtl && !$('link[href*="bootstrap.rtl"]').length) assetsToLoad++;
                            if (assets.calculator_css && !$('link[href*="loan-calculator.css"]').length) assetsToLoad++;
                            if (assets.cash_inquiry_css && !$('link[href*="cash-inquiry.css"]').length) assetsToLoad++;
                            if (assets.sweetalert2_css && !$('link[href*="sweetalert2"]').length) assetsToLoad++;
                            if (assets.select2_css && !$('link[href*="select2"]').length) assetsToLoad++;
                            if (assets.calculator_js && !$('script[src*="calculator.js"]').length) assetsToLoad++;
                            if (assets.sweetalert2_js && !$('script[src*="sweetalert2"]').length) assetsToLoad++;
                            if (assets.select2_js && !$('script[src*="select2"]').length) assetsToLoad++;
                            
                            // Function to check if all assets are loaded
                            const checkAllAssetsLoaded = function() {
                                assetsLoaded++;
                                if (assetsLoaded >= assetsToLoad || assetsToLoad === 0) {
                                    // All assets loaded, set up modal content
                                    setupModalContent(response.data.html, tabType, productId, modalBody);
                                }
                            };
                            
                            // If no assets need loading, set up content immediately
                            if (assetsToLoad === 0) {
                                setupModalContent(response.data.html, tabType, productId, modalBody);
                            } else {
                                // Load CSS files
                                if (assets.peyda_font && !$('link[href*="peyda-font.css"]').length) {
                                    $('<link>').attr({rel: 'stylesheet', type: 'text/css', href: assets.peyda_font})
                                        .appendTo('head').on('load', checkAllAssetsLoaded).on('error', checkAllAssetsLoaded);
                                }
                                
                                if (assets.line_awesome && !$('link[href*="line-awesome"]').length) {
                                    $('<link>').attr({rel: 'stylesheet', type: 'text/css', href: assets.line_awesome})
                                        .appendTo('head').on('load', checkAllAssetsLoaded).on('error', checkAllAssetsLoaded);
                                }
                                
                                if (assets.frontend_css && !$('link[href*="frontend.css"]').length) {
                                    $('<link>').attr({rel: 'stylesheet', type: 'text/css', href: assets.frontend_css})
                                        .appendTo('head').on('load', checkAllAssetsLoaded).on('error', checkAllAssetsLoaded);
                                }
                                
                                if (assets.bootstrap_rtl && !$('link[href*="bootstrap.rtl"]').length) {
                                    $('<link>').attr({rel: 'stylesheet', type: 'text/css', href: assets.bootstrap_rtl})
                                        .appendTo('head').on('load', checkAllAssetsLoaded).on('error', checkAllAssetsLoaded);
                                }
                                
                                if (assets.calculator_css && !$('link[href*="loan-calculator.css"]').length) {
                                    $('<link>').attr({rel: 'stylesheet', type: 'text/css', href: assets.calculator_css})
                                        .appendTo('head').on('load', checkAllAssetsLoaded).on('error', checkAllAssetsLoaded);
                                }
                                
                                if (assets.cash_inquiry_css && !$('link[href*="cash-inquiry.css"]').length) {
                                    $('<link>').attr({rel: 'stylesheet', type: 'text/css', href: assets.cash_inquiry_css})
                                        .appendTo('head').on('load', checkAllAssetsLoaded).on('error', checkAllAssetsLoaded);
                                }
                                
                                if (assets.sweetalert2_css && !$('link[href*="sweetalert2"]').length) {
                                    $('<link>').attr({rel: 'stylesheet', type: 'text/css', href: assets.sweetalert2_css})
                                        .appendTo('head').on('load', checkAllAssetsLoaded).on('error', checkAllAssetsLoaded);
                                }
                                
                                if (assets.select2_css && !$('link[href*="select2"]').length) {
                                    $('<link>').attr({rel: 'stylesheet', type: 'text/css', href: assets.select2_css})
                                        .appendTo('head').on('load', checkAllAssetsLoaded).on('error', checkAllAssetsLoaded);
                                }
                                
                                // Load JS files
                                if (assets.calculator_js && !$('script[src*="calculator.js"]').length) {
                                    $.getScript(assets.calculator_js)
                                        .done(checkAllAssetsLoaded)
                                        .fail(checkAllAssetsLoaded);
                                }
                                
                                if (assets.sweetalert2_js && !$('script[src*="sweetalert2"]').length) {
                                    $.getScript(assets.sweetalert2_js)
                                        .done(checkAllAssetsLoaded)
                                        .fail(checkAllAssetsLoaded);
                                }
                                
                                if (assets.select2_js && !$('script[src*="select2"]').length) {
                                    $.getScript(assets.select2_js)
                                        .done(checkAllAssetsLoaded)
                                        .fail(checkAllAssetsLoaded);
                                }
                            }
                        } else {
                            // No assets in response, assume they're already loaded
                            setupModalContent(response.data.html, tabType, productId, modalBody);
                        }
                    } else {
                        modalBody.html('<div class="alert alert-danger">' + (response.data && response.data.message ? response.data.message : autopuzzleButtonsModal.errorText) + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    // Only log errors in debug mode to prevent console spam
                    if (typeof WP_DEBUG !== 'undefined' && WP_DEBUG) {
                        console.error('AutoPuzzle: Error loading calculator tab', {
                            status: status,
                            error: error,
                            statusCode: xhr.status,
                            responseText: xhr.responseText
                        });
                    }
                    
                    // Try to parse error message from response
                    let errorMessage = autopuzzleButtonsModal.errorText;
                    try {
                        if (xhr.responseText) {
                            const response = JSON.parse(xhr.responseText);
                            if (response.data && response.data.message) {
                                errorMessage = response.data.message;
                            }
                        }
                    } catch (e) {
                        // Use default error message
                    }
                    
                    modalBody.html('<div class="alert alert-danger">' + errorMessage + '</div>');
                }
            });
        }

        /**
         * Setup modal content and initialize calculator
         *
         * @param {string} html Modal HTML content
         * @param {string} tabType Tab type ('cash' or 'installment')
         * @param {number} productId Product ID
         * @param {jQuery} $modalBody jQuery object for modal body
         */
        function setupModalContent(html, tabType, productId, $modalBody) {
            // Get modal body if not provided
            if (!$modalBody || $modalBody.length === 0) {
                $modalBody = $('#autopuzzleCalculatorModalBody');
            }
            
            // Ensure modal body exists
            if (!$modalBody || $modalBody.length === 0) {
                console.error('AutoPuzzle: Modal body not found');
                return;
            }
            
            $modalBody.html(html);
            
            // Wait for DOM to be ready and assets to be loaded
            setTimeout(function() {
                // For installment tab, initialize calculator using calculator.js functions
                if (tabType === 'installment') {
                    // Use calculator.js exported function
                    if (typeof window.autopuzzleInitInstallmentCalculator === 'function') {
                        // Pass null as container since we don't have .autopuzzle-calculator-container in modal
                        // initInstallmentCalculator will find elements by ID (#loan-calculator, #installment-tab)
                        const result = window.autopuzzleInitInstallmentCalculator(null);
                        if (!result) {
                            // Fallback to manual initialization
                            initializeInstallmentCalculator(productId);
                        }
                    } else {
                        // Fallback to manual initialization
                        initializeInstallmentCalculator(productId);
                    }
                }
                
                // For cash tab, initialize cash form handlers
                if (tabType === 'cash') {
                    // Use calculator.js exported function
                    if (typeof window.autopuzzleInitCashTab === 'function') {
                        window.autopuzzleInitCashTab();
                    }
                }
            }, 200);
        }

        /**
         * Initialize installment calculator in modal
         * Uses the same logic as calculator.js but adapted for modal
         *
         * @param {number} productId Product ID
         */
        function initializeInstallmentCalculator(productId) {
            // Wait a bit for DOM to be ready
            setTimeout(function() {
                // Use same ID as shortcode for compatibility
                const calculatorEl = document.getElementById('loan-calculator');
                if (!calculatorEl) {
                    console.warn('AutoPuzzle Modal: loan-calculator not found');
                    return;
                }

                const installmentTab = document.getElementById('installment-tab');
                if (!installmentTab) {
                    console.warn('AutoPuzzle Modal: installment-tab not found');
                    return;
                }

                // Parse values from data attributes
                const priceAttr = calculatorEl.getAttribute('data-price');
                const minDownAttr = calculatorEl.getAttribute('data-min-down');
                const maxDownAttr = calculatorEl.getAttribute('data-max-down');
                const canSeePrices = calculatorEl.getAttribute('data-can-see-prices') === 'true';
                const isUnavailable = calculatorEl.getAttribute('data-is-unavailable') === 'true';

                // Parse money values (handle Persian digits and formatting)
                const parseMoney = function(value) {
                    if (!value) return 0;
                    const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
                    const englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
                    let clean = String(value).replace(persianDigits, function(match) {
                        return englishDigits[persianDigits.indexOf(match)];
                    });
                    clean = clean.replace(/[^\d]/g, '');
                    return parseInt(clean, 10) || 0;
                };

                let productPrice = parseMoney(priceAttr);
                let minDown = parseMoney(minDownAttr);
                let maxDown = productPrice * 0.8;
                if (maxDownAttr) {
                    const parsedMax = parseMoney(maxDownAttr);
                    if (parsedMax > 0) {
                        maxDown = parsedMax;
                    }
                }

                // Get DOM elements - use same IDs as shortcode
                const input = document.getElementById('downPaymentInput');
                const slider = document.getElementById('downPaymentSlider');
                const minDisplay = installmentTab.querySelector('#minDownDisplay');
                let installmentEl = installmentTab.querySelector('#installmentAmount');
                const termButtons = installmentTab.querySelectorAll('.term-btn');
                const actionBtn = installmentTab.querySelector('.loan-action-btn');

                if (!input || !slider || !installmentEl) {
                    console.warn('AutoPuzzle Modal: Required calculator elements not found', {
                        input: !!input,
                        slider: !!slider,
                        installmentEl: !!installmentEl
                    });
                    return;
                }

                // Format money for display
                const formatMoney = function(amount) {
                    if (typeof Intl !== 'undefined' && Intl.NumberFormat) {
                        return new Intl.NumberFormat('fa-IR').format(amount);
                    }
                    return amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                };

                // Get interest rate
                const interestRate = (typeof autopuzzle_ajax_object !== 'undefined' && autopuzzle_ajax_object.interestRate)
                                   ? parseFloat(autopuzzle_ajax_object.interestRate) 
                                   : 0.035;

                // Calculate installment
                const calculateInstallment = function() {
                    const downPayment = parseMoney(input.value) || minDown;
                    const activeBtn = installmentTab.querySelector('.term-btn.active');
                    const months = activeBtn ? parseInt(activeBtn.getAttribute('data-months')) || 12 : 12;
                    
                    const loanAmount = productPrice - downPayment;
                    if (loanAmount <= 0 || productPrice <= 0 || months <= 0) {
                        installmentEl.innerText = formatMoney(0);
                        return;
                    }

                    // Same calculation as calculator.js
                    const monthlyInterestAmount = loanAmount * interestRate;
                    const totalInterest = monthlyInterestAmount * (months + 1);
                    const totalRepayment = loanAmount + totalInterest;
                    const installment = totalRepayment / months;

                    installmentEl.innerText = formatMoney(Math.ceil(installment));
                };

                // Set slider range
                slider.min = minDown;
                slider.max = maxDown;
                slider.value = minDown;

                // Update slider visual
                const updateSliderVisual = function() {
                    if (!slider.max || slider.max === slider.min) return;
                    const percentage = ((slider.value - slider.min) / (slider.max - slider.min)) * 100;
                    slider.style.setProperty('--value-percent', percentage + '%');
                };

                // Sync slider and input
                slider.addEventListener('input', function() {
                    input.value = formatMoney(parseInt(this.value));
                    updateSliderVisual();
                    calculateInstallment();
                });

                input.addEventListener('input', function() {
                    let value = parseMoney(this.value) || minDown;
                    value = Math.max(minDown, Math.min(maxDown, value));
                    this.value = formatMoney(value);
                    slider.value = value;
                    updateSliderVisual();
                    calculateInstallment();
                });

                // Term button handlers
                termButtons.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        termButtons.forEach(b => b.classList.remove('active'));
                        this.classList.add('active');
                        calculateInstallment();
                    });
                });

                // Initial setup
                input.value = formatMoney(minDown);
                updateSliderVisual();
                calculateInstallment();

                // Make sure elements are visible
                if (installmentEl && installmentEl.style.display === 'none') {
                    installmentEl.style.display = '';
                }
                if (minDisplay && minDisplay.style.display === 'none' && minDown > 0) {
                    minDisplay.style.display = '';
                }

                console.log('AutoPuzzle Modal: Calculator initialized successfully');
            }, 150);
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

