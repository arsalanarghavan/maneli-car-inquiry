<?php
/**
 * Sidebar Menu Template
 * 
 * Renders the sidebar menu based on user role
 * 
 * @package AutoPuzzle
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get menu items from handler
$handler = Autopuzzle_Dashboard_Handler::instance();
$menu_items = $handler->get_menu_items();

/**
 * Render category header
 * 
 * @param string $title Category title
 */
function render_category_header($title) {
    echo '<li class="slide">';
    echo '<div class="slide__category">';
    echo '<span class="category-name">' . esc_html($title) . '</span>';
    echo '</div>';
    echo '</li>';
}

/**
 * Recursively render menu items
 * 
 * @param array $menu_items Array of menu items
 * @param int $level Current nesting level
 */
function render_menu_items($menu_items, $level = 0) {
    foreach ($menu_items as $item) {
        // Check if this item is a category header
        if (isset($item['category'])) {
            render_category_header($item['title']);
            continue;
        }
        
        $has_children = isset($item['children']) && !empty($item['children']);
        $item_class = 'side-menu__item';
        $parent_class = $level === 0 ? 'slide has-sub' : '';
        
        if ($has_children) {
            echo '<li class="slide has-sub ' . ($level > 0 ? 'child' . $level : '') . '">';
            echo '<a href="javascript:void(0);" class="' . $item_class . '">';
            echo '<i class="ri-arrow-down-s-line side-menu__angle"></i>';
            
            // Render RemixIcon
            if (!empty($item['icon'])) {
                echo '<i class="' . esc_attr($item['icon']) . ' side-menu__icon"></i>';
            }
            
            echo '<span class="side-menu__label">' . esc_html($item['title']) . '</span>';
            echo '</a>';
            
            echo '<ul class="slide-menu child' . ($level + 1) . '">';
            render_menu_items($item['children'], $level + 1);
            echo '</ul>';
            echo '</li>';
        } else {
            echo '<li class="slide">';
            echo '<a href="' . esc_url($item['url']) . '" class="' . $item_class . '">';
            
            // Render RemixIcon
            if (!empty($item['icon'])) {
                echo '<i class="' . esc_attr($item['icon']) . ' side-menu__icon"></i>';
            }
            
            echo '<span class="side-menu__label">' . esc_html($item['title']) . '</span>';
            echo '</a>';
            echo '</li>';
        }
    }
}
?>

<!-- Start::main-sidebar -->
<aside class="app-sidebar sticky" id="sidebar">
    <!-- Start::main-sidebar-header -->
    <div class="main-sidebar-header">
        <a href="<?php echo home_url('/dashboard'); ?>" class="header-logo">
            <img src="<?php echo autopuzzle_logo('main'); ?>" alt="<?php echo esc_attr(autopuzzle_brand_name()); ?>" class="desktop-logo">
            <img src="<?php echo AUTOPUZZLE_PLUGIN_URL; ?>assets/images/brand-logos/toggle-dark.ico" alt="logo" class="toggle-dark">
            <img src="<?php echo AUTOPUZZLE_PLUGIN_URL; ?>assets/images/brand-logos/desktop-dark.png" alt="logo" class="desktop-dark">
            <img src="<?php echo AUTOPUZZLE_PLUGIN_URL; ?>assets/images/brand-logos/toggle-logo.png" alt="logo" class="toggle-logo">
            <img src="<?php echo AUTOPUZZLE_PLUGIN_URL; ?>assets/images/brand-logos/toggle-white.ico" alt="logo" class="toggle-white">
            <img src="<?php echo AUTOPUZZLE_PLUGIN_URL; ?>assets/images/brand-logos/desktop-white.png" alt="logo" class="desktop-white">
        </a>
    </div>
    <!-- End::main-sidebar-header -->

    <!-- Start::main-sidebar -->
    <div class="main-sidebar" id="sidebar-scroll">
        <!-- Start::nav -->
        <nav class="main-menu-container nav nav-pills flex-column sub-open">
            <div class="slide-left" id="slide-left">
                <svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewbox="0 0 24 24"> 
                    <path d="M13.293 6.293 7.586 12l5.707 5.707 1.414-1.414L10.414 12l4.293-4.293z"></path> 
                </svg>
            </div>
            <ul class="main-menu">
                <?php render_menu_items($menu_items); ?>
            </ul>
        </nav>
        <!-- End::nav -->
    </div>
    <!-- End::main-sidebar -->
</aside>
<!-- End::main-sidebar -->
