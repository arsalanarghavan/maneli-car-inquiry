        </main>

        <footer class="site-footer" style="background-color: var(--footer-bg, #f5f5f5); color: var(--footer-text, #333);">
            <div class="site-footer-inner">
                <div class="footer-content">
                    <div class="footer-widgets">
                        <?php
                        if (has_nav_menu('footer')) {
                            wp_nav_menu([
                                'theme_location' => 'footer',
                                'fallback_cb'    => '',
                                'depth'          => 2,
                            ]);
                        }
                        ?>
                    </div>

                    <div class="footer-copyright">
                        <p>
                            <?php
                            $copyright = get_option('maneli_theme_footer_copyright');
                            echo wp_kses_post($copyright ? $copyright : '&copy; ' . date('Y') . ' ' . esc_html(get_bloginfo('name')));
                            ?>
                        </p>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <?php wp_footer(); ?>
</body>
</html>
