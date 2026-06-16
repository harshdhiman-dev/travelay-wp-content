/**
 * @typedef {Object} ObserverParams
 * @property {string} className - The class name to be added/removed.
 * @property {(element: HTMLElement) => void} [cb] - Optional callback function. Called with the target element.
 * @property {string|boolean} [repeat='false'] - Whether to repeat the animation (add/remove class). Defaults to 'false'.
 * @property {string} [margin='0px'] - The IntersectionObserver rootMargin. Defaults to '0px'.
 * @property {number|string} [threshold=0] - The IntersectionObserver threshold. Defaults to 0.
 */

/**
 * Adds an observer to monitor the visibility of an element in the viewport.
 *
 * @param {HTMLElement} el - The DOM element to be observed.
 * @param {ObserverParams} params - The parameters for configuring the observer.
 */
const addObserver = (el, params) => {
	// Destructure params with defaults
	const {
		className,
		cb = (element) => {
		},
		repeat = 'false',
		margin = '0px',
		threshold = 0
	} = params;

	if (!('IntersectionObserver' in window)) {
		el.classList.add(className);
		cb(el); // Call callback with the element
		return;
	}

	const observer = new IntersectionObserver(
		(entries, obs) => {
			entries.forEach((entry) => {
				const targetElement = entry.target; // Cache target element
				if (entry.isIntersecting) {
					targetElement.classList.add(className);
					cb(targetElement); // Call callback with the target element

					// Unobserve if not repeating
					if (String(repeat) !== 'true') { // Ensure comparison works with boolean/string
						obs.unobserve(targetElement);
					}
				} else if (String(repeat) === 'true') { // Ensure comparison works with boolean/string
					targetElement.classList.remove(className);
				}
			});
		},
		{
			root: null,
			rootMargin: margin,
			threshold: Number(threshold) // Ensure threshold is a number
		}
	);

	observer.observe(el);
};

export default addObserver;
