/**
 * Amadex Modern Search Form JavaScript
 * Professional & Smooth Functionality
 */

(function($) {
    'use strict';

    // Passenger counters
    const MAX_MULTI_SEGMENTS = 7; // main bar (1) + 5 extra = 6 total (auto-add takes 1)
    
    let passengers = {
        adults: 1,
        children: 0,
        infants: 0
    };
    
    let selectedCabin = 'ECONOMY';

    // Calendar variables (global to this module)
    let selectedDepartureDate = null;
    let selectedReturnDate = null;
    let currentMonth = new Date();
    let currentCalendarField = null;
    let currentSegmentId = null;
    const segmentDepartureDates = {};
    let suppressLocationDropdowns = false; // Flag to prevent dropdown reopening

    // Initialize when document is ready
    $(document).ready(function() {
        initModernSearch();
    });

     /**
     * Limit text to specified number of words
     * @param {string} text - The text to limit
     * @param {number} maxWords - Maximum number of words (default: 2)
     * @returns {string} - Limited text with ellipsis if truncated
     */
    function limitWords(text, maxWords = 2) {
        if (!text || typeof text !== 'string') return '';
        const words = text.trim().split(/\s+/);
        if (words.length <= maxWords) return text;
        return words.slice(0, maxWords).join(' ') + '…';
    }
// ─── COOKIE PERSISTENCE ──────────────────────────────────────────────────────
    // Saves and restores the user's last search so it survives page reload / revisit.
    // Uses cookies (not sessionStorage) so data persists across browser sessions.
    // Cookie name: amadex_last_search  |  Expiry: 30 days
    // ─────────────────────────────────────────────────────────────────────────────

    var COOKIE_NAME = 'amadex_last_search';
    var COOKIE_DAYS = 30;

    function setCookie(name, value, days) {
        var expires = '';
        if (days) {
            var d = new Date();
            d.setTime(d.getTime() + days * 24 * 60 * 60 * 1000);
            expires = '; expires=' + d.toUTCString();
        }
        document.cookie = name + '=' + encodeURIComponent(value) + expires + '; path=/; SameSite=Lax';
    }

    function getCookie(name) {
        var nameEQ = name + '=';
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i].trim();
            if (c.indexOf(nameEQ) === 0) {
                try { return decodeURIComponent(c.substring(nameEQ.length)); } catch(e) { return null; }
            }
        }
        return null;
    }

    function saveSearchToCookie() {
        try {
            var tripType = $('input[name="tripType"]:checked').val() || 'round';
            var data = {
                trip_type:        tripType,
                origin:           $('#modern-origin').val() || '',
                origin_code:      $('#modern-origin-code').val() || '',
                destination:      $('#modern-destination').val() || '',
                destination_code: $('#modern-destination-code').val() || '',
                departure:        $('#modern-departure').val() || '',
                // return_date:      $('#modern-return').val() || '',
                return_date:      '',
                adults:           $('#modern-adults').val() || '1',
                children:         $('#modern-children').val() || '0',
                infants:          $('#modern-infants').val() || '0',
                cabin:            $('#modern-cabin').val() || 'ECONOMY'
            };

            // Save multi-city segments if applicable
            if (tripType === 'multi-city') {
                data.segments = collectMultiSegments(true);
            }

            setCookie(COOKIE_NAME, JSON.stringify(data), COOKIE_DAYS);
        } catch(e) {
            console.warn('amadex: could not save search cookie', e);
        }
    }

    function restoreSearchFromCookie() {
        // Only restore on the HOME page search form (not on results/booking pages)
        // Don't restore if URL already has search params (user came from a search)
        var urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('origin_iata') || urlParams.get('trip_type')) return;

        // Only restore into the main home form, not the results modify bar
        var $form = $('#amadex-modern-form');
        if (!$form.length) return;

        var raw = getCookie(COOKIE_NAME);
        if (!raw) return;

        try {
            var d = JSON.parse(raw);

            // Trip type
            if (d.trip_type) {
                $form.find('input[name="tripType"][value="' + d.trip_type + '"]')
                    .prop('checked', true).trigger('change');
            }

            // Origin
            if (d.origin) {
                $form.find('#modern-origin').val(d.origin);
                $form.find('#modern-origin-code').val(d.origin_code || '');
                var oMatch = d.origin.match(/(.+?)\s*\([A-Z]{3}\)/);
                if (oMatch) $form.find('#origin-description').text(limitWords(oMatch[1].trim()));
            }

            // Destination
            if (d.destination) {
                $form.find('#modern-destination').val(d.destination);
                $form.find('#modern-destination-code').val(d.destination_code || '');
                var dstMatch = d.destination.match(/(.+?)\s*\([A-Z]{3}\)/);
                if (dstMatch) $form.find('#destination-description').text(limitWords(dstMatch[1].trim()));
            }

            // if (d.departure) {
            //     var dep = new Date(d.departure);
            //     var today = new Date(); today.setHours(0,0,0,0);
            //     if (dep >= today) {
            //         $form.find('#modern-departure, #vsb-departure-date').val(d.departure);
            //         selectedDepartureDate = dep;
            //         updateDateDisplay(dep, '#departure-display', '#departure-day');
            //     } else {
            //         $form.find('#modern-departure, #vsb-departure-date').val('');
            //     }
            // }

            // // Return date
            // if (d.return_date && d.trip_type === 'round') {
            //     var ret = new Date(d.return_date);
            //     var today2 = new Date(); today2.setHours(0,0,0,0);
            //     if (ret >= today2) {
            //         $form.find('#modern-return, #vsb-return-date').val(d.return_date);
            //         selectedReturnDate = ret;
            //         updateDateDisplay(ret, '#return-display', '#return-day');
            //     }
            // }
// // Departure date — always default to today
//             var today = new Date(); today.setHours(0,0,0,0);
//             var todayStr = today.toISOString().split('T')[0]; // YYYY-MM-DD
//             $form.find('#modern-departure, #vsb-departure-date').val(todayStr);
//             selectedDepartureDate = today;
//             updateDateDisplay(today, '#departure-display', '#departure-day');

// Departure date — always default to today
            var today = new Date(); today.setHours(0,0,0,0);
            // var todayStr = today.toISOString().split('T')[0]; // YYYY-MM-DD
            // Use local date to avoid UTC timezone shift (e.g. India is UTC+5:30)
            var todayStr = today.getFullYear() + '-' +
                String(today.getMonth() + 1).padStart(2, '0') + '-' +
                String(today.getDate()).padStart(2, '0');

            // Set in ALL possible date input fields
            $('#modern-departure').val(todayStr);
            $('#vsb-departure-date').val(todayStr);
            $('[id^="modern-departure"]').val(todayStr); // catches any segment inputs too
            selectedDepartureDate = today;
            updateDateDisplay(today, '#departure-display', '#departure-day');

            // Also store today as the departure in the cookie right now
            // so search always reads the correct value
            var cookieRaw = getCookie(COOKIE_NAME);
            if (cookieRaw) {
                try {
                    var cookieData = JSON.parse(cookieRaw);
                    cookieData.departure = todayStr;
                    cookieData.return_date = '';
                    setCookie(COOKIE_NAME, JSON.stringify(cookieData), COOKIE_DAYS);
                } catch(e) {}
            }

            // Return date — always clear so user must pick it fresh
            $form.find('#modern-return, #vsb-return-date').val('');
            selectedReturnDate = null;
            var $retDisplay = $form.find('#return-display');
            var $retDay = $form.find('#return-day');
            // if ($retDisplay.length) $retDisplay.text('Return Date').css('color', '#aaa');
            // if ($retDay.length) $retDay.text('');
            if ($retDisplay.length) $retDisplay.text('Return Date').css('color', '#aaa');
            if ($retDay.length) $retDay.text('Select Date').css('color', '#aaa');
            // Passengers
            if (d.adults)   { passengers.adults   = parseInt(d.adults);   $form.find('#modern-adults').val(d.adults);     $form.find('#adults-count').text(d.adults); }
            if (d.children) { passengers.children = parseInt(d.children); $form.find('#modern-children').val(d.children); $form.find('#children-count').text(d.children); }
            if (d.infants)  { passengers.infants  = parseInt(d.infants);  $form.find('#modern-infants').val(d.infants);   $form.find('#infants-count').text(d.infants); }

            // Cabin
            if (d.cabin) {
                selectedCabin = d.cabin;
                $form.find('#modern-cabin').val(d.cabin);
                $form.find('.amadex-cabin-btn').removeClass('active');
                $form.find('.amadex-cabin-btn[data-cabin="' + d.cabin + '"]').addClass('active');
            }

            updateTravellersDisplay();
            updateCounterButtons();

            // Multi-city segments
            if (d.trip_type === 'multi-city' && d.segments && d.segments.length > 1 && !window._amadexSegmentRestoreInProgress) {
                window._amadexSegmentRestoreInProgress = true;
                setTimeout(function() {
                    $('.vsb-extra-segment').remove();
                    $('.amadex-flight-segment').not('[data-segment="1"]').remove();
                    $('.amadex-multi-city-segment').not('[data-segment="1"]').remove();
                    segmentCounter = 1;

                    d.segments.slice(1).forEach(function(seg, idx) {
                        setTimeout(function() {
                            addFlightSegment();
                            var segId = segmentCounter;
                            $('#modern-origin-'      + segId).val(seg.origin_name || seg.origin || '');
                            $('#modern-origin-code-' + segId).val(seg.origin || '');
                            $('#modern-destination-' + segId).val(seg.destination_name || seg.destination || '');
                            $('#modern-destination-code-' + segId).val(seg.destination || '');
                            if (seg.departure) {
                                var sd = new Date(seg.departure);
                                var today3 = new Date(); today3.setHours(0,0,0,0);
                                if (sd >= today3) {
                                    $('#modern-departure-' + segId).val(seg.departure);
                                    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                                    $('#departure-display-' + segId).text(sd.getDate() + ' ' + months[sd.getMonth()] + ', ' + String(sd.getFullYear()).substr(2));
                                    $('#departure-day-' + segId).text(['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][sd.getDay()]);
                                    segmentDepartureDates[segId] = sd;
                                }
                            }
                            if (idx >= d.segments.length - 2) {
                                window._amadexSegmentRestoreInProgress = false;
                            }
                        }, idx * 120);
                    });
                }, 300);
            }

        } catch(e) {
            console.warn('amadex: could not restore search cookie', e);
        }
    }
    // ─────────────────────────────────────────────────────────────────────────────
// ─── USER LOCATION DETECTION ─────────────────────────────────────────────
    function requestUserLocation() {
        // Only ask once — if already asked, skip
        if (localStorage.getItem('amadex_location_asked')) return;

        // Mark as asked immediately (prevent repeat prompts)
        localStorage.setItem('amadex_location_asked', '1');

        if (!navigator.geolocation) return;

        navigator.geolocation.getCurrentPosition(
            function(position) {
                var lat = position.coords.latitude;
                var lon = position.coords.longitude;

                // Reverse geocode using free API to get city/airport
                fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lon)
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        var city    = data.address.city || data.address.town || data.address.village || '';
                        var country = data.address.country_code ? data.address.country_code.toUpperCase() : '';

                        // Save to localStorage for use across sessions
                        localStorage.setItem('amadex_user_city',    city);
                        localStorage.setItem('amadex_user_country',  country);
                        localStorage.setItem('amadex_user_lat',      lat);
                        localStorage.setItem('amadex_user_lon',      lon);

                        // Try to pre-fill the origin field if it's empty
                        var $origin = $('#modern-origin');
                        if ($origin.length && !$origin.val()) {
                            prefillOriginFromLocation(city, country);
                        }
                    })
                    .catch(function() {
                        // Silently fail — location enrichment is optional
                    });
            },
            function(error) {
                // User denied or error — silently ignore
            },
            {
                timeout:            8000,
                maximumAge:         600000, // Cache for 10 minutes
                enableHighAccuracy: false
            }
        );
    }

    function prefillOriginFromLocation(city, country) {
        // Match city name against known airports using the existing search
        if (!city) return;

        // Use the existing airport autocomplete AJAX to find the closest airport
        $.ajax({
            url:  typeof amadexAjax !== 'undefined' ? amadexAjax.ajaxurl : '',
            type: 'POST',
            data: {
                action: 'amadex_search_airports',
                nonce:  typeof amadexAjax !== 'undefined' ? amadexAjax.nonce : '',
                query:  city
            },
            success: function(response) {
                if (response.success && response.data && response.data.length > 0) {
                    var airport = response.data[0]; // Best match
                    var iata    = airport.iata || airport.code || '';
                    var name    = airport.name || airport.city || city;
                    var display = name + ' (' + iata + ')';

                    $('#modern-origin').val(display);
                    $('#modern-origin-code').val(iata);

                    // Show description if element exists
                    var $desc = $('#origin-description');
                    if ($desc.length) {
                        $desc.text(typeof limitWords === 'function' ? limitWords(name) : name);
                    }

                    // Save to cookie too so it persists
                    localStorage.setItem('amadex_user_airport_iata', iata);
                    localStorage.setItem('amadex_user_airport_name', display);
                }
            }
        });
    }
    // ─────────────────────────────────────────────────────────────────────────

    function clearAndStoreNewSearch() {
        const tripType = $('input[name="tripType"]:checked').val() || 'round';

        function extractIataCode(codeStr) {
            if (!codeStr) return '';
            if (/^[A-Z]{3}$/i.test(codeStr.trim())) return codeStr.trim().toUpperCase();
            var match = codeStr.match(/\(([A-Z]{3})\)/i);
            if (match && match[1]) return match[1].toUpperCase();
            var codeMatch = codeStr.match(/[A-Z]{3}/i);
            if (codeMatch) return codeMatch[0].toUpperCase();
            return codeStr.trim().toUpperCase();
        }

        // Collect fresh segment data BEFORE clearing anything
        var freshSegments = [];
        if (tripType === 'multi-city') {
            freshSegments = collectMultiSegments(true);
        }

        // Wipe ALL stale data
        sessionStorage.removeItem('amadex_multi_city_segments');
        sessionStorage.removeItem('amadex_multi_city_bookings');
        sessionStorage.removeItem('amadex_booking_all_segments');
        sessionStorage.removeItem('amadex_search_data');
        sessionStorage.removeItem('amadex_search_results');
        sessionStorage.removeItem('amadex_booking_flight');
        sessionStorage.removeItem('amadex_last_booking_flight_id');
        sessionStorage.removeItem('amadex_booking_step');
        sessionStorage.removeItem('amadex_booking_addons');

        // Store fresh segments AFTER clearing
        if (tripType === 'multi-city' && freshSegments.length > 1) {
            var normalized = freshSegments.map(function(seg) {
                return {
                    origin:                  extractIataCode(seg.origin || ''),
                    originLocationCode:      extractIataCode(seg.origin || ''),
                    origin_name:             seg.origin_name || seg.origin || '',
                    destination:             extractIataCode(seg.destination || ''),
                    destinationLocationCode: extractIataCode(seg.destination || ''),
                    destination_name:        seg.destination_name || seg.destination || '',
                    departure:               seg.departure || '',
                    departure_date:          seg.departure || ''
                };
            });
            sessionStorage.setItem('amadex_multi_city_segments', JSON.stringify(normalized));
        }
    }
    /**
     * Initialize modern search form
     */
    function initModernSearch() {
        // Trip type switching (handle both forms)
        // $(document).on('change', 'input[name="tripType"]', function() {
        //     const tripType = $(this).val();
        //     const $form = $(this).closest('.amadex-modern-form');
        //     handleTripTypeChange(tripType, $form);
        // });

        $(document).on('change', 'input[name="tripType"]', function() {
            const tripType = $(this).val();
            const $form = $(this).closest('.amadex-modern-form');
            if (tripType !== 'multi-city') {
                sessionStorage.removeItem('amadex_multi_city_segments');
                sessionStorage.removeItem('amadex_multi_city_bookings');
                sessionStorage.removeItem('amadex_booking_all_segments');
            }
            handleTripTypeChange(tripType, $form);
        });
        
        // Initialize trip type state on page load (for each form)
        $('.amadex-modern-form').each(function() {
            const $form = $(this);
            const initialTripType = $form.find('input[name="tripType"]:checked').val();
            if (initialTripType) {
                handleTripTypeChange(initialTripType, $form);
            }
        });

        // Initialize date fields
        initDateFields();
        
        // Initialize location autocomplete
        initLocationAutocomplete();
        
        // Swap button
$(document).on('click', '.amadex-swap-button, #swap-locations', function(e) {
    e.preventDefault();
    e.stopPropagation();
    swapLocations();
});
        
        // Multi-city: Add City button
$(document).on('click', '#add-city-btn, .vsb-add-city-btn', function() {
    addFlightSegment();
});
        
        // Multi-city: Remove segment (delegate for dynamic buttons)
        $(document).on('click', '.amadex-remove-segment-btn', function() {
            const segmentNumber = $(this).closest('.amadex-flight-segment').data('segment');
            removeFlightSegment(segmentNumber);
        });

        // Multi-city: Swap origin/destination for each segment row
        $(document).on('click', '.amadex-segment-swap', function() {
            const segNum = $(this).data('segment');
            const $originInput   = $(`#modern-origin-${segNum}`);
            const $originCode    = $(`#modern-origin-code-${segNum}`);
            const $destInput     = $(`#modern-destination-${segNum}`);
            const $destCode      = $(`#modern-destination-code-${segNum}`);

            const originVal  = $originInput.val();
            const originC    = $originCode.val();
            const destVal    = $destInput.val();
            const destC      = $destCode.val();

            // Always rotate in same direction — accumulate 180° per click
            const $btn = $(this);
            const currentRotation = parseInt($btn.data('rotation') || 0);
            const newRotation = currentRotation + 180;
            $btn.data('rotation', newRotation);
            $btn.find('svg').css({
                transform: `rotate(${newRotation}deg)`,
                transition: 'transform 0.4s ease'
            });

            // Animate fields
            $(`#origin-field-${segNum}, #destination-field-${segNum}`).css({
                transform: 'scale(0.95)', opacity: '0.7',
                transition: 'all 0.25s cubic-bezier(0.4, 0, 0.2, 1)'
            });

            setTimeout(function() {
                $originInput.val(destVal);
                $originCode.val(destC);
                $destInput.val(originVal);
                $destCode.val(originC);
                $(`#origin-field-${segNum}, #destination-field-${segNum}`).css({
                    transform: 'scale(1)', opacity: '1'
                });
            }, 250);
        });
        
        // Travellers dropdown
        initTravellersDropdown();
        
        // Form submission (handle both search and results page forms)
        // $('#amadex-modern-form, #amadex-modern-form-results').on('submit', function(e) {
        //     e.preventDefault();
        //     performModernSearch();
        // });
        // Form submission (handle both search and results page forms)
        // $('#amadex-modern-form, #amadex-modern-form-results').on('submit', function(e) {
        //     e.preventDefault();
        //     saveSearchToCookie();
        //     performModernSearch();
        // });
$('#amadex-modern-form, #amadex-modern-form-results').on('submit', function(e) {
            e.preventDefault();
            clearAndStoreNewSearch();
            saveSearchToCookie();
            const currentTripType = $('input[name="tripType"]:checked').val() || 'round';
            if (currentTripType !== 'multi-city') {
                sessionStorage.removeItem('amadex_multi_city_segments');
                sessionStorage.removeItem('amadex_multi_city_bookings');
                sessionStorage.removeItem('amadex_booking_all_segments');
            }
            performModernSearch();
        });

        // Also bind directly to search button click (VSB segments live outside the form tag)
        $(document).on('click', '.amadex-search-btn, #amadex-modify-search-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            clearAndStoreNewSearch();
            saveSearchToCookie();
            const currentTripType = $('input[name="tripType"]:checked').val() || 'round';
            if (currentTripType !== 'multi-city') {
                sessionStorage.removeItem('amadex_multi_city_segments');
                sessionStorage.removeItem('amadex_multi_city_bookings');
                sessionStorage.removeItem('amadex_booking_all_segments');
            }
            performModernSearch();
        });

        // Set minimum dates
        // setMinimumDates();
        setMinimumDates();

        // Restore last search from cookie on home page
        setTimeout(function() {
            restoreSearchFromCookie();
        }, 150);

        // Ask for location permission on first visit
        requestUserLocation();
    }

function handleTripTypeChange(tripType, $form) {
    if (!$form || !$form.length) {
        $form = $('.amadex-modern-form').first();
    }

    const $returnField = $form.find('#return-field, .vsb-field--return');
    const $addCityBtn = $form.find('#add-city-btn');

    // Update active tab styling for VSB nav
    $form.find('input[name="tripType"]').each(function() {
        const $label = $form.find('label[for="' + $(this).attr('id') + '"]');
        if ($(this).val() === tripType) {
            $label.addClass('is-active');
        } else {
            $label.removeClass('is-active');
        }
    });

    // Reset return field state
    $returnField.removeClass('field-disabled subtle-disabled return-promo');

    if (tripType === 'multi-city') {
        $form.addClass('amadex-multi-city-mode');

        // Show Add City button — inside VSB wrap
        const $vsbWrap = $form.closest('.vsb-wrap');
        if ($vsbWrap.length) {
            // Inject Add City button after vsb-card if not already there
            if ($vsbWrap.find('.vsb-add-city-btn').length === 0) {
                $vsbWrap.append('<button type="button" class="vsb-add-city-btn amadex-add-city-btn-vsb">+ Add City</button>');
            }
            $vsbWrap.find('.vsb-add-city-btn').show();
        }

        $addCityBtn.show();
        $('#modern-return').prop('required', false);

        // Disable return field
        $returnField.addClass('field-disabled subtle-disabled');

        // Reveal any stored segments
        $('.amadex-flight-segment').show();

        // Auto-add a second segment if only segment 1 (main bar) exists
        const existingSegments = $('.amadex-flight-segment').length;
        if (existingSegments < 2) {
            addFlightSegment();
        }

        updateMultiSegmentState();
        updateSegmentRemoveButtons();

    } else {
        $form.removeClass('amadex-multi-city-mode has-extra-segments');
        $addCityBtn.hide().prop('disabled', false).removeClass('disabled');

        // Hide VSB Add City button
        $form.closest('.vsb-wrap').find('.vsb-add-city-btn').hide();

        // Hide additional segments
        $('.amadex-flight-segment').each(function() {
            if ($(this).data('segment') !== 1) {
                $(this).hide();
            }
        });

        // Hide VSB extra segments
        $('.vsb-extra-segment').hide();

        if (tripType === 'oneway') {
            $returnField.addClass('field-disabled subtle-disabled');
            $returnField.css('pointer-events', '');
            $('#modern-return').prop('required', false);
        } else {
            // Round trip — fully enable return field
            $returnField.removeClass('field-disabled subtle-disabled return-promo');
            // Force override any residual pointer-events from CSS
            $returnField.css('pointer-events', 'auto');
            $returnField.find('#return-display, #return-day, .amadex-field-value, .amadex-field-input-wrap, input')
                .css('pointer-events', 'auto');
            $('#modern-return').prop('required', true);
        }

        $('#return-day').show();
        restoreReturnFieldDisplay();
    }

    $('#amadex-multicity-wrapper').hide();
    $('#amadex-add-city').hide();
}


    /**
     * Add new flight segment for multi-city
     */
    let segmentCounter = 1;
    
    // function addFlightSegment() {
    //     const currentSegments = $('.amadex-flight-segment').length;
    //     if (currentSegments >= MAX_MULTI_SEGMENTS) {
    function addFlightSegment() {
        const segmentNumbers = new Set();
        $('.amadex-flight-segment').each(function() {
            const seg = $(this).data('segment');
            if (seg) segmentNumbers.add(seg);
        });
        const currentSegments = segmentNumbers.size;
        if (currentSegments >= MAX_MULTI_SEGMENTS) {
            $('#add-city-btn').prop('disabled', true).addClass('disabled');
            return;
        }
        
        segmentCounter++;
        const currentSegment = segmentCounter;
        
        const newSegment = `
            <div class="amadex-flight-segment" data-segment="${segmentCounter}">
                <div class="amadex-search-fields">
                    
                    <!-- Origin Field -->
                    <div class="amadex-modern-field amadex-location-field" id="origin-field-${segmentCounter}">
                        <label class="amadex-field-label">Origin</label>
                        <div class="amadex-field-input-wrap">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24.001" height="10.885" viewBox="0 0 24.001 10.885">
                                <g transform="translate(-5.002 -18.663)">
                                    <path d="M7.012,26.663a2.111,2.111,0,0,0,1.709.869,2.214,2.214,0,0,0,.5-.058c1.68-.408,4.81-1.186,7.843-2.026l-1.454,3.432a.472.472,0,0,0,.038.451.486.486,0,0,0,.4.216,4.05,4.05,0,0,0,3.12-1.464,17.671,17.671,0,0,0,2.707-4.071c.307-.106.6-.206.874-.3a25.818,25.818,0,0,0,5.707-2.486,1.349,1.349,0,0,0,.494-1.445,1.329,1.329,0,0,0-1.171-.965L26.2,18.676a3.709,3.709,0,0,0-1.68.25L18.547,21.3a36.119,36.119,0,0,0-5.832-1.013,2.935,2.935,0,0,0-2.448.888.47.47,0,0,0-.125.442.494.494,0,0,0,.307.346l3.427,1.2-3.9,1.55L7.041,22.9a.46.46,0,0,0-.4-.043l-1.31.442a.481.481,0,0,0-.23.739Z" transform="translate(0 0)" fill="#666"/>
                                </g>
                            </svg>
                            <input type="text" 
                                   class="amadex-field-value" 
                                   id="modern-origin-${segmentCounter}" 
                                   placeholder="Departure City"
                                   autocomplete="off">
                            <input type="hidden" id="modern-origin-code-${segmentCounter}">
                        </div>
                        <div class="amadex-suggestions-dropdown" id="origin-suggestions-${segmentCounter}"></div>
                    </div>
                    
                    <!-- Swap Button -->
                    <button type="button" class="amadex-swap-button amadex-segment-swap" data-segment="${segmentCounter}" aria-label="Swap locations">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48">
                            <circle cx="24" cy="24" r="15" fill="#0e7d3f"/>
                            <g transform="translate(15 14.004)">
                                <path d="M4.841,15.754a.768.768,0,0,1-.545-.223L1.223,12.459a.773.773,0,0,1,.545-1.314H17.133a.768.768,0,0,1,0,1.536H3.62l1.767,1.759a.768.768,0,0,1-.545,1.314ZM17.133,9.609H1.768a.768.768,0,1,1,0-1.536H15.282L13.515,6.313a.771.771,0,1,1,1.091-1.091l3.073,3.073a.773.773,0,0,1-.545,1.314Z" fill="#fff"/>
                            </g>
                        </svg>
                    </button>
                    
                    <!-- Destination Field -->
                    <div class="amadex-modern-field amadex-location-field" id="destination-field-${segmentCounter}">
                        <label class="amadex-field-label">Destination</label>
                        <div class="amadex-field-input-wrap">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24.001" height="10.885" viewBox="0 0 24.001 10.885">
                                <g transform="translate(-5.002 -18.663)">
                                    <path d="M7.012,26.663a2.111,2.111,0,0,0,1.709.869,2.214,2.214,0,0,0,.5-.058c1.68-.408,4.81-1.186,7.843-2.026l-1.454,3.432a.472.472,0,0,0,.038.451.486.486,0,0,0,.4.216,4.05,4.05,0,0,0,3.12-1.464,17.671,17.671,0,0,0,2.707-4.071c.307-.106.6-.206.874-.3a25.818,25.818,0,0,0,5.707-2.486,1.349,1.349,0,0,0,.494-1.445,1.329,1.329,0,0,0-1.171-.965L26.2,18.676a3.709,3.709,0,0,0-1.68.25L18.547,21.3a36.119,36.119,0,0,0-5.832-1.013,2.935,2.935,0,0,0-2.448.888.47.47,0,0,0-.125.442.494.494,0,0,0,.307.346l3.427,1.2-3.9,1.55L7.041,22.9a.46.46,0,0,0-.4-.043l-1.31.442a.481.481,0,0,0-.23.739Z" transform="translate(0 0)" fill="#666"/>
                                </g>
                            </svg>
                            <input type="text" 
                                   class="amadex-field-value" 
                                   id="modern-destination-${segmentCounter}" 
                                   placeholder="Arrival City"
                                   autocomplete="off">
                            <input type="hidden" id="modern-destination-code-${segmentCounter}">
                        </div>
                        <div class="amadex-suggestions-dropdown" id="destination-suggestions-${segmentCounter}"></div>
                    </div>
                    
                    <!-- Departure Date -->
                    <div class="amadex-modern-field amadex-date-field" id="departure-field-${segmentCounter}">
                        <label class="amadex-field-label">Departure Date</label>
                        <div class="amadex-field-input-wrap">
                            <div class="amadex-field-value" id="departure-display-${segmentCounter}" style="color:#aaa;">Select date</div>
                            <input type="date" id="modern-departure-${segmentCounter}">
                        </div>
                    </div>
                    
                    <!-- Return Date (hidden/disabled in multi-city) -->
                    <div class="amadex-modern-field amadex-date-field field-disabled subtle-disabled" id="return-field-${segmentCounter}">
                        <label class="amadex-field-label">Return Date</label>
                        <div class="amadex-field-input-wrap">
                            <div class="amadex-field-value" id="return-display-${segmentCounter}">Return Date</div>
                            <input type="hidden" id="modern-return-${segmentCounter}" value="">
                        </div>
                    </div>
                    
                    <!-- Travellers Field (shared - shows same value as main bar) -->
                    <div class="amadex-modern-field amadex-travellers-field amadex-segment-travellers pointernone" id="travellers-field-${segmentCounter}">
                        <div class="amadex-travellers-trigger amadex-segment-travellers-trigger">
                            <div class="amadex-travellers-value">
                                <span id="travellers-display-${segmentCounter}">1 Traveler</span>
                                <i class="fa-solid fa-angle-down"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Remove Button -->
                    <div class="amadex-modern-field amadex-remove-field">
                        <div class="amadex-remove-inner">
                            <button type="button" class="amadex-remove-segment-btn" data-segment="${segmentCounter}">
                               <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10">
  <g id="Group_98" data-name="Group 98" transform="translate(0 0.001)">
    <path id="Path_11" data-name="Path 11" d="M6.65,5,9.744,1.906a.875.875,0,0,0,0-1.237L9.331.256a.875.875,0,0,0-1.237,0L5,3.35,1.907.255a.875.875,0,0,0-1.237,0L.256.668a.875.875,0,0,0,0,1.237L3.351,5,.257,8.093a.875.875,0,0,0,0,1.237l.412.412a.875.875,0,0,0,1.237,0L5,6.649,8.094,9.743a.875.875,0,0,0,1.237,0l.412-.412a.875.875,0,0,0,0-1.237Zm0,0"/>
  </g>
</svg>
                                Remove
                            </button>
                        </div>
                    </div>
                    
                </div>
            </div>
        `;
        
        // Insert before Add City button
        // $('#add-city-btn').before(newSegment);
        if (!$('#add-city-btn').closest('.vsb-wrap').length) {
            $('#add-city-btn').before(newSegment);
        }
        // Also inject a VSB-styled segment row after the vsb-card
const $vsbWrap = $('.vsb-wrap');
if ($vsbWrap.length) {
    const vsbSegment = `
        <div class="vsb-extra-segment amadex-flight-segment" data-segment="${segmentCounter}">
            <div class="vsb-card vsb-segment-card">
                <div class="vsb-fields">
                    <div class="visit-location">
                        <div class="vsb-field vsb-field--origin">
                            <span class="vsb-field__label">Origin</span>
                            <input type="text" class="vsb-field__value amadex-field-value"
                                id="modern-origin-${segmentCounter}"
                                placeholder="Departure City" autocomplete="off">
                            <input type="hidden" id="modern-origin-code-${segmentCounter}">
                            <div class="amadex-suggestions-dropdown" id="origin-suggestions-${segmentCounter}"></div>
                        </div>
                        <button type="button" class="amadex-swap-button amadex-segment-swap" data-segment="${segmentCounter}" aria-label="Swap">
                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="48" height="48" viewBox="0 0 48 48">
                                <g transform="translate(-651 -632)">
                                    <circle cx="15" cy="15" r="15" transform="translate(660 641)" fill="#0e7d3f"/>
                                    <g transform="translate(666 646.004)">
                                        <path d="M4.841,15.754a.768.768,0,0,1-.545-.223L1.223,12.459a.773.773,0,0,1,.545-1.314H17.133a.768.768,0,0,1,0,1.536H3.62l1.767,1.759a.768.768,0,0,1-.545,1.314ZM17.133,9.609H1.768a.768.768,0,1,1,0-1.536H15.282L13.515,6.313a.771.771,0,1,1,1.091-1.091l3.073,3.073a.773.773,0,0,1-.545,1.314Z" fill="#fff"/>
                                    </g>
                                </g>
                            </svg>
                        </button>
                        <div class="vsb-field vsb-field--destination">
                            <span class="vsb-field__label">Destination</span>
                            <input type="text" class="vsb-field__value amadex-field-value"
                                id="modern-destination-${segmentCounter}"
                                placeholder="Arrival City" autocomplete="off">
                            <input type="hidden" id="modern-destination-code-${segmentCounter}">
                            <div class="amadex-suggestions-dropdown" id="destination-suggestions-${segmentCounter}"></div>
                        </div>
                    </div>
                    <div class="visit-date">
                        <div class="vsb-field vsb-field--departure" id="departure-field-${segmentCounter}">
                            <span class="vsb-field__label">Departure Date</span>
                            <div class="vsb-field__value" id="departure-display-${segmentCounter}">Select date</div>
                            <span class="vsb-field__sub" id="departure-day-${segmentCounter}"></span>
                            <input type="date" id="modern-departure-${segmentCounter}">
                        </div>
                    </div>
                    <div class="vsb-segment-remove">
                        <button type="button" class="amadex-remove-segment-btn vsb-remove-btn" data-segment="${segmentCounter}">
                            ✕ Remove
                        </button>
                    </div>
                </div>
            </div>
        </div>`;

    // Insert before the VSB Add City button, or append to vsb-wrap
    const $vsbAddBtn = $vsbWrap.find('.vsb-add-city-btn');
    if ($vsbAddBtn.length) {
        $vsbAddBtn.before(vsbSegment);
    } else {
        $vsbWrap.append(vsbSegment);
    }

    $(`.vsb-extra-segment[data-segment="${segmentCounter}"]`).hide().slideDown(300);
}
        // Initialize autocomplete and date bindings for new segment
        initLocationAutocompleteForSegment(currentSegment);
        bindSegmentCalendar(currentSegment);
        bindDynamicDepartureDate(currentSegment);
        setMinimumDatesForSegment(currentSegment);
        segmentDepartureDates[currentSegment] = null;

        // Reset the departure display for new segment to placeholder
        $(`#departure-display-${currentSegment}`).text('Select date').css('color', '#aaa');
        $(`#departure-day-${currentSegment}`).text('').css('color', '#aaa');
        $(`#modern-departure-${currentSegment}`).val('');
        
        // Add smooth animation
        $(`.amadex-flight-segment[data-segment="${segmentCounter}"]`).hide().slideDown(300);
        updateMultiSegmentState();
        updateSegmentRemoveButtons();
    }
    
    /**
     * Remove flight segment
     */
function removeFlightSegment(segmentNumber) {
    // Remove both old-style and VSB-style segment rows
    const $segment = $(`.amadex-flight-segment[data-segment="${segmentNumber}"], .vsb-extra-segment[data-segment="${segmentNumber}"]`);
    
    $segment.slideUp(300, function() {
        $(this).remove();
        delete segmentDepartureDates[segmentNumber];
        updateMultiSegmentState();
        updateSegmentRemoveButtons();
    });
}
    
    /**
     * Initialize location autocomplete for a specific segment
     */
    function initLocationAutocompleteForSegment(segmentNum) {
        let searchTimeout;
        const originInput = `#modern-origin-${segmentNum}`;
        const originField = `#origin-field-${segmentNum}`;
        const originSuggestions = `#origin-suggestions-${segmentNum}`;
        const originCode = `#modern-origin-code-${segmentNum}`;
        const originDescription = `#origin-description-${segmentNum}`;
        
        $(originInput).on('focus click', function(e) {
            e.stopPropagation();
            if (suppressLocationDropdowns) return;
            $('.amadex-suggestions-dropdown').removeClass('active');
            $('.amadex-location-field').removeClass('field-active');
            $(originField).addClass('field-active');
            showDropdownWithSections(originSuggestions, originInput);
        });
        
        $(originInput).on('input', function() {
            const query = $(this).val();
            clearTimeout(searchTimeout);
            
            if (query.length >= 2) {
                searchTimeout = setTimeout(function() {
                    searchAirports(query, originInput, originCode, originSuggestions, originDescription);
                }, 250);
            } else if (query.length === 0) {
                showDropdownWithSections(originSuggestions, originInput);
            }
        });
        
        const destinationInput = `#modern-destination-${segmentNum}`;
        const destinationField = `#destination-field-${segmentNum}`;
        const destinationSuggestions = `#destination-suggestions-${segmentNum}`;
        const destinationCode = `#modern-destination-code-${segmentNum}`;
        const destinationDescription = `#destination-description-${segmentNum}`;
        
        $(destinationInput).on('focus click', function(e) {
            e.stopPropagation();
            if (suppressLocationDropdowns) return;
            $('.amadex-suggestions-dropdown').removeClass('active');
            $('.amadex-location-field').removeClass('field-active');
            $(destinationField).addClass('field-active');
            showDropdownWithSections(destinationSuggestions, destinationInput);
        });
        
        $(destinationInput).on('input', function() {
            const query = $(this).val();
            clearTimeout(searchTimeout);
            
            if (query.length >= 2) {
                searchTimeout = setTimeout(function() {
                    searchAirports(query, destinationInput, destinationCode, destinationSuggestions, destinationDescription);
                }, 250);
            } else if (query.length === 0) {
                showDropdownWithSections(destinationSuggestions, destinationInput);
            }
        });
    }
    
    function bindSegmentCalendar(segmentId) {
        const $field = $(`#departure-field-${segmentId}`);
        const selectors = `#departure-display-${segmentId}`;
        
        $(selectors).off('click').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            $('#modern-origin, #modern-destination').blur();
            $('.amadex-suggestions-dropdown').removeClass('active');
            $('.amadex-location-field').removeClass('field-active');
            $('.amadex-date-field').removeClass('field-active');
            
            $field.addClass('field-active');
            currentCalendarField = 'segment';
            currentSegmentId = segmentId;
            
            const existing = segmentDepartureDates[segmentId] || null;
            showCustomCalendar(existing);
        });
    }
    
    /**
     * Show/hide Remove buttons — only show when 2+ extra segments exist
     * Segment 1 (main bar) always hides its remove btn via CSS
     */
function updateSegmentRemoveButtons() {
    // Count unique segment numbers (not DOM elements) to avoid double-counting
    // since each segment creates both .amadex-flight-segment and .vsb-extra-segment
    var segmentNumbers = [];
    $('.amadex-flight-segment, .vsb-extra-segment').not('[data-segment="1"]').each(function() {
        var seg = $(this).data('segment');
        if (seg && segmentNumbers.indexOf(seg) === -1) {
            segmentNumbers.push(seg);
        }
    });
    var uniqueCount = segmentNumbers.length;

    var $extraSegments = $('.amadex-flight-segment, .vsb-extra-segment').not('[data-segment="1"]');
    if (uniqueCount > 1) {
        // 2+ extra segments — remove button active and fully visible
        $extraSegments.find('.amadex-remove-segment-btn, .vsb-remove-btn')
            .prop('disabled', false)
            .css({ 'opacity': '1', 'pointer-events': 'auto', 'cursor': 'pointer' });
    } else {
        // Only 1 extra segment — show button but disabled/greyed out
        $extraSegments.find('.amadex-remove-segment-btn, .vsb-remove-btn')
            .prop('disabled', true)
            .css({ 'opacity': '0.35', 'pointer-events': 'none', 'cursor': 'not-allowed' });
    }
}

    /**
     * Update return field & button states based on segment count
     */
    function updateMultiSegmentState() {
        const tripType = $('input[name="tripType"]:checked').val();
        const $form = $('.amadex-modern-form');
        const $addCityBtn = $('#add-city-btn');
        const totalSegments = $('.amadex-flight-segment').length;
        const hasExtraSegments = totalSegments > 1;
        
        if (tripType !== 'multi-city') {
            $form.removeClass('has-extra-segments');
            restoreReturnFieldDisplay();
            return;
        }
        
        // Keep return field same as one-way — disabled, show actual date not promo text
        $('#return-field').addClass('field-disabled subtle-disabled');
        $('#return-field').removeClass('return-promo');
        $('#return-day').hide();
        // Restore actual return date text (same as one-way behaviour)
        restoreReturnFieldDisplay();
        
        if (hasExtraSegments) {
            $form.addClass('has-extra-segments');
        } else {
            $form.removeClass('has-extra-segments');
        }
        
        if (totalSegments >= MAX_MULTI_SEGMENTS) {
            $addCityBtn.prop('disabled', true).addClass('disabled');
        } else {
            $addCityBtn.prop('disabled', false).removeClass('disabled');
        }
    }
    
    /**
     * Restore return field text/day from selected date
     */
    function restoreReturnFieldDisplay() {
        if (selectedReturnDate instanceof Date && !isNaN(selectedReturnDate)) {
            updateDateDisplay(selectedReturnDate, '#return-display', '#return-day');
            return;
        }
        
        const storedValue = $('#modern-return').val();
        if (storedValue) {
            const parsed = new Date(storedValue);
            if (!isNaN(parsed)) {
                updateDateDisplay(parsed, '#return-display', '#return-day');
                return;
            }
        }

        // No return date selected — show placeholder
        $('#return-display').text('Select date').css('color', '#aaa');
        $('#return-day').text('Select return date').css('color', '#aaa');
    }
    
    /**
     * Collect multi-city segments (visible rows only)
     */
    function collectMultiSegments(onlyVisible = true) {
    const segments = [];

    // Segment 1 always comes from the main VSB bar or old form
    const seg1Origin = $('#modern-origin-code').val() || $('#modern-origin').val();
    const seg1Dest = $('#modern-destination-code').val() || $('#modern-destination').val();
    // const seg1Dep = $('#modern-departure').val();
    const seg1Dep = $('#modern-departure').val() || $('#vsb-departure-date').val();

    if (seg1Origin || seg1Dest) {
        segments.push({
            origin: seg1Origin,
            origin_name: $('#modern-origin').val(),
            destination: seg1Dest,
            destination_name: $('#modern-destination').val(),
            departure: seg1Dep
        });
    }

    // Collect extra segments (segment 2, 3, 4...) from either VSB extra rows or old segments
    const seenSegments = [1]; // already handled segment 1

    // Check VSB extra segments first
    $('.vsb-extra-segment').each(function() {
        const $seg = $(this);
        if (onlyVisible && !$seg.is(':visible')) return;

        const segId = $seg.data('segment');
        if (!segId || seenSegments.includes(segId)) return;
        seenSegments.push(segId);

        segments.push({
            origin: $(`#modern-origin-code-${segId}`).val() || $(`#modern-origin-${segId}`).val(),
            origin_name: $(`#modern-origin-${segId}`).val(),
            destination: $(`#modern-destination-code-${segId}`).val() || $(`#modern-destination-${segId}`).val(),
            destination_name: $(`#modern-destination-${segId}`).val(),
            departure: $(`#modern-departure-${segId}`).val()
        });
    });

    // Also check old-style .amadex-flight-segment rows (fallback)
    $('.amadex-flight-segment').each(function() {
        const $segment = $(this);
        if (onlyVisible && !$segment.is(':visible')) return;

        const segmentId = $segment.data('segment');
        if (!segmentId || segmentId === 1 || seenSegments.includes(segmentId)) return;
        seenSegments.push(segmentId);

        const originInput = $(`#modern-origin-${segmentId}`);
        const destinationInput = $(`#modern-destination-${segmentId}`);
        if (!originInput.length || !destinationInput.length) return;

        segments.push({
            origin: $(`#modern-origin-code-${segmentId}`).val() || originInput.val(),
            origin_name: originInput.val(),
            destination: $(`#modern-destination-code-${segmentId}`).val() || destinationInput.val(),
            destination_name: destinationInput.val(),
            departure: $(`#modern-departure-${segmentId}`).val()
        });
    });

    return segments;
}
    
    /**
     * Close calendar widget function - Must be at module level for accessibility
     */
    function closeLocationDropdowns() {
        $(".amadex-suggestions-dropdown").removeClass("active");
        $(".amadex-location-field").removeClass("field-active");
        $("#modern-origin, #modern-destination").blur();
    }

    function closeCalendarWidget() {
        const $calendar = $('#departure-calendar');
        $calendar.removeClass('active');
        $calendar.css({
            'display': 'none',
            'visibility': 'hidden',
            'opacity': '0',
            'top': '',
            'left': '',
            'right': '',
            'position': ''
        });
        $('.amadex-date-field').removeClass('field-active');
        currentSegmentId = null;
        currentCalendarField = null;
        suppressLocationDropdowns = false; // Re-enable location dropdowns
        
        // Restore body scroll on mobile and remove backdrop
        if ($(window).width() <= 768) {
            $('body').css('overflow', '');
            $('.amadex-calendar-backdrop').fadeOut(200, function() {
                $(this).remove();
            });
        }
    }
    
    /**
     * Initialize date fields with custom calendar
     */
   function initDateFields() {

        
        // Create calendar widget HTML (only once)
        if ($('#departure-calendar').length === 0) {
            const calendarHtml = `
                <div class="amadex-calendar-widget" id="departure-calendar">
                    <div class="amadex-calendar-header">
                        <div class="amadex-calendar-selected-date">
                            <span class="amadex-calendar-date-item active" id="calendar-departure-text">5 Nov 25</span>
                            <span class="amadex-calendar-date-item" id="calendar-return-text">10 Nov 25</span>
                        </div>
                        <div class="amadex-calendar-trip-type" id="calendar-trip-type">Book Round Trip</div>
                        <button type="button" class="amadex-calendar-close-btn" aria-label="Close calendar">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M15 5L5 15M5 5L15 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                    <div class="amadex-calendar-months" id="calendar-months-container">
                        <!-- Calendar will be rendered here -->
                    </div>
                </div>
            `;
            
            // Append to the form container
            $('.amadex-modern-form').append(calendarHtml);

        }
        
        // Click display to show custom calendar
        $('#departure-display, #departure-day').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Departure field clicked');
            currentCalendarField = 'departure';
            
            // Suppress location dropdowns from reopening while calendar is open
            suppressLocationDropdowns = true;
            $('.amadex-suggestions-dropdown').removeClass('active');
            $('.amadex-location-field').removeClass('field-active');
            $('.amadex-date-field').removeClass('field-active');
            setTimeout(function() { suppressLocationDropdowns = false; }, 400);
            
            // Add active state to departure field
            $('#departure-display').closest('.amadex-date-field').addClass('field-active');
            
            showCustomCalendar(selectedDepartureDate);
        });
        
        $('#return-display, #return-day').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const tripType = $('input[name="tripType"]:checked').val();

            // Block for one-way and multi-city
            if (tripType === 'oneway' || tripType === 'multi-city') {
                return;
            }

            // If field still has disabled class (e.g. CSS residual), force-enable and continue
            const $retField = $('#return-field, .vsb-field--return');
            if ($retField.hasClass('field-disabled')) {
                $retField.removeClass('field-disabled subtle-disabled return-promo');
                $retField.css('pointer-events', 'auto');
                $retField.find('*').css('pointer-events', 'auto');
            }
            console.log('Return field clicked');
            currentCalendarField = 'return';
            
            // Suppress location dropdowns from reopening while calendar is open
            suppressLocationDropdowns = true;
            $('.amadex-suggestions-dropdown').removeClass('active');
            $('.amadex-location-field').removeClass('field-active');
            $('.amadex-date-field').removeClass('field-active');
            setTimeout(function() { suppressLocationDropdowns = false; }, 400);
            
            // Add active state to return field
            $('#return-display').closest('.amadex-date-field').addClass('field-active');
            
            showCustomCalendar(selectedReturnDate);
        });
        
        // Close calendar when clicking close button
        $(document).on('click', '.amadex-calendar-close-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeCalendarWidget();
        });
        
        // Close calendar when clicking outside or on backdrop
        $(document).on('click', function(e) {
            // Don't close if clicking on calendar widget (except close button) or date field
            if ($(e.target).closest('.amadex-calendar-widget').length && !$(e.target).closest('.amadex-calendar-close-btn').length) {
                return;
            }
            if ($(e.target).closest('.amadex-date-field').length) {
                return;
            }
            
            // Close calendar if it's active
            if ($('#departure-calendar').hasClass('active')) {
                closeCalendarWidget();
            }
        });
        
        // Close calendar when clicking on backdrop
        $(document).on('click', '.amadex-calendar-backdrop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeCalendarWidget();
        });
        
        // Prevent calendar from closing when clicking inside (but allow close button)
        $('#departure-calendar').on('click', function(e) {
            // Allow close button clicks to propagate
            if ($(e.target).closest('.amadex-calendar-close-btn').length) {
                return;
            }
            e.stopPropagation();
        });
        
        // Set initial departure date (7 days from now)
        // Return date is NOT auto-set — user must select it manually
        const today = new Date();
        const departDate = new Date();
        departDate.setDate(today.getDate() + 7);
        
        selectedDepartureDate = departDate;
        selectedReturnDate = null; // No auto return date
        
        $('#modern-departure').val(formatDateForInput(departDate));
        updateDateDisplay(departDate, '#departure-display', '#departure-day');
        
        // Return date — show placeholder text, no value
        $('#modern-return').val('');
        $('#return-display').text('Select date').css('color', '#aaa');
        $('#return-day').text('Select return date').css('color', '#aaa');
    }
    
    /**
     * Show custom calendar widget
     */
    function showCustomCalendar(selectedDate) {
        console.log('showCustomCalendar called', {
            selectedDate: selectedDate,
            currentField: currentCalendarField
        });
        
        const $calendar = $('#departure-calendar');
        
        if ($calendar.length === 0) {
            console.error('Calendar element not found!');
            return;
        }
        
        const tripType = $('input[name="tripType"]:checked').val();
        const isRoundTrip = tripType === 'round';
        const isSegmentMode = currentCalendarField === 'segment';
        
        if (isSegmentMode) {
            if (selectedDate) {
                $('#calendar-departure-text').text(formatDateForCalendarHeader(selectedDate));
            } else {
                $('#calendar-departure-text').text('Select Date');
            }
            $('#calendar-return-text').hide();
            $('#calendar-trip-type').text('Select Departure Date');
            $('#calendar-departure-text').addClass('active');
            $('#calendar-return-text').removeClass('active');
        } else {
            // Update header with both dates (like screenshot)
            if (selectedDepartureDate) {
                const departFormatted = formatDateForCalendarHeader(selectedDepartureDate);
                $('#calendar-departure-text').text(departFormatted);
            } else {
                $('#calendar-departure-text').text('Select Date');
            }
            
            if (selectedReturnDate && isRoundTrip) {
                const returnFormatted = formatDateForCalendarHeader(selectedReturnDate);
                $('#calendar-return-text').text(returnFormatted).show();
            } else if (isRoundTrip) {
                $('#calendar-return-text').text('Select Return').show();
            } else {
                $('#calendar-return-text').hide();
            }
            
            // Highlight active date being selected
            if (currentCalendarField === 'departure') {
                $('#calendar-departure-text').addClass('active');
                $('#calendar-return-text').removeClass('active');
            } else {
                $('#calendar-departure-text').removeClass('active');
                $('#calendar-return-text').addClass('active');
            }
            
            $('#calendar-trip-type').text(isRoundTrip ? 'Book Round Trip' : 'Book One Way');
        }
        
        // Render two months
        renderCalendarMonths();

        // For segment mode: position calendar below the segment's departure field
        if (isSegmentMode && currentSegmentId) {
            const $anchorField = $(`#departure-field-${currentSegmentId}`);
            if ($anchorField.length) {
                const fieldOffset = $anchorField.offset();
                const fieldHeight = $anchorField.outerHeight();
                const formOffset = $('.amadex-modern-form').offset();

                // Position relative to the form (calendar uses position:absolute inside form)
                const topPos = (fieldOffset.top - formOffset.top) + fieldHeight + 8;
                const leftPos = fieldOffset.left - formOffset.left;

                $calendar.css({
                    position: 'absolute',
                    top: topPos + 'px',
                    left: leftPos + 'px',
                    right: 'auto'
                });
            }
        } else {
            // Reset to CSS-controlled position for main bar fields
            $calendar.css({ top: '', left: '', right: '', position: '' });
        }

        // Show calendar with slight delay for smooth animation
        setTimeout(function() {
            $calendar.addClass('active');
            
            // Force display on mobile devices
            if ($(window).width() <= 768) {
                $calendar.css({
                    'display': 'block',
                    'visibility': 'visible',
                    'opacity': '1'
                });
                
                $('body').css('overflow', 'hidden');
                
                // Add backdrop overlay if it doesn't exist
                if ($('.amadex-calendar-backdrop').length === 0) {
                    $('<div class="amadex-calendar-backdrop"></div>').insertBefore($calendar);
                }
                $('.amadex-calendar-backdrop').css({
                    'display': 'block',
                    'opacity': '0'
                }).fadeTo(200, 0.5);
                
                console.log('Mobile calendar activated', {
                    hasActive: $calendar.hasClass('active'),
                    display: $calendar.css('display'),
                    visibility: $calendar.css('visibility'),
                    opacity: $calendar.css('opacity'),
                    zIndex: $calendar.css('z-index')
                });
            } else {
                console.log('Desktop calendar activated');
            }
        }, 10);
    }
    
    /**
     * Render calendar months with proper navigation - Single month view
     */
    function renderCalendarMonths() {
        // Show only one month at a time
        let html = '';
        html += renderMonth(currentMonth, 0);
        
        $('#calendar-months-container').html(html);
        
        // Bind day click events
        $('.amadex-calendar-day').on('click', function() {
            if ($(this).hasClass('disabled')) return;
            
            const year = parseInt($(this).data('year'));
            const month = parseInt($(this).data('month'));
            const day = parseInt($(this).data('day'));
            
            const selectedDate = new Date(year, month, day);
            
            // Validate date selection
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (selectedDate < today) {
                console.log('Cannot select past date');
                return;
            }
            
            if (currentCalendarField === 'departure') {
                selectedDepartureDate = selectedDate;
                $('#modern-departure').val(formatDateForInput(selectedDate));
                updateDateDisplay(selectedDate, '#departure-display', '#departure-day');
                
                // Update header to show selected date
                const formatted = formatDateForCalendarHeader(selectedDate);
                $('#calendar-departure-text').text(formatted);
                
                // If round trip and return date not set or is before new departure — auto open return calendar
                const tripType = $('input[name="tripType"]:checked').val();
                if (tripType === 'round') {
                    // Reset return date if it's before or same as departure
                    if (!selectedReturnDate || selectedReturnDate <= selectedDate) {
                        selectedReturnDate = null;
                        $('#modern-return').val('');
                        $('#return-display').text('Select date').css('color', '#aaa');
                        $('#return-day').text('Select return date').css('color', '#aaa');
                    }
                    // Auto-switch to return date selection
                    closeCalendarWidget();
                    setTimeout(function() {
                        currentCalendarField = 'return';
                        $('#return-display').closest('.amadex-date-field').addClass('field-active');
                        showCustomCalendar(selectedReturnDate);
                    }, 150);
                } else {
                    closeCalendarWidget();
                }
            } else if (currentCalendarField === 'segment') {
                if (!currentSegmentId) {
                    return;
                }

                // Find the previous segment's departure date
                // Segments are numbered — get all segment IDs less than current
                var allSegmentIds = [];
                $('.amadex-flight-segment, .vsb-extra-segment').each(function() {
                    var sid = parseInt($(this).data('segment'));
                    if (sid && !allSegmentIds.includes(sid)) allSegmentIds.push(sid);
                });
                allSegmentIds.sort(function(a, b) { return a - b; });

                var currentIdx = allSegmentIds.indexOf(parseInt(currentSegmentId));
                var minAllowedDate = null;

                if (currentIdx > 0) {
                    // Get previous segment's date
                    var prevSegId = allSegmentIds[currentIdx - 1];
                    if (prevSegId === 1) {
                        // Previous is main bar — use selectedDepartureDate
                        minAllowedDate = selectedDepartureDate;
                    } else {
                        minAllowedDate = segmentDepartureDates[prevSegId] || selectedDepartureDate;
                    }
                } else {
                    // First extra segment — must be after main departure
                    minAllowedDate = selectedDepartureDate;
                }

                // Validate — selected date must be AFTER previous segment date
                if (minAllowedDate && selectedDate <= minAllowedDate) {
                    // Show error message briefly
                    var $depField = $(`#departure-field-${currentSegmentId}`);
                    $depField.addClass('amadex-field-error');
                    $depField.find('.amadex-field-input-wrap, .vsb-field__value').first()
                        .append('<span class="amadex-field-error-msg" style="white-space:nowrap;">⚠ Must be after previous departure date</span>');
                    setTimeout(function() {
                        $depField.removeClass('amadex-field-error');
                        $depField.find('.amadex-field-error-msg').remove();
                    }, 3000);
                    return; // Don't select this date
                }

                segmentDepartureDates[currentSegmentId] = selectedDate;
                $(`#modern-departure-${currentSegmentId}`).val(formatDateForInput(selectedDate));
                updateDateDisplay(selectedDate, `#departure-display-${currentSegmentId}`, `#departure-day-${currentSegmentId}`);
                // Restore normal color after date selected
                $(`#departure-display-${currentSegmentId}, #departure-day-${currentSegmentId}`).css('color', '');
                
                // Close calendar immediately after selection
                closeCalendarWidget();
            } else {
                // Block return date selection if departure not selected yet
                if (!selectedDepartureDate || isNaN(selectedDepartureDate)) {
                    closeCalendarWidget();
                    // Auto-open departure calendar instead
                    setTimeout(function() {
                        currentCalendarField = 'departure';
                        $('#departure-display').closest('.amadex-date-field').addClass('field-active');
                        showCustomCalendar(null);
                        // Show a brief tooltip on the departure field
                        var $dep = $('#departure-field, .vsb-field--departure');
                        $dep.addClass('amadex-field-error');
                        $dep.find('.amadex-field-input-wrap, .vsb-field__value').first()
                            .append('<span class="amadex-field-error-msg" style="white-space:nowrap;">⚠ Please select departure date first</span>');
                        setTimeout(function() {
                            $dep.removeClass('amadex-field-error');
                            $dep.find('.amadex-field-error-msg').remove();
                        }, 3000);
                    }, 150);
                    return;
                }

                // Validate return date is after departure
                if (selectedDepartureDate && selectedDate <= selectedDepartureDate) {
                    alert('Return date must be after departure date');
                    return;
                }
                
                selectedReturnDate = selectedDate;
                $('#modern-return').val(formatDateForInput(selectedDate));
                updateDateDisplay(selectedDate, '#return-display', '#return-day');
                $('#return-display, #return-day').css('color', ''); // restore normal color
                
                // Update header to show selected date
                const formatted = formatDateForCalendarHeader(selectedDate);
                $('#calendar-return-text').text(formatted);
                
                // Close calendar immediately after selection
                closeCalendarWidget();
            }
        });
        
        // Bind navigation buttons - Single month navigation
        $('.amadex-calendar-nav-btn').on('click', function() {
            const direction = $(this).data('direction');
            
            if (direction === 'prev') {
                // Don't go before current month
                const today = new Date();
                const currentMonthStart = new Date(today.getFullYear(), today.getMonth(), 1);
                const prevMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() - 1, 1);
                
                if (prevMonth >= currentMonthStart) {
                    currentMonth = prevMonth;
                    renderCalendarMonths();
                }
            } else if (direction === 'next') {
                // Can navigate forward up to 12 months
                const today = new Date();
                const maxMonth = new Date(today.getFullYear() + 1, today.getMonth(), 1);
                const nextMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 1);
                
                if (nextMonth <= maxMonth) {
                    currentMonth = nextMonth;
                    renderCalendarMonths();
                }
            }
        });
    }
    
    /**
     * Render single month with proper validation
     */
    function renderMonth(date, monthIndex) {
        const year = date.getFullYear();
        const month = date.getMonth();
        const monthName = date.toLocaleDateString('en-US', { month: 'long' });
        
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        // Adjust to start week on Monday (0=Sunday, 1=Monday, etc.)
        let startingDayOfWeek = firstDay.getDay();
        startingDayOfWeek = startingDayOfWeek === 0 ? 6 : startingDayOfWeek - 1; // Convert Sunday (0) to 6, others shift by 1
        const totalDays = lastDay.getDate();
        
        // Determine navigation limits
        const today = new Date();
        const currentMonthStart = new Date(today.getFullYear(), today.getMonth(), 1);
        const prevMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() - 1, 1);
        const nextMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 1);
        const maxMonth = new Date(today.getFullYear() + 1, today.getMonth(), 1);
        
        const canGoPrev = prevMonth >= currentMonthStart;
        const canGoNext = nextMonth <= maxMonth;
        
        let html = `
            <div class="amadex-calendar-month">
                <div class="amadex-calendar-month-header">
                    <button type="button" class="amadex-calendar-nav-btn" data-direction="prev" ${!canGoPrev ? 'disabled' : ''}>‹</button>
                    <div class="amadex-calendar-month-title">${monthName} ${year}</div>
                    <button type="button" class="amadex-calendar-nav-btn" data-direction="next" ${!canGoNext ? 'disabled' : ''}>›</button>
                </div>
                <table class="amadex-calendar-table">
                    <thead>
                        <tr>
                            <th>Mo</th>
                            <th>Tu</th>
                            <th>We</th>
                            <th>Th</th>
                            <th>Fr</th>
                            <th>Sa</th>
                            <th>Su</th>
                        </tr>
                    </thead>
                    <tbody>`;
        
        // Generate calendar days
        let dayCount = 1;
        let rows = Math.ceil((startingDayOfWeek + totalDays) / 7);
        
        for (let row = 0; row < rows; row++) {
            html += '<tr>';
            
            for (let col = 0; col < 7; col++) {
                if (row === 0 && col < startingDayOfWeek) {
                    html += '<td></td>';
                } else if (dayCount > totalDays) {
                    html += '<td></td>';
                } else {
                    const currentDate = new Date(year, month, dayCount);
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    currentDate.setHours(0, 0, 0, 0);
                    
                    let classes = ['amadex-calendar-day'];
                    
                    // Check if disabled (past dates)
                    if (currentDate < today) {
                        classes.push('disabled');
                    }
                    
                    // Additional validation for return date
                    if (currentCalendarField === 'return') {
                        if (!selectedDepartureDate || isNaN(selectedDepartureDate)) {
                            // No departure selected — disable ALL dates
                            classes.push('disabled');
                        } else if (currentDate <= selectedDepartureDate) {
                            // Disable dates before/on departure date
                            classes.push('disabled');
                        }
                    }

                    // Segment date validation — disable dates on/before previous segment date
                    if (currentCalendarField === 'segment' && currentSegmentId) {
                        var minSegDate = null;

                        // Get all segment IDs sorted
                        var segIds = [];
                        $('.amadex-flight-segment, .vsb-extra-segment').each(function() {
                            var sid = parseInt($(this).data('segment'));
                            if (sid && segIds.indexOf(sid) === -1) segIds.push(sid);
                        });
                        segIds.sort(function(a, b) { return a - b; });

                        var curIdx = segIds.indexOf(parseInt(currentSegmentId));
                        if (curIdx > 0) {
                            var prevId = segIds[curIdx - 1];
                            minSegDate = prevId === 1
                                ? selectedDepartureDate
                                : (segmentDepartureDates[prevId] || selectedDepartureDate);
                        } else {
                            minSegDate = selectedDepartureDate;
                        }

                        if (minSegDate && currentDate <= minSegDate) {
                            classes.push('disabled');
                        }
                    }
                    
                    // Check if today
                    if (currentDate.toDateString() === today.toDateString()) {
                        classes.push('today');
                    }
                    
                    // Check if selected (both departure and return should be highlighted)
                    if (selectedDepartureDate && currentDate.toDateString() === selectedDepartureDate.toDateString()) {
                        classes.push('selected');
                    }
                    
                    if (selectedReturnDate && currentDate.toDateString() === selectedReturnDate.toDateString()) {
                        classes.push('selected');
                    }
                    
                    // Check if in range (for round trip)
                    if (selectedDepartureDate && selectedReturnDate) {
                        if (currentDate > selectedDepartureDate && currentDate < selectedReturnDate) {
                            classes.push('in-range');
                        }
                    }
                    
                    html += `<td><span class="${classes.join(' ')}" data-year="${year}" data-month="${month}" data-day="${dayCount}">${dayCount}</span></td>`;
                    dayCount++;
                }
            }
            
            html += '</tr>';
        }
        
        html += `
                    </tbody>
                </table>
            </div>`;
        
        return html;
    }
    
    /**
     * Format date for calendar header
     */
    function formatDateForCalendarHeader(date) {
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear().toString().substr(2)}`;
    }

    /**
     * Update date display
     */
    function updateDateDisplay(date, displaySelector, daySelector) {
        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        const dayName = days[date.getDay()];
        const formatted = `${date.getDate()} ${months[date.getMonth()]}, ${date.getFullYear().toString().substr(2)}`;
        
        $(displaySelector).text(formatted);
        if (daySelector) $(daySelector).text(dayName);
    }

    /**
     * Format date for input
     */
    function formatDateForInput(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    /**
     * Set minimum dates
     */
    function setMinimumDates() {
        const today = new Date();
        const tomorrow = new Date(today);
        tomorrow.setDate(today.getDate() + 1);
        
        $('#modern-departure').attr('min', formatDateForInput(today));
        $('#modern-return').attr('min', formatDateForInput(tomorrow));
    }
    
    function setMinimumDatesForSegment(segmentNum) {
        const today = new Date();
        $(`#modern-departure-${segmentNum}`).attr('min', formatDateForInput(today));
    }
    
    function bindDynamicDepartureDate(segmentNum) {
        const inputSelector = `#modern-departure-${segmentNum}`;
        const displaySelector = `#departure-display-${segmentNum}`;
        const daySelector = `#departure-day-${segmentNum}`;
        
        $(inputSelector).on('change', function() {
            const value = $(this).val();
            if (!value) return;
            
            const parsed = new Date(value);
            if (!isNaN(parsed)) {
                updateDateDisplay(parsed, displaySelector, daySelector);
            }
        });
    }

    /**
     * Initialize location autocomplete
     */
    function initLocationAutocomplete() {
        let searchTimeout;
        let allAirports = [];
        let currentField = null;
        
        // Blur timeout IDs — stored so focus/click can cancel them before they fire
        let originBlurTimer = null;
        let destinationBlurTimer = null;

        // Origin field - show dropdown on click and add active state
        $(document).on('focus click', '#modern-origin', function(e) {
            e.stopPropagation();
            clearTimeout(originBlurTimer); // cancel pending blur-close
            if (suppressLocationDropdowns) return; // blocked by calendar/travellers open
            currentField = 'origin';
            
            // Close destination dropdown and remove its active state
            clearTimeout(destinationBlurTimer);
            $('#destination-suggestions').removeClass('active');
            $('#destination-field').removeClass('field-active');
            
            // Open origin dropdown and add active state
            $('#origin-field').addClass('field-active');
            showDropdownWithSections('#origin-suggestions', '#modern-origin');
        });
        
        // Destination field - show dropdown on click and add active state
        $(document).on('focus click', '#modern-destination', function(e) {
            e.stopPropagation();
            clearTimeout(destinationBlurTimer); // cancel pending blur-close
            if (suppressLocationDropdowns) return; // blocked by calendar/travellers open
            currentField = 'destination';
            
            // Close origin dropdown and remove its active state
            clearTimeout(originBlurTimer);
            $('#origin-suggestions').removeClass('active');
            $('#origin-field').removeClass('field-active');
            
            // Open destination dropdown and add active state
            $('#destination-field').addClass('field-active');
            showDropdownWithSections('#destination-suggestions', '#modern-destination');
        });

        // Blur handlers: close dropdown when input loses focus (delay allows suggestion clicks)
        $(document).on('blur', '#modern-origin', function() {
            originBlurTimer = setTimeout(function() {
                $('#origin-suggestions').removeClass('active');
                $('#origin-field').removeClass('field-active');
            }, 200);
        });

        $(document).on('blur', '#modern-destination', function() {
            destinationBlurTimer = setTimeout(function() {
                $('#destination-suggestions').removeClass('active');
                $('#destination-field').removeClass('field-active');
            }, 200);
        });

        // Origin autocomplete on typing in main input
        $(document).on('input', '#modern-origin', function() {
            const keyword = $(this).val();
            clearTimeout(searchTimeout);
            
            if (keyword.length >= 2) {
                searchTimeout = setTimeout(function() {
                    searchAirports(keyword, '#modern-origin', '#modern-origin-code', '#origin-suggestions', '#origin-description');
                }, 300);
            } else if (keyword.length === 0) {
                showDropdownWithSections('#origin-suggestions', '#modern-origin');
            }
        });
        
        // Destination autocomplete on typing in main input
        $(document).on('input', '#modern-destination', function() {
            $('#destination-field, .vsb-field--destination').removeClass('amadex-field-error');
    $('.amadex-field-error-msg').remove();
            const keyword = $(this).val();
            clearTimeout(searchTimeout);
            
            if (keyword.length >= 2) {
                searchTimeout = setTimeout(function() {
                    searchAirports(keyword, '#modern-destination', '#modern-destination-code', '#destination-suggestions', '#destination-description');
                }, 300);
            } else if (keyword.length === 0) {
                showDropdownWithSections('#destination-suggestions', '#modern-destination');
            }
        });
        
        // Close suggestions when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.amadex-location-field').length) {
                $('.amadex-suggestions-dropdown').removeClass('active');
                $('.amadex-location-field').removeClass('field-active');
            }
        });

        // Re-init autocomplete when results page form becomes visible
$(document).on('amadex:resultsPageReady', function() {
    // Populate form fields from URL on results page
    if ($('#amadex-modern-form-results').length) {
        populateFormFromURL();
    }
});
    }

    /**
     * Show dropdown with Recent Search and Nearby Airport sections
     */
    function showDropdownWithSections(suggestionsSelector, inputSelector) {
        const $dropdown = $(suggestionsSelector);
        
        // Determine placeholder text based on field
        const isOrigin = inputSelector.includes('origin');
        const placeholderText = isOrigin ? 'Origin' : 'Destination';
        
        // Build dropdown structure
        let html = `
         <!--   <div class="amadex-dropdown-search">
                <div style="position: relative;">
                    <span class="amadex-dropdown-search-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="13.998" viewBox="0 0 14 13.998">
  <g id="Group_62" data-name="Group 62" transform="translate(0 -0.035)">
    <path id="Path_7" data-name="Path 7" d="M5.635,11.3a5.621,5.621,0,0,0,3.454-1.187l3.724,3.725a.7.7,0,0,0,.99-.99L10.079,9.122A5.631,5.631,0,1,0,5.635,11.3ZM2.642,2.675a4.232,4.232,0,1,1,0,5.985h0A4.217,4.217,0,0,1,2.62,2.7l.022-.022Z" transform="translate(0 0)" fill="#707070"/>
  </g>
</svg>
                    </span>
                    <input type="text" class="amadex-dropdown-search-input" placeholder="${placeholderText}" data-target="${suggestionsSelector}" data-input="${inputSelector}">
                </div>
            </div> -->
            <div class="amadex-suggestions-scroll">`;
        
        // Get recent searches
        const recentSearches = getRecentSearches();
        
        // Recent Search Section
        if (recentSearches.length > 0) {
            html += `
                <div class="amadex-suggestions-section">
                    <div class="amadex-suggestions-section-title">Recent Search</div>
                </div>`;
            
            recentSearches.slice(0, 3).forEach(function(airport) {
                html += createAirportItem(airport, inputSelector, suggestionsSelector);
            });
        }
        
        // Nearby Airport Section
        const nearbyAirports = getNearbyAirports();
        html += `
            <div class="amadex-suggestions-section">
                <div class="amadex-suggestions-section-title">Nearby Airport</div>
            </div>`;
        
        nearbyAirports.forEach(function(airport) {
            html += createAirportItem(airport, inputSelector, suggestionsSelector);
        });
        
        html += `</div>`;
        
        $dropdown.html(html);
        $dropdown.css('display', '').addClass('active');
        
        // Handle search within dropdown
        $dropdown.find('.amadex-dropdown-search-input').on('input', function() {
            const keyword = $(this).val();
            const targetDropdown = $(this).data('target');
            const targetInput = $(this).data('input');
            
            console.log('Dropdown search input:', keyword, 'for', targetInput);
            
            if (keyword.length >= 2) {
                // Build correct selectors
                const codeSelector = targetInput + '-code';
                const descriptionSelector = targetInput.replace('#modern-', '#') + '-description';
                
                console.log('Calling searchAirports with:', keyword, targetInput, codeSelector, targetDropdown, descriptionSelector);
                searchAirports(keyword, targetInput, codeSelector, targetDropdown, descriptionSelector);
            } else if (keyword.length === 0) {
                showDropdownWithSections(targetDropdown, targetInput);
            }
        });
        
        // Bind click events for airport items
        bindAirportItemClicks($dropdown);
    }
    
    /**
     * Create airport item HTML
     */
    function createAirportItem(airport, inputSelector, suggestionsSelector) {
        const inputId = inputSelector.replace('#modern-', '');
        return `
            <div class="amadex-suggestion-item" 
                 data-city="${airport.city}" 
                 data-code="${airport.code}" 
                 data-name="${airport.name}"
                 data-country="${airport.country}"
                 data-input="${inputSelector}"
                 data-dropdown="${suggestionsSelector}">
                <div class="amadex-suggestion-content">
                    <div class="amadex-suggestion-city">${airport.city}</div>
                    <div class="amadex-suggestion-airport">${airport.name}</div>
                </div>
                <div class="amadex-suggestion-code">${airport.code}</div>
            </div>`;
    }
    
    /**
     * Bind click events for airport items
     */
    // function bindAirportItemClicks($container) {
    //     $container.find('.amadex-suggestion-item').on('click', function() {

    //         const $item = $(this);
    //         const city = $item.data('city');
    //         const code = $item.data('code');
    //         const name = $item.data('name');
    //         const country = $item.data('country');
    //         const inputSelector = $item.data('input');
        
    //         $(inputSelector).val(`${city} (${code})`);
    //         $(inputSelector.replace('#modern-', '#') + '-code').val(code);
    //         $(inputSelector.replace('#modern-', '#') + '-description').text(limitWords(name));
        
    //         saveRecentSearch({ city, code, name, country });
        
    //         // Hide suggestions popup
    //         $item.closest('.amadex-suggestions-scroll').hide();
        
    //     });
    // }
    function bindAirportItemClicks($container) {

        $container.find('.amadex-suggestion-item').on('click', function(e) {
    
            e.preventDefault();
            e.stopPropagation();
    
            const $item = $(this);
            const city = $item.data('city');
            const code = $item.data('code');
            const name = $item.data('name');
            const country = $item.data('country');
            const inputSelector = $item.data('input');
    
            $(inputSelector).val(`${city} (${code})`);
            $(inputSelector.replace('#modern-', '#') + '-code').val(code);
            $(inputSelector.replace('#modern-', '#') + '-description').text(limitWords(name));
    
            if (typeof saveRecentSearch === 'function') {
                saveRecentSearch({ city, code, name, country });
            }
    
            /* HARD CLOSE */
            $('.amadex-suggestions-dropdown').removeClass('active');
            $('.amadex-location-field').removeClass('field-active');
    
            /* PREVENT REOPEN */
            suppressLocationDropdowns = true;
    
            setTimeout(function(){
                suppressLocationDropdowns = false;
            }, 300);
    
        });
    
    }
    
    /**
     * Get recent searches from localStorage
     */
    function getRecentSearches() {
        try {
            const recent = localStorage.getItem('amadex_recent_airports');
            return recent ? JSON.parse(recent) : [];
        } catch (e) {
            return [];
        }
    }
    
    /**
     * Save airport to recent searches
     */
    function saveRecentSearch(airport) {
        try {
            let recent = getRecentSearches();
            
            // Remove if already exists
            recent = recent.filter(item => item.code !== airport.code);
            
            // Add to beginning
            recent.unshift(airport);
            
            // Keep only last 5
            recent = recent.slice(0, 5);
            
            localStorage.setItem('amadex_recent_airports', JSON.stringify(recent));
        } catch (e) {
            console.error('Could not save recent search:', e);
        }
    }
    
    /**
     * Get nearby airports (popular US airports for now)
     */
    function getNearbyAirports() {
        return [
            { city: 'Sacramento', code: 'SMF', name: 'Sacramento International Airport', country: 'USA' },
            { city: 'Washington', code: 'DCA', name: 'Ronald Reagan Washington National Airport', country: 'USA' },
            { city: 'Los Angeles', code: 'LAX', name: 'Los Angeles International Airport', country: 'USA' },
            { city: 'New York', code: 'JFK', name: 'John F. Kennedy International Airport', country: 'USA' },
            { city: 'San Francisco', code: 'SFO', name: 'San Francisco International Airport', country: 'USA' }
        ];
    }

    /**
     * Search airports via Amadeus API
     */
    function searchAirports(keyword, inputSelector, codeSelector, suggestionsSelector, descriptionSelector) {
        console.log('Searching airports for:', keyword);
        
        $.ajax({
            url: typeof AmadexConfig !== 'undefined' ? AmadexConfig.ajaxUrl : '/wp-admin/admin-ajax.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'amadex_search_airports',
                keyword: keyword,
                nonce: typeof AmadexConfig !== 'undefined' ? AmadexConfig.nonce : ''
            },
            beforeSend: function() {
                // Show loading state
                $(suggestionsSelector).css('display', '').addClass('active').html(`
                   <!-- <div class="amadex-dropdown-search">
                        <div style="position: relative;">
                            <span class="amadex-dropdown-search-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="13.998" viewBox="0 0 14 13.998">
                                  <g id="Group_62" data-name="Group 62" transform="translate(0 -0.035)">
                                    <path id="Path_7" data-name="Path 7" d="M5.635,11.3a5.621,5.621,0,0,0,3.454-1.187l3.724,3.725a.7.7,0,0,0,.99-.99L10.079,9.122A5.631,5.631,0,1,0,5.635,11.3ZM2.642,2.675a4.232,4.232,0,1,1,0,5.985h0A4.217,4.217,0,0,1,2.62,2.7l.022-.022Z" transform="translate(0 0)" fill="#707070"/>
                                  </g>
                                </svg>
                            </span>
                            <input type="text" class="amadex-dropdown-search-input" placeholder="${inputSelector.includes('origin') ? 'Origin' : 'Destination'}" value="${keyword}">
                        </div>
                    </div>-->
                    <div class="amadex-suggestions-scroll">
                        <div class="amadex-suggestions-empty" style="padding: 20px; text-align: center; color: #0E7D3F;">
                            <div class="spinner" style="border: 3px solid #f3f3f3; border-top: 3px solid #0E7D3F; border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; margin: 0 auto 10px;"></div>
                            Searching airports...
                        </div>
                    </div>
                    <style>
                        @keyframes spin {
                            0% { transform: rotate(0deg); }
                            100% { transform: rotate(360deg); }
                        }
                    </style>
                `);
            },
            success: function(response) {
                console.log('Airport search response:', response);
                
                if (response.success && response.data && response.data.length > 0) {
                    console.log('Found', response.data.length, 'airports');
                    displayAirportSuggestions(response.data, inputSelector, codeSelector, suggestionsSelector, descriptionSelector);
                } else {
                    console.log('No airports found or empty response');
                    showNoResults(suggestionsSelector, inputSelector, keyword);
                }
            },
            // error: function(xhr, status, error) {
            //     console.error('Airport search error:', error);
            //     console.error('XHR:', xhr);
            //     console.error('Status:', status);
                
            //     showError(suggestionsSelector, inputSelector, 'Error searching airports. Please try again.');
            // }
            
            error: function(xhr, status, error) {
    console.log("RAW RESPONSE:");
    console.log(xhr.responseText);
}
        });
    }

    /**
     * Show no results message
     */
    function showNoResults(suggestionsSelector, inputSelector, keyword) {
        const placeholderText = inputSelector.includes('origin') ? 'Q Origin' : 'Q Destination';
        const $dropdown = $(suggestionsSelector);
        
        $dropdown.css('display', '').addClass('active').html(`
           <!-- <div class="amadex-dropdown-search">
                <div style="position: relative;">
                    <span class="amadex-dropdown-search-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="13.998" viewBox="0 0 14 13.998">
                          <g id="Group_62" data-name="Group 62" transform="translate(0 -0.035)">
                            <path id="Path_7" data-name="Path 7" d="M5.635,11.3a5.621,5.621,0,0,0,3.454-1.187l3.724,3.725a.7.7,0,0,0,.99-.99L10.079,9.122A5.631,5.631,0,1,0,5.635,11.3ZM2.642,2.675a4.232,4.232,0,1,1,0,5.985h0A4.217,4.217,0,0,1,2.62,2.7l.022-.022Z" transform="translate(0 0)" fill="#707070"/>
                          </g>
                        </svg>
                    </span>
                    <input type="text" class="amadex-dropdown-search-input" placeholder="${placeholderText}" value="${keyword}" data-target="${suggestionsSelector}" data-input="${inputSelector}">
                </div>
            </div>-->
            <div class="amadex-suggestions-scroll">
                <div class="amadex-suggestions-empty" style="padding: 30px 20px; text-align: center; color: #64748b;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="2" style="display: block; margin: 0 auto 10px;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <div style="font-weight: 600; margin-bottom: 5px;">No airports found</div>
                    <div style="font-size: 12px;">Try searching by city name or airport code</div>
                </div>
                </div>
            `);
            
        // Re-bind search input
        $dropdown.find('.amadex-dropdown-search-input').on('input', function() {
            const newKeyword = $(this).val();
            if (newKeyword.length >= 2) {
                const codeSelector = inputSelector + '-code';
                const descriptionSelector = inputSelector.replace('#modern-', '#') + '-description';
                searchAirports(newKeyword, inputSelector, codeSelector, suggestionsSelector, descriptionSelector);
            } else if (newKeyword.length === 0) {
                showDropdownWithSections(suggestionsSelector, inputSelector);
            }
        });
    }

    /**
     * Show error message
     */
    function showError(suggestionsSelector, inputSelector, message) {
        const placeholderText = inputSelector.includes('origin') ? 'Q Origin' : 'Q Destination';
        const $dropdown = $(suggestionsSelector);
        
        $dropdown.css('display', '').addClass('active').html(`
            <!--<div class="amadex-dropdown-search">
                <div style="position: relative;">
                    <span class="amadex-dropdown-search-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="13.998" viewBox="0 0 14 13.998">
                          <g id="Group_62" data-name="Group 62" transform="translate(0 -0.035)">
                            <path id="Path_7" data-name="Path 7" d="M5.635,11.3a5.621,5.621,0,0,0,3.454-1.187l3.724,3.725a.7.7,0,0,0,.99-.99L10.079,9.122A5.631,5.631,0,1,0,5.635,11.3ZM2.642,2.675a4.232,4.232,0,1,1,0,5.985h0A4.217,4.217,0,0,1,2.62,2.7l.022-.022Z" transform="translate(0 0)" fill="#707070"/>
                          </g>
                        </svg>
                    </span>
                    <input type="text" class="amadex-dropdown-search-input" placeholder="${placeholderText}" data-target="${suggestionsSelector}" data-input="${inputSelector}">
                </div>
            </div> -->
            <div class="amadex-suggestions-scroll">
                <div class="amadex-suggestions-empty" style="padding: 30px 20px; text-align: center; color: #dc2626;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" style="display: block; margin: 0 auto 10px;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                    <div style="font-weight: 600; margin-bottom: 5px;">Error</div>
                    <div style="font-size: 12px;">${message}</div>
                </div>
            </div>
        `);
        
        // Re-bind search input
        $dropdown.find('.amadex-dropdown-search-input').on('input', function() {
            const newKeyword = $(this).val();
            if (newKeyword.length >= 2) {
                const codeSelector = inputSelector + '-code';
                const descriptionSelector = inputSelector.replace('#modern-', '#') + '-description';
                searchAirports(newKeyword, inputSelector, codeSelector, suggestionsSelector, descriptionSelector);
            } else if (newKeyword.length === 0) {
                showDropdownWithSections(suggestionsSelector, inputSelector);
            }
        });
    }

    /**
     * Display airport suggestions with new design
     */
    function displayAirportSuggestions(airports, inputSelector, codeSelector, suggestionsSelector, descriptionSelector) {
        const $dropdown = $(suggestionsSelector);
        
        if (!airports || airports.length === 0) {
            let html = `
                <!--<div class="amadex-dropdown-search">
                    <div style="position: relative;">
                        <span class="amadex-dropdown-search-icon">
                             <svg xmlns="http://www.w3.org/2000/svg" width="14" height="13.998" viewBox="0 0 14 13.998">
                          <g id="Group_62" data-name="Group 62" transform="translate(0 -0.035)">
                            <path id="Path_7" data-name="Path 7" d="M5.635,11.3a5.621,5.621,0,0,0,3.454-1.187l3.724,3.725a.7.7,0,0,0,.99-.99L10.079,9.122A5.631,5.631,0,1,0,5.635,11.3ZM2.642,2.675a4.232,4.232,0,1,1,0,5.985h0A4.217,4.217,0,0,1,2.62,2.7l.022-.022Z" transform="translate(0 0)" fill="#707070"/>
                          </g>
                        </svg>
                        </span>
                        <input type="text" class="amadex-dropdown-search-input" placeholder="🔍 Search" data-target="${suggestionsSelector}" data-input="${inputSelector}">
                    </div>
                </div> -->
                <div class="amadex-suggestions-scroll">
                    <div class="amadex-suggestions-empty">No airports found</div>
                </div>`;
            $dropdown.html(html);
            $dropdown.css('display', '').addClass('active');
            return;
        }
        
        // Build dropdown structure with search results
        let html = `
            <!--<div class="amadex-dropdown-search">
                <div style="position: relative;">
                    <span class="amadex-dropdown-search-icon">
                         <svg xmlns="http://www.w3.org/2000/svg" width="14" height="13.998" viewBox="0 0 14 13.998">
                          <g id="Group_62" data-name="Group 62" transform="translate(0 -0.035)">
                            <path id="Path_7" data-name="Path 7" d="M5.635,11.3a5.621,5.621,0,0,0,3.454-1.187l3.724,3.725a.7.7,0,0,0,.99-.99L10.079,9.122A5.631,5.631,0,1,0,5.635,11.3ZM2.642,2.675a4.232,4.232,0,1,1,0,5.985h0A4.217,4.217,0,0,1,2.62,2.7l.022-.022Z" transform="translate(0 0)" fill="#707070"/>
                          </g>
                        </svg>
                    </span>
                    <input type="text" class="amadex-dropdown-search-input" placeholder="Search" data-target="${suggestionsSelector}" data-input="${inputSelector}" value="">
                </div>
            </div> -->
            <div class="amadex-suggestions-scroll">
                <div class="amadex-suggestions-section">
                    <div class="amadex-suggestions-section-title">Search Results</div>
                </div>`;
        
        airports.forEach(function(airport) {
            html += `
                <div class="amadex-suggestion-item" 
                     data-city="${airport.city}" 
                     data-code="${airport.code}" 
                     data-name="${airport.name}"
                     data-country="${airport.country || ''}"
                     data-input="${inputSelector}"
                     data-code-input="${codeSelector}"
                     data-description="${descriptionSelector}"
                     data-dropdown="${suggestionsSelector}">
                    <div class="amadex-suggestion-content">
                        <div class="amadex-suggestion-city">${airport.city}</div>
                        <div class="amadex-suggestion-airport">${airport.name}${airport.country ? ', ' + airport.country : ''}</div>
                </div>
                    <div class="amadex-suggestion-code">${airport.code}</div>
                </div>`;
        });
        
        html += `</div>`;
        
        $dropdown.html(html);
        $dropdown.css('display', '').addClass('active');
        
        // Handle search within dropdown
        $dropdown.find('.amadex-dropdown-search-input').on('input', function() {
            const keyword = $(this).val();
            const targetDropdown = $(this).data('target');
            const targetInput = $(this).data('input');
            
            console.log('Dropdown search (displayAirportSuggestions):', keyword, 'for', targetInput);
            
            if (keyword.length >= 2) {
                const newCodeSelector = targetInput + '-code';
                const newDescriptionSelector = targetInput.replace('#modern-', '#') + '-description';
                searchAirports(keyword, targetInput, newCodeSelector, targetDropdown, newDescriptionSelector);
            } else if (keyword.length === 0) {
                showDropdownWithSections(targetDropdown, targetInput);
            }
        });
        
        // Bind click events for airport items
        $dropdown.find('.amadex-suggestion-item').on('click', function(e) {

            e.preventDefault();
            e.stopPropagation();
        
            const $item = $(this);
            const city = $item.data('city');
            const code = $item.data('code');
            const name = $item.data('name');
            const country = $item.data('country');
            const codeInput = $item.data('code-input');
            const description = $item.data('description');
        
            // set values
            $(inputSelector).val(`${city} (${code})`);
            $(codeInput).val(code);
            $(description).text(limitWords(name));
        
            if (typeof saveRecentSearch === 'function') {
                saveRecentSearch({ city, code, name, country });
            }
        
            // CLOSE DROPDOWN
            $('.amadex-suggestions-dropdown').removeClass('active');
            $('.amadex-location-field').removeClass('field-active');
        
        });
    }

    /**
     * Swap locations with smooth animation and anticlockwise rotation
     */
//     function swapLocations() {
//         // Swap input values
//         const originVal = $('#modern-origin').val();
//         const originCode = $('#modern-origin-code').val();
//         const originDesc = $('#origin-description').text();
        
//         const destVal = $('#modern-destination').val();
//         const destCode = $('#modern-destination-code').val();
//         const destDesc = $('#destination-description').text();
        
//         // Add anticlockwise rotation animation to swap button
//         const $swapButton = $('#swap-locations, .amadex-swap-button');
//         $swapButton.addClass('rotating');
        
//         // Add smooth animation effect
//         $('#origin-field, #destination-field').css({
//             'transform': 'scale(0.95)',
//             'opacity': '0.7',
//             'transition': 'all 0.25s cubic-bezier(0.4, 0, 0.2, 1)'
//         });
        
//         setTimeout(function() {
//             // Swap values
//             $('#modern-origin').val(destVal);
//             $('#modern-origin-code').val(destCode);
//            // $('#origin-description').text(destDesc);
//             $('#origin-description').text(limitWords(destDesc));
            
// $('#modern-destination').val(originVal);
// $('#modern-destination-code').val(originCode);
// $('#destination-description').text(limitWords(originDesc));
// $('#modern-origin').trigger('input').trigger('change');
// $('#modern-destination').trigger('input').trigger('change');
            
//             // Restore animation
//             $('#origin-field, #destination-field').css({
//                 'transform': 'scale(1)',
//                 'opacity': '1'
//             });
//         }, 250);
        
//         // Remove rotation class after animation completes (0.6s)
//         setTimeout(function() {
//             $swapButton.removeClass('rotating');
//         }, 600);
//     }

function swapLocations() {
    // Scope to whichever form is currently visible/active
    const $activeForm = $('#amadex-modern-form-results:visible').length 
        ? $('#amadex-modern-form-results') 
        : $('#amadex-modern-form');
    
    const $originInput = $activeForm.find('#modern-origin, .vsb-field--origin input').first();
    const $originCode  = $activeForm.find('#modern-origin-code').first();
    const $originDesc  = $activeForm.find('#origin-description').first();
    
    const $destInput   = $activeForm.find('#modern-destination, .vsb-field--destination input').first();
    const $destCode    = $activeForm.find('#modern-destination-code').first();
    const $destDesc    = $activeForm.find('#destination-description').first();
    
    const originVal  = $originInput.val();
    const originCode = $originCode.val();
    const originDesc = $originDesc.text();
    
    const destVal  = $destInput.val();
    const destCode = $destCode.val();
    const destDesc = $destDesc.text();
    
    const $swapButton = $activeForm.find('#swap-locations, .amadex-swap-button').first();
    $swapButton.addClass('rotating');
    
    $activeForm.find('#origin-field, #destination-field, .vsb-field--origin, .vsb-field--destination').css({
        'transform': 'scale(0.95)',
        'opacity': '0.7',
        'transition': 'all 0.25s cubic-bezier(0.4, 0, 0.2, 1)'
    });
    
    setTimeout(function() {
        $originInput.val(destVal);
        $originCode.val(destCode);
        $originDesc.text(limitWords(destDesc));
        
        $destInput.val(originVal);
        $destCode.val(originCode);
        $destDesc.text(limitWords(originDesc));
        
        $activeForm.find('#origin-field, #destination-field, .vsb-field--origin, .vsb-field--destination').css({
            'transform': 'scale(1)',
            'opacity': '1'
        });
    }, 250);
    
    setTimeout(function() {
        $swapButton.removeClass('rotating');
    }, 600);
}

    /**
     * Initialize travellers dropdown with smooth animations
     */
    function initTravellersDropdown() {
        // Toggle dropdown with smooth animation
        $(document).on('click', '.amadex-travellers-trigger', function(e) {
            e.stopPropagation();
            const $dropdown = $('#travellers-dropdown');
            const isActive = $dropdown.hasClass('active');
            
            // Suppress location dropdowns from reopening while travellers is open
            suppressLocationDropdowns = true;
            $('.amadex-suggestions-dropdown').removeClass('active');
            $('.amadex-calendar-widget').removeClass('active');
            $('.amadex-location-field').removeClass('field-active');
            $('.amadex-date-field').removeClass('field-active');
            setTimeout(function() { suppressLocationDropdowns = false; }, 400);
            
            // Clean up mobile calendar state
            if ($(window).width() <= 768) {
                $('body').css('overflow', '');
                $('.amadex-calendar-backdrop').fadeOut(200, function() {
                    $(this).remove();
                });
            }
            
            if (!isActive) {
                // Opening
                $('#travellers-field').addClass('active').addClass('field-active');
                $dropdown.css('display', '').addClass('active');
            } else {
                // Closing
                $dropdown.css('opacity', '0').css('transform', 'translateY(-10px) scale(0.95)');
                setTimeout(function() {
                    $('#travellers-field').removeClass('active');
                    $dropdown.removeClass('active').css('opacity', '').css('transform', '');
                }, 200);
            }
        });
        
        // Counter buttons with smooth animation
        // Counter buttons with smooth animation
$('.amadex-counter-btn').on('click', function(e) {
    e.preventDefault();
    const $btn = $(this);
    const action = $btn.data('action');
    const target = $btn.data('target');
    const $counter = $(`#${target}-count`);
    let currentValue = parseInt($counter.text());
    let newValue = currentValue;

    const totalPassengers = passengers.adults + passengers.children + passengers.infants;
    const MAX_TOTAL = 9;

    if (action === 'plus') {
    if (target === 'adults') {
        if (currentValue < 9 && totalPassengers < MAX_TOTAL) {
            newValue++;
        } else {
            showPaxLimitMsg('total');
        }
    } else if (target === 'children') {
        if (totalPassengers < MAX_TOTAL) {
            newValue++;
        } else {
            showPaxLimitMsg('total');
        }
    } else if (target === 'infants') {
        if (currentValue < passengers.adults && totalPassengers < MAX_TOTAL) {
            newValue++;
        } else if (currentValue >= passengers.adults) {
            showPaxLimitMsg('infants');
        } else {
            showPaxLimitMsg('total');
        }
    }
} else if (action === 'minus') {
        if (target === 'adults' && currentValue > 1) {
            newValue--;
            // If reducing adults makes infants exceed adults, reduce infants too
            if (passengers.infants > newValue) {
                passengers.infants = newValue;
                $('[id="modern-infants"]').val(newValue);
                const $infantCounter = $('#infants-count');
                $infantCounter.text(newValue);
            }
        } else if ((target === 'children' || target === 'infants') && currentValue > 0) {
            newValue--;
        }
    }

    // Only animate if value changed
    if (newValue !== currentValue) {
        $counter.css({
            'transform': 'scale(1.2)',
            'color': '#0E7D3F',
            'transition': 'all 0.2s ease'
        });

        setTimeout(function() {
            $counter.text(newValue);
            $counter.css({
                'transform': 'scale(1)',
                'color': ''
            });
        }, 100);

        passengers[target] = newValue;
        $(`[id="modern-${target}"]`).val(newValue);

        updateTravellersDisplay();
        updateCounterButtons();
    }
});
        
        // Cabin buttons with smooth animation
        $('.amadex-cabin-btn').on('click', function(e) {
            e.preventDefault();
            const $btn = $(this);
            
            // Remove active from all buttons
            $('.amadex-cabin-btn').removeClass('active');
            
            // Add active to clicked button
            $btn.addClass('active');
            
            selectedCabin = $btn.data('cabin');
            $('#modern-cabin').val(selectedCabin);
            updateTravellersDisplay();
        });
        
        // Apply button - close dropdown and update display
        $(document).on('click', '#travellers-apply', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Close dropdown immediately
            const $dropdown = $('#travellers-dropdown');
            $('#travellers-field').removeClass('active').removeClass('field-active');
            $dropdown.removeClass('active').css({
                'opacity': '',
                'transform': ''
            });
            suppressLocationDropdowns = false; // Re-enable location dropdowns
        });
        
        // Close dropdown when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#travellers-field').length) {
                $('#travellers-field').removeClass('active').removeClass('field-active');
                $('#travellers-dropdown').removeClass('active');
                suppressLocationDropdowns = false; // Re-enable location dropdowns
            }
        });
        
        updateCounterButtons();
        
        // Set Economy as active by default if no cabin is selected
        if (!selectedCabin || selectedCabin === '' || selectedCabin === 'ANY') {
            $('.amadex-cabin-btn').removeClass('active');
            $('.amadex-cabin-btn[data-cabin="ECONOMY"]').addClass('active');
            selectedCabin = 'ECONOMY';
            $('#modern-cabin').val('ECONOMY');
        } else {
            // Set the selected cabin as active
            $('.amadex-cabin-btn').removeClass('active');
            $(`.amadex-cabin-btn[data-cabin="${selectedCabin}"]`).addClass('active');
        }
        
        // Initialize cabin display
        updateTravellersDisplay();
    }

    /**
     * Update travellers display
     */
    function updateTravellersDisplay() {
        const total = passengers.adults + passengers.children + passengers.infants;
        let text = `${total} Traveller${total > 1 ? 's' : ''}`;
        
        // Update only the text, preserve SVG icon
        const $display = $('#travellers-display');
        if ($display.length) {
            const $span = $display.find('span');
            if ($span.length) {
                $span.text(' ' + text);
            } else {
                $display.text(text);
            }
        }

        // Sync traveller count to all segment rows
        $('[id^="travellers-display-"]').each(function() {
            $(this).find('span').first().text(text);
        });
        
        // Update cabin display
        const cabinNames = {
            'ECONOMY': 'Economy',
            'PREMIUM_ECONOMY': 'Premium / Economy',
            'BUSINESS': 'Business',
            'FIRST': 'First Class'
        };
        if ($('#cabin-display').length) {
            $('#cabin-display').text(cabinNames[selectedCabin] || 'Economy');
        }
    }

    /**
     * Update counter button states
     */
    // function updateCounterButtons() {
    //     // Adults min/max
    //     $('[data-target="adults"][data-action="minus"]').prop('disabled', passengers.adults <= 1);
    //     $('[data-target="adults"][data-action="plus"]').prop('disabled', passengers.adults >= 9);
        
    //     // Children min/max
    //     $('[data-target="children"][data-action="minus"]').prop('disabled', passengers.children <= 0);
    //     $('[data-target="children"][data-action="plus"]').prop('disabled', passengers.children >= 8);
        
    //     // Infants min/max
    //     $('[data-target="infants"][data-action="minus"]').prop('disabled', passengers.infants <= 0);
    //     $('[data-target="infants"][data-action="plus"]').prop('disabled', passengers.infants >= 8);
    // }

    function showPaxLimitMsg(type) {
    // Clear existing
    $('.amadex-pax-limit-msg').remove();

    const msg = type === 'infants'
        ? '⚠ Infants cannot exceed the number of adults.'
        : '⚠ Maximum 9 travellers allowed in total.';

    const $msg = $('<div class="amadex-pax-limit-msg amadex-pax-limit-msg--' + type + '">' + msg + '</div>');
    $('#travellers-dropdown').find('.amadex-cabin-selector').before($msg);

    // Auto remove after 3 seconds
    setTimeout(function() {
        $msg.fadeOut(300, function() { $(this).remove(); });
    }, 3000);
}
function updateCounterButtons() {
    const total = passengers.adults + passengers.children + passengers.infants;
    const maxReached = total >= 9;

    // Clear all messages first
    $('.amadex-pax-limit-msg').remove();

    // Adults
    $('[data-target="adults"][data-action="minus"]').prop('disabled', passengers.adults <= 1);
    $('[data-target="adults"][data-action="plus"]').prop('disabled', passengers.adults >= 9 || maxReached);

    // Children
    $('[data-target="children"][data-action="minus"]').prop('disabled', passengers.children <= 0);
    $('[data-target="children"][data-action="plus"]').prop('disabled', maxReached);

    // Infants
    $('[data-target="infants"][data-action="minus"]').prop('disabled', passengers.infants <= 0);
    $('[data-target="infants"][data-action="plus"]').prop('disabled', passengers.infants >= passengers.adults || maxReached);
}
    /**
     * Perform modern search
     */
    function performModernSearch() {
        const $form = $('#amadex-modern-form, #amadex-modern-form-results');
        const $searchBtn = $form.find('.amadex-search-btn');
        const resultsPage = $form.data('results');
        
        // Get trip type
        const tripType = $('input[name="tripType"]:checked').val();
        const isOneWay = tripType === 'oneway';
        const isRoundTrip = tripType === 'round';
        
        // Extract IATA codes from origin/destination fields
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
        
        // Extract IATA code from string (handles formats like "DELHI (DEL)" or just "DEL")
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
        // ── Validation ──────────────────────────────────────────────
        const originVal = $('#modern-origin').val()?.trim();
        const destVal   = $('#modern-destination').val()?.trim();

        // Remove previous error styles
        $('#origin-field, #destination-field').removeClass('amadex-field-error');
        $('.amadex-field-error-msg').remove();

        let hasError = false;

        if (!originVal) {
            $('#origin-field').addClass('amadex-field-error');
            $('#origin-field .amadex-field-input-wrap').append(
                '<span class="amadex-field-error-msg">⚠ Please enter a departure city</span>'
            );
            $('#modern-origin').focus();
            hasError = true;
        }

        if (!destVal) {
            $('#destination-field').addClass('amadex-field-error');
            $('#destination-field .amadex-field-input-wrap').append(
                '<span class="amadex-field-error-msg">⚠ Please enter an arrival city</span>'
            );
            if (!hasError) $('#modern-destination').focus();
            hasError = true;
        }

        if (hasError) {
            $searchBtn.prop('disabled', false).removeClass('loading');
            $form.removeClass('searching');
            return;
        }

        // Check origin and destination are not the same
// Check origin and destination are not the same
const rawOriginCode = $('#modern-origin-code').val() || $('#modern-origin').val();
const rawDestCode = $('#modern-destination-code').val() || $('#modern-destination').val();

if (rawOriginCode && rawDestCode && rawOriginCode.trim().toUpperCase() === rawDestCode.trim().toUpperCase()) {
    $('#destination-field, .vsb-field--destination').addClass('amadex-field-error');
    $('#destination-field .amadex-field-input-wrap').append(
        '<span class="amadex-field-error-msg">⚠ Origin and destination cannot be the same</span>'
    );
    $('#modern-destination').focus();
    $searchBtn.prop('disabled', false).removeClass('loading');
    $form.removeClass('searching');
    return;
}

        // ────────────────────────────────────────────────────────────
        // Get form data with IATA code extraction
        let originCode = $('#modern-origin-code').val() || $('#modern-origin').val();
        let destCode = $('#modern-destination-code').val() || $('#modern-destination').val();
        // ────────────────────────────────────────────────────────────
        
        // Extract IATA codes if needed
        originCode = extractIataCode(originCode);
        destCode = extractIataCode(destCode);
        
        const searchData = {
            origin: originCode,
            origin_name: $('#modern-origin').val() || originCode,
            destination: destCode,
            destination_name: $('#modern-destination').val() || destCode,
            departure: $('#modern-departure').val(),
            return: isOneWay ? '' : $('#modern-return').val(), // Clear return for one-way
            adults: $('#modern-adults').val() || 1,
            children: $('#modern-children').val() || 0,
            infants: $('#modern-infants').val() || 0,
            // If no cabin selected, use empty string to show all cabin classes
            cabin: $('#modern-cabin').val() || '',
            // Show all cabins if no cabin selected, ECONOMY selected, or value is empty/null
            show_all_cabins: (!$('#modern-cabin').val() || $('#modern-cabin').val() === '' || $('#modern-cabin').val() === 'ECONOMY' || $('#modern-cabin').val() === 'ANY') ? 'yes' : 'no',
            currency: 'USD',
            one_way: isOneWay ? 'Yes' : 'No',
            trip_type: tripType
        };
        

        
        if (tripType === 'multi-city') {
            const segments = collectMultiSegments(true);
            
            // Normalize IATA codes for each segment
            const normalizedSegments = segments.map(function(seg) {
                return {
                    origin: extractIataCode(seg.origin || ''),
                    originLocationCode: extractIataCode(seg.origin || ''),
                    origin_name: seg.origin_name || seg.origin || '',
                    destination: extractIataCode(seg.destination || ''),
                    destinationLocationCode: extractIataCode(seg.destination || ''),
                    destination_name: seg.destination_name || seg.destination || '',
                    departure: seg.departure || '',
                    departure_date: seg.departure || ''
                };
            });
            
            searchData.multi_segments = normalizedSegments;
            searchData.segments = normalizedSegments; // Also add as segments for compatibility
            searchData.segment_count = normalizedSegments.length;
            
            console.log('Modern Search - Multi-city segments:', normalizedSegments);
            
            if (normalizedSegments.length > 1) {
                searchData.return = '';
                // For multi-city, use first segment's origin/destination for main search
                if (normalizedSegments.length > 0) {
                    searchData.origin = normalizedSegments[0].origin;
                    searchData.destination = normalizedSegments[0].destination;
                }
            }
            
            if (normalizedSegments.length < 2) {
                alert('Please add at least two cities for a Multi-City search.');
                $searchBtn.prop('disabled', false).removeClass('loading');
                $form.removeClass('searching');
                return;
            }
            
            const hasIncomplete = normalizedSegments.some(function(segment) {
                return !segment.origin || !segment.destination || !segment.departure;
            });
            
            if (hasIncomplete) {
                alert('Please complete origin, destination, and departure date for every city.');
                $searchBtn.prop('disabled', false).removeClass('loading');
                $form.removeClass('searching');
                return;
            }
        }
        
        // Validate form
        if (tripType !== 'multi-city') {
            if (!searchData.origin || !searchData.destination || !searchData.departure) {
                alert('Please fill in all required fields');
                return;
            }
        }
        
        if (isRoundTrip && !searchData.return) {
            // Highlight return field and open calendar
            $('#return-field, .vsb-field--return').addClass('amadex-field-error');
            $('#return-field .amadex-field-input-wrap').append(
                '<span class="amadex-field-error-msg">⚠ Please select a return date</span>'
            );
            // Auto-open return date calendar
            currentCalendarField = 'return';
            $('#return-display').closest('.amadex-date-field').addClass('field-active');
            showCustomCalendar(null);
            $searchBtn.prop('disabled', false).removeClass('loading');
            $form.removeClass('searching');
            return;
        }

        // Clear any return field error
        $('#return-field, .vsb-field--return').removeClass('amadex-field-error');
        $('#return-field .amadex-field-error-msg').remove();
        
        // Show loading state with smooth animation
        $searchBtn.prop('disabled', true).addClass('loading');
        $form.addClass('searching');
        
        // Close all dropdowns during search
        $('.amadex-suggestions-dropdown').removeClass('active');
        $('.amadex-calendar-widget').removeClass('active');
        $('.amadex-travellers-dropdown').removeClass('active');
        $('.amadex-modern-field').removeClass('field-active');
        
        // Clean up mobile calendar state
        if ($(window).width() <= 768) {
            $('body').css('overflow', '');
            $('.amadex-calendar-backdrop').fadeOut(200, function() {
                $(this).remove();
            });
        }
        
        // Prepare AJAX data
        const ajaxData = {
            action: 'amadex_search_flights',
            origin: searchData.origin,
            destination: searchData.destination,
            departure_date: searchData.departure,
            return_date: searchData.return || '',
            adults: searchData.adults,
            children: searchData.children,
            infants: searchData.infants,
            // When ECONOMY is selected, no cabin selected, or cabin is empty/null/undefined, show all cabin classes
            // Send empty travel_class to API to get all cabin classes
            travel_class: (searchData.cabin === 'ECONOMY' || !searchData.cabin || searchData.cabin === '' || searchData.cabin === null || searchData.cabin === undefined) ? '' : searchData.cabin,
            show_all_cabins: (searchData.cabin === 'ECONOMY' || !searchData.cabin || searchData.cabin === '' || searchData.cabin === null || searchData.cabin === undefined) ? 'yes' : 'no',
            currency: searchData.currency,
            trip_type: searchData.trip_type || tripType,
            nonce: typeof AmadexConfig !== 'undefined' ? AmadexConfig.nonce : ''
        };
        
        // Add multi-city segments if applicable
        if (searchData.trip_type === 'multi-city' && searchData.multi_segments && searchData.multi_segments.length > 1) {
            ajaxData.multi_segments = JSON.stringify(searchData.multi_segments);
            ajaxData.segments = JSON.stringify(searchData.multi_segments); // Also send as segments for compatibility
            console.log('Modern Search - Adding multi-city segments to AJAX:', searchData.multi_segments);
        }
        
        console.log('Modern Search - Sending AJAX request:', ajaxData);
        
        // Perform AJAX search
        $.ajax({
            url: typeof AmadexConfig !== 'undefined' ? AmadexConfig.ajaxUrl : '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: ajaxData,
            success: function(response) {
                if (response.success) {
                    console.log('Modern Search - API Response:', response);
                    console.log('Modern Search - Response data structure:', {
                        hasFlights: !!(response.data && response.data.flights),
                        flightCount: response.data && response.data.flights ? response.data.flights.length : 0,
                        hasMeta: !!(response.data && response.data.meta),
                        hasSegmentResults: !!(response.data && response.data.segment_results),
                        isMultiCity: !!(response.data && response.data.is_multi_city)
                    });
                    
                    // Store search data FIRST before displaying
                    sessionStorage.setItem('amadex_search_data', JSON.stringify(searchData));
                    sessionStorage.setItem('amadex_search_results', JSON.stringify(response.data));
                    
                    console.log('Modern Search - Stored search data and results in sessionStorage');
                    console.log('Modern Search - Response data structure:', {
                        hasFlights: !!(response.data && response.data.flights),
                        flightCount: response.data && response.data.flights ? response.data.flights.length : 0,
                        hasSegmentResults: !!(response.data && response.data.segment_results),
                        isMultiCity: !!(response.data && response.data.is_multi_city)
                    });
                    
                    // Store segments separately if multi-city
                    if (tripType === 'multi-city' && searchData.multi_segments && searchData.multi_segments.length > 1) {
                        // Format segments for storage
                        // const formattedSegments = searchData.multi_segments.map(function(seg) {
                        //     return {
                        //         origin: seg.origin || seg.originLocationCode,
                        //         originLocationCode: seg.origin || seg.originLocationCode,
                        //         destination: seg.destination || seg.destinationLocationCode,
                        //         destinationLocationCode: seg.destination || seg.destinationLocationCode,
                        //         departure_date: seg.departure || seg.departure_date
                        //     };
                        // });
                        // sessionStorage.setItem('amadex_multi_city_segments', JSON.stringify(formattedSegments));
                        const formattedSegments = searchData.multi_segments.map(function(seg) {
    return {
        origin: seg.origin || seg.originLocationCode,
        originLocationCode: seg.origin || seg.originLocationCode,
        origin_name: seg.origin_name || seg.origin || seg.originLocationCode,
        destination: seg.destination || seg.destinationLocationCode,
        destinationLocationCode: seg.destination || seg.destinationLocationCode,
        destination_name: seg.destination_name || seg.destination || seg.destinationLocationCode,
        departure_date: seg.departure || seg.departure_date
    };
});
sessionStorage.setItem('amadex_multi_city_segments', JSON.stringify(formattedSegments));
                        console.log('Modern Search - Stored multi-city segments:', formattedSegments);
                        
                        // Also store segment results if available
                        if (response.data && response.data.segment_results) {
                            console.log('Modern Search - Storing segment_results for faster filtering');
                            // Segment results are already in response.data, will be loaded on results page
                        }
                    }
                    
                    // Build URL parameters
                    const isDomestic = checkIfDomestic(searchData.origin, searchData.destination);
                    
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
                    
                    // Add segments to URL if multi-city (double encode for safety)
                    if (tripType === 'multi-city' && searchData.multi_segments && searchData.multi_segments.length > 1) {
                        // const formattedSegments = searchData.multi_segments.map(function(seg) {
                        //     return {
                        //         origin: seg.origin || seg.originLocationCode,
                        //         originLocationCode: seg.origin || seg.originLocationCode,
                        //         destination: seg.destination || seg.destinationLocationCode,
                        //         destinationLocationCode: seg.destination || seg.destinationLocationCode,
                        //         departure_date: seg.departure || seg.departure_date
                        //     };
                        // });

                        const formattedSegments = searchData.multi_segments.map(function(seg) {
    return {
        origin: seg.origin || seg.originLocationCode,
        originLocationCode: seg.origin || seg.originLocationCode,
        origin_name: seg.origin_name || seg.origin || seg.originLocationCode,
        destination: seg.destination || seg.destinationLocationCode,
        destinationLocationCode: seg.destination || seg.destinationLocationCode,
        destination_name: seg.destination_name || seg.destination || seg.destinationLocationCode,
        departure_date: seg.departure || seg.departure_date
    };
});
                        const segmentsJSON = JSON.stringify(formattedSegments);
                        // params.append('segments', encodeURIComponent(segmentsJSON));
                        params.append('segments', segmentsJSON);
                        console.log('Modern Search - Added segments to URL:', formattedSegments);
                    }
                    
                    // Check if we're already on results page
                    const isOnResultsPage = window.location.href.includes('flight-results') || 
                                           $('#amadex-results-page').length > 0 ||
                                           $('#amadex-flight-cards-container').length > 0 ||
                                           (typeof isResultsPage === 'function' && isResultsPage());
                    
                    if (isOnResultsPage) {
                        // We're on results page - display results without redirecting
                        console.log('Modern Search - Already on results page, displaying results without redirect');
                        
                        // Update URL without reloading (preserve existing path)
const currentPath = window.location.pathname;
const newUrl = currentPath + '?' + params.toString();
window.history.pushState({}, '', newUrl);

// Re-populate the search form fields from the new URL
setTimeout(function() {
    populateFormFromURL();
}, 100);

// Update search info display
if (typeof window.updateSearchInfo === 'function') {
    window.updateSearchInfo(searchData);
} else if (typeof updateSearchInfo === 'function') {
    updateSearchInfo(searchData);
}
                        
                        // Clear existing results and show loading
                        $('#amadex-flight-cards-container').empty();
                        $('#amadex-loading').show();
                        $('#amadex-no-results').hide();
                        $('.amadex-segment-tabs-container').remove(); // Remove old tabs if any
                        
                        console.log('Modern Search - Cleared container, showing loading, about to display results');
                        console.log('Modern Search - Response data:', response.data);
                        
                        // Display results directly using displayFlightResults from amadex.js
                        // Call immediately, no setTimeout needed - data is already stored
                        if (typeof window.displayFlightResults === 'function') {
                            console.log('Modern Search - Calling window.displayFlightResults immediately');
                            try {
                                // Hide loading before displaying
                                $('#amadex-loading').hide();
                                window.displayFlightResults(response.data);
                                console.log('Modern Search - displayFlightResults called successfully');
                            } catch(e) {
                                console.error('Modern Search - Error calling displayFlightResults:', e);
                                // Fallback: try to trigger display via jQuery event
                                $(document).trigger('amadex:displayResults', [response.data]);
                            }
                        } else {
                            // Fallback: try to trigger display via jQuery event
                            console.log('Modern Search - window.displayFlightResults not available, triggering event');
                            $('#amadex-loading').hide();
                            $(document).trigger('amadex:displayResults', [response.data]);
                            
                            // Double check after a delay
                            setTimeout(function() {
                                const hasResults = $('#amadex-flight-cards-container').children().length > 0;
                                const hasTabs = $('.amadex-segment-tabs-container').length > 0;
                                
                                if (!hasResults && !hasTabs) {
                                    console.log('Modern Search - No results displayed after event, trying direct call');
                                    // Try to load from sessionStorage
                                    if (typeof loadStoredResults === 'function') {
                                        loadStoredResults();
                                    } else if (typeof window.loadStoredResults === 'function') {
                                        window.loadStoredResults();
                                    } else {
                                        console.log('Modern Search - No display handler found, redirecting as fallback');
                                        window.location.href = resultsPage + '?' + params.toString();
                                    }
                                } else {
                                    console.log('Modern Search - Results displayed successfully via event');
                                }
                            }, 500);
                        }
                        
                        // Reset button state
                        $searchBtn.prop('disabled', false).removeClass('loading');
                        $form.removeClass('searching');
                        
                        // Scroll to results after a short delay
                        setTimeout(function() {
                            const $container = $('#amadex-flight-cards-container');
                            const $tabs = $('.amadex-segment-tabs-container');
                            const scrollTarget = $tabs.length ? $tabs : $container;
                            
                            if (scrollTarget.length && scrollTarget.offset()) {
                                $('html, body').animate({
                                    scrollTop: scrollTarget.offset().top - 100
                                }, 300);
                            }
                        }, 300);
                    } else {
                        // Not on results page - redirect normally
                        const resultsUrl = resultsPage + '?' + params.toString();
                        console.log('Modern Search - Not on results page, redirecting to:', resultsUrl);
                        window.location.href = resultsUrl;
                    }
                } else {
                    console.error('Modern Search - API Error Response:', response);
                    alert('Search Error: ' + (response.data && response.data.message ? response.data.message : 'Please try again'));
                    $searchBtn.prop('disabled', false).removeClass('loading');
                    $form.removeClass('searching');
                }
            },
            error: function(xhr, status, error) {
                console.error('Modern Search - AJAX Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText,
                    statusCode: xhr.status
                });
                
                let errorMessage = 'Network error. Please check your connection and try again.';
                if (xhr.status === 0) {
                    errorMessage = 'Network error. Please check your internet connection.';
                } else if (xhr.status === 404) {
                    errorMessage = 'Search endpoint not found. Please contact support.';
                } else if (xhr.status === 500) {
                    errorMessage = 'Server error. Please try again later.';
                } else if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMessage = xhr.responseJSON.data.message;
                }
                
                alert('Search Error: ' + errorMessage);
                $searchBtn.prop('disabled', false).removeClass('loading');
                $form.removeClass('searching');
            }
        });
    }

    /**
     * Check if domestic route
     */
    function checkIfDomestic(origin, destination) {
        const countryAirports = {
            'US': ['JFK', 'LAX', 'ORD', 'ATL', 'DFW', 'DEN', 'SFO', 'SEA', 'LAS', 'MCO', 'EWR', 'CLT', 'PHX', 'IAH', 'MIA', 'BOS', 'MSP', 'FLL', 'DTW', 'PHL', 'LGA', 'BWI', 'SLC', 'SAN', 'IAD', 'DCA', 'MDW', 'TPA', 'PDX', 'STL', 'SMF'],
            'IN': ['DEL', 'BOM', 'BLR', 'MAA', 'HYD', 'CCU', 'AMD', 'GOI', 'COK', 'PNQ'],
            'GB': ['LHR', 'LGW', 'MAN', 'STN', 'EDI'],
            'CA': ['YYZ', 'YVR', 'YUL', 'YYC', 'YOW'],
            'AU': ['SYD', 'MEL', 'BNE', 'PER', 'ADL']
        };
        
        let originCountry = null;
        let destCountry = null;
        
        for (const [country, airports] of Object.entries(countryAirports)) {
            if (airports.includes(origin)) originCountry = country;
            if (airports.includes(destination)) destCountry = country;
        }
        
        return originCountry && destCountry && originCountry === destCountry;
    }

    /**
     * Populate form from URL parameters (for results page)
     */
    function populateFormFromURL() {
        // Check if we're on results page
        // const $summaryContainer = $('#amadex-search-summary');
        // const $resultsForm = $summaryContainer.find('.amadex-modern-form');
        // if (!$summaryContainer.length || !$resultsForm.length) {
        //     return;
        // }
        const $summaryContainer = $('#amadex-search-summary, #amadex-search-summary-mobile');
const $resultsForm = $summaryContainer.find('.amadex-modern-form');
if (!$summaryContainer.length || !$resultsForm.length) {
    return;
}
        // Get URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        
        // Origin
        const originName = urlParams.get('origin_name') || '';
        const originIata = urlParams.get('origin_iata') || '';
        if (originName && originIata) {
            $resultsForm.find('#modern-origin').val(originName);
            $resultsForm.find('#modern-origin-code').val(originIata);
            // Extract city and code for description
            const originMatch = originName.match(/(.+?)\s*\(([A-Z]{3})\)/);
            if (originMatch) {
               // $resultsForm.find('#origin-description').text(originMatch[1].trim());
                  $resultsForm.find('#origin-description').text(limitWords(originMatch[1].trim()));
            }
        }
        
        // Destination
        const destName = urlParams.get('destination_name') || '';
        const destIata = urlParams.get('destination_iata') || '';
        if (destName && destIata) {
            $resultsForm.find('#modern-destination').val(destName);
            $resultsForm.find('#modern-destination-code').val(destIata);
            const destMatch = destName.match(/(.+?)\s*\(([A-Z]{3})\)/);
            if (destMatch) {
               // $resultsForm.find('#destination-description').text(destMatch[1].trim());

                 $resultsForm.find('#destination-description').text(limitWords(destMatch[1].trim()));
            }
        }
        
        // Departure Date
        const departDate = urlParams.get('depart_date') || '';
        if (departDate) {
            // $resultsForm.find('#modern-departure').val(departDate);
            $resultsForm.find('#modern-departure, #vsb-departure-date').val(departDate);
            const depDate = new Date(departDate);
            if (!isNaN(depDate)) {
                const $depDisplay = $resultsForm.find('#departure-display');
                const $depDay = $resultsForm.find('#departure-day');
                if ($depDisplay.length && $depDay.length) {
                    $depDisplay.text(`${depDate.getDate()} ${['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'][depDate.getMonth()]}, ${depDate.getFullYear().toString().substr(2)}`);
                    $depDay.text(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][depDate.getDay()]);
                }
                selectedDepartureDate = depDate;
            }
        }
        
        // Return Date
        const returnDate = urlParams.get('return_date') || '';
        const oneWay = urlParams.get('one_way') === 'Yes';
        if (returnDate && !oneWay) {
            // $resultsForm.find('#modern-return').val(returnDate);
            $resultsForm.find('#modern-return, #vsb-return-date').val(returnDate);
            const retDate = new Date(returnDate);
            if (!isNaN(retDate)) {
                const $retDisplay = $resultsForm.find('#return-display');
                const $retDay = $resultsForm.find('#return-day');
                if ($retDisplay.length && $retDay.length) {
                    $retDisplay.text(`${retDate.getDate()} ${['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'][retDate.getMonth()]}, ${retDate.getFullYear().toString().substr(2)}`);
                    $retDay.text(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][retDate.getDay()]);
                }
                selectedReturnDate = retDate;
            }
        }
        
        // Trip Type
        // const tripType = urlParams.get('trip_type') || '';
        // if (tripType === 'multi-city' || tripType === 'multicity') {

        const tripType = urlParams.get('trip_type') || '';
        if (tripType !== 'multi-city' && tripType !== 'multicity') {
            sessionStorage.removeItem('amadex_multi_city_segments');
            sessionStorage.removeItem('amadex_multi_city_bookings');
            sessionStorage.removeItem('amadex_booking_all_segments');
        }
        if (tripType === 'multi-city' || tripType === 'multicity') {
    // Set multi-city trip type
    $resultsForm.find('input[name="tripType"][value="multi-city"]').prop('checked', true).trigger('change');

    // Load segments from URL or sessionStorage
    let multiSegments = [];
    const segmentsParam = urlParams.get('segments');

    // if (segmentsParam) {
    //     try {
    //         let decoded = decodeURIComponent(segmentsParam);
    //         try {
    //             multiSegments = JSON.parse(decoded);
    //         } catch(e) {
    //             multiSegments = JSON.parse(decodeURIComponent(decoded));
    //         }
    //         sessionStorage.setItem('amadex_multi_city_segments', JSON.stringify(multiSegments));
    //     } catch(e) {
    //         console.error('populateFormFromURL: Error parsing segments:', e);
    //     }
    // }
if (segmentsParam) {
    try {
        let decoded = segmentsParam;
        try { decoded = decodeURIComponent(decoded); } catch(e1) {}
        try { decoded = decodeURIComponent(decoded); } catch(e2) {}
        try {
            multiSegments = JSON.parse(decoded);
        } catch(e3) {}
        if (multiSegments.length) {
            sessionStorage.setItem('amadex_multi_city_segments', JSON.stringify(multiSegments));
        }
    } catch(e) {
        console.error('populateFormFromURL: Error parsing segments:', e);
    }
}

    // Populate extra segments (segment 2 onwards) into VSB rows
    // if (multiSegments.length > 1) {
    //     setTimeout(function() {
    //         // Remove any auto-added blank segment first
    //         $('.vsb-extra-segment').remove();

    //         for (let i = 1; i < multiSegments.length; i++) {
    //             const seg = multiSegments[i];

    //             // Trigger addFlightSegment to create the row
    //             addFlightSegment();

    //             // Now populate the newly created segment
    //             const segId = segmentCounter;
    //             const originVal = seg.origin_name || seg.origin || seg.originLocationCode || '';
    //             const originCode = seg.origin || seg.originLocationCode || '';
    //             const destVal = seg.destination_name || seg.destination || seg.destinationLocationCode || '';
    //             const destCode = seg.destination || seg.destinationLocationCode || '';
    //             const depDate = seg.departure_date || seg.departure || '';

    //             // Populate old-style segment inputs (used by collectMultiSegments)
    //             $(`#modern-origin-${segId}`).val(originVal);
    //             $(`#modern-origin-code-${segId}`).val(originCode);
    //             $(`#modern-destination-${segId}`).val(destVal);
    //             $(`#modern-destination-code-${segId}`).val(destCode);

    //             // Populate VSB extra segment inputs
    //             $(`.vsb-extra-segment[data-segment="${segId}"] #modern-origin-${segId}`).val(originVal);
    //             $(`.vsb-extra-segment[data-segment="${segId}"] #modern-origin-code-${segId}`).val(originCode);
    //             $(`.vsb-extra-segment[data-segment="${segId}"] #modern-destination-${segId}`).val(destVal);
    //             $(`.vsb-extra-segment[data-segment="${segId}"] #modern-destination-code-${segId}`).val(destCode);

    //             if (depDate) {
    //                 $(`#modern-departure-${segId}`).val(depDate);
    //                 const d = new Date(depDate);
    //                 if (!isNaN(d)) {
    //                     const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    //                     const formatted = `${d.getDate()} ${months[d.getMonth()]}, ${String(d.getFullYear()).substr(2)}`;
    //                     $(`#departure-display-${segId}`).text(formatted);
    //                     segmentDepartureDates[segId] = d;
    //                 }
    //             }
    //         }
    //     }, 200);
    // }

    if (multiSegments.length > 1) {
        // Guard against double-execution (can happen if populateFormFromURL is called twice)
        if (window._amadexSegmentRestoreInProgress) return;
        window._amadexSegmentRestoreInProgress = true;

        setTimeout(function() {
            // Remove ALL extra rows of every type to start completely clean
            $('.vsb-extra-segment').remove();
            $('.amadex-flight-segment').not('[data-segment="1"]').remove();
            $('.amadex-multi-city-segment').not('[data-segment="1"]').remove();
            segmentCounter = 1;

            function populateSegmentRow(segId, seg) {
                const originVal  = seg.origin_name  || seg.origin  || seg.originLocationCode  || '';
                const originCode = seg.origin  || seg.originLocationCode  || '';
                const destVal    = seg.destination_name || seg.destination || seg.destinationLocationCode || '';
                const destCode   = seg.destination || seg.destinationLocationCode || '';
                const depDate    = seg.departure_date || seg.departure || '';

                $(`#modern-origin-${segId}`).val(originVal);
                $(`#modern-origin-code-${segId}`).val(originCode);
                $(`#modern-destination-${segId}`).val(destVal);
                $(`#modern-destination-code-${segId}`).val(destCode);

                if (depDate) {
                    $(`#modern-departure-${segId}`).val(depDate);
                    const d = new Date(depDate);
                    if (!isNaN(d)) {
                        const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                        const formatted = `${d.getDate()} ${months[d.getMonth()]}, ${String(d.getFullYear()).substr(2)}`;
                        $(`#departure-display-${segId}`).text(formatted);
                        $(`#departure-day-${segId}`).text(['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][d.getDay()]);
                        segmentDepartureDates[segId] = d;
                    }
                }
            }

            const extraSegments = multiSegments.slice(1);
            const totalToAdd = extraSegments.length;
            let addedCount = 0;

            extraSegments.forEach(function(seg, idx) {
                setTimeout(function() {
                    addFlightSegment();
                    const segId = segmentCounter;
                    populateSegmentRow(segId, seg);
                    addedCount++;
                    // Release guard only after all segments are added
                    if (addedCount >= totalToAdd) {
                        window._amadexSegmentRestoreInProgress = false;
                    }
                }, idx * 120);
            });
        }, 300);
    }
}else if (oneWay) {
            $resultsForm.find('#trip-oneway-results').prop('checked', true).trigger('change');
        } else if (returnDate) {
            $resultsForm.find('#trip-round-results').prop('checked', true).trigger('change');
        }
        
        // Passengers
        const adults = urlParams.get('adults') || '1';
        const children = urlParams.get('children') || '0';
        const infants = urlParams.get('infants') || '0';
        $resultsForm.find('#modern-adults').val(adults);
        $resultsForm.find('#modern-children').val(children);
        $resultsForm.find('#modern-infants').val(infants);
        passengers.adults = parseInt(adults);
        passengers.children = parseInt(children);
        passengers.infants = parseInt(infants);
        
        // Update counter display values in dropdown
        $resultsForm.find('#adults-count').text(passengers.adults);
        $resultsForm.find('#children-count').text(passengers.children);
        $resultsForm.find('#infants-count').text(passengers.infants);
        
        // Cabin - normalize to ensure correct format
        let cabin = urlParams.get('cabin') || 'ECONOMY';
        cabin = cabin.toUpperCase().trim();
        
        // Map variations to standard values
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
            console.warn('Invalid cabin from URL:', cabin, '- defaulting to ECONOMY');
            cabin = 'ECONOMY';
        }
        
        $resultsForm.find('#modern-cabin').val(cabin);
        selectedCabin = cabin;
        
        // Update displays
        updateTravellersDisplay();
        updateCounterButtons();
        
        // Update cabin button active state
        $resultsForm.find('.amadex-cabin-btn').removeClass('active');
        $resultsForm.find(`.amadex-cabin-btn[data-cabin="${cabin}"]`).addClass('active');
    }
    
    // Populate form on results page load
    // Populate form on results page load
    $(document).ready(function() {
        // Check if we're on results page
        if ($('#amadex-search-summary').length) {
            // Wait a bit to ensure all DOM elements are ready
            setTimeout(function() {
                populateFormFromURL();
            }, 100);
        }
    });

    // VSB Search Bar Loader — reveal once DOM + scripts ready
    function revealVsbSearchBar() {
        var $loader = $('#vsb-loader');
        var $vsb = $('.vsb-wrap.vsb-loading');

        if ($loader.length && $vsb.length) {
            $loader.fadeOut(200, function() {
                $vsb.removeClass('vsb-loading').hide().fadeIn(300);
            });
        } else if ($vsb.length) {
            // No loader found, just reveal
            $vsb.removeClass('vsb-loading').fadeIn(300);
        }
    }

    // Reveal on window load
    $(window).on('load', function() {
        revealVsbSearchBar();
    });

    // Fallback: reveal after 2.5 seconds max
    setTimeout(function() {
        revealVsbSearchBar();
    }, 2500);

    // Also reveal on DOM ready as early fallback
    $(document).ready(function() {
        setTimeout(function() {
            revealVsbSearchBar();
        }, 800);
    });

// ── Results page: re-initialize modify search bar ──────────────────
$(document).ready(function() {
    if ($('#amadex-modern-form-results').length) {
        jQuery('.amadex-overlay').css('pointer-events', 'none');
        $(document).on('click', '.amadex-overlay', function(e) {
            e.stopPropagation();
            $(this).css('pointer-events', 'none');
        });
        // Rebind form submit for results page form
        // $('#amadex-modern-form-results').off('submit').on('submit', function(e) {
        //     e.preventDefault();
        //     performModernSearch();
        // });
        $('#amadex-modern-form-results').off('submit').on('submit', function(e) {
            e.preventDefault();
            clearAndStoreNewSearch();
            saveSearchToCookie();
            const currentTripType = $('input[name="tripType"]:checked').val() || 'round';
            if (currentTripType !== 'multi-city') {
                sessionStorage.removeItem('amadex_multi_city_segments');
                sessionStorage.removeItem('amadex_multi_city_bookings');
                sessionStorage.removeItem('amadex_booking_all_segments');
            }
            performModernSearch();
        });
        // Rebind swap for results page
        $('#amadex-modern-form-results').find('#swap-locations, .amadex-swap-button').off('click').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            swapLocations();
        });
        
        // Populate from URL after short delay
        setTimeout(function() {
            populateFormFromURL();
        }, 200);
    }
});
})(jQuery);



// =========================
// MULTI CITY ROW SYSTEM
// =========================

(function($) {
'use strict';

let amadexMcIndex = 1;

// Create row HTML — styled to match the main search bar fields
function createAmadexMultiRow(index) {
    const today = new Date();
    const minDate = today.toISOString().split('T')[0];
    return `
        <div class="amadex-multi-row" data-index="${index}">

            <div class="amadex-modern-field amadex-location-field amadex-mc-field">
                <div class="amadex-field-input-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="11" viewBox="0 0 24.001 10.885">
                        <g transform="translate(-5.002 -18.663)">
                            <path d="M7.012,26.663a2.111,2.111,0,0,0,1.709.869,2.214,2.214,0,0,0,.5-.058c1.68-.408,4.81-1.186,7.843-2.026l-1.454,3.432a.472.472,0,0,0,.038.451.486.486,0,0,0,.4.216,4.05,4.05,0,0,0,3.12-1.464,17.671,17.671,0,0,0,2.707-4.071c.307-.106.6-.206.874-.3a25.818,25.818,0,0,0,5.707-2.486,1.349,1.349,0,0,0,.494-1.445,1.329,1.329,0,0,0-1.171-.965L26.2,18.676a3.709,3.709,0,0,0-1.68.25L18.547,21.3a36.119,36.119,0,0,0-5.832-1.013,2.935,2.935,0,0,0-2.448.888.47.47,0,0,0-.125.442.494.494,0,0,0,.307.346l3.427,1.2-3.9,1.55L7.041,22.9a.46.46,0,0,0-.4-.043l-1.31.442a.481.481,0,0,0-.23.739Z" fill="#666"/>
                        </g>
                    </svg>
                    <input type="text" class="amadex-field-value mc-from" placeholder="Departure City" autocomplete="off">
                    <input type="hidden" class="mc-from-code">
                </div>
                <div class="amadex-suggestions-dropdown mc-from-suggestions"></div>
            </div>

            <div class="amadex-modern-field amadex-location-field amadex-mc-field">
                <div class="amadex-field-input-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="11" viewBox="0 0 24.001 10.885">
                        <g transform="translate(-5.002 -18.663)">
                            <path d="M7.012,26.663a2.111,2.111,0,0,0,1.709.869,2.214,2.214,0,0,0,.5-.058c1.68-.408,4.81-1.186,7.843-2.026l-1.454,3.432a.472.472,0,0,0,.038.451.486.486,0,0,0,.4.216,4.05,4.05,0,0,0,3.12-1.464,17.671,17.671,0,0,0,2.707-4.071c.307-.106.6-.206.874-.3a25.818,25.818,0,0,0,5.707-2.486,1.349,1.349,0,0,0,.494-1.445,1.329,1.329,0,0,0-1.171-.965L26.2,18.676a3.709,3.709,0,0,0-1.68.25L18.547,21.3a36.119,36.119,0,0,0-5.832-1.013,2.935,2.935,0,0,0-2.448.888.47.47,0,0,0-.125.442.494.494,0,0,0,.307.346l3.427,1.2-3.9,1.55L7.041,22.9a.46.46,0,0,0-.4-.043l-1.31.442a.481.481,0,0,0-.23.739Z" fill="#666"/>
                        </g>
                    </svg>
                    <input type="text" class="amadex-field-value mc-to" placeholder="Arrival City" autocomplete="off">
                    <input type="hidden" class="mc-to-code">
                </div>
                <div class="amadex-suggestions-dropdown mc-to-suggestions"></div>
            </div>

            <div class="amadex-modern-field amadex-date-field amadex-mc-field">
                <div class="amadex-field-input-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="18" viewBox="0 0 17.143 18">
                        <g transform="translate(-4 -3)">
                            <rect width="2" height="3" rx="1" transform="translate(6.571 3)" fill="#666"/>
                            <rect width="2" height="3" rx="1" transform="translate(16.571 3)" fill="#666"/>
                            <path d="M4,11.143V21a1.714,1.714,0,0,0,1.714,1.714H19.429A1.714,1.714,0,0,0,21.143,21V11.143Zm5.143,8.571a.857.857,0,0,1-.857.857H7.429a.857.857,0,0,1-.857-.857v-.857A.857.857,0,0,1,7.429,18h.857a.857.857,0,0,1,.857.857Zm4.714,0a.857.857,0,0,1-.857.857h-.857a.857.857,0,0,1-.857-.857v-.857A.857.857,0,0,1,12.143,18H13a.857.857,0,0,1,.857.857Zm4.714,0a.857.857,0,0,1-.857.857h-.857A.857.857,0,0,1,16,19.714v-.857A.857.857,0,0,1,16.857,18h.857a.857.857,0,0,1,.857.857Zm0-4.714a.857.857,0,0,1-.857.857h-.857A.857.857,0,0,1,16,15v-.857a.857.857,0,0,1,.857-.857h.857a.857.857,0,0,1,.857.857Zm2.571-4.714V7.714A1.714,1.714,0,0,0,19.429,6H19v.429a1.714,1.714,0,0,1-3.429,0V6h-6v.429a1.714,1.714,0,1,1-3.429,0V6H5.714A1.714,1.714,0,0,0,4,7.714v2.571Z" transform="translate(0 -1.714)" fill="#666"/>
                        </g>
                    </svg>
                    <div class="amadex-field-value mc-date-display">Select Date</div>
                    <input type="date" class="mc-date" min="${minDate}" style="position:absolute;opacity:0;width:0;height:0;">
                </div>
            </div>

            <button type="button" class="remove-mc amadex-remove-mc-btn" title="Remove city" style="display:none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10">
                    <path d="M6.65,5,9.744,1.906a.875.875,0,0,0,0-1.237L9.331.256a.875.875,0,0,0-1.237,0L5,3.35,1.907.255a.875.875,0,0,0-1.237,0L.256.668a.875.875,0,0,0,0,1.237L3.351,5,.257,8.093a.875.875,0,0,0,0,1.237l.412.412a.875.875,0,0,0,1.237,0L5,6.649,8.094,9.743a.875.875,0,0,0,1.237,0l.412-.412a.875.875,0,0,0,0-1.237Z" fill="currentColor"/>
                </svg>
                Remove
            </button>

        </div>
    `;
}

// Add first row automatically when switching to multi
$(document).on('change', 'input[name="tripType"]', function () {
    const type = $('input[name="tripType"]:checked').val();
    if (type === 'multi-city') {
        // handled by handleTripTypeChange → addFlightSegment()
    }
});

// Add new city — calls the real addFlightSegment() which has full autocomplete + calendar
$(document).on('click', '#amadex-add-city', function () {
    // addFlightSegment is defined inside the jQuery IIFE — trigger via #add-city-btn
    $('#add-city-btn').trigger('click');
});

// Remove row
$(document).on('click', '.remove-mc', function () {
    $(this).closest('.amadex-multi-row').remove();
    updateMcRemoveButtons();
});

// Date display binding for mc rows
$(document).on('change', '.mc-date', function () {
    const val = $(this).val();
    if (!val) return;
    const d = new Date(val);
    if (isNaN(d)) return;
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const formatted = `${d.getDate()} ${months[d.getMonth()]}, ${String(d.getFullYear()).substr(2)}`;
    $(this).closest('.amadex-modern-field').find('.mc-date-display').text(formatted);
});

// Click on date display opens the hidden date input
$(document).on('click', '.mc-date-display', function () {
    $(this).closest('.amadex-modern-field').find('.mc-date').trigger('click').trigger('focus');
});

// Show/hide remove buttons — only show when 2+ rows exist
function updateMcRemoveButtons() {
    const $rows = $('#amadex-multicity-wrapper .amadex-multi-row');
    if ($rows.length > 1) {
        $rows.find('.remove-mc').show();
    } else {
        $rows.find('.remove-mc').hide();
    }
}

// Basic autocomplete for mc rows (reuses existing airport search)
function initMcRowAutocomplete($row) {
    let searchTimeout;

    $row.find('.mc-from').on('input', function () {
        const q = $(this).val();
        const $dropdown = $row.find('.mc-from-suggestions');
        const $codeInput = $row.find('.mc-from-code');
        clearTimeout(searchTimeout);
        if (q.length >= 2) {
            searchTimeout = setTimeout(() => searchMcAirports(q, $(this), $codeInput, $dropdown), 300);
        } else {
            $dropdown.removeClass('active').html('');
        }
    });

    $row.find('.mc-to').on('input', function () {
        const q = $(this).val();
        const $dropdown = $row.find('.mc-to-suggestions');
        const $codeInput = $row.find('.mc-to-code');
        clearTimeout(searchTimeout);
        if (q.length >= 2) {
            searchTimeout = setTimeout(() => searchMcAirports(q, $(this), $codeInput, $dropdown), 300);
        } else {
            $dropdown.removeClass('active').html('');
        }
    });

    // Close dropdowns on outside click
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.amadex-mc-field').length) {
            $row.find('.amadex-suggestions-dropdown').removeClass('active').html('');
        }
    });
}

function searchMcAirports(keyword, $input, $codeInput, $dropdown) {
    $.ajax({
        url: typeof AmadexConfig !== 'undefined' ? AmadexConfig.ajaxUrl : '/wp-admin/admin-ajax.php',
        type: 'POST',
        data: {
            action: 'amadex_search_airports',
            keyword: keyword,
            nonce: typeof AmadexConfig !== 'undefined' ? AmadexConfig.nonce : ''
        },
        success: function (response) {
            if (!response.success || !response.data || !response.data.length) {
                $dropdown.html('<div style="padding:12px;color:#999;font-size:13px;">No airports found</div>').addClass('active');
                return;
            }
            let html = '<div class="amadex-suggestions-scroll">';
            response.data.forEach(function (airport) {
                html += `<div class="amadex-suggestion-item mc-airport-pick"
                    data-code="${airport.code}"
                    data-city="${airport.city}"
                    data-name="${airport.name}">
                    <div class="amadex-suggestion-content">
                        <div class="amadex-suggestion-city">${airport.city}</div>
                        <div class="amadex-suggestion-airport">${airport.name}</div>
                    </div>
                    <div class="amadex-suggestion-code">${airport.code}</div>
                </div>`;
            });
            html += '</div>';
            $dropdown.html(html).addClass('active');

            $dropdown.find('.mc-airport-pick').on('click', function (e) {
                e.stopPropagation();
                const city = $(this).data('city');
                const code = $(this).data('code');
                $input.val(`${city} (${code})`);
                $codeInput.val(code);
                $dropdown.removeClass('active').html('');
            });
        }
    });
}

$(window).on('load', function() {
    revealVsbSearchBar();
});

setTimeout(function() {
    revealVsbSearchBar();
}, 2000);

function revealVsbSearchBar() {
    const $loader = $('#vsb-loader');
    const $vsb = $('.vsb-wrap.vsb-loading');

    if ($loader.length && $vsb.length) {
        $loader.fadeOut(200, function() {
            $vsb.removeClass('vsb-loading').hide().fadeIn(300);
        });
    }
}


})(jQuery);