export type TaskListState = {
  isTaskListHidden: boolean;
  tasksStatus: TaskListTasksStatus;
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
