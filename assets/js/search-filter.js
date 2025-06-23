(function ($) {
  "use strict";

  // State management
  const state = {
    selectedLocations: {},
    isDropdownOpen: {},
  };

  // Toggle dropdown visibility
  function toggleDropdown(blockId) {
    const dropdown = $(`#search-filter-${blockId} .dropdown-menu`);
    const arrow = $(`#search-filter-${blockId} .dropdown-arrow`);

    state.isDropdownOpen[blockId] = !state.isDropdownOpen[blockId];

    dropdown.toggleClass("open", state.isDropdownOpen[blockId]);
    arrow.toggleClass("open", state.isDropdownOpen[blockId]);
  }

  // Toggle location selection
  function toggleLocation(element, location, blockId) {
    if (!state.selectedLocations[blockId]) {
      state.selectedLocations[blockId] = [];
    }

    const checkmark = $(element).find(".checkmark");
    const isSelected = state.selectedLocations[blockId].includes(location);

    if (isSelected) {
      state.selectedLocations[blockId] = state.selectedLocations[
        blockId
      ].filter((loc) => loc !== location);
      checkmark.removeClass("checked").html("");
      $(element).removeClass("selected");
    } else {
      state.selectedLocations[blockId].push(location);
      checkmark.addClass("checked").html("✓");
      $(element).addClass("selected");
    }

    updateSelectedText(blockId);
    filterTrips(blockId);
  }

  // Update selected locations text
  function updateSelectedText(blockId) {
    const selectedText = $(`#search-filter-${blockId} #selected-text`);
    const selectedCount = $(`#search-filter-${blockId} #selected-count`);
    const locations = state.selectedLocations[blockId] || [];

    if (locations.length === 0) {
      selectedText.text("Select locations");
      selectedCount.hide();
    } else if (locations.length === 1) {
      selectedText.text(locations[0]);
      selectedCount.hide();
    } else {
      selectedText.text("Multiple locations");
      selectedCount.text(locations.length + " selected").show();
    }

    // Update clear button visibility
    updateClearButton(blockId);
  }

  // Filter locations in dropdown
  function filterLocations(blockId) {
    const searchTerm = $(`#search-filter-${blockId} #location-search`)
      .val()
      .toLowerCase();

    $(`#search-filter-${blockId} .location-item`).each(function () {
      const locationName = $(this).find(".location-name").text().toLowerCase();
      $(this).toggle(locationName.includes(searchTerm));
    });
  }

  // Filter trips based on search text and selected locations
  function filterTrips(blockId) {
    const container = $(`#trips-container-${blockId}`);
    const searchText = $(`#search-filter-${blockId} .search-input`)
      .val()
      .toLowerCase();
    const selectedLocs = state.selectedLocations[blockId] || [];

    // First, remove any existing filtered class and show all items
    container.find(".trip-item").removeClass("filtered").show();

    // Apply filters
    container.find(".trip-item").each(function () {
      const tripItem = $(this);
      const title = tripItem.find("h3").text().toLowerCase();
      const location = tripItem.find(".trip-location").text().toLowerCase();

      const matchesSearch = !searchText || title.includes(searchText);
      const matchesLocation =
        selectedLocs.length === 0 ||
        selectedLocs.some((loc) => location.includes(loc.toLowerCase()));

      if (!(matchesSearch && matchesLocation)) {
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
    const hasValue = searchInput.val().trim().length > 0;
    const hasLocationFilters =
      (state.selectedLocations[blockId] || []).length > 0;
    const hasAnyFilters = hasValue || hasLocationFilters;

    clearBtn.toggle(hasValue);
    clearAllBtn.toggle(hasAnyFilters);
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

    // Clear location selections
    state.selectedLocations[blockId] = [];
    $(`#search-filter-${blockId} .location-item .checkmark`)
      .removeClass("checked")
      .html("");
    $(`#search-filter-${blockId} .location-item`).removeClass("selected");

    // Reset dropdown text
    updateSelectedText(blockId);

    // Show all items
    container.find(".trip-item").removeClass("filtered").show();
    container.find(".no-trips").hide();

    // Show pagination if it exists
    const paginationContainer = $(`#pagination-${blockId}`);
    if (paginationContainer.length > 0) {
      paginationContainer.show();
    }

    // Update clear button
    updateClearButton(blockId);

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

    // Location button handler
    $(document).on("click", ".location-button", function (e) {
      e.preventDefault();
      e.stopPropagation();
      toggleDropdown($(this).data("block-id"));
    });

    // Location item handler
    $(document).on("click", ".location-item", function (e) {
      e.preventDefault();
      e.stopPropagation();
      const blockId = $(this).data("block-id");
      toggleLocation(this, $(this).data("location"), blockId);
    });

    // Location search handler
    $(document).on("input", "#location-search", function () {
      filterLocations($(this).data("block-id"));
    });

    // Close dropdown when clicking outside
    $(document).on("click", function (event) {
      // Check all open dropdowns
      Object.keys(state.isDropdownOpen).forEach(function (blockId) {
        if (state.isDropdownOpen[blockId]) {
          const dropdownContainer = $(
            `#search-filter-${blockId} .location-dropdown`
          );
          const locationButton = $(
            `#search-filter-${blockId} .location-button`
          );

          // Check if the click target is outside the dropdown container and not on the location button
          if (
            dropdownContainer.length &&
            !dropdownContainer[0].contains(event.target) &&
            !locationButton[0].contains(event.target)
          ) {
            toggleDropdown(blockId);
          }
        }
      });
    });

    // Prevent dropdown from closing when clicking inside
    $(document).on("click", ".dropdown-menu", function (e) {
      e.stopPropagation();
    });

    // Initialize filters when trips are loaded
    $(document).on("tripsRendered", ".wetravel-trips-container", function () {
      const blockId = $(this).attr("id").replace("trips-container-", "");
      filterTrips(blockId);
      updateClearButton(blockId);
    });

    // Initialize clear button state on page load
    $(document).ready(function () {
      $(".search-input").each(function () {
        const blockId = $(this).data("block-id");
        if (blockId) {
          updateClearButton(blockId);
        }
      });
    });
  });
})(jQuery);
