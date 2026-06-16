import DSMPTabsClass from './DSMPTabsClass';
import { u_extendObject } from '../../utils/u_object_extend';

/**
 * DSMPTabsTab
 *
 * This class represents a single tab in the DSMPTabsClass.
 * It extends the DSMPTabsClass and provides additional functionality for managing the tab.
 *
 * @extends DSMPTabsClass
 */
class DSMPTabsTab extends DSMPTabsClass {

    constructor(options) {
        super();
        this.defaults = {
            wrapper: '.js-tabs-wrapper',
            selectors: {
                nav: '.js-tabs-nav-item',
                panel: '.js-tabs-panel',
            },
            classes: {
                active: 'is-active',
            },
            data: 'data-tab',
            breakpoints: 'tablet', // tablet, desktop, desktop-l, all,  leave empty for disabled
        };

        this.config = u_extendObject(this.defaults, options);

        this.selector = `${this.config.wrapper} ${this.config.selectors.nav}`;
        this.selectorPanels = `${this.config.wrapper} ${this.config.selectors.panel}`;

        this.items = document.querySelectorAll(this.selector);
        this.panels = document.querySelectorAll(this.selectorPanels);

        if (this.config.breakpoints !== 'all') {
            this.mql = window.matchMedia(`(max-width: ${this.breakpoints[this.config.breakpoints]}px)`);
            this.mediaMatch = this.mql.matches;
        } else {
            this.mediaMatch = true;
        }

        if (!this.items.length) return;

        this.init();
    }

    init() {
        if (this.items.length > 0) {
            this.currentIndex = super.getNavTabID(this.items[0]);
        }
        super.bindFunctions();
        super.bindTabNavEvent();
        super.bindTabPanelEvent();
    }

}

export default DSMPTabsTab;
