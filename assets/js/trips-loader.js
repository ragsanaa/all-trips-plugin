/**
 * Trips Loader JavaScript
 * Handles loading and displaying trips from WeTravel
 */

(function ($) {
  "use strict";

  // Global function to load trips (making it available to other scripts)
  window.loadTrips = function (container) {
    // Show loading state
    container.find(".all-trips-loading").show();

    // Get data attributes
    var slug = container.data("slug");
    var env = container.data("env");
    var displayType = container.data("display-type");
    var buttonType = container.data("button-type");
    var buttonText = container.data("button-text");
    var buttonColor = container.data("button-color");
    var tripType = container.data("trip-type") || "all";
    var dateStart = container.data("date-start") || "";
    var dateEnd = container.data("date-end") || "";
    var itemsPerPage = container.data("items-per-page") || 10;

    // Validate required data
    if (!slug || !env) {
      container.find(".all-trips-loading").html("Error: Missing configuration");
      return;
    }

    // Create a nonce for security
    var nonce = container.data("nonce") || "";

    // Get the AJAX URL from the global object or use the default WordPress path
    var ajaxUrl = window.allTripsData
      ? window.allTripsData.ajaxurl
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
        no_cache: isEditMode ? new Date().getTime() : null, // Add timestamp to prevent caching in edit mode
      },
      success: function (response) {
        if (response.success && response.data) {
          renderTrips(container, response.data, {
            env: env,
            displayType: displayType,
            buttonType: buttonType,
            buttonText: buttonText,
            buttonColor: buttonColor,
            itemsPerPage: itemsPerPage,
          });
        } else {
          container
            .find(".all-trips-loading")
            .html("Error: " + (response.data || "No trips found"));
        }
      },
      error: function (xhr, status, error) {
        container
          .find(".all-trips-loading")
          .html("Error loading trips: " + error);
      },
    });
  };

  $(document).ready(function () {
    // Find all trip containers on the page
    $(".all-trips-container").each(function () {
      var container = $(this);
      loadTrips(container);
    });
  });

  /**
   * Load trips data from the API based on container settings
   */
  function loadTrips(container) {
    // Show loading state
    container.find(".all-trips-loading").show();

    // Get data attributes
    var slug = container.data("slug");
    var env = container.data("env");
    var displayType = container.data("display-type");
    var buttonType = container.data("button-type");
    var buttonText = container.data("button-text");
    var buttonColor = container.data("button-color");
    var tripType = container.data("trip-type") || "all";
    var dateStart = container.data("date-start") || "";
    var dateEnd = container.data("date-end") || "";
    var itemsPerPage = container.data("items-per-page") || 10;

    // Validate required data
    if (!slug || !env) {
      container.find(".all-trips-loading").html("Error: Missing configuration");
      return;
    }

    // Create a nonce for security
    var nonce = container.data("nonce") || "";

    // Get the AJAX URL from the global object or use the default WordPress path
    var ajaxUrl = window.allTripsData
      ? window.allTripsData.ajaxurl
      : "/wp-admin/admin-ajax.php";

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
      },
      success: function (response) {
        if (response.success && response.data) {
          renderTrips(container, response.data, {
            env: env,
            displayType: displayType,
            buttonType: buttonType,
            buttonText: buttonText,
            buttonColor: buttonColor,
            itemsPerPage: itemsPerPage,
          });
        } else {
          container
            .find(".all-trips-loading")
            .html("Error: " + (response.data || "No trips found"));
        }
      },
      error: function (xhr, status, error) {
        container
          .find(".all-trips-loading")
          .html("Error loading trips: " + error);
      },
    });
  }

  /**
   * Render trips in the container
   */
  function renderTrips(container, trips, options) {
    var blockId = container.attr("id").replace("trips-container-", "");
    var env = options.env;
    var itemsPerPage = options.itemsPerPage;
    var tripsHtml = "";

    // Hide loading message
    container.find(".all-trips-loading").hide();

    // Check if trips exist
    if (!trips || !trips.length) {
      container.html('<div class="no-trips">No trips found</div>');
      return;
    }

    if (options.displayType === "carousel") {
      // Carousel view layout
      tripsHtml += '<div class="swiper"><div class="swiper-wrapper">';

      $.each(trips, function (index, trip) {
        tripsHtml += '<div class="swiper-slide">';
        tripsHtml += renderTripItem(trip, options);
        tripsHtml += "</div>";
      });

      tripsHtml += "</div>";
      tripsHtml += '<div class="swiper-pagination"></div>';
      tripsHtml += '<div class="swiper-button-next"></div>';
      tripsHtml += '<div class="swiper-button-prev"></div>';
      tripsHtml += "</div>";
    } else {
      // Grid or vertical view
      $.each(trips, function (index, trip) {
        var visibilityClass =
          index < itemsPerPage ? "visible-item" : "hidden-item";
        tripsHtml += renderTripItem(trip, options, visibilityClass);
      });
    }

    // Add to the DOM
    container.html(tripsHtml);

    // Add click handlers for carousel items
    if (options.displayType === "carousel") {
      container.find(".trip-item").on("click", function() {
        var url = $(this).data("trip-url");
        if (url) {
          window.open(url, "_blank");
        }
      });
    }

    // Trigger a custom event to notify that trips have been rendered
    // This will be captured by carousel.js and pagination.js
    container.trigger("tripsRendered");
  }

  /**
   * Render a single trip item based on the original PHP implementation
   * Get button URL for a trip based on options and trip data
   */
  function getButtonUrl(trip, options) {
    var env = options.env;
    var buttonUrl = "";

    // Set up button URL based on button type
    if (options.buttonType === "book_now") {
      buttonUrl = env + "/checkout_embed?uuid=" + trip.uuid;
    } else {
      buttonUrl = env + "/trips/" + trip.uuid;
      if (trip.href) {
        buttonUrl = trip.href;
      }
    }

    return buttonUrl;
  }

  /**
   * Render a single trip item based on the original PHP implementation
   */
  function renderTripItem(trip, options, visibilityClass = "") {
    var html = "";
    var buttonUrl = getButtonUrl(trip, options);

    if (options.displayType === "vertical" || options.displayType === "grid") {
      html += '<div class="trip-item ' + visibilityClass + '">';
    } else if (options.displayType === "carousel") {
      html += '<div class="trip-item" data-trip-url="' + buttonUrl + '" style="cursor: pointer;">';
    }

    // Image
    if (trip.default_image) {
      html += '<img src="' + trip.default_image + '" alt="' + trip.title + '">';
    } else {
      html +=
        '<div class="no-image-placeholder"><span>No Image Available</span></div>';
    }

    // Content
    html += '<div class="trip-content">';
    html += "<h3>" + trip.title + "</h3>";

    // Description
    if (trip.full_description) {
      html +=
        '<div class="trip-description">' + trip.full_description + "</div>";
    }

    // Date or duration
    html += '<div class="trip-loc-duration">';
    if (!trip.all_year) {
      html +=
        '<div class="trip-date trip-tag">' + trip.start_end_dates + "</div>";
    } else if (trip.custom_duration) {
      html +=
        '<div class="trip-duration trip-tag">' +
        trip.custom_duration +
        " days</div>";
    }
    html += '<div class="trip-location trip-tag">' + trip.location + "</div>";
    html += "</div>"; // Close trip-loc-duration
    html += "</div>"; // Close trip-content

    // Price and button section
    html += '<div class="trip-price-button">';

    // Price
    if (trip.price) {
      html +=
        '<div class="trip-price"><p>From</p> <span>' +
        trip.price.currencySymbol +
        trip.price.amount +
        "</span></div>";
    }

    // Button
    html +=
      '<a href="' +
      buttonUrl +
      `" class="trip-button" target="_blank">` +
      options.buttonText +
      "</a>";

    html += "</div>"; // Close trip-price-button
    html += "</div>"; // Close trip-item

    return html;
  }
})(jQuery);
