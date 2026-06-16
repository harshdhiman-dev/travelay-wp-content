/**
 * Amadex Confirmation Page JavaScript
 * Handles interactions and animations on the booking confirmation page
 */

// CRITICAL: Define clearing function OUTSIDE jQuery wrapper so it can run immediately
// This prevents duplicate bookings when user clicks back button
function clearBookingSessionData() {
    // Check if sessionStorage is available
    if (typeof sessionStorage === 'undefined') {
        return;
    }
    
    // List of booking-specific keys to clear
    const bookingKeysToClear = [
        'amadex_booking_flight',
        'amadex_search_data',
        'amadexBookingStage',
        'amadex_booking_step',
        'amadex_booking_timer_start',
        'amadex_booking_timer_remaining',
        'amadex_booking_timer_paused_at',
        'amadex_last_booking_flight_id',
        'amadex_booking_addons',
        'amadex_premium_service_added',
        'amadex_multi_city_bookings',
        'amadex_multi_city_segments',
        'amadex_booking_all_segments',
        'amadex_results_page_url',
        'amadex_booking_reference',
        'amadex_moonpay_paid_reference',
        'amadex_moonpay_onramp_transaction_id'
        // NOTE: 'amadex_pending_purchase' is intentionally NOT cleared here.
        // It is read and pushed to dataLayer on this page, then removed separately.
    ];
    
    // Clear each booking-specific key
    let clearedCount = 0;
    bookingKeysToClear.forEach(function(key) {
        try {
            if (sessionStorage.getItem(key) !== null) {
                sessionStorage.removeItem(key);
                clearedCount++;
            }
        } catch (e) {
            // Ignore errors (e.g., if storage is disabled)
            if (typeof console !== 'undefined' && console.warn) {
                console.warn('Amadex: Could not clear sessionStorage key:', key, e);
            }
        }
    });
    
    // Log for debugging (only if keys were actually cleared)
    if (clearedCount > 0 && typeof console !== 'undefined' && console.log) {
        console.log('Amadex: Cleared ' + clearedCount + ' booking data item(s) from sessionStorage after successful booking');
    }
}

// CRITICAL: Run clearing IMMEDIATELY when script loads (before DOM ready)
// This ensures data is cleared before any other scripts can access it
(function() {
    'use strict';
    
    // Check if we're on confirmation page using multiple methods
    const urlHasReference = window.location.search.indexOf('reference=') !== -1;
    const urlHasConfirmation = window.location.href.indexOf('booking-confirmation') !== -1;
    const hasConfirmationClass = document.querySelector('.amadex-confirmation-page') !== null || 
                                 document.querySelector('.amadex-confirmation-greeting') !== null;
    
    // If any indicator suggests confirmation page, clear data IMMEDIATELY
    if (urlHasReference || urlHasConfirmation || hasConfirmationClass) {
        // Clear booking data immediately (before DOM ready)
        clearBookingSessionData();
        
        // Also set a flag to prevent re-clearing
        if (typeof sessionStorage !== 'undefined') {
            sessionStorage.setItem('amadex_booking_cleared', 'true');
        }
    }
})();

(function($) {
    'use strict';

    /**
     * Initialize confirmation page functionality
     */
    function initConfirmationPage() {
        // Smooth scroll to top on page load
        $('html, body').animate({ scrollTop: 0 }, 300);
        
        // Initialize collapsible flight cards
        initFlightCardToggles();
        
        // Initialize scroll animations
        initScrollAnimations();
        
        // Initialize hover effects
        initHoverEffects();
    }

    /**
     * Initialize collapsible flight card functionality
     * Matches the booking page toggle behavior
     */
    function initFlightCardToggles() {
        // Handle flight card header clicks
        $('.amadex-flight-card-header[data-toggle]').off('click.confirmation').on('click.confirmation', function() {
            const toggleId = $(this).data('toggle');
            const $content = $('#' + toggleId + '-content');
            const $chevron = $(this).find('.amadex-chevron-icon');
            const $card = $(this).closest('.amadex-flight-detail-card');
            
            if ($content.is(':visible')) {
                // Collapse
                $content.slideUp(300);
                $chevron.css('transform', 'rotate(0deg)');
                $card.removeClass('is-expanded');
            } else {
                // Expand
                $content.slideDown(300);
                $chevron.css('transform', 'rotate(180deg)');
                $card.addClass('is-expanded');
            }
        });

        // Handle itinerary toggle clicks (if using different structure)
        $('.amadex-itinerary-toggle').off('click.confirmation').on('click.confirmation', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $header = $(this).closest('.amadex-flight-itinerary-header');
            const $content = $header.next('.amadex-flight-itinerary-content');
            const $chevron = $(this).find('.amadex-chevron-up, .amadex-chevron-icon');
            
            if ($content.length) {
                if ($content.hasClass('is-collapsed')) {
                    // Expand
                    $content.removeClass('is-collapsed');
                    $content.slideDown(400);
                    $header.addClass('is-expanded');
                    if ($chevron.length) {
                        $chevron.css('transform', 'rotate(180deg)');
                    }
                } else {
                    // Collapse
                    $content.addClass('is-collapsed');
                    $content.slideUp(400);
                    $header.removeClass('is-expanded');
                    if ($chevron.length) {
                        $chevron.css('transform', 'rotate(0deg)');
                    }
                }
            }
        });

        // Ensure all flight cards are expanded by default on confirmation page
        // (as per design requirement - cards should be open on confirmation)
        $('.amadex-flight-card-content').each(function() {
            if ($(this).css('display') === 'none') {
                $(this).css('display', 'block');
            }
        });
    }

    /**
     * Initialize scroll animations using Intersection Observer
     */
    function initScrollAnimations() {
        if (typeof IntersectionObserver === 'undefined') {
            // Fallback for browsers without IntersectionObserver
            $('.amadex-card').css({
                'opacity': '1',
                'transform': 'none'
            });
            return;
        }

        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);
        
        // Observe all cards for fade-in animation
        $('.amadex-card').each(function() {
            const $card = $(this);
            $card.css({
                'opacity': '0',
                'transform': 'translateY(20px)',
                'transition': 'opacity 0.6s ease, transform 0.6s ease'
            });
            observer.observe(this);
        });

        // Observe greeting banner
        $('.amadex-confirmation-greeting').each(function() {
            const $greeting = $(this);
            $greeting.css({
                'opacity': '0',
                'transform': 'translateY(-20px)',
                'transition': 'opacity 0.8s ease, transform 0.8s ease'
            });
            observer.observe(this);
        });
    }

    /**
     * Initialize hover effects for cards
     */
    function initHoverEffects() {
        // Add smooth hover effects to cards
        $('.amadex-card').hover(
            function() {
                $(this).css('transform', 'translateY(-2px)');
            },
            function() {
                $(this).css('transform', 'translateY(0)');
            }
        );

        // Hover effects for support card
        $('.amadex-support-card').hover(
            function() {
                $(this).css({
                    'transform': 'translateY(-4px)',
                    'box-shadow': '0px 8px 25px rgba(14, 125, 63, 0.25)'
                });
            },
            function() {
                $(this).css({
                    'transform': 'translateY(0)',
                    'box-shadow': '0px 0px 15px #00000029'
                });
            }
        );

        // Hover effects for support links
        $('.amadex-support-link').hover(
            function() {
                $(this).find('svg').css('transform', 'scale(1.1)');
            },
            function() {
                $(this).find('svg').css('transform', 'scale(1)');
            }
        );
    }

    /**
     * Format booking reference for display
     */
    function formatBookingReference(ref) {
        if (!ref) return '';
        // Add spacing or formatting if needed
        return ref.toUpperCase();
    }

    /**
     * Copy booking reference to clipboard
     */
    function copyBookingReference() {
        const $refElement = $('.amadex-booking-id-value');
        if ($refElement.length) {
            const ref = $refElement.text().trim();
            if (ref) {
                // Create temporary input element
                const $temp = $('<input>');
                $('body').append($temp);
                $temp.val(ref).select();
                document.execCommand('copy');
                $temp.remove();
                
                // Show feedback
                const $feedback = $('<span class="amadex-copy-feedback">Copied!</span>');
                $refElement.after($feedback);
                setTimeout(function() {
                    $feedback.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 2000);
            }
        }
    }

    /**
     * Initialize booking reference copy functionality
     */
    function initBookingReferenceCopy() {
        $('.amadex-booking-id').off('click.copy').on('click.copy', function() {
            copyBookingReference();
        });
    }

    /**
     * Handle responsive behavior
     */
    function handleResponsive() {
        // Adjust card padding on mobile
        if ($(window).width() <= 768) {
            $('.amadex-card').css('padding', '20px 16px');
        } else {
            $('.amadex-card').css('padding', '28px 32px');
        }
    }

    /**
     * Initialize print functionality
     */
    function initPrintFunctionality() {
        // Add print button handler if exists
        $('.amadex-print-booking').off('click.print').on('click.print', function(e) {
            e.preventDefault();
            window.print();
        });
    }

    /**
     * Initialize smooth scrolling for anchor links
     */
    function initSmoothScrolling() {
        $('a[href^="#"]').off('click.smooth').on('click.smooth', function(e) {
            const target = $(this.getAttribute('href'));
            if (target.length) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: target.offset().top - 100
                }, 600);
            }
        });
    }

    /**
     * Initialize all confirmation page features
     */
    $(document).ready(function() {
        // Check if we're on confirmation page
        const isConfirmationPage = $('.amadex-confirmation-page').length > 0 || 
                                   $('.amadex-confirmation-greeting').length > 0 ||
                                   window.location.href.indexOf('booking-confirmation') !== -1 ||
                                   window.location.search.indexOf('reference=') !== -1;
        
        if (isConfirmationPage) {
            // Clear again as backup (in case first clear didn't run)
            clearBookingSessionData();

            // ── GA4 purchase: fires on the booking-confirmation page ──
            // The 'purchase' event payload is built and stored (not pushed)
            // during checkout, before booking session data is cleared.
            // It is pushed to dataLayer here, on the confirmation page,
            // so it's easy to see/verify per booking.
            try {
                var pendingPurchaseRaw = sessionStorage.getItem('amadex_pending_purchase');
                if (pendingPurchaseRaw) {
                    var pendingPurchaseEvent = JSON.parse(pendingPurchaseRaw);
                    window.dataLayer = window.dataLayer || [];
                    window.dataLayer.push({ ecommerce: null });
                    window.dataLayer.push(pendingPurchaseEvent);

                    if (typeof console !== 'undefined' && console.log) {
                        console.log('[Amadex GA4] purchase pushed', pendingPurchaseEvent);
                    }

                    sessionStorage.removeItem('amadex_pending_purchase');
                }
            } catch (purchaseErr) {
                if (typeof console !== 'undefined' && console.warn) {
                    console.warn('[Amadex GA4] purchase push failed:', purchaseErr);
                }
            }

            // Initialize confirmation page features
            initConfirmationPage();
            initBookingReferenceCopy();
            initPrintFunctionality();
            initSmoothScrolling();
            
            // Handle window resize
            $(window).on('resize.confirmation', function() {
                handleResponsive();
            });
            
            // Initial responsive check
            handleResponsive();
        }
    });

    // Expose functions globally if needed
    window.AmadexConfirmation = {
        init: initConfirmationPage,
        copyReference: copyBookingReference,
        toggleFlightCard: function(cardId) {
            const $content = $('#' + cardId + '-content');
            const $header = $('[data-toggle="' + cardId + '"]');
            if ($content.length && $header.length) {
                $header.trigger('click.confirmation');
            }
        }
    };

})(jQuery);

