/**
 * Handles frontend logic for the Expert's New Inquiry Form.
 * This includes initializing the Select2 AJAX search for cars,
 * displaying the loan calculator, and managing form visibility.
 *
 * @version 1.0.2 (Uses localized strings from PHP)
 */
(function($) {
    'use strict';

    // Helper to get localized text, falling back to an empty string if missing (for robustness)
    const getText = (key, fallback = '') => (typeof maneli_expert_ajax !== 'undefined' && maneli_expert_ajax.text) 
                                             ? maneli_expert_ajax.text[key] || fallback 
                                             : fallback;

    $(document).ready(function() {
        const expertForm = $('#expert-inquiry-form');
        if (!expertForm.length) {
            // If the expert form is not on the page, do nothing.
            return;
        }

        // --- 1. DOM Element Cache ---
        const productSelect = $('#product_id_expert');
        const detailsWrapper = $('#expert-form-details');
        const calculatorWrapper = $('#loan-calculator-wrapper');
        const issuerRadios = expertForm.find('input[name="issuer_type"]');
        const issuerForm = $('#issuer-form-wrapper');

        // --- 2. Helper Functions ---
        const formatMoney = (num) => {
            if (isNaN(num) || num === null) return '۰';
            return Math.ceil(num).toLocaleString('fa-IR');
        };
        
        const parseMoney = (str) => {
            if (!str) return 0;
            // Converts a formatted string (with commas and Persian/English numerals) to a plain integer.
            // This robustly handles both Farsi (۰-۹) and English (0-9) digits.
            return parseInt(
                String(str)
                    .replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d))
                    .replace(/[^0-9]/g, ''), 
                10
            ) || 0;
        };

        // --- 3. Initialize Select2 for Car Search ---
        productSelect.select2({
            placeholder: getText('car_search_placeholder', 'نام خودرو را جستجو کنید...'),
            dir: "rtl",
            width: '100%',
            allowClear: true,
            ajax: {
                url: maneli_expert_ajax.ajax_url,
                dataType: 'json',
                delay: 250,
                method: 'POST',
                data: function(params) {
                    return {
                        action: 'maneli_search_cars',
                        nonce: maneli_expert_ajax.nonce,
                        search: params.term,
                    };
                },
                processResults: function(data) {
                    if (data.success) {
                        return { results: data.data.results };
                    } else {
                        console.error(getText('server_error', 'Server error:'), data.data ? data.data.message : getText('unknown_error', 'Unknown error'));
                        return { results: [] };
                    }
                },
                cache: true
            }
        });

        // --- 4. Event Handlers ---

        /**
         * Handles the selection of a car from the Select2 dropdown.
         * Renders the loan calculator and sets up its event listeners.
         */
        productSelect.on('select2:select', function(e) {
            const data = e.params.data;
            const price = parseInt(data.price, 10);
            const minDownPayment = parseInt(data.min_downpayment, 10);

            if (price > 0) {
                // Get localized text once to avoid repeated calls inside the template literal
                const tomanText = getText('toman');
                
                // Inject the calculator HTML into the wrapper using localized strings
                const calculatorHTML = `
                    <div class="form-grid" style="margin-top: 20px;">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="expert_down_payment">${getText('down_payment_label')}</label>
                                <input type="text" id="expert_down_payment" name="down_payment" class="regular-text" required>
                                <p class="description" style="margin-top: 5px;">${getText('min_down_payment_desc')}: ${formatMoney(minDownPayment)} ${tomanText}</p>
                            </div>
                            <div class="form-group">
                                <label for="expert_term_months">${getText('term_months_label')}</label>
                                <select name="term_months" id="expert_term_months" class="regular-text">
                                    <option value="12">${getText('term_12')}</option>
                                    <option value="18" selected>${getText('term_18')}</option>
                                    <option value="24">${getText('term_24')}</option>
                                    <option value="36">${getText('term_36')}</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row" style="border-top: 1px solid #eee; padding-top: 15px; margin-top: 15px;">
                           <div class="form-group">
                                <label>${getText('loan_amount_label')}</label>
                                <div class="result-display"><span id="expert-loan-amount">-</span> <span>${tomanText}</span></div>
                            </div>
                             <div class="form-group">
                                <label>${getText('total_repayment_label')}</label>
                                <div class="result-display"><span id="expert-total-repayment">-</span> <span>${tomanText}</span></div>
                            </div>
                             <div class="form-group">
                                <label>${getText('installment_amount_label')}</label>
                                <div class="result-display" style="color: #3989BE; font-size: 1.3em;"><span id="expert-installment-amount">-</span> <span>${tomanText}</span></div>
                            </div>
                        </div>
                    </div>`;

                calculatorWrapper.html(calculatorHTML);
                detailsWrapper.slideDown();

                // --- Calculator Live Update Logic ---
                const downPaymentInput = $('#expert_down_payment');
                const termSelect = $('#expert_term_months');
                
                const calculateInstallment = () => {
                    const dp = parseMoney(downPaymentInput.val());
                    const months = parseInt(termSelect.val(), 10) || 12;
                    const loanAmount = price - dp;

                    if (loanAmount <= 0) {
                        $('#expert-installment-amount').text('۰');
                        $('#expert-loan-amount').text('۰');
                        // Repayment equals down payment if loan is zero or negative
                        $('#expert-total-repayment').text(formatMoney(dp));
                        return;
                    }
                    
                    // Retrieve configurable interest rate from the localized object
                    const interestRate = (typeof maneli_expert_ajax !== 'undefined' && maneli_expert_ajax.interestRate) 
                                         ? parseFloat(maneli_expert_ajax.interestRate) 
                                         : 0.035; 

                    // Calculation logic (using configurable interest rate)
                    const monthlyInterestAmount = loanAmount * interestRate;
                    const totalInterest = monthlyInterestAmount * (months + 1);
                    const totalRepayment = loanAmount + totalInterest;
                    const installment = totalRepayment / months;

                    $('#expert-loan-amount').text(formatMoney(loanAmount));
                    $('#expert-total-repayment').text(formatMoney(totalRepayment));
                    $('#expert-installment-amount').text(formatMoney(installment));
                };
                
                downPaymentInput.on('keyup input', function() {
                    const value = $(this).val();
                    const parsedValue = parseMoney(value);
                    if (parsedValue > 0) {
                        // Reformat the input value with commas while typing
                        $(this).val(parsedValue.toLocaleString('en-US')); 
                    }
                    calculateInstallment();
                });

                termSelect.on('change', calculateInstallment);

                // Set initial value and trigger calculation
                downPaymentInput.val(minDownPayment.toLocaleString('en-US')).trigger('input');
            }
        });

        /**
         * Handles the clearing of a car selection.
         * Hides the calculator and details form.
         */
        productSelect.on('select2:unselect', function() {
            detailsWrapper.slideUp();
            calculatorWrapper.empty();
        });

        /**
         * Toggles the visibility of the cheque issuer's form fields.
         */
        const toggleIssuerForm = () => {
            const selectedValue = expertForm.find('input[name="issuer_type"]:checked').val();
            const isOther = selectedValue === 'other';
            
            // Use jQuery's slideToggle for smooth transition in the expert panel.
            issuerForm.slideToggle(isOther);
        };

        issuerRadios.on('change', toggleIssuerForm);

        // Initial check on page load
        toggleIssuerForm(); 
    });

})(jQuery);