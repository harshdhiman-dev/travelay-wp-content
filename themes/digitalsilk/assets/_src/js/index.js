/*
 * @title Main App
 * @description Application entry point
 */

// Utils
import { u_addTouchToHtml } from './utils/u_is-touch-device';
import DSMPViewAnim from './utils/u_io-anim';

// Header
import { dst_HeaderSticky } from './header/dst_HeaderSticky';
import { dst_HeaderSearch } from './header/dst_HeaderSearch';
import { dst_HeaderMobileToggleMenu } from './header/dst_HeaderMobileToggleMenu';
import { dst_HeaderMegaMenu } from './header/dst_HeaderMegaMenu';

// Function Calls

import { dst_Collapse } from './library/collapsers/dst_Collapse';
import { dst_ToggleElement } from './library/collapsers/dst_ToggleElement';
import { dst_ReadMore } from './function-calls/tinymce-read-more/dst_ReadMore';

// Libraries
import DSMPMediaControls from './library/media-controls/media-control';
import { dst_DimboxInit } from './components/dst_DimboxConfig.js';
import dstMarqueeHoverEffect from "./components/dst-marquee-hover-effect";
import adivahaSearchSwitcher from "./components/adivahaSearchSwitcher";
import burgerMenuSwitcher from "./components/burgerMenuSwitcher";

// Components

// import ProgressCircleCounter from './library/counters/progress-counter';

document.addEventListener('DOMContentLoaded', () => {

	/*window.onerror = function(message, source, lineno, colno, error) {
		if (String(message).includes("postMessage")) {
			return true;
		}
	};*/


	// Check whether it is touch device or not
	u_addTouchToHtml();
	// u_scrollEffect()

	/**
	 * Header
	 */
	// Sticky header
	dst_HeaderSticky('.site-header', 'is-sticky', 'scrolling-down', 'scrolling-up');


	burgerMenuSwitcher('.js-menu','.js-menu-switcher');

	adivahaSearchSwitcher('.dst-adivaha-wrapper','.dst-adivaha-switcher');

	// Mobile menu toggle
	dst_HeaderMobileToggleMenu('.js-m-burger-toggle', '.js-m-burger-wrap');

	dst_HeaderSearch();
	dst_HeaderMegaMenu();

	/**
	 * Utils
	 */
	dst_Collapse();
	dst_ReadMore();
	dst_ToggleElement();

	/**
	 * Libraries
	 */
	// eslint-disable-next-line no-new
	new DSMPMediaControls();
	dst_DimboxInit();
	dstMarqueeHoverEffect();

	/**
	 * Components
	 */
	// eslint-disable-next-line no-new
	/*  new ProgressCircleCounter({
		percentage: 80,
	}); */

	// eslint-disable-next-line no-new
	new DSMPViewAnim({});
});

window.addEventListener('load', () => {
	// Enable if using lazy load on Video (set data-src instead of src)
	// eslint-disable-next-line
	let lazyLoadInstance = new LazyLoad();
});
