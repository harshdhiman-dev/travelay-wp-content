<?php
/**
 * Removes possibility to deactivate plugins in admin panel
 *
 * @package DS_Theme
 */

if ( ! class_exists( 'DS_DefaultPlugins' ) ) {

	/**
	 * Class responible for declaring default plugins
	 */
	class DS_DefaultPlugins {
		/**
		 * Array of plugins which user will can't to disable
		 *
		 * @var array plugin_dir/plugin_main_class.php
		 */
		public $important_plugins = array(
			'advanced-custom-fields-pro/acf.php',
			'acf-field-group-composer/acf-field-group-composer.php',
			// 'woocommerce/woocommerce.php',
		);

		/**
		 * Default_Plugins constructor.
		 */
		public function __construct() {
			add_filter( 'plugin_action_links', array( $this, 'disable_plugin_deactivation' ), 10, 2 );
		}

		/**
		 * Adds class 'musthave_js' to required plugins
		 *
		 * @param array  $actions contains available actions.
		 * @param string $plugin_file contains plugin file.
		 *
		 * @return mixed
		 */
		public function disable_plugin_deactivation( $actions, $plugin_file ) {
			unset( $actions['edit'] );

			// phpcs:ignore
			if ( in_array( $plugin_file, $this->important_plugins ) ) {
				unset( $actions['deactivate'] );
				$actions['info'] = '<b class="musthave_js">' . __( 'Required for site', 'dstheme' ) . '</b>';
			}

			return $actions;
		}
	}

	new DS_DefaultPlugins();
}
