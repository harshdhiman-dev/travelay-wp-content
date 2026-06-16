/**
 * Amadex Step Elements — Level 5
 * Visible creative elements per step: heroes, badges, teasers, strips.
 * Search → Results → Booking (5 steps) → Payment → Confirmation.
 *
 * @package Amadex
 * @version 1.0.0
 */

(function () {
  'use strict';

  var $ = typeof jQuery !== 'undefined' ? jQuery : null;
  if (!$) return;

  var settings = (typeof amadexCreativeExperienceSettings !== 'undefined') ? amadexCreativeExperienceSettings : null;

  function prefersReducedMotion() {
    try {
      return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    } catch (e) {
      return false;
    }
  }

  function debounce(fn, wait) {
    var t;
    return function () {
      var a = arguments;
      clearTimeout(t);
      t = setTimeout(function () { fn.apply(null, a); }, wait);
    };
  }

  var icons = {
    plane: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2l-1.8-8.2L16 11l8.2 1.8c.5.1.9.1 1.2.3z"/></svg>',
    user: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
    seat: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="6" width="16" height="12" rx="2"/><line x1="4" y1="12" x2="20" y2="12"/></svg>',
    gift: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 12v10H4V12"/><rect x="2" y="7" width="20" height="5"/><path d="M12 22V7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>',
    shield: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
    lock: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
    check: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>'
  };

  function injectOnce(container, selector, fn) {
    if (!container || !container.length) return;
    if (container.find(selector).length) return;
    var el = fn();
    if (el) container.prepend(el);
  }

  function injectOnceAppend(container, selector, fn) {
    if (!container || !container.length) return;
    if (container.find(selector).length) return;
    var el = fn();
    if (el) container.append(el);
  }

  /* ---------- Search ---------- */
  function initSearch() {
    var $form = $('#amadex-modern-form, #amadex-modern-form-results').first();
    var $wrap = $form.closest('.amadex-search-modern');
    if (!$wrap.length) $wrap = $form.parent();
    if (!$form.length || !$wrap.length) return;
    var isResultsPage = $('#amadex-results-page').length > 0;
    var t = (settings && settings.text) ? settings.text : {};
    var se = (settings && settings.step_elements) ? settings.step_elements : {};
    var searchHero = t.search_hero || 'Your next trip starts here';
    var popularLabel = t.search_popular_label || 'Popular:';
    var popularEnabled = se.popular_chips_enabled !== false;
    var chips = (t.popular_routes && Array.isArray(t.popular_routes)) ? t.popular_routes : [
      { from: 'New York', to: 'Miami', origin: 'JFK', dest: 'MIA' },
      { from: 'Los Angeles', to: 'Las Vegas', origin: 'LAX', dest: 'LAS' },
      { from: 'Chicago', to: 'Orlando', origin: 'ORD', dest: 'MCO' }
    ];

    if (!isResultsPage) {
      injectOnce($wrap, '.amadex-se-hero', function () {
        return $('<div class="amadex-se-hero" role="heading" aria-level="2">' + icons.plane + ' ' + searchHero + '</div>');
      });
    }

    var $hero = $wrap.find('.amadex-se-hero');
    if (!$hero.length) $hero = $wrap.find('.amadex-search-summary-modern, .amadex-search-container').first();
    if (!isResultsPage && $hero.length && !$wrap.find('.amadex-se-popular').length && popularEnabled) {
      var html = '<div class="amadex-se-popular"><span>' + popularLabel + '</span>';
      chips.forEach(function (c) {
        html += '<button type="button" class="amadex-se-popular-chip" data-route="' + (c.origin || '') + '-' + (c.dest || '') + '">' + (c.from || '') + ' → ' + (c.to || '') + '</button>';
      });
      html += '</div>';
      $hero.after($(html));
    }

    $wrap.find('.amadex-se-popular-chip').off('click.amadexSe').on('click.amadexSe', function () {
      var route = ($(this).data('route') || '').split('-');
      if (route.length >= 2) {
        $('#modern-origin').val(route[0]);
        $('#modern-origin-code').val(route[0]);
        $('#origin-description').text(route[0]);
        $('#modern-destination').val(route[1]);
        $('#modern-destination-code').val(route[1]);
        $('#destination-description').text(route[1]);
      }
    });

    var findingText = t.search_finding_strip || 'Finding your flights…';
    $form.off('submit.amadexSe').on('submit.amadexSe', function () {
      var $strip = $wrap.find('.amadex-se-finding');
      if (!$strip.length) {
        $strip = $('<div class="amadex-se-finding" style="display:none"><span class="amadex-se-finding-plane">' + icons.plane + '</span> ' + findingText + '</div>');
        $form.after($strip);
      }
      $strip.show();
    });
  }

  /* ---------- Results ---------- */
  function animateCount(el, end, prefix, suffix) {
    if (!el || !el.length) return;
    var current = parseInt(String(el.text()).replace(/\D/g, ''), 10) || 0;
    if (current === end) return;
    var start = current;
    var startTime = null;
    function step(now) {
      if (!startTime) startTime = now;
      var t = Math.min((now - startTime) / 800, 1);
      var v = Math.round(start + (end - start) * (1 - Math.pow(1 - t, 3)));
      el.text((prefix || '') + v + (suffix || ''));
      if (t < 1) requestAnimationFrame(step);
    }
    if (!prefersReducedMotion()) requestAnimationFrame(step);
    else el.text((prefix || '') + end + (suffix || ''));
  }

  function initResults() {
    var $page = $('#amadex-results-page');
    if (!$page.length) return;

    var t = (settings && settings.text) ? settings.text : {};
    var se = (settings && settings.step_elements) ? settings.step_elements : {};
    var resultsHero = t.results_hero || 'We found your flights';
    var selectStrip = t.results_select_strip || "Pick a flight to continue — you're one step away from booking.";

    var $heroWrap = $page.find('.amadex-se-results-hero-wrap');
    if (!$heroWrap.length && se.results_hero_enabled !== false) {
      var $insert = $('#amadex-search-summary').length ? $('#amadex-search-summary') : $('.amadex-search-bar-wrapper').first();
      if ($insert.length) {
        $insert.after('<div class="amadex-se-results-hero-wrap" style="margin-bottom:0.5rem;"><div class="amadex-se-results-hero">' + resultsHero + '</div></div>');
      }
    }

    if (!$page.find('.amadex-se-select-strip').length && se.results_select_strip_enabled !== false) {
      var $cards = $('#amadex-flight-cards-container');
      if ($cards.length) {
        var $strip = $('<div class="amadex-se-strip amadex-se-select-strip">' + icons.plane + ' ' + selectStrip + '</div>');
        $cards.before($strip);
      }
    }

    var $count = $('#amadex-results-count');
    var $mobileCount = $('#amadex-mobile-results-count-display');
    var lastCount = 0;
    function tryAnimateCount() {
      var raw = $count.length ? $count.text() : '';
      var n = parseInt(String(raw).replace(/\D/g, ''), 10);
      if (isNaN(n) || n === lastCount) return;
      lastCount = n;
      animateCount($count, n, '', '');
      if ($mobileCount.length) {
        if ($mobileCount.is('strong')) animateCount($mobileCount, n, '', '');
        else {
          var $strong = $mobileCount.find('strong');
          if ($strong.length) animateCount($strong, n, '', '');
        }
      }
    }

    if (typeof MutationObserver !== 'undefined' && $count.length) {
      var ob = new MutationObserver(function () { tryAnimateCount(); });
      ob.observe($count[0], { characterData: true, childList: true, subtree: true });
    }
    tryAnimateCount();

    $(document).on('click.amadexSe', '.amadex-book-now-btn', function () {
      if (prefersReducedMotion()) return;
      if (settings && settings.animations && settings.animations.book_now_dots_enabled === false) return;
      var $btn = $(this);
      if (!$btn.css('position') || $btn.css('position') === 'static') $btn.css('position', 'relative');
      [0, 72, 144].forEach(function (i) {
        var rad = (i * Math.PI) / 180;
        var dx = 20 * Math.cos(rad);
        var dy = 20 * Math.sin(rad);
        var $d = $('<span class="amadex-se-book-dot" style="position:absolute;left:50%;top:50%;margin-left:-3px;margin-top:-3px;--dx:' + dx + 'px;--dy:' + dy + 'px;"></span>');
        $btn.append($d);
        setTimeout(function () { $d.remove(); }, 500);
      });
    });
  }

  /* ---------- Booking steps ---------- */
  function getStepConfig() {
    var t = (settings && settings.text && settings.text.steps) ? settings.text.steps : {};
    var def = {
      flights: { hero: 'Your flight', icon: 'plane', badge: 'Looking good', badgeDelay: 1500, teaser: 'Next: Passenger details', teaserIcon: 'user', route: true },
      passengers: { hero: "Who's flying?", icon: 'user', teaser: 'Next: Pick your seats', teaserIcon: 'seat' },
      seats: { hero: 'Window or aisle?', icon: 'seat', skipOk: "Skip — I'll take any seat", teaser: 'Next: Add-ons', teaserIcon: 'gift' },
      addons: { hero: 'Little extras for your trip', icon: 'gift', teaser: 'Next: Review & pay', teaserIcon: 'shield', optional_label: 'Optional' },
      review: { hero: 'Final check', icon: 'shield', protected: "You're protected", noTeaser: true }
    };
    var cfg = {};
    for (var step in def) {
      cfg[step] = {};
      for (var k in def[step]) {
        cfg[step][k] = (t[step] && t[step][k] !== undefined && t[step][k] !== '') ? t[step][k] : def[step][k];
      }
    }
    return cfg;
  }
  var stepConfig = getStepConfig();

  function sectionForStep(step) {
    var map = {
      flights: '#amadex-section-flights',
      passengers: '#amadex-section-passengers',
      seats: '#amadex-seat-selection-section',
      addons: '#amadex-addons-section',
      review: '#amadex-review-section'
    };
    return $(map[step] || '');
  }

  function enhanceBookingStep(stepName) {
    stepConfig = getStepConfig();
    var cfg = stepConfig[stepName];
    if (!cfg) return;
    var se = (settings && settings.step_elements) ? settings.step_elements : {};
    if (stepName === 'flights' && se.flights_hero === false) return;
    if (stepName === 'passengers' && se.passengers_hero === false) return;
    if (stepName === 'seats' && se.seats_hero === false) return;
    if (stepName === 'addons' && se.addons_hero === false) return;
    if (stepName === 'review' && se.review_hero === false) return;
    var $sec = sectionForStep(stepName);
    if (!$sec.length) return;
    if ($sec.data('amadex-se-enhanced')) return;

    var $inner = $sec.find('h3').first().parent();
    if (!$inner.length) $inner = $sec;

    var $hero = $('<div class="amadex-se-hero">' + (icons[cfg.icon] || '') + cfg.hero + '</div>');
    $sec.find('h3').first().before($hero);

    if (cfg.route && stepName === 'flights' && se.flights_route !== false) {
      var $itinerary = $('#amadex-booking-itinerary');
      if ($itinerary.length) {
        var $route = $('<div class="amadex-se-route-mini"><span class="amadex-se-route-origin">—</span><span class="amadex-se-route-dash"></span><span class="amadex-se-route-plane">' + icons.plane + '</span><span class="amadex-se-route-dash"></span><span class="amadex-se-route-dest">—</span></div>');
        $hero.after($route);
        var flightData = null;
        try {
          var raw = window.sessionStorage && sessionStorage.getItem('amadex_booking_flight');
          if (raw) flightData = JSON.parse(raw);
        } catch (e) {}
        var origin = '—';
        var dest = '—';
        if (flightData) {
          if (flightData.departure && flightData.arrival) {
            origin = flightData.departure.iataCode || flightData.departure.iata_code || flightData.departure.code || '—';
            dest = flightData.arrival.iataCode || flightData.arrival.iata_code || flightData.arrival.code || '—';
          } else if (flightData.itineraries && flightData.itineraries[0] && flightData.itineraries[0].segments && flightData.itineraries[0].segments.length) {
            var segs = flightData.itineraries[0].segments;
            var first = segs[0];
            var last = segs[segs.length - 1];
            origin = (first.departure && (first.departure.iataCode || first.departure.iata_code || first.departure.code)) || '—';
            dest = (last.arrival && (last.arrival.iataCode || last.arrival.iata_code || last.arrival.code)) || '—';
          }
        }
        $route.find('.amadex-se-route-origin').text(origin);
        $route.find('.amadex-se-route-dest').text(dest);
      }
    }

    if (cfg.badge && cfg.badgeDelay) {
      var $anchor = $sec.find('.amadex-se-route-mini').length ? $sec.find('.amadex-se-route-mini') : $hero;
      setTimeout(function () {
        if (!$sec.hasClass('step-active')) return;
        if ($sec.find('.amadex-se-badge').length) return;
        var $b = $('<div class="amadex-se-badge">' + icons.check + cfg.badge + '</div>');
        $anchor.after($b);
      }, cfg.badgeDelay);
    }

    if (cfg.skipOk && stepName === 'seats' && se.seats_skip_ok !== false) {
      var $skip = $('#amadex-skip-seat-selection');
      if ($skip.length) {
        var $p = $('<p class="amadex-se-skip-ok">' + cfg.skipOk + '</p>');
        $skip.after($p);
      }
    }

    if (cfg.protected && stepName === 'review' && se.review_protected !== false) {
      var $pay = $('#amadex-payment-section').first();
      if (!$pay.length) $pay = $sec.find('.amadex-payment-form-wrapper, [id*="payment"]').first();
      if ($pay.length) {
        var $prot = $('<div class="amadex-se-protected">' + icons.lock + cfg.protected + '</div>');
        $pay.before($prot);
      }
    }

    var teaserVisible = (stepName === 'flights' && se.flights_teaser === false) ? false :
      (stepName === 'passengers' && se.passengers_teaser === false) ? false :
      (stepName === 'seats' && se.seats_teaser === false) ? false :
      (stepName === 'addons' && se.addons_teaser === false) ? false : true;
    if (!cfg.noTeaser && cfg.teaser && teaserVisible) {
      var $nav = $sec.find('.amadex-pagination-nav').first();
      var $teaser = $('<div class="amadex-se-teaser">' + (icons[cfg.teaserIcon] || '') + cfg.teaser + '</div>');
      if ($nav.length) $nav.before($teaser);
      else $sec.append($teaser);
    }

    if (stepName === 'addons' && se.addons_optional !== false) {
      var $sub = $sec.find('.amadex-section-subtitle').first();
      if ($sub.length && !$sec.find('.amadex-se-optional').length) {
        var optLabel = (cfg.optional_label && cfg.optional_label !== '') ? cfg.optional_label : 'Optional';
        $sub.append(' <span class="amadex-se-optional">' + optLabel + '</span>');
      }
    }

    var anim = (settings && settings.animations) ? settings.animations : {};
    if (anim.step_section_enter !== false) {
      $sec.addClass('amadex-se-enter');
    }
    $sec.data('amadex-se-enhanced', 1);
  }

  function onStepChange(stepName) {
    var anim = (settings && settings.animations) ? settings.animations : {};
    $('.amadex-booking-section[data-section]').removeClass('amadex-se-enter');
    var $active = $('.amadex-booking-section.step-active[data-section="' + stepName + '"]');
    if ($active.length) {
      if (anim.step_section_enter !== false) {
        $active.addClass('amadex-se-enter');
      }
      enhanceBookingStep(stepName);
    }
  }

  function initBookingSteps() {
    if (!$('#amadex-booking-page').length) return;

    $(document).on('amadexBookingStepChanged.amadexSe', function (e, stepName) {
      onStepChange(stepName);
    });

    var step = (typeof URLSearchParams !== 'undefined' && window.location.search)
      ? new URLSearchParams(window.location.search).get('step')
      : (window.sessionStorage && sessionStorage.getItem('amadex_booking_step'));
    if (step && stepConfig[step]) {
      onStepChange(step);
    } else {
      onStepChange('flights');
    }
  }

  /* ---------- Payment page (Stripe) ---------- */
  function initPaymentPage() {
    if (!$('.amadex-payment-page-container').length) return;
    var se = (settings && settings.step_elements) ? settings.step_elements : {};
    if (se.payment_secure_bar_enabled === false) return;
    var t = (settings && settings.text) ? settings.text : {};
    var secureText = t.payment_secure_bar || 'Secure payment';
    var $c = $('.amadex-payment-page-container');
    injectOnce($c, '.amadex-se-secure-bar', function () {
      return $('<div class="amadex-se-secure-bar">' + icons.lock + ' ' + secureText + '</div>');
    });
  }

  /* ---------- Bootstrap ---------- */
  function init() {
    if ($('.amadex-search-modern').length) initSearch();
    if ($('#amadex-results-page').length) initResults();
    if ($('#amadex-booking-page').length) initBookingSteps();
    if ($('.amadex-payment-page-container').length) initPaymentPage();
  }

  $(document).ready(init);

  window.AmadexStepElements = { init: init, enhanceBookingStep: enhanceBookingStep, onStepChange: onStepChange };
})();
