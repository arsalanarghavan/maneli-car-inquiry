(function($) {
    'use strict';

    $(document).ready(function() {
        const buyerForm = $('#buyer-form-wrapper');
        const issuerForm = $('#issuer-form-wrapper');
        const issuerInputs = issuerForm.find('input');

        function toggleIssuerForm() {
            const selectedValue = $('input[name="issuer_type"]:checked').val();
            if (selectedValue === 'self') {
                issuerForm.slideUp();
                issuerInputs.prop('required', false);
            } else {
                issuerForm.slideDown();
                issuerInputs.prop('required', true);
            }
        }
        $('input[name="issuer_type"]').on('change', toggleIssuerForm);
        toggleIssuerForm();

        const productSelect = $('#product_id_expert');
        const calculatorWrapper = $('#loan-calculator-wrapper');

        productSelect.on('change', function() {
            const selectedOption = $(this).find('option:selected');
            const price = parseInt(selectedOption.data('price'));

            if (price > 0) {
                const calculatorHTML = `
                    <div class="form-grid" style="margin-top: 20px;">
                        <div class="form-row">
                            <div class="form-group"><label>مبلغ پیش پرداخت (تومان)</label><input type="number" name="down_payment" class="regular-text" required></div>
                            <div class="form-group"><label>مدت بازپرداخت (ماه)</label><select name="term_months"><option value="12">۱۲</option><option value="18">۱۸</option><option value="24">۲۴</option><option value="36">۳۶</option></select></div>
                        </div>
                        <div class="form-row"><div class="form-group"><label>مبلغ تقریبی هر قسط</label><div class="result-display"><span id="expert-installment-amount">-</span> تومان</div></div></div>
                    </div>`;
                calculatorWrapper.html(calculatorHTML).slideDown();

                const downPaymentInput = $('input[name="down_payment"]');
                const termSelect = $('select[name="term_months"]');
                const installmentDisplay = $('#expert-installment-amount');

                function calculateInstallment() {
                    const dp = parseInt(downPaymentInput.val()) || 0;
                    const months = parseInt(termSelect.val()) || 12;
                    const loanAmount = price - dp;
                    if (loanAmount <= 0) { installmentDisplay.text('0'); return; }
                    const monthlyInterestAmount = loanAmount * 0.035;
                    const totalInterest = monthlyInterestAmount * (months + 1);
                    const totalRepayment = loanAmount + totalInterest;
                    const installment = totalRepayment / months;
                    installmentDisplay.text(Math.ceil(installment).toLocaleString('fa-IR'));
                }
                downPaymentInput.on('keyup change', calculateInstallment);
                termSelect.on('change', calculateInstallment);
            } else {
                calculatorWrapper.slideUp().empty();
            }
        });
    });
})(jQuery);