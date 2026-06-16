import { openMobileMenu, closeMobileMenu } from './utils/u-menu-open-close';
import { addSwipeEventHandlers } from './utils/u-menu-swipe';

const ARIA_EXPANDED = 'aria-expanded';
const BODY_SELECTOR = 'body';
const NAVBAR_INNER = '.navbar-mobile__inner';
const NAV_MAIN_WRAP_BURGER = '.nav-main__wrap-burger';

/**
 * Toggles the header menu and handles submenu functionality based on the provided selector.
 *
 * @param {string} menuToggleSelector - The selector for the menu toggle button.
 */
const dst_HeaderMobileToggleMenu = (menuToggleSelector) => {

	const menuToggleButton = document.querySelector(menuToggleSelector);
	if (!menuToggleButton) return;

	const body = document.querySelector(BODY_SELECTOR);
	const mobileNav = document.querySelector(NAVBAR_INNER);
	// Add listener for mobile menu toggle
	addToggleMenuClickListener(menuToggleButton, body);

	// Add swipe gestures for closing the mobile menu
	if (mobileNav) {
		if (window.innerWidth <= 1112) {
			addSwipeEventHandlers(mobileNav, menuToggleButton, body);
		}

		// Add listener for anchor link clicks
		const anchorLinks = mobileNav.querySelectorAll('a[href^="#"]:not(.js-menu-back-btn), a[href^="/#"]:not(.js-menu-back-btn)');
		anchorLinks.forEach((link) => {
			link.addEventListener('click', () => {
				closeMobileMenu(menuToggleButton, body);
			});
		});
	}

	// Add listener for esc key press
	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape') {
			closeMobileMenu(menuToggleButton, body);
		}
	});
};

/**
 * Adds a click listener to toggle the mobile menu.
 *
 * @param {HTMLElement} menuToggleButton - The button element controlling the menu.
 * @param {HTMLElement} body - The body element to toggle related classes.
 */
const addToggleMenuClickListener = (menuToggleButton, body) => {
	const mobileNav = document.querySelector(NAVBAR_INNER);
	menuToggleButton.addEventListener('click', (event) => {
		event.preventDefault();
		const isExpanded = menuToggleButton.getAttribute(ARIA_EXPANDED) === 'true';
		if (isExpanded) {
			closeMobileMenu(menuToggleButton, body);
		} else {
			openMobileMenu(menuToggleButton, body);
			// Add outside click event listener only after the mobile menu has been opened
			document.addEventListener('click', (event) => {
				if (!mobileNav.contains(event.target) && !menuToggleButton.contains(event.target) &&
					!document.querySelector(NAV_MAIN_WRAP_BURGER).contains(event.target)) {
					closeMobileMenu(menuToggleButton, body);
				}
			});
		}
	});
};

export { dst_HeaderMobileToggleMenu };
