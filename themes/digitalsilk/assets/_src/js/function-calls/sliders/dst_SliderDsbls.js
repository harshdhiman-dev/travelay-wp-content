/**
 * DSBLS SLIDER type
 */
import DSMPSliderDSBLS from '../../library/sliders/slider-dsbls';

// config selectors only here
/**
 * Represents the selector for disabled sliders in a JavaScript slider component.
 *
 * @type {string}
 * @constant
 */
const dsblsSel = '.js-slider-dsbls';
/**
 * Represents the CSS class selector for the disabled mobile slider.
 *
 * @type {string}
 * @name dsblsSelMob
 */
const dsblsSelMob = '.js-slider-dsbls-m';


/**
 * Function to initialize the DSBLs sliders.
 *
 * @function dsblsSlider
 * @returns {void}
 */
const dsblsSlider = () => {
    // loop through sliders and add ID's to it, we assume each
    // dsbls slider has its own mobile slider as its
    // component, so no need to loop, search parent
    // and query child element

    // find those selectors
    /**
     * Retrieves a list of elements matching the given selector and assigns it to the dsblsSliderList variable.
     *
     * @param {string} dsblsSel - The CSS selector used to select the elements.
     * @returns {NodeList} - A list of elements matching the given selector.
     */
    const dsblsSliderList = document.querySelectorAll(dsblsSel);
    /**
     * Retrieves a list of elements using the specified selector and stores them in the variable dsblsSliderMobileList.
     *
     * @param {string} dsblsSelMob - The CSS selector used to retrieve the elements.
     * @returns {NodeList} - A list of elements matching the given selector.
     */
    const dsblsSliderMobileList = document.querySelectorAll(dsblsSelMob);

    const dsbls = [];
    dsblsSliderList.forEach((slider, i) => {
        const sliderID = `js-slider-dsbls-${i}`;
        const sliderMobileID = `js-slider-dsbls-m-${i}`;

        slider.setAttribute('id', sliderID);
        dsblsSliderMobileList[i].setAttribute('id', sliderMobileID);

        dsbls[i] = new DSMPSliderDSBLS(sliderID);
    });
};

export {
    dsblsSlider,
};
