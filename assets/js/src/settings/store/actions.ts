import { select } from '@wordpress/data';
import { STORE_NAME } from '.';
import { Action } from './types';

export function setSetting(path: string[], value: any): Action {
  return { type: 'SET_SETTING', path, value };
}

export function* saveSettings() {
  yield { type: 'SAVE_STARTED' };
  const data = select(STORE_NAME).getSettings();
  const { success, error } = yield {
    type: 'CALL_API',
    endpoint: 'settings',
    action: 'set',
    data,
  };
  if (!success) {
    return { type: 'SAVE_FAILED', error };
  }
  yield { type: 'TRACK_SETTINGS_SAVED' };
  return { type: 'SAVE_DONE' };
}
