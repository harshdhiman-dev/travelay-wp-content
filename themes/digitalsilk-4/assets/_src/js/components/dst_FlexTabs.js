/**
 * Reveal hero on scroll
 */

const dst_FlexTabs = () => {
  const container = document.querySelector('.js-tabs-hide');

  if (container) {

    const primary = container.querySelector('.-primary');
    const primaryItems = primary.querySelectorAll(':not(.-more) > li');
    container.classList.add('--jsfied');

    // Insert "more" button and duplicate the list
    primary.insertAdjacentHTML('beforeend', `
    <li class="-more">
      <button type="button" aria-haspopup="true" aria-expanded="false">
        More <span>&darr;</span>
      </button>
      <ul class="-secondary">${primary.innerHTML}</ul>
    </li>
  `);

    const secondary = container.querySelector('.-secondary');
    const secondaryItems = secondary.querySelectorAll('li');
    const allItems = container.querySelectorAll('li');
    const moreLi = primary.querySelector('.-more');
    const moreBtn = moreLi.querySelector('button');

    // Use a single event listener for the button click and outside click
    document.addEventListener('click', (e) => {
      if (e.target === moreBtn) {
        e.preventDefault();
        container.classList.toggle('--show-secondary');
        moreBtn.setAttribute('aria-expanded', container.classList.contains('--show-secondary'));
      } else if (!container.contains(e.target)) {
        container.classList.remove('--show-secondary');
        moreBtn.setAttribute('aria-expanded', false);
      }
    });

    // Adapt tabs on load and resize
    const adaptTabs = () => {
      // Reveal all items for the calculation
      allItems.forEach((item) => item.classList.remove('--hidden'));

      // Hide items that won't fit in the Primary
      const hiddenItems = [];
      const primaryWidth = primary.offsetWidth;
      let stopWidth = moreBtn.offsetWidth;
      primaryItems.forEach((item, i) => {
        if (primaryWidth >= stopWidth + item.offsetWidth) {
          stopWidth += item.offsetWidth;
        } else {
          item.classList.add('--hidden');
          hiddenItems.push(i);
        }
      });

      // Toggle the visibility of More button and items in Secondary
      if (!hiddenItems.length) {
        moreLi.classList.add('--hidden');
        container.classList.remove('--show-secondary');
        moreBtn.setAttribute('aria-expanded', false);
      } else {
        secondaryItems.forEach((item, i) => {
          if (!hiddenItems.includes(i)) {
            item.classList.add('--hidden');
          }
        });
      }
    };

    adaptTabs(); // Adapt immediately on load

    // Create a ResizeObserver to observe changes to the primary element's size
    const resizeObserver = new ResizeObserver(() => {
      adaptTabs();
    });

    // Observe the primary element's size and the more button's size
    resizeObserver.observe(primary);
    resizeObserver.observe(moreBtn);
  }
};

export { dst_FlexTabs };
