/**
 * DSMP Accordions Light version
 * Just a basic functionality for accordions, add/remove is-active class on accordion item, and supports ARIA
 *
 * @param selector - use classname or ID
 * @param options  - pass options to change selectors
 *            - item : main holder of trigger and content
 *            - trigger : class that triggers event (button, header)
 *            - content : class that changes visibility
 * supports data attributes: data-close, data-expand,
 *            - data-close : if you want to be able to self close element, its automatically true if expand option enabled
 *            - data-expand : if you want to have multiple accordion items opened at same time, set to true
 *
 */

import { u_extendObject } from '../../utils/u_object_extend';

/**
 * Represents a Lightweight Accordion Component.
 * @class
 */
class DSMPAccordionsLight {

    constructor(selector, options) {
        // default wrapper value
        this.wrapper = '.js-a-light';

        // default options
        this.defaults = {
            selectors: {
                item: '.js-a-item',
                trigger: '.js-a-button',
                content: '.js-a-content',
            },
            classes: {
                active: 'is-active',
                focus: 'focus',
            },
            attr: {
                close: 'data-close',
                open: 'data-expand',
            },
        };

        // aria names used to generate id's and controls
        this.aria = {
            button: 'header',
            content: 'content',
        };

        // options for close and expand
        this.opt = {
            close: false,
            expand: false,
        };

        // merge config
        this.config = u_extendObject(this.defaults, options);

        // check if we changed selector
        if (typeof selector !== 'undefined') {
            this.wrapper = selector;
        }

        // get name to use for aria id's and controls
        this.getAriaName();

        this.selector = document.querySelector(this.wrapper);

        // check if we have valid selector, other way do nothing
        if (!this.selector) return;

        // stash triggers, and items
        this.trigger = this.selector.querySelectorAll(this.config.selectors.trigger);
        this.items = this.selector.querySelectorAll(this.config.selectors.item);

        // array for stashing reference to binded events
        this.handlers = [];

        this.init();

    }

    init() {
        this.parseOptions();
        this.addAria();
        this.bindEvents();
    }

    // bind clicks to trigger elements
    bindEvents() {
        let self = this;
        let elem = self.trigger;

        self.addListenerFocus = self.addListenerFocus.bind(self);
        self.addListenerBlur = self.addListenerBlur.bind(self);
        self.addKeyListener = self.addKeyListener.bind(self);

        elem.forEach((acc, i) => {
            let handlerFunc = self.accordionNavClick.bind(self, i);

            self.handlers.push(handlerFunc);
            acc.addEventListener('click', handlerFunc, {passive: true});
            acc.addEventListener('focus', self.addListenerFocus, {passive: true});
            acc.addEventListener('blur', self.addListenerBlur, {passive: true});

        });

        let accordion = self.selector;
        accordion.addEventListener('keydown', self.addKeyListener, {passive: true});
    }

    addListenerFocus(ev) {
        let self = this;
        let elem = ev.target;

        elem.classList.add(self.config.classes.focus);
    }

    addListenerBlur(ev) {
        let self = this;
        let elem = ev.target;
        elem.classList.remove(self.config.classes.focus);
    }

    addKeyListener(ev) {
        let self = this;
        let elem = ev.target;
        let key = ev.which.toString();

        let triggers = [...self.trigger];

        let triggerClass = self.config.selectors.trigger.slice(1);

        // 33 = Page Up, 34 = Page Down
        let ctrlModifier = (ev.ctrlKey && key.match(/33|34/));

        if (elem.classList.contains(triggerClass)) {
            // Up/ Down arrow and Control + Page Up/ Page Down keyboard operations
            // 38 = Up, 40 = Down
            if (key.match(/38|40/) || ctrlModifier) {
                let index = triggers.indexOf(elem);
                let direction = (key.match(/34|40/)) ? 1 : -1;
                let length = triggers.length;
                let newIndex = (index + length + direction) % length;
                triggers[newIndex].focus();
            } else if (key.match(/35|36/)) {
                // 35 = End, 36 = Home keyboard operations
                switch (key) {

                    // Go to first accordion
                    case '36':
                        triggers[0].focus();
                        break;
                    // Go to last accordion
                    case '35':
                        triggers[triggers.length - 1].focus();
                        break;
                }
            }
        }
    }

    accordionNavClick(i, ev) {
        let self = this;
        let currentItemClicked = ev.currentTarget;
        let elems = self.items;

        let currentItem = currentItemClicked.closest(self.config.selectors.item);
        let currentItemContent = currentItem.querySelector(self.config.selectors.content);
        let expanded = currentItemClicked.getAttribute('aria-expanded') === 'true' || false;

        if (currentItem.classList.contains(self.config.classes.active)) {
            if (self.opt.close) {
                currentItem.classList.remove(self.config.classes.active);
                currentItemClicked.setAttribute('aria-expanded', !expanded);
                currentItemContent.setAttribute('aria-hidden', expanded);
            }
        } else {
            if (!self.opt.expand) {
                elems.forEach((item) => {
                    item.classList.remove(self.config.classes.active);
                });
                currentItem.classList.add(self.config.classes.active);
                currentItemClicked.setAttribute('aria-expanded', !expanded);
                currentItemContent.setAttribute('aria-hidden', expanded);
            } else {
                currentItem.classList.add(self.config.classes.active);
                currentItemClicked.setAttribute('aria-expanded', !expanded);
                currentItemContent.setAttribute('aria-hidden', expanded);
            }
        }
    }

    parseOptions() {
        let self = this;

        let isSelfClose = Boolean(self.selector.getAttribute(self.config.attr.close));
        isSelfClose ? self.opt.close = true : false;

        // if leave open is true, self close should automatically be true, otherwise we wont be able to close on self click
        let isLeaveOpen = Boolean(self.selector.getAttribute(self.config.attr.open));
        if (isLeaveOpen) {
            self.opt.expand = true;
            self.opt.close = true;
        } else {
            self.opt.expand = false;
        }
    }

    // call this function if we want to unbind clicks and remove all ARIA attributes
    unbindEvents() {
        let self = this;
        let elem = self.trigger;

        elem.forEach((acc, i) => {
            let elemParent = acc.closest(self.config.selectors.item);
            let elemContent = elemParent.querySelector(self.config.selectors.content);

            let control, header;
            if (self.aria.name) {
                control = `${self.aria.name}-${self.aria.content}-${i}`;
                header = `${self.aria.name}-${self.aria.button}-${i}`;
            }

            acc.removeAttribute('aria-expanded', '');
            elemContent.removeAttribute('aria-hidden', '');

            if (self.aria.name) {
                acc.removeAttribute('aria-controls', '');
                acc.removeAttribute('id', '');
                elemContent.removeAttribute('id', '');
                elemContent.removeAttribute('aria-labelledby', '');
            }
            elemContent.removeAttribute('role', '');
            acc.removeEventListener('click', self.handlers[i]);
            acc.removeEventListener('focus', self.addListenerFocus);
            acc.removeEventListener('blur', self.addListenerBlur);
        });

        let accordion = self.selector;
        accordion.removeEventListener('keydown', self.addKeyListener);
    }

    // small function to check for valid ID of wrapper
    isValidId(s) {
        return /^[^\s]+$/.test(s);
    }

    getAriaName() {
        let ariaName = this.wrapper.slice(1);
        if (this.isValidId(ariaName)) {
            this.aria.name = ariaName;
        } else {
            this.aria.name = false;
        }
    }

    addAria() {
        let self = this;
        let elem = self.trigger;

        elem.forEach((acc, i) => {
            let elemParent = acc.closest(self.config.selectors.item);
            let elemContent = elemParent.querySelector(self.config.selectors.content);

            let control, header;
            if (self.aria.name) {
                control = `${self.aria.name}-${self.aria.content}-${i}`;
                header = `${self.aria.name}-${self.aria.button}-${i}`;
            }

            if (elemParent.classList.contains(self.config.classes.active)) {
                acc.setAttribute('aria-expanded', true);
                elemContent.setAttribute('aria-hidden', false);
            } else {
                acc.setAttribute('aria-expanded', false);
                elemContent.setAttribute('aria-hidden', true);
            }

            if (self.aria.name) {
                acc.setAttribute('aria-controls', control);
                acc.setAttribute('id', header);
                elemContent.setAttribute('id', control);
                elemContent.setAttribute('aria-labelledby', header);
            }
            elemContent.setAttribute('role', 'region');
        });
    }

}

export default DSMPAccordionsLight;
