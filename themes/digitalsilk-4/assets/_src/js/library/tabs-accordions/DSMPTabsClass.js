/**
 * The DSMPTabsClass represents a tabbed interface with swipe functionality.
 * @class
 */
class DSMPTabsClass {

    constructor() {
        this.eventsListeners = {};
        this.currentIndex = 0;
        this.activeNav = null;
        this.activePanel = null;

        this.breakpoints = {
            tablet: 768,
            desktop: 1112,
            'desktop-l': 1440,
        };

        this.breakpoint = false;
    }

    bindFunctions() {
        this.tabNavClick = this.tabNavClick.bind(this);
        this.mediaMatches = this.mediaMatches.bind(this);
        this.onSwipeStart = this.onSwipeStart.bind(this);
        this.onSwipeEnd = this.onSwipeEnd.bind(this);

        if (this.config.breakpoints !== 'all') {
            this.mql.addEventListener('change', this.mediaMatches);
        }
    }

    mediaMatches(e) {
        this.mediaMatch = e.matches;

        if (this.mediaMatch) {
            this.bindTabPanelEvent();
        } else {
            this.unbindTabPanelEvent();
        }
    }

    bindTabNavEvent() {
        const self = this;
        const elem = self.items;

        elem.forEach((tab) => {
            tab.addEventListener('click', self.tabNavClick, { passive: true });
        });
    }

    bindTabPanelEvent() {
        const self = this;
        const { panels } = self;

        if (self.mediaMatch) {
            panels.forEach((panel) => {
                panel.addEventListener('mousedown', self.onSwipeStart);
                panel.addEventListener('touchstart', self.onSwipeStart);
                panel.addEventListener('mouseup', self.onSwipeEnd);
                panel.addEventListener('touchend', self.onSwipeEnd);
            });
        }
    }

    unbindTabPanelEvent() {
        const self = this;
        const { panels } = self;

        panels.forEach((panel) => {
            panel.removeEventListener('mousedown', self.onSwipeStart);
            panel.removeEventListener('touchstart', self.onSwipeStart);
            panel.removeEventListener('mouseup', self.onSwipeEnd);
            panel.removeEventListener('touchend', self.onSwipeEnd);
        });
    }

    unbindTabNavEvent() {
        const self = this;
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

        if (this.config.breakpoints !== 'all') {
            self.mql.removeEventListener('change', self.mediaMatches);
        }
    }

    tabNavClick(ev) {
        const self = this;
        const currentTab = ev.currentTarget;
        self.activeNav = ev.currentTarget;
        const currentSelector = currentTab.closest(self.config.wrapper);
        const elem = currentSelector.querySelectorAll(self.config.selectors.nav);
        const currentTabID = self.getNavTabID(currentTab);

        self.clearActiveClass(elem, 'nav');
        self.setActiveClass(currentTab, 'nav');
        self.tabPanelChange(currentTabID);
    }

    tabPanelChange(index) {
        const self = this;

        if (typeof index === 'undefined') {
            return;
        }
        const currentPanelID = `${self.config.data}-${index}`;
        const currentPanel = document.querySelector(`#${currentPanelID}`);
        self.activePanel = currentPanel;
        const currentPanelHolder = currentPanel.closest(self.config.wrapper);
        const elem = currentPanelHolder.querySelectorAll(self.config.selectors.panel);

        if (typeof currentPanel === 'undefined') {
            return;
        }

        self.clearActiveClass(elem, 'panel');
        self.setActiveClass(currentPanel, 'panel');
        self.currentIndex = index;
        self.emit('tabsChange');
    }

    getNavTabID(index) {
        const self = this;
        const dataID = index.getAttribute(self.config.data);
        return dataID;
    }

    clearActiveClass(elem, section) {
        const self = this;
        elem.forEach((tab) => {
            tab.classList.remove(self.config.classes.active);

            if (section === 'panel') {
                tab.setAttribute('aria-hidden', true);
            }
            if (section === 'nav') {
                tab.setAttribute('aria-selected', false);
            }
        });
    }

    setActiveClass(elem, section) {
        const self = this;
        elem.classList.add(self.config.classes.active);
        if (section === 'panel') {
            elem.setAttribute('aria-hidden', false);
        }
        if (section === 'nav') {
            elem.setAttribute('aria-selected', true);
        }
    }

    changeActiveTab(i = 0) {
        const self = this;
        const elems = self.items;
        const currentTab = elems[i];
        const currentSelector = currentTab.closest(self.config.wrapper);
        const elem = currentSelector.querySelectorAll(self.config.selectors.nav);
        const currentTabID = self.getNavTabID(currentTab);

        self.activeNav = currentTab;
        self.clearActiveClass(elem, 'nav');
        self.setActiveClass(currentTab, 'nav');
        self.tabPanelChange(currentTabID);
    }

    on(events, callback) {
        const self = this;
        if (typeof callback !== 'function') return;

        events.split(' ').forEach((event, i) => {
            if (!self.eventsListeners[event]) self.eventsListeners[event] = [];
            self.eventsListeners[event].push(callback);
        });
    }

    off(events, handler) {
        const self = this;
        if (!self.eventsListeners) return;
        events.split(' ').forEach((event) => {
            if (typeof handler === 'undefined') {
                self.eventsListeners[event] = [];
            } else if (self.eventsListeners[event]) {
                self.eventsListeners[event].forEach((eventHandler, index) => {
                    if (eventHandler === handler) {
                        self.eventsListeners[event].splice(index, 1);
                    }
                });
            }
        });
    }

    emit(...args) {
        const self = this;

        if (!self.eventsListeners) return self;
        let events;
        let data;
        let context;

        if (typeof args[0] === 'string' || Array.isArray(args[0])) {
            events = args[0];
            data = args.slice(1, args.length);
            context = self;
        } else {
            events = args[0].events;
            data = args[0].data;
            context = args[0].context || self;
        }

        // console.log(events, data, context);
        data.unshift(context);
        const eventsArray = Array.isArray(events) ? events : events.split(' ');

        eventsArray.forEach((event) => {
            if (self.eventsListeners && self.eventsListeners[event]) {
                self.eventsListeners[event].forEach((eventHandler) => {
                    eventHandler.apply(context, data);
                });
            }
        });
    }

    onSwipeStart(e) {
        const self = this;
        e.stopPropagation();
        self.swipeStart = e.pageX || e.targetTouches[0].pageX;
    }

    onSwipeEnd(e) {
        const self = this;
        e.stopPropagation();
        const pageX = e.pageX || e.changedTouches[0].pageX;
        let offset;

        if (self.swipeStart) {
            offset = self.swipeStart - pageX;

            if (Math.abs(offset) > 30) {
                if (offset > 30) {
                    self.nextTab();
                }

                if (offset < -30) {
                    self.prevTab();
                }

            }

            self.swipeStart = null;
        }
    }

    nextTab() {
        const self = this;
        const { items } = self;
        const currentItem = self.currentIndex;
        const numberOfElem = self.items.length;
        let foundIndex = 0;
        let nextElem;

        items.forEach((item, i) => {
            const itemID = self.getNavTabID(item);
            if (itemID === currentItem) {
                foundIndex = i;
            }
        });

        if (foundIndex < numberOfElem - 1) {
            self.changeActiveTab(foundIndex + 1);
        }

        // foundIndex === numberOfElem - 1 ? nextElem = 0 : nextElem = foundIndex + 1;
        // self.changeActiveTab(nextElem);
    }

    prevTab() {
        const self = this;
        const { items } = self;
        const currentItem = self.currentIndex;
        const numberOfElem = self.items.length;
        let foundIndex = 0;
        let prevElem;

        items.forEach((item, i) => {
            const itemID = self.getNavTabID(item);
            if (itemID === currentItem) {
                foundIndex = i;
            }
        });

        if (foundIndex > 0) {
            self.changeActiveTab(foundIndex - 1);
        }

        // foundIndex === 0 ? prevElem = numberOfElem - 1 : prevElem = foundIndex - 1;
        // self.changeActiveTab(prevElem);
    }

}

export default DSMPTabsClass;
