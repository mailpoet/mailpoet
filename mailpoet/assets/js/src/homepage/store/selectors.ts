import { State, TaskListTasksStatus, TaskType } from './types';

export function getIsTaskListHidden(state: State): boolean {
  return state.taskList.isTaskListHidden;
}

export function getTasksStatus(state: State): TaskListTasksStatus {
  return state.taskList.tasksStatus;
}

export function getCanImportWooCommerceSubscribers(state: State): boolean {
  return state.taskList.canImportWooCommerceSubscribers;
}

export function getCurrentTask(state: State): TaskType | null {
  if (!state.taskList.tasksStatus.senderSet) return 'senderSet';
  if (!state.taskList.tasksStatus.mssConnected) return 'mssConnected';
  if (
    !state.taskList.tasksStatus.wooSubscribersImported &&
    state.taskList.canImportWooCommerceSubscribers
  )
    return 'wooSubscribersImported';
  if (!state.taskList.tasksStatus.subscribersAdded) return 'subscribersAdded';
  return null;
}
