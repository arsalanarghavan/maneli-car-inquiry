/**
 * اسکریپت شورتکد گزارشات سیستم
 * 
 * @package Maneli_Car_Inquiry
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // انیمیشن ورود کارت‌ها
        animateCards();
        
        // Smooth scroll
        smoothScroll();
    });
    
    /**
     * انیمیشن ورود کارت‌ها
     */
    function animateCards() {
        const cards = $('.stat-card');
        
        cards.each(function(index) {
            const card = $(this);
            setTimeout(function() {
                card.css({
                    'opacity': '0',
                    'transform': 'translateY(20px)'
                }).animate({
                    'opacity': '1'
                }, 300, function() {
                    card.css('transform', 'translateY(0)');
                });
            }, index * 50);
        });
    }
    
    /**
     * Smooth scroll برای لینک‌ها
     */
    function smoothScroll() {
        $('a[href^="#"]').on('click', function(e) {
            const target = $(this.getAttribute('href'));
            if (target.length) {
                e.preventDefault();
                $('html, body').stop().animate({
                    scrollTop: target.offset().top - 100
                }, 800);
            }
        });
    }
    
})(jQuery);

