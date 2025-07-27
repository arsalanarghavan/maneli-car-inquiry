(function($) {
    'use strict';

    $(document).ready(function() {

        const expertForm = $('#expert-inquiry-form');
        // If the expert form is not on the page, do nothing.
        if (!expertForm.length) {
            return;
        }

        const productSelect = $('#product_id_expert');
        const detailsWrapper = $('#expert-form-details');

        // --- Initialize Select2 AJAX Car Search ---
        productSelect.select2({
            placeholder: 'نام خودرو را جستجو کنید...',
            dir: "rtl",
            width: '100%',
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
                    return {
                        results: data.results
                    };
                }
            }
        });

        // --- Handle actions after a car is selected ---
        productSelect.on('select2:select', function(e) {
            const data = e.params.data;
            const price = parseInt(data.price);
            const minDownPayment = parseInt(data.min_downpayment);

            if (price > 0) {
                const calculatorWrapper = $('#loan-calculator-wrapper');
                const calculatorHTML = `
                    <div class="form-grid" style="margin-top: 20px;">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="expert_down_payment">مبلغ پیش پرداخت (تومان)</label>
                                <input type="text" id="expert_down_payment" name="down_payment" class="regular-text" required>
                                <div class="input-note">حداقل پیش‌پرداخت: ${minDownPayment.toLocaleString('fa-IR')} تومان</div>
                            </div>
                            <div class="form-group">
                                <label for="expert_term_months">مدت بازپرداخت (ماه)</label>
                                <select name="term_months" id="expert_term_months">
                                    <option value="12">۱۲ ماهه</option>
                                    <option value="18">۱۸ ماهه</option>
                                    <option value="24">۲۴ ماهه</option>
                                    <option value="36">۳۶ ماهه</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>مبلغ تقریبی هر قسط</label>
                                <div class="result-display"><span>تومان</span> <span id="expert-installment-amount">-</span></div>
                            </div>
                        </div>
                    </div>`;
                
                calculatorWrapper.html(calculatorHTML);
                detailsWrapper.slideDown();
                
                const downPaymentInput = $('#expert_down_payment');
                const termSelect = $('#expert_term_months');
                const installmentDisplay = $('#expert-installment-amount');

                // Add Thousand Separator Logic
                downPaymentInput.on('keyup input', function(event) {
                    // Get raw number value by removing commas
                    let value = $(this).val().replace(/,/g, '');
                    
                    // Check if it's a valid number
                    if ($.isNumeric(value)) {
                        // Format with US-style commas, which are easy to parse back
                        $(this).val(parseInt(value, 10).toLocaleString('en-US'));
                    } else {
                        // Clear the field if invalid characters are entered
                        $(this).val('');
                    }
                    // Trigger calculation on every keyup
                    calculateInstallment();
                });

                function calculateInstallment() {
                    let dp_string = downPaymentInput.val() || '0';
                    // Always remove commas before parsing to an integer
                    const dp = parseInt(dp_string.replace(/,/g, '')) || 0;
                    const months = parseInt(termSelect.val()) || 12;
                    const loanAmount = price - dp;

                    if (loanAmount < 0) {
                        installmentDisplay.text('نامعتبر');
                        return;
                    }
                    if (loanAmount === 0) {
                        installmentDisplay.text('0');
                        return;
                    }
                    
                    const monthlyInterestAmount = loanAmount * 0.035;
                    const totalInterest = monthlyInterestAmount * (months + 1);
                    const totalRepayment = loanAmount + totalInterest;
                    const installment = totalRepayment / months;
                    
                    // Format the final result with Persian locale for display
                    installmentDisplay.text(Math.ceil(installment).toLocaleString('fa-IR'));
                }

                downPaymentInput.on('change', calculateInstallment);
                termSelect.on('change', calculateInstallment);
                
                // Set initial value and trigger the formatting and calculation
                downPaymentInput.val(minDownPayment.toString()).trigger('keyup');
            }
        });
        
        productSelect.on('select2:unselect', function (e) {
            detailsWrapper.slideUp();
            $('#loan-calculator-wrapper').empty();
        });

        // --- Logic for conditional issuer form ---
        const issuerRadios = expertForm.find('input[name="issuer_type"]');
        const issuerForm = $('#issuer-form-wrapper');
        const issuerInputs = issuerForm.find('input');

        function toggleIssuerForm() {
            const selectedValue = expertForm.find('input[name="issuer_type"]:checked').val();
            if (selectedValue === 'self') {
                issuerForm.slideUp();
                issuerInputs.prop('required', false);
            } else {
                issuerForm.slideDown();
                issuerInputs.prop('required', true);
            }
        }
        issuerRadios.on('change', toggleIssuerForm);
        toggleIssuerForm(); // Set initial state on page load
    });

})(jQuery);