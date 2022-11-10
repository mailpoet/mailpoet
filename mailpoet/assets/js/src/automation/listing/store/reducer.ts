import { Action } from '@wordpress/data';
import { State } from './types';
import { Automation } from '../automation';

export function reducer(state: State, action: Action): State {
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
        automations: state.automations.map((automation: Automation) =>
          automation.id === action.automation.id
            ? action.automation
            : automation,
        ),
      };
    case 'DELETE_AUTOMATION':
      return {
        ...state,
        automations: state.automations.filter(
          (automation: Automation) => automation.id !== action.automation.id,
        ),
      };
    default:
      return state;
  }
}
