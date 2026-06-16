/**
 * Break Points Options
 */
import { u_parseBool } from '../../../utils/u_types';

/**
 * Evaluates whether breakpoints are enabled or not.
 * @param {Element} elem - The element to check for breakpoints.
 * @param {object} options - The options object to be returned.
 * @returns {object} - The modified options object.
 */
const isBreakpointsOn = (elem, options) => {
    if (!elem) return options;

    const noColumns = parseInt(elem.getAttribute('data-slider-columns'), 10);
    const noColumnsMobile = parseFloat(elem.getAttribute('data-slider-columns-mobile'), 10) || 1;
    const columnsGap = parseInt(elem.getAttribute('data-slider-columns-gap'), 10) || 0;
    const columnsMobileGap = parseInt(elem.getAttribute('data-slider-columns-mobile-gap'), 10) || 10;
    const isCentered = u_parseBool(elem.getAttribute('data-slider-centered')) || false;

    if (noColumns) {
        options.slidesPerView = noColumns;
        options.breakpoints = {
            320: {
                slidesPerView: noColumnsMobile,
                spaceBetween: columnsMobileGap,
                centeredSlides: isCentered,
                centeredSlidesBounds: (noColumnsMobile > 1 && noColumnsMobile < 2 && isCentered),
            },

            576: {
                slidesPerView: noColumns > 3 ? noColumnsMobile > 2 ? noColumnsMobile : 2 : 1,
                spaceBetween: columnsMobileGap,
                centeredSlides: isCentered,
                centeredSlidesBounds: (noColumnsMobile > 1 && noColumnsMobile < 2 && isCentered),
            },
            768: {
                slidesPerView: noColumns > 3 ? 3 : (noColumns === 1 ? 1 : 2),
                spaceBetween: columnsMobileGap,
            },
			1112: {
                slidesPerView: noColumns,
                spaceBetween: columnsGap,
            },
        };
    }

    return options;
};

export {
    isBreakpointsOn,
};
