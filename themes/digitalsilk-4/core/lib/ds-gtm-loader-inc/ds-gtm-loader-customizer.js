jQuery(document).ready(function ($) {
  if (!wp) {
    return;
  }


  /**
   * Toggles the visibility of the GTM ID field and related controls based on the "Enable GTM" and "Enable GTM Async" settings.
   *
   * The GTM ID field and related controls are only visible if GTM is enabled and async loading is enabled.
   * If GTM is enabled but async loading is disabled, the GTM ID field is visible but the async controls are hidden.
   * If GTM is disabled, all controls are hidden.
   *
   * @since 1.0.0
   * @function toggleGTMIdField
   */
  function toggleGTMIdField() {
    const isGTMEnabled = wp.customize('ds_gtm_enable').get();
    const isGTMAsync = wp.customize('ds_gtm_async_enable').get();

    const gtmIdControl = $('#customize-control-ds_gtm_id_control');
    const gtmAsyncControl = $('#customize-control-ds_gtm_async_enable_control');
    const gtm4wpControl = $('#customize-control-ds_gtm_id_gtm4wp_control');
    const gtm4wpAsyncControl = $('#customize-control-ds_gtm_id_gtm4wp_async_control');

    const shouldShow = isGTMEnabled && isGTMAsync;

    if (gtmIdControl && gtmAsyncControl.length > 0) {
      gtmIdControl.toggle(isGTMEnabled);
    }
    if (gtmAsyncControl && gtmAsyncControl.length > 0) {
      gtmAsyncControl.toggle(isGTMEnabled);
    }
    if (gtm4wpControl && gtm4wpControl.length > 0) {
      gtm4wpControl.toggle(!shouldShow);
    }
    if (gtm4wpAsyncControl && gtm4wpAsyncControl.length > 0) {
      gtm4wpAsyncControl.toggle(shouldShow);
    }
  }


  /**
   * Validate the GTM ID input and display a warning if it is invalid.
   *
   * Checks if the input is a valid GTM ID (GTM-XXXXXXXXX) and validates each ID if multiple IDs are entered with commas.
   * If the input is invalid, display a warning message.
   *
   * @since 1.0.0
   * @function validateGTMId
   */
  function validateGTMId() {
    const gtmIdInput = wp.customize('ds_gtm_id').get(); // Input string
    let gtmIds = gtmIdInput.split(',').map(id => id.trim()); // Split by commas and trim whitespace.
    gtmIds = gtmIds.filter(id => /\w+/.test(id));

    let message = `It seems the GTM ID entered is invalid. Please double-check the format and ensure it follows GTM-XXXXXXXXX.`;
    let isValid = false;
    if (gtmIds && gtmIds.length > 1) {
      message = `It seems one or more GTM IDs entered are invalid. Please ensure all IDs follow the format GTM-XXXXXXXXX. Separate multiple IDs with commas.`;
      isValid = gtmIds.every(id => /^[A-Z]{1,3}-[A-Z0-9]{1,10}$/.test(id)); // Validate each ID
    } else {
      isValid = /^[A-Z]{1,3}-[A-Z0-9]{1,10}$/.test(gtmIds[0]);
    }

    // Check if warning message exists; if not, create one.
    if ($('#ds_gtm_id_warning').length === 0) {
      $('#customize-control-ds_gtm_id_control').append(
        `<span id="ds_gtm_id_warning" style="color: red; font-size: 12px; display: none;">${message}</span>`
      );
    } else {
      $('#ds_gtm_id_warning').text(message);
    }

    // Show or hide warning based on validity.
    if (isValid) {
      $('#ds_gtm_id_warning').hide();
    } else {
      $('#ds_gtm_id_warning').show();
    }
  }

  // Initial call to set visibility on load.
  toggleGTMIdField();

  // Bind change event to 'ds_gtm_enable' setting.
  wp.customize('ds_gtm_enable', function (setting) {
    setting.bind(toggleGTMIdField);
  });
  wp.customize('ds_gtm_async_enable', function (setting) {
    setting.bind(toggleGTMIdField);
  });

  // Bind validation check on GTM ID change.
  wp.customize('ds_gtm_id', function (setting) {
    setting.bind(validateGTMId);
  });

});
