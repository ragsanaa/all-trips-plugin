/**
 * WeTravel Widgets Tracking System - Frontend JavaScript
 *
 * Tracks widget loads, clicks, and button clicks with GDPR compliance
 */

(function ($) {
  "use strict";

  // Initialize tracking when document is ready
  $(document).ready(function () {
    WetravelTracking.init();
  });

  /**
   * Main tracking object
   */
  window.WetravelTracking = {
    // Configuration
    config: {},

    // Consent status
    hasConsent: false,

    /**
     * Initialize tracking system
     */
    init: function () {
      // Check if tracking data is available
      if (typeof wetravelTrackingData === "undefined") {
        console.warn("WeTravel Tracking: Configuration not found");
        return;
      }

      this.config = wetravelTrackingData;

      // Update consent status from configuration
      this.hasConsent = this.config.has_consent || false;

      // Skip if tracking is disabled or no consent
      if (!this.hasConsent) {
        return;
      }

      // Skip if in admin or edit context
      if (this.isAdminOrEditContext()) {
        return;
      }

      // Setup event listeners
      this.setupEventListeners();
    },

    /**
     * Check if current context is admin, edit, preview, or customizer
     */
    isAdminOrEditContext: function () {
      // Check server-side admin context from PHP
      if (this.config && this.config.is_admin_context) {
        return true;
      }

      // Check if we're in an iframe (likely editor preview)
      if (window !== window.top) {
        return true;
      }

      // Check for Gutenberg editor context
      if (window.wp && window.wp.blockEditor) {
        return true;
      }

      // Check for edit context in URL
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get("context") === "edit") {
        return true;
      }

      // Check for WordPress admin body class
      if (document.body && document.body.classList.contains("wp-admin")) {
        return true;
      }

      // Check for customizer
      if (window.wp && window.wp.customize) {
        return true;
      }

      return false;
    },

    /**
     * Setup event listeners for the three tracking events
     */
    setupEventListeners: function () {
      if (!this.hasConsent) {
        return;
      }

      // Double-check admin context before setting up listeners
      if (this.isAdminOrEditContext()) {
        return;
      }

      // Track widget loads
      this.trackWidgetLoads();

      // Track widget clicks
      this.trackWidgetClicks();

      // Track button clicks
      this.trackButtonClicks();
    },

    /**
     * Track widget loads
     */
    trackWidgetLoads: function () {
      // Track existing widgets on page
      this.trackExistingWidgets();

      // Observer for dynamically loaded widgets
      this.observeNewWidgets();
    },

    /**
     * Track existing widgets on page load
     */
    trackExistingWidgets: function () {
      const widgets = this.findWidgets();

      widgets.each((index, widget) => {
        const $widget = $(widget);

        // Skip widgets that were already tracked server-side to prevent duplication
        if ($widget.data("tracked-server-side")) {
          return;
        }

        const widgetData = this.getWidgetData($widget);

        this.trackEvent("widget_load", widgetData.widget_id, {
          ...widgetData,
          load_type: "page_load",
          widget_index: index,
        });
      });
    },

    /**
     * Observe for dynamically loaded widgets
     */
    observeNewWidgets: function () {
      const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
          mutation.addedNodes.forEach((node) => {
            if (node.nodeType === 1) {
              // Element node
              const $node = $(node);
              const newWidgets = $node
                .find(this.getWidgetSelectors())
                .addBack(this.getWidgetSelectors());

              newWidgets.each((index, widget) => {
                const $widget = $(widget);

                // Skip widgets that were already tracked server-side to prevent duplication
                if ($widget.data("tracked-server-side")) {
                  return;
                }

                const widgetData = this.getWidgetData($widget);

                this.trackEvent("widget_load", widgetData.widget_id, {
                  ...widgetData,
                  load_type: "dynamic_load",
                  widget_index: index,
                });
              });
            }
          });
        });
      });

      observer.observe(document.body, {
        childList: true,
        subtree: true,
      });
    },

    /**
     * Track widget clicks (clicks anywhere on the widget)
     */
    trackWidgetClicks: function () {
      $(document).on("click", this.getWidgetSelectors(), (e) => {
        const $widget = $(e.currentTarget);

        // Skip if click was on a button or link (to avoid double tracking with button clicks)
        const $target = $(e.target);
        if (
          $target.is("button, a, .trip-button") ||
          $target.closest("button, a, .trip-button").length > 0
        ) {
          return;
        }

        const widgetData = this.getWidgetData($widget);

        this.trackEvent("widget_click", widgetData.widget_id, {
          ...widgetData,
          click_target: e.target.tagName.toLowerCase(),
          click_class: e.target.className,
          click_position: {
            x: e.pageX,
            y: e.pageY,
          },
        });
      });
    },

    /**
     * Track button clicks specifically
     */
    trackButtonClicks: function () {
      const buttonSelectors = [
        ".wetravel-book-now",
        ".book-now-btn",
        ".wetravel-button",
        ".wetravel-cta",
        "[data-wetravel-button]",
        ".wetravel-trips-container button",
        ".wetravel-trips-container .button",
        ".wetravel-trips-container .trip-button", // Grid view trip buttons
        '.wetravel-trips-container a[href*="book"]',
        '.wetravel-trips-container a[href*="reserve"]',
      ].join(", ");

      $(document).on("click", buttonSelectors, (e) => {
        const $button = $(e.currentTarget);
        const $widget = $button.closest(this.getWidgetSelectors());
        const widgetData = this.getWidgetData($widget);

        // Get trip UUID from button data attributes
        const tripUuid =
          $button.data("uuid") ||
          $button.data("trip-uuid") ||
          $button.closest("[data-trip-uuid]").data("trip-uuid") ||
          "";

        this.trackEvent("button_click", widgetData.widget_id, {
          ...widgetData,
          button_text: $button.text().trim(),
          button_href: $button.attr("href") || "",
          button_type: this.getButtonType($button),
          button_class: $button.attr("class") || "",
          trip_uuid: tripUuid,
          trip_id:
            tripUuid ||
            $button.data("trip-id") ||
            $button.closest("[data-trip-id]").data("trip-id") ||
            "",
          click_target: "button",
          layout_type: widgetData.display_type,
        });
      });

      // Track carousel and grid trip item clicks (these act as buttons)
      $(document).on("click", ".wetravel-trips-container .trip-item", (e) => {
        const $tripItem = $(e.currentTarget);
        const $widget = $tripItem.closest(this.getWidgetSelectors());

        // Skip if click was on a button element (to avoid double tracking)
        if ($(e.target).closest(".trip-button, button, a").length > 0) {
          return;
        }

        // Only track as button click for carousel and grid layouts where trip items are clickable
        const displayType = $widget.data("display-type") || "vertical";
        if (displayType !== "carousel" && displayType !== "grid") {
          return;
        }

        const widgetData = this.getWidgetData($widget);

        this.trackEvent("button_click", widgetData.widget_id, {
          ...widgetData,
          button_text:
            $tripItem.find(".trip-title").text().trim() || "Trip Item",
          button_href: $tripItem.attr("href") || "",
          button_type:
            widgetData.button_type || this.getCarouselButtonType($widget),
          button_class: $tripItem.attr("class") || "",
          trip_uuid: $tripItem.data("trip-uuid") || "",
          trip_id:
            $tripItem.data("trip-uuid") || $tripItem.data("trip-id") || "",
          click_target: "trip-item",
          layout_type: displayType,
        });
      });
    },

    /**
     * Get widget selectors
     */
    getWidgetSelectors: function () {
      return [
        ".wetravel-trips-container",
        "[data-wetravel-widget]",
        ".wetravel-widget",
        ".wetravel-trips-block",
      ].join(", ");
    },

    /**
     * Find all widgets on page
     */
    findWidgets: function () {
      return $(this.getWidgetSelectors());
    },

    /**
     * Get widget data for tracking
     */
    getWidgetData: function ($widget) {
      return {
        wt_user_id:
          $widget.data("wetravel-user-id") || $widget.data("user-id") || "",
        display_type: $widget.data("display-type") || "vertical",
        wt_widget_type:
          $widget.data("wetravel-widget-type") ||
          $widget.data("widget-type") ||
          "all-trips",
        integration_type: $widget.data("integration-type") || "block",
        button_type: $widget.data("button-type") || "book_now",
        trip_uuid: $widget.data("trip-uuid") || "",
        trip_type: $widget.data("trip-type") || "",
        page_url: window.location.href,
        page_title: document.title,
        widget_id: $widget.attr("id") || this.generateWidgetId($widget),
      };
    },

    /**
     * Generate widget ID if not present
     */
    generateWidgetId: function ($widget) {
      const classes = $widget.attr("class") || "";
      const index = this.findWidgets().index($widget);
      return (
        "widget_" + index + "_" + classes.replace(/\s+/g, "_").substr(0, 20)
      );
    },

    /**
     * Get button type based on button characteristics
     */
    getButtonType: function ($button) {
      const text = $button.text().toLowerCase();
      const href = $button.attr("href") || "";
      const classes = $button.attr("class") || "";

      if (
        text.includes("book") ||
        href.includes("book") ||
        classes.includes("book")
      ) {
        return "book_now";
      }
      if (text.includes("reserve") || href.includes("reserve")) {
        return "reserve";
      }
      if (
        text.includes("learn") ||
        text.includes("more") ||
        text.includes("detail")
      ) {
        return "learn_more";
      }
      if (text.includes("view") || text.includes("see")) {
        return "view_details";
      }

      return "other";
    },

    /**
     * Get button type for carousel/grid widgets from widget data
     */
    getCarouselButtonType: function ($widget) {
      return $widget.data("button-type") || "book_now";
    },

    /**
     * Main event tracking function
     */
    trackEvent: function (eventType, widgetId, eventData = {}) {
      if (!this.hasConsent) {
        return;
      }

      // Additional safety check for admin context
      if (this.isAdminOrEditContext()) {
        return;
      }

      // Add session and timestamp data
      const fullEventData = {
        ...eventData,
        user_agent: navigator.userAgent,
      };

      // Send to server
      $.ajax({
        url: this.config.ajaxurl,
        type: "POST",
        data: {
          action: "wetravel_track_event",
          nonce: this.config.nonce,
          event_type: eventType,
          widget_id: widgetId,
          event_data: fullEventData,
        },
        // No console messages for tracking success or failure
      });
    },
  };
})(jQuery);
