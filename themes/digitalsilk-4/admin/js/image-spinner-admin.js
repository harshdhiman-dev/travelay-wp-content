(function($) {
    /**
     * initializeBlock
     *
     * Adds custom JavaScript to the block HTML.
     *
     * @param   object $block The block $ element.
     * @param   object attributes The block attributes (only available when editing).
     * @return  void
     */


    var initializeBlock = function($block) {
        // config selectors
        const spinnerElemName = 'js-image-spinner';
        const spinnerModuleWrap = '.m-image-spinner';

        let spinnerModule = $block.find('.m-image-spinner');
        let spinnerElem = $block.find('.js-image-spinner');
        let imgPath = spinnerModule.attr('data-spinner-path');
        let imgPrefix = spinnerModule.attr('data-spinner-prefix');
        let imgDigits = spinnerModule.attr('data-spinner-digits');
        let imgCount = spinnerModule.attr('data-spinner-count');
        let imgExt = spinnerModule.attr('data-spinner-ext');

        if (imgPath && imgPrefix && imgDigits && imgCount && imgExt) {
            spinnerOptions = {
                source: SpriteSpin.sourceArray(imgPath + imgPrefix + '{frame}.' + imgExt, {
                    frame: [1, imgCount],
                    digits: imgDigits
                }),
                zoomUseClick: true,
                zoomPinFrame: false,
                sense: -1,
                responsive: true,
                animate: false,
                sizeMode: 'fit',
                renderer: 'canvas',
                preloadCount: 2,
                frameTime: 120,
                playToFrameTime: 10,
                reverse: false,
                forceReverse: false,
                plugins: [
                    '360',
                    //    'drag',
                    //    'zoom',
                ]
            };

            let isAnimate = spinnerModule.attr('data-spinner-autoanimate');

            if (isAnimate === 'true') {
                spinnerOptions.animate = true;
            }

            let firstFrameImgSRC = spinnerElem.attr('data-first-frame');

            checkIfImageExists(firstFrameImgSRC, (exists) => {
                if (exists) {
                    bootImageSpinner(spinnerElem, spinnerOptions);

                    let hasHotspots = spinnerModule.attr('data-spinner-has-hotspots');

                    if (hasHotspots === 'true') {
                        assignHotspots(spinnerElem);
                        hotspotsNav(spinnerElem);
                    }

                } else {
                    //   console.log(firstFrameImgSRC, 'img failed to load');
                }
            });
        }

    }

    function checkIfImageExists(url, callback) {
        let img = new Image();
        img.src = url;

        if (img.complete && img.naturalHeight !== 0) {
            callback(true);
        } else {
            img.onload = () => {
                callback(true);
            };

            img.onerror = () => {
                callback(false);
            };
        }
    }

    // Initialize dynamic block preview (editor).
    if (window.acf) {
        window.acf.addAction('render_block_preview/type=image-spinner', initializeBlock);
    }


    function bootImageSpinner(spinnerElem, spinnerOptions) {
        spinnerElem.spritespin(spinnerOptions);

        spinnerControls(spinnerElem);

        progressFraction(spinnerElem);
    }

    function spinnerControls(spinnerElem) {
        let api = spinnerElem.spritespin('api');
        let spinnerModule = spinnerElem.closest('.m-image-spinner');
        let ctrlBttnPlay = spinnerModule.find('.js-image-spinner-play');
        let ctrlBttnFullScr = spinnerModule.find('.js-image-spinner-fullscr');
        let ctrlBttnZoom = spinnerModule.find('.js-image-spinner-zoom');
        let ctrlBttnPrev = spinnerModule.find('.js-image-spinner-prev');
        let ctrlBttnNext = spinnerModule.find('.js-image-spinner-next');

        if (0 < ctrlBttnPlay.length) {
            ctrlBttnPlay.on('click', function(e) {
                api.data.stage.find(".hotspot").hide();

                // Get original 'reverse' setting
                api.data.reverse = api.data.forceReverse;

                api.toggleAnimation();

                if (true === api.isPlaying()) {
                    spinnerModule.addClass('is-playing');

                } else {
                    spinnerModule.removeClass('is-playing');
                }
            });
        }

        if (0 < ctrlBttnPrev.length) {
            ctrlBttnPrev.on('click', function(e) {
                api.data.reverse = api.data.forceReverse;
                api.prevFrame();
            });
        }

        if (0 < ctrlBttnNext.length) {
            ctrlBttnNext.on('click', function(e) {
                api.data.reverse = api.data.forceReverse;
                api.nextFrame();
            });
        }

        if (0 < ctrlBttnZoom.length) {
            ctrlBttnZoom.on('click', function(e) {
                api.toggleZoom();
                spinnerModule.toggleClass('is-zoom');
            });
        }

        if (0 < ctrlBttnFullScr.length) {
            ctrlBttnFullScr.on('click', function(e) {
                api.requestFullscreen();
            });
        }
    }

    function progressFraction(spinnerElem) {
        let api = spinnerElem.spritespin('api');
        let spinnerModule = spinnerElem.closest('.m-image-spinner');
        let spinnerFraction = spinnerModule.find('.image-spinner__fraction-current');
        let data = api.data;

        spinnerElem.bind("onFrame.spritespin", function() {
            data = api.data;
            spinnerFraction.text(data.frame + 1);
        });
    }

    function setHotspotFrameIndex(index, hotspots) {
        let hotspotFrameIndex = index;
        if (hotspotFrameIndex < 0) {
            hotspotFrameIndex = hotspots.length - 1;
        }
        if (hotspotFrameIndex >= hotspots.length) {
            hotspotFrameIndex = 0;
        }

        return hotspotFrameIndex;
    }

    function assignHotspots(spinnerElem) {
        let api = spinnerElem.spritespin('api');
        let data = api.data;
        let prevFrame = 0;

        let hotspotsHTML = spinnerElem.closest('.image-spinner').find(".hotspot");
        let hotspotFrameIndex = 0;
        let hotspots = [];

        spinnerElem.bind("onComplete.spritespin", function() {
            data = api.data;

            data.stage.prepend(hotspotsHTML);

            data.stage.find(".hotspot").hide();
            data.stage.find(".hotspot.hotspot-frame-0").fadeIn();

        }).bind("onFrame.spritespin", function() {
            data = api.data;
            prevFrame = data.state.playback.lastFrame;
            hotspots = data.stage.find(".hotspot");

            Array.from(hotspots).forEach(det => {
                if (data.frame > prevFrame) {
                    hotspotFrameIndex = setHotspotFrameIndex(hotspotFrameIndex + 1, hotspots);
                } else {
                    hotspotFrameIndex = setHotspotFrameIndex(hotspotFrameIndex - 1, hotspots);

                }
            });

            data.stage.find(".hotspot:visible").stop(false).fadeOut();
            data.stage.find(".hotspot.hotspot-frame-" + data.frame).stop(false).fadeIn();
        });

    }

    /**
     * Add hotspots navigation
     */
    function hotspotsNav(spinnerElem) {
        let api = spinnerElem.spritespin('api');
        let spinnerModule = spinnerElem.closest('.m-image-spinner');
        let ctrlBttnPrevHotspot = spinnerModule.find('.js-image-spinner-hotspot-prev');
        let ctrlBttnNextHotspot = spinnerModule.find('.js-image-spinner-hotspot-next');
        let hotspotFrameIndex = 0;
        let hotspots = [];
        let hs_frames_list = spinnerModule.attr('data-hotspots-frames');

        if (!hs_frames_list) {
            return;
        }

        let hs_frames = hs_frames_list.split(',');

        let hsContentList = spinnerModule.find('.js-hotspots-list');
        let hsContentListItem = hsContentList.find('.js-hotspots-list-item');

        hs_frames.forEach(function(hs) {
            hotspots.push(parseInt(hs));
        });

        hotspotFrameIndex = 0 < hsContentList.find('.hs-frame-0').length ? 0 : hotspots.length;

        ctrlBttnPrevHotspot.on('click', function(e) {
            hotspotFrameIndex = setHotspotFrameIndex(hotspotFrameIndex - 1, hotspots);
            api.playTo(hotspots[hotspotFrameIndex] - 1);
            spinnerModule.removeClass('is-playing');
            // highlight content item
            hsContentListItem.removeClass('is-active');
            hsContentList.find('.hs-frame-' + (hotspots[hotspotFrameIndex] - 1)).addClass('is-active');
        });

        ctrlBttnNextHotspot.on('click', function(e) {
            hotspotFrameIndex = setHotspotFrameIndex(hotspotFrameIndex + 1, hotspots);
            api.playTo(hotspots[hotspotFrameIndex] - 1);
            spinnerModule.removeClass('is-playing');
            // highlight content item
            hsContentListItem.removeClass('is-active');
            hsContentList.find('.hs-frame-' + (hotspots[hotspotFrameIndex] - 1)).addClass('is-active');
        });

        // Content navigation
        hsContentList.find('.hs-frame-0').addClass('is-active');
        Array.from(hsContentListItem).forEach(det => {
            $(det).on('click', function(e) {
                $(hsContentListItem).removeClass('is-active');
                $(det).addClass('is-active');

                let hotspotFrame = $(det).attr('data-hs-frame');

                hotspotFrame = parseInt(hotspotFrame) + 1;

                hotspotFrameIndex = parseInt(getObjKey(hotspots, hotspotFrame));

                api.data.stage.find(".hotspot").hide();

                api.playTo(hotspots[hotspotFrameIndex] - 1, { nearest: true });
                spinnerModule.removeClass('is-playing');
            });
        });
    }

    function getObjKey(obj, value) {
        return Object.keys(obj).find(key => obj[key] === value);
    }
    /*
    // Initialize each block on page load (front end).
    $(document).ready(function(){
        $('.wp-block-acf-image-spinner').each(function(){
            initializeBlock( $(this) );
        });
    });
*/
})(jQuery);