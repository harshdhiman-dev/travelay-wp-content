/**
 * Amadex Flight Filters
 */

(function($) {
    'use strict';

    const PRICE_MIN_BOUND = 130;
    const PRICE_MAX_BOUND = 2891;
    const DURATION_MIN_BOUND = 1;
    const DURATION_MAX_BOUND = 22;

    let allFlights = [];
    let filteredFlights = [];
    let currentFilters = {
        stops: [],
        airlines: [],
        priceMin: 0,
        priceMax: 10000,
        departureTime: [],
        returnTime: [],
        meal: false
    };
    let basePriceMin = PRICE_MIN_BOUND;
    let basePriceMax = PRICE_MAX_BOUND;
    let baseDurationMin = DURATION_MIN_BOUND;
    let baseDurationMax = DURATION_MAX_BOUND;

    /**
     * Initialize filters with flight data
     */
    window.amadexInitFilters = function(flights) {
        allFlights = flights || [];
        filteredFlights = [...allFlights];
        
        // Calculate price range
        // const prices = allFlights.map(f => parseFloat(f.price.total));
        // const minPrice = prices.length > 0 ? Math.max(Math.min(...prices), PRICE_MIN_BOUND) : PRICE_MIN_BOUND;
        // const maxPrice = prices.length > 0 ? Math.min(Math.max(...prices), PRICE_MAX_BOUND) : PRICE_MAX_BOUND;
        
        // currentFilters.priceMin = PRICE_MIN_BOUND;
        // currentFilters.priceMax = PRICE_MAX_BOUND;
        // basePriceMin = PRICE_MIN_BOUND;
        // basePriceMax = PRICE_MAX_BOUND;
        
        // // Update price slider
        // $('#amadex-price-min')
        //     .attr('min', PRICE_MIN_BOUND)
        //     .attr('max', PRICE_MAX_BOUND)
        //     .val(PRICE_MIN_BOUND);
        // $('#amadex-price-max')
        //     .attr('min', PRICE_MIN_BOUND)
        //     .attr('max', PRICE_MAX_BOUND)
        //     .val(PRICE_MAX_BOUND);
        // $('#amadex-price-min-display').text('$' + Math.round(PRICE_MIN_BOUND));
        // $('#amadex-price-max-display').text('$' + Math.round(PRICE_MAX_BOUND));
        // updatePriceSliderTrack();
        
        // // Setup duration slider
        // $('#amadex-duration-min')
        //     .attr('min', DURATION_MIN_BOUND)
        //     .attr('max', DURATION_MAX_BOUND)
        //     .val(DURATION_MIN_BOUND);
        // $('#amadex-duration-max')
        //     .attr('min', DURATION_MIN_BOUND)
        //     .attr('max', DURATION_MAX_BOUND)
        //     .val(DURATION_MAX_BOUND);
        // $('#amadex-duration-min-display').text(DURATION_MIN_BOUND + 'h');
        // $('#amadex-duration-max-display').text(DURATION_MAX_BOUND + 'h');
        // updateDurationSliderTrack();

        // Calculate price range from real data
        const prices = allFlights.map(f => parseFloat(f.price.total)).filter(p => !isNaN(p));
        const realMin = prices.length > 0 ? Math.floor(Math.min(...prices)) : PRICE_MIN_BOUND;
        const realMax = prices.length > 0 ? Math.ceil(Math.max(...prices))  : PRICE_MAX_BOUND;

        basePriceMin = realMin;
        basePriceMax = realMax;
        currentFilters.priceMin = realMin;
        currentFilters.priceMax = realMax;
        
        // Update price slider bounds with real data
        $('#amadex-price-min')
            .attr('min', realMin).attr('max', realMax).val(realMin);
        $('#amadex-price-max')
            .attr('min', realMin).attr('max', realMax).val(realMax);
        $('#amadex-price-min-display').text('$' + realMin);
        $('#amadex-price-max-display').text('$' + realMax);
        updatePriceSliderTrack();

        // Calculate duration range from real data
        const durations = allFlights.map(f => Math.round(getFlightDurationMinutes(f) / 60)).filter(d => d > 0);
        const realDurMin = durations.length > 0 ? Math.min(...durations) : DURATION_MIN_BOUND;
        const realDurMax = durations.length > 0 ? Math.max(...durations) : DURATION_MAX_BOUND;

        baseDurationMin = realDurMin;
        baseDurationMax = realDurMax;

        $('#amadex-duration-min')
            .attr('min', realDurMin).attr('max', realDurMax).val(realDurMin);
        $('#amadex-duration-max')
            .attr('min', realDurMin).attr('max', realDurMax).val(realDurMax);
        // $('#amadex-duration-min-display').text(realDurMin + 'h');
        // $('#amadex-duration-max-display').text(realDurMax + 'h');
        // updateDurationSliderTrack();

        $('#amadex-duration-min-display').text(realDurMin + 'h');
        $('#amadex-duration-max-display').text(realDurMax + 'h');
        updateDurationSliderTrack();

        // Set city names from DOM
        const fromCity = $('#amadex-from-city').text() || 'Origin';
        const toCity   = $('#amadex-to-city').text()   || 'Destination';
        $('#amadex-duration-from-city').text(fromCity);
        $('#amadex-duration-to-city').text(toCity);

        // Show return duration slider only for round trips
        const hasReturn = allFlights.some(f => f.itineraries && f.itineraries[1]);
        if (hasReturn) {
            const returnDurs = allFlights
                .filter(f => f.itineraries && f.itineraries[1])
                .map(f => Math.round(getReturnDurationMinutes(f) / 60))
                .filter(d => d > 0);
            const retMin = returnDurs.length > 0 ? Math.min(...returnDurs) : realDurMin;
            const retMax = returnDurs.length > 0 ? Math.max(...returnDurs) : realDurMax;

            $('#amadex-return-duration-min').attr('min', retMin).attr('max', retMax).val(retMin);
            $('#amadex-return-duration-max').attr('min', retMin).attr('max', retMax).val(retMax);
            $('#amadex-return-duration-min-display').text(retMin + 'h');
            $('#amadex-return-duration-max-display').text(retMax + 'h');
            updateReturnDurationSliderTrack();
            $('#amadex-return-duration-leg').show();
        } else {
            $('#amadex-return-duration-leg').hide();
        }
        populatePopularFilters();
        // Populate stops filter
        populateStopsFilter();
        
        // Populate airlines filter
        populateAirlinesFilter();
        
        // Bind events
        bindFilterEvents();
        refreshFilterOptionStates();
    };

    /**
     * Populate stops filter
     */
    // function populateStopsFilter() {
    //     const container = $('#amadex-stops-filter');
    //     container.empty();
        
    //     // Count flights by stops
    //     const stopCounts = {};
    //     allFlights.forEach(flight => {
    //         const stops = getStops(flight);
    //         stopCounts[stops] = (stopCounts[stops] || 0) + 1;
    //     });
        
    //     // Add Non Stop
    //     if (stopCounts[0]) {
    //         const price = getMinPriceForStops(0);
    //         container.append(`
    //             <label class="amadex-filter-option">
    //                 <input type="checkbox" name="stops" value="0">
    //                 <span class="amadex-filter-label">Non Stop</span>
    //                 <span class="amadex-filter-price">$${price}</span>
    //             </label>
    //         `);
    //     }
        
    //     // Add One Stop
    //     if (stopCounts[1]) {
    //         const price = getMinPriceForStops(1);
    //         container.append(`
    //             <label class="amadex-filter-option">
    //                 <input type="checkbox" name="stops" value="1">
    //                 <span class="amadex-filter-label">One Stop</span>
    //                 <span class="amadex-filter-price">$${price}</span>
    //             </label>
    //         `);
    //     }
    // }

    /**
     * Populate popular filters dynamically from actual flight data
     */
    function populatePopularFilters() {
        const container = $('#amadex-popular-filter');
        container.empty();

        const SHOW_INITIAL = 3;

        const allOptions = [
            {
                id: 'pop_nonstop',
                label: 'Non Stop',
                matcher: f => getStops(f) === 0
            },
            {
                id: 'pop_morning',
                label: 'Morning Departures',
                matcher: f => {
                    if (!f.itineraries || !f.itineraries[0] || !f.itineraries[0].segments) return false;
                    const hour = new Date(f.itineraries[0].segments[0].departure.at).getHours();
                    return hour >= 5 && hour < 12;
                }
            },
            {
                id: 'pop_baggage',
                label: 'Checked Baggage Included',
                matcher: f => f.has_baggage === true || f.has_baggage === 1
            },
            {
                id: 'pop_meal',
                label: 'Free Meal Included',
                matcher: f => f.has_meal === true || f.has_meal === 1
            },
            {
                id: 'pop_afternoon',
                label: 'Afternoon Departure',
                matcher: f => {
                    if (!f.itineraries || !f.itineraries[0] || !f.itineraries[0].segments) return false;
                    const hour = new Date(f.itineraries[0].segments[0].departure.at).getHours();
                    return hour >= 12 && hour < 18;
                }
            },
            {
                id: 'pop_evening',
                label: 'Evening Departure',
                matcher: f => {
                    if (!f.itineraries || !f.itineraries[0] || !f.itineraries[0].segments) return false;
                    const hour = new Date(f.itineraries[0].segments[0].departure.at).getHours();
                    return hour >= 18;
                }
            },
            {
                id: 'pop_short',
                label: 'Short Duration (< 6h)',
                matcher: f => getFlightDurationMinutes(f) > 0 && getFlightDurationMinutes(f) < 360
            }
        ];

        const available = allOptions.filter(opt => allFlights.some(opt.matcher));
        if (available.length === 0) return;

        available.forEach((opt, idx) => {
            const count = allFlights.filter(opt.matcher).length;
            const hidden = idx >= SHOW_INITIAL;
            container.append(`
                <label class="amadex-filter-option amadex-popular-option${hidden ? ' amadex-popular-hidden' : ''}"
                       data-popular-id="${opt.id}">
                    <input type="checkbox" name="popular_filter" value="${opt.id}">
                    <span class="amadex-filter-label">${opt.label}</span>
                    <span class="amadex-filter-count">(${count})</span>
                </label>
            `);
        });

        const $showAllBtn = $('#amadex-popular-show-all');
        const hiddenCount = available.length - SHOW_INITIAL;
        if (hiddenCount > 0) {
            $showAllBtn.show().find('.amadex-popular-show-all-label').text(`+${hiddenCount} Show all`);
        } else {
            $showAllBtn.hide();
        }

        // let expanded = false;
        // $showAllBtn.off('click').on('click', function() {
        //     expanded = !expanded;
        //     container.find('.amadex-popular-hidden').toggleClass('amadex-popular-visible', expanded);
        //     $(this).find('.amadex-popular-show-all-label').text(
        //         expanded ? 'Show less' : `+${hiddenCount} Show all`
        //     );
        // });

        let expanded = false;
        $showAllBtn.off('click').on('click', function() {
            expanded = !expanded;
            if (expanded) {
                container.find('.amadex-popular-hidden').addClass('amadex-popular-visible');
            } else {
                container.find('.amadex-popular-hidden').removeClass('amadex-popular-visible');
            }
            $(this).find('.amadex-popular-show-all-label').text(
                expanded ? 'Show less' : `+${hiddenCount} Show all`
            );
        });

        container.find('input[name="popular_filter"]').off('change').on('change', function() {
            const $all = container.find('input[name="popular_filter"]');
            const allChecked = $all.length > 0 && $all.length === $all.filter(':checked').length;
            $('#amadex-popular-toggle').prop('checked', allChecked);
            refreshFilterOptionStates();
            applyFilters();
        });

        $('#amadex-popular-toggle').off('change').on('change', function() {
            const isOn = $(this).is(':checked');
            container.find('input[name="popular_filter"]').prop('checked', isOn);
            refreshFilterOptionStates();
            applyFilters();
        });

        window._amadexPopularMatchers = {};
        available.forEach(opt => {
            window._amadexPopularMatchers[opt.id] = opt.matcher;
        });
    }

    /**
     * Populate stops filter — handles ALL stop counts dynamically
     */
    function populateStopsFilter() {
        const container = $('#amadex-stops-filter');
        container.empty();

        // Count flights by stop value
        const stopCounts = {};
        allFlights.forEach(flight => {
            const s = getStops(flight);
            stopCounts[s] = (stopCounts[s] || 0) + 1;
        });

        // Sort ascending: 0 → 1 → 2 → ...
        const sortedValues = Object.keys(stopCounts).map(Number).sort((a, b) => a - b);

        const stopLabels = { 0: 'Non Stop', 1: '1-Stop', 2: '2-Stop' };

        sortedValues.forEach(stopCount => {
            const count = stopCounts[stopCount];
            const label = stopLabels[stopCount] !== undefined
                ? stopLabels[stopCount]
                : stopCount + '-Stop';

            container.append(`
                <label class="amadex-filter-option">
                    <input type="checkbox" name="stops" value="${stopCount}">
                    <span class="amadex-filter-label">${label}</span>
                    <span class="amadex-filter-count">(${count})</span>
                </label>
            `);
        });

        // Bind change events RIGHT AFTER creating the checkboxes
        // container.find('input[name="stops"]').off('change').on('change', function() {
        //     refreshFilterOptionStates();
        //     applyFilters();
        // });

        container.find('input[name="stops"]').off('change').on('change', function() {
            const $all = container.find('input[name="stops"]');
            const allChecked = $all.length > 0 && $all.length === $all.filter(':checked').length;
            $('input.amadex-filter-toggle[data-target="#amadex-stops-filter"]').prop('checked', allChecked);
            refreshFilterOptionStates();
            applyFilters();
        });
    }

    /**
     * Get airline full name
     */
    function getAirlineFullName(airlineCode) {
        const airlineNames = {
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
            'HH': 'Hahn Air',
            '6E': 'IndiGo',
            'SG': 'SpiceJet',
            'UK': 'Vistara',
            'G8': 'Go Air',
            'I5': 'AirAsia India'
        };
        
        return airlineNames[airlineCode] || airlineCode;
    }

    /**
     * Populate airlines filter
     */
    // function populateAirlinesFilter() {
    //     const container = $('#amadex-airlines-filter');
    //     container.empty();
        
    //     // Collect airlines with prices
    //     const airlines = {};
    //     allFlights.forEach(flight => {
    //         const airline = flight.validating_airline_codes ? flight.validating_airline_codes[0] : 'Unknown';
    //         if (!airlines[airline] || flight.price.total < airlines[airline]) {
    //             airlines[airline] = Math.round(flight.price.total);
    //         }
    //     });
        
    //     // Sort by price
    //     const sortedAirlines = Object.keys(airlines).sort((a, b) => airlines[a] - airlines[b]);
        
    //     sortedAirlines.forEach(airline => {
    //         const fullName = getAirlineFullName(airline);
    //         container.append(`
    //             <label class="amadex-filter-option">
    //                 <input type="checkbox" name="airlines" value="${airline}">
    //                 <span class="amadex-filter-label">${fullName}</span>
    //                 <span class="amadex-filter-price">$${airlines[airline]}</span>
    //             </label>
    //         `);
    //     });
    // }

function populateAirlinesFilter() {
        const container = $('#amadex-airlines-filter');
        container.empty();

        // Collect airlines with flight counts
        const airlineCounts = {};
        allFlights.forEach(flight => {
            const airline = flight.validating_airline_codes ? flight.validating_airline_codes[0] : 'Unknown';
            airlineCounts[airline] = (airlineCounts[airline] || 0) + 1;
        });

        // Sort by count descending (most flights first)
        const sortedAirlines = Object.keys(airlineCounts).sort((a, b) => airlineCounts[b] - airlineCounts[a]);

        sortedAirlines.forEach(airline => {
            const fullName = getAirlineFullName(airline);
            const count = airlineCounts[airline];
            container.append(`
                <label class="amadex-filter-option amadex-airline-option">
                    <input type="checkbox" name="airlines" value="${airline}">
                    <span class="amadex-filter-label">${fullName}</span>
                    <span class="amadex-filter-count">(${count})</span>
                </label>
            `);
        });

        // Bind checkbox events — also sync master toggle
        container.find('input[name="airlines"]').off('change').on('change', function() {
            const $all = container.find('input[name="airlines"]');
            const allChecked = $all.length > 0 && $all.length === $all.filter(':checked').length;
            $('#amadex-airlines-toggle').prop('checked', allChecked);
            refreshFilterOptionStates();
            applyFilters();
        });

        // Master toggle — select all / deselect all
        $('#amadex-airlines-toggle').off('change').on('change', function() {
            const isOn = $(this).is(':checked');
            container.find('input[name="airlines"]').prop('checked', isOn);
            refreshFilterOptionStates();
            applyFilters();
        });
    }

    /**
     * Get number of stops
     */
    function getStops(flight) {
        if (!flight.itineraries || !flight.itineraries[0] || !flight.itineraries[0].segments) {
            return 0;
        }
        return flight.itineraries[0].segments.length - 1;
    }

    /**
     * Get minimum price for stops
     */
    function getMinPriceForStops(stops) {
        const prices = allFlights
            .filter(f => getStops(f) === stops)
            .map(f => f.price.total);
        return prices.length > 0 ? Math.round(Math.min(...prices)) : 0;
    }

    /**
     * Bind filter events
     */
    function bindFilterEvents() {
        // Clear filters
        $('#amadex-clear-filters').off('click').on('click', function() {
            clearAllFilters();
        });
        
        // Airlines search
        $('#amadex-airlines-search').off('input').on('input', function() {
            const query = $(this).val().toLowerCase();
            $('#amadex-airlines-filter .amadex-filter-option').each(function() {
                const label = $(this).find('.amadex-filter-label').text().toLowerCase();
                $(this).toggle(label.indexOf(query) !== -1);
            });
        });
        
        // Toggle groups
        // $('.amadex-filter-toggle').off('change').on('change', function() {
        //     const targetSelector = $(this).data('target');
        //     if (!targetSelector) return;
        //     const $target = $(targetSelector);
        //     const isEnabled = $(this).is(':checked');
        //     if ($target.length) {
        //         $target.toggleClass('is-disabled', !isEnabled);
        //         const $inputs = $target.find('input[type="checkbox"]');
        //         $inputs.prop('disabled', !isEnabled);
        //         if (!isEnabled) {
        //             $inputs.prop('checked', false);
        //         }
        //         refreshFilterOptionStates();
        //         applyFilters();
        //     }
        // });
        // Toggle groups — ON = select all options, OFF = deselect all
        $('.amadex-filter-toggle').off('change').on('change', function() {
            const targetSelector = $(this).data('target');
            if (!targetSelector) return;
            const $target = $(targetSelector);
            const isEnabled = $(this).is(':checked');
            if ($target.length) {
                const $inputs = $target.find('input[type="checkbox"]');
                $inputs.prop('checked', isEnabled);
                refreshFilterOptionStates();
                applyFilters();
            }
        });
        // Stop filter
        // $('input[name="stops"]').off('change').on('change', function() {
        //     refreshFilterOptionStates();
        //     applyFilters();
        // });
        
        // // Airlines filter
        // $('input[name="airlines"]').off('change').on('change', function() {

        // Airlines filter (stops events are bound inside populateStopsFilter)
        // $('#amadex-airlines-filter').off('change', 'input[name="airlines"]')
        //     .on('change', 'input[name="airlines"]', function() {
        //     refreshFilterOptionStates();
        //     applyFilters();
        // });

// Airlines filter events bound inside populateAirlinesFilter()

        
        // Meal filter
        $('input[name="meal"]').off('change').on('change', function() {
            refreshFilterOptionStates();
            applyFilters();
        });
        
        // Price range sliders
        $('#amadex-price-min, #amadex-price-max').off('input').on('input', function() {
            const min = parseInt($('#amadex-price-min').val());
            const max = parseInt($('#amadex-price-max').val());
            
            // Ensure min doesn't exceed max
            if (min > max) {
                if ($(this).attr('id') === 'amadex-price-min') {
                    $('#amadex-price-min').val(max);
                } else {
                    $('#amadex-price-max').val(min);
                }
            }
            
            $('#amadex-price-min-display').text('$' + $('#amadex-price-min').val());
            $('#amadex-price-max-display').text('$' + $('#amadex-price-max').val());
            
            updatePriceSliderTrack();
            applyFilters();
        });
        
        // Duration sliders
        // $('#amadex-duration-min, #amadex-duration-max').off('input').on('input', function() {
        //     const min = parseInt($('#amadex-duration-min').val());
        //     const max = parseInt($('#amadex-duration-max').val());
            
        //     if (min > max) {
        //         if ($(this).attr('id') === 'amadex-duration-min') {
        //             $('#amadex-duration-min').val(max);
        //         } else {
        //             $('#amadex-duration-max').val(min);
        //         }
        //     }
            
        //     $('#amadex-duration-min-display').text($('#amadex-duration-min').val() + 'h');
        //     $('#amadex-duration-max-display').text($('#amadex-duration-max').val() + 'h');
            
        //     updateDurationSliderTrack();
        //     applyFilters();
        // });

        // Outbound duration sliders
        $('#amadex-duration-min, #amadex-duration-max').off('input').on('input', function() {
            let min = parseInt($('#amadex-duration-min').val());
            let max = parseInt($('#amadex-duration-max').val());
            if (min > max) {
                if ($(this).attr('id') === 'amadex-duration-min') { min = max; $('#amadex-duration-min').val(max); }
                else { max = min; $('#amadex-duration-max').val(min); }
            }
            $('#amadex-duration-min-display').text(min + 'h');
            $('#amadex-duration-max-display').text(max + 'h');
            updateDurationSliderTrack();
            applyFilters();
        });

        // Return duration sliders
        $('#amadex-return-duration-min, #amadex-return-duration-max').off('input').on('input', function() {
            let min = parseInt($('#amadex-return-duration-min').val());
            let max = parseInt($('#amadex-return-duration-max').val());
            if (min > max) {
                if ($(this).attr('id') === 'amadex-return-duration-min') { min = max; $('#amadex-return-duration-min').val(max); }
                else { max = min; $('#amadex-return-duration-max').val(min); }
            }
            $('#amadex-return-duration-min-display').text(min + 'h');
            $('#amadex-return-duration-max-display').text(max + 'h');
            updateReturnDurationSliderTrack();
            applyFilters();
        });
        
        // Departure time filter
        $('input[name="departure_time"]').off('change').on('change', function() {
            refreshFilterOptionStates();
            applyFilters();
        });
        
        // Return time filter
        $('input[name="return_time"]').off('change').on('change', function() {
            refreshFilterOptionStates();
            applyFilters();
        });
        
        refreshFilterOptionStates();
    }

    /**
     * Apply all filters
     */
    function applyFilters() {
        // Get selected filters
        const selectedStops = $('input[name="stops"]:checked').map(function() {
            return parseInt($(this).val());
        }).get();
        
        const selectedAirlines = $('input[name="airlines"]:checked').map(function() {
            return $(this).val();
        }).get();
        
        const priceMin = parseInt($('#amadex-price-min').val());
        const priceMax = parseInt($('#amadex-price-max').val());
        
        const durationMin = parseInt($('#amadex-duration-min').val());
        const durationMax = parseInt($('#amadex-duration-max').val());
        
        const selectedDeparture = $('input[name="departure_time"]:checked').map(function() {
            return $(this).val();
        }).get();
        
        const selectedReturn = $('input[name="return_time"]:checked').map(function() {
            return $(this).val();
        }).get();
        
        const hasFreeMeal = $('#amadex-filter-meal').is(':checked');
        
        updateActiveFilterChips(selectedStops, selectedAirlines, priceMin, priceMax, durationMin, durationMax, selectedDeparture, selectedReturn, hasFreeMeal);
        
        // Filter flights
        filteredFlights = allFlights.filter(flight => {
            // Stop filter
                        const selectedPopular = $('input[name="popular_filter"]:checked').map(function() {
                return $(this).val();
            }).get();
            if (selectedPopular.length > 0 && window._amadexPopularMatchers) {
                const matchesAny = selectedPopular.some(id => {
                    const matcher = window._amadexPopularMatchers[id];
                    return matcher && matcher(flight);
                });
                if (!matchesAny) return false;
            }

            // Stops filter
            if (selectedStops.length > 0) {
                const stops = getStops(flight);
                if (!selectedStops.includes(stops)) return false;
            }
            
            // Airlines filter
            if (selectedAirlines.length > 0) {
                const airline = flight.validating_airline_codes ? flight.validating_airline_codes[0] : '';
                if (!selectedAirlines.includes(airline)) return false;
            }
            
            // Price filter
            const price = parseFloat(flight.price.total);
            if (price < priceMin || price > priceMax) return false;
            
            // Duration filter
            // const flightDurationHours = getFlightDurationMinutes(flight) / 60;
            // if (flightDurationHours < durationMin || flightDurationHours > durationMax) return false;

            // Outbound duration filter
            const flightDurationHours = getFlightDurationMinutes(flight) / 60;
            if (flightDurationHours < durationMin || flightDurationHours > durationMax) return false;

            // Return duration filter (only for round trips)
            if (flight.itineraries && flight.itineraries[1]) {
                const retDurMin = parseInt($('#amadex-return-duration-min').val()) || 0;
                const retDurMax = parseInt($('#amadex-return-duration-max').val()) || 999;
                const retSliderMin = parseInt($('#amadex-return-duration-min').attr('min')) || 0;
                const retSliderMax = parseInt($('#amadex-return-duration-max').attr('max')) || 999;
                if (retDurMin > retSliderMin || retDurMax < retSliderMax) {
                    const retHours = getReturnDurationMinutes(flight) / 60;
                    if (retHours < retDurMin || retHours > retDurMax) return false;
                }
            }
            
            // Meal filter
            if (hasFreeMeal) {
                const hasMeal = flight.has_meal === true || flight.hasMeal === true || flight.has_meal === 1 || flight.hasMeal === 1;
                if (!hasMeal) return false;
            }
            
            // Departure time filter
            if (selectedDeparture.length > 0) {
                if (!matchesTimeFilter(flight, 'departure', selectedDeparture)) return false;
            }
            
            // Return time filter
            if (selectedReturn.length > 0) {
                if (!matchesTimeFilter(flight, 'return', selectedReturn)) return false;
            }
            
            return true;
        });
        
        // Update results count
    //     $('#amadex-results-count').text(filteredFlights.length);
    //     if (window.amadexRenderFlights) {
    //         window.amadexRenderFlights(filteredFlights);
    //     }
    // }

    $('#amadex-results-count').text(filteredFlights.length);
        
        if (window.amadexRenderFlights) {
            window.amadexRenderFlights(filteredFlights);
        }

        // Sync active filter selections to URL (so unchecking removes from URL too)
        if (typeof window.syncFiltersToURL === 'function') {
            window.syncFiltersToURL();
        }
    }

    /**
     * Match time filter
     */
    function matchesTimeFilter(flight, type, selectedTimes) {
        if (!flight.itineraries) return false;
        
        // For return time filter on one-way flights, skip the filter (return true to include the flight)
        // One-way flights don't have a return itinerary, so they should pass return time filters
        if (type === 'return') {
            // Check if this is a one-way flight (no return itinerary)
            if (!flight.itineraries[1]) {
                return true; // Include one-way flights when return time filter is applied
            }
        }
        
        const itinerary = type === 'departure' ? flight.itineraries[0] : flight.itineraries[1];
        if (!itinerary || !itinerary.segments || !itinerary.segments[0]) return false;
        
        const departureTime = new Date(itinerary.segments[0].departure.at);
        const hour = departureTime.getHours();
        
        for (let time of selectedTimes) {
            if (time === 'early_morning' && hour >= 0 && hour < 5) return true;
            if (time === 'morning' && hour >= 5 && hour < 12) return true;
            if (time === 'afternoon' && hour >= 12 && hour < 18) return true;
            if (time === 'evening' && hour >= 18 && hour < 24) return true;
        }
        
        return false;
    }

    /**
     * Clear all filters
     */
    function clearAllFilters() {
        // Uncheck all checkboxes including meal filter
        // $('.amadex-filter-option input[type="checkbox"], input[name="meal"]').prop('checked', false);
        $('.amadex-filter-option input[type="checkbox"], input[name="meal"], input[name="popular_filter"]').prop('checked', false);
        $('#amadex-popular-toggle').prop('checked', false);
        
        // Reset price sliders
        // const prices = allFlights.map(f => parseFloat(f.price.total));
        // const minPrice = prices.length > 0 ? Math.max(Math.min(...prices), PRICE_MIN_BOUND) : PRICE_MIN_BOUND;
        // const maxPrice = prices.length > 0 ? Math.min(Math.max(...prices), PRICE_MAX_BOUND) : PRICE_MAX_BOUND;
        
        // $('#amadex-price-min').val(PRICE_MIN_BOUND);
        // $('#amadex-price-max').val(PRICE_MAX_BOUND);
        // $('#amadex-price-min-display').text('$' + Math.round(PRICE_MIN_BOUND));
        // $('#amadex-price-max-display').text('$' + Math.round(PRICE_MAX_BOUND));
        // updatePriceSliderTrack();
        
        // // Reset duration sliders
        // $('#amadex-duration-min').val(DURATION_MIN_BOUND);
        // $('#amadex-duration-max').val(DURATION_MAX_BOUND);
        // $('#amadex-duration-min-display').text(DURATION_MIN_BOUND + 'h');
        // $('#amadex-duration-max-display').text(DURATION_MAX_BOUND + 'h');
        // updateDurationSliderTrack();
        
        $('#amadex-price-min').val(basePriceMin);
        $('#amadex-price-max').val(basePriceMax);
        $('#amadex-price-min-display').text('$' + basePriceMin);
        $('#amadex-price-max-display').text('$' + basePriceMax);
        updatePriceSliderTrack();
        
        // $('#amadex-duration-min').val(baseDurationMin);
        // $('#amadex-duration-max').val(baseDurationMax);
        // $('#amadex-duration-min-display').text(baseDurationMin + 'h');
        // $('#amadex-duration-max-display').text(baseDurationMax + 'h');
        // updateDurationSliderTrack();
        
        $('#amadex-duration-min').val(baseDurationMin);
        $('#amadex-duration-max').val(baseDurationMax);
        $('#amadex-duration-min-display').text(baseDurationMin + 'h');
        $('#amadex-duration-max-display').text(baseDurationMax + 'h');
        updateDurationSliderTrack();

        const retMin = $('#amadex-return-duration-min').attr('min') || baseDurationMin;
        const retMax = $('#amadex-return-duration-max').attr('max') || baseDurationMax;
        $('#amadex-return-duration-min').val(retMin);
        $('#amadex-return-duration-max').val(retMax);
        $('#amadex-return-duration-min-display').text(retMin + 'h');
        $('#amadex-return-duration-max-display').text(retMax + 'h');
        updateReturnDurationSliderTrack();

        // Apply filters (show all)
        refreshFilterOptionStates();
        applyFilters();
    }
    
    /**
     * Update price slider track fill between handles
     */
function updatePriceSliderTrack() {
        const $track = $('.amadex-price-slider-track');
        if (!$track.length) return;
        const min       = parseInt($('#amadex-price-min').val());
        const max       = parseInt($('#amadex-price-max').val());
        const sliderMin = parseInt($('#amadex-price-min').attr('min')) || 0;
        const sliderMax = parseInt($('#amadex-price-max').attr('max')) || 100;
        const range     = sliderMax - sliderMin || 1;
        const minPct    = ((min - sliderMin) / range * 100).toFixed(1);
        const maxPct    = ((max - sliderMin) / range * 100).toFixed(1);
        $track.css('background',
            `linear-gradient(90deg, #D1D5DB 0%, #D1D5DB ${minPct}%, #0E7D3F ${minPct}%, #0E7D3F ${maxPct}%, #D1D5DB ${maxPct}%, #D1D5DB 100%)`
        );
    }
    
    /**
     * Update duration slider track
     */
    function updateDurationSliderTrack() {
        const $track = $('.amadex-duration-slider-track');
        if (!$track.length) return;
        const min       = parseInt($('#amadex-duration-min').val());
        const max       = parseInt($('#amadex-duration-max').val());
        const sliderMin = parseInt($('#amadex-duration-min').attr('min')) || 0;
        const sliderMax = parseInt($('#amadex-duration-max').attr('max')) || 100;
        const range     = sliderMax - sliderMin || 1;
        const minPct    = ((min - sliderMin) / range * 100).toFixed(1);
        const maxPct    = ((max - sliderMin) / range * 100).toFixed(1);
        $track.css('background',
            `linear-gradient(90deg, #D1D5DB 0%, #D1D5DB ${minPct}%, #0E7D3F ${minPct}%, #0E7D3F ${maxPct}%, #D1D5DB ${maxPct}%, #D1D5DB 100%)`
        );
    }
    
    /**
     * Refresh visual state of filter options
     */
    function refreshFilterOptionStates() {
        $('.amadex-filter-option input[type="checkbox"]').each(function() {
            $(this).closest('.amadex-filter-option').toggleClass('is-checked', $(this).is(':checked'));
        });
    }
    
    /**
     * Update active filter chips row
     */
    function updateActiveFilterChips(stops, airlines, priceMin, priceMax, durationMin, durationMax, departureTimes, returnTimes, hasFreeMeal) {
        const $chips = $('#amadex-active-filters');
        if (!$chips.length) return;
        $chips.empty();
        
        const addChip = (label, remover) => {
            const $chip = $(`
                <button type="button" class="amadex-filter-tag">
                    <span>${label}</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 15 15">
  <g id="Group_181" data-name="Group 181" transform="translate(-340 -317)">
    <g id="Ellipse_9" data-name="Ellipse 9" transform="translate(340 317)" fill="#fff" stroke="#0e7d3f" stroke-width="1">
      <circle cx="7.5" cy="7.5" r="7.5" stroke="none"/>
      <circle cx="7.5" cy="7.5" r="7" fill="none"/>
    </g>
    <g id="Group_146" data-name="Group 146" transform="translate(343.915 320.915)">
      <path id="Path_12" data-name="Path 12" d="M4.338,3.8,7.194.947A.475.475,0,0,0,6.522.275L3.667,3.131.811.275A.475.475,0,0,0,.139.947L2.995,3.8.139,6.658a.475.475,0,1,0,.672.672L3.667,4.474,6.522,7.33a.475.475,0,0,0,.672-.672Zm0,0" transform="translate(0 -0.136)" fill="#0e7d3f"/>
    </g>
  </g>
</svg>
                </button>
            `);
            $chip.on('click', remover);
            $chips.append($chip);
        };
        
                $('input[name="popular_filter"]:checked').each(function() {
            const labelText = $(this).closest('.amadex-filter-option').find('.amadex-filter-label').text();
            const $input = $(this);
            addChip(labelText, () => {
                $input.prop('checked', false).trigger('change');
            });
        });

        // Stop chips
        stops.forEach(value => {
            const $input = $(`input[name="stops"][value="${value}"]`);
            const labelText = $input.closest('.amadex-filter-option').find('.amadex-filter-label').text();
            addChip(labelText || `Stops ${value}`, () => {
                $input.prop('checked', false).trigger('change');
            });
        });
        
        airlines.forEach(code => {
            const $input = $(`input[name="airlines"][value="${code}"]`);
            const labelText = $input.closest('.amadex-filter-option').find('.amadex-filter-label').text();
            addChip(labelText || code, () => {
                $input.prop('checked', false).trigger('change');
            });
        });
        
        departureTimes.forEach(value => {
            const $input = $(`input[name="departure_time"][value="${value}"]`);
            const labelText = $input.closest('.amadex-filter-option').find('.amadex-filter-label').text();
            addChip(`Depart: ${labelText || value}`, () => {
                $input.prop('checked', false).trigger('change');
            });
        });
        
        returnTimes.forEach(value => {
            const $input = $(`input[name="return_time"][value="${value}"]`);
            const labelText = $input.closest('.amadex-filter-option').find('.amadex-filter-label').text();
            addChip(`Arrive: ${labelText || value}`, () => {
                $input.prop('checked', false).trigger('change');
            });
        });
        
        // Meal filter chip
        if (hasFreeMeal) {
            const $mealInput = $('#amadex-filter-meal');
            addChip('Free Meal Included', () => {
                $mealInput.prop('checked', false).trigger('change');
            });
        }
        
        if (priceMin > basePriceMin || priceMax < basePriceMax) {
            addChip(`$${priceMin} - $${priceMax}`, () => {
                $('#amadex-price-min').val(basePriceMin);
                $('#amadex-price-max').val(basePriceMax);
                $('#amadex-price-min-display').text('$' + Math.round(basePriceMin));
                $('#amadex-price-max-display').text('$' + Math.round(basePriceMax));
                updatePriceSliderTrack();
                applyFilters();
            });
        }
        
        // if (durationMin > DURATION_MIN_BOUND || durationMax < DURATION_MAX_BOUND) {
        //     addChip(`Fly: ${durationMin}h - ${durationMax}h`, () => {
        //         $('#amadex-duration-min').val(DURATION_MIN_BOUND);
        //         $('#amadex-duration-max').val(DURATION_MAX_BOUND);
        //         $('#amadex-duration-min-display').text(DURATION_MIN_BOUND + 'h');
        //         $('#amadex-duration-max-display').text(DURATION_MAX_BOUND + 'h');

        if (durationMin > baseDurationMin || durationMax < baseDurationMax) {
            addChip(`Fly: ${durationMin}h – ${durationMax}h`, () => {
                $('#amadex-duration-min').val(baseDurationMin);
                $('#amadex-duration-max').val(baseDurationMax);
                $('#amadex-duration-min-display').text(baseDurationMin + 'h');
                $('#amadex-duration-max-display').text(baseDurationMax + 'h');
                updateDurationSliderTrack();
                applyFilters();
            });
        }
    }
    
    /**
     * Get flight duration in minutes
     */
    function getFlightDurationMinutes(flight) {
        if (!flight.itineraries || !flight.itineraries.length) return 0;
        const itinerary = flight.itineraries[0];
        
        if (itinerary.duration) {
            return parseIsoDurationMinutes(itinerary.duration);
        }
        
        if (itinerary.segments && itinerary.segments.length) {
            const firstDeparture = new Date(itinerary.segments[0].departure.at);
            const lastArrival = new Date(itinerary.segments[itinerary.segments.length - 1].arrival.at);
            const diff = (lastArrival - firstDeparture) / 60000;
            return diff > 0 ? diff : 0;
        }
        
        return 0;
    }
    
    // function parseIsoDurationMinutes(duration) {

    function getReturnDurationMinutes(flight) {
        if (!flight.itineraries || !flight.itineraries[1]) return 0;
        const itinerary = flight.itineraries[1];
        if (itinerary.duration) return parseIsoDurationMinutes(itinerary.duration);
        if (itinerary.segments && itinerary.segments.length) {
            const first = new Date(itinerary.segments[0].departure.at);
            const last  = new Date(itinerary.segments[itinerary.segments.length - 1].arrival.at);
            const diff  = (last - first) / 60000;
            return diff > 0 ? diff : 0;
        }
        return 0;
    }

    // function updateReturnDurationSliderTrack() {
    //     const $track = $('.amadex-return-duration-slider-track');
    //     if (!$track.length) return;
    //     $track.css('background', 'linear-gradient(90deg, #0E7D3F 0%, #0E7D3F 100%)');
    // }

    function updateReturnDurationSliderTrack() {
        const $track = $('.amadex-return-duration-slider-track');
        if (!$track.length) return;
        const min       = parseInt($('#amadex-return-duration-min').val());
        const max       = parseInt($('#amadex-return-duration-max').val());
        const sliderMin = parseInt($('#amadex-return-duration-min').attr('min')) || 0;
        const sliderMax = parseInt($('#amadex-return-duration-max').attr('max')) || 100;
        const range     = sliderMax - sliderMin || 1;
        const minPct    = ((min - sliderMin) / range * 100).toFixed(1);
        const maxPct    = ((max - sliderMin) / range * 100).toFixed(1);
        $track.css('background',
            `linear-gradient(90deg, #D1D5DB 0%, #D1D5DB ${minPct}%, #0E7D3F ${minPct}%, #0E7D3F ${maxPct}%, #D1D5DB ${maxPct}%, #D1D5DB 100%)`
        );
    }

    function parseIsoDurationMinutes(duration) {
        if (!duration || typeof duration !== 'string') return 0;
        const hoursMatch = duration.match(/(\d+)H/);
        const minutesMatch = duration.match(/(\d+)M/);
        const hours = hoursMatch ? parseInt(hoursMatch[1], 10) : 0;
        const minutes = minutesMatch ? parseInt(minutesMatch[1], 10) : 0;
        return hours * 60 + minutes;
    }

/**
     * Save current filter state (checked boxes + slider values)
     */
    window.amadexSaveFilterState = function() {
        var state = {
            stops: [], airlines: [], popular: [],
            priceMin: $('#amadex-price-min').val(),
            priceMax: $('#amadex-price-max').val(),
            durationMin: $('#amadex-duration-min').val(),
            durationMax: $('#amadex-duration-max').val(),
            returnDurationMin: $('#amadex-return-duration-min').val(),
            returnDurationMax: $('#amadex-return-duration-max').val(),
            departureTimes: [], returnTimes: [],
            meal: $('#amadex-filter-meal').is(':checked')
        };
        $('input[name="stops"]:checked').each(function() { state.stops.push($(this).val()); });
        $('input[name="airlines"]:checked').each(function() { state.airlines.push($(this).val()); });
        $('input[name="popular_filter"]:checked').each(function() { state.popular.push($(this).val()); });
        $('input[name="departure_time"]:checked').each(function() { state.departureTimes.push($(this).val()); });
        $('input[name="return_time"]:checked').each(function() { state.returnTimes.push($(this).val()); });
        var hasActive = state.stops.length || state.airlines.length || state.popular.length ||
                        state.departureTimes.length || state.returnTimes.length || state.meal ||
                        parseFloat(state.priceMin) > basePriceMin || parseFloat(state.priceMax) < basePriceMax;
        return hasActive ? state : null;
    };

    /**
     * Restore a previously saved filter state after tab switch
     */
    window.amadexRestoreFilterState = function(state) {
        if (!state) return;
        state.stops.forEach(function(v) { $('input[name="stops"][value="' + v + '"]').prop('checked', true); });
        state.airlines.forEach(function(v) { $('input[name="airlines"][value="' + v + '"]').prop('checked', true); });
        state.popular.forEach(function(v) { $('input[name="popular_filter"][value="' + v + '"]').prop('checked', true); });
        state.departureTimes.forEach(function(v) { $('input[name="departure_time"][value="' + v + '"]').prop('checked', true); });
        state.returnTimes.forEach(function(v) { $('input[name="return_time"][value="' + v + '"]').prop('checked', true); });
        if (state.meal) $('#amadex-filter-meal').prop('checked', true);
        if (state.priceMin !== undefined) { $('#amadex-price-min').val(state.priceMin); $('#amadex-price-min-display').text('$' + Math.round(state.priceMin)); }
        if (state.priceMax !== undefined) { $('#amadex-price-max').val(state.priceMax); $('#amadex-price-max-display').text('$' + Math.round(state.priceMax)); }
        updatePriceSliderTrack();
        if (state.durationMin !== undefined) { $('#amadex-duration-min').val(state.durationMin); $('#amadex-duration-min-display').text(state.durationMin + 'h'); }
        if (state.durationMax !== undefined) { $('#amadex-duration-max').val(state.durationMax); $('#amadex-duration-max-display').text(state.durationMax + 'h'); }
        updateDurationSliderTrack();
        if (state.returnDurationMin !== undefined) { $('#amadex-return-duration-min').val(state.returnDurationMin); $('#amadex-return-duration-min-display').text(state.returnDurationMin + 'h'); }
        if (state.returnDurationMax !== undefined) { $('#amadex-return-duration-max').val(state.returnDurationMax); $('#amadex-return-duration-max-display').text(state.returnDurationMax + 'h'); }
        updateReturnDurationSliderTrack();
        refreshFilterOptionStates();
        applyFilters();
    };

})(jQuery);

