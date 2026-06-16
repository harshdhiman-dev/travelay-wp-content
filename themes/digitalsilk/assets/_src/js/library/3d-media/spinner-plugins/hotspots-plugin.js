/**
 * Image Spinner Plugin - dsHotSpots
 */

function normalizeItemIndex(index, arr) {
    let itemIndex = index;

    if (itemIndex < 0) {
        itemIndex = arr.length - 1;
    }
    if (itemIndex >= arr.length) {
        itemIndex = 0;
    }

    return itemIndex;
}

/**
 * Append hotspots to spinner stage
 */
function assignHotspots(spinnerElem) {
    const spinnerModule = spinnerElem.closest('.m-image-spinner');

    if (!spinnerModule.attr('data-spinner-has-hotspots')) {
        return;
    }

    if (!spinnerModule.attr('data-hotspots-frames')) {
        return;
    }

    const hotspotEl = spinnerModule.find('.hotspot');

    let api = spinnerElem.spritespin('api');
    let data = api.data;

    let hotspotsHTML = spinnerModule.find(".hotspot");

    spinnerElem.bind("onComplete.spritespin", function() {
        //   data = api.data;

        // prepend all hotspots on spinner init
        data.stage.prepend(hotspotsHTML);

        // initially show only those hotspots that exist on first frame
        data.stage.find(".hotspot").hide();
        data.stage.find(".hotspot-frame-0").fadeIn();

    }).bind("onAnimationStop.spritespin", function() {
        // get data for current state
        data = api.data;

        // show hotspots on current frame
        hotspotEl.hide();
        data.stage.find(".hotspot.hotspot-frame-" + data.frame).stop(false).fadeIn();
    });

    // Hide tooltip on close bttn
    hotspotEl.on('click', '.hotspot__tooltip-close', function(e) {
        hotspotEl.removeClass('is-active');
        spinnerModule.find('.js-hotspots-list-item').removeClass('is-active');
    });

    // Hide tooltip on hitting the Esc key
    $(document).keyup(function(e) {
        if (27 === e.keyCode) {
            hotspotEl.removeClass('is-active');
            spinnerModule.find('.js-hotspots-list-item').removeClass('is-active');
        }
    });

    // Hide tooltip on clicking outside of it
    $(document).on('click', function(e) {
        if ((0 === $(e.target).closest($('.hotspot')).length) && (0 === $(e.target).closest($('.hotspots-content')).length)) {
            hotspotEl.removeClass('is-active');
            spinnerModule.find('.js-hotspots-list-item').removeClass('is-active');
        }
    });
}


/**
 * Add hotspots navigation
 */
function hotspotsNav(spinnerElem) {
    const spinnerModule = spinnerElem.closest('.m-image-spinner');
    if (!spinnerModule.attr('data-spinner-has-hotspots')) {
        return;
    }
    if (!spinnerModule.attr('data-hotspots-frames')) {
        return;
    }

    const hs_frames_list = spinnerModule.attr('data-hotspots-frames');
    const hs_frames = hs_frames_list.split(',');
    const hsContentList = spinnerModule.find('.js-hotspots-list');
    const hsContentListItem = hsContentList.find('.js-hotspots-list-item');
    const hotspotEl = spinnerModule.find('.hotspot');

    let api = spinnerElem.spritespin('api');
    let hotspots = [];
    let activeFrameIndex = api.data.frame;
    let activeHotspot,
        activeHotspotIndex;

    hs_frames.forEach(function(hs) {
        hotspots.push(parseInt(hs));
    });

    /**
     * Set active hotspot
     */
    function setActiveHotspot(activeHotspotIndex, deactivateHotspot) {
        // deactivate all hotspots
        hotspotEl.removeClass('is-active');
        hsContentListItem.removeClass('is-active');

        // if the hotspot is already active, close it
        if (deactivateHotspot) {
            return;
        }

        // get the new hotspot and its frame
        activeHotspot = api.data.stage.find(".hotspot.hotspot-index-" + activeHotspotIndex);

        activeFrameIndex = hotspots[activeHotspotIndex];

        // if the new hotspot is not the same frame,
        // hide all hotspots,
        // and navigate spinner to the according one
        if (activeFrameIndex - 1 !== api.data.frame) {
            hotspotEl.hide();
            api.playTo(activeFrameIndex - 1, { nearest: true });
        }

        // activate current hotspot and its content
        activeHotspot.addClass('is-active');
        hsContentList.find('.hs-index-' + activeHotspotIndex).addClass('is-active');

    }

    /**
     * Navigate through hotspots' pins
     */
    Array.from(hotspotEl).forEach(hs => {
        $(hs).on('click', '.js-hotspot-pin', function(e) {
            activeHotspotIndex = $(hs).attr('data-hotspot-index');
            activeHotspotIndex = parseInt(activeHotspotIndex);

            let deactivateHotspot = $(hs).hasClass('is-active');
            setActiveHotspot(activeHotspotIndex, deactivateHotspot);
        });
    });


    /**
     * Navigate through hotspots' content
     */
    Array.from(hsContentListItem).forEach(det => {
        $(det).on('click', function(e) {
            activeHotspotIndex = $(this).attr('data-hs-index');
            activeHotspotIndex = parseInt(activeHotspotIndex);

            let deactivateHotspot = $(this).hasClass('is-active');
            setActiveHotspot(activeHotspotIndex, deactivateHotspot);
        });
    });


    /**
     * Prev/Next navigation
     */
    if (spinnerModule.attr('data-ctrl-hotspots-nav')) {
        const ctrlBttnPrevHotspot = spinnerModule.find('.js-image-spinner-hotspot-prev');
        const ctrlBttnNextHotspot = spinnerModule.find('.js-image-spinner-hotspot-next');

        activeFrameIndex = api.data.frame;

        ctrlBttnPrevHotspot.on('click', function(e) {
            activeHotspot = api.data.stage.find(".hotspot.is-active");

            if (0 < activeHotspot.length) {
                activeHotspotIndex = activeHotspot.attr('data-hotspot-index');
            } else {
                activeHotspotIndex = 0;
            }

            activeHotspotIndex = parseInt(activeHotspotIndex);
            activeHotspotIndex--;
            activeHotspotIndex = normalizeItemIndex(activeHotspotIndex, hotspots);

            setActiveHotspot(activeHotspotIndex);
        });

        ctrlBttnNextHotspot.on('click', function(e) {
            activeHotspot = api.data.stage.find(".hotspot.is-active");

            if (0 < activeHotspot.length) {
                activeHotspotIndex = activeHotspot.attr('data-hotspot-index');

            } else {
                activeHotspotIndex = hotspots.length;
            }

            activeHotspotIndex = parseInt(activeHotspotIndex);
            activeHotspotIndex++;
            activeHotspotIndex = normalizeItemIndex(activeHotspotIndex, hotspots);

            setActiveHotspot(activeHotspotIndex);
        });
    }
}

/*
function getObjKey(obj, value) {
    return Object.keys(obj).find(key => obj[key] === value);
}
*/

const registerHotSpotsPlugin = (label) => {
    SpriteSpin.registerPlugin(label, {
        onLoad: (ev) => {
            assignHotspots($(ev.target));
            hotspotsNav($(ev.target));
        }
    });
}


export {
    registerHotSpotsPlugin
}