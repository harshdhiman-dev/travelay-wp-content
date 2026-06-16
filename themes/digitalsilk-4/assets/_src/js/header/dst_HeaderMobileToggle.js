import { openMobileMenu, closeMobileMenu } from './utils/u-menu';
import { addSwipeEventHandlers } from './utils/u-menu-swipe';
import { u_debounced } from '../utils/utils';

// Constants for attribute and class names
const ARIA_EXPANDED = 'aria-expanded';
const BODY_SELECTOR = 'body';
const NAVBAR_INNER = '.navbar-mobile__inner';

// Constants for mobile megamenu close button
const DST_MEGA_MENU_MOBILE_BREAKPOINT = 1112; // Default, adjust if your breakpoint differs
const MEGA_MENU_CLOSE_BUTTON_CLASS = 'js-megamenu-mobile-close-btn';
const MOBILE_MEGAMENU_PANEL_SELECTOR = '.megamenu'; // Default, adjust if not .megamenu itself

// Cache for DOM elements to avoid repeated queries
const domCache = new Map();

/**
 * Toggles the header menu and handles submenu functionality based on the provided selector.
 *
 * @param {string} menuToggleSelector     - The selector for the menu toggle button.
 * @param {string} subMenuSelector        - The selector for the submenu element.
 * @param {string} [subMenuCloseSelector] - Optional selector for close buttons in submenus.
 */
const dst_HeaderMobileToggle = (menuToggleSelector, subMenuSelector, subMenuCloseSelector = null) => {
	const menuToggleButton = document.querySelector(menuToggleSelector);
	if (!menuToggleButton) return;

	const body = document.querySelector(BODY_SELECTOR);
	const mobileNav = document.querySelector(NAVBAR_INNER);

	// Cache frequently accessed DOM elements
	domCache.set('menuToggleButton', menuToggleButton);
	domCache.set('body', body);
	domCache.set('mobileNav', mobileNav);

	// Add listener for mobile menu toggle
	addToggleMenuClickListener(menuToggleButton, body);

	// Add swipe gestures for closing the mobile menu
	if (mobileNav) {
		addSwipeEventHandlers(mobileNav, menuToggleButton, body);

		// Add listener for anchor link clicks using event delegation
		mobileNav.addEventListener('click', (event) => {
			const link = event.target.closest('a[href^="#"], a[href^="/#"]');
			if (link) {
				closeMobileMenu(menuToggleButton, body);
			}
		});
	}

	// Initialize submenu toggle functionality
	if (subMenuSelector) {
		initSubMenuToggle(subMenuSelector, subMenuCloseSelector);
	}

	// Add listener for esc key press
	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape') {
			closeMobileMenu(menuToggleButton, body);
		}
	});
};

/**
 * Resets all open submenus within the mobile navigation
 *
 * @param {HTMLElement} mobileNav - The mobile navigation element
 */
const resetAllSubMenus = (mobileNav) => {
	if (!mobileNav) return;

	const openSubItems = mobileNav.querySelectorAll('.menu-item-has-children.is-open');
	openSubItems.forEach((subItem) => {
		const toggle = subItem.querySelector('.js-sub-menu-toggle');
		const subMenuPanel = subItem.querySelector('.sub-menu');
		if (toggle && subMenuPanel) {
			subItem.classList.remove('is-open');
			subItem.setAttribute(ARIA_EXPANDED, 'false');
			subMenuPanel.classList.remove('is-open');
			toggle.classList.remove('is-toggled');

			// Keep the megamenu close button but ensure it's properly initialized
			const megamenuPanelToTarget = subMenuPanel.matches(MOBILE_MEGAMENU_PANEL_SELECTOR) ? subMenuPanel : subMenuPanel.querySelector(MOBILE_MEGAMENU_PANEL_SELECTOR);

			if (megamenuPanelToTarget && window.innerWidth <= DST_MEGA_MENU_MOBILE_BREAKPOINT) {
				// Instead of removing, ensure it's properly set up
				ensureMobileMegamenuCloseButton(megamenuPanelToTarget, () => {
					subMenuPanel.classList.remove('is-open');
					subItem.classList.remove('is-open');
					subItem.setAttribute(ARIA_EXPANDED, 'false');
					toggle.classList.remove('is-toggled');
				});
			}
		}
	});
};

/**
 * Adds a click listener to toggle the mobile menu.
 *
 * @param {HTMLElement} menuToggleButton - The button element controlling the menu.
 * @param {HTMLElement} body             - The body element to toggle related classes.
 */
const addToggleMenuClickListener = (menuToggleButton, body) => {
	const mobileNav = domCache.get('mobileNav') || document.querySelector(NAVBAR_INNER);

	// Store the listener reference so we can remove it later
	let outsideClickListenerRef;

	// Define the outside click handler function so it can be added/removed
	const outsideClickListener = (outsideEvent) => {
		// Check if the menu is actually open and the click is outside
		if (mobileNav && menuToggleButton.getAttribute(ARIA_EXPANDED) === 'true' && !mobileNav.contains(outsideEvent.target) && !menuToggleButton.contains(outsideEvent.target)) {
			if (mobileNav && !mobileNav.contains(outsideEvent.target)) {
				closeMobileMenu(menuToggleButton, body); // Close main menu
				document.removeEventListener('click', outsideClickListenerRef); // Remove this listener

				// Reset all submenus
				resetAllSubMenus(mobileNav);
			}
		}
	};

	// Store the reference
	outsideClickListenerRef = outsideClickListener;

	menuToggleButton.addEventListener('click', (event) => {
		event.preventDefault();
		const isExpanded = menuToggleButton.getAttribute(ARIA_EXPANDED) === 'true';

		if (isExpanded) {
			// Menu is currently open, so we're closing it
			closeMobileMenu(menuToggleButton, body);
			document.removeEventListener('click', outsideClickListenerRef);
			resetAllSubMenus(mobileNav); // Also reset submenus when closing main via toggle
		} else {
			// Menu is currently closed, so we're opening it
			openMobileMenu(menuToggleButton, body);
			// Add the outside click listener. Adding it in the next microtask to avoid self-triggering.
			setTimeout(() => document.addEventListener('click', outsideClickListenerRef), 0);
			resetAllSubMenus(mobileNav); // Reset submenus when opening main menu
		}
	});
};

/**
 * Initializes submenu toggle functionality.
 *
 * @param {string} subMenuSelector      - The selector for the main menu element with submenus.
 * @param {string} subMenuCloseSelector - Optional selector for close buttons in submenus.
 */
const initSubMenuToggle = (subMenuSelector, subMenuCloseSelector) => {
	const menuElement = document.querySelector(subMenuSelector);
	if (!menuElement) return;

	// Use event delegation for toggle buttons
	menuElement.addEventListener('click', (event) => {
		const toggleButton = event.target.closest('.js-sub-menu-toggle');
		if (toggleButton) {
			setupIndividualSubMenuToggle(toggleButton, event);
		}
	});

	// Handle close elements if provided
	if (subMenuCloseSelector) {
		const closeElements = document.querySelectorAll(subMenuCloseSelector);
		closeElements.forEach((closeEle) => {
			closeEle.addEventListener('click', () => {
				const toggleButtons = menuElement.querySelectorAll('.js-sub-menu-toggle');
				toggleButtons.forEach((toggleButton) => {
					const toggleButtonMenuItem = toggleButton.closest('.menu-item-has-children');
					if (!toggleButtonMenuItem) return;

					const subMenu = toggleButtonMenuItem.querySelector('.sub-menu');
					if (!subMenu) return;

					subMenu.classList.remove('is-open');
					toggleButtonMenuItem.classList.remove('is-open');
					toggleButtonMenuItem.setAttribute('aria-expanded', 'false');
					toggleButton.classList.remove('is-toggled');

					const megamenuPanelToTarget = subMenu.matches(MOBILE_MEGAMENU_PANEL_SELECTOR) ? subMenu : subMenu.querySelector(MOBILE_MEGAMENU_PANEL_SELECTOR);

					if (megamenuPanelToTarget && window.innerWidth <= DST_MEGA_MENU_MOBILE_BREAKPOINT) {
						// Ensure the close button is properly set up instead of removing
						ensureMobileMegamenuCloseButton(megamenuPanelToTarget, () => {
							subMenu.classList.remove('is-open');
							toggleButtonMenuItem.classList.remove('is-open');
							toggleButtonMenuItem.setAttribute('aria-expanded', 'false');
							toggleButton.classList.remove('is-toggled');
						});
					}
				});
			});
		});
	}
};

/**
 * Sets up individual submenu toggle functionality
 *
 * @param {HTMLElement} toggleButton - The toggle button element
 * @param {Event}       event        - The click event
 */
const setupIndividualSubMenuToggle = (toggleButton, event) => {
	if (event) {
		event.preventDefault(); // Prevent default link behavior for parent item
	}

	const toggleButtonMenuItem = toggleButton.closest('.menu-item-has-children');
	if (!toggleButtonMenuItem) return;

	const subMenu = toggleButtonMenuItem.querySelector('.sub-menu'); // The panel to open/close
	if (!subMenu) return;

	// Toggle the active state
	const isActive = subMenu.classList.toggle('is-open');
	toggleButtonMenuItem.classList.toggle('is-open', isActive); // Sync 'is-open' on the LI
	toggleButtonMenuItem.setAttribute('aria-expanded', isActive.toString());
	toggleButton.classList.toggle('is-toggled', isActive); // For the arrow icon

	// Determine if this is a megamenu context on mobile
	// This could be the subMenu itself or a specific .megamenu div within it.
	const megamenuPanelToTarget = subMenu.matches(MOBILE_MEGAMENU_PANEL_SELECTOR) ? subMenu : subMenu.querySelector(MOBILE_MEGAMENU_PANEL_SELECTOR);

	if (isActive) {
		// Panel was just opened
		if (megamenuPanelToTarget && window.innerWidth <= DST_MEGA_MENU_MOBILE_BREAKPOINT) {
			const closeCurrentMegaMenu = () => {
				subMenu.classList.remove('is-open');
				toggleButtonMenuItem.classList.remove('is-open');
				toggleButtonMenuItem.setAttribute('aria-expanded', 'false');
				toggleButton.classList.remove('is-toggled');
			};
			ensureMobileMegamenuCloseButton(megamenuPanelToTarget, closeCurrentMegaMenu);
		}
	} else {
		// Panel was just closed by clicking the arrow toggle again
		if (megamenuPanelToTarget && window.innerWidth <= DST_MEGA_MENU_MOBILE_BREAKPOINT) {
			// Keep the close button but ensure it's properly set up
			ensureMobileMegamenuCloseButton(megamenuPanelToTarget, () => {
				subMenu.classList.remove('is-open');
				toggleButtonMenuItem.classList.remove('is-open');
				toggleButtonMenuItem.setAttribute('aria-expanded', 'false');
				toggleButton.classList.remove('is-toggled');
			});
		}
	}
};

/**
 * Ensures a megamenu has a close button, creating one if needed
 *
 * @param {HTMLElement} megamenuPanelElement - The DOM element of the megamenu panel
 * @param {Function}    closeCallback        - The function that should be called when the 'X' button is clicked
 */
function ensureMobileMegamenuCloseButton(megamenuPanelElement, closeCallback) {
	if (!megamenuPanelElement || typeof closeCallback !== 'function') {
		return;
	}

	if (window.innerWidth <= DST_MEGA_MENU_MOBILE_BREAKPOINT) {
		// Ensure we're targeting the correct panel that is *actually* a megamenu context on mobile
		const isMegamenuContext = megamenuPanelElement.matches(MOBILE_MEGAMENU_PANEL_SELECTOR) || megamenuPanelElement.querySelector(MOBILE_MEGAMENU_PANEL_SELECTOR);

		if (isMegamenuContext) {
			let closeButton = megamenuPanelElement.querySelector(`.${MEGA_MENU_CLOSE_BUTTON_CLASS}`);

			// If the button doesn't exist, create it
			if (!closeButton) {
				closeButton = document.createElement('button');
				closeButton.type = 'button';
				closeButton.textContent = '×'; // Multiplication sign
				closeButton.classList.add(MEGA_MENU_CLOSE_BUTTON_CLASS);
				closeButton.setAttribute('aria-label', 'Close Menu Section');

				// Use a single event listener and store it on the button itself
				const handleClick = (event) => {
					event.stopPropagation();
					closeCallback();
				};

				closeButton.addEventListener('click', handleClick);

				// Store the handler reference on the element for potential cleanup
				closeButton._closeHandler = handleClick;

				megamenuPanelElement.prepend(closeButton);
			} else {
				// If button exists, update its click handler to use the new callback
				if (closeButton._closeHandler) {
					closeButton.removeEventListener('click', closeButton._closeHandler);
				}

				const handleClick = (event) => {
					event.stopPropagation();
					closeCallback();
				};

				closeButton.addEventListener('click', handleClick);
				closeButton._closeHandler = handleClick;
			}
		}
	}
}

export { dst_HeaderMobileToggle };
