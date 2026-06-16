/**
 * Amadex Travel Deals JavaScript
 * Handles tabs, slider, and AJAX loading
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        initDealsSection();
    });

    /**
     * Initialize deals section
     */
    function initDealsSection() {
        $('.amadex-deals-section').each(function() {
            const $section = $(this);
            const $tabs = $section.find('.amadex-deal-tab');
            const $prevBtn = $section.find('.amadex-deals-prev');
            const $nextBtn = $section.find('.amadex-deals-next');
            const $grid = $section.find('.amadex-deals-grid');
            
            let currentPage = 0;
            let dealsPerPage = 4;
            let totalDeals = [];
            
            // Load initial deals
            if ($tabs.length > 0) {
                const firstDestination = $tabs.first().data('destination');
                loadDeals($section, firstDestination);
            }
            
            // Tab click handler
            $tabs.on('click', function() {
                const $tab = $(this);
                const destination = $tab.data('destination');
                
                // Update active tab
                $tabs.removeClass('active');
                $tab.addClass('active');
                
                // Reset pagination
                currentPage = 0;
                
                // Load deals for selected destination
                loadDeals($section, destination);
            });
            
            // Slider navigation
            $prevBtn.on('click', function() {
                if (currentPage > 0) {
                    currentPage--;
                    updateDisplayedDeals($grid, totalDeals, currentPage, dealsPerPage);
                    updateSliderButtons($prevBtn, $nextBtn, currentPage, totalDeals.length, dealsPerPage);
                }
            });
            
            $nextBtn.on('click', function() {
                const maxPage = Math.ceil(totalDeals.length / dealsPerPage) - 1;
                if (currentPage < maxPage) {
                    currentPage++;
                    updateDisplayedDeals($grid, totalDeals, currentPage, dealsPerPage);
                    updateSliderButtons($prevBtn, $nextBtn, currentPage, totalDeals.length, dealsPerPage);
                }
            });
            
            // Store reference for slider
            $section.data('sliderData', {
                currentPage: currentPage,
                dealsPerPage: dealsPerPage,
                totalDeals: totalDeals,
                updateDisplay: function() {
                    updateDisplayedDeals($grid, this.totalDeals, this.currentPage, this.dealsPerPage);
                    updateSliderButtons($prevBtn, $nextBtn, this.currentPage, this.totalDeals.length, this.dealsPerPage);
                }
            });
        });
    }

    /**
     * Load deals via AJAX
     */
    function loadDeals($section, destination) {
        const $loader = $section.find('.amadex-deals-loader');
        const $grid = $section.find('.amadex-deals-grid');
        const $error = $section.find('.amadex-deals-error');
        const priceLimit = $section.data('price-limit') || 300;
        const maxDeals = $grid.data('max-deals') || 8;
        
        // Show loader
        $loader.show();
        $grid.removeClass('loaded').empty();
        $error.hide();
        
        $.ajax({
            url: typeof AmadexConfig !== 'undefined' ? AmadexConfig.ajaxUrl : '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: {
                action: 'amadex_get_deals',
                destination: destination,
                price_limit: priceLimit,
                max_deals: maxDeals
            },
            success: function(response) {
                $loader.hide();
                
                if (response.success && response.data.deals) {
                    const sliderData = $section.data('sliderData');
                    sliderData.totalDeals = response.data.deals;
                    sliderData.currentPage = 0;
                    
                    renderDeals($grid, response.data.deals);
                    sliderData.updateDisplay();
                } else {
                    $error.show();
                }
            },
            error: function() {
                $loader.hide();
                $error.show();
            }
        });
    }

    /**
     * Render deals to grid
     */
    function renderDeals($grid, deals) {
        $grid.empty();
        
        deals.forEach(function(deal) {
            const $card = createDealCard(deal);
            $grid.append($card);
        });
        
        // Add loaded class for fade-in animation
        setTimeout(function() {
            $grid.addClass('loaded');
        }, 50);
    }

    
    /**
     * Create deal card HTML
     */
    function createDealCard(deal) {
        const departDate = formatDate(deal.depart_date);
        const returnDate = formatDate(deal.return_date);
        const airlineLogo = getAirlineLogo(deal.airline.code);
        const price = formatPrice(deal.price, deal.currency);
        
        const $card = $(`
            <div class="amadex-deal-card" data-deal='${JSON.stringify(deal)}'>
                <div class="amadex-deal-header">
                    <div class="amadex-deal-airline">
                        <div class="amadex-airline-logo">
                            ${airlineLogo ? `<img src="${airlineLogo}" alt="${deal.airline.name}">` : deal.airline.code}
                        </div>
                        <div class="amadex-airline-name">${deal.airline.name}</div>
                    </div>
                    <div class="amadex-deal-dates">
                        ${departDate}<br>
                        ${returnDate}<br>
                        
                    </div>
                </div>
                
                <div class="amadex-deal-route">
                    <div class="amadex-route-visual">
                        <div class="amadex-route-point">
                            <div class="amadex-route-code">${deal.origin.code}</div>
                            <div class="amadex-route-city">${deal.origin.city}</div>
                        </div>
                        <div class="amadex-route-line">
                            <div class="amadex-route-origin-dot"></div>
                            <div class="amadex-route-icon"></div>
                        </div>
                        <div class="amadex-route-point">
                            <div class="amadex-route-code">${deal.destination.code}</div>
                            <div class="amadex-route-city">${deal.destination.city}</div>
                        </div>
                    </div>
                </div>
                
                <div class="amadex-deal-price-wrap">
                    <span class="amadex-deal-price-label">Starting from</span>
                    <button class="amadex-deal-price-btn">${price}</button>
                </div>
            </div>
        `);
        
        // Click handler
        $card.on('click', function() {
            handleDealClick(deal);
        });
        
        return $card;
    }

    /**
     * Update displayed deals for pagination
     */
    function updateDisplayedDeals($grid, deals, page, perPage) {
        const start = page * perPage;
        const end = start + perPage;
        const pageDeals = deals.slice(start, end);
        
        renderDeals($grid, pageDeals);
    }

    /**
     * Update slider button states
     */
    function updateSliderButtons($prevBtn, $nextBtn, currentPage, totalDeals, perPage) {
        const maxPage = Math.ceil(totalDeals / perPage) - 1;
        
        $prevBtn.prop('disabled', currentPage === 0);
        $nextBtn.prop('disabled', currentPage >= maxPage);
    }

    /**
     * Handle deal card click
     */
    function handleDealClick(deal) {
        // Build search URL with deal parameters
        const resultsPage = '/flight-results/'; // Adjust this to your results page URL
        
        const params = new URLSearchParams({
            origin_name: deal.origin.city,
            origin_iata: deal.origin.code,
            destination_name: deal.destination.city,
            destination_iata: deal.destination.code,
            depart_date: deal.depart_date,
            return_date: deal.return_date,
            one_way: 'No',
            adults: 1,
            children: 0,
            infants: 0,
            currency: deal.currency,
            language: 'en',
            lang: 'en',
            isDomestic: 'No',
            cabin: 'Economy'
        });
        
        // Redirect to search results
        window.location.href = resultsPage + '?' + params.toString();
    }

    /**
     * Format date
     */
    function formatDate(dateString) {
        if (!dateString) return '';
        
        const date = new Date(dateString);
        const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        return `${days[date.getDay()]}, ${date.getDate()} ${months[date.getMonth()]}`;
    }

    /**
     * Format price
     */
    function formatPrice(amount, currency) {
        const symbols = {
            'USD': '$',
            'EUR': '€',
            'GBP': '£',
            'INR': '₹'
        };
        
        const symbol = symbols[currency] || '$';
        return symbol + parseFloat(amount).toFixed(2);
    }

    /**
     * Get airline logo URL from Amadeus API airline codes (IATA format)
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






















