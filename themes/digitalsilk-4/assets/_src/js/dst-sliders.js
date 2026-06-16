/*
 * @title Sliders Scripts
 * @description Application entry point
 */


import {u_addTouchToHtml} from './utils/u_is-touch-device';
import {callSliders, callSlidersAdmin} from './function-calls/dst_SlidersInit';

// backend preview
if (window.acf) {
	u_addTouchToHtml();

	window.acf.addAction('render_block_preview', ($block, $attributes) => {
		callSlidersAdmin($attributes.name);
	});
} else {
	document.addEventListener('DOMContentLoaded', () => {
		u_addTouchToHtml();
		callSliders();
	})
}
