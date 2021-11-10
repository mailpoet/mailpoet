/*
 * This creates two functions and adds them to MailPoet object
 * - `trackEvent` which should be used in normal circumstances.
 *   This function tracks an event and sends it to mixpanel.
 *   This function does nothing if analytics is disabled.
 * - `forceTrackEvent` which sends given event to analytics
 *   even if it has been disabled.
 *
 */
import MailPoet from 'mailpoet';
import _ from 'underscore';

/**
 *  This is to cache events which are triggered before the mixpanel
 *  library is loaded. This might happen if an event is tracked
 *  on page load and the mixpanel library takes a long time to load.
 *  After it is loaded all events are posted.
 * @type {Array.Object}
 */
var eventsCache = [];

function track(name, data = []) {
  let trackedData = data;

  if (typeof window.mixpanel.track !== 'function') {
    window.mixpanel.init(window.mixpanelTrackingId);
  }

  if (typeof window.mailpoet_version !== 'undefined') {
    trackedData['MailPoet Free version'] = window.mailpoet_version;
  }

  if (typeof window.mailpoet_premium_version !== 'undefined') {
    trackedData['MailPoet Premium version'] = window.mailpoet_premium_version;
  }

  window.mixpanel.track(name, trackedData);
}

function exportMixpanel() {
  MailPoet.forceTrackEvent = track;

  if (window.mailpoet_analytics_enabled && MailPoet.libs3rdPartyEnabled) {
    MailPoet.trackEvent = track;
  } else {
    MailPoet.trackEvent = function emptyFunction() {};
  }
}

function trackCachedEvents() {
  eventsCache.forEach(function trackIfEnabled(event) {
    if (window.mailpoet_analytics_enabled || event.forced) {
      window.mixpanel.track(event.name, event.data);
    }
  });
}

function cacheEvent(forced, name, data) {
  eventsCache.push({
    name: name,
    data: data,
    forced: forced,
  });
}

function initializeMixpanelWhenLoaded() {
  if (typeof window.mixpanel === 'object') {
    exportMixpanel();
    trackCachedEvents();
  } else {
    setTimeout(initializeMixpanelWhenLoaded, 100);
  }
}

MailPoet.trackEvent = _.partial(cacheEvent, false);
MailPoet.forceTrackEvent = _.partial(cacheEvent, true);

initializeMixpanelWhenLoaded();
