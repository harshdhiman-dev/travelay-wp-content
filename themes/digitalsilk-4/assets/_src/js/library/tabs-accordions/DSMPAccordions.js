import { u_extendObject } from '../../utils/u_object_extend';
import { u_slideDown, u_slideUp } from '../../utils/u_slide-up-down';
import { u_fadeIn, u_fadeOut } from '../../utils/u_fade-in-out';
import { u_parseBool } from '../../utils/u_types';
import { scrollToUtil } from '../animations/scroll-to';
import { easeInQuad } from '../animations/easings-es6';

/**
 * Represents an accordion component.
 * @class
 */
class DSMPAccordions {
    constructor(selector, options) {
        // default wrapper value
        this.wrapper = '.js-acc-wrapper';

        this.defaults = {
            selectors: {
                item: '.js-acc-item',
                trigger: '.js-acc-button',
                content: '.js-acc-content',
            },
            gallery: {
                container: '.js-acc-gallery',
                item: '.js-acc-media',
            },
            classes: {
                active: 'is-active',
                focus: 'focus',
                display: 'block',
                animating: 'is-sliding',
            },
            animation: {
                content: true, // true: use js , false: use css
                gallery: false, // true: use js , false: use css
            },
            attr: {
                close: 'data-close',
                open: 'data-expand',
                gallery: 'data-gallery',
                startClosed: 'data-start-closed',
                animationContent: 'data-animation',
                animationGallery: 'data-gallery-animation',
                display: 'data-acc-display',
                scrollToView: 'data-scroll-to-view',
            },
            opt: {
                close: false,
                expand: false,
                hasGallery: false,
                startClosed: false,
                scrollToView: false,
            },
            aria: {
                button: 'header',
                content: 'content',
            },
        };

        // breakpoints: {
        //     tablet: 1113,
        //         mobile: 769,
        // },

        this.config = u_extendObject(this.defaults, options);
        // check if we changed selector
        if (typeof selector !== 'undefined') {
            this.wrapper = selector;
        }

        // get name to use for aria id's and controls
        this.getAriaName();

        this.selector = document.querySelector(this.wrapper);

        this.eventsListeners = {};

        this.parseOptions();
        this.shouldScroll = false;

        this.mql = window.matchMedia('(max-width: 1113px)');
        if (this.config.opt.scrollToView) {
            this.shouldScroll = this.mql.matches;

            this.mql.addEventListener('change', (e) => {
                this.shouldScroll = e.matches;
            });
        }

        this.trigger = this.selector.querySelectorAll(this.config.selectors.trigger);
        this.items = this.selector.querySelectorAll(this.config.selectors.item);

        if (this.config.opt.hasGallery) {
            this.galleryItems = this.selector.querySelectorAll(this.config.gallery.item);
        }

        // array for stashing reference to binded events
        this.handlers = [];

        this.previousIndex = 0;
        this.currentIndex = 0;

        this.init();
    }

    init() {
        this.addAria();
        this.prepareForAnimation();
        this.accordionBindEvents();
    }

    reInit() {
        this.accordionUnbindEvents();
        this.trigger = this.selector.querySelectorAll(this.config.selectors.trigger);
        this.items = this.selector.querySelectorAll(this.config.selectors.item);
        this.handlers = [];
        this.addAria();
        this.reInitAnimation();
        this.accordionBindEvents();
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

    accordionBindEvents() {
        let self = this;
        let elem = self.trigger;

        self.addListenerFocus = self.addListenerFocus.bind(self);
        self.addListenerBlur = self.addListenerBlur.bind(self);
        self.addKeyListener = self.addKeyListener.bind(self);

        self.on = self.on.bind(self);
        self.off = self.off.bind(self);
        self.emit = self.emit.bind(self);

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

    accordionUnbindEvents() {
        const self = this;
        const elem = self.trigger;

        elem.forEach((acc, i) => {
            let elemParent = acc.closest(self.config.selectors.item);
            let elemContent = elemParent.querySelector(self.config.selectors.content);

            let control, header;
            if (self.config.aria.name) {
                control = `${self.config.aria.name}-${self.config.aria.content}-${i}`;
                header = `${self.config.aria.name}-${self.config.aria.button}-${i}`;
            }

            acc.removeAttribute('aria-expanded', '');
            if (elemContent) {
                elemContent.removeAttribute('aria-hidden', '');
            }

            if (self.config.aria.name) {
                acc.removeAttribute('aria-controls', '');
                acc.removeAttribute('id', '');
                if (elemContent) {
                    elemContent.removeAttribute('id', '');
                    elemContent.removeAttribute('aria-labelledby', '');
                }
            }

            if (elemContent) {
                elemContent.removeAttribute('role', '');
            }

            acc.removeEventListener('click', self.handlers[i]);
            acc.removeEventListener('focus', self.addListenerFocus);
            acc.removeEventListener('blur', self.addListenerBlur);
        });

        let accordion = self.selector;
        accordion.removeEventListener('keydown', self.addKeyListener);
        self.removeStyles();
    }

    accordionNavClick(i, ev) {
        let self = this;

        let currentItemClicked = ev.currentTarget;

        self.accordionContentchange(i, currentItemClicked, ev);

    }

    accordionContentchange(i, elem, ev) {
        const self = this;

        const currentItemClicked = elem;
        const elems = self.items;

        const currentItem = currentItemClicked.closest(self.config.selectors.item);
        const currentItemContent = currentItem.querySelector(self.config.selectors.content);
        const expanded = currentItemClicked.getAttribute('aria-expanded') === 'true' || false;

        if (currentItem.classList.contains(self.config.classes.animating)) {
            return;
        }

        if (currentItem.classList.contains(self.config.classes.active)) {
            if (self.config.opt.close) {
                if (self.config.animation.content) {
                    currentItem.classList.add(self.config.classes.animating);
                    u_slideUp(currentItemContent, {
                        display: self.config.classes.display,
                        complete: () => {
                            currentItem.classList.remove(self.config.classes.animating);
                        },
                    });
                }
                currentItem.classList.remove(self.config.classes.active);
                currentItemClicked.setAttribute('aria-expanded', !expanded);
                currentItemContent.setAttribute('aria-hidden', expanded);
            }
        } else {
            if (!self.config.opt.expand) {
                elems.forEach((item) => {
                    const itemContent = item.querySelector(self.config.selectors.content);
                    const itemTrigger = item.querySelector(self.config.selectors.trigger);
                    if (self.config.animation.content && item.classList.contains(self.config.classes.active)) {
                        item.classList.add(self.config.classes.animating);
                        u_slideUp(itemContent, {
                            display: self.config.classes.display,
                            complete: () => {
                                item.classList.remove(self.config.classes.animating);
                            },
                        });
                    }
                    item.classList.remove(self.config.classes.active);
                    if (itemTrigger) {
                        itemTrigger.setAttribute('aria-expanded', expanded);
                    }
                    if (itemContent) {
                        itemContent.setAttribute('aria-hidden', !expanded);
                    }
                });

                if (self.config.animation.content) {
                    currentItem.classList.add(self.config.classes.animating);
                    u_slideDown(currentItemContent, {
                        display: self.config.classes.display,
                        complete: () => {
                            currentItem.classList.remove(self.config.classes.animating);
                        },
                    });
                }

                currentItem.classList.add(self.config.classes.active);
                currentItemClicked.setAttribute('aria-expanded', !expanded);
                currentItemContent.setAttribute('aria-hidden', expanded);
            } else {
                if (self.config.animation.content) {
                    currentItem.classList.add(self.config.classes.animating);
                    u_slideDown(currentItemContent, {
                        display: self.config.classes.display,
                        complete: () => {
                            currentItem.classList.remove(self.config.classes.animating);
                        },
                    });
                }
                currentItem.classList.add(self.config.classes.active);
                currentItemClicked.setAttribute('aria-expanded', !expanded);
                currentItemContent.setAttribute('aria-hidden', expanded);
            }

            if (self.config.opt.hasGallery) {
                self.accordionChangeGallery(i);
            }

            if (self.shouldScroll && self.currentIndex < i) {
                self.scrollToAccordion(i);
            }
        }
        this.previousIndex = this.currentIndex;
        this.currentIndex = i;

        self.emit('accordionChange', ev);
    }

    nextAccordion() {
        const self = this;
        let nextElem = self.currentIndex;
        const numberOfElem = self.items.length;

        nextElem === numberOfElem - 1 ? nextElem = 0 : nextElem += 1;
        const nextElemItem = self.items[nextElem];

        self.accordionContentchange(nextElem, nextElemItem, null);
    }

    prevAccordion() {
        const self = this;
        let prevElem = self.currentIndex;
        const numberOfElem = self.items.length;

        prevElem === 0 ? prevElem = numberOfElem - 1 : prevElem -= 1;
        const prevElemItem = self.items[prevElem];

        self.accordionContentchange(prevElem, prevElemItem, null);
    }

    accordionChangeGallery(i) {
        let self = this;
        let galleryItems = [...self.galleryItems];

        galleryItems.forEach((gallery) => {
            if (self.config.animation.gallery) {
                u_fadeOut(gallery, {
                    complete: () => {
                        gallery.classList.remove(self.config.classes.active);
                        let newItem = galleryItems[i];
                        u_fadeIn(newItem, {
                            duration: 50
                        });
                        newItem.classList.add(self.config.classes.active);
                    }
                })

            } else {
                gallery.classList.remove(self.config.classes.active);
            }
        });
        if (!self.config.animation.gallery) {
            galleryItems[i].classList.add(self.config.classes.active);
        }

    }

    prepareForAnimation() {
        /* check whether items contains is-active class, if its not start closed all,
        first item should have is-active class and its content should be set to
        display block / flex, otherwise, hide it
        */
        let self = this;
        let items = self.items;

        let index = 0;
        let activeFound = false;

        if (self.config.animation.content) {
            items.forEach((list, i) => {
                const itemContent = list.querySelector(self.config.selectors.content);

                if (list.classList.contains(self.config.classes.active)) {
                    if (!self.config.opt.startClosed) {
                        if (itemContent) {
                            itemContent.style.display = self.config.classes.display;
                        }
                    }
                    index = i;
                    activeFound = true;
                } else {
                    if (itemContent) {
                        itemContent.style.display = 'none';
                    }
                }
            });

            if (!activeFound && !self.config.opt.startClosed) {
                const item0Content = items[0].querySelector(self.config.selectors.content);
                items[0].classList.add(self.config.classes.active);
                item0Content.style.display = self.config.classes.display;
            }
        }
    }

    reInitAnimation() {
        let self = this;
        let items = self.items;

        if (self.config.animation.content) {
            items.forEach((list, i) => {
                let itemContent = list.querySelector(self.config.selectors.content);

                if (!list.classList.contains(self.config.classes.active)) {
                    itemContent.style.display = 'none';
                }
            })
        }
    }

    removeStyles() {
        let self = this;
        let items = self.items;

        if (self.config.animation.content) {

            items.forEach((list, i) => {
                let itemContent = list.querySelector(self.config.selectors.content);
                itemContent.style.display = '';
            })
        }
    }

    parseOptions() {
        const self = this;

        const isSelfClose = u_parseBool(self.selector.getAttribute(self.config.attr.close))
            || self.config.opt.close;
        if (isSelfClose) {
            isSelfClose ? self.config.opt.close = true : self.config.opt.close = false;
        }

        /**
         * if leave open is true, self close should automatically be true,
         * otherwise we wont be able to close on self click
         */

        const isLeaveOpen = u_parseBool(self.selector.getAttribute(self.config.attr.open))
            || self.config.opt.expand;

        if (isLeaveOpen) {
            self.config.opt.expand = true;
            self.config.opt.close = true;
        } else {
            self.config.opt.expand = false;
        }

        const isStartClosed = u_parseBool(self.selector.getAttribute(self.config.attr.startClosed))
            || self.config.opt.startClosed;

        if (isStartClosed) {
            self.config.opt.startClosed = true;
            self.config.opt.close = true;
        }

        const isGallery = u_parseBool(self.selector.getAttribute(self.config.attr.gallery))
            || self.config.opt.hasGallery;
        if (isGallery) {
            self.config.opt.hasGallery = true;

            // if we have gallery, self close and expand is by default off
            self.config.opt.expand = false;
            self.config.opt.close = false;
            self.config.opt.startClosed = false;
        }

        const animateContent = self.selector.getAttribute(self.config.attr.animationContent);

        if (animateContent) {
            animateContent === 'js' ? self.config.animation.content = true : self.config.animation.content = false;
        }

        const animateGallery = self.selector.getAttribute(self.config.attr.animationGallery);
        if (animateGallery) {
            animateGallery === 'js' ? self.config.animation.gallery = true : self.config.animation.gallery = false;
        }

        const display = self.selector.getAttribute(self.config.attr.display)
            || self.config.classes.display;
        self.config.classes.display = display === 'flex' ? 'flex' : 'block';

        const isScrollToView = u_parseBool(
            self.selector.getAttribute(self.config.attr.scrollToView),
        ) || self.config.opt.scrollToView;
        if (isScrollToView) {
            self.config.opt.scrollToView = true;
            self.config.opt.expand = false;
        }

        self.emit('optionsParsed');
    }

    // small function to check for valid ID of wrapper
    isValidId(s) {
        return /^[^\s]+$/.test(s);
    }

    getAriaName() {
        let ariaName = this.wrapper.slice(1);
        if (this.isValidId(ariaName)) {
            this.config.aria.name = ariaName;
        } else {
            this.config.aria.name = false;
        }
    }

    addAria() {
        const self = this;
        const elem = self.trigger;

        elem.forEach((acc, i) => {
            const elemParent = acc.closest(self.config.selectors.item);
            const elemContent = elemParent.querySelector(self.config.selectors.content);

            let control,
                header;
            if (self.config.aria.name) {
                control = `${self.config.aria.name}-${self.config.aria.content}-${i}`;
                header = `${self.config.aria.name}-${self.config.aria.button}-${i}`;
            }

            if (elemParent.classList.contains(self.config.classes.active)) {
                acc.setAttribute('aria-expanded', true);
                if (elemContent) {
                    elemContent.setAttribute('aria-hidden', false);
                }
            } else {
                acc.setAttribute('aria-expanded', false);
                if (elemContent) {
                    elemContent.setAttribute('aria-hidden', true);
                }
            }

            if (self.config.aria.name) {
                acc.setAttribute('aria-controls', control);
                acc.setAttribute('id', header);
                if (elemContent) {
                    elemContent.setAttribute('id', control);
                    elemContent.setAttribute('aria-labelledby', header);
                }
            }

            if (elemContent) {
                elemContent.setAttribute('role', 'region');
            }
        });
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

    scrollToAccordion(currentIndex) {
        const self = this;
        const elem = self.trigger[0];
        const scrollCurrentContent = elem.getBoundingClientRect();
        const elemHeight = scrollCurrentContent.height;
        const offset = elemHeight * currentIndex;
        const currentScrollPos = window.scrollY || document.documentElement.scrollTop;
        const scrollTo = scrollCurrentContent.top + currentScrollPos + offset - 80;

        scrollToUtil({
            to: scrollTo,
            duration: 400,
            easing: easeInQuad,
        });
    }

}

export default DSMPAccordions;
