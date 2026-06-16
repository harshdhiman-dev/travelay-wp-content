/**
 * Simple Image Spinner
 */
import { registerPlaybackControlPlugin } from '../../library/3d-media/spinner-plugins/ctrl-playback-plugin';
import { registerFramesNavControlPlugin } from '../../library/3d-media/spinner-plugins/ctrl-frames-nav-plugin';
import { registerZoomControlPlugin } from '../../library/3d-media/spinner-plugins/ctrl-zoom-plugin';
import { registerFullscrControlPlugin } from '../../library/3d-media/spinner-plugins/ctrl-fullscreen-plugin';
import { registerProgressFractionPlugin } from '../../library/3d-media/spinner-plugins/progress-fraction-plugin';
import { registerHotSpotsPlugin } from '../../library/3d-media/spinner-plugins/hotspots-plugin';
import { isDragOn } from '../../library/3d-media/spinner-plugins/ctrl-drag-plugin';
import { isPlaybackOn } from '../../library/3d-media/spinner-controls/playback';
import { isFramesNavOn } from '../../library/3d-media/spinner-controls/frames-nav';
import { isZoomOn } from '../../library/3d-media/spinner-controls/zoom';
import { isFullScreenOn } from '../../library/3d-media/spinner-controls/fullscreen';
import { isFractionOn } from '../../library/3d-media/spinner-controls/progress-fraction';
import { isAnimateOn } from '../../library/3d-media/spinner-controls/autoanimate';
import { isHotspotsOn } from '../../library/3d-media/spinner-controls/hotspots-nav';

// config selectors
/**
 * The name of the spinner element.
 *
 * @type {string}
 */
const spinnerElemName = 'js-image-spinner';
/**
 * Represents the spinner module wrapper selector.
 *
 * @constant {string} spinnerModuleWrap
 * @description This constant holds the CSS selector for the spinner module wrapper element.
 */
const spinnerModuleWrap = '.m-image-spinner';

// get all spinners
/**
 * Retrieves a list of DOM elements that match a specific CSS selector.
 *
 * @param {string} spinnerModuleWrap - The CSS selector to match against.
 * @returns {NodeList} - The list of DOM elements matching the selector.
 */
const spinnerModuleList = document.querySelectorAll(spinnerModuleWrap);

/**
 * Initializes image spinners based on the spinnerModuleList array.
 */
const callImageSpinners = () => {

    if (!spinnerModuleList.length) {
        return;
    }

    const spinnerOptions = [];

    // loop through spinners and assign them IDs
    spinnerModuleList.forEach((spinnerModule, i) => {
        const imgSpinnerElem = spinnerModule.querySelector('.js-image-spinner');
        const imgPath = spinnerModule.getAttribute('data-spinner-path');
        const imgPrefix = spinnerModule.getAttribute('data-spinner-prefix');
        const imgDigits = spinnerModule.getAttribute('data-spinner-digits');
        const imgCount = spinnerModule.getAttribute('data-spinner-count');
        const imgExt = spinnerModule.getAttribute('data-spinner-ext');

        if (!(imgPath || imgPrefix || imgDigits || imgCount || imgExt)) {
            return;
        }

        const spinnerID = `${spinnerElemName}-${i}`;
        imgSpinnerElem.setAttribute('id', spinnerID);

        spinnerOptions[i] = {
            source: SpriteSpin.sourceArray(imgPath + '/' + imgPrefix + '{frame}.' + imgExt, {
                frame: [1, imgCount],
                digits: imgDigits,
            }),
            // use double click to in/out (default is true)
            zoomUseClick: true,
            // prevents changing the frame during zoom (default is true)
            zoomPinFrame: false,
            sense: -1,
            responsive: true,
            animate: false,
            sizeMode: 'fit',
            renderer: 'canvas',
            preloadCount: 2,
            // animation speed
            frameTime: 120,
            playToFrameTime: 10,
            reverse: false,
            // Make sure to use the same value for forceReverse, in case it gets changed by 'nearest' frame hs option
            forceReverse: false,
            plugins: [
                '360', // display plugin
                //    'drag', // interaction plugin - optional per module settings
                // native zoom plugin is triggered via dsZoomControl
                //    'zoom',
            ],
        };

        // plugins
        spinnerOptions[i] = isFractionOn(spinnerModule, spinnerOptions[i]);
        spinnerOptions[i] = isFramesNavOn(spinnerModule, spinnerOptions[i]);
        spinnerOptions[i] = isZoomOn(spinnerModule, spinnerOptions[i]);
        spinnerOptions[i] = isFullScreenOn(spinnerModule, spinnerOptions[i]);
        spinnerOptions[i] = isPlaybackOn(spinnerModule, spinnerOptions[i]);
        spinnerOptions[i] = isHotspotsOn(spinnerModule, spinnerOptions[i]);
        spinnerOptions[i] = isDragOn(spinnerModule, spinnerOptions[i]);

        // other options
        spinnerOptions[i] = isAnimateOn(spinnerModule, spinnerOptions[i]);

        bootImageSpinner(`#${spinnerID}`, spinnerOptions[i]);
    });

    registerPlaybackControlPlugin('dsPlaybackControl');
    registerFramesNavControlPlugin('dsFramesNavControl');
    registerZoomControlPlugin('dsZoomControl');
    registerFullscrControlPlugin('dsFullScreenControl');
    registerProgressFractionPlugin('dsProgressFraction');
    registerHotSpotsPlugin('dsHotSpots');
};

/**
 * Boots an image spinner using the given selector and options.
 *
 * @param {string} selector - The selector to select the element which the image spinner will be attached to.
 * @param {Object} options - The options to configure the image spinner.
 *
 * @return {void}
 */
function bootImageSpinner(selector, options) {
    if ("IntersectionObserver" in window) {
        // Browser supports IntersectionObserver so use that to defer the boot
        let observer = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    observer.unobserve(entry.target);

                    $(entry.target).spritespin(options);
                }
            });
        });
        observer.observe($(selector)[0]);
    } else {
        // Browser does not support IntersectionObserver so boot instantly
        $(selector).spritespin(options);
        //   console.log("spinner booted by default", selector, options);
    }
}

export {
    callImageSpinners,
};
