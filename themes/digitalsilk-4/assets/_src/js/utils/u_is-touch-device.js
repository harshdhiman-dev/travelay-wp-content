import { u_throttled } from './utils';

/**
 * Determines if the device has touch support.
 *
 * @return {boolean} Returns true if the device has touch support, otherwise returns false.
 */
const u_isTouchDevice = () => {
    return (
        !!(typeof window !== 'undefined' &&
            ('ontouchstart' in window ||
                (window.DocumentTouch &&
                    typeof document !== 'undefined' &&
                    document instanceof window.DocumentTouch))) ||
        !!(typeof navigator !== 'undefined' &&
            (navigator.maxTouchPoints || navigator.msMaxTouchPoints))
    );
};

/**
 * Sets the appropriate CSS class to the HTML element based on whether the device has touch capability or not.
 *
 * @function isTouchHtmlUtil
 * @return {void}
 */
const isTouchHtmlUtil = () => {
    const touch = u_isTouchDevice();
    const html = document.getElementsByTagName('html')[0];

    // if true, add touch-device to html, otherwise no-touch-device
    if (touch) {
        html.classList.remove('no-touch-device');
        html.classList.add('touch-device');
    } else {
        html.classList.remove('touch-device');
        html.classList.add('no-touch-device');
    }
};

/**
 * Adds touch event to HTML element.
 * @function u_addTouchToHtml
 * @return {void}
 */
const u_addTouchToHtml = () => {
    isTouchHtmlUtil();

    // throttle the function
    const throttleIsTouch = u_throttled(() => {
        isTouchHtmlUtil();
    }, 300);

    // bind resize event
    window.addEventListener('resize', () => {
        throttleIsTouch();
    });
};

export {
    u_isTouchDevice,
    u_addTouchToHtml,
};
