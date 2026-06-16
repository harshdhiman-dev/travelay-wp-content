/**
 * Amadex Dropdown Fix
 *
 * Safety-net override to guarantee the dropdown closes after airport selection.
 *
 * The root cause (.hide() setting an inline display:none that CSS cannot
 * override) has been fixed directly in amadex-search-modern.js by removing
 * the chained .hide() call on line ~1238.
 *
 * This file remains as a lightweight safety net that:
 *  - Removes active classes 50ms after any suggestion click (catches edge cases)
 *  - Never calls .hide() so it cannot cause the re-open problem
 *
 * WordPress note: jQuery runs in no-conflict mode — never use bare `$`.
 */
// jQuery(function ($) {

//     $(document).on('click', '.amadex-suggestion-item', function () {
//         setTimeout(function () {
//             $('.amadex-suggestions-dropdown').removeClass('active');
//             $('.amadex-suggestions').removeClass('active');
//             $('.amadex-location-field').removeClass('field-active');
//         }, 50);
//     });

// });