(function ($) {
	'use strict';

	function setStatus($el, message, type) {
		$el.removeClass('is-success is-error').addClass(type === 'success' ? 'is-success' : (type === 'error' ? 'is-error' : ''));
		$el.find('p').text(message || '');
		if (message) {
			$el.prop('hidden', false);
		} else {
			$el.prop('hidden', true);
		}
	}

	function runBatch(config, state) {
		return $.post(config.ajaxUrl, {
			action: 'tcw_sync_batch',
			nonce: config.nonce
		}).then(function (response) {
			if (!response || !response.success) {
				throw new Error((response && response.data && response.data.message) || config.i18n.error);
			}

			var data = response.data;
			state.created = data.created;
			state.updated = data.updated;
			state.skipped = data.skipped;

			setStatus(config.$status, data.message, data.done ? 'success' : 'info');
			config.$bar.val(Math.min(data.offset, data.total));
			config.$bar.attr('max', data.total);

			if (!data.done) {
				return runBatch(config, state);
			}

			if (data.done && (data.created || data.updated)) {
				window.setTimeout(function () {
					window.location.href = config.redirectUrl || window.location.href.split('#')[0];
				}, 1200);
			}

			return data;
		});
	}

	$(function () {
		var $form = $('#tcw-sync-pages-form');
		if (!$form.length || !window.tcwAdminSync) {
			return;
		}

		var config = window.tcwAdminSync;
		config.$status = $('#tcw-sync-status');
		config.$bar = $('#tcw-sync-progress');
		config.$button = $('#tcw-sync-pages-button');

		$form.on('submit', function (event) {
			event.preventDefault();

			config.$button.prop('disabled', true);
			config.$bar.val(0).attr('max', 100).prop('hidden', false);
			setStatus(config.$status, config.i18n.starting, 'info');

			$.post(config.ajaxUrl, {
				action: 'tcw_sync_start',
				nonce: config.nonce,
				scope: $('#tcw-sync-scope').val()
			}).then(function (response) {
				if (!response || !response.success) {
					throw new Error((response && response.data && response.data.message) || config.i18n.error);
				}

				var total = response.data.total || 0;
				config.$bar.attr('max', total || 100);
				setStatus(config.$status, response.data.message, 'info');

				if (!total) {
					setStatus(config.$status, config.i18n.empty, 'info');
					return null;
				}

				return runBatch(config, { created: 0, updated: 0, skipped: 0 });
			}).fail(function (xhr) {
				var message = config.i18n.error;
				if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
					message = xhr.responseJSON.data.message;
				}
				setStatus(config.$status, message, 'error');
			}).always(function () {
				config.$button.prop('disabled', false);
			});
		});
	});
}(jQuery));
