/**
 * Amadex Frontend JavaScript
 */
(function($) {
    'use strict';
    
    // Initialize datepickers
    function initDatepickers() {
        let today = new Date();
        
        $('.amadex-datepicker').datepicker({
            dateFormat: 'yy-mm-dd',
            minDate: today,
            changeMonth: true,
            changeYear: true,
            showButtonPanel: true
        });
        
        // Ensure return date is after departure date
        $('#amadex-departure-date').datepicker('option', 'onSelect', function(selectedDate) {
            let departureDate = new Date(selectedDate);
            $('#amadex-return-date').datepicker('option', 'minDate', departureDate);
        });
    }
    
    // Initialize airport autocomplete
    function initAirportAutocomplete() {
        console.log('Initializing airport autocomplete...');
        
        $('.amadex-airport-search').each(function() {
            const $input = $(this);
            const $wrapper = $input.closest('.amadex-autocomplete-wrapper');
            const $results = $wrapper.find('.amadex-autocomplete-results');
            const $hidden = $wrapper.find('input[type="hidden"]');
            
            // Debug info
            console.log('Input element:', $input.attr('id'));
            console.log('Results element found:', $results.length > 0);
            console.log('Hidden element found:', $hidden.length > 0);
            
            let debounceTimer;
            
            // Function to show autocomplete results
            function showResults(airports) {
                console.log('Showing results:', airports);
                $results.empty();
                
                if (airports.length === 0) {
                    $results.append('<div class="amadex-no-results">No airports found</div>');
                } else {
                    $.each(airports, function(index, airport) {
                        const $item = $('<div class="amadex-autocomplete-item"></div>');
                        
                        $item.html(
                            '<strong>' + airport.code + '</strong> - ' + 
                            airport.city + ', ' + 
                            airport.name + ' (' + airport.country + ')'
                        );
                        
                        // Store data for selection
                        $item.data('airport', airport);
                        
                        // Add click event
                        $item.on('click', function() {
                            selectAirport(airport);
                        });
                        
                        $results.append($item);
                    });
                }
                
                // Show results with explicit styling
                $results.css({
                    'display': 'block',
                    'position': 'absolute',
                    'z-index': '9999',
                    'width': '100%',
                    'max-height': '250px',
                    'overflow-y': 'auto',
                    'background': 'white',
                    'border': '1px solid #ddd',
                    'box-shadow': '0 4px 6px rgba(0, 0, 0, 0.1)'
                }).show();
            }
            
            // Function to select an airport
            function selectAirport(airport) {
                console.log('Selecting airport:', airport);
                
                // Update hidden field with airport code
                $hidden.val(airport.code);
                
                // Display the selected airport
                $input.val(airport.code + ' - ' + airport.city);
                
                // Hide results
                $results.hide();
            }
            
            // Show sample airports (for testing)
            function showSampleAirports() {
                showResults([
                    {
                        code: 'JFK',
                        name: 'John F. Kennedy International Airport',
                        city: 'New York',
                        country: 'United States'
                    },
                    {
                        code: 'LAX',
                        name: 'Los Angeles International Airport',
                        city: 'Los Angeles',
                        country: 'United States'
                    },
                    {
                        code: 'LHR',
                        name: 'Heathrow Airport',
                        city: 'London',
                        country: 'United Kingdom'
                    }
                ]);
            }
            
            // Input keyup event for searching
            $input.on('input', function() {
                const searchTerm = $(this).val().trim();
                console.log('Search term:', searchTerm);
                
                // If input is cleared, reset the selection
                if (searchTerm === '') {
                    $hidden.val('');
                    $results.hide();
                    return;
                }
                
                // Clear previous timer
                clearTimeout(debounceTimer);
                
                // Minimum 2 characters for search
                if (searchTerm.length < 2) {
                    $results.hide();
                    return;
                }
                
                // Set debounce timer to avoid too many requests
                debounceTimer = setTimeout(function() {
                    // Show loading indicator
                    $results.html('<div class="amadex-loading-results">Searching...</div>').show();
                    
                    // Make direct AJAX request without using REST API
                    $.ajax({
                        url: amadex_params.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'amadex_search_airports',
                            term: searchTerm,
                            nonce: amadex_params.nonce
                        },
                        success: function(response) {
                            console.log('AJAX response:', response);
                            
                            if (response.success && response.data) {
                                showResults(response.data);
                            } else {
                                // Fallback to sample airports
                                console.log('No airports found or error. Using sample airports.');
                                showSampleAirports();
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX error:', error);
                            
                            // Fallback to sample airports
                            console.log('AJAX error. Using sample airports.');
                            showSampleAirports();
                        }
                    });
                }, 300);
            });
            
            // Double-click for testing
            $input.on('dblclick', function() {
                console.log('Double-click detected. Showing sample airports.');
                showSampleAirports();
            });
            
            // Hide results when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.amadex-autocomplete-wrapper').length) {
                    $results.hide();
                }
            });
            
            // Handle keyboard events
            $input.on('keydown', function(e) {
                if (e.keyCode === 27) { // Escape key
                    $results.hide();
                }
            });
        });
    }
    
    // Handle form submission
    function handleFormSubmission() {
        $('#amadex-flight-search-form').on('submit', function(e) {
            e.preventDefault();
            
            // Validate required fields
            const origin = $('#amadex-origin').val();
            const destination = $('#amadex-destination').val();
            const departureDate = $('#amadex-departure-date').val();
            
            // Check if all required fields are provided
            if (!origin || !destination || !departureDate) {
                alert('Please fill in all required fields');
                return;
            }
            
            // Show loading indicator
            $('#amadex-loading').show();
            $('#amadex-flight-results').hide();
            
            // Get form data
            let formData = $(this).serialize();
            
            // Add action and nonce to the form data
            formData += '&action=amadex_search_flights';
            formData += '&nonce=' + amadex_params.nonce;
            
            console.log('Sending flight search request with data:', formData);
            
            // Send AJAX request
            $.ajax({
                url: amadex_params.ajax_url,
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    // Hide loading indicator
                    $('#amadex-loading').hide();
                    $('#amadex-flight-results').show();
                    
                    console.log('Flight search response:', response);
                    
                    if (response.success) {
                        displayFlightResults(response.data);
                    } else {
                        $('#amadex-flight-results').html('<div class="amadex-error">' + (response.data.message || amadex_params.error_message) + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    // Hide loading indicator
                    $('#amadex-loading').hide();
                    $('#amadex-flight-results').show();
                    
                    console.error('Flight search error:', error);
                    console.error('Response:', xhr.responseText || 'No response text');
                    
                    // Display error message
                    $('#amadex-flight-results').html('<div class="amadex-error">' + amadex_params.error_message + '</div>');
                }
            });
        });
    }
    
    // Display flight search results
    function displayFlightResults(data) {
        const $resultsContainer = $('#amadex-flight-results');
        
        // Clear previous results
        $resultsContainer.empty();
        
        console.log('Displaying flight results:', data);
        
        // Check if we have results
        if (!data || !data.data || data.data.length === 0) {
            $resultsContainer.html('<div class="amadex-no-results">' + amadex_params.no_results_message + '</div>');
            return;
        }
        
        // Create results header
        const flightCount = data.meta ? data.meta.count : data.data.length;
        let resultsHeader = $('<div class="amadex-results-header"></div>');
        resultsHeader.append('<h2>' + flightCount + ' ' + (flightCount === 1 ? 'Flight' : 'Flights') + ' Found</h2>');
        $resultsContainer.append(resultsHeader);
        
        // Create flight cards container
        const $flightCardsContainer = $('<div class="amadex-flight-cards"></div>');
        $resultsContainer.append($flightCardsContainer);
        
        // Loop through flights and create flight cards
        $.each(data.data, function(index, flight) {
            // Create flight card
            const $flightCard = $('<div class="amadex-flight-card"></div>');
            
            // Get first itinerary (outbound)
            const itinerary = flight.itineraries && flight.itineraries.length > 0 ? flight.itineraries[0] : null;
            
            if (itinerary && itinerary.segments && itinerary.segments.length > 0) {
                const firstSegment = itinerary.segments[0];
                const lastSegment = itinerary.segments[itinerary.segments.length - 1];
                
                // Price badge
                $flightCard.append('<div class="amadex-price-badge">' + 
                    flight.price.currency + ' ' + parseFloat(flight.price.total).toFixed(2) + 
                    '</div>');
                
                // Create itinerary section
                const $itinerary = $('<div class="amadex-itinerary"></div>');
                $itinerary.append('<div class="amadex-itinerary-header">Outbound Flight</div>');
                
                // Route summary
                const $routeSummary = $('<div class="amadex-route-summary"></div>');
                
                // Departure info
                const departureTime = new Date(firstSegment.departure.at);
                const departureCode = firstSegment.departure.iataCode;
                $routeSummary.append(
                    '<div class="amadex-departure-info">' +
                    '<div class="amadex-airport-code">' + departureCode + '</div>' +
                    '<div class="amadex-time">' + formatTime(departureTime) + '</div>' +
                    '<div class="amadex-date">' + formatDate(departureTime) + '</div>' +
                    '</div>'
                );
                
                // Flight info
                const stops = itinerary.segments.length - 1;
                const duration = itinerary.duration ? formatDuration(itinerary.duration) : '';
                $routeSummary.append(
                    '<div class="amadex-flight-info">' +
                    '<div class="amadex-duration">' + duration + '</div>' +
                    '<div class="amadex-stops">' + (stops === 0 ? 'Direct' : stops + ' stop' + (stops > 1 ? 's' : '')) + '</div>' +
                    '<div class="amadex-flight-line">' +
                    '<div class="amadex-line"></div>' +
                    (stops > 0 ? generateStopPoints(stops) : '') +
                    '<div class="amadex-line"></div>' +
                    '</div>' +
                    '</div>'
                );
                
                // Arrival info
                const arrivalTime = new Date(lastSegment.arrival.at);
                const arrivalCode = lastSegment.arrival.iataCode;
                $routeSummary.append(
                    '<div class="amadex-arrival-info">' +
                    '<div class="amadex-airport-code">' + arrivalCode + '</div>' +
                    '<div class="amadex-time">' + formatTime(arrivalTime) + '</div>' +
                    '<div class="amadex-date">' + formatDate(arrivalTime) + '</div>' +
                    '</div>'
                );
                
                $itinerary.append($routeSummary);
                
                // Segments details (initially hidden)
                const $segmentsDetails = $('<div class="amadex-segments-details" style="display:none;"></div>');
                
                $.each(itinerary.segments, function(segIndex, segment) {
                    const $segment = $('<div class="amadex-segment"></div>');
                    
                    // Airline info
                    $segment.append(
                        '<div class="amadex-airline">' +
                        '<span class="amadex-carrier-code">' + segment.carrierCode + '</span>' +
                        '<span class="amadex-flight-number">Flight ' + segment.number + '</span>' +
                        '</div>'
                    );
                    
                    // Segment details
                    const departureDateTime = new Date(segment.departure.at);
                    const arrivalDateTime = new Date(segment.arrival.at);
                    
                    $segment.append(
                        '<div class="amadex-segment-details">' +
                        '<div>' + segment.departure.iataCode + ' ' + formatTime(departureDateTime) + ' → ' + 
                        segment.arrival.iataCode + ' ' + formatTime(arrivalDateTime) + '</div>' +
                        '<div>' + formatDate(departureDateTime) + '</div>' +
                        '<div>Duration: ' + formatDuration(segment.duration) + '</div>' +
                        '</div>'
                    );

                    // 27 oct 2025

                    $segment.append(
                        '<button type="submit" class="btn-more">Learn More</button>',
                        '<div class="append-class-btn"> + segment.departure.iatacode</div>',
                    )
                    
                    $segmentsDetails.append($segment);
                });
                
                $itinerary.append($segmentsDetails);
                
                // Toggle details button
                const $detailsToggle = $('<button class="amadex-details-toggle">Show details</button>');
                $detailsToggle.on('click', function() {
                    $segmentsDetails.toggle();
                    $(this).text($segmentsDetails.is(':visible') ? 'Hide details' : 'Show details');
                });
                
                $itinerary.append($detailsToggle);
                
                $flightCard.append($itinerary);
                
                // Return itinerary if exists
                if (flight.itineraries.length > 1) {
                    const returnItinerary = flight.itineraries[1];
                    
                    // Create return itinerary section (similar to outbound)
                    // Code would be similar to the outbound itinerary
                }
                
                // Select flight button
                $flightCard.append('<button class="amadex-select-flight">Select this flight</button>');
            } else {
                // Fallback if no itinerary details
                $flightCard.append('<div class="amadex-flight-summary">' +
                    '<div class="amadex-flight-price">' + flight.price.currency + ' ' + parseFloat(flight.price.total).toFixed(2) + '</div>' +
                    '<div class="amadex-flight-code">' + flight.validatingAirlineCodes.join(', ') + '</div>' +
                    '</div>');
            }
            
            // Add flight card to container
            $flightCardsContainer.append($flightCard);
        });
        
        // Helper function to format time
        function formatTime(date) {
             return date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        }
        //      return date.toLocaleTimeString('en-US', {
        //         hour: '2-digit',
        //         minute: '2-digit',
        //         hour12: true
        //     });
        // }
        
        // Helper function to format date
        function formatDate(date) {
            return date.toLocaleDateString([], {weekday: 'short', month: 'short', day: 'numeric'});
        }
        
        // Helper function to format duration
        function formatDuration(durationString) {
            // Parse PT5H30M format to "5h 30m"
            if (!durationString) return '';
            
            const hours = durationString.match(/(\d+)H/);
            const minutes = durationString.match(/(\d+)M/);
            
            let result = '';
            if (hours) result += hours[1] + 'h ';
            if (minutes) result += minutes[1] + 'm';
            
            return result.trim();
        }
        
        // Helper function to generate stop points
        function generateStopPoints(stops) {
            let stopPoints = '';
            for (let i = 0; i < stops; i++) {
                stopPoints += '<div class="amadex-stop"></div>';
            }
            return stopPoints;
        }
    }
    
    // Initialize on document ready
    $(document).ready(function() {
        console.log('Amadex: Document ready');
        
        try {
            // Initialize components
            initDatepickers();
            initAirportAutocomplete();
            handleFormSubmission();
            
            console.log('Amadex: Initialization complete');
        } catch (error) {
            console.error('Amadex: Error during initialization:', error);
        }
    });
    
})(jQuery);