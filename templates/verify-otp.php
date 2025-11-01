<?php
/**
 * OTP Verification template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check if already logged in
$session = new Maneli_Session();
if ($session->is_logged_in()) {
    wp_redirect(home_url('/dashboard'));
    exit;
}

// Get logo
$theme_handler = Maneli_Frontend_Theme_Handler::instance();
$logo_url = $theme_handler->get_logo('desktop-white');
$custom_logo_id = get_theme_mod('custom_logo');
if ($custom_logo_id) {
    $wp_logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
    if ($wp_logo_url) {
        $logo_url = $wp_logo_url;
    }
}

// Get phone from session (secure)
// Session already started by dashboard handler
$phone = '';
if (isset($_SESSION) && isset($_SESSION['maneli_sms_phone'])) {
    $phone = sanitize_text_field($_SESSION['maneli_sms_phone']);
}
?>
<!DOCTYPE html>
<html lang="fa" data-nav-layout="vertical" data-vertical-style="overlay" data-theme-mode="light" data-header-styles="light" data-menu-styles="light" data-toggled="close">
<head>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo esc_html(__('Verify Code - Maneli Car Inquiry', 'maneli-car-inquiry')); ?></title>
    <link rel="icon" href="<?php echo $theme_handler->get_favicon(); ?>" type="image/x-icon">
    
    <!-- Authentication Main JS -->
    <script src="<?php echo MANELI_PLUGIN_URL; ?>assets/js/authentication-main.js"></script>
    
    <!-- Bootstrap CSS -->
    <link id="style" href="<?php echo MANELI_PLUGIN_URL; ?>assets/libs/bootstrap/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- Styles CSS -->
    <link href="<?php echo MANELI_PLUGIN_URL; ?>assets/css/styles.css" rel="stylesheet">
    
    <!-- Icons CSS -->
    <link href="<?php echo MANELI_PLUGIN_URL; ?>assets/css/icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo MANELI_PLUGIN_URL; ?>assets/css/maneli-custom.css" rel="stylesheet">
    
    <!-- Two Step Verification JS -->
    <script src="<?php echo MANELI_PLUGIN_URL; ?>assets/js/two-step-verification.js"></script>
</head>

<body class="bg-white">
    <div class="row authentication two-step-verification authentication-cover-main mx-0">
        <div class="col-xxl-6 col-xl-7">
            <div class="row justify-content-center align-items-center h-100">
                <div class="col-xxl-6 col-xl-9 col-lg-6 col-md-6 col-sm-8 col-12">
                    <div class="card custom-card my-auto border">
                        <div class="card-body p-4 p-sm-5">
                            <p class="h5 mb-2 text-center"><?php echo esc_html(__('Verification Code', 'maneli-car-inquiry')); ?></p>
                            <p class="mb-4 text-muted op-7 fw-normal text-center fs-12">
                                <?php 
                                $masked_phone = $phone ? substr($phone, 0, 4) . '******' . substr($phone, -2) : '******';
                                echo sprintf(esc_html__('Enter the 4-digit code sent to mobile number %s.', 'maneli-car-inquiry'), $masked_phone); 
                                ?>
                            </p>
                            <div class="row gy-3">
                                <div class="col-xl-12 mb-2">
                                    <div class="row">
                                        <div class="col-3">
                                            <input type="text" class="form-control text-center" id="one" maxlength="1" onkeyup="clickEvent(this,'two')" autocomplete="off">
                                        </div>
                                        <div class="col-3">
                                            <input type="text" class="form-control text-center" id="two" maxlength="1" onkeyup="clickEvent(this,'three')" autocomplete="off">
                                        </div>
                                        <div class="col-3">
                                            <input type="text" class="form-control text-center" id="three" maxlength="1" onkeyup="clickEvent(this,'four')" autocomplete="off">
                                        </div>
                                        <div class="col-3">
                                            <input type="text" class="form-control text-center" id="four" maxlength="1" onkeyup="verifyOtpOnComplete()" autocomplete="off">
                                        </div>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" value="" id="resend-check">
                                        <label class="form-check-label fs-14" for="resend-check">
                                            <?php echo esc_html(__("Didn't receive code?", 'maneli-car-inquiry')); ?>
                                            <a href="javascript:void(0);" class="text-primary ms-2 d-inline-block" id="resend-link"><?php echo esc_html(__('Resend', 'maneli-car-inquiry')); ?></a>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-xl-12 d-grid mt-2">
                                    <button id="verify-btn" class="btn btn-primary" onclick="verifyOtpCode()"><?php echo esc_html(__('Verify', 'maneli-car-inquiry')); ?></button>
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
                    <a href="<?php echo home_url(); ?>">
                        <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" class="authentication-brand desktop-white" style="max-height: 80px; width: auto;">
                    </a>
                </div>
                <div class="aunthentication-cover-content d-flex align-items-center justify-content-center">
                    <div>
                        <h3 class="text-fixed-white mb-1 fw-medium"><?php echo esc_html(__('Welcome!', 'maneli-car-inquiry')); ?></h3>
                        <h6 class="text-fixed-white mb-3 fw-medium"><?php echo esc_html(__('Two-step Verification', 'maneli-car-inquiry')); ?></h6>
                        <p class="text-fixed-white mb-1 op-6"><?php echo esc_html(__('Please enter the verification code sent to your mobile number to continue.', 'maneli-car-inquiry')); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="<?php echo MANELI_PLUGIN_URL; ?>assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <!-- Two Step Verification JS -->
    <script>
        var maneli_ajax = {
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce: '<?php echo wp_create_nonce('maneli-ajax-nonce'); ?>',
            phone: '<?php echo esc_js($phone); ?>',
            strings: {
                verifying: '<?php echo esc_js(__('Verifying...', 'maneli-car-inquiry')); ?>',
                verify: '<?php echo esc_js(__('Verify', 'maneli-car-inquiry')); ?>',
                invalidCode: '<?php echo esc_js(__('Please enter the complete 4-digit code.', 'maneli-car-inquiry')); ?>',
                resending: '<?php echo esc_js(__('Sending...', 'maneli-car-inquiry')); ?>',
                resend: '<?php echo esc_js(__('Resend', 'maneli-car-inquiry')); ?>'
            }
        };
        
        function clickEvent(first, last) {
            if (first.value.length) {
                document.getElementById(last).focus();
            }
        }
        
        function verifyOtpOnComplete() {
            var code = document.getElementById('one').value + 
                       document.getElementById('two').value + 
                       document.getElementById('three').value + 
                       document.getElementById('four').value;
            if (code.length === 4) {
                verifyOtpCode();
            }
        }
        
        function getOtpCode() {
            return document.getElementById('one').value + 
                   document.getElementById('two').value + 
                   document.getElementById('three').value + 
                   document.getElementById('four').value;
        }
        
        function verifyOtpCode() {
            var code = getOtpCode();
            
            if (code.length !== 4) {
                alert(maneli_ajax.strings.invalidCode);
                return;
            }
            
            var btn = document.getElementById('verify-btn');
            btn.disabled = true;
            btn.innerHTML = maneli_ajax.strings.verifying;
            
            fetch(maneli_ajax.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'maneli_verify_otp',
                    'nonce': maneli_ajax.nonce,
                    'phone': maneli_ajax.phone,
                    'otp': code
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.data.needs_password) {
                        window.location.href = data.data.redirect;
                    } else {
                        window.location.href = data.data.redirect;
                    }
                } else {
                    alert(data.data.message);
                    btn.disabled = false;
                    btn.innerHTML = maneli_ajax.strings.verify;
                    // Clear inputs
                    document.getElementById('one').value = '';
                    document.getElementById('two').value = '';
                    document.getElementById('three').value = '';
                    document.getElementById('four').value = '';
                    document.getElementById('one').focus();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('<?php echo esc_js(__('An error occurred. Please try again.', 'maneli-car-inquiry')); ?>');
                btn.disabled = false;
                btn.innerHTML = maneli_ajax.strings.verify;
            });
        }
        
        // Resend code
        document.getElementById('resend-link').addEventListener('click', function(e) {
            e.preventDefault();
            
            var link = this;
            link.style.pointerEvents = 'none';
            link.textContent = maneli_ajax.strings.resending;
            
            fetch(maneli_ajax.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'maneli_send_otp',
                    'nonce': maneli_ajax.nonce,
                    'phone': maneli_ajax.phone
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.data.message);
                    // Clear inputs
                    document.getElementById('one').value = '';
                    document.getElementById('two').value = '';
                    document.getElementById('three').value = '';
                    document.getElementById('four').value = '';
                    document.getElementById('one').focus();
                } else {
                    alert(data.data.message);
                }
                link.style.pointerEvents = 'auto';
                link.textContent = maneli_ajax.strings.resend;
            })
            .catch(error => {
                console.error('Error:', error);
                alert('<?php echo esc_js(__('An error occurred. Please try again.', 'maneli-car-inquiry')); ?>');
                link.style.pointerEvents = 'auto';
                link.textContent = maneli_ajax.strings.resend;
            });
        });
        
        // Auto focus first input
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('one').focus();
        });
    </script>
</body>
</html>

