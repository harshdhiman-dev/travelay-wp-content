/**
 * Add class on scroll for sticky header
 * @param {string} el      - selector for adding an active class
 * @param {string} elClass - active class
 */
import { u_throttled } from '../utils/utils';

/**
 * Constants used for offset calculations
 */
const OFFSET_DIVISOR = 5;
const THROTTLE_DELAY = 30;

/**
 * Variables to track the previous scroll position and sticky state
 */
let lastScrollY = 0;
let isSticky = false; // Tracks whether the header is currently sticky

/**
 * Determines if the header should be sticky and handle scroll direction classes.
 *
 * @param {Element} header             - The header element.
 * @param {string}  activeClass        - The class name to be added to the header when sticky.
 * @param {string}  scrollingDownClass - The class name to be added when scrolling down.
 * @param {string}  scrollingUpClass   - The class name to be added when scrolling up.
 * @param {number}  bottomThreshold    - The scroll bottom threshold.
 * @param {number}  topThreshold       - The scroll top threshold.
 * @param {number}  buffer             - Additional buffer to prevent rapid toggling.
 */
const updateHeaderClassOnScroll = (header, activeClass, scrollingDownClass, scrollingUpClass, bottomThreshold, topThreshold, buffer = 15) => {
  const currentScrollY = window.scrollY;

  // Add or remove the sticky class with buffer
  if (currentScrollY > bottomThreshold + buffer && !isSticky) {
    header.classList.add(activeClass);
    isSticky = true;
  } else if (currentScrollY <= topThreshold - buffer && isSticky) {
    header.classList.remove(activeClass);
    isSticky = false;
  }

  // Reset scroll direction classes when at the top of the page
  if (currentScrollY === 0) {
    header.classList.remove(scrollingDownClass, scrollingUpClass);
  }
  // Add scroll direction classes
  else if (currentScrollY > lastScrollY) {
    // Scrolling down
    header.classList.add(scrollingDownClass);
    header.classList.remove(scrollingUpClass);
  } else if (currentScrollY < lastScrollY) {
    // Scrolling up
    header.classList.remove(scrollingDownClass);
    header.classList.add(scrollingUpClass);
  }

  lastScrollY = currentScrollY;
};

/**
 * Initializes the sticky header functionality.
 *
 * @param {string} selector           - The element selector for the header.
 * @param {string} stickyClass        - The class name to be added to the header when sticky.
 * @param {string} scrollingDownClass - The class name to be added when scrolling down.
 * @param {string} scrollingUpClass   - The class name to be added when scrolling up.
 */
const dst_HeaderSticky = (selector, stickyClass, scrollingDownClass, scrollingUpClass) => {
  const headerElement = document.querySelector(selector);
  const elementHeight = headerElement.offsetHeight;
  const offset = elementHeight / OFFSET_DIVISOR;
  const bottomThreshold = elementHeight + offset;
  const topThreshold = elementHeight - offset;

  const onScroll = u_throttled(
      () => updateHeaderClassOnScroll(headerElement, stickyClass, scrollingDownClass, scrollingUpClass, bottomThreshold, topThreshold),
      THROTTLE_DELAY,
      false,
  );

  window.addEventListener('scroll', onScroll);

  // Check scroll position on the initial load
  updateHeaderClassOnScroll(headerElement, stickyClass, scrollingDownClass, scrollingUpClass, bottomThreshold, topThreshold);
};

export { dst_HeaderSticky };
