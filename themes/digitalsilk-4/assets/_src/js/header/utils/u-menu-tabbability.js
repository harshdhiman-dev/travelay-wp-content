import { getTabbableElements } from './u-menu-helpers.js';

/**
 * Sets or removes tabindex for all focusable elements in a menu.
 *
 * @param {HTMLElement} menu   - The menu container.
 * @param {boolean}     enable - Whether to enable (true) or disable (false) tabbing.
 */
export function setTabbableInMenu(menu, enable) {
	getTabbableElements(menu).forEach(el => {
		if (enable) {
			el.removeAttribute('tabindex');
		} else {
			el.setAttribute('tabindex', -1);
		}
	});
}
