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
      container.html("Error: Missing configuration");
      $loadingSpinner.fadeOut();
      return;
    }

    // Create a nonce for security
    var nonce = container.data("nonce") || "";

    // Get the AJAX URL from the global object or use the default WordPress path
    var ajaxUrl = window.wetravelTripsData
      ? window.wetravelTripsData.ajaxurl
      : "/wp-admin/admin-ajax.php";

    // Check if we're in an editing environment
    var isEditMode =
      (window.parent && window.parent !== window) ||
      (window.frames && window.frames.length > 0) ||
      document.body.classList.contains("editor-body") ||
      document.body.classList.contains("wp-admin") ||
      document.body.classList.contains("edit-php") ||
      (window.location.href &&
        window.location.href.indexOf("action=edit") > -1);

    // Hide spinner on success
    $loadingSpinner.fadeOut();

    // Call the global tripsLoaded function if it exists
    if (typeof window.tripsLoaded === "function") {
      window.tripsLoaded(blockId);
    }

    // Trigger the tripsRendered event after content is loaded
    container.trigger("tripsRendered");
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
