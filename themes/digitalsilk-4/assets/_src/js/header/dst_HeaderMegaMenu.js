import { u_debounced } from './../utils/utils';
import { getMegaMenu } from './utils/u-menu-helpers.js';
import { setTabbableInMenu } from './utils/u-menu-tabbability.js';
import { openMegaMenu, closeMegaMenu } from './utils/u-mega-menu-logic.js';
import { dst_headerMobileGoBack } from './utils/u-menu-mobile-go-back.js';

let hasInitialized = false;
const MOBILE_BREAKPOINT = 1112;

/**
 * Manages mobile-specific menu features based on viewport size.
 * Adds or removes back buttons as needed.
 * @param {NodeList} menuItems - A NodeList of menu items with children.
 */
function manageMobileMenuState(menuItems) {
	const isMobile = window.innerWidth <= MOBILE_BREAKPOINT;

	menuItems.forEach((menuItem) => {
		// Only process menu items that are currently visible on the page.
		if (menuItem.offsetParent === null) {
			return;
		}

		const menu = getMegaMenu(menuItem);
		if (!menu) return; // No submenu found.

		const backButton = menu.querySelector('.js-menu-back-btn');

		if (isMobile) {
			// On mobile, ensure a back button exists in the submenu for mega menus.
			if (!backButton && menu.matches('.sub-menu') && menu.querySelector('li > .megamenu')) {
				const newBackButton = document.createElement('button');
				newBackButton.className = 'js-menu-back-btn nav-icon__close';
				newBackButton.innerHTML = '<svg width="30" height="30" class="icon icon-close" aria-hidden="true" role="img"><use xlink:href="#close"></use></svg>';
				newBackButton.setAttribute('aria-label', 'Go back to previous menu');
				menu.prepend(newBackButton);
			}
		} else {
			// On desktop, ensure the back button is removed and any open menus are closed.
			backButton?.remove();
			if (menuItem.classList.contains('is-open')) {
				closeMegaMenu(menuItem);
			}
		}
	});

	if (isMobile) {
		dst_headerMobileGoBack();
	}
}

/**
 * Initializes all mega menu instances on the page.
 */
function dst_HeaderMegaMenu() {
	if (hasInitialized) return;
	hasInitialized = true;

	const navMains = document.querySelectorAll('.nav-main');
	if (!navMains.length) return;

	// Initialize each menu instance found on the page.
	navMains.forEach((navMain, navIndex) => {
		const menuItemsWithChildren = navMain.querySelectorAll('.menu-item-has-children');
		if (!menuItemsWithChildren.length) return;

		// 1. Initial setup: Set ARIA attributes and tabindex for all submenus in this instance.
		menuItemsWithChildren.forEach((menuItem, itemIndex) => {
			const trigger = menuItem.querySelector('.js-sub-menu-toggle');
			const menu = getMegaMenu(menuItem);

			if (trigger && menu) {
				// Ensure unique IDs to prevent conflicts between menus.
				menu.id = menu.id || `mega-menu-${navIndex}-${itemIndex}`;
				trigger.setAttribute('aria-controls', menu.id);
				trigger.setAttribute('aria-haspopup', 'true');
				trigger.setAttribute('aria-expanded', 'false');
				menu.setAttribute('tabindex', -1);
				setTabbableInMenu(menu, false);
			}
		});

		// 2. Delegated Event Listener for Keyboard Navigation (scoped to this instance).
		navMain.addEventListener('keydown', (e) => {
			const trigger = e.target.closest('.js-sub-menu-toggle');
			if (!trigger) return;

			const menuItem = trigger.closest('.menu-item-has-children');
			if (!menuItem) return;

			const isOpen = menuItem.classList.contains('is-open');

			if (e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				if (isOpen) {
					closeMegaMenu(menuItem);
				} else {
					openMegaMenu(menuItem);
				}
			} else if (e.key === 'ArrowDown' && !isOpen) {
				e.preventDefault();
				openMegaMenu(menuItem);
			} else if (e.key === 'ArrowUp' && isOpen) {
				e.preventDefault();
				closeMegaMenu(menuItem);
			}
		});

		// 3. Delegated listener for closing menu when focus leaves (scoped to this instance).
		navMain.addEventListener('focusout', (e) => {
			if (window.innerWidth <= MOBILE_BREAKPOINT) return;

			const menuItem = e.target.closest('.menu-item-has-children');
			if (menuItem && !menuItem.contains(e.relatedTarget)) {
				closeMegaMenu(menuItem);
			}
		});
	});

	// --- Global Listeners (attached only once) ---

	// 4. Delegated Event Listener for Clicks.
	document.addEventListener('click', (e) => {
		const trigger = e.target.closest('.js-sub-menu-toggle');

		// Case 1: A submenu toggle button was clicked.
		if (trigger) {
			const menuItem = trigger.closest('.menu-item-has-children');
			if (!menuItem) return;

			e.preventDefault();
			const isOpen = menuItem.classList.contains('is-open');
			const isMobile = window.innerWidth <= MOBILE_BREAKPOINT;

			// On desktop, implement accordion-style closing for other menus.
			if (!isMobile && !isOpen) {
				document.querySelectorAll('.nav-main .menu-item-has-children.is-open').forEach((menuToClose) => {
					if (menuToClose !== menuItem) {
						closeMegaMenu(menuToClose);
					}
				});
			}

			// Toggle the current menu.
			if (isOpen) {
				closeMegaMenu(menuItem);
			} else {
				openMegaMenu(menuItem);
			}
			return;
		}

		// Case 2: A click happened outside of any open menu. Close all open menus.
		if (!e.target.closest('.menu-item-has-children.is-open')) {
			document.querySelectorAll('.nav-main .menu-item-has-children.is-open').forEach(closeMegaMenu);
		}
	});

	// 5. Global Escape key listener.
	document.addEventListener('keydown', (e) => {
		if (e.key === 'Escape') {
			const openMenuItem = document.querySelector('.nav-main .menu-item-has-children.is-open');
			if (openMenuItem) {
				closeMegaMenu(openMenuItem);
				openMenuItem.querySelector('.js-sub-menu-toggle')?.focus();
			}
		}
	});

	// 6. Initial state and resize handling for ALL menus.
	const allMenuItems = document.querySelectorAll('.nav-main .menu-item-has-children');
	manageMobileMenuState(allMenuItems);
	window.addEventListener('resize', u_debounced(() => manageMobileMenuState(allMenuItems), 250));
}

export { dst_HeaderMegaMenu };
