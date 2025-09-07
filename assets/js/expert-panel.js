(function($) {
    'use strict';

    $(document).ready(function() {
        const expertForm = $('#expert-inquiry-form');
        if (!expertForm.length) {
            return;
        }

        const productSelect = $('#product_id_expert');
        const detailsWrapper = $('#expert-form-details');

        const formatMoney = (num) => {
            if (isNaN(num) || num === null) return '-';
            return Math.ceil(num).toLocaleString('fa-IR');
        };

        const parseMoney = (str) => {
             if (!str) return 0;
             return parseInt(String(str).replace(/,/g, ''), 10) || 0;
        };

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
                        security_nonce: maneli_expert_ajax.security_nonce, // Corrected nonce key
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
                                <div class="result-display" style="color: #2D89BE; font-size: 1.3em;"><span id="expert-installment-amount">-</span> <span>تومان</span></div>
                            </div>
                        </div>
                    </div>`;

                calculatorWrapper.html(calculatorHTML);
                detailsWrapper.slideDown();

                const downPaymentInput = $('#expert_down_payment');
                const termSelect = $('#expert_term_months');
                const installmentDisplay = $('#expert-installment-amount');
                const loanAmountDisplay = $('#expert-loan-amount');
                const totalRepaymentDisplay = $('#expert-total-repayment');
                
                downPaymentInput.on('keyup input', function(event) {
                    let value = parseMoney($(this).val());
                    if (value > 0) {
                       $(this).val(value.toLocaleString('en-US'));
                    } else {
                       $(this).val('');
                    }
                    calculateInstallment();
                });

                function calculateInstallment() {
                    let dp = parseMoney(downPaymentInput.val());
                    const months = parseInt(termSelect.val()) || 12;
                    const loanAmount = price - dp;

                    if (loanAmount <= 0) {
                        installmentDisplay.text('0');
                        loanAmountDisplay.text('0');
                        totalRepaymentDisplay.text(formatMoney(dp));
                        return;
                    }

                    const monthlyInterestAmount = loanAmount * 0.035;
                    const totalInterest = monthlyInterestAmount * (months + 1);
                    const totalRepayment = loanAmount + totalInterest;
                    const installment = totalRepayment / months;

                    loanAmountDisplay.text(formatMoney(loanAmount));
                    totalRepaymentDisplay.text(formatMoney(totalRepayment));
                    installmentDisplay.text(formatMoney(installment));
                }

                downPaymentInput.on('change', calculateInstallment);
                termSelect.on('change', calculateInstallment);

                downPaymentInput.val(minDownPayment.toString()).trigger('keyup');
            }
        });

        productSelect.on('select2:unselect', function(e) {
            detailsWrapper.slideUp();
            $('#loan-calculator-wrapper').empty();
        });

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
        toggleIssuerForm(); 
    });

})(jQuery);