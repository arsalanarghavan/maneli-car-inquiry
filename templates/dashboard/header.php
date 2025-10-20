<?php
/**
 * Dashboard Header Template
 * Based on Xintra Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

$theme_handler = Maneli_Frontend_Theme_Handler::instance();
$current_user = wp_get_current_user();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light" data-menu-styles="dark" data-toggled="close">

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
    
    <!-- Choices JS -->
    <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/libs/choices.js/public/assets/scripts/choices.min.js"></script>

    <!-- Main Theme Js -->
    <script src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/js/main.js"></script>
    
    <!-- Bootstrap Css -->
    <link id="style" href="<?php echo esc_url(MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/bootstrap/css/bootstrap.rtl.min.css'); ?>" rel="stylesheet">

    <!-- Style Css -->
    <link href="<?php echo esc_url(MANELI_INQUIRY_PLUGIN_URL . 'assets/css/styles.css'); ?>" rel="stylesheet">

    <!-- Icons Css -->
    <link href="<?php echo esc_url(MANELI_INQUIRY_PLUGIN_URL . 'assets/css/icons.css'); ?>" rel="stylesheet">
    
    <!-- Icon Fonts -->
    <link href="<?php echo esc_url(MANELI_INQUIRY_PLUGIN_URL . 'assets/icon-fonts/icons.css'); ?>" rel="stylesheet">

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
    
    <!-- Persian Datepicker CSS -->
    <link rel="stylesheet" href="<?php echo esc_url(MANELI_INQUIRY_PLUGIN_URL . 'assets/css/persianDatepicker-default.css'); ?>">
    
    <!-- Persian Fonts -->
    <link rel="stylesheet" href="<?php echo esc_url(MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-fonts.css'); ?>">
    
    <!-- Force RTL and Persian Font -->
    <link rel="stylesheet" href="<?php echo esc_url(MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-rtl-force.css'); ?>">
    
    <!-- Dashboard Additional Fixes -->
    <link rel="stylesheet" href="<?php echo esc_url(MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-complete-dashboard-fix.css'); ?>">
    
    <!-- Loader Fix - Prevent infinite loading -->
    <link rel="stylesheet" href="<?php echo esc_url(MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-loader-fix.css'); ?>">
    
    <!-- End::Styles -->

    <?php
    // Output custom theme colors
    $theme_handler->output_custom_css();
    ?>

</head>

<body class="">

    <!-- Loader -->
    <div id="loader" style="display: none;">
        <img src="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/images/media/loader.svg" alt="">
    </div>
    <!-- Loader -->
    
    <!-- Force Hide Loader Script -->
    <script>
    (function() {
        if (document.querySelector('html')) {
            document.querySelector('html').removeAttribute('loader');
            document.querySelector('html').setAttribute('loader', 'disable');
        }
        
        var loader = document.getElementById('loader');
        if (loader) {
            loader.style.display = 'none';
            loader.classList.add('d-none');
        }
        
        localStorage.setItem('loaderEnable', 'false');
    })();
    </script>

    <?php
    // بارگذاری switcher کامل
    $switcher_file = MANELI_INQUIRY_PLUGIN_DIR . 'templates/dashboard/parts/switcher.php';
    if (file_exists($switcher_file)) {
        include $switcher_file;
    }
    ?>

    <div class="page">

        <?php include MANELI_INQUIRY_PLUGIN_DIR . 'templates/dashboard/parts/header-main.php'; ?>
