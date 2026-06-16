/*
 * @title DS Accordions
 * @description Application entry point
 */

import { callAccordions } from './function-calls/dst_AccordionsInit';
import { callTabAccordionsMobile } from './function-calls/dst_TabsToAccordionMobile';

document.addEventListener('DOMContentLoaded', () => {
    callAccordions();
    callTabAccordionsMobile();
});

window.addEventListener('load', () => {

});
