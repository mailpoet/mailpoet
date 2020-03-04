import { Action, Settings } from './types';

export function setSetting(path: string[], value: any): Action {
  return { type: 'SET_SETTING', path, value };
}

export function* saveSettings(data: Settings) {
  yield { type: 'SAVE_STARTED' };
  const error = yield { type: 'SEND_DATA_TO_API', data };
  if (error) {
    return { type: 'SAVE_FAILED', error };
  }
  yield { type: 'TRACK_SETTINGS_SAVED', data };
  return { type: 'SAVE_DONE' };
}
