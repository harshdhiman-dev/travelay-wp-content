/**
 * Amadex Flight Search - Enhanced JavaScript
 */

(function($) {
    'use strict';

    // Global variables
    let currentSearchData = {};
    let currentFilters = {};
    let selectedFlight = null;
    const BOOKING_STEPS_ORDER = ['booking', 'passengers', 'payment'];
    let isOneWaySearch = false;

    function getStreamingLoader() {
        const $container = $('#amadex-flight-cards-container');
        if (!$container.length) return null;
        if (typeof AmadexStreamingLoader === 'undefined') return null;
        if (!window.amadexStreamingLoader) {
            window.amadexStreamingLoader = new AmadexStreamingLoader($container[0]);
        }
        return window.amadexStreamingLoader;
    }

    function amadexShowStreamingLoaderUI() {
        const loader = getStreamingLoader();
        if (!loader) return;
        const config = typeof AmadexConfig !== 'undefined' ? AmadexConfig : {};
        if (config.enableLoadingAnimation === true) {
            loader.showLoadingAnimation();
        } else if (config.enableSkeletonUi === true) {
            loader.showSkeleton();
        }
    }

    function extractIataCode(codeStr) {
        if (!codeStr) return '';
        
        const trimmed = String(codeStr).trim();
        if (/^[A-Z]{3}$/i.test(trimmed)) {
            return trimmed.toUpperCase();
        }
        const parenMatch = trimmed.match(/\(([A-Z]{3})\)/i);
        if (parenMatch && parenMatch[1]) {
            return parenMatch[1].toUpperCase();
        }
        
        const codeMatch = trimmed.match(/[A-Z]{3}/i);
        if (codeMatch) {
            return codeMatch[0].toUpperCase();
        }
        
        return trimmed.toUpperCase();
    }

    $(document).ready(function() {
        initFlightSearch();
        initResultsPage();
        initModals();
        
        if (typeof getPromotionalContainers === 'function') {
            getPromotionalContainers().then(function(containers) {

            }).catch(function(error) {
                console.warn('Amadex: Failed to pre-fetch promotional containers', error);
            });
        }
        
        setTimeout(function() {
            initializePriceDisplay();
        }, 200);
        
        $(document).on('amadex:displayResults', function(e, data) {
            if (data) {
                displayFlightResults(data);
            }
        });
        
        $(document).on('amadex:currency-changed', function() {
            setTimeout(function() {
                initializePriceDisplay();
            }, 300);
        });
    });

    function initFlightSearch() {
        $('input[name="tripType"]').on('change', function() {
            const tripType = $(this).val();
            if (tripType === 'round') {
                $('#amadex-return-wrap').show();
                $('#amadex-return').prop('required', true);
            } else if (tripType === 'multi-city') {
                $('#amadex-return-wrap').show();
                $('#amadex-return').prop('required', false);
            } else {
                $('#amadex-return-wrap').hide();
                $('#amadex-return').prop('required', false);
            }
        });

        $('#amadex-from, #amadex-to').on('input', function() {
            const input = $(this);
            const keyword = input.val();
            
            if (keyword.length >= 2) {
                searchAirports(keyword, input);
            }
        });

        $('#amadex-form').on('submit', function(e) {
            e.preventDefault();
            performFlightSearch();
        });

        const today = new Date().toISOString().split('T')[0];
        $('#amadex-departure, #amadex-return').attr('min', today);
        
        initPassengersCabinModal();
    }
    
    function initPassengersCabinModal() {
        const modal = $('#amadex-passengers-cabin-modal');
        const trigger = $('#amadex-passengers-cabin-trigger');
        const doneBtn = $('#amadex-passengers-done');
        
        let passengers = {
            adults: 1,
            children: 0,
            infantsLap: 0,
            infantsSeat: 0
        };
        let selectedCabin = 'ECONOMY';
        
        trigger.on('click', function() {
            trigger.addClass('active');
            modal.show();
        });
        
        $('.amadex-cabin-btn').on('click', function() {
            $('.amadex-cabin-btn').removeClass('active');
            $(this).addClass('active');
            selectedCabin = $(this).data('cabin');
        });
        
        $('.amadex-counter-btn').on('click', function() {
            const target = $(this).data('target');
            const isPlus = $(this).hasClass('amadex-counter-plus');
            const counterValue = $('#amadex-' + target + '-count');
            let currentValue = parseInt(counterValue.text());
            
            if (isPlus) {
                const maxLimits = {
                    'adults': 9,
                    'children': 9,
                    'infants-lap': 9,
                    'infants-seat': 9
                };
                
                if (currentValue < maxLimits[target]) {
                    currentValue++;
                }
            } else {
                const minLimits = {
                    'adults': 1,
                    'children': 0,
                    'infants-lap': 0,
                    'infants-seat': 0
                };
                
                if (currentValue > minLimits[target]) {
                    currentValue--;
                }
            }
            
            counterValue.text(currentValue);
            updateCounterButtons(target, currentValue);
            
            if (target === 'adults') passengers.adults = currentValue;
            else if (target === 'children') passengers.children = currentValue;
            else if (target === 'infants-lap') passengers.infantsLap = currentValue;
            else if (target === 'infants-seat') passengers.infantsSeat = currentValue;
        });
        
        function updateCounterButtons(target, value) {
            const minusBtn = $(`.amadex-counter-minus[data-target="${target}"]`);
            const plusBtn = $(`.amadex-counter-plus[data-target="${target}"]`);
            
            const minLimits = {
                'adults': 1,
                'children': 0,
                'infants-lap': 0,
                'infants-seat': 0
            };
            
            const maxLimits = {
                'adults': 9,
                'children': 9,
                'infants-lap': 9,
                'infants-seat': 9
            };
            
            if (value <= minLimits[target]) {
                minusBtn.prop('disabled', true);
            } else {
                minusBtn.prop('disabled', false);
            }
            
            if (value >= maxLimits[target]) {
                plusBtn.prop('disabled', true);
            } else {
                plusBtn.prop('disabled', false);
            }
        }
        
        updateCounterButtons('adults', 1);
        updateCounterButtons('children', 0);
        updateCounterButtons('infants-lap', 0);
        updateCounterButtons('infants-seat', 0);
        
        doneBtn.on('click', function() {
            $('#amadex-adults').val(passengers.adults);
            $('#amadex-children').val(passengers.children);
            $('#amadex-infants-lap').val(passengers.infantsLap);
            $('#amadex-infants-seat').val(passengers.infantsSeat);
            $('#amadex-infants').val(passengers.infantsLap + passengers.infantsSeat);
            $('#amadex-cabin').val(selectedCabin);
            
            updatePassengersSummary(passengers, selectedCabin);
            
            trigger.removeClass('active');
            modal.hide();
        });
        
        modal.find('.amadex-modal-close').on('click', function() {
            trigger.removeClass('active');
            modal.hide();
        });
        
        modal.on('click', function(e) {
            if (e.target === this) {
                trigger.removeClass('active');
                modal.hide();
            }
        });
    }
    
    function updatePassengersSummary(passengers, cabin) {
        let summary = [];
        
        if (passengers.adults > 0) {
            summary.push(`${passengers.adults} Adult${passengers.adults > 1 ? 's' : ''}`);
        }
        if (passengers.children > 0) {
            summary.push(`${passengers.children} Child${passengers.children > 1 ? 'ren' : ''}`);
        }
        if (passengers.infantsLap > 0) {
            summary.push(`${passengers.infantsLap} Infant${passengers.infantsLap > 1 ? 's' : ''} (lap)`);
        }
        if (passengers.infantsSeat > 0) {
            summary.push(`${passengers.infantsSeat} Infant${passengers.infantsSeat > 1 ? 's' : ''} (seat)`);
        }
        
        const paxText = summary.join(', ');
        const cabinText = getCabinDisplayName(cabin);
        
        $('#amadex-pax-summary').text(paxText);
        $('#amadex-cabin-summary').text(cabinText);
    }

    /**
     * Initialize results page functionality
     */
    function initResultsPage() {
        // Only initialize if we're on a results page
        if (!isResultsPage()) {
            return;
        }

        // Initialize currency detection and conversion
        initCurrencyDetection();

        // ── Desktop sidebar filter event bindings ────────────────────────
        // Airline checkboxes
        // $(document).on('change', '.amadex-filters-sidebar input[name="airlines"]', function() {
        //     applyFilters();
        // });

        // Stops checkboxes
        // $(document).on('change', '.amadex-filters-sidebar input[name="stops"]', function() {
        //     applyFilters();
        // });

        // Departure time checkboxes
        // $(document).on('change', '.amadex-filters-sidebar input[name="outbound_departure"], .amadex-filters-sidebar input[name="departure_time"]', function() {
        //     applyFilters();
        // });

        // Arrival time checkboxes
        // $(document).on('change', '.amadex-filters-sidebar input[name="outbound_arrival"], .amadex-filters-sidebar input[name="return_time"]', function() {
        //     applyFilters();
        // });

        // Price range sliders — debounced
        // var priceSliderTimer;
        // $(document).on('input change', '#amadex-price-min, #amadex-price-max', function() {
        //     var minVal = parseFloat($('#amadex-price-min').val());
        //     var maxVal = parseFloat($('#amadex-price-max').val());

        //     // Enforce min < max
        //     if (minVal > maxVal) {
        //         if ($(this).attr('id') === 'amadex-price-min') {
        //             $('#amadex-price-min').val(maxVal - 1);
        //         } else {
        //             $('#amadex-price-max').val(minVal + 1);
        //         }
        //     }

        //     // Update display labels
        //     $('#amadex-price-min-display').text('$' + Math.round($('#amadex-price-min').val()));
        //     $('#amadex-price-max-display').text('$' + Math.round($('#amadex-price-max').val()));

        //     clearTimeout(priceSliderTimer);
        //     priceSliderTimer = setTimeout(function() {
        //         applyFilters();
        //     }, 300);
        // });

        // Clear all filters button
        // $(document).on('click', '#amadex-clear-filters', function() {
        //     $('.amadex-filters-sidebar input[type="checkbox"]').prop('checked', false);
        //     var $minSlider = $('#amadex-price-min');
        //     var $maxSlider = $('#amadex-price-max');
        //     if ($minSlider.length) {
        //         $minSlider.val($minSlider.attr('min'));
        //         $('#amadex-price-min-display').text('$' + $minSlider.attr('min'));
        //     }
        //     if ($maxSlider.length) {
        //         $maxSlider.val($maxSlider.attr('max'));
        //         $('#amadex-price-max-display').text('$' + $maxSlider.attr('max'));
        //     }
        //     $('#amadex-active-filters').empty();
        //     // Clear filter params from URL
        //     var urlParams = new URLSearchParams(window.location.search);
        //     ['f_airlines','f_stops','f_price_min','f_price_max','f_dep_time','f_arr_time'].forEach(function(k) { urlParams.delete(k); });
        //     window.history.replaceState({}, '', window.location.pathname + '?' + urlParams.toString());
        //     applyFilters();
        // });



        // Expose applyFilters globally so mobile filters can also call it
        // window.applyFilters = applyFilters;
        // Expose applyFilters globally so mobile filters can also call it
        window.applyFilters = applyFilters;
        // Expose syncFiltersToURL so amadex-filters.js can sync URL state
        // Expose syncFiltersToURL so amadex-filters.js can sync URL state
    window.syncFiltersToURL = syncFiltersToURL;

    // ── Populate Search Summary Banner from URL params ────────────────────────
    (function populateSearchSummaryBanner() {
        const banner = document.getElementById('amadex-search-summary-banner');
        if (!banner) return;

        const p = new URLSearchParams(window.location.search);

        const originIata  = (p.get('origin_iata')       || '').toUpperCase();
        const destIata    = (p.get('destination_iata')   || '').toUpperCase();
        const originName  = p.get('origin_name')      || originIata;
        const destName    = p.get('destination_name')  || destIata;
        const departDate  = p.get('depart_date')       || '';
        const returnDate  = p.get('return_date')       || '';
        const adults      = parseInt(p.get('adults'))  || 1;
        const children    = parseInt(p.get('children'))|| 0;
        const infants     = parseInt(p.get('infants')) || 0;
        const cabin       = p.get('cabin')             || 'ECONOMY';
        const tripType    = p.get('trip_type') || (returnDate ? 'roundtrip' : 'oneway');
        const segmentsRaw = p.get('segments')          || '';

        if (!originIata && !destIata && !segmentsRaw) { banner.style.display = 'none'; return; }

        function fmtDate(str) {
            if (!str) return '';
            const d = new Date(str + 'T00:00:00');
            if (isNaN(d)) return str;
            return d.toLocaleDateString('en-US', { day: 'numeric', month: 'short' });
        }
        function cabinLabel(c) {
            const map = { ECONOMY: 'Economy', PREMIUM_ECONOMY: 'Prem. Economy', BUSINESS: 'Business', FIRST: 'First Class' };
            return map[(c || '').toUpperCase()] || c;
        }
        function travellerText() {
            const parts = [];
            if (adults)   parts.push(adults   + ' Adult'  + (adults   > 1 ? 's' : ''));
            if (children) parts.push(children + ' Child'  + (children > 1 ? 'ren' : ''));
            if (infants)  parts.push(infants  + ' Infant' + (infants  > 1 ? 's' : ''));
            return parts.join(', ') || '1 Adult';
        }
        const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };

        // ── Multi-city ──────────────────────────────────────────────────────────
        const isMultiCity = tripType === 'multi-city' || tripType === 'multicity';
        if (isMultiCity && segmentsRaw) {
            let segments = [];
            try { segments = JSON.parse(decodeURIComponent(segmentsRaw)); } catch(e) {}

            if (segments.length) {
                const routeWrapper = document.querySelector('.amadex-ssb-route');

                if (routeWrapper) {
                    // Each segment: origin → destination, separated by a pipe divider between legs
                    // Correct keys from amadex-search-modern.js: origin, destination, origin_name, destination_name, departure
                    const arrowSvg = `<svg width="14" height="8" viewBox="0 0 14 8" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 4h11M9 1l3 3-3 3" stroke="rgba(255,255,255,0.65)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
                    const legSep   = `<span style="color:rgba(255,255,255,0.25);font-size:18px;margin:0 6px;font-weight:300;">|</span>`;

                    const html = segments.map((s, i) => {
                        const orig = (s.origin || '').toUpperCase();
                        const dest = (s.destination || '').toUpperCase();
                        return `${i > 0 ? legSep : ''}<span class="amadex-ssb-iata">${orig}</span>${arrowSvg}<span class="amadex-ssb-iata">${dest}</span>`;
                    }).join('');

                    routeWrapper.innerHTML = html;
                }

// Dates: key is departure_date in URL segments (departure is used in form/session)
                const firstDate = (segments[0] && (segments[0].departure_date || segments[0].departure)) || '';
                const lastSeg   = segments[segments.length - 1];
                const lastDate  = (lastSeg && (lastSeg.departure_date || lastSeg.departure)) || '';
                const datesText = firstDate
                    ? (lastDate && lastDate !== firstDate
                        ? fmtDate(firstDate) + ' – ' + fmtDate(lastDate)
                        : fmtDate(firstDate))
                    : '—';
                set('amadex-ssb-dates-text', datesText);

                // Full route names right side: "Los Angeles → Sacramento → New York → San Francisco"
                const cityNames = [];
                segments.forEach((s, i) => {
                    const orig = (s.origin_name || s.origin || '').replace(/\s*\(.*?\)\s*/g, '').trim();
                    if (orig) cityNames.push(orig);
                    // Add last segment's destination too
                    if (i === segments.length - 1) {
                        const dest = (s.destination_name || s.destination || '').replace(/\s*\(.*?\)\s*/g, '').trim();
                        if (dest) cityNames.push(dest);
                    }
                });
                set('amadex-ssb-fullroute', cityNames.join(' → '));

                set('amadex-ssb-travellers-text', travellerText());
                set('amadex-ssb-cabin-text', cabinLabel(cabin));
                return;
            }
        }

        // ── Round trip / One way ───────────────────────────────────────────────
        let datesText = fmtDate(departDate);
        if (tripType !== 'oneway' && tripType !== 'one_way' && returnDate) {
            datesText += ' – ' + fmtDate(returnDate);
        }

        set('amadex-ssb-origin-iata',     originIata || '—');
        set('amadex-ssb-dest-iata',       destIata   || '—');
        set('amadex-ssb-dates-text',      datesText  || '—');
        set('amadex-ssb-travellers-text', travellerText());
        set('amadex-ssb-cabin-text',      cabinLabel(cabin));
        set('amadex-ssb-fullroute',       [originName, destName].filter(Boolean).join(' → '));
    })();
    // ── End Search Summary Banner ─────────────────────────────────────────────

    // ── Banner Edit button → toggle amadex-route-header with blur backdrop ────
    // Inject blur backdrop once
    // ── Modify Search Popup ───────────────────────────────────────────────────
    function amadexBuildModifyPopup() {
        if ($('#amadex-modify-popup').length) return;

        const p = new URLSearchParams(window.location.search);
        const originName  = p.get('origin_name')      || '';
        const originIata  = (p.get('origin_iata')      || '').toUpperCase();
        const destName    = p.get('destination_name')  || '';
        const destIata    = (p.get('destination_iata') || '').toUpperCase();
        const departDate  = p.get('depart_date')       || '';
        const returnDate  = p.get('return_date')       || '';
        const adults      = parseInt(p.get('adults'))  || 1;
        const children    = parseInt(p.get('children'))|| 0;
        const infants     = parseInt(p.get('infants')) || 0;
        const cabin       = (p.get('cabin') || 'ECONOMY').toUpperCase();
// Read trip_type from URL first, then sessionStorage as fallback
        let tripType = p.get('trip_type') || '';
        if (!tripType) {
            try {
                const sd = JSON.parse(sessionStorage.getItem('amadex_search_data') || '{}');
                tripType = sd.trip_type || sd.tripType || '';
            } catch(e) {}
        }
        if (!tripType) tripType = returnDate ? 'roundtrip' : 'oneway';
        // const isRound = tripType !== 'oneway' && tripType !== 'one_way' && tripType !== 'multi-city';
        // let tripType = p.get('trip_type') || '';
        // if (!tripType) {
        //     try {
        //         const sd = JSON.parse(sessionStorage.getItem('amadex_search_data') || '{}');
        //         tripType = sd.trip_type || sd.tripType || '';
        //     } catch(e) {}
        // }
        // if (!tripType) tripType = returnDate ? 'roundtrip' : 'oneway';
        const isRound = tripType !== 'oneway' && tripType !== 'one_way' && tripType !== 'multi-city';
        // const resultsPage = typeof AmadexConfig !== 'undefined' ? (AmadexConfig.resultsPage || AmadexConfig.results_page || '') : '';

        const resultsPage = typeof AmadexConfig !== 'undefined' ? (AmadexConfig.resultsPage || AmadexConfig.results_page || '') : '';

        const cabins = [
            { val: 'ECONOMY',         label: 'Economy' },
            { val: 'PREMIUM_ECONOMY', label: 'Premium Economy' },
            { val: 'BUSINESS',        label: 'Business' },
            { val: 'FIRST',           label: 'First Class' }
        ];
        const cabinOpts = cabins.map(c =>
            `<option value="${c.val}"${cabin === c.val ? ' selected' : ''}>${c.label}</option>`
        ).join('');

        $('body').append(`
        <div id="amadex-modify-popup" role="dialog" aria-modal="true">
            <div id="amadex-modify-backdrop2"></div>
            <div id="amadex-modify-card">

                <div id="amadex-modify-header">
                    <div id="amadex-modify-header-left">
                        <div>
                            <div id="amadex-modify-title">Modify Search</div>
                            <div id="amadex-modify-subtitle">Update your flight preferences</div>
                        </div>
                    </div>
                    <button id="amadex-modify-close" type="button" aria-label="Close">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M1 1l14 14M15 1L1 15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </button>
                </div>

<div id="amadex-modify-trip-tabs">
                    <button type="button" class="amod-tab${isRound ? ' amod-tab-active' : ''}" data-trip="round">Round Trip</button>
                    <button type="button" class="amod-tab${(tripType === 'oneway' || tripType === 'one_way') ? ' amod-tab-active' : ''}" data-trip="oneway">One Way</button>
                    <button type="button" class="amod-tab${(tripType === 'multi-city' || tripType === 'multicity') ? ' amod-tab-active' : ''}" data-trip="multi-city">Multi-City</button>
                </div>

                <div id="amadex-modify-form-body">

                    <div class="amod-row amod-route-row">
                        <div class="amod-field" id="amod-origin-field">
                            <label class="amod-label">From</label>
                            <div class="amod-input-wrap">
                                <svg class="amod-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                                <input type="text" id="amod-origin-input" class="amod-input" placeholder="City or Airport" autocomplete="off"
                                    value="${originName || originIata}">
                                <input type="hidden" id="amod-origin-code" value="${originIata}">
                            </div>
                            <div class="amod-suggestions" id="amod-origin-suggestions"></div>
                        </div>

                        <button type="button" id="amod-swap-btn" title="Swap">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/></svg>
                        </button>

                        <div class="amod-field" id="amod-dest-field">
                            <label class="amod-label">To</label>
                            <div class="amod-input-wrap">
                                <svg class="amod-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                <input type="text" id="amod-dest-input" class="amod-input" placeholder="City or Airport" autocomplete="off"
                                    value="${destName || destIata}">
                                <input type="hidden" id="amod-dest-code" value="${destIata}">
                            </div>
                            <div class="amod-suggestions" id="amod-dest-suggestions"></div>
                        </div>
                    </div>

<div class="amod-row amod-dates-row">
                        <div class="amod-field">
                            <label class="amod-label">Departure</label>
                            <div class="amod-input-wrap amod-date-trigger" id="amod-depart-trigger" data-target="depart">
                                <svg class="amod-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                <span class="amod-date-display" id="amod-depart-display">${departDate ? amodFmtDate(departDate) : 'Select date'}</span>
                                <input type="hidden" id="amod-depart-date" value="${departDate}">
                            </div>
                        </div>
                        <div class="amod-field" id="amod-return-field"${!isRound ? ' style="opacity:0.4;pointer-events:none;"' : ''}>
                            <label class="amod-label">Return</label>
                            <div class="amod-input-wrap amod-date-trigger" id="amod-return-trigger" data-target="return">
                                <svg class="amod-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                <span class="amod-date-display" id="amod-return-display">${returnDate ? amodFmtDate(returnDate) : 'Select date'}</span>
                                <input type="hidden" id="amod-return-date" value="${returnDate}">
                            </div>
                        </div>
                    </div>
                    <div id="amod-cal-popup" style="display:none;"></div>

                    <div class="amod-row amod-pax-row">
<div class="amod-field" id="amod-cabin-field">
                            <label class="amod-label">Cabin</label>
                            <div class="amod-input-wrap amod-cabin-trigger" id="amod-cabin-trigger">
                                <svg class="amod-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                <span id="amod-cabin-display" class="amod-input" style="cursor:pointer;">${cabins.find(c=>c.val===cabin)?.label || 'Economy'}</span>
                                <svg style="margin-left:auto;flex-shrink:0;opacity:0.5" width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                                <input type="hidden" id="amod-cabin" value="${cabin}">
                            </div>
                            <div id="amod-cabin-panel">
                                ${cabins.map(c => `
                                    <div class="amod-cabin-opt${c.val===cabin?' amod-cabin-opt-active':''}" data-val="${c.val}">
                                        <div class="amod-cabin-opt-info">
                                            <div class="amod-cabin-opt-label">${c.label}</div>
                                        </div>
                                        <svg class="amod-cabin-check" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                    </div>`).join('')}
                            </div>
                        </div>

                        <div class="amod-field" id="amod-travellers-field">
                            <label class="amod-label">Travellers</label>
                            <div class="amod-input-wrap amod-travellers-trigger" id="amod-travellers-trigger">
                                <svg class="amod-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                                <span id="amod-travellers-display" class="amod-input" style="cursor:pointer;display:flex;align-items:center;"></span>
                                <svg style="margin-left:auto;flex-shrink:0;opacity:0.5" width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                            </div>
                            <div id="amod-travellers-panel">
                                <div class="amod-pax-row-item">
                                    <div><div class="amod-pax-label">Adults</div><div class="amod-pax-sub">12+ years</div></div>
                                    <div class="amod-counter">
                                        <button type="button" class="amod-count-btn" data-field="adults" data-action="minus">−</button>
                                        <span id="amod-adults-count">${adults}</span>
                                        <button type="button" class="amod-count-btn" data-field="adults" data-action="plus">+</button>
                                    </div>
                                </div>
                                <div class="amod-pax-row-item">
                                    <div><div class="amod-pax-label">Children</div><div class="amod-pax-sub">2–12 years</div></div>
                                    <div class="amod-counter">
                                        <button type="button" class="amod-count-btn" data-field="children" data-action="minus">−</button>
                                        <span id="amod-children-count">${children}</span>
                                        <button type="button" class="amod-count-btn" data-field="children" data-action="plus">+</button>
                                    </div>
                                </div>
                                <div class="amod-pax-row-item">
                                    <div><div class="amod-pax-label">Infants</div><div class="amod-pax-sub">Under 2</div></div>
                                    <div class="amod-counter">
                                        <button type="button" class="amod-count-btn" data-field="infants" data-action="minus">−</button>
                                        <span id="amod-infants-count">${infants}</span>
                                        <button type="button" class="amod-count-btn" data-field="infants" data-action="plus">+</button>
                                    </div>
                                </div>
                                <button type="button" id="amod-travellers-done">Done</button>
                            </div>
                        </div>
                    </div>

                </div>

                <div id="amadex-modify-footer">
                    <button type="button" id="amod-cancel-btn">Cancel</button>
                    <button type="button" id="amod-search-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        Search Flights
                    </button>
                </div>

            </div>
        </div>`);

        // Set today as min date
        const today = new Date().toISOString().split('T')[0];
        $('#amod-depart-date, #amod-return-date').attr('min', today);

        // Update travellers display
        function amodUpdateTravellersDisplay() {
            const a = parseInt($('#amod-adults-count').text()) || 1;
            const c = parseInt($('#amod-children-count').text()) || 0;
            const i = parseInt($('#amod-infants-count').text()) || 0;
            const total = a + c + i;
            const parts = [`${a} Adult${a>1?'s':''}`];
            if (c) parts.push(`${c} Child${c>1?'ren':''}`);
            if (i) parts.push(`${i} Infant${i>1?'s':''}`);
            $('#amod-travellers-display').text(parts.join(', '));
        }
amodUpdateTravellersDisplay();

        // ── Custom Calendar (dual month) ─────────────────────────────────────
        function amodFmtDate(str) {
            if (!str) return '';
            const d = new Date(str + 'T00:00:00');
            if (isNaN(d)) return str;
            return d.toLocaleDateString('en-US', { weekday:'short', month:'short', day:'numeric', year:'numeric' });
        }
        function amodFmtISO(d) {
            return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
        }

        let amodCalYear, amodCalMonth;
        // Selection state: 0 = picking depart, 1 = picking return
        let amodCalPhase = 0;
        let amodCalHover = null;

        function amodIsRoundTrip() {
            return $('.amod-tab-active').data('trip') !== 'oneway';
        }

function amodRenderDualCal() {
            $('#amod-cal-popup').css('visibility', 'hidden'); // prevent flicker during rebuild
            const today   = new Date(); today.setHours(0,0,0,0);
            const depV    = $('#amod-depart-date').val();
            const retV    = $('#amod-return-date').val();
            const depD    = depV ? new Date(depV + 'T00:00:00') : null;
            const retD    = retV ? new Date(retV + 'T00:00:00') : null;
            const hoverD  = amodCalHover ? new Date(amodCalHover + 'T00:00:00') : null;
            const months  = ['January','February','March','April','May','June','July','August','September','October','November','December'];
            const days    = ['Su','Mo','Tu','We','Th','Fr','Sa'];
            const isRound = amodIsRoundTrip();

            function buildMonth(year, month) {
                const first    = new Date(year, month, 1);
                const last     = new Date(year, month+1, 0);
                const startDay = first.getDay();
                const dayHeaders = days.map(d => `<div class="amod-cal-day-header">${d}</div>`).join('');
                let cells = '';
                for (let i = 0; i < startDay; i++) cells += `<div class="amod-cal-cell amod-cal-blank"></div>`;
                for (let d = 1; d <= last.getDate(); d++) {
                    const thisDate = new Date(year, month, d);
                    const iso      = amodFmtISO(thisDate);
                    const isPast   = thisDate < today;
                    const isToday  = thisDate.getTime() === today.getTime();
                    const isDep    = depD && thisDate.getTime() === depD.getTime();
                    const isRet    = retD && thisDate.getTime() === retD.getTime();
                    // Range highlight — between dep and ret (or dep and hover if picking return)
                    const rangeEnd = (amodCalPhase === 1 && hoverD && depD && !retD) ? hoverD : retD;
                    const inRange  = depD && rangeEnd && thisDate > depD && thisDate < rangeEnd;
                    let cls = 'amod-cal-cell';
                    if (isPast)   cls += ' amod-cal-past';
                    if (isToday)  cls += ' amod-cal-today';
                    if (isDep)    cls += ' amod-cal-selected amod-cal-dep';
                    if (isRet)    cls += ' amod-cal-selected amod-cal-ret';
                    if (inRange)  cls += ' amod-cal-range';
                    if (amodCalHover === iso && amodCalPhase === 1) cls += ' amod-cal-hover-end';
                    cells += `<div class="${cls}"${!isPast ? ` data-date="${iso}"` : ''}>${d}</div>`;
                }
                return `
                    <div class="amod-cal-month-block">
                        <div class="amod-cal-month-title">${months[month]} ${year}</div>
                        <div class="amod-cal-days-grid">
                            ${dayHeaders}${cells}
                        </div>
                    </div>`;
            }

            // Second month
            let m2 = amodCalMonth + 1, y2 = amodCalYear;
            if (m2 > 11) { m2 = 0; y2++; }

            const phaseLabel = amodCalPhase === 0
                ? '<span class="amod-cal-phase amod-cal-phase-dep">Select departure</span>'
                : '<span class="amod-cal-phase amod-cal-phase-ret">Select return</span>';

            // Selected summary
            const depLabel = depV ? `<span class="amod-cal-sel-date">${amodFmtDate(depV)}</span>` : `<span class="amod-cal-sel-empty">Departure</span>`;
            const retLabel = !isRound ? '' : (retV ? `<span class="amod-cal-sel-date">${amodFmtDate(retV)}</span>` : `<span class="amod-cal-sel-empty">Return</span>`);
            const arrowIcon = !isRound ? '' : `<svg width="16" height="10" viewBox="0 0 24 10" fill="none"><path d="M1 5h21M18 1l4 4-4 4" stroke="rgba(255,255,255,0.4)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>`;

            $('#amod-cal-popup').html(`
                <div id="amod-cal-inner">
                    <div id="amod-cal-top">
                        <div id="amod-cal-selected-bar">
                            ${depLabel}${arrowIcon}${retLabel}
                        </div>
                        ${isRound ? phaseLabel : ''}
                    </div>
                    <div id="amod-cal-nav">
                        <button class="amod-cal-nav-btn" id="amod-cal-prev">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M15 18l-6-6 6-6"/></svg>
                        </button>
<div id="amod-cal-months-wrap">
                            ${buildMonth(amodCalYear, amodCalMonth)}
                            ${buildMonth(y2, m2)}
                        </div>
                        <button class="amod-cal-nav-btn" id="amod-cal-next">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M9 18l6-6-6-6"/></svg>
                        </button>
                    </div>
                    <div id="amod-cal-footer">
                        <button type="button" id="amod-cal-clear">Clear dates</button>
                        <button type="button" id="amod-cal-done">Done</button>
                    </div>
                </div>
`);
            $('#amod-cal-popup').css('visibility', ''); // restore after rebuild
        }

        function amodOpenDualCal() {
            const depV = $('#amod-depart-date').val();
            const retV = $('#amod-return-date').val();
            // If both set, re-pick from depart; if depart set, pick return next
            if (!depV) { amodCalPhase = 0; }
            else if (!retV && amodIsRoundTrip()) { amodCalPhase = 1; }
            else { amodCalPhase = 0; }

            const ref = depV ? new Date(depV + 'T00:00:00') : new Date();
            amodCalYear  = ref.getFullYear();
            amodCalMonth = ref.getMonth();
            amodCalHover = null;

$('#amod-cal-popup').show();
            amodRenderDualCal();
        }

        function amodCloseDualCal() {
            $('#amod-cal-popup').hide();
            amodCalHover = null;
        }

        // Open cal on clicking either date trigger
        $(document).on('click', '.amod-date-trigger', function(e) {
            e.stopPropagation();
            if ($('#amod-cal-popup').is(':visible')) { amodCloseDualCal(); return; }
            const target = $(this).data('target');
            amodCalPhase = (target === 'return' && amodIsRoundTrip()) ? 1 : 0;
            const depV = $('#amod-depart-date').val();
            const ref  = depV ? new Date(depV + 'T00:00:00') : new Date();
            amodCalYear  = ref.getFullYear();
            amodCalMonth = ref.getMonth();
            amodCalHover = null;
            const $datesRow = $('.amod-dates-row');
            const rowBottom = $datesRow.position().top + $datesRow.outerHeight() + 6;
            $('#amod-cal-popup').css({ top: rowBottom + 'px', left: '0', right: '0' }).show();
            amodRenderDualCal();
        });

        // Navigate months
        $(document).on('click', '#amod-cal-prev', function(e) {
            e.stopPropagation();
            amodCalMonth--; if (amodCalMonth < 0) { amodCalMonth = 11; amodCalYear--; }
            amodRenderDualCal();
        });
        $(document).on('click', '#amod-cal-next', function(e) {
            e.stopPropagation();
            amodCalMonth++; if (amodCalMonth > 11) { amodCalMonth = 0; amodCalYear++; }
            amodRenderDualCal();
        });

$(document).on('mouseenter', '.amod-cal-cell[data-date]', function() {
            if (amodCalPhase !== 1) return;
            const newHover = $(this).data('date');
            if (newHover === amodCalHover) return;
            amodCalHover = newHover;

            // Update range highlight in-place — no full re-render
            const depV = $('#amod-depart-date').val();
            if (!depV) return;
            const depD   = new Date(depV + 'T00:00:00');
            const hoverD = new Date(newHover + 'T00:00:00');

            $('#amod-cal-popup .amod-cal-cell[data-date]').each(function() {
                const iso       = $(this).data('date');
                const cellDate  = new Date(iso + 'T00:00:00');
                const inRange   = cellDate > depD && cellDate < hoverD;
                const isHoverEnd = iso === newHover;

                $(this)
                    .toggleClass('amod-cal-range',    inRange && !isHoverEnd)
                    .toggleClass('amod-cal-hover-end', isHoverEnd);
            });
        });

        $(document).on('mouseleave', '#amod-cal-inner', function() {
            if (amodCalPhase !== 1) return;
            // Clear hover highlights without re-rendering
            $('#amod-cal-popup .amod-cal-cell').removeClass('amod-cal-hover-end');
            // Keep range only if return already selected
            const retV = $('#amod-return-date').val();
            if (!retV) $('#amod-cal-popup .amod-cal-cell').removeClass('amod-cal-range');
            amodCalHover = null;
        });

        // Click a date
        $(document).on('click', '.amod-cal-cell[data-date]', function(e) {
            e.stopPropagation();
            const iso    = $(this).data('date');
            const isRound = amodIsRoundTrip();

            if (amodCalPhase === 0) {
                // Picking departure
                $('#amod-depart-date').val(iso);
                $('#amod-depart-display').text(amodFmtDate(iso));
                // Clear return if now before depart
                const retV = $('#amod-return-date').val();
                if (retV && retV <= iso) {
                    $('#amod-return-date').val('');
                    $('#amod-return-display').text('Select date');
                }
                if (isRound) {
                    amodCalPhase = 1; // Auto-advance to return
                    amodCalHover = null;
                    amodRenderDualCal();
                } else {
                    amodCloseDualCal();
                }
            } else {
                // Picking return
                const depV = $('#amod-depart-date').val();
                if (depV && iso <= depV) {
                    // Clicked before depart — reset depart to this date
                    $('#amod-depart-date').val(iso);
                    $('#amod-depart-display').text(amodFmtDate(iso));
                    $('#amod-return-date').val('');
                    $('#amod-return-display').text('Select date');
                    amodCalPhase = 1;
                    amodCalHover = null;
                    amodRenderDualCal();
                    return;
                }
                $('#amod-return-date').val(iso);
                $('#amod-return-display').text(amodFmtDate(iso));
                amodCalHover = null;
                amodRenderDualCal();
                // Auto close after short delay so user sees the selection
                setTimeout(amodCloseDualCal, 320);
            }
        });

        $(document).on('click', '#amod-cal-clear', function(e) {
            e.stopPropagation();
            $('#amod-depart-date, #amod-return-date').val('');
            $('#amod-depart-display, #amod-return-display').text('Select date');
            amodCalPhase = 0; amodCalHover = null;
            amodRenderDualCal();
        });
        $(document).on('click', '#amod-cal-done', function(e) {
            e.stopPropagation();
            amodCloseDualCal();
        });
$(document).on('click.amodcal', function(e) {
            if (!$(e.target).closest('#amod-cal-popup, .amod-date-trigger, #amadex-modify-card').length) {
                amodCloseDualCal();
            }
        });
        // ── End Custom Calendar ───────────────────────────────────────────────

        // Counter buttons

        // Counter buttons
        $(document).on('click', '.amod-count-btn', function() {
            const field  = $(this).data('field');
            const action = $(this).data('action');
            const $span  = $(`#amod-${field}-count`);
            let val = parseInt($span.text()) || 0;
            const a = parseInt($('#amod-adults-count').text()) || 1;
            const c = parseInt($('#amod-children-count').text()) || 0;
            const i = parseInt($('#amod-infants-count').text()) || 0;
            const total = a + c + i;
            if (action === 'plus') {
                if (total >= 9) return;
                if (field === 'adults' && val >= 9) return;
                val++;
            } else {
                if (field === 'adults' && val <= 1) return;
                if (field !== 'adults' && val <= 0) return;
                val--;
            }
            $span.text(val);
            amodUpdateTravellersDisplay();
        });
// Cabin panel toggle
        $(document).on('click', '#amod-cabin-trigger', function(e) {
            e.stopPropagation();
            $('#amod-cabin-panel').toggleClass('amod-panel-open');
            $('#amod-travellers-panel').removeClass('amod-panel-open');
        });
        $(document).on('click', '.amod-cabin-opt', function() {
            const val   = $(this).data('val');
            const label = $(this).find('.amod-cabin-opt-label').text();
            $('#amod-cabin').val(val);
            $('#amod-cabin-display').text(label);
            $('.amod-cabin-opt').removeClass('amod-cabin-opt-active');
            $(this).addClass('amod-cabin-opt-active');
            $('#amod-cabin-panel').removeClass('amod-panel-open');
        });
        // Travellers panel toggle
        $(document).on('click', '#amod-travellers-trigger', function(e) {
            e.stopPropagation();
            $('#amod-travellers-panel').toggleClass('amod-panel-open');
        });
        $(document).on('click', '#amod-travellers-done', function() {
            $('#amod-travellers-panel').removeClass('amod-panel-open');
        });
$(document).on('click', '#amadex-modify-card', function(e) {
            if (!$(e.target).closest('#amod-travellers-field').length) {
                $('#amod-travellers-panel').removeClass('amod-panel-open');
            }
            if (!$(e.target).closest('#amod-cabin-field').length) {
                $('#amod-cabin-panel').removeClass('amod-panel-open');
            }
        });

// Trip type tabs
        $(document).on('click', '.amod-tab', function() {
            $('.amod-tab').removeClass('amod-tab-active');
            $(this).addClass('amod-tab-active');
            const trip = $(this).data('trip');
            const isRound = trip === 'round';
            const isMulti = trip === 'multi-city';

            // Return field
            $('#amod-return-field').css({ opacity: isRound ? 1 : 0.4, 'pointer-events': isRound ? '' : 'none' });
            if (!isRound) $('#amod-return-date').val('');

// Multi-city: show segment builder, hide standard fields
            if (isMulti) {
                $('.amod-dates-row, .amod-route-row').hide();
                $('#amod-return-field').hide();
                if (!$('#amod-segments-block').length) {
                    const seg1Origin = $('#amod-origin-code').val() || '';
                    const seg1OriginName = $('#amod-origin-input').val() || '';
                    const seg1Dest = $('#amod-dest-code').val() || '';
                    const seg1DestName = $('#amod-dest-input').val() || '';
                    const seg1Date = $('#amod-depart-date').val() || '';
                    $('#amadex-modify-form-body').prepend(`
                        <div id="amod-segments-block">
                            <div class="amod-seg-list" id="amod-seg-list"></div>
                            <button type="button" id="amod-add-seg-btn">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                Add City
                            </button>
                        </div>`);
                    // Add first 2 segments
                    amodAddSegment(seg1OriginName || seg1Origin, seg1Origin, seg1DestName || seg1Dest, seg1Dest, seg1Date);
                    amodAddSegment('', '', '', '', '');
                }
                $('#amod-segments-block').show();
} else {
                $('#amod-segments-block').hide();
                $('.amod-dates-row, .amod-route-row').show();
                const nowRound = trip === 'round';
                // Clear any inline display:none left from multi-city toggle
                $('#amod-return-field').css({
                    display: '',
                    opacity: nowRound ? 1 : 0.4,
                    'pointer-events': nowRound ? '' : 'none'
                });
            }
        });
// Multi-city segment builder
        let amodSegCount = 0;
        function amodAddSegment(originName, originCode, destName, destCode, date) {
            amodSegCount++;
            const idx = amodSegCount;
            $('#amod-seg-list').append(`
                <div class="amod-seg" data-seg="${idx}">
                    <div class="amod-seg-num">${idx}</div>
                    <div class="amod-seg-fields">
                        <div class="amod-seg-field" style="position:relative">
                            <label class="amod-label">From</label>
                            <div class="amod-input-wrap">
                                <svg class="amod-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                                <input type="text" class="amod-input amod-seg-origin-input" placeholder="City or airport" autocomplete="off" value="${originName}">
                                <input type="hidden" class="amod-seg-origin-code" value="${originCode}">
                            </div>
                            <div class="amod-suggestions amod-seg-origin-sug"></div>
                        </div>
                        <div class="amod-seg-field" style="position:relative">
                            <label class="amod-label">To</label>
                            <div class="amod-input-wrap">
                                <svg class="amod-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                <input type="text" class="amod-input amod-seg-dest-input" placeholder="City or airport" autocomplete="off" value="${destName}">
                                <input type="hidden" class="amod-seg-dest-code" value="${destCode}">
                            </div>
                            <div class="amod-suggestions amod-seg-dest-sug"></div>
                        </div>
<div class="amod-seg-field amod-seg-date-field" style="position:relative;">
                            <label class="amod-label">Date</label>
                            <div class="amod-input-wrap amod-seg-date-trigger" style="cursor:pointer;">
                                <svg class="amod-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                <span class="amod-seg-date-display amod-input">${date ? amodFmtDate(date) : 'Select date'}</span>
                                <input type="hidden" class="amod-seg-date" value="${date}">
                            </div>
                            <div class="amod-seg-cal-popup" style="display:none;"></div>
                        </div>
                    </div>
                    ${idx > 2 ? `<button type="button" class="amod-seg-remove" data-seg="${idx}" title="Remove">
                        <svg width="12" height="12" viewBox="0 0 16 16" fill="none"><path d="M1 1l14 14M15 1L1 15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </button>` : ''}
                </div>`);

            // Autocomplete for new segment
            const $seg = $(`#amod-seg-list .amod-seg[data-seg="${idx}"]`);
            $seg.find('.amod-seg-origin-input').on('input', function() {
                amodAirportSearch($(this).val(), $seg.find('.amod-seg-origin-sug'), $seg.find('.amod-seg-origin-code'));
            });
            $seg.find('.amod-seg-dest-input').on('input', function() {
                amodAirportSearch($(this).val(), $seg.find('.amod-seg-dest-sug'), $seg.find('.amod-seg-dest-code'));
            });
        }
// ── Segment date calendar ─────────────────────────────────────────────
        function amodRenderSegCal($popup, selectedDate) {
            const today  = new Date(); today.setHours(0,0,0,0);
            const selD   = selectedDate ? new Date(selectedDate + 'T00:00:00') : null;
            const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
            const days   = ['Su','Mo','Tu','We','Th','Fr','Sa'];
            const year   = $popup.data('cal-year') || today.getFullYear();
            const month  = $popup.data('cal-month') !== undefined ? $popup.data('cal-month') : today.getMonth();
            $popup.data('cal-year', year).data('cal-month', month);

            const first    = new Date(year, month, 1);
            const last     = new Date(year, month+1, 0);
            const startDay = first.getDay();
            let cells = '';
            for (let i = 0; i < startDay; i++) cells += `<div class="amod-cal-cell amod-cal-blank"></div>`;
            for (let d = 1; d <= last.getDate(); d++) {
                const thisDate = new Date(year, month, d);
                const iso      = amodFmtISO(thisDate);
                const isPast   = thisDate < today;
                const isSel    = selD && thisDate.getTime() === selD.getTime();
                const isToday  = thisDate.getTime() === today.getTime();
                let cls = 'amod-cal-cell';
                if (isPast)  cls += ' amod-cal-past';
                if (isToday) cls += ' amod-cal-today';
                if (isSel)   cls += ' amod-cal-selected';
                cells += `<div class="${cls}"${!isPast ? ` data-date="${iso}"` : ''}>${d}</div>`;
            }

            $popup.html(`
                <div class="amod-seg-cal-inner">
                    <div class="amod-seg-cal-nav">
                        <button type="button" class="amod-cal-nav-btn amod-seg-cal-prev">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M15 18l-6-6 6-6"/></svg>
                        </button>
                        <span class="amod-seg-cal-title">${months[month]} ${year}</span>
                        <button type="button" class="amod-cal-nav-btn amod-seg-cal-next">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M9 18l6-6-6-6"/></svg>
                        </button>
                    </div>
                    <div class="amod-seg-cal-grid">
                        ${days.map(d => `<div class="amod-cal-day-header">${d}</div>`).join('')}
                        ${cells}
                    </div>
                </div>`);
        }

        $(document).on('click', '.amod-seg-date-trigger', function(e) {
            e.stopPropagation();
            const $field  = $(this).closest('.amod-seg-date-field');
            const $popup  = $field.find('.amod-seg-cal-popup');
            const selDate = $field.find('.amod-seg-date').val();

            // Close all other segment calendars
            $('.amod-seg-cal-popup').not($popup).hide();

            if ($popup.is(':visible')) { $popup.hide(); return; }

            const ref = selDate ? new Date(selDate + 'T00:00:00') : new Date();
            $popup.data('cal-year', ref.getFullYear()).data('cal-month', ref.getMonth());
            amodRenderSegCal($popup, selDate);
            $popup.show();
        });

        $(document).on('click', '.amod-seg-cal-prev', function(e) {
            e.stopPropagation();
            const $popup = $(this).closest('.amod-seg-cal-popup');
            let m = $popup.data('cal-month') - 1, y = $popup.data('cal-year');
            if (m < 0) { m = 11; y--; }
            $popup.data('cal-year', y).data('cal-month', m);
            amodRenderSegCal($popup, $popup.closest('.amod-seg-date-field').find('.amod-seg-date').val());
        });

        $(document).on('click', '.amod-seg-cal-next', function(e) {
            e.stopPropagation();
            const $popup = $(this).closest('.amod-seg-cal-popup');
            let m = $popup.data('cal-month') + 1, y = $popup.data('cal-year');
            if (m > 11) { m = 0; y++; }
            $popup.data('cal-year', y).data('cal-month', m);
            amodRenderSegCal($popup, $popup.closest('.amod-seg-date-field').find('.amod-seg-date').val());
        });

        $(document).on('click', '.amod-seg-cal-popup .amod-cal-cell[data-date]', function(e) {
            e.stopPropagation();
            const iso    = $(this).data('date');
            const $field = $(this).closest('.amod-seg-date-field');
            $field.find('.amod-seg-date').val(iso);
            $field.find('.amod-seg-date-display').text(amodFmtDate(iso));
            $field.find('.amod-seg-cal-popup').hide();
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('.amod-seg-date-field').length) {
                $('.amod-seg-cal-popup').hide();
            }
        });
        // ── End Segment date calendar ─────────────────────────────────────────
        $(document).on('click', '#amod-add-seg-btn', function() {
            if ($('#amod-seg-list .amod-seg').length >= 5) return;
            amodAddSegment('', '', '', '', '');
        });
        $(document).on('click', '.amod-seg-remove', function() {
                    const seg = $(this).data('seg');
                    $(`#amod-seg-list .amod-seg[data-seg="${seg}"]`).remove();
        });
        // Suggestion click for segments
        $(document).on('click', '#amod-seg-list .amod-suggestion', function() {
            const iata = $(this).data('iata');
            const name = $(this).data('name');
            const $seg = $(this).closest('.amod-seg');
            if ($(this).closest('.amod-seg-origin-sug').length) {
                $seg.find('.amod-seg-origin-input').val(name);
                $seg.find('.amod-seg-origin-code').val(iata);
                $seg.find('.amod-seg-origin-sug').hide().empty();
            } else {
                $seg.find('.amod-seg-dest-input').val(name);
                $seg.find('.amod-seg-dest-code').val(iata);
                $seg.find('.amod-seg-dest-sug').hide().empty();
            }
        });
        // Swap button
$(document).on('click', '#amod-swap-btn', function() {
            const origText = $('#amod-origin-input').val();
            const origCode = $('#amod-origin-code').val();
            const destText = $('#amod-dest-input').val();
            const destCode = $('#amod-dest-code').val();
            $('#amod-origin-input').val(destText); $('#amod-origin-code').val(destCode);
            $('#amod-dest-input').val(origText);   $('#amod-dest-code').val(origCode);
            $(this).toggleClass('amod-spin');
        });

        // Airport autocomplete — uses same action + field names as main search bar
        let amodAjaxTimer;
        function amodAirportSearch(val, $suggestions, $codeInput) {
            if (val.length < 2) { $suggestions.hide().empty(); return; }
            clearTimeout(amodAjaxTimer);
            $suggestions.html(`<div class="amod-sug-loading"><span class="amod-btn-spinner"></span> Searching...</div>`).show();
            amodAjaxTimer = setTimeout(function() {
                const ajaxUrl = typeof AmadexConfig !== 'undefined' ? AmadexConfig.ajaxUrl : '/wp-admin/admin-ajax.php';
                const nonce   = typeof AmadexConfig !== 'undefined' ? AmadexConfig.nonce : '';
                $.ajax({
                    url: ajaxUrl, type: 'POST',
                    data: { action: 'amadex_search_airports', keyword: val, nonce: nonce },
                    success: function(res) {
                        $suggestions.empty();
                        if (res.success && res.data && res.data.length) {
                            res.data.slice(0, 7).forEach(function(a) {
                                // Exact field names from displayAirportSuggestions: city, code, name, country
                                const iata    = (a.code || a.iata || a.iata_code || '').toUpperCase();
                                const city    = a.city || a.municipality || '';
                                const name    = a.name || a.airport_name || '';
                                const country = a.country || a.iso_country || '';
                                $suggestions.append(`
                                    <div class="amod-suggestion" data-iata="${iata}" data-name="${city || name}">
                                        <div class="amod-sug-iata">${iata}</div>
                                        <div class="amod-sug-info">
                                            <div class="amod-sug-city">${city || name}</div>
                                            <div class="amod-sug-airport">${name}${country ? ' · '+country : ''}</div>
                                        </div>
                                    </div>`);
                            });
                            $suggestions.show();
                        } else {
                            $suggestions.html(`<div style="padding:16px;text-align:center;color:rgba(255,255,255,0.4);font-size:13px;">No airports found</div>`).show();
                        }
                    },
                    error: function() { $suggestions.hide().empty(); }
                });
            }, 280);
        }

        $('#amod-origin-input').on('input', function() {
            amodAirportSearch($(this).val(), $('#amod-origin-suggestions'), $('#amod-origin-code'));
        });
        $('#amod-dest-input').on('input', function() {
            amodAirportSearch($(this).val(), $('#amod-dest-suggestions'), $('#amod-dest-code'));
        });

        $(document).on('click', '.amod-suggestion', function() {
            const iata  = $(this).data('iata');
            const name  = $(this).data('name');
            const $sug  = $(this).closest('.amod-suggestions');
            if ($sug.attr('id') === 'amod-origin-suggestions') {
                $('#amod-origin-input').val(name); $('#amod-origin-code').val(iata);
            } else {
                $('#amod-dest-input').val(name); $('#amod-dest-code').val(iata);
            }
            $sug.hide().empty();
        });

        // Close suggestions on outside click
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#amod-origin-field, #amod-dest-field').length) {
                $('.amod-suggestions').hide().empty();
            }
        });

        // Cancel / close
        $(document).on('click', '#amod-cancel-btn, #amadex-modify-close, #amadex-modify-backdrop2', function() {
            amadexCloseModifyPopup();
        });

        // ── Auto-trigger the active tab on build to show correct form state ──
        const $activeTab = $('.amod-tab-active');
        if ($activeTab.length) {
            const initialTrip = $activeTab.data('trip');
            if (initialTrip === 'multi-city') {
                $('.amod-dates-row, .amod-route-row').hide();
                $('#amod-return-field').hide();
                if (!$('#amod-segments-block').length) {
                    const seg1Origin = $('#amod-origin-code').val() || '';
                    const seg1OriginName = $('#amod-origin-input').val() || '';
                    const seg1Dest = $('#amod-dest-code').val() || '';
                    const seg1DestName = $('#amod-dest-input').val() || '';
                    const seg1Date = $('#amod-depart-date').val() || '';
                    $('#amadex-modify-form-body').prepend(`
                        <div id="amod-segments-block">
                            <div class="amod-seg-list" id="amod-seg-list"></div>
                            <button type="button" id="amod-add-seg-btn">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                Add City
                            </button>
                        </div>`);
                    let restored = false;
                    try {
                        const storedSegs = JSON.parse(sessionStorage.getItem('amadex_multi_city_segments') || '[]');
                        if (storedSegs.length >= 2) {
                            storedSegs.forEach(function(s) {
                                amodAddSegment(s.origin_name || s.origin || '', s.origin || '', s.destination_name || s.destination || '', s.destination || '', s.departure_date || s.departure || '');
                            });
                            restored = true;
                        }
                    } catch(e) {}
                    if (!restored) {
                        amodAddSegment(seg1OriginName || seg1Origin, seg1Origin, seg1DestName || seg1Dest, seg1Dest, seg1Date);
                        amodAddSegment('', '', '', '', '');
                    }
                }
                $('#amod-segments-block').show();
            } else if (initialTrip === 'oneway' || initialTrip === 'one_way') {
                $('#amod-return-field').css({ opacity: 0.4, 'pointer-events': 'none' });
            }
        }
        // ── End auto-trigger ──
        $(document).on('keydown.amodpopup', function(e) {
            if (e.key === 'Escape') amadexCloseModifyPopup();
        });

        // Search submit
        $(document).on('click', '#amod-search-btn', function() {
            const origin    = $('#amod-origin-code').val().trim();
            const dest      = $('#amod-dest-code').val().trim();
            const depart    = $('#amod-depart-date').val();
            const ret       = $('#amod-return-date').val();
            const originName = $('#amod-origin-input').val();
            const destName   = $('#amod-dest-input').val();
            const adults    = parseInt($('#amod-adults-count').text()) || 1;
            const children  = parseInt($('#amod-children-count').text()) || 0;
            const infants   = parseInt($('#amod-infants-count').text()) || 0;
            const cabin     = $('#amod-cabin').val();
            const activeTrip = $('.amod-tab-active').data('trip') || 'round';
            const isOneWay   = activeTrip === 'oneway';
            const isMultiCity = activeTrip === 'multi-city';

            // ── Multi-city ──────────────────────────────────────────────────
            if (isMultiCity) {
                const segments = [];
                $('#amod-seg-list .amod-seg').each(function() {
                    const orig     = $(this).find('.amod-seg-origin-code').val().trim();
                    const dst      = $(this).find('.amod-seg-dest-code').val().trim();
                    const dep      = $(this).find('.amod-seg-date').val();
                    const oName    = $(this).find('.amod-seg-origin-input').val();
                    const dName    = $(this).find('.amod-seg-dest-input').val();
                    if (orig && dst && dep) {
                        segments.push({ origin: orig, origin_name: oName, destination: dst, destination_name: dName, departure_date: dep, departure: dep });
                    }
                });
                if (segments.length < 2) { alert('Please fill in at least 2 city segments.'); return; }

                const firstSeg = segments[0];
                const lastSeg  = segments[segments.length - 1];
                const cab      = $('#amod-cabin').val();
                const paxA     = parseInt($('#amod-adults-count').text()) || 1;
                const paxC     = parseInt($('#amod-children-count').text()) || 0;
                const paxI     = parseInt($('#amod-infants-count').text()) || 0;

amadexCloseModifyPopup();
                $('#amadex-flight-cards-container').empty();
                amadexShowSearchLoader(paxA);

                const ajaxUrl = typeof AmadexConfig !== 'undefined' ? AmadexConfig.ajaxUrl : '/wp-admin/admin-ajax.php';
                const nonce   = typeof AmadexConfig !== 'undefined' ? AmadexConfig.nonce : '';
                const currency = (typeof AmadexConfig !== 'undefined' && AmadexConfig.currency) ? AmadexConfig.currency.default || 'USD' : 'USD';

                $.ajax({
                    url: ajaxUrl, type: 'POST',
                    data: {
                        action: 'amadex_search_flights',
                        origin: firstSeg.origin,
                        destination: lastSeg.destination,
                        departure_date: firstSeg.departure_date,
                        return_date: '',
                        adults: paxA, children: paxC, infants: paxI,
                        travel_class: cab,
                        currency: currency,
                        trip_type: 'multi-city',
                        segments: JSON.stringify(segments),
                        nonce: nonce
                    },
success: function(response) {
                        amadexHideSearchLoader();
                        if (response.success && response.data) {
                            const searchData = {
                                origin: firstSeg.origin, origin_name: firstSeg.origin_name,
                                destination: lastSeg.destination, destination_name: lastSeg.destination_name,
                                departure: firstSeg.departure_date, return: '',
                                adults: paxA, children: paxC, infants: paxI,
                                cabin: cab, currency: currency,
                                trip_type: 'multi-city', segments: segments
                            };
                            sessionStorage.setItem('amadex_search_data', JSON.stringify(searchData));
                            sessionStorage.setItem('amadex_search_results', JSON.stringify(response.data));
                            sessionStorage.setItem('amadex_multi_city_segments', JSON.stringify(segments));

                            // Update URL without reload
                            const params = new URLSearchParams({
                                origin_name: firstSeg.origin_name, origin_iata: firstSeg.origin,
                                destination_name: lastSeg.destination_name, destination_iata: lastSeg.destination,
                                depart_date: firstSeg.departure_date, return_date: '',
                                one_way: '0', adults: paxA, children: paxC, infants: paxI,
                                cabin: cab, trip_type: 'multi-city',
                                currency: currency, language: 'en', lang: 'en',
                                segments: JSON.stringify(segments)
                            });
                            window.history.pushState({}, '', window.location.pathname + '?' + params.toString());

                            // Re-populate banner
                            if (typeof populateSearchSummaryBanner === 'function') populateSearchSummaryBanner();

displayFlightResults(response.data);
                            amadexUpdateGreenBar({
                                origin: firstSeg.origin, originName: firstSeg.origin_name,
                                dest: lastSeg.destination, destName: lastSeg.destination_name,
                                depart: firstSeg.departure_date, ret: '',
                                adults: paxA, children: paxC, infants: paxI,
                                cabin: cab, tripType: 'multi-city', segments: segments
                            });
                        } else {
                            $('#amadex-no-results').show();
                            alert((response.data && response.data.message) || 'No flights found. Please try different options.');
                        }
                    },
error: function() {
                        amadexHideSearchLoader();
                        $('#amadex-no-results').show();
                        alert('Search failed. Please try again.');
                    }
                });
                return;
            }

            // ── Round trip / One Way validation ────────────────────────────
            if (!origin) { $('#amod-origin-input').focus().closest('.amod-field').addClass('amod-field-error'); return; }
            if (!dest)   { $('#amod-dest-input').focus().closest('.amod-field').addClass('amod-field-error'); return; }
            if (!depart) { alert('Please select a departure date.'); return; }
            $('.amod-field').removeClass('amod-field-error');

            const currency = (typeof AmadexConfig !== 'undefined' && AmadexConfig.currency) ? AmadexConfig.currency.default || 'USD' : 'USD';
            const ajaxUrl  = typeof AmadexConfig !== 'undefined' ? AmadexConfig.ajaxUrl : '/wp-admin/admin-ajax.php';
            const nonce    = typeof AmadexConfig !== 'undefined' ? AmadexConfig.nonce : '';

amadexCloseModifyPopup();
            $('#amadex-flight-cards-container').empty();
            $('#amadex-no-results').hide();
            amadexShowSearchLoader(adults);

            $.ajax({
                url: ajaxUrl, type: 'POST',
                data: {
                    action: 'amadex_search_flights',
                    origin: origin,
                    destination: dest,
                    departure_date: depart,
                    return_date: isOneWay ? '' : (ret || ''),
                    adults: adults, children: children, infants: infants,
                    travel_class: cabin,
                    show_all_cabins: (cabin === 'ECONOMY' || !cabin) ? 'yes' : 'no',
                    currency: currency,
                    trip_type: isOneWay ? 'oneway' : 'roundtrip',
                    nonce: nonce
                },
                success: function(response) {
amadexHideSearchLoader();
                    if (response.success && response.data) {
                        const searchData = {
                            origin: origin, origin_name: originName,
                            destination: dest, destination_name: destName,
                            departure: depart, return: isOneWay ? '' : (ret || ''),
                            adults: adults, children: children, infants: infants,
                            cabin: cabin, currency: currency,
                            trip_type: isOneWay ? 'oneway' : 'roundtrip',
                            one_way: isOneWay
                        };
                        sessionStorage.setItem('amadex_search_data', JSON.stringify(searchData));
                        sessionStorage.setItem('amadex_search_results', JSON.stringify(response.data));
                        sessionStorage.removeItem('amadex_multi_city_segments');

                        // Update URL without reload
                        const params = new URLSearchParams({
                            origin_name: originName, origin_iata: origin,
                            destination_name: destName, destination_iata: dest,
                            depart_date: depart,
                            return_date: isOneWay ? '' : (ret || ''),
                            one_way: isOneWay ? '1' : '0',
                            adults: adults, children: children, infants: infants,
                            cabin: cabin,
                            trip_type: isOneWay ? 'oneway' : 'roundtrip',
                            currency: currency, language: 'en', lang: 'en'
                        });
                        window.history.pushState({}, '', window.location.pathname + '?' + params.toString());

displayFlightResults(response.data);
                        amadexUpdateGreenBar({
                            origin: origin, originName: originName,
                            dest: dest, destName: destName,
                            depart: depart, ret: isOneWay ? '' : (ret || ''),
                            adults: adults, children: children, infants: infants,
                            cabin: cabin, tripType: isOneWay ? 'oneway' : 'roundtrip'
                        });
                    } else {
                        $('#amadex-no-results').show();
                        alert((response.data && response.data.message) || 'No flights found. Please try different options.');
                    }
                },
error: function() {
                    amadexHideSearchLoader();
                    $('#amadex-no-results').show();
                    alert('Search failed. Please try again.');
                }
            });
        });
    }

    // ── Full-page overlay loader ──────────────────────────────────────────
    function amadexShowSearchLoader(adults) {
        if ($('#amadex-fullpage-loader').length) return;
        const pax = parseInt(adults) || 1;
        $('body').append(`
            <div id="amadex-fullpage-loader">
                <div id="amadex-fullpage-loader-inner">
                    <div class="afpl-ring">
                        <svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="30" cy="30" r="26" stroke="rgba(255,255,255,0.12)" stroke-width="4"/>
                            <circle cx="30" cy="30" r="26" stroke="#0e7d3f" stroke-width="4"
                                stroke-linecap="round" stroke-dasharray="40 124"
                                class="afpl-arc"/>
                        </svg>
                    </div>
                    <div class="afpl-text">
                        <div class="afpl-title">Checking availability...</div>
                        <div class="afpl-sub">Finding the best price for ${pax} adult${pax > 1 ? 's' : ''}</div>
                    </div>
                </div>
            </div>
        `);
        setTimeout(function() { $('#amadex-fullpage-loader').addClass('afpl-visible'); }, 10);
    }
    function amadexHideSearchLoader() {
        const $loader = $('#amadex-fullpage-loader');
        $loader.removeClass('afpl-visible');
        setTimeout(function() { $loader.remove(); }, 350);
    }
    // ── End full-page overlay loader ──────────────────────────────────────
// ── Update green search summary bar after modify search ───────────────
    function amadexUpdateGreenBar(opts) {
        try {
            const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            const days   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

            function fmtDisplay(iso) {
                if (!iso) return '';
                const d = new Date(iso + 'T00:00:00');
                if (isNaN(d)) return iso;
                return `${d.getDate()} ${months[d.getMonth()]}, ${String(d.getFullYear()).slice(2)} ${['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][d.getDay()]}`;
            }
            function fmtShort(iso) {
                if (!iso) return '';
                const d = new Date(iso + 'T00:00:00');
                if (isNaN(d)) return iso;
                return `${d.getDate()} ${months[d.getMonth()]}`;
            }
            function paxText(a, c, i) {
                const parts = [];
                if (a) parts.push(a + ' Adult' + (a > 1 ? 's' : ''));
                if (c) parts.push(c + ' Child' + (c > 1 ? 'ren' : ''));
                if (i) parts.push(i + ' Infant' + (i > 1 ? 's' : ''));
                return parts.join(', ') || '1 Adult';
            }
            function cabinLabel(c) {
                return {ECONOMY:'Economy',PREMIUM_ECONOMY:'Premium Economy',BUSINESS:'Business',FIRST:'First Class'}[(c||'').toUpperCase()] || c;
            }

            // ── Update the inline modern search form (green bar) ──────────
            // Origin
            const originDisplay = (opts.originName || opts.origin || '').replace(/\s*\(.*?\)\s*/g, '').trim() + (opts.origin ? ` (${opts.origin})` : '');
            $('#modern-origin').val(originDisplay);
            $('#modern-origin-code').val(opts.origin || '');
            $('#modern-origin-description').text(opts.originName || opts.origin || '');

            // Destination
            const destDisplay = (opts.destName || opts.dest || '').replace(/\s*\(.*?\)\s*/g, '').trim() + (opts.dest ? ` (${opts.dest})` : '');
            $('#modern-destination').val(destDisplay);
            $('#modern-destination-code').val(opts.dest || '');
            $('#modern-destination-description').text(opts.destName || opts.dest || '');

            // Departure date display
            if (opts.depart) {
                $('#departure-display').text(fmtDisplay(opts.depart));
                $('#departure-day').text(['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][new Date(opts.depart + 'T00:00:00').getDay()] || '');
                $('#modern-departure').val(opts.depart);
            }

            // Return date display
            if (opts.ret) {
                $('#return-display').text(fmtDisplay(opts.ret));
                $('#return-day').text(['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][new Date(opts.ret + 'T00:00:00').getDay()] || '');
                $('#modern-return').val(opts.ret);
            } else {
                $('#return-display').text('Return Date');
                $('#modern-return').val('');
            }

            // Passengers
            $('#modern-adults').val(opts.adults || 1);
            $('#adults-count').text(opts.adults || 1);
            $('#modern-children').val(opts.children || 0);
            $('#children-count').text(opts.children || 0);
            $('#modern-infants').val(opts.infants || 0);
            $('#infants-count').text(opts.infants || 0);
            $('#amadex-pax-summary').text(paxText(opts.adults, opts.children, opts.infants));

            // Cabin
            const cabinVal = (opts.cabin || 'ECONOMY').toUpperCase();
            $('#modern-cabin').val(cabinVal);
            $('#amadex-cabin-summary').text(cabinLabel(cabinVal));

            // Trip type radio
            const tripMap = { 'oneway': 'oneway', 'one_way': 'oneway', 'roundtrip': 'round', 'round': 'round', 'multi-city': 'multi-city', 'multicity': 'multi-city' };
            const normalTrip = tripMap[opts.tripType] || 'round';
            $('input[name="tripType"]').filter(`[value="${normalTrip}"]`).prop('checked', true).trigger('change');

            // ── Update the sticky route header (LAX → YTO bar) ───────────
            // Route text (e.g. "LAX → YTO")
            const routeText = opts.tripType === 'multi-city' && opts.segments && opts.segments.length
                ? opts.segments.map(s => s.origin).join(' → ') + ' → ' + opts.segments[opts.segments.length - 1].destination
                : `${opts.origin} → ${opts.dest}`;

            // Dates text (e.g. "May 12 – May 15")
            const datesText = opts.depart
                ? (opts.ret ? `${fmtShort(opts.depart)} – ${fmtShort(opts.ret)}` : fmtShort(opts.depart))
                : '';

            const pax = paxText(opts.adults, opts.children, opts.infants);
            const cabin = cabinLabel(cabinVal);

            // Update common sticky bar selectors (try all known patterns)
            $('.amadex-route-info, .amadex-sticky-route, [data-route-display]').text(routeText);
            $('.amadex-route-dates, .amadex-sticky-dates, [data-dates-display]').text(datesText);
            $('.amadex-route-pax, .amadex-sticky-pax, [data-pax-display]').text(`${pax} · ${cabin}`);

            // Update the specific green banner elements from the session-based banner
            $('#amadex-ssb-origin-iata').text(opts.origin || '');
            $('#amadex-ssb-dest-iata').text(opts.dest || '');
            $('#amadex-ssb-dates-text').text(datesText);
            $('#amadex-ssb-travellers-text').text(pax);
            $('#amadex-ssb-cabin-text').text(cabin);
            $('#amadex-ssb-fullroute').text(
                opts.tripType === 'multi-city' && opts.segments
                    ? opts.segments.map(s => s.origin_name || s.origin).join(' → ') + ' → ' + ((opts.segments[opts.segments.length-1].destination_name) || opts.segments[opts.segments.length-1].destination)
                    : [opts.originName || opts.origin, opts.destName || opts.dest].filter(Boolean).join(' → ')
            );

            // Update mobile route details
            $('#amadex-mobile-route-details').text(`${routeText} · ${datesText} · ${pax}`);

            // Trigger any custom update event
            $(document).trigger('amadex:search-bar-updated', [opts]);

        } catch(e) {
            console.warn('amadexUpdateGreenBar error:', e);
        }
    }
    // ── End green bar update ──────────────────────────────────────────────
function amadexOpenModifyPopup() {
        $('#amadex-modify-popup').remove();
        amadexBuildModifyPopup();
        $('#amadex-modify-popup').addClass('amod-visible');
        $('body').addClass('amadex-popup-open');
        $('#amadex-ssb-edit-btn').addClass('active');
    }

    function amadexCloseModifyPopup() {
        $('#amadex-modify-popup').removeClass('amod-visible');
        $('body').removeClass('amadex-popup-open');
        $('#amadex-ssb-edit-btn').removeClass('active');
        $(document).off('keydown.amodpopup');
    }

    $(document).on('click', '#amadex-ssb-edit-btn', function(e) {
        e.preventDefault(); e.stopPropagation();
        $('#amadex-modify-popup').hasClass('amod-visible') ? amadexCloseModifyPopup() : amadexOpenModifyPopup();
    });
    $(document).on('click', '#amadex-modify-search-btn', function(e) {
        e.preventDefault(); e.stopPropagation();
        amadexOpenModifyPopup();
    });
    // ── End Modify Search Popup ───────────────────────────────────────────────

        // Restore filters from URL on page load
        restoreFiltersFromURL();

        // Sort controls
        $('#amadex-sort-by').on('change', function() {
            sortFlights();
        });
        // Initialize tabs active state
        const initialSort = $('#amadex-sort-by').val();
        $('.sort-tab').removeClass('is-active').filter(`[data-sort="${initialSort}"]`).addClass('is-active');
        $(document).on('click', '.sort-tab', function() {
            const sortValue = $(this).data('sort');
            $('#amadex-sort-by').val(sortValue).trigger('change');
            $('.sort-tab').removeClass('is-active');
            $(this).addClass('is-active');
        });

        // Price toggle
        $('#amadex-price-per-person').on('change', function() {
            updatePriceDisplay();
        });

        // Search again button
        $('.amadex-search-again-btn').on('click', function() {
              // Clear booking timer session when starting a new search
            if (typeof window.clearBookingTimerSession === 'function') {
                window.clearBookingTimerSession();
            } else {
                // Fallback: clear timer sessionStorage directly if function not available
                if (window.amadexBookingTimerInterval) {
                    clearInterval(window.amadexBookingTimerInterval);
                    window.amadexBookingTimerInterval = null;
                }
                sessionStorage.removeItem('amadex_booking_timer_start');
                sessionStorage.removeItem('amadex_booking_timer_remaining');
            }
            window.location.href = '/';
        });

        // Load initial results if available
        loadStoredResults();
        
        // Initialize layover tooltip handlers (using event delegation for dynamically created elements)
        initLayoverTooltips();
        
        // Check for multi-city segments and initialize tabs after results load
        // Only initialize tabs if it's actually a multi-city trip
        // Try multiple times to catch results when they load
        setTimeout(function() {
            // Only check for multi-city tabs if trip_type is multi-city
            const urlParams = new URLSearchParams(window.location.search);
            const tripType = urlParams.get('trip_type');
            const isMultiCity = tripType === 'multi-city' || tripType === 'multicity';
            
            if (isMultiCity) {
                checkAndInitMultiCityTabs();
            } else {
                // Not multi-city - ensure tabs are removed
                $('.amadex-segment-tabs-container').remove();
                $('.amadex-segment-tabs-wrapper').remove();
                $('#amadex-segment-tabs').remove();
            }
        }, 300);
        
        setTimeout(function() {
            // Only check for multi-city tabs if trip_type is multi-city
            const urlParams = new URLSearchParams(window.location.search);
            const tripType = urlParams.get('trip_type');
            const isMultiCity = tripType === 'multi-city' || tripType === 'multicity';
            
            if (isMultiCity) {
                checkAndInitMultiCityTabs();
            } else {
                // Not multi-city - ensure tabs are removed
                $('.amadex-segment-tabs-container').remove();
                $('.amadex-segment-tabs-wrapper').remove();
                $('#amadex-segment-tabs').remove();
            }
        }, 1000);
        
        // Handle page navigation (back/forward button, etc.)
        // Restore results when user returns to this page from booking page
        window.addEventListener('pageshow', function(event) {

            
            // If page was loaded from cache (back/forward button), restore results
            if (event.persisted || (performance && performance.navigation && performance.navigation.type === 2)) {
                console.log('Page loaded from cache, restoring results...');
                
                // Small delay to ensure DOM is ready
                setTimeout(function() {
                    // Check if results container is empty
                    const container = $('#amadex-flight-cards-container');
                    const hasResults = container.children().length > 0;
                    const hasTabs = $('.amadex-segment-tabs-container').length > 0;
                    
                    // if (!hasResults && !hasTabs) {
                    //     console.log('No results visible, loading from storage...');
                    //     loadStoredResults();

                    const filtersActive = $('#amadex-no-results').is(':visible');
                    if (!hasResults && !hasTabs && !filtersActive) {
                        console.log('No results visible, loading from storage...');
                        loadStoredResults();
                    } else {
                        console.log('Results already visible, no need to reload');
                        // Even if results are visible, ensure multi-city tabs are initialized
                        const urlParams = new URLSearchParams(window.location.search);
                        const tripType = urlParams.get('trip_type');
                        if (tripType === 'multi-city' || tripType === 'multicity') {
                            checkAndInitMultiCityTabs();
                        }
                    }
                }, 100);
            }
        });
        
        // Also listen for visibility change (when tab becomes visible again)
        // document.addEventListener('visibilitychange', function() {
        //     if (!document.hidden && isResultsPage()) {
        //         // Page became visible, check if we need to restore results
        //         const container = $('#amadex-flight-cards-container');
        //         const hasResults = container.children().length > 0;
        //         const hasTabs = $('.amadex-segment-tabs-container').length > 0;
                
        //         if (!hasResults && !hasTabs) {
        //             console.log('Page became visible with no results, loading from storage...');
        //             setTimeout(function() {
        //                 loadStoredResults();
        //             }, 100);
        //         }
        //     }
        // });

// Also listen for visibility change (when tab becomes visible again)
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden && isResultsPage()) {
                // Page became visible, check if we need to restore results
                const container = $('#amadex-flight-cards-container');
                const hasResults = container.children().length > 0;
                const hasTabs = $('.amadex-segment-tabs-container').length > 0;
                // If no-results message is visible, filters are active — don't reload
                const filtersActive = $('#amadex-no-results').is(':visible');

                if (!hasResults && !hasTabs && !filtersActive) {
                    console.log('Page became visible with no results, loading from storage...');
                    setTimeout(function() {
                        loadStoredResults();
                    }, 100);
                }
            }
        });
        
        // Also check when results are loaded
        $(document).on('DOMNodeInserted', function() {
            setTimeout(checkAndInitMultiCityTabs, 100);
        });
        initBookingProgress();
        try {
            sessionStorage.setItem('amadexBookingStage', 'booking');
        } catch (e) {
            console.warn('Unable to store booking stage', e);
        }
    }

    /**
     * Initialize modal functionality
     */
    /**
     * Initialize layover tooltip handlers
     */
    function initLayoverTooltips() {
        // Handle click/touch on layover trigger
        $(document).on('click', '.amadex-layover-trigger', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $trigger = $(this);
            const $tooltip = $trigger.find('.amadex-layover-tooltip');
            
            // Close all other tooltips
            $('.amadex-layover-tooltip').not($tooltip).css({
                'opacity': '0',
                'pointer-events': 'none',
                'transform': 'translate(-50%, -50%) scale(0.95)'
            });
            $('.amadex-layover-trigger').not($trigger).removeClass('active');
            
            // Toggle current tooltip
            if ($trigger.hasClass('active')) {
                // Close
                $tooltip.css({
                    'opacity': '0',
                    'pointer-events': 'none',
                    'transform': 'translate(-50%, -50%) scale(0.95)'
                });
                $trigger.removeClass('active');
            } else {
                // Open
                $tooltip.css({
                    'opacity': '1',
                    'pointer-events': 'auto',
                    'transform': 'translate(-50%, -50%) scale(1)'
                });
                $trigger.addClass('active');
            }
        });
        
        // Close tooltip when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.amadex-layover-trigger').length && 
                !$(e.target).closest('.amadex-layover-tooltip').length) {
                $('.amadex-layover-tooltip').css({
                    'opacity': '0',
                    'pointer-events': 'none',
                    'transform': 'translate(-50%, -50%) scale(0.95)'
                });
                $('.amadex-layover-trigger').removeClass('active');
            }
        });
        
        // Close tooltip on ESC key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                $('.amadex-layover-tooltip').css({
                    'opacity': '0',
                    'pointer-events': 'none',
                    'transform': 'translate(-50%, -50%) scale(0.95)'
                });
                $('.amadex-layover-trigger').removeClass('active');
            }
        });
    }

    function initModals() {
        // Modal close buttons - use event delegation for dynamically created modals
        $(document).on('click', '.amadex-modal-close, .amadex-flight-details-close', function() {
            const modal = $(this).closest('.amadex-modal');
            modal.removeClass('show').fadeOut(300, function() {
                $('body').css('overflow', '');
            });
        });

        // Close modal when clicking outside - use event delegation
        $(document).on('click', '.amadex-modal', function(e) {
            if (e.target === this) {
                const modal = $(this);
                modal.removeClass('show').fadeOut(300, function() {
                    $('body').css('overflow', '');
                });
            }
        });
        
        // Close modal on ESC key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                const openModal = $('.amadex-modal.show');
                if (openModal.length) {
                    openModal.removeClass('show').fadeOut(300, function() {
                        $('body').css('overflow', '');
                    });
                }
            }
        });
        
        // ESC key to close any modal
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('.amadex-modal:visible').hide();
            }
        });

        // Flight details button
        $(document).on('click', '.amadex-flight-details-btn', function() {
            const flightId = $(this).data('flight-id');
            showFlightDetails(flightId);
        });
        
        // Share flight button
        $(document).on('click', '.amadex-flight-share', function(e) {
            e.preventDefault();
            const flightCard = $(this).closest('.amadex-flight-card');
            const flightData = flightCard.data('flight-index');
            showShareModal(flightData);
        });

        // Select button - show flight details modal
        $(document).on('click', '.amadex-select-flight-btn', function() {
            const flightData = $(this).data('flight-data');
            showFlightDetailsModal(flightData);
        });
        
        // Book now button - redirect to booking page
        $(document).on('click', '.amadex-book-now-btn', function(e) {
        e.preventDefault();
            const flightData = $(this).data('flight-data');
            
            if (!flightData) {
                alert('Flight data not available. Please try again.');
                return;
            }
            
            // Check if this is a multi-city trip
            const segmentsStr = sessionStorage.getItem('amadex_multi_city_segments');
            const searchDataStr = sessionStorage.getItem('amadex_search_data');
            let isMultiCity = false;
            let segments = [];
            let currentSegmentIndex = null;
            
            // Detect multi-city trip
            if (segmentsStr) {
                try {
                    segments = JSON.parse(segmentsStr);
                    isMultiCity = segments && segments.length > 1;
                    if (isMultiCity) {
                        // Get the current segment index from the flight card
                        const $flightCard = $(this).closest('.amadex-flight-card');
                        if ($flightCard.length) {
                            const segmentIndex = $flightCard.data('segment-index');
                            if (segmentIndex !== undefined && segmentIndex !== null) {
                                currentSegmentIndex = parseInt(segmentIndex);
                            }
                        }
                        console.log('Multi-city trip detected. Segment index:', currentSegmentIndex);
                    }
                } catch(e) {
                    console.error('Error parsing segments:', e);
                }
            }
            
            // For multi-city trips, store all selected flights per segment
            if (isMultiCity && segments.length > 1) {
                // Get or create multi-city bookings object
                let multiCityBookings = {};
                const storedBookings = sessionStorage.getItem('amadex_multi_city_bookings');
                if (storedBookings) {
                    try {
                        multiCityBookings = JSON.parse(storedBookings);
                    } catch(e) {
                        console.error('Error parsing multi-city bookings:', e);
                    }
                }
                
                // If segment index is available, store flight for that segment
                if (currentSegmentIndex !== null && currentSegmentIndex >= 0 && currentSegmentIndex < segments.length) {
                    multiCityBookings[currentSegmentIndex] = flightData;
                    sessionStorage.setItem('amadex_multi_city_bookings', JSON.stringify(multiCityBookings));
                    console.log('Stored flight for segment', currentSegmentIndex, ':', flightData);
                    
                    // Mark this segment tab as selected (add visual indicator)
                    const $currentTab = $(`.amadex-segment-tab[data-segment="${currentSegmentIndex}"]`);
                    if ($currentTab.length) {
                        $currentTab.addClass('segment-selected');
                        // Add checkmark or indicator
                        if (!$currentTab.find('.segment-selected-indicator').length) {
                            $currentTab.find('.amadex-segment-tab-content').append('<span class="segment-selected-indicator">✓</span>');
                        }
                    }
                    
                    // Mark the clicked flight card as selected
                    const $flightCard = $(this).closest('.amadex-flight-card');
                    if ($flightCard.length) {
                        $flightCard.addClass('flight-selected');
                    }
                    
                    // Check if all segments have flights selected
                    const allSegmentsBooked = segments.every((seg, idx) => multiCityBookings[idx] !== undefined);
                    
                    if (allSegmentsBooked) {
                        // All segments booked, proceed to booking page
                        // Store combined flight data for booking page
                        const allFlights = segments.map((seg, idx) => multiCityBookings[idx]).filter(f => f !== undefined);
                        // Add user's selected currency from regional settings to all flights
                        // Check if regional settings are enabled first
                        const regionalSettingsEnabled = typeof AmadexConfig !== 'undefined' && 
                                                       AmadexConfig.currency && 
                                                       AmadexConfig.currency.regionalSettingsEnabled !== false;
                        
                        let selectedCurrency = 'USD'; // Default to USD
                        if (regionalSettingsEnabled) {
                            // Regional settings enabled - get currency from storage
                            selectedCurrency = sessionStorage.getItem('amadex_selected_currency') || 
                                             (localStorage.getItem('amadex_regional_settings') ? 
                                                 (JSON.parse(localStorage.getItem('amadex_regional_settings') || '{}')).currency : null) || 
                                             'USD';
                        }
                        
                        // Always store currency in flight data (USD when disabled)
                        allFlights.forEach(flight => {
                            flight.selected_currency = selectedCurrency;
                            if (!flight.price) {
                                flight.price = {};
                            }
                            flight.price.selected_currency = selectedCurrency;
                        });
                        sessionStorage.setItem('amadex_booking_flight', JSON.stringify(allFlights[0])); // Store first flight as primary
                        sessionStorage.setItem('amadex_booking_all_segments', JSON.stringify(allFlights)); // Store all flights
                        console.log('All segments booked. Proceeding to booking page with', allFlights.length, 'flights, currency:', selectedCurrency);
                        
                        // All segments booked, show success message and redirect to booking page
                        showMultiCityFeedback('All flights selected! Redirecting to booking page...');
                        
                        // Small delay to show feedback, then redirect
                        const $btn = $(this);
                        // Store current results page URL for back button navigation
                        const currentResultsUrl = window.location.href;
                        try {
                            sessionStorage.setItem('amadex_results_page_url', currentResultsUrl);
                            console.log('Stored results page URL for back navigation:', currentResultsUrl);
                        } catch (err) {
                            console.warn('Unable to store results page URL', err);
                        }
                        
                        setTimeout(function() {
                            const href = $btn.attr('href') || 
                                        (typeof AmadexConfig !== 'undefined' && AmadexConfig.bookingPageUrl ? AmadexConfig.bookingPageUrl : '/flight-booking/');
                            const newStage = 'passengers';
                            try {
                                sessionStorage.setItem('amadexBookingStage', newStage);
                            } catch (err) {
                                console.warn('Unable to store booking stage', err);
                            }
                            $(document).trigger('amadexBookingStageChange', [newStage]);
                            window.location.href = href;
                        }, 800);
                        return; // Don't continue with normal redirect
                    } else {
                        // Not the last segment, switch to next segment tab
                        const nextSegmentIndex = currentSegmentIndex + 1;
                        
                        if (nextSegmentIndex < segments.length) {
                            // Find next segment tab and click it
                            const $nextTab = $(`.amadex-segment-tab[data-segment="${nextSegmentIndex}"]`);
                            if ($nextTab.length) {
                                console.log('Switching to next segment:', nextSegmentIndex);
                                
                                // Show feedback message
                                showMultiCityFeedback(`Flight ${currentSegmentIndex + 1} selected! Now select flight for segment ${nextSegmentIndex + 1}.`);
                                
                                // Smooth scroll to tabs area
                                $('html, body').animate({
                                    scrollTop: $('.amadex-segment-tabs-container').offset().top - 100
                                }, 300);
                                
                                // Switch to next tab after short delay
                                setTimeout(function() {
                                    $nextTab.trigger('click');
                                }, 400);
                                
                                return; // Don't redirect yet
                            } else {
                                // Tab not found, try manual switching using window function
                                console.warn('Next tab not found, trying to switch manually');
                                const searchResults = JSON.parse(sessionStorage.getItem('amadex_search_results') || '{}');
                                
                                // Update active tab first
                                $('.amadex-segment-tab').removeClass('is-active');
                                const $nextTab = $(`.amadex-segment-tab[data-segment="${nextSegmentIndex}"]`);
                                if ($nextTab.length) {
                                    $nextTab.addClass('is-active');
                                }
                                
                                // Try to use window.displaySegmentFlights if available
                                if (typeof window.displaySegmentFlights === 'function') {
                                    const $container = $('#amadex-flight-cards-container');
                                    $container.fadeOut(200, function() {
                                        window.displaySegmentFlights(nextSegmentIndex, segments, searchResults);
                                        $container.fadeIn(300);
                                    });
                                } else {
                                    // Fallback: manually trigger tab click
                                    console.warn('window.displaySegmentFlights not available, trying to trigger tab click');
                                    if ($nextTab.length) {
                                        setTimeout(function() {
                                            $nextTab.trigger('click');
                                        }, 100);
                                    }
                                }
                                
                                showMultiCityFeedback(`Flight ${currentSegmentIndex + 1} selected! Now select flight for segment ${nextSegmentIndex + 1}.`);
                                return;
                            }
                        } else {
                            // This shouldn't happen if logic is correct, but handle it
                            alert('All segments selected. Redirecting to booking...');
                        }
                    }
                } else {
                    // Segment index not available, store as single flight (fallback)
                    // Add user's selected currency from regional settings
                    // Check if regional settings are enabled first
                    const regionalSettingsEnabled = typeof AmadexConfig !== 'undefined' && 
                                                   AmadexConfig.currency && 
                                                   AmadexConfig.currency.regionalSettingsEnabled !== false;
                    
                    let selectedCurrency = 'USD'; // Default to USD
                    if (regionalSettingsEnabled) {
                        // Regional settings enabled - get currency from storage
                        selectedCurrency = sessionStorage.getItem('amadex_selected_currency') || 
                                         (localStorage.getItem('amadex_regional_settings') ? 
                                             (JSON.parse(localStorage.getItem('amadex_regional_settings') || '{}')).currency : null) || 
                                         'USD';
                    }
                    
                    // Always store currency in flight data (USD when disabled)
                    flightData.selected_currency = selectedCurrency;
                    if (!flightData.price) {
                        flightData.price = {};
                    }
                    flightData.price.selected_currency = selectedCurrency;
                    sessionStorage.setItem('amadex_booking_flight', JSON.stringify(flightData));
                    console.log('Segment index not found, stored as single flight with currency:', selectedCurrency);
                }
            } else {
                // Single flight or round trip - store normally
                // Add user's selected currency from regional settings
                // Check if regional settings are enabled first
                const regionalSettingsEnabled = typeof AmadexConfig !== 'undefined' && 
                                               AmadexConfig.currency && 
                                               AmadexConfig.currency.regionalSettingsEnabled !== false;
                
                let selectedCurrency = 'USD'; // Default to USD
                if (regionalSettingsEnabled) {
                    // Regional settings enabled - get currency from storage
                    selectedCurrency = sessionStorage.getItem('amadex_selected_currency') || 
                                     (localStorage.getItem('amadex_regional_settings') ? 
                                         (JSON.parse(localStorage.getItem('amadex_regional_settings') || '{}')).currency : null) || 
                                     'USD';
                }
                
                // Always store currency in flight data (USD when disabled)
                flightData.selected_currency = selectedCurrency;
                // Also store in price object for consistency
                if (!flightData.price) {
                    flightData.price = {};
                }
                flightData.price.selected_currency = selectedCurrency;
                sessionStorage.setItem('amadex_booking_flight', JSON.stringify(flightData));
                console.log('Stored single flight data for booking with currency:', selectedCurrency, flightData);
            }
            
            // Store current results page URL for back button navigation
            const currentResultsUrl = window.location.href;
            try {
                sessionStorage.setItem('amadex_results_page_url', currentResultsUrl);
                console.log('Stored results page URL for back navigation:', currentResultsUrl);
            } catch (err) {
                console.warn('Unable to store results page URL', err);
            }
            
            // Get the href from the anchor tag or use config
            const href = $(this).attr('href') || 
                        (typeof AmadexConfig !== 'undefined' && AmadexConfig.bookingPageUrl ? AmadexConfig.bookingPageUrl : '/flight-booking/');
            
            // Redirect to booking page (only for single flights or round trips, or when all multi-city segments are booked)
            const newStage = 'passengers';
            try {
                sessionStorage.setItem('amadexBookingStage', newStage);
            } catch (err) {
                console.warn('Unable to store booking stage', err);
            }
            $(document).trigger('amadexBookingStageChange', [newStage]);
            window.location.href = href;
        });
        
        /**
         * Extract IATA code from string (handles formats like "DELHI (DEL)" or just "DEL")
         */
        function extractIataCode(codeStr) {
            if (!codeStr) return '';
            
            // If it's already a clean 3-letter code, return it
            if (/^[A-Z]{3}$/i.test(codeStr.trim())) {
                return codeStr.trim().toUpperCase();
            }
            
            // Try to extract code from parentheses like "DELHI (DEL)" or "BANGKOK (BKK)"
            const match = codeStr.match(/\(([A-Z]{3})\)/i);
            if (match && match[1]) {
                return match[1].toUpperCase();
            }
            
            // Try to extract 3-letter code from anywhere in the string
            const codeMatch = codeStr.match(/[A-Z]{3}/i);
            if (codeMatch) {
                return codeMatch[0].toUpperCase();
            }
            
            // Return as-is if no code found
            return codeStr.trim().toUpperCase();
        }
        
        /**
         * Extract IATA code from strings like "DELHI (DEL)" or "BOM"
         */
        function extractIataCode(codeStr) {
            if (!codeStr) return '';
            
            const trimmed = String(codeStr).trim();
            if (/^[A-Z]{3}$/i.test(trimmed)) {
                return trimmed.toUpperCase();
            }
            
            const parenMatch = trimmed.match(/\(([A-Z]{3})\)/i);
            if (parenMatch && parenMatch[1]) {
                return parenMatch[1].toUpperCase();
            }
            
            const codeMatch = trimmed.match(/[A-Z]{3}/i);
            if (codeMatch) {
                return codeMatch[0].toUpperCase();
            }
            
            return trimmed.toUpperCase();
        }
        
        /**
         * Show feedback message for multi-city trip segment selection
         */
        function showMultiCityFeedback(message) {
            // Remove existing feedback if any
            $('.amadex-multicity-feedback').remove();
            
            // Create feedback element
            const $feedback = $('<div class="amadex-multicity-feedback">' + message + '</div>');
            
            // Insert after segment tabs
            const $tabsContainer = $('.amadex-segment-tabs-container');
            if ($tabsContainer.length) {
                $tabsContainer.after($feedback);
            } else {
                // Fallback: insert at top of results
                $('#amadex-flight-cards-container').before($feedback);
            }
            
            // Show with animation
            $feedback.fadeIn(300);
            
            // Auto-hide after 4 seconds
            setTimeout(function() {
                $feedback.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 4000);
        }

        // Call now button - direct call only, keep anchor default if tel present
        $(document).on('click', '.amadex-call-btn', function(e) {
            const href = $(this).attr('href') || '';
            const flightData = $(this).data('flight-data') || $(this).closest('.amadex-flight-card').find('.amadex-select-flight-btn').data('flight-data');
            
            if (href.startsWith('tel:')) {
                // Create PHONE_LEAD before opening dialer
                if (flightData) {
                    createPhoneLead(flightData);
                }
                // Let the browser handle the dialer
                return;
            }
            // Fallback if no href present
            e.preventDefault();
            const number = (typeof amadexSettings !== 'undefined' && amadexSettings.call_now_number)
                ? amadexSettings.call_now_number
                : '';
            if (number) {
                // Create PHONE_LEAD before opening dialer
                if (flightData) {
                    createPhoneLead(flightData);
                }
                window.location.href = 'tel:' + number.replace(/[^\d+]/g, '');
            }
        });

        // Booking form submission
        $('#amadex-book-now').on('click', function() {
            processBooking();
        });
    }

    /**
     * Search airports
     */
    function searchAirports(keyword, input) {
        $.ajax({
            url: AmadexConfig.ajaxUrl,
            type: 'GET',
            data: {
                action: 'amadex_airports',
                q: keyword,
                nonce: AmadexConfig.nonce
            },
            success: function(response) {
                if (response.success) {
                    showAirportSuggestions(response.data, input);
                }
            }
        });
    }

    /**
     * Show airport suggestions
     */
    function showAirportSuggestions(airports, input) {
        // Remove existing suggestions
        input.siblings('.amadex-airport-suggestions').remove();
        
        if (airports.length === 0) {
            return;
        }

        const suggestions = $('<div class="amadex-airport-suggestions"></div>');
        
        airports.forEach(function(airport) {
            const suggestion = $('<div class="amadex-airport-suggestion"></div>')
                .text(airport.label)
                .data('code', airport.code)
                .on('click', function() {
                    input.val(airport.label);
                    input.siblings('input[type="hidden"]').val(airport.code);
                    console.log('Airport selected:', airport.code, 'for input:', input.attr('id'));
                    suggestions.remove();
                });
            suggestions.append(suggestion);
        });

        input.after(suggestions);
    }

    /**
     * Check if route is domestic
     */
    function checkIfDomestic(origin, destination) {
        // List of country-specific airport codes (you can expand this)
        const countryAirports = {
            'US': ['JFK', 'LAX', 'ORD', 'ATL', 'DFW', 'DEN', 'SFO', 'SEA', 'LAS', 'MCO', 'EWR', 'CLT', 'PHX', 'IAH', 'MIA', 'BOS', 'MSP', 'FLL', 'DTW', 'PHL', 'LGA', 'BWI', 'SLC', 'SAN', 'IAD', 'DCA', 'MDW', 'TPA', 'PDX', 'STL'],
            'IN': ['DEL', 'BOM', 'BLR', 'MAA', 'HYD', 'CCU', 'AMD', 'GOI', 'COK', 'PNQ', 'JAI', 'LKO', 'TRV', 'IXC', 'GAU', 'IXB', 'IXR', 'IXA', 'IXL', 'IXJ'],
            'GB': ['LHR', 'LGW', 'MAN', 'STN', 'EDI', 'BHX', 'GLA', 'BRS', 'NCL', 'EMA', 'LBA', 'ABZ', 'BFS', 'SOU'],
            'CA': ['YYZ', 'YVR', 'YUL', 'YYC', 'YOW', 'YEG', 'YHZ', 'YWG', 'YQB'],
            'AU': ['SYD', 'MEL', 'BNE', 'PER', 'ADL', 'CNS', 'DRW', 'HBA', 'OOL'],
            'CN': ['PEK', 'PVG', 'CAN', 'CTU', 'SZX', 'CKG', 'XIY', 'KMG', 'HGH', 'NKG'],
            'JP': ['NRT', 'HND', 'KIX', 'FUK', 'CTS', 'OKA', 'NGO'],
            'DE': ['FRA', 'MUC', 'DUS', 'TXL', 'HAM', 'CGN', 'STR'],
            'FR': ['CDG', 'ORY', 'NCE', 'LYS', 'MRS', 'TLS', 'BOD'],
            'ES': ['MAD', 'BCN', 'AGP', 'PMI', 'SVQ', 'VLC', 'BIO'],
            'IT': ['FCO', 'MXP', 'VCE', 'NAP', 'BLQ', 'CTA'],
            'BR': ['GRU', 'GIG', 'BSB', 'CGH', 'SSA', 'FOR', 'REC'],
            'MX': ['MEX', 'CUN', 'GDL', 'MTY', 'TIJ', 'SJD'],
            'AE': ['DXB', 'AUH', 'SHJ'],
            'SG': ['SIN'],
            'TH': ['BKK', 'HKT', 'CNX', 'DMK'],
            'MY': ['KUL', 'PEN', 'JHB']
        };
        
        // Find countries for origin and destination
        let originCountry = null;
        let destCountry = null;
        
        for (const [country, airports] of Object.entries(countryAirports)) {
            if (airports.includes(origin)) originCountry = country;
            if (airports.includes(destination)) destCountry = country;
        }
        
        // If both airports found and same country, it's domestic
        return originCountry && destCountry && originCountry === destCountry;
    }
    
    /**
     * Perform flight search
     */
    function performFlightSearch() {
       // Clear booking timer session when starting a new flight search
       if (typeof window.clearBookingTimerSession === 'function') {
        window.clearBookingTimerSession();
    } else {
        // Fallback: clear timer sessionStorage directly if function not available
        if (window.amadexBookingTimerInterval) {
            clearInterval(window.amadexBookingTimerInterval);
            window.amadexBookingTimerInterval = null;
        }
        sessionStorage.removeItem('amadex_booking_timer_start');
        sessionStorage.removeItem('amadex_booking_timer_remaining');
    }
    

        const form = $('#amadex-form');
        const searchBtn = form.find('.amadex-button');
        const resultsPage = form.data('results');
        
        // Get trip type
        const tripType = $('input[name="tripType"]:checked').val();
        const isOneWay = tripType === 'oneway';
        const isMultiCity = tripType === 'multi-city';
        
        // Get form data
        const searchData = {
            origin: $('#amadex-from-code').val() || $('#amadex-from').val(),
            origin_name: $('#amadex-from').val(),
            destination: $('#amadex-to-code').val() || $('#amadex-to').val(),
            destination_name: $('#amadex-to').val(),
            departure: $('#amadex-departure').val(),
            return: $('#amadex-return').val(),
            adults: $('#amadex-adults').val() || 1,
            children: $('#amadex-children').val() || 0,
            infants: $('#amadex-infants').val() || 0,
            cabin: $('#amadex-cabin').val() || 'ECONOMY',
            currency: $('#amadex-currency').val() || 'USD',
            one_way: isOneWay ? 'Yes' : 'No',
            trip_type: tripType
        };
        
        // Handle multi-city segments - collect all segments
        if (isMultiCity) {
            const segments = [];
            
            // First try to get segments from modern search form
            $('.amadex-flight-segment').each(function(index) {
                const $seg = $(this);
                const segmentNum = $seg.data('segment') || (index + 1);
                
                // Try modern form field IDs first
                let originCode = $('#modern-origin-code-' + segmentNum).val();
                let destCode = $('#modern-destination-code-' + segmentNum).val();
                let depDate = $('#modern-departure-' + segmentNum).val();
                
                // Fallback to basic form fields for first segment
                if (segmentNum === 1 && !originCode) {
                    originCode = $('#amadex-from-code').val() || $('#amadex-from').val();
                }
                if (segmentNum === 1 && !destCode) {
                    destCode = $('#amadex-to-code').val() || $('#amadex-to').val();
                }
                if (segmentNum === 1 && !depDate) {
                    depDate = $('#amadex-departure').val();
                }
                
                if (originCode && destCode && depDate) {
                    segments.push({
                        origin: originCode,
                        destination: destCode,
                        departure_date: depDate,
                        originLocationCode: originCode,
                        destinationLocationCode: destCode
                    });
                }
            });
            
            // If no segments from modern form, use basic form fields as single segment
            if (segments.length === 0 && searchData.origin && searchData.destination && searchData.departure) {
                segments.push({
                    origin: searchData.origin,
                    destination: searchData.destination,
                    departure_date: searchData.departure,
                    originLocationCode: searchData.origin,
                    destinationLocationCode: searchData.destination
                });
            }
            
            // Also check URL parameters for multi-city segments
            const urlParams = new URLSearchParams(window.location.search);
            const segmentsParam = urlParams.get('segments');
            if (segmentsParam && segments.length === 0) {
                try {
                    const urlSegments = JSON.parse(decodeURIComponent(segmentsParam));
                    if (Array.isArray(urlSegments) && urlSegments.length > 0) {
                        segments.push(...urlSegments);
                    }
                } catch(e) {
                    console.error('Error parsing segments from URL:', e);
                }
            }
            
            searchData.segments = segments;
            searchData.trip_type = 'multi-city'; // Ensure trip_type is set correctly
            
            // Store segments for later use
            if (segments.length > 0) {
                sessionStorage.setItem('amadex_multi_city_segments', JSON.stringify(segments));
            }
        }
        
        // Debug: Log the search data
        console.log('Search Data:', searchData);
        console.log('Origin:', searchData.origin, 'Destination:', searchData.destination, 'Departure:', searchData.departure);
        
        // Validate form
        if (!searchData.origin || !searchData.destination || !searchData.departure) {
            let missingFields = [];
            if (!searchData.origin) missingFields.push('From (Origin)');
            if (!searchData.destination) missingFields.push('To (Destination)');
            if (!searchData.departure) missingFields.push('Departure Date');
            
            alert('Please fill in all required fields:\n' + missingFields.join('\n'));
            return;
        }

        // Validate dates
        const today = new Date();
        const departureDate = new Date(searchData.departure);
        if (departureDate < today) {
            alert('Departure date cannot be in the past');
            return;
        }
        
        if (searchData.return) {
            const returnDate = new Date(searchData.return);
            if (returnDate < departureDate) {
                alert('Return date must be after departure date');
                return;
            }
        }
        
        // Check if AmadexConfig is defined
        if (typeof AmadexConfig === 'undefined') {
            console.error('AmadexConfig is not defined. Script localization may have failed.');
            alert('Configuration error. Please refresh the page and try again.');
            return;
        }
        
        console.log('AmadexConfig:', AmadexConfig);
        
        // Show loading state
        searchBtn.prop('disabled', true).text('Searching...');

        // Show skeleton or loading animation if enabled (e.g. when on results page with URL params)
        if (typeof amadexShowStreamingLoaderUI === 'function') {
            amadexShowStreamingLoaderUI();
        }
        
        // Perform search
        $.ajax({
            url: AmadexConfig.ajaxUrl,
            type: 'POST',
            data: {
                action: 'amadex_search_flights',
                origin: searchData.origin,
                destination: searchData.destination,
                departure_date: searchData.departure,
                return_date: searchData.return,
                adults: searchData.adults,
                children: searchData.children,
                infants: searchData.infants,
                travel_class: normalizeCabinClass(searchData.cabin || 'ECONOMY'),
                currency: searchData.currency,
                trip_type: searchData.trip_type || tripType,
                segments: isMultiCity && searchData.segments ? JSON.stringify(searchData.segments) : null,
                load_more: '0',
                progressive_load: (typeof AmadexConfig !== 'undefined' && AmadexConfig.progressiveLoading === true) ? '1' : '0',
                nonce: AmadexConfig.nonce
            },
            success: function(response) {
                console.log('Search Response:', response);
                if (response.success) {
                    const data = response.data;
                    
                    // Progressive Loading: If this is the initial progressive load, trigger background fetch for remaining results
                    const progressiveEnabled = typeof AmadexConfig !== 'undefined' && AmadexConfig.progressiveLoading === true;
                    if (progressiveEnabled && data._progressive && data._progressive_initial) {
                        console.log('Amadex Progressive: Initial 30 results received, fetching remaining results in background...');
                        
                        // Trigger background fetch for remaining results (non-progressive, fetches up to 250)
                        setTimeout(function() {
                            $.ajax({
                                url: AmadexConfig.ajaxUrl,
                                type: 'POST',
                                data: {
                                    action: 'amadex_search_flights',
                                    origin: searchData.origin,
                                    destination: searchData.destination,
                                    departure_date: searchData.departure,
                                    return_date: searchData.return,
                                    adults: searchData.adults,
                                    children: searchData.children,
                                    infants: searchData.infants,
                                    travel_class: normalizeCabinClass(searchData.cabin || 'ECONOMY'),
                                    currency: searchData.currency,
                                    trip_type: searchData.trip_type || tripType,
                                    segments: isMultiCity && searchData.segments ? JSON.stringify(searchData.segments) : null,
                                    load_more: '0',
                                    progressive_fetch: '1', // Mark as background progressive fetch
                                    nonce: AmadexConfig.nonce
                                },
                                success: function(fetchResponse) {
                                    if (fetchResponse.success && fetchResponse.data && fetchResponse.data.flights) {
                                        console.log('Amadex Progressive: Background fetch completed - ' + fetchResponse.data.flights.length + ' total flights');
                                        sessionStorage.setItem('amadex_search_results', JSON.stringify(fetchResponse.data));
                                    }
                                },
                                error: function() {
                                    console.warn('Amadex Progressive: Background fetch failed, but initial results are already displayed');
                                }
                            });
                        }, 100); // Small delay to let initial render complete
                    }
                    
                    // Store search data in sessionStorage for results page
                    sessionStorage.setItem('amadex_search_data', JSON.stringify(searchData));
                    sessionStorage.setItem('amadex_search_results', JSON.stringify(data));
                    
                    // Redirect to results page with URL parameters
                    if (resultsPage) {
                        // Determine if domestic
                        const isDomestic = checkIfDomestic(searchData.origin, searchData.destination);
                        
                        // Build URL parameters - only trip details, no tracking IDs
                        const params = new URLSearchParams({
                            origin_name: searchData.origin_name || searchData.origin,
                            origin_iata: searchData.origin,
                            destination_name: searchData.destination_name || searchData.destination,
                            destination_iata: searchData.destination,
                            depart_date: searchData.departure,
                            return_date: searchData.return || '',
                            one_way: searchData.one_way,
                            adults: searchData.adults,
                            children: searchData.children,
                            infants: searchData.infants,
                            currency: searchData.currency,
                            language: 'en',
                            lang: 'en',
                            isDomestic: isDomestic ? 'Yes' : 'No',
                            cabin: searchData.cabin,
                            trip_type: searchData.trip_type || tripType
                        });
                        
                        // Add multi-city segments to URL if applicable
                        if (isMultiCity && searchData.segments && searchData.segments.length > 0) {
                            params.append('segments', encodeURIComponent(JSON.stringify(searchData.segments)));
                        }
                        
                        // Redirect with parameters
                        window.location.href = resultsPage + '?' + params.toString();
                    } else {
                        // If no results page specified, show results inline
                        displayFlightResults(response.data);
                        updateSearchInfo(searchData);
                    }
                } else {
                    const errorMsg = response.data && response.data.message 
                        ? response.data.message 
                        : 'Search failed. Please try again.';
                    console.error('Search failed:', errorMsg);
                    alert('Search Error: ' + errorMsg);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    readyState: xhr.readyState,
                    statusCode: xhr.status
                });
                
                let errorMessage = 'An error occurred during search. Please try again.';
                
                // Try to parse error response
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response && response.data && response.data.message) {
                        errorMessage = response.data.message;
                    }
                } catch(e) {
                    // If parsing fails, check for common errors
                    if (xhr.status === 0) {
                        errorMessage = 'Network error. Please check your internet connection.';
                    } else if (xhr.status === 403) {
                        errorMessage = 'Security check failed. Please refresh the page and try again.';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Server error. Please try again later or contact support.';
                    } else if (xhr.statusText) {
                        errorMessage = 'Error: ' + xhr.statusText;
                    }
                }
                
                alert(errorMessage);
            },
            complete: function() {
                searchBtn.prop('disabled', false).text('Search');
            }
        });
    }

    /**
     * Display flight results
     * Make it globally accessible for modern search form
     */
    function displayFlightResults(data) {

        
        const container = $('#amadex-flight-cards-container');
        const loading = $('#amadex-loading');
        const noResults = $('#amadex-no-results');
        const loadMoreWrap = $('#amadex-load-more-wrap');
        const loadMoreBtn = $('#amadex-load-more');
        
        // Pagination state
        const PAGE_SIZE = 10;
        let renderedCount = 0;
        
        // Store search params globally for load more functionality
        const searchDataStr = sessionStorage.getItem('amadex_search_data');
        let globalSearchParams = null;
        if (searchDataStr) {
            try {
                globalSearchParams = JSON.parse(searchDataStr);
            } catch(e) {
                console.error('Error parsing search data for load more:', e);
            }
        }
        
        // Track if we need to load more from API
        let hasMoreFromAPI = false;
        let totalAvailableFromAPI = 0;
        if (data.meta && data.meta.count) {
            totalAvailableFromAPI = data.meta.count;
            hasMoreFromAPI = data.flights.length < totalAvailableFromAPI;
        }
        
        // Hide skeleton/loading animation if streaming loader was used (respects Advanced Settings)
        if (typeof window.amadexStreamingLoader !== 'undefined' && window.amadexStreamingLoader) {
            try {
                window.amadexStreamingLoader.hideSkeleton();
                window.amadexStreamingLoader.hideLoadingAnimation();
            } catch (e) {
                console.warn('Amadex: Could not hide streaming loader:', e);
            }
        }

        // Hide loading immediately
        loading.hide();
        
        // Validate data structure
        if (!data) {
            console.error('displayFlightResults: No data provided');
            noResults.show();
            container.empty();
            return;
        }
        
        // EXPERT/GOD MODE FIX: Re-check currency before displaying results
        // This ensures we use the most recent currency selection even if timing issues occurred
        // Manual currency selection from localStorage always takes precedence
        const savedSettings = localStorage.getItem('amadex_regional_settings');
        if (savedSettings) {
            try {
                const settings = JSON.parse(savedSettings);
                if (settings.currency) {
                    // Update sessionStorage immediately with manual selection
                    if (typeof sessionStorage !== 'undefined') {
                        sessionStorage.setItem('amadex_selected_currency', settings.currency);

                    }
                    
                    // EXPERT/GOD MODE FIX: Convert immediately, then re-convert after DOM is ready
                    // This ensures prices are converted as soon as possible while catching late-rendered elements
                    // Immediate conversion handles initial flight cards, delayed conversion catches virtual scroll/lazy loading
                    if (typeof convertAllPricesToCurrency === 'function') {
                        convertAllPricesToCurrency(settings.currency);

                    } else if (typeof window.amadexConvertAllPrices === 'function') {
                        window.amadexConvertAllPrices(settings.currency);

                    }
                    
                    // Also convert after a short delay to catch any late-rendered elements (virtual scroll, lazy loading, etc.)
                    // This ensures all prices are converted even if DOM elements are created after initial conversion
                    setTimeout(function() {
                        if (typeof convertAllPricesToCurrency === 'function') {
                            convertAllPricesToCurrency(settings.currency);

                        } else if (typeof window.amadexConvertAllPrices === 'function') {
                            window.amadexConvertAllPrices(settings.currency);

                        }
                    }, 500);
                }
            } catch (e) {
                console.error('Error reading currency for flight results:', e);
            }
        }
        
        // Handle different data structures (flights array or data array)
        let flights = [];
        if (data.flights && Array.isArray(data.flights)) {
            flights = data.flights;

        } else if (data.data && Array.isArray(data.data)) {
            flights = data.data;

        } else if (data.data && data.data.flights && Array.isArray(data.data.flights)) {
            flights = data.data.flights;

        }
        
        // For multi-city, check segment_results
        if ((!flights || flights.length === 0) && data.segment_results) {
            console.log('displayFlightResults: No direct flights, but found segment_results');
            // Collect all flights from segment_results
            flights = [];
            Object.keys(data.segment_results).forEach(function(segmentIndex) {
                const segmentFlights = data.segment_results[segmentIndex];
                if (Array.isArray(segmentFlights)) {
                    flights = flights.concat(segmentFlights);
                }
            });
            console.log('displayFlightResults: Collected', flights.length, 'flights from segment_results');
        }
        
        if (!flights || flights.length === 0) {
            console.warn('displayFlightResults: No flights found in any data structure');
            console.warn('displayFlightResults: Full data object:', JSON.stringify(data).substring(0, 500));
            noResults.show();
            container.empty();
            if (typeof updateResultsAvailableCount === 'function') {
            updateResultsAvailableCount(0);
            }
            return;
        }
        

        
        // Update data structure if needed
        if (!data.flights && flights.length > 0) {
            data.flights = flights;
        }
        
        // Hide no results
        noResults.hide();
        
        // Expose lowest fare for fare-alert subscription (current search baseline)
        if (flights.length > 0) {
            const prices = flights.map(function(f) {
                const p = f.price || {};
                return parseFloat(p.display_total || p.total || p.grandTotal || 0) || 0;
            }).filter(function(n) { return n > 0; });
            const lowestFare = prices.length > 0 ? Math.min.apply(null, prices) : 0;
            if (lowestFare > 0) {
                window.amadexLowestFarePrice = lowestFare;
                $('#amadex-results-page').attr('data-lowest-fare', lowestFare);
            } else {
                window.amadexLowestFarePrice = null;
                $('#amadex-results-page').removeAttr('data-lowest-fare');
            }
        } else {
            window.amadexLowestFarePrice = null;
            $('#amadex-results-page').removeAttr('data-lowest-fare');
        }
        
        // Clear existing results
        container.empty();
        
        // Check if multi-city trip - ONLY proceed if explicitly multi-city
        // Priority 1: Check URL parameters first (most reliable)
        const urlParams = new URLSearchParams(window.location.search);
        const urlTripType = urlParams.get('trip_type');
        let isMultiCity = (urlTripType === 'multi-city' || urlTripType === 'multicity');
        
        // Priority 2: Check search data from sessionStorage (reuse searchDataStr declared above)
        if (!isMultiCity && searchDataStr) {
            try {
                const searchData = JSON.parse(searchDataStr);
                const tripType = searchData.trip_type || searchData.tripType;
                isMultiCity = (tripType === 'multi-city' || tripType === 'multicity');
            } catch(e) {
                console.error('Error parsing search data:', e);
            }
        }
        
        // Priority 3: Check form on page
        if (!isMultiCity) {
            const $modernForm = $('#amadex-modern-form, #amadex-modern-form-results');
            if ($modernForm.length) {
                const tripTypeRadio = $modernForm.find('input[name="tripType"]:checked');
                if (tripTypeRadio.length && tripTypeRadio.val() === 'multi-city') {
                    isMultiCity = true;
                }
            }
        }
        
        let segments = [];
        
        // ONLY collect segments if explicitly multi-city
        if (isMultiCity) {
            // Check URL first
            const segmentsParam = urlParams.get('segments');
            if (segmentsParam) {
                try {
                    let decodedSegments = decodeURIComponent(segmentsParam);
                    try {
                        segments = JSON.parse(decodedSegments);
                    } catch(e) {
                        decodedSegments = decodeURIComponent(decodedSegments);
                        segments = JSON.parse(decodedSegments);
                    }
                    
                    // Normalize segment codes
                    segments = segments.map(seg => {
                        return {
                            origin: extractIataCode(seg.origin || seg.originLocationCode || ''),
                            originLocationCode: extractIataCode(seg.originLocationCode || seg.origin || ''),
                            destination: extractIataCode(seg.destination || seg.destinationLocationCode || ''),
                            destinationLocationCode: extractIataCode(seg.destinationLocationCode || seg.destination || ''),
                            departure_date: seg.departure_date || seg.departureDate || seg.departure || ''
                        };
                    });
                    
                    sessionStorage.setItem('amadex_multi_city_segments', JSON.stringify(segments));
                } catch(e) {
                    console.error('Error parsing segments from URL:', e);
                }
            }
            
            // Check sessionStorage
            if (segments.length === 0) {
                const segmentsStr = sessionStorage.getItem('amadex_multi_city_segments');
                if (segmentsStr) {
                    try {
                        segments = JSON.parse(segmentsStr);
                        isMultiCity = segments && segments.length > 1;
                    } catch(e) {
                        console.error('Error parsing stored segments:', e);
                    }
                }
            }
            
            // Check search data
            if (segments.length === 0 && searchDataStr) {
                try {
                    const searchData = JSON.parse(searchDataStr);
                    if (searchData.segments && Array.isArray(searchData.segments) && searchData.segments.length > 1) {
                        segments = searchData.segments;
                    }
                } catch(e) {
                    console.error('Error parsing segments from search data:', e);
                }
            }
            
            // Check form
            if (segments.length === 0) {
                const $modernForm = $('#amadex-modern-form, #amadex-modern-form-results');
                if ($modernForm.length) {
                    const tripTypeRadio = $modernForm.find('input[name="tripType"]:checked');
                    if (tripTypeRadio.length && tripTypeRadio.val() === 'multi-city') {
                        const formSegments = collectSegmentsFromModernForm();
                        if (formSegments && formSegments.length > 1) {
                            segments = formSegments;
                        }
                    }
                }
            }
        } else {
            // NOT multi-city - clear any old segments from sessionStorage
            sessionStorage.removeItem('amadex_multi_city_segments');
            // Remove any existing segment tabs
            $('.amadex-segment-tabs-container').remove();
            $('.amadex-segment-tabs-wrapper').remove();
            $('#amadex-segment-tabs').remove();

        }
        
        // ONLY create segment tabs if explicitly multi-city AND we have segments
        if (isMultiCity && segments && segments.length > 1) {
            console.log('Creating segment tabs for multi-city trip with', segments.length, 'segments');
            console.log('Creating tabs for multi-city with segments:', segments);
            console.log('Multi-city data structure:', {
                hasFlights: !!(data && data.flights),
                flightCount: data && data.flights ? data.flights.length : 0,
                hasSegmentResults: !!(data && data.segment_results),
                segmentResults: data && data.segment_results ? Object.keys(data.segment_results) : [],
                isMultiCityFlag: !!(data && data.is_multi_city)
            });
            
            // Store flights globally before creating tabs
            if (data && data.flights) {
                window.amadexAllFlights = data.flights;

            // Initialize price slider min/max from actual flight prices
            (function initPriceSlider(flights) {
                if (!flights || !flights.length) return;
                var prices = flights.map(function(f) {
                    return parseFloat(
                        (f.price && (f.price.pricing_charge_total || f.price.grandTotal || f.price.total || f.price.base)) || 0
                    );
                }).filter(function(p) { return p > 0; });
                if (!prices.length) return;
                var minP = Math.floor(Math.min.apply(null, prices));
                var maxP = Math.ceil(Math.max.apply(null, prices));
                $('#amadex-price-min').attr('min', minP).attr('max', maxP).val(minP);
                $('#amadex-price-max').attr('min', minP).attr('max', maxP).val(maxP);
                $('#amadex-price-min-display').text('$' + minP);
                $('#amadex-price-max-display').text('$' + maxP);
            })(data.flights);
                console.log('Stored', data.flights.length, 'total flights for multi-city filtering');
            } else {
                console.warn('No flights data available for multi-city trip');
            }
            
            // Store segment results if available from backend
            if (data && data.segment_results) {
                console.log('Backend provided segment_results:', Object.keys(data.segment_results).length, 'segments');
            }
            
            // Don't display regular flights - wait for tab selection
            container.empty();
            loadMoreWrap.hide();
            
            createSegmentTabs(segments, data);
            
            // Don't proceed with regular flight rendering for multi-city
            return;
        } else {
            // NOT multi-city - remove any existing segment tabs and ensure they're hidden
            $('.amadex-segment-tabs-container').remove();
            $('.amadex-segment-tabs-wrapper').remove();
            $('#amadex-segment-tabs').remove();


        }
        
        // CRITICAL: Reset container appearance tracking for new results
        containerAppearanceCount = {};
        
        // Pre-fetch promotional containers before rendering (advanced technique: synchronous availability)
        let preFetchedPromoContainers = null;
        let containersPromiseResolved = false;
        const promoContainersPromise = getPromotionalContainers().then(function(containers) {
            preFetchedPromoContainers = containers || {};
            containersPromiseResolved = true;
            return containers;
        }).catch(function() {
            preFetchedPromoContainers = {};
            containersPromiseResolved = true;
            return {};
        });
        
        // Helper to render next chunk
        function renderNext() {
            const next = data.flights.slice(renderedCount, renderedCount + PAGE_SIZE);
            next.forEach(function(flight, i) {
                const index = renderedCount + i;
                const flightElement = createFlightElement(flight, index);
                // CRITICAL FIX: Convert to jQuery BEFORE appending, or get reference AFTER appending
                const $flightElement = $(flightElement);
                container.append($flightElement);
                
                // CRITICAL: Ensure $flightElement references the actual DOM element that was just appended
                // If flightElement was a string, $flightElement might be detached, so get the actual appended element
                const $actualFlightElement = $flightElement.length > 0 && $flightElement.parent().length > 0 
                    ? $flightElement 
                    : container.children('.amadex-flight-card').last();
                
                // Insert promotional containers at intervals (after flight card) - SYNCHRONOUS with pre-fetched data
                // Use pre-fetched containers if available, otherwise will use cached version
                // CRITICAL: Check cache first if promise hasn't resolved yet (faster fallback)
                let containersToUse = null;
                if (preFetchedPromoContainers !== null) {
                    containersToUse = preFetchedPromoContainers;
                } else if (promotionalContainersCache) {
                    containersToUse = promotionalContainersCache;
                } else {
                    containersToUse = {};
                }
                insertPromotionalContainerAfter($actualFlightElement, index + 1, containersToUse);
            });
            renderedCount += next.length;
            // Toggle load more visibility
            // Show if: (1) more flights in current data, OR (2) more available from API
            if (renderedCount < data.flights.length || hasMoreFromAPI) {
                loadMoreWrap.show();
            } else {
                loadMoreWrap.hide();
            }
            sortFlights();
        }
        
        // Initialize price display to fix any double currency symbols after all flights are rendered
        setTimeout(function() {
            initializePriceDisplay();
        }, 100);
        
        // Update header information
        const totalCount = data.meta && data.meta.count ? data.meta.count : data.flights.length;
        updateResultsAvailableCount(totalCount);
        
        // Build airline price matrix
        if (typeof window.amadexBuildMatrix === 'function') {
            window.amadexBuildMatrix(data.flights);
        }
        
        // Initialize filters with flight data
        if (typeof window.amadexInitFilters === 'function') {
            window.amadexInitFilters(data.flights);
        }
        
        // Update recommended price
        if (data.flights.length > 0) {
            const cheapest = Math.min(...data.flights.map(f => f.price.total));
            $('#amadex-recommended-price').text('$' + cheapest.toFixed(2));
        }
        
        // Create render function for filters to use
        window.amadexRenderFlights = function(flights) {
            container.empty();
            renderedCount = 0;
            flightsViewedCount = 0; // Reset flight view count
            containerAppearanceCount = {}; // CRITICAL: Reset appearance tracking for filtered results
            data.flights = flights; // Update data
            
            // When filters leave zero results, show the no-results message
            if (!flights || flights.length === 0) {
                $('#amadex-no-results').show();
            } else {
                $('#amadex-no-results').hide();
            }
            
            // Pre-fetch containers again when filters change (async but don't block)
            preFetchedPromoContainers = null;
            getPromotionalContainers().then(function(containers) {
                preFetchedPromoContainers = containers || {};
            }).catch(function() {
                preFetchedPromoContainers = {};
            });
            
            renderNext();
        };
        
        // Initial render - wait for containers to be pre-fetched (with timeout fallback for performance)
        // ADVANCED: Use cache immediately if available, otherwise wait longer for containers
        // Check cache FIRST - if available, use it immediately (no need to wait)
        const hasCachedContainers = promotionalContainersCache && Object.keys(promotionalContainersCache).length > 0;
        
        if (hasCachedContainers) {
            // Use cache immediately - no waiting
            preFetchedPromoContainers = promotionalContainersCache;
            console.log('Amadex Promo: Using cached containers immediately for initial render');
            // Start background refresh (don't block rendering)
            promoContainersPromise.then(function(containers) {
                if (containers && Object.keys(containers).length > 0) {
                    preFetchedPromoContainers = containers;
                    promotionalContainersCache = containers;
                    console.log('Amadex Promo: Containers refreshed in background');
                }
            });
            // Render immediately with cached containers
        renderNext();
        } else {
            // No cache - wait longer for containers (CRITICAL FIX: Increased timeout to 2s for first load)
            Promise.race([
                promoContainersPromise,
                new Promise(function(resolve) {
                    setTimeout(resolve, 2000); // 2s timeout - give containers time to load on first request
                })
            ]).then(function() {
                // Use loaded containers or fallback to empty object
                if (preFetchedPromoContainers === null) {
                    // Still no containers after timeout - use empty but log it
                    preFetchedPromoContainers = {};
                    console.warn('Amadex Promo: No containers loaded within timeout, rendering without containers');
                } else {

                }
                renderNext();
            }).catch(function() {
                // On error, render anyway (containers will be empty)
                if (promotionalContainersCache) {
                    preFetchedPromoContainers = promotionalContainersCache;
                } else {
                    preFetchedPromoContainers = {};
                }
                renderNext();
            });
            
            // CRITICAL FIX #3: If containers load AFTER initial render, insert them retroactively
            promoContainersPromise.then(function(containers) {
                if (containers && Object.keys(containers).length > 0) {
                    // Check if flights have already been rendered (use setTimeout to check after renderNext completes)
                    setTimeout(function() {
                        const $existingFlights = container.children('.amadex-flight-card');
                        if ($existingFlights.length > 0 && preFetchedPromoContainers === null) {
                            // Containers loaded but weren't used in render (they loaded after timeout)
                            console.log('Amadex Promo: Containers loaded after initial render, inserting retroactively', {
                                flightsCount: $existingFlights.length,
                                containersCount: Object.keys(containers).length
                            });
                            insertPromotionalContainersRetroactively(containers, $existingFlights);
                        }
                    }, 100);
                }
            });
        }
        
        // Initialize price display after initial render - fix any double currency symbols
        setTimeout(function() {
            initializePriceDisplay();
            // Also run after a short delay to catch any dynamically added prices
            setTimeout(initializePriceDisplay, 500);
        }, 100);
        
        // Function to load more results from API
        function loadMoreFromAPI() {
            if (!globalSearchParams || !hasMoreFromAPI) {
                console.log('No more results to load from API');
                loadMoreWrap.hide();
                return;
            }
            
            // Show loading state
            loadMoreBtn.prop('disabled', true).text('Loading more...');
            loading.show();
            
            // Perform search with load_more flag
            $.ajax({
                url: AmadexConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'amadex_search_flights',
                    origin: globalSearchParams.origin || globalSearchParams.origin_iata,
                    destination: globalSearchParams.destination || globalSearchParams.destination_iata,
                    departure_date: globalSearchParams.departure || globalSearchParams.depart_date,
                    return_date: globalSearchParams.return || globalSearchParams.return_date || '',
                    adults: globalSearchParams.adults || 1,
                    children: globalSearchParams.children || 0,
                    infants: globalSearchParams.infants || 0,
                    travel_class: globalSearchParams.cabin || 'ECONOMY',
                    currency: globalSearchParams.currency || 'USD',
                    trip_type: globalSearchParams.trip_type || 'round',
                    load_more: '1', // Load more flag (will fetch 250 results)
                    nonce: AmadexConfig.nonce
                },
                success: function(response) {
                    loading.hide();
                    loadMoreBtn.prop('disabled', false).text('Load More');
                    
                    if (response.success && response.data && response.data.flights) {
                        // Append new flights to existing data
                        const newFlights = response.data.flights;
                        const existingFlightIds = new Set(data.flights.map(f => f.id));
                        
                        // Filter out duplicates
                        const uniqueNewFlights = newFlights.filter(f => !existingFlightIds.has(f.id));
                        
                        if (uniqueNewFlights.length > 0) {
                            // Append to data.flights
                            data.flights = data.flights.concat(uniqueNewFlights);
                            
                            // Update hasMoreFromAPI flag
                            if (response.data.meta && response.data.meta.count) {
                                hasMoreFromAPI = data.flights.length < response.data.meta.count;
                                totalAvailableFromAPI = response.data.meta.count;
                            } else {
                                hasMoreFromAPI = false;
                            }
                            
                            // Render next chunk from newly loaded data
                            renderNext();
                            
                            // Update total count display
                            if (typeof updateResultsAvailableCount === 'function') {
                                updateResultsAvailableCount(data.flights.length);
                            }
                        } else {
                            console.log('No new unique flights to add');
                            hasMoreFromAPI = false;
                            loadMoreWrap.hide();
                        }
                    } else {
                        console.warn('Load more failed:', response);
                        hasMoreFromAPI = false;
                        loadMoreWrap.hide();
                    }
                },
                error: function(xhr, status, error) {
                    loading.hide();
                    loadMoreBtn.prop('disabled', false).text('Load More');
                    console.error('Load more error:', error);
                    alert('Failed to load more results. Please try again.');
                }
            });
        }
        
        // Bind load more button
        loadMoreBtn.off('click').on('click', function(e) {
            e.preventDefault();
            
            // If we've rendered all current flights and there are more from API, load from API
            if (renderedCount >= data.flights.length && hasMoreFromAPI) {
                loadMoreFromAPI();
            } else {
                // Otherwise, just render next chunk from existing data
                renderNext();
            }
        });
        
        // Infinite scroll implementation (Phase 1 optimization)
        let scrollTimeout = null;
        $(window).off('scroll.amadex-infinite-scroll').on('scroll.amadex-infinite-scroll', function() {
            // Throttle scroll events
            if (scrollTimeout) {
                clearTimeout(scrollTimeout);
            }
            
            scrollTimeout = setTimeout(function() {
                // Check if user scrolled near bottom (within 300px of bottom)
                const scrollTop = $(window).scrollTop();
                const windowHeight = $(window).height();
                const documentHeight = $(document).height();
                const distanceFromBottom = documentHeight - (scrollTop + windowHeight);
                
                // If near bottom and not already loading
                if (distanceFromBottom < 300 && !loadMoreBtn.prop('disabled')) {
                    // If we've rendered all current flights and there are more from API, load from API
                    if (renderedCount >= data.flights.length && hasMoreFromAPI) {
                        loadMoreFromAPI();
                    } else if (renderedCount < data.flights.length) {
                        // Otherwise, render next chunk from existing data
                        renderNext();
                    }
                }
            }, 100); // Throttle to 100ms
        });
        
        // Store results for filtering
        currentSearchData = data;
        currentSearchData._searchInfo = currentSearchData._searchInfo || {};
        currentSearchData._searchInfo.isOneWay = isOneWaySearch;
        currentSearchData._searchInfo.isMultiCity = isMultiCity;
        currentSearchData._searchInfo.segments = segments;
        currentSearchData._searchInfo.activeSegment = segments.length > 0 ? 0 : null;
    }
    
    /**
     * Create segment tabs for multi-city trips
     */
    function createSegmentTabs(segments, data) {
        // Remove existing tabs if any
        $('.amadex-segment-tabs-container').remove();
        
        if (!segments || segments.length <= 1) {
            console.log('Not creating tabs - segments:', segments);
            return; // No need for tabs with single segment
        }
        
        console.log('Creating segment tabs with segments:', segments);
        
        // Insert tabs right after booking progress bar, before sort bar (where arrow points)
        const $bookingProgress = $('.amadex-booking-progress').first();
        const $sortBar = $('.amadex-results-sort-bar').first();
        
        const tabsHTML = createTabsHTML(segments);
        
        if ($bookingProgress.length && $sortBar.length) {
            // Insert after booking progress, before sort bar
            $bookingProgress.after(tabsHTML);
            console.log('Tabs inserted after booking progress, before sort bar');
        } else if ($bookingProgress.length) {
            // If no sort bar, insert after booking progress
            $bookingProgress.after(tabsHTML);
            console.log('Tabs inserted after booking progress');
        } else if ($sortBar.length) {
            // If no booking progress, insert before sort bar
            $sortBar.before(tabsHTML);
            console.log('Tabs inserted before sort bar');
        } else {
            // Last fallback: insert after results header
            const $resultsHeader = $('.amadex-results-top-header').first();
            if ($resultsHeader.length) {
                $resultsHeader.after(tabsHTML);
                console.log('Tabs inserted after results header');
            } else {
                // Ultimate fallback: insert at beginning of flights list
                const $flightsList = $('#amadex-flights-list').first();
                if ($flightsList.length) {
                    $flightsList.prepend(tabsHTML);
                    console.log('Tabs inserted at beginning of flights list');
                }
            }
        }
        
        // Initialize tab switching (even if no data yet)
        initSegmentTabs(segments, data || { flights: [] });
        
        // Ensure first tab is active and results are displayed immediately
        setTimeout(function() {
            const $firstTab = $('.amadex-segment-tab[data-segment="0"]');
            if ($firstTab.length && !$firstTab.hasClass('is-active')) {
                $firstTab.addClass('is-active');
                if (typeof displaySegmentFlights === 'function') {
                    displaySegmentFlights(0, segments, data || { flights: [] });
                } else if (typeof window.displaySegmentFlights === 'function') {
                    window.displaySegmentFlights(0, segments, data || { flights: [] });
                }
            }
        }, 100);
    }
    
    /**
     * Create HTML for segment tabs
     */
    function createTabsHTML(segments) {
        let tabsHTML = '<div class="amadex-segment-tabs-container" id="amadex-segment-tabs">';
        tabsHTML += '<div class="amadex-segment-tabs-wrapper">';
        tabsHTML += '<div class="amadex-segment-tabs-header">';
        
        segments.forEach((segment, index) => {
            const originInfo = getAirportInfo(segment.origin || segment.originLocationCode);
            const destInfo = getAirportInfo(segment.destination || segment.destinationLocationCode);
            const originCity = originInfo ? originInfo.city : (segment.origin || segment.originLocationCode);
            const destCity = destInfo ? destInfo.city : (segment.destination || segment.destinationLocationCode);
            const originCode = segment.origin || segment.originLocationCode;
            const destCode = segment.destination || segment.destinationLocationCode;
            
            // Format date with better formatting
            let dateStr = '';
            let dateFull = '';
            if (segment.departure_date || segment.departureDate) {
                try {
                    const date = new Date(segment.departure_date || segment.departureDate);
                    dateStr = formatDate(date);
                    // Get day name
                    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                    const dayName = days[date.getDay()];
                    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    const monthName = months[date.getMonth()];
                    const dayNum = date.getDate();
                    const year = date.getFullYear();
                    dateFull = `${dayNum} ${monthName}, ${year} ${dayName}`;
                } catch(e) {
                    dateStr = segment.departure_date || segment.departureDate || '';
                    dateFull = dateStr;
                }
            }
            
            // Calculate duration if available
            let durationStr = '';
            if (segment.duration) {
                durationStr = formatDuration(segment.duration);
            }
            
            // Check if this segment has a selected flight
            const multiCityBookings = sessionStorage.getItem('amadex_multi_city_bookings');
            let hasSelectedFlight = false;
            if (multiCityBookings) {
                try {
                    const bookings = JSON.parse(multiCityBookings);
                    hasSelectedFlight = bookings[index] !== undefined;
                } catch(e) {
                    console.error('Error parsing multi-city bookings:', e);
                }
            }
            
            const isActive = index === 0 ? 'is-active' : '';
            const selectedClass = hasSelectedFlight ? 'segment-selected' : '';
            const segmentNumber = index + 1;
            
            tabsHTML += `
                <div class="amadex-segment-tab ${isActive} ${selectedClass}" 
                        data-segment="${index}" 
                        data-origin="${originCode}" 
                        data-destination="${destCode}">
                    <div class="amadex-segment-tab-content">
                        ${hasSelectedFlight ? '<span class="segment-selected-indicator">✓</span>' : ''}
                        <div class="amadex-segment-tab-number">${segmentNumber}</div>
                        <div class="amadex-segment-tab-route">
                            <span class="amadex-segment-tab-origin">${originCity}</span>
                            <span class="amadex-segment-tab-arrow">→</span>
                            <span class="amadex-segment-tab-destination">${destCity}</span>
                        </div>
                        ${dateFull ? `<div class="amadex-segment-tab-date">${dateFull}</div>` : ''}
                    </div>
                </div>
            `;
        });
        
        tabsHTML += '</div></div></div>';
        return tabsHTML;
    }
    
    /**
     * Initialize segment tab switching
     */
    function initSegmentTabs(segments, data) {
        // Store all flights by segment
        if (!window.amadexSegmentFlights) {
            window.amadexSegmentFlights = {};
        }
        
        // Store original data for filtering
        if (!window.amadexAllFlights) {
            window.amadexAllFlights = data.flights || [];
        }
        
        // Check if we have segment_results from backend (more efficient)
        if (data.segment_results && typeof data.segment_results === 'object') {
            console.log('Using segment_results from backend for filtering');
            segments.forEach((segment, segmentIndex) => {
                if (data.segment_results[segmentIndex]) {
                    window.amadexSegmentFlights[segmentIndex] = data.segment_results[segmentIndex].flights || [];
                    const originCode = extractIataCode(segment.origin || segment.originLocationCode || '');
                    const destCode = extractIataCode(segment.destination || segment.destinationLocationCode || '');
                    console.log(`Segment ${segmentIndex} (${originCode} → ${destCode}): Found ${window.amadexSegmentFlights[segmentIndex].length} flights from backend`);
                } else {
                    window.amadexSegmentFlights[segmentIndex] = [];
                    console.warn(`No backend results for segment ${segmentIndex}`);
                }
            });
        } else {
            // Fallback: Use _segment_index tag if available (from backend tagging)
            const flightsBySegment = {};
            segments.forEach((segment, segmentIndex) => {
                flightsBySegment[segmentIndex] = [];
            });
            
            window.amadexAllFlights.forEach(flight => {
                // Check if flight has _segment_index tag from backend
                if (flight._segment_index !== undefined && flight._segment_index !== null) {
                    const segIndex = parseInt(flight._segment_index);
                    if (!flightsBySegment[segIndex]) {
                        flightsBySegment[segIndex] = [];
                    }
                    flightsBySegment[segIndex].push(flight);
                }
            });
            
            // Use tagged flights if available
            let hasTaggedFlights = false;
            segments.forEach((segment, segmentIndex) => {
                if (flightsBySegment[segmentIndex] && flightsBySegment[segmentIndex].length > 0) {
                    window.amadexSegmentFlights[segmentIndex] = flightsBySegment[segmentIndex];
                    hasTaggedFlights = true;
                    const originCode = extractIataCode(segment.origin || segment.originLocationCode || '');
                    const destCode = extractIataCode(segment.destination || segment.destinationLocationCode || '');
                    console.log(`Segment ${segmentIndex} (${originCode} → ${destCode}): Found ${flightsBySegment[segmentIndex].length} tagged flights`);
                }
            });
            
            // If no tagged flights, fall back to filtering by origin/destination
            if (!hasTaggedFlights) {
                console.log('No tagged flights found, filtering by origin/destination');
                segments.forEach((segment, segmentIndex) => {
                    const originCode = extractIataCode(segment.origin || segment.originLocationCode || '');
                    const destCode = extractIataCode(segment.destination || segment.destinationLocationCode || '');
                    
                    console.log(`Filtering flights for segment ${segmentIndex}: ${originCode} → ${destCode}`);
                    
                    // Filter flights that match this segment
                    const segmentFlights = window.amadexAllFlights.filter(flight => {
                        if (!flight.itineraries || !flight.itineraries[0]) return false;
                        const itinerary = flight.itineraries[0];
                        if (!itinerary.segments || itinerary.segments.length === 0) return false;
                        
                        const firstSeg = itinerary.segments[0];
                        const lastSeg = itinerary.segments[itinerary.segments.length - 1];
                        
                        // Try multiple property paths for iataCode
                        let flightOrigin = firstSeg.departure?.iataCode || 
                                         firstSeg.departure?.iata_code || 
                                         firstSeg.departure?.airport?.iataCode ||
                                         firstSeg.departure?.locationCode;
                        let flightDest = lastSeg.arrival?.iataCode || 
                                       lastSeg.arrival?.iata_code || 
                                       lastSeg.arrival?.airport?.iataCode ||
                                       lastSeg.arrival?.locationCode;
                        
                        // Normalize codes to uppercase for comparison
                        const originIata = (flightOrigin || '').toString().toUpperCase().trim();
                        const destIata = (flightDest || '').toString().toUpperCase().trim();
                        const expectedOrigin = originCode.toString().toUpperCase().trim();
                        const expectedDest = destCode.toString().toUpperCase().trim();
                        
                        return originIata === expectedOrigin && destIata === expectedDest;
                    });
                    
                    window.amadexSegmentFlights[segmentIndex] = segmentFlights;
                    console.log(`Segment ${segmentIndex} (${originCode} → ${destCode}): Found ${segmentFlights.length} flights after filtering`);
                    
                    if (segmentFlights.length === 0) {
                        console.warn(`⚠ No flights found for segment ${segmentIndex} (${originCode} → ${destCode})`);
                    }
                });
            }
        }
        
        // Handle tab clicks with smooth animation
        $('.amadex-segment-tab').off('click').on('click', function() {
            const $tab = $(this);
            const segmentIndex = parseInt($tab.data('segment'));
            
            // Don't switch if already active
            if ($tab.hasClass('is-active')) {
                return;
            }
            
            console.log('Switching to segment', segmentIndex + 1, 'of', segments.length);
            
            // Update active tab with animation
            $('.amadex-segment-tab').removeClass('is-active');
            $tab.addClass('is-active');
            
            // Update current search info
            if (currentSearchData && currentSearchData._searchInfo) {
                currentSearchData._searchInfo.activeSegment = segmentIndex;
            }
            
            // Smoothly update displayed flights
            // const $container = $('#amadex-flight-cards-container');
            // const $loading = $('#amadex-loading');
            
            // // Show loading briefly
            // if (!$loading.is(':visible')) {
            //     $container.fadeOut(150);
            //     setTimeout(function() {
            //         displaySegmentFlights(segmentIndex, segments, data);
            //         $container.fadeIn(300);
                    // Save active filter state before switching tabs
            var savedFilterState = window.amadexSaveFilterState ? window.amadexSaveFilterState() : null;

            // Smoothly update displayed flights
            const $container = $('#amadex-flight-cards-container');
            const $loading = $('#amadex-loading');
            
            // Show loading briefly
            if (!$loading.is(':visible')) {
                $container.fadeOut(150);
                setTimeout(function() {
                    displaySegmentFlights(segmentIndex, segments, data, savedFilterState);
                    $container.fadeIn(300);
                    // Scroll to top of results
                    if ($container.length && $container.offset()) {
                        $('html, body').animate({
                            scrollTop: $container.offset().top - 150
                        }, 300);
                    }
                }, 150);
        //     } else {
        //         displaySegmentFlights(segmentIndex, segments, data);
        //     }
        // });
        
} else {
                displaySegmentFlights(segmentIndex, segments, data, savedFilterState);
            }
        });

        // Always display first segment by default (even if no flights - will show "no results")
        if (segments.length > 0) {
            // Ensure first tab is active
            $('.amadex-segment-tab').removeClass('is-active');
            const $firstTab = $('.amadex-segment-tab[data-segment="0"]');
            if ($firstTab.length) {
                $firstTab.addClass('is-active');
            }
            
            // Always display first segment's flights (even if empty array - will show "no results" message)
            const activeTab = $('.amadex-segment-tab.is-active');
            const currentSegmentIndex = activeTab.length ? parseInt(activeTab.data('segment')) : 0;
            
            // Initialize flights array for first segment if not exists
            if (!window.amadexSegmentFlights[0]) {
                window.amadexSegmentFlights[0] = [];
            }
            
            // Display first segment immediately
                displaySegmentFlights(0, segments, data);
            console.log('initSegmentTabs: Displayed first segment (index 0) immediately');
        }
        
        // Make displaySegmentFlights accessible globally for Book Now handler
        window.displaySegmentFlights = displaySegmentFlights;
    }
    
    /**
     * Display flights for a specific segment
     */
    // function displaySegmentFlights(segmentIndex, segments, data) {
    function displaySegmentFlights(segmentIndex, segments, data, savedFilterState) {
        const segment = segments[segmentIndex];
        if (!segment) {
            console.error('Segment not found at index:', segmentIndex);
            return;
        }
        
        const container = $('#amadex-flight-cards-container');
        const loadMoreWrap = $('#amadex-load-more-wrap');
        const noResults = $('#amadex-no-results');
        
        // Get flights for this segment
        let segmentFlights = [];
        if (window.amadexSegmentFlights && window.amadexSegmentFlights[segmentIndex]) {
            segmentFlights = window.amadexSegmentFlights[segmentIndex];
        } else {
            // Fallback: filter flights by origin/destination
            const allFlights = window.amadexAllFlights || data.flights || [];
            const originCode = extractIataCode(segment.origin || segment.originLocationCode || '');
            const destCode = extractIataCode(segment.destination || segment.destinationLocationCode || '');
            
            console.log(`displaySegmentFlights: Filtering for ${originCode} → ${destCode} from ${allFlights.length} flights`);
            
            segmentFlights = allFlights.filter(flight => {
                if (!flight.itineraries || !flight.itineraries[0]) return false;
                const itinerary = flight.itineraries[0];
                if (!itinerary.segments || itinerary.segments.length === 0) return false;
                
                const firstSeg = itinerary.segments[0];
                const lastSeg = itinerary.segments[itinerary.segments.length - 1];
                
                // Try multiple property paths for iataCode
                let flightOrigin = firstSeg.departure?.iataCode || 
                                 firstSeg.departure?.iata_code || 
                                 (firstSeg.departure?.at ? firstSeg.departure.iataCode : null);
                let flightDest = lastSeg.arrival?.iataCode || 
                               lastSeg.arrival?.iata_code || 
                               (lastSeg.arrival?.at ? lastSeg.arrival.iataCode : null);
                
                // If still no code, try to extract from airport object or location code
                if (!flightOrigin) {
                    flightOrigin = firstSeg.departure?.airport?.iataCode || 
                                 firstSeg.departure?.airport?.iata_code ||
                                 firstSeg.departure?.locationCode;
                }
                
                if (!flightDest) {
                    flightDest = lastSeg.arrival?.airport?.iataCode || 
                               lastSeg.arrival?.airport?.iata_code ||
                               lastSeg.arrival?.locationCode;
                }
                
                // Normalize codes to uppercase for comparison
                const originIata = (flightOrigin || '').toString().toUpperCase().trim();
                const destIata = (flightDest || '').toString().toUpperCase().trim();
                const expectedOrigin = originCode.toString().toUpperCase().trim();
                const expectedDest = destCode.toString().toUpperCase().trim();
                
                const match = originIata === expectedOrigin && destIata === expectedDest;
                
                if (match) {
                    console.log(`✓ displaySegmentFlights: Matched flight ${originIata} → ${destIata}`);
                }
                
                return match;
            });
            
            // Store for future use
            if (!window.amadexSegmentFlights) {
                window.amadexSegmentFlights = {};
            }
            window.amadexSegmentFlights[segmentIndex] = segmentFlights;
        }
        
        // Clear container
        container.empty();
        
        // Show/hide no results
        if (segmentFlights.length === 0) {
            noResults.show();
            container.hide();
        } else {
            noResults.hide();
            container.show();
        }
        
        // Update header text to show "Choose Flight X" for this segment
        const segmentNumber = segmentIndex + 1;
        const segOriginInfo = getAirportInfo(segment.origin || segment.originLocationCode);
        const segDestInfo = getAirportInfo(segment.destination || segment.destinationLocationCode);
        const segOriginCity = segOriginInfo ? segOriginInfo.city : (segment.origin || segment.originLocationCode);
        const segDestCity = segDestInfo ? segDestInfo.city : (segment.destination || segment.destinationLocationCode);
        const segOriginCode = extractIataCode(segment.origin || segment.originLocationCode || '');
        const segDestCode = extractIataCode(segment.destination || segment.destinationLocationCode || '');
        
        // Update results header text to show flight count
        const $resultsHeader = $('.amadex-results-header, .amadex-results-top-header h2, .amadex-results-count');
        if ($resultsHeader.length) {
            const countText = segmentFlights.length === 1 ? 'flight' : 'flights';
            $resultsHeader.text(`${segmentFlights.length} ${countText} from ${segOriginCity} (${segOriginCode}) to ${segDestCity} (${segDestCode})`);
        }
        
        // Update or create "Choose Flight X" heading
        let $chooseFlightHeading = $('.amadex-segment-flight-heading, .amadex-choose-flight-text');
        if (!$chooseFlightHeading.length) {
            // Create heading before sort bar or before container
            const $sortBar = $('.amadex-results-sort-bar').first();
            if ($sortBar.length) {
                $chooseFlightHeading = $('<h3 class="amadex-segment-flight-heading amadex-choose-flight-text">Choose Flight ' + segmentNumber + '</h3>');
                $sortBar.before($chooseFlightHeading);
            } else {
                $chooseFlightHeading = $('<h3 class="amadex-segment-flight-heading amadex-choose-flight-text">Choose Flight ' + segmentNumber + '</h3>');
                container.before($chooseFlightHeading);
            }
        } else {
            $chooseFlightHeading.text('Choose Flight ' + segmentNumber);
        }
        
        // Update count
        updateResultsAvailableCount(segmentFlights.length);
        
        // Render flights (first 10 initially, rest via load more)
        const PAGE_SIZE = 10;
        const flightsToRender = segmentFlights.slice(0, PAGE_SIZE);
        
        flightsToRender.forEach((flight, index) => {
            const flightElement = createFlightElement(flight, index);
            // Add segment index to flight card for multi-city tracking
            if (segmentIndex !== undefined && segmentIndex !== null) {
                flightElement.attr('data-segment-index', segmentIndex);
            }
            container.append(flightElement);
        });
        
        // Show/hide load more
        if (segmentFlights.length > PAGE_SIZE) {
            loadMoreWrap.show();
            // Update load more handler
            $('#amadex-load-more').off('click').on('click', function(e) {
                e.preventDefault();
                let renderedCount = PAGE_SIZE;
                const next = segmentFlights.slice(renderedCount, renderedCount + PAGE_SIZE);
                next.forEach((flight, i) => {
                    const index = renderedCount + i;
                    const flightElement = createFlightElement(flight, index);
                    // Add segment index to flight card for multi-city tracking
                    if (segmentIndex !== undefined && segmentIndex !== null) {
                        flightElement.attr('data-segment-index', segmentIndex);
                    }
                    container.append(flightElement);
                });
                renderedCount += next.length;
                if (renderedCount >= segmentFlights.length) {
                    loadMoreWrap.hide();
                }
            });
        } else {
            loadMoreWrap.hide();
        }
        
        // Update additional header elements if they exist
        $('#amadex-from-city').text(segOriginCity);
        $('#amadex-to-city').text(segDestCity);
        $('#amadex-total-flights').text(segmentFlights.length);
        
        // Update sort functionality for this segment
        // if (typeof window.amadexInitFilters === 'function') {
        //     window.amadexInitFilters(segmentFlights);
        // }

        // Update sort functionality for this segment
        if (typeof window.amadexInitFilters === 'function') {
            window.amadexInitFilters(segmentFlights);
            // Restore filter state after re-initializing (so tab switch preserves filters)
            if (savedFilterState && typeof window.amadexRestoreFilterState === 'function') {
                window.amadexRestoreFilterState(savedFilterState);
            }
        }
        
        setTimeout(function() {
    var savedSettings = localStorage.getItem('amadex_regional_settings');
    if (savedSettings) {
        try {
            var settings = JSON.parse(savedSettings);
            if (settings.currency && settings.currency !== 'USD') {
                // Reset data-currency so convertSinglePriceElement doesn't skip them
                $('.amadex-flight-price').attr('data-currency', 'USD');
                if (typeof window.amadexConvertAllPrices === 'function') {
                    window.amadexConvertAllPrices(settings.currency);
                }
            }
        } catch(e) {}
    }
}, 300);
        
        // Store active segment
        if (currentSearchData && currentSearchData._searchInfo) {
            currentSearchData._searchInfo.activeSegment = segmentIndex;
        }
    }
    
    /**
     * Collect segments from modern search form on page
     */
    function collectSegmentsFromModernForm() {
        const segments = [];
        const $modernForm = $('#amadex-modern-form, #amadex-modern-form-results');
        
        if (!$modernForm.length) {
            return segments;
        }
        
        // Check trip type
        const tripType = $modernForm.find('input[name="tripType"]:checked').val();
        if (tripType !== 'multi-city') {
            return segments;
        }
        
        // Collect all segments from form
        $('.amadex-flight-segment').each(function() {
            const $segment = $(this);
            const segmentNum = $segment.data('segment') || 1;
            
            let originCode, destCode, depDate;
            
            if (segmentNum === 1) {
                originCode = $('#modern-origin-code').val() || $('#modern-origin').val();
                destCode = $('#modern-destination-code').val() || $('#modern-destination').val();
                depDate = $('#modern-departure').val();
            } else {
                originCode = $(`#modern-origin-code-${segmentNum}`).val() || $(`#modern-origin-${segmentNum}`).val();
                destCode = $(`#modern-destination-code-${segmentNum}`).val() || $(`#modern-destination-${segmentNum}`).val();
                depDate = $(`#modern-departure-${segmentNum}`).val();
            }
            
            if (originCode && destCode && depDate) {
                // Normalize IATA codes
                const normalizedOrigin = extractIataCode(originCode);
                const normalizedDest = extractIataCode(destCode);
                
                segments.push({
                    origin: normalizedOrigin,
                    originLocationCode: normalizedOrigin,
                    destination: normalizedDest,
                    destinationLocationCode: normalizedDest,
                    departure_date: depDate
                });
                
                console.log(`collectSegmentsFromModernForm: Collected segment ${segmentNum}: ${normalizedOrigin} → ${normalizedDest} on ${depDate}`);
            }
        });
        
        return segments;
    }
    
    /**
     * Check and initialize multi-city tabs when results page loads
     */
    function checkAndInitMultiCityTabs() {
        // Check if we're on results page
        if (!isResultsPage()) {
            return;
        }
        
        // FIRST: Check if this is actually a multi-city trip BEFORE doing anything
        const urlParams = new URLSearchParams(window.location.search);
        let tripType = urlParams.get('trip_type');
        let isMultiCity = tripType === 'multi-city' || tripType === 'multicity';
        
        // Also check sessionStorage for trip type
        const searchDataStr = sessionStorage.getItem('amadex_search_data');
        if (!isMultiCity && searchDataStr) {
            try {
                const searchData = JSON.parse(searchDataStr);
                tripType = searchData.trip_type || searchData.tripType;
                isMultiCity = tripType === 'multi-city' || tripType === 'multicity';
            } catch(e) {
                console.error('Error parsing search data:', e);
            }
        }
        
        // If NOT multi-city, remove any existing tabs and return immediately
        if (!isMultiCity) {
            $('.amadex-segment-tabs-container').remove();
            $('.amadex-segment-tabs-wrapper').remove();
            $('#amadex-segment-tabs').remove();
            $('.amadex-segment-tab').remove();
            console.log('checkAndInitMultiCityTabs: NOT multi-city (trip_type:', tripType, '), removed any existing tabs');
            return; // Exit early - don't create tabs for one-way or round trip
        }
        
        // We only get here if it's multi-city
        // If tabs already exist, ensure first tab is active and showing results
        if ($('.amadex-segment-tabs-container').length > 0) {
            console.log('checkAndInitMultiCityTabs: Tabs already exist for multi-city, ensuring first tab is active');
            
            // Ensure first tab is active
            const $firstTab = $('.amadex-segment-tab[data-segment="0"]');
            const $activeTab = $('.amadex-segment-tab.is-active');
            
            if ($firstTab.length) {
                // If no tab is active or first tab is not active, activate first tab
                if (!$activeTab.length || parseInt($activeTab.data('segment')) !== 0) {
                    $('.amadex-segment-tab').removeClass('is-active');
                    $firstTab.addClass('is-active');
                    
                    // Display first segment's flights if segments are available
                    const segmentsStr = sessionStorage.getItem('amadex_multi_city_segments');
                    const resultsStr = sessionStorage.getItem('amadex_search_results');
                    
                    if (segmentsStr && resultsStr) {
                        try {
                            const segments = JSON.parse(segmentsStr);
                            const data = JSON.parse(resultsStr);
                            
                            // Ensure flights are stored
                            if (data && data.flights) {
                                window.amadexAllFlights = data.flights;

            // Initialize price slider min/max from actual flight prices
            (function initPriceSlider(flights) {
                if (!flights || !flights.length) return;
                var prices = flights.map(function(f) {
                    return parseFloat(
                        (f.price && (f.price.pricing_charge_total || f.price.grandTotal || f.price.total || f.price.base)) || 0
                    );
                }).filter(function(p) { return p > 0; });
                if (!prices.length) return;
                var minP = Math.floor(Math.min.apply(null, prices));
                var maxP = Math.ceil(Math.max.apply(null, prices));
                $('#amadex-price-min').attr('min', minP).attr('max', maxP).val(minP);
                $('#amadex-price-max').attr('min', minP).attr('max', maxP).val(maxP);
                $('#amadex-price-min-display').text('$' + minP);
                $('#amadex-price-max-display').text('$' + maxP);
            })(data.flights);
                            }
                            
                            // Initialize segment flights if not already done
                            if (!window.amadexSegmentFlights) {
                                window.amadexSegmentFlights = {};
                            }
                            
                            // Display first segment
                            if (typeof displaySegmentFlights === 'function') {
                                displaySegmentFlights(0, segments, data);
                            } else if (typeof window.displaySegmentFlights === 'function') {
                                window.displaySegmentFlights(0, segments, data);
                            }
                        } catch(e) {
                            console.error('Error displaying first segment:', e);
                        }
                    }
                }
            }
            return;
        }
        
        // Check for stored segments
        const segmentsStr = sessionStorage.getItem('amadex_multi_city_segments');
        const resultsStr = sessionStorage.getItem('amadex_search_results');
        
        let segments = [];
        
        // Get segments from sessionStorage
        if (segmentsStr) {
            try {
                segments = JSON.parse(segmentsStr);
                console.log('checkAndInitMultiCityTabs: Found stored segments', segments);
            } catch(e) {
                console.error('Error parsing stored segments:', e);
            }
        }
        
        // Check search data for segments
        if (segments.length === 0 && searchDataStr) {
            try {
                const searchData = JSON.parse(searchDataStr);
                if (searchData.segments && Array.isArray(searchData.segments) && searchData.segments.length > 1) {
                    segments = searchData.segments;
                    console.log('checkAndInitMultiCityTabs: Found segments in search data', segments);
                }
            } catch(e) {
                console.error('Error parsing search data:', e);
            }
        }
        
        // Check URL parameters for segments
        if (segments.length === 0) {
            const segmentsParam = urlParams.get('segments');
            if (segmentsParam) {
                    try {
                        // Decode URL-encoded segments (may be double-encoded)
                        let decodedSegments = decodeURIComponent(segmentsParam);
                        let segmentsFromUrl;
                        // Try parsing - might need another decode if double-encoded
                        try {
                            segmentsFromUrl = JSON.parse(decodedSegments);
                        } catch(e) {
                            // Try one more decode
                            decodedSegments = decodeURIComponent(decodedSegments);
                            segmentsFromUrl = JSON.parse(decodedSegments);
                        }
                        
                        if (segmentsFromUrl && segmentsFromUrl.length > 1) {
                            // Normalize segment codes
                            segments = segmentsFromUrl.map(seg => {
                                return {
                                    origin: extractIataCode(seg.origin || seg.originLocationCode || ''),
                                    originLocationCode: extractIataCode(seg.originLocationCode || seg.origin || ''),
                                    destination: extractIataCode(seg.destination || seg.destinationLocationCode || ''),
                                    destinationLocationCode: extractIataCode(seg.destinationLocationCode || seg.destination || ''),
                                    departure_date: seg.departure_date || seg.departureDate || seg.departure || ''
                                };
                            });
                            
                            console.log('checkAndInitMultiCityTabs: Found and normalized segments in URL', segments);
                            // Store normalized segments
                            sessionStorage.setItem('amadex_multi_city_segments', JSON.stringify(segments));
                        }
                    } catch(e) {
                        console.error('Error parsing segments from URL:', e);
                        console.error('Raw segments param:', segmentsParam);
                    }
                }
        }
        
        // Check modern search form on page for segments
        if (segments.length === 0) {
            const formSegments = collectSegmentsFromModernForm();
            if (formSegments && formSegments.length > 1) {
                segments = formSegments;
                console.log('checkAndInitMultiCityTabs: Found segments from form', segments);
                // Store for future use
                sessionStorage.setItem('amadex_multi_city_segments', JSON.stringify(segments));
            }
        }
        
        // If multi-city with segments, initialize tabs
        if (segments.length > 1) {
            // Get stored results or try to get from current page data
            let data = null;
            if (resultsStr) {
                try {
                    data = JSON.parse(resultsStr);
                } catch(e) {
                    console.error('Error parsing stored results:', e);
                }
            }
            
            // If no stored results, try to get from current page
            if (!data || !data.flights) {
                // Try to get from window variable or recreate from displayed flights
                if (window.amadexAllFlights && window.amadexAllFlights.length > 0) {
                    data = { flights: window.amadexAllFlights };
                } else if (currentSearchData && currentSearchData.flights) {
                    data = currentSearchData;
                } else {
                    // Try to collect flights from the page
                    const flightCards = $('.amadex-flight-card');
                    if (flightCards.length > 0) {
                        const flights = [];
                        flightCards.each(function() {
                            const flightData = $(this).data('flight-index');
                            if (flightData) {
                                // Try to get full flight data
                                const flightDataAttr = $(this).find('[data-flight-data]').first().data('flight-data');
                                if (flightDataAttr) {
                                    flights.push(flightDataAttr);
                                }
                            }
                        });
                        if (flights.length > 0) {
                            data = { flights: flights };
                            window.amadexAllFlights = flights;
                        }
                    }
                }
            }
            
            // Store flights globally for filtering + init price slider range
            if (data && data.flights) {
                window.amadexAllFlights = data.flights;

            // Initialize price slider min/max from actual flight prices
            (function initPriceSlider(flights) {
                if (!flights || !flights.length) return;
                var prices = flights.map(function(f) {
                    return parseFloat(
                        (f.price && (f.price.pricing_charge_total || f.price.grandTotal || f.price.total || f.price.base)) || 0
                    );
                }).filter(function(p) { return p > 0; });
                if (!prices.length) return;
                var minP = Math.floor(Math.min.apply(null, prices));
                var maxP = Math.ceil(Math.max.apply(null, prices));
                $('#amadex-price-min').attr('min', minP).attr('max', maxP).val(minP);
                $('#amadex-price-max').attr('min', minP).attr('max', maxP).val(maxP);
                $('#amadex-price-min-display').text('$' + minP);
                $('#amadex-price-max-display').text('$' + maxP);
            })(data.flights);
            }
            
            // Create tabs if we have segments (even without data, we'll show empty state)
            if (segments.length > 1) {
                console.log('checkAndInitMultiCityTabs: Creating tabs with segments', segments);
                createSegmentTabs(segments, data || { flights: [] });
            } else {
                // Not enough segments - remove any existing tabs
                $('.amadex-segment-tabs-container').remove();
                $('.amadex-segment-tabs-wrapper').remove();
                $('#amadex-segment-tabs').remove();
                console.log('checkAndInitMultiCityTabs: Not enough segments, removed tabs');
            }
        } else {
            // NOT multi-city - ensure tabs are removed (should never reach here since we check early, but just in case)
            $('.amadex-segment-tabs-container').remove();
            $('.amadex-segment-tabs-wrapper').remove();
            $('#amadex-segment-tabs').remove();
            console.log('checkAndInitMultiCityTabs: Not enough segments or not multi-city, removed tabs');
        }
    }

    /**
     * Initialize booking progress indicator
     */
    function initBookingProgress() {
        const $progressBars = $('.amadex-booking-progress');
        if (!$progressBars.length) return;
        
        $progressBars.each(function() {
            const $bar = $(this);
            let stage = $bar.data('booking-stage');
            if (!stage || BOOKING_STEPS_ORDER.indexOf(stage) === -1) {
                try {
                    const storedStage = sessionStorage.getItem('amadexBookingStage');
                    if (storedStage && BOOKING_STEPS_ORDER.indexOf(storedStage) !== -1) {
                        stage = storedStage;
                    } else {
                        stage = 'booking';
                    }
                } catch (e) {
                    stage = 'booking';
                }
            }
            const activeIndex = BOOKING_STEPS_ORDER.indexOf(stage);
            $bar.find('.booking-step').each(function() {
                const step = $(this).data('step');
                const stepIndex = BOOKING_STEPS_ORDER.indexOf(step);
                // When on passengers page, both booking and passengers should be active
                const isActive = (stepIndex === activeIndex) || (stage === 'passengers' && stepIndex <= activeIndex);
                $(this).toggleClass('is-active', isActive);
                $(this).toggleClass('is-complete', stepIndex < activeIndex && !isActive);
            });
        });

        $(document).on('amadexBookingStageChange', function(e, newStage) {
            $('.amadex-booking-progress').each(function() {
                const activeIndex = BOOKING_STEPS_ORDER.indexOf(newStage);
                $(this).find('.booking-step').each(function() {
                    const stepIndex = BOOKING_STEPS_ORDER.indexOf($(this).data('step'));
                    // When on passengers page, both booking and passengers should be active
                    const isActive = (stepIndex === activeIndex) || (newStage === 'passengers' && stepIndex <= activeIndex);
                    $(this).toggleClass('is-active', isActive);
                    $(this).toggleClass('is-complete', stepIndex < activeIndex && !isActive);
                });
            });
        });
    }

    /**
     * Get airline logo URL from Amadeus API airline codes
     * Uses IATA codes from Amadeus API response to fetch logos
     */
    function getAirlineLogo(airlineCode) {
        // Normalize airline code - ensure uppercase and remove whitespace
        if (!airlineCode || airlineCode === 'N/A' || airlineCode.trim() === '') {
            return getPlaceholderLogo('');
        }
        
        // Normalize to uppercase IATA code (standard format)
        const normalizedCode = airlineCode.trim().toUpperCase();
        
        // Primary source: Kiwi.com CDN (most reliable for Amadeus IATA airline codes)
        // This matches the airline codes from Amadeus API (IATA format)
        return `https://images.kiwi.com/airlines/64/${normalizedCode}.png`;
    }
    
    /**
     * Get fallback airline logo URL
     */
    function getAirlineLogoFallback(airlineCode) {
        if (!airlineCode || airlineCode === 'N/A' || airlineCode.trim() === '') {
            return getPlaceholderLogo('');
        }
        
        const normalizedCode = airlineCode.trim().toUpperCase();
        
        // Fallback to Google Flights CDN (highly reliable secondary source)
        return `https://www.gstatic.com/flights/airline_logos/70px/${normalizedCode}.png`;
    }
    
    /**
     * Get placeholder logo SVG
     */
    function getPlaceholderLogo(airlineCode) {
        const code = airlineCode || 'N/A';
        return 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64"%3E%3Crect fill="%23e0e0e0" width="64" height="64"/%3E%3Ctext x="50%25" y="50%25" dominant-baseline="middle" text-anchor="middle" font-family="Arial" font-size="12" fill="%23999"%3E' + encodeURIComponent(code) + '%3C/text%3E%3C/svg%3E';
    }
    
    /**
     * Get aircraft name from code (expanded mapping with fallback)
     */
    function getAircraftName(code) {
        if (!code) return '';
        const aircraftMap = {
            // Airbus models
            '319': 'Airbus A319',
            '320': 'Airbus A320',
            '321': 'Airbus A321',
            '32Q': 'Airbus A320neo', // Neo variant
            '32N': 'Airbus A320neo',
            '32S': 'Airbus A320neo Sharklet',
            '321N': 'Airbus A321neo',
            '330': 'Airbus A330',
            '332': 'Airbus A330-200',
            '333': 'Airbus A330-300',
            '350': 'Airbus A350',
            '351': 'Airbus A350-1000',
            '359': 'Airbus A350-900',
            '380': 'Airbus A380',
            // Boeing models
            '737': 'Boeing 737',
            '738': 'Boeing 737-800',
            '739': 'Boeing 737-900',
            '73H': 'Boeing 737-800',
            '73J': 'Boeing 737-900',
            '73M': 'Boeing 737 MAX',
            '7M8': 'Boeing 737 MAX 8',
            '7M9': 'Boeing 737 MAX 9',
            '767': 'Boeing 767',
            '777': 'Boeing 777',
            '787': 'Boeing 787',
            '788': 'Boeing 787-8',
            '789': 'Boeing 787-9',
            '78X': 'Boeing 787-10',
            // Embraer
            'E90': 'Embraer E190',
            'E95': 'Embraer E195',
            'E70': 'Embraer E170',
            'E75': 'Embraer E175',
            // Regional/Other
            'CRJ': 'Bombardier CRJ',
            'CR7': 'Bombardier CRJ-700',
            'CR9': 'Bombardier CRJ-900',
            'ATR': 'ATR 72',
            'AT4': 'ATR 42'
        };
        return aircraftMap[code] || code;
    }
    
    /**
     * Generate Code128 barcode SVG from flight data
     */
    function generateBarcode(data) {
        if (!data) data = '';
        const dataStr = String(data).toUpperCase().substring(0, 15); // Limit to 15 chars
        
        // Create barcode pattern based on character codes (visual representation)
        let pattern = '';
        let xPos = 0;
        const barWidth = 2;
        const spacing = 1;
        
        for (let i = 0; i < dataStr.length; i++) {
            const char = dataStr[i];
            const charCode = char.charCodeAt(0);
            // Create varying bar widths based on character code for realistic appearance
            const barHeight = 35 + (charCode % 5); // Vary height slightly
            const width = (charCode % 3) + 1; // Bar width 1-3
            
            pattern += `<rect x="${xPos}" y="${(40 - barHeight) / 2}" width="${width}" height="${barHeight}" fill="#000000"/>`;
            xPos += width + spacing;
        }
        
        return `<svg xmlns="http://www.w3.org/2000/svg" class="amadex-barcode-svg" viewBox="0 0 ${Math.max(40, xPos)} 40" preserveAspectRatio="none">
            ${pattern}
        </svg>`;
    }
    
    /**
     * Get airline name
     */
    function getAirlineName(airlineCode) {
        const airlines = {
            'AA': 'American Airlines',
            'UA': 'United Airlines',
            'DL': 'Delta Air Lines',
            'BA': 'British Airways',
            'LH': 'Lufthansa',
            'AF': 'Air France',
            'KL': 'KLM',
            'EK': 'Emirates',
            'QR': 'Qatar Airways',
            'EY': 'Etihad Airways',
            'SQ': 'Singapore Airlines',
            'CX': 'Cathay Pacific',
            'QF': 'Qantas',
            'AC': 'Air Canada',
            'NH': 'All Nippon Airways',
            'TK': 'Turkish Airlines',
            'SV': 'Saudia',
            'AI': 'Air India',
            'UL': 'SriLankan Airlines',
            'WY': 'Oman Air',
            'GF': 'Gulf Air',
            'XY': 'Flynas',
            'KU': 'Kuwait Airways',
            'HH': 'Hahn Air'
        };
        
        return airlines[airlineCode] || airlineCode;
    }

    /**
     * Create flight element - New horizontal card design
     */
    /**
     * Return the boarding pass header HTML string for use at the top of each flight card.
     * Optional: include departure/return dates in the header.
     */
    function getBoardingPassHeaderHtml(includeDates) {
        const urlParams = new URLSearchParams(window.location.search);
        const departDate = urlParams.get('depart_date') || urlParams.get('departure_date') || '';
        const returnDate = urlParams.get('return_date') || '';
        let departLabel = '';
        let returnLabel = '';
        if (includeDates) {
            if (departDate) {
                try {
                    const d = new Date(departDate);
                    departLabel = d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: '2-digit' });
                } catch (e) {
                    departLabel = departDate;
                }
            }
            if (returnDate) {
                try {
                    const r = new Date(returnDate);
                    returnLabel = r.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: '2-digit' });
                } catch (e) {
                    returnLabel = returnDate;
                }
            }
        }
        const datesHtml = (departLabel || returnLabel)
            ? `<div class="amadex-results-header-dates">${departLabel ? '<span>Departure • ' + departLabel + '</span>' : ''}${returnLabel ? '<span>Return • ' + returnLabel + '</span>' : ''}</div>`
            : '';
        return `
            <div class="amadex-boarding-pass-header">
                <div class="amadex-header-left">
                 <span class="amadex-ticket-svg">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18.615" height="11" viewBox="0 0 18.615 11" class="amadex-header-plane-icon">
                        <g id="Group_30144" data-name="Group 30144" transform="translate(-1 -9)">
                            <g id="Group_30143" data-name="Group 30143" transform="translate(1 9)">
                                <path id="Path_9878" data-name="Path 9878" d="M1,9.846A.846.846,0,0,1,1.846,9H18.769a.846.846,0,0,1,.846.846v2.538a.846.846,0,0,1-.846.846,1.269,1.269,0,1,0,0,2.538.846.846,0,0,1,.846.846v2.538a.846.846,0,0,1-.846.846H1.846A.846.846,0,0,1,1,19.154V16.615a.846.846,0,0,1,.846-.846,1.269,1.269,0,1,0,0-2.538A.846.846,0,0,1,1,12.385Zm7.292,5.5h1.827l-.969,1.7,1.469.839,1.449-2.535h1.625V13.654H12.068l-1.449-2.535-1.469.839.969,1.7H8.292L7.68,12.429l-1.513.756L6.823,14.5l-.657,1.314,1.513.756Z" transform="translate(-1 -9)" fill="#0e7d3f" fill-rule="evenodd"/>
                            </g>
                        </g>
                    </svg>
                    </span>
                    <span class="amadex-boarding-pass-text">Travelay Pass <span class="amadex-header-subtitle">( Suggest Better Price on Call )</span></span>
                </div>
                <div class="amadex-header-right">
                    ${datesHtml}
                    <span class="amadex-verified-flight-text">
                    VERIFIED FLIGHT <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10">
  <g id="Group_30149" data-name="Group 30149" transform="translate(-2 -2)">
    <path id="_8-Help" data-name="8-Help" d="M7,12a5,5,0,1,1,5-5,5,5,0,0,1-5,5ZM7,2.714A4.286,4.286,0,1,0,11.286,7,4.286,4.286,0,0,0,7,2.714Zm0,2.5A.714.714,0,1,1,7.714,4.5.714.714,0,0,1,7,5.214Zm0,.714a.714.714,0,0,0-.714.714V9.5a.714.714,0,1,0,1.429,0V6.643A.714.714,0,0,0,7,5.929ZM8.429,9.5" fill="#fff"/>
  </g>
</svg>
                    </span>
                </div>
            </div>
        `;
    }

    /**
     * Create the results header as a jQuery element (legacy: used when a single header was at top of list).
     * Now each flight card has its own header via getBoardingPassHeaderHtml().
     */
    function createResultsHeaderElement() {
        return $(getBoardingPassHeaderHtml(true));
    }

    /**
     * Generate Viewers Badge HTML
     * Creates a social proof badge showing random viewer count
     */
    function generateViewersBadge() {
        // Check if badge is enabled
        if (!amadexSettings || !amadexSettings.viewersBadge || !amadexSettings.viewersBadge.enabled) {
            return '';
        }
        
        const badgeSettings = amadexSettings.viewersBadge;
        const min = parseInt(badgeSettings.min) || 12;
        const max = parseInt(badgeSettings.max) || 89;
        const text = badgeSettings.text || 'people exploring';
        const position = badgeSettings.position || 'top-left';
        
        // Ensure max is greater than min
        const validMax = Math.max(min + 1, max);
        
        // Generate random number between min and max
        const viewerCount = Math.floor(Math.random() * (validMax - min + 1)) + min;
        
        // Position classes
        const positionClass = `amadex-viewers-badge-${position}`;
        
        // Create badge HTML
        const badgeHtml = `
            <div class="amadex-viewers-badge ${positionClass}" data-viewers="${viewerCount}">
                <span class="amadex-viewers-badge-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14.099" height="8.405" viewBox="0 0 14.099 8.405">
  <g id="Group_30146" data-name="Group 30146" transform="translate(0 -98.725)">
    <path id="Path_9881" data-name="Path 9881" d="M7.05,98.725A8.8,8.8,0,0,0,.11,102.593a.558.558,0,0,0,0,.666A8.791,8.791,0,0,0,7.05,107.13a8.8,8.8,0,0,0,6.939-3.868.558.558,0,0,0,0-.666A8.791,8.791,0,0,0,7.05,98.725Zm.193,7.161a2.966,2.966,0,1,1,2.766-2.766,2.967,2.967,0,0,1-2.766,2.766Zm-.089-1.367a1.6,1.6,0,1,1,1.491-1.491,1.594,1.594,0,0,1-1.491,1.491Z" transform="translate(0)" fill="#fff"></path>
  </g>
</svg>
                </span>
                <span class="amadex-viewers-badge-count">${viewerCount}</span>
                <span class="amadex-viewers-badge-text">${text}</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10">
  <g id="Group_30149" data-name="Group 30149" transform="translate(-2 -2)">
    <path id="_8-Help" data-name="8-Help" d="M7,12a5,5,0,1,1,5-5,5,5,0,0,1-5,5ZM7,2.714A4.286,4.286,0,1,0,11.286,7,4.286,4.286,0,0,0,7,2.714Zm0,2.5A.714.714,0,1,1,7.714,4.5.714.714,0,0,1,7,5.214Zm0,.714a.714.714,0,0,0-.714.714V9.5a.714.714,0,1,0,1.429,0V6.643A.714.714,0,0,0,7,5.929ZM8.429,9.5" fill="#fff"/>
  </g>
</svg>
            </div>
        `;
        
        return badgeHtml;
    }
    
    function createFlightElement(flight, index) {
        const airlineCode = flight.validating_airline_codes ? flight.validating_airline_codes[0] : 'N/A';
        
        // Use same price source as Flight Details modal (Total Trip Cost): grandTotal || total
        const priceFromApi = parseFloat(flight.price?.grandTotal || flight.price?.total || 0) || 0;
        // Apply price markup based on settings
        // Pass flight data to check if Pricing Rules Engine is enabled
        const adjustedPrice = calculatePriceWithMarkup(priceFromApi, airlineCode, flight);
        const totalPriceValue = Number(adjustedPrice) || 0;
        
        // Store original total price and formatted text for currency conversion
        const originalPrice = priceFromApi || totalPriceValue;
        const originalCurrency = flight.price.currency || 'USD';
        
        // Get passenger count from stored search data
        const searchData = getStoredSearchData();
        const adultsCount = parseInt(searchData.adults || 1, 10);
        const childrenCount = parseInt(searchData.children || 0, 10);
        const infantsCount = parseInt(searchData.infants || 0, 10);
        const infantsLapCount = parseInt(searchData.infants_lap || 0, 10);
        const infantsSeatCount = parseInt(searchData.infants_seat || 0, 10);
        
        // Parse travelerPricings to extract individual prices by traveler type
        // Try both camelCase and snake_case property names
        const travelerPricings = flight.travelerPricings || flight.traveler_pricings || [];
        let perPersonPrice = adultsCount > 0 ? totalPriceValue / adultsCount : totalPriceValue;
        
        if (travelerPricings && travelerPricings.length > 0) {
            // Initialize price totals by traveler type
            let adultTotal = 0;
            let childTotal = 0;
            let infantLapTotal = 0;
            let infantSeatTotal = 0;
            let adultPricingCount = 0;
            let childPricingCount = 0;
            let infantLapPricingCount = 0;
            let infantSeatPricingCount = 0;
            
            // Parse each traveler pricing entry
            travelerPricings.forEach(function(travelerPricing) {
                const travelerType = (travelerPricing.travelerType || travelerPricing.traveler_type || '').toUpperCase();
                const priceObj = travelerPricing.price || {};
                const travelerPrice = parseFloat(priceObj.total || 0);
                
                // Count and sum prices by traveler type
                if (travelerType === 'ADULT' || travelerType === 'ADT') {
                    adultTotal += travelerPrice;
                    adultPricingCount += 1;
                } else if (travelerType === 'CHILD' || travelerType === 'CHD') {
                    childTotal += travelerPrice;
                    childPricingCount += 1;
                } else if (travelerType === 'HELD_INFANT' || travelerType === 'INF' || travelerType === 'INFANT') {
                    infantLapTotal += travelerPrice;
                    infantLapPricingCount += 1;
                } else if (travelerType === 'SEATED_INFANT' || travelerType === 'INS') {
                    infantSeatTotal += travelerPrice;
                    infantSeatPricingCount += 1;
                }
            });
            
            // Check if Pricing Rules Engine is enabled (discount already applied to price.total)
            const rulesEngineEnabled = flight.price && (flight.price.pricing_snapshot || flight.price.pricing_charge_total);
            
            if (rulesEngineEnabled) {
                // Pricing Rules Engine enabled - use discounted price.total (P_display)
                // price.total is already discounted, so divide by passenger count to get per-person price
                // Do NOT use travelerPricings (they contain original, undiscounted prices)
                if (adultsCount > 0) {
                    perPersonPrice = totalPriceValue / adultsCount;
                } else if (childrenCount > 0) {
                    perPersonPrice = totalPriceValue / childrenCount;
                } else {
                    const totalPassengers = adultsCount + childrenCount + infantsLapCount + infantsSeatCount;
                    perPersonPrice = totalPassengers > 0 ? totalPriceValue / totalPassengers : totalPriceValue;
                }
            } else {
                // Legacy pricing - use travelerPricings (original logic)
                // Priority: Use adult price for display (since label says "per person" which typically means adult)
                // If adults exist, show adult price; otherwise show average or first available
                if (adultPricingCount > 0 && adultsCount > 0) {
                    // Calculate per-adult price from travelerPricings data
                    const perAdultFromPricing = adultTotal / adultPricingCount;
                    // Apply markup to the per-adult price from travelerPricings
                    // Pass flight data to check if Pricing Rules Engine is enabled
                    const adjustedPerAdult = calculatePriceWithMarkup(perAdultFromPricing, airlineCode, flight);
                    perPersonPrice = adjustedPerAdult;
                } else if (childPricingCount > 0 && childrenCount > 0) {
                    // If no adults but children exist, use child price
                    const perChildFromPricing = childTotal / childPricingCount;
                    const adjustedPerChild = calculatePriceWithMarkup(perChildFromPricing, airlineCode, flight);
                    perPersonPrice = adjustedPerChild;
                } else {
                    // Fallback to original calculation (divide total by passenger count)
                    const totalPassengers = adultsCount + childrenCount + infantsLapCount + infantsSeatCount;
                    perPersonPrice = totalPassengers > 0 ? totalPriceValue / totalPassengers : totalPriceValue;
                }
            }
        } else {
            // No travelerPricings available - use original fallback logic
            // Calculate per-person price (divide total by number of adults)
            perPersonPrice = adultsCount > 0 ? totalPriceValue / adultsCount : totalPriceValue;
        }
        
        // const priceValue = perPersonPrice; // Marked-up per-person (for data attributes / booking)
        // // Original (pre-markup) per-person for display in flight list
        // const originalPerPerson = adultsCount > 0 ? originalPrice / adultsCount : originalPrice;
        
        // // Show original price in flight list (formatted)
        // const originalFormattedPrice = formatPrice(originalPerPerson, originalCurrency);
        
        // const price = originalFormattedPrice;
        const priceValue = perPersonPrice; // Marked-up per-person (for data attributes / booking)

        // For display: use perPersonPrice if calculated from travelerPricings (more accurate)
        // otherwise fall back to total / adults
        let displayPerPerson;
        if (travelerPricings && travelerPricings.length > 0) {
            displayPerPerson = perPersonPrice;
        } else {
            displayPerPerson = adultsCount > 0 ? originalPrice / adultsCount : originalPrice;
        }

        // Show per-person price in flight list (formatted)
        const originalFormattedPrice = formatPrice(displayPerPerson, originalCurrency);

        const price = originalFormattedPrice;
        const airlineLogo = getAirlineLogo(airlineCode);
        const airlineName = getAirlineName(airlineCode);
        
        // Get main itinerary (outbound)
        const mainItinerary = flight.itineraries && flight.itineraries[0] ? flight.itineraries[0] : null;
        if (!mainItinerary) return $('<div></div>');
        
        const segments = mainItinerary.segments || [];
        const firstSegment = segments[0];
        const lastSegment = segments[segments.length - 1];
        
        const depTime = formatTime(new Date(firstSegment.departure.at));
        const arrTime = formatTime(new Date(lastSegment.arrival.at));
        const depCode = firstSegment.departure.iataCode || firstSegment.departure.iata_code || 'N/A';
        const arrCode = lastSegment.arrival.iataCode || lastSegment.arrival.iata_code || 'N/A';
        const depDate = formatDate(new Date(firstSegment.departure.at));
        const arrDate = formatDate(new Date(lastSegment.arrival.at));
        
        // Calculate layover info
        const stops = segments.length - 1;
        const durationMinutes = getItineraryDurationMinutes(mainItinerary);
        let layoverHtml = '';
        if (stops > 0) {
            const stopCity = segments[0].arrival.iataCode || segments[0].arrival.iata_code || '';
            const layoverDuration = calculateLayover(segments[0], segments[1]);
            layoverHtml = `
                <div class="amadex-flight-layover">
                    <div class="amadex-layover-dot"></div>
                    <div class="amadex-layover-info">
                        <span class="amadex-layover-city">${stopCity}</span>
                        <span class="amadex-layover-time">${layoverDuration}</span>
                    </div>
                </div>
            `;
        }
        

        // Check for meal - use has_meal flag from API if available
        // Only show badge if airline provides free meal
        const hasMeal = (flight.has_meal === true || flight.hasMeal === true || flight.has_meal === 1 || flight.hasMeal === 1);
        const mealBadge = hasMeal ? `<div class="amadex-flight-badge">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="badge-icon">
                            <path d="M20 10c0 1.1-.9 2-2 2h-2v7c0 .55-.45 1-1 1s-1-.45-1-1v-7H6c-1.1 0-2-.9-2-2V8c0-1.1.9-2 2-2h12c1.1 0 2 .9 2 2v2zm-2.5-4H17V4c0-.55-.45-1-1-1s-1 .45-1 1v2h-1.5V4c0-.55-.45-1-1-1s-1 .45-1 1v2H11V4c0-.55-.45-1-1-1s-1 .45-1 1v2H7.5V4c0-.55-.45-1-1-1s-1 .45-1 1v2H4c-1.1 0-2 .9-2 2v2c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2z" fill="currentColor"/>
                            <path d="M6 14h12v2H6z" fill="currentColor"/>
                        </svg>
                        Free Meal
                    </div>` : '';
        
        // Check for baggage - use has_baggage flag from API if available
        const hasBaggage = flight.has_baggage === true || flight.hasBaggage === true;

        const outboundSummary = buildLegSummary(mainItinerary, 'Departure', hasBaggage, airlineLogo, airlineName, flight);
        const returnItinerary = flight.itineraries && flight.itineraries[1] ? flight.itineraries[1] : null;
        const returnSummary = returnItinerary ? buildLegSummary(returnItinerary, 'Return', hasBaggage, airlineLogo, airlineName, flight) : null;
        const cabinLabel = getCabinDisplayName(getCabinCodeFromFlight(flight));
        const seatsLeft = typeof flight.number_of_bookable_seats === 'number' ? flight.number_of_bookable_seats : (typeof flight.numberOfBookableSeats === 'number' ? flight.numberOfBookableSeats : null);
        
        // Extract branded fare name (from first segment if available)
        let brandedFareName = null;
        if (flight.branded_fares && Array.isArray(flight.branded_fares) && flight.branded_fares.length > 0) {
            const firstBrandedFare = Object.values(flight.branded_fares)[0];
            if (firstBrandedFare) {
                brandedFareName = firstBrandedFare;
            }
        }
        
        // Build branded fare badge (show if available and not just generic economy)
        const brandedFareBadge = brandedFareName && brandedFareName.toUpperCase() !== 'ECONOMY' && brandedFareName.toUpperCase() !== 'STANDARD' 
            ? `<div class="amadex-flight-badge amadex-branded-fare-badge">${brandedFareName}</div>` : '';
        
        // Extract parsed fare rules (if available from Flight Offers Price API)
        const parsedFareRules = flight.parsed_fare_rules || {};
        const cancellationPolicy = parsedFareRules.cancellation || {};
        const exchangePolicy = parsedFareRules.exchange || parsedFareRules.change || {};
        
        // Build cancellation policy badge
        let cancellationBadge = '';
        if (cancellationPolicy.allowed !== undefined) {
            if (!cancellationPolicy.allowed) {
                cancellationBadge = `<div class="amadex-policy-badge amadex-policy-badge-no-cancel" title="Cancellation not allowed">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" fill="currentColor"/>
                    </svg>
                    <span>No Cancel</span>
                </div>`;
            } else if (cancellationPolicy.fee === null || cancellationPolicy.fee === 0) {
                cancellationBadge = `<div class="amadex-policy-badge amadex-policy-badge-free-cancel" title="Free cancellation">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" fill="currentColor"/>
                    </svg>
                    <span>Free Cancel</span>
                </div>`;
            } else {
                const feeText = cancellationPolicy.currency ? `${cancellationPolicy.currency} ${cancellationPolicy.fee}` : `$${cancellationPolicy.fee}`;
                cancellationBadge = `<div class="amadex-policy-badge amadex-policy-badge-fee-cancel" title="Cancellation fee: ${feeText}">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="currentColor"/>
                    </svg>
                    <span>Cancel Fee</span>
                </div>`;
            }
        }
        
        // Build change policy badge
        let changeBadge = '';
        if (exchangePolicy.allowed !== undefined) {
            if (!exchangePolicy.allowed) {
                changeBadge = `<div class="amadex-policy-badge amadex-policy-badge-no-change" title="Changes not allowed">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" fill="currentColor"/>
                    </svg>
                    <span>No Changes</span>
                </div>`;
            } else {
                changeBadge = `<div class="amadex-policy-badge amadex-policy-badge-change-allowed" title="Changes allowed${exchangePolicy.fee > 0 ? ' with fee' : ''}">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46C19.54 15.03 20 13.57 20 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74C4.46 8.97 4 10.43 4 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z" fill="currentColor"/>
                    </svg>
                    <span>Changes${exchangePolicy.fee > 0 ? ' Fee' : ''}</span>
                </div>`;
            }
        }
        const seatBadge = seatsLeft && seatsLeft > 0 ? `<div class="amadex-flight-seats ${seatsLeft <= 7 ? 'is-low' : ''}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12.79" height="17.487" viewBox="0 0 12.79 17.487">
  <g id="Group_216" data-name="Group 216" transform="translate(-17.202 -0.013)">
    <circle id="Ellipse_22" data-name="Ellipse 22" cx="1.14" cy="1.14" r="1.14" transform="translate(19.189 13.363) rotate(-13.34)" fill="#b80000"/>
    <path id="Path_102" data-name="Path 102" d="M20.559,12.8a1.369,1.369,0,0,1,1.231.724,1.745,1.745,0,0,0,.633-1.278A2.788,2.788,0,0,0,22,10.635a13.719,13.719,0,0,1-.87-1.605A12.257,12.257,0,0,1,20.2,6.072c-.033-.18-.068-.354-.107-.518a1.288,1.288,0,0,0-1.256-1c-.059,0-.112.005-.169.011h-.022a1.531,1.531,0,0,0-.168.036V4.042a1.259,1.259,0,0,0,1.23-1.092l.2-1.517a1.258,1.258,0,1,0-2.494-.33l-.2,1.519a1.259,1.259,0,0,0,.991,1.4V4.7a1.5,1.5,0,0,0-.779,1.8l2.084,6.772a1.392,1.392,0,0,1,1.05-.469Zm9.4.81a1.545,1.545,0,0,0-.608-1.056,1.577,1.577,0,0,0-1.182-.317l-5.653.754a1.921,1.921,0,0,1-.616.793,1.369,1.369,0,0,1,.068.429,1.415,1.415,0,0,1-1.413,1.413c-.046,0-.092,0-.137-.007V16.2h8.521S30.194,15.669,29.963,13.614Zm-9.541,2.861v.19a.836.836,0,0,0,.835.835h6.948a.835.835,0,0,0,.834-.835v-.2l-.012.008Z" transform="translate(0 0)" fill="#b80000"/>
  </g>
</svg>
                        <span>${seatsLeft} Seats Left</span>
                    </div>` : '';
        
        const fromResultsMeta = currentSearchData && currentSearchData._searchInfo && currentSearchData._searchInfo.isOneWay;
        const isOneWayCard = isOneWaySearch || fromResultsMeta || !returnSummary;
        const cardClasses = ['amadex-flight-card'];
        if (isOneWayCard) {
            cardClasses.push('is-oneway-card');
        }
        const showReturnSummary = !isOneWayCard && returnSummary;

        const flightDataAttr = JSON.stringify(flight);
        const bookingUrl = (typeof AmadexConfig !== 'undefined' && AmadexConfig.bookingPageUrl) ? AmadexConfig.bookingPageUrl : '/flight-booking/';
        
        // Generate viewers badge HTML
        const viewersBadgeHtml = generateViewersBadge();

        const boardingPassHeaderHtml = getBoardingPassHeaderHtml(false);
        const $flightCard = $(`
            <div class="${cardClasses.join(' ')}" data-flight-index="${index}" data-price="${priceValue}" data-total-price="${totalPriceValue}" data-duration="${durationMinutes}" data-stops="${stops}">
                ${boardingPassHeaderHtml}
                <div class="amadex-boarding-pass-content">
                    ${viewersBadgeHtml}
                    <div class="amadex-flight-card-main">
                    <div class="amadex-flight-card-info">
                        <div class="amadex-flight-legs">
                            ${renderLegBlock(outboundSummary)}
                            ${showReturnSummary ? renderLegBlock(returnSummary) : ''}
                        </div>
                        <div class="amadex-flight-info-footer">
                            <button type="button" class="amadex-flight-detail-link amadex-select-flight-btn" data-flight-data='${flightDataAttr}'>
                                Flight Detail <span aria-hidden="true">›</span>
                            </button>
                        </div>
                        </div>
                    <div class="amadex-perforated-line"></div>
                    <div class="amadex-flight-card-price">
                       <div class="amadex-ticketinfo">
                    <div class="amadex-flight-card-header">
                    ${seatBadge}
                            </div>
                    <div class="amadex-flight-class-wrapper">
                        <div class="amadex-flight-class">${cabinLabel}</div>
                        ${brandedFareBadge}
                    </div>
                    <div class="amadex-flight-policy-badges">
                        ${cancellationBadge}
                        ${changeBadge}
                    </div>
        ` + (hasBaggage ? `<div class="amadex-flight-price-notes">Includes baggage</div>` : '') + `
                        </div>
        
<div class="amadex-flight-price-wrapper">
                            <span class="amadex-flight-price" 
                                  data-original-price="${originalPrice}" 
                                  data-price="${priceValue}" 
                                  data-total-price="${totalPriceValue}"
                                  data-original-text="${originalFormattedPrice}" 
                                  data-currency="${flight.price.currency || 'USD'}">${originalFormattedPrice}</span>
                            <span class="amadex-flight-price-note">per person</span>
                            </div>
                        <a href="${bookingUrl}" class="amadex-book-now-btn" data-flight-data='${flightDataAttr}'>Book Now</a>
                        <button class="amadex-price-box-select amadex-select-flight-btn" data-flight-data='${flightDataAttr}'>
                            Review Details
                            </button>
                        <a href="tel:+18777210410" class="amadex-price-box-call amadex-call-btn" data-flight-data='${flightDataAttr}'>
                            <span class="amadex-phone-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 14 14">
  <g id="Group_25" data-name="Group 25" transform="translate(-0.006)">
    <path id="Path_101" data-name="Path 101" d="M10.65,9.262a.981.981,0,0,0-1.483,0c-.347.344-.693.688-1.034,1.037a.2.2,0,0,1-.286.052c-.224-.122-.463-.221-.679-.355a10.755,10.755,0,0,1-2.593-2.36,6.143,6.143,0,0,1-.929-1.489A.213.213,0,0,1,3.7,5.873c.347-.335.685-.679,1.025-1.023a.985.985,0,0,0,0-1.518c-.271-.274-.542-.542-.813-.816s-.556-.562-.839-.839a.987.987,0,0,0-1.483,0c-.35.344-.685.7-1.04,1.034a1.688,1.688,0,0,0-.53,1.139A4.826,4.826,0,0,0,.389,5.932a12.622,12.622,0,0,0,2.24,3.732,13.864,13.864,0,0,0,4.591,3.592A6.64,6.64,0,0,0,9.764,14a1.864,1.864,0,0,0,1.6-.609c.3-.332.632-.635.947-.953a.991.991,0,0,0,.006-1.509q-.83-.835-1.666-1.664ZM10.093,6.94l1.075-.184A4.825,4.825,0,0,0,7.087,2.8L6.935,3.878A3.729,3.729,0,0,1,10.093,6.94Zm1.681-4.673A7.927,7.927,0,0,0,7.229,0L7.078,1.081a6.917,6.917,0,0,1,5.853,5.672l1.075-.184a7.985,7.985,0,0,0-2.232-4.3Z" transform="translate(0)" fill="#fff"/>
  </g>
</svg></span>
                            +1-877-721-0410
                        </a>
                    </div>
                    </div>
                </div>
            </div>
        `);
        
        return $flightCard;
    }
    
    /**
     * Calculate layover duration
     */
    function calculateLayover(seg1, seg2) {
        if (!seg1 || !seg2) return '';
        const arr = new Date(seg1.arrival.at);
        const dep = new Date(seg2.departure.at);
        const diff = (dep - arr) / 1000 / 60; // minutes
        const hours = Math.floor(diff / 60);
        const mins = Math.floor(diff % 60);
        return `${hours}h ${mins}m`;
    }
    
    /**
     * Calculate total duration from segments
     */
    function calculateTotalDuration(segments) {
        if (!segments || segments.length === 0) return '0h 0m';
        const firstDep = new Date(segments[0].departure.at);
        const lastArr = new Date(segments[segments.length - 1].arrival.at);
        const diff = (lastArr - firstDep) / 1000 / 60; // minutes
        const hours = Math.floor(diff / 60);
        const mins = Math.floor(diff % 60);
        return `${hours}h ${mins}m`;
    }

    function getItineraryDurationMinutes(itinerary) {
        if (!itinerary || !itinerary.segments || !itinerary.segments.length) return 0;
        const firstDep = new Date(itinerary.segments[0].departure.at);
        const lastArr = new Date(itinerary.segments[itinerary.segments.length - 1].arrival.at);
        const diff = (lastArr - firstDep) / 1000 / 60;
        return Math.max(0, Math.round(diff));
    }

    function updateResultsAvailableCount(count) {
        $('#amadex-total-flights').text(count);
        $('#amadex-results-available-count').text(count);
    }

    function buildLegSummary(itinerary, label, hasBaggage, airlineLogo, airlineName, flightData) {
        if (!itinerary || !itinerary.segments || !itinerary.segments.length) return null;
        const segments = itinerary.segments;
        const firstSegment = segments[0];
        const lastSegment = segments[segments.length - 1];
        if (!firstSegment || !lastSegment) return null;
        
        const depDate = new Date(firstSegment.departure.at);
        const arrDate = new Date(lastSegment.arrival.at);
        const depCode = firstSegment.departure.iataCode || firstSegment.departure.iata_code || 'N/A';
        const arrCode = lastSegment.arrival.iataCode || lastSegment.arrival.iata_code || 'N/A';
        const carrier = firstSegment.carrierCode || firstSegment.marketingCarrier || '';
        const airlineDisplay = carrier ? getAirlineName(carrier) : airlineName || '';
        const airlineLogoUrl = airlineLogo || getAirlineLogo(carrier);
        
        // Extract aircraft information (from first segment)
        let aircraftInfo = '';
        let aircraftCode = '';
        if (firstSegment.aircraft) {
            aircraftCode = firstSegment.aircraft.code || firstSegment.aircraft || '';
            aircraftInfo = getAircraftName(aircraftCode);
        }
        
        // Extract operating carrier if different from marketing carrier
        let operatingCarrier = '';
        if (firstSegment.operating && firstSegment.operating.carrierCode && 
            firstSegment.operating.carrierCode !== carrier) {
            operatingCarrier = getAirlineName(firstSegment.operating.carrierCode);
        } else if (firstSegment.operating_carrier_code && firstSegment.operating_carrier_code !== carrier) {
            operatingCarrier = getAirlineName(firstSegment.operating_carrier_code);
        }
        
        // Extract detailed baggage information from flight data
        let baggageDetails = '';
        if (flightData && flightData.detailed_baggage && flightData.detailed_baggage.length > 0) {
            const checkedBags = flightData.detailed_baggage.filter(b => b.type === 'checked');
            const carryOnBags = flightData.detailed_baggage.filter(b => b.type === 'carry_on');
            
            const bagParts = [];
            if (checkedBags.length > 0) {
                const firstChecked = checkedBags[0];
                const weightInfo = firstChecked.weight ? ` (${firstChecked.weight}${firstChecked.weight_unit || 'KG'})` : '';
                bagParts.push(`${firstChecked.quantity}x Checked${weightInfo}`);
            }
            if (carryOnBags.length > 0) {
                const firstCarryOn = carryOnBags[0];
                bagParts.push(`${firstCarryOn.quantity}x Carry-on`);
            }
            baggageDetails = bagParts.join(', ') || 'Baggage included';
        } else if (hasBaggage) {
            baggageDetails = 'Baggage included';
        } else {
            baggageDetails = 'No baggage included';
        }
        const stops = segments.length - 1;
        // Use enhanced layover data from backend if available
        let layovers = [];
        if (itinerary.layovers && Array.isArray(itinerary.layovers) && itinerary.layovers.length > 0) {
            // Use backend-calculated layover data with plane/terminal change info
            layovers = itinerary.layovers.map((layover, idx) => ({
                index: layover.segment_index !== undefined ? layover.segment_index + 1 : idx + 1,
                duration: layover.duration_formatted || calculateLayover(segments[layover.segment_index || idx], segments[(layover.segment_index || idx) + 1]) || '—',
                duration_minutes: layover.duration_minutes || 0,
                airport: layover.airport || (segments[layover.segment_index || idx]?.arrival?.iata_code || 'N/A'),
                city: layover.airport || (segments[layover.segment_index || idx]?.arrival?.iata_code || 'N/A'),
                terminal_change: layover.terminal_change || false,
                plane_change: layover.plane_change || false,
                arrival_terminal: layover.arrival_terminal || '',
                departure_terminal: layover.departure_terminal || ''
            }));
        } else if (stops > 0) {
            // Fallback to client-side calculation if backend data not available
            for (let i = 0; i < segments.length - 1; i++) {
                const currentSeg = segments[i];
                const nextSeg = segments[i + 1];
                const layoverDuration = calculateLayover(currentSeg, nextSeg);
                const layoverAirport = currentSeg.arrival || {};
                const layoverCode = layoverAirport.iataCode || layoverAirport.iata_code || layoverAirport.code || 'N/A';
                const layoverCity = layoverAirport.cityCode || layoverAirport.cityName || layoverCode;
                
                // Check for plane change (different aircraft)
                const planeChange = (currentSeg.aircraft && nextSeg.aircraft) && (currentSeg.aircraft !== nextSeg.aircraft);
                // Check for terminal change
                const terminalChange = (currentSeg.arrival?.terminal && nextSeg.departure?.terminal) && 
                                     (currentSeg.arrival.terminal !== nextSeg.departure.terminal);
                
                layovers.push({
                    index: i + 1,
                    duration: layoverDuration || '—',
                    airport: layoverCode,
                    city: layoverCity,
                    terminal_change: terminalChange,
                    plane_change: planeChange,
                    arrival_terminal: currentSeg.arrival?.terminal || '',
                    departure_terminal: nextSeg.departure?.terminal || ''
                });
            }
        }
        
        return {
            label,
            dateLabel: depDate.toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: '2-digit', weekday: 'long' }),
            depTime: formatTime(depDate),
            depCode,
            arrTime: formatTime(arrDate),
            arrCode,
            duration: itinerary.duration ? formatIsoDuration(itinerary.duration) : calculateTotalDuration(segments),
            stopsLabel: stops === 0 ? 'Non Stop' : `${stops} Stop${stops > 1 ? 's' : ''}`,
            airlineName: airlineDisplay,
            airlineLogo: airlineLogoUrl,
            hasBaggage: hasBaggage || false,
            baggageDetails: baggageDetails,
            aircraftInfo: aircraftInfo,
            aircraftCode: aircraftCode,
            operatingCarrier: operatingCarrier,
            layovers,
            itinerarySegments: segments // Include segments for layover position calculation
        };
    }

    function renderLegBlock(summary) {
        if (!summary) return '';
        const layoverDetails = summary.layovers && summary.layovers.length ? `
            <div class="amadex-layover-trigger" tabindex="0">
                ${summary.layovers.length === 1 ? '1 Stop' : `${summary.layovers.length} Stops`}
                <div class="amadex-layover-tooltip" role="tooltip">
                    <div class="amadex-layover-tooltip-body">
                        ${summary.layovers.map(item => {
                            const planeChangeBadge = item.plane_change ? '<span class="amadex-layover-badge plane-change" title="Plane Change"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" class="amadex-layover-badge-icon"><path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z" fill="currentColor"/></svg></span>' : '';
                            const terminalChangeBadge = item.terminal_change ? '<span class="amadex-layover-badge terminal-change" title="Terminal Change">🏢</span>' : '';
                            const terminalInfo = (item.arrival_terminal && item.departure_terminal && item.arrival_terminal !== item.departure_terminal) 
                                ? ` <span class="amadex-layover-terminal">Term ${item.arrival_terminal} → Term ${item.departure_terminal}</span>` 
                                : (item.arrival_terminal || item.departure_terminal ? ` <span class="amadex-layover-terminal">Term ${item.arrival_terminal || item.departure_terminal}</span>` : '');
                            
                            return `
                            <div class="amadex-layover-row ${item.plane_change ? 'has-plane-change' : ''} ${item.terminal_change ? 'has-terminal-change' : ''}">
                                <div class="amadex-layover-row-header">
                                    <span class="amadex-layover-index">Layover (${item.index})</span>
                                    ${planeChangeBadge}${terminalChangeBadge}
                                </div>
                                <div class="amadex-layover-row-details">
                                    <span class="amadex-layover-duration">${item.duration}</span>
                                    <span class="amadex-layover-city">${item.city} (${item.airport})${terminalInfo}</span>
                                </div>
                            </div>
                        `;
                        }).join('')}
                    </div>
                </div>
            </div>
        ` : `<div class="amadex-layover-trigger non-stop">Non Stop</div>`;
        // Calculate layover positions based on actual timing (proportional to journey time)
        const layoverMarkers = summary.layovers && summary.layovers.length ? (() => {
            // Calculate total duration in minutes
            const totalDurationText = summary.duration || '0h 0m';
            const durationMatch = totalDurationText.match(/(\d+)h\s*(\d+)m?/);
            const totalHours = durationMatch ? parseInt(durationMatch[1] || 0, 10) : 0;
            const totalMinutes = durationMatch ? parseInt(durationMatch[2] || 0, 10) : 0;
            const totalDurationMinutes = (totalHours * 60) + totalMinutes;
            
            // If we have itinerary segments, calculate from actual segment times
            const itinerarySegments = summary.itinerarySegments || [];
            let calculatedTotalMinutes = totalDurationMinutes;
            
            if (itinerarySegments.length > 0) {
                const firstSeg = itinerarySegments[0];
                const lastSeg = itinerarySegments[itinerarySegments.length - 1];
                if (firstSeg && lastSeg && firstSeg.departure && lastSeg.arrival) {
                    const depTime = new Date(firstSeg.departure.at);
                    const arrTime = new Date(lastSeg.arrival.at);
                    calculatedTotalMinutes = (arrTime - depTime) / (1000 * 60);
                }
            }
            
            return summary.layovers.map((item, idx) => {
                let positionPercent = 50; // Default fallback
                
                // Calculate position based on time until layover occurs
                // Layover happens at the END of segment at index (idx) 
                if (itinerarySegments.length > 0 && idx < itinerarySegments.length) {
                    const firstSeg = itinerarySegments[0];
                    const segmentBeforeLayover = itinerarySegments[idx]; // Segment that ends at this layover
                    
                    if (firstSeg && segmentBeforeLayover && firstSeg.departure && segmentBeforeLayover.arrival) {
                        const depTime = new Date(firstSeg.departure.at);
                        const layoverArrivalTime = new Date(segmentBeforeLayover.arrival.at);
                        const timeToLayoverMinutes = (layoverArrivalTime - depTime) / (1000 * 60);
                        
                        if (calculatedTotalMinutes > 0 && timeToLayoverMinutes > 0) {
                            positionPercent = (timeToLayoverMinutes / calculatedTotalMinutes) * 100;
                        }
                    }
                }
                
                // Clamp between 3% and 97% to keep circle visible (not at edges, account for airplane icon)
                positionPercent = Math.max(3, Math.min(97, positionPercent));
                
                return `<span class="amadex-layover-dot" style="left:${positionPercent}%;" title="Layover (${item.index}) · ${item.duration} · ${item.city} (${item.airport})"></span>`;
            }).join('');
        })() : '';
        return `
            <div class="amadex-flight-leg">
                <div class="amadex-leg-title">${summary.label} • ${summary.dateLabel}</div>
                
                <div class="amadex-leg-route">
                ${summary.airlineLogo ? `<div class="amadex-leg-airline-logo">
                    <img src="${summary.airlineLogo}" alt="${summary.airlineName || ''}" onerror="this.onerror=null; this.src='${getAirlineLogoFallback(summary.airlineCode || '')}';">
                </div>` : ''}
                    <div class="amadex-leg-time-block">
                        <span class="amadex-leg-time">${summary.depTime}</span>
                        <span class="amadex-leg-code">${summary.depCode}</span>
                    </div>
                    <div class="amadex-leg-path">
                        <div class="amadex-leg-duration">
                            <span>${summary.duration}</span>
                        </div>
                        <div class="amadex-leg-path-line">
                            <svg width="140" height="2" viewBox="0 0 140 2" fill="none" xmlns="http://www.w3.org/2000/svg" class="amadex-path-line">
                                <line x1="0" y1="1" x2="140" y2="1" stroke="#707070" stroke-width="2"/>
                            </svg>
                            ${layoverMarkers ? `<div class="amadex-layover-dots">${layoverMarkers}</div>` : ''}
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" class="amadex-plane-icon">
                                <path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z" fill="#6B7280"/>
                            </svg>
                        </div>
                        <div class="amadex-leg-info-row">
                        ${layoverDetails}
                            ${summary.aircraftInfo ? `<div class="amadex-leg-aircraft-info" data-aircraft-code="${summary.aircraftCode || ''}" data-aircraft-name="${summary.aircraftInfo}" title="Aircraft: ${summary.aircraftInfo}">${summary.aircraftInfo}</div>` : ''}
                        </div>
                    </div>
                    <div class="amadex-leg-time-block">
                        <span class="amadex-leg-time">${summary.arrTime}</span>
                        <span class="amadex-leg-code">${summary.arrCode}</span>
                    </div>
                </div>
                <div class="amadex-leg-meta">
                    <span class="amadex-leg-airline">${summary.airlineName || ''}${summary.operatingCarrier ? ' <span class="amadex-operating-carrier">(operated by ' + summary.operatingCarrier + ')</span>' : ''}</span>
                    <span class="amadex-leg-included ${!summary.hasBaggage ? 'no-baggage' : ''}">
                        ${summary.hasBaggage ? '<span class="amadex-baggage-label">Included:</span>' : ''}
                        <span class="amadex-baggage-icons-wrapper" title="${summary.baggageDetails || (summary.hasBaggage ? 'Baggage included' : 'No baggage included')}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="14.545" viewBox="0 0 10 14.545" class="amadex-baggage-svg">
  <g id="Group_256" data-name="Group 256" transform="translate(-80)">
    <path id="Path_28" data-name="Path 28" d="M88.182,1.818h-.909V.909a.455.455,0,0,0,0-.909H82.727a.455.455,0,0,0,0,.909v.909h-.909A1.82,1.82,0,0,0,80,3.636v8.182a1.82,1.82,0,0,0,1.818,1.818h.227v.455a.455.455,0,1,0,.909,0v-.455h4.091v.455a.455.455,0,1,0,.909,0v-.455h.227A1.82,1.82,0,0,0,90,11.818V3.636A1.82,1.82,0,0,0,88.182,1.818ZM83.636.909h2.727v.909H83.636Zm-.455,10.227a.455.455,0,0,1-.909,0V4.318a.455.455,0,0,1,.909,0Zm2.273,0a.455.455,0,1,1-.909,0V4.318a.455.455,0,1,1,.909,0Zm1.364,0V4.318a.455.455,0,1,1,.909,0v6.818a.455.455,0,1,1-.909,0Z" fill="${summary.hasBaggage ? '#0e7d3f' : '#9CA3AF'}"/>
  </g>
</svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="11.666" height="14.243" viewBox="0 0 11.666 14.243" class="amadex-baggage-svg">
  <g id="Group_263" data-name="Group 263" transform="translate(0 -48.164)">
    <path id="Path_29" data-name="Path 29" d="M7.862,52.824V48.531a.367.367,0,0,0-.367-.367H4.17a.367.367,0,0,0-.367.367v4.293H2.763v9.583H8.9V52.824Zm-.734,0H4.537V48.9H7.128v3.923Zm-5.881,0A1.249,1.249,0,0,0,0,54.071V61.16a1.249,1.249,0,0,0,1.247,1.247h.782V52.824Zm9.171,0H9.637v9.583h.782a1.249,1.249,0,0,0,1.247-1.247V54.071A1.249,1.249,0,0,0,10.418,52.824Z" transform="translate(0 0)" fill="${summary.hasBaggage ? '#0e7d3f' : '#9CA3AF'}"/>
  </g>
</svg>
                            
                        </span>
                    </span>
                </div>
            </div>
        `;
    }

    function formatIsoDuration(duration) {
        if (!duration) return '';
        const hoursMatch = duration.match(/(\d+)H/);
        const minutesMatch = duration.match(/(\d+)M/);
        const hours = hoursMatch ? parseInt(hoursMatch[1], 10) : 0;
        const minutes = minutesMatch ? parseInt(minutesMatch[1], 10) : 0;
        const parts = [];
        if (hours) parts.push(`${hours}h`);
        if (minutes) parts.push(`${minutes}m`);
        return parts.join(' ') || '0h';
    }

    function getCabinCodeFromFlight(flight) {
        try {
            if (flight && flight.travelerPricings && flight.travelerPricings[0] && flight.travelerPricings[0].fareDetailsBySegment && flight.travelerPricings[0].fareDetailsBySegment[0]) {
                return flight.travelerPricings[0].fareDetailsBySegment[0].cabin || 'ECONOMY';
            }
        } catch (e) {
            console.warn('Unable to detect cabin class from flight data', e);
        }
        const stored = getStoredSearchData();
        return stored.cabin || 'ECONOMY';
    }

    /**
     * Create segment HTML
     */
    function createSegmentHtml(segment, index, totalSegments) {
        const departure = new Date(segment.departure.at);
        const arrival = new Date(segment.arrival.at);
        const isLast = index === totalSegments - 1;
        
        return `
            <div class="amadex-flight-segment">
                <div class="amadex-segment-airports">
                    <div class="amadex-segment-airport">
                        <div class="amadex-segment-airport-code">${segment.departure.iata_code}</div>
                        <div class="amadex-segment-airport-name">${formatTime(departure)}</div>
                    </div>
                    <div class="amadex-segment-duration">${segment.duration}</div>
                    <div class="amadex-segment-airport">
                        <div class="amadex-segment-airport-code">${segment.arrival.iata_code}</div>
                        <div class="amadex-segment-airport-name">${formatTime(arrival)}</div>
                    </div>
                </div>
                ${!isLast ? '<div class="amadex-segment-connection">1 Stops</div>' : ''}
            </div>
        `;
    }

    /**
     * Update search info display
     */
    function updateSearchInfo(searchData) {
        // Update search summary bar with city names if available, otherwise use IATA codes
        const fromDisplay = searchData.origin_name || searchData.origin || 'DEL';
        const toDisplay = searchData.destination_name || searchData.destination || 'DXB';
        isOneWaySearch = searchData.one_way === true || searchData.one_way === 'Yes' || searchData.one_way === 'yes';
        
        $('#amadex-summary-from').text(fromDisplay);
        $('#amadex-summary-to').text(toDisplay);
        
        const depDate = searchData.departure ? formatDate(searchData.departure) : '';
        const retDate = searchData.return ? formatDate(searchData.return) : '';
        $('#amadex-summary-dates').text(depDate + (retDate ? ' - ' + retDate : ''));
        
        const adults = parseInt(searchData.adults || 1);
        const children = parseInt(searchData.children || 0);
        const infants = parseInt(searchData.infants || 0);
        const cabin = getCabinDisplayName(searchData.cabin || 'ECONOMY');
        
        // Build passenger summary
        let paxSummary = `${adults} Adult${adults > 1 ? 's' : ''}`;
        if (children > 0) paxSummary += `, ${children} Child${children > 1 ? 'ren' : ''}`;
        if (infants > 0) paxSummary += `, ${infants} Infant${infants > 1 ? 's' : ''}`;
        paxSummary += `, ${cabin}`;
        
        $('#amadex-summary-pax').text(paxSummary);
        
        // Update header cities (use city names if available)
        $('#amadex-from-city').text(fromDisplay);
        $('#amadex-to-city').text(toDisplay);
    }

    /**
     * Update filters
     */
    function updateFilters(filters) {
        // Update airlines filter
        const airlinesContainer = $('#amadex-airlines-filter');
        airlinesContainer.empty();
        
        if (filters.airlines) {
            filters.airlines.forEach(function(airline) {
                const option = $(`
                    <label class="amadex-filter-option">
                        <input type="checkbox" name="airlines" value="${airline}">
                        <span class="amadex-filter-label">${airline}</span>
                    </label>
                `);
                airlinesContainer.append(option);
            });
        }
        
        // Update price range
        if (filters.price_range) {
            $('#amadex-price-min').attr('max', filters.price_range.max);
            $('#amadex-price-max').attr('max', filters.price_range.max);
            $('#amadex-price-max').val(filters.price_range.max);
            updatePriceLabels();
        }
    }

    /**
     * Apply filters
     */
    /**
     * Get departure hour from flight
     */
    function getFlightDepartureHour(flight) {
        try {
            var itin = flight.itineraries && flight.itineraries[0];
            var seg  = itin && itin.segments && itin.segments[0];
            var at   = seg && (seg.departure && (seg.departure.at || seg.departure.datetime));
            if (!at) return -1;
            return parseInt((at.split('T')[1] || '').split(':')[0], 10);
        } catch(e) { return -1; }
    }

    /**
     * Get arrival hour from flight (last segment arrival)
     */
    function getFlightArrivalHour(flight) {
        try {
            var itin = flight.itineraries && flight.itineraries[0];
            var segs = itin && itin.segments;
            var seg  = segs && segs[segs.length - 1];
            var at   = seg && (seg.arrival && (seg.arrival.at || seg.arrival.datetime));
            if (!at) return -1;
            return parseInt((at.split('T')[1] || '').split(':')[0], 10);
        } catch(e) { return -1; }
    }

    /**
     * Map hour to time-of-day bucket
     */
    function hourToTimeBucket(hour) {
        if (hour < 0)  return null;
        if (hour < 6)  return 'early_morning';
        if (hour < 12) return 'morning';
        if (hour < 18) return 'afternoon';
        return 'evening';
    }

    /**
     * Get stop count for flight
     */
    function getFlightStops(flight) {
        try {
            var itin = flight.itineraries && flight.itineraries[0];
            var segs = itin && itin.segments;
            return segs ? segs.length - 1 : 0;
        } catch(e) { return 0; }
    }

    /**
     * Get airline code for flight
     */
    function getFlightAirline(flight) {
        try {
            var itin = flight.itineraries && flight.itineraries[0];
            var seg  = itin && itin.segments && itin.segments[0];
            return (seg && (seg.carrierCode || seg.carrier_code || seg.operating_carrier)) || '';
        } catch(e) { return ''; }
    }

    /**
     * Get flight price
     */
    function getFlightPrice(flight) {
        try {
            var p = flight.price;
            if (!p) return 0;
            return parseFloat(
                p.pricing_charge_total || p.grandTotal || p.total || p.base || 0
            );
        } catch(e) { return 0; }
    }

    /**
     * CLIENT-SIDE applyFilters — filters window.amadexAllFlights directly
     * No AJAX call needed — all data is already in memory
     */
    function applyFilters() {
        // Use all flights stored globally
        var allFlights = window.amadexAllFlights || (currentSearchData && currentSearchData.flights) || [];
        if (!allFlights.length) return;

        // Read active filter values
        var selectedAirlines   = $('input[name="airlines"]:checked').map(function() { return this.value; }).get();
        var selectedStops      = $('input[name="stops"]:checked').map(function() { return this.value; }).get();
        var priceMin           = parseFloat($('#amadex-price-min').val()) || 0;
        var priceMax           = parseFloat($('#amadex-price-max').val()) || 999999;
        var selectedDepTimes   = $('input[name="outbound_departure"], input[name="departure_time"]').filter(':checked').map(function() { return this.value; }).get();
        var selectedArrTimes   = $('input[name="outbound_arrival"], input[name="return_time"]').filter(':checked').map(function() { return this.value; }).get();

        // Filter flights
        var filtered = allFlights.filter(function(flight) {

            // ── Airline filter ──
            if (selectedAirlines.length > 0) {
                var airline = getFlightAirline(flight).toUpperCase();
                if (!selectedAirlines.some(function(a) { return a.toUpperCase() === airline; })) {
                    return false;
                }
            }

            // ── Stops filter ──
            if (selectedStops.length > 0) {
                var stops = getFlightStops(flight);
                var stopKey = stops === 0 ? '0' : (stops === 1 ? '1' : '2');
                if (!selectedStops.includes(stopKey) && !selectedStops.includes(String(stops))) {
                    return false;
                }
            }

            // ── Price filter ──
            var price = getFlightPrice(flight);
            if (price < priceMin || price > priceMax) {
                return false;
            }

            // ── Departure time filter ──
            if (selectedDepTimes.length > 0) {
                var depHour   = getFlightDepartureHour(flight);
                var depBucket = hourToTimeBucket(depHour);
                if (!selectedDepTimes.includes(depBucket)) {
                    return false;
                }
            }

            // ── Arrival time filter ──
            if (selectedArrTimes.length > 0) {
                var arrHour   = getFlightArrivalHour(flight);
                var arrBucket = hourToTimeBucket(arrHour);
                if (!selectedArrTimes.includes(arrBucket)) {
                    return false;
                }
            }

            return true;
        });

        // Display filtered results
        displayFilteredResults({ flights: filtered, meta: { count: filtered.length } });

        // Update active filter tags
        updateActiveFilterTags();

        // Sync filters to URL so results are bookmarkable/shareable
        syncFiltersToURL();
    }

    /**
     * Update active filter tags shown above results
     */
    function updateActiveFilterTags() {
        var $tags = $('#amadex-active-filters');
        if (!$tags.length) return;
        $tags.empty();

        $('input[name="airlines"]:checked, input[name="stops"]:checked, input[name="outbound_departure"]:checked, input[name="outbound_arrival"]:checked, input[name="departure_time"]:checked, input[name="return_time"]:checked').each(function() {
            var label = $(this).closest('label').find('.amadex-filter-label').text().trim()
                     || $(this).closest('label').text().trim()
                     || this.value;
            var name  = this.name;
            var value = this.value;
            var $tag  = $('<span class="amadex-filter-tag"></span>').text(label);
            var $x    = $('<button class="amadex-filter-tag-remove" aria-label="Remove filter">×</button>');
            $x.on('click', function() {
                $('input[name="' + name + '"][value="' + value + '"]').prop('checked', false);
                applyFilters();
            });
            $tag.append($x);
            $tags.append($tag);
        });
    }

    /**
     * Display filtered results
     */
    function displayFilteredResults(data) {
        const flightsList = $('#amadex-flights-list');
        const flightCardsContainer = $('#amadex-flight-cards-container');
        const noResults = $('#amadex-no-results');
        
        if (!data.flights || data.flights.length === 0) {
            // Show no-results message when filters return no flights
            noResults.show();
            flightCardsContainer.empty();
            updateResultsAvailableCount(0);
            return;
        }
        
        // Hide no results message when there are flights
        noResults.hide();
        // Clear only the flight cards container
        flightCardsContainer.empty();
        
        data.flights.forEach(function(flight, index) {
            const flightElement = createFlightElement(flight, index);
            flightCardsContainer.append(flightElement);
        });
        
        const totalCount = data.meta && data.meta.count ? data.meta.count : data.flights.length;
        updateResultsAvailableCount(totalCount);
        currentSearchData = data;
        currentSearchData._searchInfo = currentSearchData._searchInfo || {};
        currentSearchData._searchInfo.isOneWay = isOneWaySearch;
        sortFlights();
    }

    /**
     * Sync active filters to URL query params (no page reload)
     */
    function syncFiltersToURL() {
        var urlParams = new URLSearchParams(window.location.search);

        // Remove old filter params
        urlParams.delete('f_airlines');
        urlParams.delete('f_stops');
        urlParams.delete('f_price_min');
        urlParams.delete('f_price_max');
        urlParams.delete('f_dep_time');
        urlParams.delete('f_arr_time');

        // Airlines
        var airlines = $('input[name="airlines"]:checked').map(function() { return this.value; }).get();
        if (airlines.length) urlParams.set('f_airlines', airlines.join(','));

        // Stops
        var stops = $('input[name="stops"]:checked').map(function() { return this.value; }).get();
        if (stops.length) urlParams.set('f_stops', stops.join(','));

        // Price
        var priceMin = $('#amadex-price-min').val();
        var priceMax = $('#amadex-price-max').val();
        var sliderMin = $('#amadex-price-min').attr('min');
        var sliderMax = $('#amadex-price-max').attr('max');
        if (priceMin && priceMin !== sliderMin) urlParams.set('f_price_min', priceMin);
        if (priceMax && priceMax !== sliderMax) urlParams.set('f_price_max', priceMax);

        // Departure time
        var depTimes = $('input[name="outbound_departure"]:checked, input[name="departure_time"]:checked')
            .map(function() { return this.value; }).get();
        if (depTimes.length) urlParams.set('f_dep_time', depTimes.join(','));

        // Arrival time
        var arrTimes = $('input[name="outbound_arrival"]:checked, input[name="return_time"]:checked')
            .map(function() { return this.value; }).get();
        if (arrTimes.length) urlParams.set('f_arr_time', arrTimes.join(','));

        // Push to URL without reloading
        var newUrl = window.location.pathname + '?' + urlParams.toString();
        window.history.replaceState({ filters: true }, '', newUrl);
    }

    /**
     * Restore filters from URL params on page load
     */
    function restoreFiltersFromURL() {
        var urlParams = new URLSearchParams(window.location.search);
        var hasFilters = false;

        // Airlines
        var airlines = urlParams.get('f_airlines');
        if (airlines) {
            airlines.split(',').forEach(function(code) {
                $('input[name="airlines"][value="' + code.trim() + '"]').prop('checked', true);
            });
            hasFilters = true;
        }

        // Stops
        var stops = urlParams.get('f_stops');
        if (stops) {
            stops.split(',').forEach(function(val) {
                $('input[name="stops"][value="' + val.trim() + '"]').prop('checked', true);
            });
            hasFilters = true;
        }

        // Price
        var priceMin = urlParams.get('f_price_min');
        var priceMax = urlParams.get('f_price_max');
        if (priceMin) { $('#amadex-price-min').val(priceMin); $('#amadex-price-min-display').text('$' + Math.round(priceMin)); hasFilters = true; }
        if (priceMax) { $('#amadex-price-max').val(priceMax); $('#amadex-price-max-display').text('$' + Math.round(priceMax)); hasFilters = true; }

        // Departure time
        var depTime = urlParams.get('f_dep_time');
        if (depTime) {
            depTime.split(',').forEach(function(val) {
                $('input[name="outbound_departure"][value="' + val.trim() + '"], input[name="departure_time"][value="' + val.trim() + '"]').prop('checked', true);
            });
            hasFilters = true;
        }

        // Arrival time
        var arrTime = urlParams.get('f_arr_time');
        if (arrTime) {
            arrTime.split(',').forEach(function(val) {
                $('input[name="outbound_arrival"][value="' + val.trim() + '"], input[name="return_time"][value="' + val.trim() + '"]').prop('checked', true);
            });
            hasFilters = true;
        }

        // Apply filters if any were restored
        if (hasFilters) {
            setTimeout(function() { applyFilters(); }, 500);
        }
    }

    /**
     * Sort flights - CRITICAL FIX: Preserves promotional containers
     */
    function sortFlights() {
        const sortBy = $('#amadex-sort-by').val() || 'low_to_high';
        const container = $('#amadex-flight-cards-container');
        if (!container.length) return;
        
        // CRITICAL FIX #2: Store promotional containers with their associated flights
        // Get all children and separate flights from promotional containers
        const allElements = container.children().toArray();
        const flightContainerPairs = [];
        
        for (let i = 0; i < allElements.length; i++) {
            const element = allElements[i];
            const $element = $(element);
            
            if ($element.hasClass('amadex-flight-card')) {
                // Found a flight card - collect it and any promotional containers that follow
                const containers = [];
                let j = i + 1;
                while (j < allElements.length && $(allElements[j]).hasClass('amadex-promotional-container')) {
                    containers.push(allElements[j]);
                    j++;
                }
                
                flightContainerPairs.push({
                    flight: element,
                    containers: containers
                });
                
                i = j - 1; // Skip over the containers we just collected
            }
        }
        
        if (flightContainerPairs.length === 0) return;
        
        // Extract just flights for sorting
        const flights = flightContainerPairs.map(pair => pair.flight);
        
        // Sort flights
        flights.sort(function(a, b) {
            const aPrice = parseFloat(a.dataset.price) || 0;
            const bPrice = parseFloat(b.dataset.price) || 0;
            const priceDiff = aPrice - bPrice;
            switch (sortBy) {
                case 'low_to_high':
                    return priceDiff;
                case 'high_to_low':
                    return -priceDiff;
                case 'nearest':
                    const aStops = parseInt(a.dataset.stops, 10) || 0;
                    const bStops = parseInt(b.dataset.stops, 10) || 0;
                    if (aStops !== bStops) {
                        return aStops - bStops;
                    }
                    return priceDiff;
                case 'shortest':
                    const aDuration = parseInt(a.dataset.duration, 10) || 0;
                    const bDuration = parseInt(b.dataset.duration, 10) || 0;
                    if (aDuration !== bDuration) {
                        return aDuration - bDuration;
                    }
                    return priceDiff;
                default:
                    return priceDiff;
            }
        });
        
        // CRITICAL FIX: Rebuild container with sorted flights and their associated promotional containers
        container.empty();
        
        // Find containers for each sorted flight (maintain original association)
        flights.forEach(function(flight) {
            // Find the original pair for this flight
            const pair = flightContainerPairs.find(p => p.flight === flight);
            if (pair) {
                // Append flight
                container.append(pair.flight);
                // Append its associated containers
                pair.containers.forEach(function(containerEl) {
                    container.append(containerEl);
                });
            } else {
                // Fallback: just append the flight
                container.append(flight);
            }
        });
    }

    /**
     * Update price labels
     */
    function updatePriceLabels() {
        const minPrice = $('#amadex-price-min').val();
        const maxPrice = $('#amadex-price-max').val();
        $('#amadex-price-min-label').text('£' + minPrice);
        $('#amadex-price-max-label').text('£' + maxPrice);
    }

    /**
     * Update price display
     */
    function updatePriceDisplay() {
        const isPerPerson = $('#amadex-price-per-person').is(':checked');
        // Implementation for per person vs total price display
    }

    /**
     * Show flight details modal
     */
    function showFlightDetails(flightId) {
        $.ajax({
            url: AmadexConfig.ajaxUrl,
            type: 'POST',
            data: {
                action: 'amadex_get_flight_details',
                flight_id: flightId,
                nonce: AmadexConfig.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayFlightDetailsModal(response.data);
                }
            }
        });
    }

    /**
     * Display flight details modal
     */
    function displayFlightDetailsModal(details) {
        const modal = $('#amadex-flight-details-modal');
        const content = $('#amadex-flight-details-content');
        
        let html = `
            <div class="amadex-flight-details-content">
                <h4>Flight Information</h4>
                <div class="amadex-details-section">
                    <h5>Baggage</h5>
                    <p>${details.details.baggage.included}</p>
                    <p>Weight: ${details.details.baggage.weight}</p>
                    <p>${details.details.baggage.additional}</p>
                </div>
                <div class="amadex-details-section">
                    <h5>Meals</h5>
                    <p>${details.details.meals.included}</p>
                    <ul>
                        ${details.details.meals.options.map(option => `<li>${option}</li>`).join('')}
                    </ul>
                </div>
                <div class="amadex-details-section">
                    <h5>Seats</h5>
                    <p>${details.details.seats.included}</p>
                    <ul>
                        ${details.details.seats.options.map(option => `<li>${option}</li>`).join('')}
                    </ul>
                </div>
                <div class="amadex-details-section">
                    <h5>Amenities</h5>
                    <ul>
                        <li>${details.details.amenities.wifi}</li>
                        <li>${details.details.amenities.entertainment}</li>
                        <li>${details.details.amenities.power}</li>
                    </ul>
                </div>
                <div class="amadex-details-section">
                    <h5>Policies</h5>
                    <ul>
                        <li>${details.details.policies.cancellation}</li>
                        <li>${details.details.policies.changes}</li>
                        <li>${details.details.policies.refund}</li>
                    </ul>
                </div>
            </div>
        `;
        
        content.html(html);
        modal.show();
    }

    /**
     * Amadeus add-ons: price analysis, delay prediction, trip purpose (Insights tab).
     */
    // function loadAmadeusInsightsPanel(flightData, searchData) {
    //     var $root = $('#amadex-amadeus-insights-root');
    //     if (!$root.length || typeof window.AmadexAmadeus === 'undefined' || !window.AmadexAmadeus.call) {
    //         return;
    //     }

    function renderLocalInsights($root, flightData, searchData) {
        var sd = searchData || {};
        var price = flightData.price || {};
        var itineraries = flightData.itineraries || [];
        var firstItin = itineraries[0] || {};
        var segments = firstItin.segments || [];
        var firstSeg = segments[0] || {};
        var lastSeg = segments[segments.length - 1] || firstSeg;

        function iata(o) { return (o && (o.iata_code || o.iataCode || o.code)) || ''; }
        function dur(iso) {
            if (!iso) return '—';
            var h = iso.match(/(\d+)H/), m = iso.match(/(\d+)M/);
            return (h ? h[1] + 'h ' : '') + (m ? m[1] + 'm' : '');
        }
        function fmtDate(iso) {
            if (!iso) return '—';
            try { var d = new Date(iso); return d.toLocaleDateString('en-US', { weekday:'short', month:'short', day:'numeric', year:'numeric' }); }
            catch(e) { return iso.slice(0,10); }
        }
        function fmtTime(iso) {
            if (!iso) return '—';
            try { var d = new Date(iso); return d.toLocaleTimeString('en-US', { hour:'2-digit', minute:'2-digit', hour12:true }); }
            catch(e) { return iso.slice(11,16); }
        }
        function money(v, cur) {
            try { return new Intl.NumberFormat('en-US',{style:'currency',currency:cur||'USD',maximumFractionDigits:2}).format(v); }
            catch(e) { return '$' + parseFloat(v||0).toFixed(2); }
        }
        function icon(svg) { return '<span class="ai-icon">' + svg + '</span>'; }

        var SVG = {
            price:  '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>',
            seat:   '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.38 3.46L16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.57a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.57a2 2 0 0 0-1.34-2.23z"/></svg>',
            bag:    '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="7" width="22" height="15" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/><line x1="12" y1="12" x2="12" y2="16"/><line x1="10" y1="14" x2="14" y2="14"/></svg>',
            plane:  '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.8 19.2L16 11l3.5-3.5C21 6 21 4 19 4s-2 1-3.5 2.5L11 8 2.8 6.2c-.5-.1-.9.2-1.1.7l-.3.8c-.1.5.2 1 .7 1.2L7 11l-1.5 2.2-2.1.4c-.4.1-.7.4-.7.8V15c0 .5.5.9 1 .7L6 15l2 2-.7 2.2c-.2.5.2 1 .7 1l.8.3c.5.2 1-.1 1.2-.6L11 17l4.1 2.1c.5.3 1.1.1 1.3-.4l.8-2.1c.1-.4 0-.8-.2-1.1l-.3-.5z"/></svg>',
            clock:  '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
            co2:    '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2z"/><path d="M8 12s1-4 4-4 4 4 4 4"/></svg>',
        };

        var cur       = price.currency || 'USD';
        var total     = parseFloat(price.total || price.grandTotal || 0);
        var base      = parseFloat(price.base || price.original_base || 0);
        var taxes     = parseFloat(price.taxes || price.original_taxes || (total - base) || 0);
        var origTotal = parseFloat(price.original_total || total);
        var seats     = parseInt(flightData.number_of_bookable_seats || 0);
        var lastTkt   = flightData.last_ticketing_date || '';
        var adults    = parseInt(sd.adults || 1);
        var children  = parseInt(sd.children || 0);
        var infants   = parseInt(sd.infants || 0);
        var totalPax  = adults + children + infants || 1;
        var cabinRaw  = (sd.cabin || 'ECONOMY').toUpperCase();
        var cabinMap  = {ECONOMY:'Economy',PREMIUM_ECONOMY:'Premium Economy',BUSINESS:'Business',FIRST:'First Class'};
        var cabin     = cabinMap[cabinRaw] || cabinRaw;
        var stops     = segments.length - 1;
        var hasMeal   = !!flightData.has_meal;
        var hasBag    = !!flightData.has_baggage;
        var detBag    = flightData.detailed_baggage || [];
        var amenities = flightData.detailed_amenities || {};
        var segAmen   = flightData.segment_amenities || {};
        var fareBasis = flightData.fare_basis_codes || {};
        var bookClass = flightData.booking_classes || {};
        var branded   = flightData.branded_fares || {};
        var airline   = (flightData.validating_airline_codes || flightData.validatingAirlineCodes || [])[0] || firstSeg.carrier_code || '';
        var saved     = origTotal > total && origTotal > 0 ? (origTotal - total) : 0;
        var savedPct  = origTotal > 0 && saved > 0 ? Math.round((saved/origTotal)*100) : 0;
        var perPax    = totalPax > 0 ? total / totalPax : total;
        var co2Total  = 0;
        segments.forEach(function(seg) {
            (seg.co2_emissions || []).forEach(function(e) { co2Total += parseFloat(e.weight || 0); });
        });

        var html = '<div class="ai-grid">';

        // Card 1: Price Breakdown
        html += '<div class="ai-card ai-card--price">';
        html += '<div class="ai-card-header">' + icon(SVG.price) + '<span>Price Breakdown</span></div>';
        html += '<div class="ai-card-body">';
        html += '<div class="ai-row"><span>Base Fare</span><strong>' + money(base, cur) + '</strong></div>';
        if (taxes > 0) html += '<div class="ai-row"><span>Taxes & Fees</span><strong>' + money(taxes, cur) + '</strong></div>';
        if (totalPax > 1) html += '<div class="ai-row"><span>Per Person</span><strong>' + money(perPax, cur) + '</strong></div>';
        if (saved > 0) html += '<div class="ai-row ai-row--green"><span>You Save (' + savedPct + '% off)</span><strong>-' + money(saved, cur) + '</strong></div>';
        html += '<div class="ai-divider"></div>';
        html += '<div class="ai-row ai-row--total"><span>Total (' + totalPax + ' Pax)</span><strong>' + money(total, cur) + '</strong></div>';
        html += '</div></div>';

        // Card 2: Flight Summary
        html += '<div class="ai-card">';
        html += '<div class="ai-card-header">' + icon(SVG.plane) + '<span>Flight Summary</span></div>';
        html += '<div class="ai-card-body">';
        html += '<div class="ai-row"><span>Airline</span><strong>' + airline + '</strong></div>';
        html += '<div class="ai-row"><span>Cabin</span><strong>' + cabin + '</strong></div>';
        html += '<div class="ai-row"><span>Stops</span><strong>' + (stops === 0 ? 'Non-stop ✈' : stops + ' Stop' + (stops > 1 ? 's' : '')) + '</strong></div>';
        html += '<div class="ai-row"><span>Total Duration</span><strong>' + dur(firstItin.duration) + '</strong></div>';
        html += '<div class="ai-row"><span>Route</span><strong>' + iata(firstSeg.departure) + ' → ' + iata(lastSeg.arrival) + '</strong></div>';
        if (Object.keys(branded).length) html += '<div class="ai-row"><span>Fare Brand</span><strong>' + Object.values(branded)[0] + '</strong></div>';
        html += '</div></div>';

        // Card 3: Availability
        html += '<div class="ai-card' + (seats > 0 && seats <= 5 ? ' ai-card--warn' : '') + '">';
        html += '<div class="ai-card-header">' + icon(SVG.seat) + '<span>Availability</span></div>';
        html += '<div class="ai-card-body">';
        if (seats > 0) {
            var sc = seats <= 3 ? '#e53935' : seats <= 7 ? '#f97316' : '#0E7D3F';
            html += '<div class="ai-row"><span>Seats Left</span><strong style="color:' + sc + '">' + seats + ' Seat' + (seats > 1 ? 's' : '') + (seats <= 5 ? ' ⚠️' : '') + '</strong></div>';
        }
        if (lastTkt) html += '<div class="ai-row"><span>Book By</span><strong>' + fmtDate(lastTkt) + '</strong></div>';
        var paxParts = [];
        if (adults) paxParts.push(adults + ' Adult' + (adults > 1 ? 's' : ''));
        if (children) paxParts.push(children + ' Child' + (children > 1 ? 'ren' : ''));
        if (infants) paxParts.push(infants + ' Infant' + (infants > 1 ? 's' : ''));
        html += '<div class="ai-row"><span>Passengers</span><strong>' + paxParts.join(', ') + '</strong></div>';
        html += '</div></div>';

        // Card 4: Baggage & Services
        html += '<div class="ai-card">';
        html += '<div class="ai-card-header">' + icon(SVG.bag) + '<span>Baggage & Services</span></div>';
        html += '<div class="ai-card-body">';
        if (detBag.length > 0) {
            detBag.forEach(function(b) {
                var label = b.type === 'carry_on' ? '🎒 Cabin Bag' : '🧳 Checked Bag';
                var detail = b.quantity + ' piece' + (b.quantity > 1 ? 's' : '');
                if (b.weight) detail += ' · ' + b.weight + ' ' + (b.weight_unit || 'KG');
                html += '<div class="ai-row"><span>' + label + '</span><strong>' + detail + '</strong></div>';
            });
        } else {
            html += '<div class="ai-row"><span>Checked Bag</span><strong>' + (hasBag ? '✅ Included' : '❌ Not included') + '</strong></div>';
        }
        html += '<div class="ai-row"><span>Meal</span><strong>' + (hasMeal ? '✅ Included' : '❌ Not included') + '</strong></div>';
        Object.keys(amenities).slice(0, 4).forEach(function(code) {
            html += '<div class="ai-row"><span>' + amenities[code] + '</span><strong>✅</strong></div>';
        });
        html += '</div></div>';

        // Card 5: Segment Details
        if (segments.length > 0) {
            html += '<div class="ai-card ai-card--full">';
            html += '<div class="ai-card-header">' + icon(SVG.clock) + '<span>Segment Details</span></div>';
            html += '<div class="ai-card-body">';
            segments.forEach(function(seg, idx) {
                var dep = seg.departure || {}; var arr = seg.arrival || {};
                var segId = seg.id || idx;
                var bc = bookClass[idx] || bookClass[segId] || '';
                var fb = fareBasis[idx] || fareBasis[segId] || '';
                html += '<div class="ai-segment-row">';
                html += '<div class="ai-segment-header"><strong>' + iata(dep) + ' → ' + iata(arr) + '</strong><span class="ai-badge">' + (seg.carrier_code||'') + ' ' + (seg.number||'') + '</span></div>';
                html += '<div class="ai-row"><span>Departs</span><strong>' + fmtTime(dep.at) + ' · ' + fmtDate(dep.at) + '</strong></div>';
                html += '<div class="ai-row"><span>Arrives</span><strong>' + fmtTime(arr.at) + ' · ' + fmtDate(arr.at) + '</strong></div>';
                html += '<div class="ai-row"><span>Duration</span><strong>' + dur(seg.duration) + '</strong></div>';
                html += '<div class="ai-row"><span>Aircraft</span><strong>' + (seg.aircraft || '—') + '</strong></div>';
                if (bc) html += '<div class="ai-row"><span>Booking Class</span><strong>' + bc + '</strong></div>';
                if (fb) html += '<div class="ai-row"><span>Fare Basis</span><strong>' + fb + '</strong></div>';
                if (dep.terminal) html += '<div class="ai-row"><span>Dep. Terminal</span><strong>T' + dep.terminal + '</strong></div>';
                if (arr.terminal) html += '<div class="ai-row"><span>Arr. Terminal</span><strong>T' + arr.terminal + '</strong></div>';
                if (seg.is_operated_by_different && seg.operating_carrier_code) html += '<div class="ai-row ai-row--muted"><span>Operated by</span><strong>' + seg.operating_carrier_code + '</strong></div>';
                var sa = segAmen[idx] || segAmen[segId] || [];
                if (sa.length > 0) html += '<div class="ai-row"><span>Amenities</span><strong>' + sa.join(', ') + '</strong></div>';
                var segCo2 = seg.co2_emissions || [];
                if (segCo2.length > 0) html += '<div class="ai-row"><span>CO₂</span><strong>' + (segCo2[0].weight||0) + ' kg/pax</strong></div>';
                html += '</div>';
                if (idx < segments.length - 1) html += '<div class="ai-divider"></div>';
            });
            html += '</div></div>';
        }

        // Card 6: CO2
        if (co2Total > 0) {
            html += '<div class="ai-card">';
            html += '<div class="ai-card-header">' + icon(SVG.co2) + '<span>Environmental Impact</span></div>';
            html += '<div class="ai-card-body">';
            html += '<div class="ai-row"><span>CO₂ Total</span><strong>' + co2Total.toFixed(1) + ' kg/pax</strong></div>';
            var trees = Math.round(co2Total / 21);
            if (trees > 0) html += '<div class="ai-row ai-row--muted"><span>Offset equivalent</span><strong>~' + trees + ' tree' + (trees > 1 ? 's' : '') + ' planted</strong></div>';
            html += '</div></div>';
        }

        html += '</div>';

        if (!document.getElementById('amadex-ai-styles')) {
            var style = document.createElement('style');
            style.id = 'amadex-ai-styles';
            style.textContent = [
                '.ai-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px;padding:16px 0;}',
                '.ai-card--full{grid-column:1/-1;}',
                '.ai-card{background:#fff;border:1px solid #e5f0e8;border-radius:14px;overflow:hidden;transition:box-shadow .2s;}',
                '.ai-card:hover{box-shadow:0 4px 16px rgba(14,125,63,.1);}',
                '.ai-card--warn{border-color:#f97316;border-width:2px;}',
                '.ai-card-header{display:flex;align-items:center;gap:8px;padding:12px 16px;background:#f6fdf8;border-bottom:1px solid #e5f0e8;font-weight:700;font-size:13px;color:#0E7D3F;}',
                '.ai-icon{display:flex;align-items:center;color:#0E7D3F;}',
                '.ai-card-body{padding:12px 16px;display:flex;flex-direction:column;gap:8px;}',
                '.ai-row{display:flex;justify-content:space-between;align-items:center;font-size:13px;gap:8px;}',
                '.ai-row span{color:#666;white-space:nowrap;}',
                '.ai-row strong{color:#1a1a1a;text-align:right;font-weight:600;}',
                '.ai-row--green strong{color:#0E7D3F;}',
                '.ai-row--total{font-size:15px;font-weight:700;}',
                '.ai-row--total strong{color:#0E7D3F;font-size:16px;}',
                '.ai-row--muted{opacity:.75;}',
                '.ai-divider{border:none;border-top:1px dashed #e5f0e8;margin:4px 0;}',
                '.ai-badge{background:#e8f5ed;color:#0E7D3F;border-radius:6px;padding:2px 8px;font-size:12px;font-weight:600;}',
                '.ai-segment-row{padding:8px 0;}',
                '.ai-segment-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;font-size:14px;}',
                '@media(max-width:600px){.ai-grid{grid-template-columns:1fr;}.ai-card--full{grid-column:auto;}}',
            ].join('');
            document.head.appendChild(style);
        }

        $root.html(html);
    }

     /**
     * Amadeus add-ons: price analysis, delay prediction, trip purpose (Insights tab).
     */
    function loadAmadeusInsightsPanel(flightData, searchData) {
        var $root = $('#amadex-amadeus-insights-root');
        if (!$root.length) return;

        // Build insights from data already in flightData
        renderLocalInsights($root, flightData, searchData);

        // If real Amadeus API is also available, load those too
        if (typeof window.AmadexAmadeus === 'undefined' || !window.AmadexAmadeus.call) {
            return;
        }
        var itineraries = flightData.itineraries || [];
        if (!itineraries.length) {
            $root.html('');
            return;
        }
        var segs = itineraries[0].segments || [];
        if (!segs.length) {
            $root.html('');
            return;
        }
        var first = segs[0];
        function iata(o) {
            return (o && (o.iata_code || o.iataCode)) || '';
        }
        function isoTime(iso) {
            if (!iso || typeof iso !== 'string') return '';
            var p = iso.split('T');
            var t = (p[1] || '12:00:00').replace(/([+-]\d{2}:\d{2}|Z)$/, '');
            if (t.length === 5) t += ':00';
            return t.slice(0, 8);
        }
        var dep = first.departure || {};
        var arr = first.arrival || {};
        var origin = iata(dep);
        var dest = iata(arr);
        var depAt = dep.at || '';
        var arrAt = arr.at || '';
        var sd = searchData || {};
        var cityOrigin = (sd.origin || origin || '').toString().toUpperCase().replace(/[^A-Z]/g, '').slice(0, 3);
        var cityDest = (sd.destination || dest || '').toString().toUpperCase().replace(/[^A-Z]/g, '').slice(0, 3);
        var depDate = (sd.departure || (depAt.split('T')[0] || '')).toString().slice(0, 10);
        var retDate = (sd.return || '').toString().slice(0, 10);
        var pending = 0;
        var finished = 0;
        $root.empty().append('<p class="amadex-muted amadex-insights-loading">Loading travel insights…</p>');
        var $grid = $('<div class="amadex-insights-grid"></div>');
        $root.append($grid);

        function doneOne() {
            finished += 1;
            if (finished >= pending) {
                $root.find('.amadex-insights-loading').remove();
                if (!$grid.children().length) {
                    $grid.append('<p class="amadex-muted">No additional insights are available for this itinerary.</p>');
                }
            }
        }
        var Ui = window.AmadexAmadeusUi;
        function addCard(title, subtitle, payload, kind) {
            if (Ui && Ui.addInsightCard) {
                Ui.addInsightCard($grid, title, subtitle, payload, kind || 'price_analysis');
            } else {
                var $c = $('<div class="amadex-insight-card"></div>');
                $c.append('<h4></h4>').find('h4').text(title);
                if (subtitle) {
                    $c.append('<p class="amadex-insight-small"></p>').find('p.amadex-insight-small').text(subtitle);
                }
                $c.append($('<pre class="amadex-insight-pre"></pre>').text(JSON.stringify(payload, null, 2).slice(0, 4000)));
                $grid.append($c);
            }
        }
        function addError(title, msg) {
            if (Ui && Ui.addError) {
                Ui.addError($grid, title, msg);
            } else {
                var $c = $('<div class="amadex-insight-card"></div>');
                $c.append('<h4></h4>').find('h4').text(title);
                $c.append('<p class="amadex-insight-error"></p>').find('p').text(msg);
                $grid.append($c);
            }
        }

        if (cityOrigin.length >= 3 && cityDest.length >= 3 && /^\d{4}-\d{2}-\d{2}$/.test(depDate)) {
            pending += 1;
            var oneWay = !retDate || !/^\d{4}-\d{2}-\d{2}$/.test(retDate);
            window.AmadexAmadeus.call('flight_price_analysis', {
                originIataCode: cityOrigin,
                destinationIataCode: cityDest,
                departureDate: depDate,
                currencyCode: (flightData.price && flightData.price.currency) || 'USD',
                oneWay: oneWay
            }).done(function (resp) {
                if (!resp || !resp.success || !resp.data) {
                    addError('Price analysis', (resp && resp.data && resp.data.message) || 'Unavailable');
                } else {
                    addCard('Price analysis', 'Amadeus itinerary price metrics for this route and date.', resp.data, 'price_analysis');
                }
            }).fail(function (xhr) {
                var msg = Ui && Ui.friendlyAjaxError ? Ui.friendlyAjaxError(xhr, 'Request failed') : ((xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) || 'Request failed');
                addError('Price analysis', msg);
            }).always(doneOne);
        }

        var carrier = (first.carrier_code || first.carrierCode || '').toString().toUpperCase();
        var fn = (first.number || first.flight_number || '').toString().replace(/\D/g, '');
        var ac = (typeof first.aircraft === 'string' ? first.aircraft : (first.aircraft && (first.aircraft.code || first.aircraft_code)) || '').toString();
        var dur = first.duration || itineraries[0].duration || '';
        if (depAt && arrAt && carrier && fn && ac && dur && origin && dest) {
            pending += 1;
            var dDate = depAt.split('T')[0];
            var aDate = arrAt.split('T')[0];
            window.AmadexAmadeus.call('flight_delay_prediction', {
                originLocationCode: origin,
                destinationLocationCode: dest,
                departureDate: dDate,
                departureTime: isoTime(depAt),
                arrivalDate: aDate,
                arrivalTime: isoTime(arrAt),
                aircraftCode: ac,
                carrierCode: carrier,
                flightNumber: fn,
                duration: dur
            }).done(function (resp) {
                if (!resp || !resp.success || !resp.data) {
                    addError('Delay prediction', (resp && resp.data && resp.data.message) || 'Unavailable');
                } else {
                    addCard('Delay prediction', 'Machine-learning estimate from Amadeus.', resp.data, 'delay_prediction');
                }
            }).fail(function (xhr) {
                var msg = Ui && Ui.friendlyAjaxError ? Ui.friendlyAjaxError(xhr, 'Request failed') : ((xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) || 'Request failed');
                addError('Delay prediction', msg);
            }).always(doneOne);
        }

        if (/^\d{4}-\d{2}-\d{2}$/.test(depDate) && /^\d{4}-\d{2}-\d{2}$/.test(retDate) && cityOrigin.length >= 2 && cityDest.length >= 2) {
            pending += 1;
            var searchDate = new Date().toISOString().slice(0, 10);
            window.AmadexAmadeus.call('trip_purpose_prediction', {
                originLocationCode: cityOrigin,
                destinationLocationCode: cityDest,
                departureDate: depDate,
                returnDate: retDate,
                searchDate: searchDate
            }).done(function (resp) {
                if (!resp || !resp.success || !resp.data) {
                    addError('Trip purpose', (resp && resp.data && resp.data.message) || 'Unavailable');
                } else {
                    addCard('Trip purpose', 'Business vs leisure forecast.', resp.data, 'trip_purpose');
                }
            }).fail(function (xhr) {
                var msg = Ui && Ui.friendlyAjaxError ? Ui.friendlyAjaxError(xhr, 'Request failed') : ((xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) || 'Request failed');
                addError('Trip purpose', msg);
            }).always(doneOne);
        }

        if (flightData.rawOffer && flightData.rawOffer.itineraries) {
            pending += 1;
            window.AmadexAmadeus.call('detailed_flight_pricing', { flightOffer: flightData.rawOffer })
                .done(function (resp) {
                    if (!resp || !resp.success || !resp.data) {
                        addError('Fare rules (priced)', (resp && resp.data && resp.data.message) || 'Unavailable');
                        return;
                    }
                    var pr = resp.data.parsed_fare_rules || resp.data.parsedFareRules;
                    var payload = pr || resp.data;
                    addCard('Fare rules (priced)', pr ? 'From Flight Offers Price with detailed fare rules.' : 'Flight Offers Price response (summary).', payload, 'detailed_pricing');
                })
                .fail(function (xhr) {
                    var msg = Ui && Ui.friendlyAjaxError ? Ui.friendlyAjaxError(xhr, 'Request failed') : ((xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) || 'Request failed');
                    addError('Fare rules (priced)', msg);
                })
                .always(doneOne);
        }

        if (pending === 0) {
            $root.find('.amadex-insights-loading').remove();
            $grid.append('<p class="amadex-muted">No insights could be requested for this flight (missing dates or segment data).</p>');
        }
    }

    /**
     * Show flight details modal when Select is clicked
     * Matches the exact design from XD reference
     */
    function showFlightDetailsModal(flightData) {
        if (!flightData) {
            return;
        }
        
        const modal = $('#amadex-flight-details-modal');
        const content = $('#amadex-flight-details-content');
        const itineraries = flightData.itineraries || [];
        
        if (!itineraries.length) {
            content.html('<p class="amadex-empty-details">No itinerary information available for this flight.</p>');
            modal.show();
            return;
        }
        
        const searchData = getStoredSearchData();
        const cabinClass = getCabinDisplayName(searchData.cabin || getCabinCodeFromFlight(flightData) || 'ECONOMY');
        const serializedFlight = JSON.stringify(flightData);
        const priceCurrency = flightData.price?.currency || 'USD';
        
        // Get airline code for markup calculation
        const airlineCode = (flightData.validatingAirlineCodes && flightData.validatingAirlineCodes[0]) || 
                           (flightData.validating_airline_codes && flightData.validating_airline_codes[0]) || 
                           '';
        
        // Get original price from flight data
        const originalPrice = parseFloat(flightData.price?.grandTotal || flightData.price?.total || 0);
        
        // Apply markup to match results page pricing
        // Pass flightData to check if Pricing Rules Engine is enabled
        const totalPrice = calculatePriceWithMarkup(originalPrice, airlineCode, flightData);
        
        // Get user's selected currency from regional settings
        let selectedCurrency = priceCurrency; // Default to flight currency
        try {
            // Priority 1: Check localStorage (regional settings)
            const savedSettings = localStorage.getItem('amadex_regional_settings');
            if (savedSettings) {
                const settings = JSON.parse(savedSettings);
                if (settings.currency) {
                    selectedCurrency = settings.currency;
                }
            }
            
            // Priority 2: Check sessionStorage
            if (selectedCurrency === priceCurrency && typeof sessionStorage !== 'undefined') {
                const sessionCurrency = sessionStorage.getItem('amadex_selected_currency');
                if (sessionCurrency) {
                    selectedCurrency = sessionCurrency;
                }
            }
        } catch (e) {
            console.error('Error reading currency settings:', e);
        }
        
        // Calculate discount (12% or max $90) based on marked-up price
        const discountAmount = totalPrice ? Math.min(totalPrice * 0.12, 90) : 0;
        
        // Convert prices to selected currency if different
        let displayPrice = totalPrice;
        let displayDiscount = discountAmount;
        
        // Convert prices if currency is different
        if (selectedCurrency !== priceCurrency && selectedCurrency !== 'USD' && typeof AmadexConfig !== 'undefined' && AmadexConfig.currency && typeof convertPricesToCurrency === 'function') {
            // Convert both price and discount
            Promise.all([
                convertPricesToCurrency(totalPrice, 0, priceCurrency, selectedCurrency),
                convertPricesToCurrency(discountAmount, 0, priceCurrency, selectedCurrency)
            ])
            .then(function(results) {
                displayPrice = results[0].base;
                displayDiscount = results[1].base;
                
                // Update the price display in modal
                const $priceElement = $('#amadex-flight-details-modal .amadex-flight-price');
                if ($priceElement.length) {
                    const formatted = formatPrice(displayPrice, selectedCurrency);
                    // formatPrice already cleans duplicates, but double-check for safety
                    const cleaned = cleanDuplicateCurrencySymbols(formatted);
                    $priceElement.text(cleaned);
                }
                
                // Update discount badge
                const $discountBadge = $('#amadex-flight-details-modal .amadex-discount-badge');
                if ($discountBadge.length && displayDiscount > 0) {
                    const formatted = formatPrice(displayDiscount, selectedCurrency);
                    // formatPrice already cleans duplicates, but double-check for safety
                    const cleaned = cleanDuplicateCurrencySymbols(formatted);
                    $discountBadge.text(cleaned + ' Off');
                }
            })
            .catch(function(error) {
                console.error('Currency conversion failed in modal:', error);
            });
        }
        
        // Format prices with selected currency (initial display)
        // formatPrice already handles cleanup internally, but ensure clean output
        let formattedPrice = formatPrice(displayPrice, selectedCurrency);
        formattedPrice = cleanDuplicateCurrencySymbols(formattedPrice);
        
        // Format discount with selected currency
        let discountFormatted = formatPrice(displayDiscount || discountAmount, selectedCurrency);
        discountFormatted = cleanDuplicateCurrencySymbols(discountFormatted);
        const discountLabel = discountAmount > 0 ? `<span class="amadex-discount-badge">${discountFormatted} Off</span>` : '';
        
        // Create tabs for each itinerary (e.g., "SMF - DCA", "DCA - SMF") plus Fare Rules
        const itineraryTabs = itineraries.map((itinerary, index) => createItineraryTabLabel(itinerary, index)).join('');
        const itineraryPanels = itineraries.map((itinerary, index) => buildItineraryPanel(itinerary, index, flightData, cabinClass)).join('');
        const fareRulesPanel = buildFareRulesPanel(flightData, cabinClass);
        const insightsPanel = `
            <div class="amadex-flight-tab-panel" id="amadeus-insights">
                <div id="amadex-amadeus-insights-root" class="amadex-amadeus-insights-root"></div>
            </div>`;
        
        // Get baggage information from API
        const allowances = getAllowanceInfo(flightData, cabinClass);
        
        const modalHtml = `
            <div class="amadex-flight-details-modern">
                <div class="amadex-flight-tabs">
                    ${itineraryTabs}
                    <button class="amadex-flight-tab" data-tab="fare-rules">Fare Rules</button>
                    <button class="amadex-flight-tab" data-tab="amadeus-insights">Insights</button>
                        </div>
                <div class="amadex-flight-tab-panels">
                    ${itineraryPanels}
                    ${fareRulesPanel}
                    ${insightsPanel}
                    </div>
                <div class="amadex-flight-footer-bar">
                    <div class="amadex-flight-price-wrap">
                        <span class="amadex-price-label">Total Trip Cost</span>
                        <div class="amadex-price-row">
                            <span class="amadex-flight-price">${formattedPrice}</span>
                            ${discountLabel}
                            </div>
                        </div>
                    <button class="amadex-flight-continue-btn amadex-book-now-btn" data-flight-data='${serializedFlight}'>
                        Continue
                    </button>
                </div>
            </div>
        `;
        
        content.html(modalHtml);
        modal.css('display', 'flex').addClass('show').hide().fadeIn(400);
        $('body').css('overflow', 'hidden');
        initFlightDetailsTabs();
        loadAmadeusInsightsPanel(flightData, searchData);
        
        // Smooth scroll to top of modal content
        setTimeout(() => {
            modal.find('.amadex-flight-details-body').scrollTop(0);
        }, 100);
    }
    
    function createItineraryTabLabel(itinerary, index) {
                const segments = itinerary.segments || [];
                const firstSeg = segments[0];
                const lastSeg = segments[segments.length - 1];
        const originCode = firstSeg?.departure?.iataCode || firstSeg?.departure?.iata_code || '---';
        const destCode = lastSeg?.arrival?.iataCode || lastSeg?.arrival?.iata_code || '---';
        return `<button class="amadex-flight-tab ${index === 0 ? 'is-active' : ''}" data-tab="itinerary-${index}">
            ${originCode} - ${destCode}
        </button>`;
    }
    
    function buildItineraryPanel(itinerary, index, flightData, cabinClass) {
        const segments = itinerary.segments || [];
        if (!segments.length) return '';
        
        const firstSeg = segments[0];
        const lastSeg = segments[segments.length - 1];
        const originCode = firstSeg.departure?.iataCode || firstSeg.departure?.iata_code || '---';
        const destCode = lastSeg.arrival?.iataCode || lastSeg.arrival?.iata_code || '---';
        const originInfo = getAirportInfo(originCode);
        const destInfo = getAirportInfo(destCode);
        
        // Format duration
        const durationLabel = itinerary.duration ? formatDurationDisplay(itinerary.duration) : calculateTotalDuration(segments);
        
        // Get baggage info
        const allowances = getAllowanceInfo(flightData, cabinClass);
        
        // Get airline info from first segment
        const airlineCode = firstSeg.carrierCode || firstSeg.carrier_code || (flightData.validating_airline_codes ? flightData.validating_airline_codes[0] : 'N/A');
        const airlineName = getAirlineName(airlineCode);
        const airlineLogo = getAirlineLogo(airlineCode);
        
        // Format departure date - "5 Nov, 25 Tuesday"
        const depDate = new Date(firstSeg.departure?.at);
        const departureDateFormatted = formatSegmentDate(depDate);
        
        // Calculate stops
        const stopsCount = segments.length - 1;
        const stopsLabel = stopsCount === 0 ? 'Non Stop' : `${stopsCount} Stop${stopsCount > 1 ? 's' : ''}`;
        
        // Calculate connecting flight info if multiple segments
        let connectingBanner = '';
        if (segments.length > 1) {
            const firstArrival = new Date(segments[0].arrival.at);
            const secondDeparture = new Date(segments[1].departure.at);
            const layoverMs = secondDeparture - firstArrival;
            const layoverHours = Math.floor(layoverMs / (1000 * 60 * 60));
            const layoverMinutes = Math.floor((layoverMs % (1000 * 60 * 60)) / (1000 * 60));
            const layoverTime = `${layoverHours.toString().padStart(2, '0')}h:${layoverMinutes.toString().padStart(2, '0')}m`;
            const connectingAirport = segments[0].arrival.iataCode || segments[0].arrival.iata_code || '---';
            const connectingAirportInfo = getAirportInfo(connectingAirport);
            const connectingAirportName = connectingAirportInfo.airport || connectingAirportInfo.city || connectingAirport;
            // connectingBanner = `
            //     <div class="amadex-connecting-banner">
            //         <span class="amadex-connecting-text">Change planes at ${connectingAirportName}</span>
            //         <span class="amadex-connecting-time">Connecting Time: ${layoverTime}</span>
            //     </div>
            // `;
        }
        
        return `
            <div class="amadex-flight-tab-panel ${index === 0 ? 'is-active' : ''}" id="itinerary-${index}">
                ${connectingBanner}
                
                <!-- Flight Segments with Layovers -->
                <div class="amadex-flight-segment-list">
                    ${buildSegmentRows(segments, flightData, cabinClass, airlineCode, airlineName, airlineLogo)}
                </div>
                    
                <!-- Baggage Information Section -->
                <div class="amadex-baggage-info-section">
                    <!-- Check In Baggage -->
                    <div class="amadex-baggage-item">
                        <div class="amadex-baggage-header-row">
                            <span class="amadex-baggage-label">Check In Baggage</span>
                            <div class="amadex-baggage-status ${allowances.baggageIncluded ? 'amadex-baggage-included' : 'amadex-baggage-chargeable'}">
                                ${allowances.baggageIncluded ? `
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="amadex-baggage-status-icon">
                                        <circle cx="12" cy="12" r="10" fill="#0E7D3F"/>
                                        <path d="M9 12l2 2 4-4" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <span class="amadex-baggage-status-text">Included</span>
                                ` : `
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="amadex-baggage-status-icon">
                                        <circle cx="12" cy="12" r="10" fill="#DC2626"/>
                                        <path d="M15 9l-6 6M9 9l6 6" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <span class="amadex-baggage-status-text">Chargeable</span>
                                `}
                            </div>
                        </div>
                        <div class="amadex-baggage-details">
                            ${allowances.baggageIncluded ? allowances.baggageDetails || allowances.baggage : 'Add baggage at checkout'}
                        </div>
                    </div>
                    
                    <!-- Cabin Baggage -->
                    <div class="amadex-baggage-item">
                        <div class="amadex-baggage-header-row">
                            <span class="amadex-baggage-label">Cabin Baggage</span>
                            <div class="amadex-baggage-status ${allowances.cabinIncluded ? 'amadex-baggage-included' : 'amadex-baggage-chargeable'}">
                                ${allowances.cabinIncluded ? `
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="amadex-baggage-status-icon">
                                        <circle cx="12" cy="12" r="10" fill="#0E7D3F"/>
                                        <path d="M9 12l2 2 4-4" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <span class="amadex-baggage-status-text">Included</span>
                                ` : `
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="amadex-baggage-status-icon">
                                        <circle cx="12" cy="12" r="10" fill="#DC2626"/>
                                        <path d="M15 9l-6 6M9 9l6 6" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <span class="amadex-baggage-status-text">Chargeable</span>
                                `}
                            </div>
                        </div>
                        <div class="amadex-baggage-details">
                            ${allowances.cabinIncluded ? allowances.cabinDetails || allowances.cabin : 'Add cabin baggage at checkout'}
                        </div>
                    </div>
                </div>
                                
                <!-- Amenities Section -->
                ${buildAmenitiesSection(flightData)}
            </div>
        `;
    }
    
    function buildAllowancePills(allowances) {
        return `
            <div class="amadex-baggage-info-section">
                <div class="amadex-baggage-item">
                    <span class="amadex-baggage-label">Baggage Adult</span>
                    <strong class="amadex-baggage-value">${allowances.baggage}</strong>
                                </div>
                <div class="amadex-baggage-item">
                    <span class="amadex-baggage-label">Check In</span>
                    <strong class="amadex-baggage-value">${allowances.checkin}</strong>
                            </div>
                <div class="amadex-baggage-item">
                    <span class="amadex-baggage-label">Cabin</span>
                    <strong class="amadex-baggage-value">${allowances.cabin}</strong>
                        </div>
                    </div>
        `;
    }
    
    function buildAmenitiesSection(flightData) {
        const wifiIcon = `<svg xmlns="http://www.w3.org/2000/svg" width="13" height="11" viewBox="0 0 13 11" class="amadex-wifi-icon">
            <g id="Group_283" data-name="Group 283" transform="translate(0 -45.5)">
                <circle id="Ellipse_24" data-name="Ellipse 24" cx="0.814" cy="0.814" r="0.814" transform="translate(5.502 54.871)"/>
                <path id="Path_106" data-name="Path 106" d="M12.864,48.544A8.286,8.286,0,0,0,6.5,45.5,8.286,8.286,0,0,0,.137,48.544a.6.6,0,0,0,.025.787A.477.477,0,0,0,.88,49.3,7.32,7.32,0,0,1,6.5,46.613a7.32,7.32,0,0,1,5.621,2.69.477.477,0,0,0,.718.028A.6.6,0,0,0,12.864,48.544Z" transform="translate(0)"/>
                <path id="Path_107" data-name="Path 107" d="M81.793,163.66a5.486,5.486,0,0,0-8.653,0,.613.613,0,0,0,0,.787.447.447,0,0,0,.691,0,4.592,4.592,0,0,1,7.267,0,.447.447,0,0,0,.691,0A.613.613,0,0,0,81.793,163.66Zm-1.954,2.305a3.328,3.328,0,0,0-2.377-1.163h-.036a3.328,3.328,0,0,0-2.377,1.163.613.613,0,0,0,0,.787.447.447,0,0,0,.691,0,2.412,2.412,0,0,1,1.689-.841h.036a2.412,2.412,0,0,1,1.689.841.447.447,0,0,0,.691,0A.613.613,0,0,0,79.838,165.965Z" transform="translate(-70.966 -113.173)"/>
            </g>
        </svg>`;
        
        const acPowerIcon = `<svg xmlns="http://www.w3.org/2000/svg" width="11.221" height="14" viewBox="0 0 11.221 14" class="amadex-ac-power-icon">
            <g id="Group_285" data-name="Group 285" transform="translate(-408.462 -256)">
                <g id="Group_284" data-name="Group 284" transform="translate(408.462 256)">
                    <path id="Path_108" data-name="Path 108" d="M415.665,593.282h-6.513a.69.69,0,0,1-.69-.69v-.133a.69.69,0,0,1,.69-.69h6.513a.69.69,0,0,1,.69.69v.133A.69.69,0,0,1,415.665,593.282Zm-6.513-1.075a.252.252,0,0,0-.252.252v.133a.252.252,0,0,0,.252.252h6.513a.252.252,0,0,0,.252-.252v-.133a.252.252,0,0,0-.252-.252Z" transform="translate(-408.462 -588.71)" fill="currentColor"/>
                    <path id="Path_109" data-name="Path 109" d="M492.586,715.059h-.742A2.841,2.841,0,0,1,489,712.218v-2.31a.219.219,0,0,1,.219-.219h5.987a.219.219,0,0,1,.219.219v2.31a2.841,2.841,0,0,1-2.841,2.841Zm-3.146-4.933v2.092a2.406,2.406,0,0,0,2.4,2.4h.742a2.406,2.406,0,0,0,2.4-2.4v-2.092Z" transform="translate(-488.269 -705.554)" fill="currentColor"/>
                    <path id="Path_110" data-name="Path 110" d="M579.193,266.715a.7.7,0,0,1-.7-.7v-.733a.219.219,0,0,1,.438,0v.733a.261.261,0,1,0,.521,0v-.733a.219.219,0,0,1,.438,0v.733A.7.7,0,0,1,579.193,266.715Zm-1.2-7.217a.219.219,0,0,1-.219-.219v-2.566a.276.276,0,0,0-.551,0v2.566a.219.219,0,0,1-.437,0v-2.566a.713.713,0,0,1,1.426,0v2.566A.219.219,0,0,1,577.989,259.5Zm3.4,0a.219.219,0,0,1-.219-.219v-2.566a.276.276,0,0,0-.551,0v2.566a.219.219,0,0,1-.437,0v-2.566a.713.713,0,0,1,1.426,0v2.566A.219.219,0,0,1,581.386,259.5Zm-1.506,4.544a.218.218,0,0,1-.155-.064.753.753,0,0,0-1.065,0,.219.219,0,0,1-.309-.309,1.191,1.191,0,0,1,1.684,0,.219.219,0,0,1-.155.373Zm.52-.737a.218.218,0,0,1-.155-.064,1.489,1.489,0,0,0-2.105,0,.219.219,0,1,1-.309-.309,1.926,1.926,0,0,1,2.724,0,.219.219,0,0,1-.155.373Z" transform="translate(-575.247 -256)" fill="currentColor"/>
                    <path id="Path_111" data-name="Path 111" d="M631.62,471.271a.218.218,0,0,1-.155-.064,2.226,2.226,0,0,0-3.145,0,.219.219,0,1,1-.309-.309,2.661,2.661,0,0,1,3.763,0,.219.219,0,0,1-.155.373Zm.679,7.431a2.624,2.624,0,0,1-2.625-2.624V475.2a.219.219,0,0,1,.438,0v.879a2.187,2.187,0,1,0,4.374,0v-8.115a1.341,1.341,0,0,1,2.682,0v1.886a.219.219,0,0,1-.437,0v-1.886a.9.9,0,1,0-1.807,0v8.115A2.624,2.624,0,0,1,632.3,478.7Z" transform="translate(-625.947 -464.702)" fill="currentColor"/>
                </g>
            </g>
        </svg>`;
        
        const usbPowerIcon = `<svg xmlns="http://www.w3.org/2000/svg" width="8.261" height="13.606" viewBox="0 0 8.261 13.606" class="amadex-usb-power-icon">
            <g id="Group_286" data-name="Group 286" transform="translate(-8084.437 -1135)">
                <path id="Path_112" data-name="Path 112" d="M29.235,8.68h-2.43a.243.243,0,0,1-.243-.243V6.493a.243.243,0,0,1,.243-.243h2.43a.243.243,0,0,1,.243.243V8.437a.243.243,0,0,1-.243.243Zm-2.187-.486h1.944V6.736H27.049Z" transform="translate(8058.36 1128.75)" fill="currentColor"/>
                <path id="Path_113" data-name="Path 113" d="M26.6,24.824H24.132a.7.7,0,0,1-.695-.695V18.993a.243.243,0,0,1,.243-.243h3.4a.243.243,0,0,1,.243.243v5.1a.729.729,0,0,1-.729.729Zm-2.673-5.588v4.893a.211.211,0,0,0,.209.209H26.6a.243.243,0,0,0,.243-.243V19.236Z" transform="translate(8061 1118.194)" fill="currentColor"/>
                <path id="Path_114" data-name="Path 114" d="M30.173,11.423H29.93a.243.243,0,1,1,0-.486h.243a.243.243,0,0,1,0,.486Z" transform="translate(8055.722 1124.792)" fill="currentColor"/>
                <path id="Path_115" data-name="Path 115" d="M37.986,11.423h-.243a.243.243,0,1,1,0-.486h.243a.243.243,0,0,1,0,.486Z" transform="translate(8049.124 1124.792)" fill="currentColor"/>
                <path id="Path_116" data-name="Path 116" d="M30.783,56.388H30.54a.848.848,0,0,1-.853-.848v-.61a.243.243,0,0,1,.243-.243h1.458a.243.243,0,0,1,.243.243v.61a.848.848,0,0,1-.848.848Zm-.61-1.215v.367a.362.362,0,0,0,.362.362h.243a.362.362,0,0,0,.367-.362v-.367Z" transform="translate(8055.722 1087.845)" fill="currentColor"/>
                <path id="Path_117" data-name="Path 117" d="M40.692,44.927a.243.243,0,0,1-.243-.243V37.638a1.215,1.215,0,0,0-2.43,0v5.467a1.822,1.822,0,0,1-3.645,0V40.311a.243.243,0,0,1,.486,0v2.794a1.336,1.336,0,0,0,2.673,0V37.638a1.7,1.7,0,1,1,3.4,0v7.046a.243.243,0,0,1-.243.243Z" transform="translate(8051.763 1103.679)" fill="currentColor"/>
            </g>
        </svg>`;
        
        const personalDeviceIcon = `<svg xmlns="http://www.w3.org/2000/svg" width="10" height="14" viewBox="0 0 10 14" class="amadex-personal-device-icon">
            <g id="Group_289" data-name="Group 289" transform="translate(-985 -818)">
                <g id="Group_287" data-name="Group 287" transform="translate(970 816)">
                    <path id="Path_118" data-name="Path 118" d="M23.529,2H16.471A1.346,1.346,0,0,0,15,3.167V14.833A1.346,1.346,0,0,0,16.471,16h7.059A1.346,1.346,0,0,0,25,14.833V3.167A1.346,1.346,0,0,0,23.529,2Zm.882,12.833a.81.81,0,0,1-.882.7H16.471a.81.81,0,0,1-.882-.7V3.167a.81.81,0,0,1,.882-.7h1.288l.344.546a.926.926,0,0,0,.791.387h2.212a.926.926,0,0,0,.791-.387l.344-.546h1.288a.81.81,0,0,1,.882.7Z" fill="currentColor"/>
                    <path id="Path_119" data-name="Path 119" d="M29.041,56H26.276a.276.276,0,1,0,0,.553h2.765a.276.276,0,0,0,0-.553Z" transform="translate(-7.659 -41.659)" fill="currentColor"/>
                </g>
                <path id="Polygon_5" data-name="Polygon 5" d="M1.584.624a.5.5,0,0,1,.832,0l1.066,1.6A.5.5,0,0,1,3.066,3H.934a.5.5,0,0,1-.416-.777Z" transform="translate(992 823) rotate(90)" fill="currentColor"/>
            </g>
        </svg>`;
        
        const liveTvIcon = `<svg xmlns="http://www.w3.org/2000/svg" width="14.401" height="11.849" viewBox="0 0 14.401 11.849" class="amadex-live-tv-icon">
            <g id="Group_288" data-name="Group 288" transform="translate(-9 -52.777)">
                <path id="Path_120" data-name="Path 120" d="M22.266,52.777H10.136A1.137,1.137,0,0,0,9,53.913v7.881a1.137,1.137,0,0,0,1.136,1.136h3.239a4.13,4.13,0,0,0-2.183,1.377.2.2,0,0,0,.336.231A3.537,3.537,0,0,1,13.259,63.4a8.884,8.884,0,0,1,2.942-.467,8.883,8.883,0,0,1,2.942.467,3.537,3.537,0,0,1,1.731,1.142.2.2,0,1,0,.336-.231,4.13,4.13,0,0,0-2.183-1.377h3.239A1.137,1.137,0,0,0,23.4,61.794V53.913A1.137,1.137,0,0,0,22.266,52.777Zm-12.13.408h12.13a.728.728,0,0,1,.727.727v7.541H9.408V53.913a.728.728,0,0,1,.727-.727Zm12.13,9.336H10.136a.728.728,0,0,1-.724-.66H22.99A.728.728,0,0,1,22.266,62.521ZM15.336,57.7v1.8a.2.2,0,0,1-.408,0V57.7a.2.2,0,0,1,.408,0Zm-2.045,1.8V57.7a.2.2,0,0,1,.408,0v1.6h.646a.2.2,0,1,1,0,.408H13.5A.2.2,0,0,1,13.291,59.5Zm4.573,0V57.7a.2.2,0,0,1,.2-.2h.838a.2.2,0,1,1,0,.408h-.634v.493h.38a.2.2,0,1,1,0,.408h-.38V59.3h.634a.2.2,0,1,1,0,.408h-.838a.2.2,0,0,1-.2-.2Zm-.427-1.739-.589,1.8a.2.2,0,0,1-.388,0l-.589-1.8a.2.2,0,0,1,.388-.127l.4,1.209.4-1.209a.2.2,0,1,1,.388.127Zm-2.724-2.5a.2.2,0,0,1,0-.289,2.106,2.106,0,0,1,2.975,0,.2.2,0,0,1-.289.289,1.7,1.7,0,0,0-2.4,0A.2.2,0,0,1,14.713,55.265Zm.952.663a.2.2,0,1,1-.289-.289,1.167,1.167,0,0,1,1.649,0,.2.2,0,0,1-.289.289.759.759,0,0,0-1.072,0Zm.536.256a.262.262,0,1,1-.262.262A.263.263,0,0,1,16.2,56.185Z" fill-rule="evenodd" fill="currentColor"/>
            </g>
        </svg>`;
        
        const amenities = [
            { icon: wifiIcon, name: 'High-speed Wi-Fi', available: true },
            { icon: acPowerIcon, name: 'AC power', available: true },
            { icon: usbPowerIcon, name: 'USB power', available: true },
            { icon: personalDeviceIcon, name: 'Personal device streaming', available: true },
            { icon: liveTvIcon, name: 'Live TV', available: true }
        ];
        
        return `
            <div class="amadex-amenities-section">
                <h5 class="amadex-amenities-title">Amenities</h5>
                <div class="amadex-amenities-grid">
                    ${amenities.map(amenity => `
                        <div class="amadex-amenity-item">
                            <span class="amadex-amenity-icon">${amenity.icon}</span>
                            <span class="amadex-amenity-name">${amenity.name}</span>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }
    
    function buildSegmentRows(segments, flightData, cabinClass, airlineCode, airlineName, airlineLogo) {
        if (!segments || !segments.length) return '';
        
        return segments.map((segment, index) => {
            const departureDate = new Date(segment.departure.at);
            const arrivalDate = new Date(segment.arrival.at);
            const depCode = segment.departure.iataCode || segment.departure.iata_code || '---';
            const arrCode = segment.arrival.iataCode || segment.arrival.iata_code || '---';
            const depInfo = getAirportInfo(depCode);
            const arrInfo = getAirportInfo(arrCode);
            
            // Calculate segment duration
            const duration = formatDurationDisplay(segment.duration) || calculateTotalDuration([segment]);
            
            // Calculate layover if not the last segment
            const nextSegment = segments[index + 1];
            const layover = nextSegment ? calculateLayover(segment, nextSegment) : '';
            
            // Get airline info for this segment (use provided or fallback)
            const carrierCode = segment.carrierCode || segment.carrier_code || airlineCode;
            const segAirlineName = carrierCode === airlineCode ? airlineName : getAirlineName(carrierCode);
            const segAirlineLogo = carrierCode === airlineCode ? airlineLogo : getAirlineLogo(carrierCode);
            const segAirlineCode = carrierCode || airlineCode || '';
            const flightNum = segment.number || segment.flight_number || '';
            const fullFlightNum = carrierCode + '-' + flightNum;
            
            // Get terminal information from API
            const depTerminal = segment.departure?.terminal || '';
            const arrTerminal = segment.arrival?.terminal || '';
            
            // Format dates for display - "5 Nov, 25 Tuesday"
            const depDateStr = formatSegmentDate(departureDate);
            const arrDateStr = formatSegmentDate(arrivalDate);
            
            // Check if arrival is next day
            const dayDiff = arrivalDate.getDate() !== departureDate.getDate() || 
                          arrivalDate.getMonth() !== departureDate.getMonth() ||
                          arrivalDate.getFullYear() !== departureDate.getFullYear();
            
            // Format airport names (e.g., "Sacramento Metro (SMF)")
            const depAirportName = depInfo.airport || depCode;
            const arrAirportName = arrInfo.airport || arrCode;
            const depCityName = depInfo.city || depCode;
            const arrCityName = arrInfo.city || arrCode;
            
            // Format times (e.g., "20:21", "00:21")
            const depTimeStr = formatTime(departureDate);
            const arrTimeStr = formatTime(arrivalDate);
            
            // Get aircraft type if available
            const aircraft = segment.aircraft?.code || '';
            const aircraftName = aircraft ? getAircraftName(aircraft) : '';
            
            // Get seat information if available
            const seatNumber = segment.numberOfStops || 0;
            const stopsInfo = seatNumber > 0 ? `${seatNumber} Stop${seatNumber > 1 ? 's' : ''}` : 'Non-stop';
            
            return `
                <div class="amadex-segment-detailed">
                    <!-- Airline Header -->
                    <div class="amadex-segment-airline-header">
                        <div class="amadex-segment-airline-info">
                            <img src="${segAirlineLogo}" alt="${segAirlineName}" class="amadex-segment-airline-logo" onerror="this.onerror=null; this.src='${getAirlineLogoFallback(segAirlineCode)}';">
                            <div class="amadex-segment-airline-text">
                                <span class="amadex-segment-airline-name">${segAirlineName}</span>
                                <span class="amadex-segment-flight-number">Flight ${fullFlightNum}</span>
                            </div>
                        </div>
                        <span class="amadex-segment-travel-class">${cabinClass}</span>
                    </div>
                    
                    <!-- Flight Timeline -->
                    <div class="amadex-segment-timeline-wrapper">
                        <div class="amadex-segment-timeline-row">
                            <!-- Departure Information -->
                            <div class="amadex-segment-departure-section">
                                <div class="amadex-segment-time-main">${depTimeStr}</div>
                                <div class="amadex-segment-airport-code">${depCode}</div>
                                <div class="amadex-segment-airport-name">${depCityName}</div>
                                <div class="amadex-segment-date-info">
                                    <span class="amadex-date-text">${depDateStr}</span>
                                    ${depTerminal ? `<span class="amadex-terminal-text">${depCode} • Terminal ${depTerminal}</span>` : `<span class="amadex-terminal-text">${depCode}</span>`}
                                </div>
                            </div>
                            
                            <!-- Flight Duration Line -->
                            <div class="amadex-segment-duration-line">
                                <div class="amadex-segment-line-vertical"></div>
                                <div class="amadex-segment-duration-badge">
                            
                                    <span class="amadex-duration-text">${duration}</span>
                                </div>
                                <div class="amadex-segment-line-vertical"></div>
                                <svg xmlns="http://www.w3.org/2000/svg" id="group-480" data-name="Group 480" width="18" height="12.33" viewBox="0 0 18 12.33">
  <path id="Path_216" data-name="Path 216" d="M17.885,5.786a2.3,2.3,0,0,0-1.307-.839,18.631,18.631,0,0,0-2.519-.162,2.491,2.491,0,0,1-.276-.016,1.859,1.859,0,0,1-.216-.221L10.23.409A2.063,2.063,0,0,0,9.918.086,1.293,1.293,0,0,0,9.3.006L9.211.013a1.294,1.294,0,0,0-.454.1c-.063.037-.255.15-.078.7,0,0,1.272,3.939,1.277,3.957-.019,0-1.83,0-1.83,0a2.566,2.566,0,0,1-.281-.015,1.82,1.82,0,0,1-.223-.217L6.446,3.145a2.032,2.032,0,0,0-.328-.316A.9.9,0,0,0,5.63,2.78a.832.832,0,0,0-.3.119c-.146.106-.171.337-.074.688.264.952.641,2.362.669,2.578C5.9,6.38,5.52,7.791,5.256,8.742A1.472,1.472,0,0,0,5.19,9.2c.042.27.424.345.44.348a1.163,1.163,0,0,0,.308.014c.2-.024.458-.32.508-.379L7.623,7.793a2.508,2.508,0,0,1,.191-.2,1.9,1.9,0,0,1,.313-.029H9.956c0,.018-1.276,3.957-1.276,3.957a1.322,1.322,0,0,0-.083.457c.027.2.234.31.615.339l.091.007c.058,0,.143.009.227.009a1.172,1.172,0,0,0,.221-.016c.19-.041.451-.359.48-.4L13.568,7.78a2.559,2.559,0,0,1,.185-.206,1.831,1.831,0,0,1,.306-.03,18.627,18.627,0,0,0,2.519-.162,2.3,2.3,0,0,0,1.307-.839.678.678,0,0,0,0-.755ZM3.589,3.554H2.19a.548.548,0,0,0,0,1.1h1.4a.548.548,0,0,0,0-1.1Zm0,4.269H2.19a.548.548,0,0,0,0,1.1h1.4a.548.548,0,0,0,0-1.1Zm0-2.134H.539a.548.548,0,0,0,0,1.1H3.589a.548.548,0,0,0,0-1.1Zm0,0" transform="translate(0 -0.001)" fill="#707070"></path>
</svg>
                            </div>
                            
                            <!-- Arrival Information -->
                            <div class="amadex-segment-arrival-section">
                                <div class="amadex-segment-time-main">
                                    ${arrTimeStr}
                                    ${dayDiff ? '<sup class="amadex-day-diff">+1</sup>' : ''}
                                </div>
                                <div class="amadex-segment-airport-code">${arrCode}</div>
                                <div class="amadex-segment-airport-name">${arrCityName}</div>
                                <div class="amadex-segment-date-info">
                                    <span class="amadex-date-text">${arrDateStr}</span>
                                    ${arrTerminal ? `<span class="amadex-terminal-text">${arrCode} • Terminal ${arrTerminal}</span>` : `<span class="amadex-terminal-text">${arrCode}</span>`}
                                </div>
                            </div>
                        </div>
                        
                        ${layover && index < segments.length - 1 ? `
                            <div class="amadex-layover-info">
                                <span class="amadex-layover-label">Change planes at ${depCityName}</span>
                                <span class="amadex-layover-duration">Connecting Time: ${layover}</span>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
        }).join('');
    }
    
    function formatSegmentDate(date) {
        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const dayName = days[date.getDay()];
        const day = date.getDate();
        const month = months[date.getMonth()];
        const year = date.getFullYear().toString().slice(-2); // 2-digit year (e.g., 25 instead of 2025)
        return `${day} ${month}, ${year} ${dayName}`;
    }
    
    function buildFareRulesPanel(flightData, cabinClass) {
        const itineraries = flightData.itineraries || [];
        const airlineCode = flightData.validating_airline_codes ? flightData.validating_airline_codes[0] : 'N/A';
        const airlineName = getAirlineName(airlineCode);
        const allowances = getAllowanceInfo(flightData, cabinClass);
        
        // Get parsed fare rules from flight data (if available from Flight Offers Price API)
        const parsedFareRules = flightData.parsed_fare_rules || {};
        const cancellationPolicy = parsedFareRules.cancellation || {};
        const exchangePolicy = parsedFareRules.exchange || parsedFareRules.change || {};
        const noShowPolicy = parsedFareRules.no_show || {};
        
        // Format fee amount with currency
        function formatFee(fee, currency) {
            if (fee === null || fee === undefined || fee === 0) {
                return 'Free';
            }
            const currencySymbol = currency === 'USD' ? '$' : (currency || '$');
            return `${currencySymbol}${parseFloat(fee).toFixed(2)}`;
        }
        
        // Build cancellation policy text
        function buildCancellationText() {
            if (!cancellationPolicy.allowed && cancellationPolicy.not_applicable) {
                return 'Cancellation is not allowed for this fare.';
            }
            if (cancellationPolicy.allowed && (!cancellationPolicy.fee || cancellationPolicy.fee === 0)) {
                const deadline = cancellationPolicy.deadline ? ` within ${cancellationPolicy.deadline}` : '';
                return `Cancellation is allowed${deadline} free of charge.`;
            }
            if (cancellationPolicy.allowed && cancellationPolicy.fee > 0) {
                const feeText = formatFee(cancellationPolicy.fee, cancellationPolicy.currency);
                const deadline = cancellationPolicy.deadline ? ` within ${cancellationPolicy.deadline}` : '';
                return `Cancellation is allowed${deadline} with a fee of ${feeText}.`;
            }
            return 'Cancellation policy details are not available. Please contact customer service for more information.';
        }
        
        // Build change/exchange policy text
        function buildChangeText() {
            if (!exchangePolicy.allowed && exchangePolicy.not_applicable) {
                return 'Changes are not allowed for this fare.';
            }
            if (exchangePolicy.allowed && (!exchangePolicy.fee || exchangePolicy.fee === 0)) {
                const deadline = exchangePolicy.deadline ? ` within ${exchangePolicy.deadline}` : '';
                return `Changes are allowed${deadline} free of charge.`;
            }
            if (exchangePolicy.allowed && exchangePolicy.fee > 0) {
                const feeText = formatFee(exchangePolicy.fee, exchangePolicy.currency);
                const deadline = exchangePolicy.deadline ? ` within ${exchangePolicy.deadline}` : '';
                return `Changes are allowed${deadline} with a fee of ${feeText}. Fare difference may apply.`;
            }
            return 'Change policy details are not available. Please contact customer service for more information.';
        }
        
        // Build no-show policy text
        function buildNoShowText() {
            if (noShowPolicy.policy === 'not_applicable') {
                return 'No-show policy is not applicable.';
            }
            if (noShowPolicy.penalty && noShowPolicy.penalty.amount) {
                const penaltyText = formatFee(noShowPolicy.penalty.amount, noShowPolicy.penalty.currency);
                return `No-show penalty: ${penaltyText}. In case of no-show, this penalty will apply.`;
            }
            if (noShowPolicy.descriptions && noShowPolicy.descriptions.length > 0) {
                return noShowPolicy.descriptions.join(' ');
            }
            return 'In case of no-show, penalties may apply. Please check with the airline for specific no-show policies.';
        }
        
        // Get additional fare information
        const fareBasisCodes = flightData.fare_basis_codes || {};
        const bookingClasses = flightData.booking_classes || {};
        const brandedFares = flightData.branded_fares || {};
        
        // Build fare info section
        let fareInfoHtml = '';
        const hasFareInfo = Object.keys(fareBasisCodes).length > 0 || Object.keys(bookingClasses).length > 0 || Object.keys(brandedFares).length > 0;
        
        if (hasFareInfo) {
            fareInfoHtml = '<div class="amadex-fare-info-section"><h5>Fare Information</h5>';
            
            // Branded fare
            const firstBrandedFare = Object.values(brandedFares)[0];
            if (firstBrandedFare) {
                fareInfoHtml += `<div class="amadex-fare-info-item"><strong>Branded Fare:</strong> ${firstBrandedFare}</div>`;
            }
            
            // Fare basis codes
            const fareBasisList = Object.values(fareBasisCodes).filter(fb => fb);
            if (fareBasisList.length > 0) {
                fareInfoHtml += `<div class="amadex-fare-info-item"><strong>Fare Basis:</strong> ${fareBasisList.join(', ')}</div>`;
            }
            
            // Booking classes
            const bookingClassList = Object.values(bookingClasses).filter(bc => bc);
            if (bookingClassList.length > 0) {
                fareInfoHtml += `<div class="amadex-fare-info-item"><strong>Booking Class:</strong> ${bookingClassList.join(', ')}</div>`;
            }
            
            fareInfoHtml += '</div>';
        }
        
        // Build fare rules for each itinerary
        const fareRulesSections = itineraries.map((itinerary, index) => {
            const segments = itinerary.segments || [];
            const firstSeg = segments[0];
            const lastSeg = segments[segments.length - 1];
            const originCode = firstSeg?.departure?.iataCode || firstSeg?.departure?.iata_code || '---';
            const destCode = lastSeg?.arrival?.iataCode || lastSeg?.arrival?.iata_code || '---';
            
            const cancellationText = buildCancellationText();
            const changeText = buildChangeText();
            const noShowText = buildNoShowText();
            
            return `
                <div class="amadex-fare-rules-route-section">
                    <h4 class="amadex-fare-rules-route-title">${originCode} - ${destCode}</h4>
                    <div class="amadex-fare-rules-buttons">
                        <button class="amadex-fare-rule-btn active" data-rule="cancellation-${index}">Cancellation Policy</button>
                        <button class="amadex-fare-rule-btn" data-rule="change-${index}">Change Policy</button>
                        <button class="amadex-fare-rule-btn" data-rule="noshow-${index}">No-Show Policy</button>
                    </div>
                    <div class="amadex-fare-rules-content">
                        <div class="amadex-fare-rule-text active" id="cancellation-${index}">
                            <p>${cancellationText}</p>
                            ${cancellationPolicy.descriptions && cancellationPolicy.descriptions.length > 0 
                                ? '<ul>' + cancellationPolicy.descriptions.map(desc => `<li>${desc}</li>`).join('') + '</ul>' 
                                : ''}
                        </div>
                        <div class="amadex-fare-rule-text" id="change-${index}">
                            <p>${changeText}</p>
                            ${exchangePolicy.descriptions && exchangePolicy.descriptions.length > 0 
                                ? '<ul>' + exchangePolicy.descriptions.map(desc => `<li>${desc}</li>`).join('') + '</ul>' 
                                : ''}
                        </div>
                        <div class="amadex-fare-rule-text" id="noshow-${index}">
                            <p>${noShowText}</p>
                            ${noShowPolicy.descriptions && noShowPolicy.descriptions.length > 0 
                                ? '<ul>' + noShowPolicy.descriptions.map(desc => `<li>${desc}</li>`).join('') + '</ul>' 
                                : ''}
                        </div>
                    </div>
                </div>
            `;
        }).join('');
        
        return `
            <div class="amadex-flight-tab-panel" id="fare-rules">
                ${fareRulesSections}
                ${fareInfoHtml}
            </div>
        `;
    }
    
    function getAllowanceInfo(flightData) {
        const defaults = {
            baggage: null,
            baggageIncluded: false,
            baggageDetails: '',
            checkin: 'Reach airport 3 hrs before departure',
            cabin: null,
            cabinIncluded: false,
            cabinDetails: ''
        };
        
        try {
            const traveler = flightData.travelerPricings && flightData.travelerPricings[0];
            const fareSegment = traveler?.fareDetailsBySegment && traveler.fareDetailsBySegment[0];
            
            // Check for included checked baggage
            if (fareSegment?.includedCheckedBags) {
                const checked = fareSegment.includedCheckedBags;
                const quantity = typeof checked === 'object' ? (checked.quantity || 0) : parseInt(checked) || 0;
                
                if (quantity > 0) {
                    defaults.baggageIncluded = true;
                    if (checked.weight) {
                        defaults.baggageDetails = `${checked.weight} ${checked.weightUnit || 'KGs'} (${quantity} Piece${quantity > 1 ? 's' : ''})`;
                    } else {
                        defaults.baggageDetails = `${quantity} Piece${quantity > 1 ? 's' : ''}`;
                    }
                    defaults.baggage = defaults.baggageDetails;
                } else {
                    defaults.baggageIncluded = false;
                    defaults.baggageDetails = 'Add baggage at checkout';
                    defaults.baggage = 'Chargeable';
                }
            } else {
                // Check alternative sources for baggage info
                if (flightData.has_baggage === true || flightData.hasBaggage === true) {
                    defaults.baggageIncluded = true;
                    defaults.baggageDetails = 'Included in fare';
                    defaults.baggage = 'Included';
                } else if (flightData.detailed_baggage && flightData.detailed_baggage.length > 0) {
                    const firstBag = flightData.detailed_baggage[0];
                    if (firstBag.quantity > 0) {
                        defaults.baggageIncluded = true;
                        if (firstBag.weight) {
                            defaults.baggageDetails = `${firstBag.weight} ${firstBag.weight_unit || 'KGs'} (${firstBag.quantity} Piece${firstBag.quantity > 1 ? 's' : ''})`;
                        } else {
                            defaults.baggageDetails = `${firstBag.quantity} Piece${firstBag.quantity > 1 ? 's' : ''}`;
                        }
                        defaults.baggage = defaults.baggageDetails;
                    } else {
                        defaults.baggageIncluded = false;
                        defaults.baggageDetails = 'Add baggage at checkout';
                        defaults.baggage = 'Chargeable';
                    }
                } else {
                    defaults.baggageIncluded = false;
                    defaults.baggageDetails = 'Add baggage at checkout';
                    defaults.baggage = 'Chargeable';
                }
            }
            
            // Check for included carry-on/cabin baggage
            if (fareSegment?.includedCarryOnBags) {
                const carryOn = fareSegment.includedCarryOnBags;
                const carryOnQty = typeof carryOn === 'object' ? (carryOn.quantity || 0) : parseInt(carryOn) || 0;
                
                if (carryOnQty > 0) {
                    defaults.cabinIncluded = true;
                    defaults.cabinDetails = `${carryOnQty} Piece${carryOnQty > 1 ? 's' : ''}`;
                    defaults.cabin = defaults.cabinDetails;
                } else {
                    defaults.cabinIncluded = false;
                    defaults.cabinDetails = 'Standard cabin baggage rules apply';
                    defaults.cabin = 'Chargeable';
                }
            } else {
                // Default: assume cabin baggage is usually included, but check if explicitly not
                if (flightData.detailed_baggage) {
                    const carryOnBag = flightData.detailed_baggage.find(bag => bag.type === 'carry_on');
                    if (carryOnBag && carryOnBag.quantity > 0) {
                        defaults.cabinIncluded = true;
                        defaults.cabinDetails = `${carryOnBag.quantity} Piece${carryOnBag.quantity > 1 ? 's' : ''}`;
                        defaults.cabin = defaults.cabinDetails;
                    } else {
                        defaults.cabinIncluded = true; // Most airlines include standard cabin baggage
                        defaults.cabinDetails = '7 Kgs (1 Piece)'; // Standard default
                        defaults.cabin = 'Included';
                    }
                } else {
                    defaults.cabinIncluded = true; // Default assumption
                    defaults.cabinDetails = '7 Kgs (1 Piece)'; // Standard default
                    defaults.cabin = 'Included';
                }
            }
            
            // Legacy support - keep old format for backward compatibility
            if (!defaults.baggage) {
                defaults.baggage = defaults.baggageIncluded ? defaults.baggageDetails : 'Chargeable';
            }
            if (!defaults.cabin || (defaults.cabin === '7 Kgs (1 Piece)' && !defaults.cabinIncluded)) {
                defaults.cabin = defaults.cabinIncluded ? defaults.cabinDetails : 'Chargeable';
            }
            
        } catch (e) {
            console.warn('Unable to derive allowance info', e);
            // Set defaults on error
            defaults.baggageIncluded = false;
            defaults.baggage = 'Chargeable';
            defaults.baggageDetails = 'Add baggage at checkout';
            defaults.cabinIncluded = true;
            defaults.cabin = 'Included';
            defaults.cabinDetails = '7 Kgs (1 Piece)';
        }
        
        return defaults;
    }
    
    function formatLongDate(dateValue) {
        if (!dateValue) return '';
        return new Date(dateValue).toLocaleDateString('en-US', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    }
    
    function formatDurationDisplay(duration) {
        if (!duration) return '';
        const hoursMatch = duration.match(/(\d+)H/);
        const minutesMatch = duration.match(/(\d+)M/);
        const hours = hoursMatch ? parseInt(hoursMatch[1], 10) : 0;
        const minutes = minutesMatch ? parseInt(minutesMatch[1], 10) : 0;
        const parts = [];
        if (hours) parts.push(`${hours}h`);
        if (minutes) parts.push(`${minutes}m`);
        return parts.join(' ') || '0h';
    }
    
    function initFlightDetailsTabs() {
        $(document).off('click', '.amadex-flight-tab').on('click', '.amadex-flight-tab', function() {
            const target = $(this).data('tab');
            $('.amadex-flight-tab').removeClass('is-active');
            $(this).addClass('is-active');
            $('.amadex-flight-tab-panel').removeClass('is-active');
            $(`#${target}`).addClass('is-active');
        });
        
        // Handle fare rules policy buttons
        $(document).off('click', '.amadex-fare-rule-btn').on('click', '.amadex-fare-rule-btn', function() {
            const $btn = $(this);
            const ruleId = $btn.data('rule');
            const $section = $btn.closest('.amadex-fare-rules-route-section');
            
            // Remove active from all buttons and texts in this section
            $section.find('.amadex-fare-rule-btn').removeClass('active');
            $section.find('.amadex-fare-rule-text').removeClass('active');
            
            // Add active to clicked button and corresponding text
            $btn.addClass('active');
            $section.find(`#${ruleId}`).addClass('active');
        });
    }
    
    /**
     * Share flight from modal
     */
    window.amadexShareFlight = function() {
        $('.amadex-modal').hide();
        setTimeout(function() {
            showShareModal(0);
        }, 200);
    };
    
    /**
     * Show share modal
     */
    function showShareModal(flightIndex) {
        const currentUrl = window.location.href;
        const shareUrl = currentUrl + '?flight=' + flightIndex;
        const shareText = 'Check out this flight deal!';
        
        const html = `
            <div class="amadex-share-modal">
                <h3>Invite Your Friends</h3>
                <p class="amadex-share-subtitle">Friends can join live</p>
                
                <div class="amadex-share-options">
                    <a href="https://wa.me/?text=${encodeURIComponent(shareText + ' ' + shareUrl)}" target="_blank" class="amadex-share-option">
                        <div class="amadex-share-icon whatsapp">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        </div>
                        <span>WhatsApp</span>
                    </a>
                    
                    <a href="https://mail.google.com/mail/?view=cm&su=${encodeURIComponent(shareText)}&body=${encodeURIComponent(shareUrl)}" target="_blank" class="amadex-share-option">
                        <div class="amadex-share-icon gmail">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 5.457v13.909c0 .904-.732 1.636-1.636 1.636h-3.819V11.73L12 16.64l-6.545-4.91v9.273H1.636A1.636 1.636 0 0 1 0 19.366V5.457c0-2.023 2.309-3.178 3.927-1.964L5.455 4.64 12 9.548l6.545-4.91 1.528-1.145C21.69 2.28 24 3.434 24 5.457z"/></svg>
                        </div>
                        <span>Gmail</span>
                    </a>
                    
                    <button class="amadex-share-option" onclick="amadexCopyLink('${shareUrl}')">
                        <div class="amadex-share-icon copy">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>
                        </div>
                        <span>Copy Link</span>
                    </button>
                    
                    <a href="https://www.messenger.com/t/?link=${encodeURIComponent(shareUrl)}" target="_blank" class="amadex-share-option">
                        <div class="amadex-share-icon messenger">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.373 0 0 4.974 0 11.111c0 3.498 1.744 6.614 4.469 8.654V24l4.088-2.242c1.092.3 2.246.464 3.443.464 6.627 0 12-4.974 12-11.111C24 4.974 18.627 0 12 0zm1.191 14.963l-3.055-3.26-5.963 3.26L10.732 8l3.131 3.259L19.752 8l-6.561 6.963z"/></svg>
                        </div>
                        <span>Messenger</span>
                    </a>
                    
                    <a href="mailto:?subject=${encodeURIComponent(shareText)}&body=${encodeURIComponent(shareUrl)}" class="amadex-share-option">
                        <div class="amadex-share-icon email">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                        </div>
                        <span>Email</span>
                    </a>
                    
                    <a href="https://t.me/share/url?url=${encodeURIComponent(shareUrl)}&text=${encodeURIComponent(shareText)}" target="_blank" class="amadex-share-option">
                        <div class="amadex-share-icon telegram">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M9.78 18.65l.28-4.23 7.68-6.92c.34-.31-.07-.46-.52-.19L7.74 13.3 3.64 12c-.88-.25-.89-.86.2-1.3l15.97-6.16c.73-.33 1.43.18 1.15 1.3l-2.72 12.81c-.19.91-.74 1.13-1.5.71L12.6 16.3l-1.99 1.93c-.23.23-.42.42-.83.42z"/></svg>
                        </div>
                        <span>Telegram</span>
                    </a>
                    
                    <a href="https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl)}" target="_blank" class="amadex-share-option">
                        <div class="amadex-share-icon facebook">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </div>
                        <span>Facebook</span>
                    </a>
                </div>
            </div>
        `;
        
        showModal('amadex-share-modal', html);
    }
    
    /**
     * Copy link to clipboard
     */
    window.amadexCopyLink = function(url) {
        navigator.clipboard.writeText(url).then(function() {
            alert('Link copied to clipboard!');
            $('.amadex-modal').hide();
        }).catch(function() {
            // Fallback for older browsers
            const tempInput = document.createElement('input');
            tempInput.value = url;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
            alert('Link copied to clipboard!');
            $('.amadex-modal').hide();
        });
    };
    
    /**
     * Show booking modal (legacy)
     */
    function showBookingModal(flightId) {
        selectedFlight = flightId;
        $('#amadex-booking-modal').show();
    }

    /**
     * Process booking
     */
    function processBooking() {
        const bookingData = {
            passenger_name: $('#amadex-passenger-name').val(),
            passenger_email: $('#amadex-passenger-email').val(),
            passenger_phone: $('#amadex-passenger-phone').val(),
            flight_id: selectedFlight
        };
        
        if (!bookingData.passenger_name || !bookingData.passenger_email || !bookingData.passenger_phone) {
            alert('Please fill in all required fields');
                return;
            }
        
        // Process booking logic here
        alert('Booking functionality will be implemented');
    }
    
    /**
     * Show automatic call now popup (after 10 seconds)
     */
    function showAutomaticCallNowPopup(searchData, results) {
        // Get settings from WordPress
        const callNowNumber = (typeof amadexSettings !== 'undefined' && amadexSettings.call_now_number) 
            ? amadexSettings.call_now_number 
            : '+1-866-960-2626';
        const popupTitle = (typeof amadexSettings !== 'undefined' && amadexSettings.popup_title) 
            ? amadexSettings.popup_title 
            : 'Exclusive Deals';
        const popupDescription = (typeof amadexSettings !== 'undefined' && amadexSettings.popup_description) 
            ? amadexSettings.popup_description 
            : 'Get the best deals on flights by calling our travel experts.';
        const popupLogoUrl = (typeof amadexSettings !== 'undefined' && amadexSettings.popup_logo_url) 
            ? amadexSettings.popup_logo_url 
            : '';
        
        // Get first flight for popup display
        const firstFlight = results.flights && results.flights.length > 0 ? results.flights[0] : null;
        
        if (!firstFlight) return;
        
        // Create popup content
        const popupContent = createCallNowPopupContent(firstFlight, {
            number: callNowNumber,
            title: popupTitle,
            description: popupDescription,
            logoUrl: popupLogoUrl,
            searchData: searchData
        });
        
        // Show popup
        showModal('amadex-call-now-modal', popupContent);
    }
    
    /**
     * Show call now popup (legacy - not used anymore)
     */
    function showCallNowPopup(flightData) {
        // This function is no longer used - call now buttons now dial directly
        const callNowNumber = (typeof amadexSettings !== 'undefined' && amadexSettings.call_now_number) 
            ? amadexSettings.call_now_number 
            : '+1-866-960-2626';
        
        if (callNowNumber) {
            window.location.href = 'tel:' + callNowNumber.replace(/[^\d+]/g, '');
        }
    }
    
    /**
     * Create call now popup content
     */
    function createCallNowPopupContent(flightData, settings) {
        // Get airline code for markup calculation
        const airlineCode = (flightData.validatingAirlineCodes && flightData.validatingAirlineCodes[0]) || 
                           (flightData.validating_airline_codes && flightData.validating_airline_codes[0]) || 
                           '';
        
        // Get original price and apply markup to match results page
        const originalPrice = parseFloat(flightData.price.total || 0);
        // Pass flightData to check if Pricing Rules Engine is enabled
        const apiAmount = calculatePriceWithMarkup(originalPrice, airlineCode, flightData);
        const currency = flightData.price.currency;
        const priceType = (typeof amadexSettings !== 'undefined' && amadexSettings.popup_price_type) ? amadexSettings.popup_price_type : 'none';
        const fixed = parseFloat(amadexSettings.popup_price_fixed || 0);
        const discountPercent = parseFloat(amadexSettings.popup_discount_percent || 0);

        let computedAmount = apiAmount;
        if (priceType === 'fixed' && !isNaN(fixed) && fixed > 0) {
            computedAmount = fixed;
        } else if (priceType === 'discount_percent' && !isNaN(discountPercent) && discountPercent > 0) {
            computedAmount = apiAmount - (apiAmount * (discountPercent / 100));
        }

        const price = formatPrice(computedAmount, currency);
        
        // Extract flight data from itineraries
        const firstItinerary = flightData.itineraries && flightData.itineraries[0] ? flightData.itineraries[0] : null;
        const secondItinerary = flightData.itineraries && flightData.itineraries[1] ? flightData.itineraries[1] : null;
        
        // Get first and last segments for origin and destination
        const departureSegment = firstItinerary && firstItinerary.segments && firstItinerary.segments[0] ? firstItinerary.segments[0] : null;
        const lastSegment = firstItinerary && firstItinerary.segments && firstItinerary.segments.length > 0 
            ? firstItinerary.segments[firstItinerary.segments.length - 1] 
            : null;
        const returnSegment = secondItinerary && secondItinerary.segments && secondItinerary.segments[0] ? secondItinerary.segments[0] : null;
        
        // Extract airport codes from flight segments
        const originCode = departureSegment?.departure?.iataCode || departureSegment?.departure?.iata_code || '';
        const destinationCode = lastSegment?.arrival?.iataCode || lastSegment?.arrival?.iata_code || '';
        const returnOriginCode = returnSegment?.departure?.iataCode || returnSegment?.departure?.iata_code || '';
        const returnDestinationCode = returnSegment?.arrival?.iataCode || returnSegment?.arrival?.iata_code || '';
        
        // Get airport info for city names
        const originInfo = originCode ? getAirportInfo(originCode) : { city: '', airport: '' };
        const destinationInfo = destinationCode ? getAirportInfo(destinationCode) : { city: '', airport: '' };
        const returnOriginInfo = returnOriginCode ? getAirportInfo(returnOriginCode) : { city: '', airport: '' };
        const returnDestinationInfo = returnDestinationCode ? getAirportInfo(returnDestinationCode) : { city: '', airport: '' };
        
        // Use search data if available for better display, otherwise use flight data
        const searchData = settings.searchData || {};
        const origin = originCode || searchData.origin || 'N/A';
        const originCity = originInfo.city || searchData.origin_city || searchData.origin_city_name || originCode || origin;
        const destination = destinationCode || searchData.destination || 'N/A';
        const destinationCity = destinationInfo.city || searchData.destination_city || searchData.destination_city_name || destinationCode || destination;
        
        // Extract dates from flight segments
        const departureDate = departureSegment?.departure?.at || searchData.departure || flightData.departure || new Date();
        const returnDate = returnSegment?.departure?.at || searchData.return || flightData.return || null;
        const cabinClass = getCabinDisplayName(searchData.cabin || 'ECONOMY');
        const tripType = returnDate && searchData.return ? 'Round Trip' : 'One Way';
        const adults = parseInt(searchData.adults || 1);
        const children = parseInt(searchData.children || 0);
        const infants = parseInt(searchData.infants || 0);
        
        // Get phone number
        const callNowNumber = (typeof amadexSettings !== 'undefined' && amadexSettings.call_now_number) 
            ? amadexSettings.call_now_number 
            : '+1-877-721-0410';
        
        // Get customer service image and trust badges settings
        const customerServiceImage = (typeof amadexSettings !== 'undefined' && amadexSettings.popup_customer_service_image) 
            ? amadexSettings.popup_customer_service_image 
            : '';
        const trustBadgeYears = (typeof amadexSettings !== 'undefined' && amadexSettings.popup_trust_years) 
            ? amadexSettings.popup_trust_years 
            : '20+';
        const trustpilotRating = (typeof amadexSettings !== 'undefined' && amadexSettings.popup_trustpilot_rating) 
            ? amadexSettings.popup_trustpilot_rating 
            : '4.4';
        const countdownMinutes = (typeof amadexSettings !== 'undefined' && amadexSettings.popup_countdown_minutes) 
            ? parseInt(amadexSettings.popup_countdown_minutes) 
            : 12;
        
        // Format dates for display (matching Adobe XD format: "Sun, 27 Oct.")
        const formatDateForDisplay = (dateStr) => {
            if (!dateStr) return '';
            try {
                const date = new Date(dateStr);
                if (isNaN(date.getTime())) return '';
                const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                return `${days[date.getDay()]}, ${date.getDate()} ${months[date.getMonth()]}.`;
            } catch (e) {
                // Fallback to existing formatDate function
                return formatDate(dateStr);
            }
        };
        
        return `
            <div class="amadex-call-popup-new">
               <button class="amadex-modal-close">×</button>
                
                <!-- Two Column Layout -->
                <div class="amadex-popup-content-wrapper">
                    <!-- Left Column: Flight Details -->
                    <div class="amadex-popup-left-column">
                     <!-- Limited Time Offer Banner -->
                <div class="amadex-popup-banner">
                    <span class="amadex-banner-text">Limited Time Offer</span>
                    <span class="amadex-banner-countdown">Ends in: <span id="amadex-countdown">${countdownMinutes}min 04Sec</span></span>
                </div>
                        <div class="amadex-popup-deal-heading">
                            <span class="amadex-deal-label">EXCLUSIVE DEALS TO</span>
                            <div class="amadex-deal-destination-wrapper">
                                <h2 class="amadex-deal-destination">${destinationCity.toUpperCase()}</h2>
                                <div class="amadex-deal-price-section">
                                    <!--<span class="amadex-price-label-text">Price</span> -->
                                    <span class="amadex-price-amount-large">${price}</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Flight Itinerary Box -->
                        <div class="amadex-popup-flight-box">
                            <div class="amadex-flight-box-header">${tripType}, ${cabinClass}</div>
                            <div class="amadex-flight-box-route">
                                <div class="amadex-flight-box-segment">
                                    <span class="amadex-flight-box-label">Depart</span>
                                    <span class="amadex-flight-box-code">${origin}</span>
                                    <span class="amadex-flight-box-city">${originCity}</span>
                                    <span class="amadex-flight-box-date">${formatDateForDisplay(departureDate)}</span>
                                </div>
                                <div class="amadex-flight-box-arrow">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="102.5" height="14" viewBox="0 0 102.5 14">
  <g id="Group_443" data-name="Group 443" transform="translate(1700 -251)">
    <line id="Line_47" data-name="Line 47" x2="100" transform="translate(-1698.5 258.5)" fill="none" stroke="#c1c1c1" stroke-linecap="round" stroke-width="1" stroke-dasharray="3"/>
    <g id="Group_3698" data-name="Group 3698" transform="translate(-1597.5 206.941) rotate(90)">
      <path id="Path_1057" data-name="Path 1057" d="M58.059,9.852V8.709L52.251,5.054c.018-.963.016-1.938-.019-2.931C52.167.949,51.634,0,51.059,0s-1.107.949-1.172,2.122c-.036.994-.037,1.968-.019,2.931L44.059,8.709V9.852l5.9-2.074c.074,1.546.165,3.077.23,4.618l-1.8.849V14l2.664-.4,2.664.4v-.755l-1.8-.849c.065-1.54.155-3.072.23-4.618Z"/>
      <path id="Path_1058" data-name="Path 1058" d="M138.92,248.809h1.1v1.778h-1.1Zm7.837,0h1.1v1.778h-1.1Z" transform="translate(-92.331 -243.106)"/>
    </g>
    <g id="Ellipse_43" data-name="Ellipse 43" transform="translate(-1700 254)" fill="#fff" stroke="#000" stroke-width="1">
      <circle cx="4.5" cy="4.5" r="4.5" stroke="none"/>
      <circle cx="4.5" cy="4.5" r="4" fill="none"/>
    </g>
  </g>
</svg>
                                </div>
                                <div class="amadex-flight-box-segment">
                                    <span class="amadex-flight-box-label">${returnDate ? 'Return' : 'Arrival'}</span>
                                    <span class="amadex-flight-box-code">${returnDate && returnOriginCode ? returnOriginCode : destination}</span>
                                    <span class="amadex-flight-box-city">${returnDate && returnOriginInfo.city ? returnOriginInfo.city : destinationCity}</span>
                                    <span class="amadex-flight-box-date">${returnDate ? formatDateForDisplay(returnDate) : formatDateForDisplay(departureDate)}</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Call Button -->
                        <a href="tel:${callNowNumber.replace(/[^\d+]/g, '')}" class="amadex-popup-call-button-large">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
  <g id="Group_25" data-name="Group 25" transform="translate(-0.006)">
    <path id="Path_101" data-name="Path 101" d="M15.211,13.231a1.4,1.4,0,0,0-2.118,0c-.5.491-.991.982-1.477,1.482a.292.292,0,0,1-.408.075c-.32-.175-.662-.316-.97-.508a15.364,15.364,0,0,1-3.7-3.371A8.775,8.775,0,0,1,5.206,8.782a.3.3,0,0,1,.075-.391c.5-.479.978-.97,1.465-1.461a1.407,1.407,0,0,0,0-2.168C6.355,4.37,5.968,3.987,5.581,3.6s-.795-.8-1.2-1.2a1.41,1.41,0,0,0-2.118,0c-.5.491-.978.995-1.486,1.477A2.412,2.412,0,0,0,.02,5.506,6.9,6.9,0,0,0,.553,8.474a18.032,18.032,0,0,0,3.2,5.331,19.806,19.806,0,0,0,6.559,5.132,9.486,9.486,0,0,0,3.633,1.057,2.663,2.663,0,0,0,2.285-.87c.425-.474.9-.907,1.353-1.361a1.416,1.416,0,0,0,.008-2.156q-1.186-1.192-2.381-2.376Zm-.795-3.317,1.536-.262A6.894,6.894,0,0,0,10.121,4L9.9,5.54a5.328,5.328,0,0,1,4.512,4.374Zm2.4-6.676A11.324,11.324,0,0,0,10.325,0l-.216,1.544a9.881,9.881,0,0,1,8.361,8.1l1.536-.262a11.407,11.407,0,0,0-3.188-6.147Z" transform="translate(0)" fill="#fff"/>
  </g>
</svg>
                            <span class="amadex-call-button-text">${callNowNumber}</span>
                        </a>
                    </div>
                    
                    <!-- Right Column: Customer Service & Trust -->
                    <div class="amadex-popup-right-column">
                        ${customerServiceImage ? `
                            <div class="amadex-popup-customer-service">
                                <img src="${customerServiceImage}" alt="Customer Service Representative" class="amadex-customer-service-img" onerror="this.style.display='none';">
                            </div>
                        ` : `
                            <div class="amadex-popup-customer-service">
                                <div class="amadex-customer-service-placeholder">
                                    <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z" stroke="#666" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                            </div>
                        `}
                        
                        
                    </div>
                </div>
            </div>
        `;
    }
    
    /**
     * Show modal
     */
    function showModal(modalId, content) {
        let modal = $('#' + modalId);
        if (modal.length === 0) {
            modal = $(`
                <div class="amadex-modal" id="${modalId}">
                    <div class="amadex-modal-content amadex-call-modal-content">
                      <!--  <div class="amadex-modal-header">
                            <button class="amadex-modal-close">&times;</button>
                        </div> -->
                        <div class="amadex-modal-body"></div>
                    </div>
                </div>
            `);
            $('body').append(modal);
            
            // Bind close events to the new modal
            bindModalCloseEvents(modal);
        }
        
        modal.find('.amadex-modal-body').html(content);
        
        // Add body class to prevent scrolling on mobile
        $('body').addClass('amadex-modal-open');
        
        // Show modal with animation
        modal.fadeIn(300);
        
        // Start countdown timer
        startCountdownTimer();
    }
    
    /**
     * Hide modal and remove body class
     */
    function hideModal(modalId) {
        const modal = $('#' + modalId);
        if (modal.length > 0) {
            modal.fadeOut(300, function() {
                $('body').removeClass('amadex-modal-open');
            });
        }
    }
    
    /**
     * Bind modal close events
     */
    function bindModalCloseEvents(modal) {
        const modalId = modal.attr('id');
        
        // Close button click
        modal.find('.amadex-modal-close').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Close button clicked');
            hideModal(modalId);
        });
        
        // Click outside modal to close
        modal.on('click', function(e) {
            if (e.target === this) {
                console.log('Clicked outside modal');
                hideModal(modalId);
            }
        });
        
        // ESC key to close
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && modal.is(':visible')) {
                console.log('ESC key pressed');
                hideModal(modalId);
            }
        });
    }
    
    /**
     * Start countdown timer
     */
    function startCountdownTimer() {
        // Get countdown minutes from settings or default to 12
        const countdownMinutes = (typeof amadexSettings !== 'undefined' && amadexSettings.popup_countdown_minutes) 
            ? parseInt(amadexSettings.popup_countdown_minutes) 
            : 12;
        let timeLeft = countdownMinutes * 60; // Convert minutes to seconds
        const timerElement = $('#amadex-countdown');
        
        if (timerElement.length === 0) return;
        
        const timer = setInterval(() => {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            
            // Format: 12min 04Sec (matching Adobe XD design)
            timerElement.text(`${minutes}min ${seconds.toString().padStart(2, '0')}Sec`);
            
            timeLeft--;
            
            if (timeLeft < 0) {
                clearInterval(timer);
                timerElement.text('Expired');
            }
        }, 1000);
    }
    
    /**
     * Request callback
     */
    window.requestCallback = function() {
        const phoneNumber = $('#amadex-callback-number').val();
        if (!phoneNumber) {
            alert('Please enter your phone number');
            return;
        }
        
        // Here you would typically send the callback request to your server
        alert('Callback requested! We will call you at ' + phoneNumber);
        
        // Close modal
        $('.amadex-modal').hide();
    };

    /**
     * Check if we're on the results page
     */
    function isResultsPage() {
        // Strict check: require the results page wrapper
        const hasResultsWrapper = $('#amadex-results-page').length > 0;

        // Additional safety: also require URL to include flight-results
        const url = window.location.href.toLowerCase();
        const matchesResultsUrl = url.includes('flight-results');

        return hasResultsWrapper && matchesResultsUrl;
    }

    /**
     * Load stored results
     * This function is called on page load and when returning from booking page
     */
    function loadStoredResults() {

        
        // First, try to read URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        
        // Check if we have stored results in sessionStorage
        const storedResults = sessionStorage.getItem('amadex_search_results');
        const storedSearchData = sessionStorage.getItem('amadex_search_data');
        
        // If URL has parameters, update the display
        if (urlParams.has('origin_iata') && urlParams.has('destination_iata')) {
            const urlSearchData = {
                origin: urlParams.get('origin_iata'),
                origin_name: urlParams.get('origin_name'),
                destination: urlParams.get('destination_iata'),
                destination_name: urlParams.get('destination_name'),
                departure: urlParams.get('depart_date'),
                return: urlParams.get('return_date'),
                adults: parseInt(urlParams.get('adults')) || 1,
                children: parseInt(urlParams.get('children')) || 0,
                infants: parseInt(urlParams.get('infants')) || 0,
                cabin: urlParams.get('cabin') || 'Economy',
                currency: urlParams.get('currency') || 'USD',
                one_way: urlParams.get('one_way') === 'Yes',
                isDomestic: urlParams.get('isDomestic') === 'Yes',
                trip_type: urlParams.get('trip_type') || 'round'
            };
            
            // Update search info display with URL data
            updateSearchInfo(urlSearchData);
            
            // Store in sessionStorage for consistency
            sessionStorage.setItem('amadex_search_data', JSON.stringify(urlSearchData));
        }
        
        if (storedResults && storedSearchData) {
            try {
                const searchData = JSON.parse(storedSearchData);
                const results = JSON.parse(storedResults);
                

                
                // Check if multi-city and restore segments if needed
                if (searchData.trip_type === 'multi-city' || results.is_multi_city) {
                    // Check if segments are stored
                    const segmentsStr = sessionStorage.getItem('amadex_multi_city_segments');
                    if (!segmentsStr) {
                        // Try to get from URL
                        const urlSegmentsParam = urlParams.get('segments');
                        if (urlSegmentsParam) {
                            try {
                                let decodedSegments = decodeURIComponent(urlSegmentsParam);
                                try {
                                    const segments = JSON.parse(decodedSegments);
                                    sessionStorage.setItem('amadex_multi_city_segments', JSON.stringify(segments));
                                    console.log('loadStoredResults: Restored segments from URL:', segments);
                                } catch(e) {
                                    decodedSegments = decodeURIComponent(decodedSegments);
                                    const segments = JSON.parse(decodedSegments);
                                    sessionStorage.setItem('amadex_multi_city_segments', JSON.stringify(segments));
                                    console.log('loadStoredResults: Restored segments from URL (double-decode):', segments);
                                }
                            } catch(e) {
                                console.error('loadStoredResults: Error parsing segments from URL:', e);
                            }
                        }
                    }
                    
                    // Also check if segments are in results
                    if (results.segments && Array.isArray(results.segments)) {
                        sessionStorage.setItem('amadex_multi_city_segments', JSON.stringify(results.segments));
                        console.log('loadStoredResults: Restored segments from results:', results.segments);
                    }
                }
                
                // Update search info display (if not already updated from URL)
                if (!urlParams.has('origin_iata')) {
                    updateSearchInfo(searchData);
                }
                
                // Check if stored results are empty (0 flights)
                const flightCount = results.flights ? results.flights.length : (results.meta ? results.meta.count : 0);
                
                // If we have URL parameters but stored results are empty, trigger a new search
                if (urlParams.has('origin_iata') && urlParams.has('destination_iata') && urlParams.has('depart_date') && flightCount === 0) {
                    console.log('loadStoredResults: URL parameters present but stored results are empty, triggering new search...');
                    // Clear stale results
                    sessionStorage.removeItem('amadex_search_results');
                    // Trigger search after a short delay to ensure form is populated
                    setTimeout(function() {
                        if (typeof performModernSearch === 'function') {
                            performModernSearch();
                        } else {
                            console.error('loadStoredResults: performModernSearch function not available');
                        }
                    }, 800);
                    return; // Don't display empty results
                }
                
                // Display results - handle different data structures
                const resultsToDisplay = results.flights ? results : (results.data && results.data.flights ? results.data : results);
                
                if (resultsToDisplay && (resultsToDisplay.flights || resultsToDisplay.data)) {
                    // Clear container first to ensure clean display
                    $('#amadex-flight-cards-container').empty();
                    $('#amadex-loading').hide();
                    $('#amadex-no-results').hide();
                    

                    displayFlightResults(resultsToDisplay);
                
                // Store in global variable for filtering
                    currentSearchData = resultsToDisplay;
                    
                    // If multi-city, initialize tabs after a short delay
                    if (searchData.trip_type === 'multi-city' || results.is_multi_city) {
                        setTimeout(function() {
                            checkAndInitMultiCityTabs();
                        }, 300);
                    }
                } else {
                    console.warn('loadStoredResults: Results data structure is invalid, trying to fetch from URL:', results);
                    // Try to fetch from URL if stored results are invalid
                    if (urlParams.has('origin_iata') && urlParams.has('destination_iata')) {
                        fetchResultsFromURL();
                    } else {
                        // Try to show "no results" message
                        $('#amadex-no-results').show();
                        $('#amadex-flight-cards-container').empty();
                    }
                }
                
                // Show automatic popup ONLY on results page if enabled
                const autoPopupEnabled = (typeof amadexSettings !== 'undefined' && amadexSettings.auto_popup_enabled !== undefined) 
                    ? parseInt(amadexSettings.auto_popup_enabled) 
                    : 1;
                const autoPopupDelay = (typeof amadexSettings !== 'undefined' && amadexSettings.auto_popup_delay) 
                    ? parseInt(amadexSettings.auto_popup_delay) * 1000 
                    : 420000; // Default: 7 minutes (420 seconds)
                

                
                if (isResultsPage() && autoPopupEnabled === 1) {

                    setTimeout(function() {
                        showAutomaticCallNowPopup(searchData, results);
                    }, autoPopupDelay);
                } else {
                    console.log('Automatic popup disabled or not on results page');
                }
                
            } catch (e) {
                console.error('Error loading stored results:', e);
                // If parsing fails, try to fetch from URL
                if (urlParams.has('origin_iata') && urlParams.has('destination_iata')) {
                    console.log('Parsing failed, attempting to fetch results from URL parameters');
                    fetchResultsFromURL();
                }
            }
        } else if (currentSearchData && currentSearchData.flights) {
            console.log('loadStoredResults: Using currentSearchData:', currentSearchData);
            displayFlightResults(currentSearchData);
        } else if (urlParams.has('origin_iata') && urlParams.has('destination_iata')) {
            // If we have URL parameters but no stored results, trigger a new search
            console.log('loadStoredResults: No stored results found, triggering new search from URL parameters');
            setTimeout(function() {
                if (typeof performModernSearch === 'function') {
                    performModernSearch();
                } else {
                    console.error('loadStoredResults: performModernSearch function not available, trying fetchResultsFromURL');
                    fetchResultsFromURL();
                }
            }, 800);
        } else {
            console.warn('loadStoredResults: No results found in sessionStorage or URL parameters');
            // Show loading state while checking
            $('#amadex-loading').show();
            $('#amadex-flight-cards-container').empty();
        }
    }
    
    /**
     * Fetch flight results from URL parameters
     */
    function fetchResultsFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        
        if (!urlParams.has('origin_iata') || !urlParams.has('destination_iata') || !urlParams.has('depart_date')) {
            console.error('fetchResultsFromURL: Missing required URL parameters');
            $('#amadex-loading').hide();
            $('#amadex-no-results').show();
            return;
        }
        
        // Show loading state
        $('#amadex-loading').show();
        $('#amadex-flight-cards-container').empty();
        $('#amadex-no-results').hide();

        // Show skeleton or loading animation if enabled in Advanced Settings
        amadexShowStreamingLoaderUI();
        
        // Get trip type and check if one-way
        const tripType = urlParams.get('trip_type') || (urlParams.get('one_way') === 'Yes' ? 'oneway' : 'round');
        const isOneWay = tripType === 'oneway' || tripType === 'one-way' || urlParams.get('one_way') === 'Yes';
        
        // Get and normalize cabin class from URL
        let cabin = urlParams.get('cabin') || 'ECONOMY';
        cabin = cabin.toUpperCase().trim();
        
        // Map common variations to standard values
        const cabinMap = {
            'PREMIUM': 'PREMIUM_ECONOMY',
            'PREMIUM ECONOMY': 'PREMIUM_ECONOMY',
            'PREMIUM-ECONOMY': 'PREMIUM_ECONOMY',
            'BUSINESS CLASS': 'BUSINESS',
            'FIRST CLASS': 'FIRST'
        };
        if (cabinMap[cabin]) {
            cabin = cabinMap[cabin];
        }
        
        // Validate cabin is one of accepted values
        const validCabins = ['ECONOMY', 'PREMIUM_ECONOMY', 'BUSINESS', 'FIRST'];
        if (!validCabins.includes(cabin)) {
            console.warn('fetchResultsFromURL: Invalid cabin from URL:', cabin, '- defaulting to ECONOMY');
            cabin = 'ECONOMY';
        }
        
        // Prepare search parameters from URL
        const searchParams = {
            origin: urlParams.get('origin_iata'),
            destination: urlParams.get('destination_iata'),
            departure_date: urlParams.get('depart_date'),
            return_date: isOneWay ? '' : (urlParams.get('return_date') || ''), // Clear return_date for one-way
            adults: parseInt(urlParams.get('adults')) || 1,
            children: parseInt(urlParams.get('children')) || 0,
            infants: parseInt(urlParams.get('infants')) || 0,
            travel_class: cabin,
            currency: urlParams.get('currency') || 'USD',
            trip_type: tripType
        };
        
        console.log('fetchResultsFromURL: Trip type:', tripType, 'Is one-way:', isOneWay);
        console.log('fetchResultsFromURL: Search params:', searchParams);
        
        // Handle multi-city segments if present
        if (searchParams.trip_type === 'multi-city') {
            const segmentsParam = urlParams.get('segments');
            if (segmentsParam) {
                try {
                    let decodedSegments = decodeURIComponent(segmentsParam);
                    try {
                        searchParams.segments = JSON.parse(decodedSegments);
                    } catch(e) {
                        decodedSegments = decodeURIComponent(decodedSegments);
                        searchParams.segments = JSON.parse(decodedSegments);
                    }
                    searchParams.multi_segments = JSON.stringify(searchParams.segments);
                } catch(e) {
                    console.error('Error parsing segments from URL:', e);
                }
            }
        }
        
        console.log('fetchResultsFromURL: Fetching results with params:', searchParams);
        
        console.log('fetchResultsFromURL: Preparing AJAX request with params:', searchParams);
        
        // Determine if we should show all cabins (when ECONOMY or empty)
        const showAllCabins = (searchParams.travel_class === 'ECONOMY' || !searchParams.travel_class || searchParams.travel_class === '');
        
        // Prepare AJAX data
        const ajaxData = {
            action: 'amadex_search_flights',
            origin: searchParams.origin,
            destination: searchParams.destination,
            departure_date: searchParams.departure_date,
            return_date: searchParams.return_date, // Will be empty for one-way
            adults: searchParams.adults,
            children: searchParams.children,
            infants: searchParams.infants,
            travel_class: showAllCabins ? '' : searchParams.travel_class, // Send empty for ECONOMY to show all cabins
            show_all_cabins: showAllCabins ? 'yes' : 'no',
            currency: searchParams.currency,
            trip_type: searchParams.trip_type,
            one_way: isOneWay ? 'Yes' : 'No', // Explicitly send one_way flag
            multi_segments: searchParams.multi_segments || '',
            segments: searchParams.segments ? JSON.stringify(searchParams.segments) : '', // Also send as segments for backend
            nonce: typeof AmadexConfig !== 'undefined' ? AmadexConfig.nonce : ''
        };
        
        console.log('fetchResultsFromURL: Normalized cabin:', cabin, 'travel_class:', ajaxData.travel_class, 'show_all_cabins:', ajaxData.show_all_cabins);
        
        // Log multi-city segments if present
        if (searchParams.trip_type === 'multi-city' && searchParams.segments) {
            console.log('fetchResultsFromURL: Sending multi-city segments:', searchParams.segments);
        }
        
        console.log('fetchResultsFromURL: AJAX data being sent:', ajaxData);
        
        // Perform AJAX search
        $.ajax({
            url: typeof AmadexConfig !== 'undefined' ? AmadexConfig.ajaxUrl : '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: ajaxData,
            success: function(response) {
                $('#amadex-loading').hide();
                
                console.log('fetchResultsFromURL: AJAX Response:', response);
                console.log('fetchResultsFromURL: Response structure:', {
                    success: response.success,
                    hasData: !!(response.data),
                    dataType: response.data ? typeof response.data : 'undefined',
                    hasFlights: !!(response.data && response.data.flights),
                    flightCount: response.data && response.data.flights ? response.data.flights.length : 0,
                    hasMeta: !!(response.data && response.data.meta),
                    metaCount: response.data && response.data.meta ? response.data.meta.count : 0
                });
                
                if (response.success && response.data) {
                    console.log('fetchResultsFromURL: Results fetched successfully:', response.data);
                    
                    // Validate response structure
                    if (!response.data.flights && !response.data.data && (!response.data.meta || response.data.meta.count === 0)) {
                        console.warn('fetchResultsFromURL: No flights in response:', response.data);
                        $('#amadex-no-results').show();
                        $('#amadex-flight-cards-container').empty();
                        updateResultsAvailableCount(0);
                        return;
                    }
                    
                    // Store results in sessionStorage
                    sessionStorage.setItem('amadex_search_results', JSON.stringify(response.data));
                    
                    // Store search data
                    const searchData = {
                        origin: searchParams.origin,
                        origin_name: urlParams.get('origin_name') || searchParams.origin,
                        destination: searchParams.destination,
                        destination_name: urlParams.get('destination_name') || searchParams.destination,
                        departure: searchParams.departure_date,
                        return: searchParams.return_date,
                        adults: searchParams.adults,
                        children: searchParams.children,
                        infants: searchParams.infants,
                        cabin: searchParams.travel_class,
                        currency: searchParams.currency,
                        trip_type: searchParams.trip_type,
                        one_way: isOneWay
                    };
                    sessionStorage.setItem('amadex_search_data', JSON.stringify(searchData));
                    
                    // Update search info display
                    updateSearchInfo(searchData);
                    
                    // Display results
                    displayFlightResults(response.data);
                } else {
                    console.error('fetchResultsFromURL: API returned error:', response);
                    const errorMessage = response.data && response.data.message 
                        ? response.data.message 
                        : 'No results found. Please try different dates or routes.';
                    
                    $('#amadex-no-results').show();
                    $('#amadex-flight-cards-container').empty();
                    updateResultsAvailableCount(0);
                    
                    alert('Search Error: ' + errorMessage);
                }
            },
            error: function(xhr, status, error) {
                $('#amadex-loading').hide();
                console.error('fetchResultsFromURL: AJAX Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText,
                    statusCode: xhr.status
                });
                
                let errorMessage = 'Network error. Please check your connection and try again.';
                if (xhr.status === 404) {
                    errorMessage = 'Search endpoint not found. Please contact support.';
                } else if (xhr.status === 500) {
                    errorMessage = 'Server error. Please try again later.';
                }
                
                $('#amadex-no-results').show();
                alert('Search Error: ' + errorMessage);
            }
        });
    }

    /**
     * Calculate price with markup
     * Applies markup based on plugin settings
     * 
     * @param {number} originalPrice - The original price to apply markup to
     * @param {string} airlineCode - Airline code for airline-specific markup
     * @param {object} flightData - Optional flight data object to check for Pricing Rules Engine
     * @returns {number} Price with markup applied (or original if Rules Engine enabled)
     */
    function calculatePriceWithMarkup(originalPrice, airlineCode, flightData) {
        // Check if Pricing Rules Engine is enabled (via flight data)
        // If pricing_snapshot or pricing_charge_total exists, Rules Engine is enabled
        // In this case, price is already P_display (with discount), so return as-is
        if (flightData && flightData.price) {
            if (flightData.price.pricing_snapshot || flightData.price.pricing_charge_total) {
                // Pricing Rules Engine is enabled - price is already P_display, return as-is
                return parseFloat(originalPrice);
            }
        }
        
        // Check if pricing settings are available and enabled
        if (typeof amadexSettings === 'undefined' || 
            !amadexSettings.pricing || 
            !amadexSettings.pricing.enabled) {
            return parseFloat(originalPrice);
        }
        
        const pricing = amadexSettings.pricing;
        let price = parseFloat(originalPrice);
        
        // Check for airline-specific markup
        if (airlineCode && pricing.airlineMarkups && pricing.airlineMarkups[airlineCode.toUpperCase()]) {
            const airlineMarkup = pricing.airlineMarkups[airlineCode.toUpperCase()];
            price = price + (price * (airlineMarkup / 100));
        } else {
            // Apply global markup
            switch (pricing.type) {
                case 'percentage':
                    price = price + (price * (pricing.percentage / 100));
                    break;
                    
                case 'fixed':
                    price = price + pricing.fixed;
                    break;
                    
                case 'both':
                    price = price + (price * (pricing.percentage / 100)) + pricing.fixed;
                    break;
            }
        }
        
        // Apply rounding
        price = roundPrice(price, pricing.rounding);
        
        return price;
    }
    
    /**
     * Round price based on settings
     */
    function roundPrice(price, roundingType) {
        switch (roundingType) {
            case 'nearest_1':
                return Math.round(price);
                
            case 'nearest_5':
                return Math.round(price / 5) * 5;
                
            case 'nearest_10':
                return Math.round(price / 10) * 10;
                
            case 'round_up_5':
                return Math.ceil(price / 5) * 5;
                
            case 'round_up_10':
                return Math.ceil(price / 10) * 10;
                
            case 'ending_99':
                return Math.floor(price) + 0.99;
                
            case 'none':
            default:
                return Math.round(price * 100) / 100;
        }
    }
    
    /**
     * Remove all currency symbols from a price string
     * Handles both single-character and multi-character currency symbols
     */
    function stripCurrencySymbols(text) {
        if (!text || typeof text !== 'string') {
            return text;
        }
        
        // First, remove multi-character currency symbols (order matters - remove longest first)
        // Multi-character symbols: C$, A$, S$, R$, and Arabic symbols
        let cleaned = text
            .replace(/C\$/g, '')  // Canadian Dollar
            .replace(/A\$/g, '')  // Australian Dollar
            .replace(/S\$/g, '')  // Singapore Dollar
            .replace(/R\$/g, '')  // Brazilian Real
            .replace(/د\.إ/g, '') // UAE Dirham (Arabic)
            .trim();
        
        // Then remove single-character currency symbols
        cleaned = cleaned
            .replace(/^[\$€£₹¥₨৳¥]+/, '') // Remove leading currency symbols
            .replace(/^[^0-9]+/, '')      // Remove any remaining non-numeric leading characters
            .trim();
        
        return cleaned;
    }
    
    /**
     * Clean up duplicate currency symbols in a formatted price string
     * Handles patterns like "CC$", "AA$", "$$", etc.
     */
    function cleanDuplicateCurrencySymbols(text) {
        if (!text || typeof text !== 'string') {
            return text;
        }
        
        // Pattern 1: Multi-character symbols repeated (e.g., "CC$" -> "C$", "AA$" -> "A$")
        text = text
            .replace(/^(C\$){2,}/, 'C$')   // CC$, CCC$, etc. -> C$
            .replace(/^(A\$){2,}/, 'A$')   // AA$, AAA$, etc. -> A$
            .replace(/^(S\$){2,}/, 'S$')   // SS$, SSS$, etc. -> S$
            .replace(/^(R\$){2,}/, 'R$');  // RR$, RRR$, etc. -> R$
        
        // Pattern 2: Single character repeated (e.g., "$$" -> "$", "££" -> "£")
        text = text.replace(/^([\$€£₹¥₨৳])\1+/, '$1');
        
        // Pattern 3: Mixed patterns (e.g., "C$$" -> "C$", "A$C$" -> "C$")
        // If we have a multi-character symbol followed by $, remove the extra $
        text = text.replace(/^(C|A|S|R)\$\$+/, '$1$');
        
        // Pattern 4: Remove any trailing currency symbols (shouldn't happen, but safety check)
        text = text.replace(/[\$€£₹¥₨৳]+$/, '');
        
        return text.trim();
    }
    
    /**
     * Format price with currency symbol
     * Ensures no duplicate symbols by stripping existing symbols first
     */
    function formatPrice(amount, currency) {
        // Strip any existing currency symbols from amount if it's a string
        let numericAmount = amount;
        if (typeof amount === 'string') {
            const stripped = stripCurrencySymbols(amount);
            numericAmount = parseFloat(stripped) || 0;
        } else {
            numericAmount = parseFloat(amount) || 0;
        }
        
        // Use currency data from AmadexConfig if available
        if (typeof AmadexConfig !== 'undefined' && AmadexConfig.currency && AmadexConfig.currency.currencies) {
            const currencyInfo = AmadexConfig.currency.currencies[currency];
            if (currencyInfo && currencyInfo.symbol) {
                const symbol = currencyInfo.symbol;
                // Format with currency symbol FIRST, then amount
                const formatted = symbol + numericAmount.toFixed(2);
                // Final cleanup to prevent any duplicate symbols
                return cleanDuplicateCurrencySymbols(formatted);
            }
        }
        
        // Fallback to basic symbols
        const symbols = {
            'USD': '$',
            'EUR': '€',
            'GBP': '£',
            'JPY': '¥',
            'INR': '₹',
            'CAD': 'C$',
            'AUD': 'A$',
            'MXN': '$',
            'SGD': 'S$',
            'AED': 'د.إ',
            'BDT': '৳',
            'PKR': '₨',
            'BRL': 'R$',
            'CNY': '¥'
        };
        
        const symbol = symbols[currency] || currency;
        // Format with currency symbol FIRST, then amount
        const formatted = symbol + numericAmount.toFixed(2);
        // Final cleanup to prevent any duplicate symbols
        return cleanDuplicateCurrencySymbols(formatted);
    }
    
    /**
     * Initialize currency detection and conversion on results page
     * REMOVED: Results page currency selector - now uses Regional Settings Modal only
     */
    function initCurrencyDetection() {
        if (typeof AmadexConfig === 'undefined' || !AmadexConfig.currency) {
            return;
        }
        
        // Check if regional settings system is enabled (from AmadexConfig)
        const regionalSettingsEnabled = AmadexConfig.currency.regionalSettingsEnabled !== false; // Default to true if not set
        
        // If regional settings disabled, force USD and skip all detection/conversion
        if (!regionalSettingsEnabled) {
            console.log('Regional Settings System disabled - using USD, skipping currency detection and conversion');
            // Ensure USD is set in storage for consistency
            if (typeof sessionStorage !== 'undefined') {
                sessionStorage.setItem('amadex_selected_currency', 'USD');
            }
            // No conversion needed - prices are already in USD
            return;
        }
        
        // Regional settings enabled - proceed with normal detection
        // EXPERT/GOD MODE FIX: Unified currency detection with proper priority
        // This ensures manual selection always takes precedence over auto-detection
        let selectedCurrency = 'USD';
        let currencySource = 'default';
        
        // Priority 1: Check localStorage (manual selection) - ALWAYS CHECK FIRST
        // This is the user's explicit choice and must take precedence
        try {
            const savedSettings = localStorage.getItem('amadex_regional_settings');
            if (savedSettings) {
                const settings = JSON.parse(savedSettings);
                if (settings.currency) {
                    selectedCurrency = settings.currency;
                    currencySource = 'localStorage (manual selection)';

                }
            }
        } catch (e) {
            console.error('Error reading saved regional settings:', e);
        }
        
        // Priority 2: Check sessionStorage (only if no manual selection found)
        // This is for current session preference, but manual selection overrides it
        if (selectedCurrency === 'USD' && typeof sessionStorage !== 'undefined') {
            const sessionCurrency = sessionStorage.getItem('amadex_selected_currency');
            if (sessionCurrency && sessionCurrency !== 'USD') {
                selectedCurrency = sessionCurrency;
                currencySource = 'sessionStorage';
                console.log('Using currency from sessionStorage:', selectedCurrency);
            }
        }
        
        // Priority 3: Server-detected (only if no manual selection and no sessionStorage)
        // This is auto-detection, lowest priority - only used if user hasn't selected
        if (selectedCurrency === 'USD') {
            if (regionalSettingsEnabled && AmadexConfig.currency.detected) {
                selectedCurrency = AmadexConfig.currency.detected || AmadexConfig.currency.default || 'USD';
                currencySource = 'server-detected';
                console.log('Using server-detected currency:', selectedCurrency);
            }
        }
        
        // CRITICAL: Always update sessionStorage with detected currency for booking page
        // This ensures booking page will read correct currency
        if (selectedCurrency && typeof sessionStorage !== 'undefined') {
            sessionStorage.setItem('amadex_selected_currency', selectedCurrency);

        }
        
        // EXPERT/GOD MODE FIX: Always convert prices, even if USD
        // This ensures prices are displayed in correct currency immediately
        if (selectedCurrency) {

            // Wait a bit for DOM to be ready
            setTimeout(function() {
                convertAllPricesToCurrency(selectedCurrency);
            }, 300);
        } else {
            console.log('No currency selected, using USD (default)');
        }
        
        // Listen for currency changes from regional settings modal (only way to change currency now)
        $(document).on('amadex:currency-changed', function(event, currency) {
            if (currency) {
                if (typeof sessionStorage !== 'undefined') {
                    sessionStorage.setItem('amadex_selected_currency', currency);
                }
                convertAllPricesToCurrency(currency);
            }
        });
        
        // Listen for direct conversion trigger
        $(document).on('amadex:convert-prices-now', function(event, currency) {
            if (currency) {
                convertAllPricesToCurrency(currency);
            }
        });
    }
    
    /**
     * Convert all prices on results page to selected currency
     * Exposed globally for regional settings integration
     * 
     * When Regional Settings System is disabled, this function immediately returns
     * without performing any conversion, ensuring all prices remain in USD.
     * 
     * @since 1.0.0
     * @param {string} targetCurrency - Target currency code (e.g., 'USD', 'EUR', 'INR')
     * @returns {Promise<void>}
     * 
     * @example
     * // Convert all prices to INR (only if regional settings enabled)
     * convertAllPricesToCurrency('INR');
     * // Returns immediately if regional settings disabled
     */
    async function convertAllPricesToCurrency(targetCurrency) {
        // Expose function globally for regional settings
        if (typeof window !== 'undefined') {
            window.amadexConvertAllPrices = convertAllPricesToCurrency;
        }
        
        // Check if regional settings system is enabled (from AmadexConfig)
        const regionalSettingsEnabled = typeof AmadexConfig !== 'undefined' && 
                                       AmadexConfig.currency && 
                                       AmadexConfig.currency.regionalSettingsEnabled !== false;
        
        // If regional settings disabled, skip conversion and ensure USD
        if (!regionalSettingsEnabled) {
            console.log('Regional Settings System disabled - skipping currency conversion, using USD');
            // Restore original prices (which are in USD)
            restoreOriginalPrices();
            return;
        }
        
        if (!targetCurrency || targetCurrency === 'USD') {
            // If USD, show original prices (no conversion needed)
            restoreOriginalPrices();
            return;
        }
        
        if (typeof AmadexConfig === 'undefined' || !AmadexConfig.currency) {
            console.warn('AmadexConfig.currency not available - skipping currency conversion');
            return;
        }
        
        try {

            
            // Get all flight cards with prices - try multiple selectors
            const $flightCards = $('.amadex-flight-card, .amadex-flight-result-card, [data-flight-index]');

            
            if ($flightCards.length === 0) {
                // Try to find any price elements directly - include amadex-flight-price
                const $allPriceElements = $('.amadex-price-badge, .amadex-price, .amadex-flight-price, [data-price], .price, .flight-price, span.amadex-flight-price');
                
                $allPriceElements.each(function() {
                    const $priceElement = $(this);
                    convertSinglePriceElement($priceElement, targetCurrency);
                });
            } else {
                $flightCards.each(function() {
                    const $card = $(this);
                    // Try multiple selectors for price elements - include amadex-flight-price
                    // Find ALL price elements, not just the first one
                    const $priceElements = $card.find('.amadex-price-badge, .amadex-price, .amadex-flight-price, span.amadex-flight-price, [data-price], .price, .flight-price, .amadex-price-box-price');
                    
                    if ($priceElements.length > 0) {
                        // Convert all price elements found in this card
                        $priceElements.each(function() {
                            const $priceElement = $(this);
                            convertSinglePriceElement($priceElement, targetCurrency);
                        });
                    } else {
                        // If no price element found, try to find it in the card's text
                        const cardText = $card.text();
                        const priceMatch = cardText.match(/\$([\d,]+\.?\d*)/);
                        if (priceMatch) {
                            const originalPrice = parseFloat(priceMatch[1].replace(/,/g, ''));
                            if (originalPrice > 0) {
                                // Find the element containing this price
                                const $priceContainer = $card.find('*:contains("$' + priceMatch[1] + '")').first();
                                if ($priceContainer.length) {
                                    convertSinglePriceElement($priceContainer, targetCurrency);
                                }
                            }
                        }
                    }
                });
            }
            
            // Update price range filters
            updatePriceRangeForCurrency(targetCurrency);
            

            
        } catch (error) {
            console.error('Error converting prices:', error);
        }
    }
    
    /**
     * Convert a single price element
     */
    function convertSinglePriceElement($priceElement, targetCurrency) {
        // Skip if already converted to this currency
        const currentCurrency = $priceElement.attr('data-currency');
        if (currentCurrency === targetCurrency) {
            return;
        }
        
        // Get original USD price from data attribute or parse from text
        // Priority: Use data-price (per-person price) first since that's what's displayed
        // Then fall back to data-original-price (total price) if data-price doesn't exist
        let originalPrice = parseFloat($priceElement.attr('data-price') || $priceElement.attr('data-original-price') || 0);
        
        // Check if we have data-original-text which might contain the formatted price
        const originalText = $priceElement.attr('data-original-text');
        if (originalText && (!originalPrice || originalPrice === 0)) {
            // Extract numeric value from original text (handles formats like "C$1335.68", "$960.92", etc.)
            const cleanedText = originalText.replace(/[^0-9.]/g, '');
            originalPrice = parseFloat(cleanedText) || 0;
        }
        
        // If no data attribute, try to parse from current text (remove currency symbols)
        if (!originalPrice || originalPrice === 0) {
            // Try to extract price from text - handle various formats like "$532.53", "532.53", "$1,206.03", "C$1335.68"
            const priceText = $priceElement.text().trim();
            // Remove currency symbols and commas, keep numbers and decimal point
            // Handle cases like "CC$1335.68" by removing all non-numeric characters except decimal point
            const cleanedText = priceText.replace(/[^0-9.]/g, '');
            originalPrice = parseFloat(cleanedText) || 0;
            
            // If still 0, try to get from parent element's data-price
            if (originalPrice === 0) {
                const $parent = $priceElement.closest('[data-price]');
                if ($parent.length) {
                    originalPrice = parseFloat($parent.attr('data-price')) || 0;
                }
            }
        }
        
        if (originalPrice > 0) {
            // Store original price if not already stored
            if (!$priceElement.attr('data-original-price')) {
                $priceElement.attr('data-original-price', originalPrice);
            }
            

            
            // Convert price
            convertPriceAndUpdate(originalPrice, 'USD', targetCurrency, $priceElement);
        } else {
            console.warn('Could not extract price from element:', $priceElement[0], 'Text:', $priceElement.text());
        }
    }
    
    /**
     * Convert multiple prices to selected currency (helper function)
     */
    async function convertPricesToCurrency(baseAmount, totalAmount, fromCurrency, toCurrency) {
        if (fromCurrency === toCurrency) {
            return {
                base: baseAmount,
                total: totalAmount || baseAmount,
                rate: 1.0
            };
        }
        
        try {
            const response = await $.ajax({
                url: AmadexConfig.currency.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'amadex_get_exchange_rate',
                    nonce: AmadexConfig.currency.nonce,
                    from_currency: fromCurrency,
                    to_currency: toCurrency
                }
            });
            
            if (response.success && response.data && response.data.rate) {
                const rate = parseFloat(response.data.rate);
                return {
                    base: baseAmount * rate,
                    total: (totalAmount || baseAmount) * rate,
                    rate: rate
                };
            }
        } catch (error) {
            console.error('Error converting prices:', error);
        }
        
        return {
            base: baseAmount,
            total: totalAmount || baseAmount,
            rate: 1.0
        };
    }
    
    /**
     * Convert a single price and update element
     */
    async function convertPriceAndUpdate(usdAmount, fromCurrency, toCurrency, $element) {
        if (!usdAmount || usdAmount <= 0 || fromCurrency === toCurrency) {
            return;
        }
        
        try {
            // Get exchange rate from server
            const response = await $.ajax({
                url: AmadexConfig.currency.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'amadex_get_exchange_rate',
                    nonce: AmadexConfig.currency.nonce,
                    from_currency: fromCurrency,
                    to_currency: toCurrency
                }
            });
            
            if (response.success && response.data && response.data.rate) {
                const exchangeRate = parseFloat(response.data.rate);
                const convertedAmount = usdAmount * exchangeRate;
                const formattedPrice = formatPrice(convertedAmount, toCurrency);
                

                
                // Update price element - try multiple methods
                // Store original text if needed for restoration (before any conversion)
                if (!$element.attr('data-original-text')) {
                    const currentText = $element.text().trim();
                    // Clean up any double currency symbols (like "CC$" -> "C$")
                    const cleanedText = cleanDuplicateCurrencySymbols(currentText);
                    $element.attr('data-original-text', cleanedText);
                }
                
                // Check if we should use data-original-text instead of formatting
                // Priority: Use data-original-text if it exists and currency matches
                const originalText = $element.attr('data-original-text');
                let displayText = formattedPrice;
                
                // Get current currency from element to check if conversion is needed
                const currentCurrency = $element.attr('data-currency') || fromCurrency;
                
                // If converting to the same currency as original, use original text if available
                if (originalText && originalText.trim() && toCurrency === currentCurrency) {
                    // Extract numeric values to compare (strip currency symbols first)
                    const originalNumeric = parseFloat(stripCurrencySymbols(originalText));
                    const convertedNumeric = parseFloat(convertedAmount.toFixed(2));
                    
                    // If numbers match (within 0.01), use original text format (preserves nice formatting)
                    if (Math.abs(originalNumeric - convertedNumeric) < 0.01) {
                        displayText = cleanDuplicateCurrencySymbols(originalText);
                        console.log('Using data-original-text for display:', displayText);
                    } else {
                        // Numbers don't match, use formatted price (already cleaned by formatPrice)
                        displayText = formattedPrice;
                    }
                } else {
                    // Different currency or no original text - use formatted price
                    // formatPrice already cleans duplicates, but ensure it's clean
                    displayText = formattedPrice;
                }
                
                // Final cleanup: remove any double currency symbols (safety check)
                displayText = cleanDuplicateCurrencySymbols(displayText);
                
                // Update the text content
                $element.text(displayText);
                $element.attr('data-price', convertedAmount);
                $element.attr('data-currency', toCurrency);
                
                // Also store the formatted text for easy restoration
                $element.attr('data-formatted-text', displayText);
                
                // Also update parent wrapper if it contains the price text (for amadex-flight-price-wrapper)
                const $parentWrapper = $element.closest('.amadex-flight-price-wrapper, .amadex-price-wrapper');
                if ($parentWrapper.length) {
                    const wrapperHtml = $parentWrapper.html();
                    // Replace any USD price in the wrapper
                    const updatedHtml = wrapperHtml.replace(/\$\s*[\d,]+\.?\d*/g, formattedPrice);
                    if (updatedHtml !== wrapperHtml) {
                        $parentWrapper.html(updatedHtml);
                    }
                }
                
                // Also update parent elements if they contain the price
                $element.parent().each(function() {
                    const $parent = $(this);
                    const parentText = $parent.text();
                    if (parentText.includes('$' + usdAmount.toFixed(2)) || parentText.match(/\$\d+\.\d{2}/)) {
                        $parent.html($parent.html().replace(/\$\d+\.\d{2}/g, formattedPrice));
                    }
                });
            } else {
                console.error('Failed to get exchange rate:', response);
            }
        } catch (error) {
            console.error('Error converting price:', error);
        }
    }
    
    /**
     * Restore original USD prices
     * Uses data-original-text if available for better formatting
     */
    function restoreOriginalPrices() {
        $('[data-original-price]').each(function() {
            const $element = $(this);
            const originalText = $element.attr('data-original-text');
            const originalPrice = parseFloat($element.attr('data-original-price'));
            
            if (originalText) {
                // Use original text if available (already formatted nicely)
                // Clean up any double currency symbols first
                const cleanedText = originalText.replace(/^([C$€£₹¥A$S$R$د\.إ৳₨]+)\1+/, '$1');
                $element.text(cleanedText);
            } else if (originalPrice > 0) {
                // Fallback to formatting from price value
                $element.text(formatPrice(originalPrice, 'USD'));
            }
            
            if (originalPrice > 0) {
                $element.attr('data-price', originalPrice);
                $element.attr('data-currency', 'USD');
            }
        });
    }
    
    /**
     * Get currency symbol for a currency code
     */
    function getCurrencySymbol(currency) {
        if (typeof AmadexConfig !== 'undefined' && AmadexConfig.currency && AmadexConfig.currency.currencies) {
            const currencyInfo = AmadexConfig.currency.currencies[currency];
            if (currencyInfo && currencyInfo.symbol) {
                return currencyInfo.symbol;
            }
        }
        
        // Fallback symbols
        const symbols = {
            'USD': '$', 'EUR': '€', 'GBP': '£', 'INR': '₹', 'JPY': '¥',
            'CAD': 'C$', 'AUD': 'A$', 'MXN': '$', 'SGD': 'S$', 'AED': 'د.إ',
            'BDT': '৳', 'PKR': '₨', 'BRL': 'R$', 'CNY': '¥'
        };
        return symbols[currency] || currency;
    }
    
    /**
     * Initialize price display - use data-original-text if available for nice formatting
     * This ensures prices display correctly on page load and fixes double currency symbols
     */
    function initializePriceDisplay() {
        $('.amadex-flight-price').each(function() {
            const $element = $(this);
            const originalText = $element.attr('data-original-text');
            const currentText = $element.text().trim();
            const currentCurrency = $element.attr('data-currency');
            
            // Priority 1: If data-original-text exists and currency matches, use it
            if (originalText && originalText.trim()) {
                // Clean up any double currency symbols in original text first
                let cleanedOriginal = cleanDuplicateCurrencySymbols(originalText);
                
                // Check if current text has double currency symbols (like "CC$", "AA$")
                if (currentText.match(/^(C\$|A\$|S\$|R\$){2,}|^[\$€£₹¥₨৳]{2,}/)) {
                    // Definitely use original text to fix double symbols
                    $element.text(cleanedOriginal);
                    console.log('Fixed double currency symbol using data-original-text:', cleanedOriginal);
                } else {
                    // Check if current text matches original (to avoid unnecessary updates)
                    const currentNumeric = parseFloat(currentText.replace(/[^0-9.]/g, ''));
                    const originalNumeric = parseFloat(cleanedOriginal.replace(/[^0-9.]/g, ''));
                    
                    // If numbers match and currency matches, prefer original text format
                    if (Math.abs(currentNumeric - originalNumeric) < 0.01) {
                        // Use original text to preserve nice formatting (e.g., "C$1640.74" instead of formatted version)
                        if (currentText !== cleanedOriginal) {
                            $element.text(cleanedOriginal);
                            console.log('Using data-original-text for better formatting:', cleanedOriginal);
                        }
                    }
                }
            } else if (currentText.match(/^(C\$|A\$|S\$|R\$){2,}|^[\$€£₹¥₨৳]{2,}/)) {
                // No original text but current has double symbols - clean it up
                const cleaned = cleanDuplicateCurrencySymbols(currentText);
                $element.text(cleaned);
                // Store cleaned version as original for future use
                $element.attr('data-original-text', cleaned);
                console.log('Fixed double currency symbol and stored as original:', cleaned);
            } else if (originalText && !$element.attr('data-original-text')) {
                // Store current text as original if not already stored
                $element.attr('data-original-text', currentText);
            }
        });
    }
    
    /**
     * Update price range filters for selected currency
     */
    async function updatePriceRangeForCurrency(currency) {
        // This would update the price range sliders if they exist
        // Implementation depends on your filter structure
    }

    /**
     * Format time
     */
    function formatTime(date) {
        return date.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        });
    }

    /**
     * Format date
     */
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            weekday: 'short',
            day: 'numeric',
            month: 'short'
        });
    }
    
    /**
     * Create PHONE_LEAD when user clicks Call Now
     */
    function createPhoneLead(flightData) {
        const searchData = getStoredSearchData();
        
        $.ajax({
            url: AmadexConfig.ajaxUrl,
            type: 'POST',
            data: {
                action: 'amadex_create_lead',
                nonce: AmadexConfig.nonce,
                lead_type: 'PHONE_LEAD',
                contact_name: 'Call Inquiry',
                contact_email: '',
                contact_phone: '',
                flight_data: flightData,
                search_params: searchData,
                source: 'PHONE',
                notes: 'User clicked Call Now button from flight results'
            },
            success: function(response) {
                console.log('Phone lead created:', response);
            },
            error: function() {
                console.log('Failed to create phone lead');
            }
        });
    }

    /**
     * Normalize cabin class value
     * Converts 'ANY' to ECONOMY (default) or validates enum values
     * 
     * @param {string} cabinClass - The cabin class value
     * @returns {string} Normalized cabin class (ECONOMY, PREMIUM_ECONOMY, BUSINESS, FIRST)
     */
    function normalizeCabinClass(cabinClass) {
        if (!cabinClass) {
            return 'ECONOMY'; // Default to ECONOMY
        }
        
        const upperClass = cabinClass.toUpperCase().trim();
        
        // Valid enum values for Amadeus API
        const validClasses = ['ECONOMY', 'PREMIUM_ECONOMY', 'BUSINESS', 'FIRST'];
        
        // Return if valid
        if (validClasses.includes(upperClass)) {
            return upperClass;
        }
        
        // Map common variations
        const classMap = {
            'ANY': 'ECONOMY', // Default ANY to ECONOMY
            'PREMIUM': 'PREMIUM_ECONOMY',
            'FIRST_CLASS': 'FIRST'
        };
        
        if (classMap[upperClass]) {
            return classMap[upperClass];
        }
        
        // Default to ECONOMY for invalid values
        console.warn('Invalid cabin class:', cabinClass, '- defaulting to ECONOMY');
        return 'ECONOMY';
    }
    
    /**
     * Get cabin class display name
     */
    function getCabinDisplayName(code) {
        const cabinClasses = {
            'ECONOMY': 'Economy',
            'PREMIUM_ECONOMY': 'Premium Economy',
            'BUSINESS': 'Business',
            'FIRST': 'First Class',
            'NO PREFERENCE': 'No Preference'
        };
        
        return cabinClasses[code] || code;
    }
    
    /**
     * Get stored search data from session storage
     */
    function getStoredSearchData() {
        try {
            const stored = sessionStorage.getItem('amadex_search_data');
            return stored ? JSON.parse(stored) : {};
        } catch (e) {
            return {};
        }
    }

    /**
     * Get airport/city information by IATA code
     */
    function getAirportInfo(iataCode) {
        const airports = {
            // US Airports
            'JFK': { city: 'New York', airport: 'John F. Kennedy International', country: 'US' },
            'LGA': { city: 'New York', airport: 'LaGuardia', country: 'US' },
            'EWR': { city: 'Newark', airport: 'Newark Liberty International', country: 'US' },
            'LAX': { city: 'Los Angeles', airport: 'Los Angeles International', country: 'US' },
            'SFO': { city: 'San Francisco', airport: 'San Francisco International', country: 'US' },
            'ORD': { city: 'Chicago', airport: "O'Hare International", country: 'US' },
            'MIA': { city: 'Miami', airport: 'Miami International', country: 'US' },
            'DFW': { city: 'Dallas', airport: 'Dallas/Fort Worth International', country: 'US' },
            'ATL': { city: 'Atlanta', airport: 'Hartsfield-Jackson Atlanta International', country: 'US' },
            'BOS': { city: 'Boston', airport: 'Logan International', country: 'US' },
            'SEA': { city: 'Seattle', airport: 'Seattle-Tacoma International', country: 'US' },
            'LAS': { city: 'Las Vegas', airport: 'Harry Reid International', country: 'US' },
            'MCO': { city: 'Orlando', airport: 'Orlando International', country: 'US' },
            'PHX': { city: 'Phoenix', airport: 'Sky Harbor International', country: 'US' },
            'IAH': { city: 'Houston', airport: 'George Bush Intercontinental', country: 'US' },
            'SMF': { city: 'Sacramento', airport: 'Sacramento International', country: 'US' },
            'DCA': { city: 'Washington', airport: 'Ronald Reagan Washington National', country: 'US' },
            'IAD': { city: 'Washington', airport: 'Washington Dulles International', country: 'US' },
            
            // UK Airports
            'LHR': { city: 'London', airport: 'Heathrow', country: 'GB' },
            'LGW': { city: 'London', airport: 'Gatwick', country: 'GB' },
            'STN': { city: 'London', airport: 'Stansted', country: 'GB' },
            'LTN': { city: 'London', airport: 'Luton', country: 'GB' },
            'MAN': { city: 'Manchester', airport: 'Manchester', country: 'GB' },
            'EDI': { city: 'Edinburgh', airport: 'Edinburgh', country: 'GB' },
            'BHX': { city: 'Birmingham', airport: 'Birmingham', country: 'GB' },
            'GLA': { city: 'Glasgow', airport: 'Glasgow', country: 'GB' },
            
            // European Airports
            'CDG': { city: 'Paris', airport: 'Charles de Gaulle', country: 'FR' },
            'ORY': { city: 'Paris', airport: 'Orly', country: 'FR' },
            'AMS': { city: 'Amsterdam', airport: 'Schiphol', country: 'NL' },
            'FRA': { city: 'Frankfurt', airport: 'Frankfurt', country: 'DE' },
            'MUC': { city: 'Munich', airport: 'Munich', country: 'DE' },
            'MAD': { city: 'Madrid', airport: 'Barajas', country: 'ES' },
            'BCN': { city: 'Barcelona', airport: 'El Prat', country: 'ES' },
            'FCO': { city: 'Rome', airport: 'Fiumicino', country: 'IT' },
            'MXP': { city: 'Milan', airport: 'Malpensa', country: 'IT' },
            'ZRH': { city: 'Zurich', airport: 'Zurich', country: 'CH' },
            'VIE': { city: 'Vienna', airport: 'Vienna International', country: 'AT' },
            'CPH': { city: 'Copenhagen', airport: 'Copenhagen', country: 'DK' },
            'ARN': { city: 'Stockholm', airport: 'Arlanda', country: 'SE' },
            'OSL': { city: 'Oslo', airport: 'Gardermoen', country: 'NO' },
            'HEL': { city: 'Helsinki', airport: 'Helsinki-Vantaa', country: 'FI' },
            'IST': { city: 'Istanbul', airport: 'Istanbul', country: 'TR' },
            'ATH': { city: 'Athens', airport: 'Eleftherios Venizelos', country: 'GR' },
            'LIS': { city: 'Lisbon', airport: 'Humberto Delgado', country: 'PT' },
            'DUB': { city: 'Dublin', airport: 'Dublin', country: 'IE' },
            
            // Middle East Airports
            'DXB': { city: 'Dubai', airport: 'Dubai International', country: 'AE' },
            'AUH': { city: 'Abu Dhabi', airport: 'Abu Dhabi International', country: 'AE' },
            'DOH': { city: 'Doha', airport: 'Hamad International', country: 'QA' },
            'RUH': { city: 'Riyadh', airport: 'King Khalid International', country: 'SA' },
            'JED': { city: 'Jeddah', airport: 'King Abdulaziz International', country: 'SA' },
            'CAI': { city: 'Cairo', airport: 'Cairo International', country: 'EG' },
            'TLV': { city: 'Tel Aviv', airport: 'Ben Gurion', country: 'IL' },
            'MCT': { city: 'Muscat', airport: 'Muscat International', country: 'OM' },
            'KWI': { city: 'Kuwait', airport: 'Kuwait International', country: 'KW' },
            'BAH': { city: 'Bahrain', airport: 'Bahrain International', country: 'BH' },
            
            // Indian Airports
            'DEL': { city: 'New Delhi', airport: 'Indira Gandhi International', country: 'IN' },
            'BOM': { city: 'Mumbai', airport: 'Chhatrapati Shivaji Maharaj International', country: 'IN' },
            'BLR': { city: 'Bangalore', airport: 'Kempegowda International', country: 'IN' },
            'HYD': { city: 'Hyderabad', airport: 'Rajiv Gandhi International', country: 'IN' },
            'MAA': { city: 'Chennai', airport: 'Chennai International', country: 'IN' },
            'CCU': { city: 'Kolkata', airport: 'Netaji Subhas Chandra Bose International', country: 'IN' },
            'GOI': { city: 'Goa', airport: 'Dabolim', country: 'IN' },
            'COK': { city: 'Kochi', airport: 'Cochin International', country: 'IN' },
            'AMD': { city: 'Ahmedabad', airport: 'Sardar Vallabhbhai Patel International', country: 'IN' },
            'PNQ': { city: 'Pune', airport: 'Pune', country: 'IN' },
            
            // Asian Airports
            'SIN': { city: 'Singapore', airport: 'Changi', country: 'SG' },
            'HKG': { city: 'Hong Kong', airport: 'Hong Kong International', country: 'HK' },
            'NRT': { city: 'Tokyo', airport: 'Narita International', country: 'JP' },
            'HND': { city: 'Tokyo', airport: 'Haneda', country: 'JP' },
            'ICN': { city: 'Seoul', airport: 'Incheon International', country: 'KR' },
            'PEK': { city: 'Beijing', airport: 'Capital International', country: 'CN' },
            'PVG': { city: 'Shanghai', airport: 'Pudong International', country: 'CN' },
            'BKK': { city: 'Bangkok', airport: 'Suvarnabhumi', country: 'TH' },
            'KUL': { city: 'Kuala Lumpur', airport: 'Kuala Lumpur International', country: 'MY' },
            'MNL': { city: 'Manila', airport: 'Ninoy Aquino International', country: 'PH' },
            'CGK': { city: 'Jakarta', airport: 'Soekarno-Hatta International', country: 'ID' },
            'HAN': { city: 'Hanoi', airport: 'Noi Bai International', country: 'VN' },
            'SGN': { city: 'Ho Chi Minh City', airport: 'Tan Son Nhat International', country: 'VN' },
            
            // Australian Airports
            'SYD': { city: 'Sydney', airport: 'Kingsford Smith', country: 'AU' },
            'MEL': { city: 'Melbourne', airport: 'Melbourne', country: 'AU' },
            'BNE': { city: 'Brisbane', airport: 'Brisbane', country: 'AU' },
            'PER': { city: 'Perth', airport: 'Perth', country: 'AU' },
            'AKL': { city: 'Auckland', airport: 'Auckland', country: 'NZ' },
            
            // Canadian Airports
            'YYZ': { city: 'Toronto', airport: 'Toronto Pearson International', country: 'CA' },
            'YVR': { city: 'Vancouver', airport: 'Vancouver International', country: 'CA' },
            'YUL': { city: 'Montreal', airport: 'Montreal-Trudeau International', country: 'CA' },
            'YYC': { city: 'Calgary', airport: 'Calgary International', country: 'CA' },
            
            // South American Airports
            'GRU': { city: 'São Paulo', airport: 'Guarulhos International', country: 'BR' },
            'GIG': { city: 'Rio de Janeiro', airport: 'Galeão International', country: 'BR' },
            'EZE': { city: 'Buenos Aires', airport: 'Ministro Pistarini International', country: 'AR' },
            'BOG': { city: 'Bogotá', airport: 'El Dorado International', country: 'CO' },
            'LIM': { city: 'Lima', airport: 'Jorge Chávez International', country: 'PE' },
            'SCL': { city: 'Santiago', airport: 'Arturo Merino Benítez International', country: 'CL' },
            
            // African Airports
            'JNB': { city: 'Johannesburg', airport: 'O.R. Tambo International', country: 'ZA' },
            'CPT': { city: 'Cape Town', airport: 'Cape Town International', country: 'ZA' },
            'NBO': { city: 'Nairobi', airport: 'Jomo Kenyatta International', country: 'KE' },
            'ADD': { city: 'Addis Ababa', airport: 'Addis Ababa Bole International', country: 'ET' },
            'LOS': { city: 'Lagos', airport: 'Murtala Muhammed International', country: 'NG' },
            'CMN': { city: 'Casablanca', airport: 'Mohammed V International', country: 'MA' }
        };
        
        return airports[iataCode] || { city: iataCode, airport: iataCode, country: '' };
    }

    // Hide suggestions when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.amadex-field').length) {
            $('.amadex-airport-suggestions').remove();
        }
    });
    
    // Aircraft details hover/click functionality
    let aircraftTooltipTimeout = null;
    let currentAircraftTooltip = null;
    
    // Desktop: Show tooltip on hover
    $(document).on('mouseenter', '.amadex-leg-aircraft-info', function(e) {
        const $el = $(this);
        const aircraftCode = $el.data('aircraft-code') || '';
        const aircraftName = $el.data('aircraft-name') || $el.text() || '';
        
        if (!aircraftCode) return;
        
        // Clear any existing timeout
        if (aircraftTooltipTimeout) {
            clearTimeout(aircraftTooltipTimeout);
        }
        
        // Delay tooltip to avoid flickering on quick hover
        aircraftTooltipTimeout = setTimeout(function() {
            loadAircraftDetails($el, aircraftCode, aircraftName, true);
        }, 300);
    });
    
    // Desktop: Hide tooltip on mouse leave
    $(document).on('mouseleave', '.amadex-leg-aircraft-info', function() {
        if (aircraftTooltipTimeout) {
            clearTimeout(aircraftTooltipTimeout);
            aircraftTooltipTimeout = null;
        }
        hideAircraftTooltip();
    });
    
    // Mobile: Show details on click
    $(document).on('click', '.amadex-leg-aircraft-info', function(e) {
        // Only handle on mobile/touch devices
        if (window.innerWidth > 768) return;
        
        const $el = $(this);
        const aircraftCode = $el.data('aircraft-code') || '';
        const aircraftName = $el.data('aircraft-name') || $el.text() || '';
        
        if (!aircraftCode) return;
        
        e.preventDefault();
        e.stopPropagation();
        
        loadAircraftDetails($el, aircraftCode, aircraftName, false);
    });
    
    // Close tooltip when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.amadex-leg-aircraft-info, .amadex-aircraft-tooltip').length) {
            hideAircraftTooltip();
        }
    });
    
    /**
     * Load aircraft details via AJAX and show tooltip/popup
     */
    function loadAircraftDetails($element, aircraftCode, aircraftName, isHover) {
        // Remove existing tooltip first
        hideAircraftTooltip();
        
        // Get nonce from page
        const nonce = $('#amadex-search-form, [data-amadex-nonce]').first().data('amadex-nonce') || 
                     $('[name="amadex_nonce"]').first().val() ||
                     (typeof AmadexConfig !== 'undefined' && AmadexConfig.nonce ? AmadexConfig.nonce : '');
        
        if (!nonce) {
            console.warn('Amadex: Aircraft details nonce not found');
            // Fallback: show basic tooltip with current name
            showAircraftTooltip($element, {
                code: aircraftCode,
                name: aircraftName,
                description: ''
            }, isHover);
            return;
        }
        
        // Check cache first (using sessionStorage for quick lookups)
        const cacheKey = 'amadex_aircraft_' + aircraftCode;
        const cached = sessionStorage.getItem(cacheKey);
        if (cached) {
            try {
                const cachedData = JSON.parse(cached);
                showAircraftTooltip($element, cachedData, isHover);
                return;
            } catch(e) {
                // Invalid cache, continue to API
            }
        }
        
        // Show loading state
        $element.addClass('amadex-aircraft-loading');
        
        // Fetch from API
        const ajaxUrl = typeof AmadexConfig !== 'undefined' && AmadexConfig.ajaxUrl ? AmadexConfig.ajaxUrl : '/wp-admin/admin-ajax.php';
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'amadex_aircraft_details',
                nonce: nonce,
                aircraft_code: aircraftCode
            },
            success: function(response) {
                $element.removeClass('amadex-aircraft-loading');
                if (response.success && response.data) {
                    // Cache the result
                    sessionStorage.setItem(cacheKey, JSON.stringify(response.data));
                    showAircraftTooltip($element, response.data, isHover);
                } else {
                    // Fallback to local mapping
                    showAircraftTooltip($element, {
                        code: aircraftCode,
                        name: aircraftName,
                        description: ''
                    }, isHover);
                }
            },
            error: function() {
                $element.removeClass('amadex-aircraft-loading');
                // Fallback to local mapping on error
                showAircraftTooltip($element, {
                    code: aircraftCode,
                    name: aircraftName,
                    description: ''
                }, isHover);
            }
        });
    }
    
    /**
     * Show aircraft details tooltip/popup
     */
    function showAircraftTooltip($element, aircraftData, isHover) {
        const aircraftName = aircraftData.name || aircraftData.code || '';
        const aircraftDescription = aircraftData.description || '';
        const aircraftCode = aircraftData.code || '';
        
        // Create tooltip HTML
        let tooltipHtml = `
            <div class="amadex-aircraft-tooltip ${isHover ? 'amadex-aircraft-tooltip-hover' : 'amadex-aircraft-tooltip-click'}">
                <div class="amadex-aircraft-tooltip-header">
                    <span class="amadex-aircraft-tooltip-code">${aircraftCode}</span>
                    <button class="amadex-aircraft-tooltip-close" aria-label="Close">×</button>
                </div>
                <div class="amadex-aircraft-tooltip-body">
                    <div class="amadex-aircraft-tooltip-name">${aircraftName}</div>
                    ${aircraftDescription ? `<div class="amadex-aircraft-tooltip-description">${aircraftDescription}</div>` : ''}
                </div>
            </div>
        `;
        
        const $tooltip = $(tooltipHtml);
        $('body').append($tooltip);
        currentAircraftTooltip = $tooltip;
        
        // Position tooltip
        positionAircraftTooltip($element, $tooltip, isHover);
        
        // Close button handler
        $tooltip.find('.amadex-aircraft-tooltip-close').on('click', function(e) {
            e.stopPropagation();
            hideAircraftTooltip();
        });
    }
    
    /**
     * Position aircraft tooltip relative to element
     */
    function positionAircraftTooltip($element, $tooltip, isHover) {
        const elementOffset = $element.offset();
        const elementWidth = $element.outerWidth();
        const elementHeight = $element.outerHeight();
        const tooltipWidth = $tooltip.outerWidth();
        const tooltipHeight = $tooltip.outerHeight();
        
        let top = elementOffset.top + elementHeight + 8;
        let left = elementOffset.left + (elementWidth / 2) - (tooltipWidth / 2);
        
        // Adjust for viewport edges
        const viewportWidth = $(window).width();
        const viewportHeight = $(window).height();
        const scrollTop = $(window).scrollTop();
        
        if (left < 8) left = 8;
        if (left + tooltipWidth > viewportWidth - 8) left = viewportWidth - tooltipWidth - 8;
        
        // If tooltip would go below viewport, show above instead
        if (top + tooltipHeight > scrollTop + viewportHeight - 8) {
            top = elementOffset.top - tooltipHeight - 8;
        }
        
        $tooltip.css({
            top: top + 'px',
            left: left + 'px'
        }).addClass('amadex-aircraft-tooltip-visible');
    }
    
    /**
     * Hide aircraft tooltip
     */
    function hideAircraftTooltip() {
        if (currentAircraftTooltip) {
            currentAircraftTooltip.removeClass('amadex-aircraft-tooltip-visible');
            setTimeout(function() {
                if (currentAircraftTooltip) {
                    currentAircraftTooltip.remove();
                    currentAircraftTooltip = null;
                }
            }, 200);
        }
    }

    // ========================================
    // Promotional Containers System
    // ========================================
    
    // Cache for promotional containers
    let promotionalContainersCache = null;
    let promotionalContainersCacheTime = null;
    const PROMOTIONAL_CONTAINERS_CACHE_TTL = 5 * 60 * 1000; // 5 minutes
    
    /**
     * Get promotional containers from server (with caching)
     */
    function getPromotionalContainers() {
        // Return cached containers if available and fresh
        const now = Date.now();
        if (promotionalContainersCache && promotionalContainersCacheTime && 
            (now - promotionalContainersCacheTime) < PROMOTIONAL_CONTAINERS_CACHE_TTL) {
            console.log('Amadex Promo: Using cached containers', {
                count: Object.keys(promotionalContainersCache).length,
                cacheAge: Math.round((now - promotionalContainersCacheTime) / 1000) + 's'
            });
            return Promise.resolve(promotionalContainersCache);
        }
        
        // Fetch from server
        const ajaxUrl = typeof AmadexConfig !== 'undefined' ? AmadexConfig.ajaxUrl : '/wp-admin/admin-ajax.php';
        const nonce = typeof AmadexConfig !== 'undefined' ? AmadexConfig.nonce : '';
        

        
        return new Promise(function(resolve, reject) {
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'amadex_get_promotional_containers',
                    nonce: nonce
                },
                success: function(response) {

                    
                    if (response.success && response.data && response.data.containers) {
                        promotionalContainersCache = response.data.containers;
                        promotionalContainersCacheTime = Date.now();

                        resolve(response.data.containers);
                    } else {
                        console.warn('Amadex Promo: Response missing containers data', response);
                        promotionalContainersCache = {};
                        promotionalContainersCacheTime = Date.now();
                        resolve({});
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Amadex Promo: AJAX error', {
                        status: status,
                        error: error,
                        statusCode: xhr.status,
                        responseText: xhr.responseText ? xhr.responseText.substring(0, 200) : 'No response'
                    });
                    // On error, return empty object but don't cache it
                    resolve({});
                }
            });
        });
    }
    
    /**
     * Track how many flights user has viewed
     */
    let flightsViewedCount = 0;
    
    /**
     * Track how many times each promotional container has appeared in current results
     * This is reset when new results are displayed
     * Key: containerId, Value: appearance count
     */
    let containerAppearanceCount = {};
    
    /**
     * Insert promotional container after a specific flight element (SYNCHRONOUS version)
     * Advanced technique: Uses pre-fetched containers to avoid async delays
     * @param {jQuery} $flightElement - jQuery object of the flight card to insert after
     * @param {number} flightIndex - 1-based index of the flight (first flight = 1)
     * @param {Object} containers - Pre-fetched containers object (optional, will use cache if not provided)
     */
    function insertPromotionalContainerAfter($flightElement, flightIndex, containers) {
        // Validate inputs - Advanced defensive coding: check jQuery object validity
        if (!$flightElement || !$flightElement.length || !$flightElement.jquery) {
            console.warn('Amadex Promo: Invalid flight element provided', {
                hasElement: !!$flightElement,
                hasLength: $flightElement ? $flightElement.length : 0,
                isJQuery: $flightElement ? !!$flightElement.jquery : false
            });
            return;
        }
        
        // Only insert if we've viewed minimum flights
        flightsViewedCount = flightIndex;
        
        // Use provided containers or fallback to cache
        const containersToCheck = containers || promotionalContainersCache || {};
        
        // Early exit if no containers
        if (!containersToCheck || Object.keys(containersToCheck).length === 0) {
            if (flightIndex === 1) {
                console.log('Amadex Promo: No containers available', {
                    providedContainers: !!containers,
                    cachedContainers: !!promotionalContainersCache,
                    containerKeys: containers ? Object.keys(containers).length : 0,
                    cacheKeys: promotionalContainersCache ? Object.keys(promotionalContainersCache).length : 0
                });
            }
            return; // No containers configured
        }
        
        // Process containers in order and check which ones should be inserted
        const containersToInsert = [];
        const containerIds = Object.keys(containersToCheck);
        
        for (let i = 0; i < containerIds.length; i++) {
            const containerId = containerIds[i];
            const container = containersToCheck[containerId];
            
            // Defensive check: skip if container is invalid
            if (!container || typeof container !== 'object') {
                continue;
            }
            
            // Skip if disabled
            if (!container.enabled) {
                continue;
            }
            
            // Check minimum flights viewed (default: 2)
            const minFlights = parseInt(container.min_flights_viewed, 10) || 2;
            if (flightIndex < minFlights) {
                continue;
            }
            
            // Check insertion interval (after every N flights, default: 4)
            // Interval of 1 = after every flight, 2 = after every 2nd flight, etc.
            const interval = parseInt(container.insertion_interval, 10) || 4;
            // Calculate adjusted index: (flightIndex - 1) because we want first insertion after minFlights
            // Example: minFlights=2, interval=4 -> insert at flights 4, 8, 12... (not 2, 6, 10)
            // But if minFlights=2, we might want: 2, 6, 10... which would be: (index-2) % interval === 0 when index >= minFlights
            // Current logic: interval=4 means flights 4, 8, 12... (flightIndex % interval === 0)
            // This is correct if we want containers at exact multiples of interval
            if (interval > 0 && flightIndex % interval !== 0) {
                continue;
            }
            
            // Check insertion frequency (probability 0.0 to 1.0, default: 0.25)
            const frequency = parseFloat(container.insertion_frequency);
            if (isNaN(frequency) || frequency < 0 || frequency > 1) {
                // Invalid frequency, use default
                const defaultFreq = 0.25;
                if (Math.random() > defaultFreq) {
                    continue;
                }
            } else {
                // Valid frequency - check probability
                if (frequency < 1.0 && Math.random() > frequency) {
                    continue;
                }
            }
            
            // CRITICAL NEW CHECK: Check maximum appearances limit (0 = unlimited)
            const maxAppearances = parseInt(container.max_appearances, 10) || 0;
            if (maxAppearances > 0) {
                const currentCount = containerAppearanceCount[containerId] || 0;
                if (currentCount >= maxAppearances) {
                    // Container has reached its maximum appearances, skip it
                    continue;
                }
            }
            
            // All checks passed - add to insertion list
            containersToInsert.push({
                id: containerId,
                container: container,
                priority: parseInt(container.display_order, 10) || 0
            });
        }
        
        // If multiple containers compete for same position, pick highest priority (lowest display_order)
        if (containersToInsert.length > 0) {
            // Sort by priority (lower number = higher priority)
            containersToInsert.sort(function(a, b) {
                return a.priority - b.priority;
            });
            
            // Insert only the highest priority container
            const selectedContainer = containersToInsert[0];
            
            try {
                
                const containerHtml = renderPromotionalContainer(selectedContainer.container, selectedContainer.id);
                const $container = $(containerHtml);
                
                // Debug: Verify animation classes in rendered HTML
                const containerClasses = $container.attr('class') || '';
                const hasAnimationClass = containerClasses.indexOf('amadex-animation-') !== -1;
                
                if (selectedContainer.container.animations && selectedContainer.container.animations.length > 0) {

                    
                    if (!hasAnimationClass) {
                        console.error('Amadex Promo: WARNING - Animation classes missing from rendered HTML!', {
                            containerId: selectedContainer.id,
                            expectedAnimations: selectedContainer.container.animations,
                            actualClasses: containerClasses
                        });
                    } else {
                        // Verify CSS is loaded by checking computed styles
                        setTimeout(function() {
                            const computedStyle = window.getComputedStyle($container[0]);
                            const hasPositionRelative = computedStyle.position === 'relative';
                            const hasOverflowHidden = computedStyle.overflow === 'hidden' || computedStyle.overflowX === 'hidden';
                            
                            // Check if ::before pseudo-element exists
                            const beforeStyle = window.getComputedStyle($container[0], '::before');
                            const hasBeforeContent = beforeStyle.content !== 'none' && beforeStyle.content !== '';
                            

                            
                            if (!hasPositionRelative || !hasOverflowHidden) {
                                console.warn('Amadex Promo: Container missing required CSS properties for animations!');
                            }
                            if (!hasBeforeContent && containerClasses.indexOf('amadex-animation-glazing') !== -1) {
                                console.warn('Amadex Promo: Glazing animation ::before pseudo-element not found!');
                            }
                        }, 100);
                    }
                }
                
                // CRITICAL FIX: Use insertAfter() instead of append() to insert RIGHT AFTER the flight element
                // Verify flight element is in DOM before inserting
                if ($flightElement.length === 0 || $flightElement.parent().length === 0) {
                    console.error('Amadex Promo: Flight element not in DOM', {
                        flightIndex: flightIndex,
                        hasElement: $flightElement.length > 0,
                        hasParent: $flightElement.parent().length > 0
                    });
                } else {
                    $container.insertAfter($flightElement);
                    
                    // Verify container was inserted correctly
                    if ($container.parent().length > 0 && $container.prev().is($flightElement)) {

                    } else {
                        console.warn('Amadex Promo: Container insertion may have failed', {
                            containerId: selectedContainer.id,
                            flightIndex: flightIndex,
                            containerInDOM: $container.parent().length > 0,
                            prevIsFlight: $container.prev().hasClass('amadex-flight-card')
                        });
                    }
                    
                    // CRITICAL: Increment appearance count after successful insertion
                    const containerId = selectedContainer.id;
                    containerAppearanceCount[containerId] = (containerAppearanceCount[containerId] || 0) + 1;
                }
                
                // Initialize container interactions
                initPromotionalContainerInteractions($container);
            } catch (error) {
                // Enhanced error logging with container context
                console.error('Amadex Promo: Error during container insertion:', error);
                console.error('Container Context:', {
                    containerId: selectedContainer.id,
                    containerType: selectedContainer.container.type,
                    containerTitle: selectedContainer.container.title || 'N/A',
                    flightIndex: flightIndex,
                    errorName: error.name,
                    errorMessage: error.message,
                    errorStack: error.stack
                });
                
                // Specific error detection for common issues
                if (error.message && error.message.includes('textColorStyle')) {
                    console.error('CRITICAL: textColorStyle error detected. This indicates a code issue in renderPromotionalContainer().');
                }
                if (error.message && error.message.includes('is not defined')) {
                    console.error('CRITICAL: Undefined variable error. Check renderPromotionalContainer() for missing variable definitions.');
                }
            }
        } else if (flightIndex <= 5) {
        }
    }
    
    /**
     * CRITICAL FIX #3: Retroactively insert promotional containers into already-rendered flights
     * This is called when containers load AFTER initial render has completed
     * @param {Object} containers - Container data object
     * @param {jQuery} $existingFlights - jQuery collection of existing flight card elements
     */
    function insertPromotionalContainersRetroactively(containers, $existingFlights) {
        if (!$existingFlights || $existingFlights.length === 0) {
            console.warn('Amadex Promo: No existing flights found for retroactive insertion');
            return;
        }
        
        if (!containers || Object.keys(containers).length === 0) {
            console.warn('Amadex Promo: No containers provided for retroactive insertion');
            return;
        }
        
        console.log('Amadex Promo: Starting retroactive insertion', {
            flightsCount: $existingFlights.length,
            containersCount: Object.keys(containers).length
        });
        
        // Iterate through each existing flight and check if it should have a container
        $existingFlights.each(function(flightIndex) {
            const index = flightIndex + 1; // Convert to 1-based index
            const $flightElement = $(this);
            
            // Check if this flight already has a promotional container after it
            const $nextPromo = $flightElement.next('.amadex-promotional-container');
            if ($nextPromo.length > 0) {
                // Already has a container, skip
                return;
            }
            
            // Use the same insertion logic as renderNext()
            insertPromotionalContainerAfter($flightElement, index, containers);
        });
        
        console.log('Amadex Promo: Retroactive insertion completed');
    }
    
    /**
     * Legacy async function - kept for backward compatibility
     * NOTE: This function is deprecated. Use insertPromotionalContainerAfter() for synchronous insertion.
     * @param {number} flightIndex - 1-based index of the flight (first flight = 1)
     * @deprecated Use insertPromotionalContainerAfter() instead
     */
    async function insertPromotionalContainersIfNeeded(flightIndex) {
        console.warn('insertPromotionalContainersIfNeeded() is deprecated. Use insertPromotionalContainerAfter() instead.');
        
        // Only insert if we've viewed minimum flights
        flightsViewedCount = flightIndex;
        
        // Get promotional containers
        const containers = await getPromotionalContainers();
        
        // Get the flight element by index (1-based to 0-based conversion)
        const $flightCards = $('#amadex-flight-cards-container .amadex-flight-card');
        const flightCardIndex = flightIndex - 1; // Convert to 0-based
        
        if ($flightCards.length > flightCardIndex) {
            const $flightElement = $flightCards.eq(flightCardIndex);
            insertPromotionalContainerAfter($flightElement, flightIndex, containers);
        }
    }
    
    /**
     * Render promotional container HTML based on type
     * 
     * NOW USES SHARED RENDERER for 1:1 preview parity
     * Falls back to local implementation if shared renderer not available
     * 
     * @param {Object} container - Container configuration object
     * @param {string} containerId - Unique container identifier
     * @returns {string} HTML string for the container
     */
    function renderPromotionalContainer(container, containerId) {
        // Use shared renderer if available (for 1:1 preview parity)
        if (typeof AmadexPromoRenderer !== 'undefined' && typeof AmadexPromoRenderer.renderPromotionalContainer === 'function') {
            try {
                return AmadexPromoRenderer.renderPromotionalContainer(container, containerId);
            } catch (error) {
                console.error('Amadex Promo: Error using shared renderer, falling back to local:', error);
                // Fall through to local implementation
            }
        }
        
        // Fallback: Local implementation (for backward compatibility)
        // This should rarely be used if shared renderer is properly loaded
        try {
            // Validate inputs
            if (!container || typeof container !== 'object') {
                throw new Error('Invalid container object provided');
            }
            
            const type = container.type || 'price_alert';
            const title = container.title || '';
            const description = container.description || '';
            const buttonText = container.button_text || 'Track prices';
            const imageUrl = container.image_url || '';
            const linkUrl = container.link_url || '';
            const additionalData = container.additional_data || {};
        
        // NEW: Custom width/height with units
        let containerWidth = '100%';
        let containerHeight = 'auto';
        
        if (container.container_width_value !== undefined && container.container_width_unit) {
            containerWidth = container.container_width_value + container.container_width_unit;
        } else if (container.container_width) {
            // Legacy support: convert old presets
            if (container.container_width === 'full') containerWidth = '100%';
            else if (container.container_width === 'compact') containerWidth = '65%';
            else if (container.container_width === 'mini') containerWidth = '45%';
        }
        
        if (container.container_height_value !== undefined && container.container_height_unit && container.container_height_unit !== 'auto') {
            containerHeight = container.container_height_value + container.container_height_unit;
        }
        
        // NEW: Animations
        const animations = (container.animations && Array.isArray(container.animations)) ? container.animations : [];
        const animationDuration = container.animation_duration || '2s';
        const animationDelay = container.animation_delay || '0s';
        const animationMobileDisabled = container.animation_mobile_disabled || false;
        const animationIntensity = container.animation_intensity !== undefined ? parseFloat(container.animation_intensity) : 50; // 0-100
        const intensityRatio = animationIntensity / 100; // Convert to 0-1 for CSS variable
        
        // Build animation classes
        let animationClasses = '';
        if (animations.length > 0) {
            animations.forEach(function(anim) {
                animationClasses += ' amadex-animation-' + anim;
            });
            if (animationMobileDisabled) {
                animationClasses += ' amadex-animation-mobile-disabled';
            }
        }
        
        // NEW: Build background color style
        let backgroundColorStyle = '';
        let headingColorStyle = '';
        let bodyColorStyle = '';
        const colorType = container.container_color_type || 'default';
        let primaryColor = '#0e7d3f'; // Default for contrast calculation
        
        // Helper function to convert hex to rgba
        function hexToRgba(hex, alpha) {
            if (!hex || hex.length < 7) return 'rgba(0, 0, 0, ' + alpha + ')';
            const r = parseInt(hex.slice(1, 3), 16);
            const g = parseInt(hex.slice(3, 5), 16);
            const b = parseInt(hex.slice(5, 7), 16);
            return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + alpha + ')';
        }
        
        // Helper function for contrast calculation
        function getContrastColor(hex) {
            if (!hex || hex.length < 7) return '#111827';
            const r = parseInt(hex.slice(1, 3), 16);
            const g = parseInt(hex.slice(3, 5), 16);
            const b = parseInt(hex.slice(5, 7), 16);
            const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
            return luminance > 0.5 ? '#111827' : '#ffffff';
        }
        
        if (colorType !== 'default') {
            primaryColor = container.container_color_primary || '#0e7d3f';
            const opacity = container.container_color_opacity !== undefined ? parseFloat(container.container_color_opacity) : 100;
            const opacityDecimal = opacity / 100;
            
            if (colorType === 'solid') {
                backgroundColorStyle = 'background: ' + hexToRgba(primaryColor, opacityDecimal) + ';';
            } else if (colorType === 'gradient_2' || colorType === 'gradient_3') {
                const secondaryColor = container.container_color_secondary || '#22af5c';
                let gradientDirection = container.container_gradient_direction || 'to right';
                const gradientAngle = container.container_gradient_angle !== undefined ? parseInt(container.container_gradient_angle) : 135;
                
                if (gradientDirection === 'custom') {
                    gradientDirection = gradientAngle + 'deg';
                }
                
                if (colorType === 'gradient_2') {
                    backgroundColorStyle = 'background: linear-gradient(' + gradientDirection + ', ' + hexToRgba(primaryColor, opacityDecimal) + ', ' + hexToRgba(secondaryColor, opacityDecimal) + ');';
                } else {
                    const tertiaryColor = container.container_color_tertiary || '#f97316';
                    const stops = container.gradient_stops || [0, 50, 100];
                    backgroundColorStyle = 'background: linear-gradient(' + gradientDirection + ', ' + hexToRgba(primaryColor, opacityDecimal) + ' ' + stops[0] + '%, ' + hexToRgba(secondaryColor, opacityDecimal) + ' ' + stops[1] + '%, ' + hexToRgba(tertiaryColor, opacityDecimal) + ' ' + stops[2] + '%);';
                }
            }
        }
        
        // NEW: Text color handling (separate heading and body)
        const textColorAuto = container.text_color_auto !== undefined ? container.text_color_auto : true;
        
        if (!textColorAuto) {
            // Manual mode: get heading and body colors separately
            let headingColor = container.container_heading_color || '';
            let bodyColor = container.container_body_color || '';
            
            // Legacy support: if old container_text_color exists, use it for both
            if (!headingColor && !bodyColor && container.container_text_color) {
                headingColor = container.container_text_color;
                bodyColor = container.container_text_color;
            }
            
            // If only one is set, auto-calculate the other from background
            if (headingColor && !bodyColor && colorType !== 'default') {
                bodyColor = getContrastColor(primaryColor);
            } else if (!headingColor && bodyColor && colorType !== 'default') {
                headingColor = getContrastColor(primaryColor);
            } else if (!headingColor && !bodyColor && colorType !== 'default') {
                // Both empty, use auto-calculated
                headingColor = getContrastColor(primaryColor);
                bodyColor = getContrastColor(primaryColor);
            } else if (!headingColor && !bodyColor) {
                // Default colors when no background customization
                headingColor = '#111827';
                bodyColor = '#6b7280';
            }
            
            headingColorStyle = 'color: ' + (headingColor || '#111827') + ';';
            bodyColorStyle = 'color: ' + (bodyColor || '#6b7280') + ';';
        } else if (colorType !== 'default' && backgroundColorStyle) {
            // Auto-calculate both from background
            const autoTextColor = getContrastColor(primaryColor);
            headingColorStyle = 'color: ' + autoTextColor + ';';
            bodyColorStyle = 'color: ' + autoTextColor + ';';
        }
        
        // Build inline styles for width/height, intensity, and colors
        let inlineStyles = 'width: ' + containerWidth + ';';
        if (containerHeight !== 'auto') {
            inlineStyles += ' height: ' + containerHeight + ';';
        }
        // Add intensity CSS variable
        if (animations.length > 0) {
            inlineStyles += ' --amadex-intensity: ' + intensityRatio + ';';
        }
        
        // Base CSS classes
        let cssClasses = 'amadex-promotional-container';
        cssClasses += ' amadex-promo-type-' + type;
        cssClasses += animationClasses;
        
        let html = '<div class="' + cssClasses + '" style="' + inlineStyles + '" data-container-id="' + containerId + '" data-container-type="' + type + '">';
        
        // Render based on type
        if (type === 'price_alert') {
            html += '<div class="amadex-promo-content" style="' + backgroundColorStyle + '">';
            if (imageUrl) {
                html += '<div class="amadex-promo-image"><img src="' + escapeHtml(imageUrl) + '" alt="' + escapeHtml(title) + '"></div>';
            }
            html += '<div class="amadex-promo-text">';
            html += '<h3 class="amadex-promo-title" style="' + headingColorStyle + '">' + escapeHtml(title) + '</h3>';
            if (description) {
                html += '<p class="amadex-promo-description" style="' + bodyColorStyle + '">' + escapeHtml(description) + '</p>';
            }
            html += '</div>';
            html += '<form class="amadex-promo-form amadex-price-alert-form">';
            html += '<input type="email" class="amadex-promo-email-input" placeholder="' + escapeHtml(additionalData.email_placeholder || 'Enter your email') + '" required>';
            html += '<button type="submit" class="amadex-promo-button">' + escapeHtml(buttonText) + '</button>';
            html += '</form>';
            html += '</div>';
        } else if (type === 'airline_ad') {
            html += '<div class="amadex-promo-content" style="' + backgroundColorStyle + '">';
            if (additionalData.airline_logo_url) {
                html += '<div class="amadex-promo-airline-logo"><img src="' + escapeHtml(additionalData.airline_logo_url) + '" alt="' + escapeHtml(title) + '"></div>';
            }
            html += '<div class="amadex-promo-text">';
            html += '<h3 class="amadex-promo-title" style="' + headingColorStyle + '">' + escapeHtml(title) + '</h3>';
            if (description) {
                html += '<p class="amadex-promo-description" style="' + bodyColorStyle + '">' + escapeHtml(description) + '</p>';
            }
            if (additionalData.offer_text) {
                html += '<p class="amadex-promo-offer" style="' + bodyColorStyle + '">' + escapeHtml(additionalData.offer_text) + '</p>';
            }
            html += '</div>';
            if (linkUrl) {
                html += '<a href="' + escapeHtml(linkUrl) + '" class="amadex-promo-button amadex-promo-link" target="_blank">' + escapeHtml(buttonText) + '</a>';
            }
            html += '</div>';
        } else if (type === 'product_cross_sell') {
            html += '<div class="amadex-promo-content" style="' + backgroundColorStyle + '">';
            if (imageUrl) {
                html += '<div class="amadex-promo-image"><img src="' + escapeHtml(imageUrl) + '" alt="' + escapeHtml(title) + '"></div>';
            }
            html += '<div class="amadex-promo-text">';
            html += '<h3 class="amadex-promo-title" style="' + headingColorStyle + '">' + escapeHtml(title) + '</h3>';
            if (description) {
                html += '<p class="amadex-promo-description" style="' + bodyColorStyle + '">' + escapeHtml(description) + '</p>';
            }
            html += '</div>';
            if (linkUrl) {
                html += '<a href="' + escapeHtml(linkUrl) + '" class="amadex-promo-button amadex-promo-link" target="_blank">' + escapeHtml(buttonText) + '</a>';
            } else {
                html += '<button type="button" class="amadex-promo-button">' + escapeHtml(buttonText) + '</button>';
            }
            html += '</div>';
        } else if (type === 'callback') {
            html += '<div class="amadex-promo-content" style="' + backgroundColorStyle + '">';
            html += '<div class="amadex-promo-text">';
            html += '<h3 class="amadex-promo-title" style="' + headingColorStyle + '">' + escapeHtml(title) + '</h3>';
            if (description) {
                html += '<p class="amadex-promo-description" style="' + bodyColorStyle + '">' + escapeHtml(description) + '</p>';
            }
            html += '</div>';
            html += '<form class="amadex-promo-form amadex-callback-form">';
            html += '<input type="tel" class="amadex-promo-phone-input" placeholder="' + escapeHtml(additionalData.phone_placeholder || 'Enter your phone number') + '" required>';
            html += '<button type="submit" class="amadex-promo-button">' + escapeHtml(buttonText) + '</button>';
            html += '</form>';
            html += '<div class="amadex-promo-message" style="display:none;"></div>';
            html += '</div>';
        } else if (type === 'ad') {
            html += '<div class="amadex-promo-content" style="' + backgroundColorStyle + '">';
            
            // Image section (if provided)
            if (imageUrl) {
                if (linkUrl) {
                    html += '<a href="' + escapeHtml(linkUrl) + '" target="_blank" class="amadex-promo-ad-link">';
                    html += '<img src="' + escapeHtml(imageUrl) + '" alt="' + escapeHtml(title) + '" class="amadex-promo-ad-image">';
                    html += '</a>';
                } else {
                    html += '<img src="' + escapeHtml(imageUrl) + '" alt="' + escapeHtml(title) + '" class="amadex-promo-ad-image">';
                }
            }
            
            // Text content section (title and/or description)
            if (title || description) {
                html += '<div class="amadex-promo-text">';
                if (title) {
                    html += '<h3 class="amadex-promo-title" style="' + headingColorStyle + '">' + escapeHtml(title) + '</h3>';
                }
                if (description) {
                    html += '<p class="amadex-promo-description" style="' + bodyColorStyle + '">' + escapeHtml(description) + '</p>';
                }
                html += '</div>';
            }
            
            html += '</div>';
        }
        
        html += '</div>';
        return html;
        } catch (error) {
            // Enhanced error logging with full context
            console.error('Amadex Promo: Critical error in renderPromotionalContainer (fallback):', error);
            console.error('Error Details:', {
                containerId: containerId || 'unknown',
                containerType: container ? (container.type || 'unknown') : 'null',
                containerTitle: container ? (container.title || 'N/A') : 'N/A',
                errorName: error.name,
                errorMessage: error.message,
                errorStack: error.stack
            });
            
            // Return a user-friendly fallback container (for production)
            const fallbackTitle = container && container.title ? container.title : 'Promotional Content';
            return '<div class="amadex-promotional-container amadex-promo-error" style="padding: 20px; border: 2px solid #ff6b6b; background: #fff5f5; border-radius: 8px; margin: 10px 0;">' +
                   '<div class="amadex-promo-content">' +
                   '<h3 class="amadex-promo-title" style="color: #d63031; margin: 0 0 10px 0;">' + escapeHtml(fallbackTitle) + '</h3>' +
                   '<p style="color: #666; margin: 0; font-size: 14px;">Unable to load promotional content. Please refresh the page.</p>' +
                   '</div>' +
                   '</div>';
        }
    }
    
    /**
     * Initialize promotional container interactions (forms, buttons, etc.)
     */
    function initPromotionalContainerInteractions($container) {
        // Price alert form submission
        $container.find('.amadex-price-alert-form').on('submit', function(e) {
            e.preventDefault();
            const $form = $(this);
            const $input = $form.find('.amadex-promo-email-input');
            const email = $input.val();
            
            if (!email || !isValidEmail(email)) {
                alert('Please enter a valid email address.');
                return;
            }
            
            // TODO: Send to backend via AJAX
            console.log('Price alert signup:', email);
            alert('Thank you! We\'ll notify you when prices change.');
            $input.val('');
        });
        
        // Callback form submission
        $container.find('.amadex-callback-form').on('submit', function(e) {
            e.preventDefault();
            const $form = $(this);
            const $input = $form.find('.amadex-promo-phone-input');
            const phone = $input.val();
            const $message = $container.find('.amadex-promo-message');
            
            if (!phone || phone.length < 10) {
                alert('Please enter a valid phone number.');
                return;
            }
            
            // TODO: Send to backend via AJAX
            console.log('Callback request:', phone);
            $message.text('Thank you! We\'ll call you shortly.').fadeIn();
            $input.val('');
            setTimeout(function() {
                $message.fadeOut();
            }, 3000);
        });
        
        // Initialize number counter animations
        $container.find('.amadex-animation-number_counter').each(function() {
            const $counterElement = $(this);
            
            // Check if already initialized
            if ($counterElement.data('counter-initialized')) {
                return;
            }
            
            // Get counter data attributes
            const startValue = parseFloat($counterElement.data('counter-start')) || 0;
            const endValue = parseFloat($counterElement.data('counter-end')) || 0;
            const duration = parseFloat($counterElement.data('counter-duration')) || 2000; // Default 2 seconds
            const decimals = parseInt($counterElement.data('counter-decimals')) || 0;
            const prefix = $counterElement.data('counter-prefix') || '';
            const suffix = $counterElement.data('counter-suffix') || '';
            
            // Check if PureCounter library is available
            if (typeof PureCounter !== 'undefined') {
                try {
                    new PureCounter({
                        selector: $counterElement[0], // Pass the native DOM element
                        start: startValue,
                        end: endValue,
                        duration: duration,
                        decimals: decimals,
                        prefix: prefix,
                        suffix: suffix
                    });
                    $counterElement.data('counter-initialized', true);
                } catch (error) {
                    console.warn('Amadex Promo: Error initializing PureCounter:', error);
                    // Fallback: just show the end value
                    $counterElement.text(prefix + endValue.toFixed(decimals) + suffix);
                }
            } else {
                // Fallback: Simple counter animation using jQuery
                console.warn('Amadex Promo: PureCounter not found. Using fallback counter animation.');
                const startTime = Date.now();
                const valueRange = endValue - startValue;
                
                function updateCounter() {
                    const elapsed = Date.now() - startTime;
                    const progress = Math.min(elapsed / duration, 1);
                    
                    // Easing function (ease-out)
                    const eased = 1 - Math.pow(1 - progress, 3);
                    const currentValue = startValue + (valueRange * eased);
                    
                    $counterElement.text(prefix + currentValue.toFixed(decimals) + suffix);
                    
                    if (progress < 1) {
                        requestAnimationFrame(updateCounter);
                    } else {
                        $counterElement.text(prefix + endValue.toFixed(decimals) + suffix);
                    }
                }
                
                updateCounter();
                $counterElement.data('counter-initialized', true);
            }
        });
    }
    
    /**
     * Helper: Escape HTML
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    /**
     * Helper: Validate email
     */
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    // Initialize promotional container interactions on document ready (for containers already on page)
    $(document).ready(function() {
        initPromotionalContainerInteractions($('.amadex-promotional-container'));
    });

    // Expose functions globally for modern search form
    if (typeof window !== 'undefined') {
        window.displayFlightResults = displayFlightResults;
        window.updateSearchInfo = updateSearchInfo;
        // Expose sortFlights function for mobile filters
        window.amadexSortFlights = function(sortValue) {
            // Update sort select value
            const $sortSelect = $('#amadex-sort-by');
            if ($sortSelect.length && sortValue) {
                $sortSelect.val(sortValue);
            }
            // Call sort function
            sortFlights();
        };
        // Also expose direct sortFlights for backward compatibility
        window.sortFlights = sortFlights;
    }

})(jQuery);