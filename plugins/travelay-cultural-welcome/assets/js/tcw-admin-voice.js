(function ($) {
	'use strict';

	if (!window.tcwVoiceCatalog) {
		return;
	}

	var catalog = window.tcwVoiceCatalog;
	var $language = $('#tcw_voice_language');
	var $feature = $('#tcw_voice_feature');
	var $voice = $('#tcw_voice_name');
	var $preview = $('#tcw-voice-preview-meta');

	function uniqueVoicesByName(voices) {
		var seen = {};
		return voices.filter(function (voice) {
			if (seen[voice.name]) {
				return false;
			}
			seen[voice.name] = true;
			return true;
		});
	}

	function voicesForSelection() {
		var lang = $language.val();
		var feature = $feature.val();
		var list = catalog.voices || [];

		if (lang) {
			list = list.filter(function (voice) {
				return voice.language === lang;
			});
		}

		if (feature) {
			list = list.filter(function (voice) {
				return voice.features && voice.features.indexOf(feature) !== -1;
			});
		}

		return uniqueVoicesByName(list);
	}

	function populateVoiceOptions() {
		var current = $voice.data('selected') || $voice.val() || '';
		var voices = voicesForSelection();

		$voice.empty();
		$voice.append(
			$('<option>', {
				value: '',
				text: tcwVoiceCatalog.i18n.autoVoice,
			})
		);

		voices.forEach(function (voice) {
			$voice.append(
				$('<option>', {
					value: voice.name,
					text: voice.label + ' — ' + voice.name,
				})
			);
		});

		if (current && $voice.find('option[value="' + current.replace(/"/g, '\\"') + '"]').length) {
			$voice.val(current);
		} else {
			$voice.val('');
		}

		updatePreviewMeta();
	}

	function updatePreviewMeta() {
		if (!$preview.length) {
			return;
		}

		var name = $voice.val();
		if (!name) {
			$preview.text(tcwVoiceCatalog.i18n.autoVoiceHint);
			return;
		}

		var match = (catalog.voices || []).find(function (voice) {
			return voice.name === name;
		});

		if (!match) {
			$preview.text(name);
			return;
		}

		$preview.text(
			match.label +
				' · ' +
				match.sample_rate +
				'Hz · ' +
				(match.features || []).join(', ')
		);
	}

	function bindLanguageOptions() {
		$language.empty();
		$language.append(
			$('<option>', {
				value: '',
				text: tcwVoiceCatalog.i18n.autoLanguage,
			})
		);

		(catalog.languages || []).forEach(function (language) {
			$language.append(
				$('<option>', {
					value: language.code,
					text: language.label + ' (' + language.voice_count + ' voices)',
				})
			);
		});
	}

	function bindFeatureOptions() {
		$feature.empty();
		$feature.append(
			$('<option>', {
				value: '',
				text: tcwVoiceCatalog.i18n.allFeatures,
			})
		);

		(catalog.features || []).forEach(function (feature) {
			$feature.append(
				$('<option>', {
					value: feature,
					text: feature,
				})
			);
		});
	}

	$language.on('change', populateVoiceOptions);
	$feature.on('change', populateVoiceOptions);
	$voice.on('change', updatePreviewMeta);

	bindLanguageOptions();
	bindFeatureOptions();
	populateVoiceOptions();
})(jQuery);
