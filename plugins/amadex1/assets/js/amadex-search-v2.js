/**
 * Amadex Search Form V2 - JavaScript
 * Handles all interactions for the modern search form
 */

(function($) {
	'use strict';

	class AmadexSearchV2 {
		constructor() {
			this.form = $('#amadex-search-v2, #amadex-search-v2-results');
			this.singleFields = $('.amadex-single-trip-fields');
			this.multiFields = $('#multi-city-container');
			this.returnField = $('.amadex-return-field');
			this.segmentCount = 2;
			
			this.passengers = {
				adults: 1,
				children: 0,
				infants: 0
			};
			
			this.init();
		}

		init() {
			this.bindTripTypeChange();
			this.bindSwapButton();
			this.bindPassengersDropdown();
			this.bindAutocomplete();
			this.bindMultiCity();
			this.bindFormSubmit();
			this.initDefaults();
		}

		initDefaults() {
			// Set today as min date
			const today = new Date().toISOString().split('T')[0];
			$('#departure-date, #return-date').attr('min', today);
			
			// Set default departure date (tomorrow)
			const tomorrow = new Date();
			tomorrow.setDate(tomorrow.getDate() + 1);
			$('#departure-date').val(tomorrow.toISOString().split('T')[0]);
			
			// Set default return date (7 days from now)
			const weekLater = new Date();
			weekLater.setDate(weekLater.getDate() + 8);
			$('#return-date').val(weekLater.toISOString().split('T')[0]);
		}

		bindTripTypeChange() {
			$('input[name="tripType"]').on('change', (e) => {
				const tripType = e.target.value;
				
				if (tripType === 'oneway') {
					this.singleFields.show();
					this.multiFields.hide();
					this.returnField.addClass('is-disabled');
					$('#return-date').prop('required', false);
				} else if (tripType === 'round') {
					this.singleFields.show();
					this.multiFields.hide();
					this.returnField.removeClass('is-disabled');
					$('#return-date').prop('required', true);
				} else if (tripType === 'multi-city') {
					this.singleFields.hide();
					this.multiFields.show();
				}
			});
		}

		bindSwapButton() {
			$('#swap-button').on('click', () => {
				// Swap origin and destination
				const originInput = $('#origin-input');
				const destinationInput = $('#destination-input');
				const originCode = $('#origin-code');
				const destinationCode = $('#destination-code');
				const originCodeDisplay = $('#origin-code-display');
				const destinationCodeDisplay = $('#destination-code-display');
				
				// Swap values
				const tempInput = originInput.val();
				const tempCode = originCode.val();
				const tempCodeDisplay = originCodeDisplay.text();
				
				originInput.val(destinationInput.val());
				originCode.val(destinationCode.val());
				originCodeDisplay.text(destinationCodeDisplay.text());
				
				destinationInput.val(tempInput);
				destinationCode.val(tempCode);
				destinationCodeDisplay.text(tempCodeDisplay);
			});
		}

		bindPassengersDropdown() {
			// Toggle dropdown
			$('#passengers-trigger, #passengers-trigger-multi').on('click', function() {
				const dropdown = $(this).closest('.amadex-passengers-field').find('.amadex-passengers-dropdown');
				dropdown.toggleClass('is-active');
			});

			// Close dropdown when clicking outside
			$(document).on('click', (e) => {
				if (!$(e.target).closest('.amadex-passengers-field').length) {
					$('.amadex-passengers-dropdown').removeClass('is-active');
				}
			});

			// Counter buttons
			$('.amadex-counter-btn').on('click', function() {
				const action = $(this).data('action');
				const type = $(this).data('type');
				const countElement = $(`#${type}-count`);
				let count = parseInt(countElement.text()) || 0;
				
				if (action === 'increase') {
					if (type === 'adults' && count < 9) count++;
					else if (type === 'children' && count < 6) count++;
					else if (type === 'infants' && count < 2) count++;
				} else if (action === 'decrease') {
					if (type === 'adults' && count > 1) count--;
					else if ((type === 'children' || type === 'infants') && count > 0) count--;
				}
				
				countElement.text(count);
				$(`#${type}-input`).val(count);
				this.updatePassengerDisplay();
			}.bind(this));

			// Apply button
			$('#passengers-apply').on('click', () => {
				$('.amadex-passengers-dropdown').removeClass('is-active');
				this.updatePassengerDisplay();
			});
		}

		updatePassengerDisplay() {
			const adults = parseInt($('#adults-count').text()) || 1;
			const children = parseInt($('#children-count').text()) || 0;
			const infants = parseInt($('#infants-count').text()) || 0;
			const total = adults + children + infants;
			
			const text = total === 1 ? '1 Passenger' : `${total} Passengers`;
			$('#passengers-display, #passengers-display-multi').text(text);
		}

		bindAutocomplete() {
			let searchTimeout;
			
			$('.amadex-autocomplete').on('input', function() {
				const input = $(this);
				const query = input.val();
				const dropdown = input.closest('.amadex-search-v2__field, .amadex-location-field').find('.amadex-autocomplete-dropdown');
				
				clearTimeout(searchTimeout);
				
				if (query.length < 2) {
					dropdown.removeClass('is-active').empty();
					return;
				}
				
				searchTimeout = setTimeout(() => {
					this.searchAirports(query, dropdown, input);
				}, 300);
			}.bind(this));

			// Click outside to close
			$(document).on('click', (e) => {
				if (!$(e.target).closest('.amadex-location-field, .amadex-search-v2__field').length) {
					$('.amadex-autocomplete-dropdown').removeClass('is-active');
				}
			});
		}

		searchAirports(query, dropdown, input) {
			// Use WordPress AJAX
			$.ajax({
				url: amadexData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'amadex_search_airports',
					nonce: amadexData.nonce,
					query: query
				},
				success: (response) => {
					if (response.success && response.data.length > 0) {
						this.renderAirportSuggestions(response.data, dropdown, input);
					} else {
						dropdown.html('<div class="amadex-autocomplete-item">No airports found</div>');
						dropdown.addClass('is-active');
					}
				},
				error: () => {
					dropdown.removeClass('is-active');
				}
			});
		}

		renderAirportSuggestions(airports, dropdown, input) {
			let html = '';
			
			airports.forEach((airport) => {
				html += `
					<div class="amadex-autocomplete-item" 
						 data-code="${airport.code}" 
						 data-name="${airport.name}"
						 data-city="${airport.city}"
						 data-country="${airport.country}">
						<div class="amadex-autocomplete-item__name">
							${airport.city}, ${airport.country}
						</div>
						<div class="amadex-autocomplete-item__details">
							${airport.name} (${airport.code})
						</div>
					</div>
				`;
			});
			
			dropdown.html(html).addClass('is-active');
			
			// Bind click events
			dropdown.find('.amadex-autocomplete-item').on('click', function() {
				const item = $(this);
				const code = item.data('code');
				const city = item.data('city');
				const country = item.data('country');
				
				input.val(`${city}, ${country}`);
				
				// Set hidden code input
				const fieldContainer = input.closest('.amadex-search-v2__field, .amadex-location-field');
				fieldContainer.find('input[type="hidden"]').val(code);
				fieldContainer.find('.amadex-field__code').text(code);
				
				dropdown.removeClass('is-active');
			});
		}

		bindMultiCity() {
			// Add flight segment
			$('#add-flight-segment').on('click', () => {
				if (this.segmentCount >= 6) {
					alert('Maximum 6 flights allowed');
					return;
				}
				
				this.segmentCount++;
				this.addFlightSegment(this.segmentCount);
			});

			// Remove flight segment
			$(document).on('click', '.amadex-remove-segment', function() {
				const segment = $(this).data('segment');
				if (this.segmentCount > 2) {
					$(`.amadex-multi-city-segment[data-segment="${segment}"]`).remove();
					this.segmentCount--;
				}
			}.bind(this));
		}

		addFlightSegment(segmentNumber) {
			const html = `
				<div class="amadex-multi-city-segment" data-segment="${segmentNumber}">
					<div class="amadex-multi-city-fields">
						<div class="amadex-search-v2__field">
							<input type="text" 
								   class="amadex-field__input amadex-autocomplete" 
								   placeholder="From" 
								   data-type="origin" 
								   data-segment="${segmentNumber}">
							<input type="hidden" class="multi-origin-code" data-segment="${segmentNumber}">
						</div>
						<div class="amadex-search-v2__field">
							<input type="text" 
								   class="amadex-field__input amadex-autocomplete" 
								   placeholder="To" 
								   data-type="destination" 
								   data-segment="${segmentNumber}">
							<input type="hidden" class="multi-destination-code" data-segment="${segmentNumber}">
						</div>
						<div class="amadex-search-v2__field">
							<input type="date" 
								   class="amadex-field__input" 
								   data-segment="${segmentNumber}" 
								   name="multi_date_${segmentNumber}">
						</div>
						<button type="button" 
								class="amadex-remove-segment" 
								data-segment="${segmentNumber}" 
								aria-label="Remove flight">×</button>
					</div>
				</div>
			`;
			
			$('.amadex-add-flight-btn').before(html);
			this.bindAutocomplete(); // Re-bind for new inputs
		}

		bindFormSubmit() {
			this.form.on('submit', (e) => {
				e.preventDefault();
				
				if (!this.validateForm()) {
					return false;
				}
				
				this.submitSearch();
			});
		}

		validateForm() {
			const tripType = $('input[name="tripType"]:checked').val();
			
			if (tripType === 'multi-city') {
				// Validate multi-city
				let isValid = true;
				$('.amadex-multi-city-segment').each(function() {
					const segment = $(this).data('segment');
					const origin = $(this).find('.multi-origin-code').val();
					const destination = $(this).find('.multi-destination-code').val();
					const date = $(this).find('input[type="date"]').val();
					
					if (!origin || !destination || !date) {
						isValid = false;
						return false;
					}
				});
				return isValid;
			} else {
				// Validate single trip
				const origin = $('#origin-code').val();
				const destination = $('#destination-code').val();
				const departureDate = $('#departure-date').val();
				
				if (!origin || !destination || !departureDate) {
					alert('Please fill in all required fields');
					return false;
				}
				
				if (tripType === 'round' && !$('#return-date').val()) {
					alert('Please select return date');
					return false;
				}
				
				return true;
			}
		}

		submitSearch() {
			$('.amadex-search-v2__loading').show();
			
			// Collect form data
			const searchData = this.collectFormData();
			
			// Redirect to results page or handle via AJAX
			const resultsPage = this.form.data('results');
			const queryString = $.param(searchData);
			
			window.location.href = `${resultsPage}?${queryString}`;
		}

		collectFormData() {
			const tripType = $('input[name="tripType"]:checked').val();
			const data = {
				tripType: tripType,
				adults: $('#adults-input').val() || 1,
				children: $('#children-input').val() || 0,
				infants: $('#infants-input').val() || 0
			};
			
			if (tripType === 'multi-city') {
				data.segments = [];
				$('.amadex-multi-city-segment').each(function() {
					const segment = $(this).data('segment');
					data.segments.push({
						origin: $(this).find('.multi-origin-code').val(),
						destination: $(this).find('.multi-destination-code').val(),
						date: $(this).find('input[type="date"]').val()
					});
				});
			} else {
				data.origin = $('#origin-code').val();
				data.destination = $('#destination-code').val();
				data.departure_date = $('#departure-date').val();
				if (tripType === 'round') {
					data.return_date = $('#return-date').val();
				}
			}
			
			return data;
		}
	}

	// Initialize when document is ready
	$(document).ready(function() {
		if ($('.amadex-search-v2').length) {
			new AmadexSearchV2();
		}
	});

})(jQuery);


