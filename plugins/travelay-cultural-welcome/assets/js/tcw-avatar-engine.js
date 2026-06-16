/**
 * Avatar engine: Rive → Premium SVG (auto) or Lottie (explicit) with tap gesture cycling.
 */
(function (window) {
	'use strict';

	var DEFAULT_STATE_MACHINE = 'State Machine 1';
	var riveScriptLoaded = false;
	var lottieScriptLoaded = false;

	function loadScript(src) {
		return new Promise(function (resolve, reject) {
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

	function ensureRive() {
		if (window.rive) return Promise.resolve();
		if (riveScriptLoaded) return Promise.resolve();
		riveScriptLoaded = true;
		return loadScript('https://unpkg.com/@rive-app/canvas@2.23.4/rive.js');
	}

	function ensureLottie() {
		if (window.lottie) return Promise.resolve();
		if (lottieScriptLoaded) return Promise.resolve();
		lottieScriptLoaded = true;
		return loadScript('https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js');
	}

	function AvatarInstance(container, manifest, svgMarkup) {
		this.container = container;
		this.manifest = manifest;
		this.svgMarkup = svgMarkup;
		this.mode = 'svg';
		this.stateIndex = 0;
		this.gestureIndex = 0;
		this.currentGesture = null;
		this.rive = null;
		this.lottie = null;
		this.host = null;
		this.riveHoverBound = false;
		this.riveConfig = manifest.riveConfig || null;
	}

	AvatarInstance.prototype.getStateMachineName = function () {
		if (this.riveConfig && this.riveConfig.stateMachine) {
			return this.riveConfig.stateMachine;
		}
		return DEFAULT_STATE_MACHINE;
	};

	AvatarInstance.prototype.getTapGestures = function () {
		if (this.manifest.tapGestures && this.manifest.tapGestures.length) {
			return this.manifest.tapGestures;
		}
		return (this.manifest.states || ['idle', 'wave', 'smile']).filter(function (state) {
			return state !== 'idle' && state !== 'smile';
		}).map(function (state) {
			return {
				key: state,
				label: state,
				riveTrigger: state,
				svgState: state,
			};
		});
	};

	AvatarInstance.prototype.dispatchGestureEvent = function (gesture, source) {
		if (!this.host || !gesture) return;
		var detail = {
			gesture: gesture,
			index: this.gestureIndex,
			source: source || 'tap',
			mode: this.mode,
		};
		try {
			this.host.dispatchEvent(new CustomEvent('tcw:avatar-gesture', { bubbles: true, detail: detail }));
		} catch (e) { /* noop */ }
	};

	AvatarInstance.prototype.mount = function () {
		var self = this;
		this.host = document.createElement('div');
		this.host.className = 'tcw-avatar-host';
		this.container.appendChild(this.host);

		var renderer = this.manifest.renderer || 'auto';

		if (renderer === 'svg') {
			return Promise.resolve(this.mountSvg());
		}

		if ((renderer === 'auto' || renderer === 'rive') && this.manifest.enableRive && this.manifest.hasRive) {
			return this.mountRive().catch(function () {
				return self.mountSvg();
			});
		}

		if (renderer === 'lottie' && this.manifest.enableLottie) {
			return this.mountLottie().catch(function () {
				return self.mountSvg();
			});
		}

		return Promise.resolve(this.mountSvg());
	};

	AvatarInstance.prototype.mountRive = function () {
		var self = this;
		var stateMachine = this.getStateMachineName();

		return ensureRive().then(function () {
			self.host.innerHTML = '<canvas class="tcw-rive-canvas" width="200" height="260"></canvas>';
			var canvas = self.host.querySelector('canvas');
			self.rive = new window.rive.Rive({
				src: self.manifest.riveUrl,
				canvas: canvas,
				autoplay: true,
				stateMachines: stateMachine,
				onLoad: function () {
					self.mode = 'rive';
					self.rive.resizeDrawingSurfaceToCanvas();
					self.bindRiveHover();
					self.playEntryGesture();
				},
			});
		});
	};

	AvatarInstance.prototype.bindRiveHover = function () {
		if (this.riveHoverBound || !this.host || !this.riveConfig || !this.riveConfig.hoverInput) {
			return;
		}

		var inputName = this.riveConfig.hoverInput;
		var self = this;

		var onEnter = function () {
			self.setRiveBoolean(inputName, true);
		};
		var onLeave = function () {
			self.setRiveBoolean(inputName, false);
		};

		this.host.addEventListener('pointerenter', onEnter);
		this.host.addEventListener('pointerleave', onLeave);
		this.riveHoverBound = true;
	};

	AvatarInstance.prototype.setRiveBoolean = function (inputName, value) {
		if (this.mode !== 'rive' || !this.rive || !inputName) return false;

		var stateMachine = this.getStateMachineName();
		var set = false;

		try {
			(this.rive.stateMachineInputs(stateMachine) || []).forEach(function (input) {
				if (set || input.name !== inputName) return;
				input.value = !!value;
				set = true;
			});
		} catch (e) { /* noop */ }

		return set;
	};

	AvatarInstance.prototype.parseLottiePayload = function (text) {
		if (!text) throw new Error('Empty Lottie response');
		var trimmed = String(text).trim();
		if (trimmed.charAt(0) === '{') {
			return JSON.parse(trimmed);
		}
		var start = trimmed.indexOf('{');
		if (start < 0) throw new Error('Invalid Lottie JSON');
		return JSON.parse(trimmed.slice(start));
	};

	AvatarInstance.prototype.loadLottieData = function (url) {
		return fetch(url, { credentials: 'same-origin', cache: 'default' }).then(function (response) {
			if (!response.ok) {
				throw new Error('Lottie HTTP ' + response.status);
			}
			return response.text();
		}).then(this.parseLottiePayload.bind(this));
	};

	AvatarInstance.prototype.mountLottie = function () {
		var self = this;
		return ensureLottie().then(function () {
			self.host.innerHTML = '<div class="tcw-lottie-host"></div>';
			var el = self.host.querySelector('.tcw-lottie-host');
			return self.loadLottieData(self.manifest.lottieUrl).then(function (animationData) {
				self.lottie = window.lottie.loadAnimation({
					container: el,
					renderer: 'svg',
					loop: true,
					autoplay: true,
					animationData: animationData,
					rendererSettings: {
						preserveAspectRatio: 'xMidYMid meet',
					},
				});
				self.mode = 'lottie';
				self.playEntryGesture();
			});
		});
	};

	AvatarInstance.prototype.mountSvg = function () {
		this.host.innerHTML = this.svgMarkup;
		this.host.classList.add('tcw-avatar-svg-host');
		this.mode = 'svg';
		this.playEntryGesture();
		return this;
	};

	AvatarInstance.prototype.fireRiveTrigger = function (triggerName) {
		if (this.mode !== 'rive' || !this.rive || !triggerName) return false;

		var stateMachine = this.getStateMachineName();
		var fired = false;

		try {
			(this.rive.stateMachineInputs(stateMachine) || []).forEach(function (input) {
				if (!fired && input.name === triggerName && typeof input.fire === 'function') {
					input.fire();
					fired = true;
				}
			});
		} catch (e) { /* noop */ }

		if (!fired) {
			var fallbacks = [triggerName, 'tap', 'wave', 'smile'];
			var self = this;
			fallbacks.some(function (name) {
				try {
					(self.rive.stateMachineInputs(stateMachine) || []).forEach(function (input) {
						if (!fired && input.name === name && typeof input.fire === 'function') {
							input.fire();
							fired = true;
						}
					});
				} catch (e) { /* noop */ }
				return fired;
			});
		}

		return fired;
	};

	AvatarInstance.prototype.applyState = function (state) {
		if (this.mode !== 'svg' || !this.host || !state) return;
		var figure = this.host.querySelector('.tcw-figure');
		if (!figure) return;

		figure.classList.remove(
			'tcw-state-idle', 'tcw-state-wave', 'tcw-state-bow', 'tcw-state-namaste',
			'tcw-state-heart', 'tcw-state-welcome', 'tcw-state-nod', 'tcw-state-smile'
		);
		figure.classList.add('tcw-state-' + state);
		figure.classList.remove('tcw-animate-replay');
		void figure.offsetWidth;
		figure.classList.add('tcw-animate-replay');
	};

	AvatarInstance.prototype.playGesture = function (gestureDef, source) {
		if (!gestureDef) return null;

		var svgState = gestureDef.svgState || gestureDef.key;
		var riveTrigger = gestureDef.riveTrigger || svgState;

		if (this.mode === 'rive') {
			this.fireRiveTrigger(riveTrigger);
		}

		if (this.mode === 'lottie' && this.lottie) {
			this.lottie.goToAndPlay(0, true);
		}

		this.applyState(svgState);
		this.currentGesture = gestureDef;
		this.dispatchGestureEvent(gestureDef, source);
		return gestureDef;
	};

	AvatarInstance.prototype.playEntryGesture = function () {
		if (this.mode === 'rive' && this.riveConfig && this.riveConfig.entryTrigger) {
			this.fireRiveTrigger(this.riveConfig.entryTrigger);
		}

		var gestures = this.getTapGestures();
		if (!gestures.length) {
			this.applyState('idle');
			return null;
		}

		this.gestureIndex = 0;
		return this.playGesture(gestures[0], 'entry');
	};

	AvatarInstance.prototype.getNextGesture = function () {
		var gestures = this.getTapGestures();
		if (!gestures.length) return null;
		var nextIndex = (this.gestureIndex + 1) % gestures.length;
		return gestures[nextIndex];
	};

	AvatarInstance.prototype.advanceGesture = function (source) {
		var gestures = this.getTapGestures();
		if (!gestures.length) {
			return this.advanceState();
		}

		this.gestureIndex = (this.gestureIndex + 1) % gestures.length;
		return this.playGesture(gestures[this.gestureIndex], source || 'tap');
	};

	AvatarInstance.prototype.advanceState = function () {
		var states = this.manifest.states || ['idle', 'wave', 'smile', 'idle'];
		this.stateIndex = (this.stateIndex + 1) % states.length;
		var state = states[this.stateIndex];
		return this.playGesture({
			key: state,
			label: state,
			riveTrigger: state,
			svgState: state,
		}, 'legacy');
	};

	AvatarInstance.prototype.destroy = function () {
		if (this.lottie) this.lottie.destroy();
		if (this.rive) this.rive.cleanup();
		if (this.host && this.host.parentNode) this.host.parentNode.removeChild(this.host);
	};

	window.TCWAvatarEngine = {
		create: function (container, manifest, svgMarkup) {
			return new AvatarInstance(container, manifest, svgMarkup);
		},
	};
})(window);
