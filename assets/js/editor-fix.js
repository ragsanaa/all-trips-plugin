/**
 * WeTravel Editor Fix Script
 *
 * Handles editor environment detection and ensures widgets work properly
 * in various editor contexts (Gutenberg, page builders, etc.)
 */

jQuery(document).ready(function ($) {
  // Check if we're in any editing environment
  var isEditMode =
    // Generic ways to detect edit mode across different page builders
    (window.parent && window.parent !== window) ||
    (window.frames && window.frames.length > 0) ||
    document.body.classList.contains("editor-body") ||
    document.body.classList.contains("wp-admin") ||
    document.body.classList.contains("edit-php") ||
    (window.location.href && window.location.href.indexOf("action=edit") > -1);

  if (isEditMode) {
    // In editor mode, force hydration for better preview
    setTimeout(function () {
      $(".wetravel-trips-container[data-hydrate='true']").each(function () {
        var container = $(this);
        var blockId = this.id.replace("trips-container-", "");

        // Show loading state
        container.find(".wetravel-trips-loading").show();

        // Extract config from data attributes
        var config = {
          slug: container.data("slug"),
          env: container.data("env"),
          wetravelUserID: container.data("wetravel-user-id"),
          tripType: container.data("trip-type"),
          dateStart: container.data("date-start"),
          dateEnd: container.data("date-end"),
          locations: container.data("locations"),
          displayType: container.data("display-type"),
          buttonType: container.data("button-type"),
          buttonText: container.data("button-text"),
          buttonColor: container.data("button-color"),
          itemsPerPage: parseInt(container.data("items-per-page")) || 10,
          itemsPerRow: parseInt(container.data("items-per-row")) || 3,
          searchVisibility: container.data("search-visibility") === "true",
        };

        // Force hydration for editor preview
        if (window.WeTravelTripsHydrate) {
          window.WeTravelTripsHydrate(blockId, config);
        } else {
          // If hydration script not loaded yet, wait and try again
          setTimeout(function () {
            if (window.WeTravelTripsHydrate) {
              window.WeTravelTripsHydrate(blockId, config);
            }
          }, 500);
        }
      });
    }, 1000); // Wait for everything to load
  }
});
