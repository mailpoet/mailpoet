import { select } from '@wordpress/data';
import { get } from 'lodash';

import { STORE_NAME } from 'settings/store/store-name';
import { Action, ReEngagement } from 'settings/store/types';
import { updateKeyActivationState } from './key-activation';

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export function setSetting(path: string[], value: any): Action {
  return { type: 'SET_SETTING', path, value };
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export function setSettings(value: any): Action {
  return { type: 'SET_SETTINGS', value };
}

export function setSaveDone(): Action {
  return { type: 'SAVE_DONE' };
}

export function setErrorFlag(value: boolean): Action {
  return { type: 'SET_ERROR_FLAG', value };
}

export function setReEngagement(value: ReEngagement): Action {
  return { type: 'SET_RE_ENGAGEMENT_NOTICE', value };
}

function* afterSave(meta: unknown) {
  yield { type: 'TRACK_SETTINGS_SAVED' };
  yield { type: 'REFRESH_HELD_CAMPAIGNS' };
  yield updateKeyActivationState({
    congratulatoryMssEmailSentTo: null,
    fromAddressModalCanBeShown: false,
  });
  yield setReEngagement(meta as ReEngagement);
  yield { type: 'TRACK_UNAUTHORIZED_EMAIL', meta };
}

export function* saveSettings() {
  yield { type: 'SAVE_STARTED' };
  const data = select(STORE_NAME).getSettings();

  // trim all strings before saving
  const stringified = JSON.stringify(data);
  const parsed = JSON.parse(stringified, (_, value) =>
    typeof value === 'string' ? value.trim() : value,
  );

  const { success, error, res } = yield {
    type: 'CALL_API',
    endpoint: 'settings',
    action: 'set',
    data: parsed,
  };
  if (!success) {
    return { type: 'SAVE_FAILED', error };
  }
  yield setSettings(res.data);
  yield* afterSave(res.meta);
  return { type: 'SAVE_DONE' };
}

/**
 * Saves only the given settings, so unsaved edits on other tabs stay unsaved.
 * The keys are posted dotted, e.g. "mta.method", so the rest of "mta" is kept.
 */
export function* saveSettingPaths(paths: string[][]) {
  yield { type: 'SAVE_STARTED' };
  const settings = select(STORE_NAME).getSettings();
  const data = {};
  paths.forEach((path) => {
    const value: unknown = get(settings, path);
    data[path.join('.')] = typeof value === 'string' ? value.trim() : value;
  });

  const { success, error, res } = yield {
    type: 'CALL_API',
    endpoint: 'settings',
    action: 'set',
    data,
  };
  if (!success) {
    return { type: 'SAVE_FAILED', error };
  }
  yield* afterSave(res.meta);
  return {
    type: 'PARTIAL_SAVE_DONE',
    values: paths.map((path) => ({ path, value: get(res.data, path) })),
  };
}

export function* loadSettings() {
  const { success, error, res } = yield {
    type: 'CALL_API',
    endpoint: 'settings',
    action: 'get',
  };
  if (!success) {
    return { type: 'SAVE_FAILED', error };
  }
  return setSettings(res.data);
}
