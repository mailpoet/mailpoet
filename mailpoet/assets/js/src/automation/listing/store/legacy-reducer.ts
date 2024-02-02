import { State } from './types';
import { Automation } from '../automation';

export function legacyReducer(state: State, action): State {
  switch (action.type) {
    case 'SET_LEGACY_AUTOMATIONS':
      return {
        ...state,
        legacyAutomations: action.automations,
      };
    case 'UPDATE_LEGACY_AUTOMATION_STATUS':
      return {
        ...state,
        legacyAutomations: state.legacyAutomations.map(
          (automation: Automation) =>
            automation.id === action.id
              ? { ...automation, status: action.status }
              : automation,
        ),
      };
    case 'DELETE_LEGACY_AUTOMATION':
      return {
        ...state,
        legacyAutomations: state.legacyAutomations.filter(
          (automation: Automation) => automation.id !== action.id,
        ),
      };
    default:
      return state;
  }
}
