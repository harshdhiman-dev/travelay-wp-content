/**
 * Retrieve the computed style of an element.
 *
 * @param {Element} el - The element to retrieve the computed style from.
 * @return {CSSStyleDeclaration} - The computed style object of the element.
 */
const u_computedStyle = (el) => {
    let style;
    if (window.getComputedStyle) {
        style = window.getComputedStyle(el, null);
    }
    if (!style && el.currentStyle) {
        style = el.currentStyle;
    }
    if (!style) {
        style = el.style;
    }
    return style;
};

/**
 * Calculates the total height of an element, including its content, padding, borders, and margins.
 *
 * @param {Element} el - The element for which to calculate the height.
 * @return {number} The total height of the element in pixels.
 */
const u_getElementHeight = (el) => {
    let height = el.clientHeight;
    let getStyle = u_computedStyle(el);
    height += parseInt(getStyle.marginTop, 10);
    height += parseInt(getStyle.marginBottom, 10);
    height += parseInt(getStyle.borderTopWidth, 10);
    height += parseInt(getStyle.borderBottomWidth, 10);
    return height;
};

/**
 * Function to get the translate value of an element along the specified axis.
 *
 * @param {Element} el         - The element to get the translate value from.
 * @param {string}  [axis='x'] - The axis along which to get the translate value. Can be 'x' or 'y'.
 * @return {number} - The translate value of the element along the specified axis.
 */
const u_getTranslate = (el, axis = 'x') => {
    const window = getWindow();
    let matrix;
    let curTransform;
    let transformMatrix;

    const curStyle = getComputedStyle(el, null);

    if (window.WebKitCSSMatrix) {
        curTransform = curStyle.transform || curStyle.webkitTransform;
        if (curTransform.split(',').length > 6) {
            curTransform = curTransform
                .split(', ')
                .map((a) => a.replace(',', '.'))
                .join(', ');
        }
        // Some old versions of Webkit choke when 'none' is passed; pass
        // empty string instead in this case
        transformMatrix = new window.WebKitCSSMatrix(curTransform === 'none' ? '' : curTransform);
    } else {
        transformMatrix =
            curStyle.MozTransform ||
            curStyle.OTransform ||
            curStyle.MsTransform ||
            curStyle.msTransform ||
            curStyle.transform ||
            curStyle.getPropertyValue('transform').replace('translate(', 'matrix(1, 0, 0, 1,');
        matrix = transformMatrix.toString().split(',');
    }

    if (axis === 'x') {
        // Latest Chrome and webkits Fix
        if (window.WebKitCSSMatrix) curTransform = transformMatrix.m41;
        // Crazy IE10 Matrix
        else if (matrix.length === 16) curTransform = parseFloat(matrix[12]);
        // Normal Browsers
        else curTransform = parseFloat(matrix[4]);
    }
    if (axis === 'y') {
        // Latest Chrome and webkits Fix
        if (window.WebKitCSSMatrix) curTransform = transformMatrix.m42;
        // Crazy IE10 Matrix
        else if (matrix.length === 16) curTransform = parseFloat(matrix[13]);
        // Normal Browsers
        else curTransform = parseFloat(matrix[5]);
    }
    return curTransform || 0;
};

export {
    u_getElementHeight,
    u_computedStyle,
    u_getTranslate,
};
