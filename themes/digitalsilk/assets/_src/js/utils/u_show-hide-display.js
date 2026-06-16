/**
 * Sets the display property of the specified element to "block".
 *
 * @param {HTMLElement} elem - The element whose display property needs to be set to "block".
 *
 * @return {void}
 */

const u_showDisplay = (elem) => {
    elem.style.display = 'block';
};

/**
 * Hides the display of the given element.
 *
 * @param {HTMLElement} elem - The element to hide.
 */
const u_hideDisplay = (elem) => {
    elem.style.display = 'none';
};

/**
 * Shows the specified element by removing a hidden class and adding a visible class.
 *
 * @param {HTMLElement} elem                 - The element to show.
 * @param {string}      [hidden='is-hidden'] - The class name used for hiding the element. Defaults to 'is-hidden'.
 * @param {string}      [visible='is-shown'] - The class name used for showing the element. Defaults to 'is-shown'.
 */
const u_showElem = (elem, hidden = 'is-hidden', visible = 'is-shown') => {
    elem.classList.remove(hidden);
    elem.classList.add(visible);
};

/**
 * Hides the given element by adding a hidden class and removing a visible class.
 *
 * @param {Element} elem                 - The element to hide.
 * @param {string}  [hidden='is-hidden'] - The class to be added to the element to hide it.
 * @param {string}  [visible='is-shown'] - The class to be removed from the element to make it not visible.
 * @return {void}
 */
const u_hideElem = (elem, hidden = 'is-hidden', visible = 'is-shown') => {
    elem.classList.add(hidden);
    elem.classList.remove(visible);
};

/**
 * Toggles the visibility of an element by adding or removing a specified CSS class.
 *
 * @param {HTMLElement} elem                 - The element to toggle visibility.
 * @param {string}      [hidden='is-hidden'] - The CSS class name used to hide the element.
 * @return {void}
 */
const u_toggleElem = (elem, hidden = 'is-hidden') => {
    elem.classList.toggle(hidden);
};

export {
    u_showElem,
    u_hideElem,
    u_toggleElem,
    u_showDisplay,
    u_hideDisplay,
};
