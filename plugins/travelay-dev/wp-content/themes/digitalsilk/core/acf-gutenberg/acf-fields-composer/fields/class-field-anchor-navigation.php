<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_ContentAvailableBlocks extends DS_Field {

	/**
	 * Get
	 *
	 * @param string $field_name field name.
	 * @param array  $args arguments.
	 * @param bool   $is_super_admin super admin check.
	 */
	public static function get( $field_name = 'anchor_available_blocks', array $args = array(), $is_super_admin = false ): string {

		$default_args = array(
			'label' => 'Inner Page Content',
			'ui'    => 1,
		);
		$args         = wp_parse_args( $args, $default_args );

		return self::add_field( 'select', $field_name, $args, $is_super_admin );
	}

	/**
	 * Load Field
	 *
	 * @param array $field contains field.
	 */
	public static function load_field( $field ): array {
		$post_id      = acf_maybe_get_POST( 'post_id' );
		$post_content = get_the_content( null, false, $post_id );
		$blocks       = parse_blocks( $post_content );

		if ( empty( $blocks ) || ! is_array( $blocks ) ) {
			return $field;
		}
		// Reset choices.
		$field['choices'] = array(
			'' => 'Choose link',
		);

		$index = 1;
		foreach ( $blocks as $key => $block ) {
			if ( ! isset( $block['attrs']['id'] ) ) {
				continue;
			}
			$block_data = acf_get_block_type( $block['attrs']['name'] );

			$field['choices'][ $block['attrs']['id'] ] = "Section {$index} - {$block_data['title']}";

			if ( ! empty( $block['innerBlocks'] ) ) {
				foreach ( $block['innerBlocks'] as $key_inner => $block_inner ) {
					if ( ! isset( $block_inner['attrs']['id'] ) ) {
						continue;
					}
					$block_inner_data = acf_get_block_type( $block_inner['attrs']['name'] );

					$field['choices'][ $block_inner['attrs']['id'] ] = "Section {$index} - {$block_inner_data['title']}";
				}
			}
			++$index;
		}

		return $field;
	}
}

// Populate select field using filter.
add_filter( 'acf/load_field/name=anchor_available_blocks', array( DS_Field_ContentAvailableBlocks::class, 'load_field' ) );
