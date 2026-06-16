// call each tab options as single instance
/**
 * Retrieves the tabs timeline items from the DOM.
 *
 * @returns {NodeList} - A collection of tabs timeline items.
 */
const getTabsTimeline = document.querySelectorAll('.js-tabs-timeline .js-tabs-nav-item');
/**
 * Returns an array representing the elements of the 'getTabsTimeline' object.
 *
 * @function getTabsTimelineList
 * @returns {Array} - An array containing the elements of 'getTabsTimeline'.
 */
const getTabsTimelineList = Array.prototype.slice.call(getTabsTimeline);

/**
 * Returns an array containing all elements before a given element in the `getTabsTimelineList` array.
 *
 * @param {*} current - The element to get all elements before.
 * @return {Array} - An array containing all elements before the given element.
 */
function getAllBefore(current) {
    const i = getTabsTimelineList.indexOf(current);

    return i > -1 ? getTabsTimelineList.slice(0, i) : [];
}

getTabsTimeline.forEach((item) => {
    item.addEventListener('click', (e) => {
        item.classList.remove('js-timeline-active');
        const getActiveTabs = getAllBefore(e.currentTarget);

        getActiveTabs.forEach((tab) => {
            tab.classList.add('js-timeline-active');
        });
        e.currentTarget.classList.add('js-timeline-active');
    });
});
