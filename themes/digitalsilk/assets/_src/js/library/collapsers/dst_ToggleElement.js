/**
 * Toggle element on click
 *
 * https://gomakethings.com/how-to-show-and-hide-elements-with-vanilla-javascript/
 */
import { u_toggleElem } from '../../utils/u_show-hide-display';

/**
 * Add click event listener to toggle elements.
 *
 * @function dst_ToggleElement
 * @description This function attaches a click event listener to the document. When a click event is triggered on an element with the attribute 'data-js="toggle-element"', it prevents
 * the default behavior and toggles the content associated with the clicked element.
 *
 * @example
 * // HTML
 * // <a href="#content1" data-js="toggle-element">Toggle Content 1</a>
 * // <div id="content1">...</div>
 *
 * // JavaScript
 * dst_toggleElement();
 *
 * @returns {void}
 */
const dst_ToggleElement = () => {
    document.addEventListener('click', (e) => {
        if (e.target.matches('[data-js="toggle-element"]')) {
            e.preventDefault();

            // Get the content
            const content = document.querySelector(e.target.hash);
            if (!content) return;

            // Toggle the content
            u_toggleElem(content);

        }

    }, false);
};

export {
    dst_ToggleElement,
};
