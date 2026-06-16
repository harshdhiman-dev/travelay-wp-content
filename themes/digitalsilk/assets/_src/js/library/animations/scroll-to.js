/**
 * TODO: rework it to use request animation frame
 * https://stackoverflow.com/questions/21474678/scrolltop-animation-without-jquery
 *
 * taken from
 * https://gist.github.com/andjosh/6764939
 * https://github.com/alvarotrigo/skrollTop.js/blob/master/skrollTop.js
 *
 */
Math.easeInOutCubic = function (t, b, c, d) {
    if ((t /= d / 2) < 1) return c / 2 * t * t * t + b;
    return c / 2 * ((t -= 2) * t * t + 2) + b;
};

export const scrollToUtil = (params) => {
    let element = typeof params.element !== 'undefined' ? params.element : window;
    let to = params.to;
    let duration = typeof params.duration !== 'undefined' ? params.duration : 250;
    let callback = typeof params.callback !== 'undefined' ? params.callback : null;
    let easing = typeof params.easing !== 'undefined' ? params.easing : Math.easeInOutCubic;

    let start = element !== window ? element.scrollTop : (window.pageYOffset || document.documentElement.scrollTop) - (document.documentElement.clientTop || 0);
    let change = to - start;
    let currentTime = 0;
    let increment = 16; //same amount of milliseconds as requestAnimationFrame

    const animateScroll = () => {

        currentTime += increment;
        var easingValue = duration ? easing(currentTime, start, change, duration) : to;
        element.scrollTo(0, easingValue);

        if (currentTime < duration) {
            setTimeout(animateScroll, increment);
        } else if (callback) {
            callback();
        }
    };

    animateScroll();
};
