/**
 * Image Spinner Options - 'drag' plugin
 */

const isDragOn = (elem, options) => {
    if (!elem) return options;

    let isDrag = elem.getAttribute('data-spinner-drag');

    if (isDrag === 'true') {
        options.plugins.push('drag');
    }

    return options;
}


export {
    isDragOn
}