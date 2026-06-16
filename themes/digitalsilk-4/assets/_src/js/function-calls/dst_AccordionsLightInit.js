import DSMPAccordionsLight from '../library/tabs-accordions/DSMPAccordionsLight';

/**
 * Calls the DSMPAccordionsLight constructor on a collection of elements with the given selector.
 * @param {string} [selector='.js-a-light'] - The CSS selector used to select the elements.
 * @param {Object} options - The options to be passed to the DSMPAccordionsLight constructor.
 */
const callAccordionsLight = (selector = '.js-a-light', options) => {
    let accordions = document.querySelectorAll(selector);
    let accNewName = selector.slice(1);

    accordions.forEach((acc, i) => {
        let newID = `${accNewName}-${i}`;
        let callID = `#${newID}`;
        acc.setAttribute('id', newID);

        new DSMPAccordionsLight(callID, options);
    });
};

export {
    callAccordionsLight,
};
