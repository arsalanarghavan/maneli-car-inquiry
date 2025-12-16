/**
 * Handles the installment calculator modal for car replacement in dashboard step 3
 * This is a standalone implementation (not touching the shortcode calculator)
 * 
 * @version 1.0.0
 */

(function() {
    'use strict';

    let modalCalculatorInitialized = false;
    let currentProductData = null;

    /**
     * Initialize the modal calculator when it's shown
     */
    function initModalCalculator() {
        if (modalCalculatorInitialized) {
            return;
        }

        const modal = document.getElementById('installmentCalculatorModal');
        if (!modal) {
            return;
        }

        // Initialize when modal is shown
        modal.addEventListener('show.bs.modal', function(event) {
            // Get product data from window.maneliModalProductData (set by inquiry-form.js)
            if (window.maneliModalProductData) {
                currentProductData = window.maneliModalProductData;
            } else {
                // Fallback: try to get from button if available
                const button = event.relatedTarget;
                if (button) {
                    const productId = button.getAttribute('data-product-id');
                    const productPrice = parseFloat(button.getAttribute('data-product-price')) || 0;
                    const productName = button.getAttribute('data-product-name') || '';
                    const productImage = button.getAttribute('data-product-image') || '';
                    
                    currentProductData = {
                        id: productId,
                        price: productPrice,
                        name: productName,
                        image: productImage
                    };
                }
            }
            
            // If we have product data, initialize calculator
            if (currentProductData && currentProductData.id) {
                setupModalCalculator(currentProductData);
            } else {
                console.error('Modal Calculator: No product data available');
            }
        });

        // Reset when modal is hidden
        modal.addEventListener('hidden.bs.modal', function() {
            resetModalCalculator();
            currentProductData = null;
        });

        modalCalculatorInitialized = true;
    }

    /**
     * Setup calculator with product data
     */
    function setupModalCalculator(productData) {
        const calc = document.getElementById('modal-loan-calculator');
        if (!calc) {
            console.error('Modal Calculator: Element not found');
            return;
        }

        // Calculate min/max down payment from product price if not provided
        if (!productData.minDown || !productData.maxDown) {
            const productPrice = productData.price || 0;
            productData.minDown = productData.minDown || (productPrice * 0.2);
            productData.maxDown = productData.maxDown || (productPrice * 0.8);
        }
        
        initializeCalculator(productData);
    }


    /**
     * Initialize the calculator with product data
     */
    function initializeCalculator(productData) {
        const calc = document.getElementById('modal-loan-calculator');
        const input = document.getElementById('modalDownPaymentInput');
        const slider = document.getElementById('modalDownPaymentSlider');
        const installmentEl = document.getElementById('modalInstallmentAmount');
        const minDisplay = document.getElementById('modalMinDownDisplay');
        const productIdInput = document.getElementById('modal-product-id');
        const carNameEl = document.getElementById('modal-car-name');
        const carImageEl = document.getElementById('modal-car-image');

        if (!calc || !input || !slider || !installmentEl) {
            console.error('Modal Calculator: Required elements not found');
            return;
        }

        // Set product info
        if (productIdInput) productIdInput.value = productData.id;
        if (carNameEl && productData.name) carNameEl.textContent = productData.name;
        if (carImageEl && productData.image) carImageEl.innerHTML = productData.image;

        // Calculate price and limits
        const productPrice = productData.price || 0;
        const minDown = productData.minDown || (productPrice * 0.2);
        const maxDown = productData.maxDown || (productPrice * 0.8);

        // Update data attributes
        calc.setAttribute('data-price', productPrice);
        calc.setAttribute('data-min-down', minDown);
        calc.setAttribute('data-max-down', maxDown);

        // Update display
        if (minDisplay) minDisplay.textContent = formatMoney(minDown);

        // Initialize slider and input
        slider.min = 0;
        slider.max = maxDown;
        slider.value = minDown;
        input.value = formatMoney(minDown);

        // Get interest rate
        const interestRate = (typeof autopuzzle_ajax_object !== 'undefined' && autopuzzle_ajax_object.interestRate)
                           ? parseFloat(autopuzzle_ajax_object.interestRate) 
                           : 0.035;

        // Calculate and display installment
        function calculateInstallment() {
            const downPayment = parseMoney(input.value) || 0;
            const activeBtn = document.querySelector('#modal-installment-tab .term-btn.active');
            const months = activeBtn ? parseInt(activeBtn.dataset.months) || 12 : 12;
            
            const loanAmount = productPrice - downPayment;
            
            if (loanAmount <= 0 || productPrice <= 0 || months <= 0) {
                installmentEl.textContent = formatMoney(0);
                return;
            }

            // Calculation: monthlyInterestAmount * (months + 1) = total interest
            const monthlyInterestAmount = loanAmount * interestRate;
            const totalInterest = monthlyInterestAmount * (months + 1);
            const totalRepayment = loanAmount + totalInterest;
            const installment = totalRepayment / months;

            installmentEl.textContent = formatMoney(Math.ceil(installment));
        }

        // Event listeners
        slider.addEventListener('input', function() {
            input.value = formatMoney(parseInt(this.value) || 0);
            updateSliderVisual(slider);
            calculateInstallment();
        });

        slider.addEventListener('change', function() {
            let value = parseInt(this.value) || 0;
            let clamped = clamp(value, minDown, maxDown);
            if (value !== clamped) {
                this.value = clamped;
                input.value = formatMoney(clamped);
            }
            updateSliderVisual(slider);
            calculateInstallment();
        });

        input.addEventListener('input', function() {
            let value = parseMoney(this.value) || 0;
            slider.value = clamp(value, minDown, maxDown);
            updateSliderVisual(slider);
            calculateInstallment();
        });

        input.addEventListener('blur', function() {
            let value = parseMoney(this.value) || 0;
            let clamped = clamp(value, minDown, maxDown);
            slider.value = clamped;
            this.value = formatMoney(clamped);
            updateSliderVisual(slider);
            calculateInstallment();
        });

        // Term button events
        const termButtons = document.querySelectorAll('#modal-installment-tab .term-btn');
        termButtons.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                termButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                calculateInstallment();
            });
        });

        // Initial calculation
        calculateInstallment();
        updateSliderVisual(slider);

        // Confirm button
        const confirmBtn = document.getElementById('modalConfirmCarBtn');
        if (confirmBtn) {
            confirmBtn.onclick = function() {
                // Get raw values first for debugging
                const inputValue = input.value;
                const installmentText = installmentEl.textContent;
                const activeTermBtn = document.querySelector('#modal-installment-tab .term-btn.active');
                
                // Parse values
                const downPayment = parseMoney(inputValue) || 0;
                const termMonths = activeTermBtn ? parseInt(activeTermBtn.dataset.months) || 12 : 12;
                const installmentAmount = parseMoney(installmentText) || 0;

                // Debug logging
                console.log('🔵 Modal Calculator - Values being sent:');
                console.log('  Input value:', inputValue);
                console.log('  Parsed down payment:', downPayment);
                console.log('  Term months:', termMonths);
                console.log('  Installment text:', installmentText);
                console.log('  Parsed installment:', installmentAmount);
                console.log('  Product ID:', productData.id);
                console.log('  Product price:', productPrice);

                // Store calculated values for replacement
                if (window.maneliModalCalculatorData) {
                    window.maneliModalCalculatorData = {
                        product_id: productData.id,
                        down_payment: downPayment,
                        term_months: termMonths,
                        installment_amount: installmentAmount,
                        total_price: productPrice
                    };
                }

                const eventData = {
                    product_id: productData.id,
                    down_payment: downPayment,
                    term_months: termMonths,
                    installment_amount: installmentAmount,
                    total_price: productPrice
                };

                // Trigger replacement via custom event
                const replaceEvent = new CustomEvent('maneliReplaceCarFromModal', {
                    detail: eventData
                });
                console.log('🔵 Dispatching event with data:', eventData);
                document.dispatchEvent(replaceEvent);

                // Close modal after triggering event
                const modal = document.getElementById('installmentCalculatorModal');
                if (modal) {
                    // Try Bootstrap 5
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        const modalInstance = bootstrap.Modal.getInstance(modal);
                        if (modalInstance) {
                            modalInstance.hide();
                        }
                    } else if (typeof jQuery !== 'undefined' && jQuery(modal).modal) {
                        // Fallback to jQuery Bootstrap 4
                        jQuery(modal).modal('hide');
                    }
                }
            };
        }
    }

    /**
     * Reset calculator when modal is closed
     */
    function resetModalCalculator() {
        const input = document.getElementById('modalDownPaymentInput');
        const slider = document.getElementById('modalDownPaymentSlider');
        const installmentEl = document.getElementById('modalInstallmentAmount');
        
        if (input) input.value = '';
        if (slider) slider.value = 0;
        if (installmentEl) installmentEl.textContent = '0';
    }

    /**
     * Update slider visual fill with gradient
     */
    function updateSliderVisual(slider) {
        if (!slider || !slider.max || slider.max === slider.min) return;
        const percentage = ((slider.value - slider.min) / (slider.max - slider.min)) * 100;
        slider.style.setProperty('--value-percent', percentage + '%');
        
        // Set background gradient directly for better browser support
        const gradient = `linear-gradient(to right, #2D89BE 0%, #2D89BE ${percentage}%, #e2e6f1 ${percentage}%, #e2e6f1 100%)`;
        slider.style.background = gradient;
        
        // Also update webkit track
        if (slider.style.webkitAppearance !== undefined) {
            slider.style.setProperty('--webkit-track-gradient', gradient);
        }
    }

    /**
     * Format money with Persian digits
     */
    function formatMoney(num) {
        if (isNaN(num) || num === null || num === undefined || num === '') return '۰';
        const numValue = typeof num === 'string' ? parseFloat(num) : num;
        if (isNaN(numValue)) return '۰';
        const absValue = Math.abs(numValue);
        return Math.ceil(absValue).toLocaleString('fa-IR', {
            useGrouping: true,
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).replace(/٬/g, ',');
    }

    /**
     * Parse money string to number
     */
    function parseMoney(str) {
        if (!str && str !== 0) return 0;
        let cleanStr = String(str).trim();
        if (/^\d+$/.test(cleanStr)) {
            return parseInt(cleanStr, 10) || 0;
        }
        cleanStr = cleanStr.replace(/[,،\s]/g, '');
        const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        const englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        for (let i = 0; i < persianDigits.length; i++) {
            cleanStr = cleanStr.replace(new RegExp(persianDigits[i], 'g'), englishDigits[i]);
        }
        cleanStr = cleanStr.replace(/[^\d]/g, '');
        const parsed = parseInt(cleanStr, 10);
        return isNaN(parsed) ? 0 : parsed;
    }

    /**
     * Clamp number between min and max
     */
    function clamp(num, min, max) {
        return Math.min(Math.max(num, min), max);
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initModalCalculator);
    } else {
        initModalCalculator();
    }

})();

