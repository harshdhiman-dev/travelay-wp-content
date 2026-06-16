<?php
/**
 * Illustrated country avatar renderer (v2).
 *
 * @package TravelayCulturalWelcome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TCW_Avatar_Library
 */
class TCW_Avatar_Library {

	/**
	 * Render avatar SVG for a profile.
	 *
	 * @param array<string, mixed> $profile Profile data.
	 * @return string
	 */
	public static function render( $profile ) {
		$slug    = isset( $profile['location_slug'] ) ? sanitize_title( $profile['location_slug'] ) : '';
		$gesture = isset( $profile['gesture'] ) ? TCW_Gestures::sanitize( $profile['gesture'] ) : 'wave';
		$palette = isset( $profile['palette'] ) && is_array( $profile['palette'] ) ? $profile['palette'] : TCW_Gestures::palette_for_slug( $slug );

		if ( 'all-country-test' === $slug ) {
			$slug = 'india';
		}

		$custom_file = TCW_PLUGIN_DIR . 'assets/avatars/countries/' . $slug . '.svg';
		if ( file_exists( $custom_file ) ) {
			$svg = file_get_contents( $custom_file );
			if ( is_string( $svg ) && false !== strpos( $svg, '<svg' ) ) {
				return self::inject_palette( $svg, $palette, $gesture );
			}
		}

		$premium = TCW_Premium_Avatars::render( $slug, $gesture, $palette );
		if ( $premium ) {
			return $premium;
		}

		$style = self::country_style( $slug );
		$pose  = self::render_pose( $gesture, $style, $palette );

		return $pose;
	}

	/**
	 * Inject palette CSS variables into a file-based SVG.
	 *
	 * @param string               $svg     SVG content.
	 * @param array<string,string> $palette Palette.
	 * @param string               $gesture Gesture key.
	 * @return string
	 */
	private static function inject_palette( $svg, $palette, $gesture ) {
		$style = sprintf(
			'--tcw-primary:%1$s;--tcw-secondary:%2$s;--tcw-accent:%3$s;',
			esc_attr( $palette['primary'] ?? '#0f766e' ),
			esc_attr( $palette['secondary'] ?? '#ffffff' ),
			esc_attr( $palette['accent'] ?? '#134e4a' )
		);

		if ( false === strpos( $svg, 'tcw-avatar-art' ) ) {
			$svg = preg_replace( '/<svg\b/', '<svg class="tcw-avatar-art tcw-gesture-' . esc_attr( $gesture ) . '" style="' . $style . '"', $svg, 1 );
		} else {
			$svg = preg_replace( '/<svg\b[^>]*class="([^"]*)"/', '<svg class="$1 tcw-gesture-' . esc_attr( $gesture ) . '" style="' . $style . '"', $svg, 1 );
		}
		return $svg;
	}

	/**
	 * Country-specific styling metadata.
	 *
	 * @param string $slug Location slug.
	 * @return array<string, string>
	 */
	private static function country_style( $slug ) {
		$styles = array(
			'japan'          => array( 'garment' => 'kimono-collar', 'hair' => 'straight', 'skin' => '#f0c7a8' ),
			'india'          => array( 'garment' => 'kurta', 'hair' => 'long', 'skin' => '#c98b63' ),
			'italy'          => array( 'garment' => 'elegant-scarf', 'hair' => 'wavy', 'skin' => '#e8b796' ),
			'brazil'         => array( 'garment' => 'casual-shirt', 'hair' => 'curly', 'skin' => '#9d6944' ),
			'australia'      => array( 'garment' => 'casual-shirt', 'hair' => 'short', 'skin' => '#e5b08d' ),
			'saudi-arabia'   => array( 'garment' => 'thobe', 'hair' => 'covered', 'skin' => '#d1a07b' ),
			'france'         => array( 'garment' => 'blazer', 'hair' => 'styled', 'skin' => '#e8b796' ),
			'spain'          => array( 'garment' => 'open-shirt', 'hair' => 'wavy', 'skin' => '#d9a074' ),
			'united-kingdom' => array( 'garment' => 'blazer', 'hair' => 'short', 'skin' => '#e5b08d' ),
			'mexico'         => array( 'garment' => 'embroidered', 'hair' => 'dark-wavy', 'skin' => '#b87952' ),
			'canada'         => array( 'garment' => 'casual-shirt', 'hair' => 'short', 'skin' => '#e5b08d' ),
			'usa'            => array( 'garment' => 'casual-shirt', 'hair' => 'short', 'skin' => '#e0a97f' ),
			'netherlands'    => array( 'garment' => 'casual-shirt', 'hair' => 'straight', 'skin' => '#e8b796' ),
			'greece'         => array( 'garment' => 'open-shirt', 'hair' => 'wavy', 'skin' => '#d9a074' ),
			'russia'         => array( 'garment' => 'coat', 'hair' => 'straight', 'skin' => '#e5b08d' ),
			'switzerland'    => array( 'garment' => 'blazer', 'hair' => 'short', 'skin' => '#e8b796' ),
		);

		return isset( $styles[ $slug ] ) ? $styles[ $slug ] : array(
			'garment' => 'casual-shirt',
			'hair'    => 'short',
			'skin'    => '#e0a97f',
		);
	}

	/**
	 * Build pose SVG.
	 *
	 * @param string               $gesture Gesture.
	 * @param array<string,string> $style   Country style.
	 * @param array<string,string> $palette Palette.
	 * @return string
	 */
	private static function render_pose( $gesture, $style, $palette ) {
		$primary   = esc_attr( $palette['primary'] ?? '#0f766e' );
		$secondary = esc_attr( $palette['secondary'] ?? '#ffffff' );
		$accent    = esc_attr( $palette['accent'] ?? '#134e4a' );
		$skin      = esc_attr( $style['skin'] );
		$garment   = esc_attr( $style['garment'] );

		$head  = self::head_group( $style, $skin );
		$torso = self::torso_group( $garment, $primary, $secondary, $accent );
		$limbs = self::limbs_for_gesture( $gesture, $skin, $primary );

		return sprintf(
			'<svg class="tcw-avatar-art tcw-gesture-%1$s tcw-garment-%8$s" viewBox="0 0 160 200" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false" style="--tcw-primary:%2$s;--tcw-secondary:%3$s;--tcw-accent:%4$s;">
				<defs>
					<linearGradient id="tcwFaceGlow" x1="0%%" y1="0%%" x2="0%%" y2="100%%">
						<stop offset="0%%" stop-color="#ffffff" stop-opacity="0.45"/>
						<stop offset="100%%" stop-color="#ffffff" stop-opacity="0"/>
					</linearGradient>
				</defs>
				<ellipse cx="80" cy="188" rx="42" ry="8" fill="#0f172a" opacity="0.08"/>
				<g class="tcw-figure">
					%5$s
					%6$s
					%7$s
				</g>
			</svg>',
			esc_attr( $gesture ),
			$primary,
			$secondary,
			$accent,
			$limbs,
			$torso,
			$head,
			$garment
		);
	}

	/**
	 * Head and face.
	 *
	 * @param array<string,string> $style Style.
	 * @param string               $skin  Skin tone.
	 * @return string
	 */
	private static function head_group( $style, $skin ) {
		$hair = self::hair_shape( $style['hair'] );

		return sprintf(
			'<g class="tcw-head-group">
				%1$s
				<ellipse cx="80" cy="58" rx="30" ry="34" fill="%2$s"/>
				<ellipse cx="80" cy="46" rx="22" ry="14" fill="url(#tcwFaceGlow)"/>
				<ellipse cx="68" cy="56" rx="4.5" ry="5.5" fill="#1f2937"/>
				<ellipse cx="92" cy="56" rx="4.5" ry="5.5" fill="#1f2937"/>
				<ellipse cx="69" cy="57" rx="1.4" ry="1.4" fill="#fff"/>
				<ellipse cx="93" cy="57" rx="1.4" ry="1.4" fill="#fff"/>
				<path d="M68 68 Q80 76 92 68" fill="none" stroke="#b45309" stroke-width="2.2" stroke-linecap="round"/>
				<ellipse cx="62" cy="62" rx="5" ry="3" fill="#e38b7b" opacity="0.35"/>
				<ellipse cx="98" cy="62" rx="5" ry="3" fill="#e38b7b" opacity="0.35"/>
			</g>',
			$hair,
			esc_attr( $skin )
		);
	}

	/**
	 * Hair variants.
	 *
	 * @param string $type Hair type.
	 * @return string
	 */
	private static function hair_shape( $type ) {
		switch ( $type ) {
			case 'covered':
				return '<path d="M48 44 C52 18 108 18 112 44 C112 58 108 72 80 74 C52 72 48 58 48 44 Z" fill="#f8f5ef"/><path d="M50 42 C54 24 106 24 110 42 L110 88 C110 98 98 104 80 104 C62 104 50 98 50 88 Z" fill="#f8f5ef"/>';
			case 'long':
				return '<path d="M50 38 C54 14 106 14 110 38 C114 54 112 84 104 102 C98 112 62 112 56 102 C48 84 46 54 50 38 Z" fill="#1f1f1f"/>';
			case 'curly':
				return '<path d="M48 40 C50 18 110 18 112 40 C116 58 114 72 108 78 C100 86 60 86 52 78 C46 72 44 58 48 40 Z" fill="#2b1810"/><circle cx="54" cy="48" r="6" fill="#2b1810"/><circle cx="106" cy="48" r="6" fill="#2b1810"/>';
			case 'wavy':
			case 'dark-wavy':
				return '<path d="M48 38 C52 16 108 16 112 38 C116 56 114 80 108 92 C100 104 60 104 52 92 C46 80 44 56 48 38 Z" fill="#2d241e"/>';
			case 'straight':
				return '<path d="M50 36 C54 14 106 14 110 36 C112 50 110 68 108 76 C100 84 60 84 52 76 C50 68 48 50 50 36 Z" fill="#1a1a2e"/>';
			default:
				return '<path d="M50 38 C54 18 106 18 110 38 C112 52 108 66 80 68 C52 66 48 52 50 38 Z" fill="#2d241e"/>';
		}
	}

	/**
	 * Torso and garment.
	 *
	 * @param string $garment   Garment key.
	 * @param string $primary   Primary color.
	 * @param string $secondary Secondary color.
	 * @param string $accent    Accent color.
	 * @return string
	 */
	private static function torso_group( $garment, $primary, $secondary, $accent ) {
		$overlay = '';

		switch ( $garment ) {
			case 'kimono-collar':
				$overlay = '<path d="M56 92 L80 110 L104 92 L104 180 L56 180 Z" fill="' . $primary . '"/><path d="M56 92 L80 110 L104 92" fill="none" stroke="' . $accent . '" stroke-width="3"/><path d="M62 112 L80 124 L98 112" fill="none" stroke="' . $secondary . '" stroke-width="2"/>';
				break;
			case 'kurta':
				$overlay = '<path d="M52 90 L80 104 L108 90 L112 182 L48 182 Z" fill="' . $primary . '"/><path d="M62 104 L80 116 L98 104" fill="none" stroke="' . $secondary . '" stroke-width="2.5"/><rect x="73" y="118" width="14" height="20" rx="3" fill="' . $accent . '" opacity="0.85"/>';
				break;
			case 'thobe':
				$overlay = '<path d="M54 88 L80 102 L106 88 L110 182 L50 182 Z" fill="' . $secondary . '"/><path d="M68 102 L80 112 L92 102" fill="none" stroke="' . $primary . '" stroke-width="2"/>';
				break;
			case 'blazer':
				$overlay = '<path d="M54 90 L80 106 L106 90 L108 180 L52 180 Z" fill="' . $accent . '"/><path d="M80 106 L80 180" stroke="' . $secondary . '" stroke-width="2"/><path d="M58 98 L68 180 M102 98 L92 180" stroke="' . $primary . '" stroke-width="4" stroke-linecap="round"/>';
				break;
			case 'embroidered':
				$overlay = '<path d="M52 90 L80 106 L108 90 L110 182 L50 182 Z" fill="' . $primary . '"/><path d="M58 110 Q80 126 102 110" fill="none" stroke="' . $accent . '" stroke-width="3"/><circle cx="80" cy="132" r="5" fill="' . $secondary . '"/>';
				break;
			case 'elegant-scarf':
				$overlay = '<path d="M54 90 L80 106 L106 90 L108 180 L52 180 Z" fill="' . $primary . '"/><path d="M64 108 C72 130 88 130 96 108 L92 150 C84 160 76 160 68 150 Z" fill="' . $accent . '"/>';
				break;
			case 'coat':
				$overlay = '<path d="M52 88 L80 104 L108 88 L112 182 L48 182 Z" fill="' . $accent . '"/><path d="M80 104 L80 182" stroke="' . $secondary . '" stroke-width="2"/><path d="M56 96 L48 150 M104 96 L112 150" stroke="' . $primary . '" stroke-width="5" stroke-linecap="round"/>';
				break;
			default:
				$overlay = '<path d="M54 90 L80 106 L106 90 L108 180 L52 180 Z" fill="' . $primary . '"/><path d="M64 108 L80 120 L96 108" fill="none" stroke="' . $secondary . '" stroke-width="2"/>';
		}

		return '<g class="tcw-torso-group">' . $overlay . '</g>';
	}

	/**
	 * Arms and gesture limbs.
	 *
	 * @param string $gesture Gesture.
	 * @param string $skin    Skin tone.
	 * @param string $primary Primary color.
	 * @return string
	 */
	private static function limbs_for_gesture( $gesture, $skin, $primary ) {
		switch ( $gesture ) {
			case 'namaste':
				return sprintf(
					'<g class="tcw-limbs-group tcw-limbs-namaste">
						<path d="M46 108 C40 120 38 132 42 142" fill="none" stroke="%1$s" stroke-width="10" stroke-linecap="round"/>
						<path d="M114 108 C120 120 122 132 118 142" fill="none" stroke="%1$s" stroke-width="10" stroke-linecap="round"/>
						<g class="tcw-hands-namaste">
							<path d="M58 108 L80 92 L102 108 L102 132 C102 142 92 148 80 148 C68 148 58 142 58 132 Z" fill="%1$s"/>
							<path d="M64 110 L80 98 L96 110" fill="none" stroke="%2$s" stroke-width="2"/>
							<path d="M68 118 L80 108 L92 118" fill="none" stroke="%2$s" stroke-width="1.5"/>
							<path d="M72 126 L80 118 L88 126" fill="none" stroke="%2$s" stroke-width="1.5"/>
						</g>
					</g>',
					esc_attr( $skin ),
					esc_attr( $primary )
				);

			case 'bow':
				return sprintf(
					'<g class="tcw-limbs-group tcw-limbs-bow">
						<path d="M54 108 C48 130 46 150 50 166" fill="none" stroke="%1$s" stroke-width="9" stroke-linecap="round"/>
						<path d="M106 108 C112 130 114 150 110 166" fill="none" stroke="%1$s" stroke-width="9" stroke-linecap="round"/>
						<ellipse cx="50" cy="168" rx="7" ry="5" fill="%1$s"/>
						<ellipse cx="110" cy="168" rx="7" ry="5" fill="%1$s"/>
					</g>',
					esc_attr( $skin )
				);

			case 'hand_heart':
				return sprintf(
					'<g class="tcw-limbs-group tcw-limbs-heart">
						<path d="M54 108 C48 128 46 146 48 160" fill="none" stroke="%1$s" stroke-width="9" stroke-linecap="round"/>
						<path d="M106 108 C110 126 110 142 106 154" fill="none" stroke="%1$s" stroke-width="9" stroke-linecap="round"/>
						<g class="tcw-hand-heart">
							<path d="M62 112 C58 126 58 140 66 150 C74 140 90 140 98 150 C106 140 106 126 102 112 C98 104 80 104 62 112 Z" fill="%1$s"/>
							<path d="M72 118 C76 128 84 128 88 118" fill="none" stroke="%2$s" stroke-width="1.5" opacity="0.5"/>
						</g>
					</g>',
					esc_attr( $skin ),
					esc_attr( $primary )
				);

			case 'open_welcome':
				return sprintf(
					'<g class="tcw-limbs-group tcw-limbs-open">
						<g class="tcw-arm-left">
							<path d="M54 108 C34 112 20 100 14 84" fill="none" stroke="%1$s" stroke-width="10" stroke-linecap="round"/>
							<path d="M12 82 C8 78 10 70 16 68 C22 66 26 72 24 78 C22 84 16 86 12 82 Z" fill="%1$s"/>
						</g>
						<g class="tcw-arm-right">
							<path d="M106 108 C126 112 140 100 146 84" fill="none" stroke="%1$s" stroke-width="10" stroke-linecap="round"/>
							<path d="M148 82 C152 78 150 70 144 68 C138 66 134 72 136 78 C138 84 144 86 148 82 Z" fill="%1$s"/>
						</g>
					</g>',
					esc_attr( $skin )
				);

			case 'nod':
				return sprintf(
					'<g class="tcw-limbs-group tcw-limbs-nod">
						<path d="M54 108 C50 128 48 148 50 164" fill="none" stroke="%1$s" stroke-width="9" stroke-linecap="round"/>
						<path d="M106 108 C110 128 112 148 110 164" fill="none" stroke="%1$s" stroke-width="9" stroke-linecap="round"/>
						<ellipse cx="50" cy="166" rx="7" ry="5" fill="%1$s"/>
						<ellipse cx="110" cy="166" rx="7" ry="5" fill="%1$s"/>
					</g>',
					esc_attr( $skin )
				);

			default: // wave.
				return sprintf(
					'<g class="tcw-limbs-group tcw-limbs-wave">
						<path d="M54 108 C50 128 48 148 50 164" fill="none" stroke="%1$s" stroke-width="9" stroke-linecap="round"/>
						<g class="tcw-arm-wave">
							<path d="M106 108 C118 92 132 78 138 58" fill="none" stroke="%1$s" stroke-width="10" stroke-linecap="round"/>
							<g class="tcw-wave-hand">
								<ellipse cx="140" cy="54" rx="12" ry="10" fill="%1$s"/>
								<path d="M132 48 L132 40 M136 46 L136 36 M140 45 L140 34 M144 46 L144 36 M148 48 L148 40" stroke="%2$s" stroke-width="2" stroke-linecap="round"/>
							</g>
						</g>
						<ellipse cx="50" cy="166" rx="7" ry="5" fill="%1$s"/>
					</g>',
					esc_attr( $skin ),
					esc_attr( $primary )
				);
		}
	}
}
