<?php
/**
 * Elementor Home Page Template
 * Modern, fully customizable landing page for AutoPuzzle
 * 
 * This template is designed to work seamlessly with Elementor and includes:
 * - Hero slider with vehicle showcase
 * - Feature highlights section
 * - Product gallery with filtering
 * - Call-to-action sections
 * - Customer testimonials
 * - Company info & statistics
 * - Blog/news section
 * - Contact CTA
 * 
 * @package AutoPuzzle/Templates/Elementor
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get theme option for page layout
$layout_type = get_option('autopuzzle_home_layout', 'elementor'); // elementor or classic
$show_header = get_option('autopuzzle_show_custom_header', true);
$show_footer = get_option('autopuzzle_show_custom_footer', true);

// Enqueue custom styles for this template
wp_enqueue_style('autopuzzle-home-page', AUTOPUZZLE_PLUGIN_URL . 'assets/css/elementor-home.css', [], '1.0.0');
wp_enqueue_script('autopuzzle-home-page', AUTOPUZZLE_PLUGIN_URL . 'assets/js/elementor-home.js', ['jquery'], '1.0.0', true);

// Get Elementor content if available
$elementor_home_content = get_post_meta(get_the_ID(), '_elementor_data', true);

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class('autopuzzle-home-page'); ?>>
    
    <?php wp_body_open(); ?>

    <!-- HEADER SECTION -->
    <?php if ($show_header) : ?>
        <header class="autopuzzle-header-modern">
            <div class="header-container">
                <!-- Top Info Bar -->
                <div class="header-info-bar">
                    <div class="info-left">
                        <button class="btn-login">دسته‌کاری</button>
                        <a href="#" class="link-signup">ثبت نام</a>
                    </div>
                    <div class="info-right">
                        <span class="phone-number">۰۲۱-۹۹۸۱۲۱۲۹-۲۷</span>
                    </div>
                </div>

                <!-- Main Header Content -->
                <div class="header-main">
                    <!-- Left: Navigation Menu -->
                    <nav class="header-nav">
                        <a href="#" class="nav-item active">صفحه اصلی</a>
                        <a href="#" class="nav-item">فروشگاه</a>
                        <a href="#" class="nav-item">خرید اقساطی</a>
                        <a href="#" class="nav-item">درباره ما</a>
                        <a href="#" class="nav-item">تماس با ما</a>
                    </nav>

                    <!-- Center: Logo -->
                    <div class="logo-section">
                        <a href="<?php echo esc_url(home_url('/')); ?>" class="logo-link">
                            <img src="<?php echo esc_url(autopuzzle_logo('main')); ?>" 
                                 alt="<?php echo esc_attr(autopuzzle_brand_name()); ?>" 
                                 class="logo-img">
                            <div class="logo-text">
                                <div class="logo-fa"><?php echo esc_html(autopuzzle_brand_name('fa_IR')); ?></div>
                                <div class="logo-en"><?php echo esc_html(autopuzzle_brand_name('en_US')); ?></div>
                            </div>
                        </a>
                    </div>

                    <!-- Right: Actions -->
                    <div class="header-actions">
                        <button class="btn-search" id="search-toggle">
                            <i class="fas fa-search"></i>
                        </button>
                        <?php
                        if (is_user_logged_in()) {
                            echo '<a href="' . esc_url(get_dashboard_url()) . '" class="btn-user"><i class="fas fa-user-circle"></i></a>';
                        } else {
                            echo '<a href="' . esc_url(wp_login_url()) . '" class="btn-user"><i class="fas fa-user-circle"></i></a>';
                        }
                        ?>
                        <button class="btn-menu-toggle" id="mobile-menu-toggle">
                            <i class="fas fa-bars"></i>
                        </button>
                    </div>
                </div>

                <!-- Search Bar (Hidden by default) -->
                <div class="header-search-bar" id="search-bar">
                    <div class="search-input-group">
                        <input type="search" placeholder="جستجو برای خودرو..." class="search-input">
                        <button class="search-btn"><i class="fas fa-search"></i></button>
                    </div>
                </div>
            </div>
        </header>
    <?php endif; ?>

    <!-- MAIN CONTENT -->
    <main class="autopuzzle-main-content">
        
        <!-- HERO SLIDER SECTION -->
        <section class="hero-slider-section">
            <div class="hero-slider">
                <div class="swiper-container hero-swiper">
                    <div class="swiper-wrapper">
                        <!-- Slide 1 -->
                        <div class="swiper-slide hero-slide">
                            <div class="slide-bg" style="background-image: url('<?php echo esc_url(AUTOPUZZLE_PLUGIN_URL . 'assets/images/hero-1.jpg'); ?>');">
                                <div class="slide-overlay"></div>
                                <div class="slide-content">
                                    <h1 class="slide-title">خرید خودرو با اقساط آسان</h1>
                                    <p class="slide-subtitle">بدون ضامن، بدون تاخیر - فقط سه مرحله ساده</p>
                                    <a href="#products" class="btn btn-primary btn-lg">مشاهده خودروها</a>
                                </div>
                            </div>
                        </div>

                        <!-- Slide 2 -->
                        <div class="swiper-slide hero-slide">
                            <div class="slide-bg" style="background-image: url('<?php echo esc_url(AUTOPUZZLE_PLUGIN_URL . 'assets/images/hero-2.jpg'); ?>');">
                                <div class="slide-overlay"></div>
                                <div class="slide-content">
                                    <h1 class="slide-title">تحویل خودرو در کمتر از یک ساعت</h1>
                                    <p class="slide-subtitle">خدمات سریع و قابل اعتماد</p>
                                    <a href="#features" class="btn btn-primary btn-lg">بیشتر بدانید</a>
                                </div>
                            </div>
                        </div>

                        <!-- Slide 3 -->
                        <div class="swiper-slide hero-slide">
                            <div class="slide-bg" style="background-image: url('<?php echo esc_url(AUTOPUZZLE_PLUGIN_URL . 'assets/images/hero-3.jpg'); ?>');">
                                <div class="slide-overlay"></div>
                                <div class="slide-content">
                                    <h1 class="slide-title">سند به نام شما از اول</h1>
                                    <p class="slide-subtitle">مالکیت واقعی از روز اول</p>
                                    <a href="#contact" class="btn btn-primary btn-lg">درخواست مشاوره</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Slider Navigation -->
                    <div class="swiper-button-prev"></div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-pagination"></div>
                </div>
            </div>
        </section>

        <!-- FEATURES SECTION -->
        <section id="features" class="features-section">
            <div class="container">
                <h2 class="section-title"><?php echo sprintf(__('چرا باید از %s خرید کنیم؟', 'autopuzzle'), esc_html(autopuzzle_brand_name('fa_IR'))); ?></h2>
                <p class="section-subtitle">مزایای انتخاب ما برای خرید خودروی دلخواه‌تان</p>

                <div class="features-grid">
                    <!-- Feature 1 -->
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3 class="feature-title">تحویل فوری</h3>
                        <p class="feature-text">تحویل خودرو در کمتر از یک ساعت پس از تایید نهایی</p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <h3 class="feature-title">بدون ضامن</h3>
                        <p class="feature-text">تنها با ارائه چک شخصی معتبر، فرایند خرید را انجام دهید</p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-file-contract"></i>
                        </div>
                        <h3 class="feature-title">سند به نام شما</h3>
                        <p class="feature-text">سند خودرو از همان ابتدا به نام خریدار صادر می‌شود</p>
                    </div>

                    <!-- Feature 4 -->
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <h3 class="feature-title">خدمات شفاف</h3>
                        <p class="feature-text">شفافیت کامل در تمام مراحل خرید و قرارداد</p>
                    </div>

                    <!-- Feature 5 -->
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 class="feature-title">پشتیبانی مستمر</h3>
                        <p class="feature-text">تیم مجرب ما در تمام مراحل خریدتان کنار شما خواهد بود</p>
                    </div>

                    <!-- Feature 6 -->
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-car"></i>
                        </div>
                        <h3 class="feature-title">تنوع خودرو</h3>
                        <p class="feature-text">انتخاب گسترده‌ای از خودروهای مختلف برای شما</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- PRODUCTS SECTION -->
        <section id="products" class="products-section">
            <div class="container">
                <h2 class="section-title">آخرین خودروها</h2>
                <p class="section-subtitle">بهترین پیشنهادات برای شما</p>

                <!-- Product Filters -->
                <div class="product-filters">
                    <button class="filter-btn active" data-filter="all">همه</button>
                    <button class="filter-btn" data-filter="cash">نقدی</button>
                    <button class="filter-btn" data-filter="installment">اقساطی</button>
                    <button class="filter-btn" data-filter="assembled">مونتاژی</button>
                    <button class="filter-btn" data-filter="imported">وارداتی</button>
                </div>

                <!-- Products Grid -->
                <div class="products-grid">
                    <?php
                    // Get latest products
                    $products = get_posts([
                        'post_type' => 'product',
                        'posts_per_page' => 12,
                        'orderby' => 'date',
                        'order' => 'DESC'
                    ]);

                    if ($products) {
                        foreach ($products as $product) {
                            // Get product details
                            $product_obj = wc_get_product($product->ID);
                            $price = $product_obj->get_price();
                            $image = get_the_post_thumbnail_url($product->ID, 'medium');
                            $category = get_the_terms($product->ID, 'product_cat')[0] ?? null;
                            $type = get_post_meta($product->ID, '_product_type', true) ?: 'all';
                            
                            ?>
                            <div class="product-card" data-filter="<?php echo esc_attr($type); ?>">
                                <div class="product-image">
                                    <?php if ($image) : ?>
                                        <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($product->post_title); ?>">
                                    <?php endif; ?>
                                    <?php if ($category) : ?>
                                        <span class="product-category"><?php echo esc_html($category->name); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <h3 class="product-title"><?php echo esc_html($product->post_title); ?></h3>
                                    <p class="product-description"><?php echo wp_trim_words($product->post_excerpt, 15); ?></p>
                                    <div class="product-footer">
                                        <span class="product-price"><?php echo wc_price($price); ?></span>
                                        <a href="<?php echo esc_url(get_permalink($product->ID)); ?>" class="btn btn-sm btn-outline">مشاهده جزئیات</a>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>

                <div class="text-center mt-5">
                    <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="btn btn-primary btn-lg">مشاهده تمام خودروها</a>
                </div>
            </div>
        </section>

        <!-- BUYING PROCESS SECTION -->
        <section class="buying-process-section">
            <div class="container">
                <h2 class="section-title"><?php echo sprintf(__('فرایند خرید خودرو در %s', 'autopuzzle'), esc_html(autopuzzle_brand_name('fa_IR'))); ?></h2>
                <p class="section-subtitle">تنها 3 مرحله ساده برای صاحب شدن خودروی دلخواه‌تان</p>

                <div class="process-steps">
                    <!-- Step 1 -->
                    <div class="process-step">
                        <div class="step-number">۱</div>
                        <h3 class="step-title">انتخاب و مشاوره</h3>
                        <p class="step-text">خودروی مورد نظر خود را انتخاب کنید و با کارشناسان ما مشاوره داشته باشید</p>
                        <div class="step-icon">
                            <i class="fas fa-search"></i>
                        </div>
                    </div>

                    <div class="process-arrow">
                        <i class="fas fa-arrow-left"></i>
                    </div>

                    <!-- Step 2 -->
                    <div class="process-step">
                        <div class="step-number">۲</div>
                        <h3 class="step-title">تکمیل مدارک</h3>
                        <p class="step-text">مدارک لازم را تکمیل کنید و اطلاعات خود را تایید کنید</p>
                        <div class="step-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                    </div>

                    <div class="process-arrow">
                        <i class="fas fa-arrow-left"></i>
                    </div>

                    <!-- Step 3 -->
                    <div class="process-step">
                        <div class="step-number">۳</div>
                        <h3 class="step-title">تحویل و استفاده</h3>
                        <p class="step-text">خودروی خود را تحویل بگیرید و از آن لذت ببرید</p>
                        <div class="step-icon">
                            <i class="fas fa-car"></i>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- STATISTICS SECTION -->
        <section class="statistics-section">
            <div class="container">
                <div class="statistics-grid">
                    <div class="stat-card">
                        <div class="stat-number">
                            <span class="counter" data-target="20">0</span>
                            <span class="stat-suffix">+</span>
                        </div>
                        <p class="stat-label">تیم مجرب</p>
                    </div>

                    <div class="stat-card">
                        <div class="stat-number">
                            <span class="counter" data-target="5000">0</span>
                            <span class="stat-suffix">+</span>
                        </div>
                        <p class="stat-label">سفارش تکمیل شده</p>
                    </div>

                    <div class="stat-card">
                        <div class="stat-number">
                            <span class="counter" data-target="9">0</span>
                            <span class="stat-suffix">+</span>
                        </div>
                        <p class="stat-label">سال تجربه</p>
                    </div>

                    <div class="stat-card">
                        <div class="stat-number">
                            <span class="counter" data-target="3">0</span>
                        </div>
                        <p class="stat-label">مرحله خرید</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- TESTIMONIALS SECTION -->
        <section class="testimonials-section">
            <div class="container">
                <h2 class="section-title"><?php echo sprintf(__('رضایت مشتریان %s', 'autopuzzle'), esc_html(autopuzzle_brand_name('fa_IR'))); ?></h2>
                <p class="section-subtitle">نظرات و تجارب واقعی مشتریان ما</p>

                <div class="testimonials-grid">
                    <?php
                    // Get testimonials from reviews
                    $reviews = get_comments([
                        'post_type' => 'product',
                        'number' => 6,
                        'status' => 'approve'
                    ]);

                    if ($reviews) {
                        foreach ($reviews as $review) {
                            $rating = intval(get_comment_meta($review->comment_ID, 'rating', true));
                            ?>
                            <div class="testimonial-card">
                                <div class="testimonial-rating">
                                    <?php for ($i = 0; $i < $rating; $i++) : ?>
                                        <i class="fas fa-star"></i>
                                    <?php endfor; ?>
                                </div>
                                <p class="testimonial-text"><?php echo wp_trim_words($review->comment_content, 20); ?></p>
                                <div class="testimonial-author">
                                    <strong><?php echo esc_html($review->comment_author); ?></strong>
                                    <span class="testimonial-date"><?php echo esc_html(get_comment_date('j F Y', $review->comment_ID)); ?></span>
                                </div>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>
            </div>
        </section>

        <!-- BLOG SECTION -->
        <section class="blog-section">
            <div class="container">
                <h2 class="section-title">آخرین مطالب وبلاگ</h2>
                <p class="section-subtitle">اخبار و مقالات مفید درباره خودروها</p>

                <div class="blog-grid">
                    <?php
                    // Get latest blog posts
                    $posts = get_posts([
                        'post_type' => 'post',
                        'posts_per_page' => 3,
                        'orderby' => 'date',
                        'order' => 'DESC'
                    ]);

                    if ($posts) {
                        foreach ($posts as $post) {
                            $image = get_the_post_thumbnail_url($post->ID, 'medium');
                            ?>
                            <article class="blog-card">
                                <?php if ($image) : ?>
                                    <div class="blog-image">
                                        <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($post->post_title); ?>">
                                    </div>
                                <?php endif; ?>
                                <div class="blog-content">
                                    <h3 class="blog-title"><?php echo esc_html($post->post_title); ?></h3>
                                    <p class="blog-excerpt"><?php echo wp_trim_words($post->post_content, 20); ?></p>
                                    <div class="blog-meta">
                                        <span class="blog-date"><?php echo esc_html(get_the_date('j F Y', $post->ID)); ?></span>
                                        <a href="<?php echo esc_url(get_permalink($post->ID)); ?>" class="blog-link">بیشتر بخوانید</a>
                                    </div>
                                </div>
                            </article>
                            <?php
                        }
                    }
                    ?>
                </div>
            </div>
        </section>

        <!-- CALL-TO-ACTION SECTION -->
        <section id="contact" class="cta-section">
            <div class="container">
                <div class="cta-content">
                    <h2 class="cta-title">درخواست مشاوره رایگان</h2>
                    <p class="cta-text">
                        اگر نیازمند خرید خودرو به صورت اقساط هستید ولی نمی‌دانید از کجا شروع کنید؟
                        <br>شماره خود را ثبت کنید تا کارشناسان ما با شما تماس بگیرند.
                    </p>

                    <!-- Contact Form -->
                    <form class="cta-form" id="consultation-form">
                        <div class="form-group">
                            <input type="tel" name="phone" placeholder="شماره تلفن خود را وارد کنید" required>
                        </div>
                        <div class="form-group">
                            <input type="email" name="email" placeholder="آدرس ایمیل" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg">درخواست مشاوره</button>
                    </form>
                </div>
            </div>
        </section>

        <!-- ABOUT SECTION -->
        <section class="about-section">
            <div class="container">
                <div class="about-content">
                    <div class="about-text">
                        <h2 class="section-title"><?php echo sprintf(__('درباره %s', 'autopuzzle'), esc_html(autopuzzle_brand_name('fa_IR'))); ?></h2>
                        <p>
                            <?php echo sprintf(__('%s از سال ۱۳۹۵ فعالیت خود را در زمینه فروش اقساطی خودرو آغاز کرده و با دریافت مجوز رسمی از وزارت صنعت، معدن و تجارت، به یکی از برندهای قابل اعتماد در این حوزه تبدیل شده است.', 'autopuzzle'), esc_html(autopuzzle_brand_name('fa_IR'))); ?>
                        </p>
                        <p>
                            <?php echo __('ما با هدف ساده‌سازی فرآیند خرید خودرو، شرایطی را فراهم کرده‌ایم که مشتریان تنها در سه مرحله بتوانند صاحب خودروی دلخواه خود شوند.', 'autopuzzle'); ?>
                        </p>
                        <p>
                            <?php echo sprintf(__('%s با بهره‌گیری از تیمی مجرب، خدمات شفاف، تحویل سریع و تنوع در انتخاب خودرو، تجربه‌ای آسان، امن و حرفه‌ای را برای خریداران فراهم می‌کند.', 'autopuzzle'), esc_html(autopuzzle_brand_name('fa_IR'))); ?>
                        </p>
                    </div>
                    <div class="about-image">
                        <img src="<?php echo esc_url(AUTOPUZZLE_PLUGIN_URL . 'assets/images/about.jpg'); ?>" alt="<?php echo esc_attr(sprintf(__('نمایشگاه %s', 'autopuzzle'), autopuzzle_brand_name('fa_IR'))); ?>">
                    </div>
                </div>
            </div>
        </section>

    </main>

    <!-- FOOTER SECTION -->
    <?php if ($show_footer) : ?>
        <footer class="autopuzzle-footer-modern">
            <div class="container">
                <div class="footer-content">
                    <!-- Footer Column 1: Logo & Info -->
                    <div class="footer-col">
                        <h4 class="footer-title"><?php echo esc_html(autopuzzle_brand_name('fa_IR')); ?></h4>
                        <p class="footer-desc">
                            فروش خودروهای جدید و دست‌دوم با شرایط اقساطی و نقدی آسان
                        </p>
                        <div class="footer-socials">
                            <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="social-link"><i class="fab fa-telegram"></i></a>
                            <a href="#" class="social-link"><i class="fab fa-whatsapp"></i></a>
                        </div>
                    </div>

                    <!-- Footer Column 2: Quick Links -->
                    <div class="footer-col">
                        <h4 class="footer-title">لینک‌های مهم</h4>
                        <ul class="footer-links">
                            <li><a href="<?php echo esc_url(home_url('/')); ?>">صفحه اصلی</a></li>
                            <li><a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">فروشگاه</a></li>
                            <li><a href="#about">درباره ما</a></li>
                            <li><a href="#contact">تماس با ما</a></li>
                        </ul>
                    </div>

                    <!-- Footer Column 3: Info -->
                    <div class="footer-col">
                        <h4 class="footer-title">دسترسی سریع</h4>
                        <ul class="footer-links">
                            <li><a href="#">شرایط اقساطی</a></li>
                            <li><a href="#">خرید نقدی</a></li>
                            <li><a href="#">حریم خصوصی</a></li>
                            <li><a href="#">قوانین و مقررات</a></li>
                        </ul>
                    </div>

                    <!-- Footer Column 4: Contact -->
                    <div class="footer-col">
                        <h4 class="footer-title">تماس با ما</h4>
                        <p class="footer-contact">
                            <i class="fas fa-map-marker-alt"></i>
                            تهران، خیابان شریعتی
                        </p>
                        <p class="footer-contact">
                            <i class="fas fa-phone"></i>
                            <a href="tel:02177636319">۰۲۱-۷۷۶۳۶۳۱۹</a>
                        </p>
                        <p class="footer-contact">
                            <i class="fas fa-clock"></i>
                            ساعت کاری: ۱۰ الی ۱۸
                        </p>
                    </div>
                </div>

                <!-- Footer Bottom -->
                <div class="footer-bottom">
                    <div class="footer-copyright">
                        <p>&copy; <?php echo esc_html(date('Y')); ?> <?php echo esc_html(autopuzzle_brand_name('fa_IR')); ?> - تمام حقوق محفوظ است</p>
                    </div>
                    <div class="footer-credits">
                        <p><?php echo sprintf(__('طراحی و توسعه توسط <a href="%s">%s</a>', 'autopuzzle'), esc_url(home_url('/')), esc_html(Autopuzzle_Branding_Helper::get_company_name())); ?></p>
                    </div>
                </div>
            </div>
        </footer>
    <?php endif; ?>

    <?php wp_footer(); ?>
</body>
</html>
