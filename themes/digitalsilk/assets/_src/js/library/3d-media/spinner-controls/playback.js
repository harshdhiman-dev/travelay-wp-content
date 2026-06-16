/**
 * Image Spinner Controls - Playback
 */

const isPlaybackOn = (elem, options) => {
    if (!elem) return options;

    let isPlayback = elem.getAttribute('data-ctrl-playback');

    if (isPlayback === 'true') {
        options.plugins.push('dsPlaybackControl');
    }

    return options;
}


export {
    isPlaybackOn
}