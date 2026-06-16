/**
 * Image Spinner Controls - Hotspots navigation
 */

const isHotspotsOn = (elem, options) => {
    if (!elem) return options;

    let isHotspots = elem.getAttribute('data-spinner-has-hotspots');

    if (isHotspots === 'true') {
        options.plugins.push('dsHotSpots');
    }

    return options;
}


export {
    isHotspotsOn
}