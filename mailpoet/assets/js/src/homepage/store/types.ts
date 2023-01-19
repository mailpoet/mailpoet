export type TaskListState = {
  isTaskListHidden: boolean;
  tasksStatus: TaskListTasksStatus | null;
  canImportWooCommerceSubscribers: boolean;
  hasImportedSubscribers: boolean;
};

export type ProductDiscoveryState = {
  isHidden: boolean;
  tasksStatus: ProductDiscoveryTasksStatus | null;
};

export type SubscribersCountChange = {
  subscribed: number;
  unsubscribed: number;
};

export type ListSubscribersCountChange = {
  subscribed: number;
  unsubscribed: number;
  name: string;
  id: number;
  type: string;
};

export type TaskListTasksStatus = {
  senderSet: boolean;
  mssConnected: boolean;
  subscribersAdded: boolean;
  wooSubscribersImported: boolean;
};

export type ProductDiscoveryTasksStatus = {
  setUpWelcomeCampaign: boolean;
  addSubscriptionForm: boolean;
  sendFirstNewsletter: boolean;
  setUpAbandonedCartEmail: boolean;
  brandWooEmails: boolean;
};

export type TaskType = keyof TaskListTasksStatus;

export type UpsellStatus = {
  canDisplay: boolean;
};

export type UpsellState = {
  isHidden: boolean;
  upsellStatus: UpsellStatus;
};

export type State = {
  taskList: TaskListState;
  productDiscovery: ProductDiscoveryState;
  upsell: UpsellState;
  isWooCommerceActive: boolean;
  subscribersStats: {
    global: SubscribersCountChange;
    lists: ListSubscribersCountChange[];
  };
};
