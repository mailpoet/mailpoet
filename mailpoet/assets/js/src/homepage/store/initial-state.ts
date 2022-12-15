import { State } from './types';

export function getInitialState(): State {
  return {
    taskList: {
      isTaskListHidden: false,
    },
  };
}
