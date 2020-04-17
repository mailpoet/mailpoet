import _ from 'lodash';
import {
  State, Action, KeyActivationState, MssStatus, PremiumStatus,
} from './types';
import normalizeSettings from './normalize_settings';

export default function createReducer(defaultValue: State) {
  let keyActivation: KeyActivationState;
  return (state: State = defaultValue, action: Action): State => {
    switch (action.type) {
      case 'SET_SETTING':
        return _.setWith(_.clone(state), ['data', ...action.path], action.value, _.clone);
      case 'SET_SETTINGS':
        return { ...state, data: normalizeSettings(action.value) };
      case 'SET_ERROR_FLAG':
        return { ...state, flags: { ...state.flags, error: action.value } };
      case 'SAVE_STARTED':
        return { ...state, save: { inProgress: true, error: null } };
      case 'SAVE_DONE':
        return { ...state, save: { inProgress: false, error: null } };
      case 'SAVE_FAILED':
        return { ...state, save: { inProgress: false, error: action.error } };
      case 'UPDATE_KEY_ACTIVATION_STATE':
        keyActivation = { ...state.keyActivation, ...action.fields };
        keyActivation.isKeyValid = null;
        if (keyActivation.mssStatus !== null && keyActivation.premiumStatus !== null) {
          keyActivation.isKeyValid = (
            keyActivation.mssStatus !== MssStatus.INVALID
            || keyActivation.premiumStatus !== PremiumStatus.INVALID
          );
        }
        return { ...state, keyActivation };
      case 'START_TEST_EMAIL_SENDING':
        return { ...state, testEmail: { state: 'sending', error: null } };
      case 'TEST_EMAIL_SUCCESS':
        return { ...state, testEmail: { state: 'success', error: null } };
      case 'TEST_EMAIL_FAILED':
        return { ...state, testEmail: { state: 'failure', error: action.error } };
      default:
        return state;
    }
  };
}
