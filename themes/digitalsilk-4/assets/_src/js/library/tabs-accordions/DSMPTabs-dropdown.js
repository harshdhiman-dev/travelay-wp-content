import DSMPTabsClass from './DSMPTabsClass';
import { u_extendObject } from '../../utils/u_object_extend';

/**
 * DSMPTabsDropdown class extends DSMPTabsClass.
 * Represents a dropdown component for selecting different tabs in a responsive tab system.
 */
class DSMPTabsDropdown extends DSMPTabsClass {

    constructor(options) {
        super();
        this.defaults = {
            wrapper: '.js-tabsDrop-wrapper',
            selectors: {
                dropdown: '.js-tabs-dropdown',
                panel: '.js-tabs-panel',
            },
            classes: {
                active: 'is-active',
            },
            data: 'data-tab',
            breakpoints: 'tablet', // tablet, desktop, desktop-l, all,  leave empty for disabled
        };

        this.config = u_extendObject(this.defaults, options);

        this.selectorDropdown = `${this.config.wrapper} ${this.config.selectors.dropdown}`;
        this.selectorPanels = `${this.config.wrapper} ${this.config.selectors.panel}`;

        this.itemsDropdown = document.querySelectorAll(this.selectorDropdown);
        this.panels = document.querySelectorAll(this.selectorPanels);

        if (this.config.breakpoints !== 'all') {
            this.mql = window.matchMedia(`(max-width: ${this.breakpoints[this.config.breakpoints]}px)`);
            this.mediaMatch = this.mql.matches;
        } else {
            this.mediaMatch = true;
        }

        if (!this.itemsDropdown.length) return;

        this.init();
    }

    init() {
        this.bindFunctions();
        this.bindTabsDropdownEvent();
        super.bindTabPanelEvent();
    }

    bindFunctions() {
        this.tabDropdownChange = this.tabDropdownChange.bind(this);

        super.tabNavClick = super.tabNavClick.bind(this);
        super.mediaMatches = super.mediaMatches.bind(this);
        super.onSwipeStart = super.onSwipeStart.bind(this);
        super.onSwipeEnd = super.onSwipeEnd.bind(this);

        if (this.config.breakpoints !== 'all') {
            this.mql.addEventListener('change', super.mediaMatches);
        }
    }

    bindTabsDropdownEvent() {
        const self = this;
        const dropdowns = self.itemsDropdown;

        dropdowns.forEach((dropdown) => {
            dropdown.addEventListener('change', self.tabDropdownChange);
        });
    }

    tabDropdownChange(ev) {
        const currDropdown = ev.currentTarget;
        const currentTabID = currDropdown.value;
        const currentIndex = currDropdown.options.selectedIndex;

        for (let i = 0; i < currDropdown.options.length; i += 1) {
            currDropdown.options[i].removeAttribute('selected');
        }
        currDropdown.options[currentIndex].setAttribute('selected', 'selected');

        super.tabPanelChange(currentTabID);
    }

    unbindTabsDropdownEvent() {
        const self = this;
        const dropdowns = self.itemsDropdown;

        dropdowns.forEach((dropdown) => {
            dropdown.removeEventListener('change', self.tabDropdownChange);
        });
    }

}

export default DSMPTabsDropdown;
