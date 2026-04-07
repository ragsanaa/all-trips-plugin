(function ($) {
  // Use capture phase to intercept "See More" clicks before the embed checkout script
  document.addEventListener('click', function (e) {
    var link = e.target.closest('.learn-more-link');
    if (link && link.closest('.grid-view, .carousel-view')) {
      e.stopImmediatePropagation();
    }
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
