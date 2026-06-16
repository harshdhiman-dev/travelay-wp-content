/**
 * https://github.com/janrembold/es6-slide-up-down
 *
 * usage with easings
 *
 */

import { u_extend } from './u_object_extend';
import { u_isInteger } from './u_types';

/**
 * Default values for animation configuration.
 * @type {{duration: number, easing: (function(number, number, number, number): number), display: string, complete: Function}}
 */
const defaults = {
    duration: 250,
    easing: (currentTime, startValue, diffValue, dureation) => {
        return -diffValue * (currentTime /= dureation) * (currentTime - 2) + startValue;
    },
    display: 'block',
    complete() {

    },
};

/**
 * Directions constant object.
 *
 * @constant
 * @type {Object<number>}
 * @property {number} OPEN  - Represents the open direction.
 * @property {number} CLOSE - Represents the close direction.
 */
const directions = {
    OPEN: 1,
    CLOSE: 2,
};

/**
 * Sets animation styles for an element.
 *
 * @param {HTMLElement} element               - The element to set animation styles on.
 * @param {string}      [displayType='block'] - The display type for the element.
 *                                            Default is 'block'.
 * @return {void}
 */
const setElementAnimationStyles = (element, displayType = 'block') => {
    element.style.display = displayType === 'flex' ? 'flex' : 'block';
    element.style.overflow = 'hidden';
    element.style.marginTop = '0';
    element.style.marginBottom = '0';
    element.style.paddingTop = '0';
    element.style.paddingBottom = '0';
};
/**
 * Remove animation styles from an element.
 *
 * This function removes specific animation styles from an element by setting
 * their values to null. The styles that are removed include height, overflow,
 * marginTop, marginBottom, paddingTop, and paddingBottom.
 *
 * @param {HTMLElement} element - The element from which the styles should be removed.
 * @return {void}
 */
const removeElementAnimationStyles = (element) => {
    element.style.height = null;
    element.style.overflow = null;
    element.style.marginTop = null;
    element.style.marginBottom = null;
    element.style.paddingTop = null;
    element.style.paddingBottom = null;
};

/**
 * Animate the height of an element.
 *
 * @param {HTMLElement} element                - The element to animate.
 * @param {Object}      options                - The animation options.
 * @param {number}      options.startTime      - The start time of the animation.
 * @param {number}      options.duration       - The duration of the animation.
 * @param {Function}    options.easing         - The easing function for the animation.
 * @param {number}      options.startingHeight - The starting height of the element.
 * @param {number}      options.distanceHeight - The distance of the height change.
 * @param {string}      options.direction      - The direction of the animation (OPEN or CLOSE).
 * @param {string}      options.display        - The display property of the element.
 * @param {Function}    options.complete       - The callback function to be called when the animation is complete.
 * @param {number}      now                    - The current timestamp.
 */
const animate = (element, options, now) => {
    if (!options.startTime) {
        options.startTime = now;
    }
    const currentTime = now - options.startTime;
    const animationContinue = currentTime < options.duration;
    const newHeight = options.easing(
        currentTime,
        options.startingHeight,
        options.distanceHeight,
        options.duration,
    );

    if (animationContinue) {
        element.style.height = `${newHeight.toFixed(2)}px`;
        window.requestAnimationFrame((timestamp) => animate(element, options, timestamp));
    } else {
        if (options.direction === directions.CLOSE) {
            element.style.display = 'none';
        }
        if (options.direction === directions.OPEN) {
            element.style.display = options.display === 'flex' ? 'flex' : 'block';
        }
        removeElementAnimationStyles(element);
        if (typeof options.complete === 'function') {
            options.complete();
        }
    }
};

/**
 * Slides up an element with animation.
 *
 * @param {HTMLElement} element         - The target element to slide up.
 * @param {Object}      [args]          - The optional arguments for customization.
 * @param {number}      [args.duration] - The duration of the animation in milliseconds.
 * @param {string}      [args.display]  - The display CSS property of the element after sliding up. Defaults to 'block'.
 */
export const u_slideUp = (element, args = {}) => {
    if (u_isInteger(args)) {
        args = { duration: args };
    }
    const options = u_extend(defaults, args);
    const displayType = options.display;
    options.direction = directions.CLOSE;
    options.to = 0;
    options.startingHeight = element.scrollHeight;
    options.distanceHeight = -options.startingHeight;
    setElementAnimationStyles(element, displayType);
    window.requestAnimationFrame((timestamp) => animate(element, options, timestamp));
};
/**
 * Performs a slide down animation on the given element.
 *
 * @param {HTMLElement} element         - The element to slide down.
 * @param {Object}      [args={}]       - Additional options for the animation.
 * @param {number}      [args.duration] - The duration of the animation in milliseconds.
 * @param {string}      [args.display]  - The desired CSS `display` value for the element after the animation completes.
 */
export const u_slideDown = (element, args = {}) => {
    if (u_isInteger(args)) {
        args = { duration: args };
    }
    element.style.height = '0px';
    const options = u_extend(defaults, args);
    const displayType = options.display;
    setElementAnimationStyles(element, displayType);
    options.direction = directions.OPEN;
    options.to = element.scrollHeight;
    options.startingHeight = 0;
    options.distanceHeight = options.to;
    window.requestAnimationFrame((timestamp) => animate(element, options, timestamp));
};

/**
 * Toggles the visibility of an element using slide animation.
 * If the element is currently hidden, it will slide down.
 * If the element is currently visible, it will slide up.
 *
 * @param {HTMLElement} element - The HTML element to slide toggle.
 * @param {Object}      args    - Optional arguments for the slide animation.
 * @return {Promise} - A promise that resolves when the slide animation is complete.
 */
export const u_slideToggle = (element, args = {}) => {
    if (window.getComputedStyle(element).display === 'none') {
        return u_slideDown(element, args);
    }
        return u_slideUp(element, args);

};
