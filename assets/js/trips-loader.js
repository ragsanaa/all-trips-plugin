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
    var wetravelUserID = container.data("wetravel-user-id");
    var displayType = container.data("display-type");
    var buttonType = container.data("button-type");
    var buttonText = container.data("button-text");
    var buttonColor = container.data("button-color");
    var tripType = container.data("trip-type") || "all";
    var dateStart = container.data("date-start") || "";
    var dateEnd = container.data("date-end") || "";
    var itemsPerPage = container.data("items-per-page") || 10;

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

    // Create AJAX request
    $.ajax({
      url: ajaxUrl,
      type: "GET",
      dataType: "json",
      data: {
        action: "fetch_wetravel_trips",
        nonce: nonce,
        slug: slug,
        env: env,
        trip_type: tripType,
        date_start: dateStart,
        date_end: dateEnd,
        block_id: blockId,
        no_cache: isEditMode ? new Date().getTime() : null, // Add timestamp to prevent caching in edit mode
      },
      success: function (response) {
        if (response.success && response.data) {
          // Hide spinner on success
          $loadingSpinner.fadeOut();

          // Call the global tripsLoaded function if it exists
          if (typeof window.tripsLoaded === "function") {
            window.tripsLoaded(blockId);
          }

          // Trigger the tripsRendered event after content is loaded
          container.trigger("tripsRendered");
        } else {
          container.html(
            "Error: " + (response.data.message || "No trips found")
          );
          $loadingSpinner.fadeOut();
        }
      },
      error: function (xhr, status, error) {
        container.html("Error loading trips: " + error);
        $loadingSpinner.fadeOut();
      },
      // Add a timeout handler
      timeout: 15000, // 15 second timeout
      complete: function () {
        // Ensure spinner is hidden in all cases after a short delay
        setTimeout(function () {
          $loadingSpinner.fadeOut();
        }, 500);
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
