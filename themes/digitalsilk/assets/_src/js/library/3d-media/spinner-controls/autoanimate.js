/**
 * Image Spinner Options - auto animation
 */

const isAnimateOn = (elem, options) => {
    if (!elem) return options;

    let isAnimate = elem.getAttribute('data-spinner-autoanimate');

    if (isAnimate === 'true') {
        options.animate = true;
    }

    return options;
}


export {
    isAnimateOn
}