import {getMegaMenu, getTabbableElements} from './u-menu-helpers.js';
import {setTabbableInMenu} from './u-menu-tabbability.js';

// WeakMap to store cleanup functions for arrow navigation
const cleanupArrowNavMap = new WeakMap();

/**
 * Closes a megamenu and resets ARIA attributes.
 *
 * @param   {HTMLElement} menuItem             - The menu item to close.
 * @param   {boolean}     [returnFocusToTrigger=false] - Whether to return focus to the trigger.
 */
export function closeMegaMenu(menuItem, returnFocusToTrigger = false) {
	const menu = getMegaMenu(menuItem);
	const trigger = menuItem.querySelector('.js-sub-menu-toggle');

	if (menu) {
		menuItem.classList.remove('is-open');
		menu.classList.remove('is-open');
		menu.scrollTo({top: 0, behavior: "smooth"});
		if (trigger) trigger.setAttribute('aria-expanded', 'false');
		menu.setAttribute('tabindex', -1);
		setTabbableInMenu(menu, false);
		const cleanup = cleanupArrowNavMap.get(menuItem);
		if (cleanup) cleanup();
		cleanupArrowNavMap.delete(menuItem);

		if (returnFocusToTrigger && trigger) {
			trigger.focus();
		}
	}
}

/**
 * Enables arrow key navigation within an open megamenu.
 *
 * @param   {HTMLElement} menuItem - The parent menu item.
 * @returns {Function}               Cleanup function to remove event listeners.
 */
function enableMegaMenuArrowNavigation(menuItem) {
	const menu = getMegaMenu(menuItem);
	if (!menu) return () => {
	};

	function onKeyNav(e) {
		const focusable = getTabbableElements(menu);
		const idx = focusable.indexOf(document.activeElement);

		if (e.key === 'Escape') {
			e.preventDefault();
			closeMegaMenu(menuItem, true); // true = return focus to trigger
			return;
		}

		if (e.key === 'ArrowDown') {
			e.preventDefault();
			const nextIdx = (idx + 1) % focusable.length;
			focusable[nextIdx]?.focus();
		}
		if (e.key === 'ArrowUp') {
			e.preventDefault();
			const prevIdx = (idx - 1 + focusable.length) % focusable.length;
			focusable[prevIdx]?.focus();
		}
	}

	menu.addEventListener('keydown', onKeyNav);
	return () => menu.removeEventListener('keydown', onKeyNav);
}

/**
 * Handles tab navigation to close the menu when tabbing out.
 * This now handles both forward and backward tabbing and returns a cleanup function.
 *
 * @param   {HTMLElement} menu - The menu container.
 * @param   {HTMLElement} li   - The parent menu item.
 * @returns {Function}           Cleanup function to remove event listeners.
 */
function handleMenuTabbing(menu, li) {
	const tabbables = getTabbableElements(menu);
	if (tabbables.length < 1) return () => {
	}; // Return empty cleanup if no tabbables

	const first = tabbables[0];
	const last = tabbables[tabbables.length - 1];

	function onFirstTab(e) {
		if (e.key === 'Tab' && e.shiftKey) {
			closeMegaMenu(li);
		}
	}

	function onLastTab(e) {
		if (e.key === 'Tab' && !e.shiftKey) {
			closeMegaMenu(li);
		}
	}

	first.addEventListener('keydown', onFirstTab);
	last.addEventListener('keydown', onLastTab);

	// Return a cleanup function that removes both listeners
	return () => {
		first.removeEventListener('keydown', onFirstTab);
		last.removeEventListener('keydown', onLastTab);
	};
}

/**
 * Opens a megamenu and sets appropriate ARIA attributes.
 *
 * @param   {HTMLElement} menuItem - The menu item to open.
 */
export function openMegaMenu(menuItem) {
	const menu = getMegaMenu(menuItem);
	const trigger = menuItem.querySelector('.js-sub-menu-toggle');
	if (menu) {
		menuItem.classList.add('is-open');
		menu.classList.add('is-open');
		if (trigger) trigger.setAttribute('aria-expanded', 'true');
		menu.removeAttribute('tabindex');
		setTabbableInMenu(menu, true);

		// Combine cleanup functions for arrow navigation and tab trapping.
		const cleanupNav = enableMegaMenuArrowNavigation(menuItem);
		const cleanupTabbing = handleMenuTabbing(menu, menuItem);

		cleanupArrowNavMap.set(menuItem, () => {
			cleanupNav();
			cleanupTabbing();
		});
	}
}
