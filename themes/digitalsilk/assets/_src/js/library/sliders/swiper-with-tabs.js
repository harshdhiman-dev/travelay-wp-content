import { u_isTouchDevice } from '../../utils/u_is-touch-device';

class SwiperWithTabs {

    constructor(swiper, options) {
        this.defaults = {
            element: '.l-nav',
            item: '.c-nav__item',
            active: 'is-active',
            trigger: 'click',
        };
        this.isTouch = false;
        // util function to check for touch device
        this.isTouchDevice();

        // if swiper is not initialized, end the script
        if (!swiper.initialized) {
            console.log('swiper not initialized');
            return;
        }

        this.swiper = swiper;

        this.config = Object.assign({}, this.defaults, options || {});

        this.selector = `${this.config.element} ${this.config.item}`;
        this.items = document.querySelectorAll(this.selector);

        // reference to click function
        this.tabClicked = this.tabClick.bind(this);

        this.init();
    }

    init() {
        const self = this;
        // add event that catches slide changes
        self.swiperSlideChange();
        // bind events that catches tabs changes
        self.bindTabs();
    }

    bindTabs() {
        const self = this;
        const elem = self.items;

        elem.forEach((tab) => {
            tab.addEventListener(self.config.trigger, self.tabClicked, {passive: true});

            if (self.isTouch && self.config.trigger === 'mouseover') {
                tab.addEventListener('touchstart', self.tabClicked, {passive: true});
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
            tab.classList.remove(self.config.active);
        })

        currentTab.classList.add(self.config.active);
        self.swiper.slideToLoop(clickedTab);
    }

    tabChange(index) {
        const self = this;
        const elem = self.items;
        elem.forEach((tab) => {
            tab.classList.remove(self.config.active);
        });

        elem.forEach((tab, i) => {
            if (index === i) {
                tab.classList.add(self.config.active);
            }
        });

    }

    isTouchDevice() {
        let self = this;
        if (u_isTouchDevice()) {
            self.isTouch = true;
        }
    }

    swiperSlideChange() {
        let self = this;

        self.swiper.on('slideChange', () => {
            const currentSlide = self.swiper.realIndex;
            self.tabChange(currentSlide);
        });
    }

}

export default SwiperWithTabs;
