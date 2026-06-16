<?php
//phpcs:ignoreFile
/**
 * Allow synchronizing acf-json automatically
 *
 * @package DS_Theme
 */
class DS_acf_json_autosync {

	protected string $json_dir;

	protected array $synced_groups = array();

	public function __construct() {

		if ( $this->init_json_dir() ) {
			$this->autosync_json_groups();
		}
	}

	protected function init_json_dir(): bool {
		$this->json_dir = trailingslashit( get_template_directory() ) . 'acf-json';

		if ( file_exists( $this->json_dir ) ) {
			return true;
		}

		return false;
	}

	protected function autosync_json_groups(): bool {
		$sync = $this->get_json_groups();

		if ( ! $sync ) {
			return false;
		}

		// disable filters to ensure ACF loads raw data from DB.
		acf_disable_filters();
		acf_enable_filter( 'local' );

		// prevent a new JSON file being created
		acf_update_setting( 'json', false );

		if ( ! empty( $sync ) ) {
			$files         = acf_get_local_json_files();
			$synced_groups = array();
			foreach ( $sync as $key => $v ) {
				$local_field_group       = json_decode( file_get_contents( $files[ $key ] ), true );
				$local_field_group['ID'] = $v['ID'];
				$result                  = acf_import_field_group( $local_field_group );
				$synced_groups[]         = $result['ID'];
			}

			if ( ! empty( $synced_groups ) ) {
				$this->synced_groups = $synced_groups;

				wp_redirect( admin_url( 'edit.php?post_type=acf-field-group&acfsynccomplete=' . implode( ',', $synced_groups ) ) );
				exit;
			}
		}

		return true;
	}

	protected function get_json_groups(): array {
		if ( count( glob( trailingslashit( $this->json_dir ) . '/*.json' ) ) > 0 ) {
			$groups         = acf_get_field_groups();
			$groups_to_sync = array();

			if ( ! empty( $groups ) ) {
				foreach ( $groups as $group ) {
					$local    = acf_maybe_get( $group, 'local', false );
					$modified = acf_maybe_get( $group, 'modified', 0 );
					// $private  = acf_maybe_get( $group, 'private', false );

					// ignore all instead json.
					if ( $local === 'json' ) {
						if ( ! $group['ID'] || ( $modified && $modified > get_post_modified_time( 'U', true, $group['ID'], true ) ) ) {
							$groups_to_sync[ $group['key'] ] = $group;
						}
					}
				}
			}

			return $groups_to_sync;
		}

		return array();
	}
}

if ( class_exists( 'DS_acf_json_autosync' ) && class_exists( 'acf' ) ) {
	// run script only for admin panel
	add_action(
		'admin_init',
		function () {
			new DS_acf_json_autosync();
		}
	);
}
