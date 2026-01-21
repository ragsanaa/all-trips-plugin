(function ($) {
  "use strict";

  // Initialize colorpicker
  function initColorPicker() {
    $(".color-picker").wpColorPicker({
      change: function (event, ui) {
        // Ensure the input reflects the newly selected color immediately
        var color =
          ui && ui.color ? ui.color.toString() : $(event.target).val();
        $(event.target).val(color);
        // Trigger a standard change so our unified handler updates the preview with the new value
        setTimeout(function () {
          $("#button_color").trigger("change");
        }, 0);
      },
      clear: function (event) {
        $(event.target).val("");
        setTimeout(function () {
          $("#button_color").trigger("change");
        }, 0);
      },
    });
  }

  // Update the design preview with live mock data
  function updatePreview() {
    const displayType = $("#display_type").val();
    const buttonType = $("#button_type").val();
    const buttonText = $("#button_text").val();
    const buttonColor = $("#button_color").val();
    const tripType = $("#trip_type").val();
    const itemsPerPage = $("#items_per_page").val() || 10;
    const itemsPerRow = $("#items_per_row").val() || 3;
    const itemsPerSlide = $("#items_per_slide").val() || 1;
    const borderRadius = $("#border_radius").val() || 6;
    const searchVisibility = $("#search_visibility").is(":checked");

    // Get selected locations from the trip_location select field
    const selectedLocations = $("#trip_location").val() || [];

    $("#design-preview").html(
      '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 20px; height: 20px; border: 3px solid #f3f3f3; border-top: 3px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 10px;">Loading preview...</p></div><style>@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>'
    );

    // Generate live preview using server-side mock data
    setTimeout(() => {
      generateLivePreview();
    }, 800);

    function generateLivePreview() {
      // Use the actual block renderer via AJAX
      const requestData = {
        action: "render_mock_preview",
        nonce: wetravel_ajax.nonce,
        displayType: displayType,
        buttonType: buttonType,
        buttonText: buttonText,
        buttonColor: buttonColor,
        borderRadius: parseInt(borderRadius),
        itemsPerRow: parseInt(itemsPerRow),
        itemsPerPage: parseInt(itemsPerPage),
        itemsPerSlide: parseInt(itemsPerSlide),
        searchVisibility: searchVisibility ? 1 : 0,
        tripType: tripType,
        locations: selectedLocations,
      };

      $.ajax({
        url: wetravel_ajax.ajaxurl,
        type: "POST",
        data: requestData,
        success: function (response) {
          if (response.success) {
            const previewHtml = `
              <h4>${
                displayType.charAt(0).toUpperCase() + displayType.slice(1)
              } Layout Preview - Live Data</h4>
              ${response.data.html}
            `;
            $("#design-preview").html(previewHtml);

            // Hide loading spinner if it exists
            $("#design-preview .wetravel-trips-loading").hide();

            // Initialize components based on display type
            if (displayType === "carousel") {
              initializeCarouselPreview();
            } else {
              initializePaginationPreview();
            }
          } else {
            $("#design-preview").html(`
              <div style="color: red; padding: 20px;">
                <h4>Preview Error</h4>
                <p>Failed to load preview: ${
                  response.data || "Unknown error"
                }</p>
              </div>
            `);
          }
        },
        error: function (xhr, status, error) {
          $("#design-preview").html(`
            <div style="color: red; padding: 20px;">
              <h4>Preview Error</h4>
              <p>Failed to load preview: ${error}</p>
            </div>
          `);
        },
      });
    }

    function initializeCarouselPreview() {
      // Wait for DOM to be fully ready and Swiper to be loaded
      function waitForSwiper(callback, maxAttempts = 50) {
        let attempts = 0;

        function checkSwiper() {
          attempts++;

          if (typeof Swiper !== "undefined") {
            callback();
          } else if (attempts < maxAttempts) {
            setTimeout(checkSwiper, 100);
          } else {
            console.error("Swiper library failed to load for admin preview");
          }
        }

        checkSwiper();
      }

      waitForSwiper(function () {
        setTimeout(function () {
          const carouselContainer = $(
            "#design-preview .wetravel-trips-container.carousel-view"
          );

          if (carouselContainer.length > 0) {
            // Set consistent preview width to 600px (same as other layouts)
            const previewContainer = $("#design-preview");

            // Let CSS handle the carousel wrapper styling
            const carouselWrapper = carouselContainer.find(
              ".wetravel-carousel-wrapper"
            );
            if (carouselWrapper.length > 0) {
              carouselWrapper.removeAttr("style");
            }

            // Force proper CSS for carousel container
            carouselContainer.css({
              width: "100%",
              "max-width": "100%",
              overflow: "visible", // Allow buttons to be visible outside
              "box-sizing": "border-box",
            });

            const swiperContainerWrapper = carouselContainer.find(
              ".swiper-container-wrapper"
            );
            if (swiperContainerWrapper.length > 0) {
              swiperContainerWrapper.removeAttr("style");
            }

            const swiperElement = carouselContainer.find(".swiper");
            if (swiperElement.length > 0) {
              swiperElement.css({
                width: "100%",
                "max-width": "100%",
                height: "auto",
                "box-sizing": "border-box",
                overflow: "hidden",
              });

              const swiperWrapper = swiperElement.find(".swiper-wrapper");
              if (swiperWrapper.length > 0) {
                swiperWrapper.css({
                  width: "100%",
                  "max-width": "100%",
                  "box-sizing": "border-box",
                });
              }

              // Constrain slide widths
              const swiperSlides = swiperElement.find(".swiper-slide");
              swiperSlides.css({
                width: "100%",
                "max-width": "100%",
                "box-sizing": "border-box",
              });
            }

            // Remove any custom styling since we now use CSS for consistent positioning
            const navButtons = carouselContainer.find(
              ".swiper-button-next, .swiper-button-prev"
            );
            if (navButtons.length > 0) {
              // Clear any inline styles to let CSS take over
              navButtons.removeAttr("style");
            }

            // Ensure all images are loaded before initializing
            const images = carouselContainer.find("img");
            let loadedImages = 0;

            function checkAllImagesLoaded() {
              loadedImages++;

              if (loadedImages === images.length || images.length === 0) {
                // Small delay to ensure DOM is stable
                setTimeout(() => {
                  carouselContainer.trigger("tripsRendered");
                }, 100);
              }
            }

            if (images.length === 0) {
              carouselContainer.trigger("tripsRendered");
            } else {
              // Wait for all images to load
              images.each(function () {
                if (this.complete) {
                  checkAllImagesLoaded();
                } else {
                  $(this).on("load error", checkAllImagesLoaded);
                }
              });

              // Fallback timeout in case some images fail to load
              setTimeout(function () {
                if (loadedImages < images.length) {
                  carouselContainer.trigger("tripsRendered");
                }
              }, 3000);
            }
          } else {
            console.error("Carousel container not found in preview");
          }
        }, 300);
      });
    }

    // New function to initialize pagination preview
    function initializePaginationPreview() {
      setTimeout(() => {
        const previewContainer = $("#design-preview .wetravel-trips-container");
        if (previewContainer.length > 0) {
          // Trigger the pagination initialization from pagination.js
          if (typeof window.initializePaginationForContainer === "function") {
            window.initializePaginationForContainer(previewContainer);
          } else {
            // Fallback: trigger the existing pagination system
            previewContainer.trigger("tripsRendered");
          }
        }
      }, 500);
    }

    // Button style with border radius
    const buttonStyle =
      displayType === "vertical"
        ? `background-color: ${buttonColor}; color: white; padding: 8px 16px; border-radius: ${borderRadius}px; text-decoration: none; display: inline-block; text-align: center; cursor: pointer; border: 1px solid ${buttonColor};`
        : `background-color: transparent; color: ${buttonColor}; padding: 8px 16px; border-radius: ${borderRadius}px; text-decoration: none; display: inline-block; text-align: center; cursor: pointer; border: 1px solid ${buttonColor};`;

    // Update shortcode preview if we're creating a new design
    if (!$('input[name="design_id"]').length) {
      $(".shortcode-preview").html(
        "<p>Shortcode will be generated after saving.</p>"
      );
    }
  }

  // Copy shortcode to clipboard
  function initCopyShortcode() {
    $(".wetravel-trips-copy-shortcode").on("click", function (e) {
      e.preventDefault();

      const shortcode = $(this).data("shortcode");

      // Create temporary textarea
      const textarea = document.createElement("textarea");
      textarea.value = shortcode;
      document.body.appendChild(textarea);

      // Select and copy
      textarea.select();
      document.execCommand("copy");

      // Remove textarea
      document.body.removeChild(textarea);

      // Show success message
      const originalText = $(this).text();
      $(this).text("Copied!");
      setTimeout(() => {
        $(this).text(originalText);
      }, 2000);
    });
  }

  // Show/hide form fields based on display type
  function toggleDisplayTypeFields() {
    const displayType = $("#display_type").val();

    // Hide all display-specific fields first
    $("#items_per_slide").closest(".wetravel-trips-form-field").hide();
    $("#items_per_row").closest(".wetravel-trips-form-field").hide();
    $("#items_per_page").closest(".wetravel-trips-form-field").hide();

    // Show relevant fields based on display type
    if (displayType === "carousel") {
      $("#items_per_slide").closest(".wetravel-trips-form-field").show();
    } else if (displayType === "grid") {
      $("#items_per_row").closest(".wetravel-trips-form-field").show();
      $("#items_per_page").closest(".wetravel-trips-form-field").show();
    } else {
      // Vertical
      $("#items_per_page").closest(".wetravel-trips-form-field").show();
    }
  }

  // Initialize on document ready
  $(document).ready(function ($) {
    // Init color picker
    initColorPicker();

    // Initialize destinations Select2 with AJAX search
    initializeLocationsSelect2();

    // Update preview initially (only if we're on the design page)
    if ($("#display_type").length > 0) {
      updatePreview();
    }

    // Only initialize design page specific functionality if we're on that page
    if ($("#display_type").length > 0) {
      // Run on page load
      toggleDisplayTypeFields();

      // Update preview when form fields change
      $(
        "#display_type, #button_type, #button_text, #button_color, #trip_type, #items_per_page, #items_per_row, #items_per_slide, #border_radius, #search_visibility, #trip_location"
      ).on("change input", function () {
        // Force immediate update when any field changes
        setTimeout(updatePreview, 0);
      });

      // Run when display type changes
      $("#display_type").on("change", toggleDisplayTypeFields);

      // Reinitialize Select2 when filters change
      $("#trip_type, #departure_date_gte, #departure_date_lte").on(
        "change",
        function () {
          // Reinitialize the locations select with new filter context
          initializeLocationsSelect2();

          // Trigger preview update
          updatePreview();
        }
      );

      // Update button text based on button type if it has default value
      $("#button_type").on("change", function () {
        const buttonType = $(this).val();
        const buttonText = $("#button_text");

        if (
          buttonText.val() === "Book Now" ||
          buttonText.val() === "View Trip"
        ) {
          buttonText.val(buttonType === "book_now" ? "Book Now" : "View Trip");
        }

        // Force update preview immediately after changing button type
        updatePreview();
      });
    }

    // Init copy shortcode functionality (works on both pages)
    initCopyShortcode();

    // Real-time keyword uniqueness checker (only on design page)
    if ($("#design_keyword").length > 0) {
      var checkKeywordTimeout;
      $("#design_keyword").on("keyup blur", function () {
        var keyword = $(this).val().trim();
        clearTimeout(checkKeywordTimeout);

        // Clear any existing validation messages
        $("#keyword-validation-message").remove();

        // Only check if keyword has content
        if (keyword.length > 0) {
          // Add a small delay to prevent too many requests
          checkKeywordTimeout = setTimeout(function () {
            $.ajax({
              url: wetravel_ajax.ajaxurl,
              type: "POST",
              data: {
                action: "check_keyword_unique",
                keyword: keyword,
                design_id: wetravel_ajax.design_id || "",
                nonce: wetravel_ajax.nonce,
              },
              success: function (response) {
                if (response && typeof response.unique !== "undefined") {
                  if (!response.unique) {
                    // Display validation message
                    $(
                      '<p id="keyword-validation-message" class="validation-error" style="color:red; margin-top: 5px; font-size: 12px;">⚠️ This keyword is already in use. Please choose a unique keyword.</p>'
                    ).insertAfter("#design_keyword");
                  } else {
                    // Show success message
                    $(
                      '<p id="keyword-validation-message" class="validation-success" style="color:green; margin-top: 5px; font-size: 12px;">✓ Keyword is available!</p>'
                    ).insertAfter("#design_keyword");
                  }
                }
              },
              error: function (xhr, status, error) {
                console.log("Keyword check error:", error);
                // Optionally show an error message to user
                $(
                  '<p id="keyword-validation-message" class="validation-error" style="color:orange; margin-top: 5px; font-size: 12px;">⚠️ Could not verify keyword uniqueness. Please try again.</p>'
                ).insertAfter("#design_keyword");
              },
            });
          }, 500);
        }
      });
    }
  });

  // Initialize Select2 with AJAX search for locations
  // Uses the centralized WeTravelSelect2 utility
  function initializeLocationsSelect2() {
    // Check if required dependencies are available
    if (!wetravel_ajax || typeof window.WeTravelSelect2 === "undefined") {
      return;
    }

    // Get current filter values for additional AJAX parameters
    function getAdditionalFilters() {
      var filters = {
        trip_type: $("#trip_type").val() || "",
        date_start: $("#departure_date_gte").val() || "",
        date_end: $("#departure_date_lte").val() || "",
      };

      var queryParams = {};

      // Add optional filters if they have values
      if (filters.trip_type !== "") {
        queryParams.trip_type = filters.trip_type;
      }
      if (filters.date_start) {
        queryParams.date_start = filters.date_start;
      }
      if (filters.date_end) {
        queryParams.date_end = filters.date_end;
      }

      return queryParams;
    }

    // Use centralized Select2 initialization
    window.WeTravelSelect2.initializeLocationSelect2({
      selector: "#trip_location",
      globalData: wetravel_ajax,
      allowClear: true,
      placeholder: "Type to search destinations (min 3 characters)...",
      getAdditionalFilters: getAdditionalFilters,
    });
  }
})(jQuery);
