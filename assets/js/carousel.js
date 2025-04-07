(function ($) {
  // Also listen for dynamically loaded trips
  $(document).on(
    "tripsRendered",
    ".wetravel-trips-container.carousel-view",
    function () {
      // Initialize this specific carousel
      const container = this;
      const itemsPerSlide = parseInt($(this).data("items-per-slide")) || 3;
      const buttonColor = $(this).data("button-color") || "#33ae3f";
      const swiperElement = container.querySelector(".swiper");

      if (!swiperElement) {
        console.error("Swiper element not found in container:", container);
        return;
      }

      // Check if Swiper is loaded
      if (typeof Swiper === "undefined") {
        console.error("Swiper library not loaded!");
        return;
      }

      // Initialize Swiper
      const swiper = new Swiper(swiperElement, {
        slidesPerView: 1,
        spaceBetween: 20, // Increased space between slides
        loop: false,
        watchOverflow: true,
        loopAdditionalSlides: 0,
        watchSlidesProgress: true,
        slidesOffsetBefore: 0,
        slidesOffsetAfter: 0,
        centeredSlides: false,
        pagination: {
          el: swiperElement.querySelector(".swiper-pagination"),
          clickable: true,
        },
        navigation: {
          nextEl: swiperElement.querySelector(".swiper-button-next"),
          prevEl: swiperElement.querySelector(".swiper-button-prev"),
        },
        breakpoints: {
          // when window width is >= 480px
          480: {
            slidesPerView: 1,
            spaceBetween: 10,
          },
          // when window width is >= 640px
          640: {
            slidesPerView: 2,
            spaceBetween: 20,
          },
          // when window width is >= 960px
          960: { slidesPerView: 3, spaceBetween: 20 },
          // when window width is >= 1024px
          1024: { slidesPerView: itemsPerSlide, spaceBetween: 20 },
        },
        on: {
          init: function () {
            // Apply custom color to navigation buttons
            const nextButton = swiperElement.querySelector(
              ".swiper-button-next"
            );
            const prevButton = swiperElement.querySelector(
              ".swiper-button-prev"
            );
            if (nextButton) nextButton.style.backgroundColor = buttonColor;
            if (prevButton) prevButton.style.backgroundColor = buttonColor;

            // Apply custom color to pagination bullets on initialization
            applyPaginationStyles(swiperElement, buttonColor);
          },
          slideChange: function () {
            // Reapply styles after slide change
            applyPaginationStyles(swiperElement, buttonColor);
          },
        },
      });

      function applyPaginationStyles(element, color) {
        const activeBullet = element.querySelector(
          ".swiper-pagination-bullet-active"
        );
        if (activeBullet) {
          activeBullet.style.backgroundColor = color;
        }
      }

      // Manually trigger a resize event to ensure Swiper updates properly
      setTimeout(function () {
        window.dispatchEvent(new Event("resize"));
      }, 500);
    }
  );

  // Add a document ready handler to trigger tripsRendered event for carousels
  $(document).ready(function () {
    // For pre-existing carousels, trigger the event manually
    $(".wetravel-trips-container.carousel-view").each(function () {
      // Check if the container has any trips
      if ($(this).find(".swiper-slide").length > 0) {
        $(this).trigger("tripsRendered");
      }
    });
  });
})(jQuery);
