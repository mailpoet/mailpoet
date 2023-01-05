export type TaskListState = {
  isTaskListHidden: boolean;
  tasksStatus: TaskListTasksStatus | null;
  canImportWooCommerceSubscribers: boolean;
  hasImportedSubscribers: boolean;
};

export type ProductDiscoveryState = {
  isHidden: boolean;
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
  productDiscovery: ProductDiscoveryState;
};
