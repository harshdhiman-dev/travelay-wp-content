import { u_debounced } from './utils/utils';

/**
 * Blog TOC imports and functions
 */

class dstBlogToc {
	constructor() {
		// DOM elements
		this.contentContainer = document.querySelector('.js-has-toc');
		if (!this.contentContainer) return;

		this.u_debounced = u_debounced;

		// Configuration
		this.headerHeight = this.getHeaderHeight();
		// eslint-disable-next-line no-undef
		this.tocTitle = ds.toc_title.replace(/^"|"$/g, '');
		// eslint-disable-next-line no-undef
		this.headingSelectors = JSON.parse(ds.toc_tags);
		this.excludeHeadingSelectors = 'exclude';

		// State
		this.headings = [];
		this.tocElement = null;
		this.tocPlaceholder = null;
		this.tocDetailsElement = null;
		this.intersectionObserver = null;
		this.isSticky = false;
		this.initialTOCHeight = 0;
		// Initialize
		this.init();
	}

	init() {
		this.headings = Array.from(this.contentContainer.querySelectorAll(this.headingSelectors.join(','))).filter(heading => !heading.classList.contains(this.excludeHeadingSelectors));

		if (this.headings.length === 0) return;

		this.ensureHeadingIds();
		this.createTOC();
		this.setupObservers();
		this.setupEventListeners();
	}

	ensureHeadingIds() {
		this.headings.forEach(heading => {
			if (!heading.id) {
				const slug = heading.textContent.toLowerCase().replace(/[^\w\s-]/g, '').replace(/\s+/g, '-');
				heading.id = `heading-${ slug }`;
			}
		});
	}

	createPlaceholder() {
		this.tocPlaceholder = document.createElement('div');
		this.tocPlaceholder.className = 'toc-placeholder';
		this.tocPlaceholder.style.height = `${ this.tocElement.offsetHeight }px`;
		this.tocPlaceholder.style.display = 'none'; // Hide initially

		this.tocElement.insertAdjacentElement('beforebegin', this.tocPlaceholder);
	}

	/**
	 * Builds the TOC list structure with sublist
	 * @returns {HTMLElement} The TOC list element
	 */
	createTOC() {
		const fragment = document.createDocumentFragment();

		// Create wrapper
		this.tocWrapper = document.createElement('div');
		this.tocWrapper.className = 'toc-wrapper';

		// Create TOC
		this.tocElement = document.createElement('div');
		this.tocElement.className = 'toc-container';

		this.tocDetailsElement = document.createElement('details');
		if (window.innerWidth > 1112) {
			this.tocDetailsElement.setAttribute('open', '');
		}

		const tocSummary = document.createElement('summary');
		tocSummary.innerHTML = `
		<span class="toc-title">${ this.tocTitle }</span>
		<span class="toc-toggle">
			<span class="toc-show">Show</span>
			<span class="toc-hide">Hide</span>
		</span>`;

		const tocNav = document.createElement('nav');
		tocNav.className = 'toc-nav';
		const tocList = this.buildTOCList();

		tocNav.appendChild(tocList);
		this.tocDetailsElement.appendChild(tocSummary);
		this.tocDetailsElement.appendChild(tocNav);
		this.tocElement.appendChild(this.tocDetailsElement);

		// Append TOC to wrapper
		this.tocWrapper.appendChild(this.tocElement);
		fragment.appendChild(this.tocWrapper);

		// Insert everything at once
		this.contentContainer.insertBefore(fragment, this.contentContainer.children[1]);

		// Set wrapper height to TOC height
		this.initialTOCHeight = this.tocElement.offsetHeight;
		this.tocWrapper.style.height = `${ this.initialTOCHeight }px`;

		// Resize observer for dynamic resizing
		this.setupResizeObserver();
	}

	buildTOCList() {
		const fragment = document.createDocumentFragment();
		const tocList = document.createElement('ol');
		tocList.className = 'toc-list';

		let currentLevel = 2; // h2
		let parentList = tocList;
		let parentStack = [tocList];

		this.headings.forEach(heading => {
			const level = parseInt(heading.tagName[1]);

			// Handle nested lists
			while (level > currentLevel) {
				const nestedList = document.createElement('ol');
				nestedList.className = 'toc-sublist';

				const lastItem = parentList.lastElementChild;
				if (lastItem) {
					lastItem.appendChild(nestedList);
					parentStack.push(parentList);
					parentList = nestedList;
				}
				currentLevel++;
			}

			while (level < currentLevel && parentStack.length > 1) {
				parentList = parentStack.pop();
				currentLevel--;
			}

			// Create the TOC item
			const listItem = document.createElement('li');
			listItem.className = `toc-item toc-level-${ level }`;

			const link = document.createElement('a');
			link.href = `#${ heading.id }`;
			link.textContent = heading.textContent;
			link.className = 'toc-link';
			link.dataset.targetId = heading.id;

			listItem.appendChild(link);
			parentList.appendChild(listItem);
		});

		fragment.appendChild(tocList);
		return fragment;
	}

	setupObservers() {
		this.intersectionObserver = new IntersectionObserver(entries => {
			entries.forEach(entry => {
				const link = this.tocElement.querySelector(`[data-target-id="${ entry.target.id }"]`);
				if (link) {
					link.classList.toggle('is-active', entry.isIntersecting);
				}
			});
		}, {
			rootMargin: `-${ this.headerHeight }px 0px 0px 0px`,
			threshold: 0.1
		});

		this.headings.forEach(heading => {
			this.intersectionObserver.observe(heading);
		});
	}

	setupResizeObserver() {
		if (this.resizeObserver) {
			this.resizeObserver.disconnect();
		}

		if ('ResizeObserver' in window) {
			this.resizeObserver = new ResizeObserver(() => {
				if (!this.isSticky) {
					this.initialTOCHeight = this.tocElement.offsetHeight;
					this.tocWrapper.style.height = `${ this.initialTOCHeight }px`;
				}
			});
			this.resizeObserver.observe(this.tocElement);
		}
	}

	setupEventListeners() {
		this.tocElement.querySelectorAll('.toc-link').forEach(link => {
			link.addEventListener('click', e => this.smoothScroll(e));
		});

		window.addEventListener('resize', this.u_debounced(() => {
			this.headerHeight = this.getHeaderHeight();
			this.updatePlaceholderHeight();

			// Toggle TOC open state on resize
			if (window.innerWidth <= 1112) {
				this.tocDetailsElement.removeAttribute('open');
			} else {
				this.tocDetailsElement.setAttribute('open', '');
			}
		}, 200));

		window.addEventListener('scroll', this.u_debounced(() => {
			this.handleStickyTOC();
		}, 100));

		// 🚀 Cleanup when the page is unloaded
		window.addEventListener('beforeunload', () => {
			if (this.resizeObserver) {
				this.resizeObserver.disconnect();
			}
		});
	}

	smoothScroll(e) {
		e.preventDefault();
		const targetId = e.target.dataset.targetId;
		const targetElement = document.getElementById(targetId);
		if (targetElement) {
			const targetPosition = targetElement.getBoundingClientRect().top + window.scrollY - this.headerHeight;

			window.scrollTo({
				top: targetPosition,
				behavior: 'smooth'
			});

			history.pushState(null, null, `#${ targetId }`);
		}
	}

	handleStickyTOC() {
		const scrollPosition = window.scrollY;
		const contentTop = this.contentContainer.offsetTop;
		const stickyOffset = 400;
		const contentBottom = contentTop + this.contentContainer.offsetHeight - this.tocElement.offsetHeight;

		if (scrollPosition > contentTop + stickyOffset && scrollPosition < contentBottom) {
			this.tocElement.classList.add('is-sticky');
			this.isSticky = true;
		} else {
			this.tocElement.classList.remove('is-sticky');
			this.isSticky = false;
		}
	}

	updatePlaceholderHeight() {
		this.tocPlaceholder.style.height = `${ this.initialTOCHeight }px`;
	}

	getHeaderHeight() {
		return parseInt(getComputedStyle(document.documentElement).getPropertyValue('--dst--header-height') || '0', 10);
	}
}

document.addEventListener('DOMContentLoaded', () => {
	new dstBlogToc();
});
