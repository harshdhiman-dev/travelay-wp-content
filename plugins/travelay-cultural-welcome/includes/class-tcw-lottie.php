<?php
/**
 * Lottie asset resolution: static .json, dotLottie .lottie, or generated fallback.
 *
 * @package TravelayCulturalWelcome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TCW_Lottie
 */
class TCW_Lottie {

	/**
	 * Absolute path to a slug's Lottie file.
	 *
	 * @param string $slug     Location slug.
	 * @param string $extension File extension without dot.
	 * @return string
	 */
	public static function get_file_path( $slug, $extension ) {
		$slug = sanitize_title( $slug );
		if ( '' === $slug ) {
			return '';
		}

		$extension = strtolower( preg_replace( '/[^a-z0-9]/', '', $extension ) );
		return TCW_PLUGIN_DIR . 'assets/avatars/lottie/' . $slug . '.' . $extension;
	}

	/**
	 * Public URL for a static Lottie JSON file.
	 *
	 * @param string $slug Location slug.
	 * @return string
	 */
	public static function get_static_json_url( $slug ) {
		$path = self::get_file_path( $slug, 'json' );
		if ( ! $path || ! is_readable( $path ) ) {
			return '';
		}

		return TCW_PLUGIN_URL . 'assets/avatars/lottie/' . sanitize_title( $slug ) . '.json';
	}

	/**
	 * Whether a custom Lottie asset exists (.json or .lottie).
	 *
	 * @param string $slug Location slug.
	 * @return bool
	 */
	public static function has_custom_file( $slug ) {
		return self::has_json_file( $slug ) || self::has_dotlottie_file( $slug );
	}

	/**
	 * @param string $slug Location slug.
	 * @return bool
	 */
	public static function has_json_file( $slug ) {
		$path = self::get_file_path( $slug, 'json' );
		return '' !== $path && is_readable( $path );
	}

	/**
	 * @param string $slug Location slug.
	 * @return bool
	 */
	public static function has_dotlottie_file( $slug ) {
		$path = self::get_file_path( $slug, 'lottie' );
		return '' !== $path && is_readable( $path );
	}

	/**
	 * Frontend / REST URL for Lottie animation data.
	 *
	 * @param string $slug Location slug.
	 * @return string
	 */
	public static function get_url( $slug ) {
		$slug = sanitize_title( $slug );

		self::materialize_json_from_dotlottie( $slug );

		$json_url = self::get_static_json_url( $slug );
		if ( $json_url ) {
			return $json_url;
		}

		// Generated animations only — custom assets should use static .json.
		return rest_url( 'tcw/v1/lottie/' . $slug );
	}

	/**
	 * Write {slug}.json from {slug}.lottie so lottie-web loads a clean static file.
	 *
	 * @param string $slug Location slug.
	 * @return bool Whether a readable .json exists afterward.
	 */
	public static function materialize_json_from_dotlottie( $slug ) {
		$slug = sanitize_title( $slug );
		if ( '' === $slug || self::has_json_file( $slug ) || ! self::has_dotlottie_file( $slug ) ) {
			return self::has_json_file( $slug );
		}

		$data = self::extract_dotlottie_json( self::get_file_path( $slug, 'lottie' ) );
		if ( ! is_array( $data ) ) {
			return false;
		}

		$path = self::get_file_path( $slug, 'json' );
		$dir  = dirname( $path );
		if ( ! is_dir( $dir ) || ! is_writable( $dir ) ) {
			return false;
		}

		$lottie_mtime = (int) filemtime( self::get_file_path( $slug, 'lottie' ) );
		if ( self::has_json_file( $slug ) ) {
			$json_mtime = (int) filemtime( $path );
			if ( $json_mtime >= $lottie_mtime ) {
				return true;
			}
		}

		$written = file_put_contents( $path, wp_json_encode( $data ), LOCK_EX );
		return false !== $written && is_readable( $path );
	}

	/**
	 * Resolve animation JSON for a slug.
	 *
	 * @param string $slug Location slug.
	 * @return array<string, mixed>|null
	 */
	public static function get_animation_data( $slug ) {
		$slug = sanitize_title( $slug );
		if ( '' === $slug ) {
			return null;
		}

		if ( self::has_json_file( $slug ) ) {
			$raw = file_get_contents( self::get_file_path( $slug, 'json' ) );
			if ( false === $raw ) {
				return null;
			}
			$data = json_decode( $raw, true );
			return is_array( $data ) ? $data : null;
		}

		if ( self::materialize_json_from_dotlottie( $slug ) && self::has_json_file( $slug ) ) {
			$raw = file_get_contents( self::get_file_path( $slug, 'json' ) );
			if ( false !== $raw ) {
				$data = json_decode( $raw, true );
				if ( is_array( $data ) ) {
					return $data;
				}
			}
		}

		return TCW_Lottie_Generator::generate( $slug );
	}

	/**
	 * Extract the primary animation JSON from a .lottie (dotLottie) zip archive.
	 *
	 * @param string $path Absolute file path.
	 * @return array<string, mixed>|null
	 */
	public static function extract_dotlottie_json( $path ) {
		if ( ! is_readable( $path ) || ! class_exists( 'ZipArchive' ) ) {
			return null;
		}

		$zip = new ZipArchive();
		if ( true !== $zip->open( $path ) ) {
			return null;
		}

		$manifest_animation = '';
		$manifest_raw       = $zip->getFromName( 'manifest.json' );
		if ( $manifest_raw ) {
			$manifest = json_decode( $manifest_raw, true );
			if ( is_array( $manifest ) && ! empty( $manifest['animations'][0]['id'] ) ) {
				$manifest_animation = sanitize_file_name( (string) $manifest['animations'][0]['id'] );
			}
		}

		$candidates = array();
		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$name = (string) $zip->getNameIndex( $i );
			if ( preg_match( '#^animations/.+\.json$#i', $name ) ) {
				$candidates[] = $name;
			}
		}

		if ( empty( $candidates ) ) {
			$zip->close();
			return null;
		}

		$chosen = $candidates[0];
		if ( $manifest_animation ) {
			foreach ( $candidates as $candidate ) {
				if ( false !== strpos( $candidate, $manifest_animation ) ) {
					$chosen = $candidate;
					break;
				}
			}
		}

		$content = $zip->getFromName( $chosen );
		$zip->close();

		if ( false === $content ) {
			return null;
		}

		$data = json_decode( $content, true );
		return is_array( $data ) ? $data : null;
	}
}
