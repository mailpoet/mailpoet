define(
  ['mailpoet'],
  function(MailPoet) {

    MailPoet.trackEvent = cacheEvent;
    MailPoet.forceTrackEvent = cacheEvent;

    initializeMixpanelWhenLoaded();
  }
);

var eventsCache = [];
function cacheEvent(name, data) {
  eventsCache.push({
    name: name,
    data: data
  });
}

function initializeMixpanelWhenLoaded() {
  if (typeof window.mixpanel === "object" && typeof window.mixpanel.track === "function") {
    exportMixpanel(MailPoet);
    trackCachedEvents(eventsCache);
  } else {
    setTimeout(initializeMixpanelWhenLoaded, 100);
  }
}

function track(name, data){
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

function trackCachedEvents(eventsCache) {
  if (window.mailpoet_analytics_enabled) {
    eventsCache.map(function (event) {
      window.mixpanel.track(event.name, event.data)
    });
  }
}