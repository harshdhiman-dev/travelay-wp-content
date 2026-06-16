/**
 * Advanced slider type
 */

import { isAutoPlayOn } from '../../library/sliders/slider-options/autoplay';
import { isLazyLoadOn } from '../../library/sliders/slider-options/lazy';
import { isBreakpointsOn } from '../../library/sliders/slider-options/breakpoints';
import { isNavigationOn } from '../../library/sliders/slider-options/navigation';

import { isLoopOn } from '../../library/sliders/slider-options/loop';
import { isEffectOn } from '../../library/sliders/slider-options/effects';
import SwiperWithCircularTabs from '../../library/sliders/swiper-with-circular-tabs';
import { isPaginationOn } from '../../library/sliders/slider-options/pagination';
import { u_parseBool } from '../../utils/u_types';

// config selectors only here
/**
 * Represents the name of the advanced feature in a JavaScript circular implementation.
 *
 * @type {string}
 * @constant
 */
const advancedName = 'js-circular-adv';
/**
 * Represents the selector for the advanced slider.
 * @constant {string}
 */
const advSliderSel = '.js-circular-adv';
/**
 * Represents the advanced slider tabs variable.
 *
 * @type {string}
 * @constant
 */
const advSliderTabs = '.l-slider-nav';


/**
 * Initializes circular sliders.
 */
const circularSliders = () => {
    // loop through sliders and add ID's to it

    // find those selectors
    /**
     * Represents a list of advanced slider elements.
     * @type {NodeList}
     */
    const advSliderList = document.querySelectorAll(advSliderSel);
    const advSliderOptions = [];
    const advSliders = [];
    const sliderTabOptions = [];
    const advSliderNav = [];
    let sliderNav;
    const sliderThumbOptions = [];

    advSliderList.forEach((slider, i) => {
        advSliderOptions[i] = {};
        sliderTabOptions[i] = {
            item: '.js-nav__item',
        };

        sliderThumbOptions[i] = {
            spaceBetween: 10,
            slidesPerView: 'auto',
            freeMode: true,
            threshold: 10,
            watchSlidesProgress: true,
            wrapperClass: 'slider-nav',
        };

        const sliderID = `${advancedName}-${i}`;
        slider.setAttribute('id', sliderID);

        const sliderParent = slider.closest('.m-slider');

        if (sliderParent) {
            sliderNav = sliderParent.querySelector(advSliderTabs);
        }

        if (sliderNav) {
            const sliderTabID = `js-slider-circular-nav-${i}`;
            sliderNav.setAttribute('id', sliderTabID);
            sliderTabOptions[i].element = `#${sliderTabID}`;
        }

        const isCenterSlides = sliderNav.getAttribute('data-slider-circular-arrange');
        const isSymmetric = u_parseBool(sliderNav.getAttribute('data-slider-circular-symmetric')) || false;

        if (isCenterSlides === 'center' && !isSymmetric) {
            const cSliderNav = sliderNav.querySelector('.slider-nav');
            if (cSliderNav) {
                const initialIndex = parseInt(cSliderNav.getAttribute('data-initial-index'), 10);
                sliderNav.style.setProperty('--cAItem', initialIndex);
                advSliderOptions[i].initialSlide = initialIndex;
            }
        } else {
            sliderNav.style.setProperty('--cAItem', 0);
        }

        advSliderOptions[i] = isLoopOn(slider, advSliderOptions[i]);
        advSliderOptions[i] = isAutoPlayOn(slider, advSliderOptions[i]);
        advSliderOptions[i] = isLazyLoadOn(slider, advSliderOptions[i]);
        advSliderOptions[i] = isBreakpointsOn(slider, advSliderOptions[i]);
        advSliderOptions[i] = isEffectOn(slider, advSliderOptions[i]);
        advSliderOptions[i] = isPaginationOn(slider, advSliderOptions[i]);

        // .m-slider parent is hardcoded in isNavigationOn options
        advSliderOptions[i] = isNavigationOn(slider, advSliderOptions[i], advancedName, i);

        advSliders[i] = new Swiper(slider, advSliderOptions[i]);

        if (sliderNav) {
            if (advSliders[i].initialized) {
                advSliderNav[i] = new SwiperWithCircularTabs(advSliders[i], sliderTabOptions[i]);
            }
        }
    });
};

export {
    circularSliders,
};
