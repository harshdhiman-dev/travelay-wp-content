<?php
/**
 * Main Plugin class
 *
 * @package Ds_Gf_Tracking
 * @since 1.0.0
 */

// phpcs:disable WordPress.Security.NonceVerification.Recommended

defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'DS_GF_Tracking' ) ) :

	/**
	 * Class responsible for providing addon functionality
	 */
	class DS_GF_Tracking {

		/**
		 * Holds the class instance
		 *
		 * @var object
		 */
		private static $instance;


		/**
		 * Holds the html_variables
		 *
		 * @var array
		 */
		private static $html_variables = array(
			'utm'     => array(
				'title'             => 'UTM Data',
				'description'       => '',
				'show'              => true,
				'minimize_if_empty' => true,
				'list'              => array(
					'ds_utm_source'   => array(
						'label'        => 'UTM Source',
						'value'        => '',
						'export_label' => 'UTM Source',
						'type'         => 'text',
					),
					'ds_utm_medium'   => array(
						'label'        => 'UTM Medium',
						'value'        => '',
						'export_label' => 'UTM Medium',
						'type'         => 'text',
					),
					'ds_utm_campaign' => array(
						'label'        => 'UTM Campaign',
						'value'        => '',
						'export_label' => 'UTM Campaign',
						'type'         => 'text',
					),
					'ds_utm_term'     => array(
						'label'        => 'UTM Term',
						'value'        => '',
						'export_label' => 'UTM Term',
						'type'         => 'text',
					),
					'ds_utm_content'  => array(
						'label'        => 'UTM Content',
						'value'        => '',
						'export_label' => 'UTM Content',
						'type'         => 'text',
					),
					'ds_utm_id'       => array(
						'label'        => 'UTM ID',
						'value'        => '',
						'export_label' => 'UTM ID',
						'type'         => 'text',
					),
				),
			),

			'session' => array(
				'title' => 'HTTP Referrer',
				'show'  => true,
				'list'  => array(
					'ds_referer' => array(
						'label'        => 'Website Referrer',
						'value'        => '',
						'export_label' => 'Website Referrer',
						'type'         => 'textarea',
					),
				),
			),
		);

		/**
		 * Get class instance
		 */
		public static function get_instance() {

			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor
		 */
		private function __construct() {
		}

		/**
		 * Run the plugin
		 *
		 * @since    1.0.0
		 */
		public function run() {
			$this->register_hooks();
		}

		/**
		 * Initialize hooks
		 */
		public function register_hooks() {
			// Maybe show notice
			add_action( 'admin_notices', array( $this, 'maybe_show_not_active_notice' ) );

			// add_action( 'template_redirect', array( $this, 'collect_additional_form_data' ) );
			// add_action( 'gform_pre_render', array( $this, 'collect_additional_form_data_on_form' ), 10, 1 );
			add_action( 'gform_entry_created', array( $this, 'store_additional_form_data' ), 10, 2 );

			// register entry meta
			add_filter( 'gform_entry_meta', array( $this, 'gform_entry_meta' ), 10, 2 );

			// GF Admin - entries page
			add_filter( 'gform_entry_list_columns', array( $this, 'filter_gform_entry_list_columns' ), 10, 2 );
			add_filter( 'gform_entries_column_filter', array( $this, 'filter_gform_entries_column_filter' ), 10, 5 );

			// GF Admin - entry page
			add_filter( 'gform_entry_detail_meta_boxes', array( $this, 'filter_gform_entry_detail_meta_boxes' ), 10, 3 );

			// GF Admin - export
			add_filter( 'gform_leads_before_export', array( $this, 'filter_gform_leads_before_export' ), 10, 3 );

			// Add ds_utm_source to email using gform_notification
			add_filter( 'gform_notification', array( $this, 'add_ds_utm_source_to_notification' ), 10, 3 );

            // Add ds_utm to merge tags, these are used in different contexts based on how the merge tag selector is presented.
			add_filter( 'gform_custom_merge_tags',  array( $this, 'ds_custom_merge_tags' ), 10, 4 );
			add_filter( 'gform_replace_merge_tags', array( $this, 'ds_custom_replace_merge_tags' ), 9999, 7 );
		}

		/**
		 * Display an error notice if the required Gravity Forms plugin is not active.
		 *
		 * @return void
		 */
		public function maybe_show_not_active_notice() {
			if ( ! class_exists( 'GFCommon' ) ) {
				?>
                <div class="notice notice-error">
                    <p><?php esc_html_e( 'DS Gravity Forms Tracking Addon requires Gravity Forms plugin to be active. Please activate Gravity Forms to enable tracking features.', 'ds-gf-tracking' ); ?></p>
                </div>
				<?php
			}
		}

		/**
		 * Get referrer based on $_SERVER
		 */
		public static function get_page_referer() {
			$protocol = self::is_https() ? 'https' : 'http';
			$http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ''; // phpcs:ignore
			$request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''; // phpcs:ignore
			$http_referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ''; // phpcs:ignore
			$host = $protocol . '://' . $http_host;
			$url = $protocol . '://' . $http_host . $request_uri;
			$current_url = strtok( $url, '?' );
			$referer_url = ( ! empty( $http_referer ) && $host !== $http_referer) ? $http_referer : '';

			if ( ! $referer_url ) {
				$referer_url = $current_url;
			}

			if ( $current_url === $referer_url ) {
				$page_value = esc_html__( 'Direct', 'ds-gf-tracking' );
			} else {
				$page_value = sanitize_text_field( $referer_url );
			}

			return $page_value;
		}

		/**
		 * Retrieve cookie data.
		 *
		 * @return array Decoded cookie data including UTM and referrer information.
		 */
		public function get_cookie_data() {
			$cookie_data = array(
				'utm_data'     => array(),
				'referer_data' => '',
			);

			if ( isset( $_COOKIE['ds_gf_data'] ) ) {
				// Decode the cookie data from the cookie.
				$decoded_params = stripslashes( base64_decode( $_COOKIE['ds_gf_data'] ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

				// Decode JSON.
				$ds_gf_data = json_decode( $decoded_params, true );

				$utm_params = isset( $ds_gf_data['utm_data'] ) ? $ds_gf_data['utm_data'] : array();
				// $page_referer = isset( $ds_gf_data['referer_data'] ) ? sanitize_text_field( $ds_gf_data['referer_data'] ) : esc_html__( 'Direct', 'ds-gf-tracking' );
				$page_referer = '';

				if( isset( $ds_gf_data['referer_data'] ) ) {
					$page_referer = esc_html__( 'Organic', 'ds-gf-tracking' );
					if( !empty( $ds_gf_data['referer_data'] ) ) {
						$page_referer =  sanitize_text_field( $ds_gf_data['referer_data'] );
					}
				} else {
					$page_referer = esc_html__( 'Direct', 'ds-gf-tracking' );
				}

				$cookie_data = array(
					'utm_data'     => $utm_params,
					'referer_data' => $page_referer,
				);

			}

			return $cookie_data;
		}

		/**
		 * Collect additional form data only when a form is being rendered on the page.
		 *
		 * @param array $form The form object
		 * @return array The unmodified form object
		 */
		public function collect_additional_form_data_on_form( $form ) {
			// This function will only run when a GF form is present
			$this->collect_additional_form_data();

			// Return the form unmodified
			return $form;
		}

		/**
		 * Collect additional form data such as UTM parameters from the query string and page referer,
		 * and store this data in a cookie if the request is not from an admin page.
		 *
		 * UTM data is updated if there is an override only to ensure passing first touch data
		 *
		 * @return void
		 */
		public function collect_additional_form_data() {
			$cookie_data = self::get_cookie_data();
			$page_referer = self::get_page_referer();

			if ( ! is_admin() ) {
				if ( ! empty( $_GET ) ) {
					// Check if UTM parameters exist in the query string and update if exists
					if ( isset( $_GET['utm_source'] ) || isset( $_GET['utm_medium'] ) || isset( $_GET['utm_campaign'] ) || isset( $_GET['utm_content'] ) || isset( $_GET['utm_term'] ) || isset( $_GET['utm_id'] ) ) {
						// Combine UTM parameters into an array
						$utm_params = array(
							'utm_source'   => isset( $_GET['utm_source'] ) ? sanitize_text_field( $_GET['utm_source'] ) : 'Organic',
							'utm_medium'   => isset( $_GET['utm_medium'] ) ? sanitize_text_field( $_GET['utm_medium'] ) : 'N/A',
							'utm_campaign' => isset( $_GET['utm_campaign'] ) ? sanitize_text_field( $_GET['utm_campaign'] ) : 'N/A',
							'utm_id'       => isset( $_GET['utm_id'] ) ? sanitize_text_field( $_GET['utm_id'] ) : 'N/A',
							'utm_term'     => isset( $_GET['utm_term'] ) ? sanitize_text_field( $_GET['utm_term'] ) : 'N/A',
							'utm_content'  => isset( $_GET['utm_content'] ) ? sanitize_text_field( $_GET['utm_content'] ) : 'N/A',
						);

						if ( $utm_params ) {
							$cookie_data['utm_data'] = $utm_params;
						}
					}
				}

				if ( $page_referer ) {
					// We want to update each time when set
					$cookie_data['referer_data'] = $page_referer;
				}

				// Serialize
				$encoded_params = wp_json_encode( $cookie_data );

				// Encode
				$cookie_value = base64_encode( $encoded_params ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

				// Set the data in a cookie
				setcookie( 'ds_gf_data', $cookie_value, time() + 3600, COOKIEPATH, COOKIE_DOMAIN );
			}
		}

		/**
		 * Stores additional form data into entry meta.
		 *
		 * @param array $entry The entry array containing form submission data.
		 * @param array $form The form array containing form structure and details.
		 *
		 * @return void
		 */
		public function store_additional_form_data( $entry, $form ) {
			$cookie_data = self::get_cookie_data();

			// Extract data
			$utm_params = isset( $cookie_data['utm_data'] ) ? $cookie_data['utm_data'] : array();
			$page_referer = isset( $cookie_data['referer_data'] ) ? sanitize_text_field( $cookie_data['referer_data'] ) : esc_html__( 'Direct', 'ds-gf-tracking' );

			if ( isset( $entry['id'] ) ) {
				if ( $utm_params && is_array( $utm_params ) ) {
					foreach ( $utm_params as $key => $value ) {
						$meta_key = sprintf( 'ds_%s', $key );
						$sanitized_value = esc_html( $value );

						// Store UTM meta.
						gform_update_meta( $entry['id'], $meta_key, $sanitized_value );
					}
				}

				// Store referrer meta.
				gform_update_meta( $entry['id'], 'ds_referer', $page_referer );
			}
		}

		/**
		 * Add entry list columns to include custom UTM and referer data for Gravity Forms.
		 *
		 * @param array $columns Existing columns in the Gravity Forms entry list.
		 * @param int   $form_id ID of the form being filtered.
		 */
		public static function filter_gform_entry_list_columns( $columns, $form_id ) {
			$original_column_selector = '';
			if ( isset( $columns['column_selector'] ) ) {
				$original_column_selector = $columns['column_selector'];
				unset( $columns['column_selector'] );
			}

			$columns['ds_utm_source'] = esc_html__( 'UTM Source', 'ds-gf-tracking' );
			$columns['ds_utm_medium'] = esc_html__( 'UTM Medium', 'ds-gf-tracking' );
			$columns['ds_utm_campaign'] = esc_html__( 'UTM Campaign', 'ds-gf-tracking' );
			$columns['ds_utm_term'] = esc_html__( 'UTM Term', 'ds-gf-tracking' );
			$columns['ds_utm_content'] = esc_html__( 'UTM Content', 'ds-gf-tracking' );
			$columns['ds_utm_id'] = esc_html__( 'UTM ID', 'ds-gf-tracking' );
			$columns['ds_referer'] = esc_html__( 'Referer', 'ds-gf-tracking' );

			if ( $original_column_selector ) {
				$columns['column_selector'] = $original_column_selector;
			}

			return $columns;
		}

		/**
		 * Populate Entry List columns
		 *
		 * @param string $value contains the value
		 * @param int    $form_id contains the form id
		 * @param string $column contains the column name
		 * @param array  $entry contains the entry data
		 * @param string $query_string contains the query_string
		 */
		public static function filter_gform_entries_column_filter( $value, $form_id, $column, $entry, $query_string ) {
			try {
				switch ( $column ) :
					case 'ds_utm_source':
					case 'ds_utm_medium':
					case 'ds_utm_campaign':
					case 'ds_utm_term':
					case 'ds_utm_content':
					case 'ds_utm_id':
						$value = self::get_entry_meta( $entry['id'], $column );
						break;

					case 'ds_referer':
						$value = self::get_entry_meta( $entry['id'], 'ds_referer' );
						break;

					default:
						break;
				endswitch;

			} catch ( \Exception $e ) {
				// remain silent
				self::log( $e );
			}

			return $value;
		}

		/**
		 * Add View Entry Metabox
		 *
		 * @param array $meta_boxes contains the meta_boxes
		 * @param array $entry contains the entry data
		 * @param array $form contains the form data
		 */
		public static function filter_gform_entry_detail_meta_boxes( $meta_boxes, $entry, $form ) {
			$meta_boxes['ds_gf_tracking'] = array(
				'title'    => esc_html__( 'DS Tracking Data', 'ds-gf-tracking' ),
				'callback' => array( __CLASS__, 'render_entry_metabox_content' ),
				'context'  => 'side',
				'priority' => 'high',
			);

			return $meta_boxes;
		}

		/**
		 * Render View Entry Metabox
		 *
		 * @param array $args contains the arguments
		 */
		public static function render_entry_metabox_content( $args ) {
			$form = $args['form'];
			$entry = $args['entry'];

			echo self::get_metabox_content($entry); //phpcs:ignore
		}

		/**
		 * Populate View Entry Metabox
		 *
		 * @param array $entry contains the entry data
		 */
		public static function get_metabox_content( $entry = array() ) {
			$html_variables = self::$html_variables;
			$data = array();
			$html = '';

			if ( $html_variables ) {
				foreach ( $html_variables as $group_key => $group ) :

					if ( isset( $group['show'] ) && false === $group['show'] ) {
						continue;
					}

					if ( isset( $group['title'] ) ) {
						$html .= '<h3>' . wp_kses(
								$group['title'],
								array(
									'span' => array( 'style' => array() ),
								)
							) . '</h3>';
					}

					if ( isset( $group['description'] ) ) {
						$html .= '<p style="margin-top: 0">' . esc_html( $group['description'] ) . '</p>';
					}

					if ( ! empty( $group['list'] ) ) :
						$i = 0;
						$is_empty = false;

						foreach ( $group['list'] as $row_key => $row ) :
							$entry_value = self::get_entry_meta( $entry['id'], $row_key );

							if ( $entry_value ) {
								$row['value'] = $entry_value;
							}

							if ( 0 == $i && ! empty( $group['minimize_if_empty'] ) && (false === $row['value'] || '' === $row['value']) ) :
								$is_empty = true;
								break;
							endif;

							$html .= '<div><b>' . esc_html( $row['label'] ) . '</b></div>';

							if ( isset( $row['description'] ) ) :
								$html .= sprintf(
									'<div style="margin: 0 0 8px"><i>%1$s</i></div>',
									esc_html( $row['description'] )
								);
							endif;

							if ( isset( $row['type'] ) && 'textarea' === $row['type'] && ! empty( $row['value'] ) ) :
								$html .= sprintf(
									'<textarea rows="3" style="width:100%%; max-width:100%%; margin: 4px 0 4px; pointer-events:auto!important; cursor:auto!important" readonly>%1$s</textarea>',
									esc_html( $row['value'] )
								);
							else :
								$html .= sprintf(
									'<input type="text" value="%1$s" style="width:100%%; max-width:100%%; margin: 4px 0 8px" readonly>',
									esc_attr( $row['value'] )
								);
							endif;
							++$i;
						endforeach;

						if ( $is_empty ) :
							$html .= '<div>No value recorded.</div>';
						endif;

						$html .= '<hr>';
					endif;
				endforeach;
			}

			return $html;
		}

		/**
		 * Add metadata so it can be exported
		 *
		 * @param array $entry_meta contains the entry_meta
		 * @param int   $form_id contains the form_id
		 */
		public static function gform_entry_meta( $entry_meta, $form_id ) {
			try {
				$meta_whitelist = array();

				if ( self::$html_variables ) {
					foreach ( self::$html_variables as $main_key => $values ) {
						$list_items = array();
						if ( isset( $values['list'] ) ) {
							$list_items = $values['list'];
						}

						if ( ! empty( $list_items ) ) {
							foreach ( $list_items as $item_key => $item_values ) {
								$meta_whitelist[ $item_key ] = $item_values;
							}
						}
					}
				}

				if ( empty( $meta_whitelist ) ) :
					return $entry_meta;
				endif;

				foreach ( $meta_whitelist as $meta_key => $meta ) :
					$is_numeric = false;
					if ( isset( $meta['type'] ) ) :
						switch ( $meta['type'] ) :
							case 'timestamp':
							case 'integer':
								$is_numeric = true;
								break;
						endswitch;
					endif;

					$entry_meta[ $meta_key ] = array(
						'label'             => 'DS | ' . $meta['label'],
						'is_numeric'        => $is_numeric,
						'is_default_column' => false,
					);

				endforeach;

			} catch ( \Exception $e ) {

				// remain silent.
				self::log( $e );
			}

			return $entry_meta;
		}

		/**
		 * Load data before export
		 *
		 * @param array $entries contains the entries
		 * @param array $form contains the form data
		 * @param mixed $paging contains the paging
		 */
		public static function filter_gform_leads_before_export( $entries, $form, $paging ) {
			try {
				if ( count( $entries ) ) :
					$export_blank_setting = 'N/A';
					foreach ( $entries as $entry_index => $entry ) :
						if ( ! empty( $entry['id'] ) && self::$html_variables ) :
							foreach ( self::$html_variables as $group_key => $group ) :
								if ( ! empty( $group['list'] ) ) :
									foreach ( $group['list'] as $row_key => $row ) :
										$entry_value = self::get_entry_meta( $entry['id'], $row_key );
										if ( $entry_value ) :
											$entries[ $entry_index ][ $row_key ] = $entry_value;
										else :
											$entries[ $entry_index ][ $row_key ] = $export_blank_setting;
										endif;
									endforeach;
								endif;
							endforeach;
						endif;
					endforeach;
				endif;

			} catch ( \Exception $e ) {
				// remain silent.
				self::log( $e );

			}

			return $entries;
		}

		/**
		 * Add ds_utm_source to notification emails.
		 *
		 * @param   array  $notification An array of properties which make up a notification object
		 * @param   array  $form The form object for which the notification is being sent
		 * @param   mixed  $entry The entry object for which the notification is being sent
		 */
		public static function add_ds_utm_source_to_notification( $notification, $form, $entry ) {
			// Check if this is the admin notification email
			if ( isset( $notification['name'] ) && $notification['name'] === 'Admin Notification' ) {
				$sanitized_value = 'Organic'; // Default value

				// Check for the ds_gf_data cookie
				if ( isset( $_COOKIE['ds_gf_data'] ) && ! empty( $_COOKIE['ds_gf_data'] ) ) {
					// Decode the cookie value
					$decoded_params = base64_decode( stripslashes( $_COOKIE['ds_gf_data'] ) );

					// Decode JSON data
					$utm_params = json_decode( $decoded_params, true );

					// Validate and sanitize UTM source
					if ( is_array( $utm_params ) && isset( $utm_params['utm_data']['utm_source'] ) ) {
						$sanitized_value = esc_html( $utm_params['utm_data']['utm_source'] );
					}
				}

				// Append to the notification message
				$notification['message'] .= "\n\nSource: " . $sanitized_value;
			}

			return $notification;
		}

        /**
		 * Add ds_utm_ fields to merge tags.
		 *
		 * @param   array  $merge_tags The custom merge tags.
         * @param   int  $form_id The ID of the current form.
		 * @param   object  $fields An array of fields objects.
		 * @param   string|int  $element_id The ID of the input field.
         */
		public static function ds_custom_merge_tags( $merge_tags, $form_id, $fields, $element_id ) {
			$utm_tags = [
				'Source'   => 'source',
				'Medium'   => 'medium',
				'Campaign' => 'campaign',
				'Term'     => 'term',
				'Content'  => 'content',
				'ID'       => 'id',
			];

			foreach ($utm_tags as $label => $key) {
				$merge_tags[] = [
					'label' => "UTM $label",
					'tag'   => "{ds_utm_$key}",
				];
			}

			return $merge_tags;
		}

		public function ds_custom_replace_merge_tags( $text, $form, $entry, $url_encode, $esc_html, $nl2br, $format ) {
			$utm_tags = array(
				'ds_utm_source',
				'ds_utm_medium',
				'ds_utm_campaign',
				'ds_utm_term',
				'ds_utm_content',
				'ds_utm_id',
				'ds_referer',
			);
			foreach ( $utm_tags as $tag ) {
				if ( str_contains( $text, '{' . $tag . '}' ) ) {
					$entry_meta_value = self::get_entry_meta( $entry['id'], $tag );
					$text             = str_replace( '{' . $tag . '}', $entry_meta_value, $text );
				}
			}

			return $text;
		}

		/**
		 * Helper to get entry meta
		 *
		 * @param int    $entry_id contains the entry id
		 * @param string $meta_key contains the meta key
		 */
		public static function get_entry_meta( $entry_id, $meta_key ) {
			$column_value = gform_get_meta( $entry_id, $meta_key );

			return esc_html( $column_value ? $column_value : 'N/A' );
		}

		/**
		 * Helper to determine are we using HTTPS
		 */
		public static function is_https() {
			return (!empty($_SERVER['HTTPS']) && 'off' !== $_SERVER['HTTPS']) || 443 == $_SERVER['SERVER_PORT']; //phpcs:ignore
		}

		/**
		 * Helper function to output logs to debug.log
		 *
		 * WP_DEBUG should be enabled, recommended configuration in wp-config.php:
		 * define( 'WP_DEBUG', true );
		 * define( 'WP_DEBUG_LOG', true );
		 * define( 'WP_DEBUG_DISPLAY', false );
		 *
		 * @param mixed $log Variable containing data to output.
		 */
		public static function log( $log ) {
			if ( true === WP_DEBUG ) {
				if ( is_array( $log ) || is_object( $log ) ) {
					error_log(print_r($log, true)); // phpcs:ignore
				} elseif ( is_bool( $log ) ) {
					error_log($log ? 'true' : 'false'); // phpcs:ignore
				} else {
					error_log($log); // phpcs:ignore
				}
			}
		}
	}

endif;