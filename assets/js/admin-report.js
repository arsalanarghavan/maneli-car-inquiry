(function($) {
    'use strict';

    $(document).ready(function() {

        /**
         * Admin Action Buttons and Modal Logic
         */
        const adminActionForm = $('#admin-action-form');
        const finalStatusInput = $('#final-status-input');
        
        $('#approve-btn').on('click', function() {
            finalStatusInput.val('approved');
            adminActionForm.submit();
        });

        const modal = $('#rejection-modal');
        const rejectBtn = $('#reject-btn');
        const closeModalBtn = $('.modal-close');
        const confirmRejectionBtn = $('#confirm-rejection-btn');
        const reasonSelect = $('#rejection-reason-select');
        const customReasonWrapper = $('#custom-reason-wrapper');
        const customReasonText = $('#rejection-reason-custom');
        const finalReasonInput = $('#rejection-reason-input');
        
        rejectBtn.on('click', function() {
            modal.show();
        });

        closeModalBtn.on('click', function() {
            modal.hide();
        });
        
        $(window).on('click', function(e) {
            if ($(e.target).is(modal)) {
                modal.hide();
            }
        });

        reasonSelect.on('change', function() {
            if ($(this).val() === 'custom') {
                customReasonWrapper.show();
            } else {
                customReasonWrapper.hide();
            }
        });

        confirmRejectionBtn.on('click', function() {
            let reason = reasonSelect.val();
            if (reason === 'custom') {
                reason = customReasonText.val();
            }

            if (!reason) {
                alert('لطفاً یک دلیل برای رد درخواست انتخاب یا وارد کنید.');
                return;
            }

            finalReasonInput.val(reason);
            finalStatusInput.val('rejected');
            adminActionForm.submit();
        });
    });

})(jQuery);