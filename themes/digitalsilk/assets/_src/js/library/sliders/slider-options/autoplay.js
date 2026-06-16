/**
 * Updates the autoplay settings of the slider based on the provided element and options.
 *
 * @param {HTMLElement} elem - The element containing the slider.
 * @param {Object} options - The current options of the slider.
 * @returns {Object} - The updated options with autoplay settings applied.
 */

const isAutoPlayOn = (elem, options) => {
    if (!elem) return options;

    const isAutoplay = elem.getAttribute('data-slider-autoplay');
    const isAutoplayDelay = elem.getAttribute('data-slider-autoplay-delay');

    if (isAutoplay === 'true') {
        options.autoplay = {};
        options.autoplay.disableOnInteraction = false;
        options.autoplay.delay = isAutoplayDelay ? parseInt(isAutoplayDelay, 10) : 3000;
    }

    const isSpeedOn = elem.getAttribute('data-slider-autoplay-speed');

    if (isSpeedOn) {
        options.speed = parseInt(isSpeedOn, 10);
    }

    return options;
};

export {
    isAutoPlayOn,
};
