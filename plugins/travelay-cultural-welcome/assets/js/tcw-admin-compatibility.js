(function ($) {
	'use strict';

	var STATUS_LABELS = {
		pass: 'Pass',
		warn: 'Warning',
		fail: 'Critical',
		info: 'Info'
	};

	function escapeHtml(text) {
		return $('<div>').text(text || '').html();
	}

	function renderSummary($root, data) {
		var s = data.summary || {};
		$root.find('.tcw-compat-summary__counts').html(
			'<span class="tcw-compat-count tcw-compat-count--pass">' + (s.pass || 0) + ' ' + escapeHtml(window.tcwAdminCompat.i18n.passed) + '</span>' +
			'<span class="tcw-compat-count tcw-compat-count--warn">' + (s.warn || 0) + ' ' + escapeHtml(window.tcwAdminCompat.i18n.warnings) + '</span>' +
			'<span class="tcw-compat-count tcw-compat-count--fail">' + (s.fail || 0) + ' ' + escapeHtml(window.tcwAdminCompat.i18n.critical) + '</span>'
		);
		$root.find('.tcw-compat-summary__verdict').text(data.verdict || '');
		if (data.run_at) {
			$root.find('.tcw-compat-summary__time').text(
				window.tcwAdminCompat.i18n.ranAt.replace('%s', new Date(data.run_at * 1000).toLocaleString())
			);
		}
		$root.find('.tcw-compat-results').prop('hidden', false);
	}

	function renderGroups($root, groups) {
		var $container = $root.find('.tcw-compat-groups').empty();

		(groups || []).forEach(function (group) {
			var $section = $('<section class="tcw-compat-group"></section>');
			$section.append('<h3 class="tcw-compat-group__title">' + escapeHtml(group.label) + '</h3>');
			var $list = $('<ul class="tcw-compat-list"></ul>');

			(group.checks || []).forEach(function (check) {
				var status = check.status || 'info';
				var $item = $('<li class="tcw-compat-item tcw-compat-item--' + status + '"></li>');
				var html = '<div class="tcw-compat-item__head">' +
					'<span class="tcw-compat-badge tcw-compat-badge--' + status + '">' + escapeHtml(STATUS_LABELS[status] || status) + '</span>' +
					'<strong class="tcw-compat-item__label">' + escapeHtml(check.label) + '</strong>' +
					'</div>' +
					'<p class="tcw-compat-item__message">' + escapeHtml(check.message) + '</p>';

				if (check.fix) {
					html += '<p class="tcw-compat-item__fix"><strong>' + escapeHtml(window.tcwAdminCompat.i18n.fix) + ':</strong> ' + escapeHtml(check.fix) + '</p>';
				}
				if (check.url) {
					html += '<p class="tcw-compat-item__link"><a href="' + escapeHtml(check.url) + '">' + escapeHtml(window.tcwAdminCompat.i18n.view) + '</a></p>';
				}

				$item.html(html);
				$list.append($item);
			});

			$section.append($list);
			$container.append($section);
		});
	}

	function renderReport($root, data) {
		renderSummary($root, data);
		renderGroups($root, data.groups || []);
		$('#tcw-copy-compatibility').prop('disabled', false);
	}

	function copyReport(data) {
		var lines = [data.verdict || '', ''];
		(data.groups || []).forEach(function (group) {
			lines.push(group.label);
			(group.checks || []).forEach(function (check) {
				lines.push('  [' + (check.status || '').toUpperCase() + '] ' + check.label + ': ' + check.message);
				if (check.fix) {
					lines.push('    Fix: ' + check.fix);
				}
			});
			lines.push('');
		});
		return lines.join('\n');
	}

	$(function () {
		var $card = $('#tcw-compatibility-card');
		if (!$card.length || !window.tcwAdminCompat) {
			return;
		}

		var $btn = $('#tcw-run-compatibility');
		var $copy = $('#tcw-copy-compatibility');
		var lastReport = window.tcwAdminCompat.cached || null;

		if (lastReport) {
			renderReport($card, lastReport);
		}

		$btn.on('click', function () {
			$btn.prop('disabled', true).addClass('is-running');
			$card.find('.tcw-compat-running').prop('hidden', false);

			$.post(window.tcwAdminCompat.ajaxUrl, {
				action: 'tcw_run_compatibility',
				nonce: window.tcwAdminCompat.nonce
			}).done(function (response) {
				if (!response || !response.success) {
					throw new Error((response && response.data && response.data.message) || window.tcwAdminCompat.i18n.error);
				}
				lastReport = response.data;
				renderReport($card, lastReport);
			}).fail(function (xhr) {
				var msg = window.tcwAdminCompat.i18n.error;
				if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
					msg = xhr.responseJSON.data.message;
				}
				window.alert(msg);
			}).always(function () {
				$btn.prop('disabled', false).removeClass('is-running');
				$card.find('.tcw-compat-running').prop('hidden', true);
			});
		});

		$copy.on('click', function () {
			if (!lastReport) {
				return;
			}
			var text = copyReport(lastReport);
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(text).then(function () {
					$copy.text(window.tcwAdminCompat.i18n.copied);
					window.setTimeout(function () {
						$copy.text(window.tcwAdminCompat.i18n.copy);
					}, 2000);
				});
			} else {
				window.prompt(window.tcwAdminCompat.i18n.copy, text);
			}
		});
	});
}(jQuery));
