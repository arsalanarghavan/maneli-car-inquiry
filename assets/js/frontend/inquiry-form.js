/**
 * Handles JavaScript functionality for the multi-step inquiry form,
 * including datepicker initialization and conditional form visibility.
 *
 * This script is now centralized and handles Datepicker initialization for:
 * 1. Customer Inquiry Form (step-2-identity-form.php)
 * 2. Expert New Inquiry Form (expert-new-inquiry-form.php)
 * 3. User Edit Forms (form-edit-user.php and admin user profile)
 *
 * @version 1.0.2 (Localized Datepicker placeholder)
 */
document.addEventListener('DOMContentLoaded', function() {

    /**
     * Dynamically retrieves the datepicker placeholder text from available localization objects.
     *
     * این تابع فرض می‌کند که رشته‌ی محلی‌سازی شده با کلید 'datepicker_placeholder' 
     * در آبجکت maneli_expert_ajax.text قرار داده شده است.
     *
     * @return {string} The localized placeholder text or a default.
     */
    function getDatepickerPlaceholder() {
        if (typeof maneli_expert_ajax !== 'undefined' && maneli_expert_ajax.text && maneli_expert_ajax.text.datepicker_placeholder) {
            return maneli_expert_ajax.text.datepicker_placeholder;
        }
        // Fallback to a generic, non-hardcoded value to maintain the datepicker's function.
        return 'YYYY/MM/DD'; 
    }

    const datepickerPlaceholder = getDatepickerPlaceholder();

    // --- 1. Datepicker Initialization ---
    // Check if the kamadatepicker library is available.
    if (typeof kamadatepicker !== 'undefined') {
        
        // Target all individual IDs used in various forms (customer/expert) 
        // AND the class used in admin profile/user edit forms.
        const datepickerSelectors = [
            '#buyer_birth_date',
            '#issuer_birth_date',
            '#expert_buyer_birth_date',
            '#expert_issuer_birth_date',
            // Target used in form-edit-user.php and class-user-profile.php
            '#birth_date', 
            // Target used generally in class-user-profile.php
            '.maneli-datepicker'
        ];

        datepickerSelectors.forEach(selector => {
            document.querySelectorAll(selector).forEach(element => {
                // Ensure the element hasn't been initialized by another selector (e.g. if it has an ID and the class)
                if (element.hasAttribute('data-kdp-init')) {
                    return;
                }
                
                kamadatepicker(element.id || element, {
                    bidi: true, // Enable bidirectional support for RTL
                    placeholder: datepickerPlaceholder, // FIX: Use localized string
                    format: 'YYYY/MM/DD'
                });
                element.setAttribute('data-kdp-init', 'true'); // Mark as initialized
            });
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
});