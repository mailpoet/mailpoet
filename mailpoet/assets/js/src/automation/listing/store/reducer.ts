import { AutomationItem, State } from './types';

export function reducer(state: State, action): State {
  switch (action.type) {
    case 'SET_AUTOMATIONS':
      return {
        ...state,
        automations: action.automations,
      };
    case 'ADD_AUTOMATION':
      return {
        ...state,
        automations: [action.automation, ...state.automations],
      };
    case 'UPDATE_AUTOMATION':
      return {
        ...state,
        automations: state.automations.map((automation: AutomationItem) =>
          automation.id === action.automation.id
            ? action.automation
            : automation,
        ),
      };
    case 'DELETE_AUTOMATION':
      return {
        ...state,
        automations: state.automations.filter(
          (automation: AutomationItem) =>
            automation.id !== action.automation.id,
        ),
      };
    default:
      return state;
  }
}
