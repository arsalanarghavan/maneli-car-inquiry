<?php
/**
 * Main Dashboard Template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Set page title based on current page
$page_title = 'داشبورد مانلی خودرو';
switch ($page) {
    case 'new-inquiry':
        $page_title = 'استعلام جدید - مانلی خودرو';
        break;
    case 'inquiries':
        $page_title = 'لیست استعلامات - مانلی خودرو';
        break;
    case 'followups':
        $page_title = 'پیگیری‌ها - مانلی خودرو';
        break;
    case 'reports':
        $page_title = 'گزارشات - مانلی خودرو';
        break;
    case 'products':
        $page_title = 'محصولات - مانلی خودرو';
        break;
    case 'product-editor':
        $page_title = 'ویرایش قیمت محصولات - مانلی خودرو';
        break;
    case 'payments':
        $page_title = 'پرداخت‌ها - مانلی خودرو';
        break;
    case 'users':
        $page_title = 'کاربران - مانلی خودرو';
        break;
    case 'experts':
        $page_title = 'کارشناسان - مانلی خودرو';
        break;
    case 'settings':
        $page_title = 'تنظیمات - مانلی خودرو';
        break;
}

// Include header
include MANELI_INQUIRY_PLUGIN_PATH . 'templates/dashboard/header.php';

// Include sidebar  
include MANELI_INQUIRY_PLUGIN_PATH . 'templates/dashboard/sidebar.php';
?>

            <!-- Start::app-content -->
            <div class="main-content app-content">
                <div class="container-fluid">

                    <!-- Start::page-header -->
                    <div class="d-flex align-items-center justify-content-between page-header-breadcrumb flex-wrap gap-2">
                        <div>
                            <ol class="breadcrumb mb-1">
                                <li class="breadcrumb-item">
                                    <a href="<?php echo home_url('/dashboard'); ?>">
                                        خانه
                                    </a>
                                </li>
                                <?php if ($page): ?>
                                    <li class="breadcrumb-item active" aria-current="page">
                                        <?php
                                        switch ($page) {
                                            case 'new-inquiry':
                                                echo 'استعلام جدید';
                                                break;
                                            case 'inquiries':
                                                echo 'لیست استعلامات';
                                                break;
                                            case 'followups':
                                                echo 'پیگیری‌ها';
                                                break;
                                            case 'reports':
                                                echo 'گزارشات';
                                                break;
                                            case 'products':
                                                echo 'محصولات';
                                                break;
                                            case 'payments':
                                                echo 'پرداخت‌ها';
                                                break;
                                            case 'users':
                                                echo 'کاربران';
                                                break;
                                            case 'settings':
                                                echo 'تنظیمات';
                                                break;
                                            default:
                                                echo 'داشبورد';
                                                break;
                                        }
                                        ?>
                                    </li>
                                <?php endif; ?>
                            </ol>
                            <h1 class="page-title fw-medium fs-18 mb-0">
                                <?php
                                switch ($page) {
                                    case 'new-inquiry':
                                        echo 'استعلام جدید - مانلی خودرو';
                                        break;
                                    case 'inquiries':
                                        echo 'لیست استعلامات - مانلی خودرو';
                                        break;
                                    case 'followups':
                                        echo 'پیگیری‌ها - مانلی خودرو';
                                        break;
                                    case 'reports':
                                        echo 'گزارشات - مانلی خودرو';
                                        break;
                                    case 'products':
                                        echo 'محصولات - مانلی خودرو';
                                        break;
                                    case 'payments':
                                        echo 'پرداخت‌ها - مانلی خودرو';
                                        break;
                                    case 'users':
                                        echo 'کاربران - مانلی خودرو';
                                        break;
                                    case 'settings':
                                        echo 'تنظیمات - مانلی خودرو';
                                        break;
                                    default:
                                        echo 'داشبورد اصلی - مانلی خودرو';
                                        break;
                                }
                                ?>
                            </h1>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <div class="form-group">
                                <div class="input-group">
                                    <div class="input-group-text bg-white border"> <i class="la la-calendar"></i> </div>
                                    <input type="text" class="form-control breadcrumb-input" id="daterange" placeholder="جستجو براساس بازه زمانی">
                                </div>
                            </div>
                            <div class="btn-list">
                                <button class="btn btn-white btn-wave">
                                    <i class="la la-filter align-middle me-1 lh-1"></i> فیلتر
                                </button>
                                <button class="btn btn-primary btn-wave me-0">
                                    <i class="la la-share me-1"></i> اشتراک‌گذاری
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- End::page-header -->

                    <!-- Start::row -->
                    <?php
                    // Route to appropriate content based on page
                    switch ($page) {
                        case 'new-inquiry':
                            include MANELI_INQUIRY_PLUGIN_PATH . 'templates/dashboard/new-inquiry.php';
                            break;
                        case 'inquiries':
                            include MANELI_INQUIRY_PLUGIN_PATH . 'templates/dashboard/inquiries.php';
                            break;
                        case 'followups':
                            include MANELI_INQUIRY_PLUGIN_PATH . 'templates/dashboard/followups.php';
                            break;
                        case 'reports':
                            include MANELI_INQUIRY_PLUGIN_PATH . 'templates/dashboard/reports.php';
                            break;
                        case 'products':
                            include MANELI_INQUIRY_PLUGIN_PATH . 'templates/dashboard/products.php';
                            break;
                        case 'product-editor':
                            include MANELI_INQUIRY_PLUGIN_PATH . 'templates/dashboard/product-editor.php';
                            break;
                        case 'payments':
                            include MANELI_INQUIRY_PLUGIN_PATH . 'templates/dashboard/payments.php';
                            break;
                        case 'users':
                            include MANELI_INQUIRY_PLUGIN_PATH . 'templates/dashboard/users.php';
                            break;
                        case 'experts':
                            include MANELI_INQUIRY_PLUGIN_PATH . 'templates/dashboard/experts.php';
                            break;
                        case 'calendar':
                            include MANELI_INQUIRY_PLUGIN_PATH . 'templates/dashboard/calendar.php';
                            break;
                        case 'settings':
                            include MANELI_INQUIRY_PLUGIN_PATH . 'templates/dashboard/settings.php';
                            break;
                        default:
                            include MANELI_INQUIRY_PLUGIN_PATH . 'templates/dashboard/dashboard-home.php';
                            break;
                    }
                    ?>
                    <!-- End::row -->

                </div>
            </div>
            <!-- End::app-content -->

<?php
// Include footer
include MANELI_INQUIRY_PLUGIN_PATH . 'templates/dashboard/footer.php';
?>
