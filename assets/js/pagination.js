(function ($) {
  $(document).ready(function () {
    // Initialize server-side pagination for each trips container
    initializeAllPagination();

    // Listen for dynamic trip rendering (from hydration)
    $(document).on("tripsRendered", ".wetravel-trips-container", function () {
      const container = $(this);
      if (container.data("display-type") === "carousel") {
        return;
      }
      initializePaginationForContainer(container);
    });
  });

  function initializeAllPagination() {
    $(".wetravel-trips-container").each(function () {
      initializePaginationForContainer($(this));
    });
  }

  // Expose functions globally for search-filter.js to use
  window.initializePaginationForContainer = initializePaginationForContainer;
  window.initializeServerPagination = initializeServerPagination;
  window.renderServerPaginationControls = renderServerPaginationControls;

  /**
   * Initialize server-side pagination handlers
   * Makes API requests when page changes
   */
  function initializeServerPagination(container, serverPaginationEl, blockId) {
    // Remove any existing handlers to prevent duplicates
    serverPaginationEl.off("click");

    // Handle pagination clicks for server-side pagination
    serverPaginationEl.on("click", ".page-nav, .page-number", function (e) {
      e.preventDefault();

      const $clicked = $(this);
      const targetPage = parseInt($clicked.data("page"));

      // Don't do anything if clicked on current page or invalid page
      if (isNaN(targetPage) || $clicked.hasClass("active")) {
        return;
      }

      // Show loading state
      container.css("opacity", "0.5");

      // Get current configuration from container data attributes
      const config = {
        slug: container.data("slug"),
        env: container.data("env"),
        wetravelUserID: container.data("wetravel-user-id"),
        tripType: container.data("trip-type") || "all",
        dateStart: container.data("date-start") || "",
        dateEnd: container.data("date-end") || "",
        locations: container.data("locations") || "",
        displayType: container.data("display-type") || "vertical",
        buttonType: container.data("button-type") || "book_now",
        buttonText: container.data("button-text") || "",
        buttonColor: container.data("button-color") || "#33ae3f",
        itemsPerPage: parseInt(container.data("items-per-page")) || 10,
        itemsPerRow: parseInt(container.data("items-per-row")) || 3,
        page: targetPage,
      };

      // Build API URL
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
        page: config.page,
        per_page: config.itemsPerPage || 10,
        items_per_row: config.itemsPerRow || 3,
        wetravel_user_id: config.wetravelUserID || "",
      });

      // Fetch new page data
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
            if (data.pagination) {
              renderServerPaginationControls(
                blockId,
                data.pagination,
                container.data("button-color")
              );

              // Re-attach click handlers after regenerating HTML
              const newPaginationElement = $("#pagination-" + blockId);
              if (newPaginationElement.length) {
                initializeServerPagination(
                  container,
                  newPaginationElement,
                  blockId
                );
              }
            }

            // Restore opacity
            container.css("opacity", "1");

            // Scroll to top of trips container
            $("html, body").animate(
              {
                scrollTop: container.offset().top - 100,
              },
              300
            );

            // Trigger custom event
            container.trigger("pageChanged", {
              page: targetPage,
              tripsCount: data.trips_count,
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
          console.error("Failed to fetch page:", error);
          container.css("opacity", "1");
          alert("Failed to load page. Please try again.");
        });
    });
  }

  /**
   * Render server-side pagination controls (matches PHP rendering)
   */
  function renderServerPaginationControls(blockId, pagination, buttonColor) {
    const serverPaginationEl = $("#pagination-" + blockId);
    if (!serverPaginationEl.length) return;

    const totalPages = parseInt(pagination.total_pages);
    const currentPage = parseInt(pagination.page);
    const perPage = parseInt(pagination.per_page);
    const totalCount = parseInt(pagination.total_count);

    // Update data attributes
    serverPaginationEl.attr("data-current-page", currentPage);
    serverPaginationEl.attr("data-total-pages", totalPages);
    serverPaginationEl.attr("data-per-page", perPage);
    serverPaginationEl.attr("data-total-items", totalCount);

    // Clear existing controls
    serverPaginationEl.find(".pagination-controls").empty();
    const controlsContainer = serverPaginationEl.find(".pagination-controls");

    if (totalPages <= 1) {
      serverPaginationEl.hide();
      return;
    }

    serverPaginationEl.show();

    let html = "";

    // Previous button
    if (currentPage > 1) {
      html += `<span class="page-nav page-prev" data-page="${
        currentPage - 1
      }">&laquo;</span>`;
    }

    // Page numbers with ellipsis
    let showPages = [];
    if (totalPages <= 7) {
      showPages = Array.from({ length: totalPages }, (_, i) => i + 1);
    } else {
      showPages.push(1);
      if (currentPage > 3) {
        showPages.push("...");
      }
      for (
        let i = Math.max(2, currentPage - 1);
        i <= Math.min(totalPages - 1, currentPage + 1);
        i++
      ) {
        showPages.push(i);
      }
      if (currentPage < totalPages - 2) {
        showPages.push("...");
      }
      showPages.push(totalPages);
    }

    showPages.forEach((page) => {
      if (page === "...") {
        html += '<span class="page-ellipsis">...</span>';
      } else {
        const activeClass = page === currentPage ? "active" : "";
        html += `<span class="page-number ${activeClass}" data-page="${page}">${page}</span>`;
      }
    });

    // Next button
    if (currentPage < totalPages) {
      html += `<span class="page-nav page-next" data-page="${
        currentPage + 1
      }">&raquo;</span>`;
    }

    controlsContainer.html(html);
  }

  /**
   * Initialize pagination for a container
   * This now only handles server-side pagination
   */
  function initializePaginationForContainer(container) {
    const blockId = container.attr("id").replace("trips-container-", "");
    const displayType = container.data("display-type");

    // Skip carousel layout
    if (displayType === "carousel") {
      return;
    }

    // Check if server-side pagination exists
    const paginationElement = $("#pagination-" + blockId);
    if (
      paginationElement.length > 0 &&
      paginationElement.data("total-pages") !== undefined
    ) {
      // Ensure pagination is visible
      paginationElement.show();

      // Set up server-side pagination click handlers
      initializeServerPagination(container, paginationElement, blockId);
    }
  }
})(jQuery);
