/**
 * Amadex Virtual Scrolling
 * 
 * Renders only visible items for performance
 * 
 * @package Amadex
 * @since 1.1.0
 */

(function($) {
    'use strict';
    
    /**
     * Virtual Scroll Manager
     */
    class AmadexVirtualScroll {
        constructor(container, itemHeight, buffer = 3) {
            this.container = $(container);
            this.itemHeight = itemHeight;
            this.buffer = buffer; // Number of items to render outside viewport
            this.items = [];
            this.scrollTop = 0;
            this.containerHeight = 0;
            this.visibleCount = 0;
            this.startIndex = 0;
            this.endIndex = 0;
            this.isEnabled = false;
            
            this.init();
        }
        
        init() {
            if (this.container.length === 0) {
                console.warn('Amadex Virtual Scroll: Container not found');
                return;
            }
            
            this.containerHeight = this.container.height();
            
            // Create virtual scroll wrapper
            if (!this.container.find('.amadex-virtual-scroll-wrapper').length) {
                this.container.html('<div class="amadex-virtual-scroll-wrapper"></div>');
            }
            this.wrapper = this.container.find('.amadex-virtual-scroll-wrapper');
            
            // Throttle scroll event for performance
            let scrollTimeout;
            this.container.on('scroll', () => {
                if (scrollTimeout) {
                    clearTimeout(scrollTimeout);
                }
                scrollTimeout = setTimeout(() => {
                    this.onScroll();
                }, 16); // ~60fps
            });
            
            // Handle window resize
            $(window).on('resize', () => {
                this.containerHeight = this.container.height();
                this.render();
            });
        }
        
        setItems(items) {
            this.items = items || [];
            this.render();
        }
        
        render() {
            if (!this.items || this.items.length === 0) {
                this.wrapper.empty();
                return;
            }
            
            // If items count is small, render all (no need for virtual scrolling)
            if (this.items.length <= 30) {
                this.isEnabled = false;
                this.renderAll();
                return;
            }
            
            // Enable virtual scrolling for large lists
            this.isEnabled = true;
            this.updateVisibleRange();
            this.renderVisibleItems();
        }
        
        renderAll() {
            // Render all items normally (for small lists)
            this.wrapper.empty();
            this.wrapper.css({
                'height': 'auto',
                'position': 'relative'
            });
            
            // Items will be rendered by normal displayFlightResults function
            // This is just a fallback
        }
        
        updateVisibleRange() {
            if (!this.isEnabled) {
                return;
            }
            
            this.scrollTop = this.container.scrollTop();
            this.containerHeight = this.container.height() || window.innerHeight;
            
            // Calculate visible range
            this.startIndex = Math.max(0, Math.floor(this.scrollTop / this.itemHeight) - this.buffer);
            this.visibleCount = Math.ceil(this.containerHeight / this.itemHeight);
            this.endIndex = Math.min(this.items.length - 1, this.startIndex + this.visibleCount + (this.buffer * 2));
        }
        
        renderVisibleItems() {
            if (!this.isEnabled || this.items.length === 0) {
                return;
            }
            
            // Calculate total height for scrollbar
            const totalHeight = this.items.length * this.itemHeight;
            const offsetY = this.startIndex * this.itemHeight;
            
            // Set wrapper height
            this.wrapper.css({
                'height': totalHeight + 'px',
                'position': 'relative'
            });
            
            // Create content container for visible items
            let $content = this.wrapper.find('.amadex-virtual-scroll-content');
            if ($content.length === 0) {
                $content = $('<div class="amadex-virtual-scroll-content"></div>');
                this.wrapper.append($content);
            }
            
            // Position content container
            $content.css({
                'position': 'absolute',
                'top': offsetY + 'px',
                'left': '0',
                'right': '0'
            });
            
            // Clear and render visible items
            $content.empty();
            
            // Render visible items (will be populated by displayFlightResults)
            // This is a placeholder - actual flight cards will be added by the main JS
            for (let i = this.startIndex; i <= this.endIndex; i++) {
                if (this.items[i]) {
                    // Create placeholder - actual rendering done by displayFlightResults
                    const $item = $('<div class="amadex-virtual-scroll-item" data-index="' + i + '"></div>');
                    $item.css('height', this.itemHeight + 'px');
                    $content.append($item);
                }
            }
        }
        
        onScroll() {
            if (!this.isEnabled) {
                return;
            }
            
            const oldStartIndex = this.startIndex;
            const oldEndIndex = this.endIndex;
            
            this.updateVisibleRange();
            
            // Only re-render if visible range changed significantly
            if (Math.abs(this.startIndex - oldStartIndex) > this.buffer || 
                Math.abs(this.endIndex - oldEndIndex) > this.buffer) {
                this.renderVisibleItems();
            }
        }
        
        scrollToIndex(index) {
            if (index < 0 || index >= this.items.length) {
                return;
            }
            
            const scrollTop = index * this.itemHeight;
            this.container.animate({
                scrollTop: scrollTop
            }, 300);
        }
        
        destroy() {
            this.container.off('scroll');
            $(window).off('resize');
            this.wrapper.remove();
            this.items = [];
        }
        
        /**
         * Throttle function for performance
         */
        throttle(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    }
    
    // Make available globally
    window.AmadexVirtualScroll = AmadexVirtualScroll;
    
})(jQuery);
