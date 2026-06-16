(function () {
	'use strict';

	if (!window.TCWWelcome || !window.TCWWelcome.profile) {
		return;
	}

	var config = window.TCWWelcome;
	var profile = config.profile;
	var confettiConfig = config.confetti || {};
	var experience = config.experience || {};
	var i18n = config.i18n || {};
	var root = document.getElementById('tcw-welcome-root');
	var avatarTemplate = document.getElementById('tcw-avatar-template');
	var autoTimer = null;
	var closeTimer = null;
	var typewriterTimer = null;
	var layer = null;
	var replayButton = null;
	var confettiEngine = null;
	var avatarInstance = null;
	var avatarManifest = config.avatar || {};
	var reducedMotion = profile.respectReducedMotion && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var confettiEnabled = experience.enableConfetti && !reducedMotion && window.TCWConfetti;
	var soundEnabled = experience.enableSound && window.TCWSound;
	var voiceConfig = config.voice || {};
	var voiceEnabled = experience.enableVoice && voiceConfig.enabled && voiceConfig.url && window.TCWVoice;
	var welcomeLaunched = false;
	var consentLaunchArmed = false;
	var voiceStarted = false;

	function intensityMultiplier() {
		switch (experience.confettiIntensity) {
			case 'low': return 0.45;
			case 'medium': return 0.75;
			default: return 1;
		}
	}

	function scaledConfettiConfig() {
		var mult = intensityMultiplier();
		var cfg = Object.assign({}, confettiConfig);
		cfg.burst_count = Math.round((cfg.burst_count || 80) * mult);
		cfg.shower_count = Math.round((cfg.shower_count || 50) * mult);
		cfg.shower_duration = Math.round((cfg.shower_duration || 3500) * (mult > 0.75 ? 1 : 0.85));
		return cfg;
	}

	function storageKey(suffix) {
		return 'tcw_welcome_' + profile.locationSlug + '_' + suffix;
	}

	function shouldAutoShow() {
		if (profile.trigger !== 'auto' && profile.trigger !== 'both') {
			return false;
		}
		if (profile.frequency === 'always') {
			return true;
		}
		var key = storageKey(profile.frequency);
		try {
			var stored = profile.frequency === 'session'
				? window.sessionStorage.getItem(key)
				: window.localStorage.getItem(key);
			return !stored;
		} catch (e) {
			return true;
		}
	}

	function markAutoShown() {
		try {
			var key = storageKey(profile.frequency);
			var val = String(Date.now());
			if (profile.frequency === 'session') {
				window.sessionStorage.setItem(key, val);
			} else {
				window.localStorage.setItem(key, val);
			}
		} catch (e) { /* noop */ }
	}

	function setPaletteStyles(element) {
		if (!profile.palette || !element) return;
		element.style.setProperty('--tcw-primary', profile.palette.primary || '#0f766e');
		element.style.setProperty('--tcw-secondary', profile.palette.secondary || '#ffffff');
		element.style.setProperty('--tcw-accent', profile.palette.accent || '#134e4a');
		element.style.setProperty('--tcw-z', String(profile.zIndex || 2147483000));
	}

	function getAvatarMarkup() {
		return avatarTemplate && avatarTemplate.innerHTML ? avatarTemplate.innerHTML.trim() : '';
	}

	function ensureConfetti() {
		if (!confettiEnabled) return null;
		if (!confettiEngine) {
			confettiEngine = window.TCWConfetti.create(scaledConfettiConfig());
			confettiEngine.mount(root);
		}
		return confettiEngine;
	}

	function hasNavigationActivation() {
		return !!(navigator.userActivation && navigator.userActivation.isActive);
	}

	function unlockCelebrationSound() {
		if (!soundEnabled || !window.TCWSound) return Promise.resolve();
		return window.TCWSound.unlock();
	}

	function fireCelebration(originEl) {
		var engine = ensureConfetti();
		if (!engine) return;

		var rect = originEl ? originEl.getBoundingClientRect() : null;
		var ox = rect ? rect.left + rect.width / 2 : window.innerWidth * 0.75;
		var oy = rect ? rect.top + rect.height * 0.35 : window.innerHeight * 0.65;

		engine.burst(ox, oy, scaledConfettiConfig().burst_count);
		engine.shower(scaledConfettiConfig().shower_duration);

		if (soundEnabled && window.TCWSound) {
			window.TCWSound.play(avatarManifest.soundMotif || confettiConfig.motif || 'default', experience.soundVolume);
		}
	}

	function isCookieConsentPending() {
		if (typeof window.cmplz_has_consent === 'function') {
			try {
				if (window.cmplz_has_consent('functional') || window.cmplz_has_consent('marketing')) {
					return false;
				}
			} catch (e) { /* noop */ }
		}

		if (typeof window.cmplz_get_consent === 'function') {
			try {
				if (window.cmplz_get_consent('functional') || window.cmplz_get_consent('marketing')) {
					return false;
				}
			} catch (e2) { /* noop */ }
		}

		var banners = document.querySelectorAll('.cmplz-cookiebanner, #cmplz-cookiebanner-container');
		for (var i = 0; i < banners.length; i++) {
			var banner = banners[i];
			if (banner.classList.contains('cmplz-show') || banner.offsetHeight > 0) {
				return true;
			}
		}

		return !!document.querySelector('.cmplz-accept, .cmplz-custom-accept-btn .cmplz-accept');
	}

	function isConsentAcceptTarget(target) {
		if (!target || !target.closest) return false;
		return !!target.closest(
			'.cmplz-accept, button.cmplz-accept, .cmplz-custom-accept-btn, .cmplz-accept-category, .cmplz-accept-service'
		);
	}

	function clearAutoTimer() {
		if (autoTimer) {
			window.clearTimeout(autoTimer);
			autoTimer = null;
		}
	}

	function primeVoice() {
		if (!voiceEnabled) return;
		window.TCWVoice.preload(voiceConfig.url, experience.voiceVolume);
	}

	function defaultGestureHintText() {
		return i18n.tapAvatar || 'Tap to greet again';
	}

	function updateGestureHint(hint, gestureDef) {
		if (!hint) return;

		var nextGesture = avatarInstance && avatarInstance.getNextGesture
			? avatarInstance.getNextGesture()
			: null;

		if (gestureDef && gestureDef.label && i18n.nowPlayingGesture) {
			hint.textContent = i18n.nowPlayingGesture.replace('%s', gestureDef.label);
		} else {
			hint.textContent = defaultGestureHintText();
		}

		if (gestureDef && gestureDef.key) {
			hint.setAttribute('data-current-gesture', gestureDef.key);
		}

		if (nextGesture && nextGesture.label && i18n.nextGestureHint) {
			hint.setAttribute('aria-label', i18n.nextGestureHint.replace('%s', nextGesture.label));
			hint.setAttribute('title', i18n.nextGestureHint.replace('%s', nextGesture.label));
		} else {
			hint.setAttribute('aria-label', defaultGestureHintText());
			hint.removeAttribute('title');
		}
	}

	function cycleAvatarGesture(source) {
		var stage = layer ? layer.querySelector('.tcw-avatar-stage') : null;
		var hint = stage ? stage.querySelector('.tcw-tap-hint') : null;

		if (!stage) return null;

		stage.classList.remove('tcw-avatar-pulse');
		void stage.offsetWidth;
		stage.classList.add('tcw-avatar-pulse');

		var played = null;
		if (avatarInstance && avatarInstance.advanceGesture) {
			played = avatarInstance.advanceGesture(source || 'tap');
		} else {
			replayGestureAnimation();
		}

		if (hint) {
			updateGestureHint(hint, played);
		}

		puffCelebration(stage);

		if (soundEnabled && window.TCWSound) {
			window.TCWSound.unlock().then(function () {
				window.TCWSound.playTap(experience.soundVolume);
			});
		}

		return played;
	}

	function playVoiceWelcome(stage, card) {
		if (!voiceEnabled || voiceStarted) {
			return Promise.resolve();
		}

		voiceStarted = true;

		return window.TCWVoice.play(voiceConfig.url, experience.voiceVolume).then(function () {
			return unlockCelebrationSound();
		}).catch(function () {
			voiceStarted = false;
			return Promise.resolve();
		});
	}

	function puffCelebration(originEl) {
		var engine = ensureConfetti();
		if (!engine || !originEl) return;
		var rect = originEl.getBoundingClientRect();
		engine.puff(rect.left + rect.width / 2, rect.top + rect.height / 2);
	}

	function typewriterMessage(element, text) {
		if (!element) return;
		window.clearInterval(typewriterTimer);
		if (!experience.typewriterEnabled || reducedMotion) {
			element.textContent = text;
			element.classList.add('tcw-typed-complete');
			return;
		}
		element.textContent = '';
		element.classList.remove('tcw-typed-complete');
		var i = 0;
		typewriterTimer = window.setInterval(function () {
			i++;
			element.textContent = text.slice(0, i);
			if (i >= text.length) {
				window.clearInterval(typewriterTimer);
				element.classList.add('tcw-typed-complete');
			}
		}, 22);
	}

	function bindParallax(card) {
		if (reducedMotion || window.matchMedia('(max-width: 768px)').matches) return;

		card.addEventListener('mousemove', function (e) {
			var rect = card.getBoundingClientRect();
			var px = (e.clientX - rect.left) / rect.width - 0.5;
			var py = (e.clientY - rect.top) / rect.height - 0.5;
			card.style.setProperty('--tcw-tilt-x', String(py * -4));
			card.style.setProperty('--tcw-tilt-y', String(px * 4));
		});

		card.addEventListener('mouseleave', function () {
			card.style.setProperty('--tcw-tilt-x', '0');
			card.style.setProperty('--tcw-tilt-y', '0');
		});
	}

	function bindGestureTapButton(hint, stage) {
		if (!hint || !stage) return;

		hint.addEventListener('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			cycleAvatarGesture('hint');
		});
	}

	function bindAvatarInteraction(stage) {
		if (!stage) return;

		var hint = stage.querySelector('.tcw-tap-hint');
		bindGestureTapButton(hint, stage);

		stage.addEventListener('click', function (e) {
			if (e.target.closest('.tcw-tap-hint')) return;

			if (e.target.closest('.tcw-avatar-mount, .tcw-avatar-host, .tcw-figure, .tcw-rive-canvas, .tcw-lottie-host')) {
				cycleAvatarGesture('avatar');
			}
		});

		stage.addEventListener('keydown', function (e) {
			if (e.key !== 'Enter' && e.key !== ' ') return;
			if (e.target !== hint) return;
			e.preventDefault();
			hint.click();
		});
	}

	function buildLayer() {
		if (layer) return layer;

		layer = document.createElement('div');
		layer.className = 'tcw-welcome-layer tcw-tone-' + profile.tone + ' tcw-gesture-' + profile.gesture + (reducedMotion ? ' tcw-reduced-motion' : '');
		layer.setAttribute('role', 'dialog');
		layer.setAttribute('aria-modal', 'true');
		layer.setAttribute('aria-label', i18n.welcome || 'Welcome');
		setPaletteStyles(layer);

		var backdrop = document.createElement('div');
		backdrop.className = 'tcw-welcome-backdrop';
		backdrop.addEventListener('click', hideWelcome);

		var card = document.createElement('div');
		card.className = 'tcw-welcome-card';

		var celebration = document.createElement('div');
		celebration.className = 'tcw-celebration-badge';
		celebration.innerHTML = '<span class="tcw-celebration-spark"></span><span>' + escapeHtml(i18n.celebrating || 'Celebrating your visit') + '</span>';

		var inner = document.createElement('div');
		inner.className = 'tcw-welcome-inner';
		inner.innerHTML =
			'<div class="tcw-avatar-stage" data-gesture="' + escapeAttr(profile.gesture) + '">' +
				'<div class="tcw-ambient-particles" aria-hidden="true"></div>' +
				'<div class="tcw-avatar-mount"></div>' +
				'<button type="button" class="tcw-tap-hint" data-mode="gesture">' + escapeHtml(i18n.tapAvatar || 'Tap to greet again') + '</button>' +
			'</div>' +
			'<div class="tcw-welcome-copy">' +
				'<p class="tcw-welcome-eyebrow tcw-reveal">' + escapeHtml(i18n.welcome || 'Welcome') + '</p>' +
				'<h2 class="tcw-welcome-title tcw-reveal">' + escapeHtml(profile.displayName) + '</h2>' +
				'<p class="tcw-welcome-message"></p>' +
			'</div>';

		var actions = document.createElement('div');
		actions.className = 'tcw-welcome-actions';
		actions.innerHTML = '<span class="tcw-welcome-brand">' + escapeHtml(i18n.travelay || 'Travelay') + '</span>';

		var dismiss = document.createElement('button');
		dismiss.type = 'button';
		dismiss.className = 'tcw-welcome-dismiss';
		dismiss.textContent = i18n.dismiss || 'Continue';
		dismiss.addEventListener('click', hideWelcome);
		actions.appendChild(dismiss);

		card.appendChild(celebration);
		card.appendChild(inner);
		card.appendChild(actions);
		layer.appendChild(backdrop);
		layer.appendChild(card);
		root.appendChild(layer);

		bindParallax(card);
		var stageEl = inner.querySelector('.tcw-avatar-stage');
		bindAvatarInteraction(stageEl);
		buildAmbientParticles(inner.querySelector('.tcw-ambient-particles'));
		mountAvatarEngine(stageEl.querySelector('.tcw-avatar-mount'));

		document.addEventListener('keydown', onKeydown);
		return layer;
	}

	function mountAvatarEngine(mount) {
		if (!mount || !window.TCWAvatarEngine) {
			if (mount) mount.innerHTML = getAvatarMarkup();
			return;
		}

		avatarInstance = window.TCWAvatarEngine.create(mount, avatarManifest, getAvatarMarkup());
		avatarInstance.mount().then(function () {
			var stageEl = layer ? layer.querySelector('.tcw-avatar-stage') : null;
			var hintEl = stageEl ? stageEl.querySelector('.tcw-tap-hint') : null;
			updateGestureHint(hintEl, avatarInstance.currentGesture);
		}).catch(function () {
			mount.innerHTML = getAvatarMarkup();
		});
	}

	function buildAmbientParticles(container) {
		if (!container || reducedMotion) return;
		var colors = (confettiConfig.colors || ['#fff']).slice(0, 4);
		for (var i = 0; i < 12; i++) {
			var dot = document.createElement('span');
			dot.className = 'tcw-ambient-dot';
			dot.style.setProperty('--tcw-dot-color', colors[i % colors.length]);
			dot.style.setProperty('--tcw-dot-delay', (i * 0.35) + 's');
			dot.style.setProperty('--tcw-dot-x', (10 + Math.random() * 80) + '%');
			dot.style.setProperty('--tcw-dot-duration', (3 + Math.random() * 4) + 's');
			container.appendChild(dot);
		}
	}

	function buildReplayButton() {
		if (!profile.showReplayButton || (profile.trigger !== 'manual' && profile.trigger !== 'both')) return;

		replayButton = document.createElement('button');
		replayButton.type = 'button';
		replayButton.className = 'tcw-replay-button';
		replayButton.setAttribute('aria-label', i18n.openWelcome || 'Open country welcome');
		setPaletteStyles(replayButton);
		replayButton.innerHTML =
			'<span class="tcw-replay-dot" aria-hidden="true"></span>' +
			'<span>' + escapeHtml(i18n.replay || 'Celebrate again') + '</span>';
		replayButton.addEventListener('click', function () {
			showWelcome(true);
		});
		root.appendChild(replayButton);
	}

	function launchWelcome(options) {
		if (welcomeLaunched) return;
		welcomeLaunched = true;
		clearAutoTimer();
		showWelcome(false, options || {});
	}

	function showWelcome(isManual, options) {
		options = options || {};
		var currentLayer = buildLayer();
		window.clearTimeout(closeTimer);

		var messageEl = currentLayer.querySelector('.tcw-welcome-message');
		var stageEl = currentLayer.querySelector('.tcw-avatar-stage');
		var cardEl = currentLayer.querySelector('.tcw-welcome-card');

		if (isManual) {
			voiceStarted = false;
			if (window.TCWVoice) {
				window.TCWVoice.stop();
			}
			unlockCelebrationSound();
		}

		currentLayer.classList.add('is-active');

		if (voiceEnabled && isCookieConsentPending()) {
			currentLayer.classList.add('tcw-awaiting-consent');
		} else {
			currentLayer.classList.remove('tcw-awaiting-consent');
		}

		// Play inside the user-gesture call stack (cookie accept, replay, early tap).
		if (voiceEnabled && (isManual || options.fromGesture)) {
			playVoiceWelcome(stageEl, cardEl);
		}

		window.requestAnimationFrame(function () {
			currentLayer.classList.add('is-visible');
			replayGestureAnimation();
			typewriterMessage(messageEl, profile.message);
			fireCelebration(stageEl);

			if (voiceEnabled && !voiceStarted) {
				playVoiceWelcome(stageEl, cardEl);
			}
		});

		if (!isManual) markAutoShown();
		if (!isManual && profile.autoDurationMs > 0) {
			closeTimer = window.setTimeout(hideWelcome, Math.max(profile.autoDurationMs, 7000));
		}
	}

	function playVoiceOnOpenWelcome() {
		if (!layer || voiceStarted) return;
		if (!layer.classList.contains('is-visible')) return;
		var cardEl = layer.querySelector('.tcw-welcome-card');
		var stageEl = layer.querySelector('.tcw-avatar-stage');
		playVoiceWelcome(stageEl, cardEl);
	}

	function armConsentVoiceLaunch() {
		if (!voiceEnabled || consentLaunchArmed) return;
		consentLaunchArmed = true;

		function launchFromConsent() {
			if (!welcomeLaunched) {
				launchWelcome({ fromGesture: true });
				return;
			}
			playVoiceOnOpenWelcome();
		}

		document.addEventListener('click', function (event) {
			if (!isConsentAcceptTarget(event.target)) return;
			launchFromConsent();
		}, true);

		document.addEventListener('cmplz_status_change', function () {
			if (isCookieConsentPending()) return;
			if (layer) {
				layer.classList.remove('tcw-awaiting-consent');
			}
			if (!welcomeLaunched) {
				launchWelcome({});
				return;
			}
			if (window.TCWVoice && window.TCWVoice.isBlocked()) {
				playVoiceOnOpenWelcome();
			}
		});
	}

	function armEarlyInteractionLaunch() {
		function removeListeners() {
			document.removeEventListener('pointerdown', onEarlyInteraction, true);
			document.removeEventListener('keydown', onEarlyInteraction, true);
			document.removeEventListener('touchstart', onEarlyInteraction, true);
			document.removeEventListener('scroll', onEarlyInteraction, true);
		}

		function onEarlyInteraction() {
			removeListeners();

			if (!welcomeLaunched) {
				clearAutoTimer();
				unlockCelebrationSound();
				launchWelcome({ fromGesture: true });
				return;
			}

			// Welcome already auto-launched before this gesture happened —
			// unlock audio now and replay the celebration sound so a
			// first-time visitor still hears it.
			unlockCelebrationSound().then(function () {
				if (soundEnabled && window.TCWSound) {
					window.TCWSound.play(avatarManifest.soundMotif || confettiConfig.motif || 'default', experience.soundVolume);
				}
				if (voiceEnabled && !voiceStarted && layer && layer.classList.contains('is-visible')) {
					var cardEl = layer.querySelector('.tcw-welcome-card');
					var stageEl = layer.querySelector('.tcw-avatar-stage');
					playVoiceWelcome(stageEl, cardEl);
				}
			});
		}

		if (!shouldAutoShow() && !soundEnabled) return;

		document.addEventListener('pointerdown', onEarlyInteraction, true);
		document.addEventListener('keydown', onEarlyInteraction, true);
		document.addEventListener('touchstart', onEarlyInteraction, true);
		document.addEventListener('scroll', onEarlyInteraction, true);
	}

	function scheduleAutoWelcome() {
		if (!shouldAutoShow()) return;

		primeVoice();

		var delay = profile.autoDelayMs || 1200;

		// A click/tap that navigated here (search result, menu link, etc.) grants
		// transient activation — open welcome quickly so voice can start in sync.
		if (voiceEnabled && hasNavigationActivation()) {
			delay = Math.min(delay, 80);
		}

		if (voiceEnabled && isCookieConsentPending()) {
			armConsentVoiceLaunch();
		}

		armEarlyInteractionLaunch();

		autoTimer = window.setTimeout(function () {
			launchWelcome({});
		}, delay);
	}

	function replayGestureAnimation() {
		if (!layer || reducedMotion) return;
		var figure = layer.querySelector('.tcw-figure');
		if (!figure) return;
		figure.classList.remove('tcw-animate-replay');
		void figure.offsetWidth;
		figure.classList.add('tcw-animate-replay');
	}

	function hideWelcome() {
		if (!layer) return;
		window.clearTimeout(closeTimer);
		window.clearInterval(typewriterTimer);
		voiceStarted = false;
		if (window.TCWVoice) {
			window.TCWVoice.stop();
		}
		layer.classList.remove('is-visible');
		window.setTimeout(function () {
			if (layer) layer.classList.remove('is-active');
		}, 320);
	}

	function onKeydown(event) {
		if (event.key === 'Escape' && layer && layer.classList.contains('is-visible')) {
			hideWelcome();
		}
	}

	function escapeHtml(value) {
		return String(value)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function escapeAttr(value) {
		return escapeHtml(value).replace(/`/g, '&#096;');
	}

	function mountRootToBody() {
		if (!root) return;
		setPaletteStyles(root);
		if (root.parentNode !== document.body) {
			document.body.appendChild(root);
		}
	}

	function init() {
		if (!root || !avatarTemplate) return;
		mountRootToBody();
		buildReplayButton();
		scheduleAutoWelcome();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
