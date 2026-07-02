<?php
/**
 * DST Locations Explorer — render.php
 * Converted from ACF block (travelay-locations) to native Gutenberg render.
 *
 * @package DST\Blocks\ds_blocks
 * @var array    $attributes
 * @var WP_Block $block
 */

$heading          = $attributes['heading']           ?? 'Find a Travelay™ Counter Near You';
$show_board       = (bool) ( $attributes['showDepartureBoard'] ?? true );
$show_globe       = (bool) ( $attributes['showGlobe']          ?? true );
$show_search      = (bool) ( $attributes['showSearch']         ?? true );
$show_filters     = (bool) ( $attributes['showFilters']        ?? true );
$show_svc_filters = (bool) ( $attributes['showServiceFilters'] ?? true );
$show_directions  = (bool) ( $attributes['showMapDirections']  ?? true );
$show_whatsapp    = (bool) ( $attributes['showWhatsapp']       ?? true );
$whatsapp_number  = preg_replace( '/\D+/', '', (string) ( $attributes['whatsappNumber'] ?? '18885262920' ) );
$show_stats       = (bool) ( $attributes['showStats']          ?? true );
$show_map         = (bool) ( $attributes['showMap']            ?? true );
$show_cta         = (bool) ( $attributes['showCta']            ?? true );
$default_phone    = $attributes['defaultPhone']     ?? '+1 877 721 0410';
$cta_url          = esc_url_raw( $attributes['ctaUrl']  ?? '/' );
$cta_title        = $attributes['ctaTitle']         ?? "Can't find a counter nearby?";
$cta_text         = $attributes['ctaText']          ?? 'Our TravelayGents™ answer in two rings — book by phone, chat, or online from anywhere.';
$mobile_view      = $attributes['mobileDefaultView'] ?? 'list';
$mobile_map_h     = (int) ( $attributes['mobileMapHeight']   ?? 55 );
$sticky_search    = (bool) ( $attributes['stickySearch']      ?? true );
$card_density     = $attributes['cardDensity']       ?? 'comfortable';
$map_height       = (int) ( $attributes['mapHeightDesktop']   ?? 560 );
$hero_image       = $attributes['heroImage']         ?? [];

$hero_image_url = $hero_image['url'] ?? '';
$hero_image_alt = $hero_image['alt'] ?? '';

$anchor      = ! empty( $attributes['anchor'] ) ? $attributes['anchor'] : '';
$anchor_attr = ! empty( $anchor ) ? ' id="' . esc_attr( $anchor ) . '"' : '';

// ── Load locations from CPT ──────────────────────────────────────────────────
$locations   = array();
$countries   = array();
$board_codes = array();

if ( post_type_exists( 'travelay_location' ) ) {
	$posts = get_posts( array(
		'post_type'      => 'travelay_location',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'orderby'        => 'title',
		'order'          => 'ASC',
	) );

	foreach ( $posts as $post ) {
		$meta = get_post_meta( $post->ID );
		$get  = function( $key ) use ( $meta ) {
			return $meta[ $key ][0] ?? '';
		};

		$services = array();
		if ( $get( 'tl_walk_in' ) )      $services[] = 'walk_in';
		if ( $get( 'tl_airport' ) )       $services[] = 'airport';
		if ( $get( 'tl_phone_247' ) )     $services[] = 'phone_247';

		$country_code = strtoupper( trim( $get( 'tl_country_code' ) ) );
		$airport_code = strtoupper( trim( $get( 'tl_airport_code' ) ) );
		$lat          = floatval( $get( 'tl_lat' ) );
		$lng          = floatval( $get( 'tl_lng' ) );

		$loc = array(
			'id'           => $post->ID,
			'slug'         => $post->post_name,
			'title'        => $post->post_title,
			'address'      => $get( 'tl_address' ),
			'city'         => $get( 'tl_city' ),
			'country'      => $get( 'tl_country' ),
			'country_code' => $country_code,
			'airport_code' => $airport_code,
			'phone'        => $get( 'tl_phone' ) ?: $default_phone,
			'email'        => $get( 'tl_email' ),
			'hours'        => $get( 'tl_hours' ),
			'terminal'     => $get( 'tl_terminal' ),
			'lat'          => $lat,
			'lng'          => $lng,
			'services'     => $services,
			'has_airport'  => in_array( 'airport', $services, true ),
			'is_247'       => in_array( 'phone_247', $services, true ),
			'image_url'    => get_the_post_thumbnail_url( $post->ID, 'medium' ) ?: '',
			'open_label'   => $get( 'tl_open_label' ),
			'open_status'  => $get( 'tl_open_status' ) ?: 'unknown',
		);

		$locations[] = $loc;

		if ( $country_code && ! isset( $countries[ $country_code ] ) ) {
			$countries[ $country_code ] = $get( 'tl_country' ) ?: $country_code;
		}
		if ( $airport_code ) {
			$board_codes[] = $airport_code;
		}
	}
}

if ( empty( $board_codes ) ) {
	$board_codes = array( 'ATQ', 'IAD', 'AUS', 'MDW', 'LAS', 'BNA' );
}

// ── Build JS config ──────────────────────────────────────────────────────────
$config = array(
	'locations'    => $locations,
	'defaultPhone' => $default_phone,
	'ctaUrl'       => $cta_url,
	'pageUrl'      => esc_url_raw( get_permalink() ?: home_url( '/our-locations/' ) ),
	'mapStyle'     => 'https://basemaps.cartocdn.com/gl/voyager-gl-style/style.json',
	'mobileView'   => $mobile_view,
	'isEditor'     => false,
	'features'     => array(
		'mapDirections' => $show_directions,
		'whatsapp'      => $show_whatsapp,
		'setLocation'   => true,
		'shareLink'     => true,
	),
	'whatsapp'     => $whatsapp_number,
	'osrmUrl'      => 'https://router.project-osrm.org/route/v1/driving',
	'i18n'         => array(
		'searchPlaceholder' => __( 'Search airport, city, or address…', 'dstheme' ),
		'nearMe'            => __( 'Near me', 'dstheme' ),
		'noResults'         => __( 'No locations match your search.', 'dstheme' ),
		'directions'        => __( 'Get directions', 'dstheme' ),
		'mapDirections'     => __( 'Show route', 'dstheme' ),
		'clearRoute'        => __( 'Clear route', 'dstheme' ),
		'call'              => __( 'Call', 'dstheme' ),
		'whatsapp'          => __( 'WhatsApp', 'dstheme' ),
		'book'              => __( 'Book a flight', 'dstheme' ),
		'share'             => __( 'Share', 'dstheme' ),
		'setMyLocation'     => __( 'Set as my location', 'dstheme' ),
		'myLocation'        => __( 'My location', 'dstheme' ),
		'open247'           => __( '24/7 support', 'dstheme' ),
		'hours'             => __( 'Hours', 'dstheme' ),
		'terminal'          => __( 'Terminal', 'dstheme' ),
		'listView'          => __( 'List', 'dstheme' ),
		'mapView'           => __( 'Map', 'dstheme' ),
		'locations'         => __( 'locations', 'dstheme' ),
		'kmAway'            => __( 'km away', 'dstheme' ),
		'miAway'            => __( 'mi away', 'dstheme' ),
		'viewMap'           => __( 'View map', 'dstheme' ),
		'viewList'          => __( 'View list', 'dstheme' ),
		'walkIn'            => __( 'Walk-in', 'dstheme' ),
		'airportCounter'    => __( 'Airport counter', 'dstheme' ),
		'phone247'          => __( '24/7 phone', 'dstheme' ),
		'linkCopied'        => __( 'Link copied', 'dstheme' ),
		'routeError'        => __( 'Could not load route. Try external directions.', 'dstheme' ),
	),
);

$config_json = wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE );

$uid = 'tl-' . substr( md5( $heading . count( $locations ) ), 0, 8 );

$root_classes = array( 'tl-locations', 'wp-block-ds-blocks-locations' );
if ( $show_globe && empty( $hero_image_url ) )   $root_classes[] = 'tl-locations--globe';
if ( ! empty( $hero_image_url ) )                $root_classes[] = 'tl-locations--hero-image';
if ( 'compact' === $card_density )               $root_classes[] = 'tl-locations--compact';
if ( $sticky_search )                            $root_classes[] = 'tl-locations--sticky-search';

$root_style = '--tl-map-height:' . $map_height . 'px;--tl-mobile-map-height:' . $mobile_map_h . 'vh;--tl-stats-cols:' . max( 1, min( 4, count( $countries ) ?: 3 ) ) . ';';
?>

<?php // ── Enqueue MapLibre ────────────────────────────────────────────── ?>
<link rel="stylesheet" href="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.css" />

<div<?php echo $anchor_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	id="<?php echo esc_attr( $uid ); ?>"
	class="<?php echo esc_attr( implode( ' ', $root_classes ) ); ?>"
	style="<?php echo esc_attr( $root_style ); ?>"
	data-travelay-locations
	data-tl-config="<?php echo esc_attr( $config_json ); ?>"
	data-mobile-view="<?php echo esc_attr( $mobile_view ); ?>"
>

	<?php // ── Hero ──────────────────────────────────────────────────── ?>
	<section class="tl-hero" aria-label="<?php esc_attr_e( 'Find a location', 'dstheme' ); ?>">
		<?php if ( ! empty( $hero_image_url ) ) : ?>
			<div class="tl-hero__photo" aria-hidden="true" style="background-image:url(<?php echo esc_url( $hero_image_url ); ?>);"></div>
		<?php elseif ( $show_globe ) : ?>
			<div class="tl-hero__globe" aria-hidden="true">
				<div class="tl-hero__globe-sphere"></div>
				<div class="tl-hero__globe-glow"></div>
			</div>
		<?php endif; ?>

		<div class="tl-hero__inner">
			<?php if ( $show_board && ! empty( $board_codes ) ) : ?>
				<div class="tl-hero__board" aria-hidden="true" data-tl-board>
					<?php foreach ( array_slice( $board_codes, 0, 6 ) as $code ) : ?>
						<button type="button" class="tl-hero__board-item" data-tl-board-code="<?php echo esc_attr( $code ); ?>" data-tl-board-filter="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $code ); ?></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $heading ) ) : ?>
				<h2 class="tl-hero__heading"><?php echo wp_kses_post( $heading ); ?></h2>
			<?php endif; ?>

			<?php if ( $show_search || $show_filters || $show_svc_filters ) : ?>
				<div class="tl-hero__tools<?php echo $sticky_search ? ' tl-hero__tools--sticky' : ''; ?>" data-tl-tools>

					<?php if ( $show_search ) : ?>
						<div class="tl-hero__search">
							<label class="screen-reader-text" for="<?php echo esc_attr( $uid ); ?>-search"><?php esc_html_e( 'Search locations', 'dstheme' ); ?></label>
							<input
								id="<?php echo esc_attr( $uid ); ?>-search"
								class="tl-hero__search-input"
								type="search"
								inputmode="search"
								autocomplete="off"
								enterkeyhint="search"
								placeholder="<?php esc_attr_e( 'Search airport, city, or address…', 'dstheme' ); ?>"
								data-tl-search
							/>
							<button type="button" class="tl-hero__near-btn" data-tl-near-me>
								<span class="tl-hero__near-icon" aria-hidden="true">
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
										<circle cx="12" cy="12" r="3" fill="currentColor"/>
										<path d="M12 2v3M12 19v3M2 12h3M19 12h3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
									</svg>
								</span>
								<?php esc_html_e( 'Near me', 'dstheme' ); ?>
							</button>
						</div>
					<?php endif; ?>

					<?php if ( $show_filters && ! empty( $countries ) ) : ?>
						<div class="tl-hero__filters" role="group" aria-label="<?php esc_attr_e( 'Filter by country', 'dstheme' ); ?>">
							<button type="button" class="tl-filter is-active" data-tl-filter="all"><?php esc_html_e( 'All', 'dstheme' ); ?></button>
							<?php foreach ( $countries as $code => $name ) : ?>
								<button type="button" class="tl-filter" data-tl-filter="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $name ); ?></button>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<?php if ( $show_svc_filters ) : ?>
						<div class="tl-hero__filters tl-hero__filters--services" role="group" aria-label="<?php esc_attr_e( 'Filter by service', 'dstheme' ); ?>">
							<button type="button" class="tl-filter tl-filter--service is-active" data-tl-service="all"><?php esc_html_e( 'All services', 'dstheme' ); ?></button>
							<button type="button" class="tl-filter tl-filter--service" data-tl-service="walk_in"><?php esc_html_e( 'Walk-in', 'dstheme' ); ?></button>
							<button type="button" class="tl-filter tl-filter--service" data-tl-service="airport"><?php esc_html_e( 'Airport counter', 'dstheme' ); ?></button>
							<button type="button" class="tl-filter tl-filter--service" data-tl-service="phone_247"><?php esc_html_e( '24/7 phone', 'dstheme' ); ?></button>
						</div>
					<?php endif; ?>

				</div>
			<?php endif; ?>
		</div>
	</section>

	<?php // ── Stats ────────────────────────────────────────────────── ?>
	<?php if ( $show_stats ) : ?>
		<div class="tl-stats">
			<div class="tl-stats__item">
				<span class="tl-stats__num"><?php echo esc_html( (string) count( $locations ) ); ?></span>
				<span class="tl-stats__label"><?php esc_html_e( 'Locations', 'dstheme' ); ?></span>
			</div>
			<div class="tl-stats__item">
				<span class="tl-stats__num"><?php echo esc_html( (string) count( $countries ) ); ?></span>
				<span class="tl-stats__label"><?php esc_html_e( 'Countries', 'dstheme' ); ?></span>
			</div>
			<div class="tl-stats__item">
				<span class="tl-stats__num">24/7</span>
				<span class="tl-stats__label"><?php esc_html_e( 'TravelayGent™', 'dstheme' ); ?></span>
			</div>
		</div>
	<?php endif; ?>

	<?php // ── Explorer (list + map) ──────────────────────────────── ?>
	<div class="tl-explorer" data-view="<?php echo esc_attr( $mobile_view ); ?>">

		<div class="tl-explorer__mobile-bar">
			<p class="tl-panel__count" data-tl-count>
				<?php printf( esc_html( _n( '%d location', '%d locations', count( $locations ), 'dstheme' ) ), count( $locations ) ); ?>
			</p>
			<div class="tl-explorer__mobile-toggle" role="tablist">
				<button type="button" class="tl-explorer__toggle<?php echo 'list' === $mobile_view ? ' is-active' : ''; ?>" role="tab" aria-selected="<?php echo 'list' === $mobile_view ? 'true' : 'false'; ?>" data-tl-view="list"><?php esc_html_e( 'List', 'dstheme' ); ?></button>
				<button type="button" class="tl-explorer__toggle<?php echo 'map' === $mobile_view ? ' is-active' : ''; ?>" role="tab" aria-selected="<?php echo 'map' === $mobile_view ? 'true' : 'false'; ?>" data-tl-view="map"><?php esc_html_e( 'Map', 'dstheme' ); ?></button>
			</div>
		</div>

		<div class="tl-explorer__layout">
			<aside class="tl-panel" aria-label="<?php esc_attr_e( 'Location list', 'dstheme' ); ?>">
				<div class="tl-panel__header">
					<p class="tl-panel__count" data-tl-count aria-live="polite">
						<?php printf( esc_html( _n( '%d location', '%d locations', count( $locations ), 'dstheme' ) ), count( $locations ) ); ?>
					</p>
				</div>
				<ul class="tl-panel__list" data-tl-list role="list">
					<?php foreach ( $locations as $loc ) : ?>
						<li
							class="tl-card"
							data-tl-card
							data-id="<?php echo esc_attr( (string) $loc['id'] ); ?>"
							data-slug="<?php echo esc_attr( $loc['slug'] ); ?>"
							data-country="<?php echo esc_attr( $loc['country_code'] ); ?>"
							data-services="<?php echo esc_attr( implode( ',', $loc['services'] ) ); ?>"
						>
							<button type="button" class="tl-card__btn" data-tl-select="<?php echo esc_attr( (string) $loc['id'] ); ?>">
								<?php if ( ! empty( $loc['airport_code'] ) ) : ?>
									<span class="tl-card__code"><?php echo esc_html( $loc['airport_code'] ); ?></span>
								<?php endif; ?>
								<span class="tl-card__name"><?php echo esc_html( $loc['title'] ); ?></span>
								<?php if ( ! empty( $loc['open_label'] ) ) : ?>
									<span class="tl-card__hours tl-card__hours--<?php echo esc_attr( $loc['open_status'] ); ?>"><?php echo esc_html( $loc['open_label'] ); ?></span>
								<?php endif; ?>
								<span class="tl-card__address"><?php echo esc_html( $loc['address'] ); ?></span>
								<span class="tl-card__meta">
									<?php if ( $loc['has_airport'] ) : ?>
										<span class="tl-card__badge"><?php esc_html_e( 'Airport', 'dstheme' ); ?></span>
									<?php endif; ?>
									<?php if ( $loc['is_247'] ) : ?>
										<span class="tl-card__badge"><?php esc_html_e( '24/7', 'dstheme' ); ?></span>
									<?php endif; ?>
									<span class="tl-card__distance" data-tl-distance hidden></span>
								</span>
							</button>
						</li>
					<?php endforeach; ?>
				</ul>
				<p class="tl-panel__empty" data-tl-empty hidden><?php esc_html_e( 'No locations match your search.', 'dstheme' ); ?></p>
			</aside>

			<?php if ( $show_map ) : ?>
				<div class="tl-map-wrap" data-tl-map-wrap>
					<div class="tl-map" data-tl-map role="application" aria-label="<?php esc_attr_e( 'Interactive map of Travelay locations', 'dstheme' ); ?>"></div>
					<div class="tl-map__route-status" data-tl-route-status hidden aria-live="polite"></div>
				</div>
			<?php endif; ?>
		</div>

		<aside class="tl-detail" data-tl-detail hidden aria-live="polite">
			<div class="tl-detail__handle" aria-hidden="true"></div>
			<button type="button" class="tl-detail__close" data-tl-detail-close aria-label="<?php esc_attr_e( 'Close details', 'dstheme' ); ?>">&times;</button>
			<div class="tl-detail__media" data-tl-detail-media></div>
			<div class="tl-detail__body" data-tl-detail-body></div>
		</aside>
	</div>

	<?php // ── Bottom CTA ─────────────────────────────────────────── ?>
	<?php if ( $show_cta ) : ?>
		<section class="tl-cta">
			<div class="tl-cta__inner">
				<h2 class="tl-cta__title"><?php echo esc_html( $cta_title ); ?></h2>
				<p class="tl-cta__text"><?php echo esc_html( $cta_text ); ?></p>
				<div class="tl-cta__actions">
					<a class="tl-cta__btn tl-cta__btn--primary" href="tel:<?php echo esc_attr( preg_replace( '/\D+/', '', $default_phone ) ); ?>"><?php esc_html_e( 'Call Now', 'dstheme' ); ?></a>
					<a class="tl-cta__btn tl-cta__btn--secondary" href="<?php echo esc_url( $cta_url ); ?>"><?php esc_html_e( 'Book Online', 'dstheme' ); ?></a>
					<a class="tl-cta__btn tl-cta__btn--ghost" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Contact Us', 'dstheme' ); ?></a>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<noscript>
		<div class="tl-noscript">
			<h2><?php esc_html_e( 'Travelay Locations', 'dstheme' ); ?></h2>
			<ul>
				<?php foreach ( $locations as $loc ) : ?>
					<li>
						<strong><?php echo esc_html( $loc['title'] ); ?></strong><br />
						<?php echo esc_html( $loc['address'] ); ?><br />
						<a href="tel:<?php echo esc_attr( preg_replace( '/\D+/', '', $loc['phone'] ) ); ?>"><?php echo esc_html( $loc['phone'] ); ?></a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</noscript>

</div>
