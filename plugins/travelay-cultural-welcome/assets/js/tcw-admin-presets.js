(function ($) {
	'use strict';

	if (!window.tcwAdminPresets) {
		return;
	}

	var config = window.tcwAdminPresets;
	var $preset = $('#tcw-experience-preset');
	var $description = $('#tcw-preset-description');
	var $featureRows = $('.tcw-preset-feature');

	function presetFlags(key) {
		return config.presets[key] || null;
	}

	function isCustomMode() {
		return $preset.val() === 'custom';
	}

	function setCheckbox(name, checked) {
		var $input = $('input[name="' + config.optionKey + '[' + name + ']"]');
		if ($input.length) {
			$input.prop('checked', !!checked);
		}
	}

	function applyPresetToForm(key) {
		var flags = presetFlags(key);
		if (!flags) {
			return;
		}

		Object.keys(flags).forEach(function (flagKey) {
			if (flagKey === 'label' || flagKey === 'description') {
				return;
			}
			setCheckbox(flagKey, flags[flagKey]);
		});
	}

	function updateDescription() {
		var key = $preset.val();
		var preset = config.presets[key];
		if (!preset) {
			return;
		}

		$description.text(preset.description || '');
		$featureRows.toggleClass('tcw-preset-locked', !isCustomMode());
		$featureRows.find('input[type="checkbox"]').prop('disabled', !isCustomMode());
	}

	$preset.on('change', function () {
		var key = $preset.val();
		if (key !== 'custom') {
			applyPresetToForm(key);
		}
		updateDescription();
	});

	$featureRows.find('input[type="checkbox"]').on('change', function () {
		if (isCustomMode()) {
			return;
		}
		$preset.val('custom');
		updateDescription();
	});

	updateDescription();
}(jQuery));
