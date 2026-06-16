import {dsblsSlider} from './sliders/dst_SliderDsbls';
import {simpleSliders} from './sliders/dst_SliderSimple';
import {advancedSliders} from './sliders/dst_SliderAdvanced';
import {circularSliders} from './sliders/dst_SliderCircular';
import {extendedSliders} from './sliders/dst_SliderExtended';
import {u_addTouchToHtml} from "../utils/u_is-touch-device";

/**
 * Calls all the slider functions to initialize and set up sliders.
 * @function callSliders
 * @returns {void}
 */
const callSliders = () => {
	dsblsSlider();
	simpleSliders();
	advancedSliders();
	circularSliders();
	extendedSliders();
};

const callSlidersAdmin = (acfBlockName) => {
	switch (acfBlockName) {
		case 'acf/testimonials-slider-2':
		case 'acf/cards-slider':
		case 'acf/images-slider':
		case 'acf/marquee-slider':
		case 'acf/panel-slider':
		case 'acf/testimonials-slider-1':
			simpleSliders();
			break;
		case 'acf/advanced-banner-slider':
			advancedSliders();
			break;
		case 'acf/circular-slider':
			circularSliders();
			break;
		case 'acf/double-cards':
			dsblsSlider()
			break;
		case 'acf/extended-banner-slider':
			extendedSliders();
			break;
		default:
			console.log(`Sorry, we are out of ${acfBlockName}.`);
	}
};

export {callSliders, callSlidersAdmin};
