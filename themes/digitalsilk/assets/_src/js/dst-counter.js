/*
 * @title Counter Plugin Init
 * @description Application entry point
 */

import PureCounter from './library/counters/purecounter';

document.addEventListener('DOMContentLoaded', () => {
    // eslint-disable-next-line no-new
    new PureCounter({
        selector: '.c-counter__number',
    });
});
