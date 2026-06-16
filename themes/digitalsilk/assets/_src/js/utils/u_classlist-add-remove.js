/**
 * Checks if an element has a specific class.
 *
 * @param {HTMLElement} el  - The element to check.
 * @param {string}      cls - The class to look for.
 */
const u_hasClass = (el, cls) => {
    if (el.className.match('(?:^|\\s)' + cls + '(?!\\S)')) {
        return true;
    }
};

/**
 * Adds a class to an element if it does not already have it.
 *
 * @param {HTMLElement} el  - The element to add the class to.
 * @param {string}      cls - The class to add to the element.
 */
const u_addClass = (el, cls) => {
    if (!el.className.match('(?:^|\\s)' + cls + '(?!\\S)')) {
        el.className += ' ' + cls;
    }
};

/**
 * Removes a specified CSS class from an element's className property.
 *
 * @param {HTMLElement} el  - The element from which to remove the CSS class.
 * @param {string}      cls - The CSS class to be removed.
 */
const u_delClass = (el, cls) => {
    el.className = el.className.replace(new RegExp('(?:^|\\s)' + cls + '(?!\\S)'), '');
};

export {
    u_hasClass,
    u_addClass,
    u_delClass,
};
