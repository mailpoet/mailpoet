import { setWith, clone, isEqual } from 'lodash';
import {
  State,
  Action,
  KeyActivationState,
  MssStatus,
  PremiumStatus,
  TestEmailState,
} from './types';
import { normalizeSettings } from './normalize_settings';

export function createReducer(defaultValue: State) {
  let keyActivation: KeyActivationState;
  return (
    state: State = defaultValue, // eslint-disable-line @typescript-eslint/default-param-last
    action: Action,
  ): State => {
    switch (action.type) {
      case 'SET_SETTING': {
        const newState = setWith(
          clone(state),
          ['data', ...action.path],
          action.value,
          clone,
        );

        newState.save.hasUnsavedChanges = !isEqual(
          newState.data,
          state.originalData,
        );

        return newState;
      }
      case 'SET_SETTINGS': {
        const newState = {
          ...state,
          data: normalizeSettings(action.value as Record<string, unknown>),
        };

        newState.save.hasUnsavedChanges = !isEqual(
          newState.data,
          state.originalData,
        );

        return newState;
      }
      case 'SET_ERROR_FLAG':
        return { ...state, flags: { ...state.flags, error: !!action.value } };
      case 'SAVE_STARTED':
        return {
          ...state,
          save: {
            inProgress: true,
            error: null,
            hasUnsavedChanges: state.save.hasUnsavedChanges,
          },
        };
      case 'SAVE_DONE':
        return {
          ...state,
          save: { inProgress: false, error: null, hasUnsavedChanges: false },
        };
      case 'SAVE_FAILED':
        return {
          ...state,
          save: {
            inProgress: false,
            error: action.error,
            hasUnsavedChanges: state.save.hasUnsavedChanges,
          },
        };
      case 'SET_RE_ENGAGEMENT_NOTICE':
        return {
          ...state,
          reEngagement: {
            showNotice: action.value.showNotice,
            action: action.value.action,
          },
        };
      case 'UPDATE_KEY_ACTIVATION_STATE':
        keyActivation = { ...state.keyActivation, ...action.fields };
        keyActivation.isKeyValid = null;
        if (
          keyActivation.mssStatus !== null &&
          keyActivation.premiumStatus !== null
        ) {
          keyActivation.isKeyValid =
            keyActivation.mssStatus !== MssStatus.INVALID ||
            keyActivation.premiumStatus !== PremiumStatus.INVALID;
        }
        return { ...state, keyActivation };
      case 'START_TEST_EMAIL_SENDING':
        return {
          ...state,
          testEmail: { state: TestEmailState.SENDING, error: null },
        };
      case 'TEST_EMAIL_SUCCESS':
        return {
          ...state,
          testEmail: { state: TestEmailState.SUCCESS, error: null },
        };
      case 'TEST_EMAIL_FAILED':
        return {
          ...state,
          testEmail: { state: TestEmailState.FAILURE, error: action.error },
        };
      default:
        return state;
    }
  };
}
