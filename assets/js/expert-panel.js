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
                    <div class="mb-4 pb-3 border-bottom">
                        <h5 class="mb-3">
                            <span class="avatar avatar-sm avatar-rounded bg-success-transparent me-2">
                                <i class="la la-calculator fs-18"></i>
                            </span>
                            <span class="align-middle">محاسبه اقساط</span>
                        </h5>
                        
                        <div class="card custom-card shadow-none border border-success mb-3">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="expert_down_payment" class="form-label fw-semibold">
                                            <i class="la la-wallet text-success me-1"></i>
                                            مبلغ پیش‌پرداخت (تومان) <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" id="expert_down_payment" name="down_payment" class="form-control form-control-lg" required>
                                        <div class="form-text text-muted">
                                            <i class="la la-info-circle me-1"></i>
                                            حداقل پیش‌پرداخت: <strong class="text-success">${formatMoney(minDownPayment)}</strong> تومان
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">
                                            <i class="la la-calendar text-info me-1"></i>
                                            تعداد اقساط (ماه) <span class="text-danger">*</span>
                                        </label>
                                        <input type="hidden" name="term_months" id="expert_term_months" value="18">
                                        <div class="btn-group w-100" role="group">
                                            <input type="radio" class="btn-check" name="expert_term_radio" id="term-12" value="12">
                                            <label class="btn btn-outline-primary" for="term-12">12 ماه</label>
                                            
                                            <input type="radio" class="btn-check" name="expert_term_radio" id="term-18" value="18" checked>
                                            <label class="btn btn-outline-primary" for="term-18">18 ماه</label>
                                            
                                            <input type="radio" class="btn-check" name="expert_term_radio" id="term-24" value="24">
                                            <label class="btn btn-outline-primary" for="term-24">24 ماه</label>
                                            
                                            <input type="radio" class="btn-check" name="expert_term_radio" id="term-36" value="36">
                                            <label class="btn btn-outline-primary" for="term-36">36 ماه</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row g-3 mt-3">
                                    <div class="col-md-4">
                                        <div class="alert alert-primary mb-0">
                                            <label class="fw-semibold mb-1">مبلغ وام</label>
                                            <div class="fs-5"><span id="expert-loan-amount">-</span> تومان</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="alert alert-info mb-0">
                                            <label class="fw-semibold mb-1">مجموع بازپرداخت</label>
                                            <div class="fs-5"><span id="expert-total-repayment">-</span> تومان</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="alert alert-success mb-0">
                                            <label class="fw-semibold mb-1">قسط ماهانه</label>
                                            <div class="fs-4 fw-bold"><span id="expert-installment-amount">-</span> تومان</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>`;

                calculatorWrapper.html(calculatorHTML);
                detailsWrapper.slideDown();

                // --- Calculator Live Update Logic ---
                const downPaymentInput = $('#expert_down_payment');
                const termHiddenInput = $('#expert_term_months');
                
                // Handle term button clicks
                $('input[name="expert_term_radio"]').on('change', function() {
                    termHiddenInput.val($(this).val());
                    calculateInstallment();
                });
                
                const calculateInstallment = () => {
                    const dp = parseMoney(downPaymentInput.val());
                    const months = parseInt(termHiddenInput.val(), 10) || 18;
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
        if (typeof kamadatepicker === 'function') {
            // Initialize datepicker for buyer birth date
            if ($('#expert_buyer_birth_date').length) {
                kamadatepicker('expert_buyer_birth_date', {
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
                kamadatepicker('expert_issuer_birth_date', {
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
            const $jobTitleWrapper = $('.buyer-job-title-wrapper');
            const $propertyWrapper = $('.buyer-property-wrapper');
            
            if (jobType === 'self') {
                // آزاد: نیاز به عنوان شغلی و وضعیت مسکن دارد
                $jobTitleWrapper.slideDown(200);
                $propertyWrapper.slideDown(200);
            } else if (jobType === 'employee') {
                // کارمند: فقط درآمد لازم است، بقیه مخفی
                $jobTitleWrapper.slideUp(200);
                $propertyWrapper.slideUp(200);
            } else {
                // هیچکدام انتخاب نشده
                $jobTitleWrapper.slideUp(200);
                $propertyWrapper.slideUp(200);
            }
        });
        
        $('#issuer_job_type').on('change', function() {
            const jobType = $(this).val();
            const $jobTitleWrapper = $('.issuer-job-title-wrapper');
            const $propertyWrapper = $('.issuer-property-wrapper');
            
            if (jobType === 'self') {
                // آزاد: نیاز به عنوان شغلی و وضعیت مسکن دارد
                $jobTitleWrapper.slideDown(200);
                $propertyWrapper.slideDown(200);
            } else if (jobType === 'employee') {
                // کارمند: فقط درآمد لازم است، بقیه مخفی
                $jobTitleWrapper.slideUp(200);
                $propertyWrapper.slideUp(200);
            } else {
                // هیچکدام انتخاب نشده
                $jobTitleWrapper.slideUp(200);
                $propertyWrapper.slideUp(200);
            }
        });
    });

})(jQuery);