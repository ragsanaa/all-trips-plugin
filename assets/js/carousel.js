document.addEventListener("DOMContentLoaded", function () {
  const container = document.querySelector(
    ".all-trips-container.carousel-view"
  );

  if (!container) {
    return;
  }

  const swiperContainer = container.querySelector(".swiper");

  if (!swiperContainer) {
    return;
  }

  // Get settings from localized object
  const settings = window.allTripsSettings || {
    buttonColor: "#33ae3f",
  };

  // Initialize Swiper
  const swiper = new Swiper(swiperContainer, {
    slidesPerView: "auto",
    spaceBetween: 15,
    pagination: {
      el: ".swiper-pagination",
      clickable: true,
    },
    navigation: {
      nextEl: ".swiper-button-next",
      prevEl: ".swiper-button-prev",
    },
    breakpoints: {
      // when window width is >= 320px
      320: {
        slidesPerView: 1,
        spaceBetween: 10,
      },
      // when window width is >= 480px
      480: {
        slidesPerView: 2,
        spaceBetween: 15,
      },
      // when window width is >= 768px
      768: {
        slidesPerView: 3,
        spaceBetween: 15,
      },
    },
  });

  // Apply button color from settings to any dynamic buttons
  if (settings.buttonColor) {
    const buttons = container.querySelectorAll(".trip-button");
    buttons.forEach((button) => {
      button.style.backgroundColor = settings.buttonColor;
    });
  }
});
