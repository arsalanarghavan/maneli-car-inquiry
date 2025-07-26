(function($) {
    'use strict';

    $(document).ready(function() {

        // --- Logic for conditional issuer form ---
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
        toggleIssuerForm(); // Run on page load to set the initial state

        // --- Logic for loan calculator ---
        const productSelect = $('#product_id_expert');
        const calculatorWrapper = $('#loan-calculator-wrapper');

        productSelect.on('change', function() {
            const selectedOption = $(this).find('option:selected');
            const price = parseInt(selectedOption.data('price'));

            if (price > 0) {
                // Simplified calculator HTML for the expert panel
                const calculatorHTML = `
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row"><label for="expert_down_payment">مبلغ پیش پرداخت (تومان)</label></th>
                                <td><input type="number" id="expert_down_payment" name="down_payment" class="regular-text" required></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="expert_term_months">مدت بازپرداخت (ماه)</label></th>
                                <td>
                                    <select name="term_months" id="expert_term_months">
                                        <option value="12">۱۲ ماهه</option>
                                        <option value="18">۱۸ ماهه</option>
                                        <option value="24">۲۴ ماهه</option>
                                        <option value="36">۳۶ ماهه</option>
                                    </select>
                                </td>
                            </tr>
                             <tr>
                                <th scope="row"><label>مبلغ تقریبی هر قسط</label></th>
                                <td><div class="result-display"><span id="expert-installment-amount">-</span> تومان</div></td>
                            </tr>
                        </tbody>
                    </table>
                `;
                calculatorWrapper.html(calculatorHTML).slideDown();

                // Add live calculation logic to the newly added elements
                const downPaymentInput = $('#expert_down_payment');
                const termSelect = $('#expert_term_months');
                const installmentDisplay = $('#expert-installment-amount');

                function calculateInstallment() {
                    const dp = parseInt(downPaymentInput.val()) || 0;
                    const months = parseInt(termSelect.val()) || 12;
                    const loanAmount = price - dp;

                    if (loanAmount <= 0) {
                        installmentDisplay.text('0');
                        return;
                    }

                    // The calculation logic is assumed to be the same as the frontend calculator
                    // (3.5% monthly interest on the remaining amount for the duration)
                    const monthlyInterestAmount = loanAmount * 0.035;
                    const totalInterest = monthlyInterestAmount * (months + 1);
                    const totalRepayment = loanAmount + totalInterest;
                    const installment = totalRepayment / months;
                    
                    // Format number with commas for Persian locale
                    installmentDisplay.text(Math.ceil(installment).toLocaleString('fa-IR'));
                }

                // Attach event listeners to the new inputs
                downPaymentInput.on('keyup change', calculateInstallment);
                termSelect.on('change', calculateInstallment);

            } else {
                calculatorWrapper.slideUp().empty();
            }
        });
    });

})(jQuery);