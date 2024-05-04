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
   * Check if the bottom of the events container is visible.
   *
   * @param {HTMLElement} el - The element to check if it's in the viewport.
   * @return {boolean} - True if the element is in the viewport, false otherwise.
   */
  function isElementInViewport(el) {
    // Get the rectangular coordinates of the element relative to the viewport
    var rect = el.getBoundingClientRect();

    // Check if the element is within 200 pixels of the bottom of the viewport
    return (
      rect.bottom + 200 <=
      (window.innerHeight || document.documentElement.clientHeight)
    );
  }

  /**
   * Event handler for when the user scrolls to the bottom of the page.
   */
  $(window).scroll(function () {
    // Check if the bottom of the events container is visible
    if (
      isElementInViewport($("#simple-events-container")[0]) &&
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
          $("#load-more-events").show();
        },
        success: function (data) {
          if (data.trim() === "No more events!") {
            // Set the flag to true as all events are loaded
            noMoreEvents = true;
          } else {
            // Append the new events to the existing events container
            $("#simple-events-container").append(data);
            // Increment the offset for the next set of events
            offset += 6;
          }
          // Hide the loading spinner
          $("#load-more-events").show();
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
          $("#load-more-events").hide();
        },
      });
    }
  });
});
