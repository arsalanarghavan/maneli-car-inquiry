/**
 * Logging Tracker - Intercepts console logs, button clicks, and AJAX calls
 * برای ثبت لاگ‌های سیستم و کاربر
 */

(function() {
    'use strict';

    // Wait for jQuery
    function initLoggingTracker() {
        if (typeof jQuery === 'undefined') {
            setTimeout(initLoggingTracker, 50);
            return;
        }

        var $ = jQuery;
        var ajaxUrl = typeof maneliAjax !== 'undefined' ? maneliAjax.ajax_url : '/wp-admin/admin-ajax.php';
        var nonce = typeof maneliAjax !== 'undefined' ? maneliAjax.nonce : '';

        // Get logging settings from localized data
        var loggingSettings = typeof maneliLoggingSettings !== 'undefined' ? maneliLoggingSettings : {
            enable_logging_system: true,
            log_console_messages: true,
            enable_user_logging: true,
            log_button_clicks: true,
            log_form_submissions: true,
            log_ajax_calls: true,
            log_page_views: false
        };

        // Intercept console.log, console.error, console.warn
        var originalLog = console.log;
        var originalError = console.error;
        var originalWarn = console.warn;

        console.log = function() {
            originalLog.apply(console, arguments);
            if (loggingSettings.enable_logging_system && loggingSettings.log_console_messages) {
                logToSystem('console', 'info', Array.from(arguments).join(' '));
            }
        };

        console.error = function() {
            originalError.apply(console, arguments);
            if (loggingSettings.enable_logging_system && loggingSettings.log_console_messages) {
                logToSystem('error', 'error', Array.from(arguments).join(' '));
            }
        };

        console.warn = function() {
            originalWarn.apply(console, arguments);
            if (loggingSettings.enable_logging_system && loggingSettings.log_console_messages) {
                logToSystem('debug', 'warning', Array.from(arguments).join(' '));
            }
        };

        // Function to log to system
        function logToSystem(logType, severity, message, context) {
            if (!ajaxUrl) return;

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'maneli_log_console',
                    log_type: logType,
                    severity: severity,
                    message: message,
                    context: context ? JSON.stringify(context) : null,
                    security: nonce
                },
                error: function() {
                    // Silently fail - don't log logging errors
                }
            });
        }

        // Track button clicks
        $(document).on('click', 'button, .btn, a.btn, input[type="submit"], input[type="button"]', function(e) {
            if (!loggingSettings.enable_user_logging || !loggingSettings.log_button_clicks) {
                return;
            }
            
            var $btn = $(this);
            var buttonId = $btn.attr('id') || $btn.data('id') || '';
            var buttonText = $btn.text().trim() || $btn.attr('title') || $btn.attr('aria-label') || '';
            var buttonClass = $btn.attr('class') || '';
            var action = $btn.data('action') || '';
            
            // Skip if it's a navigation link or form button without important action
            if ($btn.is('a') && !$btn.hasClass('btn')) {
                return;
            }

            // Log user action
            logUserAction('button_click', 
                'Button clicked: ' + (buttonText || buttonId || buttonClass),
                null, null,
                {
                    button_id: buttonId,
                    button_text: buttonText,
                    button_class: buttonClass,
                    action: action,
                    page_url: window.location.href
                }
            );
        });

        // Track form submissions
        $(document).on('submit', 'form', function(e) {
            if (!loggingSettings.enable_user_logging || !loggingSettings.log_form_submissions) {
                return;
            }
            
            var $form = $(this);
            var formId = $form.attr('id') || '';
            var formAction = $form.attr('action') || '';
            var formMethod = $form.attr('method') || 'POST';

            logUserAction('form_submit',
                'Form submitted: ' + (formId || formAction),
                null, null,
                {
                    form_id: formId,
                    form_action: formAction,
                    form_method: formMethod,
                    page_url: window.location.href
                }
            );
        });

        // Track AJAX calls
        var originalAjax = $.ajax;
        $.ajax = function(options) {
            // Only track if it's our plugin's AJAX calls
            if (loggingSettings.enable_user_logging && loggingSettings.log_ajax_calls && 
                options.url && (options.url.indexOf('admin-ajax.php') !== -1 || options.url.indexOf('maneli') !== -1)) {
                var action = options.data && options.data.action ? options.data.action : '';
                
                logUserAction('ajax_call',
                    'AJAX call: ' + action,
                    null, null,
                    {
                        action: action,
                        url: options.url,
                        method: options.type || 'POST',
                        page_url: window.location.href
                    }
                );
            }

            // Call original AJAX
            return originalAjax.apply(this, arguments);
        };

        // Function to log user actions
        function logUserAction(actionType, description, targetType, targetId, metadata) {
            if (!ajaxUrl) return;

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'maneli_log_user_action',
                    action_type: actionType,
                    action_description: description,
                    target_type: targetType,
                    target_id: targetId,
                    metadata: metadata ? JSON.stringify(metadata) : null,
                    security: nonce
                },
                error: function() {
                    // Silently fail
                }
            });
        }

        // Track page views (for dashboard pages)
        if (loggingSettings.enable_user_logging && loggingSettings.log_page_views && 
            window.location.pathname.indexOf('/dashboard') !== -1) {
            var pageName = window.location.pathname.split('/').pop() || 'home';
            logUserAction('page_view',
                'Viewed page: ' + pageName,
                null, null,
                {
                    page: pageName,
                    full_url: window.location.href
                }
            );
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLoggingTracker);
    } else {
        initLoggingTracker();
    }
})();

