(function ($) {
  $(document).ready(function () {
    // Handle click events for trip items
    $(document).on('click', '.trip-item', function (e) {
      const container = $(this).closest('.wetravel-trips-container');
      const buttonType = container.data('button-type');
      const href = $(this).attr('href');

      // Only handle clicks for grid and carousel views
      if (!$(this).closest('.grid-view, .carousel-view').length) {
        return;
      }

      // Only handle trip_link type, let embed_checkout handle its own clicks
      if (buttonType === 'book_now') {
        return;
      }

      // For trip_link type, navigate to the href
      if (buttonType === 'trip_link' && href) {
        e.preventDefault(); // Prevent default only for trip_link
        window.open(href, '_blank');
      }
    });
  });
})(jQuery);
