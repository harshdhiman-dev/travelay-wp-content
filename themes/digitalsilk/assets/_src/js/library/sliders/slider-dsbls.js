import { isAutoPlayOn } from './slider-options/autoplay';
import { isLazyLoadOn } from './slider-options/lazy';
import SwiperWithTabs from './swiper-with-tabs';
import { u_throttled } from '../../utils/utils';
import { isNavigationOn } from './slider-options/navigation';
import { u_parseBool } from '../../utils/u_types';
import { isLoopOn } from './slider-options/loop';

/**
 * Class representing a DSMPSliderDSBLS.
 * @constructor
 * @param {string} sliderID - The ID of the slider.
 */
class DSMPSliderDSBLS {
    constructor(sliderID) {
        this.optionsDesktop = {};
        this.optionsMobile = {
            slideClass: 'js-dsbls-nav-item',
            pagination: {
                el: '.l-slider-nav__pagination',
                clickable: true,
            },
        };
        this.optionsNav = {
            item: '.js-dsbls-nav-item',
            active: 'is-active',
            trigger: 'mouseover',
        };

        this.sliderNo = sliderID.replace('js-slider-dsbls-', '');
        this.sliderName = sliderID;
        this.sliderMobileName = sliderID.replace('js-slider-dsbls-', 'js-slider-dsbls-m-');

        this.sliderSel = `#${this.sliderName}`;
        this.sliderMobileSel = `#${this.sliderMobileName}`;
        this.optionsNav.element = this.sliderMobileSel;

        this.sliderElem = document.querySelector(this.sliderSel);
        this.sliderMobileElem = document.querySelector(this.sliderMobileSel);

        this.showMobile = u_parseBool(this.sliderElem.getAttribute('data-slider-is-mobile'));
        this.optionsNav.trigger = this.sliderElem.getAttribute('data-slider-trigger') || 'mouseover';

        this.isMobile = false;
        this.isDesktop = false;

        this.desktopInstance;
        this.mobileInstance;
        this.desktopTabs;

        this.init();
    }

    init() {
        let self = this;
        let currentWidth = window.innerWidth;
        let breakpoint = 1112;

        currentWidth < breakpoint ? self.isMobile = true : self.isDesktop = true;

        self.parseOptions();

        if (self.isMobile && self.showMobile) self.createMobile();
        if (self.isDesktop) self.createDesktop();

        window.addEventListener('resize', () => {
            self.throttleResize();
        });

        self.throttleResize = u_throttled(() => {
            self.resizeSlider();
        }, 350);
    }

    parseOptions() {
        let self = this;

        if (self.isMobile && self.showMobile) {
            let basename = self.sliderMobileName;
            self.optionsMobile = isLoopOn(self.sliderMobileElem, self.optionsMobile);
            self.optionsMobile = isAutoPlayOn(self.sliderMobileElem, self.optionsMobile);
            self.optionsMobile = isLazyLoadOn(self.sliderMobileElem, self.optionsMobile);

            // .m-slider parent is hardcoded in isNavigationOn options
            self.optionsMobile = isNavigationOn(self.sliderMobileElem, self.optionsMobile, basename, self.sliderNo);
        }

        if (self.isDesktop) {
            let basename = self.sliderName;
            self.optionsDesktop = isLoopOn(self.sliderElem, self.optionsDesktop);
            self.optionsDesktop = isAutoPlayOn(self.sliderElem, self.optionsDesktop);
            self.optionsDesktop = isLazyLoadOn(self.sliderElem, self.optionsDesktop);

            // .m-slider parent is hardcoded in isNavigationOn options
            self.optionsDesktop = isNavigationOn(self.sliderElem, self.optionsDesktop, basename, self.sliderNo);

        }
    }

    createDesktop() {
        let self = this;
        self.desktopInstance = new Swiper(self.sliderSel, self.optionsDesktop);
        if (self.desktopInstance.initialized) {
            self.desktopTabs = new SwiperWithTabs(self.desktopInstance, self.optionsNav);
        }
    }

    createMobile() {
        let self = this;
        self.mobileInstance = new Swiper(self.sliderMobileSel, self.optionsMobile);
    }

    resizeSlider() {
        let self = this;
        let newWidth = window.innerWidth;
        let breakpoint = 1112;
        if (newWidth < breakpoint) {
            if (!self.isMobile) {
                if (typeof self.desktopInstance !== "undefined") {
                    self.desktopTabs.unbindTabs();
                    self.desktopInstance.destroy();
                    self.desktopInstance = undefined;
                }

                if (self.showMobile) {
                    self.createMobile();
                }
                self.isDesktop = false;
                self.isMobile = true;
            }
        } else {
            if (!self.isDesktop) {
                if (typeof self.mobileInstance !== "undefined") {
                    self.mobileInstance.destroy();
                    self.mobileInstance = undefined;
                }

                self.createDesktop();
                self.isMobile = false;
                self.isDesktop = true;
            }
        }
    }

}

export default DSMPSliderDSBLS;
