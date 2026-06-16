import { closeMegaMenu } from './u-mega-menu-logic.js';

const dst_headerMobileGoBack = () => {
	const goBackButtons = document.querySelectorAll('.js-menu-back-btn');
	if (!goBackButtons.length) return;

	goBackButtons.forEach((goBackButton) => {
		goBackButton.addEventListener('click', (e) => {
			e.preventDefault();
			e.stopPropagation(); // Prevent click from bubbling up

			// Find the menu item that contains the sub-menu we want to close.
			const parentMenuItem = goBackButton.closest('.menu-item-has-children');

			if (parentMenuItem) {
				// Close the menu and return focus to its trigger button
				closeMegaMenu(parentMenuItem, true);
			}
		});
	});
};

export {
	dst_headerMobileGoBack,
};
