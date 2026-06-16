/**
 * Checks if the given value is an integer.
 * @param {any} value - The value to be checked.
 * @return {boolean} - true if the value is an integer, false otherwise.
 */
const u_isInteger = (value) => {
    if (Number.isInteger) {
        return Number.isInteger(value);
    }
        // eslint-disable-next-line no-restricted-globals
        return typeof value === 'number' && isFinite(value) && Math.floor(value) === value;

};

/**
 * Checks if the given input is an object.
 *
 * @param {*} o - The input to be checked.
 * @return {boolean} - Returns `true` if the input is an object, `false` otherwise.
 */
const u_isObject = (o) => {
    return (
        typeof o === 'object' &&
        o !== null &&
        o.constructor &&
        Object.prototype.toString.call(o).slice(8, -1) === 'Object'
    );
};

/**
 * Parses a value and returns a boolean representation of the value.
 *
 * @param {string|boolean} str - The value to parse.
 * @return {boolean} - The parsed boolean value.
 */
const u_parseBool = (str) =>  {
    // console.log(typeof str);
    // strict: JSON.parse(str)

    if(str == null)
        return false;

    if (typeof str === 'boolean')
    {
        return (str === true);
    }

    if(typeof str === 'string')
    {
        if(str == "")
            return false;

        str = str.replace(/^\s+|\s+$/g, '');
        if(str.toLowerCase() == 'true' || str.toLowerCase() == 'yes')
            return true;

        str = str.replace(/,/g, '.');
        str = str.replace(/^\s*\-\s*/g, '-');
    }

    // var isNum = string.match(/^[0-9]+$/) != null;
    // var isNum = /^\d+$/.test(str);
    if(!isNaN(str))
        return (parseFloat(str) != 0);

    return false;
};

export {
    u_isInteger,
    u_isObject,
    u_parseBool,
}
