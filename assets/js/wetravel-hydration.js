/**
 * WeTravel Widgets Hydration Script
 *
 * Implements client-side hydration for hybrid SSR + JS pattern.
 * This script fetches fresh data from the REST API and updates the DOM
 * after the initial server-rendered content is displayed.
 */

(function ($) {
  "use strict";

  // Global hydration function
  window.WeTravelTripsHydrate = function (blockId, config) {
    if (!blockId || !config) {
      console.error("WeTravel Hydration: Missing blockId or config");
      return;
    }

    const container = document.getElementById("trips-container-" + blockId);
    if (!container) {
      console.error(
        "WeTravel Hydration: Container not found for block " + blockId
      );
      return;
    }

    // Build the API URL
    const apiUrl = "/wp-json/wetravel/v1/trips";
    const params = new URLSearchParams({
      block_id: blockId,
      slug: config.slug || "",
      env: config.env || "",
      trip_type: config.tripType || "all",
      date_start: config.dateStart || "",
      date_end: config.dateEnd || "",
      locations: config.locations || "",
      display_type: config.displayType || "vertical",
      button_type: config.buttonType || "book_now",
      button_text: config.buttonText || "",
      button_color: config.buttonColor || "#33ae3f",
      items_per_page: config.itemsPerPage || 10,
      items_per_row: config.itemsPerRow || 3,
      items_per_slide: config.itemsPerSlide || 1,
      wetravel_user_id: config.wetravelUserID || "",
    });

    // Fetch fresh data
    fetch(apiUrl + "?" + params.toString())
      .then((response) => {
        if (!response.ok) {
          throw new Error(
            "HTTP " + response.status + ": " + response.statusText
          );
        }
        return response.json();
      })
      .then((data) => {
        if (data.success && data.html) {
          // Smoothly update the content
          updateContent(container, data.html, config);

          // Trigger custom event for other scripts
          $(document).trigger("wetravel:hydrated", {
            blockId: blockId,
            tripsCount: data.trips_count,
          });
        } else {
          console.warn(
            "WeTravel Hydration: Invalid response data for block " + blockId
          );
        }
      })
      .catch((error) => {
        console.error(
          "WeTravel Hydration: Failed to fetch fresh data for block " +
            blockId +
            ":",
          error
        );

        // Trigger error event
        $(document).trigger("wetravel:hydration-error", {
          blockId: blockId,
          error: error.message,
        });
      });
  };

  /**
   * Update container content with smooth transition
   */
  function updateContent(container, newHtml, config) {
    const $container = $(container);

    // Add a subtle fade effect during update
    $container.css("opacity", "0.8");

    // Update the content
    $container.html(newHtml);

    // Always trigger the shared event for both carousel and non-carousel views
    $container.trigger("tripsRendered");

    // Initialize search filters if needed
    if (config.searchVisibility) {
      const containerBlockId =
        container && container.id
          ? container.id.replace("trips-container-", "")
          : null;
      if (containerBlockId) {
        initializeSearchFilters(containerBlockId);
      }
    }

    // Restore opacity with smooth transition
    $container.animate({ opacity: 1 }, 300);

    // Re-initialize WeTravel embed checkout if needed
    if (config.buttonType === "book_now" && window.wtrvl) {
      window.wtrvl.init();
    }
  }

  /**
   * Initialize search filters for hydrated content
   */
  function initializeSearchFilters(blockId) {
    // Re-initialize Select2 if it exists
    if ($.fn.select2) {
      const $locationFilter = $(
        "#search-filter-" + blockId + " .location-filter"
      );
      if ($locationFilter.length) {
        $locationFilter.select2({
          placeholder: "Filter by location...",
          allowClear: true,
          width: "100%",
        });
      }
    }

    // Re-bind search and filter events
    // Note: The search-filter.js script should handle most of this,
    // but we may need to trigger re-initialization events
    $(document).trigger("wetravel:search-filters-init", { blockId: blockId });
  }

  // Auto-hydration based on data attributes
  $(document).ready(function () {
    $('.wetravel-trips-container[data-hydrate="true"]').each(function () {
      const $container = $(this);
      const blockId = this.id.replace("trips-container-", "");

      const config = {
        slug: $container.data("slug"),
        env: $container.data("env"),
        wetravelUserID: $container.data("wetravel-user-id"),
        tripType: $container.data("trip-type"),
        dateStart: $container.data("date-start"),
        dateEnd: $container.data("date-end"),
        locations: $container.data("locations"),
        displayType: $container.data("display-type"),
        buttonType: $container.data("button-type"),
        buttonText: $container.data("button-text"),
        buttonColor: $container.data("button-color"),
        itemsPerPage: parseInt($container.data("items-per-page")) || 10,
        itemsPerRow: parseInt($container.data("items-per-row")) || 3,
        searchVisibility: $container.data("search-visibility") === "true",
      };

      // Delay hydration slightly to allow server-rendered content to be visible first
      setTimeout(function () {
        window.WeTravelTripsHydrate(blockId, config);
      }, 1000);
    });
  });

  // Expose utility functions for external use
  window.WeTravelTripsUtils = {
    updateContent: updateContent,
    initializeSearchFilters: initializeSearchFilters,
  };
})(jQuery);
