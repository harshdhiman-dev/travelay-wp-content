/**
 * Image Spinner Controls - Zoom
 */

const isZoomOn = (elem, options) => {
    if (!elem) return options;

    let isZoom = elem.getAttribute('data-ctrl-zoom');

    if (isZoom === 'true') {
        options.plugins.push('dsZoomControl', 'zoom');
    }

    return options;
}


export {
    isZoomOn
}