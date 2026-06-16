<?php
/**
 * Custom class that provides methods for enqueue scripts and styles with Vite bundler
 *
 * @since custom 1.5
 * @package DS_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'DS_ViteAssets' ) ) {
	class DS_ViteAssets {
		/**
		 * @var array The decoded manifest JSON data.
		 */
		private static array $manifest;

		/**
		 * @var string The path to the manifest JSON file.
		 */
		private static string $manifestPath;

		/**
		 * Constructor
		 */
		public function __construct() {
			self::$manifestPath = get_template_directory() . '/assets/_dist/.vite/manifest.json';
			$this->load_manifest();
			$this->define_globals();
		}

		/**
		 * Loads the manifest file and decodes its contents.
		 */
		private function load_manifest(): void {
			if ( file_exists( self::$manifestPath ) ) {
				self::$manifest = json_decode( file_get_contents( self::$manifestPath ), true ); //phpcs:ignore
			} else {
				self::$manifest = [];
			}
		}

		/**
		 * Get the file path from the manifest using a given key.
		 *
		 * @param string $key The key to search in the manifest.
		 *
		 * @return string|null The file path, or null if not found.
		 */
		public static function get_entry_from_manifest( string $key ): ?string {
			return isset( self::$manifest[ $key ] ) ? self::$manifest[ $key ]['file'] : null;
		}

		/**
		 * Define global constants based on the environment configuration file.
		 *
		 * @return void
		 */
		private function define_globals(): void {
			// Define the path to the .ds-dev-mode file
			$dev_mode_file_path = get_template_directory() . '/.ds-dev-mode';
			// Define the path to the .env file
			$env_file_path = get_template_directory() . '/.env';
			// Check if the .ds-dev-mode file exists
			$is_dev_env = file_exists( $dev_mode_file_path );
			// Check if the .env file exists
			if ( file_exists( $env_file_path ) ) {
				$env = parse_ini_file( get_template_directory() . '/.env' );
			}
			define( 'DS_DEV_ENV', ( $is_dev_env && $env ) ); // go to dev mode only if both .env and .ds-dev-mode exists in the folder
			define( 'VITE_PORT', $env['VITE_PORT'] ?? 3000 );
			define( 'VITE_THEME_PATH', $env['VITE_THEME_PATH'] ?? '' );
		}

		/**
		 * Outputs a script tag for Vite HMR client.
		 */
		public static function add_vite_dev_scripts(): void {
			if ( defined( 'DS_DEV_ENV' ) && DS_DEV_ENV ) {
				add_action(
					'wp_head',
					function () {
						echo '<script type="module" crossorigin src="http://localhost:' . VITE_PORT . VITE_THEME_PATH . '/assets/_src/@vite/client"></script>'; //phpcs:ignore
					}
				);
			}
		}

		/**
		 * Register a Vite development asset.
		 *
		 * @param string $key The key used to retrieve the asset URL.
		 *
		 * @return void
		 */
		public static function register_vite_dev_asset( $key ): void {
			$file = 'http://localhost:' . VITE_PORT . VITE_THEME_PATH . '/assets/_src/' . $key;
			wp_register_script_module( $key, $file, [], null );
		}

		/**
		 * Enqueue a Vite development asset.
		 *
		 * @param string $key The key used to retrieve the asset URL.
		 *
		 * @return void
		 */
		public static function enqueue_vite_dev_asset( $key ): void {
			$file = 'http://localhost:' . VITE_PORT . VITE_THEME_PATH . '/assets/_src/' . $key;
			wp_enqueue_script_module( $key, $file, [], null, [ 'in_footer' => true ] );
		}

		/**
		 * Register a script from the manifest.
		 *
		 * @param string $key The key used to retrieve the script from the manifest.
		 *
		 * @return bool True if the script was registered, false otherwise.
		 */
		public static function register_script( string $key ): bool {
			if ( defined( 'DS_DEV_ENV' ) && DS_DEV_ENV ) {
				self::register_vite_dev_asset( $key );

				return true;
			} else {
				$file = self::get_entry_from_manifest( $key );
				if ( $file ) {
					wp_register_script_module( $key, get_template_directory_uri() . '/assets/_dist/' . $file, [], null );

					return true;
				}

				return false;
			}
		}

		/**
		 * Enqueue a script from the manifest.
		 *
		 * @param string $key The key used to retrieve the script from the manifest.
		 *
		 * @return bool True if the script was enqueued, false otherwise.
		 */
		public static function enqueue_script( string $key ): bool {
			if ( defined( 'DS_DEV_ENV' ) && DS_DEV_ENV ) {
				self::enqueue_vite_dev_asset( $key );

				return true;
			} else {
				$file = self::get_entry_from_manifest( $key );
				if ( $file ) {
					wp_enqueue_script_module( $key, get_template_directory_uri() . '/assets/_dist/' . $file, [], null, [ 'in_footer' => true ] );

					return true;
				}

				return false;
			}
		}

		/**
		 * Register a style from the manifest.
		 *
		 * @param string $key The key used to retrieve the style from the manifest.
		 *
		 * @return bool True if the style was registered, false otherwise.
		 */
		public static function enqueue_style( string $key ): bool {
			if ( defined( 'DS_DEV_ENV' ) && DS_DEV_ENV ) {
				self::enqueue_vite_dev_asset( $key );

				return true;
			} else {
				$file = self::get_entry_from_manifest( $key );
				if ( $file ) {
					wp_enqueue_style( $key, get_template_directory_uri() . '/assets/_dist/' . $file, [], '1.0.0' );

					return true;
				}

				return false;
			}
		}

		/**
		 * Enqueue a style from the manifest.
		 *
		 * @param string $key The key used to retrieve the style from the manifest.
		 *
		 * @return bool True if the style was enqueued, false otherwise.
		 */
		public static function register_style( string $key ): bool {
			if ( defined( 'DS_DEV_ENV' ) && DS_DEV_ENV ) {
				self::register_vite_dev_asset( $key );

				return true;
			} else {
				$file = self::get_entry_from_manifest( $key );
				if ( $file ) {
					wp_register_style( $key, get_template_directory_uri() . '/assets/_dist/' . $file, [], '1.0.0' );

					return true;
				}

				return false;
			}
		}
	}

	new DS_ViteAssets();
}
