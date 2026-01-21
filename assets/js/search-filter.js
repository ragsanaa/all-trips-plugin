(function ($) {
  "use strict";

  // State management
  const state = {
    selectedLocations: {},
    isDropdownOpen: {},
    searchDebounceTimers: {},
  };

  // Debounce function to prevent too many API calls while typing
  function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }

  // Initialize Select2 for location filter with AJAX search
  // Uses the centralized WeTravelSelect2 utility
  function initializeLocationSelect2(blockId) {
    // Check if required dependencies are available
    if (!wetravelSearchData || typeof window.WeTravelSelect2 === "undefined") {
      return;
    }

    // Get design-selected locations from container data attribute
    const container = $(`#trips-container-${blockId}`);
    const designLocations = container.data("locations") || "";
    const designLocationsArray = designLocations
      ? designLocations.split(";").filter(Boolean)
      : [];

    // Use centralized Select2 initialization
    window.WeTravelSelect2.initializeLocationSelect2({
      selector: "#location-filter-" + blockId,
      blockId: blockId,
      globalData: wetravelSearchData,
      allowClear: false,
      dropdownParent: ".filter-dropdown",
      placeholder: "Type to search locations (min 3 characters)...",
      withEventHandlers: true, // Enable frontend-specific event handlers
      // Pass design locations as filter to limit destination search results
      getAdditionalFilters: function () {
        if (designLocationsArray.length > 0) {
          return {
            destinations: designLocationsArray.join(";"),
          };
        }
        return {};
      },
      onChange: function ($select, blockId) {
        const selectedValues = $select.val() || [];
        state.selectedLocations[blockId] = selectedValues;

        // Update clear button visibility
        const $clearBtn = $(
          ".location-clear-btn[data-block-id='" + blockId + "']"
        );
        if (selectedValues.length > 0) {
          $clearBtn.show();
        } else {
          $clearBtn.hide();
        }

        // Don't filter automatically - wait for Apply button
        updateClearButton(blockId);
      },
    });
  }

  // Reload original data without clearing filter inputs
  // Used when search is cleared or no filters are active
  function reloadOriginalData(blockId) {
    const container = $(`#trips-container-${blockId}`);

    // Show loading state
    container.css("opacity", "0.5");

    // Get original configuration from container data attributes (without any user filters)
    const config = {
      slug: container.data("slug"),
      env: container.data("env"),
      wetravelUserID: container.data("wetravel-user-id"),
      tripType: container.data("trip-type") || "",
      dateStart: container.data("date-start") || "",
      dateEnd: container.data("date-end") || "",
      locations: container.data("locations") || "",
      displayType: container.data("display-type") || "vertical",
      buttonType: container.data("button-type") || "book_now",
      buttonText: container.data("button-text") || "",
      buttonColor: container.data("button-color") || "#33ae3f",
      itemsPerPage: parseInt(container.data("items-per-page")) || 10,
      itemsPerRow: parseInt(container.data("items-per-row")) || 3,
      page: 1,
    };

    // Build API URL to reload original data
    const apiUrl = "/wp-json/wetravel/v1/trips";
    const params = new URLSearchParams({
      block_id: blockId,
      slug: config.slug || "",
      env: config.env || "",
      trip_type: config.tripType || "",
      date_start: config.dateStart || "",
      date_end: config.dateEnd || "",
      locations: config.locations || "",
      query: "",
      display_type: config.displayType || "vertical",
      button_type: config.buttonType || "book_now",
      button_text: config.buttonText || "",
      button_color: config.buttonColor || "#33ae3f",
      page: config.page,
      per_page: config.itemsPerPage || 10,
      items_per_row: config.itemsPerRow || 3,
      wetravel_user_id: config.wetravelUserID || "",
    });

    // Fetch original data from API
    fetch(apiUrl + "?" + params.toString())
      .then((response) => {
        if (!response.ok) {
          throw new Error("HTTP " + response.status);
        }
        return response.json();
      })
      .then((data) => {
        if (data.success && data.html) {
          container.html(data.html);

          if (data.pagination) {
            const paginationContainer = $(`#pagination-${blockId}`);
            if (paginationContainer.length > 0) {
              paginationContainer.show();
            }

            if (window.renderServerPaginationControls) {
              window.renderServerPaginationControls(
                blockId,
                data.pagination,
                container.data("button-color")
              );

              const newPaginationElement = $("#pagination-" + blockId);
              if (
                newPaginationElement.length &&
                window.initializeServerPagination
              ) {
                window.initializeServerPagination(
                  container,
                  newPaginationElement,
                  blockId
                );
              }
            }
          }

          container.css("opacity", "1");

          container.trigger("tripsFiltered", {
            visibleCount: data.trips_count || 0,
            totalCount: data.pagination ? data.pagination.total_count : 0,
          });

          // Re-initialize WeTravel checkout buttons if needed
          // Use setTimeout to ensure DOM is fully updated before re-initializing
          setTimeout(function () {
            if (window.wtrvl && window.wtrvl.init) {
              window.wtrvl.init();
            }
            // Trigger a custom event that the embed_checkout script might listen to
            document.dispatchEvent(new Event("DOMContentLoaded"));
          }, 100);
        }
      })
      .catch((error) => {
        console.error("Failed to reload trips:", error);
        container.css("opacity", "1");
      });
  }

  // Filter trips based on search text (title only), selected locations, and date range
  // Makes API request to search across all trips (not just current page)
  function filterTrips(blockId) {
    const container = $(`#trips-container-${blockId}`);
    const rawSearch = $(`#search-filter-${blockId} .search-input`).val();
    const searchText = (rawSearch || "").toString().trim();
    const selectedLocs = state.selectedLocations[blockId] || [];
    const dateStart = $(`#search-filter-${blockId} .date-start-input`).val();
    const dateEnd = $(`#search-filter-${blockId} .date-end-input`).val();

    // Validate search text - must be at least 3 characters or empty
    const hasValidSearch = searchText.length === 0 || searchText.length >= 3;
    const effectiveSearch =
      hasValidSearch && searchText.length >= 3 ? searchText : "";

    // Check if any filters are active
    const hasFilters =
      effectiveSearch || selectedLocs.length > 0 || dateStart || dateEnd;

    // If no filters are active, reload original data from API
    if (!hasFilters) {
      // Clear the search input if it's less than 3 characters
      if (searchText.length > 0 && searchText.length < 3) {
        return; // Don't do anything until user types at least 3 characters
      }

      // Reload original data by calling clearAllFilters without clearing inputs
      reloadOriginalData(blockId);
      return;
    }

    // Show loading state
    container.css("opacity", "0.5");

    // Get current configuration from container data attributes
    const config = {
      slug: container.data("slug"),
      env: container.data("env"),
      wetravelUserID: container.data("wetravel-user-id"),
      tripType: container.data("trip-type") || "",
      dateStart: dateStart || container.data("date-start") || "",
      dateEnd: dateEnd || container.data("date-end") || "",
      locations:
        selectedLocs.length > 0
          ? selectedLocs.join(";")
          : container.data("locations") || "",
      displayType: container.data("display-type") || "vertical",
      buttonType: container.data("button-type") || "book_now",
      buttonText: container.data("button-text") || "",
      buttonColor: container.data("button-color") || "#33ae3f",
      itemsPerPage: parseInt(container.data("items-per-page")) || 10,
      itemsPerRow: parseInt(container.data("items-per-row")) || 3,
      query: effectiveSearch, // Use validated search text (min 3 chars or empty)
      page: 1, // Reset to page 1 when filtering
    };

    // Build API URL
    const apiUrl = "/wp-json/wetravel/v1/trips";
    const params = new URLSearchParams({
      block_id: blockId,
      slug: config.slug || "",
      env: config.env || "",
      trip_type: config.tripType || "",
      date_start: config.dateStart || "",
      date_end: config.dateEnd || "",
      locations: config.locations || "",
      query: config.query || "",
      display_type: config.displayType || "vertical",
      button_type: config.buttonType || "book_now",
      button_text: config.buttonText || "",
      button_color: config.buttonColor || "#33ae3f",
      page: config.page,
      per_page: config.itemsPerPage || 10,
      items_per_row: config.itemsPerRow || 3,
      wetravel_user_id: config.wetravelUserID || "",
    });

    // Fetch filtered data from API
    fetch(apiUrl + "?" + params.toString())
      .then((response) => {
        if (!response.ok) {
          throw new Error("HTTP " + response.status);
        }
        return response.json();
      })
      .then((data) => {
        if (data.success && data.html) {
          // Update trips container with new HTML
          container.html(data.html);

          // Update pagination controls if included in response
          if (data.pagination && window.renderServerPaginationControls) {
            window.renderServerPaginationControls(
              blockId,
              data.pagination,
              container.data("button-color")
            );

            // Re-attach click handlers after regenerating HTML
            const newPaginationElement = $("#pagination-" + blockId);
            if (
              newPaginationElement.length &&
              window.initializeServerPagination
            ) {
              window.initializeServerPagination(
                container,
                newPaginationElement,
                blockId
              );
            }
          } else if (!data.trips_count || data.trips_count === 0) {
            // Hide pagination if no results
            $(`#pagination-${blockId}`).hide();
          }

          // Restore opacity
          container.css("opacity", "1");

          // Trigger custom event
          container.trigger("tripsFiltered", {
            visibleCount: data.trips_count || 0,
            totalCount: data.pagination ? data.pagination.total_count : 0,
          });

          // Re-initialize WeTravel checkout buttons if needed
          // Use setTimeout to ensure DOM is fully updated before re-initializing
          setTimeout(function () {
            if (window.wtrvl && window.wtrvl.init) {
              window.wtrvl.init();
            }
            // Trigger a custom event that the embed_checkout script might listen to
            document.dispatchEvent(new Event("DOMContentLoaded"));
          }, 100);
        }
      })
      .catch((error) => {
        console.error("Failed to filter trips:", error);
        container.css("opacity", "1");
        // Show error message
        container.html(
          '<div class="no-trips">Failed to load trips. Please try again.</div>'
        );
        $(`#pagination-${blockId}`).hide();
      });
  }

  // Update clear button visibility
  function updateClearButton(blockId) {
    const searchInput = $(`#search-filter-${blockId} .search-input`);
    const clearBtn = $(`#search-filter-${blockId} .search-clear-btn`);
    const clearAllBtn = $(`#search-filter-${blockId} .clear-all-filters`);
    const dateStartInput = $(`#search-filter-${blockId} .date-start-input`);
    const dateEndInput = $(`#search-filter-${blockId} .date-end-input`);

    const searchVal = (searchInput.length ? searchInput.val() : "").toString();
    const hasValue = searchVal.trim().length > 0;
    const hasLocationFilters =
      (state.selectedLocations[blockId] || []).length > 0;
    const hasDateFilters =
      (dateStartInput.length &&
        (dateStartInput.val() || "").toString().trim().length > 0) ||
      (dateEndInput.length &&
        (dateEndInput.val() || "").toString().trim().length > 0);
    const hasAnyFilters = hasValue || hasLocationFilters || hasDateFilters;

    clearBtn.toggle(hasValue);
    clearAllBtn.toggle(hasAnyFilters);

    // Update filter count badge
    updateFilterCount(blockId);
  }

  // Update filter count badge
  function updateFilterCount(blockId) {
    const filterBadge = $(
      `.filter-button[data-block-id="${blockId}"] .filter-count-badge`
    );
    const dateStartInput = $(`#search-filter-${blockId} .date-start-input`);
    const dateEndInput = $(`#search-filter-${blockId} .date-end-input`);

    let filterCount = 0;

    // Count location filters
    const hasLocationFilters =
      (state.selectedLocations[blockId] || []).length > 0;
    if (hasLocationFilters) {
      filterCount++;
    }

    // Count date range filters (count as one if either start or end date is set)
    const hasDateFilters =
      (dateStartInput.length &&
        (dateStartInput.val() || "").toString().trim().length > 0) ||
      (dateEndInput.length &&
        (dateEndInput.val() || "").toString().trim().length > 0);
    if (hasDateFilters) {
      filterCount++;
    }

    // Update badge
    if (filterCount > 0) {
      filterBadge.text(filterCount).show();
    } else {
      filterBadge.hide();
    }
  }

  // Clear search input
  function clearSearchInput(blockId) {
    const searchInput = $(`#search-filter-${blockId} .search-input`);
    searchInput.val("");
    updateClearButton(blockId);
    filterTrips(blockId);
    searchInput.focus();
  }

  // Clear all filters and reload original data
  function clearAllFilters(blockId) {
    const container = $(`#trips-container-${blockId}`);

    // Clear search input
    $(`#search-filter-${blockId} .search-input`).val("");

    // Clear date inputs
    $(`#search-filter-${blockId} .date-start-input`).val("");
    $(`#search-filter-${blockId} .date-end-input`).val("");

    // Clear location selections
    state.selectedLocations[blockId] = [];
    const $locationSelect = $("#location-filter-" + blockId);
    if (
      $locationSelect.length &&
      $locationSelect.hasClass("select2-hidden-accessible")
    ) {
      $locationSelect.val(null).trigger("change");
    }

    // Update clear button and filter count
    updateClearButton(blockId);
    updateFilterCount(blockId);

    // Show loading state
    container.css("opacity", "0.5");

    // Get original configuration from container data attributes (without any filters)
    const config = {
      slug: container.data("slug"),
      env: container.data("env"),
      wetravelUserID: container.data("wetravel-user-id"),
      tripType: container.data("trip-type") || "",
      dateStart: container.data("date-start") || "",
      dateEnd: container.data("date-end") || "",
      locations: container.data("locations") || "",
      displayType: container.data("display-type") || "vertical",
      buttonType: container.data("button-type") || "book_now",
      buttonText: container.data("button-text") || "",
      buttonColor: container.data("button-color") || "#33ae3f",
      itemsPerPage: parseInt(container.data("items-per-page")) || 10,
      itemsPerRow: parseInt(container.data("items-per-row")) || 3,
      page: 1, // Reset to page 1
    };

    // Build API URL to reload original data
    const apiUrl = "/wp-json/wetravel/v1/trips";
    const params = new URLSearchParams({
      block_id: blockId,
      slug: config.slug || "",
      env: config.env || "",
      trip_type: config.tripType || "",
      date_start: config.dateStart || "",
      date_end: config.dateEnd || "",
      locations: config.locations || "",
      query: "", // No search query
      display_type: config.displayType || "vertical",
      button_type: config.buttonType || "book_now",
      button_text: config.buttonText || "",
      button_color: config.buttonColor || "#33ae3f",
      page: config.page,
      per_page: config.itemsPerPage || 10,
      items_per_row: config.itemsPerRow || 3,
      wetravel_user_id: config.wetravelUserID || "",
    });

    // Fetch original data from API
    fetch(apiUrl + "?" + params.toString())
      .then((response) => {
        if (!response.ok) {
          throw new Error("HTTP " + response.status);
        }
        return response.json();
      })
      .then((data) => {
        if (data.success && data.html) {
          // Update trips container with original HTML
          container.html(data.html);

          // Update pagination controls if included in response
          if (data.pagination) {
            const paginationContainer = $(`#pagination-${blockId}`);
            if (paginationContainer.length > 0) {
              paginationContainer.show();
            }

            if (window.renderServerPaginationControls) {
              window.renderServerPaginationControls(
                blockId,
                data.pagination,
                container.data("button-color")
              );

              // Re-attach click handlers after regenerating HTML
              const newPaginationElement = $("#pagination-" + blockId);
              if (
                newPaginationElement.length &&
                window.initializeServerPagination
              ) {
                window.initializeServerPagination(
                  container,
                  newPaginationElement,
                  blockId
                );
              }
            }
          }

          // Restore opacity
          container.css("opacity", "1");

          // Trigger custom event
          container.trigger("tripsFiltered", {
            visibleCount: data.trips_count || 0,
            totalCount: data.pagination ? data.pagination.total_count : 0,
          });

          // Re-initialize WeTravel checkout buttons if needed
          // Use setTimeout to ensure DOM is fully updated before re-initializing
          setTimeout(function () {
            if (window.wtrvl && window.wtrvl.init) {
              window.wtrvl.init();
            }
            // Trigger a custom event that the embed_checkout script might listen to
            document.dispatchEvent(new Event("DOMContentLoaded"));
          }, 100);
        }
      })
      .catch((error) => {
        console.error("Failed to reload trips:", error);
        container.css("opacity", "1");
        alert("Failed to reload trips. Please try again.");
      });
  }

  // Event handlers
  $(document).ready(function () {
    // Search input handler with debouncing (wait 500ms after user stops typing)
    $(document).on("input", ".search-input", function () {
      const blockId = $(this).data("block-id");
      const searchValue = $(this).val().trim();

      updateClearButton(blockId);

      // Clear existing timer
      if (state.searchDebounceTimers[blockId]) {
        clearTimeout(state.searchDebounceTimers[blockId]);
      }

      // Early return: Don't trigger search for inputs with 1-2 characters
      // Only search when empty (to reset) or >= 3 characters
      if (searchValue.length > 0 && searchValue.length < 3) {
        return;
      }

      // Debounce the API call to avoid too many requests while typing
      state.searchDebounceTimers[blockId] = setTimeout(function () {
        filterTrips(blockId);
      }, 500); // Wait 500ms after user stops typing
    });

    // Clear button handler
    $(document).on("click", ".search-clear-btn", function (e) {
      e.preventDefault();
      e.stopPropagation();
      const blockId = $(this).data("block-id");
      clearSearchInput(blockId);
    });

    // Clear all filters button handler
    $(document).on("click", ".clear-all-filters", function (e) {
      e.preventDefault();
      e.stopPropagation();
      const blockId = $(this).data("block-id");
      clearAllFilters(blockId);
    });

    // Filter button handler - toggle filter dropdown
    $(document).on("click", ".filter-button", function (e) {
      e.preventDefault();
      e.stopPropagation();
      const blockId = $(this).data("block-id");
      const filterDropdown = $(`#search-filter-${blockId} .filter-dropdown`);

      if (filterDropdown.length) {
        const isVisible = filterDropdown.is(":visible");
        filterDropdown.toggle(!isVisible);
      }
    });

    // Filter close button handler
    $(document).on("click", ".filter-close-btn", function (e) {
      e.preventDefault();
      e.stopPropagation();
      const blockId = $(this).data("block-id");
      $(`#search-filter-${blockId} .filter-dropdown`).hide();
    });

    // Location clear button handler
    $(document).on("click", ".location-clear-btn", function (e) {
      e.preventDefault();
      e.stopPropagation();
      const blockId = $(this).data("block-id");

      // Clear location selections
      state.selectedLocations[blockId] = [];
      const $locationSelect = $("#location-filter-" + blockId);
      if (
        $locationSelect.length &&
        $locationSelect.hasClass("select2-hidden-accessible")
      ) {
        $locationSelect.val(null).trigger("change");
      }

      // Hide the clear button
      $(this).hide();

      // Don't auto-apply - let user click Apply button to apply filters
      updateClearButton(blockId);
    });

    // Date range filter handlers
    $(document).on("change", ".date-input", function () {
      const blockId = $(this).data("block-id");
      // You can add date filtering logic here if needed
      // For now, we'll just update the clear button state and filter count
      updateClearButton(blockId);
      updateFilterCount(blockId);
    });

    // Reset button handler
    $(document).on("click", ".reset-btn", function (e) {
      e.preventDefault();
      e.stopPropagation();
      const blockId = $(this)
        .closest(".wetravel-trips-search-filter")
        .attr("id")
        .replace("search-filter-", "");

      // Hide the filter dropdown
      $(`#search-filter-${blockId} .filter-dropdown`).hide();

      // Clear all filters and reload original data
      clearAllFilters(blockId);
    });

    // Apply button handler
    $(document).on("click", ".apply-btn", function (e) {
      e.preventDefault();
      e.stopPropagation();
      const blockId = $(this)
        .closest(".wetravel-trips-search-filter")
        .attr("id")
        .replace("search-filter-", "");

      // Hide the filter dropdown
      $(`#search-filter-${blockId} .filter-dropdown`).hide();

      // Update filter count badge
      updateFilterCount(blockId);

      // Apply filters (you can add date range filtering logic here)
      filterTrips(blockId);
    });

    // Close filter dropdown when clicking outside
    $(document).on("click", function (event) {
      $(".wetravel-trips-search-filter").each(function () {
        const searchFilter = $(this);
        const blockId = searchFilter.attr("id").replace("search-filter-", "");
        const filterDropdown = searchFilter.find(".filter-dropdown");
        const filterButton = searchFilter.find(".filter-button");

        if (
          filterDropdown.is(":visible") &&
          !filterDropdown[0].contains(event.target) &&
          !filterButton[0].contains(event.target)
        ) {
          filterDropdown.hide();
        }
      });
    });

    // Prevent filter dropdown from closing when clicking inside
    $(document).on("click", ".filter-dropdown", function (e) {
      e.stopPropagation();
    });

    // Initialize filters when trips are loaded
    $(document).on("tripsRendered", ".wetravel-trips-container", function () {
      const blockId = $(this).attr("id").replace("trips-container-", "");

      // Initialize location Select2 for this block
      initializeLocationSelect2(blockId);

      // Only update UI states, don't reload trips (they're already loaded)
      updateClearButton(blockId);
      updateFilterCount(blockId);
    });

    // Initialize clear button state and location Select2 on page load
    $(".search-input").each(function () {
      const blockId = $(this).data("block-id");
      if (blockId) {
        updateClearButton(blockId);
        updateFilterCount(blockId);
      }
    });

    // Initialize location Select2 for all visible location filters
    $(".location-filter-select").each(function () {
      const blockId = $(this).data("block-id");
      if (blockId) {
        initializeLocationSelect2(blockId);
      }
    });
  });
})(jQuery);
