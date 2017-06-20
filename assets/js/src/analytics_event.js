/*
 * This creates two functions and adds them to MailPoet object
 * - `trackEvent` which should be used in normal circumstances.
 *   This function tracks an event and sends it to mixpanel.
 *   This function does nothing if analytics is disabled.
 * - `forceTrackEvent` which sends given event to analytics
 *   even if it has been disabled.
 */

var eventsCache = [];

function track(name, data){
  if (typeof window.mixpanel.track !== "function") {
    window.mixpanel.init(window.mixpanelTrackingId);
  }
  window.mixpanel.track(name, data);
}

function exportMixpanel(MailPoet) {
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
      window.mixpanel.track(event.name, event.data)
    }
  });
}

function initializeMixpanelWhenLoaded() {
  if (typeof window.mixpanel === "object") {
    exportMixpanel(MailPoet);
    trackCachedEvents();
  } else {
    setTimeout(initializeMixpanelWhenLoaded, 100);
  }
}

function cacheEvent(forced, name, data) {
  eventsCache.push({
    name: name,
    data: data,
    forced: forced,
  });
}

define(
  ['mailpoet', 'underscore'],
  function(MailPoet, _) {

    MailPoet.trackEvent = _.partial(cacheEvent, false);
    MailPoet.forceTrackEvent = _.partial(cacheEvent, true);

    initializeMixpanelWhenLoaded();
  }
);
