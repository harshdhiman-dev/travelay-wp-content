<?php
/**
 * Class DS_Icon_Library_Rest
 *
 * Handles REST API functionality for our theme.
 *
 * @package dstheme
 */

/**
 * Our rest api class.
 */
class DS_Icon_Library_Rest {

	/**
	 * Root path for the DS rest API.
	 *
	 * @var string
	 */
	public $rest_api_root = 'ds/v1';

	/**
	 * Constructor to register the necessary actions.
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register REST API route.
	 */
	public function register_routes() {
		// Register path for listing SVG icons.
		register_rest_route(
			$this->rest_api_root,
			'/icons-svg',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_svg_icons' ],
				'permission_callback' => 'is_user_logged_in',
			]
		);
		// Register path for listing media library icons.
		register_rest_route(
			$this->rest_api_root,
			'/icons-media',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_media_icons' ],
				'permission_callback' => 'is_user_logged_in',
				'args'                => [
					'tax' => [
						'required'          => false,
						'validate_callback' => function ( $param ) {
							return is_string( $param );
						},
						'sanitize_callback' => 'sanitize_text_field',
						'description'       => __( 'Optional taxonomy term slug to filter media icons.', 'dstheme' ),
					],
				],
			]
		);
		// Register the SVG raw data field for media library attachments.
		register_rest_field(
			'attachment',
			'svgRaw',
			[
				'get_callback'    => [ $this, 'get_raw_svg_data' ],
				'update_callback' => null,
				'schema'          => null,
			]
		);
	}

	/**
	 * Callback to retrieve SVG icons from the theme directory.
	 *
	 * @return WP_REST_Response|WP_Error Response containing SVG icon data or an error.
	 */
	public function get_svg_icons() {
		// Directory path of the SVG icons.
		$svg_dir = get_template_directory() . '/assets/_src/images/svg-icons/';

		// Ensure the directory exists.
		if ( ! file_exists( $svg_dir ) ) {
			return new WP_Error(
				'no_directory',
				__( 'SVG icons directory not found.', 'dstheme' ),
				[ 'status' => 404 ]
			);
		}

		// Scan the directory for SVG files.
		$files = scandir( $svg_dir );
		if ( ! $files ) {
			return new WP_Error(
				'no_files',
				__( 'No files found in the SVG icons directory.', 'dstheme' ),
				[ 'status' => 404 ]
			);
		}

		// Prepare the array of icons with 'slug' and 'name'.
		$icons = [];
		foreach ( $files as $file ) {
			if ( pathinfo( $file, PATHINFO_EXTENSION ) === 'svg' ) {
				$slug = pathinfo( $file, PATHINFO_FILENAME );
				// Skip "svg sprite" if present.
				if ( 'svg-sprite' === $slug ) {
					continue;
				}
				$icons[] = [
					'slug' => $slug,
					'name' => $this->normalize_name( $slug ),
					'url'  => get_template_directory_uri() . '/assets/_src/images/svg-icons/' . $file,
				];
			}
		}

		return rest_ensure_response( $icons );
	}

	/**
	 * Normalize the slug to a readable name format.
	 *
	 * @param string $slug The slug (file name).
	 * @return string The normalized name.
	 */
	private function normalize_name( $slug ) {
		// Replace hyphens or underscores with spaces, capitalize words.
		$name = str_replace( '-', ' ', $slug );
		$name = str_replace( '_', ' ', $name );
		return wp_strip_all_tags( ucwords( $name ) );
	}

	/**
	 * Get icons uploaded to the media library.
	 *
	 * @param WP_REST_Request $request The REST API request object.
	 * @return WP_REST_Response Response containing media icons.
	 */
	public function get_media_icons( WP_REST_Request $request ) {
		// Define the array of MIME types to filter by.
		$mime_types = [
			'image/jpeg',
			'image/png',
			'image/gif',
			'image/svg+xml',
			'image/webp',
		];

		// Get the 'tax' query parameter, if provided.
		$tax_slug = $request->get_param( 'tax' );

		// Set up the WP_Query arguments.
		$args = [
			'post_type'      => 'attachment',
			'post_mime_type' => $mime_types,
			'post_status'    => 'inherit',
			'tax_query'      => [ // phpcs:ignore
				[
					'taxonomy' => 'icon_type',
					'field'    => 'slug',
					'terms'    => $tax_slug ? $tax_slug : 'uncategorized',
				],
			],
			'posts_per_page' => -1,
		];

		// Execute the query to get media items.
		$query = new WP_Query( $args );

		// Initialize an array to store the formatted results.
		$attachments = [];

		// Loop through the results and prepare the attachment data.
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();

				// Get the attachment ID.
				$attachment_id = get_the_ID();

				// Push each attachment into the results array with name, URL, and ID.
				$attachments[] = [
					'name' => $this->normalize_name( get_the_title( $attachment_id ) ),
					'url'  => wp_get_attachment_image_url( $attachment_id, 'thumbnail', false ),
					'id'   => $attachment_id,
				];
			}
			wp_reset_postdata();
		}

		return rest_ensure_response( $attachments );
	}

	/**
	 * Get raw SVG content for an attachment.
	 *
	 * @param array $attachment The attachment object from the REST API.
	 * @return array SVG content and viewBox if it's an SVG, otherwise empty array.
	 */
	public function get_raw_svg_data( $attachment ) {
		// Get the attachment ID.
		$attachment_id = ( isset( $attachment['id'] ) ) ? $attachment['id'] : 0;
		if ( ! $attachment_id || ! is_user_logged_in() ) {
			return [];
		}

		// Get the attachment object.
		$attachment = get_post( $attachment_id );

		// Ensure it's an SVG file.
		if ( strpos( $attachment->post_mime_type, 'svg' ) !== false ) {

			// Check if the data is cached.
			$cache_key   = "svg_attachment_data_{$attachment_id}";
			$cached_data = get_transient( $cache_key );
			if ( $cached_data ) {
				return $cached_data;
			}

			$file_path   = get_attached_file( $attachment_id );
			$svg_content = file_get_contents( $file_path ); // phpcs:ignore

			if ( $svg_content ) {
				libxml_use_internal_errors( true );
				$xml = simplexml_load_string( $svg_content );
				if ( ! $xml ) {
					return null;
				}

				// Extract all attributes from the <svg> tag.
				$excluded_attrs       = [ 'viewBox', 'width', 'height' ];
				$svg_attributes_array = [];
				foreach ( $xml->attributes() as $attr_name => $attr_value ) {
					if ( in_array( (string) $attr_name, $excluded_attrs, true ) ) {
						continue;
					}
					$svg_attributes_array[ $attr_name ] = (string) $attr_value;
				}

				$view_box = isset( $xml['viewBox'] ) ? (string) $xml['viewBox'] : '';

				// Fallback to lowercase viewbox if needed.
				if ( empty( $view_box ) ) {
					$view_box = isset( $xml['viewbox'] ) ? (string) $xml['viewbox'] : '';
				}

				$inner_content = '';
				foreach ( $xml->children() as $child ) {
					$inner_content .= $child->asXML() . "\n";
				}

				$result = [
					'markup'     => wp_kses( $inner_content, svg_kses_extended_ruleset() ),
					'viewBox'    => esc_attr( $view_box ),
					'attributes' => $svg_attributes_array,
				];

				// Store result in transient for 24 hours.
				set_transient( $cache_key, $result, 24 * HOUR_IN_SECONDS );

				return $result;
			}
		}

		return [];
	}
}

// Initialize the class.
new DS_Icon_Library_Rest();
