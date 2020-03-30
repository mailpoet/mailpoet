import _ from 'lodash';
import { State, Action, KeyActivationState } from './types';

export default function createReducer(defaultValue: State) {
  let keyActivation: KeyActivationState;
  return (state: State = defaultValue, action: Action): State => {
    switch (action.type) {
      case 'SET_SETTING':
        return _.setWith(_.clone(state), ['data', ...action.path], action.value, _.clone);
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
            keyActivation.mssStatus !== 'invalid'
            || keyActivation.premiumStatus !== 'invalid'
          );
        }
        return { ...state, keyActivation };
      default:
        return state;
    }
  };
}
