<?php
/**
 * Create Password template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get phone from session (secure)
// Session already started by dashboard handler
$phone = '';
if (isset($_SESSION) && isset($_SESSION['autopuzzle_create_password_phone'])) {
    $phone = sanitize_text_field($_SESSION['autopuzzle_create_password_phone']);
}

if (empty($phone)) {
    wp_redirect(home_url('/login'));
    exit;
}

// Get logo
$theme_handler = Autopuzzle_Frontend_Theme_Handler::instance();
$logo_url = $theme_handler->get_logo('desktop-white');
$custom_logo_id = get_theme_mod('custom_logo');
if ($custom_logo_id) {
    $wp_logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
    if ($wp_logo_url) {
        $logo_url = $wp_logo_url;
    }
}
?>
<!DOCTYPE html>
<html lang="fa" data-nav-layout="vertical" data-vertical-style="overlay" data-theme-mode="light" data-header-styles="light" data-menu-styles="light" data-toggled="close">
<head>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo esc_html( sprintf( __('Create Password - %s', 'autopuzzle'), autopuzzle_brand_name() ) ); ?></title>
    <link rel="icon" href="<?php echo esc_url( $theme_handler->get_favicon() ); ?>" type="image/x-icon">
    
    <!-- Authentication Main JS -->
    <script src="<?php echo AUTOPUZZLE_PLUGIN_URL; ?>assets/js/authentication-main.js"></script>
    
    <!-- Bootstrap CSS -->
    <link id="style" href="<?php echo AUTOPUZZLE_PLUGIN_URL; ?>assets/libs/bootstrap/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- Styles CSS -->
    <link href="<?php echo AUTOPUZZLE_PLUGIN_URL; ?>assets/css/styles.css" rel="stylesheet">
    
    <!-- Icons CSS -->
    <link href="<?php echo AUTOPUZZLE_PLUGIN_URL; ?>assets/css/icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo AUTOPUZZLE_PLUGIN_URL; ?>assets/css/autopuzzle-custom.css" rel="stylesheet">
</head>

<body class="bg-white">
    <div class="row authentication authentication-cover-main mx-0">
        <div class="col-xxl-6 col-xl-7">
            <div class="row justify-content-center align-items-center h-100">
                <div class="col-xxl-6 col-xl-9 col-lg-6 col-md-6 col-sm-8 col-12">
                    <div class="card custom-card my-auto border">
                        <div class="card-body p-5">
                            <p class="h5 mb-2 text-center"><?php echo esc_html(__('Create Password', 'autopuzzle')); ?></p>
                            <p class="mb-4 text-muted fw-normal text-center fs-14"><?php echo esc_html(__('Hello! Please create a password for your account.', 'autopuzzle')); ?></p>
                            <div class="row gy-3">
                                <div class="col-xl-12">
                                    <label class="form-label text-default" for="create-password"><?php echo esc_html(__('Password', 'autopuzzle')); ?><sup class="fs-12 text-danger">*</sup></label>
                                    <div class="position-relative">
                                        <input class="form-control create-password-input" id="create-password" placeholder="<?php echo esc_attr(__('Password', 'autopuzzle')); ?>" type="password"> 
                                        <a class="show-password-button text-muted" href="javascript:void(0);" onclick="createpassword('create-password',this)">
                                            <i class="ri-eye-off-line align-middle"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="col-xl-12 mb-2">
                                    <label class="form-label text-default" for="create-confirmpassword"><?php echo esc_html(__('Confirm Password', 'autopuzzle')); ?><sup class="fs-12 text-danger">*</sup></label>
                                    <div class="position-relative">
                                        <input class="form-control create-password-input" id="create-confirmpassword" placeholder="<?php echo esc_attr(__('Confirm Password', 'autopuzzle')); ?>" type="password"> 
                                        <a class="show-password-button text-muted" href="javascript:void(0);" onclick="createpassword('create-confirmpassword',this)">
                                            <i class="ri-eye-off-line align-middle"></i>
                                        </a>
                                    </div>
                                    <div class="mt-2">
                                        <div class="form-check">
                                            <input class="form-check-input" id="remember-password" type="checkbox" value=""> 
                                            <label class="form-check-label text-muted fw-normal" for="remember-password"><?php echo esc_html(__('Remember password?', 'autopuzzle')); ?></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-grid mt-4">
                                <button id="save-password-btn" class="btn btn-primary" onclick="savePassword()"><?php echo esc_html(__('Save Password', 'autopuzzle')); ?></button>
                            </div>
                            <div class="text-center">
                                <p class="text-muted mt-3 mb-0"><?php echo esc_html(__('Back to home?', 'autopuzzle')); ?> <a class="text-primary" href="<?php echo home_url(); ?>"><?php echo esc_html(__('Click here', 'autopuzzle')); ?></a></p>
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
                        <h3 class="text-fixed-white mb-1 fw-medium"><?php echo esc_html(__('Welcome!', 'autopuzzle')); ?></h3>
                        <h6 class="text-fixed-white mb-3 fw-medium"><?php echo esc_html(__('Create Your Account', 'autopuzzle')); ?></h6>
                        <p class="text-fixed-white mb-1 op-6"><?php echo esc_html(__('Please create a secure password for your account to protect your information.', 'autopuzzle')); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="<?php echo AUTOPUZZLE_PLUGIN_URL; ?>assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <!-- Show Password JS -->
    <script src="<?php echo AUTOPUZZLE_PLUGIN_URL; ?>assets/js/show-password.js"></script>
    
    <script>
        var autopuzzle_ajax = {
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce: '<?php echo wp_create_nonce('autopuzzle-ajax-nonce'); ?>',
            phone: '<?php echo esc_js($phone); ?>',
            strings: {
                passwordRequired: '<?php echo esc_js(__('Please enter a password.', 'autopuzzle')); ?>',
                passwordsNotMatch: '<?php echo esc_js(__('Passwords do not match.', 'autopuzzle')); ?>',
                passwordTooShort: '<?php echo esc_js(__('Password must be at least 6 characters.', 'autopuzzle')); ?>',
                saving: '<?php echo esc_js(__('Saving...', 'autopuzzle')); ?>',
                savePassword: '<?php echo esc_js(__('Save Password', 'autopuzzle')); ?>'
            }
        };
        
        function savePassword() {
            var password = document.getElementById('create-password').value;
            var confirmPassword = document.getElementById('create-confirmpassword').value;
            
            if (!password) {
                alert(autopuzzle_ajax.strings.passwordRequired);
                return;
            }
            
            if (password.length < 6) {
                alert(autopuzzle_ajax.strings.passwordTooShort);
                return;
            }
            
            if (password !== confirmPassword) {
                alert(autopuzzle_ajax.strings.passwordsNotMatch);
                return;
            }
            
            var btn = document.getElementById('save-password-btn');
            btn.disabled = true;
            btn.innerHTML = autopuzzle_ajax.strings.saving;
            
            fetch(autopuzzle_ajax.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'autopuzzle_create_password',
                    'nonce': autopuzzle_ajax.nonce,
                    'phone': autopuzzle_ajax.phone,
                    'password': password
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.data.message);
                    window.location.href = data.data.redirect;
                } else {
                    alert(data.data.message);
                    btn.disabled = false;
                    btn.innerHTML = autopuzzle_ajax.strings.savePassword;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('<?php echo esc_js(__('An error occurred. Please try again.', 'autopuzzle')); ?>');
                btn.disabled = false;
                btn.innerHTML = autopuzzle_ajax.strings.savePassword;
            });
        }
    </script>
</body>
</html>

