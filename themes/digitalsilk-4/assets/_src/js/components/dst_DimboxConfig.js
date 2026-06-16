// Importing and configuring DimBox
// For more information, please visit: https://dimboxjs.com/#options

const dst_DimboxInit = () => {
	// eslint-disable-next-line no-undef
	dimbox.setConfig({
		showDownloadButton: false,
		showFullscreenButton: false,
		onAfterOpen() {
			// Get the current active element that triggered the popup
			const activeElement = document.activeElement;

			// Only move buttons for non-gallery popups
			if (!activeElement || activeElement.getAttribute('data-dimbox') !== 'dst-popup-gallery') {
				const dimboxContainer = document.querySelector('.dimbox-container');
				const dimboxContent = document.querySelector('.dimbox-content');

				if (dimboxContainer && dimboxContent) {
					const originalButtons = dimboxContainer.querySelector('.dimbox-buttons');
					if (originalButtons) {
						dimboxContent.appendChild(originalButtons);
					}
				}
			}
		},
		onAfterClose() {
			const dimboxContainer = document.querySelector('.dimbox-container');
			const originalButtons = document.querySelector('.dimbox-buttons');

			if (dimboxContainer && originalButtons) {
				dimboxContainer.appendChild(originalButtons);
			}
		}
	});
};

export { dst_DimboxInit };
