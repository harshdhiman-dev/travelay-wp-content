/**
 * Image Spinner Controls - Frame by frame navigation
 */

const isFramesNavOn = (elem, options) => {
    if (!elem) return options;

    let isFramesNav = elem.getAttribute('data-ctrl-frames-nav');

    if (isFramesNav === 'true') {
        options.plugins.push('dsFramesNavControl');
    }

    return options;
}


export {
    isFramesNavOn
}