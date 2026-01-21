(function ($) {
  // Store loading state per carousel
  const carouselStates = new Map();

  // Also listen for dynamically loaded trips
  $(document).on(
    "tripsRendered",
    ".wetravel-trips-container.carousel-view",
    function () {
      // Initialize this specific carousel
      const container = this;
      const $container = $(container);
      const blockId = container.id.replace("trips-container-", "");
      const itemsPerSlide = parseInt($container.data("items-per-slide")) || 1;
      const buttonColor = $container.data("button-color") || "#33ae3f";
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

      // Initialize carousel state for progressive loading
      if (!carouselStates.has(blockId)) {
        carouselStates.set(blockId, {
          currentPage: 1,
          isLoading: false,
          hasMoreTrips: true,
          totalTrips: 0,
          loadedTrips: 0,
          totalPages: 1,
        });
      }

      const state = carouselStates.get(blockId);

      // Get pagination info from container (set by PHP during initial render)
      const initialTotalPages =
        parseInt($container.attr("data-total-pages")) || 1;
      const initialTotalTrips =
        parseInt($container.attr("data-total-trips")) || 0;

      // Update state with actual totals
      if (initialTotalPages) {
        state.totalPages = initialTotalPages;
      }
      if (initialTotalTrips) {
        state.totalTrips = initialTotalTrips;
      }

      // If only 1 page, no need for progressive loading
      if (initialTotalPages <= 1) {
        state.hasMoreTrips = false;
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

            // Initialize trip count from current slides
            state.loadedTrips = this.slides.length;
          },
          slideChange: function () {
            // Reapply styles after slide change
            applyPaginationStyles(swiperElement, buttonColor);

            // Check if we're near the end and should load more
            checkAndLoadMore(this, blockId, $container, itemsPerSlide);
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

      /**
       * Check if we should load more trips based on current slide position
       */
      function checkAndLoadMore(
        swiperInstance,
        blockId,
        $container,
        itemsPerSlide
      ) {
        const state = carouselStates.get(blockId);
        if (!state) return;

        // Don't load if already loading or no more trips
        if (state.isLoading || !state.hasMoreTrips) return;

        // Calculate how close we are to the end
        const slidesRemaining =
          swiperInstance.slides.length - swiperInstance.activeIndex;
        const loadTriggerPoint = 2; // Load when 2 slides remaining

        if (slidesRemaining <= loadTriggerPoint) {
          loadMoreTrips(swiperInstance, blockId, $container);
        }
      }

      /**
       * Load more trips from the API and append to carousel
       */
      function loadMoreTrips(swiperInstance, blockId, $container) {
        const state = carouselStates.get(blockId);
        if (!state || state.isLoading || !state.hasMoreTrips) return;

        state.isLoading = true;
        state.currentPage++;

        // Show loading indicator (optional)
        showCarouselLoading($container);

        // Get current configuration from container data attributes
        const config = {
          slug: $container.data("slug"),
          env: $container.data("env"),
          wetravelUserID: $container.data("wetravel-user-id"),
          tripType: $container.data("trip-type") || "all",
          dateStart: $container.data("date-start") || "",
          dateEnd: $container.data("date-end") || "",
          locations: $container.data("locations") || "",
          displayType: $container.data("display-type") || "carousel",
          buttonType: $container.data("button-type") || "book_now",
          buttonText: $container.data("button-text") || "",
          buttonColor: $container.data("button-color") || "#33ae3f",
          itemsPerPage: parseInt($container.data("items-per-page")) || 15,
          itemsPerRow: parseInt($container.data("items-per-row")) || 3,
          page: state.currentPage,
        };

        // Build API URL
        const apiUrl = "/wp-json/wetravel/v1/trips";
        const params = new URLSearchParams({
          block_id: blockId,
          slug: config.slug || "",
          env: config.env || "",
          trip_type: config.tripType || "all",
          date_start: config.dateStart || "",
          date_end: config.dateEnd || "",
          locations: config.locations || "",
          display_type: config.displayType || "carousel",
          button_type: config.buttonType || "book_now",
          button_text: config.buttonText || "",
          button_color: config.buttonColor || "#33ae3f",
          page: config.page,
          per_page: config.itemsPerPage || 15,
          items_per_row: config.itemsPerRow || 3,
          wetravel_user_id: config.wetravelUserID || "",
        });

        // Fetch new trips
        fetch(apiUrl + "?" + params.toString())
          .then((response) => {
            if (!response.ok) {
              throw new Error("HTTP " + response.status);
            }
            return response.json();
          })
          .then((data) => {
            hideCarouselLoading($container);

            if (data.success && data.trips && data.trips.length > 0) {
              // Append new slides to Swiper
              const newSlides = [];

              data.trips.forEach((trip) => {
                // Use pre-rendered HTML from PHP to ensure styling consistency
                const slideHTML = `<div class="swiper-slide">${trip.html}</div>`;
                newSlides.push(slideHTML);
              });

              // Append slides to Swiper
              swiperInstance.appendSlide(newSlides);

              // Force Swiper to update and recalculate
              swiperInstance.update();

              // Update pagination bullets
              if (
                swiperInstance.pagination &&
                swiperInstance.pagination.render
              ) {
                swiperInstance.pagination.render();
                swiperInstance.pagination.update();
              }

              // Update state
              state.loadedTrips += data.trips.length;
              state.hasMoreTrips = data.pagination && data.pagination.has_more;

              // Re-initialize WeTravel checkout buttons if needed
              // Use setTimeout to ensure DOM is fully updated before re-initializing
              setTimeout(function () {
                if (window.wtrvl && window.wtrvl.init) {
                  window.wtrvl.init();
                }
                // Trigger a custom event that the embed_checkout script might listen to
                document.dispatchEvent(new Event("DOMContentLoaded"));
              }, 100);
            } else {
              // No more trips available
              state.hasMoreTrips = false;
            }

            state.isLoading = false;
          })
          .catch((error) => {
            console.error("Failed to load more trips:", error);
            hideCarouselLoading($container);
            state.isLoading = false;
            // Don't set hasMoreTrips to false on error - allow retry
          });
      }

      /**
       * Show loading indicator for carousel (disabled - silent loading)
       */
      function showCarouselLoading($container) {
        // Silent loading - no indicator shown
      }

      /**
       * Hide loading indicator (disabled - silent loading)
       */
      function hideCarouselLoading($container) {
        // Silent loading - no indicator to remove
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
