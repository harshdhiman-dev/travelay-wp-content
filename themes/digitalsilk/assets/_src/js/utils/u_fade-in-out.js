/**
 * Default options for a duration and complete function.
 *
 * @type {Object}
 * @property {number}   duration - The default duration in milliseconds.
 * @property {Function} complete - The default complete function to be called after the animation is complete.
 */

// export const fadeIn = (el, displayStyle = 'block', smooth = true) => {
//     el.style.opacity = 0;
//     el.style.display = displayStyle;
//     if (smooth) {
//         let opacity = 0;
//         let request;
//
//         const animation = () => {
//             el.style.opacity = opacity += 0.04;
//             if (opacity >= 1) {
//                 opacity = 1;
//                 cancelAnimationFrame(request);
//             }
//         };
//
//         const rAf = () => {
//             request = requestAnimationFrame(rAf);
//             animation();
//         };
//         rAf();
//
//     } else {
//         el.style.opacity = 1;
//     }
// };
//
// export const fadeOut = (el, displayStyle = 'none', smooth = true ) => {
//     if (smooth) {
//         let opacity = el.style.opacity;
//         let request;
//
//         const animation = () => {
//             el.style.opacity = opacity -= 0.04;
//             if (opacity <= 0) {
//                 opacity = 0;
//                 el.style.display = displayStyle;
//                 cancelAnimationFrame(request);
//             }
//         };
//
//         const rAf = () => {
//             request = requestAnimationFrame(rAf);
//             animation();
//         };
//         rAf();
//
//     } else {
//         el.style.opacity = 0;
//     }
// };
const defaults = {
    duration: 100,
    complete() {

    },
};

/**
 * Animates fading effect based on given options.
 *
 * @param {Object}   options            - The options for the animation.
 * @param {number}   options.duration   - The duration of the animation in milliseconds.
 * @param {number}   [options.delay=10] - The delay between animation steps in milliseconds.
 * @param {Function} options.delta      - The function to calculate the animation value based on progress.
 * @param {Function} options.step       - The function to apply the animation value to the target element.
 * @param {Function} [options.complete] - The function to be called when animation is complete.
 *
 * @return {void}
 */
const animateFade = (options) => {
    let start = new Date;
    let id = setInterval(function () {
        let timePassed = new Date - start;
        let progress = timePassed / options.duration;
        if (progress > 1) {
            progress = 1;
        }
        options.progress = progress;
        let delta = options.delta(progress);
        options.step(delta);
        if (progress == 1) {
            clearInterval(id);
            if (typeof options.complete === 'function') {
                options.complete();
            }
        }
    }, options.delay || 10);
};

/**
 * Fades in an element.
 *
 * @param {HTMLElement} element - The element to fade in.
 * @param {Object} [options = {}] - The options for the fade in animation.
 * @param {number} [options.duration] - The duration of the fade in animation.
 * @param {function} [options.complete] - The callback function to execute when the fade in animation is complete.
 */
export const u_fadeIn = (element, options = {}) => {
    if (typeof options.duration === 'undefined') {
        options.duration = defaults.duration;
    }
    let to = 0;
    animateFade({
        duration: options.duration,
        delta(progress) {
            progress = this.progress;
            return easings.swing(progress);
        },
        complete: options.complete,
        step(delta) {
            element.style.opacity = to + delta;
        },
    });
};

/**
 * Fades out an element's opacity over a given duration.
 *
 * @param {HTMLElement} element - The element to fade out.
 * @param {Object} [options] - The fade out options.
 * @param {number} [options.duration] - The duration of the fade out animation in milliseconds.
 *                                      If not provided, a default duration will be used.
 * @param {Function} [options.complete] - A callback function to be executed when the animation completes.
 * @return {void}
 */
export const u_fadeOut = (element, options = {}) => {
    if (typeof options.duration === 'undefined') {
        options.duration = defaults.duration;
    }
    let to = 1;
    animateFade({
        duration: options.duration,
        delta(progress) {
            progress = this.progress;
            return easings.swing(progress);
        },
        complete: options.complete,
        step(delta) {
            element.style.opacity = to - delta;
        },
    });
};

/**
 * A collection of easing functions.
 * @type {Object}
 */
const easings = {
    linear: function (progress) {
        return progress;
    },
    quadratic: function (progress) {
        return Math.pow(progress, 2);
    },
    swing: function (progress) {
        return 0.5 - Math.cos(progress * Math.PI) / 2;
    },
    circ: function (progress) {
        return 1 - Math.sin(Math.acos(progress));
    },
    back: function (progress, x) {
        return Math.pow(progress, 2) * ((x + 1) * progress - x);
    },
    bounce: function (progress) {
        for (var a = 0, b = 1, result; 1; a += b, b /= 2) {
            if (progress >= (7 - 4 * a) / 11) {
                return -Math.pow((11 - 6 * a - 11 * progress) / 4, 2) + Math.pow(b, 2);
            }
        }
    },
    elastic: function (progress, x) {
        return Math.pow(2, 10 * (progress - 1)) * Math.cos(20 * Math.PI * x / 3 * progress);
    },
};
