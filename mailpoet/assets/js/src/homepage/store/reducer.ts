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
    default:
      return state;
  }
}
