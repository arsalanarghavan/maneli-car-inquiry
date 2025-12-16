<?php
/**
 * Unified Login/Registration template - All steps in one page
 * 
 * States:
 * - initial: Phone input + login method selection
 * - otp_sent: OTP input visible
 * - needs_password: Password creation form
 * - logged_in: Redirect to dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get logo from theme handler
$theme_handler = Autopuzzle_Frontend_Theme_Handler::instance();
$logo_url = $theme_handler->get_logo('desktop-white');

// Try WordPress custom logo first
$custom_logo_id = get_theme_mod('custom_logo');
if ($custom_logo_id) {
    $wp_logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
    if ($wp_logo_url) {
        $logo_url = $wp_logo_url;
    }
}

// Check session for any existing state
// Session is already started by render_login_page() method
$session_state = 'initial';
$phone_from_session = '';

// Check for existing password creation state (session is already started)
if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['autopuzzle_create_password_phone']) && !empty($_SESSION['autopuzzle_create_password_phone'])) {
    $session_state = 'needs_password';
    $phone_from_session = sanitize_text_field($_SESSION['autopuzzle_create_password_phone']);
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl" data-nav-layout="vertical" data-vertical-style="overlay" data-theme-mode="light" data-header-styles="light" data-menu-styles="light" data-toggled="close">
<head>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo esc_html(sprintf(__('Login - %s', 'autopuzzle'), autopuzzle_brand_name())); ?></title>
    <link rel="icon" href="<?php echo esc_url($theme_handler->get_favicon()); ?>" type="image/x-icon">
    
    <!-- Authentication Main JS -->
    <script src="<?php echo esc_url(AUTOPUZZLE_PLUGIN_URL); ?>assets/js/authentication-main.js"></script>
    
    <!-- Bootstrap CSS (RTL) -->
    <link id="style" href="<?php echo esc_url(AUTOPUZZLE_PLUGIN_URL); ?>assets/libs/bootstrap/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- Styles CSS -->
    <link href="<?php echo esc_url(AUTOPUZZLE_PLUGIN_URL); ?>assets/css/styles.css" rel="stylesheet">
    
    <!-- Icons CSS -->
    <link href="<?php echo esc_url(AUTOPUZZLE_PLUGIN_URL); ?>assets/css/icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo esc_url(AUTOPUZZLE_PLUGIN_URL); ?>assets/css/autopuzzle-custom.css" rel="stylesheet">
    
    <!-- Login RTL Fix CSS -->
    <link href="<?php echo esc_url(AUTOPUZZLE_PLUGIN_URL); ?>assets/css/login-rtl-fix.css" rel="stylesheet">
    
    <!-- SweetAlert2 CSS -->
    <?php
    $sweetalert2_css_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/libs/sweetalert2/sweetalert2.min.css';
    if (file_exists($sweetalert2_css_path)) {
        echo '<link href="' . esc_url(AUTOPUZZLE_PLUGIN_URL . 'assets/libs/sweetalert2/sweetalert2.min.css') . '" rel="stylesheet">';
    } else {
        echo '<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">';
    }
    ?>
    
    <style>
        .login-step {
            display: none !important;
        }
        .login-step.active {
            display: block !important;
        }
        .back-to-phone {
            cursor: pointer;
            color: #3085d6;
            text-decoration: none;
        }
        .back-to-phone:hover {
            text-decoration: underline;
        }
        /* Force visibility for active step content */
        #step-phone.active,
        #step-otp.active,
        #step-password.active {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        /* Ensure all form elements inside active step are visible */
        .login-step.active .form-control,
        .login-step.active .btn,
        .login-step.active .nav-pills,
        .login-step.active .tab-content,
        .login-step.active .tab-pane {
            display: block !important;
            visibility: visible !important;
        }
        
        .login-step.active .nav-pills {
            display: flex !important;
        }
        
        .login-step.active .d-grid {
            display: grid !important;
        }
    </style>
</head>

<body class="bg-white">
    <div class="row authentication authentication-cover-main mx-0">
        <div class="col-xxl-6 col-xl-7">
            <div class="row justify-content-center align-items-center h-100">
                <div class="col-xxl-7 col-xl-9 col-lg-6 col-md-6 col-sm-8 col-12">
                    <div class="card custom-card my-auto border">
                        <div class="card-body p-5">
                            <!-- Step 1: Phone Input & Login Method -->
                            <div class="login-step<?php echo ($session_state === 'initial' ? ' active' : ''); ?>" id="step-phone" data-state="initial">
                                <p class="h5 mb-2 text-center"><?php echo esc_html(__('Login to Dashboard', 'autopuzzle')); ?></p>
                                <p class="mb-4 text-muted op-7 fw-normal text-center"><?php echo esc_html(__('Welcome!', 'autopuzzle')); ?></p>
                                
                                <div class="text-center my-3 authentication-barrier">
                                    <span><?php echo esc_html(__('Select login method', 'autopuzzle')); ?></span>
                                </div>
                                
                                <!-- Login Type Tabs -->
                                <ul class="nav nav-pills nav-justified mb-3" id="loginTypeTab" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="otp-tab" data-bs-toggle="pill" data-bs-target="#otp-panel" type="button" role="tab">
                                            <?php echo esc_html(__('Login with SMS', 'autopuzzle')); ?>
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="password-tab" data-bs-toggle="pill" data-bs-target="#password-panel" type="button" role="tab">
                                            <?php echo esc_html(__('Login with password', 'autopuzzle')); ?>
                                        </button>
                                    </li>
                                </ul>
                                
                                <!-- Tab Content -->
                                <div class="tab-content" id="loginTypeTabContent">
                                    <!-- OTP Login Panel -->
                                    <div class="tab-pane fade show active" id="otp-panel" role="tabpanel">
                                        <div class="row gy-3">
                                            <div class="col-xl-12">
                                                <label for="otp-phone" class="form-label text-default"><?php echo esc_html(__('Mobile Number', 'autopuzzle')); ?><sup class="fs-12 text-danger">*</sup></label>
                                                <input type="tel" class="form-control" id="otp-phone" placeholder="<?php echo esc_attr(__('e.g., 09123456789', 'autopuzzle')); ?>" maxlength="11" value="<?php echo esc_attr($phone_from_session); ?>">
                                            </div>
                                        </div>
                                        
                                        <!-- CAPTCHA Widget (for v2 and hCaptcha) -->
                                        <?php if (class_exists('Autopuzzle_Captcha_Helper') && Autopuzzle_Captcha_Helper::is_enabled()): 
                                            $captcha_type = Autopuzzle_Captcha_Helper::get_captcha_type();
                                            $site_key = Autopuzzle_Captcha_Helper::get_site_key($captcha_type);
                                            if (!empty($captcha_type) && !empty($site_key)):
                                                if ($captcha_type === 'recaptcha_v2' || $captcha_type === 'hcaptcha'): ?>
                                                    <div class="mt-3 mb-3">
                                                        <?php echo Autopuzzle_Captcha_Helper::render_widget($captcha_type, $site_key, 'autopuzzle-captcha-widget-login-otp'); ?>
                                                    </div>
                                                <?php elseif ($captcha_type === 'recaptcha_v3'): ?>
                                                    <!-- reCAPTCHA v3 badge will be automatically displayed by Google -->
                                                    <div class="autopuzzle-recaptcha-v3-badge" style="display:none;"></div>
                                                <?php endif;
                                            endif;
                                        endif; ?>
                                        
                                        <div class="d-grid mt-4">
                                            <button id="send-otp-btn" class="btn btn-primary" onclick="autopuzzle_send_otp()"><?php echo esc_html(__('Send Code', 'autopuzzle')); ?></button>
                                        </div>
                                    </div>
                                    
                                    <!-- Password Login Panel -->
                                    <div class="tab-pane fade" id="password-panel" role="tabpanel">
                                        <div class="row gy-3">
                                            <div class="col-xl-12">
                                                <label for="password-phone" class="form-label text-default"><?php echo esc_html(__('Mobile Number', 'autopuzzle')); ?><sup class="fs-12 text-danger">*</sup></label>
                                                <input type="tel" class="form-control" id="password-phone" placeholder="<?php echo esc_attr(__('e.g., 09123456789', 'autopuzzle')); ?>" maxlength="11">
                                            </div>
                                            <div class="col-xl-12 mb-2">
                                                <label for="password-pass" class="form-label text-default"><?php echo esc_html(__('Password', 'autopuzzle')); ?><sup class="fs-12 text-danger">*</sup></label>
                                                <div class="position-relative">
                                                    <input type="password" class="form-control create-password-input" id="password-pass" placeholder="<?php echo esc_attr(__('Password', 'autopuzzle')); ?>">
                                                    <a href="javascript:void(0);" class="show-password-button text-muted" onclick="createpassword('password-pass',this)">
                                                        <i class="ri-eye-off-line align-middle"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- CAPTCHA Widget (for v2 and hCaptcha) -->
                                        <?php if (class_exists('Autopuzzle_Captcha_Helper') && Autopuzzle_Captcha_Helper::is_enabled()): 
                                            $captcha_type = Autopuzzle_Captcha_Helper::get_captcha_type();
                                            $site_key = Autopuzzle_Captcha_Helper::get_site_key($captcha_type);
                                            if (!empty($captcha_type) && !empty($site_key)):
                                                if ($captcha_type === 'recaptcha_v2' || $captcha_type === 'hcaptcha'): ?>
                                                    <div class="mt-3 mb-3">
                                                        <?php echo Autopuzzle_Captcha_Helper::render_widget($captcha_type, $site_key, 'autopuzzle-captcha-widget-login-password'); ?>
                                                    </div>
                                                <?php elseif ($captcha_type === 'recaptcha_v3'): ?>
                                                    <!-- reCAPTCHA v3 badge will be automatically displayed by Google -->
                                                    <div class="autopuzzle-recaptcha-v3-badge" style="display:none;"></div>
                                                <?php endif;
                                            endif;
                                        endif; ?>
                                        
                                        <div class="d-grid mt-4">
                                            <button id="password-login-btn" class="btn btn-primary" onclick="autopuzzle_password_login()"><?php echo esc_html(__('Login', 'autopuzzle')); ?></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Step 2: OTP Verification -->
                            <div class="login-step<?php echo ($session_state === 'otp_sent' ? ' active' : ''); ?>" id="step-otp" data-state="otp_sent">
                                <p class="h5 mb-2 text-center"><?php echo esc_html(__('Verification Code', 'autopuzzle')); ?></p>
                                <p class="mb-4 text-muted op-7 fw-normal text-center fs-12" id="otp-phone-display">
                                    <?php echo esc_html(__('Enter the 4-digit code sent to your mobile number.', 'autopuzzle')); ?>
                                </p>
                                <div class="row gy-3">
                                    <div class="col-xl-12">
                                        <label for="otp-code" class="form-label text-default"><?php echo esc_html(__('Verification code', 'autopuzzle')); ?><sup class="fs-12 text-danger">*</sup></label>
                                        <input type="text" class="form-control text-center" id="otp-code" placeholder="<?php echo esc_attr(__('Enter 4-digit code', 'autopuzzle')); ?>" maxlength="4">
                                        <div class="fs-12 mt-1" id="resend-countdown-container">
                                            <span id="resend-countdown"></span>
                                        </div>
                                    </div>
                                    
                                    <!-- CAPTCHA Widget (for v2 and hCaptcha) -->
                                    <?php if (class_exists('Autopuzzle_Captcha_Helper') && Autopuzzle_Captcha_Helper::is_enabled()): 
                                        $captcha_type = Autopuzzle_Captcha_Helper::get_captcha_type();
                                        $site_key = Autopuzzle_Captcha_Helper::get_site_key($captcha_type);
                                        if (!empty($captcha_type) && !empty($site_key)):
                                            if ($captcha_type === 'recaptcha_v2' || $captcha_type === 'hcaptcha'): ?>
                                                <div class="mt-3 mb-3">
                                                    <?php echo Autopuzzle_Captcha_Helper::render_widget($captcha_type, $site_key, 'autopuzzle-captcha-widget-otp-verify'); ?>
                                                </div>
                                            <?php elseif ($captcha_type === 'recaptcha_v3'): ?>
                                                <!-- reCAPTCHA v3 badge will be automatically displayed by Google -->
                                                <div class="autopuzzle-recaptcha-v3-badge" style="display:none;"></div>
                                            <?php endif;
                                        endif;
                                    endif; ?>
                                    
                                </div>
                                <div class="d-grid mt-4">
                                    <button id="verify-otp-btn" class="btn btn-primary" onclick="autopuzzle_verify_otp()"><?php echo esc_html(__('Verify and Continue', 'autopuzzle')); ?></button>
                                    <a href="javascript:void(0);" class="back-to-phone text-center mt-2" onclick="autopuzzle_go_back_to_phone()"><?php echo esc_html(__('← Back to phone number', 'autopuzzle')); ?></a>
                                </div>
                            </div>
                            
                            <!-- Step 3: Create Password -->
                            <div class="login-step<?php echo ($session_state === 'needs_password' ? ' active' : ''); ?>" id="step-password" data-state="needs_password">
                                <p class="h5 mb-2 text-center"><?php echo esc_html(__('Create Password', 'autopuzzle')); ?></p>
                                <p class="mb-4 text-muted fw-normal text-center fs-14"><?php echo esc_html(__('Hello! Please create a password for your account.', 'autopuzzle')); ?></p>
                                <div class="row gy-3">
                                    <div class="col-xl-12">
                                        <label class="form-label text-default" for="create-password"><?php echo esc_html(__('Password', 'autopuzzle')); ?><sup class="fs-12 text-danger">*</sup></label>
                                        <div class="position-relative">
                                            <input class="form-control create-password-input" id="create-password" placeholder="<?php echo esc_attr__('Password', 'autopuzzle'); ?>" type="password"> 
                                            <a class="show-password-button text-muted" href="javascript:void(0);" onclick="createpassword('create-password',this)">
                                                <i class="ri-eye-off-line align-middle"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="col-xl-12 mb-2">
                                        <label class="form-label text-default" for="create-confirmpassword"><?php echo esc_html(__('Confirm Password', 'autopuzzle')); ?><sup class="fs-12 text-danger">*</sup></label>
                                        <div class="position-relative">
                                            <input class="form-control create-password-input" id="create-confirmpassword" placeholder="<?php echo esc_attr__('Confirm Password', 'autopuzzle'); ?>" type="password"> 
                                            <a class="show-password-button text-muted" href="javascript:void(0);" onclick="createpassword('create-confirmpassword',this)">
                                                <i class="ri-eye-off-line align-middle"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-grid mt-4">
                                    <button id="save-password-btn" class="btn btn-primary" onclick="savePassword()"><?php echo esc_html(__('Save Password', 'autopuzzle')); ?></button>
                                </div>
                                <div class="text-center">
                                    <p class="text-muted mt-3 mb-0"><?php echo esc_html(__('Back to home?', 'autopuzzle')); ?> <a class="text-primary" href="<?php echo esc_url(home_url()); ?>"><?php echo esc_html(__('Click here', 'autopuzzle')); ?></a></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-6 col-xl-5 col-lg-12 d-xl-block d-none px-0">
            <div class="authentication-cover overflow-hidden">
                <div class="authentication-cover-logo">
                    <a href="<?php echo esc_url(home_url()); ?>">
                        <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" class="authentication-brand desktop-white" style="max-height: 80px; width: auto;">
                    </a>
                </div>
                <div class="aunthentication-cover-content d-flex align-items-center justify-content-center">
                    <div>
                        <h3 class="text-fixed-white mb-1 fw-medium"><?php echo esc_html(__('Welcome!', 'autopuzzle')); ?></h3>
                        <h6 class="text-fixed-white mb-3 fw-medium"><?php echo esc_html(__('Login to Dashboard', 'autopuzzle')); ?></h6>
                        <p class="text-fixed-white mb-1 op-6"><?php echo esc_html(__('Welcome to the management dashboard. Please log in to manage inquiries and monitor activities.', 'autopuzzle')); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="<?php echo esc_url(AUTOPUZZLE_PLUGIN_URL); ?>assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 JS -->
    <?php
    $sweetalert2_js_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/libs/sweetalert2/sweetalert2.min.js';
    if (file_exists($sweetalert2_js_path)) {
        echo '<script src="' . esc_url(AUTOPUZZLE_PLUGIN_URL . 'assets/libs/sweetalert2/sweetalert2.min.js') . '"></script>';
    } else {
        echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
    }
    ?>
    
    <!-- Show Password JS -->
    <script src="<?php echo esc_url(AUTOPUZZLE_PLUGIN_URL); ?>assets/js/show-password.js"></script>
    
    <!-- Unified Login JavaScript -->
    <script>
        // State management
        var autopuzzleLoginState = {
            currentStep: '<?php echo esc_js($session_state); ?>', // initial, otp_sent, needs_password
            currentPhone: '<?php echo esc_js($phone_from_session); ?>',
            
            setStep: function(stepElementId) {
                // stepElementId: 'step-phone', 'step-otp', 'step-password'
                this.currentStep = stepElementId.replace('step-', '');
                
                // Hide all steps
                document.querySelectorAll('.login-step').forEach(function(el) {
                    el.classList.remove('active');
                });
                
                // Show selected step
                var stepEl = document.getElementById(stepElementId);
                if (stepEl) {
                    stepEl.classList.add('active');
                }
            },
            
            showStep: function(stepName) {
                // stepName: 'phone', 'otp', 'password'
                var stepId = 'step-' + stepName;
                this.setStep(stepId);
            }
        };
        
        // Initialize state on page load - wait for DOM to be ready
        // CRITICAL: Only initialize if needed - don't override server-side active state
        function initializeLoginState() {
            // Check if any step is already active (from server-side rendering)
            var activeStep = document.querySelector('.login-step.active');
            
            if (activeStep) {
                // Server has already set the active step - use it
                var stepId = activeStep.id;
                var stepName = stepId.replace('step-', '');
                autopuzzleLoginState.currentStep = stepName;
                // Don't override server state - just return
                return;
            }
            
            // No active step found - this should not happen, but initialize based on state
            var currentState = autopuzzleLoginState.currentStep;
            

            if (currentState === 'needs_password') {
                autopuzzleLoginState.showStep('password');
            } else if (currentState === 'otp_sent') {
                autopuzzleLoginState.showStep('otp');
            } else {
                // Default: show phone step
                autopuzzleLoginState.showStep('phone');
            }
        }
        
        // Initialize when DOM is ready
        // Use window.onload to ensure all resources are loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                // Double check after a small delay
                setTimeout(function() {
                    var hasActive = document.querySelector('.login-step.active');
                    if (!hasActive) {
                        initializeLoginState();
                    }
                }, 100);
            });
        } else {
            // DOM already ready - check if active step exists
            var hasActive = document.querySelector('.login-step.active');
            if (!hasActive) {
                setTimeout(initializeLoginState, 100);
            }
        }
        
        // Define AJAX URL, nonce, and translations
        var autopuzzle_ajax = {
            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
            nonce: '<?php echo esc_js(wp_create_nonce('autopuzzle-ajax-nonce')); ?>',
            strings: {
                invalidMobile: '<?php echo esc_js(__('Please enter a valid mobile number.', 'autopuzzle')); ?>',
                sending: '<?php echo esc_js(__('Sending...', 'autopuzzle')); ?>',
                sendingCode: '<?php echo esc_js(__('Sending code...', 'autopuzzle')); ?>',
                resendCountdown: '<?php echo esc_js(__('Resend in %s seconds', 'autopuzzle')); ?>',
                resendCode: '<?php echo esc_js(__('Resend code', 'autopuzzle')); ?>',
                sendCode: '<?php echo esc_js(__('Send code', 'autopuzzle')); ?>',
                verifying: '<?php echo esc_js(__('Verifying...', 'autopuzzle')); ?>',
                verifyAndContinue: '<?php echo esc_js(__('Verify and Continue', 'autopuzzle')); ?>',
                invalidOtp: '<?php echo esc_js(__('Please enter the 4-digit code.', 'autopuzzle')); ?>',
                errorSendingCode: '<?php echo esc_js(__('Error sending code. Please try again.', 'autopuzzle')); ?>',
                errorVerifyingCode: '<?php echo esc_js(__('Error verifying code. Please try again.', 'autopuzzle')); ?>',
                fillAllFields: '<?php echo esc_js(__('Please fill all required fields.', 'autopuzzle')); ?>',
                loggingIn: '<?php echo esc_js(__('Logging in...', 'autopuzzle')); ?>',
                login: '<?php echo esc_js(__('Log In', 'autopuzzle')); ?>',
                errorLogin: '<?php echo esc_js(__('Login error. Please try again.', 'autopuzzle')); ?>',
                passwordRequired: '<?php echo esc_js(__('Please enter a password.', 'autopuzzle')); ?>',
                passwordsNotMatch: '<?php echo esc_js(__('Passwords do not match.', 'autopuzzle')); ?>',
                passwordTooShort: '<?php echo esc_js(__('Password must be at least 6 characters.', 'autopuzzle')); ?>',
                saving: '<?php echo esc_js(__('Saving...', 'autopuzzle')); ?>',
                savePassword: '<?php echo esc_js(__('Save Password', 'autopuzzle')); ?>',
                errorCreatingPassword: '<?php echo esc_js(__('An error occurred. Please try again.', 'autopuzzle')); ?>',
                success: '<?php echo esc_js(__('Success', 'autopuzzle')); ?>',
                error: '<?php echo esc_js(__('Error', 'autopuzzle')); ?>',
                ok: '<?php echo esc_js(__('OK', 'autopuzzle')); ?>'
            }
        };
        
        // Helper function to show SweetAlert
        function autopuzzle_show_alert(message, type) {
            type = type || 'info';
            
            if (typeof Swal !== 'undefined') {
                return Swal.fire({
                    icon: type,
                    title: type === 'success' ? autopuzzle_ajax.strings.success : (type === 'error' ? autopuzzle_ajax.strings.error : ''),
                    text: message,
                    confirmButtonText: autopuzzle_ajax.strings.ok,
                    confirmButtonColor: type === 'error' ? '#dc3545' : (type === 'success' ? '#28a745' : '#3085d6'),
                    customClass: {
                        popup: 'swal2-rtl'
                    },
                    html: false
                });
            } else {
                alert(message);
                return Promise.resolve();
            }
        }
        
        // Go back to phone input
        function autopuzzle_go_back_to_phone() {
            autopuzzleLoginState.showStep('phone');
            document.getElementById('otp-code').value = '';
        }
        
        // Send OTP
        function autopuzzle_send_otp() {
            var phone = document.getElementById('otp-phone').value.trim();
            
            if (!phone || phone.length != 11 || !/^09\d{9}$/.test(phone)) {
                autopuzzle_show_alert(autopuzzle_ajax.strings.invalidMobile, 'error');
                return;
            }
            
            var btn = document.getElementById('send-otp-btn');
            btn.disabled = true;
            btn.innerHTML = autopuzzle_ajax.strings.sendingCode;
            
            fetch(autopuzzle_ajax.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'autopuzzle_send_otp',
                    'nonce': autopuzzle_ajax.nonce,
                    'phone': phone
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Store phone
                    autopuzzleLoginState.currentPhone = phone;
                    
                    // Update OTP phone display
                    updateOtpPhoneDisplay(phone);
                    
                    // Show OTP step
                    autopuzzleLoginState.showStep('otp');
                    
                    // Show success message
                    var countdownEl = document.getElementById('resend-countdown');
                    countdownEl.textContent = data.data.message || autopuzzle_ajax.strings.sendingCode;
                    countdownEl.style.color = '#28a745';
                    countdownEl.style.fontWeight = '500';
                    
                    // Start countdown after 2 seconds
                    setTimeout(function() {
                        var countdown = 60;
                        var countdownInterval = setInterval(function() {
                            countdown--;
                            countdownEl.textContent = autopuzzle_ajax.strings.resendCountdown.replace('%s', countdown);
                            countdownEl.style.color = '';
                            countdownEl.style.fontWeight = '';
                            if (countdown <= 0) {
                                clearInterval(countdownInterval);
                                countdownEl.textContent = '';
                            }
                        }, 1000);
                    }, 2000);
                    
                    // Focus OTP input
                    document.getElementById('otp-code').focus();
                } else {
                    var errorMessage = data.data.message || autopuzzle_ajax.strings.errorSendingCode;
                    var countdownEl = document.getElementById('resend-countdown');
                    
                    const secondsText = <?php echo wp_json_encode(esc_html__('seconds', 'autopuzzle')); ?>;
                    const waitText = <?php echo wp_json_encode(esc_html__('wait', 'autopuzzle')); ?>;
                    if (errorMessage.includes(secondsText) || errorMessage.includes('seconds') || errorMessage.includes(waitText) || errorMessage.includes('wait')) {
                        countdownEl.textContent = errorMessage;
                        countdownEl.style.color = '#dc3545';
                        countdownEl.style.fontWeight = '500';
                        
                        var waitMatch = errorMessage.match(new RegExp('(\\d+)\\s*(' + secondsText + '|seconds|second)', 'i'));
                        if (waitMatch && waitMatch[1]) {
                            var waitTime = parseInt(waitMatch[1]);
                            var remaining = waitTime;
                            var waitInterval = setInterval(function() {
                                remaining--;
                                if (remaining > 0) {
                                    countdownEl.textContent = autopuzzle_ajax.strings.resendCountdown.replace('%s', remaining);
                                } else {
                                    clearInterval(waitInterval);
                                    countdownEl.textContent = '';
                                }
                            }, 1000);
                        }
                    } else {
                        autopuzzle_show_alert(errorMessage, 'error');
                    }
                    
                    btn.disabled = false;
                    btn.innerHTML = autopuzzle_ajax.strings.sendCode;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                autopuzzle_show_alert(autopuzzle_ajax.strings.errorSendingCode, 'error');
                btn.disabled = false;
                btn.innerHTML = autopuzzle_ajax.strings.sendCode;
            });
        }
        
        // Verify OTP
        function autopuzzle_verify_otp() {
            var phone = autopuzzleLoginState.currentPhone || document.getElementById('otp-phone').value.trim();
            var otp = document.getElementById('otp-code').value.trim();
            
            if (!otp || otp.length != 4) {
                autopuzzle_show_alert(autopuzzle_ajax.strings.invalidOtp, 'error');
                return;
            }
            
            var btn = document.getElementById('verify-otp-btn');
            btn.disabled = true;
            btn.innerHTML = autopuzzle_ajax.strings.verifying;
            
            fetch(autopuzzle_ajax.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'autopuzzle_verify_otp',
                    'nonce': autopuzzle_ajax.nonce,
                    'phone': phone,
                    'otp': otp
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.data.needs_password) {
                        // Show password creation step
                        autopuzzleLoginState.showStep('password');
                    } else {
                        // Login successful - redirect WITHOUT showing alert first
                        // Direct redirect prevents any session issues
                        window.location.href = data.data.redirect || '<?php echo esc_url(home_url('/dashboard')); ?>';
                    }
                } else {
                    autopuzzle_show_alert(data.data.message, 'error');
                    btn.disabled = false;
                    btn.innerHTML = autopuzzle_ajax.strings.verifyAndContinue;
                    document.getElementById('otp-code').value = '';
                    document.getElementById('otp-code').focus();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                autopuzzle_show_alert(autopuzzle_ajax.strings.errorVerifyingCode, 'error');
                btn.disabled = false;
                btn.innerHTML = autopuzzle_ajax.strings.verifyAndContinue;
            });
        }
        
        // Save Password
        function savePassword() {
            var password = document.getElementById('create-password').value;
            var confirmPassword = document.getElementById('create-confirmpassword').value;
            
            if (!password) {
                autopuzzle_show_alert(autopuzzle_ajax.strings.passwordRequired, 'error');
                return;
            }
            
            if (password.length < 6) {
                autopuzzle_show_alert(autopuzzle_ajax.strings.passwordTooShort, 'error');
                return;
            }
            
            if (password !== confirmPassword) {
                autopuzzle_show_alert(autopuzzle_ajax.strings.passwordsNotMatch, 'error');
                return;
            }
            
            var btn = document.getElementById('save-password-btn');
            btn.disabled = true;
            btn.innerHTML = autopuzzle_ajax.strings.saving;
            
            var phone = autopuzzleLoginState.currentPhone || '<?php echo esc_js($phone_from_session); ?>';
            
            fetch(autopuzzle_ajax.url, {
                method: 'POST',
                headers: {

                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'autopuzzle_create_password',
                    'nonce': autopuzzle_ajax.nonce,
                    'phone': phone,
                    'password': password
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Login successful - redirect WITHOUT showing alert first
                    window.location.href = data.data.redirect || '<?php echo esc_url(home_url('/dashboard')); ?>';
                } else {
                    autopuzzle_show_alert(data.data.message, 'error');
                    btn.disabled = false;
                    btn.innerHTML = autopuzzle_ajax.strings.savePassword;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                autopuzzle_show_alert(autopuzzle_ajax.strings.errorCreatingPassword, 'error');
                btn.disabled = false;
                btn.innerHTML = autopuzzle_ajax.strings.savePassword;
            });
        }
        
        // Password login
        function autopuzzle_password_login() {
            var phone = document.getElementById('password-phone').value.trim();
            var password = document.getElementById('password-pass').value;
            
            if (!phone || !password) {
                autopuzzle_show_alert(autopuzzle_ajax.strings.fillAllFields, 'error');
                return;
            }
            
            var btn = document.getElementById('password-login-btn');
            btn.disabled = true;
            btn.innerHTML = autopuzzle_ajax.strings.loggingIn;
            
            fetch(autopuzzle_ajax.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'autopuzzle_password_login',
                    'nonce': autopuzzle_ajax.nonce,
                    'phone': phone,
                    'password': password
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Login successful - redirect WITHOUT showing alert first
                    window.location.href = data.data.redirect || '<?php echo esc_url(home_url('/dashboard')); ?>';
                } else {
                    autopuzzle_show_alert(data.data.message, 'error');
                    btn.disabled = false;
                    btn.innerHTML = autopuzzle_ajax.strings.login;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                autopuzzle_show_alert(autopuzzle_ajax.strings.errorLogin, 'error');
                btn.disabled = false;
                btn.innerHTML = autopuzzle_ajax.strings.login;
            });
        }
        
        // Format phone inputs
        document.getElementById('otp-phone').addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').substring(0, 11);
        });
        
        document.getElementById('password-phone').addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').substring(0, 11);
        });
        
        // Format OTP input
        document.getElementById('otp-code').addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').substring(0, 4);
        });
        
        // Add translation for OTP phone display
        if (!autopuzzle_ajax.strings.codeSentTo) {
            autopuzzle_ajax.strings.codeSentTo = '<?php echo esc_js(__('Code sent to %s', 'autopuzzle')); ?>';
        }
        
        // Update OTP phone display helper
        function updateOtpPhoneDisplay(phone) {
            var maskedPhone = phone ? (phone.substring(0, 4) + '******' + phone.substring(9)) : '******';
            var displayText = '<?php echo esc_js(__('Enter the 4-digit code sent to mobile number %s.', 'autopuzzle')); ?>';
            document.getElementById('otp-phone-display').textContent = displayText.replace('%s', maskedPhone);
        }
    </script>
</body>
</html>

