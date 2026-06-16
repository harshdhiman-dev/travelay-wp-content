/*
 * @title DS Tabs
 * @description Application entry point
 */

import DSMPTabsTab from './library/tabs-accordions/DSMPTabs-tab';
import DSMPTabsDropdown from './library/tabs-accordions/DSMPTabs-dropdown';
import DSMPTabsTabDropdown from './library/tabs-accordions/DSMPTabs-tabdropdown';
import { callTabAccordionsMobile } from './function-calls/dst_TabsToAccordionMobile';

document.addEventListener('DOMContentLoaded', () => {
    // eslint-disable-next-line no-new
    new DSMPTabsTab();
    // eslint-disable-next-line no-new
    new DSMPTabsDropdown();
    // eslint-disable-next-line no-new
    new DSMPTabsTabDropdown();

    callTabAccordionsMobile();
});

window.addEventListener('load', () => {

});
