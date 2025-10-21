<!DOCTYPE html>
<html lang="fa" dir="rtl" data-nav-layout="vertical" data-vertical-style="overlay" data-theme-mode="light" data-header-styles="light" data-menu-styles="light" data-toggled="close" loader="disable">

<head>

    <!-- Meta Data -->
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="Description" content="ورود به داشبورد مانلی خودرو">
    <meta name="Author" content="مانلی خودرو">
    <meta name="keywords" content="ورود، داشبورد، مانلی خودرو">
    
    <!-- Title -->
    <title>ورود - داشبورد مانلی خودرو</title>

    <!-- Favicon -->
    <?php 
    $theme_handler_login = Maneli_Frontend_Theme_Handler::instance();
    ?>
    <link rel="icon" href="<?php echo $theme_handler_login->get_favicon(); ?>" type="image/x-icon">

    <!-- Start::custom-styles -->
        
    <!-- Main Theme Js -->
    <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/js/authentication-main.js"></script>

    <!-- Bootstrap Css -->
    <link id="style" href="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/libs/bootstrap/css/bootstrap.rtl.min.css" rel="stylesheet">

    <!-- Style Css -->
    <link href="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/css/styles.css" rel="stylesheet">

    <!-- Line Awesome Complete - CSS کامل با font-face و content codes -->
    <link href="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/css/maneli-line-awesome-complete.css" rel="stylesheet">
    
    <!-- Fonts Css -->
    <link href="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/css/maneli-fonts.css" rel="stylesheet">
    
    <!-- Force RTL and Persian Font -->
    <link rel="stylesheet" href="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/css/maneli-rtl-force.css">
    
    <!-- Dashboard Additional Fixes -->
    <link rel="stylesheet" href="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/css/maneli-dashboard-fix.css">
    
    <!-- Loader Fix - Prevent infinite loading -->
    <link rel="stylesheet" href="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/css/maneli-loader-fix.css">
    
    <!-- End::custom-styles -->

</head>

<body class="bg-white">

    <!-- Start::custom-switcher -->
    
    <div class="offcanvas offcanvas-end" tabindex="-1" id="switcher-canvas" aria-labelledby="offcanvasRightLabel">
        <div class="offcanvas-header border-bottom d-block p-0">
            <div class="d-flex align-items-center justify-content-between p-3">
                <h5 class="offcanvas-title text-default" id="offcanvasRightLabel">تنظیمات</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <nav class="border-top border-block-start-dashed">
                <div class="nav nav-tabs nav-justified" id="switcher-main-tab" role="tablist">
                    <button class="nav-link active" id="switcher-home-tab" data-bs-toggle="tab" data-bs-target="#switcher-home" type="button" role="tab" aria-controls="switcher-home" aria-selected="true">تنظیمات قالب</button>
                    <button class="nav-link" id="switcher-profile-tab" data-bs-toggle="tab" data-bs-target="#switcher-profile" type="button" role="tab" aria-controls="switcher-profile" aria-selected="false">رنگ قالب</button>
                </div>
            </nav>
        </div>
        <div class="offcanvas-body">
            <div class="tab-content" id="nav-tabContent">
                <div class="tab-pane fade show active border-0" id="switcher-home" role="tabpanel" aria-labelledby="switcher-home-tab" tabindex="0">
                    <div class="">
                        <p class="switcher-style-head">حالت رنگ قالب:</p>
                        <div class="row switcher-style gx-0">
                            <div class="col-4">
                                <div class="form-check switch-select">
                                    <label class="form-check-label" for="switcher-light-theme">
                                        روشن
                                    </label>
                                    <input class="form-check-input" type="radio" name="theme-style" id="switcher-light-theme" checked="">
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-check switch-select">
                                    <label class="form-check-label" for="switcher-dark-theme">
                                        تیره
                                    </label>
                                    <input class="form-check-input" type="radio" name="theme-style" id="switcher-dark-theme">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade border-0" id="switcher-profile" role="tabpanel" aria-labelledby="switcher-profile-tab" tabindex="0">
                    <div>
                        <div class="theme-colors">
                            <p class="switcher-style-head">رنگ اصلی قالب:</p>
                            <div class="d-flex flex-wrap align-items-center switcher-style">
                                <div class="form-check switch-select me-3">
                                    <input class="form-check-input color-input color-primary-1" type="radio" name="theme-primary" id="switcher-primary" checked="">
                                </div>
                                <div class="form-check switch-select me-3">
                                    <input class="form-check-input color-input color-primary-2" type="radio" name="theme-primary" id="switcher-primary1">
                                </div>
                                <div class="form-check switch-select me-3">
                                    <input class="form-check-input color-input color-primary-3" type="radio" name="theme-primary" id="switcher-primary2">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-between canvas-footer flex-nowrap gap-2">
            <a href="javascript:void(0);" id="reset-all" class="btn btn-danger m-1 w-100">بازنشانی</a>
        </div>
    </div>
    <!-- End::custom-switcher -->

    <div class="row authentication authentication-cover-main mx-0">
        <div class="col-xxl-6 col-xl-7">
            <div class="row justify-content-center align-items-center h-100">
                <div class="col-xxl-7 col-xl-9 col-lg-6 col-md-6 col-sm-8 col-12">
                    <div class="card custom-card my-auto border">
                        <div class="card-body p-5">
                            <p class="h5 mb-2 text-center">ورود به داشبورد</p>
                            <p class="mb-4 text-muted op-7 fw-normal text-center">خوش آمدید!</p>
                            
                            <?php if (isset($error_message) && !empty($error_message)): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo esc_html($error_message); ?>
                                </div>
                            <?php endif; ?>

                            <?php if (isset($success_message) && !empty($success_message)): ?>
                                <div class="alert alert-success" role="alert">
                                    <?php echo esc_html($success_message); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="text-center my-3 authentication-barrier">
                                <span>انتخاب روش ورود</span>
                            </div>
                            
                            <div class="d-flex mb-3 justify-content-between gap-2">
                                <button type="button" class="btn btn-lg btn-primary-light border flex-fill" id="sms-login-btn">
                                    <i class="la la-comment me-2"></i>
                                    <span class="lh-1">ورود با پیامک</span>
                                </button>
                                <button type="button" class="btn btn-lg btn-light border flex-fill" id="password-login-btn">
                                    <i class="la la-lock me-2"></i>
                                    <span class="lh-1">ورود با رمز عبور</span>
                                </button>
                            </div>
                            
                            <form id="loginForm" method="POST">
                                <div class="row gy-3">
                                    <div class="col-xl-12">
                                        <label for="phone" class="form-label text-default">شماره موبایل</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" placeholder="09123456789" required>
                                    </div>
                                    
                                    <!-- SMS Fields -->
                                    <div class="col-xl-12" id="sms-fields">
                                        <label for="sms_code" class="form-label text-default">کد تایید</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="sms_code" name="sms_code" placeholder="کد 4 رقمی" maxlength="4">
                                            <button class="btn btn-primary" type="button" id="send_sms_btn">
                                                <i class="la la-paper-plane me-1"></i>
                                                ارسال کد
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Password Fields -->
                                    <div class="col-xl-12 mb-2" id="password-fields" style="display: none;">
                                        <label for="password" class="form-label text-default">رمز عبور</label>
                                        <div class="position-relative">
                                            <input type="password" class="form-control create-password-input" id="password" name="password" placeholder="رمز عبور">
                                            <a href="javascript:void(0);" class="show-password-button text-muted" id="toggle-password"><i class="la la-eye-slash align-middle"></i></a>
                                        </div>
                                    </div>
                                    
                                    <input type="hidden" name="login_type" id="login_type" value="sms">
                                </div>
                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-primary">ورود</button>
                                </div>
                                <div class="text-center">
                                    <p class="text-muted mt-3 mb-0">
                                        <a href="<?php echo home_url(); ?>" class="text-primary">
                                            <i class="la la-arrow-right me-1"></i>بازگشت به صفحه اصلی
                                        </a>
                                    </p>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-6 col-xl-5 col-lg-12 d-xl-block d-none px-0">
            <div class="authentication-cover overflow-hidden">
                <div class="authentication-cover-logo">
                    <a href="<?php echo home_url(); ?>">
                        <?php
                        // دریافت لوگوی سفارشی وردپرس
                        $custom_logo_id = get_theme_mod('custom_logo');
                        if ($custom_logo_id) {
                            $logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
                            echo '<img src="' . esc_url($logo_url) . '" alt="' . get_bloginfo('name') . '" class="authentication-brand desktop-white" style="max-height: 80px; width: auto;">';
                        } else {
                            // Fallback به لوگوی پیش‌فرض اگر لوگو تنظیم نشده باشد
                            echo '<img src="' . MANELI_INQUIRY_PLUGIN_URL . 'assets/images/brand-logos/desktop-white.png" alt="' . get_bloginfo('name') . '" class="authentication-brand desktop-white">';
                        }
                        ?>
                    </a>
                </div>
                <div class="aunthentication-cover-content d-flex align-items-center justify-content-center">
                    <div>
                        <h3 class="text-fixed-white mb-1 fw-medium">خوش آمدید!</h3>
                        <h6 class="text-fixed-white mb-3 fw-medium">ورود به داشبورد مانلی خودرو</h6>
                        <p class="text-fixed-white mb-1 op-6">به داشبورد مدیریت خوش آمدید. لطفاً برای مدیریت استعلامات و نظارت بر فعالیت‌ها وارد شوید.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Start::custom-scripts -->
    
    <!-- Bootstrap JS -->
    <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- End::custom-scripts -->

    <!-- Show Password JS -->
    <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/js/show-password.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const smsLoginBtn = document.getElementById('sms-login-btn');
            const passwordLoginBtn = document.getElementById('password-login-btn');
            const smsFields = document.getElementById('sms-fields');
            const passwordFields = document.getElementById('password-fields');
            const loginTypeInput = document.getElementById('login_type');
            const sendSmsBtn = document.getElementById('send_sms_btn');
            const phoneInput = document.getElementById('phone');
            const smsCodeInput = document.getElementById('sms_code');
            const passwordInput = document.getElementById('password');
            const togglePassword = document.getElementById('toggle-password');
            const loginForm = document.getElementById('loginForm');

            // Toggle login type - SMS
            smsLoginBtn.addEventListener('click', function() {
                smsLoginBtn.classList.remove('btn-light');
                smsLoginBtn.classList.add('btn-primary-light');
                passwordLoginBtn.classList.remove('btn-primary-light');
                passwordLoginBtn.classList.add('btn-light');
                
                smsFields.style.display = 'block';
                passwordFields.style.display = 'none';
                loginTypeInput.value = 'sms';
                smsCodeInput.required = true;
                passwordInput.required = false;
            });

            // Toggle login type - Password
            passwordLoginBtn.addEventListener('click', function() {
                passwordLoginBtn.classList.remove('btn-light');
                passwordLoginBtn.classList.add('btn-primary-light');
                smsLoginBtn.classList.remove('btn-primary-light');
                smsLoginBtn.classList.add('btn-light');
                
                smsFields.style.display = 'none';
                passwordFields.style.display = 'block';
                loginTypeInput.value = 'password';
                smsCodeInput.required = false;
                passwordInput.required = true;
            });

            // Toggle password visibility
            if (togglePassword) {
                togglePassword.addEventListener('click', function(e) {
                    e.preventDefault();
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    const icon = this.querySelector('i');
                    icon.classList.toggle('la la-eye-slash');
                    icon.classList.toggle('ri-eye-line');
                });
            }

            // Send SMS code
            sendSmsBtn.addEventListener('click', function() {
                const phone = phoneInput.value.trim();
                if (!phone) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'توجه',
                        text: 'لطفاً شماره موبایل را وارد کنید',
                        confirmButtonText: 'متوجه شدم'
                    });
                    return;
                }

                if (!/^09\d{9}$/.test(phone)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطا',
                        text: 'لطفاً شماره موبایل معتبر وارد کنید',
                        confirmButtonText: 'متوجه شدم'
                    });
                    return;
                }

                this.disabled = true;
                this.innerHTML = '<i class="la la-spinner la-spin me-1"></i>در حال ارسال...';

                // Send AJAX request
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'maneli_send_sms_code',
                        phone: phone,
                        nonce: '<?php echo wp_create_nonce('maneli_dashboard_nonce'); ?>'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'موفق!',
                            text: 'کد تایید ارسال شد',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        
                        // Start countdown
                        let countdown = 60;
                        const interval = setInterval(() => {
                            this.innerHTML = `<i class="la la-clock me-1"></i>${countdown}`;
                            countdown--;
                            if (countdown < 0) {
                                clearInterval(interval);
                                this.disabled = false;
                                this.innerHTML = '<i class="la la-paper-plane me-1"></i>ارسال کد';
                            }
                        }, 1000);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'خطا',
                            text: data.data.message || 'خطا در ارسال کد تایید',
                            confirmButtonText: 'متوجه شدم'
                        });
                        this.disabled = false;
                        this.innerHTML = '<i class="la la-paper-plane me-1"></i>ارسال کد';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'خطا',
                        text: 'خطا در ارسال کد تایید',
                        confirmButtonText: 'متوجه شدم'
                    });
                    this.disabled = false;
                    this.innerHTML = '<i class="la la-paper-plane me-1"></i>ارسال کد';
                });
            });

            // Handle form submission
            loginForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('action', 'maneli_dashboard_login');
                formData.append('nonce', '<?php echo wp_create_nonce('maneli_dashboard_nonce'); ?>');

                // Show loading
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="la la-spinner la-spin me-1"></i>در حال ورود...';

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'ورود موفق!',
                            text: 'در حال انتقال به داشبورد...',
                            timer: 1500,
                            showConfirmButton: false,
                            timerProgressBar: true
                        }).then(() => {
                            window.location.href = data.data.redirect;
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'خطا در ورود',
                            text: data.data.message || 'اطلاعات ورود نامعتبر است',
                            confirmButtonText: 'متوجه شدم'
                        });
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'خطا',
                        text: 'خطا در برقراری ارتباط با سرور',
                        confirmButtonText: 'متوجه شدم'
                    });
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
            });

            // Format phone number
            phoneInput.addEventListener('input', function() {
                let value = this.value.replace(/\D/g, '');
                if (value.length > 11) {
                    value = value.substring(0, 11);
                }
                this.value = value;
            });

            // Format SMS code
            smsCodeInput.addEventListener('input', function() {
                let value = this.value.replace(/\D/g, '');
                if (value.length > 4) {
                    value = value.substring(0, 4);
                }
                this.value = value;
            });
        });
    </script>

</body>

</html>
