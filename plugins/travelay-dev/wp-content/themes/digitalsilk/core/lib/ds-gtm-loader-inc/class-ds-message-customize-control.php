<?php
/**
 * Class DS_Message_Customize_Control
 *
 * Customizer control for displaying messages.
 *
 * @package DS_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'DS_Message_Customize_Control' ) && class_exists( 'WP_Customize_Control' ) ) {
	/**
	 * Class DS_Message_Customize_Control
	 *
	 * Customizer control for displaying messages.
	 *
	 * @package DS_Theme
	 */
	class DS_Message_Customize_Control extends WP_Customize_Control {
		/**
		 * Control type.
		 *
		 * @var string
		 */
		public $type = 'ds_message';

		/**
		 * Render the control's content.
		 *
		 * @return void
		 */
		public function render_content() {
			$title       = $this->label;
			$description = $this->description;
			if ( ! empty( $this->label ) ) {
				echo '<span class="customize-control-title">' . wp_kses_post( $title ) . '</span>';
			}
			if ( ! empty( $this->description ) ) {
				echo '<p class="customize-control-description">' . wp_kses_post( $description ) . '</p>';
			}
		}
	}
}
