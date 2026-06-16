/**
 * Travelay Cultural Welcome — synthesized celebration audio (Web Audio API).
 */
(function (window) {
	'use strict';

	var audioCtx = null;

	function getContext() {
		if (!audioCtx) {
			var Ctx = window.AudioContext || window.webkitAudioContext;
			if (!Ctx) return null;
			audioCtx = new Ctx();
		}
		return audioCtx;
	}

	var motifs = {
		default:       [523.25, 659.25, 783.99],
		royal_celebration: [392, 493.88, 587.33, 739.99],
		festival:      [440, 554.37, 659.25, 880],
		sakura_bloom:  [587.33, 698.46, 880],
		la_dolce:      [493.88, 622.25, 739.99],
		carnival:      [523.25, 659.25, 783.99, 1046.5],
		southern_cross:[440, 554.37, 659.25],
		desert_gold:   [369.99, 466.16, 554.37],
		elegance:      [523.25, 622.25, 739.99],
		fiesta:        [493.88, 587.33, 698.46, 880],
		papel_picado:  [440, 523.25, 659.25, 783.99],
		maple_snow:    [392, 493.88, 587.33],
		stars_stripes: [523.25, 659.25, 783.99, 987.77],
		tulip_spring:  [587.33, 698.46, 830.61],
		aegean:        [440, 554.37, 659.25, 739.99],
		winter_gold:   [392, 493.88, 587.33, 698.46],
		alpine:        [523.25, 659.25, 783.99],
	};

	function playTone(ctx, freq, start, duration, volume) {
		var osc = ctx.createOscillator();
		var gain = ctx.createGain();
		osc.type = 'sine';
		osc.frequency.setValueAtTime(freq, start);
		gain.gain.setValueAtTime(0, start);
		gain.gain.linearRampToValueAtTime(volume, start + 0.02);
		gain.gain.exponentialRampToValueAtTime(0.001, start + duration);
		osc.connect(gain);
		gain.connect(ctx.destination);
		osc.start(start);
		osc.stop(start + duration + 0.05);
	}

	function playMotif(ctx, motif, volume) {
		var freqs = motifs[motif] || motifs.default;
		var vol = typeof volume === 'number' ? volume : 0.22;
		var now = ctx.currentTime + 0.05;

		freqs.forEach(function (f, i) {
			playTone(ctx, f, now + i * 0.11, 0.45, vol);
		});
	}

	function ensureRunning(ctx) {
		if (!ctx) {
			return Promise.resolve(false);
		}
		if (ctx.state === 'running') {
			return Promise.resolve(true);
		}
		return ctx.resume().then(function () {
			return ctx.state === 'running';
		}).catch(function () {
			return false;
		});
	}

	window.TCWSound = {
		canPlay: function () {
			var ctx = getContext();
			return !!(ctx && ctx.state === 'running');
		},

		unlock: function () {
			return ensureRunning(getContext());
		},

		play: function (motif, volume) {
			var ctx = getContext();
			if (!ctx) {
				return Promise.resolve();
			}

			return ensureRunning(ctx).then(function () {
				try {
					playMotif(ctx, motif, volume);
				} catch (e) { /* silent */ }
			});
		},

		playTap: function (volume) {
			var ctx = getContext();
			if (!ctx) {
				return Promise.resolve();
			}

			return ensureRunning(ctx).then(function () {
				try {
					playTone(ctx, 880, ctx.currentTime, 0.15, (volume || 0.12) * 0.6);
				} catch (e) { /* silent */ }
			});
		},
	};
})(window);
