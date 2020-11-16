import { select } from '@wordpress/data';

import { STORE_NAME } from 'settings/store';
import { Action } from 'settings/store/types';
import { updateKeyActivationState } from './mss_and_premium';

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export function setSetting(path: string[], value: any): Action {
  return { type: 'SET_SETTING', path, value };
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export function setSettings(value: any): Action {
  return { type: 'SET_SETTINGS', value };
}

export function setErrorFlag(value: boolean): Action {
  return { type: 'SET_ERROR_FLAG', value };
}

export function* saveSettings() {
  yield { type: 'SAVE_STARTED' };
  const data = select(STORE_NAME).getSettings();
  const { success, error, res } = yield {
    type: 'CALL_API',
    endpoint: 'settings',
    action: 'set',
    data,
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
