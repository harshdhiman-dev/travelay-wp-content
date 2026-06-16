/**
 * Activates the mobile menu by adding necessary classes and attributes to the elements.
 *
 * @param {HTMLElement} btn  - The button element that triggers the menu.
 * @param {HTMLElement} body - The body element used to toggle the menu.
 */
const openMobileMenu = (btn, body) => {
    btn.classList.add('is-active');
    body.classList.add('nav-active');
    btn.setAttribute('aria-expanded', 'true');
};

/**
 * Closes the mobile menu by modifying the provided button and body elements.
 *
 * @param {HTMLElement} btn  - The button element representing the mobile menu button.
 * @param {HTMLElement} body - The body element where the mobile menu is displayed.
 * @return {void}
 */
const closeMobileMenu = (btn, body) => {
    btn.classList.remove('is-active');
    body.classList.remove('nav-active');
    btn.setAttribute('aria-expanded', 'false');
};

/**
 * Toggles the visibility of a sub item and updates its aria attributes.
 *
 * @param {HTMLElement} subItem  - The sub item to show or hide.
 * @param {string}      type     - The operation to perform on the classList ('add' or 'remove').
 * @param {string}      ariaAttr - The value to set for the aria-expanded attribute.
 * @return {void} - Does not return any value.
 */
const showHideSubItem = (subItem, type, ariaAttr) => {
    if (subItem) {
        subItem.classList[type]('is-visible');
        // eslint-disable-next-line no-param-reassign
        subItem.ariaExpanded = [ariaAttr];
        // subItem.style.height = `${ height }px`;
    }
};

/**
 * Toggles the visibility of a sub-menu item.
 *
 * @param {Element} item       - The sub-menu item to toggle.
 * @param {Element} itemParent - The parent element of the sub-menu item.
 * @param {Element} itemMenu   - The sub-menu element containing the item.
 * @return {void}
 */
const openSubMenu = (item, itemParent, itemMenu) => {
    item.classList.add('is-toggled');
    itemParent.classList.add('is-opened');
    showHideSubItem(itemMenu, 'add', 'true');
};

/**
 * Close sub menu.
 *
 * @param {HTMLElement} item       - The menu item being closed.
 * @param {HTMLElement} itemParent - The parent element of the menu item.
 * @param {HTMLElement} itemMenu   - The sub menu element.
 * @return {void}
 */
const closeSubMenu = (item, itemParent, itemMenu) => {
    item.classList.remove('is-toggled');
    itemParent.classList.remove('is-opened');
    showHideSubItem(itemMenu, 'remove', 'false');
};

/**
 * Toggles the visibility of child sub-menus when a menu item is clicked.
 *
 * @param {Element} item - The menu item element.
 */
const checkChildSubMenu = (item) => {
    const toggleInnerButton = Array.from(item.nextElementSibling.getElementsByClassName('js-sub-menu-toggle'));
    if (toggleInnerButton) {
        toggleInnerButton.forEach((innerItem) => {
            const childSubMenu = innerItem.nextElementSibling;
            if (childSubMenu.ariaExpanded === 'true') {
                childSubMenu.ariaExpanded = 'false';
            } else if (childSubMenu.classList.contains('is-visible')) {
                childSubMenu.ariaExpanded = 'true';
            }
        });
    }
};

export {
    openMobileMenu, closeMobileMenu, closeSubMenu, openSubMenu, checkChildSubMenu,
};
