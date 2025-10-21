<?php
/**
 * Main Header Content - Fixed Structure
 * با ساختار صحیح theme و آیکون‌های Line Awesome
 */

if (!defined('ABSPATH')) {
    exit;
}

$theme_handler = Maneli_Frontend_Theme_Handler::instance();
$current_user = wp_get_current_user();
?>

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
            <div class="header-element header-search d-md-block d-none my-auto">
                <!-- Start::header-link -->
                <input type="text" class="header-search-bar form-control" id="header-search" placeholder="جستجو در داشبورد..." spellcheck="false" autocomplete="off" autocapitalize="off">
                <a href="javascript:void(0);" class="header-search-icon border-0">
                    <i class="la la-search"></i>
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
                    <i class="la la-search header-link-icon"></i>
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
                        <i class="la la-moon header-link-icon"></i>
                        <!-- End::header-link-icon -->
                    </span>
                    <span class="dark-layout">
                        <!-- Start::header-link-icon -->
                        <i class="la la-sun header-link-icon"></i>
                        <!-- End::header-link-icon -->
                    </span>
                </a>
                <!-- End::header-link|layout-setting -->
            </li>
            <!-- End::header-element -->

            <!-- Start::header-element -->
            <li class="header-element notifications-dropdown dropdown">
                <!-- Start::header-link|dropdown-toggle -->
                <a href="javascript:void(0);" class="header-link dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" id="messageDropdown" aria-expanded="false">
                    <i class="la la-bell header-link-icon"></i>
                    <span class="header-icon-pulse bg-danger rounded pulse pulse-danger"></span>
                </a>
                <!-- End::header-link|dropdown-toggle -->
                <!-- Start::main-header-dropdown -->
                <div class="main-header-dropdown dropdown-menu dropdown-menu-end" data-popper-placement="none">
                    <div class="p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <p class="mb-0 fs-15 fw-semibold">اعلان‌ها</p>
                            <span class="badge bg-danger-transparent" id="notifiation-data">0 جدید</span>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <div class="p-5 empty-item1">
                        <div class="text-center">
                            <span class="avatar avatar-xl avatar-rounded bg-secondary-transparent">
                                <i class="la la-bell-slash fs-2"></i>
                            </span>
                            <h6 class="fw-medium mt-3">هشداری وجود ندارد</h6>
                        </div>
                    </div>
                </div>
                <!-- End::main-header-dropdown -->
            </li>
            <!-- End::header-element -->

            <!-- Start::header-element -->
            <li class="header-element header-fullscreen">
                <!-- Start::header-link -->
                <a onclick="openFullscreen();" href="javascript:void(0);" class="header-link">
                    <i class="la la-expand header-link-icon full-screen-open"></i>
                    <i class="la la-compress header-link-icon full-screen-close d-none"></i>
                </a>
                <!-- End::header-link -->
            </li>
            <!-- End::header-element -->

            <!-- Start::header-element -->
            <li class="header-element dropdown">
                <!-- Start::header-link|dropdown-toggle -->
                <a href="javascript:void(0);" class="header-link dropdown-toggle" id="mainHeaderProfile" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                    <div class="d-flex align-items-center">
                        <div class="me-xl-2">
                            <?php echo get_avatar($current_user->ID, 32, '', '', ['class' => 'avatar avatar-sm avatar-rounded']); ?>
                        </div>
                        <div class="d-xl-block d-none lh-1">
                            <span class="fw-semibold lh-1"><?php echo esc_html($current_user->display_name); ?></span>
                            <span class="op-7 fw-normal d-block fs-11">
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
                    </div>
                </a>
                <!-- End::header-link|dropdown-toggle -->
                <ul class="main-header-dropdown dropdown-menu pt-0 overflow-hidden header-profile-dropdown dropdown-menu-end" aria-labelledby="mainHeaderProfile">
                    <li>
                        <div class="header-navheading border-bottom">
                            <h6 class="main-notification-title mb-1"><?php echo esc_html($current_user->display_name); ?></h6>
                            <p class="main-notification-text mb-0 fs-11 op-6 text-muted"><?php echo esc_html($current_user->user_email); ?></p>
                        </div>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex" href="<?php echo home_url('/dashboard'); ?>">
                            <i class="la la-home fs-16 me-2 op-7"></i>داشبورد
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex" href="<?php echo home_url('/dashboard/settings'); ?>">
                            <i class="la la-cog fs-16 me-2 op-7"></i>تنظیمات
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex" href="<?php echo wp_logout_url(home_url('/login')); ?>">
                            <i class="la la-sign-out-alt fs-16 me-2 op-7"></i>خروج
                        </a>
                    </li>
                </ul>
            </li>
            <!-- End::header-element -->

            <!-- Start::header-element -->
            <li class="header-element">
                <!-- Start::header-link|switcher-icon -->
                <a href="javascript:void(0);" class="header-link switcher-icon" data-bs-toggle="offcanvas" data-bs-target="#switcher-canvas">
                    <i class="la la-sliders-h header-link-icon"></i>
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
                        <i class="la la-search header-link-icon"></i>
                    </a>
                    <input type="search" class="form-control border-0 px-2" placeholder="جستجو در داشبورد..." aria-label="Username">
                    <a href="javascript:void(0);" class="input-group-text" id="voice-search">
                        <i class="la la-microphone header-link-icon"></i>
                    </a>
                    <button class="btn btn-light btn-wave" type="button">
                        <i class="la la-times"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
