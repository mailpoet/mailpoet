/*
 * This creates two functions and adds them to MailPoet object
 * - `trackEvent` which should be used in normal circumstances.
 *   This function tracks an event and sends it to mixpanel.
 *   This function does nothing if analytics is disabled.
 * - `forceTrackEvent` which sends given event to analytics
 *   even if it has been disabled.
 *
 */
import _ from 'underscore';

/**
 *  This is to cache events which are triggered before the mixpanel
 *  library is loaded. This might happen if an event is tracked
 *  on page load and the mixpanel library takes a long time to load.
 *  After it is loaded all events are posted.
 * @type {Array.Object}
 */
var eventsCache = [];

const LOCALSTORAGE_KEY = 'mailpoet-track-events-cache';
export const CacheEventOptionSaveInStorage = 'saveInStorage';

function track(name, data = [], options = {}, callback = null) {
  let trackedData = data;

  const optionsData = options === CacheEventOptionSaveInStorage ? {} : options;

  if (typeof window.mixpanel.track !== 'function') {
    window.mixpanel.init(window.mixpanelTrackingId, window.mixpanelInitConfig);
  }

  if (typeof window.mailpoet_version !== 'undefined') {
    trackedData['MailPoet Free version'] = window.mailpoet_version;
  }

  if (typeof window.mailpoet_premium_version !== 'undefined') {
    trackedData['MailPoet Premium version'] = window.mailpoet_premium_version;
  }

  // Fallback when Mixpanel is not loaded (e.g., blocked by AdBlock)
  if (!window.mixpanelIsLoaded) {
    if (callback) {
      callback();
    }
  } else {
    window.mixpanel.track(name, trackedData, optionsData, callback);
  }
}

function exportMixpanel() {
  window.MailPoet.forceTrackEvent = track;

  if (
    window.mailpoet_analytics_enabled &&
    window.MailPoet.libs3rdPartyEnabled
  ) {
    window.MailPoet.trackEvent = track;
  } else {
    window.MailPoet.trackEvent = function emptyFunction(
      name,
      data,
      options,
      callback,
    ) {
      if (typeof callback === 'function') {
        callback();
      }
    };
  }
}

function trackIfEnabled(event) {
  if (window.mailpoet_analytics_enabled || event.forced) {
    track(event.name, event.data, event.options);
  }
}

function trackCachedEvents() {
  const storageItem = localStorage.getItem(LOCALSTORAGE_KEY);
  if (storageItem && window.mailpoet_analytics_enabled) {
    const localEventsCache = JSON.parse(storageItem);
    localEventsCache.forEach(trackIfEnabled);
    localStorage.removeItem(LOCALSTORAGE_KEY);
    return;
  }

  eventsCache.forEach(trackIfEnabled);
}

function cacheEvent(forced, name, data, options, callback) {
  eventsCache.push({
    name: name,
    data: data,
    options: options,
    forced: forced,
  });
  if (options === CacheEventOptionSaveInStorage) {
    localStorage.setItem(LOCALSTORAGE_KEY, JSON.stringify(eventsCache));
  }
  if (typeof callback === 'function') {
    callback();
  }
}

export function initializeMixpanelWhenLoaded() {
  if (typeof window.mixpanel === 'object') {
    exportMixpanel();
    trackCachedEvents();
  } else {
    setTimeout(initializeMixpanelWhenLoaded, 100);
  }
}

export const MailPoetTrackEvent = _.partial(cacheEvent, false);
export const MailPoetForceTrackEvent = _.partial(cacheEvent, true);
