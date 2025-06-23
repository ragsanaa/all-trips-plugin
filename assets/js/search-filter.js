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

    container.find(".trip-item").each(function () {
      const tripItem = $(this);
      const title = tripItem.find("h3").text().toLowerCase();
      const location = tripItem.find(".trip-location").text().toLowerCase();

      const matchesSearch = !searchText || title.includes(searchText);
      //  ||
      // location.includes(searchText);
      const matchesLocation =
        selectedLocs.length === 0 ||
        selectedLocs.some((loc) => location.includes(loc.toLowerCase()));

      tripItem.toggleClass("filtered", !(matchesSearch && matchesLocation));
    });

    updatePagination(blockId);
  }

  // Update pagination after filtering
  function updatePagination(blockId) {
    const container = $(`#trips-container-${blockId}`);
    const paginationContainer = $(`#pagination-${blockId}`);
    const itemsPerPage = parseInt(container.data("items-per-page")) || 10;
    const visibleItems = container.find(".trip-item:not(.filtered)");
    const totalVisibleItems = visibleItems.length;

    // Handle no results
    const noTripsMsg = container.find(".no-trips");
    if (totalVisibleItems === 0) {
      if (noTripsMsg.length === 0) {
        container.append(
          '<div class="no-trips">No trips found matching your criteria</div>'
        );
      }
      noTripsMsg.show();
      paginationContainer.hide();
      return;
    }

    noTripsMsg.hide();

    // Update pagination if needed
    if (totalVisibleItems > itemsPerPage) {
      const totalPages = Math.ceil(totalVisibleItems / itemsPerPage);
      const paginationHTML = Array.from({ length: totalPages }, (_, i) => {
        const pageNum = i + 1;
        return `<span class="page-number ${
          pageNum === 1 ? "active" : ""
        }" data-page="${pageNum}">${pageNum}</span>`;
      }).join("");

      paginationContainer.find(".pagination-controls").html(paginationHTML);
      paginationContainer.show();

      // Show first page items
      visibleItems.each(function (index) {
        $(this)
          .toggleClass("visible-item", index < itemsPerPage)
          .toggleClass("hidden-item", index >= itemsPerPage);
      });
    } else {
      visibleItems.removeClass("hidden-item").addClass("visible-item");
      paginationContainer.hide();
    }
  }

  // Update clear button visibility
  function updateClearButton(blockId) {
    const searchInput = $(`#search-filter-${blockId} .search-input`);
    const clearBtn = $(`#search-filter-${blockId} .search-clear-btn`);
    const hasValue = searchInput.val().trim().length > 0;

    clearBtn.toggle(hasValue);
  }

  // Clear search input
  function clearSearchInput(blockId) {
    const searchInput = $(`#search-filter-${blockId} .search-input`);
    searchInput.val("");
    updateClearButton(blockId);
    filterTrips(blockId);
    searchInput.focus();
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
      $(".dropdown-menu.open").each(function () {
        const container = $(this).closest(".location-dropdown");
        const blockId = container.find(".location-button").data("block-id");

        if (!container[0].contains(event.target)) {
          if (state.isDropdownOpen[blockId]) {
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
