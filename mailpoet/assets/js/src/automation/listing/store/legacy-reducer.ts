import { State } from './types';

export function legacyReducer(state: State, action): State {
  switch (action.type) {
    case 'SET_LEGACY_AUTOMATIONS':
      return {
        ...state,
        legacyAutomations: action.automations,
      };
    default:
      return state;
  }
}
