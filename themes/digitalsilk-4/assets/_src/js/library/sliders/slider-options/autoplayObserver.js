/**
 * Observe the intersection of elements to control autoplay functionality of sliders.
 *
 * @param {Array} items - An array of objects containing information about the sliders to observe.
 * @param {string} name - The common name prefix for the sliders.
 * @param {Array} sliders - An array of slider objects containing the autoplay functionality.
 */

const autoplayObserver = (items, name, sliders) => {
    const observerCallback = (entries) => {
        entries.forEach(entry => {
            const sIndex = parseInt(entry.target.getAttribute('id').replace(`${name}-`, ''), 10);
            if (entry.intersectionRatio > 0) {
                sliders[sIndex].autoplay.start();
            } else {
                sliders[sIndex].autoplay.stop();
            }
        });
    };

    const observer = new IntersectionObserver(observerCallback);

    items.forEach((observe) => {
        const target = document.querySelector(`#${observe.slider}`);
        observer.observe(target);
    });
};

export {
    autoplayObserver,
};
