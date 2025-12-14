<?php
/**
 * Front Page Template - Uses Elementor
 */

get_header();

if (class_exists('\Elementor\Plugin')) {
    $elementor = \Elementor\Plugin::instance();
    $content = $elementor->frontend->get_builder_content_for_display(get_the_ID());

    if ($content) {
        echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    } else {
        if (have_posts()) {
            while (have_posts()) {
                the_post();
                the_content();
            }
        }
    }
} else {
    if (have_posts()) {
        while (have_posts()) {
            the_post();
            the_content();
        }
    }
}

get_footer();
