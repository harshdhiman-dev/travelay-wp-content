/**
 * Pagination Slider Options
 */
import {u_parseBool} from '../../../utils/u_types';

/**
 * Checks if pagination is enabled and applies the appropriate options.
 * @param {HTMLElement} elem - The element to check for pagination attribute.
 * @param {object} options - The options object to apply pagination options to.
 * @returns {object} - The updated options object.
 */
const isPaginationOn = (elem, options) => {
  if (!elem) return options;

  const isPagination = elem.getAttribute('data-slider-pagination');

  if (isPagination) {
    options.pagination = {};
    let paginationEl;

    // Find the controls element first
    const controlsEl = elem.nextElementSibling;
    if (controlsEl && controlsEl.classList.contains('m-slider__controls')) {
      // Look for pagination within the controls
      paginationEl = controlsEl.querySelector('.m-slider__pagination');
      if (!paginationEl) {
        // Look for pagination as next sibling of controls
        paginationEl = controlsEl.nextElementSibling;
        if (paginationEl && !paginationEl.classList.contains('m-slider__pagination')) {
          paginationEl = null;
        }
      }
    }

    // Fallback to looking inside the slider if not found outside
    if (!paginationEl) {
      paginationEl = elem.querySelector('.m-slider__pagination');
      if (paginationEl && controlsEl) {
        controlsEl.appendChild(paginationEl);
      }
    }

    if (paginationEl) {
      options.pagination.el = paginationEl;
    }

    let leadingZero = false;

    if (isPagination === 'combo' || isPagination === 'fraction') {
      leadingZero = u_parseBool(elem.getAttribute('data-slider-leading-zero')) || false;
    }

    switch (isPagination) {
      case 'progressbar':
        options.pagination.type = 'progressbar';
        break;
      case 'fraction':
        options.pagination.type = 'fraction';
        options.pagination.formatFractionCurrent = function (number) {
          return leadingZero && number < 10 ? `0${number}` : number;
        };
        options.pagination.formatFractionTotal = function (number) {
          return leadingZero && number < 10 ? `0${number}` : number;
        };
        break;
      case 'combo':
        options.pagination.type = 'custom';
        options.pagination.renderCustom = function (swiper, current, total) {
          let totalFormatted = leadingZero && total < 10 ? `0${total}` : total;
          let currentFormatted = leadingZero && current < 10 ? `0${current}` : current;
          let progress = parseFloat(current / total).toFixed(5);

          return `<div class="swiper-pagination-progressbar swiper-pagination-horizontal" style="--data-current: ${current} ; --data-total: ${total}; --data-progress: ${progress}">
                    <span class="swiper-pagination-progressbar-fill"></span>
                  </div>
                  <div class="swiper-pagination-fraction">
                    <span class="swiper-pagination-current">${currentFormatted}</span>/
                    <span class="swiper-pagination-total">${totalFormatted}</span>
                  </div>`;
        };
        break;
      default:
        options.pagination.clickable = true;
    }

    if (isPagination === 'combo' && paginationEl) {
      paginationEl.classList.add('has-combo-progress');
    }

    options.keyboard = {};
    options.keyboard.enabled = true;
  }

  return options;
};


export {
  isPaginationOn,
};
