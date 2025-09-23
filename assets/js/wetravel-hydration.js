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

          console.log(
            "WeTravel Hydration: Successfully updated block " +
              blockId +
              " with " +
              data.trips_count +
              " trips"
          );
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

    // Re-initialize any scripts based on display type
    if (config.displayType === "carousel") {
      initializeCarousel(container.id);
    } else {
      initializePagination(container.id);
    }

    // Initialize search filters if needed
    if (config.searchVisibility) {
      initializeSearchFilters(config.blockId);
    }

    // Apply description fades if function is available
    if (window.applyDescriptionFades) {
      window.applyDescriptionFades();
    }

    // Restore opacity with smooth transition
    $container.animate({ opacity: 1 }, 300);

    // Re-initialize WeTravel embed checkout if needed
    if (config.buttonType === "book_now" && window.wtrvl) {
      window.wtrvl.init();
    }
  }

  /**
   * Initialize carousel for hydrated content
   */
  function initializeCarousel(containerId) {
    const $container = $("#" + containerId);
    const $swiper = $container.find(".swiper");

    if ($swiper.length && window.Swiper) {
      // Destroy existing swiper instance if any
      if ($swiper[0].swiper) {
        $swiper[0].swiper.destroy(true, true);
      }

      // Initialize new swiper
      new window.Swiper($swiper[0], {
        slidesPerView: 1,
        spaceBetween: 20,
        navigation: {
          nextEl: $container.find(".swiper-button-next")[0],
          prevEl: $container.find(".swiper-button-prev")[0],
        },
        pagination: {
          el: $container.find(".swiper-pagination")[0],
          clickable: true,
        },
        breakpoints: {
          768: {
            slidesPerView: 2,
          },
          1024: {
            slidesPerView: 3,
          },
        },
      });

      // Apply description fades for carousel items
      if (window.applyDescriptionFades) {
        window.applyDescriptionFades();
      }
    }
  }

  /**
   * Initialize pagination for hydrated content
   */
  function initializePagination(containerId) {
    const $container = $("#" + containerId);
    const blockId = containerId.replace("trips-container-", "");

    // Re-bind pagination events
    $("#pagination-" + blockId + " .page-number")
      .off("click")
      .on("click", function () {
        const page = parseInt($(this).data("page"));
        const itemsPerPage = parseInt($container.data("items-per-page")) || 10;

        // Hide all items
        $container
          .find(".trip-item")
          .addClass("hidden-item")
          .removeClass("visible-item");

        // Show items for current page
        const startIndex = (page - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;

        $container
          .find(".trip-item")
          .slice(startIndex, endIndex)
          .removeClass("hidden-item")
          .addClass("visible-item");

        // Update pagination active state
        $("#pagination-" + blockId + " .page-number").removeClass("active");
        $(this).addClass("active");

        // Apply description fades after pagination change
        if (window.applyDescriptionFades) {
          window.applyDescriptionFades();
        }

        // Scroll to top of trips container
        $("html, body").animate(
          {
            scrollTop: $container.offset().top - 100,
          },
          500
        );
      });
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
    initializeCarousel: initializeCarousel,
    initializePagination: initializePagination,
    initializeSearchFilters: initializeSearchFilters,
  };
})(jQuery);
