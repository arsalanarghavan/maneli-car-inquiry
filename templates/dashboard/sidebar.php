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
                
                <!-- ═══════════════════════════════════════════════════ -->
                <!-- بخش اصلی -->
                <!-- ═══════════════════════════════════════════════════ -->
                <li class="slide__category"><span class="category-name">داشبورد</span></li>

                <li class="slide">
                    <a href="<?php echo home_url('/dashboard'); ?>" class="side-menu__item <?php echo (!isset($page) || $page === '') ? 'active' : ''; ?>">
                        <i class="la la-home side-menu__icon"></i>
                        <span class="side-menu__label">صفحه اصلی</span>
                    </a>
                </li>

                <!-- ═══════════════════════════════════════════════════ -->
                <!-- استعلامات نقدی -->
                <!-- ═══════════════════════════════════════════════════ -->
                <li class="slide__category"><span class="category-name">استعلامات نقدی</span></li>

                <li class="slide">
                    <a href="<?php echo home_url('/dashboard/inquiries/cash'); ?>" class="side-menu__item <?php echo (isset($page) && $page === 'inquiries' && isset($subpage) && $subpage === 'cash') ? 'active' : ''; ?>">
                        <i class="la la-dollar-sign side-menu__icon"></i>
                        <span class="side-menu__label">لیست استعلامات نقدی</span>
                    </a>
                </li>

                <!-- ═══════════════════════════════════════════════════ -->
                <!-- استعلامات اقساطی -->
                <!-- ═══════════════════════════════════════════════════ -->
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
                        <span class="side-menu__label">پیگیری‌ها</span>
                    </a>
                </li>

                <li class="slide">
                    <a href="<?php echo home_url('/dashboard/calendar'); ?>" class="side-menu__item <?php echo (isset($page) && $page === 'calendar') ? 'active' : ''; ?>">
                        <i class="la la-calendar side-menu__icon"></i>
                        <span class="side-menu__label">تقویم جلسات</span>
                    </a>
                </li>

                <!-- ═══════════════════════════════════════════════════ -->
                <!-- مدیریت محصولات -->
                <!-- ═══════════════════════════════════════════════════ -->
                <li class="slide__category"><span class="category-name">مدیریت محصولات</span></li>

                <li class="slide">
                    <a href="<?php echo home_url('/dashboard/products'); ?>" class="side-menu__item <?php echo (isset($page) && $page === 'products') ? 'active' : ''; ?>">
                        <i class="la la-car side-menu__icon"></i>
                        <span class="side-menu__label">ویرایش محصولات</span>
                    </a>
                </li>

                <!-- ═══════════════════════════════════════════════════ -->
                <!-- مالی -->
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
                <!-- مدیریت کاربران -->
                <!-- ═══════════════════════════════════════════════════ -->
                <?php if (current_user_can('manage_maneli_inquiries')): ?>
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
                <?php endif; ?>

                <!-- ═══════════════════════════════════════════════════ -->
                <!-- تنظیمات و خروج -->
                <!-- ═══════════════════════════════════════════════════ -->
                <li class="slide__category"><span class="category-name">تنظیمات</span></li>

                <?php if (current_user_can('manage_maneli_inquiries')): ?>
                <li class="slide">
                    <a href="<?php echo home_url('/dashboard/settings'); ?>" class="side-menu__item <?php echo (isset($page) && $page === 'settings') ? 'active' : ''; ?>">
                        <i class="la la-cog side-menu__icon"></i>
                        <span class="side-menu__label">تنظیمات سیستم</span>
                    </a>
                </li>
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

<style>
/* ═══════════════════════════════════════════════════════════
   Sidebar Custom Enhancements - Compact Version
   ═══════════════════════════════════════════════════════════ */

/* Fix: المان‌ها از بالا شروع شوند */
.main-sidebar {
    display: flex;
    flex-direction: column;
    height: 100%;
    padding-top: 0 !important;
}

.main-menu-container {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding-top: 5px !important;
}

.main-menu {
    padding-top: 0 !important;
    margin-top: 0 !important;
}

/* Category Styling - Compact */
.slide__category {
    padding: 8px 15px 4px;
    margin-top: 8px;
}

.slide__category:first-child {
    margin-top: 0;
}

.category-name {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: var(--text-muted);
    opacity: 0.7;
    display: flex;
    align-items: center;
}

.category-name::before {
    content: '';
    display: inline-block;
    width: 3px;
    height: 3px;
    background: var(--primary-color);
    border-radius: 50%;
    margin-left: 6px;
}

/* Menu Items - Compact */
.side-menu__item {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    margin: 1px 6px;
    border-radius: 6px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.side-menu__item::before {
    content: '';
    position: absolute;
    right: 0;
    top: 0;
    height: 100%;
    width: 0;
    background: var(--primary-color);
    transition: width 0.3s ease;
    border-radius: 6px 0 0 6px;
}

.side-menu__item:hover::before {
    width: 3px;
}

.side-menu__item.active::before {
    width: 3px;
}

.side-menu__item.active {
    background: linear-gradient(90deg, rgba(var(--primary-rgb), 0.1) 0%, transparent 100%);
    color: var(--primary-color);
}

.side-menu__item:hover {
    background: rgba(var(--primary-rgb), 0.05);
    transform: translateX(-2px);
}

/* Icons - Smaller */
.side-menu__icon {
    font-size: 17px;
    width: 17px;
    text-align: center;
    margin-left: 6px;
    transition: all 0.3s ease;
}

.side-menu__item:hover .side-menu__icon {
    transform: scale(1.05);
    color: var(--primary-color);
}

.side-menu__item.active .side-menu__icon {
    color: var(--primary-color);
}

/* Label - Smaller */
.side-menu__label {
    font-size: 13px;
    font-weight: 500;
    flex: 1;
    line-height: 1.4;
}

/* Badge for Notifications */
.menu-badge {
    margin-right: auto;
    background: var(--danger-color);
    color: white;
    font-size: 9px;
    padding: 1px 5px;
    border-radius: 8px;
    font-weight: 700;
}

/* Logout Item Special Style */
.side-menu__item.text-danger:hover {
    background: rgba(220, 53, 69, 0.1);
}

.side-menu__item.text-danger .side-menu__icon {
    color: #dc3545;
}

/* Scroll Customization */
#sidebar-scroll::-webkit-scrollbar {
    width: 5px;
}

#sidebar-scroll::-webkit-scrollbar-track {
    background: transparent;
}

#sidebar-scroll::-webkit-scrollbar-thumb {
    background: rgba(var(--primary-rgb), 0.3);
    border-radius: 3px;
}

#sidebar-scroll::-webkit-scrollbar-thumb:hover {
    background: rgba(var(--primary-rgb), 0.5);
}

/* Responsive */
@media (max-width: 991.98px) {
    .main-sidebar-header {
        padding: 12px;
    }
    
    .category-name {
        font-size: 9px;
    }
    
    .side-menu__item {
        padding: 7px 12px;
        margin: 1px 6px;
    }
    
    .side-menu__label {
        font-size: 12px;
    }
}

/* Dark Mode Support */
[data-theme-mode="dark"] .category-name {
    color: rgba(255, 255, 255, 0.6);
}

[data-theme-mode="dark"] .side-menu__item:hover {
    background: rgba(255, 255, 255, 0.05);
}

[data-theme-mode="dark"] .side-menu__item.active {
    background: linear-gradient(90deg, rgba(var(--primary-rgb), 0.2) 0%, transparent 100%);
}

/* Slide Arrows */
.slide-left, .slide-right {
    cursor: pointer;
    transition: all 0.3s ease;
}

.slide-left:hover, .slide-right:hover {
    background: rgba(var(--primary-rgb), 0.1);
}
</style>
