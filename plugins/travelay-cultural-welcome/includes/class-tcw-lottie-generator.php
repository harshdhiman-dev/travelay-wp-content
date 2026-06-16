<?php
/**
 * Generates valid Lottie JSON animations per country.
 *
 * @package TravelayCulturalWelcome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TCW_Lottie_Generator
 */
class TCW_Lottie_Generator {

	/**
	 * Generate Lottie animation data.
	 *
	 * @param string $slug Country slug.
	 * @return array<string, mixed>|null
	 */
	public static function generate( $slug ) {
		$slug = sanitize_title( $slug );
		if ( 'all-country-test' === $slug ) {
			$slug = 'india';
		}

		$profile = TCW_Profile::get_by_slug( $slug, 'country' );
		$gesture = $profile ? $profile['gesture'] : 'wave';
		$palette = TCW_Gestures::palette_for_slug( $slug );

		$primary   = self::hex_to_rgb( $palette['primary'] );
		$secondary = self::hex_to_rgb( $palette['secondary'] );
		$accent    = self::hex_to_rgb( $palette['accent'] );

		$body_kf  = self::body_keyframes( $gesture );
		$arm_kf   = self::arm_keyframes( $gesture );
		$head_kf  = self::head_keyframes( $gesture );

		return array(
			'v'      => '5.9.0',
			'fr'     => 60,
			'ip'     => 0,
			'op'     => 180,
			'w'      => 200,
			'h'      => 260,
			'nm'     => 'TCW ' . $slug,
			'ddd'    => 0,
			'assets' => array(),
			'layers' => array(
				self::ellipse_layer( 'Shadow', 0, 100, 248, 52, array( 0.06, 0.09, 0.15 ), array( 'k' => array() ), 100, 248 ),
				self::shape_layer( 'Torso', 1, $primary, self::torso_path(), $body_kf, 100, 155 ),
				self::shape_layer( 'Collar', 2, $secondary, self::collar_path(), array( 'k' => array() ), 100, 138 ),
				self::shape_layer( 'Arm', 3, $primary, self::arm_path(), $arm_kf, 145, 145 ),
				self::ellipse_layer( 'Hand', 4, 168, 108, 12, $primary, $arm_kf, 168, 108 ),
				self::ellipse_layer( 'Head', 5, 100, 78, 34, array( 0.94, 0.78, 0.65 ), $head_kf, 100, 78 ),
				self::ellipse_layer( 'Hair', 6, 100, 58, 36, array( 0.15, 0.12, 0.1 ), $head_kf, 100, 58 ),
				self::ellipse_layer( 'Accent', 7, 100, 175, 4, $accent, array( 'k' => array() ), 100, 175 ),
			),
		);
	}

	/**
	 * @param string $hex Hex color.
	 * @return float[]
	 */
	private static function hex_to_rgb( $hex ) {
		$hex = ltrim( $hex, '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		return array(
			hexdec( substr( $hex, 0, 2 ) ) / 255,
			hexdec( substr( $hex, 2, 2 ) ) / 255,
			hexdec( substr( $hex, 4, 2 ) ) / 255,
		);
	}

	/**
	 * @param string $gesture Gesture.
	 * @return array<string, mixed>
	 */
	private static function body_keyframes( $gesture ) {
		if ( 'bow' === $gesture || 'namaste' === $gesture ) {
			return array(
				'k' => array(
					array( 't' => 0, 's' => array( 0 ), 'e' => array( 0 ) ),
					array( 't' => 30, 's' => array( 0 ), 'e' => array( 14 ) ),
					array( 't' => 90, 's' => array( 14 ), 'e' => array( 14 ) ),
					array( 't' => 150, 's' => array( 14 ), 'e' => array( 0 ) ),
					array( 't' => 180, 's' => array( 0 ) ),
				),
			);
		}
		return array( 'k' => array() );
	}

	/**
	 * @param string $gesture Gesture.
	 * @return array<string, mixed>
	 */
	private static function arm_keyframes( $gesture ) {
		if ( 'wave' === $gesture ) {
			return array(
				'k' => array(
					array( 't' => 0, 's' => array( 0 ), 'e' => array( 18 ) ),
					array( 't' => 45, 's' => array( 18 ), 'e' => array( -8 ) ),
					array( 't' => 90, 's' => array( -8 ), 'e' => array( 18 ) ),
					array( 't' => 135, 's' => array( 18 ), 'e' => array( 0 ) ),
					array( 't' => 180, 's' => array( 0 ) ),
				),
			);
		}
		if ( 'open_welcome' === $gesture ) {
			return array(
				'k' => array(
					array( 't' => 0, 's' => array( -20 ), 'e' => array( 12 ) ),
					array( 't' => 90, 's' => array( 12 ), 'e' => array( -20 ) ),
					array( 't' => 180, 's' => array( -20 ) ),
				),
			);
		}
		return array( 'k' => array() );
	}

	/**
	 * @param string $gesture Gesture.
	 * @return array<string, mixed>
	 */
	private static function head_keyframes( $gesture ) {
		if ( 'nod' === $gesture ) {
			return array(
				'k' => array(
					array( 't' => 0, 's' => array( 0 ), 'e' => array( 10 ) ),
					array( 't' => 40, 's' => array( 10 ), 'e' => array( 0 ) ),
					array( 't' => 80, 's' => array( 0 ), 'e' => array( 10 ) ),
					array( 't' => 120, 's' => array( 10 ), 'e' => array( 0 ) ),
					array( 't' => 180, 's' => array( 0 ) ),
				),
			);
		}
		return self::body_keyframes( $gesture );
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private static function torso_path() {
		return array(
			array( 'ty' => 'rc', 'd' => 1, 's' => array( 'a' => 0, 'k' => array( 70, 90, 0 ) ) ),
			array( 'ty' => 'fl', 'c' => array( 'a' => 0, 'k' => array( 1, 1, 1, 1 ) ) ),
		);
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private static function collar_path() {
		return array(
			array( 'ty' => 'rc', 'd' => 1, 's' => array( 'a' => 0, 'k' => array( 28, 12, 0 ) ) ),
			array( 'ty' => 'fl', 'c' => array( 'a' => 0, 'k' => array( 1, 1, 1, 1 ) ) ),
		);
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private static function arm_path() {
		return array(
			array( 'ty' => 'rc', 'd' => 1, 's' => array( 'a' => 0, 'k' => array( 8, 40, 4 ) ) ),
			array( 'ty' => 'fl', 'c' => array( 'a' => 0, 'k' => array( 1, 1, 1, 1 ) ) ),
		);
	}

	/**
	 * @param string               $name  Layer name.
	 * @param int                  $ind   Index.
	 * @param float[]              $color RGB 0-1.
	 * @param array<int, mixed>    $shapes Shapes.
	 * @param array<string, mixed> $rot   Rotation keyframes.
	 * @param float                $x     X.
	 * @param float                $y     Y.
	 * @return array<string, mixed>
	 */
	private static function shape_layer( $name, $ind, $color, $shapes, $rot, $x, $y ) {
		foreach ( $shapes as &$shape ) {
			if ( 'fl' === $shape['ty'] ) {
				$shape['c']['k'] = array( $color[0], $color[1], $color[2], 1 );
			}
		}

		return array(
			'ddd' => 0,
			'ind' => $ind,
			'ty'  => 4,
			'nm'  => $name,
			'sr'  => 1,
			'ks'  => array(
				'o' => array( 'a' => 0, 'k' => 100 ),
				'r' => array_merge( array( 'a' => ! empty( $rot['k'] ) ? 1 : 0 ), $rot ),
				'p' => array( 'a' => 0, 'k' => array( $x, $y, 0 ) ),
				'a' => array( 'a' => 0, 'k' => array( 0, 0, 0 ) ),
				's' => array( 'a' => 0, 'k' => array( 100, 100, 100 ) ),
			),
			'ao' => 0,
			'shapes' => $shapes,
			'ip' => 0,
			'op' => 180,
			'st' => 0,
			'bm' => 0,
		);
	}

	/**
	 * @param string               $name  Name.
	 * @param int                  $ind   Index.
	 * @param float                $x     X.
	 * @param float                $y     Y.
	 * @param float                $rx    Radius x.
	 * @param float[]              $color Color.
	 * @param array<string, mixed> $rot   Rotation.
	 * @param float                $px    Position x.
	 * @param float                $py    Position y.
	 * @return array<string, mixed>
	 */
	private static function ellipse_layer( $name, $ind, $x, $y, $rx, $color, $rot, $px, $py ) {
		$ry = 'Hand' === $name ? $rx : $rx * 1.1;
		return self::shape_layer(
			$name,
			$ind,
			$color,
			array(
				array(
					'ty' => 'el',
					'd'  => 1,
					's'  => array( 'a' => 0, 'k' => array( $rx * 2, $ry * 2 ) ),
					'p'  => array( 'a' => 0, 'k' => array( 0, 0 ) ),
				),
				array( 'ty' => 'fl', 'c' => array( 'a' => 0, 'k' => array( 1, 1, 1, 1 ) ) ),
			),
			$rot,
			$px,
			$py
		);
	}
}
