/**
 * Image Spinner Controls - Full Screen
 */

const isFullScreenOn = (elem, options) => {
    if (!elem) return options;

    let isFullScreen = elem.getAttribute('data-ctrl-fullscr');

    if (isFullScreen === 'true') {
        options.plugins.push('dsFullScreenControl');
    }

    return options;
}


export {
    isFullScreenOn
}