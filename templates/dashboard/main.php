<?php
/**
 * Main Dashboard Template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Set page title based on current page
$page_title = 'داشبورد منلی کار';
switch ($page) {
    case 'new-inquiry':
        $page_title = 'استعلام جدید - منلی کار';
        break;
    case 'inquiries':
        $page_title = 'لیست استعلامات - منلی کار';
        break;
    case 'followups':
        $page_title = 'پیگیری‌ها - منلی کار';
        break;
    case 'reports':
        $page_title = 'گزارشات - منلی کار';
        break;
    case 'products':
        $page_title = 'محصولات - منلی کار';
        break;
    case 'payments':
        $page_title = 'پرداخت‌ها - منلی کار';
        break;
    case 'users':
        $page_title = 'کاربران - منلی کار';
        break;
    case 'settings':
        $page_title = 'تنظیمات - منلی کار';
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
                <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
                    <h1 class="page-title fw-semibold fs-18 mb-0">
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
                    </h1>
                    <div class="ms-md-1 ms-0">
                        <nav>
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?php echo home_url('/dashboard'); ?>">خانه</a></li>
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
                                        }
                                        ?>
                                    </li>
                                <?php endif; ?>
                            </ol>
                        </nav>
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
                    case 'payments':
                        include MANELI_INQUIRY_PLUGIN_PATH . 'templates/dashboard/payments.php';
                        break;
                    case 'users':
                        include MANELI_INQUIRY_PLUGIN_PATH . 'templates/dashboard/users.php';
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

<?php
// Include footer
include MANELI_INQUIRY_PLUGIN_PATH . 'templates/dashboard/footer.php';
?>
