/**
 * Extends the destination object with properties from the source object.
 * Nested objects are recursively merged.
 *
 * @param {Object} destination - The object to be extended.
 * @param {Object} source      - The object with properties to extend from.
 * @return {Object} - The extended object.
 */
const u_extendObject = (destination, source) => {
    for (const property in source) {
        if (source[property] && source[property].constructor &&
            source[property].constructor === Object) {
            destination[property] = destination[property] || {};
            u_extendObject(destination[property], source[property]);
        } else {
            destination[property] = source[property];
        }
    }
    return destination;
};

/**
 * Extends an options object with default values.
 *
 * @param {Object} defaults - The default options object.
 * @param {Object} options  - The options to extend with.
 * @return {Object} - The extended options object.
 */
const u_extend = (defaults, options) => {
    const extendedOptions = {};
    for (const key in defaults) {
        extendedOptions[key] = options[key] || defaults[key];
    }
    return extendedOptions;
};

/**
 * Merges two objects deeply.
 *
 * @param {Object} target - The target object to merge into.
 * @param {Object} source - The source object to merge from.
 * @return {Object} - The merged object.
 */
const u_mergeDeep = (target, source) => {
    const isObject = (obj) => obj && typeof obj === 'object';

    if (!isObject(target) || !isObject(source)) {
        return source;
    }

    Object.keys(source).forEach(key => {
        const targetValue = target[key];
        const sourceValue = source[key];

        if (Array.isArray(targetValue) && Array.isArray(sourceValue)) {
            target[key] = targetValue.concat(sourceValue);
        } else if (isObject(targetValue) && isObject(sourceValue)) {
            target[key] = u_mergeDeep(Object.assign({}, targetValue), sourceValue);
        } else {
            target[key] = sourceValue;
        }
    });

    return target;
};

export {
    u_extend,
    u_extendObject,
    u_mergeDeep,
};
