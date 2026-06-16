<?php
/**
 * Outputs client lock data to the admin screen via a dummy script.
 *
 * @package DS_Theme
 */

if ( ! class_exists( 'Clients_Lock' ) ) {
	/**
	 * Class Clients_Lock
	 */
	class Clients_Lock {

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_client_lock_data' ] );
		}

		/**
		 * Enqueue a dummy script and output ds_is_super_admin() result inline.
		 *
		 * @return void
		 */
		public function enqueue_admin_client_lock_data() {
			$handle = 'ds-client-lock';

			// Register and enqueue a dummy script with no src.
			wp_register_script( $handle, '', [], null, true ); // phpcs:ignore
			wp_enqueue_script( $handle );

			// Inline data: assign result of ds_is_super_admin() to window.ds.clientLock.
			wp_add_inline_script(
				$handle,
				'window.ds = window.ds || {};' . PHP_EOL .
				'window.ds.isSuperAdmin = ' . wp_json_encode( ds_is_super_admin() ) . ';',
				'before'
			);
		}
	}

	// Initialize the class.
	new Clients_Lock();
}
