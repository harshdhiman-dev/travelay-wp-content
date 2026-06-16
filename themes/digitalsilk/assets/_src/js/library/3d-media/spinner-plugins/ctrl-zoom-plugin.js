/**
 * Image Spinner Plugin - dsZoomControl
 */

function zoomControl(spinnerElem) {
    let api = spinnerElem.spritespin('api');
    const spinnerModule = spinnerElem.closest('.m-image-spinner');

    const ctrlBttnZoom = spinnerModule.find('.js-image-spinner-zoom');

    if (0 < ctrlBttnZoom.length) {
        ctrlBttnZoom.on('click', function(e) {
            api.toggleZoom();
            spinnerModule.toggleClass('is-zoom');
        });
    }
}

const registerZoomControlPlugin = (label) => {
    SpriteSpin.registerPlugin(label, {
        onLoad: (ev) => {
            zoomControl($(ev.target));
        }
    });
}


export {
    registerZoomControlPlugin
}