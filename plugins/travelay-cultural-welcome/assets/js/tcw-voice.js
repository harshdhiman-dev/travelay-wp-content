/**
 * Travelay Cultural Welcome — Google TTS voice playback (single source).
 */
(function (window) {
	'use strict';

	var sharedAudio = null;
	var blockedUrl = '';
	var blockedVolume = 0.9;
	var unlockHandler = null;
	var unlockScope = null;

	function normalizeVolume(volume) {
		return typeof volume === 'number' ? Math.max(0, Math.min(1, volume)) : 0.9;
	}

	function sameSource(url, audio) {
		if (!audio || !url) return false;
		try {
			var resolved = new URL(url, window.location.href).href;
			var current = new URL(audio.currentSrc || audio.src, window.location.href).href;
			return resolved === current;
		} catch (e) {
			return (audio.src || '') === url;
		}
	}

	function ensureAudio(url, volume) {
		blockedVolume = normalizeVolume(volume);

		if (!sharedAudio || !sameSource(url, sharedAudio)) {
			sharedAudio = new Audio(url);
			sharedAudio.preload = 'auto';
			sharedAudio.setAttribute('playsinline', '');
			sharedAudio.playsInline = true;
		}

		sharedAudio.volume = blockedVolume;
		return sharedAudio;
	}

	function resetAudio(audio) {
		if (!audio) return;
		try {
			audio.pause();
			audio.currentTime = 0;
		} catch (e) { /* noop */ }
	}

	function clearUnlockHandler() {
		if (!unlockHandler) return;
		var target = unlockScope || document;
		target.removeEventListener('pointerdown', unlockHandler, true);
		target.removeEventListener('keydown', unlockHandler, true);
		unlockHandler = null;
		unlockScope = null;
	}

	window.TCWVoice = {
		preload: function (url, volume) {
			if (!url) return null;
			var audio = ensureAudio(url, volume);
			try {
				audio.load();
			} catch (e) { /* noop */ }
			return audio;
		},

		play: function (url, volume) {
			if (!url) {
				return Promise.reject(new Error('missing_url'));
			}

			var audio = ensureAudio(url, volume);

			if (!audio.paused && !audio.ended && sameSource(url, audio)) {
				return Promise.resolve(audio);
			}

			resetAudio(audio);

			return audio.play().then(function () {
				blockedUrl = '';
				clearUnlockHandler();
				return audio;
			}).catch(function (err) {
				blockedUrl = url;
				blockedVolume = audio.volume;
				throw err;
			});
		},

		stop: function () {
			clearUnlockHandler();
			blockedUrl = '';
			resetAudio(sharedAudio);
		},

		isPlaying: function () {
			return !!(sharedAudio && !sharedAudio.paused && !sharedAudio.ended);
		},

		resumeIfBlocked: function () {
			if (!blockedUrl) {
				return Promise.resolve();
			}
			return this.play(blockedUrl, blockedVolume);
		},

		isBlocked: function () {
			return !!blockedUrl;
		},

		armGestureUnlock: function (onUnlock, scopeEl) {
			if (!blockedUrl || unlockHandler) {
				return;
			}

			unlockScope = scopeEl || document;

			unlockHandler = function (event) {
				if (unlockScope !== document) {
					if (event && event.target && !unlockScope.contains(event.target)) {
						return;
					}
				}

				clearUnlockHandler();
				window.TCWVoice.resumeIfBlocked().then(function () {
					if (typeof onUnlock === 'function') {
						onUnlock();
					}
				}).catch(function () {
					if (blockedUrl) {
						window.TCWVoice.armGestureUnlock(onUnlock, scopeEl);
					}
				});
			};

			unlockScope.addEventListener('pointerdown', unlockHandler, true);
			unlockScope.addEventListener('keydown', unlockHandler, true);
		},
	};
})(window);
