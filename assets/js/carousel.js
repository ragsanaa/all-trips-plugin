(function ($) {
  // Also listen for dynamically loaded trips
  $(document).on(
    "tripsRendered",
    ".wetravel-trips-container.carousel-view",
    function () {
      // Initialize this specific carousel
      const container = this;
      const itemsPerSlide = parseInt($(this).data("items-per-slide")) || 1;
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

      // Check if this swiper is already initialized
      if (swiperElement.swiper) {
        swiperElement.swiper.destroy(true, true);
      }

      // Initialize Swiper
      const swiper = new Swiper(swiperElement, {
        init: false,
        spaceBetween: 20,
        slidesPerView: itemsPerSlide,
        width: null, // Let Swiper calculate width automatically
        watchOverflow: true,
        watchSlidesProgress: true,
        slidesOffsetBefore: 0,
        slidesOffsetAfter: 0,
        centeredSlides: false,
        autoHeight: false,
        pagination: {
          el: swiperElement.querySelector(".swiper-pagination"),
          clickable: true,
        },
        navigation: {
          nextEl: container.querySelector(".swiper-button-next"),
          prevEl: container.querySelector(".swiper-button-prev"),
        },
        // Ensure navigation buttons are positioned correctly
        observer: true,
        observeParents: true,
        breakpoints: {
          // Explicit mobile-first breakpoint (0 - 639px)
          0: {
            slidesPerView: 1,
            spaceBetween: 20,
          },
          // >= 640px
          640: {
            slidesPerView: Math.min(2, itemsPerSlide),
            spaceBetween: 20,
          },
          // >= 960px
          960: {
            slidesPerView: Math.min(3, itemsPerSlide),
            spaceBetween: 20,
          },
          // >= 1024px
          1024: {
            slidesPerView: itemsPerSlide,
            spaceBetween: 20,
          },
        },
        on: {
          init: function () {
            // Apply custom color to navigation buttons
            const nextButton = container.querySelector(".swiper-button-next");
            const prevButton = container.querySelector(".swiper-button-prev");
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

      // Initialize swiper
      try {
        swiper.init();

        // Force update after initialization to ensure proper layout
        setTimeout(() => {
          swiper.update();
        }, 100);
      } catch (error) {
        console.error("Error initializing Swiper:", error);
      }

      // Update swiper when all images are loaded
      const images = swiperElement.getElementsByTagName("img");
      let loadedImages = 0;

      function checkAllImagesLoaded() {
        loadedImages++;
        if (loadedImages === images.length) {
          swiper.update();
        }
      }

      // Handle image loading
      if (images.length > 0) {
        Array.from(images).forEach((img) => {
          if (img.complete) {
            checkAllImagesLoaded();
          } else {
            img.addEventListener("load", checkAllImagesLoaded);
            img.addEventListener("error", checkAllImagesLoaded); // Count errors too
          }
        });
      }

      // Update on window resize
      const resizeHandler = () => {
        if (swiper && !swiper.destroyed) {
          swiper.update();
        }
      };

      window.addEventListener("resize", resizeHandler);

      // Store resize handler for cleanup
      if (!container.swiperResizeHandler) {
        container.swiperResizeHandler = resizeHandler;
      }
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
