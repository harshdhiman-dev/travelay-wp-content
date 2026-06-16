/**
 * Search Overlay
 */
import { u_hideElem, u_showElem } from '../utils/u_show-hide-display';

/**
 * dst_headerSearch handles the functionality of the header search overlay.
 * It allows the user to open and close the overlay and perform search operations.
 */
const dst_HeaderSearch = () => {
	const searchContainers = document.querySelectorAll('.site-search');
	const searchOverlayClass = 'ds-overlay-search';

	searchContainers.forEach((container) => {
		const searchTrigger = container.querySelector('[data-js="search-trigger"]');
		const searchTarget = container.querySelector('[data-js="search-target"]');
		const input = searchTarget.querySelector('.search-field');
		const searchClose = searchTarget.querySelector('[data-js="search-close"]');

		const toggleSearchOverlay = (isOpen) => {
			if (isOpen) {
				u_showElem(searchTarget);
				input.focus();
				document.body.classList.add(searchOverlayClass);
			} else {
				u_hideElem(searchTarget);
				document.body.classList.remove(searchOverlayClass);
			}
		};

		const handleOutsideClick = (e) => {
			if (document.body.classList.contains(searchOverlayClass) &&
				!searchTarget.contains(e.target) &&
				e.target !== searchTrigger) {
				toggleSearchOverlay(false);
			}
		};

		const handleSearchTriggerClick = (e) => {
			e.preventDefault();
			toggleSearchOverlay(true);
			document.addEventListener('click', handleOutsideClick);
		};

		const handleSearchCloseClick = (e) => {
			e.preventDefault();
			toggleSearchOverlay(false);
			document.removeEventListener('click', handleOutsideClick);
		};

		const handleKeyDown = (e) => {
			if (e.target === searchTrigger && (e.key === 'Enter' || e.keyCode === 13)) {
				e.preventDefault();
				toggleSearchOverlay(true);
				document.addEventListener('click', handleOutsideClick);
			} else if (document.body.classList.contains(searchOverlayClass)) {
				if (e.key === 'Escape' || e.keyCode === 27) {
					toggleSearchOverlay(false);
					document.removeEventListener('click', handleOutsideClick);
				} else if (e.key === 'Enter' || e.keyCode === 13) {
					// Open the search overlay when "Enter" key is pressed
					toggleSearchOverlay(true);
					document.addEventListener('click', handleOutsideClick);
				}
			}
		};

		searchTrigger.addEventListener('click', handleSearchTriggerClick);
		searchClose.addEventListener('click', handleSearchCloseClick);
		document.addEventListener('keydown', handleKeyDown);
	});
};

export {
	dst_HeaderSearch,
};
