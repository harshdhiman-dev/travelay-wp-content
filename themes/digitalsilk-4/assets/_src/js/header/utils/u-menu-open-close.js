import { closeMegaMenu } from './u-mega-menu-logic.js';

/**
 * Activates the mobile menu by adding necessary classes and attributes to the elements.
 *
 * @param {HTMLElement} btn  - The button element that triggers the menu.
 * @param {HTMLElement} body - The body element used to toggle the menu.
 */
const openMobileMenu = (btn, body) => {
	if (!btn || !body) return;
	btn.classList.add('is-active');
	body.classList.add('nav-active');
	btn.setAttribute('aria-expanded', 'true');
};

/**
 * Closes the mobile menu by modifying the provided button and body elements.
 *
 * @param {HTMLElement} btn  - The button element representing the mobile menu button.
 * @param {HTMLElement} body - The body element where the mobile menu is displayed.
 * @return {void}
 */
const closeMobileMenu = (btn, body) => {
	if (!btn || !body) return;

	// Find and close all open submenus before closing the main menu.
	const openSubMenus = document.querySelectorAll('.nav-main .menu-item-has-children.is-open');
	openSubMenus.forEach(subMenu => {
		closeMegaMenu(subMenu);
	});

	btn.classList.remove('is-active');
	body.classList.remove('nav-active');
	btn.setAttribute('aria-expanded', 'false');
};

export {
	openMobileMenu, closeMobileMenu,
};
