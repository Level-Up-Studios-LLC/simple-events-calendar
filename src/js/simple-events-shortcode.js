/**
 * Simple Events Calendar Shortcode JavaScript
 *
 * Handles shortcode-specific functionality
 */

(function($) {
  'use strict';

  // Initialize when DOM is ready
  $(document).ready(function() {
    initShortcodeFunctionality();
  });

  /**
   * Initialize shortcode functionality
   */
  function initShortcodeFunctionality() {
    // Add any shortcode-specific initialization here
    console.log('Simple Events Calendar shortcode initialized');

    // Example: Handle shortcode-specific interactions
    $('.simple-events-calendar[data-shortcode="true"]').each(function() {
      const $container = $(this);

      // Get display options from data attributes
      const showTime = $container.data('show-time') === true;
      const showExcerpt = $container.data('show-excerpt') === true;
      const showLocation = $container.data('show-location') === true;
      const showFooter = $container.data('show-footer') === true;

      // Apply display options
      applyDisplayOptions($container, {
        showTime,
        showExcerpt,
        showLocation,
        showFooter
      });
    });
  }

  /**
   * Apply display options to shortcode container
   *
   * @param {jQuery} $container Shortcode container
   * @param {Object} options Display options
   */
  function applyDisplayOptions($container, options) {
    if (!options.showTime) {
      $container.addClass('hide-time');
    }

    if (!options.showExcerpt) {
      $container.addClass('hide-excerpt');
    }

    if (!options.showLocation) {
      $container.addClass('hide-location');
    }

    if (!options.showFooter) {
      $container.addClass('hide-footer');
    }
  }

})(jQuery);