document.addEventListener("DOMContentLoaded", function () {
  // Find all trip containers on the page (to support multiple blocks)
  const tripsContainers = document.querySelectorAll("[id^='trips-container-']");

  if (!tripsContainers.length) {
    return;
  }

  // Process each container
  tripsContainers.forEach(function(tripsContainer) {
    // Get the container ID to fetch the corresponding settings and button
    const containerId = tripsContainer.id;
    const blockId = containerId.replace("trips-container-", "");
    const loadMoreButton = document.getElementById(`load-more-button-${blockId}`);

    // Skip if button doesn't exist
    if (!loadMoreButton) {
      return;
    }

    // Get settings from data attributes on the container
    const itemsPerPage = parseInt(tripsContainer.dataset.itemsPerPage) || 10;
    const loadMoreText = tripsContainer.dataset.loadMoreText || "Load More";
    const displayType = tripsContainer.dataset.displayType || "vertical";

    // Set button text
    loadMoreButton.textContent = loadMoreText;

    // Apply display styles based on display type
    if (displayType === "grid") {
      tripsContainer.classList.add("grid-view");
    } else {
      tripsContainer.classList.add("vertical-view");
    }

    const tripItems = tripsContainer.querySelectorAll(".trip-item");
    let currentPage = 1;

    function loadTrips() {
      // Calculate start and end indices for the current page
      const startIndex = 0;
      const endIndex = currentPage * itemsPerPage;

      // Hide all items first
      tripItems.forEach((trip, index) => {
        trip.style.display = index < endIndex ? "block" : "none";
      });

      // Hide load more button if all items are visible
      if (endIndex >= tripItems.length) {
        loadMoreButton.style.display = "none";
      } else {
        loadMoreButton.style.display = "block";
      }
    }

    // Add click event to load more button
    loadMoreButton.addEventListener("click", function () {
      currentPage++;
      loadTrips();
    });

    // Initialize first page
    loadTrips();
  });
});
