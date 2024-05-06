/**
 * Script for loading more events when the user scrolls to the bottom of the page.
 */
jQuery(document).ready(function ($) {
  // Initial number of posts is 9
  var offset = 9;
  // Flag to indicate if the user is currently loading more events
  var loading = false;
  // Flag to indicate if there are no more events to load
  var noMoreEvents = false;

  /**
   * Event handler for when the user scrolls to the bottom of the page.
   */
  $(window).scroll(function () {
    // Check if the bottom of the events container is visible
    if (
      $(window).scrollTop() + $(window).height() > $(document).height() - 100 &&
      !loading &&
      !noMoreEvents
    ) {
      loading = true;
      $.ajax({
        type: "POST",
        url: ajax_params.ajaxurl,
        data: {
          action: "load_more_events",
          nonce: ajax_params.nonce,
          offset: offset,
        },
        beforeSend: function () {
          // Show the loading spinner
          $(".simple-events-calendar").after('<div id="loader"><span class="spinner"></span></div>');
        },
        success: function (data) {
          if (data.trim() === "No events found") {
            // Set the flag to true as all events are loaded
            noMoreEvents = true;
          } else {
            // Append the new events to the existing events container
            $(".simple-events-calendar").append(data);
            // Increment the offset for the next set of events
            offset += 6;
          }
          // Set the loading flag to false
          loading = false;
        },
        error: function () {
          // Log an error message to the console
          console.error("Error loading more events");
          // Set the loading flag to false
          loading = false;
        },
        complete: function () {
          // Hide the loading spinner
          $("#loader").remove();
        },
      });
    }
  });
});
