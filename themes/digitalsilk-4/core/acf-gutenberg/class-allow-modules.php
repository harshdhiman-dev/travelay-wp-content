<?php
/**
 * DS_AllowModules class.
 *
 * Enables or disables DSMP Gutenberg modules in the WordPress admin panel.
 *
 * @package    DSMP
 * @author     Your Name
 * @since      1.0.0
 */

if ( ! class_exists( 'DS_AllowModules' ) ) {
	class DS_AllowModules {

		/**
		 * List of all ACF block.json DSMP modules.
		 *
		 * @var array $dsmp_modules
		 */
		private $dsmp_modules = array();

		/**
		 * List of all ACF DSMP moduels
		 *
		 * @var array $dsmp_modules_old
		 */
		private array $dsmp_modules_old = array();

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->get_blocks_from_theme();
			add_action(
				'acf/init',
				function () {
					$this->add_option_page_fields();
					$this->get_allowed_modules();
				}
			);
			add_action( 'acf/options_page/save', array( $this, 'save_allowed_modules' ), 10, 2 );

			$this->init_modules();
		}

		/**
		 * Retrieves the allowed modules for the theme from the global scope.
		 *
		 * @return void
		 */
		private function get_allowed_modules() {
			global $allowed_dsmp_modules;
			$allowed_dsmp_modules = get_field( 'theme_allowed_modules_old', 'options' );
		}

		/**
		 * Retrieves block directories from specific theme module locations.
		 *
		 * This method looks into predefined directories within the theme structure
		 * to find block.json module files. It gathers directories matching the
		 * specified glob patterns and updates the internal `dsmp_modules` property
		 * with the located blocks.
		 *
		 * @return void
		 */
		private function get_blocks_from_theme() {
			// Locations of block.json modules files
			$dirs = [
				'acf/**',
			];

			$blocks     = [];
			$theme_path = get_theme_file_path();
			foreach ( $dirs as $dir ) {
				$block_paths          = glob( get_template_directory() . '/modules/' . $dir . '/*', GLOB_ONLYDIR );
				$block_relative_paths = array_map( fn( $path ) => str_replace( $theme_path, '', $path ), $block_paths );

				$blocks = array_merge( $blocks, $block_relative_paths );
			}

			if ( ! empty( $blocks ) ) {
				$this->dsmp_modules = array_combine( $blocks, $blocks );
			}
		}

		/**
		 * Glob and init all DSMP ACF modules
		 */
		private function init_modules() {
			// loop for new folder structure
			foreach ( glob( get_template_directory() . '/modules/acf-old/**/*', GLOB_ONLYDIR ) as $file ) {
				if ( $file ) {
					require_once $file . '/class.php';

					$module_controller = 'DS_Module_' . str_replace( '-', '_', basename( $file ) );
					if ( class_exists( $module_controller ) ) {
						$module                                  = new $module_controller();
						$this->dsmp_modules_old[ $module->name ] = $module->title . ' - ' . $module->name;
						$module->init();
					}
				}
			}
		}

		/**
		 * ACF group for options page with all DSMP ACF modules.
		 */
		public function add_option_page_fields() {
			if ( function_exists( 'acf_add_local_field_group' ) ) {
				acf_add_local_field_group(
					array(
						'key'                   => 'group_6346b3f8725ae',
						'title'                 => 'DSMP Moduels',
						'fields'                => array(
							array(
								'key'               => 'field_6346b46a937d8o',
								'label'             => 'DSMP Modules',
								'name'              => 'theme_allowed_modules_old',
								'type'              => 'checkbox',
								'instructions'      => 'List of DSMP modules to enable for use in Gutenberg',
								'required'          => 0,
								'conditional_logic' => 0,
								'wrapper'           => array(
									'width' => '',
									'class' => '',
									'id'    => '',
								),
								'choices'           => $this->dsmp_modules_old,
								'allow_custom'      => 0,
								'default_value'     => array(),
								'layout'            => 'vertical',
								'toggle'            => 1,
								'return_format'     => 'value',
								'save_custom'       => 0,
							),
							array(
								'key'               => 'field_6346b46a937d8',
								'label'             => 'DSMP Modules',
								'name'              => 'theme_allowed_modules',
								'type'              => 'checkbox',
								'instructions'      => 'List of DSMP modules to enable for use in Gutenberg',
								'required'          => 0,
								'conditional_logic' => 0,
								'wrapper'           => array(
									'width' => '',
									'class' => '',
									'id'    => '',
								),
								'choices'           => $this->dsmp_modules,
								'allow_custom'      => 0,
								'default_value'     => array(),
								'layout'            => 'vertical',
								'toggle'            => 1,
								'return_format'     => 'value',
								'save_custom'       => 0,
							),
						),
						'location'              => array(
							array(
								array(
									'param'    => 'options_page',
									'operator' => '==',
									'value'    => 'allowed-modules',
								),
							),
						),
						'menu_order'            => 0,
						'position'              => 'normal',
						'style'                 => 'seamless',
						'label_placement'       => 'top',
						'instruction_placement' => 'label',
						'hide_on_screen'        => '',
						'active'                => true,
						'description'           => '',
						'show_in_rest'          => 0,
					)
				);
			}
		}

		/**
		 * Save allowed modules.
		 *
		 * @param int    $post_id   Post ID.
		 * @param string $menu_slug Menu slug.
		 */
		public function save_allowed_modules( $post_id, $menu_slug ) {
			if ( 'allowed-modules' !== $menu_slug ) {
				return;
			}

			$theme_allowed_modules = get_field( 'theme_allowed_modules', $post_id );
			update_option( 'ds_acf_blocks', $theme_allowed_modules );
		}
	}

	new DS_AllowModules();
}
