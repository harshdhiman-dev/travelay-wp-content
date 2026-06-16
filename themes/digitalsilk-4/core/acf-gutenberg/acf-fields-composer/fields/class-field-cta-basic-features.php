<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_CtaBasicFeatures extends DS_Field {

	/**
	 * Get
	 */
	public static function get(): array {
		return array(
			DS_Field_ButtonSize::get(),
			DS_Field_ButtonStyle::get(),
			...DS_Field_ButtonIcon::get(),
		);
	}
}
