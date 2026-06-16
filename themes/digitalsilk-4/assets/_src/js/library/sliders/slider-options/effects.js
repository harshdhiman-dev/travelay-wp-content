/**
 * Determines if a slider effect should be applied to an element based on its attributes.
 *
 * @param {Element} elem - The element to check for the data-slider-effect-transition attribute.
 * @param {Object} options - The options object to modify.
 * @returns {Object} - The modified options object.
 */
const isEffectOn = (elem, options) => {
    if (!elem) return options;

    const isEffect = elem.getAttribute('data-slider-effect-transition');

    options.effect = {};
    switch (isEffect) {

        case 'fade':
            options.effect = 'fade';
            options.fadeEffect = {};
            options.fadeEffect.crossFade = true;
            break;
        case 'cube':
            options.effect = 'cube';
            break;
        case 'coverflow':
            options.effect = 'coverflow';
            break;
        case 'cards':
            options.effect = 'cards';
            break;
        case 'flip':
            options.effect = 'flip';
            break;
    }

    return options;
};

export {
    isEffectOn,
};
