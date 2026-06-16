/**
 * usage:   elementFromTop(elemTrigger, elemTarget, classToAdd, distanceFromTop, unit);
 *
 * http://blog.fofwebdesign.co.uk/41-add-classes-to-an-element-when-scrolled-into-viewport
 */
import { u_addClass, u_hasClass, u_delClass } from './u_classlist-add-remove';

const u_elementFromTop = (elemTrigger, elemTarget, classToAdd, distanceFromTop, unit) => {
    // eslint-disable-next-line
    let winY = window.innerHeight || document.documentElement.clientHeight,
        // eslint-disable-next-line
        elTriggerLength = elemTrigger.length,
        elTargetLength,
        distTop,
        distPercent,
        distPixels,
        distUnit,
        elTarget,
        i,
        j;

    for (i = 0; i < elTriggerLength; ++i) {
        elTarget = document.querySelectorAll(elemTarget);
        elTargetLength = elTarget.length;
        distTop = elemTrigger[i].getBoundingClientRect().top;
        distPercent = Math.round((distTop / winY) * 100);
        distPixels = Math.round(distTop);
        distUnit = unit === 'percent' ? distPercent : distPixels;

        if (distUnit <= distanceFromTop) {
            if (!u_hasClass(elemTrigger[i], elemTarget)) {
                for (j = 0; j < elTargetLength; ++j) {
                    if (!u_hasClass(elTarget[j], classToAdd)) {
                        u_addClass(elTarget[j], classToAdd);
                    }
                }
            } else if (!u_hasClass(elemTrigger[i], classToAdd)) {
                u_addClass(elemTrigger[i], classToAdd);
            }
        } else {
            u_delClass(elemTrigger[i], classToAdd);
            if (!u_hasClass(elemTrigger[i], elemTarget)) {
                for (j = 0; j < elTargetLength; ++j) {
                    u_delClass(elTarget[j], classToAdd);
                }
            }
        }
    }
};

/**
 * Checks if the given element is in the current viewport.
 *
 * @param {Element} el - The element to check.
 * @return {boolean} - true if the element is in the viewport, false otherwise.
 */
const u_isElementIsInView = (el) => {
    const scroll = window.scrollY || window.pageYOffset;
    const boundsTop = el.getBoundingClientRect().top + scroll;

    const viewport = {
        top: scroll,
        bottom: scroll + window.innerHeight,
    };

    const bounds = {
        top: boundsTop,
        bottom: boundsTop + el.clientHeight,
    };

    return (bounds.bottom >= viewport.top && bounds.bottom <= viewport.bottom) || (bounds.top <= viewport.bottom && bounds.top >= viewport.top);
};

export { u_elementFromTop, u_isElementIsInView };
