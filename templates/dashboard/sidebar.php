<!-- Start::main-sidebar -->

<aside class="app-sidebar sticky" id="sidebar">

    <!-- Start::main-sidebar-header -->
    <div class="main-sidebar-header">
        <a href="<?php echo home_url('/dashboard'); ?>" class="header-logo">
            <?php 
            $theme_handler = Maneli_Frontend_Theme_Handler::instance();
            ?>
            <img src="<?php echo $theme_handler->get_logo('desktop'); ?>" alt="logo" class="desktop-logo">
            <img src="<?php echo $theme_handler->get_logo('toggle-dark'); ?>" alt="logo" class="toggle-dark">
            <img src="<?php echo $theme_handler->get_logo('desktop-dark'); ?>" alt="logo" class="desktop-dark">
            <img src="<?php echo $theme_handler->get_logo('toggle'); ?>" alt="logo" class="toggle-logo">
            <img src="<?php echo $theme_handler->get_logo('toggle-white'); ?>" alt="logo" class="toggle-white">
            <img src="<?php echo $theme_handler->get_logo('desktop-white'); ?>" alt="logo" class="desktop-white">
        </a>
    </div>
    <!-- End::main-sidebar-header -->

    <!-- Start::main-sidebar -->
    <div class="main-sidebar" id="sidebar-scroll">

        <!-- Start::nav -->
        <nav class="main-menu-container nav nav-pills flex-column sub-open">
            <div class="slide-left" id="slide-left">
                <svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewbox="0 0 24 24"> <path d="M13.293 6.293 7.586 12l5.707 5.707 1.414-1.414L10.414 12l4.293-4.293z"></path> </svg>
            </div>
            <ul class="main-menu">
                <!-- Start::slide__category -->
                <li class="slide__category"><span class="category-name">اصلی</span></li>
                <!-- End::slide__category -->

                <!-- Start::slide -->
                <li class="slide">
                    <a href="<?php echo home_url('/dashboard'); ?>" class="side-menu__item <?php echo (!isset($page) || $page === '') ? 'active' : ''; ?>">
                        <i class="la la-home side-menu__icon"></i>
                        <span class="side-menu__label">داشبورد</span>
                    </a>
                </li>
                <!-- End::slide -->

                <!-- Start::slide__category -->
                <li class="slide__category"><span class="category-name">مدیریت استعلامات</span></li>
                <!-- End::slide__category -->

                <!-- Start::slide -->
                <li class="slide">
                    <a href="<?php echo home_url('/dashboard/inquiries/cash'); ?>" class="side-menu__item <?php echo (isset($page) && $page === 'inquiries' && isset($subpage) && $subpage === 'cash') ? 'active' : ''; ?>">
                        <i class="la la-dollar-sign side-menu__icon"></i>
                        <span class="side-menu__label">استعلامات نقدی</span>
                    </a>
                </li>
                <!-- End::slide -->

                <!-- Start::slide -->
                <li class="slide">
                    <a href="<?php echo home_url('/dashboard/inquiries/installment'); ?>" class="side-menu__item <?php echo (isset($page) && $page === 'inquiries' && isset($subpage) && $subpage === 'installment') ? 'active' : ''; ?>">
                        <i class="la la-credit-card side-menu__icon"></i>
                        <span class="side-menu__label">استعلامات اقساطی</span>
                    </a>
                </li>
                <!-- End::slide -->

                <!-- Start::slide -->
                <li class="slide">
                    <a href="<?php echo home_url('/dashboard/followups'); ?>" class="side-menu__item <?php echo (isset($page) && $page === 'followups') ? 'active' : ''; ?>">
                        <i class="la la-calendar-check side-menu__icon"></i>
                        <span class="side-menu__label">پیگیری‌ها</span>
                    </a>
                </li>
                <!-- End::slide -->

                <!-- Start::slide -->
                <li class="slide">
                    <a href="<?php echo home_url('/dashboard/reports'); ?>" class="side-menu__item <?php echo (isset($page) && $page === 'reports') ? 'active' : ''; ?>">
                        <i class="la la-chart-bar side-menu__icon"></i>
                        <span class="side-menu__label">گزارشات</span>
                    </a>
                </li>
                <!-- End::slide -->

                <!-- Start::slide -->
                <li class="slide">
                    <a href="<?php echo home_url('/dashboard/products'); ?>" class="side-menu__item <?php echo (isset($page) && $page === 'products') ? 'active' : ''; ?>">
                        <i class="la la-car side-menu__icon"></i>
                        <span class="side-menu__label">محصولات</span>
                    </a>
                </li>
                <!-- End::slide -->

                <!-- Start::slide -->
                <li class="slide">
                    <a href="<?php echo home_url('/dashboard/payments'); ?>" class="side-menu__item <?php echo (isset($page) && $page === 'payments') ? 'active' : ''; ?>">
                        <i class="la la-money-bill side-menu__icon"></i>
                        <span class="side-menu__label">پرداخت‌ها</span>
                    </a>
                </li>
                <!-- End::slide -->

                <!-- Start::slide -->
                <li class="slide">
                    <a href="<?php echo home_url('/dashboard/users'); ?>" class="side-menu__item <?php echo (isset($page) && $page === 'users') ? 'active' : ''; ?>">
                        <i class="la la-users side-menu__icon"></i>
                        <span class="side-menu__label">کاربران</span>
                    </a>
                </li>
                <!-- End::slide -->

                <!-- Start::slide__category -->
                <li class="slide__category"><span class="category-name">تنظیمات</span></li>
                <!-- End::slide__category -->

                <!-- Start::slide -->
                <li class="slide">
                    <a href="<?php echo home_url('/dashboard/settings'); ?>" class="side-menu__item <?php echo (isset($page) && $page === 'settings') ? 'active' : ''; ?>">
                        <i class="la la-cog side-menu__icon"></i>
                        <span class="side-menu__label">تنظیمات</span>
                    </a>
                </li>
                <!-- End::slide -->

                <!-- Start::slide -->
                <li class="slide">
                    <a href="<?php echo home_url('/logout'); ?>" class="side-menu__item">
                        <i class="la la-sign-out-alt side-menu__icon"></i>
                        <span class="side-menu__label">خروج</span>
                    </a>
                </li>
                <!-- End::slide -->

            </ul>
            <div class="slide-right" id="slide-right"><svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewbox="0 0 24 24"> <path d="M10.707 17.707 16.414 12l-5.707-5.707-1.414 1.414L13.586 12l-4.293 4.293z"></path> </svg></div>
        </nav>
        <!-- End::nav -->

    </div>
    <!-- End::main-sidebar -->

</aside>
<!-- End::main-sidebar -->
