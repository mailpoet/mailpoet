import {
  ProductDiscoveryTasksStatus,
  State,
  TaskListTasksStatus,
  TaskType,
} from './types';

export function getIsTaskListHidden(state: State): boolean {
  return state.taskList.isTaskListHidden;
}

export function getTasksStatus(state: State): TaskListTasksStatus {
  return state.taskList.tasksStatus;
}

export function getProductDiscoveryTasksStatus(
  state: State,
): ProductDiscoveryTasksStatus {
  return state.productDiscovery.tasksStatus;
}

export function getCanImportWooCommerceSubscribers(state: State): boolean {
  return state.taskList.canImportWooCommerceSubscribers;
}

export function getHasImportedSubscribers(state: State): boolean {
  return state.taskList.hasImportedSubscribers;
}

export function getIsProductDiscoveryHidden(state: State): boolean {
  return state.productDiscovery.isHidden;
}

export function getIsProductDiscoveryDone(state: State): boolean {
  const status = state.productDiscovery.tasksStatus;
  if (state.isWooCommerceActive) {
    return (
      status.addSubscriptionForm &&
      status.setUpWelcomeCampaign &&
      status.setUpAbandonedCartEmail &&
      status.brandWooEmails
    );
  }
  return (
    status.setUpWelcomeCampaign &&
    status.addSubscriptionForm &&
    status.sendFirstNewsletter
  );
}

export function getIsWooCommerceActive(state: State): boolean {
  return state.isWooCommerceActive;
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

export function getIsUpsellHidden(state: State): boolean {
  return state.upsell.isHidden;
}

export function getCanDisplayUpsell(state: State): boolean {
  return state.upsell.upsellStatus?.canDisplay;
}
