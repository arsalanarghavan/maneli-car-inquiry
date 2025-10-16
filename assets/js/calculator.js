/**
 * Handles frontend logic for the loan calculator widget on the single product page.
 * This includes tab switching, live calculation for installments, and AJAX form submission.
 *
 * @version 1.0.1 (Configurable loan interest rate)
 */

document.addEventListener("DOMContentLoaded", function () {
    const calcContainer = document.querySelector(".maneli-calculator-container");
    if (!calcContainer) {
        // If the calculator container is not on the page, do nothing.
        return;
    }

    // --- 1. TAB SWITCHING LOGIC ---
    const tabs = calcContainer.querySelectorAll('.calculator-tabs .tab-link');
    const contents = calcContainer.querySelectorAll('.tabs-content-wrapper .tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', function (e) {
            e.preventDefault();

            // Deactivate all tabs and content panels
            tabs.forEach(item => item.classList.remove('active'));
            contents.forEach(content => content.classList.remove('active'));

            // Activate the clicked tab and its corresponding content panel
            this.classList.add('active');
            const activeContent = calcContainer.querySelector('#' + this.dataset.tab);
            if (activeContent) {
                activeContent.classList.add('active');
            }
        });
    });

    // --- 2. HELPER FUNCTIONS ---
    const formatMoney = (num) => {
        if (isNaN(num) || num === null) return '۰';
        // Converts number to Persian/Farsi numerals for display
        return Math.ceil(num).toLocaleString('fa-IR');
    };
    const parseMoney = (str) => {
        if (!str) return 0;
        // Converts a formatted string (with commas and Persian numerals) to a plain integer
        const persianDigits = '۰۱۲۳۴۵۶۷۸۹';
        const englishDigits = '0123456789';
        let englishStr = String(str).replace(/,/g, '');
        for (let i = 0; i < persianDigits.length; i++) {
            englishStr = englishStr.replace(new RegExp(persianDigits[i], 'g'), englishDigits[i]);
        }
        return parseInt(englishStr, 10) || 0;
    };
    const clamp = (num, min, max) => Math.min(Math.max(num, min), max);

    // --- 3. CASH TAB LOGIC ---
    const cashTab = document.getElementById("cash-tab");
    if (cashTab) {
        const cashPriceEl = document.getElementById('cashPriceAmount');
        if (cashPriceEl) {
            const priceValue = parseMoney(cashPriceEl.innerText);
            cashPriceEl.innerText = formatMoney(priceValue);
        }
        // Note: The form submission is a standard, non-AJAX POST request.
        // The backend handles logic for guests vs. logged-in users.
    }

    // --- 4. INSTALLMENT CALCULATOR LOGIC ---
    const installmentTab = document.getElementById("installment-tab");
    if (installmentTab) {
        const calc = document.getElementById("loan-calculator");
        if (!calc) return;

        // Get initial values from data attributes
        const productPrice = parseInt(calc.dataset.price) || 0;
        const minDown = parseInt(calc.dataset.minDown) || 0;
        const maxDown = parseInt(calc.dataset.maxDown) || (productPrice * 0.8);

        // Get DOM elements
        const input = document.getElementById("downPaymentInput");
        const slider = document.getElementById("downPaymentSlider");
        const minDisplay = document.getElementById("minDownDisplay");
        const installmentEl = document.getElementById("installmentAmount");
        const actionBtn = installmentTab.querySelector(".loan-action-btn");
        
        // NEW: Get the configurable interest rate from the localized object
        const interestRate = (typeof maneli_ajax_object !== 'undefined' && maneli_ajax_object.interestRate) 
                             ? parseFloat(maneli_ajax_object.interestRate) 
                             : 0.035; 
        
        /**
         * Updates the visual fill of the slider based on its current value.
         */
        function updateSliderLook() {
            if (!slider) return;
            const percentage = ((slider.value - slider.min) / (slider.max - slider.min)) * 100;
            slider.style.setProperty('--value-percent', percentage + '%');
        }

        /**
         * Calculates and displays the monthly installment amount.
         */
        function calculateInstallment() {
            if (!input || !installmentEl) return;
            const dp = parseMoney(input.value);
            const activeBtn = installmentTab.querySelector(".term-btn.active");
            if (!activeBtn) return;

            const selectedMonths = parseInt(activeBtn.dataset.months);
            const loanAmount = productPrice - dp;

            if (loanAmount <= 0) {
                installmentEl.innerText = formatMoney(0);
                return;
            }

            // Calculation logic (using configurable interest rate)
            const monthlyInterestAmount = loanAmount * interestRate; // FIXED: Used configurable rate
            const totalInterest = monthlyInterestAmount * (selectedMonths + 1);
            const totalRepayment = loanAmount + totalInterest;
            const installment = totalRepayment / selectedMonths;

            installmentEl.innerText = formatMoney(installment);
        }

        /**
         * Sets the initial state of the calculator.
         */
        function initializeCalculator() {
            if (!slider || !input || !minDisplay) return;

            slider.min = 0;
            slider.max = productPrice;
            slider.value = minDown;
            input.value = formatMoney(minDown);
            minDisplay.innerText = formatMoney(minDown);

            updateSliderLook();
            calculateInstallment();
        }

        // --- Event Listeners for Installment Calculator ---

        if (slider) {
            slider.addEventListener("input", () => {
                if (input) input.value = formatMoney(slider.value);
                updateSliderLook();
                calculateInstallment();
            });
            slider.addEventListener("change", () => { // On release
                let value = parseInt(slider.value);
                let clampedValue = clamp(value, minDown, maxDown);
                if (value !== clampedValue) {
                    slider.value = clampedValue;
                    if (input) input.value = formatMoney(clampedValue);
                    updateSliderLook();
                }
                calculateInstallment();
            });
        }

        if (input) {
            input.addEventListener('input', () => {
                let value = parseMoney(input.value);
                if (slider) slider.value = clamp(value, 0, productPrice);
                updateSliderLook();
                calculateInstallment();
            });
            input.addEventListener('blur', () => { // On focus out
                let value = parseMoney(input.value);
                let clampedValue = clamp(value, minDown, maxDown);
                if (slider) slider.value = clampedValue;
                input.value = formatMoney(clampedValue);
                updateSliderLook();
                calculateInstallment();
            });
        }

        installmentTab.querySelectorAll(".term-btn").forEach(btn => {
            btn.addEventListener("click", () => {
                const currentActive = installmentTab.querySelector(".term-btn.active");
                if (currentActive) {
                    currentActive.classList.remove("active");
                }
                btn.classList.add("active");
                calculateInstallment();
            });
        });

        // AJAX submission for logged-in users
        if (actionBtn && typeof maneli_ajax_object !== 'undefined') {
            actionBtn.addEventListener("click", function (e) {
                e.preventDefault();

                actionBtn.disabled = true;
                const sendingText = (typeof maneli_ajax_object !== 'undefined' && maneli_ajax_object.text && maneli_ajax_object.text.sending) 
                                    ? maneli_ajax_object.text.sending 
                                    : "در حال ارسال اطلاعات...";
                actionBtn.textContent = sendingText;

                const downPayment = parseMoney(document.getElementById('downPaymentInput').value);
                const termMonths = installmentTab.querySelector('.term-btn.active').dataset.months;
                const installmentAmount = parseMoney(document.getElementById('installmentAmount').innerText);
                const totalPrice = calc.dataset.price;
                const productId = installmentTab.querySelector('input[name="product_id"]').value;

                const formData = new FormData();
                formData.append('action', 'maneli_select_car_ajax');
                formData.append('product_id', productId);
                formData.append('nonce', maneli_ajax_object.nonce);
                formData.append('down_payment', downPayment);
                formData.append('term_months', termMonths);
                formData.append('installment_amount', installmentAmount);
                formData.append('total_price', totalPrice);

                fetch(maneli_ajax_object.ajax_url, { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.href = maneli_ajax_object.inquiry_page_url;
                        } else {
                            const errorText = (typeof maneli_ajax_object !== 'undefined' && maneli_ajax_object.text && maneli_ajax_object.text.error_sending) 
                                              ? maneli_ajax_object.text.error_sending 
                                              : "خطا در ارسال اطلاعات: ";
                            const unknownError = (typeof maneli_ajax_object !== 'undefined' && maneli_ajax_object.text && maneli_ajax_object.text.unknown_error) 
                                                 ? maneli_ajax_object.text.unknown_error 
                                                 : "خطای ناشناخته.";
                            const buttonText = (typeof maneli_ajax_object !== 'undefined' && maneli_ajax_object.text && maneli_ajax_object.text.credit_check) 
                                               ? maneli_ajax_object.text.credit_check 
                                               : "استعلام سنجی بانکی جهت خرید خودرو";
                            alert(errorText + (data.data.message || unknownError));
                            actionBtn.disabled = false;
                            actionBtn.textContent = buttonText;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        const serverError = (typeof maneli_ajax_object !== 'undefined' && maneli_ajax_object.text && maneli_ajax_object.text.server_error_connection) 
                                            ? maneli_ajax_object.text.server_error_connection 
                                            : "یک خطای ناشناخته در ارتباط با سرور رخ داد.";
                        const buttonText = (typeof maneli_ajax_object !== 'undefined' && maneli_ajax_object.text && maneli_ajax_object.text.credit_check) 
                                           ? maneli_ajax_object.text.credit_check 
                                           : "استعلام سنجی بانکی جهت خرید خودرو";
                        alert(serverError);
                        actionBtn.disabled = false;
                        actionBtn.textContent = buttonText;
                    });
            });
        }

        // Initialize the calculator if the product has a price.
        if (productPrice > 0) {
            initializeCalculator();
        }
    }
});