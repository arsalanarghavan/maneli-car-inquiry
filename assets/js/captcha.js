/**
 * CAPTCHA Integration JavaScript
 * 
 * Handles CAPTCHA token generation and widget rendering for:
 * - Google reCAPTCHA v2
 * - Google reCAPTCHA v3
 * - hCaptcha
 * 
 * @package Maneli_Car_Inquiry
 * @version 1.0.0
 */

(function($) {
    'use strict';

    let captchaConfig = {
        enabled: false,
        type: '',
        siteKey: '',
        widgetIds: {}
    };

    /**
     * Initialize CAPTCHA functionality
     */
    function initCaptcha() {
        // Get config from localized script data
        if (typeof maneliCaptchaConfig !== 'undefined') {
            captchaConfig = maneliCaptchaConfig;
        }

        if (!captchaConfig.enabled || !captchaConfig.type) {
            return;
        }

        // Initialize based on CAPTCHA type
        switch (captchaConfig.type) {
            case 'recaptcha_v2':
                initRecaptchaV2();
                break;
            case 'recaptcha_v3':
                initRecaptchaV3();
                break;
            case 'hcaptcha':
                initHCaptcha();
                break;
        }
    }

    /**
     * Initialize reCAPTCHA v2
     */
    function initRecaptchaV2() {
        if (typeof grecaptcha === 'undefined') {
            console.error('reCAPTCHA v2 script not loaded');
            return;
        }

        // Render widget in all containers (look for both .g-recaptcha and .maneli-recaptcha-v2-widget)
        $('.g-recaptcha:not([data-widget-id]), .maneli-recaptcha-v2-widget').each(function() {
            const $container = $(this);
            let containerId = $container.attr('id');
            
            // If no ID, create one
            if (!containerId) {
                containerId = 'maneli-recaptcha-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
                $container.attr('id', containerId);
            }

            // Skip if already rendered
            if ($container.attr('data-widget-id')) {
                return;
            }

            // Get site key from data attribute or config
            const siteKey = $container.data('sitekey') || captchaConfig.siteKey;
            
            if (!siteKey) {
                console.error('reCAPTCHA v2 site key not found');
                return;
            }

            try {
                const widgetId = grecaptcha.render(containerId, {
                    'sitekey': siteKey,
                    'callback': function(token) {
                        // Token is automatically submitted with form
                        $container.closest('form').data('captcha-verified', true);
                    },
                    'expired-callback': function() {
                        $container.closest('form').data('captcha-verified', false);
                    }
                });

                // Store widget ID
                $container.attr('data-widget-id', widgetId);
                captchaConfig.widgetIds[containerId] = widgetId;
            } catch (error) {
                console.error('reCAPTCHA v2 render error:', error);
            }
        });
    }

    /**
     * Initialize reCAPTCHA v3
     */
    function initRecaptchaV3() {
        if (typeof grecaptcha === 'undefined') {
            console.error('reCAPTCHA v3 script not loaded');
            return;
        }

        // reCAPTCHA v3 executes automatically, but we need to get token on form submit
        $(document).on('submit', 'form[data-captcha-required="true"]', function(e) {
            const $form = $(this);
            
            // Skip if already processing
            if ($form.data('captcha-processing')) {
                return;
            }

            $form.data('captcha-processing', true);

            grecaptcha.ready(function() {
                grecaptcha.execute(captchaConfig.siteKey, {action: 'submit'})
                    .then(function(token) {
                        // Add token to form data
                        $form.append('<input type="hidden" name="captcha_token" value="' + token + '">');
                        $form.data('captcha-verified', true);
                        $form.data('captcha-processing', false);
                        
                        // Re-submit form
                        if (!e.isDefaultPrevented()) {
                            $form[0].submit();
                        }
                    })
                    .catch(function(error) {
                        console.error('reCAPTCHA v3 error:', error);
                        $form.data('captcha-processing', false);
                        
                        // Show user-friendly error message
                        const errorMessage = (typeof maneliCaptchaConfig !== 'undefined' && maneliCaptchaConfig.strings && maneliCaptchaConfig.strings.verification_failed) 
                            ? maneliCaptchaConfig.strings.verification_failed 
                            : 'CAPTCHA verification failed. Please try again.';
                        
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: maneliCaptchaConfig?.strings?.error_title || 'Verification Failed',
                                text: errorMessage,
                                confirmButtonText: maneliCaptchaConfig?.strings?.try_again || 'Try Again',
                                confirmButtonColor: '#5e72e4'
                            });
                        } else {
                            alert(errorMessage);
                        }
                    });
            });

            // Prevent default form submission
            e.preventDefault();
            return false;
        });

        // Handle AJAX form submissions
        $(document).on('ajaxSend', function(e, xhr, settings) {
            if (settings.type === 'POST' && settings.data && settings.data.indexOf('action=') !== -1) {
                // Check if form requires CAPTCHA
                const $form = $('form[data-captcha-required="true"]');
                if ($form.length > 0 && !$form.data('captcha-token-generated')) {
                    e.preventDefault();
                    
                    grecaptcha.ready(function() {
                        grecaptcha.execute(captchaConfig.siteKey, {action: 'submit'})
                            .then(function(token) {
                                // Add token to AJAX data
                                if (typeof settings.data === 'string') {
                                    settings.data += '&captcha_token=' + encodeURIComponent(token);
                                } else {
                                    settings.data = settings.data || {};
                                    settings.data.captcha_token = token;
                                }
                                
                                $form.data('captcha-token-generated', true);
                                
                                // Re-send AJAX request
                                $.ajax(settings);
                            })
                            .catch(function(error) {
                                console.error('reCAPTCHA v3 error:', error);
                                
                                // Show user-friendly error message
                                const errorMessage = (typeof maneliCaptchaConfig !== 'undefined' && maneliCaptchaConfig.strings && maneliCaptchaConfig.strings.verification_failed) 
                                    ? maneliCaptchaConfig.strings.verification_failed 
                                    : 'CAPTCHA verification failed. Please try again.';
                                
                                if (typeof Swal !== 'undefined') {
                                    Swal.fire({
                                        icon: 'error',
                                        title: maneliCaptchaConfig?.strings?.error_title || 'Verification Failed',
                                        text: errorMessage,
                                        confirmButtonText: maneliCaptchaConfig?.strings?.try_again || 'Try Again',
                                        confirmButtonColor: '#5e72e4'
                                    });
                                } else {
                                    alert(errorMessage);
                                }
                            });
                    });
                }
            }
        });
    }

    /**
     * Initialize hCaptcha
     */
    function initHCaptcha() {
        if (typeof hcaptcha === 'undefined') {
            console.error('hCaptcha script not loaded');
            return;
        }

        // Render widget in all containers (look for both .h-captcha and .maneli-hcaptcha-widget)
        $('.h-captcha:not([data-widget-id]), .maneli-hcaptcha-widget').each(function() {
            const $container = $(this);
            let containerId = $container.attr('id');
            
            // If no ID, create one
            if (!containerId) {
                containerId = 'maneli-hcaptcha-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
                $container.attr('id', containerId);
            }

            // Skip if already rendered
            if ($container.attr('data-widget-id')) {
                return;
            }

            // Get site key from data attribute or config
            const siteKey = $container.data('sitekey') || captchaConfig.siteKey;
            
            if (!siteKey) {
                console.error('hCaptcha site key not found');
                return;
            }

            try {
                const widgetId = hcaptcha.render(containerId, {
                    'sitekey': siteKey,
                    'callback': function(token) {
                        // Token is automatically submitted with form
                        $container.closest('form').data('captcha-verified', true);
                    },
                    'expired-callback': function() {
                        $container.closest('form').data('captcha-verified', false);
                    }
                });

                // Store widget ID
                $container.attr('data-widget-id', widgetId);
                captchaConfig.widgetIds[containerId] = widgetId;
            } catch (error) {
                console.error('hCaptcha render error:', error);
            }
        });
    }

    /**
     * Get CAPTCHA token for AJAX submission
     */
    function getCaptchaToken() {
        if (!captchaConfig.enabled || !captchaConfig.type) {
            return Promise.resolve('');
        }

        switch (captchaConfig.type) {
            case 'recaptcha_v2':
                return getRecaptchaV2Token();
            case 'recaptcha_v3':
                return getRecaptchaV3Token();
            case 'hcaptcha':
                return getHCaptchaToken();
            default:
                return Promise.resolve('');
        }
    }

    /**
     * Get reCAPTCHA v2 token
     */
    function getRecaptchaV2Token() {
        return new Promise(function(resolve, reject) {
            if (typeof grecaptcha === 'undefined') {
                resolve('');
                return;
            }

            // Try to get token from any rendered widget
            const widgetIds = Object.values(captchaConfig.widgetIds);
            if (widgetIds.length > 0) {
                try {
                    // Try first widget
                    const token = grecaptcha.getResponse(widgetIds[0]);
                    if (token) {
                        resolve(token);
                        return;
                    }
                } catch (error) {
                    console.warn('reCAPTCHA v2 token error for widget:', error);
                }
            }

            // Try to get token from containers in current form
            const $form = $('form[data-captcha-required="true"]').first();
            if ($form.length > 0) {
                const $container = $form.find('.g-recaptcha[data-widget-id]').first();
                if ($container.length > 0) {
                    const widgetId = parseInt($container.attr('data-widget-id'), 10);
                    if (!isNaN(widgetId)) {
                        try {
                            const token = grecaptcha.getResponse(widgetId);
                            if (token) {
                                resolve(token);
                                return;
                            }
                        } catch (error) {
                            console.warn('reCAPTCHA v2 token error:', error);
                        }
                    }
                }
            }

            // No token found
            resolve('');
        });
    }

    /**
     * Get reCAPTCHA v3 token
     */
    function getRecaptchaV3Token() {
        return new Promise(function(resolve, reject) {
            if (typeof grecaptcha === 'undefined') {
                resolve('');
                return;
            }

            grecaptcha.ready(function() {
                grecaptcha.execute(captchaConfig.siteKey, {action: 'submit'})
                    .then(function(token) {
                        resolve(token);
                    })
                    .catch(function(error) {
                        console.error('reCAPTCHA v3 token error:', error);
                        resolve('');
                    });
            });
        });
    }

    /**
     * Get hCaptcha token
     */
    function getHCaptchaToken() {
        return new Promise(function(resolve, reject) {
            if (typeof hcaptcha === 'undefined') {
                resolve('');
                return;
            }

            // Try to get token from any rendered widget
            const widgetIds = Object.values(captchaConfig.widgetIds);
            if (widgetIds.length > 0) {
                try {
                    // Try first widget
                    const response = hcaptcha.getResponse(widgetIds[0]);
                    if (response) {
                        resolve(response);
                        return;
                    }
                } catch (error) {
                    console.warn('hCaptcha token error for widget:', error);
                }
            }

            // Try to get token from containers in current form
            const $form = $('form[data-captcha-required="true"]').first();
            if ($form.length > 0) {
                const $container = $form.find('.h-captcha[data-widget-id]').first();
                if ($container.length > 0) {
                    const widgetId = parseInt($container.attr('data-widget-id'), 10);
                    if (!isNaN(widgetId)) {
                        try {
                            const response = hcaptcha.getResponse(widgetId);
                            if (response) {
                                resolve(response);
                                return;
                            }
                        } catch (error) {
                            console.warn('hCaptcha token error:', error);
                        }
                    }
                }
            }

            // No token found
            resolve('');
        });
    }

    /**
     * Reset CAPTCHA widget
     */
    function resetCaptcha(containerId) {
        if (!captchaConfig.enabled || !captchaConfig.type) {
            return;
        }

        switch (captchaConfig.type) {
            case 'recaptcha_v2':
                if (captchaConfig.widgetIds[containerId] !== undefined && typeof grecaptcha !== 'undefined') {
                    grecaptcha.reset(captchaConfig.widgetIds[containerId]);
                }
                break;
            case 'hcaptcha':
                if (captchaConfig.widgetIds[containerId] !== undefined && typeof hcaptcha !== 'undefined') {
                    hcaptcha.reset(captchaConfig.widgetIds[containerId]);
                }
                break;
            case 'recaptcha_v3':
                // v3 doesn't need reset as it generates new tokens automatically
                break;
        }
    }

    // Make functions available globally
    window.maneliCaptcha = {
        getToken: getCaptchaToken,
        reset: resetCaptcha,
        init: initCaptcha
    };

    // Callbacks for CAPTCHA scripts
    window.maneliRecaptchaCallback = function() {
        if (captchaConfig.type === 'recaptcha_v2') {
            initRecaptchaV2();
        }
    };

    window.maneliHcaptchaCallback = function() {
        if (captchaConfig.type === 'hcaptcha') {
            initHCaptcha();
        }
    };

    /**
     * Retry initialization if external scripts are not yet loaded
     */
    function retryInit(maxRetries, retryDelay) {
        maxRetries = maxRetries || 5;
        retryDelay = retryDelay || 500;
        let retries = 0;

        function attemptInit() {
            if (retries >= maxRetries) {
                console.error('Maneli CAPTCHA: Failed to initialize after ' + maxRetries + ' attempts');
                return;
            }

            const type = captchaConfig.type;
            let scriptsLoaded = false;

            if (type === 'recaptcha_v2' || type === 'recaptcha_v3') {
                scriptsLoaded = typeof grecaptcha !== 'undefined';
            } else if (type === 'hcaptcha') {
                scriptsLoaded = typeof hcaptcha !== 'undefined';
            }

            if (scriptsLoaded) {
                initCaptcha();
            } else {
                retries++;
                setTimeout(attemptInit, retryDelay);
            }
        }

        attemptInit();
    }

    // Initialize on document ready
    $(document).ready(function() {
        // Get config from localized script data
        if (typeof maneliCaptchaConfig !== 'undefined') {
            captchaConfig = maneliCaptchaConfig;
        }

        // Only initialize if CAPTCHA is enabled
        if (!captchaConfig.enabled || !captchaConfig.type) {
            return;
        }

        // Try immediate initialization, then retry if needed
        const type = captchaConfig.type;
        let scriptsLoaded = false;

        if (type === 'recaptcha_v2' || type === 'recaptcha_v3') {
            scriptsLoaded = typeof grecaptcha !== 'undefined';
        } else if (type === 'hcaptcha') {
            scriptsLoaded = typeof hcaptcha !== 'undefined';
        }

        if (scriptsLoaded) {
            initCaptcha();
        } else {
            // Wait a bit for scripts to load, then retry
            setTimeout(function() {
                retryInit(5, 500);
            }, 100);
        }

        // Intercept AJAX form submissions to include CAPTCHA token
        $(document).ajaxSend(function(event, xhr, settings) {
            if (!captchaConfig.enabled || !captchaConfig.type) {
                return;
            }

            // Check if this AJAX request is for a form that requires CAPTCHA
            if (settings.data && typeof settings.data === 'string') {
                // Check for inquiry form actions
                const requiresCaptcha = (
                    settings.data.indexOf('maneli_create_customer_cash_inquiry') !== -1 ||
                    settings.data.indexOf('maneli_select_car_ajax') !== -1 ||
                    settings.data.indexOf('maneli_dashboard_login') !== -1
                );

                if (requiresCaptcha && settings.data.indexOf('captcha_token') === -1) {
                    // Get token and add to data
                    getCaptchaToken().then(function(token) {
                        if (token) {
                            settings.data += '&captcha_token=' + encodeURIComponent(token);
                        }
                    });
                }
            }
        });
    });

})(jQuery);

