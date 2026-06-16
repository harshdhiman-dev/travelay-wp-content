<?php
/**
 * Helps to create fields with common used types
 */
class DS_Field {

	/**
	 * Allowed field types
	 *
	 * @var array|string[]
	 */
	private static array $allowed_field_types = array(
		'accordion',
		'button_group',
		'checkbox',
		'color_picker',
		'date_picker',
		'date_time_picker',
		'email',
		'file',
		'google_map',
		'group',
		'image',
		'link',
		'message',
		'number',
		'oembed',
		'page_link',
		'password',
		'post_object',
		'radio',
		'range',
		'relationship',
		'select',
		'separator',
		'tab',
		'taxonomy',
		'text',
		'textarea',
		'time_picker',
		'true_false',
		'url',
		'user',
		'wysiwyg',
		'repeater',
		'clone',
		'flexible_content',
		'gallery',
	);

	/**
	 * Add field
	 *
	 * @param string $type field type.
	 * @param string $component_name component name.
	 * @param array  $field_args field arguments.
	 * @param bool   $is_super_admin is super admin.
	 *
	 * @return string
	 */
	public static function add_field( string $type, string $component_name, array $field_args, bool $is_super_admin = false ): string {
		if ( in_array( $type, self::$allowed_field_types, true ) ) {
			$filter_unique     = uniqid( '', true );
			$field_filter_name = "DSMP/ACF/fields/{$type}_{$filter_unique}_{$component_name}";

			$defaults           = acf_get_field_type( $type )->defaults ?? array();
			$field_args         = wp_parse_args( $field_args, $defaults );
			$field_args['name'] = $component_name;
			$field_args['type'] = $type;

			if ( $is_super_admin ) {
				$field_args['wrapper'] = array(
					'width' => '',
					'class' => 'js-ds-super-admin',
					'id'    => '',
				);
			}

			add_filter(
				$field_filter_name,
				function ( $field ) use ( $field_args ) {
					return $field_args;
				},
				10,
				1
			);

			return $field_filter_name;
		}

		return false;
	}

	/**
	 * Magic method to call field creation
	 *
	 * @param string $method contains method.
	 * @param array  $args contains arguments.
	 *
	 * @return false|mixed
	 */
	public static function __callStatic( $method, $args ) {
		if ( ! in_array( $method, self::$allowed_field_types, true ) ) {
			throw new BadMethodCallException();
		}

		array_unshift( $args, $method );

		return call_user_func_array( array( __CLASS__, 'add_field' ), $args );
	}

	/**
	 * Generates choices for fields
	 * 1=>'1' or, for example, 'v1'=>'v1'
	 *
	 * @param int|array $num contains number.
	 * @param string    $prefix contains prefix.
	 *
	 * @return array
	 */
	public static function get_choices( $num = 5, string $prefix = '' ): array {
		$choices = array();

		if ( is_array( $num ) ) {
			foreach ( $num as $choice ) {
				$choices[ $prefix . $choice ] = $prefix . $choice;
			}
		} else {
			for ( $i = 0; $i < $num; $i++ ) {
				$choices[ $prefix . ( $i + 1 ) ] = $prefix . ( $i + 1 );
			}
		}

		return $choices;
	}
}
