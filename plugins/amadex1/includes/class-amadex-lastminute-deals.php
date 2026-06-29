<?php

/**
 * Last Minute Deals Shortcode for Amadex
 * Usage: [amadex_last_minute_deals origin="JFK" adults="1" count="9" days_ahead="7"]
 */

if (!defined('ABSPATH')) exit;

class Amadex_LastMinute_Deals
{
    public function __construct()
    {
        add_shortcode('amadex_last_minute_deals', array($this, 'render'));
        add_action('wp_ajax_amadex_fetch_lastminute_deals', array($this, 'fetch_deals'));
        add_action('wp_ajax_nopriv_amadex_fetch_lastminute_deals', array($this, 'fetch_deals'));
    }

    /**
     * Shortcode renderer
     */
    public function render($atts)
    {
        $atts = shortcode_atts(array(
            'title'       => 'Last-Minute Flight Deals',
            'subtitle'    => 'Airlines Deals',
            'origin'      => 'JFK',           // Default origin IATA
            'adults'      => '1',
            'count'       => '24',           // How many deals to show
            'days_ahead'  => '7',             // Search window (next N days)
            'currency'    => 'USD',
            'cabin'       => 'ECONOMY',
            'booking_url' => '',              // Where clicking a card goes (defaults to flight results page)
        ), $atts, 'amadex_last_minute_deals');

        $uid = 'lmd-' . uniqid();

        // Popular destinations to search against
        $destinations = array(
            array('iata' => 'LAX', 'city' => 'Los Angeles',  'country' => 'US'),
            array('iata' => 'ORD', 'city' => 'Chicago',      'country' => 'US'),
            array('iata' => 'MIA', 'city' => 'Miami',        'country' => 'US'),
            array('iata' => 'DFW', 'city' => 'Dallas',       'country' => 'US'),
            array('iata' => 'LAS', 'city' => 'Las Vegas',    'country' => 'US'),
            array('iata' => 'SEA', 'city' => 'Seattle',      'country' => 'US'),
            array('iata' => 'DEN', 'city' => 'Denver',       'country' => 'US'),
            array('iata' => 'ATL', 'city' => 'Atlanta',      'country' => 'US'),
            array('iata' => 'BOS', 'city' => 'Boston',       'country' => 'US'),
            array('iata' => 'SFO', 'city' => 'San Francisco', 'country' => 'US'),
            array('iata' => 'LHR', 'city' => 'London',       'country' => 'UK'),
            array('iata' => 'CDG', 'city' => 'Paris',        'country' => 'FR'),
            array('iata' => 'DXB', 'city' => 'Dubai',        'country' => 'UAE'),
            array('iata' => 'CUN', 'city' => 'Cancun',       'country' => 'MX'),
        );

        // Remove origin from destinations list
        $destinations = array_filter($destinations, function ($d) use ($atts) {
            return strtoupper($d['iata']) !== strtoupper($atts['origin']);
        });
        $destinations = array_values($destinations);

        wp_enqueue_style('amadex-lmd-style', false);

        ob_start();
?>
        <style>
            #<?php echo esc_attr($uid); ?>* {
                box-sizing: border-box;
            }

            #<?php echo esc_attr($uid); ?> {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
                max-width: 1100px;
                margin: 0 auto;
                padding: 0;
            }

            span.lmd-plane .amadex-plane-icon {
                width: 22px;
                height: 29px;
                background: #ffffff;
                padding: 2px;
                z-index: 3;
                transition: transform 0.3s ease;
                transform: rotate(90deg);
            }

            /* Header */
            .lmd-subtitle {
                text-align: center;
                font-size: 15px;
                font-weight: 600;
                color: #64748b;
                text-transform: uppercase;
                letter-spacing: 1.5px;
                margin: 0 0 8px;
            }

            .lmd-title {
                text-align: center;
                font-size: 32px;
                color: #0f172a;
                margin: 0 0 28px;
                letter-spacing: -0.5px;
            }

            /* Airline filter pills */
            .lmd-airline-filters {
                display: flex;
                gap: 10px;
                flex-wrap: nowrap;
                justify-content: flex-start;
                margin-bottom: 32px;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
                -ms-overflow-style: none;
                padding-bottom: 4px;
            }

            .lmd-airline-filters::-webkit-scrollbar {
                display: none;
            }

            .lmd-airline-pill {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 9px 18px;
                border: 1.5px solid #e2e8f0;
                border-radius: 100px;
                background: #fff;
                cursor: pointer;
                font-size: 13px;
                font-weight: 600;
                color: #334155;
                transition: all .15s;
                white-space: nowrap;
                box-shadow: 0 1px 3px rgba(0, 0, 0, .06);
            }

            .lmd-airline-pill:hover {
                border-color: #0e7d3f;
                color: #0e7d3f;
            }

            .lmd-airline-pill.active {
                background: #0e7d3f;
                border-color: #0e7d3f;
                color: #fff;
                box-shadow: 0 2px 8px rgba(14, 125, 63, .25);
            }

            .lmd-airline-pill img {
                width: 22px;
                height: 22px;
                border-radius: 50%;
                object-fit: cover;
            }

            /* Section title */
            .lmd-section-title {
                text-align: center;
                font-size: 26px;
                font-weight: 800;
                color: #0f172a;
                margin: 0 0 20px;
                letter-spacing: -0.4px;
            }

            /* Loading */
            .lmd-loading {
                text-align: center;
                padding: 60px 20px;
                color: #94a3b8;
            }

            .lmd-spinner {
                width: 36px;
                height: 36px;
                border: 3px solid #e2e8f0;
                border-top-color: #0e7d3f;
                border-radius: 50%;
                animation: lmd-spin .8s linear infinite;
                margin: 0 auto 14px;
            }

            @keyframes lmd-spin {
                to {
                    transform: rotate(360deg);
                }
            }

            /* Error */
            .lmd-error {
                text-align: center;
                padding: 40px 20px;
                color: #ef4444;
                font-size: 14px;
            }

            /* Grid */
            .lmd-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 16px;
                padding-top: 4px;
                width: 100%;
                box-sizing: border-box;
            }

            @media (max-width: 900px) {
                .lmd-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }

            @media (max-width: 580px) {
                .lmd-grid {
                    grid-template-columns: 1fr;
                }
            }

            /* Deal Card */
            .lmd-card {
                background: #fff;
                border: 1.5px solid #e8ecf0;
                border-radius: 14px;
                padding: 16px;
                cursor: pointer;
                transition: border-color .15s, box-shadow .15s, transform .15s;
                text-decoration: none;
                color: inherit;
                display: block;
            }

            .lmd-card:hover {
                border-color: #0e7d3f;
                box-shadow: 0 4px 20px rgba(14, 125, 63, .12);
                transform: translateY(-2px);
            }

            /* Card top: airline + dates */
            .lmd-card-top {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 12px;
            }

            .lmd-airline-info {
                display: flex;
                align-items: center;
                gap: 7px;
            }

            .lmd-airline-logo {
                width: 26px;
                height: 26px;
                border-radius: 50%;
                object-fit: cover;
                background: #f1f5f9;
            }

            .lmd-airline-name {
                font-size: 12.5px;
                font-weight: 700;
                color: #0f172a;
            }

            .lmd-dates {
                font-size: 11px;
                color: #94a3b8;
                font-weight: 500;
                white-space: nowrap;
            }

            /* Route */
            .lmd-route {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 4px;
            }

            .lmd-iata {
                font-size: 22px;
                font-weight: 700;
                color: #0f172a;
                letter-spacing: -0.5px;
                min-width: 48px;
                white-space: nowrap;
                flex-shrink: 0;
            }

            .lmd-iata.right {
                text-align: right;
            }

            .lmd-route-line {
                flex: 1;
                display: flex;
                align-items: center;
                gap: 4px;
                min-width: 0;
            }

            .lmd-circle {
                width: 9px;
                height: 9px;
                border: 2px solid #94a3b8;
                border-radius: 50%;
                background: #fff;
                flex-shrink: 0;
            }

            .lmd-dashed-line {
                flex: 1;
                border-top: 2px dashed #cbd5e1;
                min-width: 0;
            }

            .lmd-plane {
                font-size: 14px;
                color: #334155;
                flex-shrink: 0;
                transform: rotate(0deg);
                display: inline-block;
            }

            /* City names */
            .lmd-cities {
                display: flex;
                justify-content: space-between;
                margin-bottom: 14px;
            }

            .lmd-city {
                font-size: 11.5px;
                color: #64748b;
                font-weight: 500;
            }

            .lmd-city.right {
                text-align: right;
            }

            /* Price row */
            .lmd-price-row {
                display: flex;
                align-items: center;
                border: 1.5px solid #e2e8f0;
                border-radius: 8px;
                overflow: hidden;
            }

            .lmd-starting-from {
                flex: 1;
                padding: 9px 12px;
                font-size: 12px;
                color: #64748b;
                font-weight: 500;
            }

            .lmd-price-badge {
                background: #0e7d3f;
                color: #fff;
                padding: 9px 16px;
                font-size: 15px;
                font-weight: 600;
                white-space: nowrap;
                font-family: 'DM Mono', monospace, inherit;
                letter-spacing: -0.3px;
            }

            /* Savings badge */
            .lmd-savings {
                display: inline-block;
                background: #fef3c7;
                color: #92400e;
                font-size: 10px;
                font-weight: 700;
                padding: 2px 7px;
                border-radius: 20px;
                margin-left: 8px;
                vertical-align: middle;
            }

            /* Scrollable grid wrapper */
            .lmd-grid-scroll {
                max-height: 680px;
                overflow-y: auto;
                overflow-x: hidden;
                scrollbar-width: thin;
                scrollbar-color: #0e7d3f #f1f5f9;
                border: 1px solid #e2e8f0;
                padding: 16px;
                border-radius: 10px;
                box-shadow: 0 4px 16px rgba(0, 0, 0, 0.10);
                box-sizing: border-box;
                width: 100%;
            }

            .lmd-grid-scroll::-webkit-scrollbar {
                width: 5px;
            }

            .lmd-grid-scroll::-webkit-scrollbar-track {
                background: #f1f5f9;
                border-radius: 10px;
            }

            .lmd-grid-scroll::-webkit-scrollbar-thumb {
                background: #0e7d3f;
                border-radius: 10px;
            }

            /* Savings badge inline */
            .lmd-savings {
                display: inline-flex;
                align-items: center;
                background: #fef3c7;
                color: #92400e;
                font-size: 10.5px;
                padding: 2px 8px;
                border-radius: 20px;
                letter-spacing: 0.3px;
                vertical-align: middle;
                margin-left: 6px;
                white-space: nowrap;
            }

            /* No results */
            .lmd-no-results {
                grid-column: 1/-1;
                text-align: center;
                padding: 50px 20px;
                color: #94a3b8;
                font-size: 14px;
            }

            .lmd-no-results span {
                display: block;
                font-size: 36px;
                margin-bottom: 10px;
            }
        </style>

        <div id="<?php echo esc_attr($uid); ?>">
            <?php if (!empty($atts['subtitle'])): ?>
            <?php endif; ?>
            <h2 class="lmd-title"></h2>

            <!-- Airline filter pills (populated by JS) -->
            <div class="lmd-airline-filters" id="<?php echo esc_attr($uid); ?>-filters"></div>

            <!-- Deals grid -->
            <div class="lmd-grid-scroll">
                <div class="lmd-grid" id="<?php echo esc_attr($uid); ?>-grid">
                    <div style="grid-column:1/-1;" class="lmd-loading">
                        <div class="lmd-spinner"></div>
                        <p>Searching live deals...</p>
                    </div>
                </div>
            </div>
        </div>

        <script>
            (function() {
                var UID = <?php echo json_encode($uid); ?>;
                var ORIGIN = <?php echo json_encode(strtoupper($atts['origin'])); ?>;
                var ADULTS = <?php echo json_encode($atts['adults']); ?>;
                var COUNT = <?php echo intval($atts['count']); ?>;
                var CURRENCY = <?php echo json_encode($atts['currency']); ?>;
                var CABIN = <?php echo json_encode($atts['cabin']); ?>;
                var AJAXURL = <?php echo json_encode(admin_url('admin-ajax.php')); ?>;
                var NONCE = <?php echo json_encode(wp_create_nonce('amadex_nonce')); ?>;
                var BOOKING_URL = typeof AmadexConfig !== 'undefined' && AmadexConfig.bookingPageUrl ? AmadexConfig.bookingPageUrl : <?php echo json_encode(home_url('/flight-booking/')); ?>;
                var DESTS = <?php echo json_encode($destinations); ?>;
                var DAYS = <?php echo intval($atts['days_ahead']); ?>;

                var allDeals = [];
                var activeAirline = null;

                function getDateRange() {
                    var now = new Date();
                    var dep = new Date(now);
                    dep.setDate(dep.getDate() + 1);
                    var ret = new Date(dep);
                    ret.setDate(ret.getDate() + DAYS);
                    var fmt = function(d) {
                        return d.getFullYear() + '-' +
                            String(d.getMonth() + 1).padStart(2, '0') + '-' +
                            String(d.getDate()).padStart(2, '0');
                    };
                    return {
                        dep: fmt(dep),
                        ret: fmt(ret)
                    };
                }

                function formatDate(str) {
                    if (!str) return '';
                    var d = new Date(str);
                    var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    var days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                    return days[d.getDay()] + ', ' + String(d.getDate()).padStart(2, '0') +
                        ' ' + months[d.getMonth()];
                }

                function airlineLogoUrl(code) {
                    return 'https://images.kiwi.com/airlines/64/' + code + '.png';
                }

                function airlineName(code) {
                    var names = {
                        'AA': 'American Airlines',
                        'UA': 'United Airlines',
                        'DL': 'Delta Air Lines',
                        'WN': 'Southwest Airlines',
                        'B6': 'JetBlue',
                        'AS': 'Alaska Airlines',
                        'F9': 'Frontier Airlines',
                        'NK': 'Spirit Airlines',
                        'HA': 'Hawaiian Airlines',
                        'G4': 'Allegiant Air',
                        'SY': 'Sun Country',
                        'VX': 'Virgin America',
                        'BA': 'British Airways',
                        'LH': 'Lufthansa',
                        'AF': 'Air France',
                        'EK': 'Emirates',
                        'QR': 'Qatar Airways',
                        'SQ': 'Singapore Airlines',
                        'TK': 'Turkish Airlines',
                        '6E': 'IndiGo',
                        'AI': 'Air India',
                        'AC': 'Air Canada',
                        'QF': 'Qantas',
                        'CX': 'Cathay Pacific',
                    };
                    return names[code] || code;
                }

                function formatPrice(amount, currency) {
                    return (currency === 'USD' ? '$' : currency + ' ') +
                        parseFloat(amount).toFixed(2);
                }

                // Fetch all deals in parallel
                function fetchAllDeals() {
                    var dates = getDateRange();
                    var requests = DESTS.slice(0, 14).map(function(dest, i) {
                        return fetch(AJAXURL, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: new URLSearchParams({
                                    action: 'amadex_search_flights',
                                    nonce: NONCE,
                                    origin: ORIGIN,
                                    destination: dest.iata,
                                    departure_date: dates.dep,
                                    return_date: dates.ret,
                                    adults: ADULTS,
                                    children: '0',
                                    infants: '0',
                                    travel_class: CABIN,
                                    currency: CURRENCY,
                                    trip_type: 'round',
                                    load_more: '0'
                                })
                            })
                            .then(function(r) {
                                return r.json();
                            })
                            .then(function(data) {
                                if (!data.success || !data.data || !data.data.flights) return [];
                                // Take cheapest flight for this route
                                var flights = data.data.flights.slice(0, 3);
                                return flights.map(function(f) {
                                    var itin = f.itineraries && f.itineraries[0];
                                    var seg = itin && itin.segments && itin.segments[0];
                                    var retIt = f.itineraries && f.itineraries[1];
                                    var retSeg = retIt && retIt.segments && retIt.segments[retIt.segments.length - 1];
                                    var carrier = seg ? (seg.carrierCode || seg.carrier_code || '') : '';
                                    var depAt = seg && seg.departure ? seg.departure.at : dates.dep;
                                    var retAt = retSeg && retSeg.arrival ? retSeg.arrival.at : dates.ret;
                                    return {
                                        origin: ORIGIN,
                                        originCity: 'Your City',
                                        dest: dest.iata,
                                        destCity: dest.city,
                                        airline: carrier,
                                        depDate: depAt,
                                        retDate: retAt,
                                        price: parseFloat(f.price && (f.price.total || f.price.grandTotal) || 0),
                                        currency: f.price && f.price.currency || CURRENCY,
                                        flightData: f
                                    };
                                });
                            })
                            .catch(function() {
                                return [];
                            });
                    });

                    Promise.all(requests).then(function(results) {
                        allDeals = [];
                        results.forEach(function(r) {
                            allDeals = allDeals.concat(r);
                        });
                        // Sort by price cheapest first
                        allDeals.sort(function(a, b) {
                            return a.price - b.price;
                        });
                        // Deduplicate: remove same airline + same route + same price
                        var seen = {};
                        allDeals = allDeals.filter(function(d) {
                            var key = d.airline + '_' + d.origin + '_' + d.dest + '_' + d.price;
                            if (seen[key]) return false;
                            seen[key] = true;
                            return true;
                        });
                        renderFilters();
                        renderGrid(null);
                    });
                }

                // Build airline pill filters
                function renderFilters() {
                    var filtersEl = document.getElementById(UID + '-filters');
                    var airlines = {};
                    allDeals.forEach(function(d) {
                        if (d.airline) airlines[d.airline] = airlineName(d.airline);
                    });

                    var html = Object.keys(airlines).map(function(code) {
                        return '<button class="lmd-airline-pill" data-airline="' + code + '" onclick="lmdFilter_' + UID.replace(/-/g, '_') + '(\'' + code + '\')">' +
                            '<img src="' + airlineLogoUrl(code) + '" alt="' + airlines[code] + '" onerror="this.style.display=\'none\'">' +
                            airlines[code] +
                            '</button>';
                    }).join('');

                    filtersEl.innerHTML = html || '<p style="color:#94a3b8;font-size:13px;">No airline filters available</p>';
                }

                // Render the deals grid
                function renderGrid(filterAirline) {
                    var gridEl = document.getElementById(UID + '-grid');
                    var deals = filterAirline ?
                        allDeals.filter(function(d) {
                            return d.airline === filterAirline;
                        }) :
                        allDeals;

                    if (!deals.length) {
                        gridEl.innerHTML = '<div class="lmd-no-results"><span>✈️</span>No deals found for this filter.</div>';
                        return;
                    }

                    // Calculate avg price for savings badge
                    var avg = deals.reduce(function(s, d) {
                        return s + d.price;
                    }, 0) / deals.length;

                    gridEl.innerHTML = deals.map(function(d, idx) {
                        var savings = avg > 0 && d.price < avg * 0.85 ?
                            '' : '';
                        return '<div class="lmd-card" onclick="lmdBook_' + UID.replace(/-/g, '_') + '(' + idx + ')" style="cursor:pointer;">' +
                            '<div class="lmd-card-top">' +
                            '<div class="lmd-airline-info">' +
                            '<img class="lmd-airline-logo" src="' + airlineLogoUrl(d.airline) + '" alt="' + airlineName(d.airline) + '" onerror="this.style.display=\'none\'">' +
                            '<span class="lmd-airline-name">' + airlineName(d.airline) + '</span>' +
                            '</div>' +
                            '<span class="lmd-dates">' + formatDate(d.depDate) + ' · ' + formatDate(d.retDate) + '</span>' +
                            '</div>' +
                            '<div class="lmd-route">' +
                            '<span class="lmd-iata">' + d.origin + '</span>' +
                            '<div class="lmd-route-line">' +
                            '<div class="lmd-circle"></div>' +
                            '<div class="lmd-dashed-line"></div>' +
                            '<span class="lmd-plane"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" class="amadex-plane-icon"><path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z" fill="#6B7280"/></svg></span>' +
                            '</div>' +
                            '<span class="lmd-iata right">' + d.dest + '</span>' +
                            '</div>' +
                            '<div class="lmd-cities">' +
                            '<span class="lmd-city">' + d.originCity + '</span>' +
                            '<span class="lmd-city right">' + d.destCity + '</span>' +
                            '</div>' +
                            '<div class="lmd-price-row">' +
                            '<span class="lmd-starting-from">Starting from</span>' +
                            '<span class="lmd-price-badge">' + formatPrice(d.price, d.currency) + '</span>' +
                            '</div>' +
                            '</div>';
                    }).join('');
                }

                // Global filter function (called from onclick)
                var fnName = 'lmdFilter_' + UID.replace(/-/g, '_');
                window[fnName] = function(airline) {
                    var pills = document.querySelectorAll('#' + UID + '-filters .lmd-airline-pill');
                    pills.forEach(function(p) {
                        p.classList.toggle('active', p.dataset.airline === airline && activeAirline !== airline);
                    });
                    activeAirline = (activeAirline === airline) ? null : airline;
                    renderGrid(activeAirline);
                };

                // Book a specific deal
                var fnBook = 'lmdBook_' + UID.replace(/-/g, '_');
                window[fnBook] = function(idx) {
                    var deals = activeAirline ?
                        allDeals.filter(function(d) {
                            return d.airline === activeAirline;
                        }) :
                        allDeals;
                    var deal = deals[idx];
                    if (!deal || !deal.flightData) return;

                    var flightData = deal.flightData;
                    var selectedCurrency = sessionStorage.getItem('amadex_selected_currency') || CURRENCY;
                    flightData.selected_currency = selectedCurrency;
                    if (!flightData.price) flightData.price = {};
                    flightData.price.selected_currency = selectedCurrency;

                    var dates = getDateRange();
                    var searchData = {
                        origin: deal.origin,
                        destination: deal.dest,
                        departure_date: dates.dep,
                        return_date: dates.ret,
                        adults: parseInt(ADULTS),
                        children: 0,
                        infants: 0,
                        cabin: CABIN,
                        travel_class: CABIN,
                        currency: selectedCurrency,
                        trip_type: 'round'
                    };

                    try {
                        sessionStorage.setItem('amadex_booking_flight', JSON.stringify(flightData));
                        sessionStorage.setItem('amadex_search_data', JSON.stringify(searchData));
                        sessionStorage.setItem('amadex_results_page_url', window.location.href);
                        sessionStorage.setItem('amadexBookingStage', 'passengers');
                        sessionStorage.removeItem('amadex_booking_timer_start');
                        sessionStorage.removeItem('amadex_booking_timer_remaining');
                        sessionStorage.removeItem('amadex_last_booking_flight_id');
                    } catch (e) {
                        console.warn('lmd sessionStorage error', e);
                    }

                    window.location.href = BOOKING_URL;
                };

                // Kick off
                fetchAllDeals();
            })();
        </script>
<?php
        return ob_get_clean();
    }

    /**
     * AJAX handler (kept for future use / caching layer)
     */
    public function fetch_deals()
    {
        wp_send_json_success(array('message' => 'Use amadex_search_flights directly'));
    }
}

new Amadex_LastMinute_Deals();
