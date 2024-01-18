export type TaskListState = {
  isTaskListHidden: boolean;
  tasksStatus: TaskListTasksStatus | null;
  canImportWooCommerceSubscribers: boolean;
  hasImportedSubscribers: boolean;
  isNewUserForSenderDomainAuth: boolean;
  mssActive: boolean;
  isFreeMailUser: boolean;
};

export type ProductDiscoveryState = {
  isHidden: boolean;
  tasksStatus: ProductDiscoveryTasksStatus | null;
};

export type SubscribersCountChange = {
  subscribed: number;
  unsubscribed: number;
  changePercent: number;
};

export type ListSubscribersCountChange = {
  subscribed: number;
  unsubscribed: number;
  name: string;
  id: number;
  type: string;
  averageEngagementScore: number;
};

export type TaskListTasksStatus = {
  senderSet: boolean;
  mssConnected: boolean;
  subscribersAdded: boolean;
  wooSubscribersImported: boolean;
  senderDomainAuthenticated: boolean;
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
  subscribersStats: {
    global: SubscribersCountChange;
    lists: ListSubscribersCountChange[];
  };
  isWooCommerceActive: boolean;
  formsCount: number;
};
