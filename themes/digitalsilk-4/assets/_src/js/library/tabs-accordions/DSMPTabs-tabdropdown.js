import {u_extendObject} from '../../utils/u_object_extend';
import DSMPTabsClass from './DSMPTabsClass';

/**
 * @class
 * @classdesc Represents a dropdown tab in a DSMPTabsClass instance.
 * @extends DSMPTabsClass
 */
class DSMPTabsTabDropdown extends DSMPTabsClass {

    constructor(options) {
        super();
        this.defaults = {
            wrapper: '.js-tabsTabDrop-wrapper',
            selectors: {
                nav: '.js-tabs-nav-item',
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

        this.selectorTabs = `${this.config.wrapper} ${this.config.selectors.nav}`;
        this.selectorDropdown = `${this.config.wrapper} ${this.config.selectors.dropdown}`;
        this.selectorPanels = `${this.config.wrapper} ${this.config.selectors.panel}`;

        this.items = document.querySelectorAll(this.selectorTabs);
        this.itemsDropdown = document.querySelectorAll(this.selectorDropdown);
        this.panels = document.querySelectorAll(this.selectorPanels);

        if (this.config.breakpoints !== 'all') {
            this.mql = window.matchMedia(`(max-width: ${this.breakpoints[this.config.breakpoints]}px)`);
            this.mediaMatch = this.mql.matches;
        } else {
            this.mediaMatch = true;
        }

        if (!this.items.length) return;

        this.initTabsDropdown();
    }

    initTabsDropdown() {
        if (this.items.length > 0) {
            this.currentIndex = super.getNavTabID(this.items[0]);
            this.activeNav = this.items[0];
            this.activePanel = this.panels[0];
        }
        this.bindFunctions();
        this.bindTabNavEv();
        this.bindTabsDropdownEvent();
        super.bindTabPanelEvent();
    }

    bindFunctions() {
        this.tabDropdownChange = this.tabDropdownChange.bind(this);
        this.tabNavClick = this.tabNavClick.bind(this);
        this.mediaMatches = this.mediaMatches.bind(this);
        super.onSwipeStart = super.onSwipeStart.bind(this);
        super.onSwipeEnd = super.onSwipeEnd.bind(this);

        if (this.config.breakpoints !== 'all') {
            this.mql.addEventListener('change', this.mediaMatches);
        }
    }

    bindTabsDropdownEvent() {
        const self = this;
        const dropdowns = self.itemsDropdown;

        dropdowns.forEach((dropdown) => {
            dropdown.addEventListener('change', self.tabDropdownChange);
        });
    }

    mediaMatches(e) {
        this.mediaMatch = e.matches;

        if (this.mediaMatch) {
            super.bindTabPanelEvent();
        } else {
            super.unbindTabPanelEvent();
        }
    }

    bindTabNavEv() {
        const self = this;
        const elem = self.items;

        elem.forEach((tab) => {
            tab.addEventListener('click', self.tabNavClick, { passive: true });
        });
    }

    tabNavClick(ev) {
        const self = this;
        const currentTab = ev.currentTarget;
        const currentTabID = super.getNavTabID(currentTab);
        const currentSelector = currentTab.closest(self.config.wrapper);
        const currentDropdown = currentSelector.querySelector(self.config.selectors.dropdown);

        let newIndex;
        for (let i = 0; i < currentDropdown.options.length; i += 1) {
            if (currentDropdown.options[i].value === currentTabID) {
                newIndex = i;
            }
        }

        self.updateTabNav(currentTab);
        self.updateDropdown(currentDropdown, newIndex);
        super.tabPanelChange(currentTabID);
    }

    tabDropdownChange(ev) {
        const self = this;
        const currDropdown = ev.currentTarget;
        const currentIndex = currDropdown.options.selectedIndex;

        const currentTabID = currDropdown.value;
        const currentNavItem = document.querySelector(`[${self.config.data}='${currentTabID}']`);

        self.updateDropdown(currDropdown, currentIndex);
        self.updateTabNav(currentNavItem);
        super.tabPanelChange(currentTabID);
    }

    updateDropdown(currentDrop, newDropIndex) {
        const self = this;
        const currDropdown = currentDrop;
        const currentIndex = newDropIndex;

        for (let i = 0; i < currDropdown.options.length; i += 1) {
            currDropdown.options[i].removeAttribute('selected');
        }
        currDropdown.options[currentIndex].setAttribute('selected', 'selected');
        currDropdown.options.selectedIndex = currentIndex;
    }

    updateTabNav(currTab) {
        const self = this;
        const currentTab = currTab;
        self.activeNav = currTab;
        const currentSelector = currentTab.closest(self.config.wrapper);
        const elem = currentSelector.querySelectorAll(self.config.selectors.nav);

        super.clearActiveClass(elem, 'nav');
        super.setActiveClass(currentTab, 'nav');
    }

    unbindTabsDropEvents() {
        const self = this;
        const dropdowns = self.itemsDropdown;
        const elem = self.items;
        const { panels } = self;

        elem.forEach((tab) => {
            tab.removeEventListener('click', self.tabNavClick);
        });

        panels.forEach((panel) => {
            panel.removeEventListener('mousedown', self.onSwipeStart);
            panel.removeEventListener('touchstart', self.onSwipeStart);
            panel.removeEventListener('mouseup', self.onSwipeEnd);
            panel.removeEventListener('touchend', self.onSwipeEnd);
        });

        dropdowns.forEach((dropdown) => {
            dropdown.removeEventListener('change', self.tabDropdownChange);
        });
    }

    nextTab() {
        const self = this;
        const { items } = self;
        const currentItem = self.currentIndex;
        const numberOfElem = self.items.length;
        let foundIndex = 0;
        let nextElem;

        const currentTab = document.querySelector(`[${self.config.data}='${currentItem}']`);
        const currentSelector = currentTab.closest(self.config.wrapper);
        const currentDropdown = currentSelector.querySelector(self.config.selectors.dropdown);

        items.forEach((item, i) => {
            const itemID = self.getNavTabID(item);
            if (itemID === currentItem) {
                foundIndex = i;
            }
        });

        if (foundIndex < numberOfElem - 1) {
            self.changeActiveTab(foundIndex + 1);
            self.updateDropdown(currentDropdown, foundIndex + 1);
        }
    }

    prevTab() {
        const self = this;
        const { items } = self;
        const currentItem = self.currentIndex;
        const numberOfElem = self.items.length;
        let foundIndex = 0;
        let prevElem;

        const currentTab = document.querySelector(`[${self.config.data}='${currentItem}']`);
        const currentSelector = currentTab.closest(self.config.wrapper);
        const currentDropdown = currentSelector.querySelector(self.config.selectors.dropdown);

        items.forEach((item, i) => {
            const itemID = self.getNavTabID(item);
            if (itemID === currentItem) {
                foundIndex = i;
            }
        });

        if (foundIndex > 0) {
            self.changeActiveTab(foundIndex - 1);
            self.updateDropdown(currentDropdown, foundIndex - 1);
        }
    }

}

export default DSMPTabsTabDropdown;
