/**
 * Admin Rive input scanner — loads Rive runtime only on demand.
 */
(function ($) {
	'use strict';

	var cfg = window.tcwAdminRive || {};
	var riveScriptLoaded = false;
	var scanInstance = null;

	function loadRiveScript() {
		if (window.rive) {
			return Promise.resolve();
		}
		if (riveScriptLoaded) {
			return new Promise(function (resolve) {
				var tries = 0;
				var timer = setInterval(function () {
					tries += 1;
					if (window.rive || tries > 40) {
						clearInterval(timer);
						resolve();
					}
				}, 50);
			});
		}

		riveScriptLoaded = true;
		return new Promise(function (resolve, reject) {
			var src = 'https://unpkg.com/@rive-app/canvas@2.23.4/rive.js';
			if (document.querySelector('script[src="' + src + '"]')) {
				resolve();
				return;
			}
			var s = document.createElement('script');
			s.src = src;
			s.async = true;
			s.onload = resolve;
			s.onerror = reject;
			document.head.appendChild(s);
		});
	}

	function inputTypeLabel(input) {
		if (!input) return 'unknown';
		if (typeof input.type === 'string') {
			return input.type.toLowerCase();
		}
		if (window.rive && window.rive.StateMachineInputType) {
			var t = window.rive.StateMachineInputType;
			if (input.type === t.Trigger || input.type === 58) return 'trigger';
			if (input.type === t.Boolean || input.type === 59) return 'boolean';
			if (input.type === t.Number || input.type === 56) return 'number';
		}
		if (input.type === 58) return 'trigger';
		if (input.type === 59) return 'boolean';
		if (input.type === 56) return 'number';
		return 'unknown';
	}

	function cleanupScanInstance() {
		if (scanInstance && typeof scanInstance.cleanup === 'function') {
			try {
				scanInstance.cleanup();
			} catch (e) { /* noop */ }
		}
		scanInstance = null;
		var host = document.getElementById('tcw-rive-scan-host');
		if (host) {
			host.innerHTML = '';
		}
	}

	function createScanCanvas() {
		var host = document.getElementById('tcw-rive-scan-host');
		if (!host) {
			host = document.createElement('div');
			host.id = 'tcw-rive-scan-host';
			host.style.cssText = 'position:absolute;left:-9999px;width:2px;height:2px;overflow:hidden;';
			document.body.appendChild(host);
		}
		host.innerHTML = '<canvas width="2" height="2"></canvas>';
		return host.querySelector('canvas');
	}

	function loadRiveForScan(fileUrl, stateMachine) {
		return new Promise(function (resolve, reject) {
			var settled = false;
			var timer = setTimeout(function () {
				if (!settled) {
					settled = true;
					reject(new Error('Scan timed out'));
				}
			}, 20000);

			var canvas = createScanCanvas();
			var opts = {
				src: fileUrl,
				canvas: canvas,
				autoplay: true,
				onLoad: function () {
					if (settled) return;
					settled = true;
					clearTimeout(timer);
					resolve(scanInstance);
				},
				onLoadError: function () {
					if (settled) return;
					settled = true;
					clearTimeout(timer);
					reject(new Error('Rive load failed'));
				},
			};

			if (stateMachine) {
				opts.stateMachines = stateMachine;
			}

			scanInstance = new window.rive.Rive(opts);
		});
	}

	function collectInputsFromInstance(instance, machineName) {
		var inputs = [];
		try {
			(instance.stateMachineInputs(machineName) || []).forEach(function (input) {
				var name = String(input.name || '').trim();
				if (!name) return;
				inputs.push({
					name: name,
					type: inputTypeLabel(input),
				});
			});
		} catch (e) { /* noop */ }
		return inputs;
	}

	function scanRiveFile(fileUrl) {
		return loadRiveScript().then(function () {
			if (!window.rive) {
				throw new Error('Rive runtime unavailable');
			}

			cleanupScanInstance();

			return loadRiveForScan(fileUrl, null).then(function (probe) {
				var names = [];
				try {
					names = probe.stateMachineNames || [];
				} catch (e) { /* noop */ }

				if (typeof probe.cleanup === 'function') {
					probe.cleanup();
				}
				scanInstance = null;

				if (!names.length) {
					names = ['State Machine 1'];
				}

				return names.reduce(function (chain, machineName) {
					return chain.then(function (machines) {
						return loadRiveForScan(fileUrl, machineName).then(function (loaded) {
							var inputs = collectInputsFromInstance(loaded, machineName);
							if (typeof loaded.cleanup === 'function') {
								loaded.cleanup();
							}
							scanInstance = null;
							machines.push({ name: machineName, inputs: inputs });
							return machines;
						});
					});
				}, Promise.resolve([])).then(function (machines) {
					cleanupScanInstance();
					return { stateMachines: machines };
				});
			});
		});
	}

	function getMachines(scan) {
		if (!scan) return [];
		if (scan.state_machines && scan.state_machines.length) return scan.state_machines;
		if (scan.stateMachines && scan.stateMachines.length) return scan.stateMachines;
		return [];
	}

	function normalizeClientScan(scanPayload) {
		return {
			scanned_at: Math.floor(Date.now() / 1000),
			state_machines: (scanPayload.stateMachines || []).map(function (machine) {
				return {
					name: String(machine.name || ''),
					inputs: (machine.inputs || []).map(function (input) {
						return {
							name: String(input.name || ''),
							type: String(input.type || 'unknown').toLowerCase(),
						};
					}),
				};
			}),
		};
	}

	function getMachine(scan, machineName) {
		var machines = getMachines(scan);
		for (var i = 0; i < machines.length; i += 1) {
			if (machines[i].name === machineName) {
				return machines[i];
			}
		}
		return machines[0] || null;
	}

	function fillSelect($select, options, selected, allowEmpty) {
		$select.empty();
		if (allowEmpty) {
			$select.append($('<option>', { value: '', text: cfg.i18n.none || '— None —' }));
		}
		options.forEach(function (opt) {
			$select.append(
				$('<option>', {
					value: opt.value,
					text: opt.label,
					selected: selected === opt.value,
				})
			);
		});
		$select.prop('disabled', options.length === 0 && !allowEmpty);
	}

	function syncTapHidden() {
		var selected = [];
		$('#rive_tap_triggers option:selected').each(function () {
			var val = $(this).val();
			if (val) selected.push(val);
		});
		$('#rive_tap_triggers_json').val(JSON.stringify(selected));
	}

	function renderFromScan(scan, settings) {
		settings = settings || cfg.settings || {};
		var machines = getMachines(scan);
		var machineOptions = machines.map(function (m) {
			return { value: m.name, label: m.name };
		});

		var machineName = settings.rive_state_machine || (machines[0] && machines[0].name) || '';
		fillSelect($('#rive_state_machine'), machineOptions, machineName, false);
		$('#rive_state_machine').prop('disabled', machineOptions.length === 0);

		var machine = getMachine(scan, machineName);
		var inputs = (machine && machine.inputs) || [];

		var booleans = inputs
			.filter(function (i) { return i.type === 'boolean'; })
			.map(function (i) { return { value: i.name, label: i.name + ' (boolean)' }; });
		var triggers = inputs
			.filter(function (i) { return i.type === 'trigger'; })
			.map(function (i) { return { value: i.name, label: i.name + ' (trigger)' }; });
		var unknowns = inputs
			.filter(function (i) { return i.type !== 'boolean' && i.type !== 'trigger'; })
			.map(function (i) { return { value: i.name, label: i.name + ' (' + i.type + ')' }; });

		var hoverOptions = booleans.length ? booleans : unknowns;
		var triggerOptions = triggers.length ? triggers : unknowns;

		fillSelect($('#rive_hover_input'), hoverOptions, settings.rive_hover_input || '', true);
		fillSelect($('#rive_entry_trigger'), triggerOptions, settings.rive_entry_trigger || '', true);

		var $tap = $('#rive_tap_triggers');
		$tap.empty();
		triggerOptions.forEach(function (opt) {
			$tap.append($('<option>', { value: opt.value, text: opt.label }));
		});
		$tap.prop('disabled', triggerOptions.length === 0);

		if (inputs.length) {
			setStatus(
				(inputs.length + ' input(s) in "' + machineName + '" — ' +
				booleans.length + ' boolean, ' + triggers.length + ' trigger.'),
				false
			);
		} else if (machineName) {
			setStatus('State machine found but no inputs listed. Re-scan after saving the .riv file.', true);
		}

		var tapSelected = settings.rive_tap_triggers || [];
		if (typeof tapSelected === 'string') {
			try { tapSelected = JSON.parse(tapSelected); } catch (e) { tapSelected = []; }
		}
		$tap.val(tapSelected);
		syncTapHidden();
	}

	function setStatus(message, isError) {
		var $el = $('#tcw-rive-status');
		$el.text(message || '');
		$el.css('color', isError ? '#b45309' : '');
	}

	function setScanMeta(scan) {
		var $meta = $('#tcw-rive-scan-meta');
		if (!scan || !scan.scanned_at) {
			$meta.text('');
			return;
		}
		var date = new Date(scan.scanned_at * 1000);
		$meta.text((cfg.i18n.lastScanned || 'Last scanned:') + ' ' + date.toLocaleString());
	}

	function saveScan(scanPayload) {
		var ajaxUrl = cfg.ajaxUrl || (typeof window.ajaxurl !== 'undefined' ? window.ajaxurl : '');

		return $.ajax({
			url: ajaxUrl,
			method: 'POST',
			dataType: 'json',
			data: {
				action: 'tcw_save_rive_scan',
				_ajax_nonce: cfg.ajaxNonce,
				profile_id: cfg.profileId,
				scan: JSON.stringify(scanPayload),
			},
		}).then(function (response) {
			if (!response || !response.success) {
				var message = (response && response.data && response.data.message) || 'Could not save scan';
				throw new Error(message);
			}
			return response.data;
		});
	}

	function initPanel() {
		var $panel = $('#tcw-rive-panel');
		if (!$panel.length) return;

		if (!cfg.profileId) {
			setStatus(cfg.i18n.saveFirst || 'Save the profile first to scan Rive inputs.');
			$('#tcw-rive-scan-btn').prop('disabled', true);
			return;
		}

		if (!cfg.hasFile) {
			setStatus(cfg.i18n.noFile || 'No .riv file found for this slug.', true);
			$('#tcw-rive-scan-btn').prop('disabled', true);
			return;
		}

		if (getMachines(cfg.scan).length) {
			renderFromScan(cfg.scan, cfg.settings || {});
			setScanMeta(cfg.scan);
		}

		$('#rive_state_machine').on('change', function () {
			renderFromScan(cfg.scan, {
				rive_state_machine: $(this).val(),
				rive_hover_input: '',
				rive_entry_trigger: '',
				rive_tap_triggers: [],
			});
		});

		$('#rive_hover_input, #rive_entry_trigger, #rive_tap_triggers').on('change', syncTapHidden);

		$('form').on('submit', function () {
			syncTapHidden();
		});

		$('#tcw-rive-scan-btn').on('click', function () {
			var $btn = $(this);
			if ($btn.prop('disabled')) return;

			$btn.prop('disabled', true).text(cfg.i18n.scanning || 'Scanning…');
			setStatus('');

			scanRiveFile(cfg.fileUrl)
				.then(function (scanPayload) {
					var localScan = normalizeClientScan(scanPayload);
					cfg.scan = localScan;
					renderFromScan(localScan, cfg.settings || {});
					$('#rive_scan_cache').val(JSON.stringify(localScan));
					return saveScan(scanPayload);
				})
				.then(function (data) {
					cfg.scan = (data && data.scan) || cfg.scan;
					cfg.settings = (data && data.profile && data.profile.settings) || cfg.settings || {};
					renderFromScan(cfg.scan, cfg.settings);
					setScanMeta(cfg.scan);
					$('#rive_scan_cache').val(JSON.stringify(cfg.scan));
					setStatus(cfg.i18n.scanDone || 'Scan complete.');
				})
				.catch(function (err) {
					if (getMachines(cfg.scan).length) {
						setStatus(
							'Inputs loaded locally. ' +
							((err && err.message) ? err.message + '. ' : '') +
							'Click Save Profile to store them.',
							true
						);
					} else {
						setStatus(
							(err && err.message) || cfg.i18n.scanError || 'Could not scan Rive file.',
							true
						);
					}
				})
				.finally(function () {
					$btn.prop('disabled', false).text(cfg.i18n.scan || 'Scan Rive inputs');
				});
		});

		$('#location_slug').on('change blur', function () {
			setStatus(
				(cfg.i18n.noFile || 'No .riv file found.') + ' ' +
				'Re-save the profile after changing the slug to refresh Rive file detection.'
			);
		});
	}

	$(initPanel);
})(jQuery);
