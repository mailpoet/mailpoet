import { dispatch } from '@wordpress/data';
import { store as preferencesStore } from '@wordpress/preferences';

type PreferencesData = Record<string, Record<string, unknown>>;

const EMPTY_PREFERENCES: PreferencesData = {};
const LEGACY_PERSISTENCE_LAYER_FACTORY = '__unstableCreatePersistenceLayer';

let isInitialized = false;

function isRecord(value: unknown): value is Record<string, unknown> {
  return Boolean(value) && typeof value === 'object' && !Array.isArray(value);
}

export function getPreloadedPreferencesData(): PreferencesData {
  if (typeof window === 'undefined') {
    return EMPTY_PREFERENCES;
  }
  const preloadedData = window.mailpoet_preferences_data?.preloadedData;
  return isRecord(preloadedData)
    ? (preloadedData as PreferencesData)
    : EMPTY_PREFERENCES;
}

export function getPreloadedPreference<T>(
  scope: string,
  name: string,
): T | undefined {
  const scopedPreferences = getPreloadedPreferencesData()[scope];
  if (!isRecord(scopedPreferences)) {
    return undefined;
  }

  return scopedPreferences[name] as T | undefined;
}

function createPersistenceLayer():
  | MailPoetPreferencesPersistenceLayer
  | undefined {
  if (typeof window === 'undefined') {
    return undefined;
  }

  const preferencesData = window.mailpoet_preferences_data;
  const preferencesPersistence = window.wp?.preferencesPersistence;
  if (!preferencesData || !preferencesPersistence) {
    return undefined;
  }

  // Feature-detect and guard both factories: `create` is the stable API, the
  // `__unstable*` one only exists on older WordPress versions and may change
  // shape. On failure, degrade gracefully — preferences still work within the
  // session from the preloaded data, they just don't persist across reloads.
  if (typeof preferencesPersistence.create === 'function') {
    try {
      return preferencesPersistence.create({
        preloadedData: getPreloadedPreferencesData(),
        localStorageRestoreKey: `WP_PREFERENCES_USER_${preferencesData.currentUserId}`,
      });
    } catch {
      return undefined;
    }
  }

  const createLegacyPersistenceLayer =
    preferencesPersistence[LEGACY_PERSISTENCE_LAYER_FACTORY];
  if (typeof createLegacyPersistenceLayer === 'function') {
    try {
      return createLegacyPersistenceLayer(
        getPreloadedPreferencesData(),
        preferencesData.currentUserId,
      );
    } catch {
      return undefined;
    }
  }

  return undefined;
}

export function initializeMailPoetPreferences(): void {
  if (isInitialized) {
    return;
  }
  isInitialized = true;

  const persistenceLayer = createPersistenceLayer();
  if (!persistenceLayer) {
    return;
  }

  void dispatch(preferencesStore).setPersistenceLayer(persistenceLayer);
}
