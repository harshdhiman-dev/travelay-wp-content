import { u_extendObject } from '../../utils/u_object_extend';
import { u_parseBool } from '../../utils/u_types';
import { u_throttled } from '../../utils/utils';

/**
 * Represents a Swiper with circular tabs.
 * @constructor
 * @param {Object} swiper - The Swiper object.
 * @param {Object} options - The options for the SwiperWithCircularTabs class.
 * @param {string} options.element - The selector for the container element.
 * @param {string} options.item - The selector for the tab items.
 * @param {string} options.circle - The selector for the circular tab container.
 * @param {string} options.trigger - The event trigger for tab clicks.
 * @param {Object} options.classes - The CSS classes for the tabs.
 * @param {string} options.classes.active - The CSS class for the active tab.
 * @param {string} options.classes.right - The CSS class for tabs on the right side.
 * @param {string} options.classes.left - The CSS class for tabs on the left side.
 * @param {string} options.classes.top - The CSS class for tabs on the top side.
 * @param {string} options.classes.middle - The CSS class for tabs in the middle.
 * @param {string} options.classes.bottom - The CSS class for tabs on the bottom side.
 * @param {boolean} options.direction - The direction of the circular tabs.
 * @param {number} options.position - The position of the start item.
 * @param {number} options.arrange - The arrangement of the circular tabs.
 * @param {boolean} options.arrangeCentered - Whether to force centered arrangement.
 * @param {string} options.itemAlign - The alignment of the tab items.
 * @param {number} options.itemAngle - The angle of each tab item.
 * @param {boolean} options.rotateActive - Whether to rotate the active tab.
 * @param {number} options.offset - The offset angle of the tabs.
 * @param {boolean} options.symmetric - Whether to use symmetric layout for the tabs.
 * @param {string} options.symmetricOrder - The order of the symmetric layout.
 * @param {Object} options.data - The data attributes for configuring the tabs.
 * @param {string} options.data.arrange - The data attribute for the arrange option.
 * @param {string} options.data.arrangeCentered - The data attribute for the arrange centered option.
 * @param {string} options.data.position - The data attribute for the position option.
 * @param {string} options.data.itemAngle - The data attribute for the item angle option.
 * @param {string} options.data.itemAlign - The data attribute for the item alignment option.
 * @param {string} options.data.direction - The data attribute for the direction option.
 * @param {string} options.data.rotateActive - The data attribute for the rotate active option.
 * @param {string} options.data.offset - The data attribute for the offset option.
 * @param {string} options.data.trigger - The data attribute for the trigger option.
 * @param {string} options.data.symmetric - The data attribute for the symmetric option.
 * @param {string} options.data.symmetricOrder - The data attribute for the symmetric order option.
 */
class SwiperWithCircularTabs {

    constructor(swiper, options) {
        this.defaults = {
            element: '.l-nav',
            item: '.c-nav__item',
            circle: '.slider-nav',
            trigger: 'click',
            classes: {
                active: 'is-active',
                right: 'is-right',
                left: 'is-left',
                top: 'is-top',
                middle: 'is-middle',
                bottom: 'is-bottom',
            },
            direction: false, // false: clockwise, true: anticlockwise
            position: 2, // position of start item, top: 1, right: 2, bottom: 3, left: 4
            arrange: 0, // arrange 0 = full circle, any other number means angle
            arrangeCentered: true, // force centered even if uneven no of items
            itemAlign: 'center', // center, inside, outside
            itemAngle: 0,
            rotateActive: false,
            offset: 0, // max 90, min -90
            symmetric: false,
            symmetricOrder: 'columns', // columns or rows
            data: {
                arrange: 'data-slider-circular-arrange',
                arrangeCentered: 'data-slider-circular-centered',
                position: 'data-slider-circular-position',
                itemAngle: 'data-slider-circular-angle',
                itemAlign: 'data-slider-circular-align-items', // parsed from backend also
                direction: 'data-slider-circular-item-direction',
                rotateActive: 'data-slider-circular-rotate-to-active',
                offset: 'data-slider-circular-offset',
                trigger: 'data-slider-circular-trigger',
                symmetric: 'data-slider-circular-symmetric',
                symmetricOrder: 'data-slider-circular-order',
            },
        };

        // if swiper is not initialized, end the script
        if (!swiper.initialized) {
            console.log('swiper not initialized');
            return;
        }

        this.swiper = swiper;

        this.config = u_extendObject(this.defaults, options);

        this.selector = `${this.config.element} ${this.config.item}`;
        this.container = document.querySelector(this.config.element);
        this.circle = this.container.querySelector(this.config.circle);
        this.items = document.querySelectorAll(this.selector);

        this.shift = 0;
        this.shiftSymmetric = 180;
        this.multiplier = this.items.length;
        this.numberOfItems = this.items.length;
        this.arrangeShift = 0;
        this.full = 360;
        this.arrangeIndex = 0;
        // reference to click function
        this.tabClicked = this.tabClick.bind(this);
        this.parseOptions();
        this.init();
    }

    init() {
        const self = this;
        self.getContainerRadius();
        self.getItemDimensions();
        // add event that catches slide changes
        self.swiperSlideChange();
        // bind events that catches tabs changes
        self.bindTabs();

        self.updateItemsPositions();

        self.container.style.setProperty('--navitems', self.numberOfItems);

        window.addEventListener('resize', u_throttled(() => {
            self.updateItemsPositions();
        }), 150);
    }

    bindTabs() {
        const self = this;
        const elem = self.items;

        elem.forEach((tab) => {
            tab.addEventListener(self.config.trigger, self.tabClicked, { passive: true });

            if (self.isTouch && self.config.trigger === 'mouseover') {
                tab.addEventListener('touchstart', self.tabClicked, { passive: true });
            }
        });
    }

    unbindTabs() {
        const self = this;
        const elem = self.items;

        elem.forEach((tab) => {
            tab.removeEventListener(self.config.trigger, self.tabClicked);

            if (self.isTouch && self.config.trigger === 'mouseover') {
                tab.removeEventListener('touchstart', self.tabClicked);
            }
        });
    }

    tabClick(ev) {
        const self = this;
        const currentTab = ev.currentTarget;
        const elem = self.items;

        let clickedTab;
        elem.forEach((tab, i) => {
            if (currentTab === tab) {
                clickedTab = i;
            }
            tab.classList.remove(self.config.classes.active);
        });

        currentTab.classList.add(self.config.classes.active);
        self.swiper.slideToLoop(clickedTab);
        self.container.style.setProperty('--cAItem', clickedTab);
        if (self.config.rotateActive) {
            self.updateItemsPositions(clickedTab);
        }
    }

    tabChange(index) {
        const self = this;
        const elem = self.items;
        elem.forEach((tab) => {
            tab.classList.remove(self.config.classes.active);
        });

        elem.forEach((tab, i) => {
            if (index === i) {
                tab.classList.add(self.config.classes.active);
            }
        });
        self.container.style.setProperty('--cAItem', index);
    }

    swiperSlideChange() {
        const self = this;

        self.swiper.on('slideChange', () => {
            const currentSlide = self.swiper.realIndex;
            self.tabChange(currentSlide);
            self.updateItemsPositions(currentSlide);
        });
    }

    updateItemsPositions(index) {
        const self = this;
        const elems = self.items;
        let ind;

        if (index == null) {
            ind = self.arrangeIndex;
        } else {
            ind = index;
        }

        let angle;
        let rotateShift = 0;

        if (self.config.rotateActive) {
            rotateShift = (ind - self.arrangeIndex) * self.config.itemAngle;
        }

        const { arrangeShift, multiplier, full } = self;

        const objClasses = Object.values(self.config.classes);

        elems.forEach((elem, i) => {

            let currentIndex = i;
            const divider = Math.ceil(self.numberOfItems / 2);
            if (self.config.symmetric) {
                if (self.config.symmetricOrder === 'rows') {
                    i % 2 === 0 ? currentIndex = i / 2 : currentIndex = (i - 1) / 2;
                }

                if (self.config.symmetricOrder === 'columns') {
                    if (i > divider - 1) currentIndex = i - divider;
                }

            }
            if (self.config.direction) {
                angle = full * (currentIndex / multiplier)
                    + self.shift - arrangeShift - rotateShift - self.config.offset;
            } else {
                angle = -full * (currentIndex / multiplier)
                    + self.shift + arrangeShift + rotateShift + self.config.offset;
            }

            if (self.config.symmetric) {
                if (self.config.symmetricOrder === 'rows') {
                    if (i % 2 === 1) angle = self.shiftSymmetric - angle;
                }
                if (self.config.symmetricOrder === 'columns') {
                    if (i > divider - 1) angle = self.shiftSymmetric - angle;
                }
            }

            const cosine = parseFloat(Math.cos(angle * (Math.PI / 180)).toFixed(6));
            const sinus = parseFloat(Math.sin(angle * (Math.PI / 180)).toFixed(6));

            // eslint-disable-next-line no-nested-ternary
            const itemSideX = cosine === 0
                ? self.config.classes.middle
                : ((cosine < 0)
                    ? self.config.classes.left
                    : self.config.classes.right);
            // eslint-disable-next-line no-nested-ternary
            const itemSideY = sinus === 0
                ? self.config.classes.middle
                : (sinus < 0
                    ? self.config.classes.top
                    : self.config.classes.bottom);

            objClasses.forEach((classItems) => {

                if (!(classItems === 'is-active' || classItems === itemSideY || classItems === itemSideX)) {
                    elem.classList.remove(classItems);
                }
            });

            sinus === 0
                ? elem.classList.add(itemSideY, itemSideX)
                : elem.classList.add(itemSideX, itemSideY);

            /* calculate actual height of rotated elements */
            if (self.config.itemAlign !== 'center') {
                const height = elem.offsetHeight;
                const width = elem.offsetWidth;

                const rHeight = parseFloat(
                    (Math.abs(cosine) * height) + (Math.abs(sinus) * width),
                ).toFixed(6);
                const rWidth = parseFloat(
                    (Math.abs(cosine) * width) + (Math.abs(sinus) * height),
                ).toFixed(6);

                elem.style.setProperty('--itemRH', `${rHeight}px`);
                elem.style.setProperty('--itemRW', `${rWidth}px`);
            }

            elem.style.setProperty('--az', `${angle}deg`);
        });
    }

    parseOptions() {
        const self = this;
        /* parse arranging of items, center, or none */
        const arrange = self.container.getAttribute(self.config.data.arrange);
        /* parse position, top, left, right, bottom */
        self.config.position = parseInt(self.container.getAttribute(self.config.data.position), 10);

        /* parse angle */
        self.config.itemAngle = parseInt(
            self.container.getAttribute(self.config.data.itemAngle),
            10,
        ) || self.config.itemAngle;
        /* parse alignment of items to circle, inside, outside or center */
        self.config.itemAlign = self.container.getAttribute(self.config.data.itemAlign)
            || self.config.itemAlign;

        self.config.direction = u_parseBool(self.container.getAttribute(self.config.data.direction))
            || self.config.direction;
        /* parse direction, clockwise, anticlockwise */
        self.config.rotateActive = u_parseBool(
            self.container.getAttribute(self.config.data.rotateActive),
        ) || self.config.rotateActive;
        /* parse offset, if you want to have items start
        from different angle from starting position */
        self.config.offset = parseInt(self.container.getAttribute(self.config.data.offset), 10)
            || self.config.offset;
        /* trigger method, click or mouseover */
        const trigger = self.container.getAttribute(self.config.data.trigger)
            || self.config.trigger;

        if (trigger === 'mouseover') {
            self.config.trigger = 'mouseover';
            self.config.rotateActive = false;
        }

        switch (self.config.position) {

            case 1:
                self.shift = -90;
                self.shiftSymmetric = 0;
                break;
            case 3:
                self.shift = 90;
                self.shiftSymmetric = 0;
                break;
            case 4:
                self.shift = 180;
                self.shiftSymmetric = 180;
                break;
            default:
                self.shift = 0;
                self.shiftSymmetric = 180;

        }

        let isSemiCircle = false;

        if (self.config.itemAngle
            && (self.config.itemAngle * self.numberOfItems <= self.full)
            && (self.config.itemAngle > 15)) {
            self.full = self.config.itemAngle;
            self.multiplier = 1;
            isSemiCircle = true;
        } else {
            self.config.itemAngle = (self.full / self.numberOfItems);
        }

        if (arrange === 'center' && isSemiCircle) {
            /* parse force centered */
            self.config.arrangeCentered = u_parseBool(
                self.container.getAttribute(self.config.data.arrangeCentered),
            );
            /* parse symmetric options */
            self.config.symmetric = u_parseBool(
                self.container.getAttribute(self.config.data.symmetric),
            ) || self.config.symmetric;
            self.config.symmetricOrder = self.container.getAttribute(
                self.config.data.symmetricOrder,
            ) || self.config.symmetricOrder;

            if (self.config.symmetric) self.config.rotateActive = false;

            const divider = self.config.symmetric ? 4 : 2;
            self.arrangeIndex = ((self.numberOfItems - 1) / divider);
            if (self.config.arrangeCentered) self.arrangeIndex = Math.floor(self.arrangeIndex);
            self.arrangeShift = self.arrangeIndex * self.config.itemAngle;
        }

        if (Math.abs(self.config.offset) > 90) {
            self.config.offset = 0;
        }
    }

    getContainerRadius() {
        const self = this;
        const { circle } = self;

        const observer = new ResizeObserver((entries) => {

            entries.forEach((entry) => {
                const pureradius = entry.contentRect.width / 2;
                const radius = entry.borderBoxSize[0].inlineSize / 2;

                entry.target.style.setProperty('--r', `${pureradius.toFixed()}px`);
                entry.target.style.setProperty('--rclean', `${radius.toFixed()}px`);
            });
        });
        observer.observe(circle);
    }

    getItemDimensions() {
        const self = this;
        const elems = self.items;

        const observer = new ResizeObserver((entries) => {

            entries.forEach((entry) => {
                const { width, height } = entry.contentRect;

                entry.target.style.setProperty('--itemH', `${height}px`);
                entry.target.style.setProperty('--itemW', `${width}px`);

            });
        });

        elems.forEach((elem) => {
            observer.observe(elem);
        });

    }

}

export default SwiperWithCircularTabs;
