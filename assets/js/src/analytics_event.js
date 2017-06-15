define(
  ['mailpoet'],
  function(MailPoet) {

    const eventsCache = [];
    MailPoet.trackEvent = function(name, data) {
      eventsCache.push({
        name: name,
        data: data
      });
    };

    function waitForMixpanelInitialised() {
      if (window.mixpanel === undefined) {
        setTimeout(waitForMixpanelInitialised, 100)
      } else {
        exportMixpanel(MailPoet);
        trackCachedEvents(eventsCache);
      }
    }
    waitForMixpanelInitialised();
  }
);

function exportMixpanel(MailPoet) {
  if (window.mailpoet_analytics_enabled) {
    MailPoet.trackEvent = function(name, data){
      window.mixpanel.track(name, data);
    }
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