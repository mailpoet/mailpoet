/*
 * This creates two functions and adds them to MailPoet object
 * - `trackEvent` which should be used in normal circumstances.
 *   This function tracks an event and sends it to mixpanel.
 *   This function does nothing if analytics is disabled.
 * - `forceTrackEvent` which sends given event to analytics
 *   even if it has been disabled.
 *
 */

/**
 *  This is to cache events which are triggered before the mixpanel
 *  library is loaded. This might happen if an event is tracked
 *  on page load and the mixpanel library takes a long time to load.
 *  After it is loaded all events are posted.
 * @type {Array.Object}
 */
var eventsCache = [];

function track(name, data){
  if (typeof window.mixpanel.track !== 'function') {
    window.mixpanel.init(window.mixpanelTrackingId);
  }
  window.mixpanel.track(name, data);
}

function exportMixpanel(mp) {
  var MailPoet = mp;
  MailPoet.forceTrackEvent = track;

  if (window.mailpoet_analytics_enabled) {
    MailPoet.trackEvent = track;
  } else {
    MailPoet.trackEvent = function () {};
  }
}

function trackCachedEvents() {
  eventsCache.map(function (event) {
    if (window.mailpoet_analytics_enabled || event.forced) {
      window.mixpanel.track(event.name, event.data);
    }
  });
}

function cacheEvent(forced, name, data) {
  eventsCache.push({
    name: name,
    data: data,
    forced: forced
  });
}

define(
  ['mailpoet', 'underscore'],
  function (mp, _) {
    var MailPoet = mp;

    function initializeMixpanelWhenLoaded() {
      if (typeof window.mixpanel === 'object') {
        exportMixpanel(MailPoet);
        trackCachedEvents();
      } else {
        setTimeout(initializeMixpanelWhenLoaded, 100);
      }
    }

    MailPoet.trackEvent = _.partial(cacheEvent, false);
    MailPoet.forceTrackEvent = _.partial(cacheEvent, true);

    initializeMixpanelWhenLoaded();
  }
);
