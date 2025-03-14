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

    // Create container based on display type
    let previewHtml = "";

    // Button style
    const buttonStyle = `
          background-color: ${buttonColor};
          color: white;
          padding: 8px 16px;
          border-radius: 4px;
          text-decoration: none;
          display: inline-block;
          text-align: center;
      `;

    // Create sample trip item
    function createTripItem(index, displayType) {
      return `
              <div class="preview-trip-item">
                  <div class="preview-trip-image">Trip Image</div>
                  <h3>Sample Trip ${index}</h3>
                  ${
                    displayType === "vertical"
                      ? `
                      <p>About your trip description goes here.</p>
                      `
                      : ""
                  }
                  <p>Duration</p>
                  <p style="direction:rtl;">Price: $100</p>
                  ${
                    displayType === "vertical"
                      ? `
                      <a href="#" style="${buttonStyle}" class="button-${buttonType}">${buttonText}</a>
                      `
                      : ""
                  }
              </div>
          `;
    }

    // Generate preview based on display type
    if (displayType === "grid") {
      previewHtml = `
              <h4>Grid Layout Preview</h4>
              <div class="preview-grid">
                  ${createTripItem(1, "grid")}
                  ${createTripItem(2, "grid")}
                  ${createTripItem(3, "grid")}
              </div>
          `;
    } else if (displayType === "carousel") {
      previewHtml = `
              <h4>Carousel Layout Preview</h4>
              <div class="preview-carousel">
                  <div class="preview-carousel-controls" style="flex-direction: column;">
                      <div style="display: flex; align-items: center; width: 100%;">
                        <span>◀</span>
                        ${createTripItem(1, "carousel")}
                        <span>▶</span>
                      </div>
                      <div class="swiper-pagination" style="display: flex; justify-content: center;">
                          <span class="swiper-pagination-bullet active"></span>
                          <span class="swiper-pagination-bullet"></span>
                          <span class="swiper-pagination-bullet"></span>
                      </div>
                  </div>
              </div>
          `;
    } else {
      previewHtml = `
              <h4>Vertical Layout Preview</h4>
              <div class="preview-vertical">
                  ${createTripItem(1, "vertical")}
              </div>
          `;
    }

    // Update the preview
    $("#design-preview").html(previewHtml);

    // Add styles to the preview
    $("#design-preview").append(`
          <style>
              .preview-trip-item {
                  border: 1px solid #ddd;
                  padding: 15px;
                  margin-bottom: 10px;
                  border-radius: 4px;
                  width: 90%;
                  display: inline-block;
              }
              .preview-trip-image {
                  height: 150px;
                  background-color: #eee;
                  display: flex;
                  align-items: center;
                  justify-content: center;
                  margin-bottom: 10px;
                  color: #777;
              }
              .preview-grid {
                  display: grid;
                  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                  gap: 15px;
              }
              .preview-carousel {
                  position: relative;
              }
              .preview-carousel-controls {
                  display: flex;
                  align-items: center;
                  margin-top: 10px;
              }
              .preview-carousel-controls span {
                  cursor: pointer;
                  background: ${buttonColor};
                  color: white;
                  width: 30px;
                  height: 30px;
                  display: flex;
                  align-items: center;
                  justify-content: center;
                  border-radius: 50%;
              }

              .swiper-pagination {
                position: relative;
                margin-top: 20px;
              }
              .preview-carousel-controls .swiper-pagination-bullet {
                width: 12px;
                height: 12px;
                margin: 0 5px;
                background-color: #ddd;
              }
              .preview-carousel-controls .active {
                background-color: ${buttonColor};
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
    $(".all-trips-copy-shortcode").on("click", function (e) {
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

  // Initialize on document ready
  $(document).ready(function () {
    // Init color picker
    initColorPicker();

    // Update preview initially
    updatePreview();

    // Update preview when form fields change - using input event too for more immediate response
    $("#display_type, #button_type, #button_text").on(
      "change input",
      function () {
        // Force immediate update when any field changes
        setTimeout(updatePreview, 0);
      }
    );

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
  });
})(jQuery);
