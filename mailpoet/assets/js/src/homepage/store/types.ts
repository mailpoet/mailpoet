export type TaskListState = {
  isTaskListHidden: boolean;
  tasksStatus: TaskListTasksStatus;
  canImportWooCommerceSubscribers: boolean;
};

export type TaskListTasksStatus = {
  senderSet: boolean;
  mssConnected: boolean;
  subscribersAdded: boolean;
  wooSubscribersImported: boolean;
};

export type TaskType = keyof TaskListTasksStatus;

export type State = {
  taskList: TaskListState;
};
