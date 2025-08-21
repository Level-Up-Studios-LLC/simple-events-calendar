/**
 * Simple Events Calendar JavaScript
 * 
 * Handles infinite scroll loading of events with improved error handling
 * and user experience enhancements.
 */
jQuery(document).ready(function ($) {
  'use strict';

  // Configuration - use localized values if available
  const config = {
    initialOffset: (typeof ajax_params !== 'undefined' && ajax_params.initial_offset) ?
      parseInt(ajax_params.initial_offset, 10) : 6,
    loadIncrement: (typeof ajax_params !== 'undefined' && ajax_params.load_increment) ?
      parseInt(ajax_params.load_increment, 10) : 6,
    scrollThreshold: 100,
    maxRetries: 3,
    retryDelay: 2000
  };

  // State management
  let state = {
    offset: config.initialOffset,
    loading: false,
    noMoreEvents: false,
    retryCount: 0,
    container: $('.simple-events-calendar')
  };

  // Check if we have the required elements and AJAX parameters
  if (!state.container.length || typeof ajax_params === 'undefined') {
    return;
  }

  /**
   * Show loading spinner
   */
  function showLoader() {
    if ($('#simple-events-loader').length === 0) {
      const loader = $('<div id="simple-events-loader" class="simple-events-loader">' +
        '<div class="simple-events-spinner"></div>' +
        '<span class="simple-events-loading-text">Loading more events...</span>' +
        '</div>');

      state.container.after(loader);

      // Add smooth fade-in animation
      loader.hide().fadeIn(300);
    }
  }

  /**
   * Hide loading spinner
   */
  function hideLoader() {
    $('#simple-events-loader').fadeOut(300, function () {
      $(this).remove();
    });
  }

  /**
   * Show error message
   */
  function showError(message) {
    hideLoader();

    const errorDiv = $('<div id="simple-events-error" class="simple-events-error">' +
      '<p>' + message + '</p>' +
      '<button class="simple-events-retry-btn">Try Again</button>' +
      '</div>');

    state.container.after(errorDiv);

    // Handle retry button click
    errorDiv.find('.simple-events-retry-btn').on('click', function () {
      $('#simple-events-error').remove();
      state.retryCount = 0;
      loadMoreEvents();
    });
  }

  /**
   * Load more events via AJAX
   */
  function loadMoreEvents() {
    if (state.loading || state.noMoreEvents) {
      return;
    }

    state.loading = true;
    showLoader();

    $.ajax({
      type: 'POST',
      url: ajax_params.ajaxurl,
      data: {
        action: 'load_more_events',
        nonce: ajax_params.nonce,
        offset: state.offset
      },
      timeout: 15000, // 15 second timeout
      success: function (response) {
        handleLoadSuccess(response);
      },
      error: function (xhr, status, error) {
        handleLoadError(xhr, status, error);
      },
      complete: function () {
        hideLoader();
        state.loading = false;
      }
    });
  }

  /**
   * Handle successful AJAX response
   */
  function handleLoadSuccess(data) {
    // Reset retry count on success
    state.retryCount = 0;

    // Check if we received the special "no more events" response
    if (data && data.trim() === 'NO_MORE_EVENTS') {
      state.noMoreEvents = true;
      showNoMoreEventsMessage();
      return;
    }

    // Check if we received valid data
    if (!data || data.trim() === '' || data.trim() === 'No events found' || data.indexOf('No more events') !== -1) {
      state.noMoreEvents = true;
      showNoMoreEventsMessage();
      return;
    }

    // Parse and validate the response
    const $newEvents = $(data);
    if ($newEvents.length === 0) {
      state.noMoreEvents = true;
      showNoMoreEventsMessage();
      return;
    }

    // Add fade-in animation for new events
    $newEvents.hide();
    state.container.append($newEvents);
    $newEvents.fadeIn(600);

    // Update offset for next load
    state.offset += config.loadIncrement;

    // Remove any existing error messages
    $('#simple-events-error').remove();

    // Remove the scroll hint since we've loaded more events
    $('.simple-events-load-more-info').fadeOut();
  }

  /**
   * Handle AJAX errors with retry logic
   */
  function handleLoadError(xhr, status, error) {
    console.error('Simple Events AJAX Error:', {
      status: status,
      error: error,
      responseText: xhr.responseText
    });

    // Increment retry count
    state.retryCount++;

    // Determine error message based on error type
    let errorMessage = 'Unable to load more events. ';

    if (status === 'timeout') {
      errorMessage += 'The request timed out.';
    } else if (status === 'parsererror') {
      errorMessage += 'Invalid response from server.';
    } else if (xhr.status === 403) {
      errorMessage += 'Access denied.';
    } else if (xhr.status >= 500) {
      errorMessage += 'Server error occurred.';
    } else {
      errorMessage += 'Please check your connection.';
    }

    // Auto-retry for certain errors (up to max retries)
    if (state.retryCount < config.maxRetries && (status === 'timeout' || xhr.status >= 500)) {
      setTimeout(function () {
        loadMoreEvents();
      }, config.retryDelay);
      return;
    }

    // Show error message with retry button
    showError(errorMessage);
  }

  /**
   * Show "no more events" message
   */
  function showNoMoreEventsMessage() {
    if ($('#simple-events-end').length === 0) {
      const endMessage = $('<div id="simple-events-end" class="simple-events-end">' +
        '<p>🎉 You\'ve seen all our upcoming events!</p>' +
        '<p>Check back soon for new events.</p>' +
        '</div>');

      state.container.after(endMessage);
      endMessage.hide().fadeIn(600);
    }

    // Remove the scroll hint if it exists
    $('.simple-events-load-more-info').fadeOut();
  }

  /**
   * Check if user has scrolled near bottom of page
   */
  function isNearBottom() {
    const scrollTop = $(window).scrollTop();
    const windowHeight = $(window).height();
    const documentHeight = $(document).height();

    return (scrollTop + windowHeight) > (documentHeight - config.scrollThreshold);
  }

  /**
   * Check if user has scrolled past the events container
   */
  function isScrolledPastEvents() {
    if (!state.container.length) return false;

    const containerBottom = state.container.offset().top + state.container.outerHeight();
    const scrollPosition = $(window).scrollTop() + $(window).height();

    return scrollPosition > (containerBottom - config.scrollThreshold);
  }

  /**
   * Throttle function to limit scroll event frequency
   */
  function throttle(func, limit) {
    let inThrottle;
    return function () {
      const args = arguments;
      const context = this;
      if (!inThrottle) {
        func.apply(context, args);
        inThrottle = true;
        setTimeout(() => inThrottle = false, limit);
      }
    };
  }

  /**
   * Handle scroll events (throttled)
   */
  const handleScroll = throttle(function () {
    // Use the more specific scroll detection for better UX
    if ((isNearBottom() || isScrolledPastEvents()) && !state.loading && !state.noMoreEvents) {
      loadMoreEvents();
    }
  }, 250);

  /**
   * Handle load more button clicks (for shortcode implementation)
   */
  function handleLoadMoreButton() {
    $(document).on('click', '.simple-events-load-more', function (e) {
      e.preventDefault();

      const $button = $(this);
      const offset = parseInt($button.data('offset'), 10) || state.offset;

      // Update state offset if different
      if (offset !== state.offset) {
        state.offset = offset;
      }

      // Load more events
      loadMoreEvents();

      // Hide the button after clicking
      $button.closest('.simple-events-load-more-container').fadeOut();
    });
  }

  /**
   * Initialize scroll listener
   */
  function initScrollListener() {
    // Only attach scroll listener if we have events container
    if (state.container.length > 0) {
      $(window).on('scroll', handleScroll);
    }
  }

  /**
   * Initialize the plugin
   */
  function init() {
    // Set up event listeners
    initScrollListener();
    handleLoadMoreButton();

    // Add CSS classes for styling
    $('body').addClass('simple-events-js-enabled');

    // Initial check if content is shorter than viewport
    setTimeout(function () {
      if ($(document).height() <= $(window).height() && !state.noMoreEvents) {
        loadMoreEvents();
      }
    }, 100);
  }

  /**
   * Cleanup function for page unload
   */
  function cleanup() {
    $(window).off('scroll', handleScroll);
    $('#simple-events-loader, #simple-events-error, #simple-events-end').remove();
  }

  // Initialize when DOM is ready
  init();

  // Cleanup on page unload
  $(window).on('beforeunload', cleanup);

  // Expose public methods for external use
  window.SimpleEventsCalendar = {
    loadMore: loadMoreEvents,
    reset: function () {
      state.offset = config.initialOffset;
      state.loading = false;
      state.noMoreEvents = false;
      state.retryCount = 0;
      cleanup();
      init();
    }
  };
});