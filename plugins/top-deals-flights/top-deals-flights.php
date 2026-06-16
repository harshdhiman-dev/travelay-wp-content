<?php
/**
 * Plugin Name: Top Deals on Flights
 * Plugin URI: https://travelaystagging.com
 * Description: Display top flight deals in a customizable grid layout with data from Amadeus API
 * Version: 1.0.0
 * Author: DigitalSilk
 * Author URI: https://digitalsilk.com
 * Text Domain: top-deals-flights
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TDF_VERSION', '1.0.0' );
define( 'TDF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TDF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main plugin class
 */
class Top_Deals_Flights {
	
	private static $instance = null;
	
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}
	
	public function init() {
		// Check if ACF is active
		if ( ! function_exists( 'acf_register_block_type' ) ) {
			add_action( 'admin_notices', array( $this, 'acf_missing_notice' ) );
			return;
		}
		
		// Register ACF fields first
		add_action( 'acf/init', array( $this, 'register_acf_fields' ), 5 );
		
		// Register block
		add_action( 'acf/init', array( $this, 'register_block' ), 10 );
		
		// Register REST API
		add_action( 'rest_api_init', array( $this, 'register_rest_api' ) );
		
		// Enqueue assets
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
	}
	
	public function acf_missing_notice() {
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'Top Deals on Flights requires Advanced Custom Fields Pro to be installed and active.', 'top-deals-flights' ); ?></p>
		</div>
		<?php
	}
	
	public function register_block() {
		static $attempted = false;
		if ( $attempted ) {
			return;
		}
		$attempted = true;
		if ( function_exists( 'WP_Block_Type_Registry' ) && WP_Block_Type_Registry::get_instance()->is_registered( 'acf/top-deals-flights' ) ) {
			return;
		}
		acf_register_block_type( array(
			'name'            => 'top-deals-flights',
			'title'           => __( 'Top Deals on Flights', 'top-deals-flights' ),
			'description'     => __( 'Display top flight deals in a grid layout with data from Amadeus API', 'top-deals-flights' ),
			'render_callback' => array( $this, 'render_block' ),
			'category'        => 'widgets',
			'icon'            => 'airplane',
			'keywords'        => array( 'flights', 'deals', 'amadeus', 'travel' ),
			'supports'        => array(
				'align' => false,
				'mode'  => false,
			),
		) );
	}
	
	public function register_acf_fields() {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}
		
		acf_add_local_field_group( array(
			'key'    => 'group_top_deals_flights',
			'title'  => 'Top Deals on Flights Settings',
			'menu_order' => 0,
			'fields' => array(
				array(
					'key'   => 'field_origin',
					'label' => 'Origin (IATA Code)',
					'name'  => 'origin',
					'type'  => 'text',
					'default_value' => 'NYC',
					'instructions' => 'Enter IATA code (e.g., NYC, JFK, WAS)',
					'required' => 1,
				),
				array(
					'key'   => 'field_destination',
					'label' => 'Destination (IATA Code)',
					'name'  => 'destination',
					'type'  => 'text',
					'default_value' => 'ATL',
					'instructions' => 'Enter IATA code (e.g., ATL, MIA, FLL)',
					'required' => 1,
				),
				array(
					'key'   => 'field_departure_date',
					'label' => 'Departure Date',
					'name'  => 'departure_date',
					'type'  => 'date_picker',
					'display_format' => 'Y-m-d',
					'return_format' => 'Y-m-d',
					'instructions' => 'Optional. Defaults to 30 days from now if not set.',
				),
				array(
					'key'   => 'field_return_date',
					'label' => 'Return Date (Optional)',
					'name'  => 'return_date',
					'type'  => 'date_picker',
					'display_format' => 'Y-m-d',
					'return_format' => 'Y-m-d',
					'instructions' => 'For round trip flights',
				),
				array(
					'key'   => 'field_max_results',
					'label' => 'Max Results',
					'name'  => 'max_results',
					'type'  => 'number',
					'default_value' => 9,
					'min' => 1,
					'max' => 50,
					'instructions' => 'Number of deals to display (recommended: 9 for 3x3 grid)',
				),
				array(
					'key'   => 'field_currency',
					'label' => 'Currency Code',
					'name'  => 'currency',
					'type'  => 'text',
					'default_value' => 'USD',
					'instructions' => 'ISO currency code (USD, EUR, GBP, etc.)',
				),
				array(
					'key'   => 'field_columns_desktop',
					'label' => 'Columns (Desktop)',
					'name'  => 'columns_desktop',
					'type'  => 'number',
					'default_value' => 3,
					'min' => 1,
					'max' => 4,
					'instructions' => 'Number of columns on desktop screens',
				),
				array(
					'key'   => 'field_columns_tablet',
					'label' => 'Columns (Tablet)',
					'name'  => 'columns_tablet',
					'type'  => 'number',
					'default_value' => 2,
					'min' => 1,
					'max' => 3,
				),
				array(
					'key'   => 'field_columns_mobile',
					'label' => 'Columns (Mobile)',
					'name'  => 'columns_mobile',
					'type'  => 'number',
					'default_value' => 1,
					'min' => 1,
					'max' => 2,
				),
				array(
					'key'   => 'field_savings_percentage',
					'label' => 'Savings Badge Percentage',
					'name'  => 'savings_percentage',
					'type'  => 'text',
					'default_value' => '15',
					'instructions' => 'Percentage shown in "save X%" badge (e.g., 15 for 15%)',
				),
				array(
					'key'   => 'field_show_savings',
					'label' => 'Show Savings Badge',
					'name'  => 'show_savings',
					'type'  => 'true_false',
					'default_value' => 1,
					'ui' => 1,
				),
			),
			'location' => array(
				array(
					array(
						'param'    => 'block',
						'operator' => '==',
						'value'    => 'acf/top-deals-flights',
					),
				),
			),
		) );
	}
	
	public function render_block( $block, $content = '', $is_preview = false ) {
		$block_id = 'tdf-' . ( $block['id'] ?? uniqid() );
		
		$origin         = get_field( 'origin' ) ?: 'NYC';
		$destination    = get_field( 'destination' ) ?: 'ATL';
		$departure_date = get_field( 'departure_date' );
		$return_date    = get_field( 'return_date' );
		$max_results    = get_field( 'max_results' ) ?: 9;
		$currency       = get_field( 'currency' ) ?: 'USD';
		$columns_desk   = get_field( 'columns_desktop' ) ?: 3;
		$columns_tab    = get_field( 'columns_tablet' ) ?: 2;
		$columns_mob    = get_field( 'columns_mobile' ) ?: 1;
		$savings_pct    = get_field( 'savings_percentage' ) ?: '15';
		$show_savings   = get_field( 'show_savings' ) !== false;
		
		if ( empty( $departure_date ) ) {
			$departure_date = date( 'Y-m-d', strtotime( '+30 days' ) );
		}
		
		$rest_url = esc_url( rest_url( 'tdf/v1/offers' ) );
		
		$data_attrs = sprintf(
			' data-api-url="%s" data-origin="%s" data-destination="%s" data-date-from="%s" data-date-to="%s" data-limit="%d" data-currency="%s" data-cols-desk="%d" data-cols-tab="%d" data-cols-mob="%d" data-savings-pct="%s" data-show-savings="%s"',
			$rest_url,
			esc_attr( $origin ),
			esc_attr( $destination ),
			esc_attr( $departure_date ),
			esc_attr( $return_date ?: '' ),
			(int) $max_results,
			esc_attr( $currency ),
			(int) $columns_desk,
			(int) $columns_tab,
			(int) $columns_mob,
			esc_attr( $savings_pct ),
			$show_savings ? 'true' : 'false'
		);
		
		// Enqueue assets directly in render for editor preview
		wp_enqueue_style( 'tdf-style', TDF_PLUGIN_URL . 'assets/style.css', array(), TDF_VERSION );
		wp_enqueue_script( 'tdf-script', TDF_PLUGIN_URL . 'assets/script.js', array( 'jquery' ), TDF_VERSION, true );
		
		?>
		<div id="<?php echo esc_attr( $block_id ); ?>" class="tdf-grid"<?php echo $data_attrs; ?>>
			<div class="tdf-loading">Loading top deals...</div>
			<div class="tdf-error" style="display:none;"></div>
			<div class="tdf-items"></div>
		</div>
		<?php
	}
	
	public function register_rest_api() {
		register_rest_route( 'tdf/v1', '/offers', array(
			'methods'             => 'GET',
			'callback'           => array( $this, 'rest_get_offers' ),
			'permission_callback' => '__return_true',
			'args'               => array(
				'origin'        => array( 'required' => false, 'type' => 'string', 'default' => 'NYC' ),
				'destination'   => array( 'required' => false, 'type' => 'string', 'default' => 'ATL' ),
				'departure_date' => array( 'required' => false, 'type' => 'string' ),
				'return_date'   => array( 'required' => false, 'type' => 'string' ),
				'max'           => array( 'required' => false, 'type' => 'integer', 'default' => 9 ),
				'currency'      => array( 'required' => false, 'type' => 'string', 'default' => 'USD' ),
			),
		) );
	}
	
	public function rest_get_offers( $request ) {
		$origin         = $request->get_param( 'origin' ) ?: 'NYC';
		$destination    = $request->get_param( 'destination' ) ?: 'ATL';
		$departure_date = $request->get_param( 'departure_date' );
		$return_date    = $request->get_param( 'return_date' );
		$max            = (int) ( $request->get_param( 'max' ) ?: 9 );
		$currency       = $request->get_param( 'currency' ) ?: 'USD';
		
		if ( empty( $departure_date ) ) {
			$departure_date = date( 'Y-m-d', strtotime( '+30 days' ) );
		}
		
		// Try to get from Amadeus API
		$offers = $this->get_amadeus_offers( $origin, $destination, $departure_date, $return_date, $max, $currency );
		
		// Fallback to sample data
		if ( is_wp_error( $offers ) || empty( $offers ) ) {
			$offers = $this->get_sample_offers( $origin, $destination, $departure_date, $return_date, $max );
		}
		
		return new WP_REST_Response( array(
			'success' => true,
			'data'    => $offers,
		), 200 );
	}
	
	private function get_amadeus_offers( $origin, $destination, $departure_date, $return_date, $max, $currency ) {
		// Use Flight Offers (DigitalSilk) helpers if available
		if ( ! function_exists( 'ds_amadeus_token' ) || ! function_exists( 'ds_amadeus_base_url' ) ) {
			return new WP_Error( 'helpers_missing', 'Amadeus helpers not available' );
		}
		
		$token = ds_amadeus_token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}
		
		$query = array(
			'originLocationCode'      => strtoupper( $origin ),
			'destinationLocationCode' => strtoupper( $destination ),
			'departureDate'           => $departure_date,
			'adults'                  => 1,
			'max'                     => min( 50, max( 1, $max ) ),
			'currencyCode'            => $currency,
		);
		
		if ( ! empty( $return_date ) ) {
			$query['returnDate'] = $return_date;
		}
		
		$url = add_query_arg( $query, ds_amadeus_base_url() . '/v2/shopping/flight-offers' );
		
		$response = wp_remote_get( $url, array(
			'headers' => array( 'Authorization' => 'Bearer ' . $token ),
			'timeout' => 20,
		) );
		
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		
		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		
		if ( 200 !== $code || empty( $body['data'] ) ) {
			return new WP_Error( 'api_error', 'Amadeus API error' );
		}
		
		return $this->transform_amadeus_results( $body, $max );
	}
	
	private function transform_amadeus_results( $results, $max ) {
		$offers = array();
		$carriers = $results['dictionaries']['carriers'] ?? array();
		
		$count = 0;
		foreach ( $results['data'] as $offer ) {
			if ( $count >= $max ) break;
			
			$itineraries = $offer['itineraries'][0] ?? array();
			$segments = $itineraries['segments'] ?? array();
			if ( empty( $segments ) ) continue;
			
			$first_seg = $segments[0];
			$last_seg  = end( $segments );
			
			$origin_code = $first_seg['departure']['iataCode'] ?? '';
			$dest_code   = $last_seg['arrival']['iataCode'] ?? '';
			$dep_time    = $first_seg['departure']['at'] ?? '';
			$arr_time    = $last_seg['arrival']['at'] ?? '';
			
			$dep_date = ! empty( $dep_time ) ? date( 'Y-m-d', strtotime( $dep_time ) ) : '';
			$arr_date = ! empty( $arr_time ) ? date( 'Y-m-d', strtotime( $arr_time ) ) : '';
			
			$price_text = '';
			if ( isset( $offer['price'] ) ) {
				$total = $offer['price']['total'] ?? '0';
				$curr  = $offer['price']['currency'] ?? 'USD';
				$price_text = '$' . number_format( (float) $total, 2 );
				if ( strtoupper( $curr ) !== 'USD' ) {
					$price_text = $curr . ' ' . number_format( (float) $total, 2 );
				}
			}
			
			$carrier_code = $first_seg['carrierCode'] ?? '';
			$airline      = $carriers[ $carrier_code ] ?? $carrier_code;
			
			$offers[] = array(
				'airline'     => $airline,
				'origin'      => $origin_code,
				'destination' => $dest_code,
				'dateText'    => $dep_date . ( $arr_date ? ' - ' . $arr_date : '' ),
				'priceText'   => $price_text,
				'deepLink'    => $offer['links']['flightOffers'] ?? '#',
				'savingsText' => '',
				'isFeatured'  => ( $count === 0 ),
			);
			
			$count++;
		}
		
		return $offers;
	}
	
	private function get_sample_offers( $origin, $destination, $departure_date, $return_date, $max ) {
		$airlines = array( 'American Airlines', 'Delta', 'United Airlines', 'Southwest', 'JetBlue', 'Alaska Airlines', 'SPIRIT AIRLINES', 'FRONTIER AIRLINES' );
		$offers   = array();
		
		$base_dep = ! empty( $departure_date ) ? strtotime( $departure_date ) : strtotime( '+30 days' );
		if ( ! $base_dep ) $base_dep = strtotime( '+30 days' );
		
		for ( $i = 0; $i < min( $max, 12 ); $i++ ) {
			$price = '$' . number_format( rand( 60, 500 ), 2 );
			
			$dep_ts = strtotime( '+' . rand( 0, 10 ) . ' days', $base_dep );
			$ret_ts = strtotime( '+' . rand( 1, 7 ) . ' days', $dep_ts );
			
			$dep_date = date( 'Y-m-d', $dep_ts );
			$ret_date = date( 'Y-m-d', $ret_ts );
			
			$offers[] = array(
				'airline'     => $airlines[ array_rand( $airlines ) ],
				'origin'      => $origin,
				'destination' => $destination,
				'dateText'    => $dep_date . ' - ' . $ret_date,
				'priceText'   => $price,
				'deepLink'    => '#',
				'savingsText' => ( $i % 3 === 0 ) ? 'Save ' . rand( 10, 30 ) . '%' : '',
				'isFeatured'  => ( $i === 0 ),
			);
		}
		
		return $offers;
	}
	
	public function enqueue_assets() {
		if ( ! has_block( 'acf/top-deals-flights' ) ) {
			return;
		}
		
		wp_enqueue_style(
			'tdf-style',
			TDF_PLUGIN_URL . 'assets/style.css',
			array(),
			TDF_VERSION
		);
		
		wp_enqueue_script(
			'tdf-script',
			TDF_PLUGIN_URL . 'assets/script.js',
			array( 'jquery' ),
			TDF_VERSION,
			true
		);
	}
	
	public function enqueue_editor_assets() {
		wp_enqueue_style(
			'tdf-editor-style',
			TDF_PLUGIN_URL . 'assets/style.css',
			array(),
			TDF_VERSION
		);
		
		wp_enqueue_script(
			'tdf-editor-script',
			TDF_PLUGIN_URL . 'assets/script.js',
			array( 'jquery' ),
			TDF_VERSION,
			true
		);
	}
}

Top_Deals_Flights::get_instance();

