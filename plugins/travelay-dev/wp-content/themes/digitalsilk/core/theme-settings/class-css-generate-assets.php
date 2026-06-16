<?php
//phpcs:ignoreFile

/**
 * Generate theme.css :root{} file
 *
 * Triggers after saving ACF groups on Theme content and Theme setting option pages
 */
class DS_GenerateAssets extends DS_Settings {

	/**
	 * ACF fields slugs that should be saved to a css file
	 */
	private $root_settings_fields = array(
		'theme_colors',
		'alternative_colors',
		'font_settings',
		'buttons_styles',
		'forms_settings',
		'header_gradient_styles',
		'header_top_content_styles',
		'header_main_content_styles',
		'header_bottom_content_styles',
		'header_sticky_type',
		'sticky_header_color',
		'header_height',
		'header_height_mobile',
		'footer_content_styles',
		'layout_settings',
		'theme_content_settings',
	);

	/**
	 * default content for theme.css file after theme activation
	 */
	private $default_colors = [
		'primary-color1'   => '#4558AA',
		'primary-color2'   => '#3550BE',
		'primary-color3'   => '#A6B6F6',
		'secondary-color1' => '#4A6CFA',
		'secondary-color2' => '#E8EEFF',
		'secondary-color3' => '#F7F9FF',
		'body-color'       => '#FFFFFF',
		'border-color'     => '#4558AA',
	];

	/**
	 * :root{} css variables
	 */
	private $root_content = '';

	public function __construct() {

		add_action(
			'after_setup_theme',
			function () {
				$this->save_styles( true );
			}
		);

		add_action(
			'acf/save_post',
			function () {
				// check ACF options page to save content
				$url_base = get_current_screen()->base;

				if ( strpos( $url_base, 'theme-settings' ) !== false || strpos( $url_base, 'theme-content' ) !== false || strpos( $url_base, 'theme-fonts' ) !== false || strpos( $url_base, 'theme-buttons' ) !== false || strpos( $url_base, 'theme-forms' ) !== false || false !== strpos( $url_base, 'theme-general-content' ) ) {
					$this->save_styles();
				}
			},
			999
		);
	}

	/**
	 * Writes styles into file
	 */
	public function save_styles( $is_create = false ) {
		$assets_dir = wp_get_upload_dir()['basedir'] . '/dsmp-assets';
		if ( ! is_dir( $assets_dir ) ) {
			mkdir( $assets_dir, 0755 );
		}

		$assets_file_path = "{$assets_dir}/theme.css";

		if ( $is_create ) {
			if ( ! file_exists( $assets_file_path ) ) {
				file_put_contents( $assets_file_path, $this->build_styles( true ) );
			}
		} else {
			file_put_contents( $assets_file_path, $this->build_styles() );
		}
	}

	/**
	 * Gets styles within root css
	 *
	 * @return string
	 */
	public function build_styles( $is_default = false ) {
		if ( $is_default ) {
			$this->add_default_colors();
		} else {
			$this->build_settings();
		}

		$render = ':root{' . $this->root_content . '}';

		return $render;
	}

	/**
	 * Builds css vars
	 */
	public function build_settings() {
		if ( ! empty( $this->root_settings_fields ) ) {
			foreach ( $this->root_settings_fields as $setting_field ) {
				$this->build_fields( $setting_field );
			}
		}
	}

	/**
	 * Gets field object by name and runs build process
	 *
	 * @param $group_fields_name
	 */
	protected function build_fields( $group_fields_name ) {
		if ( 'alternative_colors' == $group_fields_name ) {
			if ( have_rows( 'alternative_colors', 'options' ) ) {
				$flag = 4;
				while ( have_rows( 'alternative_colors', 'options' ) ) {
					the_row();
					$field_object                    = get_sub_field_object( 'alt_color', true, true );
					$field_object['ds_css_key_name'] = $field_object['ds_css_key_name'] . $flag;
					$this->build_field_with_object( $field_object );
					$flag ++;
				}
			}
		} else {
			$field_object = get_field_object( $group_fields_name, 'options', true, true );
			$this->build_field_with_object( $field_object );
		}
	}

	/**
	 * Parses field object or settings
	 *
	 * @param $field_object
	 */
	protected function build_field_with_object( $field_object ) {
		if ( ! empty( $field_object['type'] ) ) {
			if ( ! empty( $field_object['sub_fields'] ) ) {
				foreach ( $field_object['sub_fields'] as $sub_field ) {
					$this->prepare_field_parse( $sub_field, $field_object['value'], $field_object['name'] );
				}
			} else {
				$this->prepare_field_parse( $field_object, null, null, true );
			}
		}
	}

	/**
	 * Parses settings by field type
	 *
	 * @param      $field
	 * @param      $group_fields_value
	 * @param      $parent_group_name
	 * @param bool $is_single
	 */
	protected function prepare_field_parse( $field, $group_fields_value, $parent_group_name, $is_single = false ) {
		$types_execlude         = [ 'accordion' ];// empty types
		$ds_field_types_exclude = [ 'color_switcher_color', 'gradient_color', 'gradient_direction' ];

		// fix for WPML and ACFML plugins
		if ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) && $parent_group_name && ! isset( $group_fields_value[ $field['name'] ] ) ) {
			$field['name'] = str_replace( $parent_group_name . '_', '', $field['name'] );
		}

		// check if this sub-subfield has child sub-fields
		if ( ! empty( $field['sub_fields'] ) ) {
			if ( empty( $field['ds_asset_type'] ) || ( isset( $field['ds_asset_type'] ) && $field['ds_asset_type'] !== 'gradient' ) ) {
				$this->build_fields( $parent_group_name . '_' . $field['name'] );

				return;
			}
		}

		if ( ! empty( $field['ds_asset_type'] ) && ! in_array( $field['type'], $types_execlude, false ) ) {
			// skip field
			if ( in_array( $field['ds_asset_type'], $ds_field_types_exclude ) ) {
				return;
			}

			$field_name = ! empty( $field['ds_css_key_name'] ) ? $field['ds_css_key_name'] : $field['name'];

			if ( ! empty( $field['conditional_logic'] ) ) {
				if ( $is_single ) {
					$conditional_field_value = get_field( $field['conditional_logic'][0][0]['field'], 'options' );
				} else {
					$conditional_field = get_field_object( $field['conditional_logic'][0][0]['field'], 'options', true, true );
					if ( ! empty( $conditional_field['parent'] ) ) {
						$group_conditional_field = get_field_object( $conditional_field['parent'], 'options', true, true );
						$conditional_field_value = $group_conditional_field['value'][ $conditional_field['_name'] ];
					} else {
						$conditional_field_value = $conditional_field['value'];
					}
				}
				if ( $conditional_field_value != $field['conditional_logic'][0][0]['value'] ) {
					return;
				}
			}

			if ( $field['ds_asset_type'] === 'color_switcher' ) { // for color switcher we need custom color field
				if ( $is_single ) {
					$color_object = get_field_object( $field['name'] . '_custom', 'options', true, true );
					$custom_color = ! empty( $color_object['value'] ) ? $color_object['value'] : 'rgba(0,0,0,0)';
				} else {
					$custom_color = ! empty( $group_fields_value[ $field['name'] . '_custom' ] ) ? $group_fields_value[ $field['name'] . '_custom' ] : 'rgba(0,0,0,0)';
				}
				$this->build_var( $field_name, $is_single ? $field['value'] : $group_fields_value[ $field['name'] ], $field['ds_asset_type'], [ 'color' => $custom_color ] );
			} elseif ( $field['ds_asset_type'] === 'color' ) { // color field can return vars
				$color_field_value = $is_single ? $field['value'] : $group_fields_value[ $field['name'] ];
				$color_type        = false !== strpos( $color_field_value, '--' ) ? 'var' : $field['ds_asset_type'];
				$this->build_var( $field_name, $color_field_value, $color_type );
			} elseif ( $field['ds_asset_type'] === 'gradient' ) { // gradient field has 3 additional field
				$field_value = $is_single ? $field['value'] : $group_fields_value[ $field['name'] ];
				if ( ! empty( $field_value ) ) {
					if ( $is_single ) {
						$values = [
							'gradient_color_1'   => get_field( $field['name'] . '_color_1', 'options' ),
							'gradient_color_2'   => get_field( $field['name'] . '_color_2', 'options' ),
							'gradient_direction' => get_field( $field['name'] . '_direction', 'options' ),
						];
					} else {
						$values = [
							'gradient_color_1'   => $group_fields_value[ $field['name'] . '_color_1' ],
							'gradient_color_2'   => $group_fields_value[ $field['name'] . '_color_2' ],
							'gradient_direction' => $group_fields_value[ $field['name'] . '_direction' ],
						];
					}
					$this->build_var( $field_name, $values, $field['ds_asset_type'] );
				}
			} else { // other field types
				$this->build_var( $field_name, $is_single ? $field['value'] : $group_fields_value[ $field['name'] ], $field['ds_asset_type'] );
			}
		}
	}

	/**
	 * Build variables for css from settings and its fields' type
	 *
	 * @param        $key
	 * @param        $value
	 * @param string $type
	 * @param array  $args
	 */
	protected function build_var( $key, $value, $type = '', $args = [] ) {
		if ( empty( $value ) ) {
			return;
		}

		switch ( $type ) {
			case 'font_family':
				$this->root_content .= '--' . $key . ':"' . $value . '", sans-serif;';
				break;
			case 'size_em':
				$this->root_content .= '--' . $key . ':' . $value . 'em;';
				break;
			case 'size_rem':
				$this->root_content .= '--' . $key . ':' . $value / 10 . 'rem;';
				break;
			case 'size_px':
				$this->root_content .= '--' . $key . ':' . $value . 'px;';
				break;
			case 'var':
				$this->root_content .= '--' . $key . ':var(' . $value . ');';
				break;
			case 'img_url':
				if ( ! empty( $value ) ) {
					$this->root_content .= '--' . $key . ':url(' . wp_make_link_relative( is_array( $value ) ? $value['url'] : $value ) . ');';
				}
				break;
			case 'img_url_base64':
				if ( ! empty( $value ) ) {
					$icon_type          = pathinfo( $value['url'], PATHINFO_EXTENSION );
					$icon_type          = $icon_type === 'svg' ? 'svg+xml' : $icon_type;
					$auth               = base64_encode( 'digital:silk' );
					$context            = stream_context_create(
						[
							'http' => [
								'header' => "Authorization: Basic $auth",
							],
						]
					);
					$data               = file_get_contents( get_attached_file( $value['ID'] ), false, $context );
					$base64             = 'data:image/' . $icon_type . ';base64,' . base64_encode( $data );
					$this->root_content .= '--' . $key . ':url(' . $base64 . ');';
				}
				break;
			case 'color':
				$this->root_content .= '--' . $key . ':' . $value . ';';
				break;
			case 'color_switcher':
				$this->root_content .= $this->build_field_color_switcher( $key, $value, $args['color'] );
				break;
			case 'gradient':
				$this->build_field_gradient( $key, $value );
				break;
			default:
				$this->root_content .= '--' . $key . ':' . $value . ';';
		}
	}

	/**
	 * Builds var from color switcher
	 *
	 * @param $switcher
	 * @param $color
	 * @param $key
	 *
	 * @return string
	 */
	protected function build_field_color_switcher( $key, $switcher, $color = null ) {
		if ( ! empty( $switcher ) ) {
			return $switcher === 'custom' ? "--$key:" . $color . ';' : "--$key:var($switcher);";
		}

		return '';
	}

	/**
	 * Builds gradient field
	 *
	 * @param      $key
	 * @param null $setting
	 */
	protected function build_field_gradient( $key, $setting = null ) {
		$g_c_1 = ( empty( $setting['gradient_color_1'] ) ) ? 'transparent' : $setting['gradient_color_1'];
		$g_c_2 = ( empty( $setting['gradient_color_2'] ) ) ? 'transparent' : $setting['gradient_color_2'];

		$gradient_val = sprintf( 'linear-gradient(to %1$s, %2$s, %3$s)', $setting['gradient_direction'], $g_c_1, $g_c_2 );

		$this->root_content .= "--$key:" . $gradient_val . ';';
	}

	public function add_default_colors() {
		if ( ! empty( $this->default_colors ) ) {
			foreach ( $this->default_colors as $key => $color ) {
				$this->root_content .= '--' . $key . ':' . $color . ';';
			}
		}
	}
}

new DS_GenerateAssets();
