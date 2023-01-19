import { Action } from '@wordpress/data';
import { State } from './types';

export function reducer(state: State, action: Action): State {
  switch (action.type) {
    case 'SET_TASK_LIST_HIDDEN':
      return {
        ...state,
        taskList: {
          ...state.taskList,
          isTaskListHidden: true,
        },
      };
    case 'SET_PRODUCT_DISCOVERY_HIDDEN':
      return {
        ...state,
        productDiscovery: {
          ...state.productDiscovery,
          isHidden: true,
        },
      };
    case 'SET_UPSELL_HIDDEN':
      return {
        ...state,
        upsell: {
          ...state.upsell,
          isHidden: true,
        },
      };
    default:
      return state;
  }
}
