import type { Field, View } from '@wordpress/dataviews';
import { dispatch, select } from '@wordpress/data';
import { store as preferencesStore } from '@wordpress/preferences';
import { useCallback, useRef } from 'react';

import { getPreloadedPreference } from '../preferences';

// Read the live store first so preferences persisted during the session are
// reflected; fall back to the server-preloaded snapshot for reads that happen
// before the store is initialized.
function readPreference(scope: string, name: string): unknown {
  const fromStore: unknown = select(preferencesStore).get(scope, name);
  if (fromStore !== undefined) {
    return fromStore;
  }
  return getPreloadedPreference<unknown>(scope, name);
}

const VIEW_PREFERENCES_SCOPE = 'core/views';
const MAX_PER_PAGE = 100;

type DataViewsField = Pick<Field<unknown>, 'id'>;

type PersistedView = Partial<View> & Record<string, unknown>;
type OnChangeView = (view: View) => void;

export function getDataViewsPreferenceKey(
  name: string,
  slug = 'default',
): string {
  return `dataviews-mailpoet-${name}-${slug}`;
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return Boolean(value) && typeof value === 'object' && !Array.isArray(value);
}

function getAvailableFieldIds(
  fields?: readonly DataViewsField[],
): Set<string> | undefined {
  return fields ? new Set(fields.map((field) => field.id)) : undefined;
}

function isAllowedField(
  field: unknown,
  availableFieldIds?: Set<string>,
): field is string {
  return (
    typeof field === 'string' &&
    (!availableFieldIds || availableFieldIds.has(field))
  );
}

function getPreferredFields(
  fields: unknown,
  availableFieldIds?: Set<string>,
): string[] | undefined {
  if (!Array.isArray(fields)) {
    return undefined;
  }
  const preferredFields = fields.filter((field) =>
    isAllowedField(field, availableFieldIds),
  );
  if (fields.length > 0 && preferredFields.length === 0) {
    return undefined;
  }
  return preferredFields;
}

function getPreferredPerPage(perPage: unknown): number | undefined {
  if (
    typeof perPage !== 'number' ||
    !Number.isInteger(perPage) ||
    perPage < 1
  ) {
    return undefined;
  }
  return Math.min(perPage, MAX_PER_PAGE);
}

function getPreferredSort(
  sort: unknown,
  availableFieldIds?: Set<string>,
): View['sort'] | undefined {
  if (!isRecord(sort)) {
    return undefined;
  }
  if (
    !isAllowedField(sort.field, availableFieldIds) ||
    (sort.direction !== 'asc' && sort.direction !== 'desc')
  ) {
    return undefined;
  }
  return { field: sort.field, direction: sort.direction };
}

function getBooleanPreference(
  preference: PersistedView,
  key: 'showTitle' | 'showDescription' | 'showMedia',
): boolean | undefined {
  return typeof preference[key] === 'boolean' ? preference[key] : undefined;
}

function getFieldPreference(
  preference: PersistedView,
  key: 'titleField' | 'descriptionField' | 'mediaField',
  availableFieldIds?: Set<string>,
): string | undefined {
  if (isAllowedField(preference[key], availableFieldIds)) {
    return preference[key];
  }
  return undefined;
}

export function getDataViewsPreference<T>(
  name: string,
  defaultView: View,
  fields?: readonly Field<T>[],
): View {
  const preference = readPreference(
    VIEW_PREFERENCES_SCOPE,
    getDataViewsPreferenceKey(name),
  );
  if (!isRecord(preference)) {
    return defaultView;
  }

  const availableFieldIds = getAvailableFieldIds(fields);
  const persistedView = preference as PersistedView;
  const view: View = { ...defaultView };

  const preferredPerPage = getPreferredPerPage(persistedView.perPage);
  if (preferredPerPage) {
    view.perPage = preferredPerPage;
  }

  const preferredSort = getPreferredSort(persistedView.sort, availableFieldIds);
  if (preferredSort) {
    view.sort = preferredSort;
  }

  const preferredFields = getPreferredFields(
    persistedView.fields,
    availableFieldIds,
  );
  if (preferredFields) {
    view.fields = preferredFields;
  }

  if (isRecord(persistedView.layout)) {
    view.layout = persistedView.layout;
  }

  const titleField = getFieldPreference(
    persistedView,
    'titleField',
    availableFieldIds,
  );
  if (titleField) {
    view.titleField = titleField;
  }

  const descriptionField = getFieldPreference(
    persistedView,
    'descriptionField',
    availableFieldIds,
  );
  if (descriptionField) {
    view.descriptionField = descriptionField;
  }

  const mediaField = getFieldPreference(
    persistedView,
    'mediaField',
    availableFieldIds,
  );
  if (mediaField) {
    view.mediaField = mediaField;
  }

  const showTitle = getBooleanPreference(persistedView, 'showTitle');
  if (showTitle !== undefined) {
    view.showTitle = showTitle;
  }

  const showDescription = getBooleanPreference(
    persistedView,
    'showDescription',
  );
  if (showDescription !== undefined) {
    view.showDescription = showDescription;
  }

  const showMedia = getBooleanPreference(persistedView, 'showMedia');
  if (showMedia !== undefined) {
    view.showMedia = showMedia;
  }

  return view;
}

export function getPersistedDataViewsPreference(view: View): Partial<View> {
  const preference: Partial<View> = {
    type: view.type,
  };

  if (view.perPage) {
    preference.perPage = view.perPage;
  }
  if (view.sort) {
    preference.sort = view.sort;
  }
  if (Array.isArray(view.fields)) {
    preference.fields = view.fields;
  }
  if (isRecord(view.layout)) {
    preference.layout = view.layout;
  }
  if (view.titleField) {
    preference.titleField = view.titleField;
  }
  if (view.descriptionField) {
    preference.descriptionField = view.descriptionField;
  }
  if (view.mediaField) {
    preference.mediaField = view.mediaField;
  }
  if (typeof view.showTitle === 'boolean') {
    preference.showTitle = view.showTitle;
  }
  if (typeof view.showDescription === 'boolean') {
    preference.showDescription = view.showDescription;
  }
  if (typeof view.showMedia === 'boolean') {
    preference.showMedia = view.showMedia;
  }

  return preference;
}

export function persistDataViewsPreference(
  name: string,
  view: View,
  slug = 'default',
): void {
  void dispatch(preferencesStore).set(
    VIEW_PREFERENCES_SCOPE,
    getDataViewsPreferenceKey(name, slug),
    getPersistedDataViewsPreference(view),
  );
}

export function isSameDataViewsPreference(view: View, nextView: View): boolean {
  return (
    JSON.stringify(getPersistedDataViewsPreference(view)) ===
    JSON.stringify(getPersistedDataViewsPreference(nextView))
  );
}

export function usePersistedDataViewsPreference(
  name: string,
  view: View,
  onChangeView: OnChangeView,
  slug = 'default',
): OnChangeView {
  const viewRef = useRef(view);
  viewRef.current = view;
  return useCallback(
    (nextView: View) => {
      // DataViews fires spurious `onChangeView` calls (e.g. its search box
      // syncs an undefined `view.search` to '' on mount). Persisting then
      // would freeze the current defaults as a user preference, overriding
      // later server-driven defaults (per-page, tracking-dependent columns).
      // Persist only when a persisted-view property actually changed.
      if (!isSameDataViewsPreference(viewRef.current, nextView)) {
        persistDataViewsPreference(name, nextView, slug);
      }
      onChangeView(nextView);
    },
    [name, onChangeView, slug],
  );
}
