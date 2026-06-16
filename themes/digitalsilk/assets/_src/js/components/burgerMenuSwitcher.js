const burgerMenuSwitcher = (selector, selectorSwitcher) => {
	const wrapper = document.querySelector(selector);
	const footer = document.querySelector('.site-footer');

	if (wrapper) {
		const switcher = wrapper.querySelector(selectorSwitcher);
		const linksWrapper = wrapper.querySelector('.js-menu-links');
		const links = [...linksWrapper.children];

		if (switcher && links[0]) {
			const isHoverableDevice = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
			let activeLinkIndex = null;
			let isMobile = window.innerWidth <= 786;

			if (isHoverableDevice) {
				wrapper.classList.add('is-hoverable');
			}

			links.forEach((item, index) => {
				item.children[0]?.addEventListener('click', (e) => {
					if (isHoverableDevice && !isMobile) return;
					const isOpen = item.classList.contains('is-open');
					if (isOpen) {
						activeLinkIndex = null;
					} else {
						if (activeLinkIndex !== index && activeLinkIndex !== null) {
							links[activeLinkIndex].classList.remove('is-open');
						}
						activeLinkIndex = index;
					}
					item.classList.toggle('is-open', !isOpen);
				});
			});

			const openMenu = () => {
				wrapper.classList.add('is-active');
				if (isMobile) {
					document.body.style.overflow = 'hidden';
					footer.style.zIndex = '20';
				}
				if (!isHoverableDevice && !isMobile) {
					links[0].classList.add('is-open');
					activeLinkIndex = 0;
				}
			};

			const closeMenu = () => {
				wrapper.classList.remove('is-active');
				document.body.style.overflow = 'auto';
				footer.style.zIndex = '0';
				links[activeLinkIndex]?.classList.remove('is-open');
				activeLinkIndex = null;
			};

			switcher.addEventListener('click', (e) => {

				e.preventDefault();
				const isActive = wrapper.classList.contains('is-active');
				isActive ? closeMenu() : openMenu();


			});

			const resizeObserver = new ResizeObserver(entries => {
				for (let entry of entries) {
					const {width} = entry.contentRect;
					if (width > 786) {
						if (isMobile) {
							closeMenu()
							isMobile = false;
						}
					} else {
						if (!isMobile) {
							closeMenu();
							isMobile = true;
						}

					}
				}
			});

			resizeObserver.observe(document.body);
		}
	}
};

export default burgerMenuSwitcher;
