/**
 * Handles the tab switching functionality for the frontend settings panel
 * rendered by the [maneli_settings] shortcode.
 *
 * This script is enqueued by the Maneli_Admin_Shortcodes class.
 *
 * @version 1.0.0
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Find the main container for the settings panel.
        const settingsContainer = $('.maneli-settings-container');
        
        // If the container doesn't exist on the page, do nothing.
        if (!settingsContainer.length) {
            return;
        }

        const tabLinks = settingsContainer.find('.maneli-tab-link');
        const tabPanes = settingsContainer.find('.maneli-tab-pane');

        /**
         * Handles the click event on a tab link.
         * It deactivates all tabs and panes, then activates the clicked one and its corresponding content pane.
         * @param {Event} e The click event object.
         */
        function switchTab(e) {
            e.preventDefault(); // Prevent the browser from following the anchor link.

            const clickedTab = $(this);
            const targetPaneSelector = clickedTab.attr('href');
            const targetPane = $(targetPaneSelector);

            // Ensure the target pane exists before proceeding.
            if (targetPane.length) {
                // Remove the 'active' class from all tab links and content panes.
                tabLinks.removeClass('active');
                tabPanes.removeClass('active');

                // Add the 'active' class to the clicked link and its target pane.
                clickedTab.addClass('active');
                targetPane.addClass('active');
            }
        }

        // Attach the click event handler to all tab links within the container.
        tabLinks.on('click', switchTab);
    });

})(jQuery);