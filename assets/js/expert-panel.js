/**
 * Handles frontend logic for the Expert's New Inquiry Form.
 * This includes initializing the Select2 AJAX search for cars,
 * displaying the loan calculator, and managing form visibility.
 *
 * @version 1.0.0
 */
(function($) {
    'use strict';

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
            return parseInt(String(str).replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/[^0-9]/g, ''), 10) || 0;
        };

        // --- 3. Initialize Select2 for Car Search ---
        productSelect.select2({
            placeholder: 'نام خودرو را جستجو کنید...',
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
                        console.error('Server error:', data.data ? data.data.message : 'Unknown error');
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
                // Inject the calculator HTML into the wrapper
                const calculatorHTML = `
                    <div class="form-grid" style="margin-top: 20px;">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="expert_down_payment">مبلغ پیش پرداخت (تومان)</label>
                                <input type="text" id="expert_down_payment" name="down_payment" class="regular-text" required>
                                <p class="description" style="margin-top: 5px;">حداقل پیش‌پرداخت پیشنهادی: ${formatMoney(minDownPayment)} تومان</p>
                            </div>
                            <div class="form-group">
                                <label for="expert_term_months">مدت بازپرداخت (ماه)</label>
                                <select name="term_months" id="expert_term_months" class="regular-text">
                                    <option value="12">۱۲ ماهه</option>
                                    <option value="18" selected>۱۸ ماهه</option>
                                    <option value="24">۲۴ ماهه</option>
                                    <option value="36">۳۶ ماهه</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row" style="border-top: 1px solid #eee; padding-top: 15px; margin-top: 15px;">
                           <div class="form-group">
                                <label>مبلغ کل وام</label>
                                <div class="result-display"><span id="expert-loan-amount">-</span> <span>تومان</span></div>
                            </div>
                             <div class="form-group">
                                <label>مبلغ کل بازپرداخت</label>
                                <div class="result-display"><span id="expert-total-repayment">-</span> <span>تومان</span></div>
                            </div>
                             <div class="form-group">
                                <label>مبلغ تقریبی هر قسط</label>
                                <div class="result-display" style="color: #3989BE; font-size: 1.3em;"><span id="expert-installment-amount">-</span> <span>تومان</span></div>
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
                        $('#expert-total-repayment').text(formatMoney(dp));
                        return;
                    }
                    
                    const monthlyInterestAmount = loanAmount * 0.035;
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
            
            issuerForm.slideToggle(isOther);
            issuerForm.find('input, select').prop('required', isOther);
        };

        issuerRadios.on('change', toggleIssuerForm);

        // Initial check on page load
        toggleIssuerForm(); 
    });

})(jQuery);