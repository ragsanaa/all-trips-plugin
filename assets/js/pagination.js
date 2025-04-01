(function ($) {
  $(document).ready(function () {
    // Initialize pagination for each trips container
    initializeAllPagination();

    // Listen for dynamic trip rendering
    $(document).on("tripsRendered", ".wetravel-trips-container", function () {
      // Skip carousel layout
      const container = $(this);
      if (container.data("display-type") === "carousel") {
        return;
      }

      // Initialize pagination for this container
      initializePaginationForContainer(container);
    });
  });

  function initializeAllPagination() {
    $(".wetravel-trips-container").each(function () {
      initializePaginationForContainer($(this));
    });
  }

  function initializePaginationForContainer(container) {
    const blockId = container.attr("id").replace("trips-container-", "");
    const itemsPerPage = parseInt(container.data("items-per-page")) || 10;
    const displayType = container.data("display-type");
    const buttonColor = container.data("button-color") || "#33ae3f";

    // Skip carousel layout
    if (displayType === "carousel") {
      return;
    }

    // Get all trip items
    const wetravelTrips = container.find(".trip-item");
    const totalItems = wetravelTrips.length;

    // If we don't have enough items for pagination, bail early
    if (totalItems <= itemsPerPage) {
      return;
    }

    // Calculate number of pages
    const totalPages = Math.ceil(totalItems / itemsPerPage);

    // Create pagination element reference
    const paginationElement = $("#pagination-" + blockId);

    // Current page tracker
    let currentPage = 1;

    // Generate pagination HTML
    function renderPagination() {
      paginationElement.empty();

      // Set proper styling for pagination container to match preview
      paginationElement.css({
        display: "flex",
        "justify-content": "center",
        "align-items": "center",
        margin: "20px 0",
        "flex-wrap": "wrap",
      });

      // Don't render pagination if there's only one page
      if (totalPages <= 1) {
        return;
      }

      // Add "First" button
      paginationElement.append(`
          <div class="page-item${
            currentPage === 1 ? " disabled" : ""
          }" style="margin: 0 3px;">
            <a class="page-link" data-page="first" href="#" style="display: inline-block; padding: 8px 12px; color: #333; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; cursor: pointer; transition: all 0.3s ease;">&laquo;</a>
          </div>
        `);

      // Add "Previous" button
      paginationElement.append(`
          <div class="page-item${
            currentPage === 1 ? " disabled" : ""
          }" style="margin: 0 3px;">
            <a class="page-link" data-page="prev" href="#" style="display: inline-block; padding: 8px 12px; color: #333; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; cursor: pointer; transition: all 0.3s ease;">&lsaquo;</a>
          </div>
        `);

      // Maximum visible page numbers (adjust as needed)
      const maxVisiblePages = 5;
      let startPage = Math.max(
        1,
        currentPage - Math.floor(maxVisiblePages / 2)
      );
      let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

      // Adjust start page if we're near the end
      if (endPage - startPage + 1 < maxVisiblePages) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
      }

      // Add first page if needed
      if (startPage > 1) {
        paginationElement.append(`
            <div class="page-item" style="margin: 0 3px;">
              <a class="page-link" data-page="1" href="#" style="display: inline-block; padding: 8px 12px; color: #333; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; cursor: pointer; transition: all 0.3s ease;">1</a>
            </div>
          `);

        // Add ellipsis if needed
        if (startPage > 2) {
          paginationElement.append(`
              <div class="page-item disabled" style="margin: 0 3px;">
                <a class="page-link" href="#" style="display: inline-block; padding: 8px 12px; color: #999; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; pointer-events: none; cursor: default;">...</a>
              </div>
            `);
        }
      }

      // Add page numbers
      for (let i = startPage; i <= endPage; i++) {
        const isActive = i === currentPage;
        const activeStyle = isActive
          ? `background-color: ${buttonColor}; color: white; border-color: ${buttonColor};`
          : "color: #333;";

        paginationElement.append(`
            <div class="page-item${
              isActive ? " active" : ""
            }" style="margin: 0 3px;">
              <a class="page-link" data-page="${i}" href="#" style="display: inline-block; padding: 8px 12px; ${activeStyle} border: 1px solid #ddd; border-radius: 4px; text-decoration: none; cursor: pointer; transition: all 0.3s ease;">${i}</a>
            </div>
          `);
      }

      // Add last page if needed
      if (endPage < totalPages) {
        // Add ellipsis if needed
        if (endPage < totalPages - 1) {
          paginationElement.append(`
              <div class="page-item disabled" style="margin: 0 3px;">
                <a class="page-link" href="#" style="display: inline-block; padding: 8px 12px; color: #999; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; pointer-events: none; cursor: default;">...</a>
              </div>
            `);
        }

        paginationElement.append(`
            <div class="page-item" style="margin: 0 3px;">
              <a class="page-link" data-page="${totalPages}" href="#" style="display: inline-block; padding: 8px 12px; color: #333; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; cursor: pointer; transition: all 0.3s ease;">${totalPages}</a>
            </div>
          `);
      }

      // Add "Next" button
      paginationElement.append(`
          <div class="page-item${
            currentPage === totalPages ? " disabled" : ""
          }" style="margin: 0 3px;">
            <a class="page-link" data-page="next" href="#" style="display: inline-block; padding: 8px 12px; color: #333; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; cursor: pointer; transition: all 0.3s ease;">&rsaquo;</a>
          </div>
        `);

      // Add "Last" button
      paginationElement.append(`
          <div class="page-item${
            currentPage === totalPages ? " disabled" : ""
          }" style="margin: 0 3px;">
            <a class="page-link" data-page="last" href="#" style="display: inline-block; padding: 8px 12px; color: #333; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; cursor: pointer; transition: all 0.3s ease;">&raquo;</a>
          </div>
        `);

      // Update active page style
      paginationElement.find(".page-item.active .page-link").css({
        "background-color": buttonColor,
        color: "white",
        "border-color": buttonColor,
      });

      // Update disabled page style
      paginationElement.find(".page-item.disabled .page-link").css({
        color: "#999",
        "pointer-events": "none",
        cursor: "default",
      });
    }

    // Function to update displayed items
    function displayItems(page) {
      const startIndex = (page - 1) * itemsPerPage;
      const endIndex = startIndex + itemsPerPage;

      // Hide all items first
      wetravelTrips.hide();

      // Show only items for current page
      wetravelTrips.slice(startIndex, endIndex).show();

      // Scroll to top of container if needed
      if (page !== currentPage) {
        $("html, body").animate(
          {
            scrollTop: container.offset().top - 50,
          },
          200
        );
      }

      // Update current page
      currentPage = page;

      // Re-render pagination
      renderPagination();
    }

    // Initial pagination rendering
    renderPagination();

    // Handle pagination clicks
    paginationElement.on("click", ".page-link", function (e) {
      e.preventDefault();

      const pageAction = $(this).data("page");
      let targetPage = currentPage;

      // Handle special page actions
      switch (pageAction) {
        case "first":
          targetPage = 1;
          break;
        case "prev":
          targetPage = Math.max(1, currentPage - 1);
          break;
        case "next":
          targetPage = Math.min(totalPages, currentPage + 1);
          break;
        case "last":
          targetPage = totalPages;
          break;
        default:
          // If it's a number
          if (!isNaN(pageAction)) {
            targetPage = parseInt(pageAction);
          }
      }

      // Only update if page changed
      if (targetPage !== currentPage) {
        displayItems(targetPage);
      }
    });

    // Initial display of items
    displayItems(1);
  }
})(jQuery);
