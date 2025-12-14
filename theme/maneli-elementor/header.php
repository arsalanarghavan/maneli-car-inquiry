<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>
    <div class="site-wrapper">
        <header class="site-header">
            <div class="site-header-inner">
                <div class="site-branding">
                    <?php
                    if (has_custom_logo()) {
                        the_custom_logo();
                    } else {
                        echo '<a href="' . esc_url(home_url('/')) . '" class="site-logo">';
                        bloginfo('name');
                        echo '</a>';
                    }
                    ?>
                </div>

                <nav class="site-navigation">
                    <?php
                    wp_nav_menu([
                        'theme_location' => 'primary',
                        'fallback_cb'    => '',
                        'depth'          => 3,
                    ]);
                    ?>
                </nav>
            </div>
        </header>

        <main class="site-main">
