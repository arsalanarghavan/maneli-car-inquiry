<?php
/**
 * Dashboard Header Template
 * فقط HTML، HEAD و شروع BODY
 */

if (!defined('ABSPATH')) {
    exit;
}

$theme_handler = Maneli_Frontend_Theme_Handler::instance();
$current_user = wp_get_current_user();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl" class="maneli-dashboard" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light" data-menu-styles="dark" data-toggled="close">

<head>

    <!-- Meta Data -->
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="Description" content="داشبورد مدیریتی منلی کار">
    <meta name="Author" content="<?php echo esc_attr($theme_handler->get_site_title()); ?>">
    <meta name="keywords" content="داشبورد، استعلام خودرو، منلی کار">
    
    <!-- Title -->
    <title><?php echo isset($page_title) ? esc_html($page_title) : esc_html($theme_handler->get_site_title()); ?></title>

    <!-- Favicon -->
    <link rel="icon" href="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/images/brand-logos/favicon.ico" type="image/x-icon">

    <!-- Start::Styles -->
    
    <!-- تنظیم مسیر پلاگین -->
    <script>
        window.MANELI_PLUGIN_URL = '<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>';
    </script>
    
    <!-- Bootstrap Path Fix (باید قبل از همه لود شود) -->
    <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/js/maneli-bootstrap-fix.js"></script>
    
    <!-- Maneli Xintra Init - فیکس مسیرها -->
    <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/js/maneli-xintra-init.js"></script>
    
    <!-- Bootstrap Css -->
    <link id="style" href="<?php echo esc_url(MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/bootstrap/css/bootstrap.rtl.min.css'); ?>" rel="stylesheet">
    
    <!-- Main Theme Js -->
    <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/js/main.js"></script>

    <!-- Style Css -->
    <link href="<?php echo esc_url(MANELI_INQUIRY_PLUGIN_URL . 'assets/css/styles.css'); ?>" rel="stylesheet">

    <!-- Icons Css -->
    <link href="<?php echo esc_url(MANELI_INQUIRY_PLUGIN_URL . 'assets/css/icons.css'); ?>" rel="stylesheet">

    <!-- Node Waves Css -->
    <link href="<?php echo esc_url(MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/node-waves/waves.min.css'); ?>" rel="stylesheet"> 

    <!-- Simplebar Css -->
    <link href="<?php echo esc_url(MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/simplebar/simplebar.min.css'); ?>" rel="stylesheet">
    
    <!-- Color Picker Css -->
    <link rel="stylesheet" href="<?php echo esc_url(MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/flatpickr/flatpickr.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo esc_url(MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/@simonwep/pickr/themes/nano.min.css'); ?>">

    <!-- Choices Css -->
    <link rel="stylesheet" href="<?php echo esc_url(MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/choices.js/public/assets/styles/choices.min.css'); ?>">

    <!-- Auto Complete CSS -->
    <link rel="stylesheet" href="<?php echo esc_url(MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/@tarekraafat/autocomplete.js/css/autoComplete.css'); ?>">
    <!-- End::Styles -->

    <?php
    // Output custom theme colors
    $theme_handler->output_custom_css();
    ?>
    
    <!-- jQuery - Load before other scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

</head>

<body class="maneli-dashboard">

    <!-- Loader -->
    <div id="loader">
        <img src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/images/media/loader.svg" alt="">
    </div>
    <!-- Loader -->
    
    <?php
    // Include switcher
    $switcher_file = MANELI_INQUIRY_PLUGIN_DIR . 'templates/dashboard/parts/switcher.php';
    if (file_exists($switcher_file)) {
        include $switcher_file;
    }
    ?>

    <div class="page">

        <!-- Start::main-header -->
        <header class="app-header sticky" id="header">

            <!-- Start::main-header-container -->
            <div class="main-header-container container-fluid">

                <!-- Start::header-content-left -->
                <div class="header-content-left">

                    <!-- Start::header-element -->
                    <div class="header-element">
                        <div class="horizontal-logo">
                            <a href="<?php echo home_url('/dashboard'); ?>" class="header-logo">
                                <img src="<?php echo $theme_handler->get_logo('desktop'); ?>" alt="logo" class="desktop-logo">
                                <img src="<?php echo $theme_handler->get_logo('toggle-dark'); ?>" alt="logo" class="toggle-dark">
                                <img src="<?php echo $theme_handler->get_logo('desktop-dark'); ?>" alt="logo" class="desktop-dark">
                                <img src="<?php echo $theme_handler->get_logo('toggle'); ?>" alt="logo" class="toggle-logo">
                                <img src="<?php echo $theme_handler->get_logo('toggle-white'); ?>" alt="logo" class="toggle-white">
                                <img src="<?php echo $theme_handler->get_logo('desktop-white'); ?>" alt="logo" class="desktop-white">
                            </a>
                        </div>
                    </div>
                    <!-- End::header-element -->

                    <!-- Start::header-element -->
                    <div class="header-element mx-lg-0 mx-2">
                        <a aria-label="Hide Sidebar" class="sidemenu-toggle header-link animated-arrow hor-toggle horizontal-navtoggle" data-bs-toggle="sidebar" href="javascript:void(0);"><span></span></a>
                    </div>
                    <!-- End::header-element -->

                    <!-- Start::header-element -->
                    <div class="header-element header-search d-md-block d-none my-auto auto-complete-search">
                        <!-- Start::header-link -->
                        <input type="text" class="header-search-bar form-control" id="header-search" placeholder="جستجو" spellcheck="false" autocomplete="off" autocapitalize="off">
                        <a href="javascript:void(0);" class="header-search-icon border-0">
                            <i class="ri-search-line"></i>
                        </a>
                        <!-- End::header-link -->
                    </div>
                    <!-- End::header-element -->

                </div>
                <!-- End::header-content-left -->

                <!-- Start::header-content-right -->
                <ul class="header-content-right">

                    <!-- Start::header-element -->
                    <li class="header-element d-md-none d-block">
                        <a href="javascript:void(0);" class="header-link" data-bs-toggle="modal" data-bs-target="#header-responsive-search">
                            <!-- Start::header-link-icon -->
                            <i class="bi bi-search header-link-icon d-flex"></i>
                            <!-- End::header-link-icon -->
                        </a>  
                    </li>
                    <!-- End::header-element -->

                    <!-- Start::header-element -->
                    <li class="header-element header-theme-mode">
                        <!-- Start::header-link|layout-setting -->
                        <a href="javascript:void(0);" class="header-link layout-setting">
                            <span class="light-layout">
                                <!-- Start::header-link-icon -->
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 header-link-icon" fill="none" viewbox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z"></path>
                                </svg>
                                <!-- End::header-link-icon -->
                            </span>
                            <span class="dark-layout">
                                <!-- Start::header-link-icon -->
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 header-link-icon" fill="none" viewbox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"></path>
                                </svg>
                                <!-- End::header-link-icon -->
                            </span>
                        </a>
                        <!-- End::header-link|layout-setting -->
                    </li>
                    <!-- End::header-element -->

                    <!-- Start::header-element -->
                    <li class="header-element notifications-dropdown d-xl-block d-none dropdown">
                        <!-- Start::header-link|dropdown-toggle -->
                        <a href="javascript:void(0);" class="header-link dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" id="messageDropdown" aria-expanded="false">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 header-link-icon" fill="none" viewbox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0M3.124 7.5A8.969 8.969 0 0 1 5.292 3m13.416 0a8.969 8.969 0 0 1 2.168 4.5"></path>
                            </svg>
                            <span class="header-icon-pulse bg-primary2 rounded pulse pulse-secondary"></span>
                        </a>
                        <!-- End::header-link|dropdown-toggle -->
                        <!-- Start::main-header-dropdown -->
                        <div class="main-header-dropdown dropdown-menu dropdown-menu-end" data-popper-placement="none">
                            <div class="p-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <p class="mb-0 fs-15 fw-medium">هشدارها</p>
                                    <span class="badge bg-secondary text-fixed-white" id="notifiation-data">0 خوانده نشده</span>
                                </div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <ul class="list-unstyled mb-0 d-none" id="header-notification-scroll">
                                <!-- Empty for now -->
                            </ul>
                            <div class="p-3 empty-header-item1 border-top d-none">
                                <div class="d-grid">
                                    <a href="javascript:void(0);" class="btn btn-primary btn-wave">مشاهده همه</a>
                                </div>
                            </div>
                            <div class="p-5 empty-item1">
                                <div class="text-center">
                                    <span class="avatar avatar-xl avatar-rounded bg-secondary-transparent">
                                        <i class="ri-notification-off-line fs-2"></i>
                                    </span>
                                    <h6 class="fw-medium mt-3">هشداری وجود ندارد</h6>
                                </div>
                            </div>
                        </div>
                    </li>
                    <!-- End::header-element -->

                    <!-- Start::header-element -->
                    <li class="header-element header-fullscreen">
                        <!-- Start::header-link -->
                        <a onclick="openFullscreen();" href="javascript:void(0);" class="header-link">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 full-screen-open header-link-icon" fill="none" viewbox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15"></path>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 full-screen-close header-link-icon d-none" fill="none" viewbox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 9H4.5M9 9 3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5 5.25 5.25"></path>
                            </svg>
                        </a>
                        <!-- End::header-link -->
                    </li>
                    <!-- End::header-element -->

                    <!-- Start::header-element -->
                    <li class="header-element dropdown">
                        <!-- Start::header-link|dropdown-toggle -->
                        <a href="javascript:void(0);" class="header-link dropdown-toggle" id="mainHeaderProfile" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                            <div class="d-flex align-items-center">
                                <div>
                                    <?php echo get_avatar($current_user->ID, 32, '', '', ['class' => 'avatar avatar-sm']); ?>
                                </div>
                            </div>
                        </a>
                        <!-- End::header-link|dropdown-toggle -->
                        <ul class="main-header-dropdown dropdown-menu pt-0 overflow-hidden header-profile-dropdown dropdown-menu-end" aria-labelledby="mainHeaderProfile">
                            <li>
                                <div class="dropdown-item text-center border-bottom">
                                    <span>
                                        <?php echo esc_html($current_user->display_name); ?>
                                    </span>
                                    <span class="d-block fs-12 text-muted">
                                        <?php 
                                        if (current_user_can('manage_maneli_inquiries')) {
                                            echo 'مدیر سیستم';
                                        } elseif (in_array('maneli_expert', $current_user->roles)) {
                                            echo 'کارشناس';
                                        } else {
                                            echo 'کاربر';
                                        }
                                        ?>
                                    </span>
                                </div>
                            </li>
                            <li><a class="dropdown-item d-flex align-items-center" href="<?php echo home_url('/dashboard'); ?>"><i class="fe fe-user p-1 rounded-circle bg-primary-transparent me-2 fs-16"></i>حساب کاربری</a></li>
                            <li><a class="dropdown-item d-flex align-items-center" href="<?php echo home_url('/dashboard/settings'); ?>"><i class="fe fe-settings p-1 rounded-circle bg-primary-transparent ings me-2 fs-16"></i>تنظیمات</a></li>
                            <li><a class="dropdown-item d-flex align-items-center" href="<?php echo wp_logout_url(home_url('/login')); ?>"><i class="fe fe-lock p-1 rounded-circle bg-primary-transparent ut me-2 fs-16"></i>خروج</a></li>
                        </ul>
                    </li>  
                    <!-- End::header-element -->

                    <!-- Start::header-element -->
                    <li class="header-element">
                        <!-- Start::header-link|switcher-icon -->
                        <a href="javascript:void(0);" class="header-link switcher-icon" data-bs-toggle="offcanvas" data-bs-target="#switcher-canvas">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 header-link-icon" fill="none" viewbox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"></path>
                            </svg>
                        </a>
                        <!-- End::header-link|switcher-icon -->
                    </li>
                    <!-- End::header-element -->

                </ul>
                <!-- End::header-content-right -->

            </div>
            <!-- End::main-header-container -->

        </header>
        <!-- End::main-header -->

        <!-- Modal Search Responsive -->
        <div class="modal fade" id="header-responsive-search" tabindex="-1" aria-labelledby="header-responsive-searchLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="input-group">
                            <a href="javascript:void(0);" class="input-group-text" id="Search-Grid">
                                <i class="bi bi-search header-link-icon"></i>
                            </a>
                            <input type="search" class="form-control border-0 px-2" placeholder="جستجو" aria-label="Username">
                            <a href="javascript:void(0);" class="input-group-text" id="voice-search">
                                <i class="bi bi-mic header-link-icon"></i>
                            </a>
                            <button class="btn btn-light btn-wave" type="button" data-bs-dismiss="modal">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
