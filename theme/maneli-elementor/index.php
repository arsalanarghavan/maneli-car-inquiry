<?php
get_header();

if (have_posts()) {
    while (have_posts()) {
        the_post();
        ?>
        <article <?php post_class(); ?>>
            <header class="entry-header">
                <?php the_title('<h1 class="entry-title">', '</h1>'); ?>
                <div class="entry-meta">
                    <?php echo esc_html(get_the_date()); ?>
                </div>
            </header>

            <div class="entry-content">
                <?php
                the_content();
                wp_link_pages([
                    'before'           => '<div class="page-links">',
                    'after'            => '</div>',
                    'link_before'      => '<span>',
                    'link_after'       => '</span>',
                    'pagelink'         => __('Page %', 'maneli-elementor'),
                    'separator'        => ' ',
                ]);
                ?>
            </div>
        </article>
        <?php
    }

    the_posts_pagination();
} else {
    echo '<p>' . esc_html__('No posts found.', 'maneli-elementor') . '</p>';
}

get_footer();
