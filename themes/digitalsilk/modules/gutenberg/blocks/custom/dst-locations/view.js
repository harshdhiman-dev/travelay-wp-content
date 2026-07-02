/**
 * Travelay Locations Explorer — MapLibre store locator (mobile-first).
 */
(function () {
	'use strict';

	var initialized = new WeakMap();
	var PREF_KEY = 'travelay_preferred_location';

	function boot() {
		document.querySelectorAll('[data-travelay-locations]').forEach(function (root) {
			if (initialized.has(root)) {
				destroyRoot(root);
			}
			initRoot(root);
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}

	if (window.acf) {
		window.acf.addAction('render_block_preview', function ($el) {
			var root = $el && $el[0] ? $el[0].querySelector('[data-travelay-locations]') : null;
			if (!root && $el && $el[0] && $el[0].hasAttribute && $el[0].hasAttribute('data-travelay-locations')) {
				root = $el[0];
			}
			if (root) {
				if (initialized.has(root)) {
					destroyRoot(root);
				}
				setTimeout(function () {
					initRoot(root);
				}, 60);
			}
		});

		window.acf.addAction('remove_block_preview', function ($el) {
			var root = $el && $el[0] ? $el[0].querySelector('[data-travelay-locations]') : null;
			if (root) {
				destroyRoot(root);
			}
		});
	}

	function destroyRoot(root) {
		var state = initialized.get(root);
		if (!state) {
			return;
		}
		if (state.map) {
			state.map.remove();
		}
		if (state.resizeObserver) {
			state.resizeObserver.disconnect();
		}
		document.body.classList.remove('tl-detail-open');
		initialized.delete(root);
	}

	function trackEvent(action, params) {
		var payload = Object.assign({ event: 'travelay_locations', action: action }, params || {});
		window.dataLayer = window.dataLayer || [];
		window.dataLayer.push(payload);
	}

	function initRoot(root) {
		if (typeof maplibregl === 'undefined') {
			return;
		}

		var configRaw = root.getAttribute('data-tl-config');
		if (!configRaw) {
			return;
		}

		var config;
		try {
			config = JSON.parse(configRaw);
		} catch (e) {
			return;
		}

		var locations = config.locations || [];
		var i18n = config.i18n || {};
		var features = config.features || {};
		var mapEl = root.querySelector('[data-tl-map]');
		var listEl = root.querySelector('[data-tl-list]');
		var emptyEl = root.querySelector('[data-tl-empty]');
		var searchEl = root.querySelector('[data-tl-search]');
		var nearBtn = root.querySelector('[data-tl-near-me]');
		var detailEl = root.querySelector('[data-tl-detail]');
		var detailMedia = root.querySelector('[data-tl-detail-media]');
		var detailBody = root.querySelector('[data-tl-detail-body]');
		var detailClose = root.querySelector('[data-tl-detail-close]');
		var explorer = root.querySelector('.tl-explorer');
		var filterBtns = root.querySelectorAll('[data-tl-filter]');
		var serviceBtns = root.querySelectorAll('[data-tl-service]');
		var viewBtns = root.querySelectorAll('[data-tl-view]');
		var boardEl = root.querySelector('[data-tl-board]');
		var routeStatusEl = root.querySelector('[data-tl-route-status]');

		var state = {
			filter: 'all',
			serviceFilter: 'all',
			query: '',
			selectedId: null,
			userLat: null,
			userLng: null,
			map: null,
			resizeObserver: null,
			routeActive: false,
		};

		var locById = {};
		var locBySlug = {};
		locations.forEach(function (loc) {
			locById[String(loc.id)] = loc;
			if (loc.slug) {
				locBySlug[String(loc.slug).toLowerCase()] = loc;
			}
			if (loc.airport_code) {
				locBySlug[String(loc.airport_code).toLowerCase()] = loc;
			}
		});

		if (boardEl && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
			var codes = locations.map(function (l) { return l.airport_code; }).filter(Boolean);
			if (codes.length > 1) {
				var boardIndex = 0;
				setInterval(function () {
					if (!root.isConnected) return;
					boardEl.querySelectorAll('[data-tl-board-code]').forEach(function (item, idx) {
						var code = codes[(boardIndex + idx) % codes.length];
						if (item.textContent !== code) {
							item.style.opacity = '0.3';
							setTimeout(function () {
								item.textContent = code;
								item.setAttribute('data-tl-board-filter', code);
								item.style.opacity = '1';
							}, 100);
						}
					});
					boardIndex = (boardIndex + 1) % codes.length;
				}, 3200);
			}
		}

		boardEl && boardEl.querySelectorAll('[data-tl-board-filter]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var code = btn.getAttribute('data-tl-board-filter');
				if (!code || !searchEl) return;
				searchEl.value = code;
				state.query = code.toLowerCase();
				applyFilters();
				trackEvent('board_filter', { airport_code: code });
			});
		});

		if (mapEl) {
			state.map = new maplibregl.Map({
				container: mapEl,
				style: config.mapStyle,
				center: getCenter(locations),
				zoom: locations.length > 1 ? 2.8 : 10,
				cooperativeGestures: true,
				attributionControl: !config.isEditor,
			});

			state.map.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'top-right');

			state.map.on('load', function () {
				if (config.isEditor) {
					state.map.resize();
				}
				state.map.addSource('locations', {
					type: 'geojson',
					data: buildGeoJSON(locations),
					cluster: true,
					clusterMaxZoom: 11,
					clusterRadius: 55,
				});

				state.map.addSource('route', {
					type: 'geojson',
					data: { type: 'FeatureCollection', features: [] },
				});

				state.map.addLayer({
					id: 'clusters',
					type: 'circle',
					source: 'locations',
					filter: ['has', 'point_count'],
					paint: {
						'circle-color': '#0e7d3f',
						'circle-radius': ['step', ['get', 'point_count'], 20, 5, 26, 12, 32],
						'circle-opacity': 0.88,
					},
				});

				state.map.addLayer({
					id: 'cluster-count',
					type: 'symbol',
					source: 'locations',
					filter: ['has', 'point_count'],
					layout: {
						'text-field': '{point_count_abbreviated}',
						'text-size': 12,
					},
					paint: { 'text-color': '#ffffff' },
				});

				state.map.addLayer({
					id: 'unclustered-point',
					type: 'circle',
					source: 'locations',
					filter: ['!', ['has', 'point_count']],
					paint: {
						'circle-color': '#22af5c',
						'circle-radius': 11,
						'circle-stroke-width': 3,
						'circle-stroke-color': '#ffffff',
					},
				});

				state.map.addLayer({
					id: 'route-line',
					type: 'line',
					source: 'route',
					layout: { 'line-join': 'round', 'line-cap': 'round' },
					paint: {
						'line-color': '#0e7d3f',
						'line-width': 5,
						'line-opacity': 0.85,
					},
				});

				state.map.on('click', 'clusters', function (e) {
					var features = state.map.queryRenderedFeatures(e.point, { layers: ['clusters'] });
					if (!features.length) return;
					var clusterId = features[0].properties.cluster_id;
					state.map.getSource('locations').getClusterExpansionZoom(clusterId, function (err, zoom) {
						if (err) return;
						state.map.easeTo({ center: features[0].geometry.coordinates, zoom: zoom });
					});
				});

				state.map.on('click', 'unclustered-point', function (e) {
					if (!e.features || !e.features[0]) return;
					selectLocation(e.features[0].properties.id, true);
				});

				['clusters', 'unclustered-point'].forEach(function (layer) {
					state.map.on('mouseenter', layer, function () { state.map.getCanvas().style.cursor = 'pointer'; });
					state.map.on('mouseleave', layer, function () { state.map.getCanvas().style.cursor = ''; });
				});
			});

			if (window.ResizeObserver) {
				state.resizeObserver = new ResizeObserver(function () {
					if (state.map) state.map.resize();
				});
				state.resizeObserver.observe(mapEl);
			}
		}

		root.querySelectorAll('[data-tl-select]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				selectLocation(btn.getAttribute('data-tl-select'), true);
				if (window.matchMedia('(max-width: 767px)').matches && explorer) {
					explorer.setAttribute('data-view', 'map');
					viewBtns.forEach(function (b) {
						var active = b.getAttribute('data-tl-view') === 'map';
						b.classList.toggle('is-active', active);
						b.setAttribute('aria-selected', active ? 'true' : 'false');
					});
					if (state.map) {
						setTimeout(function () { state.map.resize(); }, 200);
					}
				}
			});
		});

		if (searchEl) {
			var searchTimer;
			searchEl.addEventListener('input', function () {
				clearTimeout(searchTimer);
				searchTimer = setTimeout(function () {
					state.query = searchEl.value.trim().toLowerCase();
					applyFilters();
				}, 160);
			});
		}

		filterBtns.forEach(function (btn) {
			btn.addEventListener('click', function () {
				filterBtns.forEach(function (b) { b.classList.remove('is-active'); });
				btn.classList.add('is-active');
				state.filter = btn.getAttribute('data-tl-filter');
				applyFilters();
				trackEvent('country_filter', { country: state.filter });
			});
		});

		serviceBtns.forEach(function (btn) {
			btn.addEventListener('click', function () {
				serviceBtns.forEach(function (b) { b.classList.remove('is-active'); });
				btn.classList.add('is-active');
				state.serviceFilter = btn.getAttribute('data-tl-service');
				applyFilters();
				trackEvent('service_filter', { service: state.serviceFilter });
			});
		});

		if (nearBtn) {
			nearBtn.addEventListener('click', function () {
				if (!navigator.geolocation) return;
				nearBtn.disabled = true;
				var label = nearBtn.textContent;
				nearBtn.textContent = '…';
				navigator.geolocation.getCurrentPosition(
					function (pos) {
						state.userLat = pos.coords.latitude;
						state.userLng = pos.coords.longitude;
						nearBtn.disabled = false;
						nearBtn.textContent = label;
						sortByDistance();
						applyFilters();
						if (state.map) {
							state.map.easeTo({ center: [state.userLng, state.userLat], zoom: 5.5 });
						}
						trackEvent('near_me', {});
					},
					function () {
						nearBtn.disabled = false;
						nearBtn.textContent = label;
					},
					{ enableHighAccuracy: false, timeout: 12000, maximumAge: 60000 }
				);
			});
		}

		viewBtns.forEach(function (btn) {
			btn.addEventListener('click', function () {
				var view = btn.getAttribute('data-tl-view');
				viewBtns.forEach(function (b) {
					var active = b === btn;
					b.classList.toggle('is-active', active);
					b.setAttribute('aria-selected', active ? 'true' : 'false');
				});
				if (explorer) {
					explorer.setAttribute('data-view', view);
				}
				if (view === 'map' && state.map) {
					setTimeout(function () { state.map.resize(); }, 150);
				}
			});
		});

		if (detailClose) {
			detailClose.addEventListener('click', closeDetail);
		}

		detailBody && detailBody.addEventListener('click', function (e) {
			var target = e.target.closest('[data-tl-action]');
			if (!target) return;
			var action = target.getAttribute('data-tl-action');
			var loc = locById[String(state.selectedId)];
			if (!loc) return;

			if (action === 'route') {
				e.preventDefault();
				if (state.routeActive) {
					clearRoute();
				} else {
					showRoute(loc);
				}
			}

			if (action === 'share') {
				e.preventDefault();
				shareLocation(loc, target);
			}

			if (action === 'set-location') {
				e.preventDefault();
				setPreferredLocation(loc, target);
			}
		});

		function applyFilters() {
			var visible = [];
			root.querySelectorAll('[data-tl-card]').forEach(function (card) {
				var id = card.getAttribute('data-id');
				var loc = locById[id];
				if (!loc) {
					card.hidden = true;
					return;
				}
				var matchCountry = state.filter === 'all' || loc.country_code === state.filter;
				var label = (loc.search_label || '').toLowerCase();
				var matchQuery = !state.query || label.indexOf(state.query) !== -1;
				var services = loc.services || [];
				var matchService = state.serviceFilter === 'all' || services.indexOf(state.serviceFilter) !== -1;
				var show = matchCountry && matchQuery && matchService;
				card.hidden = !show;
				if (show) visible.push(loc);
			});

			updateMapSource(visible);
			updateCount(visible.length);
			if (emptyEl) emptyEl.hidden = visible.length > 0;
			if (listEl) listEl.hidden = visible.length === 0;
		}

		function updateCount(n) {
			var word = n === 1 ? 'location' : (i18n.locations || 'locations');
			var text = n + ' ' + word;
			root.querySelectorAll('[data-tl-count]').forEach(function (el) {
				el.textContent = text;
			});
		}

		function updateMapSource(visible) {
			if (!state.map || !state.map.getSource('locations')) return;
			state.map.getSource('locations').setData(buildGeoJSON(visible));
		}

		function selectLocation(id, fly) {
			var loc = locById[String(id)];
			if (!loc) return;

			state.selectedId = String(id);
			updateDeepLink(loc);
			root.querySelectorAll('[data-tl-card]').forEach(function (card) {
				card.classList.toggle('is-selected', card.getAttribute('data-id') === String(id));
				card.classList.toggle('is-preferred', card.getAttribute('data-slug') === getPreferredSlug());
			});

			var selectedCard = root.querySelector('[data-tl-card][data-id="' + id + '"]');
			if (selectedCard) {
				selectedCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
			}

			if (fly && state.map && state.map.loaded()) {
				state.map.easeTo({ center: [loc.lng, loc.lat], zoom: Math.max(state.map.getZoom(), 10), duration: 800 });
			}

			clearRoute();
			renderDetail(loc);
			trackEvent('select_location', { location_id: loc.id, slug: loc.slug, airport_code: loc.airport_code || '' });
		}

		function renderDetail(loc) {
			if (!detailEl || !detailBody) return;

			var hoursHtml = loc.hours ? '<div class="tl-detail__row"><strong>' + esc(i18n.hours || 'Hours') + '</strong>' + nl2br(esc(loc.hours)) + '</div>' : '';
			var terminalHtml = loc.terminal ? '<div class="tl-detail__row"><strong>' + esc(i18n.terminal || 'Terminal') + '</strong>' + esc(loc.terminal) + (loc.counter ? '<br>' + esc(loc.counter) : '') + '</div>' : '';
			var phoneDigits = (loc.phone || config.defaultPhone || '').replace(/\D+/g, '');
			var openHtml = loc.open_label ? '<div class="tl-detail__row"><span class="tl-card__hours tl-card__hours--' + esc(loc.open_status || 'unknown') + '">' + esc(loc.open_label) + '</span></div>' : '';
			var whatsappUrl = '';
			if (features.whatsapp && config.whatsapp) {
				var waText = encodeURIComponent('Hi Travelay, I need help at ' + loc.title + (loc.airport_code ? ' (' + loc.airport_code + ')' : '') + '.');
				whatsappUrl = 'https://wa.me/' + esc(config.whatsapp) + '?text=' + waText;
			}
			var isPreferred = getPreferredSlug() === loc.slug;
			var routeBtn = features.mapDirections
				? '<button type="button" class="tl-btn-secondary" data-tl-action="route">' + esc(i18n.mapDirections || 'Show route') + '</button>'
				: '';
			var shareBtn = features.shareLink
				? '<button type="button" class="tl-btn-secondary" data-tl-action="share">' + esc(i18n.share || 'Share') + '</button>'
				: '';
			var setBtn = features.setLocation
				? '<button type="button" class="tl-btn-secondary' + (isPreferred ? ' is-active' : '') + '" data-tl-action="set-location">' + esc(isPreferred ? (i18n.myLocation || 'My location') : (i18n.setMyLocation || 'Set as my location')) + '</button>'
				: '';

			detailBody.innerHTML =
				'<h3 class="tl-detail__title">' + esc(loc.title) + '</h3>' +
				openHtml +
				'<div class="tl-detail__row"><strong>Address</strong>' + esc(loc.address) + '</div>' +
				terminalHtml + hoursHtml +
				(loc.is_247 ? '<div class="tl-detail__row"><span class="tl-card__badge">' + esc(i18n.open247 || '24/7 support') + '</span></div>' : '') +
				'<div class="tl-detail__actions">' +
				'<a class="tl-btn-primary" href="tel:' + esc(phoneDigits) + '" data-tl-track="call">' + esc(i18n.call || 'Call') + '</a>' +
				(whatsappUrl ? '<a class="tl-btn-secondary tl-btn-whatsapp" href="' + whatsappUrl + '" target="_blank" rel="noopener" data-tl-track="whatsapp">' + esc(i18n.whatsapp || 'WhatsApp') + '</a>' : '') +
				routeBtn +
				(loc.directions ? '<a class="tl-btn-secondary" href="' + esc(loc.directions) + '" target="_blank" rel="noopener" data-tl-track="external_directions">' + esc(i18n.directions || 'Get directions') + '</a>' : '') +
				(config.ctaUrl ? '<a class="tl-btn-secondary" href="' + esc(config.ctaUrl) + '" data-tl-track="book">' + esc(i18n.book || 'Book a flight') + '</a>' : '') +
				shareBtn + setBtn +
				'</div>';

			if (detailMedia) {
				var galleryHtml = '';
				if (loc.photo) {
					galleryHtml += '<img src="' + esc(loc.photo) + '" alt="' + esc(loc.photo_alt || loc.title) + '" loading="lazy" />';
				}
				if (loc.gallery && loc.gallery.length) {
					galleryHtml += '<div class="tl-detail__gallery">';
					loc.gallery.forEach(function (img) {
						galleryHtml += '<img src="' + esc(img.url) + '" alt="' + esc(img.alt || '') + '" loading="lazy" />';
					});
					galleryHtml += '</div>';
				}
				detailMedia.innerHTML = galleryHtml;
			}

			detailEl.hidden = false;
			detailEl.classList.add('is-open');
			if (window.matchMedia('(max-width: 767px)').matches) {
				document.body.classList.add('tl-detail-open');
			}

			detailBody.querySelectorAll('[data-tl-track]').forEach(function (link) {
				link.addEventListener('click', function () {
					trackEvent(link.getAttribute('data-tl-track'), { location_id: loc.id, slug: loc.slug });
				});
			});
		}

		function closeDetail() {
			if (!detailEl) return;
			detailEl.classList.remove('is-open');
			detailEl.hidden = true;
			document.body.classList.remove('tl-detail-open');
			clearRoute();
			root.querySelectorAll('[data-tl-card]').forEach(function (card) {
				card.classList.remove('is-selected');
			});
		}

		function clearRoute() {
			state.routeActive = false;
			if (state.map && state.map.getSource('route')) {
				state.map.getSource('route').setData({ type: 'FeatureCollection', features: [] });
			}
			if (routeStatusEl) {
				routeStatusEl.hidden = true;
				routeStatusEl.textContent = '';
			}
			var routeBtn = detailBody && detailBody.querySelector('[data-tl-action="route"]');
			if (routeBtn) {
				routeBtn.textContent = i18n.mapDirections || 'Show route';
			}
		}

		function showRoute(loc) {
			if (!features.mapDirections || !state.map || !config.osrmUrl) {
				if (loc.directions) window.open(loc.directions, '_blank', 'noopener');
				return;
			}

			function drawRoute(fromLng, fromLat) {
				var url = config.osrmUrl + '/' + fromLng + ',' + fromLat + ';' + loc.lng + ',' + loc.lat + '?overview=full&geometries=geojson';
				if (routeStatusEl) {
					routeStatusEl.hidden = false;
					routeStatusEl.textContent = '…';
				}

				fetch(url)
					.then(function (res) { return res.json(); })
					.then(function (data) {
						if (!data.routes || !data.routes[0]) {
							throw new Error('no route');
						}
						var route = data.routes[0];
						state.map.getSource('route').setData({
							type: 'Feature',
							properties: {},
							geometry: route.geometry,
						});
						state.routeActive = true;
						var routeBtn = detailBody.querySelector('[data-tl-action="route"]');
						if (routeBtn) routeBtn.textContent = i18n.clearRoute || 'Clear route';
						if (routeStatusEl) {
							var mins = Math.round((route.duration || 0) / 60);
							var distMi = ((route.distance || 0) * 0.000621371).toFixed(1);
							routeStatusEl.textContent = distMi + ' mi · ~' + mins + ' min';
							routeStatusEl.hidden = false;
						}
						fitRouteBounds(route.geometry.coordinates);
						trackEvent('map_directions', { location_id: loc.id, slug: loc.slug });
					})
					.catch(function () {
						if (routeStatusEl) {
							routeStatusEl.textContent = i18n.routeError || 'Could not load route.';
							routeStatusEl.hidden = false;
						}
						if (loc.directions) window.open(loc.directions, '_blank', 'noopener');
					});
			}

			if (state.userLat !== null && state.userLng !== null) {
				drawRoute(state.userLng, state.userLat);
				return;
			}

			if (!navigator.geolocation) {
				if (loc.directions) window.open(loc.directions, '_blank', 'noopener');
				return;
			}

			navigator.geolocation.getCurrentPosition(
				function (pos) {
					state.userLat = pos.coords.latitude;
					state.userLng = pos.coords.longitude;
					drawRoute(state.userLng, state.userLat);
				},
				function () {
					if (loc.directions) window.open(loc.directions, '_blank', 'noopener');
				},
				{ enableHighAccuracy: false, timeout: 10000, maximumAge: 120000 }
			);
		}

		function fitRouteBounds(coords) {
			if (!state.map || !coords || !coords.length) return;
			var bounds = new maplibregl.LngLatBounds(coords[0], coords[0]);
			coords.forEach(function (c) { bounds.extend(c); });
			state.map.fitBounds(bounds, { padding: 48, maxZoom: 13, duration: 800 });
		}

		function shareLocation(loc, btn) {
			var url = buildShareUrl(loc);
			if (navigator.share) {
				navigator.share({ title: loc.title, text: loc.address, url: url }).catch(function () {});
				trackEvent('share', { location_id: loc.id, method: 'native' });
				return;
			}
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(url).then(function () {
					var original = btn.textContent;
					btn.textContent = i18n.linkCopied || 'Link copied';
					setTimeout(function () { btn.textContent = original; }, 2000);
				});
				trackEvent('share', { location_id: loc.id, method: 'clipboard' });
				return;
			}
			window.prompt(i18n.share || 'Share', url);
		}

		function buildShareUrl(loc) {
			var base = config.pageUrl || window.location.href.split('?')[0];
			var join = base.indexOf('?') > -1 ? '&' : '?';
			return base + join + 'location=' + encodeURIComponent(loc.slug || loc.id);
		}

		function updateDeepLink(loc) {
			if (config.isEditor || !window.history || !window.history.replaceState) return;
			var url = new URL(window.location.href);
			url.searchParams.set('location', loc.slug || String(loc.id));
			window.history.replaceState({}, '', url.toString());
		}

		function getPreferredSlug() {
			try {
				return localStorage.getItem(PREF_KEY) || '';
			} catch (e) {
				return '';
			}
		}

		function setPreferredLocation(loc, btn) {
			try {
				localStorage.setItem(PREF_KEY, loc.slug || String(loc.id));
			} catch (e) {
				return;
			}
			root.querySelectorAll('[data-tl-card]').forEach(function (card) {
				card.classList.toggle('is-preferred', card.getAttribute('data-slug') === (loc.slug || String(loc.id)));
			});
			if (btn) {
				btn.textContent = i18n.myLocation || 'My location';
				btn.classList.add('is-active');
			}
			trackEvent('set_my_location', { location_id: loc.id, slug: loc.slug });
		}

		function sortByDistance() {
			if (state.userLat === null) return;
			locations.sort(function (a, b) {
				return haversine(state.userLat, state.userLng, a.lat, a.lng) - haversine(state.userLat, state.userLng, b.lat, b.lng);
			});
			root.querySelectorAll('[data-tl-card]').forEach(function (card) {
				var id = card.getAttribute('data-id');
				var loc = locById[id];
				var distEl = card.querySelector('[data-tl-distance]');
				if (!loc || !distEl) return;
				var km = haversine(state.userLat, state.userLng, loc.lat, loc.lng);
				var mi = km * 0.621371;
				distEl.textContent = mi < 120 ? mi.toFixed(1) + ' ' + (i18n.miAway || 'mi away') : Math.round(km) + ' ' + (i18n.kmAway || 'km away');
				distEl.hidden = false;
			});
			if (!listEl) return;
			locations.forEach(function (loc) {
				var card = root.querySelector('[data-tl-card][data-id="' + loc.id + '"]');
				if (card) listEl.appendChild(card);
			});
		}

		function resolveInitialLocation() {
			var params = new URLSearchParams(window.location.search);
			var param = (params.get('location') || '').toLowerCase();
			if (param && locBySlug[param]) {
				return locBySlug[param];
			}
			var preferred = getPreferredSlug();
			if (preferred && locBySlug[preferred.toLowerCase()]) {
				return locBySlug[preferred.toLowerCase()];
			}
			return null;
		}

		applyFilters();
		markPreferredCards();
		var initial = resolveInitialLocation();
		if (initial) {
			setTimeout(function () {
				selectLocation(initial.id, true);
			}, state.map ? 400 : 0);
		}

		function markPreferredCards() {
			var preferred = getPreferredSlug();
			if (!preferred) return;
			root.querySelectorAll('[data-tl-card]').forEach(function (card) {
				card.classList.toggle('is-preferred', card.getAttribute('data-slug') === preferred);
			});
		}

		initialized.set(root, state);
	}

	function buildGeoJSON(locations) {
		return {
			type: 'FeatureCollection',
			features: locations.map(function (loc) {
				return {
					type: 'Feature',
					properties: { id: String(loc.id), title: loc.title },
					geometry: { type: 'Point', coordinates: [loc.lng, loc.lat] },
				};
			}),
		};
	}

	function getCenter(locations) {
		if (!locations.length) return [-98, 38];
		if (locations.length === 1) return [locations[0].lng, locations[0].lat];
		var lng = 0;
		var lat = 0;
		locations.forEach(function (l) { lng += l.lng; lat += l.lat; });
		return [lng / locations.length, lat / locations.length];
	}

	function haversine(lat1, lon1, lat2, lon2) {
		var R = 6371;
		var dLat = toRad(lat2 - lat1);
		var dLon = toRad(lon2 - lon1);
		var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
			Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) * Math.sin(dLon / 2);
		return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
	}

	function toRad(deg) { return deg * (Math.PI / 180); }

	function esc(str) {
		if (!str) return '';
		return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
	}

	function nl2br(str) { return str.replace(/\n/g, '<br>'); }
})();
