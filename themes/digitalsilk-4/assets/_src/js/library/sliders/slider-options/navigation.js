/**
 * Checks if navigation is enabled for a given element and sets it up in the options object.
 *
 * @param {HTMLElement} elem - The element to check for navigation.
 * @param {Object} options - The options object for the swiper.
 * @param {string} basename - The base name used for generating the IDs.
 * @param {number} currentID - The current ID used for generating the IDs.
 *
 * @returns {Object} - The updated options object.
 */

const isNavigationOn = (elem, options, basename, currentID) => {
    let nextEl = '.swiper-button-next';
    let prevEl = '.swiper-button-prev';
    let nextID, prevID, sliderNext, sliderPrev;
    if (!elem) return options;

    let isNavigation = elem.getAttribute('data-slider-navigation');

    if (isNavigation) {
        options.navigation = {};

        if (basename && (typeof currentID !== 'undefined')) {
            nextID = `${basename}-next-${currentID}`;
            prevID = `${basename}-prev-${currentID}`;
        }

        let sliderParent = elem.closest('.m-slider');
        if (sliderParent) {
            sliderNext = sliderParent.querySelector(nextEl);
            sliderPrev = sliderParent.querySelector(prevEl);
        }
        if (sliderNext && nextID) {
            sliderNext.setAttribute('id', nextID);
            options.navigation.nextEl = `#${nextID}`;
        }
        if (sliderPrev && prevID) {
            sliderPrev.setAttribute('id', prevID);
            options.navigation.prevEl = `#${prevID}`;
        }

        options.keyboard = {};
        options.keyboard.enabled = true;

    } else {
        options.navigation = false;
    }

    return options;
};

export {
    isNavigationOn,
};
