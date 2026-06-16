/**
 * Enable by toggling option in module ADVANCED SETTINGS/EFFECT in wp-admin page.
 * Module has the following options:
 *
 * ENABLED (ON/OFF):
 * Triggers IntersectionObserver on module.
 * Link: https://developer.mozilla.org/en-US/docs/Web/API/IntersectionObserver
 *
 * REPEATABLE (ON/OFF):
 * Check if the animation is repeated each time modules enters viewport.
 *
 * EFFECT (SELECT OPTION):
 * Chooses from one of predefined animation effects.
 * You can also do a custom CSS animation by adding custom class and animation it in CSS.
 *
 * Basic CSS animations:
 * Location: wp-content/themes/digitalsilk/assets/_src/sass/visuals/animate/_a-viewport.scss
 *
 * Custom CSS animations:
 * Location: wp-content/themes/digitalsilk/assets/_src/sass/project-custom/_custom__animations.scss
 *
 * THRESHOLD (STEPS SLIDER):
 * Specifies 'threshold' of the element:
 * Link: https://developer.mozilla.org/en-US/docs/Web/API/IntersectionObserver/thresholds
 *
 * MARGIN (INPUT FIELD):
 * Specifies 'rootMargin' of the element:
 * Link: https://developer.mozilla.org/en-US/docs/Web/API/IntersectionObserver/rootMargin
 *
 * Custom overrides can be added.
 * Callback function can be triggered when elements enters viewport.
 *
 * Example usage on a custom element:
 * new DSMPViewAnim({
 *         selector: '.custom-selector',
 *         class: '.custom-animation-class',
 *         repeat: 'true',
 *         threshold: '0',
 *         margin: '0px 0px -10% 0px',
 *         // Callback function when element is intersecting
 *         callback: () => {
 *             console.log('callback function');
 *       },
 *  });
 */

import { u_throttled } from './utils';
import addObserver from './u_io-anim-observer';

/**
 * @typedef {Object} ViewAnimConfig
 * @property {string} selector - CSS selector for target elements
 * @property {string} class - CSS class to add when element enters viewport
 * @property {string|boolean} repeat - Whether animation should repeat
 * @property {number|string} threshold - IntersectionObserver threshold
 * @property {string} margin - IntersectionObserver rootMargin
 * @property {Function} callback - Function to call when element enters viewport
 */

/**
 * Represents a class for animating elements in the viewport.
 * @class
 */
class DSMPViewAnim {
    /**
     * Default configuration options
     * @type {ViewAnimConfig}
     * @private
     */
    static #defaultConfig = Object.freeze({
        selector: '[data-viewport="true"]',
        repeat: 'false',
        class: 'in-view',
        threshold: 0,
        margin: '0px 0px 0px 0px',
        callback() {},
    });

    /**
     * Track if any instance has been created
     * @type {boolean}
     * @private
     */
    static #hasInviewInstance = false;

    /**
     * Collection of all active instances
     * @type {Set<DSMPViewAnim>}
     * @private
     */
    static #instances = new Set();

    /**
     * Create a new viewport animation instance
     * @param {Partial<ViewAnimConfig>} options - Configuration options
     */
    constructor(options = {}) {
        // Merge default config with provided options
        this.config = { ...DSMPViewAnim.#defaultConfig, ...options };
        
        // Store elements as a weak map for better garbage collection
        this.elements = new Map();
        
        // Cache DOM elements
        const elements = document.querySelectorAll(this.config.selector);
        if (elements.length > 0) {
            elements.forEach(el => this.elements.set(el, {
                hasObserver: false
            }));
            
            // Add body class if this is the first instance with elements
            if (!DSMPViewAnim.#hasInviewInstance) {
                document.body.classList.add('has-inview-a');
                DSMPViewAnim.#hasInviewInstance = true;
            }
        }

        // Bound methods to maintain correct 'this' context
        this.handleResize = this.#throttledInViewport.bind(this);
        
        // Add to instances collection
        DSMPViewAnim.#instances.add(this);
        
        // Initialize
        this.#setupObservers();
        this.#bindEvents();
    }

    /**
     * Set up intersection observers for all trigger elements
     * @private
     */
    #setupObservers() {
        if (this.elements.size === 0) return;

        // Use entries for better performance when iterating
        for (const [element, data] of this.elements.entries()) {
            if (data.hasObserver) continue;
            
            // Get attributes from element data attributes (if present)
            const repeat = element.dataset.viewportRepeat || this.config.repeat;
            const threshold = element.dataset.viewportThreshold || this.config.threshold;
            const margin = element.dataset.viewportMargin || this.config.margin;

            // Add observer to the element
            addObserver(
                element,
                {
                    className: this.config.class,
                    repeat,
                    threshold,
                    margin,
                    cb: this.config.callback,
                },
            );
            
            // Mark as having an observer
            data.hasObserver = true;
        }
    }

    /**
     * Throttled version of setupObservers for event handlers
     * @private
     */
    #throttledInViewport = u_throttled(() => {
        this.#setupObservers();
    }, 30);

    /**
     * Bind event listeners
     * @private
     */
    #bindEvents() {
        // Use passive event listeners for better performance
        const options = { passive: true };
        
        // Optional: Uncomment if scroll-based updates are needed
        // window.addEventListener('scroll', this.handleResize, options);
        
        window.addEventListener('resize', this.handleResize, options);
        window.addEventListener('orientationchange', this.handleResize, options);
    }

    /**
     * Remove event listeners and clean up resources
     * @public
     */
    destroy() {
        const options = { passive: true };
        
        // Optional: Uncomment if scroll listener was added
        // window.removeEventListener('scroll', this.handleResize, options);
        
        window.removeEventListener('resize', this.handleResize, options);
        window.removeEventListener('orientationchange', this.handleResize, options);
        
        // Remove from instances collection
        DSMPViewAnim.#instances.delete(this);
        
        // Clear element references
        this.elements.clear();
        
        // Update body class if this was the last instance
        if (DSMPViewAnim.#hasInviewInstance && DSMPViewAnim.#instances.size === 0) {
            // Double-check if there are any remaining elements with animations
            const remainingElements = document.querySelectorAll(this.config.selector);
            if (remainingElements.length === 0) {
                document.body.classList.remove('has-inview-a');
                DSMPViewAnim.#hasInviewInstance = false;
            }
        }
    }
    
    /**
     * Static method to destroy all instances
     * @public
     */
    static destroyAll() {
        // Create a copy of the instances to avoid modification during iteration
        const instances = [...DSMPViewAnim.#instances];
        instances.forEach(instance => instance.destroy());
    }
}

export default DSMPViewAnim;
