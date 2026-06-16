/**
 * Calculates the quadratic ease-out interpolation value.
 *
 * @param {number} t - The current time.
 * @param {number} b - The start value.
 * @param {number} c - The change in value.
 * @param {number} d - The duration.
 * @returns {number} The interpolated value.
 */

export const easeOutQuad = (t, b, c, d) => {
    return -c * (t /= d) * (t - 2) + b;
};
/**
 * Calculates the easing value for an animation using the easeInQuad equation.
 *
 * @function easeInQuad
 * @param {number} t - The current time of the animation.
 * @param {number} b - The start value of the animation.
 * @param {number} c - The change in value from start to end of the animation.
 * @param {number} d - The total duration of the animation.
 * @returns {number} - The eased value based on the input parameters.
 */
export const easeInQuad = (t, b, c, d) => {
    return c * (t /= d) * t + b;
};
/**
 * Eases the value of t using the quadratic easing equation with ease-in-out effect.
 *
 * @param {number} t - The current time.
 * @param {number} b - The starting value.
 * @param {number} c - The change in value.
 * @param {number} d - The total duration.
 * @returns {number} - The eased value.
 */
export const easeInOutQuad = (t, b, c, d) => {
    if ((t /= d / 2) < 1)
        return c / 2 * t * t + b;
    return -c / 2 * ((--t) * (t - 2) - 1) + b;
};
/**
 * Applies cubic ease-in interpolation to a value.
 *
 * @param {number} t - The current time.
 * @param {number} b - The initial value.
 * @param {number} c - The change in value over time.
 * @param {number} d - The total duration of the interpolation.
 * @returns {number} - The interpolated value at the current time.
 */
export const easeInCubic = (t, b, c, d) => {
    return c * (t /= d) * t * t + b;
};
/**
 * Calculates the easing progression using the cubic easing function.
 *
 * @param {number} t - The current time.
 * @param {number} b - The initial value.
 * @param {number} c - The change in value.
 * @param {number} d - The duration of the easing.
 * @returns {number} - The eased value at the given time.
 */
export const easeOutCubic = (t, b, c, d) => {
    return c * ((t = t / d - 1) * t * t + 1) + b;
};
/**
 * Applies ease-in ease-out cubic easing to a value.
 *
 * @param {number} t - The current time or progress, usually between 0 and `d` (duration).
 * @param {number} b - The starting value.
 * @param {number} c - The change in value.
 * @param {number} d - The duration or total progress time.
 *
 * @returns {number} - The resulting value after applying ease-in ease-out cubic easing.
 */
export const easeInOutCubic = (t, b, c, d) => {
    if ((t /= d / 2) < 1)
        return c / 2 * t * t * t + b;
    return c / 2 * ((t -= 2) * t * t + 2) + b;
};
/**
 * Applies the ease-in quart easing function to a value.
 *
 * @param {number} t - The current time or progress.
 * @param {number} b - The starting value.
 * @param {number} c - The change in value.
 * @param {number} d - The total duration or distance.
 * @returns {number} The eased value.
 */
export const easeInQuart = (t, b, c, d) => {
    return c * (t /= d) * t * t * t + b;
};
/**
 * Calculates the easing value using the easeOutQuart equation.
 *
 * @param {number} t - The current time (in milliseconds).
 * @param {number} b - The starting value.
 * @param {number} c - The change in value.
 * @param {number} d - The total duration (in milliseconds).
 * @returns {number} - The calculated easing value.
 */
export const easeOutQuart = (t, b, c, d) => {
    return -c * ((t = t / d - 1) * t * t * t - 1) + b;
};
/**
 * Calculates the easing value for a quartic ease-in-out function.
 *
 * @param {number} t - The current time.
 * @param {number} b - The starting value.
 * @param {number} c - The change in value.
 * @param {number} d - The total duration.
 * @returns {number} - The calculated easing value.
 */
export const easeInOutQuart = (t, b, c, d) => {
    if ((t /= d / 2) < 1)
        return c / 2 * t * t * t * t + b;
    return -c / 2 * ((t -= 2) * t * t * t - 2) + b;
};
/**
 * Applies the ease-in quint function to calculate the value based on the provided parameters.
 *
 * @param {number} t - The current time.
 * @param {number} b - The starting value.
 * @param {number} c - The change in value.
 * @param {number} d - The duration of the animation.
 * @returns {number} - The calculated value.
 */
export const easeInQuint = (t, b, c, d) => {
    return c * (t /= d) * t * t * t * t + b;
};
/**
 * Applies an easing equation to calculate the easing value based on the time elapsed.
 *
 * @param {number} t - The current time elapsed.
 * @param {number} b - The initial value.
 * @param {number} c - The change in value.
 * @param {number} d - The duration.
 * @returns {number} - The eased value at the current time.
 */
export const easeOutQuint = (t, b, c, d) => {
    return c * ((t = t / d - 1) * t * t * t * t + 1) + b;
};
/**
 * Computes the easing value for the easeInOutQuint easing function.
 *
 * @param {number} t - The current time value.
 * @param {number} b - The starting value.
 * @param {number} c - The change in value.
 * @param {number} d - The total duration.
 * @returns {number} - The computed easing value.
 */
export const easeInOutQuint = (t, b, c, d) => {
    if ((t /= d / 2) < 1)
        return c / 2 * t * t * t * t * t + b;
    return c / 2 * ((t -= 2) * t * t * t * t + 2) + b;
};
/**
 * Calculate easing value using the Sinusoidal In function.
 *
 * @param {number} t - Current time.
 * @param {number} b - Starting value.
 * @param {number} c - Change in value.
 * @param {number} d - Duration.
 * @returns {number} - Eased value at the given time.
 */
export const easeInSine = (t, b, c, d) => {
    return -c * Math.cos(t / d * (Math.PI / 2)) + c + b;
};
/**
 * Applies the easing function easeOutSine to a value.
 *
 * @param {number} t - The current time.
 * @param {number} b - The start value.
 * @param {number} c - The change in value.
 * @param {number} d - The duration.
 * @returns {number} A new value based on the easing function.
 */
export const easeOutSine = (t, b, c, d) => {
    return c * Math.sin(t / d * (Math.PI / 2)) + b;
};
/**
 * Calculates the easing value using the easeInOutSine function.
 *
 * @param {number} t - The current time.
 * @param {number} b - The starting value.
 * @param {number} c - The change in value.
 * @param {number} d - The duration.
 * @returns {number} - The eased value.
 */
export const easeInOutSine = (t, b, c, d) => {
    return -c / 2 * (Math.cos(Math.PI * t / d) - 1) + b;
};
/**
 * Calculates the easing value using the exponential function.
 *
 * @param {number} t - The current time.
 * @param {number} b - The start value.
 * @param {number} c - The change in value.
 * @param {number} d - The duration.
 * @returns {number} - The calculated eased value.
 */
export const easeInExpo = (t, b, c, d) => {
    return (t == 0) ? b : c * Math.pow(2, 10 * (t / d - 1)) + b;
};
/**
 * Eases out the value of a property over time using the 'Expo' easing function.
 * @param {number} t - The current time (in milliseconds) of the easing animation.
 * @param {number} b - The initial value of the property.
 * @param {number} c - The total change in value of the property.
 * @param {number} d - The total duration (in milliseconds) of the easing animation.
 * @returns {number} - The calculated value of the property at the given time.
 */
export const easeOutExpo = (t, b, c, d) => {
    return (t == d) ? b + c : c * (-Math.pow(2, -10 * t / d) + 1) + b;
};
/**
 * Easing function that creates an exponential interpolation effect with ease-in ease-out behavior.
 *
 * @param {number} t - The current time value.
 * @param {number} b - The start value.
 * @param {number} c - The change in value.
 * @param {number} d - The duration or total time.
 * @returns {number} - The interpolated value based on the easing function.
 */
export const easeInOutExpo = (t, b, c, d) => {
    if (t == 0)
        return b;
    if (t == d)
        return b + c;
    if ((t /= d / 2) < 1)
        return c / 2 * Math.pow(2, 10 * (t - 1)) + b;
    return c / 2 * (-Math.pow(2, -10 * --t) + 2) + b;
};
/**
 * Calculates the eased value for a given time using the Circ easing function.
 *
 * @param {number} t - The current time.
 * @param {number} b - The starting value.
 * @param {number} c - The change in value.
 * @param {number} d - The total duration.
 * @returns {number} - The eased value at the given time.
 */
export const easeInCirc = (t, b, c, d) => {
    return -c * (Math.sqrt(1 - (t /= d) * t) - 1) + b;
};
/**
 * Calculates the eased out circular value based on the given parameters.
 *
 * @param {number} t - The current time value.
 * @param {number} b - The starting value.
 * @param {number} c - The change in value.
 * @param {number} d - The duration or total time.
 * @returns {number} - The eased out circular value.
 */
export const easeOutCirc = (t, b, c, d) => {
    return c * Math.sqrt(1 - (t = t / d - 1) * t) + b;
};
/**
 * Calculates the easing value using the easeInOutCirc algorithm.
 *
 * @param {number} t - The current time value.
 * @param {number} b - The start value.
 * @param {number} c - The change in value.
 * @param {number} d - The total duration.
 * @returns {number} - The calculated easing value.
 */
export const easeInOutCirc = (t, b, c, d) => {
    if ((t /= d / 2) < 1)
        return -c / 2 * (Math.sqrt(1 - t * t) - 1) + b;
    return c / 2 * (Math.sqrt(1 - (t -= 2) * t) + 1) + b;
};
/**
 * Calculates the eased-in elastic value for a given point in time.
 *
 * @param {number} t - The current time.
 * @param {number} b - The starting value.
 * @param {number} c - The change in value.
 * @param {number} d - The total duration.
 * @returns {number} - The eased-in elastic value.
 */
export const easeInElastic = (t, b, c, d) => {
    var s = 1.70158;
    var p = 0;
    var a = c;
    if (t == 0)
        return b;
    if ((t /= d) == 1)
        return b + c;
    if (!p)
        p = d * .3;
    if (a < Math.abs(c)) {
        a = c;
        var s = p / 4;
    } else
        var s = p / (2 * Math.PI) * Math.asin(c / a);
    return -(a * Math.pow(2, 10 * (t -= 1)) * Math.sin((t * d - s) * (2 * Math.PI) / p)) + b;
};
/**
 * Calculates the easing value using the easeOutElastic easing function.
 * @param {number} t - The current time/progress.
 * @param {number} b - The starting value.
 * @param {number} c - The change in value.
 * @param {number} d - The total duration/time.
 * @returns {number} - The calculated easing value.
 */
export const easeOutElastic = (t, b, c, d) => {
    var s = 1.70158;
    var p = 0;
    var a = c;
    if (t == 0)
        return b;
    if ((t /= d) == 1)
        return b + c;
    if (!p)
        p = d * .3;
    if (a < Math.abs(c)) {
        a = c;
        var s = p / 4;
    } else
        var s = p / (2 * Math.PI) * Math.asin(c / a);
    return a * Math.pow(2, -10 * t) * Math.sin((t * d - s) * (2 * Math.PI) / p) + c + b;
};
/**
 * Calculate the eased value using the easeInOutElastic equation.
 *
 * @param {number} t - The current time.
 * @param {number} b - The starting value.
 * @param {number} c - The change in value.
 * @param {number} d - The total duration.
 * @return {number} - The eased value at the current time.
 */
export const easeInOutElastic = (t, b, c, d) => {
    var s = 1.70158;
    var p = 0;
    var a = c;
    if (t == 0)
        return b;
    if ((t /= d / 2) == 2)
        return b + c;
    if (!p)
        p = d * (.3 * 1.5);
    if (a < Math.abs(c)) {
        a = c;
        var s = p / 4;
    } else
        var s = p / (2 * Math.PI) * Math.asin(c / a);
    if (t < 1)
        return -.5 * (a * Math.pow(2, 10 * (t -= 1)) * Math.sin((t * d - s) * (2 * Math.PI) / p)) + b;
    return a * Math.pow(2, -10 * (t -= 1)) * Math.sin((t * d - s) * (2 * Math.PI) / p) * .5 + c + b;
};
/**
 * Calculates easing using the Back equation with ease in effect.
 *
 * @param {number} t - The current time.
 * @param {number} b - The starting value.
 * @param {number} c - The change in value.
 * @param {number} d - The total duration.
 * @param {number} [s=1.70158] - The overshoot amount.
 * @returns {number} - The calculated value based on the easing equation.
 */
export const easeInBack = (t, b, c, d, s = 1.70158) => {
    return c * (t /= d) * t * ((s + 1) * t - s) + b;
};
/**
 * Calculates the easing value using the easeOutBack equation.
 *
 * @param {number} t - The current time value.
 * @param {number} b - The begin value.
 * @param {number} c - The change in value.
 * @param {number} d - The duration of the animation.
 * @param {number} [s=1.70158] - The overshoot amount.
 * @returns {number} - The calculated easing value.
 */
export const easeOutBack = (t, b, c, d, s = 1.70158) => {
    return c * ((t = t / d - 1) * t * ((s + 1) * t + s) + 1) + b;
};
/**
 * Eases the animation in and out with a back style equation.
 *
 * @param {number} t - The current time in the animation (between 0 and duration).
 * @param {number} b - The starting value of the animation.
 * @param {number} c - The change in value of the animation.
 * @param {number} d - The duration of the animation.
 * @param {number} [s=1.70158] - The parameter controlling the amount of overshoot.
 * @returns {number} - The new value of the animation at the current time.
 */
export const easeInOutBack = (t, b, c, d, s = 1.70158) => {
    if ((t /= d / 2) < 1)
        return c / 2 * (t * t * (((s *= (1.525)) + 1) * t - s)) + b;
    return c / 2 * ((t -= 2) * t * (((s *= (1.525)) + 1) * t + s) + 2) + b;
};
/**
 * Applies the "easeInBounce" easing function to a given time value.
 *
 * @param {number} t - The current time value.
 * @param {number} b - The initial value.
 * @param {number} c - The change in value.
 * @param {number} d - The total duration.
 * @returns {number} The modified value based on the "easeInBounce" easing function.
 */
export const easeInBounce = (t, b, c, d) => {
    return c - easeOutBounce(d - t, 0, c, d) + b;
};
/**
 * Calculates the easing value using the easeOutBounce easing function.
 * The easeOutBounce function starts fast and slows down as it reaches the end, creating a bouncing effect.
 *
 * @param {number} t - The current time in milliseconds.
 * @param {number} b - The starting value.
 * @param {number} c - The change in value.
 * @param {number} d - The duration in milliseconds.
 * @returns {number} - The eased value at the current time.
 */
export const easeOutBounce = (t, b, c, d) => {
    if ((t /= d) < (1 / 2.75)) {
        return c * (7.5625 * t * t) + b;
    } else if (t < (2 / 2.75)) {
        return c * (7.5625 * (t -= (1.5 / 2.75)) * t + .75) + b;
    } else if (t < (2.5 / 2.75)) {
        return c * (7.5625 * (t -= (2.25 / 2.75)) * t + .9375) + b;
    } else {
        return c * (7.5625 * (t -= (2.625 / 2.75)) * t + .984375) + b;
    }
};
/**
 * Applies the ease-in-out-bounce easing function to a given value.
 *
 * @param {number} t - The current time or step.
 * @param {number} b - The starting value.
 * @param {number} c - The change in value.
 * @param {number} d - The total duration or number of steps.
 * @returns {number} - The new value after applying the easing function.
 */
export const easeInOutBounce = (t, b, c, d) => {
    if (t < d / 2)
        return easeInBounce(t * 2, 0, c, d) * .5 + b;
    return easeOutBounce(t * 2 - d, 0, c, d) * .5 + c * .5 + b;
};
// # sourceMappingURL=index.js.map
