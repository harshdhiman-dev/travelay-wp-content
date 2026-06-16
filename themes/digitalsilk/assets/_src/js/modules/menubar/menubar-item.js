/**
 * Represents a menu item in a menubar.
 * @class
 * @param {HTMLElement} menubarItem - The DOM element for the menu item.
 * @param {Object}      menuObj     - The menu object that this item belongs to.
 */
class MenubarItem {
    constructor(menubarItem, menuObj) {
        // console.log('elem', menubarItem);
        // console.log(menuObj, 'menu obj');
        this.menu = menuObj;
        this.menubarItem = menubarItem;
        this.popupMenu = false;

        this.hasFocus = false;
        this.hasHover = false;

        this.isMenubarItem = true;

        this.keyCode = Object.freeze({
            TAB: 9,
            RETURN: 13,
            ESC: 27,
            SPACE: 32,
            PAGEUP: 33,
            PAGEDOWN: 34,
            END: 35,
            HOME: 36,
            LEFT: 37,
            UP: 38,
            RIGHT: 39,
            DOWN: 40,
        });
    }

    init() {
        this.menubarItem.tabIndex = -1;

        // this.domNode.addEventListener('keydown', this.handleKeydown.bind(this));
        // this.domNode.addEventListener('focus', this.handleFocus.bind(this));
        // this.domNode.addEventListener('blur', this.handleBlur.bind(this));
        // this.domNode.addEventListener('mouseover', this.handleMouseover.bind(this));
        // this.domNode.addEventListener('mouseout', this.handleMouseout.bind(this));

        // Initialize pop up menus

        // const nextElement = this.menubarItem.nextElementSibling;

        // console.log(nextElement, this.menubaritem);

        // if (nextElement && nextElement.tagName === 'UL') {
        //     this.popupMenu = new PopupMenu(nextElement, this);
        //     this.popupMenu.init();
        // }
    }
}

export default MenubarItem;
