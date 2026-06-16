<?php
/**
 * Premium illustrated SVG avatars for all countries.
 *
 * @package TravelayCulturalWelcome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TCW_Premium_Avatars
 */
class TCW_Premium_Avatars {

	/**
	 * Render premium avatar if available.
	 *
	 * @param string               $slug    Slug.
	 * @param string               $gesture Gesture.
	 * @param array<string,string> $palette Palette.
	 * @return string
	 */
	public static function render( $slug, $gesture, $palette ) {
		$config = self::get_config( $slug );
		if ( ! $config ) {
			return '';
		}

		return self::build_svg( $config, $gesture, $palette );
	}

	/**
	 * Country configs.
	 *
	 * @param string $slug Slug.
	 * @return array<string, mixed>|null
	 */
	private static function get_config( $slug ) {
		$configs = array(
			'india'          => array( 'garment' => 'kurta', 'skin' => '#c98b63', 'hair' => '#1f1f1f', 'label' => 'India' ),
			'japan'          => array( 'garment' => 'kimono', 'skin' => '#f0c7a8', 'hair' => '#1a1a2e', 'label' => 'Japan' ),
			'united-kingdom' => array( 'garment' => 'blazer', 'skin' => '#e8b796', 'hair' => '#2d241e', 'label' => 'UK' ),
			'italy'          => array( 'garment' => 'scarf', 'skin' => '#e8b796', 'hair' => '#3d2b1f', 'label' => 'Italy' ),
			'brazil'         => array( 'garment' => 'shirt', 'skin' => '#9d6944', 'hair' => '#2b1810', 'label' => 'Brazil' ),
			'australia'      => array( 'garment' => 'shirt', 'skin' => '#e5b08d', 'hair' => '#4a3728', 'label' => 'Australia' ),
			'saudi-arabia'   => array( 'garment' => 'thobe', 'skin' => '#d1a07b', 'hair' => '#f8f5ef', 'label' => 'Saudi' ),
			'france'         => array( 'garment' => 'blazer', 'skin' => '#e8b796', 'hair' => '#3d2b1f', 'label' => 'France' ),
			'spain'          => array( 'garment' => 'shirt', 'skin' => '#d9a074', 'hair' => '#2d241e', 'label' => 'Spain' ),
			'mexico'         => array( 'garment' => 'embroidered', 'skin' => '#b87952', 'hair' => '#1f1f1f', 'label' => 'Mexico' ),
			'canada'         => array( 'garment' => 'shirt', 'skin' => '#e5b08d', 'hair' => '#3d2b1f', 'label' => 'Canada' ),
			'usa'            => array( 'garment' => 'blazer', 'skin' => '#e0a97f', 'hair' => '#2d241e', 'label' => 'USA' ),
			'netherlands'    => array( 'garment' => 'shirt', 'skin' => '#e8b796', 'hair' => '#d4a017', 'label' => 'NL' ),
			'greece'         => array( 'garment' => 'shirt', 'skin' => '#d9a074', 'hair' => '#2d241e', 'label' => 'Greece' ),
			'russia'         => array( 'garment' => 'coat', 'skin' => '#e5b08d', 'hair' => '#d4a017', 'label' => 'Russia' ),
			'switzerland'    => array( 'garment' => 'blazer', 'skin' => '#e8b796', 'hair' => '#3d2b1f', 'label' => 'CH' ),
		);

		return isset( $configs[ $slug ] ) ? $configs[ $slug ] : null;
	}

	/**
	 * Build SVG from config.
	 *
	 * @param array<string, mixed> $config  Config.
	 * @param string               $gesture Gesture.
	 * @param array<string,string> $palette Palette.
	 * @return string
	 */
	private static function build_svg( $config, $gesture, $palette ) {
		$primary   = esc_attr( $palette['primary'] ?? '#0f766e' );
		$secondary = esc_attr( $palette['secondary'] ?? '#ffffff' );
		$accent    = esc_attr( $palette['accent'] ?? '#134e4a' );
		$skin      = esc_attr( $config['skin'] );
		$hair      = esc_attr( $config['hair'] );
		$garment   = $config['garment'];

		$torso   = self::torso_markup( $garment, $primary, $secondary, $accent );
		$limbs   = self::limbs_markup( $gesture, $skin, $primary );
		$gesture_class = 'tcw-gesture-' . esc_attr( $gesture );

		return sprintf(
			'<svg class="tcw-avatar-art %1$s tcw-garment-%8$s" viewBox="0 0 200 260" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="--tcw-primary:%2$s;--tcw-secondary:%3$s;--tcw-accent:%4$s;">
			<defs>
				<linearGradient id="tcwSkin" x1="0%%" y1="0%%" x2="0%%" y2="100%%"><stop offset="0%%" stop-color="%5$s"/><stop offset="100%%" stop-color="%5$s" stop-opacity="0.88"/></linearGradient>
				<radialGradient id="tcwGlow" cx="50%%" cy="30%%" r="55%%"><stop offset="0%%" stop-color="#fff" stop-opacity="0.4"/><stop offset="100%%" stop-color="#fff" stop-opacity="0"/></radialGradient>
			</defs>
			<ellipse cx="100" cy="248" rx="52" ry="9" fill="#0f172a" opacity="0.1"/>
			<g class="tcw-figure">%6$s%7$s%9$s</g>
			</svg>',
			$gesture_class,
			$primary,
			$secondary,
			$accent,
			$skin,
			$limbs,
			$torso,
			esc_attr( $garment ),
			self::head_markup( $hair )
		);
	}

	/**
	 * Head group markup.
	 *
	 * @param string $hair Hair color.
	 * @return string
	 */
	private static function head_markup( $hair ) {
		return '<g class="tcw-head-group">
			<path d="M62 52 C64 28 136 28 138 52 C140 72 136 88 128 96 C118 106 82 106 72 96 C64 88 60 72 62 52 Z" fill="' . esc_attr( $hair ) . '"/>
			<ellipse cx="100" cy="78" rx="34" ry="38" fill="url(#tcwSkin)"/>
			<ellipse cx="100" cy="62" rx="24" ry="16" fill="url(#tcwGlow)"/>
			<ellipse cx="86" cy="76" rx="5" ry="6" fill="#1e293b"/><ellipse cx="88" cy="77" rx="1.8" ry="2" fill="#fff"/>
			<ellipse cx="114" cy="76" rx="5" ry="6" fill="#1e293b"/><ellipse cx="116" cy="77" rx="1.8" ry="2" fill="#fff"/>
			<path d="M88 74 Q86 72 84 74" fill="none" stroke="#1e293b" stroke-width="1.2" stroke-linecap="round"/>
			<path d="M116 74 Q118 72 120 74" fill="none" stroke="#1e293b" stroke-width="1.2" stroke-linecap="round"/>
			<path d="M90 92 Q100 100 110 92" fill="none" stroke="#c2785c" stroke-width="2.2" stroke-linecap="round"/>
			<ellipse cx="78" cy="84" rx="6" ry="3.5" fill="#e38b7b" opacity="0.3"/>
			<ellipse cx="122" cy="84" rx="6" ry="3.5" fill="#e38b7b" opacity="0.3"/>
			<path d="M100 108 C96 118 92 122 88 120" fill="none" stroke="url(#tcwSkin)" stroke-width="8" stroke-linecap="round"/>
			<path d="M100 108 C104 118 108 122 112 120" fill="none" stroke="url(#tcwSkin)" stroke-width="8" stroke-linecap="round"/>
		</g>';
	}

	/**
	 * Torso markup by garment type.
	 *
	 * @param string $type      Garment.
	 * @param string $primary   Primary.
	 * @param string $secondary Secondary.
	 * @param string $accent    Accent.
	 * @return string
	 */
	private static function torso_markup( $type, $primary, $secondary, $accent ) {
		$base = '<g class="tcw-torso-group">';
		switch ( $type ) {
			case 'kimono':
				$base .= '<path d="M54 148 L100 168 L146 148 L150 238 L50 238 Z" fill="' . $primary . '"/>
				<path d="M54 148 L100 168 L146 148" fill="none" stroke="' . $accent . '" stroke-width="2.5"/>
				<path d="M68 168 L100 186 L132 168" fill="none" stroke="' . $secondary . '" stroke-width="2"/>';
				break;
			case 'kurta':
				$base .= '<path d="M52 148 L100 166 L148 148 L152 238 L48 238 Z" fill="' . $primary . '"/>
				<path d="M62 162 L100 178 L138 162" fill="none" stroke="' . $secondary . '" stroke-width="2.5"/>
				<rect x="93" y="178" width="14" height="22" rx="3" fill="' . $accent . '" opacity="0.9"/>';
				break;
			case 'thobe':
				$base .= '<path d="M54 146 L100 164 L146 146 L150 238 L50 238 Z" fill="' . $secondary . '"/>
				<path d="M68 162 L100 174 L132 162" fill="none" stroke="' . $primary . '" stroke-width="2"/>';
				break;
			case 'blazer':
				$base .= '<path d="M52 148 L100 166 L148 148 L152 238 L48 238 Z" fill="' . $accent . '"/>
				<path d="M100 166 L100 238" stroke="' . $secondary . '" stroke-width="2"/>
				<path d="M58 154 L68 238 M142 154 L132 238" stroke="' . $primary . '" stroke-width="4" stroke-linecap="round"/>
				<rect x="88" y="168" width="24" height="28" rx="2" fill="' . $secondary . '"/>';
				break;
			case 'scarf':
				$base .= '<path d="M52 148 L100 166 L148 148 L152 238 L48 238 Z" fill="' . $primary . '"/>
				<path d="M64 168 C72 192 88 192 96 168 L92 198 C84 208 76 208 68 198 Z" fill="' . $accent . '"/>';
				break;
			case 'coat':
				$base .= '<path d="M50 146 L100 166 L150 146 L154 238 L46 238 Z" fill="' . $accent . '"/>
				<path d="M54 152 L44 210 M146 152 L156 210" stroke="' . $primary . '" stroke-width="5" stroke-linecap="round"/>';
				break;
			case 'embroidered':
				$base .= '<path d="M52 148 L100 166 L148 148 L152 238 L48 238 Z" fill="' . $primary . '"/>
				<path d="M58 172 Q100 190 142 172" fill="none" stroke="' . $accent . '" stroke-width="3"/>
				<circle cx="100" cy="188" r="5" fill="' . $secondary . '"/>';
				break;
			default:
				$base .= '<path d="M54 148 L100 166 L146 148 L150 238 L50 238 Z" fill="' . $primary . '"/>
				<path d="M64 168 L100 180 L136 168" fill="none" stroke="' . $secondary . '" stroke-width="2"/>';
		}
		$base .= '<circle cx="100" cy="178" r="3" fill="' . $accent . '" opacity="0.75"/></g>';
		return $base;
	}

	/**
	 * Limbs by gesture.
	 *
	 * @param string $gesture Gesture.
	 * @param string $skin    Skin.
	 * @param string $primary Primary.
	 * @return string
	 */
	private static function limbs_markup( $gesture, $skin, $primary ) {
		$legs = '<path d="M58 168 C52 192 50 214 54 230" fill="none" stroke="' . $skin . '" stroke-width="11" stroke-linecap="round"/>
		<path d="M142 168 C148 192 150 214 146 230" fill="none" stroke="' . $skin . '" stroke-width="11" stroke-linecap="round"/>
		<ellipse cx="54" cy="232" rx="8" ry="6" fill="' . $skin . '"/><ellipse cx="146" cy="232" rx="8" ry="6" fill="' . $skin . '"/>';

		switch ( $gesture ) {
			case 'namaste':
				return '<g class="tcw-limbs-namaste">' . $legs . '
				<g class="tcw-hands-namaste">
					<path d="M56 112 L100 94 L144 112 L144 140 C144 152 124 160 100 160 C76 160 56 152 56 140 Z" fill="' . $skin . '"/>
					<path d="M64 114 L100 100 L136 114" fill="none" stroke="' . $primary . '" stroke-width="2" opacity="0.5"/>
				</g></g>';
			case 'bow':
				return '<g class="tcw-limbs-bow">' . $legs . '</g>';
			case 'hand_heart':
				return '<g class="tcw-limbs-heart">' . $legs . '
				<g class="tcw-hand-heart"><path d="M62 118 C58 132 58 146 66 156 C74 146 90 146 98 156 C106 146 106 132 102 118 C98 110 80 110 62 118 Z" fill="' . $skin . '"/></g></g>';
			case 'open_welcome':
				return '<g class="tcw-limbs-open">
				<g class="tcw-arm-left"><path d="M54 158 C30 162 14 148 8 128" fill="none" stroke="' . $skin . '" stroke-width="11" stroke-linecap="round"/></g>
				<g class="tcw-arm-right"><path d="M146 158 C170 162 186 148 192 128" fill="none" stroke="' . $skin . '" stroke-width="11" stroke-linecap="round"/></g>' . $legs . '</g>';
			case 'nod':
				return '<g class="tcw-limbs-nod">' . $legs . '</g>';
			default:
				return '<g class="tcw-limbs-wave">' . $legs . '
				<g class="tcw-arm-wave">
					<path d="M142 158 C158 140 174 116 180 94" fill="none" stroke="' . $skin . '" stroke-width="12" stroke-linecap="round"/>
					<g class="tcw-wave-hand"><ellipse cx="182" cy="90" rx="14" ry="12" fill="' . $skin . '"/>
					<path d="M172 82 L172 70 M178 80 L178 66 M184 79 L184 64 M190 80 L190 66 M196 82 L196 70" stroke="#d4a574" stroke-width="2.2" stroke-linecap="round"/></g>
				</g></g>';
		}
	}
}
