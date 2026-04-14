(function ($) {
  // Use capture phase to intercept "See More" clicks before the embed checkout script
  document.addEventListener('click', function (e) {
    var link = e.target.closest('.learn-more-link');
    if (link && link.closest('.grid-view, .carousel-view')) {
      e.stopImmediatePropagation();
    }
  }, true);

  // Defensive capture-phase handler: ensure trip_link <a> buttons always navigate,
  // even if another plugin/theme script calls preventDefault() later in the chain.
  document.addEventListener('click', function (e) {
    var button = e.target.closest('a.trip-button[href]');
    if (!button) {
      return;
    }
    var container = button.closest('.wetravel-trips-container');
    if (!container || container.getAttribute('data-button-type') !== 'trip_link') {
      return;
    }
    e.stopImmediatePropagation();
    window.open(button.getAttribute('href'), '_blank');
  }, true);

  // Track button clicks using capture phase to fire before checkout/navigation
  document.addEventListener('click', function (e) {
    var button = e.target.closest('.trip-button, .wtrvl-checkout_button');
    if (!button) {
      return;
    }

    if (!window.WetravelTracking || !window.WetravelTracking.hasConsent) {
      return;
    }

    var $button = $(button);
    var $widget = $button.closest('.wetravel-trips-container');
    if (!$widget.length) {
      return;
    }

    var widgetData = window.WetravelTracking.getWidgetData($widget);
    var tripUuid = $button.data('uuid') || $button.data('trip-uuid') || '';

    window.WetravelTracking.trackEvent('button_click', widgetData.widget_id, {
      ...widgetData,
      button_text: $button.text().trim(),
      button_href: $button.attr('href') || '',
      trip_uuid: tripUuid,
    });
  }, true);

  $(document).ready(function () {
    // Handle click events for trip items with trip_link button type
    $(document).on('click', '.grid-view .trip-item, .carousel-view .trip-item', function (e) {
      var container = $(this).closest('.wetravel-trips-container');
      var buttonType = container.data('button-type');
      var href = $(this).attr('href');

      // book_now type is handled by the embed checkout script on the card itself
      if (buttonType === 'book_now') {
        return;
      }

      // For trip_link type, navigate to the href
      if (buttonType === 'trip_link' && href) {
        e.preventDefault();
        window.open(href, '_blank');
      }
    });
  });
})(jQuery);
