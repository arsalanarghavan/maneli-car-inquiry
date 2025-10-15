/**
 * Handles JavaScript functionality for the admin-facing credit report page
 * (/wp-admin/admin.php?page=maneli-credit-report).
 *
 * This includes managing the modal for rejecting an inquiry.
 *
 * @version 1.0.0
 */
(function($) {
    'use strict';

    $(document).ready(function() {

        // --- 1. DOM Element Cache ---
        const adminActionForm = $('#admin-action-form');
        // If the main form doesn't exist on the page, do nothing.
        if (!adminActionForm.length) {
            return;
        }

        const finalStatusInput = $('#final-status-input');
        const finalReasonInput = $('#rejection-reason-input');
        
        const approveBtn = $('#approve-btn');
        const rejectBtn = $('#reject-btn');

        const modal = $('#rejection-modal');
        const closeModalBtn = modal.find('.modal-close');
        const confirmRejectionBtn = $('#confirm-rejection-btn');
        
        const reasonSelect = $('#rejection-reason-select');
        const customReasonWrapper = $('#custom-reason-wrapper');
        const customReasonText = $('#rejection-reason-custom');
        
        // توجه: maneliAdminReport توسط wp_localize_script در includes/class-credit-report-page.php تعریف می‌شود.

        // --- 2. Event Listeners ---

        /**
         * Handles the 'Approve' button click.
         * Sets the hidden status input to 'approved' and submits the form.
         */
        approveBtn.on('click', function() {
            finalStatusInput.val('approved');
            adminActionForm.submit();
        });

        /**
         * Handles the 'Reject' button click.
         * Displays the rejection reason modal.
         */
        rejectBtn.on('click', function() {
            modal.show();
        });

        /**
         * Handles the modal close button click.
         */
        closeModalBtn.on('click', function() {
            modal.hide();
        });
        
        /**
         * Closes the modal if the user clicks on the background overlay.
         */
        $(window).on('click', function(e) {
            if ($(e.target).is(modal)) {
                modal.hide();
            }
        });

        /**
         * Shows or hides the custom reason textarea based on the dropdown selection.
         */
        reasonSelect.on('change', function() {
            customReasonWrapper.toggle($(this).val() === 'custom');
        });

        /**
         * Handles the final confirmation of the rejection within the modal.
         * It gathers the reason, sets the hidden status input to 'rejected', and submits the main form.
         */
        confirmRejectionBtn.on('click', function() {
            let reason = reasonSelect.val();
            if (reason === 'custom') {
                reason = customReasonText.val();
            }

            // Basic validation
            if (!reason) {
                // FIX: استفاده از رشته محلی‌سازی شده برای اعتبارسنجی
                if (typeof maneliAdminReport !== 'undefined' && maneliAdminReport.text.rejection_reason_required) {
                    alert(maneliAdminReport.text.rejection_reason_required);
                } else {
                    // Fallback اگر محلی‌سازی به درستی کار نکرد
                    alert('لطفاً یک دلیل برای رد درخواست انتخاب یا وارد کنید.');
                }
                return;
            }

            finalReasonInput.val(reason);
            finalStatusInput.val('rejected');
            adminActionForm.submit();
        });
    });

})(jQuery);