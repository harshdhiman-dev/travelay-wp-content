import DSMPTabToAccordionMobile from '../library/tabs-accordions/DSMPTabsToAccordionMobile';

/**
 * The ID of the tab accordion element.
 *
 * @type {string}
 */
const tabaccID = 'js-tab-acc';
/**
 * Represents the selector for the JavaScript Tabs to Accordion wrapper.
 *
 * @constant
 * @type {string}
 */
const tabaccSelector = '.js-tabs-to-acc-wrapper';
/**
 * Retrieves a collection of elements that match a specific selector.
 *
 * @param {string} tabaccSelector - The CSS selector used to identify the elements.
 * @returns {NodeList} - A collection of elements that match the given selector.
 */
const tabaccItems = document.querySelectorAll(tabaccSelector);

/**
 * Calls the `DSMPTabToAccordionMobile` constructor for each accordion item in the `tabaccItems` array.
 *
 * @function callTabAccordionsMobile
 * @returns {void}
 */
const callTabAccordionsMobile = () => {

    tabaccItems.forEach((acc, i) => {
        let taID = `${tabaccID}-${i}`;
        let callID = `#${taID}`;
        acc.setAttribute('id', taID);

        new DSMPTabToAccordionMobile(callID);
    });
};

export {
    callTabAccordionsMobile,
}
