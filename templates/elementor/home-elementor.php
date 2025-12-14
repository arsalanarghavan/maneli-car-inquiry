<?php
/**
 * Elementor Home Page Template
 * Fully Elementor-based landing page for Maneli Khodro
 * 
 * This template is designed ONLY for Elementor editor
 * All sections, header, footer are built with Elementor
 * 
 * @package Maneli_Car_Inquiry/Templates/Elementor
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Don't output anything - let Elementor handle everything
// This is just a wrapper for Elementor
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class('elementor-page'); ?>>
    <?php wp_body_open(); ?>
    
    <!-- Elementor Page Content -->
    <div class="elementor-page-wrapper">
        <?php
        // Get the current page ID
        $page_id = get_the_ID();
        
        // Check if Elementor has content for this page
        if (class_exists('\Elementor\Plugin')) {
            // Output Elementor content
            echo \Elementor\Plugin::instance()->frontend->get_builder_content($page_id);
        } else {
            // Fallback if Elementor is not active
            the_content();
        }
        ?>
    </div>

    <?php wp_footer(); ?>
</body>
</html>
