/**
 * Advanced slider type
 */

import SwiperWithTabs from '../../library/sliders/swiper-with-tabs';
import { isAutoPlayOn } from '../../library/sliders/slider-options/autoplay';
import { isLazyLoadOn } from '../../library/sliders/slider-options/lazy';
import { isBreakpointsOn } from '../../library/sliders/slider-options/breakpoints';
import { isNavigationOn } from '../../library/sliders/slider-options/navigation';
import { isLoopOn } from '../../library/sliders/slider-options/loop';
import { isPaginationOn } from '../../library/sliders/slider-options/pagination';
import { u_parseBool } from '../../utils/u_types';
import { autoplayObserver } from '../../library/sliders/slider-options/autoplayObserver';
import { isEffectOn } from '../../library/sliders/slider-options/effects';

// config selectors only here
/**
 * @typedef {string} advancedName
 * Represents the advanced name of a JS slider.
 * The advanced name should follow a specific format: 'js-slider-advanced'.
 */
const advancedName = 'js-slider-advanced';
/**
 * Selects the advanced slider elements with the given selector.
 *
 * @constant {string} advSliderSel - The selector for the advanced slider elements.
 */
const advSliderSel = '.js-slider-advanced';
/**
 * Query selector for the Advanced Slider Tabs.
 *
 * @type {string}
 */
const advSliderTabs = '.l-slider-nav';


/**
 * Initializes and adds functionality to advanced sliders.
 *
 * @returns {void}
 */
const advancedSliders = () => {
    // loop through sliders and add ID's to it

    // find those selectors
    /**
     * Variable to store the list of elements selected by querySelectorAll function.
     *
     * @type {NodeList}
     * @name advSliderList
     * @memberOf window
     * @desc This variable stores the list of elements that are selected using the querySelectorAll function.
     *       It is used to reference and manipulate the selected elements in the document.
     *
     * Example usage:
     * 1. Accessing the length property to determine the number of selected elements:
     *    ```js
     *    console.log(advSliderList.length);
     *    ```
     *
     * 2. Looping through each selected element:
     *    ```js
     *    for (let i = 0; i < advSliderList.length; i++) {
     *      const element = advSliderList[i];
     *      // Perform actions on the selected element
     *    }
     *    ```
     *
     * @see {@link https://developer.mozilla.org/en-US/docs/Web/API/Document/querySelectorAll|MDN Documentation}
     */
    const advSliderList = document.querySelectorAll(advSliderSel);
    const advSliderOptions = [];
    const advSliders = [];
    const sliderTabOptions = [];
    const advSliderNav = [];
    let sliderNav;
    const advSliderThumbs = [];
    const sliderThumbOptions = [];
    const advancedObserver = [];

    advSliderList.forEach((slider, i) => {
        advSliderOptions[i] = {};

        const triggerType = slider.getAttribute('data-slider-trigger') || 'click';

        sliderTabOptions[i] = {
            item: '.js-nav__item',
            active: 'is-active',
            trigger: triggerType,
        };

        sliderThumbOptions[i] = {
            spaceBetween: 10,
            slidesPerView: 'auto',
            freeMode: true,
            threshold: 10,
            watchSlidesProgress: true,
            wrapperClass: 'slider-nav',
        };

        const isThumbs = u_parseBool(slider.getAttribute('data-slider-thumbs'));

        const sliderID = `${advancedName}-${i}`;
        slider.setAttribute('id', sliderID);

        const sliderParent = slider.closest('.m-slider');

        if (sliderParent) {
            sliderNav = sliderParent.querySelector(advSliderTabs);
        }

        let sliderThumbsSelector;
        if (sliderNav) {
            if (isThumbs) {
                const sliderThumbsID = `js-slider-advanced-thumbs-${i}`;
                sliderNav.setAttribute('id', sliderThumbsID);
                sliderThumbsSelector = `#${sliderThumbsID}`;
            } else {
                const sliderTabID = `js-slider-advanced-nav-${i}`;
                sliderNav.setAttribute('id', sliderTabID);
                sliderTabOptions[i].element = `#${sliderTabID}`;
            }
        }

        advSliderOptions[i] = isLoopOn(slider, advSliderOptions[i]);
        advSliderOptions[i] = isAutoPlayOn(slider, advSliderOptions[i]);
        advSliderOptions[i] = isLazyLoadOn(slider, advSliderOptions[i]);
        advSliderOptions[i] = isBreakpointsOn(slider, advSliderOptions[i]);
        advSliderOptions[i] = isPaginationOn(slider, advSliderOptions[i]);
        advSliderOptions[i] = isEffectOn(slider, advSliderOptions[i]);

        // .m-slider parent is hardcoded in isNavigationOn options
        advSliderOptions[i] = isNavigationOn(slider, advSliderOptions[i], advancedName, i);

        if (isThumbs) {

            const isVertical = u_parseBool(slider.getAttribute('data-slider-vertical'));

            if (isVertical) {
                sliderThumbOptions[i].direction = 'vertical';
                // sliderThumbOptions[i].autoHeight = true;
                sliderParent.classList.add('swiper-thumbs-nav-vertical');
            }

            advSliderThumbs[i] = new Swiper(sliderThumbsSelector, sliderThumbOptions[i]);

            advSliderOptions[i].thumbs = {};
            advSliderOptions[i].thumbs.swiper = advSliderThumbs[i];

            advSliderOptions[i].noSwipingSelector = '.l-slider-nav, .m-slider__pagination';

        }

        advSliders[i] = new Swiper(slider, advSliderOptions[i]);

        if (sliderNav) {
            if (advSliders[i].initialized) {
                advSliderNav[i] = new SwiperWithTabs(advSliders[i], sliderTabOptions[i]);
            }
        }

        const isAutoplay = slider.getAttribute('data-slider-autoplay');
        const autoplayObserve = u_parseBool(slider.getAttribute('data-slider-autoplay-observer'));

        if (isAutoplay && autoplayObserve) {
            advSliders[i].autoplay.stop();
            advancedObserver.push({
                slider: sliderID,
            });
        }
    });

    if (advancedObserver.length > 0) {
        autoplayObserver(advancedObserver, advancedName, advSliders);
    }
};

export {
    advancedSliders,
};
