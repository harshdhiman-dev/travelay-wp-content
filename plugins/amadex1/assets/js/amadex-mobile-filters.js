/**
 * Amadex Mobile Filters
 * Mobile filter modal and sort functionality
 */


(function($) {
    'use strict';

    /**
     * Initialize mobile filter functionality
     */
    function initMobileFilters() {
        // Initialize on mobile and tablet devices (320px to 1024px)
        const isMobileOrTablet = window.innerWidth <= 1024;
        
        if (!isMobileOrTablet) {
            // Hide mobile sort bar on desktop
            $('.amadex-mobile-sort-bar').hide();
            return;
        }

        // Create mobile filter modal if it doesn't exist
        if ($('#amadex-mobile-filter-modal').length === 0) {
            createMobileFilterModal();
        }

        // Check if we're on results page (multiple ways to detect)
        const isResultsPage = $('#amadex-results-page').length > 0 || 
                             $('.amadex-booking-page').length > 0 || 
                             $('.amadex-flight-card').length > 0 ||
                             $('.amadex-flights-list').length > 0 ||
                             $('#amadex-flight-cards-container').length > 0 ||
                             window.location.pathname.includes('flight-results') ||
                             window.location.pathname.includes('results');
        
        // Create mobile sort bar if it doesn't exist and we're on results page
        if (isResultsPage) {
        if ($('.amadex-mobile-sort-bar').length === 0) {
            createMobileSortBar();
        }
            // Always show sort bar on results page
            $('.amadex-mobile-sort-bar').show().css({
                'display': 'block',
                'visibility': 'visible',
                'opacity': '1'
            });
        } else {
            // Hide sort bar if not on results page
            $('.amadex-mobile-sort-bar').hide();
        }

        // Bind events (always bind, even if sort bar doesn't exist yet)
        bindMobileFilterEvents();
        
        console.log('Mobile filters initialized', {
            isMobile: isMobileOrTablet,
            isResultsPage: isResultsPage,
            sortBarExists: $('.amadex-mobile-sort-bar').length > 0,
            flightCards: $('.amadex-flight-card').length
        });
    }

    /**
     * Create mobile filter modal
     */
    function createMobileFilterModal() {
        const $sidebar = $('.amadex-filters-sidebar');
        if ($sidebar.length === 0) {
            // Wait a bit and try again if sidebar not ready
            setTimeout(function() {
                if ($('.amadex-filters-sidebar').length > 0) {
                    createMobileFilterModal();
                }
            }, 500);
            return;
        }

        // Remove existing modal if any
        $('#amadex-mobile-filter-modal, .amadex-mobile-filter-overlay').remove();

        // Create overlay
        const $overlay = $('<div class="amadex-mobile-filter-overlay"></div>');
        
        // Create modal container
        const $modal = $('<div class="amadex-mobile-filter-modal" id="amadex-mobile-filter-modal"></div>');
        
        // Create header
        const $header = $(`
            <div class="amadex-mobile-filter-header">
                <div>
                    <h3 class="amadex-mobile-filter-title">Filters</h3>
                    <div class="amadex-mobile-filter-count">
                        <span id="amadex-mobile-results-count">0</span> Flights Available
                    </div>
                </div>
                <button class="amadex-mobile-filter-close" type="button">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">
  <g id="Group_179" data-name="Group 179" transform="translate(-340 -317)">
    <g id="Ellipse_9" data-name="Ellipse 9" transform="translate(340 317)" fill="#fff" stroke="#707070" stroke-width="1">
      <circle cx="9" cy="9" r="9" stroke="none"/>
      <circle cx="9" cy="9" r="8.5" fill="none"/>
    </g>
    <g id="Group_146" data-name="Group 146" transform="translate(344.979 321.979)">
      <path id="Path_12" data-name="Path 12" d="M4.881,4.261,8.093,1.048A.534.534,0,1,0,7.337.293L4.125,3.505.913.293a.534.534,0,0,0-.756.756L3.369,4.261.157,7.473a.534.534,0,1,0,.756.756L4.125,5.017,7.337,8.229a.534.534,0,0,0,.756-.756Zm0,0" transform="translate(0 -0.136)" fill="#707070"/>
    </g>
  </g>
</svg></button>
            </div>
        `);

        // Deep clone the entire sidebar content with all filters - preserve ALL desktop structure
        const $filterContent = $sidebar.clone(true, true);
        
        // Add class to identify as mobile modal content
        $filterContent.addClass('amadex-mobile-filter-content');
        
        // Remove sidebar-specific positioning but keep ALL desktop filter styles
        $filterContent.removeClass('amadex-filters-sidebar');
        
        // Only remove positioning styles, keep all other desktop filter styles intact
        $filterContent.css({
            'position': 'static',
            'top': 'auto',
            'height': 'auto',
            'box-shadow': 'none',
            'border-radius': '0',
            'padding': '0',
            'background': 'transparent',
            'display': 'block',
            'visibility': 'visible',
            'opacity': '1',
            'width': '100%',
            'max-width': '100%'
        });
        
        // Preserve all computed styles from desktop
        $filterContent.find('*').each(function() {
            const $el = $(this);
            // Don't override inline styles that are important for filters
            if (!$el.attr('style') || !$el.attr('style').includes('display: none')) {
                $el.css({
                    'visibility': 'visible',
                    'opacity': '1'
                });
            }
        });
        
        // Remove the desktop header but keep everything else
        $filterContent.find('.amadex-filters-header').remove();
        
        // Preserve all IDs but store original for reference
        $filterContent.find('[id]').each(function() {
            const $el = $(this);
            const id = $el.attr('id');
            if (id && !id.includes('mobile')) {
                // Store original ID for syncing
                $el.attr('data-original-id', id);
                // Keep the same ID so filters work properly
                // $el.attr('id', 'mobile-' + id);
            }
        });
        
        // Ensure ALL filter groups and elements are visible
        $filterContent.find('.amadex-filter-group').each(function() {
            $(this).show().css({
                'display': 'block',
                'visibility': 'visible',
                'opacity': '1'
            });
        });
        
        $filterContent.find('.amadex-filter-options').each(function() {
            $(this).show().css({
                'display': 'flex',
                'visibility': 'visible',
                'opacity': '1'
            });
        });
        
        $filterContent.find('.amadex-filter-tags').show();
        $filterContent.find('.amadex-filter-chip-grid').show();
        $filterContent.find('.amadex-filter-time-grid').show();
        $filterContent.find('.amadex-price-range').show();
        $filterContent.find('.amadex-duration-range').show();
        $filterContent.find('.amadex-price-slider').show();
        $filterContent.find('.amadex-duration-slider').show();
        
        // Ensure all inputs, selects, buttons are enabled and visible
        $filterContent.find('input, select, button, label').each(function() {
            $(this).prop('disabled', false).show().css({
                'display': '',
                'visibility': 'visible',
                'opacity': '1'
            });
        });
        
        // Ensure all SVG icons are visible
        $filterContent.find('svg').show();
        
        // Ensure time cards are visible
        $filterContent.find('.amadex-time-card').show();
        
        // Ensure sliders and tracks are visible
        $filterContent.find('.amadex-price-slider-track, .amadex-duration-slider-track').show();
        
        // Ensure switch toggles are visible
        $filterContent.find('.amadex-switch').show();
        
        // Ensure all text elements are visible
        $filterContent.find('span, div, p, h4').css({
            'display': '',
            'visibility': 'visible',
            'opacity': '1'
        });

        // Create apply button
        const $applySection = $(`
            <div class="amadex-mobile-filter-apply">
                <button type="button" class="amadex-mobile-filter-apply-btn">Apply Filters</button>
            </div>
        `);

        // Assemble modal
        $modal.append($header);
        $modal.append($filterContent);
        $modal.append($applySection);

        // Add to body
        $('body').append($overlay);
        $('body').append($modal);

        // Re-bind filter events for cloned elements
        setTimeout(function() {
            rebindFilterEventsInModal();
            // Sync filters from desktop
            syncFiltersToMobile();
        }, 150);
    }

    /**
     * Re-bind filter events in mobile modal
     */
    function rebindFilterEventsInModal() {
        const $modal = $('#amadex-mobile-filter-modal');
        if ($modal.length === 0) return;

        // Re-bind all filter inputs
        $modal.find('input[type="checkbox"]').off('change').on('change', function() {
            const $input = $(this);
            const name = $input.attr('name');
            const value = $input.val();
            
            // Sync to desktop sidebar
            $('.amadex-filters-sidebar').find(`input[name="${name}"][value="${value}"]`).prop('checked', $input.is(':checked'));
            
            // Trigger filter refresh
            if (window.refreshFilterOptionStates) {
                window.refreshFilterOptionStates();
            }
            
            // Apply filters
            if (window.applyFilters) {
                window.applyFilters();
            }
        });

        // Re-bind sliders
        $modal.find('input[type="range"]').off('input').on('input', function() {
            const $slider = $(this);
            const originalId = $slider.attr('data-original-id') || $slider.attr('id');
            const val = $slider.val();
            
            // Sync to desktop using original ID
            const $desktopSlider = $(`#${originalId}`);
            if ($desktopSlider.length) {
                $desktopSlider.val(val).trigger('input');
            }
            
            // Update display in both modal and desktop
            if (originalId === 'amadex-price-min' || originalId && originalId.includes('price-min')) {
                $modal.find('#amadex-price-min-display, [data-original-id="amadex-price-min-display"]').text('$' + val);
                $('#amadex-price-min-display').text('$' + val);
            } else if (originalId === 'amadex-price-max' || originalId && originalId.includes('price-max')) {
                $modal.find('#amadex-price-max-display, [data-original-id="amadex-price-max-display"]').text('$' + val);
                $('#amadex-price-max-display').text('$' + val);
            } else if (originalId === 'amadex-duration-min' || originalId && originalId.includes('duration-min')) {
                $modal.find('#amadex-duration-min-display, [data-original-id="amadex-duration-min-display"]').text(val + 'h');
                $('#amadex-duration-min-display').text(val + 'h');
            } else if (originalId === 'amadex-duration-max' || originalId && originalId.includes('duration-max')) {
                $modal.find('#amadex-duration-max-display, [data-original-id="amadex-duration-max-display"]').text(val + 'h');
                $('#amadex-duration-max-display').text(val + 'h');
            }
        });

        // Re-bind toggle switches
        $modal.find('.amadex-filter-toggle').off('change').on('change', function() {
            const $toggle = $(this);
            const target = $toggle.data('target');
            const isChecked = $toggle.is(':checked');
            
            // Sync to desktop
            $('.amadex-filters-sidebar').find(`.amadex-filter-toggle[data-target="${target}"]`).prop('checked', isChecked);
            
            // Apply toggle logic
            if (target) {
                const $targetEl = $modal.find(target);
                $targetEl.toggleClass('is-disabled', !isChecked);
                $targetEl.find('input[type="checkbox"]').prop('disabled', !isChecked);
                if (!isChecked) {
                    $targetEl.find('input[type="checkbox"]').prop('checked', false);
                }
            }
            
            if (window.refreshFilterOptionStates) {
                window.refreshFilterOptionStates();
            }
            
            if (window.applyFilters) {
                window.applyFilters();
            }
        });

        // Re-bind clear filters button
        $modal.find('#amadex-clear-filters').off('click').on('click', function() {
            // Clear both desktop and mobile filter tags
            $('.amadex-filters-sidebar #amadex-active-filters').empty();
            $('#amadex-mobile-filter-modal #amadex-active-filters').empty();
            
            if (window.clearAllFilters) {
                window.clearAllFilters();
            } else {
                // Fallback clear
                $modal.find('input[type="checkbox"]').prop('checked', false);
                $('.amadex-filters-sidebar input[type="checkbox"]').prop('checked', false);
                
                $modal.find('input[type="range"]').each(function() {
                    const $slider = $(this);
                    const originalId = $slider.attr('data-original-id') || $slider.attr('id');
                    const $desktopSlider = $(`#${originalId}`);
                    
                    if (originalId === 'amadex-price-min' || originalId && originalId.includes('price-min')) {
                        const minVal = $slider.attr('min');
                        $slider.val(minVal);
                        if ($desktopSlider.length) $desktopSlider.val(minVal);
                    } else if (originalId === 'amadex-price-max' || originalId && originalId.includes('price-max')) {
                        const maxVal = $slider.attr('max');
                        $slider.val(maxVal);
                        if ($desktopSlider.length) $desktopSlider.val(maxVal);
                    } else if (originalId === 'amadex-duration-min' || originalId && originalId.includes('duration-min')) {
                        const minVal = $slider.attr('min');
                        $slider.val(minVal);
                        if ($desktopSlider.length) $desktopSlider.val(minVal);
                    } else if (originalId === 'amadex-duration-max' || originalId && originalId.includes('duration-max')) {
                        const maxVal = $slider.attr('max');
                        $slider.val(maxVal);
                        if ($desktopSlider.length) $desktopSlider.val(maxVal);
                    }
                });
                
                if (window.applyFilters) {
                    window.applyFilters();
                }
            }
        });
    }

    /**
     * Create mobile sort bar
     */
    function createMobileSortBar() {
        // Check if sort bar already exists
        if ($('.amadex-mobile-sort-bar').length > 0) {
            return;
        }
        
        const $sortBar = $(`
            <div class="amadex-mobile-sort-bar">
                <div class="amadex-mobile-sort-bar-content">
                   <svg class="amadex-mobile-filter-icon" id="amadex-mobile-filter-icon" xmlns="http://www.w3.org/2000/svg" width="21.163" height="16.948" viewBox="0 0 21.163 16.948">
  <g id="Group_3637" data-name="Group 3637" transform="translate(0 -50.984)">
    <path id="Path_373" data-name="Path 373" d="M.661,54.4H11.429a2.754,2.754,0,0,0,5.346,0H20.5a.661.661,0,0,0,0-1.323H16.776a2.754,2.754,0,0,0-5.346,0H.661a.661.661,0,0,0,0,1.323ZM14.1,52.307a1.431,1.431,0,1,1-1.431,1.431A1.433,1.433,0,0,1,14.1,52.307ZM.661,60.119H4.387a2.754,2.754,0,0,0,5.346,0H20.5a.661.661,0,0,0,0-1.323H9.734a2.754,2.754,0,0,0-5.346,0H.661a.661.661,0,0,0,0,1.323Zm6.4-2.093a1.431,1.431,0,1,1-1.431,1.431A1.433,1.433,0,0,1,7.06,58.027ZM20.5,64.517H16.776a2.754,2.754,0,0,0-5.346,0H.661a.661.661,0,1,0,0,1.323H11.429a2.754,2.754,0,0,0,5.346,0H20.5a.661.661,0,1,0,0-1.323Zm-6.4,2.093a1.431,1.431,0,1,1,1.431-1.431A1.433,1.433,0,0,1,14.1,66.609Z"/>
  </g>
</svg>
                    <div class="amadex-mobile-sort-options">
                        <button class="amadex-mobile-sort-btn active" data-sort="low_to_high">Low to High</button>
                        <button class="amadex-mobile-sort-btn" data-sort="high_to_low">High to Low</button>
                        <button class="amadex-mobile-sort-btn" data-sort="nearest">Nearest Airport</button>
                        <button class="amadex-mobile-sort-btn" data-sort="shortest">Shortest Duration</button>
                    </div>
                </div>
            </div>
        `);

        // Append to results page container or body
        const $resultsPage = $('#amadex-results-page, .amadex-booking-page');
        if ($resultsPage.length > 0) {
            $resultsPage.append($sortBar);
        } else {
        $('body').append($sortBar);
        }
        
        // Sync active state with current sort value
        setTimeout(function() {
            const currentSort = $('#amadex-sort-by').val() || 'low_to_high';
            $('.amadex-mobile-sort-btn').removeClass('active');
            const $activeBtn = $('.amadex-mobile-sort-btn[data-sort="' + currentSort + '"]');
            if ($activeBtn.length > 0) {
                $activeBtn.addClass('active');
            } else {
                // Default to first button if no match
                $('.amadex-mobile-sort-btn').first().addClass('active');
            }
        }, 100);
        
        console.log('Mobile sort bar created');
    }

    /**
     * Bind mobile filter events
     */
    function bindMobileFilterEvents() {
        // Filter trigger button - Click on filter icon
        $(document).on('click', '#amadex-mobile-filter-icon, .amadex-mobile-filter-trigger', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Ensure sidebar exists before opening modal
            if ($('.amadex-filters-sidebar').length === 0) {
                console.warn('Filter sidebar not found, waiting...');
                setTimeout(function() {
                    if ($('.amadex-filters-sidebar').length > 0) {
                        openMobileFilterModal();
                    }
                }, 300);
                return;
            }
            
            openMobileFilterModal();
        });

        // Close modal
        $(document).on('click', '.amadex-mobile-filter-close, .amadex-mobile-filter-overlay', function(e) {
            if ($(e.target).hasClass('amadex-mobile-filter-overlay') || $(e.target).hasClass('amadex-mobile-filter-close')) {
                closeMobileFilterModal();
            }
        });

        // Apply filters button
        $(document).on('click', '.amadex-mobile-filter-apply-btn', function() {
            // Sync mobile filters to desktop before closing
            syncFiltersToDesktop();
            
            // Apply filters
            if (window.applyFilters) {
                window.applyFilters();
            }
            
            // Close modal
            closeMobileFilterModal();
        });

        // Sort buttons - Use event delegation for dynamically created buttons
        $(document).on('click touchstart', '.amadex-mobile-sort-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            const $btn = $(this);
            const sortValue = $btn.data('sort') || $btn.attr('data-sort');
            
            if (!sortValue) {
                console.warn('No sort value found on button');
                return;
            }
            
            console.log('Mobile sort clicked:', sortValue, {
                windowWidth: window.innerWidth,
                flightCount: $('.amadex-flight-card').length
            });
            
            // Update active state
            $('.amadex-mobile-sort-btn').removeClass('active');
            $btn.addClass('active');
            
            // Map mobile sort values to desktop sort values (they should match now)
            const mappedSortValue = sortValue; // Already using correct values
            
            // Update desktop sort select if exists
                const $sortSelect = $('#amadex-sort-by');
                if ($sortSelect.length) {
                $sortSelect.val(mappedSortValue);
            }
            
            // Update desktop sort tabs if exists
            $('.sort-tab').removeClass('is-active');
            $('.sort-tab[data-sort="' + mappedSortValue + '"]').addClass('is-active');
            
            // Trigger sort function with multiple fallbacks
            let sortTriggered = false;
            
            // Method 1: Use global wrapper function
            if (window.amadexSortFlights && typeof window.amadexSortFlights === 'function') {
                try {
                    window.amadexSortFlights(mappedSortValue);
                    sortTriggered = true;
                    console.log('Sort triggered via window.amadexSortFlights');
                } catch (err) {
                    console.warn('Error calling amadexSortFlights:', err);
                }
            }
            
            // Method 2: Trigger change event on sort select (most reliable)
            if (!sortTriggered && $sortSelect.length) {
                try {
                    $sortSelect.val(mappedSortValue).trigger('change');
                    sortTriggered = true;
                    console.log('Sort triggered via sort select change');
                } catch (err) {
                    console.warn('Error triggering sort select change:', err);
                }
            }
            
            // Method 3: Direct function call
            if (!sortTriggered && typeof sortFlights === 'function') {
                try {
                    sortFlights();
                    sortTriggered = true;
                    console.log('Sort triggered via direct sortFlights call');
                } catch (err) {
                    console.warn('Error calling sortFlights:', err);
                }
            }
            
            // Method 4: Manual sort as last resort
            if (!sortTriggered) {
                try {
                    sortFlightsManually(mappedSortValue);
                    console.log('Sort triggered via manual sort');
                } catch (err) {
                    console.error('Error in manual sort:', err);
                }
            }
        });
        
        /**
         * Manual sort function as fallback
         */
        function sortFlightsManually(sortBy) {
            // Try multiple container selectors
            const container = $('#amadex-flight-cards-container, .amadex-flights-list, .amadex-results-content, #amadex-flights-list').first();
            
            if (!container.length) {
                console.warn('Flight container not found for sorting');
                return;
            }
            
            const flights = container.find('.amadex-flight-card').toArray();
            if (!flights.length) {
                console.warn('No flights found to sort');
                return;
            }
            
            console.log('Manual sorting', flights.length, 'flights by', sortBy);
            
            flights.sort(function(a, b) {
                const $a = $(a);
                const $b = $(b);
                
                // Get price - try multiple methods
                let aPrice = parseFloat($a.data('price') || $a.attr('data-price') || 0);
                let bPrice = parseFloat($b.data('price') || $b.attr('data-price') || 0);
                
                // Try to extract from price text if data attribute not found
                if (!aPrice || aPrice === 0) {
                    const priceText = $a.find('.amadex-price, .flight-price, [class*="price"]').first().text();
                    const priceMatch = priceText.match(/[\d,]+\.?\d*/);
                    if (priceMatch) {
                        aPrice = parseFloat(priceMatch[0].replace(/,/g, ''));
                    }
                }
                if (!bPrice || bPrice === 0) {
                    const priceText = $b.find('.amadex-price, .flight-price, [class*="price"]').first().text();
                    const priceMatch = priceText.match(/[\d,]+\.?\d*/);
                    if (priceMatch) {
                        bPrice = parseFloat(priceMatch[0].replace(/,/g, ''));
                    }
                }
                
                const priceDiff = aPrice - bPrice;
                
                switch (sortBy) {
                    case 'low_to_high':
                        return priceDiff;
                    case 'high_to_low':
                        return -priceDiff;
                    case 'nearest':
                        const aStops = parseInt($a.data('stops') || $a.attr('data-stops') || 0, 10);
                        const bStops = parseInt($b.data('stops') || $b.attr('data-stops') || 0, 10);
                        if (aStops !== bStops) {
                            return aStops - bStops;
                        }
                        return priceDiff;
                    case 'shortest':
                        // Try to get duration in minutes
                        let aDuration = parseInt($a.data('duration') || $a.attr('data-duration') || 0, 10);
                        let bDuration = parseInt($b.data('duration') || $b.attr('data-duration') || 0, 10);
                        
                        // If duration not in data, try to parse from text
                        if (!aDuration || aDuration === 0) {
                            const durationText = $a.find('.amadex-duration, .flight-duration, [class*="duration"]').first().text();
                            const durationMatch = durationText.match(/(\d+)h\s*(\d+)m|(\d+)h|(\d+)m/);
                            if (durationMatch) {
                                aDuration = (parseInt(durationMatch[1] || 0) * 60) + parseInt(durationMatch[2] || durationMatch[4] || 0);
                            }
                        }
                        if (!bDuration || bDuration === 0) {
                            const durationText = $b.find('.amadex-duration, .flight-duration, [class*="duration"]').first().text();
                            const durationMatch = durationText.match(/(\d+)h\s*(\d+)m|(\d+)h|(\d+)m/);
                            if (durationMatch) {
                                bDuration = (parseInt(durationMatch[1] || 0) * 60) + parseInt(durationMatch[2] || durationMatch[4] || 0);
                            }
                        }
                        
                        if (aDuration !== bDuration) {
                            return aDuration - bDuration;
                        }
                        return priceDiff;
                    default:
                        return priceDiff;
                }
            });
            
            // Re-append sorted flights
            container.find('.amadex-flight-card').detach();
            flights.forEach(function(flight) {
                container.append(flight);
            });
            
            console.log('Flights sorted manually by:', sortBy, '-', flights.length, 'flights reordered');
        }

        // Update results count in modal and header
        $(document).on('amadex:resultsUpdated', function(e, count) {
            const resultCount = count || 0;
            $('#amadex-mobile-results-count').text(resultCount);
            $('#amadex-mobile-results-count-display').text(resultCount);
        });

        // Update route header from URL params or search form
        updateMobileRouteHeader();

        // Edit search button - Toggle search bar with slide-down animation (Mobile & Tablet)
        // $(document).on('click', '#amadex-mobile-edit-search, .amadex-route-edit', function(e) {
           
        //     e.preventDefault();
        //     e.stopPropagation();
            
        //     // Only work on mobile and tablet (up to 1024px)
        //     if (window.innerWidth > 1024) {
        //         return;
        //     }
            
        //     const $searchBarWrapper = $('.amadex-search-bar-wrapper');
        //     const $button = $(this);
            
        //     if ($searchBarWrapper.length === 0) {
        //         console.warn('Search bar wrapper not found');
        //         return;
        //     }
            
        //     // Toggle search bar visibility
        //     if ($searchBarWrapper.hasClass('amadex-search-bar-active')) {
        //         // Hide search bar with slide-up animation
        //         $searchBarWrapper.removeClass('amadex-search-bar-active');
        //         $searchBarWrapper.slideUp(300, function() {
        //             $(this).css('display', 'none');
        //         });
        //         $button.removeClass('active');
        //     } else {
        //         // Show search bar with slide-down animation
        //         $searchBarWrapper.addClass('amadex-search-bar-active');
        //         $searchBarWrapper.css('display', 'block');
        //         $searchBarWrapper.slideDown(300);
        //         $button.addClass('active');
                
        //         // Scroll to search bar smoothly
        //     $('html, body').animate({
        //             scrollTop: $searchBarWrapper.offset().top - 20
        //         }, 400);
        //     }
        // });
        
        
        
        // Test Assignments changes
        
//     (function () {
//   if (!document.getElementById('amadex-sheet-override')) {
//     $('head').append(`
//       <style id="amadex-sheet-override">
//         .amadex-search-bar-wrapper.amadex-search-bar-active{
//           display:block !important;
//           visibility:visible !important;
//           opacity:1 !important;
//           position:fixed !important;
//           left:0 !important;
//           right:0 !important;
//           bottom:-18px !important;
//           width:100% !important;
//           z-index:999999 !important;
//         }
//         .amadex-overlay{
//           position:fixed !important;
//           inset:0 !important;
//           background:rgba(0,0,0,0.6) !important;
//           z-index:999998 !important;
//           display:none;
//         }
//       </style>
//     `);
//   }
// $(document).on('click', '#amadex-mobile-edit-search, .amadex-route-edit', function (e) {
//     e.preventDefault();
//     e.stopPropagation();

//     if (window.innerWidth > 1024) return;

//     const $sheet = $('.desktop-search-bar');
//     if (!$sheet.length) return console.warn('Desktop search bar not found');

//     let $overlay = $('.amadex-overlay');
//     if (!$overlay.length) {
//         $overlay = $('<div class="amadex-overlay"></div>').appendTo('body');
//     }

//     const isOpen = $sheet.is(':visible');

//     if (isOpen) {
//         $sheet.slideUp(300);
//         $overlay.stop(true, true).fadeOut(200);
//         $('body').css('overflow', '');
//     } else {
//         $sheet.css({ 'z-index': 9999999, 'position': 'relative' });
//         $sheet.slideDown(300);
//         $overlay.stop(true, true).fadeIn(200);
//         $('body').css('overflow', 'hidden');
//     }
// });

// $(document).on('click', '.amadex-overlay', function () {
//     $('.desktop-search-bar').slideUp(300);
//     $('.amadex-overlay').fadeOut(200);
//     $('body').css('overflow', '');
// });
// //   $(document).on('click', '#amadex-mobile-edit-search, .amadex-route-edit', function (e) {
  
// //     e.preventDefault();
// //     e.stopPropagation();

// //     if (window.innerWidth > 1024) return;

// //     const $sheet = $('.amadex-search-bar-wrapper');
// //     if (!$sheet.length) return console.warn('Search bar wrapper not found');


// // console.log('CLICK DETECTED');
// //   console.log('Clicked element:', this);
  
// //     let $overlay = $('.amadex-overlay');
// //     if (!$overlay.length) {
// //       $overlay = $('<div class="amadex-overlay"></div>').appendTo('body');
// //     }

// //     $sheet.css({
// //       position: 'fixed',
// //       left: 0,
// //       right: 0,
// //       bottom: 0,
// //       width: '100%',
// //       zIndex: 999999,
// //       visibility: 'visible',
// //       opacity: 1
// //     });

// //     const isOpen = $sheet.hasClass('amadex-search-bar-active');
// //     if (isOpen) closeSheet($sheet, $overlay);
// //     else openSheet($sheet, $overlay);
// //   });

//   function openSheet($sheet, $overlay) {
//     // Force display (designer CSS may set display:none !important)
//     $sheet.addClass('amadex-search-bar-active');
//     $sheet.attr('style', ($sheet.attr('style') || '') + ';display:block !important;');

//     // Measure height
//     $sheet.show(); // ensure it's measurable
//     // $sheet.css({ height: 'auto', overflow: 'hidden' });
//     $sheet.css({ height: 'auto', overflow: 'visible' });

//     // If content is still "not visible", try cloning height
//     let fullHeight = $sheet.outerHeight(true);
//     if (!fullHeight) {
//       // fallback: temporarily move it offscreen to measure
//       $sheet.css({ top: '-9999px', bottom: 'auto' });
//       fullHeight = $sheet.outerHeight(true);
//       $sheet.css({ top: '', bottom: 0 });
//     }

//     // Animate from 0 -> fullHeight
//     $sheet.css({ height: 0, overflow: 'hidden' });

//     $overlay.stop(true, true).fadeIn(200);
//     $('body').css('overflow', 'hidden');

//     $sheet.stop(true, true).animate({ height: fullHeight }, 320, function () {
//       // allow natural height after animation (optional)
//     //   $sheet.css({ height: 'auto', overflow: '' });
//     $sheet.css({ height: 'auto', overflow: 'visible' });
//     });
//   }

//   function closeSheet($sheet, $overlay) {
//     $sheet.stop(true, true).animate({ height: 0 }, 380, function () {
//       $sheet.removeClass('amadex-search-bar-active');
//       $sheet.hide();
//     });

//     $overlay.stop(true, true).fadeOut(200);
//     $('body').css('overflow', '');
//   }

//   $(document).on('click', '.amadex-overlay', function () {
//     closeSheet($('.amadex-search-bar-wrapper'), $('.amadex-overlay'));
//   });

//   // Close button inside mobile search header
//   $(document).on('click', '.amadex-search-bar-close', function () {
//     closeSheet($('.amadex-search-bar-wrapper'), $('.amadex-overlay'));
//   });

//   $(document).on('keydown', function (e) {
//     if (e.key === 'Escape') {
//       closeSheet($('.amadex-search-bar-wrapper'), $('.amadex-overlay'));
//     }
//   });
// })();     
        (function () {
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('#amadex-mobile-edit-search, .amadex-route-edit');
        if (!btn) return;

        e.preventDefault();
        e.stopPropagation();

        if (window.innerWidth > 1024) return;

        const sheet = document.querySelector('.desktop-search-bar');
        if (!sheet) return;

        let overlay = document.querySelector('.amadex-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'amadex-overlay';
            document.body.appendChild(overlay);
        }

        const isOpen = sheet.style.display === 'block' || getComputedStyle(sheet).display !== 'none';

        if (isOpen) {
            sheet.style.display = 'none';
            overlay.style.display = 'none';
            document.body.style.overflow = '';
        } else {
            sheet.style.display = 'block';
            sheet.style.zIndex = '9999999';
            sheet.style.position = 'relative';
            overlay.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
    });

    document.addEventListener('click', function (e) {
        if (!e.target.classList.contains('amadex-overlay')) return;
        const sheet = document.querySelector('.desktop-search-bar');
        const overlay = document.querySelector('.amadex-overlay');
        if (sheet) sheet.style.display = 'none';
        if (overlay) overlay.style.display = 'none';
        document.body.style.overflow = '';
    });
})();
        
        // Close search bar when clicking outside
        $(document).on('click', function(e) {
            const $searchBarWrapper = $('.amadex-search-bar-wrapper');
            const $button = $('.amadex-route-edit, #amadex-mobile-edit-search');
            
            
            // Don't close if clicking inside search bar or on the button
            if (!$(e.target).closest('.amadex-search-bar-wrapper').length && 
                !$(e.target).closest('.amadex-route-edit, #amadex-mobile-edit-search').length) {
                
                if ($searchBarWrapper.hasClass('amadex-search-bar-active')) {
                    $searchBarWrapper.removeClass('amadex-search-bar-active');
                    $searchBarWrapper.slideUp(300, function() {
                        $(this).css('display', 'none');
                    });
                    $button.removeClass('active');
                }
            }
        });

        // Sync filter changes from desktop to mobile modal
        $(document).on('change', '.amadex-filter-option input, .amadex-filter-toggle', function() {
            syncFiltersToMobile();
        });
    }

    /**
     * Open mobile filter modal
     */
    function openMobileFilterModal() {
        // Always recreate modal to ensure it has latest filter content
        // This is important if filters are populated dynamically
        $('#amadex-mobile-filter-modal, .amadex-mobile-filter-overlay').remove();
        
        // Create the modal
        createMobileFilterModal();
        
        // Wait for modal to be created and populated
        setTimeout(function() {
            const $modal = $('#amadex-mobile-filter-modal');
            if ($modal.length === 0) {
                // Try again if modal not created
                setTimeout(arguments.callee, 100);
                return;
            }
            
            // Show modal with animation
            $('.amadex-mobile-filter-overlay').addClass('active');
            $modal.addClass('active');
            $('body').css('overflow', 'hidden');
            
            // Force all filter content to be visible - exact desktop match
            $modal.find('.amadex-filter-group').each(function() {
                $(this).show().css({
                    'display': 'block',
                    'visibility': 'visible',
                    'opacity': '1'
                });
            });
            
            $modal.find('.amadex-filter-options').each(function() {
                $(this).show().css({
                    'display': 'flex',
                    'visibility': 'visible',
                    'opacity': '1'
                });
            });
            
            $modal.find('.amadex-filter-option').each(function() {
                $(this).show().css({
                    'display': 'block',
                    'visibility': 'visible',
                    'opacity': '1'
                });
            });
            
            $modal.find('.amadex-time-card').each(function() {
                $(this).show().css({
                    'display': 'flex',
                    'visibility': 'visible',
                    'opacity': '1'
                });
            });
            
            $modal.find('.amadex-filter-chip-grid').show();
            $modal.find('.amadex-filter-time-grid').show();
            $modal.find('.amadex-price-range').show();
            $modal.find('.amadex-duration-range').show();
            $modal.find('.amadex-price-slider').show();
            $modal.find('.amadex-duration-slider').show();
            $modal.find('.amadex-price-slider-track').show();
            $modal.find('.amadex-duration-slider-track').show();
            $modal.find('.amadex-switch').show();
            $modal.find('.amadex-filter-tags').show();
            
            $modal.find('input, select, button, label, span, div, svg').each(function() {
                const $el = $(this);
                // Don't hide elements that should be visible
                if (!$el.hasClass('amadex-mobile-filter-close') && !$el.closest('.amadex-mobile-filter-header').length) {
                    $el.css({
                        'visibility': 'visible',
                        'opacity': '1'
                    });
                }
            });
            
            // Sync filters from desktop sidebar to mobile modal
            syncFiltersToMobile();
            
            // Update results count
            const count = $('#amadex-results-count').text() || $('.amadex-flight-card').length || 0;
            $('#amadex-mobile-results-count').text(count);
            
            // Re-bind events
            rebindFilterEventsInModal();
            
            // Refresh filter states
            if (window.refreshFilterOptionStates) {
                window.refreshFilterOptionStates();
            }
        }, 200);
    }

    /**
     * Close mobile filter modal
     */
    function closeMobileFilterModal() {
        $('.amadex-mobile-filter-overlay').removeClass('active');
        $('#amadex-mobile-filter-modal').removeClass('active');
        $('body').css('overflow', '');
    }

    /**
     * Sync filters from desktop sidebar to mobile modal
     */
    function syncFiltersToMobile() {
        const $desktopSidebar = $('.amadex-filters-sidebar');
        const $mobileModal = $('#amadex-mobile-filter-modal');
        
        if ($desktopSidebar.length === 0 || $mobileModal.length === 0) return;

        // Sync checkbox states (including all filter types)
        $desktopSidebar.find('input[type="checkbox"]').each(function() {
            const $input = $(this);
            const name = $input.attr('name');
            const value = $input.val();
            const isChecked = $input.is(':checked');
            
            const $mobileInput = $mobileModal.find(`input[name="${name}"][value="${value}"]`);
            if ($mobileInput.length) {
                $mobileInput.prop('checked', isChecked);
                // Update parent label state
                $mobileInput.closest('.amadex-filter-option, .amadex-time-card').toggleClass('is-checked', isChecked);
            }
        });

        // Sync slider values and their displays
        ['price-min', 'price-max', 'duration-min', 'duration-max'].forEach(function(id) {
            const $desktopSlider = $(`#amadex-${id}`);
            if ($desktopSlider.length) {
                const desktopVal = $desktopSlider.val();
                const $mobileSlider = $mobileModal.find(`#amadex-${id}`);
                if ($mobileSlider.length) {
                    $mobileSlider.val(desktopVal);
                    
                    // Update display
                    if (id === 'amadex-price-min') {
                        $mobileModal.find('#amadex-price-min-display').text('$' + desktopVal);
                    } else if (id === 'amadex-price-max') {
                        $mobileModal.find('#amadex-price-max-display').text('$' + desktopVal);
                    } else if (id === 'amadex-duration-min') {
                        $mobileModal.find('#amadex-duration-min-display').text(desktopVal + 'h');
                    } else if (id === 'amadex-duration-max') {
                        $mobileModal.find('#amadex-duration-max-display').text(desktopVal + 'h');
                    }
                }
            }
        });

        // Sync toggle states
        $desktopSidebar.find('.amadex-filter-toggle').each(function() {
            const $toggle = $(this);
            const target = $toggle.data('target');
            const isChecked = $toggle.is(':checked');
            
            if (target) {
                const $mobileToggle = $mobileModal.find(`.amadex-filter-toggle[data-target="${target}"]`);
                if ($mobileToggle.length) {
                    $mobileToggle.prop('checked', isChecked);
                    
                    // Update target element state
                    const $targetEl = $mobileModal.find(target);
                    $targetEl.toggleClass('is-disabled', !isChecked);
                    $targetEl.find('input[type="checkbox"]').prop('disabled', !isChecked);
                }
            }
        });

        // Sync active filter tags - avoid duplicates
        const $desktopTags = $desktopSidebar.find('#amadex-active-filters');
        const $mobileTags = $mobileModal.find('#amadex-active-filters');
        if ($desktopTags.length && $mobileTags.length) {
            // Clear mobile tags first
            $mobileTags.empty();
            // Clone tags from desktop, but ensure no duplicates
            $desktopTags.find('.amadex-filter-tag').each(function() {
                const $tag = $(this);
                const tagText = $tag.find('span').first().text().trim();
                // Check if this tag already exists in mobile
                const existingTag = $mobileTags.find('.amadex-filter-tag').filter(function() {
                    return $(this).find('span').first().text().trim() === tagText;
                });
                // Only add if not duplicate
                if (existingTag.length === 0) {
                    $mobileTags.append($tag.clone(true));
                }
            });
        }

        // Refresh filter option states
        if (window.refreshFilterOptionStates) {
            window.refreshFilterOptionStates();
        }
    }

    /**
     * Sync filters from mobile modal to desktop sidebar
     */
    function syncFiltersToDesktop() {
        const $mobileModal = $('#amadex-mobile-filter-modal');
        const $desktopSidebar = $('.amadex-filters-sidebar');
        
        if ($mobileModal.length === 0 || $desktopSidebar.length === 0) return;

        // Sync checkbox states
        $mobileModal.find('input[type="checkbox"]').each(function() {
            const $input = $(this);
            const name = $input.attr('name');
            const value = $input.val();
            const isChecked = $input.is(':checked');
            
            const $desktopInput = $desktopSidebar.find(`input[name="${name}"][value="${value}"]`);
            if ($desktopInput.length) {
                $desktopInput.prop('checked', isChecked);
                // Update parent label state
                $desktopInput.closest('.amadex-filter-option, .amadex-time-card').toggleClass('is-checked', isChecked);
            }
        });

        // Sync slider values and trigger updates
        ['price-min', 'price-max', 'duration-min', 'duration-max'].forEach(function(id) {
            const $mobileSlider = $mobileModal.find(`#amadex-${id}`);
            if ($mobileSlider.length) {
                const mobileVal = $mobileSlider.val();
                const $desktopSlider = $(`#amadex-${id}`);
                if ($desktopSlider.length) {
                    $desktopSlider.val(mobileVal).trigger('input');
                }
            }
        });

        // Sync toggle states
        $mobileModal.find('.amadex-filter-toggle').each(function() {
            const $toggle = $(this);
            const target = $toggle.data('target');
            const isChecked = $toggle.is(':checked');
            
            if (target) {
                const $desktopToggle = $desktopSidebar.find(`.amadex-filter-toggle[data-target="${target}"]`);
                if ($desktopToggle.length) {
                    $desktopToggle.prop('checked', isChecked);
                    
                    // Update target element state
                    const $targetEl = $desktopSidebar.find(target);
                    $targetEl.toggleClass('is-disabled', !isChecked);
                    $targetEl.find('input[type="checkbox"]').prop('disabled', !isChecked);
                }
            }
        });

        // Refresh filter option states
        if (window.refreshFilterOptionStates) {
            window.refreshFilterOptionStates();
        }
    }

    /**
     * Update mobile filter trigger text
     */
    function updateMobileFilterTrigger() {
        const activeFilters = $('.amadex-filter-tag').length;
        const $trigger = $('.amadex-mobile-filter-trigger');
        
        if ($trigger.length) {
            if (activeFilters > 0) {
                $trigger.html(`
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="4" y1="21" x2="4" y2="14"></line>
                        <line x1="4" y1="10" x2="4" y2="3"></line>
                        <line x1="12" y1="21" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12" y2="3"></line>
                        <line x1="20" y1="21" x2="20" y2="16"></line>
                        <line x1="20" y1="12" x2="20" y2="3"></line>
                        <line x1="1" y1="14" x2="7" y2="14"></line>
                        <line x1="9" y1="8" x2="15" y2="8"></line>
                        <line x1="17" y1="16" x2="23" y2="16"></line>
                    </svg>
                    Filters (${activeFilters})
                `);
            } else {
                $trigger.html(`
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="4" y1="21" x2="4" y2="14"></line>
                        <line x1="4" y1="10" x2="4" y2="3"></line>
                        <line x1="12" y1="21" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12" y2="3"></line>
                        <line x1="20" y1="21" x2="20" y2="16"></line>
                        <line x1="20" y1="12" x2="20" y2="3"></line>
                        <line x1="1" y1="14" x2="7" y2="14"></line>
                        <line x1="9" y1="8" x2="15" y2="8"></line>
                        <line x1="17" y1="16" x2="23" y2="16"></line>
                    </svg>
                    Filters
                `);
            }
        }
    }

    /**
     * Update mobile route header
     */
    function updateMobileRouteHeader() {
        // Get route info from URL params
        const urlParams = new URLSearchParams(window.location.search);
        const origin = urlParams.get('origin') || urlParams.get('from');
        const destination = urlParams.get('destination') || urlParams.get('to');
        const originCode = urlParams.get('origin_code') || urlParams.get('from_code');
        const destCode = urlParams.get('destination_code') || urlParams.get('to_code');
        const departureDate = urlParams.get('departure_date') || urlParams.get('departure');
        const returnDate = urlParams.get('return_date') || urlParams.get('return');
        const travellers = urlParams.get('travellers') || urlParams.get('adults') || '1';
        const cabin = urlParams.get('cabin') || 'Economy';

        // Try to get from search form if URL params not available
        if (!origin && !destination) {
            const $originField = $('#modern-origin, #amadex-from');
            const $destField = $('#modern-destination, #amadex-to');
            if ($originField.length && $destField.length) {
                const originVal = $originField.val() || $originField.text();
                const destVal = $destField.val() || $destField.text();
                
                if (originVal && destVal) {
                    updateRouteHeaderText(originVal, destVal, departureDate, returnDate, travellers, cabin);
                    return;
                }
            }
        }

        if (origin && destination) {
            updateRouteHeaderText(origin, destination, departureDate, returnDate, travellers, cabin);
        } else {
            // Fallback: try to extract from page
            setTimeout(function() {
                const $routeText = $('.amadex-route, .amadex-search-summary-modern .amadex-location-field .amadex-field-value');
                if ($routeText.length >= 2) {
                    const originText = $routeText.eq(0).text().trim();
                    const destText = $routeText.eq(1).text().trim();
                    if (originText && destText) {
                        updateRouteHeaderText(originText, destText, departureDate, returnDate, travellers, cabin);
                    }
                }
            }, 500);
        }
    }

    /**
     * Update route header text
     */
    function updateRouteHeaderText(origin, destination, departureDate, returnDate, travellers, cabin) {
        // Format route title
        const originParts = origin.split('(');
        const destParts = destination.split('(');
        const originCity = originParts[0].trim();
        const originCode = originParts[1] ? originParts[1].replace(')', '').trim() : '';
        const destCity = destParts[0].trim();
        const destCode = destParts[1] ? destParts[1].replace(')', '').trim() : '';

        let routeTitle = '';
        if (originCode && destCode) {
            routeTitle = `${originCity} (${originCode}) - ${destCity} (${destCode})`;
        } else {
            routeTitle = `${originCity} - ${destCity}`;
        }

        // Format details
        let details = [];
        if (departureDate) {
            const depDate = new Date(departureDate);
            const depFormatted = depDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: '2-digit' });
            if (returnDate) {
                const retDate = new Date(returnDate);
                const retFormatted = retDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: '2-digit' });
                details.push(`${depFormatted} - ${retFormatted}`);
            } else {
                details.push(depFormatted);
            }
        }
        details.push(`${travellers} Traveller${parseInt(travellers) > 1 ? 's' : ''}`);
        details.push(cabin);

        $('#amadex-mobile-route-title').text(routeTitle);
        $('#amadex-mobile-route-details').text(details.join(' / '));
    }

    /**
     * Handle window resize
     */
    function handleResize() {
        if (window.innerWidth > 768) {
            closeMobileFilterModal();
        }
    }

    // Initialize on DOM ready
    $(document).ready(function() {
        // Check if we're on mobile or tablet (320px to 1024px)
        const isMobileOrTablet = window.innerWidth <= 1024;
        
        // Initialize immediately if mobile or tablet
        if (isMobileOrTablet) {
            initMobileFilters();
        }
        
        // Also check for results page after a delay
        setTimeout(function() {
            const hasResults = $('#amadex-results-page').length > 0 || 
                              $('.amadex-booking-page').length > 0 || 
                              $('.amadex-flight-card').length > 0 ||
                              $('.amadex-flights-list').length > 0;
            
            const isMobile = window.innerWidth <= 767;
            if (isMobile && hasResults) {
                // Ensure sort bar is created and visible
                if ($('.amadex-mobile-sort-bar').length === 0) {
                    createMobileSortBar();
                }
                $('.amadex-mobile-sort-bar').show();
                
                // Sync active sort state
                const currentSort = $('#amadex-sort-by').val() || 'low_to_high';
                $('.amadex-mobile-sort-btn').removeClass('active');
                $('.amadex-mobile-sort-btn[data-sort="' + currentSort + '"]').addClass('active');
            }
        }, 500);
        
        // Update route header after page load
        setTimeout(function() {
            updateMobileRouteHeader();
        }, 1000);

        // Update route header when search form changes
        $(document).on('change', '#modern-origin, #modern-destination, #amadex-from, #amadex-to', function() {
            setTimeout(function() {
                updateMobileRouteHeader();
            }, 100);
        });
        
        // Re-initialize on resize
        let resizeTimer;
        $(window).on('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                const currentWidth = window.innerWidth;
                handleResize();
                if (currentWidth <= 1024) {
                    // Initialize for mobile and tablet
                    initMobileFilters();
                    // Show sort bar if results exist (mobile only)
                    if (currentWidth <= 767 && $('.amadex-flight-card').length > 0) {
                        if ($('.amadex-mobile-sort-bar').length === 0) {
                            createMobileSortBar();
                        }
                        $('.amadex-mobile-sort-bar').show();
                    } else if (currentWidth > 767) {
                        // Hide sort bar on tablet (but keep edit button functionality)
                        $('.amadex-mobile-sort-bar').hide();
                    }
                } else {
                    // Hide on desktop
                    $('.amadex-mobile-sort-bar').hide();
                    $('.amadex-search-bar-wrapper').removeClass('amadex-search-bar-active');
                }
            }, 250);
        });

        // Update filter trigger when filters change
        $(document).on('amadex:filtersUpdated', function() {
            updateMobileFilterTrigger();
        });

        // Recreate modal when filters are initialized (in case filters are populated dynamically)
        $(document).on('amadex:filtersInitialized', function() {
            if (window.innerWidth <= 768) {
                // Remove existing modal to force recreation with new filter content
                $('#amadex-mobile-filter-modal, .amadex-mobile-filter-overlay').remove();
                // Ensure mobile filter elements are created
                initMobileFilters();
            }
        });
        
        // Listen for when flight results are loaded (multiple events)
        $(document).on('amadex:resultsLoaded amadex:resultsUpdated amadex:flightsRendered amadex:flightsLoaded', function() {
            const isMobile = window.innerWidth <= 767;
            if (isMobile) {
                setTimeout(function() {
                    const hasFlights = $('.amadex-flight-card').length > 0;
                    
                    if (hasFlights) {
                    // Ensure mobile filter elements exist
                    if ($('.amadex-mobile-sort-bar').length === 0) {
                            createMobileSortBar();
                        }
                        
                        // Show sort bar with force
                        $('.amadex-mobile-sort-bar').show().css({
                            'display': 'block !important',
                            'visibility': 'visible !important',
                            'opacity': '1 !important'
                        });
                        
                        // Sync active state with current sort
                        const currentSort = $('#amadex-sort-by').val() || 'low_to_high';
                        $('.amadex-mobile-sort-btn').removeClass('active');
                        const $activeBtn = $('.amadex-mobile-sort-btn[data-sort="' + currentSort + '"]');
                        if ($activeBtn.length > 0) {
                            $activeBtn.addClass('active');
                        } else {
                            // Default to first button
                            $('.amadex-mobile-sort-btn').first().addClass('active');
                        }
                        
                        console.log('Mobile sort bar shown after results loaded', {
                            currentSort: currentSort,
                            sortBarVisible: $('.amadex-mobile-sort-bar').is(':visible'),
                            flightCount: $('.amadex-flight-card').length
                        });
                    }
                }, 300);
            }
        });
        
        // Also watch for DOM changes (MutationObserver for dynamic content)
        if (typeof MutationObserver !== 'undefined') {
            const observer = new MutationObserver(function(mutations) {
                const isMobile = window.innerWidth <= 767;
                if (isMobile && $('.amadex-flight-card').length > 0) {
                    if ($('.amadex-mobile-sort-bar').length === 0) {
                        createMobileSortBar();
                    }
                    $('.amadex-mobile-sort-bar').show();
                }
            });
            
            // Observe the flights list container
            setTimeout(function() {
                const $container = $('#amadex-flight-cards-container, .amadex-flights-list');
                if ($container.length > 0) {
                    observer.observe($container[0], {
                        childList: true,
                        subtree: true
                    });
            }
            }, 1000);
        }

        // Update results count when filters are applied
        if (window.applyFilters) {
            const originalApplyFilters = window.applyFilters;
            window.applyFilters = function() {
                const result = originalApplyFilters.apply(this, arguments);
                setTimeout(function() {
                    const count = $('#amadex-results-count').text() || $('.amadex-flight-card').length || 0;
                    $('#amadex-mobile-results-count').text(count);
                    $('#amadex-mobile-results-count-display').text(count);
                    $(document).trigger('amadex:resultsUpdated', [count]);
                }, 100);
                return result;
            };
        }

        // Sync mobile modal filters back to desktop when applying
        $(document).on('change', '#amadex-mobile-filter-modal input, #amadex-mobile-filter-modal .amadex-filter-toggle', function() {
            // Apply filters immediately in mobile modal
            if (window.applyFilters) {
                window.applyFilters();
            }
        });
    });

    // Expose functions globally
    window.amadexMobileFilters = {
        open: openMobileFilterModal,
        close: closeMobileFilterModal,
        syncToMobile: syncFiltersToMobile,
        syncToDesktop: syncFiltersToDesktop
    };

})(jQuery);