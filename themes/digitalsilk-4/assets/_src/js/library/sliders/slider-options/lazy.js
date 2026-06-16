/**
 * Determines if lazy loading is enabled.
 * TODO: missing option for data option, create preloader div via js, and change image src to data-src, right now all this done manually
 * @param {HTMLElement} elem - The DOM element to check.
 * @param {Object} options - The options object.
 * @returns {Object} - The updated options object with lazy loading settings.
 */

const isLazyLoadOn = (elem, options) => {
    if (!elem) return options;

    // let isLazyLoad = elem.getAttribute('data-slider-lazy');

    options.preloadImages = false;
    options.lazy = {};
    options.lazy.loadPrevNext = true;
    options.loadOnTransitionStart = true;

    return options;
};

export {
    isLazyLoadOn,
};
