(function($) {
	'use strict';
	
	function init() {
		$('.tdf-grid').each(function() {
			const $c = $(this);
			if ($c.data('init')) return;
			$c.data('init', true);
			
			// Small delay to ensure DOM is ready
			setTimeout(function() {
				loadDeals($c);
			}, 100);
		});
	}
	
	function loadDeals($c) {
			
		const url = $c.data('api-url');
		if (!url) {
			console.error('TDF: API URL missing');
			return;
		}
		
		const origin = $c.data('origin') || 'NYC';
		const dest = $c.data('destination') || 'ATL';
		const dateFrom = $c.data('date-from') || '';
		const dateTo = $c.data('date-to') || '';
		const limit = parseInt($c.data('limit')) || 9;
		const currency = $c.data('currency') || 'USD';
		const colsDesk = parseInt($c.data('cols-desk')) || 3;
		const colsTab = parseInt($c.data('cols-tab')) || 2;
		const colsMob = parseInt($c.data('cols-mob')) || 1;
		const savingsPct = $c.data('savings-pct') || '15';
		const showSavings = $c.data('show-savings') === 'true';
		
		const params = {
			origin: origin,
			destination: dest,
			max: limit,
			currency: currency,
		};
		
		if (dateFrom) params.departure_date = dateFrom;
		if (dateTo) params.return_date = dateTo;
		
		const $items = $c.find('.tdf-items');
		const $load = $c.find('.tdf-loading');
		const $err = $c.find('.tdf-error');
		
		$items.css('--cols-desk', colsDesk);
		$items.css('--cols-tab', colsTab);
		$items.css('--cols-mob', colsMob);
		
		$.ajax({
			url: url,
			method: 'GET',
			data: params,
			dataType: 'json',
			timeout: 30000,
			cache: false,
		}).done(function(res) {
			$load.hide();
			$err.hide();
			
			let deals = [];
			if (res && res.success && res.data && Array.isArray(res.data)) {
				deals = res.data;
			} else if (Array.isArray(res)) {
				deals = res;
			}
			
			if (deals.length > 0) {
				renderDeals($items, deals, { savingsPct, showSavings });
			} else {
				$err.text('No flight deals found.').show();
			}
		}).fail(function(xhr, status, error) {
			$load.hide();
			let errMsg = 'Failed to load deals';
			if (xhr.responseJSON && xhr.responseJSON.message) {
				errMsg += ': ' + xhr.responseJSON.message;
			} else if (error) {
				errMsg += ': ' + error;
			}
			$err.text(errMsg).show();
			console.error('TDF Error:', xhr, status, error);
		});
	}
	
	function renderDeals($container, deals, opts) {
		$container.empty();
		
		deals.forEach(function(deal) {
			const origin = deal.origin || '';
			const dest = deal.destination || '';
			const dateText = deal.dateText || '';
			const priceText = deal.priceText || '';
			const deepLink = deal.deepLink || '#';
			
			const dates = parseDate(dateText);
			const originCity = getCity(origin);
			const destCity = getCity(dest);
			
			let badge = '';
			if (opts.showSavings && opts.savingsPct) {
				badge = '<span class="tdf-badge">save ' + escapeHtml(opts.savingsPct) + '%</span>';
			}
			
			let dateHtml = '';
			if (dates.dep) {
				dateHtml = '<div class="tdf-date">' + escapeHtml(dates.dep) + '</div>';
			}
			
			const card = $(
				'<div class="tdf-card">' +
				badge +
				dateHtml +
				'<div class="tdf-route">' +
				'<div class="tdf-route-origin">' +
				'<span class="tdf-code">' + escapeHtml(origin) + '</span>' +
				'<span class="tdf-city">' + escapeHtml(originCity) + '</span>' +
				'<span class="tdf-starting">Starting From</span>' +
				'</div>' +
				'<div class="tdf-line">' +
				'<span class="tdf-dots"></span>' +
				'<span class="tdf-icon">✈</span>' +
				'</div>' +
				'<div class="tdf-route-dest">' +
				'<span class="tdf-code">' + escapeHtml(dest) + '</span>' +
				'<span class="tdf-city">' + escapeHtml(destCity) + '</span>' +
				'</div>' +
				'</div>' +
				'<div class="tdf-price">' +
				'<span class="tdf-price-val">' + escapeHtml(priceText) + '</span>' +
				'<span class="tdf-price-label">/Adult</span>' +
				'</div>' +
				'</div>'
			);
			
			if (deepLink && deepLink !== '#') {
				card.css('cursor', 'pointer').on('click', function() {
					window.open(deepLink, '_blank');
				});
			}
			
			$container.append(card);
		});
	}
	
	function parseDate(dateText) {
		if (!dateText) return { dep: '', ret: '' };
		const parts = dateText.split(/[–-]/).map(s => s.trim());
		if (parts.length === 2) {
			return { dep: formatDate(parts[0]), ret: formatDate(parts[1]) };
		}
		return { dep: formatDate(dateText), ret: '' };
	}
	
	function formatDate(str) {
		if (!str) return '';
		str = str.trim();
		if (/^\d{4}-\d{2}-\d{2}$/.test(str)) return str;
		const d = new Date(str);
		if (!isNaN(d.getTime())) {
			const y = d.getFullYear();
			const m = String(d.getMonth() + 1).padStart(2, '0');
			const day = String(d.getDate()).padStart(2, '0');
			return y + '-' + m + '-' + day;
		}
		return str;
	}
	
	function getCity(code) {
		const cities = {
			NYC: 'New York', JFK: 'New York', LGA: 'New York', EWR: 'New York',
			WAS: 'Washington', DCA: 'Washington', IAD: 'Washington',
			FLL: 'Ft Lauderdale', MIA: 'Miami', ATL: 'Atlanta',
			LAX: 'Los Angeles', ORD: 'Chicago', CHI: 'Chicago',
			DFW: 'Dallas', DEN: 'Denver', SFO: 'San Francisco',
			SEA: 'Seattle', LAS: 'Las Vegas', PHX: 'Phoenix',
		};
		return cities[code] || code;
	}
	
	function escapeHtml(text) {
		const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
		return String(text).replace(/[&<>"']/g, m => map[m]);
	}
	
	// Run on document ready
	$(document).ready(function() {
		init();
		// Also run after a short delay for editor compatibility
		setTimeout(init, 500);
	});
	
	// Re-initialize on AJAX content load
	$(document).on('ajaxComplete', function() {
		setTimeout(init, 100);
	});
	
	// For Gutenberg editor
	if (typeof wp !== 'undefined' && wp.domReady) {
		wp.domReady(init);
	}
})(jQuery);

