(function ($) {
	'use strict';

	function getVisibleRows($table) {
		return $table.find('tbody tr.tcw-profile-row:visible');
	}

	function syncRowSelection($table) {
		$table.find('tbody tr.tcw-profile-row').each(function () {
			var $row = $(this);
			$row.toggleClass('is-selected', $row.find('.tcw-profile-cb').is(':checked'));
		});
	}

	function updateBulkState($root) {
		var $table = $root.find('.tcw-table');
		var $checked = $table.find('.tcw-profile-cb:checked');
		var count = $checked.length;
		var $bulkBtn = $root.find('#tcw-bulk-delete-btn');
		var $countLabel = $root.find('#tcw-selected-count');

		if ($bulkBtn.length) {
			$bulkBtn.prop('disabled', count === 0);
		}

		if ($countLabel.length) {
			$countLabel.text(
				count
					? window.tcwAdminProfiles.i18n.selected.replace('%d', String(count))
					: window.tcwAdminProfiles.i18n.noneSelected
			);
		}

		var $visible = getVisibleRows($table);
		var $visibleChecked = $visible.find('.tcw-profile-cb:checked');
		var $selectAll = $root.find('#tcw-select-all');

		if (!$selectAll.length) {
			syncRowSelection($table);
			return;
		}

		if (!$visible.length) {
			$selectAll.prop({ checked: false, indeterminate: false });
		} else {
			$selectAll.prop({
				checked: $visibleChecked.length === $visible.length,
				indeterminate: $visibleChecked.length > 0 && $visibleChecked.length < $visible.length,
			});
		}

		syncRowSelection($table);
	}

	function filterRows($root, query) {
		var $table = $root.find('.tcw-table');
		var term = String(query || '').toLowerCase().trim();
		var shown = 0;
		var total = 0;
		var $clear = $root.find('#tcw-search-clear');

		$table.find('tbody tr.tcw-profile-row').each(function () {
			var $row = $(this);
			var haystack = String($row.attr('data-search') || '').toLowerCase();
			var match = !term || haystack.indexOf(term) !== -1;
			total++;
			if (match) {
				shown++;
				$row.show();
			} else {
				$row.hide();
				$row.find('.tcw-profile-cb').prop('checked', false);
			}
		});

		$root.find('#tcw-no-matches').toggle(total > 0 && shown === 0);
		$root.find('#tcw-profiles-shown').text(
			window.tcwAdminProfiles.i18n.showing
				.replace('%1$d', String(shown))
				.replace('%2$d', String(total))
		);

		if ($clear.length) {
			$clear.prop('hidden', !term);
		}

		updateBulkState($root);
	}

	$(function () {
		var $root = $('.tcw-profiles-section');
		if (!$root.length || !window.tcwAdminProfiles) {
			return;
		}

		var $search = $root.find('#tcw-profile-search');
		var $form = $root.find('#tcw-bulk-delete-form');
		var $clear = $root.find('#tcw-search-clear');

		$search.on('input', function () {
			filterRows($root, $search.val());
		});

		$clear.on('click', function () {
			$search.val('').trigger('focus');
			filterRows($root, '');
		});

		$root.on('change', '#tcw-select-all', function () {
			var checked = $(this).is(':checked');
			getVisibleRows($root.find('.tcw-table')).each(function () {
				$(this).find('.tcw-profile-cb').prop('checked', checked);
			});
			updateBulkState($root);
		});

		$root.on('change', '.tcw-profile-cb', function () {
			updateBulkState($root);
		});

		$form.on('submit', function (event) {
			var count = $root.find('.tcw-profile-cb:checked').length;
			if (!count) {
				event.preventDefault();
				return;
			}

			var message = window.tcwAdminProfiles.i18n.confirmBulk.replace('%d', String(count));
			if (!window.confirm(message)) {
				event.preventDefault();
			}
		});

		filterRows($root, '');
	});
}(jQuery));
