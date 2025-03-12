/**
 * Trips Loader JavaScript
 * Handles loading and displaying trips from WeTravel
 */

(function ($) {
  "use strict";

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

    // Validate required data
    if (!slug || !env) {
      container.find(".all-trips-loading").html("Error: Missing configuration");
      return;
    }

    // Build API endpoint
    var apiUrl = env + "/api/v2/embeds/all_trips?slug=" + slug;

    // Build query parameters
    var params = {};

    // Add trip type filter
    if (tripType !== "all") {
      params.type = tripType;

      // Add date range for one-time trips
      if (tripType === "one-time" && dateStart && dateEnd) {
        params.startDate = dateStart;
        params.endDate = dateEnd;
      }
    }

    // Convert params to query string
    var queryString = Object.keys(params)
      .map(function (key) {
        return key + "=" + encodeURIComponent(params[key]);
      })
      .join("&");

    if (queryString) {
      apiUrl += "?" + queryString;
    }

    // Try to get cached data first
    $cache_key = "wetravel_trips_".md5($api_url);
    $cached_data = get_transient($cache_key);

    if (false !== $cached_data) {
      return renderTrips(container, $cached_data, {
        displayType: displayType,
        buttonType: buttonType,
        buttonText: buttonText,
        buttonColor: buttonColor,
      });
    }
    // Fetch trips from the API
    $.ajax({
      url: apiUrl,
      type: "GET",
      dataType: "json",
      success: function (response) {
        renderTrips(container, response, {
          displayType: displayType,
          buttonType: buttonType,
          buttonText: buttonText,
          buttonColor: buttonColor,
        });
      },
      error: function (xhr, status, error) {
        container
          .find(".all-trips-loading")
          .html("Error loading trips: " + error);
      },
    });

    // Cache for 1 minutes
    set_transient($cache_key, $data['trips'], 60);
  }

  /**
   * Render trips in the container
   */
  function renderTrips(container, tripsData, options) {
    var tripsContainer = container.find(".all-trips-list");
    var tripsHtml = "";

    // Hide loading message
    container.find(".all-trips-loading").hide();

    // Check if trips exist
    if (!tripsData || !tripsData.length) {
      tripsContainer.html(
        '<div class="all-trips-no-results">No trips found</div>'
      );
      return;
    }

    // Generate HTML based on display type
    switch (options.displayType) {
      case "grid":
        tripsHtml += '<div class="all-trips-grid">';
        break;

      case "carousel":
        tripsHtml += '<div class="all-trips-carousel">';
        break;

      default: // vertical
        tripsHtml += '<div class="all-trips-vertical">';
        break;
    }

    // Add each trip
    $.each(tripsData, function (index, trip) {
      tripsHtml += renderTripItem(trip, options);
    });

    tripsHtml += "</div>";

    // Add to the DOM
    tripsContainer.html(tripsHtml);

    // Initialize specific display types
    if (options.displayType === "carousel") {
      initializeCarousel(tripsContainer);
    }
  }

  /**
   * Render a single trip item
   */
  function renderTripItem(trip, options) {
    var html = '<div class="all-trips-item">';

    // Trip image
    if (trip.featuredImage) {
      html += '<div class="all-trips-item-image">';
      html += '<img src="' + trip.featuredImage + '" alt="' + trip.title + '">';
      html += "</div>";
    }

    // Trip content
    html += '<div class="all-trips-item-content">';

    // Trip title
    html += '<h3 class="all-trips-item-title">' + trip.title + "</h3>";

    // Trip dates
    if (trip.startDate) {
      var dateDisplay = formatTripDates(trip);
      html += '<div class="all-trips-item-dates">' + dateDisplay + "</div>";
    }

    // Trip price
    if (trip.price) {
      html +=
        '<div class="all-trips-item-price">From ' +
        formatPrice(trip.price, trip.currency) +
        "</div>";
    }

    // Trip button
    var buttonUrl =
      options.buttonType === "book_now" ? trip.bookingUrl : trip.detailUrl;
    var buttonLabel =
      options.buttonText ||
      (options.buttonType === "book_now" ? "Book Now" : "View Trip");

    html +=
      '<a href="' + buttonUrl + '" class="all-trips-button" target="_blank" ';
    html += 'style="background-color: ' + options.buttonColor + ';">';
    html += buttonLabel;
    html += "</a>";

    html += "</div>"; // Close trip content
    html += "</div>"; // Close trip item

    return html;
  }

  /**
   * Format trip dates for display
   */
  function formatTripDates(trip) {
    if (!trip.startDate) {
      return "";
    }

    var startDate = new Date(trip.startDate);
    var formattedStart = startDate.toLocaleDateString();

    if (trip.endDate) {
      var endDate = new Date(trip.endDate);
      var formattedEnd = endDate.toLocaleDateString();
      return formattedStart + " - " + formattedEnd;
    }

    return formattedStart;
  }

  /**
   * Format price with currency
   */
  function formatPrice(price, currency) {
    if (!price) return "";

    // Default to USD if no currency provided
    currency = currency || "USD";

    try {
      return new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: currency,
      }).format(price);
    } catch (e) {
      // Fallback if Intl is not supported
      return currency + " " + price;
    }
  }

  /**
   * Initialize carousel functionality
   */
  function initializeCarousel(container) {
    // This would be initialized via carousel.js
    // For now we'll just add a placeholder class
    container.find(".all-trips-carousel").addClass("carousel-initialized");
  }
})(jQuery);
