/**
 * Amadex Airline Price Matrix
 */

(function($) {
    'use strict';

    /**
     * Build airline price matrix
     */
    window.amadexBuildMatrix = function(flights) {
        if (!flights || flights.length === 0) return;

        const matrix = {};
        const airlines = new Set();

        // Collect data
        flights.forEach(flight => {
            const airlineCode = flight.validating_airline_codes ? flight.validating_airline_codes[0] : '';
            if (!airlineCode) return;

            airlines.add(airlineCode);

            const stops = getStops(flight);
            const stopKey = stops === 0 ? 'nonstop' : '1plus';
            const price = parseFloat(flight.price.total);

            if (!matrix[airlineCode]) {
                matrix[airlineCode] = { nonstop: null, '1plus': null };
            }

            if (!matrix[airlineCode][stopKey] || price < matrix[airlineCode][stopKey]) {
                matrix[airlineCode][stopKey] = price;
            }
        });

        // Sort airlines by cheapest price
        const sortedAirlines = Array.from(airlines).sort((a, b) => {
            const priceA = Math.min(matrix[a].nonstop || 999999, matrix[a]['1plus'] || 999999);
            const priceB = Math.min(matrix[b].nonstop || 999999, matrix[b]['1plus'] || 999999);
            return priceA - priceB;
        }).slice(0, 5); // Top 5 airlines

        renderMatrix(sortedAirlines, matrix);
    };

    /**
     * Render matrix HTML
     */
    function renderMatrix(airlines, matrix) {
        const container = $('#amadex-airline-matrix');
        if (!container.length) return;

        let html = '<table class="amadex-matrix-table"><thead><tr><th>Airline</th>';

        // Header - airline names
        airlines.forEach(code => {
            const name = getAirlineName(code);
            const logo = getAirlineLogo(code);
            html += `<th><div class="amadex-matrix-airline"><img src="${logo}" alt="${name}" onerror="this.style.display='none'"><span>${name}</span></div></th>`;
        });

        html += '</tr></thead><tbody>';

        // Non Stop row
        html += '<tr><td class="amadex-matrix-label">Non Stop</td>';
        airlines.forEach(code => {
            const price = matrix[code].nonstop;
            html += `<td>${price ? '$' + price.toFixed(2) : '—'}</td>`;
        });
        html += '</tr>';

        // 1+ Stops row
        html += '<tr><td class="amadex-matrix-label">1+ Stops</td>';
        airlines.forEach(code => {
            const price = matrix[code]['1plus'];
            html += `<td>${price ? '$' + price.toFixed(2) : '—'}</td>`;
        });
        html += '</tr>';

        html += '</tbody></table>';

        container.html(html);
    }

    /**
     * Get stops count
     */
    function getStops(flight) {
        if (!flight.itineraries || !flight.itineraries[0] || !flight.itineraries[0].segments) {
            return 0;
        }
        return flight.itineraries[0].segments.length - 1;
    }

    /**
     * Get airline name
     */
    function getAirlineName(code) {
        const names = {
            'AA': 'Alaska Airlines',
            'KL': 'KLM',
            'UA': 'United Airlines',
            'AP': 'Apg Airlines',
            'LH': 'Lufthansa',
            'AI': 'Air India',
            'EK': 'Emirates',
            'QR': 'Qatar Airways',
            'BA': 'British Airways',
            'AF': 'Air France'
        };
        return names[code] || code;
    }

    /**
     * Get airline logo from Amadeus API airline codes (IATA format)
     */
    function getAirlineLogo(code) {
        if (!code || code === 'N/A' || code.trim() === '') {
            return 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64"%3E%3Crect fill="%23e0e0e0" width="64" height="64"/%3E%3Ctext x="50%25" y="50%25" dominant-baseline="middle" text-anchor="middle" font-family="Arial" font-size="12" fill="%23999"%3EN/A%3C/text%3E%3C/svg%3E';
        }
        
        const normalizedCode = code.trim().toUpperCase();
        // Primary source: Kiwi.com CDN (most reliable for Amadeus IATA airline codes)
        return `https://images.kiwi.com/airlines/64/${normalizedCode}.png`;
    }
    
    /**
     * Get fallback airline logo URL
     */
    function getAirlineLogoFallback(code) {
        if (!code || code === 'N/A' || code.trim() === '') {
            return getAirlineLogo('');
        }
        const normalizedCode = code.trim().toUpperCase();
        // Fallback to Google Flights CDN (highly reliable secondary source)
        return `https://www.gstatic.com/flights/airline_logos/70px/${normalizedCode}.png`;
    }

})(jQuery);

