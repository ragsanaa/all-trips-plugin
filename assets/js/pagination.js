document.addEventListener("DOMContentLoaded", function () {
  const tripsContainer = document.getElementById("trips-container");
  const loadMoreButton = document.getElementById("load-more-button");

  // If using carousel view or button doesn't exist, skip pagination
  if (!loadMoreButton || !tripsContainer) {
    return;
  }

  // Get settings from localized object
  const settings = window.allTripsSettings || {
    itemsPerPage: 10,
    displayType: "vertical",
    loadMoreText: "Load More",
  };

  const tripItems = tripsContainer.querySelectorAll(".trip-item");
  let currentPage = 1;
  const itemsPerPage = parseInt(settings.itemsPerPage) || 10;

  // Set button text
  if (loadMoreButton && settings.loadMoreText) {
    loadMoreButton.textContent = settings.loadMoreText;
  }

  // Apply display styles
  if (settings.displayType === "grid") {
    tripsContainer.classList.add("grid-view");
  } else {
    tripsContainer.classList.add("vertical-view");
  }

  function loadTrips() {
    const end = currentPage * itemsPerPage;

    // Show items up to the current page limit
    tripItems.forEach((trip, index) => {
      if (index < end) {
        trip.style.display = "block";
      } else {
        trip.style.display = "none";
      }
    });

    // Hide load more button if all items are visible
    if (end >= tripItems.length) {
      loadMoreButton.style.display = "none";
    } else {
      loadMoreButton.style.display = "block";
    }
  }

  if (loadMoreButton) {
    loadMoreButton.addEventListener("click", function () {
      currentPage++;
      loadTrips();
    });
  }

  // Initialize first page
  loadTrips();
});
