/*
 * This creates two functions and adds them to MailPoet object
 * - `trackEvent` which should be used in normal circumstances.
 *   This function tracks an event and sends it to mixpanel and MailPoet Tracks in parallel.
 *   This function does nothing if analytics is disabled.
 * - `forceTrackEvent` which sends given event to analytics
 *   even if it has been disabled.
 *
 */
import _ from 'underscore';

/**
 *  This is to cache events which are triggered before the analytics
 *  libraries are loaded. This might happen if an event is tracked
 *  on page load and the libraries take a long time to load.
 *  After they are loaded all events are posted.
 * @type {Array.Object}
 */
var eventsCache = [];
var tracksEventsCache = [];

const LOCALSTORAGE_KEY = 'mailpoet-track-events-cache';
const TRACKS_LOCALSTORAGE_KEY = 'mailpoet-tracks-events-cache';
export const CacheEventOptionSaveInStorage = 'saveInStorage';

function convertDataForTracks(data) {
  if (!data || typeof data !== 'object') {
    return {};
  }

  // Brand names and compound words that should stay together
  const specialCases = {
    MailPoet: 'mailpoet',
    WooCommerce: 'woocommerce',
    WordPress: 'wordpress',
  };

  const converted = {};
  Object.entries(data).forEach(([key, value]) => {
    let convertedKey = key;

    Object.entries(specialCases).forEach(([original, replacement]) => {
      convertedKey = convertedKey.replace(
        new RegExp(original, 'g'),
        replacement,
      );
    });

    convertedKey = convertedKey
      .replace(/([A-Z])/g, '_$1')
      .toLowerCase()
      .replace(/[^a-z0-9_]/g, '_')
      .replace(/_{2,}/g, '_')
      .replace(/^_|_$/g, '');

    converted[convertedKey] = value;
  });

  return converted;
}

function convertEventNameForTracks(eventName) {
  return eventName
    .toLowerCase()
    .replace(/[^a-z0-9]/g, '_')
    .replace(/_{2,}/g, '_')
    .replace(/^_|_$/g, '');
}

function trackToMixpanel(name, data = [], options = {}, callback = null) {
  const optionsData = options === CacheEventOptionSaveInStorage ? {} : options;

  if (typeof window.mixpanel.track !== 'function') {
    window.mixpanel.init(window.mixpanelTrackingId, window.mixpanelInitConfig);
  }

  if (!window.mixpanelIsLoaded) {
    if (callback) {
      callback();
    }
  } else {
    window.mixpanel.track(name, data, optionsData, callback);
  }
}

let tracksRequestQueue = [];

function createMailPoetTracks() {
  if (!window.mailpoetTracks) {
    window.mailpoetTracks = {
      isEnabled: true,
      recordEvent: (name, properties) => {
        const publicId = window.mailpoet_analytics_public_id || '';
        const eventName = 'mailpoet_' + name;

        if (!publicId) {
          return;
        }

        const payload = {
          commonProps: {
            public_id: publicId,
            ...properties,
          },
          events: [
            {
              _ut: 'anon',
              _ui: publicId,
              _en: eventName,
            },
          ],
        };

        tracksRequestQueue.push(payload);
      },
      flushQueue: () => {
        if (tracksRequestQueue.length === 0) {
          return;
        }

        tracksRequestQueue.forEach((payload) => {
          fetch('https://public-api.wordpress.com/rest/v1.1/tracks/record', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              Accept: 'application/json',
              'User-Agent': 'MailPoet Plugin',
            },
            body: JSON.stringify(payload),
            keepalive: true,
          }).catch(() => {
            // Fail silently to avoid breaking the user experience
          });
        });

        tracksRequestQueue = [];
      },
    };
  }
}

function trackToTracks(name, data = []) {
  try {
    createMailPoetTracks();

    const tracksEventName = convertEventNameForTracks(name);

    const tracksData = convertDataForTracks(data);

    if (
      window.mailpoetTracks &&
      typeof window.mailpoetTracks.recordEvent === 'function'
    ) {
      window.mailpoetTracks.recordEvent(tracksEventName, tracksData);
    }
  } catch (error) {
    // Fail silently to avoid breaking the user experience
  }
}

function track(name, data = [], options = {}, callback = null) {
  let trackedData = data;

  if (typeof window.mailpoet_version !== 'undefined') {
    trackedData['MailPoet Free version'] = window.mailpoet_version;
  }

  if (typeof window.mailpoet_premium_version !== 'undefined') {
    trackedData['MailPoet Premium version'] = window.mailpoet_premium_version;
  }

  // Track to MixPanel immediately
  trackToMixpanel(name, trackedData, options, callback);

  // For tracks, queue the event to be sent later
  trackToTracks(name, trackedData);
}

function trackIfEnabled(event) {
  if (window.mailpoet_analytics_enabled || event.forced) {
    track(event.name, event.data, event.options, event.callback);
  }
}

function trackTracksIfEnabled(event) {
  if (window.mailpoet_analytics_enabled || event.forced) {
    createMailPoetTracks();

    trackToTracks(event.name, event.data);
  }
}

function trackCachedEvents() {
  if (typeof localStorage !== 'undefined') {
    const storageItem = localStorage.getItem(LOCALSTORAGE_KEY);
    if (storageItem && window.mailpoet_analytics_enabled) {
      const localEventsCache = JSON.parse(storageItem);
      localEventsCache.forEach(trackIfEnabled);
      localStorage.removeItem(LOCALSTORAGE_KEY);
      return;
    }
  }

  eventsCache.forEach(trackIfEnabled);
}

function trackCachedTracksEvents() {
  if (typeof localStorage !== 'undefined') {
    const storageItem = localStorage.getItem(TRACKS_LOCALSTORAGE_KEY);
    if (storageItem && window.mailpoet_analytics_enabled) {
      const localTracksEventsCache = JSON.parse(storageItem);
      localTracksEventsCache.forEach(trackTracksIfEnabled);
      localStorage.removeItem(TRACKS_LOCALSTORAGE_KEY);
      return;
    }
  }

  tracksEventsCache.forEach(trackTracksIfEnabled);
}

function cacheEvent(forced, name, data, options, callback) {
  eventsCache.push({
    name: name,
    data: data,
    options: options,
    callback: callback,
    forced: forced,
  });
  if (
    options === CacheEventOptionSaveInStorage &&
    typeof localStorage !== 'undefined'
  ) {
    localStorage.setItem(LOCALSTORAGE_KEY, JSON.stringify(eventsCache));
  }
  if (typeof callback === 'function') {
    callback();
  }
}

function cacheTracksEvent(forced, name, data, options) {
  tracksEventsCache.push({
    name: name,
    data: data,
    options: options,
    forced: forced,
  });
  if (
    options === CacheEventOptionSaveInStorage &&
    typeof localStorage !== 'undefined'
  ) {
    localStorage.setItem(
      TRACKS_LOCALSTORAGE_KEY,
      JSON.stringify(tracksEventsCache),
    );
  }
}

export function queueTrackEvent(
  name,
  data = [],
  options = {},
  callback = null,
) {
  let trackedData = { ...data };

  if (typeof window.mailpoet_version !== 'undefined') {
    trackedData['MailPoet Free version'] = window.mailpoet_version;
  }

  if (typeof window.mailpoet_premium_version !== 'undefined') {
    trackedData['MailPoet Premium version'] = window.mailpoet_premium_version;
  }

  cacheEvent(false, name, trackedData, options, callback);
  cacheTracksEvent(false, name, trackedData, options);
}

export function processAllCachedEvents() {
  trackCachedEvents();
  trackCachedTracksEvents();

  if (
    window.mailpoetTracks &&
    typeof window.mailpoetTracks.flushQueue === 'function'
  ) {
    window.mailpoetTracks.flushQueue();
  }
}

function exportAnalytics() {
  window.MailPoet.forceTrackEvent = track;

  if (
    window.mailpoet_analytics_enabled &&
    window.mailpoet_3rd_party_libs_enabled
  ) {
    window.MailPoet.trackEvent = track;

    window.MailPoet.queueTrackEvent = queueTrackEvent;
    window.MailPoet.processAllCachedEvents = processAllCachedEvents;
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

    window.MailPoet.queueTrackEvent = function emptyQueueFunction(
      name,
      data,
      options,
      callback,
    ) {
      if (typeof callback === 'function') {
        callback();
      }
    };
    window.MailPoet.processAllCachedEvents =
      function emptyProcessAllCachedEvents() {};
  }
}

export function initializeAnalyticsWhenLoaded() {
  const MAX_RETRY = 5;
  let intervalId;
  let retryCount = 0;

  const setupAnalytics = () => {
    exportAnalytics();
    trackCachedEvents();
    trackCachedTracksEvents();

    // Set up event listeners to flush tracks queue on page unload
    const flushTracksQueue = () => {
      if (
        window.mailpoetTracks &&
        typeof window.mailpoetTracks.flushQueue === 'function'
      ) {
        window.mailpoetTracks.flushQueue();
      }
    };

    // Flush queue before page unloads
    window.addEventListener('beforeunload', flushTracksQueue);
    window.addEventListener('pagehide', flushTracksQueue);

    // Also flush queue periodically (every 30 seconds)
    setInterval(flushTracksQueue, 30000);
  };

  const checkLibrariesReady = () => {
    const mixpanelReady = typeof window.mixpanel === 'object';
    // mailpoetTracks is now created by our JS, so always consider it ready
    const tracksReady = true;

    return mixpanelReady || tracksReady; // At least one should be ready
  };

  if (checkLibrariesReady()) {
    setupAnalytics();
  } else {
    intervalId = setInterval(() => {
      if (checkLibrariesReady()) {
        clearInterval(intervalId);
        setupAnalytics();
      } else {
        retryCount += 1;
      }

      if (retryCount > MAX_RETRY) {
        clearInterval(intervalId);
      }
    }, 100);
  }
}

export const MailPoetTrackEvent = _.partial(cacheEvent, false);
export const MailPoetForceTrackEvent = _.partial(cacheEvent, true);

export const initializeMixpanelWhenLoaded = initializeAnalyticsWhenLoaded;
