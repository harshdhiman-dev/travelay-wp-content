import {closeMobileMenu} from './u-menu-open-close';

/**
 * Adds swipe event handlers to the mobile navigation element.
 *
 * @param {HTMLElement} mobileNav        - The navigation element to handle swipe gestures.
 * @param {HTMLElement} menuToggleButton - The button element controlling the menu.
 * @param {HTMLElement} body             - The body element to toggle related classes.
 */
const addSwipeEventHandlers = (mobileNav, menuToggleButton, body) => {
	let startX = null;
	let startY = null;

	const isScrollableY = (element) => element.scrollHeight > element.offsetHeight;

	mobileNav.addEventListener('touchstart', (event) => {
		startX = event.touches[0].clientX;
		startY = event.touches[0].clientY;
	}, false);

	mobileNav.addEventListener('touchmove', (event) => {
		if (!startX || !startY) return;
		event.stopPropagation();
		let scrollableElement = mobileNav;
		let subMenu = event.target.closest(".sub-menu");
		if (subMenu) {
			scrollableElement = subMenu;
		}

		const {clientX: endX, clientY: endY} = event.touches[0];
		const diffX = startX - endX;
		const diffY = startY - endY;

		if (Math.abs(diffX) <= Math.abs(diffY) && diffY > 0 && !isScrollableY(scrollableElement)) {
			closeMobileMenu(menuToggleButton, body);
		}

		startX = null;
		startY = null;
	}, false);
};

export {
	addSwipeEventHandlers,
};
