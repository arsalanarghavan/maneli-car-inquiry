/**
 * Handles JavaScript functionality for the multi-step inquiry form,
 * including datepicker initialization and conditional form visibility.
 *
 * This script is now centralized and handles Datepicker initialization for:
 * 1. Customer Inquiry Form (step-2-identity-form.php)
 * 2. Expert New Inquiry Form (expert-new-inquiry-form.php)
 * 3. User Edit Forms (form-edit-user.php and admin user profile)
 *
 * @version 1.2.0 (Persian digit conversion and validation fixes)
 */
document.addEventListener('DOMContentLoaded', function() {

    /**
     * Dynamically retrieves the datepicker placeholder text from available localization objects.
     *
     * این تابع فرض می‌کند که رشته‌ی محلی‌سازی شده با کلید 'datepicker_placeholder' 
     * در آبجکت autopuzzle_expert_ajax.text قرار داده شده است.
     *
     * @return {string} The localized placeholder text or a default.
     */
    function getDatepickerPlaceholder() {
        if (typeof autopuzzle_expert_ajax !== 'undefined' && autopuzzle_expert_ajax.text && autopuzzle_expert_ajax.text.datepicker_placeholder) {
            return autopuzzle_expert_ajax.text.datepicker_placeholder;
        }
        // Fallback to a generic, non-hardcoded value to maintain the datepicker's function.
        return 'YYYY/MM/DD'; 
    }

    const datepickerPlaceholder = getDatepickerPlaceholder();

    // --- 0.5 Persian Number Conversion Helpers ---
    /**
     * Convert English digits to Persian digits
     */
    function toPersianDigits(str) {
        if (!str) return '';
        const english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        const persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        let result = String(str);
        for (let i = 0; i < 10; i++) {
            result = result.split(english[i]).join(persian[i]);
        }
        return result;
    }

    /**
     * Convert Persian digits to English digits
     */
    function toEnglishDigits(str) {
        if (!str) return '';
        const persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        const english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        let result = String(str);
        for (let i = 0; i < 10; i++) {
            result = result.split(persian[i]).join(english[i]);
        }
        return result;
    }

    // --- 0.6 Convert numeric input fields to display Persian digits ---
    // Target numeric fields: national code, phone numbers, income, account numbers, etc.
    const numericFields = [
        '#national_code',
        '#mobile_number', 
        '#phone_number',
        '#income_level',
        '#account_number',
        '#branch_code',
        '#issuer_national_code',
        '#issuer_mobile_number',
        '#issuer_phone_number',
        '#issuer_income_level',
        '#issuer_account_number',
        '#issuer_branch_code',
        // Profile settings page fields
        '#profile-national-code',
        '#profile-phn-no',
        '#profile-phone',
        '#profile-income',
        '#profile-account-number',
        '#profile-branch-code'
    ];

    numericFields.forEach(selector => {
        const elements = document.querySelectorAll(selector);
        elements.forEach(element => {
            // Convert on blur (when user finishes typing)
            element.addEventListener('blur', function() {
                if (this.value && /^\d+$/.test(toEnglishDigits(this.value))) {
                    // Only convert if all characters are digits
                    this.value = toPersianDigits(toEnglishDigits(this.value));
                }
            });

            // Convert initial value if present
            if (element.value && /^\d+$/.test(toEnglishDigits(element.value))) {
                element.value = toPersianDigits(toEnglishDigits(element.value));
            }
        });
    });

    // --- 0.7 Convert Persian digits back to English before form submission ---
    // Also fix pattern validation for Persian digits
    document.querySelectorAll('form').forEach(form => {
        // Only handle forms that contain numeric fields
        const hasNumericFields = form.querySelectorAll(numericFields.join(',')).length > 0;
        if (hasNumericFields) {
            form.addEventListener('submit', function(e) {
                // Convert all Persian digits to English before submission
                numericFields.forEach(selector => {
                    const elements = form.querySelectorAll(selector);
                    elements.forEach(element => {
                        if (element.value) {
                            element.value = toEnglishDigits(element.value);
                        }
                    });
                });
            });
            
            // Fix pattern validation for fields with Persian digits
            // Store original pattern, then validate in JS
            form.querySelectorAll('input[pattern]').forEach(input => {
                // Check if this input is in our numeric fields list
                const inputId = input.getAttribute('id');
                const isNumericField = numericFields.some(sel => {
                    const selectorWithoutHash = sel.replace('#', '');
                    return inputId === selectorWithoutHash;
                });
                
                if (isNumericField) {
                    const originalPattern = input.getAttribute('pattern');
                    // Remove pattern to allow Persian digits in HTML5 validation
                    input.removeAttribute('pattern');
                    
                    // Add custom validation
                    const validationHandler = function(e) {
                        const englishValue = toEnglishDigits(this.value);
                        this.setCustomValidity('');
                        
                        // Convert to English and check length
                        if (originalPattern === '\\d{10}' && englishValue.length !== 10) {
                            this.setCustomValidity('کد ملی باید ۱۰ رقم باشد');
                            return;
                        }
                    };
                    
                    input.addEventListener('invalid', validationHandler);
                    input.addEventListener('blur', validationHandler);
                }
            });
        }
    });

    // --- 1. Datepicker Initialization ---
    // Wait for jQuery and check if persianDatepicker library is available
    if (typeof jQuery !== 'undefined') {
        jQuery(document).ready(function($) {
            // Check if persianDatepicker plugin is available
            if (typeof $.fn.persianDatepicker !== 'undefined') {
                
                // Target all individual IDs used in various forms (customer/expert) 
                // AND the class used in admin profile/user edit forms.
                const datepickerSelectors = [
                    '#buyer_birth_date',
                    '#issuer_birth_date',
                    '#expert_buyer_birth_date',
                    '#expert_issuer_birth_date',
                    // Target used in form-edit-user.php and class-user-profile.php
                    '#birth_date',
                    // Profile settings page
                    '#profile-birth-date',
                    // Target used generally in class-user-profile.php
                    '.autopuzzle-datepicker'
                ];

                datepickerSelectors.forEach(selector => {
                    $(selector).each(function() {
                        var $element = $(this);
                        
                        // Ensure the element hasn't been initialized
                        if ($element.attr('data-pdp-init')) {
                            return;
                        }
                        
                        // Initialize persianDatepicker (jQuery plugin - works with jQuery objects)
                        $element.persianDatepicker({
                            formatDate: 'YYYY/MM/DD',
                            persianNumbers: true, // Display Persian digits
                            autoClose: true,
                            initialValue: false,
                            observer: false
                        });
                        
                        $element.attr('data-pdp-init', 'true'); // Mark as initialized
                    });
                });
            }
        });
    }

    // --- 2. Issuer Choice Form Logic (Step 2 of Inquiry Form & Expert Form) ---
    // This handles both the customer identity form and the expert new inquiry form.
    const identityForm = document.getElementById('identity-form') || document.getElementById('expert-inquiry-form');
    if (identityForm) {
        const radios = identityForm.querySelectorAll('input[name="issuer_type"]');
        // Note: In expert form, this ID might not exist, hence the more robust selector
        const buyerForm = document.getElementById('buyer-form-wrapper') || identityForm.querySelector('#expert-form-details > #buyer-form-wrapper'); 
        const issuerForm = document.getElementById('issuer-form-wrapper');

        /**
         * Toggles the visibility of the buyer and issuer form sections based on
         * the selected radio button.
         */
        function toggleForms() {
            const checkedRadio = identityForm.querySelector('input[name="issuer_type"]:checked');
            
            // If no radio is selected, hide both forms (only possible on initial load of expert form if not checked by default).
            if (!checkedRadio) {
                if (buyerForm) buyerForm.style.display = 'none';
                if (issuerForm) issuerForm.style.display = 'none';
                return;
            }

            const selectedValue = checkedRadio.value;
            
            // The buyer form is always shown once a choice is made (it's the main section).
            if (buyerForm) {
                buyerForm.style.display = 'block';
            }
            
            // The issuer form is only shown if the issuer is 'other'.
            if (issuerForm) {
                // The expert form uses smooth toggle, customer form uses direct style manipulation
                if (identityForm.id === 'expert-inquiry-form') {
                     $(issuerForm).slideToggle(selectedValue === 'other');
                } else {
                     issuerForm.style.display = (selectedValue === 'other') ? 'block' : 'none';
                }
            }
        }

        // Attach the change event listener to each radio button.
        radios.forEach(radio => radio.addEventListener('change', toggleForms));
        
        // Run the function once on page load to set the initial state.
        toggleForms();
    }

    // --- 2.1 Job type and conditional fields (buyer & issuer) ---
    const buyerJobType = document.getElementById('buyer_job_type');
    const buyerJobTitleWrapper = document.querySelector('.buyer-job-title-wrapper');
    const buyerPropertyWrapper = document.querySelector('.buyer-property-wrapper');
    function toggleBuyerJob() {
        if (!buyerJobType) return;
        const v = buyerJobType.value; // '' | self | employee
        if (buyerJobTitleWrapper) buyerJobTitleWrapper.style.display = v ? 'block' : 'none';
        if (buyerPropertyWrapper) buyerPropertyWrapper.style.display = (v === 'self') ? 'block' : 'none';
    }
    if (buyerJobType) {
        buyerJobType.addEventListener('change', toggleBuyerJob);
        toggleBuyerJob();
    }

    const issuerJobType = document.getElementById('issuer_job_type');
    const issuerJobTitleWrapper = document.querySelector('.issuer-job-title-wrapper');
    const issuerPropertyWrapper = document.querySelector('.issuer-property-wrapper');
    function toggleIssuerJob() {
        if (!issuerJobType) return;
        const v = issuerJobType.value;
        if (issuerJobTitleWrapper) issuerJobTitleWrapper.style.display = v ? 'block' : 'none';
        if (issuerPropertyWrapper) issuerPropertyWrapper.style.display = (v === 'self') ? 'block' : 'none';
    }
    if (issuerJobType) {
        issuerJobType.addEventListener('change', toggleIssuerJob);
        toggleIssuerJob();
    }
    
    // --- 3. Discount Code Toggle Logic (Step 3 of Inquiry Form) ---
    const showDiscountLink = document.getElementById('show-discount-form');
    if (showDiscountLink) {
        showDiscountLink.addEventListener('click', function(e) {
            e.preventDefault();
            const discountWrapper = document.getElementById('discount-form-wrapper');
            if (discountWrapper) {
                discountWrapper.style.display = 'block';
            }
            // Hide the parent paragraph of the link
            this.parentElement.style.display = 'none';
        });
    }

    // --- 4. Confirm Car Step: Catalog load & filters ---
    (function initConfirmCarCatalog(){
        const catalog = document.getElementById('confirm_car_catalog');
        const pagination = document.getElementById('confirm_car_pagination');
        const searchInput = document.getElementById('confirm_car_search');
        const filterBtn = document.getElementById('confirm_car_filter_btn');
        // Only catalog is required, pagination is optional
        if (!catalog) {
            console.log('Confirm car catalog: catalog element not found');
            return;
        }

        function fetchCatalog(page=1){
            console.log('🔵 fetchCatalog called with page:', page);
            const params = new URLSearchParams();
            params.append('action', 'autopuzzle_confirm_car_catalog');
            const shared = (window.maneliInquiryForm && window.maneliInquiryForm.nonces && window.maneliInquiryForm.nonces.confirm_catalog) ? window.maneliInquiryForm.nonces.confirm_catalog : '';
            params.append('nonce', shared);
            params.append('page', String(page));
            if (searchInput && searchInput.value) params.append('search', searchInput.value);
            // Future: brand & category selects
            const brandSel = document.getElementById('confirm_car_brand');
            const catSel = document.getElementById('confirm_car_category');
            if (brandSel && brandSel.value) params.append('brand', brandSel.value);
            if (catSel && catSel.value) params.append('category', catSel.value);

            const ajaxUrl = (window.maneliInquiryForm && maneliInquiryForm.ajax_url) ? maneliInquiryForm.ajax_url : (window.ajaxurl || '/wp-admin/admin-ajax.php');
            
            console.log('🔵 Sending catalog request:', {
                ajaxUrl: ajaxUrl,
                action: 'autopuzzle_confirm_car_catalog',
                nonce: shared ? shared.substring(0, 10) + '...' : 'MISSING',
                page: page,
                maneliInquiryForm: window.maneliInquiryForm
            });
            
            fetch(ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: params.toString()
            }).then(r=>{
                console.log('🔵 Catalog response status:', r.status, r.statusText);
                if (!r.ok) {
                    throw new Error('HTTP error! status: ' + r.status);
                }
                return r.json();
            }).then(res=>{
                console.log('🔵 Catalog response data:', res);
                if (!res || !res.success) {
                    console.error('❌ Catalog fetch failed:', res);
                    if (res && res.data && res.data.message) {
                        console.error('Error message:', res.data.message);
                    }
                    // Show empty state or error message
                    if (catalog) {
                        catalog.innerHTML = '<div class="col-12"><div class="alert alert-warning">' + 
                            (res && res.data && res.data.message ? res.data.message : 'Failed to load catalog. Please refresh the page.') + 
                            '</div></div>';
                    }
                    return;
                }
                
                const html = res.data.html || '';
                const paginationHtml = res.data.pagination_html || '';
                
                console.log('🔵 Catalog HTML length:', html.length, 'Pagination HTML length:', paginationHtml.length);
                
                if (catalog) {
                    catalog.innerHTML = html;
                    const cards = catalog.querySelectorAll('.selectable-car');
                    console.log('✅ Catalog HTML inserted, cards count:', cards.length);
                    
                    // Debug: Check if cards are visible
                    if (cards.length > 0) {
                        const firstCard = cards[0];
                        const computedStyle = window.getComputedStyle(firstCard);
                        console.log('🔵 First card visibility check:', {
                            display: computedStyle.display,
                            visibility: computedStyle.visibility,
                            opacity: computedStyle.opacity,
                            width: computedStyle.width,
                            height: computedStyle.height,
                            offsetParent: firstCard.offsetParent !== null,
                            element: firstCard
                        });
                    } else {
                        console.warn('⚠️ No cards found after inserting HTML');
                    }
                } else {
                    console.error('❌ Catalog element not found!');
                }
                
                if (pagination && paginationHtml) {
                    pagination.innerHTML = paginationHtml;
                    console.log('✅ Pagination HTML inserted');
                    // Hook clicks on pagination links
                    pagination.querySelectorAll('a').forEach(a=>{
                        a.addEventListener('click', function(e){
                            e.preventDefault();
                            const m = this.href.match(/paged=(\d+)/);
                            const next = m ? parseInt(m[1], 10) : 1;
                            fetchCatalog(next);
                        });
                    });
                } else if (pagination) {
                    console.log('⚠ Pagination element exists but no pagination HTML received');
                }
                
                // Hook clicks on product cards to select/replace car
                catalog.querySelectorAll('.selectable-car').forEach(card=>{
                    card.addEventListener('click', function(){
                        const productId = this.getAttribute('data-product-id');
                        const productPrice = parseFloat(this.getAttribute('data-product-price')) || 0;
                        const productName = this.querySelector('.title')?.textContent?.trim() || '';
                        const productImage = this.querySelector('.thumb')?.innerHTML || '';
                        
                        if (!productId) return;
                        
                        // Check if we're on dashboard/new-inquiry page (step 3) - if so, open modal
                        const isDashboardNewInquiry = window.location.pathname.includes('/dashboard/new-inquiry') && 
                                                       (new URLSearchParams(window.location.search).get('step') === '3' || 
                                                        window.location.search.includes('step=3'));
                        
                        if (isDashboardNewInquiry) {
                            // Open installment calculator modal
                            openCalculatorModal(productId, productPrice, productName, productImage);
                            return;
                        }
                        
                        // For other pages, use the old behavior (SweetAlert confirmation)
                        // Get localized texts
                        const texts = (window.maneliInquiryForm && window.maneliInquiryForm.texts) ? window.maneliInquiryForm.texts : {};
                        const confirmText = texts.replace_car_confirm || 'Are you sure you want to replace the current car with this one?';
                        const successText = texts.car_replaced_success || 'Car replaced successfully. Page is being refreshed...';
                        const errorPrefix = texts.error_replacing_car || 'Error replacing car';
                        const serverError = texts.server_error || 'Server connection error. Please try again.';
                        
                        // Debug: log available nonces
                        console.log('Available nonces:', {
                            maneliInquiryForm: window.maneliInquiryForm?.nonces,
                            autopuzzle_ajax: window.autopuzzle_ajax?.nonce,
                            autopuzzle_ajax_object: window.autopuzzle_ajax_object?.nonce,
                            shared: shared
                        });
                        
                        // Show SweetAlert confirmation dialog
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: confirmText,
                                icon: 'question',
                                showCancelButton: true,
                                confirmButtonText: (texts.confirm || 'Yes'),
                                cancelButtonText: (texts.cancel || 'Cancel'),
                                confirmButtonColor: '#5e72e4',
                                cancelButtonColor: '#6c757d',
                                reverseButtons: true // Put cancel button on right (RTL support)
                            }).then((result) => {
                                if (!result.isConfirmed) return;
                                
                                replaceCar();
                            });
                        } else {
                            // Fallback to native confirm if SweetAlert is not available
                            if (!confirm(confirmText)) {
                                return;
                            }
                            replaceCar();
                        }
                        
                        function replaceCar() {
                            // Get current calculator values from page
                            const dataRow = document.querySelector('[data-down-payment]');
                            const currentDownPayment = dataRow?.getAttribute('data-down-payment') || '0';
                            const currentTermMonths = dataRow?.getAttribute('data-term-months') || '12';
                            const currentTotalPrice = productPrice > 0 ? productPrice : (dataRow?.getAttribute('data-total-price') || currentDownPayment);
                            
                            // Get AJAX URL from various sources (must be defined before params)
                            let ajaxUrl = '';
                            if (window.autopuzzle_ajax && window.autopuzzle_ajax.url) {
                                ajaxUrl = window.autopuzzle_ajax.url;
                            } else if (window.autopuzzle_ajax_object && window.autopuzzle_ajax_object.ajax_url) {
                                ajaxUrl = window.autopuzzle_ajax_object.ajax_url;
                            } else if (window.maneliInquiryForm && window.maneliInquiryForm.ajax_url) {
                                ajaxUrl = window.maneliInquiryForm.ajax_url;
                            } else if (window.ajaxurl) {
                                ajaxUrl = window.ajaxurl;
                            } else {
                                ajaxUrl = '/wp-admin/admin-ajax.php';
                            }
                            
                            // Call AJAX to replace selected car using existing handler
                            const params = new URLSearchParams();
                            params.append('action', 'autopuzzle_select_car_ajax');
                            // Try to get nonce from various sources (priority: select_car > autopuzzle_ajax > autopuzzle_ajax_object > confirm_catalog)
                            let ajaxNonce = '';
                            if (window.maneliInquiryForm && window.maneliInquiryForm.nonces && window.maneliInquiryForm.nonces.select_car) {
                                ajaxNonce = window.maneliInquiryForm.nonces.select_car;
                            } else if (window.autopuzzle_ajax && window.autopuzzle_ajax.nonce) {
                                ajaxNonce = window.autopuzzle_ajax.nonce;
                            } else if (window.autopuzzle_ajax_object && window.autopuzzle_ajax_object.nonce) {
                                ajaxNonce = window.autopuzzle_ajax_object.nonce;
                            } else if (window.maneliInquiryForm && window.maneliInquiryForm.nonces && window.maneliInquiryForm.nonces.confirm_catalog) {
                                ajaxNonce = window.maneliInquiryForm.nonces.confirm_catalog;
                            } else {
                                ajaxNonce = shared || '';
                            }
                            params.append('nonce', ajaxNonce);
                            params.append('product_id', productId);
                            params.append('total_price', currentTotalPrice);
                            params.append('down_payment', currentDownPayment);
                            params.append('term_months', currentTermMonths);
                            
                            // Debug: log request parameters
                            console.log('=== CAR REPLACEMENT AJAX REQUEST ===');
                            console.log('Action: autopuzzle_select_car_ajax');
                            console.log('AJAX URL:', ajaxUrl);
                            console.log('Nonce:', ajaxNonce ? ajaxNonce.substring(0, 10) + '...' : 'MISSING');
                            console.log('Product ID:', productId);
                            console.log('Total Price:', currentTotalPrice);
                            console.log('Down Payment:', currentDownPayment);
                            console.log('Term Months:', currentTermMonths);
                            console.log('Full params:', params.toString());
                            
                            fetch(ajaxUrl, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                                body: params.toString()
                            }).then(r=>{
                                // Log response for debugging
                                console.log('AJAX Response status:', r.status);
                                if (!r.ok) {
                                    throw new Error('HTTP error! status: ' + r.status);
                                }
                                return r.json();
                            }).then(res=>{
                                console.log('AJAX Response data:', res);
                                if (res && res.success) {
                                    // Update page with new car data if provided
                                    if (res.data && res.data.car_data) {
                                        const carData = res.data.car_data;
                                        console.log('Updating page with new car data:', carData);
                                        
                                        // Update data attributes
                                        const dataRow = document.querySelector('[data-down-payment]');
                                        if (dataRow) {
                                            dataRow.setAttribute('data-down-payment', carData.down_payment);
                                            dataRow.setAttribute('data-term-months', carData.term_months);
                                            dataRow.setAttribute('data-total-price', carData.total_price);
                                            
                                            // Update displayed values
                                            const downPaymentEl = dataRow.querySelector('.col-md-6:first-child .text-success strong');
                                            const termMonthsEl = dataRow.querySelector('.col-md-6:nth-child(2) .text-info strong');
                                            const installmentEl = dataRow.querySelector('.col-md-12 .text-primary strong');
                                            
                                            // Format and update displayed values (will be replaced after reload)
                                            if (downPaymentEl) {
                                                const formattedDown = new Intl.NumberFormat('fa-IR').format(carData.down_payment);
                                                downPaymentEl.textContent = formattedDown + ' تومان';
                                            }
                                            if (termMonthsEl) {
                                                termMonthsEl.textContent = carData.term_months + ' ماه';
                                            }
                                            if (installmentEl) {
                                                const formattedInstallment = new Intl.NumberFormat('fa-IR').format(carData.installment);
                                                installmentEl.textContent = formattedInstallment + ' تومان';
                                            }
                                        }
                                    }
                                    
                                    if (typeof Swal !== 'undefined') {
                                        Swal.fire({
                                            title: successText,
                                            icon: 'success',
                                            confirmButtonColor: '#5e72e4',
                                            timer: 2000,
                                            timerProgressBar: true
                                        }).then(() => {
                                            window.location.reload();
                                        });
                                    } else {
                                        alert(successText);
                                        window.location.reload();
                                    }
                                } else {
                                    // Handle different error types
                                    let errorMsg = serverError;
                                    if (res && res.data) {
                                        if (res.data.message) {
                                            errorMsg = res.data.message;
                                        } else if (typeof res.data === 'string') {
                                            errorMsg = res.data;
                                        }
                                    }
                                    
                                    // Check for common error messages and translate them
                                    const invalidRequestText = texts.invalid_request || 'Invalid request. Please log in and try again.';
                                    if (errorMsg.includes('Invalid request') || errorMsg.includes('nonce') || errorMsg.includes('security') || errorMsg.includes('security token')) {
                                        errorMsg = texts.invalid_request || invalidRequestText;
                                    } else if (errorMsg.includes('log in') || errorMsg.includes('Please log in')) {
                                        errorMsg = texts.please_login || 'Please log in to continue.';
                                    } else if (errorMsg.includes('Product ID')) {
                                        errorMsg = texts.product_id_required || 'Product ID is required.';
                                    }
                                    
                                    if (typeof Swal !== 'undefined') {
                                        Swal.fire({
                                            title: errorPrefix,
                                            text: errorMsg,
                                            icon: 'error',
                                            confirmButtonColor: '#dc3545'
                                        });
                                    } else {
                                        alert(errorPrefix + ': ' + errorMsg);
                                    }
                                    
                                    // Log for debugging
                                    console.error('Car replacement error:', {
                                        response: res,
                                        nonce: ajaxNonce,
                                        nonceSource: window.maneliInquiryForm?.nonces?.select_car ? 'maneliInquiryForm.nonces.select_car' : 
                                                    window.autopuzzle_ajax?.nonce ? 'autopuzzle_ajax.nonce' :
                                                    window.autopuzzle_ajax_object?.nonce ? 'autopuzzle_ajax_object.nonce' : 'other',
                                        ajaxUrl: ajaxUrl
                                    });
                                }
                            }).catch(err=>{
                                console.error('Error replacing car:', err);
                                console.error('Error details:', {
                                    message: err.message,
                                    stack: err.stack,
                                    ajaxUrl: ajaxUrl,
                                    params: params.toString()
                                });
                                if (typeof Swal !== 'undefined') {
                                    Swal.fire({
                                        title: errorPrefix,
                                        text: serverError + ' (Error: ' + err.message + ')',
                                        icon: 'error',
                                        confirmButtonColor: '#dc3545'
                                    });
                                } else {
                                    alert(serverError + ' (Error: ' + err.message + ')');
                                }
                            });
                        }
                    });
                });
            }).catch(err=>{
                console.error('❌ Error fetching catalog:', err);
                console.error('Error details:', {
                    message: err.message,
                    stack: err.stack,
                    ajaxUrl: ajaxUrl,
                    params: params.toString()
                });
                if (catalog) {
                    catalog.innerHTML = '<div class="col-12"><div class="alert alert-danger">خطا در بارگذاری کاتالوگ. لطفاً صفحه را refresh کنید.<br><small>' + err.message + '</small></div></div>';
                }
            });
        }

        if (filterBtn) filterBtn.addEventListener('click', ()=>fetchCatalog(1));
        if (searchInput) searchInput.addEventListener('keydown', (e)=>{ if (e.key==='Enter'){ e.preventDefault(); fetchCatalog(1);} });
        
        // Initialize catalog on page load
        console.log('Initializing confirm car catalog...');
        fetchCatalog(1);
    })();

    // Function to open calculator modal for car replacement (dashboard step 3)
    function openCalculatorModal(productId, productPrice, productName, productImage) {
        const modal = document.getElementById('installmentCalculatorModal');
        if (!modal) {
            console.error('Calculator modal not found');
            return;
        }

        // Store product data in modal data attributes
        const modalInstance = new bootstrap.Modal(modal);
        
        // Set product data on a temporary element that modal-calculator.js can read
        if (!window.maneliModalProductData) {
            window.maneliModalProductData = {};
        }
        
        window.maneliModalProductData = {
            id: productId,
            price: productPrice,
            name: productName,
            image: productImage
        };

        // Show modal
        modalInstance.show();
    }

    // Listen for custom event from modal calculator
    document.addEventListener('maneliReplaceCarFromModal', function(event) {
        const data = event.detail;
        console.log('🔵 Event received - maneliReplaceCarFromModal:', data);
        replaceCarWithData(data.product_id, data.down_payment, data.term_months, data.installment_amount, data.total_price);
    });

    // Function to update car info cards with new data
    // Flag to prevent multiple updates
    let isUpdatingCards = false;
    
    function updateCarInfoCards(carData) {
        console.log('🔵 updateCarInfoCards called with:', carData);
        
        // Prevent multiple simultaneous updates
        if (isUpdatingCards) {
            console.warn('⚠️ updateCarInfoCards: Already updating, skipping...');
            return;
        }
        
        isUpdatingCards = true;
        
        try {
            // Find the data row with car information
            const dataRow = document.querySelector('[data-down-payment]');
            console.log('🔵 dataRow found:', dataRow ? 'YES' : 'NO');
            if (!dataRow) {
                console.error('❌ updateCarInfoCards: dataRow not found! Searching for [data-down-payment]...');
                const allDataRows = document.querySelectorAll('[data-down-payment]');
                console.error('❌ Found', allDataRows.length, 'elements with [data-down-payment]');
                return;
            }
            if (!carData) {
                console.error('❌ updateCarInfoCards: carData is missing!', carData);
                return;
            }
            
            console.log('🔵 dataRow found, carData:', carData);
            
            // Update data attributes
            if (carData.down_payment !== undefined) {
                dataRow.setAttribute('data-down-payment', carData.down_payment);
            }
            if (carData.term_months !== undefined) {
                dataRow.setAttribute('data-term-months', carData.term_months);
            }
            if (carData.total_price !== undefined) {
                dataRow.setAttribute('data-total-price', carData.total_price);
            }
            
            // Format number function
            function formatPersianNumber(num) {
                if (!num && num !== 0) return '0';
                const numValue = parseInt(num);
                if (isNaN(numValue)) return '0';
                return new Intl.NumberFormat('fa-IR').format(numValue);
            }
            
            // Update displayed values - completely replace content
            // Strategy: Find strong element directly and replace ALL its text content
            
            // Down Payment (first col-md-6)
            const downPaymentBox = dataRow.querySelector('.col-md-6:first-child .bg-success-transparent');
            console.log('🔵 Down payment box found:', downPaymentBox ? 'YES' : 'NO');
            console.log('🔵 carData.down_payment:', carData.down_payment);
            
            if (downPaymentBox && carData.down_payment !== undefined) {
                // Try multiple selectors to find the strong element
                let downPaymentStrong = downPaymentBox.querySelector('strong.fs-16.text-success');
                if (!downPaymentStrong) {
                    downPaymentStrong = downPaymentBox.querySelector('strong.text-success');
                }
                if (!downPaymentStrong) {
                    downPaymentStrong = downPaymentBox.querySelector('strong');
                }
                
                console.log('🔵 Down payment strong found:', downPaymentStrong ? 'YES' : 'NO');
                if (downPaymentStrong) {
                    const oldText = downPaymentStrong.textContent;
                    const newText = formatPersianNumber(carData.down_payment) + ' تومان';
                    console.log('🔵 Down payment - Old:', oldText, 'New:', newText);
                    // Force complete replacement by clearing and setting
                    downPaymentStrong.textContent = '';
                    downPaymentStrong.textContent = newText;
                    console.log('✅ Down payment updated to:', downPaymentStrong.textContent);
                } else {
                    console.error('❌ Down payment strong element not found in:', downPaymentBox);
                    console.error('❌ Down payment box HTML:', downPaymentBox.innerHTML);
                }
            } else {
                console.error('❌ Down payment box or carData.down_payment missing:', {
                    downPaymentBox: !!downPaymentBox,
                    down_payment: carData.down_payment
                });
            }
            
            // Term Months (second col-md-6)
            const termMonthsBox = dataRow.querySelector('.col-md-6:nth-child(2) .bg-info-transparent');
            console.log('🔵 Term months box found:', termMonthsBox ? 'YES' : 'NO');
            console.log('🔵 carData.term_months:', carData.term_months);
            
            if (termMonthsBox && carData.term_months !== undefined) {
                let termMonthsStrong = termMonthsBox.querySelector('strong.fs-16.text-info');
                if (!termMonthsStrong) {
                    termMonthsStrong = termMonthsBox.querySelector('strong.text-info');
                }
                if (!termMonthsStrong) {
                    termMonthsStrong = termMonthsBox.querySelector('strong');
                }
                
                console.log('🔵 Term months strong found:', termMonthsStrong ? 'YES' : 'NO');
                if (termMonthsStrong) {
                    const oldText = termMonthsStrong.textContent;
                    const newText = carData.term_months.toString() + ' ماه';
                    console.log('🔵 Term months - Old:', oldText, 'New:', newText);
                    termMonthsStrong.textContent = '';
                    termMonthsStrong.textContent = newText;
                    console.log('✅ Term months updated to:', termMonthsStrong.textContent);
                } else {
                    console.error('❌ Term months strong element not found in:', termMonthsBox);
                    console.error('❌ Term months box HTML:', termMonthsBox.innerHTML);
                }
            } else {
                console.error('❌ Term months box or carData.term_months missing:', {
                    termMonthsBox: !!termMonthsBox,
                    term_months: carData.term_months
                });
            }
            
            // Installment Amount (col-md-12)
            const installmentBox = dataRow.querySelector('.col-md-12 .bg-primary-transparent');
            console.log('🔵 Installment box found:', installmentBox ? 'YES' : 'NO');
            console.log('🔵 carData.installment:', carData.installment);
            
            if (installmentBox && carData.installment !== undefined) {
                let installmentStrong = installmentBox.querySelector('strong.fs-18.text-primary');
                if (!installmentStrong) {
                    installmentStrong = installmentBox.querySelector('strong.text-primary');
                }
                if (!installmentStrong) {
                    installmentStrong = installmentBox.querySelector('strong');
                }
                
                console.log('🔵 Installment strong found:', installmentStrong ? 'YES' : 'NO');
                if (installmentStrong) {
                    const oldText = installmentStrong.textContent;
                    const newText = formatPersianNumber(carData.installment) + ' تومان';
                    console.log('🔵 Installment - Old:', oldText, 'New:', newText);
                    installmentStrong.textContent = '';
                    installmentStrong.textContent = newText;
                    console.log('✅ Installment updated to:', installmentStrong.textContent);
                } else {
                    console.error('❌ Installment strong element not found in:', installmentBox);
                    console.error('❌ Installment box HTML:', installmentBox.innerHTML);
                }
            } else {
                console.error('❌ Installment box or carData.installment missing:', {
                    installmentBox: !!installmentBox,
                    installment: carData.installment
                });
            }
            
            console.log('✓ updateCarInfoCards: Completed', carData);
        } finally {
            // Reset flag after a short delay
            setTimeout(() => {
                isUpdatingCards = false;
            }, 100);
        }
    }

    // Function to replace car with calculated data
    function replaceCarWithData(productId, downPayment, termMonths, installmentAmount, totalPrice) {
        // Debug: log incoming parameters
        console.log('🔵 replaceCarWithData called with:', {
            productId: productId,
            downPayment: downPayment,
            termMonths: termMonths,
            installmentAmount: installmentAmount,
            totalPrice: totalPrice
        });
        
        // Get localized texts
        const texts = (window.maneliInquiryForm && window.maneliInquiryForm.texts) ? window.maneliInquiryForm.texts : {};
        const successText = texts.car_replaced_success || 'Car replaced successfully. Page is being refreshed...';
        const errorPrefix = texts.error_replacing_car || 'Error replacing car';
        const serverError = texts.server_error || 'Server connection error. Please try again.';

        // Get AJAX URL and nonce
        let ajaxUrl = '';
        if (window.autopuzzle_ajax && window.autopuzzle_ajax.url) {
            ajaxUrl = window.autopuzzle_ajax.url;
        } else if (window.autopuzzle_ajax_object && window.autopuzzle_ajax_object.ajax_url) {
            ajaxUrl = window.autopuzzle_ajax_object.ajax_url;
        } else if (window.maneliInquiryForm && window.maneliInquiryForm.ajax_url) {
            ajaxUrl = window.maneliInquiryForm.ajax_url;
        } else {
            ajaxUrl = '/wp-admin/admin-ajax.php';
        }

        let ajaxNonce = '';
        if (window.maneliInquiryForm && window.maneliInquiryForm.nonces && window.maneliInquiryForm.nonces.select_car) {
            ajaxNonce = window.maneliInquiryForm.nonces.select_car;
        } else if (window.autopuzzle_ajax && window.autopuzzle_ajax.nonce) {
            ajaxNonce = window.autopuzzle_ajax.nonce;
        } else if (window.autopuzzle_ajax_object && window.autopuzzle_ajax_object.nonce) {
            ajaxNonce = window.autopuzzle_ajax_object.nonce;
        }

        // Call AJAX to replace car
        const params = new URLSearchParams();
        params.append('action', 'autopuzzle_select_car_ajax');
        params.append('nonce', ajaxNonce);
        params.append('product_id', productId);
        params.append('total_price', totalPrice);
        params.append('down_payment', downPayment);
        params.append('term_months', termMonths);

        console.log('🔵 Sending AJAX request with params:', {
            action: 'autopuzzle_select_car_ajax',
            product_id: productId,
            total_price: totalPrice,
            down_payment: downPayment,
            term_months: termMonths
        });

        fetch(ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: params.toString()
        }).then(r => r.json()).then(res => {
            console.log('🔵 AJAX Response received:', res);
            if (res && res.success) {
                // Update car data cards immediately before reload
                if (res.data && res.data.car_data) {
                    const carData = res.data.car_data;
                    console.log('🔵 Updating cards with server data:', carData);
                    console.log('🔵 Calling updateCarInfoCards...');
                    try {
                        updateCarInfoCards(carData);
                        console.log('🔵 updateCarInfoCards called successfully');
                        
                        // Wait a bit to ensure DOM is updated before reload
                        setTimeout(() => {
                            console.log('🔵 DOM update complete, showing success message...');
                            // Show success message and reload
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    title: successText,
                                    icon: 'success',
                                    confirmButtonColor: '#5e72e4',
                                    timer: 2000,
                                    timerProgressBar: true
                                }).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                alert(successText);
                                window.location.reload();
                            }
                        }, 300); // 300ms delay to ensure DOM updates are visible
                    } catch (err) {
                        console.error('❌ Error in updateCarInfoCards:', err);
                        // Even if update fails, show success and reload
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: successText,
                                icon: 'success',
                                confirmButtonColor: '#5e72e4',
                                timer: 2000,
                                timerProgressBar: true
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            alert(successText);
                            window.location.reload();
                        }
                    }
                } else {
                    console.warn('⚠ No car_data in response:', res.data);
                    console.warn('⚠ Full response:', res);
                    // Even without car_data, show success and reload (server has saved the data)
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: successText,
                            icon: 'success',
                            confirmButtonColor: '#5e72e4',
                            timer: 2000,
                            timerProgressBar: true
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        alert(successText);
                        window.location.reload();
                    }
                }
            } else {
                // Handle error
                console.error('❌ AJAX failed:', res);
                let errorMsg = serverError;
                if (res && res.data) {
                    if (res.data.message) {
                        errorMsg = res.data.message;
                    } else if (typeof res.data === 'string') {
                        errorMsg = res.data;
                    }
                }
                
                // Check for common error messages and translate them
                const invalidRequestText = texts.invalid_request || 'Invalid request. Please log in and try again.';
                if (errorMsg.includes('Invalid request') || errorMsg.includes('nonce') || errorMsg.includes('security') || errorMsg.includes('security token')) {
                    errorMsg = texts.invalid_request || invalidRequestText;
                } else if (errorMsg.includes('log in') || errorMsg.includes('Please log in')) {
                    errorMsg = texts.please_login || 'Please log in to continue.';
                } else if (errorMsg.includes('Product ID')) {
                    errorMsg = texts.product_id_required || 'Product ID is required.';
                }
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: errorPrefix,
                        text: errorMsg,
                        icon: 'error',
                        confirmButtonColor: '#dc3545'
                    });
                } else {
                    alert(errorPrefix + ': ' + errorMsg);
                }
            }
        }).catch(err => {
            console.error('Error replacing car:', err);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: errorPrefix,
                    text: serverError,
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
            } else {
                alert(serverError);
            }
        });
    }

    // --- 5. Meetings: load slots and book ---
    (function initMeetings(){
        const dateInput = document.getElementById('meeting_date');
        const slotsWrap = document.getElementById('meeting_slots');
        if (!dateInput || !slotsWrap) return;
        
        function fetchSlots(){
            if (!dateInput.value) {
                slotsWrap.innerHTML = '<div class="status-box status-pending"><p>لطفاً ابتدا تاریخ را انتخاب کنید</p></div>';
                return;
            }
            
            slotsWrap.innerHTML = '<div class="spinner is-active"></div>';
            
            const params = new URLSearchParams();
            params.append('action', 'autopuzzle_get_meeting_slots');
            params.append('nonce', (window.maneliInquiryForm && window.maneliInquiryForm.nonces && window.maneliInquiryForm.nonces.confirm_catalog) || '');
            params.append('date', dateInput.value);
            
            fetch((window.maneliInquiryForm && maneliInquiryForm.ajax_url) || '/wp-admin/admin-ajax.php', {
                method: 'POST', 
                headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'}, 
                body: params.toString()
            }).then(r=>r.json()).then(res=>{
                const errorMsg = (typeof autopuzzle_meetings !== 'undefined' && autopuzzle_meetings.text && autopuzzle_meetings.text.error_retrieving) 
                                 ? autopuzzle_meetings.text.error_retrieving 
                                 : 'خطا در دریافت اطلاعات';
                if (!res.success) { 
                    slotsWrap.innerHTML = '<div class="status-box status-error"><p>'+(res.data && res.data.message || errorMsg)+'</p></div>'; 
                    return; 
                }
                const slots = res.data.slots || [];
                if (slots.length === 0) {
                    slotsWrap.innerHTML = '<div class="status-box status-pending"><p>هیچ اسلات خالی برای این تاریخ وجود ندارد</p></div>';
                    return;
                }
                
                slotsWrap.innerHTML = slots.map(s=>{
                    const cls = s.available ? 'available' : 'busy';
                    const dis = s.available ? '' : 'disabled';
                    return '<button type="button" class="slot-btn '+cls+'" data-start="'+s.start+'" '+dis+'>'+s.time+'</button>';
                }).join('');
                
                slotsWrap.querySelectorAll('.slot-btn.available').forEach(btn=>{
                    btn.addEventListener('click', ()=>{
                        slotsWrap.querySelectorAll('.slot-btn').forEach(b=>b.classList.remove('selected'));
                        btn.classList.add('selected');
                        document.getElementById('meeting_start').value = btn.getAttribute('data-start');
                    });
                });
            }).catch(err => {
                const serverError = (typeof autopuzzle_meetings !== 'undefined' && autopuzzle_meetings.text && autopuzzle_meetings.text.server_error) 
                                    ? autopuzzle_meetings.text.server_error 
                                    : 'خطا در ارتباط با سرور';
                slotsWrap.innerHTML = '<div class="status-box status-error"><p>'+serverError+'</p></div>';
            });
        }
        
        dateInput.addEventListener('change', fetchSlots);
        if (dateInput.value) fetchSlots();

        const form = document.getElementById('meeting_form');
        if (form) {
            form.addEventListener('submit', function(e){
                e.preventDefault();
                const start = document.getElementById('meeting_start').value;
                if (!start) {
                    const selectTimeMsg = (typeof autopuzzle_meetings !== 'undefined' && autopuzzle_meetings.text && autopuzzle_meetings.text.select_time) 
                                          ? autopuzzle_meetings.text.select_time 
                                          : 'لطفاً یک زمان را انتخاب کنید';
                    alert(selectTimeMsg);
                    return;
                }
                
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                const bookingText = (typeof autopuzzle_meetings !== 'undefined' && autopuzzle_meetings.text && autopuzzle_meetings.text.booking) 
                                    ? autopuzzle_meetings.text.booking 
                                    : 'در حال رزرو...';
                submitBtn.textContent = bookingText;
                submitBtn.disabled = true;
                
                const params = new URLSearchParams();
                params.append('action', 'autopuzzle_book_meeting');
                params.append('nonce', (window.maneliInquiryForm && window.maneliInquiryForm.nonces && window.maneliInquiryForm.nonces.confirm_catalog) || '');
                params.append('start', start);
                params.append('inquiry_id', form.getAttribute('data-inquiry-id'));
                params.append('inquiry_type', form.getAttribute('data-inquiry-type'));
                
                fetch((window.maneliInquiryForm && maneliInquiryForm.ajax_url) || '/wp-admin/admin-ajax.php', {
                    method:'POST', 
                    headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'}, 
                    body: params.toString()
                }).then(r=>r.json()).then(res=>{
                    const successMsg = (typeof autopuzzle_meetings !== 'undefined' && autopuzzle_meetings.text && autopuzzle_meetings.text.success) 
                                       ? autopuzzle_meetings.text.success 
                                       : 'رزرو با موفقیت انجام شد';
                    const errorMsg = (typeof autopuzzle_meetings !== 'undefined' && autopuzzle_meetings.text && autopuzzle_meetings.text.error_booking) 
                                     ? autopuzzle_meetings.text.error_booking 
                                     : 'خطا در رزرو';
                    const serverError = (typeof autopuzzle_meetings !== 'undefined' && autopuzzle_meetings.text && autopuzzle_meetings.text.server_error) 
                                        ? autopuzzle_meetings.text.server_error 
                                        : 'خطا در ارتباط با سرور';
                    if (res.success) { 
                        form.reset(); 
                        slotsWrap.innerHTML=''; 
                        alert(successMsg);
                    } else { 
                        alert(res.data && res.data.message || errorMsg); 
                    }
                }).catch(err => {
                    alert(serverError);
                }).finally(() => {
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                });
            });
        }
    })();
});