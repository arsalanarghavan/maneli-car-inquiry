/**
 * Handles all JavaScript logic for the admin-side Product Editor page.
 * (/wp-admin/edit.php?post_type=product&page=maneli-product-editor)
 *
 * This includes live price formatting and auto-saving data changes via AJAX.
 *
 * @version 1.0.0
 */
jQuery(document).ready(function($) {
    'use strict';

    // Data is passed from PHP via wp_localize_script as `maneliAdminProductEditor`

    /**
     * Helper function to format a number with thousand separators.
     * It removes non-digit characters and then adds commas.
     * @param {string|number} n The number or string to format.
     * @returns {string} The formatted number string.
     */
    function formatNumber(n) {
        let numStr = String(n).replace(/[^0-9]/g, '');
        return numStr.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    // --- INITIALIZATION ---

    // Apply initial formatting to all price inputs on page load.
    $('.manli-price-input').each(function() {
        const initialValue = $(this).val();
        if (initialValue) {
            $(this).val(formatNumber(initialValue));
        }
    });

    // --- EVENT HANDLERS ---

    // Live formatting for price inputs as the user types.
    $('.manli-price-input').on('keyup', function() {
        const formattedValue = formatNumber($(this).val());
        $(this).val(formattedValue);
    });

    // Handle the 'change' event on any of the data input fields (text, select).
    // This triggers the auto-save functionality.
    $('.manli-data-input').on('change', function() {
        const inputField = $(this);
        const productId = inputField.data('product-id');
        const fieldType = inputField.data('field-type');
        let fieldValue = inputField.val();
        
        // Remove commas from price fields before sending the value to the server.
        if (inputField.hasClass('manli-price-input')) {
            fieldValue = fieldValue.replace(/,/g, '');
        }

        const spinner = inputField.closest('td').find('.spinner');

        // Provide visual feedback that an action is in progress.
        spinner.addClass('is-active');
        inputField.css('border-color', '#007cba'); // Blue border for "in progress"

        const data = {
            action: 'maneli_update_product_data',
            nonce: maneliAdminProductEditor.nonce, // Nonce passed from PHP
            product_id: productId,
            field_type: fieldType,
            field_value: fieldValue
        };

        // Send the AJAX request to the server.
        $.post(maneliAdminProductEditor.ajax_url, data, function(response) {
            spinner.removeClass('is-active');
            if (response.success) {
                // Green border for success
                inputField.css('border-color', '#46b450');
            } else {
                // Red border for error
                inputField.css('border-color', '#dc3232');
                console.error(maneliAdminProductEditor.text.error, response.data);
                // Optionally, show an alert to the user
                // alert(maneliAdminProductEditor.text.error + ' ' + response.data.message);
            }
        }).fail(function() {
            spinner.removeClass('is-active');
            inputField.css('border-color', '#dc3232'); // Red border for AJAX failure
            console.error('An AJAX error occurred.');
        }).always(function() {
            // Reset the border color after a short delay to remove the feedback indicator.
            setTimeout(() => inputField.css('border-color', ''), 2000);
        });
    });
});