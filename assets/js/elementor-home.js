/**
 * Elementor Home Page JavaScript
 * Handles interactive elements, animations, and functionality
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        initializeElements();
    });

    /**
     * Initialize all page elements
     */
    function initializeElements() {
        initializeHeader();
        initializeSwiper();
        initializeProductFilters();
        initializeCounterAnimation();
        initializeScrollAnimations();
        initializeMobileMenu();
        initializeContactForm();
    }

    /**
     * Initialize header functionality
     */
    function initializeHeader() {
        // Search toggle
        $('#search-toggle').on('click', function() {
            $('#search-bar').toggleClass('active');
            $('.search-input').focus();
        });

        // Close search when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.autopuzzle-header-modern').length) {
                $('#search-bar').removeClass('active');
            }
        });

        // Search on enter
        $('.search-input').on('keypress', function(e) {
            if (e.which === 13) {
                const query = $(this).val();
                if (query) {
                    window.location.href = '/?s=' + encodeURIComponent(query);
                }
            }
        });
    }

    /**
     * Initialize Swiper for hero slider
     */
    function initializeSwiper() {
        if (typeof Swiper === 'undefined') {
            console.warn('Swiper library not loaded');
            return;
        }

        const swiper = new Swiper('.hero-swiper', {
            loop: true,
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            effect: 'fade',
            fadeEffect: {
                crossFade: true
            },
            speed: 800,
        });
    }

    /**
     * Initialize product filtering
     */
    function initializeProductFilters() {
        $('.filter-btn').on('click', function() {
            const filter = $(this).data('filter');

            // Update active button
            $('.filter-btn').removeClass('active');
            $(this).addClass('active');

            // Filter products with animation
            filterProducts(filter);
        });

        function filterProducts(filter) {
            const products = $('.product-card');

            products.each(function() {
                const $card = $(this);
                const cardFilter = $card.data('filter');

                if (filter === 'all' || cardFilter === filter) {
                    $card.fadeIn(300);
                    $card.css('display', 'block');
                } else {
                    $card.fadeOut(300);
                }
            });
        }
    }

    /**
     * Animate counters in statistics section
     */
    function initializeCounterAnimation() {
        const counterElements = $('.counter');

        if (counterElements.length === 0) {
            return;
        }

        // Check if element is in viewport
        let hasAnimated = false;

        $(window).on('scroll', function() {
            if (hasAnimated) return;

            const $statsSection = $('.statistics-section');
            const sectionOffset = $statsSection.offset().top;
            const scrollPos = $(window).scrollTop() + $(window).height();

            if (scrollPos > sectionOffset) {
                animateCounters();
                hasAnimated = true;
            }
        });

        function animateCounters() {
            counterElements.each(function() {
                const $counter = $(this);
                const target = parseInt($counter.data('target'));
                const duration = 2000; // 2 seconds
                let current = 0;

                const increment = target / (duration / 50);

                const interval = setInterval(function() {
                    current += increment;

                    if (current >= target) {
                        $counter.text(target);
                        clearInterval(interval);
                    } else {
                        $counter.text(Math.floor(current));
                    }
                }, 50);
            });
        }
    }

    /**
     * Add scroll animations to elements
     */
    function initializeScrollAnimations() {
        // Observe elements with Intersection Observer
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    $(entry.target).addClass('visible');
                    // Unobserve after animation
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1
        });

        // Observe feature cards
        $('.feature-card').each(function() {
            observer.observe(this);
        });

        // Observe product cards
        $('.product-card').each(function() {
            observer.observe(this);
        });

        // Observe blog cards
        $('.blog-card').each(function() {
            observer.observe(this);
        });

        // Observe testimonial cards
        $('.testimonial-card').each(function() {
            observer.observe(this);
        });
    }

    /**
     * Initialize mobile menu toggle
     */
    function initializeMobileMenu() {
        const menuToggle = $('#mobile-menu-toggle');
        const header = $('.autopuzzle-header-modern');
        let mobileNavOpen = false;

        menuToggle.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            mobileNavOpen = !mobileNavOpen;
            
            if (mobileNavOpen) {
                menuToggle.addClass('active');
                // Create and show mobile menu
                showMobileMenu();
            } else {
                menuToggle.removeClass('active');
                hideMobileMenu();
            }
        });

        // Close menu when clicking outside
        $(document).on('click', function(e) {
            if (mobileNavOpen && !$(e.target).closest('.autopuzzle-header-modern').length) {
                mobileNavOpen = false;
                menuToggle.removeClass('active');
                hideMobileMenu();
            }
        });

        // Close menu on window resize
        $(window).on('resize', function() {
            if ($(window).width() > 768 && mobileNavOpen) {
                mobileNavOpen = false;
                menuToggle.removeClass('active');
                hideMobileMenu();
            }
        });
    }

    function showMobileMenu() {
        // Implementation for mobile menu display
        // This can be enhanced with actual mobile menu structure
        console.log('Mobile menu opened');
    }

    function hideMobileMenu() {
        // Implementation for mobile menu hide
        console.log('Mobile menu closed');
    }

    /**
     * Handle contact form submission
     */
    function initializeContactForm() {
        $('#consultation-form').on('submit', function(e) {
            e.preventDefault();

            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            const originalText = $submitBtn.text();

            // Get form data
            const formData = {
                phone: $form.find('input[name="phone"]').val(),
                email: $form.find('input[name="email"]').val(),
                action: 'submit_consultation'
            };

            // Validate
            if (!formData.phone || !formData.email) {
                showNotification('لطفاً تمام فیلدها را تکمیل کنید', 'error');
                return;
            }

            // Submit
            $submitBtn.prop('disabled', true).text('درحال ارسال...');

            $.ajax({
                type: 'POST',
                url: window.location.href,
                data: formData,
                success: function(response) {
                    showNotification('درخواست شما با موفقیت ارسال شد', 'success');
                    $form[0].reset();
                },
                error: function() {
                    showNotification('خطا در ارسال درخواست', 'error');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        });
    }

    /**
     * Show notification message
     */
    function showNotification(message, type) {
        const notification = $('<div>')
            .addClass('notification notification-' + type)
            .text(message)
            .appendTo('body');

        // Animate in
        setTimeout(function() {
            notification.addClass('show');
        }, 10);

        // Remove after 3 seconds
        setTimeout(function() {
            notification.removeClass('show');
            setTimeout(function() {
                notification.remove();
            }, 300);
        }, 3000);
    }

    /**
     * Smooth scroll to anchors
     */
    $(document).on('click', 'a[href^="#"]', function(e) {
        const href = $(this).attr('href');
        if (href === '#') return;

        const $target = $(href);
        if ($target.length) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: $target.offset().top - 100
            }, 800);
        }
    });

})(jQuery);

// Add notification styles
document.addEventListener('DOMContentLoaded', function() {
    if (!document.getElementById('notification-styles')) {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            .notification {
                position: fixed;
                top: 20px;
                left: 20px;
                padding: 15px 20px;
                border-radius: 8px;
                background: white;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                z-index: 9999;
                transform: translateX(-400px);
                transition: transform 0.3s ease;
                max-width: 300px;
                font-weight: 500;
            }

            .notification.show {
                transform: translateX(0);
            }

            .notification-success {
                background: #10b981;
                color: white;
            }

            .notification-error {
                background: #ef4444;
                color: white;
            }

            .notification-info {
                background: #3b82f6;
                color: white;
            }

            @media (max-width: 768px) {
                .notification {
                    left: 10px;
                    right: 10px;
                    max-width: none;
                }
            }
        `;
        document.head.appendChild(style);
    }
});
