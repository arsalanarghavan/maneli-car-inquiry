/**
 * Handles JavaScript functionality for the multi-step inquiry form,
 * including datepicker initialization and conditional form visibility.
 *
 * This script is enqueued by the Maneli_Inquiry_Form_Shortcode class.
 *
 * @version 1.0.0
 */
document.addEventListener('DOMContentLoaded', function() {
    
    // --- 1. Datepicker Initialization ---
    // Check if the kamadatepicker library is available.
    if (typeof kamadatepicker !== 'undefined') {
        
        // An array of datepicker fields to initialize.
        const datepickerFields = [
            'buyer_birth_date',
            'issuer_birth_date',
            'expert_buyer_birth_date', // For expert panel form
            'expert_issuer_birth_date' // For expert panel form
        ];

        datepickerFields.forEach(fieldId => {
            const fieldElement = document.getElementById(fieldId);
            if (fieldElement) {
                kamadatepicker(fieldId, {
                    bidi: true, // Enable bidirectional support for RTL
                    placeholder: 'مثال: ۱۳۶۵/۰۴/۱۵',
                    format: 'YYYY/MM/DD'
                });
            }
        });
    }

    // --- 2. Issuer Choice Form Logic (Step 2 of Inquiry Form) ---
    const identityForm = document.getElementById('identity-form');
    if (identityForm) {
        const radios = identityForm.querySelectorAll('input[name="issuer_type"]');
        const buyerForm = document.getElementById('buyer-form-wrapper');
        const issuerForm = document.getElementById('issuer-form-wrapper');

        /**
         * Toggles the visibility of the buyer and issuer form sections based on
         * the selected radio button.
         */
        function toggleForms() {
            const checkedRadio = identityForm.querySelector('input[name="issuer_type"]:checked');
            
            // If no radio is selected, hide both forms.
            if (!checkedRadio) {
                if (buyerForm) buyerForm.style.display = 'none';
                if (issuerForm) issuerForm.style.display = 'none';
                return;
            }

            const selectedValue = checkedRadio.value;
            
            // The buyer form is always shown once a choice is made.
            if (buyerForm) {
                buyerForm.style.display = 'block';
            }
            
            // The issuer form is only shown if the issuer is 'other'.
            if (issuerForm) {
                issuerForm.style.display = (selectedValue === 'other') ? 'block' : 'none';
            }
        }

        // Attach the change event listener to each radio button.
        radios.forEach(radio => radio.addEventListener('change', toggleForms));
        
        // Run the function once on page load to set the initial state.
        toggleForms();
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