/**
 * Simple Events Calendar JavaScript
 *
 * Infinite-scroll loading of events. Supports multiple independent calendars
 * on the same page (e.g. several [sec_events] shortcodes, or an archive plus a
 * shortcode) — each container tracks its own offset/state and continues its own
 * query context (category / order / past-events / display flags).
 */
jQuery(document).ready(($) => {
  'use strict';

  if (typeof ajax_params === 'undefined') {
    return;
  }

  const config = {
    initialOffset: Number.parseInt(ajax_params.initial_offset, 10) || 6,
    loadIncrement: Number.parseInt(ajax_params.load_increment, 10) || 6,
    scrollThreshold: 100,
    maxRetries: 3,
    retryDelay: 2000,
  };

  /**
   * Controller for a single calendar container.
   *
   * @param {jQuery} $container The .simple-events-calendar element.
   */
  function createCalendar($container) {
    const ctx = {
      category: $container.attr('data-category') || '',
      order: ($container.attr('data-order') || 'ASC').toUpperCase(),
      showPast: $container.attr('data-show-past') || 'false',
      showTime: $container.attr('data-show-time') || 'true',
      showExcerpt: $container.attr('data-show-excerpt') || 'true',
      showLocation: $container.attr('data-show-location') || 'true',
      showFooter: $container.attr('data-show-footer') || 'true',
    };

    const startOffset = Number.parseInt($container.attr('data-offset'), 10);

    const state = {
      offset: Number.isNaN(startOffset) ? config.initialOffset : startOffset,
      loading: false,
      noMore: false,
      retry: 0,
      $loader: null,
    };

    function showLoader() {
      if (!state.$loader) {
        const loadingText = ajax_params.loading_text || 'Loading more events...';
        state.$loader = $(
          `<div class="simple-events-loader">` +
            `<div class="simple-events-spinner"></div>` +
            `<span class="simple-events-loading-text">${loadingText}</span>` +
            `</div>`
        );
        $container.after(state.$loader);
        state.$loader.hide().fadeIn(300);
      }
    }

    function hideLoader() {
      if (state.$loader) {
        const $l = state.$loader;
        state.$loader = null;
        $l.fadeOut(300, function () { $(this).remove(); });
      }
    }

    function removeMessages() {
      // Scope to this calendar's own messages — stop at the next calendar so
      // we never remove messages belonging to another calendar on the page.
      $container.nextUntil('.simple-events-calendar', '.simple-events-error, .simple-events-end').remove();
    }

    function showError(message) {
      hideLoader();
      const $err = $(
        `<div class="simple-events-error"><p></p>` +
          `<button class="simple-events-retry-btn"></button></div>`
      );
      $err.find('p').text(message);
      $err.find('button').text(ajax_params.retry_text || 'Try Again');
      $container.after($err);
      $err.find('.simple-events-retry-btn').on('click', () => {
        $err.remove();
        state.retry = 0;
        load();
      });
    }

    function showEnd() {
      if (!$container.nextUntil('.simple-events-calendar', '.simple-events-end').length) {
        const $end = $('<div class="simple-events-end"><p></p></div>');
        $end.find('p').text(ajax_params.no_more_text || 'No more events to load.');
        $container.after($end);
        $end.hide().fadeIn(600);
      }
    }

    function onSuccess(response) {
      state.retry = 0;

      if (!response || response.success !== true || !response.data) {
        state.noMore = true;
        showEnd();
        return;
      }

      const html = typeof response.data.html === 'string' ? response.data.html : '';
      const hasMore = response.data.has_more !== false && html.trim() !== '';

      if (!hasMore) {
        state.noMore = true;
        showEnd();
        return;
      }

      const $new = $(html);
      if (!$new.length) {
        state.noMore = true;
        showEnd();
        return;
      }

      $new.hide();
      $container.append($new);
      $new.fadeIn(600);
      state.offset += config.loadIncrement;
      removeMessages();
    }

    function onError(xhr, status) {
      state.retry++;
      const serverMessage = xhr?.responseJSON?.data?.message ?? '';
      let message = 'Unable to load more events. ';
      if (serverMessage) {
        message += serverMessage;
      } else if (status === 'timeout') {
        message += 'The request timed out.';
      } else if (xhr.status === 403) {
        message += 'Access denied.';
      } else if (xhr.status >= 500) {
        message += 'Server error occurred.';
      } else {
        message += 'Please check your connection.';
      }

      if (state.retry < config.maxRetries && (status === 'timeout' || xhr.status >= 500)) {
        setTimeout(load, config.retryDelay);
        return;
      }
      showError(message);
    }

    function load() {
      if (state.loading || state.noMore) {
        return;
      }
      state.loading = true;
      showLoader();

      $.ajax({
        type: 'POST',
        url: ajax_params.ajaxurl,
        dataType: 'json',
        timeout: 15000,
        data: {
          action: 'load_more_events',
          nonce: ajax_params.nonce,
          offset: state.offset,
          category: ctx.category,
          order: ctx.order,
          show_past: ctx.showPast,
          show_time: ctx.showTime,
          show_excerpt: ctx.showExcerpt,
          show_location: ctx.showLocation,
          show_footer: ctx.showFooter,
        },
        success: onSuccess,
        error: onError,
        complete: () => {
          hideLoader();
          state.loading = false;
        },
      });
    }

    function shouldLoad() {
      if (state.loading || state.noMore || !$container.length) {
        return false;
      }
      const containerBottom = $container.offset().top + $container.outerHeight();
      const viewportBottom = $(window).scrollTop() + $(window).height();
      return viewportBottom > (containerBottom - config.scrollThreshold);
    }

    return { $container, state, load, shouldLoad };
  }

  // Build a controller only for containers that opt into load-more
  // (data-sec-loadmore="1"): the [sec_events] shortcode, the archive/taxonomy
  // templates, and the Events Grid widget when its toggle is on. This keeps the
  // controller off single-event cards and fixed-count grids.
  const calendars = [];
  $('.simple-events-calendar[data-sec-loadmore="1"]').not('.simple-events-no-events').each((_, el) => {
    calendars.push(createCalendar($(el)));
  });

  if (!calendars.length) {
    return;
  }

  // Leading + trailing throttle. A leading-only throttle drops the final
  // scroll event when the user flicks to the bottom and stops mid-window, so
  // the resting position is never re-checked (load-more never fires until the
  // next scroll). The trailing call re-evaluates the final position.
  function throttle(fn, limit) {
    let lastRun = 0;
    let timer = null;
    return function (...args) {
      const now = Date.now();
      const remaining = limit - (now - lastRun);
      if (remaining <= 0) {
        if (timer) {
          clearTimeout(timer);
          timer = null;
        }
        lastRun = now;
        fn.apply(this, args);
      } else if (!timer) {
        timer = setTimeout(() => {
          lastRun = Date.now();
          timer = null;
          fn.apply(this, args);
        }, remaining);
      }
    };
  }

  const handleScroll = throttle(() => {
    calendars.forEach((cal) => {
      if (cal.shouldLoad()) {
        cal.load();
      }
    });
  }, 250);

  $(window).on('scroll', handleScroll);

  // Optional "load more" button: drive the nearest preceding calendar.
  $(document).on('click', '.simple-events-load-more', (e) => {
    e.preventDefault();
    const $btn = $(e.currentTarget);
    let $cal = $btn.prevAll('.simple-events-calendar').first();
    if (!$cal.length) {
      $cal = $btn.closest('.simple-events-load-more-container').prevAll('.simple-events-calendar').first();
    }
    calendars.forEach((cal) => {
      if (cal.$container.is($cal)) {
        cal.load();
      }
    });
    $btn.closest('.simple-events-load-more-container').fadeOut();
  });

  $('body').addClass('simple-events-js-enabled');

  // If the initial content is shorter than the viewport, prime each calendar.
  setTimeout(() => {
    if ($(document).height() <= $(window).height()) {
      calendars.forEach((cal) => cal.load());
    }
  }, 100);

  // Public API.
  window.SimpleEventsCalendar = {
    loadMore() {
      calendars.forEach((cal) => cal.load());
    },
    calendars,
  };
});
