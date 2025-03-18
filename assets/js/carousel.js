(function ($) {
  // Also listen for dynamically loaded trips
  $(document).on(
    "tripsRendered",
    ".all-trips-container.carousel-view",
    function () {
      // Initialize this specific carousel
      const container = this;
      const itemsPerPage = parseInt($(this).data("items-per-page")) || 10;
      const buttonColor = $(this).data("button-color") || "#33ae3f";
      const swiperElement = container.querySelector(".swiper");

      if (!swiperElement) {
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

          1024: { slidesPerView: itemsPerPage, spaceBetween: 20 },
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
        activeBullet.style.backgroundColor = color;
      }
    }
  );
})(jQuery);
