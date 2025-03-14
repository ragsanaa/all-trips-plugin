document.addEventListener("DOMContentLoaded", function () {
  // Find all carousel containers
  const carouselContainers = document.querySelectorAll(
    ".all-trips-container.carousel-view"
  );

  if (!carouselContainers.length) {
    return;
  }

  // Initialize each carousel
  carouselContainers.forEach(function (container) {
    const swiperElement = container.querySelector(".swiper");

    if (!swiperElement) {
      return;
    }

    // Initialize Swiper
    const swiper = new Swiper(swiperElement, {
      slidesPerView: 1,
      spaceBetween: 20, // Increased space between slides
      pagination: {
        el: swiperElement.querySelector(".swiper-pagination"),
        clickable: true,
      },
      navigation: {
        nextEl: swiperElement.querySelector(".swiper-button-next"),
        prevEl: swiperElement.querySelector(".swiper-button-prev"),
      },
      breakpoints: {
        // when window width is >= 640px
        640: {
          slidesPerView: 1,
          spaceBetween: 30, // Increased space
        },
        // when window width is >= 992px
        992: {
          slidesPerView: 2, // Show 3 cards at once on larger screens
          spaceBetween: 30, // Consistent spacing
        },
      },
    });
  });
});
