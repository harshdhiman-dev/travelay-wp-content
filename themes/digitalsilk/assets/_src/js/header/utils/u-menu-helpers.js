/**
 * Gets the megamenu sub-menu element.
 *
 * @param {HTMLElement} menuItem - The parent menu item.
 * @return {HTMLElement|null}       The sub-menu element or null if not found.
 */
export function getMegaMenu(menuItem) {
	return menuItem.querySelector(':scope > .sub-menu');
}

/**
 * Gets all focusable/tabbable elements within a container.
 *   Scoped to .nav-main to prevent capturing elements outside the navigation.
 *
 * @param {HTMLElement} container - The container to search within.
 * @return {Array}                   Array of focusable elements.
 */
export function getTabbableElements(container) {
	return Array.from(container.querySelectorAll('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])')).filter((el) => !el.hasAttribute('disabled') && !el.closest('[aria-hidden="true"]') && el.offsetParent !== null);
}
