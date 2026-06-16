/**
 * Image Spinner Plugin - dsFullScreenControl
 */

function fullscrControl(spinnerElem) {
    let api = spinnerElem.spritespin('api');
    const spinnerModule = spinnerElem.closest('.m-image-spinner');

    const ctrlBttnFullScr = spinnerModule.find('.js-image-spinner-fullscr');

    if (0 < ctrlBttnFullScr.length) {
        ctrlBttnFullScr.on('click', function(e) {
            api.requestFullscreen();
        });
    }
}

const registerFullscrControlPlugin = (label) => {
    SpriteSpin.registerPlugin(label, {
        onLoad: (ev) => {
            fullscrControl($(ev.target));
        }
    });
}


export {
    registerFullscrControlPlugin
}