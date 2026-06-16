/**
 * Creates a debounced version of a given function.
 * The debounced function delays invoking the original function until a certain delay has passed since the last time it was invoked.
 * If `immediate` is true, the debounced function will be invoked immediately instead of waiting for the delay.
 * @param {Function} func      - The function to be debounced.
 * @param {number}   delay     - The delay in milliseconds before the debounced function is invoked.
 * @param {boolean}  immediate - Specifies whether the debounced function should be invoked immediately.
 * @return {Function} - The debounced function.
 */
const u_debounced = (func, delay, immediate) => {
    let timerId;
    return (...args) => {
        const boundFunc = func.bind(this, ...args);
        clearTimeout(timerId);
        if (immediate && !timerId) {
            boundFunc();
        }
        const calleeFunc = immediate
            ? () => {
                  timerId = null;
              }
            : boundFunc;
        timerId = setTimeout(calleeFunc, delay);
    };
};

/**
 * Creates a throttled function that will be called at most once per delay milliseconds.
 *
 * @param {Function} func      - The function to be throttled.
 * @param {number}   delay     - The number of milliseconds to delay execution of the throttled function.
 * @param {boolean}  immediate - Whether to execute the function immediately or after the delay.
 * @return {Function} - The throttled function.
 */
const u_throttled = (func, delay, immediate) => {
    let timerId;
    return (...args) => {
        const boundFunc = func.bind(this, ...args);
        if (timerId) {
            return;
        }
        if (immediate && !timerId) {
            boundFunc();
        }
        timerId = setTimeout(() => {
            if (!immediate) {
                boundFunc();
            }
            timerId = null;
        }, delay);
    };
};

export { u_debounced, u_throttled };
