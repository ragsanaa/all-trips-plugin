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
        slidesPerView: itemsPerPage,
        spaceBetween: 20, // Increased space between slides
        pagination: {
          el: swiperElement.querySelector(".swiper-pagination"),
          clickable: true,
        },
        navigation: {
          nextEl: swiperElement.querySelector(".swiper-button-next"),
          prevEl: swiperElement.querySelector(".swiper-button-prev"),
        },
        on: {
          init: function () {
            // Apply custom button color to pagination bullets
            const bullets = swiperElement.querySelectorAll(
              ".swiper-pagination-bullet"
            );
            bullets.forEach((bullet) => {
              bullet.addEventListener("click", function () {
                // Reset all bullets
                bullets.forEach((b) => (b.style.backgroundColor = ""));
                // Set active bullet color
                this.style.backgroundColor = buttonColor;
              });
            });

            // Set active bullet color initially
            const activeBullet = swiperElement.querySelector(
              ".swiper-pagination-bullet-active"
            );
            if (activeBullet) {
              activeBullet.style.backgroundColor = buttonColor;
            }

            // Apply custom color to navigation buttons
            const nextButton = swiperElement.querySelector(
              ".swiper-button-next"
            );
            const prevButton = swiperElement.querySelector(
              ".swiper-button-prev"
            );
            if (nextButton) nextButton.style.backgroundColor = buttonColor;
            if (prevButton) prevButton.style.backgroundColor = buttonColor;
          },
        },
      });
    }
  );
})(jQuery);
