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
                <svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24">
                    <path d="M13.293 6.293 7.586 12l5.707 5.707 1.414-1.414L10.414 12l4.293-4.293z"></path>
                </svg>
            </div>
            <ul class="main-menu">
                <?php
                // Get current user info
                $current_user = wp_get_current_user();
                $is_admin = current_user_can('manage_maneli_inquiries');
                $is_expert = in_array('maneli_expert', $current_user->roles, true);
                $is_customer = !$is_admin && !$is_expert;
                ?>
                
                <!-- ═══════════════════════════════════════════════════ -->
                <!-- بخش اصلی - برای همه -->
                <!-- ═══════════════════════════════════════════════════ -->
                <li class="slide__category"><span class="category-name">داشبورد</span></li>

                <li class="slide">
                    <a href="<?php echo home_url('/dashboard'); ?>" class="side-menu__item <?php echo (!isset($page) || $page === '') ? 'active' : ''; ?>">
                        <i class="la la-home side-menu__icon"></i>
                        <span class="side-menu__label">صفحه اصلی</span>
                    </a>
                </li>
                
                <?php if ($is_admin || $is_expert): ?>
                <!-- تقویم جلسات فقط برای Admin و Expert -->
                <li class="slide">
                    <a href="<?php echo home_url('/dashboard/calendar'); ?>" class="side-menu__item <?php echo (isset($page) && $page === 'calendar') ? 'active' : ''; ?>">
                        <i class="la la-calendar side-menu__icon"></i>
                        <span class="side-menu__label">تقویم جلسات</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- ═══════════════════════════════════════════════════ -->
                <!-- استعلامات - بر اساس رول -->
                <!-- ═══════════════════════════════════════════════════ -->
                <?php if ($is_customer): ?>
                    <!-- منوی مشتری -->
                    <li class="slide__category"><span class="category-name">استعلامات من</span></li>
                    
                    <li class="slide">
                        <a href="<?php echo home_url('/dashboard/inquiries/cash'); ?>" class="side-menu__item <?php echo (isset($page) && $page === 'inquiries' && isset($subpage) && $subpage === 'cash') ? 'active' : ''; ?>">
                            <i class="la la-dollar-sign side-menu__icon"></i>
                            <span class="side-menu__label">خرید نقدی</span>
                        </a>
                    </li>
                    
                    <li class="slide">
                        <a href="<?php echo home_url('/dashboard/inquiries/installment'); ?>" class="side-menu__item <?php echo (isset($page) && $page === 'inquiries' && isset($subpage) && $subpage === 'installment') ? 'active' : ''; ?>">
                            <i class="la la-credit-card side-menu__icon"></i>
                            <span class="side-menu__label">خرید اقساطی</span>
                        </a>
                    </li>
                    
                <?php elseif ($is_expert): ?>
                    <!-- منوی کارشناس - فقط استعلامات ارجاع شده -->
                    <li class="slide__category"><span class="category-name">استعلامات نقدی</span></li>

                    <li class="slide">
                        <a href="<?php echo home_url('/dashboard/inquiries/cash'); ?>" class="side-menu__item <?php echo (isset($page) && $page === 'inquiries' && isset($subpage) && $subpage === 'cash') ? 'active' : ''; ?>">
                            <i class="la la-dollar-sign side-menu__icon"></i>
                            <span class="side-menu__label">لیست استعلامات نقدی</span>
                        </a>
                    </li>
                    
                    <li class="slide">
                        <a href="<?php echo home_url('/dashboard/cash-followups'); ?>" class="side-menu__item <?php echo (isset($page) && $page === 'cash-followups') ? 'active' : ''; ?>">
                            <i class="la la-clock side-menu__icon"></i>
                            <span class="side-menu__label">پیگیری‌های نقدی</span>
                        </a>
                    </li>

                    <li class="slide__category"><span class="category-name">استعلامات اقساطی</span></li>

                    <li class="slide">
                        <a href="<?php echo home_url('/dashboard/inquiries/installment'); ?>" class="side-menu__item <?php echo (isset($page) && $page === 'inquiries' && isset($subpage) && $subpage === 'installment') ? 'active' : ''; ?>">
                            <i class="la la-credit-card side-menu__icon"></i>
                            <span class="side-menu__label">لیست استعلامات اقساطی</span>
                        </a>
                    </li>

                    <li class="slide">
                        <a href="<?php echo home_url('/dashboard/followups'); ?>" class="side-menu__item <?php echo (isset($page) && $page === 'followups') ? 'active' : ''; ?>">
                            <i class="la la-tasks side-menu__icon"></i>
                            <span class="side-menu__label">پیگیری‌های اقساطی</span>
                        </a>
                    </li>
                    
                <?php elseif ($is_admin): ?>
                    <!-- منوی مدیر - دسترسی کامل -->
                    <li class="slide__category"><span class="category-name">استعلامات نقدی</span></li>

                    <li class="slide">
                        <a href="<?php echo home_url('/dashboard/inquiries/cash'); ?>" class="side-menu__item <?php echo (isset($page) && $page === 'inquiries' && isset($subpage) && $subpage === 'cash') ? 'active' : ''; ?>">
                            <i class="la la-dollar-sign side-menu__icon"></i>
                            <span class="side-menu__label">لیست استعلامات نقدی</span>
                        </a>
                    </li>
                    
                    <li class="slide">
                        <a href="<?php echo home_url('/dashboard/cash-followups'); ?>" class="side-menu__item <?php echo (isset($page) && $page === 'cash-followups') ? 'active' : ''; ?>">
                            <i class="la la-clock side-menu__icon"></i>
                            <span class="side-menu__label">پیگیری‌های نقدی</span>
                        </a>
                    </li>

                    <li class="slide__category"><span class="category-name">استعلامات اقساطی</span></li>

                    <li class="slide">
                        <a href="<?php echo home_url('/dashboard/inquiries/installment'); ?>" class="side-menu__item <?php echo (isset($page) && $page === 'inquiries' && isset($subpage) && $subpage === 'installment') ? 'active' : ''; ?>">
                            <i class="la la-credit-card side-menu__icon"></i>
                            <span class="side-menu__label">لیست استعلامات اقساطی</span>
                        </a>
                    </li>

                    <li class="slide">
                        <a href="<?php echo home_url('/dashboard/followups'); ?>" class="side-menu__item <?php echo (isset($page) && $page === 'followups') ? 'active' : ''; ?>">
                            <i class="la la-tasks side-menu__icon"></i>
                            <span class="side-menu__label">پیگیری‌های اقساطی</span>
                        </a>
                    </li>

                    <!-- ═══════════════════════════════════════════════════ -->
                    <!-- مدیریت محصولات (فقط مدیر) -->
                    <!-- ═══════════════════════════════════════════════════ -->
                    <li class="slide__category"><span class="category-name">مدیریت محصولات</span></li>

                    <li class="slide">
                        <a href="<?php echo home_url('/dashboard/products'); ?>" class="side-menu__item <?php echo (isset($page) && $page === 'products') ? 'active' : ''; ?>">
                            <i class="la la-car side-menu__icon"></i>
                            <span class="side-menu__label">ویرایش محصولات</span>
                        </a>
                    </li>

                    <!-- ═══════════════════════════════════════════════════ -->
                    <!-- مدیریت مالی (فقط مدیر) -->
                    <!-- ═══════════════════════════════════════════════════ -->
                    <li class="slide__category"><span class="category-name">مدیریت مالی</span></li>

                    <li class="slide">
                        <a href="<?php echo home_url('/dashboard/payments'); ?>" class="side-menu__item <?php echo (isset($page) && $page === 'payments') ? 'active' : ''; ?>">
                            <i class="la la-money-bill-wave side-menu__icon"></i>
                            <span class="side-menu__label">پرداخت‌ها</span>
                        </a>
                    </li>

                    <li class="slide">
                        <a href="<?php echo home_url('/dashboard/reports'); ?>" class="side-menu__item <?php echo (isset($page) && $page === 'reports') ? 'active' : ''; ?>">
                            <i class="la la-chart-line side-menu__icon"></i>
                            <span class="side-menu__label">گزارشات مالی</span>
                        </a>
                    </li>

                    <!-- ═══════════════════════════════════════════════════ -->
                    <!-- مدیریت کاربران (فقط مدیر) -->
                    <!-- ═══════════════════════════════════════════════════ -->
                    <li class="slide__category"><span class="category-name">کاربران و کارشناسان</span></li>

                    <li class="slide">
                        <a href="<?php echo home_url('/dashboard/users'); ?>" class="side-menu__item <?php echo (isset($page) && $page === 'users') ? 'active' : ''; ?>">
                            <i class="la la-users side-menu__icon"></i>
                            <span class="side-menu__label">مدیریت کاربران</span>
                        </a>
                    </li>

                    <li class="slide">
                        <a href="<?php echo home_url('/dashboard/experts'); ?>" class="side-menu__item <?php echo (isset($page) && $page === 'experts') ? 'active' : ''; ?>">
                            <i class="la la-user-tie side-menu__icon"></i>
                            <span class="side-menu__label">کارشناسان</span>
                        </a>
                    </li>

                    <!-- ═══════════════════════════════════════════════════ -->
                    <!-- تنظیمات سیستم (فقط مدیر) -->
                    <!-- ═══════════════════════════════════════════════════ -->
                    <li class="slide__category"><span class="category-name">تنظیمات</span></li>

                    <li class="slide">
                        <a href="<?php echo home_url('/dashboard/settings'); ?>" class="side-menu__item <?php echo (isset($page) && $page === 'settings') ? 'active' : ''; ?>">
                            <i class="la la-cog side-menu__icon"></i>
                            <span class="side-menu__label">تنظیمات سیستم</span>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- ═══════════════════════════════════════════════════ -->
                <!-- خروج - برای همه -->
                <!-- ═══════════════════════════════════════════════════ -->
                <?php if (!$is_admin): ?>
                <li class="slide__category"><span class="category-name">تنظیمات</span></li>
                <?php endif; ?>

                <li class="slide">
                    <a href="<?php echo wp_logout_url(home_url('/login')); ?>" class="side-menu__item text-danger">
                        <i class="la la-sign-out-alt side-menu__icon"></i>
                        <span class="side-menu__label">خروج از حساب</span>
                    </a>
                </li>

            </ul>
            <div class="slide-right" id="slide-right">
                <svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24">
                    <path d="M10.707 17.707 16.414 12l-5.707-5.707-1.414 1.414L13.586 12l-4.293 4.293z"></path>
                </svg>
            </div>
        </nav>
        <!-- End::nav -->

    </div>
    <!-- End::main-sidebar -->

</aside>
<!-- End::main-sidebar -->
