<?php
/**
 * DS_GTM_Loader class file.
 *
 * This file is responsible for asynchronously loading the Google Tag Manager (GTM) script into the WordPress theme.
 * It integrates with the WordPress Customizer to allow users to enable or disable GTM async loading and provide a GTM ID.
 * Additionally, if the "GTM4WP" plugin (DuracellTomi's Google Tag Manager for WordPress) is installed and active, the class retrieves the GTM ID from the plugin's settings.
 * This ensures compatibility with either a manual GTM ID input via the Customizer or an automated one from the plugin.
 *
 * The class asynchronously loads the GTM script into the `<head>` section and injects a `noscript` iframe after the opening `<body>` tag for non-JS users.
 *
 * @package DS_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Check if the function exists.
if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

if ( ! class_exists( 'DS_GTM_Loader' ) ) {

	/**
	 * Class DS_GTM_Loader.
	 *
	 * Handles the asynchronous loading of Google Tag Manager (GTM) scripts.
	 * The class pulls configuration settings either from the WordPress Customizer or the GTM4WP plugin, if available.
	 * It ensures that the GTM script is added efficiently to the site and supports both JavaScript and non-JavaScript users by injecting the necessary GTM elements.
	 */
	class DS_GTM_Loader {

		/**
		 * Whether GTM include is enabled.
		 *
		 * @var bool
		 */
		public $is_enabled = false;

		/**
		 * Whether GTM async loading is enabled.
		 *
		 * @var bool
		 */
		public $is_async = false;

		/**
		 * Holds the GTM ID.
		 *
		 * @var string|null
		 */
		public $gtm_id = null;

		/**
		 * DS_GTM_Loader constructor.
		 *
		 * Sets the GTM ID and hooks the necessary actions.
		 */
		public function __construct() {
			// Set includes.
			$this->includes();

			// Add settings.
			add_action( 'customize_register', array( $this, 'add_settings' ), 10, 1 );

			// Set values.
			add_action( 'wp', array( $this, 'get_gtm_settings' ), 10 );
			add_action( 'wp', array( $this, 'set_gtm_id' ), 15 );

			// Output scripts.
			add_action( 'wp_head', array( $this, 'include_gtm_js_header' ), 50 );
			add_action( 'wp_body_open', array( $this, 'include_gtm_no_js_after_body' ), 1 );

			// Enqueue the custom JavaScript for controlling the visibility of GTM ID.
			add_action( 'customize_controls_enqueue_scripts', array( $this, 'enqueue_customizer_js' ) );
		}

		/**
		 * Includes the DS_Message_Customize_Control class for use in the
		 * Customizer.
		 *
		 * @return void
		 */
		public function includes() {
			$ds_message_customize_control = get_template_directory() . '/core/lib/ds-gtm-loader-inc/class-ds-message-customize-control.php';
			if ( file_exists( $ds_message_customize_control ) ) {
				require_once $ds_message_customize_control;
			}
		}

		/**
		 * Enqueues the custom JavaScript for the Customizer controls.
		 *
		 * This function registers and enqueues a script to handle the visibility
		 * and settings of the Google Tag Manager (GTM) ID within the WordPress
		 * Customizer interface.
		 *
		 * @return void
		 */
		public function enqueue_customizer_js() {
			wp_enqueue_script( 'ds-gtm-loader-customizer', get_template_directory_uri() . '/core/lib/ds-gtm-loader-inc/ds-gtm-loader-customizer.js', array( 'customize-controls' ), '1.0.0', true );
		}

		/**
		 * Adds settings for GTM Async Loading to the Customizer.
		 *
		 * Adds a section for GTM Async Loading, and adds settings for
		 * enabling/disabling GTM Async Loading and setting the GTM ID.
		 *
		 * @param WP_Customize_Manager $wp_customize The Customizer object.
		 */
		public function add_settings( $wp_customize ) {
			// Add a section for GTM Async Loading.
			$wp_customize->add_section(
				'ds_gtm_async_loading_section',
				array(
					'title'       => __( 'DS GTM Loading', 'dstheme' ),
					'description' => sprintf(
					/* translators: %1$s, %2$s, %3$s, %4$s, %5$s */
						__( 'You can enable adding GTM and it\'s async loading. You can set GTM ID below.%5$sIf you have %3$s%1$sGTM4WP - A Google Tag Manager (GTM) plugin%2$s%4$s activated we will use GTM ID from that plugin.', 'dstheme' ),
						'<a href="https://wordpress.org/plugins/duracelltomi-google-tag-manager/" target="_blank" rel="noopener noreferrer">',
						'</a>',
						'<strong>',
						'</strong>',
						'<br><br>',
					),
					'priority'    => 30,
				)
			);

			// Add setting for Enable/Disable GTM Loading.
			$wp_customize->add_setting(
				'ds_gtm_enable',
				array(
					'default'           => false,
					'sanitize_callback' => 'wp_validate_boolean',
					'transport'         => 'refresh',
				)
			);

			// Add Control for Enable/Disable GTM Loading (checkbox).
			$wp_customize->add_control(
				'ds_gtm_enable_control',
				array(
					'label'       => __( 'Enable GTM Loading', 'dstheme' ),
					'description' => __( 'Enables adding Google Tag Manager (GTM) to the website. GTM code will be added to the <code>&lt;head&gt;</code> and after the opening <code>&lt;body&gt;</code> tag.', 'dstheme' ),
					'section'     => 'ds_gtm_async_loading_section',
					'settings'    => 'ds_gtm_enable',
					'type'        => 'checkbox',
				)
			);

			// Add setting for Enable/Disable GTM Async Loading.
			$wp_customize->add_setting(
				'ds_gtm_async_enable',
				array(
					'default'           => false,
					'sanitize_callback' => 'wp_validate_boolean',
					'transport'         => 'refresh',
				)
			);

			// Add Control for Enable/Disable GTM Async Loading (checkbox).
			$wp_customize->add_control(
				'ds_gtm_async_enable_control',
				array(
					'label'       => __( 'Enable GTM Async Loading', 'dstheme' ),
					'description' => __( 'Enables async loading of GTM. Async loading can be good for your website speed as it will not block loading of other scripts.', 'dstheme' ),
					'section'     => 'ds_gtm_async_loading_section',
					'settings'    => 'ds_gtm_async_enable',
					'type'        => 'checkbox',
				)
			);

			if ( $this->is_gtm4wp_active() ) {
				// Add setting to add message.
				$wp_customize->add_setting(
					'ds_gtm_id_gtm4wp',
					array(
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
						'transport'         => 'refresh',
					)
				);

				// Add message.
				$wp_customize->add_control(
					new DS_Message_Customize_Control(
						$wp_customize,
						'ds_gtm_id_gtm4wp_control',
						array(
							'label'       => __( 'GTM will be loaded with GTM4WP plugin', 'dstheme' ),
							'description' => sprintf(
							/* translators: %s - link to the settings page */
								__( 'You can edit settings in <a href="%s">plugin settings</a>.', 'dstheme' ),
								admin_url( 'options-general.php?page=gtm4wp-settings' )
							),
							'section'     => 'ds_gtm_async_loading_section',
							'settings'    => 'ds_gtm_id_gtm4wp',
						)
					)
				);

				$wp_customize->add_setting(
					'ds_gtm_id_gtm4wp_async',
					array(
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
						'transport'         => 'refresh',
					)
				);

				// Add message.
				$wp_customize->add_control(
					new DS_Message_Customize_Control(
						$wp_customize,
						'ds_gtm_id_gtm4wp_async_control',
						array(
							'label'       => __( 'GTM ID will be loaded from GTM4WP', 'dstheme' ),
							'description' => sprintf(
							/* translators: %s - link to the settings page */
								__( 'You can add or edit GTM ID from <a href="%s">plugin settings</a>.', 'dstheme' ),
								admin_url( 'options-general.php?page=gtm4wp-settings' )
							),
							'section'     => 'ds_gtm_async_loading_section',
							'settings'    => 'ds_gtm_id_gtm4wp_async',
						)
					)
				);
			} else {
				// Add setting for GTM ID.
				$wp_customize->add_setting(
					'ds_gtm_id',
					array(
						'default'           => '',
						'sanitize_callback' => array( $this, 'sanitize_gtm_id' ),
						'transport'         => 'refresh',
					)
				);

				// Add Control for GTM ID (input).
				$wp_customize->add_control(
					'ds_gtm_id_control',
					array(
						'label'       => __( 'GTM ID', 'dstheme' ),
						'description' => __( 'Enter your Google Tag Manager ID, e.g., GTM-XXXXXX. You can enter multiple GTM IDs separated by a comma.', 'dstheme' ),
						'section'     => 'ds_gtm_async_loading_section',
						'settings'    => 'ds_gtm_id',
						'type'        => 'text',
					)
				);
			}
		}

		/**
		 * Sanitizes and validates the GTM ID.
		 *
		 * Ensures the GTM ID contains only letters, digits, and hyphens,
		 * and is a maximum of 10 characters.
		 *
		 * @param string $gtm_id The GTM ID input from the Customizer.
		 *
		 * @return string The sanitized GTM ID or an empty string if invalid.
		 */
		public function sanitize_gtm_id( $gtm_id ) {
			// Split the input by commas and trim each part.
			$gtm_ids = array_map( 'trim', explode( ',', $gtm_id ) );

			// Define the validation pattern for a single GTM ID.
			$pattern = '/^[A-Z]{1,3}-[A-Z0-9]{1,10}$/';

			// Sanitize each GTM ID.
			$gtm_ids = array_map( 'sanitize_text_field', $gtm_ids );
			// Remove any empty or invalid GTM IDs.
			$gtm_ids = array_filter( $gtm_ids );

			// Validate each GTM ID.
			foreach ( $gtm_ids as $id ) {
				if ( ! preg_match( $pattern, $id ) ) {
					// If any ID is invalid, return an empty string (or handle as needed).
					return '';
				}
			}

			// Return the sanitized and re-joined GTM IDs.
			return implode( ',', $gtm_ids );
		}

		/**
		 * Sets whether GTM async loading is enabled.
		 *
		 * @return void
		 */
		public function get_gtm_settings() {
			$this->is_enabled = get_theme_mod( 'ds_gtm_enable' ) ?: false;
			$this->is_async   = get_theme_mod( 'ds_gtm_async_enable' ) ?: false;

			if ( $this->is_enabled && ! $this->is_async && $this->is_gtm4wp_active() ) {
				$this->is_enabled = false;
			}
		}

		/**
		 * Sets the GTM ID based on plugin availability or custom field.
		 *
		 * Checks if the GTM plugin is active and retrieves the GTM ID.
		 * If the plugin is not active, retrieves the GTM ID from ACF options.
		 *
		 * @return void
		 */
		public function set_gtm_id() {
			$gtm_id = null;

			// Check if GTM loading is enabled.
			if ( empty( $this->is_enabled ) ) {
				return;
			}

			// Check if the GTM4WP plugin is active.
			if ( $this->is_gtm4wp_active() ) {
				global $gtm4wp_options;
				if ( defined( 'GTM4WP_OPTION_GTM_CODE' ) && ! empty( $gtm4wp_options[ GTM4WP_OPTION_GTM_CODE ] ) ) {
					$gtm_id = $gtm4wp_options[ GTM4WP_OPTION_GTM_CODE ];
				}

				// If the GTM ID is set, switch off the placement from GTM4WP.
				if ( ! empty( $gtm_id ) && defined( 'GTM4WP_OPTION_GTM_PLACEMENT' ) && defined( 'GTM4WP_PLACEMENT_OFF' ) ) {
					$gtm4wp_options[ GTM4WP_OPTION_GTM_PLACEMENT ] = GTM4WP_PLACEMENT_OFF;
				}
			} else {
				// Fallback to option from customizer.
				$gtm_id = get_theme_mod( 'ds_gtm_id' ) ?: null;
			}

			if ( $gtm_id ) {
				$this->gtm_id = $gtm_id;
			}
		}

		/**
		 * Checks if the GTM4WP plugin is active.
		 *
		 * @return bool True if the plugin is active, false otherwise.
		 */
		protected function is_gtm4wp_active() {
			return is_plugin_active( 'duracelltomi-google-tag-manager/duracelltomi-google-tag-manager-for-wordpress.php' );
		}

		/**
		 * Checks if the GTM loading is enabled.
		 *
		 * Checks if we are not in the admin area, the GTM loading is enabled,
		 * and the GTM ID is set.
		 *
		 * @return bool True if the loading is enabled, false otherwise.
		 */
		protected function is_loading_enabled() {
			return ! is_admin() && ! empty( $this->is_enabled ) && ! empty( $this->gtm_id );
		}

		/**
		 * Checks if async loading of GTM is enabled.
		 *
		 * Checks if the GTM ID is set and async loading is enabled.
		 *
		 * @return bool True if async loading of GTM is enabled, false otherwise.
		 */
		protected function is_async_loading() {
			return $this->is_loading_enabled() && ! empty( $this->is_async );
		}

		/**
		 * Includes the GTM script in the header.
		 *
		 * Outputs the GTM script in the <head> section if the GTM ID is set.
		 *
		 * @return void
		 */
		public function include_gtm_js_header() {
			// Check if we are not in the admin area.
			if ( is_admin() || wp_doing_ajax() ) {
				return;
			}

			// Check if GTM loading is enabled.
			if ( ! $this->is_loading_enabled() ) {
				return;
			}

			// Check if async loading is enabled.
			if ( $this->is_async_loading() ) {
				// Output async GTM script.
				$this->include_gtm_js_header_async();
			} else {
				// Split the GTM IDs into an array.
				$gtm_ids = array_map( 'trim', explode( ',', $this->gtm_id ) );
				if ( empty( $gtm_ids ) || ! is_array( $gtm_ids ) ) {
					return;
				}
				// Output the `dataLayer` initialization only once.
				?>
				<script>
					window.dataLayer = window.dataLayer || [];
				</script>
				<?php
				// Output standard GTM scripts for each ID.
				foreach ( $gtm_ids as $gtm_id ) :
					?>
					<!-- Google Tag Manager -->
					<script>
						(function (w, d, s, l, i) {
							w[l] = w[l] || [];
							w[l].push({'gtm.start': new Date().getTime(), event: 'gtm.js'});
							var f = d.getElementsByTagName(s)[0], j = d.createElement(s),
								dl = l != 'dataLayer' ? '&l=' + l : '';
							j.async = true;
							j.src = 'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
							f.parentNode.insertBefore(j, f);
						})(window, document, 'script', 'dataLayer', '<?php echo esc_attr( $gtm_id ); ?>');
					</script>
					<!-- End Google Tag Manager -->
				<?php
				endforeach;
			}
		}

		/**
		 * Includes the GTM script in the header, loaded asynchronously.
		 *
		 * Outputs the GTM script in the <head> section if the GTM ID is set.
		 * The script is loaded asynchronously and waits for the script to be
		 * loaded before initializing the dataLayer and calling gtag.
		 *
		 * @return void
		 */
		public function include_gtm_js_header_async() {
			// Check if we are not in the admin area.
			if ( is_admin() || wp_doing_ajax() ) {
				return;
			}
			?>
			<script>
				// PHP array of GTM IDs.
				const gtmIds = <?php echo wp_json_encode( array_map( 'trim', explode( ',', $this->gtm_id ) ) ); ?>;

				function loadGTM(GTMId) {
					return new Promise((resolve, reject) => {
						// Create the script element for GTM.
						var script = document.createElement('script');
						script.async = true;
						script.src = 'https://www.googletagmanager.com/gtm.js?id=' + GTMId;
						script.onload = () => {
							console.log('GTM script loaded');
							resolve();
						};
						script.onerror = reject;
						document.head.appendChild(script);
					});
				}

				async function initGTM(GTMId) {
					try {
						await loadGTM(GTMId);
						window.dataLayer = window.dataLayer || [];

						function gtag() {
							dataLayer.push(arguments);
						}

						gtag('js', new Date());
						gtag('config', GTMId);
					} catch (error) {
						console.error('Failed to load GTM: ', error);
					}
				}

				// Initialize GTM for all IDs.
				if (gtmIds && gtmIds.length > 0) {
					gtmIds.forEach(initGTM);
				}
			</script>
			<?php
		}

		/**
		 * Includes the GTM noscript iframe after the opening <body> tag.
		 *
		 * Outputs a noscript iframe for GTM if the GTM ID is set.
		 *
		 * @return void
		 */
		public function include_gtm_no_js_after_body() {
			if ( ! $this->is_loading_enabled() ) {
				return;
			}

			$gtm_ids = array_map( 'trim', explode( ',', $this->gtm_id ) );
			if ( empty( $gtm_ids ) || ! is_array( $gtm_ids ) ) {
				return;
			}
			foreach ( $gtm_ids as $gtm_id ) :
				?>
				<!-- No Script Tag -->
				<noscript>
					<iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_attr( $gtm_id ); ?>"
							height="0" width="0" style="display:none;visibility:hidden"></iframe>
				</noscript>
				<!-- End No Script Tag -->
			<?php
			endforeach;
		}
	}

	new DS_GTM_Loader();
}
