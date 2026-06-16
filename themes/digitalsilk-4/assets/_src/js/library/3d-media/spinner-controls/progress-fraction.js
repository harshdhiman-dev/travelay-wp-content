/**
 * Image Spinner Options - Progress - Fraction
 */

const isFractionOn = (elem, options) => {
    if (!elem) return options;

    let isFraction = elem.getAttribute('data-ctrl-progress-fraction');

    if (isFraction === 'true') {
        options.plugins.push('dsProgressFraction');
    }

    return options;
}


export {
    isFractionOn
}