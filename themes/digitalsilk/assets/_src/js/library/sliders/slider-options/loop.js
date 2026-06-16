/**
 * Check if loop is enabled for an element and update options accordingly.
 *
 * @param {HTMLElement} elem - The element to check for loop attribute.
 * @param {Object} options - The options object to be updated.
 * @returns {Object} Updated options object.
 */

const isLoopOn = (elem, options) => {
    if (!elem) return options;

    let isLoop = elem.getAttribute('data-slider-loop');

    if (isLoop === 'true') {
        options.loop = true;
    }

    return options;
};

export {
    isLoopOn,
};
