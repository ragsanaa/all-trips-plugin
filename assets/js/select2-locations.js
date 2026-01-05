/**
 * Centralized Select2 Location Initialization
 *
 * This utility provides a shared function to initialize Select2 with AJAX
 * for location/destination searches, used in both frontend and admin contexts.
 */

(function ($) {
  "use strict";

  /**
   * Initialize Select2 for location/destination selection with AJAX search
   *
   * @param {Object} options - Configuration options
   * @param {string} options.selector - jQuery selector for the select element
   * @param {string} options.blockId - Optional block ID (for frontend multi-instance support)
   * @param {Object} options.globalData - Global data object (wetravelSearchData or wetravel_ajax)
   * @param {boolean} options.allowClear - Whether to show clear button (default: true)
   * @param {string|jQuery} options.dropdownParent - Optional dropdown parent selector/element
   * @param {Function} options.getAdditionalFilters - Optional function that returns additional filter params
   * @param {Function} options.onChange - Optional callback when selection changes
   * @param {boolean} options.withEventHandlers - Whether to add extra event handlers (default: false)
   * @param {string} options.placeholder - Custom placeholder text
   */
  function initializeLocationSelect2(options) {
    // Default options
    const defaults = {
      selector: null,
      blockId: null,
      globalData: null,
      allowClear: true,
      dropdownParent: null,
      getAdditionalFilters: null,
      onChange: null,
      withEventHandlers: false,
      placeholder: "Type to search destinations (min 3 characters)...",
    };

    // Merge options with defaults
    const config = $.extend({}, defaults, options);

    // Validate required parameters
    if (!config.selector) {
      console.error("Select2 initialization error: selector is required");
      return;
    }

    if (!config.globalData) {
      console.error("Select2 initialization error: globalData is required");
      return;
    }

    const $locationSelect = $(config.selector);

    // Check if element exists
    if ($locationSelect.length === 0) {
      return;
    }

    // Extract REST URL and organizer ID based on data object structure
    const restUrl = config.globalData.restUrl || config.globalData.rest_url;
    const organizerId =
      config.globalData.organizerId || config.globalData.organizer_id;

    // Validate required data
    if (!restUrl || !organizerId) {
      console.error(
        "Select2 initialization error: restUrl and organizerId are required in globalData"
      );
      return;
    }

    // Destroy existing Select2 if present
    if ($locationSelect.hasClass("select2-hidden-accessible")) {
      $locationSelect.select2("destroy");
    }

    // Build Select2 configuration
    const select2Config = {
      placeholder: config.placeholder,
      allowClear: config.allowClear,
      width: "100%",
      minimumInputLength: 3,
      ajax: {
        url: restUrl + "wetravel/v1/destinations/search",
        dataType: "json",
        delay: 300,
        data: function (params) {
          const queryParams = {
            query: params.term,
            organizer_id: organizerId,
          };

          // Add additional filters if provided
          if (typeof config.getAdditionalFilters === "function") {
            const additionalFilters = config.getAdditionalFilters();
            $.extend(queryParams, additionalFilters);
          }

          return queryParams;
        },
        processResults: function (data) {
          if (data && data.destinations && Array.isArray(data.destinations)) {
            return {
              results: data.destinations.map(function (destination) {
                return {
                  id: destination,
                  text: destination,
                };
              }),
            };
          }
          return { results: [] };
        },
        transport: function (params, success, failure) {
          var $request = $.ajax(params);
          $request.then(success);
          $request.fail(failure);
          return $request;
        },
        cache: true,
      },
    };

    // Add dropdown parent if specified
    if (config.dropdownParent) {
      if (typeof config.dropdownParent === "string") {
        select2Config.dropdownParent = $locationSelect.closest(
          config.dropdownParent
        );
      } else {
        select2Config.dropdownParent = config.dropdownParent;
      }
    }

    // Initialize Select2
    $locationSelect.select2(select2Config);

    // Add change listener if provided
    if (typeof config.onChange === "function") {
      $locationSelect.on("change", function () {
        config.onChange($locationSelect, config.blockId);
      });
    }

    // Add extra event handlers if requested (for frontend dropdown behavior)
    if (config.withEventHandlers) {
      // Prevent dropdown from closing when removing individual selections
      $locationSelect.on("select2:unselecting", function (e) {
        e.stopPropagation();
      });

      // Prevent clicks on remove buttons from closing the filter dropdown
      $(document).on(
        "click",
        ".select2-selection__choice__remove",
        function (e) {
          e.stopPropagation();
        }
      );
    }

    return $locationSelect;
  }

  // Expose function globally for use in other scripts
  window.WeTravelSelect2 = {
    initializeLocationSelect2: initializeLocationSelect2,
  };
})(jQuery);
