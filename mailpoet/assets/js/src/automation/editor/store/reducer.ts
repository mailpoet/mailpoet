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
    default:
      return state;
  }
}
