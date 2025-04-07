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
          // Render the trips data
          const trips = response.data;
          let tripsHtml = "";

          if (trips.length === 0) {
            tripsHtml = '<div class="no-trips">No trips found</div>';
          } else {
            trips.forEach((trip, index) => {
              const isVisible =
                index < itemsPerPage ? "visible-item" : "hidden-item";
              tripsHtml += renderTripItem(
                trip,
                {
                  env,
                  wetravelUserID,
                  displayType,
                  buttonType,
                  buttonText,
                  buttonColor,
                  itemsPerPage,
                },
                isVisible
              );
            });
          }

          // Update container content
          container.html(tripsHtml);

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

  // Function to render a single trip item
  function renderTripItem(trip, options, visibilityClass = "") {
    let html = "";
    const buttonUrl = getButtonUrl(trip, options);

    if (options.displayType === "vertical" || options.displayType === "grid") {
      html += `<div class="trip-item ${visibilityClass}">`;
    } else if (options.displayType === "carousel") {
      html += `<div class="trip-item wtrvl-checkout_button"
        data-env="${escapeHtml(options.env)}"
        data-version="v0.3"
        data-uid="${escapeHtml(options.wetravelUserID)}"
        data-uuid="${escapeHtml(trip.uuid)}"
        href="${escapeHtml(buttonUrl)}"
        style="cursor: pointer;">`;
    }

    // Image
    if (trip.default_image) {
      html += `<div class="trip-image">
        <img src="${escapeHtml(trip.default_image)}" alt="${escapeHtml(
        trip.title
      )}">
      </div>`;
    } else {
      html +=
        '<div class="no-image-placeholder"><span>No Image Available</span></div>';
    }

    // Content
    html += '<div class="trip-content">';
    html += '<div class="trip-title-desc">';
    html += `<h3>${escapeHtml(trip.title)}</h3>`;

    // Description
    if (trip.full_description) {
      html += `<div class="trip-description">${trip.full_description}</div>`;
    }
    html += "</div>"; // Close trip-title-desc

    if (options.displayType === "carousel") {
      html += "<div class='trip-loc-price'>";
    }

    // Date or duration
    html += '<div class="trip-loc-duration">';
    if (!trip.all_year) {
      html += `<div class="trip-date trip-tag">${escapeHtml(
        trip.start_end_dates
      )}</div>`;
    } else if (trip.custom_duration) {
      html += `<div class="trip-duration trip-tag">${escapeHtml(
        trip.custom_duration
      )} days</div>`;
    }
    html += `<div class="trip-location trip-tag">${escapeHtml(
      trip.location
    )}</div>`;
    html += "</div>"; // Close trip-loc-duration

    if (options.displayType !== "carousel") {
      html += "</div>"; // Close trip-content
    }

    // Price and button section
    html += '<div class="trip-price-button">';

    // Price
    if (trip.price) {
      html += `<div class="trip-price">
        <p>From</p>
        <span>${escapeHtml(trip.price.currencySymbol)}${escapeHtml(
        trip.price.amount
      )}</span>
      </div>`;
    }

    // Button
    if (options.displayType !== "carousel") {
      if (options.buttonType === "book_now") {
        html += `<button class="wtrvl-checkout_button trip-button"
          data-env="${escapeHtml(options.env)}"
          data-version="v0.3"
          data-uid="${escapeHtml(options.wetravelUserID)}"
          data-uuid="${escapeHtml(trip.uuid)}"
          href="${escapeHtml(buttonUrl)}">
          ${escapeHtml(options.buttonText)}
        </button>`;
      } else {
        html += `<a href="${escapeHtml(
          buttonUrl
        )}" class="trip-button" target="_blank">
          ${escapeHtml(options.buttonText)}
        </a>`;
      }
    }

    html += "</div>"; // Close trip-price-button
    if (options.displayType === "carousel") {
      html += "</div>"; // Close trip-loc-price
      html += "</div>"; // Close trip-content
    }
    html += "</div>"; // Close trip-item

    return html;
  }

  // Helper function to get button URL
  function getButtonUrl(trip, options) {
    if (options.buttonType === "book_now") {
      return `${options.env}/checkout_embed?uuid=${trip.uuid}`;
    } else {
      return trip.href || `${options.env}/trips/${trip.uuid}`;
    }
  }

  // Helper function to escape HTML
  function escapeHtml(unsafe) {
    if (unsafe == null) return "";
    return unsafe
      .toString()
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }
})(jQuery);
