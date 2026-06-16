const dstMarqueeHoverEffect = () => {
	const marqueeSliders = document.querySelectorAll('.m-marquee');
	[...marqueeSliders].forEach(marqueeSlider => {
		const slides = marqueeSlider.querySelectorAll('.m-marquee__slide');
		[...slides].forEach((slide, index) => {
			slide.addEventListener('mouseover', function () {
				// Code to execute when mouse enters the element or its children
				let next = slides[index + 1];
				let prev = slides[index - 1];
				slide.classList.add('--hovered');

				if (next) {
					next.classList.add('--hovered-next');
					if (slides[index + 2]) {
						slides[index + 2].classList.add('--hovered--next');
					} else {
						slides[0].classList.add('--hovered--next');
					}
				} else {
					slides[0].classList.add('--hovered-next');
					slides[1].classList.add('--hovered--next');
				}

				if (prev) {
					prev.classList.add('--hovered-prev');
					if (slides[index - 2]) {
						slides[index - 2].classList.add('--hovered--prev');
					} else {
						slides[slides.length - 1].classList.add('-hovered--prev');
					}
				} else {
					slides[slides.length - 1].classList.add('--hovered-prev');
					slides[slides.length - 2].classList.add('--hovered--prev');
				}

			});

			slide.addEventListener('mouseout', function () {
				[...slides].forEach((slide, index) => {
					slide.classList.remove('--hovered', '--hovered-prev', '--hovered--prev', '--hovered-next', '--hovered--next');
				});
			});
		});
	});
};

export default dstMarqueeHoverEffect;
