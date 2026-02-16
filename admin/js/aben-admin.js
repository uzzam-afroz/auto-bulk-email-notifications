(function ($) {
  "use strict";

  $(document).ready(function () {
    var mediaUploader;

    // Handler for the media uploader button click
    $(document).on("click", ".aben-media-upload-button", function (e) {
      e.preventDefault();

      var $button = $(this);
      var $input = $button.siblings('input[type="hidden"]');
      var $preview = $button.siblings("img");
      var $removeButton = $button.siblings(".aben-media-remove-button");

      if (typeof wp === "undefined" || typeof wp.media === "undefined") {
        console.error("wp.media is not defined.");
        return;
      }

      if (mediaUploader) {
        mediaUploader.open();
        return;
      }

      mediaUploader = wp.media({
        title: "Select or Upload Image",
        button: {
          text: "Use this image",
        },
        multiple: false,
      });

      mediaUploader.on("select", function () {
        var attachment = mediaUploader.state().get("selection").first().toJSON();
        $input.val(attachment.url);
        $preview.attr("src", attachment.url).show();
        $removeButton.text("X");
        $removeButton.css("color", "red");
        $removeButton.show(); // Show the Remove Image button
      });

      mediaUploader.open();
    });

    // Handler for the remove image button click
    $(document).on("click", ".aben-media-remove-button", function (e) {
      e.preventDefault();

      var $button = $(this);
      var $input = $button.siblings('input[type="hidden"]');
      var $preview = $button.siblings("img");

      $input.val(""); // Clear the input value
      $preview.attr("src", "").hide(); // Hide the image preview
      $button.hide(); // Hide the Remove Image button
    });

    // Initial display setup for post tiles (show/hide based on the initial value)
    const numberOfPosts = $("#aben_options_number_of_posts").val();
    $(".post-tile").each(function (index) {
      if (index < numberOfPosts) {
        $(this).fadeIn(); // Show the first n posts based on the input value
      } else {
        $(this).hide(); // Hide the rest
      }
    });
  });

  // Map input field IDs to corresponding elements and their actions
  const elementsMap = {
    aben_options_header_text: {
      target: "#header-text",
      action: (el, value) => el.html(`<strong>${value}</strong>`),
    },
    aben_options_header_subtext: {
      target: "#header-subtext",
      action: (el, value) => el.text(value),
    },
    aben_options_footer_text: {
      target: "#footer-text",
      action: (el, value) => el.text(value),
    },
    aben_options_body_bg: {
      target: "#aben-email-template",
      action: (el, value) => el.css("background-color", value),
    },
    aben_options_header_bg: {
      target: ".post-tile",
      action: (el, value) => el.closest("div").css("background-color", value),
    },
    aben_options_view_post_text: {
      target: ".view-post",
      action: (el, value) => $(".view-post").text(value),
    },

    aben_options_show_view_post: {
      target: ".view-post",
      action: (el, value) => $(".view-post").css("display", value ? "inline-block" : "none"),
      checkbox: true,
    },
    aben_options_show_unsubscribe: {
      target: "#unsubscribe",
      action: (el, value) => $("#unsubscribe").css("display", value ? "inline-block" : "none"),
      checkbox: true,
    },
    aben_options_view_all_posts_text: {
      target: "#view-all-post",
      action: (el, value) => el.text(value),
    },
    aben_options_archive_page_slug: {
      target: "#view-all-post",
      action: (el, value) => el.attr("href", value),
    },
    aben_options_show_view_all: {
      target: "#view-all-post",
      action: (el, value) => {
        el.css("display", value ? "inline-block" : "none"); // Show/hide the view-all-post link
      },
      checkbox: true,
    },

    aben_options_number_of_posts: {
      target: ".post-tile",
      action: (el, value) => {
        const numberOfPosts = parseInt(value, 10);
        $(".post-tile").each(function (index) {
          if (index < numberOfPosts) {
            $(this).fadeIn(); // Show the desired number of posts
          } else {
            $(this).fadeOut(); // Hide excess posts
          }
        });
      },
    },
    // Event Email Options
    aben_event_options_template_header_text: {
      target: "#header-text-event",
      action: (el, value) => el.html(`${value}`),
    },
    aben_event_options_template_button_text: {
      target: "#button-text-event",
      action: (el, value) => el.text(value),
    },
    aben_event_options_template_footer_text: {
      target: "#footer-text-event",
      action: (el, value) => el.text(value),
    },
    aben_event_options_template_header_bg: {
      target: "#email-header",
      action: (el, value) => el.css("background-color", value),
    },
    aben_event_options_template_body_bg: {
      target: "#email-container",
      action: (el, value) => el.css("background-color", value),
    },
    aben_event_options_template_content_bg: {
      target: "#email-body",
      action: (el, value) => el.css("background-color", value),
    },
    aben_event_options_template_button_bg: {
      target: "#button-text-event",
      action: (el, value) => el.css("background-color", value),
    },
    aben_event_options_template_button_text_color: {
      target: "#button-text-event",
      action: (el, value) => el.css("color", value),
    },
    aben_event_options_template_footer_bg: {
      target: "#email-footer",
      action: (el, value) => el.css("background-color", value),
    },
    aben_event_options_template_show_button: {
      target: "#button-text-event",
      action: (el, value) => $("#button-text-event").css("display", value ? "block" : "none"),
      checkbox: true,
    },
  };

  // General function to handle input changes
  function handleInputChange(event) {
    const inputId = $(event.target).attr("id");
    const map = elementsMap[inputId];
    if (map) {
      const targetElement = $(map.target);
      const value = map.checkbox ? $(event.target).is(":checked") : $(event.target).val();
      map.action(targetElement, value);
    }
  }

  // Attach event listeners to all inputs in the elementsMap
  $.each(elementsMap, function (inputId, map) {
    $(`#${inputId}`).on("input change", handleInputChange);
  });

  // Trigger the update on page load to reflect current values
  $.each(elementsMap, function (inputId, map) {
    const inputElement = $(`#${inputId}`);
    handleInputChange({ target: inputElement });
  });

  $("#aben_options_day_of_week").closest("tr").hide();

  // Function to toggle the visibility of the "Which day" field
  function toggleDayOfWeekField() {
    const emailFrequency = $("#aben_options_email_frequency").val();
    if (emailFrequency === "once_in_a_week") {
      $("#aben_options_day_of_week").closest("tr").show();
    } else {
      $("#aben_options_day_of_week").closest("tr").hide();
    }
  }

  // Attach change event listener
  $("#aben_options_email_frequency").change(toggleDayOfWeekField);

  // Trigger on page load to set initial state
  toggleDayOfWeekField();

  //Change {{USERNAME}} current user name
  if (typeof currentUserData !== "undefined") {
    // Get the first word of the user's name
    var firstName = currentUserData.user_name.split(" ")[0];

    // Replace {{USERNAME}} inside the <strong> tag within p#header-text with the first name
    $("#header-text strong").each(function () {
      var currentText = $(this).text();
      $(this).text(currentText.replace("{{USERNAME}}", firstName));
    });

    $("#header-text-event").each(function () {
      var currentText = $(this).text();
      $(this).text(currentText.replace("{{USERNAME}}", firstName));
    });
  }
})(jQuery);

const removeBranding = document.querySelector("#aben-email-tab-grid table.form-table tbody > tr:last-child th:has(a#aben_remove_branding)");
if (removeBranding) {
  removeBranding.setAttribute("colspan", "2");
  removeBranding.setAttribute("title", "Remove Powered by Aben from Email Footer");
}

// ============================================
// Email Provider Field Visibility Toggle
// ============================================
(function ($) {
  "use strict";

  $(document).ready(function () {
    // Function to toggle provider-specific fields
    function toggleProviderFields() {
      var provider = $("#aben_options_email_provider").val();

      // Get all SMTP fields
      var smtpFields = [
        "#aben_options_smtp_host",
        "#aben_options_smtp_port",
        "#aben_options_smtp_encryption",
        "#aben_options_smtp_username",
        "#aben_options_smtp_password",
      ];

      // Get all ToSend fields
      var tosendFields = ["#aben_options_tosend_api_key", "#aben_options_tosend_from_email"];

      // Common fields (always visible)
      var commonFields = ["#aben_options_from_name", "#aben_options_from_email"];

      // Hide all provider-specific fields first
      $.each(smtpFields.concat(tosendFields), function (index, fieldId) {
        $(fieldId).closest("tr").hide();
      });

      // Always show common fields
      $.each(commonFields, function (index, fieldId) {
        $(fieldId).closest("tr").show();
      });

      // Show fields based on selected provider
      if (provider === "smtp") {
        $.each(smtpFields, function (index, fieldId) {
          $(fieldId).closest("tr").show();
        });
      } else if (provider === "tosend") {
        $.each(tosendFields, function (index, fieldId) {
          $(fieldId).closest("tr").show();
        });
      }
    }

    // Run on page load
    toggleProviderFields();

    // Run on provider selection change
    $("#aben_options_email_provider").on("change", function () {
      toggleProviderFields();
    });
  });
})(jQuery);
