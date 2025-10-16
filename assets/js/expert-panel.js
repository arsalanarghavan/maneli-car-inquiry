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
        console.log('Expert panel JS loaded!');
        
        const expertForm = $('#expert-inquiry-form');
        console.log('Expert form found:', expertForm.length);
        
        if (!expertForm.length) {
            // If the expert form is not on the page, do nothing.
            console.log('Expert form not found. Exiting...');
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
        // Check if required libraries are available
        console.log('Checking dependencies...');
        console.log('jQuery available:', typeof $ !== 'undefined');
        console.log('Select2 available:', typeof $.fn.select2 !== 'undefined');
        console.log('maneli_expert_ajax available:', typeof maneli_expert_ajax !== 'undefined');
        
        if (typeof maneli_expert_ajax === 'undefined') {
            console.error('maneli_expert_ajax is not defined! Script not localized properly.');
            return;
        }
        
        if (typeof $.fn.select2 === 'undefined') {
            console.error('Select2 is not loaded! Cannot initialize car search.');
            return;
        }
        
        console.log('All dependencies OK. Initializing Select2 for car search...', productSelect);
        
        // Destroy any existing Select2 instance first
        if (productSelect.hasClass('select2-hidden-accessible')) {
            productSelect.select2('destroy');
        }
        
        // Hide the original select element explicitly
        productSelect.hide();
        
        productSelect.select2({
            placeholder: getText('search_placeholder', 'شروع به تایپ برای جستجوی خودرو... (حداقل 2 حرف)'),
            dir: "rtl",
            width: '100%',
            allowClear: true,
            minimumInputLength: 2, // Require at least 2 characters before searching
            dropdownAutoWidth: false,
            dropdownParent: productSelect.parent(), // Attach dropdown to the parent element
            containerCssClass: 'maneli-select2-container',
            dropdownCssClass: 'maneli-select2-dropdown',
            language: {
                inputTooShort: function() {
                    return getText('input_too_short', 'لطفاً حداقل 2 حرف وارد کنید...');
                },
                searching: function() {
                    return getText('searching', 'در حال جستجو...');
                },
                noResults: function() {
                    return getText('no_results', 'خودرویی یافت نشد');
                },
                loadingMore: function() {
                    return getText('loading_more', 'در حال بارگذاری بیشتر...');
                }
            },
            ajax: {
                url: maneli_expert_ajax.ajax_url,
                dataType: 'json',
                delay: 250,
                method: 'POST',
                data: function(params) {
                    console.log('Searching for:', params.term);
                    return {
                        action: 'maneli_search_cars',
                        nonce: maneli_expert_ajax.nonce,
                        search: params.term,
                    };
                },
                processResults: function(data) {
                    console.log('Search results:', data);
                    if (data.success) {
                        console.log('Found', data.data.results.length, 'cars');
                        return { results: data.data.results };
                    } else {
                        console.error(getText('server_error', 'Server error:'), data.data ? data.data.message : getText('unknown_error', 'Unknown error'));
                        return { results: [] };
                    }
                },
                cache: true
            }
        });
        
        // Ensure the original select stays hidden after Select2 initialization
        productSelect.hide();
        
        console.log('Select2 initialized. Original select hidden:', productSelect.is(':hidden'));

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
        
        // --- 5. Initialize Datepickers for Birth Date Fields ---
        if (typeof kamaDatepicker === 'function') {
            // Initialize datepicker for buyer birth date
            if ($('#expert_buyer_birth_date').length) {
                kamaDatepicker('expert_buyer_birth_date', {
                    buttonsColor: "red",
                    forceFarsiDigits: true,
                    markToday: true,
                    markHolidays: true,
                    highlightSelectedDay: true,
                    sync: true
                });
            }
            
            // Initialize datepicker for issuer birth date
            if ($('#expert_issuer_birth_date').length) {
                kamaDatepicker('expert_issuer_birth_date', {
                    buttonsColor: "red",
                    forceFarsiDigits: true,
                    markToday: true,
                    markHolidays: true,
                    highlightSelectedDay: true,
                    sync: true
                });
            }
        }
        
        // --- 6. Job Type Toggles (Show/Hide Job Title Field) ---
        $('#buyer_job_type').on('change', function() {
            const jobType = $(this).val();
            if (jobType === 'employee') {
                $('.buyer-job-title-wrapper').show();
                $('.buyer-property-wrapper').show();
            } else {
                $('.buyer-job-title-wrapper').hide();
                $('.buyer-property-wrapper').hide();
            }
        });
        
        $('#issuer_job_type').on('change', function() {
            const jobType = $(this).val();
            if (jobType === 'employee') {
                $('.issuer-job-title-wrapper').show();
            } else {
                $('.issuer-job-title-wrapper').hide();
            }
        });
    });

})(jQuery);