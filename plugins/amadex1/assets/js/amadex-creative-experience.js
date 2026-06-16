/**
 * Amadex Creative Experience — Level 5
 * Micro-interactions, confetti, lazy reveal, number counting, surprise.
 * Scope: flight results → booking → confirmation. Desktop + mobile.
 *
 * @package Amadex
 * @version 1.0.0
 */

(function () {
  'use strict';

  var $ = typeof jQuery !== 'undefined' ? jQuery : null;
  if (!$) return;

  var settings = (typeof amadexCreativeExperienceSettings !== 'undefined') ? amadexCreativeExperienceSettings : null;

  /* --------------------------------------------------------------------------
     REDUCED MOTION
     -------------------------------------------------------------------------- */
  function prefersReducedMotion() {
    if (settings && settings.accessibility) {
      if (settings.accessibility.force_disable_animations) return true;
      if (settings.accessibility.respect_reduced_motion) {
        try {
          return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        } catch (e) {}
      }
    } else {
      try {
        return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      } catch (e) {
        return false;
      }
    }
    return false;
  }

  /* --------------------------------------------------------------------------
     DEBOUNCE / THROTTLE
     -------------------------------------------------------------------------- */
  function debounce(fn, wait) {
    var t;
    return function () {
      var args = arguments;
      clearTimeout(t);
      t = setTimeout(function () { fn.apply(null, args); }, wait);
    };
  }

  function throttle(fn, limit) {
    var inThrottle;
    return function () {
      var args = arguments;
      if (inThrottle) return;
      inThrottle = true;
      fn.apply(null, args);
      setTimeout(function () { inThrottle = false; }, limit);
    };
  }

  /* --------------------------------------------------------------------------
     RIPPLE — Button micro-interaction
     -------------------------------------------------------------------------- */
  function initRipple() {
    if (prefersReducedMotion()) return;

    $(document).off('click.amadexCe', '.amadex-ce-ripple-target').on('click.amadexCe', '.amadex-ce-ripple-target', function (e) {
      var btn = this;
      var $btn = $(btn);
      if ($btn.prop('disabled')) return;

      var rect = btn.getBoundingClientRect();
      var x = (e.clientX || 0) - rect.left;
      var y = (e.clientY || 0) - rect.top;
      var size = Math.max(rect.width, rect.height, 48);
      var $ripple = $('<span class="amadex-ce-ripple"></span>').css({
        width: size,
        height: size,
        left: x - size / 2,
        top: y - size / 2,
      });

      if ($btn.css('position') === 'static') $btn.css('position', 'relative');
      $btn.append($ripple);
      $ripple[0].offsetHeight;
      $ripple.addClass('amadex-ce-ripple-active');

      setTimeout(function () {
        $ripple.remove();
      }, 500);
    });
  }

  /* --------------------------------------------------------------------------
     LAZY REVEAL — Intersection Observer
     -------------------------------------------------------------------------- */
  function initLazyReveal() {
    var els = document.querySelectorAll('.amadex-ce-reveal');
    if (settings && settings.animations && settings.animations.lazy_reveal_enabled === false) return;

    if (!els.length) return;

    if (typeof IntersectionObserver === 'undefined') {
      els.forEach(function (el) {
        el.classList.add('amadex-ce-visible');
      });
      return;
    }

    var observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('amadex-ce-visible');
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.08, rootMargin: '0px 0px -40px 0px' }
    );

    els.forEach(function (el) {
      observer.observe(el);
    });
  }

  /* --------------------------------------------------------------------------
     NUMBER COUNTING
     -------------------------------------------------------------------------- */
  function animateValue(el, start, end, duration, formatter) {
    if (prefersReducedMotion()) {
      if (el && el.textContent !== undefined) el.textContent = formatter ? formatter(end) : String(end);
      return;
    }

    var startTime = null;
    formatter = formatter || function (n) { return String(Math.round(n)); };

    function step(timestamp) {
      if (!startTime) startTime = timestamp;
      var elapsed = timestamp - startTime;
      var progress = Math.min(elapsed / duration, 1);
      var easeOut = 1 - Math.pow(1 - progress, 3);
      var current = start + (end - start) * easeOut;

      if (el && el.textContent !== undefined) el.textContent = formatter(current);

      if (progress < 1) requestAnimationFrame(step);
    }

    requestAnimationFrame(step);
  }

  function initNumberCounters() {
    document.querySelectorAll('[data-amadex-ce-count]').forEach(function (el) {
      var raw = el.getAttribute('data-amadex-ce-count');
      var num = parseFloat(raw, 10);
      if (isNaN(num)) return;

      var duration = parseInt(el.getAttribute('data-amadex-ce-duration'), 10) || 1200;
      var prefix = el.getAttribute('data-amadex-ce-prefix') || '';
      var suffix = el.getAttribute('data-amadex-ce-suffix') || '';
      var decimals = parseInt(el.getAttribute('data-amadex-ce-decimals'), 10) || 0;

      var formatter = function (n) {
        var s;
        if (decimals) {
          try {
            s = n.toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
          } catch (e) {
            s = n.toFixed(decimals);
          }
        } else {
          s = String(Math.round(n));
        }
        return prefix + s + suffix;
      };

      var io = typeof IntersectionObserver !== 'undefined'
        ? new IntersectionObserver(
            function (entries) {
              entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                  animateValue(el, 0, num, duration, formatter);
                  io.unobserve(el);
                }
              });
            },
            { threshold: 0.2 }
          )
        : null;

      if (io) io.observe(el);
      else animateValue(el, 0, num, duration, formatter);
    });
  }

  /* --------------------------------------------------------------------------
     CONFETTI — Canvas particles
     -------------------------------------------------------------------------- */
  function runConfetti(opts) {
    if (prefersReducedMotion()) return;

    opts = opts || {};
    var runMs = opts.duration != null ? opts.duration : 3500;
    var count = opts.count != null ? opts.count : 120;
    var colors = opts.colors || ['#0e7d3f', '#1a9d5f', '#fff', '#ffd700', '#87ceeb'];

    var canvas = document.createElement('canvas');
    canvas.id = 'amadex-ce-confetti-canvas';
    document.body.appendChild(canvas);

    var ctx = canvas.getContext('2d');
    if (!ctx) {
      canvas.remove();
      return;
    }

    var w = (canvas.width = window.innerWidth);
    var h = (canvas.height = window.innerHeight);
    var particles = [];
    var startTime = null;

    function Particle() {
      this.x = Math.random() * w;
      this.y = -20;
      this.vx = (Math.random() - 0.5) * 6;
      this.vy = Math.random() * 4 + 4;
      this.g = 0.2;
      this.color = colors[Math.floor(Math.random() * colors.length)];
      this.size = Math.random() * 6 + 4;
      this.rotation = Math.random() * 360;
      this.rotSpeed = (Math.random() - 0.5) * 12;
    }

    for (var i = 0; i < count; i++) particles.push(new Particle());

    function cleanup() {
      try {
        canvas.remove();
      } catch (e) {}
    }

    function loop(now) {
      if (!startTime) startTime = now;
      var elapsed = now - startTime;
      if (elapsed >= runMs) {
        cleanup();
        return;
      }

      ctx.clearRect(0, 0, w, h);
      for (var j = 0; j < particles.length; j++) {
        var p = particles[j];
        p.vy += p.g;
        p.x += p.vx;
        p.y += p.vy;
        p.rotation += p.rotSpeed;
        ctx.save();
        ctx.translate(p.x, p.y);
        ctx.rotate((p.rotation * Math.PI) / 180);
        ctx.fillStyle = p.color;
        ctx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size);
        ctx.restore();
      }
      requestAnimationFrame(loop);
    }

    requestAnimationFrame(loop);
  }

  /* --------------------------------------------------------------------------
     CONFIRMATION — Confetti + Surprise badge
     -------------------------------------------------------------------------- */
  function initConfirmationCelebration() {
    var isConfirmation =
      document.querySelector('.amadex-confirmation-page') ||
      document.querySelector('.amadex-confirmation-greeting') ||
      (window.location.href || '').indexOf('booking-confirmation') !== -1 ||
      (window.location.search || '').indexOf('reference=') !== -1;

    if (!isConfirmation) return;

    if (!prefersReducedMotion()) {
    var celeb = (settings && settings.celebration) ? settings.celebration : {};
    var textOpts = (settings && settings.text) ? settings.text : {};
    var badgeText = textOpts.confirmation_badge || 'Adventure Awaits ✈️';
    var psText = textOpts.confirmation_ps || "P.S. You're going to love this trip.";
    var confettiEnabled = celeb.confetti_enabled !== false;
    var surpriseBadgeEnabled = celeb.surprise_badge_enabled !== false;
    var surprisePsEnabled = celeb.surprise_ps_enabled !== false;
    var badgeDelay = parseInt(celeb.surprise_badge_delay, 10) || 800;
    var psDelay = parseInt(celeb.surprise_ps_delay, 10) || 1200;

    if (confettiEnabled && !prefersReducedMotion()) {
      var duration = parseInt(celeb.confetti_duration, 10) || 4000;
      var count = parseInt(celeb.confetti_count, 10) || 100;
      var colors = (celeb.confetti_colors && Array.isArray(celeb.confetti_colors)) ? celeb.confetti_colors : ['#0e7d3f', '#1a9d5f', '#fff', '#ffd700', '#87ceeb'];
      setTimeout(function () {
        runConfetti({ duration: duration, count: count, colors: colors });
      }, 400);
    }

    /* Surprise badge */
    function escHtml(s) {
      if (typeof s !== 'string') return '';
      var div = document.createElement('div');
      div.textContent = s;
      return div.innerHTML;
    }
    var badgeHtml =
      '<div class="amadex-ce-surprise-wrap" role="status" aria-live="polite">' +
      '<div class="amadex-ce-surprise-badge">' +
      '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
      '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>' +
      '<polyline points="3.27 6.96 12 12.01 20.73 6.96"/>' +
      '<line x1="12" y1="22.08" x2="12" y2="12"/>' +
      '</svg>' +
      escHtml(badgeText) +
      '</div>' +
      (surprisePsEnabled ? '<p class="amadex-ce-surprise-ps">' + escHtml(psText) + '</p>' : '') +
      '</div>';

    var $container = $('.amadex-confirmation-main').first();
    if (!$container.length) $container = $('.amadex-confirmation-greeting').first();
    if (!$container.length) $container = $('.amadex-confirmation-content').first();
    if (!$container.length) $container = $('.amadex-confirmation-page').first();

    if ($container.length && surpriseBadgeEnabled) {
      setTimeout(function () {
        var $wrap = $(badgeHtml);
        var $greeting = $('.amadex-confirmation-greeting').first();
        if ($greeting.length) {
          $greeting.after($wrap);
        } else {
          $container.prepend($wrap);
        }
      }, badgeDelay);
    }
  }
  }

  /* --------------------------------------------------------------------------
     RIPPLE TARGETS — Add class to CTAs
     -------------------------------------------------------------------------- */
  function markRippleTargets() {
    $('#amadex-confirm-book, #amadex-confirm-book-pagination, #amadex-step-next, .amadex-step-next, .amadex-btn-primary, .amadex-print-booking, .amadex-pagination-confirm').addClass('amadex-ce-ripple-target');
  }

  /* --------------------------------------------------------------------------
     PROGRESS FILL — Optional progress bar
     -------------------------------------------------------------------------- */
  function initProgressFill() {
    var $bar = $('.amadex-ce-progress-fill');
    if (!$bar.length) return;

    var pct = parseFloat($bar.attr('data-progress'), 10);
    if (isNaN(pct)) pct = 100;
    pct = Math.max(0, Math.min(100, pct));

    $bar.css('transform', 'scaleX(' + pct / 100 + ')');
  }

  /* --------------------------------------------------------------------------
     REVEAL TARGETS — Add .amadex-ce-reveal to cards/sections
     -------------------------------------------------------------------------- */
  function markRevealTargets() {
    if (settings && settings.animations && settings.animations.lazy_reveal_enabled === false) return;
    $('.amadex-card, .amadex-flight-detail-card')
      .not('.amadex-ce-reveal')
      .addClass('amadex-ce-reveal');
  }

  /* --------------------------------------------------------------------------
     INIT
     -------------------------------------------------------------------------- */
  function init() {
    markRippleTargets();
    markRevealTargets();
    initRipple();
    initLazyReveal();
    initNumberCounters();
    initProgressFill();
    initConfirmationCelebration();

    $(window).on('resize.amadexCe', debounce(function () {
      initLazyReveal();
    }, 200));
  }

  $(document).ready(init);

  /* --------------------------------------------------------------------------
     GLOBAL API (for surprise / external use)
     -------------------------------------------------------------------------- */
  window.AmadexCreativeExperience = {
    runConfetti: runConfetti,
    animateValue: animateValue,
    prefersReducedMotion: prefersReducedMotion,
  };
})();
