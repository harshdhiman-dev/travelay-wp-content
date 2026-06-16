<?php
/**
 * Class generates module template data-attributes on render_callback level for image spinner modules
 */

// phpcs:ignoreFile
class DS_ModuleImageSpinnerSettings {
	/**
	 * Contains all main spinner settings from ACF
	 */
	private array $settings = array();

	/**
	 * Contains all main spinner data attributes
	 */
	public string $data_attributes = '';

	/**
	 * Contains data attributes for spinner controls
	 */
	public string $controlsDataAttributes = '';

	/**
	 * Contains spinner hotspots data attributes
	 */
	public string $hotspotsDataAttributes = '';

	/**
	 * Contains additional main slider classes
	 *
	 * @var string
	 */
	public string $classNames = '';

	public function __construct() {
		$this->image_spinner_assets();
		$mainSettings     = get_field( 'module_image_spinner_settings' );
		$controlsSettings = get_field( 'module_image_spinner_controls' );
		$hotspotsSettings = get_field( 'module_image_spinner_hotspots' );

		if ( empty( $mainSettings ) ) {
			return;
		}

		$this->settings               = $mainSettings;
		$this->data_attributes        = $this->acf_build_spinner_data_attributes( $mainSettings );
		$this->controlsDataAttributes = $this->acf_build_spinner_controls_data_attributes( $controlsSettings );
		$this->hotspotsDataAttributes = $this->acf_build_spinner_hotspots_data_attributes( $hotspotsSettings );

		$this->classNames = $this->acf_build_spinner_class_names();

		// add_action( 'enqueue_block_editor_assets', array( $this, 'image_spinner_editor_assets' ) );
	}

	/**
	 * Enqueue editor assets
	 */
	/*
	public function image_spinner_editor_assets() {
		wp_enqueue_script( 'ds-image-spinner-admin-js', get_template_directory_uri() . '/admin/js/image-spinner-admin.js', array('image-spritespin-js'), filemtime( get_template_directory() . '/admin/js/image-spinner-admin.js' ), true );
	}
	*/
	/**
	 * Enqueue assets
	 */
	public function image_spinner_assets() {
		/**
		 * Source with docs & examples: https://spritespin.ginie.eu/
		 *
		 * Note 1: original script was edited near line 1858 (mark: DS_NOTE) - drawImage() didn't work well with resizing, until some arguments were omitted.
		 *
		 * Note 2: some useful options/methods can be found in the source code, even though not mentioned in docs.
		 */

		wp_enqueue_script( 'image-spritespin-js', get_template_directory_uri() . '/assets/vendors/spritespin/spritespin.js', array( 'jquery' ), '1.0', false );
	}

	public function get_setting( string $key = '' ) {
		return $this->settings[ $key ] ?? false;
	}

	/**
	 * Build all data attributes based on chosen settings.
	 * Note: if an acf field should be printed as data attribute it should contain a prefix of 'data_' in the key name
	 *       - ex: If a data attribute with name of 'example' should be present the acf key name should be 'data_example'
	 */

	private function acf_build_spinner_data_attributes( $settings ): string {
		$data_attributes = '';

		if ( empty( $settings ) ) {
			return $data_attributes;
		}

		foreach ( $settings as $key => $item ) {
			if ( empty( $item ) || ! $this->is_data_attribute( $key, $item ) ) {
				continue;
			}

			if ( is_array( $item ) ) {
				foreach ( $item as $itemKey => $itemValue ) {
					if ( strpos( $itemKey, 'enabled' ) !== false && $itemValue !== true ) {
						break;
					}

					if ( empty( $itemValue ) || ! $this->is_data_attribute( $itemKey, $itemValue ) ) {
						continue;
					}

					$data_attributes .= ' data-spinner-'
										. ( strpos( $itemKey, 'enabled' ) === false
							? "{$this->clean_data_key($key)}-{$this->clean_data_key($itemKey)}='{$itemValue}'"
							: "{$this->clean_data_key($key)}='true'" );
				}
				continue;
			}

			if ( $key === 'data_folder' ) {
				$library_path     = site_url( '/', 'relative' ) . 'wp-content/uploads';
				$data_attributes .= " data-spinner-path='{$library_path}/{$item}/'";
			} else {
				$itemValue        = $item === true ? 'true' : $item;
				$data_attributes .= " data-spinner-{$this->clean_data_key($key)}='{$itemValue}'";
			}
		}

		return trim( $data_attributes );
	}

	/**
	 * Build constrols data attributes
	 */

	private function acf_build_spinner_controls_data_attributes( $settings ): string {
		$data_attributes = '';

		if ( empty( $settings ) ) {
			return $data_attributes;
		}

		foreach ( $settings as $key => $item ) {
			if ( empty( $item ) || ! $this->is_data_attribute( $key, $item ) ) {
				continue;
			}

			if ( is_array( $item ) ) {
				foreach ( $item as $itemKey => $itemValue ) {
					if ( strpos( $itemKey, 'enabled' ) !== false && $itemValue !== true ) {
						break;
					}

					if ( empty( $itemValue ) || ! $this->is_data_attribute( $itemKey, $itemValue ) ) {
						continue;
					}

					$data_attributes .= ' data-ctrl-'
										. ( strpos( $itemKey, 'enabled' ) === false
							? "{$this->clean_data_key($key)}-{$this->clean_data_key($itemKey)}='{$itemValue}'"
							: "{$this->clean_data_key($key)}='true'" );
				}
				continue;
			}

			$itemValue        = $item === true ? 'true' : $item;
			$data_attributes .= " data-ctrl-{$this->clean_data_key($key)}='{$itemValue}'";
		}

		return trim( $data_attributes );
	}

	/**
	 * Build hotspots data attributes
	 */

	private function acf_build_spinner_hotspots_data_attributes( $settings ): string {
		$data_attributes = '';

		if ( empty( $settings ) ) {
			return $data_attributes;
		}

		$hs_frames = array();

		foreach ( $settings as $key => $item ) {
			if ( empty( $item ) ) {
				continue;
			}

			$hs_frames[] = $item['hotspot_frame'];
		}

		$frames_list = implode( ',', $hs_frames );

		$data_attributes .= " data-hotspots-frames='{$frames_list}'";

		return trim( $data_attributes );
	}

	private function is_data_attribute( $key, $item ): bool {
		return ! ( strpos( $key, 'data_' ) === false );
	}

	/**
	 * Remove 'data_' from start of acf key name which is used to recognize if a element is a data attribute
	 */
	private function clean_data_key( $key ): string {
		return str_replace( 'data_', '', $key );
	}


	/**
	 * Generates additional class names based on options for main slider
	 *
	 * @return string
	 */

	private function acf_build_spinner_class_names(): string {
		$classNames = '';

		if ( ! empty( $this->settings ) ) {
			$autoanimate = $this->get_setting( 'data_autoanimate' );

			if ( ! empty( $autoanimate ) ) {
				$classNames .= ' is-playing';
			}
		}

		return trim( $classNames );
	}

	/**
	 * Get image path for given frame
	 *
	 * @return string
	 */
	public static function get_image_path_by_frame( $frame, $settings ): string {
		$library_path = site_url( '/', 'relative' ) . 'wp-content/uploads';
		$image_folder = $settings['data_folder'] ?: '';
		$image_prefix = $settings['data_prefix'] ?: '';
		$image_digits = $settings['data_digits'] ?: '';
		$image_ext    = $settings['data_ext'] ?: '';
		$image_count  = $settings['data_count'] ?: '';

		$frame_digits   = ( abs( $frame ) > 0 ) ? (int) log10( abs( $frame ) ) + 1 : 1;
		$frame_str_diff = $image_digits - $frame_digits;
		$frame_str_pre  = '';

		while ( $frame_str_diff > 0 ) :
			$frame_str_pre .= '0';
			--$frame_str_diff;
		endwhile;

		$frame_str = $frame_str_pre . $frame;

		$frame_img_path = "{$library_path}/{$image_folder}/{$image_prefix}{$frame_str}.{$image_ext}";

		return $frame_img_path;
	}
}
