/**
 * Trips Loader JavaScript
 * Handles loading and displaying trips from WeTravel
 * This updated version properly integrates with the loading spinner
 */

(function ($) {
  "use strict";

  // Global function to load trips (making it available to other scripts)
  window.loadTrips = function (container) {
    // Get data attributes
    var slug = container.data("slug");
    var env = container.data("env");

    // Find the block ID from the container ID
    var blockId = container.attr("id").replace("trips-container-", "");

    // Show loading spinner before AJAX call
    var $loadingSpinner = $("#loading-" + blockId);
    $loadingSpinner.fadeIn();

    // Validate required data
    if (!slug || !env) {
      container.html(
        "Error: Missing configuration. Please check your WeTravel Widgets Plugin settings and try again."
      );
      $loadingSpinner.fadeOut();
      return;
    }

    // Verify wetravelTripsData exists
    if (!window.wetravelTripsData || !window.wetravelTripsData.ajaxurl) {
      console.error(
        "WeTravel Trips: Ajax URL not found. Make sure the plugin is properly initialized."
      );
      container.html(
        window.wetravelTripsData?.loading_error ||
          "Error: Plugin not properly initialized"
      );
      $loadingSpinner.fadeOut();
      return;
    }

    // Make the Ajax call
    $.ajax({
      url: window.wetravelTripsData.ajaxurl,
      type: "POST",
      data: {
        action: "wetravel_load_trips",
        nonce: window.wetravelTripsData.nonce,
        slug: slug,
        env: env,
        block_id: blockId,
      },
      success: function (response) {
        if (response.success) {
          // Update container with trips HTML
          container.html(response.data.html);

          // Initialize event handlers
          initializeEventHandlers(container);

          // Call the global tripsLoaded function if it exists
          if (typeof window.tripsLoaded === "function") {
            window.tripsLoaded(blockId);
          }

          // Trigger the tripsRendered event
          container.trigger("tripsRendered");
        } else {
          container.html(
            response.data.message || window.wetravelTripsData.loading_error
          );
        }
      },
      error: function (xhr, status, error) {
        console.error("WeTravel Trips: Ajax error", error);
        container.html(window.wetravelTripsData.loading_error);
      },
      complete: function () {
        $loadingSpinner.fadeOut();
      },
    });
  };

  // Function to initialize event handlers for interactive elements
  function initializeEventHandlers(container) {
    // Initialize pagination controls
    container
      .find(".wetravel-trips-pagination .page-number")
      .on("click", function () {
        var pageNumber = $(this).data("page");

        // Update active page indicator
        $(this).siblings().removeClass("active");
        $(this).addClass("active");

        // Show/hide trips based on pagination
        var itemsPerPage = container.data("items-per-page");
        var start = (pageNumber - 1) * itemsPerPage;
        var end = start + itemsPerPage;

        container.find(".trip-item").each(function (index) {
          if (index >= start && index < end) {
            $(this).removeClass("hidden-item").addClass("visible-item");
          } else {
            $(this).removeClass("visible-item").addClass("hidden-item");
          }
        });
      });

    // Apply fade effect to long descriptions
    applyDescriptionFades();
  }

  // Initialize on document ready
  $(document).ready(function () {
    // Show all loading spinners initially
    $(".wetravel-trips-loading").show();

    // Find any containers that need trips loaded
    $(".wetravel-trips-container:not(:has(.trip-item))").each(function () {
      window.loadTrips($(this));
    });

    // Initialize event handlers for any existing content
    initializeEventHandlers($(".wetravel-trips-container"));
  });

  // Call the function after trips are rendered
  $(document).on("tripsRendered", function (e) {
    // Apply fade effects to descriptions
    applyDescriptionFades();

    // Initialize other interactive elements
    initializeEventHandlers($(e.target));
  });

  /**
   * Apply fade effects to descriptions that exceed 3 lines
   */
  function applyDescriptionFades() {
    $(".trip-description").each(function () {
      var $this = $(this);

      // Calculate the line height and max height for 3 lines
      var lineHeight = parseInt($this.css("line-height"));
      var maxHeight = lineHeight * 3;

      // Check if the actual scroll height exceeds what we want to show
      if ($this[0].scrollHeight > maxHeight) {
        $this.addClass("needs-fade");
      }
    });
  }
})(jQuery);
