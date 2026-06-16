/**
 * Image Spinner Plugin - dsPlaybackControl
 */

function playbackControl(spinnerElem) {
    let api = spinnerElem.spritespin('api');
    const spinnerModule = spinnerElem.closest('.m-image-spinner');
    const hotspotEl = spinnerModule.find('.hotspot');
    const hsContentListItem = spinnerModule.find('.js-hotspots-list-item');
    const ctrlBttnPlay = spinnerModule.find('.js-image-spinner-play');

    if (0 < ctrlBttnPlay.length) {
        ctrlBttnPlay.on('click', function(e) {

            // Get original 'reverse' setting
            api.data.reverse = api.data.forceReverse;

            api.toggleAnimation();

            if (true === api.isPlaying()) {
                hsContentListItem.removeClass('is-active');
                hotspotEl.removeClass('is-active');
                hotspotEl.hide();
                spinnerModule.addClass('is-playing');

            } else {
                spinnerModule.removeClass('is-playing');
            }
        });
    }
}

const registerPlaybackControlPlugin = (label) => {
    SpriteSpin.registerPlugin(label, {
        onLoad: (ev) => {
            playbackControl($(ev.target));
        }
    });
}


export {
    registerPlaybackControlPlugin
}