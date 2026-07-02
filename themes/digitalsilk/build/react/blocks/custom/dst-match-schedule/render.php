<?php
/**
 * Match Schedule markup
 *
 * @package DST\Blocks\ds_blocks
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

$heading         = $attributes['heading']        ?? '';
$heading_color   = $attributes['headingColor']   ?? '#ffffff';
$stages          = ( ! empty( $attributes['stages'] )    && is_array( $attributes['stages'] ) )    ? $attributes['stages']    : [];
$countries       = ( ! empty( $attributes['countries'] ) && is_array( $attributes['countries'] ) ) ? $attributes['countries'] : [];
$default_stage   = $attributes['defaultStage']   ?? 'group-stage';
$default_country = $attributes['defaultCountry'] ?? 'all-countries';
$cta_text             = $attributes['ctaText']           ?? 'Find Flights';
$cta_color            = $attributes['ctaColor']          ?? '#1f7a4d';
$flight_results_page  = $attributes['flightResultsPage'] ?? '/flight-results/';
$default_origin_iata  = $attributes['defaultOriginIata'] ?? '';
$default_origin_name  = $attributes['defaultOriginName'] ?? '';
$matches         = ( ! empty( $attributes['matches'] ) && is_array( $attributes['matches'] ) ) ? $attributes['matches'] : [];

$background = wp_parse_args(
	$attributes['background'] ?? [],
	[
		'image'          => [],
		'bgColor'        => '#6b9c3f',
		'overlayColor'   => '#000000',
		'overlayOpacity' => 0,
	]
);

$bg_image_url    = $background['image']['url'] ?? '';
$bg_image_alt    = $background['image']['alt'] ?? '';
$bg_color        = $background['bgColor'] ?? '#6b9c3f';
$overlay_color   = $background['overlayColor'] ?? '#000000';
$overlay_opacity = (float) ( $background['overlayOpacity'] ?? 0 ) / 100;

$anchor      = ! empty( $attributes['anchor'] ) ? $attributes['anchor'] : '';
$anchor_attr = ! empty( $anchor ) ? ' id="' . esc_attr( $anchor ) . '"' : '';

$unique_id = 'dst-match-schedule-' . substr( md5( $heading . wp_json_encode( $matches ) ), 0, 8 );
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flag-icons/7.2.3/css/flag-icons.min.css">

<div<?php echo $anchor_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	id="<?php echo esc_attr( $unique_id ); ?>"
	class="c-match-schedule wp-block-ds-blocks-match-schedule"
	style="background-color:<?php echo esc_attr( $bg_color ); ?>;"
	data-items-per-page="<?php echo esc_attr( $items_per_page ); ?>"
	data-flight-results-page="<?php echo esc_attr( $flight_results_page ); ?>"
	data-default-origin-iata="<?php echo esc_attr( $default_origin_iata ); ?>"
	data-default-origin-name="<?php echo esc_attr( $default_origin_name ); ?>"
>
	<?php if ( ! empty( $bg_image_url ) ) : ?>
		<img
			class="c-match-schedule__bg"
			src="<?php echo esc_url( $bg_image_url ); ?>"
			alt="<?php echo esc_attr( $bg_image_alt ); ?>"
			aria-hidden="true"
			loading="lazy"
		/>
	<?php endif; ?>

	<?php if ( $overlay_opacity > 0 ) : ?>
		<span
			class="c-match-schedule__overlay"
			aria-hidden="true"
			style="background-color:<?php echo esc_attr( $overlay_color ); ?>; opacity:<?php echo esc_attr( $overlay_opacity ); ?>;"
		></span>
	<?php endif; ?>

	<div class="c-match-schedule__inner">

		<?php if ( ! empty( $heading ) ) : ?>
			<h2 class="c-match-schedule__heading" style="color:<?php echo esc_attr( $heading_color ); ?>;">
				<?php echo wp_kses_post( $heading ); ?>
			</h2>
		<?php endif; ?>

		<div class="c-match-schedule__panel">

			<div class="c-match-schedule__sidebar">
				<?php foreach ( $stages as $stage ) :
					$is_heading = ! empty( $stage['isHeading'] );
					$value      = $stage['value'] ?? '';
					$label      = $stage['label'] ?? '';
					$active     = ( $value === $default_stage ) ? ' -active' : '';
				?>
					<?php if ( $is_heading ) : ?>
						<p class="c-match-schedule__sidebar-label"><?php echo esc_html( $label ); ?></p>
					<?php else : ?>
						<button
							type="button"
							class="c-match-schedule__stage-btn<?php echo esc_attr( $active ); ?>"
							data-stage="<?php echo esc_attr( $value ); ?>"
						>
							<?php echo esc_html( $label ); ?>
						</button>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>

			<div class="c-match-schedule__content">

				<div class="c-match-schedule__tabs" role="tablist">
					<?php foreach ( $countries as $country ) :
						$value  = $country['value'] ?? '';
						$label  = $country['label'] ?? '';
						$active = ( $value === $default_country ) ? ' -active' : '';
					?>
						<button
							type="button"
							class="c-match-schedule__tab<?php echo esc_attr( $active ); ?>"
							data-country="<?php echo esc_attr( $value ); ?>"
						>
							<?php echo esc_html( $label ); ?>
						</button>
					<?php endforeach; ?>
				</div>

				<div class="c-match-schedule__grid">
					<?php foreach ( $matches as $match ) :
						$stage       = $match['stage']      ?? '';
						$country     = $match['country']    ?? '';
						$stadium     = $match['stadium']    ?? '';
						$group_label = $match['groupLabel'] ?? '';
						$team_a_flag = $match['teamAFlag']  ?? '';
						$team_a_name = $match['teamAName']  ?? '';
						$team_b_flag = $match['teamBFlag']  ?? '';
						$team_b_name = $match['teamBName']  ?? '';
						$date        = $match['date']       ?? '';
						$time        = $match['time']       ?? '';
						$link        = $match['link']       ?? '#';
						$visible     = ( $stage === $default_stage && ( $default_country === 'all-countries' || $country === $default_country ) );
					?>
						<div
							class="c-match-schedule__card"
							data-stage="<?php echo esc_attr( $stage ); ?>"
							data-country="<?php echo esc_attr( $country ); ?>"
							<?php echo $visible ? '' : ' style="display:none"'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						>
							<div class="c-match-schedule__card-top">
								<span class="c-match-schedule__stadium">
									<span class="c-event-fares__flight-line" aria-hidden="true">
										<img src="https://www.flytravelay.com/wp-content/uploads/2026/07/Stadium.svg" alt="" width="25" height="14" loading="lazy" />
									</span>
									<?php echo esc_html( $stadium ); ?>
								</span>
								<?php if ( ! empty( $group_label ) ) : ?>
									<span class="c-match-schedule__group"><?php echo esc_html( $group_label ); ?></span>
								<?php endif; ?>
							</div>

							<div class="c-match-schedule__team">
								<span class="c-match-schedule__flag fi fi-<?php echo esc_attr( strtolower( $team_a_flag ) ); ?>"></span>
								<span class="c-match-schedule__team-name"><?php echo esc_html( $team_a_name ); ?></span>
							</div>
							<div class="c-match-schedule__vs">vs</div>
							<div class="c-match-schedule__team">
								<span class="c-match-schedule__flag fi fi-<?php echo esc_attr( strtolower( $team_b_flag ) ); ?>"></span>
								<span class="c-match-schedule__team-name"><?php echo esc_html( $team_b_name ); ?></span>
							</div>

							<div class="c-match-schedule__bottom">
								<div class="c-match-schedule__datetime">
									<span class="c-match-schedule__date"><?php echo esc_html( $date ); ?></span>
									<span class="c-match-schedule__time">Time: <?php echo esc_html( $time ); ?></span>
								</div>
								<button
									type="button"
									class="c-match-schedule__cta c-match-schedule__find-flights"
									style="background-color:<?php echo esc_attr( $cta_color ); ?>;"
									data-destination-iata="<?php echo esc_attr( $match['destinationIata'] ?? '' ); ?>"
									data-destination-name="<?php echo esc_attr( $match['destinationName'] ?? '' ); ?>"
									data-origin-iata="<?php echo esc_attr( $match['originIata'] ?? '' ); ?>"
									data-origin-name="<?php echo esc_attr( $match['originName'] ?? '' ); ?>"
									data-date="<?php echo esc_attr( $date ); ?>"
								>
									<?php echo esc_html( $cta_text ); ?>
								</button>
							</div>
						</div>
					<?php endforeach; ?>

					<p class="c-match-schedule__empty" style="display:none;">
						<?php esc_html_e( 'No matches found for this selection.', 'dstheme' ); ?>
					</p>
				</div>

			</div>
		</div>
	</div>
</div>

<script>
(function() {
	var root = document.getElementById('<?php echo esc_js( $unique_id ); ?>');
	if (!root) return;

	var stageBtns        = root.querySelectorAll('.c-match-schedule__stage-btn');
	var countryTabs      = root.querySelectorAll('.c-match-schedule__tab');
	var cards            = root.querySelectorAll('.c-match-schedule__card');
	var emptyMsg         = root.querySelector('.c-match-schedule__empty');
	var flightResultsPage = root.getAttribute('data-flight-results-page') || '/flight-results/';
	var defaultOriginIata = root.getAttribute('data-default-origin-iata') || '';
	var defaultOriginName = root.getAttribute('data-default-origin-name') || '';

	var activeStage   = '<?php echo esc_js( $default_stage ); ?>';
	var activeCountry = '<?php echo esc_js( $default_country ); ?>';

	// ── Filter logic ─────────────────────────────────────────────────
	function applyFilter() {
		var visibleCount = 0;
		cards.forEach(function(card) {
			var stage   = card.getAttribute('data-stage');
			var country = card.getAttribute('data-country');
			var stageMatch   = (activeStage === 'all-stages' || stage === activeStage);
			var countryMatch = (activeCountry === 'all-countries' || country === activeCountry);
			var show = stageMatch && countryMatch;
			card.style.display = show ? '' : 'none';
			if (show) visibleCount++;
		});
		if (emptyMsg) emptyMsg.style.display = visibleCount === 0 ? '' : 'none';
	}

	stageBtns.forEach(function(btn) {
		btn.addEventListener('click', function() {
			var isActive = btn.classList.contains('-active');
			stageBtns.forEach(function(b) { b.classList.remove('-active'); });
			if (!isActive) {
				btn.classList.add('-active');
				activeStage = btn.getAttribute('data-stage');
			} else {
				activeStage = 'all-stages';
			}
			applyFilter();
		});
	});

	countryTabs.forEach(function(tab) {
		tab.addEventListener('click', function() {
			countryTabs.forEach(function(t) { t.classList.remove('-active'); });
			tab.classList.add('-active');
			activeCountry = tab.getAttribute('data-country');
			applyFilter();
		});
	});

	applyFilter();

	// ── Find Flights logic ────────────────────────────────────────────
	function parseMatchDate(dateStr) {
		// e.g. "12 June, Friday" → "2026-06-12"
		if (!dateStr) return '';
		var months = { january:'01',february:'02',march:'03',april:'04',may:'05',june:'06',july:'07',august:'08',september:'09',october:'10',november:'11',december:'12' };
		var clean = dateStr.replace(/,.*$/, '').trim(); // remove ", Friday" part
		var parts = clean.split(' ');
		if (parts.length < 2) return '';
		var day   = parts[0].padStart(2, '0');
		var month = months[(parts[1] || '').toLowerCase()] || '01';
		var year  = new Date().getFullYear();
		return year + '-' + month + '-' + day;
	}

	function buildFlightUrl(originIata, originName, destinationIata, destinationName, dateStr) {
		var date = parseMatchDate(dateStr);
		var params = new URLSearchParams({
			origin_iata:        originIata,
			origin_name:        originName || originIata,
			destination_iata:   destinationIata,
			destination_name:   destinationName || destinationIata,
			depart_date:        date,
			return_date:        '',
			one_way:            'true',
			adults:             '1',
			children:           '0',
			infants:            '0',
			trip_type:          'oneway',
			cabin:              'ECONOMY',
			language:           'en',
			lang:               'en',
		});
		return flightResultsPage + '?' + params.toString();
	}

	function getNearestAirport(lat, lng, callback) {
		// Use api.aviationstack.com free tier or fallback to simple fetch
		// Using a free no-key endpoint — aviowiki / airportdb
		var url = 'https://airportdb.io/api/v1/nearest?lat=' + lat + '&lng=' + lng + '&limit=1';
		fetch(url)
			.then(function(r) { return r.json(); })
			.then(function(data) {
				var airport = (data && data.airports && data.airports[0]) || null;
				if (airport && airport.iata_code) {
					callback(airport.iata_code, airport.name || airport.iata_code);
				} else {
					callback(null, null);
				}
			})
			.catch(function() { callback(null, null); });
	}

	function redirectToFlights(btn, originIata, originName) {
		var destIata  = btn.getAttribute('data-destination-iata') || '';
		var destName  = btn.getAttribute('data-destination-name') || '';
		var dateStr   = btn.getAttribute('data-date') || '';
		if (!destIata) { alert('Destination airport not configured for this match.'); return; }
		var url = buildFlightUrl(originIata, originName, destIata, destName, dateStr);
		window.location.href = url;
	}

	function handleFindFlights(btn) {
		var originIata = btn.getAttribute('data-origin-iata') || '';
		var originName = btn.getAttribute('data-origin-name') || '';

		// 1. Match has a specific origin set — use it directly
		if (originIata) {
			redirectToFlights(btn, originIata, originName);
			return;
		}

		// 2. Try cached geolocation airport from localStorage
		var cached = localStorage.getItem('amadex_user_airport_iata');
		if (cached) {
			redirectToFlights(btn, cached, localStorage.getItem('amadex_user_airport_name') || cached);
			return;
		}

		// 3. Try browser geolocation → nearest airport
		if (navigator.geolocation) {
			var origText = btn.textContent;
			btn.textContent = 'Locating…';
			btn.disabled = true;
			navigator.geolocation.getCurrentPosition(
				function(pos) {
					getNearestAirport(pos.coords.latitude, pos.coords.longitude, function(iata, name) {
						btn.textContent = origText;
						btn.disabled = false;
						if (iata) {
							localStorage.setItem('amadex_user_airport_iata', iata);
							localStorage.setItem('amadex_user_airport_name', name);
							redirectToFlights(btn, iata, name);
						} else if (defaultOriginIata) {
							redirectToFlights(btn, defaultOriginIata, defaultOriginName);
						} else {
							var manual = prompt('Could not detect your airport. Please enter your origin airport code (e.g. DXB, JFK):');
							if (manual) redirectToFlights(btn, manual.toUpperCase(), manual.toUpperCase());
						}
					});
				},
				function() {
					// Geolocation denied
					btn.textContent = origText;
					btn.disabled = false;
					if (defaultOriginIata) {
						redirectToFlights(btn, defaultOriginIata, defaultOriginName);
					} else {
						var manual = prompt('Please enter your origin airport code (e.g. DXB, JFK):');
						if (manual) redirectToFlights(btn, manual.toUpperCase(), manual.toUpperCase());
					}
				},
				{ timeout: 6000 }
			);
		} else if (defaultOriginIata) {
			redirectToFlights(btn, defaultOriginIata, defaultOriginName);
		} else {
			var manual = prompt('Please enter your origin airport code (e.g. DXB, JFK):');
			if (manual) redirectToFlights(btn, manual.toUpperCase(), manual.toUpperCase());
		}
	}

	root.querySelectorAll('.c-match-schedule__find-flights').forEach(function(btn) {
		btn.addEventListener('click', function() {
			handleFindFlights(btn);
		});
	});

})();
</script>
