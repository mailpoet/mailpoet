import { State } from './types';
import { Automation } from '../automation';

export function legacyReducer(state: State, action): State {
  switch (action.type) {
    case 'SET_LEGACY_AUTOMATIONS':
      return {
        ...state,
        legacyAutomations: action.automations,
      };
    case 'UPDATE_LEGACY_AUTOMATION':
      return {
        ...state,
        legacyAutomations: state.legacyAutomations.map(
          (automation: Automation) =>
            automation.id === action.automation.id
              ? (action.automation as Automation)
              : automation,
        ),
      };
    default:
      return state;
  }
}
