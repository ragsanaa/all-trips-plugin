/**
 * WeTravel Plugin Deactivation Form JavaScript
 */
(function ($) {
  "use strict";

  // Placeholder texts for different feedback reasons
  const placeholderTexts = {
    couldnt_understand: "Would you like us to assist you?",
    missing_feature: "Could you tell us more about that feature?",
    not_working: "Could you tell us a bit more whats not working?",
    not_what_looking_for: "Could you tell us a bit more?",
    not_work_expected: "What did you expect?",
    other: "Could you tell us a bit more?",
  };

  let deactivationLink = "";

  $(document).ready(function () {
    // Initialize deactivation form functionality
    initDeactivationForm();
  });

  function initDeactivationForm() {
    // Find the WeTravel plugin deactivation link
    const $deactivateLink = $(
      'tr[data-plugin="' + wetravelDeactivation.plugin_slug + '"] .deactivate a'
    );

    if ($deactivateLink.length === 0) {
      return;
    }

    // Store the original deactivation link
    deactivationLink = $deactivateLink.attr("href");

    // Intercept deactivation click
    $deactivateLink.on("click", function (e) {
      e.preventDefault();
      showDeactivationModal();
    });

    // Handle feedback option selection
    $(document).on("change", 'input[name="feedback_reason"]', function () {
      const selectedReason = $(this).val();
      updatePlaceholderText(selectedReason);
    });

    // Handle modal actions
    $("#wetravel-cancel-deactivate").on("click", hideDeactivationModal);
    $("#wetravel-skip-deactivate").on("click", skipAndDeactivate);
    $("#wetravel-submit-deactivate").on("click", submitFeedbackAndDeactivate);

    // Close modal when clicking outside
    $(document).on("click", "#wetravel-deactivation-modal", function (e) {
      if (e.target === this) {
        hideDeactivationModal();
      }
    });

    // Close modal on Escape key
    $(document).on("keydown", function (e) {
      if (
        e.keyCode === 27 &&
        $("#wetravel-deactivation-modal").is(":visible")
      ) {
        hideDeactivationModal();
      }
    });
  }

  function showDeactivationModal() {
    $("#wetravel-deactivation-modal").fadeIn(300);
    $("body").addClass("wetravel-modal-open");

    // Reset form
    $("#wetravel-deactivation-form")[0].reset();
    updatePlaceholderText("couldnt_understand");

    // Focus on first radio button
    $('input[name="feedback_reason"]:first').focus();
  }

  function hideDeactivationModal() {
    $("#wetravel-deactivation-modal").fadeOut(300);
    $("body").removeClass("wetravel-modal-open");
  }

  function updatePlaceholderText(reason) {
    const placeholder = placeholderTexts[reason] || placeholderTexts["other"];
    $("#wetravel-feedback-text").attr("placeholder", placeholder);
  }

  function skipAndDeactivate() {
    // Send tracking data for skip action
    const skipData = {
      action: "wetravel_deactivation_feedback",
      nonce: wetravelDeactivation.nonce,
      feedback_reason: "skipped",
      feedback_text: "",
    };

    // Send skip tracking in background, then deactivate regardless of result
    $.ajax({
      url: wetravelDeactivation.ajax_url,
      type: "POST",
      data: skipData,
      complete: function () {
        // Always deactivate after tracking attempt (success or failure)
        window.location.href = deactivationLink;
      },
    });
  }

  function submitFeedbackAndDeactivate() {
    const $submitBtn = $("#wetravel-submit-deactivate");
    const originalText = $submitBtn.text();

    // Get form data
    const selectedReason = $('input[name="feedback_reason"]:checked').val();
    const feedbackText = $("#wetravel-feedback-text").val().trim();

    // Validate - at least a reason must be selected
    if (!selectedReason) {
      alert("Please select a reason for deactivation.");
      return;
    }

    // Show loading state
    $submitBtn.text("Submitting...").prop("disabled", true);

    // Prepare data for submission
    const feedbackData = {
      action: "wetravel_deactivation_feedback",
      nonce: wetravelDeactivation.nonce,
      feedback_reason: selectedReason,
      feedback_text: feedbackText,
    };

    // Submit feedback via AJAX
    $.ajax({
      url: wetravelDeactivation.ajax_url,
      type: "POST",
      data: feedbackData,
      success: function (response) {
        if (response.success) {
          // Show success message briefly
          $submitBtn.text("Thank you!");

          // Deactivate the plugin after a short delay
          setTimeout(function () {
            window.location.href = deactivationLink;
          }, 1000);
        } else {
          // Handle error
          console.error("Feedback submission failed:", response);
          $submitBtn.text(originalText).prop("disabled", false);
          alert(
            "Failed to submit feedback. You can still deactivate the plugin."
          );
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX error:", error);
        $submitBtn.text(originalText).prop("disabled", false);

        // Still allow deactivation even if feedback fails
        if (
          confirm(
            "Failed to submit feedback. Would you like to deactivate anyway?"
          )
        ) {
          window.location.href = deactivationLink;
        }
      },
    });
  }
})(jQuery);
