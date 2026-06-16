<?php
/**
 * Class generates module template data-attributes on render_callback level for slider modules
 */
// phpcs:ignoreFile
class DS_ModuleSlider {
	/**
	 * Contains all main slider settings from ACF
	 */
	private array $settings = array();

	/**
	 * Contains all main slider data attributes
	 */
	public string $data_attributes = '';

	/**
	 * Contains additional main slider classes
	 *
	 * @var string
	 */
	public string $classNames = '';

	/**
	 * Contains all navigation slider settings from ACF
	 */
	private array $navSettings = array();

	/**
	 * Contains all navigation slider data attributes
	 */
	public string $navDataAttributes = '';

	/**
	 * Contains additional navigation slider classes
	 *
	 * @var string
	 */
	public string $navClassNames = '';

	public function __construct() {
		$mainSettings = get_field( 'module_slider_settings' );
		if ( empty( $mainSettings ) ) {
			return;
		}
		$navSettings = get_field( 'module_slider_nav_settings' ) ?: array();

		$this->navSettings       = $navSettings;
		$this->navDataAttributes = $this->acf_build_data_attributes( $navSettings );
		$this->navClassNames     = $this->acf_build_nav_slider_class_names();

		$this->settings        = $mainSettings;
		$this->data_attributes = $this->acf_build_data_attributes( $mainSettings );
		$this->classNames      = $this->acf_build_main_slider_class_names();
	}

	public function get_setting( string $key = '' ) {
		return $this->settings[ $key ] ?? false;
	}

	public function get_nav_setting( string $key = '' ) {
		return $this->navSettings[ $key ] ?? false;
	}

	/**
	 * Build all data attributes based on chosen settings.
	 * Note: if an acf field should be printed as data attribute it should contain a prefix of 'data_' in the key name
	 *       - ex: If a data attribute with name of 'example' should be present the acf key name should be 'data_example'
	 */
	private function acf_build_data_attributes( $settings ): string {
		$data_attributes = '';

		if ( empty( $settings ) ) {
			return $data_attributes;
		}

		foreach ( $settings as $key => $item ) {
			if ( empty( $item ) || ! $this->is_data_attribute( $key, $item ) || ( $key === 'data_thumbs' && $item === 'tabbed' ) ) {
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
					$data_attributes .= ' data-slider-'
										. ( strpos( $itemKey, 'enabled' ) === false
							? "{$this->clean_data_key($key)}-{$this->clean_data_key($itemKey)}='{$itemValue}'"
							: "{$this->clean_data_key($key)}='true'" );
				}
				continue;
			}

			$itemValue        = $item === true || ( $key === 'data_thumbs' && $item === 'thumbs' ) ? 'true' : $item;
			$data_attributes .= " data-slider-{$this->clean_data_key($key)}='{$itemValue}'";
		}

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
	private function acf_build_main_slider_class_names(): string {
		$classNames = '';

		if ( ! empty( $this->settings ) ) {
			$tabbedNavigation = $this->get_setting( 'tabbed_navigation' );
			$navigation       = $this->get_setting( 'data_thumbs' );
			if ( ( ! empty( $tabbedNavigation ) ) || $navigation === 'tabbed' ) {
				$classNames .= ' has-tabs';
			}
		}

		return trim( $classNames );
	}

	/**
	 * Generates additional class names based on options for navigation slider
	 *
	 * @return string
	 */
	private function acf_build_nav_slider_class_names(): string {
		$classNames = '';

		if ( ! empty( $this->navSettings ) ) {
			if ( ! empty( $this->get_nav_setting( 'data_circular-align-items' ) ) ) {
				$classNames .= " items-{$this->get_nav_setting('data_circular-align-items')}";
			}
		}

		return trim( $classNames );
	}
}
