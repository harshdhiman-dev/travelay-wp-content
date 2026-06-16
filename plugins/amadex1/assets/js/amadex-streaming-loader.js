/**
 * Amadex Streaming Loader
 * 
 * Handles streaming responses, skeleton UI, and loading animations
 * Coordinates progressive loading of flight results
 *
 * @package Amadex
 * @since 1.1.0
 */

(function($) {
    'use strict';
    
    /**
     * Streaming Loader Class
     */
    class AmadexStreamingLoader {
        constructor(container, options = {}) {
            this.container = $(container);
            // Read from AmadexConfig if available; otherwise use safe defaults
            const config = (typeof AmadexConfig !== 'undefined') ? AmadexConfig : {};
            const configSkeleton = config.enableSkeletonUi === true;
            const configAnimation = config.enableLoadingAnimation === true;
            const configVirtual = config.enableVirtualScrolling === true;
            this.options = $.extend({
                skeletonCount: (typeof config.streamingInitialCount === 'number') ? config.streamingInitialCount : 5,
                itemHeight: 200,
                enableSkeleton: configSkeleton,
                enableAnimation: configAnimation,
                enableVirtualScroll: configVirtual
            }, options);
            
            this.skeletonShown = false;
            this.animationShown = false;
            this.initialFlightsReceived = false;
            this.remainingFlightsRequested = false;
            
            this.init();
        }
        
        init() {
            // Ensure container exists
            if (this.container.length === 0) {
                console.warn('Amadex Streaming Loader: Container not found');
                return;
            }
        }
        
        /**
         * Show skeleton UI
         */
        showSkeleton(count = null) {
            if (!this.options.enableSkeleton) {
                return;
            }
            
            const skeletonCount = count || this.options.skeletonCount;
            
            // Check if skeleton template exists
            if (typeof AmadexConfig !== 'undefined' && AmadexConfig.skeletonTemplate) {
                // Load skeleton via AJAX
                this.loadSkeletonTemplate(skeletonCount);
            } else {
                // Create inline skeleton
                this.createInlineSkeleton(skeletonCount);
            }
            
            this.skeletonShown = true;
        }
        
        /**
         * Load skeleton template via AJAX
         */
        loadSkeletonTemplate(count) {
            $.ajax({
                url: AmadexConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'amadex_get_skeleton',
                    nonce: AmadexConfig.nonce,
                    count: count
                },
                success: (response) => {
                    if (response.success && response.data.html) {
                        this.container.prepend(response.data.html);
                    }
                },
                error: () => {
                    // Fallback to inline skeleton
                    this.createInlineSkeleton(count);
                }
            });
        }
        
        /**
         * Create inline skeleton
         */
        createInlineSkeleton(count) {
            let skeletonHTML = '<div class="amadex-skeleton-container" id="amadex-skeleton-container">';
            
            for (let i = 0; i < count; i++) {
                skeletonHTML += `
                    <div class="amadex-skeleton-card" data-skeleton-index="${i}">
                        <div class="amadex-skeleton-header">
                            <div class="amadex-skeleton-line" style="width: 60%; height: 20px; margin-bottom: 8px;"></div>
                            <div class="amadex-skeleton-line" style="width: 30%; height: 16px;"></div>
                        </div>
                        <div class="amadex-skeleton-content">
                            <div class="amadex-skeleton-line" style="width: 80%; height: 14px; margin-bottom: 10px;"></div>
                            <div class="amadex-skeleton-line" style="width: 50%; height: 14px; margin-bottom: 10px;"></div>
                            <div class="amadex-skeleton-line" style="width: 70%; height: 14px;"></div>
                        </div>
                        <div class="amadex-skeleton-price">
                            <div class="amadex-skeleton-line" style="width: 40%; height: 24px; float: right;"></div>
                        </div>
                    </div>
                `;
            }
            
            skeletonHTML += '</div>';
            this.container.prepend(skeletonHTML);
        }
        
        /**
         * Hide skeleton UI
         */
        hideSkeleton() {
            const $skeleton = this.container.find('.amadex-skeleton-container');
            if ($skeleton.length > 0) {
                $skeleton.addClass('fade-out');
                setTimeout(() => {
                    $skeleton.remove();
                }, 300);
            }
            this.skeletonShown = false;
        }
        
        /**
         * Show loading animation
         */
        showLoadingAnimation(origin = '', destination = '') {
            if (!this.options.enableAnimation) {
                return;
            }
            
            // Check if animation template exists
            if (typeof AmadexConfig !== 'undefined' && AmadexConfig.animationTemplate) {
                this.loadAnimationTemplate(origin, destination);
            } else {
                this.createInlineAnimation(origin, destination);
            }
            
            this.animationShown = true;
            $(document).trigger('amadex:loading-animation:show');
        }
        
        /**
         * Load animation template via AJAX
         */
        loadAnimationTemplate(origin, destination) {
            $.ajax({
                url: AmadexConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'amadex_get_loading_animation',
                    nonce: AmadexConfig.nonce,
                    origin: origin,
                    destination: destination
                },
                success: (response) => {
                    if (response.success && response.data.html) {
                        this.container.prepend(response.data.html);
                    }
                },
                error: () => {
                    // Fallback to inline animation
                    this.createInlineAnimation(origin, destination);
                }
            });
        }
        
        /**
         * Create inline animation
         */
        createInlineAnimation(origin, destination) {
            const animationHTML = `
                <div class="amadex-loading-animation" id="amadex-loading-animation">
                    <div class="amadex-loading-content">
                        <div class="amadex-airplane-container">
                            <div class="amadex-loading-spinner"></div>
                        </div>
                        <div class="amadex-loading-message">
                            <span class="amadex-message-text" id="amadex-loading-message-text">Searching your flights...</span>
                        </div>
                        <div class="amadex-progress-container">
                            <div class="amadex-progress-bar" id="amadex-loading-progress-bar"></div>
                        </div>
                    </div>
                </div>
            `;
            this.container.prepend(animationHTML);
        }
        
        /**
         * Hide loading animation
         */
        hideLoadingAnimation() {
            const $animation = this.container.find('#amadex-loading-animation');
            if ($animation.length > 0) {
                $animation.addClass('fade-out');
                setTimeout(() => {
                    $animation.remove();
                }, 300);
            }
            this.animationShown = false;
            $(document).trigger('amadex:loading-animation:hide');
        }
        
        /**
         * Handle initial streaming response
         */
        handleInitialResponse(flights) {
            if (!flights || flights.length === 0) {
                return;
            }
            
            // Hide skeleton and animation
            this.hideSkeleton();
            this.hideLoadingAnimation();
            
            // Mark as received
            this.initialFlightsReceived = true;
            
            // Trigger event for main JS to handle display
            $(document).trigger('amadex:streaming:initial', [flights]);
        }
        
        /**
         * Handle remaining flights response
         */
        handleRemainingResponse(flights) {
            if (!flights || flights.length === 0) {
                return;
            }
            
            // Trigger event for main JS to handle append
            $(document).trigger('amadex:streaming:remaining', [flights]);
        }
        
        /**
         * Append flights progressively
         */
        appendFlights(flights, animate = true) {
            if (!flights || flights.length === 0) {
                return;
            }
            
            // Trigger event - main JS will handle actual rendering
            $(document).trigger('amadex:streaming:append', [flights, animate]);
        }
        
        /**
         * Update loading progress
         */
        updateProgress(percent) {
            const $progressBar = $('#amadex-loading-progress-bar');
            if ($progressBar.length > 0) {
                $progressBar.css('width', Math.min(100, Math.max(0, percent)) + '%');
            }
        }
        
        /**
         * Destroy loader
         */
        destroy() {
            this.hideSkeleton();
            this.hideLoadingAnimation();
            this.container = null;
        }
    }
    
    // Make available globally
    window.AmadexStreamingLoader = AmadexStreamingLoader;
    
})(jQuery);
