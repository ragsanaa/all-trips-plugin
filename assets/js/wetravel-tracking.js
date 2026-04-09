/**
 * WeTravel Widgets Tracking System - Frontend JavaScript
 *
 * Tracks widget loads with GDPR compliance
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
     * Setup event listeners for tracking events
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
