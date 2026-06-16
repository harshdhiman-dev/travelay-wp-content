/**
 * Travelay Cultural Welcome — country-aware canvas confetti engine.
 */
(function (window) {
	'use strict';

	var activeCanvases = [];

	function rand(min, max) {
		return min + Math.random() * (max - min);
	}

	function pick(arr) {
		return arr[Math.floor(Math.random() * arr.length)];
	}

	function createParticle(config, originX, originY, burst) {
		var angle = burst ? rand(-Math.PI, Math.PI) : rand(-0.4, 0.4) - Math.PI / 2;
		var speed = burst ? rand(4, 14) : rand(1, 4);
		return {
			x: originX,
			y: originY,
			vx: Math.cos(angle) * speed + config.wind * 20,
			vy: Math.sin(angle) * speed - (burst ? rand(2, 8) : 0),
			rotation: rand(0, Math.PI * 2),
			rotationSpeed: rand(-0.12, 0.12) * config.flutter,
			size: rand(6, 14),
			color: pick(config.colors),
			shape: pick(config.shapes),
			opacity: 1,
			wobble: rand(0, Math.PI * 2),
			wobbleSpeed: rand(0.04, 0.1),
			life: burst ? rand(80, 140) : rand(120, 220),
			age: 0,
			sparkle: config.sparkle && Math.random() > 0.7,
		};
	}

	function drawShape(ctx, particle) {
		var s = particle.size;
		ctx.save();
		ctx.translate(particle.x, particle.y);
		ctx.rotate(particle.rotation);
		ctx.globalAlpha = particle.opacity;

		switch (particle.shape) {
			case 'sakura':
			case 'marigold':
			case 'lotus':
			case 'tulip':
			case 'maple':
				drawPetal(ctx, s, particle.shape);
				break;
			case 'union_cross':
				drawUnionCross(ctx, s, particle.color);
				break;
			case 'cross_swiss':
				drawSwissCross(ctx, s, particle.color);
				break;
			case 'star':
				drawStar(ctx, s, particle.color);
				break;
			case 'ribbon':
				ctx.fillStyle = particle.color;
				ctx.fillRect(-s * 0.15, -s, s * 0.3, s * 2);
				break;
			case 'stripe':
				ctx.fillStyle = particle.color;
				ctx.fillRect(-s * 0.4, -s * 0.12, s * 0.8, s * 0.24);
				break;
			case 'papel':
				drawPapel(ctx, s, particle.color);
				break;
			case 'diamond':
				ctx.fillStyle = particle.color;
				ctx.beginPath();
				ctx.moveTo(0, -s);
				ctx.lineTo(s * 0.6, 0);
				ctx.lineTo(0, s);
				ctx.lineTo(-s * 0.6, 0);
				ctx.closePath();
				ctx.fill();
				break;
			default:
				ctx.fillStyle = particle.color;
				ctx.beginPath();
				ctx.arc(0, 0, s * 0.45, 0, Math.PI * 2);
				ctx.fill();
		}

		if (particle.sparkle) {
			ctx.fillStyle = 'rgba(255,255,255,0.9)';
			ctx.beginPath();
			ctx.arc(s * 0.2, -s * 0.2, s * 0.12, 0, Math.PI * 2);
			ctx.fill();
		}

		ctx.restore();
	}

	function drawPetal(ctx, s, type) {
		var color = ctx.fillStyle;
		ctx.beginPath();
		if (type === 'maple') {
			ctx.moveTo(0, -s);
			ctx.lineTo(s * 0.5, 0);
			ctx.lineTo(0, s);
			ctx.lineTo(-s * 0.5, 0);
			ctx.closePath();
		} else if (type === 'tulip') {
			ctx.ellipse(0, 0, s * 0.35, s * 0.7, 0, 0, Math.PI * 2);
		} else {
			for (var i = 0; i < 5; i++) {
				var a = (i / 5) * Math.PI * 2 - Math.PI / 2;
				var px = Math.cos(a) * s * 0.7;
				var py = Math.sin(a) * s * 0.7;
				if (i === 0) ctx.moveTo(px, py);
				else ctx.lineTo(px, py);
			}
			ctx.closePath();
		}
		ctx.fillStyle = color;
		ctx.fill();
	}

	function drawUnionCross(ctx, s, color) {
		ctx.fillStyle = color;
		ctx.fillRect(-s * 0.45, -s * 0.12, s * 0.9, s * 0.24);
		ctx.fillRect(-s * 0.12, -s * 0.45, s * 0.24, s * 0.9);
	}

	function drawSwissCross(ctx, s, color) {
		ctx.fillStyle = color;
		ctx.fillRect(-s * 0.35, -s * 0.1, s * 0.7, s * 0.2);
		ctx.fillRect(-s * 0.1, -s * 0.35, s * 0.2, s * 0.7);
	}

	function drawStar(ctx, s, color) {
		ctx.fillStyle = color;
		ctx.beginPath();
		for (var i = 0; i < 5; i++) {
			var outer = (i / 5) * Math.PI * 2 - Math.PI / 2;
			var inner = outer + Math.PI / 5;
			ctx.lineTo(Math.cos(outer) * s * 0.5, Math.sin(outer) * s * 0.5);
			ctx.lineTo(Math.cos(inner) * s * 0.22, Math.sin(inner) * s * 0.22);
		}
		ctx.closePath();
		ctx.fill();
	}

	function drawPapel(ctx, s, color) {
		ctx.fillStyle = color;
		ctx.beginPath();
		ctx.moveTo(-s * 0.5, -s * 0.3);
		ctx.lineTo(s * 0.5, -s * 0.35);
		ctx.lineTo(s * 0.45, s * 0.35);
		ctx.lineTo(-s * 0.45, s * 0.3);
		ctx.closePath();
		ctx.fill();
		ctx.fillStyle = 'rgba(255,255,255,0.25)';
		ctx.beginPath();
		ctx.arc(0, 0, s * 0.12, 0, Math.PI * 2);
		ctx.fill();
	}

	function ConfettiCanvas(config, options) {
		this.config = config;
		this.options = options || {};
		this.particles = [];
		this.running = false;
		this.raf = null;
		this.canvas = document.createElement('canvas');
		this.canvas.className = 'tcw-confetti-canvas';
		this.canvas.setAttribute('aria-hidden', 'true');
		this.ctx = this.canvas.getContext('2d');
		this.resize();
	}

	ConfettiCanvas.prototype.mount = function (container) {
		if (!container) {
			document.body.appendChild(this.canvas);
		} else {
			container.appendChild(this.canvas);
		}
		activeCanvases.push(this);
		window.addEventListener('resize', this.onResize = this.resize.bind(this));
	};

	ConfettiCanvas.prototype.resize = function () {
		var dpr = Math.min(window.devicePixelRatio || 1, 2);
		this.canvas.width = window.innerWidth * dpr;
		this.canvas.height = window.innerHeight * dpr;
		this.canvas.style.width = window.innerWidth + 'px';
		this.canvas.style.height = window.innerHeight + 'px';
		this.ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
		this.width = window.innerWidth;
		this.height = window.innerHeight;
	};

	ConfettiCanvas.prototype.burst = function (originX, originY, count) {
		var n = count || this.config.burst_count || 80;
		for (var i = 0; i < n; i++) {
			this.particles.push(createParticle(this.config, originX, originY, true));
		}
		this.start();
	};

	ConfettiCanvas.prototype.shower = function (duration) {
		var self = this;
		var ms = duration || this.config.shower_duration || 3500;
		var interval = 120;
		var elapsed = 0;
		var timer = window.setInterval(function () {
			elapsed += interval;
			var batch = Math.ceil((self.config.shower_count || 50) / (ms / interval));
			for (var i = 0; i < batch; i++) {
				self.particles.push(createParticle(self.config, rand(0, self.width), -20, false));
			}
			self.start();
			if (elapsed >= ms) {
				window.clearInterval(timer);
			}
		}, interval);
	};

	ConfettiCanvas.prototype.puff = function (originX, originY) {
		this.burst(originX, originY, Math.floor((this.config.burst_count || 80) * 0.35));
	};

	ConfettiCanvas.prototype.start = function () {
		if (this.running) return;
		this.running = true;
		this.tick();
	};

	ConfettiCanvas.prototype.tick = function () {
		var self = this;
		var cfg = this.config;
		this.ctx.clearRect(0, 0, this.width, this.height);

		for (var i = this.particles.length - 1; i >= 0; i--) {
			var p = this.particles[i];
			p.age++;
			p.wobble += p.wobbleSpeed;
			p.x += p.vx + Math.sin(p.wobble) * cfg.flutter * 0.6;
			p.y += p.vy;
			p.vy += cfg.gravity;
			p.vx += cfg.wind;
			p.rotation += p.rotationSpeed;
			p.opacity = Math.max(0, 1 - p.age / p.life);

			if (p.age >= p.life || p.y > this.height + 40) {
				this.particles.splice(i, 1);
				continue;
			}

			this.ctx.fillStyle = p.color;
			drawShape(this.ctx, p);
		}

		if (this.particles.length > 0) {
			this.raf = window.requestAnimationFrame(function () { self.tick(); });
		} else {
			this.running = false;
			this.raf = null;
		}
	};

	ConfettiCanvas.prototype.destroy = function () {
		if (this.raf) window.cancelAnimationFrame(this.raf);
		window.removeEventListener('resize', this.onResize);
		if (this.canvas.parentNode) this.canvas.parentNode.removeChild(this.canvas);
		this.particles = [];
		this.running = false;
	};

	window.TCWConfetti = {
		create: function (config, options) {
			return new ConfettiCanvas(config, options);
		},
		destroyAll: function () {
			while (activeCanvases.length) {
				activeCanvases.pop().destroy();
			}
		},
	};
})(window);
