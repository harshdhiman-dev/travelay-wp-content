<?php
/**
 * DS Icon Library ACF Class
 *
 * @package DigitalSilk
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class DS_Icon_Library_ACF
 */
class DS_Icon_Library_ACF {

	/**
	 * Setup project hooks.
	 */
	public static function init() {
		add_action(
			'acf/load_field/name=button-project-icon-library',
			array(
				__CLASS__,
				'load_icon_library_buttons',
			)
		);
		add_action(
			'acf/load_field/name=link-btn-project-icon-library',
			array(
				__CLASS__,
				'load_icon_library_buttons',
			)
		);
	}

	/**
	 * Load of icon library fields.
	 *
	 * @param array $field The field array.
	 * @return array
	 */
	public static function load_icon_library_buttons( $field ) {
		if ( ! class_exists( 'DS_Icon_Library' ) ) {
			return $field;
		}

		$icons = DS_Icon_Library::get_all_icons();
		if ( empty( $icons['buttons'] ) ) {
			return $field;
		}

		$choices = [];
		foreach ( $icons['buttons'] as $icon ) {
			if ( empty( $icon['id'] ) || empty( $icon['url'] ) ) {
				continue;
			}

			$choices[ $icon['id'] ] = "<img src='{$icon['url']}' alt='' class='js-library-icon'/>";
		}

		$field['choices'] = $choices;

		return $field;
	}
}

DS_Icon_Library_ACF::init();
