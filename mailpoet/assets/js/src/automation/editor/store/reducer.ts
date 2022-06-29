import { Action } from '@wordpress/data';
import { State } from './types';

export function reducer(state: State, action: Action): State {
  switch (action.type) {
    case 'TOGGLE_INSERTER_SIDEBAR':
      return {
        ...state,
        inserterSidebar: {
          ...state.inserterSidebar,
          isOpened: !state.inserterSidebar.isOpened,
        },
      };
    case 'SET_INSERTER_POPOVER_ANCHOR':
      return {
        ...state,
        inserterPopover: {
          ...state.inserterPopover,
          anchor: action.anchor,
        },
      };
    case 'SET_SELECTED_STEP':
      return {
        ...state,
        selectedStep: action.value,
      };
    case 'ACTIVATE':
      return {
        ...state,
        workflowData: action.workflow,
      };
    default:
      return state;
  }
}
