import { select } from '@wordpress/data';

import { STORE_NAME } from 'settings/store/store_name';
import { Action, ReEngagement } from 'settings/store/types';
import { updateKeyActivationState } from './key_activation';

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
  yield { type: 'TRACK_SETTINGS_SAVED' };
  yield updateKeyActivationState({
    congratulatoryMssEmailSentTo: null,
    fromAddressModalCanBeShown: false,
  });
  yield setSettings(res.data);
  yield setReEngagement(res.meta as ReEngagement);
  yield { type: 'TRACK_UNAUTHORIZED_EMAIL', meta: res.meta };
  return { type: 'SAVE_DONE' };
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
