(function ($) {
  "use strict";

  // State management
  const state = {
    selectedLocations: {},
    isDropdownOpen: {},
  };

  // Initialize Select2 for location filter with AJAX search
  // Uses the centralized WeTravelSelect2 utility
  function initializeLocationSelect2(blockId) {
    // Check if required dependencies are available
    if (!wetravelSearchData || typeof window.WeTravelSelect2 === "undefined") {
      return;
    }

    // Use centralized Select2 initialization
    window.WeTravelSelect2.initializeLocationSelect2({
      selector: "#location-filter-" + blockId,
      blockId: blockId,
      globalData: wetravelSearchData,
      allowClear: false,
      dropdownParent: ".filter-dropdown",
      placeholder: "Type to search locations (min 3 characters)...",
      withEventHandlers: true, // Enable frontend-specific event handlers
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

  // Filter trips based on search text, selected locations, and date range
  function filterTrips(blockId) {
    const container = $(`#trips-container-${blockId}`);
    const rawSearch = $(`#search-filter-${blockId} .search-input`).val();
    const searchText = (rawSearch || "").toString().toLowerCase();
    const selectedLocs = state.selectedLocations[blockId] || [];
    const dateStart = $(`#search-filter-${blockId} .date-start-input`).val();
    const dateEnd = $(`#search-filter-${blockId} .date-end-input`).val();

    // First, remove any existing filtered class and show all items
    container.find(".trip-item").removeClass("filtered").show();

    // Apply filters
    container.find(".trip-item").each(function () {
      const tripItem = $(this);
      const rawTitle = tripItem.find("h3").text();
      const rawLocation = tripItem.find(".trip-location").text();
      const title = (rawTitle || "").toString().toLowerCase();
      const location = (rawLocation || "").toString().toLowerCase();

      const matchesSearch =
        !searchText ||
        title.includes(searchText) ||
        location.includes(searchText);
      const matchesLocation =
        selectedLocs.length === 0 ||
        selectedLocs.some((loc) => {
          const normalized = (loc || "").toString().toLowerCase();
          return normalized && location.includes(normalized);
        });

      // Date filtering logic - this is a basic implementation
      // You may need to adjust this based on your specific date format and requirements
      let matchesDate = true;
      if (dateStart || dateEnd) {
        // Extract date from trip item - you'll need to adjust this based on your HTML structure
        const tripDateElement = tripItem.find(".trip-date, .trip-tag");
        if (tripDateElement.length > 0) {
          const tripDateText = (tripDateElement.text() || "").toString().trim();
          // Basic date matching - you may want to implement more sophisticated date parsing
          if (dateStart && tripDateText < dateStart) {
            matchesDate = false;
          }
          if (dateEnd && tripDateText > dateEnd) {
            matchesDate = false;
          }
        }
      }

      if (!(matchesSearch && matchesLocation && matchesDate)) {
        tripItem.addClass("filtered").hide();
      }
    });

    // Handle no results
    const visibleItems = container.find(".trip-item:not(.filtered)");
    const noTripsMsg = container.find(".no-trips");

    if (visibleItems.length === 0) {
      if (noTripsMsg.length === 0) {
        container.append(
          '<div class="no-trips">No trips found matching your criteria</div>'
        );
      }
      noTripsMsg.show();
      // Hide pagination when no results
      $(`#pagination-${blockId}`).hide();
    } else {
      noTripsMsg.hide();
      // Show pagination if it exists and there are visible items
      const paginationContainer = $(`#pagination-${blockId}`);
      if (paginationContainer.length > 0) {
        paginationContainer.show();
      }
    }

    // Trigger a custom event to notify pagination system about the filter change
    container.trigger("tripsFiltered", {
      visibleCount: visibleItems.length,
      totalCount: container.find(".trip-item").length,
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

  // Clear all filters
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

    // Show all items
    container.find(".trip-item").removeClass("filtered").show();
    container.find(".no-trips").hide();

    // Show pagination if it exists
    const paginationContainer = $(`#pagination-${blockId}`);
    if (paginationContainer.length > 0) {
      paginationContainer.show();
    }

    // Update clear button and filter count
    updateClearButton(blockId);
    updateFilterCount(blockId);

    // Trigger filter event
    container.trigger("tripsFiltered", {
      visibleCount: container.find(".trip-item").length,
      totalCount: container.find(".trip-item").length,
    });
  }

  // Event handlers
  $(document).ready(function () {
    // Search input handler
    $(document).on("input", ".search-input", function () {
      const blockId = $(this).data("block-id");
      updateClearButton(blockId);
      filterTrips(blockId);
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

      // Clear date inputs
      $(`#search-filter-${blockId} .date-input`).val("");

      // Clear all other filters
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

      filterTrips(blockId);
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
