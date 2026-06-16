import { u_extendObject } from '../../utils/u_object_extend';
import MenubarItem from './menubar-item';

/**
 * Class representing a Menubar in DSMP.
 * @class
 * @param {string} [selector='.nav-main'] - The selector for the menubar element.
 * @param {Object} [options]              - The optional configuration options for the menubar.
 * @throws {TypeError} If the menubar element is not a DOM Element.
 */
class DSMPMenubar {
    constructor(selector = '.nav-main', options) {
        // default wrapper value
        this.menu = selector;

        this.defaults = {
            selectors: {
                holder: '.nav-main__links',
                submenu: '.sub-menu',
                item: '.menu-item',
                content: '.js-acc-content',
            },
            classes: {
                active: 'is-active',
                plainItem: 'plain-menu-item',
                hasSubmenu: 'menu-item-has-children',
                focus: 'focus',
                display: 'block',
            },
            attr: {
                close: 'data-close',
                open: 'data-expand',
                gallery: 'data-gallery',
                startClosed: 'data-start-closed',
                animationContent: 'data-animation',
                animationGallery: 'data-gallery-animation',
            },
            opt: {
                close: false,
                expand: false,
                hasGallery: false,
                startClosed: false,
            },
        };

        // aria names used to generate id's and controls
        this.aria = {
            button: 'header',
            content: 'content',
        };

        this.config = u_extendObject(this.defaults, options);

        this.menubar = document.querySelector(this.menu);

        if (!this.menubar) {
            throw new TypeError(`Menubar ${this.menubar} is not a DOM Element.`);
        }
        this.wrapper = this.menubar.querySelector(this.config.selectors.holder);
        this.wrapper.setAttribute('role', 'menubar');

        this.isMenubar = true;
        this.menubarItems = [];
        this.firstChars = [];

        this.firstItem = null;
        this.lastItem = null;

        this.hasFocus = false;
        this.hasHover = false;

        // console.log(this.wrapper);

        // // get name to use for aria id's and controls
        // this.getAriaName();
        //
        // this.selector = document.querySelector(this.wrapper);
        //
        // this.parseOptions();
        //
        // this.trigger = this.selector.querySelectorAll(this.config.selectors.trigger);
        // this.items = this.selector.querySelectorAll(this.config.selectors.item);
        //
        // if(this.config.opt.hasGallery) {
        //     this.galleryItems = this.selector.querySelectorAll(this.config.gallery.item);
        // }
        //
        // // array for stashing reference to binded events
        // this.handlers = [];

        this.init();
        // console.log(this);
    }

    init() {
        let menubarItem;
        let menuElement;
        let textContent;
        // let numItems;

        let elem = this.wrapper.firstElementChild;
        // console.log('elem', elem);

        while (elem) {
            menuElement = elem.firstElementChild;
            // console.log(menuElement, elem.classList);

            if (elem && menuElement && (menuElement.tagName === 'A' || elem.classList.contains(this.config.classes.hasSubmenu))) {
                menubarItem = new MenubarItem(menuElement, this);
                menubarItem.init();
                textContent = menuElement.textContent.trim();
                this.menubarItems.push(textContent);
                this.firstChars.push(textContent.substring(0, 1).toLowerCase());
            }
            elem.setAttribute('role', 'none');

            elem = elem.nextElementSibling;
        }

        // console.log(this.menubarItems);
    }
}

export default DSMPMenubar;
