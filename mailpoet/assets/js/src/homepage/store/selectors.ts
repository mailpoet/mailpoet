import { State, TaskListTasksStatus } from './types';

export function getIsTaskListHidden(state: State): boolean {
  return state.taskList.isTaskListHidden;
}

export function getTasksStatus(state: State): TaskListTasksStatus {
  return state.taskList.tasksStatus;
}
