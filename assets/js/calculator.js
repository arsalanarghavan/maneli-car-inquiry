/**
 * Handles frontend logic for the loan calculator widget on the single product page.
 * This includes tab switching, live calculation for installments, and AJAX form submission.
 *
 * @version 2.2.0 (Complete rewrite with comprehensive debugging)
 */

(function() {
    'use strict';

    let initialized = false;

    // Comprehensive initialization with retry mechanism
    function initializeCalculator() {
        // Prevent multiple initializations
        if (initialized) {
            console.log('AutoPuzzle Calculator: Already initialized, skipping...');
            return;
        }

        const calcContainer = document.querySelector(".autopuzzle-calculator-container");
        if (!calcContainer) {
            // Container not found - this is normal on pages without calculator
            // Don't log or retry - just return silently
            return false;
        }

        console.log('AutoPuzzle Calculator: Container found, initializing...');

        try {
            // Initialize tab switching
            initTabSwitching(calcContainer);

            // Initialize cash tab
            initCashTab();

            // Initialize installment calculator
            const success = initInstallmentCalculator(calcContainer);
            
            if (success) {
                initialized = true;
                console.log('AutoPuzzle Calculator: Successfully initialized!');
            }
            
            return success;
        } catch (error) {
            console.error('AutoPuzzle Calculator: Error during initialization', error);
            return false;
        }
    }

    // Try initialization with multiple strategies
    function tryInit() {
        // First check: if container doesn't exist, don't try to initialize
        // This prevents unnecessary retries and console logs on pages without calculator
        const calcContainer = document.querySelector(".autopuzzle-calculator-container");
        if (!calcContainer) {
            // Container doesn't exist on this page - this is normal, don't log or retry
            return;
        }

        if (initializeCalculator()) {
            return;
        }

        // Retry after DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                // Check again before retrying
                if (document.querySelector(".autopuzzle-calculator-container")) {
                    setTimeout(initializeCalculator, 100);
                }
            });
        } else {
            // Check again before retrying
            if (document.querySelector(".autopuzzle-calculator-container")) {
                setTimeout(initializeCalculator, 100);
            }
        }

        // Final retry after page load
        window.addEventListener('load', function() {
            setTimeout(function() {
                if (!initialized && document.querySelector(".autopuzzle-calculator-container")) {
                    console.warn('AutoPuzzle Calculator: Final retry after window load');
                    initializeCalculator();
                }
            }, 300);
        });
    }

    // Start initialization only if container exists
    tryInit();

    // === TAB SWITCHING ===
    function initTabSwitching(container) {
        const tabs = container.querySelectorAll('.calculator-tabs .tab-link');
        const contents = container.querySelectorAll('.tabs-content-wrapper .tab-content');

        if (tabs.length === 0 || contents.length === 0) {
            console.warn('AutoPuzzle Calculator: Tabs or contents not found');
            return;
        }

        tabs.forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Deactivate all
                tabs.forEach(t => t.classList.remove('active'));
                contents.forEach(c => c.classList.remove('active'));
                
                // Activate clicked tab
                this.classList.add('active');
                const targetContent = container.querySelector('#' + this.dataset.tab);
                if (targetContent) {
                    targetContent.classList.add('active');
                }
            });
        });

        console.log('AutoPuzzle Calculator: Tab switching initialized');
    }

    // === CASH TAB ===
    // Export to window for use in modals
    window.autopuzzleInitCashTab = function() {
        return initCashTabInternal();
    };
    
    function initCashTab() {
        return initCashTabInternal();
    }
    
    function initCashTabInternal() {
        const cashPriceEl = document.getElementById('cashPriceAmount');
        if (cashPriceEl && cashPriceEl.innerText) {
            const priceValue = parseMoney(cashPriceEl.innerText);
            if (priceValue > 0) {
                cashPriceEl.innerText = formatMoney(priceValue);
            }
        }
        
        // Handle cash form submission with AJAX for logged-in users
        const cashForm = document.querySelector('.cash-request-form');
        if (cashForm) {
            // Check if cash tab is unavailable (use form's own data attribute, not installment calculator's)
            const cashTab = document.getElementById('cash-tab');
            const isUnavailable = cashForm.getAttribute('data-is-unavailable') === 'true';
            
            if (isUnavailable) {
                console.log('AutoPuzzle Cash Form: Product unavailable - form disabled');
                
                // Add unavailable-form class if not already added
                if (!cashForm.classList.contains('unavailable-form')) {
                    cashForm.classList.add('unavailable-form');
                    console.log('AutoPuzzle Cash Form: Added unavailable-form class to form');
                }
                
                // Ensure overlay message is visible (should be in tab-content, not form)
                let overlayMessage = cashTab.querySelector('.unavailable-overlay-message');
                if (!overlayMessage) {
                    // Create overlay if it doesn't exist (fallback) - add to tab, not form
                    overlayMessage = document.createElement('div');
                    overlayMessage.className = 'unavailable-overlay-message';
                    overlayMessage.innerHTML = `
                        <div class="unavailable-message-content">
                            <i class="la la-exclamation-circle"></i>
                            <p><?php echo esc_html($unavailable_message); ?></p>
                        </div>
                    `;
                    cashTab.style.position = 'relative';
                    cashTab.insertBefore(overlayMessage, cashForm);
                    console.log('AutoPuzzle Cash Form: Created unavailable overlay message');
                }
                
                // Add unavailable-tab class to tab-content
                if (!cashTab.classList.contains('unavailable-tab')) {
                    cashTab.classList.add('unavailable-tab');
                }
                
                // Disable all form inputs
                cashForm.querySelectorAll('input, select, button').forEach(element => {
                    element.disabled = true;
                    element.style.pointerEvents = 'none';
                });
                
                // Disable submit button specifically
                const submitBtn = cashForm.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.style.pointerEvents = 'none';
                }
            }
            
            const submitBtn = cashForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                cashForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Check if user is logged in
                    const isLoggedIn = typeof autopuzzle_ajax_object !== 'undefined' && 
                                      autopuzzle_ajax_object.ajax_url && 
                                      autopuzzle_ajax_object.ajax_url !== '' &&
                                      autopuzzle_ajax_object.nonce && 
                                      autopuzzle_ajax_object.nonce !== '';
                    
                    // Check if cash tab is unavailable (prevent submission)
                    const isUnavailable = cashForm.getAttribute('data-is-unavailable') === 'true';
                    if (isUnavailable) {
                        console.log('AutoPuzzle Cash Form: Cannot submit - cash tab unavailable');
                        alert(autopuzzle_ajax_object.text.unavailable_cash_message || 'این محصول در حال حاضر برای خرید نقدی در دسترس نیست.');
                        return;
                    }
                    
                    if (!isLoggedIn) {
                        // Save form data to localStorage and redirect to login
                        const formData = {
                            product_id: cashForm.querySelector('input[name="product_id"]').value,
                            first_name: cashForm.querySelector('#cash_first_name')?.value || '',
                            last_name: cashForm.querySelector('#cash_last_name')?.value || '',
                            mobile_number: cashForm.querySelector('#cash_mobile_number')?.value || '',
                            car_color: cashForm.querySelector('#cash_car_color')?.value || '',
                            timestamp: Date.now()
                        };
                        
                        localStorage.setItem('autopuzzle_pending_cash_inquiry', JSON.stringify(formData));
                        console.log('AutoPuzzle Cash Form: Saved form data to localStorage');
                        
                        // Redirect to login with redirect_to parameter pointing to current product page
                        const currentUrl = window.location.href;
                        const loginUrl = '/dashboard/login?redirect_to=' + encodeURIComponent(currentUrl);
                        window.location.href = loginUrl;
                        return;
                    }
                    
                    // User is logged in, submit via AJAX
                    const originalText = submitBtn.textContent;
                    submitBtn.disabled = true;
                    submitBtn.textContent = (autopuzzle_ajax_object.text && autopuzzle_ajax_object.text.sending) 
                                         ? autopuzzle_ajax_object.text.sending 
                                         : "Submitting...";
                    
                    const formDataObj = new FormData();
                    formDataObj.append('action', 'autopuzzle_create_customer_cash_inquiry');
                    formDataObj.append('nonce', autopuzzle_ajax_object.cash_inquiry_nonce || autopuzzle_ajax_object.nonce);
                    formDataObj.append('product_id', cashForm.querySelector('input[name="product_id"]').value);
                    formDataObj.append('first_name', cashForm.querySelector('#cash_first_name')?.value || '');
                    formDataObj.append('last_name', cashForm.querySelector('#cash_last_name')?.value || '');
                    formDataObj.append('mobile_number', cashForm.querySelector('#cash_mobile_number')?.value || '');
                    formDataObj.append('car_color', cashForm.querySelector('#cash_car_color')?.value || '');
                    
                    fetch(autopuzzle_ajax_object.ajax_url, {
                        method: 'POST',
                        body: formDataObj
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Redirect to inquiry page
                            if (data.data && data.data.redirect_url) {
                                window.location.href = data.data.redirect_url;
                            } else if (data.data && data.data.inquiry_id) {
                                window.location.href = '/dashboard/inquiries/cash?cash_inquiry_id=' + data.data.inquiry_id;
                            } else {
                                window.location.href = '/dashboard/inquiries/cash';
                            }
                        } else {
                            const errorMsg = (autopuzzle_ajax_object.text && autopuzzle_ajax_object.text.error_sending) 
                                           ? autopuzzle_ajax_object.text.error_sending 
                                           : "Error: ";
                            alert(errorMsg + (data.data && data.data.message ? data.data.message : "Unknown error."));
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalText;
                        }
                    })
                    .catch(error => {
                        console.error('AutoPuzzle Cash Form: AJAX Error:', error);
                        const errorMsg = (autopuzzle_ajax_object.text && autopuzzle_ajax_object.text.server_error_connection) 
                                       ? autopuzzle_ajax_object.text.server_error_connection 
                                       : "An unknown error occurred while communicating with the server.";
                        alert(errorMsg);
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    });
                });
            }
        }
    }

    // === INSTALLMENT CALCULATOR ===
    // Export to window for use in modals
    window.autopuzzleInitInstallmentCalculator = function(container) {
        return initInstallmentCalculatorInternal(container);
    };
    
    function initInstallmentCalculator(container) {
        return initInstallmentCalculatorInternal(container);
    }
    
    function initInstallmentCalculatorInternal(container) {
        const installmentTab = document.getElementById("installment-tab");
        if (!installmentTab) {
            console.error('AutoPuzzle Calculator: installment-tab not found');
            return false;
        }

        const calc = document.getElementById("loan-calculator");
        if (!calc) {
            console.error('AutoPuzzle Calculator: loan-calculator element not found');
            return false;
        }

        // Parse initial values from data attributes
        const priceAttr = calc.getAttribute('data-price');
        const minDownAttr = calc.getAttribute('data-min-down');
        const maxDownAttr = calc.getAttribute('data-max-down');
        
        console.log('AutoPuzzle Calculator: Data attributes:', {
            price: priceAttr,
            minDown: minDownAttr,
            maxDown: maxDownAttr
        });

        let productPrice = 0;
        if (priceAttr) {
            productPrice = parseMoney(priceAttr);
            if (productPrice <= 0 && priceAttr) {
                // Try direct parsing if parseMoney fails
                const cleanPrice = String(priceAttr).replace(/[^\d]/g, '');
                productPrice = parseInt(cleanPrice, 10) || 0;
            }
        }
        
        let minDown = 0;
        if (minDownAttr) {
            minDown = parseMoney(minDownAttr);
            if (minDown <= 0 && minDownAttr) {
                const cleanMin = String(minDownAttr).replace(/[^\d]/g, '');
                minDown = parseInt(cleanMin, 10) || 0;
            }
        }
        
        let maxDown = productPrice * 0.8;
        if (maxDownAttr) {
            const parsedMax = parseMoney(maxDownAttr);
            if (parsedMax > 0) {
                maxDown = parsedMax;
            }
        }

        console.log('AutoPuzzle Calculator: Parsed values:', {
            productPrice,
            minDown,
            maxDown
        });

        // Get DOM elements - use querySelector to find hidden elements too
        const input = document.getElementById("downPaymentInput");
        const slider = document.getElementById("downPaymentSlider");
        const minDisplay = installmentTab.querySelector("#minDownDisplay"); // Find even if hidden
        let installmentEl = installmentTab.querySelector("#installmentAmount"); // Find even if hidden
        const actionBtn = installmentTab.querySelector(".loan-action-btn");

        if (!input) {
            console.error('AutoPuzzle Calculator: downPaymentInput not found');
            return false;
        }
        if (!slider) {
            console.error('AutoPuzzle Calculator: downPaymentSlider not found');
            return false;
        }
        if (!installmentEl) {
            // Try to find hidden element by searching all elements
            installmentEl = installmentTab.querySelector('span#installmentAmount[style*="display:none"]') || 
                            installmentTab.querySelector('span#installmentAmount');
            if (!installmentEl) {
                console.error('AutoPuzzle Calculator: installmentAmount not found anywhere');
                return false;
            }
            console.warn('AutoPuzzle Calculator: Found hidden installmentAmount element');
        }
        
        // If installmentEl is hidden (via style), make it visible so calculations show
        if (installmentEl && installmentEl.style.display === 'none') {
            installmentEl.style.display = '';
            console.log('AutoPuzzle Calculator: Made installmentAmount visible (was hidden)');
        }
        
        // Hide price-hidden span if installmentAmount is being shown (to remove the "-" sign)
        const priceHiddenSpan = installmentTab.querySelector('.result-section .price-hidden');
        if (priceHiddenSpan && installmentEl && installmentEl.style.display !== 'none') {
            priceHiddenSpan.style.display = 'none';
        }
        
        // Also check and show minDisplay if it was hidden
        if (minDisplay && minDisplay.style.display === 'none' && minDown > 0) {
            minDisplay.style.display = '';
            console.log('AutoPuzzle Calculator: Made minDownDisplay visible (was hidden)');
        }
        
        // Hide price-hidden span near minDisplay (to remove the "-" sign)
        const minPriceHidden = installmentTab.querySelector('.loan-note .price-hidden');
        if (minPriceHidden && minDisplay && minDisplay.style.display !== 'none') {
            minPriceHidden.style.display = 'none';
        }

        // Check if calculator should be disabled
        // Only disable if product is UNAVAILABLE - don't disable just because prices are hidden
        const isUnavailable = calc.getAttribute('data-is-unavailable') === 'true';
        const canSeePricesAttr = calc.getAttribute('data-can-see-prices');

        console.log('AutoPuzzle Calculator: Availability check:', {
            isUnavailable: isUnavailable,
            canSeePricesAttr: canSeePricesAttr,
            productPrice: productPrice
        });

        // ONLY disable if product is unavailable
        // If prices are hidden, calculator should still work, just hide the display
        if (isUnavailable) {
            console.log('AutoPuzzle Calculator: Product unavailable - calculator disabled');
            
            // Add unavailable-form class to the form if not already added
            const form = installmentTab.querySelector('.loan-calculator-form');
            if (form && !form.classList.contains('unavailable-form')) {
                form.classList.add('unavailable-form');
                console.log('AutoPuzzle Calculator: Added unavailable-form class to form');
            }
            
            // Ensure overlay message is visible (should be in tab-content, not form)
            let overlayMessage = installmentTab.querySelector('.unavailable-overlay-message');
            if (!overlayMessage) {
                // Create overlay if it doesn't exist (fallback) - add to tab, not form
                overlayMessage = document.createElement('div');
                overlayMessage.className = 'unavailable-overlay-message';
                overlayMessage.innerHTML = `
                    <div class="unavailable-message-content">
                        <i class="la la-exclamation-circle"></i>
                        <p>این محصول در حال حاضر برای خرید اقساطی در دسترس نیست.</p>
                    </div>
                `;
                installmentTab.style.position = 'relative';
                if (form) {
                    installmentTab.insertBefore(overlayMessage, form);
                } else {
                    installmentTab.appendChild(overlayMessage);
                }
                console.log('AutoPuzzle Calculator: Created unavailable overlay message');
            }
            
            // Add unavailable-tab class to tab-content
            if (!installmentTab.classList.contains('unavailable-tab')) {
                installmentTab.classList.add('unavailable-tab');
            }
            
            if (input) input.disabled = true;
            if (slider) slider.disabled = true;
            installmentTab.querySelectorAll(".term-btn").forEach(btn => btn.disabled = true);
            
            // Disable action button
            if (actionBtn) {
                actionBtn.disabled = true;
                actionBtn.style.pointerEvents = 'none';
            }
            
            return true; // Initialized but disabled
        }

        console.log('AutoPuzzle Calculator: Calculator enabled and ready to use');

        // Get interest rate
        const interestRate = (typeof autopuzzle_ajax_object !== 'undefined' && autopuzzle_ajax_object.interestRate)
                           ? parseFloat(autopuzzle_ajax_object.interestRate) 
                           : 0.035;

        console.log('AutoPuzzle Calculator: Interest rate:', interestRate);

        // Helper: Update slider visual fill
        function updateSliderVisual() {
            if (!slider || !slider.max || slider.max === slider.min) return;
            // Calculate percentage based on full range (0 to maxDown)
            const percentage = ((slider.value - slider.min) / (slider.max - slider.min)) * 100;
            slider.style.setProperty('--value-percent', percentage + '%');
        }

        // Helper: Calculate and display installment
        function calculateInstallment() {
            if (!input || !installmentEl) return;

            const downPayment = parseMoney(input.value) || 0;
            const activeBtn = installmentTab.querySelector(".term-btn.active");
            
            if (!activeBtn) {
                // Activate first button if none is active
                const firstBtn = installmentTab.querySelector(".term-btn");
                if (firstBtn) {
                    firstBtn.classList.add("active");
                    calculateWithMonth(parseInt(firstBtn.dataset.months) || 12, downPayment);
                } else {
                    // No buttons found, use default 12 months
                    calculateWithMonth(12, downPayment);
                }
                return;
            }

            const months = parseInt(activeBtn.dataset.months) || 12;
            calculateWithMonth(months, downPayment);
        }

        // Helper: Calculate with specific month count
        function calculateWithMonth(months, downPayment) {
            const loanAmount = productPrice - downPayment;

            if (loanAmount <= 0 || productPrice <= 0 || months <= 0) {
                if (installmentEl) {
                    installmentEl.innerText = formatMoney(0);
                }
                return;
            }

            // Calculation: monthlyInterestAmount * (months + 1) = total interest
            const monthlyInterestAmount = loanAmount * interestRate;
            const totalInterest = monthlyInterestAmount * (months + 1);
            const totalRepayment = loanAmount + totalInterest;
            const installment = totalRepayment / months;

            if (installmentEl) {
                const formatted = formatMoney(Math.ceil(installment));
                installmentEl.innerText = formatted;
                // Ensure the element is visible
                if (installmentEl.style.display === 'none') {
                    installmentEl.style.display = '';
                }
                // Also check parent if it was hidden
                const parent = installmentEl.parentElement;
                if (parent && parent.style.display === 'none') {
                    parent.style.display = '';
                }
                console.log('AutoPuzzle Calculator: Calculated installment:', formatted, 'for', months, 'months');
            }
        }

        // Initialize calculator state
        function initializeCalculatorState() {
            if (!slider || !input) {
                console.warn('AutoPuzzle Calculator: Cannot initialize state - slider or input missing');
                return;
            }

            try {
                // Set slider min to 0 and max to the full price range for proper visual display
                slider.min = 0;
                slider.max = maxDown || productPrice || 1000000;
                slider.value = minDown || 0;

                if (input) {
                    input.value = formatMoney(minDown || 0);
                }
                if (minDisplay) {
                    // Always update minDisplay value, even if hidden - we'll show it if needed
                    const currentDisplay = window.getComputedStyle(minDisplay).display;
                    minDisplay.innerText = formatMoney(minDown || 0);
                    // If it was hidden but we have a value, show it
                    if (currentDisplay === 'none' && minDown > 0) {
                        minDisplay.style.display = '';
                    }
                }

                updateSliderVisual();
                calculateInstallment();

                console.log('AutoPuzzle Calculator: State initialized successfully');
            } catch (error) {
                console.error('AutoPuzzle Calculator: Error initializing state', error);
            }
        }

        // === EVENT LISTENERS ===

        // Slider events
        slider.addEventListener("input", function() {
            // Keep min at 0 for proper visual display
            if (this.min != 0) this.min = 0;
            if (this.max != maxDown) this.max = maxDown || productPrice || 1000000;
            
            if (input) {
                input.value = formatMoney(parseInt(this.value) || 0);
            }
            updateSliderVisual();
            calculateInstallment();
        });

        slider.addEventListener("change", function() {
            // Keep min at 0 for proper visual display
            if (this.min != 0) this.min = 0;
            if (this.max != maxDown) this.max = maxDown || productPrice || 1000000;
            
            let value = parseInt(this.value) || 0;
            let clamped = clamp(value, minDown, maxDown);
            if (value !== clamped) {
                this.value = clamped;
                if (input) input.value = formatMoney(clamped);
            }
            updateSliderVisual();
            calculateInstallment();
        });

        // Input field events
        input.addEventListener("input", function() {
            let value = parseMoney(this.value) || 0;
            if (slider) {
                slider.value = clamp(value, minDown, maxDown);
            }
            updateSliderVisual();
            calculateInstallment();
        });

        input.addEventListener("blur", function() {
            let value = parseMoney(this.value) || 0;
            let clamped = clamp(value, minDown, maxDown);
            if (slider) {
                slider.min = 0;
                slider.max = maxDown;
                slider.value = clamped;
            }
            this.value = formatMoney(clamped);
            updateSliderVisual();
            calculateInstallment();
        });

        // Term button events
        const termButtons = installmentTab.querySelectorAll(".term-btn");
        termButtons.forEach(btn => {
            btn.addEventListener("click", function(e) {
                e.preventDefault();
                
                // Remove active from all buttons
                termButtons.forEach(b => b.classList.remove("active"));
                
                // Add active to clicked button
                this.classList.add("active");
                
                // Recalculate
                calculateInstallment();
            });
        });

        // AJAX submission button - always attach listener, check login state inside
        if (actionBtn) {
            actionBtn.addEventListener("click", function(e) {
                e.preventDefault();

                // Check if user is logged in - check both ajax_url and nonce
                const isLoggedIn = typeof autopuzzle_ajax_object !== 'undefined' && 
                                  autopuzzle_ajax_object.ajax_url && 
                                  autopuzzle_ajax_object.ajax_url !== '' &&
                                  autopuzzle_ajax_object.nonce && 
                                  autopuzzle_ajax_object.nonce !== '';
                
                console.log('AutoPuzzle Calculator: Login check:', {
                    ajax_url: typeof autopuzzle_ajax_object !== 'undefined' ? autopuzzle_ajax_object.ajax_url : 'undefined',
                    nonce: typeof autopuzzle_ajax_object !== 'undefined' ? autopuzzle_ajax_object.nonce : 'undefined',
                    isLoggedIn: isLoggedIn
                });
                
                if (!isLoggedIn) {
                    // User is not logged in, save calculator data to localStorage and redirect to login
                    const downPaymentInput = document.getElementById('downPaymentInput');
                    const installmentAmountEl = document.getElementById('installmentAmount');
                    const activeTermBtn = installmentTab.querySelector('.term-btn.active');
                    const termMonths = activeTermBtn ? (activeTermBtn.dataset.months || '12') : '12';
                    const productIdInput = installmentTab.querySelector('input[name="product_id"]');
                    
                    if (downPaymentInput && installmentAmountEl && productIdInput) {
                        const calculatorData = {
                            product_id: productIdInput.value,
                            down_payment: parseMoney(downPaymentInput.value) || 0,
                            term_months: termMonths,
                            installment_amount: parseMoney(installmentAmountEl.innerText) || 0,
                            total_price: productPrice,
                            timestamp: Date.now()
                        };
                        
                        // Save to localStorage
                        localStorage.setItem('autopuzzle_pending_calculator_data', JSON.stringify(calculatorData));
                        console.log('AutoPuzzle Calculator: Saved calculator data to localStorage:', calculatorData);
                    }
                    
                    // Redirect to login page
                    const loginWrapper = actionBtn.closest('.loan-action-wrapper');
                    if (loginWrapper) {
                        const loginLink = loginWrapper.querySelector('a.loan-action-btn');
                        if (loginLink && loginLink.href) {
                            console.log('AutoPuzzle Calculator: Redirecting to login:', loginLink.href);
                            window.location.href = loginLink.href;
                            return;
                        }
                    }
                    // Fallback to dashboard login
                    console.log('AutoPuzzle Calculator: Redirecting to dashboard login');
                    window.location.href = '/dashboard/';
                    return;
                }

                // Disable button and show loading
                const originalText = this.textContent;
                this.disabled = true;
                this.textContent = (autopuzzle_ajax_object.text && autopuzzle_ajax_object.text.sending) 
                                 ? autopuzzle_ajax_object.text.sending 
                                 : "Sending information...";

                // Get values
                const downPaymentInput = document.getElementById('downPaymentInput');
                const installmentAmountEl = document.getElementById('installmentAmount');
                const productIdInput = installmentTab.querySelector('input[name="product_id"]');
                
                if (!downPaymentInput || !installmentAmountEl || !productIdInput) {
                    console.error('AutoPuzzle Calculator: Required elements not found for submission');
                    this.disabled = false;
                    this.textContent = originalText;
                    return;
                }

                const downPayment = parseMoney(downPaymentInput.value) || 0;
                const activeTermBtn = installmentTab.querySelector('.term-btn.active');
                const termMonths = activeTermBtn ? (activeTermBtn.dataset.months || '12') : '12';
                const installmentAmount = parseMoney(installmentAmountEl.innerText) || 0;
                const productId = productIdInput.value;

                if (!productId) {
                    console.error('AutoPuzzle Calculator: Product ID not found');
                    this.disabled = false;
                    this.textContent = originalText;
                    return;
                }

                console.log('AutoPuzzle Calculator: Submitting inquiry:', {
                    productId,
                    downPayment,
                    termMonths,
                    installmentAmount,
                    productPrice
                });

                // Prepare form data
                const formData = new FormData();
                formData.append('action', 'autopuzzle_select_car_ajax');
                formData.append('product_id', productId);
                formData.append('nonce', autopuzzle_ajax_object.nonce);
                formData.append('down_payment', downPayment);
                formData.append('term_months', termMonths);
                formData.append('installment_amount', installmentAmount);
                formData.append('total_price', productPrice);

                // Get CAPTCHA token if enabled
                if (typeof maneliCaptcha !== 'undefined' && maneliCaptchaConfig && maneliCaptchaConfig.enabled) {
                    maneliCaptcha.getToken().then(function(token) {
                        if (token) {
                            if (maneliCaptchaConfig.type === 'hcaptcha') {
                                formData.append('h-captcha-response', token);
                            } else if (maneliCaptchaConfig.type === 'recaptcha_v2') {
                                formData.append('g-recaptcha-response', token);
                            } else if (maneliCaptchaConfig.type === 'recaptcha_v3') {
                                formData.append('captcha_token', token);
                            }
                        }
                        sendAjaxRequest();
                    }).catch(function() {
                        sendAjaxRequest();
                    });
                } else {
                    sendAjaxRequest();
                }
                
                function sendAjaxRequest() {
                    fetch(autopuzzle_ajax_object.ajax_url, {
                        method: 'POST',
                        body: formData
                    })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('AutoPuzzle Calculator: AJAX response:', data);
                    if (data.success) {
                        // Redirect to wizard form step 2 (identity form)
                        const redirectUrl = '/dashboard/new-inquiry?step=2';
                        console.log('AutoPuzzle Calculator: Redirecting to wizard:', redirectUrl);
                        window.location.href = redirectUrl;
                    } else {
                        const errorMsg = (autopuzzle_ajax_object.text && autopuzzle_ajax_object.text.error_sending) 
                                       ? autopuzzle_ajax_object.text.error_sending 
                                       : "Error sending information: ";
                        const unknownError = (autopuzzle_ajax_object.text && autopuzzle_ajax_object.text.unknown_error) 
                                           ? autopuzzle_ajax_object.text.unknown_error 
                                           : "Unknown error.";
                        alert(errorMsg + (data.data && data.data.message ? data.data.message : unknownError));
                        this.disabled = false;
                        this.textContent = originalText;
                    }
                })
                .catch(error => {
                    console.error('AutoPuzzle Calculator: AJAX Error:', error);
                    const errorMsg = (autopuzzle_ajax_object.text && autopuzzle_ajax_object.text.server_error_connection) 
                                   ? autopuzzle_ajax_object.text.server_error_connection 
                                   : autopuzzle_ajax_object.text.unknown_server_error || "An unknown error occurred while communicating with the server.";
                    alert(errorMsg);
                    this.disabled = false;
                    this.textContent = originalText;
                });
                }
            });
        } else {
            console.warn('AutoPuzzle Calculator: Action button not found');
        }

        // Initialize calculator state after a short delay to ensure DOM is ready
        setTimeout(function() {
            initializeCalculatorState();
        }, 50);

        return true;
    }

    // === UTILITY FUNCTIONS ===

    /**
     * Formats a number as Persian numerals with thousand separators
     */
    function formatMoney(num) {
        if (isNaN(num) || num === null || num === undefined || num === '') return '۰';
        const numValue = typeof num === 'string' ? parseFloat(num) : num;
        if (isNaN(numValue)) return '۰';
        // Ensure non-negative numbers
        const absValue = Math.abs(numValue);
        // Use toLocaleString with options to get Persian digits but English comma
        return Math.ceil(absValue).toLocaleString('fa-IR', {
            useGrouping: true,
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).replace(/٬/g, ',');
    }

    /**
     * Parses a formatted money string (with Persian digits and separators) to integer
     */
    function parseMoney(str) {
        if (!str && str !== 0) return 0;
        
        // Convert to string and trim
        let cleanStr = String(str).trim();
        
        // If already a clean number string, parse directly
        if (/^\d+$/.test(cleanStr)) {
            return parseInt(cleanStr, 10) || 0;
        }
        
        // Remove thousand separators (commas, spaces, Persian comma)
        cleanStr = cleanStr.replace(/[,،\s]/g, '');
        
        // Convert Persian digits to English
        const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        const englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        for (let i = 0; i < persianDigits.length; i++) {
            cleanStr = cleanStr.replace(new RegExp(persianDigits[i], 'g'), englishDigits[i]);
        }
        
        // Remove all non-numeric characters
        cleanStr = cleanStr.replace(/[^\d]/g, '');
        
        const parsed = parseInt(cleanStr, 10);
        return isNaN(parsed) ? 0 : parsed;
    }

    /**
     * Clamps a number between min and max
     */
    function clamp(num, min, max) {
        return Math.min(Math.max(num, min), max);
    }

})();
