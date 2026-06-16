/**
 * Image Spinner Plugin - dsFramesNavControl
 */

function framesNavControl(spinnerElem) {
    let api = spinnerElem.spritespin('api');
    const spinnerModule = spinnerElem.closest('.m-image-spinner');
    const hotspotEl = spinnerModule.find('.hotspot');
    const hsContentListItem = spinnerModule.find('.js-hotspots-list-item');
    const ctrlBttnPrev = spinnerModule.find('.js-image-spinner-prev');
    const ctrlBttnNext = spinnerModule.find('.js-image-spinner-next');

    if (0 < ctrlBttnPrev.length) {
        ctrlBttnPrev.on('click', function(e) {
            // Get original 'reverse' setting
            api.data.reverse = api.data.forceReverse;
            api.prevFrame();

            // hide all hotspots
            hotspotEl.removeClass('is-active');
            hotspotEl.hide();
            // deactivate all labels
            hsContentListItem.removeClass('is-active');
            // show current hotspots for this frame
            api.data.stage.find(".hotspot.hotspot-frame-" + api.data.frame).stop(false).fadeIn();
        });
    }

    if (0 < ctrlBttnNext.length) {
        ctrlBttnNext.on('click', function(e) {
            // Get original 'reverse' setting
            api.data.reverse = api.data.forceReverse;
            api.nextFrame();

            // hide all hotspots
            hotspotEl.removeClass('is-active');
            hotspotEl.hide();
            // deactivate all labels
            hsContentListItem.removeClass('is-active');
            // show current hotspots for this frame
            api.data.stage.find(".hotspot.hotspot-frame-" + api.data.frame).stop(false).fadeIn();
        });
    }
}

const registerFramesNavControlPlugin = (label) => {
    SpriteSpin.registerPlugin(label, {
        onLoad: (ev) => {
            framesNavControl($(ev.target));
        }
    });
}


export {
    registerFramesNavControlPlugin
}
