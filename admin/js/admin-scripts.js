(function ($) {
  "use strict";

  // Initialize colorpicker
  function initColorPicker() {
    $(".color-picker").wpColorPicker({
      change: function (event, ui) {
        updatePreview();
      },
    });
  }

  // Update the design preview
  function updatePreview() {
    const displayType = $("#display_type").val();
    const buttonType = $("#button_type").val();
    const buttonText = $("#button_text").val();
    const buttonColor = $("#button_color").val();
    const tripType = $("#trip_type").val();
    const itemsPerPage = $("#items_per_page").val() || 10;
    const itemsPerRow = $("#items_per_row").val() || 3;
    const searchVisibility = $("#search_visibility").is(":checked");

    // Create container based on display type
    let previewHtml = "";
    let containerClass = "preview-" + displayType;

    // Button style
    const buttonStyle = `
      background-color: ${buttonColor};
      color: white;
      padding: 8px 16px;
      border-radius: 4px;
      text-decoration: none;
      display: inline-block;
      text-align: center;
      cursor: pointer;
    `;

    // Create sample trip item - matching the rendering from trips-loader.js
    function createTripItem(index, displayType) {
      return `
        <div class="preview-trip-item">
          <div class="preview-trip-image">Trip Image</div>
          <div class="preview-trip-content">
            <div class="preview-trip-title-desc">
              <h3>Sample Trip ${index}</h3>
                <div class="preview-trip-description">
                  <p>About your trip description goes here with more details about this amazing trip.
                  This text may be longer to demonstrate the fading effect on descriptions.</p>
                </div>
            </div>
            ${
              displayType === "carousel"
                ? `<div class='preview-trip-loc-price'>`
                : ""
            }
            <div class="preview-trip-loc-duration">
              <div class="preview-trip-tag">10 days</div>
              <div class="preview-trip-tag">Exotic Location</div>
            </div>

            ${displayType !== "carousel" ? `</div>` : ""}
          <div class="preview-trip-price-button">
              <div class="preview-trip-price">
                <p>From</p> <span>$1,000</span>
              </div>
              ${
                displayType !== "carousel"
                  ? `<a href="#" style="${buttonStyle}" class="preview-button-${buttonType}">${buttonText}</a>`
                  : ""
              }
            </div>
          ${displayType === "carousel" ? `</div></div>` : ""}
        </div>
      `;
    }

    // Create pagination preview
    function createPaginationPreview(buttonColor) {
      return `
        <div class="preview-pagination">
          <div class="preview-pagination-item">«</div>
          <div class="preview-pagination-item">‹</div>
          <div class="preview-pagination-item active">1</div>
          <div class="preview-pagination-item">2</div>
          <div class="preview-pagination-item">3</div>
          <div class="preview-pagination-item">...</div>
          <div class="preview-pagination-item">10</div>
          <div class="preview-pagination-item">›</div>
          <div class="preview-pagination-item">»</div>
        </div>
      `;
    }

    // Create search bar preview
    function createSearchBarPreview(buttonColor) {
      return `
        <div class="preview-search-filter">
          <div class="preview-search-filter-container">
            <input type="text" class="preview-search-input" placeholder="Search trips by name..." disabled />
            <button type="button" class="preview-location-button" style="background-color: ${buttonColor}; border-color: ${buttonColor}; color: white;">
              <span>Select locations</span>
              <span class="preview-dropdown-arrow">▲</span>
            </button>
          </div>
        </div>
      `;
    }

    // Generate preview based on display type
    if (displayType === "grid") {
      previewHtml = `
        <h4>Grid Layout Preview</h4>
        ${searchVisibility ? createSearchBarPreview(buttonColor) : ""}
        <div class="${containerClass}" style="grid-template-columns: repeat(${itemsPerRow}, 1fr);">
          ${createTripItem(1, displayType)}
          ${createTripItem(2, displayType)}
          ${createTripItem(3, displayType)}
        </div>
      `;

      // Add pagination preview for grid and vertical layouts
      previewHtml += createPaginationPreview(buttonColor);
    } else if (displayType === "carousel") {
      previewHtml = `
        <h4>Carousel Layout Preview</h4>
        <div class="${containerClass}">
          <div class="preview-carousel-controls">
            <div class="preview-carousel-nav prev" style="background-color: ${buttonColor};">◀</div>
            <div class="preview-carousel-slides">
              ${createTripItem(1, displayType)}
            </div>
            <div class="preview-carousel-nav next" style="background-color: ${buttonColor};">▶</div>
          </div>
          <div class="preview-carousel-pagination">
            <span class="preview-pagination-bullet active" style="background-color: ${buttonColor};"></span>
            <span class="preview-pagination-bullet"></span>
            <span class="preview-pagination-bullet"></span>
          </div>
        </div>
      `;
    } else {
      // Vertical layout
      previewHtml = `
        <h4>Vertical Layout Preview</h4>
        ${searchVisibility ? createSearchBarPreview(buttonColor) : ""}
        <div class="${containerClass}">
          ${createTripItem(1, displayType)}
          ${createTripItem(2, displayType)}
        </div>
      `;

      // Add pagination preview for grid and vertical layouts
      previewHtml += createPaginationPreview(buttonColor);
    }

    // Update the preview
    $("#design-preview").html(previewHtml);

    // Add styles to the preview - matching trips-loader.js styling
    $("#design-preview").append(`
      <style>
        .preview-trip-item {
          border: 1px solid #ddd;
          padding: 15px;
          margin-bottom: 15px;
          border-radius: 4px;
          background-color: white;
          box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .preview-trip-image {
          height: 150px;
          background-color: #eee;
          display: flex;
          align-items: center;
          justify-content: center;
          margin-bottom: 10px;
          color: #777;
          border-radius: 4px;
        }

        .preview-trip-content {
          display: flex;
          flex-direction: column;
        }

        .preview-trip-title-desc h3 {
          margin-top: 0;
          margin-bottom: 10px;
        }

        .preview-trip-description {
          position: relative;
          max-height: 60px;
          overflow: hidden;
          margin-bottom: 10px;
        }

        .preview-trip-description:after {
          content: "";
          position: absolute;
          bottom: 0;
          left: 0;
          width: 100%;
          height: 20px;
          background: linear-gradient(transparent, white);
        }

        .preview-trip-loc-duration {
          display: flex;
          flex-wrap: wrap;
          gap: 8px;
          margin-bottom: 10px;
        }
        .preview-trip-loc-price {
          display: flex;
          justify-content: space-between;
        }
        .preview-trip-tag {
          background-color: #f5f5f5;
          padding: 4px 8px;
          border-radius: 4px;
          font-size: 12px;
        }

        .preview-trip-price-button {
          display: flex;
          justify-content: space-between;
          flex-direction: column;
          direction: rtl;
        }

        .preview-trip-price {
          display: flex;
          flex-direction: column;
        }

        .preview-trip-price p {
          margin: 0;
          font-size: 12px;
          color: #777;
        }

        .preview-trip-price span {
          font-size: 18px;
          font-weight: bold;
        }

        .preview-grid {
          display: grid;
          grid-template-columns: repeat(3, 1fr);
          gap: 15px;
        }

        .preview-carousel {
          position: relative;
        }

        .preview-carousel-controls {
          display: flex;
          align-items: center;
          gap: 10px;
        }

        .preview-carousel-slides {
          flex: 1;
          overflow: hidden;
        }

        .preview-carousel-nav {
          width: 30px;
          height: 30px;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          color: white;
          cursor: pointer;
        }

        .preview-carousel-pagination {
          display: flex;
          justify-content: center;
          gap: 5px;
          margin-top: 15px;
        }

        .preview-pagination-bullet {
          width: 10px;
          height: 10px;
          border-radius: 50%;
          background-color: #ddd;
          cursor: pointer;
        }

        .preview-pagination-bullet.active {
          background-color: ${buttonColor};
        }

        .preview-pagination {
          display: flex;
          justify-content: center;
          margin-top: 20px;
          flex-wrap: wrap;
          gap: 5px;
        }

        .preview-pagination-item {
          width: 35px;
          height: 35px;
          display: flex;
          align-items: center;
          justify-content: center;
          border: 1px solid #ddd;
          border-radius: 4px;
          cursor: pointer;
        }

        .preview-pagination-item.active {
          background-color: ${buttonColor};
          color: white;
          border-color: ${buttonColor};
        }

        /* Vertical layout specific styles */
        .preview-vertical .preview-trip-item {
          display: grid;
          grid-template-columns: 2fr 3fr 1fr;
          gap: 15px;
        }

        .preview-vertical .preview-trip-image {
          height: 100%;
          margin-bottom: 0;
        }
        .preview-grid .preview-trip-price-button {
          direction: ltr;
          display: flex;
          flex-direction: row;
          justify-content: space-between;
          align-items: end;
        }

        /* Search filter styles */
        .preview-search-filter {
          margin-bottom: 20px;
        }

        .preview-search-filter-container {
          display: flex;
          gap: 10px;
          margin-bottom: 10px;
        }

        .preview-search-input {
          flex: 1;
          padding: 8px 12px;
          border: 1px solid #ddd;
          border-radius: 4px;
          font-size: 14px;
          background-color: #f5f5f5;
          cursor: not-allowed;
        }

        .preview-search-input::placeholder {
          color: #888;
        }

        .preview-location-button {
          display: flex;
          align-items: center;
          gap: 8px;
          padding: 8px 12px;
          border: 1px solid;
          border-radius: 4px;
          cursor: pointer;
          font-size: 14px;
          transition: all 0.3s ease;
        }

        .preview-location-button:hover {
          opacity: 0.9;
        }

        .preview-dropdown-arrow {
          font-size: 10px;
          transform: rotate(180deg);
          color: white;
        }
      </style>
    `);

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

  // Show/hide date range inputs based on trip type selection
  function toggleDateRangeFields() {
    var selectedTripType = $("#trip_type").val();
    if (selectedTripType === "one-time") {
      $("#date-range-container").show();
    } else {
      $("#date-range-container").hide();
    }
  }

  // Initialize on document ready
  $(document).ready(function ($) {
    // Init color picker
    initColorPicker();

    // Update preview initially
    updatePreview();

    // Run on page load
    toggleDateRangeFields();

    // Update preview when form fields change
    $(
      "#display_type, #button_type, #button_text, #button_color, #trip_type, #items_per_page, #items_per_row, #search_visibility"
    ).on("change input", function () {
      // Force immediate update when any field changes
      setTimeout(updatePreview, 0);
    });

    // Run when trip type changes
    $("#trip_type").on("change", toggleDateRangeFields);

    // Init copy shortcode functionality
    initCopyShortcode();

    // Update button text based on button type if it has default value
    $("#button_type").on("change", function () {
      const buttonType = $(this).val();
      const buttonText = $("#button_text");

      if (buttonText.val() === "Book Now" || buttonText.val() === "View Trip") {
        buttonText.val(buttonType === "book_now" ? "Book Now" : "View Trip");
      }

      // Force update preview immediately after changing button type
      updatePreview();
    });

    // Create a nonce field in the form
    var nonceField = $("<input>").attr({
      type: "hidden",
      name: "wetravel_trips_nonce",
      value: '<?php echo wp_create_nonce("wetravel_trips_nonce"); ?>',
    });
    $("form").append(nonceField);

    // Keyword uniqueness checker
    var checkKeywordTimeout;
    $("#design_keyword").on("keyup blur", function () {
      var keyword = $(this).val();
      clearTimeout(checkKeywordTimeout);

      // Clear any existing validation messages
      $("#keyword-validation-message").remove();

      // Only check if keyword has content
      if (keyword.length > 0) {
        // Add a small delay to prevent too many requests
        checkKeywordTimeout = setTimeout(function () {
          $.ajax({
            url: ajaxurl,
            type: "POST",
            data: {
              action: "check_keyword_unique",
              keyword: keyword,
              design_id: "<?php echo esc_js($design_id); ?>",
              nonce: '<?php echo wp_create_nonce("wetravel_trips_nonce"); ?>',
            },
            success: function (response) {
              if (!response.unique) {
                // Display validation message
                $(
                  '<p id="keyword-validation-message" class="validation-error" style="color:red;">This keyword is already in use. Please choose a unique keyword.</p>'
                ).insertAfter("#design_keyword");
              } else {
                // Show success message
                $(
                  '<p id="keyword-validation-message" class="validation-success" style="color:green;">Keyword is available!</p>'
                ).insertAfter("#design_keyword");
              }
            },
          });
        }, 500);
      }
    });
  });
})(jQuery);
