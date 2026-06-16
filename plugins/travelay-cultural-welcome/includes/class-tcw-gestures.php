<?php
/**
 * Gesture definitions and cultural metadata.
 *
 * @package TravelayCulturalWelcome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TCW_Gestures
 */
class TCW_Gestures {

	/**
	 * Supported gesture keys.
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function all() {
		return array(
			'namaste'       => array(
				'label'       => __( 'Namaste', 'travelay-cultural-welcome' ),
				'description' => __( 'Palms joined with a gentle bow — a respectful greeting widely used in India and South Asia.', 'travelay-cultural-welcome' ),
			),
			'bow'           => array(
				'label'       => __( 'Respectful Bow', 'travelay-cultural-welcome' ),
				'description' => __( 'A modest bow showing respect — common in Japan and parts of East Asia.', 'travelay-cultural-welcome' ),
			),
			'wave'          => array(
				'label'       => __( 'Warm Wave', 'travelay-cultural-welcome' ),
				'description' => __( 'An open, friendly wave — welcoming and universally approachable.', 'travelay-cultural-welcome' ),
			),
			'hand_heart'    => array(
				'label'       => __( 'Hand Over Heart', 'travelay-cultural-welcome' ),
				'description' => __( 'Hand placed over the heart — a gesture of sincerity and respect.', 'travelay-cultural-welcome' ),
			),
			'open_welcome'  => array(
				'label'       => __( 'Open Welcome', 'travelay-cultural-welcome' ),
				'description' => __( 'Open arms or outward hands — expressing hospitality and warmth.', 'travelay-cultural-welcome' ),
			),
			'nod'           => array(
				'label'       => __( 'Polite Nod', 'travelay-cultural-welcome' ),
				'description' => __( 'A subtle nod of acknowledgment — understated and dignified.', 'travelay-cultural-welcome' ),
			),
		);
	}

	/**
	 * Validate gesture key.
	 *
	 * @param string $gesture Gesture key.
	 * @return bool
	 */
	public static function is_valid( $gesture ) {
		return array_key_exists( $gesture, self::all() );
	}

	/**
	 * Sanitize gesture key with fallback.
	 *
	 * @param string $gesture Gesture key.
	 * @return string
	 */
	public static function sanitize( $gesture ) {
		$gesture = sanitize_key( $gesture );
		return self::is_valid( $gesture ) ? $gesture : 'wave';
	}

	/**
	 * Default accent palette per country slug.
	 *
	 * @param string $slug Location slug.
	 * @return array{primary:string,secondary:string,accent:string}
	 */
	public static function palette_for_slug( $slug ) {
		$palettes = array(
			'india'          => array( 'primary' => '#FF9933', 'secondary' => '#138808', 'accent' => '#000080' ),
			'japan'          => array( 'primary' => '#BC002D', 'secondary' => '#FFFFFF', 'accent' => '#1A1A2E' ),
			'italy'          => array( 'primary' => '#008C45', 'secondary' => '#F4F5F0', 'accent' => '#CD212A' ),
			'brazil'         => array( 'primary' => '#009C3B', 'secondary' => '#FFDF00', 'accent' => '#002776' ),
			'australia'      => array( 'primary' => '#00008B', 'secondary' => '#FFCD00', 'accent' => '#E4002B' ),
			'saudi-arabia'   => array( 'primary' => '#006C35', 'secondary' => '#FFFFFF', 'accent' => '#C5A572' ),
			'france'         => array( 'primary' => '#0055A4', 'secondary' => '#FFFFFF', 'accent' => '#EF4135' ),
			'spain'          => array( 'primary' => '#AA151B', 'secondary' => '#F1BF00', 'accent' => '#1E3A5F' ),
			'united-kingdom' => array( 'primary' => '#012169', 'secondary' => '#FFFFFF', 'accent' => '#C8102E' ),
			'mexico'         => array( 'primary' => '#006847', 'secondary' => '#FFFFFF', 'accent' => '#CE1126' ),
			'canada'         => array( 'primary' => '#FF0000', 'secondary' => '#FFFFFF', 'accent' => '#1F2A44' ),
			'usa'            => array( 'primary' => '#3C3B6E', 'secondary' => '#FFFFFF', 'accent' => '#B22234' ),
			'netherlands'    => array( 'primary' => '#AE1C28', 'secondary' => '#FFFFFF', 'accent' => '#21468B' ),
			'greece'         => array( 'primary' => '#0D5EAF', 'secondary' => '#FFFFFF', 'accent' => '#1C2833' ),
			'russia'         => array( 'primary' => '#0039A6', 'secondary' => '#FFFFFF', 'accent' => '#D52B1E' ),
			'switzerland'    => array( 'primary' => '#FF0000', 'secondary' => '#FFFFFF', 'accent' => '#1F2A44' ),
		);

		if ( isset( $palettes[ $slug ] ) ) {
			return $palettes[ $slug ];
		}

		return array(
			'primary'   => '#0F766E',
			'secondary' => '#FFFFFF',
			'accent'    => '#134E4A',
		);
	}
}
