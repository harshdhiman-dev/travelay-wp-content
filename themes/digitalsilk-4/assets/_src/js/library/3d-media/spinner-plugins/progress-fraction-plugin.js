/**
 * Image Spinner Plugin - dsProgressFraction
 */

function progressFraction(spinnerElem) {
    let api = spinnerElem.spritespin('api');
    const spinnerModule = spinnerElem.closest('.m-image-spinner');
    const spinnerFraction = spinnerModule.find('.image-spinner__fraction-current');
    let data = api.data;

    spinnerElem.bind("onFrame.spritespin", function() {
        data = api.data;
        spinnerFraction.text(data.frame + 1);
    });
}

const registerProgressFractionPlugin = (label) => {
    SpriteSpin.registerPlugin(label, {
        onLoad: (ev) => {
            progressFraction($(ev.target));
        }
    });
}


export {
    registerProgressFractionPlugin
}