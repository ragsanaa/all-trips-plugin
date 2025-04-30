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
    // Force reload trips in any editor
    setTimeout(function () {
      $(".wetravel-trips-container").each(function () {
        var container = $(this);
        // Clear any existing content
        container.find(".wetravel-trips-loading").show();
        // Reload trips
        if (typeof loadTrips === "function") {
          loadTrips(container);
        }
      });
    }, 1000); // Wait for everything to load
  }
});
