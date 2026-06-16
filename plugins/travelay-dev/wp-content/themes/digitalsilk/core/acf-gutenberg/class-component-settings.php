<?php
// phpcs:ignoreFile
class DS_ComponentSettings {

	public $class = '';

	public $styles = '';

	public function __construct( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'component_type'           => get_field( 'component_settings_type' ) ?: 'v1',
				'component_gap_vertical'   => get_field( 'component_settings_inner_gap_vertical' ) ?: 0,
				'component_gap_horizontal' => get_field( 'component_settings_inner_gap_horizontal' ) ?: 0,
				'has_background'           => get_field( 'component_settings_has_background' ) ?: false,
				'has_hover'                => get_field( 'component_settings_has_hover' ) ?: false,
				'title_styles'             => get_field( 'component_settings_title_styles' ) ?: array( 'tag' => 'h3' ),
				'orientation'              => get_field( 'component_settings_orientation' ) ?: 'vertical',
				'horizontal_alignment'     => get_field( 'component_settings_horizontal_alignment' ) ?: 'center',
				'vertical_alignment'       => get_field( 'component_settings_vertical_alignment' ) ?: 'top',
				'media_fit' 			   => get_field( 'component_settings_media_fit' ) ?: 'cover',
				'media_ratio' 			   => get_field( 'component_settings_media_ratio' ) ?: 'r-16x9',
				'text_clamp' 			   => get_field( 'component_settings_text_clamp' ) ?: 'none',
				'component_background'     => get_field( 'component_settings_component_background' ),
				'title_color'              => get_field( 'component_settings_title_color' ),
				'content_color'            => get_field( 'component_settings_content_color' ),
			)
		);

		if ( isset( $args['component_gap_vertical'] ) ) {
			$this->styles .= '--c-block__padding-block:' . intval( $args['component_gap_vertical'] ) . 'px;';
		}

		if ( isset( $args['component_gap_horizontal'] ) ) {
			$this->styles .= '--c-block__padding-inline:' . intval( $args['component_gap_horizontal'] ) . 'px;';
		}

		if ( $args['component_type'] ) {
			$this->class .= " c-block-{$args['component_type']}";
		}

		if ( $args['orientation'] ) {
			$this->class .= " is-{$args['orientation']}";
		}

		if ( $args['has_background'] ) {
			$this->class .= ' has-background';
		}

		if ( $args['component_background'] ) {
			$this->styles .= "--c-block__bg:{$args['component_background']};";
		}

		if ( $args['title_color'] ) {
			$this->styles .= "--c-block__title-color:{$args['title_color']};";
		}

		if ( $args['has_hover'] ) {
			$this->class .= " has-hover";
		}

		if ( ! empty( $args['horizontal_alignment'] ) ) {
			$this->class .= " text-{$args['horizontal_alignment']}";
		}

		if ( ! empty( $args['media_fit'] ) ) {
			$this->class .= " media-{$args['media_fit']}";
		}

		if ( ! empty( $args['media_ratio'] ) ) {
			$this->class .= " {$args['media_ratio']}";
		}

		if ( ! empty( $args['text_clamp'] ) ) {
			$this->styles .= "--c-block__text-clamp:{$args['text_clamp']};";
		}

		if ( ! empty( $args['vertical_alignment'] ) ) {
			$this->class .= " align-{$args['vertical_alignment']}";
		}

		if ( $args['content_color'] ) {
			$this->styles .= "--c-block__color:{$args['content_color']};";
		}
	}
}
