<?php

use ACFComposer\ACFComposer;

class DS_ACF_Block_Manager {

	/**
	 * Initialize hooks for loading blocks and ACF fields.
	 */
	public static function init() {
		add_action( 'init', [ self::class, 'register_blocks' ], 5 );
		add_action( 'wp_enqueue_scripts', [ self::class, 'register_blocks_assets' ] );
		add_filter( 'acf/init', [ self::class, 'register_acf_field_groups' ] );
		add_filter( 'acf/load_field/name=ds_classes', [ self::class, 'setup_block_custom_classes' ], 99 );
		add_filter( 'acf/prepare_field/name=decor_settings', [ self::class, 'setup_block_custom_decorations' ], 99 );
	}

	/**
	 * Register all blocks based on their JSON definitions.
	 */
	public static function register_blocks(): void {
		$theme_path = untrailingslashit( get_theme_file_path() );
		foreach ( self::get_blocks() as $block_path ) {
			$block_full_path = $theme_path . '/' . $block_path;
			if ( file_exists( $block_full_path ) ) {
				register_block_type( $block_full_path );
			}
		}
	}

	/**
	 * Register assets for specific blocks.
	 */
	public static function register_blocks_assets(): void {
		foreach ( self::get_blocks() as $block_path ) {
			$config = self::get_block_config( $block_path );

			if ( isset( $config['register_assets_callback'] ) && is_callable( $config['register_assets_callback'] ) ) {
				call_user_func( $config['register_assets_callback'] );
			}
		}
	}

	/**
	 * Register ACF field groups for blocks.
	 */
	public static function register_acf_field_groups(): void {
		foreach ( self::get_blocks() as $block_path ) {
			$block_name = self::get_block_name( $block_path );
			$config     = self::get_block_config( $block_path );

			$fields = $config['fields'] ?? [];
			$fields = array_merge( $fields, self::get_block_config_fields( $block_name ) );

			self::register_composer_fields( $block_name, $fields );
		}
	}

	/**
	 * Get block name.
	 *
	 * @param string $block_path Path to the block directory.
	 *
	 * @return string Block name.
	 */
	private static function get_block_name( string $block_path ): string {
		return basename( rtrim( $block_path, '/' ) );
	}

	/**
	 * Retrieve and parse the configuration for a block.
	 *
	 * @param string $block_path Path to the block directory.
	 *
	 * @return array Block configuration.
	 */
	private static function get_block_config( string $block_path ): array {
		$theme_path  = untrailingslashit( get_theme_file_path() );
		$config_path = $theme_path . trailingslashit( $block_path ) . 'config.php';

		return is_readable( $config_path ) ? include $config_path : [];
	}

	/**
	 * Get custom settings fields for a block.
	 *
	 * @param string $block_name Name of the block.
	 */
	private static function get_block_config_fields( string $block_name ): array {
		return [
			DS_Field::accordion( 'ds_custom_project_acc', [ 'label' => 'Project Custom Settings' ], true ),
			DS_Field::group(
				'ds_custom_settings',
				[
					'label'      => '',
					'layout'     => 'block',
					'sub_fields' => [
						DS_Field::checkbox(
							'ds_classes',
							[
								'label'         => 'Class',
								'choices'       => [],
								'default_value' => '',
								'multiple'      => true,
								'ui'            => true,
								'layout'        => 'horizontal',
								'ds_block_name' => $block_name,
							]
						),
					],
				],
				true
			),
			DS_Field::repeater(
				'decor_settings',
				array(
					'label'         => '',
					'layout'        => 'block',
					'button_label'  => 'Add Decor',
					'ds_block_name' => $block_name,
					'sub_fields'    => array(
						DS_Field::accordion(
							'decor_acc',
							array(
								'label'        => 'Decoration',
								'open'         => 1,
								'multi_expand' => 1,
							)
						),
						DS_Field::button_group(
							'decor_type',
							array(
								'label'         => 'Decor Type',
								'choices'       => array(
									'none'  => 'none',
									'class' => 'class',
									'image' => 'image',
								),
								'default_value' => 'none',
							)
						),
						DS_Field::image(
							'decor_image',
							array(
								'label'             => 'Decor Image',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'decor_type',
											'operator'  => '==',
											'value'     => 'image',
										),
									),
								),
							)
						),
						DS_Field::select(
							'decor_class',
							array(
								'label'             => 'Decor Class',
								'choices'           => array( '' => 'none' ),
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'decor_type',
											'operator'  => '!=',
											'value'     => 'none',
										),
									),
								),
							)
						),
						DS_Field::true_false(
							'embed_image',
							array(
								'label'             => 'Embed Image?',
								'ui'                => 1,
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'decor_type',
											'operator'  => '==',
											'value'     => 'image',
										),
									),
								),
							)
						),
						DS_Field::button_group(
							'horizontal_pos',
							array(
								'label'             => 'Horizontal Position',
								'choices'           => array(
									'left'   => 'left',
									'center' => 'center',
									'right'  => 'right',
								),
								'default_value'     => 'left',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'decor_type',
											'operator'  => '!=',
											'value'     => 'none',
										),
									),
								),
							)
						),
						DS_Field::button_group(
							'vertical_pos',
							array(
								'label'             => 'Vertical Position',
								'choices'           => array(
									'top'    => 'top',
									'center' => 'center',
									'bottom' => 'bottom',
								),
								'default_value'     => 'top',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'decor_type',
											'operator'  => '!=',
											'value'     => 'none',
										),
									),
								),
							)
						),
					),
				),
				true,
			),
		];
	}

	/**
	 * Register ACF Composer fields for a specific block.
	 */
	private static function register_composer_fields( string $block_name, array $fields ): void {
		if ( class_exists( ACFComposer::class ) ) {
			ACFComposer::registerFieldGroup(
				[
					'name'       => "group_{$block_name}",
					'title'      => $block_name,
					'fields'     => $fields,
					'location'   => [
						[
							[
								'param'    => 'block',
								'operator' => '==',
								'value'    => "acf/{$block_name}",
							],
						],
					],
					'menu_order' => - 99,
				]
			);
		}
	}

	/**
	 * Updates a field's choices with custom classes from a block's configuration.
	 *
	 * @param array $field The field to update.
	 *
	 * @return array The updated field.
	 */
	public static function setup_block_custom_classes( array $field ): array {
		if ( empty( $field['ds_block_name'] ) ) {
			return $field;
		}

		$block = acf_get_block_type( "acf/{$field['ds_block_name']}" );
		if ( empty( $block ) || empty( $block['attributes']['dsConfig']['customClasses'] ) || ! is_array( $block['attributes']['dsConfig']['customClasses'] ) ) {
			return $field;
		}

		$field['choices'] = $block['attributes']['dsConfig']['customClasses'];

		return $field;
	}

	/**
	 * Updates a field's choices with custom classes from a block's configuration.
	 *
	 * @param array $field The field to update.
	 *
	 * @return array The updated field.
	 */
	public static function setup_block_custom_decorations( $field ) {
		if ( empty( $field['ds_block_name'] ) ) {
			return $field;
		}

		$block = acf_get_block_type( "acf/{$field['ds_block_name']}" );
		if ( empty( $block ) || empty( $block['attributes']['dsConfig']['decorClasses'] ) || ! is_array( $block['attributes']['dsConfig']['decorClasses'] ) ) {
			return false;
		}

		// decor_class acf field.
		$field['sub_fields'][3]['choices'] = $block['attributes']['dsConfig']['decorClasses'];

		return $field;
	}

	/**
	 * Render callback for custom blocks.
	 *
	 * @param array  $block The block attributes.
	 * @param string $content Block content.
	 * @param bool   $is_preview Whether the block is being previewed.
	 * @param int    $post_id Current post ID.
	 * @param object $wp_block Block object.
	 * @param array  $context Block context.
	 */
	public static function block_render_callback( $block, $content, $is_preview, $post_id, $wp_block, $context ): void {
		$module_config      = new DS_ModuleDefaultSettings();
		$block['className'] = $module_config->classNames;

		// Initialize module-specific settings.
		if ( 'ds-sliders' === $block['category'] ) {
			$module_slider = new DS_ModuleSlider();
		} elseif ( 'ds-3d-media' === $block['category'] ) {
			$module_image_spinner = new DS_ModuleImageSpinnerSettings();
		}

		if ( $is_preview ) {
			self::render_block_preview( $block );
		}

		$template_path = $block['path'] . '/view.php';

		// Output preview wrapper.
		echo $is_preview ? '<div class="block-preview-content">' : '';

		if ( is_readable( $template_path ) ) {
			include $template_path;
		} else {
			echo '<div>Template does not exist!</div>';
		}

		echo $is_preview ? '</div>' : '';
	}

	/**
	 * Renders a preview for a block in the WordPress theme.
	 *
	 * @param array $block Block data.
	 */
	private static function render_block_preview( array $block ): void {
		// Display block title.
		printf(
			'<div class="block-label">%s</div>',
			esc_html( $block['title'] ?? '' )
		);

		// Display block name.
		printf(
			'<div class="block-name preview-label ds-super-admin">%s</div>',
			esc_html( self::get_block_name( $block['name'] ?? '' ) )
		);

		// Get preview image URL.
		$preview_url = self::get_preview_url( $block );

		// Display preview image or message.
		if ( ! empty( $preview_url ) ) {
			printf(
				'<img class="preview-hover" src="%s" width="100%%" style="display: none;">',
				esc_url( $preview_url )
			);
		} else {
			echo '<div class="preview-hover" style="display:none;">Preview does not exist!</div>';
		}
	}

	/**
	 * Gets the preview image URL for a block.
	 *
	 * @param array $block Block data.
	 *
	 * @return string Preview image URL.
	 */
	private static function get_preview_url( array $block ): string {
		$preview_path = $block['path'] . '/preview.jpg';

		// Check if pattern name is available.
		if ( ! empty( $block['metadata']['patternName'] ) ) {
			$pattern_id  = absint( str_replace( 'core/block/', '', $block['metadata']['patternName'] ) );
			$preview_url = get_the_post_thumbnail_url( $pattern_id, 'medium' );
		} else {
			$preview_url = '';
		}

		// Check if preview image exists.
		if ( empty( $preview_url ) && is_readable( $preview_path ) ) {
			$preview_url = esc_url(
				str_replace(
					get_theme_file_path(),
					get_stylesheet_directory_uri(),
					$preview_path
				)
			);
		}

		return $preview_url;
	}

	/**
	 * Get the list of blocks.
	 */
	private static function get_blocks(): array {
		return get_option( 'ds_acf_blocks', [] );
	}
}

// Initialize the DS_ACF_Block_Manager.
DS_ACF_Block_Manager::init();
