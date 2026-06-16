const addSwipeEventHandlers = (wrapper) => {
	let startY = null;

	function onTouchStart(event) {
		startY = event.touches[0].clientY;
	}

	function onTouchMove(event) {
		if (startY === null) return;

		const {clientY: endY} = event.touches[0];
		const diffY = startY - endY;

		if (diffY < 0) {
			if (wrapper.classList.contains("is-expanded")) {
				wrapper.classList.remove("is-expanded");
				document.body.style.overflow = "auto";
			}
		}
		startY = null;
	}

	[wrapper, window].forEach(target => {
		target.addEventListener("touchstart", onTouchStart, {passive: true});
		target.addEventListener("touchmove", onTouchMove, {passive: false});
	});
};

const addResizeEventHandlers = (wrapper, observer) => {
	let isMobile = window.innerWidth <= 786;
	const resizeObserver = new ResizeObserver(entries => {
		for (let entry of entries) {
			const {width} = entry.contentRect;
			if (width > 786) {
				if(isMobile){
					observer.unobserve();
					wrapper.classList.remove("is-expanded");
					document.body.style.overflow = "auto";
					isMobile = false;
				}
			} else {
				if(!isMobile){
					observer.observe();
					isMobile = true;
				}

			}
		}
	});

	resizeObserver.observe(document.body);
};


const handleIntersect = (entries, wrapper) => {
	entries.forEach((entry) => {
		const rect = entry.target.getBoundingClientRect();
		if (!entry.isIntersecting && rect.top < 0) {

			wrapper.classList.add('is-fixed');
			entry.target.style.height = `${wrapper.scrollHeight}px`
		} else {
			wrapper.classList.remove('is-fixed');
			entry.target.style.height = 0;
		}
	});
};

const createObserver = (wrapper, trigger) => {
	let isObserve = false;
	let options = {
		root: null,
		rootMargin: "100px",
		threshold: 1,
	};
	const observer = new IntersectionObserver((entries, observer) => handleIntersect(entries, wrapper), options);
	const observe = () => setTimeout(() => {
		!isObserve && observer.observe(trigger);
		isObserve = true;
	}, 500);
	const unobserve = () => {
		observer.unobserve(trigger);
		wrapper.classList.remove('is-fixed');
		trigger.style.height = 0;
		isObserve = false;
	};
	return {observe, unobserve};
}


const adivahaSearchSwitcher = (selector, selectorSwitcher) => {
	const wrapper = document.querySelector(selector);

	if (wrapper) {
		const switcher = wrapper.querySelector(selectorSwitcher);

		if (switcher) {
			const trigger = document.createElement('div');
			const observer = createObserver(wrapper, trigger);
			trigger.classList.add('dst-adivaha-trigger');

			if (window.innerWidth <= 786) {
				observer.observe();
			}

			wrapper.after(trigger);

			switcher.addEventListener('click', (e) => {
				e.preventDefault();
				const isExpanded = wrapper.classList.contains('is-expanded');
				wrapper.classList.toggle('is-expanded', !isExpanded);
				document.body.style.overflow = !isExpanded ? 'hidden' : 'auto';
			});

			addSwipeEventHandlers(wrapper);
			addResizeEventHandlers(wrapper, observer);
		}
	}
};

export default adivahaSearchSwitcher;
