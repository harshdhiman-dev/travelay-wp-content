<?php
/**
 * Country-specific confetti celebration profiles.
 *
 * @package TravelayCulturalWelcome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TCW_Confetti
 */
class TCW_Confetti {

	/**
	 * Resolve confetti profile for a location slug.
	 *
	 * @param string $slug Location slug.
	 * @return array<string, mixed>
	 */
	public static function get_profile_for_slug( $slug ) {
		$slug     = sanitize_title( $slug );
		$profiles = self::profiles();

		if ( 'all-country-test' === $slug ) {
			$slug = 'india';
		}

		$profile = isset( $profiles[ $slug ] ) ? $profiles[ $slug ] : $profiles['default'];

		/**
		 * Filter confetti profile for a country slug.
		 *
		 * @param array  $profile Confetti profile.
		 * @param string $slug    Location slug.
		 */
		return apply_filters( 'tcw_confetti_profile', $profile, $slug );
	}

	/**
	 * All country confetti definitions.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function profiles() {
		return array(
			'default' => array(
				'colors'          => array( '#0f766e', '#ffffff', '#fbbf24', '#134e4a' ),
				'shapes'          => array( 'circle', 'ribbon', 'star' ),
				'gravity'         => 0.14,
				'wind'            => 0.015,
				'flutter'         => 0.6,
				'burst_count'     => 80,
				'shower_count'    => 50,
				'shower_duration' => 3500,
				'sparkle'         => true,
			),
			'united-kingdom' => array(
				'colors'          => array( '#012169', '#C8102E', '#FFFFFF', '#FFD700', '#1F2A44' ),
				'shapes'          => array( 'union_cross', 'star', 'ribbon', 'circle' ),
				'gravity'         => 0.11,
				'wind'            => 0.025,
				'flutter'         => 0.45,
				'burst_count'     => 110,
				'shower_count'    => 65,
				'shower_duration' => 4200,
				'sparkle'         => true,
				'motif'           => 'royal_celebration',
			),
			'india' => array(
				'colors'          => array( '#FF9933', '#FFFFFF', '#138808', '#FFD700', '#E91E63' ),
				'shapes'          => array( 'marigold', 'lotus', 'ribbon', 'circle' ),
				'gravity'         => 0.1,
				'wind'            => 0.01,
				'flutter'         => 0.95,
				'burst_count'     => 130,
				'shower_count'    => 80,
				'shower_duration' => 5000,
				'sparkle'         => true,
				'motif'           => 'festival',
			),
			'japan' => array(
				'colors'          => array( '#FFB7C5', '#FFFFFF', '#BC002D', '#FFC0CB', '#F8E8EE' ),
				'shapes'          => array( 'sakura', 'circle', 'ribbon' ),
				'gravity'         => 0.06,
				'wind'            => 0.035,
				'flutter'         => 1.2,
				'burst_count'     => 100,
				'shower_count'    => 70,
				'shower_duration' => 4800,
				'sparkle'         => false,
				'motif'           => 'sakura_bloom',
			),
			'italy' => array(
				'colors'          => array( '#008C45', '#FFFFFF', '#CD212A', '#FFD700' ),
				'shapes'          => array( 'ribbon', 'star', 'circle', 'diamond' ),
				'gravity'         => 0.12,
				'wind'            => 0.02,
				'flutter'         => 0.55,
				'burst_count'     => 95,
				'shower_count'    => 55,
				'shower_duration' => 3800,
				'sparkle'         => true,
				'motif'           => 'la_dolce',
			),
			'brazil' => array(
				'colors'          => array( '#009C3B', '#FFDF00', '#002776', '#FFFFFF' ),
				'shapes'          => array( 'star', 'diamond', 'ribbon', 'circle' ),
				'gravity'         => 0.13,
				'wind'            => 0.03,
				'flutter'         => 0.7,
				'burst_count'     => 120,
				'shower_count'    => 75,
				'shower_duration' => 4500,
				'sparkle'         => true,
				'motif'           => 'carnival',
			),
			'australia' => array(
				'colors'          => array( '#00008B', '#FFCD00', '#E4002B', '#FFFFFF' ),
				'shapes'          => array( 'star', 'circle', 'ribbon' ),
				'gravity'         => 0.14,
				'wind'            => 0.028,
				'flutter'         => 0.5,
				'burst_count'     => 90,
				'shower_count'    => 55,
				'shower_duration' => 3600,
				'sparkle'         => true,
				'motif'           => 'southern_cross',
			),
			'saudi-arabia' => array(
				'colors'          => array( '#006C35', '#FFFFFF', '#C5A572', '#FFD700' ),
				'shapes'          => array( 'diamond', 'star', 'circle', 'ribbon' ),
				'gravity'         => 0.09,
				'wind'            => 0.012,
				'flutter'         => 0.4,
				'burst_count'     => 85,
				'shower_count'    => 50,
				'shower_duration' => 3400,
				'sparkle'         => true,
				'motif'           => 'desert_gold',
			),
			'france' => array(
				'colors'          => array( '#0055A4', '#FFFFFF', '#EF4135', '#FFD700' ),
				'shapes'          => array( 'ribbon', 'star', 'circle', 'diamond' ),
				'gravity'         => 0.11,
				'wind'            => 0.022,
				'flutter'         => 0.65,
				'burst_count'     => 95,
				'shower_count'    => 58,
				'shower_duration' => 3900,
				'sparkle'         => true,
				'motif'           => 'elegance',
			),
			'spain' => array(
				'colors'          => array( '#AA151B', '#F1BF00', '#FFFFFF', '#C60B1E' ),
				'shapes'          => array( 'ribbon', 'star', 'circle', 'diamond' ),
				'gravity'         => 0.12,
				'wind'            => 0.025,
				'flutter'         => 0.6,
				'burst_count'     => 100,
				'shower_count'    => 60,
				'shower_duration' => 4000,
				'sparkle'         => true,
				'motif'           => 'fiesta',
			),
			'mexico' => array(
				'colors'          => array( '#006847', '#CE1126', '#FFFFFF', '#FFD700', '#FF6B9D' ),
				'shapes'          => array( 'papel', 'star', 'ribbon', 'circle' ),
				'gravity'         => 0.1,
				'wind'            => 0.02,
				'flutter'         => 0.85,
				'burst_count'     => 125,
				'shower_count'    => 78,
				'shower_duration' => 4600,
				'sparkle'         => true,
				'motif'           => 'papel_picado',
			),
			'canada' => array(
				'colors'          => array( '#FF0000', '#FFFFFF', '#1F2A44' ),
				'shapes'          => array( 'maple', 'circle', 'ribbon', 'star' ),
				'gravity'         => 0.12,
				'wind'            => 0.018,
				'flutter'         => 0.55,
				'burst_count'     => 90,
				'shower_count'    => 55,
				'shower_duration' => 3700,
				'sparkle'         => true,
				'motif'           => 'maple_snow',
			),
			'usa' => array(
				'colors'          => array( '#3C3B6E', '#B22234', '#FFFFFF', '#FFD700' ),
				'shapes'          => array( 'star', 'ribbon', 'circle', 'stripe' ),
				'gravity'         => 0.13,
				'wind'            => 0.02,
				'flutter'         => 0.5,
				'burst_count'     => 105,
				'shower_count'    => 62,
				'shower_duration' => 4000,
				'sparkle'         => true,
				'motif'           => 'stars_stripes',
			),
			'netherlands' => array(
				'colors'          => array( '#AE1C28', '#FFFFFF', '#21468B', '#FFD700' ),
				'shapes'          => array( 'tulip', 'ribbon', 'circle', 'star' ),
				'gravity'         => 0.11,
				'wind'            => 0.03,
				'flutter'         => 0.7,
				'burst_count'     => 88,
				'shower_count'    => 52,
				'shower_duration' => 3600,
				'sparkle'         => true,
				'motif'           => 'tulip_spring',
			),
			'greece' => array(
				'colors'          => array( '#0D5EAF', '#FFFFFF', '#FFD700', '#1C2833' ),
				'shapes'          => array( 'circle', 'ribbon', 'star', 'diamond' ),
				'gravity'         => 0.1,
				'wind'            => 0.028,
				'flutter'         => 0.75,
				'burst_count'     => 85,
				'shower_count'    => 50,
				'shower_duration' => 3800,
				'sparkle'         => true,
				'motif'           => 'aegean',
			),
			'russia' => array(
				'colors'          => array( '#0039A6', '#FFFFFF', '#D52B1E', '#FFD700' ),
				'shapes'          => array( 'ribbon', 'star', 'circle', 'diamond' ),
				'gravity'         => 0.12,
				'wind'            => 0.015,
				'flutter'         => 0.5,
				'burst_count'     => 90,
				'shower_count'    => 55,
				'shower_duration' => 3700,
				'sparkle'         => true,
				'motif'           => 'winter_gold',
			),
			'switzerland' => array(
				'colors'          => array( '#FF0000', '#FFFFFF', '#1F2A44', '#FFD700' ),
				'shapes'          => array( 'cross_swiss', 'circle', 'ribbon', 'star' ),
				'gravity'         => 0.1,
				'wind'            => 0.012,
				'flutter'         => 0.45,
				'burst_count'     => 82,
				'shower_count'    => 48,
				'shower_duration' => 3500,
				'sparkle'         => true,
				'motif'           => 'alpine',
			),
		);
	}
}
