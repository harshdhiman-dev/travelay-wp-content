import VimPlayer from '@vimeo/player';
import YouTubePlayer from 'youtube-player';
import DSMPAccordions from '../library/tabs-accordions/DSMPAccordions';
import { u_parseBool } from '../utils/u_types';
import DSMPMediaControls from '../library/media-controls/media-control';

/**
 * Initializes and controls the accordion videos functionality.
 *
 * @function dst_AccordionsVideos
 */
const dst_AccordionsVideos = () => {

    // const playVideo = {
    //     youtube: 'playVideo',
    //     vimeo: 'play',
    // };
    //
    // const pauseVideo = {
    //     youtube: 'pauseVideo',
    //     vimeo: 'pause',
    // };
    //
    // const stopVideo = {
    //     youtube: 'stopVideo',
    //     vimeo: 'pause',
    // };

    // const playa = new VimPlayer('vimeo-player');
    //
    // //playa[playVideo.vimeo]();
    //
    //
    // const ytPlaya = YouTubePlayer('ytu-player');

    // ytPlaya.loadVideoByUrl('https://www.youtube.com/embed/sZtTVSghzsg');
    // https://www.youtube.com/watch?v=sZtTVSghzsg
    // ytPlaya.loadVideoById('sZtTVSghzsg');
    // console.log(ytPlaya, ' playa');

    // ytPlaya[playVideo.youtube]();

    const wrapper = 'js-acc-auto';
    const wrapperList = document.querySelectorAll(`.${wrapper}`);

    wrapperList.forEach((acc, j) => {
        const accID = `${wrapper}${j}`;
        const callID = `#${accID}`;
        wrapperList[j].setAttribute('id', accID);

        const accordionVideo = acc;

        const accordionAutoplay = u_parseBool(acc.getAttribute('data-media-autoplay'));

        const accOn = new DSMPAccordions(`${callID}`);

        if (accordionAutoplay) {
            const accordionMedia = accordionVideo.querySelectorAll('.js-acc-media');

            const videoList = [];

            accordionMedia.forEach((media, i) => {
                const selector = media.querySelector('iframe');
                const customVideo = media.querySelector('.js-video-autoplay');

                if (selector) {
                    if (selector.src.indexOf('youtube') !== -1) {

                        const newVideoId = `${accID}-vid-${i}`;
                        selector.setAttribute('id', newVideoId);

                        if (selector.src.indexOf('enablejsapi=1') === -1) {
                            const newSrc = `${selector.src}?enablejsapi=1`;
                            selector.setAttribute('src', newSrc);
                        }

                        const addMedia = {};
                        addMedia.id = i;
                        addMedia.name = newVideoId;
                        addMedia.service = 'youtube';
                        videoList.push(addMedia);
                    }

                    if (selector.src.indexOf('vimeo') !== -1) {

                        const newVideoId = `${accID}-vid-${i}`;
                        selector.setAttribute('id', newVideoId);

                        const addMedia = {};
                        addMedia.id = i;
                        addMedia.name = newVideoId;
                        addMedia.service = 'vimeo';
                        videoList.push(addMedia);
                    }
                }

                if (customVideo) {
                    const newVideoId = `${accID}-vid-${i}`;
                    customVideo.setAttribute('id', newVideoId);

                    const addMedia = {};
                    addMedia.id = i;
                    addMedia.name = newVideoId;
                    addMedia.service = 'custom';
                    videoList.push(addMedia);
                }
            });

            videoList.forEach((video, i) => {
                if (video.service === 'youtube') {
                    videoList[i].player = YouTubePlayer(video.name);

                    videoList[i].player.on('stateChange', (event) => {
                        if (event.data === 0) {
                            videoList[i].player.stopVideo();
                            accOn.nextAccordion();
                        }
                    });
                }

                if (video.service === 'vimeo') {
                    videoList[i].player = new VimPlayer(video.name);

                    videoList[i].player.on('ended', () => {
                        accOn.nextAccordion();
                    });
                }

                if (video.service === 'custom') {
                    videoList[i].player = new DSMPMediaControls({
                        selector: `#${video.name}`,
                    });
                    const currVideo = document.getElementById(video.name);
                    currVideo.addEventListener('ended', () => {
                        accOn.nextAccordion();
                    });
                }

            });

            accOn.on('accordionChange', (accordion) => {
                // eslint-disable-next-line no-use-before-define
                stopVideoPlay(accordion.previousIndex);

                // eslint-disable-next-line no-use-before-define
                triggerVideoPlay(accordion.currentIndex);

            });

            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {

                    if (entry.intersectionRatio > 0.3) {
                        accordionVideo.style.backgroundColor = 'yellow';
                        // eslint-disable-next-line no-use-before-define
                        triggerVideoPlay(accOn.currentIndex);

                    } else {
                        accordionVideo.style.backgroundColor = '';
                        // eslint-disable-next-line no-use-before-define
                        stopVideoPlay(accOn.currentIndex);
                    }
                });
            }, {rootMargin: '-100px', threshold: 0.3});
            observer.observe(accordionVideo);

            const nextAcc = document.getElementById('nextAcc');
            const prevAcc = document.getElementById('prevAcc');

            nextAcc.addEventListener('click', () => {
                accOn.nextAccordion();
            });

            prevAcc.addEventListener('click', () => {
                accOn.prevAccordion();
            });

            const triggerVideoPlay = (compare) => {
                videoList.forEach((item, i) => {
                    if (item.id === compare) {
                        const serv = item.service;
                        if (serv === 'youtube') {
                            videoList[i].player.playVideo();
                        }

                        if (serv === 'vimeo') {
                            videoList[i].player.play();
                        }

                        if (serv === 'custom') {
                            const currVideo = document.getElementById(item.name);
                            videoList[i].player.startPlay(currVideo);
                        }
                    }
                });
            };

            const stopVideoPlay = (compare) => {
                videoList.forEach((item, i) => {
                    if (item.id === compare) {
                        const serv = item.service;
                        if (serv === 'youtube') {
                            videoList[i].player.pauseVideo();
                        }

                        if (serv === 'vimeo') {
                            videoList[i].player.pause();
                        }

                        if (serv === 'custom') {
                            const currVideo = document.getElementById(item.name);
                            videoList[i].player.stopPlay(currVideo);
                        }
                    }
                });
            };
        }

    });
};

export {
    dst_AccordionsVideos,
};
