# How to Test the Three Loading Features

This guide explains how to test:
1. **Skeleton UI** (template + CSS)
2. **Loading animation** (template + CSS)
3. **amadex-loading-animations.css** (styles for both)

---

## Prerequisites

- Amadex plugin active.
- A page that shows the Amadex flight search (e.g. your flight search or flight-results page).

---

## 1. Test via normal flight search (CSS + inline skeleton/animation)

The **CSS file** and the **inline** skeleton/loading animation (built in JavaScript) use the same class names, so you can test styling without using the PHP templates.

### Step 1: Enable the features in WordPress Admin

1. Go to **WP Admin → Amadex → Advanced Settings** (or **Settings → Amadex**, then the Advanced/Performance section).
2. Enable:
   - **Skeleton UI** (e.g. “Enable Skeleton UI” or similar).
   - **Loading animation** (e.g. “Enable Loading Animation” or similar).
3. Save.

### Step 2: Run a flight search

1. Open the **front-end page** that has the Amadex search (e.g. your homepage or flight search page).
2. Enter **origin**, **destination**, **dates**, and run the search.
3. On the **flight results** page you should see:
   - **Skeleton**: Gray placeholder “cards” (shimmer lines) before results load.
   - **Loading animation**: Spinner, “Searching your flights...” (or “Searching X to Y...”), and a progress bar.
4. When results load, skeleton and animation should disappear.

**What you’re testing here:**  
- `amadex-loading-animations.css` is applied (spinner, progress bar, skeleton cards, fade-out).  
- The **inline** skeleton and loading animation (from `amadex-streaming-loader.js`) work; the **PHP templates** are not used in this flow unless you enable them (see Section 3).

---

## 2. Test the PHP templates via browser console (AJAX)

The **skeleton** and **loading animation** can be loaded from the server via AJAX. By default the plugin uses inline HTML; to verify the **PHP templates** and AJAX handlers work, call the actions from the console.

### Where to run the commands

- Use a page where Amadex scripts are loaded (e.g. the **flight results** page or any page with the Amadex shortcode).
- Open **Developer Tools (F12) → Console**.

### Get the nonce

In the console, type:

```js
AmadexConfig.nonce
```

Copy the value (e.g. `"abc123..."`).

### Test 1: Skeleton template (PHP)

Run (replace `YOUR_NONCE` with the value from above):

```js
fetch(AmadexConfig.ajaxUrl, {
  method: 'POST',
  headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
  body: new URLSearchParams({
    action: 'amadex_get_skeleton',
    nonce: AmadexConfig.nonce,
    count: 5
  })
})
  .then(r => r.json())
  .then(data => {
    console.log('Skeleton success:', data.success);
    if (data.success && data.data.html) {
      console.log('HTML length:', data.data.html.length);
      console.log('HTML preview:', data.data.html.substring(0, 300));
    } else {
      console.log('Error:', data);
    }
  });
```

- **Success:** `data.success === true`, `data.data.html` is a string containing the skeleton markup (e.g. `amadex-skeleton-container`, `amadex-skeleton-card`).
- **Failure:** `data.success === false` or “Skeleton template not found” → check that `templates/loading-skeleton.php` exists and is readable.

### Test 2: Loading animation template (PHP)

Run (replace nonce if needed):

```js
fetch(AmadexConfig.ajaxUrl, {
  method: 'POST',
  headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
  body: new URLSearchParams({
    action: 'amadex_get_loading_animation',
    nonce: AmadexConfig.nonce,
    origin: 'YYZ',
    destination: 'LAX'
  })
})
  .then(r => r.json())
  .then(data => {
    console.log('Animation success:', data.success);
    if (data.success && data.data.html) {
      console.log('HTML length:', data.data.html.length);
      console.log('Contains "Searching': data.data.html.indexOf('Searching') !== -1);
      console.log('Contains "YYZ"/"LAX":', data.data.html.indexOf('YYZ') !== -1, data.data.html.indexOf('LAX') !== -1);
    } else {
      console.log('Error:', data);
    }
  });
```

- **Success:** `data.success === true`, `data.data.html` contains “Searching” and optionally “YYZ” and “LAX”.
- **Failure:** “Animation template not found” → check that `templates/loading-animation.php` exists and is readable.

---

## 3. (Optional) Use server templates in the live search

Right now the streaming loader only requests the **PHP** skeleton and animation if `AmadexConfig.skeletonTemplate` and `AmadexConfig.animationTemplate` are set. They are not set by default, so the live search uses **inline** HTML.

- **Without changing code:** Testing in **Sections 1 and 2** is enough to confirm:
  - CSS and inline behavior (Section 1).
  - PHP templates and AJAX (Section 2).
- **To use the server templates in the real search:** A developer would add `skeletonTemplate: true` and `animationTemplate: true` to the `AmadexConfig` array in `includes/frontend/class-amadex-shortcodes.php` (where `$amadex_config` is built). Then the same search from Section 1 would load skeleton and animation via AJAX from the PHP templates.

---

## Quick checklist

| What to test              | How                                      | Expected result                          |
|---------------------------|------------------------------------------|------------------------------------------|
| CSS on skeleton           | Section 1: enable Skeleton UI, run search| Gray skeleton cards with shimmer         |
| CSS on loading animation  | Section 1: enable Loading animation      | Spinner + message + progress bar         |
| Skeleton PHP template     | Section 2: console `amadex_get_skeleton` | `success: true`, HTML with skeleton divs |
| Animation PHP template    | Section 2: console `amadex_get_loading_animation` | `success: true`, HTML with “Searching” |

If any step fails, check: plugin active, correct page, nonce valid, and that `templates/loading-skeleton.php`, `templates/loading-animation.php`, and `assets/css/amadex-loading-animations.css` exist and were not modified by another process.
